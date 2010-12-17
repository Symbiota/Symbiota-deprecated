<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceEditorManager {

	private $conn;
	private $occId;
	private $occurrenceMap = Array();

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getOccurArr($oid){
		$this->occId = $oid;
		$metaSql = "SHOW COLUMNS FROM omoccurrences";
		$metaRs = $this->conn->query($metaSql);
		while($metaRow = $metaRs->fetch_object()){
			$this->occurrenceMap[strtolower($metaRow->Field)]["type"] = $metaRow->Type;
		}
		$metaRs->close();
		$sql = "SELECT c.CollectionName, o.occid, o.collid, o.dbpk, o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, ".
			"o.ownerInstitutionCode, o.family, o.scientificName, o.sciname, o.tidinterpreted, o.genus, o.institutionID, o.collectionID, ".
			"o.specificEpithet, o.datasetID, o.taxonRank, o.infraspecificEpithet, o.institutionCode, o.collectionCode, ".
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
			"WHERE o.occid = ".$oid;
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
		$sql = "SELECT i.imgid, i.url, i.thumbnailurl, i.originalurl, i.caption, i.sourceurl, i.copyright, i.notes, i.sortsequence ".
			"FROM images i ".
			"WHERE i.occid = ".$this->occId." ORDER BY i.sortsequence";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$this->occurrenceMap["images"][$imgId]["url"] = $row->url;
			$this->occurrenceMap["images"][$imgId]["tnurl"] = $row->thumbnailurl;
			$this->occurrenceMap["images"][$imgId]["origurl"] = $row->originalurl;
			$this->occurrenceMap["images"][$imgId]["caption"] = $row->caption;
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
		$tid = $_REQUEST["tid"];
		$caption = $this->cleanStr($_REQUEST["caption"]);
		$notes = $this->cleanStr($_REQUEST["notes"]);
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$sourceUrl = $this->cleanStr($_REQUEST["sourceurl"]);
		$sortSequence = (array_key_exists("sortsequence",$_REQUEST)?$_REQUEST["sortsequence"]:0);

		$sql = "UPDATE images ".
			"SET url = \"".$url."\", thumbnailurl = ".($tnUrl?"\"".$tnUrl."\"":"NULL").
			",originalurl = ".($origUrl?"\"".$origUrl."\"":"NULL").",occid = ".$occId.",tid = ".$tid.",caption = ".
			($caption?"\"".$caption."\"":"NULL").",notes = ".($notes?"\"".$notes."\"":"NULL").
			",copyright = ".($copyRight?"\"".$copyRight."\"":"NULL").",imagetype = \"specimen\",sourceurl = ".
			($sourceUrl?"\"".$sourceUrl."\"":"NULL").",sortsequence = ".$sortSequence." ".
			"WHERE imgid = ".$imgId;
		//echo $sql;
		if($this->conn->query($sql)){
			$this->setPrimaryImageSort($tid);
		}
		else{
			$status .= "ERROR: image not edit, ".$this->conn->error."SQL: ".$sql;
		}
		return $status;
	}
	
	public function addImage($imgArr){
		
	}

	public function deleteImage($imgIdDel, $removeImg){
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = "";
		$sqlQuery = "SELECT ti.url, ti.thumbnailurl, ti.originalurl ".
			"FROM images ti WHERE ti.imgid = ".$imgIdDel;
		$result = $this->conn->Query($sqlQuery);
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
		$this->setPrimaryImageSort($this->tid);
		return $status;
	}
	
	private function setPrimaryImageSort($subjectTid){
		$sql1 = "SELECT count(ti.imgid) AS reccnt FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			WHERE ts.taxauthid = 1 AND ti.SortSequence = 1 AND ts.tidaccepted = ".$subjectTid;
		//echo $sql1;
		$result = $this->conn->query($sql1);
		if($row = $result->fetch_object()){
			if($row->reccnt == 0){
				$sql2 = "UPDATE images ti2 INNER JOIN (SELECT ti.imgid, ti.sortsequence FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid
					WHERE ((ts.taxauthid = 1) AND (ts.tidaccepted=".$subjectTid.")) ORDER BY ti.SortSequence LIMIT 1) innertab ON ti2.imgid = innertab.imgid 
					SET ti2.SortSequence = 1";
				//echo $sql2;
				$this->conn->query($sql2);
			}
		}
		$result->close();
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

