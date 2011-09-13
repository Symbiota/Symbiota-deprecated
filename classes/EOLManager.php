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
		$retStatus = 0;
		$url = 'http://eol.org/api/search/1.0/'.urlencode($sciName).'.json';
		if($fh = fopen($url, 'r')){
			echo '<li>Reading identifier for '.$sciName.' ('.$tid.")</li>\n";
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
						echo '<li style="color:red;">ERROR: unable to read identifier for '.$sciName.' ('.$tid.")</li>\n";
					}
				}
			}
			else{
				echo '<li>No results returned for '.$sciName.' ('.$tid.")</li>\n";
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