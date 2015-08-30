<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
$famArrJson = array_key_exists("famarrjson",$_REQUEST)?$_REQUEST["famarrjson"]:'';
$geoArrJson = array_key_exists("geoarrjson",$_REQUEST)?$_REQUEST["geoarrjson"]:'';
$collId = array_key_exists("collids",$_REQUEST)?$_REQUEST["collids"]:'';

$collManager = new CollectionProfileManager();

$fileName = '';
$outputArr = array();
$header = array('Names','SpecimenCount','GeorefCount','IDCount','IDGeorefCount','GeorefPercent','IDPercent','IDGeorefPercent');
if($action == 'Download Family Dist'){
	$fileName = 'stats_family.csv';
	$famArr = json_decode($famArrJson,true);
	foreach($famArr as $name => $data){
		$specCnt = $data['SpecimensPerFamily'];
		$geoRefCnt = $data['GeorefSpecimensPerFamily'];
		$IDCnt = $data['IDSpecimensPerFamily'];
		$IDGeoRefCnt = $data['IDGeorefSpecimensPerFamily'];
		$geoRefPerc = ($data['GeorefSpecimensPerFamily']?round(100*($data['GeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])):0);
		$IDPerc = ($data['IDSpecimensPerFamily']?round(100*($data['IDSpecimensPerFamily']/$data['SpecimensPerFamily'])):0);
		$IDgeoRefPerc = ($data['IDGeorefSpecimensPerFamily']?round(100*($data['IDGeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])):0);
		array_push($outputArr,array($name,$specCnt,$geoRefCnt,$IDCnt,$IDGeoRefCnt,$geoRefPerc,$IDPerc,$IDgeoRefPerc));
	}
}
if($action == 'Download Geo Dist'){
	$fileName = 'stats_country.csv';
	$geoArr = json_decode($geoArrJson,true);
	foreach($geoArr as $name => $data){
		$specCnt = $data['CountryCount'];
		$geoRefCnt = $data['GeorefSpecimensPerCountry'];
		$IDCnt = $data['IDSpecimensPerCountry'];
		$IDGeoRefCnt = $data['IDGeorefSpecimensPerCountry'];
		$geoRefPerc = ($data['GeorefSpecimensPerCountry']?round(100*($data['GeorefSpecimensPerCountry']/$data['CountryCount'])):0);
		$IDPerc = ($data['IDSpecimensPerCountry']?round(100*($data['IDSpecimensPerCountry']/$data['CountryCount'])):0);
		$IDgeoRefPerc = ($data['IDGeorefSpecimensPerCountry']?round(100*($data['IDGeorefSpecimensPerCountry']/$data['CountryCount'])):0);
		array_push($outputArr,array($name,$specCnt,$geoRefCnt,$IDCnt,$IDGeoRefCnt,$geoRefPerc,$IDPerc,$IDgeoRefPerc));
	}
}
if($action == 'Download CSV'){
	$fileName = 'year_stats.csv';
	$headerArr = $collManager->getYearStatsHeaderArr($collId);
	$dataArr = $collManager->getYearStatsDataArr($collId);
}

header ('Content-Type: text/csv');
header ("Content-Disposition: attachment; filename=\"$fileName\""); 

//Write column names out to file
if($action == 'Download Family Dist' || $action == 'Download Geo Dist'){
	if($outputArr){
		$outstream = fopen("php://output", "w");
		fputcsv($outstream,$header);
		
		foreach($outputArr as $row){
			fputcsv($outstream,$row);
		}
		fclose($outstream);
	}
	else{
		echo "Recordset is empty.\n";
	}
}
if($action == 'Download CSV'){
	if($dataArr){
		$outputArr = array();
		$i = 0;
		foreach($dataArr as $code => $data){
			$outputArr[$i]['name'] = $data['collectionname'];
			$outputArr[$i]['object'] = 'Specimens';
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('speccnt',$data['stats'][$month])){
						$outputArr[$i][$month] = $data['stats'][$month]['speccnt'];
					}
					else{
						$outputArr[$i][$month] = 0;
					}
				}
				else{
					$outputArr[$i][$month] = 0;
				}
			}
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Stage 1';
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('stage1Count',$data['stats'][$month])){
						$outputArr[$i][$month] = $data['stats'][$month]['stage1Count'];
					}
					else{
						$outputArr[$i][$month] = 0;
					}
				}
				else{
					$outputArr[$i][$month] = 0;
				}
			}
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Stage 2';
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('stage2Count',$data['stats'][$month])){
						$outputArr[$i][$month] = $data['stats'][$month]['stage2Count'];
					}
					else{
						$outputArr[$i][$month] = 0;
					}
				}
				else{
					$outputArr[$i][$month] = 0;
				}
			}
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Stage 3';
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('stage3Count',$data['stats'][$month])){
						$outputArr[$i][$month] = $data['stats'][$month]['stage3Count'];
					}
					else{
						$outputArr[$i][$month] = 0;
					}
				}
				else{
					$outputArr[$i][$month] = 0;
				}
			}
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Images';
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('imgcnt',$data['stats'][$month])){
						$outputArr[$i][$month] = $data['stats'][$month]['imgcnt'];
					}
					else{
						$outputArr[$i][$month] = 0;
					}
				}
				else{
					$outputArr[$i][$month] = 0;
				}
			}
			$i++;
		}
		
		array_unshift($headerArr,"Institution","Object");
		
		$outstream = fopen("php://output", "w");
		fputcsv($outstream,$headerArr);
		
		foreach($outputArr as $row){
			fputcsv($outstream,$row);
		}
		fclose($outstream);
	}
	else{
		echo "Recordset is empty.\n";
	}
}