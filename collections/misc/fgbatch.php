<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/FieldGuideManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$taxon = array_key_exists("taxon",$_POST)?$_POST["taxon"]:'';
$jobId = array_key_exists("jobid",$_POST)?$_POST["jobid"]:0;

$apiManager = new FieldGuideManager();
$currentJobs = array();
$currentResults = array();
$currentCount = 0;
$statusStr = '';
$imagesExist = $apiManager->checkImages($collId);

$isEditor = 0;		 
if($SYMB_UID){
	if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
		$isEditor = 1;
	}
}

if($isEditor){
    if($action == 'Initiate Process'){
        $apiManager->setCollID($collId);
        $apiManager->setTaxon($taxon);
        $statusStr = $apiManager->initiateFGBatchProcess();
    }
    if($action == 'Cancel Job'){
        $statusStr = $apiManager->cancelFGBatchProcess($collId,$jobId);
    }
    if($action == 'Delete Results'){
        $statusStr = $apiManager->deleteFGBatchResults($collId,$jobId);
    }
    $logData = $apiManager->checkFGLog($collId);
    if(isset($logData['jobs'])) $currentJobs = $logData['jobs'];
    if(isset($logData['results'])) $currentResults = $logData['results'];
    $currentCount = count($currentJobs);
}
?>
<html>
<head>
	<title><?php echo $collMetadata['collectionname']; ?> Fieldguide Batch Processing Utility</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <link rel="stylesheet" href="../../css/jquery-ui.css" type="text/css" />
    <script type="text/javascript" src="../../js/jquery.js"></script>
    <script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            function split( val ) {
                return val.split( /,\s*/ );
            }
            function extractLast( term ) {
                return split( term ).pop();
            }

            if(document.getElementById("taxon")){
                $( "#taxon" )
                // don't navigate away from the field on tab when selecting an item
                    .bind( "keydown", function( event ) {
                        if ( event.keyCode === $.ui.keyCode.TAB &&
                            $( this ).data( "autocomplete" ).menu.active ) {
                            event.preventDefault();
                        }
                    })
                    .autocomplete({
                        source: function( request, response ) {
                            $.getJSON( "rpc/speciessuggest.php", {
                                term: extractLast( request.term )
                            }, response );
                        },
                        search: function() {
                            var term = extractLast( this.value );
                            if ( term.length < 4 ) {
                                return false;
                            }
                        },
                        focus: function() {
                            return false;
                        },
                        select: function( event, ui ) {
                            var terms = split( this.value );
                            terms.pop();
                            terms.push( ui.item.value );
                            this.value = terms.join( ", " );
                            return false;
                        },
                        change: function (event, ui) {
                            if (!ui.item) {
                                this.value = '';
                            }
                        }
                    },
                {});
            }
        });
    </script>
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
			echo "<b>Fieldguide Batch Processing</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt;&gt; 
			<a href='collprofiles.php?emode=1&collid=<?php echo $collId; ?>'>Collection Management</a> &gt;&gt; 
			<b>Fieldguide Batch Processing</b>
		</div>
		<?php 
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
        if($statusStr){
            ?>
            <hr/>
            <div style="margin:15px;color:red;">
                <?php echo $statusStr; ?>
            </div>
            <hr/>
            <?php
        }
        if($isEditor && $imagesExist){
            ?>
            <h1>Fieldguide Batch Processing</h1>
            <div style="margin:10px;">
                Use this dialogue to initiate, cancel, or view the results of a Fieldguide Batch Image identification process. Either type a parent taxon into the Parent
                Taxon box to initiate a batch image identification process for a particular taxonomic group, or leave the Parent Taxon box empty and click Initiate Process
                to intiate a batch image identification process for your whole collection. Processes that are currently being identified by Fieldguide will show up in the
                Current Jobs box. Once results are received from Fieldguide, the job will be moved to the Current Results box and you will be able to review the results.
            </div>
            <?php
            if($currentCount < 20){
                ?>
                <form action="fgbatch.php" method="post" style="" onsubmit="">
                    <fieldset style="margin: 15px auto 15px auto;width:600px;padding:15px;">
                        <div style="float:left;">
                            Parent Taxon: <input type="text" id="taxon" size="43" name="taxon" value="" />
                        </div>
                        <div style="float:right;">
                            <input type="submit" name="action" value="Initiate Process" />
                            <input type="hidden" name="collid" value="<?php echo $collId; ?>">
                        </div>
                    </fieldset>
                </form>
                <?php
            }
            if($currentJobs){
                ?>
                <div style="width:650px;margin-left:auto;margin-right:auto;">
                    <h2>Current Jobs:</h2>
                    <table class="styledtable" style="font-family:Arial;font-size:12px;width:570px;margin-left:auto;margin-right:auto;">
                        <tr>
                            <th style="width:100px;">Date Initiated</th>
                            <th style="width:250px;">Parent Taxon</th>
                            <th style="width:20px;">Cancel</th>
                        </tr>
                        <?php
                        foreach($currentJobs as $job => $jArr){
                            echo '<tr>';
                            echo '<td>'.$jArr['date'].'</td>';
                            echo '<td>'.$jArr['taxon'].'</td>';
                            echo '<td>';
                            echo '<form action="fgbatch.php" method="post" style="" onsubmit="">';
                            echo '<input type="image" src="../../images/del.png" name="action" value="Cancel Job" title="Cancel Job" style="width:15px;" />';
                            echo '<input type="hidden" name="collid" value="'.$collId.'">';
                            echo '<input type="hidden" name="jobid" value="'.$job.'">';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </table>
                </div>
                <?php
            }
            if($currentResults){
                ?>
                <div style="width:650px;margin-left:auto;margin-right:auto;">
                    <h2>Current Results:</h2>
                    <table class="styledtable" style="font-family:Arial;font-size:12px;width:570px;margin-left:auto;margin-right:auto;">
                        <tr>
                            <th style="width:100px;"> </th>
                            <th style="width:100px;">Date Initiated</th>
                            <th style="width:100px;">Date Received</th>
                            <th style="width:250px;">Parent Taxon</th>
                            <th style="width:20px;">Delete</th>
                        </tr>
                        <?php
                        foreach($currentResults as $job => $jArr){
                            echo '<tr>';
                            echo '<td><a href="fgresults.php?collid='.$collId.'&resid='.$job.'">View Results</a></td>';
                            echo '<td>'.$jArr['inidate'].'</td>';
                            echo '<td>'.$jArr['recdate'].'</td>';
                            echo '<td>'.$jArr['taxon'].'</td>';
                            echo '<td>';
                            echo '<form action="fgbatch.php" method="post" style="" onsubmit="">';
                            echo '<input type="image" src="../../images/del.png" name="action" value="Delete Results" title="Delete Results" style="width:15px;" />';
                            echo '<input type="hidden" name="collid" value="'.$collId.'">';
                            echo '<input type="hidden" name="jobid" value="'.$job.'">';
                            echo '</form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </table>
                </div>
                <?php
            }
            ?>
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