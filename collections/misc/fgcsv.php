<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/FieldGuideManager.php');

$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$resultId = array_key_exists("resid",$_REQUEST)?$_REQUEST["resid"]:0;
$viewMode = array_key_exists("viewmode",$_REQUEST)?$_REQUEST["viewmode"]:'full';

$apiManager = new FieldGuideManager();
$resultArr = array();
$fileName = '';
$outputArr = array();
$start = 0;
$limit = 0;

$apiManager->setCollID($collId);
if($resultId){
    $apiManager->setJobID($resultId);
    $apiManager->setViewMode($viewMode);
    $apiManager->setRecLimit($limit);
    $apiManager->setRecStart($start);
    $apiManager->primeFGResults();
    $apiManager->processFGResults();
    $resultArr = $apiManager->getResults();
    $tidArr = $apiManager->getTids();

    $headerArr = array('RecordID','CurrentIdentification','Family','ImageID','ImageURL','FieldguideIdentification','Note');
    $fileName = $resultId.'_fieldguide_results.csv';

    header ('Content-Type: text/csv');
    header ("Content-Disposition: attachment; filename=\"$fileName\"");

    if($resultArr){
        $outputArr = array();
        $i = 0;
        foreach($resultArr as $occId => $occArr){
            if($prevOccId != $occId){
                $prevOccId = $occId;
                $setCnt++;
                $firstOcc = true;
                $firstRadio = true;
                $recResults = false;
                $currID = $occArr['sciname'];
                $family = $occArr['family'];
                unset($occArr['sciname']);
                unset($occArr['family']);
                foreach($occArr as $imgId => $imgArr){
                    if($imgArr['results']) $recResults = true;
                }
            }
            foreach($occArr as $imgId => $imgArr){
                if($prevImgId != $imgId){
                    $prevImgId = $imgId;
                    $imgurl = $imgArr['url'];
                    $fgStatus = $imgArr['status'];
                    $fgidarr = $imgArr['results'];
                    $firstImg = true;
                }
                if($fgidarr){
                    foreach($fgidarr as $name){
                        $valid = false;
                        $note = '';
                        $tId = 0;
                        if(array_key_exists($name,$tidArr) && $tidArr[$name]){
                            if($currID == $name){
                                $note = 'Current determination';
                            }
                            else{
                                if(count($tidArr[$name]) == 1){
                                    $valid = true;
                                    $tId = $tidArr[$name][0];
                                }
                                else{
                                    $note = 'Name ambiguous';
                                }
                            }
                        }
                        else{
                            $note = 'Not valid in thesaurus';
                        }
                        if($firstOcc) $outputArr[$i]['recid'] = $occId;
                        else $outputArr[$i]['recid'] = '';
                        if($firstOcc) $outputArr[$i]['currid'] = $currID;
                        else $outputArr[$i]['currid'] = '';
                        if($firstOcc) $outputArr[$i]['family'] = $family;
                        else $outputArr[$i]['family'] = '';
                        if($firstImg){
                            $outputArr[$i]['imgid'] = $imgId;
                            $outputArr[$i]['imgurl'] = $imgurl;
                        }
                        else{
                            $outputArr[$i]['imgid'] = '';
                            $outputArr[$i]['imgurl'] = '';
                        }
                        $outputArr[$i]['fgid'] = $name;
                        $outputArr[$i]['note'] = $note;
                        $firstOcc = false;
                        $firstImg = false;
                        if($valid) $firstRadio = false;
                        $i++;
                    }
                }
                elseif($viewMode == 'full'){
                    $note = '';
                    if($fgStatus == 'OK' && !$fgidarr){
                        $note = 'No results provided.';
                    }
                    if($firstOcc) $outputArr[$i]['name'] = $occId;
                    else $outputArr[$i]['name'] = '';
                    if($firstOcc) $outputArr[$i]['currid'] = $currID;
                    else $outputArr[$i]['name'] = '';
                    if($firstOcc) $outputArr[$i]['family'] = $family;
                    else $outputArr[$i]['family'] = '';
                    if($firstImg){
                        $outputArr[$i]['imgid'] = $imgId;
                        $outputArr[$i]['imgurl'] = $imgurl;
                    }
                    else{
                        $outputArr[$i]['imgid'] = '';
                        $outputArr[$i]['imgurl'] = '';
                    }
                    $outputArr[$i]['fgid'] = '';
                    $outputArr[$i]['note'] = $note;
                    $firstOcc = false;
                    $firstImg = false;
                    $i++;
                }
            }
        }

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
?>