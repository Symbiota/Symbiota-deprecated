<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistLoaderManager.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../checklists/tools/checklistloader.php?'.$_SERVER['QUERY_STRING']);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:""; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$hasHeader = array_key_exists("hasheader",$_REQUEST)?$_REQUEST["hasheader"]:"";
$thesId = array_key_exists("thes",$_REQUEST)?$_REQUEST["thes"]:0;
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 

$clLoaderManager = new ChecklistLoaderManager();
$clLoaderManager->setClid($clid);
$clMeta = $clLoaderManager->getChecklistMetadata();

$isEditor = false;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = true;
}
if($isEditor){
}
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Species Checklist Loader</title>
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function validateUploadForm(thisForm){
			var testStr = document.getElementById("uploadfile").value;
			if(testStr == ""){
				alert("Please select a file to upload");
				return false;
			}
			testStr = testStr.toLowerCase();
			if(testStr.indexOf(".csv") == -1 && testStr.indexOf(".CSV") == -1){
				alert("Document "+document.getElementById("uploadfile").value+" must be a CSV file (with a .csv extension)");
				return false;
			}
			return true;
		}

		function displayErrors(clickObj){
			clickObj.style.display='none';
			document.getElementById('errordiv').style.display = 'block';
		}
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = true;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<?php 
		if($pid) echo '<a href="'.$clientRoot.'/projects/index.php?proj='.$pid.'">';
		echo '<a href="../checklist.php?cl='.$clid.'&pid='.$pid.'">Return to Checklist</a> &gt;&gt; '; 
		?> 
		<a href="checklistloader.php?clid=<?php echo $clid.'&pid='.$pid; ?>"><b>Checklists Loader</b></a>
	</div>
	<?php 
	if($statusStr = $clLoaderManager->getErrorStr()){
		echo '<div style="margin:30px;font-size:110%;font-weight:bold;color:red;">'.$statusStr.'</div>';
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>
			<a href="<?php echo $clientRoot."/checklists/checklist.php?cl=".$clid.'&pid='.$pid; ?>">
				<?php echo $clMeta['name']; ?>
			</a>
		</h1>
		<div style="margin:10px;">
			<b>Authors:</b> <?php echo $clMeta['authors']; ?>
		</div>
		<?php 
			if($isEditor){ 
				if($action == "Upload Checklist"){
					?>
					<div style='margin:10px;'>
						<ul>
							<li>Loading checklist...</li>
							<?php 
							$cnt = $clLoaderManager->uploadCsvList($hasHeader,$thesId);
							$errorArr = $clLoaderManager->getErrorArr();
							$probCnt = count($clLoaderManager->getProblemTaxa());
							?>
							<li>Upload status...</li>
							<li style="margin-left:10px;">Taxa successfully loaded: <?php echo $cnt; ?></li>
							<li style="margin-left:10px;">Problematic Taxa: <?php echo $probCnt.($probCnt?' (see below)':''); ?></li>
							<li style="margin-left:10px;">General errors: <?php echo count($errorArr); ?></li>
						</ul>
						<?php 
						if($probCnt){
							echo '<fieldset>';
							echo '<legend><b>Problematic Taxa Resolution</b></legend>';
							$clLoaderManager->resolveProblemTaxa();
							echo '</fieldset>';
						}
						//General errors 
						if($errorArr){
							?>
							<fieldset style="padding:20px;">
								<legend><b>General Errors</b></legend>
								<a href="#" onclick="displayErrors(this);return false;"><b>Display <?php echo count($errorArr); ?> general errors</b></a>
								<div id="errordiv" style="display:none">
									<ol style="margin-left:15px;">
										<?php 
										foreach($errorArr as $errStr){
											echo '<li>'.$errStr.'</li>';
										}
										?>
									</ol>
								</div>
							</fieldset>
							<?php 	
						}
						?>
					</div>
					<?php 
				}
				else{
					?>
					<form enctype="multipart/form-data" action="checklistloader.php" method="post" onsubmit="return validateUploadForm(this);">
						<fieldset>
							<legend>Checklist Upload Form</legend>
							<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
							<div style="font-weight:bold;">
								Checklist File: 
								<input id="uploadfile" name="uploadfile" type="file" size="45" />
							</div>
							<div>
								<input type="checkbox" name="hasheader" value="1" <?php echo ($hasHeader||!$action?"CHECKED":""); ?> />
								First line contains header
							</div>
							<div>
								Taxonomic Resolution:
								<select name="thes">
									<option value="">Leave Taxonomy As Is</option>
									<?php 
									$thesArr = $clLoaderManager->getThesauri();
									foreach($thesArr as $k => $v){
										echo "<option value='".$k."'>".$v."</option>";
									}
									?>
								</select>
								
							</div>
							<div style="margin:10px;">
								<div>Must be a CSV text file that follows one of the following criteria. 
								Note that Excel spreadsheets can be saved as a CSV file.</div>
								<ul>
									<li>First column consisting of the scientific name, with or without authors</li>
									<li>First row contains following column names (in any order):</li>
									<ul>
										<li>sciname (required)</li>
										<li>family (optional)</li>
										<li>habitat (optional)</li>
										<li>abundance (optional)</li>
										<li>notes (optional)</li>
									</ul>
								</ul>
							</div>
							<div style="margin-top:10px;">
								<input id="clloadsubmit" name="action" type="submit" value="Upload Checklist" />
								<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
							</div>
						</fieldset>
					</form>
				<?php
				} 
			}
			else{
				echo "<h2>You appear not to have rights to edit this checklist. If you think this is in error, contact an administrator</h2>";
			}
		?>

	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>
</body>
</html>
