<?php
/*
 * 15 Jan 2010
 * E.E. Gilbert
 */

//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);
 
 $clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:""; 
 $tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:""; 
 $action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
 
 $editable = false;
 if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
 	$editable = true;
 }
 
 $vManager = new VoucherManager();
 
 $status = "";
 $vManager->setTid($tid);
 $vManager->setClid($clid);

 if($action == "Rename Taxon"){
 	$vManager->renameTaxon($_REQUEST["renametid"]);
 }
 elseif($action == "Submit Checklist Edits"){
 	$eArr = Array();
 	$eArr["habitat"] = $_REQUEST["habitat"];
 	$eArr["abundance"] = $_REQUEST["abundance"];
 	$eArr["notes"] = $_REQUEST["notes"];
 	$eArr["internalnotes"] = $_REQUEST["internalnotes"];
 	$eArr["source"] = $_REQUEST["source"];
 	$eArr["familyoverride"] = $_REQUEST["familyoverride"];
	$status = $vManager->editClData($eArr);
 }
 elseif($action == "Delete Taxon From Checklist"){
 	$status = $vManager->deleteTaxon();
 	$action = "close";
 }
 elseif($action == "Submit Voucher Edits"){
 	$vStrings = Array();
 	$vStrings["occid"] = $_REQUEST["occid"];
 	$vStrings["collector"] = $_REQUEST["collector"];
	$vStrings["notes"] = $_REQUEST["notes"];
 	$vStrings["editornotes"] = $_REQUEST["editornotes"];
	$status = $vManager->editVoucher($vStrings);
 }
 elseif($action == "Delete Voucher"){
 	$status = $vManager->removeVoucher($_REQUEST["oiddel"]);
 }
 elseif($action == "Add Voucher"){
 	//For processing requests sent from /collections/individual/individual.php
 	$status = $vManager->addVoucher($_REQUEST["voccid"],$_REQUEST["vnotes"],$_REQUEST["veditnotes"]);
 }
 $clArray = $vManager->getChecklistData();
 ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">
	<head>
		<title>Species Details: <?php echo $vManager->getTaxonName()." of ".$vManager->getClName(); ?></title>
		<link rel="stylesheet" href="../css/main.css" type="text/css" />
	    <link rel="stylesheet" href="../css/jqac.css" type="text/css" />
		<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
		<script type="text/javascript" src="../js/jquery.autocomplete-1.4.2.js"></script>
		<script language="JavaScript">
		
			var cseXmlHttp;

			function validateRenameForm(){ 
				var sciName = document.getElementById("renamesciname").value;
				if(sciName == ""){
					alert("Enter the scientific name to which you want to rename taxon");
					return false;
				}
				else{
					checkScinameExistance(sciName);
					return false;
				}
			}
			
			function checkScinameExistance(sciname){
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
					renameTid = cseXmlHttp.responseText;
					if(renameTid == ""){
						alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, it may have to be added to taxa table.");
					}
					else{
						document.getElementById("renametid").value = renameTid;
						document.forms["renametaxonform"].submit();
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
		
			function initRenameList(input){
				$(input).autocomplete({ ajax_get:getRenameSuggs, minchars:3 });
			}

			function getRenameSuggs(key,cont){ 
			   	var script_name = 'rpc/getspecies.php';
			   	var params = { 'q':key,'cl':'<?php echo $clid;?>' }
			   	$.get(script_name,params,
					function(obj){ 
						// obj is just array of strings
						var res = [];
						for(var i=0;i<obj.length;i++){
							res.push({ id:i , value:obj[i]});
						}
						// will build suggestions list
						cont(res); 
					},
				'json');
			}

			function closeEditor(){
				//if(parent.opener.name != "gmap") parent.opener.location.reload();
				//var URL = unescape(window.opener.location.pathname);
				//window.opener.location.href = URL
				self.close();
			}
		</script>
	</head>
	<body onload="<?php  if($action == "close" && !$status) echo "closeEditor()"; ?>" >
		<!-- This is inner text! -->
		<div id='innertext'>
			<h1>
				<?php echo "<i>".$vManager->getTaxonName()."</i> of ".$vManager->getClName();?>
			</h1>
			<?php 
			if($status){
				?>
				<hr />
				<div style='color:red;font-weight:bold;'>
					<?php echo $status;?>
				</div>
				<hr />
				<?php 
			}
			if($editable){ 
			?>
			<div style=width:600px;>
				<?php
				$habitat = (array_key_exists("habitat",$clArray)?$clArray["habitat"]:"");
				$abundance = (array_key_exists("abundance",$clArray)?$clArray["abundance"]:"");
				$notes = (array_key_exists("notes",$clArray)?$clArray["notes"]:"");
				$internalNotes = (array_key_exists("internalnotes",$clArray)?$clArray["internalnotes"]:"");
				$source = (array_key_exists("source",$clArray)?$clArray["source"]:"");
				$familyOverride = (array_key_exists("familyoverride",$clArray)?$clArray["familyoverride"]:"");
				?>
				<div style="font-weight:bold;">
					<?php 
						
						unset($clArray["familyoverride"]);
						echo implode("; ",$clArray); 
					?>
				</div>
				<form action="clsppeditor.php" method='post' name='editcl' target='_self'>
					<fieldset style='margin:5px 0px 5px 5px;'>
		    			<legend>Edit Checklist Information:</legend>
		    			<div style="clear:both;">
							<div style='width:100px;font-weight:bold;float:left;'>
								Habitat:
							</div>
							<div style='float:left;'>
								<input name='habitat' type='text' value="<?php echo $habitat;?>" size='70' maxlength='250' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Abundance:
							</div>
							<div style='float:left;'>
								<select name="abundance">
									<option value="">undefined</option>
									<option <?php echo ($abundance=="abundant"?" SELECTED":"");?>>abundant</option>
									<option <?php echo ($abundance=="locally abundant"?" SELECTED":"");?>>locally abundant</option>
									<option <?php echo ($abundance=="seasonal abundant"?" SELECTED":"");?>>seasonal abundant</option>
									<option <?php echo ($abundance=="frequent"?" SELECTED":"");?>>frequent</option>
									<option <?php echo ($abundance=="locally frequent"?" SELECTED":"");?>>locally frequent</option>
									<option <?php echo ($abundance=="seasonal frequent"?" SELECTED":"");?>>seasonal frequent</option>
									<option <?php echo ($abundance=="occasional"?" SELECTED":"");?>>occasional</option>
									<option <?php echo ($abundance=="infrequent"?" SELECTED":"");?>>infrequent</option>
									<option <?php echo ($abundance=="rare"?" SELECTED":"");?>>rare</option>
								</select>
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Notes:
							</div>
							<div style='float:left;'>
								<input name='notes' type='text' value="<?php echo $notes;?>" size='65' maxlength='2000' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Editor Notes:
							</div>
							<div style='float:left;'>
								<input name='internalnotes' type='text' value="<?php echo $internalNotes;?>" size='65' maxlength='250' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Source:
							</div>
							<div style='float:left;'>
								<input name='source' type='text' value="<?php echo $source;?>" size='65' maxlength='250' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Family Override: 
							</div>
							<div style='float:left;'>
								<input name='familyoverride' type='text' value="<?php echo $familyOverride;?>" size='65' maxlength='250' />
							</div>
						</div>
						<div style='clear:both;'>
							<input name='tid' type='hidden' value="<?php echo $vManager->getTid();?>" />
							<input name='taxon' type='hidden' value="<?php echo $vManager->getTaxonName();?>" />
							<input name='clid' type='hidden' value="<?php echo $vManager->getClid();?>" />
							<input name='clname' type='hidden' value="<?php echo $vManager->getClName();?>" />
							<input type='submit' name='action' value='Submit Checklist Edits' />
						</div>
					</fieldset>
				</form>
				
				<hr />
				<form action="clsppeditor.php" method="post" id="renametaxonform" name="renametaxonform" onsubmit="return validateRenameForm();">
					<fieldset style='margin:5px 0px 5px 5px;'>
						<legend>Rename Taxon:</legend>
						<div style='clear:both;margin-top:2px;'>
							<div style='width:120px;font-weight:bold;float:left;'>
								New Taxon Name:
							</div>
							<div style='float:left;'>
								<input id="renamesciname" name='renamesciname' type="text" size="50" onfocus="initRenameList(this)" autocomplete="off" />
								<input id="renametid" name="renametid" type="hidden" value="" />
							</div>
							<div style='float:right;margin-right:30px;'>
							</div>
						</div>
						<div style='clear:both;margin-top:2px;'>
							<b>*</b> Note that vouchers &amp; notes will transfer to new taxon
							<input name='tid' type='hidden' value="<?php echo $vManager->getTid();?>" />
							<input name='clid' type='hidden' value="<?php echo $vManager->getClid();?>" />
							<input name="action" type="hidden" value="Rename Taxon" />
							<input type="submit" name="renamesubmit" id="renamesubmit" style="margin:5px 40px;" />
						</div>
					</fieldset>
				</form>
				
				<hr />
				<form action="clsppeditor.php" method="post" name="deletetaxon" onsubmit="return window.confirm('Are you sure you want to delete this taxon from checklist?');">
					<fieldset style='margin:5px 0px 5px 5px;'>
				    	<legend>Delete:</legend>
						<input type='hidden' name='tid' value="<?php echo $vManager->getTid();?>" />
						<input type='hidden' name='clid' value="<?php echo $vManager->getClid();?>" />
						<input type="submit" name="action" value="Delete Taxon From Checklist" />
					</fieldset>
				</form>
				<hr />
				<div style="float:right;margin-top:10px;">
					<a href="../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $vManager->getTaxonName()."&clid=".$vManager->getClid();?>">
						<img src="../images/link.png"  style="border:0px;" />
					</a>
				</div>
				<?php if($occurrenceModIsActive){ ?>
					<h3>Voucher Information</h3>
					<?php
					$vArray = $vManager->getVoucherData();
					if(!$vArray){
						echo "<div>No vouchers for this species has been assigned to checklist </div>";
					}
					?>
					<ul>
					<?php 
					foreach($vArray as $occId => $iArray){
					?>
						<li><?php
							$url = "javascript:var popupReference=window.open('../collections/individual/individual.php?occid=".$occId."','indpane','toolbar=1,resizable=1,width=650,height=600,left=20,top=20');";
							echo "<a href=\"$url\">".$occId."</a>: ";
							echo $iArray["collector"].($iArray["notes"]?"; ".$iArray["notes"]:"").($iArray["editornotes"]?"; ".$iArray["editornotes"]:"");
							?>
							<form action="clsppeditor.php" method='post' name='delform' style="display:inline;;" onsubmit="return window.confirm('Are you sure you want to delete this voucher record?');">
								<input type='hidden' name='tid' value="<?php echo $vManager->getTid();?>" />
								<input type='hidden' name='clid' value="<?php echo $vManager->getClid();?>" />
								<input type='hidden' name='oiddel' id='oiddel' value="<?php echo $occId;?>" />
								<input type="image" name="action" src="../images/del.gif" style="width:13px;" value="Delete Voucher" title="Delete Voucher" />
							</form>
							<div style='margin:10px;clear:both;'>
								<form action="clsppeditor.php" method='get' name='editvoucher'>
									<fieldset style='margin:5px 0px 5px 5px;'>
										<legend>Edit Voucher:</legend>
										<input type='hidden' name='tid' value="<?php echo $vManager->getTid();?>" />
										<input type='hidden' name='clid' value="<?php echo $vManager->getClid();?>" />
										<input type='hidden' name='occid' value="<?php echo $occid;?>" />
										<div style='margin-top:0.5em;'>
											<b>Collector:</b> 
											<input name='collector' type='text' value="<?php echo $iArray["collector"];?>" size='30' maxlength='100' />
										</div>
										<div style='margin-top:0.5em;'>
											<b>Notes:</b>
											<input name='notes' type='text' value="<?php echo $iArray["notes"];?>" size='60' maxlength='250' />
										</div>
										<div style='margin-top:0.5em;'>
											<b>Editor Notes (editor display only):</b>
											<input name='editornotes' type='text' value="<?php echo $iArray["editornotes"];?>" size='30' maxlength='50' />
										</div>
										<div style='margin-top:0.5em;'>
											<input type='submit' name='action' value='Submit Voucher Edits' />
										</div>
									</fieldset>
								</form>
							</div>
						</li>
					<?php } ?>
					</ul>
				<?php } ?>
			</div>
			<?php 
				}
				else{
					echo "<div>You must be logged-in and have editing rights to edited species details</div>";
				} 
			?>
		</div>
	</body>
</html>

 <?php
 
 class VoucherManager {

	private $vCon;
	private $tid;
	private $taxonName;
	private $clid;
	private $clName;
	private $voucherData;
	
	function __construct() {
		$this->vCon = MySQLiConnectionFactory::getCon("write");
 	}
	
 	function __destruct(){
 		if(!($this->vCon === false)) $this->vCon->close();
	}

 	public function setTid($t){
		$this->tid = $t;
 	}
	
	public function getTid(){
		return $this->tid;
	}
	
	public function getTaxonName(){
		return $this->taxonName;
	}
	
	public function setClid($id){
		$this->clid = $id;
	}
	
	public function getClid(){
		return $this->clid;
	}
	
	public function getClName(){
		return $this->clName;
	}
	
	public function getChecklistData(){
 		$checklistData = Array();
 		if(!$this->tid || !$this->clid) return $checklistData; 
		$sql = "SELECT t.SciName, cllink.Habitat, cllink.Abundance, cllink.Notes, cllink.internalnotes, cllink.source, cllink.familyoverride, cl.Name ".
			"FROM (fmchecklists cl INNER JOIN fmchklsttaxalink cllink ON cl.CLID = cllink.CLID) ".
			"INNER JOIN taxa t ON cllink.TID = t.TID ".
			"WHERE ((cllink.TID = ".$this->tid.") AND (cllink.CLID = ".$this->clid."))";
 		$result = $this->vCon->query($sql);
		if($row = $result->fetch_object()){
			if($row->Habitat) $checklistData["habitat"] = $row->Habitat;
			if($row->Abundance) $checklistData["abundance"] = $row->Abundance;
			if($row->Notes) $checklistData["notes"] = $row->Notes;
			if($row->internalnotes) $checklistData["internalnotes"] = $row->internalnotes;
			if($row->source) $checklistData["source"] = $row->source;
			if($row->familyoverride) $checklistData["familyoverride"] = $row->familyoverride;
			if(!$this->clName) $this->clName = $row->Name;
			if(!$this->taxonName) $this->taxonName = $row->SciName;
		}
		$result->close();
		return $checklistData;
	}

	public function editClData($eArr){
		$innerSql = "";
		foreach($eArr as $k => $v){
			$innerSql .= ",".$k."=".($v?"\"".$v."\" ":"NULL ");
		}
		$sqlClUpdate = "UPDATE fmchklsttaxalink SET ".substr($innerSql,1).
			"WHERE (tid = $this->tid) AND (clid = $this->clid)";
		if(!$this->vCon->query($sqlClUpdate)){
			return "ERROR: ".$vCon->error."<br/>SQL: ".$sqlClUpdate.";<br/> ";
		}
		return "";
	}

	public function renameTaxon($newTaxon){
		$sql = "UPDATE fmchklsttaxalink SET TID = ".$newTaxon." ".
			"WHERE TID = ".$this->tid." AND CLID = ".$this->clid;
		if($this->vCon->query($sql)){
			$this->tid = $newTaxon;
			$this->taxonName = "";
		}
		else{
			$sqlTarget = "SELECT cllink.Habitat, cllink.Abundance, cllink.Notes, cllink.internalnotes, cllink.source, cllink.Nativity ".
				"FROM fmchklsttaxalink cllink WHERE TID = ".$newTaxon." AND CLID = ".$this->clid;
			$rsTarget = $this->vCon->query($sqlTarget);
			if($row = $rsTarget->fetch_object()){
				$habitatTarget = $row->Habitat; 
				$abundTarget = $row->Abundance;
				$notesTarget = $row->Notes;
				$internalNotesTarget = $row->internalnotes;
				$sourceTarget = $row->source;
				$nativeTarget = $row->Nativity;
			
				//Move all vouchers to new name
				$sqlVouch = "UPDATE fmvouchers SET TID = ".$newTaxon." ".
					"WHERE TID = ".$this->tid." AND CLID = ".$this->clid;
				$this->vCon->query($sqlVouch);
				//Delete all Vouchers that didn't transfer because they were already linked to target name
				$sqlVouchDel = "DELETE FROM fmvouchers v WHERE v.CLID = $this->clid AND v.TID = $this->tid";
				$this->vCon->query($sqlVouchDel);
				
				//Merge chklsttaxalink data
				//Harvest source (unwanted) chklsttaxalink data
				$sqlSourceCl = "SELECT ctl.Habitat, ctl.Abundance, ctl.Notes, ctl.internalnotes, ctl.source, ctl.Nativity ".
					"FROM fmchklsttaxalink ctl WHERE ctl.TID = ".$this->tid." AND ctl.CLID = ".$this->clid;
				$rsSourceCl =  $this->vCon->query($sqlSourceCl);
				if($row = $rsSourceCl->fetch_object()){
					$habitatSource = $row->Habitat;
					$abundSource = $row->Abundance;
					$notesSource = $row->Notes;
					$internalNotesSource = $row->internalnotes;
					$sourceSource = $row->source;
					$nativeSource = $row->Nativity;
				}
				$rsSourceCl->close();
				//Transfer source chklsttaxalink data to target record
				$habitatStr = $habitatTarget.(($habitatTarget && $habitatSource)?"; ":"").$habitatSource;
				$abundStr = $abundTarget.(($abundTarget && $abundSource)?"; ":"").$abundSource;
				$notesStr = $notesTarget.(($notesTarget && $notesSource)?"; ":"").$notesSource;
				$internalNotesStr = $internalNotesTarget.(($internalNotesTarget && $internalNotesSource)?"; ":"").$internalNotesSource;
				$sourceStr = $sourceTarget.(($sourceTarget && $sourceSource)?"; ":"").$sourceSource;
				$nativeStr = $nativeTarget.(($nativeTarget && $nativeSource)?"; ":"").$nativeSource;
				$sqlCl = "UPDATE fmchklsttaxalink SET Habitat = \"".$habitatStr."\", ". 
					"Abundance = \"".$abundStr."\", Notes = \"".$notesStr."\", internalnotes = \"".$internalNotesStr."\", source = \"".$sourceStr."\", Nativity = \"".$nativeStr."\" ".
					"WHERE TID = ".$newTaxon." AND CLID = ".$this->clid;
				$this->vCon->query($sqlCl);
				//Delete unwanted taxon
				$sqlDel = "DELETE FROM fmchklsttaxalink ctl WHERE ctl.CLID = $this->clid AND ctl.TID = $this->tid";
				if($this->vCon->query($sqlDel)){
					$this->tid = $newTaxon;
					$this->taxonName = "";
				}
			}
			$rsTarget->close();
		}
	}
	
	public function deleteTaxon(){
		//Delete vouchers
		$vSql = "DELETE v.* FROM fmvouchers v WHERE v.tid = ".$this->tid." AND v.clid = ".$this->clid;
		$this->vCon->query($vSql);
		//Delete checklist record 
		$sql = "DELETE ctl.* FROM fmchklsttaxalink ctl WHERE ctl.tid = $this->tid AND ctl.clid = $this->clid ";
		if(!$this->vCon->query($sql)){
			return "ERROR - Unable to delete taxon from checklist: ".$this->vCon->error;
		}
	}

	public function getVoucherData(){
		$voucherData = Array();
 		if(!$this->tid || !$this->clid) return $voucherData;
		$sql = "SELECT v.occid, v.Collector, v.Notes, v.editornotes ".
			"FROM fmvouchers v ".
			"WHERE (v.TID = ".$this->tid.") AND (v.CLID = ".$this->clid.")";
		$result = $this->vCon->query($sql);
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$voucherData[$occId]["collector"] = $row->Collector;
			$voucherData[$occId]["notes"] = $row->Notes;
			$voucherData[$occId]["editornotes"] = $row->editornotes;
		}
		$result->close();
		return $voucherData;
	}
	
	public function editVoucher($editArr){
		if($this->tid && $this->clid){
			$occId = $editArr["occid"];
			unset($editArr["occid"]);
			$setStr = "";
			foreach($editArr as $k => $v){
				$setStr .= ", ".$k." = '".$v."'";
			}
			$setStr = substr($setStr,2);
			$sqlVoucUpdate = "UPDATE fmvouchers v ".
				"SET $setStr WHERE v.occid = \"".$occId."\" AND v.TID = ".$this->tid." AND v.CLID = ".$this->clid;
			$this->vCon->query($sqlVoucUpdate);
		}
	}
	
	public function addVoucher($vOccId, $vNotes, $vEditNotes){
		if($vOccId && $this->clid){
			$status = $this->addVoucherRecord($vOccId, $vNotes, $vEditNotes);
			if($status){
				$sqlInsertCl = "INSERT INTO fmchklsttaxalink ( clid, TID ) ".
					"SELECT ".$this->clid." AS clid, o.TidInterpreted ".
					"FROM omoccurrences o WHERE o.occid = ".$vOccId;
				//echo "<div>sqlInsertCl: ".$sqlInsertCl."</div>";
				if($this->vCon->query($sqlInsertCl)){
					return $this->addVoucherRecord($vOccId, $vNotes, $vEditNotes);
				}
			}
		}
	}

	private function addVoucherRecord($vOccId, $vNotes, $vEditNotes){
		$insertArr = Array();
		//Checklist-taxon combination already exists
		$sql = "SELECT DISTINCT o.occid, o.occurrenceID, ctl.tid, ctl.clid, ".
			"CONCAT_WS('',o.recordedby, CONCAT(' (',IFNULL(o.recordnumber,o.occurrenceid),')')) AS Collector, ".
			"'".$vNotes."' AS Notes, '".$vEditNotes."' AS editnotes ".
			"FROM ((omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted) ".
			"INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid ".
			"WHERE ctl.clid = ".$this->clid." AND o.occid = ".$vOccId." AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 ".
			"LIMIT 1";
		//echo "addVoucherSql: ".$sql."<br/>";
		$rs = $this->vCon->query($sql);
		if($row = $rs->fetch_object()){
			$occId = str_replace("\"","''",$row->occid);
			$collector = str_replace("\"","''",$row->Collector);
			$notes = str_replace("\"","''",$row->Notes);
			$editNotes = str_replace("\"","''",$row->editnotes);
			
			$sqlInsert = "INSERT INTO fmvouchers ( occid, TID, CLID, Collector, Notes, editornotes ) ".
				"VALUES (\"".$occId."\",".$row->tid.",".$row->clid.",\"".$collector."\",\"".
				$notes."\",\"".$editNotes."\") ";
			//echo "<div>".$sqlInsert."</div>";
			if(!$this->vCon->query($sqlInsert)){
				$rs->close();
				return "ERROR - Voucher insert failed: ".$this->vCon->error;
			}
			else{
				$this->tid = $row->tid;
			}
			$rs->close();
			return "";
		}
		return "ERROR: Neither the target taxon nor a sysnonym is present in this checklists. Taxon needs to be added.";
	}

	public function removeVoucher($delOid){
		$sqlDel = "DELETE FROM fmvouchers WHERE occid = ".$delOid." AND TID = ".$this->tid." AND CLID = ".$this->clid;
		$this->vCon->query($sqlDel);
	}
 }
?>
 