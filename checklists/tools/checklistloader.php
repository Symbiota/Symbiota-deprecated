<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistLoaderManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../checklists/tools/checklistloader.php?'.$_SERVER['QUERY_STRING']);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:"";
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$thesId = array_key_exists("thes",$_REQUEST)?$_REQUEST["thes"]:0;
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$clLoaderManager = new ChecklistLoaderManager();
$clLoaderManager->setClid($clid);
$clMeta = $clLoaderManager->getChecklistMetadata();

$isEditor = false;
if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
	$isEditor = true;
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Species Checklist Loader</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
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
	include($SERVER_ROOT.'/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<?php
		if($pid) echo '<a href="'.$CLIENT_ROOT.'/projects/index.php?pid='.$pid.'">';
		echo '<a href="../checklist.php?clid='.$clid.'&pid='.$pid.'">Return to Checklist</a> &gt;&gt; ';
		?>
		<a href="checklistloader.php?clid=<?php echo $clid.'&pid='.$pid; ?>"><b>Checklists Loader</b></a>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>
			<a href="<?php echo $CLIENT_ROOT."/checklists/checklist.php?clid=".$clid.'&pid='.$pid; ?>">
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
							$cnt = $clLoaderManager->uploadCsvList($thesId);
							$statusStr = $clLoaderManager->getErrorMessage();
							if(!$cnt && $statusStr){
								echo '<div style="margin:20px;font-weight:bold;">';
								echo '<div style="font-size:110%;color:red;">'.$statusStr.'</div>';
								echo '<div><a href="checklistloader.php?clid='.$clid.'&pid='.$pid.'">Return to Loader</a> and make sure the input file matches requirements within instructions</div>';
								echo '</div>';
								exit;
							}
							$probCnt = count($clLoaderManager->getProblemTaxa());
							$errorArr = $clLoaderManager->getWarningArr();
							?>
							<li>Upload status...</li>
							<li style="margin-left:10px;">Taxa successfully loaded: <?php echo $cnt; ?></li>
							<li style="margin-left:10px;">Problematic Taxa: <?php echo $probCnt.($probCnt?' (see below)':''); ?></li>
							<li style="margin-left:10px;">General errors: <?php echo count($errorArr); ?></li>
							<li style="margin-left:10px;">Upload Complete! <a href="../checklist.php?clid=<?php echo $clid.'&pid='.$pid; ?>">Proceed to Checklists</a></li>
						</ul>
						<?php
						if($probCnt){
							echo '<fieldset>';
							echo '<legend><b>Problematic Taxa Resolution</b></legend>';
							$clLoaderManager->resolveProblemTaxa();
							echo '</fieldset>';
						}
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
						<fieldset style="padding:15px;width:800px;">
							<legend><b>Checklist Upload Form</b></legend>
							<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
							<div style="font-weight:bold;">
								Checklist File:
								<input id="uploadfile" name="uploadfile" type="file" size="45" />
							</div>
							<div style="margin-top:10px;">
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
							<div style="margin-top:10px;">
								<div>Must be a CSV text file with the first row containing the following columns. Note that Excel spreadsheets can be saved as a CSV file.</div>
								<ul>
									<li>sciname (required)</li>
									<li>family (optional)</li>
									<li>habitat (optional)</li>
									<li>abundance (optional)</li>
									<li>notes (optional)</li>
									<li>internalnotes (optional) - displayed only to editors</li>
									<li>source (optional)</li>

								</ul>
							</div>
							<div style="margin:25px;">
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
		include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>