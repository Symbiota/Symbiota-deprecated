<?php
/*
 * Built 3 Feb 2011
 * By E.E. Gilbert
 */
 
class SpecProcessorAbbyy extends SpecProcessorManager{

	private $recDelimiter = '/--END--/';
	private $pkPattern = '/(ASU\d{7})/';
	
	function __construct($logPath){
 		parent::__construct($logPath);
	}

	public function loadLabelFile(){
		$statusArr = Array();
	 	$fileName = basename($_FILES['abbyyfile']['name']);
	 	$filePath = $GLOBALS['tempDirRoot'];
	 	if(!$filePath) $filePath = ini_get('upload_tmp_dir');
	 	if(substr($filePath,-1) != '/') $filePath .= '/';
	 	if(move_uploaded_file($_FILES['abbyyfile']['tmp_name'], $filePath.$fileName)){
	 		$fh = fopen($filePath.$fileName,'rb') or die("Can't open file");
			if($fh){
				$statusArr = $this->parseAbbyyFile($fh);
			}
			fclose($fh);
			unlink($filePath.$fileName);
	 	}
	 	return $statusArr;
	}
	
	private function parseAbbyyFile($fh){
		$statusArr = Array();
		$labelBlock = '';
		$lineCnt = 0;
		while(!feof($fh)){
			$buffer = fgets($fh);
			if(preg_match($this->recDelimiter,$buffer)){
				$labelBlock = trim($labelBlock);
				if($labelBlock){
					if($statusStr = $this->loadRecord(trim($labelBlock))){
						$statusArr[] = $statusStr;
					}
				}
				$labelBlock = '';
				$lineCnt = 0;
			}
			else{
				$labelBlock .= $buffer;
				$lineCnt++;
			}
		}
		return $statusArr;
	}
}
?>
 