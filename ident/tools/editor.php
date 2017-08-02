<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyEditorManager.php');
header("Cache-control: private; Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/tools/editor.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_POST)?$_POST["action"]:""; 
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$DEFAULT_LANG; 
$charValue = array_key_exists("char",$_REQUEST)?$_REQUEST["char"]:"";
$childrenStr = array_key_exists("children",$_REQUEST)?$_REQUEST["children"]:"";
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:""; 

$editorManager = new KeyEditorManager();

if(!$tid && $childrenStr){
	$childrenArr = explode(',',$childrenStr);
	$tid = array_pop($childrenArr);
	$childrenStr = implode(',',$childrenArr);
}
$editorManager->setLanguage($langValue);
$editorManager->setTid($tid);

$isEditor = false;
if($isAdmin || array_key_exists("KeyEditor",$userRights) || array_key_exists("KeyAdmin",$userRights)){
	$isEditor = true;
}

if($isEditor && $action){
	if($action=="Submit Changes"){
		$addArr = array_key_exists('add',$_POST)?$_POST['add']:null;
		$removeArr = array_key_exists('remove',$_POST)?$_POST['remove']:null;
		$editorManager->processTaxa($addArr,$removeArr);
	}
}
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Identification Character Editor</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
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
			newWindow = window.open(urlStr,windowName,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($ident_tools_editorMenu)?$ident_tools_editorMenu:'false');
	include($SERVER_ROOT.'/header.php');
	if(isset($ident_tools_editorCrumbs) && $ident_tools_editorCrumbs){
		echo "<div class='navpath'>";
		echo $ident_tools_editorCrumbs;
		echo "<b>Character Editor</b>";
		echo "</div>";
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt;
			<b>Character Editor</b>
		</div>
		<?php 
	}
?>
<div style="margin:15px;">
<?php 
if($isEditor){
	?>
  	<form action="editor.php" method="post" onsubmit="dataChanged=false;">
	<?php 
	if($tid){
 		$sn = $editorManager->getTaxonName();
 		if($editorManager->getRankId() > 140){
	  		$sn = "<i>$sn</i>";
 		}
 		echo "<div style='float:right;'>";
 		if($editorManager->getRankId() > 140){
			echo "<a href='editor.php?tid=".$editorManager->getParentTid()."&children=".($childrenStr?$childrenStr.',':'').$tid."'>edit parent</a>&nbsp;&nbsp;";
 		}
		if($childrenStr){
			echo "<br><a href='editor.php?children=".$childrenStr."'>back to child</a>";
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
				echo "<span class='".$heading."' onclick=\"toggle('".$heading."');\" style=\"display:none;\">$minusGif</span>";
				echo "<span class='".$heading."' onclick=\"toggle('".$heading."');\" style=\"display:;\">$plusGif</span>";
				echo " $heading</div>\n";
				echo "<div class='".$heading."' id='".$heading."' style='text-indent:1em;".($charValue?"":" display:none;")."'>";
				foreach($charArray as $cidKey => $charNameStr){
					if(!$charValue || $charValue == $cidKey){
						echo "<div id='chardiv".$cidKey."' style='display:".(array_key_exists($cidKey,$depArr)?"hidden":"block").";'>";
						echo "<div style='margin-top:1em;'><span style='font-weight:bold; font-size:larger;'>$charNameStr</span>\n";
						if($editorManager->getRankId() > 140){
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='font-size:smaller;'>";
							echo "<a href=\"#\" onclick=\"openPopup('editor.php?tid=".$editorManager->getParentTid()."&char=".$cidKey."','technical');\">parent</a>";
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
				<input type="hidden" name="children" value="<?php echo $childrenStr; ?>" />
				<input type="hidden" name="lang" value="<?php echo $langValue; ?>" />
			</div>
			<?php 
		}
  	}
	?>
	</form>
	<?php 
}
else{  
	echo "<h1>You do not have authority to edit character data or there is a problem with the database connection.</h1>";
}
?>
</div>
<?php 
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>	