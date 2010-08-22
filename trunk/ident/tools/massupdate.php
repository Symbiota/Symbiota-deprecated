<?php
error_reporting(E_ALL);
//set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT']."" );
include_once("../../util/dbconnection.php");
include_once("../../util/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">
 <head>
  <title><?php echo $defaultTitle; ?> Character Mass Updater</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script language="JavaScript">
		
		var addStr = ";";
		var removeStr = ";";
		
		function addAttr(target){
			var indexOfAdd = addStr.indexOf(";"+target+";");
			if(indexOfAdd == -1){
				addStr += target + ";";
			}
			else{
				removeAttr(target);
			}
		}
		
		function removeAttr(target){
			var indexOfRemove = removeStr.indexOf(";"+target+";");
			if(indexOfRemove == -1){
				removeStr += target + ";";
			}
			else{
				addAttr(target);
			}
		}
	
		function submitAttrs(){
			var sform = document.getElementById("submitform");
			var a;
			var r;
			
			if(addStr.length > 1){
				var addAttrs = addStr.split(";");
				for(a in addAttrs)
				{
					var addValue = addAttrs[a];
					if(addValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","a[]");
						newInput.setAttribute("value",addValue);
						sform.appendChild(newInput);
					}
				}
			}
	
			if(removeStr.length > 1){
				var removeAttrs = removeStr.split(";");
				for(r in removeAttrs)
				{
					var removeValue = removeAttrs[r];
					if(removeValue.length > 1){
						var newInput = document.createElement("input");
						newInput.setAttribute("type","hidden");
						newInput.setAttribute("name","r[]");
						newInput.setAttribute("value",removeValue);
						sform.appendChild(newInput);
					}
				}
			}
			sform.submit();
		}
			
	</script>
</head>
<body>

<?php
/*
 * Created on Jul 9, 2006
 *
 * By E.E. Gilbert
 */

 	
 	$editable = false;
 	if($isAdmin || array_key_exists("KeyEditor",$userRights)){
 		$editable = true;
 	}
 	 	
 	$muManager = new MassUpdateManager();

	$removeAttrs = Array();
	$addAttrs = Array();

	 $action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
	 $clFilter = array_key_exists("clf",$_REQUEST)?$_REQUEST["clf"]:""; 
	 $taxonFilter = array_key_exists("tf",$_REQUEST)?$_REQUEST["tf"]:""; 
	 $generaOnly = array_key_exists("generaonly",$_REQUEST)?$_REQUEST["generaonly"]:""; 
	 $cidValue = array_key_exists("cid",$_REQUEST)?$_REQUEST["cid"]:""; 
	 $removeAttrs = array_key_exists("r",$_REQUEST)?$_REQUEST["r"]:""; 
	 $addAttrs = array_key_exists("a",$_REQUEST)?$_REQUEST["a"]:""; 
	 $projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
	 $langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 
	 
	if($projValue) $muManager->setProj();
	if($langValue) $muManager->setLang();
	if($clFilter) $muManager->setClFilter($clFilter);
	if($taxonFilter) $muManager->setTaxonFilter($taxonFilter);
	if($generaOnly) $muManager->setGeneraOnly($generaOnly);
	if($cidValue) $muManager->setCid($cidValue);

	//Set username
 	if(array_key_exists("un",$paramsArr)) $muManager->setUsername($paramsArr["un"]);
	
	if($addAttrs || $removeAttrs){
		if($removeAttrs) $muManager->setRemoves($removeAttrs);
		if($addAttrs) $muManager->setAdds($addAttrs);
		$muManager->deleteInheritance();
		$muManager->processAttrs();
		$muManager->resetInheritance();
	}

	$displayLeftMenu = (isset($ident_tools_massupdateMenu)?$ident_tools_massupdateMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($ident_tools_massupdateCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $ident_tools_massupdateCrumbs;
		echo "</div>";
	}
	
?>
	<!-- This is inner text! -->
	<div id="innertext">
<?php 	
	if($editable){
		?>
		<table height='500' border='0'>
			<tr>
				<td width='200' valign='top'>
		  			<form id="setupform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
						<div style='font-weight:bold;'>Checklist:</div>
	  					<select name="clf"> 
							<option value='all'>Select a Checklist</option>
					 		<?php 
							echo "<option value='all' ".($clFilter=="all"?"SELECTED":"").">Checklist Filter Off (all taxa)</option>\n";
					 		$selectList = $muManager->getClQueryList();
					 			foreach($selectList as $key => $value){
					 				echo "<option value='".$key."' ".($key==$clFilter?"SELECTED":"").">$value</option>\n";
					 			}
					 		?>
	  					</select>
	  					<div style='font-weight:bold;'>Taxon:</div>
						<select name="tf">
					  		<?php 
				 				echo "<option value='0'>-- Select a Family or Genus --</option>\n";
				 				echo "<option value='0'>--------------------------</option>\n";
						  		$selectList = $muManager->getTaxaQueryList();
					  			foreach($selectList as $value){
					  				echo "<option ".($value==$taxonFilter?"SELECTED":"").">$value</option>\n";
					  			}
					  		?>
						</select>
						<div>
							<input type="checkbox" name="generaonly" value="1" <?php if($generaOnly) echo "checked"; ?> /> 
							Exclude Species Rank
						</div>
						<div>
							<input type='submit' name='action' id='list' value='Submit Criteria' />
						</div>
	 					<hr size="2" />
				 		<?php 
				 			if($clFilter && $taxonFilter){
				 				$cList = $muManager->getCharList();			//Array(Heading => Array(CID => CharName))
								foreach($cList as $h => $charData){
									echo "<div style='margin-top:1em;font-size:125%;'>$h</div>\n";
									ksort($charData);
									foreach($charData as $cidKey => $charValue){
										echo "<div> <input name='cid' type='radio' value='".$cidKey."' ".($cidKey == $cidValue?"checked":"").">$charValue</div>\n";
									}
								}
				 				echo "<input type='submit' name='action' id='list' value='Submit Criteria'>\n";
				 			}
							if($projValue) echo "<input type='hidden' name='proj' value='".$projValue."' />\n";
							if($langValue) echo "<input type='hidden' name='lang' value='".$langValue."' />\n";
				 			
				 		?>
					</form>
		     	</td>
			  	<td width="20" background="../../images/brown_hor_strip.gif">
			  	</td>
		     	<td valign="top">
		     	<?php
		     	$inheritStr = "<span title='State Inherited from parent taxon'> (I)</span>";
		     	if($clFilter && $taxonFilter && $cidValue){
		     		?>
		     		<table border='1'>
		     		<?php 
		     		$sList = $muManager->getStates();
		     		$tList = $muManager->getTaxaList();				//Array(familyName => Array(SciName => Array("TID" => TIDvalue,"csArray" => Array(csValues => Inheritance))))
						//List CharState columns and replace spaces with line breaks
		     		echo "<tr><td/>";
		     		foreach ($sList as $cs => $csName){
							$csNameNew = str_replace(" ","<br/>",$csName);
		     			$sList[$cs] = $csName;
		     			echo "<td align='center' width='50px'>$csNameNew</td>\n";
		     		}
						echo "</tr>\n";
						$count = 0;
						ksort($tList);
		     		foreach($tList as $fam => $sciNameArr){
							//Show Family first
		     			if(array_key_exists($fam,$sciNameArr)){
		      			$famArr = $sciNameArr[$fam];
								echo "<tr><td><span style='margin-left:1px'><a href='editor.php?taxon=".$fam."&action=Get+Character+Info' target='_blank'>$fam</a></span></td>\n";
								$t = $famArr["TID"];
								$csValues = $famArr["csArray"];
								foreach($sList as $cs => $csName){
									$isSelected = false;
									$isInherited = false;
									if(array_key_exists($cs,$csValues)){
										$isSelected = true;
										if($csValues[$cs]) $isInherited = true;
									}
									if($isSelected && !$isInherited){
										//State is true and not inherited for this taxon
										$jsStr = "javascript: removeAttr('".$t."-".$cs."');";
									}
									else{
										//State is false for this taxon or it is inherited
										$jsStr = "javascript: addAttr('".$t."-".$cs."');";	
									}
									echo "<td align='center' width='15'><input type=\"checkbox\" name=\"csDisplay\" onclick=\"".$jsStr."\" ".($isSelected && !$isInherited?"CHECKED":"")." title=\"".$csName."\"/>".($isInherited?"(I)":"")."</td>\n";
								}
								echo "</tr>\n";
								unset($sciNameArr[$fam]);
		     			}
		
							//Go through taxa names and list
							ksort($sciNameArr);
							foreach($sciNameArr as $sciName => $sciArr){
								$display = $sciArr["display"];
								echo "<tr><td><a href='editor.php?taxon=".$sciName."&action=Get+Character+Info' target='_blank'>$display</a></td>\n";
								$t = $sciArr["TID"];
								$csValues = $sciArr["csArray"];
								foreach($sList as $cs => $csName){
									$isSelected = false;
									$isInherited = false;
									if(array_key_exists($cs,$csValues)){
										$isSelected = true;
										if($csValues[$cs]) $isInherited = true;
									}
									if($isSelected && !$isInherited){
										//State is true and not inherited for this taxon
										$jsStr = "javascript: removeAttr('".$t."-".$cs."');";
									}
									else{
										//State is false for this taxon or it is inherited
										$jsStr = "javascript: addAttr('".$t."-".$cs."');";	
									}
									echo "<td width='10' align='center'><div ".($isSelected?"style='text-weight:bold;'":"")."><input type=\"checkbox\" name=\"csDisplay\" onclick=\"".$jsStr."\" ".($isSelected && !$isInherited?"CHECKED":"")." title=\"".$csName."\"/>".($isInherited?$inheritStr:"")."</div></td>\n";
								}
								echo "</tr>\n";
		
								//Occationally show column names and submit button
								$count++;
								if($count%13 == 0){
				      		echo "<tr><td align='right' colspan='".(count($sList)+1)."'><input type='submit' name='action' value='Save Changes' onclick='javascript: submitAttrs();'></td></tr>\n";
								}
							}
							echo "<tr><td align='right' colspan='".(count($sList)+1)."'><input type='submit' name='action' value='Save Changes' onclick='javascript: submitAttrs();'></td></tr>\n";
		     		}
					?>
		  		</table>
		  	</td>
		   </tr>
			<tr>
				<td colspan="3">
				<?php
		     	}
		     	else if($clFilter && $taxonFilter){
		     		echo "<h3>Select a morphological character and click 'Set Species List' button</h3>";
		     	}
		     	else{
		     		echo "<h3 sytle='margin-left:20px;'>Select a checklist, family or genus, and click 'Display Characters' button</h3>";
		     	}
				?>
				</td>
			</tr>
		 </table>
		 <form id="submitform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<?php
				if($clFilter) echo "<input type='hidden' name='clf' value='".$clFilter."' />\n";
				if($taxonFilter) echo "<input type='hidden' name='tf' value='".$taxonFilter."' />\n";
				if($cidValue) echo "<input type='hidden' name='cid' value='".$cidValue."' />\n";
				if($projValue) echo "<input type='hidden' name='proj' value='".$projValue."' />\n";
				if($langValue) echo "<input type='hidden' name='lang' value='".$langValue."' />\n";
			?>
		</form>
		<?php 
 }
 else{  //Not editable or writable connection is not set
	echo "<h1>You do not have authority to edit character data.</h1> <h3>You must first login to the system.</h3>";
 }
?>
	</div>
<?php  
 include($serverRoot."/util/footer.php");
 
?>
</body>
</html>


<?php

class MassUpdateManager{
	
	private $con;
	private $taxonNameFilter;
	private $tidFilter;
	private $clidFilter;
	private $generaOnly;
	private $cid;
	private $adds = Array();
	private $removes = Array();
	private $childrenStr;
	private $tidUsed = Array();
	private $proj;
	private $lang = "English";
  	private $username;
	
 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("write");
 	}

 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function setTaxonFilter($name){
		$this->taxonNameFilter = $name;
	}
	
	public function setClFilter($clid){
		$this->clidFilter = $clid;
	}

	public function setGeneraOnly($genOnly){
		$this->generaOnly = $genOnly;
	}

	public function setCID($c){
		$this->cid = $c;
	}
	
	public function setProj($p){
		$this->proj = $p;
	}
	
	public function setLang($l){
		$this->lang = $l;
	}

	public function setUsername($uname){
    	$this->username = $uname;
  	}

  	public function getClQueryList(){
		$returnList = Array();
		$sql = "SELECT cl.CLID, cl.Name FROM (fmchecklists cl ";
		if($this->proj) {
			$sql .= "INNER JOIN fmchklstprojlink cpl ON cl.CLID = cpl.clid) INNER JOIN fmprojects p ON cpl.pid = p.pid ".
				"WHERE p.projname = '$this->proj' ";
		}
		else{
			$sql .= ") ";
		}
		$sql .= "ORDER BY cl.Name";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$returnList[$row->CLID] = $row->Name;
		}
		$result->free();
		return $returnList;
	}
	
	public function getTaxaQueryList(){
		$returnList = Array();
		$sql = "SELECT DISTINCT ts.UpperTaxonomy, ts.Family, t.UnitName1 
			FROM (((fmprojects p INNER JOIN fmchklstprojlink cpl ON p.pid = cpl.pid) 
			INNER JOIN fmchklsttaxalink ctl ON cpl.clid = ctl.CLID) ".
			"INNER JOIN taxstatus ts ON ctl.tid = ts.tid) 
			INNER JOIN taxa t ON ts.tidaccepted = t.TID ";
		$sqlWhere = "";
		if($this->clidFilter && $this->clidFilter != "all") $sqlWhere .= "ctl.CLID = ".$this->clidFilter." ";
		if($this->proj) $sqlWhere .= ($sqlWhere?"AND ":"")."(p.projname = '".$this->proj."') ";
		if($sqlWhere) $sql = $sql."WHERE ".$sqlWhere;
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$upper = $row->UpperTaxonomy;
			$fam = $row->Family;
			$genus = $row->UnitName1;
			if(!in_array($upper,$returnList)) $returnList[] = $upper;
			if(!in_array($fam,$returnList)) $returnList[] = $fam;
			if(!in_array($genus,$returnList)) $returnList[] = $genus;
		}
		$result->close();
		sort($returnList);
		return $returnList;
	}

	public function getCharList(){
		$headingArray = Array();		//Heading => Array(CID => CharName)
		if($this->taxonNameFilter){
			$strFrag = implode(",",$this->getParents($this->taxonNameFilter));
			/*$sql = "SELECT DISTINCT charnames.Heading, charnames.CID, charnames.CharName ".
				"FROM ((chartaxalink INNER JOIN characters ON chartaxalink.CID = characters.CID) INNER JOIN charnames ON characters.CID = charnames.CID) ".
				"LEFT JOIN chardependance ON characters.CID = chardependance.CID ".
				"WHERE (chartaxalink.Relation = 'include') ".
				"AND (characters.Type='UM' Or characters.Type='OM') AND (charnames.Language='".$this->lang."') ".
				"AND (chartaxalink.TID In ($strFrag)) ".
				"ORDER BY charnames.Heading, characters.SortSequence";*/
			$sql = "SELECT DISTINCT ch.headingname, c.CID, c.CharName ".
				"FROM ((kmcharacters c INNER JOIN kmchartaxalink ctl ON c.CID = ctl.CID) ".
				"INNER JOIN kmcharheading ch ON c.hid = ch.hid) ".
				"LEFT JOIN kmchardependance cd ON c.CID = cd.CID ".
				"WHERE ch.language = 'English' AND (ctl.Relation = 'include') ".
				"AND (c.chartype='UM' Or c.chartype='OM') AND (c.defaultlang='".$this->lang."') ".
				"AND (ctl.TID In ($strFrag)) ".
				"ORDER BY c.hid, c.SortSequence";
			//echo $sql;
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$headingArray[$row->headingname][$row->CID] = $row->CharName;
			}
			$result->free();
		}
		return $headingArray;
	}
	
	private function getParents($t){
		//Returns a list of parent TIDs, including target 
 		$returnList = Array();
		$targetTaxon = $t;
		while($targetTaxon){
			$sql = "SELECT t.TID, ts.ParentTID FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE ts.taxauthid = 1 AND ".(intval($targetTaxon)?"t.TID = $targetTaxon":"t.SciName = '".$t."'");
			$result = $this->con->query($sql);
		    if ($row = $result->fetch_object()){
					$targetTaxon = $row->ParentTID;
					$tid = $row->TID;
					if(in_array($tid, $returnList)) break;
					$returnList[] = $tid;
		    }
		    else{
		    	break;
		    }
			$result->free();
		}
		return $returnList;
	}

	public function getStates(){
		$stateArr = Array();
		$sql = "SELECT kmcs.CharStateName, kmcs.CS FROM kmcs ".
			"WHERE kmcs.Language = '".$this->lang."' AND kmcs.CID = $this->cid ORDER BY kmcs.SortSequence";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$stateArr[$row->CS] = $row->CharStateName;
		}
		$rs->free();
		return $stateArr;
	}
	
	public function getTaxaList(){
		//Get list of Char States
 		$stateList = Array();
		$sql = "SELECT kmcs.CS, kmcs.CharStateName FROM kmcs WHERE kmcs.CID = $this->cid";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$stateList[$row->CS] = $row->CharStateName; 
	    }
		$result->free();
		
		//Get all Taxa found in checklist 
		$taxaList = Array();
		$parArr = Array();
		$famArr = Array();
		$sql = "SELECT DISTINCT t.TID, ts.Family, t.SciName, ts.ParentTID, t.RankId, d.CID, d.CS, d.Inherited ".
			"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) ".
			"LEFT JOIN (SELECT d.TID, d.CID, d.CS, d.Inherited FROM kmdescr d WHERE (d.CID=".$this->cid.")) AS d ON t.TID = d.TID) ";
		if($this->clidFilter && $this->clidFilter != "all"){
			$sql .= "INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid ";
		}
		$sql .= "WHERE (t.RankId = 220) AND (ts.taxauthid = 1) AND (ts.Family='".$this->taxonNameFilter."' OR t.SciName Like '".$this->taxonNameFilter." %') ";
		if($this->clidFilter && $this->clidFilter != "all"){
			$sql .= "AND (ctl.CLID = $this->clidFilter)" ;
		}
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row1 = $rs->fetch_object()){
			$sciName = $row1->SciName;
			$sciNameDisplay = "<div style='margin-left:6px'>$sciName</div>";
			$family = $row1->Family;
			if(!$this->generaOnly){
				$taxaList[$family][$sciName]["TID"] = $row1->TID;
				$taxaList[$family][$sciName]["display"] = $sciNameDisplay;
				$taxaList[$family][$sciName]["csArray"][$row1->CS] = $row1->Inherited;
			}
			$parTID = $row1->ParentTID;
			if(!in_array($parTID,$parArr)) $parArr[] = $parTID;
			if(!in_array($family,$famArr)) $famArr[] = $family;
		}
		$rs->close();

		//Get all genera and family and add them to list
		$taxaStr = implode(",",$parArr);
		$famStr = implode("','",$famArr);
		$sql = "SELECT DISTINCT t.TID, ts.Family, t.SciName, t.RankId, ts.ParentTID, d.CID, d.CS, d.Inherited ".
		"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ".
		"LEFT JOIN (SELECT di.TID, di.CID, di.CS, di.Inherited FROM kmdescr di ".
		"WHERE (di.CID=$this->cid)) AS d ON t.TID = d.TID ".
		"WHERE (ts.taxauthid = 1 AND (((t.RankId = 180) AND (t.TID IN(".$taxaStr."))) OR (t.SciName IN('$famStr'))))";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$sciName = $row->SciName;
			$rankId = $row->RankId;
			$family = ($rankId == 140?$sciName:$row->Family);
			$sciNameDisplay = "<div style='margin-left:3px'>$sciName</div>";
			$taxaList[$family][$sciName]["TID"] = $row->TID;
			$taxaList[$family][$sciName]["display"] = $sciNameDisplay;
			$taxaList[$family][$sciName]["csArray"][$row->CS] = $row->Inherited;
		}
		$rs->close();
		return $taxaList;
	}

	public function setRemoves($removeArr){
 		foreach($removeArr as $v){
 			if(strlen($v) > 0){
				$t = explode("-",$v);
 				$this->removes[] = "((d.TID = ".$t[0].") AND (d.CID = ".$this->cid.") AND (d.CS = '".$t[1]."'))";
				$this->tidUsed[] = $t[0];
 			}
 		}
	}
	
	public function setAdds($addArr){
 		foreach($addArr as $v){
 			if(strlen($v) > 0){
 				$t = explode("-",$v);
				$tid = $t[0];
				$cs = $t[1];
				$this->tidUsed[] = $tid;
				$this->adds[] = "INSERT INTO kmdescr (TID, CID, CS, Source) VALUES (".$t[0].",".$this->cid.",'".$t[1]."','".$this->username."')";
 			}
 		}
	}
		
	public function processAttrs(){
		if($this->removes){
			//transfer deletes to the descrdeletions table
			$sqlTrans = "INSERT INTO kmdescrdeletions ( TID, CID, Modifier, CS, X, TXT, Inherited, Source, Seq, Notes, InitialTimeStamp, DeletedBy ) ".
			"SELECT d.TID, d.CID, d.Modifier, d.CS, d.X, d.TXT, d.Inherited, ".
			"d.Source, d.Seq, d.Notes, d.DateEntered, '".$this->username."' ".
			"FROM kmdescr d WHERE ".implode(" OR ",$this->removes);
			$this->con->query($sqlTrans);
			
			//delete value from descr
			$sqlStr = "DELETE d.* FROM kmdescr d WHERE ".implode(" OR ",$this->removes);
			$this->con->query($sqlStr);
		}
		
		foreach($this->adds as $v){
			$this->con->query($v);
 		}
	}

	public function deleteInheritance(){
		//delete all inherited children traits for CIDs that will be modified
		$this->setChildrenList();
		$sqlDel = "DELETE FROM kmdescr ".
			"WHERE (TID IN(".$this->childrenStr.")) ".
			"AND (CID = ".$this->cid.") AND (Inherited Is Not Null AND Inherited <> '')";
		$this->con->query($sqlDel);
	}
		
	public function resetInheritance(){
		//set inheritance for target only
		$sqlAdd1 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
			"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
			"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
			"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
			"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
			"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
			"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
			"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (t2.rankid > 140) AND (ts2.tid = ts2.tidaccepted) ".
			"AND (t2.tid IN(".implode(",",$this->tidUsed).")) AND (d1.cid = $this->cid) AND (d2.CID Is Null)";
		$this->con->query($sqlAdd1);
		//echo $sqlAdd1."<br />";

		//Set inheritance for all children of target
		$count = 0;
		do{
			$count++;
			$sqlAdd2 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (d1.cid = $this->cid) AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId >= 180 OR t2.RankId <= 220) AND (t2.tid IN($this->childrenStr)) AND (d2.CID Is Null)";
			//echo $sqlAdd2;
			$this->con->query($sqlAdd2);
		}while($count < 2);
	}

	public function setChildrenList(){
 		//Returns a list of children TID, excluding target TIDs
		$childrenArr = Array();
 		$childrenArr = $this->tidUsed;
		$targetStr = implode(",",$this->tidUsed);
		do{
			//unset($targetList);
			$targetList = Array();
			$sql = "SELECT DISTINCT t.TID FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) AND t.RankId > 140 AND t.RankId <= 220 AND ts.ParentTID In ($targetStr)";
			//echo $sql."<br/>";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$targetList[] = $row->TID;
		    }
			if($targetList){
				$targetStr = implode(",", $targetList);
				$childrenArr = array_merge($childrenArr, $targetList);
			}
		}while($targetList);
		$result->close();
		$this->childrenStr = implode(",",array_unique($childrenArr));
	}
}
?>

	