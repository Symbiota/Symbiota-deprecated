<?php
/*
 * Created on Jun 11, 2006
 * By E.E. Gilbert
 */
 //error_reporting(0);
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/TaxonProfileManager.php');
 Header("Content-Type: text/html; charset=".$charset);

 $descrDisplayLevel;
 $taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
 $taxAuthId = array_key_exists("taxauthid",$_REQUEST)?$_REQUEST["taxauthid"]:1; 
 $clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:"";
 $projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
 $lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$defaultLang;
 $descrDisplayLevel = array_key_exists("displaylevel",$_REQUEST)?$_REQUEST["displaylevel"]:"";
 
 if(!$projValue && !$clValue) $projValue = $defaultProjId;
 
 $taxonManager = new TaxonProfileManager();
 if($taxAuthId || $taxAuthId === "0") {
 	$taxonManager->setTaxAuthId($taxAuthId);
 }
 if($clValue) $taxonManager->setClName($clValue);
 if($projValue) $taxonManager->setProj($projValue);
 if($lang) $taxonManager->setLanguage($lang);
 if($taxonValue) $taxonManager->setTaxon($taxonValue);
 $spDisplay = $taxonManager->getDisplayName();
 $taxonRank = $taxonManager->getRankId();
 
 $editable = false;
 if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
 	$editable = true;
 }
 $descr = Array();
 
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." - ".$spDisplay; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<meta name='keywords' content='virtual flora,<?php echo $spDisplay; ?>' />
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<link rel="stylesheet" href="../css/speciesprofile.css" type="text/css"/>
	<link rel="stylesheet" type="text/css" href="../css/tabcontent.css" />
	<script type="text/javascript" src="../js/tabcontent.js"></script>
	<SCRIPT LANGUAGE="JavaScript">

		var imageArr = new Array();
		var imgCnt = 0;
		var currentLevel = <?php echo ($descrDisplayLevel?$descrDisplayLevel:"1"); ?>;
		var levelArr = new Array(<?php echo ($descr?"'".implode("','",array_keys($descr))."'":""); ?>);

		function toggle(target){
			var spanObjs = document.getElementsByTagName("span");
			for (i = 0; i < spanObjs.length; i++) {
				var obj = spanObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="inline";
					}
					else {
						obj.style.display="none";
					}
				}
			}
	
			var divObjs = document.getElementsByTagName("div");
			for (i = 0; i < divObjs.length; i++) {
				var obj = divObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="inline";
					}
					else {
						obj.style.display="none";
					}
				}
			}
		}
	
		function toggleMap(mapObj){
			var roi = mapObj.value;
			var mapObjs = getElementByTagName("div");
			for(x=0;x<mapObjs.length;x++){
				var mObj = mapObjs[x];
				if(mObj.classname == "mapdiv"){
					if(mObj == mapObj){
						mObj.style.display = "block";
					}
					else{
						mObj.style.display = "none";
					}
				}
			}
		}
		
		function toggleImgInfo(target, anchorObj){
			//close all imgpopup divs
			var divs = document.getElementsByTagName("div");
			for(x=0;x<divs.length;x++){
				var d = divs[x];
				if(d.getAttribute("class") == "imgpopup" || d.getAttribute("className") == "imgpopup"){
					d.style.display = "none";
				}
			}

			//Open and place target imgpopup
			var obj = document.getElementById(target);
			var pos = findPos(anchorObj);
			var posLeft = pos[0];
			if(posLeft > 550){
				posLeft = 550;
			}
			obj.style.left = posLeft;
			obj.style.top = pos[1];
			if(obj.style.display=="block"){
				obj.style.display="none";
			}
			else {
				obj.style.display="block";
			}
			var targetStr = "document.getElementById('" + target + "').style.display='none'";
			var t=setTimeout(targetStr,10000);
		}
		
		function findPos(obj){
			var curleft = 0; 
			var curtop = 0;
			curleft = obj.offsetLeft;
			curtop = obj.offsetTop;
			return [curleft,curtop];
		}	
		
		function setImgPopupHtml(puTarget,imgId){
			siphXmlHttp=GetXmlHttpObject();
			if (siphXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="<?php echo $clientRoot; ?>/imagelib/rpc/getimgmetadata.php";
			url=url+"?imgid="+imgId;
			siphXmlHttp.onreadystatechange=function(){
				alert(siphXmlHttp.readyState);
				alert(siphXmlHttp.responseText);
				if(siphXmlHttp.readyState==4){
					if(siphXmlHttp.status==200){
						puTarget.innerHTML=siphXmlHttp.responseText;
					}
					else{
						puTarget.innerHTML = "Unable to retrieve image details";
					}
				}
			}
			siphXmlHttp.open("POST",url,true);
			siphXmlHttp.send(null);
		} 
		
		function initTabs(tabObjId){
			var dTabs=new ddtabcontent(tabObjId); 
			dTabs.setpersist(true);
			dTabs.setselectedClassTarget("link"); 
			dTabs.init();
		}
		
		function expandImages(){
			eiObj = document.getElementById("imgextra");
			eiObj.style.display = "block";
			mpObj = document.getElementById("morephotos");
			mpObj.style.display = "none";
		}

		function GetXmlHttpObject(){
			var xmlHttp=null;
			try{
				// Firefox, Opera 8.0+, Safari, IE 7.x
		  		xmlHttp=new XMLHttpRequest();
		  	}
			catch (e){
		  		// Internet Explorer
		  		try{
		    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		    	}
		  		catch(e){
		    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		    	}
		  	}
			return xmlHttp;
		}
		
	</script>
</head>
<body onload="initTabs('desctabs');">
<?php
$displayLeftMenu = (isset($taxa_indexMenu)?$taxa_indexMenu:"true");
include($serverRoot.'/header.php');
if(isset($taxa_indexCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_indexCrumbs;
	echo " <b>$spDisplay</b>";
	echo "</div>";
}
?>
<table id="innertable">
<?php 
if($taxonManager->getSciName() != "unknown"){
	if($taxonRank > 180){
		?>
		<tr>
			<td colspan="2" valign="bottom" height="35px">
			<?php 
			//Top Middle Section, scientific name
			echo "<div style='float:left;font-size:16px;margin-left:10px;'><span style='font-weight:bold;color:#990000;'><i>$spDisplay</i></span> ".$taxonManager->getAuthor();
			$parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&cl=".$taxonManager->getClName()."&proj=".$projValue."&taxauthid=".$taxAuthId;
			echo "&nbsp;<a href='".$parentLink."'><img border='0' height='10px' src='../images/toparent.jpg' title='Go to Parent' /></a>";
		 	//If submitted tid does not equal accepted tid, state that user will be redirected to accepted
		 	if(($taxonManager->getTid() != $taxonManager->getSubmittedTid()) && $taxAuthId){
		 		echo "<span style='font-size:90%;margin-left:25px;'> (redirected from: <i>".$taxonManager->getSubmittedSciName()."</i>)</span>"; 
		 	}
			echo "</div>";
			if($editable){
				echo "<div style='float:right;'><a href='admin/tpeditor.php?tid=".$taxonManager->getTid()."' title='Edit Taxon Data'><img style='border:0px;' src='../images/edit.png'/></a></div>";
			}
			?>
			</td>
		</tr>
		<tr>
			<td width='300' valign='top'>
		<?php 
		//Left Middle Section
		echo "\t<div id='family' style='margin-left:20px;margin-top:0.25em;'><b>Family:</b> ".$taxonManager->getFamily()."</div>\n";
	
		$vernStr = $taxonManager->getVernacularStr();
		if($vernStr){
			echo "\t<div id='vernaculars' style='margin-left:10px;margin-top:0.5em;font-size:130%;' title='Common Names'>";
			echo $vernStr;
			echo"</div>\n";
		}
		
		$synStr = $taxonManager->getSynonymStr();
		if($synStr){
			echo "\t<div id='synonyms' style='margin-left:20px;margin-top:0.5em;' title='Synonyms'>[";
			echo $synStr;
			echo"]</div>\n";
		}
		
		if(!$taxonManager->echoImages(0,1,0)){
			echo "<div class='image' style='width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;'>";
			if($editable){
				echo "<a href='admin/tpeditor.php?category=imageadd&tid=".$taxonManager->getTid()."'><b>Add an Image</b></a>";
			}
			else{
				echo "<br/><br/><br/><br/><br/><br/>Images<br/>not yet<br/>available";
			}
			echo "</div>";
		}
		?>
			</td>
			<td class="desc">
				<div style="height:290px;">
				<?php 
				//Middle Right Section (Description section)
				$taxonManager->echoDescriptionBlock();
				?>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
		<?php 
		//Bottom Section - Pics and Map
		//Display next 4 pics along bottom to left of map
		$taxonManager->echoImages(1,4);
		
		//Map
		$mapSrc = $taxonManager->getMapUrl();
		if($mapSrc){
			$gUrl = ""; $iUrl = "";
			if($taxonManager->getSecurityStatus() == 0 || $isAdmin || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
				$gUrl = "javascript:var popupReference=window.open('".$clientRoot."/collections/maps/googlemap.php?usecookies=false&type=3&db=all&thes=on&taxa=".$taxonManager->getSciName()."','gmap','toolbar=0,resizable=1,width=950,height=700,left=20,top=20');";
			}
			$url = array_shift($mapSrc);
			if(strpos($url,"maps.google.com")){
				if($gUrl) $aUrl = $gUrl;
			}
			else{
				$aUrl = $url;
				if($gUrl) $iUrl = $gUrl;
			}
			echo "<div class='mapthumb'>";
			if($aUrl) echo "<a href=\"".$aUrl."\">";
			echo "<img src='".$url."' title='".$spDisplay." dot map' alt='".$spDisplay." dot map'/>";
			if($aUrl) echo "</a>";
			if($iUrl) echo "<br /><a href=\"".$iUrl."\">Open Interactive Map</a>";
			echo "</div>";
		}
		?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div id="imgextra" style="display:none;">
					<?php 
					//Section with extra images
					$taxonManager->echoImages(5,0);
					?>
				</div>
			</td>
		</tr>
		<?php 
	}
	else{
		?>
		<tr>
			<td style="width:250px;">
				<?php 
				$displayName = $spDisplay;
				if($taxonRank == 180){
					$parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&cl=".$taxonManager->getClName()."&proj=".$projValue."&taxauthid=".$taxAuthId;
					$displayName = "<i>".$displayName."</i> spp.&nbsp;<a href='".$parentLink."'><img border='0' height='10px' src='../images/toparent.jpg' title='Go to Parent' /></a>";
				}
				echo "<div style='font-size:16px;margin-top:15px;margin-left:10px;font-weight:bold;'>$displayName</div>\n";
				if($taxonRank == 180) echo "<div id='family' style='margin-top:3px;margin-left:20px;'><b>Family:</b> ".$taxonManager->getFamily()."</div>\n";
				if($projValue) echo "<div style='margin-top:3px;margin-left:20px;'><b>Project:</b> ".$taxonManager->getProjName()."</div>\n";
				?>
			</td>
			<td>
				<div id='descrgenus' style='float:right;width:100%;height:200px;position:relative;'>
				<?php 
				//Display description
				$taxonManager->echoDescriptionBlock();
				?>
				<?php 
				if($editable){
					?>
					<div style='position:absolute;top:0px;right:0px;'>
						<a href='admin/tpeditor.php?tid=".$taxonManager->getTid()."' title='Edit Taxon Data'>
							<img style='border:0px;' src='../images/edit.png'/>
						</a>
					</div>
					<?php 
				}
				?>
				</div>
			</td>
		</tr>

		<tr>
			<td colspan="2">
				<div class='fieldset' style="padding:10px 2px 10px 2px;">
					<div class='legend'>Species
					<?php 
					if($clValue){
						echo " within ".$taxonManager->getClTitle()."&nbsp;&nbsp;";
						if($taxonManager->getParentClid()){
							echo "<a href='index.php?taxon=$taxonValue&cl=".$taxonManager->getParentClid()."&taxauthid=".$taxAuthId."' title='Go to ".$taxonManager->getParentName()." checklist'><img style='border:0px;width:12px;height:12px;' src='../images/toparent.jpg'/></a>";
						}
					}
					?>
					</div>
					<div>
					<?php 
					$sppArr = $taxonManager->getSppArray();
					$cnt = 0;
					ksort($sppArr);
					foreach($sppArr as $sciNameKey => $subArr){
						if($cnt%5 == 0 && $cnt > 0){
							echo "<div style='clear:both;'><hr></div>";
						}
						echo "<div class='spptaxon'>";
						echo "<div style='margin-top:10px;'><a href='index.php?taxon=".$subArr["tid"]."&taxauthid=".$taxAuthId.($clValue?"&cl=".$clValue:"")."'><i>".$sciNameKey."</i></a></div>\n";
						echo "<div class='sppimg' style='overflow:hidden;'>";
	
						if(array_key_exists("url",$subArr)){
							$imgUrl = $subArr["url"];
							if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
								$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
							}
							echo "<a href='index.php?taxon=".$subArr["tid"]."&taxauthid=".$taxAuthId.($clValue?"&cl=".$clValue:"")."'>";
	
							if($subArr["thumbnailurl"]){
								$imgUrl = $subArr["thumbnailurl"];
								if(array_key_exists("imageDomain",$GLOBALS) && substr($subArr["thumbnailurl"],0,1)=="/"){
									$imgUrl = $GLOBALS["imageDomain"].$subArr["thumbnailurl"];
								}
							}
							echo "<img src='".$imgUrl."' title='".$subArr["caption"]."' alt='Image of ".$sciNameKey."' style='z-index:-1' />";
							echo "</a>\n";
							echo "<div style='text-align:right;position:relative;top:-26px;left:5px;' title='Photographer: ".$subArr["photographer"]."'>";
							echo "<a href='../imagelib/imgdetails.php?imgid=".$subArr["imgid"]."'>";
							echo "<img style='width:10px;height:10px;border:0px;' src='../images/info.jpg' />";
							echo "</a>";
							echo "</div>";
						}
						elseif($editable){
							echo "<div class='spptext'><a href='admin/tpeditor.php?category=imageadd&tid=".$subArr["tid"]."'>Add an Image!</a></div>";
						}
						else{
							echo "<div class='spptext'>Image<br/>Not Available</div>";
						}
						echo "</div>\n";
						
						if(array_key_exists("map",$subArr) && $mapUrl = $subArr["map"]){
							$gUrl = ""; $iUrl = "";
							if($taxonManager->getSecurityStatus() == 1 || $isAdmin){
								$gUrl = "javascript:var popupReference=window.open('".$clientRoot."/collections/maps/googlemap.php?usecookies=false&type=3&db=all&thes=on&taxa=".$subArr["tid"]."','gmap','toolbar=0,resizable=1,width=950,height=700,left=20,top=20');";
							}
							if(strpos($mapUrl,"maps.google.com")){
								if($gUrl) $aUrl = $gUrl;
							}
							else{
								$aUrl = $mapUrl;
								if($gUrl) $iUrl = $gUrl;
							}
							echo "<div class='sppmap'>";
							if($aUrl) echo "<a href=\"".$aUrl."\">";
							echo "<img src='".$mapUrl."' title='".$spDisplay." dot map' alt='".$spDisplay." dot map'/>";
							if($aUrl) echo "</a>";
							if($iUrl) echo "<br /><a href=\"".$iUrl."\">Open Interactive Map</a>";
							echo "</div>";
						}
						elseif($taxonManager->getRankId()>140){
							echo "<div class='sppmap'><div class='spptext'>Map<br />not<br />Available</div></div>\n";
						}
						echo "</div>";
						$cnt++;
					}
					?>
						<div style='clear:both;'><hr></div>
					</div>
				</div>
			</td>
		</tr>
			<?php 
	}
	?>
		<tr>
		<td colspan="2">
	
	<?php 
	//Bottom line listing options
	echo "<div style='margin-top:15px;text-align:center;'>";
	if($taxonRank > 180){
		if($taxonManager->getTaxaImageCnt() > 5) echo "<span id='morephotos'><a href='javascript:expandImages();'>More Photos</a></span>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"javascript:toggle('links')\">Web Links</a>";
	}

	if($taxonRank > 140){
		$parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&taxauthid=".$taxAuthId;
		if($clValue) $parentLink .= "&cl=".$taxonManager->getClName();
		if($projValue) $parentLink .= "&proj=".$projValue;
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='".$parentLink."'>View Parent Taxon</a>";
	}
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript: self.close();'>Close window</a>";
	echo "</div>";
	
	//List Web Links as a list
	if($taxonRank > 180){
		echo "<div class='links' style='display:none;'>\n<h1 style='margin-left:20px;'>Web Links</h1>\n<ul style='margin-left:30px;'>\n";
		$links = $taxonManager->getTaxaLinks();
		if($links){
			foreach($links as $l){
				$urlStr = str_replace("--SCINAME--",str_replace(" ","%20",$taxonManager->getSciName()),$l["url"]);
				$title = $l["title"];
				if(!$title) $title = $urlStr;
				echo "<li><a href='".$urlStr."' target='_blank'>".$title."</a></li>";
				if($l["notes"]) echo " ".$l["notes"];
			}
		}
		echo "</ul>\n</div>";
	}
	echo "</td></tr>\n";
}
elseif($taxonValue){
	echo "<tr><td>";
	echo "<div style='margin-top:45px;margin-left:20px'><h1>Sorry, we do not have <i>$taxonValue</i> in our system.</h1>\n";
	echo "<h3>Links below may provide some useful information:</h3>\n";
	echo "<ul><li><a target='_blank' href='http://www.google.com/search?hl=en&btnG=Google+Search&q=\"".$taxonValue."\"'>Google</a></li>\n";
	echo "<li><a target='_blank' href='http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=all&search_value=".$taxonValue."&search_kingdom=every&search_span=exactly_for&categories=All&source=html&search_credRating=All'>ITIS: Integrated Taxonomic Information System</a></li>\n";
	echo "<li><a target='_blank' href='http://images.google.com/images?q=\"".$taxonValue."\"'>Google Images</a></li>\n</ul></div>\n";
	echo "</td></tr>";
}
else{
	echo "<tr><td>";
	echo "Scientific name (eg: taxon=Pinus+ponderosa) not submitted. Please submit a taxon value.";
	echo "</td></tr>";
}
?>
</table>
<?php 
include($serverRoot.'/footer.php');

?>
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
			var pageTracker = _gat._getTracker("<?php echo $googleAnalyticsKey; ?>");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
</body>
</html>

