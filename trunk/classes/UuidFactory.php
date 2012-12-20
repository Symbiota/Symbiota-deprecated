<?php
include_once($serverRoot.'/config/dbconnection.php');

class UuidFactory {

	private $silent = 0;

	public function populateGuids($collId = 0){
		$conn = MySQLiConnectionFactory::getCon("write");
		$this->echoStr("Starting batch GUID processing (".date('Y-m-d h:i:s A').")\n");

		//Populate Collection GUIDs
		$this->echoStr("Populating collection GUIDs\n");
		$sql = 'SELECT c.collid '.
			'FROM omcollections c LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "omcollections") g ON c.collid = g.tablepk '.
			'WHERE g.tablepk IS NULL LIMIT 10 ';
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$id = $r->collid;
			$guid = UuidFactory::getUuidV4();
			$insSql = 'INSERT INTO guids(guid,tablename,tablepk) '.
				'VALUES("'.$guid.'","omcollections",'.$id.')';
			$conn->query($insSql);
		}
		$this->echoStr("Starting batch process (".date('Y-m-d h:i:s A').")\n");
		
		//Populate occurrence GUIDs
		$this->echoStr("Populating occurrence GUIDs\n");
		$sql = 'SELECT o.occid '.
			'FROM omoccurrences o LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "omoccurrences") g ON o.occid = g.tablepk '.
			'WHERE g.tablepk IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		$sql .= ' LIMIT 10';
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$id = $r->occid;
			$guid = UuidFactory::getUuidV4();
			$insSql = 'INSERT INTO guids(guid,tablename,tablepk) '.
				'VALUES("'.$guid.'","omoccurrences",'.$id.')';
			$conn->query($insSql);
		}

		//Populate determination GUIDs
		$this->echoStr("Populating determination GUIDs\n");
		$sql = 'SELECT d.detid '.
			'FROM omoccurdeterminations d LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "omcollections") g ON d.detid = g.tablepk ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		$sql .= 'WHERE g.tablepk IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		$sql .= ' LIMIT 10';
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$id = $r->detid;
			$guid = UuidFactory::getUuidV4();
			$insSql = 'INSERT INTO guids(guid,tablename,tablepk) '.
				'VALUES("'.$guid.'","omoccurdeterminations",'.$id.')';
			$conn->query($insSql);
		}

		//Populate image GUIDs
		$this->echoStr("Populating image GUIDs\n");
		$sql = 'SELECT i.imgid '.
			'FROM images i LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "omcollections") g ON i.imgid = g.tablepk ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE g.tablepk IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		$sql .= ' LIMIT 10';
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$id = $r->imgid;
			$guid = UuidFactory::getUuidV4();
			$insSql = 'INSERT INTO guids(guid,tablename,tablepk) '.
				'VALUES("'.$guid.'","images",'.$id.')';
			$conn->query($insSql);
		}

		$this->echoStr("GUID batch processing complete (".date('Y-m-d h:i:s A').")\n");
		if(!($conn === false)) $conn->close();
	}

	public function getCollectionCount(){
		$retCnt = 0;
		$conn = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT count(c.collid) as reccnt '.
			'FROM omcollections c LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "omcollections") g ON c.collid = g.tablepk '.
			'WHERE g.tablepk IS NULL ';
		//echo $sql;
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		if(!($conn === false)) $conn->close();
		return $retCnt;
	}

	public function getOccurrenceCount($collId = 0){
		$retCnt = 0;
		$conn = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT COUNT(o.occid) as reccnt '.
			'FROM omoccurrences o LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "omoccurrences") g ON o.occid = g.tablepk '.
			'WHERE g.tablepk IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		if(!($conn === false)) $conn->close();
		return $retCnt;
	}

	public function getDeterminationCount($collId = 0){
		$retCnt = 0;
		$conn = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT COUNT(d.detid) as reccnt '.
			'FROM omoccurdeterminations d LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "omoccurdeterminations") g ON d.detid = g.tablepk ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		$sql .= 'WHERE g.tablepk IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		if(!($conn === false)) $conn->close();
		return $retCnt;
	}

	public function getImageCount($collId = 0){
		$retCnt = 0;
		$conn = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT COUNT(i.imgid) as reccnt '.
			'FROM images i LEFT JOIN (SELECT tablepk, tablename FROM guids WHERE tablename = "images") g ON i.imgid = g.tablepk ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE g.tablepk IS NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$retCnt = $r->reccnt;
		}
		$rs->free();
		if(!($conn === false)) $conn->close();
		return $retCnt;
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