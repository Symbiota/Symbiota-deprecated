<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/taxa/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/CommonSearchManager.php');
Header("Content-Type: text/html; charset=".$CHARSET);
$commonValue = (isset($_REQUEST['common']) && $_REQUEST['common'] !='') ? $_REQUEST['common'] : '';

if(!empty($commonValue)){
    $commonManager = new CommonSearchManager($commonValue);
    $results = $commonManager->getDataArr();
}


?>

<html>
<head>
	<title><?php echo $DEFAULT_TITLE." - ".$spDisplay; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../css/speciesprofilebase.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/speciesprofile.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
<?php
include($SERVER_ROOT.'/header.php');
?>
<div class="inner-content">
    <h1>Search Results for "<?php echo htmlentities($commonValue); ?>"</h1>
    <h2><?php echo count($results); ?> results</h2>
    <div class="reultsBoxWrapper" id="reultsDiv" style="display: block;">
        <div class="searchResultTable">
            <?php foreach ($results as $result) { ?>
                <div class="searchresultgridcell">
                    <a href="../taxa/garden.php?taxon=<?php echo $result['tid'] ?>" target="_blank">
                        <img class="searchresultgridimage" src="<?php echo $result['url'] ?>" title="<?php echo htmlentities($result['sciname']) ?>" alt="<?php echo htmlentities($result['sciname']) ?> image">
                        <div class="searchresultgridsciname"><?php echo htmlentities($result['common']) ?></div>
                    </a>
                </div>
            <?php }?>
        </div>
    </div>



</div>
<?php
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>