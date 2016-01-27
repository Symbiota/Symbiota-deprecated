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

	//Edit functions
	public function saveAttributes($stateId,$occid,$uid){
		if(!is_numeric($stateId) || !is_numeric($occid) || !is_numeric($uid)){
			$this->errorMessage = 'ERROR saving occurrence attribute: bad input values';
			return false;
		}
		$sql = 'INSERT INTO tmattributes(stateid,occid,createduid) VALUES('.$stateId.','.$occid.','.$uid.') ';
		if(!$this->conn->query($sql)){
			$this->errorMessage = 'ERROR saving occurrence attribute: '.$this->error;
			return false;
		}
		return true;
	}
	
	//Get data functions
	public function getImageUrls(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT i.occid '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'LEFT JOIN tmattributes a ON i.occid = a.occid '. 
				'WHERE (a.occid IS NULL) AND (o.collid = '.$this->collid.') '.
				'ORDER BY RAND() LIMIT 1';
			if($this->tidFilter){
				$sql = 'SELECT i.occid '.
					'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
					'INNER JOIN taxaenumtree e ON i.tid = e.tid '.
					'LEFT JOIN tmattributes a ON i.occid = a.occid '.
					'WHERE (e.parenttid = '.$this->tidFilter.' OR e.tid = '.$this->tidFilter.') AND (a.occid IS NULL) AND (o.collid = '.$this->collid.') '.
					'ORDER BY RAND() LIMIT 1';
			}
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$sql2 = 'SELECT i.imgid, i.url, i.originalurl, i.occid '.
					'FROM images i '.
					'WHERE (i.occid = '.$r->occid.') ';
				$rs2 = $this->conn->query($sql2);
				$cnt = 1;
				while($r2 = $rs2->fetch_object()){
					$retArr[$r2->occid][$cnt]['web'] = $r2->url;
					$retArr[$r2->occid][$cnt]['lg'] = $r2->originalurl;
					$cnt++;
				}
				$rs2->free();
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getTraitNames(){
		$retArr = array();
		$sql = 'SELECT traitid, traitname '.
			'FROM tmtraits '. 
			'WHERE traittype IN("UM","OM")';
		if($this->tidFilter){
			$sql = 'SELECT DISTINCT t.traitid, t.traitname '.
				'FROM tmtraits t INNER JOIN tmtraittaxalink l ON t.traitid = l.traitid '.
				'INNER JOIN taxaenumtree e ON l.tid = e.parenttid '.
				'WHERE traittype IN("UM","OM") AND e.taxauthid = 1 AND e.tid = '.$this->tidFilter;
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->traitid] = $r->traitname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getTraitArr($traitID){
		$retArr = array();
		if(is_numeric($traitID)){
			$sql = 'SELECT traitname, traittype, units, description, refurl, notes, dynamicproperties '.
				'FROM tmtraits '. 
				'WHERE (traitid = '.$traitID.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['name'] = $r->traitname;
				$retArr['type'] = $r->traittype;
				$retArr['units'] = $r->units;
				$retArr['description'] = $r->description;
				$retArr['refurl'] = $r->refurl;
				$retArr['notes'] = $r->notes;
				$retArr['props'] = $r->dynamicproperties;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getTraitStates($traitId){
		$retArr = array();
		if(is_numeric($traitId)){
			$sql = 'SELECT stateid, statename, description, notes, refurl '.
				'FROM tmstates '.
				'WHERE traitid = '.$traitId.' ORDER BY sortseq ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->stateid]['name'] = $r->statename;
				$retArr[$r->stateid]['description'] = $r->description;
				$retArr[$r->stateid]['notes'] = $r->notes;
				$retArr[$r->stateid]['refurl'] = $r->refurl;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getTaxonFilterSuggest($str,$exactMatch=false){
		$retArr = array();
		if($str){
			$sql = 'SELECT tid, sciname FROM taxa ';
			if($exactMatch){
				$sql .= 'WHERE sciname = "'.$str.'"';
			}
			else{
				$sql .= 'WHERE sciname LIKE "'.$str.'%"';
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = array('id' => $r->tid, 'value' => $r->sciname);
			}
			$rs->free();
		}
		return json_encode($retArr);
	}

	//Attribute mining 
	public function getFieldValueArr($collid, $traitID, $fieldName, $tidFilter){
		$retArr = array();
		if(is_numeric($collid) && is_numeric($traitID)){
			$sql = '';
			if($tidFilter){
				$sql = 'SELECT DISTINCT o.'.$fieldName.' '.
					'FROM omoccurrences o INNER JOIN taxaenumtree e ON o.tidinterpreted = e.tid '.
					'WHERE (o.collid = '.$collid.') AND (o.'.$fieldName.' IS NOT NULL) AND (e.taxauthid = 1) AND (e.parenttid = '.$tidFilter.' OR o.tidinterpreted = '.$tidFilter.') '. 
					'AND (o.occid NOT IN(SELECT t.occid FROM tmattributes t INNER JOIN tmstates s ON t.stateid = s.stateid WHERE s.traitid = '.$traitID.')) '.
					'ORDER BY o.'.$fieldName;
			}
			else{
				$sql = 'SELECT DISTINCT o.'.$fieldName.' FROM omoccurrences o '.
					'WHERE o.collid = '.$collid.' AND o.'.$fieldName.' IS NOT NULL '. 
					'AND o.occid NOT IN(SELECT t.occid FROM tmattributes t INNER JOIN tmstates s ON t.stateid = s.stateid WHERE s.traitid = '.$traitID.') '.
					'ORDER BY o.'.$fieldName;
			}
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_assoc()){
				$retArr[] = $r[$fieldName];
			}
			$rs->free();
			//sort($retArr);
		}
		return $retArr;
	}

	public function submitBatchAttributes($collid, $stateID, $fieldName, $fieldValue, $uid){
		if(!is_numeric($collid) || !is_numeric($stateID) || !is_numeric($uid)){
			$this->errorMessage = 'ERROR saving occurrence attribute: bad input values';
			return false;
		}
		$sql = 'INSERT INTO tmattributes(stateid,occid,createduid) '.
			'SELECT "'.$stateID.'", occid, "'.$uid.'" FROM omoccurrences '.
			'WHERE collid = '.$collid.' AND '.$fieldName.' = "'.$this->cleanInStr($fieldValue).'"';
		//echo $sql; exit;
		if(!$this->conn->query($sql)){
			$this->errorMessage = 'ERROR saving batch occurrence attributes: '.$this->conn->error;
			return false;
		}
		return true;
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