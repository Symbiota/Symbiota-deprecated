<?php
include_once("OccurrenceManager.php");

class OccurrenceListManager extends OccurrenceManager{

	private $recordCount = 0;
	private $sortArr = array();

 	public function __construct(){
 		parent::__construct();
 	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getSpecimenMap($pageRequest,$cntPerPage){
		$returnArr = Array();
		$canReadRareSpp = false;
		if($GLOBALS['USER_RIGHTS']){
			if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
				$canReadRareSpp = true;
			}
		}

		$occArr = array();
		$sqlWhere = $this->getSqlWhere();
		if(!$this->recordCount || $this->reset){
			$this->setRecordCnt($sqlWhere);
		}
		$sql = 'SELECT DISTINCT o.occid, c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, '.
			'o.catalognumber, o.family, o.sciname, o.scientificnameauthorship, o.tidinterpreted, o.recordedby, o.recordnumber, o.eventdate, o.year, o.enddayofyear, '.
			'o.country, o.stateprovince, o.county, o.locality, o.decimallatitude, o.decimallongitude, o.localitysecurity, o.localitysecurityreason, '.
			'o.habitat, o.minimumelevationinmeters, o.maximumelevationinmeters, o.observeruid, c.sortseq '.
			'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ';
		/*
		$sql = 'SELECT DISTINCT o.occid, c.CollID, c.institutioncode, c.collectioncode, c.collectionname, c.icon, '.
			'CONCAT_WS(":",c.institutioncode, c.collectioncode) AS collection, '.
			'IFNULL(o.CatalogNumber,"") AS catalognumber, o.family, o.sciname, o.tidinterpreted, '.
			'CONCAT_WS(" to ",IFNULL(DATE_FORMAT(o.eventDate,"%d %M %Y"),""),DATE_FORMAT(MAKEDATE(o.year,o.endDayOfYear),"%d %M %Y")) AS date, '.
			'IFNULL(o.scientificNameAuthorship,"") AS author, IFNULL(o.recordedBy,"") AS recordedby, IFNULL(o.recordNumber,"") AS recordnumber, '.
			'o.eventDate, IFNULL(o.country,"") AS country, IFNULL(o.StateProvince,"") AS state, IFNULL(o.county,"") AS county, '.
			'CONCAT_WS(", ",o.locality,CONCAT(ROUND(o.decimallatitude,5)," ",ROUND(o.decimallongitude,5))) AS locality, '.
			'IFNULL(o.LocalitySecurity,0) AS LocalitySecurity, o.localitysecurityreason, '.
			'CONCAT_WS("-",o.minimumElevationInMeters, o.maximumElevationInMeters) AS elev, o.observeruid '.
			'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
		*/
		$sql .= $this->getTableJoins($sqlWhere).$sqlWhere;

		if($this->sortArr){
			$sql .= 'ORDER BY ';
			if(!$canReadRareSpp) $sql .= 'localitySecurity,';
			$sql .= implode(',',$this->sortArr);
		}
		else{
			$sql .= 'ORDER BY c.sortseq, c.collectionname ';
			$pageRequest = ($pageRequest - 1)*$cntPerPage;
		}
		$sql .= ' LIMIT '.$pageRequest.",".$cntPerPage;
		//echo "<div>Spec sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		if($result){
    		while($row = $result->fetch_object()){
    			$returnArr[$row->occid]['collid'] = $row->collid;
    			$returnArr[$row->occid]['instcode'] = $row->institutioncode;
    			$returnArr[$row->occid]['collcode'] = $row->collectioncode;
    			$returnArr[$row->occid]['collname'] = $row->collectionname;
    			$returnArr[$row->occid]['icon'] = $row->icon;
    			$returnArr[$row->occid]["catnum"] = $row->catalognumber;
    			$returnArr[$row->occid]["family"] = $row->family;
    			$returnArr[$row->occid]["sciname"] = $row->sciname;
    			$returnArr[$row->occid]["tid"] = $row->tidinterpreted;
    			$returnArr[$row->occid]["author"] = $this->cleanOutStr($row->scientificnameauthorship);
    			$returnArr[$row->occid]["collector"] = $row->recordedby;
    			$returnArr[$row->occid]["country"] = $row->country;
    			$returnArr[$row->occid]["state"] = $row->stateprovince;
    			$returnArr[$row->occid]["county"] = $row->county;
    			$returnArr[$row->occid]["obsuid"] = $row->observeruid;
    			if(!$row->localitysecurity|| $canReadRareSpp
    				|| (array_key_exists("CollEditor", $GLOBALS['USER_RIGHTS']) && in_array($row->collid,$GLOBALS['USER_RIGHTS']["CollEditor"]))
    				|| (array_key_exists("RareSppReader", $GLOBALS['USER_RIGHTS']) && in_array($row->collid,$GLOBALS['USER_RIGHTS']["RareSppReader"]))){
    					$locStr = str_replace('.,',',',$row->locality);
    					if($row->decimallatitude && $row->decimallongitude) $locStr .= ', '.$row->decimallatitude.' '.$row->decimallongitude;
    					$returnArr[$row->occid]["locality"] = trim($locStr,' ,;');
    					$returnArr[$row->occid]["collnum"] = $this->cleanOutStr($row->recordnumber);
    					$dateStr = date('d M Y',strtotime($row->eventdate));
    					if($row->enddayofyear && $row->year){
    						if($d = DateTime::createFromFormat('z Y', strval($row->enddayofyear).' '.strval($row->year))){
    							$dateStr .= ' to '.$d->format('d M Y');
    						}
    					}
    					$returnArr[$row->occid]["date"] = $dateStr;
    					$returnArr[$row->occid]["habitat"] = $row->habitat;
    					$elevStr = $row->minimumelevationinmeters;
    					if($row->maximumelevationinmeters) $elevStr .= ' - '.$row->maximumelevationinmeters;
    					$returnArr[$row->occid]["elev"] = $elevStr;
    					$occArr[] = $row->occid;
    			}
    			else{
    				$securityStr = '<span style="color:red;">Detailed locality information protected. ';
    				if($row->localitysecurityreason){
    					$securityStr .= $row->localitysecurityreason;
    				}
    				else{
    					$securityStr .= 'This is typically done to protect rare or threatened species localities.';
    				}
    				$returnArr[$row->occid]["locality"] = $securityStr.'</span>';
    			}
    		}
    		$result->free();
		}
		//Set images
		if($occArr){
			$sql = 'SELECT o.collid, o.occid, i.thumbnailurl '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE o.occid IN('.implode(',',$occArr).')';
			$rs = $this->conn->query($sql);
			$previousOccid = 0;
			while($r = $rs->fetch_object()){
				if($r->occid != $previousOccid) $returnArr[$r->occid]['img'] = $r->thumbnailurl;
				$previousOccid = $r->occid;
			}
			$rs->free();
		}
		return $returnArr;
	}

	private function setRecordCnt($sqlWhere){
		if($sqlWhere){
			$sql = "SELECT COUNT(o.occid) AS cnt FROM omoccurrences o ".$this->getTableJoins($sqlWhere).$sqlWhere;
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($result){
			    if($row = $result->fetch_object()){
    				$this->recordCount = $row->cnt;
			    }
			    $result->free();
			}
		}
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}

	public function addSort($field,$direction){
		$this->sortArr[] = trim($field.' '.$direction);
	}

	public function getCloseTaxaMatch($name){
		$retArr = array();
		$searchName = trim($name);
		$sql = 'SELECT tid, sciname FROM taxa WHERE soundex(sciname) = soundex("'.$searchName.'") AND sciname != "'.$searchName.'"';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[] = $r->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}
}
?>