<?php
class SpecUploadDirect extends SpecUploadManager {

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
			$sql = trim($this->queryStr);
			if(substr($sql,-1) == ";") $sql = substr($sql,0,strlen($sql)-1); 
			if(strlen($sql) > 20 && stripos(substr($sql,-20)," limit ") === false){
				$sql .= " LIMIT 10";
			}
			$rs = $sourceConn->query($sql);
			if(!$rs){
				echo "<li style='font-weight:bold;'>ERROR: Possible syntax error in source SQL</li>";
				return;
			}
			$sourceArr = Array();
			if($row = $rs->fetch_assoc()){
				foreach($row as $k => $v){
					$sourceArr[] = strtolower($k);
				}
			}
			$rs->close();
			$this->sourceArr = $sourceArr;
			//$this->echoFieldMapTable($sourceArr);
		}
	}

 	public function uploadData($finalTransfer){
 		global $charset;
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
			if($sourceDbpkFieldName){
				//Delete all records in uploadspectemp table
				$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
				$this->conn->query($sqlDel);
				
				echo "<li style='font-weight:bold;'>Connected to Source Database</li>";
				set_time_limit(800);
				$sourceConn->query("SET NAMES ".str_replace('-','',strtolower($charset)).";");
				//echo "<div>".$this->queryStr."</div><br/>";
				if($result = $sourceConn->query($this->queryStr)){
					echo "<li style='font-weight:bold;'>Results obtained from Source Connection, now reading Resultset... </li>";
					$recCnt = 1;
					while($row = $result->fetch_assoc()){
						$row = array_change_key_case($row);
						//Set DBPK value
						$dbpk = 0;
						$sqlInsertValues = $sqlInsertValuesBase;
						$sqlInsertValues .= ",\"".$row[$sourceDbpkFieldName]."\"";
						//Set Lat/Long values that are both 0 to null
						if(array_key_exists("decimallatitude",$this->fieldMap) && array_key_exists("decimallongitude",$this->fieldMap)){
							if($row[$this->fieldMap["decimallatitude"]["field"]] === 0 && $row[$this->fieldMap["decimallongitude"]["field"]] === 0){
								$row[$this->fieldMap["decimallatitude"]["field"]] = "";
								$row[$this->fieldMap["decimallongitude"]["field"]] = "";
							}
						}
						foreach($this->fieldMap as $symbField => $sourceField){
							$value = $row[$sourceField["field"]];
							$value = $this->cleanString($value);
							$value = $this->encodeString($value);
							$type = (array_key_exists('type',$sourceField)?$sourceField['type']:'');
							$size = (array_key_exists("size",$sourceField)?$sourceField['size']:0);
							
							if($type == "date"){
								if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)){
									$sqlInsertValues .= ',"'.$value.'"';
								} 
								elseif($datetime = strtotime($value)){
									$value = date('Y-m-d H:i:s',$datetime);
									$sqlInsertValues .= ",\"".$value."\"";
								}
								else{
									$sqlInsertValues .= ",null";
								}
							}
							elseif($type == "numeric"){
								if(is_numeric($value)){
									$sqlInsertValues .= ",".$value;
								}
								else{
									$sqlInsertValues .= ",null";
								}
							}
							else{
								if($size && strlen($value) > $size){
									$value = substr($value,0,$size);
								}
								if($value){
									$sqlInsertValues .= ",\"".$value."\"";
								}
								else{
									$sqlInsertValues .= ",NULL";
								}
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
			else{
				echo "<div style='color:red;'>Source Primary Key has not yet been mapped to a system field</div>";
			}
		}
	}
	
	private function getSourceConnection() {
		if(!$this->server || !$this->username || !$this->password || !$this->schemaName){
			echo "<div style='color:red;'>One of the required connection variables are null. Please resolve.</div>";
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
