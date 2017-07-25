<?php
include_once('../../config/symbini.php');
include_once('../../config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/SpatialModuleManager.php');
$con = MySQLiConnectionFactory::getCon("readonly");

$spatialManager = new SpatialModuleManager();

$taxaArrJson = array_key_exists('taxajson',$_REQUEST)?$_REQUEST['taxajson']:'';
$taxonType = array_key_exists('type',$_REQUEST)?$_REQUEST['type']:0;
$useThes = array_key_exists('thes',$_REQUEST)?$_REQUEST['thes']:false;

$tempTaxaArr = Array();
$taxaArr = Array();

if($taxaArrJson){
    $tempTaxaArr = json_decode($taxaArrJson);
}

if($tempTaxaArr){
    foreach($tempTaxaArr as $name){
        if(is_numeric($name)){
            $sql = 'SELECT sciname FROM taxa WHERE (TID = '.$name.')';
            $rs = $con->query($sql);
            while($row = $rs->fetch_object()){
                $taxaStr = $row->sciname;
                if($taxaStr) $taxaArr[$taxaStr] = Array();
            }
            $rs->close();
        }
        else{
            if($taxonType != 5) $name = ucfirst($name);
            $taxaArr[$name] = Array();
        }
    }

    if($taxonType == 5){
        $sql = "SELECT DISTINCT v.VernacularName, t.tid, t.sciname, ts.family, t.rankid ".
            "FROM (taxstatus AS ts INNER JOIN taxavernaculars AS v ON ts.TID = v.TID) ".
            "INNER JOIN taxa AS t ON t.TID = ts.tidaccepted ";
        $whereStr = "";
        foreach($taxaArr as $key => $value){
            $whereStr .= "OR v.VernacularName = '".$key."' ";
        }
        $sql .= "WHERE (ts.taxauthid = 1) AND (".substr($whereStr,3).") ORDER BY t.rankid LIMIT 20";
        //echo "<div>sql: ".$sql."</div>";
        $result = $con->query($sql);
        if($result->num_rows){
            while($row = $result->fetch_object()){
                $vernName = strtolower($row->VernacularName);
                if($row->rankid < 140){
                    $taxaArr[$vernName]["tid"][] = $row->tid;
                }
                elseif($row->rankid == 140){
                    $taxaArr[$vernName]["families"][] = $row->sciname;
                }
                else{
                    $taxaArr[$vernName]["scinames"][] = $row->sciname;
                }
            }
        }
        else{
            $taxaArr["no records"]["scinames"][] = "no records";
        }
        $result->free();
    }
    elseif($useThes){
        foreach($taxaArr as $key => $value){
            if(array_key_exists("scinames",$value)){
                if(!in_array("no records",$value["scinames"])){
                    $synArr = $spatialManager->getSynonyms($value["scinames"]);
                    if($synArr) $taxaArr[$key]["synonyms"] = $synArr;
                }
            }
            else{
                $synArr = $spatialManager->getSynonyms($key);
                if($synArr) $taxaArr[$key]["synonyms"] = $synArr;
            }
        }
    }
    foreach($taxaArr as $key => $valueArray){
        if($taxonType == 4){
            $rs1 = $con->query("SELECT ts.tidaccepted FROM taxa AS t LEFT JOIN taxstatus AS ts ON t.TID = ts.tid WHERE (t.sciname = '".$key."')");
            if($r1 = $rs1->fetch_object()){
                $taxaArr[$r1->tidaccepted] = $taxaArr[$key];
                unset($taxaArr[$key]);
            }
        }
        elseif($taxonType == 5){
            $famArr = Array();
            if(array_key_exists("families",$valueArray)){
                $famArr = $valueArray["families"];
            }
            if(array_key_exists("tid",$valueArray)){
                $tidArr = $valueArray['tid'];
                $sql = 'SELECT DISTINCT t.sciname '.
                    'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
                    'WHERE t.rankid = 140 AND e.taxauthid = 1 AND e.parenttid IN('.implode(',',$tidArr).')';
                $rs = $con->query($sql);
                while($r = $rs->fetch_object()){
                    $famArr[] = $r->family;
                }
                if($famArr){
                    $famArr = array_unique($famArr);
                    $valueArray["families"] = $famArr;
                }
            }
        }
    }
    $con->close();
}
echo json_encode($taxaArr);
?>