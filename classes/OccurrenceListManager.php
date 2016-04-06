<?php
include_once("OccurrenceManager.php");
include_once('../config/symbini.php');

class OccurrenceListManager extends OccurrenceManager{

	protected $recordCount = 0;
	public $imageFlagIsActive = 0;
	
 	public function __construct(){
 		parent::__construct();
 	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getSpecimenMap($pageRequest,$cntPerPage){
		global $userRights;
		global $IMAGE_FLAG_IS_ACTIVE;

		$returnArr = Array();
		$sqlWhere = $this->getSqlWhere();
		if(!$this->recordCount || $this->reset){
			$this->setRecordCnt($sqlWhere);
		} // Changed VMS 3/30/2016
		$sql = 'SELECT o.occid, o.CollID, o.institutioncode, o.collectioncode, IFNULL( o.CatalogNumber,  "" ) AS catalognumber, ' .
		'o.family, o.sciname, o.tidinterpreted, IFNULL( o.scientificNameAuthorship,  "" ) AS author, IFNULL( o.recordedBy,  "" ) AS recordedby, ' .
		'IFNULL( o.recordNumber,  "" ) AS recordnumber, CONCAT_WS(  " to ", IFNULL( DATE_FORMAT( o.eventDate, "%d %M %Y" ) ,  "" ) , DATE_FORMAT( MAKEDATE( o.year, o.endDayOfYear ) ,  "%d %M %Y" ) ) AS DATE, ' .
		'IFNULL( o.country,  "" ) AS country, IFNULL( o.StateProvince,  "" ) AS state, IFNULL( o.county,  "" ) AS county,' .
		'CONCAT_WS(  ", ", o.locality, CONCAT( ROUND( o.decimallatitude, 5 ) ,  " ", ROUND( o.decimallongitude, 5 ) ) ) AS locality,' .
		'IFNULL( o.LocalitySecurity, 0 ) AS LocalitySecurity, o.localitysecurityreason, CONCAT_WS(  "-", o.minimumElevationInMeters, o.maximumElevationInMeters ) AS elev, o.observeruid';
		
		if($IMAGE_FLAG_IS_ACTIVE > 0){ //query additional flags
			$sql .= ', IFNULL(i.tid, 0) AS taxonImages, i.adultFlag AS adultFlag, i.immatureFlag AS immatureFlag, i.diagnosticFlag AS diagnosticFlag';
		}
	
		$sql .= ' FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
		
		if($IMAGE_FLAG_IS_ACTIVE> 0){ 
		 	//left join for distinct tid from images for image flags, VMS 3/28/2016
             $sql .= 'LEFT JOIN (SELECT tid, BIT_OR(IFNULL(adultFlag, 0)) AS adultFlag, BIT_OR(IFNULL(immatureFlag, 0)) AS immatureFlag, BIT_OR(IFNULL(diagnosticFlag, 0)) AS diagnosticFlag FROM images GROUP BY tid) i ON i.tid = o.tidinterpreted ';     
		}
		$sql = 'SELECT o.occid, o.CollID, o.institutioncode, o.collectioncode, IFNULL(o.CatalogNumber,"") AS catalognumber, o.family, o.sciname, o.tidinterpreted, '.
			'IFNULL(o.scientificNameAuthorship,"") AS author, IFNULL(o.recordedBy,"") AS recordedby, IFNULL(o.recordNumber,"") AS recordnumber, '.
			'CONCAT_WS(" to ",IFNULL(DATE_FORMAT(o.eventDate,"%d %M %Y"),""),DATE_FORMAT(MAKEDATE(o.year,o.endDayOfYear),"%d %M %Y")) AS date, '.
			'IFNULL(o.country,"") AS country, IFNULL(o.StateProvince,"") AS state, IFNULL(o.county,"") AS county, '.
			'CONCAT_WS(", ",o.locality,CONCAT(ROUND(o.decimallatitude,5)," ",ROUND(o.decimallongitude,5))) AS locality, '.
			'IFNULL(o.LocalitySecurity,0) AS LocalitySecurity, o.localitysecurityreason, '.
			'CONCAT_WS("-",o.minimumElevationInMeters, o.maximumElevationInMeters) AS elev, o.observeruid '.
			'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
		
		if(array_key_exists("clid",$this->searchTermsArr)) $sql .= 'INNER JOIN fmvouchers v ON o.occid = v.occid ';
		$sql .= $sqlWhere;
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= "ORDER BY c.sortseq, c.collectionname ";
		if(strpos($sqlWhere,"(o.sciname") || strpos($sqlWhere,"o.family")){
			$sql .= ",o.sciname ";
		}
		$sql .= ",o.recordedBy,o.recordNumber+1 ";
		$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
		//echo "<div>Spec sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$canReadRareSpp = false;
		if($userRights){
			if(array_key_exists("SuperAdmin", $userRights) || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
				$canReadRareSpp = true;
			}
		}
		while($row = $result->fetch_object()){
			$collIdStr = $row->CollID;
			$occId = $row->occid;
			$returnArr[$collIdStr][$occId]["institutioncode"] = $row->institutioncode;
			$returnArr[$collIdStr][$occId]["collectioncode"] = $row->collectioncode;
			$returnArr[$collIdStr][$occId]["accession"] = $row->catalognumber;
			$returnArr[$collIdStr][$occId]["family"] = $this->cleanOutStr($row->family);
			$returnArr[$collIdStr][$occId]["sciname"] = $this->cleanOutStr($row->sciname);
			$returnArr[$collIdStr][$occId]["tid"] = $row->tidinterpreted;
			$returnArr[$collIdStr][$occId]["author"] = $this->cleanOutStr($row->author);
			$returnArr[$collIdStr][$occId]["collector"] = $this->cleanOutStr($row->recordedby);
			$returnArr[$collIdStr][$occId]["country"] = $row->country;
			$returnArr[$collIdStr][$occId]["state"] = $row->state;
			$returnArr[$collIdStr][$occId]["county"] = $row->county;
			$returnArr[$collIdStr][$occId]["observeruid"] = $row->observeruid;
            
			// Added by Vaughn M. Shirey (VMS) Image Flags
			if($IMAGE_FLAG_IS_ACTIVE > 0){ 
				$returnArr[$collIdStr][$occId]["taxonImages"] = $row->taxonImages; //throw image value to list.php for image flags, VMS 3/28/2016
				$returnArr[$collIdStr][$occId]["adultFlag"] = $row->adultFlag;
				$returnArr[$collIdStr][$occId]["immatureFlag"] = $row->immatureFlag;
				$returnArr[$collIdStr][$occId]["diagnosticFlag"] = $row->diagnosticFlag; // image flags added VMS 3/30/2016
            }
			
			$localitySecurity = $row->LocalitySecurity;
			if(!$localitySecurity || $canReadRareSpp 
				|| (array_key_exists("CollEditor", $userRights) && in_array($collIdStr,$userRights["CollEditor"]))
				|| (array_key_exists("RareSppReader", $userRights) && in_array($collIdStr,$userRights["RareSppReader"]))){
				$returnArr[$collIdStr][$occId]["locality"] = str_replace('.,',',',$row->locality);
				$returnArr[$collIdStr][$occId]["collnumber"] = $this->cleanOutStr($row->recordnumber);
				$returnArr[$collIdStr][$occId]["date"] = $row->date;
				$returnArr[$collIdStr][$occId]["elev"] = $row->elev;
			}
			else{
				$securityStr = '<span style="color:red;">Detailed locality information protected. ';
				if($row->localitysecurityreason){
					$securityStr .= $row->localitysecurityreason;
				}
				else{
					$securityStr .= 'This is typically done to protect rare or threatened species localities.';
				}
				$returnArr[$collIdStr][$occId]["locality"] = $securityStr.'</span>';
			}
		}
		$result->close();
		return $returnArr;
	}

	private function setRecordCnt($sqlWhere){
		global $clientRoot;
		if($sqlWhere){
			$sql = "SELECT COUNT(o.occid) AS cnt FROM omoccurrences o ";
			if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
			$sql .= $sqlWhere;
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->close();
		}
		setCookie("collvars","reccnt:".$this->recordCount,time()+64800,($clientRoot?$clientRoot:'/'));
	}

	public function getRecordCnt(){
		return $this->recordCount;
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
