<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include_once("OccurrenceManager.php");

class SOLRManager extends OccurrenceManager{

	protected $recordCount = 0;
	protected $sortField1 = '';
	protected $sortField2 = '';
	protected $sortOrder = '';
    protected $qStr = '';
    protected $spatial = false;
    private $checklistTaxaCnt = 0;
    private $iconColors = Array();
    private $collArr = Array();

 	public function __construct(){
 		parent::__construct();
        $this->iconColors = array('fc6355','5781fc','fcf357','00e13c','e14f9e','55d7d7','ff9900','7e55fc');
    }

	public function __destruct(){
 		parent::__destruct();
	}

    public function getMaxCnt($geo = false){
        global $SOLR_URL;
        $maxCnt = 0;
        if($geo) $this->setSpatial();
        $solrWhere = $this->getSOLRWhere();
        $solrURL = $SOLR_URL.'/select?'.$solrWhere;
        $solrURL .= '&rows=1&start=1&wt=json';
        //echo str_replace(' ','%20',$solrURL);
        $solrArrJson = file_get_contents(str_replace(' ','%20',$solrURL));
        $solrArr = json_decode($solrArrJson, true);
        $maxCnt = $solrArr['response']['numFound'];

        return $maxCnt;
    }

    public function getTaxaArr(){
        global $SOLR_URL;
        $returnArr = Array();
        $solrURL = '';
        $solrURLpre = '';
        $solrURLsuf = '';
        $cnt = $this->getMaxCnt();
        $solrWhere = $this->getSOLRWhere();
        $solrURLpre = $SOLR_URL.'/select?';
        $solrURLsuf = '&rows='.$cnt.'&start=1&fl=family,tidinterpreted,sciname,accFamily&group=true&group.field=familyscinamecode&wt=json';
        $solrURL = $solrURLpre.$solrWhere.$solrURLsuf;
        //echo str_replace(' ','%20',$solrURL);
        $solrArrJson = file_get_contents(str_replace(' ','%20',$solrURL));
        $solrArr = json_decode($solrArrJson, true);
        $returnArr = $solrArr['grouped']['familyscinamecode']['groups'];

        return $returnArr;
    }

    public function getOccArr($geo = false){
        global $SOLR_URL;
        $returnArr = Array();
        $solrURL = '';
        $solrURLpre = '';
        $solrURLsuf = '';
        if($geo) $this->setSpatial();
        $cnt = $this->getMaxCnt();
        $solrWhere = $this->getSOLRWhere();
        $solrURLpre = $SOLR_URL.'/select?';
        $solrURLsuf = '&rows='.$cnt.'&start=0&fl=occid&wt=json';
        $solrURL = $solrURLpre.$solrWhere.$solrURLsuf;
        //echo str_replace(' ','%20',$solrURL);
        $solrArrJson = file_get_contents(str_replace(' ','%20',$solrURL));
        $solrArr = json_decode($solrArrJson, true);
        $recArr = $solrArr['response']['docs'];
        foreach($recArr as $k){
            $returnArr[] = $k['occid'];
        }

        return $returnArr;
    }

    public function getRecordArr($pageRequest,$cntPerPage){
        global $SOLR_URL;
	    $returnArr = Array();
		$solrURL = '';
        $solrURLpre = '';
        $solrURLsuf = '';
        $bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$solrWhere = $this->getSOLRWhere();
        $solrURLpre = $SOLR_URL.'/select?';
        if($this->sortField1 || $this->sortField2 || $this->sortOrder){
            $sortArr = Array();
            $sortFields = array('Collection' => 'CollectionName','Catalog Number' => 'catalogNumber','Family' => 'family',
                'Scientific Name' => 'sciname','Collector' => 'recordedBy','Number' => 'recordNumber','Event Date' => 'eventDate',
                'Individual Count' => 'individualCount','Life Stage' => 'lifeStage','Sex' => 'sex',
                'Country' => 'country','State/Province' => 'StateProvince','County' => 'county','Elevation' => 'minimumElevationInMeters');
            if($this->sortField1) $this->sortField1 = $sortFields[$this->sortField1];
            if($this->sortField2) $this->sortField2 = $sortFields[$this->sortField2];
            $solrURLsuf = '&sort=';
            if(!$canReadRareSpp) $sortArr[] = "localitySecurity asc";
            $sortArr[] = $this->sortField1.' '.$this->sortOrder;
            if($this->sortField2) $sortArr[] = $this->sortField2.' '.$this->sortOrder;
            $solrURLsuf .= implode(',',$sortArr);
        }
        else{
            $solrURLsuf = '&sort=SortSeq asc,CollectionName asc,sciname asc,';
            if(!$canReadRareSpp) $solrURLsuf .= 'localitySecurity asc,';
            $solrURLsuf .= 'recordedBy asc,recordNumber asc';
        }
        $solrURLsuf .= '&rows='.$cntPerPage.'&start='.$bottomLimit.'&wt=json';
		$solrURL = $solrURLpre.$solrWhere.$solrURLsuf;
		//echo str_replace(' ','%20',$solrURL);
		$solrArrJson = file_get_contents(str_replace(' ','%20',$solrURL));
        $solrArr = json_decode($solrArrJson, true);
        $this->recordCount = $solrArr['response']['numFound'];
        $returnArr = $solrArr['response']['docs'];

        return $returnArr;
	}

    public function getGeoArr($pageRequest,$cntPerPage){
        global $SOLR_URL;
        $returnArr = Array();
        $solrURL = '';
        $solrURLpre = '';
        $solrURLsuf = '';
        $this->setSpatial();
        $solrWhere = ($this->qStr?$this->qStr:$this->getSOLRWhere());
        if($pageRequest > 0) $bottomLimit = ($pageRequest - 1)*$cntPerPage;
        //$solrURLpre = $SOLR_URL.'/select?q=*:*&fq={!geofilt sfield=geo}&pt=35.389049966911664,-109.27001953125&d=5';
        $solrURLpre = $SOLR_URL.'/select?';
        $solrURLsuf = '&rows='.$cntPerPage.'&start='.($bottomLimit?$bottomLimit:'0');
        $solrURLsuf .= '&fl=occid,recordedBy,recordNumber,displayDate,sciname,family,accFamily,tidinterpreted,decimalLatitude,decimalLongitude,'.
            'localitySecurity,locality,collid,catalogNumber,otherCatalogNumbers,InstitutionCode,CollectionCode,CollectionName&wt=json';
        $solrURL = $solrURLpre.$solrWhere.$solrURLsuf;
        //echo str_replace(' ','%20',$solrURL);
        $solrArrJson = file_get_contents(str_replace(' ','%20',$solrURL));
        $solrArr = json_decode($solrArrJson, true);
        $this->recordCount = $solrArr['response']['numFound'];
        $returnArr = $solrArr['response']['docs'];

        return $returnArr;
    }

    public function checkQuerySecurity($q){
        $canReadRareSpp = false;
        if($GLOBALS['USER_RIGHTS']){
            if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
                $canReadRareSpp = true;
            }
        }
        if(!$canReadRareSpp){
            if($q == '*:*'){
                $q = '(localitySecurity:0)';
            }
            else{
                $q .= ' AND (localitySecurity:0)';
            }
        }

        return $q;
    }

    public function translateSOLRRecList($sArr){
        global $imageDomain;
 	    $returnArr = Array();
        $canReadRareSpp = false;
        if($GLOBALS['USER_RIGHTS']){
            if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
                $canReadRareSpp = true;
            }
        }
        foreach($sArr as $k){
            $occId = $k['occid'];
            $collId = $k['collid'];
            $locality = (isset($k['locality'])?$k['locality'][0]:'');
            $locality .= (isset($k['decimalLatitude'])?', '.round((float)$k['decimalLatitude'],5).(isset($k['decimalLongitude'])?' '.round((float)$k['decimalLongitude'],5):''):'');
            $elev = (isset($k['minimumElevationInMeters'])?$k['minimumElevationInMeters']:'');
            $elev .= (isset($k['minimumElevationInMeters']) && isset($k['maximumElevationInMeters'])?' - ':'');
            $elev .= (isset($k['maximumElevationInMeters'])?$k['maximumElevationInMeters']:'');
            $localitySecurity = (isset($k['LocalitySecurity'])?$k['LocalitySecurity']:0);
            $returnArr[$occId]["collid"] = $collId;
            $returnArr[$occId]["institutioncode"] = (isset($k['InstitutionCode'])?$k['InstitutionCode']:'');
            $returnArr[$occId]["collectioncode"] = (isset($k['CollectionCode'])?$k['CollectionCode']:'');
            $returnArr[$occId]["collectionname"] = (isset($k['CollectionName'])?$k['CollectionName']:'');
            $returnArr[$occId]["collicon"] = (isset($k['collicon'])?$k['collicon'][0]:'');
            $returnArr[$occId]["accession"] = (isset($k['catalogNumber'])?$k['catalogNumber']:'');
            $returnArr[$occId]["family"] = (isset($k['family'])?$k['family']:'');
            $returnArr[$occId]["sciname"] = (isset($k['sciname'])?$k['sciname']:'');
            $returnArr[$occId]["tid"] = (isset($k['tidinterpreted'])?$k['tidinterpreted']:'');
            $returnArr[$occId]["author"] = (isset($k['scientificNameAuthorship'])?$k['scientificNameAuthorship']:'');
            $returnArr[$occId]["collector"] = (isset($k['recordedBy'])?$k['recordedBy']:'');
            $returnArr[$occId]["country"] = (isset($k['country'])?$k['country']:'');
            $returnArr[$occId]["state"] = (isset($k['StateProvince'])?$k['StateProvince']:'');
            $returnArr[$occId]["county"] = (isset($k['county'])?$k['county']:'');
            $returnArr[$occId]["assochost"] = (isset($k['assocverbatimsciname'])?$k['assocverbatimsciname'][0]:'');
            $returnArr[$occId]["observeruid"] = (isset($k['observeruid'])?$k['observeruid']:'');
            $returnArr[$occId]["individualCount"] = (isset($k['individualCount'])?$k['individualCount']:'');
            $returnArr[$occId]["lifeStage"] = (isset($k['lifeStage'])?$k['lifeStage']:'');
            $returnArr[$occId]["sex"] = (isset($k['sex'])?$k['sex']:'');
            $localitySecurity = (isset($k['localitySecurity'])?$k['localitySecurity']:false);
            if(!$localitySecurity || $canReadRareSpp
                || (array_key_exists("CollEditor", $GLOBALS['USER_RIGHTS']) && in_array($collId,$GLOBALS['USER_RIGHTS']["CollEditor"]))
                || (array_key_exists("RareSppReader", $GLOBALS['USER_RIGHTS']) && in_array($collId,$GLOBALS['USER_RIGHTS']["RareSppReader"]))){
                $returnArr[$occId]["locality"] = str_replace('.,',',',$locality);
                $returnArr[$occId]["collnumber"] = (isset($k['recordNumber'])?$k['recordNumber']:'');
                $returnArr[$occId]["habitat"] = (isset($k['habitat'])?$k['habitat'][0]:'');
                $returnArr[$occId]["date"] = (isset($k['displayDate'])?$k['displayDate']:'');
                $returnArr[$occId]["eventDate"] = (isset($k['eventDate'])?$k['eventDate']:'');
                $returnArr[$occId]["elev"] = $elev;
            }
            else{
                $securityStr = '<span style="color:red;">Detailed locality information protected. ';
                if(isset($k['localitySecurityReason'])){
                    $securityStr .= $k['localitySecurityReason'];
                }
                else{
                    $securityStr .= 'This is typically done to protect rare or threatened species localities.';
                }
                $returnArr[$occId]["locality"] = $securityStr.'</span>';
            }
            if(isset($k['thumbnailurl'])){
                $tnUrl = $k['thumbnailurl'][0];
                if($imageDomain){
                    if(substr($tnUrl,0,1)=="/") $tnUrl = $imageDomain.$tnUrl;
                }
                $returnArr[$occId]["img"] = $tnUrl;
            }
	    }

	    return $returnArr;
    }

    public function translateSOLRMapRecList($sArr){
        $returnArr = Array();
        $canReadRareSpp = false;
        if($GLOBALS['USER_RIGHTS']){
            if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
                $canReadRareSpp = true;
            }
        }
        foreach($sArr as $k){
            $occId = $k['occid'];
            $collId = $k['collid'];
            $locality = (isset($k['locality'])?$k['locality'][0]:'');
            $locality .= (isset($k['decimalLatitude'])?', '.round((float)$k['decimalLatitude'],5).(isset($k['decimalLongitude'])?' '.round((float)$k['decimalLongitude'],5):''):'');
            $localitySecurity = (isset($k['LocalitySecurity'])?$k['LocalitySecurity']:0);
            $returnArr[$occId]["i"] = (isset($k['InstitutionCode'])?$k['InstitutionCode']:'');
            $returnArr[$occId]["cat"] = (isset($k['catalogNumber'])?$k['catalogNumber']:'');
            $returnArr[$occId]["c"] = (isset($k['recordedBy'])?$k['recordedBy']:'').(isset($k['recordNumber'])?' '.$k['recordNumber']:'');
            $returnArr[$occId]["e"] = (isset($k['displayDate'])?$k['displayDate']:'');
            $returnArr[$occId]["f"] = (isset($k['family'])?$k['family']:'');
            $returnArr[$occId]["s"] = (isset($k['sciname'])?$k['sciname']:'');
            $returnArr[$occId]["lat"] = (isset($k['decimalLatitude'])?$k['decimalLatitude']:'');
            $returnArr[$occId]["lon"] = (isset($k['decimalLongitude'])?$k['decimalLongitude']:'');
            if(!$localitySecurity || $canReadRareSpp
                || (array_key_exists("CollEditor", $GLOBALS['USER_RIGHTS']) && in_array($collId,$GLOBALS['USER_RIGHTS']["CollEditor"]))
                || (array_key_exists("RareSppReader", $GLOBALS['USER_RIGHTS']) && in_array($collId,$GLOBALS['USER_RIGHTS']["RareSppReader"]))){
                $returnArr[$occId]["l"] = str_replace('.,',',',$locality);
            }
            else{
                $securityStr = '<span style="color:red;">Detailed locality information protected. ';
                if(isset($k['localitySecurityReason'])){
                    $securityStr .= $k['localitySecurityReason'];
                }
                else{
                    $securityStr .= 'This is typically done to protect rare or threatened species localities.';
                }
                $returnArr[$occId]["l"] = $securityStr.'</span>';
            }
        }

        return $returnArr;
    }


    public function translateSOLRGeoCollList($sArr){
        $returnArr = Array();
        $collMapper = Array();
        $collMapper["undefined"] = "undefined";
        $cnt = 0;
        $color = 'e69e67';
        foreach($sArr as $k){
            $canReadRareSpp = false;
            $collid = $this->xmlentities($k['collid']);
            $localitySecurity = $k['localitySecurity'];
            if($GLOBALS['USER_RIGHTS']){
                if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin",$GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin",$GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll",$GLOBALS['USER_RIGHTS'])){
                    $canReadRareSpp = true;
                }
                elseif(array_key_exists("RareSppReader",$userRights) && in_array($collid,$GLOBALS['USER_RIGHTS']["RareSppReader"])){
                    $canReadRareSpp = true;
                }
            }
            $decLat = $k['decimalLatitude'];
            $decLong = $k['decimalLongitude'];
            if((($decLong <= 180 && $decLong >= -180) && ($decLat <= 90 && $decLat >= -90)) && ($canReadRareSpp || !$localitySecurity)){
                $occId = $k['occid'];
                $collName = $k['CollectionName'];
                $tidInterpreted = (isset($k['tidinterpreted'])?$this->xmlentities($k['tidinterpreted']):'');
                $identifier = (isset($k['recordedBy'])?$k['recordedBy']:'');
                $identifier .= ((isset($k['recordNumber']) || isset($k['displayDate']))?' ':'');
                $identifier .= ((isset($k['recordNumber']) && !isset($k['displayDate']))?$k['recordNumber']:'');
                $identifier .= ((!isset($k['recordNumber']) && isset($k['displayDate']))?$k['displayDate']:'');
                $latLngStr = $decLat.",".$decLong;
                $returnArr[$collName][$occId]["latLngStr"] = $latLngStr;
                $returnArr[$collName][$occId]["collid"] = $collid;
                $tidcode = strtolower(str_replace(" ","",$tidInterpreted.$k['sciname']));
                $tidcode = preg_replace("/[^A-Za-z0-9 ]/","",$tidcode);
                $returnArr[$collName][$occId]["namestring"] = $this->xmlentities($tidcode);
                $returnArr[$collName][$occId]["tidinterpreted"] = $tidInterpreted;
                $returnArr[$collName][$occId]["family"] = (isset($k['accFamily'])?$this->xmlentities($k['accFamily']):(isset($k['family'])?$this->xmlentities($k['family']):''));
                if($returnArr[$collName][$occId]["family"]){
                    $returnArr[$collName][$occId]["family"] = strtoupper($returnArr[$collName][$occId]["family"]);
                }
                else{
                    $returnArr[$collName][$occId]["family"] = 'undefined';
                }
                $returnArr[$collName][$occId]["sciname"] = (isset($k['sciname'])?$k['sciname']:'');
                $returnArr[$collName][$occId]["identifier"] = $this->xmlentities($identifier);
                $returnArr[$collName][$occId]["institutioncode"] = $this->xmlentities($k['InstitutionCode']);
                $returnArr[$collName][$occId]["collectioncode"] = $this->xmlentities($k['CollectionCode']);
                $returnArr[$collName][$occId]["catalognumber"] = $this->xmlentities($k['catalogNumber']);
                $returnArr[$collName][$occId]["othercatalognumbers"] = $this->xmlentities($k['otherCatalogNumbers']);
                $returnArr[$collName]["color"] = $color;
            }
        }
        if(isset($returnArr['undefined'])){
            $returnArr["undefined"]["color"] = $color;
        }

        return $returnArr;
    }

    public function translateSOLRGeoTaxaList($sArr){
        $returnArr = Array();
        $taxaMapper = Array();
        $taxaMapper["undefined"] = "undefined";
        $cnt = 0;
        foreach($this->taxaArr as $key => $valueArr){
            $coordArr[$key] = Array("color" => $this->iconColors[$cnt%7]);
            $cnt++;
            $taxaMapper[$key] = $key;
            if(array_key_exists("scinames",$valueArr)){
                $scinames = $valueArr["scinames"];
                foreach($scinames as $sciname){
                    $taxaMapper[$sciname] = $key;
                }
            }
            if(array_key_exists("synonyms",$valueArr)){
                $synonyms = $valueArr["synonyms"];
                foreach($synonyms as $syn){
                    $taxaMapper[$syn] = $key;
                }
            }
        }
        foreach($sArr as $k){
            $canReadRareSpp = false;
            $collid = $k['collid'];
            $localitySecurity = $k['localitySecurity'];
            if($GLOBALS['USER_RIGHTS']){
                if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin",$GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppAdmin",$GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll",$GLOBALS['USER_RIGHTS'])){
                    $canReadRareSpp = true;
                }
                elseif(array_key_exists("RareSppReader",$userRights) && in_array($collid,$GLOBALS['USER_RIGHTS']["RareSppReader"])){
                    $canReadRareSpp = true;
                }
            }
            $decLat = $k['decimalLatitude'];
            $decLong = $k['decimalLongitude'];
            if((($decLong <= 180 && $decLong >= -180) && ($decLat <= 90 && $decLat >= -90)) && ($canReadRareSpp || !$localitySecurity)){
                $occId = $k['occid'];
                $sciName = $k['sciname'];
                $family = $k['family'];
                $identifier = (isset($k['recordedBy'])?$k['recordedBy']:'');
                $identifier .= ((isset($k['recordNumber']) || isset($k['displayDate']))?' ':'');
                $identifier .= ((isset($k['recordNumber']) && !isset($k['displayDate']))?$k['recordNumber']:'');
                $identifier .= ((!isset($k['recordNumber']) && isset($k['displayDate']))?$k['displayDate']:'');
                $latLngStr = $decLat.",".$decLong;
                if(!array_key_exists($sciName,$taxaMapper)){
                    foreach($taxaMapper as $keySciname => $v){
                        if(strpos($sciName,$keySciname) === 0){
                            $sciName = $keySciname;
                            break;
                        }
                    }
                    if(!array_key_exists($sciName,$taxaMapper) && array_key_exists($family,$taxaMapper)){
                        $sciName = $family;
                    }
                }
                if(!array_key_exists($sciName,$taxaMapper)) $sciName = "undefined";
                $returnArr[$taxaMapper[$sciName]][$occId]["collid"] = $collid;
                $returnArr[$taxaMapper[$sciName]][$occId]["latLngStr"] = $latLngStr;
                $returnArr[$taxaMapper[$sciName]][$occId]["identifier"] = $identifier;
                $returnArr[$taxaMapper[$sciName]][$occId]["tidinterpreted"] = $k['tidinterpreted'];
                $returnArr[$taxaMapper[$sciName]][$occId]["institutioncode"] = $k['InstitutionCode'];
                $returnArr[$taxaMapper[$sciName]][$occId]["collectioncode"] = $k['CollectionCode'];
                $returnArr[$taxaMapper[$sciName]][$occId]["catalognumber"] = $k['catalogNumber'];
                $returnArr[$taxaMapper[$sciName]][$occId]["othercatalognumbers"] = $k['otherCatalogNumbers'];
            }
        }
        if(isset($returnArr['undefined'])){
            $returnArr["undefined"]["color"] = $this->iconColors[7];
        }

        return $returnArr;
    }

    public function translateSOLRTaxaList($sArr){
        $returnArr = Array();
        $this->checklistTaxaCnt = 0;
        foreach($sArr as $k){
            $family = (isset($k['doclist']['docs'][0]['accFamily'])?strtoupper($k['doclist']['docs'][0]['accFamily']):strtoupper($k['doclist']['docs'][0]['family']));
            if(!$family) $family = 'undefined';
            $returnArr[$family][] = $k['doclist']['docs'][0]['sciname'];
            $this->checklistTaxaCnt++;
        }

        return $returnArr;
    }

    public function getSOLRTidList($sArr){
        $returnArr = Array();
        foreach($sArr as $k){
            if(isset($k['doclist']['docs'][0]['tidinterpreted']) && !in_array($k['doclist']['docs'][0]['tidinterpreted'],$returnArr)){
                $returnArr[] = $k['doclist']['docs'][0]['tidinterpreted'];
            }
        }

        return $returnArr;
    }

	public function getRecordCnt(){
		return $this->recordCount;
	}

    public function getChecklistTaxaCnt(){
        return $this->checklistTaxaCnt;
    }

    public function setTaxaArr($tArr){
        $this->taxaArr = $tArr;
    }

    public function setCollArr($cArr){
        $this->collArr = $cArr;
    }

    public function setSpatial(){
        $this->spatial = true;
    }

    public function setQStr($str){
        $this->qStr = $str;
    }

    public function setSorting($sf1,$sf2,$so){
        $this->sortField1 = $sf1;
        $this->sortField2 = $sf2;
        $this->sortOrder = $so;
    }
	
	public function updateSOLR(){
        global $SOLR_URL;
	    $needsFullUpdate = $this->checkLastSOLRUpdate();
        $command = ($needsFullUpdate?'full-import':'delta-import');
        file_get_contents($SOLR_URL.'/dataimport?command='.$command.'&clean=false');
        if($needsFullUpdate){
            $this->resetSOLRInfoFile();
        }
    }

    public function deleteSOLRDocument($occid){
        global $SOLR_URL;
        $occidStr = '';
        $pArr = Array();
        if(!is_array($occid) || count($occid) < 1000){
            if(is_array($occid)){
                $occidStr = '('.implode(' ',$occid).')';
            }
            else{
                $occidStr = '('.$occid.')';
            }
            $pArr["commit"] = 'true';
            $pArr["stream.body"] = '<delete><query>(occid:'.$occidStr.')</query></delete>';

            $headers = array(
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Content-Length: '.strlen(http_build_query($pArr))
            );
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => $SOLR_URL.'/update',
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_POSTFIELDS => http_build_query($pArr),
                CURLOPT_RETURNTRANSFER => true
            );
            curl_setopt_array($ch, $options);
            curl_exec($ch);
            curl_close($ch);
        }
        else{
            $delCnt = count($occid);
            $i = 0;
            do{
                $subArr = array_slice($occid,$i,1000);
                $occidStr = '('.implode(' ',$subArr).')';
                $pArr["commit"] = 'true';
                $pArr["stream.body"] = '<delete><query>(occid:'.$occidStr.')</query></delete>';

                $headers = array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'Content-Length: '.strlen(http_build_query($pArr))
                );
                $ch = curl_init();
                $options = array(
                    CURLOPT_URL => $SOLR_URL.'/update',
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 90,
                    CURLOPT_POSTFIELDS => http_build_query($pArr),
                    CURLOPT_RETURNTRANSFER => true
                );
                curl_setopt_array($ch, $options);
                $result = curl_exec($ch);
                curl_close($ch);
                $i = $i + 1000;
            } while($i < $delCnt);
        }
    }

    public function cleanSOLRIndex($collid){
        global $SOLR_URL;
        $cnt = 0;
        $SOLROccArr = Array();
        $mysqlOccArr = Array();
        $delOccArr = Array();
        $solrWhere = 'q=(collid:('.$collid.'))';
        $solrURL = $SOLR_URL.'/select?'.$solrWhere;
        $solrURL .= '&rows=1&start=1&wt=json';
        //echo str_replace(' ','%20',$solrURL);
        $solrArrJson = file_get_contents(str_replace(' ','%20',$solrURL));
        $solrArr = json_decode($solrArrJson, true);
        $cnt = $solrArr['response']['numFound'];
        $occURL = $SOLR_URL.'/select?'.$solrWhere.'&rows='.$cnt.'&start=1&fl=occid&wt=json';
        //echo str_replace(' ','%20',$occURL);
        $solrOccArrJson = file_get_contents(str_replace(' ','%20',$occURL));
        $solrOccArr = json_decode($solrOccArrJson, true);
        $recArr = $solrOccArr['response']['docs'];
        foreach($recArr as $k){
            $SOLROccArr[] = $k['occid'];
        }
        $sql = 'SELECT occid FROM omoccurrences WHERE collid = '.$collid;
        if($rs = $this->conn->query($sql)){
            while($r = $rs->fetch_object()){
                $mysqlOccArr[] = $r->occid;
            }
        }
        $delOccArr = array_diff($SOLROccArr,$mysqlOccArr);
        if($delOccArr){
            $this->deleteSOLRDocument($delOccArr);
        }
        echo '<li>...Complete!</li>';
    }

    private function checkLastSOLRUpdate(){
        global $SERVER_ROOT, $SOLR_FULL_IMPORT_INTERVAL;
        $now = new DateTime();
        $now = $now->format('Y-m-d H:i:sP');
        $needsUpdate = false;

        if(file_exists($SERVER_ROOT.'/temp/data/solr.json')){
            $infoArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/solr.json'), true);
            $lastDate = (isset($infoArr['lastFullImport'])?$infoArr['lastFullImport']:'');
            if($lastDate){
                $lastDate = new DateTime($lastDate);
                $now = new DateTime($now);
                $interval = $now->diff($lastDate);
                $hours = $interval->h;
                $hours = $hours + ($interval->days*24);
                if($hours >= $SOLR_FULL_IMPORT_INTERVAL){
                    $needsUpdate = true;
                }
            }
            else{
                $needsUpdate = true;
            }
        }
        else{
            $this->resetSOLRInfoFile();
        }

        return $needsUpdate;
    }

    private function resetSOLRInfoFile(){
        global $SERVER_ROOT;
        $now = new DateTime();
        $now = $now->format('Y-m-d H:i:sP');
        $infoArr = Array();

        if(file_exists($SERVER_ROOT.'/temp/data/solr.json')){
            $infoArr = json_decode(file_get_contents($SERVER_ROOT.'/temp/data/solr.json'), true);
            unlink($SERVER_ROOT.'/temp/data/solr.json');
        }
        $infoArr['lastFullImport'] = $now;

        $fp = fopen($SERVER_ROOT.'/temp/data/solr.json', 'w');
        fwrite($fp, json_encode($infoArr));
        fclose($fp);
    }
	
	public function getSOLRWhere(){
        $solrWhere = '';
        $solrGeoWhere = '';
        $retStr = '';
        if(array_key_exists('clid',$this->searchTermsArr)){
            $solrWhere .= "AND (CLID:(".str_replace(',',' ',$this->searchTermsArr['clid']).")) ";
        }
        elseif(array_key_exists("db",$this->searchTermsArr) && $this->searchTermsArr['db']){
            //Do nothing if db = all
            if($this->searchTermsArr['db'] != 'all'){
                if($this->searchTermsArr['db'] == 'allspec'){
                    $solrWhere .= 'AND (CollType:"Preserved Specimens") ';
                }
                elseif($this->searchTermsArr['db'] == 'allobs'){
                    $solrWhere .= 'AND (CollType:("General Observations" "Observations")) ';
                }
                else{
                    $dbArr = explode(';',$this->searchTermsArr["db"]);
                    $dbStr = '';
                    if(isset($dbArr[0]) && $dbArr[0]){
                        $dbStr = "collid:(".str_replace(',',' ',trim($dbArr[0])).")";
                    }
                    $solrWhere .= 'AND ('.$dbStr.') ';
                }
            }
        }

        if(array_key_exists("taxa",$this->searchTermsArr)){
            $sqlWhereTaxa = "";
            $useThes = (array_key_exists("usethes",$this->searchTermsArr)?$this->searchTermsArr["usethes"]:0);
            $this->taxaSearchType = $this->searchTermsArr["taxontype"];
            $taxaArr = explode(";",trim($this->searchTermsArr["taxa"]));
            //Set scientific name
            $this->taxaArr = Array();
            foreach($taxaArr as $sName){
                $this->taxaArr[trim($sName)] = Array();
            }
            if($this->taxaSearchType == 5){
                //Common name search
                $this->setSciNamesByVerns();
            }
            elseif($useThes){
                $this->setSynonyms();
            }

            //Build sql
            foreach($this->taxaArr as $key => $valueArray){
                if($this->taxaSearchType == 4){
                    //Class, order, or other higher rank
                    $rs1 = $this->conn->query("SELECT ts.tidaccepted FROM taxa AS t LEFT JOIN taxstatus AS ts ON t.TID = ts.tid WHERE (t.sciname = '".$key."')");
                    if($r1 = $rs1->fetch_object()){
                        $sqlWhereTaxa = 'OR (parenttid:'.$r1->tidaccepted.') ';
                    }
                }
                else{
                    if($this->taxaSearchType == 5){
                        $famArr = array();
                        if(array_key_exists("families",$valueArray)){
                            $famArr = $valueArray["families"];
                        }
                        if(array_key_exists("tid",$valueArray)){
                            $tidArr = $valueArray['tid'];
                            $sql = 'SELECT DISTINCT t.sciname '.
                                'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
                                'WHERE t.rankid = 140 AND e.taxauthid = 1 AND e.parenttid IN('.implode(',',$tidArr).')';
                            $rs = $this->conn->query($sql);
                            while($r = $rs->fetch_object()){
                                $famArr[] = $r->family;
                            }
                        }
                        if($famArr){
                            $famArr = array_unique($famArr);
                            $sqlWhereTaxa .= 'OR (family:('.implode(' ',$famArr).')) ';
                        }
                        if(array_key_exists("scinames",$valueArray)){
                            foreach($valueArray["scinames"] as $sciName){
                                $sqlWhereTaxa .= "OR ((sciname:".str_replace(' ','\ ',$sciName).") OR (sciname:".str_replace(' ','\ ',$sciName)."\ *)) ";
                            }
                        }
                    }
                    else{
                        if($this->taxaSearchType == 2 || ($this->taxaSearchType == 1 && (strtolower(substr($key,-5)) == "aceae" || strtolower(substr($key,-4)) == "idae"))){
                            $sqlWhereTaxa .= 'OR (family:'.$key.') ';
                        }
                        if($this->taxaSearchType == 3 || ($this->taxaSearchType == 1 && strtolower(substr($key,-5)) != "aceae" && strtolower(substr($key,-4)) != "idae")){
                            $sqlWhereTaxa .= "OR ((sciname:".str_replace(' ','\ ',$key).") OR (sciname:".str_replace(' ','\ ',$key)."\ *)) ";
                        }
                    }
                    if(array_key_exists("synonyms",$valueArray)){
                        $synArr = $valueArray["synonyms"];
                        if($synArr){
                            if($this->taxaSearchType == 1 || $this->taxaSearchType == 2 || $this->taxaSearchType == 5){
                                foreach($synArr as $synTid => $sciName){
                                    if(strpos($sciName,'aceae') || strpos($sciName,'idae')){
                                        $sqlWhereTaxa .= 'OR (family:'.$sciName.') ';
                                    }
                                }
                            }
                            $sqlWhereTaxa .= 'OR (tidinterpreted:('.implode(' ',array_keys($synArr)).')) ';
                        }
                    }
                }
            }
            $solrWhere .= "AND (".substr($sqlWhereTaxa,3).") ";
        }

        if(array_key_exists("country",$this->searchTermsArr)){
            $searchStr = str_replace("%apos;","'",$this->searchTermsArr["country"]);
            $countryArr = explode(";",$searchStr);
            $tempArr = Array();
            foreach($countryArr as $k => $value){
                if($value == 'NULL'){
                    $countryArr[$k] = '-country:["" TO *]';
                    $tempArr[] = '(Country IS NULL)';
                }
                else{
                    $tempArr[] = '(country:"'.trim($value).'")';
                }
            }
            $solrWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
            $this->localSearchArr[] = implode(' OR ',$countryArr);
        }
        if(array_key_exists("state",$this->searchTermsArr)){
            $searchStr = str_replace("%apos;","'",$this->searchTermsArr["state"]);
            $stateAr = explode(";",$searchStr);
            $tempArr = Array();
            foreach($stateAr as $k => $value){
                if($value == 'NULL'){
                    $tempArr[] = '-StateProvince:["" TO *]';
                    $stateAr[$k] = 'State IS NULL';
                }
                else{
                    $tempArr[] = '(StateProvince:"'.trim($value).'")';
                }
            }
            $solrWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
            $this->localSearchArr[] = implode(' OR ',$stateAr);
        }
        if(array_key_exists("county",$this->searchTermsArr)){
            $searchStr = str_replace("%apos;","'",$this->searchTermsArr["county"]);
            $countyArr = explode(";",$searchStr);
            $tempArr = Array();
            foreach($countyArr as $k => $value){
                if($value == 'NULL'){
                    $tempArr[] = '-county:["" TO *]';
                    $countyArr[$k] = 'County IS NULL';
                }
                else{
                    $value = trim(str_ireplace(' county',' ',$value));
                    $tempArr[] = '(county:'.str_replace(' ','\ ',trim($value)).'*)';
                }
            }
            $solrWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
            $this->localSearchArr[] = implode(' OR ',$countyArr);
        }
        if(array_key_exists("local",$this->searchTermsArr)){
            $searchStr = str_replace("%apos;","'",$this->searchTermsArr["local"]);
            $localArr = explode(";",$searchStr);
            $tempArr = Array();
            foreach($localArr as $k => $value){
                if(strpos($value,' ')){
                    $wordArr = explode(" ",$value);
                    $tempStrArr = Array();
                    foreach($wordArr as $w => $word){
                        $tempStrArr[] = '((municipality:'.trim($word).'*) OR (locality:*'.trim($word).'*))';
                    }
                    $tempArr[] = '('.implode(' AND ',$tempStrArr).')';
                }
                else{
                    if($value == 'NULL'){
                        $tempArr[] = '-locality:["" TO *]';
                        $localArr[$k] = 'Locality IS NULL';
                    }
                    else{
                        $tempArr[] = '((municipality:'.trim($value).'*) OR (locality:*'.trim($value).'*))';
                    }
                }
            }
            $solrWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
            $this->localSearchArr[] = implode(' OR ',$localArr);
        }
        if(array_key_exists("elevlow",$this->searchTermsArr) || array_key_exists("elevhigh",$this->searchTermsArr)){
            $elevlow = 0;
            $elevhigh = 30000;
            if (array_key_exists("elevlow",$this->searchTermsArr))  { $elevlow = $this->searchTermsArr["elevlow"]; }
            if (array_key_exists("elevhigh",$this->searchTermsArr))  { $elevhigh = $this->searchTermsArr["elevhigh"]; }
            $solrWhere .= 'AND ((minimumElevationInMeters:['.$elevlow.' TO *] AND maximumElevationInMeters:[* TO '.$elevhigh.']) OR '.
                '(-maximumElevationInMeters:[* TO *] AND minimumElevationInMeters:['.$elevlow.' TO *] AND minimumElevationInMeters:[* TO '.$elevhigh.']))';
        }
        if(array_key_exists("assochost",$this->searchTermsArr)){
            $searchStr = str_replace("%apos;","'",$this->searchTermsArr["assochost"]);
            $hostAr = explode(";",$searchStr);
            $tempArr = Array();
            foreach($hostAr as $k => $value){
                if($value == 'NULL'){
                    $tempArr[] = '((assocrelationship:"host") AND (-assocverbatimsciname:["" TO *]))';
                    $hostAr[$k] = 'Host IS NULL';
                }
                else{
                    $tempArr[] = '((assocrelationship:"host") AND (assocverbatimsciname:*'.str_replace(' ','\ ',trim($value)).'*))';
                }
            }
            $solrWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
            $this->localSearchArr[] = implode(' OR ',$hostAr);
        }
        if(array_key_exists("llbound",$this->searchTermsArr)){
            $llboundArr = explode(";",$this->searchTermsArr["llbound"]);
            if(count($llboundArr) == 4){
                $solrWhere .= 'AND ((decimalLatitude:['.$llboundArr[1].' TO '.$llboundArr[0].']) AND '.
                    '(decimalLongitude:['.$llboundArr[2].' TO '.$llboundArr[3].'])) ';
                $this->localSearchArr[] = "Lat: >".$llboundArr[1].", <".$llboundArr[0]."; Long: >".$llboundArr[2].", <".$llboundArr[3];
            }
        }
        if(array_key_exists("collector",$this->searchTermsArr)){
            $searchStr = str_replace("%apos;","'",$this->searchTermsArr["collector"]);
            $collectorArr = explode(";",$searchStr);
            $tempArr = Array();
            if(count($collectorArr) == 1){
                if($collectorArr[0] == 'NULL'){
                    $tempArr[] = '(-recordedBy:["" TO *])';
                    $collectorArr[] = 'Collector IS NULL';
                }
                else{
                    $tempInnerArr = array();
                    $collValueArr = explode(" ",trim($collectorArr[0]));
                    foreach($collValueArr as $collV){
                        $tempInnerArr[] = '(recordedBy:*'.str_replace(' ','\ ',$collV).'*) ';
                    }
                    $tempArr[] = implode(' AND ', $tempInnerArr);
                }
            }
            elseif(count($collectorArr) > 1){
                $tempArr[] = '(recordedBy:('.implode(' ',$collectorArr).')) ';
            }
            $solrWhere .= 'AND ('.implode(' OR ',$tempArr).') ';
            $this->localSearchArr[] = implode(', ',$collectorArr);
        }
        if(array_key_exists("collnum",$this->searchTermsArr)){
            $collNumArr = explode(";",$this->searchTermsArr["collnum"]);
            $rnWhere = '';
            foreach($collNumArr as $v){
                $v = trim($v);
                if($p = strpos($v,' - ')){
                    $term1 = trim(substr($v,0,$p));
                    $term2 = trim(substr($v,$p+3));
                    if(is_numeric($term1) && is_numeric($term2)){
                        $rnIsNum = true;
                        $rnWhere .= 'OR (recordNumber:['.$term1.' TO '.$term2.'])';
                    }
                    else{
                        if(strlen($term2) > strlen($term1)) $term1 = str_pad($term1,strlen($term2),"0",STR_PAD_LEFT);
                        $rnWhere .= 'OR (recordNumber:["'.$term1.'" TO "'.$term2.'"])';
                    }
                }
                else{
                    $rnWhere .= 'OR (recordNumber:"'.$v.'") ';
                }
            }
            if($rnWhere){
                $solrWhere .= "AND (".substr($rnWhere,3).") ";
                $this->localSearchArr[] = implode(", ",$collNumArr);
            }
        }
        if(array_key_exists('eventdate1',$this->searchTermsArr)){
            $dateArr = array();
            if(strpos($this->searchTermsArr['eventdate1'],' to ')){
                $dateArr = explode(' to ',$this->searchTermsArr['eventdate1']);
            }
            elseif(strpos($this->searchTermsArr['eventdate1'],' - ')){
                $dateArr = explode(' - ',$this->searchTermsArr['eventdate1']);
            }
            else{
                $dateArr[] = $this->searchTermsArr['eventdate1'];
                if(isset($this->searchTermsArr['eventdate2'])){
                    $dateArr[] = $this->searchTermsArr['eventdate2'];
                }
            }
            if($dateArr[0] == 'NULL'){
                $solrWhere .= 'AND (-eventDate:["" TO *]) ';
                $this->localSearchArr[] = 'Date IS NULL';
            }
            elseif($eDate1 = $this->formatDate($dateArr[0])){
                $eDate2 = (count($dateArr)>1?$this->formatDate($dateArr[1]):'');
                if($eDate2){
                    $solrWhere .= 'AND (eventDate:['.$eDate1.'T00:00:00Z TO '.$eDate2.'T23:59:59.999Z]) ';
                }
                else{
                    if(substr($eDate1,-5) == '00-00'){
                        $solrWhere .= 'AND (coll_year:'.substr($eDate1,0,4).') ';
                    }
                    elseif(substr($eDate1,-2) == '00'){
                        $solrWhere .= 'AND ((coll_year:'.substr($eDate1,0,4).') AND (coll_month:'.substr($eDate1,5,7).')) ';
                    }
                    else{
                        $solrWhere .= 'AND (eventDate:['.$eDate1.'T00:00:00Z TO '.$eDate1.'T23:59:59.999Z]) ';
                    }
                }
                $this->localSearchArr[] = $this->searchTermsArr['eventdate1'].(isset($this->searchTermsArr['eventdate2'])?' to '.$this->searchTermsArr['eventdate2']:'');
            }
        }
        if(array_key_exists('catnum',$this->searchTermsArr)){
            $catStr = $this->searchTermsArr['catnum'];
            $includeOtherCatNum = array_key_exists('othercatnum',$this->searchTermsArr)?true:false;

            $catArr = explode(',',str_replace(';',',',$catStr));
            $betweenFrag = array();
            $inFrag = array();
            foreach($catArr as $v){
                if($p = strpos($v,' - ')){
                    $term1 = trim(substr($v,0,$p));
                    $term2 = trim(substr($v,$p+3));
                    if(is_numeric($term1) && is_numeric($term2)){
                        $betweenFrag[] = '(catalogNumber:['.$term1.' TO '.$term2.'])';
                        if($includeOtherCatNum){
                            $betweenFrag[] = '(otherCatalogNumbers:['.$term1.' TO '.$term2.'])';
                        }
                    }
                    else{
                        $catTerm = '(catalogNumber:["'.$term1.'" TO "'.$term2.'"])';
                        $betweenFrag[] = '('.$catTerm.')';
                        if($includeOtherCatNum){
                            $betweenFrag[] = '(otherCatalogNumbers:["'.$term1.'" TO "'.$term2.'"])';
                        }
                    }
                }
                else{
                    $vStr = trim($v);
                    $inFrag[] = $vStr;
                }
            }
            $catWhere = '';
            if($betweenFrag){
                $catWhere .= 'OR '.implode(' OR ',$betweenFrag);
            }
            if($inFrag){
                $catWhere .= 'OR (catalogNumber:("'.implode('" "',$inFrag).'")) ';
                if($includeOtherCatNum){
                    $catWhere .= 'OR (otherCatalogNumbers:("'.implode('" "',$inFrag).'")) ';
                }
            }
            $solrWhere .= 'AND ('.substr($catWhere,3).') ';
            $this->localSearchArr[] = $this->searchTermsArr['catnum'];
        }
        if(array_key_exists("typestatus",$this->searchTermsArr)){
            $solrWhere .= 'AND (typeStatus:[* TO *]) ';
            $this->localSearchArr[] = 'is type';
        }
        if(array_key_exists("hasimages",$this->searchTermsArr)){
            $solrWhere .= 'AND (imgid:[* TO *]) ';
            $this->localSearchArr[] = 'has images';
        }
        if(array_key_exists("hasgenetic",$this->searchTermsArr)){
            $solrWhere .= 'AND (resourcename:[* TO *]) ';
            $this->localSearchArr[] = 'has genetic data';
        }
        if(array_key_exists("llpoint",$this->searchTermsArr)){
            $pointArr = explode(";",$this->searchTermsArr["llpoint"]);
            $radius = $pointArr[2]*1.6214;
            $solrGeoWhere = '{!geofilt sfield=geo}';
            $solrGeoWhere .= '&pt='.$pointArr[0].','.$pointArr[1].'&d='.$radius;
            $this->localSearchArr[] = "Point radius: ".$pointArr[0].", ".$pointArr[1].", within ".$pointArr[2]." miles";
        }
        if(array_key_exists("polycoords",$this->searchTermsArr)){
            $coordArr = json_decode($this->searchTermsArr["polycoords"], true);
            if($coordArr){
                $coordStr = '';
                $coordStr = 'Polygon((';
                $keys = array();
                foreach($coordArr as $k => $v){
                    $keys = array_keys($v);
                    $coordStr .= $v[$keys[1]]." ".$v[$keys[0]].",";
                }
                $coordStr .= $coordArr[0][$keys[1]]." ";
                $coordStr .= $coordArr[0][$keys[0]]."))";
                $solrGeoWhere = '{!field f=geo}Intersects('.$coordStr.')';
                $this->localSearchArr[] = "Within polygon";
            }
        }
        if($solrWhere){
            $retStr = 'q=';
            $retStr .= substr($solrWhere,4);
            if($this->spatial){
                $retStr .= 'AND (decimalLatitude:[* TO *] AND decimalLongitude:[* TO *]) ';
            }
        }
        else{
            $retStr = 'q=*:*';
        }
        if($solrGeoWhere){
            $retStr .= '&fq='.$solrGeoWhere;
        }
        //echo $retStr; exit;
        return $retStr;
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

    private function xmlentities($string){
        return str_replace(array ('&','"',"'",'<','>','?'),array ('&amp;','&quot;','&apos;','&lt;','&gt;','&apos;'),$string);
    }
}
?>