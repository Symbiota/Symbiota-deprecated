<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$start = array_key_exists("start",$_REQUEST)?$_REQUEST["start"]:'';
$limit = array_key_exists("limit",$_REQUEST)?$_REQUEST["limit"]:1000;

$collManager = new OccurrenceCollectionProfile();
if(!$collManager->setCollid($collId)) $collId = ''; 
$collData = array();
if($collId) $collData = $collManager->getCollectionData();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE." ".($collId?$collData["collectionname"]:"") ; ?> Collection Index</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<meta name="keywords" content="natural history collection,<?php echo ($collId?$collData["collectionname"]:""); ?>" />
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php">Home Page</a> &gt;&gt; 
		<?php 
		if($collId){
			echo '<a href="collprofiles.php?collid='.$collId.'">Collection Profile</a> &gt;&gt; ';
			if(is_numeric($start)){
				echo '<a href="collectionindex.php?collid='.$collId.'">';
			}
			else{
				echo '<b>';
			}
			echo $collData["collectionname"].' Index';
			if(is_numeric($start)){
				echo '</a> &gt;&gt; ';
			}
			else{
				echo '</b>';
			}
			if(is_numeric($start)){
				echo '<b>Records '.($start+1).' - '.($start+$limit).'</b>';
			}
		}
		else{
			echo '<b>Collection Index</b>';
		}
		?>
	</div>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if(!$collId){
			$collArr = $collManager->getCollectionList();
			foreach($collArr as $cid => $cArr){
				?>
				<div style="margin:10px;">
					<h2>
						<?php echo '<b>'.$cArr['collectionname'].'</b> '.$cArr['institutioncode'].($cArr['collectioncode']?':'.$cArr['collectioncode']:''); ?> 
					</h2>
					<div style="margin:2px 0px 2px 15px;">
						<b>Description:</b> <?php echo $cArr['fulldescription']; ?>
					</div>
					<div style="margin:2px 0px 2px 15px;">
						<b>Contact:</b> <?php echo $cArr['contact']; ?>
					</div>
					<div style="margin:2px 0px 2px 15px;">
						<b>Homepage:</b> <?php echo $cArr['homepage']; ?>
					</div>
 					<div style="margin:2px 0px 2px 15px;">
						<?php echo '<a href="collectionindex.php?collid='.$cid.'">Specimen List</a>'; ?>
					</div>
				</div>
				<?php 
			}
		}
		else{
			echo '<h2>'.$collData['collectionname'].' ('.$collData['institutioncode'].($collData['collectioncode']?$collData['collectioncode']:'').')</h2>';
			$collManager->echoOccurrenceListing($start,$limit);
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>