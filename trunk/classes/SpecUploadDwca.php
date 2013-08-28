<?php
class SpecUploadDwca extends SpecUploadBase{
	
	private $baseFolderName;
	private $metaArr;
	
	function __construct() {
 		parent::__construct();
		$this->setUploadTargetPath();
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function uploadFile(){
		if(!$this->baseFolderName){
			if($this->digirPath){
				//import DwC-A file onto local server and set the base file name 
				$this->baseFolderName = $this->collMetadataArr["institutioncode"].($this->collMetadataArr["collectioncode"]?$this->collMetadataArr["collectioncode"].'_':'').time();
				mkdir($this->uploadTargetPath.$this->baseFolderName,777);
				$fullPath = $this->uploadTargetPath.$this->baseFolderName.'/dwca.zip';
				if(!copy($this->digirPath,$fullPath)){
					echo '<li>ERROR: unable to upload file (path: '.$fullPath.') </li>';
				}
			}
			else{
				echo '<li>ERROR: Path to Darwin Core Archive not defined </li>';
			}
		}
		if($this->baseFolderName){
			$this->unpackArchive();
		}
		else{
			echo '<li>ERROR: base file name not set (path: '.$this->uploadTargetPath.$this->baseFolderName.')</li>';
		}
		return $this->baseFolderName;
	}

	public function analyzeUpload(){
		$this->readMetaFile();
	}

	private function unpackArchive(){
		//Extract archive
		$zip = new ZipArchive;
		$targetPath = $this->uploadTargetPath.$this->baseFolderName;
		$zip->open($targetPath.'/dwca.zip');
		$zip->extractTo($targetPath);
		$zip->close();
	}
	
	private function readMetaFile($full = 0){
		//Read meta.xml file
		if($this->uploadTargetPath.$this->baseFolderName.'/meta.xml'){
			$metaDoc = new DOMDocument('1.0', 'iso-8859-1');
			$metaDoc->load($this->uploadTargetPath.$this->baseFolderName.'/meta.xml');
			$coreId = '';
			//Get core (occurrences) file name
			if($coreElements = $metaDoc->getElementsByTagName('core')){
				//There many be more than one core elements, thus look for occurrence element
				foreach($coreElements as $coreElement){
					$rowType = $coreElement->getAttribute('rowType');
					if(stripos($rowType,'occurrence')){
						//Get index id
						if($idElement = $coreElement->getElementsByTagName('id')){
							$coreId = $idElement->item(0)->getAttribute('index');
						}
						else{
							$this->errorArr[] = 'WARNING: Core ID absent';
						}
						//Get file name
						if($locElements = $coreElement->getElementsByTagName('location')){
							$this->metaArr['occur']['name'] = $locElements->item(0)->nodeValue;
						}
						else{
							$this->errorArr[] = 'ERROR: Unable to obtain the occurrence file name from meta.xml';
						}
						if($full){
							//Get the rest of the core attributes
							$this->metaArr['occur']['encoding'] = $coreElement->getAttribute('encoding');
							$this->metaArr['occur']['fieldsTerminatedBy'] = $coreElement->getAttribute('fieldsTerminatedBy');
							$this->metaArr['occur']['linesTerminatedBy'] = $coreElement->getAttribute('linesTerminatedBy');
							$this->metaArr['occur']['fieldsEnclosedBy'] = $coreElement->getAttribute('fieldsEnclosedBy');
							$this->metaArr['occur']['ignoreHeaderLines'] = $coreElement->getAttribute('ignoreHeaderLines');
							$this->metaArr['occur']['rowType'] = $rowType;
							//Get the Core field names
							if($fieldElements = $coreElement->getElementsByTagName('fields')){
								foreach($fieldElements as $fieldElement){
									$this->metaArr['occur']['fields'][$fieldElement->getAttribute('index')] = $fieldElement->getAttribute('term');
								}
							}
						}
					}
				}
			}
			if($this->metaArr){
				$extensionElements = $metaDoc->getElementsByTagName('extension');
				foreach($extensionElements as $extensionElement){
					$rowType = $extensionElement->getAttribute('rowType');
					$extCoreId = '';
					if($coreidElement = $extensionElement->getElementsByTagName('coreid')){
						$extCoreId = $coreidElement->item(0)->getAttribute('index');
					}
					//If coreIds equal, retrieve determination data
					if($coreId === '' || $coreId === $extCoreId){
						$tagName = '';
						if(stripos($rowType,'identification')){
							//Is identification data related to core data
							$tagName = 'ident';
						}
						elseif(stripos($rowType,'image')){
							//Is image data related to core data
							$tagName = 'image';
						}
						if($tagName){
							if($locElements = $extensionElement->getElementsByTagName('location')){
								$this->metaArr[$tagName]['name'] = $locElements->item(0)->nodeValue;
							}
							else{
								$this->errorArr[] = 'ERROR: Unable to obtain the '.$tagName.' file name from meta.xml';
							}
							if($full){
								//Get the rest of the core attributes
								$this->metaArr[$tagName]['encoding'] = $extensionElement->getAttribute('encoding');
								$this->metaArr[$tagName]['fieldsTerminatedBy'] = $extensionElement->getAttribute('fieldsTerminatedBy');
								$this->metaArr[$tagName]['linesTerminatedBy'] = $extensionElement->getAttribute('linesTerminatedBy');
								$this->metaArr[$tagName]['fieldsEnclosedBy'] = $extensionElement->getAttribute('fieldsEnclosedBy');
								$this->metaArr[$tagName]['ignoreHeaderLines'] = $extensionElement->getAttribute('ignoreHeaderLines');
								$this->metaArr[$tagName]['rowType'] = $rowType;
								//Get the Core field names
								if($fieldElements = $extensionElement->getElementsByTagName('fields')){
									foreach($fieldElements as $fieldElement){
										$this->metaArr[$tagName]['fields'][$fieldElement->getAttribute('index')] = $fieldElement->getAttribute('term');
									}
								}
							}
						}
					}					
				}				
			}
			else{
				$this->errorArr[] = 'ERROR: Unable to obtain core element for occurrences from meta.xml';
			}
		}
	}

	public function uploadData($finalTransfer){
		if($this->ulFileName){
		 	$this->readUploadParameters();
			set_time_limit(7200);
		 	ini_set("max_input_time",240);
	
			//First, delete all records in uploadspectemp table associated with this collection
			$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
			$this->conn->query($sqlDel);

			$fullPath = $this->uploadTargetPath.$this->ulFileName;
	 		$fh = fopen($fullPath,'rb') or die("Can't open file");
			
			$headerArr = $this->getHeaderArr($fh);
			
			//Grab data 
			$this->transferCount = 0;
			while($recordArr = $this->getRecordArr($fh)){
				$recMap = Array();
				foreach($this->fieldMap as $symbField => $sMap){
					$indexArr = array_keys($headerArr,$sMap['field']);
					$index = array_shift($indexArr);
					if(array_key_exists($index,$recordArr)){
						$valueStr = $recordArr[$index];
						//If value is enclosed by quotes, remove quotes
						if(substr($valueStr,0,1) == '"' && substr($valueStr,-1) == '"'){
							$valueStr = substr($valueStr,1,strlen($valueStr)-2);
						}
						$recMap[$symbField] = $valueStr;
					}
				}
				$this->loadRecord($recMap);
				unset($recMap);
			}
			fclose($fh);

			//Delete upload file 
			if(file_exists($fullPath)) unlink($fullPath);
			
			$this->finalUploadSteps($finalTransfer);
		}
		else{
			echo "<li>File Upload FAILED: unable to locate file</li>";
		}
	}
	
	private function getHeaderArr($fHandler){
		$headerData = fgets($fHandler);
		//Check to see if we can figure out the delimiter
		if(strpos($headerData,",") === false){
			if(strpos($headerData,"\t") !== false){
				$this->delimiter = "\t";
			}
		}
		//Check to see if file is csv\
		if(substr($this->ulFileName,-4) == ".csv" || strpos($headerData,$this->delimiter.'"') !== false){
			$this->isCsv = true;
		}
		//Grab header terms
		$headerArr = Array();
		if($this->isCsv){
			rewind($fHandler);
			$headerArr = fgetcsv($fHandler,0,$this->delimiter);
		}
		else{
			$headerArr = explode($this->delimiter,$headerData);
		}
		$retArr = array();
		foreach($headerArr as $field){
			$fieldStr = strtolower(trim($field));
			if($fieldStr){
				$retArr[] = $fieldStr;
			}
			else{
				break;
			}
		}
		return $retArr;
	}

	private function getRecordArr($fHandler){
		$recordArr = Array();
		if($this->isCsv){
			$recordArr = fgetcsv($fHandler,0,$this->delimiter);
		}
		else{
			$record = fgets($fHandler);
			if($record) $recordArr = explode($this->delimiter,$record);
		}
		return $recordArr;
	}
	
	public function echoOccurMapTable($autoMap){
		$sourceArr = $this->metaArr['occur']['fields'];
		foreach($sourceArr as $k => $fieldName){
			echo "<tr>\n";
			echo "<td style='padding:2px;'>";
			echo $fieldName;
			echo "<input type='hidden' name='sf[]' value='".$k."' />";
			echo "</td>\n";
			echo "<td>\n";
			echo "<select name='tf[]' style='background:".(!array_key_exists($fieldName,$sourceSymbArr)&&!$isAutoMapped?"yellow":"")."'>";
			echo "<option value=''>Select Target Field</option>\n";
			echo "<option value=''>Leave Field Unmapped</option>\n";
			echo "<option value=''>-------------------------</option>\n";
			if($isAutoMapped){
				//Source Field = Symbiota Field
				foreach($this->symbFields as $sField){
					echo "<option ".(strtolower($tranlatedFieldName)==$sField?"SELECTED":"").">".$sField."</option>\n";
				}
			}
			elseif(array_key_exists($fieldName,$sourceSymbArr)){
				//Source Field is mapped to Symbiota Field
				foreach($this->symbFields as $sField){
					echo "<option ".($sourceSymbArr[$fieldName]==$sField?"SELECTED":"").">".$sField."</option>\n";
				}
			}
			else{
				foreach($this->symbFields as $sField){
					echo "<option>".$sField."</option>\n";
				}
			}
			echo "</select></td>\n";
			echo "</tr>\n";
			
		}
		
		//Build a Source => Symbiota field Map
		$sourceSymbArr = Array();
		foreach($this->fieldMap as $symbField => $fArr){
			if($symbField != 'dbpk') $sourceSymbArr[$fArr["field"]] = $symbField;
		}

		//Output table rows for source data
		sort($this->symbFields);
		$autoMapArr = Array();
		foreach($this->sourceArr as $fieldName){
			$isAutoMapped = false;
			$tranlatedFieldName = str_replace(array('_',' ','.'),'',$fieldName);
			if($autoMap){
				if(array_key_exists($tranlatedFieldName,$this->translationMap)) $tranlatedFieldName = $this->translationMap[$tranlatedFieldName];
				if(in_array($tranlatedFieldName,$this->symbFields)){
					$isAutoMapped = true;
					$autoMapArr[$tranlatedFieldName] = $fieldName;
				}
			}
			echo "<tr>\n";
			echo "<td style='padding:2px;'>";
			echo $fieldName;
			echo "<input type='hidden' name='sf[]' value='".$fieldName."' />";
			echo "</td>\n";
			echo "<td>\n";
			echo "<select name='tf[]' style='background:".(!array_key_exists($fieldName,$sourceSymbArr)&&!$isAutoMapped?"yellow":"")."'>";
			echo "<option value=''>Select Target Field</option>\n";
			echo "<option value=''>Leave Field Unmapped</option>\n";
			echo "<option value=''>-------------------------</option>\n";
			if($isAutoMapped){
				//Source Field = Symbiota Field
				foreach($this->symbFields as $sField){
					echo "<option ".(strtolower($tranlatedFieldName)==$sField?"SELECTED":"").">".$sField."</option>\n";
				}
			}
			elseif(array_key_exists($fieldName,$sourceSymbArr)){
				//Source Field is mapped to Symbiota Field
				foreach($this->symbFields as $sField){
					echo "<option ".($sourceSymbArr[$fieldName]==$sField?"SELECTED":"").">".$sField."</option>\n";
				}
			}
			else{
				foreach($this->symbFields as $sField){
					echo "<option>".$sField."</option>\n";
				}
			}
			echo "</select></td>\n";
			echo "</tr>\n";
		
		
	}

	public function echoIdentMapTable($autoMap){
		
		
	}

	public function echoImageMapTable($autoMap){
		
		
	}

	public function setBaseFolderName($name){
		$this->baseFolderName = $name;
	}

	public function getDbpk(){
		$dbpk = parent::getDbpk();
		if(!$dbpk) $dbpk = 'coreid';
		return $dbpk;
	}

	public function getMetaArr(){
		return $this->metaArr;
	}
}
?>