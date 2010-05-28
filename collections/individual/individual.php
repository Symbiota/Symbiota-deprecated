<?php
 header("Content-Type: text/html; charset=ISO-8859-1");
 //error_reporting(E_ALL);
 include_once("../../util/dbconnection.php");
 include_once("../../util/symbini.php");
 
 $collId = array_key_exists("collid",$_REQUEST)?trim($_REQUEST["collid"]):"";
 $collectionCode = array_key_exists("collcode",$_REQUEST)?trim($_REQUEST["collcode"]):"";
 $pk = array_key_exists("pk",$_REQUEST)?trim($_REQUEST["pk"]):"";
 $gui = array_key_exists("gui",$_REQUEST)?trim($_REQUEST["gui"]):"";

 $indManager = new IndividualRecord();
 if($collId) $indManager->setCollId($collId); 
 if($collectionCode) $indManager->setCollectionCode($collectionCode);
 if($pk) $indManager->setDbpk($pk);
 if($gui) $indManager->setGui($gui);

 $htmlVec = Array();
 $htmlVec = $indManager->getData();
 
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title><?php echo $defaultTitle; ?> Detailed Collection Record Information</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<script type="text/javascript">

	    function checkVoucherForm(f){
			var clTarget = f.elements["clid"].value; 
	        if(clTarget == "0"){
	            window.alert("Please select a checklist");
	            return false;
	        }
            return true;
	    }

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
	</script>
</head>

<body>
	<!-- This is inner text! -->
	<div id="innertext">
		<div style="float:left;margin:15px;text-align:center;font-weight:bold;">
			<img border='1' height='50' width='50' src='../../<?php echo $indManager->getIcon(); ?>'/><br/>
			<?php echo $indManager->getCollectionCode(); ?>
		</div>
		<div style="float:left;margin:25px;">
			<span style="font-size:18px;font-weight:bold;vertical-align:60%;">
				<?php echo $indManager->getCollectionName(); ?>
			</span>
		</div>
		<div style="clear:both;margin:20px;">
	        <?php
			foreach($htmlVec as $value){
	                echo $value."\n";
	               }
			if(!$htmlVec){
	               echo "<div><b>There is a problem retrieving data. <br>Please try again later.</b></div>";
	           }
	
	           echo "<div>&nbsp;</div>";
	           if($indManager->getIndividualUrl()){
				$indUrl = $indManager->getIndividualUrl();
				$indUrl = str_replace("--PK--",$indManager->getDbpk(),$indUrl);
	           	echo "<div>".$indManager->getCollectionName()." <a href='".$indUrl."'> display page</a></div>";
	           }
			echo "<div>For more information on this specimen, please contact <a class='bodylink' href='mailto:".$indManager->getContactEmail()."'>".$indManager->getContactName()." (".$indManager->getContactEmail().")</a></div>";
		        
	    	if($symbUid && $userRights){
				?>
	    		<div style='margin-top:15px;'>
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
							<?php
				    		$voucherTid = $indManager->getTid();
				    		$voucherGui = $indManager->getGui();
				    		if($voucherTid && $voucherGui){
								$clArr = $indManager->getChecklists($paramsArr["uid"]);
								if($clArr){
							?>
							<form action="../../checklists/clsppeditor.php" onsubmit="return checkVoucherForm(this);">
								<div style='margin:5px 0px 0px 10px;'>
								Add as voucher to checklist: 
								<?php 
								echo "<input name='vgui' type='hidden' value='".$voucherGui."'>\n";
								echo "<input name='tid' type='hidden' value='".$voucherTid."'>\n";
								echo "<select id='clid' name='clid'>\n";
					  			echo "<option value='0'>Select a Checklist</option>\n";
					  			echo "<option value='0'>--------------------------</option>\n";
					  			$clid = (array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0);
					  			foreach($clArr as $clKey => $clValue){
					  				echo "<option value='".$clKey."' ".($clid==$clKey?"SELECTED":"").">$clValue</option>\n";
								}
								echo "</select>\n";
								?>
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
									<input type='submit' name='action' value='Add Voucher'>
								</div>
							</form>
							<?php 
								}
				    		}
				    		else{
				    			?>
				    			<div style='font-weight:bold;'>
				    				Unable to use this specimen record as a voucher due to:
				    			</div>
				    			<ul>
				    			<?php 
				    				if(!$voucherTid){
				    					echo "<li>Scientific name is not in Taxonomic Thesaurus (name maybe misspelled)";
				    					
				    				}
				    				if(!$voucherGui){
				    					echo "<li>Global Unique Identifier is null (does specimen have an assigned accession number)";
				    				}
				    			?>
				    			</ul>
								<div>
									Contact 
									<a href="mailto:seinetAdmin@asu.edu?subject=bad voucher specimen?body=gui: <?php echo $voucherGui."%0Atid: ".$voucherTid."%0AcollId ".$collId."%0AcollectionCode ".$collectionCode."%0Adbpk ".$pk;?>">
										seinetAdmin@asu.edu
									</a> 
									to resolve this issue.
								</div>
								<?php 
							}
							?>
						</fieldset>
					</div>
				</div>
			<?php 
			}
			?>
		</div>
	</div>
</body>
</html> 

<?php
 
 class IndividualRecord {
    
    private $gui;
    private $collectionCode;
    private $collId;
    private $dbpk;
    private $tid;
    
    private $collectionName = "";
    private $icon = "";
    private $homepage = "";
    private $contactName = "";
    private $contactEmail = "";
    private $individualUrl;
    
	private $con;
    private	$outputVec = Array();
    private $uRights = Array();
    private $checklistRights = Array();
    private $isAdmin = false;
    
 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 		$this->setUserRights();
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
    
 	public function getChecklists($uid){
 		$returnArr = Array();
		if($this->isAdmin){
			//Get all public checklist names
			$sql = "SELECT DISTINCT c.Name, c.CLID ".
				"FROM (fmchecklists c INNER JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid) ".
				"INNER JOIN fmprojects p ON cpl.pid = p.pid ".
				"WHERE c.type = 'static' AND (c.Access = 'public' or c.uid = ".$uid.") ".
				"ORDER BY c.Name";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$returnArr[$row->CLID] = $row->Name;
			}
			$result->close();
		}
		elseif($this->checklistRights){
			$sql = "SELECT DISTINCT c.Name, c.CLID FROM fmchecklists c ".
				"WHERE c.type = 'static' AND (c.clid IN(".implode(",",$this->checklistRights).") OR c.uid = ".$uid.") ".
				"ORDER BY c.Name";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$returnArr[$row->CLID] = $row->Name;
			}
			$result->free();
		}
		return $returnArr;
 	}
 	
 	private function setUserRights(){
 		global $userRights, $isAdmin;
 		$this->uRights = $userRights;
 		if($isAdmin) $this->isAdmin = true;
 		foreach($this->uRights as $value){
 			if(strpos($value, "CL") === 0 && strpos($value, "-admin")){
 				$replaceTxt = array("CL","-admin");
 				$this->checklistRights[] = str_replace($replaceTxt,"",$value);
 			}
 		}
 	}
 	
 	public function setGui($g){
		$this->gui = $g;
	}
	
	public function getGui(){
		return $this->gui;
	}
	
	public function getTid(){
		return $this->tid;
	}
	
	public function setCollId($id){
		$this->collId = $id;
	}
	
	public function setCollectionCode($code){
		$this->collectionCode = $code;
	}
	
	public function setDbpk($pk){
		$this->dbpk = $pk;
	}
	
	public function getDbpk(){
		return $this->dbpk;
	}
    
    public function getData(){
		$sql = "SELECT c.CollID, IFNULL(o.CollectionCode,c.CollectionCode) AS CollectionCode, ".
			"c.CollectionName, c.Homepage, c.IndividualUrl, c.Contact, c.email, c.icon, o.occurrenceID, ".
			"o.CatalogNumber, o.occurrenceRemarks, o.TidInterpreted, o.Family, o.SciName, o.scientificNameAuthorship, o.IdentificationQualifier, o.IdentifiedBy, ".
			"DATE_FORMAT(o.DateIdentified,'%d %M %Y') AS DateIdentified, o.Country, o.StateProvince, o.County, o.Locality, o.MinimumElevationInMeters, o.MaximumElevationInMeters, o.VerbatimElevation, ".
			"o.DecimalLatitude, o.DecimalLongitude, o.GeodeticDatum, o.CoordinateUncertaintyInMeters, o.GeoreferenceSources, ".
			"o.verbatimCoordinates, o.verbatimCoordinateSystem, ".
			"DATE_FORMAT(o.eventDate,'%d %M %Y') AS eventDate, MAKEDATE(o.year,o.enddayofyear) AS eventDateEnd, o.verbatimEventDate, ".
			"o.recordedBy, o.associatedCollectors, o.recordNumber, o.FieldNotes, o.Attributes, o.TypeStatus, o.DBPK, o.LocalitySecurity, ".
			"o.Habitat, o.associatedTaxa, o.reproductiveCondition, o.CultivationStatus, o.ownerInstitutionCode, o.otherCatalogNumbers, ".
			"o.reproductiveCondition ".
			"FROM omcollections AS c INNER JOIN omoccurrences o ON c.CollID = o.CollID WHERE ";
		if($this->gui) {
			$sql .= "o.occurrenceID = '".$this->gui."'";
		}
		else{
			$sqlWhere = "";
			if($this->dbpk){
				$sqlWhere .= "AND o.DBPK = '".$this->dbpk."' ";
			}
			if($this->collId){
				$sqlWhere .= "AND c.CollID = ".$this->collId." ";
			}
			if($this->collectionCode){
				$sqlWhere .= "AND c.CollectionCode = '".$this->collectionCode."' ";
			}
			if($sqlWhere){
				$sql .= substr($sqlWhere,4);
			}
			else{
            	$this->outputVec[] = "ERROR: Collection acronym was null or empty";
				return $this->outputVec;
			}
		}
		//echo "SQL: ".$sql;

		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			if(!$this->gui) $this->gui = $row->occurrenceID;
			$this->tid = $row->TidInterpreted;
			$this->contactEmail = $row->email;
			$this->contactName = $row->Contact;
			$this->collectionName = $row->CollectionName;
			if(!$this->collectionCode) $this->collectionCode = $row->CollectionCode;
			$this->icon = $row->icon;
			$this->homepage = $row->Homepage;
			$this->individualUrl = $row->IndividualUrl;
			$this->dbpk = $row->DBPK;

			$accNum = $row->CatalogNumber;
			$this->outputVec[] = "<div><div style='float:left;'><b>Family:</b> ".$row->Family."</div><div style='float:right;'><b>Accession #:</b> ".$accNum."</div></div>";
			$this->outputVec[] = "<div style='clear:both;'>";
			$cf = $row->IdentificationQualifier;
            $this->outputVec[] = "<div style='float:left;'><b>Taxon:</b> ".($cf?$cf." ":"")."<i>".$row->SciName."</i> ".$row->scientificNameAuthorship."</div>";
			if($row->otherCatalogNumbers) $this->outputVec[] = "<div style='float:right;padding:2px;background-color:#EEEEEE;border:1px solid #AAC7E9;'><b>".$row->ownerInstitutionCode."</b> ".$row->otherCatalogNumbers."</div>";
			$this->outputVec[] = "</div>";
            $deter = $row->IdentifiedBy;
            $deterDate = $row->DateIdentified;
            if($deter && $deterDate) $deter .= " (".$deterDate.")";
            if($deter) $this->outputVec[] = "<div style='clear:both;'><b>Determiner:</b> ".$deter."</div>";
            $collNum = $row->recordNumber;
            $this->outputVec[] = "<div style='clear:both;'><b>Collector:</b> ".$row->recordedBy.($collNum?" (#".$collNum.")":"")."</div>";

            $collDate = $row->eventDate;
            if($row->eventDateEnd) $collDate .= " - ".$row->eventDateEnd;
            if(!$collDate) $collDate = $row->verbatimEventDate;
            $this->outputVec[] = "<div><b>Date Collected:</b> ".$collDate."</div>";
            $others = $row->associatedCollectors;
            if($others) $this->outputVec[] = "<div><b>Additional Collectors:</b> ".$others."</div>";
            $phen = $row->reproductiveCondition;
            if($phen) $this->outputVec[] = "<div><b>Phenology:</b> ".$phen."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>";
			
            $habitat = $row->Habitat; 
            $cult = $row->CultivationStatus;
            if($cult && $cult == "-1") $habitat .= ($habitat?"; ":"")."cultivated";
            if($habitat) $this->outputVec[] = "<div><b>Habitat:</b> ".$habitat."</div>";
            $assocSpp = $row->associatedTaxa;
            if($assocSpp) $this->outputVec[] = "<div><b>Associated Species:</b> <i>".$assocSpp."</i></div>";
            $descr = $row->Attributes; 
            if($descr) $this->outputVec[] = "<div><b>Description:</b> ".$descr."</div>";
            $notes = $row->FieldNotes;
            $remark = $row->occurrenceRemarks;
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
	            $this->outputVec[] = "<div>".$row->Locality."</div>";
	            $latDecimal = $row->DecimalLatitude;
	            $longDecimal = $row->DecimalLongitude;
	            if($latDecimal && $longDecimal){
	            	echo "<div>";
	            	$this->outputVec[] = $latDecimal."&nbsp;&nbsp;".$longDecimal;
		            if($row->CoordinateUncertaintyInMeters) $this->outputVec[] = "&nbsp;&nbsp;&nbsp;(+-".trim($row->CoordinateUncertaintyInMeters)." meters)";
	            	echo "</div>";
	            }
	            $datum = $row->GeodeticDatum;
	            $coordSource = $row->GeoreferenceSources;
	            if(($datum) || ($coordSource)){
		            $this->outputVec[] = "<div>";
	                if($datum) $this->outputVec[] = trim($datum);
	                if($coordSource) $this->outputVec[] = "Source: ".trim($coordSource);
	                $this->outputVec[] = "</div>";
	            }
	            $verbatimCoords = $row->verbatimCoordinates;
	            if($verbatimCoords){
		            $this->outputVec[] = "<div>".$verbatimCoords.($row->verbatimCoordinateSystem?" (".$row->verbatimCoordinateSystem.")":"")."</div>";
	            }
                $elevStr = $row->MinimumElevationInMeters;
                $elevMeterMax = $row->MaximumElevationInMeters;
                $verbatimElevation = $row->VerbatimElevation;
                if($elevMeterMax){
                	$elevStr .= ($elevStr?"-":"").$elevMeterMax;
                }
				if(!$elevStr && $verbatimElevation) $elevStr = $verbatimElevation;
                if($elevStr) $this->outputVec[] = "<div><b>Elevation:</b> ".$elevStr."m.</div>";
			}
			else{
	            $this->outputVec[] = "<div style='color:red;'>This species has a sensitive status.</div>";
	            $this->outputVec[] = "<div>For more information, please contact collection manager (see email below).</div>";
			}
            $this->outputVec[] = "</div>";
			$this->addImages();
		}
        else{
        	$this->outputVec[] = "<h1>Record was not located.</h1>";
        }
        $result->close();
 		return $this->outputVec;
    }
        
    private function addImages(){
    	if($this->gui){
	        $imgSql = "SELECT ti.url, ti.notes FROM images ti ".
				"WHERE (ti.specimengui = '".$this->gui."') ORDER BY ti.sortsequence";
	        $cnt = 0;
	        $result = $this->con->query($imgSql);
			$imgArr = Array();
			while($row = $result->fetch_object()){
				$imgUrl = $row->url;
				if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
					$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
				}
	            if($imgUrl){
	            	$cnt++;
	              	$imgArr[] = "<div style='float:left;'>";
	            	$imgArr[] = "<a href='".$imgUrl."'><img border=1 width='150' src='".$imgUrl."'></a>&nbsp;";
	              	$imgArr[] = "</div>";
	            }
	        }
			$result->free();
			if($imgArr){
				$this->outputVec[] = "<div><hr/></div><div style='margin:15px;position:relative;'>";
				$this->outputVec = array_merge($this->outputVec, $imgArr);
				$this->outputVec[] = "</div><div style='clear:both;'><hr/></div>";
			}
    	}
    }
    
    public function getCollectionCode(){
    	return $this->collectionCode;
    }

    public function getCollectionName(){
    	return $this->collectionName;
    }

    public function getIcon(){
    	return $this->icon;
    }
    
    public function getHomepage(){
    	return $this->homepage;
    }
    
    public function getIndividualUrl(){
    	return $this->individualUrl;
    }
 
    public function getContactName(){
    	return $this->contactName;
    }
 
    public function getContactEmail(){
    	return $this->contactEmail;
    }
 }

?>

