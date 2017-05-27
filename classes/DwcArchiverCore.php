<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverOccurrence.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverDetermination.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverImage.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverAttribute.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

class DwcArchiverCore extends Manager{

	private $ts;

	protected $collArr;
	private $customWhereSql;
	private $conditionSql;
 	private $conditionArr = array();
	private $condAllowArr;
	private $upperTaxonomy = array();

	private $targetPath;
	protected $serverDomain;

	private $schemaType = 'dwc';			//dwc, symbiota, backup
	private $limitToGuids = false;			//Limit output to only records with GUIDs
	private $extended = 0;
	private $delimiter = ',';
	private $fileExt = '.csv';
	private $occurrenceFieldArr = array();
	private $determinationFieldArr = array();
	private $imageFieldArr = array();
	private $attributeFieldArr = array();
	
	private $securityArr = array();
	private $includeDets = 1;
	private $includeImgs = 1;
	private $includeAttributes = 0;
	private $redactLocalities = 1;
	private $rareReaderArr = array();
	private $charSetSource = '';
	protected $charSetOut = '';

	private $geolocateVariables = array();
	
	public function __construct($conType='readonly'){
		parent::__construct(null,$conType);
		//Ensure that PHP DOMDocument class is installed
		if(!class_exists('DOMDocument')){
			exit('FATAL ERROR: PHP DOMDocument class is not installed, please contact your server admin');
		}
		$this->ts = time();
		if($this->verboseMode){
			$logFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/')."temp/logs/DWCA_".date('Y-m-d').".log";
			$this->setLogFH($logPath);
		}

		//Character set
		$this->charSetSource = strtoupper($GLOBALS['CHARSET']);
		$this->charSetOut = $this->charSetSource;
		
		$this->condAllowArr = array('catalognumber','othercatalognumbers','occurrenceid','family','sciname',
			'country','stateprovince','county','municipality','recordedby','recordnumber','eventdate',
			'decimallatitude','decimallongitude','minimumelevationinmeters','maximumelevationinmeters','datelastmodified','dateentered');
		
		$this->securityArr = array('eventDate','month','day','startDayOfYear','endDayOfYear','verbatimEventDate',
			'recordNumber','locality','locationRemarks','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation',
			'decimalLatitude','decimalLongitude','geodeticDatum','coordinateUncertaintyInMeters','footprintWKT',
			'verbatimCoordinates','georeferenceRemarks','georeferencedBy','georeferenceProtocol','georeferenceSources',
			'georeferenceVerificationStatus','habitat','informationWithheld');

		//ini_set('memory_limit','512M');
		set_time_limit(500);
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getOccurrenceCnt(){
		$retStr = 0;
		$this->applyConditions();
		$sql = DwcArchiverOccurrence::getSqlOccurrences($this->occurrenceFieldArr['fields'],$this->conditionSql,$this->getTableJoins(),false);
		if($sql){
			$sql = 'SELECT COUNT(o.occid) as cnt '.$sql;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retStr = $r->cnt;
			}
			$rs->free();
		}
		return $retStr;
	}

	public function setTargetPath($tp = ''){
		if($tp){
			$this->targetPath = $tp;
		}
		else{
			//Set to temp download path
			$tPath = $GLOBALS["tempDirRoot"];
			if(!$tPath){
				$tPath = ini_get('upload_tmp_dir');
			}
			if(!$tPath){
				$tPath = $GLOBALS["serverRoot"];
				if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
					$tPath .= '/';
				}
				$tPath .= "temp/";
			}
			if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
				$tPath .= '/';
			}
			if(file_exists($tPath."downloads")){
				$tPath .= "downloads/";
			}
			$this->targetPath = $tPath;
		}
	}
	
	public function setCollArr($collTarget, $collType = ''){
		$collTarget = $this->cleanInStr($collTarget);
		$collType = $this->cleanInStr($collType);
		$sqlWhere = '';
		if($collType == 'specimens'){
			$sqlWhere = '(c.colltype = "Preserved Specimens") ';
		}
		elseif($collType == 'observations'){
			$sqlWhere = '(c.colltype = "Observations" OR c.colltype = "General Observations") ';
		}
		if($collTarget){
			$sqlWhere .= ($sqlWhere?'AND ':'').'(c.collid IN('.$collTarget.')) ';
		}
		else{
			//Don't limit by collection id 
		}
		if($sqlWhere){
			$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.fulldescription, c.collectionguid, '.
				'IFNULL(c.homepage,i.url) AS url, IFNULL(c.contact,i.contact) AS contact, IFNULL(c.email,i.email) AS email, c.guidtarget, c.dwcaurl, '.
				'c.latitudedecimal, c.longitudedecimal, c.icon, c.managementtype, c.colltype, c.rights, c.rightsholder, c.usageterm, '.
				'i.address1, i.address2, i.city, i.stateprovince, i.postalcode, i.country, i.phone '.
				'FROM omcollections c LEFT JOIN institutions i ON c.iid = i.iid WHERE '.$sqlWhere;
			//echo 'SQL: '.$sql.'<br/>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collArr[$r->collid]['instcode'] = $r->institutioncode;
				$this->collArr[$r->collid]['collcode'] = $r->collectioncode;
				$this->collArr[$r->collid]['collname'] = $r->collectionname;
				$this->collArr[$r->collid]['description'] = $r->fulldescription;
				$this->collArr[$r->collid]['collectionguid'] = $r->collectionguid;
				$this->collArr[$r->collid]['url'] = $r->url;
				$this->collArr[$r->collid]['contact'] = $r->contact;
				$this->collArr[$r->collid]['email'] = $r->email;
				$this->collArr[$r->collid]['guidtarget'] = $r->guidtarget;
				$this->collArr[$r->collid]['dwcaurl'] = $r->dwcaurl;
				$this->collArr[$r->collid]['lat'] = $r->latitudedecimal;
				$this->collArr[$r->collid]['lng'] = $r->longitudedecimal;
				$this->collArr[$r->collid]['icon'] = $r->icon;
				$this->collArr[$r->collid]['colltype'] = $r->colltype;
				$this->collArr[$r->collid]['managementtype'] = $r->managementtype;
				$this->collArr[$r->collid]['rights'] = $r->rights;
				$this->collArr[$r->collid]['rightsholder'] = $r->rightsholder;
				$this->collArr[$r->collid]['usageterm'] = $r->usageterm;
				$this->collArr[$r->collid]['address1'] = $r->address1;
				$this->collArr[$r->collid]['address2'] = $r->address2;
				$this->collArr[$r->collid]['city'] = $r->city;
				$this->collArr[$r->collid]['state'] = $r->stateprovince;
				$this->collArr[$r->collid]['postalcode'] = $r->postalcode;
				$this->collArr[$r->collid]['country'] = $r->country;
				$this->collArr[$r->collid]['phone'] = $r->phone;
			}
			$rs->free();
		}
	}

	public function getCollArr($id = 0){
		if($id && isset($this->collArr[$id])) return $this->collArr[$id];
		return $this->collArr;
	}

	public function setCustomWhereSql($sql){
		$this->customWhereSql = $sql;
	}

	public function addCondition($field, $cond, $value = ''){
		//Sanitation
		$cond = strtoupper(trim($cond));
		if(!preg_match('/^[A-Za-z]+$/',$field)) return false;
		if(!preg_match('/^[A-Z]+$/',$cond)) return false;
		//Set condition
		if($field){
			if(!$cond) $cond = 'EQUALS';
			if($value || ($cond == 'NULL' || $cond == 'NOTNULL')){
				if(is_array($value)){
					$this->conditionArr[$field][$cond] = $this->cleanInArray($value);
				}
				else{
					$this->conditionArr[$field][$cond][] = $this->cleanInStr($value);
				}
			}
		}
	}

	private function applyConditions(){
		$sqlFrag = '';
		if($this->conditionArr){
			foreach($this->conditionArr as $field => $condArr){
				if($field == 'stateid'){
					$sqlFrag .= 'AND (a.stateid IN('.implode(',',$condArr['EQUALS']).')) ';
				}
				elseif($field == 'traitid'){
					$sqlFrag .= 'AND (s.traitid IN('.implode(',',$condArr['EQUALS']).')) ';
				}
				else{
					$sqlFrag2 = '';
					foreach($condArr as $cond => $valueArr){
						if($cond == 'NULL'){
							$sqlFrag2 .= 'OR o.'.$field.' IS NULL ';
						}
						elseif($cond == 'NOTNULL'){
							$sqlFrag2 .= 'OR o.'.$field.' IS NOT NULL ';
						}
						elseif($cond == 'EQUALS'){
							$sqlFrag2 .= 'OR o.'.$field.' IN("'.implode('","',$valueArr).'") ';
						}
						else{
							foreach($valueArr as $value){
								if($cond == 'STARTS'){
									$sqlFrag2 .= 'OR o.'.$field.' LIKE "'.$value.'%" ';
								}
								elseif($cond == 'LIKE'){ 
									$sqlFrag2 .= 'OR o.'.$field.' LIKE "%'.$value.'%" ';
								}
								elseif($cond == 'LESSTHAN'){ 
									$sqlFrag2 .= 'OR o.'.$field.' < "'.$value.'" ';
								}
								elseif($cond == 'GREATERTHAN'){ 
									$sqlFrag2 .= 'OR o.'.$field.' > "'.$value.'" ';
								}
							}
						}
					}
					if($sqlFrag2) $sqlFrag .= 'AND ('.substr($sqlFrag2,3).') ';
				}
			}
		}
		//Build where
		$this->conditionSql = '';
		if($this->customWhereSql){
			$this->conditionSql = $this->customWhereSql.' ';
		}
		if($this->collArr && (!$this->conditionSql || !stripos($this->conditionSql,'collid in('))){
			$this->conditionSql .= 'AND (o.collid IN('.implode(',',array_keys($this->collArr)).')) ';
		}
		if($sqlFrag){
			$this->conditionSql .= $sqlFrag;
		}
		if($this->conditionSql){
			//Make sure it starts with WHERE 
			if(substr($this->conditionSql,0,4) == 'AND '){
				$this->conditionSql = 'WHERE'.substr($this->conditionSql,3);
			}
			elseif(substr($this->conditionSql,0,6) != 'WHERE '){
				$this->conditionSql = 'WHERE '.$this->conditionSql;
			}
		}
	}
	
	private function getTableJoins(){
		$sql = '';
		if($this->conditionSql){
			if(stripos($this->conditionSql,'v.clid')){
				//Search criteria came from custom search page
				$sql = 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
			}
			if(stripos($this->conditionSql,'p.point')){
				//Search criteria came from map search page
				$sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
			}
			if(stripos($this->conditionSql,'MATCH(f.recordedby)')){
				$sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
			}
			if(stripos($this->conditionSql,'a.stateid')){
				//Search is limited by occurrence attribute
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid ';
			}
			elseif(stripos($this->conditionSql,'s.traitid')){
				//Search is limited by occurrence trait
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid '.
					'INNER JOIN tmstates s ON a.stateid = s.stateid ';
			}
		}
		return $sql;
	}

    public function getAsJson() {
        $this->schemaType='dwc';
        $arr = $this->getDwcArray();
        return json_encode($arr[0]);
    }

    /** 
     * Render the records as RDF in a turtle serialization following the TDWG
     *  DarwinCore RDF Guide.
     *
     * @return strin containing turtle serialization of selected dwc records.
     */
    public function getAsTurtle() { 
       $debug = false;
       $returnvalue  = "@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
       $returnvalue .= "@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\n";
       $returnvalue .= "@prefix owl: <http://www.w3.org/2002/07/owl#> .\n";
       $returnvalue .= "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n";
       $returnvalue .= "@prefix dwc: <http://rs.tdwg.org/dwc/terms/> .\n";
       $returnvalue .= "@prefix dwciri: <http://rs.tdwg.org/dwc/iri/> .\n";
       $returnvalue .= "@prefix dc: <http://purl.org/dc/elements/1.1/> . \n";
       $returnvalue .= "@prefix dcterms: <http://purl.org/dc/terms/> . \n";
       $returnvalue .= "@prefix dcmitype: <http://purl.org/dc/dcmitype/> . \n";
       $this->schemaType='dwc';
       $arr = $this->getDwcArray();
	   $occurTermArr = $this->occurrenceFieldArr['terms'];
       $dwcguide223 = "";
       foreach ($arr as $rownum => $dwcArray)  {
          if ($debug) { print_r($dwcArray);  } 
          if (isset($dwcArray['occurrenceID'])||(isset($dwcArray['catalogNumber']) && isset($dwcArray['collectionCode']))) { 
             $occurrenceid = $dwcArray['occurrenceID'];
             if (UuidFactory::is_valid($occurrenceid)) { 
                $occurrenceid = "urn:uuid:$occurrenceid";
             } else {
                $catalogNumber = $dwcArray['catalogNumber'];
                if (strlen($occurrenceid)==0 || $occurrenceid==$catalogNumber) {
                    // If no occurrenceID is present, construct one with a urn:catalog: scheme.
                    // Pathology may also exist of an occurrenceID equal to the catalog number, fix this.
                    $institutionCode = $dwcArray['institutionCode'];
                    $collectionCode = $dwcArray['collectionCode'];
                    $occurrenceid = "urn:catalog:$institutionCode:$collectionCode:$catalogNumber";
                }
             }
             $returnvalue .= "<$occurrenceid>\n";
             $returnvalue .= "    a dwc:Occurrence ";
             $separator = " ; \n ";
             foreach($dwcArray as $key => $value) { 
                if (strlen($value)>0) { 
                  switch ($key) {
                    case "recordId": 
                    case "occurrenceID": 
                    case "verbatimScientificName":
                         // skip
                      break;
                    case "collectionID":
                         // RDF Guide Section 2.3.3 owl:sameAs for urn:lsid and resolvable IRI.
                         if (stripos("urn:uuid:",$value)===false && UuidFactory::is_valid($value)) { 
                           $lsid = "urn:uuid:$value";
                         } elseif (stripos("urn:lsid:biocol.org",$value)===0) { 
                           $lsid = "http://biocol.org/$value";
                           $dwcguide223 .= "<http://biocol.org/$value>\n";
                           $dwcguide223 .= "    owl:sameAs <$value> .\n";
                         } else { 
                           $lsid = $value;
                         }
                         $returnvalue .= "$separator   dwciri:inCollection <$lsid>";
                      break;
                    case "basisOfRecord": 
                          if (preg_match("/(PreservedSpecimen|FossilSpecimen)/",$value)==1) { 
                             $returnvalue .= "$separator   a dcmitype:PhysicalObject";
                          }
                          $returnvalue .= "$separator   dwc:$key  \"$value\"";
                      break;
                    case "modified":
                         $returnvalue .= "$separator   dcterms:$key \"$value\"";
                      break;
                    case "rights":
                          // RDF Guide Section 3.3 dcterms:licence for IRI, xmpRights:UsageTerms for literal
                          if (stripos("http://creativecommons.org/licenses/",$value)==0) { 
                             $returnvalue .= "$separator   dcterms:license <$value>";
                          } else { 
                             $returnvalue .= "$separator   dc:$key \"$value\"";
                          }
                      break;
                    case "rightsHolder":
                          // RDF Guide Section 3.3  dcterms:rightsHolder for IRI, xmpRights:Owner for literal
                          if (stripos("http://",$value)==0 || stripos("urn:",$value)==0) { 
                             $returnvalue .= "$separator   dcterms:rightsHolder <$value>";
                          } else { 
                             $returnvalue .= "$separator   xmpRights:Owner \"$value\"";
                          }
                      break;
                    case "day":
                    case "month":
                    case "year":
                         if ($value!="0") { 
                           $returnvalue .= "$separator   dwc:$key  \"$value\"";
                         }
                      break;
                    case "eventDate":
                         if ($value!="0000-00-00" && strlen($value)>0) { 
                           $value = str_replace("-00","",$value);
                           $returnvalue .= "$separator   dwc:$key  \"$value\"";
                         }
                      break;
                    default: 
                        if (isset($occurTermArr[$key])) { 
                           $ns = RdfUtility::namespaceAbbrev($occurTermArr[$key]);
                           $returnvalue .= $separator . "   " . $ns . " \"$value\"";
                        }
                  }
                }
             }
         
             $returnvalue .= ".\n";
          }
       }
       if ($dwcguide223!="") { 
          $returnvalue .= $dwcguide223;
       }
       return $returnvalue;
    }

    /** 
     * Render the records as RDF in a rdf/xml serialization following the TDWG
     *  DarwinCore RDF Guide.
     *
     * @return string containing rdf/xml serialization of selected dwc records.
     */
    public function getAsRdfXml() { 
       $debug = false;
	   $newDoc = new DOMDocument('1.0',$this->charSetOut);
       $newDoc->formatOutput = true;

       $rootElem = $newDoc->createElement('rdf:RDF');
       $rootElem->setAttribute('xmlns:rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns#');
       $rootElem->setAttribute('xmlns:rdfs','http://www.w3.org/2000/01/rdf-schema#');
       $rootElem->setAttribute('xmlns:owl','http://www.w3.org/2002/07/owl#');
       $rootElem->setAttribute('xmlns:foaf','http://xmlns.com/foaf/0.1/');
       $rootElem->setAttribute('xmlns:dwc','http://rs.tdwg.org/dwc/terms/');
       $rootElem->setAttribute('xmlns:dwciri','http://rs.tdwg.org/dwc/iri/');
       $rootElem->setAttribute('xmlns:dc','http://purl.org/dc/elements/1.1/');
       $rootElem->setAttribute('xmlns:dcterms','http://purl.org/dc/terms/');
       $rootElem->setAttribute('xmlns:dcmitype','http://purl.org/dc/dcmitype/');
       $newDoc->appendChild($rootElem);

       $this->schemaType='dwc';
       $arr = $this->getDwcArray();
	   $occurTermArr = $this->occurrenceFieldArr['terms'];
       foreach ($arr as $rownum => $dwcArray)  {
          if ($debug) { print_r($dwcArray);  } 
          if (isset($dwcArray['occurrenceID'])||(isset($dwcArray['catalogNumber']) && isset($dwcArray['collectionCode']))) { 
             $occurrenceid = $dwcArray['occurrenceID'];
             if (UuidFactory::is_valid($occurrenceid)) { 
                $occurrenceid = "urn:uuid:$occurrenceid";
             } else {
                $catalogNumber = $dwcArray['catalogNumber'];
                if (strlen($occurrenceid)==0 || $occurrenceid==$catalogNumber) {
                    // If no occurrenceID is present, construct one with a urn:catalog: scheme.
                    // Pathology may also exist of an occurrenceID equal to the catalog number, fix this.
                    $institutionCode = $dwcArray['institutionCode'];
                    $collectionCode = $dwcArray['collectionCode'];
                    $occurrenceid = "urn:catalog:$institutionCode:$collectionCode:$catalogNumber";
                }
             }
             $occElem = $newDoc->createElement('dwc:Occurrence');
             $occElem->setAttribute("rdf:about","$occurrenceid");
             $sameAsElem = null;
             foreach($dwcArray as $key => $value) { 
                $flags = ENT_NOQUOTES;
                if(defined('ENT_XML1')) $flags = ENT_NOQUOTES | ENT_XML1 | ENT_DISALLOWED;
                $value = htmlentities($value,$flags,$this->charSetOut);
                // TODO: Figure out how to use mb_encode_numericentity() here.
                $value = str_replace("&copy;","&#169;",$value);  // workaround, need to fix &copy; rendering
                if (strlen($value)>0) { 
                  $elem = null;
                  switch ($key) {
                    case "recordId": 
                    case "occurrenceID": 
                    case "verbatimScientificName":
                         // skip
                      break;
                    case "collectionID":
                         // RDF Guide Section 2.3.3 owl:sameAs for urn:lsid and resolvable IRI.
                         if (stripos("urn:uuid:",$value)===false && UuidFactory::is_valid($value)) { 
                           $lsid = "urn:uuid:$value";
                         }elseif (stripos("urn:lsid:biocol.org",$value)===0) { 
                           $lsid = "http://biocol.org/$value";
                           $sameAsElem = $newDoc->createElement("rdf:Description");
                           $sameAsElem->setAttribute("rdf:about","http://biocol.org/$value");
                           $sameAsElemC = $newDoc->createElement("owl:sameAs");
                           $sameAsElemC->setAttribute("rdf:resource","$value");
                           $sameAsElem->appendChild($sameAsElemC);
                         } else { 
                           $lsid = $value;
                         }
                         $elem = $newDoc->createElement("dwciri:inCollection");
                         $elem->setAttribute("rdf:resource","$lsid");
                      break;
                    case "basisOfRecord": 
                          if (preg_match("/(PreservedSpecimen|FossilSpecimen)/",$value)==1) { 
                             $elem = $newDoc->createElement("rdf:type");
                             $elem->setAttribute("rdf:resource","http://purl.org/dc/dcmitype/PhysicalObject");
                          }
                          $elem = $newDoc->createElement("dwc:$key",$value);
                      break;
                    case "rights":
                          // RDF Guide Section 3.3 dcterms:licence for IRI, xmpRights:UsageTerms for literal
                          if (stripos("http://creativecommons.org/licenses/",$value)==0) { 
                             $elem = $newDoc->createElement("dcterms:license");
                             $elem->setAttribute("rdf:resource","$value");
                          } else { 
                             $elem = $newDoc->createElement("xmpRights:UsageTerms",$value);
                          }
                      break;
                    case "rightsHolder":
                          // RDF Guide Section 3.3  dcterms:rightsHolder for IRI, xmpRights:Owner for literal
                          if (stripos("http://",$value)==0 || stripos("urn:",$value)==0) { 
                             $elem = $newDoc->createElement("dcterms:rightsHolder");
                             $elem->setAttribute("rdf:resource","$value");
                          } else { 
                             $elem = $newDoc->createElement("xmpRights:Owner",$value);
                          }
                      break;
                    case "modified":
                          $elem = $newDoc->createElement("dcterms:$key",$value);
                      break;
                    case "day":
                    case "month":
                    case "year":
                         if ($value!="0") { 
                            $elem = $newDoc->createElement("dwc:$key",$value);
                         }
                      break;
                    case "eventDate":
                         if ($value!="0000-00-00" || strlen($value)>0) { 
                           $value = str_replace("-00","",$value);
                           $elem = $newDoc->createElement("dwc:$key",$value);
                         }
                      break;
                    default: 
                         if (isset($occurTermArr[$key])) { 
                            $ns = RdfUtility::namespaceAbbrev($occurTermArr[$key]);
                            $elem = $newDoc->createElement($ns);
                            $elem->appendChild($newDoc->createTextNode($value));
                         }
                  }
                  if ($elem!=null) { 
                     $occElem->appendChild($elem);
                  }
                }
             }
             $node = $newDoc->importNode($occElem);
             $newDoc->documentElement->appendChild($node);
             if ($sameAsElem!=null) { 
                $node = $newDoc->importNode($sameAsElem);
                $newDoc->documentElement->appendChild($node);
             }
             // For many matching rows this is a point where partial serialization could occur
             // to prevent creation of a large DOM model in memmory.
          }
       }
       $returnvalue = $newDoc->saveXML();
       return $returnvalue;
    }

    private function getDwcArray() { 
		$result = Array();
		if(!$this->occurrenceFieldArr){
			$this->occurrenceFieldArr = DwcArchiverOccurrence::getOccurrenceArr($this->schemaType, $this->extended);
		}
		
		$this->applyConditions();
		$sql = DwcArchiverOccurrence::getSqlOccurrences($this->occurrenceFieldArr['fields'],$this->conditionSql,$this->getTableJoins());
		if(!$sql) return false;
		$fieldArr = $this->occurrenceFieldArr['fields'];
		if($this->schemaType == 'dwc'){
			unset($fieldArr['localitySecurity']);
		}
		if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
			unset($fieldArr['collId']);
		}
		if(!$this->collArr){
			//Collection array not previously primed by source  
			$sql1 = 'SELECT DISTINCT o.collid FROM omoccurrences o ';
			if($this->conditionSql){
				$sql1 .= $this->getTableJoins().$this->conditionSql;
			}
			$rs1 = $this->conn->query($sql1);
			$collidStr = '';
			while($r1 = $rs1->fetch_object()){
				$collidStr .= ','.$r1->collid;
			}
			$rs1->free();
			if($collidStr) $this->setCollArr(trim($collidStr,','));
		}

		//Populate Upper Taxonomic data
		$this->setUpperTaxonomy();
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			$this->setServerDomain();
			$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
			$hasRecords = false;
			$cnt = 0;
			while($r = $rs->fetch_assoc()){
				$hasRecords = true;
				//Protect sensitive records
				if($this->redactLocalities 
                   && $r["localitySecurity"] == 1 
                   && !in_array($r['collid'],$this->rareReaderArr)
                ){
					$protectedFields = array();
					foreach($this->securityArr as $v){
						if(array_key_exists($v,$r) && $r[$v]){
							$r[$v] = '';
							$protectedFields[] = $v;
						}
					}
					if($protectedFields){
						$r['informationWithheld'] = trim($r['informationWithheld'].'; field values redacted: '.implode(', ',$protectedFields),' ;');
					}
				}
				if(!$r['occurrenceID']){
					//Set occurrence GUID based on GUID target, but only if occurrenceID field isn't already populated
					$guidTarget = $this->collArr[$r['collid']]['guidtarget'];
					if($guidTarget == 'catalogNumber'){
						$r['occurrenceID'] = $r['catalogNumber'];
					}
					elseif($guidTarget == 'symbiotaUUID'){
						$r['occurrenceID'] = $r['recordId'];
					}
				}
				
				$r['recordId'] = 'urn:uuid:'.$r['recordId'];
				//Add collection GUID based on management type
				$managementType = $this->collArr[$r['collid']]['managementtype'];
				if($managementType && $managementType == 'Live Data'){
					if(array_key_exists('collectionID',$r) && !$r['collectionID']){
						$guid = $this->collArr[$r['collid']]['collectionguid'];
						if(strlen($guid) == 36) $guid = 'urn:uuid:'.$guid;
						$r['collectionID'] = $guid;
					}
				}
				if($this->schemaType == 'dwc'){
					unset($r['localitySecurity']);
				}
				if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
					unset($r['collid']);
				}
				//Add upper taxonomic data
				if($r['family'] && $this->upperTaxonomy){
					$famStr = strtolower($r['family']);
					if(isset($this->upperTaxonomy[$famStr]['o'])){
						$r['t_order'] = $this->upperTaxonomy[$famStr]['o'];
					}
					if(isset($this->upperTaxonomy[$famStr]['c'])){
						$r['t_class'] = $this->upperTaxonomy[$famStr]['c'];
					}
					if(isset($this->upperTaxonomy[$famStr]['p'])){
						$r['t_phylum'] = $this->upperTaxonomy[$famStr]['p'];
					}
					if(isset($this->upperTaxonomy[$famStr]['k'])){
						$r['t_kingdom'] = $this->upperTaxonomy[$famStr]['k'];
					}
				}
				if($urlPathPrefix) $r['t_references'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				
				foreach($r as $rKey => $rValue){
					if(substr($rKey, 0, 2) == 't_') $rKey = substr($rKey,2);
	                $result[$cnt][$rKey] = $rValue;
				}
				$cnt++;
			}
			$rs->free();
			$result[0]['associatedMedia'] = $this->getAssociatedMedia();
		}
		else{
			$this->logOrEcho("ERROR creating occurrence file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
		return $result;
    }
    
    private function getAssociatedMedia(){
    	$retStr = '';
    	$sql = 'SELECT originalurl FROM images '.str_replace('o.','',$this->conditionSql);
    	$rs = $this->conn->query($sql);
    	while($r = $rs->fetch_object()){
    		$retStr .= ';'.$r->originalurl;
    	}
    	$rs->free();
    	return trim($retStr,';');
    }

	public function createDwcArchive($fileNameSeed = ''){
		$status = false;
		if(!$fileNameSeed){
			if(count($this->collArr) == 1){
				$firstColl = current($this->collArr);
				if($firstColl){
					$fileNameSeed = $firstColl['instcode'];
					if($firstColl['collcode']) $fileNameSeed .= '-'.$firstColl['collcode'];
				}
				if($this->schemaType == 'backup'){
					$fileNameSeed .= '_backup_'.$this->ts;
				}
			}
			else{
				$fileNameSeed = 'SymbiotaOutput_'.$this->ts;
			}
		}
		$fileName = str_replace(array(' ','"',"'"),'',$fileNameSeed).'_DwC-A.zip';
		
		if(!$this->targetPath) $this->setTargetPath();
		$archiveFile = '';
		$this->logOrEcho('Creating DwC-A file: '.$fileName."\n");
		
		if(!class_exists('ZipArchive')){
			$this->logOrEcho("FATAL ERROR: PHP ZipArchive class is not installed, please contact your server admin\n");
			exit('FATAL ERROR: PHP ZipArchive class is not installed, please contact your server admin');
		}
		$status = $this->writeOccurrenceFile();
		if($status){
			$archiveFile = $this->targetPath.$fileName;
			if(file_exists($archiveFile)) unlink($archiveFile);
			$zipArchive = new ZipArchive;
			$status = $zipArchive->open($archiveFile, ZipArchive::CREATE);
			if($status !== true){
				exit('FATAL ERROR: unable to create archive file: '.$status);
			}
			//$this->logOrEcho("DWCA created: ".$archiveFile."\n");
			
			//Occurrences
			$zipArchive->addFile($this->targetPath.$this->ts.'-occur'.$this->fileExt);
			$zipArchive->renameName($this->targetPath.$this->ts.'-occur'.$this->fileExt,'occurrences'.$this->fileExt);
			//Determination history
			if($this->includeDets) {
				$this->writeDeterminationFile();
				$zipArchive->addFile($this->targetPath.$this->ts.'-det'.$this->fileExt);
				$zipArchive->renameName($this->targetPath.$this->ts.'-det'.$this->fileExt,'identifications'.$this->fileExt);
			}
			//Images
			if($this->includeImgs){
				$this->writeImageFile();
				$zipArchive->addFile($this->targetPath.$this->ts.'-images'.$this->fileExt);
				$zipArchive->renameName($this->targetPath.$this->ts.'-images'.$this->fileExt,'images'.$this->fileExt);
			}
			//Occurrence Attributes
			if($this->includeAttributes){
				$this->writeAttributeFile();
				$zipArchive->addFile($this->targetPath.$this->ts.'-attr'.$this->fileExt);
				$zipArchive->renameName($this->targetPath.$this->ts.'-attr'.$this->fileExt,'measurementOrFact'.$this->fileExt);
			}
			//Meta file
			$this->writeMetaFile();
			$zipArchive->addFile($this->targetPath.$this->ts.'-meta.xml');
    		$zipArchive->renameName($this->targetPath.$this->ts.'-meta.xml','meta.xml');
			//EML file
			$this->writeEmlFile();
			$zipArchive->addFile($this->targetPath.$this->ts.'-eml.xml');
    		$zipArchive->renameName($this->targetPath.$this->ts.'-eml.xml','eml.xml');

			$zipArchive->close();
			unlink($this->targetPath.$this->ts.'-occur'.$this->fileExt);
			if($this->includeDets) unlink($this->targetPath.$this->ts.'-det'.$this->fileExt);
			if($this->includeImgs) unlink($this->targetPath.$this->ts.'-images'.$this->fileExt);
			if($this->includeAttributes) unlink($this->targetPath.$this->ts.'-attr'.$this->fileExt);
			unlink($this->targetPath.$this->ts.'-meta.xml');
			if($this->schemaType == 'dwc'){
				rename($this->targetPath.$this->ts.'-eml.xml',$this->targetPath.str_replace('.zip','.eml',$fileName));
			}
			else{
				unlink($this->targetPath.$this->ts.'-eml.xml');
			}
		}
		else{
			$errStr = "<span style='color:red;'>FAILED to create archive file due to failure to return occurrence records. ".
				"Note that OccurrenceID GUID assignments are required for Darwin Core Archive publishing. ".
				"Symbiota GUID (recordID) assignments are also required, which can be verified by the portal manager through running the GUID mapping utilitiy available in sitemap</span>";
			$this->logOrEcho($errStr);
			$collid = key($this->collArr);
			if($collid) $this->deleteArchive($collid);
			unset($this->collArr[$collid]);
		}
		$this->logOrEcho("\n-----------------------------------------------------\n");
		return $archiveFile;
	}

	//Generate DwC support files
	private function writeMetaFile(){
		$this->logOrEcho("Creating meta.xml (".date('h:i:s A').")... ");
		
		//Create new DOM document 
		$newDoc = new DOMDocument('1.0',$this->charSetOut);

		//Add root element 
		$rootElem = $newDoc->createElement('archive');
		$rootElem->setAttribute('metadata','eml.xml');
		$rootElem->setAttribute('xmlns','http://rs.tdwg.org/dwc/text/');
		$rootElem->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$rootElem->setAttribute('xsi:schemaLocation','http://rs.tdwg.org/dwc/text/   http://rs.tdwg.org/dwc/text/tdwg_dwc_text.xsd');
		$newDoc->appendChild($rootElem);

		//Core file definition
		$coreElem = $newDoc->createElement('core');
		$coreElem->setAttribute('dateFormat','YYYY-MM-DD');
		$coreElem->setAttribute('encoding',$this->charSetOut);
		$coreElem->setAttribute('fieldsTerminatedBy',$this->delimiter);
		$coreElem->setAttribute('linesTerminatedBy','\n');
		$coreElem->setAttribute('fieldsEnclosedBy','"');
		$coreElem->setAttribute('ignoreHeaderLines','1');
		$coreElem->setAttribute('rowType','http://rs.tdwg.org/dwc/terms/Occurrence');
		
		$filesElem = $newDoc->createElement('files');
		$filesElem->appendChild($newDoc->createElement('location','occurrences'.$this->fileExt));
		$coreElem->appendChild($filesElem);

		$idElem = $newDoc->createElement('id');
		$idElem->setAttribute('index','0');
		$coreElem->appendChild($idElem);

		$occCnt = 1;
		$termArr = $this->occurrenceFieldArr['terms'];
		if($this->schemaType == 'dwc'){
			unset($termArr['localitySecurity']);
		}
		if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
			unset($termArr['collId']);
		}
		foreach($termArr as $k => $v){
			$fieldElem = $newDoc->createElement('field');
			$fieldElem->setAttribute('index',$occCnt);
			$fieldElem->setAttribute('term',$v);
			$coreElem->appendChild($fieldElem);
			$occCnt++;
		}
		$rootElem->appendChild($coreElem);

		//Identification extension
		if($this->includeDets){
			$extElem1 = $newDoc->createElement('extension');
			$extElem1->setAttribute('encoding',$this->charSetOut);
			$extElem1->setAttribute('fieldsTerminatedBy',$this->delimiter);
			$extElem1->setAttribute('linesTerminatedBy','\n');
			$extElem1->setAttribute('fieldsEnclosedBy','"');
			$extElem1->setAttribute('ignoreHeaderLines','1');
			$extElem1->setAttribute('rowType','http://rs.tdwg.org/dwc/terms/Identification');
	
			$filesElem1 = $newDoc->createElement('files');
			$filesElem1->appendChild($newDoc->createElement('location','identifications'.$this->fileExt));
			$extElem1->appendChild($filesElem1);
			
			$coreIdElem1 = $newDoc->createElement('coreid');
			$coreIdElem1->setAttribute('index','0');
			$extElem1->appendChild($coreIdElem1);
			
			//List identification fields
			$detCnt = 1;
			$termArr = $this->determinationFieldArr['terms'];
			foreach($termArr as $k => $v){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$detCnt);
				$fieldElem->setAttribute('term',$v);
				$extElem1->appendChild($fieldElem);
				$detCnt++;
			}
			$rootElem->appendChild($extElem1);
		}

		//Image extension
		if($this->includeImgs){
			$extElem2 = $newDoc->createElement('extension');
			$extElem2->setAttribute('encoding',$this->charSetOut);
			$extElem2->setAttribute('fieldsTerminatedBy',$this->delimiter);
			$extElem2->setAttribute('linesTerminatedBy','\n');
			$extElem2->setAttribute('fieldsEnclosedBy','"');
			$extElem2->setAttribute('ignoreHeaderLines','1');
			$extElem2->setAttribute('rowType','http://rs.gbif.org/terms/1.0/Image');
	
			$filesElem2 = $newDoc->createElement('files');
			$filesElem2->appendChild($newDoc->createElement('location','images'.$this->fileExt));
			$extElem2->appendChild($filesElem2);
			
			$coreIdElem2 = $newDoc->createElement('coreid');
			$coreIdElem2->setAttribute('index','0');
			$extElem2->appendChild($coreIdElem2);
			
			//List image fields
			$imgCnt = 1;
			$termArr = $this->imageFieldArr['terms'];
			foreach($termArr as $k => $v){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$imgCnt);
				$fieldElem->setAttribute('term',$v);
				$extElem2->appendChild($fieldElem);
				$imgCnt++;
			}
			$rootElem->appendChild($extElem2);
		}
		
		//MeasurementOrFact extension
		if($this->includeAttributes){
			$extElem3 = $newDoc->createElement('extension');
			$extElem3->setAttribute('encoding',$this->charSetOut);
			$extElem3->setAttribute('fieldsTerminatedBy',$this->delimiter);
			$extElem3->setAttribute('linesTerminatedBy','\n');
			$extElem3->setAttribute('fieldsEnclosedBy','"');
			$extElem3->setAttribute('ignoreHeaderLines','1');
			$extElem3->setAttribute('rowType','http://rs.iobis.org/obis/terms/ExtendedMeasurementOrFact');

			$filesElem3 = $newDoc->createElement('files');
			$filesElem3->appendChild($newDoc->createElement('location','measurementOrFact'.$this->fileExt));
			$extElem3->appendChild($filesElem3);

			$coreIdElem3 = $newDoc->createElement('coreid');
			$coreIdElem3->setAttribute('index','0');
			$extElem3->appendChild($coreIdElem3);
			
			$mofCnt = 1;
			$termArr = $this->attributeFieldArr['terms'];
			foreach($termArr as $k => $v){
				$fieldElem = $newDoc->createElement('field');
				$fieldElem->setAttribute('index',$mofCnt);
				$fieldElem->setAttribute('term',$v);
				$extElem3->appendChild($fieldElem);
				$mofCnt++;
			}
			$rootElem->appendChild($extElem3);
		}
		
		$newDoc->save($this->targetPath.$this->ts.'-meta.xml');
		
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function getEmlArr(){
		
		$this->setServerDomain();
		$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
		$localDomain = $this->serverDomain;
		
		$emlArr = array();
		if(count($this->collArr) == 1){
			$collId = key($this->collArr);
			$cArr = $this->collArr[$collId];

			$emlArr['alternateIdentifier'][] = $urlPathPrefix.'collections/misc/collprofiles.php?collid='.$collId;
			$emlArr['title'] = $cArr['collname'];
			$emlArr['description'] = $cArr['description'];
	
			$emlArr['contact']['individualName'] = $cArr['contact'];
			$emlArr['contact']['organizationName'] = $cArr['collname'];
			$emlArr['contact']['phone'] = $cArr['phone'];
			$emlArr['contact']['electronicMailAddress'] = $cArr['email'];
			$emlArr['contact']['onlineUrl'] = $cArr['url'];
			
			$emlArr['contact']['addr']['deliveryPoint'] = $cArr['address1'].($cArr['address2']?', '.$cArr['address2']:'');
			$emlArr['contact']['addr']['city'] = $cArr['city'];
			$emlArr['contact']['addr']['administrativeArea'] = $cArr['state'];
			$emlArr['contact']['addr']['postalCode'] = $cArr['postalcode'];
			$emlArr['contact']['addr']['country'] = $cArr['country'];
			
			
			$emlArr['intellectualRights'] = $cArr['rights'];
		}
		else{
			$emlArr['title'] = $GLOBALS['DEFAULT_TITLE'].' general data extract';
		}
		if(isset($GLOBALS['USER_DISPLAY_NAME']) && $GLOBALS['USER_DISPLAY_NAME']){
			//$emlArr['creator'][0]['individualName'] = $GLOBALS['USER_DISPLAY_NAME'];
			$emlArr['associatedParty'][0]['individualName'] = $GLOBALS['USER_DISPLAY_NAME'];
			$emlArr['associatedParty'][0]['role'] = 'CONTENT_PROVIDER';
		}

		if(array_key_exists('PORTAL_GUID',$GLOBALS) && $GLOBALS['PORTAL_GUID']){
			$emlArr['creator'][0]['attr']['id'] = $GLOBALS['PORTAL_GUID'];
		}
		$emlArr['creator'][0]['organizationName'] = $GLOBALS['DEFAULT_TITLE'];
		$emlArr['creator'][0]['electronicMailAddress'] = $GLOBALS['ADMIN_EMAIL'];
		$emlArr['creator'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['metadataProvider'][0]['organizationName'] = $GLOBALS['DEFAULT_TITLE'];
		$emlArr['metadataProvider'][0]['electronicMailAddress'] = $GLOBALS['ADMIN_EMAIL'];
		$emlArr['metadataProvider'][0]['onlineUrl'] = $urlPathPrefix.'index.php';
		
		$emlArr['pubDate'] = date("Y-m-d");
		
		//Append collection metadata
		$cnt = 1;
		foreach($this->collArr as $id => $collArr){
			//associatedParty elements
			$emlArr['associatedParty'][$cnt]['organizationName'] = $collArr['collname'];
			$emlArr['associatedParty'][$cnt]['individualName'] = $collArr['contact'];
			$emlArr['associatedParty'][$cnt]['positionName'] = 'Collection Manager';
			$emlArr['associatedParty'][$cnt]['role'] = 'CONTENT_PROVIDER';
			$emlArr['associatedParty'][$cnt]['electronicMailAddress'] = $collArr['email'];
			$emlArr['associatedParty'][$cnt]['phone'] = $collArr['phone'];
			
			if($collArr['state']){
				$emlArr['associatedParty'][$cnt]['address']['deliveryPoint'] = $collArr['address1'];
				if($collArr['address2']) $emlArr['associatedParty'][$cnt]['address']['deliveryPoint'] = $collArr['address2'];
				$emlArr['associatedParty'][$cnt]['address']['city'] = $collArr['city'];
				$emlArr['associatedParty'][$cnt]['address']['administrativeArea'] = $collArr['state'];
				$emlArr['associatedParty'][$cnt]['address']['postalCode'] = $collArr['postalcode'];
				$emlArr['associatedParty'][$cnt]['address']['country'] = $collArr['country'];
			}

			//Collection metadata section (additionalMetadata)
			$emlArr['collMetadata'][$cnt]['attr']['identifier'] = $collArr['collectionguid'];
			$emlArr['collMetadata'][$cnt]['attr']['id'] = $id;
			$emlArr['collMetadata'][$cnt]['alternateIdentifier'] = $urlPathPrefix.'collections/misc/collprofiles.php?collid='.$id;
			$emlArr['collMetadata'][$cnt]['parentCollectionIdentifier'] = $collArr['instcode']; 
			$emlArr['collMetadata'][$cnt]['collectionIdentifier'] = $collArr['collcode']; 
			$emlArr['collMetadata'][$cnt]['collectionName'] = $collArr['collname'];
			if($collArr['icon']){
				$imgLink = '';
				if(substr($collArr['icon'],0,17) == 'images/collicons/'){
					$imgLink = $urlPathPrefix.$collArr['icon'];
				}
				elseif(substr($collArr['icon'],0,1) == '/'){
					$imgLink = $localDomain.$collArr['icon'];
				}
				else{
					$imgLink = $collArr['icon'];
				}
				$emlArr['collMetadata'][$cnt]['resourceLogoUrl'] = $imgLink;
			}
			$emlArr['collMetadata'][$cnt]['onlineUrl'] = $collArr['url'];
			$emlArr['collMetadata'][$cnt]['intellectualRights'] = $collArr['rights'];
			if($collArr['rightsholder']) $emlArr['collMetadata'][$cnt]['additionalInfo'] = $collArr['rightsholder'];
			if($collArr['usageterm']) $emlArr['collMetadata'][$cnt]['additionalInfo'] = $collArr['usageterm'];
			$emlArr['collMetadata'][$cnt]['abstract'] = $collArr['description'];
			
			$cnt++; 
		}
		$emlArr = $this->utf8EncodeArr($emlArr);
		return $emlArr;
	}
	
	private function writeEmlFile(){
		$this->logOrEcho("Creating eml.xml (".date('h:i:s A').")... ");
		
		$emlDoc = $this->getEmlDom();

		$emlDoc->save($this->targetPath.$this->ts.'-eml.xml');

    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	/* 
	 * Input: Array containing the eml data
	 * OUTPUT: XML String representing the EML
	 * USED BY: this class, and emlhandler.php 
	 */
	public function getEmlDom($emlArr = null){
		global $RIGHTS_TERMS_DEFS;
		$usageTermArr = Array();
		
		if(!$emlArr) $emlArr = $this->getEmlArr();
		foreach($RIGHTS_TERMS_DEFS as $k => $v){
			if($k == $emlArr['intellectualRights']){
				$usageTermArr = $v;
			}
		}

		//Create new DOM document 
		$newDoc = new DOMDocument('1.0',$this->charSetOut);

		//Add root element 
		$rootElem = $newDoc->createElement('eml:eml');
		$rootElem->setAttribute('xmlns:eml','eml://ecoinformatics.org/eml-2.1.1');
		$rootElem->setAttribute('xmlns:dc','http://purl.org/dc/terms/');
		$rootElem->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$rootElem->setAttribute('xsi:schemaLocation','eml://ecoinformatics.org/eml-2.1.1 http://rs.gbif.org/schema/eml-gbif-profile/1.0.1/eml.xsd');
		$rootElem->setAttribute('packageId',UuidFactory::getUuidV4());
		$rootElem->setAttribute('system','http://symbiota.org');
		$rootElem->setAttribute('scope','system');
		$rootElem->setAttribute('xml:lang','eng');
		
		$newDoc->appendChild($rootElem);

		$cArr = array();
		$datasetElem = $newDoc->createElement('dataset');
		$rootElem->appendChild($datasetElem);

		if(array_key_exists('alternateIdentifier',$emlArr)){
			foreach($emlArr['alternateIdentifier'] as $v){
				$altIdElem = $newDoc->createElement('alternateIdentifier');
				$altIdElem->appendChild($newDoc->createTextNode($v));
				$datasetElem->appendChild($altIdElem);
			}
		}
		
		if(array_key_exists('title',$emlArr)){
			$titleElem = $newDoc->createElement('title');
			$titleElem->setAttribute('xml:lang','eng');
			$titleElem->appendChild($newDoc->createTextNode($emlArr['title']));
			$datasetElem->appendChild($titleElem);
		}

		if(array_key_exists('creator',$emlArr)){
			$createArr = $emlArr['creator'];
			foreach($createArr as $childArr){
				$creatorElem = $newDoc->createElement('creator');
				if(isset($childArr['attr'])){
					$attrArr = $childArr['attr'];
					unset($childArr['attr']);
					foreach($attrArr as $atKey => $atValue){
						$creatorElem->setAttribute($atKey,$atValue);
					}
				}
				foreach($childArr as $k => $v){
					$newChildElem = $newDoc->createElement($k);
					$newChildElem->appendChild($newDoc->createTextNode($v));
					$creatorElem->appendChild($newChildElem);
				}
				$datasetElem->appendChild($creatorElem);
			}
		}

		if(array_key_exists('metadataProvider',$emlArr)){
			$mdArr = $emlArr['metadataProvider'];
			foreach($mdArr as $childArr){
				$mdElem = $newDoc->createElement('metadataProvider');
				foreach($childArr as $k => $v){
					$newChildElem = $newDoc->createElement($k);
					$newChildElem->appendChild($newDoc->createTextNode($v));
					$mdElem->appendChild($newChildElem);
				}
				$datasetElem->appendChild($mdElem);
			}
		}
		
		if(array_key_exists('pubDate',$emlArr) && $emlArr['pubDate']){
			$pubElem = $newDoc->createElement('pubDate');
			$pubElem->appendChild($newDoc->createTextNode($emlArr['pubDate']));
			$datasetElem->appendChild($pubElem);
		}
		$langStr = 'eng';
		if(array_key_exists('language',$emlArr) && $emlArr) $langStr = $emlArr['language'];
		$langElem = $newDoc->createElement('language');
		$langElem->appendChild($newDoc->createTextNode($langStr));
		$datasetElem->appendChild($langElem);

		if(array_key_exists('description',$emlArr) && $emlArr['description']){
			$abstractElem = $newDoc->createElement('abstract');
			$paraElem = $newDoc->createElement('para');
			$paraElem->appendChild($newDoc->createTextNode($emlArr['description']));
			$abstractElem->appendChild($paraElem);
			$datasetElem->appendChild($abstractElem);
		}
		
		if(array_key_exists('contact',$emlArr)){
			$contactArr = $emlArr['contact'];
			$contactElem = $newDoc->createElement('contact');
			$addrArr = array();
			if(isset($contactArr['addr'])){
				$addrArr = $contactArr['addr'];
				unset($contactArr['addr']);
			}
			foreach($contactArr as $contactKey => $contactValue){
				$conElem = $newDoc->createElement($contactKey);
				$conElem->appendChild($newDoc->createTextNode($contactValue));
				$contactElem->appendChild($conElem);
			}
			if(isset($contactArr['addr'])){
				$addressElem = $newDoc->createElement('address');
				foreach($addrArr as $aKey => $aVal){
					$childAddrElem = $newDoc->createElement($aKey);
					$childAddrElem->appendChild($newDoc->createTextNode($aVal));
					$addressElem->appendChild($childAddrElem);
				}
				$contactElem->appendChild($addressElem);
			}
			$datasetElem->appendChild($contactElem);
		}

		if(array_key_exists('associatedParty',$emlArr)){
			$associatedPartyArr = $emlArr['associatedParty'];
			foreach($associatedPartyArr as $assocKey => $assocArr){
				$assocElem = $newDoc->createElement('associatedParty');
				$addrArr = array();
				if(isset($assocArr['address'])){
					$addrArr = $assocArr['address'];
					unset($assocArr['address']);
				}
				foreach($assocArr as $aKey => $aArr){
					$childAssocElem = $newDoc->createElement($aKey);
					$childAssocElem->appendChild($newDoc->createTextNode($aArr));
					$assocElem->appendChild($childAssocElem);
				}
				if($addrArr){
					$addrElem = $newDoc->createElement('address');
					foreach($addrArr as $addrKey => $addrValue){
						$childAddrElem = $newDoc->createElement($addrKey);
						$childAddrElem->appendChild($newDoc->createTextNode($addrValue));
						$addrElem->appendChild($childAddrElem);
					}
					$assocElem->appendChild($addrElem);
				}
				$datasetElem->appendChild($assocElem);
			}
		}
		
		if(array_key_exists('intellectualRights',$emlArr)){
			$rightsElem = $newDoc->createElement('intellectualRights');
			$paraElem = $newDoc->createElement('para');
			$paraElem->appendChild($newDoc->createTextNode('To the extent possible under law, the publisher has waived all rights to these data and has dedicated them to the '));
            $ulinkElem = $newDoc->createElement('ulink');
            $citetitleElem = $newDoc->createElement('citetitle');
            $citetitleElem->appendChild($newDoc->createTextNode((array_key_exists('title',$usageTermArr)?$usageTermArr['title']:'')));
            $ulinkElem->appendChild($citetitleElem);
            $ulinkElem->setAttribute('url',(array_key_exists('url',$usageTermArr)?$usageTermArr['url']:$emlArr['intellectualRights']));
            $paraElem->appendChild($ulinkElem);
            $paraElem->appendChild($newDoc->createTextNode((array_key_exists('def',$usageTermArr)?$usageTermArr['def']:'')));
            $rightsElem->appendChild($paraElem);
			$datasetElem->appendChild($rightsElem);
		}

		$symbElem = $newDoc->createElement('symbiota');
		$dateElem = $newDoc->createElement('dateStamp');
		$dateElem->appendChild($newDoc->createTextNode(date("c")));
		$symbElem->appendChild($dateElem);
		//Citation
		$id = UuidFactory::getUuidV4();
		$citeElem = $newDoc->createElement('citation');
		$citeElem->appendChild($newDoc->createTextNode($GLOBALS['defaultTitle'].' - '.$id));
		$citeElem->setAttribute('identifier',$id);
		$symbElem->appendChild($citeElem);
		//Physical
		$physicalElem = $newDoc->createElement('physical');
		$physicalElem->appendChild($newDoc->createElement('characterEncoding',$this->charSetOut));
		//format
		$dfElem = $newDoc->createElement('dataFormat');
		$edfElem = $newDoc->createElement('externallyDefinedFormat');
		$dfElem->appendChild($edfElem);
		$edfElem->appendChild($newDoc->createElement('formatName','Darwin Core Archive'));
		$physicalElem->appendChild($dfElem);
		$symbElem->appendChild($physicalElem);
		//Collection data
		if(array_key_exists('collMetadata',$emlArr)){
			
			foreach($emlArr['collMetadata'] as $k => $collArr){
				$collArr = $this->utf8EncodeArr($collArr);
				$collElem = $newDoc->createElement('collection');
				if(isset($collArr['attr']) && $collArr['attr']){
					$attrArr = $collArr['attr'];
					unset($collArr['attr']);
					foreach($attrArr as $attrKey => $attrValue){
						$collElem->setAttribute($attrKey,$attrValue);
					}
				}
				$abstractStr = '';
				if(isset($collArr['abstract']) && $collArr['abstract']){
					$abstractStr = $collArr['abstract'];
					unset($collArr['abstract']);
				}
				foreach($collArr as $collKey => $collValue){
					$collElem2 = $newDoc->createElement($collKey);
					$collElem2->appendChild($newDoc->createTextNode($collValue));
					$collElem->appendChild($collElem2);
				}
				if($abstractStr){
					$abstractElem = $newDoc->createElement('abstract');
					$abstractElem2 = $newDoc->createElement('para');
					$abstractElem2->appendChild($newDoc->createTextNode($abstractStr));
					$abstractElem->appendChild($abstractElem2);
					$collElem->appendChild($abstractElem);
				}
				$symbElem->appendChild($collElem);
			}
		}
		
		$metaElem = $newDoc->createElement('metadata');
		$metaElem->appendChild($symbElem);
		if($this->schemaType == 'coge' && $this->geolocateVariables){
			$this->setServerDomain();
			$urlPathPrefix = '';
			if($this->serverDomain){
				$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
				$urlPathPrefix .= 'collections/individual/index.php';
				//Add Geolocate metadata
				$glElem = $newDoc->createElement('geoLocate');
				$glElem->appendChild($newDoc->createElement('dataSourcePrimaryName',$this->geolocateVariables['cogename']));
				$glElem->appendChild($newDoc->createElement('dataSourceSecondaryName',$this->geolocateVariables['cogedescr']));
				$glElem->appendChild($newDoc->createElement('targetCommunityName',$this->geolocateVariables['cogecomm']));
				#if(isset($this->geolocateVariables['targetcommunityidentifier'])) $glElem->appendChild($newDoc->createElement('targetCommunityIdentifier',''));
				$glElem->appendChild($newDoc->createElement('specimenHyperlinkBase',$urlPathPrefix));
				$glElem->appendChild($newDoc->createElement('specimenHyperlinkParameter','occid'));
				$glElem->appendChild($newDoc->createElement('specimenHyperlinkValueField','Id'));
				$metaElem->appendChild($glElem);
			}
		}
		$addMetaElem = $newDoc->createElement('additionalMetadata');
		$addMetaElem->appendChild($metaElem);
		$rootElem->appendChild($addMetaElem);

		return $newDoc;
	}

	//Generate Data files
	private function writeOccurrenceFile(){
		$this->logOrEcho("Creating occurrence file (".date('h:i:s A').")... ");
		$filePath = $this->targetPath.$this->ts.'-occur'.$this->fileExt;
		$fh = fopen($filePath, 'w');
		if(!$fh){
			$this->logOrEcho('ERROR establishing output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}
		$hasRecords = false;
		
		if(!$this->occurrenceFieldArr){
			$this->occurrenceFieldArr = DwcArchiverOccurrence::getOccurrenceArr($this->schemaType, $this->extended);
		}
		//Output records
		$this->applyConditions();
		$sql = DwcArchiverOccurrence::getSqlOccurrences($this->occurrenceFieldArr['fields'],$this->conditionSql,$this->getTableJoins());
		if(!$sql) return false;
		//Output header
		$fieldArr = $this->occurrenceFieldArr['fields'];
		if($this->schemaType == 'dwc'){
			unset($fieldArr['localitySecurity']);
		}
		if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
			unset($fieldArr['collId']);
		}
		$fieldOutArr = array();
		if($this->schemaType == 'coge'){
			//Convert to GeoLocate flavor
			$glFields = array('specificEpithet'=>'Species','scientificNameAuthorship'=>'ScientificNameAuthor','recordedBy'=>'Collector','recordNumber'=>'CollectorNumber',
				'year'=>'YearCollected','month'=>'MonthCollected','day'=>'DayCollected','decimalLatitude'=>'Latitude','decimalLongitude'=>'Longitude',
				'minimumElevationInMeters'=>'MinimumElevation','maximumElevationInMeters'=>'MaximumElevation','maximumDepthInMeters'=>'MaximumDepth','minimumDepthInMeters'=>'MinimumDepth',
				'occurrenceRemarks'=>'Notes','dateEntered','dateLastModified','collId','recordId','references');
			foreach($fieldArr as $k => $v){
				if(array_key_exists($k,$glFields)){
					$fieldOutArr[] = $glFields[$k];
				} 
				else{
					$fieldOutArr[] = strtoupper(substr($k,0,1)).substr($k,1);
				}
			}
		}
		else{
			$fieldOutArr = array_keys($fieldArr);
		}
		$this->writeOutRecord($fh,$fieldOutArr);
		if(!$this->collArr){
			//Collection array not previously primed by source  
			$sql1 = 'SELECT DISTINCT o.collid FROM omoccurrences o ';
			if($this->conditionSql){
				$sql1 .= $this->getTableJoins().$this->conditionSql;
			}
			$rs1 = $this->conn->query($sql1);
			$collidStr = '';
			while($r1 = $rs1->fetch_object()){
				$collidStr .= ','.$r1->collid;
			}
			$rs1->free();
			if($collidStr) $this->setCollArr(trim($collidStr,','));
		}

		//Populate Upper Taxonomic data
		$this->setUpperTaxonomy();
		
		//echo $sql; exit;
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			$this->setServerDomain();
			$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
			
			while($r = $rs->fetch_assoc()){
				//Set occurrence GUID based on GUID target
				$guidTarget = $this->collArr[$r['collid']]['guidtarget'];
				if($guidTarget == 'catalogNumber'){
					$r['occurrenceID'] = $r['catalogNumber'];
				}
				elseif($guidTarget == 'symbiotaUUID'){
					$r['occurrenceID'] = $r['recordId'];
				}
				if($this->limitToGuids && (!$r['occurrenceID'] || !$r['basisOfRecord'])){
					// Skip record because there is no occurrenceID guid
					continue;
				}
				$hasRecords = true;
				//Protect sensitive records
				if($this->redactLocalities && $r["localitySecurity"] == 1 && !in_array($r['collid'],$this->rareReaderArr)){
					$protectedFields = array();
					foreach($this->securityArr as $v){
						if(array_key_exists($v,$r) && $r[$v]){
							$r[$v] = '';
							$protectedFields[] = $v;
						}
					}
					if($protectedFields){
						$r['informationWithheld'] = trim($r['informationWithheld'].'; field values redacted: '.implode(', ',$protectedFields),' ;');
					}
				}
				
				if($urlPathPrefix) $r['t_references'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				$r['recordId'] = 'urn:uuid:'.$r['recordId'];
				//Add collection GUID based on management type
				$managementType = $this->collArr[$r['collid']]['managementtype'];
				if($managementType && $managementType == 'Live Data'){
					if(array_key_exists('collectionID',$r) && !$r['collectionID']){
						$guid = $this->collArr[$r['collid']]['collectionguid'];
						if(strlen($guid) == 36) $guid = 'urn:uuid:'.$guid;
						$r['collectionID'] = $guid;
					}
				}
				if($this->schemaType == 'dwc'){
					unset($r['localitySecurity']);
				}
				if($this->schemaType == 'dwc' || $this->schemaType == 'backup'){
					unset($r['collid']);
				}
				//Add upper taxonomic data
				if($r['family'] && $this->upperTaxonomy){
					$famStr = strtolower($r['family']);
					if(isset($this->upperTaxonomy[$famStr]['o'])){
						$r['t_order'] = $this->upperTaxonomy[$famStr]['o'];
					}
					if(isset($this->upperTaxonomy[$famStr]['c'])){
						$r['t_class'] = $this->upperTaxonomy[$famStr]['c'];
					}
					if(isset($this->upperTaxonomy[$famStr]['p'])){
						$r['t_phylum'] = $this->upperTaxonomy[$famStr]['p'];
					}
					if(isset($this->upperTaxonomy[$famStr]['k'])){
						$r['t_kingdom'] = $this->upperTaxonomy[$famStr]['k'];
					}
				} 
				//print_r($r); exit;
				$this->encodeArr($r);
				$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating occurrence file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}

		fclose($fh);
		if(!$hasRecords){
			$filePath = false;
			//$this->writeOutRecord($fh,array('No records returned. Modify query variables to be more inclusive.'));
			$this->logOrEcho("No records returned. Modify query variables to be more inclusive. \n");
		}
		$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
		return $filePath;
	}
	
	public function getOccurrenceFile(){
		if(!$this->targetPath) $this->setTargetPath();
		$filePath = $this->writeOccurrenceFile();
		return $filePath;
	}
	
	private function writeDeterminationFile(){
		$this->logOrEcho("Creating identification file (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->ts.'-det'.$this->fileExt, 'w');
		if(!$fh){
			$this->logOrEcho('ERROR establishing output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}
		
		if(!$this->determinationFieldArr){
			$this->determinationFieldArr = DwcArchiverDetermination::getDeterminationArr($this->schemaType,$this->extended);
		}
		//Output header
		$this->writeOutRecord($fh,array_keys($this->determinationFieldArr['fields']));
		
		//Output records
		$sql = DwcArchiverDetermination::getSqlDeterminations($this->determinationFieldArr['fields'],$this->conditionSql);
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			while($r = $rs->fetch_assoc()){
				$r['recordId'] = 'urn:uuid:'.$r['recordId'];
				$this->encodeArr($r);
				$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating identification file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
			
		fclose($fh);
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function writeImageFile(){

		$this->logOrEcho("Creating image file (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->ts.'-images'.$this->fileExt, 'w');
		if(!$fh){
			$this->logOrEcho('ERROR establishing output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}

		if(!$this->imageFieldArr) $this->imageFieldArr = DwcArchiverImage::getImageArr($this->schemaType);
		
		//Output header
		$this->writeOutRecord($fh,array_keys($this->imageFieldArr['fields']));
		
		//Output records
		$sql = DwcArchiverImage::getSqlImages($this->imageFieldArr['fields'], $this->conditionSql, $this->redactLocalities, $this->rareReaderArr);
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			
			$this->setServerDomain();
			$urlPathPrefix = $this->serverDomain.$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
			
			$localDomain = '';
			if(isset($GLOBALS['IMAGE_DOMAIN']) && $GLOBALS['IMAGE_DOMAIN']){
				$localDomain = $GLOBALS['IMAGE_DOMAIN'];
			}
			else{
				$localDomain = $this->serverDomain;
			}

			while($r = $rs->fetch_assoc()){
				if(substr($r['identifier'],0,1) == '/') $r['identifier'] = $localDomain.$r['identifier'];
				if(substr($r['accessURI'],0,1) == '/') $r['accessURI'] = $localDomain.$r['accessURI'];
				if(substr($r['thumbnailAccessURI'],0,1) == '/') $r['thumbnailAccessURI'] = $localDomain.$r['thumbnailAccessURI'];
				if(substr($r['goodQualityAccessURI'],0,1) == '/') $r['goodQualityAccessURI'] = $localDomain.$r['goodQualityAccessURI'];

				if($this->schemaType != 'backup'){
					if(stripos($r['rights'],'http://creativecommons.org') === 0){
						$r['webstatement'] = $r['rights'];
						$r['rights'] = '';
						if(!$r['usageterms']){
							if($r['webstatement'] == 'http://creativecommons.org/publicdomain/zero/1.0/'){
								$r['usageterms'] = 'CC0 1.0 (Public-domain)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by/3.0/'){
								$r['usageterms'] = 'CC BY (Attribution)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by-sa/3.0/'){
								$r['usageterms'] = 'CC BY-SA (Attribution-ShareAlike)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by-nc/3.0/'){
								$r['usageterms'] = 'CC BY-NC (Attribution-Non-Commercial)';
							}
							elseif($r['webstatement'] == 'http://creativecommons.org/licenses/by-nc-sa/3.0/'){
								$r['usageterms'] = 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)';
							}
						}
					}
					if(!$r['usageterms']) $r['usageterms'] = 'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)';
				}
				$r['providermanagedid'] = 'urn:uuid:'.$r['providermanagedid'];
				$r['associatedSpecimenReference'] = $urlPathPrefix.'collections/individual/index.php?occid='.$r['occid'];
				$r['type'] = 'StillImage';
				$r['subtype'] = 'Photograph';
				$extStr = strtolower(substr($r['accessURI'],strrpos($r['accessURI'],'.')+1));
				if($r['format'] == ''){
					if($extStr == 'jpg' || $extStr == 'jpeg'){
						$r['format'] = 'image/jpeg';
					}
					elseif($extStr == 'gif'){
						$r['format'] = 'image/gif';
					}
					elseif($extStr == 'png'){
						$r['format'] = 'image/png';
					}
					elseif($extStr == 'tiff' || $extStr == 'tif'){
						$r['format'] = 'image/tiff';
					}
					else{
						$r['format'] = '';
					}
				}
				$r['metadataLanguage'] = 'en';
				//Load record array into output file
				//$this->encodeArr($r);
				//$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating image file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}
		
		fclose($fh);
		
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function writeAttributeFile(){
		$this->logOrEcho("Creating occurrence Attributes file as MeasurementsOrFact extension (".date('h:i:s A').")... ");
		$fh = fopen($this->targetPath.$this->ts.'-attr'.$this->fileExt, 'w');
		if(!$fh){
			$this->logOrEcho('ERROR establishing output file ('.$filePath.'), perhaps target folder is not readable by web server.');
			return false;
		}

		if(!$this->attributeFieldArr) $this->attributeFieldArr = DwcArchiverAttribute::getFieldArr();

		//Output header
		$this->writeOutRecord($fh,array_keys($this->attributeFieldArr['fields']));

		//Output records
		$sql = DwcArchiverAttribute::getSql($this->attributeFieldArr['fields'],$this->conditionSql);
		//echo $sql; exit;
		if($rs = $this->conn->query($sql,MYSQLI_USE_RESULT)){
			while($r = $rs->fetch_assoc()){
				$this->encodeArr($r);
				$this->addcslashesArr($r);
				$this->writeOutRecord($fh,$r);
			}
			$rs->free();
		}
		else{
			$this->logOrEcho("ERROR creating attribute (MeasurementOrFact file: ".$this->conn->error."\n");
			$this->logOrEcho("\tSQL: ".$sql."\n");
		}

		fclose($fh);
    	$this->logOrEcho("Done!! (".date('h:i:s A').")\n");
	}

	private function writeOutRecord($fh,$outputArr){
		if($this->delimiter == ","){
			fputcsv($fh, $outputArr);
		}
		else{
			foreach($outputArr as $k => $v){
				$outputArr[$k] = str_replace($this->delimiter,'',$v);
			}
			fwrite($fh, implode($this->delimiter,$outputArr)."\n");
		}
	}

	public function deleteArchive($collID){
		//Remove archive instance from RSS feed 
		$rssFile = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'webservices/dwc/rss.xml';
		if(!file_exists($rssFile)) return false;
		$doc = new DOMDocument();
		$doc->load($rssFile);
		$cElem = $doc->getElementsByTagName("channel")->item(0);
		$items = $cElem->getElementsByTagName("item");
		foreach($items as $i){
			if($i->getAttribute('collid') == $collID){
				$link = $i->getElementsByTagName("link");
				$nodeValue = $link->item(0)->nodeValue;
				$filePath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/');
				$filePath1 = $filePath.'content/dwca'.substr($nodeValue,strrpos($nodeValue,'/'));
				if(file_exists($filePath1)) unlink($filePath1);
				$emlPath1 = str_replace('.zip','.eml',$filePath1);
				if(file_exists($emlPath1)) unlink($emlPath1);
				//Following lines temporarly needed to support previous versions 
				$filePath2 = $filePath.'collections/datasets/dwc'.substr($nodeValue,strrpos($nodeValue,'/'));
				if(file_exists($filePath2)) unlink($filePath2);
				$emlPath2 = str_replace('.zip','.eml',$filePath2);
				if(file_exists($emlPath2)) unlink($emlPath2);
				$cElem->removeChild($i);
			}
		}
		$doc->save($rssFile);
		//Remove DWCA path from database
		$sql = 'UPDATE omcollections SET dwcaUrl = NULL WHERE collid = '.$collID;
		if(!$this->conn->query($sql)){
			$this->logOrEcho('ERROR nullifying dwcaUrl while removing DWCA instance: '.$this->conn->error);
			return false;
		}
		return true;
	}

	//getters, setters, and misc functions
	private function setUpperTaxonomy(){
		if(!$this->upperTaxonomy){
			$sqlOrder = 'SELECT t.sciname AS family, t2.sciname AS taxonorder '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '. 
				'WHERE t.rankid = 140 AND t2.rankid = 100';
			$rsOrder = $this->conn->query($sqlOrder);
			while($rowOrder = $rsOrder->fetch_object()){
				$this->upperTaxonomy[strtolower($rowOrder->family)]['o'] = $rowOrder->taxonorder;
			}
			$rsOrder->free();
			
			$sqlClass = 'SELECT t.sciname AS family, t2.sciname AS taxonclass '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 60';
			$rsClass = $this->conn->query($sqlClass);
			while($rowClass = $rsClass->fetch_object()){
				$this->upperTaxonomy[strtolower($rowClass->family)]['c'] = $rowClass->taxonclass;
			}
			$rsClass->free();
			
			$sqlPhylum = 'SELECT t.sciname AS family, t2.sciname AS taxonphylum '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 30';
			$rsPhylum = $this->conn->query($sqlPhylum);
			while($rowPhylum = $rsPhylum->fetch_object()){
				$this->upperTaxonomy[strtolower($rowPhylum->family)]['p'] = $rowPhylum->taxonphylum;
			}
			$rsPhylum->free();
			
			$sqlKing = 'SELECT t.sciname AS family, t2.sciname AS kingdom '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
				'WHERE t.rankid = 140 AND t2.rankid = 10';
			$rsKing = $this->conn->query($sqlKing);
			while($rowKing = $rsKing->fetch_object()){
				$this->upperTaxonomy[strtolower($rowKing->family)]['k'] = $rowKing->kingdom;
			}
			$rsKing->free();
		}
	}

	public function setSchemaType($type){
		//dwc, symbiota, backup, coge
		if(in_array($type, array('dwc','backup','coge'))){
			$this->schemaType = $type;
		}
		else{
			$this->schemaType = 'symbiota';
		}
	}
	
	public function setLimitToGuids($testValue){
		if($testValue) $this->limitToGuids = true;
	}

	public function setExtended($e){
		$this->extended = $e;
	} 

	public function setDelimiter($d){
		if($d == 'tab' || $d == "\t"){
			$this->delimiter = "\t";
			$this->fileExt = '.tab';
		}
		elseif($d == 'csv' || $d == 'comma' || $d == ','){
			$this->delimiter = ",";
			$this->fileExt = '.csv';
		}
		else{
			$this->delimiter = $d;
			$this->fileExt = '.txt';
		}
	}

	public function setIncludeDets($includeDets){
		$this->includeDets = $includeDets;
	}
	
	public function setIncludeImgs($includeImgs){
		$this->includeImgs = $includeImgs;
	}
	
	public function setIncludeAttributes($include){
		$this->includeAttributes = $include;
	}
	
	public function setRedactLocalities($redact){
		$this->redactLocalities = $redact;
	}

	public function setRareReaderArr($approvedCollid){
		if(is_array($approvedCollid)){ 
			$this->rareReaderArr = $approvedCollid;
		}
		elseif(is_string($approvedCollid)){
			$this->rareReaderArr = explode(',',$approvedCollid);
		}
	}

	public function setCharSetOut($cs){
		$cs = strtoupper($cs);
		if($cs == 'ISO-8859-1' || $cs == 'UTF-8'){
			$this->charSetOut = $cs;
		}
	}
	
	public function setGeolocateVariables($geolocateArr){
		$this->geolocateVariables = $geolocateArr;
	}

	public function setServerDomain($domain = ''){
		if($domain){
			$this->serverDomain = $domain;
		}
		elseif(!$this->serverDomain){
			$this->serverDomain = "http://";
			if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $this->serverDomain = "https://";
			$this->serverDomain .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $this->serverDomain .= ':'.$_SERVER["SERVER_PORT"];
		}
	}

	public function getServerDomain(){
		$this->setServerDomain();
		return $this->serverDomain;
	}

	protected function utf8EncodeArr($inArr){
		$retArr = $inArr;
		if($this->charSetSource == 'ISO-8859-1'){
			foreach($retArr as $k => $v){
				if(is_array($v)){
					$retArr[$k] = $this->utf8EncodeArr($v);
				}
				elseif(is_string($v)){
					if(mb_detect_encoding($v,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
						$retArr[$k] = utf8_encode($v);
					}
				}
				else{
					$retArr[$k] = $v;
				}
			}
		}
		return $retArr;
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
		if($inStr && $this->charSetSource){
			if($this->charSetOut == 'UTF-8' && $this->charSetSource == 'ISO-8859-1'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif($this->charSetOut == "ISO-8859-1" && $this->charSetSource == 'UTF-8'){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
		}
		return $retStr;
	}
	
	private function addcslashesArr(&$arr){
		foreach($arr as $k => $v){
			if($v) $arr[$k] = addcslashes($v,"\n\r\\");
		}
	}
}
?>