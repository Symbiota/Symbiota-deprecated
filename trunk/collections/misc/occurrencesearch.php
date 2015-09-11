<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceSupport.php');
header("Content-Type: text/html; charset=".$charset);

$targetId = $_REQUEST["targetid"];
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists("action",$_POST)?$_POST["action"]:'';
$catalogNumber = array_key_exists("catalognumber",$_POST)?$_POST['catalognumber']:'';
$otherCatalogNumbers = array_key_exists("othercatalognumbers",$_POST)?$_POST['othercatalognumbers']:'';
$recordedBy = array_key_exists("recordedby",$_POST)?$_POST['recordedby']:'';
$recordNumber = array_key_exists("recordnumber",$_POST)?$_POST['recordnumber']:'';

$collEditorArr = array();
if(array_key_exists("CollAdmin",$USER_RIGHTS)){
	$collEditorArr = $USER_RIGHTS['CollAdmin'];
}
if(array_key_exists("CollEditor",$USER_RIGHTS)){
	$collEditorArr = array_unique(array_merge($collEditorArr,$USER_RIGHTS['CollEditor']));
}

$occManager = new OccurrenceSupport();
$collArr = $occManager->getCollectionArr($IS_ADMIN?'all':$collEditorArr);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Search Page</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />	
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
	    function updateParentForm(occId) {
	        opener.document.getElementById("<?php echo $targetId;?>").value = occId;
	        self.close();
	        return false;
	    }

	    function verifyOccurSearchForm(f){
			if(!f.collid.value){
				alert("You must select target collection");
				return false;
			}
			if(!f.catalognumber.value && !f.othercatalognumbers.value && !f.recordedby.value && !f.recordnumber.value){
				alert("You must enter at least one search term");
				return false;
			}
			return true;
	    }

	    function linkToNewOccurrence(f){
		    if(!f.collid.value){
				alert("You must select target collection");
				return false;
		    }
		    else{
				$.ajax({
					type: "POST",
					url: "../editor/rpc/occuradd.php",
					data: { 
						collid: f.collid.value 
					}
				}).done(function( retStr ) {
					if(isNumeric(retStr)){
						updateParentForm(retStr);
					}
					else{
						alert("Unable to create new record due to unknown error ("+retStr+"). Contact portal administrator");
					}
				});
		    }
		}

	    function isNumeric(inStr){
	       	var validChars = "0123456789-.";
	       	var isNumber = true;
	       	var charVar;

	       	for(var i = 0; i < inStr.length && isNumber == true; i++){ 
	       		charVar = inStr.charAt(i); 
	    		if(validChars.indexOf(charVar) == -1){
	    			isNumber = false;
	    			break;
	          	}
	       	}
	    	return isNumber;
	    }
	</script>
</head>
<body>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($collEditorArr){
			?>
			<form name="occform" action="occurrencesearch.php" method="post" onsubmit="return verifyOccurSearchForm(this)" >
				<fieldset style="width:650px;">
					<legend><b>Voucher Search Pane</b></legend>
					<div style="clear:both;padding:2px;">
						<div style="float:left;width:130px;">Target Collection:</div>
						<div style="float:left;">
							<select name="collid">
								<option value="">Select Collection</option>
								<option value="">--------------------------------</option>
								<?php
								foreach($collArr as $id => $collName){
									echo '<option value="'.$id.'" '.($id == $collid?'SELECTED':'').'>'.$collName.'</option>';
								}  
								?>
							</select>
						</div>
					</div>
					<div style="clear:both;padding:2px;">
						<div style="float:left;width:130px;">Catalog #:</div>
						<div style="float:left;"><input name="catalognumber" type="text" /></div>
					</div>
					<div style="clear:both;padding:2px;">
						<div style="float:left;width:130px;">Other Catalog #:</div>
						<div style="float:left;"><input name="othercatalognumbers" type="text" /></div>
					</div>
					<div style="clear:both;padding:2px;">
						<div style="float:left;width:130px;">Collector Last Name:</div>
						<div style="float:left;"><input name="recordedby" type="text" /></div>
					</div>
					<div style="clear:both;padding:2px;">
						<div style="float:left;width:130px;">Collector Number:</div>
						<div style="float:left;"><input name="recordnumber" type="text" /></div>
					</div>
					<div style="clear:both;padding:2px;">
						<input name="action" type="submit" value="Search Occurrences" />
						<input type="hidden" name="targetid" value="<?php echo $targetId;?>" />
					</div>
				</fieldset>
			</form>
			<?php
			if($action){ 
				if($occArr = $occManager->getOccurrenceList($collid, $catalogNumber, $otherCatalogNumbers, $recordedBy, $recordNumber)){
					echo '<div style="margin:30px 10px;">';
					foreach($occArr as $occid => $vArr){
						?>
						<div style="margin:10px;">
							<?php echo "<b>OccId ".$occid.":</b> ".$vArr["recordedby"]." [".($vArr["recordnumber"]?$vArr["recordnumber"]:$vArr["eventdate"])."]; ".$vArr["locality"];?>
							<div style="margin-left:10px;cursor:pointer;color:blue;" onclick="updateParentForm('<?php echo $occid;?>')">
								Select Occurrence Record
							</div>
						</div>
						<hr />
						<?php 
					}
					echo '</div>';
				}
				else{
					?>
					<div style="margin:30 10px;">
						<b>No records were returned. Please modify your search and try again.</b> 
					</div>
					<?php 
				}
			}
			?>
			<form name="occform" action="occurrencesearch.php" method="post" onsubmit="return false" >
				<fieldset style="width:650px;padding:20px">
					<legend><b>Link to New Occurrence Record</b></legend>
					<select name="collid">
						<option value="">Select Collection</option>
						<option value="">--------------------------------</option>
						<?php
						foreach($collArr as $id => $collName){
							echo '<option value="'.$id.'" '.($id == $collid?'SELECTED':'').'>'.$collName.'</option>';
						}  
						?>
					</select>
					<button type="button" onclick="linkToNewOccurrence(this.form)">Create New Occurrence</button>
				</fieldset>
			</form>
			<?php
		}
		else{
			?>
			<div style="margin:30 10px;">
				<b>You are not authorized to link to any collections</b> 
			</div>
			<?php 
		} 
		?> 
	</div>
</body>
</html>