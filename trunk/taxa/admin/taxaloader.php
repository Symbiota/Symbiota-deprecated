<?php
/*
* Author: E.E. Gilbert
* Sept 2010
*/

//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxaLoaderManager.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";

$editable = false;
if($isAdmin || array_key_exists("Taxonomy",$userRights)){
	$editable = true;
}
	 
$loaderManager;
$status = "";
if($editable){
	if($action == "Upload ITIS File"){
		$loaderManager = new ItisTaxaLoaderManager();
		$status = $loaderManager->uploadFile();
	}
	elseif($action == "Analyze Upload File"){
		$loaderManager = new TaxaLoaderManager();
		$loaderManager->setUploadFile();
	}
	elseif(array_key_exists("sf",$_REQUEST)){
		if($ulFileName) $loaderManager->setUploadFile($ulFileName);
		//Grab field mapping, if mapping form was submitted
 		$targetFields = $_REQUEST["tf"];
 		$sourceFields = $_REQUEST["sf"];
 		$fieldMap = Array();
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$targetFields[$x]] = $sourceFields[$x];
		}
 		$duManager->setFieldMap($fieldMap);

		if($action == "Check Mapping"){
		
		}
		elseif($action == "Upload Taxa"){
			
		}
		
	}
	
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle; ?> Taxa Loader</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script type="text/javascript">

	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_itistaxaloaderMenu)?$taxa_admin_itistaxaloaderMenu:false);
include($serverRoot.'/header.php');
if(isset($taxa_admin_itistaxaloaderCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_itistaxaloaderCrumbs;
	echo " <b>ITIS Taxa Loader</b>"; 
	echo "</div>";
}

if($editable){
?>
<div style="margin:30px;">
	<?php 
	if($status){
		echo '<div><ul>';
		echo $status;
		echo '</ul></div><hr/>';
	}
	?>
	<div>
		<form name="itisuploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return checkItisUploadForm()">
			<fieldset style="width:450px;">
				<legend style="font-weight:bold;font-size:120%;">Upload File</legend>
				<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
				<div>
					<b>Upload File:</b>
					<div style="margin:10px;">
						<input id="uploadfile" name="uploadfile" type="file" size="40" />
					</div>
				</div>
				<div style="margin:10px;">
					<input type="submit" name="action" value="Upload ITIS File" />
				</div>
			</fieldset>
		</form>
	</div>
	<div>
		<form name="uploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return checkUploadForm()">
			<fieldset style="width:450px;">
				<legend style="font-weight:bold;font-size:120%;">Taxa Upload Form</legend>
				<input type="hidden" name="ulfilename" value="<?php echo $ulFileName;?>" />
				<?php if(!$ulFileName){ ?>
					<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
					<div>
						<b>Upload File:</b>
						<div style="margin:10px;">
							<input id="uploadfile" name="uploadfile" type="file" size="40" />
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Analyze Upload File" />
						</div>
					</div>
				<?php }else{ ?>
					<div id="mdiv">
						<table border="1" cellpadding="2" style="border:1px solid black">
							<tr>
								<th>
									Source Field
								</th>
								<th>
									Target Field
								</th>
							</tr>
							<?php
							$sArr = $loaderManager->getSourceArr();
							 

		$sourceSymbArr = Array();
		foreach($this->fieldMap as $symbField => $fArr){
			$sourceSymbArr[$fArr["field"]] = $symbField;
		}

		//Output table rows for source data
		sort($this->symbFields);
		$dbpk = (array_key_exists("dbpk",$this->fieldMap)?$this->fieldMap["dbpk"]["field"]:"");
		$autoMapArr = Array();
		foreach($this->sourceArr as $fieldName){
			if($dbpk != $fieldName){
				$isAutoMapped = false;
				if($autoMap && in_array($fieldName,$this->symbFields)){
					$isAutoMapped = true;
					$autoMapArr[] = $fieldName;
				}
				echo "<tr>\n";
				echo "<td style='padding:2px;'>";
				echo $fieldName;
				echo "<input type='hidden' name='sf[]' value='".$fieldName."' />";
				echo "</td>\n";
				echo "<td>\n";
				echo "<select name='tf[]' style='background:".(!array_key_exists($fieldName,$sourceSymbArr)&&!$isAutoMapped?"yellow":"")."'>";
				echo "<option value=''>Select Target Field</option>\n";
				echo "<option value=''>Leave Field Unmapped</option>\n";
				echo "<option value=''>-------------------------</option>\n";
				if($isAutoMapped){
					//Source Field = Symbiota Field
					foreach($this->symbFields as $sField){
						if($sField != "dbpk"){
							echo "<option ".($fieldName==$sField?"SELECTED":"").">".$sField."</option>\n";
						}
					}
				}
				elseif(array_key_exists($fieldName,$sourceSymbArr)){
					//Source Field is mapped to Symbiota Field
					foreach($this->symbFields as $sField){
						if($sField != "dbpk"){
							echo "<option ".($sourceSymbArr[$fieldName]==$sField?"SELECTED":"").">".$sField."</option>\n";
						}
					}
				}
				else{
					foreach($this->symbFields as $sField){
						if($sField != "dbpk"){
							echo "<option>".$sField."</option>\n";
						}
					}
				}
				echo "</select></td>\n";
				echo "</tr>\n";
			}
		}
							

							?>
						</table>
						<div>
							* Mappings that are not yet saved are displayed in Yellow
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Check Mapping" />
						</div>
					</div>
				<?php } ?>
				<?php if((($uploadType == $DIRECTUPLOAD || $uploadType == $FILEUPLOAD) && $dbpk) || ($uploadType == $DIGIRUPLOAD) 
					|| ($uploadType == $STOREDPROCEDURE) || ($uploadType == $SCRIPTUPLOAD)){ ?>
					<div id="uldiv">
						<div style="margin:10px;">
							<input type="submit" name="action" value="Start Upload" />
						</div>
					</div>
				<?php } ?>
			</fieldset>
		</form>
	
	</div>
</div>
<?php  
}
else{
	?>
	<div style='font-weight:bold;margin:30px;'>
		You must login and have the correct permissions to upload taxonomic data.<br />
		Please 
		<a href="<?php echo $clientRoot; ?>/profile/index.php?refurl=<?php echo $clientRoot; ?>/taxa/admin/itistaxaloader.php">
			login
		</a>!
	</div>
	<?php 
}


include($serverRoot.'/footer.php');
?>

</body>
</html>
