<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class OccurrenceGeorefTools {

	private $conn;
	private $collId;
	private $collName;
	private $managementType;
	private $qryVars = array();

	function __construct($type = 'write') {
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getLocalityArr(){
		$retArr = array();
		if($this->collId){
			$sql = 'SELECT occid, country, stateprovince, county, locality, verbatimcoordinates ,decimallatitude, decimallongitude '.
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
					$sql .= 'AND (county LIKE "'.$this->qryVars['qcounty'].'%") ';
				}
				else{
					$orderBy .= 'county,';
				}
				if(array_key_exists('qlocality',$this->qryVars) && $this->qryVars['qlocality']){
					$sql .= 'AND (locality LIKE "%'.$this->qryVars['qlocality'].'%") ';
				}
			}
			$sql .= 'ORDER BY '.$orderBy.'locality,verbatimcoordinates ';
			//echo $sql;
			$totalCnt = 0;
			$locCnt = 1;
			$countryStr='';$stateStr='';$countyStr='';$localityStr='';$verbCoordStr = '';$decLatStr='';$decLngStr='';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($countryStr != trim($r->country) || $stateStr != trim($r->stateprovince) || $countyStr != trim($r->county)  
					|| $localityStr != trim($r->locality," .,;") || $verbCoordStr != trim($r->verbatimcoordinates)
					|| $decLatStr != $r->decimallatitude || $decLngStr != $r->decimallongitude){
					$countryStr = trim($r->country);
					$stateStr = trim($r->stateprovince);
					$countyStr = trim($r->county);
					$localityStr = trim($r->locality," .,;");
					$verbCoordStr = trim($r->verbatimcoordinates);
					$decLatStr = $r->decimallatitude;
					$decLngStr = $r->decimallongitude;
					$totalCnt++;
					$retArr[$totalCnt]['occid'] = $r->occid;
					$retArr[$totalCnt]['country'] = $countryStr;
					$retArr[$totalCnt]['stateprovince'] = $stateStr;
					$retArr[$totalCnt]['county'] = $countyStr;
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
			$rs->close();
		}
		//usort($retArr,array('OccurrenceGeorefTools', '_cmpLocCnt'));
		return $retArr;
	}

	public function updateCoordinates($geoRefArr){
		global $paramsArr;
		if($this->collId){
			if($geoRefArr['decimallatitude'] && is_numeric($geoRefArr['decimallatitude']) && $geoRefArr['decimallongitude'] && is_numeric($geoRefArr['decimallongitude'])){
				set_time_limit(1000);
				$localList = $geoRefArr['locallist'];
				$localStr = $this->cleanInStr(implode(',',$localList));
				if($localStr){
					if($this->managementType == 'Snapshot'){
						//Presevre coordinate data in omoccuredits; if collection refreshes their data, coordinates will ntoe be lost 
						$newValueArr = array($geoRefArr['decimallatitude'],$geoRefArr['decimallongitude'],$geoRefArr['coordinateuncertaintyinmeters'],
							$geoRefArr['geodeticdatum'],$geoRefArr['georeferencesources'],$geoRefArr['georeferenceremarks'],
							$geoRefArr['georeferenceverificationstatus'],$geoRefArr['minimumelevationinmeters'],$geoRefArr['maximumelevationinmeters']);
						$newValueStr = $this->cleanInStr(json_encode($newValueArr));
						$sql = 'INSERT INTO omoccuredits(occid, FieldName, FieldValueNew, FieldValueOld, appliedstatus, uid) '.
							"SELECT occid, 'georefbatchstr', '".$newValueStr."', CONCAT_WS(',',decimallatitude, decimallongitude, coordinateUncertaintyInMeters, ".
							'geodeticdatum, georeferencesources, georeferenceRemarks, georeferenceVerificationStatus, '. 
							'minimumElevationInMeters, maximumElevationInMeters) AS oldvalue, 1, '.$paramsArr['uid'].' '.
							'FROM omoccurrences WHERE (collid = '.$this->collId.') AND (occid IN('.$localStr.'))';
						//echo 'sql: '.$sql.'<br/>';
						$this->conn->query($sql);
					}
					
					//Update coordinates
					$sql = 'UPDATE omoccurrences '.
						'SET decimallatitude = '.$geoRefArr['decimallatitude'].', decimallongitude = '.$geoRefArr['decimallongitude'].
						',georeferencedBy = "'.$this->cleanInStr($geoRefArr['georefby']).'"';
					if($geoRefArr['georeferenceverificationstatus']){
						$sql .= ',georeferenceverificationstatus = "'.$this->cleanInStr($geoRefArr['georeferenceverificationstatus']).'"';
					}
					if($geoRefArr['georeferencesources']){
						$sql .= ',georeferencesources = "'.$this->cleanInStr($geoRefArr['georeferencesources']).'"';
					}
					if($geoRefArr['georeferenceremarks']){
						$sql .= ',georeferenceremarks = CONCAT_WS("; ",georeferenceremarks,"'.$this->cleanInStr($geoRefArr['georeferenceremarks']).'")';
					}
					if($geoRefArr['coordinateuncertaintyinmeters']){
						$sql .= ',coordinateuncertaintyinmeters = '.$this->cleanInStr($geoRefArr['coordinateuncertaintyinmeters']);
					}
					if($geoRefArr['geodeticdatum']){
						$sql .= ', geodeticdatum = "'.$this->cleanInStr($geoRefArr['geodeticdatum']).'"';
					}
					if($geoRefArr['maximumelevationinmeters']){
						$sql .= ',maximumelevationinmeters = IF(minimumelevationinmeters IS NULL,'.$this->cleanInStr($geoRefArr['maximumelevationinmeters']).',maximumelevationinmeters)';
					}
					if($geoRefArr['minimumelevationinmeters']){
						$sql .= ',minimumelevationinmeters = IF(minimumelevationinmeters IS NULL,'.$this->cleanInStr($geoRefArr['minimumelevationinmeters']).',minimumelevationinmeters)';
					}
					$sql .= ' WHERE (collid = '.$this->collId.') AND (occid IN('.$localStr.'))';
					//echo $sql;
					$this->conn->query($sql);
				}
			}
		}
	}

	public function getCoordStatistics(){
		$retArr = array();
		$totalCnt = 0;
		$sql = 'SELECT COUNT(occid) AS cnt '. 
			'FROM omoccurrences '. 
			'WHERE (collid = '.$this->collId.')'; 
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$totalCnt = $r->cnt;
		}
		$rs->close();
		
		$sql = 'SELECT COUNT(occid) AS cnt '. 
			'FROM omoccurrences '. 
			'WHERE (collid = '.$this->collId.') AND (decimalLatitude IS NULL) AND (georeferenceVerificationStatus IS NULL) ';
		$k = '';
		$limitedSql = '';
		if($this->qryVars){
			if(array_key_exists('qcounty',$this->qryVars)){
				$limitedSql = 'AND county = "'.$this->qryVars['qcounty'].'" ';
				$k = $this->qryVars['qcounty'];
			}
			elseif(array_key_exists('qstate',$this->qryVars)){
				$limitedSql = 'AND stateprovince = "'.$this->qryVars['qstate'].'" ';
				$k = $this->qryVars['qstate'];
			}
			elseif(array_key_exists('qcountry',$this->qryVars)){
				$limitedSql = 'AND country = "'.$this->qryVars['qcountry'].'" ';
				$k = $this->qryVars['qcountry'];
			}
		}
		//Count limited to country, state, or county
		if($k){
			if($rs = $this->conn->query($sql.$limitedSql)){
				if($r = $rs->fetch_object()){
					$retArr[$k] = $r->cnt;
				}
				$rs->close();
			}
		}
		//Full count
		if($rs = $this->conn->query($sql)){
			if($r = $rs->fetch_object()){
				$retArr['Total Number'] = $r->cnt;
				$retArr['Total Percentage'] = round($r->cnt*100/$totalCnt,1);
			}
			$rs->close();
		}
		
		return $retArr;
	} 

	public function getGeorefClones($locality, $country, $state, $county){
		$occArr = array();
		$sql = 'SELECT count(occid) AS cnt, decimallatitude, decimallongitude, coordinateUncertaintyInMeters, country, stateprovince, county, georeferencedby '. 
			'FROM omoccurrences '. 
			'WHERE decimallatitude IS NOT NULL AND decimallongitude IS NOT NULL AND locality = "'.$this->cleanInStr($locality).'" ';
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
			'ORDER BY georeferencedBy DESC, count(occid) DESC';
		//echo '<div>'.$sql.'</div>';
		
		$rs = $this->conn->query($sql);
		$cnt = 0;
		$lat = 0;
		$lng = 0;
		while($r = $rs->fetch_object()){
			if($lat != $r->decimallatitude && $lng != $r->decimallongitude){
				$lat = $r->decimallatitude;
				$lng = $r->decimallongitude;
				$occArr[$cnt]['cnt'] = $r->cnt;
				$occArr[$cnt]['lat'] = $r->decimallatitude;
				$occArr[$cnt]['lng'] = $r->decimallongitude;
				$occArr[$cnt]['err'] = $r->coordinateUncertaintyInMeters;
				$occArr[$cnt]['country'] = $r->country;
				$occArr[$cnt]['state'] = $r->stateprovince;
				$occArr[$cnt]['county'] = $r->county;
				$occArr[$cnt]['georefby'] = $r->georeferencedby;
				$cnt++;
			}
			else{
				$occArr[$cnt]['cnt'] = ($occArr[$cnt]['cnt']+$r->cnt);
				if($occArr[$cnt]['georefby'] != $r->georeferencedby) $occArr[$cnt]['georefby'] = $occArr[$cnt]['georefby'].', '.$r->georeferencedby;
			}
		}
		$rs->free();
		return $occArr;
	}
	
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
			$rs->close();
		}
	}

	public function setQueryVariables($k,$v){
		$this->qryVars[$k] = $this->cleanInStr($v);
	}

	public function getCollName(){
		return $this->collName;
	}

	public function getCountryArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT country '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ORDER BY country';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->country);
			if($cStr) $retArr[] = $cStr;
		}
		$rs->close();
		return $retArr;
	}
	
	public function getStateArr($countryStr = ''){
		$retArr = array();
		$sql = 'SELECT DISTINCT stateprovince '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ';
		/*if($countryStr){
			$sql .= 'AND country = "'.$countryStr.'" ';
		}*/
		$sql .= 'ORDER BY stateprovince';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$sStr = trim($r->stateprovince);
			if($sStr) $retArr[] = $sStr;
		}
		$rs->close();
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
		$sql .= 'ORDER BY county';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->county);
			if($cStr) $retArr[] = $cStr;
		}
		$rs->close();
		return $retArr;
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
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