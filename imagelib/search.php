<?php 
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$target = array_key_exists("target",$_REQUEST)?trim($_REQUEST["target"]):"";
$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1;
$view = array_key_exists("imagedisplay",$_REQUEST)?$_REQUEST["imagedisplay"]:'';
$stArrJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';
$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:'';

$imgLibManager = new ImageLibraryManager();

$collList = $imgLibManager->getFullCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);
$stArr = Array();
$previousCriteria = Array();
$imageArr = Array();
$taxaList = Array();
$jsonStArr = '';

if($stArrJson && !array_key_exists('db',$_REQUEST)){
	$stArrJson = str_replace( "'", '"',$stArrJson);
	$stArr = json_decode($stArrJson, true);
}

if($_REQUEST || $stArr){
	if($_REQUEST){
		$previousCriteria = $_REQUEST;
	}
	elseif($stArr){
		$previousCriteria = $stArr;
	}
}

$dbArr = Array();
if(array_key_exists('db',$_REQUEST)){
	$dbArr = $_REQUEST["db"];
}
elseif(array_key_exists('db',$previousCriteria)){
    $dbArr = explode(';',$previousCriteria["db"]);
}

if($action){
	if($action == 'Load Images'){
		if($stArr){
			$imgLibManager->setSearchTermsArr($stArr);
		}
		else{
            $imgLibManager->readRequestVariables();
			$stArr = $imgLibManager->getSearchTermsArr();
		}
		$imgLibManager->setSqlWhere();
		if($view == 'thumbnail'){
			$imageArr = $imgLibManager->getImageArr($pageNumber,$cntPerPage);
		}
		if($view == 'taxalist'){
			$taxaList = $imgLibManager->getFamilyList();
		}
		$recordCnt = $imgLibManager->getRecordCnt();
		$jsonStArr = json_encode($stArr);
	}
}
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Image Library</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
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

    <script src="../js/jquery.manifest.js" type="text/javascript"></script>
    <script src="../js/jquery.marcopolo.js" type="text/javascript"></script>
    <script src="../js/symb/images.index.js?ver=20170711" type="text/javascript"></script>

    <script type="text/javascript">
        var phArr = <?php echo (isset($previousCriteria["phjson"])&&$previousCriteria["phjson"]?"JSON.parse('".$previousCriteria["phjson"]."')":"new Array()"); ?>;

        jQuery(document).ready(function($) {
            $('#tabs').tabs({
                active: <?php echo (($imageArr || $taxaList)?'2':'0'); ?>,
                beforeLoad: function( event, ui ) {
                    $(ui.panel).html("<p>Loading...</p>");
                }
            });

            $('#photographer').manifest({
                required: true,
                marcoPolo: {
                    url: 'rpc/imagesearchautofill.php',
                    data: {
                        t: 'photographer'
                    },
                    formatItem: function (data){
                        return data.name;
                    }
                }
            });

            <?php
            if($stArr){
            if(array_key_exists("nametype",$previousCriteria) && $previousCriteria["nametype"] != "3"){
            ?>
            if(document.getElementById('taxastr').value){
                var qtaxaArr = document.getElementById('taxastr').value.split(",");
                for(i = 0; i < qtaxaArr.length; i++){
                    $('#taxa').manifest('add',qtaxaArr[i]);
                }
            }
            <?php
            }
            elseif(array_key_exists("nametype",$previousCriteria) && $previousCriteria["nametype"] == "3"){
            ?>
            if(document.getElementById('taxastr').value){
                var qtaxaArr = document.getElementById('taxastr').value.split(",");
                for(i = 0; i < qtaxaArr.length; i++){
                    $('#common').manifest('add',qtaxaArr[i]);
                }
            }
            <?php
            }
            ?>
            if(document.getElementById('countrystr').value){
                var qcountryArr = document.getElementById('countrystr').value.split(",");
                for(i = 0; i < qcountryArr.length; i++){
                    $('#country').manifest('add',qcountryArr[i]);
                }
            }
            if(document.getElementById('statestr').value){
                var qstateArr = document.getElementById('statestr').value.split(",");
                for(i = 0; i < qstateArr.length; i++){
                    $('#state').manifest('add',qstateArr[i]);
                }
            }
            if(document.getElementById('keywordstr').value){
                var qkeywordArr = document.getElementById('keywordstr').value.split(",");
                for(i = 0; i < qkeywordArr.length; i++){
                    $('#keywords').manifest('add',qkeywordArr[i]);
                }
            }
            if(document.getElementById('phjson').value){
                var qphArr = JSON.parse(document.getElementById('phjson').value);
                for(i = 0; i < qphArr.length; i++){
                    $('#photographer').manifest('add',qphArr[i].name);
                }
            }
            <?php
            }
            ?>

            $('#photographer').on('marcopoloselect', function (event, data, $item, initial) {
                phArr.push({name:data.name,id:data.id});
            });

            $('#photographer').on('manifestremove',function (event, data, $item){
                for (i = 0; i < phArr.length; i++) {
                    if(phArr[i].name == data){
                        phArr.splice(i,1);
                    }
                }
            });
            <?php

            if($view == 'thumbnail' && !$imageArr){
                echo "alert('There were no images matching your search critera');";
            }
            ?>
        });

        var starr = JSON.stringify(<?php echo $jsonStArr; ?>);
        var view = '<?php echo $view; ?>';
        var selectedFamily = '';
    </script>
	<!-- This is inner text! -->
	<div id="innertext">
		<div id="tabs" style="margin:0px;">
			<ul>
				<li><a href="#criteriadiv">Search Criteria</a></li>
				<li><a href="#collectiondiv">Collections</a></li>
				<?php
				if($imageArr || $taxaList){
					?>
					<li><a href="#imagesdiv"><span id="imagetab"><?php echo ($view == 'thumbnail'?'Images':'Taxa List'); ?></span></a></li>
					<?php
				}
				?>
			</ul>
			
			<form name="imagesearchform" id="imagesearchform" action="search.php" method="get" onsubmit="return submitImageForm();">
				<div id="criteriadiv">
					<div id="thesdiv" style="margin-left:160px;display:<?php echo ((array_key_exists("nametype",$previousCriteria) && $previousCriteria["nametype"] == "3")?'none':'block'); ?>;" >
						<input type='checkbox' id='thes' name='thes' value='1' <?php if(!$action || (array_key_exists("thes",$previousCriteria) && $previousCriteria["thes"])) echo "CHECKED"; ?> >Include Synonyms
					</div>
					<div style="clear:both;">
						<div style="float:left;">
							<select id="taxontype" name="nametype" onchange="checkTaxonType();" style="padding:5px;margin:5px 10px;">
								<option id='sciname' value='2' <?php if(array_key_exists("nametype",$previousCriteria) && $previousCriteria["nametype"] == "2") echo "SELECTED"; ?> >Scientific Name</option>
								<option id='commonname' value='3' <?php if(array_key_exists("nametype",$previousCriteria) && $previousCriteria["nametype"] == "3") echo "SELECTED"; ?> >Common Name</option>
							</select>
							<input id="taxtp" name="taxtp" type="hidden" value="<?php echo (array_key_exists("taxtp",$previousCriteria)?$previousCriteria["taxtp"]:'2'); ?>" />
						</div>
						<div id="taxabox" style="float:left;margin-bottom:10px;display:<?php echo ((array_key_exists("nametype",$previousCriteria) && $previousCriteria["nametype"] == "3")?'none':'block'); ?>;">
							<input id="taxa" type="text" style="width:450px;" name="taxa" value="" title="Separate multiple names w/ commas" autocomplete="off" />
						</div>
						<div id="commonbox" style="margin-bottom:10px;display:<?php echo ((array_key_exists("nametype",$previousCriteria) && $previousCriteria["nametype"] == "3")?'block':'none'); ?>;">
							<input id="common" type="text" style="width:450px;" name="common" value="" title="Separate multiple names w/ commas" autocomplete="off" />
						</div>
					</div>
					<!-- <div style="clear:both;margin:5 0 5 0;"><hr /></div>
					<div style="margin-top:5px;">
						<div style="float:left;margin-right:8px;padding-top:8px;">
							Country: 
						</div>
						<div style="float:left;">
							<input type="text" id="country" style="width:350px;" name="country" value="" title="Separate multiple countries w/ commas" />
						</div>
					</div>
					<div style="clear:both;margin-top:5px;">
						<div style="float:left;margin-right:8px;padding-top:8px;">
							State/Province: 
						</div>
						<div style="float:left;margin-bottom:10px;">
							<input type="text" id="state" style="width:350px;" name="state" value="" title="Separate multiple states w/ commas" />
						</div>
					</div> -->
					<div style="clear:both;margin:5 0 5 0;"><hr /></div>
					<div>
						<div style="float:left;margin-right:8px;padding-top:8px;">
							Photographers: 
						</div>
						<div style="float:left;margin-bottom:10px;">
							<input type="text" id="photographer" style="width:450px;" name="photographer" value="" title="Separate multiple photographers w/ commas" />
						</div>
					</div>
<!--					<div style="clear:both;margin:5 0 5 0;"><hr /></div>-->
<!--					--><?php
//					$tagArr = $imgLibManager->getTagArr();
//					if($tagArr){
//						?>
<!--						<div>-->
<!--							Image Tags: -->
<!--							<select id="tags" style="width:350px;" name="tags" >-->
<!--								<option value="">Select Tag</option>-->
<!--								<option value="">--------------</option>-->
<!--								--><?php //
//								foreach($tagArr as $k){
//									echo '<option value="'.$k.'" '.((array_key_exists("tags",$previousCriteria))&&($previousCriteria["tags"]==$k)?'SELECTED ':'').'>'.$k.'</option>';
//								}
//								?>
<!--							</select>-->
<!--						</div>-->
<!--						--><?php
//					}
//					?>
<!--					<div style="margin-top:5px;">-->
<!--						<div style="float:left;margin-right:8px;padding-top:8px;">-->
<!--							Image Keywords: -->
<!--						</div>-->
<!--						<div style="float:left;margin-bottom:10px;">-->
<!--							<input type="text" id="keywords" style="width:350px;" name="keywords" value="" title="Separate multiple keywords w/ commas" />-->
<!--						</div>-->
<!--					</div>-->
<!--                    <div style="clear:both;margin-top:5px;">-->
<!--                        <div style="float:left;margin-right:8px;padding-top:8px;">-->
<!--                            Date Uploaded:-->
<!--                        </div>-->
<!--                        <div style="float:left;margin-bottom:10px;">-->
<!--                            <input type="text" id="uploaddate1" size="32" name="uploaddate1" style="width:100px;" value="--><?php //echo (array_key_exists("uploaddate1",$previousCriteria)?$previousCriteria["uploaddate1"]:''); ?><!--" title="Single date or start date of range" /> --->
<!--                            <input type="text" id="uploaddate2" size="32" name="uploaddate2" style="width:100px;" value="--><?php //echo (array_key_exists("uploaddate2",$previousCriteria)?$previousCriteria["uploaddate2"]:''); ?><!--" title="End date of range; leave blank if searching for single date" />-->
<!--                        </div>-->
<!--                    </div>-->
<!--					<div style="clear:both;margin:5 0 5 0;"><hr /></div>-->
<!--					<div style="margin-top:5px;">-->
<!--						Limit Image Counts: -->
<!--						<select id="imagecount" name="imagecount">-->
<!--							<option value="all" --><?php //echo ((array_key_exists("imagecount",$previousCriteria))&&($previousCriteria["imagecount"]=='all')?'SELECTED ':''); ?><!-->All images</option>-->
<!--							<option value="taxon" --><?php //echo ((array_key_exists("imagecount",$previousCriteria))&&($previousCriteria["imagecount"]=='taxon')?'SELECTED ':''); ?><!-->One per taxon</option>-->
<!--							<option value="specimen" --><?php //echo ((array_key_exists("imagecount",$previousCriteria))&&($previousCriteria["imagecount"]=='specimen')?'SELECTED ':''); ?><!-->One per occurrence</option>-->
<!--						</select>-->
<!--					</div>-->
					<div style="margin-top:5px;">
						Image Display: 
						<select id="imagedisplay" name="imagedisplay" onchange="imageDisplayChanged(this.form)">
							<option value="thumbnail" <?php echo ((array_key_exists("imagedisplay",$previousCriteria))&&($previousCriteria["imagedisplay"]=='thumbnail')?'SELECTED ':''); ?>>Thumbnails</option>
							<option value="taxalist" <?php echo ((array_key_exists("imagedisplay",$previousCriteria))&&($previousCriteria["imagedisplay"]=='taxalist')?'SELECTED ':''); ?>>Taxa List</option>
						</select>
					</div>
					<table>
						<tr>
							<td>
								<div style="margin-top:5px;">
									<p><b>Limit Image Type:</b></p>
								</div>
								<div style="margin-top:5px;">
									<input type='radio' name='imagetype' value='all' <?php if((!array_key_exists("imagetype",$previousCriteria)) || (array_key_exists("imagetype",$previousCriteria) && $previousCriteria["imagetype"] == 'all')) echo "CHECKED"; ?> > All Images
								</div>
								<div style="margin-top:5px;">
									<input type='radio' name='imagetype' value='specimenonly' <?php if(array_key_exists("imagetype",$previousCriteria) && $previousCriteria["imagetype"] == 'specimenonly') echo "CHECKED"; ?> > Vouchered occurrence Images
								</div>
								<div style="margin-top:5px;">
									<input type='radio' name='imagetype' value='observationonly' <?php if(array_key_exists("imagetype",$previousCriteria) && $previousCriteria["imagetype"] == 'observationonly') echo "CHECKED"; ?> > Field photos
								</div>
<!--								<div style="margin-top:5px;">-->
<!--									<input type='radio' name='imagetype' value='fieldonly' --><?php //if(array_key_exists("imagetype",$previousCriteria) && $previousCriteria["imagetype"] == 'fieldonly') echo "CHECKED"; ?><!-- > Field Images (lacking specific locality details)-->
<!--								</div>-->
							</td>
						</tr>
					</table>
					<div><hr></div>
					<input id="taxastr" name="taxastr" type="hidden" value="<?php if(array_key_exists("taxastr",$previousCriteria)) echo $previousCriteria["taxastr"]; ?>" />
					<input id="countrystr" name="countrystr" type="hidden" value="<?php if(array_key_exists("countrystr",$previousCriteria)) echo $previousCriteria["countrystr"]; ?>" />
					<input id="statestr" name="statestr" type="hidden" value="<?php if(array_key_exists("statestr",$previousCriteria)) echo $previousCriteria["statestr"]; ?>" />
					<input id="keywordstr" name="keywordstr" type="hidden" value="<?php if(array_key_exists("keywordstr",$previousCriteria)) echo $previousCriteria["keywordstr"]; ?>" />
					<input id="phuidstr" name="phuidstr" type="hidden" value="<?php if(array_key_exists("phuidstr",$previousCriteria)) echo $previousCriteria["phuidstr"]; ?>" />
					<input id="phjson" name="phjson" type="hidden" value='<?php if(array_key_exists("phjson",$previousCriteria)) echo $previousCriteria["phjson"]; ?>' />
					<button id="loadimages" style='margin: 20px' name="submitaction" type="submit" value="Load Images" >Load Images</button>
					<div style="clear:both;"></div>
				</div>
				
				<div id="collectiondiv">
					<?php 
					if($specArr || $obsArr){
						?>
						<div id="specobsdiv">
							<div style="margin:0px 0px 10px 20px;">
								<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" <?php echo ((!$dbArr || in_array('all',$dbArr))?'checked':''); ?>/>
								Select/Deselect all <a href="<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php">Collections</a>
							</div>
							<?php 
							if($specArr){
								echo '<button id="loadimages" style="float:right;" name="submitaction" type="submit" value="Load Images" >Load Images</button>';
								$imgLibManager->outputFullMapCollArr($dbArr,$specArr);
							}
							if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>'; 
							if($obsArr){
								echo '<button id="loadimages" style="float:right;" name="submitaction" type="submit" value="Load Images" >Load Images</button>';
								$imgLibManager->outputFullMapCollArr($dbArr,$obsArr);
							}
							?>
							<div style="clear:both;"></div>
						</div>
						<?php 
					}
					?>
					<div style="clear:both;"></div>
				</div>
			</form>
			
			<?php
			if($imageArr || $taxaList){
				?>
				<div id="imagesdiv">
					<div id="imagebox">
						<?php
						if($imageArr){
							$lastPage = (int) ($recordCnt / $cntPerPage) + 1;
							$startPage = ($pageNumber > 4?$pageNumber - 4:1);
							$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
							$onclick = 'changeImagePage("","thumb",starr,';
							$hrefPrefix = "<a href='#' onclick='".$onclick;
							$pageBar = '<div style="float:left" >';
							if($startPage > 1){
								$pageBar .= "<span class='pagination' style='margin-right:5px;'>".$hrefPrefix."1); return false;'>First</a></span>";
								$pageBar .= "<span class='pagination' style='margin-right:5px;'>".$hrefPrefix.(($pageNumber - 10) < 1 ?1:$pageNumber - 10)."); return false;'>&lt;&lt;</a></span>";
							}
							for($x = $startPage; $x <= $endPage; $x++){
								if($pageNumber != $x){
									$pageBar .= "<span class='pagination' style='margin-right:3px;'>".$hrefPrefix.$x."); return false;'>".$x."</a></span>";
								}
								else{
									$pageBar .= "<span class='pagination' style='margin-right:3px;font-weight:bold;'>".$x."</span>";
								}
							}
							if(($lastPage - $startPage) >= 10){
								$pageBar .= "<span class='pagination' style='margin-left:5px;'>".$hrefPrefix.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10))."); return false;'>&gt;&gt;</a></span>";
								$pageBar .= "<span class='pagination' style='margin-left:5px;'>".$hrefPrefix.$lastPage."); return false;'>Last</a></span>";
							}
							$pageBar .= "</div><div style='float:right;margin-top:4px;margin-bottom:8px;'>";
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
						}
						if($taxaList){
							echo "<div style='margin-left:20px;margin-bottom:20px;font-weight:bold;'>Select a family to see genera list.</div>";
							foreach($taxaList as $value){
								$onChange = '"'.$value.'","genlist",starr,1';
								$famChange = '"'.$value.'"';
								echo "<div style='margin-left:30px;'><a href='#' onclick='changeFamily(".$famChange.");changeImagePage(".$onChange."); return false;'>".strtoupper($value)."</a></div>";
							}
						}
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