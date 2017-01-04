<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');

class OccurrenceAPIManager{

	private $conn;
	private $collId = 0;
    private $occId = 0;
    private $dbpk = '';
    private $catNum = '';
    private $occLUWhere = '';

	function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

    public function setOccLookupSQLWhere(){
        $this->occLUWhere = '';
        $this->occLUWhere = 'WHERE o.collid = '.$this->collId.' ';
        if($this->occId){
            $this->occLUWhere .= 'AND o.occid = '.$this->occId.' ';
        }
        if($this->dbpk){
            $this->occLUWhere .= 'AND o.dbpk = "'.$this->dbpk.'" ';
        }
        if($this->catNum){
            $this->occLUWhere .= 'AND (o.catalogNumber = "'.$this->catNum.'" OR o.otherCatalogNumbers = "'.$this->catNum.'") ';
        }
    }

    public function getOccLookupArr(){
        global $USER_RIGHTS;
        $returnArr = Array();
        $sql = 'SELECT o.occid, o.collid, o.dbpk, o.institutioncode, o.collectioncode, o.catalogNumber, o.otherCatalogNumbers, o.family, '.
            'o.sciname, o.tidinterpreted, o.scientificNameAuthorship, o.recordedBy, o.recordNumber, o.eventDate, o.country, '.
            'o.stateProvince, o.county, o.locality, o.decimallatitude, o.decimallongitude, o.LocalitySecurity, '.
            'o.localitysecurityreason, o.minimumElevationInMeters, o.maximumElevationInMeters, o.observeruid '.
            'FROM omoccurrences AS o ';
        $sql .= $this->occLUWhere;
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        $canReadRareSpp = false;
        if($USER_RIGHTS){
            if(array_key_exists("SuperAdmin",$USER_RIGHTS) || array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
                $canReadRareSpp = true;
            }
        }
        while($row = $result->fetch_object()){
            $occId = $row->occid;
            $returnArr[$occId]["collid"] = $row->collid;
            $returnArr[$occId]["dbpk"] = $row->dbpk;
            $returnArr[$occId]["institutioncode"] = $row->institutioncode;
            $returnArr[$occId]["collectioncode"] = $row->collectioncode;
            $returnArr[$occId]["catalogNumber"] = $row->catalogNumber;
            $returnArr[$occId]["otherCatalogNumbers"] = $row->otherCatalogNumbers;
            $returnArr[$occId]["family"] = $row->family;
            $returnArr[$occId]["sciname"] = $row->sciname;
            $returnArr[$occId]["tidinterpreted"] = $row->tidinterpreted;
            $returnArr[$occId]["scientificNameAuthorship"] = $row->scientificNameAuthorship;
            $returnArr[$occId]["recordedBy"] = $row->recordedBy;
            $returnArr[$occId]["country"] = $row->country;
            $returnArr[$occId]["stateProvince"] = $row->stateProvince;
            $returnArr[$occId]["county"] = $row->county;
            $returnArr[$occId]["observeruid"] = $row->observeruid;
            $localitySecurity = $row->LocalitySecurity;
            if(!$localitySecurity || $canReadRareSpp
                || (array_key_exists("CollEditor", $GLOBALS['USER_RIGHTS']) && in_array($collIdStr,$GLOBALS['USER_RIGHTS']["CollEditor"]))
                || (array_key_exists("RareSppReader", $GLOBALS['USER_RIGHTS']) && in_array($collIdStr,$GLOBALS['USER_RIGHTS']["RareSppReader"]))){
                $returnArr[$occId]["locality"] = $row->locality;
                $returnArr[$occId]["decimallatitude"] = $row->decimallatitude;
                $returnArr[$occId]["decimallongitude"] = $row->decimallongitude;
                $returnArr[$occId]["recordNumber"] = $row->recordNumber;
                $returnArr[$occId]["eventDate"] = $row->eventDate;
                $returnArr[$occId]["minimumElevationInMeters"] = $row->minimumElevationInMeters;
                $returnArr[$occId]["maximumElevationInMeters"] = $row->maximumElevationInMeters;
            }
            else{
                $securityStr = 'Detailed locality information protected. ';
                if($row->localitysecurityreason){
                    $securityStr .= $row->localitysecurityreason;
                }
                else{
                    $securityStr .= 'This is typically done to protect rare or threatened species localities.';
                }
                $returnArr[$occId]["locality"] = $securityStr;
            }
        }
        $result->free();

        return $returnArr;
    }

	public function setCollID($val){
        $this->collId = $this->cleanInStr($val);
    }

    public function setOccID($val){
        $this->occId = $this->cleanInStr($val);
    }

    public function setDBPK($val){
        $this->dbpk = $this->cleanInStr($val);
    }

    public function setCatNum($val){
        $this->catNum = $this->cleanInStr($val);
    }

    protected function cleanInStr($str){
        $newStr = trim($str);
        $newStr = preg_replace('/\s\s+/', ' ',$newStr);
        $newStr = $this->conn->real_escape_string($newStr);
        return $newStr;
    }
}
?>