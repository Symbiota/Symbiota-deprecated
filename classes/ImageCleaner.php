<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once('ImageShared.php');

class ImageCleaner{
	
	private $conn;
	private $verbose = 1;
	private $errorStr;

	function __construct() {
		set_time_limit(2000);
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		if($this->conn) $this->conn->close();
	}

	public function getReportArr($collid = 0){
		$retArr = array();
		$sql = 'SELECT c.collid, CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, c.collectionname, count(i.imgid) AS cnt '. 
			'FROM images i LEFT JOIN omoccurrences o ON i.occid = o.occid '.
			'LEFT JOIN omcollections c ON o.collid = c.collid '.
			'WHERE ((i.thumbnailurl IS NULL) OR (i.thumbnailurl = "") OR (i.thumbnailurl = "bad url") OR (i.url = "empty")) ';
		if($collid) $sql .= 'AND (c.collid = '.$collid.') ';
		$sql .= 'GROUP BY c.collid ORDER BY c.collectionname';

		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$id = $r->collid;
			$name = $r->collectionname.' ('.$r->collcode.')';
			if(!$id){
				$id = 0;
				$name = 'Field images (not linked to specimens)';
			}
			$retArr[$id]['name'] = $name;
			$retArr[$id]['cnt'] = $r->cnt;
		}
		$rs->free();
		if(array_key_exists(0, $retArr)){
			$tempArr = $retArr[0];
			unset($retArr[0]);
			$retArr[0] = $tempArr;
		}
		return $retArr;
	}

	public function buildThumbnailImages($collid = 0){
		//Process images linked to collections
		$sql = 'SELECT DISTINCT c.collid, CONCAT_WS("_",c.institutioncode, c.collectioncode) AS code, c.collectionname '.
			'FROM omcollections c ';
		if($collid){
			$sql .= 'WHERE c.collid = '.$collid;
		}
		else{
			$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
			'INNER JOIN images i ON o.occid = i.occid '.
			'WHERE (i.thumbnailurl IS NULL) OR (i.thumbnailurl = "") OR (i.thumbnailurl = "bad url") OR (i.thumbnailurl LIKE "processing%") OR (i.url = "empty") OR (i.url LIKE "processing%")';
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($this->verbose){
				echo '<ul><li>Processing Collection: '.$r->collectionname.'</li></ul>';
				ob_flush();
				flush();
			}
			$this->buildImages($r->code.'/',$r->collid);
		}
		$rs->free();
		
		if(!$collid){
			//Check for images that are NOT associated with a collection
			if($this->verbose){
				echo '<ul><li>Processing field images (not linked to specimens)</li></ul>';
				ob_flush();
				flush();
			}
			$this->buildImages('misc/'.date('Ym').'/');
		}
	}
	
	private function buildImages($targetPath, $collid = 0){
		ini_set('memory_limit','512M');
		$imgManager = new ImageShared();

		$sql = '';
		if($collid){
			$sql = 'SELECT i.imgid, i.url, i.originalurl, i.thumbnailurl, i.format, o.catalognumber '.
				'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'WHERE (o.collid = '.$collid.') ';
		}
		else{
			$sql = 'SELECT i.imgid, i.url, i.originalurl, i.thumbnailurl, i.format '.
				'FROM images i '.
				'WHERE (i.occid IS NULL) ';
		}
		$sql .= 'AND ((i.thumbnailurl IS NULL) OR (i.thumbnailurl = "") OR (i.thumbnailurl = "bad url") OR (i.thumbnailurl LIKE "processing%") OR (i.url LIKE "processing%") OR (i.url = "empty")) '.
			'ORDER BY RAND()';
		//echo $sql; exit;
		$result = $this->conn->query($sql);
		if($this->verbose) echo '<ol style="margin-left:15px;">';
		while($row = $result->fetch_object()){
			$status = true;
			$webIsEmpty = false;
			$imgId = $row->imgid;
			if($this->verbose){
				echo '<li>Building thumbnail: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">'.$imgId.'</a>...</li> ';
				ob_flush();
				flush();
			}
			$this->conn->autocommit(false);
			//Tag for updating; needed to ensure two parallel processes are not processing the same image
			$testSql = 'SELECT thumbnailurl, url FROM images WHERE (imgid = '.$imgId.') FOR UPDATE ';
			$textRS = $this->conn->query($testSql);
			if($testR = $textRS->fetch_object()){
				if(!$testR->thumbnailurl || (substr($testR->thumbnailurl,0,10) == 'processing' && $testR->thumbnailurl != 'processing '.date('Y-m-d'))){
					$tagSql = 'UPDATE images SET thumbnailurl = "processing '.date('Y-m-d').'" '.
						'WHERE (imgid = '.$imgId.')';
					$this->conn->query($tagSql);
				}
				elseif($testR->url == 'empty' || (substr($testR->url,0,10) == 'processing' && $testR->url != 'processing '.date('Y-m-d'))){
					$tagSql = 'UPDATE images SET url = "processing '.date('Y-m-d').'" '.
						'WHERE (imgid = '.$imgId.')';
					$this->conn->query($tagSql);
				}
				else{
					//Records already processed by a parallel running process, thus go to next record
					if($this->verbose) echo '<div style="margin-left:30px">Already being handled by a parallel running processs</div>';
					$textRS->free();
					$this->conn->commit();
					$this->conn->autocommit(true);
					continue;
				}
			}
			$textRS->free();
			$this->conn->commit();
			$this->conn->autocommit(true);

			//Build target path
			$finalPath = $targetPath;
			if($collid){
				$catNum = $row->catalognumber;
				if($catNum){
					$catNum = str_replace(array('/','\\',' '), '', $catNum);
					if(preg_match('/^(\D{0,8}\d{4,})/', $catNum, $m)){
						$catPath = substr($m[1], 0, -3);
						if(is_numeric($catPath) && strlen($catPath)<5) $catPath = str_pad($catPath, 5, "0", STR_PAD_LEFT);
						$finalPath .= $catPath.'/';
					}
					else{
						$finalPath .= '00000/';
					}
				}
				else{
					$finalPath .= date('Ym').'/';
				}
			}
			$imgManager->setTargetPath($finalPath);
			
			$imgUrl = trim($row->url);
			if((!$imgUrl || $imgUrl == 'empty') && $row->originalurl){
				$imgUrl = trim($row->originalurl);
				$webIsEmpty = true;
			}
			if($imgManager->parseUrl($imgUrl)){
				//Create thumbnail
				$imgTnUrl = '';
				if(!$row->thumbnailurl || substr($testR->thumbnailurl,0,10) == 'processing'){
					if($imgManager->createNewImage('_tn',$imgManager->getTnPixWidth(),70)){
						$imgTnUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_tn.jpg';
					}
					else{
						$this->errorStr = 'ERROR building thumbnail: '.$imgManager->getErrStr();
						$errSql = 'UPDATE images SET thumbnailurl = "bad url" WHERE thumbnailurl IS NULL AND imgid = '.$imgId;
						$this->conn->query($errSql);
						$status = false;
					}
				}
				else{
					$imgTnUrl = $row->thumbnailurl;
				}
				
				if($status && $imgTnUrl && $imgManager->uriExists($imgTnUrl)){
					$webFullUrl = '';
					$lgFullUrl = '';
					//If web image is too large, transfer to large image and create new web image
					list($sourceWidth, $sourceHeight) = getimagesize($imgManager->getSourcePath());
					if(!$webIsEmpty && !$row->originalurl){
						$fileSize = $imgManager->getSourceFileSize();
						if($fileSize > $imgManager->getWebFileSizeLimit() || $sourceWidth > ($imgManager->getWebPixWidth()*1.2)){
							$lgFullUrl = $imgManager->getSourcePath();
							$webIsEmpty = true;
						}
					}
					if($webIsEmpty){
						if($sourceWidth && $sourceWidth < $imgManager->getWebPixWidth()){
							if(copy($imgManager->getSourcePath(),$imgManager->getTargetPath().$imgManager->getImgName().'_web'.$imgManager->getImgExt())){
								$webFullUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_web'.$imgManager->getImgExt();
							}
						}
						if(!$webFullUrl){
							if($imgManager->createNewImage('_web',$imgManager->getWebPixWidth())){
								$webFullUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_web.jpg';
							}
						}
					}
	
					$sql = 'UPDATE images ti SET ti.thumbnailurl = "'.$imgTnUrl.'" ';
					if($webFullUrl){
						$sql .= ',url = "'.$webFullUrl.'" ';
					}
					if($lgFullUrl){
						$sql .= ',originalurl = "'.$lgFullUrl.'" ';
					}
					if(!$row->format && $imgManager->getFormat()){
						$sql .= ',format = "'.$imgManager->getFormat().'" ';
					}
					$sql .= "WHERE ti.imgid = ".$imgId;
					//echo $sql; 
					if(!$this->conn->query($sql)){
						$this->errorStr = 'ERROR: thumbnail created but failed to update database: '.$this->conn->error;
						if($this->verbose) echo '<div style="margin-left:30px">'.$this->errorStr.'</div>';
						$status = false;
					}
				}
				$imgManager->reset();
			}
			else{
				$this->errorStr = 'ERROR: unable to parse source image ('.$imgUrl.')';
				if($this->verbose) echo '<div style="margin-left:30px">'.$this->errorStr.'</div>';
				$status = false;
			}
			if($this->verbose && !$status){
				echo $this->errorStr.'</li>';
			}
			ob_flush();
			flush();
		}
		$result->free();
		if($this->verbose) echo '</ol>';
	}

	//URL testing 
	public function testByCollid($collid){
		$sql = 'SELECT i.imgid, i.url, i.thumbnailurl, i.originalurl '.
				'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'WHERE o.collid IN('.$collid.')';
		return $this->testUrls($sql);
	}
	
	public function testByImgid($imgidStr){
	
	
	}
	
	private function testUrls($sql){
		$status = true;
		$badUrlArr = array();
		if(!$sql){
			$this->errorStr = 'SQL string is NULL';
			return false;
		}
		$imgManager = new ImageShared();
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_object()){
				if(!$imgManager->uriExists($r->url)) $badUrlArr[$r->imgid]['url'] = $r->url;
				if(!$imgManager->uriExists($r->thumbnailurl)) $badUrlArr[$r->imgid]['tn'] = $r->thumbnailurl;
				if(!$imgManager->uriExists($r->originalurl)) $badUrlArr[$r->imgid]['lg'] = $r->originalurl;
			}
			$rs->free();
		}
		else{
			$this->errorStr = 'Issue with connection or SQL: '.$sql;
			return false;
		}
		//Output results (needs to be extended)
		foreach($badUrlArr as $imgid => $badUrls){
			echo $imgid.', ';
			echo (isset($badUrls['url'])?$badUrls['url']:'').',';
			echo (isset($badUrls['tn'])?$badUrls['tn']:'').',';
			echo (isset($badUrls['lg'])?$badUrls['lg']:'').',';
			echo '<br/>';
		}
		return $status;
	}

	//Setters and getters
	public function setVerbose($verb){
		$this->verbose = $verb;
	}
	
	public function getErrorStr(){
		return $this->errorStr;
	}
}
?>