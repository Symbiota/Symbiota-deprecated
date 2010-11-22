<?php
//error_reporting(E_ALL);
 include_once('../config/symbini.php');
 include_once($serverRoot.'/classes/ChecklistListingManager.php');
 header("Content-Type: text/html; charset=".$charset);
 $projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
 $clManager = new ChecklistListingManager();
 $clManager->setProj($projValue);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Species Lists</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<meta name='keywords' content='checklists,species lists' />
	<script type="text/javascript">
		function toggle(target){
			var divObjs = document.getElementsByTagName("div");
		  	for (i = 0; i < divObjs.length; i++) {
		  		var obj = divObjs[i];
		  		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="block";
					}
				 	else {
				 		obj.style.display="none";
				 	}
				}
			}
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
		}
	</script>
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '<?php echo $googleAnalyticsKey; ?>']);
		_gaq.push(['_trackPageview']);
	
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($checklists_indexMenu)?$checklists_indexMenu:"true");
	include($serverRoot."/header.php");
	if(isset($checklists_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_indexCrumbs;
		echo " <b>".$defaultTitle." Species Lists</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Species Checklists</h1>
        <div style="margin:20px">
			Research and Public Survey Checklists are listed below. 
            Research Checklists are pre-compiled by floristic researchers.
            This is a very controlled method of building a species list where specific specimens can be linked to serve 
            as a voucher. Vouchers specimens serve as physical proof that the species actually occurs in the given area. 
            While Research Checklists are compiled with vouchers linked afterwards as support data, 
            Public Survey Checklists are directly generated from the specimen data. These are usually built by a team of individuals
            who create the list by linking specimens or photo observations obtained from the area to a survey project. 
            This offers a collaborative, reproducible method for generating a species list for a given area.  
		</div>

        <div style='margin:20px;'>
			<?php 
            $researchList = $clManager->getResearchChecklists();
			if($researchList){
				?>
				<h2>Research Checklists</h2>
	            <?php
				foreach($researchList as $projStr => $clArr){
					$tokens = explode("::",$projStr);
					$pid = $tokens[0];
					$pName = $tokens[1];
					$projId = str_replace(" ","",$pName);
					?>
					<div style='margin:3px 0px 0px 15px;'>
						<a name="<?php echo $pName; ?>"></a>
						<h3>
							<span style="cursor:pointer;color:#990000;" onclick="javascript:toggle('stcl-<?php echo $projId; ?>')">
								<span class="stcl-<?php echo $projId; ?>" style="display:none;">
									<img src='../images/plus.gif'/>
								</span>
								<span class="stcl-<?php echo $projId; ?>" style="display:inline;">
									<img src='../images/minus.gif'/>
								</span>&nbsp;&nbsp;
								<?php echo $pName;?>
							</span>&nbsp;&nbsp;
							<a href="<?php echo "clgmap.php?cltype=research&proj=".$pid; ?>" title='Show checklists on map'>
								<img src='../images/world40.gif' style='width:10px;border:0' />
							</a>
						</h3>
						<div class="stcl-<?php echo $projId; ?>" style="display:block;">
							<ul>
								<?php 
								foreach($clArr as $clid => $clName){
									echo "<li><a href='checklist.php?cl=".$clid."'>".$clName."</a></li>\n";
								}
								?>
							</ul>
						</div>
					</div>
					<?php 
				}
			}
			//List Public Survey Checklists
            $surveyList = $clManager->getSurveyChecklists();
            if($surveyList){
				?>
				<h2 style="margin-top:30px;">Public Survey Checklists</h2>
	            <?php
				foreach($surveyList as $projStr => $clArr){
					$tokens = explode("::",$projStr);
					$pid = $tokens[0];
					$pName = $tokens[1];
					$projId = str_replace(" ","",$pName);
					?>
					<div style='margin:3px 0px 0px 15px;'>
						<a name="<?php echo $pName; ?>"></a>
						<h3>
							<span style="cursor:pointer;color:#990000;" onclick="javascript:toggle('pscl-<?php echo $projId; ?>')">
								<span class="stcl-<?php echo $projId; ?>" style="display:none;">
									<img src='../images/plus.gif'/>
								</span>
								<span class="stcl-<?php echo $projId; ?>" style="display:inline;">
									<img src='../images/minus.gif'/>
								</span>&nbsp;&nbsp;
								<?php echo $pName;?>
							</span>&nbsp;&nbsp;
							<a href="<?php echo "clgmap.php?cltype=survey&proj=".$pid; ?>" title='Show checklists on map'>
								<img src='../images/world40.gif' style='width:10px;border:0' />
							</a>
						</h3>
						<div class="pscl-<?php echo $projId; ?>" style="display:block;">
							<ul>
								<?php 
								foreach($clArr as $id => $clName){
									echo "<li><a href='survey.php?surveyid=".$id."'>".$clName."</a></li>\n";
								}
								?>
							</ul>
						</div>
					</div>
					<?php 
				}
            }
			?>
		</div>
	</div>
	
	<?php
		include($serverRoot."/footer.php");
	?>
</body>
</html>
