<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/KeyDataManager.php');
header("Content-Type: text/html; charset=".$charset);
$editable = false;
if($isAdmin || array_key_exists("KeyEditor",$userRights)){
	$editable = true;
}

$spLink = "../taxa/index.php?taxon=--SPECIES--";
// $spLink = "http://mobot.mobot.org/cgi-bin/search_vast?name=--SPECIES--";
$secondaryLink = "";
$secondaryIcon = "";
//$secondaryLink = "http://images.google.com/images?q=&#034;--SPECIES--&#034;";
//$secondaryIcon = "../images/google.png";
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
if($projValue) $pid = $dataManager->setProject($projValue);
if($dynClid) $dataManager->setDynClid($dynClid);
$clid = $dataManager->setClValue($clValue);
if($taxonValue) $dataManager->setTaxonFilter($taxonValue);
if($attrsValues) $dataManager->setAttrs($attrsValues);
if($rv) $dataManager->setRelevanceValue($rv);

$data = Array();
$chars = Array();
$taxa = Array();
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

<!DOCTYPE html>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Web-Key: <?php echo preg_replace('/\<[^\>]+\>/','',$dataManager->getClName()); ?></title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
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
		echo '<a href="../index.php">Home</a> &gt;&gt; ';
		if($dynClid){
			if($dataManager->getClType() == 'Specimen Checklist'){
				echo '<a href="'.$clientRoot.'/collections/list.php?tabindex=0">';
				echo 'Occurrence Checklist';
				echo '</a> &gt;&gt; ';
			}
		}
		elseif($clid){
			echo '<a href="'.$clientRoot.'/checklists/checklist.php?cl='.$clid.'">';
			echo 'Checklist: '.$dataManager->getClName();
			echo '</a> &gt;&gt; ';
		}
		elseif($pid){
			echo '<a href="'.$clientRoot.'/projects/index.php?proj='.$pid.'">';
			echo 'Project Checklists';
			echo '</a> &gt;&gt; ';
		}
		echo '<b>Key: '.$dataManager->getClName().'</b>';
		echo '</div>';
	}
	
?>
<div id="innertext">
	<form name="keyform" id="keyform" action="key.php" method="get">
		<table border="0" width="590">
			<tr>
			<td valign="top" width="200">
				<div>
					<div style="font-weight:bold; margin-top:0.5em;">Taxon:</div>
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
				</div>
				<div style='font-weight:bold; margin-top:0.5em;'>
					<input type="hidden" id="cl" name="cl" value="<?php echo $clid; ?>" />
					<input type="hidden" id="dynclid" name="dynclid" value="<?php echo $dynClid; ?>" />
					<input type="hidden" id="proj" name="proj" value="<?php echo $projValue; ?>" />
					<input type="hidden" id="rv" name="rv" value="<?php echo $dataManager->getRelevanceValue(); ?>" />
				    <input type="submit" name="submitbutton" id="submitbutton" value="Display/Reset Species List"/>
				</div>
		  		<hr size="2" />
			  		
		  		<?php
				//echo "<div style=''>Relevance value: <input name='rv' type='text' size='3' title='Only characters with > ".($rv*100)."% relevance to the active spp. list will be displayed.' value='".$dataManager->getRelevanceValue()."'></div>";
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
			<td width="20" style="background-image:url(../images/layout/brown_hor_strip.gif);">&nbsp;</td>
			<td valign="top">
				<?php
				//List taxa by family/sci name
				if(($clid && $taxonValue) || $dynClid){
					?>
					<table border='0' width='300px'>
						<tr><td colspan='2'>
							<h2>
								<?php 
								if($floraModIsActive){
									echo "<a href='../checklists/checklist.php?cl=".$clid."&dynclid=".$dynClid."'>";
								}
								echo $dataManager->getClName()." ";
								if($floraModIsActive){
									echo "</a>";
								}
								?>
							</h2>
							<?php 
							if(!$dynClid) echo "<div>".$dataManager->getClAuthors()."</div>";
							?>
						</td></tr>
						<?php 
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
						?>
					</table>
					<?php 
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

