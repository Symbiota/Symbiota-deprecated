<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceEditorManager {

	private $conn;
	private $occurrenceMap = Array();
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getOccurArr($occid){
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
			"o.verbatimEventDate, o.habitat, o.fieldNotes, o.occurrenceRemarks, o.associatedOccurrences, o.associatedTaxa, ".
			"o.dynamicProperties, o.attributes, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, ".
			"o.stateProvince, o.county, o.municipality, o.locality, o.localitySecurity, o.decimalLatitude, o.decimalLongitude, ".
			"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, ".
			"o.verbatimCoordinateSystem, o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, ".
			"o.georeferenceVerificationStatus, o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, ".
			"o.verbatimElevation, o.previousIdentifications, o.disposition, o.modified, o.language, o.observeruid, o.dateLastModified ".
			"FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid ".
			"WHERE o.occid = ".$occid;
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
		$sql = "SELECT i.url, i.thumbnailurl, i.originalurl FROM images i ".
			"WHERE i.occid = ".$this->occurrenceMap["occid"]["value"]." ORDER BY i.sortsequence";
		$cnt = 0;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$this->occurrenceMap["images"]["url"] = $row->url;
			$this->occurrenceMap["images"]["tnurl"] = $row->thumbnailurl;
			$this->occurrenceMap["images"]["origurl"] = $row->originalurl;
			$cnt++;
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

