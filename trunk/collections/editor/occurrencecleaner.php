<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$cleanManager = new OccurrenceCleaner();
if($collId) $cleanManager->setCollId($collId);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditor = 1;
}
if($isEditor){
	if($action == ''){
		
	}
	
}


?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Editor</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script type="text/javascript">

	</script>
</head>
<body>
	<?php 	
	include($serverRoot.'/header.php');
	?>
	<!-- inner text -->
	<div id="innertext">
		<?php 
		if($symbUid && $collId && $isEditor){
			
			//Look for duplicate catalognumbers 
			SELECT o.* 
FROM omoccurrences o INNER JOIN (SELECT catalognumber
FROM omoccurrences 
GROUP BY catalognumber, collid 
HAVING Count(*)>1 AND collid = 1 AND catalognumber IS NOT NULL) rt ON o.catalognumber = rt.catalognumber
WHERE o.collid = 1;
			
			
			//Look for duplicate occurrenceids

			//Look for bad taxonomic names
			
			
			
		}
		else{
			if(!$symbUid){
				?>
				<div style="font-weight:bold;font-size:120%;margin:30px;">
					Please 
					<a href="../../profile/index.php?refurl=<?php echo $clientRoot.'/collections/editor/occurrenceeditor.php&collid='.$collId; ?>">
						LOGIN
					</a> 
				</div>
				<?php 
			}
			elseif(!$collId){
				
			}
			elseif(!$isEditor){
				echo '<h2>You are not authorized to add occurrence records</h2>';
				
			}
		}
		?>
	</div>
<?php 	
include($serverRoot.'/footer.php');
?>

</body>
</html>