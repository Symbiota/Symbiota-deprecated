<?php
/*
 * Used by automatic nightly process and by the occurrence editor (/collections/editor/occurrenceeditor.php)
 */
include_once($serverRoot.'/config/dbconnection.php');

class SpecProcessorOcr{

	private $conn;
	private $tempPath;
	private $imgUrlLocal;

	private $cropX = 0;
	private $cropY = 0;
	private $cropW = 1;
	private $cropH = 1;

	private $filterArr = array();
	private $filterIndex = 0;

	private $logPath;
	//If silent is set, script will produce no non-fatal output.
	private $silent = 1;

	function __construct() {
		$this->setTempPath();
		$this->setlogPath();
		//Set treatments
		$this->filterArr[1] = array('grayscale'=>1,'brightness'=>10,'contrast'=>1,'sharpen'=>1,'gammacorrect'=>6);
		$this->filterArr[2] = array('grayscale'=>1,'brightness'=>5,'contrast'=>-3,'sharpen'=>2,'gammacorrect'=>1.537);
		$this->filterArr[3] = array('grayscale'=>1,'gammacorrect'=>1.537);
		$this->filterArr[4] = array('grayscale'=>1,'brightness'=>30,'contrast'=>-10,'sharpen'=>3,'gammacorrect'=>1.537,'smooth'=>6);
	}

	function __destruct(){
		unlink($this->imgUrlLocal);
	}

	public function batchOcrUnprocessed($collArr = 0,$getBest = 0){
		//OCR all images with a status of "unprocessed" and change to "unprocessed/OCR"
		//Triggered automaticly (crontab) on a nightly basis
		$this->conn = MySQLiConnectionFactory::getCon("write");
		if(!$this->silent) $this->logMsg("Starting batch processing\n");
		foreach($collArr as $cid){
			if($cid){
				if(!$this->silent) $this->logMsg("\tProcessing collid".$cid."\n");
				$sql = 'SELECT i.imgid, IFNULL(i.originalurl, i.url) AS url, o.sciName '.
					'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
					'LEFT JOIN specprocessorrawlabels rl ON i.imgid = rl.imgid '.
					'WHERE o.processingstatus = "unprocessed" AND rl.prlid IS NULL '.
					'AND (o.collid = '.$cid.') ';
				//Limit for debugging purposes only
				$sql .= 'LIMIT 30 ';
				if($rs = $this->conn->query($sql)){
					$recCnt = 0;
					$bestScoreCounts = array();
					while($r = $rs->fetch_object()){
						$rawStr = '';
						if($getBest && $recCnt < 20){
							$rawStr = $this->getBestOCR($r->url, $r->sciName);
							if(!$this->silent) $this->logMsg("\tImage ".$recCnt." processed (imgid: ".$r->imgid."). Best index: ".$this->filterIndex." (".date("Y-m-d H:i:s").")\n");
							if(array_key_exists($this->filterIndex,$bestScoreCounts)){
								$newCnt = ++$bestScoreCounts[$this->filterIndex];
								$bestScoreCounts[$this->filterIndex] = $newCnt;
							}
							else{
								$bestScoreCounts[$this->filterIndex] = 1;
							}
							if($recCnt == 19){
								asort($bestScoreCounts);
								$this->filterIndex = array_pop(array_keys($bestScoreCounts));
								if(!$this->silent) $this->logMsg("\tFinal best index: ".$this->filterIndex."\n");
							}
						}
						else{
							$rawStr = $this->ocrImageByUrl($r->url);
							if(!$this->silent) $this->logMsg("\tImage ".$r->imgid." processed (".date("Y-m-d H:i:s").")\n");
						}
						$rawStr = $this->cleanRawStr($rawStr);
						if(!$rawStr) $rawStr = 'Failed OCR return';
						$this->databaseRawStr($r->imgid,$rawStr);
						$recCnt++;
					}
		 			$rs->close();
				}
			}
		}
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function ocrImageByImgId($imgId){
		$rawStr = '';
		if(is_numeric($imgId)){
			$this->conn = MySQLiConnectionFactory::getCon("write");

			$imgUrl = '';
			$sql = 'SELECT originalurl, url '.
				'FROM images '.
				'WHERE (imgid = '.$imgId.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$imgUrl = ($r->originalurl?$r->originalurl:$r->url);
			}
			$rs->close();
			$rawStr = $this->ocrImageByUrl($imgUrl);

	 		if(!($this->conn === false)) $this->conn->close();
		}
		//echo 'rawStr: '.$rawStr."\n";
 		return $rawStr;
	}

	public function ocrImageByUrl($imgUrl,$getBest = 0){
		$rawStr = '';
		if($imgUrl){
			if($this->loadImage($imgUrl)){
				if($this->filterIndex){
					$this->filterImage();
				}
				$this->cropImage();
				if($getBest){
					$rawStr = $this->getBestOCR();
				}
				else{
					$rawStr = $this->ocrImage();
				}
			}
			else{
				//Unable to create image
				$this->logMsg('\tERROR: Unable to load image, URL: '.$imgUrl."\n");
			}
		}
		else{
			$this->logMsg('\tERROR: Empty URL'."\n");
		}
		return $rawStr;
	}

	private function getBestOCR($url = '', $sciName = ''){
		if($url) $this->loadImage($url);
		
		$scoreArr = array();
		$rawStrArr = array();
		//Base run
		$rawStrArr[0] = $this->ocrImage();
		$scoreArr[0] = $this->scoreOCR($rawStrArr[0], $sciName);

		foreach($this->filterArr as $tIndex => $tArr){
			$urlTemp = str_replace('.jpg','_f'.$tIndex.'.jpg',$this->imgUrlLocal);
			copy($this->imgUrlLocal,$urlTemp);
			$this->filterImage($urlTemp,$tArr);
			$rawStrArr[$tIndex] = $this->ocrImage($urlTemp);
			$scoreArr[$tIndex] = $this->scoreOCR($rawStrArr[$tIndex], $sciName);
			unlink($urlTemp);
		}
		
		asort($scoreArr);
		$bestIndex = array_pop(array_keys($scoreArr));
		$bestValue = end($scoreArr);
		//if base score ties for high score, make sure no treatment is the tagged as best 
		if($bestValue==$scoreArr[0]) $bestIndex = 0;
		$this->filterIndex = $bestIndex;
		return $rawStrArr[$bestIndex];
	}

	private function filterImage($url='',$tArr=''){
		$status = false;
		if(!$url) $url = $this->imgUrlLocal;
		if(!$tArr && $this->filterArr){
			if($this->filterIndex){
				$tArr = $this->filterArr[$this->filterIndex];
			}
			else{
				$tArr = array_shift($this->filterArr);
			}
		}
		if($tArr){
			if($img = imagecreatefromjpeg($url)){
	   			if(array_key_exists('grayscale',$tArr) && $tArr['grayscale']) imagefilter($img,IMG_FILTER_GRAYSCALE);
	   			if(array_key_exists('smooth',$tArr)) {
					if(array_key_exists('sharpen',$tArr)) {
						if($tArr['sharpen'] == 1) {// A sharpening matrix
							$sharpenMatrix = array
							(
								array(-1.2, -1, -1.2),
								array(-1, 20, -1),
								array(-1.2, -1, -1.2)
							);
						} else if($tArr['sharpen'] == 2) {// A blurring matrix
							$sharpenMatrix = array
							(
								array(1.0, 2.0, 1.0),
								array(2.0, 4.0, 2.0),
								array(1.0, 2.0, 1.0)
							);
						} else if($tArr['sharpen'] == 3) {// A blurring matrix
							$sharpenMatrix = array
							(
								array(1.5, 1.5, 1.5),
								array(1.5, 3.0, 1.5),
								array(1.5, 1.5, 1.5)
							);
						}
						// calculate the sharpen divisor
						$divisor = array_sum(array_map('array_sum', $sharpenMatrix));
						$offset = 0;
						// apply the matrix
						imageconvolution($img, $sharpenMatrix, $divisor, $offset);
					}
	   				if(array_key_exists('gammacorrect',$tArr)) imagegammacorrect($img, $tArr['gammacorrect'], 1.0);
	   				if(array_key_exists('brightness',$tArr)) imagefilter($img,IMG_FILTER_BRIGHTNESS,$tArr['brightness']);
	   				if(array_key_exists('contrast',$tArr)) imagefilter($img,IMG_FILTER_CONTRAST,$tArr['contrast']);
	   				imagefilter($img,IMG_FILTER_SMOOTH,$tArr['smooth']);
	   			} else {
	   				if(array_key_exists('brightness',$tArr)) imagefilter($img,IMG_FILTER_BRIGHTNESS,$tArr['brightness']);
	   				if(array_key_exists('contrast',$tArr)) imagefilter($img,IMG_FILTER_CONTRAST,$tArr['contrast']);
					if(array_key_exists('sharpen',$tArr)) {
						if($tArr['sharpen'] == 1) {// A sharpening matrix
							$sharpenMatrix = array
							(
								array(-1.2, -1, -1.2),
								array(-1, 20, -1),
								array(-1.2, -1, -1.2)
							);
						} else if($tArr['sharpen'] == 2) {// A blurring matrix
							$sharpenMatrix = array
							(
								array(1.0, 2.0, 1.0),
								array(2.0, 4.0, 2.0),
								array(1.0, 2.0, 1.0)
							);
						} else if($tArr['sharpen'] == 3) {// A blurring matrix
							$sharpenMatrix = array
							(
								array(1.5, 1.5, 1.5),
								array(1.5, 3.0, 1.5),
								array(1.5, 1.5, 1.5)
							);
						}
						// calculate the sharpen divisor
						$divisor = array_sum(array_map('array_sum', $sharpenMatrix));
						$offset = 0;
						// apply the matrix
						imageconvolution($img, $sharpenMatrix, $divisor, $offset);
					}
					if($tArr['gammacorrect']) imagegammacorrect($img, $tArr['gammacorrect'], 1.0);
				}
	
				$status = imagejpeg($img,$url);
				imagedestroy($img);
			}
		}
		return $status;
	}

	private function cropImage(){
		$status = false;
		if($this->cropX || $this->cropY || $this->cropW < 1 || $this->cropH < 1){
			// Create image instances
			if($src = imagecreatefromjpeg($this->imgUrlLocal)){
				$imgW = imagesx($src);
				$imgH = imagesy($src);
				if(($this->cropX + $this->cropW) > 1) $this->cropW = 1 - $this->cropX;
				if(($this->cropY + $this->cropH) > 1) $this->cropH = 1 - $this->cropY;
				$pX = $imgW*$this->cropX;
				$pY = $imgH*$this->cropY;
				$pW = $imgW*$this->cropW;
				$pH = $imgH*$this->cropH;
				$dest = imagecreatetruecolor($pW,$pH);

				// Copy
				if(imagecopy($dest,$src,0,0,$pX,$pY,$pW,$pH)){
					//$status = imagejpeg($dest,str_replace('_img.jpg','_crop.jpg',$this->imgUrlLocal));
					$status = imagejpeg($dest,$this->imgUrlLocal);
				}
				imagedestroy($src);
				imagedestroy($dest);
			}
		}
		return $status;
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
				$this->logMsg("\tERROR: Unable to locate output file\n");
			}
		}
		return $retStr;//$this->cleanRawStr($retStr);
	}

	private function databaseRawStr($imgId,$rawStr){
		$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes) '.
			'VALUE ('.$imgId.',"'.$this->conn->real_escape_string($rawStr).'","batch OCR - '.date('Y-m-d').'")';
		//echo 'SQL: '.$sql."\n";
		if($this->conn->query($sql)){
			return true;
		}
		else{
			$this->logMsg("\tERROR: Unable to load fragment into database: ".$this->conn->error."\n");
			$this->logMsg("\t\tSQL: ".$sql."\n");
		}
	}

	private function loadImage($imgUrl){
		if($imgUrl){
			//If there is an image domain name is set in symbini.php and url is relative,
			//then it's assumed that image is located on another server, thus add domain to url
			if(array_key_exists("imageDomain",$GLOBALS)){
				if(substr($imgUrl,0,1)=="/"){
					$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
				}
			}
			//Set temp folder path and file names
			$ts = time();
			$this->imgUrlLocal = $this->tempPath.$ts.'_img.jpg';

			//Copy image to temp folder
			return copy($imgUrl,$this->imgUrlLocal);
		}
		return false;
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

	private function setlogPath(){
		$this->logPath = $this->tempPath.'log_'.date('Ymd').'.log';
	}

	private function logMsg($msg) {
		if($fh = fopen($this->logPath, 'a')) {
			fwrite($fh, $msg);
			fclose($fh);
		}
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

	private function cleanRawStr($inStr){
		$outStr = trim($inStr);

		//replace commonly misinterpreted characters
		$needles = array(chr(226).chr(128).chr(156), "Ã©", "/\.", "/-\\", "\X/", "\Y/", "`\â€˜i/", chr(96), chr(145), chr(146), "â€˜", "’" , chr(226).chr(128).chr(152), chr(226).chr(128).chr(153), chr(226).chr(128), "“", "”", "”", chr(147), chr(148), chr(152), "Â°", "º", chr(239));
		$replacements = array("\"", "e", "A.", "A","W", "W", "W", "'", "'", "'", "'", "'", "'", "'", "\"", "\"", "\"", "\"", "\"", "\"", "\"", "°", "°", "°");
		$outStr = str_replace($needles, $replacements, $outStr);

		$false_num_class = "[OSZl|I!\d]";//the regex class that represents numbers and characters that numbers are commonly replaced with
		//remove barcodes (strings of ~s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long), one-character lines, and latitudes and longitudes with double quotes instead of degree signs
		$pattern =
			array(
				"/[\|!Il\"'1U~()@\[\]{}H\/\\\]{6,}/", //strings of ~s, 's, "s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long (from barcodes)
				"/^.{1,2}$/m", //one-character lines (Tesseract must generate a 2-char end of line)
				"/(lat|long)(\.|,|:|.:|itude)(:|,)?\s?(".$false_num_class."{1,3}(\.".$false_num_class."{1,7})?)\"/i" //the beginning of lat-long repair
			);
		$replacement = array("", "", "\${1}\${2}\$3 \${4}".chr(176));
		$outStr = preg_replace($pattern, $replacement, $outStr, -1);
		$outStr = str_replace("Â°", chr(176), $outStr);

		//replace Is, ls and |s in latitudes and longitudes with ones
		//replace Os in latitudes and longitudes with zeroes, Ss with 5s and Zs with 2s
		//latitudes and longitudes can be of the types: ddd.ddddddd°, ddd° ddd.ddd' or ddd° ddd' ddd.ddd"
		$preg_replace_callback_pattern =
			array(
				"/".$false_num_class."{1,3}(\.".$false_num_class."{1,7})\s?".chr(176)."\s?[NSEW(\\\V)(\\\W)]/",
				"/".$false_num_class."{1,3}".chr(176)."\s?".$false_num_class."{1,3}(\.".$false_num_class."{1,3})?\s?'\s?[NSEW(\\\V)(\\\W)]/",
				"/".$false_num_class."{1,3}".chr(176)."\s?".$false_num_class."{1,3}\s?'\s?(".$false_num_class."{1,3}(\.".$false_num_class."{1,3})?\"\s?)?[NSEW(\\\V)(\\\W)]/"
			);
		$outStr = preg_replace_callback($preg_replace_callback_pattern, create_function('$matches','return str_replace(array("l","|","!","I","O","S","Z"), array("1","1","1","1","0","5","2"), $matches[0]);'), $outStr);
		//replace \V and \W in longitudes and latitudes with W
		$outStr = preg_replace("/(\d\s?[".chr(176)."'\"])\s?\\\[VW]/", "\${1}W", $outStr, -1);
		//add degree signs to latitudes and longitudes that lack them
		$outStr = preg_replace("/(\d{1,3})\s{1,2}(\d{1,3}'\s?)(\d{1,3}\"\s?)?([NSEW])/", "\${1}".chr(176)." $2$3$4", $outStr, -1);
		//replace Zs and zs with 2s, Is, !s, |s and ls with 1s and Os and os with 0s in dates of type Mon(th) DD, YYYY
		$outStr = preg_replace_callback(
			"/(((?i)January|Jan\.?|February|Feb\.?|March|Mar\.?|April|Apr\.?|May|June|Jun\.?|July|Jul\.?|August|Aug\.?|September|Sept?\.?|October|Oct\.?|November|Nov\.?|December|Dec\.?)\s)(([\dOIl|!ozZS]{1,2}),?\s)([\dOI|!lozZS]{4})/",
			create_function('$matches','return $matches[1].str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[3]).str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[5]);'),
			$outStr
		);
		//replace Zs with 2s, Is with 1s and Os with 0s in dates of type DD-Mon(th)-YYYY or DDMon(th)YYYY or DD Mon(th) YYYY
		$outStr = preg_replace_callback(
			"/([\dOIl!|ozZS]{1,2}[-\s]?)(((?i)January|Jan\.?|February|Feb\.?|March|Mar\.?|April|Apr\.?|May|June|Jun\.?|July|Jul\.?|August|Aug\.?|September|Sept?\.?|October|Oct\.?|November|Nov\.?|December|Dec\.?)[-\s]?)([\dOIl|!ozZS]{4})/i",
			create_function('$matches','return str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[1]).$matches[2].str_replace(array("l","|","!","I","O","o","Z","z","S"), array("1","1","1","1","0","0","2","2","5"), $matches[4]);'),
			$outStr
		);
		return trim($outStr);
	}

	private function encodeString($inStr){
 		global $charset;
 		$retStr = $inStr;
		if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
			if(mb_detect_encoding($inStr) == "ISO-8859-1"){
				//$retStr = utf8_encode($inStr);
				//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				$retStr = mb_convert_encoding($inStr,"UTF-8");
			}
		}
		elseif(strtolower($charset) == "iso-8859-1"){
			if(mb_detect_encoding($inStr) == "UTF-8"){
				//$retStr = utf8_decode($inStr);
				//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				$retStr = mb_convert_encoding($inStr,"ISO-8859-1");
			}
		}
		return $retStr;
	}

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

	public function addFilterVariable($k,$v){
		$this->filterArr[0][$k] = $v;
	}
}
?>
