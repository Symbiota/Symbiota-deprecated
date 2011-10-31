<?php

class SpecProcessorLabelManager{

	public static function ocrImage($imgId){
		$retStr = '';
		if(is_numeric($imgId)){
			$imgURrl = '';
			$conn = MySQLiConnectionFactory::getCon("readonly");
			$sql = 'SELECT origianlurl, url '.
				'FROM images '.
				'WHERE (imgid = '.$imgId.')';
			$rs = $conn->query($sql);
			if($r = $rs->fetch_object()){
				$imgUrl = ($r->originalurl?$r->originalurl:$r->url);
			}
			$rs->close();
			$conn->close();
			if($imgUrl){
				//Set path where temp files will be placed  
				$tempPath = '';
				if(array_key_exists('tempDirRoot',$GLOBALS)){
					$tempPath = $GLOBALS['tempDirRoot']; 
				}
				else{
					$tempPath = $GLOBALS['serverRoot'];
				}
				if(substr($tempPath,-1) != '/') $tempPath .= '/';
				if(file_exists($tempPath.'images/') || mkdir($tempPath.'images/')){
					$tempPath .= 'images/';
				}
				if(file_exists($tempPath.'tesseract/') || mkdir($tempPath.'tesseract/')){
					$tempPath .= 'tesseract/';
				}
				if(array_key_exists('symbUid',$GLOBALS)) $tempPath .= '_'.$GLOBALS['symbUid'];
				$tempPath .= '_'.time();
				$imgFile = $tempPath.'_img.jpg';
				$outputFile = $tempPath.'_output.txt';
				
	   			if($img = imagecreatefromjpeg($imgUrl)){
					//contrast, brightness, B/W ???
	   				
					
					//Must save as TIF if Tesseract version is pre 3.0
					//$status = imagetiff($img,$imgFile)
					$status = imagejpeg($img,$imgFile);
					if($status){
						$output = array();
						exec('tesseract '.$imgFile.' '.$outputFile, $output);
					}
					else{
						//Unable to write image to temp folder
					}					
					imagedestroy($img);
					
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
	   			}
	   		}
			else{
				//Unable to locate URL
			}
		}
		return $retStr;
	}
	
	public static function parseRawTextSymbiota($prlid){
		$textBlock = '';
		if(is_numeric($prlid)){
			$conn = MySQLiConnectionFactory::getCon("readonly");
			$sql = 'SELECT rawstr '.
				'FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlid.')';
			$rs = $conn->query($sql);
			if($r = $rs->fetch_object()){
				$textBlock = $r->rawstr;
			} 
			$rs->close();
			$conn->close();
		}
		return $this->parseTextBlockSymbiota($textBlock);
	}
	
	public static function parseTextBlockSymbiota($textBlock){
		$dataMap = array();
		
		return $dataMap;
	}
	
	public static function parseTextBlockSalix($textBlock){
		$dataMap = array();
		
		return $dataMap;
	}
	
}
?>
 