<?php
/**
 * This file contains code to read an rdf/xml file that uses OA, OAD, and dwcFP
 * as used in the New England Vascular Plants TCN.  For each OA:Annotation, this
 * code extracts the dwc and dwcFP occurrence data and populate PDOs reflecting 
 * Symbiota's Occurrence and Determination tables.  
 * 
 * The purpose of this file is to allow Symbiota records to be created from 
 * NEVP New Occurrence Annotation files uploaded with image links.  
 *
 * For an example new occurrence annotation in the expected form see: 
 * http://sourceforge.net/p/filteredpush/svn/2221/tree/FP-Design/trunk/ontologies/oa/NEVP_specimen_example.xml
 *
 * Example invocation: 
 *
 * $xml = XMLReader::open($file);
 * if ($xml->read) {
 *    $hasAnnotation = $xml->lookupNamespace("oa");
 *    $hasDataAnnotation = $xml->lookupNamespace("oad");
 *    $hasdwcFP = $xml->lookupNamespace("dwcFP");
 *    // Note: contra the PHP xmlreader documentation, lookupNamespace
 *    // returns the namespace string not a boolean.
 *    if ($xml->node=="rdf:XML" && $hasAnnotation && $hasDataAnnotation && $hasdwcFP) {
 *       $processor = new NEVPProcessor();
 *       $result = $processor->process($file);
 *    }
 * }
 * 
 * @author Paul J. Morris
 */

include_once("$serverRoot/classes/OmOccurrences.php");
include_once("$serverRoot/classes/OmOccurDeterminations.php");
if (file_exists("$serverRoot/classes/Gazeteer.php")) { 
   include_once("$serverRoot/classes/Gazeteer.php");
}

// Check to see if OmOccurrences matches current schema version.
$testOcc = new OmOccurrences();
if (!$testOcc->checkSchema()) { 
   echo "[Warning: classes/OmOccurrences.php does not match the Symbiota schema version.]\n";
   echo "[Warning: Ingest of data may fail.  Contact a Symbiota developer.]\n";
}

if (!class_exists(Result)) {
    class Result {
        public $couldparse = false; // document compliant with expectations
	    public $success = false; // completed without errors
	    public $errors = "";     // error messages
        public $recordcount = 0;  // number of core records encountered
	    public $successcount = 0; // number of records successfully processed
	    public $failurecount = 0; // number of records with errors in processing
	    public $insertcount = 0; // number of records successfully inserted
	    public $updatecount = 0; // number of records successfully updated
    }
}

$result = new Result();

class EVENT { 
   public $eventdate;
   public $verbatimeventdate;
   public $startday;
   public $startmonth;
   public $startyear;

   public function setEventDate($date) { 
      $this->eventdate = $date;
      if (strlen($date)>3) { $this->startyear = substr($date,0,4);  }
      if (strlen($date)>6) { $this->startmonth = substr($date,5,2); }
      if (strlen($date)>9) { $this->startday = substr($date,8,2);   }
   }

   public function write() { 
      return "$this->verbatimeventdate [$this->eventdate]";
   } 
}

class IDENTIFICATION { 
   public $family;
   public $taxonrank;
   public $scientificname;
   public $genus;
   public $specificepithet;
   public $identificationqualifier;
   public $scientificnameauthorship;
   public $infraspecificrank;
   public $infraspecificepithet;
   public $infraspecificauthor;
   public $nomenclaturalcode = "ICNAFP";
   public $identifiedby;
   public $dateidentified;
   public $typestatus;
   public $isfiledundernameincollection; 
   public $taxonid;

   public function write() { 
     $date = "";
     if ($this->identificationdate != null) { $date = $this->identificationdate->writeAll(); }
     $id = new OmOccurDeterminations();
     $id->keyvalueset('occid',$this->occid);
     $id->keyvalueset('sciname',$this->scientificname);
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
     // TODO: Handle taxonid in authorityfile

     $id->save();
   }
 

}

class ANNOTATION { 
   public $id;
   public $expectation;
   public $annotatedat;
   public $motivations = array();

}

class OCCURRENCE { 
   public $occurrenceid = null;
   public $basisofrecord = "PreservedSpecimen";
   public $collectioncode = null;
   public $collectionid;
   public $institutioncode = null;
   public $catalognumber;
   public $datelastmodified;
   public $identifications = array();
   public $recordedby;
   public $recordnumber; // collectornumber
   public $country = "United States of America";  // default NEVP TCN value
   public $stateprovince;
   public $county;
   public $municipality;
   public $locality = null;
   public $notes;
   public $minimumelevationinmeters = null;
   public $maximumelevationinmeters = null;
   public $collectingevent = null;
   public $datemodified;
   public $storagelocation;
   public $associatedmedia = array();

   public function getFiledUnderID() { 
        $result = null;
        foreach($this->identifications as $id) { 
           if ($result==null) { 
              $result = $id;
           }
           if ($id->isfiledundernameincollection==$this->collectioncode) { 
              $result = $id;
           }
        }
        return $result;
   }

   public function write() { 
       global $createNewRec, $result; 
       for ($i=0; $i<count($this->identifications); $i++) { 
           $this->identifications[$i]->write();
       }
       $occ = new OmOccurrences();
       $det = new OmOccurDeterminations();
       $collid = $occ->lookupCollId($this->institutioncode, $this->collectioncode);
       // Record exists if there is a matching DarwinCore triplet.  
       $exists = $occ->loadByDWCTriplet($this->institutioncode, $this->collectioncode, $this->catalognumber);
       if (!$exists) {
           // Image upload process in TcnImageTools creates omoccurrences records where 
           // collectioncode is not populated, but collid is. Need to handle both cases.
           $exists = $occ->loadByCollidCatalog($collid, $this->catalognumber);
           // TODO: Question for Ed: Find out if institutioncode and collectioncode should be populated if we
           // end up here.
       }
       if (!$exists) { 
       	  $occ = new OmOccurrences();
          $occ->setcollid($collid);
          $occ->setcollectionCode($this->collectioncode);
          $occ->setinstitutionCode($this->institutioncode);
          $occ->setcatalogNumber($this->catalognumber);
          $occ->setprocessingStatus("unprocessed");
       }
       $occ->setoccurrenceId($this->occurrenceid);
       $occ->setbasisOfRecord($this->basisofrecord);
       // TODO: Handle datemodified
       // TODO: Lookup collector with botanist guid
       // TODO: Split collectors on pipe.
       if (strpos(";",$this->recordedby)>0) { 
           // split on first semicolon.
           $occ->setrecordedBy(substr($this->recordedby,0,strpos(";",$this->recordedby)));
           $occ->setassociatedCollectors(substr($this->recordedby,strpos(";",$this->recordedby),strlen($this->recordedby)));
       } else { 
           $occ->setrecordedBy($this->recordedby);
           $occ->setassociatedCollectors("");
       }
       if ($this->recordnumber!=null) { $occ->setrecordNumber($this->recordnumber); }
       $occ->settypeStatus($this->getTypeStatusList());
       if ($this->countryname!=null) { $occ->setcountry($this->countryname); } 
       if ($this->stateprovince!=null) { $occ->setstateProvince($this->stateprovince); }
       if ($this->county!=null) { $occ->setcounty($this->county); }
       $occ->setmunicipality($this->municipality);
       if ($this->locality!=null) { $occ->setlocality($this->locality); } 
       if ($this->collectingevent != null) { 
          $occ->seteventDate($this->collectingevent->eventdate);
          $occ->setyear($this->collectingevent->startyear);
          $occ->setmonth($this->collectingevent->startmonth);
          $occ->setday($this->collectingevent->startday);
          $occ->setverbatimEventDate($this->collectingevent->verbatimeventdate);
       }
       if (class_exists(Gazeteer)) {
          $gazeteer = new Gazeteer();
          // For NEVP, lookup georeference from state+municipality
          $georef = $gazeteer->lookup($this->stateprovince, $this->municipality,$occ->getyear());
          if ($georeference->foundMatch===true) { 
             $occ->setdecimalLatitude($georef->latitude);
             $occ->setdecimalLongitude($georef->longitude);
             $occ->setcoordinateUncertaintyInMeters($georef->errorRadius);
             $occ->setgeodeticDatum($georef->geodeticDatum);
             $occ->setgeoreferencedBy($georef->georeferencedBy);
             $occ->setgeoreferenceProtocol($georef->georeferenceProtocol);
             $occ->setgeoreferenceSources($georef->georeferenceSources);
          }
       }
       if ($this-minimumelevationinmeters!=null) { $occ->setminimumElevationInMeters($this->minimumelevationinmeters); }
       if ($this-maximumelevationinmeters!=null) { $occ->setmaximumElevationInMeters($this->maximumelevationinmeters); }
       $filedUnder = $this->getFiledUnderID();
       if ($filedUnder!=null) { 
           $occ->setsciname($filedUnder->scientificname);
           if ($det->lookupAcceptedTID($filedUnder->scientificname)>0) {
               $occ->settidinterpreted($det->lookupAcceptedTID($filedUnder->scientificname));
           }
           $occ->setfamily($filedUnder->family);
           if (strlen($occ->getfamily())==0) {
               $occ->setfamily($det->lookupFamilyForTID($det->lookupTID($filedUnder->scientificname)));
           }
           $occ->setgenus($filedUnder->genus);
           $occ->setspecificEpithet($filedUnder->specificepithet);
           $occ->setinfraSpecificEpithet($filedUnder->infraspecificepithet);
           $occ->setidentificationQualifier($filedUnder->identificationqualifier);
           $occ->setscientificNameAuthorship($filedUnder->scientificnameauthorship);
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
       // TODO: Handle motivations (transcribing and NSF abstract numbers).
       
       if (!$exists || $createNewRec==1) {
           // if record exists, then TcnImageConf variable createNewRec specifies policy for update.
           if ($occ->save()) {
               if ($exists) {
                   // echo "Updated occid: [".$occ->getoccid()."]\n";
                   $result->updatecount++;
               } else {
                   // echo "Added occid: [".$occ->getoccid()."]\n";
                   $result->insertcount++;
               }
               foreach($this->identifications as $id) {
                    $id->occid = $occ->getoccid();
                    $id->write();
               }
               // TODO: Create image records from associatedmedia array.

               $result->successcount++;
           } else {
               $result->errors .= "Error: [".$occ->errorMessage()."]\n";
           }
       } else {
           echo "Skipping, record exists and specified policy is to not update. [".$occ->getoccid()."]\n";
       }
       

   }

   public function addIdentification($id) { 
      $this->identifications[] = clone $id;
   }

   public function getTypeStatusList() {
      $result = "";
      $statusValues = array();
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

class NEVPProcessor { 
    
    public $currentAnnotation = null;
    public $currentOcc = null;
    public $currentId = null;
    public $currentDate = null;
    public $currentTag = "";
    public $acount = 0;       // number of annotations expected
    public $countfound = 0;  // number of annotations found  
    
    function startElement($parser, $name, $attrs) {
        global $depth, $currentOcc, $currentTag, $currentId, $currentDate;
        $currentTag = $name;
        switch ($name) { 
           case "OA:ANNOTATION":
              $currentAnnotation = new ANNOTATION();
              $currentAnnotation->id = $attrs['RDF:ABOUT'];
              break;
           case "OAD:EXPECTATION_INSERT":
              $currentAnnotation->expectation = $name;
              break;
           case "OAD:EXPECTATION_UPDATE":
              $currentAnnotation->expectation = $name;
              break;
           case "OAD:TRANSCRIBING":
           case "OA:TRANSCRIBING":
              $currentAnnotation->motivations[] = $name;
              break;
           case "OA:MOTIVATEDBY":
              if (strlen($attrs['RDF:RESOURCE'])>0) { 
                  $currentAnnotation->motivations[] = $attrs['RDF:RESOURCE'];
              }
              break;
           case "DWCFP:OCCURRENCE":
              $currentOcc = new OCCURRENCE();
              $currentOcc->occurrenceid = $attrs['RDF:ABOUT'];
              break;
           case "DWCFP:IDENTIFICATION":
              $currentId = new IDENTIFICATION();
              break;
           case "DWCFP:EVENT":
              $currentDate = new EVENT();
              break; 
           case "DWCFP:HASCOLLECTIONBYID": 
              $currentOcc->collectionid = $attrs['RDF:RESOURCE'];
              break;
           case "DWCFP:HASASSOCIATEDMEDIA":
              $currentOcc->associatedmedia[] = $attrs['RDF:RESOURCE'];
              break;
           case "DWCFP:HASBASISOFRECORD": 
              $basis = $attrs['RDF:RESOURCE'];
              if (strpos($basis,'http://')!==false) { 
                  $bits = explode('/',$basis);
                  $basis = $bits[count($bits)-1];
              }
              $currentOcc->basisofrecord = $basis;
              break;
        }  
  
        if (!in_array($parser,$depth)) { 
          $depth[$parser] = 0;
        }
        $depth[$parser]++;
    }
    
    function endElement($parser, $name) {
        global $depth, $currentOcc, $currentId, $currentDate, $result;
        $depth[$parser]--;
        
        switch ($name) { 
           case "DWCFP:OCCURRENCE":
              // Nothing to do, assuming one occurrence per annotation.
              $result->recordcount++;
              break;
           case "DWCFP:IDENTIFICATION":
              $currentOcc->addIdentification($currentId);
              $currentId = null;
              break;
           case "DWCFP:HASCOLLECTINGEVENT":
              $currentOcc->collectingevent = clone $currentDate;
              $currentDate = null;
              break;
           case "OA:ANNOTATION":
              $this->countfound++;
              $currentOcc->write();
              $currentOcc = null;
              break;
        }
    }
    
    function value($parser, $data) { 
       global $depth, $currentOcc, $currentTag, $currentId, $currentDate;
    
// Top level of document: [RDF:RDF][RDF:DESCRIPTION][RDFS:COMMENT][RDFS:COMMENT][CO:COUNT]
// Annotation: [OA:ANNOTATION][OA:HASTARGET][OA:SPECIFICRESOURCE][OA:HASSELECTOR][OAD:KVPAIRQUERYSELECTOR][DWC:COLLECTIONCODE][DWC:INSTITUTIONCODE][OA:HASSOURCE][OA:HASBODY]
// New Occurrence: [DWCFP:OCCURRENCE][DC:TYPE][DWCFP:HASBASISOFRECORD][DWC:CATALOGNUMBER][DWCFP:HASCOLLECTIONBYID][DWC:COLLECTIONCODE][DWCFP:HASIDENTIFICATION][DWCFP:IDENTIFICATION][DWCFP:ISFILEDUNDERNAMEINCOLLECTION][DWC:SCIENTIFICNAME][DWC:GENUS][DWC:SPECIFICEPITHET][DWCFP:INFRASPECIFICRANK][DWC:INFRASPECIFICEPITHET][DWC:SCIENTIFICNAMEAUTHORSHIP][DWC:IDENTIFICATIONQUALIFIER][DWCFP:USESTAXON][DWCFP:TAXON][DWCFP:HASTAXONID][DWC:RECORDEDBY][DWC:RECORDNUMBER][DWCFP:HASCOLLECTINGEVENT][DWCFP:EVENT][DWC:EVENTDATE][DWC:VERBATIMEVENTDATE][DWC:COUNTRY][DWC:STATEPROVINCE][DWC:COUNTY][DWC:MUNICIPALITY][DC:CREATED][DWC:MODIFIED][OBO:OBI_0000967][DWCFP:HASASSOCIATEDMEDIA]
// Annotation, following occurrence in body: [OAD:HASEVIDENCE][OAD:HASEXPECTATION][OAD:EXPECTATION_INSERT][OA:MOTIVATEDBY][OAD:TRANSCRIBING][OA:MOTIVATEDBY][OA:ANNOTATEDBY][FOAF:PERSON][FOAF:MBOX_SHA1SUM][FOAF:NAME][FOAF:WORKPLACEHOMEPAGE][OA:ANNOTATEDAT][OA:SERIALIZEDBY][FOAF:AGENT][FOAF:NAME][OA:SERIALIZEDAT]
// [DC:TYPE] -- nowhere to put in symbiota
// [DWCFP:HASIDENTIFICATION] -- nowhere to put in symbiota
// TODO: Extract annotator and serializer
       if ($data!=null) { $data = trim($data); } 
       switch ($currentTag) {
         case "CO:COUNT":
             if (strlen($data)>0) { 
                $this->acount = $data;
             }
             break;

         case "DWC:INSTITUTIONCODE": 
             $currentOcc->institutioncode .= $data;
             break;
         case "DWC:COLLECTIONCODE": 
             $currentOcc->collectioncode .= $data;
             break;
         case "DWC:CATALOGNUMBER": 
             $currentOcc->catalognumber .= $data;
             break;
         case "DC:CREATED":
             $currentOcc->datelastmodified .= $data;
             break;
         case "DWC:RECORDEDBY":
             $currentOcc->recordedby .= $data;
             break;
         case "DWC:RECORDNUMBER":
             $currentOcc->recordnumber .= $data;
             break;
         case "DWC:COUNTRY":
             $currentOcc->country .= $data;
             break;
         case "DWC:STATEPROVINCE":
             $currentOcc->stateprovince .= $data;
             break;
         case "DWC:COUNTY":
             $currentOcc->county .= $data;
             break;
         case "DWC:MUNICIPALITY":
             $currentOcc->muncipality .= $data;
             break;
         case "DWC:LOCALITY":
             $currentOcc->locality .= $data;
             break;
         case "NOTES":
             $currentOcc->notes .= $data;
             break;
         case "DWC:MINIMUMELEVATIONINMETERS":
             $currentOcc->minimumelevationinmeters .= $data;
             break;
         case "DWC:MAXIMUMELEVATIONINMETERS":
             $currentOcc->maximumelevationinmeters .= $data;
             break;
         case "DWC:MODIFIED":
             $currentOcc->datemodified .= $data;
             break;
         case "OBO:OBI_0000967":
             $currentOcc->storagelocation .= $data;
             break;

         case "DWC:SCIENTIFICNAME":
             $currentId->scientificname .= $data;
             break;
         case "DWC:FAMILY":
             $currentId->family .= $data;
             break;
         case "DWC:GENUS":
             $currentId->genus .= $data;
             break;
         case "DWC:IDENTIFICATIONQUALIFIER":
             $currentId->identificationqualifier .= $data;
             break;
         case "DWC:SPECIFICEPITHET":
             $currentId->specificepithet .= $data;
             break;
         case "DWC:SCIENTIFICNAMEAUTHORSHIP":
             $currentId->scientificnameauthorship .= $data;
             break;
         case "DWC:INFRASPECIFICEPITHET":
             $currentId->infraspecificepithet .= $data;
             break;
         case "DWCFP:INFRASPECIFICRANK":
             $currentId->infraspecificrank .= $data;
             break;
         case "DWC:NOMENCLATURALCODE":
             $currentId->nomenclaturalcode .= $data;
             break;
         case "DWC:IDENTIFIEDBY":
             $currentId->identifier .= $data;
             break;
         case "DWC:DATEIDENTIFED":
             $currentId->dateidentified .= $data;
             break;
         case "DWCFP:HASTAXONID":
             $currentId->taxonid .= $data;
             break;
         case "DWCFP:ISFILEDUNDERNAMEINCOLLECTION":
             $currentId->isfiledundernameincollection .= $data;
             break;
         case "DWC:TYPESTATUS":
             $currentId->typestatus .= $data;
             break;
    
         case "DWC:EVENTDATE":
             if (strlen(trim($data))>0) { 
                $currentDate->setEventDate($data);
             }
             break;
         case "DWC:VERBATIMEVENTDATE":
             $currentDate->verbatimeventdate .= $data;
             break;
         case "DWC:YEAR":
             $currentDate->startyear .= $data;
             break;
         case "DWC:DAY":
             $currentDate->startday .= $data;
             break;
         case "DWC:MONTH":
             $currentDate->startmonth .= $data;
             break;
       }
    
    }
    
    public function process($file) {
        global $result,$depth;
        $result = new Result();
        $result->couldparse = true;

        $parser = xml_parser_create('UTF-8');

        $depth = array();

        xml_set_element_handler($parser, "NEVPProcessor::startElement", "NEVPProcessor::endElement");
        xml_set_character_data_handler($parser, "NEVPProcessor::value");
        
        if (!($fp = fopen($file, "r"))) {
            $result->couldparse = false;
        }
        
        while ($data = fread($fp, 4096)) {
            if (!xml_parse($parser, $data, feof($fp))) {
                $result->couldparse = false;
                // sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
            }
        }

        if ($this->countfound!=$this->acount) { 
           // TODO: Report discrepancy.
           echo "ERROR: Expected $this->acount annotations, found $this->countfound";
        }
    
        xml_parser_free($parser);
    
        return $result;
    }
    
}

?>
