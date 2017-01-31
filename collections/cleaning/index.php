<?php
include_once('../../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/index.php?'.$_SERVER['QUERY_STRING']);

//Sanitation
if(!is_numeric($collid)) $collid = 0;

$cleanManager = new OccurrenceCleaner();
if($collid) $cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$isEditor = 0; 
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])) || ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations'){
	$cleanManager->setObsUid($SYMB_UID);
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Occurrence Cleaner</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
		<b>Data Cleaning Module</b>
	</div>

	<!-- inner text -->
	<div id="innertext" style="background-color:white;">
		<?php 
		if($isEditor){
			echo '<h2>'.$collMap['collectionname'].' ('.$collMap['code'].')</h2>';
			?>
			<ul>
				<li><b>Duplicate Record Search</b></li>
				<ul>
					<?php
					if($collMap['colltype'] != 'General Observations'){
						?>
						<li>
							<a href="duplicatesearch.php?collid=<?php echo $collid; ?>&action=listdupscatalog">
								Search based on <b>Catalog Numbers</b>
							</a>
						</li>
						<li>
							<a href="duplicatesearch.php?collid=<?php echo $collid; ?>&action=listdupsothercatalog">
								Search based on <b>Other Catalog Numbers</b>
							</a>
						</li>
						<?php
					}
					?>
					<li>
						<a href="duplicatesearch.php?collid=<?php echo $collid; ?>&action=listdupsrecordedby">
							Search based on <b>Collector/Observer and numbers</b>
						</a>
					</li>
				</ul>
				<li><b><a href="politicalunits.php?collid=<?php echo $collid; ?>">Political Geography Cleaning Tools</a></b></li>
			</ul>
			<?php
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>