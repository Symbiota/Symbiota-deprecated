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
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='checklists,species lists' />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
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
			Research and Dynamic Survey Species Lists are listed below. 
            Research Checklists are pre-compiled by floristic researchers.
            This is a very controlled method for building a species list where specific specimens can be linked in order to serve 
            as vouchers. Vouchers specimens serve as physical proof that the species actually occurs in the given area. 
            While Research Checklists are compiled with vouchers linked afterwards as support data, 
            Dynamic Survey Species Lists are generated directly from the specimen data. These are usually built by a team of researchers
            who create the list over an extended period of time by linking physical specimens or photo observations 
            as they are obtained from the research area. Since the lists are generated from the occurrence data on-demand, an annotation  
            of an identification will automatically adjust the species list as needed.  
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
			//List Dynamic Survey Checklists
            $surveyList = $clManager->getSurveyChecklists();
            if($surveyList){
				?>
				<h2 style="margin-top:30px;">Dynamic Survey Species Lists</h2>
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
