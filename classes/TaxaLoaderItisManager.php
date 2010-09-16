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
		while($record = fgets($fh)){
			$recordArr = explode("|",$record);
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
		return $statusStr;
	}

	private function loadTaxonUnit($tuArr){
		if(count($tuArr) > 24){
			$unitInd3 = $tuArr[8]?$tuArr[8]:$tuArr[6];
			$unitName3 = $tuArr[9]?$tuArr[9]:$tuArr[7];
			$sciName = trim($tuArr[2]." ".$tuArr[3].($tuArr[4]?" ".$tuArr[4]:"")." ".$tuArr[5]." ".$unitInd3." ".$unitName3);
			$sql = "INSERT INTO uploadtaxa(SourceId,scinameinput,sciname,unitind1,unitname1,unitind2,unitname2,unitind3,unitname3,SourceParentId,author,kingdomid,rankid) ".
				"VALUES (".$tuArr[1].",\"".$sciName."\",\"".$sciName."\",".($tuArr[2]?"\"".$tuArr[2]."\"":"NULL").",".
				($tuArr[3]?"\"".$tuArr[3]."\"":"NULL").",".($tuArr[4]?"\"".$tuArr[4]."\"":"NULL").",".($tuArr[5]?"\"".$tuArr[5]."\"":"NULL").",".
				($unitInd3?"\"".$unitInd3."\"":"NULL").",".($unitName3?"\"".$unitName3."\"":"NULL").",".($tuArr[18]?$tuArr[18]:"NULL").",".
				($tuArr[20]?$tuArr[20]:"NULL").",".($tuArr[23]?$tuArr[23]:"NULL").",".($tuArr[24]?$tuArr[24]:"NULL").")";
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}

	private function loadSynonyms($synArr){
		if(count($synArr) == 5){
			$sql = "UPDATE uploadtaxa SET SourceAcceptedId = ".$synArr[3].", acceptance = 0 WHERE SourceId = ".$synArr[2];
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}

	private function loadAuthors($aArr){
		if(count($aArr) == 5 && $aArr[2]){
			$sql = "UPDATE uploadtaxa SET author = \"".$aArr[2]."\" WHERE author = '".$aArr[1]."'";
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}

	private function loadVernaculars($vArr){
		if(count($vArr) == 8 && $vArr[3]){
			$sql = "UPDATE uploadtaxa SET vernacular = \"".$vArr[3]."\", vernlang = \"".$vArr[5]."\" WHERE SourceId = ".$vArr[4];
			//echo '<div>'.$sql.'</div>';
			$this->conn->query($sql);
		}
	}
}
	
?>
