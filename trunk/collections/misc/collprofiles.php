<?php
//error_reporting(E_ALL);
 include_once("../../util/dbconnection.php");
 include_once("../../util/symbini.php");
 header("Content-Type: text/html; charset=".$charset);

 $collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
 $showFamilyList = array_key_exists("sfl",$_REQUEST)?$_REQUEST["sfl"]:0;
 $showCountryList = array_key_exists("scl",$_REQUEST)?$_REQUEST["scl"]:0;
 $showStateList = array_key_exists("ssl",$_REQUEST)?$_REQUEST["ssl"]:0;
 $editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
 
 $collData = Array();
 $collList = Array();
 $collManager = new CollectionManager();
 $collManager->setCollectionId($collId);

 $isEditable = 0;
 if($isAdmin || in_array("coll-".$collId,$userRights)){
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
	include($serverRoot."/util/header.php");
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
		include($serverRoot."/util/footer.php");
	?>

</body>
</html>
<?php
 
 class CollectionManager {

	private $con;
	private $collId;

 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function setCollectionId($collId){
		$this->collId = $collId;
	}

	public function getCollectionList(){
		$returnArr = Array();
		$sql = "SELECT c.collid, c.CollectionCode, c.CollectionName, c.BriefDescription, ".
			"c.Homepage, c.Contact, c.email, c.icon ".
			"FROM omcollections c ORDER BY c.SortSeq";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->collid]["collectioncode"] = $row->CollectionCode;
			$returnArr[$row->collid]["collectionname"] = $row->CollectionName;
			$returnArr[$row->collid]["briefdescription"] = $row->BriefDescription;
			$returnArr[$row->collid]["homepage"] = $row->Homepage;
			$returnArr[$row->collid]["contact"] = $row->Contact;
			$returnArr[$row->collid]["email"] = $row->email;
			$returnArr[$row->collid]["icon"] = $row->icon;
		}
		$rs->close();
		return $returnArr;
	}
	
	public function getCollectionData(){
		$returnArr = Array();
		$sql = "SELECT i.InstitutionCode, i.InstitutionName, i.Address1, i.Address2, i.City, i.StateProvince, ".
			"i.PostalCode, i.Country, i.Phone, c.collid, c.CollectionCode, c.CollectionName, ".
			"c.BriefDescription, c.FullDescription, c.Homepage, c.Contact, c.email, c.icon, ".
			"cs.recordcnt, cs.familycnt, cs.genuscnt, cs.speciescnt, cs.georefcnt, cs.uploaddate ".
			"FROM omcollections c INNER JOIN omcollectionstats cs ON c.collid = cs.collid ".
			"LEFT JOIN institutions i ON c.iid = i.iid ".
			"WHERE c.collid = $this->collId ORDER BY c.SortSeq";
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr["institutioncode"] = $row->InstitutionCode;
			$returnArr["institutionname"] = $row->InstitutionName;
			$returnArr["address2"] = $row->Address1;
			$returnArr["address1"] = $row->Address2;
			$returnArr["city"] = $row->City;
			$returnArr["stateprovince"] = $row->StateProvince;
			$returnArr["postalcode"] = $row->PostalCode;
			$returnArr["country"] = $row->Country;
			$returnArr["phone"] = $row->Phone;
			$returnArr["collectioncode"] = $row->CollectionCode;
			$returnArr["collectionname"] = $row->CollectionName;
			$returnArr["briefdescription"] = $row->BriefDescription;
			$returnArr["fulldescription"] = $row->FullDescription;
			$returnArr["homepage"] = $row->Homepage;
			$returnArr["contact"] = $row->Contact;
			$returnArr["email"] = $row->email;
			$returnArr["icon"] = $row->icon;
			$returnArr["recordcnt"] = $row->recordcnt;
			$returnArr["familycnt"] = $row->familycnt;
			$returnArr["generacnt"] = $row->genuscnt;
			$returnArr["speciescnt"] = $row->speciescnt;
			$returnArr["georefcnt"] = $row->georefcnt;
			$uDate = "";
			if($row->uploaddate){
				$uDate = $row->uploaddate;
				$month = substr($uDate,5,2);
				$day = substr($uDate,8,2);
				$year = substr($uDate,0,4);
				$uDate = date("j F Y",mktime(0,0,0,$month,$day,$year));
			}
			$returnArr["uploaddate"] = $uDate;
		}
		$rs->close();
		return $returnArr;
	}
	
	public function submitCollEdits($editArr){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = "";
		foreach($editArr as $field=>$value){
			$sql .= ",$field = \"".$value."\"";
		}
		$sql = "UPDATE omcollections SET ".substr($sql,1)." WHERE collid = ".$this->collId;
		//echo $sql;
		$conn->query($sql);
		$conn->close();
	}
	
	public function getFamilyRecordCounts(){
		$returnArr = Array();
		//Specimen count
		$sql = "SELECT o.Family, Count(*) AS cnt ".
			"FROM omoccurrences o GROUP BY o.CollID, o.Family HAVING (o.CollID = $this->collId) AND (o.Family IS NOT NULL) AND o.Family <> '' ".
			"ORDER BY o.Family";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->Family] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}
	
	public function getCountryRecordCounts(){
		$returnArr = Array();
		//Specimen count
		$sql = "SELECT o.Country, Count(*) AS cnt ".
			"FROM omoccurrences o GROUP BY o.CollID, o.Country HAVING (o.CollID = $this->collId) AND o.Country IS NOT NULL AND o.Country <> '' ".
			"ORDER BY o.Country";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->Country] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}

 	public function getStateRecordCounts(){
		$returnArr = Array();
		//Specimen count
		$sql = "SELECT o.StateProvince, Count(*) AS cnt ".
			"FROM omoccurrences o GROUP BY o.CollID, o.StateProvince, o.country ".
			"HAVING (o.CollID = $this->collId) AND (o.StateProvince IS NOT NULL) AND (o.StateProvince <> '') ".
			"AND (o.country = 'USA' OR o.country = 'United States' OR o.country = 'United States of America') ".
			"ORDER BY o.StateProvince";
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->StateProvince] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}
 }

 ?>