<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/TaxonomyUtilities.php');

class ChecklistLoaderManager {

	private $conn;
	private $clid;
	private $problemTaxa = array();
	private $errorArr = array();
	private $errorStr = '';
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function uploadCsvList($hasHeader, $thesId){
		set_time_limit(120);
		ini_set("max_input_time",120);
		ini_set('auto_detect_line_endings', true);
		$successCnt = 0;

		$fh = fopen($_FILES['uploadfile']['tmp_name'],'r') or die("Can't open file. File may be too large. Try uploading file in sections.");

		$headerArr = Array();
		if($hasHeader){
			$headerData = fgetcsv($fh);
			foreach($headerData as $k => $v){
				$vStr = strtolower($v);
				$vStr = str_replace(Array(" ",".","_"),"",$vStr);
				$vStr = str_replace(Array("scientificnamewithauthor","scientificname","taxa","species","taxon"),"sciname",$vStr);
				$headerArr[$vStr] = $k;
			}
		}
		else{
			$headerArr["sciname"] = 0;
		}
		
		if(array_key_exists("sciname",$headerArr)){
			while($valueArr = fgetcsv($fh)){
				$tid = 0;
				$rankId = 0;
				$sciName = ""; $family = "";
				$sciNameStr = $this->cleanInStr($valueArr[$headerArr["sciname"]]);
				$noteStr = '';
				if($sciNameStr){
					$sciNameArr = TaxonomyUtilities::parseSciName($sciNameArr);
					//Check name is in taxa table and grab tid if it is
					$sql = "";
					if($thesId && is_numeric($thesId)){
						$sql = 'SELECT t2.tid, ts.family, t2.rankid '.
							'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
							'INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid '.
							'WHERE (ts.taxauthid = '.$thesId.') ';
					}
					else{
						$sql = 'SELECT t.tid, ts.family, t.rankid '.
							'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
							'WHERE ts.taxauthid = 1 ';
					}
					$cleanSciName = $this->encodeString($sciNameArr['sciname']);
					$sql .= 'AND (t.sciname IN("'.$sciNameStr.'"'.($cleanSciName?',"'.$cleanSciName.'"':'').'))';
					$rs = $this->conn->query($sql);
					if($rs){
						if($row = $rs->fetch_object()){
							$tid = $row->tid;
							$family = $this->cleanOutStr($row->family);
							$rankId = $row->rankid;
						}
						$rs->close();
					}
					
					//Load taxon into checklist
					if($tid){
						if($rankId >= 180){
							$sqlInsert = '';
							$sqlValues = '';
							if(array_key_exists('family',$headerArr) && strtolower($family) != strtolower($valueArr[$headerArr['family']])){
								$sqlInsert .= ',familyoverride';
								$sqlValues .= ',"'.$this->cleanInStr($valueArr[$headerArr['family']]).'"';
							}
							if(array_key_exists('habitat',$headerArr) && $valueArr[$headerArr['habitat']]){
								$sqlInsert .= ',habitat';
								$sqlValues .= ',"'.$this->cleanInStr($valueArr[$headerArr['habitat']]).'"';
							}
							if(array_key_exists('abundance',$headerArr) && $valueArr[$headerArr['abundance']]){
								$sqlInsert .= ',abundance';
								$sqlValues .= ',"'.$this->cleanInStr($valueArr[$headerArr['abundance']]).'"';
							}
							if($noteStr || (array_key_exists('notes',$headerArr) && $valueArr[$headerArr['notes']])){
								if(array_key_exists('notes',$headerArr) && $valueArr[$headerArr['notes']]){
									if($noteStr) $noteStr .= '; ';
									$noteStr .= $valueArr[$headerArr['notes']];
								}
								$sqlInsert .= ',notes';
								$sqlValues .= ',"'.$this->cleanInStr($noteStr).'"';
							}
							$sql = 'INSERT INTO fmchklsttaxalink (tid,clid'.$sqlInsert.') VALUES ('.$tid.', '.$this->clid.$sqlValues.')';
							//echo $sql;
							if($this->conn->query($sql)){
								$successCnt++;
							}
							else{
								$this->errorArr[] = $sciNameStr." (TID = $tid) failed to load<br />Error msg: ".$this->conn->error;
								//echo $sql."<br />";
							}
						}
						else{
							$this->errorArr[] = $sciNameStr." failed to load (taxon must be of genus, species, or infraspecific ranking)";
						}
					}
					else{
						$this->problemTaxa[] = $cleanSciName;
						//$statusStr = $sciNameStr." failed to load (misspelled or not yet in taxonomic thesaurus)";
						//$failCnt++;
					}
				}
			}
			fclose($fh);
		}
		else{
			$this->errorArr[] = '<div style="color:red;">ERROR: unable to locate sciname field</div>';
		}
		return $successCnt;
	}
	
	public function resolveProblemTaxa(){
		if($this->problemTaxa){
			$taxUtil = new TaxonomyUtilities();
			echo '<table class="styledtable">';
			echo '<tr><th>Cnt</th><th>Name</th><th>Actions</th></tr>';
			$cnt = 1;
			foreach($this->problemTaxa as $nameStr){
				echo '<tr>';
				echo '<td>'.$cnt.'</td>';
				echo '<td>'.$nameStr.'</td>';
				echo '<td>';
				//Check taxonomic thesaurus to see if it should be added to thesaurus
				if($taxaArr = $taxUtil->getEolTaxonArr($nameStr)){
					if($tid = $taxUtil->loadNewTaxon($taxaArr)){
						$this->addTaxonToChecklist($tid);
						
						echo '<div>';
							
						echo '</div>';
					}
					else{
						echo '<div>';
							
						echo '</div>';
					}
				}
				else{
					//Check database for close matches
					echo '<div>';
					
					echo '</div>';
				}
				echo '</td>';
				echo '</tr>';
				flush();
				ob_flush();
				$cnt++;
			}
			echo '</table>';
		}
	}
	
	private function addTaxonToChecklist($tid){
		$status = true;
		$sql = 'INSERT INTO fmchklsttaxalink(clid,tid) '.
			'VALUES('.$this->clid.','.$tid.')';
		if(!$this->conn->query($sql)){
			$this->errorStr = 'ERROR adding new taxon to checklist: '.$this->conn->error;
			$status = false;
		}
		return $status;
	}

	//Setters and getters
	public function setClid($c){
		if($c && is_numeric($c)){
			$this->clid = $c;
		}
	}

	public function getProblemTaxa(){
		return $this->problemTaxa;
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	public function getChecklistMetadata(){
		$retArr = array();
		if($this->clid){
			$sql = 'SELECT c.name, c.authors FROM fmchecklists c '.
				'WHERE c.clid = '.$this->clid;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$retArr['name'] = $row->name;
				$retArr['authors'] = $row->authors;
			}
		}
		return $retArr;
	}

	public function getThesauri(){
		$retArr = Array();
		$sql = "SELECT taxauthid, name FROM taxauthority WHERE isactive = 1";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->taxauthid] = $row->name;
		}
		return $retArr;
	}

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function encodeString($inStr){
		global $charset;
		$retStr = $inStr;
		//Get rid of curly quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);
		
		if($inStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($charset) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
 		}
		return $retStr;
	}
}
?>