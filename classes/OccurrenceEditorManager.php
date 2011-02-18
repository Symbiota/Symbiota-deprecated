<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceEditorManager {

	private $conn;
	private $occId;
	private $occurrenceMap = Array();

	private $photographerArr = Array();
	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 2000;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	
	public function __construct($id){
		if($id){
			$this->occId = $id;
		}
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function getOccId(){
		return $this->occId;
	}

	public function setOccId($id){
		$this->occId = $id;
	}

	public function getOccurArr(){
		$sql = 'SELECT c.CollectionName, o.occid, o.collid, o.dbpk, o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, '.
			'o.ownerInstitutionCode, o.family, o.scientificName, o.sciname, o.tidinterpreted, o.genus, o.institutionID, o.collectionID, '.
			'o.specificEpithet, o.taxonRank, o.infraspecificEpithet, '.
			'IFNULL(o.institutionCode,c.institutionCode) AS institutionCode, IFNULL(o.collectionCode,c.collectionCode) AS collectionCode, '.
			'o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, '.
			'o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, o.CollectorFamilyName, '.
			'o.CollectorInitials, o.associatedCollectors, o.eventdate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, '.
			'o.verbatimEventDate, o.habitat, o.occurrenceRemarks, o.associatedTaxa, '.
			'o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, '.
			'o.stateProvince, o.county, o.locality, o.localitySecurity, o.decimalLatitude, o.decimalLongitude, '.
			'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, '.
			'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, '.
			'o.georeferenceVerificationStatus, o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, '.
			'o.verbatimElevation, o.disposition, o.modified, o.language, o.observeruid, o.dateLastModified '.
			'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
			'WHERE o.occid = '.$this->occId;
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			foreach($row as $k => $v){
				$this->occurrenceMap[strtolower($k)] = $v;
			}
		}
		$rs->close();

		$this->setImages();
		$this->setDeterminations();

		return $this->occurrenceMap;
	}

	private function setImages(){
		$sql = "SELECT imgid, url, thumbnailurl, originalurl, caption, photographeruid, sourceurl, copyright, notes, sortsequence ".
			"FROM images ".
			"WHERE occid = ".$this->occId." ORDER BY sortsequence";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$this->occurrenceMap["images"][$imgId]["url"] = $row->url;
			$this->occurrenceMap["images"][$imgId]["tnurl"] = $row->thumbnailurl;
			$this->occurrenceMap["images"][$imgId]["origurl"] = $row->originalurl;
			$this->occurrenceMap["images"][$imgId]["caption"] = $row->caption;
			$this->occurrenceMap["images"][$imgId]["photographeruid"] = $row->photographeruid;
			$this->occurrenceMap["images"][$imgId]["sourceurl"] = $row->sourceurl;
			$this->occurrenceMap["images"][$imgId]["copyright"] = $row->copyright;
			$this->occurrenceMap["images"][$imgId]["notes"] = $row->notes;
			$this->occurrenceMap["images"][$imgId]["sortseq"] = $row->sortsequence;
		}
		$result->close();
	}

	private function setDeterminations(){
		$sql = "SELECT detid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, ".
			"identificationQualifier, identificationReferences, identificationRemarks, sortsequence ".
			"FROM omoccurdeterminations ".
			"WHERE occid = ".$this->occId." ORDER BY sortsequence";
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$detId = $row->detid;
			$this->occurrenceMap["dets"][$detId]["identifiedby"] = $row->identifiedBy;
			$this->occurrenceMap["dets"][$detId]["dateidentified"] = $row->dateIdentified;
			$this->occurrenceMap["dets"][$detId]["sciname"] = $row->sciname;
			$this->occurrenceMap["dets"][$detId]["scientificnameauthorship"] = $row->scientificNameAuthorship;
			$this->occurrenceMap["dets"][$detId]["identificationqualifier"] = $row->identificationQualifier;
			$this->occurrenceMap["dets"][$detId]["identificationreferences"] = $row->identificationReferences;
			$this->occurrenceMap["dets"][$detId]["identificationremarks"] = $row->identificationRemarks;
			$this->occurrenceMap["dets"][$detId]["sortsequence"] = $row->sortsequence;
		}
		$result->close();
	}

	public function editOccurrence($occArr,$uid,$autoCommit){
		$status = '';
		$editedFields = trim($occArr['editedfields']);
		if($editedFields){
			//Add edits to omoccuredits
			$sqlEditsBase = 'INSERT INTO omoccuredits(occid,reviewstatus,appliedstatus,uid,fieldname,fieldvaluenew,fieldvalueold) '.
				'SELECT '.$occArr['occid'].' AS occid,"open" AS rs,'.($autoCommit?'1':'0').' AS astat,'.$uid.' AS uid,';
			$editArr = array_unique(explode(';',$editedFields));
			foreach($editArr as $k => $v){
				if($v){
					if(!array_key_exists($v,$occArr)){
						//Field is a checkbox that is unchecked
						$occArr[$v] = 0;
					}
					$sqlEdit = $sqlEditsBase.'"'.$v.'" AS fn,"'.$occArr[$v].'" AS fvn,'.$v.' FROM omoccurrences WHERE occid = '.$occArr['occid'];
					//echo '<div>'.$sqlEdit.'</div>';
					$this->conn->query($sqlEdit);
				}
			}
			//Edit record only if user is authorized to autoCommit 
			if($autoCommit){
				$status = 'SUCCESS: edits submitted and activated';
				$sql = '';
				foreach($editArr as $k => $v){
					if($v){
						$sql .= ','.$v.' = '.($occArr[$v]!==''?'"'.$occArr[$v].'"':'NULL');
					}
				}
				$sql = 'UPDATE omoccurrences SET '.substr($sql,1).' WHERE occid = '.$occArr['occid'];
				//echo $sql;
				if(!$this->conn->query($sql)){
					$status = 'ERROR: failed to edit occurrence record (#'.$occArr['occid'].'): '.$this->conn->error;
				}
			}
			else{
				$status = 'Edits submitted, but not activated.<br/> '.
					'Once edits are reviewed and approved by a data manager, they will be activated.<br/> '.
					'Thank you for aiding us in improving the data. ';
			}
		}
		else{
			$status = 'ERROR: edits empty for occid #'.$occArr['occid'].': '.$this->conn->error;
		}
		return $status;
	}

	public function addOccurrence($occArr){
		$status = "SUCCESS: new occurrence record submitted successfully";
		$dbpk = $occArr["catalognumber"];
		if(!$dbpk){
			$pkSql = 'SELECT MAX(dbpk+1) AS maxpk FROM omoccurrences WHERE collid = '.$occArr["collid"];
			$pkRs = $this->conn->query($pkSql);
			if($r = $pkRs->fetch_object()){
				$dbpk = $r->maxpk;
			}
			$pkRs->close();
			if(!$dbpk) $dbpk = 'symb1';
		}
		if($occArr){
			$sql = "INSERT INTO omoccurrences(collid, dbpk, basisOfRecord, occurrenceID, catalogNumber, otherCatalogNumbers, ".
			"ownerInstitutionCode, family, sciname, scientificNameAuthorship, identifiedBy, dateIdentified, ".
			"identificationReferences, identificationremarks, identificationQualifier, typeStatus, recordedBy, recordNumber, ".
			"associatedCollectors, eventDate, year, month, day, startDayOfYear, endDayOfYear, ".
			"verbatimEventDate, habitat, occurrenceRemarks, associatedTaxa, ".
			"dynamicProperties, reproductiveCondition, cultivationStatus, establishmentMeans, country, ".
			"stateProvince, county, locality, localitySecurity, decimalLatitude, decimalLongitude, ".
			"geodeticDatum, coordinateUncertaintyInMeters, verbatimCoordinates, ".
			"georeferencedBy, georeferenceProtocol, georeferenceSources, ".
			"georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters, ".
			"verbatimElevation, disposition, language) ".

			"VALUES (".$occArr["collid"].",\"".$dbpk."\",".
			($occArr["basisofrecord"]?"\"".$occArr["basisofrecord"]."\"":"NULL").",".
			($occArr["occurrenceid"]?"\"".$occArr["occurrenceid"]."\"":"NULL").",".
			($occArr["catalognumber"]?"\"".$occArr["catalognumber"]."\"":"NULL").",".
			($occArr["othercatalognumbers"]?"\"".$occArr["othercatalognumbers"]."\"":"NULL").",".
			($occArr["ownerinstitutioncode"]?"\"".$occArr["ownerinstitutioncode"]."\"":"NULL").",".
			($occArr["family"]?"\"".$occArr["family"]."\"":"NULL").",".
			"\"".$occArr["sciname"]."\",".
			($occArr["scientificnameauthorship"]?"\"".$occArr["scientificnameauthorship"]."\"":"NULL").",".
			($occArr["identifiedby"]?"\"".$occArr["identifiedby"]."\"":"NULL").",".
			($occArr["dateidentified"]?"\"".$occArr["dateidentified"]."\"":"NULL").",".
			($occArr["identificationreferences"]?"\"".$occArr["identificationreferences"]."\"":"NULL").",".
			($occArr["identificationremarks"]?"\"".$occArr["identificationremarks"]."\"":"NULL").",".
			($occArr["identificationqualifier"]?"\"".$occArr["identificationqualifier"]."\"":"NULL").",".
			($occArr["typestatus"]?"\"".$occArr["typestatus"]."\"":"NULL").",".
			($occArr["recordedby"]?"\"".$occArr["recordedby"]."\"":"NULL").",".
			($occArr["recordnumber"]?"\"".$occArr["recordnumber"]."\"":"NULL").",".
			($occArr["associatedcollectors"]?"\"".$occArr["associatedcollectors"]."\"":"NULL").",".
			($occArr["eventdate"]?"'".$occArr["eventdate"]."'":"NULL").",".
			($occArr["year"]?$occArr["year"]:"NULL").",".
			($occArr["month"]?$occArr["month"]:"NULL").",".
			($occArr["day"]?$occArr["day"]:"NULL").",".
			($occArr["startdayofyear"]?$occArr["startdayofyear"]:"NULL").",".
			($occArr["enddayofyear"]?$occArr["enddayofyear"]:"NULL").",".
			($occArr["verbatimeventdate"]?"\"".$occArr["verbatimeventdate"]."\"":"NULL").",".
			($occArr["habitat"]?"\"".$occArr["habitat"]."\"":"NULL").",".
			($occArr["occurrenceremarks"]?"\"".$occArr["occurrenceremarks"]."\"":"NULL").",".
			($occArr["associatedtaxa"]?"\"".$occArr["associatedtaxa"]."\"":"NULL").",".
			($occArr["dynamicproperties"]?"\"".$occArr["dynamicproperties"]."\"":"NULL").",".
			($occArr["reproductivecondition"]?"\"".$occArr["reproductivecondition"]."\"":"NULL").",".
			(array_key_exists("cultivationstatus",$occArr)?"1":"0").",".
			($occArr["establishmentmeans"]?"\"".$occArr["establishmentmeans"]."\"":"NULL").",".
			($occArr["country"]?"\"".$occArr["country"]."\"":"NULL").",".
			($occArr["stateprovince"]?"\"".$occArr["stateprovince"]."\"":"NULL").",".
			($occArr["county"]?"\"".$occArr["county"]."\"":"NULL").",".
			($occArr["locality"]?"\"".$occArr["locality"]."\"":"NULL").",".
			(array_key_exists("localitysecurity",$occArr)?"1":"0").",".
			($occArr["decimallatitude"]?$occArr["decimallatitude"]:"NULL").",".
			($occArr["decimallongitude"]?$occArr["decimallongitude"]:"NULL").",".
			($occArr["geodeticdatum"]?"\"".$occArr["geodeticdatum"]."\"":"NULL").",".
			($occArr["coordinateuncertaintyinmeters"]?"\"".$occArr["coordinateuncertaintyinmeters"]."\"":"NULL").",".
			($occArr["verbatimcoordinates"]?"\"".$occArr["verbatimcoordinates"]."\"":"NULL").",".
			($occArr["georeferencedby"]?"\"".$occArr["georeferencedby"]."\"":"NULL").",".
			($occArr["georeferenceprotocol"]?"\"".$occArr["georeferenceprotocol"]."\"":"NULL").",".
			($occArr["georeferencesources"]?"\"".$occArr["georeferencesources"]."\"":"NULL").",".
			($occArr["georeferenceverificationstatus"]?"\"".$occArr["georeferenceverificationstatus"]."\"":"NULL").",".
			($occArr["georeferenceremarks"]?"\"".$occArr["georeferenceremarks"]."\"":"NULL").",".
			($occArr["minimumelevationinmeters"]?$occArr["minimumelevationinmeters"]:"NULL").",".
			($occArr["maximumelevationinmeters"]?$occArr["maximumelevationinmeters"]:"NULL").",".
			($occArr["verbatimelevation"]?"\"".$occArr["verbatimelevation"]."\"":"NULL").",".
			($occArr["disposition"]?"\"".$occArr["disposition"]."\"":"NULL").",".
			($occArr["language"]?"\"".$occArr["language"]."\"":"NULL").") ";
			//echo "<div>".$sql."</div>";
			if(!$this->conn->query($sql)){
				$status = "ERROR - failed to add occurrence record: ".$this->conn->error;
			}
			$this->occId = $this->conn->insert_id;
		}
		return $status;
	}

	public function addDetermination($detArr){
		$status = "Determination submitted successfully!";
		$isCurrent = false;
		if(array_key_exists('makecurrent',$detArr) && $detArr['makecurrent'] == "1") $isCurrent = true;
		$sortSeq = 10;
		if($isCurrent){
			$sortSeq = 1;
			$sqlSort = 'UPDATE omoccurdeterminations '.
				'SET sortsequence = (sortsequence + 10) '.
				'WHERE occid = '.$detArr['occid'];
			$this->conn->query($sqlSort);
		}
		//Load new determination into omoccurdeterminations
		$sql = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
			'identificationQualifier, identificationReferences, identificationRemarks, sortsequence) '.
			'VALUES ('.$detArr['occid'].',"'.$detArr['identifiedby'].'","'.$detArr['dateidentified'].'","'.
			$detArr['sciname'].'",'.($detArr['scientificnameauthorship']?'"'.$detArr['scientificnameauthorship'].'"':'NULL').','.
			($detArr['identificationqualifier']?'"'.$detArr['identificationqualifier'].'"':'NULL').','.
			($detArr['identificationreferences']?'"'.$detArr['identificationreferences'].'"':'NULL').','.
			($detArr['identificationremarks']?'"'.$detArr['identificationremarks'].'"':'NULL').','.
			($detArr['sortsequence']?$detArr['sortsequence']:$sortSeq).')';
		//echo "<div>".$sql."</div>";
		if($this->conn->query($sql)){
			//If is current, move old determination from omoccurrences to omoccurdeterminations and then load new record into omoccurrences  
			if($isCurrent){
				//If determination is already in omoccurdeterminations, INSERT will fail move omoccurrences determination to  table
				$sqlInsert = 'INSERT INTO omoccurdeterminations(occid, identifiedBy, dateIdentified, sciname, scientificNameAuthorship, '.
					'identificationQualifier, identificationReferences, identificationRemarks, sortsequence) '.
					'SELECT occid, identifiedby, dateidentified, sciname, scientificnameauthorship, '.
					'identificationqualifier, identificationreferences, identificationremarks, 10 AS sortseq '.
					'FROM omoccurrences WHERE occid = '.$detArr['occid'];
				$rs = $this->conn->query($sqlInsert);
				//echo "<div>".$sqlInsert."</div>";
				//Load new determination into omoccurrences table
				$sqlNewDet = 'UPDATE omoccurrences '.
					'SET identifiedBy = "'.$detArr['identifiedby'].'", dateIdentified = "'.$detArr['dateidentified'].'",'.
					'family = '.($detArr['family']?'"'.$detArr['family'].'"':'NULL').','.
					'sciname = "'.$detArr['sciname'].'",genus = NULL, specificEpithet = NULL, taxonRank = NULL, infraspecificepithet = NULL,'.
					'scientificNameAuthorship = '.($detArr['scientificnameauthorship']?'"'.$detArr['scientificnameauthorship'].'"':'NULL').','.
					'identificationQualifier = '.($detArr['identificationqualifier']?'"'.$detArr['identificationqualifier'].'"':'NULL').','.
					'identificationReferences = '.($detArr['identificationreferences']?'"'.$detArr['identificationreferences'].'"':'NULL').','.
					'identificationRemarks = '.($detArr['identificationremarks']?'"'.$detArr['identificationremarks'].'"':'NULL').' '.
					'WHERE occid = '.$detArr['occid'];
				//echo "<div>".$sqlNewDet."</div>";
				$this->conn->query($sqlNewDet);
			}
		}
		else{
			$status = 'ERROR - failed to add determination: '.$this->conn->error;
		}
		return $status;
	}

	public function editDetermination($detArr){
		$status = "Determination editted successfully!";
		$sql = 'UPDATE omoccurdeterminations '.
			'SET identifiedBy = "'.$detArr['identifiedby'].'", dateIdentified = "'.$detArr['dateidentified'].'", sciname = "'.$detArr['sciname'].
			'", scientificNameAuthorship = '.($detArr['scientificnameauthorship']?'"'.$detArr['scientificnameauthorship'].'"':'NULL').','.
			'identificationQualifier = '.($detArr['identificationqualifier']?'"'.$detArr['identificationqualifier'].'"':'NULL').','.
			'identificationReferences = '.($detArr['identificationreferences']?'"'.$detArr['identificationreferences'].'"':'NULL').','.
			'identificationRemarks = '.($detArr['identificationremarks']?'"'.$detArr['identificationremarks'].'"':'NULL').','.
			'sortsequence = '.($detArr['sortsequence']?$detArr['sortsequence']:'10').' '.
			'WHERE detid = '.$detArr['detid'];
		if(!$this->conn->query($sql)){
			$status = "ERROR - failed to edit determination: ".$this->conn->error;
		}
		return $status;
	}

	public function deleteDetermination($detId){
		$status = 'Determination deleted successfully!';
		$sql = 'DELETE FROM omoccurdeterminations WHERE detid = '.$detId;
		if(!$this->conn->query($sql)){
			$status = "ERROR - failed to delete determination: ".$this->conn->error;
		}
		return $status;
	}

	public function editImage(){
		$rootUrl = $GLOBALS["imageRootUrl"];
		if(substr($rootUrl,-1) != "/") $rootUrl .= "/";
		$rootPath = $GLOBALS["imageRootPath"];
		if(substr($rootPath,-1) != "/") $rootPath .= "/";
		$status = "Image editted successfully!";
		$imgId = $_REQUEST["imgid"];
	 	$url = $_REQUEST["url"];
	 	$tnUrl = $_REQUEST["tnurl"];
	 	$origUrl = $_REQUEST["origurl"];
	 	if(array_key_exists("renameweburl",$_REQUEST)){
	 		$oldUrl = $_REQUEST["oldurl"];
	 		$oldName = str_replace($rootUrl,$rootPath,$oldUrl);
	 		$newName = str_replace($rootUrl,$rootPath,$url);
	 		if($url != $oldUrl){
	 			if(!rename($oldName,$newName)){
	 				$url = $oldUrl;
		 			$status .= "Web URL rename FAILED; ";
	 			}
	 		}
		}
		if(array_key_exists("renametnurl",$_REQUEST)){
	 		$oldTnUrl = $_REQUEST["oldtnurl"];
	 		$oldName = str_replace($rootUrl,$rootPath,$oldTnUrl);
	 		$newName = str_replace($rootUrl,$rootPath,$tnUrl);
	 		if($tnUrl != $oldTnUrl){
	 			if(!rename($oldName,$newName)){
	 				$tnUrl = $oldTnUrl;
		 			$status .= "Thumbnail URL rename FAILED; ";
	 			}
	 		}
		}
		if(array_key_exists("renameorigurl",$_REQUEST)){
	 		$oldOrigUrl = $_REQUEST["oldorigurl"];
	 		$oldName = str_replace($rootUrl,$rootPath,$oldOrigUrl);
	 		$newName = str_replace($rootUrl,$rootPath,$origUrl);
	 		if($origUrl != $oldOrigUrl){
	 			if(!rename($oldName,$newName)){
	 				$origUrl = $oldOrigUrl;
		 			$status .= "Thumbnail URL rename FAILED; ";
	 			}
	 		}
		}
		$occId = $_REQUEST["occid"];
		$caption = $this->cleanStr($_REQUEST["caption"]);
		$photographerUid = $_REQUEST["photographeruid"];
		$notes = $this->cleanStr($_REQUEST["notes"]);
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$sourceUrl = $this->cleanStr($_REQUEST["sourceurl"]);

		$sql = "UPDATE images ".
			"SET url = \"".$url."\", thumbnailurl = ".($tnUrl?"\"".$tnUrl."\"":"NULL").
			",originalurl = ".($origUrl?"\"".$origUrl."\"":"NULL").",occid = ".$occId.",caption = ".
			($caption?"\"".$caption."\"":"NULL").",photographeruid = ".($photographerUid?$photographerUid:"NULL").
			",notes = ".($notes?"\"".$notes."\"":"NULL").
			",copyright = ".($copyRight?"\"".$copyRight."\"":"NULL").",imagetype = \"specimen\",sourceurl = ".
			($sourceUrl?"\"".$sourceUrl."\"":"NULL").
			" WHERE imgid = ".$imgId;
		//echo $sql;
		if($this->conn->query($sql)){
			$this->setPrimaryImageSort();
		}
		else{
			$status .= "ERROR: image not changed, ".$this->conn->error."SQL: ".$sql;
		}
		return $status;
	}

	public function addImage(){
		$status = "Image added successfully!";
		//Set download paths and variables
		set_time_limit(120);
		ini_set("max_input_time",120);
 		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
		//Check for image path or download image file
		$imgUrl = (array_key_exists("imgurl",$_REQUEST)?$_REQUEST["imgurl"]:"");
		$imgPath = "";
		if(!$imgUrl){
			$imgPath = $this->loadImage();
			$imgUrl = str_replace($this->imageRootPath,$this->imageRootUrl,$imgPath);
		}
		if(!$imgUrl) return;
		
		$imgTnUrl = $this->createImageThumbnail($imgUrl);

		$imgWebUrl = $imgUrl;
		$imgLgUrl = "";
		if(strpos($imgUrl,"http://") === false || strpos($imgUrl,$this->imageRootUrl) !== false){
			//Create Large Image
			list($width, $height) = getimagesize($imgPath?$imgPath:$imgUrl);
			$fileSize = filesize($imgPath?$imgPath:$imgUrl);
			$createlargeimg = (array_key_exists('createlargeimg',$_REQUEST)&&$_REQUEST['createlargeimg']==1?true:false);
			if($createlargeimg && ($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit)){
				$lgWebUrlTemp = str_ireplace("_temp.jpg","lg.jpg",$imgPath); 
				if($width < ($this->lgPixWidth*1.2)){
					if(copy($imgPath,$lgWebUrlTemp)){
						$imgLgUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$lgWebUrlTemp);
					}
				}
				else{
					if($this->createNewImage($imgPath,$lgWebUrlTemp,$this->lgPixWidth)){
						$imgLgUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$lgWebUrlTemp);
					}
				}
			}

			//Create web url
			$imgTargetPath = str_ireplace("_temp.jpg",".jpg",$imgPath);
			if($width < ($this->webPixWidth*1.2) && $fileSize < $this->webFileSizeLimit){
				rename($imgPath,$imgTargetPath);
			}
			else{
				$newWidth = ($width<($this->webPixWidth*1.2)?$width:$this->webPixWidth);
				$this->createNewImage($imgPath,$imgTargetPath,$newWidth);
			}
			$imgWebUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$imgTargetPath);
			if(file_exists($imgPath)) unlink($imgPath);
		}
			
		if($imgWebUrl){
			$occId = $_REQUEST["occid"];
			$owner = $_REQUEST["institutioncode"];
			$caption = $this->cleanStr($_REQUEST["caption"]);
			$photographerUid = $_REQUEST["photographeruid"];
			$sourceUrl = (array_key_exists("sourceurl",$_REQUEST)?trim($_REQUEST["sourceurl"]):"");
			$copyRight = $this->cleanStr($_REQUEST["copyright"]);
			$notes = (array_key_exists("notes",$_REQUEST)?$this->cleanStr($_REQUEST["notes"]):"");
			$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographeruid, caption, '.
				'owner, sourceurl, copyright, occid, notes) '.
				'VALUES ('.$_REQUEST['tid'].',"'.$imgWebUrl.'",'.($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.($imgLgUrl?'"'.$imgLgUrl.'"':'NULL').','.
				($photographerUid?$photographerUid:'NULL').','.($caption?'"'.$caption.'"':'NULL').','.
				($owner?'"'.$owner.'"':'NULL').','.($sourceUrl?'"'.$sourceUrl.'"':'NULL').','.
				($copyRight?'"'.$copyRight.'"':'NULL').','.($occId?$occId:'NULL').','.($notes?'"'.$notes.'"':'NULL').')';
			//echo $sql;
			if($this->conn->query($sql)){
				$this->setPrimaryImageSort();
			}
			else{
				$status = "ERROR Loading Image Data: ".$this->conn->error."<br/>SQL: ".$sql;
			}
		}
		return $status;
	}

	private function loadImage(){
	 	$imgFile = basename($_FILES['imgfile']['name']);
		$fileName = $this->getFileName($imgFile);
	 	$downloadPath = $this->getDownloadPath($fileName); 
	 	if(move_uploaded_file($_FILES['imgfile']['tmp_name'], $downloadPath)){
			return $downloadPath;
	 	}
	 	return;
	}

	private function getFileName($fName){
		$fName = str_replace(" ","_",$fName);
		$fName = str_replace(array(chr(231),chr(232),chr(233),chr(234),chr(260)),"a",$fName);
		$fName = str_replace(array(chr(230),chr(236),chr(237),chr(238)),"e",$fName);
		$fName = str_replace(array(chr(239),chr(240),chr(241),chr(261)),"i",$fName);
		$fName = str_replace(array(chr(247),chr(248),chr(249),chr(262)),"o",$fName);
		$fName = str_replace(array(chr(250),chr(251),chr(263)),"u",$fName);
		$fName = str_replace(array(chr(264),chr(265)),"n",$fName);
		$fName = preg_replace("/[^a-zA-Z0-9\-_\.]/", "", $fName);
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,25).substr($fName,strrpos($fName,"."));
		}
 		return $fName;
 	}
 	
	private function getDownloadPath($fileName){
 		if(!file_exists($this->imageRootPath.$_REQUEST["institutioncode"])){
 			mkdir($this->imageRootPath.$_REQUEST["institutioncode"], 0775);
 		}
		$path = $this->imageRootPath.$_REQUEST["institutioncode"]."/";
		$yearMonthStr = date('Ym');
 		if(!file_exists($path.$yearMonthStr)){
 			mkdir($path.$yearMonthStr, 0775);
 		}
		$path = $path.$yearMonthStr."/";
 		//Check and see if file already exists, if so, rename filename until it has a unique name
 		$tempFileName = $fileName;
 		$cnt = 0;
 		while(file_exists($path.$tempFileName)){
 			$tempFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fileName);
 			$cnt++;
 		}
 		$fileName = str_ireplace(".jpg","_temp.jpg",$tempFileName);
 		return $path.$fileName;
 	}

	private function createImageThumbnail($imgUrl){
		$newThumbnailUrl = "";
		if($imgUrl){
			$imgPath = "";
			$newThumbnailPath = "";
			if(strpos($imgUrl,"http://") === 0 && strpos($imgUrl,$this->imageRootUrl) === false){
				$imgPath = $imgUrl;
				if(!is_dir($this->imageRootPath."misc_thumbnails/")){
					if(!mkdir($this->imageRootPath."misc_thumbnails/", 0775)) return "";
				}
				$fileName = "";
				if(stripos($imgUrl,"_temp.jpg")){
					$fileName = str_ireplace("_temp.jpg","tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				}
				else{
					$fileName = str_ireplace(".jpg","tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				}
				$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
				$cnt = 1;
				$fileNameBase = str_ireplace("tn.jpg","",$fileName);
				while(file_exists($newThumbnailPath)){
					$fileName = $fileNameBase."tn".$cnt.".jpg";
					$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
					$cnt++; 
				}
				$newThumbnailUrl = $this->imageRootUrl."misc_thumbnails/".$fileName;
			}
			elseif(strpos($imgUrl,$this->imageRootUrl) === 0){
				$imgPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
				$newThumbnailUrl = str_ireplace("_temp.jpg","tn.jpg",$imgUrl);
				$newThumbnailPath = str_replace($this->imageRootUrl,$this->imageRootPath,$newThumbnailUrl);
			}
			if(!$newThumbnailUrl) return "";
			if(!$this->createNewImage($imgPath,$newThumbnailPath,$this->tnPixWidth,70)){
				return false;
			}
		}
		return $newThumbnailUrl;
	}
	
	private function createNewImage($sourceImg,$targetPath,$targetWidth,$qualityRating = 0){
        $successStatus = false;
		list($sourceWidth, $sourceHeight) = getimagesize($sourceImg);
        $newWidth = $targetWidth;
        $newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
        if($newHeight > $targetWidth*1.2){
        	$newHeight = $targetWidth;
        	$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
        }

       	$newImg = imagecreatefromjpeg($sourceImg);  

    	$tmpImg = imagecreatetruecolor($newWidth,$newHeight);

		imagecopyresampled($tmpImg,$newImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);

        if($qualityRating){
        	$successStatus = imagejpeg($tmpImg, $targetPath, $qualityRating);
        }
        else{
        	$successStatus = imagejpeg($tmpImg, $targetPath);
        }

        imagedestroy($tmpImg);
	    return $successStatus;
	}
	
	public function deleteImage($imgIdDel, $removeImg){
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = "";
		$status = "Image deleted successfully";
		$occid = 0;
		$sqlQuery = "SELECT url, thumbnailurl, originalurl, occid ".
			"FROM images WHERE imgid = ".$imgIdDel;
		$result = $this->conn->query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgThumbnailUrl = $row->thumbnailurl;
			$imgOriginalUrl = $row->originalurl;
		}
		$result->close();
				
		$sql = "DELETE FROM images WHERE imgid = ".$imgIdDel;
		//echo $sql;
		if($this->conn->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT imgid FROM images WHERE url = '".$imgUrl."'";
				$rs = $this->conn->query($sql);
				if(!$rs->num_rows){
					$imageRootUrl = $GLOBALS["imageRootUrl"];
					if(substr($imageRootUrl,-1)!='/') $imageRootUrl .= "/";
					$imageRootPath = $GLOBALS["imageRootPath"];
					if(substr($imageRootPath,-1)!='/') $imageRootPath .= "/";
					//Delete image from server 
					$imgDelPath = str_replace($imageRootUrl,$imageRootPath,$imgUrl);
					if(file_exists($imgDelPath)){
						if(!unlink($imgDelPath)){
							$status = "Deleted image record from database successfully but FAILED to delete image from server. The Image will have to be deleted manually.";
						}
					}
					$imgTnDelPath = str_replace($imageRootUrl,$imageRootPath,$imgThumbnailUrl);
					if(file_exists($imgTnDelPath)){
						unlink($imgTnDelPath);
					}
					$imgOriginalDelPath = str_replace($imageRootUrl,$imageRootPath,$imgOriginalUrl);
					if(file_exists($imgOriginalDelPath)){
						unlink($imgOriginalDelPath);
					}
				}
			}
		}
		else{
			$status = "deleteImage: ".$this->conn->error."\nSQL: ".$sql;
		}
		$this->setPrimaryImageSort();
		return $status;
	}
	
	public function getPhotographerArr(){
		if(!$this->photographerArr){
			$sql = "SELECT u.uid, CONCAT_WS(', ',u.lastname,u.firstname) AS fullname ".
				"FROM users u ORDER BY u.lastname, u.firstname ";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$this->photographerArr[$row->uid] = $row->fullname;
			}
			$result->close();
		}
		return $this->photographerArr;
	}

	private function setPrimaryImageSort(){
		$sql = "UPDATE images ti2 INNER JOIN ".
			"(SELECT ti.imgid FROM omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted INNER JOIN images ti ON ts2.tid = ti.tid ".
			"WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND o.occid = ".$this->occId." ORDER BY ti.SortSequence LIMIT 1) innertab ".
			"ON ti2.imgid = innertab.imgid SET ti2.SortSequence = 1";
		//echo $sql;
		$this->conn->query($sql);
	}

	private function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace("\"","'",$newStr);
		return $newStr;
	}
	
	public function getObserverUid(){
		$obsId = 0;
		$rs = $this->conn->query('SELECT observeruid FROM omoccurrences WHERE occid = '.$this->occId);
		if($row = $rs->fetch_object()){
			$obsId = $row->observeruid;
		}
		$rs->close();
		return $obsId;
	}
	
	public function getCollectionList($collArr){
		$collList = Array();
		$sql = 'SELECT collid, collectionname, institutioncode, collectioncode FROM omcollections ';
		if($collArr){
			$sql .= 'WHERE collid IN ('.implode(',',$collArr).') ';
		}
		$sql .= 'ORDER BY collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$collName = $r->collectionname;
			if($r->institutioncode){
				$collName .= ' ('.$r->institutioncode;
				if($r->collectioncode) $collName .= ':'.$r->collectioncode;
				$collName .= ')';
			}
			$collList[$r->collid] = $collName;
		}
		return $collList;
	}
	
	public function carryOverValues($fArr){
		$locArr = Array('recordedby','recordnumber','associatedcollectors','eventdate','verbatimeventdate','month','day','year',
			'startdayofyear','enddayofyear','country','stateprovince','county','locality','decimallatitude','decimallongitude',
			'verbatimcoordinates','localitysecurity','coordinateuncertaintyinmeters','geodeticdatum','minimumelevationinmeters',
			'maximumelevationinmeters','verbatimelevation','verbatimcoordinates','georeferencedby','georeferenceprotocol',
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks','habitat','associatedtaxa');
		return array_intersect_key($fArr,array_flip($locArr)); 
	}
}

?>

