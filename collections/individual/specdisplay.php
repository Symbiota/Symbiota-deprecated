<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
 include_once("../../util/dbconnection.php");
 include_once("../../util/symbini.php");
 
 $collId = array_key_exists("collid",$_REQUEST)?trim($_REQUEST["collid"]):"";
 $dbpk = array_key_exists("dbpk",$_REQUEST)?trim($_REQUEST["dbpk"]):"";
 $gui = array_key_exists("gui",$_REQUEST)?trim($_REQUEST["gui"]):"";
 
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<title><?php echo $defaultTitle; ?> Detailed Collection Record Information</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo "default.css"; ?>" type="text/css" />
	<script language="javascript">
		//<![CDATA[
		function toggle(target){
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
		//]]>
	</script>
</head>

<body>

<?php
	include_once("../../util/headermini.php");
	
	$indManager = new IndividualRecord();
	$row = $indManager->getData($gui,$collId,$dbpk);
	$displayLocality = dsasfa;
?>
	<div style="float:left;margin:15px;text-align:center;font-weight:bold;">
		<img border='1' height='50' width='50' src='../../<?php echo $row["icon"]; ?>'/><br />
		<?php echo $row["collectioncode"]; ?>
	</div>
	<div style="float:left;margin:25px;">
		<span style="font-size:18px;font-weight:bold;vertical-align:60%;">
			<?php echo $row["collectionname"]; ?>
		</span>
	</div>
	<div style="position:relative;">
		<div style="float:left;"><b>Family:</b> <?php echo $row["family"];?></div>
		<div style="float:right;"><b>Accession #:</b> <?php echo $row["catalognumber"]; ?></div>
	</div>
	<div>
		<b>Taxon: </b>
		<?php echo $row["identificationqualifier"]." ";?>
		<i><?php echo $row["sciname"];?></i> 
		<?php echo $row["authoryearofscientificname"]?>
	</div>
	<div>
		<b>Notes: </b>
		<?php echo $row["taxonnotes"]." ";?>
	</div>
	<?php if($row["identifiedby"]) {?>
		<div style="margin-left:10px;">
			<b>Det: </b>
			<?php echo $row["identifiedby"].($row["dateidentified"]?" (".$row["dateidentified"].")":"");?>
		</div>
	<?php }?>
	<div style="margin-top:10px;">
		<?php 
			echo $row["country"].($row["stateprovince"]?", ".$row["stateprovince"]:"").($row["county"]?", ".$row["county"]:"");
			if($row["localitysecurity"] == 1 || $viewLocality || in_array($row->CollectionCode,$this->uRights)){
				?>
	            <div>
	            	<?php echo $row->Locality; ?>
	            </div>
				<?php 
	            if($row->DecimalLatitude && $row->DecimalLongitude){
		            ?>
					<div>
						<?php 
							echo $latDecimal."&nbsp;&nbsp;".$longDecimal;
			            	if($row->CoordinateUncertaintyInMeters){
			            		echo "&nbsp;&nbsp;&nbsp;(+-".trim($row->CoordinateUncertaintyInMeters)." meters)";
			            	}
			            ?>
		            </div>
		            <?php 
	            }
	            $geoDatum = trim($row->GeodeticDatum);
	            $coordSource = trim($row->GeoreferenceSources);
	            $geoRemarks = trim($row->georeferenceremarks); 
	            if($geoDatum || ($coordSource || $coordSource)){
		            echo "<div>";
	                if($geoDatum) echo $geoDatum;
					if($geoDatum && $geoRemarks) echo "; ";
	                if($geoRemarks) echo trim($row->georeferenceremarks);
					if($geoRemarks && $coordSource) echo "; ";
	                if($coordSource) echo "Source: ".$coordSource;
	                echo "</div>";
	            }
	            if($row->VerbatimCoordinates){
		            echo "<div>".$row->VerbatimCoordinates."</div>";
	            }
                $elevStr = $row->MinimumElevationInMeters;
                $verbatimElevation = $row->VerbatimElevation;
                if($row->MaximumElevationInMeters){
                	$elevStr .= ($elevStr?"-":"").$row->MaximumElevationInMeters;
                }
				if($verbatimElevation) $elevStr = $verbatimElevation;
                if($elevStr) echo "<div><b>Elevation:</b> ".$elevStr."m.</div>";
			}
			
		?> 
	</div>
			"s.collector,s.othercollectors,s.collectornumber,".
			"IFNULL(s.earliestdatecollected,s.verbatimcollectingdate) AS earliestdatecollected,".
			"s.latestdatecollected,s.fieldnotes,s.attributes,s.habitat,".
			"s.assocspp,s.remarks,s.cultivationstatus,s.herbariumacronym,s.duplicatecount,".
			"s.typestatus,s.,s.dbpk ".
			"FROM collections AS c INNER JOIN specimens AS s ON c.CollID = s.CollID ";
	
            $collNum = $row->CollectorNumber;
            $this->outputVec[] = "<div><span style=''><b>Collector:</b> ".$row->Collector."</span><span style='margin-left:".(204 - strlen($row->Collector))."px'><b>Collection #:</b> ".$collNum."</span></div>";

            $collDate = $row->EarliestDateCollected;
            if($row->LatestDateCollected) $collDate .= " - ".$row->LatestDateCollected;
            if(!$collDate) $collDate = $row->VerbatimCollectingDate;
            $this->outputVec[] = "<div><b>Date Collected:</b> ".$collDate."</div>";
            $others = $row->OtherCollectors;
            if($others) $this->outputVec[] = "<div><b>Additional Collectors:</b> ".$others."</div>";
            $phen = $row->Phenology;
            $chrom = $row->ChromosomeNumber;
            $phenChrom = "";
            if($phen) $phenChrom = "<b>Phenology:</b> ".$phen."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            if($chrom) $phenChrom .= "<b>Chromosome #:</b> ".$chrom;
            if($phenChrom) $this->outputVec[] = "<div>".$phenChrom."</div>";
			
            $habitat = $row->Habitat; 
            $cult = $row->CultivationStatus;
            if($cult && $cult == "-1") $habitat .= ($habitat?"; ":"")."cultivated";
            if($habitat) $this->outputVec[] = "<div><b>Habitat:</b> ".$habitat."</div>";
            $assocSpp = $row->AssocSpp;
            if($assocSpp) $this->outputVec[] = "<div><b>Associated Species:</b> <i>".$assocSpp."</i></div>";
            $descr = $row->Attributes; 
            if($descr) $this->outputVec[] = "<div><b>Description:</b> ".$descr."</div>";
            $notes = $row->FieldNotes;
            $remark = $row->Remarks;
            $notes =($notes&&$remark?"; ":"").$remark;
            if($notes) $this->outputVec[] = "<div><b>Notes:</b> ".$notes."</div>";
            $typeStatus = $row->TypeStatus;
            if($typeStatus) $this->outputVec[] = "<div><b>Type Status:</b> ".$typeStatus."</div>";
            $country = $row->Country; 
            $state = $row->StateProvince;
            $county = $row->County;
            $local = $country.($country?"; ":"");
            $local .= $state.($state?"; ":"");
            $local .= $county.($county?"; ":"");
            $local = substr($local, 0, strlen($local) - 2);
            $this->outputVec[] = "<div><b>Locality:</b> ".$local."</div>";
            $secur = $row->LocalitySecurity;
            if(!$secur) $secur = 1;
            $this->outputVec[] = "<div style='margin-left:15px;'>";
            if($secur < 2 || $this->isAdmin || in_array($row->CollectionCode,$this->uRights)){
			}
			else{
	            $this->outputVec[] = "<div style='color:red;'>This species has a sensitive status.</div>";
	            $this->outputVec[] = "<div>For more information, please contact collection manager (see email below).</div>";
			}
            $this->outputVec[] = "</div>";
			$this->addImages();



<?php 
	if($row["individualurl"]){
		$indUrl = $row["individualurl"];
		$indUrl = str_replace("--PK--",$row["dbpk"],$indUrl);
        echo "<div>".$row["collectionname"]." <a href='".$indUrl."'> display page</a></div>";
	}
	echo "<div>For more information on this specimen, please contact ";
	echo "<a class='bodylink' href='mailto:".$row["email"]."'>".$row["collectionname"]." (".$row["email"].")</a>";
	echo "</div>";
?>

<?php 
	if(array_key_exists("uid",$paramsArr) && $userRights){
?>
		<div id='voucherlinker' style='margin-top:15px;'>
		<?php 
   		$voucherTid = $indManager->getTid();
   		$voucherGui = $indManager->getGui();
   		if($voucherTid && $voucherGui){
			$clArr = $indManager->getChecklists($paramsArr["uid"]);
			if($clArr){
			?>
			<div class='voucheredit' style="display:block;">
				<span onclick="javascript: toggle('voucheredit');">
					<img src='../../images/plus.gif'>
				</span>
				Show Voucher Editing Box
			</div>
			<div class='voucheredit' style="display:none;">
				<div>
					<span onclick="javascript: toggle('voucheredit');">
						<img src='../../images/minus.gif'>
					</span>
					Hide Voucher Editing Box
				</div>
				<fieldset style='margin:5px 0px 0px 0px;'>
	    			<legend>Voucher Assignment:</legend>
					<form action='../../checklists/tools/vouchers.php'>
						<div style='margin:5px 0px 0px 10px;'>
							Add as voucher to checklist: 
							<input name='vgui' type='hidden' value='<?php echo $voucherGui; ?>'>
							<input name='tid' type='hidden' value='<?php echo $voucherTid; ?>'>
							<select name='clid'>
			  					<option value='0'>Select a Checklist</option>
								<?php 
								$clid = (array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0);
					  			foreach($clArr as $clKey => $clValue){
					  				echo "<option value='".$clKey."' ".($clid==$clKey?"SELECTED":"").">$clValue</option>\n";
								}
								?>
							</select>
						</div>
						<div style='margin:5px 0px 0px 10px;'>
							Notes: 
							<input name='vnotes' type='text' size='50' title='Viewable to public'>
						</div>
						<div style='margin:5px 0px 0px 10px;'>
							Editor Notes: 
							<input name='veditnotes' type='text' size='50' title='Viewable only to checklist editors'>
						</div>
						<div style='margin:5px 0px 0px 10px;'>
							<input type='submit' name='submit' value='Add Voucher'>
						</div>
					</form>
				</fieldset>
			</div>
		<?php 
			}
    	}
    	else{
    		?>
    		<div style='font-weight:bold;'>Unable to use this specimen record as a voucher due to:</div>
    		<ul>
    		<?php 
    		if(!$voucherTid) echo "<li>Scientific name is not in Taxonomic Thesaurus (name maybe misspelled)";
    		if(!$voucherGui) echo "<li>Global Unique Identifier is null (does specimen have an assigned accession number)";
    		?>
    		</ul>
    		<?php 
			echo "<div>Contact <a href=\"mailto:seinetAdmin@asu.edu?subject=bad voucher specimen?body=gui ".$voucherGui."%0Atid ".$voucherTid."%0AcollId ".$collId."%0AcollectionCode ".$collectionCode."%0Adbpk ".$pk."\">seinetAdmin@asu.edu</a> to resolve this issue.</div>";
		}
		?>
		</div>
		<?php 
	}
	include_once("../../util/footer.php");
	?>

</body>
</html> 

<?php
//$indManager->printDefaultLabelDivs();
//$indManager->printCss();

 class IndividualRecord {

	private $con;
	private $tid;
	private $gui;
    private $checklistRights = Array();
    private $isAdmin = false;
    
 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 		$this->setUserRights();
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
    public function getData($gui,$collId,$dbpk){
    	//Get Specimen record
		$sql = "SELECT c.collid, IFNULL(s.collectioncode,c.collectioncode) AS collcode,".
			"c.collectionname, c.homepage, c.individualurl, c.contact, c.email, c.icon,".
			"IFNULL(s.globaluniqueidentifier,IFNULL(s.catalognumber,s.catalognumbernumeric)) AS catalognumber,".
			"s.family,s.sciname,s.authoryearofscientificname,s.identificationqualifier,s.taxonnotes,".
			"s.identifiedby,s.dateidentified,s.country,s.stateprovince,s.county,s.locality,s.decimallatitude,".
			"s.decimallongitude,s.geodeticdatum,s.coordinateuncertaintyinmeters,s.georeferenceremarks,s.verbatimcoordinates,".
			"s.minimumelevationinmeters,s.verbatimelevation,".
			"s.maximumelevationinmeters,s.collector,s.othercollectors,s.collectornumber,".
			"IFNULL(s.earliestdatecollected,s.verbatimcollectingdate) AS earliestdatecollected,".
			"s.latestdatecollected,s.fieldnotes,s.attributes,s.habitat,".
			"s.assocspp,s.remarks,s.cultivationstatus,s.herbariumacronym,s.duplicatecount,".
			"s.typestatus,s.localitysecurity,s.dbpk ".
			"FROM collections AS c INNER JOIN specimens AS s ON c.CollID = s.CollID ";
		if($gui){
			$sql .= "WHERE s.GlobalUniqueIdentifier = '".$gui."'";
		}
		elseif($collId && $dbpk){
			$sql .= "WHERE s.DBPK = '".$dbpk."' AND c.CollID = ".$collId;
		}
		else{
            echo "<div id='errdiv'>ERROR: record variable not supplied (</div>";
			return;
		}
		//echo "SQL: ".$sql;
		
		$result = $this->con->query($sql);
		$row = $result->fetch_assoc();
        $result->close();
		if($row){
			return $row;
		}
		else{
            echo "<div id='errdiv'>ERROR: record not found (</div>";
			return;
		}
    }
        
    private function addImages($gui){
        $imgSql = "SELECT ti.url, ti.notes FROM taxaimages ti ".
			"WHERE (ti.specimengui = '".$gui."') ORDER BY ti.sortsequence";
        $cnt = 0;
        $result = $this->con->query($imgSql);
		$rowCnt = $result->num_rows;
		if($rowCnt) echo "<div id='imagediv' style='margin:15px;position:relative;'><div><hr/></div>"; 
		while($row = $result->fetch_object()){
			$imgUrl = $row->url;
			if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
				$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
			}
            if($imgUrl){
            	$cnt++;
              	echo "<div id='image' style='float:left;'>";
            	echo "<a href='".$imgUrl."'><img border=1 width='150' src='".$imgUrl."'></a>";
              	echo "</div>";
            }
        }
		if($rowCnt) echo "</div>"; 
        $result->close();
    }
    
 	public function getChecklists($uid){
 		$returnArr = Array();
		if($this->isAdmin){
			//Get all public checklist names
			$sql = "SELECT DISTINCT checklists.Name, checklists.CLID ".
				"FROM (checklists INNER JOIN chklstprojlink ON checklists.CLID = chklstprojlink.clid) ".
				"INNER JOIN projects ON chklstprojlink.pid = projects.pid ".
				"WHERE checklists.clid < 500 AND (checklists.Access = 'public' or checklists.uid = ".$uid.") ORDER BY checklists.Name";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$returnArr[$row->CLID] = $row->Name;
			}
			$result->close();
		}
		elseif($this->checklistRights){
			$sql = "SELECT DISTINCT checklists.Name, checklists.CLID ".
				"FROM checklists WHERE checklists.clid IN(".implode(",",$this->checklistRights).") OR checklists.uid = ".$uid." ORDER BY checklists.Name";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$returnArr[$row->CLID] = $row->Name;
			}
			$result->close();
		}
		return $returnArr;
 	}
 	
 	private function setUserRights(){
 		global $userRights;
 		if($isAdmin) $this->isAdmin = true;
 		foreach($userRights as $value){
 			$value = strtolower($value);
 			if(strpos($value, "cl") === 0 && strpos($value, "-admin")){
 				$this->checklistRights[] = str_replace(array("cl","-admin"),"",$value);
 			}
 		}
 	}
 	
 	public function getTid(){
 		return $this->tid;
 	}
 	
 	public function getGui(){
 		return $this->gui;
 	}

	function LatLonPointUTMtoLL($northing, $easting, $zone=12) {
		$d = 0.99960000000000004; // scale along long0
		$d1 = 6378137; // Polar Radius
		$d2 = 0.0066943799999999998;
		
		$d4 = (1 - sqrt(1 - $d2)) / (1 + sqrt(1 - $d2));
		$d15 = $easting - 500000;
		$d16 = $northing;
		$d11 = (($zone - 1) * 6 - 180) + 3;
		$d3 = $d2 / (1 - $d2);
		$d10 = $d16 / $d;
		$d12 = $d10 / ($d1 * (1 - $d2 / 4 - (3 * $d2 * $d2) / 64 - (5 * pow($d2,3) ) / 256));
		$d14 = $d12 + ((3 * $d4) / 2 - (27 * pow($d4,3) ) / 32) * sin(2 * $d12) + ((21 * $d4 * $d4) / 16 - (55 * pow($d4,4) ) / 32) * sin(4 * $d12) + ((151 * pow($d4,3) ) / 96) * sin(6 * $d12);
		$d13 = rad2deg($d14);
		$d5 = $d1 / sqrt(1 - $d2 * sin($d14) * sin($d14));
		$d6 = tan($d14) * tan($d14);
		$d7 = $d3 * cos($d14) * cos($d14);
		$d8 = ($d1 * (1 - $d2)) / pow(1 - $d2 * sin($d14) * sin($d14), 1.5);
		$d9 = $d15 / ($d5 * $d);
		$d17 = $d14 - (($d5 * tan($d14)) / $d8) * ((($d9 * $d9) / 2 - (((5 + 3 * $d6 + 10 * $d7) - 4 * $d7 * $d7 - 9 * $d3) * pow($d9,4) ) / 24) + (((61 + 90 * $d6 + 298 * $d7 + 45 * $d6 * $d6) - 252 * $d3 - 3 * $d7 * $d7) * pow($d9,6) ) / 720);
		$d17 = rad2deg($d17); // Breddegrad (N)
		$d18 = (($d9 - ((1 + 2 * $d6 + $d7) * pow($d9,3) ) / 6) + (((((5 - 2 * $d7) + 28 * $d6) - 3 * $d7 * $d7) + 8 * $d3 + 24 * $d6 * $d6) * pow($d9,5) ) / 120) / cos($d14);
		$d18 = $d11 + rad2deg($d18); // Længdegrad (Ø)
		return array('lat'=>$d17,'lng'=>$d18);
	}

 	public function printDefaultLabelDivs(){
    	$specimenMap = Array();
    	$metaSql = "SHOW COLUMNS FROM specimens";
    	$metaRs = $this->con->query($metaSql);
    	while($metaRow = $metaRs->fetch_object()){
    		echo "<div id=\"".$metaRow->Field."-label\" class=\"labeldiv\">$metaRow->Field<div>\n";
    	}
 		$metaRs->close();
 	}

 	public function printCss(){
    	$specimenMap = Array();
    	$metaSql = "SHOW COLUMNS FROM specimens";
    	$metaRs = $this->con->query($metaSql);
    	while($metaRow = $metaRs->fetch_object()){
    		echo "#".$metaRow->Field."{\n";
    		echo "\tdisplay:\tblock;\n";
    		echo "}\n";
    	}
 		$metaRs->close();
 	}
 }

?>

