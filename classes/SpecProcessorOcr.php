<?php
/* 
 * Used by automatic nightly process and by the occurrence editor (/collections/editor/occurrenceeditor.php) 
 * Authors: egbot 2012
 */

class SpecProcessorOcr{

	private $conn;
	private $tempPath;

	function __construct() {
	}
	
	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function batchOcrUnprocessed($collArr = 0){
		//OCR all images with a status of "unprocessed" and change to "unprocessed/OCR"
		//Triggered automaticly (crontab) on a nightly basis
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'SELECT $imgId, IFNULL(i.origianlurl, i.url) AS url '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'LEFT JOIN specprocessorrawlabels rl ON i.imgid = rl.imgid '.
			'WHERE o.processingstatus = "unprocessed" AND rl.prlid IS NULL ';
		if($collArr) $sql .= 'AND o.collid IN('.implode(',',$collArr).')';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$rawStr = $this->imgUrl($r->url);
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
			$sql = 'SELECT origianlurl, url '.
				'FROM images '.
				'WHERE (imgid = '.$imgId.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$imgUrl = ($r->originalurl?$r->originalurl:$r->url);
			}
			$rs->close();
			$rawStr = ocrImage($imgUrl);
	 		if(!($this->conn === false)) $this->conn->close();
	 		return $rawStr;
		}
	}

	public function ocrImage($imgUrl,$grayscale = 0,$brightness = 0,$contrast = 0){
		$retStr = '';
		if($imgUrl){
			//Set temp folder path and file names  
			$this->setTempPath();
			$ts = '_'.time();
			$imgFile = $this->tempPath.$ts.'_img.jpg';
			$outputFile = $this->tempPath.$ts.'_output.txt';
			
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
					exec('tesseract '.$imgFile.' '.$outputFile, $output);
				}
				else{
					//Unable to write image to temp folder
					//Add some error reporting
				}
				
				//Obtain text from tesseract output file
				$fh = fopen($outputFile, 'r');
				while (!feof($fh)) {
				  $retStr .= fread($fh, 8192);
				}
				fclose($fh);
				//unlink($imgFile);
				//unlink($outputFile);
   			}
   			else{
   				//Unable to create image
				//Add some error reporting
   			}
   		}
		else{
			//Unable to locate URL
			//Add some error reporting
		}
		return trim($retStr);
	}
	
	private function databaseRawStr($imgId,$rawStr){
		$sql = 'INSERT INTO (imgid,rawstr) '.
			'VALUE ('.$imgId.',"'.$rawStr.'")';
		$status = $this->query($sql);
		return $status;
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
 