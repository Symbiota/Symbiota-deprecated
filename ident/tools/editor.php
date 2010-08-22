<?php
/*
 * Created on Jul 20, 2006
 */
include_once("../../util/dbconnection.php");
include_once("../../util/symbini.php");
header("Cache-control: private; Content-Type: text/html; charset=".$charset);
 
 $addValues = Array();
 $removeValues = Array();
 $action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
 $langValue = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:""; 
 $charValue = array_key_exists("char",$_REQUEST)?$_REQUEST["char"]:""; 
 $child1Value = array_key_exists("child1",$_REQUEST)?$_REQUEST["child1"]:""; 
 $child2Value = array_key_exists("child2",$_REQUEST)?$_REQUEST["child2"]:""; 
 $clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:""; 
 $tQueryValue = array_key_exists("tquery",$_REQUEST)?$_REQUEST["tquery"]:""; 
 $tidValue = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:""; 
 $taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
 $addValues = array_key_exists("add",$_REQUEST)?$_REQUEST["add"]:""; 
 $removeValues = array_key_exists("remove",$_REQUEST)?$_REQUEST["remove"]:""; 
 
 $editorManager = new EditorManager();
 if($langValue) $editorManager->setLanguage($langValue);
 $editable = false;
 if($isAdmin || array_key_exists("KeyEditor",$userRights)){
 	$editable = true;
 }
 
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">
 <head>
  <title><?php echo $defaultTitle; ?> Identification Character Editor</title>
  <link rel="stylesheet" href="../../css/main.css" type="text/css" />
<script language="javascript">

	var dataChanged = false;
	
	window.onbeforeunload = verifyClose;
	
	function verifyClose() { 
		if (dataChanged == true) { 
			return "You will lose any unsaved data if you don't first submit your changes!"; 
		} 
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
	
	function showSearch(){
	  document.getElementById("searchDiv").style.display="block";
	  document.getElementById("searchDisplay").style.display="none";
	}
</script>
 </head>
 <body>
 
<?php
	$displayLeftMenu = (isset($ident_tools_editorMenu)?$ident_tools_editorMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($ident_tools_editorCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $ident_tools_editorCrumbs;
		echo "</div>";
	}
	
	
	
?>
<div style="margin:15px;">
<?php 
 if($editable){
	 if($tidValue) $editorManager->setTaxon($tidValue);
	 if($taxonValue) $editorManager->setTaxon($taxonValue);
	 if($addValues) $editorManager->setAddStates($addValues);
	 if($removeValues) $editorManager->setRemoveStates($removeValues);
	
	 //Set username
	 if(array_key_exists("un",$paramsArr)) $editorManager->setUsername($paramsArr["un"]);
	?>
  	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="dataChanged=false;">
	<?php 
	if(($action=="Submit Changes" || $action=="Get Character Info") && (!empty($taxonValue) || !empty($tidValue))){
		//Show character info for selected taxon
 		if($action=="Submit Changes"){
 			$editorManager->processTaxa();
 		}
 		$sn = $editorManager->getTaxonName();
 		if($editorManager->getRankId() > 140){
	  		$sn = "<i>$sn</i>";
 		}
 		echo "<div style='float:right;'>";
 		if($editorManager->getRankId() > 140){
			echo "<a href='editor.php?taxon=".$editorManager->getParentTid()."&action=Get+Character+Info&child1=".$editorManager->getTid().($child1Value?"&child2=$child1Value":"")."'>edit parent</a>&nbsp;&nbsp;";
 		}
		if($child1Value){
			echo "<br><a href='editor.php?taxon=".$child1Value."&action=Get+Character+Info".($child2Value?"&child1=".$child2Value:"")."'>back to child</a>";
		}
		echo "</div>";
 		echo "<h2>$sn</h2>";
		$cList = $editorManager->getCharList();
		$depArr = $editorManager->getCharDepArray();
		$charStatesList = $editorManager->getCharStates();
		if($cList){
			$count = 0;
			$minusGif = "<img src='../../images/minus.gif'>";
			$plusGif = "<img src='../../images/plus.gif'>";
			foreach($cList as $heading => $charArray){ 
				echo "<div style='font-weight:bold; font-size:150%; margin:1em 0em 1em 0em; color:#990000;".($charValue?" display:none;":"")."'>";
				echo "<span class='".$heading."' onclick=\"javascript: toggle('".$heading."');\" style=\"display:none;\">$minusGif</span>";
				echo "<span class='".$heading."' onclick=\"javascript: toggle('".$heading."');\" style=\"display:;\">$plusGif</span>";
				echo " $heading</div>\n";
				echo "<div class='".$heading."' id='".$heading."' style='text-indent:1em;".($charValue?"":" display:none;")."'>";
				foreach($charArray as $cidKey => $charNameStr){
					if(!$charValue || $charValue == $cidKey){
						echo "<div id='chardiv".$cidKey."' style='display:".(array_key_exists($cidKey,$depArr)?"hidden":"block").";'>";
						echo "<div style='margin-top:1em;'><span style='font-weight:bold; font-size:larger;'>$charNameStr</span>\n";
						if($editorManager->getRankId() > 140) echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style='font-size:smaller;'><a href=\"javascript:var popupReference=window.open('editor.php?taxon=".$editorManager->getParentTid()."&action=Get+Character+Info&char=".$cidKey."','technical','toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=600,height=450,left=20,top=20');\">parent</a></span>\n";
						echo "</div>\n";
						echo "<div style='font-size:smaller; text-indent:2.5em;'>Add&nbsp;&nbsp;Remove</div>\n";
						$cStates = $charStatesList[$cidKey];
						foreach($cStates as $csKey => $csValue){
							$testStr = $cidKey."_".$csKey;
							$charPresent = $editorManager->isSelected($testStr);
							$inh = $editorManager->getInheritedStr($testStr);
							$displayStr = ($charPresent?"<span style='font-size:larger;font-weight:bold;'>":"").$csValue.$inh.($charPresent?"</span>":"");
							echo "<div style='text-indent:2em;'><input type='checkbox' name='add[]' ".($charPresent && !$inh?"disabled='true' ":" ")." value='".$testStr."' onChange='dataChanged=true;'/>";
							echo "&nbsp;&nbsp;&nbsp;<input type='checkbox' name='remove[]' ".(!$charPresent || $inh?"disabled='true' ":" ")."value='".$testStr."'  onChange='dataChanged=true;'/>";
							echo "&nbsp;&nbsp;&nbsp;$displayStr</div>\n";
						}
						echo "</div>";
						$count++;
						if($count%3 == 0) echo "<div style='margin-top:1em;'><input type='submit' name='action' value='Submit Changes'/></div>\n";
					}
				}
				echo "</div>\n";
			}
			echo "<div style='margin-top:1em;'><input type='submit' name='action' value='Submit Changes'/></div>\n";
			//Hidden values to maintain values and display mode
			if($charValue){
				echo "<div><br><b>Note:</b> changes made here will not be reflected on child page until page is refreshed.</div>";
				echo "<div><input type='hidden' name='char' value='".$charValue."'/></div>";
			}
			?>
			<div>
				<input type="hidden" name="tid" value="<?php echo $editorManager->getTid(); ?>" />
				<input type="hidden" name="child1" value="<?php echo $child1Value; ?>" />
				<input type="hidden" name="child2" value="<?php echo $child2Value; ?>" />
			</div>
			<?php 
		}
  	}
 	else{
 		echo "<h3>Enter a taxon and checklist combination and hit submit.</h3>";
 	}
	?>
	</form>
 <?php 
 }
 else{  //Not editable or writable connection is not set
	echo "<h1>You do not have authority to edit character data or there is a problem with the database connection.</h1> <h3>Note that you must first login to the system.</h3>";
 }
 ?>
 </div>
 <?php 
 include($serverRoot."/util/footer.php");
?>
 
 </body>
</html>

<?php

class EditorManager{

	private $con;
	private $tid;
	private $taxonName;
	private $chars = Array();
	private $charStates = Array();
	private $selectedStates = Array();
	private $addStates = Array();
	private $removeStates = Array();
	private $family;
	private $parentTid;
	private $hierarchy;
	private $rankId;
	private $username;
	private $language = "English";
	private $charDepArray = Array();
  
 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("write");
 	}

 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function setTaxon($t){
		if(intval($t)){
			$this->tid = $t;
		}
		else{
			$this->taxonName = $t;
		}
		if($this->taxonName){
			$sql = "SELECT t.TID, ts.Family, ts.ParentTID, ts.hierarchystr, t.RankId FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE ts.taxauthid = 1 AND t.SciName = '".$this->taxonName."'";
			$result = $this->con->query($sql);
			if($row = $result->fetch_object()){
				$this->tid = $row->TID;
				$this->family = $row->Family;
				$this->parentTid = $row->ParentTID;
				$this->hierarchy = $row->hierarchystr;
				$this->rankId = $row->RankId;
		    }
		}elseif($this->tid){
			$sql = "SELECT t.SciName, ts.Family, ts.ParentTID, ts.hierarchystr, t.RankId ".
				"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE ts.taxauthid = 1 AND t.TID = ".$this->tid;
			$result = $this->con->query($sql);
			if($row = $result->fetch_object()){
				$this->taxonName = $row->SciName;
				$this->family = $row->Family;
				$this->parentTid = $row->ParentTID;
				$this->hierarchy = $row->hierarchystr;
				$this->rankId = $row->RankId;
		    }
		}
		$result->close();
	}
	
	function getTaxonName(){
		return $this->taxonName;
	}
	
	function getTid(){
		return $this->tid;
	}
	
	function getParentTid(){
		return $this->parentTid;
	}
	
	function getRankId(){
		return $this->rankId;
	}

	function setUsername($uname){
		$this->username = $uname;
	}
	
	function setLanguage($lang){
		$this->language = $lang;
	}
	
	function isSelected($str){
		if($this->selectedStates && array_key_exists($str, $this->selectedStates)){
			return true;
		}
		else{
			return false;
		}
	}
	
	function getInheritedStr($str){
		if($this->selectedStates && array_key_exists($str, $this->selectedStates)){
			return $this->selectedStates[$str];
		}
		else{
			return "";
		}
	}

	public function getCharList(){
		$this->setCharList();
		$this->setSelectedStates();
		return $this->chars;
	}
	
	private function setCharList(){
		//chars Array: HeadingName => (cid => charName)
		$cidArray = Array();
		
		/*$sql = "SELECT cn.CharName, c.CID, cn.Heading, dep.CIDDependance, dep.CSDependance ".
		"FROM ((characters c INNER JOIN chartaxalink ctl ON c.CID = ctl.CID) ".
		"INNER JOIN charnames cn ON c.CID = cn.CID) LEFT JOIN chardependance dep ON c.CID = dep.CID ".
		"WHERE ((c.CID Not In (SELECT DISTINCT chartl.CID FROM chartaxalink chartl WHERE (chartl.TID In ($strFrag)) AND (chartl.Relation='exclude'))) ".
		"AND (c.Type = 'UM' Or c.Type='OM') AND (ctl.TID In ($strFrag)) AND ".
		"(cn.Language='".$this->language."') AND (ctl.Relation='include')) ".
		"ORDER BY c.SortSequence";*/
		$sql = "SELECT c.CharName, c.CID, ch.headingname, dep.CIDDependance, dep.CSDependance ".
		"FROM ((kmcharacters c INNER JOIN kmchartaxalink ctl ON c.CID = ctl.CID) ".
		"INNER JOIN kmcharheading ch ON c.hid = ch.hid) LEFT JOIN kmchardependance dep ON c.CID = dep.CID ".
		"WHERE (ch.language = '".$this->language."' AND (c.CID Not In (SELECT DISTINCT chartl.CID FROM kmchartaxalink chartl ".
		"WHERE (chartl.TID In ($this->hierarchy)) AND (chartl.Relation='exclude'))) ".
		"AND (c.chartype = 'UM' Or c.chartype='OM') AND (ctl.TID In ($this->hierarchy)) AND ".
		"(c.defaultlang='".$this->language."') AND (ctl.Relation='include')) ".
		"ORDER BY c.SortSequence";
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$charKey = $row->CID;
			$cidArray[$charKey] = "";
			$charValue = $row->CharName;
			$charHeading = $row->headingname;
			$pos = strpos($charHeading,":");
			if($pos) $charHeading = substr($charHeading, $pos + 2);
			$this->chars[$charHeading][$charKey] = $charValue;

			//Set CharDependance array values;
			$cidDepValue = $row->CIDDependance;
			$csDepValue = $row->CSDependance;
			if($cidDepValue) $this->charDepArray[$charKey] = $cidDepValue.":".$csDepValue;
			
		}
		if($cidArray) $this->setCharStates(implode(",",array_keys($cidArray)));
		$result->free();
	}
	
	public function getCharDepArray(){
		return $this->charDepArray;
	}

	private function setCharStates($cidStr){
		//cs Array: cid => (cs => charStateName)
		$sql = "SELECT CID, CS, CharStateName FROM kmcs ".
			"WHERE CID In ($cidStr) AND Language = '".$this->language."' ".
		"ORDER BY SortSequence";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$this->charStates[$row->CID][$row->CS] = $row->CharStateName;
	    }
		$result->free();
	}
	
	public function getCharStates(){
		return $this->charStates;
	}

	private function setSelectedStates(){
		//selectedStates Array: cid_cs (ex: 1_3=>" (i)")
		$sql = "SELECT d.CID, d.CS, d.Inherited FROM kmdescr d WHERE (((d.TID) = $this->tid))";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$cid = $row->CID;
			$cs = $row->CS;
			$this->selectedStates[$cid."_".$cs] = ($row->Inherited?" (i)":"");
		}
		$result->free();
	}

	function setAddStates($arr){
		$this->addStates = $arr;
	}
	
	function setRemoveStates($arr){
		$this->removeStates = $arr;
	}

	function processTaxa(){
		$charUsed = Array();
		$rStates = Array();
		$aStates = Array();
		
		//Process $addStates and $removeStates arrays
		$innerArray = Array();
		if($this->removeStates){
			foreach($this->removeStates as $value){
				$tok = explode("_",$value);
				$remCID = $tok[0];
				$remCS = $tok[1];
				if(!array_key_exists($remCID,$rStates)){
					$charUsed[] = $remCID;
				}
				$rStates[$remCID][] = $remCS; 
			}
		}
		if($this->addStates){
			foreach($this->addStates as $value){
				$tok = explode("_",$value);
				$addCID = $tok[0];
				$addCS = $tok[1];
				if(!array_key_exists($addCID,$aStates)){
					$charUsed[] = $addCID;
				}
				$aStates[$addCID][] = $addCS; 
			}
		}
		$charUsedStr = implode(",",$charUsed); 

		if($charUsedStr){
			$targetList = $this->getChildrenList($this->tid);
			$targetStr = implode(",",$targetList);
			$sqlDel = "DELETE FROM kmdescr ".
				"WHERE (Inherited Is Not Null AND Inherited <> '') AND (TID In (".(empty($targetStr)?"":$targetStr.",")."$this->tid".")) ".
				"AND (CID IN ($charUsedStr))";
			//echo "<div>".$sqlDel."</div>";
			$this->con->query($sqlDel);
		}

		//Delete all char/cs combinations in $removeStates of set $tid
		if($rStates){
			$removeStr = "";
			foreach($rStates as $k => $v){
				foreach($v as $innerV){
					$removeStr .= ($removeStr?"OR ":"")."(d.TID=$this->tid And d.CID=$k And d.CS='".$innerV."') ";
				}
			}
			$sqlTrans = "INSERT INTO kmdescrdeletions ( TID, CID, Modifier, CS, X, TXT, Inherited, Source, Seq, Notes, InitialTimeStamp, DeletedBy ) ".
				"SELECT d.TID, d.CID, d.Modifier, d.CS, d.X, d.TXT, d.Inherited, ".
				"d.Source, d.Seq, d.Notes, d.DateEntered, '".$this->username."' ".
				"FROM kmdescr d WHERE $removeStr";
			//echo "<div>".$sqlTrans."</div>";
			$this->con->query($sqlTrans);
			$sqlDel = "DELETE d.* FROM kmdescr d WHERE $removeStr";
			$this->con->query($sqlDel);
		}

		//Add all char/cs combinations in $addStates of set $tid
		if($aStates){
			foreach($aStates as $k => $vec){
				foreach($vec as $innerVec){
					$sqlAdd = "INSERT INTO kmdescr (TID, CID, CS, Source) VALUES ($this->tid, $k, '".$innerVec."', '".$this->username."')";
					$this->con->query($sqlAdd);
				}
			}
		}

		//Send inheritance to all children
		$this->resetInheritance($targetStr, $charUsedStr);
	}

	function resetInheritance($targetStr, $charUsedStr){
		if($charUsedStr){
			//set inheritance for target only
			$sqlAdd1 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.tid = $this->tid) And (d2.CID Is Null)";
			
			$this->con->query($sqlAdd1);

			//Set inheritance for all children of target
			if($this->rankId == 140){
				$sqlAdd2a = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
					"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
					"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
					"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
					"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
					"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
					"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
					"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
					"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
					"AND (t2.RankId = 180) And (d2.CID Is Null) AND (d1.cid IN($charUsedStr))";
				//echo $sqlAdd2a;
				$this->con->query($sqlAdd2a);
			}

			if($this->rankId <= 180){
				$sqlAdd2b = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
					"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
					"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
					"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
					"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
					"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
					"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
					"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
					"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
					"AND (t2.RankId = 220) And (d2.CID Is Null) AND (d1.cid IN($charUsedStr))";
				//echo $sqlAdd2b;
				$this->con->query($sqlAdd2b);
			}
		}
	}

	function getChildrenList($parentID){
		//Returns a list of children excluding target
		$children = Array();
		$targetStr = $this->tid;
		do{
			if(isset($targetList)) unset($targetList);
			$targetList = Array();
			$sql = "SELECT DISTINCT t.TID, t.SciName, ts.Family ".
				"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE ((ts.taxauthid = 1) AND (ts.ParentTID In ($targetStr)) AND (t.rankid <= 220))";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$targetList[] = $row->TID;
		    }
			if($targetList){
				$targetStr = implode(",", $targetList);
				$children = array_merge($children, $targetList);
			}
		}while($targetList);
		$result->close();
		return $children;
	}
	
	function getParentList(){
		//Returns a list of parent TIDs, including target 
 		$parentList = Array();
		$targetTid = $this->tid;
		$parentList[] = $targetTid;
		while($targetTid){
			$sql = "SELECT ts.ParentTID FROM taxstatus ts WHERE ts.taxauthid = 1 AND ts.TID = $targetTid";
			//echo $sql;
			$result=$this->con->query($sql);
		    if ($row = $result->fetch_object()){
					$targetTid = $row->ParentTID;
					if($targetTid) $parentList[] = $targetTid;
		    }
		}
		$result->free();
		return $parentList;
	}
}
?>

	