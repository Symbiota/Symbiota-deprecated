<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/KeyEditorManager.php');
header("Cache-control: private; Content-Type: text/html; charset=".$charset);
 
$addValues = Array();
$removeValues = Array();
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 
$charValue = array_key_exists("char",$_REQUEST)?$_REQUEST["char"]:""; 
$child1Value = array_key_exists("child1",$_REQUEST)?$_REQUEST["child1"]:""; 
$child2Value = array_key_exists("child2",$_REQUEST)?$_REQUEST["child2"]:""; 
$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:""; 
$tQueryValue = array_key_exists("tquery",$_REQUEST)?$_REQUEST["tquery"]:""; 
$tidValue = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:""; 
$taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
$addValues = array_key_exists("add",$_REQUEST)?$_REQUEST["add"]:""; 
$removeValues = array_key_exists("remove",$_REQUEST)?$_REQUEST["remove"]:""; 

$editorManager = new KeyEditorManager();
if($langValue) $editorManager->setLanguage($langValue);
$editable = false;
if($isAdmin || array_key_exists("KeyEditor",$userRights) || array_key_exists("KeyAdmin",$userRights)){
	$editable = true;
}
?>

<html>
<head>
	<title><?php echo $defaultTitle; ?> Identification Character Editor</title>
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<script language="javascript">

		var dataChanged = false;
		
		window.onbeforeunload = verifyClose;
		
		function verifyClose() { 
			if (dataChanged == true) { 
				return "You will lose any unsaved data if you don't first submit your changes!"; 
			} 
		}
		
		function toggle(target){
			var divObjs = document.getElementsByTagName("div");
		  	for (i = 0; i < divObjs.length; i++) {
		  		var obj = divObjs[i];
		  		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
						if(obj.style.display=="none"){
							obj.style.display="inline";
						}
				 	else {
				 		obj.style.display="none";
				 	}
				}
			}
			var spanObjs = document.getElementsByTagName("span");
			for (i = 0; i < spanObjs.length; i++) {
				var obj = spanObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="inline";
					}
					else {
						obj.style.display="none";
					}
				}
			}
		}
		
		function showSearch(){
			document.getElementById("searchDiv").style.display="block";
			document.getElementById("searchDisplay").style.display="none";
		}
		
		function openPopup(urlStr,windowName){
			var wWidth = 900;
			if(document.getElementById('maintable').offsetWidth){
				wWidth = document.getElementById('maintable').offsetWidth*1.05;
			}
			else if(document.body.offsetWidth){
				wWidth = document.body.offsetWidth*0.9;
			}
			newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($ident_tools_editorMenu)?$ident_tools_editorMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($ident_tools_editorCrumbs)){
		echo "<div class='navpath'>";
		echo $ident_tools_editorCrumbs;
		echo "<b>Character Editor</b>";
		echo "</div>";
	}
	
	
	
?>
<div style="margin:15px;">
<?php 
 if($editable){
	 if($tidValue) $editorManager->setTaxon($tidValue);
	 if($taxonValue) $editorManager->setTaxon($taxonValue);
	 if($addValues) $editorManager->setAddStates($addValues);
	 if($removeValues) $editorManager->setRemoveStates($removeValues);
	
	 //Set username
	 if(array_key_exists("un",$paramsArr)) $editorManager->setUsername($paramsArr["un"]);
	?>
  	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="dataChanged=false;">
	<?php 
	if(($action=="Submit Changes" || $action=="Get Character Info") && (!empty($taxonValue) || !empty($tidValue))){
		//Show character info for selected taxon
 		if($action=="Submit Changes"){
 			$editorManager->processTaxa();
 		}
 		$sn = $editorManager->getTaxonName();
 		if($editorManager->getRankId() > 140){
	  		$sn = "<i>$sn</i>";
 		}
 		echo "<div style='float:right;'>";
 		if($editorManager->getRankId() > 140){
			echo "<a href='editor.php?taxon=".$editorManager->getParentTid()."&action=Get+Character+Info&child1=".$editorManager->getTid().($child1Value?"&child2=$child1Value":"")."'>edit parent</a>&nbsp;&nbsp;";
 		}
		if($child1Value){
			echo "<br><a href='editor.php?taxon=".$child1Value."&action=Get+Character+Info".($child2Value?"&child1=".$child2Value:"")."'>back to child</a>";
		}
		echo "</div>";
 		echo "<h2>$sn</h2>";
		$cList = $editorManager->getCharList();
		$depArr = $editorManager->getCharDepArray();
		$charStatesList = $editorManager->getCharStates();
		if($cList){
			$count = 0;
			$minusGif = "<img src='../../images/minus_sm.png'>";
			$plusGif = "<img src='../../images/plus_sm.png'>";
			foreach($cList as $heading => $charArray){ 
				echo "<div style='font-weight:bold; font-size:150%; margin:1em 0em 1em 0em; color:#990000;".($charValue?" display:none;":"")."'>";
				echo "<span class='".$heading."' onclick=\"javascript: toggle('".$heading."');\" style=\"display:none;\">$minusGif</span>";
				echo "<span class='".$heading."' onclick=\"javascript: toggle('".$heading."');\" style=\"display:;\">$plusGif</span>";
				echo " $heading</div>\n";
				echo "<div class='".$heading."' id='".$heading."' style='text-indent:1em;".($charValue?"":" display:none;")."'>";
				foreach($charArray as $cidKey => $charNameStr){
					if(!$charValue || $charValue == $cidKey){
						echo "<div id='chardiv".$cidKey."' style='display:".(array_key_exists($cidKey,$depArr)?"hidden":"block").";'>";
						echo "<div style='margin-top:1em;'><span style='font-weight:bold; font-size:larger;'>$charNameStr</span>\n";
						if($editorManager->getRankId() > 140){
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='font-size:smaller;'>";
							echo "<a href=\"#\" onclick=\"openPopup('editor.php?taxon=".$editorManager->getParentTid()."&action=Get+Character+Info&char=".$cidKey."','technical');\">parent</a>";
							echo "</span>\n";
						}
						echo "</div>\n";
						echo "<div style='font-size:smaller; text-indent:2.5em;'>Add&nbsp;&nbsp;Remove</div>\n";
						$cStates = $charStatesList[$cidKey];
						foreach($cStates as $csKey => $csValue){
							$testStr = $cidKey."_".$csKey;
							$charPresent = $editorManager->isSelected($testStr);
							$inh = $editorManager->getInheritedStr($testStr);
							$displayStr = ($charPresent?"<span style='font-size:larger;font-weight:bold;'>":"").$csValue.$inh.($charPresent?"</span>":"");
							echo "<div style='text-indent:2em;'><input type='checkbox' name='add[]' ".($charPresent && !$inh?"disabled='true' ":" ")." value='".$testStr."' onChange='dataChanged=true;'/>";
							echo "&nbsp;&nbsp;&nbsp;<input type='checkbox' name='remove[]' ".(!$charPresent || $inh?"disabled='true' ":" ")."value='".$testStr."'  onChange='dataChanged=true;'/>";
							echo "&nbsp;&nbsp;&nbsp;$displayStr</div>\n";
						}
						echo "</div>";
						$count++;
						if($count%3 == 0) echo "<div style='margin-top:1em;'><input type='submit' name='action' value='Submit Changes'/></div>\n";
					}
				}
				echo "</div>\n";
			}
			echo "<div style='margin-top:1em;'><input type='submit' name='action' value='Submit Changes'/></div>\n";
			//Hidden values to maintain values and display mode
			if($charValue){
				echo "<div><br><b>Note:</b> changes made here will not be reflected on child page until page is refreshed.</div>";
				echo "<div><input type='hidden' name='char' value='".$charValue."'/></div>";
			}
			?>
			<div>
				<input type="hidden" name="tid" value="<?php echo $editorManager->getTid(); ?>" />
				<input type="hidden" name="child1" value="<?php echo $child1Value; ?>" />
				<input type="hidden" name="child2" value="<?php echo $child2Value; ?>" />
			</div>
			<?php 
		}
  	}
 	else{
 		echo "<h3>Enter a taxon and checklist combination and hit submit.</h3>";
 	}
	?>
	</form>
 <?php 
 }
 else{  //Not editable or writable connection is not set
	echo "<h1>You do not have authority to edit character data or there is a problem with the database connection.</h1> <h3>Note that you must first login to the system.</h3>";
 }
 ?>
 </div>
 <?php 
 include($serverRoot.'/footer.php');
?>
 
 </body>
</html>
	