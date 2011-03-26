<?php
/*
* Rebuilt on Sept 2010
* Author: E.E. Gilbert
*/

//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TPDescEditorManager.php');
 
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:0;
$category = array_key_exists("category",$_REQUEST)?$_REQUEST["category"]:""; 
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$descEditor = new TPDescEditorManager();
if($tid){
	$descEditor->setTid($tid);
	$descEditor->setLanguage($lang);
	 
	$editable = false;
	if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
		$editable = true;
	}
	 
	$status = "";
	if($editable){
		if($action == "Edit Description Block"){
			$descEditor->editDescriptionBlock();
		}
		elseif($action == "Delete Description Block"){
			$descEditor->deleteDescriptionBlock();
		}
		elseif($action == "Add Description Block"){
			$descEditor->addDescriptionBlock();
		}
		elseif($action == "Edit Statement"){
			$descEditor->editStatement();
		}
		elseif($action == "Delete Statement"){
			$descEditor->deleteStatement();
		}
		elseif($action == "Add Statement"){
			$descEditor->addStatement();
		}
	}
}
else{
	header('Location: tpeditor.php?category='.$category.'&lang='.$lang.'&action='.$action);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." Taxon Editor: ".$descEditor->getSciName(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
	<link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../../css/speciesprofile.css" rel="stylesheet" />
	<script type="text/javascript">
		function toggleById(target){
			var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else {
				obj.style.display="none";
			}
		}

	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_tpdesceditorMenu)?$taxa_admin_tpdesceditorMenu:false);
include($serverRoot.'/header.php');
if(isset($taxa_admin_tpdesceditorCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_tpdesceditorCrumbs;
	echo " <b>Taxon Profile Description Editor</b>";
	echo "</div>";
}

if($editable && $tid){
	?>
	<table style="width:100%;">
		<tr><td>
			<div style='float:right;margin:15px;'>
				<a href="tpeditor.php?tid=<?php echo $descEditor->getTid(); ?>">
					Main Menu
				</a>
			</div>
		<?php 

 	//If submitted tid does not equal accepted tid, state that user will be redirected to accepted
 	if($descEditor->getSubmittedTid()){
 		echo "<div style='font-size:16px;margin-top:5px;margin-left:10px;font-weight:bold;'>Redirected from: <i>".$descEditor->getSubmittedSciName()."</i></div>"; 
 	}
	//Display Scientific Name and Family
	echo "<div style='font-size:16px;margin-top:15px;margin-left:10px;'><a href='../index.php?taxon=".$descEditor->getTid()."' style='color:#990000;text-decoration:none;'><b><i>".$descEditor->getSciName()."</i></b></a> ".$descEditor->getAuthor();
	//Display Parent link
	if($descEditor->getRankId() > 140) echo "&nbsp;<a href='tpeditor.php?tid=".$descEditor->getParentTid()."'><img border='0' height='10px' src='../../images/toparent.jpg' title='Go to Parent' /></a>";
	echo "</div>\n";
	//Display Family
	echo "<div id='family' style='margin-left:20px;margin-top:0.25em;'><b>Family:</b> ".$descEditor->getFamily()."</div>\n";
	
	if($status){
		echo "<h3 style='color:red;'>Error: $status<h3>";
	}

	if($category == "textdescr"){
		//Display Description info
		?>
		<div>
			<b>Descriptions</b>&nbsp;&nbsp;&nbsp;
			<span onclick="javascript:toggleById('adddescrblock');" title="Add a New Description">
				<img style='border:0px;width:15px;' src='../../images/add.png'/>
			</span>
		</div>
		<div id='adddescrblock' style='display:none;'>
			<form name='adddescrblockform' action="tpdesceditor.php" method="get">
				<fieldset style='width:475px;margin:20px;'>
	    			<legend><b>New Description Block</b></legend>
					<div style=''>
						Language: <input id="language" name="language" style="margin-top:5px;" type="text" value="<?php echo $defaultLang; ?>" />
					</div>
					<div style=''>
						Caption: <input id='caption' name='caption' style='margin:2px;' type='text' />
					</div>
					<div style=''>
						Source: <input id='source' name='source' style='margin:2px;width:300px;' type='text' />
					</div>
					<div style=''>
						Source Url: <input id='sourceurl' name='sourceurl' style='margin:2px;width:300px;' type='text' />
					</div>
					<div style=''>
						Notes: <input id='notes' name='notes' style='margin:2px;width:300px;' type='text' />
					</div>
					<div style="float:right;">
						<input name='action' style='margin-top:5px;' type='submit' value='Add Description Block' />
						<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
						<input type='hidden' name='category' value='<?php echo $category; ?>' />
					</div>
					<div style=''>
						Sort Order: <input id='displaylevel' name='displaylevel' style='margin:2px;width:40px;' type='text' />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		$descList = $descEditor->getDescriptions();
		if($descList){
			foreach($descList as $lang => $dlArr){
		    	foreach($dlArr as $displayLevel => $bArr){
		    		?>
    				<fieldset style='width:500px;margin:10px 5px 5px 5px;'>
						<legend><b><?php echo $lang.": ".($bArr["caption"]?$bArr["caption"]:"Description ".$displayLevel); ?></b></legend>
						<div style="float:right;" onclick="javascript:toggleById('dblock-<?php echo $bArr["tdbid"];?>');" title="Edit Description Block">
							<img style='border:0px;width:12px;' src='../../images/edit.png'/>
						</div>
						<div><b>Caption:</b> <?php echo $bArr["caption"]; ?></div>
						<div><b>Source:</b> <?php echo $bArr["source"]; ?></div>
						<div><b>Source URL:</b> <a href='<?php echo $bArr["sourceurl"]; ?>'><?php echo $bArr["sourceurl"]; ?></a></div>
						<div><b>Notes:</b> <?php echo $bArr["notes"]; ?></div>
						<div id="dblock-<?php echo $bArr["tdbid"];?>" style="display:none;margin-top:10px;">
							<fieldset>
								<legend><b>Description Block Edits</b></legend>
								<form id='updatedescrblock' name='updatedescrblock' action="tpdesceditor.php" method="post">
									<div>
										Language: 
										<input name='language' style='margin-top:5px;border:inset;' type='text' value='<?php echo $lang; ?>' />
									</div>
									<div>
										Caption: 
										<input id='caption' name='caption' style='margin-top:5px;border:inset;width:330px;' type='text' value='<?php echo $bArr["caption"];?>' />
									</div>
									<div>
										Source: 
										<input id='source' name='source' style='margin-top:5px;border:inset;width:330px;' type='text' value='<?php echo $bArr["source"];?>' />
									</div>
									<div>
										Source URL: 
										<input id='sourceurl' name='sourceurl' style='margin-top:5px;border:inset;width:330px;' type='text' value='<?php echo $bArr["sourceurl"];?>' />
									</div>
									<div>
										Notes: 
										<input name='notes' style='margin-top:5px;border:inset;width:400px;' type='text' value='<?php echo $bArr["notes"];?>' />
									</div>
									<div style="float:right;margin:10px;">
										<input type='hidden' name='tdbid' value='<?php echo $bArr["tdbid"];?>' />
										<input type='hidden' name='tid' value='<?php echo $tid;?>' />
										<input type='hidden' name='category' value='<?php echo $category;?>'>
										<input type='submit' name='action' value='Edit Description Block' /> 
									</div> 
									<div>
										Display Level: 
										<input id='displaylevel' name='displaylevel' style='margin-top:5px;border:inset;width:40px;' type='text' value='<?php echo $displayLevel;?>' />
									</div>
								</form>
								<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:2px;'>
									<form name='delstmt' action='tpdesceditor.php' method='post' onsubmit="javascript: return window.confirm('Are you sure you want to delete this Description?');">
										<input type='hidden' name='tdbid' value='<?php echo $bArr["tdbid"];?>' />
										<input type='hidden' name='tid' value='<?php echo $tid;?>' />
										<input type='hidden' name='category' value='<?php echo $category;?>'>
										<input name='action' value='Delete Description Block' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.gif'/> 
										Delete Description Block (Including all statements below) 
									</form>
								</div>
							</fieldset>
						</div>
    					<div style="margin-top:10px;">
							<fieldset>
								<legend><b>Statements</b></legend>
								<div onclick="javascript:toggleById('addstmt-<?php echo $bArr["tdbid"];?>');" style="float:right;" title="Add a New Statement">
									<img style='border:0px;width:15px;' src='../../images/add.png'/>
								</div>
								<div id='addstmt-<?php echo $bArr["tdbid"];?>' style='display:none;'>
									<form name='adddescrstmtform' action="tpdesceditor.php" method="post">
										<fieldset style='margin:5px 0px 0px 15px;'>
							    			<legend><b>New Description Statement</b></legend>
											<div style='margin:3px;'>
												Heading: <input name='heading' style='margin-top:5px;' type='text' />&nbsp;&nbsp;&nbsp;&nbsp;
												<input name='displayheader' type='checkbox' value='1' CHECKED /> Display Header
											</div>
											<div style='margin:3px;'>
												<textarea name='statement' cols='50' rows='3'></textarea>
											</div>
											<div style="float:right;">
												<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
												<input type='hidden' name='tdbid' value='<?php echo $bArr["tdbid"];?>' />
												<input type='hidden' name='category' value='<?php echo $category; ?>' />
												<input name='action' style='margin:3px;' type='submit' value='Add Statement' />
											</div>
											<div style='margin:3px;'>
												Sort Sequence: <input name='sortsequence' style='margin-top:5px;width:40px;' type='text' />
											</div>
										</fieldset>
									</form>
								</div>
								<?php
								if(array_key_exists("stmts",$bArr)){
									$sArr = $bArr["stmts"];
									foreach($sArr as $tdsid => $stmtArr){
										?>
										<div style="margin-top:3px;">
											<b><?php echo $stmtArr["heading"];?></b>&nbsp;&nbsp;&nbsp;
											<?php echo ($stmtArr["displayheader"]?"(header displayed)":"(heading hidden)");?>&nbsp;&nbsp;&nbsp;
											<span onclick="javascript:toggleById('dstmt-<?php echo $tdsid;?>');" title="Edit Statement">
												<img style='border:0px;width:12px;' src='../../images/edit.png'/>
											</span>
										</div>
										<div style='clear:both;'><?php echo $stmtArr["statement"];?></div>
										<div id="dstmt-<?php echo $tdsid;?>" style="display:none;">
											<div style='margin:5px 0px 5px 20px;border:2px solid cyan;padding:5px;'>
												<form id='updatedescr' name='updatedescr' action="tpdesceditor.php" method="post">
													<div>
														<b>Heading:</b> <input name='heading' style='margin:3px;' type='text' value='<?php echo $stmtArr["heading"];?>' />&nbsp;&nbsp;&nbsp;
														<input name='displayheader' type='checkbox' value='1' <?php echo ($stmtArr["displayheader"]?"CHECKED":"");?> /> Display Header
													</div>
													<div>
														<textarea name='statement' cols='50' rows='3' style='margin:3px;'><?php echo $stmtArr["statement"];?></textarea>
													</div>
													<div style="float:right;margin:10px;">
														<input name='action' type='submit' value='Edit Statement' />
													</div>
													<div>
														<b>Sort Sequence:</b> 
														<input id='sortsequence' name='sortsequence' style='margin:3px;width:40px;' type='text' value='<?php echo $stmtArr["sortsequence"];?>' />&nbsp;&nbsp;
														<input type='hidden' name='tdsid' value='<?php echo $tdsid;?>'>
														<input type='hidden' name='tid' value='<?php echo $tid;?>' />
														<input type='hidden' name='category' value='<?php echo $category;?>'>
													</div>
												</form>
											</div>
											<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:2px;'>
												<form name='delstmt' action='tpdesceditor.php' method='post' onsubmit="javascript: return window.confirm('Are you sure you want to delete this Description?');">
													<input type='hidden' name='tdsid' value='<?php echo $tdsid;?>' />
													<input type='hidden' name='tid' value='<?php echo $tid;?>' />
													<input type='hidden' name='category' value='<?php echo $category;?>'>
													<input name='action' value='Delete Statement' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.gif'/> 
													Delete Statement 
												</form>
											</div>
										</div>
									<?php 
									}
								}
							?>
							</fieldset>
						</div>
					</fieldset>
					<?php 
				}
			}
		}
	}
}
else{
	?>
	<div style="margin:30px;">
		<h2>You must be logged in and authorized to taxon data.</h2>
		<h3>
			Click <a href="<?php $clientRoot; ?>/profile/index.php">here</a> to login
		</h3>
	</div>
	<?php 
}
?>
	</td></tr>
</table>
<?php  
include($serverRoot.'/footer.php');
 ?>
	
</body>
</html>