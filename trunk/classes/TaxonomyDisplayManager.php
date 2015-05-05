<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/TaxonomyUtilities.php');

class TaxonomyDisplayManager{

	private $conn;
	private $taxaArr = Array();
	private $targetStr = "";
	private $searchTaxonRank = 0;
	private $displayAuthor = 0;
	
	function __construct($target){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->targetStr = $this->conn->real_escape_string(trim(ucfirst($target)));
	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
 	
	public function getTaxa($displayFullTree = false){
		//Temporary code: check to make sure taxaenumtree is populated
		//This code can be removed somewhere down the line
		$sqlTest = 'SELECT tid FROM taxaenumtree LIMIT 1';
		$rsTest = $this->conn->query($sqlTest);
		if(!$rsTest->num_rows){
			echo '<div style="color:red;margin:30px;">';
			echo 'NOTICE: Building new taxonomic hierarchy table (taxaenumtree).<br/>';
			echo 'This may take a few minutes, but only needs to be done once.<br/>';
			echo 'Do not terminate this process early.';
			echo '</div>';
			ob_flush();
			flush();
			$taxMainObj = new TaxonomyUtilities();
			$taxMainObj->buildHierarchyEnumTree();
		}
		$rsTest->free();
		
		//Get target taxa (we don't want children and parents of non-accepted taxa, so we'll get those later) 
		$taxaParentIndex = Array();
		if($this->targetStr){
			$subGenera = array();
			$sql1 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, ts.parenttid, ts.tidaccepted '.
				'FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid '.
				'LEFT JOIN taxstatus ts1 ON t.tid = ts1.tidaccepted '.
				'LEFT JOIN taxa t1 ON ts1.tid = t1.tid '.
				'WHERE (ts.taxauthid = 1 OR ts.taxauthid IS NULL) AND (ts1.taxauthid = 1 OR ts1.taxauthid IS NULL) ';
			if(is_numeric($this->targetStr)){
				$sql1 .= 'AND (t.tid IN('.implode(',',$this->targetStr).') OR (ts1.tid = '.$this->targetStr.'))';
			}
			elseif(strpos($this->targetStr," ")){
				$sql1 .= 'AND ((t.sciname LIKE "'.$this->targetStr.'%") OR (t1.sciname LIKE "'.$this->targetStr.'%"))';
			}
			else{
				$sql1 .= 'AND ((t.sciname = "'.$this->targetStr.'") OR (t1.sciname = "'.$this->targetStr.'"))';
			}
			//echo "<div>".$sql1."</div>";
			$rs1 = $this->conn->query($sql1);
			while($row1 = $rs1->fetch_object()){
				$tid = $row1->tid;
				$parentTid = $row1->parenttid;
				if($tid == $row1->tidaccepted || !$row1->tidaccepted){
					$this->taxaArr[$tid]["sciname"] = $row1->sciname;
					$this->taxaArr[$tid]["author"] = $row1->author; 
					$this->taxaArr[$tid]["parenttid"] = $parentTid; 
					$this->taxaArr[$tid]["rankid"] = $row1->rankid;
					if($row1->rankid == 190) $subGenera[] = $tid;
					$this->searchTaxonRank = $row1->rankid;
					$taxaParentIndex[$tid] = ($parentTid?$parentTid:0);
				}
				else{
					$synName = $row1->sciname;
					if($this->displayAuthor) $synName .= ' '.$row1->author;
					$this->taxaArr[$row1->tidaccepted]["synonyms"][$tid] = $synName;
				}
			}
			$rs1->free();
		}

		$hierarchyArr = Array();
		if($this->taxaArr){
			//Get direct parents and children, but only accepted children
			$tidStr = implode(',',array_keys($this->taxaArr));
			$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, ts.parenttid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '. 
				'INNER JOIN taxaenumtree te ON t.tid = te.tid '.
				'WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) AND (te.taxauthid = 1) '.
				'AND ((te.parenttid IN('.$tidStr.')) OR (t.tid IN('.$tidStr.'))) ';
			if($this->searchTaxonRank < 140 && !$displayFullTree) $sql2 .= "AND t.rankid <= 140 ";
			//echo $sql2."<br>";
			$rs2 = $this->conn->query($sql2);
			while($row2 = $rs2->fetch_object()){
				$tid = $row2->tid;
				$parentTid = $row2->parenttid;
				$this->taxaArr[$tid]["sciname"] = $row2->sciname;
				$this->taxaArr[$tid]["author"] = $row2->author; 
				$this->taxaArr[$tid]["rankid"] = $row2->rankid;
				$this->taxaArr[$tid]["parenttid"] = $parentTid; 
				if($row2->rankid == 190) $subGenera[] = $tid;
				if($parentTid) $taxaParentIndex[$tid] = $parentTid;
			}
			$rs2->free();
			
			//Get parent taxa
			$sql3 = 'SELECT DISTINCT t.tid, t.sciname, t.author, t.rankid, ts.parenttid '.
				'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.parenttid '. 
				'INNER JOIN taxstatus ts ON t.tid = ts.tid '. 
				'WHERE (te.taxauthid = 1) AND (ts.taxauthid = 1) AND (te.tid IN('.$tidStr.')) ';
			//echo $sql3."<br>";
			$rs3 = $this->conn->query($sql3);
			while($row3 = $rs3->fetch_object()){
				$tid = $row3->tid;
				$parentTid = $row3->parenttid;
				$this->taxaArr[$tid]["sciname"] = $row3->sciname;
				$this->taxaArr[$tid]["author"] = $row3->author; 
				$this->taxaArr[$tid]["rankid"] = $row3->rankid;
				$this->taxaArr[$tid]["parenttid"] = $parentTid; 
				if($row3->rankid == 190) $subGenera[] = $tid;
				if($parentTid) $taxaParentIndex[$tid] = $parentTid;
			}
			$rs3->free();
			
			//Get synonyms for all accepted taxa
			$synTidStr = implode(",",array_keys($this->taxaArr));
			$sqlSyns = 'SELECT ts.tidaccepted, t.tid, t.sciname, t.author, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'WHERE (ts.tid <> ts.tidaccepted) AND (ts.taxauthid = 1) AND (ts.tidaccepted IN('.$synTidStr.'))';
			//echo $sqlSyns;
			$rsSyns = $this->conn->query($sqlSyns);
			while($row = $rsSyns->fetch_object()){
				$synName = $row->sciname;
				if($row->rankid > 140){
					$synName = '<i>'.$row->sciname.'</i>';
				}
				if($this->displayAuthor) $synName .= ' '.$row->author;
				$this->taxaArr[$row->tidaccepted]["synonyms"][$row->tid] = $synName;
			}
			$rsSyns->free();

			//Grab parentTids that are not indexed in $taxaParentIndex. This would be due to a parent mismatch or a missing hierarchy definition
			$orphanTaxa = array_unique(array_diff($taxaParentIndex,array_keys($taxaParentIndex)));
			if($orphanTaxa){
				$sqlOrphan = "SELECT t.tid, t.sciname, t.author, ts.parenttid, t.rankid ".
					"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
					"WHERE (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) AND (t.tid IN (".implode(",",$orphanTaxa)."))";
				//echo $sqlOrphan;
				$rsOrphan = $this->conn->query($sqlOrphan);
				while($row4 = $rsOrphan->fetch_object()){
					$tid = $row4->tid;
					$taxaParentIndex[$tid] = $row4->parenttid;
					$this->taxaArr[$tid]["sciname"] = $row4->sciname; 
					$this->taxaArr[$tid]["author"] = $row4->author;
					$this->taxaArr[$tid]["parenttid"] = $row4->parenttid; 
					$this->taxaArr[$tid]["rankid"] = $row4->rankid;
					if($row4->rankid == 190) $subGenera[] = $tid;
				}
				$rsOrphan->free();
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
			//Adjust scientific name display for subgenera
			foreach($subGenera as $subTid){
				$genusDisplay = $this->taxaArr[$this->taxaArr[$subTid]['parenttid']]['sciname'];
				$subGenusDisplay = $genusDisplay.' ('.$this->taxaArr[$subTid]['sciname'].')';
				$this->taxaArr[$subTid]['sciname'] = $subGenusDisplay;
			}
		}
		$this->echoTaxonArray($hierarchyArr,$displayFullTree);
	}

	private function echoTaxonArray($node,$displayFullTree){
		if($node){
			uksort($node, array($this,"cmp"));
			//$indent = $this->indentValue; 
			//$this->indentValue += 10;
			foreach($node as $key => $value){
				$sciName = "";
				$taxonRankId = 0;
				if(array_key_exists($key,$this->taxaArr)){
					$sciName = $this->taxaArr[$key]["sciname"];
					$sciName = str_replace($this->targetStr,"<b>".$this->targetStr."</b>",$sciName);
					$taxonRankId = $this->taxaArr[$key]["rankid"];
					if($this->taxaArr[$key]["rankid"] >= 180){
						$sciName = " <i>".$sciName."</i> ";
					}
					if($this->displayAuthor) $sciName .= ' '.$this->taxaArr[$key]["author"];
				}
				elseif(!$key){
					$sciName = "&nbsp;";
				}
				else{
					$sciName = "<br/>Problematic Rooting (".$key.")";
				}
				$indent = $taxonRankId;
				if($indent > 230) $indent -= 10;
				//echo "<div style='margin-left:".$indent."px;'>";
				echo "<div>".str_repeat('&nbsp;',$indent/5);
				echo "<a href='taxonomyeditor.php?target=".$key."'>".$sciName."</a>";
				if($this->searchTaxonRank < 140 && !$displayFullTree && $taxonRankId == 140){
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
						//echo "<div style='padding-left:".($indent+30)."px;'>";
						echo "<div>".str_repeat('&nbsp;',$indent/5).str_repeat('&nbsp;',7);
						echo "[<a href='taxonomyeditor.php?target=".$synTid."'>".$synName."</a>]";
						echo "</div>";
					}
				}
				if(is_array($value)){
					$this->echoTaxonArray($value,$displayFullTree);
				}
			}
			//$this->indentValue -= 10;
		}
		else{
			echo "<div style='margin:20px;'>No taxa found matching your search</div>";
		}
	}

	public function setDisplayAuthor($display){
		$this->displayAuthor = $display;
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