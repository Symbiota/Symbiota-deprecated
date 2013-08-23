<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceCleaner {

	private $conn;
	private $collId;
	private $obsUid;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setCollId($collId){
		if(is_numeric($collId)){
			$this->collId = $collId;
		}
	}

	public function setObsuid($obsUid){
		if(is_numeric($obsUid)){
			$this->obsUid = $obsUid;
		}
	}

	public function getCollMap(){
		$returnArr = Array();
		if($this->collId){
			$sql = 'SELECT CONCAT_WS("-",c.institutioncode, c.collectioncode) AS code, c.collectionname, '.
				'c.icon, c.colltype, c.managementtype '.
				'FROM omcollections c '.
				'WHERE (c.collid = '.$this->collId.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['code'] = $row->code;
				$returnArr['collectionname'] = $row->collectionname;
				$returnArr['icon'] = $row->icon;
				$returnArr['colltype'] = $row->colltype;
				$returnArr['managementtype'] = $row->managementtype;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function getDuplicateCatalogNumber(){
		$sql = 'SELECT o.catalognumber AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, o.recordedby, o.recordnumber, o.associatedcollectors, '.
			'o.eventdate, o.verbatimeventdate, o.country, o.stateprovince, o.county, o.municipality, o.locality, o.datelastmodified '.
			'FROM omoccurrences o INNER JOIN (SELECT catalognumber FROM omoccurrences GROUP BY catalognumber, collid '.($this->obsUid?', observeruid ':''). 
			'HAVING Count(*)>1 AND collid = '.$this->collId.($this->obsUid?' AND observeruid = '.$this->obsUid:'').' AND catalognumber IS NOT NULL) rt ON o.catalognumber = rt.catalognumber '.
			'WHERE o.collid = '.$this->collId.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' '.
			'ORDER BY o.catalognumber, o.datelastmodified DESC LIMIT 505';
		//echo $sql;
		$retArr = $this->getDuplicates($sql); 

		return $retArr;
	}
	
	public function getDuplicateCollectorNumber($lastName = ''){
		$retArr = array();
		$sql = 'SELECT o.occid, o.eventdate, recordedby, o.recordnumber '.
			'FROM omoccurrences o INNER JOIN '. 
			'(SELECT eventdate, recordnumber FROM omoccurrences GROUP BY eventdate, recordnumber, collid '.
			'HAVING Count(*)>1 AND collid = '.$this->collId.($this->obsUid?' AND observeruid = '.$this->obsUid:'').' AND eventdate IS NOT NULL AND recordnumber IS NOT NULL '.
			'AND recordnumber NOT IN("sn","s.n.","Not Provided","unknown")) intab ON o.eventdate = intab.eventdate AND o.recordnumber = intab.recordnumber '.
			'WHERE collid = '.$this->collId.($this->obsUid?' AND observeruid = '.$this->obsUid:'').' ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		$collArr = array();
		while($r = $rs->fetch_object()){
			$nameArr = $this->parseCollectorName($r->recordedby);
			if(isset($nameArr['last']) && $nameArr['last'] && strlen($nameArr['last']) > 2){
				$lastName = $nameArr['last'];
				$collArr[$r->eventdate][$r->recordnumber][$lastName][] = $r->occid;
			}
		}
		$rs->free();
		
		//Collection duplicate clusters
		$occidArr = array();
		$cnt = 0;
		foreach($collArr as $ed => $arr1){
			foreach($arr1 as $rn => $arr2){
				foreach($arr2 as $ln => $dupArr){
					if(count($dupArr) > 1){
						$sql = 'SELECT '.$cnt.' AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.othercatalognumbers, o.family, o.sciname, o.recordedby, o.recordnumber, '.
							'o.associatedcollectors, o.eventdate, o.verbatimeventdate, o.country, o.stateprovince, o.county, o.municipality, o.locality, datelastmodified '. 
							'FROM omoccurrences o '.
							'WHERE occid IN('.implode(',',$dupArr).') ';
						//echo $sql;
						$retArr = array_merge($retArr,$this->getDuplicates($sql)); 
						$cnt++;
						if($cnt > 200) break 3; 
					}
				}
			}
		}
					
		
		return $retArr;
	}

	private function getDuplicates($sql){
		$retArr = array();
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_assoc()){
			$retArr[$row['dupid']][$row['occid']] = array_change_key_case($row);
		}
		$rs->free();
		return $retArr;
	}
	
	public function mergeDupeArr($occidArr){
		$dupArr = array();
		foreach($occidArr as $v){
			$vArr = explode(':',$v);
			$dupArr[strtoupper($vArr[0])][] = $vArr[1];
		}
		foreach($dupArr as $catNum => $occArr){
			if(count($occArr) > 1){
				$targetOccid = array_shift($occArr);
				$statusStr = $targetOccid;
				foreach($occArr as $sourceOccid){
					$this->mergeRecords($targetOccid,$sourceOccid);
					$statusStr .= ', '.$sourceOccid;
				}
				echo '<li>Merging records: '.$statusStr.'</li>';
			}
			else{
				echo '<li>Record # '.array_shift($occArr).' skipped because only one record was selected</li>';
			}
		}
	}
	
	public function mergeRecords($targetOccid,$sourceOccid){
		if(!$targetOccid || !$sourceOccid) return 'ERROR: target or source is null';
		if($targetOccid == $sourceOccid) return 'ERROR: target and source are equal';
		$status = true;

		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array_change_key_case($r);
			$id = $tempArr['occid'];
			unset($tempArr['occid']);
			unset($tempArr['collid']);
			unset($tempArr['dbpk']);
			unset($tempArr['datelastmodified']);
			$oArr[$id] = $tempArr;
		}
		$rs->free();

		$tArr = $oArr[$targetOccid];
		$sArr = $oArr[$sourceOccid];
		$sqlFrag = '';
		foreach($sArr as $k => $v){
			if(($v != '') && $tArr[$k] == ''){
				$sqlFrag .= ','.$k.'="'.$v.'"';
			} 
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			$this->conn->query($sqlIns);
		}

		//Remap determinations
		$sql = 'UPDATE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete occurrence edits
		$sql = 'DELETE FROM omoccuredits WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap survey lists
		$sql = 'UPDATE omsurveyoccurlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete source
		$sql = 'DELETE FROM omoccurrences WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$status .= '<li><span style="color:red">ERROR:</span> unable to delete occurrence record #'.$sourceOccid.
			': '.$this->conn->error.'</li>';
		}
		return $status;
	}

	public function indexCollectors(){
		//Try to populate using already linked names 
		$sql = 'UPDATE omoccurrences o1 INNER JOIN (SELECT DISTINCT recordedbyid, recordedby FROM omoccurrences WHERE recordedbyid IS NOT NULL) o2 ON o1.recordedby = o2.recordedby '.
			'SET o1.recordedbyid = o2.recordedbyid '.
			'WHERE o1.recordedbyid IS NULL';
		$this->conn->query($sql); 
		
		//Query unlinked specimens and try to parse each collector
		$collArr = array();
		$sql = 'SELECT occid, recordedby '.
			'FROM omoccurrences '.
			'WHERE recordedbyid IS NULL';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$collArr[$r->recordedby][] = $r->occid;
		}
		$rs->close();
		
		foreach($collArr as $collStr => $occidArr){
			$collArr = $this->parseCollectorName($collStr);
			//Check to make sure collector is not already in system 
			$sql = 'SELECT recordedbyid '.
				'FROM omcollectors '.
				'WHERE familyname = "'.$collArr['last'].'" AND firstname = "'.$collArr['first'].'" AND middlename = "'.$collArr['middle'].'"';
			$rs = $this->conn->query($sql);
			$recById = 0; 
			if($r = $rs->fetch_object()){
				$recById = $r->recordedbyid;
			}
			else{
				//Not in system, thus load and get PK
				$sql = 'INSERT omcollectors(familyname, firstname, middlename) '.
					'VALUES("'.$collArr['last'].'","'.$collArr['first'].'","'.$collArr['middle'].'")';
				$this->conn->query($sql);
				$recById = $this->conn->insert_id;
			}
			$rs->close();
			//Add recordedbyid to omoccurrence table
			if($recById){
				$sql = 'UPDATE omoccurrences '.
					'SET recordedbyid = '.$recById.
					' WHERE occid IN('.implode(',',$occidArr).') AND recordedbyid IS NULL ';
				$this->conn->query($sql);
			}
		}
	}
	
	private function parseCollectorName($inStr){
		$name = array();
		$primaryArr = '';
		$primaryArr = explode(';',$inStr);
		$primaryArr = explode('&',$primaryArr[0]);
		$primaryArr = explode(' and ',$primaryArr[0]);
		$lastNameArr = explode(',',$primaryArr[0]);
		if(count($lastNameArr) > 1){
			//formats: Last, F.I.; Last, First I.; Last, First Initial Last
			$name['last'] = $lastNameArr[0];
			if($pos = strpos($lastNameArr[1],' ')){
				$name['first'] = substr($lastNameArr[1],0,$pos);
				$name['middle'] = substr($lastNameArr[1],$pos);
			}
			elseif($pos = strpos($lastNameArr[1],'.')){
				$name['first'] = substr($lastNameArr[1],0,$pos);
				$name['middle'] = substr($lastNameArr[1],$pos);
			}
			else{
				$name['first'] = $lastNameArr[1];
			}
		}
		else{
			//Formats: F.I. Last; First I. Last; First Initial Last
			$tempArr = explode(' ',$lastNameArr[0]);
			$name['last'] = array_pop($tempArr);
			if($tempArr){
				$arrCnt = count($tempArr);
				if($arrCnt == 1){
					if(preg_match('/(\D+\.+)(\D+\.+)/',$tempArr[0],$m)){
						$name['first'] = $m[1];
						$name['middle'] = $m[2];
					}
					else{
						$name['first'] = $tempArr[0];
					}
				}
				elseif($arrCnt == 2){
					$name['first'] = $tempArr[0];
					$name['middle'] = $tempArr[1];
				}
				else{
					$name['first'] = implode(' ',$tempArr);
				}
			}
		}
		return $name;
	}

	private function cleanInStr($str){
		return $this->conn->real_escape_string(trim($str));
	}
}
?>