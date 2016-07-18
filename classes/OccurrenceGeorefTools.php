<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class OccurrenceGeorefTools {

	private $conn;
	private $collId;
	private $collName;
	private $managementType;
	private $qryVars = array();
	private $errorStr;

	function __construct($type = 'write') {
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getLocalityArr(){
		$retArr = array();
		if($this->collId){
			$sql = 'SELECT occid, country, stateprovince, county, municipality, locality, verbatimcoordinates ,decimallatitude, decimallongitude '.
				'FROM omoccurrences WHERE (collid = '.$this->collId.') AND (locality IS NOT NULL) AND (locality <> "") ';
			if(!$this->qryVars || !array_key_exists('qdisplayall',$this->qryVars) || !$this->qryVars['qdisplayall']){
				$sql .= 'AND (decimalLatitude IS NULL) ';
			}
			$orderBy = '';
			if($this->qryVars){
				if(array_key_exists('qsciname',$this->qryVars) && $this->qryVars['qsciname']){
					$sql .= 'AND (family = "'.$this->qryVars['qsciname'].'" OR sciname LIKE "'.$this->qryVars['qsciname'].'%") ';
				}
				if(array_key_exists('qvstatus',$this->qryVars)){
					$vs = $this->qryVars['qvstatus'];
					if(strtolower($vs) == 'is null'){
						$sql .= 'AND (georeferenceVerificationStatus IS NULL) ';
					}
					else{
						$sql .= 'AND (georeferenceVerificationStatus = "'.$vs.'") ';
					}
				}
				if(array_key_exists('qcountry',$this->qryVars) && $this->qryVars['qcountry']){
					$countySearch = $this->qryVars['qcountry'];
					$synArr = array('usa','u.s.a', 'united states','united states of america','u.s.');
					if(in_array($countySearch,$synArr)){
						$countySearch = implode('","',$synArr);
					}
					$sql .= 'AND (country IN("'.$countySearch.'")) ';
				}
				else{
					$orderBy .= 'country,';
				}
				if(array_key_exists('qstate',$this->qryVars) && $this->qryVars['qstate']){
					$sql .= 'AND (stateProvince = "'.$this->qryVars['qstate'].'") ';
				}
				else{
					$orderBy .= 'stateprovince,';
				}
				if(array_key_exists('qcounty',$this->qryVars) && $this->qryVars['qcounty']){
					$sql .= 'AND (county = "'.$this->qryVars['qcounty'].'") ';
				}
				else{
					$orderBy .= 'county,';
				}
				if(array_key_exists('qmunicipality',$this->qryVars) && $this->qryVars['qmunicipality']){
					$sql .= 'AND (municipality = "'.$this->qryVars['qmunicipality'].'") ';
				}
				else{
					$orderBy .= 'municipality,';
				}
				if(array_key_exists('qprocessingstatus',$this->qryVars) && $this->qryVars['qprocessingstatus']){
					$sql .= 'AND (processingstatus = "'.$this->qryVars['qprocessingstatus'].'") ';
				}
				else{
					$orderBy .= 'processingstatus,';
				}
				if(array_key_exists('qlocality',$this->qryVars) && $this->qryVars['qlocality']){
					$sql .= 'AND (locality LIKE "%'.$this->qryVars['qlocality'].'%") ';
				}
			}
			$sql .= 'ORDER BY '.$orderBy.'locality,verbatimcoordinates ';
			//echo $sql; exit;
			$totalCnt = 0;
			$locCnt = 1;
			$countryStr='';$stateStr='';$countyStr='';$municipalityStr='';$localityStr='';$verbCoordStr = '';$decLatStr='';$decLngStr='';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($countryStr != trim($r->country) || $stateStr != trim($r->stateprovince) || $countyStr != trim($r->county)
					|| $municipalityStr != trim($r->municipality) || $localityStr != trim($r->locality," .,;") 
					|| $verbCoordStr != trim($r->verbatimcoordinates) || $decLatStr != $r->decimallatitude || $decLngStr != $r->decimallongitude){
					$countryStr = trim($r->country);
					$stateStr = trim($r->stateprovince);
					$countyStr = trim($r->county);
					$municipalityStr = trim($r->municipality);
					$localityStr = trim($r->locality," .,;");
					$verbCoordStr = trim($r->verbatimcoordinates);
					$decLatStr = $r->decimallatitude;
					$decLngStr = $r->decimallongitude;
					$totalCnt++;
					$retArr[$totalCnt]['occid'] = $r->occid;
					$retArr[$totalCnt]['country'] = $countryStr;
					$retArr[$totalCnt]['stateprovince'] = $stateStr;
					$retArr[$totalCnt]['county'] = $countyStr;
					$retArr[$totalCnt]['municipality'] = $municipalityStr;
					$retArr[$totalCnt]['locality'] = $localityStr;
					$retArr[$totalCnt]['verbatimcoordinates'] = $verbCoordStr;
					$retArr[$totalCnt]['decimallatitude'] = $decLatStr;
					$retArr[$totalCnt]['decimallongitude'] = $decLngStr;
					$retArr[$totalCnt]['cnt'] = 1;
					$locCnt = 1;
				}
				else{
					$locCnt++;
					$newOccidStr = $retArr[$totalCnt]['occid'].','.$r->occid;
					$retArr[$totalCnt]['occid'] = $newOccidStr;
					$retArr[$totalCnt]['cnt'] = $locCnt;
				}
				if($totalCnt > 999) break;
			}
			$rs->free();
		}
		//usort($retArr,array('OccurrenceGeorefTools', '_cmpLocCnt'));
		return $retArr;
	}

	public function updateCoordinates($geoRefArr){
		global $paramsArr;
		if($this->collId){
			if(is_numeric($geoRefArr['decimallatitude']) && is_numeric($geoRefArr['decimallongitude'])){
				set_time_limit(1000);
				$localStr =  $this->cleanInStr(implode(',',$geoRefArr['locallist']));
				unset($geoRefArr['locallist']);
				$geoRefArr = $this->cleanInArr($geoRefArr);
				if($localStr){
					//Update coordinates
					$this->addOccurEdits('decimallatitude',$geoRefArr['decimallatitude'],$localStr);
					$this->addOccurEdits('decimallongitude',$geoRefArr['decimallongitude'],$localStr);
					$this->addOccurEdits('georeferencedby',$geoRefArr['georeferencedby'],$localStr);
					$sql = 'UPDATE omoccurrences '.
						'SET decimallatitude = '.$geoRefArr['decimallatitude'].', decimallongitude = '.$geoRefArr['decimallongitude'].
						',georeferencedBy = "'.$geoRefArr['georeferencedby'].' ('.date('Y-m-d H:i:s').')'.'"';
					if($geoRefArr['georeferenceverificationstatus']){
						$sql .= ',georeferenceverificationstatus = "'.$geoRefArr['georeferenceverificationstatus'].'"';
						$this->addOccurEdits('georeferenceverificationstatus',$geoRefArr['georeferenceverificationstatus'],$localStr);
					}
					if($geoRefArr['georeferencesources']){
						$sql .= ',georeferencesources = "'.$geoRefArr['georeferencesources'].'"';
						$this->addOccurEdits('georeferencesources',$geoRefArr['georeferencesources'],$localStr);
					}
					if($geoRefArr['georeferenceremarks']){
						$sql .= ',georeferenceremarks = CONCAT_WS("; ",georeferenceremarks,"'.$geoRefArr['georeferenceremarks'].'")';
						$this->addOccurEdits('georeferenceremarks',$geoRefArr['georeferenceremarks'],$localStr);
					}
					if($geoRefArr['coordinateuncertaintyinmeters']){
						$sql .= ',coordinateuncertaintyinmeters = '.$geoRefArr['coordinateuncertaintyinmeters'];
						$this->addOccurEdits('coordinateuncertaintyinmeters',$geoRefArr['coordinateuncertaintyinmeters'],$localStr);
					}
					if($geoRefArr['footprintwkt']){
						$sql .= ',footprintwkt = "'.$geoRefArr['footprintwkt'].'"';
						$this->addOccurEdits('footprintwkt',$geoRefArr['footprintwkt'],$localStr);
					}
					if($geoRefArr['geodeticdatum']){
						$sql .= ', geodeticdatum = "'.$geoRefArr['geodeticdatum'].'"';
						$this->addOccurEdits('geodeticdatum',$geoRefArr['geodeticdatum'],$localStr);
					}
					if($geoRefArr['maximumelevationinmeters']){
						$sql .= ',maximumelevationinmeters = IF(minimumelevationinmeters IS NULL,'.$geoRefArr['maximumelevationinmeters'].',maximumelevationinmeters)';
						$this->addOccurEdits('maximumelevationinmeters',$geoRefArr['maximumelevationinmeters'],$localStr);
					}
					if($geoRefArr['minimumelevationinmeters']){
						$sql .= ',minimumelevationinmeters = IF(minimumelevationinmeters IS NULL,'.$geoRefArr['minimumelevationinmeters'].',minimumelevationinmeters)';
						$this->addOccurEdits('minimumelevationinmeters',$geoRefArr['minimumelevationinmeters'],$localStr);
					}
					$sql .= ' WHERE (collid = '.$this->collId.') AND (occid IN('.$localStr.'))';
					//echo $sql; exit;
					if(!$this->conn->query($sql)){
						$this->errorStr = 'ERROR batch updating coordinates: '.$this->conn->error;
						echo $this->errorStr;
					}
				}
			}
		}
	}

	private function addOccurEdits($fieldName, $fieldValue, $occidStr){
		$sql = 'INSERT INTO omoccuredits(occid, FieldName, FieldValueNew, FieldValueOld, appliedstatus, uid) '.
			'SELECT occid, "'.$fieldName.'", "'.$fieldValue.'", IFNULL('.$fieldName.',""), 1 as ap, '.$GLOBALS['SYMB_UID'].' FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND (occid IN('.$occidStr.')) ';
		if(strpos($fieldName,'elevationinmeters')) $sql .= 'AND (minimumelevationinmeters IS NULL)';
		//echo $sql.';<br/>';
		if(!$this->conn->query($sql)){
			$this->errorStr = 'ERROR batch updating coordinates: '.$this->conn->error;
			echo $this->errorStr;
		}
	}

	public function getCoordStatistics(){
		$retArr = array();
		$totalCnt = 0;
		$sql = 'SELECT COUNT(*) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$totalCnt = $r->cnt;
		}
		$rs->free();

		//Full count
		$sql2 = 'SELECT COUNT(occid) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND (decimalLatitude IS NULL) AND (georeferenceVerificationStatus IS NULL) ';
		if($rs2 = $this->conn->query($sql2)){
			if($r2 = $rs2->fetch_object()){
				$retArr['total'] = $r2->cnt;
				$retArr['percent'] = round($r2->cnt*100/$totalCnt,1);
			}
			$rs2->free();
		}

		return $retArr;
	}

	public function getGeorefClones($locality, $country, $state, $county){
		$occArr = array();
		$sql = 'SELECT count(occid) AS cnt, decimallatitude, decimallongitude, coordinateUncertaintyInMeters, georeferencedby '.
			'FROM omoccurrences '.
			'WHERE decimallatitude IS NOT NULL AND decimallongitude IS NOT NULL ';
		if(strlen($locality) > 95){
			$locality = substr($locality,0,95);
			$sql .= 'AND locality LIKE "'.trim($this->cleanInStr($locality), " .").'%" ';
		}
		else{
			$sql .= 'AND locality = "'.trim($this->cleanInStr($locality), " .").'" ';
		}
		if($country){
			$synArr = array('usa','u.s.a', 'united states','united states of america','u.s.');
			if(in_array($country,$synArr)){
				$country = implode('","',$synArr);
			}
			$sql .= 'AND (country IN("'.$this->cleanInStr($country).'")) ';
		}
		if($state){
			$sql .= 'AND (stateprovince = "'.$this->cleanInStr($state).'") ';
		}
		if($county){
			$county = str_ireplace(array(' county',' parish'),'',$county);
			$sql .= 'AND (county LIKE "'.$this->cleanInStr($county).'%") ';
		}
		$sql .= 'GROUP BY decimallatitude, decimallongitude '.
			'ORDER BY georeferencedBy DESC, cnt DESC';
		//echo '<div>'.$sql.'</div>'; exit;

		$rs = $this->conn->query($sql);
		$cnt = 0;
		$lat = 0;
		$lng = 0;
		while($r = $rs->fetch_object()){
			if($lat != $r->decimallatitude || $lng != $r->decimallongitude){
				$lat = $r->decimallatitude;
				$lng = $r->decimallongitude;
				$occArr[$cnt]['cnt'] = $r->cnt;
				$occArr[$cnt]['lat'] = $r->decimallatitude;
				$occArr[$cnt]['lng'] = $r->decimallongitude;
				$occArr[$cnt]['err'] = $r->coordinateUncertaintyInMeters;
				//$occArr[$cnt]['footprint'] = $r->footprintWKT;
				//$occArr[$cnt]['country'] = $r->country;
				//$occArr[$cnt]['state'] = $r->stateprovince;
				//$occArr[$cnt]['county'] = $r->county;
				$occArr[$cnt]['georefby'] = $r->georeferencedby;
				$cnt++;
			}
			else{
				$occArr[$cnt]['cnt'] = ($occArr[$cnt]['cnt']+$r->cnt);
				if($occArr[$cnt]['georefby'] != $r->georeferencedby) $occArr[$cnt]['georefby'] = $occArr[$cnt]['georefby'].', '.$r->georeferencedby;
			}
			if($cnt > 15) break;
		}
		$rs->free();
		return $occArr;
	}

	//Setters and getters
	public function setCollId($cid){
		if(is_numeric($cid)){
			$this->collId = $cid;
			$sql = 'SELECT collectionname, managementtype '.
				'FROM omcollections WHERE collid = '.$cid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collName = $r->collectionname;
				$this->managementType = $r->managementtype;
			}
			$rs->free();
		}
	}

	public function setQueryVariables($k,$v){
		$this->qryVars[$k] = $this->cleanInStr($v);
	}

	public function getCollName(){
		return $this->collName;
	}

	//Get data functions
	public function getCountryArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT country '.
			'FROM omoccurrences WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->country);
			if($cStr) $retArr[] = $cStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getStateArr($countryStr = ''){
		$retArr = array();
		$sql = 'SELECT DISTINCT stateprovince '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ';
		/*if($countryStr){
			$sql .= 'AND country = "'.$countryStr.'" ';
		}*/
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$sStr = trim($r->stateprovince);
			if($sStr) $retArr[] = $sStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getCountyArr($countryStr = '',$stateStr = ''){
		$retArr = array();
		$sql = 'SELECT DISTINCT county '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ';
		/*if($countryStr){
			$sql .= 'AND country = "'.$countryStr.'" ';
		}*/
		if($stateStr){
			$sql .= 'AND stateprovince = "'.$stateStr.'" ';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->county);
			if($cStr) $retArr[] = $cStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}
	
	public function getMunicipalityArr($countryStr = '',$stateStr = ''){
		$retArr = array();
		$sql = 'SELECT DISTINCT municipality '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ';
		/*if($countryStr){
			$sql .= 'AND country = "'.$countryStr.'" ';
		}*/
		if($stateStr){
			$sql .= 'AND stateprovince = "'.$stateStr.'" ';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$mStr = trim($r->municipality);
			if($mStr) $retArr[] = $mStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}
	
	public function getProcessingStatus(){
		$retArr = array();
		$sql = 'SELECT DISTINCT processingstatus '.
			'FROM omoccurrences '.
			'WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->processingstatus) $retArr[] = $r->processingstatus;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	//Misc functions
	private function cleanInArr($arr){
		$retArr = array();
		foreach($arr as $k => $v){
			$retArr[$k] = $this->cleanInStr($v);
		}
		return $retArr;
	}
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private static function _cmpLocCnt ($a, $b){
		$aCnt = $a['cnt'];
		$bCnt = $b['cnt'];
		if($aCnt == $bCnt){
			return 0;
		}
		return ($aCnt > $bCnt) ? -1 : 1;
	}
}
?>