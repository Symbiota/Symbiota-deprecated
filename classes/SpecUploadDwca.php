<?php
class SpecUploadDwca extends SpecUploadBase{
	
	private $baseFolderName;
	private $metaArr;
	private $delimiter = ",";
	private $enclosure = '"';
	private $encoding = 'utf-8';

	function __construct() {
 		parent::__construct();
		$this->setUploadTargetPath();
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function uploadFile(){
		//Create download location
		$localFolder = $this->collMetadataArr["institutioncode"].($this->collMetadataArr["collectioncode"]?$this->collMetadataArr["collectioncode"].'_':'').time();
		mkdir($this->uploadTargetPath.$localFolder);
		$fullPath = $this->uploadTargetPath.$localFolder.'/dwca.zip';
		
		if($this->path){
			//DWCA path is stored in the upload profile definition
			//If they incorrectly mapped to the IPT instance, adjust to point to the Archive file 
			if(strpos($this->path,'/resource.do')){
				$this->path = str_replace('/resource.do','/archive.do',$this->path);
			} 
			if(copy($this->path,$fullPath)){
				$this->baseFolderName = $localFolder;
			}
			else{
				$this->outputMsg('<li>ERROR: unable to upload file (path: '.$fullPath.') </li>');
				$this->errorStr = 'ERROR: unable to upload file (path: '.$fullPath.')';
			}
		}
		elseif(array_key_exists('ulfnoverride',$_POST) && $_POST['ulfnoverride']){
			//File was physcially placed on server where Apache can read the file
			if(copy($_POST["ulfnoverride"],$fullPath)){
				$this->baseFolderName = $localFolder;
			}
			else{
				$this->outputMsg('<li>ERROR moving locally placed file, are you sure that path is correct? (path: '.$_POST["ulfnoverride"].') </li>');
				$this->errorStr = 'ERROR moving locally placed file, are you sure that path is correct?';
			}
		}
		elseif(array_key_exists("uploadfile",$_FILES)){
			//File is delivered as a POST stream, probably from browser
			if(move_uploaded_file($_FILES['uploadfile']['tmp_name'], $fullPath)){
				$this->baseFolderName = $localFolder;
			}
			else{
				$msg = 'unknown';
				$err = $_FILES['uploadfile']['error'];
				if($err == 1) $msg = 'uploaded file exceeds the upload_max_filesize directive in php.ini';
				elseif($err == 2) $msg = 'uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				elseif($err == 3) $msg = 'uploaded file was only partially uploaded';
				elseif($err == 4) $msg = 'no file was uploaded';
				elseif($err == 5) $msg = 'unknown error 5';
				elseif($err == 6) $msg = 'missing a temporary folder';
				elseif($err == 7) $msg = 'failed to write file to disk';
				elseif($err == 8) $msg = 'a PHP extension stopped the file upload';
				$this->outputMsg('<li>ERROR uploading file (target: '.$fullPath.'): '.$msg.' </li>');
				$this->errorStr = 'ERROR uploading file: '.$msg;
			}
		}
		
		if($this->baseFolderName){
			if(!$this->unpackArchive()){
				$this->baseFolderName = '';
			}
		}
		return $this->baseFolderName;
	}

	public function analyzeUpload(){
		$status = false;
		if($this->readMetaFile()){
			if(isset($this->metaArr['occur']['fields'])){
				$this->sourceArr = $this->metaArr['occur']['fields'];
				//Set identification and image source fields 
				if(isset($this->metaArr['ident']['fields'])){
					$this->identSourceArr = $this->metaArr['ident']['fields'];
				}
				if(isset($this->metaArr['image']['fields'])){
					$this->imageSourceArr = $this->metaArr['image']['fields'];
				}
			}
			$status = true;
		}
		return $status;
	}

	private function unpackArchive(){
		//Extract archive
		$status = true;
		$zip = new ZipArchive;
		$targetPath = $this->uploadTargetPath.$this->baseFolderName;
		$zip->open($targetPath.'/dwca.zip');
		if($zip->extractTo($targetPath)){
			if(!file_exists($targetPath.'/meta.xml')){
				$path = $this->locateBaseFolder($targetPath);
				if($path) $this->baseFolderName .= $path;
			}
		}
		else{
			$err = $zip->getStatusString();
			if(!$err) $err = 'target path is likely not a valid zip file';
			$this->outputMsg('<li>ERROR unpacking archive file: '.$err.'</li>');
			$this->errorStr = 'ERROR unpacking archive file: '.$err;
			$status = false;
		}
		$zip->close();
		return $status;
	}
	
	private function locateBaseFolder($baseDir, $pathFrag = ''){
		$retPath = '';
		if($pathFrag && file_exists($baseDir.$pathFrag.'/meta.xml')){
			$retPath = $pathFrag;
		}
		else{
			if($handle = opendir($baseDir.$pathFrag)) {
				while (false !== ($item = readdir($handle))) {
					if($item && $item != '.' && $item != '..'){
						$newPath = $pathFrag.'/'.$item;
						if(is_dir($baseDir.$newPath)){
							$path = $this->locateBaseFolder($baseDir, $newPath);
							if($path){
								$retPath = $path;
								break; 
							}
						}
					}
				}
			}
			closedir($handle);
		}
		return $retPath;
	}

	private function readMetaFile(){
		//Read meta.xml file
		if(!$this->metaArr){
			if(file_exists($this->uploadTargetPath.$this->baseFolderName.'/meta.xml')){
				$metaDoc = new DOMDocument();
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
								$this->outputMsg('WARNING: Core ID absent');
							}
							//Get file name
							if($locElements = $coreElement->getElementsByTagName('location')){
								$this->metaArr['occur']['name'] = $locElements->item(0)->nodeValue;
							}
							else{
								$this->outputMsg('ERROR: Unable to obtain the occurrence file name from meta.xml');
								$this->errorStr = 'ERROR: Unable to obtain the occurrence file name from meta.xml';
							}
							//Get the rest of the core attributes
							$this->metaArr['occur']['encoding'] = $coreElement->getAttribute('encoding');
							$this->metaArr['occur']['fieldsTerminatedBy'] = $coreElement->getAttribute('fieldsTerminatedBy');
							$this->metaArr['occur']['linesTerminatedBy'] = $coreElement->getAttribute('linesTerminatedBy');
							$this->metaArr['occur']['fieldsEnclosedBy'] = $coreElement->getAttribute('fieldsEnclosedBy');
							$this->metaArr['occur']['ignoreHeaderLines'] = $coreElement->getAttribute('ignoreHeaderLines');
							$this->metaArr['occur']['rowType'] = $rowType;
							//Get the Core field names
							if($fieldElements = $coreElement->getElementsByTagName('field')){
								foreach($fieldElements as $fieldElement){
									$term = $fieldElement->getAttribute('term');
									if(strpos($term,'/')) $term = substr($term,strrpos($term,'/')+1);
									$this->metaArr['occur']['fields'][$fieldElement->getAttribute('index')] = $term;
								}
							}
							//Set id
							$this->metaArr['occur']['fields'][0] = 'id';
							//Test meta.xml field list against occurrence file
							if($this->metaArr['occur']['ignoreHeaderLines'] == 1){
								//Set delimiter  
								if($this->metaArr['occur']['fieldsTerminatedBy']){
									if($this->metaArr['occur']['fieldsTerminatedBy'] == '\t'){
										$this->delimiter = "\t";
									}
									else{
										$this->delimiter = $this->metaArr['occur']['fieldsTerminatedBy'];
									}
									//Read occurrence header and compare
									$fullPath = $this->uploadTargetPath.$this->baseFolderName.'/'.$this->metaArr['occur']['name'];
			 						$fh = fopen($fullPath,'r') or die("Can't open occurrence file");
									$headerArr = $this->getRecordArr($fh);
									foreach($headerArr as $k => $v){
										if(strtolower($v) != strtolower($this->metaArr['occur']['fields'][$k])){
											$msg = '<div style="margin-left:25px;">';
											$msg .= 'WARNING: meta.xml field order out of sync w/ '.$this->metaArr['occur']['name'].'; remapping: field #'.($k+1).' => '.$v;
											$msg .= '</div>';
											$this->outputMsg($msg);
											$this->errorStr = $msg;
											$this->metaArr['occur']['fields'][$k] = $v;
										}
									}
								}
							}
							if($this->verboseMode == 2){
								$outputStr = 'DWCA details: encoding = '.$this->metaArr['occur']['encoding'].'; ';
								$outputStr .= 'fieldsTerminatedBy: '.$this->metaArr['occur']['fieldsTerminatedBy'].'; ';
								$outputStr .= 'linesTerminatedBy: '.$this->metaArr['occur']['linesTerminatedBy'].'; ';
								$outputStr .= 'fieldsEnclosedBy: '.$this->metaArr['occur']['fieldsEnclosedBy'].'; ';
								$outputStr .= 'ignoreHeaderLines: '.$this->metaArr['occur']['ignoreHeaderLines'].'; ';
								$outputStr .= 'rowType: '.$this->metaArr['occur']['rowType'];
								//$this->outputMsg($outputStr);
							}
						}
					}
				}
				else{
					$this->outputMsg('ERROR: Unable to access core element in meta.xml');
					$this->errorStr = 'ERROR: Unable to access core element in meta.xml';
					return false;
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
								$this->includeIdentificationHistory = true;
							}
							elseif(stripos($rowType,'image')){
								//Is image data related to core data
								$tagName = 'image';
								$this->includeImages = true;
							}
							if($tagName){
								if($locElements = $extensionElement->getElementsByTagName('location')){
									$this->metaArr[$tagName]['name'] = $locElements->item(0)->nodeValue;
								}
								else{
									$this->outputMsg('WARNING: Unable to obtain the '.$tagName.' file name from meta.xml');
								}
								//Get the rest of the core attributes
								$this->metaArr[$tagName]['encoding'] = $extensionElement->getAttribute('encoding');
								$this->metaArr[$tagName]['fieldsTerminatedBy'] = $extensionElement->getAttribute('fieldsTerminatedBy');
								$this->metaArr[$tagName]['linesTerminatedBy'] = $extensionElement->getAttribute('linesTerminatedBy');
								$this->metaArr[$tagName]['fieldsEnclosedBy'] = $extensionElement->getAttribute('fieldsEnclosedBy');
								$this->metaArr[$tagName]['ignoreHeaderLines'] = $extensionElement->getAttribute('ignoreHeaderLines');
								$this->metaArr[$tagName]['rowType'] = $rowType;
								//Get the Core field names
								if($fieldElements = $extensionElement->getElementsByTagName('field')){
									foreach($fieldElements as $fieldElement){
										$term = $fieldElement->getAttribute('term');
										if(strpos($term,'/')) $term = substr($term,strrpos($term,'/')+1);
										$this->metaArr[$tagName]['fields'][$fieldElement->getAttribute('index')] = $term;
									}
								}
								$this->metaArr[$tagName]['fields'][0] = 'coreid';

								//Test meta.xml field list against extension file
								if($this->metaArr[$tagName]['ignoreHeaderLines'] == 1){
									//Set delimiter  
									if($this->metaArr[$tagName]['fieldsTerminatedBy']){
										if($this->metaArr[$tagName]['fieldsTerminatedBy'] == '\t'){
											$this->delimiter = "\t";
										}
										else{
											$this->delimiter = $this->metaArr[$tagName]['fieldsTerminatedBy'];
										}
										//Read extension file header and compare
										$fullPath = $this->uploadTargetPath.$this->baseFolderName.'/'.$this->metaArr[$tagName]['name'];
				 						$fh = fopen($fullPath,'r') or die("Can't open $tagName extension file");
										$headerArr = $this->getRecordArr($fh);
										foreach($headerArr as $k => $v){
											if(strtolower($v) != strtolower($this->metaArr[$tagName]['fields'][$k])){
												$msg = '<div style="margin-left:25px;">';
												$msg .= 'WARNING: meta.xml field order out of sync w/ '.$this->metaArr[$tagName]['name'].'; remapping: field #'.($k+1).' => '.$v;
												$msg .= '</div>';
												$this->outputMsg($msg);
												$this->errorStr = $msg;
												$this->metaArr[$tagName]['fields'][$k] = $v;
											}
										}
									}
								}
							}
						}					
					}				
				}
				else{
					$this->outputMsg('ERROR: Unable to obtain core element from meta.xml');
					$this->errorStr = 'ERROR: Unable to obtain core element from meta.xml';
					return false;
				}
			}
			else{
				$this->outputMsg('ERROR: Malformed DWCA, unable to locate meta.xml');
				$this->errorStr = 'ERROR: Malformed DWCA, unable to locate meta.xml';
				return false;
			}
		}
		return true;
	}

	public function uploadData($finalTransfer){
		global $charset;
		if($this->baseFolderName){
			set_time_limit(7200);
		 	ini_set("max_input_time",240);

			//First, delete all records in uploadspectemp table associated with this collection
			$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
			$this->conn->query($sqlDel);

			if($this->readMetaFile() && isset($this->metaArr['occur']['fields'])){
				if(isset($this->metaArr['occur']['fieldsTerminatedBy']) && $this->metaArr['occur']['fieldsTerminatedBy']){
					if($this->metaArr['occur']['fieldsTerminatedBy'] == '\t'){
						$this->delimiter = "\t";
					}
					else{
						$this->delimiter = $this->metaArr['occur']['fieldsTerminatedBy'];
					}
				}
				else{
					$this->delimiter = '';
				}
				if(isset($this->metaArr['occur']['fieldsEnclosedBy']) && $this->metaArr['occur']['fieldsEnclosedBy']){
					$this->enclosure = $this->metaArr['occur']['fieldsEnclosedBy'];
				}
				if(isset($this->metaArr['occur']['encoding']) && $this->metaArr['occur']['encoding']){
					$this->encoding = strtolower(str_replace('-','',$this->metaArr['occur']['encoding']));
				}

				$fullPath = $this->uploadTargetPath.$this->baseFolderName.'/'.$this->metaArr['occur']['name'];
		 		$fh = fopen($fullPath,'r') or die("Can't open occurrence file");
				
		 		if($this->metaArr['occur']['ignoreHeaderLines'] == '1'){
		 			//Advance one record to go past header
					$this->getRecordArr($fh);
		 		}
				
				$cset = strtolower(str_replace('-','',$charset)); 
				//Set source array 
				$this->sourceArr = array();
				foreach($this->metaArr['occur']['fields'] as $k => $v){
					$this->sourceArr[$k] = strtolower($v);
				}
				//Grab data
				$this->transferCount = 0;
		 		if(!array_key_exists('dbpk',$this->fieldMap)) $this->fieldMap['dbpk']['field'] = 'id';
		 		$collName = $this->collMetadataArr["name"].' ('.$this->collMetadataArr["institutioncode"];
				if($this->collMetadataArr["collectioncode"]) $collName = $this->collMetadataArr["collectioncode"]; 
				$this->outputMsg('Uploading data for: '.$collName);
				while($recordArr = $this->getRecordArr($fh)){
					$recMap = Array();
					foreach($this->fieldMap as $symbField => $sMap){
						if(substr($symbField,0,8) != 'unmapped'){
							$indexArr = array_keys($this->sourceArr,$sMap['field']);
							$index = array_shift($indexArr);
							if(array_key_exists($index,$recordArr)){
								$valueStr = $recordArr[$index];
								if($cset != $this->encoding) $valueStr = $this->encodeString($valueStr);
								$recMap[$symbField] = $valueStr;
							}
						}
					}
					$this->loadRecord($recMap);
					unset($recMap);
				}
				fclose($fh);

				//Do some cleanup
				$this->cleanUpload();
				
				//Upload identification history
				if($this->includeIdentificationHistory){
					$fullPathIdent = '';
					if($this->metaArr['ident']['name']) $fullPathIdent = $this->uploadTargetPath.$this->baseFolderName.'/'.$this->metaArr['ident']['name'];
					if($fullPathIdent && file_exists($fullPathIdent)){
						if(isset($this->metaArr['ident']['fields'])){
							$this->outputMsg('<li style="font-weight:bold;">Starting to upload identification history records</li>');
							if(isset($this->metaArr['ident']['fieldsTerminatedBy']) && $this->metaArr['ident']['fieldsTerminatedBy']){
								if($this->metaArr['ident']['fieldsTerminatedBy'] == '\t'){
									$this->delimiter = "\t";
								}
								else{
									$this->delimiter = $this->metaArr['ident']['fieldsTerminatedBy'];
								}
							}
							else{
								$this->delimiter = '';
							}
							if(isset($this->metaArr['ident']['fieldsEnclosedBy']) && $this->metaArr['ident']['fieldsEnclosedBy']){
								$this->enclosure = $this->metaArr['ident']['fieldsEnclosedBy'];
							}
							if(isset($this->metaArr['ident']['encoding']) && $this->metaArr['ident']['encoding']){
								$this->encoding = strtolower(str_replace('-','',$this->metaArr['ident']['encoding']));
							}
	
					 		$fh = fopen($fullPathIdent,'r') or die("Can't open identification history file");
							
					 		if($this->metaArr['ident']['ignoreHeaderLines'] == '1'){
					 			//Advance one record to go past header
					 			$this->getRecordArr($fh);
					 		}
							
							//Grab data
							$cset = strtolower(str_replace('-','',$charset)); 
							//Set identification source array
							$this->identSourceArr = array();
							foreach($this->metaArr['ident']['fields'] as $k => $v){
								$this->identSourceArr[$k] = strtolower($v);
							}
							while($recordArr = $this->getRecordArr($fh)){
								$recMap = Array();
								foreach($this->identFieldMap as $symbField => $iMap){
									if(substr($symbField,0,8) != 'unmapped'){
										$indexArr = array_keys($this->identSourceArr,$iMap['field']);
										$index = array_shift($indexArr);
										if(array_key_exists($index,$recordArr)){
											$valueStr = $recordArr[$index];
											if($cset != $this->encoding) $valueStr = $this->encodeString($valueStr);
											$recMap[$symbField] = $valueStr;
										}
									}
								}
								$this->loadIdentificationRecord($recMap);
								unset($recMap);
							}
							fclose($fh);
							
							$this->outputMsg('<li style="font-weight:bold;">Identification history upload complete ('.$this->identTransferCount.' records)!</li>');
						}
						else{
							$errMsg = 'ERROR: field not defined within identification history file ('.$fullPathIdent.')';
							$this->outputMsg($errMsg);
							$this->errorStr = $errMsg;
						}
					}
					else{
						$errMsg = 'ERROR: unable to locate identification history file within archive ';
						$this->outputMsg($errMsg);
						$this->errorStr = $errMsg;
					}
				}
				
				//Upload images
				if($this->includeImages){
					
					

				}

				//Records transferred, thus finalize
				$this->finalizeUpload();
				if($finalTransfer){
					$this->finalTransfer();
				}
				
				//Delete upload file 
				if(file_exists($this->uploadTargetPath.$this->baseFolderName)){
					unlink($this->uploadTargetPath.$this->baseFolderName);
				}
			}
		}
		else{
			$this->outputMsg("<li>ERROR: unable to locate occurrence upload file</li>");
			$this->errorStr = 'ERROR: unable to locate occurrence upload file';
			return false;
		}
		return true;
	}

	private function getRecordArr($fHandler){
		$recordArr = Array();
		if($this->delimiter){
			$recordArr = fgetcsv($fHandler,0,$this->delimiter,$this->enclosure);
		}
		else{
			//Check to see if we can figure out the delimiter
			$record = fgets($fHandler);
			if(substr($this->metaArr['occur']['name'],-4) == ".csv"){
				$this->delimiter = ',';
			}
			elseif(strpos($record,"\t") !== false){
				$this->delimiter = "\t";
			}
			elseif(strpos($record,"|") !== false){
				$this->delimiter = "|";
			}
			else{
				$this->delimiter = ',';
			}
			//Get data 
			$recordArr = explode($this->delimiter,$record);
			//Remove enclosures
			if($this->enclosure){
				foreach($recordArr as $k => $v){
					if(substr($v,0,1) == $this->enclosure && substr($v,-1) == $this->enclosure){
						$recordArr[$k] = substr($v,1,strlen($v)-2);
					}
				}
			}
		}
		
		return $recordArr;
	}
	
	public function setBaseFolderName($name){
		$this->baseFolderName = $name;
	}

	public function getDbpk(){
		$dbpk = parent::getDbpk();
		if(!$dbpk) $dbpk = 'id';
		return $dbpk;
	}

	public function getMetaArr(){
		return $this->metaArr;
	}
}
?>