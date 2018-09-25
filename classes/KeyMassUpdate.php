<?php
include_once($SERVER_ROOT.'/classes/KeyManager.php');

class KeyMassUpdate extends KeyManager{

	private $clid;
	private $cid;
	private $taxaArr = array();
	private $stateArr = array();
	private $descrArr = array();
	private $headerStr;
	private $cnt = 0;

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getCharList($tidFilter){
		$retArr = Array();
		$sql = 'SELECT DISTINCT ch.headingname, c.CID, c.CharName '.
			'FROM kmcharacters c INNER JOIN kmchartaxalink ctl ON c.CID = ctl.CID '.
			'INNER JOIN kmcharheading ch ON c.hid = ch.hid '.
			'LEFT JOIN kmchardependance cd ON c.CID = cd.CID '.
			'WHERE ch.language = "'.$this->language.'" AND (c.chartype="UM" OR c.chartype="OM") AND (c.defaultlang="'.$this->language.'") ';
		$strFrag = '';
		if($tidFilter && is_numeric($tidFilter)){
			$strFrag = implode(',',$this->getParentArr($tidFilter)).','.$tidFilter;
		}
		else{
			$parTidArr = $this->getChecklistParentArr();
			if($parTidArr) $strFrag = implode(',',$parTidArr);
		}
		if($strFrag){
			$sql .= 'AND (ctl.TID In ('.$strFrag.') AND ctl.relation = "include") '.
				'AND (c.cid NOT In(SELECT DISTINCT CID FROM kmchartaxalink WHERE (TID In ('.$strFrag.') AND relation = "exclude"))) ';
		}
		$sql .= 'ORDER BY c.hid, c.SortSequence, c.CharName';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->headingname][$r->CID] = $r->CharName;
		}
		$rs->free();
		return $retArr;
	}

	private function getChecklistParentArr(){
		$retArr = Array();
		$sql = 'SELECT DISTINCT parenttid '.
			'FROM fmchklsttaxalink c INNER JOIN taxaenumtree e ON c.tid = e.tid '.
			'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (c.clid = '.$this->clid.')';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->parenttid;
		}
		$rs->free();
		return $retArr;
	}

	private function setStates(){
		$sql = 'SELECT charstatename, cs FROM kmcs WHERE (cid = '.$this->cid.') ';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$this->stateArr[$row->cs] = $row->charstatename;
		}
		$rs->free();
		ksort($this->stateArr);
	}

	public function echoTaxaList($tidFilter, $generaOnly = false){
		$tidArr = Array();
		if(!is_numeric($tidFilter)) $tidFilter = 0;
		//Get taxonomic hierarchy limits
		$tidLimitArr = array();
		$sql = 'SELECT tid, relation FROM kmchartaxalink WHERE (cid = '.$this->cid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidLimitArr[$r->relation][] = $r->tid;
		}
		$rs->free();

		//Get accepted taxa
		$sql = 'SELECT DISTINCT ts.tidaccepted FROM taxstatus ts INNER JOIN fmchklsttaxalink c ON ts.tid = c.tid ';
		$sqlWhere = 'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (c.clid = '.$this->clid.') ';
		if(array_key_exists('include', $tidLimitArr)){
			$sql .= 'INNER JOIN taxaenumtree e1 ON ts.tid = e1.tid ';
			$sqlWhere .= ' AND (e1.taxauthid = '.$this->taxAuthId.') AND (e1.parenttid IN('.implode(',',$tidLimitArr['include']).')) ';
		}
		if($tidFilter){
			$sql .= 'INNER JOIN taxaenumtree e2 ON ts.tid = e2.tid ';
			$sqlWhere .= ' AND (e2.taxauthid = '.$this->taxAuthId.') AND (e2.parenttid = '.$tidFilter.') ';
		}
		$sql .= $sqlWhere;
		//echo $sql.'<br/>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidArr[$r->tidaccepted] = $r->tidaccepted;
		}
		$rs->free();
		if(array_key_exists('exclude', $tidLimitArr)){
			$sql = 'SELECT DISTINCT ts.tid '.
				'FROM taxstatus ts INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN fmchklsttaxalink c ON ts2.tid = c.tid '.
				'INNER JOIN taxaenumtree e ON ts.tid = e.tid '.
				'WHERE (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (c.clid = 1) AND (e.taxauthid = 1) AND (e.parenttid IN('.implode(',',$tidLimitArr['exclude']).'))';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				unset($tidArr[$r->tid]);
			}
			$rs->free();
		}
		if($tidArr){
			//Get parents
			$sql2 = 'SELECT DISTINCT t.tid, t.sciname, ts.parenttid, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'LEFT JOIN taxaenumtree e ON t.tid = e.parenttid '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (e.taxauthid = '.$this->taxAuthId.' OR e.taxauthid IS NULL) AND (ts.tid = ts.tidaccepted) '.
				'AND (e.tid IN('.implode(',',$tidArr).') OR t.tid IN('.implode(',',$tidArr).')) ';
			if($generaOnly) $sql2 .= 'AND (t.rankid BETWEEN 140 AND 180) ';
			else  $sql2 .= 'AND (t.rankid BETWEEN 140 AND 220) ';
			$sql2 .= 'ORDER BY t.sciname';
			//echo $sql2.'<br/>';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$pTid = $r2->parenttid;
				if($r2->rankid == 140) $pTid = 'p'.$r2->tid;
				$this->taxaArr[$pTid][$r2->tid] = $r2->sciname;
				$tidArr[$r2->tid] = $r2->tid;
			}
			$rs2->free();

			//Get descriptions
			$sql3 = 'SELECT tid, cid, cs, inherited FROM kmdescr WHERE (cid='.$this->cid.') AND (tid IN('.implode(',',$tidArr).'))';
			$rs3 = $this->conn->query($sql3);
			while($r3 = $rs3->fetch_object()){
				$this->descrArr[$r3->tid][$r3->cs] = ($r3->inherited?1:0);
			}
			$rs3->free();

			//Create and output header
			$this->setStates();
			$this->headerStr = '<tr><th><b><span style="font-size:120%;">'.$this->getCharacterName().':</span></b></th>';
			foreach($this->stateArr as $cs => $csName){
				$this->headerStr .= '<th>'.str_replace(" ","<br/>",$csName).'</th>';
			}
			$this->headerStr .= '</tr>'."\n";
			$this->headerStr .= '<tr><td align="right" colspan="'.(count($this->stateArr)+1).'"><input type="submit" name="action" value="Save Changes" onclick="submitAttrs()" /></td></tr>';
			echo $this->headerStr;
			foreach($this->taxaArr as $parentTid => $tArr){
				if(!in_array($parentTid, $tidArr)){
					$this->processTaxa($parentTid);
				}
			}
			echo $this->headerStr;
		}
	}

	private function processTaxa($tid,$indent=0){
		if(isset($this->taxaArr[$tid])){
			$indent++;
			$childArr = $this->taxaArr[$tid];
			asort($childArr);
			foreach($childArr as $childTid => $childSciname){
				$this->echoTaxaRow($childTid,$childSciname,$indent);
				$this->processTaxa($childTid,$indent);
			}
		}
	}

	private function echoTaxaRow($tid,$sciname,$indent = 0){
		if(!is_numeric($indent)) $indent = 0;
		if(is_numeric($tid)){
			echo '<tr><td>';
			echo '<span style="margin-left:'.($indent*10).'px"><b>'.($indent?'<i>':'').htmlspecialchars($sciname, ENT_QUOTES, 'UTF-8').($indent?'</i>':'').'</b></span>';
			echo '<a href="editor.php?tid='.$tid.'" target="_blank"> <img src="../../images/edit.png" /></a>';
			echo '</td>';
			foreach($this->stateArr as $cs => $csName){
				$isSelected = false;
				$isInherited = false;
				if(isset($this->descrArr[$tid][$cs])){
					$isSelected = true;
					if($this->descrArr[$tid][$cs]) $isInherited = true;
				}
				if($isSelected && !$isInherited){
					//State is true and not inherited for this taxon
					$jsStr = "removeAttr('".$tid."-".$cs."');";
				}
				else{
					//State is false for this taxon or it is inherited
					$jsStr = "addAttr('".$tid."-".$cs."');";
				}
				echo '<td align="center" style="width:15px;white-space:nowrap;">';
				echo '<input type="checkbox" name="csDisplay" onclick="'.$jsStr.'" '.($isSelected && !$isInherited?'CHECKED':'').' title="'.$csName.'"/>'.($isInherited?'(I)':'');
				echo "</td>\n";
			}
			echo '</tr>';
			$this->cnt++;
			if($this->cnt%40 == 0) echo $this->headerStr;
		}
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

	//Misc functions
	public function getTaxaQueryList(){
		$retArr = Array();
		$sql = 'SELECT DISTINCT t.tid, t.sciname '.
			'FROM fmchklsttaxalink c INNER JOIN taxaenumtree e ON c.tid = e.tid '.
			'INNER JOIN taxa t ON e.parenttid = t.tid '.
			'WHERE (c.clid = '.$this->clid.') AND (t.rankid < 180) AND (e.taxauthid = 1) '.
			'ORDER BY t.sciname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}

	public function getCharacterName(){
		$retStr = '';
		if($this->cid){
			$sql = 'SELECT charname FROM kmcharacters WHERE (cid = '.$this->cid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retStr = $r->charname;
			}
			$rs->free();
		}
		return $retStr;
	}

	//Setter and getters
	public function setCid($cid){
		if(is_numeric($cid)) $this->cid = $cid;
	}

	public function setClid($clid){
		if(is_numeric($clid)) $this->clid = $clid;
	}
}
?>