<?php
require_once($SERVER_ROOT.'/classes/WebServiceBase.php');
require_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');

class WsOccurEditor extends WebServiceBase{

	private $occid;
	private $dwcArr = array();
	private $editType = 1;		//1 = occurrence, 2 = identification annotation...
	private $source;
	private $editor;
	private $origTimestamp;
	private $approvedFields = array();
	private $fieldTranslation = array();

	public function __construct(){
		parent::__construct(null, 'write');
		$this->approvedFields = array('catalognumber', 'othercatalognumbers', 'occurrenceid','family', 'sciname',
			'scientificnameauthorship', 'identifiedby', 'dateidentified', 'identificationreferences',
			'identificationremarks', 'taxonremarks', 'identificationqualifier', 'typestatus', 'recordedby', 'recordnumber',
			'associatedcollectors', 'eventdate', 'year', 'month', 'day','verbatimeventdate', 'habitat', 'substrate', 'fieldnumber',
			'occurrenceremarks', 'associatedtaxa', 'verbatimattributes',
			'dynamicproperties', 'reproductivecondition', 'cultivationstatus', 'establishmentmeans',
			'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
			'country', 'stateprovince', 'county', 'municipality', 'locality', 'localitysecurity', 'localitysecurityreason',
			'decimallatitude', 'decimallongitude','geodeticdatum', 'coordinateuncertaintyinmeters',
			'locationremarks', 'verbatimcoordinates', 'georeferencedby', 'georeferenceprotocol', 'georeferencesources',
			'georeferenceverificationstatus', 'georeferenceremarks', 'minimumelevationinmeters', 'maximumelevationinmeters',
			'verbatimelevation', 'disposition', 'language', 'duplicatequantity',
			'basisofrecord', 'processingstatus', 'recordenteredby');
		$this->fieldTranslation = array('species'=>'specificepithet','scientificnameauthor'=>'scientificnameauthorship','collector'=>'recordedby','collectornumber'=>'recordnumber',
			'yearcollected'=>'year','monthcollected'=>'month','daycollected'=>'day','latitude'=>'decimallatitude','longitude'=>'decimallongitude',
			'minimumelevation'=>'minimumelevationinmeters','maximumelevation'=>'maximumelevationinmeters','minimumdepth'=>'minimumdepthinmeters',
			'maximumdepth'=>'maximumdepthinmeters','notes'=>'occurrenceremarks');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function applyEdit(){
		if($this->editType == 1) return $this->applyOccurrenceEdit();
		if($this->editType == 2) return $this->applyIdentificationEdit();
	}

	private function applyOccurrenceEdit(){

		//Get current record
		$sql = 'SELECT '.implode(',',array_keys($this->dwcArr)).' FROM omoccurrences WHERE occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		$oldValueArr = $rs->fetch_assoc();
		$rs->free();
		if(!$oldValueArr) return '{"Result":[{"Status":"FAILURE","Error":"Occurrence identifier not valid"}]}';

		//limit to fields where new values are different than old values
		$vettedOldValues = array();
		$vettedNewValues = array();
		foreach($this->dwcArr as $symbField => $value){
			if(array_key_exists($symbField, $oldValueArr) && $value != $oldValueArr[$symbField]){
				$vettedOldValues[$symbField] = $oldValueArr[$symbField]; 
				$vettedNewValues[$symbField] = $value;
			}
		}
		if(!$vettedNewValues) return '{"Result":[{"Status":"FAILURE","Error":"No targetted values have changed"}]}';
		
		$appliedStatus = 0;
		//Custom adjustments applied per project based on source
		if($this->source == 'geolocate'){
			//Only edit coordinate and georeference detail field
			//Activate only if target coordinates are null
			if(!isset($vettedNewValues['decimallatitude']) && !isset($vettedNewValues['decimallongitude'])){
				//Abort edits because decimalLatitude and decimalLongitude are NULL or unchanged
				return '{"Result":[{"Status":"FAILURE","Error":"both decimalLatitude and decimalLongitude are NULL or unchanged"}]}';
			}
			else{
				$approvedGeolocateFields = array('decimallatitude','decimallongitude','geodeticdatum','coordinateuncertaintyinmeters','georeferencedby',
					'georeferenceprotocol','georeferencesources','georeferenceverificationstatus','georeferenceremarks');
				$vettedOldValues = array_intersect_key($vettedOldValues, array_flip($approvedGeolocateFields));
				$vettedNewValues = array_intersect_key($vettedNewValues, array_flip($approvedGeolocateFields));
				if(isset($vettedOldValues['decimallatitude']) || isset($vettedOldValues['decimallongitude'])){
					//There is already values in the current lat/long field, thus don't apply editing in omoccurrence table
					$appliedStatus = 0;
				}
				else{
					$appliedStatus = 1;
				}
			}
		}

		//Version edits by adding into omoccurrevisions table
		$sql2 = 'INSERT INTO omoccurrevisions(occid,oldValues,newValues,externalSource,externalEditor,reviewStatus,appliedStatus,externalTimestamp) '.
			'VALUES('.$this->occid.',"'.$this->cleanInStr(json_encode($vettedOldValues)).'","'.$this->cleanInStr(json_encode($vettedNewValues)).'",'.
			($this->source?'"'.$this->cleanInStr($this->source).'"':'NULL').','.($this->editor?'"'.$this->cleanInStr($this->editor).'"':'NULL').
			',1,'.$appliedStatus.','.($this->origTimestamp?'"'.$this->origTimestamp.'"':'NULL').')';
		//echo $sql2; exit;
		if($this->conn->query($sql2)){
			//By default, external edits will not be applied unless they are coming from an authorative source
			if($appliedStatus){
				$sqlIns = '';
				foreach($vettedNewValues as $k => $v){
					$sqlIns .= ', '.$k.' = "'.$this->cleanInStr($v).'" ';
				}
				$sql3 = 'UPDATE omoccurrences SET'.substr($sqlIns, 1).'WHERE occid = '.$this->occid;
				//echo $sql3; exit;
				if(!$this->conn->query($sql3)){
					$this->logOrEcho('ERROR activating edit within occurrence table (occid: '.$this->occid.'): '.$this->conn->error);
					return '{"Result":[{"Status":"FAILURE","Error":"ERROR activating edit within occurrence table: '.addslashes($this->conn->error).'"}]}';
				}
			}
			return '{"Result":[{"Status":"SUCCESS","Message":"Edits successfully submitted '.($appliedStatus?'and activated':'but not activated').'"}]}';
		}
		else{
			$this->logOrEcho('ERROR updating occurrence revisions table (occid: '.$this->occid.'): '.$this->conn->error);
			return '{"Result":[{"Status":"FAILURE","Error":"ERROR updating occurrence revisions table: '.addslashes($this->conn->error).'"}]}';
		}
	}

	private function applyIdentificationEdit(){
		
	}

	//Setters and getters
	public function setOccid($id){
		if(is_numeric($id)) $this->occid = $id;
	}
	
	public function setRecordID($guid){
		$guid = preg_replace("/[^A-Za-z0-9\-]/","",$guid);
		$sql = 'SELECT occid FROM guidoccurrences WHERE guid = "'.$guid.'"';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->occid = $r->occid;
		}
		$rs->free();
		if($this->occid){
			return true;
		}
		else{
			return false;
		}
	}

	public function setDwcArr($dwcObj){
		$recArr = json_decode($dwcObj,true);
		if($recArr){
			$recArr = array_change_key_case($recArr);
			//Translate fields
			foreach($this->fieldTranslation as $otherName => $symbName){
				if(array_key_exists($otherName, $recArr) && !array_key_exists($symbName, $recArr)){
					$recArr[$symbName] = $recArr[$otherName];
					unset($recArr[$otherName]);
				}
			}
			//Filter out unapproved fields
			$recArr = array_intersect_key($recArr, array_flip($this->approvedFields));
			$this->dwcArr = OccurrenceUtilities::occurrenceArrayCleaning($recArr);
			if($this->dwcArr) return true;
		}
		return false;
	}

	public function setEditType($type){
		if(is_numeric($type)){
			$editType = $type;
		}
		elseif(strtolower($type) == 'occurrence'){
			$editType = 1;
		}
		elseif(strtolower($type) == 'identification'){
			$editType = 2;
		}
	}

	public function setSource($s){ 	
		$this->source = $this->cleanInStr($s);
	}

	public function setEditor($e){
		$this->editor = $this->cleanInStr($e);
	}

	public function setOrigTimestamp($ts){
		$this->origTimestamp = $this->cleanInStr($ts);
	}
}
?>