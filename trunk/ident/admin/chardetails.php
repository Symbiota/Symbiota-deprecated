<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/IdentCharAdmin.php');

if(!$symbUid) header('Location: ../../profile/index.php?refurl=../ident/admin/index.php');

$keyManager = new IdentCharAdmin();
//$keyManager->setCollId($collId);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$cid = array_key_exists('cid',$_REQUEST)?$_REQUEST['cid']:0;
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;

$keyManager->setCid($cid);

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Create'){
		$statusStr = $keyManager->createCharacter($_POST,$paramsArr['un']);
		$cid = $keyManager->getCid();
	}
	elseif($formSubmit == 'Save Char'){
		$statusStr = $keyManager->editCharacter($_POST);
	}
	elseif($formSubmit == 'Add State'){
		$keyManager->createCharState($_POST['charstatename'],$paramsArr['un']);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Save State'){
		$statusStr = $keyManager->editCharState($_POST);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Delete Char'){
		$status = $keyManager->deleteChar();
		if($status) $cid = 0;
	}
	elseif($formSubmit == 'Delete State'){
		$status = $keyManager->deleteCharState($_POST['cs']);
		$tabIndex = 1;
	}
	elseif($formSubmit == 'Save Taxonomic Relevance'){
		$status = $keyManager->saveTaxonRelevance($_POST['tid'], $_POST['relation'], $_POST['notes']);
		$tabIndex = 2;
	}
	elseif($formSubmit == 'deltaxon'){
		$status = $keyManager->deleteTaxonRelevance($_POST['tid']);
		$tabIndex = 2;
	}
}

if(!$cid) header('Location: index.php');

?>
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title>Character Admin</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;

		$(document).ready(function() {
			$('#tabs').tabs(
				{ active: tabIndex }
			);
		});

		function updateUnits(obj){
			var unitObj = document.getElementById("units");
			if(obj.value == "IN" || obj.value == "RN"){
				unitObj.style.display = "block";
			}
			else{
				unitObj.style.display = "none";
			}				
		}

		function validateCharEditForm(f){
			if(f.charname.value == ""){
				alert("Character name must not be null");
				return false;
			} 
			if(f.chartype.value == ""){
				alert("Character type must not be null");
				return false;
			} 
			return true;
		}

		function validateStateAddForm(f){
			if(f.charstatename.value == ""){
				alert("Character state must not be null");
				return false;
			} 
			return true;
		}
		
		function validateStateEditForm(f){
			if(!isNumeric(f.sortsequence.value)){
				alert("Sort Sequence field must be numeric");
				return false;
			}
			return true;
		}

		function validateTaxonAddForm(f){
			if(f.tid.value)){
				alert("Please select a taxonomic name!");
				return false;
			}
			if(f.relation.value)){
				alert("Please select a toxonomic relevance!");
				return false;
			}
			return true;
		}
	</script>
	<script type="text/javascript" src="../../js/symb/ident.admin.js"></script>
	<style type="text/css">
		input{ autocomplete: off; } 
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($ident_admin_indexMenu)?$ident_admin_indexMenu:true);
	include($serverRoot."/header.php");
	if(isset($collections_loans_indexCrumbs)){
		if($collections_loans_indexCrumbs){
			?>
			<div class='navpath'>
				<?php echo $ident_admin_indexCrumbs; ?>
				<a href='index.php'> <b>Character Management</b></a>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt;&gt; 
			<a href='index.php'> <b>Character Management</b></a>
		</div>
		<?php 
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($symbUid){
			if($statusStr){
				?>
				<hr/>
				<div style="margin:15px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php 
			}
			$charStateArr = $keyManager->getCharStateArr($cid);
			?>
			<div id="tabs" style="margin:0px;">
			    <ul>
					<li><a href="#chardetaildiv"><span>Details</span></a></li>
					<li><a href="#charstatediv"><span>Character States</span></a></li>
					<li><a href="#tlinkdiv"><span>Taxonomic Linkages</span></a></li>
					<li><a href="#chardeldiv"><span>Admin</span></a></li>
				</ul>
				<div id="chardetaildiv">
					<?php 
					//Show character details
					$charArr = $keyManager->getCharDetails($cid);
					?>
					<form name="chareditform" action="chardetails.php" method="post" onsubmit="return validateCharEditForm(this)">
						<fieldset style="margin:15px;padding:15px;">
							<legend><b>Character Details</b></legend>
							<div style="padding-top:4px;">
								<b>Character Name</b><br />
								<input type="text" name="charname" maxlength="150" style="width:400px;" value="<?php echo $charArr['charname']; ?>" />
							</div>
							<div style="padding-top:8px;float:left;">
								<div style="float:left;">
									<b>Type</b><br />
									<select id="type" name="chartype" style="width:180px;" onchange="updateUnits(this);">
										<option value="UM">Unordered Multi-state</option>
										<option value="IN" <?php echo ($charArr['chartype']=='IN'?'SELECTED':'');?>>Integer</option>
										<option value="RN" <?php echo ($charArr['chartype']=='RN'?'SELECTED':'');?>>Real Number</option>
									</select>
								</div>
								<div id="units" style="display:<?php echo ((($charArr['chartype']=='IN')||($charArr['chartype']=='RN'))?'block':'none');?>;margin-left:15px;float:left;">
									<b>Units</b><br />
									<input type="text" name="units" maxlength="45" style="width:100px;" value="<?php echo $charArr['units']; ?>" title="" />
								</div>
								<div style="margin-left:15px;float:left;">
									<b>Difficulty</b><br />
									<select name="difficultyrank" style="width:100px;">
										<option value="1">Easy</option>
										<option value="2" <?php echo ($charArr['difficultyrank']=='2'?'SELECTED':'');?>>Intermediate</option>
										<option value="3" <?php echo ($charArr['difficultyrank']=='3'?'SELECTED':'');?>>Advanced</option>
										<option value="4" <?php echo ($charArr['difficultyrank']=='4'?'SELECTED':'');?>>Hidden</option>
									</select>
								</div>
								<div style="float:left;margin-left:15px;">
									<b>Heading</b><br />
									<select name="hid" style="width:125px;">
										<option value="">Select Heading</option>
										<option value="">---------------------</option>
										<?php 
										$headingArr = $keyManager->getHeadingArr();
										foreach($headingArr as $k => $v){
											echo '<option value="'.$k.'" '.($k==$charArr['hid']?'SELECTED':'').'>'.$v.'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div style="padding-top:8px;clear:both;">
								<b>Help URL</b><br />
								<input type="text" name="helpurl" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['helpurl']; ?>" onchange=" " />
							</div>
							<div style="padding-top:8px;clear:both;">
								<b>Description</b><br />
								<input type="text" name="description" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['description']; ?>" onchange=" " />
							</div>
							<div style="padding-top:8px;float:left;">
								<b>Notes</b><br />
								<input type="text" name="notes" tabindex="100" maxlength="32" style="width:500px;" value="<?php echo $charArr['notes']; ?>" onchange=" " />
							</div>
							<div style="width:100%;padding-top:6px;float:left;">
								<div style="float:left;">
									<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
									<button name="formsubmit" type="submit" value="Save Char">Save</button>
								</div>
								<div style="float:right;">
									Entered By:
									<input type="text" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $charArr['enteredby']; ?>" onchange=" " disabled />
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<div id="charstatediv">
					<div style="float:right;margin:10px;">
						<a href="#" onclick="toggle('newstatediv');">
							<img src="../../images/add.png" alt="Create New Character State" />
						</a>
					</div>
					<div id="newstatediv" style="display:<?php echo ($charStateArr?'none':'block');?>;">
						<form name="stateaddform" action="chardetails.php" method="post" onsubmit="return validateStateAddForm(this)">
							<fieldset style="margin:15px;padding:15px;">
								<legend><b>Add Character State</b></legend>
								<div style="padding-top:4px;">
									<b>Character State Name</b><br />
									<input type="text" name="charstatename" maxlength="255" style="width:400px;" value="" />
								</div>
								<div style="width:100%;padding-top:6px;float:left;">
									<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
									<button name="formsubmit" type="submit" value="Add State">Add Character State</button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php 
					if($charStateArr){
						echo '<h3>Character States</h3>';
						echo '<ul>';
						foreach($charStateArr as $cs => $stateArr){
							echo '<li>';
							echo '<a href="#" onclick="toggle(\'cs-'.$cs.'Div\');">'.$stateArr['charstatename'].'</a>';
							?>
							<div id="<?php echo 'cs-'.$cs.'Div'; ?>" style="display:none;">
								<form name="stateeditform-<?php echo $cs; ?>" action="chardetails.php" method="post" onsubmit="return validateStateEditForm(this)">
									<fieldset  style="margin:15px;padding:15px;">
										<legend><b>Character State Details</b></legend>
										<div>
											<b>Character State Name</b><br />
											<input type="text" name="charstatename" maxlength="255" style="width:300px;" value="<?php echo $stateArr['charstatename']; ?>" />
										</div>
										<div style="padding-top:2px;clear:both;">
											<b>Illustration URL</b><br />
											<input type="text" name="illustrationurl" maxlength="250" style="width:500px;" value="<?php echo $stateArr['illustrationurl']; ?>" />
										</div>
										<div style="padding-top:2px;clear:both;">
											<b>Description</b><br />
											<input type="text" name="description" maxlength="255" style="width:500px;" value="<?php echo $stateArr['description']; ?>"/>
										</div>
										<div style="padding-top:2px;clear:both;">
											<b>Notes</b><br />
											<input type="text" name="notes" style="width:500px;" value="<?php echo $stateArr['notes']; ?>" onchange=" " />
										</div>
										<div style="padding-top:2px;clear:both;">
											<b>Sort Sequence</b><br />
											<input type="text" name="sortsequence" value="<?php echo $stateArr['sortsequence']; ?>" />
										</div>
										<div style="width:100%;padding-top:4px;float:left;">
											<div style="float:left;">
												<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
												<input name="cs" type="hidden" value="<?php echo $cs; ?>" />
												<button name="formsubmit" type="submit" value="Save State">Save</button>
											</div>
											<div style="margin-left:5px;float:left;">
												<button name="formsubmit" type="submit" value="Delete State">Delete</button>
											</div>
											<div style="float:right;">
												Entered By:
												<input type="text" name="enteredby" tabindex="96" maxlength="32" style="width:100px;" value="<?php echo $stateArr['enteredby']; ?>" onchange=" " disabled />
											</div>
										</div>
									</fieldset>
								</form>
							</div>
							<?php
							echo '</li>';
						}
						echo '</ul>';
					}
					?>
				</div>
				<div id="tlinkdiv">
					<div style="margin:15px;">
						<div style="float:right;margin:10px;">
							<a href="#" onclick="toggle('taxonAddDiv');">
								<img src="../../images/add.png" alt="Create New Character State" />
							</a>
						</div>
						<div style="margin:10px;">
							<b>Taxonomic relevance of character</b> - 
							Tag taxonomic nodes where character is most relevant. 
							Taxonomic branches can also be excluded. 
							For example, left type is typically relevant to most flowering plants, though typically not used to identify Cactaceae.   
						</div>
						<?php 
						$tLinks = $keyManager->getTaxonRelevance();
						?>
						<div id="taxonAddDiv" style="display:<?php echo ($tLinks?'none':'block'); ?>;margin:15px;">
							<form name="taxonAddForm" action="chardetails.php" method="post" onsubmit="return validateTaxonAddForm(this)">
								<fieldset style="padding:20px;">
									<legend><b>Add Taxonomic Relevance Definition</b></legend>
									<div style="height:15px;">
										<div style="float:left;margin:3px;">
											<select name="tid">
												<option value="0">Select Taxon</option>
												<option value="0">--------------------</option>
												<?php 
												$taxonArr = $keyManager->getTaxonArr();
												foreach($taxonArr as $tid => $sciname){
													echo '<option value="'.$tid.'">'.$sciname.'</option>';
												}
												?>
											</select>
										</div>
										<div style="float:left;margin:3px;">
											<select name="relation">
												<option value="include">Relevant</option>
												<option value="exclude">Exclude</option>
											</select>
										</div>
									</div>
									<div style="margin:3px;clear:both;">
										<b>Notes</b><br/> 
										<input name="notes" type="text" value="" style="width:90%" />
									</div>
									<div style="margin:15px;">
										<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
										<button name="formsubmit" type="submit" value="Save Taxonomic Relevance">Save Taxonomic Relevance</button>
									</div>
								</fieldset>
							</form>
						</div>
						<?php 
						if($tLinks){
							if(isset($tLinks['include'])){
								?>
								<fieldset style="padding:20px;">
									<legend><b>Relevant Taxa</b></legend>
									<?php 
									foreach($tLinks['include'] as $tid => $tArr){
										?>
										<div style="margin:3px;clear:both;">
											<?php 
											echo '<div style="float:left;"><b>'.$tArr['sciname'].'</b>'.($tArr['notes']?' - '.$tArr['notes']:'').'</div> ';
											?>
											<form name="delTaxonForm" action="chardetails.php" method="post" style="float:left;margin-left:5px;" onsubmit="return comfirm('Are you sure you want to delete this relationship?')">
												<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
												<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
												<input name="formsubmit" type="hidden" value="deltaxon" />
												<input type="image" src="../../images/del.gif" style="width:15px;" />
											</form>
										</div>
										<?php 
									}
									?>
								</fieldset>
								<?php 
							}
							if(isset($tLinks['exclude'])){
								?>
								<fieldset style="padding:20px;">
									<legend><b>Exclude Taxa</b></legend>
									<?php 
									foreach($tLinks['exclude'] as $tid => $tArr){
										?>
										<div style="margin:3px;">
											<?php 
											echo '<div style="float:left;"><b>'.$tArr['sciname'].'</b>'.($tArr['notes']?' - '.$tArr['notes']:'').'</div> ';
											?>
											<form name="delTaxonForm" action="chardetails.php" method="post" style="float:left;margin-left:5px;" onsubmit="return comfirm('Are you sure you want to delete this relationship?')">
												<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
												<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
												<input name="formsubmit" type="hidden" value="deltaxon" />
												<input type="image" src="../../images/del.gif" style="width:15px;" />
											</form>
										</div>
										<?php 
									}
									?>
								</fieldset>
								<?php 
							}
						}
						?>
					</div>
				</div>
				<div id="chardeldiv">
					<form name="delcharform" action="chardetails.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this character?')">
						<fieldset style="width:350px;margin:20px;padding:20px;">
							<legend><b>Delete Character</b></legend>
							<?php 
							if($charStateArr){
								echo '<div style="font-weight:bold;margin-bottom:15px;">';
								echo 'Character cannot be deleted until all character states are removed';
								echo '</div>';
							}
							?>
							<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
							<button name="formsubmit" type="submit" value="Delete Char" <?php if($charStateArr) echo 'DISABLED'; ?>>Delete</button>
						</fieldset>
					</form>
				</div>
			</div>	
			<?php 
		}
		else{
			if(!$isEditor){
				echo '<h2>You are not authorized to add characters</h2>';
			}
			else{
				echo '<h2>ERROR: unknown error, please contact system administrator</h2>';
			}
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>

