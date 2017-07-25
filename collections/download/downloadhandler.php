<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDownload.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
ini_set('max_execution_time', 300); //180 seconds = 5 minutes

$schema = array_key_exists("schema",$_POST)?$_POST["schema"]:"symbiota"; 
$cSet = array_key_exists("cset",$_POST)?$_POST["cset"]:'';
$taxonFilterCode = array_key_exists("taxonFilterCode",$_POST)?$_POST["taxonFilterCode"]:0;
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

$dlManager = new OccurrenceDownload();
$dwcaHandler = new DwcArchiverCore();
$occurManager = new OccurrenceManager();
$solrManager = new SOLRManager();

$occWhereStr = '';

if($stArrCollJson && $stArrSearchJson){
	$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
	$collStArr = json_decode($stArrCollJson, true);
	$searchStArr = json_decode($stArrSearchJson, true);
	$stArr = array_merge($searchStArr,$collStArr);
	$occurManager->setSearchTermsArr($stArr);

    if($SOLR_MODE){
        $solrManager->setSearchTermsArr($stArr);
        if($schema == 'checklist'){
            if($taxonFilterCode){
                $solrArr = $solrManager->getTaxaArr();
                $tidArr = $solrManager->getSOLRTidList($solrArr);
                $dlManager->setTidArr($tidArr);
            }
            else{
                $occArr = $solrManager->getOccArr();
                $dlManager->setOccArr($occArr);
            }
        }
        elseif($schema == 'georef'){
            $occArr = $solrManager->getOccArr(true);
            $dlManager->setOccArr($occArr);
        }
        elseif(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
            $occArr = $solrManager->getOccArr();
            if($occArr){
                $occWhereStr = 'WHERE o.occid IN('.implode(',',$occArr).') ';
            }
        }
    }
}

if($schema == "backup"){
    $collid = $_POST["collid"];
	if($collid && is_numeric($collid)){
		//check permissions due to sensitive localities not being redacted
		if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
			$dwcaHandler->setSchemaType('backup');
			$dwcaHandler->setCharSetOut($cSet);
			$dwcaHandler->setVerboseMode(0);
			$dwcaHandler->setIncludeDets(1);
			$dwcaHandler->setIncludeImgs(1);
			$dwcaHandler->setIncludeAttributes(1);
			$dwcaHandler->setRedactLocalities(0);
			$dwcaHandler->setCollArr($collid);
			
			$archiveFile = $dwcaHandler->createDwcArchive();
			
			if($archiveFile){
				//ob_start();
				header('Content-Description: Symbiota Occurrence Backup File (DwC-Archive data package)');
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename='.basename($archiveFile));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($archiveFile));
				//od_end_clean();
				readfile($archiveFile);
				unlink($archiveFile);
			}
			else{
				echo 'ERROR creating output file. Query probably did not include any records.';
			}
		}
	}
}
else{
	$zip = (array_key_exists('zip',$_POST)?$_POST['zip']:0);
	$format = $_POST['format'];
	$extended = (array_key_exists('extended',$_POST)?$_POST['extended']:0);

	$redactLocalities = 1;
	$rareReaderArr = array();
	if($IS_ADMIN || array_key_exists("CollAdmin", $USER_RIGHTS)){
		$redactLocalities = 0;
	}
	elseif(array_key_exists("RareSppAdmin", $USER_RIGHTS) || array_key_exists("RareSppReadAll", $USER_RIGHTS)){
		$redactLocalities = 0;
	}
	else{
		if(array_key_exists('CollEditor', $USER_RIGHTS)){
			$rareReaderArr = $USER_RIGHTS['CollEditor'];
		}
		if(array_key_exists('RareSppReader', $USER_RIGHTS)){
			$rareReaderArr = array_unique(array_merge($rareReaderArr,$USER_RIGHTS['RareSppReader']));
		}
	}
	if($schema == "georef"){
		if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
			$dlManager->setSqlWhere($occurManager->getSqlWhere());
		}
		$dlManager->setSchemaType($schema);
		$dlManager->setExtended($extended);
		$dlManager->setCharSetOut($cSet);
		$dlManager->setDelimiter($format);
		$dlManager->setZipFile($zip);
		$dlManager->addCondition('decimalLatitude','NOTNULL','');
		$dlManager->addCondition('decimalLongitude','NOTNULL','');
		if(array_key_exists('targetcollid',$_POST) && $_POST['targetcollid']){
			$dlManager->addCondition('collid','EQUALS',$_POST['targetcollid']);
		}
		if(array_key_exists('processingstatus',$_POST) && $_POST['processingstatus']){
			$dlManager->addCondition('processingstatus','EQUALS',$_POST['processingstatus']);
		}
		if(array_key_exists('customfield1',$_POST) && $_POST['customfield1']){
			$dlManager->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
		}
		$dlManager->downloadData();
	}
	elseif($schema == 'checklist'){
		if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
			$dlManager->setSqlWhere($occurManager->getSqlWhere());
		}
		$dlManager->setSchemaType($schema);
		$dlManager->setCharSetOut($cSet);
		$dlManager->setDelimiter($format);
		$dlManager->setZipFile($zip);
		$dlManager->setTaxonFilter($taxonFilterCode);
		$dlManager->downloadData();
	}
	else{
		$dwcaHandler->setVerboseMode(0);
		if($schema == "coge"){
			$dwcaHandler->setCollArr($_POST["collid"]);
			$dwcaHandler->setCharSetOut('UTF-8');
			$dwcaHandler->setSchemaType('coge');
			$dwcaHandler->setExtended(false);
			$dwcaHandler->setDelimiter('csv');
			$dwcaHandler->setRedactLocalities(0);
			$dwcaHandler->setIncludeDets(0);
			$dwcaHandler->setIncludeImgs(0);
			$dwcaHandler->setIncludeAttributes(0);
			$dwcaHandler->addCondition('decimallatitude','NULL');
			$dwcaHandler->addCondition('decimallongitude','NULL');
			$dwcaHandler->addCondition('catalognumber','NOTNULL');
			$dwcaHandler->addCondition('locality','NOTNULL');
			if(array_key_exists('processingstatus',$_POST) && $_POST['processingstatus']){
				$dwcaHandler->addCondition('processingstatus','EQUALS',$_POST['processingstatus']);
			}
			if(array_key_exists('customfield1',$_POST) && $_POST['customfield1']){
				$dwcaHandler->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
			}
			if(array_key_exists('customfield2',$_POST) && $_POST['customfield2']){
				$dwcaHandler->addCondition($_POST['customfield2'],$_POST['customtype2'],$_POST['customvalue2']);
			}
		}
		else{
			//Is an occurrence download 
			$dwcaHandler->setCharSetOut($cSet);
			$dwcaHandler->setSchemaType($schema);
			$dwcaHandler->setExtended($extended);
			$dwcaHandler->setDelimiter($format);
			$dwcaHandler->setRedactLocalities($redactLocalities);
			if($rareReaderArr) $dwcaHandler->setRareReaderArr($rareReaderArr);
				
			if(array_key_exists("publicsearch",$_POST) && $_POST["publicsearch"]){
                if($SOLR_MODE && $occWhereStr){
                    $dwcaHandler->setCustomWhereSql($occWhereStr);
                }
                else{
                    $dwcaHandler->setCustomWhereSql($occurManager->getSqlWhere());
                }
			}
			else{
				//Request is coming from exporter.php for collection manager tools
				$dwcaHandler->setCollArr($_POST['targetcollid']);
				if(array_key_exists('processingstatus',$_POST) && $_POST['processingstatus']){
					$dwcaHandler->addCondition('processingstatus','EQUALS',$_POST['processingstatus']);
				}
				if(array_key_exists('customfield1',$_POST) && $_POST['customfield1']){
					$dwcaHandler->addCondition($_POST['customfield1'],$_POST['customtype1'],$_POST['customvalue1']);
				}
				if(array_key_exists('customfield2',$_POST) && $_POST['customfield2']){
					$dwcaHandler->addCondition($_POST['customfield2'],$_POST['customtype2'],$_POST['customvalue2']);
				}
				if(array_key_exists('customfield3',$_POST) && $_POST['customfield3']){
					$dwcaHandler->addCondition($_POST['customfield3'],$_POST['customtype3'],$_POST['customvalue3']);
				}
				if(array_key_exists('stateid',$_POST) && $_POST['stateid']){
					$dwcaHandler->addCondition('stateid','EQUALS',$_POST['stateid']);
				}
				elseif(array_key_exists('traitid',$_POST) && $_POST['traitid']){
					$dwcaHandler->addCondition('traitid','EQUALS',$_POST['traitid']);
				}
				if(array_key_exists('newrecs',$_POST) && $_POST['newrecs'] == 1){
					$dwcaHandler->addCondition('dbpk','NULL');
					$dwcaHandler->addCondition('catalognumber','NOTNULL');
				}
			}
		}
		$outputFile = null;
		if($zip){
			//Ouput file is a zip file
			$includeIdent = (array_key_exists('identifications',$_POST)?1:0);
			$dwcaHandler->setIncludeDets($includeIdent);
			$includeImages = (array_key_exists('images',$_POST)?1:0);
			$dwcaHandler->setIncludeImgs($includeImages);
			$includeAttributes = (array_key_exists('attributes',$_POST)?1:0);
			$dwcaHandler->setIncludeAttributes($includeAttributes);

			$outputFile = $dwcaHandler->createDwcArchive('webreq');
			
		}
		else{
			//Output file is a flat occurrence file (not a zip file)
			$outputFile = $dwcaHandler->getOccurrenceFile();
		}
		if($outputFile){
			//ob_start();
			$contentDesc = '';
			if($schema == 'dwc'){
				$contentDesc = 'Darwin Core ';
			}
			else{
				$contentDesc = 'Symbiota ';
			}
			$contentDesc .= 'Occurrence ';
			if($zip){
				$contentDesc .= 'Archive ';
			}
			$contentDesc .= 'File';
			header('Content-Description: '.$contentDesc);
			
			if($zip){
				header('Content-Type: application/zip');
			}
			elseif($format == 'csv'){
				header('Content-Type: text/csv; charset='.$CHARSET);
			}
			else{
				header('Content-Type: text/html; charset='.$CHARSET);
			}
			
			header('Content-Disposition: attachment; filename='.basename($outputFile));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($outputFile));
			ob_clean();
			flush();
			//od_end_clean();
			readfile($outputFile);
			unlink($outputFile);
		}
		else{
			header("Content-type: text/plain");
			header("Content-Disposition: attachment; filename=NoData.txt");
			echo 'The query failed to return records. Please modify query criteria and try again.';
		}
	}
}
?>