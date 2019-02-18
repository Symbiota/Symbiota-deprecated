<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceSupport.php');
header("Content-Type: text/html; charset=".$charset);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:'';
$action = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';

$harvManager = new OccurrenceSupport();

$isEditor = 0;
$collList = array();
if($isAdmin){
	$isEditor = 1;
	$collList[] = 'all';
}
else{
	if(array_key_exists("CollEditor",$userRights)){
		if(in_array($collid,$userRights["CollEditor"])){
			$isEditor = 1;
		}
		$collList = $userRights["CollEditor"];
	}
	if(array_key_exists("CollAdmin",$userRights)){
		if(in_array($collid,$userRights["CollAdmin"])){
			$isEditor = 1;
		}
		$collList = array_merge($collList,$userRights["CollAdmin"]);
	}
}

if($isEditor){
	if($action == 'Download Records'){
		$harvManager->exportCsvFile($_POST);
		exit;
	}
	else{
		
	}
}
?>
<!DOCTYPE HTML>
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> - Occurrence Harvester</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script language="javascript" type="text/javascript">
			function validateDownloadForm(f){
				
				return true;
			}

			function loadOccurRecord(fieldObj){
				var occid = fieldObj.value;
				fieldObj.value = "";
				if(!occid) return false;
				if(document.getElementById("occid-"+occid)) return false;

				var newAnchor = document.createElement('a');
				newAnchor.setAttribute("id", "a-"+occid);
				newAnchor.setAttribute("href", "#");
				newAnchor.setAttribute("onclick", "openIndPopup("+occid+");return false;");
				var newText = document.createTextNode(occid);
				newAnchor.appendChild(newText);

				var newDiv = document.createElement('div');
				newDiv.setAttribute("id", "occid-"+occid);
				newDiv.appendChild(newAnchor);

				var newInput = document.createElement('input');
				newInput.setAttribute("type", "hidden");
				newInput.setAttribute("name", "occid[]");
				newInput.setAttribute("value", occid);

				var listElem = document.getElementById("occidlist");
				//listElem.appendChild(newDiv);
				listElem.insertBefore(newDiv,listElem.childNodes[0]);
				listElem.appendChild(newInput);

				document.getElementById("emptylistdiv").style.display = "none";
				
				setOccurData(occid);
				fieldObj.focus();
			}

			function setOccurData(occidInVal){
				$.ajax({
					type: "POST",
					url: "rpc/getoccurrence.php",
					dataType: "json",
					data: { occid: occidInVal }
				}).done(function( data ) {
					var aElem = document.getElementById("a-"+occidInVal);
					var newText;
					if(data != ""){
						newText = document.createTextNode(" - "+data.recordedby+" #"+data.recordnumber+" ("+data.eventdate+")");
					}
					else{
						newText = document.createTextNode(" - unable to locate occurrence record");
					}
					aElem.appendChild(newText);
				});
			}

			function openIndPopup(occid){
				var urlStr = '../individual/index.php?occid=' + occid;
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
	<body>
	<?php
	$displayLeftMenu = (isset($collections_datasets_indexMenu)?$collections_datasets_indexMenu:true);
	include($serverRoot."/header.php");
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt; 
		<?php
		if(isset($collections_datasets_occurharvesterCrumbs)){
			echo $collections_datasets_occurharvesterCrumbs;
		}
		?>
		<b>Occurrence Harvester</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<div style="margin:15px">
			Scan or type barcode number into field below and then hit enter or tab to add the specimen to the list. 
			Once list is complete, you can enter your catalog number in the text field and then transfer to your collection 
			or file export to a file that can be imported into your local database. 
		</div>
		<div style="margin:20px 0px">
			<hr/>
		</div>
		<div style="width:450px;float:right;">
			<form name="dlform" method="post" action="occurharvester.php" target="_blank">
				<fieldset>
					<legend><b>Specimen Queue</b></legend>
					<div id="emptylistdiv" style="margin:20px;">
						<b>List Empty: </b>enter barcode in field to left
					</div>
					<div id="occidlist" style="margin:10px;">
					</div>
					<?php 
					if($collid){
						?>
						<div style="margin:30px">
							<input name="formsubmit" type="submit" value="Transfer Records" />
						</div>
						<?php 
					}
					?>
					<div style="margin:30px">
						<input name="formsubmit" type="submit" value="Download Records" />
					</div>
				</fieldset>
			</form>
		</div>
		<div style="">
			<b>Occurrence ID:</b><br/>
			<input type="text" name="occidsubmit" onchange="loadOccurRecord(this)" />
		</div>

	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
	</body>
</html>