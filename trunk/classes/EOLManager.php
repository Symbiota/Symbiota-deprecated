<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class EOLManager {

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function getEmptyIdentifierCount(){
		$tidCnt = 0;
		$sql = 'SELECT COUNT(t.tid) as tidcnt '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'LEFT JOIN (SELECT tid FROM taxalinks WHERE title = "Encyclopedia of Life" AND sourceidentifier IS NOT NULL) tl ON t.tid = tl.tid '.
			'WHERE t.rankid >= 220 AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted AND tl.TID IS NULL ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidCnt = $r->tidcnt;
		}
		$rs->close();
		return $tidCnt;
	}
	
	public function mapTaxa($makePrimaryLink = 1){
		$successCnt = 0;
		set_time_limit(6000);
		$sql = 'SELECT t.tid, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'LEFT JOIN (SELECT tid FROM taxalinks WHERE title = "Encyclopedia of Life" AND sourceidentifier IS NOT NULL) tl ON t.tid = tl.tid '.
			'WHERE t.rankid >= 220 AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted AND tl.TID IS NULL ';
		$rs = $this->conn->query($sql);
		$recCnt = $rs->num_rows;
		echo '<div style="font-weight:">Mapping EOL identifiers for '.$recCnt.' taxa</div>'."\n";
		echo "<ul>\n";
		while($r = $rs->fetch_object()){
			$tid = $r->tid;
			$sciName = $r->sciname;
			if($this->queryEolIdentifier($tid, $sciName, $makePrimaryLink)){
				$successCnt++;
			}
		}
		echo "<li>EOL mapping successfully completed for $successCnt taxa</li>\n";
		echo "</ul>\n";
		$rs->close();
	}
	
	private function queryEolIdentifier($tid, $sciName, $makePrimaryLink){
		global $eolKey;
		$retStatus = 0;
		$url = 'http://eol.org/api/search/1.0/'.urlencode($sciName).'.json';
		if(isset($eolKey) && $eolKey) $url .= '?key='.$eolKey;
		if($fh = fopen($url, 'r')){
			echo '<li>Reading identifier for '.$sciName.' (tid: '.$tid.")</li>\n";
			$content = '';
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$retArr = json_decode($content, true);
			if(is_array($retArr) && $retArr['totalResults'] > 0){
				$identifier = $retArr['results'][0]['id'];
				$link = $retArr['results'][0]['link'];
				//Load link
				if($identifier){
					$sql = 'INSERT INTO taxalinks(tid, url, sourceIdentifier, owner, title, sortsequence) '.
						'VALUES('.$tid.',"'.$link.'","'.$identifier.'","EOL","Encyclopedia of Life", '.($makePrimaryLink?1:50).') ';
					if($this->conn->query($sql)){
						echo '<li>Identifier mapped successfully</li>'."\n";
						$retStatus = 1;
					}
					else{
						echo '<li style="color:red;">ERROR: unable to read identifier for '.$sciName.' (tid: '.$tid.")</li>\n";
					}
				}
			}
			else{
				echo '<li>No results returned for '.$sciName.' (tid: '.$tid.")</li>\n";
			}
		}
		ob_flush();
		flush();
		sleep(2);
		return $retStatus;
	}
	
	public function getImageDeficiencyCount(){
		$tidCnt = 0;
		$sql = 'SELECT COUNT(t.tid) AS tidcnt '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'LEFT JOIN (SELECT tid FROM taxalinks WHERE title = "Encyclopedia of Life" AND sourceidentifier IS NOT NULL) tl ON t.tid = tl.tid '.
			'WHERE t.rankid >= 220 AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted AND tl.TID IS NULL ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidCnt = $r->tidcnt;
		}
		$rs->close();
		return $tidCnt;
	}
	
	public function mapImagesForTaxa(){
		$successCnt = 0;
		set_time_limit(6000);
		$sql = 'SELECT t.tid, t.sciname, l.sourceidentifier '.
			'FROM taxa t INNER JOIN taxalinks l ON t.tid = l.tid '.
			'LEFT JOIN (SELECT ts1.tidaccepted FROM images ii INNER JOIN taxstatus ts1 ON ii.tid = ts1.tid '.
			'WHERE ts1.taxauthid = 1 AND ii.imagetype NOT LIKE "%specimen%") i ON t.tid = i.tidaccepted '. 
			'WHERE t.rankid >= 220 AND i.tidaccepted IS NULL AND l.owner = "EOL" LIMIT 5';
		$rs = $this->conn->query($sql);
		$recCnt = $rs->num_rows;
		echo '<div style="font-weight:">Mapping images for '.$recCnt.' taxa</div>'."\n";
		echo "<ul>\n";
		while($r = $rs->fetch_object()){
			$tid = $r->tid;
			echo '<li>Mapping images for '.$r->sciname.' (tid: '.$tid.")</li>\n";
			if($this->mapEolImages($tid, $r->sourceidentifier)){
				$successCnt++;
			}
		}
		echo "<li>EOL mapping successfully completed for $successCnt taxa</li>\n";
		echo "</ul>\n";
		$rs->close();
		
	}

	private function mapEolImages($tid, $identifier){
		global $eolKey;
		$retStatus = 0;
		$url = 'http://eol.org/api/pages/1.0/'.$identifier.'.json?images=5&vetted=2&details=1 ';
		echo $url;
		if(isset($eolKey) && $eolKey) $url .= '&key='.$eolKey;
		if($fh = fopen($url, 'r')){
			$content = '';
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$retArr = json_decode($content, true);
			if(is_array($retArr) && array_key_exists('dataObjects',$retArr)){
				$dataObjArr = $retArr['dataObjects'];
				foreach($dataObjArr as $objArr){
					if(array_key_exists('mimeType',$objArr) && $objArr['mimeType'] == 'image/jpeg'){
						$resourceArr = array(); 
						$locStr = '';
						if(array_key_exists('mediaURL',$objArr)) $resourceArr['url'] = $objArr['mediaURL'];
						if(!array_key_exists('url',$resourceArr)) $resourceArr['url'] = $objArr['eolMediaURL'];
						if(array_key_exists('eolThumbnailURL',$objArr)) $resourceArr['urltn'] = $objArr['eolThumbnailURL'];

						if(array_key_exists('agents',$objArr)){
							foreach($objArr['agents'] as $agentObj){
								if($agentObj['full_name']) $resourceArr['photographer'] = $agentObj['full_name'];
								if($agentObj['role'] == 'photographer') break; 
							}
						}
						if(array_key_exists('description',$objArr)) $resourceArr['notes'] = $objArr['description'];
						if(array_key_exists('rights',$objArr)) $resourceArr['notes'] = $objArr['rights'];
						if(array_key_exists('title',$objArr)) $resourceArr['title'] = $objArr['title'];
						if(array_key_exists('rightsHolder',$objArr)) $resourceArr['owner'] = $objArr['rightsHolder'];
						if(array_key_exists('source',$objArr)) $resourceArr['source'] = $objArr['source'];
						if(array_key_exists('license',$objArr)) $resourceArr['license'] = $objArr['license'];
						if(array_key_exists('location',$objArr)) $locStr = $objArr['location'];
						if(array_key_exists('latitude',$objArr) && array_key_exists('longitude',$objArr)){
							$locStr .= ' ('.$objArr['latitude'].', '.$objArr['longitude'].')';
						}
						if($resourceArr){
							$sql = 'INSERT INTO images(tid,url,thumbnailurl,photographer,caption,owner,sourceurl,copyright,locality,notes,sortsequence) '.
							'VALUES('.$tid.',"'.$resourceArr['url'].'",'.
							(array_key_exists('urltn',$resourceArr)?'"'.$resourceArr['urltn'].'"':'NULL').','.
							(array_key_exists('photographer',$resourceArr)?'"'.$resourceArr['photographer'].'"':'NULL').','.
							(array_key_exists('title',$resourceArr)?'"'.$resourceArr['title'].'"':'NULL').','.
							(array_key_exists('owner',$resourceArr)?'"'.$resourceArr['owner'].'"':'NULL').','.
							(array_key_exists('source',$resourceArr)?'"'.$resourceArr['source'].'"':'NULL').','.
							(array_key_exists('license',$resourceArr)?'"'.$resourceArr['license'].'"':'NULL').','.
							($locStr?'"'.$locStr.'"':'NULL').','.
							(array_key_exists('notes',$resourceArr)?'"'.$resourceArr['notes'].'"':'NULL').
							',40)';
							if($this->conn->query($sql)){
								echo '<li>Image mapped successfully</li>'."\n";
								$retStatus = 1;
							}
							else{
								echo '<li style="color:red;">ERROR: unable to map image: '.$sql.")</li>\n";
							}
						}
					}
				}
			}
			else{
				echo '<li>No results returned</li>'."\n";
			}
		}
		ob_flush();
		flush();
		sleep(2);
		return $retStatus;
	}

	private function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace('"',"'",$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?> 