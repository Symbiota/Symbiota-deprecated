<?php
class FileUpload extends DataUploadManager{

	function __construct() {
 		parent::__construct();
 		set_time_limit(600);
	}

	public function analyzeFile(){
	 	$this->readUploadParameters();
		//Just read first line of file to report what fields will be loaded, ignored, and required fulfilled
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'rb') or die("Can't open file");
		$headerData = fgets($fh);
		$headerArr = explode("\t",$headerData);
		$sourceArr = Array();
		foreach($headerArr as $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$sourceArr[] = $fieldStr;
			}
			else{
				break;
			}
		}
		
		$this->echoFieldMapTable($sourceArr);
	}
 	
	public function uploadData(){
	 	$this->readUploadParameters();
		set_time_limit(200);
		ini_set("max_input_time",120);
		ini_set("upload_max_filesize",10);
		$fh = fopen($_FILES['uploadfile']['tmp_name'],'rb') or die("Can't open file");
		$headerData = fgets($fh);
		$headerArr = explode("\t",$headerData);
		foreach($headerArr as $k => $hv){
			$fieldStr = strtolower(trim($hv));
			if($fieldStr){
				$headerArr[$k] = $fieldStr;
			}
			else{
				break;
			}
		}
		$colCnt = 0;
		$sourceArr = Array();
		$targetArr = Array();
		$tempSourceArr = Array();
		//Load sourceArr with mapped fields (map should contain all matching and unmatcing fields)
		foreach($this->fieldMap as $symbField => $detailArr){
			$sourceField = $detailArr["field"];
			if(in_array($sourceField,$headerArr)){
				$sourceArr[$symbField] = $sourceField;
			}
		}
		
		//Set $sqlBase values (all specimen field names that are mapped to headerArr) 
		foreach($headerArr as $k => $fieldStr){
			$kArr = array_keys($sourceArr,$fieldStr);
			foreach($kArr as $v){
				$targetArr[] = $v;
			}
			$colCnt++;
		}
		$sqlBase = "REPLACE INTO uploadspectemp(collid,".implode(",",$targetArr).") ";

		$this->transferCount = 1;
		$reqFieldsNullCnt = 0;
		while($record = fgets($fh)){
			$recordArr = explode("\t",$record);
			
			//If there is no sciname, see if you can populate by family
			if(!$recordArr[array_search($sourceArr["sciname"],$headerArr)]){
				if(array_key_exists("family",$sourceArr) && $recordArr[array_search($sourceArr["family"],$headerArr)]){
					$recordArr[array_search($sourceArr["sciname"],$headerArr)] = $recordArr[array_search($sourceArr["family"],$headerArr)];
				}
			}
			
			//Load only if there is a scientific name in the record
			if($recordArr[array_search($this->fieldMap["sciname"]["field"],$headerArr)]){
				$sqlValues = "";
				for($x=0;$x<count($headerArr);$x++){
					//Iterate through record values and use to build SQL statement
					if($spKeys = array_keys($sourceArr,$headerArr[$x])){
						//A header field may be linked to multiple Symbiota fields
						foreach($spKeys as $specName){
							$valueStr = trim($recordArr[$x]);
							//If value is encloded by quotes, remove the quotes
							if(substr($valueStr,0,1) == "\"" && substr($valueStr,-1) == "\""){
								$valueStr = substr($valueStr,1,strlen($valueStr)-2);
							}
							$valueStr = str_replace("\"","'",$valueStr);
							//Load data
							$type = $this->fieldMap[$specName]["type"];
							switch($type){
								case "numeric":
									if(!$valueStr) $valueStr = "\N";
									$sqlValues .= ",".$valueStr;
									break;
								case "date":
									if(($dateStr = strtotime($valueStr))){
										$sqlValues .= ",\"".date('Y-m-d H:i:s', $dateStr)."\"";
									} 
									else{
										$sqlValues .= ",\"\"";
									}
									break;
								default:	//string
									$sqlValues .= ",\"".$valueStr."\"";
									
							}
						}
					}
				}
				$sql = $sqlBase."VALUES(".$this->collId.",".substr($sqlValues,1).")";
				//echo "<div>".$recordCnt.": ".$sql."</div>";
				
				$status = $this->conn->query($sql);
				if($status){
					//echo "<li>";
					//echo "Appending/Replacing observation #".$this->transferCount.": SUCCESS";
					//echo "</li>";
				}
				else{
					echo "<li>FAILED adding record #".$this->transferCount."</li>";
					echo "<div style='margin-left:10px;'>Error: ".$this->conn->error."</div>";
					//echo "<div style='margin:0px 0px 10px 10px;'>SQL: $sql</div>";
				}
				$this->transferCount++;
			}
			else{
				echo "<li>Record skipped due to lack of scientific name</li>";
			}
		}
		fclose($fh);
		
		$this->finalTransferSteps();
    }
}
	
?>
