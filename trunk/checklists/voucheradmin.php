<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 
$sqlFrag = array_key_exists("sqlfrag",$_REQUEST)?$_REQUEST["sqlfrag"]:"";
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 

$displayMode = (array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0);

$clManager = new ChecklistVoucherAdmin();
$clManager->setClid($clid);

$statusStr = "";
$isEditor = 0;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = 1;

	if($action == "Create SQL Fragment"){
		$statusStr = $clManager->saveSql($_POST);
	}
	elseif($action == 'Delete SQL Fragment'){
		$statusStr = $clManager->deleteSql();
	}
	elseif($action == 'Add Vouchers'){
		$clManager->linkVouchers($_POST['occids']);
	}
}
?>

<!DOCTYPE html >
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<title><?php echo $defaultTitle; ?> Checklist Administration</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.voucheradmin.js"></script>
	<style type="text/css">
		li{margin:5px;}
	</style>
</head>

<body>
	<?php
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt; 
		<a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>">Return to Checklist</a> &gt;&gt; 
		<b>Checklist Administrator</b>
	</div>

	<!-- This is inner text! -->
	<div id='innertext'>
		<div style="color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
			<a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>">
				<?php echo $clManager->getClName(); ?>
			</a>
		</div>
		<?php 
		if($statusStr){ 
			?>
			<hr />
			<div style="margin:20px;font-weight:bold;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<hr />
			<?php 
		}
		
		if($clid && $isEditor){
			$dynSql = $clManager->getDynamicSql();
			if($dynSql){
				?>
				<div style="margin:10px 0px;">
					<b>Search statement:</b> <?php echo $dynSql; ?>
					<span style="margin-left:10px;"><a href="#" onclick="toggle('sqlbuilderdiv');return false;" title="Edit Search Statement"><img src="../images/edit.png" style="width:15px;border:0px;"/></a></span>
				</div>
				<?php
			}
			else{
				?>
				<div style="margin-left:5px;"> 
					To use the voucher administration functions, it is first necessary to define a search statement (SQL fragment) 
					that will be used to limit occurrence records to those collected within the vacinity of the research area. 
					Click the 'Create SQL Fragment' button to build the search statement using the terms supplied in the form. 
					If needed, your data <a href="mailto:<?php echo $adminEmail; ?>">administrator</a> can aid in 
					establishing more complex searches than can be created within this form.
				</div>
				<?php
			}
			?> 
			<div id="sqlbuilderdiv" style="display:<?php echo ($dynSql?'none':'block'); ?>;margin-top:15px;">
				<fieldset>
					<legend><b>Edit Search Statement</b></legend>
					<form name="sqlbuilderform" action="voucheradmin.php" method="post" onsubmit="return validateSqlFragForm(this);">
						<div style="margin:10px;">
							Use this form to build an SQL fragment that will be used by the voucher management tools to limit occurrence records 
							to those collected within the vacinity of the research area. 
							Click the 'Create SQL Fragment' button to build and save the SQL using the terms supplied in the form. 
							If needed, your data administrator can aid you in establishing more complex SQL fragments than can be 
							created within this form.
						</div>
						<table style="margin:15px;">
							<tr>
								<td>
									<div style="margin:3px;">
										<b>Country:</b>
										<input type="text" name="country" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>State:</b>
										<input type="text" name="state" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>County:</b>
										<input type="text" name="county" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>Locality:</b>
										<input type="text" name="locality" onchange="" />
									</div>
								</td>
								<td style="padding-left:20px;">
									<div>
										<b>Lat North:</b>
										<input type="text" name="latnorth" style="width:70px;" onchange="" title="Latitude North" />
									</div>
									<div>
										<b>Lat South:</b>
										<input type="text" name="latsouth" style="width:70px;" onchange="" title="Latitude South" />
									</div>
									<div>
										<b>Long East:</b>
										<input type="text" name="lngeast" style="width:70px;" onchange="" title="Longitude East" />
									</div>
									<div>
										<b>Long West:</b>
										<input type="text" name="lngwest" style="width:70px;" onchange="" title="Longitude West" />
									</div>
									<div>
										<input type="checkbox" name="latlngor" value="1" />
										Include Lat/Long as an "OR" condition
									</div>
									<div style="float:right;margin:20px 20px 0px 0px;">
										<input type="submit" name="submitaction" value="Create SQL Fragment" />
										<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
										<input type='hidden' name='pid' value='<?php echo $pid; ?>' />
									</div>
								</td>
							</tr>
						</table>
					</form>
				</fieldset>
				<?php 
				if($dynSql){
					?>
					<fieldset>
						<legend><b>Remove Search Statement</b></legend>
						<form name="sqldeleteform" action="voucheradmin.php" method="post" onsubmit="return confirm('Are you sure you want to delete current SQL statement?');">
							<div style="margin:20px">
								<input type="submit" name="submitaction" value="Delete SQL Fragment" />
							</div>
							<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
							<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
						</form>
					</fieldset>
					<?php
				}
				?>
			</div>
			<?php 
			if($dynSql){
				?>
				<div id="tabs" style="margin-top:25px;">
				    <ul>
				        <li><a href="vanonvouchertaxa.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos.'&displaymode='.$displayMode; ?>"><span>Non-Vouchered Taxa</span></a></li>
				        <li><a href="vamissingtaxa.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span>Missing Taxa</span></a></li>
				        <li><a href="vaconflicts.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span>Voucher Conflicts</span></a></li>
				        <li><a href="vachildvouchers.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span>Children Vouchers</span></a></li>
				    </ul>
				</div>
				<?php
			}
		}
		else{
			if(!$clid){
				echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span> Checklist identifier not set</div>';
			}
			elseif(!$symbUid){
				?>
				<div style="margin:30px;font-weight:bold;font-size:120%;">
					Please <a href="../profile/index.php?refurl=<?php echo $clientRoot.'/checklists/voucheradmin.php?clid='.$clid.'&pid='.$pid; ?>">login</a>
				</div>
				<?php 
			}
			else{
				echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span> You do not have administrative permission for this checklist</div>';
			}
		}
		?>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html> 