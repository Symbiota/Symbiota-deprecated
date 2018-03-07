<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAPIManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

class FieldGuideManager {

    private $conn;
    private $collId = 0;
    private $jobId = '';
    private $token = '';
    private $taxon = '';
    private $viewMode = '';
    private $recStart = 0;
    private $recLimit = 0;
    private $fgResultTot = 0;
    private $fgResultArr = array();
    private $fgResOccArr = array();
    private $fgResTidArr = array();
    private $resultArr = array();
    protected $serverDomain;

    function __construct(){
        $this->conn = MySQLiConnectionFactory::getCon("readonly");
    }

    public function __destruct(){
        if(!($this->conn === null)) $this->conn->close();
    }

    public function checkFGLog($collid){
        $retArr = Array();
        $jsonFileName = $collid.'-FGLog.json';
        $jsonFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'temp/data/fieldguide/'.$jsonFileName;
        if(file_exists($jsonFile)){
            $jsonStr = file_get_contents($jsonFile);
            $retArr = json_decode($jsonStr,true);
        }
        return $retArr;
    }

    public function initiateFGBatchProcess(){
        global $SERVER_ROOT, $CLIENT_ROOT, $FIELDGUIDE_API_KEY;
        $status = '';
        $this->setServerDomain();
        $imgArr = $this->getFGBatchImgArr();
        if($imgArr){
            $processDataArr = array();
            $pArr = array();
            $token = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                mt_rand( 0, 0xffff ),
                mt_rand( 0, 0x0fff ) | 0x4000,
                mt_rand( 0, 0x3fff ) | 0x8000,
                mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
            );
            $jsonFileName = $this->collId.'-i-'.$token.'.json';
            $jobID = $this->collId.'_'.$token;
            $processDataArr["job_id"] = $jobID;
            $processDataArr["parenttaxon"] = $this->taxon;
            $processDataArr["dateinitiated"] = date("Y-m-d");
            $processDataArr["images"] = $imgArr;
            $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName, 'w');
            fwrite($fp, json_encode($processDataArr));
            fclose($fp);
            $dataFileUrl = $this->serverDomain.$CLIENT_ROOT.(substr($CLIENT_ROOT,-1)=='/'?'':'/').'temp/data/fieldguide/'.$jsonFileName;
            $responseUrl = $this->serverDomain.$CLIENT_ROOT.(substr($CLIENT_ROOT,-1)=='/'?'':'/').'webservices/fieldguidebatch.php';
            $pArr["job_id"] = $processDataArr["job_id"];
            $pArr["response_url"] = $responseUrl;
            $pArr["url"] = $dataFileUrl;
            $headers = array(
                'authorization: Token '.$FIELDGUIDE_API_KEY,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Content-Length: '.strlen(http_build_query($pArr))
            );
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => 'https://fieldguide.net/api2/symbiota/submit_cv_job',
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_POSTFIELDS => http_build_query($pArr),
                CURLOPT_RETURNTRANSFER => true
            );
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            curl_close($ch);
            unset($processDataArr["images"]);
            $this->logFGBatchFile($jsonFileName,$processDataArr);
            $status = 'Batch process initiated';
        }
        else{
            $status = 'No images found for that parent taxon';
        }
        return $status;
    }

    public function logFGBatchFile($jsonFileName,$infoArr){
        global $SERVER_ROOT;
        $jobID = $infoArr["job_id"];
        $fileArr = array();
        if(file_exists($SERVER_ROOT.'/temp/data/fieldguide/'.$this->collId.'-FGLog.json')){
            $fileArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$this->collId.'-FGLog.json'), true);
            unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$this->collId.'-FGLog.json');
        }
        $fileArr['jobs'][$jobID]['file'] = $jsonFileName;
        $fileArr['jobs'][$jobID]['taxon'] = $infoArr["parenttaxon"];
        $fileArr['jobs'][$jobID]['date'] = $infoArr["dateinitiated"];
        $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$this->collId.'-FGLog.json', 'w');
        fwrite($fp, json_encode($fileArr));
        fclose($fp);
    }

    public function cancelFGBatchProcess($collid,$jobId){
        global $SERVER_ROOT, $FIELDGUIDE_API_KEY;
        $status = '';
        $resultsCnt = 0;
        $fileArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json'), true);
        unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json');
        $fileName = $fileArr['jobs'][$jobId]['file'];
        unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$fileName);
        unset($fileArr['jobs'][$jobId]);
        $jobsCnt = count($fileArr['jobs']);
        if(isset($fileArr['results'])) $resultsCnt = count($fileArr['results']);
        if(($jobsCnt + $resultsCnt) > 0){
            $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json', 'w');
            fwrite($fp, json_encode($fileArr));
            fclose($fp);
        }
        $pArr["job_id"] = $jobID;
        $headers = array(
            'authorization: Token '.$FIELDGUIDE_API_KEY,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Content-Length: '.strlen(http_build_query($pArr))
        );
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => 'https://fieldguide.net/api2/symbiota/remove_cv_job',
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_POSTFIELDS => http_build_query($pArr),
            CURLOPT_RETURNTRANSFER => true
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        $status = 'Batch process cancelled';
        return $status;
    }

    public function getFGBatchImgArr(){
        $returnArr = Array();
        $tId = '';
        if($this->taxon) $tId = $this->getFGBatchTaxonTid($this->taxon);
        $sql = 'SELECT i.imgid, o.occid, o.sciname, t.SciName AS taxonorder, i.url '.
            'FROM images AS i LEFT JOIN omoccurrences AS o ON i.occid = o.occid '.
            'LEFT JOIN taxstatus AS ts ON o.tidinterpreted = ts.tid '.
            'LEFT JOIN taxaenumtree AS te ON ts.tidaccepted = te.tid '.
            'LEFT JOIN taxa AS t ON te.parenttid = t.TID '.
            'LEFT JOIN taxa AS t2 ON o.tidinterpreted = t2.TID '.
            'WHERE o.collid = '.$this->collId.' AND ((t2.SciName = "'.$this->taxon.'") OR '.
            '((ts.taxauthid = 1 AND te.taxauthid = 1 AND t.RankId = 100)';
        if($tId) $sql .= ' AND o.tidinterpreted IN(SELECT tid FROM taxaenumtree WHERE parenttid = '.$tId.')';
        $sql .= ')) ';
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
            $returnArr[$imgId]["order"] = $row->taxonorder;
            $returnArr[$imgId]["url"] = $imgUrl;
        }
        $result->free();

        return $returnArr;
    }

    public function getFGBatchTaxonTid($taxon){
        $tId = 0;
        $sql = 'SELECT TID '.
            'FROM taxa '.
            'WHERE SciName = "'.$taxon.'" ';
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $tId = $row->TID;
        }
        $result->free();

        return $tId;
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

    public function validateFGResults($collid,$jobId){
        global $SERVER_ROOT;
        $valid = false;
        if(file_exists($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json')){
            $dataArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json'),true);
            if(isset($dataArr['jobs'][$jobId])) $valid = true;
        }
        return $valid;
    }

    public function logFGResults($collid,$token,$resultUrl){
        global $SERVER_ROOT;
        $jobArr = array();
        $jobID = $collid.'_'.$token;
        $fileArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json'), true);
        unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json');
        foreach($fileArr['jobs'] as $job => $jArr){
            if($job == $jobID){
                $fileName = $jArr['file'];
                $taxon = $jArr['taxon'];
                $startDate = $jArr['date'];
            }
            else{
                $jobArr[$job] = $jArr;
            }
        }
        $dateReceived = date("Y-m-d");
        unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$fileName);
        $fileArr['jobs'] = $jobArr;
        $resArr = json_decode(file_get_contents($resultUrl), true);
        $processDataArr["job_id"] = $jobID;
        $processDataArr["parenttaxon"] = $taxon;
        $processDataArr["dateinitiated"] = $startDate;
        $processDataArr["datereceived"] = $dateReceived;
        $processDataArr["images"] = $resArr['images'];
        $jsonFileName = $collid.'-r-'.$token.'.json';
        $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$jsonFileName, 'w');
        fwrite($fp, json_encode($processDataArr));
        fclose($fp);
        $fileArr['results'][$jobID]['file'] = $jsonFileName;
        $fileArr['results'][$jobID]['taxon'] = $taxon;
        $fileArr['results'][$jobID]['inidate'] = $startDate;
        $fileArr['results'][$jobID]['recdate'] = $dateReceived;
        $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json', 'w');
        fwrite($fp, json_encode($fileArr));
        fclose($fp);
    }

    public function deleteFGBatchResults($collid,$jobId){
        global $SERVER_ROOT;
        $status = '';
        $jobsCnt = 0;
        $fileArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json'), true);
        unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json');
        $fileName = $fileArr['results'][$jobId]['file'];
        unlink($SERVER_ROOT.'/temp/data/fieldguide/'.$fileName);
        unset($fileArr['results'][$jobId]);
        $resultsCnt = count($fileArr['results']);
        if(isset($fileArr['jobs'])) $jobsCnt = count($fileArr['jobs']);
        if(($jobsCnt + $resultsCnt) > 0){
            $fp = fopen($SERVER_ROOT.'/temp/data/fieldguide/'.$collid.'-FGLog.json', 'w');
            fwrite($fp, json_encode($fileArr));
            fclose($fp);
        }
        $status = 'Batch results deleted';
        return $status;
    }

    public function primeFGResults(){
        global $SERVER_ROOT;
        $tempCntArr = array();
        $resultFilename = $this->collId.'-r-'.$this->token.'.json';
        $fileArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/fieldguide/'.$resultFilename), true);
        $this->taxon = $fileArr["parenttaxon"];
        $this->fgResultArr = $fileArr["images"];
        foreach($this->fgResultArr as $imgId => $ifArr){
            if($ifArr["status"] == 'OK'){
                if($ifArr["result"]){
                    foreach($ifArr["result"] as $name){
                        if(!array_key_exists($name,$this->fgResTidArr)){
                            $this->fgResTidArr[$name] = array();
                        }
                    }
                }
            }
            $tempCntArr[] = $imgId;
        }
        $imgIdArr = array_keys($this->fgResultArr);
        $this->primeFGResultsOccArr($imgIdArr);
        $this->getFGResultTids();
    }

    public function primeFGResultsOccArr($imgArr){
        $tempArr = $this->fgResultArr;
        $imgIDStr = implode(",",$imgArr);
        $sql = 'SELECT DISTINCT imgid, occid '.
            'FROM images '.
            "WHERE imgid IN(".$imgIDStr.") ";
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $add = false;
            $imgId = $row->imgid;
            $occId = $row->occid;
            $fgStatus = $this->fgResultArr[$imgId]["status"];
            $fgResults = $this->fgResultArr[$imgId]["result"];
            if($this->viewMode == 'full'){
                $add = true;
            }
            elseif($fgStatus == 'OK' && $fgResults && count($fgResults) > 0){
                $add = true;
            }
            if($add){
                if(!in_array($occId,$this->fgResOccArr)) $this->fgResOccArr[] = $occId;
                $this->fgResultArr[$occId][$imgId] = $tempArr[$imgId];
            }
        }
        $result->free();
    }

    public function getFGResultTids(){
        $fgIDNamesArr = array_keys($this->fgResTidArr);
        $fgIDNamesStr = "'".implode("','",$fgIDNamesArr)."'";
        $sql = 'SELECT t.SciName, t.TID '.
            'FROM taxa AS t LEFT JOIN taxstatus AS ts ON t.TID = ts.tid '.
            "WHERE t.SciName IN(".$fgIDNamesStr.") AND ts.taxauthid = 1 AND t.TID = ts.tidaccepted ";
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $sciname = $row->SciName;
            $tid = $row->TID;
            $this->fgResTidArr[$sciname][] = $tid;
        }
        $result->free();
    }

    public function getFGResultImgArr(){
        $returnArr = Array();
        $fgOccIdStr = implode(",",$this->fgResOccArr);
        $sql = 'SELECT i.imgid, o.occid, o.sciname, i.url '.
            'FROM images AS i LEFT JOIN omoccurrences AS o ON i.occid = o.occid '.
            'WHERE o.occid IN('.$fgOccIdStr.') ';
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

    public function getReturnOccArr(){
        $returnArr = array();
        $occIDStr = implode(",",$this->fgResOccArr);
        $sql = 'SELECT DISTINCT occid '.
            'FROM omoccurrences '.
            "WHERE occid IN(".$occIDStr.") ".
            'ORDER BY occid '.
            'LIMIT '.$this->recStart.','.$this->recLimit;
        //echo "<div>Sql: ".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $returnArr[] = $row->occid;
        }
        $result->free();
        return $returnArr;
    }

    public function processFGResults(){
        $this->fgResultTot = count($this->fgResOccArr);
        $imgArr = $this->getFGResultImgArr();
        $limitArr = $this->getReturnOccArr();
        $i = 1;
        $prevOccid = 0;
        //echo json_encode($imgArr);
        foreach($this->fgResultArr as $occId => $oArr){
            if(($i > $this->recLimit)){
                break;
            }
            if(in_array($occId,$limitArr)){
                foreach($oArr as $imgId => $iArr){
                    if($imgArr[$imgId]){
                        $ifArr = $imgArr[$imgId];
                        $currID = $ifArr["sciname"];
                        $imgUrl = $ifArr["url"];
                        $fgStatus = $iArr["status"];
                        $fgResults = $iArr["result"];
                        if($prevOccid != $occId){
                            $prevOccid = $occId;
                            $i++;
                        }
                        $this->resultArr[$occId]["sciname"] = $currID;
                        $this->resultArr[$occId][$imgId]["url"] = $imgUrl;
                        $this->resultArr[$occId][$imgId]["status"] = $fgStatus;
                        $this->resultArr[$occId][$imgId]["results"] = $fgResults;
                    }
                }
            }
        }
    }

    public function processDeterminations($pArr){
        global $PARAMS_ARR, $SOLR_MODE;
        $occArr = $pArr['occid'];
        foreach($occArr as $occId){
            $idIndex = 'id'.$occId;
            $detTidAccepted = $pArr[$idIndex];
            $detFamily = '';
            $detSciNameAuthor = '';
            $sciname = '';
            $determiner = "FieldGuide CV Determination";
            $sql = 'SELECT ts.family, t.SciName, t.Author '.
                'FROM taxa AS t LEFT JOIN taxstatus AS ts ON t.TID = ts.tid '.
                'LEFT JOIN taxauthority AS ta ON ts.taxauthid = ta.taxauthid '.
                'WHERE t.TID = '.$detTidAccepted.' AND ta.isprimary = 1 ';
            //echo "<div>Sql: ".$sql."</div>";
            $result = $this->conn->query($sql);
            while($row = $result->fetch_object()){
                $sciname = $row->SciName;
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
                "identifiedby" => $determiner,
                "dateidentified" => date('m-d-Y'),
                "identificationreferences" => "",
                "identificationremarks" => "",
                "makecurrent" => 1,
                "occid" => $occId
            );
            $occManager->addDetermination($iArr,1);
        }
        if($SOLR_MODE){
            $solrManager = new SOLRManager();
            $solrManager->updateSOLR();
        }
    }

    public function setCollID($val){
        $this->collId = $val;
    }

    public function setRecLimit($val){
        $this->recLimit = $val;
    }

    public function setRecStart($val){
        $this->recStart = $val;
    }

    public function setJobID($val){
        $this->jobId = $val;
        $jobArr = explode("_",$val,2);
        $this->token = $jobArr[1];
    }

    public function setViewMode($val){
        $this->viewMode = $val;
    }

    public function setTaxon($val){
        $this->taxon = $val;
    }

    public function getResults(){
        return $this->resultArr;
    }

    public function getResultTot(){
        return $this->fgResultTot;
    }

    public function getTids(){
        return $this->fgResTidArr;
    }

    public function setServerDomain(){
        $this->serverDomain = "http://";
        if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
        $this->serverDomain .= $_SERVER["SERVER_NAME"];
        if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
    }
}
?>