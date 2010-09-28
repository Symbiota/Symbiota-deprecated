<?php
include_once($serverRoot.'/config/dbconnection.php');
 
 class FloraProjectManager {

	private $con;
	private $projId;

 	public function __construct($proj){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
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
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function getProjectId(){
		return $this->projId;
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
		$sql = "SELECT p.pid, p.projname, p.managers, p.briefdescription, p.fulldescription, p.notes, p.sortsequence ".
			"FROM fmprojects p ".
			"WHERE (p.pid = ".$this->projId.") ".
			"ORDER BY p.SortSequence, p.projname";
		//echo $sql;
		$rs = $this->con->query($sql);
		if($row = $rs->fetch_object()){
			$this->projId = $row->pid;
			$returnArr[$this->projId]["projname"] = $row->projname;
			$returnArr[$this->projId]["managers"] = $row->managers;
			$returnArr[$this->projId]["briefdescription"] = $row->briefdescription;
			$returnArr[$this->projId]["fulldescription"] = $row->fulldescription;
			$returnArr[$this->projId]["notes"] = $row->notes;
			$returnArr[$this->projId]["sortsequence"] = $row->sortsequence;
		}
		$rs->close();
		return $returnArr;
	}

	public function submitProjEdits($projArr){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = "";
		foreach($projArr as $field=>$value){
			$sql .= ",$field = \"".$value."\"";
		}
		$sql = "UPDATE fmprojects SET ".substr($sql,1)." WHERE pid = ".$this->projId;
		//echo $sql;
		$conn->query($sql);
		$conn->close();
	}
	
	public function getResearchChecklists(){
		$returnArr = Array();
		$sql = "SELECT c.clid, c.name ".
			"FROM fmchklstprojlink cpl INNER JOIN fmchecklists c ON cpl.clid = c.clid ".
			"WHERE (c.access = 'public') AND (cpl.pid = ".$this->projId.") ".
			"ORDER BY c.SortSequence, c.name";
		$rs = $this->con->query($sql);
		echo "<ul>";
		while($row = $rs->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		echo "</ul>";
		$rs->close();
		return $returnArr;
	}
	
	public function getSurveyLists(){
		$returnArr = Array();
		$sql = "SELECT s.surveyid, s.projectname ".
			"FROM omsurveyprojlink spl INNER JOIN omsurveys s ON spl.surveyid = s.surveyid ".
			"WHERE (spl.pid = ".$this->projId.") ".
			"ORDER BY s.SortSequence, s.projectname";
		$rs = $this->con->query($sql);
		echo "<ul>";
		while($row = $rs->fetch_object()){
			$returnArr[$row->surveyid] = $row->projectname;
		}
		echo "</ul>";
		$rs->close();
		return $returnArr;
	}
 }

 ?>