<?php
include_once($serverRoot.'/config/dbconnection.php');
 
/**
 * Data structure to hold attributes of a specimen that needs identification. 
 * 
 * @see getSpecimensPendingIdent
 * @author mole
 *
 */
class NeedsIDResult { 
	public $occid;
	public $sciname;
	public $collectionCode;
	public $institutionCode;
	public $stateProvince;
}

class PersonalSpecimenManager {

	private $conn;
	private $uid;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getOccurrenceArr(){
		global $userRights;
		$retArr = array();
		if($this->uid){
			$cArr = array();
			if(array_key_exists('CollAdmin',$userRights)) $cArr = $userRights['CollAdmin'];
			if(array_key_exists('CollEditor',$userRights)) $cArr = array_merge($cArr,$userRights['CollEditor']);
			if($cArr){
				$sql = 'SELECT collid, collectionname, colltype, CONCAT_WS(" ",institutioncode,collectioncode) AS instcode '.
					'FROM omcollections WHERE collid IN('.implode(',',$cArr).') ORDER BY collectionname';
				//echo $sql;
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$retArr[strtolower($r->colltype)][$r->collid] = $r->collectionname.($r->instcode?' ('.$r->instcode.')':'');
					}
					$rs->free();
				}
			}
		}
		return $retArr;
	}
	
	/**
	 * 
	 * Obtain the list of specimens that aren't identified to at least the species level from 
	 * within the list of taxa for which this user is listed as a specialist.
	 * 
	 * @returns array of NeedsIDResult 
	 */
	public function getSpecimensPendingIdent(){
		global $userRights;
		$retArr = array();
		if($this->uid){
			$cArr = array();
			if(array_key_exists('CollAdmin',$userRights)) $cArr = $userRights['CollAdmin'];
			if(array_key_exists('CollEditor',$userRights)) $cArr = array_merge($cArr,$userRights['CollEditor']);
			if($cArr){
				// TODO: Query on arbtirary lower taxa, not just parent + child.  
				// Current query works as expected if usertaxonomy record is a genus or a family.
				$sql = 'select * from ( '.
				       '  select occid, sciname, o.collectioncode, o.institutioncode, o.stateprovince ' . 
				       '     from omoccurrences o ' . 
				       '     left join usertaxonomy ut on o.tidinterpreted = ut.tid ' . 
				       '    where ut.uid = ? ' . 
				       '  union ' . 
				       '  select occid, o.sciname, o.collectioncode, o.institutioncode, o.stateprovince ' .
				       '     from omoccurrences o ' . 
				       '     left join taxstatus t on o.tidinterpreted = t.tidaccepted ' .
				       '     left join usertaxonomy ut on t.parenttid = ut.tid ' . 
				       '     left join taxa on t.tidaccepted = taxa.tid ' .
				       '    where ut.uid = ? and taxa.rankid < 220 ' . 
				       ') a order by sciname';
				$statement = $this->conn->prepare($sql);
				$statement->bind_param('ii', $this->uid, $this->uid);
				$statement->execute();
				$statement->bind_result($occid, $sciname, $collectioncode, $institutioncode, $stateprovince);
				while($statement->fetch()){
					$o = new NeedsIDResult();
					$o->occid = $occid;
					$o->sciname = $sciname;
					$o->collectionCode = $collectioncode;
					$o->institutionCode = $institutioncode;
					$o->stateProvince= $stateprovince;
					$retArr[$occid] = $o;
				}
				$statement->close();
			}
		}
		return $retArr;
	}	
	


	public function dlSpecBackup($collId, $characterSet, $zipFile = 1){
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
    		'WHERE collid = '.$collId.' AND observeruid = '.$this->uid;
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
	
	public function getRecordCount($collId){
		$retCnt = 0;
		if($this->uid){
			$sql = 'SELECT count(*) AS reccnt FROM omoccurrences WHERE observeruid = '.$this->uid.' AND collid = '.$collId;
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
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
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