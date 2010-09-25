<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistLoaderManager {

	private $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function uploadChecklist($clid, $hasHeader){
		set_time_limit(120);
		ini_set("max_input_time",120);
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'r') or die("Can't open file");
		
		$headerArr = Array();
		if($hasHeader){
			$headerStr = fgets($fh);
			$headerData = explode("\t",$headerStr);
			foreach($headerData as $k => $v){
				$vStr = strtolower($v);
				$vStr = str_replace(Array(" ",".","_"),"",$vStr);
				$vStr = str_replace(Array("scientificname","taxa","species","taxon"),"sciname",$vStr);
				$headerArr[$vStr] = $k;
			}
		}
		else{
			$headerArr["sciname"] = 0;
		}
		
		if(array_key_exists("sciname",$headerArr)){
			echo "<ul>";
			echo "<li>Beginning process to load checklist</li>";
			echo "<li>File uploaded and now reading file...</li>";
			echo "<ol>";
			$successCnt = 0;
			$failCnt = 0;
			while($line = fgets($fh)){
				$valueArr = explode("\t",$line);
				$statusStr = "";
				$tid = 0;
				$rankId = 0;
				$sciName = ""; $family = "";
				$sciNameStr = $valueArr[$headerArr["sciname"]];
				if($sciNameStr){
					$sql = "SELECT t.tid, ts.family, t.rankid ".
						"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
						"WHERE ts.taxauthid = 1 AND t.sciname = '".$sciNameStr."'";
					//echo $sql;
					$rs = $this->conn->query($sql);
					if($row = $rs->fetch_object()){
						$tid = $row->tid;
						$family = $row->family;
						$sciName = $sciNameStr;
						$rankId = $row->rankid;
					}
					$rs->close();
		
					if(!$tid){
						//$sciNameStr not in database, thus try parsing out author  
						$unitInd1 = ""; $unitName1 = "";
						$unitInd2 = ""; $unitName2 = "";
						$unitInd3 = ""; $unitName3 = "";
						$sciNameArr = explode(" ",$sciNameStr);
						if(count($sciNameArr)){
							if(strtolower($sciNameArr[0]) == "x"){
								$unitInd1 = array_shift($sciNameArr);
							}
							$unitName1 = array_shift($sciNameArr);
							if(count($sciNameArr)){
								if(strtolower($sciNameArr[0]) == "x"){
									$unitInd2 = array_shift($sciNameArr);
								}
								$unitName1 = array_shift($sciNameArr);
							}				
						}
						while($sciStr = array_shift($sciNameArr)){
							if($sciStr == "ssp." || $sciStr == "ssp" || $sciStr == "subsp." || $sciStr == "subsp" || $sciStr == "var." || $sciStr == "var" || $sciStr == "f." || $sciStr == "forma"){
								$unitInd3 = $sciStr;
								$unitName3 = array_shift($sciNameArr);
							}
						}
						$sciName = $unitInd1." ".$unitName1." ".$unitInd2." ".$unitName2." ".$unitInd3." ".$unitName3;
						$sql = "SELECT t.tid, ts.family ".
							"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
							"WHERE ts.taxauthid = 1 AND t.sciname = '".$sciName."'";
						$rs = $this->conn->query($sql);
						if($row = $rs->fetch_object()){
							$tid = $row->tid;
							$family = $row->family;
							$sciName = $sciNameStr;
							$rankId = $row->rankid;
						}
						$rs->close();
					}
					
					//Load taxon into checklist
					if($tid && $rankId > 140){
						$sqlInsert = "";
						$sqlValues = "";
						if(array_key_exists("family",$headerArr) && strtolower($family) != strtolower($valueArr[$headerArr["family"]])){
							$sqlInsert .= ",familyoverride";
							$sqlValues .= ",'".$valueArr[$headerArr["family"]]."'";
						}
						if(array_key_exists("habitat",$headerArr) && $valueArr[$headerArr["habitat"]]){
							$sqlInsert .= ",habitat";
							$sqlValues .= ",'".$valueArr[$headerArr["habitat"]]."'";
						}
						if(array_key_exists("abundance",$headerArr) && $valueArr[$headerArr["abundance"]]){
							$sqlInsert .= ",abundance";
							$sqlValues .= ",'".$valueArr[$headerArr["abundance"]]."'";
						}
						if(array_key_exists("notes",$headerArr) && $valueArr[$headerArr["notes"]]){
							$sqlInsert .= ",notes";
							$sqlValues .= ",'".$valueArr[$headerArr["notes"]]."'";
						}
						$sql = "INSERT INTO fmchklsttaxalink (tid,clid".$sqlInsert.") VALUES (".$tid.", ".$clid.$sqlValues.")";
						//echo $sql;
						if($this->conn->query($sql)){
							$successCnt++;
						}
						else{
							$failCnt++;
							$statusStr = $sciNameStr." (TID = $tid) failed to load<br />Error msg: ".$this->conn->error;
						}
					}
					else{
						$statusStr = $sciNameStr." failed to load";
						$failCnt++;
					}
				}
				if($statusStr) echo "<li><span style='color:red;'>ERROR:</span> ".$statusStr."</li>";
			}
			echo "</ol>";
			fclose($fh);
			echo "<li>Finished loading checklist</li>";
			echo "<li>".$successCnt." names loaded successfully</li>";
			echo "<li>".$failCnt." failed to load</li>";
			echo "</ul>";
		}
		else{
			echo "<div style='color:red;'>ERROR: unable to located sciname field</div>";
		}
	}
 }

 ?>