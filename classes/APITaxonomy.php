<?php
require_once($SERVER_ROOT.'/classes/APIBase.php');

class APITaxonomy extends Manager{

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getTaxon($sciname){
		$retArr = array();
		$sql = 'SELECT tid, sciname, author FROM taxa WHERE (sciname = "'.$this->cleanInStr($sciname).'")';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid]['sciname'] = $r->sciname;
			$retArr[$r->tid]['author'] = $r->author;
		}
		$rs->free();
		if(count($retArr) > 1){
			//Is a Homonym, thus get kingdom
			$sql = 'SELECT e.tid, t.sciname FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
				'WHERE (e.taxauthid = 1) AND (t.rankid = 10) AND (e.tid IN("'.implode(',',array_keys($retArr)).'"))';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->tid]['kingdom'] = $r->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Setters and getters
	
}
?>