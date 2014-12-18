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
		$rs->free();
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
		$rs->free();
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
		$rs->free();
		return $retArr;
	}
	
	public function setOOTD($oodID,$clid){
		global $serverRoot;
		$currentDate = date("Y-m-d");
		$replace = 0;
		if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_info.json')){
			$oldArr = json_decode(file_get_contents($serverRoot.'/temp/ootd/'.$oodID.'_info.json'), true);
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
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_previous.json')){
				$previous = json_decode(file_get_contents($serverRoot.'/temp/ootd/'.$oodID.'_previous.json'), true);
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_previous.json');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_info.json')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_info.json');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_1.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_1.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_2.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_2.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_3.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_3.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_4.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_4.jpg');
			}
			if(file_exists($serverRoot.'/temp/ootd/'.$oodID.'_organism300_5.jpg')){
				unlink($serverRoot.'/temp/ootd/'.$oodID.'_organism300_5.jpg');
			}
			
			//Create new files
			$ootdInfo = array();
			$ootdInfo['lastDate'] = $currentDate;
			
			$tidArr = Array();
			$sql = 'SELECT l.TID, COUNT(i.imgid) AS cnt '. 
				'FROM fmchklsttaxalink l INNER JOIN images AS i ON l.TID = i.tid '.
				'WHERE i.tid IS NOT NULL AND ISNULL(i.occid) AND l.CLID IN('.$clid.') '. 
				'GROUP BY l.TID';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(($row->cnt > 4) && (!in_array($row->TID, $previous))){
					$tidArr[] = $row->TID;
				}
			}
			$rs->free();
			$k = array_rand($tidArr);
			$randTaxa = $tidArr[$k];
			$previous[] = $randTaxa;
			//echo $randTaxa.' ';
			//echo json_encode($previous);
			
			$ootdInfo['clid'] = $clid;
			
			$sql2 = 'SELECT t.TID, t.SciName, t.UnitName1, s.family '.
				'FROM taxa AS t INNER JOIN taxstatus AS s ON t.TID = s.tid '.
				'WHERE s.taxauthid = 1 AND t.TID = '.$randTaxa.' ';
			//echo '<div>'.$sql2.'</div>';
			$rs = $this->conn->query($sql2);
			while($row = $rs->fetch_object()){
				$ootdInfo['tid'] = $row->TID;
				$ootdInfo['sciname'] = $row->SciName;
				$ootdInfo['genus'] = $row->UnitName1;
				$ootdInfo['family'] = $row->family;
			}
			$rs->free();
			
			$files = Array();
			$sql3 = 'SELECT i.url '.
				'FROM images AS i '.
				'WHERE ISNULL(i.occid) AND i.tid = '.$randTaxa.' '.
				'ORDER BY i.sortsequence ';
			//echo '<div>'.$sql.'</div>';
			$cnt = 1;
			$repcnt = 1;
			$rs = $this->conn->query($sql3);
			while(($row = $rs->fetch_object()) && ($cnt < 6)){
				$file = '';
				if (substr($row->url, 0, 1) == '/'){
					//If imageDomain variable is set within symbini file, image  
					if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
						$file = $GLOBALS['imageDomain'].$row->url;
					}
					else{
						//Use local domain 
						$domain = "http://";
						if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $domain = "https://";
						$domain .= $_SERVER["SERVER_NAME"];
						if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $domain .= ':'.$_SERVER["SERVER_PORT"];
						$file = $domain.$row->url;
					}
				}
				else{
					$file = $row->url;
				}
				$newfile = $serverRoot.'/temp/ootd/'.$oodID.'_organism300_'.$cnt.'.jpg';
				$newfilepath = '../../temp/ootd/'.$oodID.'_organism300_'.$cnt.'.jpg';
				if(fopen($file, "r")){
					copy($file, $newfile);
					$files[] = $newfilepath;
					$cnt++;
				}
			}
			$rs->free();
			$ootdInfo['images'] = $files;
			
			if(array_diff($tidArr,$previous)){
				$fp = fopen($serverRoot.'/temp/ootd/'.$oodID.'_previous.json', 'w');
				fwrite($fp, json_encode($previous));
				fclose($fp);
			}
			$fp = fopen($serverRoot.'/temp/ootd/'.$oodID.'_info.json', 'w');
			fwrite($fp, json_encode($ootdInfo));
			fclose($fp);
		}
		
		
		
		$infoArr = json_decode(file_get_contents($serverRoot.'/temp/ootd/'.$oodID.'_info.json'), true);
		//echo json_encode($infoArr);
		return $infoArr;
	}
}
?>