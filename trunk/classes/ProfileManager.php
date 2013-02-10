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
    private $con;
    
    public function __construct(){
    	$this->userRights = Array();
		$this->con = MySQLiConnectionFactory::getCon("readonly");
    }
    
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	private function getConnection($type){
		return MySQLiConnectionFactory::getCon($type);
	}
    
    public function reset(){
		//Delete cookies
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
        setcookie("SymbiotaBase", $cookieStr, $cookieExpire, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'));
        //Set admin cookie
        if($this->userRights){
        	setcookie("SymbiotaRights", implode("&",$this->userRights), $cookieExpire, ($GLOBALS["clientRoot"]?$GLOBALS["clientRoot"]:'/'));
    	}
    }
    
    public function authenticate($userNameStr, $pwdStr = ''){
        $authStatus = false;
        $this->userName = $userNameStr;
		//check login
        $sql = 'SELECT u.uid, u.firstname, u.lastname '.
			'FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
            'WHERE (ul.username = "'.$this->con->real_escape_string($userNameStr).'") ';
        if($pwdStr){
			$sql .= 'AND ((ul.password = PASSWORD("'.$this->con->real_escape_string($pwdStr).'")) '.
				'OR (ul.password = OLD_PASSWORD("'.$this->con->real_escape_string($pwdStr).'")))';
        }
		//echo $sql;
        $result = $this->con->query($sql);
        if($row = $result->fetch_object()){
        	$this->uid = $row->uid;
        	$this->displayName = $row->firstname." ".$row->lastname;
            $authStatus = true;
        }
		
        if($authStatus){
			//Get Admin Rights 
	        $sql = 'SELECT up.pname FROM userpermissions up WHERE (up.uid = '.$this->uid.')';
	        //echo $sql;
	        $result = $this->con->query($sql);
    	    while($row = $result->fetch_object()){
	    	    $this->userRights[] = $row->pname;
	        }
            
	        $this->setCookies();
            
	        //Upadate last login data
	        $conn = $this->getConnection("write");
	        $sql = 'UPDATE userlogin SET lastlogindate = NOW() WHERE (username = "'.$this->con->real_escape_string($userNameStr).'")';
	        $conn->query($sql); 
	        
        	return "success";
        }
        else{
                //Check and see why authentication failed
        	$sqlStr = 'SELECT u.uid, u.firstname, u.lastname, u.email, ul.password '.
            	'FROM userlogin ul INNER JOIN users u ON ul.uid = u.uid '.
				'WHERE (ul.username = "'.$this->con->real_escape_string($userNameStr).'")';
            //echo $sqlStr;
	        $result = $this->con->query($sqlStr);
			if($row = $result->fetch_object()){
                    return 'badPassword';
	        }
                return 'badUserId';
        }
    }
    
    public function getPersonByUid($userId){
    	if(is_numeric($userId)){
	        $sqlStr = "SELECT u.uid, u.firstname, u.lastname, u.title, u.institution, u.department, ".
	        	"u.address, u.city, u.state, u.zip, u.country, u.phone, u.email, ".
	        	"u.url, u.biography, u.ispublic, u.notes, ul.username ".
	            "FROM users u LEFT JOIN userlogin ul ON u.uid = ul.uid ".
	            "WHERE (u.uid = ".$userId.")";
	        return $this->getPersonBySql($sqlStr);
    	}
    	return;
    }
        
    public function getPerson ($userName){
        $sqlStr = 'SELECT u.uid, u.firstname, u.lastname, u.title, u.institution, u.department, '.
        	'u.address, u.city, u.state, u.zip, u.country, u.phone, u.email, '.
        	'u.url, u.biography, u.ispublic, u.notes, ul.username '.
            'FROM userlogin ul INNER JOIN users u ON ul.uid = u.uid '.
            'WHERE (ul.username = "'.$this->con->real_escape_string($userName).'")';
        return $this->getPersonBySql($sqlStr);
    }
        
    private function getPersonBySql($sqlStr){
		$person = null;
		//echo $sqlStr;
    	$result = $this->con->query($sqlStr);
        if($row = $result->fetch_object()){
	        $person = new Person();
            $person->setUid($row->uid);
            $person->setFirstName($this->cleanOutStr($row->firstname));
            $person->setLastName($this->cleanOutStr($row->lastname));
            $person->setTitle($this->cleanOutStr($row->title));
            $person->setInstitution($this->cleanOutStr($row->institution));
            $person->setDepartment($this->cleanOutStr($row->department));
            $person->setAddress($this->cleanOutStr($row->address));
            $person->setCity($this->cleanOutStr($row->city));
            $person->setState($this->cleanOutStr($row->state));
            $person->setZip($row->zip);
            $person->setCountry($this->cleanOutStr($row->country));
            $person->setPhone($row->phone);
            $person->setEmail($this->cleanOutStr($row->email));
            $person->setUrl($row->url);
            $person->setBiography($this->cleanOutStr($row->biography));
            $person->setIsPublic($row->ispublic);
			$person->addLogin($row->username);
            while($row = $result->fetch_object()){
            	$person->addLogin($row->username);
            }
        }
        $result->close();
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

    public function deleteProfile($uid, $reset = 0){
        $success = false;
        if($uid){
        	$editCon = $this->getConnection("write");
        	$sql = "DELETE FROM users WHERE (uid = ".$this->con->real_escape_string($uid).')';
			$success = $editCon->query($sql);
        	$editCon->close();
        }
        if($reset) $this->reset();
        return $success;
    }
    
    public function changePassword ($id, $newPwd, $oldPwd = "", $isSelf = 0) {
        $success = false;
    	if($newPwd){
        	$editCon = $this->getConnection("write");
        	if($isSelf){
	        	$sqlTest = 'SELECT ul.uid FROM userlogin ul WHERE (ul.username = "'.$this->con->real_escape_string($id).
	        		'") AND ((ul.password = PASSWORD("'.$this->con->real_escape_string($oldPwd).
	        		'")) OR (ul.password = OLD_PASSWORD("'.$this->con->real_escape_string($oldPwd).'")))';
	        	$rsTest = $editCon->query($sqlTest);
	        	if(!$rsTest->num_rows) return false;
        	}
    		$sql = 'UPDATE userlogin ul SET ul.password = PASSWORD("'.$this->con->real_escape_string($newPwd).'") '; 
    		if($isSelf){
    			$sql .= 'WHERE (ul.username = "'.$this->con->real_escape_string($id).'")';
    		}
    		else{
    			$sql .= 'WHERE (uid = '.$this->con->real_escape_string($id).')';
    		}
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
			$sql = 'UPDATE userlogin ul SET ul.password = PASSWORD("'.$this->con->real_escape_string($newPassword).'") '. 
                    'WHERE (ul.username = "'.$this->con->real_escape_string($un).'")';
			$status = $editCon->query($sql);
        	$editCon->close();
        }
		if($status){
			//Get email address
			$emailStr = ""; 
        	$sql = 'SELECT u.email FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
        		'WHERE (ul.username = "'.$this->con->real_escape_string($un).'")';
			$result = $this->con->query($sql);
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
        for($i = 0; $i<5; $i++) {
            $newPassword .= $alphabet[rand(0,count($alphabet)-1)];
        }
        return $newPassword;
    }
    
    public function register($person){
        $returnStr = "";
        $userNew = true;

        //Test to see if user already exists
        if($person->getEmail()){
        	$sql = 'SELECT u.uid FROM users u WHERE (u.email = "'.$person->getEmail().'") AND (u.lastname = "'.$person->getLastName().'")';
			$result = $this->con->query($sql);
			if($row = $result->fetch_object()){
				$person->setUid($row->uid);
				//$returnStr = "Note: Using profile already in system that matched submitted name and email address. ";
                $userNew = false;
            }
            $result->free();
        }
        
        //If newuser, add to users table
        if($userNew){
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
            if($person->getState()){
				$fields .= ', state';
                $values .= ', "'.$this->cleanInStr($person->getState()).'"';
            }
            if($person->getZip()){
            	$fields .= ', zip';
				$values .= ', "'.$this->cleanInStr($person->getZip()).'"';
            }
            if($person->getCountry()){
            	$fields .= ', country';
                $values .= ', "'.$this->cleanInStr($person->getCountry()).'"';
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
            //echo "SQL: ".sql;
        	$editCon = $this->getConnection('write');
			if($editCon->query($sql)){
				$person->setUid($editCon->insert_id);
            }
            $editCon->close();
        }
        
        //Add userlogin
        $sql = 'INSERT INTO userlogin (uid, username, password) '.
			'VALUES ('.$this->con->real_escape_string($person->getUid()).', "'.
        	$this->con->real_escape_string($person->getUserName()).
        	'", PASSWORD("'.$this->con->real_escape_string($person->getPassword()).'"))';
        $editCon = $this->getConnection('write');
        $insertStatus = $editCon->query($sql);
        $editCon->close();
        if($insertStatus > 0){
        	$returnStr = 'SUCCESS: new user added successfully. '.$returnStr;
        }
        else{
        	$returnStr = 'FAILED: Unable to create user.<div style="margin-left:55px;">Please contact system administrator for assistance.</div>';
        }
        return $returnStr;
    }
    
    public function lookupLogin($emailAddr){
    	global $charset;
    	$login = '';
    	$sql = 'SELECT u.uid, ul.username '.
			'FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
			'WHERE (u.email = "'.$emailAddr.'")';
    	$result = $this->con->query($sql);
    	if($row = $result->fetch_object()){
    		$login = $row->username;
    	}
    	$result->free();

    	if($login){
			$subject = $GLOBALS['defaultTitle'].' Login Name';
			$bodyStr = 'Your '.$GLOBALS['defaultTitle'].' (<a href="http://'.$_SERVER['SERVER_NAME'].$GLOBALS['clientRoot'].'">http://'.
				$_SERVER['SERVER_NAME'].$GLOBALS['clientRoot'].'</a>) login name is: '.$login.' ';
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
			mail($emailAddr,$subject,$bodyStr,$headerStr);
			return 1;
    	}    	
		return 0;
    }
    
    public function createNewLogin($userId, $newLogin, $newPwd){
    	$statusStr = '<span color="red">Creation of New Login failed!</span>';
    	$newLogin = trim($newLogin);
    	
    	//Test if login exists
    	$sqlTestLogin = 'SELECT ul.uid FROM userlogin ul WHERE (ul.username = "'.$this->con->real_escape_string($newLogin).'") ';
    	$rs = $this->con->query($sqlTestLogin);
    	$numRows = $rs->num_rows;
    	$rs->close();
    	if($numRows) return "<span color='red'>FAILED! Login $newLogin is already being used by another user. Please try a new login.</span>";
    	
    	//Create new login
    	$sql = 'INSERT INTO userlogin (uid, username, password) '.
    		'VALUES ('.$this->con->real_escape_string($userId).',"'.
    		$this->con->real_escape_string($newLogin).
    		'",PASSWORD("'.$this->con->real_escape_string($newPwd).'"))';
    	//echo $sql;
        $editCon = $this->getConnection('write');
    	if($editCon->query($sql)) $statusStr = '<span color="green">Creation of New Login successful!</span>';
    	$editCon->close();
    	return $statusStr;
    }
    
    public function checkLogin($username, $email){
        //Check to see if userlogin already exists 
        $returnStr = '';
       	$sql = 'SELECT u.uid, u.email '.
			'FROM users u INNER JOIN userlogin ul ON u.uid = ul.uid '.
			'WHERE (ul.username = "'.$this->con->real_escape_string($username).'")';
		$result = $this->con->query($sql);
        if($row = $result->fetch_object()){
            $loginEmail = $row->email;
            if($loginEmail == $email){
                $returnStr = "FAILED: Login already associated with this email address.<br/> ".
                	"Click <a href='index.php?resetpwd=1&username=".$username."'>here</a> to reset password for this username.<br/>".
                	"Or change username below and resubmit form.";
            }
            else{
        		$returnStr = "FAILED: username <b>".$username."</b> login is already being used.<br> ".
        		"Please choose a different username and resubmit form.";
            }
        }
        $result->free();
        return $returnStr;
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
    
	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->con->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->con->real_escape_string($newStr);
		return $newStr;
	}
} 
?>