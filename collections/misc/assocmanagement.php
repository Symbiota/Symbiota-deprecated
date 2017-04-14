<?php
require_once('../../config/symbini.php');
require_once($SERVER_ROOT.'/classes/OccurrenceAssociations.php');
header("Content-Type: text/html; charset=".$CHARSET);

//Use following ONLY if login is required
if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/misc/assocmanagement.php?'.$_SERVER['QUERY_STRING']);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;

$assocHandler = new OccurrenceAssociations();
$collmeta = array();
if($collid) $collmeta = $assocHandler->getCollectionMetadata($collid);

$isEditor = 0; 
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		//If a page related to collections, one maight want to... 
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
	}
}
?>
<html>
	<head>
		<title>Occurrence Association Batch Build</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">

		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js?ver=140310" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt;
			<?php 
			if($collid) echo '<a href="collprofiles.php?collid='.$collid.'&emode=1">Collection Management</a> &gt;&gt; ';
			?> 
			<b>Occurrence Association Manager</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php
			if($isEditor){ 
				if($formSubmit == 'Parse Associated Taxa'){
					$assocHandler->parseAssociatedTaxa($collid);
				}
				?>
				<fieldset style="margin:20px;padding:15px">
					<legend><b>Associated Taxa Parsing</b></legend>
					<form name="" action="assocmanagement.php" method="post">
						<div>
							<?php 
							$statArr = $assocHandler->getParsingStats($collid);
							echo '<div style="margin:10px 0px;font-weight:bold;font-size:120%;">';
							if($collmeta){
								echo $collmeta['collname'].' ('.$collmeta['instcode'].($collmeta['collcode']?'-'.$collmeta['instcode']:'').')';
							}
							else{
								echo 'All Collections';
							}
							echo '</div>';
							echo '<div style="margin:3px"><b>Number of parsed specimens:</b> '.$statArr['parsed'].'</div>';
							echo '<div style="margin:3px"><b>Number of unparsed specimens:</b> '.$statArr['unparsed'].'</div>';
							echo '<div style="margin:3px"><b>Number of non-indexed parsing terms:</b> '.$statArr['failed'].' (from '.$statArr['failedOccur'].' specimen records)'.'</div>';
							?>
						</div>
						<div style="margin:20px;">
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="formsubmit" type="submit" value="Parse Associated Taxa" />
						</div>
					</form>
				</fieldset>
				<?php
			}
			else{
				echo '<div style="font-weight:bold;font-size:130%;">ERROR: permissions failure</div>';
			}
			?>
		</div>
		<?php
		include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>