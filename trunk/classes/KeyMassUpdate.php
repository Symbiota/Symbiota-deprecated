<?php
include_once($serverRoot.'/classes/KeyManager.php');

class KeyMassUpdate extends KeyManager{
	
	private $cid;
	
	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getCharList($tidFilter){
		$headingArray = Array();		//Heading => Array(CID => CharName)
		$sql = "SELECT DISTINCT ch.headingname, c.CID, c.CharName ".
			"FROM kmcharacters c INNER JOIN kmchartaxalink ctl ON c.CID = ctl.CID ".
			"INNER JOIN kmcharheading ch ON c.hid = ch.hid ".
			"LEFT JOIN kmchardependance cd ON c.CID = cd.CID ".
			"WHERE ch.language = '".$this->language."' AND (ctl.Relation = 'include') ".
			"AND (c.chartype='UM' OR c.chartype='OM') AND (c.defaultlang='".$this->language."') ";
		if($tidFilter){
			$strFrag = implode(',',$this->getParentArr($tidFilter)).','.$tidFilter;
			$sql .= 'AND (ctl.TID In ('.$strFrag.')) ';
		}
		$sql .= 'ORDER BY c.hid, c.SortSequence, c.CharName';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$headingArray[$row->headingname][$row->CID] = $row->CharName;
		}
		$result->free();
		return $headingArray;
	}
	
	public function getStates(){
		$stateArr = Array();
		$sql = 'SELECT kmcs.CharStateName, kmcs.CS FROM kmcs '.
			'WHERE (kmcs.CID = '.$this->cid.') ';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$stateArr[$row->CS] = $row->CharStateName;
		}
		$rs->free();
		ksort($stateArr);
		return $stateArr;
	}

	public function getTaxaList($clidFilter, $tidFilter, $generaOnly = false){
		//Get all Taxa found in checklist 
		$retArr = Array();
		$tidArr = Array();
		$sqlBase = '';
		$sqlWhere = '';
		if($clidFilter){
			$sqlBase .= 'INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid ';
			$sqlWhere .= 'AND (ctl.CLID = '.$clidFilter.') ' ;
		}
		if($tidFilter){
			$sqlBase .= 'INNER JOIN taxaenumtree e ON ts.tid = e.tid ';
			$sqlWhere .= 'AND (e.taxauthid = '.$this->taxAuthId.') AND (e.parenttid = '.$tidFilter.') ';
		}
		//Get accepted taxa
		if(!$generaOnly){
			$sql = 'SELECT DISTINCT t.tid, ts.family, t.sciname, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.$sqlBase.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') '.$sqlWhere;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->rankid == 220){
					$tidArr[] = $r->tid;
					$retArr[$r->family][$r->sciname] = $r->tid;
				}
			}
			$rs->free();
		}
		
		//Get parents
		$sql = 'SELECT DISTINCT t.tid, ts.family, t.sciname, t.rankid '.
			'FROM taxa t INNER JOIN taxaenumtree et ON t.tid = et.parenttid '.
			'INNER JOIN taxstatus ts ON et.tid = ts.tidaccepted '.$sqlBase.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (t.rankid >= 140) AND (t.rankid <= 220) '.$sqlWhere;
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->rankid < 220 || !$generaOnly){
				$tidArr[] = $r->tid;
				$family = $r->family;
				if($r->rankid == 140 && !$family) $family = $r->sciname;
				$retArr[$family][$r->sciname] = $r->tid;
			}
		}
		$rs->free();

		//Get descriptions
		if($tidArr){
			$sql = 'SELECT tid, cid, cs, inherited FROM kmdescr '.
				'WHERE (cid='.$this->cid.') AND (tid IN('.implode(',',$tidArr).'))';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$i = 0;
				if($r->inherited) $i = 1;
				$retArr['cs'][$r->tid][$r->cs] = $i;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function processAttributes($rAttrs,$aAttrs){
		$removeArr = $this->processAttrArr($rAttrs);
		$addArr = $this->processAttrArr($aAttrs);
		$tidUsedStr = implode(',',array_unique(array_merge(array_keys($removeArr),array_keys($addArr))));
		if($tidUsedStr){
			$this->deleteInheritance($tidUsedStr,$this->cid);
			if($removeArr) $this->processRemoveAttributes($removeArr);
			if($addArr) $this->processAddAttributes($addArr);
			$this->resetInheritance($tidUsedStr,$this->cid);
		}
	}
	
	private function processAttrArr($inputArr){
		$retArr = array();
		if($inputArr){
			foreach($inputArr as $v){
	 			if($v){
					$t = explode("-",$v);
					$retArr[$t[0]][] = $t[1];
	 			}
	 		}
		}
 		return $retArr;
	}

	private function processRemoveAttributes($inputArr){
 		foreach($inputArr as $tid => $csArr){
 			foreach($csArr as $cs){
				$this->deleteDescr($tid, $this->cid, $cs);
 			}
 		}
	}

	private function processAddAttributes($addArr){
 		foreach($addArr as $tid => $csArr){
 			foreach($csArr as $cs){
				$this->insertDescr($tid, $this->cid, $cs);
 			}
 		}
	}

	//Setter and getters
	public function getClQueryList($pid){
		$retList = Array();
		$sql = 'SELECT cl.clid, cl.name, cl.access '.
			'FROM fmchecklists cl INNER JOIN fmchklstprojlink cpl ON cl.clid = cpl.clid ';
		if($pid) {
			$sql .= 'WHERE (cpl.pid = '.$pid.') AND (cl.access = "public" ';
		}
		else{
			$sql .= 'WHERE (cl.access = "public" ';
		}
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin'])){
			$sql .= 'OR cl.clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).')';
		}
		$sql .= ') ORDER BY cl.name';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->name) $retList[$r->clid] = $r->name.($r->access == 'private'?' (private)':'');
		}
		$rs->free();
		return $retList;
	}

	public function getTaxaQueryList(){
		$retArr = Array();
		$sql = 'SELECT t.tid, t.sciname '. 
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (t.rankid >= 140) AND (t.rankid <= 180) AND (ts.tid = ts.tidaccepted) AND (ts.taxauthid = 1) '.
			'ORDER BY t.rankid, t.sciname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}

	public function setCid($cid){
		if(is_numeric($cid)) $this->cid = $cid;
	}
}
?>