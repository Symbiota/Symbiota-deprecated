<?php
require_once($SERVER_ROOT.'/config/dbconnection.php');
require_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');

class ImageProcessor {

	private $conn;

	private $collid = 0;
	private $sprid;
	private $collArr;
	private $matchCatalogNumber = true;
	private $matchOtherCatalogNumbers = false;

	private $logMode = 0;		//0 = silent, 1 = html, 2 = log file, 3 = both html & log
	private $logFH;
	private $destructConn = true;

	function __construct($con = null){
		if($con){
			//Inherits connection from another class
			$this->conn = $con;
			$this->destructConn = false;
		}
		else{
			$this->conn = MySQLiConnectionFactory::getCon('write');
			if($this->conn === false) exit("ABORT: Image upload aborted: Unable to establish connection to database");
		}
	}

	function __destruct(){
		//Close connection
		if($this->destructConn && !($this->conn === false)) $this->conn->close();

		//Close log file
		if($this->logFH) fclose($this->logFH);
	}

	private function initProcessor($processorType){
		//Close log file
		if($this->logFH) fclose($this->logFH);
		if($this->logMode > 1){
			//Create log File
			$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) == '/'?'':'/').'content/logs/';
			if($processorType) $logPath .= $processorType.'/';
			if(!file_exists($logPath)) mkdir($logPath);
			if(file_exists($logPath)){
				$logFile = $logPath.$this->collid.'_'.$this->collArr['instcode'];
				if($this->collArr['collcode']) $logFile .= '-'.$this->collArr['collcode'];
				$logFile .= '_'.date('Y-m-d').'.log';
				$this->logFH = fopen($logFile, 'a');
			}
			else{
				echo 'ERROR creating Log file; path not found: '.$logPath."\n";
			}
		}
	}

	public function batchProcessIPlantImages(){
		//Start processing images for each day from the start date to the current date
		$status = false;
		if($this->logMode == 1) echo '<ul>';
		$processList = array();
		$sql = 'SELECT collid, speckeypattern, source FROM specprocessorprojects WHERE (title = "IPlant Image Processing") ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->collid = $r->collid;
			$this->setLogMode(2);
			$status = $this->processIPlantImages();
			if($status){
				$processList[] = $this->collid;
			}
		}
		$rs->free();
		if($status) $this->cleanHouse($processList);
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		if($this->logMode == 1) echo '</ul>';
	}

	//iPlant functions
	public function processIPlantImages($pmTerm, $postArr){
		set_time_limit(1000);
		$lastRunDate = $postArr['startdate'];
		$iPlantSourcePath = (array_key_exists('sourcepath', $postArr)?$postArr['sourcepath']:'');
		$this->matchCatalogNumber = (array_key_exists('matchcatalognumber', $postArr)?true:false);
		$this->matchOtherCatalogNumbers = (array_key_exists('matchothercatalognumbers', $postArr)?true:false);

		if($this->collid){
			$iPlantDataUrl = 'https://bisque.cyverse.org/data_service/';
			$iPlantImageUrl = 'https://bisque.cyverse.org/image_service/image/';
			if(!$iPlantSourcePath && array_key_exists('IPLANT_IMAGE_IMPORT_PATH', $GLOBALS)) $iPlantSourcePath = $GLOBALS['IPLANT_IMAGE_IMPORT_PATH'];
			if($iPlantSourcePath){
				if(strpos($iPlantSourcePath, '--INSTITUTION_CODE--')) $iPlantSourcePath = str_replace('--INSTITUTION_CODE--', $this->collArr['instcode'], $iPlantSourcePath);
				if(strpos($iPlantSourcePath, '--COLLECTION_CODE--')) $iPlantSourcePath = str_replace('--COLLECTION_CODE--', $this->collArr['collcode'], $iPlantSourcePath);
			}
			else{
				echo '<div style="color:red">iPlant image import path (IPLANT_IMAGE_IMPORT_PATH) not set within symbini configuration file</div>';
				return false;
			}
			$this->initProcessor('iplant');
			$collStr = $this->collArr['instcode'].($this->collArr['collcode']?'-'.$this->collArr['collcode']:'');
			$this->logOrEcho('Starting image processing: '.$collStr.' ('.date('Y-m-d h:i:s A').')');

			if(!$pmTerm){
				$this->logOrEcho('COLLECTION SKIPPED: Pattern matching term is NULL');
				return false;
			}
			if(substr($pmTerm,0,1) != '/' || substr($pmTerm,-1) != '/'){
				$this->logOrEcho("COLLECTION SKIPPED: Regular Expression term illegal due to missing forward slashes: ".$pmTerm);
				return false;
			}
			if(!strpos($pmTerm,'(') || !strpos($pmTerm,')')){
				$this->logOrEcho("COLLECTION SKIPPED: Regular Expression term illegal due to missing capture term: ".$pmTerm);
				return false;
			}
			//Get start date
			if(!$lastRunDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$lastRunDate)) $lastRunDate = '2015-04-01';
			while(strtotime($lastRunDate) < strtotime('now')){
				$url = $iPlantDataUrl.'image?value=*'.$iPlantSourcePath.'*&tag_query=upload_datetime:'.$lastRunDate.'*';
				$contents = @file_get_contents($url);
				//check if response is received from iPlant
				if(!empty($http_response_header)) {
					$result = $http_response_header;
					//check if response is 200
					if(strpos($result[0],'200') !== false) {
						$xml = '';
						try {
							$xml = new SimpleXMLElement($contents);
						}
						catch (Exception $e) {
							$this->logOrEcho('ABORTED: bad content received from iPlant: '.$contents);
							return false;
						}
						if(count($xml->image)){
							$this->logOrEcho('Starting to process '.count($xml->image).' images uploaded on '.$lastRunDate,1);
							$cnt = 0;
							foreach($xml->image as $i){
								$fileName = $i['name'];
								if(preg_match($pmTerm,$fileName,$matchArr)){
									if(array_key_exists(1,$matchArr) && $matchArr[1]){
										$specPk = $matchArr[1];
										if($postArr['patternreplace']) $specPk = preg_replace($postArr['patternreplace'],$postArr['replacestr'],$specPk);
										$guid = $i['resource_uniq'];
										if($occid = $this->getOccid($specPk,$guid,$fileName)){
											$baseUrl = $iPlantImageUrl.$guid;
											$webUrl = $baseUrl.'/resize:1250/format:jpeg';
											$tnUrl = $baseUrl.'/thumbnail:200,200';
											$lgUrl = $baseUrl.'/resize:4000/format:jpeg';
											//$webUrl = $baseUrl.'?resize=1250&format=jpeg';
											//$tnUrl = $baseUrl.'?thumbnail=200,200';
											//$lgUrl = $baseUrl.'?resize=4000&format=jpeg';

											$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$baseUrl,$this->collArr['collname'],$guid.'; filename: '.$fileName);
											//$this->logOrEcho("Image processed successfully (".date('Y-m-d h:i:s A').")!",2);
										}
									}
									else{
										$this->logOrEcho("NOTICE: File skipped, unable to extract specimen identifier (".$sourcePathFrag.$fileName.")",2);
									}
								}
								$cnt++;
							}
						}
						else{
							$this->logOrEcho('No images were loaded on this date: '.$lastRunDate,1);
							}
					}
					else{
						$this->logOrEcho("ERROR: bad response status code returned for $url (code: $result[0])",1);
					}
				}
				else{
					$this->logOrEcho("ERROR: failed to obtain response from iPlant (".$url.")",1);
					return false;
				}
				$this->updateLastRunDate($lastRunDate);
				$lastRunDate = date('Y-m-d', strtotime($lastRunDate. ' + 1 days'));
			}
			$this->cleanHouse(array($this->collid));
			$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		}
		return true;
	}

	//iDigBio Image ingestion processing functions
	public function processiDigBioOutput($pmTerm,$postArr){
		$status = '';
		$this->matchCatalogNumber = (array_key_exists('matchcatalognumber', $postArr)?1:0);
		$this->matchOtherCatalogNumbers = (array_key_exists('matchothercatalognumbers', $postArr)?1:0);
		$idigbioImageUrl = 'https://api.idigbio.org/v2/media/';
		$this->initProcessor('idigbio');
		$collStr = $this->collArr['instcode'].($this->collArr['collcode']?'-'.$this->collArr['collcode']:'');
		$this->logOrEcho('Starting image processing for '.$collStr.' ('.date('Y-m-d h:i:s A').')');
		if($pmTerm){
			$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/idigbio_'.time().'.csv';
			if(move_uploaded_file($_FILES['idigbiofile']['tmp_name'],$fullPath)){
				if($fh = fopen($fullPath,'rb')){
					$headerArr = fgetcsv($fh,0,',');
					$origFileNameIndex = (in_array('OriginalFileName',$headerArr)?array_search('OriginalFileName',$headerArr):(in_array('idigbio:OriginalFileName',$headerArr)?array_search('idigbio:OriginalFileName',$headerArr):''));
					$mediaMd5Index = (in_array('MediaMD5',$headerArr)?array_search('MediaMD5',$headerArr):(in_array('ac:hashValue',$headerArr)?array_search('ac:hashValue',$headerArr):''));
					if(is_numeric($origFileNameIndex) && is_numeric($mediaMd5Index)){
						while(($data = fgetcsv($fh,1000,",")) !== FALSE){
							if($data[$mediaMd5Index]){
								$origFileName = basename($data[$origFileNameIndex]);
								//basename() function is system specific, thus following code needed to parse filename independent of source file from PC, Mac, etc
								if(strpos($origFileName,'/') !== false){
									$origFileName = substr($origFileName,(strrpos($origFileName,'/')+1));
								}
								elseif(strpos($origFileName,'\\') !== false){
									$origFileName = substr($origFileName,(strrpos($origFileName,'\\')+1));
								}
								if(preg_match($pmTerm,$origFileName,$matchArr)){
									if(array_key_exists(1,$matchArr) && $matchArr[1]){
										$specPk = $matchArr[1];
										if($postArr['patternreplace']) $specPk = preg_replace($postArr['patternreplace'],$postArr['replacestr'],$specPk);
										$occid = $this->getOccid($specPk,$origFileName);
										if($occid){
											//Image hasn't been loaded, thus insert image urls into image table
											$baseUrl = $idigbioImageUrl.$data[$mediaMd5Index];
											$webUrl = $baseUrl.'?size=webview';
											$tnUrl = $baseUrl.'?size=thumbnail';
											$lgUrl = $baseUrl.'?size=fullsize';
											$this->databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$baseUrl,$this->collArr['collname'],$origFileName);
										}
									}
								}
								else{
									$this->logOrEcho('NOTICE: File skipped, unable to extract specimen identifier ('.$origFileName.', pmTerm: '.$pmTerm.')',2);
								}
							}
							else{
								$errMsg = $data[array_search('idigbio:mediaStatusDetail',$headerArr)];
								$this->logOrEcho('NOTICE: File skipped due to apparent iDigBio upload failure (iDigBio Error:'.$errMsg.') ',2);
							}
						}
						$this->cleanHouse(array($this->collid));
						$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').")");
					}
					else{
						//Output to error log file
						$this->logOrEcho('Bad input fields: '.$origFileNameIndex.', '.$mediaMd5Index,2);
					}
					fclose($fh);
				}
				else{
					$this->logOrEcho('Cannot open input file',2);
				}
				unlink($fullPath);
			}
		}
		else{
			$this->logOrEcho('ERROR: Pattern matching term has not been defined ',2);
		}
		return $status;
	}

	public function initiateFileUpload(){
		$this->initProcessor('imageFile');
		$collStr = $this->collArr['instcode'].($this->collArr['collcode']?'-'.$this->collArr['collcode']:'');
		$this->logOrEcho('Starting image processing for '.$collStr.' ('.date('Y-m-d h:i:s A').')');
		if($pmTerm){
			$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/idigbio_'.time().'.csv';
			if(move_uploaded_file($_FILES['idigbiofile']['tmp_name'],$fullPath)){
				if($fh = fopen($fullPath,'rb')){
					$headerArr = fgetcsv($fh,0,',');
				}
			}
		}
	}

	//Image file upload
	public function loadImageFile(){
		$inFileName = basename($_FILES['uploadfile']['name']);
		$ext = substr(strrchr($inFileName, '.'), 1);
		$fileName = 'imageMappingFile_'.time();
		$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/';
		if(move_uploaded_file($_FILES['uploadfile']['tmp_name'],$fullPath.$fileName.'.'.$ext)){
			if($ext == 'zip'){
				$zipFilePath = $fullPath.$fileName.'.zip';
				$ext = '';
				$zip = new ZipArchive;
				$res = $zip->open($zipFilePath);
				if($res === TRUE) {
					for($i = 0; $i < $zip->numFiles; $i++){
						$fileExt = substr(strrchr($zip->getNameIndex($i), '.'), 1);
						if($fileExt == 'csv' || $fileExt == 'txt'){
							$ext = $fileExt;
							$zip->renameIndex($i, $fileName.'.'.$ext);
							$zip->extractTo($fullPath,$fileName.'.'.$ext);
							$zip->close();
							unlink($zipFilePath);
							break;
						}
					}
				}
				else{
					echo 'failed, code:' . $res;
					return false;
				}
			}
			return $fileName.'.'.$ext;
		}
		return '';
	}

	public function echoFileMapping($fileName){
		$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/'.$fileName;
		if($fh = fopen($fullPath,'rb')){
			$translationMap = array('catalognumber' => 'catalognumber', 'url' => 'url', 'thumbnailurl' => 'thumbnailurl',
				'originalurl' => 'originalurl', 'thumbnail' => 'thumbnailurl', 'large' => 'originalurl', 'web' => 'url');
			$headerArr = fgetcsv($fh,0,',');
			foreach($headerArr as $i => $sourceField){
				if($sourceField != 'collid'){
					echo '<tr><td style="padding:2px;">';
					echo $sourceField;
					$sourceField = strtolower($sourceField);
					echo '<input type="hidden" name="sf['.$i.']" value="'.$sourceField.'" />';
					echo '</td><td>';
					echo '<select name="tf['.$i.']" style="background:'.(!array_key_exists($sourceField,$translationMap)?'yellow':'').'">';
					echo '<option value="">Select Target Field</option>';
					echo '<option value="">-------------------------</option>';
					echo '<option value="catalognumber" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='catalognumber'?'SELECTED':'').'>Catalog Number (required)</option>';
					echo '<option value="originalurl" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='originalurl'?'SELECTED':'').'>Large Image URL (required)</option>';
					echo '<option value="url" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='url'?'SELECTED':'').'>Web Image URL</option>';
					echo '<option value="thumbnailurl" '.(isset($translationMap[$sourceField]) && $translationMap[$sourceField]=='thumbnailurl'?'SELECTED':'').'>Thumbnail URL</option>';
					echo '</select>';
					echo '</td></tr>';
				}
			}
		}
	}

	public function loadFileData($postArr){
		if(isset($postArr['filename']) && isset($postArr['tf'])){
			//Get field map
			$fieldMap = array_flip($postArr['tf']);
			//Load data
			$fullPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) != '/'?'/':'').'temp/data/'.$postArr['filename'];
			if($fh = fopen($fullPath,'rb')){
				$headerArr = fgetcsv($fh);
				while($recordArr = fgetcsv($fh)){
					$catalogNumber = (isset($fieldMap['catalognumber'])?$this->cleanInStr($recordArr[$fieldMap['catalognumber']]):'');
					$originalUrl = (isset($fieldMap['originalurl'])?$this->cleanInStr($recordArr[$fieldMap['originalurl']]):'');
					$url = (isset($fieldMap['url'])?$this->cleanInStr($recordArr[$fieldMap['url']]):'');
					if(!$url) $url = 'empty';
					$thumbnailUrl = (isset($fieldMap['thumbnailurl'])?$this->cleanInStr($recordArr[$fieldMap['thumbnailurl']]):'');
					if($catalogNumber && $originalUrl){
						echo '<li>Processing catalogNumber: '.$catalogNumber.'</li>';
						//Get catalogNumber
						$occArr = array();
						$sql = 'SELECT occid FROM omoccurrences WHERE collid = '.$this->collid.' AND catalognumber = "'.$catalogNumber.'"';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							$occArr[] = $r->occid;
						}
						$rs->free();
						if($occArr){
							//Check to see if image with matching filename is already linked. If so, remove and replace with new
							$origFileName = substr(strrchr($originalUrl, "/"), 1);
							$urlFileName = substr(strrchr($url, "/"), 1);
							foreach($occArr as $k => $occid){
								$sql1 = 'SELECT imgid, url, originalurl, thumbnailurl FROM images WHERE (occid = '.$occid.')';
								$rs1 = $this->conn->query($sql1);
								while($r1 = $rs1->fetch_object()){
									$uFileName = substr(strrchr($r1->url, "/"), 1);
									$oFileName = substr(strrchr($r1->originalurl, "/"), 1);
									if($oFileName == $origFileName || $uFileName == $urlFileName || $oFileName == $urlFileName || $uFileName == $origFileName){
										$sql2 = 'UPDATE images '.
											'SET url = "'.$url.'", originalurl = "'.$originalUrl.'", thumbnailurl = '.($thumbnailUrl?'"'.$thumbnailUrl.'"':'NULL').' '.
											'WHERE imgid = '.$r1->imgid;
										if($this->conn->query($sql2)){
											echo '<li style="margin-left:10px">Existing image replaced with new image mapping: <a href="../editor/occurrenceeditor.php?occid='.$occid.'" target="_blank">'.$catalogNumber.'</a></li>';
											//Delete physical images it previous version was mapped locally
											$this->deleteImage($r1->url);
											$this->deleteImage($r1->originalurl);
											$this->deleteImage($r1->thumbnailurl);
											unset($occArr[$k]);
											break;
										}
										else{
											echo '<li style="margin-left:10px">ERROR updating existing image record: '.$this->conn->error.'</li>';
										}
									}
								}
								$rs1->free();
							}
						}
						else{
							//Create new occurrence record to link image
							$sqlIns = 'INSERT INTO omoccurrences(collid,catalognumber,processingstatus,dateentered) '.
								'VALUES('.$this->collid.',"'.$catalogNumber.'","unprocessed",now())';
							if($this->conn->query($sqlIns)){
								$occArr[] = $this->conn->insert_id;
								echo '<li style="margin-left:10px">Unable to find record with matching catalogNumber; new occurrence record created</li>';
							}
							else{
								echo '<li style="margin-left:10px">ERROR creating new occurrence record: '.$this->conn->error.'</li>';
							}
						}
						foreach($occArr as $occid){
							//Load image URLs
							$sqlInsert = 'INSERT INTO images(occid,url,originalurl,thumbnailurl) '.
								'VALUES('.$occid.',"'.$url.'","'.$originalUrl.'",'.($thumbnailUrl?'"'.$thumbnailUrl.'"':'NULL').')';
							if($this->conn->query($sqlInsert)){
								echo '<li style="margin-left:10px">Image URLs linked to: <a href="../editor/occurrenceeditor.php?occid='.$occid.'" target="_blank">'.$catalogNumber.'</a></li>';
							}
							else{
								echo '<li style="margin-left:10px">ERROR loading image: '.$this->conn->error.'</li>';
							}
						}
					}
				}
			}
			fclose($fh);
			unlink($fullPath);
		}
	}

	private function deleteImage($imgUrl){
		if(stripos($imgUrl, 'http') === 0 || stripos($imgUrl, 'https') === 0){
			$imgUrl = parse_url($imgUrl, PHP_URL_PATH);
		}
		if($GLOBALS['IMAGE_ROOT_URL'] && strpos($imgUrl,$GLOBALS['IMAGE_ROOT_URL']) === 0){
			$imgPath = $GLOBALS['IMAGE_ROOT_PATH'].substr($imgUrl,strlen($GLOBALS['IMAGE_ROOT_URL']));
			unlink($imgPath);
		}
	}

	//Shared functions
	private function getOccid($specPk,$sourceIdentifier,$fileName = ''){
		$occid = 0;
		if($this->collid){
			//Check to see if record with pk already exists
			if($this->matchCatalogNumber){
				$sql = 'SELECT occid FROM omoccurrences WHERE (collid = '.$this->collid.') '.
					'AND (catalognumber IN("'.$specPk.'"'.(substr($specPk,0,1)=='0'?',"'.ltrim($specPk,'0 ').'"':'').')) ';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$occid = $row->occid;
				}
				$rs->free();
			}
			if(!$occid && $this->matchOtherCatalogNumbers){
				$sql = 'SELECT occid FROM omoccurrences WHERE (collid = '.$this->collid.') '.
					'AND (othercatalognumbers IN("'.$specPk.'"'.(substr($specPk,0,1)=='0'?',"'.ltrim($specPk,'0 ').'"':'').')) ';
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$occid = $row->occid;
				}
				$rs->free();
			}
			if($occid){
				$occLink = '<a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>';
				if($fileName){
					//Is iPlant mapped image
					//Check to see if image has already been linked
					$fileBaseName = $fileName;
					$fileExt = '';
					$dotPos = strrpos($fileName,'.');
					if($dotPos){
						$fileBaseName = substr($fileName,0,$dotPos);
						$fileExt = strtolower(substr($fileName,$dotPos+1));
					}
					//Grab existing images for that occurrence
					$imgArr = array();
					$sqlTest = 'SELECT imgid, sourceidentifier FROM images WHERE (occid = '.$occid.') ';
					$rsTest = $this->conn->query($sqlTest);
					while($rTest = $rsTest->fetch_object()){
						$imgArr[$rTest->imgid] = $rTest->sourceidentifier;
					}
					$rsTest->free();
					//Process images to determine if new images should be added
					$highResList = array('cr2','dng','tiff','tif','nef');
					foreach($imgArr as $imgId => $sourceId){
						if($sourceId){
							if(preg_match('/^([A-Za-z0-9\-]+);\sfilename:\s(.+)$/',$sourceId,$m)){
								$guid = $m[1];
								$fn = $m[2];
								$fnArr = explode('.',$fn);
								$fnExt = strtolower(array_pop($fnArr));
								$fnBase = implode($fnArr);
								if($guid == $sourceIdentifier){
									//Image file already loaded (based on identifier, thus abort and don't reload
									$occid = false;
									$this->logOrEcho('NOTICE: Image mapping skipped; image identifier ('.$sourceIdentifier.') already in system (#'.$occLink.')',2);
									break;
								}
								elseif($fn == $fileName){
									//Image file already loaded, thus abort and don't reload
									$occid = false;
									$this->logOrEcho('NOTICE: Image mapping skipped; file ('.$fileName.') already in system (#'.$occLink.')',2);
									break;
								}
								elseif($fileBaseName  == $fnBase && $fnExt == 'jpg'){
									//JPG already mapped for this image, thus abort and don't reload
									$occid = false;
									//$this->logOrEcho('NOTICE: Image mapping skipped; high-res image with same name already in system ('.$fileName.'; '.$occLink.')',2);
									break;
								}
								elseif($fileExt == 'jpg' && in_array($fnExt,$highResList)){
									//$this->logOrEcho('NOTICE: Replacing exist map of high-res with this JPG version ('.$fileName.'; #'.$occLink.')',2);
									//Replace high res source with JPG by deleteing high res from database
									$this->conn->query('DELETE FROM images WHERE imgid = '.$imgId);
								}
							}
						}
					}
				}
				else{
					if($sourceIdentifier){
						//Check to see if image was previous loaded into system, if so remove
						$sql = 'DELETE i.* FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
							'WHERE (o.occid = '.$occid.') AND (i.originalurl LIKE "http%://api.idigbio.org%") AND (i.sourceIdentifier = "'.$sourceIdentifier.'")';
						$this->conn->query($sql);
						$this->logOrEcho('Replacing previously mapped image with new input',2);
					}
				}
				if($occid) $this->logOrEcho('Linked image to existing record ('.($fileName?$fileName.'; ':'').'#'.$occLink.') ',2);
			}
			else{
				//Records does not exist, create a new one to which image will be linked
				$sql2 = 'INSERT INTO omoccurrences(collid,'.($this->matchCatalogNumber?'catalognumber':'othercatalognumbers').',processingstatus,dateentered) '.
					'VALUES('.$this->collid.',"'.$specPk.'","unprocessed","'.date('Y-m-d H:i:s').'")';
				if($this->conn->query($sql2)){
					$occid = $this->conn->insert_id;
					$this->logOrEcho('Linked image to new "unprocessed" specimen record (#<a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>) ',2);
				}
				else{
					$this->logOrEcho("ERROR creating new occurrence record: ".$this->conn->error,2);
				}
			}
		}
		return $occid;
	}

	private function databaseImage($occid,$webUrl,$tnUrl,$lgUrl,$archiveUrl,$ownerStr,$sourceIdentifier){
		$status = true;
		if($occid){
			//All idigbio and iPlant iamge returns are JPG, even if input is different
			$format = 'image/jpeg';
			/*
			$testUrl = $lgUrl;
			if(!$testUrl) $testUrl = $webUrl;
			$imgInfo = getimagesize(str_replace(' ', '%20', $testUrl));
			if($imgInfo){
				if($imgInfo[2] == IMAGETYPE_GIF){
					$format = 'image/gif';
				}
				elseif($imgInfo[2] == IMAGETYPE_PNG){
					$format = 'image/png';
				}
				elseif($imgInfo[2] == IMAGETYPE_JPEG){
					$format = 'image/jpeg';
				}
			}
			*/

			//$this->logOrEcho("Preparing to load record into database",2);
			$sql = 'INSERT INTO images(occid,url,thumbnailurl,originalurl,archiveurl,owner,sourceIdentifier,format) '.
				'VALUES ('.$occid.',"'.$webUrl.'",'.($tnUrl?'"'.$tnUrl.'"':'NULL').','.($lgUrl?'"'.$lgUrl.'"':'NULL').','.
				($archiveUrl?'"'.$archiveUrl.'"':'NULL').','.($ownerStr?'"'.$this->cleanInStr($ownerStr).'"':'NULL').','.
				($sourceIdentifier?'"'.$this->cleanInStr($sourceIdentifier).'"':'NULL').',"'.$format.'")';
			if($this->conn->query($sql)){
				//$this->logOrEcho('Image loaded into database (<a href="../individual/index.php?occid='.$occid.'" target="_blank">#'.$occid.($sourceIdentifier?'</a>: '.$sourceIdentifier:'').')',2);
			}
			else{
				$status = false;
				$this->logOrEcho("ERROR: Unable to load image record into database: ".$this->conn->error,3);
				//$this->logOrEcho($sql);
			}
		}
		else{
			$status = false;
			$this->logOrEcho("ERROR: Missing occid (omoccurrences PK), unable to load record ",2);
		}
		return $status;
	}

	private function cleanHouse($collList){
		$this->logOrEcho('Updating collection statistics...',1);
		$occurMain = new OccurrenceMaintenance($this->conn);

		/*
		$this->logOrEcho('General cleaning...',2);
		$collString = implode(',',$collList);
		if(!$occurMain->generalOccurrenceCleaning($collString)){
			$errorArr = $occurMain->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->logOrEcho($errorStr,1);
			}
		}
		$this->logOrEcho('Protecting sensitive species...',2);
		if(!$occurMain->protectRareSpecies()){
			$errorArr = $occurMain->getErrorArr();
			foreach($errorArr as $errorStr){
				$this->logOrEcho($errorStr,1);
			}
		}
		*/
		if($collList){
			$this->logOrEcho('Updating collection statistics...',2);
			foreach($collList as $collid){
				if(!$occurMain->updateCollectionStats($collid)){
					$errorArr = $occurMain->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}
			}
		}
		$occurMain->__destruct();

		$this->logOrEcho('Populating global unique identifiers (GUIDs) for all records...',2);
		$uuidManager = new UuidFactory($this->conn);
		$uuidManager->setSilent(1);
		$uuidManager->populateGuids();
		$uuidManager->__destruct();
	}

	private function updateLastRunDate($date){
		if($this->spprid){
			$sql = 'UPDATE specprocessorprojects SET source = "'.$date.'" WHERE spprid = '.$this->spprid;
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR updating last run date: '.$this->conn->error);
			}
		}
	}

	//Set and Get functions
	private function setCollArr(){
		if($this->collid){
			$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype '.
				'FROM omcollections '.
				'WHERE (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collArr['instcode'] = $r->institutioncode;
				$this->collArr['collcode'] = $r->collectioncode;
				$this->collArr['collname'] = $r->collectionname;
				$this->collArr['managementtype'] = $r->managementtype;
			}
			$rs->free();
		}
	}

	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
			$this->setCollArr();
		}
	}

	public function setSpprid($spprid){
		if(is_numeric($spprid)){
			$this->spprid = $spprid;
		}
	}

	public function setMatchCatalogNumber($b){
		if($b) $this->matchCatalogNumber = true;
		else $this->matchCatalogNumber = false;
	}

	public function setMatchOtherCatalogNumbers($b){
		if($b) $this->matchOtherCatalogNumbers = true;
		else $this->matchOtherCatalogNumbers = false;
	}

	public function setLogMode($c){
		$this->logMode = $c;
	}

	public function getLogMode(){
		return $this->logMode;
	}

	//Misc functions
	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}

	private function logOrEcho($str,$indent = 0){
		if($this->logMode > 1){
			if($this->logFH){
				if($indent) $str = "\t".$str;
				fwrite($this->logFH,strip_tags($str)."\n");
			}
		}
		if($this->logMode == 1 || $this->logMode == 3){
			echo '<li '.($indent?'style="margin-left:'.($indent*15).'px"':'').'>'.$str."</li>\n";
			ob_flush();
			flush();
		}
	}
}
?>