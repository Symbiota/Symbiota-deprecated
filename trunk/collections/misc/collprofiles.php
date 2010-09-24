<?php
//error_reporting(E_ALL);
 include_once('../../config/symbini.php');
 include_once($serverRoot.'/classes/CollectionProfileManager.php');
 header("Content-Type: text/html; charset=".$charset);

 $collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
 $showFamilyList = array_key_exists("sfl",$_REQUEST)?$_REQUEST["sfl"]:0;
 $showCountryList = array_key_exists("scl",$_REQUEST)?$_REQUEST["scl"]:0;
 $showStateList = array_key_exists("ssl",$_REQUEST)?$_REQUEST["ssl"]:0;
 $editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
 
 $collData = Array();
 $collList = Array();
 $collManager = new CollectionProfileManager();
 $collManager->setCollectionId($collId);

 $isEditable = 0;
 if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditable = 1;
 }
 
 if($isEditable){
 	if(array_key_exists("collsubmit",$_REQUEST)){
 		$collEditArr = Array();
 		$collEditArr["collectioncode"] = $_REQUEST["collectioncode"];
 		$collEditArr["collectionname"] = $_REQUEST["collectionname"];
 		$collEditArr["briefdescription"] = $_REQUEST["briefdescription"];
 		$collEditArr["fulldescription"] = $_REQUEST["fulldescription"];
 		$collEditArr["homepage"] = $_REQUEST["homepage"];
 		$collEditArr["contact"] = $_REQUEST["contact"];
 		$collEditArr["email"] = $_REQUEST["email"];
 		$collManager->submitCollEdits($collEditArr);
 	}
 }
 
 if($collId){
 	$collData = $collManager->getCollectionData();
 }
 else{
 	$collList = $collManager->getCollectionList();
 }
 
 
?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collData?$collData["collectionname"]:"") ; ?> Collection Profiles</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<meta name="keywords" content="Natural history collections,<?php echo ($collData?$collData["collectionname"]:""); ?>" />
	<script language=javascript>
		
		function toggleById(target){
		  	var obj = document.getElementById(target);
			if(obj.style.display=="none" || obj.style.display==""){
				obj.style.display="block";
			}
		 	else {
		 		obj.style.display="none";
		 	}
		}
	</script>
	
</head>

<body <?php if($editMode) echo "onload=\"toggleById('colleditor');\"";?>>

	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu)?$collections_misc_collprofilesMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($collections_misc_collprofilesCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_misc_collprofilesCrumbs;
		echo " <b>".($collData?$collData["collectionname"]:"Collection Profile")."</b>";
		echo "</div>";
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
	<?php 
		if($collList){
			echo "<h1>$defaultTitle Collections </h1>";
			echo "<div style='margin:10px;'>Select a collection to see full details. </div>";
			echo "<table style='margin:10px;'>";
			foreach($collList as $cId => $collArr){
				echo "<tr><td style='text-align:center;vertical-align:top;'>";
				echo "<img src='../../".$collArr["icon"]."' style='border-size:1px;height:30;width:30;' />";
				echo "<br/>".$collArr["collectioncode"];
				echo "</td><td>";
				echo "<a href='collprofiles.php?collid=".$cId."'><h3>".$collArr["collectionname"]."</h3></a>";
				echo "<div style='margin:10px;'><div>".$collArr["briefdescription"]."</div>";
				echo "<div style='margin-top:5px;'><b>Contact:</b> ".$collArr["contact"]." (".str_replace("@","&lt;at&gt;",$collArr["email"]).")</div>";
				echo "<div style='margin-top:5px'><b>Home Page:</b> <a href='".$collArr["homepage"]."'>".$collArr["homepage"]."</a></div></div>";
				echo "<div style='margin:5px 0px 15px 10px;'><a href='collprofiles.php?collid=".$cId."'>More Information</a></div>";
				echo "</td></tr>";
				echo "<tr><td colspan='2'><hr/></td></tr>";
			}
			echo "</table>";
		}
		elseif($collData){
	?>
			<?php if($isEditable){?>
				<div style="float:right;cursor:pointer;" onclick="toggleById('colleditor');" title="Toggle Editing Functions">
				<img style='border:0px;' src='../../images/edit.png'/>
				</div>
			<?php }?>
			<h1><?php echo $collData["collectionname"];?></h1>
			<div style='margin:10px;'>
				<div><?php echo $collData["briefdescription"];?></div>
				<div style='margin-top:5px;'><b>Contact:</b> <?php echo $collData["contact"]." (".str_replace("@","&lt;at&gt;",$collData["email"]);?>)</div>
				<?php 
					if($collData["homepage"]) echo "<div style='margin-top:5px;'><b>Home Page:</b> <a href='".$collData["homepage"]."'>".$collData["homepage"]."</a></div>";
					if($collData["uploaddate"]) echo "<div style='margin-top:5px;'><b>Last Upload Date:</b> ".$collData["uploaddate"]."</div>"; 
				?>
				<div style="margin-top:5px;">
					<b>Index Herbariorum Link:</b> 
					<a href="http://sweetgum.nybg.org/ih/herbarium_list.php?QueryName=DetailedQuery&StartAt=1&QueryPage=/ih/index.php&Restriction=NamPartyType='IH Herbarium'&col_NamOrganisationAcronym=<?php echo $collData["collectioncode"]; ?>">
						<?php echo $collData["collectioncode"]; ?>
					</a>
				</div>
				<?php if($collData["institutionname"]){ ?>
					<div style="float:left;font-weight:bold;">Address:&nbsp;</div>
					<div style="float:left;">
						<?php 
						echo "<div>".$collData["institutionname"].($collData["institutioncode"]?" (".$collData["institutioncode"].")":"")."</div>";
						if($collData["address1"]) echo "<div>".$collData["address1"]."</div>";
						if($collData["address2"]) echo "<div>".$collData["address2"]."</div>";
						if($collData["city"]) echo "<div>".$collData["city"].", ".$collData["stateprovince"]."&nbsp;&nbsp;&nbsp;".$collData["postalcode"]."</div>";
						if($collData["country"]) echo "<div>".$collData["country"]."</div>";
						if($collData["phone"]) echo "<div>".$collData["phone"]."</div>";
						?>
					</div>
				<?php } ?>
				<div style="clear:both;">
					<ul style='margin-top:10px;'>
						<li><?php echo $collData["recordcnt"];?> total records</li>
						<li><?php echo $collData["georefcnt"]." (".(round(100*$collData["georefcnt"]/$collData["recordcnt"]));?>%) georeferenced</li>
						<li><?php echo $collData["familycnt"];?> families</li>
						<li><?php echo $collData["generacnt"];?> genera</li>
						<li><?php echo $collData["speciescnt"];?> species</li>
					</ul>
				</div>
				<?php if($isEditable){ ?>
				<div id="colleditor" style="display:none;">
					<div>
						<form action='collprofiles.php' method='get' name='colleditorform'>
							<fieldset style="background-color:#FFF380;">
								<legend><b>Edit Collection Information</b></legend>
								<div>
									Collection Code:
									<input type="text" name="collectioncode" value="<?php echo $collData["collectioncode"];?>" style="width:75px;"/>
								</div>	
								<div>
									Collection Name: 
									<input type="text" name="collectionname" value="<?php echo $collData["collectionname"];?>" style="width:300px;"/>
								</div>
								<div>
									Brief Description (300 character max): 
									<textarea rows="2" cols="45" name="briefdescription" maxsize="300"><?php echo $collData["briefdescription"];?></textarea>
								</div>
								<div>
									Full Description (1000 character max): 
									<textarea rows="3" cols="45" name="fulldescription" maxsize="1000"><?php echo $collData["fulldescription"];?></textarea>
								</div>
								<div>
									Homepage:
									<input type="text" name="homepage" value="<?php echo $collData["homepage"];?>" style="width:300;"/>
								</div>
								<div>
									Contact: 
									<input type="text" name="contact" value="<?php echo $collData["contact"];?>" style="width:200;"/>
								</div>
								<div>
									Email:
									<input type="text" name="email" value="<?php echo $collData["email"];?>" style="width:200;"/>
								</div>
								<div>
									<input type="hidden" name="collid" value="<?php echo $collId;?>">
									<input type="submit" name="collsubmit" value="Submit Edits" />
								</div>
							</fieldset>
						</form>
					</div>
					<div>
						<form action='collprofiles.php' method='get' name='datauploadform'>
							<fieldset style="background-color:#FFF380;">
								<legend><b>Upload Specimens from Source Collection</b></legend>
								<h2>Programming of the GUI interface is in the works!</h2>
							</fieldset>
						</form>
					</div>
				</div>
				<?php }?>

				<div style='margin:20px 0px 20px 20px;width:200px;background-color:#FFFFCC;' class='fieldset'>
					<div class='legend'>Other Options</div>
					<div><a href='collprofiles.php?collid=<?php echo $collId;?>&sfl=1'>Show Family Coverages</a></div>
					<div><a href='collprofiles.php?collid=<?php echo $collId;?>&scl=1'>Show Country Coverages</a></div>
					<div><a href='collprofiles.php?collid=<?php echo $collId;?>&ssl=1'>Show State Coverages</a></div>
				</div>
			</div>
			
			
			
	<?php 
			if($showFamilyList){
				echo "<div style='margin:20px 0px 20px 30px;width:300px;' class='fieldset'>";
				echo "<div class='legend'>Family Coverage</div>";
				$familyCntArr = $collManager->getFamilyRecordCounts();
				echo "<ul>";
				foreach($familyCntArr as $fam=>$cnt){
					$percentage = round(100*$cnt/$collData["recordcnt"]);
					if($percentage > 10){
						$cntStr = $percentage."%";
					}
					else{
						$cntStr = $cnt;
					}
					echo "<li>$fam (".$cntStr.")</li>";
				}
				echo "</ul>";
				echo "<div>* Number in parentheses are record counts. Counts greater than 10% of total are shown as percentages.</div>";
				echo "<div>";
			}
			if($showCountryList){
				echo "<div style='margin:20px 0px 20px 30px;width:300px;' class='fieldset'>";
				echo "<div class='legend'>Country Coverage</div>";
				$countryCntArr = $collManager->getCountryRecordCounts();
				echo "<ul>";
				foreach($countryCntArr as $country => $cnt){
					$percentage = round(100*$cnt/$collData["recordcnt"]);
					if($percentage > 10){
						$cntStr = $percentage."%";
					}
					else{
						$cntStr = $cnt;
					}
					echo "<li>$country (".$cntStr.")</li>";
				}
				echo "</ul>";
				echo "<div>* Number in parentheses are record counts. Counts greater than 10% of total are shown as percentages.</div>";
				echo "<div>";
			}
			if($showStateList){
				echo "<div style='margin:20px 0px 20px 30px;width:300px;' class='fieldset'>";
				echo "<div class='legend'>State (US) Coverage</div>";
				$stateCntArr = $collManager->getStateRecordCounts();
				echo "<ul>";
				foreach($stateCntArr as $state => $cnt){
					$percentage = round(100*$cnt/$collData["recordcnt"]);
					
					if($percentage > 10){
						$cntStr = $percentage."%";
					}
					else{
						$cntStr = $cnt;
					}
					echo "<li>$state (".$cntStr.")</li>";
				}
				echo "</ul>";
				echo "<div>* Number in parentheses are record counts. Counts greater than 10% of total are shown as percentages.</div>";
				echo "<div>";
			}
		}
		else{
			echo "<div>Unknown Error: Unable to display Collection Information.</div>";
		}
	?>

	</div>
	
	<?php
		include($serverRoot.'/footer.php');
	?>

</body>
</html>