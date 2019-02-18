<?php
include_once('../../config/symbini.php');
@include_once('Image/Barcode.php');
@include_once('Image/Barcode2.php');
include_once($SERVER_ROOT.'/content/lang/collections/reports/labelmanager.'.$LANG_TAG.'.php');

include_once($SERVER_ROOT.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/reports/labelmanager.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST["collid"];
$tabTarget = array_key_exists('tabtarget',$_REQUEST)?$_REQUEST['tabtarget']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$datasetManager = new OccurrenceLabel();
$datasetManager->setCollid($collid);

$reportsWritable = false;
if(is_writable($SERVER_ROOT.'/temp/report')){
	$reportsWritable = true;
}

$isEditor = 0;
$occArr = array();
$annoArr = array();
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
	$isEditor = 1;
}
if($isEditor){
	$annoArr = $datasetManager->getAnnoQueue();
	if($action == "Filter Specimen Records"){
		$occArr = $datasetManager->queryOccurrences($_POST);
	}
}
?>
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE; ?> Specimen Label Manager</title>
		<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				if(!navigator.cookieEnabled){
					alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
				}

				function split( val ) {
					return val.split( /,\s*/ );
				}
				function extractLast( term ) {
					return split( term ).pop();
				}

				$("#tabs").tabs({
					active: <?php echo (is_numeric($tabTarget)?$tabTarget:'0'); ?>
				});

				$( "#taxa" )
				// don't navigate away from the field on tab when selecting an item
				.bind( "keydown", function( event ) {
					if ( event.keyCode === $.ui.keyCode.TAB &&
							$( this ).data( "autocomplete" ).menu.active ) {
						event.preventDefault();
					}
				})
				.autocomplete({
					source: function( request, response ) {
						$.getJSON( "../rpc/taxalist.php", {
							term: extractLast( request.term )
						}, response );
					},
					search: function() {
						// custom minLength
						var term = extractLast( this.value );
						if ( term.length < 4 ) {
							return false;
						}
					},
					focus: function() {
						// prevent value inserted on focus
						return false;
					},
					select: function( event, ui ) {
						var terms = split( this.value );
						// remove the current input
						terms.pop();
						// add the selected item
						terms.push( ui.item.value );
						this.value = terms.join( ", " );
						return false;
					}
				},{});
			});

			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function selectAllAnno(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("detid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function validateQueryForm(f){
				if(!validateDateFields(f)){
					return false;
				}
				return true;
			}

			function validateDateFields(f){
				var status = true;
				var validformat1 = /^\s*\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
				if(f.date1.value !== "" && !validformat1.test(f.date1.value)) status = false;
				if(f.date2.value !== "" && !validformat1.test(f.date2.value)) status = false;
				if(!status) alert("Date entered must follow the format YYYY-MM-DD");
				return status;
			}

			function validateSelectForm(f){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please select at least one occurrence!");
		      	return false;
			}

			function validateAnnoSelectForm(f){
				var dbElements = document.getElementsByName("detid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please select at least one occurrence!");
		      	return false;
			}

			function openIndPopup(occid){
				openPopup('../individual/index.php?occid=' + occid);
			}

			function openEditorPopup(occid){
				openPopup('../editor/occurrenceeditor.php?occid=' + occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.getElementById('maintable').offsetWidth){
					wWidth = document.getElementById('maintable').offsetWidth*1.05;
				}
				else if(document.body.offsetWidth){
					wWidth = document.body.offsetWidth*0.9;
				}
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				return false;
			}

			function changeFormExport(action,target){
				document.selectform.action = action;
				document.selectform.target = target;
			}

			function changeAnnoFormExport(action,target){
				document.annoselectform.action = action;
				document.annoselectform.target = target;
			}

			function checkPrintOnlyCheck(f){
				if(f.bconly.checked){
					f.speciesauthors.checked = false;
					f.catalognumbers.checked = false;
					f.bc.checked = false;
					f.symbbc.checked = false;
				}
			}

			function checkBarcodeCheck(f){
				if(f.bc.checked || f.symbbc.checked || f.speciesauthors.checked || f.catalognumbers.checked){
					f.bconly.checked = false;
				}
			}

		</script>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_reports_labelmanagerMenu)?$collections_reports_labelmanagerMenu:false);
	include($SERVER_ROOT."/header.php");
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<?php
		if(isset($collections_reports_labelmanagerCrumbs)){
			echo $collections_reports_labelmanagerCrumbs;
		}
		else{
			if(stripos(strtolower($datasetManager->getMetaDataTerm('colltype')), "observation") !== false){
				echo '<a href="../../profile/viewprofile.php?tabindex=1">Personal Management Menu</a> &gt;&gt; ';
			}
			else{
				echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Management Panel</a> &gt;&gt; ';
			}
		}
		?>
		<b>Label/Annotation Printing</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			if(!$reportsWritable){
				?>
				<div style="padding:5px;">
					<span style="color:red;">Please contact the site administrator to make temp/report folder writable in order to export to docx files.</span>
				</div>
				<?php
			}
			$isGeneralObservation = (($datasetManager->getMetaDataTerm('colltype') == 'General Observations')?true:false);
			echo '<h2>'.$datasetManager->getCollName().'</h2>';
			?>
			<div id="tabs" style="margin:0px;">
				<ul>
					<li><a href="#labels"><?php echo $LANG['LABELS'];?></a></li>
					<li><a href="#annotations"><?php echo $LANG['ANNOTATIONS'];?></a></li>
				</ul>

				<div id="labels">
					<form name="datasetqueryform" action="labelmanager.php" method="post" onsubmit="return validateQueryForm(this)">
						<fieldset>
							<legend><b><?php echo $LANG['DEFINE_SPEC'];?></b></legend>
							<div style="margin:3px;">
								<div title="Scientific name as entered in database.">
									<?php echo $LANG['SCI_NAME'];?>
									<input type="text" name="taxa" id="taxa" size="60" value="<?php echo (array_key_exists('taxa',$_REQUEST)?$_REQUEST['taxa']:''); ?>" />
								</div>
							</div>
							<div style="margin:3px;clear:both;">
								<div style="float:left;" title="Full or last name of collector as entered in database.">
									<?php echo $LANG['COLLECTOR'];?>
									<input type="text" name="recordedby" style="width:150px;" value="<?php echo (array_key_exists('recordedby',$_REQUEST)?$_REQUEST['recordedby']:''); ?>" />
								</div>
								<div style="float:left;margin-left:20px;" title="Separate multiple terms by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
									<?php echo $LANG['RECORD_NUMBER'];?>
									<input type="text" name="recordnumber" style="width:150px;" value="<?php echo (array_key_exists('recordnumber',$_REQUEST)?$_REQUEST['recordnumber']:''); ?>" />
								</div>
								<div style="float:left;margin-left:20px;" title="Separate multiple terms by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
									<?php echo $LANG['CAT_NUMBER'];?>
									<input type="text" name="identifier" style="width:150px;" value="<?php echo (array_key_exists('identifier',$_REQUEST)?$_REQUEST['identifier']:''); ?>" />
								</div>
							</div>
							<div style="margin:3px;clear:both;">
								<div style="float:left;">
									<?php echo $LANG['ENTERED_BY'];?>Introducido por:
									<input type="text" name="recordenteredby" value="<?php echo (array_key_exists('recordenteredby',$_REQUEST)?$_REQUEST['recordenteredby']:''); ?>" style="width:100px;" title="login name of data entry person" />
								</div>
								<div style="margin-left:20px;float:left;" title="">
									<?php echo $LANG['DATE_RANGE'];?>Intervalo de fechas:
									<input type="text" name="date1" style="width:100px;" value="<?php echo (array_key_exists('date1',$_REQUEST)?$_REQUEST['date1']:''); ?>" onchange="validateDateFields(this.form)" /> <?php echo $LANG['TO'];?>
									<input type="text" name="date2" style="width:100px;" value="<?php echo (array_key_exists('date2',$_REQUEST)?$_REQUEST['date2']:''); ?>" onchange="validateDateFields(this.form)" />
									<select name="datetarget">
										<option value="dateentered">Date Entered</option>
										<option value="datelastmodified" <?php echo (isset($_POST['datetarget']) && $_POST['datetarget'] == 'datelastmodified'?'SELECTED':''); ?>>Date Modified</option>
										<option value="eventdate"<?php echo (isset($_POST['datetarget']) && $_POST['datetarget'] == 'eventdate'?'SELECTED':''); ?>>Date Collected</option>
									</select>
								</div>
							</div>
							<div style="margin:3px;clear:both;">
								<?php echo $LANG['LABEL_PROJECTS'];?>
								<select name="labelproject" >
									<option value=""></option>
									<option value="">-------------------------</option>
									<?php
									$lProj = '';
									if(array_key_exists('labelproject',$_REQUEST)) $lProj = $_REQUEST['labelproject'];
									$lProjArr = $datasetManager->getLabelProjects();
									foreach($lProjArr as $projStr){
										echo '<option '.($lProj==$projStr?'SELECTED':'').'>'.$projStr.'</option>'."\n";
									}
									?>
								</select>
								<!--
								Dataset Projects:
								<select name="datasetproject" >
									<option value=""></option>
									<option value="">-------------------------</option>
									<?php
									/*
									$datasetProj = '';
									if(array_key_exists('datasetproject',$_REQUEST)) $datasetProj = $_REQUEST['datasetproject'];
									$dProjArr = $datasetManager->getDatasetProjects();
									foreach($dProjArr as $dsid => $dsProjStr){
										echo '<option id="'.$dsid.'" '.($datasetProj==$dsProjStr?'SELECTED':'').'>'.$dsProjStr.'</option>'."\n";
									}
									*/
									?>
								</select>
								-->
								<?php
								echo '<span style="margin-left:15px;"><input name="extendedsearch" type="checkbox" value="1" '.(array_key_exists('extendedsearch', $_POST)?'checked':'').' /></span> ';
								if($isGeneralObservation)
									echo 'Search outside user profile';
								else echo 'Search within all collections';
								?>
							</div>
							<div style="clear:both;">
								<div style="margin-left:20px;float:left;">
									<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
									<input type="submit" name="submitaction" value="Filter Specimen Records" />
								</div>
								<div style="margin-left:20px;float:left;">
									<?php echo $LANG['SPECIMEN_RET'];?>
								</div>
							</div>
						</fieldset>
					</form>
					<div style="clear:both;">
						<?php
						if($action == "Filter Specimen Records"){
							if($occArr){
								?>
								<form name="selectform" id="selectform" action="defaultlabels.php" method="post" onsubmit="return validateSelectForm(this);">
									<div style="margin-top: 15px; margin-left: 15px;">
										<input name="" value="" type="checkbox" onclick="selectAll(this);" />
										Select/Deselect all Specimens
									</div>
									<table class="styledtable" style="font-family:Arial;font-size:12px;">
										<tr>
											<th></th>
											<th>#</th>
											<th>Collector</th>
											<th>Scientific Name</th>
											<th>Locality</th>
										</tr>
										<?php
										$trCnt = 0;
										foreach($occArr as $occId => $recArr){
											$trCnt++;
											?>
											<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
												<td>
													<input type="checkbox" name="occid[]" value="<?php echo $occId; ?>" />
												</td>
												<td>
													<input type="text" name="q-<?php echo $occId; ?>" value="<?php echo $recArr["q"]; ?>" style="width:20px;border:inset;" />
												</td>
												<td>
													<a href="#" onclick="openIndPopup(<?php echo $occId; ?>); return false;">
														<?php echo $recArr["c"]; ?>
													</a>
													<?php
													if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($recArr["collid"],$USER_RIGHTS["CollAdmin"])) || (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($recArr["collid"],$USER_RIGHTS["CollEditor"]))){
														if(!$isGeneralObservation || $recArr['uid'] == $SYMB_UID){
															?>
															<a href="#" onclick="openEditorPopup(<?php echo $occId; ?>); return false;">
																<img src="../../images/edit.png" />
															</a>
															<?php
														}
													}
													?>
												</td>
												<td>
													<?php echo $recArr["s"]; ?>
												</td>
												<td>
													<?php echo $recArr["l"]; ?>
												</td>
											</tr>
											<?php
										}
										?>
									</table>
									<fieldset style="margin-top:15px;">
										<legend><b>Label Printing</b></legend>
										<div style="margin:4px;">
											<b>Heading Prefix:</b>
											<input type="text" name="lhprefix" value="" style="width:450px" /> (e.g. Plants of, Insects of, Vertebrates of)
											<div style="margin:3px 0px 3px 0px;">
												<b>Heading Mid-Section:</b>
												<input type="radio" name="lhmid" value="1" />Country
												<input type="radio" name="lhmid" value="2" checked />State
												<input type="radio" name="lhmid" value="3" />County
												<input type="radio" name="lhmid" value="4" />Family
												<input type="radio" name="lhmid" value="0" />Blank
											</div>
											<b>Heading Suffix:</b>
											<input type="text" name="lhsuffix" value="" style="width:450px" /><br/>
										</div>
										<div style="margin:4px;">
											<b>Label Footer:</b>
											<input type="text" name="lfooter" value="" style="width:450px" />
										</div>
										<div style="margin:4px;">
											<input type="checkbox" name="speciesauthors" value="1" onclick="checkBarcodeCheck(this.form);" />
											<b>Print species authors for infraspecific taxa</b>
										</div>
										<div style="margin:4px;">
											<input type="checkbox" name="catalognumbers" value="1" onclick="checkBarcodeCheck(this.form);" />
											<b>Print Catalog Numbers</b>
										</div>
										<?php
										if(class_exists('Image_Barcode2') || class_exists('Image_Barcode')){
											?>
											<div style="margin:4px;">
												<input type="checkbox" name="bc" value="1" onclick="checkBarcodeCheck(this.form);" />
												<b>Include barcode of Catalog Number</b>
											</div>
											<div style="margin:4px;">
												<input type="checkbox" name="symbbc" value="1" onclick="checkBarcodeCheck(this.form);" />
												<b>Include barcode of Symbiota Identifier</b>
											</div>
											<div style="margin:4px;">
												<input type="checkbox" name="bconly" value="1" onclick="checkPrintOnlyCheck(this.form);" />
												<b>Print only Barcode</b>
											</div>
											<?php
										}
										?>
										<fieldset style="float:left;margin:10px;width:150px;">
											<legend><b>Rows Per Page</b></legend>
											<input type="radio" name="rpp" value="1" /> 1<br/>
											<input type="radio" name="rpp" value="2" checked /> 2<br/>
											<input type="radio" name="rpp" value="3" /> 3<br/>
										</fieldset>
										<div style="float:left;margin: 15px 50px;">
											<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
											<input type="submit" name="submitaction" onclick="changeFormExport('defaultlabels.php','_blank');" value="Print in Browser" />
											<br/><br/>
											<input type="submit" name="submitaction" onclick="changeFormExport('defaultlabels.php','_self');" value="Export to CSV" />
											<?php
											if($reportsWritable){
												?>
												<br/><br/>
												<input type="submit" name="submitaction" onclick="changeFormExport('defaultlabelsexport.php','_self');" value="Export to DOCX" />
												<?php
											}
											?>
										</div>
									</fieldset>
								</form>
								<?php
							}
							else{
								?>
								<div style="font-weight:bold;margin:20px;font-weight:150%;">
									Query returned no data!
								</div>
								<?php
							}
						}
						?>
					</div>
				</div>
				<div id="annotations">
					<div>
						<?php
						if($annoArr){
							?>
							<form name="annoselectform" id="annoselectform" action="defaultannotations.php" method="post" onsubmit="return validateAnnoSelectForm(this);">
								<div style="margin-top: 15px; margin-left: 15px;">
									<input name="" value="" type="checkbox" onclick="selectAllAnno(this);" />
									Select/Deselect all Specimens
								</div>
								<table class="styledtable" style="font-family:Arial;font-size:12px;">
									<tr>
										<th style="width:25px;text-align:center;"></th>
										<th style="width:25px;text-align:center;">#</th>
										<th style="width:125px;text-align:center;">Collector</th>
										<th style="width:300px;text-align:center;">Scientific Name</th>
										<th style="width:400px;text-align:center;">Determination</th>
									</tr>
									<?php
									$trCnt = 0;
									foreach($annoArr as $detId => $recArr){
										$trCnt++;
										?>
										<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
											<td>
												<input type="checkbox" name="detid[]" value="<?php echo $detId; ?>" />
											</td>
											<td>
												<input type="text" name="q-<?php echo $detId; ?>" value="1" style="width:20px;border:inset;" />
											</td>
											<td>
												<a href="#" onclick="openIndPopup(<?php echo $recArr['occid']; ?>); return false;">
													<?php echo $recArr['collector']; ?>
												</a>
												<a href="#" onclick="openEditorPopup(<?php echo $recArr['occid']; ?>); return false;">
													<img src="../../images/edit.png" />
												</a>
											</td>
											<td>
												<?php echo $recArr['sciname']; ?>
											</td>
											<td>
												<?php echo $recArr['determination']; ?>
											</td>
										</tr>
										<?php
									}
									?>
								</table>
								<fieldset style="margin-top:15px;">
									<legend><b>Annotation Printing</b></legend>
									<div style="float:left;">
										<div style="margin:4px;">
											<b>Header:</b>
											<input type="text" name="lheading" value="<?php echo $datasetManager->getAnnoCollName(); ?>" style="width:450px" />
										</div>
										<div style="margin:4px;">
											<b>Footer:</b>
											<input type="text" name="lfooter" value="" style="width:450px" />
										</div>
										<div style="margin:4px;">
											<input type="checkbox" name="speciesauthors" value="1" onclick="" />
											<b>Print species authors for infraspecific taxa</b>
										</div>
										<div style="margin:4px;">
											<input type="checkbox" name="clearqueue" value="1" onclick="" />
											<b>Remove selected annotations from queue</b>
										</div>
									</div>
									<div style="float:right;">
										<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
										<input type="submit" name="submitaction" onclick="changeAnnoFormExport('defaultannotations.php','_blank');" value="Print in Browser" />
										<?php
										if($reportsWritable){
											?>
											<br/><br/>
											<input type="submit" name="submitaction" onclick="changeAnnoFormExport('defaultannotationsexport.php','_self');" value="Export to DOCX" />
											<?php
										}
										?>
									</div>
								</fieldset>
							</form>
							<?php
						}
						else{
							?>
							<div style="font-weight:bold;margin:20px;font-weight:150%;">
								<?php echo $LANG['THERE_NOT'];?>There are no annotations queued to be printed.
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				You do not have permissions to print labels for this collection.
				Please contact the site administrator to obtain the necessary permissions.
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
