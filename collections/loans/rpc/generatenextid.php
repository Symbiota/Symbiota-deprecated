<?php
include_once('../../../config/dbconnection.php');
	
$collId = $_REQUEST['collid'];
$idType = array_key_exists('idtype',$_REQUEST)?$_REQUEST['idtype']:'out';		//in, out, ex 

$retMsg = '';
if($collId && is_numeric($collId)){
	$sql = '';
	if($idType == 'out'){
		$sqlOut = 'SELECT loanidentifierown AS ids '.
			'FROM omoccurloans '.
			'WHERE collidown = '.$collId.' '.
			'ORDER BY loanid desc LIMIT 3';
	}
	elseif($idType == 'in'){
		$sqlOut = 'SELECT loanidentifierborr AS ids '.
			'FROM omoccurloans '.
			'WHERE collidborr = '.$collId.' '.
			'ORDER BY loanid desc LIMIT 3';
	}
	elseif($idType == 'ex'){
		$sqlOut = 'SELECT identifier AS ids '.
			'FROM omoccurexchange '.
			'WHERE collid = '.$collId.' '.
			'ORDER BY exchangeid desc LIMIT 3';
	}
	else{
		return '';
	}

	$conn = MySQLiConnectionFactory::getCon("readonly");
	if($rs = $conn->query($sqlOut)){
		$parsedArr = array();
		while($r = $rs->fetch_object()){
			$numArr = explode('-',preg_replace('/--+/','-',preg_replace('/\D/', '-', $r->ids)));
			$cnt = 0;
			foreach($numArr as $n){
				$parsedArr[$cnt][] = $n;
				$cnt++;
			}
		}
		$rs->close();
		if($parsedArr){
			$firstCnt = count($parsedArr[0]); 
			foreach($parsedArr as $k => $vArr){
				if($firstCnt <= count($vArr)){
					$retMsg = $vArr[0]+1;
					for($i=1;$i<$firstCnt;$i++){
						if(($vArr[$i]+1)<>$vArr[$i-1]){
							$retMsg = '';
							break;
						}
					}
					if($retMsg) break;
				}
			}
		}
		else{
			$retMsg = 1;
		}
	}
}
echo $retMsg;
?>