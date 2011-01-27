<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PopulusManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$popManager = new PopulusManager();
if($collId) $popManager->setCollId($collId);

$status = "";
if($editable){
	if($action == 'Upload ABBYY File'){
		$statusArr = $popManager->loadLabelFile();
		if($statusArr){
			$status = '<ul><li>'.implode('</li><li>',$statusArr).'</li></ul>';
		}
	}
}

?>
<html>
	<head>
		<title>Populus Control Panel</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<script language="javascript">
			function validateAbbyyForm(f){
				if(f.abbyyfile.value == ""){
					alert('Select an ABBYY output file to upload');
					return false;
				}
				return true;
			}

			function validateCollidForm(f){
				if(f.collid.length == null){
					if(f.collid.checked) return true;
				}
				else{
					var radioCnt = f.collid.length;
					for(var counter = 0; counter < radioCnt; counter++){
						if (f.collid[counter].checked) return true; 
					}
				}
				alert("Please select a Collection Project");
				return false;
			}
		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Populus Control Panel</h1>
			<?php 
			if($status){ 
				?>
				<div style='margin:20px 0px 20px 0px;'>
					<hr/>
					<?php echo $status; ?>
					<hr/>
				</div>
				<?php 
			}
			if($collId){ 
				?>
				<div>
					<form name="abbyyloaderform" action="index.php" enctype="multipart/form-data" method="post" onsubmit="return validateAbbyyForm(this);">
						<fieldset>
							<legend><b>ABBYY OCR File Loader</b></legend>
							<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
							<div style="font-weight:bold;margin:10px;">
								File: 
								<input id="abbyyfile" name="abbyyfile" type="file" size="45" />
							</div>
							<div style="margin:10px;">
								<input type="hidden" name="collid" value="<?php echo $collId; ?>" >
								<input type="submit" name="action" value="Upload ABBYY File" />
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			else{
				if($symbUid){
					if($collList = $popManager->getCollectionList()){
						?>
						<form name="collidform" action="index.php" method="post" onsubmit="return validateCollidForm(this);">
							<fieldset>
								<legend><b>Collection Projects</b></legend>
								<div style="margin:15px;">
									<?php 
									foreach($collList as $cId => $cName){
										echo '<input type="radio" name="collid" value="'.$cId.'" /> '.$cName.'<br/>';
									}
									?>
								</div>
								<div style="margin:15px;">
									<input type="submit" name="action" value="Select Collection Project" />
								</div>
							</fieldset>
						</form>
						<?php
					}
					else{
						echo '<div>There are no Collection Project for which you have authority to update</div>';						
					} 
				}
				else{
					?>
					<div style='font-weight:bold;'>
						Please <a href='../profile/index.php?refurl=<?php echo $clientRoot; ?>/populus/index.php'>login</a>!
					</div>
					<?php 
				}
			}
			?>
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
