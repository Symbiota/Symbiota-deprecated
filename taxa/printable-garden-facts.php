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
if($links){
	foreach($links as $linkKey => $linkUrl){
		if($linkUrl['title'] == 'REDIRECT'){
			$locUrl = str_replace('--SCINAME--',rawurlencode($taxonManager->getSciName()),$linkUrl['url']);
			header('Location: '.$locUrl);
			exit;
		}
	}
}

$styleClass = '';
if($taxonRank > 180) $styleClass = 'species';
elseif($taxonRank == 180) $styleClass = 'genus';
else $styleClass = 'higher';

$displayLocality = 0;
$isEditor = false;
if($SYMB_UID){
	if($IS_ADMIN || array_key_exists("TaxonProfile",$USER_RIGHTS)){
		$isEditor = true;
	}
	if($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS) || array_key_exists("RareSppAdmin",$USER_RIGHTS) || array_key_exists("RareSppReadAll",$userRights)){
		$displayLocality = 1;
	}
}
if($taxonManager->getSecurityStatus() == 0){
	$displayLocality = 1;
}
$taxonManager->setDisplayLocality($displayLocality);
$descr = Array();

include('includes/config/taxaProfileElementsGarden.php');

//is page a garden page?
$isGardenProfile = $OSUManager ? $OSUManager->isGardenProfile() : false;
if(!$isGardenProfile) {
    header("Location:index.php?taxon=" . $taxonManager->getTid());
    exit();
}
$garden_image = $OSUManager->mainGardenImage();
$attribs = $OSUManager->getAllAttribs();


?>

<html>
<head>
	<title><?php echo $DEFAULT_TITLE." - ".$spDisplay; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../css/speciesprofilebase.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/speciesprofile.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
    <style>
        @media print {
            #printPageButton {
                display: none;
            }
        }
    </style>
</head>
<body>
<?php
$displayLeftMenu = false;
//include($SERVER_ROOT.'/header.php');
?>
<div class="garden-content-wrapper">
    <div class="garden-page-header">
        <a id="printPageButton" href="Javascript:;"  onclick="window.print()" class="btn light-purple-btn pull-right" target="_blank">Print</a>
        <h1><strong><em><?php echo $spDisplay; ?></em></strong><br>
        <?php echo $vernaculars; ?></h1>
    </div>
    <div class="garden-facts">
        <div class="garden-desc">
            <?php echo $garden_content; ?>
        </div>
        <h3>Plant Facts</h3>
        <p>
            <strong>Plant type: </strong><?php echo $attribs['type'] ?><br>
            <strong>Size at maturity: </strong><?php echo $attribs['min_height'] ?>-<?php echo $attribs['max_height'] ?>' high x <?php echo $attribs['min_width'] ?>-<?php echo $attribs['max_width'] ?>' wide<br>
            <strong>Flower color: </strong><?php echo $attribs['flower_color'] ?><br>
            <strong>Bloom time: </strong><?php echo $attribs['bloom_months'] ?><br>
            <strong>Light: </strong><?php echo $attribs['sunlight_string'] ?><br>
            <strong>Moisture: </strong><?php echo $attribs['moisture_string'] ?><br>
            <strong>Wildlife support: </strong><?php echo $attribs['wildlife_string'] ?><br>
        </p>
        <h3>Growth and Maintenance</h3>
        <p>
            <strong>Ease of cultivation: </strong><?php echo $attribs['ease_of_growth'] ?><br>
            <strong>Spreads vigorously: </strong><?php echo $attribs['spreads_vigorously'] ?><br>
            <strong>Landscape uses: </strong><?php echo $attribs['landscape_uses'] ?><br>
            <strong>Other cultivation factors: </strong><?php echo $attribs['other_cultivation_factors'] ?><br>
            <strong>Plant behavior: </strong><?php echo $attribs['plant_behavior'] ?><br>
            <strong>Propagation: </strong><?php echo $attribs['propogation'] ?><br>
        </p>
        <div class="garden-commercial">
            <h3>Commercial Availability</h3>
            <p>We are looking forward to presenting the business that sell this plant.  Contact us if you are interested in helping to develop this resource!</p>
        </div>
    </div>
    <div class="garden-collections">
            <img src="<?php echo $garden_image['url'] ?>" title="<?php echo $spDisplay; ?> image" alt="<?php echo $vernaculars; ?> image" />
            <div class="photographer"><?php echo $garden_image['photographer'] ?>&nbsp;&nbsp;<?php //echo $garden_image['image_type'] ?></div>
        <h4>Plant collections containing <?php echo $vernaculars; ?></h4>
        <div class="home-boxes">
            <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14796&pid=3" class="home-box image-box">
                <img src="<?php echo $clientRoot; ?>/images/layout/Meadowscape_sm.jpg" alt="Meadowscape">
                <h3>Meadowscape</h3>
                <div class="box-overlay">
                    <div class="centered">A sun-loving mix of flowering herbs, perennials, and grasses</div>
                </div>
            </a>
            <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14797" class="home-box image-box">
                <img src="<?php echo $clientRoot; ?>/images/layout/Pollinator_garden_sm.jpg" alt="Pollinator Garden">
                <h3>Pollinator Garden</h3>
                <div class="box-overlay">
                    <div class="centered">A focus on flowers that provide nectar or pollen to native bees and other insect pollinators</div>
                </div>
            </a>
            <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14798&pid=3" class="home-box image-box">
                <img src="<?php echo $clientRoot; ?>/images/layout/Rock_garden_sm.jpg" alt="Rock Garden">
                <h3>Rock Garden</h3>
                <div class="box-overlay">
                    <div class="centered">Plants that bring color and texture to rocky, well-drained landscapes</div>
                </div>
            </a>
            <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14799&pid=3" class="home-box image-box">
                <img src="<?php echo $clientRoot; ?>/images/layout/Water_features_garden_sm.jpg" alt="Rain Garden">
                <h3>Rain Garden</h3>
                <div class="box-overlay">
                    <div class="centered">Plants suited to the edge of a pond or stream, or moist soils nearby</div>
                </div>
            </a>
            <a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=14800&pid=3" class="home-box image-box">
                <img src="<?php echo $clientRoot; ?>/images/layout/Woodland_garden_sm.jpg" alt="Woodland Garden">
                <h3>Woodland Garden</h3>
                <div class="box-overlay">
                    <div class="centered">Understory ferns, herbs, and shrubs to complete a wooded planting</div>
                </div>
            </a>
        </div>
    </div>
</div>
<div class="metro-wrapper">
    <div class="inner-content">
        <hr />
        <div class="metro-col1"> </div>
        <div class="metro-col2">
            <div class="col-content">
                <p>Metro is a primary contributor to OregonFlora's Gardening with Native Plants and supports efforts to protect clean
                    air, water and habitat in greater portland.</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>