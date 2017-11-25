<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');

class ChecklistLoaderManager {

	private $conn;
	private $clid;
	private $clMeta = array();
	private $problemTaxa = array();
	private $errorArr = array();
	private $errorStr = '';

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function uploadCsvList($thesId){
		set_time_limit(300);
		ini_set("max_input_time",300);
		ini_set('auto_detect_line_endings', true);
		$successCnt = 0;

		$fh = fopen($_FILES['uploadfile']['tmp_name'],'r') or die("Can't open file. File may be too large. Try uploading file in sections.");

		$headerArr = Array();
		$headerData = fgetcsv($fh);
		foreach($headerData as $k => $v){
			$vStr = strtolower($v);
			$vStr = str_replace(Array(" ",".","_"),"",$vStr);
			if(in_array($vStr, Array("scientificnamewithauthor","scientificname","taxa","speciesname","taxon"))){
				$vStr = 'sciname';
			}
			$headerArr[$vStr] = $k;
		}
		if(array_key_exists("sciname",$headerArr)){
			$cnt = 0;
			ob_flush();
			flush();
			while($valueArr = fgetcsv($fh)){
				$tid = 0;
				$rankId = 0;
				$sciName = ""; $family = "";
				$sciNameStr = $this->cleanInStr($valueArr[$headerArr["sciname"]]);
				$noteStr = '';
				if($sciNameStr){
					$sciNameArr = TaxonomyUtilities::parseScientificName($sciNameStr,$this->conn);
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
							$rankId = $row->rankid;
						}
						$rs->free();
					}

					//Load taxon into checklist
					if($tid){
						if($rankId >= 180){
							$sqlInsert = '';
							$sqlValues = '';
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
					$cnt++;
					if($cnt%500 == 0) {
						echo '<li style="margin-left:10px;">'.$cnt.' taxa loaded</li>';
						ob_flush();
						flush();
					}
				}
			}
			fclose($fh);
			if($cnt && $this->clMeta['type'] == 'rarespp'){
				$occurMain = new OccurrenceMaintenance($this->conn);
				$occurMain->protectStateRareSpecies();
			}
		}
		else{
			$this->errorStr = 'ERROR: unable to locate scientific name column';
		}
		return $successCnt;
	}

	public function resolveProblemTaxa(){
		if($this->problemTaxa){
			//$taxHarvester = new TaxonomyHarvester();
			echo '<table class="styledtable" style="font-family:Arial;font-size:12px;">';
			echo '<tr><th>Cnt</th><th>Name</th><th>Actions</th></tr>';
			$cnt = 1;
			foreach($this->problemTaxa as $nameStr){
				echo '<tr>';
				echo '<td>'.$cnt.'</td>';
				echo '<td>'.$nameStr.'</td>';
				echo '<td>';
				//Check taxonomic thesaurus to see if it should be added to thesaurus
				/*
				if($taxaArr = $taxHarvester->getEolTaxonArr($nameStr)){
					if($tid = $taxHarvester->loadNewTaxon($taxaArr)){
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
				*/
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
			$this->setChecklistMetadata();
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

	private function setChecklistMetadata(){
		if($this->clid){
			$sql = 'SELECT name, authors, type '.
				'FROM fmchecklists '.
				'WHERE clid = '.$this->clid;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->clMeta['name'] = $row->name;
				$this->clMeta['authors'] = $row->authors;
				$this->clMeta['type'] = $row->type;
			}
			$rs->free();
		}
	}

	public function getChecklistMetadata(){
		return $this->clMeta;
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
		//Get rid of Windows curly (smart) quotes
		$search = array(chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151));
		$replace = array("'","'",'"','"','*','-','-');
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