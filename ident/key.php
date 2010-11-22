<?php
/*
 * Created on Jun 11, 2006
 * By E.E. Gilbert
 */
//error_reporting(E_ALL);
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/KeyDataManager.php');
 header("Content-Type: text/html; charset=".$charset);
 $editable = false;
 if($isAdmin || array_key_exists("KeyEditor",$userRights)){
 	$editable = true;
 }
 ?> 

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">

<?php
$spLink = "../taxa/index.php?taxon=--SPECIES--";
// $spLink = "http://mobot.mobot.org/cgi-bin/search_vast?name=--SPECIES--";
$secondaryLink = "";
$secondaryIcon = "";
//$secondaryLink = "http://images.google.com/images?q=&#034;--SPECIES--&#034;";
//$secondaryIcon = "../images/google.jpg";
//$secondaryLink = "http://132.236.163.181/cgi-bin/dol/dol_terminal.pl?taxon_name=--SPECIES--&rank=genus&classif_id=0";
//$secondaryIcon = "dol.org";

$attrsValues = Array();

$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:""; 
$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0; 
$taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
$action = array_key_exists("submitbutton",$_REQUEST)?$_REQUEST["submitbutton"]:""; 
$rv = array_key_exists("rv",$_REQUEST)?$_REQUEST["rv"]:""; 
$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
$langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 
$displayMode = array_key_exists("displaymode",$_REQUEST)?$_REQUEST["displaymode"]:""; 
if(!$action){
	$attrsValues = array_key_exists("attr",$_REQUEST)?$_REQUEST["attr"]:"";	//Array of: cid + "-" + cs (ie: 2-3) 
}
$crumbLink = array_key_exists("crumblink",$_REQUEST)?$_REQUEST["crumblink"]:""; 
 
 $dataManager = new KeyDataManager();
 if(!$langValue) $langValue = $defaultLang;
 if($displayMode) $dataManager->setCommonDisplay(true);;  
 $dataManager->setLanguage($langValue);
 if($projValue) $dataManager->setProject($projValue);
 if($dynClid) $dataManager->setDynClid($dynClid);
 $dataManager->setClValue($clValue);
 if($taxonValue) $dataManager->setTaxonFilter($taxonValue);
 if($attrsValues) $dataManager->setAttrs($attrsValues);
 if($rv) $dataManager->setRelevanceValue($rv);
 $data = $dataManager->getData();
 $chars = $data["chars"];  				//$chars = Array(HTML Strings)
 $taxa = $data["taxa"];					//$taxa  = Array(family => array(TID => DisplayName))
 
 
//Harevest and remove language list from $chars
$languages = Array();
if($chars){
    $languages = $chars["Languages"];
    unset($chars["Languages"]);
}
?>
<head>
<?php 
	echo"<title>$defaultTitle Web-Key: $projValue</title>\n";
	echo"<link rel='stylesheet' href='../css/main.css' type='text/css'>\n";
	$keywordStr = "interactive key,plants identification,".$dataManager->getClName();
	echo"<meta name=\"keywords\" content=\"".$keywordStr."\" />\n";
?>
	<script type="text/javascript">
	
		function toggleAll(){
			toggleChars("dynam");
			toggleChars("dynamControl");
		}
		
		function toggleChars(name){
		  var chars = document.getElementsByTagName("div");
		  for (i = 0; i < chars.length; i++) {
		  	var obj = chars[i];
				if(obj.className == name){
					if(obj.style.display=="none"){
						obj.style.display="block";
						setCookie("all");
					}
				 	else {
				 		obj.style.display="none";
						setCookie("limited");
				 	}
				}
		  }
		}
		
		function setCookie(status){
			document.cookie = "showchars=" + status;		
		}
	
		function getCookie(name){
			var pos = document.cookie.indexOf(name + "=");
			if(pos == -1){
				return null;
			} else {
				var pos2 = document.cookie.indexOf(";", pos);
				if(pos2 == -1){
					return unescape(document.cookie.substring(pos + name.length + 1));
				}else{
					return unescape(document.cookie.substring(pos + name.length + 1, pos2));
				}
			}
		}
		
		function setDisplayStatus(){
			var showStatus = getCookie("showchars");
			if(showStatus == "all"){
				toggleAll();
			} else {
				//If everything is hid, show all; if everything is not hid, do nothing
				if(allClosed()) toggleAll();
			}
		}
		
		function allClosed(){
		  var objs = document.getElementsByTagName("div");
		  for (i = 0; i < objs.length; i++) {
		  	var obj = objs[i]; 
				if(obj.id != "showall" && obj.style.display != "none"){
					return false;
				}
			}
			return true;
		}
		
		function setLang(list){
		  var langName = list.options[list.selectedIndex].value;
		  var objs = document.getElementsByTagName("span");
		  for (i = 0; i < objs.length; i++) {
		  	var obj = objs[i]; 
				if(obj.lang == langName){
					obj.style.display="";
				}
				else if(obj.lang != ""){
			 		obj.style.display="none";
				}
			}
		}
	</script>
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?php echo $googleAnalyticsKey; ?>']);
		_gaq.push(['_trackPageview']);
	
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
</head>
 
<body>

<?php 
	$displayLeftMenu = (isset($ident_keyMenu)?$ident_keyMenu:"true");
	include($serverRoot.'/header.php');
	if($crumbLink || isset($ident_keyCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		if($crumbLink == "occurcl"){
			echo "<a href='".$clientRoot."/collections/checklist.php'>";
			echo "Occurrence Checklist";
			echo "</a> &gt; ";
		}
		elseif(!$dynClid){
			echo $ident_keyCrumbs;
		}
		echo " <b>".$dataManager->getClName()." Key</b>";
		echo "</div>";
	}
	
?>
<div id="innertext">
<form name="keyform" id="keyform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
  <table border="0" width="590">
    <tr>
      <td valign="top" width="200">
      	<table>
      	  <tr>
      	  <td>
	  		<?php 
			if(!$dynClid){
	  			echo "<div style='font-weight:bold;'>Checklist:</div>";
				echo "<select name='cl'>";
	  			$selectList = Array();
	  			$selectList = $dataManager->getClFilterList($paramsArr["uid"]);
	  			foreach($selectList as $key => $value){
	  				$selectStr = ($key==$clValue?"SELECTED":"");
	  				echo "<option value='".$key."' $selectStr>$value</option>\n";
	  			}
				echo "</select>";
			}
			//Pass project value to next page
			echo "<input Type='hidden' Id='dynclid' Name='dynclid' Value='".$dynClid."' />";
			echo "<input Type='hidden' Id='proj' Name='proj' Value='".$projValue."' />";
			echo "<input Type='hidden' Id='rv' Name='rv' Value='".$dataManager->getRelevanceValue()."' />";
			if($projValue) echo "<div><a href='clgmap.php?proj=".$projValue."'>map view</a></div>";
			?>
			<div style='font-weight:bold; margin-top:0.5em;'>Taxon:</div>
			<select name="taxon">
		  		<?php  
	  				echo "<option value='All Species'>-- Select a Taxonomic Group --</option>\n";
		  			$selectList = Array();
		  			$selectList = $dataManager->getTaxaFilterList();
		  			foreach($selectList as $value){
		  				$selectStr = ($value==$taxonValue?"SELECTED":"");
		  				echo "<option $selectStr>$value</option>\n";
		  			}
		  		?>
		  	</select>
      	  </td>
      	  </tr>
  		</table>
		<div style='font-weight:bold; margin-top:0.5em;'>
		    <input type="submit" name="submitbutton" id="submitbutton" value="Display/Reset Species List"/>
		</div>
  		<hr size="2" />
  		
  		<?php
//		echo "<div style=''>Relevance value: <input name='rv' type='text' size='3' title='Only characters with > ".($rv*100)."% relevance to the active spp. list will be displayed.' value='".$dataManager->getRelevanceValue()."'></div>";
		//List char Data with selected states checked
  		if(count($languages) > 1){
			echo "<div id=langlist style='margin:0.5em;'>Languages: <select name='lang' onchange='setLang(this);'>\n";
			foreach($languages as $l){
				echo "<option value='".$l."' ".($defaultLang == $l?"SELECTED":"").">$l</option>\n";
			}
			echo "</select></div>\n";
  		}
  		echo "<div style='margin:5px'>Display as: <select name='displaymode' onchange='javascript: document.forms[0].submit();'><option value='0'>Scientific Name</option><option value='1'".($displayMode?" SELECTED":"").">Common Name</option></select></div>";
  		if($chars){
			//echo "<div id='showall' class='dynamControl' style='display:none'><a href='#' onclick='javascript: toggleAll();'>Show All Characters</a></div>\n";
			//echo "<div class='dynamControl' style='display:block'><a href='#' onclick='javascript: toggleAll();'>Hide Advanced Characters</a></div>\n";
			foreach($chars as $key => $htmlStrings){
				echo $htmlStrings."\n";
  		  	}
  		}
  		?>
      </td>
	  <td width="20" background="../images/brown_hor_strip.gif"></td>
      <td valign="top">
    
		<?php
		//List taxa by family/sci name
		if(($clValue && $taxonValue) || $dynClid){
			echo "<table border='0' width='300px'>";
			echo "<tr><td colspan='2'>";
			echo "<h2>".$dataManager->getClName()." ";
			if($floraModIsActive){
				echo "<a href='../checklists/checklist.php?cl=".$clValue."&dynclid=".$dynClid."&crumblink=".$crumbLink."'>";
				echo "<img src='../images/info.jpg' title='More information' border='0' width='12' /></a>";
			}
			echo "</h2>";
			if(!$dynClid) echo "<div>".$dataManager->getClAuthors()."</div>";
			echo "</td></tr>";
			$count = $dataManager->getTaxaCount();
		  	if($count > 0){
		  		echo "<tr><td colspan='2'>Species Count: ".$count."</td></tr>\n";
		  	}
		  	else{
					echo "<tr><td colspan='2'>There are no species matching your criteria. Please deselect some characters to make the search less restrictive.</td></tr>\n";
		  	} 
			ksort($taxa);
		  	foreach($taxa as $family => $species){
				echo "<tr><td colspan='2'><h3>$family</h3></td></tr>\n";
				natcasesort($species);
				foreach($species as $tid => $disName){
					$newSpLink = str_replace("--SPECIES--", $tid, $spLink)."&cl=".($dataManager->getClType()=="static"?$dataManager->getClName():"");
					echo "<tr><td><div style='margin:0px 5px 0px 10px;'><a href='".$newSpLink."' target='_blank'><i>$disName</i></a></div></td>\n";
					echo "<td align='right'>\n";
					if($secondaryIcon && $secondaryLink){
						$newLink = str_replace("--SPECIES--", $disName, $secondaryLink);
						echo "&nbsp;<a href='".$newLink."' target='_blank'><img src='".$secondaryIcon."' width='40' border='0' title='View Google Images'/></a>";
					}
					if($editable){
						echo "<a href='tools/editor.php?taxon=$tid&action=Get+Character+Info&lang=".$defaultLang."' target='_blank'><img src='../images/edit.png' width='15px' border='0' title='Edit morphology' /></a>\n";
					}
					echo "</td></tr>\n";
				}
			}
			echo "</table>";
		}
		else{
			echo $dataManager->getIntroHtml();
		}
		?>
	  </td>
    </tr>
  </table>
  <?php 
  	if(array_key_exists("crumburl",$_REQUEST)) echo "<input type='hidden' name='crumburl' value='".$_REQUEST["crumburl"]."' />";
  	if(array_key_exists("crumbtitle",$_REQUEST)) echo "<input type='hidden' name='crumbtitle' value='".$_REQUEST["crumbtitle"]."' />";
  ?>
  </form>
</div>
<?php
	include($serverRoot.'/footer.php');
?>

</body>
</html>

