<?php 
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$CHARSET);

$nodeId = array_key_exists("nodeid",$_REQUEST)?$_REQUEST["nodeid"]:0;
$stmtId = array_key_exists("stmtid",$_REQUEST)?$_REQUEST["stmtid"]:0;
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$statement = array_key_exists("statement",$_REQUEST)?trim($_REQUEST["statement"]):"";
$parentStmtId = array_key_exists("parentstmtid",$_REQUEST)?$_REQUEST["parentstmtid"]:0;
$taxon = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";
$notes = array_key_exists("notes",$_REQUEST)?$_REQUEST["notes"]:"";

$dichoManager = new DichoManager();

if($action){
	$dataArr = Array(); 
	$dataArr["nodeid"] = $nodeId;
	$dataArr["statement"] = $statement;
	$dataArr["tid"] = $tid;
	$dataArr["notes"] = $notes;
	if($action == "Add New Child Cuplet"){
		$dataArr["parentstmtid"] = $parentStmtId;
		$dataArr["statement2"] = $_REQUEST["statement2"];
		$dataArr["tid2"] = $_REQUEST["tid2"];
		$dataArr["notes2"] = $_REQUEST["notes2"];
		$nodeId = $dichoManager->addCuplet($dataArr);
	}
	else{
		$dataArr["stmtid"] = $stmtId;
		$dichoManager->editStatement($dataArr);
	}
}

$editable = false;
if($IS_ADMIN || array_key_exists("KeyEditor",$USER_RIGHTS)){
 	$editable = true;
}

?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Dichotomous Key Loader</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
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

		function checkScinameExistance(inputObj,tStr){
			targetStr = tStr;
			sciname = inputObj.value;
			if (sciname.length == 0){
		  		return;
		  	}
			cseXmlHttp=GetXmlHttpObject();
			if (cseXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="rpc/gettid.php";
			url=url+"?sciname="+sciname;
			url=url+"&sid="+Math.random();
			cseXmlHttp.onreadystatechange=cseStateChanged;
			cseXmlHttp.open("POST",url,true);
			cseXmlHttp.send(null);
		} 
		
		function cseStateChanged(){
			if (cseXmlHttp.readyState==4){
				var responseStr = cseXmlHttp.responseText;
				if(responseStr == ""){
					alert("INVALID TAXON: Name does not exist in database.");
				}
				else{
					document.getElementById(targetStr).value = responseStr;
				}
			}
		}

		function GetXmlHttpObject(){
			var xmlHttp=null;
			try{
				// Firefox, Opera 8.0+, Safari, IE 7.x
		  		xmlHttp=new XMLHttpRequest();
		  	}
			catch (e){
		  		// Internet Explorer
		  		try{
		    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		    	}
		  		catch(e){
		    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		    	}
		  	}
			return xmlHttp;
		}
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($ident_dichotomous_loaderMenu)?$ident_dichotomous_loaderMenu:"true");
	include($SERVER_ROOT.'/header.php');
	if(isset($ident_dichotomous_loaderCrumbs)) echo "<div class='navpath'>".$ident_dichotomous_loaderCrumbs."</div>";
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
	<?php if($editable){ ?>
		<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editcontrols');" title="Toggle Editing on and off">
			<img style="border:0px;" src="../images/edit.png"/>
		</div>
	<?php } ?>
		<h1>Dichotomous Key Loader</h1>
		<ul>
		<?php 
		$rows = Array();
		if($nodeId){
			$rows = $dichoManager->echoNodeById($nodeId);
		}
		elseif($stmtId){
			$rows = $dichoManager->echoNodeByStmtId($stmtId);
		}
		foreach($rows as $rowCnt => $row){
			if($rowCnt === 0 && $row["parentstmtid"]){ 
				echo "<a href='dichotomous.php?stmtid=".$row["parentstmtid"]."'>";
				echo "<img src='../images/back.png' style='height:10px;border:0px;'/> Go Back";
				echo "</a>";
			}
			?>
			<li>
				<div style='clear:both;margin-top:20px;'>
					<?php 
						echo $row["nodeid"].str_repeat("'",$rowCnt).". ".$row["statement"];
						if($editable){
						?>
							<span class="editcontrols" style="cursor:pointer;display:none;" onclick="javascript:toggle('editdiv<?php echo $rowCnt; ?>');" title="Edit Statements">
								<img style="border:0px;width:12px;margin-left:5px;" src="../images/edit.png"/>
							</span>
							<span class="editcontrols" style="cursor:pointer;display:none;" onclick="javascript:toggle('adddiv<?php echo $rowCnt; ?>');" title="Add a New Cuplet">
								<img style="border:0px;width:12px;margin-left:5px;" src="../images/add.png"/>
							</span>
						<?php 
						}
						
						if($row["childid"]){
							if($row["tid"]) echo " [".$row["sciname"]."] ";
							echo "<div style='float:right;'>".str_repeat(".",20)."GOTO ";
							echo "<a href='dichotomous.php?nodeid=".$row["childid"]."'>";
							echo $row["childid"];
							echo "</a></div>";
						}
						else{
							if($row["tid"]) echo "<div style='float:right;'>".str_repeat(".",20)."GOTO ".$row["sciname"]."</div>";
						}
					?>
				</div>
				<?php if($editable){ ?>
				<div class="editcontrols" style="display:none;">
					<div class="editdiv<?php echo $rowCnt; ?>" style="margin:10px;display:none;">
						<form name="editform" action="dichotomous.php" method="get">
							<fieldset style="width:360px;">
								<legend>Statement Editor</legend>
								<div>
									Statement: 
									<input type="text" name="statement" value="<?php echo $row["statement"]; ?>" size="43"/>
								</div> 
								<div>
									Taxon: 
									<input type="text" id="taxa<?php echo $rowCnt; ?>" name="taxa" value="<?php echo $row["sciname"]; ?>" onchange="checkScinameExistance(this,'tid-<?php echo $rowCnt; ?>');" />
									<input type="hidden" id="tid-<?php echo $rowCnt; ?>" name="tid" value="<?php echo $row["tid"]; ?>" />
								</div> 
								<div>
									Notes:
									<input type="text" name="notes" value="<?php echo $row["notes"]; ?>"  size="43"/>
								</div> 
								<div>
									<input type="hidden" name="nodeid" value="<?php echo $nodeId; ?>" />
									<input type="hidden" name="stmtid" value="<?php echo $row["stmtid"]; ?>" />
									<input type="submit" name="action" value="Submit Edits" />
								</div>
							</fieldset>
						</form>
					</div>
					<div class="adddiv<?php echo $rowCnt; ?>" style="margin:10px;display:none;">
						<form name="addform" action="dichotomous.php" method="get">
							<fieldset style="width:360px;">
								<legend>Add New Child Cuplet</legend>
								<div style="font-weight:bold;">
									Statement 1:
								</div>
								<div>
									Statement:
									<input type="text" name="statement" value="" size="43"/>
								</div> 
								<div>
									Taxon:
									<input type="text" id="taxon1-<?php echo $rowCnt; ?>" name="taxon" onchange="checkScinameExistance(this,'tid1-<?php echo $rowCnt; ?>');" />
									<input type="hidden" id="tid1-<?php echo $rowCnt; ?>" name="tid" value="" />
								</div> 
								<div>
									Notes:
									<input type="text" name="notes" value="" size="43" />
								</div> 
								<hr/>
								<div style="font-weight:bold;">
									Statement 2:
								</div>
								<div>
									Statement:
									<input type="text" name="statement2" value="" size="43" />
								</div> 
								<div>
									Taxon:
									<input type="text" id="taxon2-<?php echo $rowCnt; ?>" name="taxon2" value="" onchange="checkScinameExistance(this,'tid2-<?php echo $rowCnt; ?>');"/>
									<input type="hidden" id="tid2-<?php echo $rowCnt; ?>" name="tid2" value="" />
								</div> 
								<div>
									Notes:
									<input type="text" name="notes2" value="" size="43" />
								</div> 
								<div>
									<input type="hidden" name="nodeid" value="<?php echo $nodeId; ?>" />
									<input type="hidden" name="parentstmtid" value="<?php echo $row["stmtid"]; ?>" />
									<input type="submit" name="action" value="Add New Child Cuplet" />
								</div>
							</fieldset>
						</form>
					</div>
				</div>
			<?php }?> 
			</li>
		<?php } ?> 
		</ul>
	</div>
	<?php 
		include($SERVER_ROOT.'/footer.php');
	?>
	
</body>
</html>

<?php

class DichoManager{

	private function getConnection($type = "readonly") {
 		return MySQLiConnectionFactory::getCon($type);
	}

 	public function echoNodeById($nodeId){
		$sql = "SELECT DISTINCT d.nodeid, d.stmtid, d.statement, d.parentstmtid, d.tid, t.sciname, d.notes, dc.nodeid AS childid ".
			"FROM dichotomouskey d LEFT JOIN taxa t ON d.tid = t.tid ".
			"LEFT JOIN dichotomouskey dc ON d.stmtid = dc.parentstmtid ".
			"WHERE d.nodeid = ".$nodeId." ".
			"ORDER BY d.stmtid";
		//echo $sql;
 		return $this->echoNode($sql);
 	}
 	
 	public function echoNodeByStmtId($stmtId){
		$sql = "SELECT DISTINCT d.nodeid, d.stmtid, d.statement, d.parentstmtid, d.tid, t.sciname, d.notes, dc.nodeid AS childid ".
			"FROM dichotomouskey d LEFT JOIN taxa t ON d.tid = t.tid ".
			"LEFT JOIN dichotomouskey dc ON d.stmtid = dc.parentstmtid ".
			"WHERE d.nodeid = (SELECT d2.nodeid FROM dichotomouskey d2 WHERE d2.stmtid = $stmtId) ".
			"ORDER BY d.stmtid";
 		return $this->echoNode($sql);
	}
	
 	private function echoNode($sql){
		$con = $this->getConnection();
		$result = $con->query($sql);
		$returnArr = Array();
		$stmtCnt = 0;
		while($row = $result->fetch_object()){
			$returnArr[$stmtCnt]["nodeid"] = $row->nodeid;
			$returnArr[$stmtCnt]["stmtid"] = $row->stmtid;
			$returnArr[$stmtCnt]["statement"] = $row->statement;
			$returnArr[$stmtCnt]["parentstmtid"] = $row->parentstmtid;
			$returnArr[$stmtCnt]["childid"] = $row->childid;
			$returnArr[$stmtCnt]["sciname"] = $row->sciname;
			$returnArr[$stmtCnt]["tid"] = $row->tid;
			$returnArr[$stmtCnt]["notes"] = $row->notes;
			$stmtCnt++;
		}
    	$result->close();
    	$con->close();
    	return $returnArr;
	}
	
	public function editStatement($dataArr){
		$con = $this->getConnection("write");
		$sql = "UPDATE dichotomouskey ".
			"SET statement = '".$this->cleanString($dataArr["statement"])."',tid=".($dataArr["tid"]?$dataArr["tid"]:"\N").
			",notes='".$this->cleanString($dataArr["notes"])."' ".
			"WHERE stmtid = ".$dataArr["stmtid"];
		//echo $sql;
		if(!$con->query($sql)){
			echo "<div>ERROR Updating Statement: ".$con->error."</div>";
			echo "<div>SQL: ".$sql."</div>";
		}
    	$con->close();
	}
	
	public function addCuplet($dataArr){
		$parentStart=0;$parentEnd=0;
		$childStart=0;$childEnd=0;
		$con = $this->getConnection("write");
		$sql = "SELECT di.startindex, di.endindex FROM dichotomousindex di WHERE di.nodeid = ".$dataArr["nodeid"];
		//echo $sql;
		$result = $con->query($sql);
		if($row = $result->fetch_object()){
			$parentStart = $row->startindex;
			$parentEnd = $row->endindex;
		}
    	$result->close();
		
    	$sql = "SELECT di.startindex, di.endindex ".
    		"FROM dichotomouskey dk INNER JOIN dichotomousindex di ON dk.nodeid = di.nodeid ".
    		"WHERE dk.parentstmtid = ".$dataArr["parentstmtid"];
		//echo $sql;
		$result = $con->query($sql);
		if($row = $result->fetch_object()){
			$childStart = $row->startindex;
			$chidlEnd = $row->endindex;
		}
    	$result->close();
    	
    	if($childStart){
    		//All where start < childStart and end > childEnd -> end = +2
			$con->query("UPDATE dichotomousindex SET endindex = endindex + 2 WHERE startindex < $childStart AND endindex > $childEnd");
    		//All where start >= childStart and end <= childEnd -> start = +1 and end = +1
			$con->query("UPDATE dichotomousindex SET startindex = startindex + 1,endindex = endindex + 1 WHERE startindex >= $childStart AND endindex <= $childEnd");
    		//Create new node with start = childStart+1 and and end = childEnd+2
			$con->query("INSERT INTO dichotomousindex (startindex, endindex) VALUES(".($childStart).",".($childEnd+2).")");
    	}
    	else{
	    	//All ends > parentStart -> increase by 2
			$con->query("UPDATE dichotomousindex SET endindex = endindex +2 WHERE endindex > ".$parentStart);
	    	//All starts > parentStart -> increase by 2
			$con->query("UPDATE dichotomousindex SET startindex = startindex + 2 WHERE startindex > ".$parentStart);
			//Create new node with start = parentStart+1 and and end = parentStart+2   
			$con->query("INSERT INTO dichotomousindex (startindex, endindex) VALUES(".($parentStart+1).",".($parentStart+2).")");
    	}
    	
		$newNodeId = $con->insert_id;
    	
		
		$sql = "INSERT INTO dichotomouskey (nodeid,statement,parentstmtid,tid,notes) ".
			"VALUES(".$newNodeId.",\"".$this->cleanString($dataArr["statement"])."\",".$dataArr["parentstmtid"].",".($dataArr["tid"]?$dataArr["tid"]:"\N").",\"".$this->cleanString($dataArr["notes"])."\") ";
		if(!$con->query($sql)){
			echo "<div>ERROR Loading Statement1: ".$con->error."</div>";
			echo "<div>SQL: ".$sql."</div>";
		}
		$sql = "INSERT INTO dichotomouskey (nodeid,statement,parentstmtid,tid,notes) ".
			"VALUES(".$newNodeId.",\"".$this->cleanString($dataArr["statement2"])."\",".$dataArr["parentstmtid"].",".($dataArr["tid2"]?$dataArr["tid2"]:"\N").",\"".$this->cleanString($dataArr["notes2"])."\") ";
		if(!$con->query($sql)){
			echo "<div>ERROR Loading Statement1: ".$con->error."</div>";
			echo "<div>SQL: ".$sql."</div>";
		}

    	$con->close();
    	return $newNodeId;
	}
	
	private function cleanString($str){
		$str = str_replace("\"","-",$str);
		$str = str_replace(chr(10),"",$str);
		$str = str_replace(chr(11),"",$str);
		$str = str_replace(chr(12),"",$str);
		$str = str_replace(chr(13),"",$str);
		return $str;
	}
}
?>
