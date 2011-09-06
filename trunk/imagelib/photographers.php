<?php 
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PhotographerManager.php');
header("Content-Type: text/html; charset=".$charset);

$phUid = array_key_exists("phuid",$_REQUEST)?$_REQUEST["phuid"]:0;
$limitStart = array_key_exists("lstart",$_REQUEST)?$_REQUEST["lstart"]:0;
$limitNum = array_key_exists("lnum",$_REQUEST)?$_REQUEST["lnum"]:100;
$imgCnt = array_key_exists("imgcnt",$_REQUEST)?$_REQUEST["imgcnt"]:0;

$pManager = new PhotographerManager();
if($phUid) $pManager->setUid($phUid);  
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Photographer List</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<link rel="stylesheet" href="../css/speciesprofile.css" type="text/css"/>
	<meta name='keywords' content='' />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($imagelib_photographersMenu)?$imagelib_photographersMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_photographersCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_photographersCrumbs;
		echo " <b>Photographer List</b>"; 
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
		<h1><?php echo $defaultTitle; ?> Photographers</h1>
		<?php
		if($phUid){
			$pArr = $pManager->getPhotographerInfo()
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
			<div style="float:right;">
				<a href="photographers.php">Return to Photographer List</a>
			</div>
			<?php 
			if($imgCnt < 51){
				echo "<div>Total Image: $imgCnt</div>";
			}
			else{
				echo "<div style='font-weight:bold;'>Images: $limitStart - ".($limitStart+$limitNum)." of $imgCnt</div>";
			}
			echo "<hr />";
			
			$imgArr = $pManager->getPhotographerImages($limitStart,$limitNum);
			if($imgArr){
				$paginationStr = '<div style="clear:both;">';
				if($limitStart){
					$paginationStr .= '<div style="float:left;">';
					$paginationStr .= '<a href="photographers.php?phuid='.$phUid.'&imgcnt='.$imgCnt.'&lstart='.($limitStart - $limitNum).'&lnum='.$limitNum.'">&lt;&lt; Previous Images</a>';
					$paginationStr .= '</div>';
				}
				if($imgCnt >= $limitNum){
					$paginationStr .= '<div style="float:right;">';
					$paginationStr .= '<a href="photographers.php?phuid='.$phUid.'&imgcnt='.$imgCnt.'&lstart='.($limitStart + $limitNum).'&lnum='.$limitNum.'">Next Images &gt;&gt;</a>';
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
					<div style="float:left;height:160px;" class="imgthumb">
						<a href="imgdetails.php?imgid=<?php echo $imgId; ?>">
							<img src="<?php echo $imgUrl; ?>" style="height:130px;" />
						</a><br />
						<a href="../taxa/index.php?taxon=<?php echo $imgArr['tid']; ?>">
							<i><?php echo $imgArr['sciname']; ?></i>
						</a>
					</div>
					<?php 
				}
				echo "</div>";
				echo $paginationStr;
			}			
		}
		else{
			$pList = $pManager->getPhotographerList();
			foreach($pList as $uid => $pArr){
				echo '<div>';
				echo '<a href="photographers.php?phuid='.$uid.'&imgcnt='.$pArr['imgcnt'].'">';
				echo $pArr['name'].'</a> ('.$pArr['imgcnt'].')</div>';
			}
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>

