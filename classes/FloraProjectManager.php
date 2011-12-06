<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class FloraProjectManager {

	private $con;
	private $projId;
	private $googleUrl;
	private $researchCoord = Array();
	private $surveyCoord = Array();

	public function __construct(){
		global $googleMapKey;
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
		$sql = "SELECT p.pid, p.projname, p.managers, p.briefdescription ".
			"FROM fmprojects p WHERE p.ispublic = 1 ".
			"ORDER BY p.SortSequence, p.projname";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$projId = $row->pid;
			$returnArr[$projId]["projname"] = $row->projname;
			$returnArr[$projId]["managers"] = $row->managers;
			$returnArr[$projId]["descr"] = $row->briefdescription;
		}
		$rs->close();
		return $returnArr;
	}

	public function getProjectData(){
		$returnArr = Array();
		if($this->projId){
			$sql = 'SELECT p.pid, p.projname, p.managers, p.briefdescription, p.fulldescription, p.notes, '.
				'p.occurrencesearch, p.ispublic, p.sortsequence '.
				'FROM fmprojects p '.
				'WHERE (p.pid = '.$this->projId.') '.
				'ORDER BY p.SortSequence, p.projname';
			//echo $sql;
			$rs = $this->con->query($sql);
			if($row = $rs->fetch_object()){
				$this->projId = $row->pid;
				$returnArr['projname'] = $row->projname;
				$returnArr['managers'] = $row->managers;
				$returnArr['briefdescription'] = $row->briefdescription;
				$returnArr['fulldescription'] = $row->fulldescription;
				$returnArr['notes'] = $row->notes;
				$returnArr['occurrencesearch'] = $row->occurrencesearch;
				$returnArr['ispublic'] = $row->ispublic;
				$returnArr['sortsequence'] = $row->sortsequence;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function submitProjEdits($projArr){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = "";
		foreach($projArr as $field => $value){
			$v = $this->cleanString($value);
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
		$sql = 'INSERT INTO fmprojects(projname,managers,briefdescription,fulldescription,notes,ispublic,sortsequence) '.
			'VALUES("'.$this->cleanString($projArr['projname']).'","'.$this->cleanString($projArr['managers']).'","'.
			$this->cleanString($projArr['briefdescription']).'","'.$this->cleanString($projArr['fulldescription']).'","'.
			$this->cleanString($projArr['notes']).'",'.$projArr['ispublic'].','.
			($projArr['sortsequence']?$projArr['sortsequence']:'50').')';
		//echo $sql;
		if($conn->query($sql)){
			$pid = $conn->insert_id;
		}
		$conn->close();
		return $pid;
	}
	
	public function getResearchChecklists(){
		$returnArr = Array();
		$sql = "SELECT c.clid, c.name, c.latcentroid, c.longcentroid ".
			"FROM fmchklstprojlink cpl INNER JOIN fmchecklists c ON cpl.clid = c.clid ".
			"WHERE (c.access = 'public') AND (cpl.pid = ".$this->projId.") ".
			"ORDER BY c.SortSequence, c.name";
		$rs = $this->con->query($sql);
		echo "<ul>";
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name;
			if($row->latcentroid){
				$this->researchCoord[] = $row->latcentroid.','.$row->longcentroid;
			}
		}
		echo "</ul>";
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
		echo "<ul>";
		while($row = $rs->fetch_object()){
			$returnArr[$row->surveyid] = $row->projectname;
			if($row->latcentroid){
				$this->surveyCoord[] = $row->latcentroid.','.$row->longcentroid;
			}
		}
		echo "</ul>";
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
		$returnArr = Array();
		$sql = 'SELECT c.clid, c.name '.
			'FROM fmchecklists c LEFT JOIN (SELECT clid FROM fmchklstprojlink WHERE (pid = '.$this->projId.')) pl ON c.clid = pl.clid '.
			'WHERE pl.clid IS NULL AND c.access = "public" '.
			'ORDER BY name';
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name;
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
			$returnArr[$row->clid] = $row->name;
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

	private function cleanString($inStr){
		$retStr = trim($inStr);

		$retStr = str_replace('"',"'",$retStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		return $retStr;
	}
}
?>