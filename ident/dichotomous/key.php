<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$taxon = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
$dichoKeyManager = new DichoKeyManager();

?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Dichotomous Key</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script LANGUAGE="JavaScript">
	
		var cseXmlHttp;
		var targetStr;
		
		function toggle(target){
			var divObjs = document.getElementsByTagName("div");
			for (i = 0; i < divObjs.length; i++) {
				var obj = divObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="block";
					}
					else {
						obj.style.display="none";
					}
				}
			}

			var spanObjs = document.getElementsByTagName("span");
			for (i = 0; i < spanObjs.length; i++) {
				var obj = spanObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="inline";
					}
					else {
						obj.style.display="none";
					}
				}
			}
		}

	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($ident_dichotomous_keyMenu)?$ident_dichotomous_keyMenu:"true");
	include($serverRoot."/header.php");
	if(isset($ident_dichotomous_keyCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $ident_dichotomous_keyCrumbs;
		echo " <b>Dichotomous Key</b>";
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($clid && $taxon){
			$dichoKeyManager->buildKey($clid,$taxon);
		}
		?>	
	
	</div>
	<?php 
		include($serverRoot."/footer.php");
	?>
</body>
</html>

<?php

class DichoKeyManager{

	private function getConnection($type = "readonly") {
 		return MySQLiConnectionFactory::getCon($type);
	}

	public function buildKey($clid, $taxonFilter){
		$con = $this->getConnection();
		//Grab taxa matching clid and taxonFilter; put into taxaArr
		$sql = "SELECT t.tid, t.sciname, ts.family, ts.hierarchystr, ts.parenttid ".
			"FROM (fmchklsttaxalink cl INNER JOIN taxstatus ts ON cl.tid = ts.tid) ".
			"INNER JOIN taxa t ON ts.tid = t.tid ".
			"WHERE ts.taxauthid = 1 AND cl.clid = $clid ";
		if($taxonFilter){
			$sql .= "AND (ts.family = '".$taxonFilter."' OR t.sciname LIKE '".$taxonFilter."%')";
		}
		//echo $sql."<br/>";
		$result = $con->query($sql);
		$parentArr = Array();
		$taxaArr = Array();
		$tempTaxa = Array();
		while($row = $result->fetch_object()){
			$childTid = $row->tid;
			if($row->hierarchystr){
				$hierArr = array_reverse(explode(",",$row->hierarchystr));
				foreach($hierArr as $tid){
					if(array_key_exists($childTid,$parentArr)) break;
					$parentArr[$childTid] = $tid;
					$childTid = $tid;
				}
			}
			else{
				$parentArr[$row->tid] = $row->parenttid;
			}
			$taxaArr[$row->family][$row->tid] = $row->sciname;
			$tempTaxa[] = $row->tid;
		}
		$result->close();

		//Build dichotomous key hierarchy and link taxa to nodes defined by stmtid ($taxaMap)
		$stmtTaxaMap = Array();
		$sql = "SELECT dk.stmtid, dk.statement, dk.hierarchystr, dk.tid ".
			"FROM dichotomouskey dk INNER JOIN taxa t ON dk.tid = t.tid ".
			"WHERE dk.tid IN(".implode(",",array_keys($parentArr)).") ORDER BY t.rankid DESC ";
		$result = $con->query($sql);
		//echo $sql;
		$stmtArr = Array();
		while($row = $result->fetch_object()){
			$hArr = explode(",",$row->hierarchystr);
			$pStmt = 0;
			if($row->hierarchystr){
				foreach($hArr as $v){
					if(!array_key_exists($pStmt,$stmtArr) || !array_key_exists($v,$stmtArr[$pStmt])) $stmtArr[$pStmt][$v] = 0;
					$pStmt = $v;
				}
			}
			$stmtArr[$pStmt][$row->stmtid] = ($row->tid?$row->tid:0);
			$children = Array();
			$child = $row->tid;
			do{
				$keys = array_keys($parentArr,$child);
				if($keys){
					$children = array_merge($children,$keys);
					//$taxaArr = array_diff_key($taxaArr,$keys);
				}
				if(in_array($child,$tempTaxa)){
					$stmtTaxaMap[$row->stmtid][] = $child;
				}
			}while($child = array_shift($children));
		}
		$result->close();
		unset($tempTaxa);
		
		//Filter out insignificant stmt nodes (nodes w/o branches)
		ksort($stmtArr);
		//$tempStmtArr = Array();
		foreach($stmtArr as $p => $sArr){
			while(is_array($sArr) && count($sArr) == 1){
				$taxon = current($sArr);
				$key = key($sArr);
				if(array_key_exists($key,$stmtArr)){
					//There is a child node
					$sArr = $stmtArr[$key];  //Grab child stmt array
					unset($stmtArr[$key]);  //Remove child stmt
					$stmtArr[$p] = $sArr;
				}
				else{
					//Leaf node; there is no child node
					foreach($stmtArr as $k => $vArr){
						if(array_key_exists($p,$vArr) && $vArr[$p] == 0){
							$stmtArr[$k][$p] = $taxon;
							unset($stmtArr[$p]);
							break;
						}
					}
					break;
					//unset($sArr);
					//$sArr = $taxon;
				}
			}
		}
		
		//Grab statements for active stmtids and add to map
		$tempArr = Array();
		foreach($stmtArr as $k => $innerArr){
			$tempArr = array_merge($tempArr,array_keys($innerArr));
		}
		$sql = "SELECT dk.stmtid, dk.statement FROM dichotomouskey dk WHERE dk.stmtid IN (".implode(",",$tempArr).")";
		unset($tempArr);
		$rs = $con->query($sql);
		while($row = $rs->fetch_object()){
			$tempArr[$row->stmtid] = $row->statement;
		}
		$rs->close();
		foreach($stmtArr as $pid => $cArr){
			foreach($cArr as $sid => $v){
				$stmtArr[$pid][$sid] = $tempArr[$sid];
			}
		}
		
		//Echo statements with stmtid as div id and only the lowest id with display:block
		$displayStmt = true;
		foreach($stmtArr as $sId => $stStrArr){
			echo "<div id='".$sId."' style='display:".($displayStmt?"block":"none").";'>\n";
			foreach($stStrArr as $k => $str){
				echo "<div style onclick=''>".$str."</div>\n";
			}
			echo "</div>\n";
			$displayStmt = false;
		}
		print_r($stmtArr);
		echo "<br/><br/>";
		
		//Echo taxa using tid as div id
		foreach($taxaArr as $fam => $tArr){
			
		}
		print_r($taxaArr);
		echo "<br/><br/>";

		//add taxa display control to a script block imbedded within the body
		print_r($stmtTaxaMap);

		//Add stmt display control to above script
		
		
		$con->close();
	} 
}
?>
