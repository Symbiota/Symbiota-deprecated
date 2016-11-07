<?php 
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceAttributes extends Manager {

	private $collid;
	private $tidFilter;

	public function __construct($type = 'write'){
		parent::__construct(null, $type);
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Edit functions
	public function saveAttributes($stateId,$occid,$notes,$uid){
		if(!is_numeric($stateId) || !is_numeric($occid) || !is_numeric($uid)){
			$this->errorMessage = 'ERROR saving occurrence attribute: bad input values';
			return false;
		}
		$sql = 'INSERT INTO tmattributes(stateid,occid,notes,createduid) VALUES('.$stateId.','.$occid.',"'.$notes.'",'.$uid.') ';
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
			$sql = 'SELECT i.occid, IFNULL(o.catalognumber, o.othercatalognumbers) AS catnum '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'LEFT JOIN tmattributes a ON i.occid = a.occid '. 
				'WHERE (a.occid IS NULL) AND (o.collid = '.$this->collid.') '.
				'ORDER BY RAND() LIMIT 1';
			if($this->tidFilter){
				$sql = 'SELECT i.occid, IFNULL(o.catalognumber, o.othercatalognumbers) AS catnum '.
					'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
					'INNER JOIN taxaenumtree e ON i.tid = e.tid '.
					'LEFT JOIN tmattributes a ON i.occid = a.occid '.
					'WHERE (e.parenttid = '.$this->tidFilter.' OR e.tid = '.$this->tidFilter.') AND (a.occid IS NULL) AND (o.collid = '.$this->collid.') '.
					'ORDER BY RAND() LIMIT 1';
			}
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr[$r->occid]['catnum'] = $r->catnum;
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
	
	public function getSpecimenCount(){
		$retCnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'LEFT JOIN tmattributes a ON i.occid = a.occid '. 
				'WHERE (a.occid IS NULL) AND (o.collid = '.$this->collid.') ';
			if($this->tidFilter){
				$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.
					'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
					'INNER JOIN taxaenumtree e ON i.tid = e.tid '.
					'LEFT JOIN tmattributes a ON i.occid = a.occid '.
					'WHERE (e.parenttid = '.$this->tidFilter.' OR e.tid = '.$this->tidFilter.') AND (a.occid IS NULL) AND (o.collid = '.$this->collid.') AND (e.taxauthid = 1) ';
			}
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retCnt = $r->cnt;
			}
			$rs->free();
		}
		return $retCnt;
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

	//Attribute review functions
	public function getReviewUrls($traitID, $reviewUid, $reviewDate, $reviewStatus, $start){
		$retArr = array();
		//Some sanitation
		if($reviewUid && !is_numeric($reviewUid)) return false;
		if($reviewStatus && !is_numeric($reviewStatus)) return false;
		if($reviewDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$reviewDate)) return false;
		if(is_numeric($traitID) && $this->collid){
			$targetOccid = 0;
			//$traitID is required
			$sql1 = 'SELECT DISTINCT o.occid, IFNULL(o.catalognumber, o.othercatalognumbers) AS catnum '.
				$this->getReviewSqlBase($traitID, $reviewUid, $reviewDate, $reviewStatus).' LIMIT '.$start.',1';
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$targetOccid = $r1->occid;
				$retArr[$r1->occid]['catnum'] = $r1->catnum;
			}
			$rs1->free();
			//Get images for target occid (isolation query into separate statements returns all images where there are multiples per specimen) 
			$sql = 'SELECT imgid, url, originalurl, occid FROM images WHERE (occid = '.$targetOccid.')';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			$cnt = 1;
			while($r = $rs->fetch_object()){
				$retArr[$r->occid][$cnt]['web'] = $r->url;
				$retArr[$r->occid][$cnt]['lg'] = $r->originalurl;
				$cnt++;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function getReviewCount($traitID, $reviewUid, $reviewDate, $reviewStatus){
		$cnt = 0;
		//Some sanitation
		if($reviewUid && !is_numeric($reviewUid)) return false;
		if($reviewStatus && !is_numeric($reviewStatus)) return false;
		if($reviewDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$reviewDate)) return false;
		if(is_numeric($traitID) && $this->collid){
			//$traitID is required
			$sql = 'SELECT COUNT(DISTINCT o.occid) as cnt '.
				$this->getReviewSqlBase($traitID, $reviewUid, $reviewDate, $reviewStatus);
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	private function getReviewSqlBase($traitID, $reviewUid, $reviewDate, $reviewStatus){
		$sqlFrag = 'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'INNER JOIN tmattributes a ON i.occid = a.occid '.
			'INNER JOIN tmstates s ON a.stateid = s.stateid '. 
			'WHERE (s.traitid = '.$traitID.') AND (o.collid = '.$this->collid.') ';
		if($reviewUid){
			$sqlFrag .= 'AND (a.createduid = '.$reviewUid.') ';
		}
		if($reviewDate){
			$sqlFrag .= 'AND (date(a.initialtimestamp) = "'.$reviewDate.'") ';
		}
		if($reviewStatus){
			$sqlFrag .= 'AND (a.statuscode = '.$reviewStatus.') ';
		}
		else{
			$sqlFrag .= 'AND (a.statuscode IS NULL OR a.statuscode = 0) ';
		}
		return $sqlFrag;
	}

	public function getCodedAttribute($traitID,$occid){
		$retArr = array();
		//Some sanitation
		if(is_numeric($traitID) && is_numeric($occid)){
			//$traitID and $occid are required
			$sql = 'SELECT a.stateid, a.notes FROM tmattributes a INNER JOIN tmstates s ON a.stateid = s.stateid '.
				'WHERE a.occid = '.$occid.' AND s.traitid = '.$traitID;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = $r->stateid;
				$retArr['notes'] = $r->notes;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function saveReviewStatus($traitID, $targetOccid,$setStatus,$addArr,$delArr,$notes){
		$status = false;
		if(is_numeric($traitID) && is_numeric($targetOccid) && is_numeric($setStatus)){
			if($addArr){
				foreach($addArr as $id){
					if(is_numeric($id)){
						$sql = 'INSERT INTO tmattributes(stateid,occid,createduid) VALUES('.$id.','.$targetOccid.','.$GLOBALS['SYMB_UID'].') ';
						if(!$this->conn->query($sql)){
							$this->errorMessage = 'ERROR addin occurrence attribute: '.$this->conn->error;
							$status = false;
						}
					}
				}
			} 
			if($delArr){
				foreach($delArr as $id){
					if(is_numeric($id)){
						$sql = 'DELETE FROM tmattributes WHERE stateid = '.$id.' AND occid = '.$targetOccid;
						if(!$this->conn->query($sql)){
							$this->errorMessage = 'ERROR removing occurrence attribute: '.$this->conn->error;
							$status = false;
						}
					}
				}
			} 
			
			$sql = 'UPDATE tmattributes a INNER JOIN tmstates s ON a.stateid = s.stateid '.
				'SET a.statuscode = '.$setStatus.', a.notes = "'.$this->cleanInStr($notes).'" '.
				'WHERE a.occid = '.$targetOccid.' AND s.traitid = '.$traitID;
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR updating occurrence attribute review status: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	public function getEditorArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT u.uid, u.lastname, u.firstname, l.username '.
			'FROM tmattributes a INNER JOIN users u ON a.createduid = u.uid '.
			'INNER JOIN userlogin l ON u.uid = l.uid '.
			'INNER JOIN omoccurrences o ON a.occid = o.occid '.
			'WHERE o.collid = '.$this->collid.' ORDER BY u.lastname, u.firstname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $r->lastname.($r->firstname?', '.$r->firstname:'').' ('.$r->username.')';
		}
		$rs->free();
		return $retArr;
	}
	
	public function getEditDates(){
		$retArr = array();
		$sql = 'SELECT DISTINCT DATE(a.initialtimestamp) as d '.
			'FROM tmattributes a INNER JOIN omoccurrences o ON a.occid = o.occid '.
			'WHERE o.collid = '.$this->collid.' ORDER BY a.initialtimestamp DESC';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->d;
		}
		$rs->free();
		return $retArr;
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

	public function submitBatchAttributes($collid, $stateID, $fieldName, $fieldValue, $notes, $uid){
		if(!is_numeric($collid) || !is_numeric($stateID) || !is_numeric($uid)){
			$this->errorMessage = 'ERROR saving occurrence attribute: bad input values';
			return false;
		}
		$sql = 'INSERT INTO tmattributes(stateid,occid,notes,createduid) '.
			'SELECT "'.$stateID.'", occid, "'.$this->cleanInStr($notes).'", '.$uid.' FROM omoccurrences '.
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