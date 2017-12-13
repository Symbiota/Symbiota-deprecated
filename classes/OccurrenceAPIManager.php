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
    protected $serverDomain;

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
        $occManager = new OccurrenceEditorImages();
        $occId = 0;
        $occId = ($pArr["occid"]?$pArr["occid"]:$this->getOccFromCatNum($pArr["collid"],$pArr["catnum"]));
        if($occId){
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
            $pArr["catalognumber"] = $pArr["catnum"];
            $occManager->addImageOccurrence($pArr);
            echo 'SUCCESS: Record created and image uploaded';
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
            else{
                exit('ERROR: Multiple records with catalog number');
            }
        }
        $result->free();

        return $occId;
    }

    public function checkFGBatchProcess($collid){
        $retArr = Array();
        $jsonFileName = $collid.'-FGBatch.json';
        $jsonFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'temp/data/fieldguide/'.$jsonFileName;
        if(file_exists($jsonFile)){
            $jsonStr = file_get_contents($jsonFile);
            $retArr = json_decode($jsonStr,true);
        }
        return $retArr;
    }

    public function checkFGBatchResults($collid){
        $results = false;
        $jsonFileName = $collid.'-FGResults.json';
        $jsonFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'temp/data/fieldguide/'.$jsonFileName;
        if(file_exists($jsonFile)){
            $results = true;
        }
        return $results;
    }

    public function initiateFGBatchProcess($collid){
        global $SERVER_ROOT, $CLIENT_ROOT;
        $this->setServerDomain();
        $imgArr = $this->getFGBatchImgArr($collid);
        if($imgArr){
            $processDataArr = array();
            $pArr = array();
            $jsonFileName = $collid.'-FGBatch.json';
            $token = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
            );
            $processDataArr["job_id"] = $collid.'_'.$token;
            $processDataArr["images"] = $imgArr;
            $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName, 'w');
            fwrite($fp, json_encode($processDataArr));
            fclose($fp);
            $dataFileUrl = $this->serverDomain.$CLIENT_ROOT.(substr($CLIENT_ROOT,-1)=='/'?'':'/').'temp/data/fieldguide/'.$jsonFileName;
            $pArr["job_id"] = $processDataArr["job_id"];
            $pArr["url"] = $dataFileUrl;
            $headers = array(
                'authorization: Token 7044979d9ec245768dd7561d85865004',
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Content-Length: '.strlen(http_build_query($pArr))
            );
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => 'http://staging.fieldguide.net/api2/symbiota/submit_cv_job',
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_POSTFIELDS => http_build_query($pArr),
                CURLOPT_RETURNTRANSFER => true
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            curl_close($ch);
        }
    }

    public function cancelFGBatchProcess($collid){
        global $SERVER_ROOT;
        $jsonFileName = $collid.'-FGBatch.json';
        if(file_exists($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName)){
            $dataArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName), true);
            $jobID = $dataArr['job_id'];
            if($jobID){
                $pArr["job_id"] = $jobID;
                $headers = array(
                    'authorization: Token 7044979d9ec245768dd7561d85865004',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'Content-Length: '.strlen(http_build_query($pArr))
                );
                $ch = curl_init();
                $options = array(
                    CURLOPT_URL => 'http://staging.fieldguide.net/api2/symbiota/remove_cv_job',
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 90,
                    CURLOPT_POSTFIELDS => http_build_query($pArr),
                    CURLOPT_RETURNTRANSFER => true
                );
                curl_setopt_array($ch, $options);
                $result = curl_exec($ch);
                curl_close($ch);
            }
        }
        unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName);
    }

    public function getFGBatchImgArr($collid){
        $returnArr = Array();
        $sql = 'SELECT i.imgid, o.occid, o.sciname, i.url '.
            'FROM images AS i LEFT JOIN omoccurrences AS o ON i.occid = o.occid '.
            'WHERE o.collid = '.$collid.' ';
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $imgId = $row->imgid;
            $imgUrl = $row->url;
            $localDomain = '';
            if(isset($GLOBALS['IMAGE_DOMAIN']) && $GLOBALS['IMAGE_DOMAIN']){
                $localDomain = $GLOBALS['IMAGE_DOMAIN'];
            }
            else{
                $localDomain = $this->serverDomain;
            }
            if(substr($imgUrl,0,1) == '/') $imgUrl = $localDomain.$imgUrl;
            $returnArr[$imgId]["occid"] = $row->occid;
            $returnArr[$imgId]["sciname"] = $row->sciname;
            $returnArr[$imgId]["url"] = $imgUrl;
        }
        $result->free();

        return $returnArr;
    }

    public function checkImages($collid){
        $images = false;
        $sql = 'SELECT i.imgid '.
            'FROM images AS i LEFT JOIN omoccurrences AS o ON i.occid = o.occid '.
            'WHERE o.collid = '.$collid.' LIMIT 1';
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            if($row->imgid) $images = true;
        }
        $result->free();

        return $images;
    }

    public function validateFGResults($jobId){
        global $SERVER_ROOT;
        $valid = false;
        $collId = substr($jobId, 0, strpos($jobId, '_'));
        $jsonFileName = $collId.'-FGBatch.json';
        if(file_exists($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName)){
            $dataArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName), true);
            $fileJobID = $dataArr['job_id'];
            if($fileJobID == $jobId) $valid = true;
        }
        return $valid;
    }

    public function processFGResults($jobId,$resultUrl){
        global $SERVER_ROOT;
        $collId = substr($jobId, 0, strpos($jobId, '_'));
        $jsonResFileName = $collId.'-FGResults.json';
        $jsonBatFileName = $collId.'-FGBatch.json';
        if(file_get_contents($resultUrl)){
            $resultsData = file_get_contents($resultUrl);
            $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonResFileName, 'w');
            fwrite($fp, $resultsData);
            fclose($fp);
            unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonBatFileName);
        }
    }

    public function deleteFGBatchResults($collid){
        global $SERVER_ROOT;
        $jsonFileName = $collid.'-FGResults.json';
        if(file_exists($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName)){
            unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName);
        }
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

    public function setServerDomain(){
        $this->serverDomain = "http://";
        if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
        $this->serverDomain .= $_SERVER["SERVER_NAME"];
        if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
    }

    protected function cleanInStr($str){
        $newStr = trim($str);
        $newStr = preg_replace('/\s\s+/', ' ',$newStr);
        $newStr = $this->conn->real_escape_string($newStr);
        return $newStr;
    }
}
?>