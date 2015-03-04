<?php

/**
 * This file contains code to read an xml file compliant with the GPI/LAPI/Aluka schema
 * as used in the Global Plants Initiative project, and populate PDOs reflecting 
 * Symbiota's Occurrence and Determination tables.  
 * 
 * The purpose of this file is to allow Symbiota records to be created from 
 * GPI batch.xml files uploaded with images.  This will allow institutions that have
 * tools for creating GPI batch.xml files from data records to use these tools
 * to share images and data with Symbiota.
 *
 * Example invocation: 
 *
 * $xml = XMLReader::open($file);
 * $lapischema = "lapi_schema_v2.xsd";
 * $xml->setParserProperty(XMLReader::VALIDATE, true);
 * $xml->setSchema($lapischema);
 * if($xml->read())  {
 *    if ($xml->name=="DataSet") { 
 *       $processor = new GPIProcessor();
 *       $result = $processor->process($file);
 *    }
 * }
 *
 */

// Include PDO definitions
// Used instead of the occurrence editor manager 
include_once("$serverRoot/classes/OmOccurrences.php");
include_once("$serverRoot/classes/OmOccurDeterminations.php");

// Check to see if included classes match the current schema version.
$uptodate = true;
$testOcc = new OmOccurrences();
if (!$testOcc->checkSchema()) { 
   echo "[Warning: classes/OmOccurrences.php does not support the Symbiota schema version.]\n";
   $uptodate = false;
}
$testOccDet = new OmOccurDeterminations();
if (!$testOccDet->checkSchema()) { 
   echo "[Warning: classes/OmOccurDeterminations.php does not support the Symbiota schema version.]\n";
   $uptodate = false;
}
if (!$uptodate) { 
   echo "[Warning: Ingest of data may fail.  Contact a Symbiota developer.]\n";
}

if (!class_exists('Result')) {
   /**
    * To record and sumarize the results of processing an xml file. 
    * 
    * @author mole
    *
    */
    class Result {
        public $couldparse = false; // document compliant with expectations
        public $success = false; // completed without errors
        public $errors = "";     // error messages
        public $recordcount = 0;  // number of core records encountered
        public $successcount = 0; // number of records successfully processed
        public $failurecount = 0; // number of records with errors in processing
        public $insertcount = 0; // number of records successfully inserted
        public $updatecount = 0; // number of records successfully updated
        public $imagecount = 0 ;  // number of images records encountered
        public $imagefailurecount = 0 ;  // number of images with errors in processing
        public $imageinsertcount = 0 ;  //  number of images inserted
        public $imageupdatecount = 0 ;  //  number of images updated

        function __construct() {
           $couldparse = false; // document compliant with expectations
           $success = false; // completed without errors
           $errors = "";     // error messages
           $recordcount = 0;  // number of core records encountered
           $successcount = 0; // number of records successfully processed
           $failurecount = 0; // number of records with errors in processing
           $insertcount = 0; // number of records successfully inserted
           $updatecount = 0; // number of records successfully updated
           $imagecount = 0 ;  // number of images records encountered
           $imagefailurecount = 0 ;  // number of images with errors in processing
           $imageinsertcount = 0 ;  //  number of images inserted
           $imageupdatecount = 0 ;  //  number of images updated
        }
    }
}

$result = new Result();

/**
 * Complex type for the top level concept in the GPI schema, a DataSet.
 * 
 * @author mole
 *
 */
class GPI_Dataset { 
	public $institutioncode = null;  // = dwc:collectionCode
	public $institutionname = null;  
	public $datesupplied = null;
	public $personname = null;
}

/**
 * Complex type for dates in lapi_schema_v2.xsd
 * 
 * @author mole
 *
 */
class GPI_ADate { 
   public $startday = null;
   public $startmonth = null;
   public $startyear = null;
   public $endday = null;
   public $endmonth = null;
   public $endyear = null;
   public $othertext = "";

   public function write() {
   	  $start = ""; 
   	  if ($this->startyear!=null) { $start .= $this->startyear; }
   	  if ($this->startmonth!=null) { $start .= "-" . $this->startmonth; }
   	  if ($this->startday!=null) { $start .= "-" . $this->startday; }
   	  $end = "";
   	  if ($this->endyear!=null) { $end .= $this->endyear; }
   	  if ($this->endmonth!=null) { $end .= "-" . $this->endmonth; }
   	  if ($this->endday!=null) { $end .= "-" . $this->endday; }
   	  
   	  $result = "";
   	  if (strlen($start)>0) {
   	  	  $result .= $start;
   	  } 
   	  if (strlen($end)>0) {
          if (trim($start)!=trim($end)) {
   	  	     $result .= "/$end";
          }
   	  } 
      return trim($result);
   }

   public function writeAll() { 
       $result = "";
       $date = $this->write();
       if (trim($date)==trim($this->othertext)) { 
          $result = $date;
       } else { 
          $result = trim($date . " " . $this->othertext);
       }
       return $result;
   }

   public function writeStart() {
   	  $start = ""; 
   	  if ($this->startyear!=null) { $start .= $this->startyear; }
   	  if ($this->startmonth!=null) { $start .= "-" . $this->startmonth; }
   	  if ($this->startday!=null) { $start .= "-" . $this->startday; }
   	  $result = "";
   	  if (strlen($start)>0) {
   	  	  $result .= $start;
   	  } 
      return trim($result);
   }

   public function clearValues() { 
   	   $this->startyear = null;
   	   $this->startmonth = null;
   	   $this->startday = null;
   	   $this->endyear = null;
   	   $this->endmonth = null;
   	   $this->endday=null;
   	   $this->othertext = "";;
   }
}

/**
 * Element Identification from lapi_schema_v2.xsd
 * 
 * Uses the PDO OmOccurDetermination to persist units into Symbiota omoccurdetermination records
 * for the determination history of a specimen.
 * 
 * Note that the GPI schema associates the type status with the name (this specimen has this
 * type status for this name), while Symbiota follows the flat darwin core association of a 
 * type status with an occurrence record (this specimen has a type status).
 * 
 * Note that GPI's genusqualifier and speciesqualifer don't map cleanly into dwc:identificationQualifier.
 * 
 * GPI's plantnamecode (local identifier) doesn't map into Symbiota determinations, it is placed
 * in identificationRemarks in square brackets if present.
 * 
 * @author mole
 *
 */
class GPI_Identification { 
   public $occid;
   public $family;
   public $genus;
   public $genusqualifier;
   public $species;
   public $speciesqualifier;
   public $author;
   public $infraspecificrank;
   public $infraspecificepithet;
   public $infraspecificauthor;
   public $plantnamecode;
   public $identifier;
   public $identificationdate = null;
   public $typestatus;
   public $storedundername; 

   public function write() { 
     $date = "";
     if ($this->identificationdate != null) { $date = $this->identificationdate->writeAll(); } 
     $id = new OmOccurDeterminations();
     $id->keyvalueset('occid',$this->occid);
     $id->keyvalueset('sciname',$this->getSciName());
     if (strlen($this->infraspecificauthor)>0) { 
         $id->keyvalueset('scientificNameAuthorship',$this->infraspecificauthor);
     } else { 
         $id->keyvalueset('scientificNameAuthorship',$this->author);
     }
     $code ="";
     if ($this->plantnamecode!=null && strlen($this->plantnamecode)>0) {
     	$code = "[" + $this->plantnamecode + "]";
     } 
     $id->keyvalueset('identificationRemarks',$this->typestatus + $code);
     $id->keyvalueset('identifiedBy',$this->identifier);
     $id->keyvalueset('dateIdentified',$date);
     $id->keyvalueset('identificationQualifier',trim($this->genusqualifier . " " . $this->speciesqualifier));

     $id->save();
   } 
   
   public function getSciName() { 
       $result = "";
       if ($this->genus==null && $this->species==null) {
            $result = $this->family;
       } else {
           $result = trim($this->genus . " " . $this->species . " " . $this->infraspecificrank . " " . $this->infraspecificepithet);
       }
       return $result;
   }

}

/**
 * Element Unit, representing a collection object, from lapi_schema_v2.xsd.
 * 
 * Uses the PDO OmOccurrence to persist units into Symbiota omoccurrence records.
 * 
 * @author mole
 *
 */
class GPI_Unit { 
   public $institutioncode; // Not in LAPI schema, needs to be supplied from elsewhere.
   public $collectioncode;  // Called institutionCode in LAPI schema.
   public $unitid;
   public $datelastmodified;
   public $identifications = array();
   public $collectors;
   public $collectornumber;
   public $collectiondate = NULL;
   public $unittypestatus;
   public $countryname;
   public $iso2letter;
   public $locality;
   public $notes;
   public $altitude;
   public $relatedunitid;

   public function write() { 
       global $createNewRec, $result; 
       $occ = new OmOccurrences();
       $det = new OmOccurDeterminations();
       $collid = $occ->lookupCollId(null, $this->collectioncode);
       // Record exists if there is a matching DarwinCore triplet.  
       $exists = $occ->loadByDWCTriplet($occ->lookupInstitutionCode($collid), $this->collectioncode, $this->unitid);
       if (!$exists) { 
           // Image upload process in TcnImageTools creates omoccurrences records where 
           // collectioncode is not populated, but collid is. Need to handle both cases.
           $exists = $occ->loadByCollidCatalog($collid, $this->unitid);
           // TODO: Question for Ed: Find out if institutioncode and collectioncode should be populated if we
           // end up here.
       }
       if (!$exists) { 
          // create new record
       	  $occ = new OmOccurrences();
          $occ->setcollid($collid);
          $occ->setcollectionCode($this->collectioncode);
          $occ->setinstitutionCode($occ->lookupInstitutionCode($collid));
          $occ->setcatalogNumber($this->unitid);
          $occ->setbasisOfRecord("PreservedSpecimen");
          $occ->setprocessingStatus("unprocessed");
          if ((strpos($this->getTypeStatusList(),"Photograph")!==false) || (strpos($this->getTypeStatusList(),"Drawing")!==false)) {
              // Handle case of Iconotypes.  
              // Note: GPI/LAPI schema doesn't have a standard means of identifying non-type drawings.
              $occ->setbasisOfRecord("StillImage");
          }
       }
       // Schema documentation suggests, but does not enforce, semicolon as separator for list of collectors.
       if (strpos(";",$this->collectors)>0) { 
           // split on first semicolon.
           $occ->setrecordedBy(substr($this->collectors,0,strpos(";",$this->collectors)));
           $occ->setassociatedCollectors(substr($this->collectors,strpos(";",$this->collectors),strlen($this->collectors)));
       } else { 
           $occ->setrecordedBy($this->collectors);
           $occ->setassociatedCollectors("");
       }
       if ($this->collectornumber!=null) { $occ->setrecordNumber($this->collectornumber); }
       $occ->settypeStatus($this->getTypeStatusList());
       if ($this->countryname!=null ) { $occ->setcountry($this->countryname); }
       if (substr($this->locality,0,1)=="{") { 
           // Depreciated, but there may be FH documents in the wild that put this into 
           // Locality rather than Notes.
       	   $locbits = json_decode($this->locality);
       	   $occ->setstateProvince($locbits->{'stateProvince'});
       	   $occ->setcounty($locbits->{'county'});
           $occ->setlocality($locbits->{'locality'});
       } else { 
           if ($this->locality!=null) { $occ->setlocality($this->locality); } 
       }
       if ($this->collectiondate!=NULL) { 
          $occ->seteventDate($this->collectiondate->writeStart());
          $occ->setyear($this->collectiondate->startyear);
          $occ->setmonth($this->collectiondate->startday);
          $occ->setday($this->collectiondate->startday);
          $occ->setverbatimEventDate($this->collectiondate->writeAll());
       } else { 
          $occ->setverbatimEventDate("[No Data]");
       }
       $filedUnder = $this->getFiledUnderID();
       if ($filedUnder!=null) {
           $occ->setsciname($filedUnder->getSciName());
           // Set locality security based on TID of accepted name of filed under name
           $sectid = $det->lookupAcceptedTID($filedUnder->getSciName());
           if ($sectid==null) { 
              $sectid = $det->lookupTID($filedUnder->getSciName());
           } 
           if ($sectid==null) { 
              $occ->setlocalitySecurity(0);
           } else { 
               $occ->setlocalitySecurity($det->checkSecurityStatus($sectid));
           }

           if ($det->lookupAcceptedTID($filedUnder->getSciName())>0) { 
               $occ->settidinterpreted($det->lookupAcceptedTID($filedUnder->getSciName()));
           }
           if ($filedUnder->family!="NoData") { 
               $occ->setfamily($filedUnder->family);
           }
           if (strlen($occ->getfamily())==0) { 
               $occ->setfamily($det->lookupFamilyForTID($det->lookupTID($filedUnder->getSciName())));
           }
           $occ->setgenus($filedUnder->genus);
           $occ->setspecificEpithet($filedUnder->species);
           $occ->setinfraSpecificEpithet($filedUnder->infraspecificepithet);
           $occ->setscientificNameAuthorship($filedUnder->author);
           // work out value for taxon rank.
           $occ->settaxonRank($filedUnder->infraspecificrank);
           if (strlen($filedUnder->infraspecificepithet)>0 && strlen($filedUnder->infraspecificrank)==0) { 
                $occ->settaxonRank("subspecies");
           } 
           if (strlen($filedUnder->infraspecificepithet)==0 && strlen($filedUnder->species)>0) { 
                $occ->settaxonRank("species");
           }
           if (strlen($filedUnder->infraspecificepithet)==0 && strlen($filedUnder->species)==0 && strlen($filedUnder->genus)>0) { 
                $occ->settaxonRank("genus");
           }
           if ($filedUnder->identificationdate!=NULL) { 
               $occ->setdateIdentified($filedUnder->identificationdate->writeAll()); 
           }
       }
       if (preg_match("/^[0-9.]+$/",$this->altitude)) { 
          $occ->setminimumElevationInMeters($this->altitude);
       } else { 
          if ($this->altitude!=null) { $occ->setverbatimElevation($this->altitude); } 
       }
       if (substr($this->notes,0,1)=="{") { 
       	   $bits = json_decode($this->notes);
           // Extended higher geography information
           if (array_key_exists('stateProvince',$bits)) { $occ->setstateProvince($bits->{'stateProvince'}); } 
           if (array_key_exists('county',$bits)) { $occ->setcounty($bits->{'county'}); } 
           if (array_key_exists('municipality',$bits)) { $occ->setmunicipality($bits->{'municipality'}); } 
           // Exsicatti
           $extitle = "";
           $exvolume = "";
           $exfasicle = "";
           $exnumber = "";
           $exauthor = "";
           if (array_key_exists('extitle',$bits)) { $extitle = $bits->{'extitle'}; } 
           if (array_key_exists('exvolume',$bits)) { $exvolume = $bits->{'exvolume'}; } 
           if (array_key_exists('fasicle',$bits)) { $exfasicle = $bits->{'fasicle'}; } 
           if (array_key_exists('exnumber',$bits)) { $exnumber = $bits->{'exnumber'}; } 
           if (array_key_exists('exauthor',$bits)) { $exauthor = $bits->{'exauthor'}; } 
           // TODO: Take Exsiccati data and link(/create?) records in Symbiota
       } else { 
          $occ->setoccurrenceRemarks($this->notes);
       }
       
       if (!$exists || $createNewRec==1) { 
           // if record exists, then TcnImageConf variable createNewRec specifies policy for update.
           if ($occ->save()) {
               if ($exists) {
                   //echo "Updated occid: [".$occ->getoccid()."]\n";
                   $result->successcount++;
                   $result->updatecount++;
               } else { 
                   //echo "Added occid: [".$occ->getoccid()."]\n";
                   $result->successcount++;
                   $result->insertcount++;
               }
               foreach($this->identifications as $id) { 
                    $id->occid = $occ->getoccid();
                    $id->write();
               }
           } else {
           	   $result->errors .= "Error: [".$occ->errorMessage()."]\n";   
               $result->failurecount++;
           }
       } else { 
       	   echo "Skipping, record exists and specified policy is to not update. [".$occ->getoccid()."]\n";   
       }
       
   }

   public function addIdentification($id) { 
      $this->identifications[] = clone $id;
   }

   public function getFiledUnderID() {
        $result = null;
        foreach($this->identifications as $id) {
           if ($result==null) {
              $result = $id;
           }
           if ($id->storedundername==1) {
              $result = $id;
           }
        }
        return $result;
   }

   /** Get a list of all the unique values in unittypestatus and identification.typestatus.
    *
    * @returns A string containing a comma separated list of the unique values of type status
    * associated with this unit.
    */
   public function getTypeStatusList() { 
      $result = "";
      $statusValues = array();
      if (strlen($this->unittypestatus > 1)) { 
         $result = $this->unittypestatus;
         $statusValues[$this->unittypestatus]=1;
      }
      $comma = "";
      foreach ($this->identifications as $id) { 
          if(strlen($id->typestatus)>1) { 
             if (!array_key_exists($id->typestatus,$statusValues)) { 
                $statusValues[$id->typestatus]=1;
                $result = $comma . $id->typestatus;
                $comma = ",";
             }
          }
      }
      return $result;
   }


}

/**
 * Supports processing a batch.xml file that is compliant with the GPI schema.
 * Populate PDOs with new records from that file and persist them.
 * 
 * @author mole
 *
 */
class GPIProcessor { 
    public $currentDataset = null;
    public $currentUnit = null;
    public $currentId = null;
    public $currentTag = "";
    
    function startElement($parser, $name, $attrs) {
        global $depth, $currentUnit, $currentTag, $currentId, $currentDate, $currentDataset, $result;
        $currentTag = $name;
        
        if ($name=="DATASET") { 
            $currentDataset = new GPI_Dataset();
        }
    
        if ($name=="UNIT") { 
            $currentUnit = new GPI_Unit();
            // extract the collection code from the DataSet (where it is called institutioncode).
            $currentUnit->collectioncode = $currentDataset->institutioncode;
            $result->recordcount++;
        }
    
        if ($name=="IDENTIFICATION") { 
            $currentId = new GPI_Identification();
        }
    
        if ($name=="COLLECTIONDATE" || $name=="IDENTIFICATIONDATE") { 
            $currentDate = new GPI_ADate();
        } 
    
        if (!in_array($parser,$depth)) { 
          $depth[$parser] = 0;
        }
        $depth[$parser]++;
    }
    
    function endElement($parser, $name) {
        global $depth, $currentUnit, $currentId, $currentDate, $result;
        $depth[$parser]--;
        
        if ($name=="DATASET") { 
        	// we are done.
        }
        
        if ($name=="UNIT") { 
           $currentUnit->write();
           $currentUnit = null;
        }
    
        if ($name=="IDENTIFICATION") { 
           $currentUnit->addIdentification($currentId);
           $currentId = null;
        } 
    
        if ($name=="COLLECTIONDATE") { 
            $currentUnit->collectiondate = clone $currentDate;
            $currentDate = null;
        } 
        if ($name=="IDENTIFICATONDATE") { 
            $currentId->identificationdate = clone $currentDate;
        } 
    
    }
    
    function value($parser, $data) { 
       global $depth, $currentUnit, $currentTag, $currentId, $currentDate, $currentDataset, $result;
    
       switch ($currentTag) { 
       	 case "INSTITUTIONCODE":
       	 	 $currentDataset->institutioncode = $data;
       	 	 break; 
       	 case "INSTITUTIONNAME":
       	 	 $currentDataset->institutionname = $data;
       	 	 break; 
       	 case "DATESUPPLIED":
       	 	 $currentDataset->datesupplied = $data;
       	 	 break; 
       	 case "PERSONNAME":
       	 	 $currentDataset->personname = $data;
       	 	 break; 
         case "UNITID": 
             $currentUnit->unitid .= $data;
             break;
         case "DATELASTMODIFIED":
             $currentUnit->datelastmodified .= $data;
             break;
         case "COLLECTORS":
             $currentUnit->collectors .= $data;
             break;
         case "COLLECTORNUMBER":
             $currentUnit->collectornumber .= $data;
             break;
         case "UNITTYPESTATUS":
             $currentUnit->unittypestatus .= $data;
             break;
         case "COUNTRYNAME":
             $currentUnit->countryname .= $data;
             break;
         case "ISO2LETTER":
             $currentUnit->iso2letter .= $data;
             break;
         case "LOCALITY":
             $currentUnit->locality .= $data;
             break;
         case "NOTES":
             $currentUnit->notes .= $data;
             break;
         case "ALTITUDE":
             $currentUnit->altitude .= $data;
             break;
         case "RELATEDUNITID":
             $currentUnit->relatedunitid .= $data;
             break;
         case "FAMILY":
             $currentId->family .= $data;
             break;
         case "GENUS":
             $currentId->genus .= $data;
             break;
         case "GENUSQUALIFIER":
             $currentId->genusqualifier .= $data;
             break;
         case "SPECIES":
             $currentId->species .= $data;
             break;
         case "SPECIESQUALIFIER":
             $currentId->speciesqualifier .= $data;
             break;
         case "AUTHOR":
             $currentId->author .= $data;
             break;
         // Aluka schema specifies infra- terms with a dash after infra.
         case "INFRA-SPECIFICEPITHET":
         case "INFRASPECIFICEPITHET":
             $currentId->infraspecificepithet .= $data;
             break;
         case "INFRA-SPECIFICRANK":
         case "INFRASPECIFICRANK":
             $currentId->infraspecificrank .= $data;
             break;
         case "INFRA-SPECIFICAUTHOR":
         case "INFRASPECIFICAUTHOR":
             $currentId->infraspecificauthor .= $data;
             break;
         case "PLANTNAMECODE":
             $currentId->plantnamecode .= $data;
             break;
         case "IDENTIFIER":
             $currentId->identifier .= $data;
             break;
         case "TYPESTATUS":
             $currentId->typestatus .= $data;
             break;
         case "STARTDAY":
             $currentDate->startday .= $data;
             break;
         case "STARTMONTH":
             $currentDate->startmonth .= $data;
             break;
         case "STARTYEAR":
             $currentDate->startyear .= $data;
             break;
         case "ENDDAY":
             $currentDate->endday .= $data;
             break;
         case "ENDMONTH":
             $currentDate->endmonth .= $data;
             break;
         case "ENDYEAR":
             $currentDate->endyear .= $data;
             break;
         case "OTHERTEXT":
             $currentDate->othertext .= $data;
             break;
       }
    
    }
    
    public function process($file) {
        global $result,$depth;
        $result = new Result();
        $result->couldparse = true;
    
        $parser = xml_parser_create('UTF-8');

        $depth = array();

        xml_set_element_handler($parser, "GPIProcessor::startElement", "GPIProcessor::endElement");
        xml_set_character_data_handler($parser, "GPIProcessor::value");
        
        if (!($fp = fopen($file, "r"))) {
            $result->couldparse = false;
            $result->success= false;
            // could not open XML input
            $result->errors .= "Error: Unable to open " . $file . " for reading.";
        } else { 
            while ($data = fread($fp, 4096)) {
                if (!xml_parse($parser, $data, feof($fp))) {
                    $result->couldparse = false;
                    $result->success = false;
                    $result->errors .= " XML error:" . xml_error_string(xml_get_error_code($xml_parser)). " on line " . xml_get_current_line_number($xml_parser) . ".";
                }
            } 
        }
    
        xml_parser_free($parser);
        return $result;
    }
    
}

?>