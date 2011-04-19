<?php
 include_once($serverRoot.'/config/dbconnection.php');
 
 class ChecklistListingManager {

	private $con;
	private $projectId;

 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function setProj($projValue){
		if(is_numeric($projValue)){
			$this->projectId = $projValue;
		}
		else{
			$sql = "SELECT p.pid FROM fmprojects p WHERE p.projname = '".$this->con->real_escape_string($projValue)."'";
			$result = $this->con->query($sql);
			if($row = $result->fetch_object()){
				$this->projectId = $row->pid;
			}
			$result->close();
		}
	}
	
	public function getProjectId(){
		return $this->projectId;
	}

	public function getResearchChecklists(){
		$returnArr = Array();
		$sql = "SELECT p.pid, p.projname, c.CLID, c.Name ".
			"FROM (fmprojects p INNER JOIN fmchklstprojlink cpl ON p.pid = cpl.pid) ".
			"INNER JOIN fmchecklists c ON cpl.clid = c.CLID ".
			"WHERE (c.access = 'public' AND p.ispublic = 1) ";
		if($this->projectId) $sql .= "AND p.pid = ".$this->con->real_escape_string($this->projectId)." ";
		$sql .= "ORDER BY p.SortSequence, p.projname, c.SortSequence, c.Name";
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->pid."::".$row->projname][$row->CLID] = $row->Name;
		}
		return $returnArr;
	}
	
	public function getSurveyChecklists(){
		$returnArr = Array();
		$sql = "SELECT p.pid, p.projname, s.surveyid, s.projectname ".
			"FROM (fmprojects p INNER JOIN omsurveyprojlink spl ON p.pid = spl.pid) ".
			"INNER JOIN omsurveys s ON spl.surveyid = s.surveyid ".
			"WHERE (p.ispublic = 1 AND s.ispublic = 1) ";
		if($this->projectId) $sql .= "AND p.pid = ".$this->con->real_escape_string($this->projectId)." ";
		$sql .= "ORDER BY p.SortSequence, p.projname, s.SortSequence, s.projectname";
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->pid."::".$row->projname][$row->surveyid] = $row->projectname;
		}
		return $returnArr;
	}
}

 ?>