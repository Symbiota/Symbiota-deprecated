<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceDownloadManager{
	
	private $conn;
	private $headerArr = array();
	private $securityArr = array();
	private $canReadRareSpp = false;
	private $schemaType = 'symbiota';
	private $delimiter = ',';
	private $charSetSource = '';
	private $charSetOut = '';
	private $sql = '';
 	private $sqlWhere = '';
 	private $conditionArr = array();
 	
	private $buFileName;
	private $buFilePath;

	private $errorArr = array();

 	public function __construct(){
		global $userRights, $isAdmin, $charset;
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
		
		$this->setUploadPath();

		//Create file pathName
		$this->buFileName = 'symbdl_'.time();

		$this->securityArr = Array("locality","locationRemarks","minimumElevationInMeters","maximumElevationInMeters","verbatimElevation",
			"decimalLatitude","decimalLongitude","geodeticDatum","coordinateUncertaintyInMeters","footprintWKT","coordinatePrecision",
			"verbatimCoordinates","verbatimCoordinateSystem","georeferenceRemarks",
			"verbatimLatitude","verbatimLongitude","habitat");

		if($isAdmin || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$this->canReadRareSpp = true;
		}
		//Character set
		$this->charSetSource = strtolower($charset);
		$this->charSetOut = $charset;
	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
		//if($this->zipArchive) $this->zipArchive->close();
	}

	public function downloadSpecimens(){
    	$fileName = $this->getFileName();
		header ('Content-Type: '.$this->getContentType());
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$sql = $this->getSql();
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
    		$outstream = fopen("php://output", "w");
    		if($this->delimiter == ","){
				fputcsv($outstream, $this->headerArr);
    		}
    		else{
				fwrite($outstream, implode($this->delimiter,$this->headerArr));
    		}
			while($row = $result->fetch_assoc()){
				$this->stripSensitiveFields($row);
				if($this->schemaType == 'dwc'){
					unset($row["localitySecurity"]);
					unset($row["collid"]);
				}
				$this->encodeArr($row);
	    		if($this->delimiter == ","){
					fputcsv($outstream, $row);
	    		}
	    		else{
					fwrite($outstream, implode($this->delimiter,$row));
	    		}
			}
			fclose($outstream);
		}
		else{
			echo "Recordset is empty.\n";
		}
        if($result) $result->close();
	}

	public function downloadDarwinCoreXml(){
		$this->writeXmlFile($this->dwcSql);
    }

	public function downloadDwca(){
		include_once($serverRoot.'/classes/DwcArchiverOccurrence.php');

		$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
		$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
		$cond = array_key_exists("cond",$_REQUEST)?$_REQUEST["cond"]:'';
		$collType = array_key_exists("colltype",$_REQUEST)?$_REQUEST["colltype"]:'specimens';
		$includeDets = array_key_exists("dets",$_REQUEST)?$_REQUEST["dets"]:1;
		$includeImgs = array_key_exists("imgs",$_REQUEST)?$_REQUEST["imgs"]:1;
		
		if($collid){
			$dwcaHandler = new DwcArchiverOccurrence();
			
			$dwcaHandler->setSilent(1);
			$dwcaHandler->setFileName('webreq');
			$dwcaHandler->setCollArr($collid,$collType);
			if($cond) $dwcaHandler->setConditionStr($cond);
		
			$archiveFile = $dwcaHandler->createDwcArchive($includeDets, $includeImgs, 1);
		
			if($archiveFile){
				//ob_start();
				header('Content-Description: DwC-A File Transfer');
				header('Content-Type: application/zip');
				header('Content-Disposition: attachment; filename='.basename($archiveFile));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: ' . filesize($archiveFile));
				ob_clean();
				flush();
				//od_end_clean();
				readfile($archiveFile);
				unlink($archiveFile);
				exit;
			}
			else{
				header('Content-Description: DwC-A File Transfer Error');
				header('Content-Type: text/plain');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				echo 'Error: unable to create archive';
			}
		}
		else{
			header('Content-Description: DwC-A File Transfer Error');
			header('Content-Type: text/plain');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			echo 'Error: collectoin identifier is not defined';
		}		
	}

	public function dlCollectionBackup($collId){
		$status = true;
		if($collId){
			$contentType = 'application/zip';
			$zipArchive = null;
			if(class_exists('ZipArchive')){
				$zipArchive = new ZipArchive;
				$zipArchive->open($this->buFilePath.$this->buFileName.'.zip', ZipArchive::CREATE);
			}
			else{
				$this->errorArr[] = 'ERROR: ZipArchive not supported. <br/>';
				$contentType = 'text/html; charset='.$this->charSetOut;
			}

    		//If zip archive can be created, the occurrences, determinations, and image records will be added to single archive file
    		//If not, then a CSV file containing just occurrence records will be returned
    		$fileName = $this->buFilePath.$this->buFileName;
    		$specFH = fopen($fileName.'_spec.csv', "w");
	    	//Output header 
    		$headerStr = 'occid,dbpk,basisOfRecord,catalogNumber,otherCatalogNumbers,ownerInstitutionCode, '.
				'family,scientificName,sciname,tidinterpreted,genus,specificEpithet,taxonRank,infraspecificEpithet,scientificNameAuthorship, '.
				'taxonRemarks,identifiedBy,dateIdentified,identificationReferences,identificationRemarks,identificationQualifier, '.
				'typeStatus,recordedBy,recordNumber,associatedCollectors,eventDate,year,month,day,startDayOfYear,endDayOfYear, '.
				'verbatimEventDate,habitat,substrate,fieldNotes,fieldNumber,occurrenceRemarks,informationWithheld,associatedOccurrences, '.
				'dataGeneralizations,associatedTaxa,dynamicProperties,verbatimAttributes,reproductiveCondition, '.
				'cultivationStatus,establishmentMeans,lifeStage,sex,individualCount,samplingProtocol,preparations, '.
    			'country,stateProvince,county,municipality, '.
				'locality,localitySecurity,localitySecurityReason,decimalLatitude,decimalLongitude,geodeticDatum, '.
				'coordinateUncertaintyInMeters,footprintWKT,verbatimCoordinates,georeferencedBy,georeferenceProtocol,georeferenceSources, '.
				'georeferenceVerificationStatus,georeferenceRemarks,minimumElevationInMeters,maximumElevationInMeters,verbatimElevation, '.
				'previousIdentifications,disposition,modified,language,processingstatus,recordEnteredBy,duplicateQuantity, '.
				'labelProject,dateLastModified ';
			fputcsv($specFH, explode(',',$headerStr));
			//Query and output values
    		$sql = 'SELECT '.$headerStr.
    			' FROM omoccurrences '.
    			'WHERE collid = '.$collId;
    		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
				while($r = $rs->fetch_assoc()){
					$this->encodeArr($r);
					fputcsv($specFH, $r);
				}
    			$rs->close();
    		}
    		fclose($specFH);
    		$localFile = '';

    		if($zipArchive){
	    		//Add occurrence file and then rename to 
				$zipArchive->addFile($fileName.'_spec.csv');
				$zipArchive->renameName($fileName.'_spec.csv','occurrences.csv');

				//Add determinations
				$detFH = fopen($fileName.'_det.csv', "w");
				fputcsv($detFH, Array('detid','occid','sciname','scientificNameAuthorship','identifiedBy','d.dateIdentified','identificationQualifier','identificationReferences','identificationRemarks','sortsequence'));
				//Add determination values
				$sql = 'SELECT d.detid,d.occid,d.sciname,d.scientificNameAuthorship,d.identifiedBy,d.dateIdentified, '.
					'd.identificationQualifier,d.identificationReferences,d.identificationRemarks,d.sortsequence '.
					'FROM omoccurdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid '.
					'WHERE o.collid = '.$collId;
				//echo $sql;
	    		if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_row()){
						fputcsv($detFH, $r);
					}
	    			$rs->close();
	    		}
    			fclose($detFH);
				$zipArchive->addFile($fileName.'_det.csv');
	    		$zipArchive->renameName($fileName.'_det.csv','determinations.csv');
	    		
				//Add image urls
	    		$imgFH = fopen($fileName.'_img.csv', "w");
				fputcsv($imgFH, Array('imgid','occid','url','thumbnailurl','originalurl','caption','notes'));
				$sql = 'SELECT i.imgid,i.occid,i.url,i.thumbnailurl,i.originalurl,i.caption,i.notes '.
					'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
					'WHERE o.collid = '.$collId;
				//echo $sql;
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_row()){
						fputcsv($imgFH, $r);
					}
					$rs->close();
	    		}
    			fclose($imgFH);
				$zipArchive->addFile($fileName.'_img.csv');
	    		$zipArchive->renameName($fileName.'_img.csv','images.csv');
				$localFile = $this->buFilePath.$this->buFileName.'.zip';
				$zipArchive->close();
				unlink($fileName.'_spec.csv');
				unlink($fileName.'_det.csv');
				unlink($fileName.'_img.csv');
			}
			else{
				$this->errorArr[] = 'ERROR: Only the occurrence file will be exported. <br/>';
				$contentType = 'text/html; charset='.$charset;
				$localFile = $this->buFilePath.$this->buFileName.'_spec.csv';
			}
			if(file_exists($localFile)){
				header('Content-Description: Collection Backup');
				header('Content-Type: '.$contentType);
				header('Content-Disposition: attachment; filename='.basename($localFile));
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				header('Content-Length: '.filesize($localFile));
				ob_clean();
				flush();
				readfile($localFile);
				unlink($localFile);
			}
			else{
				$status = false;
			}
		}
		return $status;
	}

	public function downloadGeorefCsv(){
    	global $userRights;
		$sql = "SELECT o.DecimalLatitude, o.DecimalLongitude, o.GeodeticDatum, o.CoordinateUncertaintyInMeters, o.footprintWKT, ". 
			"o.GeoreferenceProtocol, o.GeoreferenceSources, o.GeoreferenceVerificationStatus, o.GeoreferenceRemarks, ".
			"c.CollectionName, c.institutioncode, o.occid ".
			"FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) ";
		//if(strpos($this->sqlWhere,'sol.clid')) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		if(strpos($this->sqlWhere,'sol.clid')) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		$sql .= $this->sqlWhere;
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
    	$fileName = $this->getFileName();
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		//echo $sql;
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
    		$outstream = fopen("php://output", "w");
			$headArr = array("DecimalLatitude","DecimalLongitude","GeodeticDatum","CoordinateUncertaintyInMeters","footprintWKT",
				"GeoreferenceProtocol","GeoreferenceSources","GeoreferenceVerificationStatus","GeoreferenceRemarks",
				"CollectionName","institutioncode","occid");
			fputcsv($outstream, $headArr);
			while($row = $result->fetch_row()){
				fputcsv($outstream, $row);
			}
			fclose($outstream);
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
            	"CONCAT_WS(' ',t.unitind2,t.unitname2) AS specificEpithet, t.unitind3, t.unitname3, t.author ".
                "FROM ((omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid) INNER JOIN taxa t ON ts.TidAccepted = t.Tid) ";
			//if(array_key_exists("sol.clid",$this->sqlWhere)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			if(strpos($this->sqlWhere,'sol.clid')) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
			$sql .= $this->sqlWhere."AND t.RankId > 140 AND (ts.taxauthid = ".$taxonFilterCode.") ORDER BY ts.family, t.SciName ";
        }
        else{
			$sql = 'SELECT DISTINCT IFNULL(o.family,"not entered") AS family, o.sciname, CONCAT_WS(" ",t.unitind1,t.unitname1) AS genus, '.
            	'CONCAT_WS(" ",t.unitind2,t.unitname2) AS specificEpithet, t.unitind3, t.unitname3, t.author '.
				'FROM (omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid) ';
			//if(array_key_exists("sol.clid",$this->sqlWhere)) $sql .= 'INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ';
			if(strpos($this->sqlWhere,'sol.clid')) $sql .= 'INNER JOIN fmvouchers sol ON o.occid = sol.occid ';
			$sql .= $this->sqlWhere.'AND o.SciName NOT LIKE "%aceae" AND o.SciName NOT IN ("Plantae","Polypodiophyta") '.
                'ORDER BY IFNULL(o.family,"not entered"), o.SciName ';
        }
		//Output checklist
    	$fileName = $this->getFileName();
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		//echo $sql;
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
    		$outstream = fopen("php://output", "w");
			$headArr = array("Family","ScientificName","Genus","SpecificEpithet","TaxonRank","InfraSpecificEpithet","ScientificNameAuthorship");
			fputcsv($outstream, $headArr);
			while($row = $result->fetch_assoc()){
				if($row['sciname']) fputcsv($outstream, $row);
			}
			fclose($outstream);
		}
		else{
			echo "Recordset is empty.\n";
		}
        $result->close();
    }

/*
    private function writeTextFile($sql){
    	global $isAdmin;
    	
		$this->downloadPath .= ".txt";
		$this->downloadUrl .= ".txt";

		$fh = fopen($this->downloadPath, 'w') or die("can't open file");
		$result = $this->conn->query($sql);
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
						fwrite($fh, "Protected\t");
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
    }
     private function writeXmlFile($sql){
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
    }*/

	private function setUploadPath(){
		$tPath = $GLOBALS["serverRoot"];
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\') $tPath .= '/';
		$tPath .= "temp/";
		if(file_exists($tPath."downloads/")){
			$tPath .= "downloads/";
		}
		//echo $this->buFilePath;
		$this->buFilePath = $tPath;
	}

	//General setter and getters
	public function setSchemaType($t){
		$this->schemaType = $t;
	}

	public function setDelimiter($d){
		if($d == 'tab' || $d == "\t"){
			$this->delimiter = "\t";
		}
		elseif($d == 'comma' || $d == ','){
			$this->delimiter = ",";
		}
		else{
			$this->delimiter = $d;
		}
	}
	
	private function getContentType(){
		if($this->delimiter == "\t"){
			return 'text/html; charset='.$this->charSetOut;
		}
		elseif($this->delimiter == 'comma' || $this->delimiter == ','){
			return 'text/csv; charset='.$this->charSetOut;
		}
		return 'text/html; charset='.$this->charSetOut;
	}
	
	public function setCharSetOut($cs){
		if($cs == 'iso-8859-1' || $cs == 'utf-8'){
			$this->charSetOut = $cs;
		}
	}

	public function getErrorArr(){
		return $this->errorArr;
	}
	
	public function addCondition($c){
		$this->conditionArr[] = $this->cleanInStr($c);
	}
	
	public function setSqlWhere($sqlStr){
		$this->sqlWhere = $sqlStr;
	}

	private function getSql($isDwc = false){
		$sql = '';
		if($this->schemaType == 'dwc'){
			$sql = 'SELECT IFNULL(o.institutionCode,c.institutionCode) AS institutionCode, IFNULL(o.collectionCode,c.collectionCode) AS collectionCode, '.
				'o.basisOfRecord, o.catalogNumber, o.otherCatalogNumbers, o.occurrenceId, '.
				'o.family, o.sciname AS scientificName, IFNULL(t.author,o.scientificNameAuthorship) AS scientificNameAuthorship, '.
				'IFNULL(CONCAT_WS(" ",t.unitind1,t.unitname1),o.genus) AS genus, IFNULL(CONCAT_WS(" ",t.unitind2,t.unitname2),o.specificEpithet) AS specificEpithet, '.
				'IFNULL(t.unitind3,o.taxonRank) AS taxonRank, IFNULL(t.unitname3,o.infraspecificEpithet) AS infraspecificEpithet, '.
				"o.identifiedBy, o.dateIdentified, o.identificationReferences, o.identificationRemarks, o.identificationQualifier, ".
				"o.typeStatus, o.recordedBy, o.recordNumber, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, ".
				"o.verbatimEventDate, CONCAT_WS('; ',o.habitat, o.substrate) AS habitat, o.fieldNumber, CONCAT_WS('; ',o.occurrenceRemarks,o.verbatimAttributes) AS occurrenceRemarks, ".
				"o.dynamicProperties, o.associatedTaxa, o.reproductiveCondition, o.establishmentMeans, ".
				"o.lifeStage, o.sex, o.individualCount, o.samplingProtocol, o.preparations, ".
				"o.country, o.stateProvince, o.county, o.municipality, o.locality, o.decimalLatitude, o.decimalLongitude, ".
				"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.footprintWKT, o.verbatimCoordinates, ".
				"o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, ".
				"o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, ".
				"o.disposition, IFNULL(o.modified,o.datelastmodified) AS modified, o.language, c.rights, c.rightsHolder, c.accessRights, o.occid, o.collid, o.localitySecurity ".
				'FROM (omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid) '.
				'INNER JOIN guidoccurrences g ON o.occid = g.occid '.
				'LEFT JOIN taxa t ON o.tidinterpreted = t.TID ';
			$this->headerArr = array("institutionCode","collectionCode","basisOfRecord","catalogNumber","otherCatalogNumbers","occurrenceId",
				"family","scientificName","scientificNameAuthorship","genus","specificEpithet","taxonRank","infraspecificEpithet",
				"identifiedBy","dateIdentified","identificationReferences","identificationRemarks","identificationQualifier",
				"typeStatus","recordedBy","recordNumber","eventDate","year","month","day","startDayOfYear","endDayOfYear",
				"verbatimEventDate","habitat","fieldNumber","occurrenceRemarks",
				"dynamicProperties","associatedTaxa","reproductiveCondition","establishmentMeans",
				"lifeStage","sex","individualCount","samplingProtocol","preparations",
				"country","stateProvince","county","municipality","locality","decimalLatitude","decimalLongitude",
		 		"geodeticDatum","coordinateUncertaintyInMeters","footprintWKT","verbatimCoordinates",
				"georeferencedBy","georeferenceProtocol","georeferenceSources","georeferenceVerificationStatus",
				"georeferenceRemarks","minimumElevationInMeters","maximumElevationInMeters","verbatimElevation",
				"disposition","modified","language","rights","rightsHolder","accessRights","symbiotaId");
		}
		else{
			$sql = 'SELECT IFNULL(o.institutionCode,c.institutionCode) AS institutionCode, IFNULL(o.collectionCode,c.collectionCode) AS collectionCode, '.
				'o.basisOfRecord, o.catalogNumber, o.otherCatalogNumbers, o.occurrenceId, '.
				'o.family, o.sciname, IFNULL(t.author,o.scientificNameAuthorship) AS scientificNameAuthorship, '.
				'IFNULL(CONCAT_WS(" ",t.unitind1,t.unitname1),o.genus) AS genus, IFNULL(CONCAT_WS(" ",t.unitind2,t.unitname2),o.specificEpithet) AS specificEpithet, '.
				'IFNULL(t.unitind3,o.taxonRank) AS taxonRank, IFNULL(t.unitname3,o.infraspecificEpithet) AS infraspecificEpithet, '.
				'o.identifiedBy, o.dateIdentified, o.identificationReferences, '.
				'o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.associatedCollectors, '.
				"o.recordNumber, o.eventDate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, ".
				"o.verbatimEventDate, o.habitat, o.substrate, o.fieldNumber, o.occurrenceRemarks, o.verbatimAttributes, o.dynamicProperties, ".
				"o.associatedTaxa, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, ".
	 			"o.lifeStage, o.sex, o.individualCount, o.samplingProtocol, o.preparations, ".
				"o.country, o.stateProvince, o.county, o.municipality, o.locality, o.decimalLatitude, o.decimalLongitude, ".
				"o.geodeticDatum, o.coordinateUncertaintyInMeters, o.footprintWKT, o.verbatimCoordinates, ".
				"o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, ".
				"o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, ".
				"o.disposition, o.duplicateQuantity, IFNULL(o.modified,o.datelastmodified) AS modified, o.language, ".
				"c.rights, c.rightsHolder, c.accessRights, o.localitySecurity, o.collid, o.occid ".
	            'FROM (omcollections c INNER JOIN omoccurrences o ON c.CollID = o.CollID) '.
				'INNER JOIN guidoccurrences g ON o.occid = g.occid '.
				'LEFT JOIN taxa t ON o.tidinterpreted = t.TID ';
			$this->headerArr = array("institutionCode","collectionCode","basisOfRecord","catalogNumber","otherCatalogNumbers","occurrenceId",
				"family","scientificName","scientificNameAuthorship","genus","specificEpithet","taxonRank","infraspecificEpithet",
				"identifiedBy","dateIdentified","identificationReferences",
				"identificationRemarks","identificationQualifier","typeStatus","recordedBy","associatedCollectors",
				"recordNumber","eventDate","year","month","day","startDayOfYear","endDayOfYear",
		 		"verbatimEventDate","habitat","substrate","fieldNumber","occurrenceRemarks","verbatimAttributes","dynamicproperties",
				"associatedTaxa","reproductiveCondition","cultivationStatus","establishmentMeans",
	 			"lifeStage", "sex", "individualCount", "samplingProtocol", "preparations",
				"country","stateProvince","county","municipality","locality","decimalLatitude","decimalLongitude",
		 		"geodeticDatum","coordinateUncertaintyInMeters","footprintWKT","verbatimCoordinates",
				"georeferencedBy","georeferenceProtocol","georeferenceSources","georeferenceVerificationStatus",
				"georeferenceRemarks","minimumElevationInMeters","maximumElevationInMeters","verbatimElevation",
		 		"disposition","duplicatequantity","modified","language","rights","rightsHolder","accessRights",
		 		"localitySecurity","collId","symbiotaId");
		}

		//if(array_key_exists("sol.clid",$this->sqlWhere)) $this->sqlFrag .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		if(strpos($this->sqlWhere,'sol.clid')) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		$sql .= $this->sqlWhere;
		$sql .= "ORDER BY o.collid";
		return $sql;
	}

	private function getFileName(){
		global $defaultTitle;
    	$retStr = '';
		$fileName = str_replace(Array(".",":"),"",$defaultTitle);
		if(strlen($fileName) > 15){
			if(stripos($fileName,'the ') === 0) $fileName = substr($fileName,4);
			if($p = strpos($fileName,'(')) $fileName = substr($filename,0,$p);
			if(strpos($fileName,' ')){
				$nameArr = explode(" ",trim($fileName));
				foreach($nameArr as $v){
					$retStr .= substr($v,0,1);
				}
			}
			else{
				$retStr = substr($fileName,0,15);
			}
		}
		$retStr = str_replace(" ","",$retStr);
		if(!$retStr){
			$retStr = "symbiota";
		}
		$retStr .= "_occur_".time();
		//Set extension
		if($this->delimiter=="\t"){
			$retStr .= ".tab";
		}
		elseif($this->delimiter==','){
			$retStr .= ".csv";
		}
		else{
			$retStr .= ".txt";
		}
		return $retStr;
	}

	public function getCollectionMetadata($collid){
		$retArr = array();
		if(is_numeric($collid)){
			$sql = 'SELECT institutioncode, collectioncode, collectionname, managementtype '.
				'FROM omcollections '.
				'WHERE collid = '.$collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['instcode'] = $r->institutioncode;
				$retArr['collcode'] = $r->collectioncode;
				$retArr['collname'] = $r->collectionname;
				$retArr['manatype'] = $r->managementtype;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getProcessingStatusList($collid = 0){
		$psArr = array();
		$sql = 'SELECT DISTINCT processingstatus FROM omoccurrences ';
		if($collid){
			$sql .= 'WHERE collid = '.$collid;
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->processingstatus) $psArr[] = $r->processingstatus;
		}
		$rs->free();
		//Special sort
		$templateArr = array('unprocessed','unprocessed-nlp','pending duplicate','stage 1','stage 2','stage 3','pending review','reviewed');
		//Get all active processing statuses and then merge all extra statuses that may exists for one reason or another
		return array_merge(array_intersect($templateArr,$psArr),array_diff($psArr,$templateArr));
	}

	//Misc functions
	private function stripSensitiveFields(&$row){
    	global $userRights;
		if(!$this->canReadRareSpp && $row["localitySecurity"] == 1 
			&& (!array_key_exists("CollEditor", $userRights) || !in_array($row["collid"],$userRights["CollEditor"]))
			&& (!array_key_exists("RareSppReader", $userRights) || !in_array($row["collid"],$userRights["RareSppReader"]))){
			$row["habitat"] = 'Protected';
			$row["locality"] = 'Protected';
			$row["decimalLatitude"] = 'Protected';
			$row["decimalLongitude"] = 'Protected';
			$row["geodeticDatum"] = 'Protected';
			$row["coordinateUncertaintyInMeters"] = 'Protected';
			$row["footprintWKT"] = 'Protected';
			$row["verbatimCoordinates"] = 'Protected';
			$row["georeferencedBy"] = 'Protected';
			$row["georeferenceProtocol"] = 'Protected';
			$row["georeferenceSources"] = 'Protected';
			$row["georeferenceVerificationStatus"] = 'Protected';
			$row["georeferenceRemarks"] = 'Protected';
			$row["minimumElevationInMeters"] = 'Protected';
			$row["maximumElevationInMeters"] = 'Protected';
			$row["verbatimElevation"] = 'Protected';
		}
		return $row;
	}

	private function encodeArr(&$inArr){
		if($this->charSetSource && $this->charSetOut != $this->charSetSource){
			foreach($inArr as $k => $v){
				$inArr[$k] = $this->encodeStr($v);
			}
		}
	}

	private function encodeStr($inStr){
		$retStr = $inStr;
		if($this->charSetSource){
			if($this->charSetOut == 'utf-8' && $this->charSetSource == 'iso-8859-1'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif($this->charSetOut == "iso-8859-1" && $this->charSetSource == 'utf-8'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
		}
		return $retStr;
	}

	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = preg_replace('/\s\s+/', ' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>