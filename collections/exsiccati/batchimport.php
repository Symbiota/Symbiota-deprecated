<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ExsiccatiManager.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID){
	header('Location: ../../profile/index.php?refurl=../collections/exsiccati/batchimport.php?'.$_SERVER['QUERY_STRING']);
}

$ometid = array_key_exists('ometid',$_REQUEST)?$_REQUEST['ometid']:0;
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$source1 = array_key_exists('source1',$_POST)?$_POST['source1']:0;
$source2 = array_key_exists('source2',$_POST)?$_POST['source2']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$statusStr = '';
$isEditor = 0;
if($isAdmin){
	$isEditor = 1;
}
elseif(array_key_exists('CollAdmin',$userRights) && in_array($collid,$userRights['CollAdmin'])){
	$isEditor = 1;
}
elseif(array_key_exists('CollEditor',$userRights) && in_array($collid,$userRights['CollEditor'])){
	$isEditor = 1;
}

$exsManager = new ExsiccatiManager();
if($isEditor && $formSubmit){
	if($formSubmit == 'Import Selected Records'){
		$statusStr = $exsManager->batchImport($collid,$_POST);
	}
}

?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Exsiccati Batch Transfer</title>
    <link href="../../css/base.css" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function verifyExsTableForm(f){
			var formVerified = false;
			for(var h=0;h<f.length;h++){
				if(f.elements[h].name.substring(0,4) == "occ:"){
					if(f.elements[h].value != ""){
						formVerified = true;
						break;
					}
				}
			}
			if(!formVerified){
				alert("Enter at least one catalog number!");
				return false;
			}
			return true;
		}

		function verifyQueryForm(f){
			if(f.collid.value == ""){
				alert("Target collection must be selected");
				return false;
			}
			if(f.ometid.value == ""){
				alert("Exsiccati title must be selected");
				return false;
			}
			return true;
		}
	</script>
	<script type="text/javascript" src="../../js/symb/shared.js?ver=130926"></script>
</head>
<body>
	<?php 
	$displayLeftMenu = (isset($collections_exsiccati_batchimport)?$collections_exsiccati_batchimport:false);
	include($serverRoot."/header.php");
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt; 
		<a href="index.php">Exsiccati Index</a> &gt;&gt; 
		<a href="batchimport.php">Batch Import Module</a>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($statusStr){
			echo '<hr/>';
			echo '<div style="margin:10px;color:'.(strpos($statusStr,'SUCCESS') === false?'red':'green').';">'.$statusStr.'</div>';
			echo '<hr/>';
		}
		if($formSubmit == 'Show Exsiccati Table'){
			$occurArr = $exsManager->getExsOccArr($ometid, 'ometid');
			if($occurArr){
				$exsMetadata = $exsManager->getTitleObj($ometid);
				$exstitle = $exsMetadata['title'].' ['.$exsMetadata['editor'].']';
				echo '<div style="font-size:120%;"><b>'.$exstitle.'</b></div>';
				?>
				<form name="exstableform" method="post" action="batchimport.php" onsubmit="return verifyExsTableForm(this)">
					<div style="margin:10px 0px;">
						<b>Enter your catalog numbers in field associated with record to be imported</b> 
					</div>
					<table class="styledtable">
						<tr><th>Catalog Number</th><th>Ranking</th><th>Details</th></tr>
						<?php 
						foreach($occurArr as $omenid => $occArr){
							//Sort by preferred source collections and ranking
							$prefOcc = array();
							if($source1 || $source2){
								foreach($occArr as $id => $oArr){
									if($oArr['collid'] == $source1){
										array_unshift($prefOcc,$id);
									}
									if($oArr['collid'] == $source2){
										array_push($prefOcc,$id);
									}
								}
							}
							$cnt = 0;
							foreach($prefOcc as $oid){
								echo $exsManager->getExsTableRow($oid,$occArr[$oid],$omenid,$collid);
								unset($occArr[$oid]);
								$cnt++;
							}
							foreach($occArr as $occid => $oArr){
								//List maximun of three occurrences for each exsiccati number
								if($cnt < 3 || $oArr['collid'] == $collid){
									echo $exsManager->getExsTableRow($occid,$oArr,$omenid,$collid);
									$cnt++;
								}
							}
						}
						?>
					</table>
					<div style="margin:10px 0px">
						<b>Dataset Title</b><br/>
						<input name="dataset" type="text" value="" style="width:300px;" /><br/>
						*Enter value to create a dataset to which imported records will be linked 
					</div>
					<div style="margin:15px">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						<input name="ometid" type="hidden" value="<?php echo $ometid; ?>" />
						<input name="source1" type="hidden" value="<?php echo $source1; ?>" />
						<input name="source2" type="hidden" value="<?php echo $source2; ?>" />
						<input name="formsubmit" type="submit" value="Import Selected Records" />
					</div>
				</form>
				<?php 
			}
			else{
				echo '<div style="font-weight:bold;">There are no specimen records linked to this exsiccati title</div>';
			}
		}
		else{
			?>
			<form name="queryform" action="batchimport.php" method="post" onsubmit="return verifyQueryForm(this)">
				<fieldset>
					<legend><b>Batch Import Module</b></legend>
					<div style="margin:10px">
						<b>Target Collection</b><br/>
						<select name="collid">
							<option value="">----------------------------------</option>
							<?php
							$collArr = $exsManager->getCollArr();
							if(!$IS_ADMIN){
								//Get list of collection user has permission to edit 
								$permArr = array();
								if(array_key_exists('CollAdmin',$userRights)){
									$permArr = $userRights['CollAdmin'];
								}
								if(array_key_exists('CollEditor',$userRights)){
									$permArr = array_merge($userRights['CollEditor'],$permArr);
								}
								//Remove collections 
								$collArr = array_intersect_key($collArr,array_flip($permArr));
							}
							foreach($collArr as $id => $collName){
								echo '<option value="'.$id.'" '.($id==$collid?'SELECTED':'').'>'.$collName.'</option>';
							}
							?>
						</select>
					</div>
					<div style="margin:10px">
						<b>Exsiccati Title</b><br/>
						<select name="ometid" style="width:500px;">
							<option value="">------------------------------------</option>
							<?php 
							$exsArr = $exsManager->getTitleArr('', 1);
							foreach($exsArr as $exid => $exTitle){
								echo '<option value="'.$exid.'" '.($ometid==$exid?'SELECTED':'').'>'.$exTitle.'</option>';
							}
							?>
						</select>
					</div>
					<?php 
					if($ometid){
						if($sourceCollArr = $exsManager->getCollArr($ometid)){
							?>
							<div style="margin:10px">
								<div>
									<b>Select up to two collections that are the preferred sources for occurrence records</b>
								</div>
								<div style="margin:5px 0px">
									<b>Source Collection 1</b><br/>
									<select name="source1">
										<option value="">------------------------------------</option>
										<?php 
										foreach($sourceCollArr as $id => $cTitle){
											echo '<option value="'.$id.'" '.($source1==$id?'SELECTED':'').'>'.$cTitle.'</option>';
										}
										?>
									</select>
									
								</div>
								<?php 
								if(count($sourceCollArr) > 1){
									?>
									<div style="margin:5px 0px">
										<b>Source Collection 2</b><br/>
										<select name="source2">
											<option value="">------------------------------------</option>
											<?php 
											foreach($sourceCollArr as $id => $cTitle){
												echo '<option value="'.$id.'" '.($source2==$id?'SELECTED':'').'>'.$cTitle.'</option>';
											}
											?>
										</select>
									</div>
									<?php 
								}
								?>
							</div>
							<?php 
						}
						?>
						<div style="margin:20px">
							<input name="formsubmit" type="submit" value="Show Exsiccati Table" />
						</div>
						<?php 
					}
					else{
						?>
						<div style="margin:20px">
							<input name="formsubmit" type="submit" value="Choose Source Collections" />
						</div>
						<?php 
					}
					?>
				</fieldset>
			</form>
			<?php
		}
		?>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>