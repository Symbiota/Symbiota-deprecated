<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GlossaryManager.php');
header("Content-Type: text/html; charset=".$charset);

$glossId = array_key_exists('glossid',$_REQUEST)?$_REQUEST['glossid']:0;
$glossgrpId = array_key_exists('glossgrpid',$_REQUEST)?$_REQUEST['glossgrpid']:0;
$language = array_key_exists('searchlanguage',$_REQUEST)?$_REQUEST['searchlanguage']:'';
$keyword = array_key_exists('searchkeyword',$_REQUEST)?$_REQUEST['searchkeyword']:'';
$tId = array_key_exists('searchtaxa',$_REQUEST)?$_REQUEST['searchtaxa']:'';
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$isEditor = false;
if($isAdmin || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

$glosManager = new GlossaryManager();
$termList = array();
$sourceArr = array();
$taxonName = '';

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Edit Sources'){
		$statusStr = $glosManager->editSources($_POST);
	}
	if($formSubmit == 'Search Terms' || $formSubmit == 'Edit Sources'){
		$termList = $glosManager->getTermList($keyword,$language,$tId);
		if(isset($glossary_indexBanner)){
			$sourceArr = $glosManager->getTaxonSources($tId);
		}
	}
	if($formSubmit == 'Delete Term'){
		$statusStr = $glosManager->deleteTerm($_POST);
		$glossId = 0;
	}
}
?>
<html>
<head>
    <title><?php echo $defaultTitle; ?> Glossary</title>
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
    <link href="../css/main.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js?ver=130330"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#tabs').tabs({
				active: <?php echo (($imageArr || $taxaList)?'2':'0'); ?>,
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<p>Loading...</p>");
				}
			});
		});
	</script>
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
		if(isset($glossary_indexBanner)){
			if($glossary_indexBanner){
				echo $glossary_indexBanner;
			}
		}
		if($statusStr){
			?>
			<div style="margin:15px;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<?php 
		}
		if($isEditor){
			?>
			<div style="width:100%;margin-top:0px;margin-bottom:8px;">
				<div style="float:right;">
					<a href="#" onclick="toggle('newtermdiv');">
						<img src="../images/add.png" alt="Create New Term" />
					</a>
				</div>
				<div style="clear:both;"></div>
				<div id="newtermdiv" style="display:none;margin-bottom:10px;">
					<form name="termeditform" action="termdetails.php" method="post" onsubmit="return verifyNewTermForm(this.form);">
						<fieldset>
							<legend><b>Add New Term</b></legend>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Term: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="term" id="term" maxlength="150" style="width:350px;" value="" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Definition: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<textarea name="definition" id="definition" rows="10" maxlength="1000" style="width:450px;height:70px;resize:vertical;" ></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Language: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="language" id="language" maxlength="45" style="width:200px;" value="" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Author: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="author" id="author" maxlength="250" style="width:400px;" value="" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Translator: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="translator" id="translator" maxlength="250" style="width:400px;" value="" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;margin-top:12px;float:left;">
								Please enter the taxonomic group, higher than the rank of family, to which this term applies:
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Taxonomic Group: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="taxagroup" id="taxagroup" maxlength="45" style="width:250px;" value="" onchange="" title="" />
									<input name="tid" id="tid" type="hidden" value="" />
								</div>
							</div>
							<div style="clear:both;padding-top:8px;float:right;">
								<div style="float:right;">
									<button name="formsubmit" type="submit" value="Create Term">Create Term</button>
								</div>
								<div style="float:right;margin-right:10px;">
									<a href='glossaryloader.php'>Batch Upload Terms</a>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
			</div>
			<?php
		}
		?>
		<div id="tabs" style="margin:0px;margin-bottom:20px;padding:10px;">
			<form name="filtertermform" action="index.php" method="post" onsubmit="return verifySearchForm(this.form);">
				<input id="formaction" type="hidden" value="" />
				<div style="clear:both;height:15px;">
					<div style="float:right;">
						<button name="formsubmit" type="submit" value="Search Terms" onclick="changeFilterTermFormAction('index.php');">Browse Terms</button>
					</div>
					<div style="float:left;">
						<b>Language:</b>
						<select name="searchlanguage" id="searchlanguage" style="margin-top:2px;" onchange="">
							<option value="">Select Language</option>
							<option value="">----------------</option>
							<?php 
							$langArr = $glosManager->getLanguageArr();
							foreach($langArr as $k => $v){
								if($language){
									echo '<option value="'.$k.'" '.($k==$language?'SELECTED':'').'>'.$k.'</option>';
								}
								else{
									echo '<option value="'.$k.'" '.($k==$defaultLang?'SELECTED':'').'>'.$k.'</option>';
								}
							}
							?>
						</select>
					</div>
					<div style="float:left;margin-left:10px;">
						<b>Taxonomic Group:</b>
						<select name="searchtaxa" id="searchtaxa" style="margin-top:2px;width:300px;" onchange="">
							<option value="">Select Group</option>
							<option value="">----------------</option>
							<?php 
							$taxaArr = $glosManager->getTaxaGroupArr();
							foreach($taxaArr as $k => $v){
								if($tId){
									echo '<option value="'.$k.'" '.($k==$tId?'SELECTED':'').'>'.$v.'</option>';
									if($k==$tId){
										$taxonName = $v;
									}
								}
								else{
									echo '<option value="'.$k.'">'.$v.'</option>';
								}
							}
							?>
						</select>
					</div>
				</div>
				<div style="clear:both;height:15px;margin-top:15px;">
					<div style="float:left;">
						<b>Search for Specific Term Keyword:</b> 
						<input type="text" autocomplete="off" name="searchkeyword" id="searchkeyword" size="25" value="<?php echo ($formSubmit == 'Search Terms'?$_POST['searchkeyword']:''); ?>" />
					</div>
					<div style="float:right;">
						<div style="" onclick="toggle('downloadoptionsdiv');return false;">
							<a href="#" title="Show download options">
								Download Options
							</a>
						</div>
					</div>
					<div id="downloadoptionsdiv" style="float:left;display:none;margin-top:15px;">
						<fieldset style="padding:8px">
							<legend><b>Download Options</b></legend>
							<div style="clear:both;float:left;margin-bottom:8px;">
								Primary language of download will be language selected above.
							</div>
							<div style="clear:both;float:left;margin-bottom:8px;">
								<div style="clear:both;">
									<input name="exporttype" id="exporttype" type="radio" value="singlelanguage" checked /> Single Language
								</div>
								<div style="float:left;clear:both;margin-left:25px;">
									<input name="images" type="checkbox" value="images" /> Include Images
								</div>
							</div>
							<div style="clear:left;float:left;">
								<div style="clear:both;">
									<input name="exporttype" id="exporttype" type="radio" value="translation" /> Translation Table
								</div>
								<div style="float:left;margin-left:25px;">
									<b>Translations</b><br />
									<?php
									foreach($langArr as $k => $v){
										echo '<input name="language[]" type="checkbox" value="'.$k.'" /> '.$k.'<br />';
									}
									?>
								</div>
								<div style="float:left;margin-left:15px;padding-top:1.1em;">
									<input name="definitions" type="radio" value="nodef" checked /> Without Definitions<br />
									<input name="definitions" type="radio" value="onedef" /> Include Primary Definition Only<br />
									<input name="definitions" type="radio" value="alldef" /> Include All Definitions
								</div>
							</div>
							<div style="clear:both;float:right;">
								<button name="formsubmit" type="submit" value="Download" onclick="changeFilterTermFormAction('glossdocexport.php');">Download</button>
							</div>
						</fieldset>
					</div>
				</div>
			</form>
			<div style="clear:both;"></div>
		</div>
		<div id="termlistdiv" style="min-height:200px;">
			<?php
			if($termList){
				if(isset($glossary_indexBanner)){
					if($sourceArr){
						?>
						<div id="sourcetoggle" style="float:right;clear:both;">
							<div style="" onclick="toggle('sourcesdiv');return false;">
								<a href="#">Show Sources</a>
							</div>
						</div>
						<div id="sourcesdiv" style="display:none;margin-bottom:15px;">
							<?php
							if($sourceArr['contributorTerm']){
								?>
								<div style="">
									<b>Terms and Definitions contributed by:</b> <?php echo $sourceArr['contributorTerm']; ?>
								</div>
								<?php
							}
							if($sourceArr['contributorImage']){
								?>
								<div style="margin-top:8px;">
									<b>Images contributed by:</b> <?php echo $sourceArr['contributorImage']; ?>
								</div>
								<?php
							}
							if($sourceArr['translator']){
								?>
								<div style="margin-top:8px;">
									<b>Translations by:</b> <?php echo $sourceArr['translator']; ?>
								</div>
								<?php
							}
							if($sourceArr['additionalSources']){
								?>
								<div style="margin-top:8px;">
									<b>Translations and images were also sourced from the following references:</b> <?php echo $sourceArr['additionalSources']; ?>
								</div>
								<?php
							}
							if($isEditor){
								?>
								<div style="float:right;">
									<a href="sources.php?tid=<?php echo $tId; ?>&keyword=<?php echo $keyword; ?>&language=<?php echo $language; ?>&taxa=<?php echo $tId; ?>">Edit Sources</a>
								</div>
								<div style="clear:both;"></div>
								<?php
							}
							?>
						</div>
						<?php
					}
					else{
						if($isEditor){
							?>
							<div style="float:right;">
								<a href="sources.php?tid=<?php echo $tId; ?>&keyword=<?php echo $keyword; ?>&language=<?php echo $language; ?>&taxa=<?php echo $tId; ?>">Add Sources</a>
							</div>
							<?php
						}
					}
				}
				$title = 'Terms for '.$taxonName.' in '.$language;
				if($keyword){
					$title .= ' and with a keyword of '.$keyword;
				}
				echo '<div style="font-weight:bold;font-size:120%;">'.$title.'</div>';
				echo '<div><ul>';
				foreach($termList as $termId => $terArr){
					echo '<li>';
					echo '<a href="#" onclick="openTermPopup('.$termId.','.$tId.'); return false;"><b>'.$terArr["term"].'</b></a>';
					echo '</li>';
				}
				echo '</ul></div>';
			}
			elseif($formSubmit == 'Search Terms'){
				echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are no terms matching your criteria. If you would like to contribute terms or translations please <a href="mailto:'.$adminEmail.'" target="_top">contact us</a>.</div></div>';
			}
			else{
				echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">Enter search criteria above to see terms.</div></div>';
			}
			?>
		</div>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>