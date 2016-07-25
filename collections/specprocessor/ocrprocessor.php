<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprid = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;
$procStatus = array_key_exists('procstatus',$_REQUEST)?$_REQUEST['procstatus']:'unprocessed';

$procManager = new SpecProcessorManager();
$procManager->setCollId($collid);
$procManager->setProjVariables('OCR Harvest');
?>
<script>
	$(function() {
		var dialogArr = new Array("speckeypattern","sourcepath","ocrfile","ocrsource");
		var dialogStr = "";
		for(i=0;i<dialogArr.length;i++){
			dialogStr = dialogArr[i]+"info";
			$( "#"+dialogStr+"dialog" ).dialog({
				autoOpen: false,
				modal: true,
				position: { my: "left top", at: "right bottom", of: "#"+dialogStr }
			});
	
			$( "#"+dialogStr ).click(function() {
				$( "#"+this.id+"dialog" ).dialog( "open" );
			});
		}
	
	});
	
	function validateStatQueryForm(f){
		if(f.pscrit.value == ""){
			alert("Please select a processing status");
			return false;
		}
		return true;
	}

	function validateOcrTessForm(f){
		if(f.procstatus.value == ""){
			alert("Please select a processing status");
			return false;
		}
		return true;
	}
	
	function validateOcrUploadForm(f){
		if(f.speckeypattern.value == ""){
			alert("Please enter a pattern matching string for extracting the catalog number");
			return false;
		}

		if(f.sourcepath.value == "" && f.ocrfile.value == ""){
			alert("Please select an OCR source");
			return false;
		}
		var fileName = f.ocrfile.value;
		if(fileName != ""){
			var ext = fileName.split('.').pop();
			if(ext != 'zip' && ext != 'ZIP'){
				alert("Upload file must be a ZIP file with a .zip extension");
				return false;
			}
		}
		return true;
	}
</script>
<div style="margin:15px;">
	<?php 
	$cntTotal = $procManager->getSpecWithImage();
	$cntUnproc = $procManager->getSpecWithImage($procStatus);
	$cntUnprocNoOcr = $procManager->getSpecNoOcr($procStatus);
	if($procStatus == 'null') $procStatus = 'No Status';
	?>
	<fieldset style="padding:20px;">
		<legend><b>Specimen Image Statistics</b></legend>
		
		<div><b>Total specimens with images:</b> <?php echo $cntTotal; ?></div> 
		<div><b>&quot;<?php echo $procStatus; ?>&quot; specimens with images:</b> <?php echo $cntUnproc; ?></div> 
		<div style="margin-left:15px;">with OCR: <?php echo ($cntUnproc-$cntUnprocNoOcr); ?></div>
		<div style="margin-left:15px;">without OCR: <?php echo $cntUnprocNoOcr; ?> </div>
		
		<div style="margin:15px">
			<b>Custom Query: </b><br/>
			<form name="statqueryform" action="index.php" method="post" onsubmit="return validateStatQueryForm(this)">
				<select name="procstatus">
					<option value="">Select Processing Status</option>
					<option value="">-----------------------------------</option>
					<option value="null">No Status</option>
					<?php 
					$psList = $procManager->getProcessingStatusList();
					foreach($psList as $psVal){
						echo '<option value="'.$psVal.'">'.$psVal.'</option>';
					}
					?>
				</select>
				<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
				<input name="tabindex" type="hidden" value="3" />
				<input name="submitaction" type="submit" value="Reset Statistics" />
			</form>
		</div>
	</fieldset>

	<fieldset style="padding:20px;margin-top:20px;">
		<legend><b>Batch OCR Images using the Tesseract OCR Engine</b></legend>
		<?php
		if(isset($tesseractPath) && $tesseractPath){ 
			?>
			<form name="batchTessform" action="processor.php" method="post" onsubmit="return validateBatchTessForm(this)">
				<div style="padding:3px;">
					<b>Processing Status:</b> 
					<select name="procstatus">
						<option value="unprocessed">unprocessed</option>
						<option value="">-----------------------------------</option>
						<option value="null">No Status</option>
						<?php 
						$psList = $procManager->getProcessingStatusList();
						foreach($psList as $psVal){
							if($psVal != 'unprocessed'){
								echo '<option value="'.$psVal.'">'.$psVal.'</option>';
							}
						}
						?>
					</select><br/>
				</div>
				<div style="padding:3px;">
					<b>Number of records to process:</b> 
					<input name="batchlimit" type="text" value="100" style="width:60px" />
				</div>
				<div style="padding:15px;">
					<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
					<input name="tabindex" type="hidden" value="3" />
					<input name="submitaction" type="submit" value="Run Batch OCR" />
				</div>
				<div style="margin:15px">
					Note: This feature is dependent on the proper installation of the Tesseract OCR Engine on the hosting server
				</div>
			</form>
			<?php
		}
		else{
			echo '<div style="margin:25px"><b>';
			echo 'The Tesseract OCR engine does not appear to be installed or the tesseractPath variable is not set within the Symbiota configuration file. ';
			echo 'Contact your system administrator to resolve these issues. ';
			echo '</b></div>'; 
		} 
		?>
	</fieldset>

	<fieldset style="padding:20px;margin-top:20px;">
		<legend><b>OCR Batch Processing</b></legend>
		<form name="ocruploadform" action="processor.php" method="post" enctype="multipart/form-data" onsubmit="return validateOcrUploadForm(this);">
			<div style="float:right;margin:10px;" onclick="toggle('editdiv');toggle('imgprocessdiv')" title="Close Editor">
				<img src="../../images/edit.png" style="border:0px" />
			</div>
			<div style="margin:15px">
				This inteface will process and load OCR text files. 
				ABBYY FineReader (Corporate Edition) includes the ABBYY HotFolder tool that can batch process multiple specimen label images
				to produce separate text files (.txt) containing label text. 
				This tool will also upload OCR text obtained by other processes.   
			</div>
			<div style="margin:15px">
				<b>Requirements:</b>
				<ul>
					<li>OCR files must be in a text format with a .txt extension. When using ABBYY Hotfolders, use the &quot;Create a separate document for each file&quot; and &quot;Save as: Text (*.txt)&quot; settings.</li>
					<li>Files must be named using the Catalog Number</li>
					<li>Since OCR text is linked directly to image, source image must have been previously loaded into database</li>
					<li>If there are more than one image linked to a specimen, the full file name will be used to identify which image to link the OCR</li>
				</ul> 
			</div>
			<div style="margin:15px">
				<table style="width:100%;">
					<tr>
						<td>
							<b>Regular Expression:</b>
						</td>
						<td>
							<input name="speckeypattern" type="text" style="width:300px;" value="<?php echo $procManager->getSpecKeyPattern(); ?>" />
							<a id="speckeypatterninfo" href="#" onclick="return false" title="More Information">
								<img src="../../images/info.png" style="width:15px;" />
							</a>
							<div id="speckeypatterninfodialog">
								Regular expression (PHP version) needed to extract the unique identifier from source text.
								For example, regular expression /^(WIS-L-\d{7})\D*/ will extract catalog number WIS-L-0001234 
								from image file named WIS-L-0001234_a.jpg. For more information on creating regular expressions,
								Google &quot;Regular Expression PHP Tutorial&quot;. It is recommended to have the portal manager
								help with the initial setup of batch processing.  
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<b>OCR Text Files:</b> 
						</td>
						<td>
							<div style="float:right;"><a href="#" onclick="toggle('pathElem');return false;" title="toggle option to enter full path">full path option</a></div>
							<div class="pathElem">
								<input name="ocrfile" type="file" size="50" onchange="this.form.sourcepath.value = ''" />
								<input name="MAX_FILE_SIZE" type="hidden" value="10000000" />
								<a id="ocrfileinfo" href="#" onclick="return false" title="More Information">
									<img src="../../images/info.png" style="width:15px;" />
								</a>
								<div id="ocrfileinfodialog">
									Browse and select zip file that contains the multiple OCR text files.
								</div>
							</div>
							<div class="pathElem" style="display:none;"> 
								<input name="sourcepath" type="text" style="width:350px;" value="<?php echo $procManager->getSourcePath(); ?>" />
								<a id="sourcepathinfo" href="#" onclick="return false" title="More Information">
									<img src="../../images/info.png" style="width:15px;" />
								</a>
								<div id="sourcepathinfodialog">
									File path or URL to folder containing the OCR text files.
									If a URL (e.g. http://) is supplied, the web server needs to be configured to list 
									all files within the directory, or the html output needs to list all images in anchor tags.
									Scripts will attempt to crawl through all child directories.
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<b>OCR Source:</b> 
						</td>
						<td> 
							<input name="ocrsource" type="text" value="" />
							<a id="ocrsourceinfo" href="#" onclick="return false" title="More Information">
								<img src="../../images/info.png" style="width:15px;" />
							</a>
							<div id="ocrsourceinfodialog">
								Short string describing OCR Source (e.g. ABBYY, Tesseract, etc). This value is placed in source field with current date appended.
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input name="title" type="hidden" value="OCR Harvest" />
							<input name="spprid" type="hidden" value="<?php echo $spprid; ?>" />
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" /> 
							<input name="tabindex" type="hidden" value="3" />
							<div style="margin:25px">
								<input name="submitaction" type="submit" value="Load OCR Files" />
							</div>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</fieldset>
</div>