<?php
include_once($SERVER_ROOT.'/classes/GPoint.php');

class OccurrenceUtilities {

	static $monthRoman = array('I'=>'01','II'=>'02','III'=>'03','IV'=>'04','V'=>'05','VI'=>'06','VII'=>'07','VIII'=>'08','IX'=>'09','X'=>'10','XI'=>'11','XII'=>'12');
	static $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');

 	public function __construct(){
 	}
 	
 	public function __destruct(){
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
		if(preg_match('/([\.\d]+)\s*-\s*([\.\d]+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/([\.\d]+)\s*-\s*([\.\d]+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/([\.\d]+)\s*-\s*([\.\d]+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/([\.\d]+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/([\.\d]+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/([\.\d]+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/([\.\d]+)[fet\']{,4}\s*-\s*([\.\d]+)\s{,1}[f\']{1}/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
			$retArr['maxelev'] = (round($m[2]*.3048));
		}
		elseif(preg_match('/([\.\d]+)\s*[f\']{1}/i',$inStr,$m)){
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

	public static function occurrenceArrayCleaning($recMap){
		//Trim all field values
		foreach($recMap as $k => $v){
			$recMap[$k] = trim($v);
		}
		//Date cleaning
		if(isset($recMap['eventdate']) && $recMap['eventdate']){
			if(is_numeric($recMap['eventdate'])){
				if($recMap['eventdate'] > 2100 && $recMap['eventdate'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['eventdate'] = date('Y-m-d', mktime(0,0,0,1,$recMap['eventdate']-1,1900));
				}
				elseif($recMap['eventdate'] > 2200000 && $recMap['eventdate'] < 2500000){
					//Date is in the Gregorian format
					$dArr = explode('/',jdtogregorian($recMap['eventdate']));
					$recMap['eventdate'] = $dArr[2].'-'.$dArr[0].'-'.$dArr[1];
				}
				elseif($recMap['eventdate'] > 19000000){
					//Format: 20120101 = 2012-01-01
					$recMap['eventdate'] = substr($recMap['eventdate'],0,4).'-'.substr($recMap['eventdate'],4,2).'-'.substr($recMap['eventdate'],6,2);
				}
			}
			else{
				//Make sure event date is a valid format or drop into verbatimEventDate
				$dateStr = OccurrenceUtilities::formatDate($recMap['eventdate']);
				if($dateStr){
					if($recMap['eventdate'] != $dateStr && (!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate'])){
						$recMap['verbatimeventdate'] = $recMap['eventdate'];
					}
					$recMap['eventdate'] = $dateStr;
				}
				else{
					if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
						$recMap['verbatimeventdate'] = $recMap['eventdate'];
					}
					unset($recMap['eventdate']);
				}
			}
		}
		if(array_key_exists('latestdatecollected',$recMap) && $recMap['latestdatecollected'] && is_numeric($recMap['latestdatecollected'])){
			if($recMap['latestdatecollected'] > 2100 && $recMap['latestdatecollected'] < 45000){
				//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
				$recMap['latestdatecollected'] = date('Y-m-d', mktime(0,0,0,1,$recMap['latestdatecollected']-1,1900));
			}
			elseif($recMap['latestdatecollected'] > 2200000 && $recMap['latestdatecollected'] < 2500000){
				$dArr = explode('/',jdtogregorian($recMap['latestdatecollected']));
				$recMap['latestdatecollected'] = $dArr[2].'-'.$dArr[0].'-'.$dArr[1];
			}
			elseif($recMap['latestdatecollected'] > 19000000){
				$recMap['latestdatecollected'] = substr($recMap['latestdatecollected'],0,4).'-'.substr($recMap['latestdatecollected'],4,2).'-'.substr($recMap['latestdatecollected'],6,2);
			}
		}
		if(array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate'] && is_numeric($recMap['verbatimeventdate'])
				&& $recMap['verbatimeventdate'] > 2100 && $recMap['verbatimeventdate'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['verbatimeventdate'] = date('Y-m-d', mktime(0,0,0,1,$recMap['verbatimeventdate']-1,1900));
		}
		if(array_key_exists('dateidentified',$recMap) && $recMap['dateidentified'] && is_numeric($recMap['dateidentified'])
				&& $recMap['dateidentified'] > 2100 && $recMap['dateidentified'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['dateidentified'] = date('Y-m-d', mktime(0,0,0,1,$recMap['dateidentified']-1,1900));
		}
		//If month, day, or year are text, avoid SQL error by converting to numeric value
		if(array_key_exists('year',$recMap) || array_key_exists('month',$recMap) || array_key_exists('day',$recMap)){
			$y = (array_key_exists('year',$recMap)?$recMap['year']:'00');
			$m = (array_key_exists('month',$recMap)?$recMap['month']:'00');
			$d = (array_key_exists('day',$recMap)?$recMap['day']:'00');
			$vDate = trim($y.'-'.$m.'-'.$d,'- ');
			if(isset($recMap['day']) && !is_numeric($recMap['day'])){
				if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
					$recMap['verbatimeventdate'] = $vDate;
				}
				unset($recMap['day']);
				$d = '00';
			}
			if(isset($recMap['year']) && !is_numeric($recMap['year'])){
				if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
					$recMap['verbatimeventdate'] = $vDate;
				}
				unset($recMap['year']);
			}
			if(isset($recMap['month']) && $recMap['month'] && !is_numeric($recMap['month'])){
				if(strlen($recMap['month']) > 2){
					$monAbbr = strtolower(substr($recMap['month'],0,3));
					if(array_key_exists($monAbbr,OccurrenceUtilities::$monthNames)){
						$recMap['month'] = OccurrenceUtilities::$monthNames[$monAbbr];
						$recMap['eventdate'] = OccurrenceUtilities::formatDate(trim($y.'-'.$recMap['month'].'-'.($d?$d:'00'),'- '));
					}
					else{
						if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
							$recMap['verbatimeventdate'] = $vDate;
						}
						unset($recMap['month']);
					}
				}
				else{
					if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']) {
						$recMap['verbatimeventdate'] = $vDate;
					}
					unset($recMap['month']);
				}
			}
			if($vDate && (!array_key_exists('eventdate',$recMap) || !$recMap['eventdate'])){
				$recMap['eventdate'] = OccurrenceUtilities::formatDate($vDate);
			}
		}
		//eventDate NULL && verbatimEventDate NOT NULL && year NOT NULL
		if((!array_key_exists('eventdate',$recMap) || !$recMap['eventdate']) && array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate'] && (!array_key_exists('year',$recMap) || !$recMap['year'])){
			$dateStr = OccurrenceUtilities::formatDate($recMap['verbatimeventdate']);
			if($dateStr) $recMap['eventdate'] = $dateStr;
		}
		if((isset($recMap['recordnumberprefix']) && $recMap['recordnumberprefix']) || (isset($recMap['recordnumbersuffix']) && $recMap['recordnumbersuffix'])){
			$recNumber = $recMap['recordnumber'];
			if(isset($recMap['recordnumberprefix']) && $recMap['recordnumberprefix']) $recNumber = $recMap['recordnumberprefix'].'-'.$recNumber;
			if(isset($recMap['recordnumbersuffix']) && $recMap['recordnumbersuffix']){
				if(is_numeric($recMap['recordnumbersuffix']) && $recMap['recordnumber']) $recNumber .= '-';
				$recNumber .= $recMap['recordnumbersuffix'];
			}
			$recMap['recordnumber'] = $recNumber;
		}
		//If lat or long are not numeric, try to make them so
		if(array_key_exists('decimallatitude',$recMap) || array_key_exists('decimallongitude',$recMap)){
			$latValue = (array_key_exists('decimallatitude',$recMap)?$recMap['decimallatitude']:'');
			$lngValue = (array_key_exists('decimallongitude',$recMap)?$recMap['decimallongitude']:'');
			if(($latValue && !is_numeric($latValue)) || ($lngValue && !is_numeric($lngValue))){
				$llArr = OccurrenceUtilities::parseVerbatimCoordinates(trim($latValue.' '.$lngValue),'LL');
				if(array_key_exists('lat',$llArr) && array_key_exists('lng',$llArr)){
					$recMap['decimallatitude'] = $llArr['lat'];
					$recMap['decimallongitude'] = $llArr['lng'];
				}
				else{
					unset($recMap['decimallatitude']);
					unset($recMap['decimallongitude']);
				}
				$vcStr = '';
				if(array_key_exists('verbatimcoordinates',$recMap) && $recMap['verbatimcoordinates']){
					$vcStr .= $recMap['verbatimcoordinates'].'; ';
				}
				$vcStr .= $latValue.' '.$lngValue;
				if(trim($vcStr)) $recMap['verbatimcoordinates'] = trim($vcStr);
			}
		}
		//Transfer verbatim Lat/Long to verbatim coords
		if(isset($recMap['verbatimlatitude']) || isset($recMap['verbatimlongitude'])){
			if(isset($recMap['verbatimlatitude']) && isset($recMap['verbatimlongitude'])){
				if(!isset($recMap['decimallatitude']) || !isset($recMap['decimallongitude'])){
					if((is_numeric($recMap['verbatimlatitude']) && is_numeric($recMap['verbatimlongitude']))){
						if($recMap['verbatimlatitude'] > -90 && $recMap['verbatimlatitude'] < 90
								&& $recMap['verbatimlongitude'] > -180 && $recMap['verbatimlongitude'] < 180){
									$recMap['decimallatitude'] = $recMap['verbatimlatitude'];
									$recMap['decimallongitude'] = $recMap['verbatimlongitude'];
						}
					}
					else{
						//Attempt to extract decimal lat/long
						$coordArr = OccurrenceUtilities::parseVerbatimCoordinates($recMap['verbatimlatitude'].' '.$recMap['verbatimlongitude'],'LL');
						if($coordArr){
							if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
							if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
						}
					}
				}
			}
			//Place into verbatim coord field
			$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
			if($vCoord) $vCoord .= '; ';
			if(stripos($vCoord,$recMap['verbatimlatitude']) === false && stripos($vCoord,$recMap['verbatimlongitude']) === false){
				$recMap['verbatimcoordinates'] = $vCoord.$recMap['verbatimlatitude'].', '.$recMap['verbatimlongitude'];
			}
		}
		//Transfer DMS to verbatim coords
		if(isset($recMap['latdeg']) && $recMap['latdeg'] && isset($recMap['lngdeg']) && $recMap['lngdeg']){
			//Attempt to create decimal lat/long
			if(is_numeric($recMap['latdeg']) && is_numeric($recMap['lngdeg']) && (!isset($recMap['decimallatitude']) || !isset($recMap['decimallongitude']))){
				$latDec = $recMap['latdeg'];
				if(isset($recMap['latmin']) && $recMap['latmin'] && is_numeric($recMap['latmin'])) $latDec += $recMap['latmin']/60;
				if(isset($recMap['latsec']) && $recMap['latsec'] && is_numeric($recMap['latsec'])) $latDec += $recMap['latsec']/3600;
				if(stripos($recMap['latns'],'s') === 0 && $latDec > 0) $latDec *= -1;
				$lngDec = $recMap['lngdeg'];
				if(isset($recMap['lngmin']) && $recMap['lngmin'] && is_numeric($recMap['lngmin'])) $lngDec += $recMap['lngmin']/60;
				if(isset($recMap['lngsec']) && $recMap['lngsec'] && is_numeric($recMap['lngsec'])) $lngDec += $recMap['lngsec']/3600;
				if(stripos($recMap['lngew'],'e') === 0 ) $lngDec *= -1;
				$recMap['decimallatitude'] = round($latDec,6);
				$recMap['decimallongitude'] = round($lngDec,6);
			}
			//Place into verbatim coord field
			$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
			if($vCoord) $vCoord .= '; ';
			$vCoord .= $recMap['latdeg'].'° ';
			if(isset($recMap['latmin']) && $recMap['latmin']) $vCoord .= $recMap['latmin'].'m ';
			if(isset($recMap['latsec']) && $recMap['latsec']) $vCoord .= $recMap['latsec'].'s ';
			if(isset($recMap['latns'])) $vCoord .= $recMap['latns'].'; ';
			$vCoord .= $recMap['lngdeg'].'° ';
			if(isset($recMap['lngmin']) && $recMap['lngmin']) $vCoord .= $recMap['lngmin'].'m ';
			if(isset($recMap['lngsec']) && $recMap['lngsec']) $vCoord .= $recMap['lngsec'].'s ';
			if(isset($recMap['lngew'])) $vCoord .= $recMap['lngew'];
			$recMap['verbatimcoordinates'] = $vCoord;
		}
		/*
		 if(array_key_exists('verbatimcoordinates',$recMap) && $recMap['verbatimcoordinates'] && (!isset($recMap['decimallatitude']) || !isset($recMap['decimallongitude']))){
		 $coordArr = OccurrenceUtilities::parseVerbatimCoordinates($recMap['verbatimcoordinates']);
		 if($coordArr){
		 if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
		 if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
		 }
		 }
		 */
		//Convert UTM to Lat/Long
		if((array_key_exists('utmnorthing',$recMap) && $recMap['utmnorthing']) || (array_key_exists('utmeasting',$recMap) && $recMap['utmeasting'])){
			$no = (array_key_exists('utmnorthing',$recMap)?$recMap['utmnorthing']:'');
			$ea = (array_key_exists('utmeasting',$recMap)?$recMap['utmeasting']:'');
			$zo = (array_key_exists('utmzoning',$recMap)?$recMap['utmzoning']:'');
			$da = (array_key_exists('geodeticdatum',$recMap)?$recMap['geodeticdatum']:'');
			if(!isset($recMap['decimallatitude']) || !isset($recMap['decimallongitude'])){
				if($no && $ea && $zo){
					//Northing, easting, and zoning all had values
					$llArr = OccurrenceUtilities::convertUtmToLL($ea,$no,$zo,$da);
					if(isset($llArr['lat'])) $recMap['decimallatitude'] = $llArr['lat'];
					if(isset($llArr['lng'])) $recMap['decimallongitude'] = $llArr['lng'];
				}
				else{
					//UTM was a single field which was placed in UTM northing field within uploadspectemp table
					$coordArr = OccurrenceUtilities::parseVerbatimCoordinates(trim($zo.' '.$ea.' '.$no),'UTM');
					if($coordArr){
						if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
						if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
					}
				}
			}
			$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
			if(!($no && strpos($vCoord,$no))) $recMap['verbatimcoordinates'] = ($vCoord?$vCoord.'; ':'').$zo.' '.$ea.'E '.$no.'N';
		}
		//Transfer TRS to verbatim coords
		if(isset($recMap['trstownship']) && $recMap['trstownship'] && isset($recMap['trsrange']) && $recMap['trsrange']){
			$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
			if($vCoord) $vCoord .= '; ';
			$vCoord .= (stripos($recMap['trstownship'],'t') === false?'T':'').$recMap['trstownship'].' ';
			$vCoord .= (stripos($recMap['trsrange'],'r') === false?'R':'').$recMap['trsrange'].' ';
			if(isset($recMap['trssection'])) $vCoord .= (stripos($recMap['trssection'],'s') === false?'sec':'').$recMap['trssection'].' ';
			if(isset($recMap['trssectiondetails'])) $vCoord .= $recMap['trssectiondetails'];
			$recMap['verbatimcoordinates'] = trim($vCoord);
		}
			
		//Check to see if evelation are valid numeric values
		if((isset($recMap['minimumelevationinmeters']) && $recMap['minimumelevationinmeters'] && !is_numeric($recMap['minimumelevationinmeters']))
				|| (isset($recMap['maximumelevationinmeters']) && $recMap['maximumelevationinmeters'] && !is_numeric($recMap['maximumelevationinmeters']))){
					$vStr = (isset($recMap['verbatimelevation'])?$recMap['verbatimelevation']:'');
					if(isset($recMap['minimumelevationinmeters']) && $recMap['minimumelevationinmeters']) $vStr .= ($vStr?'; ':'').$recMap['minimumelevationinmeters'];
					if(isset($recMap['maximumelevationinmeters']) && $recMap['maximumelevationinmeters']) $vStr .= '-'.$recMap['maximumelevationinmeters'];
					$recMap['verbatimelevation'] = $vStr;
					$recMap['minimumelevationinmeters'] = '';
					$recMap['maximumelevationinmeters'] = '';
		}
		//Verbatim elevation
		if(array_key_exists('verbatimelevation',$recMap) && $recMap['verbatimelevation'] && (!array_key_exists('minimumelevationinmeters',$recMap) || !$recMap['minimumelevationinmeters'])){
			$eArr = OccurrenceUtilities::parseVerbatimElevation($recMap['verbatimelevation']);
			if($eArr){
				if(array_key_exists('minelev',$eArr)){
					$recMap['minimumelevationinmeters'] = $eArr['minelev'];
					if(array_key_exists('maxelev',$eArr)) $recMap['maximumelevationinmeters'] = $eArr['maxelev'];
				}
			}
		}
		//Deal with elevation when in two fields (number and units)
		if(isset($recMap['elevationnumber']) && $recMap['elevationnumber']){
			$elevStr = $recMap['elevationnumber'].$recMap['elevationunits'];
			//Try to extract meters
			$eArr = OccurrenceUtilities::parseVerbatimElevation($elevStr);
			if($eArr){
				if(array_key_exists('minelev',$eArr)){
					$recMap['minimumelevationinmeters'] = $eArr['minelev'];
					if(array_key_exists('maxelev',$eArr)) $recMap['maximumelevationinmeters'] = $eArr['maxelev'];
				}
			}
			if(!$eArr || !stripos($elevStr,'m')){
				$vElev = (isset($recMap['verbatimelevation'])?$recMap['verbatimelevation']:'');
				if($vElev) $vElev .= '; ';
				$recMap['verbatimelevation'] = $vElev.$elevStr;
			}
		}
		//Concatenate collectorfamilyname and collectorinitials into recordedby
		if(isset($recMap['collectorfamilyname']) && $recMap['collectorfamilyname'] && (!isset($recMap['recordedby']) || !$recMap['recordedby'])){
			$recordedBy = $recMap['collectorfamilyname'];
			if(isset($recMap['collectorinitials']) && $recMap['collectorinitials']) $recordedBy .= ', '.$recMap['collectorinitials'];
			$recMap['recordedby'] = $recordedBy;
			//Need to add code that maps to collector table
		
		}
		
		if(array_key_exists("specificepithet",$recMap)){
			if($recMap["specificepithet"] == 'sp.' || $recMap["specificepithet"] == 'sp') $recMap["specificepithet"] = '';
		}
		if(array_key_exists("taxonrank",$recMap)){
			$tr = strtolower($recMap["taxonrank"]);
			if($tr == 'species' || !$recMap["specificepithet"]) $recMap["taxonrank"] = '';
			if($tr == 'subspecies') $recMap["taxonrank"] = 'subsp.';
			if($tr == 'variety') $recMap["taxonrank"] = 'var.';
			if($tr == 'forma') $recMap["taxonrank"] = 'f.';
		}
		
		//Populate sciname if null
		if(array_key_exists('sciname',$recMap) && $recMap['sciname']){
			if(substr($recMap['sciname'],-4) == ' sp.') $recMap['sciname'] = substr($recMap['sciname'],0,-4);
			if(substr($recMap['sciname'],-3) == ' sp') $recMap['sciname'] = substr($recMap['sciname'],0,-3);
		
			$recMap['sciname'] = str_replace(array(' ssp. ',' ssp '),' subsp. ',$recMap['sciname']);
			$recMap['sciname'] = str_replace(' var ',' var. ',$recMap['sciname']);
		
			$pattern = '/\b(cf\.|cf|aff\.|aff)\s{1}/';
			if(preg_match($pattern,$recMap['sciname'],$m)){
				$recMap['identificationqualifier'] = $m[1];
				$recMap['sciname'] = preg_replace($pattern,'',$recMap['sciname']);
			}
		}
		else{
			if(array_key_exists("genus",$recMap)){
				//Build sciname from individual units supplied by source
				$sciName = $recMap["genus"];
				if(array_key_exists("specificepithet",$recMap)) $sciName .= " ".$recMap["specificepithet"];
				if(array_key_exists("taxonrank",$recMap)) $sciName .= " ".$recMap["taxonrank"];
				if(array_key_exists("infraspecificepithet",$recMap)) $sciName .= " ".$recMap["infraspecificepithet"];
				$recMap['sciname'] = trim($sciName);
			}
			elseif(array_key_exists('scientificname',$recMap)){
				//Clean and parse scientific name
				$parsedArr = OccurrenceUtilities::parseScientificName($recMap['scientificname']);
				$scinameStr = '';
				if(array_key_exists('unitname1',$parsedArr)){
					$scinameStr = $parsedArr['unitname1'];
					if(!array_key_exists('genus',$recMap) || $recMap['genus']){
						$recMap['genus'] = $parsedArr['unitname1'];
					}
				}
				if(array_key_exists('unitname2',$parsedArr)){
					$scinameStr .= ' '.$parsedArr['unitname2'];
					if(!array_key_exists('specificepithet',$recMap) || !$recMap['specificepithet']){
						$recMap['specificepithet'] = $parsedArr['unitname2'];
					}
				}
				if(array_key_exists('unitind3',$parsedArr)){
					$scinameStr .= ' '.$parsedArr['unitind3'];
					if((!array_key_exists('taxonrank',$recMap) || !$recMap['taxonrank'])){
						$recMap['taxonrank'] = $parsedArr['unitind3'];
					}
				}
				if(array_key_exists('unitname3',$parsedArr)){
					$scinameStr .= ' '.$parsedArr['unitname3'];
					if(!array_key_exists('infraspecificepithet',$recMap) || !$recMap['infraspecificepithet']){
						$recMap['infraspecificepithet'] = $parsedArr['unitname3'];
					}
				}
				if(array_key_exists('author',$parsedArr)){
					if(!array_key_exists('scientificnameauthorship',$recMap) || !$recMap['scientificnameauthorship']){
						$recMap['scientificnameauthorship'] = $parsedArr['author'];
					}
				}
				$recMap['sciname'] = trim($scinameStr);
			}
		}
		return $recMap;
	}
}
?>