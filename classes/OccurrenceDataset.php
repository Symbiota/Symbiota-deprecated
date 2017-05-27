<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/DwcArchiverCore.php');

class OccurrenceDataset {

	private $conn;
	private $symbUid;
	private $collArr = array();
	private $isAdmin = 0;
	private $newDatasetId = 0;

	private $errorArr = array();

	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getDatasetMetadata($dsid){
		$retArr = array();
		if($this->symbUid && $dsid){
			//Get and return individual dataset
			$sql = 'SELECT datasetid, name, notes, uid, sortsequence, initialtimestamp '.
				'FROM omoccurdatasets '.
				'WHERE (datasetid = '.$dsid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['name'] = $r->name;
				$retArr['notes'] = $r->notes;
				$retArr['uid'] = $r->uid;
				$retArr['sort'] = $r->sortsequence;
				$retArr['ts'] = $r->initialtimestamp;
			}
			$rs->free();
			//Get roles for current user
			$sql1 = 'SELECT role '.
				'FROM userroles '.
				'WHERE (tablename = "omoccurdatasets") AND (tablepk = '.$dsid.') AND (uid = '.$this->symbUid.') ';
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$retArr['roles'][] = $r1->role;
			}
			$rs1->free();
		}
		return $retArr;
	}

	public function getDatasetArr(){
		$retArr = array();
		if($this->symbUid){
			//Get datasets owned by user
			$sql = 'SELECT datasetid, name, notes, sortsequence, initialtimestamp '.
				'FROM omoccurdatasets '.
				'WHERE (uid = '.$this->symbUid.') '.
				'ORDER BY sortsequence,name';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['owner'][$r->datasetid]['name'] = $r->name;
				$retArr['owner'][$r->datasetid]['notes'] = $r->notes;
				$retArr['owner'][$r->datasetid]['sort'] = $r->sortsequence;
				$retArr['owner'][$r->datasetid]['ts'] = $r->initialtimestamp;
			}
			$rs->free();

			//Get shared datasets
			$sql1 = 'SELECT d.datasetid, d.name, d.notes, d.sortsequence, d.initialtimestamp, r.role '.
				'FROM omoccurdatasets d INNER JOIN userroles r ON d.datasetid = r.tablepk '.
				'WHERE (r.uid = '.$this->symbUid.') AND (r.role IN("DatasetAdmin","DatasetEditor","DatasetReader")) '.
				'ORDER BY sortsequence,name';
			//echo $sql1;
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$retArr['other'][$r1->datasetid]['name'] = $r1->name;
				$retArr['other'][$r1->datasetid]['role'] = $r1->role;
				$retArr['other'][$r1->datasetid]['notes'] = $r1->notes;
				$retArr['other'][$r1->datasetid]['sort'] = $r1->sortsequence;
				$retArr['other'][$r1->datasetid]['ts'] = $r1->initialtimestamp;
			}
			$rs1->free();
		}
		return $retArr;
	}

	public function editDataset($dsid,$name,$notes){
		$sql = 'UPDATE omoccurdatasets '.
			'SET name = "'.$this->cleanInStr($name).'", notes = "'.$this->cleanInStr($notes).'" '.
			'WHERE datasetid = '.$dsid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] = 'ERROR saving dataset edits: '.$this->conn->error;
			return false;
		}
		return true;
	}

	public function createDataset($name,$notes,$uid){
		$newId = '';
		$sql = 'INSERT INTO omoccurdatasets (name,notes,uid) '.
			'VALUES("'.$this->cleanInStr($name).'","'.$this->cleanInStr($notes).'",'.$uid.') ';
		if(!$this->conn->query($sql)){
			$this->errorArr[] = 'ERROR creating new dataset: '.$this->conn->error;
			return false;
		}
		else{
			$this->newDatasetId = $this->conn->insert_id;
		}
		return true;
	}

	public function mergeDatasets($targetArr){
		$dsArr = array();
		$sql = 'SELECT datasetid, name FROM omoccurdatasets '.
			'WHERE datasetid IN('.implode(',',$targetArr).')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$dsArr[$r->datasetid] = $r->name;
		}
		$rs->free();
		
		$targetDsid = array_shift($targetArr);
		$newName = '';
		//Rename target
		$sql2 = 'UPDATE omoccurdatasets SET name = "'.$newName.'" WHERE datasetid = '.$targetDsid;
		if($this->conn->query($sql2)){
			//Push occurrences to target
			$sql3 = 'UPDATE IGNORE omoccurdatasetlink SET datasetid = '.$targetDsid.' WHERE datasetid IN('.implode(',',$targetArr).')';
			if($this->conn->query($sql3)){
				//Delete occurrences that failed to transfer due to already being present   
				$sql4 = 'DELETE FROM omoccurdatasets WHERE datasetid IN('.implode(',',$targetArr).')';
				if(!$this->conn->query($sql4)){
					$this->errorArr[] = 'WARNING: Unable to remove extra datasets: '.$this->conn->error;
					return false;
				}
			}
			else{
				$this->errorArr[] = 'FATAL ERROR: Unable to transfer occurrence records into target dataset: '.$this->conn->error;
				return false;
			}
		}
		else{
			$this->errorArr[] = 'FATAL ERROR: Unable to rename target dataset in prep for merge: '.$this->conn->error;
			return false;
		}
		return true;
	}

	public function cloneDatasets($targetArr,$uid){
		$status = true;
		$sql = 'SELECT datasetid, name, notes, sortsequence FROM omoccurdatasets '.
			'WHERE datasetid IN('.implode(',',$targetArr).')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			//Create new name and ensure it doesn't already exist for owner
			$newName = $r->name.' - Copy';
			$newNameTemp = $newName;
			$cnt = 1;
			do{
				$sql1 = 'SELECT datasetid FROM omoccurdatasets WHERE name = "'.$newNameTemp.'" AND uid = '.$uid;
				$nameExists = false;
				$rs1 = $this->conn->query($sql1);
				while($rs1->fetch_object()){
					$newNameTemp = $newName.' '.$cnt;
					$nameExists = true;
					$cnt++;
				}
				$rs1->free();
			}while($nameExists);
			$newName = $newNameTemp;
			//Add to database
			$sql2 = 'INSERT INTO omoccurdatasets(name, notes, sortsequence, uid) '.
				'VALUES("'.$newName.'","'.$r->notes.'",'.($r->sortsequence?$r->sortsequence:'""').','.$uid.')';
			if($this->conn->query($sql2)){
				$this->newDatasetId = $this->conn->insert_id;
				//Duplicate all records wtihin new dataset
				$sql3 = 'INSERT INTO omoccurdatasetlink(occid, datasetid, notes) '.
					'SELECT occid, '.$this->newDatasetId.', notes FROM omoccurdatasetlink WHERE datasetid = '.$r->datasetid;
				if(!$this->conn->query($sql3)){
					$this->errorArr[] = 'ERROR: Unable to clone dataset links into new datasets: '.$this->conn->error;
					$status = false;
				}
			}
			else{
				$this->errorArr[] = 'ERROR: Unable to create new dataset within clone method: '.$this->conn->error;
				$status = false;
			}
			
			$dsArr[$r->datasetid] = $r->name;
		}
		$rs->free();
		return $status;
	}

	public function deleteDataset($dsid){
		//Delete users
		$sql1 = 'DELETE FROM userroles '.
			'WHERE (role IN("DatasetAdmin","DatasetEditor","DatasetReader")) AND (tablename = "omoccurdatasets") AND (tablepk = '.$dsid.') ';
		//echo $sql;
		if(!$this->conn->query($sql1)){
			$this->errorArr[] = 'ERROR deleting user: '.$this->conn->error;
			return false;
		}
		
		//Delete datasets
		$sql2 = 'DELETE FROM omoccurdatasets WHERE datasetid = '.$dsid;
		if(!$this->conn->query($sql2)){
			$this->errorArr[] = 'ERROR: Unable to delete target datasets: '.$this->conn->error;
			return false;
		}
		return true;
		
		//Delete dataset records
		$sql3 = 'DELETE FROM omoccurdatasetlink WHERE datasetid = '.$dsid;
		if(!$this->conn->query($sql3)){
			$this->errorArr[] = 'ERROR: Unable to delete target datasets: '.$this->conn->error;
			return false;
		}
		return true;
	}

	public function getUsers($datasetId){
		$retArr = array();
		$sql = 'SELECT u.uid, r.role, CONCAT_WS(", ",u.lastname,u.firstname) as username '.
				'FROM userroles r INNER JOIN users u ON r.uid = u.uid '.
				'WHERE r.role IN("DatasetAdmin","DatasetEditor","DatasetReader") '.
				'AND (r.tablename = "omoccurdatasets") AND (r.tablepk = '.$datasetId.')';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->role][$r->uid] = $r->username;
		}
		$rs->free();
		return $retArr;
	}

	public function addUser($dsid,$userStr,$role){
		$status = true;
		$uid = 0;
		if(preg_match('/\D\[#(.+)\]$/',$userStr,$m)){
			$uid = $m[1];
		}
		if(!$uid || !is_numeric($uid)){
			$sql = 'SELECT uid FROM userlogin WHERE username = "'.$userStr.'"';
			$rs = $this->conn->query();
			if($r = $rs->fetch_object()){
				$uid = $r->uid;
			}
			else{
				$this->errorArr[] = 'ERROR adding new user; unable to locate user name: '.$userStr;
				return false;
			}
			$rs->free();
		}
		if($uid && is_numeric($uid)){
			$sql1 = 'INSERT INTO userroles(uid,role,tablename,tablepk) '.
				'VALUES('.$uid.',"'.$role.'","omoccurdatasets",'.$dsid.')';
			if(!$this->conn->query($sql1)){
				$this->errorArr[] = 'ERROR adding new user: '.$this->conn->error;
				return false;
			}
		}
		else{
			$this->errorArr[] = 'ERROR adding new user; unable to locate user name(2): '.$userStr;
			return false;
		}
		return $status;
	}
	
	public function deleteUser($dsid,$uid,$role){
		$status = true;
		$sql = 'DELETE FROM userroles '.
			'WHERE (uid = '.$uid.') AND (role = "'.$role.'") AND (tablename = "omoccurdatasets") AND (tablepk = '.$dsid.') ';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$this->errorArr[] = 'ERROR deleting user: '.$this->conn->error;
			return false;
		}
		return $status;
	}
	
	public function getOccurrences($datasetId){
		$retArr = array();
		if($datasetId){
			$sql = 'SELECT o.occid, o.catalognumber, o.occurrenceid ,o.othercatalognumbers, '.
				'o.sciname, o.family, o.recordedby, o.recordnumber, o.eventdate, '.
				'o.country, o.stateprovince, o.county, o.locality, o.decimallatitude, o.decimallongitude, dl.notes '.
				'FROM omoccurrences o INNER JOIN omoccurdatasetlink dl ON o.occid = dl.occid '.
				'WHERE dl.datasetid = '.$datasetId;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->catalognumber) $retArr[$r->occid]['catnum'] = $r->catalognumber;
				elseif($r->occurrenceid) $retArr[$r->occid]['catnum'] = $r->occurrenceid;
				elseif($r->othercatalognumbers) $retArr[$r->occid]['catnum'] = $r->othercatalognumbers;
				else $retArr[$r->occid]['catnum'] = '';
				$sciname = $r->sciname;
				if($r->family) $sciname .= ' ('.$r->family.')';
				$retArr[$r->occid]['sciname'] = $sciname;
				$collStr = $r->recordedby.' '.$r->recordnumber;
				if($r->eventdate) $collStr .= ' ['.$r->eventdate.']';
				$retArr[$r->occid]['coll'] = $collStr;
				$retArr[$r->occid]['loc'] = trim($r->country.', '.$r->stateprovince.', '.$r->county.', '.$r->locality,', ');
			}
			$rs->free();
		}
		return $retArr; 
	}

	public function removeSelectedOccurrences($datasetId, $occArr){
		$status = true;
		if($datasetId && $occArr){
			$sql = 'DELETE FROM omoccurdatasetlink '.
				'WHERE (datasetid = '.$datasetId.') AND (occid IN('.implode(',',$occArr).'))';
			if(!$this->conn->query($sql)){
				$this->errorArr[] = 'ERROR deleting selected occurrences: '.$this->conn->error;
				return false;
			}
		}
		return $status;
	}
	
	public function addSelectedOccurrences($datasetId, $occArr){
		$status = true;
		if($datasetId && $occArr){
			foreach($occArr as $v){
				$sql = 'INSERT INTO omoccurdatasetlink (occid,datasetid) '.
					'VALUES("'.$v.'",'.$datasetId.') ';
				if(!$this->conn->query($sql)){
					$this->errorArr[] = 'ERROR adding selected occurrences: '.$this->conn->error;
					return false;
				}
			}
		}
		return $status;
	}

	public function exportDataset($dsid){
		//Get occurrence records
		$zip = (array_key_exists('zip',$_POST)?$_POST['zip']:0);
		$format = $_POST['format'];
		$extended = (array_key_exists('extended',$_POST)?$_POST['extended']:0);
	
		$redactLocalities = 1;
		$rareReaderArr = array();
		if($IS_ADMIN || array_key_exists("CollAdmin", $userRights)){
			$redactLocalities = 0;
		}
		elseif(array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$redactLocalities = 0;
		}
		else{
			if(array_key_exists('CollEditor', $userRights)){
				$rareReaderArr = $userRights['CollEditor'];
			}
			if(array_key_exists('RareSppReader', $userRights)){
				$rareReaderArr = array_unique(array_merge($rareReaderArr,$userRights['RareSppReader']));
			}
		}
		$dwcaHandler = new DwcArchiverCore();
		$dwcaHandler->setCharSetOut($cSet);
		$dwcaHandler->setSchemaType($schema);
		$dwcaHandler->setExtended($extended);
		$dwcaHandler->setDelimiter($format);
		$dwcaHandler->setVerboseMode(0);
		$dwcaHandler->setRedactLocalities($redactLocalities);
		if($rareReaderArr) $dwcaHandler->setRareReaderArr($rareReaderArr);

		$occurManager = new OccurrenceManager();
		$dwcaHandler->setCustomWhereSql($occurManager->getSqlWhere());

		$outputFile = null;
		if($zip){
			//Ouput file is a zip file
			$includeIdent = (array_key_exists('identifications',$_POST)?1:0);
			$dwcaHandler->setIncludeDets($includeIdent);
			$includeImages = (array_key_exists('images',$_POST)?1:0);
			$dwcaHandler->setIncludeImgs($includeImages);
			$includeAttributes = (array_key_exists('attributes',$_POST)?1:0);
			$dwcaHandler->setIncludeAttributes($includeAttributes);
				
			$outputFile = $dwcaHandler->createDwcArchive('webreq');
			
		}
		else{
			//Output file is a flat occurrence file (not a zip file)
			$outputFile = $dwcaHandler->getOccurrenceFile();
		}
		//ob_start();
		$contentDesc = '';
		if($schema == 'dwc'){
			$contentDesc = 'Darwin Core ';
		}
		else{
			$contentDesc = 'Symbiota ';
		}
		$contentDesc .= 'Occurrence ';
		if($zip){
			$contentDesc .= 'Archive ';
		}
		$contentDesc .= 'File';
		header('Content-Description: '.$contentDesc);
		
		if($zip){
			header('Content-Type: application/zip');
		}
		elseif($format == 'csv'){
			header('Content-Type: text/csv; charset='.$charset);
		}
		else{
			header('Content-Type: text/html; charset='.$charset);
		}
		
		header('Content-Disposition: attachment; filename='.basename($outputFile));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($outputFile));
		ob_clean();
		flush();
		//od_end_clean();
		readfile($outputFile);
		unlink($outputFile);
		
	}

	//General setters and getters
	public function getCollName($collId){
		$collName = '';
		if($collId){
			if(!$this->collArr) $this->setCollMetadata($collId);
			$collName = $this->collArr['collname'].' ('.$this->collArr['instcode'].($this->collArr['collcode']?':'.$this->collArr['collcode']:'').')';
		}
		return $collName;
	}

	private function setCollMetadata($collId){
		$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype '.
			'FROM omcollections WHERE collid = '.$collId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$this->collArr['instcode'] = $r->institutioncode;
				$this->collArr['collcode'] = $r->collectioncode;
				$this->collArr['collname'] = $r->collectionname;
				$this->collArr['colltype'] = $r->colltype;
			}
			$rs->free();
		}
	}

	public function setSymbUid($uid){
		$this->symbUid = $uid;
	}
	
	public function setIsAdmin($isAdmin){
		$this->isAdmin = $isAdmin;
	}
	
	public function getErrorArr(){
		return $this->errorArr;
	}
	
	public function getDsId(){
		return $this->newDatasetId;
	}

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>