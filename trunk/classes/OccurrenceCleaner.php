<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceCleaner {

	private $conn;
	private $collId;

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

	public function getCollMap(){
		$returnArr = Array();
		if($this->collId){
			$sql = 'SELECT c.institutioncode, c.collectioncode, c.collectionname, '.
				'c.icon, c.colltype, c.managementtype '.
				'FROM omcollections c '.
				'WHERE (c.collid = '.$this->collId.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['institutioncode'] = $row->institutioncode;
				$returnArr['collectioncode'] = $row->collectioncode;
				$returnArr['collectionname'] = $this->cleanOutStr($row->collectionname);
				$returnArr['icon'] = $row->icon;
				$returnArr['colltype'] = $row->colltype;
				$returnArr['managementtype'] = $row->managementtype;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function getDuplicateRecords(){
		$returnArr = array();
		$sql = 'SELECT o.occid, o.catalognumber, o.family, o.sciname, o.recordedBy, o.recordNumber, o.associatedCollectors, '.
			'o.eventDate, o.verbatimEventDate, o.country, o.stateProvince, o.county, o.municipality, o.locality '.
			'FROM omoccurrences o INNER JOIN (SELECT catalognumber FROM omoccurrences GROUP BY catalognumber, collid '. 
			'HAVING Count(*)>1 AND collid = '.$this->collId.' AND catalognumber IS NOT NULL) rt ON o.catalognumber = rt.catalognumber '.
			'WHERE o.collid = '.$this->collId.' ORDER BY o.catalognumber LIMIT 100';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->occid]['occid'] = $row->occid;
			$returnArr[$row->occid]['catalognumber'] = $row->catalognumber;
			$returnArr[$row->occid]['family'] = $row->family;
			$returnArr[$row->occid]['sciname'] = $row->sciname;
			$returnArr[$row->occid]['recordedBy'] = $row->recordedBy;
			$returnArr[$row->occid]['recordNumber'] = $row->recordNumber;
			$returnArr[$row->occid]['associatedCollectors'] = $row->associatedCollectors;
			$returnArr[$row->occid]['eventDate'] = $row->eventDate;
			$returnArr[$row->occid]['verbatimEventDate'] = $row->verbatimEventDate;
			$returnArr[$row->occid]['country'] = $row->country;
			$returnArr[$row->occid]['stateProvince'] = $row->stateProvince;
			$returnArr[$row->occid]['county'] = $row->county;
			$returnArr[$row->occid]['municipality'] = $row->municipality;
			$returnArr[$row->occid]['locality'] = $row->locality;
		}
		$rs->free();
		return $returnArr;
	}
	
	public function mergeDupeArr($occidArr){
		$dupArr = array();
		foreach($occidArr as $v){
			$vArr = explode(':',$v);
			$dupArr[$vArr[0]][] = $vArr[1];
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

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>