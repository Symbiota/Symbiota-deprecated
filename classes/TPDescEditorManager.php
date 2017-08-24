<?php
include_once("TPEditorManager.php");

class TPDescEditorManager extends TPEditorManager{

 	public function __construct(){
 		parent::__construct();
 	}
 	
 	public function __destruct(){
 		parent::__destruct();
 	}

	public function getDescriptions($editor = false){
		$descrArr = Array();
		$sql = 'SELECT t.tid, t.sciname, tdb.tdbid, tdb.caption, tdb.source, tdb.sourceurl, tdb.displaylevel, tdb.notes, tdb.language '.
			'FROM (taxstatus ts INNER JOIN taxadescrblock tdb ON ts.tid = tdb.tid) '.
			'INNER JOIN taxa t ON ts.tid = t.tid '.
			'WHERE (ts.TidAccepted = '.$this->tid.') AND (ts.taxauthid = 1) ';
		if(!$editor){
			$sql .= 'AND (tdb.Language = "'.$this->language.'") ';
		}
		$sql .= 'ORDER BY tdb.DisplayLevel ';
		//echo $sql;
		if($rs = $this->taxonCon->query($sql)){
			while($r = $rs->fetch_object()){
				//Load description block info
				$descrArr[$r->tdbid]['caption'] = $r->caption;
				$descrArr[$r->tdbid]['source'] = $r->source;
				$descrArr[$r->tdbid]['sourceurl'] = $r->sourceurl;
				$descrArr[$r->tdbid]['displaylevel'] = $r->displaylevel;
				$descrArr[$r->tdbid]['notes'] = $r->notes;
				$descrArr[$r->tdbid]['language'] = $r->language;
				$descrArr[$r->tdbid]['tid'] = $r->tid;
				$descrArr[$r->tdbid]['sciname'] = $r->sciname;
			}
			$rs->free();
		}
		else{
			trigger_error('Unable to get descriptions; '.$this->taxonCon->error);
		}
		if($descrArr){
			//Grab statements
			$sql2 = 'SELECT tdbid, tdsid, heading, statement, notes, displayheader, sortsequence '.
				'FROM taxadescrstmts '.
				'WHERE (tdbid IN('.implode(',',array_keys($descrArr)).')) '.
				'ORDER BY sortsequence'; 
			if($rs2 = $this->taxonCon->query($sql2)){
				while($r2 = $rs2->fetch_object()){
					$descrArr[$r2->tdbid]["stmts"][$r2->tdsid]["heading"] = $r2->heading;
					$descrArr[$r2->tdbid]["stmts"][$r2->tdsid]["statement"] = $r2->statement;
					$descrArr[$r2->tdbid]["stmts"][$r2->tdsid]["notes"] = $r2->notes;
					$descrArr[$r2->tdbid]["stmts"][$r2->tdsid]["displayheader"] = $r2->displayheader;
					$descrArr[$r2->tdbid]["stmts"][$r2->tdsid]["sortsequence"] = $r2->sortsequence;
				}
				$rs2->free();
			}
			else{
				trigger_error('Unable to get statements; '.$this->conn->error);
			}
		}
		return $descrArr;
	}

	public function editDescriptionBlock(){
		$sql = "UPDATE taxadescrblock ".
			"SET language = ".($_REQUEST["language"]?"\"".$this->cleanInStr($_REQUEST["language"])."\"":"NULL").
			",displaylevel = ".$this->cleanInStr($_REQUEST["displaylevel"]).
			",notes = ".($_REQUEST["notes"]?"\"".$this->cleanInStr($_REQUEST["notes"])."\"":"NULL").
			",caption = ".($_REQUEST["caption"]?"\"".$this->cleanInStr($_REQUEST["caption"])."\"":"NULL").
			",source = ".($_REQUEST["source"]?"\"".$this->cleanInStr($_REQUEST["source"])."\"":"NULL").
			",sourceurl = ".($_REQUEST["sourceurl"]?"\"".$this->cleanInStr($_REQUEST["sourceurl"])."\"":"NULL").
			" WHERE (tdbid = ".$this->taxonCon->real_escape_string($_REQUEST["tdbid"]).')';
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR editing description block: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function deleteDescriptionBlock(){
		$sql = "DELETE FROM taxadescrblock WHERE (tdbid = ".$this->taxonCon->real_escape_string($_REQUEST["tdbid"]).')';
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
		if(is_numeric($_REQUEST["tid"])){
			$sql = "INSERT INTO taxadescrblock(tid,uid,".($_REQUEST["language"]?"language,":"").($_REQUEST["displaylevel"]?"displaylevel,":"").
				"notes,caption,source,sourceurl) ".
				"VALUES(".$_REQUEST["tid"].",".$symbUid.
				",".($_REQUEST["language"]?"\"".$this->cleanInStr($_REQUEST["language"])."\",":"").
				($_REQUEST["displaylevel"]?$this->taxonCon->real_escape_string($_REQUEST["displaylevel"]).",":"").
				($_REQUEST["notes"]?"\"".$this->cleanInStr($_REQUEST["notes"])."\",":"NULL,").
				($_REQUEST["caption"]?"\"".$this->cleanInStr($_REQUEST["caption"])."\",":"NULL,").
				($_REQUEST["source"]?"\"".$this->cleanInStr($_REQUEST["source"])."\",":"NULL,").
				($_REQUEST["sourceurl"]?"\"".$_REQUEST["sourceurl"]."\"":"NULL").")";
			//echo $sql;
			$status = "";
			if(!$this->taxonCon->query($sql)){
				$status = "ERROR adding description block: ".$this->taxonCon->error;
				//$status .= "\nSQL: ".$sql;
			}
		}
		return $status;
	}
	
	public function remapDescriptionBlock($tdbid){
		$statusStr = '';
		$displayLevel = 1;
		$sql = 'SELECT max(displaylevel) as maxdl FROM taxadescrblock WHERE tid = '.$this->tid; 
		if($rs = $this->taxonCon->query($sql)){
			if($r = $rs->fetch_object()){
				$displayLevel = $r->maxdl + 1;
			}
			$rs->free();
		}
		
		$sql = 'UPDATE taxadescrblock SET tid = '.$this->tid.',displaylevel = '.$displayLevel.' WHERE tdbid = '.$tdbid;
		//echo $sql;
		if(!$this->taxonCon->query($sql)){
			$statusStr = 'ERROR remapping description block: '.$this->taxonCon->error;
		}
		return $statusStr;
	}

	public function addStatement($stArr){
		$status = '';
		$stmtStr = $this->cleanInStr($stArr['statement']);
		if(substr($stmtStr,0,3) == '<p>' && substr($stmtStr,-4) == '</p>'){
			$stmtStr = trim(substr($stmtStr,3,strlen($stmtStr)-7));
		}
		if($stmtStr && $stArr['tdbid'] && is_numeric($stArr['tdbid'])){
			$sql = 'INSERT INTO taxadescrstmts(tdbid,heading,statement,displayheader'.($stArr['sortsequence']?',sortsequence':'').') '.
				'VALUES('.$stArr['tdbid'].',"'.$this->cleanInStr($stArr['heading']).
				'","'.$stmtStr.'",'.(array_key_exists('displayheader',$stArr)?'1':'0').
				($stArr['sortsequence']?','.$this->cleanInStr($stArr['sortsequence']):'').')';
			//echo $sql;
			if(!$this->taxonCon->query($sql)){
				$status = 'ERROR adding description statement: '.$this->taxonCon->error;
			}
		}
		return $status;
	}
	
	public function editStatement($stArr){
		$status = "";
		$stmtStr = $this->cleanInStr($stArr['statement']);
		if(substr($stmtStr,0,3) == '<p>' && substr($stmtStr,-4) == '</p>'){
			$stmtStr = trim(substr($stmtStr,3,strlen($stmtStr)-7));
		}
		if($stmtStr && $stArr['tdsid'] && is_numeric($stArr["tdsid"])){
			$sql = 'UPDATE taxadescrstmts '.
				'SET heading = "'.$this->cleanInStr($stArr['heading']).'",'.
				'statement = "'.$stmtStr.'"'.
				(array_key_exists('displayheader',$stArr)?',displayheader = 1':',displayheader = 0').
				($stArr['sortsequence']?',sortsequence = '.$this->cleanInStr($stArr['sortsequence']):'').
				' WHERE (tdsid = '.$stArr['tdsid'].')';
			//echo $sql;
			if(!$this->taxonCon->query($sql)){
				$status = "ERROR editing description statement: ".$this->taxonCon->error;
			}
		}
		return $status;
	}

	public function deleteStatement($tdsid){
		$status = "";
		if(is_numeric($tdsid)){
			$sql = "DELETE FROM taxadescrstmts WHERE (tdsid = ".$tdsid.')';
			//echo $sql;
			if(!$this->taxonCon->query($sql)){
				$status = "ERROR deleting description statement: ".$this->taxonCon->error;
			}
		}
		return $status;
	}
}
?>