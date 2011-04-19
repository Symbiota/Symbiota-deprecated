<?php
/* 
 * Rebuilt 7 Sept 2010
 * @author  E. Gilbert: egbot@asu.edu
*/

include_once("TPEditorManager.php");

class TPDescEditorManager extends TPEditorManager{

 	public function __construct(){
 		parent::__construct();
 	}
 	
 	public function __destruct(){
 		parent::__destruct();
 	}

	public function getDescriptions(){
		$descrArr = Array();
		$sql = "SELECT tdb.tdbid, tdb.displaylevel, tdb.language, tdb.notes, tdb.caption, tdb.source, tdb.sourceurl, ".
			"tds.tdsid, tds.heading, tds.statement, tds.notes as stmtnotes, tds.displayheader, tds.sortsequence ".
			"FROM (taxstatus ts INNER JOIN taxadescrblock tdb ON ts.TidAccepted = tdb.tid) ".
			"LEFT JOIN taxadescrstmts tds ON tdb.tdbid = tds.tdbid ".
			"WHERE (tdb.tid = $this->tid) AND (ts.taxauthid = 1) ";
		if($this->language) $sql .=	"AND (tdb.Language = '".$this->taxonCon->real_escape_string($this->language)."') ";
		$sql .=	"ORDER BY tdb.Language, tdb.DisplayLevel, tds.SortSequence";
		//echo $sql;
		$result = $this->taxonCon->query($sql);
		$prevTdbid = 0;
		while($row = $result->fetch_object()){
			$tdbid = $row->tdbid;
			if($tdbid != $prevTdbid){
				$descrArr[$row->language][$row->displaylevel]["tdbid"] = $tdbid;
				$descrArr[$row->language][$row->displaylevel]["notes"] = $row->notes;
				$descrArr[$row->language][$row->displaylevel]["caption"] = $row->caption;
				$descrArr[$row->language][$row->displaylevel]["source"] = $row->source;
				$descrArr[$row->language][$row->displaylevel]["sourceurl"] = $row->sourceurl;
			}
			if($tdsid = $row->tdsid){
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["heading"] = $row->heading;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["statement"] = $row->statement;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["notes"] = $row->stmtnotes;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["displayheader"] = $row->displayheader;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["sortsequence"] = $row->sortsequence;
			}
			$prevTdbid = $tdbid;
		}
		$result->close();
		return $descrArr;
	}
	
	public function editDescriptionBlock(){
		$sql = "UPDATE taxadescrblock ".
			"SET language = ".($_REQUEST["language"]?"\"".$this->taxonCon->real_escape_string($_REQUEST["language"])."\"":"NULL").
			",displaylevel = ".$this->taxonCon->real_escape_string($_REQUEST["displaylevel"]).
			",notes = ".($_REQUEST["notes"]?"\"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["notes"]))."\"":"NULL").
			",caption = ".($_REQUEST["caption"]?"\"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["caption"]))."\"":"NULL").
			",source = ".($_REQUEST["source"]?"\"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["source"]))."\"":"NULL").
			",sourceurl = ".($_REQUEST["sourceurl"]?"\"".$this->taxonCon->real_escape_string($_REQUEST["sourceurl"])."\"":"NULL").
			" WHERE tdbid = ".$this->taxonCon->real_escape_string($_REQUEST["tdbid"]);
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR editing description block: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function deleteDescriptionBlock(){
		$sql = "DELETE FROM taxadescrblock WHERE tdbid = ".$this->taxonCon->real_escape_string($_REQUEST["tdbid"]);
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR deleting description block: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function addDescriptionBlock(){
		global $symbUid;
		$sql = "INSERT INTO taxadescrblock(tid,uid,".($_REQUEST["language"]?"language,":"").($_REQUEST["displaylevel"]?"displaylevel,":"").
			"notes,caption,source,sourceurl) ".
			"VALUES(".$this->taxonCon->real_escape_string($_REQUEST["tid"]).",".$this->taxonCon->real_escape_string($symbUid).
			",".($_REQUEST["language"]?"\"".$this->taxonCon->real_escape_string($_REQUEST["language"])."\",":"").
			($_REQUEST["displaylevel"]?$this->taxonCon->real_escape_string($_REQUEST["displaylevel"]).",":"").
			($_REQUEST["notes"]?"\"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["notes"]))."\",":"NULL,").
			($_REQUEST["caption"]?"\"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["caption"]))."\",":"NULL,").
			($_REQUEST["source"]?"\"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["source"]))."\",":"NULL,").
			($_REQUEST["sourceurl"]?"\"".$this->taxonCon->real_escape_string($_REQUEST["sourceurl"])."\"":"NULL").")";
			//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR adding description block: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function editStatement(){
		$sql = "UPDATE taxadescrstmts ".
			"SET heading = \"".$this->taxonCon->real_escape_string($_REQUEST["heading"])."\",".
			"statement = \"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["statement"]))."\"".
			(array_key_exists("displayheader",$_REQUEST)?",displayheader = 1":",displayheader = 0").
			($_REQUEST["sortsequence"]?",sortsequence = ".$this->taxonCon->real_escape_string($_REQUEST["sortsequence"]):"").
			" WHERE tdsid = ".$this->taxonCon->real_escape_string($_REQUEST["tdsid"]);
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR editing description statement: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function deleteStatement(){
		$sql = "DELETE FROM taxadescrstmts WHERE tdsid = ".$this->taxonCon->real_escape_string($_REQUEST["tdsid"]);
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR deleting description statement: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function addStatement(){
		$sql = "INSERT INTO taxadescrstmts(tdbid,heading,statement,displayheader".($_REQUEST["sortsequence"]?",sortsequence":"").") ".
			"VALUES(".$this->taxonCon->real_escape_string($_REQUEST["tdbid"]).",\"".$this->taxonCon->real_escape_string($_REQUEST["heading"]).
			"\",\"".$this->cleanStr($this->taxonCon->real_escape_string($_REQUEST["statement"]))."\",".
			(array_key_exists("displayheader",$_REQUEST)?"1":"0").
			($_REQUEST["sortsequence"]?",".$this->taxonCon->real_escape_string($_REQUEST["sortsequence"]):"").")";
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR adding description statement: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}
}
?>