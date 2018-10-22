<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class KeyDataManager extends Manager {

	private $sql = '';
	private $relevanceValue;		//Percent (as a decimal) of Taxa that must be coded for a CID to be displayed
	private $keyCon;
	private $taxonFilter;
	private $uid;
	private $clid;
	private $childClidArr = array();
	private $clName;
	private $clTitle;
	private $clAuthors;
	private $clType;
	private $dynamicSql;
	private $charArr = Array();
	private $taxaCount;
	private $lang;
	private $commonDisplay = false;
	private $pid;
	private $dynClid;

	function __construct() {
		parent::__construct();
		$this->relevanceValue = .9;
	}

	public function __destruct(){
		parent::__destruct();
	}


	//$returns an Array of ("chars" => $charArray, "taxa" => $taxaArray)
	//In coming attrs are in the form of: (cid => array of cs)
	public function getData(){
		$charArray = array();
		$taxaArray = array();
		if(($this->clid && $this->taxonFilter) || $this->dynClid){
		    $this->setTaxaListSQL();
			$taxaArray = $this->getTaxaList();
			$charArray = $this->getCharList();
		}
		$returnArray["chars"] = $charArray;
		$returnArray["taxa"] = $taxaArray;
		return $returnArray;
	}

	//returns map: HeadingId => Array(
	//									["HeadingNames"] => Array([language] => HeadingName)
	//									[CID] => Array(
	//									["CharNames"] => Array([language] => CharName)
	//									[cs] => Array(language => CharStateName, "ROOT" => base-div string)
	//								)
	//							)
	public function getCharList(){
		$returnArray = Array();
		//Rate char list: Get list of char that are coded for a percentage of taxa list that is greater than
		$charList = Array();
		$countMin = $this->taxaCount * $this->relevanceValue;
		$loopCnt = 0;
		while(!$charList && $loopCnt < 10){
			$sqlRev = "SELECT tc.CID, Count(tc.TID) AS c FROM ".
				"(SELECT DISTINCT tList.TID, d.CID FROM ($this->sql) AS tList INNER JOIN kmdescr d ON tList.TID = d.TID WHERE (d.CS <> '-')) AS tc ".
				"GROUP BY tc.CID HAVING ((Count(tc.TID)) > $countMin)";
			$rs = $this->conn->query($sqlRev);
			//echo $sqlRev.'<br/>';
			while($row = $rs->fetch_object()){
				$charList[] = $row->CID;
			}
			$countMin = $countMin*0.9;
			$loopCnt++;
		}
		$charList = array_merge($charList,array_keys($this->charArr));

		if($charList){
			//Create sql string and get char record set
			/*$sqlChar = "SELECT DISTINCT cs.CID, cs.CS, cs.CharStateName, cs.Description AS csdescr, charnames.CharName, charnames.Description AS chardescr, ".
				"charnames.Heading, charnames.URL, Count(cs.CS) AS Ct, characters.DifficultyRank, charnames.Language ".
				"FROM ((($this->sql) AS tList INNER JOIN descr ON tList.TID = descr.TID) INNER JOIN cs ON (descr.CS = cs.CS) AND (descr.CID = cs.CID)) INNER JOIN ".
				"(characters INNER JOIN charnames ON characters.CID = charnames.CID) ON (cs.Language = charnames.Language) AND (cs.CID = charnames.CID) ".
				"GROUP BY cs.CID, cs.CS, cs.CharStateName, charnames.CharName, charnames.Heading, charnames.URL, characters.DifficultyRank, charnames.Language, characters.Type ".
				"HAVING (((cs.CID) In (".implode(",",$charList).")) AND ((cs.CS)<>'-') AND ((characters.Type)='UM' Or (characters.Type)='OM') AND characters.DifficultyRank < 3) ".
				"ORDER BY charnames.Heading, characters.SortSequence, cs.SortSequence";*/
			$sqlChar = "SELECT DISTINCT cs.CID, cs.CS, cs.CharStateName, cs.Description AS csdescr, chars.CharName,
				chars.description AS chardescr, chars.hid, chead.headingname, chars.helpurl, Count(cs.CS) AS Ct, chars.DifficultyRank,
				chars.defaultlang FROM (((($this->sql) AS tList INNER JOIN kmdescr d ON tList.TID = d.TID)
				INNER JOIN kmcs cs ON (d.CS = cs.CS)	AND (d.CID = cs.CID)) INNER JOIN kmcharacters chars ON chars.cid = cs.CID)
				INNER JOIN kmcharheading chead ON chars.hid = chead.hid
				GROUP BY chead.language, cs.CID, cs.CS, cs.CharStateName, chars.CharName, chead.headingname, chars.helpurl,
				chars.DifficultyRank, chars.defaultlang, chars.chartype HAVING (chead.language = 'English' AND (cs.CID In (".implode(",",$charList).")) AND (cs.CS <> '-') AND
				(chars.chartype='UM' Or chars.chartype = 'OM') AND chars.DifficultyRank < 3)
				ORDER BY chead.hid,	chars.SortSequence, cs.SortSequence ";
			//echo $sqlChar.'<br/>';
			$result = $this->conn->query($sqlChar);

			//Process recordset
			$langList = Array();
			$headingArray = Array();
			$statesArray = Array();
			if(!$result) return null;
			while($row = $result->fetch_object()){
				$ct = $row->Ct;			//count of how many times the CS was used in this species list
				$charCID = $row->CID;
				if($ct < $this->taxaCount || array_key_exists($charCID,$this->charArr)){		//add to return if stateUseCount is less than taxaCount (ie: state is useless if all taxa code true) or is an attribute selected by user
					$language = $row->defaultlang;
					if(!in_array($language, $langList)) $langList[] = $language;
					$headingName = $row->headingname;
					$headingID = $row->hid;
					$charName = $row->CharName;
					$charDescr = $row->chardescr;
					if($charDescr) $charName = "<span class='charHeading' title='".$charDescr."'>".$charName."</span>";
					$url = $row->helpurl;
					if($url) $charName .= " <a href='$url' border='0' target='_blank'><img src='../images/info.png' width='12' border='0'></a>";
					$cs = $row->CS;
					$charStateName = $row->CharStateName;
					$csDescr = $row->csdescr;
					if($csDescr) $charStateName = "<span class='characterStateName' title='".$csDescr."'>".$charStateName."</span>";
					$diffRank = false;
					if($row->DifficultyRank && $row->DifficultyRank > 1 && !array_key_exists($charCID,$this->charArr)) $diffRank = true;

					//Set HeadingName within the $charArray, if not yet set
					$headingArray[$headingID]["HeadingNames"][$language] = $headingName;

					//Set CharName within the $stateArray, if not yet set
					if(!array_key_exists($headingID, $headingArray) || !array_key_exists($charCID, $headingArray[$headingID]) || !array_key_exists("CharNames", $headingArray[$headingID][$charCID]) || !array_key_exists($language, $headingArray[$headingID][$charCID]["CharNames"])){
						$headingArray[$headingID][$charCID]["CharNames"][$language] = "<div class='dynam'".($diffRank?" style='display:none;' ":" style='display:;'")."><span class='dynamlang' lang='".$language."'".
							($language==$this->lang?" style='display:;'":" style='display:none;'").">&nbsp;&nbsp;".$charName."</span></div>";
					}

					$checked = "";
					if($this->charArr && array_key_exists($charCID,$this->charArr) && in_array($cs,$this->charArr[$charCID])) $checked = "checked";
					if(!array_key_exists($headingID,$headingArray) || !array_key_exists($charCID,$headingArray[$headingID]) || !array_key_exists($cs,$headingArray[$headingID][$charCID]) || !$headingArray[$headingID][$charCID][$cs]["ROOT"]){
						$headingArray[$headingID][$charCID][$cs]["ROOT"] = "<div class='dynamopt'".//($diffRank?" style='display:none;' class='dynam'":" style='display:;'").
							">&nbsp;&nbsp;<input type='checkbox' name='attr[]' id='cb".$charCID."-".$cs."' value='".$charCID."-".$cs."' $checked onclick='document.keyform.submit();' />";
					}

					$headingArray[$headingID][$charCID][$cs][$language] = $charStateName;
				}
			}
			$result->free();
			//Ensures correct sorting and puts html output into returnStrings Array
			$returnArray["Languages"] = $langList;			//Put a list of languages in returnArray
			foreach($headingArray as $HID => $cArray){
				$displayHeading = true;
				$headNameArray = $cArray["HeadingNames"];
				unset($cArray["HeadingNames"]);
				$endStr ="";
				foreach($cArray as $cid => $csArray){
					if(count($csArray) > 2 || array_key_exists($cid,$this->charArr)){
						if($displayHeading){
							$returnArray[] = "<div class='headingname' id='headingname".$HID."' style='font-weight:bold;margin-top:1em;font-size:125%;'>\n";
							foreach($headNameArray as $langValue => $headValue){
								$returnArray[] .= "<span lang='".$langValue."' style='".($langValue==$this->lang?"display:;":"display:none;")."'>$headValue</span>\n";
							}
							$returnArray[] = "</div>\n";
							$returnArray[] = "<div class='heading' id='heading".$HID."' style=''>";
							$endStr = "</div>\n";
						}
						$displayHeading = false;
		//				ksort($csArray);
						$chars = $csArray["CharNames"];
						unset($csArray["CharNames"]);
						$returnArray[] = "<div id='char".$charCID."'>";
						foreach($chars as $names){
							$returnArray[] = $names;
						}
						foreach($csArray as $csKey => $stateNames){
							if(array_key_exists("ROOT",$stateNames)) $returnArray[] = $stateNames["ROOT"];
							unset($stateNames["ROOT"]);
							foreach($stateNames as $csLang => $csValue){
								$returnArray[] = "<span lang='".$csLang."' ".
									($csLang==$this->lang?" style='display:;'":" style='display:none;'").">$csValue</span>";
							}
							$returnArray[] = "</div>";
						}
						$returnArray[] = "</div>";
					}
				}
				if($endStr) $returnArray[] = $endStr;
			}
		}
		return $returnArray;
	}

	//return an array: family => array(TID => DisplayName)
	public function getTaxaList(){
		$taxaList[] = null;
	    unset($taxaList);
	    $sqlTaxa = "";
		if($this->commonDisplay){
			$sqlTaxa = "SELECT innert.tid, innert.Family, IFNULL(v.VernacularName, innert.DisplayName) AS DisplayName, innert.ParentTID ".
				"FROM (".$this->sql.") innert LEFT JOIN (SELECT vern.tid, vern.VernacularName FROM taxavernaculars vern WHERE vern.Language = 'English' AND vern.SortSequence = 1) v ON innert.tid = v.tid ";
		}
		else{
			$sqlTaxa = $this->sql;
		}
		//echo $sqlTaxa.'<br/>';
		$result = $this->conn->query($sqlTaxa);
		$returnArray = array();
		$sppArr = array();
		$count = 0;
	    while ($row = $result->fetch_object()){
			$count++;
	   	$family = $row->Family;
			$tid = $row->tid;
	   	$displayName = $row->DisplayName;
	   	unset($sppArr);
	   	if(array_key_exists($family, $returnArray)) $sppArr = $returnArray[$family];
	   	$sppArr[$tid] = $displayName;
	   	$returnArray[$family] = $sppArr;
	    }
	    $this->taxaCount = $count;
		$result->close();
		return $returnArray;
	}

	public function setTaxaListSQL(){
		if(!$this->sql){
			$sqlFromBase = 'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN taxa t1 ON t.UnitName1 = t1.UnitName1 AND t.UnitName2 = t1.UnitName2 '.
				'INNER JOIN taxstatus ts1 ON ts1.tidaccepted = t1.tid ';
			$sqlWhere = 'WHERE (ts.taxauthid = 1) AND (ts1.taxauthid = 1) AND (t.RankId = 220) AND (ts.tid = ts.tidaccepted) ';
			if($this->dynClid){
				$sqlFromBase .= 'INNER JOIN fmdyncltaxalink clk ON ts.tid = clk.tid ';
				$sqlWhere .= 'AND (clk.dynclid = '.$this->dynClid.') ';
			}
			else{
				if($this->clType == 'dynamic'){
					$sqlFromBase .= 'INNER JOIN omoccurrences o ON ts1.tid = o.TidInterpreted ';
					$sqlWhere .= 'AND ('.$this->dynamicSql.') ';
				}
				else{
					$sqlFromBase .= 'INNER JOIN fmchklsttaxalink clk ON ts1.tid = clk.tid ';
					$clidStr = $this->clid;
					if($this->childClidArr){
						$clidStr .= ','.implode(',',array_keys($this->childClidArr));
					}
					$sqlWhere .= 'AND (clk.clid IN('.$clidStr.')) ';
				}
			}
			//If a taxon limit has been set, add taxon value to sql
			if($this->taxonFilter){
				if($this->taxonFilter == 'All Species'){
					//Do nothing
				}
				else{
					$sqlWhere .= 'AND ((ts.Family = "'.$this->cleanInStr($this->taxonFilter).'") OR (t.UnitName1 = "'.$this->cleanInStr($this->taxonFilter).'")) ';
				}
			}

			//Limit by character attribute selections
			$count = 0;
			if($this->charArr){
				//Create sql string
				foreach($this->charArr as $cid => $states){		//key=cid, value=array of cs
					$count++;
					$sqlFromBase .= 'INNER JOIN kmdescr AS D'.$count.' ON t.TID = D'.$count.'.TID ';
					$stateStr = '';
					foreach($states as $cs){
						 $stateStr.=(empty($stateStr)?'':'OR ').'(D'.$count.'.CS="'.$this->cleanInStr($cs).'") ';
					}
					$sqlWhere.=' AND (D'.$count.'.CID='.$cid.') AND ('.$stateStr.')';
				}
			}
			$this->sql = 'SELECT DISTINCT t.tid, ts.Family, t.SciName AS DisplayName, ts.ParentTID '.$sqlFromBase.$sqlWhere;
			//echo $this->sql;
		}
	}

	//Misc functions
	public function getIntroHtml(){
		$returnStr = "<h2>Please enter a checklist, taxonomic group, and then select 'Submit Criteria'</h2>";
		$returnStr .= "This key is still in the developmental phase. The application, data model, and actual data will need tuning. ".
			"The key has been developed to minimize the exclusion of species due to the ".
			"lack of data. The consequences of this is that a 'shrubs' selection may show non-shrubs until that information is corrected. ".
			"User input is necessary for the key to improve! Please email me with suggestions, comments, or problems: <a href='".$GLOBALS['ADMIN_EMAIL']."'>".$GLOBALS['ADMIN_EMAIL']."</a><br><br>";
		$returnStr .= "<b>Note:</b> If few morphological characters are displayed for a particular checklist, it is likely due to not yet having enough ".
		"morphological data compiled for that subset of species. If you would like to help, please email me at the above address. ";
		return $returnStr;
	}

	public function setProject($projValue){
		if(is_numeric($projValue)){
			$this->pid = $projValue;
		}
	}

	public function getTaxaFilterList(){
		$returnArr = Array();
		$sql = 'SELECT DISTINCT nt.UnitName1, ts.Family ';
		if($this->clid && $this->clType == 'static'){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',array_keys($this->childClidArr));
			}
			$sql .= 'FROM (taxstatus ts INNER JOIN taxa nt ON ts.tid = nt.tid) INNER JOIN fmchklsttaxalink cltl ON nt.TID = cltl.TID WHERE (cltl.CLID IN('.$clidStr.')) ';
		}
		else if($this->dynClid){
			$sql .= 'FROM (taxstatus ts INNER JOIN taxa nt ON ts.tid = nt.tid) INNER JOIN fmdyncltaxalink dcltl ON nt.TID = dcltl.TID WHERE (dcltl.dynclid = '.$this->dynClid.') ';
		}
		else{
			$sql .= "FROM (((taxstatus ts INNER JOIN taxa nt ON ts.tid = nt.tid) ".
				"INNER JOIN fmchklsttaxalink cltl ON nt.TID = cltl.TID) ".
				"INNER JOIN fmchecklists cl ON cltl.CLID = cl.CLID) ".
				"INNER JOIN fmchklstprojlink clpl ON cl.CLID = clpl.clid ".
				"WHERE (clpl.pid = ".$this->pid.") ";
		}
		$sql .= 'AND (ts.taxauthid = 1)';
		//echo $sql.'<br/>'; exit;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$genus = $row->UnitName1;
			$family = $row->Family;
			if($genus) $returnArr[] = $genus;
			if($family) $returnArr[] = $family;
		}

		$result->free();
		$returnArr = array_unique($returnArr);
		natcasesort($returnArr);
		array_unshift($returnArr,"--------------------------");
		array_unshift($returnArr, "All Species");
		return $returnArr;
	}

	public function setClValue($clid){
		$sql = "";
		if($this->dynClid){
			$sql = 'SELECT d.name, d.details, d.type FROM fmdynamicchecklists d WHERE (dynclid = '.$this->dynClid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $row->name;
				$this->clType = $row->type;
			}
			$result->free();
		}
		else{
			if(is_numeric($clid)){
				$this->clid = $clid;
				$this->setMetaData();
				//Get children checklists
				$sqlBase = 'SELECT ch.clidchild, cl2.name '.
					'FROM fmchecklists cl INNER JOIN fmchklstchildren ch ON cl.clid = ch.clid '.
					'INNER JOIN fmchecklists cl2 ON ch.clidchild = cl2.clid '.
					'WHERE (cl2.type != "excludespp") AND cl.clid IN(';
				$sql = $sqlBase.$this->clid.')';
				do{
					$childStr = "";
					$rsChild = $this->conn->query($sql);
					while($r = $rsChild->fetch_object()){
						$this->childClidArr[$r->clidchild] = $r->name;
						$childStr .= ','.$r->clidchild;
					}
					$sql = $sqlBase.substr($childStr,1).')';
				}while($childStr);
			}
		}
		return $this->clid;
	}

	private function setMetaData(){
		$sql = "SELECT name, authors, type, dynamicsql FROM fmchecklists WHERE (clid = ".$this->clid.")";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->clName = $row->name;
			$this->clAuthors = $row->authors;
			$this->clType = ($row->type?$row->type:'static');
			$this->dynamicSql = $row->dynamicsql;
		}
		$rs->free();
	}

	public function setAttrs($attrs){
		if(is_array($attrs)){
			foreach($attrs as $attr){
				$fragments = explode("-",$attr);
				$cid = $fragments[0];
				$cs = $fragments[1];
				if(is_numeric($cid)) $this->charArr[$cid][] = $cs;
			}
		}
	}

	//General setters and getters
	public function getAttrs(){
		return $this->attrs;
	}

	public function setLanguage($l){
		$this->lang = $l;
	}

	public function setCommonDisplay($bool){
		if($bool) $this->commonDisplay = true;
	}

	public function setTaxonFilter($t){
		$this->taxonFilter = $t;
	}

	public function getClid(){
		return $this->clid;
	}

	public function setDynClid($id){
		if(is_numeric($id)){
			$this->dynClid = $id;
		}
	}

	public function getTaxaCount(){
		return $this->taxaCount;
	}

	public function getClName(){
		return $this->clName;
	}

	public function getClAuthors(){
		return $this->clAuthors;
	}

	public function getClType(){
		return $this->clType;
	}

	public function setRelevanceValue($rel){
		if(is_numeric($rel)){
			$this->relevanceValue = $rel;
		}
	}

	public function getRelevanceValue(){
		return $this->relevanceValue;
	}
}
?>