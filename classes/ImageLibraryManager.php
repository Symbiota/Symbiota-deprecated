<?php 
include_once($serverRoot.'/config/dbconnection.php');

class ImageLibraryManager{

	private $searchTermsArr = Array();
	private $recordCount = 0;
	private $conn;
	private $taxaArr = Array();
	private $collArrIndex = 0;
	private $searchTerms = 0;
	private $taxaSearchType;
	private $sqlWhere = '';
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

 	public function getFamilyList(){
 		$returnArray = Array();
		$sql = 'SELECT DISTINCT ts.Family '.
			'FROM images i INNER JOIN taxa t ON i.tid = t.tid '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'LEFT JOIN omoccurrences o ON i.occid = o.occid ';
		if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
			$sql .= 'LEFT JOIN imagetag AS it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords AS ik ON i.imgid = ik.imgid ';
		}
		if($this->sqlWhere){
			$sql .= $this->sqlWhere.' AND ';
		}
		else{
			$sql .= 'WHERE ';
		}
		$sql .= '(i.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) AND (ts.Family Is Not Null) ';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->Family;
    	}
    	$result->free();
    	sort($returnArray);
		return $returnArray;
	}
	
	public function getGenusList($taxon = ''){
 		$sql = 'SELECT DISTINCT t.UnitName1 '.
			'FROM images i INNER JOIN taxa t ON i.tid = t.tid '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'LEFT JOIN omoccurrences AS o ON i.occid = o.occid ';
		if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
			$sql .= 'INNER JOIN imagetag AS it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords AS ik ON i.imgid = ik.imgid ';
		}
		if($this->sqlWhere){
			$sql .= $this->sqlWhere.' AND ';
		}
		else{
			$sql .= 'WHERE ';
		}
		$sql .= '(i.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId > 180) AND (ts.Family Is Not Null) ';
		if($taxon){
			$taxon = $this->cleanInStr($taxon);
			$sql .= "AND (ts.Family = '".$taxon."') ";
		}
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[] = $row->UnitName1;
    	}
    	$result->free();
    	sort($returnArray);
    	return $returnArray;
	}
	
	public function getSpeciesList($taxon = ''){
		$returnArray = Array();
		$taxon = trim($taxon);
		if(strpos($taxon,' ')){
			$taxonArr = explode(' ',$taxon);
			$taxon = $taxonArr[0];
		}
		$sql = 'SELECT DISTINCT t.tid, t.SciName '.
			'FROM images i INNER JOIN taxa t ON i.tid = t.tid '.
			'INNER JOIN taxstatus AS ts ON t.tid = ts.tid '.
			'LEFT JOIN omoccurrences AS o ON i.occid = o.occid ';
		if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
			$sql .= 'INNER JOIN imagetag AS it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
			$sql .= 'INNER JOIN imagekeywords AS ik ON i.imgid = ik.imgid ';
		}
		if($this->sqlWhere){
			$sql .= $this->sqlWhere.' AND ';
		}
		else{
			$sql .= 'WHERE ';
		}
		$sql .= '(i.sortsequence < 500) AND (ts.taxauthid = 1) AND (t.RankId >= 220) AND (ts.Family Is Not Null) ';
		if($taxon){
			$taxon = $this->cleanInStr($taxon);
			$sql .= "AND ((t.SciName LIKE '".$taxon."%') OR (ts.family = '".$taxon."')) ";
		}
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArray[$row->tid] = $row->SciName;
	    }
	    $result->free();
    	asort($returnArray);
	    return $returnArray;
	}
	
	//Image contributor listings
	public function getCollectionImageList(){
		//Get collection names
		$stagingArr = array();
		$sql = 'SELECT collid, CONCAT(collectionname, " (", CONCAT_WS("-",institutioncode,collectioncode),")") as collname, colltype FROM omcollections ORDER BY collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$stagingArr[$r->collid]['name'] = $r->collname;
			$stagingArr[$r->collid]['type'] = (strpos($r->colltype,'Observations') !== false?'obs':'coll');
		}
		$rs->free();
		//Get image counts
		$sql = 'SELECT o.collid, COUNT(i.imgid) AS imgcnt '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'GROUP BY o.collid ';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$stagingArr[$row->collid]['imgcnt'] = $row->imgcnt;
		}
		$result->free();
		//Only return collections with images
		$retArr = array();
		foreach($stagingArr as $id => $collArr){
			if(array_key_exists('imgcnt', $collArr)){
				$retArr[$collArr['type']][$id]['imgcnt'] = $collArr['imgcnt'];
				$retArr[$collArr['type']][$id]['name'] = $collArr['name'];
			}
		}
		return $retArr;
	}
	
	public function getPhotographerList(){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as pname, CONCAT_WS(", ", u.firstname, u.lastname) as fullname, u.email, Count(ti.imgid) AS imgcnt '.
				'FROM users u INNER JOIN images ti ON u.uid = ti.photographeruid '.
				'GROUP BY u.uid '.
				'ORDER BY u.lastname, u.firstname';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->uid]['name'] = $row->pname;
			$retArr[$row->uid]['fullname'] = $row->fullname;
			$retArr[$row->uid]['imgcnt'] = $row->imgcnt;
		}
		$result->free();
		return $retArr;
	}

	//Search functions
	public function getFullCollectionList($catId = ""){
		$retArr = array();
		//Set collection array
		$collIdArr = array();
		$catIdArr = array();
		if(isset($this->searchTermsArr['db']) && array_key_exists('db',$this->searchTermsArr)){
			$cArr = explode(';',$this->searchTermsArr['db']);
			$collIdArr = explode(',',$cArr[0]);
			if(isset($cArr[1])) $catIdStr = $cArr[1];
		}
		//Set collections
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, c.colltype, ccl.ccpk, cat.category '.
			'FROM omcollections c LEFT JOIN omcollcatlink ccl ON c.collid = ccl.collid '.
			'LEFT JOIN omcollcategories cat ON ccl.ccpk = cat.ccpk '.
			'ORDER BY ccl.sortsequence, cat.category, c.sortseq, c.CollectionName ';
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($r = $result->fetch_object()){
			$collType = (stripos($r->colltype, "observation") !== false?'obs':'spec');
			if($r->ccpk){
				if(!isset($retArr[$collType]['cat'][$r->ccpk]['name'])){
					$retArr[$collType]['cat'][$r->ccpk]['name'] = $r->category;
				}
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["instcode"] = $r->institutioncode;
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["collcode"] = $r->collectioncode;
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["collname"] = $r->collectionname;
				$retArr[$collType]['cat'][$r->ccpk][$r->collid]["icon"] = $r->icon;
			}
			else{
				$retArr[$collType]['coll'][$r->collid]["instcode"] = $r->institutioncode;
				$retArr[$collType]['coll'][$r->collid]["collcode"] = $r->collectioncode;
				$retArr[$collType]['coll'][$r->collid]["collname"] = $r->collectionname;
				$retArr[$collType]['coll'][$r->collid]["icon"] = $r->icon;
			}
		}
		$result->close();
		//Modify sort so that default catid is first
		if(isset($retArr['spec']['cat'][$catId])){
			$targetArr = $retArr['spec']['cat'][$catId];
			unset($retArr['spec']['cat'][$catId]);
			array_unshift($retArr['spec']['cat'],$targetArr);
		} 
		elseif(isset($retArr['obs']['cat'][$catId])){
			$targetArr = $retArr['obs']['cat'][$catId];
			unset($retArr['obs']['cat'][$catId]);
			array_unshift($retArr['obs']['cat'],$targetArr);
		}
		return $retArr;
	}
	
	public function outputFullMapCollArr($dbArr,$occArr,$defaultCatid = 0){
		global $DEFAULTCATID;
		$collCnt = 0;
		if(isset($occArr['cat'])){
			$catArr = $occArr['cat'];
			?>
			<table style="float:left;width:80%;">
			<?php 
			foreach($catArr as $catid => $catArr){
				$name = $catArr["name"];
				unset($catArr["name"]);
				$idStr = $this->collArrIndex.'-'.$catid;
				?>
				<tr>
					<td style="padding:6px;width:25px;">
						<input id="cat<?php echo $idStr; ?>Input" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" <?php echo ((in_array($catid,$dbArr)||!$dbArr||in_array('all',$dbArr))?'checked':'') ?> />
					</td>
					<td style="padding:9px 5px;width:10px;">
						<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
							<img id="plus-<?php echo $idStr; ?>" src="../images/plus_sm.png" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'':'display:none;') ?>" /><img id="minus-<?php echo $idStr; ?>" src="../images/minus_sm.png" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?>" />
						</a>
					</td>
					<td style="padding-top:8px;">
			    		<span style='text-decoration:none;color:black;font-size:14px;font-weight:bold;'>
				    		<a href = '../collections/misc/collprofiles.php?catid=<?php echo $catid; ?>' target="_blank" ><?php echo $name; ?></a>
				    	</span>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<div id="cat-<?php echo $idStr; ?>" style="<?php echo (($DEFAULTCATID && $DEFAULTCATID != $catid)?'display:none;':'') ?>margin:10px;padding:10px 20px;border:inset;">
							<table>
						    	<?php 
								foreach($catArr as $collid => $collName2){
						    		?>
						    		<tr>
										<td>
											<?php 
											if($collName2["icon"]){
												$cIcon = (substr($collName2["icon"],0,6)=='images'?'../':'').$collName2["icon"]; 
												?>
												<a href = '../collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
													<img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
												</a>
										    	<?php
											}
										    ?>
										</td>
										<td style="padding:6px">
								    		<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" class="cat-<?php echo $idStr; ?>" onclick="unselectCat('cat<?php echo $catid; ?>Input')" <?php echo ((in_array($collid,$dbArr)||!$dbArr||in_array('all',$dbArr))?'checked':'') ?> /> 
										</td>
										<td style="padding:6px">
								    		<a href = '../collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
								    			<?php echo $collName2["collname"]." (".$collName2["instcode"].")"; ?>
								    		</a>
								    		<a href = '../collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
								    			more info
								    		</a>
										</td>
									</tr>
						    		<?php 
					    			$collCnt++; 
						    	}
						    	?>
						    </table>
						</div>
					</td>
				</tr>
				<?php 
			}
			?>
			</table>
			<?php 
		}
		if(isset($occArr['coll'])){
			$collArr = $occArr['coll'];
			?>
			<table style="float:left;width:80%;">
			<?php 
			foreach($collArr as $collid => $cArr){
				?>
				<tr>
					<td>
						<?php 
						if($cArr["icon"]){
							$cIcon = (substr($cArr["icon"],0,6)=='images'?'../':'').$cArr["icon"]; 
							?>
							<a href = '../collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
								<img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
							</a>
					    	<?php
						}
					    ?>
					    &nbsp;
					</td>
					<td style="padding:6px;">
			    		<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="uncheckAll(this.form)" <?php echo ((in_array($collid,$dbArr)||!$dbArr||in_array('all',$dbArr))?'checked':'') ?> /> 
					</td>
					<td style="padding:6px">
			    		<a href = '../collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
			    			<?php echo $cArr["collname"]." (".$cArr["instcode"].")"; ?>
			    		</a>
			    		<a href = '../collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
			    			more info
			    		</a>
				    </td>
				</tr>
				<?php
				$collCnt++;
			}
			?>
			</table>
			<?php 
		}
		$this->collArrIndex++;
	}
	
	public function readRequestVariables(){
		//Search will be confinded to a collid, catid, or will remain open to all collection
		//Limit collids and/or catids
		$dbStr = '';
		$this->searchTermsArr["db"] = '';
		if(array_key_exists("db",$_REQUEST)){
			$dbs = $_REQUEST["db"];
			if(is_string($dbs)){
				$dbStr = $dbs.';';
			}
			else{
				$dbStr = $this->cleanInStr(implode(',',array_unique($dbs))).';';
			}
			if(strpos($dbStr,'allspec') !== false){
				$dbStr = 'allspec';
			}
			elseif(strpos($dbStr,'allobs') !== false){
				$dbStr = 'allobs';
			}
			elseif(strpos($dbStr,'all') !== false){
				$dbStr = 'all';
			}
		}
		if(substr($dbStr,0,3) != 'all' && array_key_exists('cat',$_REQUEST)){
			$catArr = array();
			$catid = $_REQUEST['cat'];
			if(is_string($catid)){
				$catArr = Array($catid);
			}
			else{
				$catArr = $catid;
			}
			if(!$dbStr) $dbStr = ';';
			$dbStr .= $this->cleanInStr(implode(",",$catArr));
		}

		if($dbStr){
			$this->searchTermsArr["db"] = $dbStr;
		}
		$this->searchTermsArr["taxa"] = '';
		$this->searchTermsArr["taxontype"] = '';
		$this->searchTermsArr["usethes"] = '';
		if(array_key_exists("taxastr",$_REQUEST)){
			$taxa = $this->cleanInStr($_REQUEST["taxastr"]);
			$searchType = array_key_exists("nametype",$_REQUEST)?$this->cleanInStr($_REQUEST["nametype"]):1;
			$this->searchTermsArr["taxontype"] = $searchType;
			$useThes = array_key_exists("thes",$_REQUEST)?$this->cleanInStr($_REQUEST["thes"]):0;
			$this->searchTermsArr["usethes"] = $useThes;
			if($taxa){
				$taxaStr = "";
				if(is_numeric($taxa)){
					$sql = "SELECT t.sciname ". 
						"FROM taxa t ".
						"WHERE (t.tid = ".$taxa.')';
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						$taxaStr = $row->sciname;
					}
					$rs->close();
				}
				else{
					$taxaStr = str_replace(",",";",$taxa);
					$taxaArr = explode(";",$taxaStr);
					foreach($taxaArr as $key => $sciName){
						$snStr = trim($sciName);
						if($searchType != 5) $snStr = ucfirst($snStr);
						$taxaArr[$key] = $snStr;
					}
					$taxaStr = implode(";",$taxaArr);
				}
				$collTaxa = "taxa:".$taxaStr;
				$this->searchTermsArr["taxa"] = $taxaStr;
				$useThes = array_key_exists("thes",$_REQUEST)?$this->cleanInStr($_REQUEST["thes"]):0; 
				if($useThes){
					$collTaxa .= "&usethes:true";
					$this->searchTermsArr["usethes"] = true;
				}
				else{
					$this->searchTermsArr["usethes"] = false;
				}
				if($searchType){
					$collTaxa .= "&taxontype:".$searchType;
					$this->searchTermsArr["taxontype"] = $searchType;
				}
			}
		}
		$this->searchTermsArr["country"] = '';
		if(array_key_exists("countrystr",$_REQUEST)){
			$country = $this->cleanInStr($_REQUEST["countrystr"]);
			if($country){
				$str = str_replace(",",";",$country);
				if(stripos($str, "USA") !== false && stripos($str, "United States") === false){
					$str .= ";United States";
				}
				elseif(stripos($str, "United States") !== false && stripos($str, "USA") === false){
					$str .= ";USA";
				}
				$this->searchTermsArr["country"] = $str;
			}
		}
		$this->searchTermsArr["state"] = '';
		if(array_key_exists("statestr",$_REQUEST)){
			$state = $this->cleanInStr($_REQUEST["statestr"]);
			if($state){
				$str = str_replace(",",";",$state);
				$this->searchTermsArr["state"] = $str;
			}
		}
		$this->searchTermsArr["phuid"] = '';
		if(array_key_exists("phuidstr",$_REQUEST)){
			$phuid = $this->cleanInStr($_REQUEST["phuidstr"]);
			if($phuid){
				$this->searchTermsArr["phuid"] = $phuid;
			}
		}
		$this->searchTermsArr["tags"] = '';
		if(array_key_exists("tags",$_REQUEST)){
			$tags = $this->cleanInStr($_REQUEST["tags"]);
			if($tags){
				$this->searchTermsArr["tags"] = $tags;
			}
		}
		$this->searchTermsArr["keywords"] = '';
		if(array_key_exists("keywordstr",$_REQUEST)){
			$keywords = $this->cleanInStr($_REQUEST["keywordstr"]);
			if($keywords){
				$str = str_replace(",",";",$keywords);
				$this->searchTermsArr["keywords"] = $str;
			}
		}
		$this->searchTermsArr["imagecount"] = '';
		if(array_key_exists("imagecount",$_REQUEST)){
			$imagecount = $this->cleanInStr($_REQUEST["imagecount"]);
			if($imagecount){
				$this->searchTermsArr["imagecount"] = $imagecount;
			}
		}
		$this->searchTermsArr["imagedisplay"] = '';
		if(array_key_exists("imagedisplay",$_REQUEST)){
			$imagedisplay = $this->cleanInStr($_REQUEST["imagedisplay"]);
			if($imagedisplay){
				$this->searchTermsArr["imagedisplay"] = $imagedisplay;
			}
		}
		$this->searchTermsArr["imagetype"] = '';
		if(array_key_exists("imagetype",$_REQUEST)){
			$imagetype = $this->cleanInStr($_REQUEST["imagetype"]);
			if($imagetype){
				$this->searchTermsArr["imagetype"] = $imagetype;
			}
		}
	}
	
	public function setSqlWhere(){
		$sqlWhere = "";
		if(array_key_exists("db",$this->searchTermsArr) && $this->searchTermsArr['db']){
			//Do nothing if db = all
			if($this->searchTermsArr['db'] != 'all'){
				if($this->searchTermsArr['db'] == 'allspec'){
					$sqlWhere .= 'AND (o.collid IN(SELECT collid FROM omcollections WHERE colltype = "Preserved Specimens")) ';
				}
				elseif($this->searchTermsArr['db'] == 'allobs'){
					$sqlWhere .= 'AND (o.collid IN(SELECT collid FROM omcollections WHERE colltype IN("General Observations","Observations"))) ';
				}
				else{
					$dbArr = explode(';',$this->searchTermsArr["db"]);
					$dbStr = '';
					if(isset($dbArr[0]) && $dbArr[0]){
						$dbStr = "(o.collid IN(".trim($dbArr[0]).")) ";
					}
					if(isset($dbArr[1]) && $dbArr[1]){
						$dbStr .= ($dbStr?'OR ':'').'(o.CollID IN(SELECT collid FROM omcollcatlink WHERE (ccpk IN('.$dbArr[1].')))) ';
					}
					$sqlWhere .= 'AND ('.$dbStr.') ';
				}
			}
		}
		
		if(array_key_exists("taxa",$this->searchTermsArr)&&$this->searchTermsArr["taxa"]){
			$sqlWhereTaxa = "";
			$useThes = (array_key_exists("usethes",$this->searchTermsArr)?$this->searchTermsArr["usethes"]:0);
			$this->taxaSearchType = $this->searchTermsArr["taxontype"];
			$taxaArr = explode(";",trim($this->searchTermsArr["taxa"]));
			//Set scientific name
			$this->taxaArr = Array();
			foreach($taxaArr as $sName){
				$this->taxaArr[trim($sName)] = Array();
			}
			if($this->taxaSearchType == 3){
				//Common name search
				$this->setSciNamesByVerns();
			}
			else{
				if($useThes){ 
					$this->setSynonyms();
				}
			}

			//Build sql
			foreach($this->taxaArr as $key => $valueArray){
				if($this->taxaSearchType == 2){
					$rs1 = $this->conn->query("SELECT tid FROM taxa WHERE (sciname = '".$key."')");
					if($r1 = $rs1->fetch_object()){
						$sqlWhereTaxa = 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE taxauthid = 1 AND parenttid IN('.$r1->tid.'))) ';
					}
					$sqlWhereTaxa .= "OR (t.sciname LIKE '".$key."%') ";
					//Look for synonyms
					if(array_key_exists("synonyms",$valueArray)){
						$synArr = $valueArray["synonyms"];
						if($synArr){
							foreach($synArr as $synTid => $sciName){ 
								if(strpos($sciName,'aceae') || strpos($sciName,'idae')){
									$sqlWhereTaxa .= "OR (o.family = '".$sciName."') ";
								}
							}
							$sqlWhereTaxa .= 'OR (o.tidinterpreted IN('.implode(',',array_keys($synArr)).')) ';
						}
					}
				}
				else{
					//Is a common name search
					$famArr = array();
					if(array_key_exists("families",$valueArray)){
						$famArr = $valueArray["families"];
					}
					if(array_key_exists("tid",$valueArray)){
						$tidArr = $valueArray['tid'];
						$sql = 'SELECT DISTINCT t.sciname '.
							'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
							'WHERE t.rankid = 140 AND e.taxauthid = 1 AND e.parenttid IN('.implode(',',$tidArr).')';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$famArr[] = $r->family;
						}
					}
					if($famArr){
						$famArr = array_unique($famArr);
						$sqlWhereTaxa .= 'OR (o.family IN("'.implode('","',$famArr).'")) ';
					}
					if(array_key_exists("scinames",$valueArray)){
						foreach($valueArray["scinames"] as $sciName){
							$sqlWhereTaxa .= "OR (t.sciname LIKE '".$sciName."%') ";
						}
					}
				}
			}
			$sqlWhere .= "AND (".substr($sqlWhereTaxa,3).") ";
		}
		if(array_key_exists("country",$this->searchTermsArr)&&$this->searchTermsArr["country"]){
			$countryArr = explode(";",$this->searchTermsArr["country"]);
			$tempArr = Array();
			foreach($countryArr as $value){
				$tempArr[] = "(o.Country = '".trim($value)."')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
		}
		if(array_key_exists("state",$this->searchTermsArr)&&$this->searchTermsArr["state"]){
			$stateAr = explode(";",$this->searchTermsArr["state"]);
			$tempArr = Array();
			foreach($stateAr as $value){
				$tempArr[] = "(o.StateProvince LIKE '".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
		}
		if(array_key_exists("phuid",$this->searchTermsArr)&&$this->searchTermsArr["phuid"]){
			$sqlWhere .= "AND (i.photographeruid IN(".$this->searchTermsArr["phuid"].")) ";
		}
		if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
			$sqlWhere .= 'AND (it.keyvalue = "'.$this->searchTermsArr["tags"].'") ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
			$keywordArr = explode(";",$this->searchTermsArr["keywords"]);
			$tempArr = Array();
			foreach($keywordArr as $value){
				$tempArr[] = "(ik.keyword LIKE '%".trim($value)."%')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
		}
		if(array_key_exists("imagetype",$this->searchTermsArr)&&$this->searchTermsArr["imagetype"]){
			if($this->searchTermsArr["imagetype"] == 'specimenonly'){
				$sqlWhere .= 'AND (i.occid IS NOT NULL) ';
			}
			if($this->searchTermsArr["imagetype"] == 'fieldonly'){
				$sqlWhere .= 'AND (i.occid IS NULL) ';
			}
		}
		if($sqlWhere){
			$this->sqlWhere = 'WHERE '.substr($sqlWhere,4);
		}
		else{
			//Make the sql valid, but return nothing
			$this->sqlWhere = 'WHERE o.collid = -1 ';
		}
	}
	
	public function getImageArr($taxon,$pageRequest,$cntPerPage){
		$retArr = Array();
		if(!$this->recordCount){
			$this->setRecordCnt($taxon);
		}
		$sql = 'SELECT DISTINCT i.imgid, o.tidinterpreted, t.tid, t.sciname, i.url, i.thumbnailurl, i.originalurl, '.
			'u.uid, u.lastname, u.firstname, i.caption, '.
			'o.occid, o.stateprovince, o.catalognumber, CONCAT_WS("-",c.institutioncode, c.collectioncode) as instcode '.
			'FROM images AS i LEFT JOIN taxa t ON i.tid = t.tid '.
			'LEFT JOIN users AS u ON i.photographeruid = u.uid '.
			'LEFT JOIN omoccurrences AS o ON i.occid = o.occid '.
			'LEFT JOIN omcollections AS c ON o.collid = c.collid ';
		if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
			$sql .= 'LEFT JOIN imagetag AS it ON i.imgid = it.imgid ';
		}
		if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
			$sql .= 'LEFT JOIN imagekeywords AS ik ON i.imgid = ik.imgid ';
		}
		$sql .= $this->sqlWhere;
		if($taxon){
			$taxon = $this->cleanInStr($taxon);
			$sql .= "AND (t.SciName LIKE '".$taxon."%') ";
		}
		if(array_key_exists("imagecount",$this->searchTermsArr)&&$this->searchTermsArr["imagecount"]){
			if($this->searchTermsArr["imagecount"] == 'taxon'){
				$sql .= 'GROUP BY ts.tidaccepted ';
			}
			elseif($this->searchTermsArr["imagecount"] == 'specimen'){
				$sql .= 'GROUP BY o.occid ';
			}
		}
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= "ORDER BY t.sciname ";
		$sql .= "LIMIT ".$bottomLimit.",".$cntPerPage;
		//echo "<div>Spec sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($r = $result->fetch_object()){
			$imgId = $r->imgid;
			$retArr[$imgId]['imgid'] = $r->imgid;
			$retArr[$imgId]['tidaccepted'] = $r->tidinterpreted;
			$retArr[$imgId]['tid'] = $r->tid;
			$retArr[$imgId]['sciname'] = $r->sciname;
			$retArr[$imgId]['url'] = $r->url;
			$retArr[$imgId]['thumbnailurl'] = $r->thumbnailurl;
			$retArr[$imgId]['originalurl'] = $r->originalurl;
			$retArr[$imgId]['uid'] = $r->uid;
			$retArr[$imgId]['lastname'] = $r->lastname;
			$retArr[$imgId]['firstname'] = $r->firstname;
			$retArr[$imgId]['caption'] = $r->caption;
			$retArr[$imgId]['occid'] = $r->occid;
			$retArr[$imgId]['stateprovince'] = $r->stateprovince;
			$retArr[$imgId]['catalognumber'] = $r->catalognumber;
			$retArr[$imgId]['instcode'] = $r->instcode;
		}
		$result->free();
		return $retArr;
		//return $sql;
	}

	private function setRecordCnt($taxon){
		if($this->sqlWhere){
			$sql = '';
			if(array_key_exists("imagecount",$this->searchTermsArr)&&$this->searchTermsArr["imagecount"]){
				if($this->searchTermsArr["imagecount"] == 'taxon'){
					$sql = "SELECT COUNT(DISTINCT o.tidinterpreted) AS cnt ";
				}
				elseif($this->searchTermsArr["imagecount"] == 'specimen'){
					$sql = "SELECT COUNT(DISTINCT o.occid) AS cnt ";
				}
				else{
					$sql = "SELECT COUNT(i.imgid) AS cnt ";
				}
			}
			else{
				$sql = "SELECT COUNT(i.imgid) AS cnt ";
			}
			$sql .= 'FROM images AS i LEFT JOIN taxa t ON i.tid = t.tid '.
				'LEFT JOIN omoccurrences AS o ON i.occid = o.occid ';
			if(array_key_exists("tags",$this->searchTermsArr)&&$this->searchTermsArr["tags"]){
				$sql .= 'LEFT JOIN imagetag AS it ON i.imgid = it.imgid ';
			}
			if(array_key_exists("keywords",$this->searchTermsArr)&&$this->searchTermsArr["keywords"]){
				$sql .= 'LEFT JOIN imagekeywords AS ik ON i.imgid = ik.imgid ';
			}
			$sql .= $this->sqlWhere;
			if($taxon){
				$taxon = $this->cleanInStr($taxon);
				$sql .= "AND (t.SciName LIKE '".$taxon."%') ";
			}
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->free();
		}
	}

	private function setSciNamesByVerns(){
        $sql = "SELECT DISTINCT v.VernacularName, t.tid, t.sciname, ts.family, t.rankid ".
            "FROM (taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID) ".
            "INNER JOIN taxa t ON t.TID = ts.tidaccepted ";
    	$whereStr = "";
		foreach($this->taxaArr as $key => $value){
			$whereStr .= "OR v.VernacularName = '".$key."' ";
		}
		$sql .= "WHERE (ts.taxauthid = 1) AND (".substr($whereStr,3).") ORDER BY t.rankid LIMIT 20";
		//echo "<div>sql: ".$sql."</div>";
		$result = $this->conn->query($sql);
		if($result->num_rows){
			while($row = $result->fetch_object()){
				$vernName = strtolower($row->VernacularName);
				if($row->rankid < 140){
					$this->taxaArr[$vernName]["tid"][] = $row->tid;
				}
				elseif($row->rankid == 140){
					$this->taxaArr[$vernName]["families"][] = $row->sciname;
				}
				else{
					$this->taxaArr[$vernName]["scinames"][] = $row->sciname;
				}
			}
		}
		else{
			$this->taxaArr["no records"]["scinames"][] = "no records";
		}
		$result->free();
    }
	
	private function setSynonyms(){
		foreach($this->taxaArr as $key => $value){
			if(array_key_exists("scinames",$value)){
				if(!in_array("no records",$value["scinames"])){
					$synArr = $this->getSynonyms($value["scinames"]);
					if($synArr) $this->taxaArr[$key]["synonyms"] = $synArr;
				}
			}
			else{
				$synArr = $this->getSynonyms($key);
				if($synArr) $this->taxaArr[$key]["synonyms"] = $synArr;
			}
		}
    }
    
    private function getSynonyms($searchTarget,$taxAuthId = 1){
    	$synArr = array();
    	$targetTidArr = array();
    	$searchStr = '';
    	if(is_array($searchTarget)){
    		if(is_numeric(current($searchTarget))){
    			$targetTidArr = $searchTarget;
    		}
    		else{
    			$searchStr = implode('","',$searchTarget);
    		}
    	}
    	else{
    		if(is_numeric($searchTarget)){
    			$targetTidArr[] = $searchTarget;
    		}
    		else{
    			$searchStr = $searchTarget;
    		}
    	}
    	if($searchStr){
    		//Input is a string, thus get tids
    		$sql1 = 'SELECT tid FROM taxa WHERE sciname IN("'.$searchStr.'")';
    		$rs1 = $this->conn->query($sql1);
    		while($r1 = $rs1->fetch_object()){
    			$targetTidArr[] = $r1->tid;
    		}
    		$rs1->free();
    	}
    
    	if($targetTidArr){
    		//Get acceptd names
    		$accArr = array();
    		$rankId = 0;
    		$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t.rankid '.
    				'FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted '.
    				'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.tid IN('.implode(',',$targetTidArr).')) ';
    		$rs2 = $this->conn->query($sql2);
    		while($r2 = $rs2->fetch_object()){
    			$accArr[] = $r2->tid;
    			$rankId = $r2->rankid;
    			//Put in synonym array if not target
    			if(!in_array($r2->tid,$targetTidArr)) $synArr[$r2->tid] = $r2->sciname;
    		}
    		$rs2->free();
    
    		if($accArr){
    			//Get synonym that are different than target
    			$sql3 = 'SELECT DISTINCT t.tid, t.sciname ' .
    					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
    					'WHERE (ts.taxauthid = ' . $taxAuthId . ') AND (ts.tidaccepted IN(' . implode('', $accArr) . ')) ';
    			$rs3 = $this->conn->query($sql3);
    			while ($r3 = $rs3->fetch_object()) {
    				if (!in_array($r3->tid, $targetTidArr)) $synArr[$r3->tid] = $r3->sciname;
    			}
    			$rs3->free();
    
    			//If rank is 220, get synonyms of accepted children
    			if ($rankId == 220) {
    				$sql4 = 'SELECT DISTINCT t.tid, t.sciname ' .
    						'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
    						'WHERE (ts.parenttid IN(' . implode('', $accArr) . ')) AND (ts.taxauthid = ' . $taxAuthId . ') ' .
    						'AND (ts.TidAccepted = ts.tid)';
    				$rs4 = $this->conn->query($sql4);
    				while ($r4 = $rs4->fetch_object()) {
    					$synArr[$r4->tid] = $r4->sciname;
    				}
    				$rs4->free();
    			}
    		}
    	}
    	return $synArr;
    }
	
	public function getTagArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT keyvalue '. 
			'FROM imagetag '.
			'ORDER BY keyvalue ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[] = $r->keyvalue;
			}
		}
		return $retArr;
	}
	
	public function setSearchTermsArr($stArr){
    	$this->searchTermsArr = $stArr;
		$this->searchTerms = 1;
    }
	
	public function getSearchTermsArr(){
    	return $this->searchTermsArr;
    }
	
	public function getRecordCnt(){
		return $this->recordCount;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>