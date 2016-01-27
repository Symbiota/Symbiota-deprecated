<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/GPoint.php');

class OccurrenceUtilities {

	private $conn;
	private $verbose = false;	// 0 = silent, 1 = echo as list item
	private $errorArr = array();
	
	static $monthRoman = array('I'=>'01','II'=>'02','III'=>'03','IV'=>'04','V'=>'05','VI'=>'06','VII'=>'07','VIII'=>'08','IX'=>'09','X'=>'10','XI'=>'11','XII'=>'12');
	static $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
 	}
	
	/*
	 * INPUT: String representing a verbatim date 
	 * OUTPUT: String representing the date in MySQL format (YYYY-MM-DD)
	 *         Time is appended to end if present 
	 * 
	 */
	public static function formatDate($inStr){
		$retDate = '';
		$dateStr = trim($inStr);
		if(!$dateStr) return;
		$t = '';
		$y = '';
		$m = '00';
		$d = '00';
		//Remove time portion if it exists
		if(preg_match('/\d{2}:\d{2}:\d{2}/',$dateStr,$match)){
			$t = $match[0];
		}
		//Parse
		if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/',$dateStr,$match)){
			//Format: yyyy-m-d or yyyy-mm-dd
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		}
		elseif(preg_match('/^(\d{4})-(\d{1,2})/',$dateStr,$match)){
			//Format: yyyy-m or yyyy-mm
			$y = $match[1];
			$m = $match[2];
		}
		elseif(preg_match('/^(\d{1,2}).{1}([IVX]{1,4}).{1}(\d{2,4})/',$dateStr,$match)){
			//Roman numerial format: dd.IV.yyyy, dd.IV.yy, dd-IV-yyyy, dd-IV-yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			if(array_key_exists($mStr,OccurrenceUtilities::$monthRoman)){
				$m = OccurrenceUtilities::$monthRoman[$mStr];
			}
		}
		elseif(preg_match('/^(\d{1,2})[\s\/-]{1}(\D{3,})\.*[\s\/-]{1}(\d{2,4})/',$dateStr,$match)){
			//Format: dd mmm yyyy, d mmm yy, dd-mmm-yyyy, dd-mmm-yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			if(array_key_exists($mStr,OccurrenceUtilities::$monthNames)){
				$m = OccurrenceUtilities::$monthNames[$mStr];
			}
		}
		elseif(preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$dateStr,$match)){
			//Format: mm/dd/yyyy, m/d/yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s{0,1}(\d{1,2})[,\s]+([1,2]{1}[0,5-9]{1}\d{2})$/',$dateStr,$match)){
			//Format: mmm dd, yyyy
			$mStr = $match[1];
			$d = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			if(array_key_exists($mStr,OccurrenceUtilities::$monthNames)) $m = OccurrenceUtilities::$monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/',$dateStr,$match)){
			//Format: mm-dd-yyyy, mm-dd-yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s+([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: mmm yyyy
			$mStr = strtolower(substr($match[1],0,3));
			if(array_key_exists($mStr,OccurrenceUtilities::$monthNames)){
				$m = OccurrenceUtilities::$monthNames[$mStr];
			}
			else{
				$m = '00';
			}
			$y = $match[2];
		}
		elseif(preg_match('/([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: yyyy
			$y = $match[1];
		}
		//Clean, configure, return
		if($y){
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			//Check to see if month is valid
			if($m > 12){
				$m = '00';
				$d = '00';
			}
			//check to see if day is valid for month
			if($d > 31){
				//Bad day for any month
				$d = '00';
			}
			elseif($d == 30 && $m == 2){
				//Bad day for feb
				$d = '00';
			}
			elseif($d == 31 && ($m == 4 || $m == 6 || $m == 9 || $m == 11)){
				//Bad date, month w/o 31 days
				$d = '00';
			}
			//Do some cleaning
			if(strlen($y) == 2){ 
				if($y < 20) $y = '20'.$y;
				else $y = '19'.$y;
			}
			//Build
			$retDate = $y.'-'.$m.'-'.$d;
		}
		elseif(($timestamp = strtotime($retDate)) !== false){
			$retDate = date('Y-m-d', $timestamp);
		}
		if($t){
			$retDate .= ' '.$t;
		}
		return $retDate;
	}

	/*
	 * INPUT: String representing a verbatim scientific name 
	 *        Name may have imbedded authors, cf, aff, hybrid  
	 * OUTPUT: Array containing parsed values 
	 *         Keys: unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, author, identificationqualifier 
	 */
	public static function parseScientificName($inStr, $rankId = 0){
		//Converts scinetific name with author embedded into separate fields
		$retArr = array('unitname1'=>'','unitname2'=>'','unitind3'=>'','unitname3'=>'');
		if($inStr && is_string($inStr)){
			//Remove underscores, common in NPS data
			$inStr = preg_replace('/_+/',' ',$inStr);
			//Replace misc 
			$inStr = str_replace(array('?','*'),'',$inStr);
			
			if(stripos($inStr,'cf. ') !== false || stripos($inStr,'c.f. ') !== false || stripos($inStr,' cf ') !== false){
				$retArr['identificationqualifier'] = 'cf. ';
				$inStr = str_ireplace(array(' cf ','c.f. ','cf. '),' ',$inStr);
			}
			elseif(stripos($inStr,'aff. ') !== false || stripos($inStr,' aff ') !== false){
				$retArr['identificationqualifier'] = 'aff. ';
				$inStr = str_ireplace(array(' aff ','aff. '),' ',$inStr);
			}
			if(stripos($inStr,' spp.')){
				$rankId = 180;
				$inStr = str_ireplace(' spp.','',$inStr);
			}
			if(stripos($inStr,' sp.')){
				$rankId = 180;
				$inStr = str_ireplace(' sp.','',$inStr);
			}
			//Remove extra spaces
			$inStr = preg_replace('/\s\s+/',' ',$inStr);
			
			$sciNameArr = explode(' ',$inStr);
			if(count($sciNameArr)){
				if(strtolower($sciNameArr[0]) == 'x'){
					//Genus level hybrid
					$retArr['unitind1'] = array_shift($sciNameArr);
				}
				//Genus
				$retArr['unitname1'] = ucfirst(strtolower(array_shift($sciNameArr)));
				if(count($sciNameArr)){
					if(strtolower($sciNameArr[0]) == 'x'){
						//Species level hybrid
						$retArr['unitind2'] = array_shift($sciNameArr);
						$retArr['unitname2'] = array_shift($sciNameArr);
					}
					elseif(strpos($sciNameArr[0],'.') !== false){
						//It is assumed that Author has been reached, thus stop process 
						$retArr['author'] = implode(' ',$sciNameArr);
						unset($sciNameArr);
					}
					else{
						if(strpos($sciNameArr[0],'(') !== false){
							//Assumed subgenus exists, but keep a author incase an epithet does exist
							$retArr['author'] = implode(' ',$sciNameArr);
							array_shift($sciNameArr); 
						}
						//Specific Epithet
						$retArr['unitname2'] = array_shift($sciNameArr);
					}
					if($retArr['unitname2'] && preg_match('/[A-Z]+/',$retArr['unitname2'])){
						if(preg_match('/[A-Z]{1}[a-z]+/',$retArr['unitname2'])){
							//Check to see if is term is genus author
							$sql = 'SELECT tid FROM taxa WHERE unitname1 = "'.$retArr['unitname1'].'" AND unitname2 = "'.$retArr['unitname2'].'"';
							$con = MySQLiConnectionFactory::getCon('readonly');
							$rs = $con->query($sql);
							if($rs->num_rows){
								if(isset($retArr['author'])) unset($retArr['author']);
							}
							else{
								//Second word is likely author, thus assume assume author has been reach and stop process
								$retArr['unitname2'] = '';
								unset($sciNameArr);
							}
							$rs->free();
							$con->close();
						}
						$retArr['unitname2'] = strtolower($retArr['unitname2']);
					}
				}
			}
			if(isset($sciNameArr) && $sciNameArr){
				//Assume rest is author; if that is not true, author value will be replace in following loop
				$retArr['author'] = implode(' ',$sciNameArr);
				if(!$rankId || $rankId > 220){
					//cycles through the final terms to extract the last infraspecific data
					while($sciStr = array_shift($sciNameArr)){
						if($sciStr == 'f.' || $sciStr == 'fo.' || $sciStr == 'fo' || $sciStr == 'forma'){
							if($sciNameArr){
								$retArr['unitind3'] = 'f.';
								$nextStr = array_shift($sciNameArr);
								if($nextStr == 'var.' || $nextStr == 'ssp.' || $nextStr == 'subsp.'){
									$retArr['unitind3'] = $nextStr;
									$retArr['unitname3'] = array_shift($sciNameArr);
									$retArr['author'] = implode(' ',$sciNameArr);
								}
								elseif(preg_match('/^[a-z]+$/',$nextStr)){
									$retArr['unitname3'] = $nextStr;
									$retArr['author'] = implode(' ',$sciNameArr);
								}
								else{
									$retArr['unitind3'] = '';
								}
							}
						}
						elseif($sciStr == 'var.' || $sciStr == 'var'){
							if($sciNameArr){
								$retArr['unitind3'] = 'var.';
								$nextStr = array_shift($sciNameArr);
								if($nextStr == 'ssp.' || $nextStr == 'subsp.'){
									$retArr['unitind3'] = $nextStr;
									$retArr['unitname3'] = array_shift($sciNameArr);
									$retArr['author'] = implode(' ',$sciNameArr);
								}
								elseif(preg_match('/^[a-z]+$/',$nextStr)){
									$retArr['unitname3'] = $nextStr;
									$retArr['author'] = implode(' ',$sciNameArr);
								}
								else{
									$retArr['unitind3'] = '';
								}
							}
						}
						elseif($sciStr == 'ssp.' || $sciStr == 'ssp' || $sciStr == 'subsp.' || $sciStr == 'subsp'){
							if($sciNameArr){
								$retArr['unitind3'] = 'subsp.';
								$nextStr = array_shift($sciNameArr);
								if(preg_match('/^[a-z]+$/',$nextStr)){
									$retArr['unitname3'] = $nextStr;
									$retArr['author'] = implode(' ',$sciNameArr);
								}
								else{
									$retArr['unitind3'] = '';
								}
							}
						}
					}
					//Double check to see if infraSpecificEpithet is still embedded in author due initial lack of taxonRank indicator
					if(!$retArr['unitname3'] && $retArr['author']){
						$arr = explode(' ',$retArr['author']);
						$firstWord = array_shift($arr);
						if(preg_match('/^[a-z]{2,}$/',$firstWord)){
							$sql = 'SELECT unitind3 FROM taxa '.
								'WHERE unitname1 = "'.$retArr['unitname1'].'" AND unitname2 = "'.$retArr['unitname2'].'" AND unitname3 = "'.$firstWord.'" ';
							//echo $sql.'<br/>';
							$con = MySQLiConnectionFactory::getCon('readonly');
							$rs = $con->query($sql);
							if($r = $rs->fetch_object()){
								$retArr['unitind3'] = $r->unitind3;
								$retArr['unitname3'] = $firstWord;
								$retArr['author'] = implode(' ',$arr);
							}
							$rs->free();
							$con->close();
						}
					}
				}
			}
			if(array_key_exists('unitind1',$retArr)){
				$retArr['unitname1'] = $retArr['unitind1'].' '.$retArr['unitname1'];
				unset($retArr['unitind1']); 
			}
			if(array_key_exists('unitind2',$retArr)){
				$retArr['unitname2'] = $retArr['unitind2'].' '.$retArr['unitname2'];
				unset($retArr['unitind2']); 
			}
			//Build sciname, without author
			$retArr['sciname'] = trim($retArr['unitname1'].' '.$retArr['unitname2'].' '.$retArr['unitind3'].' '.$retArr['unitname3']);
			if($rankId && is_numeric($rankId)){
				$retArr['rankid'] = $rankId;
			}
			else{
				if($retArr['unitname3']){
					if($retArr['unitind3'] == 'ssp.' || !$retArr['unitind3']){
						$retArr['rankid'] = 230;
					}
					elseif($retArr['unitind3'] == 'var.'){
						$retArr['rankid'] = 240;
					}
					elseif($retArr['unitind3'] == 'f.'){
						$retArr['rankid'] = 260;
					}
				}
				elseif($retArr['unitname2']){
					$retArr['rankid'] = 220;
				} 
				elseif($retArr['unitname1']){
					if(substr($retArr['unitname1'],-5) == 'aceae' || substr($retArr['unitname1'],-4) == 'idae'){
						$retArr['rankid'] = 140;
					}
				}
			}
		}
		else{
			
		} 
		return $retArr;
	}

	/*
	 * INPUT: String representing verbatim elevation 
	 *        Verbatim string represent feet or meters   
	 * OUTPUT: Array containing minimum and maximun elevation in meters  
	 *         Keys: minelev, maxelev 
	 */
	public static function parseVerbatimElevation($inStr){
		$retArr = array();
		//Get rid of curly quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);
		//Start parsing
		if(preg_match('/(\d+)\s*-\s*(\d+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/(\d+)\s*-\s*(\d+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/(\d+)\s*-\s*(\d+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/(\d+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/(\d+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/(\d+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/(\d+)[fet\']{,4}\s*-\s*(\d+)\s{,1}[f\']{1}/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
			$retArr['maxelev'] = (round($m[2]*.3048));
		}
		elseif(preg_match('/(\d+)\s*[f\']{1}/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
		}
		//Clean
		if($retArr){
			if(array_key_exists('minelev',$retArr) && ($retArr['minelev'] > 8000 || $retArr['minelev'] < 0)) unset($retArr['minelev']);
			if(array_key_exists('maxelev',$retArr) && ($retArr['maxelev'] > 8000 || $retArr['maxelev'] < 0)) unset($retArr['maxelev']);
		}
		return $retArr;
	}

	/*
	 * INPUT: String representing verbatim coordinates 
	 *        Verbatim string can be UTM, DMS   
	 * OUTPUT: Array containing decimal values of latitude and longitude 
	 *         Keys: lat, lng 
	 */
	public static function parseVerbatimCoordinates($inStr,$target=''){
		$retArr = array();
		if(strpos($inStr,' to ')) return $retArr;
		if(strpos($inStr,' betw ')) return $retArr;
		//Get rid of curly quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);

		//Try to parse lat/lng
		$latDeg = 'null';$latMin = 0;$latSec = 0;$latNS = 'N';
		$lngDeg = 'null';$lngMin = 0;$lngSec = 0;$lngEW = 'W';
		//Grab lat deg and min
		if(!$target || $target == 'LL'){
			if(preg_match('/([\sNSns]{0,1})(-?\d{1,2}\.{1}\d+)\D{0,1}\s{0,1}([NSns]{0,1})\D{0,1}([\sEWew]{1})(-?\d{1,4}\.{1}\d+)\D{0,1}\s{0,1}([EWew]{0,1})\D*/',$inStr,$m)){
				//Decimal degree format
				$retArr['lat'] = $m[2];
				$retArr['lng'] = $m[5];
				$latDir = $m[3];
				if(!$latDir && $m[1]) $latDir = trim($m[1]);
				if($retArr['lat'] > 0 && $latDir && ($latDir == 'S' || $latDir == 's')) $retArr['lat'] = -1*$retArr['lat'];
				$lngDir = $m[6];
				if(!$lngDir && $m[4]) $lngDir = trim($m[4]);
				if($retArr['lng'] > 0 && $latDir && ($lngDir == 'W' || $lngDir == 'w')) $retArr['lng'] = -1*$retArr['lng'];
			}
			elseif(preg_match('/(\d{1,2})[^\d]{1,3}\s{0,2}(\d{1,2}\.{0,1}\d*)[\']{1}(.*)/i',$inStr,$m)){
				//DMS format
				$latDeg = $m[1];
				$latMin = $m[2];
				$leftOver = str_replace("''",'"',trim($m[3]));
				//Grab lat NS and lng EW
				if(stripos($inStr,'N') === false && strpos($inStr,'S') !== false){
					$latNS = 'S';
				}
				if(stripos($inStr,'W') === false && stripos($inStr,'E') !== false){
					$lngEW = 'E';
				}
				//Grab lat sec
				if(preg_match('/^(\d{1,2}\.{0,1}\d*)["]{1}(.*)/i',$leftOver,$m)){
					$latSec = $m[1];
					if(count($m)>2){
						$leftOver = trim($m[2]);
					}
				}
				//Grab lng deg and min
				if(preg_match('/(\d{1,3})\D{1,3}\s{0,2}(\d{1,2}\.{0,1}\d*)[\']{1}(.*)/i',$leftOver,$m)){
					$lngDeg = $m[1];
					$lngMin = $m[2];
					$leftOver = trim($m[3]);
					//Grab lng sec
					if(preg_match('/^(\d{1,2}\.{0,1}\d*)["]{1}(.*)/i',$leftOver,$m)){
						$lngSec = $m[1];
						if(count($m)>2){
							$leftOver = trim($m[2]);
						}
					}
					if(is_numeric($latDeg) && is_numeric($latMin) && is_numeric($lngDeg) && is_numeric($lngMin)){
						if($latDeg < 90 && $latMin < 60 && $lngDeg < 180 && $lngMin < 60){
							$latDec = $latDeg + ($latMin/60) + ($latSec/3600);
							$lngDec = $lngDeg + ($lngMin/60) + ($lngSec/3600);
							if($latNS == 'S'){
								$latDec = -$latDec;
							}
							if($lngEW == 'W'){
								$lngDec = -$lngDec;
							}
							$retArr['lat'] = round($latDec,6);
							$retArr['lng'] = round($lngDec,6);
						}
					}
				}
			}
		}
		if((!$target && !$retArr) || $target == 'UTM'){
			//UTM parsing
			$d = ''; 
			if(preg_match('/NAD\s*27/i',$inStr)) $d = 'NAD27';
			if(preg_match('/\D*(\d{1,2}\D{0,1})\s+(\d{6,7})m{0,1}E\s+(\d{7})m{0,1}N/i',$inStr,$m)){
				$z = $m[1];
				$e = $m[2];
				$n = $m[3];
				if($n && $e && $z){
					$llArr = OccurrenceUtilities::convertUtmToLL($e,$n,$z,$d);
					if(isset($llArr['lat'])) $retArr['lat'] = $llArr['lat'];
					if(isset($llArr['lng'])) $retArr['lng'] = $llArr['lng'];
				}
				
			}
			elseif(preg_match('/UTM/',$inStr) || preg_match('/\d{1,2}[\D\s]+\d{6,7}[\D\s]+\d{6,7}/',$inStr)){
				//UTM
				$z = ''; $e = ''; $n = '';
				if(preg_match('/^(\d{1,2}\D{0,1})[\s\D]+/',$inStr,$m)) $z = $m[1];
				if(!$z && preg_match('/[\s\D]+(\d{1,2}\D{0,1})$/',$inStr,$m)) $z = $m[1];
				if(!$z && preg_match('/[\s\D]+(\d{1,2}\D{0,1})[\s\D]+/',$inStr,$m)) $z = $m[1];
				if($z){
					if(preg_match('/(\d{6,7})m{0,1}E{1}[\D\s]+(\d{7})m{0,1}N{1}/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/m{0,1}E{1}(\d{6,7})[\D\s]+m{0,1}N{1}(\d{7})/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/(\d{7})m{0,1}N{1}[\D\s]+(\d{6,7})m{0,1}E{1}/i',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					} 
					elseif(preg_match('/m{0,1}N{1}(\d{7})[\D\s]+m{0,1}E{1}(\d{6,7})/i',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					} 
					elseif(preg_match('/(\d{6})[\D\s]+(\d{7})/',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/(\d{7})[\D\s]+(\d{6})/',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					} 
echo $e.' '.$n.' '.$z.' '.$d.'</br>';
					if($e && $n){
						$llArr = OccurrenceUtilities::convertUtmToLL($e,$n,$z,$d);
						if(isset($llArr['lat'])) $retArr['lat'] = $llArr['lat'];
						if(isset($llArr['lng'])) $retArr['lng'] = $llArr['lng'];
					}
				}				
			}
		}
		//Clean
		if($retArr){
			if($retArr['lat'] < -90 || $retArr['lat'] > 90) return;
			if($retArr['lng'] < -180 || $retArr['lng'] > 180) return;
		}
		return $retArr;
	}

	public static function convertUtmToLL($e, $n, $z, $d){
		$retArr = array();
		if($e && $n && $z){
			$gPoint = new GPoint($d);
			$gPoint->setUTM($e,$n,$z);
			$gPoint->convertTMtoLL();
			$lat = $gPoint->Lat();
			$lng = $gPoint->Long();
			if($lat && $lng){
				$retArr['lat'] = round($lat,6);
				$retArr['lng'] = round($lng,6);
			}
		}
		return $retArr;
	}
	
	//Associated species parser
	public function parseAssociatedTaxa($collid = 0){
		if(!is_numeric($collid)){
			echo '<div><b>FAIL ERROR: abort process</b></div>';
			return;
		} 
		set_time_limit(900);
		echo '<ul>';
		echo '<li>Starting to parse associated species text blocks </li>';
		ob_flush();
		flush();
		$sql = 'SELECT o.occid, o.associatedtaxa '.
			'FROM omoccurrences o LEFT JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (o.associatedtaxa IS NOT NULL) AND (o.associatedtaxa <> "") AND (a.occid IS NULL) ';
		if($collid && is_numeric($collid)){
			$sql .= 'AND (o.collid = '.$collid.') ';
		}
		//$sql .= ' LIMIT 100';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		echo '<li>Parsing new associated species text blocks (target count: '.$rs->num_rows.')... </li>';
		ob_flush();
		flush();
		$cnt = 1;
		while($r = $rs->fetch_object()){
			$assocArr = $this->parseAssocSpecies($r->associatedtaxa,$r->occid);
			if($cnt%5000 == 0) echo '<li style="margin-left:10px">'.$cnt.' specimens parsed</li>';
			$cnt++;
		}
		$rs->free();
		
		//Populate tid field using taxa table
		echo '<li>Populate tid field using taxa table... </li>';
		ob_flush();
		flush();
		$sql2 = '';
		if($collid){
			$sql2 = 'UPDATE omoccurassociations a INNER JOIN taxa t ON a.verbatimsciname = t.sciname '.
				'INNER JOIN omoccurrences o ON a.occid = o.occid '.
				'SET a.tid = t.tid '.
				'WHERE a.tid IS NULL AND (o.collid = '.$collid.') ';
		}
		else{
			$sql2 = 'UPDATE omoccurassociations a INNER JOIN taxa t ON a.verbatimsciname = t.sciname '.
				'SET a.tid = t.tid '.
				'WHERE a.tid IS NULL';
		}
		if(!$this->conn->query($sql2)){
			echo '<li style="margin-left:20px;">Unable to populate tid field using taxa table: '.$this->conn->error.'</li>';
			//echo '<li style="margin-left:20px;">'.$sql2.'</li>';
		}

		//Populate tid field using taxavernaculars table
		echo '<li>Populate tid field using taxavernaculars table... </li>';
		ob_flush();
		flush();
		$sql3 = '';
		if($collid){
			$sql3 = 'UPDATE omoccurassociations a INNER JOIN taxavernaculars v ON a.verbatimsciname = v.vernacularname '.
				'INNER JOIN omoccurrences o ON a.occid = o.occid '.
				'SET a.tid = v.tid '.
				'WHERE (a.tid IS NULL) AND (o.collid = '.$collid.') ';
		}
		else{
			$sql3 = 'UPDATE omoccurassociations a INNER JOIN taxavernaculars v ON a.verbatimsciname = v.vernacularname '.
				'SET a.tid = v.tid '.
				'WHERE a.tid IS NULL ';
		}
		if(!$this->conn->query($sql3)){
			echo '<li style="margin-left:20px;">Unable to populate tid field using taxavernaculars table: '.$this->conn->error.'</li>';
			//echo '<li style="margin-left:20px;">'.$sql3.'</li>';
		}
		
		//Populate tid field by linking back to omoccurassociations table
		//This assumes that tids are correct; in future verificationscore field can be used to select only those that have been verified
		echo '<li>Populate tid field by linking back to omoccurassociations table... </li>';
		ob_flush();
		flush();
		$sql4 = '';
		if($collid){
			$sql4 = 'UPDATE omoccurassociations a INNER JOIN omoccurassociations a2 ON a.verbatimsciname = a2.verbatimsciname '.
				'INNER JOIN omoccurrences o ON a.occid = o.occid '.
				'SET a.tid = a2.tid '.
				'WHERE (a.tid IS NULL) AND (a2.tid IS NOT NULL) AND (o.collid = '.$collid.') ';
		}
		else{
			$sql4 = 'UPDATE omoccurassociations a INNER JOIN omoccurassociations a2 ON a.verbatimsciname = a2.verbatimsciname '.
				'SET a.tid = a2.tid '.
				'WHERE a.tid IS NULL AND a2.tid IS NOT NULL ';
		}
		if(!$this->conn->query($sql4)){
			echo '<li style="margin-left:20px;">Unable to populate tid field relinking back to omoccurassociations table: '.$this->conn->error.'</li>';
			//echo '<li style="margin-left:20px;">'.$sql4.'</li>';
		}
		
		//Lets get the harder ones
		echo '<li>Mining database for the more difficult matches... </li>';
		ob_flush();
		flush();
		$sql5 = '';
		if($collid){
			$sql5 = 'SELECT DISTINCT a.verbatimsciname '.
				'FROM omoccurassociations a INNER JOIN omoccurrences o ON a.occid = o.occid '.
				'WHERE (a.tid IS NULL) AND (o.collid = '.$collid.') ';
		}
		else{
			$sql5 = 'SELECT DISTINCT verbatimsciname '.
				'FROM omoccurassociations '.
				'WHERE tid IS NULL ';
		}
		$rs5 = $this->conn->query($sql5);
		while($r5 = $rs5->fetch_object()){
			$verbStr = $r5->verbatimsciname;
			$tid = $this->mineAssocSpeciesMatch($verbStr);
			if($tid){
				$sql5b = 'UPDATE omoccurassociations '.
					'SET tid = '.$tid.' '.
					'WHERE tid IS NULL AND verbatimsciname = "'.$verbStr.'"';
				if(!$this->conn->query($sql5b)){
					echo '<li style="margin-left:20px;">Unable to populate NULL tid field: '.$this->conn->error.'</li>';
					//echo '<li style="margin-left:20px;">'.$sql5b.'</li>';
				}
			}
		}
		$rs5->free();
		
		echo '<li>DONE!</li>';
		echo '</ul>';
		ob_flush();
		flush();
	}

	private function parseAssocSpecies($assocSpeciesStr,$occid){
		$parseArr = array();
		if($assocSpeciesStr){
			//Separate associated species
			$assocSpeciesStr = str_replace(array('&',' and ',';'),',',$assocSpeciesStr);
			$assocArr = explode(',',$assocSpeciesStr);
			//Add to return array
			foreach($assocArr as $v){
				$vStr = trim($v,'."-()[]:#\' ');
				if(substr($vStr,-3) == ' sp') $vStr = substr($vStr,0,strlen($vStr)-3);
				if(substr($vStr,-4) == ' spp') $vStr = substr($vStr,0,strlen($vStr)-4);
				$vStr = preg_replace('/\s\s+/', ' ',$vStr);
				if($vStr){
					//If genus is abbreviated (e.g. P. ponderosa), try to get genus from previous entry 
					if(preg_match('/^([A-Z]{1})\.{0,1}\s{1}([a-z]*)$/',$vStr,$m)){
						//Iterate through parseArr in reverse until match is found
						$cnt = 0;
						for($i = (count($parseArr)-1); $i >= 0; $i--){
							if(preg_match('/^('.$m[1].'{1}[a-z]+)\s+/',$vStr,$m2)){
								$vStr = $m2[1].' '.$m[2];
								//Possible code to add: verify that name is in taxa tables  
								break;
							}
							if($cnt > 3) break;
							$cnt++;
						}
					}
					$parseArr[] = $vStr;
				}
			}
			//Database verbatim values
			$this->databaseAssocSpecies($parseArr,$occid);
		}
	}

	private function databaseAssocSpecies($assocArr, $occid){
		if($assocArr){
			$sql = 'INSERT INTO omoccurassociations(occid, verbatimsciname, relationship) VALUES';
			foreach($assocArr as $aStr){
				$sql .= '('.$occid.',"'.$this->conn->real_escape_string($aStr).'","associatedSpecies"), ';
			}
			$sql = trim($sql,', ');
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				echo '<li style="margin-left:20px;">ERROR adding assocaited values (<a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$occid.'</a>): '.$this->conn->error.'</li>';
				//echo '<li style="margin-left:20px;">SQL: '.$sql.'</li>';
			}
		}
	}
	
	private function mineAssocSpeciesMatch($verbStr){
		$retTid = 0;
		//Pattern: P. ponderosa
		if(preg_match('/^([A-Z]{1})\.{0,1}\s{1}([a-z]*)$/',$verbStr,$m)){
			$sql = 'SELECT tid, sciname '.
				'FROM taxa '. 
				'WHERE unitname1 LIKE "'.$m[1].'%" AND unitname2 = "'.$m[2].'" AND rankid = 220';
			//echo $sql.'; '.$verbStr;
			$rs = $this->conn->query($sql);
			if($rs->num_rows == 1){
				if($r = $rs->fetch_object()){
					$retTid = $r->tid;
				}
			}
			$rs->free();
		}
		//Add code that uses Levenshtein distance matching on taxa table
		
		
		//Add code that uses Levenshtein distance matching on taxavernaculars table

		
		return $retTid;
	}

	public function getParsingStats($collid){
		$retArr = array();
		//Get parsed count
		$sqlZ = 'SELECT COUNT(DISTINCT o.occid) as cnt '.
			'FROM omoccurrences o INNER JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (a.relationship = "associatedSpecies") ';
		if($collid){
			$sqlZ .= 'AND (o.collid = '.$collid.') ';
		}
		$rsZ = $this->conn->query($sqlZ);
		while($rZ = $rsZ->fetch_object()){
			$retArr['parsed'] = $rZ->cnt;
		}
		$rsZ->free();

		//Get unparsed count
		$sqlA = 'SELECT count(o.occid) as cnt '.
			'FROM omoccurrences o LEFT JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (o.associatedtaxa IS NOT NULL) AND (o.associatedtaxa <> "") AND (a.occid IS NULL) ';
		if($collid){
			$sqlA .= 'AND (o.collid = '.$collid.') ';
		}
		$rsA = $this->conn->query($sqlA);
		while($rA = $rsA->fetch_object()){
			$retArr['unparsed'] = $rA->cnt;
		}
		$rsA->free();

		//Get field count for parsing failures
		$sqlB = 'SELECT count(a.occid) as cnt '.
			'FROM omoccurrences o INNER JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (a.verbatimsciname IS NOT NULL) AND (a.tid IS NULL) ';
		if($collid){
			$sqlB .= 'AND (o.collid = '.$collid.') ';
		}
		$rsB = $this->conn->query($sqlB);
		while($rB = $rsB->fetch_object()){
			$retArr['failed'] = $rB->cnt;
		}
		$rsB->free();

		//Get specimen count for parsing failures
		$sqlC = 'SELECT count(DISTINCT o.occid) as cnt '.
			'FROM omoccurrences o INNER JOIN omoccurassociations a ON o.occid = a.occid '.
			'WHERE (a.verbatimsciname IS NOT NULL) AND (a.tid IS NULL) ';
		if($collid){
			$sqlC .= 'AND (o.collid = '.$collid.') ';
		}
		$rsC = $this->conn->query($sqlC);
		while($rC = $rsC->fetch_object()){
			$retArr['failedOccur'] = $rC->cnt;
		}
		$rsC->free();
		return $retArr;
	}

	//General cleaning functions 
	public function generalOccurrenceCleaning($collId){
		set_time_limit(600);
		$status = true;

		if($this->verbose) $this->outputMsg('Updating null families of family rank identifications... ',1);
		$sql1 = 'SELECT occid FROM omoccurrences WHERE (family IS NULL) AND (sciname LIKE "%aceae" OR sciname LIKE "%idae")';
		$rs1 = $this->conn->query($sql1);
		$occidArr1 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr1[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr1){
			$sql = 'UPDATE omoccurrences '.
				'SET family = sciname '.
				'WHERE occid IN('.implode(',',$occidArr1).')';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update family; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr1);
		
		if($this->verbose) $this->outputMsg('Updating null scientific names of family rank identifications... ',1);
		$sql1 = 'SELECT occid FROM omoccurrences WHERE family IS NOT NULL AND sciname IS NULL';
		$rs1 = $this->conn->query($sql1);
		$occidArr2 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr2[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr2){
			$sql = 'UPDATE omoccurrences SET sciname = family WHERE occid IN('.implode(',',$occidArr2).') ';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update sciname using family; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr2);
		
		if($this->verbose) $this->outputMsg('Indexing valid scientific names (e.g. populating tidinterpreted)... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'WHERE o.collid IN('.$collId.') AND o.TidInterpreted IS NULL';
		$rs1 = $this->conn->query($sql1);
		$occidArr3 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr3[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr3){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
				'SET o.TidInterpreted = t.tid '. 
				'WHERE o.occid IN('.implode(',',$occidArr3).') ';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update tidinterpreted; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr3);
		
		if($this->verbose) $this->outputMsg('Updating and indexing occurrence images... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'WHERE o.collid IN('.$collId.') AND (i.tid IS NULL) AND (o.tidinterpreted IS NOT NULL)';
		$rs1 = $this->conn->query($sql1);
		$occidArr4 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr4[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr4){
			$sql = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid '. 
				'SET i.tid = o.tidinterpreted '. 
				'WHERE o.occid IN('.implode(',',$occidArr4).')';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update image tid field; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr4);
		
		if($this->verbose) $this->outputMsg('Updating null families using taxonomic thesaurus... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
			'WHERE o.collid IN('.$collId.') AND (ts.taxauthid = 1) AND (ts.family IS NOT NULL) AND (o.family IS NULL)';
		$rs1 = $this->conn->query($sql1);
		$occidArr5 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr5[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr5){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '. 
				'SET o.family = ts.family '. 
				'WHERE o.occid IN('.implode(',',$occidArr5).')';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update family in omoccurrence table; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr5);

		#Updating records with null author
		if($this->verbose) $this->outputMsg('Updating null scientific authors using taxonomic thesaurus... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
			'WHERE o.scientificNameAuthorship IS NULL AND t.author IS NOT NULL LIMIT 5000 ';
		$rs1 = $this->conn->query($sql1);
		$occidArr6 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr6[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr6){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '. 
				'SET o.scientificNameAuthorship = t.author '. 
				'WHERE (o.occid IN('.implode(',',$occidArr6).'))';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update author; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr6);
		
		/*
		if($this->verbose) $this->outputMsg('Updating georeference index... ',1);
		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '.
			'FROM omoccurrences o '.
			'WHERE o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL '.
			'AND o.decimallongitude IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$errStr = 'WARNING: unable to update georeference index; '.$this->conn->error;
			$this->errorArr[] = $errStr;
			if($this->verbose) $this->outputMsg($errStr,2);
			$status = false;
		}
		*/
		
		return $status;
	}
	
	//Protect Rare species data
	public function protectRareSpecies($collid = 0){
		$this->protectGloballyRareSpecies($collid);
		$this->protectStateRareSpecies($collid);
	}
	
	public function protectGloballyRareSpecies($collid = 0){
		$status = true;
		//protect globally rare species
		if($this->verbose) $this->outputMsg('Protecting globally rare species... ',1);
		$sensitiveArr = array();
		$sql = 'SELECT DISTINCT ts2.tid '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
			'WHERE (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (t.SecurityStatus > 0)';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$sensitiveArr[] = $r->tid; 
		}
		$rs->free();

		if($sensitiveArr){
			$sql2 = 'UPDATE omoccurrences o '.
				'SET o.LocalitySecurity = 1 '.
				'WHERE (o.LocalitySecurity IS NULL OR o.LocalitySecurity = 0) AND (o.tidinterpreted IN('.implode(',',$sensitiveArr).'))';
			if(!$this->conn->query($sql2)){
				$errStr = 'WARNING: unable to protect globally rare species; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		return $status;
	}

	public function protectStateRareSpecies($collid = 0){
		$status = true;
		//Protect state level rare species
		if($this->verbose) $this->outputMsg('Protecting state level rare species... ',1);
		$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
			'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchecklists c ON o.stateprovince = c.locality '. 
			'INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid AND ts2.tid = cl.tid '.
			'SET o.localitysecurity = 1 '.
			'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (c.type = "rarespp") '.
			'AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ';
		if($collid) $sql .= ' AND o.collid ='.$collid;
		if(!$this->conn->query($sql)){
			$errStr = 'WARNING: unable to protect state level rare species; '.$this->conn->error;
			$this->errorArr[] = $errStr;
			if($this->verbose) $this->outputMsg($errStr,2);
			$status = false;
		}
		return $status;
	}

	//Update statistics
	public function updateCollectionStats($collid, $full = false){
		set_time_limit(600);
		
		$recordCnt = 0;
		$georefCnt = 0;
		$familyCnt = 0;
		$genusCnt = 0;
		$speciesCnt = 0;
		if($full){
			$statsArr = Array();
			if($this->verbose) $this->outputMsg('Calculating specimen, georeference, family, genera, and species counts... ',1);
			$sql = 'SELECT COUNT(o.occid) AS SpecimenCount, COUNT(o.decimalLatitude) AS GeorefCount, '.
				'COUNT(DISTINCT o.family) AS FamilyCount, COUNT(o.typeStatus) AS TypeCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId >= 180 THEN t.UnitName1 ELSE NULL END) AS GeneraCount, '.
				'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS SpecimensCountID, '.
				'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId >= 220 THEN t.SciName ELSE NULL END) AS TotalTaxaCount '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid = '.$collid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$recordCnt = $r->SpecimenCount;
				$georefCnt = $r->GeorefCount;
				$familyCnt = $r->FamilyCount;
				$genusCnt = $r->GeneraCount;
				$speciesCnt = $r->SpeciesCount;
				$statsArr['SpecimensCountID'] = $r->SpecimensCountID;
				$statsArr['TotalTaxaCount'] = $r->TotalTaxaCount;
				$statsArr['TypeCount'] = $r->TypeCount;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating number of specimens imaged... ',1);
			$sql = 'SELECT count(DISTINCT o.occid) as imgcnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$statsArr['imgcnt'] = $r->imgcnt;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating genetic resources counts... ',1);
			$sql = 'SELECT COUNT(CASE WHEN g.resourceurl LIKE "http://www.boldsystems%" THEN o.occid ELSE NULL END) AS boldcnt, '.
				'COUNT(CASE WHEN g.resourceurl LIKE "http://www.ncbi%" THEN o.occid ELSE NULL END) AS gencnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid = '.$collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$statsArr['boldcnt'] = $r->boldcnt;
				$statsArr['gencnt'] = $r->gencnt;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating reference counts... ',1);
			$sql = 'SELECT count(r.occid) as refcnt '.
				'FROM omoccurrences o INNER JOIN referenceoccurlink r ON o.occid = r.occid '.
				'WHERE (o.collid = '.$collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$statsArr['refcnt'] = $r->refcnt;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating counts per family... ',1);
			$sql = 'SELECT o.family, COUNT(o.occid) AS SpecimensPerFamily, COUNT(o.decimalLatitude) AS GeorefSpecimensPerFamily, '.
				'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerFamily, '.
				'COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerFamily '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid = '.$collid.') '.
				'GROUP BY o.family ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$family = str_replace(array('"',"'"),"",$r->family);
				if($family){
					$statsArr['families'][$family]['SpecimensPerFamily'] = $r->SpecimensPerFamily;
					$statsArr['families'][$family]['GeorefSpecimensPerFamily'] = $r->GeorefSpecimensPerFamily;
					$statsArr['families'][$family]['IDSpecimensPerFamily'] = $r->IDSpecimensPerFamily;
					$statsArr['families'][$family]['IDGeorefSpecimensPerFamily'] = $r->IDGeorefSpecimensPerFamily;
				}
			}
			$rs->free();
			
			if($this->verbose) $this->outputMsg('Calculating counts per country... ',1);
			$sql = 'SELECT o.country, COUNT(o.occid) AS CountryCount, COUNT(o.decimalLatitude) AS GeorefSpecimensPerCountry, '.
				'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerCountry, '.
				'COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerCountry '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid = '.$collid.') '.
				'GROUP BY o.country ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$country = str_replace(array('"',"'"),"",$r->country);
				if($country){
					$statsArr['countries'][$country]['CountryCount'] = $r->CountryCount;
					$statsArr['countries'][$country]['GeorefSpecimensPerCountry'] = $r->GeorefSpecimensPerCountry;
					$statsArr['countries'][$country]['IDSpecimensPerCountry'] = $r->IDSpecimensPerCountry;
					$statsArr['countries'][$country]['IDGeorefSpecimensPerCountry'] = $r->IDGeorefSpecimensPerCountry;
				}
			}
			$rs->free();

			$returnArrJson = json_encode($statsArr);
			$sql = 'UPDATE omcollectionstats '.
				"SET dynamicProperties = '".$this->cleanInStr($returnArrJson)."' ".
				'WHERE collid = '.$collid;
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update collection stats table [1]; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
			}
		}
		else{
			if($this->verbose) $this->outputMsg('Calculating specimen, georeference, family, genera, and species counts... ',1);
			$sql = 'SELECT COUNT(o.occid) AS SpecimenCount, COUNT(o.decimalLatitude) AS GeorefCount, COUNT(DISTINCT o.family) AS FamilyCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId >= 180 THEN t.UnitName1 ELSE NULL END) AS GeneraCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid = '.$collid.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$recordCnt = $r->SpecimenCount;
				$georefCnt = $r->GeorefCount;
				$familyCnt = $r->FamilyCount;
				$genusCnt = $r->GeneraCount;
				$speciesCnt = $r->SpeciesCount;
			}
		}
		
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = '.$recordCnt.',cs.georefcnt = '.$georefCnt.',cs.familycnt = '.$familyCnt.',cs.genuscnt = '.$genusCnt.
			',cs.speciescnt = '.$speciesCnt.', cs.datelastmodified = CURDATE() '.
			'WHERE cs.collid = '.$collid;
		if(!$this->conn->query($sql)){
			$errStr = 'WARNING: unable to update collection stats table [2]; '.$this->conn->error;
			$this->errorArr[] = $errStr;
			if($this->verbose) $this->outputMsg($errStr,2);
		}
	}
	
	//Misc support functions
	public function getCollectionMetadata($collid){
		$retArr = array();
		if(is_numeric($collid)){
			$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype, managementtype '.
				'FROM omcollections '.
				'WHERE collid = '.$collid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['instcode'] = $r->institutioncode;
				$retArr['collcode'] = $r->collectioncode;
				$retArr['collname'] = $r->collectionname;
				$retArr['colltype'] = $r->colltype;
				$retArr['mantype'] = $r->managementtype;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function setVerbose($v){
		if($v){
			$this->verbose = true;
		}
		else{
			$this->verbose = false;
		}
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	private function outputMsg($str, $indent = 0){
		if($this->verbose){
			echo '<li style="margin-left:'.($indent*10).'px;">'.$str.'</li>';
		}
		ob_flush();
		flush();
	}

	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = preg_replace('/\s\s+/', ' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>