<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/UuidFactory.php');

class OccurrenceSkeletal {

	private $conn;
	private $collid;
	private $collectionMap = array();
	private $stateList = array();
	private $errorStr = '';

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->stateList = array('AK' => 'Alaska', 'AL' => 'Alabama', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
			'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia', 'FL' => 'Florida',
			'GA' => 'Georgia', 'GU' => 'Guam', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' =>
			'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MH' => 'Marshall Islands', 'MD' =>
			'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
			'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
			'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'MP' => 'Northern Mariana Islands', 'OH' => 'Ohio',
			'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PW' => 'Palau', 'PA' => 'Pennsylvania', 'PR' => 'Puerto Rico', 'RI' => 'Rhode Island',
			'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
			'VI' => 'Virgin Islands', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' =>  'Wyoming');
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function occurrenceAdd($postArr){
		$occid = 0;
		if($this->collid){
			if($this->isEditor()){
				$allowedFields = array('collid'=>'n','catalognumber'=>'s','othercatalognumbers'=>'s','sciname'=>'s','tidinterpreted'=>'s','family'=>'s',
					'scientificnameauthorship'=>'s','localitysecurity'=>'n','country'=>'s','stateprovince'=>'s','county'=>'s','processingstatus'=>'s',
					'recordedby'=>'s','recordnumber'=>'s','eventdate'=>'d','language'=>'s');
				if(isset($postArr['stateprovince']) && $postArr['stateprovince'] && strlen($postArr['stateprovince']) == 2){
					$postArr['stateprovince'] = $this->translateStateAbbreviation($postArr['stateprovince']);
				}
				//If country is NULL and state populated, grab country from geo-lookup tables
				if((!isset($postArr['country']) || !$postArr['country']) && isset($postArr['stateprovince']) && $postArr['stateprovince']){
					$postArr['country'] = $this->getCountry($postArr['stateprovince']);
				}
				//Add record
				$sql1 = '';
				$sql2 = '';
				foreach($allowedFields as $f => $dataType){
					$sql1 .= ','.$f;
					if(array_key_exists($f, $postArr) && ($postArr[$f] || $postArr[$f] === 0)){
						$v = $postArr[$f];
						if($dataType == 'n' && is_numeric($v)){
							$sql2 .= ','.$v;
						}
						else{
							$sql2 .= ',"'.$this->cleanInStr($v).'"';
						}
					}
					else{
						$sql2 .= ',NULL';
					}
				}
				$sql = 'INSERT INTO omoccurrences('.trim($sql1,' ,').',recordenteredby,dateentered) '.
					'VALUES('.trim($sql2,' ,').',"'.$GLOBALS['USERNAME'].'","'.date('Y-m-d H:i:s').'")';
				//echo $sql;
				if($this->conn->query($sql)){
					$occid = $this->conn->insert_id;
					//Update collection stats
					$this->conn->query('UPDATE omcollectionstats SET recordcnt = recordcnt + 1 WHERE collid = '.$this->collid);
					//Create and insert Symbiota GUID (UUID)
					$guid = UuidFactory::getUuidV4();
					if(!$this->conn->query('INSERT INTO guidoccurrences(guid,occid) VALUES("'.$guid.'",'.$occid.')')){
						$this->errorStr = '(WARNING: Symbiota GUID mapping failed) ';
					}
				}
				else{
					$this->errorStr = 'ERROR adding occurrence record: '.$this->conn->error;
				}
			}
		}
		return $occid;
	}

	public function catalogNumberExists($catNum){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT occid FROM omoccurrences '.
				'WHERE (catalognumber = "'.$this->cleanInStr($catNum).'") AND (collid = '.$this->collid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr[] = $r->occid;
			}
			$rs->free();
		}
		if($retArr){
			$this->errorStr = implode(',',$retArr);
			return true;
		}
		return false;
	}

	private function getCountry($state){
		$countryStr = '';
		if($state){
			if(in_array(ucwords($state),$this->stateList)){
				$countryStr = 'United States';
			}
			else{
				$sql = 'SELECT c.countryname '.
					'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
					'WHERE s.statename = "'.$state.'"';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()) {
					$countryStr = $r->countryname;
				}
				$rs->free();
			}
		}
		return $countryStr;
	}

	private function translateStateAbbreviation($abbr){
		$stateStr = '';
		if(array_key_exists($abbr,$this->stateList)){
			$stateStr = $this->stateList[$abbr];
		}
		return $stateStr;
	}

	private function isEditor(){
		if(!isset($GLOBALS['SYMB_UID'])) return false;
		if($GLOBALS['IS_ADMIN']) return true;
		if(isset($GLOBALS['USER_RIGHTS']['CollAdmin']) && in_array($this->collid, $GLOBALS['USER_RIGHTS']['CollAdmin'])) return true;
		if(isset($GLOBALS['USER_RIGHTS']['CollEditor']) && in_array($this->collid, $GLOBALS['USER_RIGHTS']['CollEditor'])) return true;
		return false;
	}

	//Setters and getters
	public function setCollId($id){
		if($id && is_numeric($id)){
			$this->collid = $id;
			$this->setCollectionMap();
		}
	}

	private function setCollectionMap(){
		if($this->collid){
			$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, colltype, managementtype '.
				'FROM omcollections '.
				'WHERE (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->collectionMap['collectionname'] = $row->collectionname;
				$this->collectionMap['institutioncode'] = $row->institutioncode;
				$this->collectionMap['collectioncode'] = $row->collectioncode;
				$this->collectionMap['colltype'] = $row->colltype;
				$this->collectionMap['managementtype'] = $row->managementtype;
			}
			$rs->free();
		}
	}

	public function getCollectionMap(){
		return $this->collectionMap;
	}

	public function getLanguageArr(){
		$retArr = array();
		$sql = 'SELECT iso639_1, langname '.
			'FROM adminlanguages ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->iso639_1] = $r->langname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>