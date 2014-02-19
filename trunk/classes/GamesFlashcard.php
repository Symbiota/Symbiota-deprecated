<?php

include_once($serverRoot.'/config/dbconnection.php');

class GamesFlashcard{

	private $conn;
	private $clid;
	private $dynClid;
	private $taxonFilter;
	private $showCommon = 0;
	private $lang;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getImages(){
		//Get species list
		$retArr = Array();

		//Grab a random list of no more than 1000 taxa 
		$sql = 'SELECT DISTINCT t.tid, t.sciname, ts.tidaccepted '.
			'FROM taxa t INNER JOIN '.($this->clid?"fmchklsttaxalink":"fmdyncltaxalink").' ctl ON t.tid = ctl.tid '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE '.($this->clid?"ctl.clid = ".$this->clid:"ctl.dynclid = ".$this->dynClid).' AND ts.taxauthid = 1 ';
		if($this->taxonFilter) $sql .= 'AND (ts.UpperTaxonomy = "'.$this->taxonFilter.'" OR ts.Family = "'.$this->taxonFilter.'" OR t.sciname Like "'.$this->taxonFilter.'%") ';
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

	public function echoTaxonFilterList(){
		$returnArr = Array();
		$upperList = Array();
		$sqlFamily = "SELECT DISTINCT ts.uppertaxonomy, ".($this->clid?"IFNULL(ctl.familyoverride,ts.Family)":"ts.Family")." AS family ".
			"FROM (taxa t INNER JOIN taxstatus ts ON t.TID = ts.TID) ".
			"INNER JOIN ".($this->clid?"fmchklsttaxalink":"fmdyncltaxalink")." ctl ON t.TID = ctl.TID ".
			"WHERE (ts.taxauthid = 1 AND ctl.".
			($this->clid?"clid = ".$this->clid:"dynclid = ".$this->dynClid).") ";
		//echo $sqlFamily."<br>";
		$rsFamily = $this->conn->query($sqlFamily);
		while ($row = $rsFamily->fetch_object()){
			$returnArr[] = $row->family;
			$upperList[$row->uppertaxonomy] = "";
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
		ksort($upperList);
		$upperList["-----------------------------------------------"] = "";
		$returnArr["-----------------------------------------------"] = "";
		$returnArr = array_merge(array_keys($upperList),$returnArr);
		foreach($returnArr as $value){
			echo "<option ";
			if($this->taxonFilter && $this->taxonFilter == $value){
				echo " SELECTED";
			}
			echo ">".$value."</option>\n";
		}
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