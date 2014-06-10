<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class GamesManager {

	private $conn;
	private $clid;
	private $dynClid;
	private $clName;

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function setChecklist($clValue){
		if(!$clValue) return;
		$sql = "SELECT c.clid, c.name ".
			"FROM fmchecklists c ";
		if(is_numeric($clValue)){
			$sql .= 'WHERE (clid = '.$clValue.')';
		}
		else{
			$sql .= 'WHERE (clname = "'.$clValue.'")';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->clName = $row->name;
			$this->clid = $row->clid;
		}
		$rs->close();
	}
	
	public function setDynChecklist($dynClid){
		if(!$dynClid) return;
		$sql = 'SELECT c.dynclid, c.name '.
			'FROM fmdynamicchecklists c '.
			'WHERE (c.dynclid = '.$dynClid.')';
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->clName = $row->name;
			$this->dynClid = $row->dynclid;
		}
		$rs->close();
	}
	
	public function getClid(){
		return $this->clid;
	}
	
	public function getDynClid(){
		return $this->dynClid;
	}
	
	public function getClName(){
		return $this->clName;
	}
	
	public function getChecklistArr($projId = 0){
		$retArr = Array();
		$sql = 'SELECT DISTINCT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmchklstprojlink plink ON c.clid = plink.clid ';
		if($projId){
			$sql .= 'WHERE c.type = "static" AND (plink.pid = '.$projId.') ';
		}
		else{
			$sql .= 'INNER JOIN fmprojects p ON plink.pid = p.pid WHERE c.type = "static" AND p.ispublic = 1 ';
		}
		$sql .= 'ORDER BY c.name';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid] = $row->name;
		}
		$rs->close();
		return $retArr;
	}
	
	public function setOOTD($clid){
		$currentDate = date("Y-m-d");
		$replace = 0;
		if(file_exists('../../temp/ootd/info.json')){
			$oldArr = json_decode(file_get_contents('../../temp/ootd/info.json'), true);
			$lastDate = $oldArr['lastDate'];
			$lastCLID = $oldArr['clid'];
			if(($currentDate > $lastDate) || ($clid != $lastCLID)){
				$replace = 1;
			}
		}
		else{
			$replace = 1;
		}
		
		if($replace == 1){
			//Delete old files
			$previous = Array();
			if(file_exists('../../temp/ootd/previous.json')){
				$previous = json_decode(file_get_contents('../../temp/ootd/previous.json'), true);
				unlink('../../temp/ootd/previous.json');
			}
			if(file_exists('../../temp/ootd/info.json')){
				unlink('../../temp/ootd/info.json');
			}
			if(file_exists('../../temp/ootd/plant300_1.jpg')){
				unlink('../../temp/ootd/plant300_1.jpg');
			}
			if(file_exists('../../temp/ootd/plant300_2.jpg')){
				unlink('../../temp/ootd/plant300_2.jpg');
			}
			if(file_exists('../../temp/ootd/plant300_3.jpg')){
				unlink('../../temp/ootd/plant300_3.jpg');
			}
			if(file_exists('../../temp/ootd/plant300_4.jpg')){
				unlink('../../temp/ootd/plant300_4.jpg');
			}
			if(file_exists('../../temp/ootd/plant300_5.jpg')){
				unlink('../../temp/ootd/plant300_5.jpg');
			}
			
			//Create new files
			$ootdInfo = array();
			$ootdInfo['lastDate'] = $currentDate;
			
			$tidArr = Array();
			$sql = 'SELECT l.TID, COUNT(l.TID) AS cnt '.
				'FROM (fmchecklists AS f LEFT JOIN fmchklsttaxalink AS l ON f.CLID = l.CLID) '.
				'LEFT JOIN images AS i ON l.TID = i.tid '.
				'WHERE i.imagetype = "field image" AND f.CLID = '.$clid.' '.
				'GROUP BY l.TID ';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(($row->cnt > 4) && (!in_array($row->TID, $previous))){
					$tidArr[] = $row->TID;
				}
			}
			$rs->close();
			$k = array_rand($tidArr);
			$randTaxa = $tidArr[$k];
			$previous[] = $randTaxa;
			//echo $randTaxa.' ';
			//echo json_encode($previous);
			
			$ootdInfo['clid'] = $clid;
			
			$sql2 = 'SELECT t.TID, t.SciName, t.UnitName1, s.family '.
				'FROM taxa AS t LEFT JOIN taxstatus AS s ON t.TID = s.tid '.
				'WHERE s.taxauthid = 1 AND t.TID = '.$randTaxa.' ';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql2);
			while($row = $rs->fetch_object()){
				$ootdInfo['tid'] = $row->TID;
				$ootdInfo['sciname'] = $row->SciName;
				$ootdInfo['genus'] = $row->UnitName1;
				$ootdInfo['family'] = $row->family;
			}
			$rs->close();
			
			$files = Array();
			$sql3 = 'SELECT i.url '.
				'FROM images AS i '.
				'WHERE i.tid = '.$randTaxa.' '.
				'ORDER BY i.sortsequence ';
			//echo '<div>'.$sql.'</div>';
			$cnt = 1;
			$repcnt = 1;
			$rs = $this->conn->query($sql3);
			while(($row = $rs->fetch_object()) && ($cnt < 6)){
				if (substr($row->url, 0, 4) === 'http'){
					$file = $row->url;
				}
				else{
					$file = 'http://swbiodiversity.org'.$row->url;
				}
				$newfile = '../../temp/ootd/plant300_'.$cnt.'.jpg';
				if(fopen($file, "r")){
					copy($file, $newfile);
					$files[] = $newfile;
					$cnt++;
				}
			}
			$rs->close();
			$ootdInfo['images'] = $files;
			
			if(array_diff($tidArr,$previous)){
				$fp = fopen('../../temp/ootd/previous.json', 'w');
				fwrite($fp, json_encode($previous));
				fclose($fp);
			}
			$fp = fopen('../../temp/ootd/info.json', 'w');
			fwrite($fp, json_encode($ootdInfo));
			fclose($fp);
		}
		
		
		
		$infoArr = json_decode(file_get_contents('../../temp/ootd/info.json'), true);
		//echo json_encode($infoArr);
		return $infoArr;
	}
	
	public function getAnswer(){
		$answer = json_decode(file_get_contents('../../temp/ootd/info.json'), true);
		return $answer;
	}
}
?>