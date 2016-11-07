<?php
//error_reporting(E_ALL);
ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
include_once($serverRoot.'/classes/OccurrenceManager.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
$famArrJson = array_key_exists("famarrjson",$_REQUEST)?$_REQUEST["famarrjson"]:'';
$orderArrJson = array_key_exists("orderarrjson",$_REQUEST)?$_REQUEST["orderarrjson"]:'';
$geoArrJson = array_key_exists("geoarrjson",$_REQUEST)?$_REQUEST["geoarrjson"]:'';
$collId = array_key_exists("collids",$_REQUEST)?$_REQUEST["collids"]:'';

$collManager = new CollectionProfileManager();

$fileName = '';
$outputArr = array();
if($action == 'Download Family Dist' || $action == 'Download Geo Dist' || $action == 'Download Order Dist'){
	$header = array('Names','SpecimenCount','GeorefCount','IDCount','IDGeorefCount','GeorefPercent','IDPercent','IDGeorefPercent');
	if($action == 'Download Family Dist'){
		$fileName = 'stats_family.csv';
		$famArr = json_decode($famArrJson,true);
		if(is_array($famArr)){
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
	}
    if($action == 'Download Order Dist'){
        $fileName = 'stats_order.csv';
        $ordArr = json_decode($orderArrJson,true);
        if(is_array($ordArr)){
            foreach($ordArr as $name => $data){
                $specCnt = $data['SpecimensPerOrder'];
                $geoRefCnt = $data['GeorefSpecimensPerOrder'];
                $IDCnt = $data['IDSpecimensPerOrder'];
                $IDGeoRefCnt = $data['IDGeorefSpecimensPerOrder'];
                $geoRefPerc = ($data['GeorefSpecimensPerOrder']?round(100*($data['GeorefSpecimensPerOrder']/$data['SpecimensPerOrder'])):0);
                $IDPerc = ($data['IDSpecimensPerOrder']?round(100*($data['IDSpecimensPerOrder']/$data['SpecimensPerOrder'])):0);
                $IDgeoRefPerc = ($data['IDGeorefSpecimensPerOrder']?round(100*($data['IDGeorefSpecimensPerOrder']/$data['SpecimensPerOrder'])):0);
                array_push($outputArr,array($name,$specCnt,$geoRefCnt,$IDCnt,$IDGeoRefCnt,$geoRefPerc,$IDPerc,$IDgeoRefPerc));
            }
        }
    }
	if($action == 'Download Geo Dist'){
		$fileName = 'stats_country.csv';
		$geoArr = json_decode($geoArrJson,true);
		if(is_array($geoArr)){
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
	}
}
if($action == 'Download CSV'){
	$fileName = 'year_stats.csv';
	$headerArr = $collManager->getYearStatsHeaderArr($collId);
	$dataArr = $collManager->getYearStatsDataArr($collId);
}
if($action == 'Download Stats per Coll'){
	$header = array('Collection','Specimens','Georeferenced','Imaged','Species ID','Families','Genera','Species','Total Taxa','Types');
	$fileName = 'stats_per_coll.csv';
	$resultsTemp = $collManager->runStatistics($collId);
	if($resultsTemp){
		unset($resultsTemp['familycnt']);
		unset($resultsTemp['genuscnt']);
		unset($resultsTemp['speciescnt']);
		unset($resultsTemp['TotalTaxaCount']);
		unset($resultsTemp['TotalImageCount']);
		ksort($resultsTemp);
		$i = 0;
		foreach($resultsTemp as $k => $collArr){
			$dynPropTempArr = array();
			$outputArr[$i]['CollectionName'] = $collArr['CollectionName'];
			$outputArr[$i]['recordcnt'] = $collArr['recordcnt'];
			$outputArr[$i]['georefcnt'] = $collArr['georefcnt'];
			$outputArr[$i]['OccurrenceImageCount'] = $collArr['OccurrenceImageCount'];
			if($collArr['dynamicProperties']){
				$dynPropTempArr = json_decode($collArr['dynamicProperties'],true);
				if(is_array($dynPropTempArr)){
					$outputArr[$i]['SpecimensCountID'] = $dynPropTempArr['SpecimensCountID'];
				}
			}
			$outputArr[$i]['familycnt'] = $collArr['familycnt'];
			$outputArr[$i]['genuscnt'] = $collArr['genuscnt'];
			$outputArr[$i]['speciescnt'] = $collArr['speciescnt'];
			$outputArr[$i]['TotalTaxaCount'] = $collArr['TotalTaxaCount'];
			if($collArr['dynamicProperties']){
				if(is_array($dynPropTempArr)){
					$outputArr[$i]['TypeCount'] = $dynPropTempArr['TypeCount'];
				}
			}
			$i++;
		}
	}
}

header ('Content-Type: text/csv');
header ("Content-Disposition: attachment; filename=\"$fileName\"");

//Write column names out to file
if($action == 'Download Family Dist' || $action == 'Download Geo Dist' || $action == 'Download Order Dist'){
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
if($action == 'Download Stats per Coll'){
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
			$total = 0;
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('speccnt',$data['stats'][$month])){
						$total = $total + $data['stats'][$month]['speccnt'];
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
			$outputArr[$i]['total'] = $total;
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Unprocessed';
			$total = 0;
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('unprocessedCount',$data['stats'][$month])){
						$total = $total + $data['stats'][$month]['unprocessedCount'];
						$outputArr[$i][$month] = $data['stats'][$month]['unprocessedCount'];
					}
					else{
						$outputArr[$i][$month] = 0;
					}
				}
				else{
					$outputArr[$i][$month] = 0;
				}
			}
			$outputArr[$i]['total'] = $total;
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Stage 1';
			$total = 0;
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('stage1Count',$data['stats'][$month])){
						$total = $total + $data['stats'][$month]['stage1Count'];
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
			$outputArr[$i]['total'] = $total;
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Stage 2';
			$total = 0;
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('stage2Count',$data['stats'][$month])){
						$total = $total + $data['stats'][$month]['stage2Count'];
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
			$outputArr[$i]['total'] = $total;
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Stage 3';
			$total = 0;
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('stage3Count',$data['stats'][$month])){
						$total = $total + $data['stats'][$month]['stage3Count'];
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
			$outputArr[$i]['total'] = $total;
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Images';
			$total = 0;
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('imgcnt',$data['stats'][$month])){
						$total = $total + $data['stats'][$month]['imgcnt'];
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
			$outputArr[$i]['total'] = $total;
			$i++;
			$outputArr[$i]['name'] = '';
			$outputArr[$i]['object'] = 'Georeferenced';
			$total = 0;
			foreach($headerArr as $h => $month){
				if(array_key_exists($month,$data['stats'])){
					if(array_key_exists('georcnt',$data['stats'][$month])){
						$total = $total + $data['stats'][$month]['georcnt'];
						$outputArr[$i][$month] = $data['stats'][$month]['georcnt'];
					}
					else{
						$outputArr[$i][$month] = 0;
					}
				}
				else{
					$outputArr[$i][$month] = 0;
				}
			}
			$outputArr[$i]['total'] = $total;
			$i++;
		}

		array_unshift($headerArr,"Institution","Object");
		array_push($headerArr,"Total");

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