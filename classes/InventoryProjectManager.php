<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class InventoryProjectManager {

	private $con;
	private $projId;
	private $googleUrl;
	private $researchCoord = Array();
	private $surveyCoord = Array();
	private $isPublic = 1;

	public function __construct(){
		$this->con = MySQLiConnectionFactory::getCon("readonly");
		$this->googleUrl = "http://maps.google.com/maps/api/staticmap?size=120x150&maptype=terrain&sensor=false";
	}

	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}

	public function getProjectId(){
		return $this->projId;
	}
	
	public function setProj($proj){
		if($proj){
			if(is_numeric($proj)){
				$this->projId = $proj;
			}
			else{
				$sql = "SELECT p.pid FROM fmprojects p WHERE (p.projname = '".$proj."')";
				$rs = $this->con->query($sql);
				if($row = $rs->fetch_object()){
					$this->projId = $row->pid;
				}
				$rs->close();
			}
		}
	}

	public function getProjectList(){
		$returnArr = Array();
		$sql = "SELECT p.pid, p.projname, p.managers, p.fulldescription ".
			"FROM fmprojects p WHERE p.ispublic = 1 ".
			"ORDER BY p.SortSequence, p.projname";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$projId = $row->pid;
			$returnArr[$projId]["projname"] = $this->cleanOutStr($row->projname);
			$returnArr[$projId]["managers"] = $this->cleanOutStr($row->managers);
			$returnArr[$projId]["descr"] = $this->cleanOutStr($row->fulldescription);
		}
		$rs->close();
		return $returnArr;
	}

	public function getProjectData(){
		$returnArr = Array();
		if($this->projId){
			$sql = 'SELECT p.pid, p.projname, p.managers, p.fulldescription, p.notes, '.
				'p.occurrencesearch, p.ispublic, p.sortsequence '.
				'FROM fmprojects p '.
				'WHERE (p.pid = '.$this->projId.') '.
				'ORDER BY p.SortSequence, p.projname';
			//echo $sql;
			$rs = $this->con->query($sql);
			if($row = $rs->fetch_object()){
				$this->projId = $row->pid;
				$returnArr['projname'] = $this->cleanOutStr($row->projname);
				$returnArr['managers'] = $this->cleanOutStr($row->managers);
				$returnArr['fulldescription'] = $this->cleanOutStr($row->fulldescription);
				$returnArr['notes'] = $this->cleanOutStr($row->notes);
				$returnArr['occurrencesearch'] = $row->occurrencesearch;
				$returnArr['ispublic'] = $row->ispublic;
				$returnArr['sortsequence'] = $row->sortsequence;
				if($row->ispublic == 0){
					$this->isPublic = 0;
				}
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function submitProjEdits($projArr){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = "";
		foreach($projArr as $field => $value){
			$v = $this->cleanInStr($value);
			$sql .= ','.$field.' = "'.$v.'"';
		}
		$sql = 'UPDATE fmprojects SET '.substr($sql,1).' WHERE (pid = '.$this->projId.')';
		//echo $sql;
		$conn->query($sql);
		$conn->close();
	}
	
	public function addNewProject($projArr){
		$pid = 0;
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO fmprojects(projname,managers,fulldescription,notes,ispublic,sortsequence) '.
			'VALUES("'.$this->cleanInStr($projArr['projname']).'","'.$this->cleanInStr($projArr['managers']).'","'.
			$this->cleanInStr($projArr['fulldescription']).'","'.
			$this->cleanInStr($projArr['notes']).'",'.$projArr['ispublic'].','.
			($projArr['sortsequence']?$projArr['sortsequence']:'50').')';
		//echo $sql;
		if($conn->query($sql)){
			$pid = $conn->insert_id;
		}
		$conn->close();
		return $pid;
	}
	
	public function getResearchChecklists(){
		global $userRights;
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name, c.latcentroid, c.longcentroid, c.access '.
			'FROM fmchklstprojlink cpl INNER JOIN fmchecklists c ON cpl.clid = c.clid '.
			'WHERE (cpl.pid = '.$this->projId.') AND ((c.access != "private")';
		if(array_key_exists('ClAdmin',$userRights)){
			$sql .= ' OR (c.clid IN ('.implode(',',$userRights['ClAdmin']).'))) ';
		}
		else{
			$sql .= ') ';
		}
		$sql .= "ORDER BY c.SortSequence, c.name";
		//echo $sql;
		$rs = $this->con->query($sql);
		$cnt = 0;
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $this->cleanOutStr($row->name).($row->access == 'private'?' <span title="Viewable only to editors">(private)</span>':'');
			if($cnt < 50 && $row->latcentroid){
				$this->researchCoord[] = $row->latcentroid.','.$row->longcentroid;
			}
			$cnt++;
		}
		$rs->close();
		return $returnArr;
	}
	
	public function getSurveyLists(){
		$returnArr = Array();
		$sql = "SELECT s.surveyid, s.projectname, s.latcentroid, s.longcentroid ".
			"FROM omsurveyprojlink spl INNER JOIN omsurveys s ON spl.surveyid = s.surveyid ".
			"WHERE (spl.pid = ".$this->projId.") ".
			"ORDER BY s.SortSequence, s.projectname";
		$rs = $this->con->query($sql);
		$cnt = 0;
		while($row = $rs->fetch_object()){
			$returnArr[$row->surveyid] = $this->cleanOutStr($row->projectname);
			if($cnt < 50 && $row->latcentroid){
				$this->surveyCoord[] = $row->latcentroid.','.$row->longcentroid;
			}
			$cnt++;
		}
		$rs->close();
		return $returnArr;
	}

	public function getGoogleStaticMap($type){
		$googleUrlLocal = $this->googleUrl;
		//$googleUrlLocal .= "&zoom=6";
		$coordStr = '';
		if($type == 'research'){
			$coordStr = implode('%7C',$this->researchCoord);
		}
		else{
			$coordStr = implode('%7C',$this->surveyCoord);
		}
		if(!$coordStr) return ""; 
		$googleUrlLocal .= "&markers=size:tiny%7C".$coordStr;
		return $googleUrlLocal;
	}

	public function getClAddArr(){
		global $userRights;
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name, c.access '.
			'FROM fmchecklists c LEFT JOIN (SELECT clid FROM fmchklstprojlink WHERE (pid = '.$this->projId.')) pl ON c.clid = pl.clid '.
			'WHERE (pl.clid IS NULL) AND (c.access = "public" ';
		if(array_key_exists('ClAdmin',$userRights)){
			$sql .= ' OR (c.clid IN ('.implode(',',$userRights['ClAdmin']).'))) ';
		}
		else{
			$sql .= ') ';
		}
		$sql .= 'ORDER BY name';

		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $this->cleanOutStr($row->name).($row->access == 'private'?' (private)':'');
		}
		$rs->close();
		return $returnArr;
	}

	public function getClDeleteArr(){
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmchklstprojlink pl ON c.clid = pl.clid '.
			'WHERE (pl.pid = '.$this->projId.') '.
			'ORDER BY name';
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $this->cleanOutStr($row->name);
		}
		$rs->close();
		return $returnArr;
	}

	public function addChecklist($clid){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO fmchklstprojlink(pid,clid) VALUES('.$this->projId.','.$clid.') ';
		if($conn->query($sql)){
			return 'SUCCESS: Checklist has been added to project';
		}
		else{
			return 'FAILED: Unable to add checklist to project';
		}
		if(!($conn === null)) $conn->close();
	}

	public function deleteChecklist($clid){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'DELETE FROM fmchklstprojlink WHERE (pid = '.$this->projId.') AND (clid = '.$clid.')';
		if($conn->query($sql)){
			return 'SUCCESS: Checklist has been deleted from project';
		}
		else{
			return 'FAILED: Unable to checklist from project';
		}
		if(!($conn === null)) $conn->close();
	}

 	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = str_replace(chr(10),' ',$newStr);
		$newStr = str_replace(chr(11),' ',$newStr);
		$newStr = str_replace(chr(13),' ',$newStr);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->con->real_escape_string($newStr);
		return $newStr;
	}
}
?>