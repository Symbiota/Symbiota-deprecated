<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class UuidFactory {

	private $silent = 0;
	private $conn;
	private $destructConn = true;

	public function __construct($con = null){
		if($con){
			//Inherits connection from another class
			$this->conn = $con;
			$this->destructConn = false;
		}
		else{
			$this->conn = MySQLiConnectionFactory::getCon("write");
		}
	}

	public function __destruct(){
		if($this->destructConn && !($this->conn === null)){
			$this->conn->close();
			$this->conn = null;
		}
	}

	public function populateGuids($collId = 0){
		set_time_limit(1000);
		
		$this->echoStr("Starting batch GUID processing (".date('Y-m-d h:i:s A').")\n");

		//Populate Collection GUIDs
		$this->echoStr("Populating collection GUIDs (all collections by default)");
		$sql = 'SELECT collid FROM omcollections WHERE collectionguid IS NULL ';
		$rs = $this->conn->query($sql);
		$recCnt = 0;
		if($rs->num_rows){
			while($r = $rs->fetch_object()){
				$guid = UuidFactory::getUuidV4();
				$insSql = 'UPDATE omcollections SET collectionguid = "'.$guid.'" '.
					'WHERE collectionguid IS NULL AND collid = '.$r->collid;
				if(!$this->conn->query($insSql)){
					$this->echoStr('ERROR: '.$this->conn->error);
				}
				$recCnt++;
			}
			$rs->free();
		}
		$this->echoStr("Finished: $recCnt collection records processed\n");
		
		//Populate occurrence GUIDs
		$this->echoStr("Populating occurrence GUIDs\n");
		$sql = 'SELECT o.occid '.
			'FROM omoccurrences o LEFT JOIN guidoccurrences g ON o.occid = g.occid '.
			'WHERE g.occid IS NULL ';
		//$sql = 'SELECT o.occid '.
		//	'FROM omoccurrences o '.
		//	'WHERE o.occid NOT IN(SELECT occid FROM guidoccurrences WHERE occid IS NOT NULL) ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		$rs = $this->conn->query($sql);
		$recCnt = 0;
		if($rs->num_rows){
			while($r = $rs->fetch_object()){
				$guid = UuidFactory::getUuidV4();
				$insSql = 'INSERT INTO guidoccurrences(guid,occid) '.
					'VALUES("'.$guid.'",'.$r->occid.')';
				if(!$this->conn->query($insSql)){
					$this->echoStr('ERROR: occur guids'.$this->conn->error);
				}
				$recCnt++;
				if($recCnt%1000 === 0) $this->echoStr($recCnt.' records processed');
			}
			$rs->free();
		}
		$this->echoStr("Finished: $recCnt occurrence records processed\n");
		
		//Populate determination GUIDs
		$this->echoStr("Populating determination GUIDs\n");
		$sql = 'SELECT d.detid FROM omoccurdeterminations d LEFT JOIN guidoccurdeterminations g ON d.detid = g.detid ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		$sql .= 'WHERE g.detid IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//$sql = 'SELECT d.detid FROM omoccurdeterminations d ';
		//if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		//$sql .= 'WHERE d.detid NOT IN(SELECT detid FROM guidoccurdeterminations WHERE detid IS NOT NULL) ';
		//if($collId) $sql .= 'AND o.collid = '.$collId;
		$rs = $this->conn->query($sql);
		$recCnt = 0;
		if($rs->num_rows){
			while($r = $rs->fetch_object()){
				$guid = UuidFactory::getUuidV4();
				$insSql = 'INSERT INTO guidoccurdeterminations(guid,detid) '.
					'VALUES("'.$guid.'",'.$r->detid.')';
				if(!$this->conn->query($insSql)){
					$this->echoStr('ERROR: det guids '.$this->conn->error);
				}
				$recCnt++;
				if($recCnt%1000 === 0) $this->echoStr($recCnt.' records processed');
			}
			$rs->free();
		}
		$this->echoStr("Finished: $recCnt determination records processed\n");
		
		//Populate image GUIDs
		$this->echoStr("Populating image GUIDs\n");
		$sql = 'SELECT i.imgid FROM images i LEFT JOIN guidimages g ON i.imgid = g.imgid ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE g.imgid IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//$sql = 'SELECT i.imgid FROM images i ';
		//if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		//$sql .= 'WHERE i.imgid NOT IN(SELECT imgid FROM guidimages WHERE imgid IS NOT NULL) ';
		//if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $this->conn->query($sql);
		$recCnt = 0;
		if($rs->num_rows){
			while($r = $rs->fetch_object()){
				$guid = UuidFactory::getUuidV4();
				$insSql = 'INSERT INTO guidimages(guid,imgid) '.
					'VALUES("'.$guid.'",'.$r->imgid.')';
				if(!$this->conn->query($insSql)){
					$this->echoStr('ERROR: image guids; '.$this->conn->error);
				}
				$recCnt++;
				if($recCnt%1000 === 0) $this->echoStr($recCnt.' records processed');
			}
			$rs->free();
		}
		$this->echoStr("Finished: $recCnt image records processed\n");
		
		$this->echoStr("GUID batch processing complete (".date('Y-m-d h:i:s A').")\n");
	}
	
	public function getCollectionCount(){
		$retCnt = 0;
		$sql = 'SELECT count(c.collid) as reccnt '.
			'FROM omcollections '.
			'WHERE collectionguid IS NULL ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getOccurrenceCount($collId = 0){
		$retCnt = 0;
		$sql = 'SELECT COUNT(o.occid) as reccnt '.
			'FROM omoccurrences o LEFT JOIN guidoccurrences g ON o.occid = g.occid '.
			'WHERE g.occid IS NULL ';
		//$sql = 'SELECT COUNT(o.occid) as reccnt '.
		//	'FROM omoccurrences o '.
		//	'WHERE o.occid NOT IN (SELECT occid FROM guidoccurrences WHERE occid IS NOT NULL) ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getDeterminationCount($collId = 0){
		$retCnt = 0;
		$sql = 'SELECT COUNT(d.detid) as reccnt '.
			'FROM omoccurdeterminations d LEFT JOIN guidoccurdeterminations g ON d.detid = g.detid ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		$sql .= 'WHERE g.detid IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//$sql = 'SELECT COUNT(d.detid) as reccnt '.
		//	'FROM omoccurdeterminations d ';
		//if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		//$sql .= 'WHERE d.detid NOT IN (SELECT detid FROM guidoccurdeterminations WHERE detid IS NOT NULL) ';
		//if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getImageCount($collId = 0){
		$retCnt = 0;
		$sql = 'SELECT COUNT(i.imgid) as reccnt '.
			'FROM images i LEFT JOIN guidimages g ON i.imgid = g.imgid ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE g.imgid IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//$sql = 'SELECT COUNT(i.imgid) as reccnt '.
		//	'FROM images i ';
		//if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		//$sql .= 'WHERE i.imgid NOT IN(SELECT imgid FROM guidimages WHERE imgid IS NOT NULL) ';
		//if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		return $retCnt;
	}
	
	public function getCollectionName($collId){
		$retStr = '';
		$sql = 'SELECT CONCAT(collectionname," (",CONCAT_WS("-",institutioncode,collectioncode),")") as collname '.
			'FROM omcollections WHERE collid = '.$collId;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retStr = $r->collname;
		}
		$rs->free();
		return $retStr;
	}

	public function setSilent($c){
		$this->silent = $c;
	}

	public function getSilent(){
		return $this->silent;
	}

	private function echoStr($str){
		if(!$this->silent){
			echo '<li>'.$str.'</li>';
			ob_flush();
			flush();
		}
	}

	//Static functions
	public static function getUuidV3($namespace, $name) {
		if(!self::is_valid($namespace)) return false;

		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);

		// Binary Value
		$nstr = '';

		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) {
			$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		}

		// Calculate hash value
		$hash = md5($nstr . $name);

		return sprintf('%08s-%04s-%04x-%04x-%12s',

			// 32 bits for "time_low"
			substr($hash, 0, 8),

			// 16 bits for "time_mid"
			substr($hash, 8, 4),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 3
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}

	public static function getUuidV4() {
		/*
		 * Following function is only psuedo randum  
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
		*/
		$data = null;
		if(function_exists('openssl_random_pseudo_bytes')){
			$data = openssl_random_pseudo_bytes(16);
		}
		if(!$data && file_exists('/dev/urandom')){
			$data = file_get_contents('/dev/urandom', NULL, NULL, 0, 16);
		}
		if(!$data && file_exists('/dev/random')){
			$data = file_get_contents('/dev/random', NULL, NULL, 0, 16);
		}
		if(!$data){
			for($cnt = 0; $cnt < 16; $cnt ++) {
				$data .= chr ( mt_rand ( 0, 255 ) );
			}
		}
		if(!$data) return '';

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	public static function getUuidV5($namespace, $name) {
		if(!self::is_valid($namespace)) return false;

		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-','{','}'), '', $namespace);

		// Binary Value
		$nstr = '';

		// Convert Namespace UUID to bits
		for($i = 0; $i < strlen($nhex); $i+=2) {
			$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		}

		// Calculate hash value
		$hash = sha1($nstr . $name);

		return sprintf('%08s-%04s-%04x-%04x-%12s',

			// 32 bits for "time_low"
			substr($hash, 0, 8),

			// 16 bits for "time_mid"
			substr($hash, 8, 4),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 5
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}

	public static function is_valid($uuid) {
		return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
	}
}
?>