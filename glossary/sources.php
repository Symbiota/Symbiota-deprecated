<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GlossaryManager.php');
header("Content-Type: text/html; charset=".$charset);

$tId = array_key_exists('tid',$_REQUEST)?$_REQUEST['tid']:'';
$keyword = array_key_exists('keyword',$_REQUEST)?$_REQUEST['keyword']:'';
$language = array_key_exists('language',$_REQUEST)?$_REQUEST['language']:'';
$taxa = array_key_exists('taxa',$_REQUEST)?$_REQUEST['taxa']:'';

$isEditor = false;
if($isAdmin || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

$glosManager = new GlossaryManager();
$sourceArr = array();

if($tId){
	$sourceArr = $glosManager->getTaxonSources($tId);
}
else{
	header("Location: index.php");
}
?>
<html>
<head>
    <title><?php echo $defaultTitle; ?> Glossary Sources Management</title>
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
    <link href="../css/main.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($glossary_indexMenu)?$glossary_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($glossary_indexCrumbs)){
		if($glossary_indexCrumbs){
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt;&gt; 
				<?php echo $glossary_indexCrumbs; ?>
				<a href='index.php'> <b>Glossary Management</b></a>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt; 
			<a href='index.php'> <b>Glossary Management</b></a>
		</div>
		<?php 
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($isEditor){
			?>
			<div id="sourcedetaildiv" style="">
				<div id="termdetails" style="overflow:auto;">
					<form name="sourceeditform" id="sourceeditform" action="index.php" method="post">
						<div style="clear:both;padding-top:4px;float:left;">
							<div style="float:left;">
								<b>Terms and Definitions contributed by: </b>
							</div>
							<div style="float:left;clear:both;">
								<textarea name="contributorTerm" id="contributorTerm" rows="10" maxlength="1000" style="width:500px;height:40px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr['contributorTerm']:''); ?></textarea>
							</div>
						</div>
						<div style="clear:both;padding-top:4px;float:left;">
							<div style="float:left;">
								<b>Images contributed by: </b>
							</div>
							<div style="float:left;clear:both;">
								<textarea name="contributorImage" id="contributorImage" rows="10" maxlength="1000" style="width:500px;height:40px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr['contributorImage']:''); ?></textarea>
							</div>
						</div>
						<div style="clear:both;padding-top:4px;float:left;">
							<div style="float:left;">
								<b>Translations by: </b>
							</div>
							<div style="float:left;clear:both;">
								<textarea name="translator" id="translator" rows="10" maxlength="1000" style="width:500px;height:40px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr['translator']:''); ?></textarea>
							</div>
						</div>
						<div style="clear:both;padding-top:4px;float:left;">
							<div style="float:left;">
								<b>Translations and images were also sourced from the following references: </b>
							</div>
							<div style="float:left;clear:both;">
								<textarea name="additionalSources" id="additionalSources" rows="10" maxlength="1000" style="width:500px;height:150px;resize:vertical;" ><?php echo ($sourceArr?$sourceArr['additionalSources']:''); ?></textarea>
							</div>
						</div>
						<div style="clear:both;padding-top:8px;float:right;">
							<input name="tid" type="hidden" value="<?php echo $tId; ?>" />
							<input name="searchkeyword" type="hidden" value="<?php echo $keyword; ?>" />
							<input name="searchlanguage" type="hidden" value="<?php echo $language; ?>" />
							<input name="searchtaxa" type="hidden" value="<?php echo $taxa; ?>" />
							<input name="newsources" type="hidden" value="<?php echo ($sourceArr?0:1); ?>" />
							<button name="formsubmit" type="submit" value="Edit Sources">Save Edits</button>
						</div>
					</form>
				</div>
			</div>
			<?php 
		}
		else{
			if(!$symbUid){
				header("Location: ../profile/index.php?refurl=../glossary/sources.php?tid=".$tId);
			}
			else{
				echo '<h2>You do not have permissions to edit glossary data, please contact system administrator</h2>';
			}
		}
		?>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>