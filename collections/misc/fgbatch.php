<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceAPIManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$apiManager = new OccurrenceAPIManager();
$batchProcessData = array();
$imagesExist = $apiManager->checkImages($collId);

$isEditor = 0;		 
if($SYMB_UID){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
		$isEditor = 1;
	}
}

if($isEditor){
    if($action == 'Initiate Process'){
        $apiManager->initiateFGBatchProcess($collId);
    }
    if($action == 'Cancel Process'){
        $apiManager->cancelFGBatchProcess($collId);
    }
    if($action == 'Delete Results'){
        $apiManager->deleteFGBatchResults($collId);
    }
    $batchProcessData = $apiManager->checkFGBatchProcess($collId);
    $batchResults = $apiManager->checkFGBatchResults($collId);
}
?>
<html>
<head>
	<title><?php echo $collMetadata['collectionname']; ?> Collection Permissions</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_fgbatchMenu)?$collections_misc_fgbatchMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_misc_fgbatchCrumbs)){
		if($collections_misc_fgbatchCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
			echo $collections_misc_fgbatchCrumbs;
			echo "<b>FieldGuide Batch Processing</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt;&gt; 
			<a href='collprofiles.php?emode=1&collid=<?php echo $collId; ?>'>Collection Management</a> &gt;&gt; 
			<b>FieldGuide Batch Processing</b>
		</div>
		<?php 
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor && $imagesExist){
            ?>
            <h1>FieldGuide Batch Processing</h1>
            <div style="margin:10px;">
                Use this dialogue to initiate, cancel, or view the results of a FieldGuide Batch Image identification process.
            </div>
            <form action="fgbatch.php" method="post" style="" onsubmit="">
                <?php
                if(!$batchProcessData && !$batchResults){
                    ?>
                    <fieldset style="margin: 15px auto 15px auto;width:600px;padding:15px;">
                        <div style="float:left;">
                            <b>No FieldGuide Batch processes have been initiated.</b>
                        </div>
                        <div style="float:right;">
                            <input type="submit" name="action" value="Initiate Process" />
                        </div>
                    </fieldset>
                    <?php
                }
                elseif($batchProcessData){
                    ?>
                    <fieldset style="margin: 15px auto 15px auto;width:600px;padding:15px;">
                        <div style="float:left;">
                            <b>A Batch Process has been initiated but the results are still pending.</b>
                        </div>
                        <div style="float:right;">
                            <input type="image" src="../../images/del.png" name="action" value="Cancel Process" title="Cancel Process" style="width:15px;" />
                        </div>
                    </fieldset>
                    <?php
                }
                elseif($batchResults){
                    ?>
                    <fieldset style="margin: 15px auto 15px auto;width:600px;padding:15px;">
                        <div style="float:left;">
                            <b>The results of a FieldGuide Batch process have been returned. You can <a href='collprofiles.php?emode=1&collid=<?php echo $collId; ?>' target='_blank'>click here</a> to view the results.</b>
                        </div>
                        <div style="float:right;">
                            <input type="image" src="../../images/del.png" name="action" value="Delete Results" title="Delete Results" style="width:15px;" />
                        </div>
                    </fieldset>
                    <?php
                }
                ?>
                <input type="hidden" name="collid" value="<?php echo $collId; ?>">
            </form>
            <?php
		}
		elseif($isEditor && !$imagesExist){
            echo '<div style="font-weight:bold;font-size:120%;">';
            echo 'There are currently no linked image records for this collection.';
            echo '</div>';
        }
		else{
			echo '<div style="font-weight:bold;font-size:120%;">';
			echo 'Unauthorized to view this page. You must have administrative right for this collection.';
			echo '</div>';
		} 
		?>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>

</body>
</html>