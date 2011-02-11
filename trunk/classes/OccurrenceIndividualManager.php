<?php
/*
 * 13 January 2011
 * E.E. Gilbert: egbot@asu.edu
 */

include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceIndividualManager {

	private $conn;
	private $occId;
    private $collId;
    private $dbpk;
	private $occArr = Array();

 	public function __construct($occid){
 		$this->occId = $occid;
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
   
 	public function setOccId($o){
		$this->occId = $o;
	}
	
	public function setCollId($id){
		$this->collId = $id;
	}
	
	public function setDbpk($pk){
		$this->dbpk = $pk;
	}

    public function getOccData($fieldKey = ""){
		if(!$this->occArr) $this->setOccArr();
		if($fieldKey){
			if(array_key_exists($fieldKey,$this->occArr)){
				return $this->occArr($fieldKey);
			}
			return;
		}
		return $this->occArr;
    }

	private function setOccArr(){
    	$sql = 'SELECT o.occid, c.collid, c.institutioncode, o.institutioncode AS secondaryinstcode, c.collectioncode, '.
    		'o.collectioncode AS secondarycollcode, c.collectionname, c.homepage, c.individualurl, c.contact, c.email, c.icon, '.
    		'o.occurrenceid, o.catalognumber, o.occurrenceremarks, o.tidinterpreted, o.family, o.sciname, '.
    		'o.scientificnameauthorship, o.identificationqualifier, o.identificationremarks, o.identificationreferences, '.
			'o.identifiedby, dateidentified, o.recordedby, o.associatedcollectors, o.recordnumber, '.
			'DATE_FORMAT(o.eventDate,"%d %M %Y") AS eventdate, DATE_FORMAT(MAKEDATE(YEAR(eventDate),enddayofyear),"%d %M %Y") AS eventdateend, '.
    		'o.verbatimeventdate, o.country, o.stateprovince, o.county, o.locality, '.
    		'o.minimumelevationinmeters, o.maximumelevationinmeters, o.verbatimelevation, o.localitysecurity, '.
			'o.decimallatitude, o.decimallongitude, o.geodeticdatum, o.coordinateuncertaintyinmeters, '.
			'o.verbatimcoordinates, o.georeferenceremarks, o.dynamicproperties, o.typestatus, o.dbpk, '.
			'o.habitat, o.associatedtaxa, o.reproductivecondition, o.cultivationstatus, o.establishmentmeans, '.
			'o.ownerinstitutioncode, o.othercatalognumbers, o.disposition, o.modified, observeruid '.
			'FROM omcollections AS c INNER JOIN omoccurrences o ON c.CollID = o.CollID ';
		if($this->occId){
			$sql .= 'WHERE o.occid = '.$this->occId;
		}
		elseif($this->collId && $this->dbpk){
			$sql .= 'WHERE o.collid = '.$this->collId.' AND o.dbpk = "'.$this->dbpk.'"';
		}
		else{
			return 'ERROR: Collection acronym was null or empty';
		}
		//echo '<div>SQL: '.$sql.'</div>';

		$result = $this->conn->query($sql);
		if(!$result) return 'ERROR: unable to return record data';
		$this->occArr = $result->fetch_assoc();
		if(!$this->occId) $this->occId = $this->occArr['occid'];
		if($this->occArr['secondaryinstcode'] && $this->occArr['secondaryinstcode'] == $this->occArr['institutioncode']){
			$sqlSec = 'SELECT c.collectionname, c.homepage, c.individualurl, c.contact, c.email, c.icon '.
			'FROM omcollsecondary '.
			'WHERE collid = '.$this->collId;
			$rsSec = $this->conn->query($sqlSec);
			if($r = $rsSec->fetch_object()){
				$this->occArr['collectionname'] = $r->collectionname;
				$this->occArr['homepage'] = $r->homepage;
				$this->occArr['individualurl'] = $r->individualurl;
				$this->occArr['contact'] = $r->contact;
				$this->occArr['email'] = $r->email;
				$this->occArr['icon'] = $r->icon;
			}
			$rsSec->close();
		}
		$this->addDeterminations();
		$this->addImages();
		$result->close();
    }

    private function addImages(){
    	global $imageDomain;
        $sql = 'SELECT imgid, url, thumbnailurl, originalurl, notes, caption FROM images '.
			'WHERE (occid = '.$this->occId.') ORDER BY sortsequence';
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$url = $row->url;
			$tnUrl = $row->thumbnailurl;
			$lgUrl = $row->originalurl;
			if($imageDomain && substr($url,0,1)=="/"){
				$url = $imageDomain.$url;
				if($lgUrl) $lgUrl = $imageDomain.$lgUrl;
				if($tnUrl) $tnUrl = $imageDomain.$tnUrl;
			}
			$this->occArr['imgs'][$imgId]['url'] = $url;
			$this->occArr['imgs'][$imgId]['tnurl'] = $tnUrl;
			$this->occArr['imgs'][$imgId]['lgurl'] = $lgUrl;
			$this->occArr['imgs'][$imgId]['caption'] = $row->caption;
		}
		$result->close();
    }

	private function addDeterminations(){
        $sql = 'SELECT detid, dateidentified, identifiedby, sciname, scientificnameauthorship, identificationqualifier, '.
        	'identificationreferences, identificationremarks '.
        	'FROM omoccurdeterminations '.
			'WHERE (occid = '.$this->occId.') ORDER BY sortsequence';
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$detId = $row->detid;
			$this->occArr['dets'][$detId]['date'] = $row->dateidentified;
			$this->occArr['dets'][$detId]['identifiedby'] = $row->identifiedby;
			$this->occArr['dets'][$detId]['sciname'] = $row->sciname;
			$this->occArr['dets'][$detId]['author'] = $row->scientificnameauthorship;
			$this->occArr['dets'][$detId]['qualifier'] = $row->identificationqualifier;
			$this->occArr['dets'][$detId]['ref'] = $row->identificationreferences;
			$this->occArr['dets'][$detId]['notes'] = $row->identificationremarks;
		}
		$result->close();
	}

	public function getChecklists($uRights){
		$returnArr = Array();
		//Get all public checklist names
		$sql = "SELECT DISTINCT c.name, c.clid ".
			"FROM (fmchecklists c INNER JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid) ".
			"INNER JOIN fmprojects p ON cpl.pid = p.pid ".
			"WHERE c.Access = 'public' ";
		if(array_key_exists("ClAdmin",$uRights)){
			$sql .= "AND c.clid IN(".implode(",",$uRights["ClAdmin"]).") ";
		}
		$sql .= "ORDER BY c.Name";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		$result->close();
		return $returnArr;
	}
}

?>