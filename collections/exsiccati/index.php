<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ExsiccatiManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$ometId = array_key_exists('ometid',$_REQUEST)?$_REQUEST['ometid']:0;
$omenId = array_key_exists('omenid',$_REQUEST)?$_REQUEST['omenid']:0;
$occidToAdd = array_key_exists('occidtoadd',$_REQUEST)?$_REQUEST['occidtoadd']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$specimenOnly = array_key_exists('specimenonly',$_REQUEST)?$_REQUEST['specimenonly']:0;
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$imagesOnly = array_key_exists('imagesonly',$_REQUEST)?$_REQUEST['imagesonly']:0;
$sortBy = array_key_exists('sortby',$_REQUEST)?$_REQUEST['sortby']:0;
$formSubmit = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';
if(!$formSubmit && !$ometId) $specimenOnly = 1;

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || array_key_exists('CollAdmin',$USER_RIGHTS)){
	$isEditor = 1;
}

$exsManager = new ExsiccatiManager();
if($isEditor && $formSubmit){
	if($formSubmit == 'Add Exsiccati Title'){
		$exsManager->addTitle($_POST,$paramsArr['un']);
	}
	elseif($formSubmit == 'Save'){
		$exsManager->editTitle($_POST,$paramsArr['un']);
	}
	elseif($formSubmit == 'Delete Exsiccati'){
		$statusStr = $exsManager->deleteTitle($ometId);
		if(!$statusStr) $ometId = 0;
	}
	elseif($formSubmit == 'Merge Exsiccati'){
		$statusStr = $exsManager->mergeTitles($ometId,$_POST['targetometid']);
		if(!$statusStr) $ometId = $_POST['targetometid'];
	}
	elseif($formSubmit == 'Add New Number'){
		$exsManager->addNumber($_POST);
	}
	elseif($formSubmit == 'Save Edits'){
		$exsManager->editNumber($_POST);
	}
	elseif($formSubmit == 'Delete Number'){
		$exsManager->deleteNumber($omenId);
		$omenId = 0;
	}
	elseif($formSubmit == 'Transfer Number'){
		$statusStr = $exsManager->transferNumber($omenId,trim($_POST['targetometid'],'k'));
	}
	elseif($formSubmit == 'Add Specimen Link'){
		$statusStr = $exsManager->addOccLink($_POST);
	}
	elseif($formSubmit == 'Save Specimen Link Edit'){
		$exsManager->editOccLink($_POST);
	}
	elseif($formSubmit == 'Delete Link to Specimen'){
		$exsManager->deleteOccLink($omenId,$_POST['occid']);
	}
	elseif($formSubmit == 'Transfer Specimen'){
		$statusStr = $exsManager->transferOccurrence($omenId,$_POST['occid'],trim($_POST['targetometid'],'k'),$_POST['targetexsnumber']);
	}
	elseif($formSubmit == 'dlexsiccati'){
		$exsManager->exportExsiccatiAsCsv($searchTerm, $specimenOnly, $imagesOnly, $collId);
		exit;
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Exsiccati</title>
    <link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript" src="../../js/symb/shared.js?ver=130926"></script>
	<script type="text/javascript">
		function toggleExsEditDiv(){
			toggle('exseditdiv');
			document.getElementById("numadddiv").style.display = "none";
		}

		function toggleNumAddDiv(){
			toggle('numadddiv');
			document.getElementById("exseditdiv").style.display = "none";
		}

		function toggleNumEditDiv(){
			toggle('numeditdiv');
			document.getElementById("occadddiv").style.display = "none";
		}

		function toggleOccAddDiv(){
			toggle('occadddiv');
			document.getElementById("numeditdiv").style.display = "none";
		}

		function verfifyExsAddForm(f){
			if(f.title.value == ""){
				alert("Title can't be empty");
				return false;
			}
			return true;
		}

		function verifyExsEditForm(f){
			if(f.title.value == ""){
				alert("Title can't be empty");
				return false;
			}
			return true;
		}

		function verifyExsMergeForm(f){
			if(t.targetometid == ""){
				alert("You need to select a target exsiccati to merge into");
				return false;
			}
			else{
				return confirm("Are you sure you want to merge this exsiccati into the target below?");
			}
		}

		function verifyNumAddForm(f){
			if(f.exsnumber.value == ""){
				alert("Number can't be empty");
				return false;
			}
			return true;
		}

		function verifyNumEditForm(f){
			if(f.exsnumber.value == ""){
				alert("Number can't be empty");
				return false;
			}
			return true;
		}

		function verifyNumTransferForm(f){
			if(t.targetometid == ""){
				alert("You need to select a target exsiccati to merge into");
				return false;
			}
			else{
				return confirm("Are you sure you want to transfer this exsiccati into the target exsiccati?");
			}
		}

		function verifyOccAddForm(f){
			if(f.occaddcollid.value == ""){
				alert("Please select a collection");
				return false;
			}
			if(f.identifier.value == "" && (f.recordedby.value == "" || f.recordnumber.value == "")){
				alert("Catalog Number or Collector needs to be filled in");
				return false;
			}
			if(f.ranking.value && !isNumeric(f.ranking.value)){
				alert("Ranking can only be a number");
				return false;
			}
			return true;
		}

		function verifyOccEditForm(f){
			if(f.collid.options[0].selected == true || f.collid.options[1].selected){
				alert("The Collection pulldown need to be selected");
				return false;
			}
			if(f.occid.value == ""){
				alert("Occurrences ID can't be empty");
				return false;
			}
			return true;
		}

		function verifyOccTransferForm(f){
			if(f.targetometid.value == ""){
				alert("Please select an exsiccati title");
				return false;
			}
			if(f.targetexsnumber.value == ""){
				alert("Please enter an exsiccati number");
				return false;
			}
			return true;
		}

		function specimenOnlyChanged(cbObj){
			var divObj = document.getElementById('qryextradiv');
			var f = cbObj.form;
			if(cbObj.checked == true){
				divObj.style.display = "block";
			}
			else{
				divObj.style.display = "none";
				f.imagesonly.checked = false;
				f.collid.options[0].selected = true;
			}
		}

		function openIndPU(occId){
			var wWidth = 900;
			if(document.getElementById('maintable').offsetWidth){
				wWidth = document.getElementById('maintable').offsetWidth*1.05;
			}
			else if(document.body.offsetWidth){
				wWidth = document.body.offsetWidth*0.9;
			}
			newWindow = window.open('../individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if(newWindow.opener == null) newWindow.opener = self;
			return false;
		}

		<?php
		if($omenId){
			//Exsiccati number section can have a large number of ometid select look ups; using javascript makes page more efficient
			$titleArr = $exsManager->getTitleArr();
			$selectValues = '';
			//Added "k" prefix to key so that Chrom would maintain the correct sort order
			foreach($titleArr as $k => $v){
				$selectValues .= ',k'.$k.': "'.$v.'"';
			}
			?>
			function buildExsSelect(selectObj){
				var selectValues = {<?php echo substr($selectValues,1); ?>};

				for(key in selectValues) {
					try{
						selectObj.add(new Option(selectValues[key], key), null);
					}
					catch(e){ //IE
						selectObj.add(new Option(selectValues[key], key));
					}
				}
			}
			<?php
		}
		?>
	</script>
</head>

<body>
	<?php
	$displayLeftMenu = (isset($collections_exsiccati_index)?$collections_exsiccati_index:false);
	include($SERVER_ROOT."/header.php");
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<?php
		if($ometId || $omenId){
			echo '<a href="index.php"><b>Return to main Exsiccati Index</b></a>';
		}
		else{
			echo '<a href="index.php"><b>Exsiccati Index</b></a>';
		}
		?>
	</div>
	<!-- This is inner text! -->
	<div id="innertext" style="width:95%;">
		<?php
		if($statusStr){
			echo '<hr/>';
			echo '<div style="margin:10px;color:'.(strpos($statusStr,'SUCCESS') === false?'red':'green').';">'.$statusStr.'</div>';
			echo '<hr/>';
		}
		if(!$ometId && !$omenId){
			?>
			<div id="cloptiondiv" style="width:249px;float:right;">
				<form name="optionform" action="index.php" method="post">
					<fieldset style="background-color:#FFD700;">
					    <legend><b>Options</b></legend>
				    	<div>
				    		<b>Search:</b>
							<input type="text" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
						</div>
						<div title="including without linked specimen records">
							<input type="checkbox" name="specimenonly" value="1" <?php echo ($specimenOnly?"CHECKED":"");?> onchange="specimenOnlyChanged(this)" />
							Display only those w/ specimens
						</div>
						<div id="qryextradiv" style="margin-left:15px;display:<?php echo ($specimenOnly?'block':'none'); ?>;" title="including without linked specimen records">
							<div>
								Limit to:
								<select name="collid" style="width:230px;">
									<option value="">All Collections</option>
									<option value="">-----------------------</option>
									<?php
									$acroArr = $exsManager->getCollArr('all');
									foreach($acroArr as $id => $collTitle){
										echo '<option value="'.$id.'" '.($id==$collId?'SELECTED':'').'>'.$collTitle.'</option>';
									}
									?>
								</select>
							</div>
							<div>
							    <input name='imagesonly' type='checkbox' value='1' <?php echo ($imagesOnly?"CHECKED":""); ?> />
							    Display only those w/ images
							</div>
						</div>
						<div style="margin:5px 0px 0px 5px;">
							Display and sort by:<br />
							<input type="radio" name="sortby" value="0" <?php echo ($sortBy == 0?"CHECKED":""); ?>>Title
							<input type="radio" name="sortby" value="1" <?php echo ($sortBy == 1?"CHECKED":""); ?>>Abbreviation
						</div>
						<div style="float:right;">
							<?php
							$dlUrl = 'index.php?formsubmit=dlexsiccati&searchterm='.$searchTerm.'&specimenonly='.$specimenOnly.'&imagesonly='.$imagesOnly.'&collid='.$collId;
							?>
							<a href="<?php echo $dlUrl; ?>" target="_blank"><img src="../../images/dl.png" style="width:13px" /></a>
						</div>
						<div style="margin:5px 0px 0px 5px;">
							<input name="formsubmit" type="submit" value="Rebuild List" />
						</div>
					</fieldset>
				</form>
			</div>
			<div style="font-weight:bold;font-size:120%;">Exsiccati</div>
			<?php
			if($isEditor){
				?>
				<div style="cursor:pointer;float:right;" onclick="toggle('exsadddiv');" title="Edit Exsiccati Number">
					<img style="border:0px;" src="../../images/add.png" />
				</div>
				<div id="exsadddiv" style="display:none;">
					<form name="exsaddform" action="index.php" method="post" onsubmit="return verfifyExsAddForm(this)">
						<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
							<legend><b>Add New Exsiccati</b></legend>
							<div style="margin:2px;">
								Title:<br/><input name="title" type="text" value="" style="width:480px;" />
							</div>
							<div style="margin:2px;">
								Abbr:<br/><input name="abbreviation" type="text" value="" style="width:480px;" />
							</div>
							<div style="margin:2px;">
								Editor:<br/><input name="editor" type="text" value="" style="width:300px;" />
							</div>
							<div style="margin:2px;">
								Number Range:<br/><input name="exsrange" type="text" value="" />
							</div>
							<div style="margin:2px;">
								Date range:<br/>
								<input name="startdate" type="text" value="" /> -
								<input name="enddate" type="text" value="" />
							</div>
							<div style="margin:2px;">
								Source:<br/><input name="source" type="text" value="" style="width:480px;" />
							</div>
							<div style="margin:2px;">
								Notes:<br/><input name="notes" type="text" value="" style="width:480px;" />
							</div>
							<div style="margin:10px;">
								<input name="formsubmit" type="submit" value="Add Exsiccati Title" />
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			?>
			<ul>
				<?php
				$titleArr = $exsManager->getTitleArr($searchTerm, $specimenOnly, $imagesOnly, $collId, $sortBy);
				if($titleArr){
					foreach($titleArr as $k => $titleStr){
						?>
						<li>
							<a href="index.php?ometid=<?php echo $k.'&specimenonly='.$specimenOnly.'&imagesonly='.$imagesOnly.'&collid='.$collId.'&sortBy='.$sortBy; ?>">
								<?php echo $titleStr; ?>
							</a>
						</li>
						<?php
					}
				}
				else{
					echo '<div style="margin:20px;font-size:120%;">There are no exsiccati matching your request</div>';
				}
				?>
			</ul>
			<?php
		}
		elseif($ometId){
			$exsArr = $exsManager->getTitleObj($ometId);
			?>
			<div style="font-weight:bold;font-size:120%;">
				<?php
				if($isEditor){
					?>
					<div style="float:right;">
						<span style="cursor:pointer;" onclick="toggleExsEditDiv('exseditdiv');" title="Edit Exsiccati">
							<img style="border:0px;" src="../../images/edit.png" />
						</span>
						<span style="cursor:pointer;" onclick="toggleNumAddDiv('numadddiv');" title="Add Exsiccati Number">
							<img style="border:0px;" src="../../images/add.png" />
						</span>
					</div>
					<?php
				}
				echo $exsArr['title'].', '.$exsArr['editor'].($exsArr['exsrange']?' ['.$exsArr['exsrange'].']':'');
				if($exsArr['notes']) echo '<div>'.$exsArr['notes'].'</div>';
				?>
			</div>
			<div id="exseditdiv" style="display:none;">
				<form name="exseditform" action="index.php" method="post" onsubmit="return verifyExsEditForm(this);">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Edit Title</b></legend>
						<div style="margin:2px;">
							Title:<br/><input name="title" type="text" value="<?php echo $exsArr['title']; ?>" style="width:500px;" />
						</div>
						<div style="margin:2px;">
							Abbr:<br/><input name="abbreviation" type="text" value="<?php echo $exsArr['abbreviation']; ?>" style="width:500px;" />
						</div>
						<div style="margin:2px;">
							Editor:<br/><input name="editor" type="text" value="<?php echo $exsArr['editor']; ?>" style="width:300px;" />
						</div>
						<div style="margin:2px;">
							Number range:<br/><input name="exsrange" type="text" value="<?php echo $exsArr['exsrange']; ?>" />
						</div>
						<div style="margin:2px;">
							Date range:<br/>
							<input name="startdate" type="text" value="<?php echo $exsArr['startdate']; ?>" /> -
							<input name="enddate" type="text" value="<?php echo $exsArr['enddate']; ?>" />
						</div>
						<div style="margin:2px;">
							Source:<br/><input name="source" type="text" value="<?php echo $exsArr['source']; ?>" style="width:480px;" />
						</div>
						<div style="margin:2px;">
							Notes:<br/><input name="notes" type="text" value="<?php echo $exsArr['notes']; ?>" style="width:500px;" />
						</div>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Save" />
						</div>
					</fieldset>
				</form>
				<form name="exdeleteform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete this exsiccati?');">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Delete Exsiccati</b></legend>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Delete Exsiccati" />
						</div>
					</fieldset>
				</form>
				<form name="exmergeform" action="index.php" method="post" onsubmit="return verifyExsMergeForm(this);">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Merge Exsiccati</b></legend>
						<div style="margin:10px;">
							Target Exsiccati<br/>
							<select name="targetometid" style="width:650px;">
								<option value="">Select the Target Exsiccati</option>
								<option value="">-------------------------------</option>
								<?php
								$titleArr = $exsManager->getTitleArr();
								unset($titleArr[$ometId]);
								foreach($titleArr as $titleId => $titleStr){
									echo '<option value="'.$titleId.'">'.$titleStr.'</option>';
								}
								?>
							</select>
						</div>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Merge Exsiccati" />
						</div>
					</fieldset>
				</form>
			</div>
			<hr/>
			<div id="numadddiv" style="display:none;">
				<form name="numaddform" action="index.php" method="post" onsubmit="return verifyNumAddForm(this);">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Add Exsiccati Number</b></legend>
						<div style="margin:2px;">
							Exsiccati Number: <input name="exsnumber" type="text" />
						</div>
						<div style="margin:2px;">
							Notes: <input name="notes" type="text" style="width:90%" />
						</div>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Add New Number" />
						</div>
					</fieldset>
				</form>
			</div>
			<div style="margin-left:10px;">
				<ul>
					<?php
					$exsNumArr = $exsManager->getExsNumberArr($ometId,$specimenOnly,$imagesOnly,$collId);
					if($exsNumArr){
						foreach($exsNumArr as $k => $numArr){
							?>
							<li>
								<?php
								echo '<div><a href="index.php?omenid='.$k.'">';
								echo '#'.$numArr['number'].' - '.($numArr['sciname']?'<i>'.$numArr['sciname'].'</i>':'').
								', '.($numArr['collector']?$numArr['collector']:'[collector undefined]');
								echo '</a></div>';
								if($numArr['notes']) echo '<div style="margin-left:15px;">'.$numArr['notes'].'</div>';
								?>
							</li>
							<?php
						}
					}
					else{
						echo '<div style="font-weight:bold;font-size:110%;">';
						echo 'There are no exsiccati numbers in database ';
						echo '</div>';
					}
					?>
				</ul>
			</div>
			<?php
		}
		elseif($omenId){
			$mdArr = $exsManager->getExsNumberObj($omenId);
			if($isEditor){
				?>
				<div style="float:right;">
					<span style="cursor:pointer;" onclick="toggleNumEditDiv('numeditdiv');" title="Edit Exsiccati Number">
						<img style="border:0px;" src="../../images/edit.png"/>
					</span>
					<span style="cursor:pointer;" onclick="toggleOccAddDiv('occadddiv');" title="Add Occurrence to Exsiccati Number">
						<img style="border:0px;" src="../../images/add.png" />
					</span>
				</div>
				<?php
			}
			?>
			<div style="font-weight:bold;font-size:120%;">
				<?php
				echo '<a href="index.php?ometid='.$mdArr['ometid'].'">'.$mdArr['title'].'</a> #'.$mdArr['exsnumber'];
				?>
			</div>
			<div style="margin-left:15px;">
				<?php
				echo $mdArr['abbreviation'].'</br>';
				echo $mdArr['editor'];
				if($mdArr['exsrange']) echo ' ['.$mdArr['exsrange'].']';
				if($mdArr['notes']) echo '</br>'.$mdArr['notes'];
				?>
			</div>
			<div id="numeditdiv" style="display:none;">
				<form name="numeditform" action="index.php" method="post" onsubmit="return verifyNumEditForm(this)">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Edit Exsiccati Number</b></legend>
						<div style="margin:2px;">
							Number: <input name="exsnumber" type="text" value="<?php echo $mdArr['exsnumber']; ?>" style="width:500px;" />
						</div>
						<div style="margin:2px;">
							Notes: <input name="notes" type="text" value="<?php echo $mdArr['notes']; ?>" style="width:500px;" />
						</div>
						<div style="margin:10px;">
							<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
							<input name="formsubmit" type="submit" value="Save Edits" />
						</div>
					</fieldset>
				</form>
				<form name="numdelform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete this exsiccati number?')">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Delete Exsiccati Number</b></legend>
						<div style="margin:10px;">
							<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
							<input name="ometid" type="hidden" value="<?php echo $mdArr['ometid']; ?>" />
							<input name="formsubmit" type="submit" value="Delete Number" />
						</div>
					</fieldset>
				</form>
				<form name="numtransferform" action="index.php" method="post" onsubmit="return verifyNumTransferForm(this);">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Transfer Exsiccati Number</b></legend>
						<div style="margin:10px;">
							Target Exsiccati<br/>
							<select name="targetometid" style="width:650px;" onfocus="buildExsSelect(this)">
								<option value="">Select the Target Exsiccati</option>
								<option value="">-------------------------------</option>
							</select>
						</div>
						<div style="margin:10px;">
							<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
							<input name="ometid" type="hidden" value="<?php echo $mdArr['ometid']; ?>" />
							<input name="formsubmit" type="submit" value="Transfer Number" />
						</div>
					</fieldset>
				</form>
			</div>
			<div id="occadddiv" style="display:<?php echo ($occidToAdd?'block':'none') ?>;">
				<form name="occaddform" action="index.php" method="post" onsubmit="return verifyOccAddForm(this)">
					<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
						<legend><b>Add Occurrence Record to Exsiccati Number</b></legend>
						<div style="margin:2px;">
							Collection:  <br/>
							<select name="occaddcollid">
								<option value="">Select a Collection</option>
								<option value="">----------------------</option>
								<?php
								$collArr = $exsManager->getCollArr();
								foreach($collArr as $id => $collName){
									echo '<option value="'.$id.'">'.$collName.'</option>';
								}
								?>
								<option value="occid">Symbiota Primary Key (occid)</option>
							</select>
						</div>
						<div style="margin:10px 0px;height:40px;">
							<div style="margin:2px;float:left;">
								Catalog Number <br/>
								<input name="identifier" type="text" value="" />
							</div>
							<div style="padding:10px;float:left;">
								<b>- OR -</b>
							</div>
							<div style="margin:2px;float:left;">
								Collector (last name): <br/>
								<input name="recordedby" type="text" value="" />
							</div>
							<div style="margin:2px;float:left;">
								Number: <br/>
								<input name="recordnumber" type="text" value="" />
							</div>
						</div>
						<div style="margin:2px;clear:both;">
							Ranking: <br/>
							<input name="ranking" type="text" value="" />
						</div>
						<div style="margin:2px;">
							Notes: <br/>
							<input name="notes" type="text" value="" style="width:500px;" />
						</div>
						<div style="margin:10px;">
							<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
							<input name="formsubmit" type="submit" value="Add Specimen Link" />
						</div>
					</fieldset>
				</form>
			</div>
			<hr/>
			<div style="margin:15px 10px 0px 0px;">
				<?php
				$occurArr = $exsManager->getExsOccArr($omenId);
				if($exsOccArr = array_shift($occurArr)){
					?>
					<table style="width:90%;">
						<?php
						foreach($exsOccArr as $k => $occArr){
							?>
							<tr>
								<td>
									<div style="font-weight:bold;">
										<?php
										echo $occArr['collname'];
										?>
									</div>
									<div style="">
										<div style="">
											Catalog #: <?php echo $occArr['catalognumber']; ?>
										</div>
										<?php
										if($occArr['occurrenceid']){
											echo '<div style="float:right;">';
											echo $occArr['occurrenceid'];
											echo '</div>';
										}
										?>
									</div>
									<div style="clear:both;">
										<?php
										echo $occArr['recby'];
										echo ($occArr['recnum']?' #'.$occArr['recnum'].' ':' s.n. ');
										echo '<span style="margin-left:70px;">'.$occArr['eventdate'].'</span> ';
										?>
									</div>
									<div style="clear:both;">
										<?php
										echo '<i>'.$occArr['sciname'].'</i> ';
										echo $occArr['author'];
										?>
									</div>
									<div>
										<?php
										echo $occArr['country'];
										echo (($occArr['country'] && $occArr['state'])?', ':'').$occArr['state'];
										echo ($occArr['county']?', '.$occArr['county']:'');
										echo ($occArr['locality']?', '.$occArr['locality']:'');
										?>
									</div>
									<div>
										<?php echo ($occArr['notes']?$occArr['notes']:''); ?>
									</div>
									<div>
										<a href="#" onclick="openIndPU(<?php echo $k; ?>)">
											Full Record Details
										</a>
									</div>
								</td>
								<td style="width:100px;">
									<?php
									if(array_key_exists('img',$occArr)){
										$imgArr = array_shift($occArr['img']);
										?>
										<a href="<?php echo $imgArr['url']; ?>">
											<img src="<?php echo $imgArr['tnurl']; ?>" style="width:75px;" />
										</a>
										<?php
									}
									if($isEditor){
										?>
										<div style="cursor:pointer;float:right;" onclick="toggle('occeditdiv-<?php echo $k; ?>');" title="Edit Occurrence Link">
											<img style="border:0px;" src="../../images/edit.png"/>
										</div>
										<?php
									}
									?>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<div id="occeditdiv-<?php echo $k; ?>" style="display:none;">
										<form name="occeditform-<?php echo $k; ?>" action="index.php" method="post" onsubmit="return verifyOccEditForm(this)">
											<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
												<legend><b>Edit Specimen Link</b></legend>
												<div style="margin:2px;">
													Ranking: <input name="ranking" type="text" value="<?php echo $occArr['ranking']; ?>" />
												</div>
												<div style="margin:2px;">
													Notes: <input name="notes" type="text" value="<?php echo $occArr['notes']; ?>" style="width:450px;" />
												</div>
												<div style="margin:10px;">
													<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
													<input name="occid" type="hidden" value="<?php echo $k; ?>" />
													<input name="formsubmit" type="submit" value="Save Specimen Link Edit" />
												</div>
											</fieldset>
										</form>
										<form name="occdeleteform-<?php echo $k; ?>" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete the link to this specimen?')">
											<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
												<legend><b>Delete Specimen Link</b></legend>
												<div style="margin:10px;">
													<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
													<input name="occid" type="hidden" value="<?php echo $k; ?>" />
													<input name="formsubmit" type="submit" value="Delete Link to Specimen" />
												</div>
											</fieldset>
										</form>
										<form name="occtransferform-<?php echo $k; ?>" action="index.php" method="post" onsubmit="return verifyOccTransferForm(this)">
											<fieldset style="margin:10px;padding:15px;background-color:#B0C4DE;">
												<legend><b>Transfer Specimen Link</b></legend>
												<div style="margin:10px;">
													Target Exsiccati Title<br/>
													<select name="targetometid" style="width:650px;" onfocus="buildExsSelect(this)">
														<option value="">Select the Target Exsiccati</option>
														<option value="">-------------------------------</option>
													</select>
												</div>
												<div style="margin:10px;">
													Target Exsiccati Number<br/>
													<input name="targetexsnumber" type="text" value="" />
												</div>
												<div style="margin:10px;">
													<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
													<input name="occid" type="hidden" value="<?php echo $k; ?>" />
													<input name="formsubmit" type="submit" value="Transfer Specimen" />
												</div>
											</fieldset>
										</form>
									</div>
									<div style="margin:10px 0px 10px 0px;">
										<hr/>
									</div>
								</td>
							</tr>
							<?php
						}
						?>
					</table>
					<?php
				}
				else{
					echo '<li>There are no specimens linked to this exsiccati number</li>';
				}
				?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT."/footer.php");
	?>
</body>
</html>