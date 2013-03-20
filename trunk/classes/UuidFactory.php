<?php
include_once($serverRoot.'/config/dbconnection.php');

class UuidFactory {

	private $silent = 0;

	public function populateGuids($collId = 0){
		set_time_limit(1000);
		
		$conn = MySQLiConnectionFactory::getCon("write");
		$this->echoStr("Starting batch GUID processing (".date('Y-m-d h:i:s A').")\n");

		//Populate Collection GUIDs
/*		$sql = 'SELECT collid '.
			'FROM omcollections '.
			'WHERE collectionguid IS NULL ';
		$rs = $conn->query($sql);
		if($rs->num_rows) $this->echoStr("Populating collection GUIDs");
		$recCnt = 0;
		while($r = $rs->fetch_object()){
			$guid = UuidFactory::getUuidV4();
			$insSql = 'UPDATE omcollections SET collectionguid = "'.$guid.'" WHERE collid = '.$r->collid;
			if(!$conn->query($insSql)){
				$this->echoStr('ERROR: '.$conn->error);
			}
			$recCnt++;
		}
		$rs->free();
		if($recCnt) $this->echoStr("Finished: $recCnt collection records processed\n");
*/		
		//Populate occurrence GUIDs
		$sql = 'SELECT o.occid '.
			'FROM omoccurrences o '.
			'WHERE o.occid NOT IN(SELECT occid FROM guidoccurrences) ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		$rs = $conn->query($sql);
		if($rs->num_rows) $this->echoStr("Populating occurrence GUIDs\n");
		$recCnt = 0;
		while($r = $rs->fetch_object()){
			$guid = UuidFactory::getUuidV4();
			$insSql = 'INSERT INTO guidoccurrences(guid,occid) '.
				'VALUES("'.$guid.'",'.$r->occid.')';
			if(!$conn->query($insSql)){
				$this->echoStr('ERROR: occur guids'.$conn->error);
			}
			$recCnt++;
			if($recCnt%1000 === 0) $this->echoStr($recCnt.' records processed');
		}
		$rs->free();
		if($recCnt) $this->echoStr("Finished: $recCnt occurrence records processed\n");
		
		//Populate determination GUIDs
		$sql = 'SELECT d.detid '.
			'FROM omoccurdeterminations d ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		$sql .= 'WHERE d.detid NOT IN(SELECT detid FROM guidoccurdeterminations) ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		$rs = $conn->query($sql);
		if($rs->num_rows) $this->echoStr("Populating determination GUIDs\n");
		$recCnt = 0;
		while($r = $rs->fetch_object()){
			$guid = UuidFactory::getUuidV4();
			$insSql = 'INSERT INTO guidoccurdeterminations(guid,detid) '.
				'VALUES("'.$guid.'",'.$r->detid.')';
			if(!$conn->query($insSql)){
				$this->echoStr('ERROR: det guids '.$conn->error);
			}
			$recCnt++;
			if($recCnt%1000 === 0) $this->echoStr($recCnt.' records processed');
		}
		$rs->free();
		if($recCnt) $this->echoStr("Finished: $recCnt determination records processed\n");
		
		//Populate image GUIDs
		$sql = 'SELECT i.imgid '.
			'FROM images i ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE i.imgid NOT IN(SELECT imgid FROM guidimages) ';
		if($collId) $sql .= 'AND o.collid = '.$collId;
		//echo $sql;
		$rs = $conn->query($sql);
		if($rs->num_rows) $this->echoStr("Populating image GUIDs\n");
		$recCnt = 0;
		while($r = $rs->fetch_object()){
			$guid = UuidFactory::getUuidV4();
			$insSql = 'INSERT INTO guidimages(guid,imgid) '.
				'VALUES("'.$guid.'",'.$r->imgid.')';
			if(!$conn->query($insSql)){
				$this->echoStr('ERROR: image guids; '.$conn->error);
			}
			$recCnt++;
			if($recCnt%1000 === 0) $this->echoStr($recCnt.' records processed');
		}
		$rs->free();
		if($recCnt) $this->echoStr("Finished: $recCnt image records processed\n");
		
		$this->echoStr("GUID batch processing complete (".date('Y-m-d h:i:s A').")\n");
		if(!($conn === false)) $conn->close();
	}
	
	public function getCollectionCount(){
		$retCnt = 0;
		$conn = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT count(c.collid) as reccnt '.
			'FROM omcollections c '.
			'WHERE c.collid NOT IN(SELECT tablepk FROM guids WHERE tablename = "omcollections") ';
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
			'FROM omoccurrences o '.
			'WHERE o.occid NOT IN (SELECT occid FROM guidoccurrences) ';
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
			'FROM omoccurdeterminations d ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON d.occid = o.occid ';
		$sql .= 'WHERE d.detid NOT IN (SELECT detid FROM guidoccurdeterminations) ';
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
			'FROM images i ';
		if($collId) $sql .= 'INNER JOIN omoccurrences o ON i.occid = o.occid ';
		$sql .= 'WHERE i.imgid NOT IN(SELECT imgid FROM guidimages) ';
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
	
	public function getCollectionName($collId){
		$retStr = '';
		$conn = MySQLiConnectionFactory::getCon("readonly");
		$sql = 'SELECT CONCAT(collectionname," (",CONCAT_WS("-",institutioncode,collectioncode),")") as collname '.
			'FROM omcollections WHERE collid = '.$collId;
		//echo $sql;
		$rs = $conn->query($sql);
		while($r = $rs->fetch_object()){
			$retStr = $r->collname;
		}
		$rs->free();
		if(!($conn === false)) $conn->close();
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