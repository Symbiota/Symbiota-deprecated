<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/GPoint.php');

class OccurrenceUtilities {

	private $conn;
	
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
		if($inStr || is_string($inStr)){
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
					elseif((strpos($sciNameArr[0],'.') !== false) || (strpos($sciNameArr[0],'(') !== false)){
						//It is assumed that Author has been reached, thus stop process 
						unset($sciNameArr);
					}
					else{
						//Specific Epithet
						$retArr['unitname2'] = array_shift($sciNameArr);
					}
					if($retArr['unitname2'] && preg_match('/[A-Z]+/',$retArr['unitname2'])){
						if(preg_match('/[A-Z]{1}[a-z]+/',$retArr['unitname2'])){
							//Check to see if is term is genus author
							$sql = 'SELECT tid FROM taxa WHERE unitname1 = "'.$retArr['unitname1'].'" AND unitname2 = "'.$retArr['unitname2'].'"';
							$con = MySQLiConnectionFactory::getCon('readonly');
							$rs = $con->query($sql);
							if(!$rs->num_rows){
								//Second word is likely author, thus assume assume author has been reach and stop process
								$retArr['unitname2'] = '';
								unset($sciNameArr);
							}
							$rs->close();
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
								}
								else{
									$retArr['unitname3'] = $nextStr;
								}
								$retArr['author'] = implode(' ',$sciNameArr);
							}
						}
						elseif($sciStr == 'var.' || $sciStr == 'var'){
							if($sciNameArr){
								$retArr['unitind3'] = 'var.';
								$nextStr = array_shift($sciNameArr);
								if($nextStr == 'ssp.' || $nextStr == 'subsp.'){
									$retArr['unitind3'] = $nextStr;
									$retArr['unitname3'] = array_shift($sciNameArr);
								}
								else{
									$retArr['unitname3'] = $nextStr;
								}
								$retArr['author'] = implode(' ',$sciNameArr);
							}
						}
						elseif($sciStr == 'ssp.' || $sciStr == 'ssp' || $sciStr == 'subsp.' || $sciStr == 'subsp'){
							if($sciNameArr){
								$retArr['unitind3'] = 'subsp.';
								$retArr['unitname3'] = array_shift($sciNameArr);
								$retArr['author'] = implode(' ',$sciNameArr);
							}
						}
					}
					//Double check to see if infraSpecificEpithet is still embedded in author due initial lack of taxonRank indicator
					if(!$retArr['unitname3'] && $retArr['author']){
						$arr = explode(' ',$retArr['author']);
						$firstWord = array_shift($arr);
						if($firstWord){
							$sql = 'SELECT unitind3 FROM taxa '.
								'WHERE unitname1 = "'.$retArr['unitname1'].'" AND unitname2 = "'.$retArr['unitname2'].'" AND unitname3 = "'.$firstWord.'" ';
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
			elseif(preg_match('/(\d{1,2})\D{1,3}\s{0,2}(\d{1,2}\.{0,1}\d*)[\'m]{1}(.*)/i',$inStr,$m)){
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
				if(preg_match('/^(\d{1,2}\.{0,1}\d*)["s]{1}(.*)/i',$leftOver,$m)){
					$latSec = $m[1];
					if(count($m)>2){
						$leftOver = trim($m[2]);
					}
				}
				//Grab lng deg and min
				if(preg_match('/(\d{1,3})\D{1,3}\s{0,2}(\d{1,2}\.{0,1}\d*)[\'m]{1}(.*)/i',$leftOver,$m)){
					$lngDeg = $m[1];
					$lngMin = $m[2];
					$leftOver = trim($m[3]);
					//Grab lng sec
					if(preg_match('/^(\d{1,2}\.{0,1}\d*)["s]{1}(.*)/i',$leftOver,$m)){
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
			if(preg_match('/\D*(\d{1,2}\D{0,1})\s*(\d{6,7})E\s*(\d{7})N/i',$inStr,$m)){
				$z = $m[1];
				$e = $m[2];
				$n = $m[3];
				if($n && $e && $z){
					$llArr = OccurrenceUtilities::convertUtmToLL($e,$n,$z,$d);
					if(isset($llArr['lat'])) $recMap['lat'] = $llArr['lat'];
					if(isset($llArr['lng'])) $recMap['lng'] = $llArr['lng'];
				}
				
			}
			elseif(preg_match('/UTM/',$inStr) || preg_match('/\d{1,2}[\D\s]+\d{6,7}[\D\s]+\d{6,7}/',$inStr)){
				//UTM
				$z = ''; $e = ''; $n = '';
				if(preg_match('/[\s\D]*(\d{1,2}\D{0,1})[\s\D]*/',$inStr,$m)) $z = $m[1];
				if($z){
					if(preg_match('/(\d{6,7})E{1}[\D\s]+(\d{7})N{1}/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/E{1}(\d{6,7})[\D\s]+N{1}(\d{7})/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/(\d{7})N{1}[\D\s]+(\d{6,7})E{1}/i',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					} 
					elseif(preg_match('/N{1}(\d{7})[\D\s]+E{1}(\d{6,7})/i',$inStr,$m)){
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
						if(isset($llArr['lat'])) $recMap['lat'] = $llArr['lat'];
						if(isset($llArr['lng'])) $recMap['lng'] = $llArr['lng'];
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
	public function buildAssociatedTaxaIndex($collid = 0){
		set_time_limit(900);
		echo '<ul>';
		echo '<li>Starting to parse associated species text blocks </li>';
		ob_flush();
		flush();
		$sql = 'SELECT o.occid, o.associatedtaxa '.
			'FROM omoccurrences o LEFT JOIN omoccurassoctaxa a ON o.occid = a.occid '.
			'WHERE o.associatedtaxa IS NOT NULL AND a.occid IS NULL ';
		if($collid && is_numeric($collid)){
			$sql .= 'AND o.collid = '.$collid;
		}
		//$sql .= ' AND o.tidinterpreted = 4058 ';
		//$sql .= ' LIMIT 100';
		$rs = $this->conn->query($sql);
		echo '<li>Parsing new associated species text blocks (target count: '.$rs->num_rows.')... ';
		ob_flush();
		flush();
		while($r = $rs->fetch_object()){
			$assocArr = $this->parseAssocSpecies($r->associatedtaxa,$r->occid);
		}
		$rs->free();
		echo 'Done!</li>';
		
		//Populate tid field using taxa table
		echo '<li>Populate tid field using taxa table... ';
		ob_flush();
		flush();
		$sql2 = 'UPDATE omoccurassoctaxa a INNER JOIN taxa t ON a.verbatimstr = t.sciname '.
			'SET a.tid = t.tid '.
			'WHERE a.tid IS NULL';
		if(!$this->conn->query($sql2)){
			echo '<li style="margin-left:10px;">Unable to populate tid field using taxa table: '.$this->conn->error.'</li>';
			echo '<li style="margin-left:10px;">'.$sql2.'</li>';
		}
		echo 'Done!</li>';

		//Populate tid field using taxavernaculars table
		echo '<li>Populate tid field using taxavernaculars table... ';
		ob_flush();
		flush();
		$sql3 = 'UPDATE omoccurassoctaxa a INNER JOIN taxavernaculars v ON a.verbatimstr = v.vernacularname '.
			'SET a.tid = v.tid '.
			'WHERE a.tid IS NULL ';
		if(!$this->conn->query($sql3)){
			echo '<li style="margin-left:10px;">Unable to populate tid field using taxavernaculars table: '.$this->conn->error.'</li>';
			echo '<li style="margin-left:10px;">'.$sql3.'</li>';
		}
		echo 'Done!</li>';
		
		//Populate tid field by linking back to omoccurassoctaxa table
		//This assumes that tids are correct; in future verificationscore field can be used to select only those that have been verified
		echo '<li>Populate tid field by linking back to omoccurassoctaxa table... ';
		ob_flush();
		flush();
		$sql4 = 'UPDATE omoccurassoctaxa a INNER JOIN omoccurassoctaxa a2 ON a.verbatimstr = a2.verbatimstr '.
			'SET a.tid = a2.tid '.
			'WHERE a.tid IS NULL AND a2.tid IS NOT NULL ';
		if(!$this->conn->query($sql4)){
			echo '<li style="margin-left:10px;">Unable to populate tid field relinking back to omoccurassoctaxa table: '.$this->conn->error.'</li>';
			echo '<li style="margin-left:10px;">'.$sql4.'</li>';
		}
		echo 'Done!</li>';
		
		//Lets get the harder ones
		echo '<li>Mining database for the more difficult matches... ';
		ob_flush();
		flush();
		$sql5 = 'SELECT DISTINCT verbatimstr '.
			'FROM omoccurassoctaxa '.
			'WHERE tid IS NULL ';
		$rs5 = $this->conn->query($sql5);
		while($r5 = $rs5->fetch_object()){
			$verbStr = $r5->verbatimstr;
			$tid = $this->mineAssocSpeciesMatch($verbStr);
			if($tid){
				$sql5b = 'UPDATE omoccurassoctaxa '.
					'SET tid = '.$tid.' '.
					'WHERE tid IS NULL AND verbatimstr = "'.$verbStr.'"';
				if(!$this->conn->query($sql5b)){
					echo '<li style="margin-left:10px;">Unable to populate NULL tid field: '.$this->conn->error.'</li>';
					echo '<li style="margin-left:10px;">'.$sql5b.'</li>';
				}
			}
		}
		$rs5->free();
		echo 'Done!</li>';
		
		echo '<li>DONE!</li>';
		echo '</ul>';
		ob_flush();
		flush();
	}
	
	public function parseAssocSpecies($assocSpeciesStr,$occid){
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
			$sql = 'INSERT INTO omoccurassoctaxa(occid, verbatimstr) VALUES';
			foreach($assocArr as $aStr){
				$sql .= '('.$occid.',"'.$this->conn->real_escape_string($aStr).'"), ';
			}
			$sql = trim($sql,', ');
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				echo '<li style="margin-left:10px;">ERROR: unable to database assocaited values: '.$this->conn->error.'</li>';
				echo '<li style="margin-left:10px;">SQL: '.$sql.'</li>';
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
}
?>