<?php
/*
 * Used by automatic nightly process and by the occurrence editor (/collections/editor/occurrenceeditor.php)
 */
include_once($serverRoot.'/config/dbconnection.php');

class SpecProcessorOcr{

	private $conn;
	private $tempPath;
	private $imgUrlLocal;
	private $outputFile; 

	private $grayscale = 0;
	private $brightness = 0;
	private $contrast = 0;
	
	private $logPath;
	//If silent is set, script will produce no non-fatal output.
	private $silent = 1;

	function __construct() {
		$this->setlogPath();
	}

	function __destruct(){
		//unlink($this->imgUrlLocal);
		//unlink($this->outputFile.'.txt');
	}

	public function batchOcrUnprocessed($collArr = 0){
		//OCR all images with a status of "unprocessed" and change to "unprocessed/OCR"
		//Triggered automaticly (crontab) on a nightly basis
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'SELECT i.imgid, IFNULL(i.originalurl, i.url) AS url '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'LEFT JOIN specprocessorrawlabels rl ON i.imgid = rl.imgid '.
			'WHERE o.processingstatus = "unprocessed" AND rl.prlid IS NULL ';
		if($collArr) $sql .= 'AND o.collid IN('.implode(',',$collArr).') ';
		//Limit for debugging purposes only
		$sql .= 'LIMIT 2 ';
		//echo 'SQL: '.$sql."\n";
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$rawStr = $this->ocrImageByUrl($r->url);
				//echo 'rawStr: '.$rawStr."\n";
				if($rawStr){
					$this->databaseRawStr($r->imgid,$rawStr);
				}
			}
	 		$rs->close();
		}
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function ocrImageByImgId($imgId,$grayscale = 0,$brightness = 0,$contrast = 0, $x=0, $y=0, $w=1, $h=1){
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
			$rawStr = $this->ocrImageByUrl($imgUrl, $grayscale, $brightness, $contrast, $x, $y, $w, $h);
			
	 		if(!($this->conn === false)) $this->conn->close();
		}
		//echo 'rawStr: '.$rawStr."\n";
 		return $rawStr;
	}
	
	public function ocrImageByUrl($imgUrl, $grayscale=0, $brightness=0, $contrast=0, $x=0, $y=0, $w=1, $h=1){
		$rawStr = '';
		if($imgUrl){
			if($this->loadImage($imgUrl)){
				if($grayscale || $brightness || $contrast){
					if(!$this->filterImage($grayscale,$brightness,$contrast)){
						//Unable to filter image
						$this->logError('Unable to filter, URL: '.$imgUrl);
					}
				}
				if($x || $y || $w < 1 || $h < 1){
					$this->cropImage($x, $y, $w, $h);
				}
				$rawStr = $this->ocrImage();
			}
			else{
	   			//Unable to create image
				$this->logError('Unable to load image, URL: '.$imgUrl);
			}
		}
		else{
			$this->logError('Empty URL');
		}
			
		return $rawStr;
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
			$this->setTempPath();
			$ts = time();
			$this->imgUrlLocal = $this->tempPath.$ts.'_img.jpg';
			$this->outputFile = $this->tempPath.$ts.'_output';
			
			//Copy image to temp folder
			return copy($imgUrl,$this->imgUrlLocal);
		}
		return false;
	}

	private function filterImage($grayscale,$brightness,$contrast){
		$status = false;
		if($img = imagecreatefromjpeg($this->imgUrlLocal)){
   			if($grayscale) imagefilter($img,IMG_FILTER_GRAYSCALE);
   			if($brightness) imagefilter($img,IMG_FILTER_BRIGHTNESS,$brightness);
   			if($contrast) imagefilter($img,IMG_FILTER_CONTRAST,$contrast);

			$status = imagejpeg($img,$this->imgUrlLocal);
			imagedestroy($img);
		}
		return $status;
	}
	
	private function cropImage($x, $y, $w, $h){
		$status = false;
		// Create image instances
		if($src = imagecreatefromjpeg($this->imgUrlLocal)){
			$imgW = imagesx($src);
			$imgH = imagesy($src);
			if(($x + $w) > 1) $w = 1 - $x;
			if(($y + $h) > 1) $h = 1 - $y;
			$pWidth = $imgW*$w;
			$pHeight = $imgH*$h;
			$dest = imagecreatetruecolor($pWidth,$pHeight);

			// Copy
			if(imagecopy($dest,$src,0,0,$imgW*$x,$imgH*$y,$pWidth,$pHeight)){
				$status = imagejpeg($dest,$this->imgUrlLocal);
			}
			imagedestroy($src);
			imagedestroy($dest);
		}
		return $status;
	}

	private function ocrImage(){
		$retStr = '';
		if($this->imgUrlLocal){
			//OCR image, result text is output to $outputFile
			$output = array();
			//exec('tesseract '.$this->imgUrlLocal.' '.$outputFile,$output);
			//Full path to tesseract with quotes needed for Windows
			exec('"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe" '.$this->imgUrlLocal.' '.$this->outputFile,$output);
			//Obtain text from tesseract output file
			if(file_exists($this->outputFile.'.txt')){
				if($fh = fopen($this->outputFile.'.txt', 'r')){
					while (!feof($fh)) {
					  $retStr .= fread($fh, 8192);
					}
					fclose($fh);
				}
			}
			else{
				$this->logError("\tUnable to locate output file");
			}
		}
		return $this->cleanRawStr($retStr);
	}

	private function databaseRawStr($imgId,$rawStr){
		$rawStr = $this->cleanRawStr($rawStr);
		$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr) '.
			'VALUE ('.$imgId.',trim(" '.$rawStr.' "))';
		//echo 'SQL: '.$sql."\n";
		$status = $this->conn->query($sql);
		return $status;
	}

	private function setTempPath(){
		$tempPath = '';
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

	private function logError($msg) {
		$tDate = getDate();
		$msg = $msg." at ".str_pad($tDate["hours"],2,'0',STR_PAD_LEFT).":".str_pad($tDate["minutes"],2,'0',STR_PAD_LEFT).":".str_pad($tDate["seconds"],2,'0',STR_PAD_LEFT)."\n";
		$imageErrorFile = $this->logPath.'image_errors_'.$tDate["year"].'-'.str_pad($tDate["mon"],2,'0',STR_PAD_LEFT).'-'.$tDate["mday"].'.log';
		if($fh = fopen($imageErrorFile, 'at')) {
			fwrite($fh, $msg);
			fclose($fh);
		}
	}

	private function setlogPath(){
		$logPath = '';
		if(array_key_exists('tempDirRoot',$GLOBALS)){
			$logPath = $GLOBALS['tempDirRoot'];
		}
		else{
			$logPath = ini_get('upload_tmp_dir');
		}
		if(!$logPath){
			$logPath = $GLOBALS['serverRoot'];
			if(substr($logPath,-1) != '/') $logPath .= '/';
			$logPath .= 'temp/';
		}
		if(substr($logPath,-1) != '/') $logPath .= '/';
		if(file_exists($logPath.'logs/') || mkdir($logPath.'logs/')){
			$logPath .= 'logs/';
		}

		$this->logPath = $logPath;
	}

	private function cleanRawStr($inStr){
		$outStr = trim($inStr);

		return $outStr;
	}
}
?>
