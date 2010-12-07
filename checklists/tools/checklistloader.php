<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistLoaderManager.php');
header("Content-Type: text/html; charset=".$charset);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:""; 
$hasHeader = array_key_exists("hasheader",$_REQUEST)?$_REQUEST["hasheader"]:"";
$thesId = array_key_exists("thes",$_REQUEST)?$_REQUEST["thes"]:0;
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 

$clLoaderManager = new ChecklistLoaderManager();
$clLoaderManager->setClid($clid);
 
$editable = false;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$editable = true;
}
 
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Species Checklist Loader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
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
	
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($checklists_checklistloaderMenu)?$checklists_checklistloaderMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($checklists_checklistloaderCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_checklistloaderCrumbs;
		echo " <b>".$defaultTitle." Checklists Loader</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>
			<a href="<?php echo $clientRoot."/checklists/checklist.php?cl=".$clid; ?>">
				<?php echo $clLoaderManager->getClName(); ?>
			</a>
		</h1>
		<div style="margin:10px;">
			<b>Authors:</b> <?php echo $clLoaderManager->getClAuthors(); ?>
		</div>
		<?php 
			if($editable){ 
				if($action == "Upload Checklist"){
					echo "<div style='margin:10px;'>";
					$clLoaderManager->uploadCsvList($hasHeader,$thesId);
				}
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
			elseif(!$symbUid){ 
				echo "<h2>You must login to the system before you can upload a species list</h2>";	
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
