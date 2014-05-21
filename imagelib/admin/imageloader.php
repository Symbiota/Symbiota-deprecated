<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ImageShared.php');

$action = array_key_exists("formsubmit",$_POST)?$_POST["formsubmit"]:"";
$ulFileName = array_key_exists("ulfilename",$_POST)?$_POST["ulfilename"]:"";

$isEditor = false;
if($isAdmin){
	$isEditor = true;
}

$uploadManager = new ImageShared();

$fieldMap = Array();
if($isEditor){
	if($ulFileName){
		$uploadManager->setFileName($ulFileName);
	}
	
	if(array_key_exists("sf",$_REQUEST)){
		//Grab field mapping, if mapping form was submitted
 		$targetFields = $_REQUEST["tf"];
 		$sourceFields = $_REQUEST["sf"];
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$sourceFields[$x]] = $targetFields[$x];
		}
	}
}
?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Image Loader</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		
	</script>
</head>
<body>
<?php
$displayLeftMenu = true;
include($serverRoot.'/header.php');

?>
<div class="navpath">
	<b><a href="../../index.php">Homepage</a></b> &gt;&gt; 
	<b>Image Loader</b>
</div>

<h1>Image Loader</h1>
<div  id="innertext">
	<div style="margin-bottom:30px;">
		
	</div> 
	<div>
		<form name="uploadform" action="imageloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
			<fieldset style="width:90%;">
				<legend style="font-weight:bold;font-size:120%;">Image Upload Form</legend>
				<div style="margin:10px;">
					Flat structured, CSV (comma delimited) text files can be uploaded here. 
					Scientific name is the only required field below genus rank. 
					However, family, author, and rankid (as defined in taxonunits table) are always advised. 
					For upper level taxa, parents and rankids need to be included in order to build the taxonomic hierarchy.
					Large data files can be compressed as a ZIP file before import. 
					If the file upload step fails without displaying an error message, it is possible that the 
					file size excedes the file upload limits set within your PHP installation (see your php configuraton file).
				</div>
				<input type="hidden" name="ulfilename" value="<?php echo $loaderManager->getFileName();?>" />
				<?php 
				if(!$loaderManager->getFileName()){ 
					?>
					<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
					<div>
						<div class="overrideopt">
							<b>Upload File:</b>
							<div style="margin:10px;">
								<input id="genuploadfile" name="uploadfile" type="file" size="40" />
							</div>
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Analyze Input File" />
						</div>
					</div>
					<?php 
				}
				else{ 
					?>
					<div id="mdiv" style="margin:15px;">
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
							$tArr = $loaderManager->getTargetArr();
							asort($tArr);
							foreach($sArr as $sField){
								?>
								<tr>
									<td style='padding:2px;'>
										<?php echo $sField; ?>
										<input type="hidden" name="sf[]" value="<?php echo $sField; ?>" />
									</td>
									<td>
										<select name="tf[]" style="background:<?php echo (array_key_exists($sField,$fieldMap)?"":"yellow");?>">
											<option value="">Field Unmapped</option>
											<option value="">-------------------------</option>
											<?php 
											$mappedTarget = (array_key_exists($sField,$fieldMap)?$fieldMap[$sField]:"");
											$selStr = "";
											if($mappedTarget=="unmapped") $selStr = "SELECTED";
											echo "<option value='unmapped' ".$selStr.">Leave Field Unmapped</option>";
											if($selStr){
												$selStr = 0;
											}
											foreach($tArr as $k => $tField){
												if($selStr !== 0 && $tField == "scinameinput" && (strtolower($sField == "sciname") || strtolower($sField) == "scientific name")){
													$selStr = "SELECTED";
												}
												elseif($selStr !== 0 && $mappedTarget && $mappedTarget == $tField){
													$selStr = "SELECTED";
												}
												elseif($selStr !== 0 && $tField==$sField && $tField != "sciname"){
													$selStr = "SELECTED";
												}
												echo '<option value="'.$k.'" '.($selStr?$selStr:'').'>'.$tField."</option>\n";
												if($selStr){
													$selStr = 0;
												}
											}
											?>
										</select>
									</td>
								</tr>
								<?php 
							}
							?>
						</table>
						<div>
							* Fields in yellow have not yet been verified
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Verify Mapping" />
							<input type="submit" name="action" value="Upload Images" />
						</div>
					</div>
				<?php } ?>
			</fieldset>
		</form>
	</div>
</div>
<?php  
include($serverRoot.'/footer.php');
?>

</body>
</html>
