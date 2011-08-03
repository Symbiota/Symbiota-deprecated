<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLabelManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = $_REQUEST["collid"];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$labelManager = new SpecLabelManager();
$labelManager->setCollId($collId);

$isEditor = 0;
$occArr = array();
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
	if($isEditor){
		if($action == "Filter Specimen Records"){
			$occArr = $labelManager->queryOccurrences();
		}
	}
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> Specimen Label Maker</title>
	    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
		<script language="javascript" type="text/javascript">
		
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

			function validateLabelQueryForm(f){
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

			function validateLabelSelectForm(f){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please select at least one specimen for printing!");
		      	return false;
			}

		</script>
	</head>
	<body>
	
	<?php
	$displayLeftMenu = (isset($collections_labels_indexMenu)?$collections_labels_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($collections_labels_indexCrumbs)){
		if($collections_labels_indexCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt; ";
			echo $collections_labels_indexCrumbs;
			echo " <b>Label Maker</b>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../../index.php'>Home</a> &gt; ";
		echo "<b>Label Maker</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Label Generator</h1>
		<form name="labelqueryform" action="index.php" method="post" onsubmit="return validatelabelQueryForm(this)">
			<fieldset>
				<legend>Define Specimen recordset</legend>
				<div style="margin:3px;">
					<span>
						Label Project: 
						<input type="text" name="labelproject" value="<?php echo (array_key_exists('',$_POST)?$_POST["labelproject"]:''); ?>" />
					</span>
					<span style="margin-left:20px;">
						Entered by: 
						<input type="text" name="recordenteredby" value="<?php echo (array_key_exists('',$_POST)?$_POST["recordenteredby"]:''); ?>" />
					</span>
					<span style="margin-left:20px;" title="Enter a range separated by ' - ' (space before and after dash requiered), e.g.: 3700 - 3750">
						Date Modified: 
						<input type="text" name="datelastmodified" value="<?php echo (array_key_exists('',$_POST)?$_POST["datelastmodified"]:''); ?>" />
					</span>
				</div>
				<div style="margin:3px;">
					<span>
						Collector: 
						<input type="text" name="recordedby" value="<?php echo (array_key_exists('',$_POST)?$_POST["recordedby"]:''); ?>" />
					</span>
					<span style="margin-left:20px;" title="Enter a range separated by ' - ' (space before and after dash requiered), e.g.: 3700 - 3750">
						Number(s): 
						<input type="text" name="recordnumber" value="<?php echo (array_key_exists('',$_POST)?$_POST["recordnumber"]:''); ?>" />
					</span>
					<span style="margin-left:20px;" title="Separate multiples by comma and ranges by ' - ' (space before and after dash requiered), e.g.: 3542,3602,3700 - 3750">
						Identifier: 
						<input type="text" name="identifier" value="<?php echo (array_key_exists('',$_POST)?$_POST["identifier"]:''); ?>" />
					</span>
				</div>
				<div>
					<span style="margin-left:20px;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input type="submit" name="submitaction" value="Filter Specimen Records" />
					</span>
				</div>
			</fieldset>
		</form>
		<div>
		<?php 
		if($occArr){
			?>
			<form name="labelselectform" action="defaultlabels.php" method="post" onsubmit="return validateLabelSelectForm(this)">
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
					foreach($occArr as $occId => $recArr){
						?>
						<tr>
							<td>
								<input type="checkbox" name="occid[]" value="<?php echo $occId; ?>" />
							</td>
							<td>
								<input type="text" name="dq[]" value="<?php echo $recArr["q"]; ?>" style="width:20px;" />
							</td>
							<td>
								<?php echo $recArr["c"]; ?>
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
				<div style="margin:4px;">
					<fieldset>
						<legend>Label Header Settings</legend>
						<b>Prefix:</b> 
						<input type="text" name="lheaderprefix" value="" style="width:450px" /><br/>
						<b>Mid Section:</b><br/>
						<input type="radio" name="lheadermid" value="1" />Country<br/>
						<input type="radio" name="lheadermid" value="2" />State<br/>
						<input type="radio" name="lheadermid" value="3" />County<br/>
						<input type="radio" name="lheadermid" value="0" />Blank<br/>
						<b>Suffix:</b> 
						<input type="text" name="lheadersuffix" value="" style="width:450px" /><br/>
					</fieldset>					
				</div>
				<div style="margin:4px;">
					<b>Label Footer:</b> 
					<input type="text" name="lfooter" value="" style="width:450px" />
				</div>
				<div style="margin:10px;">
					<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
					<input type="submit" name="submitaction" value="Print Labels" /> 
				</div>
			</form>
			<?php 
		}
		
		?>
		
		</div>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
	</body>
</html>