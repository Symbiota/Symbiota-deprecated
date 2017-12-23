<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1;
$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$dbArr = array_key_exists('db', $_REQUEST)?$_REQUEST["db"]:array();
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:'';

if(!is_numeric($catId)) $catId = 0;

$imgLibManager = new ImageLibraryManager();

$collList = $imgLibManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);

$imageArr = Array();
if($action == 'search'){
	$imageArr = $imgLibManager->getImageArr($pageNumber,$cntPerPage);
	$recordCnt = $imgLibManager->getRecordCnt();
}
?>
<html>
<head>
<title><?php echo $DEFAULT_TITLE; ?> Image Library</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../js/jquery-ui-1.12.1/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<script src="../js/jquery-3.2.1.min.js" type="text/javascript"></script>
	<script src="../js/jquery-ui-1.12.1/jquery-ui.js" type="text/javascript"></script>
	<script src="../js/symb/collections.index.js?ver=2" type="text/javascript"></script>
	<script src="../js/symb/imagelib.search.js?ver=20170711" type="text/javascript"></script>
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#tabs').tabs({
				active: <?php echo (($imageArr)?'2':'0'); ?>,
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<p>Loading...</p>");
				}
			});
		});
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($imagelib_indexMenu)?$imagelib_indexMenu:"true");
	include($SERVER_ROOT.'/header.php');
	if(isset($imagelib_indexCrumbs)){
		echo "<div class='navpath'>";
		echo $imagelib_indexCrumbs;
		echo " <b>Image Search</b>";
		echo "</div>";
	}
	else{
		echo '<div class="navpath">';
		echo '<a href="../index.php">Home</a> &gt;&gt; ';
		echo '<a href="contributors.php">Image Contributors</a> &gt;&gt; ';
		echo '<b>Image Search</b>';
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<div id="tabs" style="margin:0px;">
			<ul>
				<li><a href="#criteriadiv">Search Criteria</a></li>
				<li><a href="#collectiondiv">Collections</a></li>
				<?php
				if($imageArr){
					?>
					<li><a href="#imagesdiv"><span id="imagetab">Images</span></a></li>
					<?php
				}
				?>
			</ul>

			<form name="imagesearchform" id="imagesearchform" action="search.php" method="get" onsubmit="return submitImageForm();">
				<div id="criteriadiv">
					<div style="clear:both;padding:5px 0px;">
						<div style="float:left;">
							<select id="taxontype" name="taxontype">
								<option id='sciname' value='2' <?php if(array_key_exists("taxontype",$_REQUEST) && $_REQUEST["taxontype"] == "2") echo "SELECTED"; ?> >Scientific Name</option>
								<option id='commonname' value='3' <?php if(array_key_exists("taxontype",$_REQUEST) && $_REQUEST["taxontype"] == "3") echo "SELECTED"; ?> >Common Name</option>
							</select>
						</div>
						<div id="taxabox" style="float:left;margin-bottom:10px;">
							<input id="taxa" name="taxa" type="text" style="width:450px;" value="" title="Separate multiple names w/ commas" autocomplete="off" />
						</div>
						<div id="thesdiv" style="float:left;margin-left:10px;display:<?php echo ((array_key_exists("taxontype",$_REQUEST) && $_REQUEST["taxontype"] == "3")?'none':'block'); ?>;" >
							<input type='checkbox' name='usethes' value='1' <?php if(!$action || (array_key_exists("usethes",$_REQUEST) && $_REQUEST["usethes"])) echo "CHECKED"; ?> >Include Synonyms
						</div>
					</div>
					<div style="clear:both;padding:5px 0px;">
						<div style="float:left;margin-right:8px;">
							Photographers:
						</div>
						<div style="float:left;">
							<input type="text" id="photographer" style="width:450px;" name="photographer" value="" title="Separate multiple photographers w/ commas" />
						</div>
					</div>
					<?php
					if($tagArr = $imgLibManager->getTagArr()){
						?>
						<div style="clear:both;padding:5px 0px;">
							Image Tags:
							<select id="tags" style="width:350px;" name="tags" >
								<option value="">Select Tag</option>
								<option value="">--------------</option>
								<?php
								foreach($tagArr as $k){
									echo '<option value="'.$k.'" '.((array_key_exists("tags",$_REQUEST))&&($_REQUEST["tags"]==$k)?'SELECTED ':'').'>'.$k.'</option>';
								}
								?>
							</select>
						</div>
						<?php
					}
					?>
					<div style="clear:both;padding:5px 0px;">
						<div style="float:left;margin-right:8px;">
							Image Keywords:
						</div>
						<div style="float:left;">
							<input type="text" id="keywords" style="width:350px;" name="keywords" value="" title="Separate multiple keywords w/ commas" />
						</div>
					</div>
					<div style="clear:both;padding:5px 0px;">
						Limit Image Counts:
						<select id="imagecount" name="imagecount">
							<option value="all" <?php echo ((array_key_exists("imagecount",$_REQUEST))&&($_REQUEST["imagecount"]=='all')?'SELECTED ':''); ?>>All images</option>
							<option value="taxon" <?php echo ((array_key_exists("imagecount",$_REQUEST))&&($_REQUEST["imagecount"]=='taxon')?'SELECTED ':''); ?>>One per taxon</option>
							<option value="specimen" <?php echo ((array_key_exists("imagecount",$_REQUEST))&&($_REQUEST["imagecount"]=='specimen')?'SELECTED ':''); ?>>One per specimen</option>
						</select>
					</div>
					<div style="clear:both;padding:5px 0px;">
						<fieldset style="width:350px;padding-bottom:10px">
							<legend>Limit Image Type</legend>
							<div style="margin-top:5px;">
								<input type='radio' name='imagetype' value='all' <?php if((!array_key_exists("imagetype",$_REQUEST)) || (array_key_exists("imagetype",$_REQUEST) && $_REQUEST["imagetype"] == 'all')) echo "CHECKED"; ?> > All Images
							</div>
							<div style="margin-top:5px;">
								<input type='radio' name='imagetype' value='specimenonly' <?php if(array_key_exists("imagetype",$_REQUEST) && $_REQUEST["imagetype"] == 'specimenonly') echo "CHECKED"; ?> > Specimen Images
							</div>
							<div style="margin-top:5px;">
								<input type='radio' name='imagetype' value='observationonly' <?php if(array_key_exists("imagetype",$_REQUEST) && $_REQUEST["imagetype"] == 'observationonly') echo "CHECKED"; ?> > Image Vouchered Observations
							</div>
							<div style="margin-top:5px;">
								<input type='radio' name='imagetype' value='fieldonly' <?php if(array_key_exists("imagetype",$_REQUEST) && $_REQUEST["imagetype"] == 'fieldonly') echo "CHECKED"; ?> > Field Images (lacking specific locality details)
							</div>
						</fieldset>
					</div>
					<div style="clear:both;margin:20px;">
						<button id="loadimages" style='margin: 20px' name="submitaction" type="submit" value="search" >Load Images</button>
					</div>
				</div>

				<div id="collectiondiv">
					<?php
					if($specArr || $obsArr){
						?>
						<div id="specobsdiv">
							<div style="margin:0px 0px 10px 5px;">
								<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" checked />
						 		<?php echo (isset($LANG['SELECT_ALL'])?$LANG['SELECT_ALL']:'Select/Deselect all'); ?>
							</div>
							<?php
							$imgLibManager->outputFullCollArr($specArr, $catId);
							if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>';
							$imgLibManager->outputFullCollArr($obsArr, $catId);
							?>
							<div style="clear:both;">&nbsp;</div>
						</div>
						<?php
					}
					?>
					<div style="clear:both;"></div>
				</div>
			</form>

			<?php
			if($imageArr){
				?>
				<div id="imagesdiv">
					<div id="imagebox">
						<?php
						$lastPage = (int) ($recordCnt / $cntPerPage) + 1;
						$startPage = ($pageNumber > 4?$pageNumber - 4:1);
						$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
						$url = 'search.php?'.$_SERVER['QUERY_STRING'];
						$hrefPrefix = "<a href='".$url;
						$pageBar = '<div style="float:left" >';
						if($startPage > 1){
							$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="'.$url.'&page=1">First</a></span>';
							$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="'.$url.'&page='.(($pageNumber - 10) < 1 ?1:$pageNumber - 10).'">&lt;&lt;</a></span>';
						}
						for($x = $startPage; $x <= $endPage; $x++){
							if($pageNumber != $x){
								$pageBar .= '<span class="pagination" style="margin-right:3px;"><a href="'.$url.'&page='.$x.'">'.$x.'</a></span>';
							}
							else{
								$pageBar .= "<span class='pagination' style='margin-right:3px;font-weight:bold;'>".$x."</span>";
							}
						}
						if(($lastPage - $startPage) >= 10){
							$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="'.$url.'&page='.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10)).'">&gt;&gt;</a></span>';
							$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="'.$url.'&page='.$lastPage.'">Last</a></span>';
						}
						$pageBar .= '</div><div style="float:right;margin-top:4px;margin-bottom:8px;">';
						$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
						$endNum = $beginNum + $cntPerPage - 1;
						if($endNum > $recordCnt) $endNum = $recordCnt;
						$pageBar .= "Page ".$pageNumber.", records ".$beginNum."-".$endNum." of ".$recordCnt."</div>";
						$paginationStr = $pageBar;
						echo '<div style="width:100%;">'.$paginationStr.'</div>';
						echo '<div style="clear:both;margin:5 0 5 0;"><hr /></div>';
						echo '<div style="width:98%;margin-left:auto;margin-right:auto;">';
						foreach($imageArr as $imgArr){
							$imgId = $imgArr['imgid'];
							$imgUrl = $imgArr['url'];
							$imgTn = $imgArr['thumbnailurl'];
							if($imgTn){
								$imgUrl = $imgTn;
								if($imageDomain && substr($imgTn,0,1)=='/'){
									$imgUrl = $imageDomain.$imgTn;
								}
							}
							elseif($imageDomain && substr($imgUrl,0,1)=='/'){
								$imgUrl = $imageDomain.$imgUrl;
							}
							?>
							<div class="tndiv" style="margin-bottom:15px;margin-top:15px;">
								<div class="tnimg">
									<?php
									if($imgArr['occid']){
										echo '<a href="#" onclick="openIndPU('.$imgArr['occid'].');return false;">';
									}
									else{
										echo '<a href="#" onclick="openImagePopup('.$imgId.');return false;">';
									}
									echo '<img src="'.$imgUrl.'" />';
									echo '</a>';
									?>
								</div>
								<div>
									<?php
									$sciname = $imgArr['sciname'];
									if($sciname){
										if(strpos($imgArr['sciname'],' ')) $sciname = '<i>'.$sciname.'</i>';
										if($imgArr['tid']) echo '<a href="#" onclick="openTaxonPopup('.$imgArr['tid'].');return false;" >';
										echo $sciname;
										if($imgArr['tid']) echo '</a>';
										echo '<br />';
									}
									if($imgArr['catalognumber']){
										echo '<a href="#" onclick="openIndPU('.$imgArr['occid'].');return false;">';
										if(strpos($imgArr['catalognumber'], $imgArr['instcode']) !== 0) echo $imgArr['instcode'] . ": ";
										echo $imgArr['catalognumber'];
										echo '</a>';
									}
									elseif($imgArr['lastname']){
										$pName = $imgArr['firstname'].' '.$imgArr['lastname'];
										if(strlen($pName) < 20) echo $pName.'<br />';
										else echo $imgArr['lastname'].'<br />';
									}
									//if($imgArr['stateprovince']) echo $imgArr['stateprovince'] . "<br />";
									?>
								</div>
							</div>
							<?php
						}
						echo "</div>";
						if($lastPage > $startPage){
							echo "<div style='clear:both;margin:5 0 5 0;'><hr /></div>";
							echo '<div style="width:100%;">'.$paginationStr.'</div>';
						}
						?>
						<div style="clear:both;"></div>
						<?php
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>