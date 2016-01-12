<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/ImageShared.php');
include_once($serverRoot.'/classes/EOLUtilities.php');

class EOLManager {

	private $conn;
	private $imgManager = null;

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
			'WHERE t.rankid IN(220,230,240,260) AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
			'AND t.TID NOT IN (SELECT tid FROM taxalinks WHERE title = "Encyclopedia of Life" AND sourceidentifier IS NOT NULL) ';
			//AND t.tid > (SELECT IFNULL(max(tid),0) AS maxtid FROM taxalinks WHERE owner = "EOL")
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidCnt = $r->tidcnt;
		}
		$rs->close();
		return $tidCnt;
	}
	
	public function mapTaxa($makePrimaryLink,$tidStart,$restart){
		$successCnt = 0;
		set_time_limit(36000);

		if(!is_numeric($tidStart)) $tidStart = 0;
		$startingTid = 0;
		if($restart){
			$sql1 = 'SELECT tid FROM taxalinks '.
				'WHERE owner = "EOL" AND initialtimestamp > "'.date('Y-m-d',time()-(7 * 24 * 60 * 60)).'" '.
				'ORDER BY initialtimestamp DESC LIMIT 1';
			$rs1 = $this->conn->query($sql1);
			if($r1 = $rs1->fetch_object()){
				$startingTid = $r1->tid;
			}
			$rs1->free();
		}
		if($tidStart && $tidStart > $startingTid) $startingTid = $tidStart;
		//Start mapping taxa
		$sql = 'SELECT t.tid, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE t.rankid IN(220,230,240,260) AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
			'AND t.tid NOT IN (SELECT tid FROM taxalinks WHERE title = "Encyclopedia of Life" AND sourceidentifier IS NOT NULL) ';
		if($startingTid) $sql .= 'AND t.tid > '.$startingTid.' ';
		$sql .= 'ORDER BY t.tid';
		//echo $sql;
		$rs = $this->conn->query($sql);
		$recCnt = $rs->num_rows;
		echo '<div style="font-weight:">';
		echo 'Mapping EOL identifiers for '.$recCnt.' taxa ';
		if($startingTid) echo '(starting tid: '.$startingTid.')';
		echo '</div>'."\n";
		echo "<ul>\n";
		while($r = $rs->fetch_object()){
			$tid = $r->tid;
			$sciName = $r->sciname;
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
		$retStatus = false;
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
						$retStatus = true;
					}
					else{
						echo '<li style="margin:10px;color:red;">ERROR: unable to read identifier for '.$sciName.' (tid: '.$tid.")</li>\n";
					}
				}
			}
			else{
				echo '<li style="margin:10px;">No results returned for '.$sciName.' (tid: '.$tid.")</li>\n";
			}
		}
		else{
			echo '<li style="color:red;">ERROR attempting to open url: '.$url.'</li>';
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
			'WHERE t.rankid IN(220,230,240,260) AND l.owner = "EOL" '.
			'AND t.tid NOT IN (SELECT ts1.tidaccepted FROM images ii INNER JOIN taxstatus ts1 ON ii.tid = ts1.tid '.
			'WHERE ts1.taxauthid = 1)';
			//'WHERE ts1.taxauthid = 1 AND (ii.imagetype NOT LIKE "%specimen%" OR ii.imagetype IS NULL))';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$tidCnt = $r->tidcnt;
		}
		$rs->close();
		return $tidCnt;
	}
	
	public function mapImagesForTaxa($tidStart,$restart){
		set_time_limit(36000);
		if(!is_numeric($tidStart)) $tidStart = 0;
		$startingTid = 0;
		if($restart){
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
		if($tidStart && $tidStart > $startingTid) $startingTid = $tidStart;
		
		$successCnt = 0;
		$sql = 'SELECT t.tid, t.sciname, l.sourceidentifier '.
			'FROM taxa t INNER JOIN taxalinks l ON t.tid = l.tid '.
			'WHERE t.rankid IN(220,230,240,260) AND l.owner = "EOL" '.
			'AND t.tid NOT IN (SELECT ts1.tidaccepted FROM images ii INNER JOIN taxstatus ts1 ON ii.tid = ts1.tid '.
			'WHERE ts1.taxauthid = 1) ';
			//'WHERE ts1.taxauthid = 1 AND (ii.imagetype NOT LIKE "%specimen%" OR ii.imagetype IS NULL)) ';
		if($startingTid) $sql .= 'AND t.tid >= '.$startingTid.' '; 
		$sql .= 'ORDER BY t.tid';
		$rs = $this->conn->query($sql);
		$recCnt = $rs->num_rows;
		echo '<div style="font-weight:">Mapping images for '.$recCnt.' taxa</div>'."\n";
		if($startingTid) echo '(starting tid: '.$startingTid.')';
		echo "<ul>\n";
		$this->imgManager = new ImageShared();
		while($r = $rs->fetch_object()){
			$tid = $r->tid;
			echo '<li>Mapping images for '.$this->cleanOutStr($r->sciname).' (tid: <a href="../index.php?taxon='.$tid.'" target="_blank">'.$tid.'</a>; EOL:'.$this->cleanOutStr($r->sourceidentifier).")</li>\n";
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
		$retStatus = false;
		$url = 'http://eol.org/api/pages/1.0/'.$identifier.'.json?images=15&vetted=2&details=1 ';
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
				$imgCnt = 0;
				foreach($dataObjArr as $objArr){
					if(array_key_exists('mimeType',$objArr) && $objArr['mimeType'] == 'image/jpeg'){
						$resourceArr = array(); 
						$imageUrl = '';
						if(array_key_exists('mediaURL',$objArr)){
							$imageUrl = $objArr['mediaURL'];
						}
						elseif(isset($objArr['eolMediaURL'])){
							$imageUrl = $objArr['eolMediaURL'];
						}
						//if(array_key_exists('eolThumbnailURL',$objArr)) $resourceArr['urltn'] = $objArr['eolThumbnailURL'];

						if(array_key_exists('agents',$objArr)){
							$agentArr = array();
							$agentCnt = 0;
							foreach($objArr['agents'] as $agentObj){
								if($agentObj['full_name']){
									if($agentCnt < 2) $agentArr[] = $this->cleanInStr($agentObj['full_name']);
									if($agentObj['role'] == 'photographer'){
										$resourceArr['photographer'] = $this->cleanInStr($agentObj['full_name']);
										unset($agentArr);
										break; 
									}
									$agentCnt++;
								}
							}
							if(isset($agentArr) && $agentArr) $resourceArr['photographer'] = implode('; ',array_unique($agentArr));
						}
						$noteStr = 'Harvest via EOL on '.date('Y-m-d');
						if(array_key_exists('description',$objArr)) $noteStr .= '; '.$this->cleanInStr($objArr['description']);
						$resourceArr['notes'] = $noteStr;
						if(array_key_exists('title',$objArr)) $resourceArr['title'] = $this->cleanInStr($objArr['title']);
						if(array_key_exists('rights',$objArr)) $resourceArr['copyright'] = $this->cleanInStr($objArr['rights']);  
						if(array_key_exists('rightsHolder',$objArr)) $resourceArr['owner'] = $this->cleanInStr($objArr['rightsHolder']);
						if(array_key_exists('license',$objArr)) $resourceArr['rights'] = $this->cleanInStr($objArr['license']);
						if(array_key_exists('source',$objArr)) $resourceArr['source'] = $this->cleanInStr($objArr['source']);
						$locStr = '';
						if(array_key_exists('location',$objArr)) $locStr = $this->cleanInStr($objArr['location']);
						if(array_key_exists('latitude',$objArr) && array_key_exists('longitude',$objArr)){
							$locStr .= ' ('.$this->cleanInStr($objArr['latitude']).', '.$this->cleanInStr($objArr['longitude']).')';
						}
						$resourceArr['locality'] = $locStr;
						//Load image
						if($this->loadImage($tid,$imageUrl,$resourceArr)){
							$imgCnt++;
							$retStatus = true;
						}
						if($imgCnt > 5) break;
					}
				}
				echo '<li style="margin-left:10px;">'.$imgCnt.' images mapped</li>';
			}
			else{
				echo '<li>Scientific name not registered with EOL</li>'."\n";
			}
		}
		else{
			echo '<li style="color:red;">ERROR attempting to open url: '.$url.'</li>';
		}
		ob_flush();
		flush();
		sleep(1);
		return $retStatus;
	}

	private function loadImage($tid,$imageUrl,$resourceArr){
		$status = false;
		if($tid && $imageUrl && $resourceArr){
			if(!in_array('MBG',$resourceArr) && !stripos($resourceArr['source'],'tropicos')){
				//Create image derivatives
				$this->imgManager->setTargetPath('eol');
				if($this->imgManager->parseUrl($imageUrl)){
					$webFullUrl = ''; $imgTnUrl = ''; $lgFullUrl = '';
					//Start with building thumbnail
					if($this->imgManager->createNewImage('_tn',$this->imgManager->getTnPixWidth())){
						$imgTnUrl = $this->imgManager->getUrlBase().$this->imgManager->getImgName().'_tn.jpg';
						//Build web image 
						//If web image is too large, transfer to large image and create new web image
						$fileSize = $this->imgManager->getSourceFileSize();
						list($sourceWidth, $sourceHeight) = getimagesize($this->imgManager->getSourcePath());
						if($fileSize > $this->imgManager->getWebFileSizeLimit() || $sourceWidth > ($this->imgManager->getWebPixWidth()*1.2)){
							$lgFullUrl = $imageUrl;
							//Create web image
							if($this->imgManager->createNewImage('_web',$this->imgManager->getWebPixWidth())){
								$webFullUrl = $this->imgManager->getUrlBase().$this->imgManager->getImgName().'_web.jpg';
							}
						}
						else{
							//Use image source image as the web image and leave original image null
							$webFullUrl = $imageUrl;
						}
					}
					else{
						echo '<li style="color:red;">ERROR: unable to create thumbnail image</li>';
					}
	
					//Load image
					if($webFullUrl || $lgFullUrl){
						if(!$webFullUrl) $webFullUrl = 'empty';
						$sql = 'INSERT INTO images(tid,url,thumbnailurl,originalurl,photographer,caption,owner,sourceurl,copyright,rights,locality,notes,imagetype,sortsequence) '.
						'VALUES('.$tid.',"'.$webFullUrl.'",'.
						($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.
						($lgFullUrl?'"'.$lgFullUrl.'"':'NULL').','.
						(isset($resourceArr['photographer'])?'"'.$resourceArr['photographer'].'"':'NULL').','.
						(isset($resourceArr['title'])?'"'.$resourceArr['title'].'"':'NULL').','.
						(isset($resourceArr['owner'])?'"'.$resourceArr['owner'].'"':'NULL').','.
						(isset($resourceArr['source'])?'"'.$resourceArr['source'].'"':'NULL').','.
						(isset($resourceArr['copyright'])?'"'.$resourceArr['copyright'].'"':'NULL').','.
						(isset($resourceArr['rights'])?'"'.$resourceArr['rights'].'"':'NULL').','.
						(isset($resourceArr['locality'])?'"'.$resourceArr['locality'].'"':'NULL').','.
						(isset($resourceArr['notes'])?'"'.$resourceArr['notes'].'"':'NULL').
						',"field image",40)';
						if($this->conn->query($sql)){
							echo '<li style="margin-left:10px;">Image mapped successfully</li>'."\n";
							ob_flush();
							flush();
							$status = true;
						}
						else{
							echo '<li style="color:red;">ERROR: unable to map image: '.$this->conn->error."</li>\n";
						}
					}
				}
				$this->imgManager->reset();
			}
		}
		return $status;
	}
	
	private function encodeString($inStr){
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
/*
{"identifier":8757321,"scientificName":"Ruellia humboldtiana","richness_score":13.8777,"synonyms":[],"vernacularNames":[],"references":[],
"taxonConcepts":[
	{"identifier":57260591,"scientificName":"Ruellia humboldtiana","nameAccordingTo":"NCBI Taxonomy","canonicalForm":"Ruellia humboldtiana","sourceIdentfier":"440967","taxonRank":"Species"},
	{"identifier":50904234,"scientificName":"Ruellia humboldtiana","nameAccordingTo":"NCBI Taxonomy","canonicalForm":"Ruellia humboldtiana","sourceIdentfier":"440967","taxonRank":"Species"},
	{"identifier":43634016,"scientificName":"Ruellia thyrsacanthoides Lindau","nameAccordingTo":"GBIF Nub Taxonomy","canonicalForm":"Ruellia thyrsacanthoides","sourceIdentfier":"5576320","taxonRank":"Species"},
	{"identifier":43633623,"scientificName":"Ruellia humboldtiana Lindau","nameAccordingTo":"GBIF Nub Taxonomy","canonicalForm":"Ruellia humboldtiana","sourceIdentfier":"5574379","taxonRank":"Species"}
],
"dataObjects":[
	{"identifier":"ffa6fdde4fa94cb8d61c188e68a02b10","dataObjectVersionID":28418333,"dataType":"http://purl.org/dc/dcmitype/StillImage","dataSubtype":"","vettedStatus":"Trusted","dataRating":2.5,"mimeType":"image/jpeg","title":"Ruellia_humboldtiana.jpg","license":"http://creativecommons.org/licenses/by-nc/3.0/",
	"source":"http://neotropical-pollination.myspecies.info/file/306",
	"mediaURL":"http://neotropical-pollination.myspecies.info/sites/neotropical-pollination.myspecies.info/files/Ruellia_humboldtiana.jpg",
	"eolMediaURL":"http://media.eol.org/content/2014/03/07/17/97311_orig.jpg","eolThumbnailURL":"http://media.eol.org/content/2014/03/07/17/97311_98_68.jpg",
	"agents":[{"full_name":"Nathan Muchhala","homepage":"","role":null},{"full_name":"Neotropical Pollination","homepage":"","role":"provider"}],"references":[]},
	
	{"identifier":"d76ef0ab43c0a9eaf2f5a4926e5c30a7","dataObjectVersionID":28418374,"dataType":"http://purl.org/dc/dcmitype/StillImage","dataSubtype":"","vettedStatus":"Trusted","dataRating":2.5,"mimeType":"image/jpeg","title":"Ruellia_humboldtiana_extrafloralnectaries.jpg","license":"http://creativecommons.org/licenses/by-nc/3.0/",
	"source":"http://neotropical-pollination.myspecies.info/file/307",
	"mediaURL":"http://neotropical-pollination.myspecies.info/sites/neotropical-pollination.myspecies.info/files/Ruellia_humboldtiana_extrafloralnectaries.jpg",
	"eolMediaURL":"http://media.eol.org/content/2014/03/07/17/30534_orig.jpg","eolThumbnailURL":"http://media.eol.org/content/2014/03/07/17/30534_98_68.jpg",
	"agents":[{"full_name":"Nathan Muchhala","homepage":"","role":null},{"full_name":"Neotropical Pollination","homepage":"","role":"provider"}],"references":[]},

	{"identifier":"be3616ee97538284cd2da8630d12dd9a","dataObjectVersionID":28418395,"dataType":"http://purl.org/dc/dcmitype/StillImage","dataSubtype":"","vettedStatus":"Trusted","dataRating":2.5,"mimeType":"image/jpeg","title":"Ruellia_humboldtiana_fullofnectar.jpg","license":"http://creativecommons.org/licenses/by-nc/3.0/",
	"source":"http://neotropical-pollination.myspecies.info/file/308",
	"mediaURL":"http://neotropical-pollination.myspecies.info/sites/neotropical-pollination.myspecies.info/files/Ruellia_humboldtiana_fullofnectar.jpg",
	"eolMediaURL":"http://media.eol.org/content/2014/03/07/17/81882_orig.jpg","eolThumbnailURL":"http://media.eol.org/content/2014/03/07/17/81882_98_68.jpg",
	"agents":[{"full_name":"Nathan Muchhala","homepage":"","role":null},{"full_name":"Neotropical Pollination","homepage":"","role":"provider"}],"references":[]}
]}
*/
?>