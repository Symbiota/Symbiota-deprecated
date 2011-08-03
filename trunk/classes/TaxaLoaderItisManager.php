<?php
class TaxaLoaderItisManager extends TaxaLoaderManager{
	
	function __construct() {
 		parent::__construct();
	}

	function __destruct(){
 		parent::__destruct();
	}

	public function uploadFile(){
		$statusStr = "<li>Starting Upload</li>";
		$synLoaded = false;
		$authLoaded = false;
		$vernLoaded = false;
		$this->conn->query("DELETE FROM uploadtaxa");
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'rb') or die("Can't open file");
		$recordCnt = 0;
		$delimtStr = "";
		while($record = fgets($fh)){
			if(!$delimtStr){
				$delimtStr = "|";
				if(!strpos($record,"|") && strpos($record,",")){
					$delimtStr = ",";
				}
			}
			$recordArr = explode($delimtStr,$record);
			if($recordArr[0] == "[TU]"){
				$this->loadTaxonUnit($recordArr);
				$recordCnt++;
			}
			elseif($recordArr[0] == "[SY]"){
				$this->loadSynonyms($recordArr);
				$synLoaded = true;
			}
			elseif($recordArr[0] == "[TA]"){
				$this->loadAuthors($recordArr);
				$authLoaded = true;
			}
			elseif($recordArr[0] == "[VR]"){
				$this->loadVernaculars($recordArr);
				$vernLoaded = true;
			}
		}
		$statusStr .= '<li>'.$recordCnt.' taxon records uploaded</li>';
		if($synLoaded){
			$statusStr .= '<ul><li>Synonym links added</li></ul>';
		}
		if($authLoaded){
			$statusStr .= '<ul><li>Authors added</li></ul>';
		}
		if($vernLoaded){
			$statusStr .= '<ul><li>Vernaculars added</li></ul>';
		}
		fclose($fh);
		$this->cleanUpload();
		return $statusStr;
	}

	private function loadTaxonUnit($tuArr){
		if(count($tuArr) > 24){
			$unitInd3 = $this->conn->real_escape_string($tuArr[8]?$tuArr[8]:$tuArr[6]);
			$unitName3 = $this->conn->real_escape_string($tuArr[9]?$tuArr[9]:$tuArr[7]);
			$sciName = $this->conn->real_escape_string(trim($tuArr[2]." ".$tuArr[3].($tuArr[4]?" ".$tuArr[4]:"")." ".$tuArr[5]." ".$unitInd3." ".$unitName3));
			$sql = "INSERT INTO uploadtaxa(SourceId,scinameinput,sciname,unitind1,unitname1,unitind2,unitname2,unitind3,unitname3,SourceParentId,author,kingdomid,rankid) ".
				"VALUES (".$this->conn->real_escape_string($tuArr[1]).",\"".$sciName."\",\"".$sciName."\",".
				($tuArr[2]?"\"".$this->conn->real_escape_string($tuArr[2])."\"":"NULL").",".
				($tuArr[3]?"\"".$this->conn->real_escape_string($tuArr[3])."\"":"NULL").",".
				($tuArr[4]?"\"".$this->conn->real_escape_string($tuArr[4])."\"":"NULL").",".
				($tuArr[5]?"\"".$this->conn->real_escape_string($tuArr[5])."\"":"NULL").",".
				($unitInd3?"\"".$unitInd3."\"":"NULL").",".($unitName3?"\"".$unitName3."\"":"NULL").",".
				($tuArr[18]?$this->conn->real_escape_string($tuArr[18]):"NULL").",".
				($tuArr[20]?$this->conn->real_escape_string($tuArr[20]):"NULL").",".
				($tuArr[23]?$this->conn->real_escape_string($tuArr[23]):"NULL").",".
				($tuArr[24]?$this->conn->real_escape_string($tuArr[24]):"NULL").")";
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}

	private function loadSynonyms($synArr){
		if(count($synArr) == 5){
			$sql = "UPDATE uploadtaxa SET SourceAcceptedId = ".$this->conn->real_escape_string($synArr[3]).
			", acceptance = 0 WHERE (SourceId = ".$this->conn->real_escape_string($synArr[2]).')';
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}

	private function loadAuthors($aArr){
		if(count($aArr) == 5 && $aArr[2]){
			$sql = "UPDATE uploadtaxa SET author = \"".$this->conn->real_escape_string($aArr[2]).
				"\" WHERE (author = '".$this->conn->real_escape_string($aArr[1])."')";
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}

	private function loadVernaculars($vArr){
		if(count($vArr) == 8 && $vArr[3]){
			$sql = "UPDATE uploadtaxa SET vernacular = \"".$this->conn->real_escape_string($vArr[3]).
				"\", vernlang = \"".$this->conn->real_escape_string($vArr[5])."\" WHERE (SourceId = ".$this->conn->real_escape_string($vArr[4]).')';
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}
}

?>
