<?php
/*
 * Created on 5 May 2009
 * @author  E. Gilbert: egbot@asu.edu
 */
include_once("CollectionManager.php");

class DownloadManager extends CollectionManager{
	
 	private $securityArr = Array();
 	private $dwcSql = "";
	//private $downloadUrl = ""; 
	//private $downloadPath = "";
	//private $fileName = "";
    
 	public function __construct(){
 		parent::__construct();
		$this->securityArr = Array("Locality","locationRemarks","MinimumElevationInMeters","MaximumElevationInMeters","VerbatimElevation",
			"DecimalLatitude","DecimalLongitude","GeodeticDatum","CoordinateUncertaintyInMeters","VerbatimCoordinates","verbatimCoordinateSystem",
			"VerbatimLatitude","VerbatimLongitude","Habitat");
 		$this->dwcSql = "SELECT o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, o.ownerInstitutionCode, o.family, ".
 			"o.sciname AS scientificName, o.genus, o.specificEpithet, o.taxonRank, o.infraspecificEpithet, o.scientificNameAuthorship, ".
 			"o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, o.identificationRemarks, o.identificationQualifier, ".
 			"o.typeStatus, o.recordedBy, o.recordNumber, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, ".
	 		"o.verbatimEventDate, o.habitat, o.fieldNotes, CONCAT_WS('; ',occurrenceRemarks,attributes) AS occurrenceRemarks, ".
 			"o.associatedOccurrences, o.associatedTaxa, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, ".
 			"o.stateProvince, o.county, o.municipality, o.locality, o.localitySecurity, o.decimalLatitude, o.decimalLongitude, ".
	 		"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, ".
 			"o.verbatimCoordinateSystem, o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, ".
 			"o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.previousIdentifications, ".
	 		"o.disposition, o.modified, o.language ".
            "FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $this->dwcSql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
 		$this->dwcSql .= $this->getSqlWhere();
		$this->dwcSql .= "ORDER BY c.CollectionCode, o.SciName";
 	}

 	public function downloadDarwinCoreText(){
		$this->writeTextFile($this->dwcSql);
    }

	public function downloadDarwinCoreXml(){
		$this->writeXmlFile($this->dwcSql);
    }

	public function downloadSymbiotaText(){
		$sql = "SELECT o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, o.ownerInstitutionCode, o.family, ".
			"t.SciName AS sciNameInterpreted, o.sciname, o.genus, o.specificEpithet, o.taxonRank, o.infraspecificEpithet, ".
			"o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, ".
			"o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.CollectorFamilyName, ".
			"o.CollectorInitials, o.associatedCollectors, o.recordNumber, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, ".
			"o.endDayOfYear, o.verbatimEventDate, o.habitat, o.fieldNotes, CONCAT_WS('; ',occurrenceRemarks,attributes) AS occurrenceRemarks, ".
			"o.associatedOccurrences, o.associatedTaxa, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, ".
			"o.country, o.stateProvince, o.county, o.municipality, o.locality, o.localitySecurity, o.decimalLatitude, o.decimalLongitude, ".
			"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, ".
			"o.verbatimCoordinateSystem, o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, ".
			"o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.previousIdentifications, ".
			"o.disposition, o.modified, o.language ".
            "FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) ".
			"LEFT JOIN taxa t ON o.tidinterpreted = t.TID ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		$sql .= $this->getSqlWhere();
		$sql .= "ORDER BY c.CollectionCode, o.SciName";
		//echo $sql;
		$this->writeTextFile($sql);
    }

	public function downloadGeorefText(){
        
		$sql = "SELECT o.DecimalLatitude, o.DecimalLongitude, o.GeodeticDatum, o.CoordinateUncertaintyInMeters, ". 
			"o.GeoreferenceProtocol, o.GeoreferenceSources, o.GeoreferenceVerificationStatus, o.GeoreferenceRemarks, ".
			"c.CollectionName, c.CollectionCode, o.occurrenceID, o.DBPK, o.LocalitySecurity ".
		"FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		$sql .= $this->getSqlWhere();
		$sql .= "ORDER BY c.CollectionCode, o.ScientificName";
		//echo $sql;
		$this->writeTextFile($sql);
    }
    
    public function downloadChecklistText($taxonFilterCode){
		$sql = "";
		if($taxonFilterCode){
            $sql = "SELECT DISTINCT ts.family, t.sciname, CONCAT_WS(' ',t.unitind1,t.unitname1) AS genus, ".
            	"CONCAT_WS(' ',t.unitind2,t.unitname2) AS epithet, t.unitind3 AS infrarank, t.unitname3 AS infraepithet, t.author ".
                "FROM ((omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid) INNER JOIN taxa t ON ts.TidAccepted = t.Tid) ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
            $sql .= $this->getSqlWhere()."AND t.RankId > 140 AND ts.taxauthid = ".$taxonFilterCode." ORDER BY ts.family, t.SciName ";
        }
        else{
			$sql = "SELECT DISTINCT o.family, o.SciName, o.Genus, IFNULL(o.SpecificEpithet,'') AS SpecificEpithet, ".
				"IFNULL(o.taxonRank,'') AS taxonRank, ".
				"IFNULL(o.InfraSpecificEpithet,'') AS InfraSpecificEpithet, IFNULL(t.author, o.scientificNameAuthorship) AS author ".
				"FROM (omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid) ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			$sql .= $this->getSqlWhere()."AND o.SciName NOT LIKE '%aceae' AND o.SciName NOT IN ('Plantae','Polypodiophyta') ".
                "ORDER BY o.family, o.SciName ";
        }
		$this->writeTextFile($sql);
    }

    private function writeTextFile($sql){
    	global $defaultTitle, $userRights, $isAdmin;
		$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10) $fileName = substr($fileName,0,10);
			$fileName = str_replace(".","",$fileName);
			$fileName = str_replace(" ","_",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= time().".txt";
    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/plain');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 
		$conn = $this->getConnection();
		//echo $sql;
		$result = $conn->query($sql);
		//Write column names out to file
		if($row = $result->fetch_assoc()){
	 		foreach($row as $colName => $value){
				echo $colName."\t";
	 		}
			echo "\n";
			
			//Write column values out to file
			do{
				foreach($row as $colName => $value){
					$localSecurity = (array_key_exists("LocalitySecurity",$row)?$row["LocalitySecurity"]:"1");
					if($isAdmin || $localSecurity == 1 || !in_array($colName,$this->securityArr) || in_array($row["CollectionCode"],$userRights)){
						echo $value."\t";
					}
					else{
						echo "Value Hidden\t";
					}
				}
				echo "\n";
			}while($row = $result->fetch_assoc());
		}
		else{
			echo "Recordset is empty.\n";
		}
        $result->close();
		$conn->close();
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