<?php
class TaxaLoaderItisManager extends TaxaLoaderManager{
	
	private $extraArr = array();
	private $authArr = array();
	
	function __construct() {
 		parent::__construct();
	}

	function __destruct(){
 		parent::__destruct();
	}

	public function loadFile(){
		echo "<li>Starting Upload</li>";
		ob_flush();
		flush();
		//Initiate upload process
		$this->conn->query("DELETE FROM uploadtaxa");
		$fh = fopen($this->uploadTargetPath.$this->uploadFileName,'rb') or die("Can't open file");
		echo "<li>Taxa file uploaded and successfully opened</li>";
		ob_flush();
		flush();
		
		//First run through file and grab and store Authors, Synonyms, and Vernaculars
		$delimtStr = "";
		echo "<li>Harvesting authors, synonyms, and vernaculars</li>";
		ob_flush();
		flush();
		while($record = fgets($fh)){
			if(!$delimtStr){
				$delimtStr = "|";
				if(!strpos($record,"|") && strpos($record,",")){
					$delimtStr = ",";
				}
			}
			if(substr($record,4) != '[TU]'){
				$recordArr = explode($delimtStr,$record);
				if($recordArr[0] == "[SY]"){
					$this->extraArr[$recordArr[2]]['s'] = $this->cleanInStr($recordArr[3]);
				}
				elseif($recordArr[0] == "[TA]"){
					$this->authArr[$recordArr[1]] = $this->cleanInStr($recordArr[2]);
				}
				elseif($recordArr[0] == "[VR]"){
					$this->extraArr[$recordArr[4]]['v'] = $this->cleanInStr($recordArr[3]);
					$this->extraArr[$recordArr[4]]['l'] = $this->cleanInStr($recordArr[5]);
				}
			}
		}
		if($this->authArr){
			echo '<ul><li>Authors mapped</li></ul>';
		}
		if($this->extraArr){
			echo '<ul><li>Synonyms and Vernaculars mapped</li></ul>';
		}
		ob_flush();
		flush();
		
		
		//Load taxa records
		echo "<li>Harvest and loading Taxa... ";
		ob_flush();
		flush();
		$recordCnt = 0;
		rewind($fh);
		
		while($record = fgets($fh)){
			$recordArr = explode($delimtStr,$record);
			if($recordArr[0] == "[TU]"){
				$this->loadTaxonUnit($recordArr);
				$recordCnt++;
			}
		}

		echo " Done!</li>";
		echo '<li>'.$recordCnt.' records loaded</li>';
		ob_flush();
		flush();
		fclose($fh);
		$this->cleanUpload();
	}

	private function loadTaxonUnit($tuArr){
		if(count($tuArr) > 24){
			
			$unitInd3 = $this->cleanInStr($tuArr[8]?$tuArr[8]:$tuArr[6]);
			$unitName3 = $this->cleanInStr($tuArr[9]?$tuArr[9]:$tuArr[7]);
			$sciName = $this->cleanInStr(trim($tuArr[2]." ".$tuArr[3].($tuArr[4]?" ".$tuArr[4]:"")." ".$tuArr[5]." ".$unitInd3." ".$unitName3));
			$author = '';
			if($tuArr[20] && array_key_exists($tuArr[20],$this->authArr)){
				$author = $this->authArr[$tuArr[20]];
				unset($this->authArr[$tuArr[20]]);
			}
			$sourceId = $this->cleanInStr($tuArr[1]);
			$sourceAcceptedId = '';
			$acceptance = '1';
			$vernacular = '';
			$vernlang = '';
			if(array_key_exists($sourceId,$this->extraArr)){
				$eArr = $this->extraArr[$sourceId];
				if(array_key_exists('s',$eArr)){
					$sourceAcceptedId = $eArr['s'];
					$acceptance = '0';
				}
				if(array_key_exists('v',$eArr)){
					$vernacular = $eArr['v'];
					$vernlang = $eArr['l'];
				}
				unset($this->extraArr[$sourceId]);
			}
			$sql = "INSERT INTO uploadtaxa(SourceId,scinameinput,sciname,unitind1,unitname1,unitind2,unitname2,unitind3,".
				"unitname3,SourceParentId,author,kingdomid,rankid,SourceAcceptedId,acceptance,vernacular,vernlang) ".
				"VALUES (".$sourceId.',"'.$sciName.'","'.$sciName.'",'.
				($tuArr[2]?'"'.$this->cleanInStr($tuArr[2]).'"':"NULL").",".
				($tuArr[3]?'"'.$this->cleanInStr($tuArr[3]).'"':"NULL").",".
				($tuArr[4]?'"'.$this->cleanInStr($tuArr[4]).'"':"NULL").",".
				($tuArr[5]?'"'.$this->cleanInStr($tuArr[5]).'"':"NULL").",".
				($unitInd3?'"'.$unitInd3.'"':"NULL").",".($unitName3?'"'.$unitName3.'"':"NULL").",".
				($tuArr[18]?$this->cleanInStr($tuArr[18]):"NULL").",".
				($author?'"'.$author.'"':"NULL").",".
				($tuArr[23]?$this->cleanInStr($tuArr[23]):"NULL").",".
				($tuArr[24]?$this->cleanInStr($tuArr[24]):"NULL").",".
				($sourceAcceptedId?$sourceAcceptedId:'NULL').','.$acceptance.','.
				($vernacular?'"'.$vernacular.'"':'NULL').','.
				($vernlang?'"'.$vernlang.'"':'NULL').')';
			//echo '<div>'.$sql.'</div>';
			if(!$this->conn->query($sql)){
				//Failed because name is already in table, thus replace if this one is accepted
				if($acceptance){
					$sql = 'REPLACE'.substr($sql,6);
					$this->conn->query($sql);
				}
			}
		}
	}
	
}
?>