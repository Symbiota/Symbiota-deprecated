<?php

include_once($serverRoot.'/config/dbconnection.php');

class TaxonomyDisplayManager{

	private $conn;
	private $indentValue = 0;
	private $indentMap = array();
	private $taxaArr = Array();
	private $targetStr = "";
	private $searchTaxonRank = 0;
	
	function __construct($target){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->targetStr = $this->conn->real_escape_string(trim(ucfirst($target)));
	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
 	
	public function getTaxa(){
		//Get target taxa (we don't want children and parents of not accepted taxa, so we'll get those later) 
		$hArray = Array();
		$hierarchyArr = Array();
		$taxaParentIndex = Array();
		if($this->targetStr){
			$sql = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, ts.parenttid, ts.tidaccepted, ts.hierarchystr '.
				'FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
				'LEFT JOIN taxstatus ts1 ON t.tid = ts1.tidaccepted '.
				'LEFT JOIN taxa t1 ON ts1.tid = t1.tid '.
				'WHERE (ts.taxauthid = 1 OR ts.taxauthid IS NULL) AND (ts1.taxauthid = 1 OR ts1.taxauthid IS NULL) ';
			if(is_numeric($this->targetStr)){
				$sql .= 'AND (t.tid IN('.implode(',',$this->targetStr).') OR (ts1.tid = '.$this->targetStr.'))';
			}
			elseif(strpos($this->targetStr," ")){
				$sql .= 'AND ((t.sciname LIKE "'.$this->targetStr.'%") OR (t1.sciname LIKE "'.$this->targetStr.'%"))';
			}
			else{
				$sql .= 'AND ((t.sciname = "'.$this->targetStr.'") OR (t1.sciname = "'.$this->targetStr.'"))';
			}
			//echo "<div>".$sql."</div>";
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$tid = $row->tid;
				$parentTid = $row->parenttid;
				if($tid == $row->tidaccepted || !$row->tidaccepted){
					$this->taxaArr[$tid]["sciname"] = $row->sciname;
					$this->taxaArr[$tid]["author"] = $row->author; 
					$this->taxaArr[$tid]["parenttid"] = $parentTid; 
					$this->taxaArr[$tid]["rankid"] = $row->rankid;
					$this->indentMap[$row->rankid] = 0;
					$this->searchTaxonRank = $row->rankid;
					$taxaParentIndex[$tid] = ($parentTid?$parentTid:0);
					if($row->hierarchystr) $hArray = array_merge($hArray,explode(",",$row->hierarchystr));
				}
				else{
					$this->taxaArr[$row->tidaccepted]["synonyms"][$tid] = $row->sciname;
				}
			}
			$rs->close();
		}
		
		if($this->taxaArr){
			//Get parents and children, but only accepted children
			$sql = "SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, ts.parenttid ".
				"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) AND (";
			//Add sql fragments that will grab the children taxa
			$innerSql = "";
			foreach($this->taxaArr as $t => $tArr){
				$innerSql .= "OR (ts.hierarchystr LIKE '%,".$t.",%') OR (ts.hierarchystr LIKE '%,".$t."') ";
			}
			if($hArray) $innerSql .= "OR (t.tid IN(".implode(",",array_unique($hArray)).")) ";
			$sql .= substr($innerSql,3).") ";
			if($this->searchTaxonRank < 140) $sql .= "AND rankid <= 140 ";
			//echo $sql."<br>";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$tid = $row->tid;
				$parentTid = $row->parenttid;
				$this->taxaArr[$tid]["sciname"] = $row->sciname;
				$this->taxaArr[$tid]["author"] = $row->author; 
				$this->taxaArr[$tid]["rankid"] = $row->rankid;
				$this->indentMap[$row->rankid] = 0;
				$this->taxaArr[$tid]["parenttid"] = $parentTid; 
				if($parentTid) $taxaParentIndex[$tid] = $parentTid;
			}
			$result->close();
			
			//Get synonyms for all accepted taxa
			$synTidStr = implode(",",array_keys($this->taxaArr));
			$sqlSyns = "SELECT ts.tidaccepted, t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
				"WHERE (ts.tid <> ts.tidaccepted) AND (ts.taxauthid = 1) AND (ts.tidaccepted IN(".$synTidStr."))";
			//echo $sqlSyns;
			$rsSyns = $this->conn->query($sqlSyns);
			while($row = $rsSyns->fetch_object()){
				$this->taxaArr[$row->tidaccepted]["synonyms"][$row->tid] = $row->sciname;
			}
			$rsSyns->close();

			//Grab parentTids that are not indexed in $taxaParentIndex. This would be due to a parent mismatch or a missing hierarchystr
			$orphanTaxa = array_unique(array_diff($taxaParentIndex,array_keys($taxaParentIndex)));
			if($orphanTaxa){
				$sqlOrphan = "SELECT t.tid, t.sciname, t.author, ts.parenttid, t.rankid ".
					"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
					"WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) AND (t.tid IN (".implode(",",$orphanTaxa)."))";
				//echo $sqlOrphan;
				$rsOrphan = $this->conn->query($sqlOrphan);
				while($row = $rsOrphan->fetch_object()){
					$tid = $row->tid;
					$taxaParentIndex[$tid] = $row->parenttid;
					$this->taxaArr[$tid]["sciname"] = $row->sciname; 
					$this->taxaArr[$tid]["author"] = $row->author;
					$this->taxaArr[$tid]["parenttid"] = $row->parenttid; 
					$this->taxaArr[$tid]["rankid"] = $row->rankid;
					$this->indentMap[$row->rankid] = 0;
				}
				$rsOrphan->close();
			}
			
			//Set $indentMap to correct values
			ksort($this->indentMap);
			$indentCnt = 0;
			foreach($this->indentMap as $rid => $v){
				$this->indentMap[$rid] = $indentCnt*10;
				$indentCnt++;
			}
			
			//Build Hierarchy Array: grab leaf nodes and attach to parent until none are left
			$leafTaxa = Array();
			while($leafTaxa = array_diff(array_keys($taxaParentIndex),$taxaParentIndex)){
				foreach($leafTaxa as $value){
					if(array_key_exists($value,$hierarchyArr)){
						$hierarchyArr[$taxaParentIndex[$value]][$value] = $hierarchyArr[$value];
						unset($hierarchyArr[$value]);
					}
					else{
						$hierarchyArr[$taxaParentIndex[$value]][$value] = $value;
					}
					unset($taxaParentIndex[$value]);
				}
			}
		}
		$this->echoTaxonArray($hierarchyArr);
	}
	
	private function echoTaxonArray($node){
		if($node){
			uksort($node, array($this,"cmp"));
			$indent = $this->indentValue; 
			$this->indentValue += 10;
			foreach($node as $key => $value){
				$sciName = "";
				$taxonRankId = 0;
				if(array_key_exists($key,$this->taxaArr)){
					$sciName = $this->taxaArr[$key]["sciname"];
					$sciName = str_replace($this->targetStr,"<b>".$this->targetStr."</b>",$sciName);
					$taxonRankId = $this->taxaArr[$key]["rankid"];
					if($this->taxaArr[$key]["rankid"] >= 180){
						$sciName = "<i>".$sciName."</i>";
					}
				}
				elseif(!$key){
					$sciName = "&nbsp;";
				}
				else{
					$sciName = "<br/>Problematic Rooting (".$key.")";
				}
				echo "<div style='margin-left:".$indent.";'>";
				echo "<a href='taxonomyeditor.php?target=".$key."'>".$sciName."</a>";
				if($this->searchTaxonRank < 140 && $taxonRankId == 140){
					echo '<a href="taxonomydisplay.php?target='.$sciName.'">';
					echo '<img src="../../images/tochild.png" style="width:9px;" />';
					echo '</a>';
				}
				echo "</div>";
				if(array_key_exists($key,$this->taxaArr) && array_key_exists("synonyms",$this->taxaArr[$key])){
					$synNameArr = $this->taxaArr[$key]["synonyms"];
					asort($synNameArr);
					foreach($synNameArr as $synTid => $synName){
						$synName = str_replace($this->targetStr,"<b>".$this->targetStr."</b>",$synName);
						echo "<div style='margin-left:".($indent+20).";'>";
						echo "[<a href='taxonomyeditor.php?target=".$synTid."'><i>".$synName."</i></a>]";
						echo "</div>";
					}
				}
				if(is_array($value)){
					$this->echoTaxonArray($value);
				}
			}
			$this->indentValue -= 10;
		}
		else{
			echo "<div style='margin:20px;'>No taxa found matching your search</div>";
		}
	}

	private function cmp($a, $b){
		$sciNameA = (array_key_exists($a,$this->taxaArr)?$this->taxaArr[$a]["sciname"]:"unknown (".$a.")");
		$sciNameB = (array_key_exists($b,$this->taxaArr)?$this->taxaArr[$b]["sciname"]:"unknown (".$b.")");
		return strcmp($sciNameA, $sciNameB);
	}
	
	public function getTargetStr(){
		return $this->targetStr;
	}
}


?>