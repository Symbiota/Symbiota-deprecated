<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecDatasetManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = $_REQUEST["collid"];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$datasetManager = new SpecDatasetManager();
$datasetManager->setCollId($collId);

$isEditor = 0;
$occArr = array();
if($symbUid){
	$datasetManager->setSymbUid($symbUid);
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
		$datasetManager->setIsAdmin(1);
		$isEditor = 1;
	}
	if(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
		$isEditor = 1;
	}
	if($isEditor){
		if($action == "Filter Specimen Records"){
			$occArr = $datasetManager->queryOccurrences();
		}
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> Specimen Dataset Definer</title>
	    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
		<script language="javascript" type="text/javascript">
			function init(){
				if(navigator.appName == "Microsoft Internet Explorer"){
					alert("You are using Internet Explorer as your web browser. We recommend that you use Firefox or Google Chrome since these browsers are generally more reliable when using these tools.");
				}
				else{
					if(/Firefox[\/\s](\d+\.\d+)/.test(navigator.userAgent)){
						var ffversion=new Number(RegExp.$1);
						if(ffversion < 7 ) alert("You are using an older version of Firefox. For best results, we recommend that you update your browser.");
					}
				}
			}
		
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

			function validateQueryForm(f){
				var dateStr = f.datelastmodified.value;
				if(dateStr == "") return true;
				try{
					var validformat1 = /^\s*\d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
					var validformat2 = /^\s*\d{4}-\d{2}-\d{2} - \d{4}-\d{2}-\d{2}\s*$/ //Format: yyyy-mm-dd
					if(!validformat1.test(dateStr) && !validformat2.test(dateStr)){
						alert("Date entered must follow YYYY-MM-DD for a single date and YYYY-MM-DD - YYYY-MM-DD as a range");
						return false;
					}
				}
				catch(ex){
					
				}
				return true;
			}

			function validateSelectForm(f){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please select at least one specimen!");
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

		</script>
	</head>
	<body onload="init()">
	<?php
	$displayLeftMenu = (isset($collections_datasets_indexMenu)?$collections_datasets_indexMenu:false);
	include($serverRoot."/header.php");
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt; 
		<?php
		if(isset($collections_datasets_indexCrumbs)){
			echo $collections_datasets_indexCrumbs;
		}
		else{
			echo '<a href="../misc/collprofiles.php?collid='.$collId.'&emode=1">Collection Management Panel</a> &gt;&gt; ';
		}
		?>
		<b>Label Printing</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($symbUid){
			if($isEditor){
				echo '<h2>'.$datasetManager->getCollName().'</h2>';
				?>
				<form name="datasetqueryform" action="index.php" method="post" onsubmit="return validateQueryForm(this)">
					<fieldset>
						<legend><b>Define Specimen Recordset</b></legend>
						<div style="margin:3px;">
							<span title="Full name of collector as entered in database. To search just on last name, place the wildcard character (%) before name (%Gentry).">
								Collector: 
								<input type="text" name="recordedby" style="width:100px;" value="<?php echo (array_key_exists('recordedby',$_REQUEST)?$_REQUEST['recordedby']:''); ?>" />
							</span>
							<span style="margin-left:20px;" title="Enter a range delimited by ' - ' (space before and after dash required), e.g.: 3700 - 3750">
								Number(s): 
								<input type="text" name="recordnumber" style="width:100px;" value="<?php echo (array_key_exists('recordnumber',$_REQUEST)?$_REQUEST['recordnumber']:''); ?>" />
							</span>
							<span style="margin-left:20px;" title="Separate multiples by comma and ranges by ' - ' (space before and after dash required), e.g.: 3542,3602,3700 - 3750">
								Identifier: 
								<input type="text" name="identifier" style="width:100px;" value="<?php echo (array_key_exists('identifier',$_REQUEST)?$_REQUEST['identifier']:''); ?>" />
							</span>
						</div>
						<div style="margin:3px;">
							<span>
								Entered by: 
								<input type="text" name="recordenteredby" value="<?php echo (array_key_exists('recordenteredby',$_REQUEST)?$_REQUEST['recordenteredby']:''); ?>" style="width:100px;" title="login name of data entry person" />
							</span>
							<span style="margin-left:20px;" title="Enter a range delimited by ' - ' (space before and after dash required), e.g.: 3700 - 3750">
								Date Modified: 
								<input type="text" name="datelastmodified" style="width:100px;" value="<?php echo (array_key_exists('datelastmodified',$_REQUEST)?$_REQUEST['datelastmodified']:''); ?>" />
							</span>
						</div>
						<div style="margin:3px;">
							Label Projects: 
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
						</div>
						<div>
							<span style="margin-left:20px;">
								<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
								<input type="submit" name="submitaction" value="Filter Specimen Records" />
							</span>
							<span style="margin-left:20px;">
								* Specimen return is limited to 100 records
							</span>
							<!-- 
							<span style="margin-left:150px;">
								<a href="#" onclick="toggle('');return false;">
									Hints
								</a>
							</span>
							-->
						</div>
					</fieldset>
				</form>
				<div>
				<?php 
				if($action == "Filter Specimen Records"){
					if($occArr){
						?>
						<form name="selectform" action="defaultlabels.php" method="post" onsubmit="return validateSelectForm(this)" target="_blank">
				        	<div style="margin-top: 15px; margin-left: 15px;">
				         		<input name="" value="" type="checkbox" onclick="selectAll(this);" />
				         		Select/Deselect all Specimens
				        	</div>
							<table class="styledtable">
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
											<a href="#" onclick="openEditorPopup(<?php echo $occId; ?>); return false;">
												<img src="../../images/edit.png" />
											</a>
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
									<input type="text" name="lhprefix" value="Plants of " style="width:450px" />
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
									<input type="checkbox" name="fw" value="1" />
									<b>Floating label width (allows one to adjust overall font in print preview)</b> 
								</div>
								<div style="margin:4px;">
									<input type="checkbox" name="bc" value="1" />
									<b>Include barcode of Catalog Number</b> 
								</div>
								<div style="margin:4px;">
									<input type="checkbox" name="bconly" value="1" />
									<b>Print only Barcode</b> 
								</div>
								<!-- 
								<div style="margin:4px;">
									<input type="checkbox" name="symbbc" value="1" />
									<b>Include barcode of Symbiota Identifier</b> 
								</div>
								 -->
								<fieldset style="float:left;margin:10px;width:150px;">
									<legend><b>Label Rows Per Page</b></legend>
									<input type="radio" name="rpp" value="1" /> 1<br/>
									<input type="radio" name="rpp" value="2" /> 2<br/>
									<input type="radio" name="rpp" value="3" checked /> 3<br/>
									<input type="radio" name="rpp" value="0" /> Auto (unreliable)
								</fieldset>
								<div style="float:left;margin: 15px 50px;">
									<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
									<input type="submit" name="submitaction" value="Print in Browser" />
									<br/><br/> 
									<input type="submit" name="submitaction" value="Export to CSV" />
									<br/><br/>
									<input type="submit" name="submitaction" value="Export to DOC" />
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
		}
		else{ 
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				Please 
				<a href="../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/datasets/index.php?collid=<?php echo $collId; ?>">
					login
				</a> 
				to access the label printing functions.
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