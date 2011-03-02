<?php
/*
 * Created on 5 May 2009
 * @author  E. Gilbert: egbot@asu.edu
 */
include_once("OccurrenceManager.php");

class OccurrenceDownloadManager extends OccurrenceManager{
	
 	private $conn;
	private $securityArr = Array();
 	private $dwcSql = "";
    
 	public function __construct(){
 		parent::__construct();
		$this->conn = MySQLiConnectionFactory::getCon("readonly");

 		$this->securityArr = Array("locality","locationRemarks","minimumElevationInMeters","maximumElevationInMeters","verbatimElevation",
			"decimalLatitude","decimalLongitude","geodeticDatum","coordinateUncertaintyInMeters","coordinatePrecision",
			"verbatimCoordinates","verbatimCoordinateSystem","georeferenceRemarks",
			"verbatimLatitude","verbatimLongitude","habitat");
 		$this->dwcSql = "SELECT o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, o.ownerInstitutionCode, o.family, ".
 			"o.sciname AS scientificName, o.genus, o.specificEpithet, o.taxonRank, o.infraspecificEpithet, o.scientificNameAuthorship, ".
 			"o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, o.identificationRemarks, o.identificationQualifier, ".
 			"o.typeStatus, o.recordedBy, o.recordNumber, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, ".
	 		"o.verbatimEventDate, o.habitat, o.fieldNotes, CONCAT_WS('; ',occurrenceRemarks,attributes) AS occurrenceRemarks, ".
 			"o.associatedOccurrences, o.associatedTaxa, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, ".
 			"o.stateProvince, o.county, o.municipality, o.locality, o.decimalLatitude, o.decimalLongitude, ".
	 		"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, ".
 			"o.verbatimCoordinateSystem, o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, ".
 			"o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.previousIdentifications, ".
	 		"o.disposition, o.modified, o.language, o.CollID, o.localitySecurity ".
            "FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $this->dwcSql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
 		$this->dwcSql .= $this->getSqlWhere();
		$this->dwcSql .= "ORDER BY c.institutioncode, o.SciName";
 	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function downloadDarwinCoreCsv(){
    	global $defaultTitle, $userRights, $isAdmin;
		$canReadRareSpp = false;
		if($isAdmin || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$canReadRareSpp = true;
		}
    	$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10){
				$nameArr = explode(" ",$fileName);
				$fileName = $nameArr[0];
			}
			$fileName = str_replace(Array("."," ",":"),"",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= "_occur_".time().".csv";
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 
		//echo $sql;
		$result = $this->conn->query($this->dwcSql);
		//Write column names out to file
		if($result){
			echo "\"basisOfRecord\",\"occurrenceID\",\"catalogNumber\",\"otherCatalogNumbers\",\"ownerInstitutionCode\",\"family\",".
 			"\"scientificName\",\"genus\",\"specificEpithet\",\"taxonRank\",\"infraspecificEpithet\",\"scientificNameAuthorship\",".
 			"\"taxonRemarks\",\"identifiedBy\",\"dateIdentified\",\"identificationReferences\",\"identificationRemarks\",\"identificationQualifier\",".
 			"\"typeStatus\",\"recordedBy\",\"recordNumber\",\"eventDate\",\"year\",\"month\",\"day\",\"startDayOfYear\",\"endDayOfYear\",".
	 		"\"verbatimEventDate\",\"habitat\",\"fieldNotes\",\"occurrenceRemarks\",".
 			"\"associatedOccurrences\",\"associatedTaxa\",\"reproductiveCondition\",\"cultivationStatus\",\"establishmentMeans\",\"country\",".
 			"\"stateProvince\",\"county\",\"municipality\",\"locality\",\"decimalLatitude\",\"decimalLongitude\",".
	 		"\"geodeticDatum\",\"coordinateUncertaintyInMeters\",\"coordinatePrecision\",\"locationRemarks\",\"verbatimCoordinates\",".
 			"\"verbatimCoordinateSystem\",\"georeferencedBy\",\"georeferenceProtocol\",\"georeferenceSources\",\"georeferenceVerificationStatus\",".
 			"\"georeferenceRemarks\",\"minimumElevationInMeters\",\"maximumElevationInMeters\",\"verbatimElevation\",\"previousIdentifications\",".
	 		"\"disposition\",\"modified\",\"language\"\n";
			
			while($row = $result->fetch_assoc()){
				$localSecurity = (array_key_exists("localitySecurity",$row)?$row["localitySecurity"]:0); 
				echo "\"".$row["basisOfRecord"]."\",\"".$row["occurrenceID"]."\",\"".$row["catalogNumber"]."\",\"".$row["otherCatalogNumbers"]."\",\"".$row["ownerInstitutionCode"].
					"\",\"".$row["family"]."\","."\"".$row["scientificName"]."\",\"".$row["genus"]."\",\"".$row["specificEpithet"]."\",\"".
					$row["taxonRank"]."\",\"".$row["infraspecificEpithet"]."\",\"".$row["scientificNameAuthorship"]."\",\"".
					$row["taxonRemarks"]."\",\"".$row["identifiedBy"]."\",\"".$row["dateIdentified"]."\",\"".$row["identificationReferences"]."\",\"".
					$row["identificationRemarks"]."\",\"".$row["identificationQualifier"]."\",\"".$row["typeStatus"]."\",\"".$row["recordedBy"]."\",\"".
					$row["recordNumber"]."\",".$row["eventDate"].",".$row["year"].",".$row["month"].",".$row["day"].",".$row["startDayOfYear"].",".
					$row["endDayOfYear"].",\"".$row["verbatimEventDate"]."\",\"".$this->cleanStr($row["habitat"])."\",\"".$this->cleanStr($row["fieldNotes"])."\",\"".
					$row["occurrenceRemarks"]."\",\"".$row["associatedOccurrences"]."\",\"".$row["associatedTaxa"]."\",\"".$row["reproductiveCondition"]."\",\"".
					$row["cultivationStatus"]."\",\"".$row["establishmentMeans"]."\",\"".$row["country"]."\",\"".$row["stateProvince"]."\",\"".
					$row["county"]."\",\"".$row["municipality"]."\",";
				if($canReadRareSpp || $localSecurity != 1 || (array_key_exists("RareSppReader", $userRights) && in_array($row["collid"],$userRights["RareSppReader"]))){
					echo "\"".$this->cleanStr($row["locality"])."\",".$row["decimalLatitude"].",".$row["decimalLongitude"].",\"".$row["geodeticDatum"]."\",".
					$row["coordinateUncertaintyInMeters"].",\"".$row["coordinatePrecision"]."\",\"".$row["locationRemarks"]."\",\"".
					$row["verbatimCoordinates"]."\",\"".$row["verbatimCoordinateSystem"]."\",\"".$row["georeferencedBy"]."\",\"".
					$row["georeferenceProtocol"]."\",\"".$row["georeferenceSources"]."\",\"".$row["georeferenceVerificationStatus"]."\",\"".
					$row["georeferenceRemarks"]."\",".$row["minimumElevationInMeters"].",".$row["maximumElevationInMeters"].",\"".$row["verbatimElevation"]."\",";
				}
				else{
					echo "\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",".
						"\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",".
						"\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",";
				}
				echo "\"".$row["previousIdentifications"]."\",\"".$row["disposition"]."\",".$row["modified"].",\"".$row["language"]."\"\n";
			}
		}
		else{
			echo "Recordset is empty.\n";
		}
        $result->close();
	}

	public function downloadDarwinCoreXml(){
		$this->writeXmlFile($this->dwcSql);
    }

	public function downloadSymbiotaCsv(){
    	global $defaultTitle, $userRights, $isAdmin;
		$sql = "SELECT c.institutionCode, c.collectionCode, o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, o.ownerInstitutionCode, o.family, ".
			"t.SciName AS sciNameInterpreted, o.sciname, o.genus, o.specificEpithet, o.taxonRank, o.infraspecificEpithet, ".
			"o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, ".
			"o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.associatedCollectors, o.recordNumber, ".
			"o.eventDate, o.year, o.month, o.day, o.startDayOfYear, ".
			"o.endDayOfYear, o.verbatimEventDate, o.habitat, o.fieldNotes, occurrenceRemarks, attributes, ".
			"o.associatedOccurrences, o.associatedTaxa, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, ".
			"o.country, o.stateProvince, o.county, o.municipality, o.locality, o.decimalLatitude, o.decimalLongitude, ".
			"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, ".
			"o.verbatimCoordinateSystem, o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, ".
			"o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.previousIdentifications, ".
			"o.disposition, o.modified, o.language, o.collid, o.localitySecurity ".
            "FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) ".
			"LEFT JOIN taxa t ON o.tidinterpreted = t.TID ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		$sql .= $this->getSqlWhere();
		$sql .= "ORDER BY c.institutioncode, o.SciName";
		//echo $sql;
		$canReadRareSpp = false;
		if($isAdmin || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$canReadRareSpp = true;
		}
    	$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10){
				$nameArr = explode(" ",$fileName);
				$fileName = $nameArr[0];
			}
			$fileName = str_replace(Array("."," ",":"),"",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= "_occur_".time().".csv";
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
			echo "\"institutionCode\",\"collectionCode\",\"basisOfRecord\",\"occurrenceID\",\"catalogNumber\",\"otherCatalogNumbers\",\"ownerInstitutionCode\",\"family\",".
 			"\"sciNameInterpreted\",\"scientificName\",\"genus\",\"specificEpithet\",\"taxonRank\",\"infraspecificEpithet\",".
 			"\"scientificNameAuthorship\",\"taxonRemarks\",\"identifiedBy\",\"dateIdentified\",\"identificationReferences\",".
 			"\"identificationRemarks\",\"identificationQualifier\",\"typeStatus\",\"recordedBy\",\"associatedCollectors\",".
 			"\"recordNumber\",\"eventDate\",\"year\",\"month\",\"day\",\"startDayOfYear\",\"endDayOfYear\",".
	 		"\"verbatimEventDate\",\"habitat\",\"fieldNotes\",\"occurrenceRemarks\",\"attributes\",".
 			"\"associatedOccurrences\",\"associatedTaxa\",\"reproductiveCondition\",\"cultivationStatus\",\"establishmentMeans\",\"country\",".
 			"\"stateProvince\",\"county\",\"municipality\",\"locality\",\"decimalLatitude\",\"decimalLongitude\",".
	 		"\"geodeticDatum\",\"coordinateUncertaintyInMeters\",\"coordinatePrecision\",\"locationRemarks\",\"verbatimCoordinates\",".
 			"\"verbatimCoordinateSystem\",\"georeferencedBy\",\"georeferenceProtocol\",\"georeferenceSources\",\"georeferenceVerificationStatus\",".
 			"\"georeferenceRemarks\",\"minimumElevationInMeters\",\"maximumElevationInMeters\",\"verbatimElevation\",\"previousIdentifications\",".
	 		"\"disposition\",\"modified\",\"language\",\"collid\",\"localitySecurity\"\n";
			
			while($row = $result->fetch_assoc()){
				$localSecurity = $row["localitySecurity"]; 
				echo "\"".$row["institutionCode"]."\",\"".$row["collectionCode"]."\",\"".$row["basisOfRecord"]."\",\"".
					$row["occurrenceID"]."\",\"".$row["catalogNumber"]."\",\"".$row["otherCatalogNumbers"]."\",\"".
					$row["ownerInstitutionCode"]."\",\"".$row["family"]."\",\"".$row["sciNameInterpreted"]."\",\"".
					$row["sciname"]."\",\"".$row["genus"]."\",\"".$row["specificEpithet"]."\",\"".
					$row["taxonRank"]."\",\"".$row["infraspecificEpithet"]."\",\"".$row["scientificNameAuthorship"]."\",\"".
					$row["taxonRemarks"]."\",\"".$row["identifiedBy"]."\",\"".$row["dateIdentified"]."\",\"".$row["identificationReferences"]."\",\"".
					$row["identificationRemarks"]."\",\"".$row["identificationQualifier"]."\",\"".$row["typeStatus"]."\",\"".$row["recordedBy"]."\",\"".$row["associatedCollectors"]."\",\"".
					$row["recordNumber"]."\",".$row["eventDate"].",".$row["year"].",".$row["month"].",".$row["day"].",".$row["startDayOfYear"].",".
					$row["endDayOfYear"].",\"".$row["verbatimEventDate"]."\",\"".$this->cleanStr($row["habitat"])."\",\"".$this->cleanStr($row["fieldNotes"])."\",\"".
					$row["occurrenceRemarks"]."\",\"".$row["attributes"]."\",\"".$row["associatedOccurrences"]."\",\"".$row["associatedTaxa"]."\",\"".$row["reproductiveCondition"]."\",\"".
					$row["cultivationStatus"]."\",\"".$row["establishmentMeans"]."\",\"".$row["country"]."\",\"".$row["stateProvince"]."\",\"".
					$row["county"]."\",\"".$row["municipality"]."\",";
				if($canReadRareSpp || $localSecurity != 1 || (array_key_exists("RareSppReader", $userRights) && in_array($row["collid"],$userRights["RareSppReader"]))){
					echo "\"".$this->cleanStr($row["locality"])."\",".$row["decimalLatitude"].",".$row["decimalLongitude"].",\"".$row["geodeticDatum"]."\",".
					$row["coordinateUncertaintyInMeters"].",\"".$row["coordinatePrecision"]."\",\"".$row["locationRemarks"]."\",\"".
					$row["verbatimCoordinates"]."\",\"".$row["verbatimCoordinateSystem"]."\",\"".$row["georeferencedBy"]."\",\"".
					$row["georeferenceProtocol"]."\",\"".$row["georeferenceSources"]."\",\"".$row["georeferenceVerificationStatus"]."\",\"".
					$row["georeferenceRemarks"]."\",".$row["minimumElevationInMeters"].",".$row["maximumElevationInMeters"].",\"".$row["verbatimElevation"]."\",";
				}
				else{
					echo "\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",".
						"\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",".
						"\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",\"Value Hidden\",";
				}
				echo "\"".$row["previousIdentifications"]."\",\"".$row["disposition"]."\",".$row["modified"].",\"".$row["language"]."\",\"".$row["collid"]."\",\"".$row["localitySecurity"]."\"\n";
			}
		}
		else{
			echo "Recordset is empty.\n";
		}
        $result->close();
    }

	public function downloadGeorefCsv(){
    	global $userRights, $defaultTitle;
		$sql = "SELECT o.DecimalLatitude, o.DecimalLongitude, o.GeodeticDatum, o.CoordinateUncertaintyInMeters, ". 
			"o.GeoreferenceProtocol, o.GeoreferenceSources, o.GeoreferenceVerificationStatus, o.GeoreferenceRemarks, ".
			"c.CollectionName, c.institutioncode, o.occurrenceID, o.occid ".
		"FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		$sql .= $this->getSqlWhere();
		$sql .= " AND o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL ";
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"])."))";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
		}
		$sql .= "ORDER BY c.institutioncode, o.ScientificName";
		//echo $sql;
		//Output checklist
    	$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10){
				$nameArr = explode(" ",$fileName);
				$fileName = $nameArr[0];
			}
			$fileName = str_replace(Array("."," ",":"),"",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= "_geopoints_".time().".csv";
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		//echo $sql;
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
			echo "\"DecimalLatitude\",\"DecimalLongitude\",\"GeodeticDatum\",\"CoordinateUncertaintyInMeters\",\"GeoreferenceProtocol\",\"GeoreferenceSources\",".
				"\"GeoreferenceVerificationStatus\",\"GeoreferenceRemarks\",\"CollectionName\",\"institutioncode\",\"occurrenceID\",\"occid\"\n";
			while($row = $result->fetch_row()){
				echo $row[0].",".$row[1].",\"".$row[2]."\",".$row[3].",\"".$row[4]."\",\"".$row[5]."\",\"".$row[6]."\",\"".$row[7]."\",\"".$row[8]."\",\"".$row[9]."\",\"".$row[10]."\",".$row[11]."\n";
			}
		}
		else{
			echo "Recordset is empty. Specimens may not have georeference points or the they may be species with protected locality information.\n";
		}
        $result->close();
    }
    
    public function downloadChecklistCsv($taxonFilterCode){
    	global $defaultTitle;
    	$sql = "";
		if($taxonFilterCode){
            $sql = "SELECT DISTINCT ts.family, t.sciname, CONCAT_WS(' ',t.unitind1,t.unitname1) AS genus, ".
            	"CONCAT_WS(' ',t.unitind2,t.unitname2) AS specificepithet, t.unitind3 AS infrarank, t.unitname3 AS infraspepithet, t.author ".
                "FROM ((omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid) INNER JOIN taxa t ON ts.TidAccepted = t.Tid) ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
            $sql .= $this->getSqlWhere()."AND t.RankId > 140 AND ts.taxauthid = ".$taxonFilterCode." ORDER BY ts.family, t.SciName ";
        }
        else{
			$sql = "SELECT DISTINCT o.family, o.sciname, o.genus, IFNULL(o.specificepithet,'') AS specificepithet, ".
				"IFNULL(o.taxonrank,'') AS infrarank, ".
				"IFNULL(o.infraspecificepithet,'') AS infraspepithet, IFNULL(t.author, o.scientificnameauthorship) AS author ".
				"FROM (omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid) ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			$sql .= $this->getSqlWhere()."AND o.SciName NOT LIKE '%aceae' AND o.SciName NOT IN ('Plantae','Polypodiophyta') ".
                "ORDER BY o.family, o.SciName ";
        }
		//Output checklist
    	$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10){
				$nameArr = explode(" ",$fileName);
				$fileName = $nameArr[0];
			}
			$fileName = str_replace(Array("."," ",":"),"",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= "_checklist_".time().".csv";
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		//echo $sql;
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
			echo "\"Family\",\"ScientificName\",\"Genus\",\"SpecificEpithet\",\"TaxonRank\",\"InfraspecificEpithet\",\"ScientificNameAuthorship\"\n";
			while($row = $result->fetch_row()){
				echo "\"".$row[0]."\",\"".$row[1]."\",\"".$row[2]."\",\"".$row[3]."\",\"".$row[4]."\",\"".$row[5]."\",\"".$row[6]."\"\n";
			}
		}
		else{
			echo "Recordset is empty.\n";
		}
        $result->close();
    }

    private function cleanStr($inStr){
    	$retStr = str_replace("\n"," ",$inStr);
    	$retStr = str_replace("\r"," ",$retStr);
    	$retStr = str_replace("\t"," ",$retStr);
		$retStr = str_replace("\"","'",$retStr);
    	return $retStr;
    }
    
/*
  
    private function writeTextFile($sql){
    	global $isAdmin;
		$conn = $this->getConnection();
    	
		$this->downloadPath .= ".txt";
		$this->downloadUrl .= ".txt";

		$fh = fopen($this->downloadPath, 'w') or die("can't open file");
		$result = $conn->query($sql);
		//Write column names out to file
		if($row = $result->fetch_assoc()){
	 		foreach($row as $colName => $value){
				fwrite($fh, $colName."\t");
	 		}
			fwrite($fh, "\n");
			
			//Write column values out to file
			do{
				foreach($row as $colName => $value){
					fwrite($fh, $colName."\t");
					$localSecurity = (array_key_exists("LocalitySecurity",$row)?$row("LocalitySecurity"):"1");
					if($isAdmin || $localSecurity == 1 || !in_array($colName,$this->securityArr) || in_array($row("CollectionCode"),$this->userRights)){
						fwrite($fh, $value."\t");
					}
					else{
						fwrite($fh, "Value Hidden\t");
					}
				}
				fwrite($fh, "\n");
			}while($row = $result->fetch_assoc());
		}
		else{
			fwrite($fh, "Recordset is empty.");
		}
		$fh->flush();
		$fw->close();
        $result->close();
		$conn->close();
    }
     private function writeXmlFile($sql){
		$conn = $this->getConnection();
		//$this->downloadPath .= ".xml";
		//$this->downloadUrl .= ".xml";
		
		header("Content-Type: text/html/force-download");
		header("Content-Disposition: attachment; filename='symbiotadownload".time().".xml'");
		$out = new XMLWriter();
		$out->openURI('php://output');
		$out->xmlwriter_start_document("1.0","ISO-8859-1");
		
		
		
		<?xml version="1.0" encoding="UTF-8"?>

$xw->startElementNS('ns0', 'approvePOrderResponse', 
	'http://PhpRESTAppLib/POrderApprovalHtIF');

<SimpleDarwinRecordSet
 xmlns="http://rs.tdwg.org/dwc/dwcrecord/"
 xmlns:dc="http://purl.org/dc/terms/"
 xmlns:dwc="http://rs.tdwg.org/dwc/terms/"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://rs.tdwg.org/dwc/dwcrecord/ http://rs.tdwg.org/dwc/xsd/tdwg_dwc_simple.xsd">
 <SimpleDarwinRecord>
  <dc:type>Taxon</dc:type>
		
xmlwriter_start_attribute($xml_resource , 'access_year');
xmlwriter_write_attribute($xml_resource, 'access_year' , '2008');
xmlwriter_end_attribute($xml_resource);

		

		StreamResult sr = new StreamResult(this.downloadPath);
            SAXTransformerFactory tf = (SAXTransformerFactory)SAXTransformerFactory.newInstance();

            TransformerHandler th = tf.newTransformerHandler();
            Transformer t = th.getTransformer();
            t.setOutputProperty(OutputKeys.ENCODING,"ISO-8859-1");
            th.setResult(sr);
            th.startDocument();

            AttributesImpl ai = new AttributesImpl();
            ai.clear();
            th.startElement("","","Document",ai);
            th.startElement("","","Specimens",ai);

            Statement st = con.createStatement();
            rs = st.executeQuery(sql);
            ResultSetMetaData rsmd = rs.getMetaData();
            int columnCnt = rsmd.getColumnCount();
            while(rs.next()){
                ai.clear();
                th.startElement("","","SpecimenRecord",ai);
                for(int x = 1;x <= columnCnt;++x){
                    String columnName = rsmd.getColumnName(x);
                    if(this.isAdmin || rs.getInt("LocalitySecurity") == 1 || !this.securityColumns.contains(columnName) || (this.userRights != null && this.userRights.contains(rs.getString("CollectionCode")))){
                        String outStr = rs.getString(x);
                        if(outStr != null && !outStr.equals("")){
                            char[] charArr = outStr.toCharArray();
                            ai.clear();
                            th.startElement("","",columnName,ai);
                            th.characters(charArr,0,charArr.length);
                            th.endElement("","",columnName);
                        }
                    }
                }
                th.endElement("","","SpecimenRecord");
            }

            th.endElement("","","Specimens");
            th.endElement("","","Document");
            th.endDocument();
            st.close();
            rs.close();
        }
        catch(SQLException sqle){
            System.out.println("DownloadCollections: writeXmlFile: sqle = " + sqle);
            System.out.println("SQL: " + sql);
        }
        catch(Exception e){
            System.out.println("DownloadCollections: writeXmlFile: e = " + e);
        }
        this.closeConnection();
        $result->close();
		$conn->close();
    }*/
}
?>