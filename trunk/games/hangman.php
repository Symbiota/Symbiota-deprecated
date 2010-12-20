<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once('../classes/FloraProjectManager.php');
header("Content-Type: text/html; charset=".$charset);

 $proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
 $editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
 $projManager = new FloraProjectManager($proj);
 
 $isEditable = 0;
 if($isAdmin){
	$isEditable = 1;
 }
 
 if($isEditable){
 	if(array_key_exists("projsubmit",$_REQUEST)){
 		$projEditArr = Array();
 		$projEditArr["projname"] = $_REQUEST["projname"];
 		$projEditArr["managers"] = $_REQUEST["managers"];
 		$projEditArr["briefdescription"] = $_REQUEST["briefdescription"];
 		$projEditArr["fulldescription"] = $_REQUEST["fulldescription"];
 		$projEditArr["notes"] = $_REQUEST["notes"];
 		$projEditArr["sortsequence"] = $_REQUEST["sortsequence"];
 		$projManager->submitProjEdits($projEditArr);
 	}
 }
 
 ?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> SEINet Spelling Quiz</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<script type="text/javascript">
	
		function toggleById(target){
		  	var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
		 	else {
		 		obj.style.display="none";
		 	}
		}

		function toggleResearchInfoBox(anchorObj){
			var obj = document.getElementById("researchlistpopup");
			var pos = findPos(anchorObj);
			var posLeft = pos[0];
			if(posLeft > 550){
				posLeft = 550;
			}
			obj.style.left = posLeft - 40;
			obj.style.top = pos[1] + 25;
			if(obj.style.display=="block"){
				obj.style.display="none";
			}
			else {
				obj.style.display="block";
			}
			var targetStr = "document.getElementById('researchlistpopup').style.display='none'";
			var t=setTimeout(targetStr,25000);
		}

		function toggleSurveyInfoBox(anchorObj){
			var obj = document.getElementById("surveylistpopup");
			var pos = findPos(anchorObj);
			var posLeft = pos[0];
			if(posLeft > 550){
				posLeft = 550;
			}
			obj.style.left = posLeft - 40;
			obj.style.top = pos[1] + 25;
			if(obj.style.display=="block"){
				obj.style.display="none";
			}
			else {
				obj.style.display="block";
			}
			var targetStr = "document.getElementById('surveylistpopup').style.display='none'";
			var t=setTimeout(targetStr,20000);
		}

		function findPos(obj){
			var curleft = 0; 
			var curtop = 0;
			if(obj.offsetParent) {
				do{
					curleft += obj.offsetLeft;
					curtop += obj.offsetTop;
				}while(obj = obj.offsetParent);
			}
			return [curleft,curtop];
		}	
	</script>
</head>

<body <?php if($editMode) echo "onload=\"toggleById('projeditor');\"";?>>

	<?php
	$displayLeftMenu = (isset($projects_indexMenu)?$projects_indexMenu:"true");
	include('../header.php');
	if(isset($projects_indexCrumbs)){
		?>
		<div class="navpath">
			<a href="../index.php">Home</a> &gt; 
			<?php echo $projects_indexCrumbs;?>
			<b>$defaultTitle Project</b> 
		</div>
		<?php 
	}
	?>
	
	<!-- This is inner text! -->
	<div id="innertext">
<h1>Seinet Spelling Quiz</h1>
<h2><?php echo $listname;?></h2>
			<?php
			include ('hangmanbody.php');
			?>
	</div>
	<!-- This ends inner text! -->
	<?php
	include('../footer.php');
	?>

	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
			var pageTracker = _gat._getTracker("<?php echo $googleAnalyticsKey; ?>");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
</body>
</html>