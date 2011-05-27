<?php
/*
 * E.E. Gilbert
 */

//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/VoucherManager.php');
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
elseif(array_key_exists('oiddel',$_REQUEST)){
	$status = $vManager->removeVoucher($_REQUEST['oiddel']);
}
elseif( $action == "Add Voucher"){
	//For processing requests sent from /collections/individual/index.php
	$status = $vManager->addVoucher($_REQUEST["voccid"],$_REQUEST["vnotes"],$_REQUEST["veditnotes"]);
}
$clArray = $vManager->getChecklistData();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">
	<head>
		<title>Species Details: <?php echo $vManager->getTaxonName()." of ".$vManager->getClName(); ?></title>
		<link rel="stylesheet" href="../css/main.css" type="text/css" />
		<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />	
		<script type="text/javascript" src="../js/jquery-1.4.4.min.js"></script>
		<script type="text/javascript" src="../js/jquery-ui-1.8.11.custom.min.js"></script>
		<script language="JavaScript">
		
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
				cseXmlHttp.onreadystatechange=function(){
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
				};
				cseXmlHttp.open("POST",url,true);
				cseXmlHttp.send(null);
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
		
			$(document).ready(function() {
				$("#renamesciname").autocomplete({
					source: function( request, response ) {
						$.getJSON( "rpc/speciessuggest.php", { term: request.term, cl: <?php echo $clid;?> }, response );
					}
					},{ minLength: 3, autoFocus: true }
				);


			});

			function openPopup(urlStr,windowName){
				var wWidth = 750;
				try{
					if(opener.document.getElementById('maintable').offsetWidth){
						wWidth = opener.document.getElementById('maintable').offsetWidth*1.05;
					}
					else if(opener.document.body.offsetWidth){
						wWidth = opener.document.body.offsetWidth*0.9;
					}
				}
				catch(err){
				}
				newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=650,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
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
				<form action="clsppeditor.php" method='post' name='editcl' target='_self'>
					<fieldset style='margin:5px 0px 5px 5px;'>
		    			<legend>Edit Checklist Information:</legend>
		    			<div style="clear:both;">
							<div style='width:100px;font-weight:bold;float:left;'>
								Habitat:
							</div>
							<div style='float:left;'>
								<input name='habitat' type='text' value="<?php echo (array_key_exists("habitat",$clArray)?$clArray["habitat"]:"");?>" size='70' maxlength='250' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Abundance:
							</div>
							<div style='float:left;'>
								<input type="text"  name="abundance" value="<?php echo (array_key_exists("abundance",$clArray)?$clArray["abundance"]:""); ?>" />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Notes:
							</div>
							<div style='float:left;'>
								<input name='notes' type='text' value="<?php echo (array_key_exists("notes",$clArray)?$clArray["notes"]:"");?>" size='65' maxlength='2000' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Editor Notes:
							</div>
							<div style='float:left;'>
								<input name='internalnotes' type='text' value="<?php echo (array_key_exists("internalnotes",$clArray)?$clArray["internalnotes"]:"");?>" size='65' maxlength='250' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Source:
							</div>
							<div style='float:left;'>
								<input name='source' type='text' value="<?php echo (array_key_exists("source",$clArray)?$clArray["source"]:"");?>" size='65' maxlength='250' />
							</div>
						</div>
						<div style='clear:both;'>
							<div style='width:100px;font-weight:bold;float:left;'>
								Family Override: 
							</div>
							<div style='float:left;'>
								<input name='familyoverride' type='text' value="<?php echo (array_key_exists("familyoverride",$clArray)?$clArray["familyoverride"]:"");?>" size='65' maxlength='250' />
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
								<input id="renamesciname" name='renamesciname' type="text" size="50" />
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
					<a href="../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $vManager->getTaxonName()."&clid=".$vManager->getClid()."&targettid=".$tid;?>">
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
							echo "<a style=\"cursor:pointer\" onclick=\"openPopup('../collections/individual/index.php?occid=".$occId."','indpane')\">".$occId."</a>: \n";
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

 