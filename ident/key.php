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

$data = Array();
$chars = Array();
$taxa = Array();
if($keyModIsActive){
	$data = $dataManager->getData();
	$chars = $data["chars"];  				//$chars = Array(HTML Strings)
	$taxa = $data["taxa"];					//$taxa  = Array(family => array(TID => DisplayName))
}

//Harevest and remove language list from $chars
$languages = Array();
if($chars){
	$languages = $chars["Languages"];
	unset($chars["Languages"]);
}
?>
<head>
	<title><?php echo $defaultTitle; ?> Web-Key: <?php echo $projValue; ?></title>
	<link rel='stylesheet' href='../css/main.css' type='text/css' />
	<meta name="keywords" content="interactive key,plants identification,<?php echo $dataManager->getClName(); ?>" />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript" src="../js/symb/ident.key.js"></script>
</head>
 
<body>

<?php 
	$displayLeftMenu = (isset($ident_keyMenu)?$ident_keyMenu:true);
	include($serverRoot.'/header.php');
	if(isset($ident_keyCrumbs)){
		if($ident_keyCrumbs){
			echo '<div class="navpath">';
			echo '<a href="../index.php">Home</a> &gt; ';
			if($dynClid){
				if($dataManager->getClType() == 'Specimen Checklist'){
					echo '<a href="'.$clientRoot.'/collections/list.php?tabindex=0">';
					echo 'Occurrence Checklist';
					echo '</a> &gt; ';
				}
			}
			else{
				echo $ident_keyCrumbs;
			}
			echo ' <b>'.$dataManager->getClName().' Key</b>';
			echo '</div>';
		}
	}
	else{
		echo '<div class="navpath">';
		echo '<a href="../index.php">Home</a> &gt; ';
		if($dynClid){
			if($dataManager->getClType() == 'Specimen Checklist'){
				echo '<a href="'.$clientRoot.'/collections/list.php?tabindex=0">';
				echo 'Occurrence Checklist';
				echo '</a> &gt; ';
			}
		}
		echo '<b>'.$dataManager->getClName().' Key</b>';
		echo '</div>';
	}
	
?>
<div id="innertext">
<?php 
if($keyModIsActive){
?>
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
			?>
			<table border='0' width='300px'>
				<tr><td colspan='2'>
					<h2>
						<?php 
						if($floraModIsActive){
							echo "<a href='../checklists/checklist.php?cl=".$clValue."&dynclid=".$dynClid."'>";
						}
						echo $dataManager->getClName()." ";
						if($floraModIsActive){
							echo "<img src='../images/info.jpg' title='More information' border='0' width='12' /></a>";
						}
						?>
					</h2>
			<?php 
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
<?php 
}
else{
	echo '<h1>Identification key module has not been activated for this data portal</h1>';
}
?>
</div>
<?php
	include($serverRoot.'/footer.php');
?>

</body>
</html>

