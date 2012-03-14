<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ExsiccatiManager.php');
header("Content-Type: text/html; charset=".$charset);

$ometId = array_key_exists('ometid',$_REQUEST)?$_REQUEST['ometid']:0;
$omenId = array_key_exists('omenid',$_REQUEST)?$_REQUEST['omenid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$isEditable = 0;
if($isAdmin){
	$isEditable = 1;
}

$exsManager = new ExsiccatiManager();

$exsNumArr = array();
$exsOccArr = array();
$titleArr = array();
if($ometId){
	$exsNumArr = $exsManager->getExsNumberArr($ometId);
}
elseif($omenId){
	$exsOccArr = $exsManager->getExsOccArr($omenId);
}
else{
	$titleArr = $exsManager->getTitleArr();
}

$statusStr = '';
$isEditor = false;

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

	</script>
</head>

<body>
	<?php 
	$displayLeftMenu = (isset($collections_exsiccati_index)?$collections_exsiccati_index:false);
	include($serverRoot."/header.php");
	?>
	<!-- This is inner text! -->
	<div id="innertext" style="width:600px;">
		<?php
		if($titleArr){
			?>
			<ul>
				<?php  
				foreach($titleArr as $k => $tArr){
					?>
					<li>
						<a href="index.php?ometid=<?php echo $k; ?>">
							<?php echo $tArr['t'].', '.$tArr['e'].($tArr['r']?' ('.$tArr['r'].')':''); ?>
						</a>
					</li>
					<?php
				}
				?>
			</ul>
			<?php  
		}
		elseif($exsNumArr){
			$exArr = $exsNumArr['ex'];
			unset($exsNumArr['ex']);
			?>
			<div style="font-weight:bold;font-size:110%;">
				<?php 
				if($isEditable){
					?>
					<div style="float:right;cursor:pointer;" onclick="toggle('editexdiv');" title="Edit Exsiccati">
						<img style="border:0px;" src="../../images/edit.png"/>
					</div>
					<?php 
				}
				echo $exArr['t'].', '.$exArr['e'].($exArr['r']?' ['.$exArr['r'].']':'');
				?>
			</div>
			<div id="editexdiv" style="display:none;">
				<form name="exeditform" action="index.php" method="post">
					<fieldset style="margin:10px;padding:15px;">
						<legend><b>Edit Title</b></legend>
						<div style="margin:2px;">
							Title: <input name="title" type="text" value="<?php echo $exArr['t']; ?>" style="width:500px;" />
						</div>
						<div style="margin:2px;">
							Abbr: <input name="abbreviation" type="text" value="<?php echo $exArr['a']; ?>" style="width:500px;" /><br/>
						</div>
						<div style="margin:2px;">
							Editor: <input name="editor" type="text" value="<?php echo $exArr['e']; ?>" style="width:300px;" /><br/>
						</div>
						<div style="margin:2px;">
							Range: <input name="range" type="text" value="<?php echo $exArr['r']; ?>" /><br/>
						</div>
						<div style="margin:2px;">
							Source: <input name="source" type="text" value="<?php echo $exArr['s']; ?>" style="width:480px;" /><br/>
						</div>
						<div style="margin:2px;">
							Notes: <input name="notes" type="text" value="<?php echo $exArr['n']; ?>" style="width:500px;" /><br/>
						</div>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Submit Edits" /> 
						</div>
					</fieldset>
				</form> 
				<form name="exaddform" action="index.php" method="post">
					<fieldset style="margin:10px;padding:15px;">
						<legend><b>Add New Exsiccati</b></legend>
						<div style="margin:2px;">
							Title: <input name="title" type="text" value="<?php echo $exArr['t']; ?>" style="width:500px;" /><br/>
						</div>
						<div style="margin:2px;">
							Abbr: <input name="abbreviation" type="text" value="<?php echo $exArr['a']; ?>" style="width:500px;" /><br/>
						</div>
						<div style="margin:2px;">
							Editor: <input name="editor" type="text" value="<?php echo $exArr['e']; ?>" style="width:300px;" /><br/>
						</div>
						<div style="margin:2px;">
							Range: <input name="range" type="text" value="<?php echo $exArr['r']; ?>" /><br/>
						</div>
						<div style="margin:2px;">
							Source: <input name="source" type="text" value="<?php echo $exArr['s']; ?>" style="width:480px;" /><br/>
						</div>
						<div style="margin:2px;">
							Notes: <input name="notes" type="text" value="<?php echo $exArr['n']; ?>" style="width:500px;" />
						</div>
						<div style="margin:10px;">
							<input name="formsubmit" type="submit" value="Create New Exsiccati" /> 
						</div>
					</fieldset>
				</form> 
				<form name="exdeleteform" action="index.php" method="post">
					<fieldset style="margin:10px;padding:15px;">
						<legend><b>Delete Exsiccati</b></legend>
						<div style="margin:10px;">
							<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
							<input name="formsubmit" type="submit" value="Delete Exsiccati" /> 
						</div>
					</fieldset>
				</form> 
			</div>
			<div style="margin-left:10px;">
				<ul>
					<?php 
					foreach($exsNumArr as $k => $numArr){
						?>
						<li>
							<a href="index.php?omenid=<?php echo $k; ?>">
								<?php echo $numArr['n'].' - '.$numArr['c']; ?>
							</a>
							<?php 
							if($isEditable){
								?>
								<span style="float:right;cursor:pointer;" onclick="toggle('editnumdiv-<?php echo $k; ?>');" title="Edit Exsiccati Number">
									<img style="border:0px;" src="../../images/edit.png"/>
								</span>
								<?php 
							}
							?>
							<div id="editnumdiv-<?php echo $k; ?>">
								<form name="exnumeditform" action="index.php" method="post">
									<fieldset style="margin:10px;padding:15px;">
										<legend><b>Edit Exsiccati Number</b></legend>
										<div style="margin:2px;">
											Title: <input name="title" type="text" value="<?php echo $exArr['t']; ?>" style="width:500px;" />
										</div>
										<div style="margin:2px;">
											Abbr: <input name="abbreviation" type="text" value="<?php echo $exArr['a']; ?>" style="width:500px;" /><br/>
										</div>
										<div style="margin:2px;">
											Editor: <input name="editor" type="text" value="<?php echo $exArr['e']; ?>" style="width:300px;" /><br/>
										</div>
										<div style="margin:2px;">
											Range: <input name="range" type="text" value="<?php echo $exArr['r']; ?>" /><br/>
										</div>
										<div style="margin:2px;">
											Source: <input name="source" type="text" value="<?php echo $exArr['s']; ?>" style="width:480px;" /><br/>
										</div>
										<div style="margin:2px;">
											Notes: <input name="notes" type="text" value="<?php echo $exArr['n']; ?>" style="width:500px;" /><br/>
										</div>
										<div style="margin:10px;">
											<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
											<input name="formsubmit" type="submit" value="Submit Edits" /> 
										</div>
									</fieldset>
								</form> 
								<form name="exnumdeleteform" action="index.php" method="post">
									<fieldset style="margin:10px;padding:15px;">
										<legend><b>Delete Exsiccati Number</b></legend>
										<div style="margin:10px;">
											<input name="ometid" type="hidden" value="<?php echo $ometId; ?>" />
											<input name="formsubmit" type="submit" value="Delete Exsiccati Number" /> 
										</div>
									</fieldset>
								</form> 
							</div>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php 
		}
		elseif($exsOccArr){
			$title = $exsOccArr['t'];
			unset($exsOccArr['t']);
			?>
			<div style="font-weight:bold;font-size:110%;"><?php echo $title; ?></div>
			<div style="margin-left:10px;">
				<ul>
					<?php 
					foreach($exsOccArr as $k => $occArr){
						?>
						<li>
							<a href="../individual/index.php?occid=<?php echo $k; ?>">
								<?php 
								echo $occArr['rb'].' '.$occArr['rn'].' '.$occArr['d'].' ';
								?>
							</a>
							<a href="<?php echo $occArr['url']; ?>">
								<img src="<?php echo $occArr['tn']; ?>" style="width:75px;" />
							</a>
						</li>
						<?php 
					}
					?>
				</ul>
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

