<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once("Person.php");

class ProfileManager{

	private $rememberMe = false;
	private $uid;
	private $userName;

	private $displayName;
	private $visitId;
	private $userRights = Array();
	private $conn;
	private $errorStr;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	private function getConnection($type){
		return MySQLiConnectionFactory::getCon($type);
	}
	
	public function reset(){
		//Delete cookies
		$domainName = $_SERVER['SERVER_NAME'];
		if(!$domainName) $domainName = $_SERVER['HTTP_HOST'];
		if($domainName == 'localhost') $domainName = false;
		setcookie("SymbiotaBase", "", time() - 3600, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'),$domainName,false,true);
		setcookie("SymbiotaRights", "", time() - 3600, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'),$domainName,false,true);
		setcookie("SymbiotaBase", "", time() - 3600, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'));
		setcookie("SymbiotaRights", "", time() - 3600, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'));
	}
	
	public function setCookies(){
		$cookieStr = "un=".$this->userName;
		$cookieStr .= "&dn=".$this->displayName;
		$cookieStr .= "&uid=".$this->uid;
		$cookieExpire = 0;
		if($this->rememberMe){
			$cookieExpire = time()+60*60*24*30;
		}
		$domainName = $_SERVER['SERVER_NAME'];
		if(!$domainName) $domainName = $_SERVER['HTTP_HOST'];
		if($domainName == 'localhost') $domainName = false;
		setcookie("SymbiotaBase", $cookieStr, $cookieExpire, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'),$domainName,false,true);
		//Set admin cookie
		if($this->userRights){
			$cookieStr = '';
			foreach($this->userRights as $name => $vArr){
				$vStr = implode(',',$vArr);
				$cookieStr .= $name.($vStr?'-'.$vStr:'').'&';
			}
			setcookie("SymbiotaRights", trim($cookieStr,"&"), $cookieExpire, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'),$domainName,false,true);
		}
	}
	
	public function authenticate($pwdStr = ''){
		$authStatus = false;
		if($this->userName){
			$sql = 'SELECT u.uid, u.firstname, u.lastname '.
				'FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
				'WHERE (ul.username = "'.$this->userName.'") ';
			if($pwdStr) $sql .= 'AND (ul.password = PASSWORD("'.$this->cleanInStr($pwdStr).'")) ';
			//echo $sql;
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->uid = $row->uid;
				$this->displayName = $row->firstname;
				if(strlen($this->displayName) > 15) $this->displayName = $this->userName;
				if(strlen($this->displayName) > 15) $this->displayName = substr($this->displayName,0,10).'...';
				
				$authStatus = true;
				$this->userRights = array();
				$this->reset();
				$this->setUserRights();
				$this->setCookies();
				
				//Update last login data
				$conn = $this->getConnection("write");
				$sql = 'UPDATE userlogin SET lastlogindate = NOW() WHERE (username = "'.$this->userName.'")';
				$conn->query($sql);
				$conn->close();
			}
		}
		return $authStatus;
	}
	
	public function getPerson(){
		$sqlStr = "SELECT u.uid, u.firstname, u.lastname, u.title, u.institution, u.department, ".
			"u.address, u.city, u.state, u.zip, u.country, u.phone, u.email, ".
			"u.url, u.biography, u.ispublic, u.notes, ul.username, ul.lastlogindate ".
			"FROM users u LEFT JOIN userlogin ul ON u.uid = ul.uid ".
			"WHERE (u.uid = ".$this->uid.")";
		$person = new Person();
		//echo $sqlStr;
		$badUserNameArr = array();
		$result = $this->conn->query($sqlStr);
		if($row = $result->fetch_object()){
			$person->setUid($row->uid);
			$person->setUserName($row->username);
			$person->setLastLoginDate($row->lastlogindate);
			$person->setFirstName($row->firstname);
			$person->setLastName($row->lastname);
			$person->setTitle($row->title);
			$person->setInstitution($row->institution);
			$person->setDepartment($row->department);
			$person->setAddress($row->address);
			$person->setCity($row->city);
			$person->setState($row->state);
			$person->setZip($row->zip);
			$person->setCountry($row->country);
			$person->setPhone($row->phone);
			$person->setEmail($row->email);
			$person->setUrl($row->url);
			$person->setBiography($row->biography);
			$person->setIsPublic($row->ispublic);
			$this->setUserTaxonomy($person);
			while($row = $result->fetch_object()){
				//Old code allowed folks to maintain more than one login names. This code will make sure the most recently active one is used 
				if($row->lastlogindate && (!$person->getLastLoginDate() || $row->lastlogindate > $person->getLastLoginDate())){
					$badUserNameArr[] = $person->getUserName();
					$person->setUserName($row->username);
					$person->setLastLoginDate($row->lastlogindate);
				}
				else{
					$badUserNameArr[] = $row->userName;
				}
			}
		}
		if($badUserNameArr){
			//Delete the none active logins
			$sql = 'DELETE FROM userlogin WHERE username IN("'.implode('","',$badUserNameArr).'")';
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR removing extra logins: '.$this->conn->error;
			}
		}
		$result->free();
		return $person;
	}
	
	public function updateProfile($person){
		$success = false;
		if($person){
			$editCon = $this->getConnection("write");
			$fields = 'UPDATE users SET ';
			$where = 'WHERE (uid = '.$person->getUid().')';
			$values = 'firstname = "'.$this->cleanInStr($person->getFirstName()).'"';
			$values .= ', lastname= "'.$this->cleanInStr($person->getLastName()).'"';
			$values .= ', title= "'.$this->cleanInStr($person->getTitle()).'"';
			$values .= ', institution="'.$this->cleanInStr($person->getInstitution()).'"';
			$values .= ', department= "'.$this->cleanInStr($person->getDepartment()).'"';
			$values .= ', address= "'.$this->cleanInStr($person->getAddress()).'"';
			$values .= ', city="'.$this->cleanInStr($person->getCity()).'"';
			$values .= ', state="'.$this->cleanInStr($person->getState()).'"';
			$values .= ', zip="'.$this->cleanInStr($person->getZip()).'"';
			$values .= ', country= "'.$this->cleanInStr($person->getCountry()).'"';
			$values .= ', phone="'.$this->cleanInStr($person->getPhone()).'"';
			$values .= ', email="'.$this->cleanInStr($person->getEmail()).'"';
			$values .= ', url="'.$this->cleanInStr($person->getUrl()).'"';
			$values .= ', biography="'.$this->cleanInStr($person->getBiography()).'"';
			$values .= ', ispublic='.$this->cleanInStr($person->getIsPublic()).' ';
			$sql = $fields." ".$values." ".$where;
			//echo $sql;
			$success = $editCon->query($sql);
			$editCon->close();
		}
		return $success;
	}

	public function deleteProfile($reset = 0){
		$success = false;
		if($this->uid){
			$editCon = $this->getConnection("write");
			$sql = "DELETE FROM users WHERE (uid = ".$this->uid.')';
			//echo $sql; Exit;
			$success = $editCon->query($sql);
			$editCon->close();
		}
		if($reset) $this->reset();
		return $success;
	}

	public function changePassword ($newPwd, $oldPwd = "", $isSelf = 0) {
		$success = false;
		if($newPwd){
			$editCon = $this->getConnection("write");
			if($isSelf){
				$sqlTest = 'SELECT ul.uid FROM userlogin ul WHERE (ul.uid = '.$this->uid.') '.
					'AND (ul.password = PASSWORD("'.$this->cleanInStr($oldPwd).
					'"))';
				$rsTest = $editCon->query($sqlTest);
				if(!$rsTest->num_rows) return false;
			}
			$sql = 'UPDATE userlogin ul SET ul.password = PASSWORD("'.$this->cleanInStr($newPwd).'") '.
				'WHERE (uid = '.$this->uid.')';
			$successCnt = $editCon->query($sql);
			$editCon->close();
			if($successCnt > 0) $success = true;
		}
		return $success;
	}
	
	public function resetPassword($un){
		global $charset;
		$newPassword = $this->generateNewPassword();
		$status = false;
		$returnStr = "";
		if($un){
			$editCon = $this->getConnection('write');
			$sql = 'UPDATE userlogin ul SET ul.password = PASSWORD("'.$this->cleanInStr($newPassword).'") '. 
					'WHERE (ul.username = "'.$this->cleanInStr($un).'")';
			$status = $editCon->query($sql);
			$editCon->close();
		}
		if($status){
			//Get email address
			$emailStr = ""; 
			$sql = 'SELECT u.email FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
				'WHERE (ul.username = "'.$this->cleanInStr($un).'")';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$emailStr = $row->email;
			}
			$result->free();

			//Send email
			$subject = "Your password";
			$bodyStr = "Your ".$GLOBALS["defaultTitle"]." (<a href='http://".$_SERVER['SERVER_NAME'].$GLOBALS["clientRoot"]."'>http://".$_SERVER['SERVER_NAME'].$GLOBALS["clientRoot"]."</a>) password has been reset to: ".$newPassword." ";
			$bodyStr .= "<br/><br/>After logging in, you can reset your password by clicking on <a href='http://".$_SERVER['SERVER_NAME'].$GLOBALS["clientRoot"]."/profile/viewprofile.php'>View Profile</a> link and then click the Edit Profile tab.";
			$bodyStr .= "<br/>If you have problems with the new password, contact the System Administrator ";
			if(array_key_exists("adminEmail",$GLOBALS)){
				$bodyStr .= "<".$GLOBALS["adminEmail"].">";
			}
			$headerStr = "MIME-Version: 1.0 \r\n".
				"Content-type: text/html; charset=".$charset." \r\n".
				"To: ".$emailStr." \r\n";
			if(array_key_exists("adminEmail",$GLOBALS)){
				$headerStr .= "From: Admin <".$GLOBALS["adminEmail"]."> \r\n";
			}
			mail($emailStr,$subject,$bodyStr,$headerStr);
			
			$returnStr = "Your new password was just emailed to: ".$emailStr;
		}
		else{
			$returnStr = "Reset Failed! Contact Administrator";
		}
		return $returnStr;
	}
	
	private function generateNewPassword(){
		// generate new random password
		$newPassword = "";
		$alphabet = str_split("0123456789abcdefghijklmnopqrstuvwxyz");
		for($i = 0; $i<8; $i++) {
			$newPassword .= $alphabet[rand(0,count($alphabet)-1)];
		}
		return $newPassword;
	}
	
	public function register($postArr){
		$status = false;
		
		$firstName = $postArr['firstname'];
		$lastName = $postArr['lastname'];
		if($postArr['institution'] && !trim(strpos($postArr['institution'],' ')) && preg_match('/[a-z]+[A-Z]+[a-z]+[A-Z]+/',$postArr['institution'])){
			if($postArr['title'] && !trim(strpos($postArr['title'],' ')) && preg_match('/[a-z]+[A-Z]+[a-z]+[A-Z]+/',$postArr['title'])){
				return false;
			}
		}
		
		$person = new Person();
		$person->setPassword($postArr['pwd']);
		$person->setUserName($this->userName);
		$person->setFirstName($firstName);
		$person->setLastName($lastName);
		$person->setTitle($postArr['title']);
		$person->setInstitution($postArr['institution']);
		$person->setCity($postArr['city']);
		$person->setState($postArr['state']);
		$person->setZip($postArr['zip']);
		$person->setCountry($postArr['country']);
		$person->setEmail($postArr['emailaddr']);
		$person->setUrl($postArr['url']);
		$person->setBiography($postArr['biography']);
		$person->setIsPublic(isset($postArr['ispublic'])?1:0);
		
		
		//Add to users table
		$fields = 'INSERT INTO users (';
		$values = 'VALUES (';
		$fields .= 'firstname ';
		$values .= '"'.$this->cleanInStr($person->getFirstName()).'"';
		$fields .= ', lastname';
		$values .= ', "'.$this->cleanInStr($person->getLastName()).'"';
		if($person->getTitle()){
			$fields .= ', title';
			$values .= ', "'.$this->cleanInStr($person->getTitle()).'"';
		}
		if($person->getInstitution()){
			$fields .= ', institution';
			$values .= ', "'.$this->cleanInStr($person->getInstitution()).'"';
		}
		if($person->getDepartment()){
			$fields .= ', department';
			$values .= ', "'.$this->cleanInStr($person->getDepartment()).'"';
		}
		if($person->getAddress()){
			$fields .= ', address';
			$values .= ', "'.$this->cleanInStr($person->getAddress()).'"';
		}
		if($person->getCity()){
			$fields .= ', city';
			$values .= ', "'.$this->cleanInStr($person->getCity()).'"';
		}
		$fields .= ', state';
		$values .= ', "'.$this->cleanInStr($person->getState()).'"';
		$fields .= ', country';
		$values .= ', "'.$this->cleanInStr($person->getCountry()).'"';
		if($person->getZip()){
			$fields .= ', zip';
			$values .= ', "'.$this->cleanInStr($person->getZip()).'"';
		}
		if($person->getPhone()){
			$fields .= ', phone';
			$values .= ', "'.$this->cleanInStr($person->getPhone()).'"';
		}
		if($person->getEmail()){
			$fields .= ', email';
			$values .= ', "'.$this->cleanInStr($person->getEmail()).'"';
		}
		if($person->getUrl()){
			$fields .= ', url';
			$values .= ', "'.$person->getUrl().'"';
		}
		if($person->getBiography()){
			$fields .= ', biography';
			$values .= ', "'.$this->cleanInStr($person->getBiography()).'"';
		}
		if($person->getIsPublic()){
			$fields .= ', ispublic';
			$values .= ', '.$person->getIsPublic();
		}
		
		$sql = $fields.') '.$values.')';
		//echo "SQL: ".$sql;
		$editCon = $this->getConnection('write');
		if($editCon->query($sql)){
			$person->setUid($editCon->insert_id);
			$this->uid = $person->getUid();
			//Add userlogin
			$sql = 'INSERT INTO userlogin (uid, username, password) '.
				'VALUES ('.$person->getUid().', "'.
				$this->cleanInStr($person->getUserName()).
				'", PASSWORD("'.$this->cleanInStr($person->getPassword()).'"))';
			if($editCon->query($sql)){
				$status = true;
				//authenicate
				$this->userName = $person->getUserName();
				$this->displayName = $person->getFirstName();
				$this->reset();
				$this->setCookies();
			}
			else{
				$this->errorStr = 'FAILED: Unable to create user.<div style="margin-left:55px;">Please contact system administrator for assistance.</div>';
			}
		}
		$editCon->close();
		
		return $status;
	}

	public function lookupUserName($emailAddr){
		global $charset;
		$status = false;
		if(!$this->validateEmailAddress($emailAddr)) return false;
		$loginStr = '';
		$sql = 'SELECT u.uid, ul.username, concat_ws("; ",u.lastname,u.firstname) '.
			'FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
			'WHERE (u.email = "'.$emailAddr.'")';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			if($loginStr) $loginStr .= '; ';
			$loginStr .= $row->username;
		}
		$result->free();
		if($loginStr){
			//Email login
			$subject = $GLOBALS['defaultTitle'].' Login Name';
			$bodyStr = 'Your '.$GLOBALS['defaultTitle'].' (<a href="http://'.$_SERVER['SERVER_NAME'].$GLOBALS['clientRoot'].'">http://'.
				$_SERVER['SERVER_NAME'].$GLOBALS['clientRoot'].'</a>) login name is: '.$loginStr.' ';
			$bodyStr .= "<br/>If you continue to have login issues, contact the System Administrator ";
			if(array_key_exists("adminEmail",$GLOBALS)){
				$bodyStr .= "<".$GLOBALS["adminEmail"].">";
			}
			$headerStr = "MIME-Version: 1.0 \r\n".
				"Content-type: text/html; charset=".$charset." \r\n".
				"To: ".$emailAddr." \r\n";
			if(array_key_exists("adminEmail",$GLOBALS)){
				$headerStr .= "From: Admin <".$GLOBALS["adminEmail"]."> \r\n";
			}
			if(mail($emailAddr,$subject,$bodyStr,$headerStr)){
				$status = true;
			}
			else{
				$this->errorStr = 'ERROR sending email, mailserver might not be properly setup';
			}
		}
		else{
			$this->errorStr = 'There are no users registered to email address: '.$emailAddr;
		}

		return $status;
	}
	
	public function changeLogin($newLogin, $pwd = ''){
		$status = true;
		if($this->uid){
			$isSelf = true;
			if($this->uid != $GLOBALS['SYMB_UID']) $isSelf = false;
			$newLogin = trim($newLogin);
			if(!$this->validateUserName($newLogin)) return false;
	
			//Test if login exists
			$sqlTestLogin = 'SELECT ul.uid FROM userlogin ul WHERE (ul.username = "'.$newLogin.'") ';
			$rs = $this->conn->query($sqlTestLogin);
			if($rs->num_rows){
				$this->errorStr = 'Login '.$newLogin.' is already being used by another user. Please try a new login.';
				$status = false;
			}
			$rs->free();

			if($status){
				$this->setUserName();
				if($isSelf){
					if(!$this->authenticate($pwd)){
						$this->errorStr = 'ERROR saving new login: incorrect password';
						$status = false;
					}
				}
				if($status){
					//Change login
					$sql = 'UPDATE userlogin '.
						'SET username = "'.$newLogin.'" '.
						'WHERE (uid = '.$this->uid.') AND (username = "'.$this->userName.'")';
					//echo $sql;
					$editCon = $this->getConnection('write');
					if($editCon->query($sql)){
						if($isSelf){
							$this->userName = $newLogin;
							$this->authenticate();
						}
					}
					else{
						$this->errorStr = 'ERROR saving new login: '.$editCon->error;
						$status = false;
					}
					$editCon->close();
				}
			}
		}
		return $status;
	}

	public function checkLogin($email){
		if(!$this->validateEmailAddress($email)) return false;
		//Check to see if userlogin already exists
		$status = true; 
	   	$sql = 'SELECT u.email, ul.username '.
			'FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
			'WHERE (ul.username = "'.$this->userName.'" OR u.email = "'.$email.'" )';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$status = false;
			if($row->username == $this->userName){
				$this->errorStr = 'login_exists';
				break;
			}
			else{
				$this->errorStr = 'email_registered';
			}
		}
		$result->free();
		return $status;
	}
	
	//Personal and general specimen management
	public function getPersonalCollectionArr(){
		global $USER_RIGHTS;
		$retArr = array();
		if($this->uid){
			$cAdminArr = array();
			if(array_key_exists('CollAdmin',$USER_RIGHTS)) $cAdminArr = $USER_RIGHTS['CollAdmin'];
			$cArr = $cAdminArr;
			if(array_key_exists('CollEditor',$USER_RIGHTS)) $cArr = array_merge($cArr,$USER_RIGHTS['CollEditor']);
			if($cArr){
				$sql = 'SELECT collid, collectionname, colltype, CONCAT_WS(" ",institutioncode,collectioncode) AS instcode '.
					'FROM omcollections WHERE collid IN('.implode(',',$cArr).') ORDER BY collectionname';
				//echo $sql;
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$retArr[$r->colltype][$r->collid] = $r->collectionname.($r->instcode?' ('.$r->instcode.')':'');
					}
					$rs->free();
				}
			}
		}
		return $retArr;
	}

	public function getPersonalOccurrenceCount($collId){
		$retCnt = 0;
		if($this->uid){
			$sql = 'SELECT count(*) AS reccnt FROM omoccurrences WHERE observeruid = '.$this->uid.' AND collid = '.$collId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retCnt = $r->reccnt;
				}
				$rs->close();
			}
		}
		return $retCnt;
	}

	//User Taxonomy functions
	private function setUserTaxonomy(&$person){
		$sql = 'SELECT ut.idusertaxonomy, t.tid, t.sciname, '.
			'ut.editorstatus, ut.geographicscope, ut.notes, ut.modifieduid, ut.modifiedtimestamp '.
			'FROM usertaxonomy ut INNER JOIN taxa t ON ut.tid = t.tid '.
			'WHERE ut.uid = ?';
		$statement = $this->conn->prepare($sql);
		$uid = $person->getUid();
		$statement->bind_param('i', $uid);
		$statement->execute();
		$statement->bind_result($id, $tid, $sciname, $editorStatus, $geographicScope, $notes, $modifiedUid, $modifiedtimestamp);
		while($statement->fetch()){
			$person->addUserTaxonomy($editorStatus, $id,'sciname',$sciname);
			$person->addUserTaxonomy($editorStatus, $id,'tid',$tid);
			$person->addUserTaxonomy($editorStatus, $id,'geographicScope',$geographicScope);
			$person->addUserTaxonomy($editorStatus, $id,'notes',$notes);
		}
		$statement->close();
	}
	
	public function deleteUserTaxonomy($utid,$editorStatus = ''){
		$statusStr = 'SUCCESS: Taxonomic relationship deleted';
		if(is_numeric($utid) || $utid == 'all'){
			$sql = 'DELETE FROM usertaxonomy ';
			if($utid == 'all'){
				$sql .= 'WHERE uid = '.$this->uid;
			}
			else{
				$sql .= 'WHERE idusertaxonomy = '.$utid;
			}
			if($editorStatus){
				$sql .= ' AND editorstatus = "'.$editorStatus.'" ';
			}
			$editCon = $this->getConnection("write");
			if($editCon->query($sql)){
				if($this->uid == $GLOBALS['SYMB_UID']){
					$this->userName = $GLOBALS['USERNAME'];
					$this->authenticate();
				}
			}
			else{
				$statusStr = 'ERROR deleting taxonomic relationship: '.$editCon->error;
			}
			$editCon->close();
		}
		return $statusStr;
	}

	public function addUserTaxonomy($taxon,$editorStatus,$geographicScope,$notes){
		$statusStr = 'SUCCESS adding taxonomic relationship';
		
		$tid = 0;
		$taxon = $this->cleanInStr($taxon);
		$editorStatus = $this->cleanInStr($editorStatus);
		$geographicScope = $this->cleanInStr($geographicScope);
		$notes = $this->cleanInStr($notes);
		$modDate = date('Y-m-d H:i:s');
		//Get tid for taxon
		$sql1 = 'SELECT tid FROM taxa WHERE sciname = "'.$taxon.'"';
		$rs1 = $this->conn->query($sql1);
		while($r1 = $rs1->fetch_object()){
			$tid = $r1->tid;
		}
		$rs1->close();
		if($tid){
			$sql2 = 'INSERT INTO usertaxonomy(uid, tid, taxauthid, editorstatus, geographicScope, notes, modifiedUid, modifiedtimestamp) '.
				'VALUES('.$this->uid.','.$tid.',1,"'.$editorStatus.'","'.$geographicScope.'","'.$notes.'",'.$GLOBALS['SYMB_UID'].',"'.$modDate.'") ';
			//echo $sql;
			$editCon = $this->getConnection("write");
			if($editCon->query($sql2)){
				if($this->uid == $GLOBALS['SYMB_UID']){
					$this->userName = $GLOBALS['USERNAME'];
					$this->authenticate();
				}
			}
			else{
				$statusStr = 'ERROR adding taxonomic relationship: '.$editCon->error;
			}
			$editCon->close();
		}
		else{
			$statusStr = 'ERROR adding taxonomic relationship: unable to obtain tid for '.$taxon;
		}
		return $statusStr;
	}

	/**
	 * 
	 * Obtain the list of specimens that have an identification verification status rank less than 6 
	 * within the list of taxa for which this user is listed as a specialist.
	 * 
	 */
	public function echoSpecimensPendingIdent($withImgOnly = 1){
		if($this->uid){
			$tidArr = array(); 
			$sqlt = 'SELECT t.tid, t.sciname '.
				'FROM usertaxonomy u INNER JOIN taxa t ON u.tid = t.tid '.
				'WHERE u.uid = '.$this->uid.' AND u.editorstatus = "OccurrenceEditor" '.
				'ORDER BY t.sciname ';
			$rst = $this->conn->query($sqlt);
			while($rt = $rst->fetch_object()){
				$tidArr[$rt->tid] = $rt->sciname;
			}
			$rst->free();
			if($tidArr){
				foreach($tidArr as $tid => $taxonName){
					echo '<div style="margin:10px;">';
					echo '<div><b><u>'.$taxonName.'</u></b></div>';
					echo '<ul style="margin:10px;">';
					$sql = 'SELECT DISTINCT o.occid, o.catalognumber, IFNULL(o.sciname,t.sciname) as sciname, o.stateprovince, '.
						'CONCAT_WS("-",IFNULL(o.institutioncode,c.institutioncode),IFNULL(o.collectioncode,c.collectioncode)) AS collcode '.
						'FROM omoccurrences o INNER JOIN omoccurverification v ON o.occid = v.occid '.
						'INNER JOIN omcollections c ON o.collid = c.collid '.
						'INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
						'INNER JOIN taxaenumtree e ON t.tid = e.tid ';
					if($withImgOnly) $sql .= 'INNER JOIN images i ON o.occid = i.occid ';
					$sql .= 'WHERE v.category = "identification" AND v.ranking < 6 AND e.taxauthid = 1 '.
						'AND (e.parenttid = '.$tid.' OR t.tid = '.$tid.') '.
						'ORDER BY o.sciname,t.sciname,o.catalognumber ';
					//echo '<div>'.$sql.'</div>';
					$rs = $this->conn->query($sql);
					if($rs->num_rows){
						while($r = $rs->fetch_object()){
							echo '<li><i>'.$r->sciname.'</i>, ';
							echo '<a href="../collections/editor/occurrenceeditor.php?occid='.$r->occid.'" target="_blank">';
							echo $r->catalognumber.'</a> ['.$r->collcode.']'.($r->stateprovince?', '.$r->stateprovince:'');
							echo '</li>'."\n";
						}
					}
					else{
						echo '<li>No deficiently identified specimens were found within this taxon</li>';
					}
					echo '</ul>';
					echo '</div>';
					$rs->free();
					ob_flush();
					flush();
				}
			}
		}
	}

	public function echoSpecimensLackingIdent($withImgOnly = 1){
		if($this->uid){
			echo '<div style="margin:10px;">';
			echo '<div><b><u>Lacking Identifications</u></b></div>';
			echo '<ul style="margin:10px;">';
			$sql = 'SELECT DISTINCT o.occid, o.catalognumber, o.stateprovince, '.
				'CONCAT_WS("-",IFNULL(o.institutioncode,c.institutioncode),IFNULL(o.collectioncode,c.collectioncode)) AS collcode '.
				'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
			if($withImgOnly) $sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			$sql .= 'WHERE (o.sciname IS NULL) '.
				'ORDER BY c.institutioncode, o.catalognumber LIMIT 2000';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			if($rs->num_rows){
				while($r = $rs->fetch_object()){
					echo '<li>';
					echo '<a href="../collections/editor/occurrenceeditor.php?occid='.$r->occid.'" target="_blank">';
					echo $r->catalognumber.'</a> ['.$r->collcode.']'.($r->stateprovince?', '.$r->stateprovince:'');
					echo '</li>'."\n";
				}
			}
			else{
				echo '<li>No un-identified specimens were found</li>';
			}
			echo '</ul>';
			echo '</div>';
			$rs->free();
			ob_flush();
			flush();
		}
	}

	//Functions to be replaced
	public function dlSpecBackup($collId, $characterSet, $zipFile = 1){
		global $charset, $paramsArr;

		$tempPath = $this->getTempPath();
    	$buFileName = $paramsArr['un'].'_'.time();
 		$zipArchive;
    	
    	if($zipFile && class_exists('ZipArchive')){
			$zipArchive = new ZipArchive;
			$zipArchive->open($tempPath.$buFileName.'.zip', ZipArchive::CREATE);
 		}
    	
    	$cSet = str_replace('-','',strtolower($charset));
		$fileUrl = '';
    	//If zip archive can be created, the occurrences, determinations, and image records will be added to single archive file
    	//If not, then a CSV file containing just occurrence records will be returned
		echo '<li style="font-weight:bold;">Zip Archive created</li>';
		echo '<li style="font-weight:bold;">Adding occurrence records to archive...';
		ob_flush();
		flush();
    	//Adding occurrence records
    	$fileName = $tempPath.$buFileName;
    	$specFH = fopen($fileName.'_spec.csv', "w");
    	//Output header 
    	//CA: Bookmark
		  $headerStr = 'occid,dbpk,basisOfRecord,otherCatalogNumbers,ownerInstitutionCode, '.
			'family,scientificName,sciname,tidinterpreted,genus,specificEpithet,taxonRank,infraspecificEpithet,scientificNameAuthorship, '.
			'taxonRemarks,identifiedBy,dateIdentified,identificationReferences,identificationRemarks,identificationQualifier, '.
			'typeStatus,recordedBy,recordNumber,associatedCollectors,eventDate,year,month,day,startDayOfYear,endDayOfYear, '.
			'verbatimEventDate,habitat,substrate,occurrenceRemarks,informationWithheld,associatedOccurrences, '.
			'dataGeneralizations,associatedTaxa,dynamicProperties,verbatimAttributes,reproductiveCondition, '.
			'cultivationStatus,establishmentMeans,lifeStage,sex,individualCount,country,stateProvince,county,municipality, '.
			'locality,localitySecurity,localitySecurityReason,decimalLatitude,decimalLongitude,geodeticDatum, '.
			'coordinateUncertaintyInMeters,verbatimCoordinates,georeferencedBy,georeferenceProtocol,georeferenceSources, '.
			'georeferenceVerificationStatus,georeferenceRemarks,minimumElevationInMeters,maximumElevationInMeters,verbatimElevation, '.
			'previousIdentifications,disposition,modified,language,processingstatus,recordEnteredBy,duplicateQuantity,dateLastModified,idCollaboratorIndigenous,sexCollaboratorIndigenous,dobCollaboratorIndigenous,verbatimIndigenous,validIndigenous,linkLanguageCollaboratorIndigenous,familyLanguageCollaboratorIndigenous,groupLanguageCollaboratorIndigenous,subgroupLanguageCollaboratorIndigenous,villageCollaboratorIndigenous,municipalityCollaboratorIndigenous,stateCollaboratorIndigenous,countryCollaboratorIndigenous,isoLanguageCollaboratorIndigenous,vernacularLexiconIndigenous,glossLexiconIndigenous,parseLexiconIndigenous,parentTaxaLexiconIndigenous,siblingTaxaLexiconIndigenous,childTaxaLexiconIndigenous,otherTaxaUseIndigenous,typologyLexiconIndigenous,semanticsLexiconIndigenous,notesLexiconIndigenous,categoryUseIndigenous,specificUseIndigenous,partUseIndigenous,notesUseIndigenous ';
		fputcsv($specFH, explode(',',$headerStr));
		//Query and output values
    	$sql = 'SELECT '.$headerStr.
    		' FROM omoccurrences '.
    		'WHERE collid = '.$collId.' AND observeruid = '.$this->uid;
    	if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_row()){
				if($characterSet && $characterSet != $cSet){
					$this->encodeArr($r,$characterSet);
				}
				fputcsv($specFH, $r);
			}
    		$rs->close();
    	}
    	fclose($specFH);
		if($zipFile && $zipArchive){
    		//Add occurrence file and then rename to 
			$zipArchive->addFile($fileName.'_spec.csv');
			$zipArchive->renameName($fileName.'_spec.csv','occurrences.csv');

			//Add determinations
			/*
			echo 'Done!</li> ';
			echo '<li style="font-weight:bold;">Adding determinations records to archive...';
			ob_flush();
			flush();
			$detFH = fopen($fileName.'_det.csv', "w");
			fputcsv($detFH, Array('detid','occid','sciname','scientificNameAuthorship','identifiedBy','d.dateIdentified','identificationQualifier','identificationReferences','identificationRemarks','sortsequence'));
			//Add determination values
			$sql = 'SELECT d.detid,d.occid,d.sciname,d.scientificNameAuthorship,d.identifiedBy,d.dateIdentified, '.
				'd.identificationQualifier,d.identificationReferences,d.identificationRemarks,d.sortsequence '.
				'FROM omdeterminations d INNER JOIN omoccurrences o ON d.occid = o.occid '.
				'WHERE o.collid = '.$this->collId.' AND o.observeruid = '.$this->uid;
    		if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_row()){
					fputcsv($detFH, $r);
				}
    			$rs->close();
    		}
    		fclose($detFH);
			$zipArchive->addFile($fileName.'_det.csv');
    		$zipArchive->renameName($fileName.'_det.csv','determinations.csv');
			*/
    		
			echo 'Done!</li> ';
			ob_flush();
			flush();
			$fileUrl = str_replace($GLOBALS['serverRoot'],$GLOBALS['clientRoot'],$tempPath.$buFileName.'.zip');
			$zipArchive->close();
			unlink($fileName.'_spec.csv');
			//unlink($fileName.'_det.csv');
		}
		else{
			$fileUrl = str_replace($GLOBALS['serverRoot'],$GLOBALS['clientRoot'],$tempPath.$buFileName.'_spec.csv');
    	}
		return $fileUrl;
	}

	//Setters and getters
	public function setUid($uid){
		if(is_numeric($uid)){
			$this->uid = $uid;
		}
	}

	private function setUserRights(){
		//Get Admin Rights 
		if($this->uid){
			$sql = 'SELECT role, tablepk FROM userroles WHERE (uid = '.$this->uid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->userRights[$r->role][] = $r->tablepk;
			}
			$rs->free();
		}
	}
	
	public function getUserRights(){
		return $this->userRights;
	}
	public function setRememberMe($test){
		$this->rememberMe = $test;
	}

	public function getRememberMe(){
		return $this->rememberMe;
	}

	public function setUserName($un = ''){
		if($un){
			if(!$this->validateUserName($un)) return false;
			$this->userName = $un;
		}
		else{
			if($this->uid == $GLOBALS['SYMB_UID']){
				$this->userName = $GLOBALS['USERNAME'];
			}
			elseif($this->uid){
				$sql = 'SELECT username FROM userlogin WHERE (uid = '.$this->uid.') ';
				//echo $sql;
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$this->userName = $r->username;
				}
				$rs->free();
			}
		}
		return true;
	}

	private function getTempPath(){
		$tPath = $GLOBALS["serverRoot"];
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\') $tPath .= '/';
		$tPath .= "temp/";
		if(file_exists($tPath."downloads/")){
			$tPath .= "downloads/";
		}
		return $tPath;
	}
	
	public function getErrorStr(){
		return $this->errorStr;
	}

	//Other misc functions
	public function validateEmailAddress($emailAddress){
		if(!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)){
			$this->errorStr = 'email_invalid';
			return false;
		}
		return true;
	}
	
	private function validateUserName($un){
		$status = true;
		if (preg_match('/^[0-9A-Za-z_!@#$\s\.+\-]+$/', $un) == 0) $status = false;
		if (substr($un,0,1) == ' ') $status = false;
		if (substr($un,-1) == ' ') $status = false;
		if(!$status) $this->errorStr = 'username not valid';
		return $status;
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
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function encodeArr(&$inArr,$cSet){
		foreach($inArr as $k => $v){
			$inArr[$k] = $this->encodeString($v,$cSet);
		}
	}

	private function encodeString($inStr,$cSet){
 		$retStr = $inStr;
		if($cSet == "utf8"){
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
				//$value = utf8_encode($value);
				$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
			}
		}
		elseif($cSet == "latin1"){
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
				//$value = utf8_decode($value);
				$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
			}
		}
		return $retStr;
	}
} 
?>