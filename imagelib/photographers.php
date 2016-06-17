<?php 
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/PhotographerManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$phUid = array_key_exists("phuid",$_REQUEST)?$_REQUEST["phuid"]:0;
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$limitStart = array_key_exists("lstart",$_REQUEST)?$_REQUEST["lstart"]:0;
$limitNum = array_key_exists("lnum",$_REQUEST)?$_REQUEST["lnum"]:100;
$imgCnt = array_key_exists("imgcnt",$_REQUEST)?$_REQUEST["imgcnt"]:0;

//
if(!is_numeric($collId)) $collId = 0;
if(!is_numeric($limitStart)) $limitStart = 0;
if(!is_numeric($limitNum)) $limitNum = 0;
if(!is_numeric($imgCnt)) $imgCnt = 0;

$pManager = new PhotographerManager();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Photographer List</title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($imagelib_photographersMenu)?$imagelib_photographersMenu:false);
	include($SERVER_ROOT.'/header.php');
	echo '<div class="navpath">';
	if(isset($imagelib_photographersCrumbs)){
		if($imagelib_photographersCrumbs){
			echo $imagelib_photographersCrumbs;
			echo '<b>Photographer List</b>'; 
		}
	}
	else{
		echo '<a href="../index.php">Home</a> &gt;&gt; ';
		echo '<a href="index.php">Image Library</a> &gt;&gt; ';
		if($phUid){
			echo '<a href="photographers.php">Photographer List</a> &gt;&gt; ';
			echo '<b>Image Listing</b>'; 
		}
		else{
			echo '<b>Photographer List</b>'; 
		}
	}
	echo "</div>";
	?> 
	<!-- This is inner text! -->
	<div id="innertext" style="height:100%">
		<?php 
		if($phUid || $collId){
			if($phUid){
				$pArr = $pManager->getPhotographerInfo($phUid)
				?>
				<div style="margin:20px;font-size:14px;">
					<div style="font-weight:bold;"><?php echo $pArr['name']; ?> </div>
					<?php 
					if($pArr['ispublic']){
						if($pArr['title']) echo '<div>'.$pArr['title'].'</div>';
						if($pArr['institution']) echo '<div>'.$pArr['institution'].'</div>';
						if($pArr['department']) echo '<div>'.$pArr['department'].'</div>';
						if($pArr['city'] || $pArr['state']){
							echo '<div>'.$pArr['city'].($pArr['city']?', ':'').$pArr['state'].'&nbsp;&nbsp;'.$pArr['zip'].'</div>';
						}
						if($pArr['country']) echo '<div>'.$pArr['country'].'</div>';
						if($pArr['email']) echo '<div>'.$pArr['email'].'</div>';
						if($pArr['notes']) echo '<div>'.$pArr['notes'].'</div>';
						if($pArr['biography']) echo '<div>'.$pArr['biography'].'</div>';
						if($pArr['url']) echo '<div><a href="'.$pArr['url'].'">'.$pArr['url'].'</a></div>';
					}
					else{
						echo '<div style="margin:10px;font-size:12px;">Photographers details not public</div>';
					}
					?>
				</div>
			<?php 
			}
			else{
				echo '<h2>'.$pManager->getCollectionName($collId).'</h2>';
			}
			?>
			<div style="float:right;">
				<a href="photographers.php">Return to Photographer List</a>
			</div>
			<?php
			if($imgCnt < 51){
				echo "<div>Total Images: $imgCnt</div>";
			}
			else{
				echo "<div style='font-weight:bold;'>Images: $limitStart - ".(($limitStart+$limitNum)<$imgCnt?($limitStart+$limitNum):$imgCnt)." of $imgCnt</div>";
			}
			echo "<hr />";
			if($imgArr = $pManager->getPhotographerImages($phUid,$collId,$limitStart,$limitNum)){
				$paginationStr = '<div style="clear:both;">';
				if($limitStart){
					$paginationStr .= '<div style="float:left;">';
					$paginationStr .= '<a href="photographers.php?phuid='.$phUid.'&collid='.$collId.'&imgcnt='.$imgCnt.'&lstart='.($limitStart - $limitNum).'&lnum='.$limitNum.'">&lt;&lt; Previous Images</a>';
					$paginationStr .= '</div>';
				}
				if($imgCnt >= ($limitStart+$limitNum)){
					$paginationStr .= '<div style="float:right;">';
					$paginationStr .= '<a href="photographers.php?phuid='.$phUid.'&collid='.$collId.'&imgcnt='.$imgCnt.'&lstart='.($limitStart + $limitNum).'&lnum='.$limitNum.'">';
					$paginationStr .= 'Next Images &gt;&gt;';
					$paginationStr .= '</a>';
					$paginationStr .= '</div>';
				}
				$paginationStr .= "</div>\n";
				echo $paginationStr;
				
				echo '<div style="clear:both;">';
				foreach($imgArr as $imgId => $imgArr){
					$imgUrl = $imgArr['url'];
					$imgTn = $imgArr['tnurl'];
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
					<div class="tndiv">
						<div class="tnimg">
							<a href="imgdetails.php?imgid=<?php echo $imgId; ?>">
								<img src="<?php echo $imgUrl; ?>" />
							</a>
						</div>
						<div>
							<?php 
							if($imgArr['tid']) echo '<a href="../taxa/index.php?taxon='.$imgArr['tid'].'">';
							echo '<i>'.$imgArr['sciname'].'</i>';
							if($imgArr['tid']) echo '</a>';
							?>
						</div>
					</div>
					<?php 
				}
				echo "</div>";
				echo $paginationStr;
			}			
		}
		else{
			?>
			<div style="float:left;;margin-right:40px;">
				<h2>Photographers</h2>
				<div style="margin-left:15px">
					<?php 
					$pList = $pManager->getPhotographerList();
					foreach($pList as $uid => $pArr){
						echo '<div>';
						echo '<a href="photographers.php?phuid='.$uid.'&imgcnt='.$pArr['imgcnt'].'">';
						echo $pArr['name'].'</a> ('.$pArr['imgcnt'].')</div>';
					}
					?>
				</div>
			</div>
			<div style="float:left">
				<h2>Specimen Images</h2>
				<div style="margin-left:15px">
					<?php
					ob_flush();
					flush();
					$pList = $pManager->getCollectionImageList();
					foreach($pList as $k => $cArr){
						echo '<div>';
						echo '<a href="photographers.php?collid='.$k.'&imgcnt='.$cArr['imgcnt'].'">';
						echo $cArr['name'].'</a> ('.$cArr['imgcnt'].')</div>';
					}
					?>
				</div>
			</div>
			<?php 
		}
		?>
	</div>
	<?php 
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>