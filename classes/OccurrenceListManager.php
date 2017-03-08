<?php
include_once("OccurrenceManager.php");

class OccurrenceListManager extends OccurrenceManager{

	protected $recordCount = 0;
	protected $sortField1 = '';
	protected $sortField2 = '';
	protected $sortOrder = '';
	
 	public function __construct(){
 		parent::__construct();
 	}

	public function __destruct(){
 		parent::__destruct();
	}

    public function getRecordArr($pageRequest,$cntPerPage){
        $canReadRareSpp = false;
        if($GLOBALS['USER_RIGHTS']){
            if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
                $canReadRareSpp = true;
            }
        }
        $returnArr = Array();
        $imageSearchArr = Array();
        $sqlWhere = $this->getSqlWhere();
        if(!$this->recordCount || $this->reset){
            $this->setRecordCnt($sqlWhere);
        }
        $sql = 'SELECT DISTINCT o.occid, c.CollID, c.institutioncode, c.collectioncode, c.collectionname, c.icon, '.
            'CONCAT_WS(":",c.institutioncode, c.collectioncode) AS collection, '.
            'IFNULL(o.CatalogNumber,"") AS catalognumber, o.family, o.sciname, o.tidinterpreted, '.
            'CONCAT_WS(" to ",IFNULL(DATE_FORMAT(o.eventDate,"%d %M %Y"),""),DATE_FORMAT(MAKEDATE(o.year,o.endDayOfYear),"%d %M %Y")) AS date, '.
            'o.eventDate, IFNULL(o.country,"") AS country, IFNULL(o.StateProvince,"") AS state, IFNULL(o.county,"") AS county, '.
            'IFNULL(o.scientificNameAuthorship,"") AS author, IFNULL(o.recordedBy,"") AS recordedby, IFNULL(o.recordNumber,"") AS recordnumber, '.
            'o.eventDate, IFNULL(o.country,"") AS country, IFNULL(o.StateProvince,"") AS state, IFNULL(o.county,"") AS county, '.
            'CONCAT_WS(", ",o.locality,CONCAT(ROUND(o.decimallatitude,5)," ",ROUND(o.decimallongitude,5))) AS locality, '.
            'IFNULL(o.LocalitySecurity,0) AS LocalitySecurity, o.localitysecurityreason, IFNULL(o.habitat,"") AS habitat, '.
            'CONCAT_WS("-",o.minimumElevationInMeters, o.maximumElevationInMeters) AS elev, o.observeruid '.
            'FROM omoccurrences AS o LEFT JOIN omcollections AS c ON o.collid = c.collid ';
        if(array_key_exists("clid",$this->searchTermsArr)) $sql .= 'LEFT JOIN fmvouchers AS v ON o.occid = v.occid ';
        if(array_key_exists("collector",$this->searchTermsArr)) $sql .= 'INNER JOIN omoccurrencesfulltext AS f ON o.occid = f.occid ';
        $sql .= $sqlWhere;
        if($this->sortField1 || $this->sortField2 || $this->sortOrder){
            $sortFields = array('Collection' => 'collection','Catalog Number' => 'o.CatalogNumber','Family' => 'o.family',
                'Scientific Name' => 'o.sciname','Collector' => 'o.recordedBy','Number' => 'o.recordNumber','Event Date' => 'o.eventDate',
                'Country' => 'o.country','State/Province' => 'o.StateProvince','County' => 'o.county','Elevation' => 'CAST(elev AS UNSIGNED)');
            if($this->sortField1) $this->sortField1 = $sortFields[$this->sortField1];
            if($this->sortField2) $this->sortField2 = $sortFields[$this->sortField2];
            $sql .= "ORDER BY ";
            if (!$canReadRareSpp) {
                $sql .= "LocalitySecurity ASC,";
            }
            $sql .= $this->sortField1.' '.$this->sortOrder.' ';
            if ($this->sortField2) {
                $sql .= ','.$this->sortField2.' '.$this->sortOrder.' ';
            }
        }
        else{
            $sql .= "ORDER BY c.sortseq, c.collectionname ";
            $pageRequest = ($pageRequest - 1)*$cntPerPage;
        }
        $sql .= "LIMIT ".$pageRequest.",".$cntPerPage;
        //echo "<div>Spec sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $occId = $row->occid;
            $returnArr[$occId]["collid"] = $row->CollID;
            $returnArr[$occId]["institutioncode"] = $row->institutioncode;
            $returnArr[$occId]["collectioncode"] = $row->collectioncode;
            $returnArr[$occId]["collectionname"] = $row->collectionname;
            $returnArr[$occId]["collicon"] = $row->icon;
            $returnArr[$occId]["accession"] = $row->catalognumber;
            $returnArr[$occId]["family"] = $this->cleanOutStr($row->family);
            $returnArr[$occId]["sciname"] = $this->cleanOutStr($row->sciname);
            $returnArr[$occId]["tid"] = $row->tidinterpreted;
            $returnArr[$occId]["author"] = $this->cleanOutStr($row->author);
            $returnArr[$occId]["collector"] = $this->cleanOutStr($row->recordedby);
            $returnArr[$occId]["country"] = $row->country;
            $returnArr[$occId]["state"] = $row->state;
            $returnArr[$occId]["county"] = $row->county;
            $returnArr[$occId]["observeruid"] = $row->observeruid;
            $localitySecurity = $row->LocalitySecurity;
            if(!$localitySecurity || $canReadRareSpp
                || (array_key_exists("CollEditor", $GLOBALS['USER_RIGHTS']) && in_array($collIdStr,$GLOBALS['USER_RIGHTS']["CollEditor"]))
                || (array_key_exists("RareSppReader", $GLOBALS['USER_RIGHTS']) && in_array($collIdStr,$GLOBALS['USER_RIGHTS']["RareSppReader"]))){
                $returnArr[$occId]["locality"] = str_replace('.,',',',$row->locality);
                $returnArr[$occId]["collnumber"] = $this->cleanOutStr($row->recordnumber);
                $returnArr[$occId]["habitat"] = $row->habitat;
                $returnArr[$occId]["date"] = $row->date;
                $returnArr[$occId]["eventDate"] = $row->eventDate;
                $returnArr[$occId]["elev"] = $row->elev;
                $imageSearchArr[] = $occId;
            }
            else{
                $securityStr = '<span style="color:red;">Detailed locality information protected. ';
                if($row->localitysecurityreason){
                    $securityStr .= $row->localitysecurityreason;
                }
                else{
                    $securityStr .= 'This is typically done to protect rare or threatened species localities.';
                }
                $returnArr[$occId]["locality"] = $securityStr.'</span>';
            }
        }
        $result->free();
        //Set images
        if($imageSearchArr){
            $sql = 'SELECT o.collid, o.occid, i.thumbnailurl '.
                'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
                'WHERE o.occid IN('.implode(',',$imageSearchArr).')';
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
			$sql = "SELECT COUNT(o.occid) AS cnt FROM omoccurrences o ";
			if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
			if(strpos($sqlWhere,'MATCH(f.recordedby)')) $sql .= "INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ";
			$sql .= $sqlWhere;
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->free();
		}
		setCookie("collvars","reccnt:".$this->recordCount,time()+64800,($GLOBALS['CLIENT_ROOT']?$GLOBALS['CLIENT_ROOT']:'/'));
	}

    public function getRecordCnt(){
		return $this->recordCount;
	}
	
	public function setSorting($sf1,$sf2,$so){
		$this->sortField1 = $sf1;
		$this->sortField2 = $sf2;
		$this->sortOrder = $so;
	}
	
	public function getCloseTaxaMatch($name){
		$retArr = array();
		$searchName = trim($name); 
		$sql = 'SELECT tid, sciname FROM taxa WHERE soundex(sciname) = soundex("'.$searchName.'") AND sciname != "'.$searchName.'"';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[] = $r->sciname;
			}
		}
		return $retArr;
	}
}
?>