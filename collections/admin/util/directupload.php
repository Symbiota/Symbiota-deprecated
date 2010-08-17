<?php
class DirectUpload extends DataUploadManager {

 	public function __construct(){
		parent::__construct();
 	}
 	
	public function __destruct(){
 		parent::__destruct();
	}
	
 	public function analyzeFile(){
	 	$this->readUploadParameters();
		$sourceConn = $this->getSourceConnection();
		if($sourceConn){
			$rs = $sourceConn->query($this->queryStr);
			$row = $rs->fetch_assoc();
			$sourceArr = Array();
			foreach($row as $k => $v){
				$sourceArr[] = strtolower($k);
			}
			$rs->close();
			
			$this->echoFieldMapTable($sourceArr);
		}
	}

 	public function uploadData($finalTransfer){
	 	$this->readUploadParameters();
 		$sourceDbpkFieldName = "";
		if(array_key_exists("dbpk",$this->fieldMap)){
			$sourceDbpkFieldName = $this->fieldMap["dbpk"]["field"];
			unset($this->fieldMap["dbpk"]);
		}
		$sqlInsertInto = "INSERT INTO uploadspectemp (collid,dbpk,";
		$sqlInsertInto .= implode(",",array_keys($this->fieldMap));
		$sqlInsertInto .= ") ";
		$sqlInsertValuesBase = "VALUES (".$this->collId;
		
		$sourceConn = $this->getSourceConnection();
		if($sourceConn){
			//Delete all records in uploadspectemp table
			$sqlDel = "DELETE FROM uploadspectemp";
			$this->conn->query($sqlDel);
			
			echo "<li style='font-weight:bold;'>Connected to Source Database</li>";
			set_time_limit(800);
			$sourceConn->query("SET NAMES latin1;");
			//echo "<div>".$this->queryStr."</div><br/>";
			if($result = $sourceConn->query($this->queryStr)){
				echo "<li style='font-weight:bold;'>Results obtained from Source Connection, now reading Resultset... </li>";
				$recCnt = 1;
				while($row = $result->fetch_assoc()){
					//Set DBPK value
					$dbpk = 0;
					$sqlInsertValues = $sqlInsertValuesBase;
					if($sourceDbpkFieldName){
						$sqlInsertValues .= ",\"".$row[$sourceDbpkFieldName]."\"";
					}
					else{
						$sqlInsertValues .= ",".$recCnt;
					}
					//Set Lat/Long values that are both 0 to null
					if(array_key_exists("decimallatitude",$this->fieldMap) && array_key_exists("decimallongitude",$this->fieldMap)){
						if($row[$this->fieldMap["decimallatitude"]["field"]] === 0 && $row[$this->fieldMap["decimallongitude"]["field"]] === 0){
							$row[$this->fieldMap["decimallatitude"]["field"]] = "";
							$row[$this->fieldMap["decimallongitude"]["field"]] = "";
						}
					}
					foreach($this->fieldMap as $symbField => $sourceField){
						$value = $row[$sourceField["field"]];
						if(mb_detect_encoding($value,'UTF-8, ISO-8859-1') == "UTF-8"){
							//$value = utf8_decode($value);
							$value = iconv("UTF-8","ISO-8859-1//TRANSLIT",$value);
				        }
						if($sourceField["type"] == "date"){
							if($datetime = strtotime($value)){
								$value = date('Y-m-d H:i:s',$datetime);
								$sqlInsertValues .= ",\"".$value."\"";
							}
							else{
								$sqlInsertValues .= ",null";
							}
						}
						elseif($sourceField["type"] == "numeric"){
							if(is_numeric($value)){
								$sqlInsertValues .= ",".$value;
							}
							else{
								$sqlInsertValues .= ",null";
							}
						}
						else{
							$value = str_replace("\"","'",$value);
							$value = str_replace(chr(10),"",$value);
							$value = str_replace(chr(11),"",$value);
							$value = str_replace(chr(13),"",$value);
							
							if(array_key_exists("size",$sourceField) && strlen($value) > $sourceField["size"]){
								$value = substr($value,0,$size);
							}
							$sqlInsertValues .= ",\"".$value."\"";
						}
					}
					$sqlInsert = $sqlInsertInto.$sqlInsertValues.")";
					//echo "<div>sqlInsert: ".$sqlInsert."</div>";
					if(!$this->conn->query($sqlInsert)){
						echo "<hr /><div style='color:red;'>ERROR inserting new record: ".$this->conn->error."</div>";
						echo "<div style='font-weight:bold;'>SQL: ".$sqlInsert."</div><hr />";
					}
					$recCnt++;
					if($recCnt%1000 == 0) echo "<li style='font-weight:bold;'>Record Count: $recCnt</li>";
				}
				
				$this->finalUploadSteps($finalTransfer);
				$this->transferCount = $recCnt-1;
				$result->close();
			}
			else{
				echo "<hr /><div style='color:red;'>Unable to create a Resultset with the Source Connection. Check connection parameters, source sql statement, and firewall restriction</div>";
				echo "<div style='color:red;'>ERROR: ".$sourceConn->error."</div><hr />";
				//echo "<div>SQL: $this->sourceSql</div>";
			}
			$sourceConn->close();
		}
	}
	
	private function getSourceConnection() {
		if(!$this->server || !$this->username || !$this->password || !$this->schemaName){
			echo "<div style='color:red;'>One of the connection variables is null (server:$this->server; username:$this->username; schema:$this->schemaName; password: ".($this->password?"******":"")."</div>";
			return null;
		}
		$connection = new mysqli($this->server, $this->username, $this->password, $this->schemaName);
		if(mysqli_connect_errno()){
			echo "<div style='color:red;'>Could not connect to Source database!</div>";
			echo "<div style='color:red;'>ERROR: ".mysqli_connect_error()."</div>";
			return null;
		}
		return $connection;
    }
}
?>
