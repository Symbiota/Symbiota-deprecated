<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpatialModuleManager{
	
	protected $conn;
	protected $searchTermsArr = Array();
	protected $localSearchArr = Array();
	protected $useCookies = 1;
	protected $reset = 0;
	protected $dynamicClid;
	protected $recordCount = 0;
	private $taxaArr = Array();
	private $collArr = Array();
	private $taxaSearchType;
	private $clName;
	private $collArrIndex = 0;
	private $iconColors = Array();
	private $googleIconArr = Array();
	private $fieldArr = Array();
	private $sqlWhere;
	private $searchTerms = 0;
	
    public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
    }

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	protected function getConnection($conType = "readonly"){
		return MySQLiConnectionFactory::getCon($conType);
	}
	
	private function getRandomColor(){
    	$first = str_pad(dechex(mt_rand(128,255)),2,'0',STR_PAD_LEFT);
		$second = str_pad(dechex(mt_rand(128,255)),2,'0',STR_PAD_LEFT);
		$third = str_pad(dechex(mt_rand(128,255)),2,'0',STR_PAD_LEFT);
		$color_code = $first.$second.$third;
		
		return $color_code;
    }

    public function getLayersArr(){
        global $GEOSERVER_URL, $GEOSERVER_LAYER_WORKSPACE;
        $url = $GEOSERVER_URL.'/wfs?service=wfs&version=2.0.0&request=GetCapabilities';
        $xml = simplexml_load_file($url);
        $layers = $xml->FeatureTypeList->FeatureType;
        $retArr = Array();
        foreach ($layers as $l){
            $nameArr = explode(":",(string)$l->Name);
            $workspace = $nameArr[0];
            $layername = $nameArr[1];
            if($workspace == $GEOSERVER_LAYER_WORKSPACE){
                $i = strtolower((string)$l->Title);
                $retArr[$i]['Name'] = $layername;
                $retArr[$i]['Title'] = (string)$l->Title;
                $retArr[$i]['Abstract'] = (string)$l->Abstract;
                $retArr[$i]['DefaultCRS'] = (string)$l->DefaultCRS;
            }
        }
        ksort($retArr);

        return $retArr;
    }
	
	public function getMysqlVersion(){
		$version = array();
		$output = '';
		if(mysqli_get_server_info($this->conn)){
			$output = mysqli_get_server_info($this->conn);
		}
		else{
			$output = shell_exec('mysql -V'); 
		}
		if($output){
			if(strpos($output,'MariaDB') !== false){
				$version["db"] = 'MariaDB';
			}
			else{
				$version["db"] = 'mysql';
				preg_match('@[0-9]+\.[0-9]+\.[0-9]+@',$output,$ver);
				$version["ver"] = $ver[0];
			}
		}
		return $version;
	}
	
	protected function setSciNamesByVerns(){
        $sql = "SELECT DISTINCT v.VernacularName, t.tid, t.sciname, ts.family, t.rankid ".
            "FROM (taxstatus ts LEFT JOIN taxavernaculars v ON ts.TID = v.TID) ".
            "LEFT JOIN taxa t ON t.TID = ts.tidaccepted ";
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
		$result->close();
    }
    
    protected function setSynonyms(){
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

    public function getFullCollectionList($catId = ""){
        $retArr = array();
        //Set collection array
        $collIdArr = array();
        $catIdArr = array();
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
	
	public function getTaxaSearchStr(){
		$returnArr = Array();
		foreach($this->taxaArr as $taxonName => $taxonArr){
			$str = $taxonName;
			if(array_key_exists("sciname",$taxonArr)){
				$str .= " => ".implode(",",$taxonArr["sciname"]);
			}
			if(array_key_exists("synonyms",$taxonArr)){
				$str .= " (".implode(",",$taxonArr["synonyms"]).")";
			}
			$returnArr[] = $str;
		}
		return implode("; ", $returnArr);
	}
	
	public function getLocalSearchStr(){
		return implode("; ", $this->localSearchArr);
	}
	
	public function getTaxonAuthorityList(){
		$taxonAuthorityList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$taxonAuthorityList[$row->taxauthid] = $row->name;
		}
		return $taxonAuthorityList;
	}

	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
    public function getGenObsInfo(){
		$retVar = array();
		$sql = 'SELECT collid, CollType '.
			'FROM omcollections ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if(stripos($r->CollType, "observation") !== false){
					$retVar[] = $r->collid;
				}
			}
			$rs->close();
		}
		return $retVar;
	}
	
	public function getFullCollArr($stArr){
		$sql = '';
		$this->collArr = Array();
		$sql = '';
		$sql = 'SELECT c.CollID, c.CollectionName '.
			'FROM omcollections AS c ';
		if($stArr['db'] != 'all'){
			$dbArr = explode(';',$stArr["db"]);
			$dbStr = '';
			$sql .= 'WHERE (c.collid IN('.trim($dbArr[0]).')) ';
        }
		$sql .= 'ORDER BY c.CollectionName ';
        //echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$collName = $row->CollectionName;
			$this->collArr[$collName] = Array();
		}
		$result->close();
		
		//return $sql;
	}
	
	private function xmlentities($string){
		return str_replace(array ('&','"',"'",'<','>','?'),array ('&amp;','&quot;','&apos;','&lt;','&gt;','&apos;'),$string);
	}
	
    //Setters and getters
    public function setFieldArr($fArr){
    	$this->fieldArr = $fArr;
    }
	
	public function setSearchTermsArr($stArr){
    	$this->searchTermsArr = $stArr;
		$this->searchTerms = 1;
    }
	
	public function getSearchTermsArr(){
    	return $this->searchTermsArr;
    }
	
	//New Map Interface functions
	private function formatDate($inDate){
		$inDate = trim($inDate);
		$retDate = '';
		$y=''; $m=''; $d='';
		if(preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/',$inDate)){
			$dateTokens = explode('-',$inDate);
			$y = $dateTokens[0];
			$m = $dateTokens[1];
			$d = $dateTokens[2];
		}
		elseif(preg_match('/^\d{1,2}\/*\d{0,2}\/\d{2,4}$/',$inDate)){
			//dd/mm/yyyy
			$dateTokens = explode('/',$inDate);
			$m = $dateTokens[0];
			if(count($dateTokens) == 3){
				$d = $dateTokens[1];
				$y = $dateTokens[2];
			}
			else{
				$d = '00';
				$y = $dateTokens[1];
			}
		}
		elseif(preg_match('/^\d{0,2}\s*\D+\s*\d{2,4}$/',$inDate)){
			$dateTokens = explode(' ',$inDate);
			if(count($dateTokens) == 3){
				$y = $dateTokens[2];
				$mText = substr($dateTokens[1],0,3);
				$d = $dateTokens[0];
			}
			else{
				$y = $dateTokens[1];
				$mText = substr($dateTokens[0],0,3);
				$d = '00';
			}
			$mText = strtolower($mText);
			$mNames = Array("ene"=>1,"jan"=>1,"feb"=>2,"mar"=>3,"abr"=>4,"apr"=>4,"may"=>5,"jun"=>6,"jul"=>7,"ago"=>8,"aug"=>8,"sep"=>9,"oct"=>10,"nov"=>11,"dic"=>12,"dec"=>12);
			$m = $mNames[$mText];
		}
		elseif(preg_match('/^\s*\d{4}\s*$/',$inDate)){
			$retDate = $inDate.'-00-00';
		}
		elseif($dateObj = strtotime($inDate)){
			$retDate = date('Y-m-d',$dateObj);
		}
		if(!$retDate && $y){
			if(strlen($y) == 2){
				if($y < 20){
					$y = "20".$y;
				}
				else{
					$y = "19".$y;
				}
			}
			if(strlen($m) == 1){
				$m = '0'.$m;
			}
			if(strlen($d) == 1){
				$d = '0'.$d;
			}
			$retDate = $y.'-'.$m.'-'.$d;
		}
		return $retDate;
	}

	public function outputFullMapCollArr($occArr){
        global $DEFAULTCATID, $CLIENT_ROOT;
	    $collCnt = 0;
        if(isset($occArr['cat'])){
            $catArr = $occArr['cat'];
            ?>
            <table>
                <?php
                foreach($catArr as $catid => $catArr){
                    $name = $catArr["name"];
                    unset($catArr["name"]);
                    $idStr = $this->collArrIndex.'-'.$catid;
                    ?>
                    <tr>
                        <td>
                            <a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
                                <img id="plus-<?php echo $idStr; ?>" src="<?php echo $CLIENT_ROOT; ?>/images/plus_sm.png" style="<?php echo ($DEFAULTCATID==$catid?'display:none;':'') ?>" /><img id="minus-<?php echo $idStr; ?>" src="<?php echo $CLIENT_ROOT; ?>/images/minus_sm.png" style="<?php echo ($DEFAULTCATID==$catid?'':'display:none;') ?>" />
                            </a>
                        </td>
                        <td>
                            <input id="cat<?php echo $idStr; ?>Input" data-role="none" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" checked />
                        </td>
                        <td>
			    		<span style='text-decoration:none;color:black;font-size:14px;font-weight:bold;'>
				    		<a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?catid=<?php echo $catid; ?>' target="_blank" ><?php echo $name; ?></a>
				    	</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <div id="cat-<?php echo $idStr; ?>" style="<?php echo ($DEFAULTCATID==$catid?'':'display:none;') ?>margin:10px 0px;">
                                <table style="margin-left:15px;">
                                    <?php
                                    foreach($catArr as $collid => $collName2){
                                        ?>
                                        <tr>
                                            <td>
                                                <?php
                                                if($collName2["icon"]){
                                                    $cIcon = (substr($collName2["icon"],0,6)=='images'?$CLIENT_ROOT.'/':'').$collName2["icon"];
                                                    ?>
                                                    <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
                                                        <img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
                                                    </a>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                            <td style="padding:6px">
                                                <input name="db[]" value="<?php echo $collid; ?>" data-role="none" type="checkbox" class="cat-<?php echo $idStr; ?>" onchange="buildQueryStrings();" onclick="unselectCat('cat<?php echo $catid; ?>Input')" checked />
                                            </td>
                                            <td style="padding:6px">
                                                <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
                                                    <?php echo $collName2["collname"]." (".$collName2["instcode"].")"; ?>
                                                </a>
                                                <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
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
            <table>
                <?php
                foreach($collArr as $collid => $cArr){
                    ?>
                    <tr>
                        <td>
                            <?php
                            if($cArr["icon"]){
                                $cIcon = (substr($cArr["icon"],0,6)=='images'?$CLIENT_ROOT.'/':'').$cArr["icon"];
                                ?>
                                <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' target="_blank" >
                                    <img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" />
                                </a>
                                <?php
                            }
                            ?>
                            &nbsp;
                        </td>
                        <td style="padding:6px;">
                            <input name="db[]" value="<?php echo $collid; ?>" data-role="none" type="checkbox" onchange="buildQueryStrings();" onclick="uncheckAll(this.form)" checked />
                        </td>
                        <td style="padding:6px">
                            <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='text-decoration:none;color:black;font-size:14px;' target="_blank" >
                                <?php echo $cArr["collname"]." (".$cArr["instcode"].")"; ?>
                            </a>
                            <a href = '<?php echo $CLIENT_ROOT; ?>/collections/misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;' target="_blank" >
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

	
	private function setRecordCnt($sqlWhere){
		global $userRights, $clientRoot;
		if($sqlWhere){
			$sql = "SELECT COUNT(o.occid) AS cnt FROM omoccurrences o ";
			if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "LEFT JOIN fmvouchers AS v ON o.occid = v.occid ";
			if(array_key_exists("polycoords",$this->searchTermsArr)) $sql .= "LEFT JOIN omoccurpoints p ON o.occid = p.occid ";
			$sql .= $sqlWhere;
			if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
				//Is global rare species reader, thus do nothing to sql and grab all records
			}
			elseif(array_key_exists("RareSppReader",$userRights)){
				$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
			}
			else{
				$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
			}
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->close();
		}
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}
	
	public function getTaxaArr(){
    	return $this->taxaArr;
    }
	
	public function getChecklist($stArr,$mapWhere){
		$returnVec = Array();
		$this->checklistTaxaCnt = 0;
		$sql = "";
        $sql = 'SELECT DISTINCT t.tid, IFNULL(ts.family,o.family) AS family, IFNULL(t.sciname,o.sciname) AS sciname '.
			'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
			'LEFT JOIN taxstatus ts ON t.tid = ts.tid ';
		if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "LEFT JOIN fmvouchers AS v ON o.occid = v.occid ";
		if(array_key_exists("polycoords",$stArr)) $sql .= "LEFT JOIN omoccurpoints p ON o.occid = p.occid ";
		$sql .= $mapWhere." AND (ISNULL(ts.taxauthid) OR ts.taxauthid = 1) ";
		$sql .= " ORDER BY family, o.sciname ";
        //echo "<div>".$sql."</div>";
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			if(!$family) $family = 'undefined';
			if($row->tid){
				$tidcode = $row->tid;
			}
			else{
				$tidcode = strtolower(str_replace( " ", "",$row->sciname));
				$tidcode = preg_replace( "/[^A-Za-z0-9 ]/","",$tidcode);
			}
			$sciName = $row->sciname;
			if($sciName){
				$returnVec[$family][$tidcode]["tid"] = $this->xmlentities($tidcode);
				$returnVec[$family][$tidcode]["sciname"] = $sciName;
				$this->checklistTaxaCnt++;
			}
        }
        return $returnVec;
		//return $sql;
	}
	
	public function getChecklistTaxaCnt(){
		return $this->checklistTaxaCnt;
	}

    public function getSynonyms($searchTarget,$taxAuthId = 1){
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
	
	public function getGpxText($seloccids){
		global $defaultTitle;
		$seloccids = preg_match('#\[(.*?)\]#', $seloccids, $match);
		$seloccids = $match[1];
		$gpxText = '';
		$gpxText = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
		$gpxText .= '<gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1" creator="mymy">';
		$sql = "";
        $sql = 'SELECT o.occid, o.basisOfRecord, c.institutioncode, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, '.
			'o.eventdate, o.family, o.sciname, o.locality, o.DecimalLatitude, o.DecimalLongitude '.
			'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid ';
		$sql .= 'WHERE o.occid IN('.$seloccids.') ';
        //echo "<div>".$sql."</div>";
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$comment = $row->institutioncode.($row->catalognumber?': '.$row->catalognumber.'. ':'. ');
			$comment .= $row->collector.'. '.$row->eventdate.'. Locality: '.$row->locality.' (occid: '.$row->occid.')';
			$gpxText .= '<wpt lat="'.$row->DecimalLatitude.'" lon="'.$row->DecimalLongitude.'">';
			$gpxText .= '<name>'.$row->sciname.'</name>';
			$gpxText .= '<cmt>'.$comment.'</cmt>';
			$gpxText .= '<sym>Waypoint</sym>';
			$gpxText .= '</wpt>';
		}
		$gpxText .= '</gpx>';
		
        return $gpxText;
	}
	
	public function getOccurrences($datasetId){
		$retArr = array();
		if($datasetId){
			$sql = 'SELECT o.occid, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, o.eventdate, '.
				'o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county) AS locality, o.DecimalLatitude, o.DecimalLongitude '.
				'FROM omoccurrences o LEFT JOIN omoccurdatasetlink dl ON o.occid = dl.occid '.
				'WHERE dl.datasetid = '.$datasetId.' '.
				'ORDER BY o.sciname ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['occid'] = $r->occid;
				$retArr[$r->occid]['sciname'] = $r->sciname;
				$retArr[$r->occid]['catnum'] = $r->catalognumber;
				$retArr[$r->occid]['coll'] = $r->collector;
				$retArr[$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->occid]['occid'] = $r->occid;
				$retArr[$r->occid]['lat'] = $r->DecimalLatitude;
				$retArr[$r->occid]['long'] = $r->DecimalLongitude;
			}
			$rs->free();
		}
		if(count($retArr)>1){
			return $retArr;
		}
		else{
			return;
		}
	}
	
	public function getPersonalRecordsets($uid){
		$retArr = Array();
		$sql = "";
        //Get datasets owned by user
		$sql = 'SELECT datasetid, name '.
			'FROM omoccurdatasets '.
			'WHERE (uid = '.$uid.') '.
			'ORDER BY name';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->datasetid]['datasetid'] = $r->datasetid;
			$retArr[$r->datasetid]['name'] = $r->name;
			$retArr[$r->datasetid]['role'] = "DatasetAdmin";
		}
		$sql2 = 'SELECT d.datasetid, d.name, r.role '.
			'FROM omoccurdatasets d LEFT JOIN userroles r ON d.datasetid = r.tablepk '.
			'WHERE (r.uid = '.$uid.') AND (r.role IN("DatasetAdmin","DatasetEditor","DatasetReader")) '.
			'ORDER BY sortsequence,name';
		$rs = $this->conn->query($sql2);
		while($r = $rs->fetch_object()){
			$retArr[$r->datasetid]['datasetid'] = $r->datasetid;
			$retArr[$r->datasetid]['name'] = $r->name;
			$retArr[$r->datasetid]['role'] = $r->role;
		}
		$rs->free();
		return $retArr;
		//return $sql;
	}
}
?>