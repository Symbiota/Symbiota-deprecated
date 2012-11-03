<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ExsiccatiManager.php');
header("Content-Type: text/html; charset=".$charset);

$ometId = array_key_exists('ometid',$_REQUEST)?$_REQUEST['ometid']:0;
$omenId = array_key_exists('omenid',$_REQUEST)?$_REQUEST['omenid']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$specimenOnly = array_key_exists('specimenonly',$_POST)?$_REQUEST['specimenonly']:0;
$imagesOnly = array_key_exists('imagesonly',$_REQUEST)?$_REQUEST['imagesonly']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
if(!$formSubmit) $specimenOnly = 1;

$statusStr = '';
$isEditor = 0;
if($isAdmin){
	$isEditor = 1;
}

$exsManager = new ExsiccatiManager();
if($isEditor && $formSubmit){
	if($formSubmit == 'Add Exsiccati Title'){
		$exsManager->addTitle($_POST);
	}
	elseif($formSubmit == 'Save'){
		$exsManager->editTitle($_POST);
	}
	elseif($formSubmit == 'Delete Exsiccati'){
		$statusStr = $exsManager->deleteTitle($ometId);
		if(!$statusStr) $ometId = 0;
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
	elseif($formSubmit == 'Add Specimen Link'){
		$exsManager->addOccLink($_POST);
	}
	elseif($formSubmit == 'Save Specimen Link Edit'){
		$exsManager->editOccLink($_POST);
	}
	elseif($formSubmit == 'Delete Link to Specimen'){
		$exsManager->deleteOccLink($omenId,$_POST['occid']);
	}
}

?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Exsiccati</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<script type="text/javascript">

		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var obj = divObjs[i];
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

		function verifyOccAddForm(f){
			if(f.occid.value == ""){
				alert("Occurrence ID can't be empty");
				return false;
			}
			return true;
		}

		function verifyOccEditForm(f){
			if(f.occid.value == ""){
				alert("Occurrences ID can't be empty");
				return false;
			}
			return true;
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

	</script>
</head>

<body>
	<?php 
	$displayLeftMenu = (isset($collections_exsiccati_index)?$collections_exsiccati_index:false);
	include($serverRoot."/header.php");
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="index.php">Exsiccati Index</a>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($statusStr){
			echo '<hr/>';
			echo '<div style="margin:10px;color:red;">'.$statusStr.'</div>';
			echo '<hr/>';
		}
		if(!$ometId && !$omenId){
			?>
			<div id="cloptiondiv">
				<form name="optionform" action="index.php" method="post">
					<fieldset>
					    <legend><b>Options</b></legend>
				    	<div>
				    		<b>Search:</b> 
							<input type="text" name="searchterm" value="<?php echo $searchTerm;?>" size="20" />
						</div>
						<div title="including without linked specimen records">
							<input type="checkbox" name="specimenonly" value="1" <?php echo ($specimenOnly?"CHECKED":"");?>/> 
							Display only those w/ specimen links
						</div>
						<div>
						    <input name='imagesonly' type='checkbox' value='1' <?php echo ($imagesOnly?"CHECKED":""); ?> /> 
						    Display only those w/ images
						</div>
						<div style="margin:5px 0px 0px 5px;">
							<input name="formsubmit" type="submit" value="Rebuild List" />
						</div>
					</fieldset>
				</form>
			</div>			
			<div style="font-weight:bold;font-size:120%;">Exsiccati Titles</div>
			<?php 
			if($isEditor){
				?>
				<div style="cursor:pointer;float:right;" onclick="toggle('exsadddiv');" title="Edit Exsiccati Number">
					<img style="border:0px;" src="../../images/add.png" />
				</div>
				<div id="exsadddiv" style="display:none;">
					<form name="exsaddform" action="index.php" method="post" onsubmit="return verfifyExsAddForm(this)">
						<fieldset style="margin:10px;padding:15px;">
							<legend><b>Add New Exsiccati</b></legend>
							<div style="margin:2px;">
								Title: <input name="title" type="text" value="" style="width:500px;" />
							</div>
							<div style="margin:2px;">
								Abbr: <input name="abbreviation" type="text" value="" style="width:500px;" />
							</div>
							<div style="margin:2px;">
								Editor: <input name="editor" type="text" value="" style="width:300px;" />
							</div>
							<div style="margin:2px;">
								Range: <input name="exsrange" type="text" value="" />
							</div>
							<div style="margin:2px;">
								Source: <input name="source" type="text" value="" style="width:480px;" />
							</div>
							<div style="margin:2px;">
								Notes: <input name="notes" type="text" value="" style="width:500px;" />
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
				$titleArr = $exsManager->getTitleArr($searchTerm, $specimenOnly, $imagesOnly);
				foreach($titleArr as $k => $tArr){
					?>
					<li>
						<a href="index.php?ometid=<?php echo $k.'&imagesonly='.$imagesOnly; ?>">
							<?php echo $tArr['title'].', '.$tArr['editor']; ?>
						</a>
					</li>
					<?php
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
						<span style="cursor:pointer;" onclick="toggle('exseditdiv');" title="Edit Exsiccati">
							<img style="border:0px;" src="../../images/edit.png" />
						</span>
						<span style="cursor:pointer;" onclick="toggle('numadddiv');" title="Add Exsiccati Number">
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
					<fieldset style="margin:10px;padding:15px;">
						<legend><b>Edit Title</b></legend>
						<div style="margin:2px;">
							Title: <input name="title" type="text" value="<?php echo $exsArr['title']; ?>" style="width:500px;" />
						</div>
						<div style="margin:2px;">
							Abbr: <input name="abbreviation" type="text" value="<?php echo $exsArr['abbreviation']; ?>" style="width:500px;" />
						</div>
						<div style="margin:2px;">
							Editor: <input name="editor" type="text" value="<?php echo $exsArr['editor']; ?>" style="width:300px;" />
						</div>
						<div style="margin:2px;">
							Range: <input name="exsrange" type="text" value="<?php echo $exsArr['exsrange']; ?>" />
						</div>
						<div style="margin:2px;">
							Source: <input name="source" type="text" value="<?php echo $exsArr['source']; ?>" style="width:480px;" />
						</div>
						<div style="margin:2px;">
							Notes: <input name="notes" type="text" value="<?php echo $exsArr['notes']; ?>" style="width:500px;" />
						</div>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Save" /> 
						</div>
					</fieldset>
				</form> 
				<form name="exdeleteform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete this exsiccati?');">
					<fieldset style="margin:10px;padding:15px;">
						<legend><b>Delete Exsiccati</b></legend>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Delete Exsiccati" /> 
						</div>
					</fieldset>
				</form>
			</div>
			<hr/> 
			<div id="numadddiv" style="display:none;">
				<form name="numaddform" action="index.php" method="post" onsubmit="return verifyNumAddForm(this);">
					<fieldset style="margin:10px;padding:15px;">
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
					$exsNumArr = $exsManager->getExsNumberArr($ometId,$imagesOnly);
					if($exsNumArr){
						foreach($exsNumArr as $k => $numArr){
							?>
							<li>
								<a href="index.php?omenid=<?php echo $k; ?>">
									<?php echo '#'.$numArr['number'].' - '.$numArr['collector']; ?>
								</a>
								<?php 
								if($numArr['notes']) echo '<div style="margin-left:15px;">'.$numArr['notes'].'</div>';
								?>
							</li>
							<?php
						}
					}
					else{
						echo '<div style="font-weight:bold;font-size:110%;">There are no exsiccati numbers with links to specimens within the system</div>';
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
					<span style="cursor:pointer;" onclick="toggle('numeditdiv');" title="Edit Exsiccati Number">
						<img style="border:0px;" src="../../images/edit.png"/>
					</span>
					<span style="cursor:pointer;" onclick="toggle('occadddiv');" title="Add Occurrence to Exsiccati Number">
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
					<fieldset style="margin:10px;padding:15px;">
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
					<fieldset style="margin:10px;padding:15px;">
						<legend><b>Delete Exsiccati Number</b></legend>
						<div style="margin:10px;">
							<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
							<input name="ometid" type="hidden" value="<?php echo $mdArr['ometid']; ?>" />
							<input name="formsubmit" type="submit" value="Delete Number" /> 
						</div>
					</fieldset>
				</form> 
			</div>
			<div id="occadddiv" style="display:none;">
				<form name="occaddform" action="index.php" method="post" onsubmit="return verifyOccAddForm(this)">
					<fieldset style="margin:10px;padding:15px;">
						<legend><b>Add Occurrence Record to Exsiccati Number</b></legend>
						<div style="margin:2px;">
							Occid: <input name="occid" type="text" value="" />
						</div>
						<div style="margin:2px;">
							Ranking: <input name="ranking" type="text" value="" />
						</div>
						<div style="margin:2px;">
							Notes: <input name="notes" type="text" value="" style="width:500px;" />
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
				$exsOccArr = $exsManager->getExsOccArr($omenId);
				if($exsOccArr){
					?>
					<table style="width:90%;">
						<?php 
						foreach($exsOccArr as $k => $occArr){
							?>
							<tr>
								<td>
									<div style="font-weight:bold;">
										<div style="float:left;"> 
											<?php
											echo $occArr['recordedby'];
											echo ($occArr['recordnumber']?' #'.$occArr['recordnumber'].' ':'s.n. ');
											echo '<span style="margin-left:70px;">'.$occArr['eventdate'].'</span> ';
											?>
										</div>
										<div style="float:right;margin-right:30px;"> 
											<?php 
											if($occArr['catalognumber']){
												echo 'Catalog Number: '.$occArr['catalognumber'];
											}
											?>
										</div>
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
										echo (($occArr['country'] && $occArr['stateprovince'])?', ':'').$occArr['stateprovince'];
										echo ($occArr['county']?', '.$occArr['county']:'');
										echo ($occArr['municipality']?', '.$occArr['municipality']:'');
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
									if(array_key_exists('url',$occArr)){
										?>
										<a href="<?php echo $occArr['url']; ?>">
											<img src="<?php echo $occArr['tnurl']; ?>" style="width:75px;" />
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
											<fieldset style="margin:10px;padding:15px;">
												<legend><b>Edit Occurrence Link</b></legend>
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
										<form name="exnumdeleteform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete the link to this specimen?')">
											<fieldset style="margin:10px;padding:15px;">
												<legend><b>Delete Exsiccati Specimen Link</b></legend>
												<div style="margin:10px;">
													<input name="omenid" type="hidden" value="<?php echo $omenId; ?>" />
													<input name="occid" type="hidden" value="<?php echo $k; ?>" />
													<input name="formsubmit" type="submit" value="Delete Link to Specimen" /> 
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
	include($serverRoot."/footer.php");
	?>
</body>
</html>