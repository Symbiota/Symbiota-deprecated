<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$obsUid = array_key_exists('obsuid',$_REQUEST)?$_REQUEST['obsuid']:'';
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

if(!$symbUid) header('Location: ../../profile/index.php?refurl=../collections/cleaning/fieldstandardization.php?'.$_SERVER['QUERY_STRING']);

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($obsUid)) $obsUid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';


$cleanManager = new OccurrenceCleaner();
if($collid) $cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))
	|| ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations' && $obsUid !== 0){
	$obsUid = $symbUid;
	$cleanManager->setObsUid($obsUid);
}

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Field Standardization</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
	
	</script>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	if(!$dupArr) include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
		<b>Batch Field Cleaning Tools</b>
	</div>

	<!-- inner text -->
	<div id="innertext">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		} 
		echo '<h2>'.$collMap['collectionname'].' ('.$collMap['code'].')</h2>';
		if($isEditor){
			?>
			<div>
				Description...
			</div>
			<?php 
			if($action){
				
			}
			?>
			<fieldset style="padding:20px;">
				<legend><b>Country</b></legend>
				<div style="margin:5px">
					<select name="country_old">
						<option value="">Select Target Field</option>
						<option value="">--------------------------------</option>
						<?php 
						
						
						
						
						?>
					</select>
					<select name="country_old">
						<option value="">Select Target Value</option>
						<option value="">--------------------------------</option>
						<?php 
						
						
						
						
						?>
					</select>
				</div>
				<div style="margin:5px">
					<b>Replacement Value:</b> 
					<input name="country_new" type="text" value="" /> 
				</div>
			</fieldset>
			<?php 
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
<?php 	
if(!$dupArr){
	include($serverRoot.'/footer.php');
}
?>
</body>
</html>