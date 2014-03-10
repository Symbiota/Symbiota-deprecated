<?php 

/**
 *  Base class for managers.  Supplies $conn for connection, $id for primary key, and 
 *  $errormessage/getErrorMessage(), along with supporting clean methods cleanOutStr()
 *  cleanInStr() and cleanInArray();
 */
class Manager  {
	protected $conn = null;
	protected $id = null;
    protected $errormessage = '';

	public function __construct($id=null,$conType='readonly'){
 		$this->conn = MySQLiConnectionFactory::getCon($conType);
 		if($id !=null || is_numeric($id)){
	 		$this->id = $id;
 		}
	}

 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
 	

 	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

 	protected function cleanInArray($arr){
 		$newArray = Array();
 		foreach($arr as $key => $value){
 			$newArray[$this->cleanInStr($key)] = $this->cleanInStr($value);
 		}
 		return $newArray;
 	}

   public function getErrorMessage() { 
      return $this->errormessage;
   }
 
   /** To enable mysqli_stmt->bind_param using call_user_func_array($array) 
     * allow $array to be converted to array of by references 
     * if php version requires it. 
     */
   public static function correctReferences($array) { 
    if (strnatcmp(phpversion(),'5.3') >= 0) {
       $byrefs = array();
       foreach($array as $key => $value)
          $byrefs[$key] = &$array[$key];
       return $byrefs;
    }
    return $arr;
   }

}

?>