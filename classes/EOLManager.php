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
			'WHERE t.rankid >= 220 AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
			'AND t.TID NOT IN (SELECT tid FROM taxalinks WHERE title = "Encyclopedia of Life" AND sourceidentifier IS NOT NULL) ';
			//AND t.tid > (SELECT IFNULL(max(tid),0) AS maxtid FROM taxalinks WHERE owner = "EOL")
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
		//Get last tid mapped within the lasat week, this will be used to start maping that may have been stopped early
		$startingTid = 0;
		$sql = 'SELECT tid FROM taxalinks '.
			'WHERE owner = "EOL" AND initialtimestamp > "'.date('Y-m-d',time()-(7 * 24 * 60 * 60)).'" '.
			'ORDER BY initialtimestamp DESC LIMIT 1';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			//$startingTid = $r->tid;
		}
		$rs->free();
		//Start mapping taxa
		$sql = 'SELECT t.tid, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE t.rankid >= 220 AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
			'AND t.tid NOT IN (SELECT tid FROM taxalinks WHERE title = "Encyclopedia of Life" AND sourceidentifier IS NOT NULL) '.
			'AND t.tid > '.$startingTid.' ORDER BY t.tid';
		//echo $sql;
		$rs = $this->conn->query($sql);
		$recCnt = $rs->num_rows;
		echo '<div style="font-weight:">';
		echo 'Mapping EOL identifiers for '.$recCnt.' taxa ';
		if($startingTid) echo '(starting tid: '.$startingTid;
		echo '</div>'."\n";
		echo "<ul>\n";
		while($r = $rs->fetch_object()){
			$tid = $r->tid;
			$sciName = $this->cleanOutStr($r->sciname);
			$sciName = str_replace(array(' subsp. ',' ssp. ',' var. ',' f. '),' ',$sciName);
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
						'VALUES('.$tid.',"'.$link.'","'.$this->cleanInStr($identifier).'","EOL","Encyclopedia of Life", '.($makePrimaryLink?1:50).') ';
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
			'FROM taxa t INNER JOIN taxalinks l ON t.tid = l.tid '.
			'WHERE t.rankid >= 220 AND l.owner = "EOL" '.
			'AND t.tid NOT IN (SELECT ts1.tidaccepted FROM images ii INNER JOIN taxstatus ts1 ON ii.tid = ts1.tid '.
			'WHERE ts1.taxauthid = 1 AND (ii.imagetype NOT LIKE "%specimen%" OR ii.imagetype IS NULL))';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidCnt = $r->tidcnt;
		}
		$rs->close();
		return $tidCnt;
	}
	
	public function mapImagesForTaxa($startIndex = 0){
		if(!is_numeric($startIndex)) return;
		if(!$startIndex){
			//Get tid last image mapped as the start index
			$sql = 'SELECT tid '.
				'FROM images '.
				'WHERE notes LIKE "Harvest via EOL%" AND initialtimestamp > "'.date('Y-m-d',time()-(7 * 24 * 60 * 60)).'" '.
				'ORDER BY initialtimestamp DESC LIMIT 1';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$startingTid = $r->tid;
			}
			$rs->free();
		}
		
		$successCnt = 0;
		set_time_limit(6000);
		$sql = 'SELECT t.tid, t.sciname, l.sourceidentifier '.
			'FROM taxa t INNER JOIN taxalinks l ON t.tid = l.tid '.
			'WHERE t.rankid >= 220 AND l.owner = "EOL" '.
			'AND t.tid NOT IN (SELECT ts1.tidaccepted FROM images ii INNER JOIN taxstatus ts1 ON ii.tid = ts1.tid '.
			'WHERE ts1.taxauthid = 1 AND (ii.imagetype NOT LIKE "%specimen%" OR ii.imagetype IS NULL))';
		if($startIndex) $sql .= 'AND t.tid >= '.$startIndex.' '; 
		$sql .= 'ORDER BY t.tid';
		$rs = $this->conn->query($sql);
		$recCnt = $rs->num_rows;
		echo '<div style="font-weight:">Mapping images for '.$recCnt.' taxa</div>'."\n";
		echo "<ul>\n";
		while($r = $rs->fetch_object()){
			$tid = $r->tid;
			echo '<li>Mapping images for '.$this->cleanOutStr($r->sciname).' (tid: '.$tid.'; EOL:'.$this->cleanOutStr($r->sourceidentifier).")</li>\n";
			if($this->mapEolImages($tid, $this->cleanOutStr($r->sourceidentifier))){
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
		$url = 'http://eol.org/api/pages/1.0/'.$identifier.'.json?images=10&vetted=2&details=1 ';
		//echo $url;
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
				$imageFound = 0;
				foreach($dataObjArr as $objArr){
					if(array_key_exists('mimeType',$objArr) && $objArr['mimeType'] == 'image/jpeg'){
						$imageFound = 1;
						$resourceArr = array(); 
						$locStr = '';
						if(array_key_exists('mediaURL',$objArr)) $resourceArr['url'] = $objArr['mediaURL'];
						if(!array_key_exists('url',$resourceArr)) $resourceArr['url'] = $objArr['eolMediaURL'];
						//if(array_key_exists('eolThumbnailURL',$objArr)) $resourceArr['urltn'] = $objArr['eolThumbnailURL'];

						if(array_key_exists('agents',$objArr)){
							foreach($objArr['agents'] as $agentObj){
								if($agentObj['full_name']) $resourceArr['photographer'] = $this->cleanInStr($agentObj['full_name']);
								if($agentObj['role'] == 'photographer') break; 
							}
						}
						if(array_key_exists('description',$objArr)) $resourceArr['notes'] = $this->cleanInStr($objArr['description']);
						$noteStr = 'Harvest via EOL on '.date('Y-m-d');
						if(array_key_exists('rights',$objArr)) $noteStr .= '; '.$this->cleanInStr($objArr['rights']);  
						$resourceArr['notes'] = $noteStr;
						if(array_key_exists('title',$objArr)) $resourceArr['title'] = $this->cleanInStr($objArr['title']);
						if(array_key_exists('rightsHolder',$objArr)) $resourceArr['owner'] = $this->cleanInStr($objArr['rightsHolder']);
						if(array_key_exists('source',$objArr)) $resourceArr['source'] = $this->cleanInStr($objArr['source']);
						if(array_key_exists('license',$objArr)) $resourceArr['license'] = $this->cleanInStr($objArr['license']);
						if(array_key_exists('location',$objArr)) $locStr = $this->cleanInStr($objArr['location']);
						if(array_key_exists('latitude',$objArr) && array_key_exists('longitude',$objArr)){
							$locStr .= ' ('.$this->cleanInStr($objArr['latitude']).', '.$this->cleanInStr($objArr['longitude']).')';
						}
						$sourceStr = (array_key_exists('source',$resourceArr)?trim($resourceArr['source']):'');
						if($resourceArr && !in_array('MBG',$resourceArr) && !stripos($sourceStr,'tropicos')){
							$sql = 'INSERT INTO images(tid,url,thumbnailurl,photographer,caption,owner,sourceurl,copyright,locality,notes,imagetype,sortsequence) '.
							'VALUES('.$tid.',"'.$resourceArr['url'].'",'.
							(array_key_exists('urltn',$resourceArr)?'"'.$resourceArr['urltn'].'"':'NULL').','.
							(array_key_exists('photographer',$resourceArr)?'"'.$resourceArr['photographer'].'"':'NULL').','.
							(array_key_exists('title',$resourceArr)?'"'.$resourceArr['title'].'"':'NULL').','.
							(array_key_exists('owner',$resourceArr)?'"'.$resourceArr['owner'].'"':'NULL').','.
							($sourceStr?'"'.$sourceStr.'"':'NULL').','.
							(array_key_exists('license',$resourceArr)?'"'.$resourceArr['license'].'"':'NULL').','.
							($locStr?'"'.$locStr.'"':'NULL').','.
							(array_key_exists('notes',$resourceArr)?'"'.$resourceArr['notes'].'"':'NULL').
							',"field image",40)';
							if($this->conn->query($sql)){
								echo '<li>Image mapped successfully</li>'."\n";
								$retStatus = 1;
							}
							else{
								echo '<li style="color:red;">ERROR: unable to map image: '.$sql."</li>\n";
							}
						}
					}
				}
				echo '<li>No images found</li>';
			}
			else{
				echo '<li>Scientific name not registered with EOL</li>'."\n";
			}
		}
		ob_flush();
		flush();
		sleep(2);
		return $retStatus;
	}

	protected function encodeString($inStr){
		global $charset;
 		$retStr = trim($inStr);
 		if($retStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($charset) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
 		}
		return $retStr;
	}
	
	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace(chr(9)," ",$newStr);
		$newStr = str_replace(chr(10)," ",$newStr);
		$newStr = str_replace(chr(13)," ",$newStr);

		$newStr = $this->encodeString($newStr);
		
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?> 