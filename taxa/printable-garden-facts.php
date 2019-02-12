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
$collections = $OSUManager->gardenCollections();


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
            @page { margin: 0; }
            body { margin: 0.8cm; }
        }
        .garden-facts, .garden-collections {
            width: 100%;
            float: none;
        }
        .garden-desc {
            margin-top: 0;
        }
        .printable-logo {
            text-align: right;
            margin-bottom: 20px;
        }

        .col-wrapper {
            overflow: hidden;
            clear: both;
        }
        .col1 {
            float: left;
            width: 47%;
        }
        .col2 {
            float: right;
            width: 47%;
        }
        .garden-content-wrapper {
            padding-top: 0;
            padding-bottom: 0;
        }
    </style>
</head>
<body class="printable">
<?php
$displayLeftMenu = false;
//include($SERVER_ROOT.'/header.php');
?>
<div class="garden-content-wrapper">
        <a id="printPageButton" href="Javascript:;"  onclick="window.print()" class="btn light-purple-btn pull-right" target="_blank">Print</a>
    <div class="garden-print-col-wrapper">
        <h1><strong><em><?php echo $spDisplay; ?></em></strong><br>
            <?php echo $vernaculars; ?></h1>
        <div class="col-wrapper">
            <div class="col1">
                <div class="garden-desc">
                    <?php echo $garden_content; ?>
                    <h3>Plant Facts</h3>
                    <p>
                        <?php if($attribs['type'] != '') { ?>
                            <strong>Plant type: </strong><?php echo strtolower($attribs['type']) ?><br>
                        <?php } ?>
                        <strong>Size at maturity: </strong>
                        <?php $height_string = ''; $width_string = ''; $joiner = ''; ?>
                        <?php if(!isset($attribs['min_height']) || $attribs['min_height'] == 0 || $attribs['min_height'] == $attribs['max_height']) {
                            if(isset($attribs['max_height']) && $attribs['max_height'] !=''){
                                $height_string .= $attribs['max_height'] . '&apos; high';
                            }
                        }else{
                            $height_string .=  $attribs['min_height'];
                            if(isset($attribs['max_height']) && $attribs['max_height'] !=''){
                                $height_string .=  '-' . $attribs['max_height'];
                            }
                            $height_string .=  '&apos; high';
                        } ?>
                        <?php if(!isset($attribs['min_width']) || $attribs['min_width'] == 0 || $attribs['min_width'] == $attribs['max_width']) {
                            if(isset($attribs['max_width']) && $attribs['max_width'] !=''){
                                $width_string .= $attribs['max_width'] . '&apos; wide';
                            }
                        }else{
                            $width_string .=  $attribs['min_width'];
                            if(isset($attribs['max_width']) && $attribs['max_width'] !=''){
                                $width_string .=  '-' . $attribs['max_width'];
                            }
                            $width_string .=  '&apos; wide';
                        }
                        if($width_string !='' && $height_string !=''){ $joiner = ' x ';}
                        $height_width_string = $height_string . $joiner . $width_string;
                        ?>

                        <?php echo $height_width_string; ?><br>
                        <?php if($attribs['flower_color'] != '') { ?>
                            <strong>Flower color: </strong><?php echo $attribs['flower_color'] ?><br>
                        <?php } ?>
                        <?php if($attribs['bloom_months'] != '') { ?>
                            <strong>Bloom time: </strong><?php echo $attribs['bloom_months'] ?><br>
                        <?php } ?>
                        <?php if($attribs['sunlight_string'] != '') { ?>
                            <strong>Light: </strong><?php echo $attribs['sunlight_string'] ?><br>
                        <?php } ?>
                        <?php if( $attribs['moisture_string'] != '' || $attribs['watering_string'] != '' ){ ?>
                            <strong>Moisture: </strong><?php echo $attribs['moisture_string'] ?>
                            <?php if( $attribs['watering_string'] != '' ){ ?>
                                <?php echo $attribs['moisture_string'] != '' ? ';' : '' ?> <?php echo $attribs['watering_string'] ?> summer water
                            <?php } ?>
                            <br>
                        <?php } ?>
                        <?php if(isset($attribs['wildlife_string']) && $attribs['wildlife_string'] != ''){ ?>
                            <strong>Wildlife support: </strong><?php echo $attribs['wildlife_string'] ?><br>
                        <?php } ?>
                    </p>
                </div>
                <h3>Growth and Maintenance</h3>
                <p>
                    <?php if($attribs['ease_of_growth'] != '') { ?>
                        <strong>Ease of cultivation: </strong><?php echo $attribs['ease_of_growth'] ?><br>
                    <?php } ?>
                    <?php if($attribs['spreads_vigorously'] != '') { ?>
                        <strong>Spreads vigorously: </strong><?php echo $attribs['spreads_vigorously'] ?><br>
                    <?php } ?>
                    <?php if($attribs['landscape_uses'] != '') { ?>
                    <strong>Landscape uses: </strong><?php echo $attribs['landscape_uses'] ?><br>
                    <?php } ?>
                    <?php if($attribs['other_cultivation_factors'] != '') { ?>
                        <strong>Other cultivation factors: </strong><?php echo $attribs['other_cultivation_factors'] ?><br>
                    <?php }?>
                    <?php if($attribs['plant_behavior'] != '') { ?>
                        <strong>Plant behavior: </strong><?php echo $attribs['plant_behavior'] ?><br>
                    <?php } ?>
                    <?php if($attribs['propogation'] != '') { ?>
                        <strong>Propagation: </strong><?php echo $attribs['propogation'] ?><br>
                    <?php } ?>
                </p>
                <div class="garden-commercial">
                    <h3>Commercial Availability</h3>
                    <p>We are looking forward to presenting the businesses that sell this plant.  Contact us if you are interested in helping to develop this resource!</p>
                </div>
            </div>
            <div class="col2">
                <div class="garden-facts">
                    <div class="garden-photo">
                        <img style="display: block;width: 86px;margin: 0 auto;margin-bottom: 20px;" src="<?php echo $clientRoot; ?>/images/layout/new-logo.png" alt="Oregon Flora">
                        <img src="<?php echo $garden_image['url'] ?>" title="<?php echo $spDisplay; ?> image" alt="<?php echo $vernaculars; ?> image" />
                        <div class="photographer"><?php echo $garden_image['photographer'] ?>&nbsp;&nbsp;<?php //echo $garden_image['image_type'] ?></div>
                    </div>
                </div>
                <?php if(is_array($collections)) { ?>
                    <h4>Plant collections containing <?php echo $vernaculars; ?></h4>
                    <p>
                        <?php foreach ($collections as $collection) { ?>
                            <strong><?php echo $collection['name'] ?></strong> -
                            <?php echo $collection['title'] ?><br>
                        <?php }?>
                    </p>
                <?php } //end if collections ?>
            </div>
        </div>
        <div style="font-size: 12px;">
            <hr />
            <p><img src="<?php echo $clientRoot; ?>/images/metro_logo.png" style="vertical-align: bottom;width: 60px;margin-right: 10px;margin-bottom: 20px;float: left;"> Metro is a primary contributor to OregonFlora's Gardening with Native Plants and supports efforts to protect clean
                air, water and habitat in greater Portland.</p>
        </div>
    </div>
</div>
</body>
</html>