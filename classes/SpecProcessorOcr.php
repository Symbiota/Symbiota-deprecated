<?php
/*
 * Used by automatic nightly process and by the occurrence editor (/collections/editor/occurrenceeditor.php)
 */
include_once($SERVER_ROOT.'/config/dbconnection.php');

class SpecProcessorOcr{

	private $conn;
	private $tempPath;
	private $imgUrlLocal;
	private $deleteAllOcrFiles = 0;

	private $cropX = 0;
	private $cropY = 0;
	private $cropW = 1;
	private $cropH = 1;

	private $collid;
	private $specKeyPattern;
	private $ocrSource;
	
	//If silent is set, script will produce no non-fatal output.
	private $verbose = 0;			//0 = silent, 1 = logFile, 2 = echo, 3 = both
	private $logFH;
	private $errorStr;
	
	function __construct() {
		$this->setTempPath();
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		if($this->logFH) fclose($this->logFH);
 		if(!($this->conn === false)) $this->conn->close();
		//unlink($this->imgUrlLocal);
	}

	public function ocrImageById($imgid,$getBest = 0,$sciName=''){
		$rawStr = '';
		$sql = 'SELECT url, originalurl FROM images WHERE imgid = '.$imgid;
		if($rs = $this->conn->query($sql)){
			if($r = $rs->fetch_object()){
				$imgUrl = ($r->originalurl?$r->originalurl:$r->url);
				$rawStr = $this->ocrImageByUrl($imgUrl, $getBest, $sciName);
			}
			$rs->free();
		}
		return $rawStr;
	}
	
	private function ocrImageByUrl($imgUrl,$getBest = 0,$sciName=''){
		$rawStr = '';
		if($imgUrl){
			if($this->loadImage($imgUrl)){
				$this->cropImage();
				if($getBest){
					$rawStr = $this->getBestOCR($sciName);
				}
				else{
					$rawStr = $this->ocrImage();
				}
				if(!$rawStr) {
					//Check for and remove problematic boarder
					if($this->imageTrimBorder()){
						if($getBest){
							$rawStr = $this->getBestOCR($sciName);
						}
						else{
							$rawStr = $this->ocrImage();
						}
					}
					if(!$rawStr) $rawStr = 'Failed OCR return';
				}
				$rawStr = $this->cleanRawStr($rawStr);
				//Cleanup, remove image
				unlink($this->imgUrlLocal);
			}
			else{
				$err = 'ERROR: Unable to load image, URL: '.$imgUrl;
				$this->logMsg($err,1);
				$rawStr = 'ERROR';
			}
		}
		else{
			$err = 'ERROR: Empty URL';
			$this->logMsg($err,1);
			$rawStr = 'ERROR';
		}
		return $rawStr;
	}

	private function ocrImage($url = ""){
		global $tesseractPath;
		$retStr = '';
		if(!$url) $url = $this->imgUrlLocal;
		if($url){
			//OCR image, result text is output to $outputFile
			$output = array();
			$outputFile = substr($url,0,strlen($url)-4);
			if(isset($tesseractPath) && $tesseractPath){
				if(substr($tesseractPath,0,2) == 'C:'){
					//Full path to tesseract with quotes needed for Windows
					exec('"'.$tesseractPath.'" '.$url.' '.$outputFile,$output);
				}
				else{
					exec($tesseractPath.' '.$url.' '.$outputFile,$output);
				}
			}
			else{
				//If path is not set in the $symbini.php file, we assume a typial linux install
				exec('/usr/local/bin/tesseract '.$url.' '.$outputFile,$output);
			}

			//Obtain text from tesseract output file
			if(file_exists($outputFile.'.txt')){
				if($fh = fopen($outputFile.'.txt', 'r')){
					while (!feof($fh)) {
						$retStr .= $this->encodeString(fread($fh, 8192));
						//$retStr .= fread($fh, 8192);
					}
					fclose($fh);
				}
				unlink($outputFile.'.txt');
			}
			else{
				$this->logMsg("ERROR: Unable to locate output file",1);
			}
		}
		return $retStr;//$this->cleanRawStr($retStr);
	}

	private function databaseRawStr($imgId,$rawStr,$notes,$source){
		if(is_numeric($imgId) && $rawStr){
			$score = '';
			if($rawStr == 'Failed OCR return') $score = 0; 
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes,source,score) '.
				'VALUE ('.$imgId.',"'.$this->cleanInStr($rawStr).'",'.
				($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.
				($source?'"'.$this->cleanInStr($source).'"':'NULL').','.
				($score?'"'.$this->cleanInStr($score).'"':'NULL').')';
			//echo 'SQL: '.$sql."\n";
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->logMsg("ERROR: Unable to load fragment into database: ".$this->conn->error,1);
				$this->logMsg("SQL: ".$sql,2);
				return false;
			}
		}
	}

	private function loadImage($imgUrl){
		$status = false;
		if($imgUrl){
			if(substr($imgUrl,0,1)=="/"){
				if(array_key_exists("imageDomain",$GLOBALS) && $GLOBALS["imageDomain"]){
					//If there is an image domain name is set in symbini.php and url is relative,
					//then it's assumed that image is located on another server, thus add domain to url
					$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
				}
				else{
					$urlDomain = "http://";
					if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) 
						$urlDomain = "https://";
					$urlDomain .= $_SERVER["SERVER_NAME"];
					if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlDomain .= ':'.$_SERVER["SERVER_PORT"];
					$imgUrl = $urlDomain.$imgUrl;
				}
			}
			//Set temp folder path and file names
			$ts = time();
			$this->imgUrlLocal = $this->tempPath.$ts.'_img.jpg';

			//Copy image to temp folder
			$status = copy($imgUrl,$this->imgUrlLocal);
		}
		return $status;
	}

	public function batchOcrUnprocessed($inCollStr,$procStatus = 'unprocessed',$limit = 0,$getBest = 0){
		//OCR all images with a status of "unprocessed" and change to "unprocessed/OCR"
		//Triggered automaticly (crontab) on a nightly basis
		if($inCollStr) {
			set_time_limit(600);
			ini_set('memory_limit','512M');
			
			//Get collection list
			$sql = 'SELECT DISTINCT collid, CONCAT_WS("-",institutioncode,collectioncode) AS instcode '.
				'FROM omcollections '.
				'WHERE collid IN('.$inCollStr.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collArr[$r->collid] = $r->instcode;
			}
			$rs->free();
			
			//Batch OCR
			foreach($collArr as $collid => $instCode){
				$this->logMsg('Starting batch processing for '.$instCode);
				$sql = 'SELECT i.imgid, IFNULL(i.originalurl, i.url) AS url, o.sciName, i.occid '.
					'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
					'LEFT JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
					'WHERE (o.collid = '.$collid.') AND r.prlid IS NULL ';
				if($procStatus) $sql .= 'AND o.processingstatus = "unprocessed" ';
				if($limit) $sql .= 'LIMIT '.$limit;
				if($rs = $this->conn->query($sql)){
					$recCnt = 1;
					while($r = $rs->fetch_object()){
						$rawStr = $this->ocrImageByUrl($r->url,$getBest,$r->sciName);
						if($rawStr != 'ERROR'){
							$this->logMsg('#'.$recCnt.': image <a href="../editor/occurrenceeditor.php?occid='.$r->occid.'" target="_blank">'.$r->imgid.'</a> processed ('.date("Y-m-d H:i:s").')');
							$notes = '';
							$source = 'Tesseract: '.date('Y-m-d');
							$this->databaseRawStr($r->imgid,$rawStr,$notes,$source);
						}
						ob_flush();
						flush();
						$recCnt++;
					}
		 			$rs->free();
				}
			}
		}
	}

	// OCR upload functions 
	public function harvestOcrText($postArr){
		$status = true;
		set_time_limit(3600);
		$this->collid = $postArr['collid'];
		$this->ocrSource = $postArr['ocrsource'];
		$this->specKeyPattern = $postArr['speckeypattern'];
		if(!$this->specKeyPattern){
			$this->errorStr = 'ERROR loading OCR files: Specimen catalog number pattern missing';
			$this->logMsg($this->errorStr);
			return false;
		}
		$sourcePath = '';
		if(array_key_exists('sourcepath',$postArr) && $postArr['sourcepath']){
			$sourcePath = $postArr['sourcepath'];
		}
		else{
			$this->deleteAllOcrFiles = 1;
			$sourcePath = $this->uploadOcrFile();
		}
		if(!$sourcePath){
			$this->errorStr = 'ERROR loading OCR files: OCR source path is missing';
			$this->logMsg($this->errorStr);
			return false;
		}
		if(substr($sourcePath,0,4) == 'http'){
			//http protocol, thus test for a valid page
			$headerArr = get_headers($sourcePath);
			if(!$headerArr){
				$this->errorStr = 'ERROR loading OCR files: sourcePath returned bad headers ('.$sourcePath.')';
				$this->logMsg($this->errorStr);
				return false;
			} 
			preg_match('/http.+\s{1}(\d{3})\s{1}/i',$headerArr[0],$codeArr);
			if($codeArr[1] == '403'){ 
				$this->errorStr = 'ERROR loading OCR files: sourcePath returned Forbidden ('.$sourcePath.')';
				$this->logMsg($this->errorStr);
				return false;
			}
			if($codeArr[1] == '404'){ 
				$this->errorStr = 'ERROR loading OCR files: sourcePath returned a page Not Found error ('.$sourcePath.')';
				$this->logMsg($this->errorStr);
				return false;
			}
			if($codeArr[1] != '200'){ 
				$this->errorStr = 'ERROR loading OCR files: sourcePath returned error code '.$codeArr[1].' ('.$sourcePath.')';
				$this->logMsg($this->errorStr);
				return false;
			}
		}
		elseif(!file_exists($sourcePath)){
			$this->errorStr = 'ERROR loading OCR files: sourcePath does not exist ('.$sourcePath.')';
			$this->logMsg($this->errorStr);
			return false;
		}
		//Initiate processing
		if(substr($sourcePath,-1) != '/') $sourcePath .= '/';
		if(substr($sourcePath,0,4) == 'http'){
			$this->processOcrHtml($sourcePath);
		}
		else{
			$this->processOcrFolder($sourcePath);
		}
		$this->logMsg('Done loading OCR files ');
		
		
		return $status;
	}
	
	private function uploadOcrFile(){
		$retPath = '';
		if(!array_key_exists('ocrfile',$_FILES)){
			$this->errorStr = 'ERROR loading OCR file: OCR file missing';
			$this->logMsg($this->errorStr);
			return ;
		}
		if(!$this->tempPath){
			$this->errorStr = 'ERROR loading OCR file: temp target path empty';
			$this->logMsg($this->errorStr);
			return ;
		}
		$zipPath = $this->tempPath.'ocrupload.zip';
		if(file_exists($zipPath)) unlink($zipPath);
		if(move_uploaded_file($_FILES['ocrfile']['tmp_name'], $zipPath)){
			$zip = new ZipArchive;
			$res = $zip->open($zipPath);
			if($res === TRUE) {
				$extractPath = $this->tempPath.'ocrtext_'.time().'/';
				mkdir($extractPath);
				if($zip->extractTo($extractPath)){
					$retPath = $extractPath;
				}
				$zip->close();
				unlink($zipPath);
			}
			else{
				$this->errorStr = 'ERROR unpacking OCR file: '.$res;
				$this->logMsg($this->errorStr);
				return ;
			}
		}
		else{
			$this->errorStr = 'ERROR loading OCR file: input file lacks zip extension';
			$this->logMsg($this->errorStr);
			return ;
		}
		return $retPath;
	}
	
	private function processOcrHtml($sourcePath){
		$dom = new DOMDocument();
		$dom->loadHTMLFile($sourcePath);
		$aNodes= $dom->getElementsByTagName('a');
		$skipAnchors = array('Name','Last modified','Size','Description');
		foreach( $aNodes as $aNode ) {
			$fileName = $aNode->nodeValue;
			if(!in_array($fileName,$skipAnchors)){
				$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')+1));
				if($fileExt){
					$this->logMsg("Processing OCR File: ".$fileName);
					if($fileExt == "txt"){
						$this->processOcrFile($fileName,$sourcePath);
					}
					else{
						$this->logMsg("ERROR: File skipped, not a supported OCR file with .txt extension: ".$sourcePath.$fileName);
					}
				}
				elseif(stripos($fileName,'Parent Dir') === false){
					$this->logMsg('New dir path: '.$sourcePath.$fileName);
					$this->processOcrHtml($sourcePath.$fileName.'/');
				}
			}
		}
	}

	private function processOcrFolder($sourcePath){
		//$this->logOrEcho("Processing: ".$sourcePath.$pathFrag);
		//Read directory and loop through OCR files
		if($dirFH = opendir($sourcePath)){
			while($fileName = readdir($dirFH)){
				if($fileName != "." && $fileName != ".." && $fileName != ".svn"){
					if(is_file($sourcePath.$fileName)){
						$this->logMsg("Processing OCR File: ".$fileName);
						$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')));
						if($fileExt == ".txt"){
							$this->processOcrFile($fileName,$sourcePath);
						}
						else{
							$this->logMsg("ERROR: File skipped, not a supported OCR text file (.txt): ".$fileName);
						}
					}
					elseif(is_dir($sourcePath.$fileName)){
						$this->processOcrFolder($sourcePath.$fileName."/");
					}
				}
			}
			if($dirFH) closedir($dirFH);
		}
		else{
			$this->logMsg("ERROR: unable to access source directory: ".$sourcePath,1);
		}
		if($this->deleteAllOcrFiles) unlink($sourcePath); 
	}

	private function processOcrFile($fileName,$sourcePath){
		$ocrCnt = 0;
		//$this->logMsg('Starting OCR text processing... ',1);
		if($rawTextFH = fopen($sourcePath.$fileName, 'r')){
			$rawStr = fread($rawTextFH, filesize($sourcePath.$fileName));
			fclose($rawTextFH);
			if($this->deleteAllOcrFiles) unlink($sourcePath.$fileName); 
			//Grab specimen primary key (e.g. catalog number 
			$catNumber = ''; 
			if(preg_match($this->specKeyPattern,$fileName,$matchArr)){
				if(array_key_exists(1,$matchArr) && $matchArr[1]){
					$catNumber = $matchArr[1];
				}
			}
			if($catNumber){
				//Grab image primary key (imgid)
				$imgArr = array();
				$sql = 'SELECT i.imgid, IFNULL(i.originalurl,i.url) AS url '.
					'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
					'WHERE (o.collid = '.$this->collid.') AND (o.catalognumber = "'.$this->cleanInStr($catNumber).'")';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$imgArr[$r->imgid] = $r->url;
				}
				$rs->free();
				if(!$imgArr){
					$fileBaseName = basename($sourcePath.$fileName, ".txt");
					if(strlen($fileBaseName)>4){
						$sql = 'SELECT i.imgid, IFNULL(i.originalurl,i.url) AS url '.
							'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
							'WHERE (o.collid = '.$this->collid.') AND ((i.originalurl LIKE "%/'.$this->cleanInStr($fileBaseName).'.jpg") OR (i.url LIKE "%/'.$this->cleanInStr($fileBaseName).'.jpg"))';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$imgArr[$r->imgid] = $r->url;
						}
						$rs->free();
					}
				}
				if($imgArr){
					$imgId = key($imgArr);
					if(count($imgArr) > 1){
						// By default will link to first image, unless there is another image with exact name as OCR file
						$fileBaseName = basename($sourcePath.$fileName, ".txt");
						$imgIdOverride = '';
						foreach($imgArr as $k => $v){
							if(stripos($v,'/'.$fileBaseName.'.') || stripos($v,'/'.$fileBaseName.'_lg.')){
								$imgIdOverride= $k;
								break;
							}
							elseif(stripos($v,'/'.$fileBaseName.'_')){
								$imgIdOverride= $k;
							}
						}
						if($imgIdOverride) $imgId = $imgIdOverride;
					}
					//Process and database OCR string
					if($this->databaseRawStr($imgId,$rawStr,'',$this->ocrSource.': '.date('Y-m-d'))){
						if(file_exists($sourcePath.$fileName)) unlink($sourcePath.$fileName);
						$ocrCnt++;
					}
				}
				else{
					$this->logMsg('ERROR: unable locate specimen image (catalog #: '.$catNumber.')',1);
				}
			}
			else{
				$this->logMsg('ERROR: unable to extract catalog number ('.$fileName.' using '.$this->specKeyPattern.')',1);
			}
		}
		else{
			$this->logMsg('ERROR: unable to read rawOcr file: '.$fileName,1);
		}
	}

	//Image manipulations and adjustments
	private function cropImage(){
		$status = false;
		if($this->cropX || $this->cropY || $this->cropW < 1 || $this->cropH < 1){
			// Create image instances
			try{
				if($img = imagecreatefromjpeg($this->imgUrlLocal)){
					$imgW = imagesx($img);
					$imgH = imagesy($img);
					if(($this->cropX + $this->cropW) > 1) $this->cropW = 1 - $this->cropX;
					if(($this->cropY + $this->cropH) > 1) $this->cropH = 1 - $this->cropY;
					$pX = $imgW*$this->cropX;
					$pY = $imgH*$this->cropY;
					$pW = $imgW*$this->cropW;
					$pH = $imgH*$this->cropH;
					$dest = imagecreatetruecolor($pW,$pH);
	
					// Copy
					if(imagecopy($dest,$img,0,0,$pX,$pY,$pW,$pH)){
						//$status = imagejpeg($dest,str_replace('_img.jpg','_crop.jpg',$this->imgUrlLocal));
						$status = imagejpeg($dest,$this->imgUrlLocal);
					}
					imagedestroy($dest);
					imagedestroy($img);
				}
			}
			catch(Exception $e){
				//echo 'Caught exception: '.$e->getMessage();
			}
		}
		return $status;
	}

	private function imageTrimBorder($c=0,$t=100){
		$img = imagecreatefromjpeg($this->imgUrlLocal);
		if (!is_numeric($c) || $c < 0 || $c > 255) {
			// Color ($c) not valid, thus grab the color from the top left corner and use that as default
			$rgb = imagecolorat($im, 2, 2); // 2 pixels in to avoid messy edges
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			$c = round(($r+$g+$b)/3); // average of rgb is good enough for a default
		}
		// if tolerance ($t) isn't a number between 0 - 255, set default
		if (!is_numeric($t) || $t < 0 || $t > 255) $t = 30;

		$width = imagesx($img);
		$height = imagesy($img);
		$bTop = 0;
		$bLeft = 0;
		$bBottom = $height - 1;
		$bRight = $width - 1;

		//top
		for(; $bTop < $height; $bTop=$bTop+2) {
			for($x = 0; $x < $width; $x=$x+2) {
				$rgb = imagecolorat($img, $x, $bTop);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		// return false when all pixels are trimmed
		if ($bTop == $height) return false;

		// bottom
		for(; $bBottom >= 0; $bBottom=$bBottom-2) {
			for($x = 0; $x < $width; $x=$x+2) {
				$rgb = imagecolorat($img, $x, $bBottom);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		// left
		for(; $bLeft < $width; $bLeft=$bLeft+2) {
			for($y = $bTop; $y <= $bBottom; $y=$y+2) {
				$rgb = imagecolorat($img, $bLeft, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		// right
		for(; $bRight >= 0; $bRight=$bRight-2) {
			for($y = $bTop; $y <= $bBottom; $y=$y+2) {
				$rgb = imagecolorat($img, $bRight, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if(($r < $c-$t || $r > $c+$t) && ($g < $c-$t || $g > $c+$t) && ($b < $c-$t || $b > $c+$t)){
					break 2;
				}
			}
		}

		$bBottom++;
		$bRight++;

		$w = $bRight - $bLeft;
		$h = $bBottom - $bTop;
		if($w < $width || $h < $height){
			$dest = imagecreatetruecolor($w,$h);
			if(imagecopy($dest, $img, 0, 0, $bLeft, $bTop, $w, $h)){
				$status = imagejpeg($dest,$this->imgUrlLocal);
			}
			imagedestroy($dest);
			imagedestroy($img);
			return true;
		}
		return false;
	}

	//Roberts scoring and treatment functions
	private function getBestOCR($sciName = ''){
		//Base run
		$rawStr_base = $this->ocrImage();
		$score_base = $this->scoreOCR($rawStr_base, $sciName);
		$urlTemp = str_replace('.jpg','_f1.jpg',$this->imgUrlLocal);
		copy($this->imgUrlLocal,$urlTemp);
		$this->filterImage($urlTemp);
		$rawStr_treated = $this->ocrImage($urlTemp);
		$score_treated = $this->scoreOCR($rawStr_treated, $sciName);
		unlink($urlTemp);
		if($score_treated > $score_base) {
			$this->logMsg('Best Score applied',1);
			return $rawStr_treated;
		} else {
			return $rawStr_base;
		}
	}

	private function filterImage($url=''){
		$status = false;
		if(!$url) $url = $this->imgUrlLocal;
		if($img = imagecreatefromjpeg($url)){
			imagefilter($img,IMG_FILTER_GRAYSCALE);
			imagefilter($img,IMG_FILTER_BRIGHTNESS,10);
			imagefilter($img,IMG_FILTER_CONTRAST,1);
			$sharpenMatrix = array
			(
				array(-1.2, -1, -1.2),
				array(-1, 20, -1),
				array(-1.2, -1, -1.2)
			);
			// calculate the sharpen divisor
			$divisor = array_sum(array_map("array_sum", $sharpenMatrix));
			$offset = 0;
			// apply the matrix
			imageconvolution($img, $sharpenMatrix, $divisor, $offset);
			imagegammacorrect($img, 6, 1.0);
			$status = imagejpeg($img,$url);
			imagedestroy($img);
		}
		return $status;
	}

	private function scoreOCR($rawStr, $sciName = '') {
		$sLength = strlen($rawStr);
		if($sLength > 12) {
			$numWords = 0;
			$numBadLinesIncremented = false;
			$numBadLines = 1;
			$lines = explode("\n", $rawStr);
			foreach($lines as $line) {
				$line = trim($line);
				if(strlen($line) > 2) {
					$words = explode(" ", $line);
					foreach($words as $word) {
						if(strlen($word) > 2)
						{
							$goodChars = 0;
							$badChars = 0;
							foreach (count_chars($word, 1) as $i => $let) {
								if(($i > 47 && $i < 60) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 176) {
									$goodChars++;
								}
								else if(($i < 44 || $i > 59) && !($i == 32 || $i == 35 || $i == 34 || $i == 39 || $i == 38 || $i == 40 || $i == 41 || $i == 61)) {
									$badChars++;
								}
							}
							if($goodChars > 3*$badChars) $numWords++;
						}
					}
				} else {
					if($numBadLines == 1) {
						if($numBadLinesIncremented) $numBadLines++;
						else $numBadLinesIncremented = true;
					} else $numBadLines++;
				}
			}
			$numGoodChars = 0;
			$numBadChars = 1;
			$numBadIncremented = false;
			foreach (count_chars($rawStr, 1) as $i => $val) {
				if(($i > 47 && $i < 60) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 176) {
					$numGoodChars += $val;
				}
				else if(($i < 44 || $i > 59) && !($i == 32 || $i == 35 || $i == 34 || $i == 39 || $i == 38 || $i == 40 || $i == 41 || $i == 61)) {
					if($numBadChars == 1) {
						if($numBadIncremented) $numBadChars += $val;
						else {
							$numBadIncremented = true;
							$numBadChars += ($val-1);
						}
					} else $numBadChars += $val;
				}
			}
			return (($numWords*$numGoodChars)/($sLength*$numBadChars*$numBadLines)) + $this->findSciName($rawStr,$sciName);
		} else return 0;
	}

	private function findSciName($rawStr,$sciName) {
		$result = 0;
		if(strlen($sciName) > 0) {
			$words = explode(" ", $sciName);
			foreach($words as $word) {
				$wrdLen = strlen($word);
				if($wrdLen > 4) {
					if(stripos($rawStr,$word) !== false) $result += 0.3;
					else if(stripos($rawStr,str_replace("g", "p", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("q", "p", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("1", "l", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("1", "i", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("b", "h", $word)) !== false) $result += 0.2;
					else if(stripos($rawStr,str_replace("v", "y", $word)) !== false) $result += 0.2;
					else {
						$shrtWrd = substr($word, 1);
						if(stripos($rawStr,$shrtWrd) !== false) $result += 0.1;
						else if(stripos($rawStr,str_replace("I", "l", $shrtWrd)) !== false) $result += 0.1;
						else if(stripos($rawStr,str_replace("H", "ll", $shrtWrd)) !== false) $result += 0.1;
						else {
							$shrtWrd = substr($word, 0, $wrdLen-1);
							if(stripos($rawStr,$shrtWrd) !== false) $result += 0.1;
						}
					}
				}
			}
		}
		$goodWords =
			array (
					"collect", "fungi", "location", "locality", "along", "rock", "outcrop", "thallus", "pseudotsuga",
					"habitat", "det.", "determine",	"date", "long.", "latitude", "lat.", "shale", "laevis",
					"longitude", "elevation", "elev.", "quercus", "acer", "highway", "preserve", "hardwood",
					"road", "sandstone", " granit", "slope", "county", "near", "north", "forest", "Bungartz",
					"south", "east", "west", "stream", "Wetmore", "Nash", "Imsaug", "mile", "wood", "Esslinger",
					"Thomson", "Lendemer", "Johnson", "Harris", "Rosentretter", "Hodges", "Malachowski",
					"Tucker", "Egan", "Fink", "Shushan", "Sullivan", "Crane", "Schoknecht", "Marsh", "Lumbsch",
					"Trana", "Phillipe", "Landron", "Eyerdam", "Sharnoff", "Schuster", "Perlmutter", "Fryday",
					"Ohlsson", "Howard", "Taylor", "Arnot", "Gowan", "Dey", "Scotter", "Llano", "Keith", "Moberg",
					"Brako", "Ricklefs", "Darrow", "Macoun", "Barclay", "Culberson", "Alvarez", "ground", "ridge",
					"Wong", "Gould", "Shchepanek", "Wheeler", "Hasse", "Kashiwadani", "Havaas", "Weise", "Sheard",
					"Malme", "Hansen", "Erbisch", "Degelius", "Hafellner", "Reed", "Sweat", "Streimann", "McCune",
					"Ryan", "Brodo", "Bratt", "Burnett", "Knudsen", "Weber", "Vezda", "Langlois", "Follmann",
					"Buck", "Arnold", "Thaxter", "Armstrong", "Ahti", "Wheeler", "Britton", "Marble", "national",
					"January", "February", "March", "April", "May", "June", "July", "August", "September", "October",
					"November", "December", "Jan", "Feb", "Mar", "Apr", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
					"Calkins", "McHenry", "Schofield", "SHIMEK", "Hepp", "Talbot", "Riefner", "WAGHORNE", "Becking",
					"Nebecker", "Lebo", "Advaita", "DeBolt", "Austin", "Brouard", "Amtoft", "KIENER", "Kalb", "Hertel",
					"Clair", "Nee", "Boykin", "Sundberg", "Elix", "Santesson", "plant", "glade", "parish", "swamp",
					"Ilex", "Diospyros", "(Ach.)", "Leight", "river", "trail", "mount", "wall", "index", "pine",
					"vicinity", "durango", "madre", "stalk", "moss", "down", "some", "base", "alga", "brown", "punta",
					"dirt", "stand", "meter", "dead", "steep", "isla", "town", "station", "picea", "shore", "over",
					"attached", "apothecia", "spruce", "upper", "rosa", "rocky", "litter", "about", "shade", "coast",
					"tree", "live", "fork", "cliff", "amabilis", "facing", "junction", "white", "partial", "bare",
					"scrub", "then", "boulder", "conifer", "branch", "adjacent", "peak", "sonoran", "maple", "sample",
					"expose", "parashant", "pinyon", "growing", "fragment", "shrub", "below", "limestone", "scatter",
					"snag", "douglas", "secondary", "state", "point", "pass", "basalt", "edge", "year", "hemlock",
					"vigor", "association", "cedar", "community", "head", "cowlitz", "tsuga", "juniper", "monument",
					"between", "baker-snoqualmie", "menziesii", "heterophylla", "just", "wenatchee", "ranger", "grand",
					"mixed", "rhyolite", "plot", "growth", "desert", "spore", "sierra", "abies", "small", "gifford",
					"pinchot", "district", "pinus", "valley", "aspect", "santa", "open", "service", "degree", "above",
					"island", "side", "bark", "lake", "creek", "canyon", "from", "substrate", "slope", "with", "area"
			);
		foreach($goodWords as $goodWord) {
			if(stripos($rawStr,$goodWord) !== false) $result += 0.2;
		}
		//return $index*$result;
		return $result;
	}

	//General setters and getters
	public function setCropX($x){
		$this->cropX = $x;
	}
	public function setCropY($y){
		$this->cropY = $y;
	}
	public function setCropW($w){
		$this->cropW = $w;
	}
	public function setCropH($h){
		$this->cropH = $h;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	public function setVerbose($s){
		$this->verbose = $s;
		if($this->verbose == 1 || $this->verbose == 3){
			if($this->tempPath){
				$logPath = $this->tempPath.'log_'.date('Ymd').'.log';
				$this->logFH = fopen($logPath, 'a');
			}
		}
	}

	private function setTempPath(){
		$tempPath = 0;
		if(array_key_exists('tempDirRoot',$GLOBALS)){
			$tempPath = $GLOBALS['tempDirRoot'];
		}
		else{
			$tempPath = ini_get('upload_tmp_dir');
		}
		if(!$tempPath){
			$tempPath = $GLOBALS['serverRoot'];
			if(substr($tempPath,-1) != '/') $tempPath .= '/';
			$tempPath .= 'temp/';
		}
		if(substr($tempPath,-1) != '/') $tempPath .= '/';
		if(file_exists($tempPath.'symbocr/') || mkdir($tempPath.'symbocr/')){
			$tempPath .= 'symbocr/';
		}

		$this->tempPath = $tempPath;
	}

	/*public function addFilterVariable($k,$v){
		$this->filterArr[0][$k] = $v;
	}*/

	//Misc functions
	private function logMsg($msg,$indent = 0) {
		if($this->verbose == 1 || $this->verbose == 3){
			if($this->logFH){
				$msg .= "\n";
				if($indent) $msg = "\t".$msg;
				fwrite($this->logFH, $msg);
			}
		}
		elseif($this->verbose > 1 ){
			echo '<li style="margin-left:'.($indent*15).'px">'.$msg.'</li>';
		}
	}

	private function cleanRawStr($inStr){
		$retStr = $this->encodeString($inStr);

		//replace commonly misinterpreted characters
		$replacements = array("/\." => "A.", "/-\\" => "A", "\X/" => "W", "\Y/" => "W", "`\‘i/" => "W", chr(96) => "'", chr(145) => "'", chr(146) => "'", 
			"�" => "'", "�" => '"', "�" => '"', "�" => '"', chr(147) => '"', chr(148) => '"', chr(152) => '"', chr(239) => "�");
		$retStr = str_replace(array_keys($replacements), $replacements, $retStr);

		//replace Is, ls and |s in latitudes and longitudes with ones
		//replace Os in latitudes and longitudes with zeroes, Ss with 5s and Zs with 2s
		//latitudes and longitudes can be of the types: ddd.ddddddd�, ddd� ddd.ddd' or ddd� ddd' ddd.ddd"
		$false_num_class = "[OSZl|I!\d]";//the regex class that represents numbers and characters that numbers are commonly replaced with
		$preg_replace_callback_pattern =
			array(
				"/".$false_num_class."{1,3}(\.".$false_num_class."{1,7})\s?".chr(176)."\s?[NSEW(\\\V)(\\\W)]/",
				"/".$false_num_class."{1,3}".chr(176)."\s?".$false_num_class."{1,3}(\.".$false_num_class."{1,3})?\s?'\s?[NSEW(\\\V)(\\\W)]/",
				"/".$false_num_class."{1,3}".chr(176)."\s?".$false_num_class."{1,3}\s?'\s?(".$false_num_class."{1,3}(\.".$false_num_class."{1,3})?\"\s?)?[NSEW(\\\V)(\\\W)]/"
			);
		$retStr = preg_replace_callback($preg_replace_callback_pattern, create_function('$matches','return str_replace(array("l","|","!","I","O","S","Z"), array("1","1","1","1","0","5","2"), $matches[0]);'), $retStr);
		//replace \V and \W in longitudes and latitudes with W
		$retStr = preg_replace("/(\d\s?[".chr(176)."'\"])\s?\\\[VW]/", "\${1}W", $retStr, -1);
		//replace Zs and zs with 2s, Is, !s, |s and ls with 1s and Os and os with 0s in dates of type Mon(th) DD, YYYY
		$retStr = preg_replace_callback(
			"/(((?i)January|Jan\.?|February|Feb\.?|March|Mar\.?|April|Apr\.?|May|June|Jun\.?|July|Jul\.?|August|Aug\.?|September|Sept?\.?|October|Oct\.?|November|Nov\.?|December|Dec\.?)\s)(([\dOIl|!ozZS]{1,2}),?\s)([\dOI|!lozZS]{4})/",
			create_function('$matches','return $matches[1].str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[3]).str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[5]);'),
			$retStr
		);
		//replace Zs with 2s, Is with 1s and Os with 0s in dates of type DD-Mon(th)-YYYY or DDMon(th)YYYY or DD Mon(th) YYYY
		$retStr = preg_replace_callback(
			"/([\dOIl!|ozZS]{1,2}[-\s]?)(((?i)January|Jan\.?|February|Feb\.?|March|Mar\.?|April|Apr\.?|May|June|Jun\.?|July|Jul\.?|August|Aug\.?|September|Sept?\.?|October|Oct\.?|November|Nov\.?|December|Dec\.?)[-\s]?)([\dOIl|!ozZS]{4})/i",
			create_function('$matches','return str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[1]).$matches[2].str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[4]);'),
			$retStr
		);
		return $retStr;
	}

	private function encodeString($inStr){
		global $charset;
		$retStr = $inStr;
		//Get rid of Windows curly (smart) quotes
		$search = array(chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151));
		$replace = array("'","'",'"','"','*','-','-');
		$inStr = str_replace($search, $replace, $inStr);
		//Get rid of UTF-8 curly smart quotes and dashes 
		$badwordchars=array("\xe2\x80\x98", // left single quote
							"\xe2\x80\x99", // right single quote
							"\xe2\x80\x9c", // left double quote
							"\xe2\x80\x9d", // right double quote
							"\xe2\x80\x94", // em dash
							"\xe2\x80\xa6" // elipses
		);
		$fixedwordchars=array("'", "'", '"', '"', '-', '...');
		$inStr = str_replace($badwordchars, $fixedwordchars, $inStr);
		
		if($inStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($charset) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
			//$line = iconv('macintosh', 'UTF-8', $line);
			//mb_detect_encoding($buffer, 'windows-1251, macroman, UTF-8');
 		}
		return $retStr;
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>