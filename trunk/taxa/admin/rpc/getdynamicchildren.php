<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$taxId = array_key_exists('id',$_REQUEST)?$_REQUEST['id']:0;
$displayAuthor = array_key_exists('authors',$_REQUEST)?$_REQUEST['authors']:0;
$targetId = array_key_exists('targetid',$_REQUEST)?$_REQUEST['targetid']:0;

$retArr = Array();
$childArr = Array();
if($taxId == 'root'){
	$retArr['id'] = 'root';
	$retArr['label'] = 'root';
	$retArr['name'] = 'root';
	$retArr['url'] = 'taxonomyeditor.php';
	$retArr['children'] = Array();
	$lowestRank = '';
	$sql = 'SELECT MIN(t.RankId) AS RankId '.
		'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'WHERE (ts.taxauthid = 1) '.
		'LIMIT 1 ';
	//echo $sql."<br>";
	$rs = $con->query($sql);
	while($row = $rs->fetch_object()){
		$lowestRank = $row->RankId;
	}
	$rs->free();
	$sql1 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, tu.rankname '.
		'FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
		'LEFT JOIN taxonunits tu ON t.rankid = tu.rankid '.
		'WHERE ts.taxauthid = 1 AND t.RankId = '.$lowestRank.' ';
	//echo "<div>".$sql1."</div>";
	$rs1 = $con->query($sql1);
	$i = 0;
	while($row1 = $rs1->fetch_object()){
		$label = '2'.$row1->rankid.$row1->sciname;
		if($row1->tid == $targetId){
			$sciName = '<b>'.$row1->sciname.'</b>';
		}
		else{
			$sciName = $row1->sciname;
		}
		$sciName = "<span style='font-size:75%;'>".$row1->rankname."</span> ".$sciName.($displayAuthor?" ".$row1->author:"");
		$childArr[$i]['id'] = $row1->tid;
		$childArr[$i]['label'] = $label;
		$childArr[$i]['name'] = $sciName;
		$childArr[$i]['url'] = 'taxonomyeditor.php?target='.$row1->tid;
		$sql3 = 'SELECT tid FROM taxaenumtree WHERE parenttid = '.$row1->tid.' LIMIT 1 ';
		//echo "<div>".$sql3."</div>";
		$rs3 = $con->query($sql3);
		if($row3 = $rs3->fetch_object()){
			$childArr[$i]['children'] = true;
		}
		else{
			$sql4 = 'SELECT DISTINCT tid, tidaccepted FROM taxstatus WHERE tidaccepted = '.$row2->tid.' ';
			//echo "<div>".$sql4."</div>";
			$rs4 = $con->query($sql4);
			while($row4 = $rs4->fetch_object()){
				if($row4->tid != $row4->tidaccepted){
					$childArr[$i]['children'] = true;
				}
			}
			$rs4->free();
		}
		$rs3->free();
		$i++;
	}
	$rs1->free();
}
else{
	//Get children, but only accepted children
	$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, tu.rankname '.
		'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'LEFT JOIN taxonunits tu ON t.rankid = tu.rankid '.
		'WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) '.
		'AND ((ts.parenttid = '.$taxId.') OR (t.tid = '.$taxId.')) ';
	//echo $sql2."<br>";
	$rs2 = $con->query($sql2);
	$i = 0;
	while($row2 = $rs2->fetch_object()){
		$label = '2-'.$row2->rankid.'-'.$row2->rankname.'-'.$row2->sciname;
		if($row2->rankid >= 180){
			$sciName = '<i>'.$row2->sciname.'</i>';
		}
		else{
			$sciName = $row2->sciname;
		}
		if($row2->tid == $targetId){
			$sciName = '<b>'.$sciName.'</b>';
		}
		$sciName = "<span style='font-size:75%;'>".$row2->rankname."</span> ".$sciName.($displayAuthor?" ".$row2->author:"");
		if($row2->tid == $taxId){
			$retArr['id'] = $row2->tid;
			$retArr['label'] = $label;
			$retArr['name'] = $sciName;
			$retArr['url'] = 'taxonomyeditor.php?target='.$row2->tid;
			$retArr['children'] = Array();
		}
		else{
			$childArr[$i]['id'] = $row2->tid;
			$childArr[$i]['label'] = $label;
			$childArr[$i]['name'] = $sciName;
			$childArr[$i]['url'] = 'taxonomyeditor.php?target='.$row2->tid;
			$sql3 = 'SELECT tid FROM taxaenumtree WHERE taxauthid = 1 AND parenttid = '.$row2->tid.' LIMIT 1 ';
			//echo "<div>".$sql3."</div>";
			$rs3 = $con->query($sql3);
			if($row3 = $rs3->fetch_object()){
				$childArr[$i]['children'] = true;
			}
			else{
				$sql4 = 'SELECT DISTINCT tid, tidaccepted FROM taxstatus WHERE taxauthid = 1 AND tidaccepted = '.$row2->tid.' ';
				//echo "<div>".$sql4."</div>";
				$rs4 = $con->query($sql4);
				while($row4 = $rs4->fetch_object()){
					if($row4->tid != $row4->tidaccepted){
						$childArr[$i]['children'] = true;
					}
				}
				$rs4->free();
			}
			$rs3->free();
			$i++;
		}
	}
	$rs2->free();
	
	//Get synonyms for all accepted taxa
	$sqlSyns = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, tu.rankname '.
		'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'LEFT JOIN taxonunits tu ON t.rankid = tu.rankid '.
		'WHERE (ts.tid <> ts.tidaccepted) AND (ts.taxauthid = 1) AND (ts.tidaccepted = '.$taxId.')';
	//echo $sqlSyns;
	$rsSyns = $con->query($sqlSyns);
	while($row = $rsSyns->fetch_object()){
		$label = '1-'.$row->rankid.'-'.$row->rankname.'-'.$row->sciname;
		if($row->rankid >= 180){
			$sciName = '<i>'.$row->sciname.'</i>';
		}
		else{
			$sciName = $row->sciname;
		}
		if($row->tid == $targetId){
			$sciName = '<b>'.$sciName.'</b>';
		}
		$sciName = '['.$sciName.']';
		$sciName = "<span style='font-size:75%;'>".$row->rankname."</span> ".$sciName.($displayAuthor?" ".$row->author:"");
		$childArr[$i]['id'] = $row->tid;
		$childArr[$i]['label'] = $label;
		$childArr[$i]['name'] = $sciName;
		$childArr[$i]['url'] = 'taxonomyeditor.php?target='.$row->tid;
		$i++;
	}
	$rsSyns->free();
}

function cmp($a,$b){
	return strnatcmp($a["label"],$b["label"]);
}

usort($childArr,"cmp");

$retArr['children'] = $childArr;
	
echo json_encode($retArr);
?>