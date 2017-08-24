<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDataset.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/datasets/index.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitize input variables
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$datasetManager = new OccurrenceDataset();
$datasetManager->setSymbUid($SYMB_UID);

$statusStr = '';
if($action == 'Create New Dataset'){
	if(!$datasetManager->createDataset($_POST['name'],$_POST['notes'],$SYMB_UID)){
		$statusStr = implode(',',$datasetManager->getErrorArr());
	}
}
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> Occurrence Dataset Manager</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
		<script type="text/javascript" src="../../js/jquery.js"></script>
		<script type="text/javascript" src="../../js/jquery-ui.js"></script>
		<script type="text/javascript" src="../../js/symb/shared.js"></script>
		<script language="javascript" type="text/javascript">
			function validateAddForm(f){
				if(f.adduser.value == ""){
					alert("Enter a user (login or last name)");
					return false
				}
				if(f.adduser.value.indexOf(" [#") == -1){
					$.ajax({
						url: "rpc/getuserlist.php",
						dataType: "json",
						data: {
							term: f.adduser.value
						},
						success: function(data) {
							if(data && data != ""){
								f.adduser.value = data;
								alert("Located login: "+data);
								f.submit();
							}
							else{
								alert("Unable to locate user");
							}
						}
					});
					return false;
				}
				return true;
			}
		</script>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_datasets_indexMenu)?$collections_datasets_indexMenu:false);
	include($serverRoot."/header.php");
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt; 
		<?php
		if(isset($collections_datasets_indexCrumbs)){
			echo $collections_datasets_indexCrumbs;
		}
		else{
			echo '<a href="../../profile/viewprofile.php?tabindex=1">My Profile</a> &gt;&gt; ';
		}
		?>
		<a href="index.php">
			<b>Dataset Listing</b>
		</a>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($statusStr){
			$color = 'green';
			if(strpos($statusStr,'ERROR') !== false) $color = 'red';
			elseif(strpos($statusStr,'WARNING') !== false) $color = 'orange';
			elseif(strpos($statusStr,'NOTICE') !== false) $color = 'yellow';
			echo '<div style="margin:15px;color:'.$color.';">';
			echo $statusStr;
			echo '</div>';
		}
		$dataSetArr = $datasetManager->getDatasetArr();
		?>
		<div>
		<div style="float:right;margin:10px;" title="Create New Dataset" onclick="toggle('adddiv')">
	 		<img src="../../images/add.png" style="width:14px;" />
		</div>
		<div id=adddiv style="display:<?php echo ($dataSetArr?'none':'block') ?>;">
			<fieldset style="padding:15px;margin:15px;">
				<legend><b>Create New Dataset</b></legend>
				<form name="adminform" action="index.php" method="post" onsubmit="return validateEditForm(this)">
					<div>
						<b>Name</b><br />
						<input name="name" type="text" style="width:250px" />
					</div>
					<div>
						<b>Notes</b><br />
						<input name="notes" type="text" style="width:90%;" />
					</div>
					<div>
						<input name="submitaction" type="submit" value="Create New Dataset" />
					</div>
				</form>
			</fieldset>
		</div>
		<?php 
		if($dataSetArr){
			?>
			<fieldset style="padding:15px;margin:15px;">
				<legend><b>Owned by You</b></legend>
				<?php 
				if(array_key_exists('owner',$dataSetArr)){
					$ownerArr = $dataSetArr['owner'];
					unset($dataSetArr['owner']);
					foreach($ownerArr as $dsid => $dsArr){
						?>
						<div>
							<?php 
							echo '<b>'.$dsArr['name'].' (#'.$dsid.')</b>';
							?>
							<a href="datasetmanager.php?datasetid=<?php echo $dsid; ?>" title="Manage and edit dataset">
								<img src="../../images/edit.png" style="width:13px;" />
							</a>&nbsp;&nbsp;
						</div>
						<div style="margin-left:15px;">
							<?php 
							echo ($dsArr["notes"]?$dsArr["notes"].'<br/>':'');
							echo 'Created: '.$dsArr["ts"]; 
							?>
						</div>
						<?php 
					}
				}
				else{
					echo '<div style="font-weight:bold;">There are no datasets owned by you</div>';
				}
				?>
			</fieldset>
			<fieldset style="padding:15px;margin:15px;">
				<legend><b>Shared with You</b></legend>
				<?php 
				if(array_key_exists('other',$dataSetArr)){
					$otherArr = $dataSetArr['other'];
					foreach($otherArr as $dsid => $dsArr){
						?>
						<div>
							<?php 
							$role = 'Dataset reader';
							if($dsArr['role'] == 'DatasetAdmin'){
								$role = 'Dataset Administator';
							}
							elseif($dsArr['role'] == 'DatasetEditor'){
								$role = 'Dataset Editor';
							}
							echo '<b>'.$dsArr["name"].' (#'.$dsid.')</b> - '.$role;
							?>
							<a href="datasetmanager.php?datasetid=<?php echo $dsid; ?>" title="Access Dataset">
								<img src="../../images/list.png" style="width:13px;" />
							</a>
						</div>
						<div style="margin-left:15px;">
							<?php 
							echo ($dsArr["notes"]?$dsArr["notes"].'<br/>':'');
							echo 'Created: '.$dsArr["ts"]; 
							?>
						</div>
						<?php
					} 
				}
				else{
					echo '<div style="font-weight:bold;">There are no datasets shared with you</div>';
				}
				?>
			</fieldset>
			<?php 	
		}
		else{
			echo '<div style="margin:15px;font-weight:bold;">There are no datasets linked to your login</div>';
		}
		?>
		</div>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
	</body>
</html>