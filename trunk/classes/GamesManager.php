<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class GamesManager {

	private $conn;
	private $clid;
	private $dynClid;
	private $clName;
	private $taxonFilter;
	private $showCommon = 0;
	private $lang;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function setChecklist($clValue){
		if(!$clValue) return;
		$sql = "SELECT c.clid, c.name ".
			"FROM fmchecklists c ";
		if(is_numeric($clValue)){
			$sql .= 'WHERE (clid = '.$clValue.')';
		}
		else{
			$sql .= 'WHERE (clname = "'.$clValue.'")';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->clName = $row->name;
			$this->clid = $row->clid;
		}
		$rs->free();
	}
	
	public function setDynChecklist($dynClid){
		if(!$dynClid) return;
		$sql = 'SELECT c.dynclid, c.name '.
			'FROM fmdynamicchecklists c '.
			'WHERE (c.dynclid = '.$dynClid.')';
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->clName = $row->name;
			$this->dynClid = $row->dynclid;
		}
		$rs->free();
	}
	
	public function getChecklistArr($projId = 0){
		$retArr = Array();
		$sql = 'SELECT DISTINCT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmchklstprojlink plink ON c.clid = plink.clid ';
		if($projId){
			$sql .= 'WHERE c.type = "static" AND (plink.pid = '.$projId.') ';
		}
		else{
			$sql .= 'INNER JOIN fmprojects p ON plink.pid = p.pid WHERE c.type = "static" AND p.ispublic = 1 ';
		}
		$sql .= 'ORDER BY c.name';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid] = $row->name;
		}
		$rs->free();
		return $retArr;
	}
	
	public function setOOTD($oodID,$clid){
		global $serverRoot;
		$currentDate = date("Y-m-d");
		$replace = 0;
		if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_info.json')){
			$oldArr = json_decode(file_get_contents($serverRoot.'/temp/ootd/'.$oodID.'_info.json'), true);
			$lastDate = $oldArr['lastDate'];
			$lastCLID = $oldArr['clid'];
			if(($currentDate > $lastDate) || ($clid != $lastCLID)){
				$replace = 1;
			}
		}
		else{
			$replace = 1;
		}
		
		if($replace == 1){
			//Delete old files
			$previous = Array();
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_previous.json')){
				$previous = json_decode(file_get_contents($serverRoot.'/temp/ootd/'.$oodID.'_previous.json'), true);
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_previous.json');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_info.json')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_info.json');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_1.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_1.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_2.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_2.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_3.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_3.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_4.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_4.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_5.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_5.jpg');
			}
			
			//Create new files
			$ootdInfo = array();
			$ootdInfo['lastDate'] = $currentDate;
			
			$tidArr = Array();
			$sql = 'SELECT l.TID, COUNT(i.imgid) AS cnt '. 
				'FROM fmchklsttaxalink l INNER JOIN images AS i ON l.TID = i.tid '.
				'WHERE i.tid IS NOT NULL AND ISNULL(i.occid) AND l.CLID IN('.$clid.') '. 
				'GROUP BY l.TID';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(($row->cnt > 4) && (!in_array($row->TID, $previous))){
					$tidArr[] = $row->TID;
				}
			}
			$rs->free();
			$k = array_rand($tidArr);
			$randTaxa = $tidArr[$k];
			$previous[] = $randTaxa;
			//echo $randTaxa.' ';
			//echo json_encode($previous);
			
			$ootdInfo['clid'] = $clid;
			
			$sql2 = 'SELECT t.TID, t.SciName, t.UnitName1, s.family '.
				'FROM taxa AS t INNER JOIN taxstatus AS s ON t.TID = s.tid '.
				'WHERE s.taxauthid = 1 AND t.TID = '.$randTaxa.' ';
			//echo '<div>'.$sql2.'</div>';
			$rs = $this->conn->query($sql2);
			while($row = $rs->fetch_object()){
				$ootdInfo['tid'] = $row->TID;
				$ootdInfo['sciname'] = $row->SciName;
				$ootdInfo['genus'] = $row->UnitName1;
				$ootdInfo['family'] = $row->family;
			}
			$rs->free();
			
			$files = Array();
			$sql3 = 'SELECT i.url '.
				'FROM images AS i '.
				'WHERE ISNULL(i.occid) AND i.tid = '.$randTaxa.' '.
				'ORDER BY i.sortsequence ';
			//echo '<div>'.$sql.'</div>';
			$cnt = 1;
			$repcnt = 1;
			$rs = $this->conn->query($sql3);
			while(($row = $rs->fetch_object()) && ($cnt < 6)){
				$file = '';
				if (substr($row->url, 0, 1) == '/'){
					//If imageDomain variable is set within symbini file, image  
					if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
						$file = $GLOBALS['imageDomain'].$row->url;
					}
					else{
						//Use local domain 
						$domain = "http://";
						if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $domain = "https://";
						$domain .= $_SERVER["SERVER_NAME"];
						if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $domain .= ':'.$_SERVER["SERVER_PORT"];
						$file = $domain.$row->url;
					}
				}
				else{
					$file = $row->url;
				}
				$newfile = $serverRoot.'/temp/ootd/'.$oodID.'_organism300_'.$cnt.'.jpg';
				$newfilepath = '../../temp/ootd/'.$oodID.'_organism300_'.$cnt.'.jpg';
				if(fopen($file, "r")){
					copy($file, $newfile);
					$files[] = $newfilepath;
					$cnt++;
				}
			}
			$rs->free();
			$ootdInfo['images'] = $files;
			
			if(array_diff($tidArr,$previous)){
				$fp = fopen($serverRoot.'/temp/ootd/'.$oodID.'_previous.json', 'w');
				fwrite($fp, json_encode($previous));
				fclose($fp);
			}
			$fp = fopen($serverRoot.'/temp/ootd/'.$oodID.'_info.json', 'w');
			fwrite($fp, json_encode($ootdInfo));
			fclose($fp);
		}
		
		$infoArr = json_decode(file_get_contents($serverRoot.'/temp/ootd/'.$oodID.'_info.json'), true);
		//echo json_encode($infoArr);
		return $infoArr;
	}
	
	public function getFlashcardImages(){
		//Get species list
		$retArr = Array();

		//Grab a random list of no more than 1000 taxa 
		$sql = 'SELECT DISTINCT t.tid, t.sciname, ts.tidaccepted '.
			'FROM taxa t INNER JOIN '.($this->clid?"fmchklsttaxalink":"fmdyncltaxalink").' ctl ON t.tid = ctl.tid '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE '.($this->clid?"ctl.clid = ".$this->clid:"ctl.dynclid = ".$this->dynClid).' AND ts.taxauthid = 1 ';
		if($this->taxonFilter) $sql .= 'AND (ts.Family = "'.$this->taxonFilter.'" OR t.sciname Like "'.$this->taxonFilter.'%") ';
		$sql .= 'ORDER BY RAND() LIMIT 1000 ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->tidaccepted]['tid'] = $r->tid;
				$retArr[$r->tidaccepted]['sciname'] = $r->sciname;
			}
			$rs->close();
		}

		if($retArr){
			$tidStr = implode(',',array_keys($retArr));
			$tidComplete = array();
	
			if($this->showCommon){
				//Grab vernaculars
				$sqlV = 'SELECT ts.tidaccepted, v.vernacularname '.
					'FROM taxavernaculars v INNER JOIN taxstatus ts ON v.tid = ts.tid '.
					'WHERE v.Language = "'.$this->lang.'" AND ts.tidaccepted IN('.$tidStr.') '.
					'ORDER BY v.SortSequence';
				if($rsV = $this->conn->query($sqlV)){
					while($rV = $rsV->fetch_object()){
						if(!array_key_exists('vern',$retArr[$rV->tidaccepted])) $retArr[$rV->tidaccepted]['vern'] = $rV->vernacularname;
					}
					$rsV->close();
				}
			}
			
			//Grab images, first pass
			$sqlImg = 'SELECT DISTINCT i.url, ts.tidaccepted FROM images i INNER JOIN taxstatus ts ON i.tid = ts.tid '.
				'WHERE ts.tidaccepted IN('.$tidStr.') AND i.occid IS NULL '.
				'ORDER BY i.sortsequence';
			//echo $sql;
			$rsImg = $this->conn->query($sqlImg);
			while($rImg = $rsImg->fetch_object()){
				$iCnt = 0;
				if(array_key_exists('url',$retArr[$rImg->tidaccepted])) $iCnt = count($retArr[$rImg->tidaccepted]['url']);
				if($iCnt < 5){
					$url = $rImg->url;
					if(array_key_exists("imageDomain",$GLOBALS) && substr($url,0,1)=="/"){
						$url = $GLOBALS["imageDomain"].$url;
					}
					$retArr[$rImg->tidaccepted]['url'][] = $url;
				}
				else{
					$tidComplete[$rImg->tidaccepted] = $rImg->tidaccepted; 
				}
			}
			$rsImg->close();
			
			//For taxa without 5 images, look for images linked to children taxa
			if(count($tidComplete) < count($retArr)){
				$newTidStr = implode(',',array_keys(array_diff_key($retArr,$tidComplete)));
				$sqlImg2 = 'SELECT DISTINCT i.url, ts.parenttid FROM images i INNER JOIN taxstatus ts ON i.tid = ts.tid '.
					'WHERE ts.parenttid IN('.$newTidStr.') AND i.occid IS NULL '.
					'ORDER BY i.sortsequence';
				$rsImg2 = $this->conn->query($sqlImg2);
				while($rImg2 = $rsImg2->fetch_object()){
					$iCnt = 0;
					if(array_key_exists('url',$retArr[$rImg2->parenttid])) $iCnt = count($retArr[$rImg2->parenttid]['url']);
					if($iCnt < 5){
						$url = $rImg2->url;
						if(array_key_exists("imageDomain",$GLOBALS) && substr($url,0,1)=="/"){
							$url = $GLOBALS["imageDomain"].$url;
						}
						$retArr[$rImg2->parenttid]['url'][] = $url;
					}
				}
				$rsImg2->close();
			}		
		}
		
		return $retArr;
	}
	
	public function echoFlashcardTaxonFilterList(){
		$returnArr = Array();
		$sqlFamily = "SELECT DISTINCT ".($this->clid?"IFNULL(ctl.familyoverride,ts.Family)":"ts.Family")." AS family ".
			"FROM (taxa t INNER JOIN taxstatus ts ON t.TID = ts.TID) ".
			"INNER JOIN ".($this->clid?"fmchklsttaxalink":"fmdyncltaxalink")." ctl ON t.TID = ctl.TID ".
			"WHERE (ts.taxauthid = 1 AND ctl.".
			($this->clid?"clid = ".$this->clid:"dynclid = ".$this->dynClid).") ";
		//echo $sqlFamily."<br>";
		$rsFamily = $this->conn->query($sqlFamily);
		while ($row = $rsFamily->fetch_object()){
			$returnArr[] = $row->family;
		}
		$rsFamily->close();
		$sqlGenus = "SELECT DISTINCT t.unitname1 ".
			"FROM taxa t INNER JOIN ".($this->clid?"fmchklsttaxalink":"fmdyncltaxalink")." ctl ON t.tid = ctl.tid ".
			"WHERE (ctl.clid = ".$this->clid.") ";
		//echo $sqlGenus."<br>";
 		$rsGenus = $this->conn->query($sqlGenus);
		while ($row = $rsGenus->fetch_object()){
			$returnArr[] = $row->unitname1;
		}
		$rsGenus->close();
		natcasesort($returnArr);
		$returnArr["-----------------------------------------------"] = "";
		foreach($returnArr as $value){
			echo "<option ";
			if($this->taxonFilter && $this->taxonFilter == $value){
				echo " SELECTED";
			}
			echo ">".$value."</option>\n";
		}
	}
	
	public function getClid(){
		return $this->clid;
	}
	
	public function getDynClid(){
		return $this->dynClid;
	}
	
	public function getClName(){
		return $this->clName;
	}
	
	public function setClid($id){
		$this->clid = $id;
	}

	public function setDynClid($id){
		$this->dynClid = $id;
	}

	public function setTaxonFilter($tValue){
		$this->taxonFilter = $tValue;
	}

	public function setShowCommon($sc){
		$this->showCommon = $sc;
	}

	public function setLang($l){
		$this->lang = $l;
	}
}
?>