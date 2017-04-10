<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

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
            if(array_key_exists("SuperAdmin",$USER_RIGHTS) || array_key_exists("CollAdmin", $USER_RIGHTS) || array_key_exists("RareSppAdmin", $USER_RIGHTS) || array_key_exists("RareSppReadAll", $USER_RIGHTS)){
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

    public function processImageUpload($pArr){
        global $PARAMS_ARR;
        $occId = 0;
        $occId = ($pArr["occid"]?$pArr["occid"]:$this->getOccFromCatNum($pArr["collid"],$pArr["catnum"]));
        if($occId){
            $occManager = new OccurrenceEditorImages();
            $occManager->setSymbUid($PARAMS_ARR["uid"]);
            $occManager->setOccId($occId);
            $occManager->setCollId($pArr["collid"]);
            if($pArr["sciname"] && $pArr["determiner"]){
                $this->processImageUploadDetermination($occId,$pArr);
            }
            $iArr = array(
                "photographeruid" => $PARAMS_ARR["uid"],
                "occid" => $occId,
                "caption" => $pArr['caption'],
                "notes" => $pArr['notes']
            );
            $occManager->addImage($iArr);
            if($SOLR_MODE){
                $solrManager = new SOLRManager();
                $solrManager->updateSOLR();
            }
            echo 'SUCCESS: Image uploaded';
        }
        else{
            echo 'ERROR: Could not determine occid from catnum';
        }
    }

    public function processImageUploadDetermination($occId,$pArr){
        $prevDet = '';
        $detTidAccepted = 0;
        $detFamily = '';
        $detSciNameAuthor = '';
        $sciname = $pArr["sciname"];
        $determiner = $pArr["determiner"];
        $detacc = $pArr["detacc"];
        $prevDet = $this->checkCurrentDetermination($occId);
        if($prevDet != $sciname){
            $sql = 'SELECT ts.tidaccepted, ts.family, t.Author '.
                'FROM taxa AS t LEFT JOIN taxstatus AS ts ON t.TID = ts.tid '.
                'LEFT JOIN taxauthority AS ta ON ts.taxauthid = ta.taxauthid '.
                'WHERE t.SciName = "'.$sciname.'" AND ta.isprimary = 1 ';
            //echo "<div>Sql: ".$sql."</div>";
            $result = $this->conn->query($sql);
            while($row = $result->fetch_object()){
                $detTidAccepted = $row->tidaccepted;
                $detFamily = $row->family;
                $detSciNameAuthor = $row->Author;
            }
            $result->free();
            $occManager = new OccurrenceEditorDeterminations();
            $occManager->setSymbUid($PARAMS_ARR["uid"]);
            $occManager->setOccId($occId);
            $occManager->setCollId($pArr["collid"]);
            $iArr = array(
                "identificationqualifier" => "",
                "sciname" => $sciname,
                "tidtoadd" => $detTidAccepted,
                "family" => $detFamily,
                "scientificnameauthorship" => $detSciNameAuthor,
                "confidenceranking" => 5,
                "identifiedby" => $pArr['determiner'],
                "dateidentified" => date('m-d-Y'),
                "identificationreferences" => "",
                "identificationremarks" => $pArr['detacc'],
                "makecurrent" => 1,
                "occid" => $occId
            );
            $occManager->addDetermination($iArr,1);
            if($SOLR_MODE){
                $solrManager = new SOLRManager();
                $solrManager->updateSOLR();
            }
            echo 'SUCCESS: New determination added';
        }
    }

    public function checkCurrentDetermination($occId){
        $prevDet = '';
        $sql = 'SELECT sciname '.
            'FROM omoccurrences '.
            'WHERE occid = '.$occId.' ';
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $prevDet = $row->sciname;
        }
        $result->free();

        return $prevDet;
    }

    public function validateEditor($collid){
        global $USER_RIGHTS;
        $isEditor = false;
        if(array_key_exists("SuperAdmin",$USER_RIGHTS) || ($collid && array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
            $isEditor = true;
        }
        elseif($collid && array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
            $isEditor = true;
        }

        return $isEditor;
    }

    public function getOccFromCatNum($collid,$catnum){
        $occId = 0;
        $sql = 'SELECT o.occid '.
            'FROM omoccurrences AS o '.
            'WHERE o.collid = '.$collid.' AND (o.catalogNumber = "'.$catnum.'") ';
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            if($result->num_rows == 1) {
                $occId = $row->occid;
            }
        }
        $result->free();

        return $occId;
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