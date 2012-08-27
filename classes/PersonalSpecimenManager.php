<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class PersonalSpecimenManager {

	private $conn;
	private $collId;
	private $collName;
	private $collType;
	private $uid;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getObservationArr(){
		global $userRights;
		$retArr = array();
		if($this->uid){
			$isAdmin = 0;
			$collIdStr = '';
			foreach($userRights as $k => $v){
				if($k == 'SuperAdmin'){
					$isAdmin = 1;
				}
				else{
					if($k == 'CollAdmin'){
						$collIdStr .= ','.implode(',',$v);
					}
					if($k == 'CollEditor'){
						$collIdStr .= ','.implode(',',$v);
					}
				}
			}
			$sql = 'SELECT collid, collectionname, CONCAT_WS(" ",institutioncode,collectioncode) AS instcode '.
				'FROM omcollections WHERE colltype = "general observations" '; 
			if($collIdStr){
				$sql .= 'AND collid IN('.substr($collIdStr,1).') ';
			}
			$sql .= 'ORDER BY collectionname';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $r->collectionname.($r->instcode?' ('.$r->instcode.')':'');
				}
				$rs->close();
			}
		}
		return $retArr;
	}

    public function dlSpecBackup($characterSet, $zipFile = 1){
    	global $charset, $paramsArr;

		$tempPath = $this->getTempPath();
    	$buFileName = $paramsArr['un'].'_'.time();
 		$zipArchive;
    	
    	if($zipFile && class_exists('ZipArchive')){
			$zipArchive = new ZipArchive;
			$zipArchive->open($tempPath.$buFileName.'.zip', ZipArchive::CREATE);
 		}
    	
    	$cSet = str_replace('-','',strtolower($charset));
		$fileUrl = '';
    	//If zip archive can be created, the occurrences, determinations, and image records will be added to single archive file
    	//If not, then a CSV file containing just occurrence records will be returned
		echo '<li style="font-weight:bold;">Zip Archive created</li>';
		echo '<li style="font-weight:bold;">Adding occurrence records to archive...';
		ob_flush();
		flush();
    	//Adding occurrence records
    	$fileName = $tempPath.$buFileName;
    	$specFH = fopen($fileName.'_spec.csv', "w");
    	//Output header 
    	$headerStr = 'occid,dbpk,basisOfRecord,otherCatalogNumbers,ownerInstitutionCode, '.
			'family,scientificName,sciname,tidinterpreted,genus,specificEpithet,taxonRank,infraspecificEpithet,scientificNameAuthorship, '.
			'taxonRemarks,identifiedBy,dateIdentified,identificationReferences,identificationRemarks,identificationQualifier, '.
			'typeStatus,recordedBy,recordNumber,associatedCollectors,eventDate,year,month,day,startDayOfYear,endDayOfYear, '.
			'verbatimEventDate,habitat,substrate,fieldNotes,occurrenceRemarks,informationWithheld,associatedOccurrences, '.
			'dataGeneralizations,associatedTaxa,dynamicProperties,verbatimAttributes,reproductiveCondition, '.
			'cultivationStatus,establishmentMeans,lifeStage,sex,individualCount,country,stateProvince,county,municipality, '.
			'locality,localitySecurity,localitySecurityReason,decimalLatitude,decimalLongitude,geodeticDatum, '.
			'coordinateUncertaintyInMeters,verbatimCoordinates,georeferencedBy,georeferenceProtocol,georeferenceSources, '.
			'georeferenceVerificationStatus,georeferenceRemarks,minimumElevationInMeters,maximumElevationInMeters,verbatimElevation, '.
			'previousIdentifications,disposition,modified,language,processingstatus,recordEnteredBy,duplicateQuantity, '.
			'labelProject,dateLastModified ';
		fputcsv($specFH, explode(',',$headerStr));
		//Query and output values
    	$sql = 'SELECT '.$headerStr.
    		' FROM omoccurrences '.
    		'WHERE collid = '.$this->collId.' AND observeruid = '.$this->uid;
    	if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_row()){
				if($characterSet && $characterSet != $cSet){
					$this->encodeArr($r,$characterSet);
				}
				fputcsv($specFH, $r);
			}
    		$rs->close();
    	}
    	fclose($specFH);
		if($zipFile && $zipArchive){
    		//Add occurrence file and then rename to 
			$zipArchive->addFile($fileName.'_spec.csv');
			$zipArchive->renameName($fileName.'_spec.csv','occurrences.csv');

			//Add determinations
			/*
			echo 'Done!</li> ';
			echo '<li style="font-weight:bold;">Adding determinations records to archive...';
			ob_flush();
			flush();
			$detFH = fopen($fileName.'_det.csv', "w");
			fputcsv($detFH, Array('detid','occid','sciname','scientificNameAuthorship','identifiedBy','d.dateIdentified','identificationQualifier','identificationReferences','identificationRemarks','sortsequence'));
			//Add determination values
			$sql = 'SELECT d.detid,d.occid,d.sciname,d.scientificNameAuthorship,d.identifiedBy,d.dateIdentified, '.
				'd.identificationQualifier,d.identificationReferences,d.identificationRemarks,d.sortsequence '.
				'FROM omdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid '.
				'WHERE o.collid = '.$this->collId.' AND o.observeruid = '.$this->uid;
    		if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_row()){
					fputcsv($detFH, $r);
				}
    			$rs->close();
    		}
    		fclose($detFH);
			$zipArchive->addFile($fileName.'_det.csv');
    		$zipArchive->renameName($fileName.'_det.csv','determinations.csv');
			*/
    		
			echo 'Done!</li> ';
			ob_flush();
			flush();
			$fileUrl = str_replace($GLOBALS['serverRoot'],$GLOBALS['clientRoot'],$tempPath.$buFileName.'.zip');
			$zipArchive->close();
			unlink($fileName.'_spec.csv');
			//unlink($fileName.'_det.csv');
		}
		else{
			$fileUrl = str_replace($GLOBALS['serverRoot'],$GLOBALS['clientRoot'],$tempPath.$buFileName.'_spec.csv');
    	}
		return $fileUrl;
	}
	
	public function getRecordCount(){
		$retCnt = 0;
		if($this->uid){
			$sql = 'SELECT count(*) AS reccnt FROM omoccurrences WHERE observeruid = '.$this->uid.' AND collid = '.$this->collId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retCnt = $r->reccnt;
				}
				$rs->close();
			}
		}
		return $retCnt;
	}

	public function setUid($id){
		$this->uid = $id;
	}

	public function setCollId($collId){
		$this->collId = $collId;
	}
	
	public function setCollectionMetadata(){
		if($this->collId){
			$sql = 'SELECT collectionname, colltype FROM omcollections WHERE collid = '.$this->collId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$this->collName = $r->collectionname;
					$this->collType = $r->colltype;
				}
				$rs->close();
			}
		}
	}

	public function getCollName(){
		return $this->collName;
	}

	public function getCollType(){
		return $this->collType;
	}

	private function getTempPath(){
		$tPath = $GLOBALS["serverRoot"];
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\') $tPath .= '/';
		$tPath .= "temp/";
		if(file_exists($tPath."downloads/")){
			$tPath .= "downloads/";
		}
		return $tPath;
	}

	private function encodeArr(&$inArr,$cSet){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->encodeString($v,$cSet);
		}
	}

	private function encodeString($inStr,$cSet){
 		$retStr = $inStr;
		if($cSet == "utf8"){
			if(mb_detect_encoding($inStr,'ISO-8859-1,UTF-8') == "ISO-8859-1"){
				//$value = utf8_encode($value);
				$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
			}
		}
		elseif($cSet == "latin1"){
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
				//$value = utf8_decode($value);
				$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
			}
		}
		return $retStr;
	}
}
?> 