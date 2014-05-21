<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceHarvester.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:'';
$action = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';

$harvManager = new OccurrenceHarvester();

$isEditor = 0;
$collList = array();
if($isAdmin){
	$isEditor = 1;
	$collList[] = 'all';
}
else{
	if(array_key_exists("CollEditor",$userRights)){
		if(in_array($collId,$userRights["CollEditor"])){
			$isEditor = 1;
		}
		$collList = $userRights["CollEditor"];
	}
	if(array_key_exists("CollAdmin",$userRights)){
		if(in_array($collId,$userRights["CollAdmin"])){
			$isEditor = 1;
		}
		$collList = array_merge($collList,$userRights["CollAdmin"]);
	}
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> - Occurrence Harvester</title>
		<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	    <link href="../../css/main.css" type="text/css" rel="stylesheet" />
		<script language="javascript" type="text/javascript">
			function validateDownloadForm(f){
				
				return true;
			}

			function loadOccurRecord(fieldObj){
				var occid = fieldObj.value;
				if(!occid) return false;
				fieldObj.value = "";
				fieldObj.focus;
				var ele = document.getElementById("occid-"+occid);
				if(ele) return false;

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
				listElem.appendChild(newDiv);
				listElem.appendChild(newInput);

				document.getElementById("emptylistdiv").style.display = "none";
				
				setOccurData(occid);
			}

			function setOccurData(occid){
				xmlHttp = GetXmlHttpObject();
				if(xmlHttp==null){
					alert ("Your browser does not support AJAX!");
					return;
				}
				var url = "rpc/getoccurrence.php?occid="+occid;
				xmlHttp.onreadystatechange=function(){
					if(xmlHttp.readyState==4 && xmlHttp.status==200){
						var retStr = xmlHttp.responseText;
						if(retStr){
							var occurObj = eval('(' + retStr + ')');
							var aElem = document.getElementById("a-"+occid);
							var newText = document.createTextNode(" - "+occurObj["recordedby"]+" ("+occurObj["recordnumber"]+") "+occurObj["eventdate"]);
							aElem.appendChild(newText);
						}
						else{
							alert("Record #"+occid+" does not appear to exist");
						}
					}
				};
				xmlHttp.open("POST",url,true);
				xmlHttp.send(null);
				return false;
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

			function GetXmlHttpObject(){
				var xmlHttp=null;
				try{
					// Firefox, Opera 8.0+, Safari, IE 7.x
			  		xmlHttp=new XMLHttpRequest();
			  	}
				catch (e){
			  		// Internet Explorer
			  		try{
			    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
			    	}
			  		catch(e){
			    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
			    	}
			  	}
				return xmlHttp;
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
			Once list is complete, you can enter your catalog number in the text field to include it into the file export or data transfer. 
		</div>
		<div style="margin:20px 0px">
			<hr/>
		</div>
		<div style="width:300px;float:right;">
			<form name="dlform" method="post" action="occurharvester.php" onsubmit="return validateDownloadForm(this)">
				<fieldset>
					<legend><b>Specimen Queue</b></legend>
					<div id="emptylistdiv" style="margin:20px;">
						<b>List Empty: </b>enter barcode in field to left
					</div>
					<div id="occidlist" style="margin:10px;">
					</div>
					<div style="margin:30px">
						<select>
							<option value="0">Select Collection</option>
							<option value="0">--------------------------------</option>
							<?php 
							$collArr = $harvManager->getCollections();
							?>
						</select>
						<input name="formsubmit" type="submit" value="Transfer Records" />
					</div>
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