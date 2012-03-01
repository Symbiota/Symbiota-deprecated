<?php
/*
 * Used by automatic nightly process and by the occurrence editor (/collections/editor/occurrenceeditor.php)
 * Authors: egbot 2012
 */
include_once($serverRoot.'/config/dbconnection.php');

class SpecProcessorOcr{

	private $conn;
	private $tempPath;
	private $logPath;
	//If silent is set, script will produce no non-fatal output.
	private $silent = 1;

	function __construct() {
		$this->setlogPath();
	}

	function __destruct(){
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
				$rawStr = $this->ocrImage($r->url,$r->imgid);
				//echo 'rawStr: '.$rawStr."\n";
				if($rawStr){
					$this->databaseRawStr($r->imgid,$rawStr);
				}
			}
	 		$rs->close();
		}
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function ocrImageByImgId($imgId,$grayscale = 0,$brightness = 0,$contrast = 0){
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
			$rawStr = $this->ocrImage($imgUrl,$imgId);
			//echo 'rawStr: '.$rawStr."\n";
	 		if(!($this->conn === false)) $this->conn->close();
	 		return $rawStr;
		}
	}

	public function ocrImage($imgUrl,$imgId = 0,$grayscale = 0,$brightness = 0,$contrast = 0){
		$retStr = '';
		if($imgUrl){
			//If there is an image domain name is set in symbini.php and url is relative,
			//then it's assumed that image is located on another server, thus add domain to url
			if(array_key_exists("imageDomain",$GLOBALS)){
				if(substr($imgUrl,0,1)=="/"){
					$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
				}
			}
			//echo 'URL: '.$imgUrl."\n";

			//Set temp folder path and file names
			$this->setTempPath();
			$ts = time();
			$imgFile = $this->tempPath.$ts.'_img.jpg';
			$outputFile = $this->tempPath.$ts.'_output';

   			if($img = imagecreatefromjpeg($imgUrl)){
				//Optional adjustments
   				if($grayscale) imagefilter($img,IMG_FILTER_GRAYSCALE);
   				if($brightness) imagefilter($img,IMG_FILTER_BRIGHTNESS,$brightness);
   				if($contrast) imagefilter($img,IMG_FILTER_CONTRAST,$contrast);

				//Save image to temp folder; if prior to Tesseract ver 3.0, must save as TIF
				//$status = imagetiff($img,$imgFile)
				$status = imagejpeg($img,$imgFile);
				imagedestroy($img);
				//OCR image, result text is output to $outputFile
				if($status){
					$output = array();
					//exec('tesseract '.$imgFile.' '.$outputFile,$output);
					//Full path to tesseract with quotes needed for Windows
					exec('"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe" '.$imgFile.' '.$outputFile,$output);
					//Obtain text from tesseract output file
					if(file_exists($outputFile.'.txt')){
						if($fh = fopen($outputFile.'.txt', 'r')){
							while (!feof($fh)) {
							  $retStr .= fread($fh, 8192);
							}
							fclose($fh);
						}
						//unlink($imgFile);
						//unlink($outputFile);
					}
				}
				else{
					//Unable to write image to temp folder
					$this->logImageError("Unable to write image to temp folder, ".($imgId?'Image ID: '.$imgId:'Image URL: '.$imgUrl));
				}

   			}
   			else{
   				//Unable to create image
				$this->logImageError("Unable to create image, ".($imgId?'Image ID: '.$imgId:'').", URL: ".$imgUrl);
   			}
   		}
		else{
			$this->logImageError("Empty URL, ".($imgId?'Image ID: '.$imgId:'Image URL: '.$imgUrl));
		}
		return trim($retStr);
	}

	private function databaseRawStr($imgId,$rawStr){
		$rawStr = $this->cleanRawStr($rawStr);
		$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr) '.
			'VALUE ('.$imgId.',trim(" '.$rawStr.' "))';
		//echo 'SQL: '.$sql."\n";
		$status = $this->conn->query($sql);
		return $status;
	}

	private function logImageError($msg) {
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
}
?>
