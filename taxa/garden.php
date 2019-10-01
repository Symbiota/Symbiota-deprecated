<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/taxa/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/TaxonProfileManager.php');
Header("Content-Type: text/html; charset=".$CHARSET);

$taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
$taxAuthId = array_key_exists("taxauthid",$_REQUEST)?$_REQUEST["taxauthid"]:1; 
$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0;
$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:0;
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$DEFAULT_LANG;
$descrDisplayLevel = array_key_exists("displaylevel",$_REQUEST)?$_REQUEST["displaylevel"]:"";

//if(!$projValue && !$clValue) $projValue = $defaultProjId;

$taxonManager = new TaxonProfileManager();
if($taxAuthId || $taxAuthId === "0") $taxonManager->setTaxAuthId($taxAuthId);
if($clValue) $taxonManager->setClName($clValue);
if($projValue) $taxonManager->setProj($projValue);
if($lang) $taxonManager->setLanguage($lang);
if($taxonValue) {
	$taxonManager->setTaxon($taxonValue);
	$taxonManager->setAttributes();
}
$ambiguous = $taxonManager->getAmbSyn();
$acceptedName = $taxonManager->getAcceptance();
$synonymArr = $taxonManager->getSynonymArr();
$spDisplay = $taxonManager->getDisplayName();
$taxonRank = $taxonManager->getRankId();
$links = $taxonManager->getTaxaLinks();
$vernaculars =@implode(", ",$taxonManager->getVernaculars());
$vernStr = $taxonManager->getVernacularStr();
$synStr = $taxonManager->getSynonymStr();
?>

<html lang="en">
  <head>
    <title><?php echo $DEFAULT_TITLE." - ".$spDisplay; ?></title>
    <meta charset="utf-8">
    <link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <link href="../css/gardenTaxa.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <script type="text/javascript">
      <?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
    </script>
  </head>
  <body>
    <?php
    $displayLeftMenu = false;
    include($SERVER_ROOT.'/header.php');
    ?>

    <!-- React app container -->
    <div id="react-garden-taxa" data-props=""></div>

    <!-- Set the attributes -->
    <script>
      const props = {
        commonName: "<?php echo $spDisplay ?>",
        sciName: "<?php echo $taxonManager->getSciName() ?>"
      };
      document.getElementById("react-garden-taxa").setAttribute("data-props", JSON.stringify(props));
    </script>

    <!-- Start React -->
    <script src="<?php echo $GLOBALS['CLIENT_ROOT'] ?>/js/react/dist/gardenTaxa.js" type="text/javascript"></script>


  </body>
</html>