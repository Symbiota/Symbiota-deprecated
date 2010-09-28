<?php
/*
 * Created on 26 Feb 2009
 * By E.E. Gilbert
 */

class Person{
    
    private $uid;
    private $userName;
    private $firstName;
    private $lastName;
    private $title;
    private $institution;
    private $department;
    private $address;
    private $city;
    private $state;
    private $zip;
    private $country;
    private $phone;
    private $email;
    private $url;
    private $biography;
    private $isPublic = 0;
    private $userDirectoryPath;
    private $password;
    private $loginArr = Array();
    //private ArrayList groupList;
    
    public  function __construct(){
    }
    
    public function getUid(){
        return $this->uid;
    } 
    
    public function setUid($id){
        $this->uid = $id;
    } 
    
    public function getUserName(){
        return $this->userName;
    }
    
    public function setUserName($idName){
        if($idName) $this->userName = trim($idName);
    }
    
    public function getFirstName(){
        return $this->firstName;
    }
    
    public function setFirstName($firstName){
        if($firstName) $this->firstName = trim($firstName);
    }
    
    public function getLastName(){
        return $this->lastName;
    }
    
    public function setLastName($lastName){
        if($lastName) $this->lastName = trim($lastName);
    }
    
    public function getDepartment(){
        return $this->department;
    }
    
    public function setDepartment($department){
        if($department) $this->department = trim($department);
    }
    
    public function getTitle(){
        return $this->title;
    }
    
    public function setTitle($title){
        if($title) $this->title = trim($title);
    }
    
    public function getInstitution(){
        return $this->institution;
    }
    
    public function setInstitution($institution){
        if($institution) $this->institution = trim($institution);
    }
    
    public function getAddress(){
        return $this->address;
    }
    
    public function setAddress($address){
        if($address) $this->address = trim($address);
    }
    
    public function getCity(){
        return $this->city;
    }
    
    public function setCity($city){
        if($city) $this->city = trim($city);
    }

    public function getState(){
        return $this->state;
    }
    
    public function setState($state){
        if($state) $this->state = trim($state);
    }
    
    public function getCountry(){
        return $this->country;
    }
    
    public function setCountry($country){
        if($country) $this->country = trim($country);
    }
    
    public function getZip(){
        return $this->zip;
    }
    
    public function setZip($zip){
        if($zip) $this->zip = trim($zip);
    }
    
    public function getPhone (){
        return $this->phone;
    }
    
    public function setPhone($phone){
        if($phone) $this->phone = trim($phone);
    }
    
    public function getUrl(){
        return $this->url;
    }
    
    public function setUrl($url){
        if($url) $this->url = trim($url);
    }
    
    public function getBiography(){
        return $this->biography;
    }
    
    public function setBiography($bio){
        if($bio) $this->biography = trim($bio);
    }

	public function getIsPublic(){
        if($this->isPublic == 1){
        	return 1;
        }
        else{
        	return 0;
        }
    }
    
    public function setIsPublic($isPub){
        $this->isPublic = $isPub;
    }
    
    public function getEmail(){
        return $this->email;
    }
    
    public function setEmail($email){
        if($email) $this->email = trim($email);
    }
    
    public function getPassword(){
        return $this->password;
    }
    
    public function setPassword($password){
        $this->password = $password;
    }
    
    public function getUserDirectoryPath(){
        return $this->userDirectoryPath;
    }
    
    public function setUserDirectoryPath($path){
        $this->userDirectoryPath = $path;
    }
    
    public function addLogin($loginStr){
    	if(trim($loginStr)) $this->loginArr[] = $loginStr;
    }
    
    public function setLoginArr($arr){
        $this->loginArr = $arr;
    }

    public function getLoginArr(){
    	return $this->loginArr;
    } 
    
    public function getLoginStr(){
    	return implode("; ",$this->loginArr);
    }
}
?>