<?php 
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceEditorAttr extends Manager {

	private $collid;
	private $tidFilter;

	public function __construct($type = 'write'){
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	public function __destruct(){
		if($this->conn !== false) $this->conn->close();
	}

	public function getImageUrls(){
		$retArr = array();
		$sql = 'SELECT i.occid '.
			'FROM images i LEFT JOIN tmdescription d ON i.occid = d.occid '. 
			'WHERE (d.occid IS NULL) AND (i.occid IS NOT NULL) '.
			'LIMIT 1';
		if($this->tidFilter){
			$sql = 'SELECT i.occid '.
				'FROM images i INNER JOIN taxaenumtree e ON i.tid = e.tid '.
				'LEFT JOIN tmdescription d ON i.occid = d.occid '.
				'WHERE (e.parenttid = '.$this->tidFilter.' OR e.tid = '.$this->tidFilter.') AND (d.occid IS NULL) AND (i.occid IS NOT NULL) '.
				'LIMIT 1';
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$sql2 = 'SELECT i.imgid, i.url, i.originalurl, i.occid '.
				'FROM images i '. 
				'WHERE (i.occid = '.$r->occid.') ';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$retArr[$r2->occid][$r2->imgid]['url'] = $r2->url;
				$retArr[$r2->occid][$r2->imgid]['lgurl'] = $r2->originalurl;
			}
			$rs2->free();
		}
		$rs->free();
		return $retArr;
	}

	//Get data functions
	public function getAttrNames(){
		$retArr = array();
		$sql = 'SELECT traitid, traitname '.
			'FROM tmtraits '. 
			'WHERE traittype IN("UM","OM")';
		if($this->tidFilter){
			$sql = 'SELECT DISTINCT t.traitid, t.traitname '.
				'FROM tmtraits t INNER JOIN tmtraittaxalink l ON t.traitid = l.traitid '.
				'INNER JOIN taxaenumtree e ON l.tid = e.tid '.
				'WHERE traittype IN("UM","OM") AND e.parenttid = '.$this->tidFilter;
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->traitid] = $r->traitname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}
	
	public function getAttrStates($attrId){
		$retArr = array();
		$sql = 'SELECT stateid, statename '.
			'FROM tmstates '.
			'WHERE traitid = '.$attrId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->stateid] = $r->statename;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getTaxonFilterSuggest($str){
		$retArr = array();
		if($str){
			$sql = 'SELECT tid, sciname FROM taxa WHERE sciname LIKE "'.$str.'%"';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = array('id' => $r->tid, 'value' => $r->sciname);
			}
			$rs->free();
		}
		return json_encode($retArr);
	}

	//Setters and getters
	public function setCollid($collid){
		if(is_numeric($collid)){
			$this->collid = $collid;
		}
	}

	public function setTidFilter($tid){
		if(is_numeric($tid)){
			$this->tidFilter = $tid;
		}
	}
}
?>