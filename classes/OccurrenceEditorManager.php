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
		$this->occId = $id;
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getOccurArr(){
		$metaSql = "SHOW COLUMNS FROM omoccurrences";
		$metaRs = $this->conn->query($metaSql);
		while($metaRow = $metaRs->fetch_object()){
			$this->occurrenceMap[strtolower($metaRow->Field)]["type"] = $metaRow->Type;
		}
		$metaRs->close();
		$sql = "SELECT c.CollectionName, o.occid, o.collid, o.dbpk, o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, ".
			"o.ownerInstitutionCode, o.family, o.scientificName, o.sciname, o.tidinterpreted, o.genus, o.institutionID, o.collectionID, ".
			"o.specificEpithet, o.datasetID, o.taxonRank, o.infraspecificEpithet, ".
			"IFNULL(o.institutionCode,c.institutionCode) AS institutionCode, IFNULL(o.collectionCode,c.collectionCode) AS collectionCode, ".
			"o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, ".
			"o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, o.CollectorFamilyName, ".
			"o.CollectorInitials, o.associatedCollectors, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, ".
			"o.verbatimEventDate, o.habitat, o.occurrenceRemarks, o.associatedOccurrences, o.associatedTaxa, ".
			"o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, ".
			"o.stateProvince, o.county, o.municipality, o.locality, o.localitySecurity, o.decimalLatitude, o.decimalLongitude, ".
			"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, ".
			"o.verbatimCoordinateSystem, o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, ".
			"o.georeferenceVerificationStatus, o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, ".
			"o.verbatimElevation, o.previousIdentifications, o.disposition, o.modified, o.language, o.observeruid, o.dateLastModified ".
			"FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid ".
			"WHERE o.occid = ".$this->occId;
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			foreach($row as $k => $v){
				$this->occurrenceMap[strtolower($k)]["value"] = $v;
			}
		}
		$rs->close();

		$this->setImages();

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
	
	public function editOccurrence($occArr){
		if($occArr){
			$sql = "UPDATE omoccurrences SET basisofrecord = ".($occArr["basisofrecord"]?"'".$occArr["basisofrecord"]."'":"NULL").",".
			"occurrenceid = ".($occArr["occurrenceid"]?"'".$occArr["occurrenceid"]."'":"NULL").",".
			"catalognumber = ".($occArr["catalognumber"]?"'".$occArr["catalognumber"]."'":"NULL").",".
			"othercatalognumbers = ".($occArr["othercatalognumbers"]?"'".$occArr["othercatalognumbers"]."'":"NULL").",".
			"ownerinstitutioncode = ".($occArr["ownerinstitutioncode"]?"'".$occArr["ownerinstitutioncode"]."'":"NULL").",".
			"family = ".($occArr["family"]?"'".$occArr["family"]."'":"NULL").",".
			"sciname = '".$occArr["sciname"]."',".
			"scientificnameauthorship = ".($occArr["scientificnameauthorship"]?"'".$occArr["scientificnameauthorship"]."'":"NULL").",".
			"taxonremarks = ".($occArr["taxonremarks"]?"'".$occArr["taxonremarks"]."'":"NULL").",".
			"identifiedby = ".($occArr["identifiedby"]?"'".$occArr["identifiedby"]."'":"NULL").",".
			"dateidentified = ".($occArr["dateidentified"]?"'".$occArr["dateidentified"]."'":"NULL").",".
			"identificationreferences = ".($occArr["identificationreferences"]?"'".$occArr["identificationreferences"]."'":"NULL").",".
			"identificationqualifier = ".($occArr["identificationqualifier"]?"'".$occArr["identificationqualifier"]."'":"NULL").",".
			"typestatus = ".($occArr["typestatus"]?"'".$occArr["typestatus"]."'":"NULL").",".
			"recordedby = ".($occArr["recordedby"]?"'".$occArr["recordedby"]."'":"NULL").",".
			"recordnumber = ".($occArr["recordnumber"]?"'".$occArr["recordnumber"]."'":"NULL").",".
			"associatedcollectors = ".($occArr["associatedcollectors"]?"'".$occArr["associatedcollectors"]."'":"NULL").",".
			"eventDate = ".($occArr["eventdate"]?"'".$occArr["eventdate"]."'":"NULL").",".
			"year = ".($occArr["year"]?$occArr["year"]:"NULL").",".
			"month = ".($occArr["month"]?$occArr["month"]:"NULL").",".
			"day = ".($occArr["day"]?$occArr["day"]:"NULL").",".
			"startDayOfYear = ".($occArr["startdayofyear"]?$occArr["startdayofyear"]:"NULL").",".
			"endDayOfYear = ".($occArr["enddayofyear"]?$occArr["enddayofyear"]:"NULL").",".
			"verbatimEventDate = ".($occArr["verbatimeventdate"]?"'".$occArr["verbatimeventdate"]."'":"NULL").",".
			"habitat = ".($occArr["habitat"]?"'".$occArr["habitat"]."'":"NULL").",".
			"occurrenceRemarks = ".($occArr["occurrenceremarks"]?"'".$occArr["occurrenceremarks"]."'":"NULL").",".
			"associatedOccurrences = ".($occArr["associatedoccurrences"]?"'".$occArr["associatedoccurrences"]."'":"NULL").",".
			"associatedTaxa = ".($occArr["associatedtaxa"]?"'".$occArr["associatedtaxa"]."'":"NULL").",".
			"dynamicProperties = ".($occArr["dynamicproperties"]?"'".$occArr["dynamicproperties"]."'":"NULL").",".
			"reproductiveCondition = ".($occArr["reproductivecondition"]?"'".$occArr["reproductivecondition"]."'":"NULL").",".
			"cultivationStatus = ".(array_key_exists("cultivationstatus",$occArr)?"1":"0").",".
			"establishmentMeans = ".($occArr["establishmentmeans"]?"'".$occArr["establishmentmeans"]."'":"NULL").",".
			"country = ".($occArr["country"]?"'".$occArr["country"]."'":"NULL").",".
			"stateProvince = ".($occArr["stateprovince"]?"'".$occArr["stateprovince"]."'":"NULL").",".
			"county = ".($occArr["county"]?"'".$occArr["county"]."'":"NULL").",".
			"municipality = ".($occArr["municipality"]?"'".$occArr["municipality"]."'":"NULL").",".
			"locality = ".($occArr["locality"]?"'".$occArr["locality"]."'":"NULL").",".
			"localitySecurity = ".(array_key_exists("localitysecurity",$occArr)?"1":"0").",".
			"decimalLatitude = ".($occArr["decimallatitude"]?$occArr["decimallatitude"]:"NULL").",".
			"decimalLongitude = ".($occArr["decimallongitude"]?$occArr["decimallongitude"]:"NULL").",".
			"geodeticDatum = ".($occArr["geodeticdatum"]?"'".$occArr["geodeticdatum"]."'":"NULL").",". 
			"coordinateUncertaintyInMeters = ".($occArr["coordinateuncertaintyinmeters"]?"'".$occArr["coordinateuncertaintyinmeters"]."'":"NULL").",".
			"verbatimCoordinates = ".($occArr["verbatimcoordinates"]?"'".$occArr["verbatimcoordinates"]."'":"NULL").",".
			"verbatimCoordinateSystem = ".($occArr["verbatimcoordinatesystem"]?"'".$occArr["verbatimcoordinatesystem"]."'":"NULL").",".
			"georeferencedBy = ".($occArr["georeferencedby"]?"'".$occArr["georeferencedby"]."'":"NULL").",".
			"georeferenceProtocol = ".($occArr["georeferenceprotocol"]?"'".$occArr["georeferenceprotocol"]."'":"NULL").",".
			"georeferenceSources = ".($occArr["georeferencesources"]?"'".$occArr["georeferencesources"]."'":"NULL").",".
			"georeferenceVerificationStatus = ".($occArr["georeferenceverificationstatus"]?"'".$occArr["georeferenceverificationstatus"]."'":"NULL").",".
			"georeferenceRemarks = ".($occArr["georeferenceremarks"]?"'".$occArr["georeferenceremarks"]."'":"NULL").",".
			"minimumElevationInMeters = ".($occArr["minimumelevationinmeters"]?$occArr["minimumelevationinmeters"]:"NULL").",".
			"maximumElevationInMeters = ".($occArr["maximumelevationinmeters"]?$occArr["maximumelevationinmeters"]:"NULL").",".
			"verbatimElevation = ".($occArr["verbatimelevation"]?"'".$occArr["verbatimelevation"]."'":"NULL").",".
			"disposition = ".($occArr["disposition"]?"'".$occArr["disposition"]."'":"NULL").",".
			"language = ".($occArr["language"]?"'".$occArr["language"]."' ":"NULL ").
			"WHERE occid = ".$occArr["occid"];
			//echo $sql;
			$this->conn->query($sql);
		}
		
	}

	public function addOccurrence($occArr){
		if($occArr){
			$sql = "INSERT INTO omoccurrences(collid, dbpk, basisOfRecord, occurrenceID, catalogNumber, otherCatalogNumbers, ".
			"ownerInstitutionCode, family, sciname, scientificNameAuthorship, taxonRemarks, identifiedBy, dateIdentified, ".
			"identificationReferences, identificationQualifier, typeStatus, recordedBy, recordNumber, ".
			"associatedCollectors, eventDate, year, month, day, startDayOfYear, endDayOfYear, ".
			"verbatimEventDate, habitat, occurrenceRemarks, associatedOccurrences, associatedTaxa, ".
			"dynamicProperties, reproductiveCondition, cultivationStatus, establishmentMeans, country, ".
			"stateProvince, county, municipality, locality, localitySecurity, decimalLatitude, decimalLongitude, ".
			"geodeticDatum, coordinateUncertaintyInMeters, verbatimCoordinates, ".
			"verbatimCoordinateSystem, georeferencedBy, georeferenceProtocol, georeferenceSources, ".
			"georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters, ".
			"verbatimElevation, disposition, language) ".

			"VALUES (".$occArr["collid"].",\"".$occArr["catalognumber"]."\",".
			($occArr["basisofrecord"]?"\"".$occArr["basisofrecord"]."\"":"NULL").",".
			($occArr["occurrenceid"]?"\"".$occArr["occurrenceid"]."\"":"NULL").",".
			($occArr["catalognumber"]?"\"".$occArr["catalognumber"]."\"":"NULL").",".
			($occArr["othercatalognumbers"]?"\"".$occArr["othercatalognumbers"]."\"":"NULL").",".
			($occArr["ownerinstitutioncode"]?"\"".$occArr["ownerinstitutioncode"]."\"":"NULL").",".
			($occArr["family"]?"\"".$occArr["family"]."\"":"NULL").",".
			"\"".$occArr["sciname"]."\",".
			($occArr["scientificnameauthorship"]?"\"".$occArr["scientificnameauthorship"]."\"":"NULL").",".
			($occArr["taxonremarks"]?"\"".$occArr["taxonremarks"]."\"":"NULL").",".
			($occArr["identifiedby"]?"\"".$occArr["identifiedby"]."\"":"NULL").",".
			($occArr["dateidentified"]?"\"".$occArr["dateidentified"]."\"":"NULL").",".
			($occArr["identificationreferences"]?"\"".$occArr["identificationreferences"]."\"":"NULL").",".
			($occArr["identificationqualifier"]?"\"".$occArr["identificationqualifier"]."\"":"NULL").",".
			($occArr["typestatus"]?"\"".$occArr["typestatus"]."\"":"NULL").",".
			($occArr["recordedby"]?"\"".$occArr["recordedby"]."\"":"NULL").",".
			($occArr["recordnumber"]?"\"".$occArr["recordnumber"]."\"":"NULL").",".
			($occArr["associatedcollectors"]?"\"".$occArr["associatedcollectors"]."\"":"NULL").",".
			($occArr["eventdate"]?"\"".$occArr["eventdate"]."\"":"NULL").",".
			($occArr["year"]?$occArr["year"]:"NULL").",".
			($occArr["month"]?$occArr["month"]:"NULL").",".
			($occArr["day"]?$occArr["day"]:"NULL").",".
			($occArr["startdayofyear"]?$occArr["startdayofyear"]:"NULL").",".
			($occArr["enddayofyear"]?$occArr["enddayofyear"]:"NULL").",".
			($occArr["verbatimeventdate"]?"\"".$occArr["verbatimeventdate"]."\"":"NULL").",".
			($occArr["habitat"]?"\"".$occArr["habitat"]."\"":"NULL").",".
			($occArr["occurrenceremarks"]?"\"".$occArr["occurrenceremarks"]."\"":"NULL").",".
			($occArr["associatedoccurrences"]?"\"".$occArr["associatedoccurrences"]."\"":"NULL").",".
			($occArr["associatedtaxa"]?"\"".$occArr["associatedtaxa"]."\"":"NULL").",".
			($occArr["dynamicproperties"]?"\"".$occArr["dynamicproperties"]."\"":"NULL").",".
			($occArr["reproductivecondition"]?"\"".$occArr["reproductivecondition"]."\"":"NULL").",".
			(array_key_exists("cultivationstatus",$occArr)?"1":"0").",".
			($occArr["establishmentmeans"]?"\"".$occArr["establishmentmeans"]."\"":"NULL").",".
			($occArr["country"]?"\"".$occArr["country"]."\"":"NULL").",".
			($occArr["stateprovince"]?"\"".$occArr["stateprovince"]."\"":"NULL").",".
			($occArr["county"]?"\"".$occArr["county"]."\"":"NULL").",".
			($occArr["municipality"]?"\"".$occArr["municipality"]."\"":"NULL").",".
			($occArr["locality"]?"\"".$occArr["locality"]."\"":"NULL").",".
			(array_key_exists("localitysecurity",$occArr)?"1":"0").",".
			($occArr["decimallatitude"]?$occArr["decimallatitude"]:"NULL").",".
			($occArr["decimallongitude"]?$occArr["decimallongitude"]:"NULL").",".
			($occArr["geodeticdatum"]?"\"".$occArr["geodeticdatum"]."\"":"NULL").",".
			($occArr["coordinateuncertaintyinmeters"]?"\"".$occArr["coordinateuncertaintyinmeters"]."\"":"NULL").",".
			($occArr["verbatimcoordinates"]?"\"".$occArr["verbatimcoordinates"]."\"":"NULL").",".
			($occArr["verbatimcoordinatesystem"]?"\"".$occArr["verbatimcoordinatesystem"]."\"":"NULL").",".
			($occArr["georeferencedby"]?"\"".$occArr["georeferencedby"]."\"":"NULL").",".
			($occArr["georeferenceprotocol"]?"\"".$occArr["georeferenceprotocol"]."\"":"NULL").",".
			($occArr["georeferencesources"]?"\"".$occArr["georeferencesources"]."\"":"NULL").",".
			($occArr["georeferenceverificationstatus"]?"\"".$occArr["georeferenceverificationstatus"]."\"":"NULL").",".
			($occArr["georeferenceremarks"]?"\"".$occArr["georeferenceremarks"]."\"":"NULL").",".
			($occArr["minimumelevationinmeters"]?$occArr["minimumelevationinmeters"]:"NULL").",".
			($occArr["maximumelevationinmeters"]?$occArr["maximumelevationinmeters"]:"NULL").",".
			($occArr["verbatimelevation"]?"\"".$occArr["verbatimelevation"]."\"":"NULL").",".
			($occArr["disposition"]?"\"".$occArr["disposition"]."\"":"NULL").",".
			($occArr["language"]?"\"".$occArr["language"]."\"":"NULL").") ".
			"WHERE occid = ".$occArr["occid"];
			$this->conn->query($sql);
		}
	}
	
	public function editImage(){
		$rootUrl = $GLOBALS["imageRootUrl"];
		if(substr($rootUrl,-1) != "/") $rootUrl .= "/";
		$rootPath = $GLOBALS["imageRootPath"];
		if(substr($rootPath,-1) != "/") $rootPath .= "/";
		$status = "";
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
		$sortSequence = (array_key_exists("sortseq",$_REQUEST)?$_REQUEST["sortseq"]:0);

		$sql = "UPDATE images ".
			"SET url = \"".$url."\", thumbnailurl = ".($tnUrl?"\"".$tnUrl."\"":"NULL").
			",originalurl = ".($origUrl?"\"".$origUrl."\"":"NULL").",occid = ".$occId.",caption = ".
			($caption?"\"".$caption."\"":"NULL").",photographeruid = ".($photographerUid?$photographerUid:"NULL").
			",notes = ".($notes?"\"".$notes."\"":"NULL").
			",copyright = ".($copyRight?"\"".$copyRight."\"":"NULL").",imagetype = \"specimen\",sourceurl = ".
			($sourceUrl?"\"".$sourceUrl."\"":"NULL").
			($_REQUEST["sortseq"]?",sortsequence = ".$_REQUEST["sortseq"]:"").
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
			if($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit){
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
			$sortSequence = $_REQUEST["sortseq"];
			$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographeruid, caption, ".
				"owner, sourceurl, copyright, occid, notes, sortsequence) ".
				"VALUES (".$_REQUEST["tid"].",\"".$imgWebUrl."\",".($imgTnUrl?"\"".$imgTnUrl."\"":"NULL").",".($imgLgUrl?"\"".$imgLgUrl."\"":"NULL").",".
				($photographerUid?$photographerUid:"NULL").",\"".$caption."\",\"".$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",".
				($occId?$occId:"NULL").",\"".$notes."\",".($sortSequence?$sortSequence:"50").")";
			//echo $sql;
			$status = "";
			if($this->conn->query($sql)){
				$this->setPrimaryImageSort();
			}
			else{
				$status = "loadImageData: ".$this->conn->error."<br/>SQL: ".$sql;
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
		$status = "";
		if($this->conn->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT imgid FROM images WHERE url = '".$imgUrl."'";
				$rs = $this->conn->query($sql);
				if(!$rs->num_rows){
					$imageRootUrl = $GLOBALS["imageRootUrl"];
					$imageRootPath = $GLOBALS["imageRootPath"];
					//Delete image from server 
					$imgDelPath = str_replace($imageRootUrl,$imageRootPath,$imgUrl);
					if(file_exists($imgDelPath)){
						if(!unlink($imgDelPath)){
							$status = "Deleted records from database successfully but FAILED to delete image from server. The Image will have to be deleted manually.";
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
	
 	private function LatLonPointUTMtoLL($northing, $easting, $zone=12) {
		$d = 0.99960000000000004; // scale along long0
		$d1 = 6378137; // Polar Radius
		$d2 = 0.0066943799999999998;

		$d4 = (1 - sqrt(1 - $d2)) / (1 + sqrt(1 - $d2));
		$d15 = $easting - 500000;
		$d16 = $northing;
		$d11 = (($zone - 1) * 6 - 180) + 3;
		$d3 = $d2 / (1 - $d2);
		$d10 = $d16 / $d;
		$d12 = $d10 / ($d1 * (1 - $d2 / 4 - (3 * $d2 * $d2) / 64 - (5 * pow($d2,3) ) / 256));
		$d14 = $d12 + ((3 * $d4) / 2 - (27 * pow($d4,3) ) / 32) * sin(2 * $d12) + ((21 * $d4 * $d4) / 16 - (55 * pow($d4,4) ) / 32) * sin(4 * $d12) + ((151 * pow($d4,3) ) / 96) * sin(6 * $d12);
		$d13 = rad2deg($d14);
		$d5 = $d1 / sqrt(1 - $d2 * sin($d14) * sin($d14));
		$d6 = tan($d14) * tan($d14);
		$d7 = $d3 * cos($d14) * cos($d14);
		$d8 = ($d1 * (1 - $d2)) / pow(1 - $d2 * sin($d14) * sin($d14), 1.5);
		$d9 = $d15 / ($d5 * $d);
		$d17 = $d14 - (($d5 * tan($d14)) / $d8) * ((($d9 * $d9) / 2 - (((5 + 3 * $d6 + 10 * $d7) - 4 * $d7 * $d7 - 9 * $d3) * pow($d9,4) ) / 24) + (((61 + 90 * $d6 + 298 * $d7 + 45 * $d6 * $d6) - 252 * $d3 - 3 * $d7 * $d7) * pow($d9,6) ) / 720);
		$d17 = rad2deg($d17); // Breddegrad (N)
		$d18 = (($d9 - ((1 + 2 * $d6 + $d7) * pow($d9,3) ) / 6) + (((((5 - 2 * $d7) + 28 * $d6) - 3 * $d7 * $d7) + 8 * $d3 + 24 * $d6 * $d6) * pow($d9,5) ) / 120) / cos($d14);
		$d18 = $d11 + rad2deg($d18); // Længdegrad (Ø)
		return array('lat'=>$d17,'lng'=>$d18);
	}
}

?>

