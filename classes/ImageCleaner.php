<?php
include_once('Manager.php');
include_once('ImageShared.php');

class ImageCleaner extends Manager{

	private $collid;
	private $tidArr = array();
	private $imgRecycleBin;
	private $imgDelRecOverride = false;
	private $imgManager = null;

	function __construct() {
		parent::__construct(null,'write');
		$this->verboseMode = 2;
		set_time_limit(2000);
	}

	function __destruct(){
		parent::__destruct();
	}

	//Thumbnail building tools
	public function getReportArr(){
		$retArr = array();

		$sql = 'SELECT c.collid, CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, c.collectionname, count(DISTINCT i.imgid) AS cnt '.
			'FROM images i LEFT JOIN omoccurrences o ON i.occid = o.occid '.
			'LEFT JOIN omcollections c ON o.collid = c.collid ';
		if($this->tidArr){
			$sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
		}
		$sql .= $this->getSqlWhere().
			'GROUP BY c.collid ORDER BY c.collectionname';
		//echo $sql;
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

	public function buildThumbnailImages(){
		$this->imgManager = new ImageShared();

		//Get image recordset to be processed
		$sql = 'SELECT DISTINCT i.imgid, i.url, i.originalurl, i.thumbnailurl, i.format ';
		if($this->collid){
			$sql .= ', o.catalognumber FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid ';
		}
		else{
			$sql .= 'FROM images i ';
		}
		if($this->tidArr){
			$sql .= 'INNER JOIN taxaenumtree e ON i.tid = e.tid ';
		}
		$sql .= $this->getSqlWhere().'ORDER BY RAND()';
		//echo $sql; exit;
		$result = $this->conn->query($sql);
		$cnt = 1;
		if($this->verboseMode > 1) echo '<ul style="margin-left:15px;">';
		while($row = $result->fetch_object()){
			$status = true;
			$imgId = $row->imgid;
			$this->logOrEcho($cnt.': Building thumbnail: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">'.$imgId.'</a>...');
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
					$this->logOrEcho('Already being handled by a parallel running processs',1);
					$textRS->free();
					$this->conn->commit();
					$this->conn->autocommit(true);
					continue;
				}
			}
			$textRS->free();
			$this->conn->commit();
			$this->conn->autocommit(true);

			$setFormat = ($row->format?false:true);
			$this->buildImageDerivatives($imgId, $row->catalognumber, $row->url, $row->thumbnailurl, $row->originalurl, $setFormat);

			if(!$status) $this->logOrEcho($this->errorMessage,1);
			$cnt++;
		}
		$result->free();
		if($this->verboseMode > 1) echo '</ul>';
	}

	private function getCollectionInfo(){
		$retArr = array();
		$sql = 'SELECT DISTINCT c.collid, CONCAT_WS("_",c.institutioncode, c.collectioncode) AS code, c.collectionname FROM omcollections c WHERE c.collid = '.$this->collid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->code] = $r->collectionname;
		}
		$rs->free();
		return $retArr;
	}

	private function getSqlWhere(){
		$sql = 'WHERE ((i.thumbnailurl IS NULL) OR (i.url = "empty")) ';
		if($this->collid) $sql .= 'AND (o.collid = '.$this->collid.') ';
		elseif($this->collid === '0') $sql .= 'AND (i.occid IS NULL) ';
		if($this->tidArr) $sql .= 'AND (e.taxauthid = 1) AND (i.tid IN('.implode(',',$this->tidArr).') OR e.parenttid IN('.implode(',',$this->tidArr).')) ';
		return $sql;
	}

	private function buildImageDerivatives($imgId, $catNum, $recUrlWeb, $recUrlTn, $recUrlOrig, $setFormat = false){
		$status = true;
		//Build target path
		$targetPath = '';
		if($this->collid){
			$collArr = $this->getCollectionInfo();
			$targetPath = key($collArr).'/';
		}
		else{
			$targetPath = 'misc/'.date('Ym').'/';
		}

		if($this->collid){
			if($catNum){
				$catNum = str_replace(array('/','\\',' '), '', $catNum);
				if(preg_match('/^(\D{0,8}\d{4,})/', $catNum, $m)){
					$catPath = substr($m[1], 0, -3);
					if(is_numeric($catPath) && strlen($catPath)<5) $catPath = str_pad($catPath, 5, "0", STR_PAD_LEFT);
					$targetPath .= $catPath.'/';
				}
				else{
					$targetPath .= '00000/';
				}
			}
			else{
				$targetPath .= date('Ym').'/';
			}
		}
		$this->imgManager->setTargetPath($targetPath);

		//Build derivatives
		$webIsEmpty = false;
		$imgUrl = trim($recUrlWeb);
		if((!$imgUrl || $imgUrl == 'empty') && $recUrlOrig){
			$imgUrl = trim($recUrlOrig);
			$webIsEmpty = true;
		}
		if($this->imgManager->parseUrl($imgUrl)){
			//Create thumbnail
			$imgTnUrl = '';
			if(!$recUrlTn || substr($recUrlTn,0,10) == 'processing'){
				if($this->imgManager->createNewImage('_tn',$this->imgManager->getTnPixWidth(),70)){
					$imgTnUrl = $this->imgManager->getUrlBase().$this->imgManager->getImgName().'_tn.jpg';
				}
				else{
					$this->errorMessage = 'ERROR building thumbnail: '.$this->imgManager->getErrStr();
					$errSql = 'UPDATE images SET thumbnailurl = "bad url" WHERE thumbnailurl IS NULL AND imgid = '.$imgId;
					$this->conn->query($errSql);
					$status = false;
				}
			}
			else{
				$imgTnUrl = $recUrlTn;
			}

			if($status && $imgTnUrl && $this->imgManager->uriExists($imgTnUrl)){
				$webFullUrl = '';
				$lgFullUrl = '';
				//If web image is too large, transfer to large image and create new web image
				list($sourceWidth, $sourceHeight) = getimagesize(str_replace(' ', '%20', $this->imgManager->getSourcePath()));
				if(!$webIsEmpty && !$recUrlOrig){
					$fileSize = $this->imgManager->getSourceFileSize();
					if($fileSize > $this->imgManager->getWebFileSizeLimit() || $sourceWidth > ($this->imgManager->getWebPixWidth()*1.2)){
						$lgFullUrl = $this->imgManager->getSourcePath();
						$webIsEmpty = true;
					}
				}
				if($webIsEmpty){
					if($sourceWidth && $sourceWidth < $this->imgManager->getWebPixWidth()){
						if(copy($this->imgManager->getSourcePath(),$this->imgManager->getTargetPath().$this->imgManager->getImgName().'_web'.$this->imgManager->getImgExt())){
							$webFullUrl = $this->imgManager->getUrlBase().$this->imgManager->getImgName().'_web'.$this->imgManager->getImgExt();
						}
					}
					if(!$webFullUrl){
						if($this->imgManager->createNewImage('_web',$this->imgManager->getWebPixWidth())){
							$webFullUrl = $this->imgManager->getUrlBase().$this->imgManager->getImgName().'_web.jpg';
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
				if($setFormat){
					if($this->imgManager->getFormat()){
						$sql .= ',format = "'.$this->imgManager->getFormat().'" ';
					}
				}
				$sql .= "WHERE ti.imgid = ".$imgId;
				//echo $sql;
				if(!$this->conn->query($sql)){
					$this->errorMessage = 'ERROR: thumbnail created but failed to update database: '.$this->conn->error;
					$this->logOrEcho($this->errorMessage,1);
					$status = false;
				}
			}
			$this->imgManager->reset();
		}
		else{
			$this->errorMessage= 'ERROR: unable to parse source image ('.$imgUrl.')';
			//$this->logOrEcho($this->errorMessage,1);
			$status = false;
		}
	}

	public function resetProcessing(){
		$sqlTN = 'UPDATE images SET thumbnailurl = NULL '.
			'WHERE (thumbnailurl = "") OR (thumbnailurl = "bad url") OR (thumbnailurl LIKE "processing %" AND thumbnailurl != "processing '.date('Y-m-d').'") ';
		$this->conn->query($sqlTN);
		$sqlWeb = 'UPDATE images SET url = "empty" '.
			'WHERE (url = "") OR (url LIKE "processing %" AND url != "processing '.date('Y-m-d').'") ';
		$this->conn->query($sqlWeb);
	}

	//Test and refresh image thumbnails for remote images
	public function getProcessingCnt($postArr){
		$retCnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(i.imgid) AS cnt '.$this->getRemoteImageSql($postArr);
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retCnt = $r->cnt;
			}
			$rs->free();
		}
		return $retCnt;
	}

	public function getRemoteImageCnt(){
		$retCnt = 0;
		$domain = $_SERVER['HTTP_HOST'];
		$sql = 'SELECT COUNT(i.imgid) AS cnt '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'WHERE (o.collid = '.$this->collid.') AND (i.thumbnailurl LIKE "%'.$domain.'/%" OR i.thumbnailurl LIKE "/%") '.
			'AND IFNULL(i.originalurl,url) LIKE "http%" AND (IFNULL(i.originalurl,url) NOT LIKE "%'.$domain.'/%") ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function refreshThumbnails($postArr){
		$this->imgManager = new ImageShared();
		$sql = 'SELECT o.occid, o.catalognumber, i.imgid, i.url, i.thumbnailurl, i.originalurl, i.format '.$this->getRemoteImageSql($postArr);
		//echo $sql.'<br/>';
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($r = $rs->fetch_object()){
			$cnt++;
			$url = $r->url;
			$urlTn = $r->thumbnailurl;
			$urlOrig = $r->originalurl;
			$this->logOrEcho($cnt.'. Rebuilding thumbnail: <a href="../imgdetails.php?imgid='.$r->imgid.'" target="_blank">'.$r->imgid.'</a> [cat#: '.$r->catalognumber.']...',0,'div');
			//echo 'evaluate_ts: '.$postArr['evaluate_ts'].'<br/>';
			$tsSource = 0;
			if($postArr['evaluate_ts']){
				$tsSource = $this->getRemoteModifiedTime($urlOrig?$urlOrig:$url);
				//echo 'tsSource: '.$tsSource.'<br/>';
				if($tsSource == -1){
					$this->logOrEcho('ERROR obtaining file creation date (filetime) from source images; image rebuild skipped',1);
					//echo 'skip<br/>';
					continue;
				}
			}
			if($this->unlinkImageFile($urlTn, $tsSource)) $urlTn = '';
			if($urlOrig){
				if($this->unlinkImageFile($url, $tsSource)) $url = '';
			}
			$setFormat = ($r->format?false:true);
			$this->buildImageDerivatives($r->imgid, $r->catalognumber, $url, $urlTn, $urlOrig, $setFormat);
		}
		$rs->free();
		if(!$cnt) $this->logOrEcho('<b>There are no images that match set criteria</b>',0,'div');
	}

	private function unlinkImageFile($url,$origTs){
		$status = false;
		if(!$GLOBALS['IMAGE_ROOT_PATH']){
			$this->logOrEcho('FATAL ERROR: IMAGE_ROOT_PATH not configured within portal configuration file',1);
			exit;
		}
		if(!$GLOBALS['IMAGE_ROOT_URL']){
			$this->logOrEcho('FATAL ERROR: IMAGE_ROOT_URL not configured within portal configuration file',1);
			exit;
		}
		if(substr($url, 0, 4) == 'http'){
			//Remove domain name
			$url = parse_url($url, PHP_URL_PATH);
		}
		if(strpos($url, $GLOBALS['IMAGE_ROOT_URL']) === 0){
			$path = $GLOBALS['IMAGE_ROOT_PATH'].substr($url,strlen($GLOBALS['IMAGE_ROOT_URL']));
			if($p = strpos($path,'?')) $path = substr($path,0,$p);
			if(!file_exists($path)) return true;
			if(is_writable($path)){
				$unlinkFile = false;
				if($origTs){
					$ts = filemtime($path);
					if(!$ts || $ts < $origTs){
						$unlinkFile = true;
					}
					else{
						$this->logOrEcho('Image derivatives are newer than source file: image rebuild skipped',1);
					}
				}
				else{
					$unlinkFile = true;
				}
				if($unlinkFile){
					if(unlink($path)) $status = true;
				}
			}
			else{
				$this->logOrEcho('ERROR rebuilding image, image file not writable: '.$path,1);
			}
		}
		return $status;
	}

	private function getRemoteImageSql($postArr){
		$domain = $_SERVER['HTTP_HOST'];
		$sql = 'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'WHERE (o.collid = '.$this->collid.') AND (i.thumbnailurl LIKE "%'.$domain.'/%" OR i.thumbnailurl LIKE "/%") '.
			'AND IFNULL(i.originalurl,url) LIKE "http%" AND IFNULL(i.originalurl,url) NOT LIKE "%'.$domain.'/%" ';
		if(array_key_exists('catNumHigh', $postArr) && $postArr['catNumHigh']){
			// Catalog numbers are given as a range
			if(is_numeric($postArr['catNumLow']) && is_numeric($postArr['catNumHigh'])){
				$sql .= 'AND (o.catalognumber BETWEEN '.$postArr['catNumLow'].' AND '.$postArr['catNumHigh'].') ';
			}
			else{
				$sql .= 'AND (o.catalognumber BETWEEN "'.$postArr['catNumLow'].'" AND "'.$postArr['catNumHigh'].'") ';
			}
		}
		elseif(array_key_exists('catNumLow', $postArr) && $postArr['catNumLow']){
			// Catalog numbers are given as a single value
			$sql .= 'AND (o.catalognumber = "'.$postArr['catNumLow'].'") ';
		}
		elseif(array_key_exists('catNumList', $postArr) && $postArr['catNumList']){
			$catNumList = preg_replace('/\s+/','","',str_replace(array("\r\n","\r","\n",','),' ',trim($postArr['catNumList'])));
			if($catNumList) $sql .= 'AND (o.catalognumber IN("'.$catNumList.'")) ';
		}
		return $sql;
	}

	private function getRemoteModifiedTime($filePath){
		$curl = curl_init($filePath);
		//Fetch only the header
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		// attempt to retrieve the modification date
		curl_setopt($curl, CURLOPT_FILETIME, true);

		$curlResult = curl_exec($curl);

		if($curlResult === false){
			$this->logOrEcho('ERROR retrieving modified date of original image file: '.curl_error($curl),1);
			return false;
		}

return -1;
		$ts = curl_getinfo($curl, CURLINFO_FILETIME);
		return $ts;
	}

	//URL testing
	public function testByCollid(){
		$sql = 'SELECT i.imgid, i.url, i.thumbnailurl, i.originalurl '.
			'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
			'WHERE o.collid IN('.$this->collid.')';
		return $this->testUrls($sql);
	}

	public function testByImgid($imgidStr){


	}

	private function testUrls($sql){
		$status = true;
		$badUrlArr = array();
		if(!$sql){
			$this->errorMessage= 'SQL string is NULL';
			return false;
		}
		$this->imgManager = new ImageShared();
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_object()){
				if(!$this->imgManager->uriExists($r->url)) $badUrlArr[$r->imgid]['url'] = $r->url;
				if(!$this->imgManager->uriExists($r->thumbnailurl)) $badUrlArr[$r->imgid]['tn'] = $r->thumbnailurl;
				if(!$this->imgManager->uriExists($r->originalurl)) $badUrlArr[$r->imgid]['lg'] = $r->originalurl;
			}
			$rs->free();
		}
		else{
			$this->errorMessage= 'Issue with connection or SQL: '.$sql;
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

	//Bulk removal of images
	public function recycleImagesFromFile($filePath){
		$this->setRecycleBin();
		if(!$filePath) exit('Image identifier file path IS NULL');
		if(!file_exists($filePath)) exit('Image identifier file Not Found');
		if(($imgidHandler = fopen($imgidFile, 'r')) !== FALSE){
			while(($data = fgets($imgidHandler)) !== FALSE){
				$this->recycleImage($data[0]);
			}
		}
	}

	public function recycleImagesFromStr($inputStr){
		$this->setRecycleBin();
		$inputStr = $this->cleanInStr($inputStr);
		$inputStr = preg_replace('/\s/',',',$inputStr);
		$inputStr = str_replace(',,',',',$inputStr);
		$idArr = explode(',',$inputStr);
		foreach($idArr as $imgid){
			if($imgid) $this->recycleImage($imgid);
		}
	}

	private function recycleImage($imgID){
		if(!is_numeric($imgID)) return false;
		if($this->imgRecycleBin){
			$sql = 'SELECT i.url, i.originalurl, i.thumbnailurl '.
				'FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (i.imgid = '.$imgID.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$imgUrlArr = array();
				if($r->originalurl) $imgUrlArr[] = $r->originalurl;
				if($r->thumbnailurl) $imgUrlArr[] = $r->thumbnailurl;
				if($r->url) $imgUrlArr[] = $r->url;

				$delRec = false;
				if($this->imgDelRecOverride) $delRec = true;
				foreach($imgUrlArr as $imgUrl){
					if(strpos($imgUrl, 'http://storage.idigbio.org') === 0){
						//Image is stored on iDigBio server
						$imgUrl = '/home/idigbio-storage.acis.ufl.edu'.parse_url($imgUrl, PHP_URL_PATH);
					}
					elseif(substr($imgUrl, 0, 4) == 'http'){
						$imgUrl = parse_url($imgUrl, PHP_URL_PATH);
					}
					if(strpos($imgUrl, $GLOBALS['IMAGE_ROOT_URL']) === 0){
						$imgUrl = $GLOBALS['IMAGE_ROOT_PATH'].substr($imgUrl,strlen($GLOBALS['IMAGE_ROOT_URL']));
					}
					if(is_writable($imgUrl)){
						$pathParts = pathinfo($imgUrl);
						$path = $pathParts['dirname'];
						if(strpos($path, $GLOBALS['IMAGE_ROOT_PATH']) === 0) $path = substr($path,strlen($GLOBALS['IMAGE_ROOT_PATH']));
						$targetPath = $this->imgRecycleBin.$path;
						if(!file_exists($targetPath)) mkdir($targetPath,0777,true);
						$targetPath .= '/'.$pathParts['basename'];
						if(rename($imgUrl,$targetPath)) $delRec = true;
					}
				}
				if($delRec){
					$this->conn->query('DELETE FROM images WHERE (imgid = '.$imgID.')');
				}
			}
			$rs->free();
		}
		return true;
	}

	private function setRecycleBin($binPath = ''){
		if($binPath){
			if(file_exists($binPath)){
				$this->imgRecycleBin = $binPath;
				return true;
			}
			else{
				$this->errorMessage = 'Recycle bin path does not exist: '.$binPath;
				return false;
			}
		}
		else{
			if($GLOBALS['IMAGE_ROOT_PATH']){
				$path = $GLOBALS['IMAGE_ROOT_PATH'];
				if(substr($path, -1) != '/') $path .= '/';
				$path .= 'trash';
				if(!file_exists($path)){
					if(!mkdir($path)){
						$this->errorMessage = 'Failed to create trash folder in IMAGE_ROOT_PATH';
						return false;
					}
				}
				$this->imgRecycleBin = $path;
				return true;
			}
			$this->errorMessage = 'ERROR: Failed to define recycle bin from configuration file';
			return false;
		}
	}

	public function setImgDelRecOverride($override){
		$this->imgDelRecOverride = $override;
	}

	//Setters and getters
	public function setCollid($id){
		if(is_numeric($id)){
			$this->collid = $id;
		}
	}

	public function setTid($id){
		if(is_numeric($id)){
			$this->tidArr[] = $id;
			$sql = 'SELECT DISTINCT ts.tid '.
				'FROM taxstatus ts INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
				'WHERE (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = '.$id.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->tid != $id) $this->tidArr[] = $r->tid;
			}
			$rs->free();
		}
	}

	public function getSciname(){
		$sciname = '';
		if($this->tidArr){
			$sql = 'SELECT sciname FROM taxa WHERE (tid = '.$this->tidArr[0].')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$sciname = $r->sciname;
			}
			$rs->free();
		}
		return $sciname;
	}
}
?>