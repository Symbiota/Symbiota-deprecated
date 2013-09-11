<?php
include_once($serverRoot.'/classes/SpecProcNlp.php');

class SpecProcNlpParserLBCC extends SpecProcNlp{

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	//Parsing functions
	public function parse($rawStr) {
		$results = array();
		$rawStr = trim($this->fixString(str_replace("\t", " ", $rawStr)));
		//If OCR source is from tesseract (utf-8 is default), convert to a latin1 character set
		//if(mb_detect_encoding($rawStr,'UTF-8,ISO-8859-1') == "UTF-8"){
		//	$rawStr = utf8_decode($rawStr);
		//}
		$politicalConfigInfo = array();
		if(strlen($rawStr) > 0 && !$this->isMostlyGarbage2($rawStr, 0.50)) {
			$politicalConfigInfo = $this->getPoliticalConfigInfo($rawStr);
			if($politicalConfigInfo) {
				$event_date = "";
				$det_date = "";
				if(array_key_exists('eventDate', $politicalConfigInfo)) {
					$event_date = $this->formatDate($politicalConfigInfo['eventDate']);
					unset($politicalConfigInfo['eventDate']);
				}
				if(array_key_exists('dateIdentified', $politicalConfigInfo)) {
					$det_date = $this->formatDate($politicalConfigInfo['dateIdentified']);
					unset($politicalConfigInfo['dateIdentified']);
				}
				if(strlen($event_date) == 0) {
					$months = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))?|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
					$dates = $this->getDates($rawStr, $months);
					$event_date = $this->formatDate($this->min_date($dates));
					if(strlen($det_date) == 0 && count($dates) > 1) $det_date = $this->formatDate($this->max_date($dates));
				}
				$results['eventDate'] = $event_date;
				$results['dateIdentified'] = $det_date;
				$verbatimCoordinates = "";
				if(array_key_exists('verbatimCoordinates', $politicalConfigInfo)) $results['verbatimCoordinates'] = $politicalConfigInfo['verbatimCoordinates'];
				else $results['verbatimCoordinates'] = $this->getVerbatimCoordinates($rawStr);
				$latLongs = $this->getLatLongs($rawStr);
				$cntLongLength = count($latLongs);
				$lat = "";
				$long = "";
				if($cntLongLength > 0) {
					$latLong = $latLongs[$cntLongLength-1];
					if(array_key_exists('latitude', $latLong)) {
						$lat = $latLong['latitude'];
						$results['decimalLatitude'] = round($this->getDigitalLatLong($lat), 5);
					}
					if(array_key_exists('longitude', $latLong)) {
						$long = $latLong['longitude'];
						$results['decimalLongitude'] = round($this->getDigitalLatLong($long), 5);
					}
				}
				$recordedBy = "";
				$recordedById = "";
				$recordNumber = "";
				$otherCatalogNumbers = '';
				$identifiedBy = '';
				if(array_key_exists('recordedBy', $politicalConfigInfo)) $recordedBy = $politicalConfigInfo['recordedBy'];
				if(array_key_exists('recordedById', $politicalConfigInfo)) $recordedById = $politicalConfigInfo['recordedById'];
				if(array_key_exists('recordNumber', $politicalConfigInfo)) $recordNumber = $politicalConfigInfo['recordNumber'];
				if(array_key_exists('otherCatalogNumbers', $politicalConfigInfo)) $otherCatalogNumbers = $politicalConfigInfo['otherCatalogNumbers'];
				if(array_key_exists('identifiedBy', $politicalConfigInfo)) $identifiedBy = $politicalConfigInfo['identifiedBy'];
				//echo "\nline 72, recordedBy: ".$recordedBy."\nrecordNumber: ".$recordNumber."\nidentifiedBy: ".$identifiedBy."\notherCatalogNumbers: ".$otherCatalogNumbers."\n";
				if(strlen($recordedBy) == 0) {
					$collectorInfo = $this->getCollector($rawStr);
					if($collectorInfo && count($collectorInfo) > 0) {
						if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
						if(strlen($recordedBy) == 0) {
							if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = $collectorInfo['collectorName'];
						}
						if(strlen($recordNumber) == 0) {
							if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
						}
						if(strlen($identifiedBy) == 0) {
							if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
						}
						if(strlen($otherCatalogNumbers) == 0) {
							if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
						} else if(array_key_exists('otherCatalogNumbers', $collectorInfo)) {
							$temp = $collectorInfo['otherCatalogNumbers'];
							if(strlen($temp) > 0) $otherCatalogNumbers .= "; ".$collectorInfo['otherCatalogNumbers'];
						}
					}
				}
				$results['identifiedBy'] = $identifiedBy;
				$results['otherCatalogNumbers'] = $otherCatalogNumbers;
				$results['recordedBy'] = $recordedBy;
				$results['recordedById'] = $recordedById;
				$results['recordNumber'] = $recordNumber;
			}
		}
		return $this->combineArrays($politicalConfigInfo, $results);
	}

	private function getDigitalLatLong($latlong) {
		$degPos = strpos($latlong, "°");
		if($degPos !== FALSE) {
			$digDegs = 0;
			$digMins = 0;
			$digSecs = 0;
			$minPos = strpos($latlong, "'");
			if($minPos !== FALSE) {
				$secPos = strpos($latlong, "\"");
				if($secPos !== FALSE) {
					$secs = trim(substr($latlong, $minPos+1, $secPos-$minPos-1));
					$digSecs = $secs/3600;
					$direction = trim(substr($latlong, $secPos+1));
				} else $direction = trim(substr($latlong, $minPos+1));
				$mins = trim(substr($latlong, $degPos+1, $minPos-$degPos-1));
				$digMins = $mins/60;
			} else $direction = trim(substr($latlong, $degPos+1));
			$degs = trim(substr($latlong, 0, $degPos));
			if(strcasecmp(substr($direction, 0, 1), 'W') == 0 || strcasecmp(substr($direction, 0, 1), 'S') == 0) return -1*($degs + $digMins + $digSecs);
			else return $degs + $digMins + $digSecs;
		}
		return null;
	}

	private function formatDate($date) {
		if(array_key_exists('year', $date)) {
			$result = $date['year'];
			if(array_key_exists('month', $date)) {
				$result .= "-".$date['month'];
				if(array_key_exists('day', $date)) $result .= "-".$date['day'];
			}
			return $result;
		}
		return "";
	}

	private function getStatesFromCountry($c) {
		$cResult = array();
		if($c) {
			$sql = "SELECT sp.stateName ".
				"FROM lkupstateprovince sp INNER JOIN lkupcountry c ".
				"ON (sp.countryID = c.countryID) ".
				"WHERE c.countryName = '".str_replace(array("\"", "'"), "", $c)."'";
			if($r2s = $this->conn->query($sql)) {
				while($r2 = $r2s->fetch_object()) array_push($cResult, $r2->stateName);
			}
		}
		return $cResult;
	}

	private function getCounties($state) {
		$cResult = array();
		if($state) {
			$sql = "SELECT c.countyName ".
				"FROM lkupcounty c INNER JOIN lkupstateprovince sp ".
				"ON (c.stateid = sp.stateid) ".
				"WHERE sp.stateName = '".str_replace(array("\"", "'"), "", $state)."'";
			if($r2s = $this->conn->query($sql)) {
				while($r2 = $r2s->fetch_object()) array_push($cResult, ucwords($r2->countyName));
			}
		}
		return $cResult;
	}

	private function getStateFromCounty($c, $ss=null, $country="") {
		if($c) {
			//$c = "Jackson";
			$sql = "SELECT cr.countryName, sp.stateName FROM (lkupstateprovince sp ".
				"INNER JOIN lkupcountry cr ON (cr.countryid = sp.countryid)) ".
				"INNER JOIN lkupcounty c ".
				"ON (sp.stateid = c.stateid) WHERE c.countyName = '".str_replace(array("\"", "'"), "", $c)."'";
			if(strlen($country) > 0) $sql .= " AND cr.cr.countryName = '".str_replace(array("\"", "'"), "", $country)."'";
			//echo "\n\nSQL: ".$sql."\n\n";
			if($r2s = $this->conn->query($sql)) {
				$num_rows = $r2s->num_rows;
				if($num_rows > 0) {
					if($num_rows == 1) if($r2 = $r2s->fetch_object()) return array($r2->stateName, $r2->countryName);
					else {
						$shortest = 0;
						$closest = '';
						if(count($ss) > 0) {
							while($r2 = $r2s->fetch_object()){
								foreach($ss as $s) {
									$lev = levenshtein($s, $r2->stateName);
									//echo "\nlevenshtein: ".$lev.", state: ".$s."\n";
									if($lev == 0) return $s;
									else if ($lev <= $shortest || $shortest == 0) {
										$closest  = array($s, $r2->countryName);
										$shortest = $lev;
									}
								}
							}
							return $closest;
						}
					}
				}
			}
		}
		return array();
	}

	private function getCountryFromState($s) {
		if($s) {
			//$c = "Jackson";
			$sql = "SELECT c.countryName FROM lkupstateprovince sp INNER JOIN lkupcountry c ".
				"ON (sp.countryid = c.countryid) WHERE sp.stateName = '".str_replace(array("\"", "'"), "", $s)."'";
			//echo "\n\nSQL: ".$sql."\n\n";
			if($r2s = $this->conn->query($sql)) {
				$num_rows = $r2s->num_rows;
				if($num_rows == 1 && $r2 = $r2s->fetch_object()) return $r2->countryName;
			}
		}
		return '';
	}

	private function getPolticalInfoFromCounty($c, $ss=null) {
		if($c) {
			$result = array();
			$pos = strripos($c, " ");
			if($pos !== FALSE) {
				$potentialCounty = trim(substr($c, $pos), " ,.:;");//the county is probably the string after the last space
				$ps = $this->getStateFromCounty($potentialCounty, $ss);
				if(count($ps) > 0) {
					$result['county'] = $potentialCounty;
					$result['state'] = $ps[0];
					$result['country'] = $ps[1];
				} else {
					$c = trim(substr($c, 0, $pos), " ,.:;");
					$pos = strripos($c, " ");
					if($pos !== FALSE) {
						$potentialCounty2 = trim(substr($c, $pos), " ,:;")." ".$potentialCounty;//the county may be the string after the second from last space
						$ps = $this->getStateFromCounty($potentialCounty2, $ss);
						if(count($ps) > 0) {
							$result['county'] = $potentialCounty2;
							$result['state'] = $ps[0];
							$result['country'] = $ps[1];
						}
					} else {
						$c = trim(substr($c, 0, $pos), " ,.:;");
						$pos = strripos($c, " ");
						if($pos !== FALSE) {
							$potentialCounty2 = trim(substr($c, $pos), " ,:;")." ".$potentialCounty;//the county may be the string after the third from last space
							$ps = $this->getStateFromCounty($potentialCounty2, $ss);
							if(count($ps) > 0) {
								$result['county'] = $potentialCounty2;
								$result['state'] = $ps[0];
								$result['country'] = $ps[1];
							} else $result['county'] = $potentialCounty;//assume the county is the string after the last space
						}
					}
				}
				return $result;
			} else {
				$result['county'] = trim($c, " ,.:;");
				$ps = $this->getStateFromCounty($c, $ss);
				if(count($ps) > 0) {
					$result['state'] = $ps[0];
					$result['country'] = $ps[1];
					return $result;
				}
				return $result;
			}
		}
		return null;
	}

	private function isMostlyGarbage2($s, $cutoff) {
		if($s) {
			if(stripos($s, "Failed OCR return") !== FALSE) return true;
			$total = 0;
			$good = 0;
			foreach (count_chars($s, 1) as $i => $val) {
				$total++;
				if(($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 32) $good++;
			}
			if(round($good/$total, 2) < $cutoff) return true;
			else return false;
		}
		return false;
	}

	private function isMostlyGarbage($s, $cutoff) {
		if($s) {
			$total = 0;
			$good = 0;
			foreach (count_chars($s, 1) as $i => $val) {
				$total++;
				if(($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123) || $i == 32) $good++;
			}
			if(round($good/$total, 2) < $cutoff) return true;
			else return false;
		}
		return false;
	}

	private function isCompleteGarbage($s) {
		if($s) {
			foreach (count_chars($s, 1) as $i => $val) {
				if(($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123)) return false;
			}
		}
		return true;
	}

	private function isStateOrProvince($sp) {
		if($sp) {
			$sql = "SELECT * FROM lkupstateprovince lusp WHERE lusp.stateName = '".str_replace(array("\"", "'"), "", $sp)."'";
			if($r2s = $this->conn->query($sql)){
				if($r2 = $r2s->fetch_object()) return true;
			}
		}
		return false;
	}

	private function isCountryInDatabase($c) {//echo "\nInput to isCountryInDatabase: ".$c."\n";
		if($c) {
			$sql = "SELECT * FROM lkupcountry luc WHERE luc.countryName = '".str_replace(array("\"", "'"), "", $c)."'";
			if($r2s = $this->conn->query($sql)){
				if($r2 = $r2s->fetch_object()) return true;
			}
		}
		return false;
	}

	private function processCollectorName($lastName, $name) {
		$firstPart = trim(substr($name, 0, strpos($name, $lastName)));
		if(preg_match("/.*\\b(?:Dr|Rev)\\.?(.+)/i", $firstPart, $mats)) $firstPart = trim($mats[1]);
		if(substr_count($firstPart, " ") > 1) {
			$words = array_reverse(explode(" ", $firstPart));
			$r = "";
			$index = 0;
			foreach($words as $word) {
				if($index++ < 3 && $this->isText($word)) $r = trim($word)." ".$r;
				else break;
			}
			$firstPart = $r;
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z])(?:[-_*.]\\s|\\s|[-_*.])([a-zA-Z])\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Initial, Middle Initial
		if(preg_match($fNamePat, $firstPart, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 518, mats[".$i++."] = ".$mat."\n";
			return array('fName' => trim($mats[1]), 'mName' => trim($mats[2]));
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z]{2,}+)(?:[-_*.]\\s|\\s|[-_*.])([a-zA-Z])\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Name, Middle Initial
		if(preg_match($fNamePat, $firstPart, $mats)) {
			return array('fName' => trim($mats[1]), 'mName' => trim($mats[2]));
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z]{2,}+)(?:[-_*.]\\s|\\s|[-_*.])([a-zA-Z]{2,}+)\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Name, Middle Name
		if(preg_match($fNamePat, $firstPart, $mats)) {
			return array('fName' => trim($mats[1]), 'mName' => trim($mats[2]));
		}
		$fNamePat = "/(?:.*)\\b([a-zA-Z]+)\\b(?:[-_*.]\\s|\\s|[-_*.])?/i";//First Name only
		if(preg_match($fNamePat, $firstPart, $mats)) {
			return array('fName' => trim($mats[1]), 'mName' => "");
		}
	}

	private function processCollectorQueryResult($r2, $lName, $fName, $mName) {//echo "\nInput to processCollectorQueryResult:\nlName: ".$lName."\nfName: ".$fName."\nmName: ".$mName,"\n";
		$firstName = $r2->firstName;
		$middleInitial = $r2->middleInitial;
		if(strlen($fName) == 1 && strlen($firstName) > 1) $firstName = substr($firstName, 0, 1);
		if(strlen($mName) == 1 && strlen($middleInitial) > 1) $middleInitial = substr($middleInitial, 0, 1);
		if(STRLEN($firstName) > 0 && strcmp($firstName, $fName) == 0) {
			if(STRLEN($middleInitial) > 0 && STRLEN($mName) > 0) {
				if(strcasecmp($middleInitial, $mName) == 0) {
					return array
					(
						'collectorID' => $r2->recordedById,
						'familyName' => $lName,
						'collectorName' => $r2->firstName." ".$middleInitial." ".$lName
					);
				}
			} else {
				return array
				(
					'collectorID' => $r2->recordedById,
					'familyName' => $lName,
					'collectorName' => $r2->firstName." ".$lName
				);
			}
		}
		return array();
	}

	private function isPossibleSciName($name) {//echo "\nInput to isPossibleSciName: ".$name."\n";
		$name = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $name), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
		$numPat = "/(.*)\\s\\w\\s(.*)/";
		if(preg_match($numPat, $name, $ns)) $name = trim($ns[1])." ".trim($ns[2]);
		$fPos = strpos($name, "¢");
		if($fPos !== FALSE && $fPos < 9) $name = trim(substr($name, $fPos+1));
		//echo "\nInput to isPossibleSciName2: ".$name."\n";
		if(strlen($name) > 2 && !preg_match("/\\b(?:on|var\\.?|strain|subsp\\.?|ssp\\.?|f\\.)\\b/i", $name)) {
			$name = trim(str_replace(array("\"", "'"), "", $name));
			$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name."'";
			if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
			$pos = strpos($name, " ");
			if($pos !== FALSE) {
				$words = explode(" ", $name);
				$word0 = str_replace(array("\"", "'"), "", trim($words[0]));
				$word1 = str_replace(array("\"", "'"), "", trim($words[1]));
				if(count($words) == 2 || (count($words) == 3 && strlen($words[2]) < 9)) {
					$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$word0."'";
					if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
					if(strlen($word1) > 3 && strcasecmp($word1, "florida") != 0 && strcasecmp($word1, "americani") != 0
						&& strcasecmp($word1, "clara") != 0 && strcasecmp($word1, "barbara") != 0) {
						$sql = "SELECT * FROM taxa t WHERE t.unitName2 = '".$word1."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
					}
				}
				if(count($words) > 3 && (strlen($word0) < 4 || strlen($word1) < 4 || strlen($words[2]) < 4)) {
					$name2 = str_replace(array("\"", "'"), "", $word0.trim($word1).trim($words[2]));
					$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
					if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
				}
				if(count($words) < 6) {
					$name2 = trim(str_replace(array("1", "!", "|", "5", "0", "\"", "'"), array("l", "l", "l", "S", "O", "", ""), $word0." ".trim($word1)));
					$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
					//echo "\nline 1078, sql: ".$sql."\n";
					if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
				}
			}
			if(preg_match("/^\\w{3,24}\\s\\w{3,24}\\s\([a-zA-Z01üé&. ]{1,14}\\.?\\s?\)\\s?(?:[A-Z]\\.\\s){0,2}[a-zA-Z01ü&. ]{2,19}\\.?$/", $name)) {
				//echo "\nline 1083, match\n";
				return true;
			}
			if(preg_match("/^[a-zA-Z01]{3,24}\\sspp?\\./", $name)) return true;
		}
		return false;
	}

	private function getCollectorFromDatabase($name, $string) {
		if(strlen($name) > 2) {//echo "\nInput to getCollectorFromDatabase, Name: ".$name.", String: ".$string."\n";
			$sql = "SELECT c.recordedById, c.familyName, c.firstName, c.middleInitial ".
				"FROM omcollectors c ".
				"WHERE c.familyName = '".str_replace(array("\"", "'"), "", $name)."'";// AND c.recordedById != 8024";

			if($r2s = $this->conn->query($sql)) {
				$firstName = "";
				$middleName = "";
				$pName = $this->processCollectorName($name, $string);
				if(count($pName) > 0) {
					if(array_key_exists('fName', $pName)) $firstName = $pName['fName'];
					if(array_key_exists('mName', $pName)) $middleName = $pName['mName'];
				}
				while($r2 = $r2s->fetch_object()){
					$result = $this->processCollectorQueryResult($r2, $name, $firstName, $middleName);
					if(count($result) > 0) return $result;
				}
				//failed to match whole first and middle names.  Try to match first and middle initials
				if(strlen($firstName) > 1) {
					$firstName = substr($firstName, 0, 1);
					if(strlen($middleName) > 1) $middleName = substr($middleName, 0, 1);
					if($r2s = $this->conn->query($sql)) {
						while($r2 = $r2s->fetch_object()){
							$result = $this->processCollectorQueryResult($r2, $name, $firstName, $middleName);
							if(count($result) > 0) return $result;
						}
					}
				}
			}
		}
		return array();
	}

	private function findTokenAtEnd($string, $token) {//echo "\nInput to findTokenAtEnd, string: ".$string.", token: ".$token."\n";
		if(strcasecmp($token, "Park") == 0) return '';
		$spacePos = strrpos($string, " ");
		$endpart = "";
		if($spacePos !== FALSE) {
			$endpart = substr($string, $spacePos+1);
			//echo "\n\nEndPart: ".$endpart.", Token: ".strtoupper($token)."\n\n";
			$rest = trim(substr($string, 0, $spacePos));
			if(strcasecmp($endpart, $token) == 0) return $token;
			else {
				$spacePos = strrpos($rest, " ");
				if($spacePos !== FALSE) {
					$nextToEndPart = trim(substr($rest, $spacePos+1));
					$nextToNextToEndPart = trim(substr($rest, 0, $spacePos));
					$endpart = $nextToEndPart." ".$endpart;
					if(strcasecmp($endpart, $token) == 0) return $token;
					$spacePos = strrpos($nextToNextToEndPart, " ");
					if($spacePos !== FALSE) {
						if(strcasecmp(trim(substr($nextToNextToEndPart, $spacePos+1))." ".$endpart, $token) == 0) return $token;
					}
				} else {
					$endpart = $rest." ".$endpart;
					if(strcasecmp($endpart, $token) == 0) return $token;
				}
			}
		} else if(strcasecmp($string, $token) == 0) return $token;
		return '';
	}

	private function getCounty($c) {
		if($c) {
			if(strlen($c) > 2) {
				$result = array();
				$c = trim($c, " \t\n\r\0\x0B,.:;!()\"\'\\~@#$%^&*_-");
				//echo "\nLine 724, Input to getCounty: ".$c."\n";
				//return null;
				$sql = "select lk2.stateName, lk3.countryName from lkupcounty lk1 INNER JOIN ".
						"(lkupstateprovince lk2 inner join lkupcountry lk3 on lk2.countryid = lk3.countryid) ".
						"on lk1.stateid = lk2.stateid ".
						"where lk1.countyName = '".str_replace(array("\"", "'"), "", $c)."'";
				if($r2s = $this->conn->query($sql)) {
					$num_rows = $r2s->num_rows;
					if($num_rows > 0) {
						while($r2 = $r2s->fetch_object()) {
							array_push($result, array('county' => ucwords(strtolower($c)), 'stateProvince' => $r2->stateName, 'country' => $r2->countryName));
						}
						return $result;
					} else {
						$c = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5', '2', '0', 'ST.', 'St.', '[', ']', '(', ')', '"', '\''),
									array('l', 'l', 'l', 'S', 'Z', 'O', 'Saint', 'Saint', '', '', '', '', '', ''),
									$c
								)
							)
						);
						$sql = "select lk2.stateName, lk3.countryName from lkupcounty lk1 INNER JOIN ".
							"(lkupstateprovince lk2 inner join lkupcountry lk3 on lk2.countryid = lk3.countryid) ".
							"on lk1.stateid = lk2.stateid ".
							"where lk1.countyName = '".$c."'";
						if($r3s = $this->conn->query($sql)) {
							while($r3 = $r3s->fetch_object()) {
								array_push($result, array('county' => $c, 'stateProvince' => $r3->stateName, 'country' => $r3->countryName));
							}
							return $result;
						}
					}
				}
			}
		}
	}

	private function processCountyMatches($firstPart, $middlePart, $lastPart) {//echo "\nLine 1290, Input to processCountyMatches, firstPart: ".$firstPart."\nmiddlePart: ".$middlePart."\nlastPart: ".$lastPart."\n";
		$countyArray = null;
		$possibleState = "";
		$county = "";
		$possibleCountry = "";
		if(strpos($lastPart, "\n") !== FALSE) {
			$nextWords = explode("\n", $lastPart);
			$nextWord = trim($nextWords[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		} else $nextWord = trim($lastPart, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");

		if(strpos($firstPart, "\n")) {
			$prevLines = array_reverse(explode("\n", $firstPart));
			$num = 0;
			if(strlen($middlePart) > 0) {
				$countyArray = $this->processCountyString($middlePart, $nextWord);
				if($countyArray != null && count($countyArray) > 0) {
					if(count($countyArray) == 1) return $countyArray[0];
					else {
						foreach($countyArray as $vs) {
							$county = $vs['county'];
							if(array_key_exists('stateProvince', $vs)) {
								$possibleState = $vs['stateProvince'];
								foreach($prevLines as $prevLine) {
									if(stripos($prevLine, $possibleState) !== FALSE) return $vs;
									else {
										$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $prevLine." ".$nextWord), $county, $possibleState);
										if($temp) return $vs;
									}
								}
							}
						}
					}
				}
			} else {
				$i=0;
				foreach($prevLines as $prevLine) {
					if($i++ < 2) {
						if($countyArray == null || count($countyArray) == 0) {
							$countyArray = $this->processCountyString($prevLine, $nextWord);
							if($countyArray != null && count($countyArray) > 0) {
								if(count($countyArray) == 1)  return array_pop($countyArray);
								else {
									foreach($countyArray as $vs) {//foreach($vs as $k => $v) echo "\nline 1332, ".$k.": ".$v."\n";
										if(array_key_exists('stateProvince', $vs)) {
											$possibleStateProvince = $vs['stateProvince'];
											if(stripos($prevLine, $possibleStateProvince) !== FALSE) return $vs;
											else {
												$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $prevLine." ".$nextWord), $vs['county'], $possibleStateProvince);
												if($temp) return $vs;
											}
										}
									}
								}
							}
						} else {
							foreach($countyArray as $vs) {
								if(array_key_exists('stateProvince', $vs)) {
									if(stripos($prevLine, $vs['stateProvince']) !== FALSE) return $vs;
									else {
										$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $prevLine." ".$nextWord), $vs['county'], $vs['stateProvince']);
										if($temp) return $vs;
									}
								}
							}
						}
					} else if($countyArray != null && count($countyArray) > 0) {
						foreach($countyArray as $vs) {
							if(array_key_exists('stateProvince', $vs)) {
								if(stripos($prevLine, $vs['stateProvince']) !== FALSE) return $vs;
								else {
									$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $prevLine." ".$nextWord), $vs['county'], $vs['stateProvince']);
									if($temp) return $vs;
								}
							}
						}
					}
				}
			}
			if($countyArray != null && count($countyArray) > 0) {
				$county = $countyArray[0]['county'];
				if(strcasecmp($county, "Park") != 0) return array('county' => $county);//park is so common in the labels it should not be returned unless its state is found
			}
		} else {
			if(strlen($middlePart) > 0) {
				$countyArray = $this->processCountyString($middlePart, $nextWord);
				if($countyArray != null && count($countyArray) > 0) {
					if(count($countyArray) == 1) return $countyArray[0];
					else {
						foreach($countyArray as $vs) {
							if(array_key_exists('stateProvince', $vs)) {
								if(stripos($firstPart, $vs['stateProvince']) !== FALSE) return $vs;
								else {
									$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $firstPart." ".$nextWord), $vs['county'], $vs['stateProvince']);
									if($temp) return $vs;
								}
							}
						}
						$county = $countyArray[0]['county'];
						if(strcasecmp($county, "Park") != 0) return array('county' => $county);//park is so common in the labels it should not be returned unless its state is found
					}
				}
			} else if(strlen($firstPart) > 0) {
				$countyArray = $this->processCountyString($firstPart, $nextWord);
				if($countyArray != null && count($countyArray) > 0) {
					if(count($countyArray) == 1) return $countyArray[0];
					else {
						$county = $countyArray[0]['county'];
						if(strcasecmp($county, "Park") != 0) return array('county' => $county);//park is so common in the labels it should not be returned unless its state is found
					}
				}
			}
		}
		if(strlen($county) == 0) {
			if(strlen($lastPart) > 0) {
				if(strpos($lastPart, "\n") !== FALSE) {
					$nextLines = explode("\n", $lastPart);
					$i=0;
					$num=0;
					$prevLine = "";
					foreach($nextLines as $nextLine) {
						if($i++ == 0) {
							$countyArray = $this->processCountyString($nextLine);
							if($countyArray != null && count($countyArray) > 0) {
								if(count($countyArray) == 1) return $countyArray[0];
								else {
									foreach($countyArray as $vs) {
										$county = $vs['county'];
										if(array_key_exists('stateProvince', $vs)) {
											if(stripos($nextLine, $vs['stateProvince']) !== FALSE) return $vs;
											else {
												$temp = $this->fetchStateAndCountryFromLine(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $nextLine), $vs['county'], $vs['stateProvince']);
												if($temp) return $vs;
											}
										}
									}
								}
							}
						} else if($countyArray != null && count($countyArray) > 0) {//found a county on the first line
							foreach($countyArray as $vs) {
								$county = $vs['county'];
								if(array_key_exists('stateProvince', $vs)) {
									if(strpos($prevLine, $vs['stateProvince']) !== FALSE) {
										$possibleState = $vs['stateProvince'];
										$possibleCountry = $vs['country'];
										$num++;
									}
								}
							}
						}
						$prevLine = $nextLine;
					}
					if($countyArray != null && count($countyArray) > 0) {
						if($num == 1) return array('county' => $county, 'stateProvince' => $possibleState, 'country' => $possibleCountry);
						else {
							$county = $countyArray[0]['county'];
							if(strcasecmp($county, "Park") != 0) return array('county' => $county);//park is so common in the labels it should not be returned unless its state is found
						}
					}
				} else {
					$countyArray = $this->processCountyString($lastPart);
					if($countyArray != null && count($countyArray) > 0) {
						if(count($countyArray) == 1) return $countyArray[0];
						else {
							$county = $countyArray[0]['county'];
							if(strcasecmp($county, "Park") != 0) return array('county' => $county);//park is so common in the labels it should not be returned unless its state is found
						}
					}
				}
			}
		}
	}

	private function fetchStateAndCountryFromLine($line, $county, $state) {//this is to handle states that might be abbreviated
		$spacePos = strpos($line, " ");
		//echo "\nline 1360, line: ".$line.", county: ".$county.", state: ".$state."\n";
		$tStates = $this->getStateOrProvince($state);
		if($tStates) {
			$tState = $tStates[0];
			$possibleStateAndCountry = array();
			if($spacePos !== FALSE) {
				$tokens = array_reverse(explode(" ", $line));
				$tokenCount = count($tokens);
				$numTokens = 0;
				if($tokenCount == 2) {
					$lastToken = trim($tokens[0]);
					$tokenBefore = trim($tokens[1]);
					$possibleStateAndCountry = $this->getStateOrProvince(trim($tokenBefore." ".$lastToken));
					if(count($possibleStateAndCountry) == 0) {
						$possibleStateAndCountry = $this->getStateOrProvince($lastToken);
						if(count($possibleStateAndCountry) == 0) $possibleStateAndCountry = $this->getStateOrProvince($tokenBefore);
					}
					if(count($possibleStateAndCountry) > 0) {
						$temp = $possibleStateAndCountry[0];
						if(strcasecmp($temp, $tState) == 0) {
							return array(
								'county' => $county,
								'stateProvince' => $temp,
								'country' => $possibleStateAndCountry[1]
							);
						}
					}
				} else {
					$tokenCount = 0;
					$prevToken = "";
					$tokenBeforePrevToken = "";
					foreach($tokens as $token) {
						if($tokenCount > 1) {//echo "\nline 1524, word: ".$word.", prevWord: ".$prevWord.", wordBeforePrevWord: ".$wordBeforePrevWord."\n";
							if(strlen($prevToken) > 0) {
								if(strlen($tokenBeforePrevToken) > 0) {
									$temp = trim($token." ".$prevToken." ".$tokenBeforePrevToken);
									$possibleStateAndCountry = $this->getStateOrProvince($temp);
									if(count($possibleStateAndCountry) == 0) {
										$temp = trim($prevToken." ".$tokenBeforePrevToken);
										$possibleStateAndCountry = $this->getStateOrProvince($temp);
										if(count($possibleStateAndCountry) == 0) {
											$temp = trim($tokenBeforePrevToken);
											$possibleStateAndCountry = $this->getStateOrProvince($temp);
											if(count($possibleStateAndCountry) == 0) $possibleStateAndCountry = $this->getStateOrProvince($prevToken);
										}
									}
								} else {
									$temp = trim($token." ".$prevToken);
									$possibleStateAndCountry = $this->getStateOrProvince($temp);
								}
							} else if(strlen($tokenBeforePrevToken) > 0) {
								$temp = trim($token." ".$tokenBeforePrevToken);
								$possibleStateAndCountry = $this->getStateOrProvince($temp);
							}
							if(count($possibleStateAndCountry) == 0) $possibleStateAndCountry = $this->getStateOrProvince($token);
							if(count($possibleStateAndCountry) > 0) {
								$temp = $possibleStateAndCountry[0];
								if(strcasecmp($temp, $tState) == 0) {
									return array(
										'county' => $county,
										'stateProvince' => $temp,
										'country' => $possibleStateAndCountry[1]
									);
								}
							}
						}
						$tokenBeforePrevToken = $prevToken;
						$prevToken = $token;
						$tokenCount++;
					}
				}
			} else {
				$possibleStateAndCountry = $this->getStateOrProvince($line);
				if(count($possibleStateAndCountry) > 0) {
					$temp = $possibleStateAndCountry[0];
					if(strcasecmp($temp, $tState) == 0) {
						return array(
							'county' => $county,
							'stateProvince' => $temp,
							'country' => $possibleStateAndCountry[1]
						);
					}
				}
			}
		}
	}

	private function processCArray($cArray, $s, $nextWord = null) {//echo "\nLine 1531, Input to processCArray: ".$s.", nextWord: ".$nextWord."\n";
		if($cArray) {//echo "\nLine 1532, cArray is not null\n";
			$num = 0;
			foreach($cArray as $vs) {
				$county = $vs['county'];
				if(stripos($s, $vs['stateProvince']) !== FALSE) {
					$possibleState = $vs['stateProvince'];
					$possibleCountry = $vs['country'];
					$num++;
				} else if($nextWord != null && stripos($nextWord, $vs['stateProvince']) !== FALSE) {
					$possibleState = $vs['stateProvince'];
					$possibleCountry = $vs['country'];
					$num++;
				}
			}//echo "\nLine 1545, num: ".$num."\n";
			if($num == 1) {
				$results = array();
				array_push($results, array('county' => $county, 'stateProvince' => $possibleState, 'country' => $possibleCountry));
				return $results;
			} else  return $cArray;
		}
	}

	private function processCountyString($cString, $nextWord = null) {
		$cString = trim($cString, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		//echo "\nLine 1052, Input to processCountyString: ".$cString.", nextWord: ".$nextWord."\n";
		if(strlen($cString) > 0) {
			if($nextWord != null) $nextWord = trim($nextWord, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			$statePos = stripos($cString, "State");
			if($statePos !== FALSE) {
				$cArray = $this->getCounty($nextWord);
				if($cArray != null) {
					return $this->processCArray($cArray, $cString, $nextWord);
				}
			}
			if(substr_count($cString, " ") < 3) {//if a string contains more than 2 spaces (3 words) there is no point in going to the database with it
				$cArray = $this->getCounty($cString);
				if($cArray != null) {
					return $this->processCArray($cArray, $cString, $nextWord);
				}
			}
			$commaPos = strrpos($cString, ",");
			$results = array();
			$possibleState = "";
			$county = "";
			$possibleCountry = "";
			if($commaPos !== FALSE) {
				$afterComma = trim(substr($cString, $commaPos+1));
				$beforeComma = trim(substr($cString, 0, $commaPos));
				if(substr_count($afterComma, " ") < 3) {
					$cArray = $this->getCounty($afterComma);
					if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
					else {
						$colonPos = strpos($afterComma, ":");
						if($colonPos !== FALSE) {
							$afterColon = trim(substr($afterComma, $colonPos+1));
							$beforeColon = trim(substr($afterComma, 0, $colonPos));
							if(substr_count($afterColon, " ") < 3) {
								$cArray = $this->getCounty($afterColon);
								if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
								else if(substr_count($beforeColon, " ") < 3) {
									$cArray = $this->getCounty($beforeColon);
									if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
								}
							}
						}
					}
				}
			}
			$colonPos = strpos($cString, ":");
			if($colonPos !== FALSE) {
				$afterColon = trim(substr($cString, $colonPos+1));
				$beforeColon = trim(substr($cString, 0, $colonPos));
				if(substr_count($afterColon, " ") < 3) {
					$cArray = $this->getCounty($afterColon);
					if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
					else if(substr_count($beforeColon, " ") < 3) {
						$cArray = $this->getCounty($beforeColon);
						if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
					}
				}
			}
			if(strpos($cString, ".") !== FALSE) {
				$dotPos = strrpos($cString, ".");
				$afterDot = trim(substr($cString, $dotPos+1));
				$beforeDot = trim(substr($cString, 0, $dotPos));
				if(substr_count($afterDot, " ") < 3) {
					$cArray = $this->getCounty($afterDot);
					if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
					else {
						$spacePos = strrpos($beforeDot, " ");//it might be a county with a name like St. Johns
						if($spacePos !== FALSE) {
							$afterSpace = trim(substr($beforeDot, $spacePos+1));
							$cArray = $this->getCounty($afterSpace);
							if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
						}
					}
				}
			}
			$spacePos = strpos($cString, " ");
			if($spacePos !== FALSE) {
				$cArray = null;
				$words = array_reverse(explode(" ", $cString));
				$totalWordCount = count($words);
				if($totalWordCount == 2) {//total word count won't be one since cString has been trimmed and spacePos !== FALSE
					$lastWord = trim($words[0]);
					$wordBefore = trim($words[1]);
					$cArray = $this->getCounty(trim($wordBefore." ".$lastWord));
					if($cArray == null) {
						$cArray = $this->getCounty($lastWord);
						if($cArray == null) $cArray = $this->getCounty($wordBefore);
					}
					if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
				} else {//to avoid false matches with individual words that are part of a multiword county, find the matches for multiple words first
					$wordCount = 0;
					$prevWord = "";
					$wordBeforePrevWord = "";
					foreach($words as $word) {
						if($wordCount > 1) {//echo "\nline 1132, word: ".$word.", prevWord: ".$prevWord.", wordBeforePrevWord: ".$wordBeforePrevWord."\n";
							if(strlen($prevWord) > 0) {
								if(strlen($wordBeforePrevWord) > 0) {
									$temp = trim($word." ".$prevWord." ".$wordBeforePrevWord);
									$cArray = $this->getCounty($temp);
									if($cArray == null) {
										$temp = trim($prevWord." ".$wordBeforePrevWord);
										$cArray = $this->getCounty($temp);
										if($cArray == null) {
											$cArray = $this->getCounty($wordBeforePrevWord);
											if($cArray == null) $cArray = $this->getCounty($prevWord);
										}
									}
								} else {
									$temp = trim($word." ".$prevWord);
									$cArray = $this->getCounty($temp);
								}
							} else if(strlen($wordBeforePrevWord) > 0) {
								$temp = trim($word." ".$wordBeforePrevWord);
								$cArray = $this->getCounty($temp);
							}
							if($cArray == null) $cArray = $this->getCounty($word);
							if($cArray != null) return $this->processCArray($cArray, $cString, $nextWord);
						}
						$wordBeforePrevWord = $prevWord;
						$prevWord = $word;
						$wordCount++;
					}
				}
			} else {
				$cArray = $this->getCounty(trim($cString));
				if($cArray != null && count($cArray) > 0) {
					if(count($cArray) == 1) return $cArray;
					else {
						foreach($cArray as $vs) {
							if($nextWord != null && stripos($nextWord, $vs['stateProvince']) !== FALSE) {
								$results = array();
								array_push($results, array('county' => $vs['county'], 'stateProvince' => $vs['stateProvince'], 'country' => $vs['country']));
								return $results;
							}
						}
						return $cArray;//return the whole result set since can't determine which is correct
					}
				}
			}
		}
	}

	private function findCounty($c, $state_province="") {//echo "\nLine 1193, Input to findCounty: ".$c."\n";
		$countyPatStr = "/(?:(?(?=(?:.*+)(?:(?:[!|150a-zA-Z]+(?:\\.?\\s?[!|150a-zA-Z]*))".
			"(?:\\.?\\s?[!|150a-zA-Z]*)?)?\\s?\\b(?:C[o0q]un[tf]y|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:(?i)\\s(?:Road|Hiway|Hwy|Highway|line)))\\b[,:]?(?:.*)))".

			"(.*+)((?:[!|150a-zA-Z]+(?:\\.?\\s?[!|150a-zA-Z]*))".
			"(?:\\.?\\s?[!|150a-zA-Z]*)?)?\\s?\\b(?:C[o0q]un[tf]y|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:(?i)\\s(?:Road|Hiway|Hwy|Highway|line)))\\b[,:]?(.*)|".

			"(?:(?(?=(?:.*+)(?:(?:[!|150a-zA-Z]+(?:\\.?\\s?[!|150a-zA-Z]*))".
			"(?:\\.?\\s?[!|150a-zA-Z]*)?)?\\s?\\bCo\\.(?:.*)))".

			"(.*+)((?:[!|150a-zA-Z]+(?:\\.?\\s?[!|150a-zA-Z]*))".
			"(?:\\.?\\s?[!|150a-zA-Z]*)?)?\\s?\\bCo\\.(.*)|".

			"(?:(?(?=(?:.*+)(?:(?:[!|150a-zA-Z]+(?:\\.?\\s?[!|150a-zA-Z]*))".
			"(?:\\.?\\s?[!|150a-zA-Z]*)?)?\\s?\\bCo,(?:.*)))".

			"(.*+)((?:[!|150a-zA-Z]+(?:\\.?\\s?[!|150a-zA-Z]*))".
			"(?:\\.?\\s?[!|150a-zA-Z]*)?)?\\s?\\bCo,(.*)|".

			"(?:(?(?=(?:.*)\\b(?:C[o0q](?:(?:unty)|\\.)|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:(?i)\\s(?:Road|Hiway|Hwy|Highway|line)))\\b[,:]?+(?:.*)))".

			"(.*)\\b(?:C[o0q](?:(?:unty)|\\.)|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:(?i)\\s(?:Road|Hiway|Hwy|Highway|line)))\\b[,:]?+(.*)|".

			"(?:(?(?=(?:.*)\\b(?:Co[,.]|Go\\.|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:(?i)\\s(?:Road|Hiway|Hwy|Highway|line)))\\b:?+(?:.*)))".

			"(.*?)\\b(?:Co[,.]|Go\\.|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:(?i)\\s(?:Road|Hiway|Hwy|Highway|line)))[,:]?\\b:?+(.*)|".

			"(.*?)(?:\\n\\r|\\r\\n|\\n|\\r|\\s)(?:Co[,.]|Go\\.|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:(?i)\\s(?:Road|Hiway|Hwy|Highway|line)))[,:]?+(.*))))))/is";

		$tempC = str_replace(array("\"", " & "), array("", " and "), $c);
		if(preg_match($countyPatStr, $tempC, $countyMatches)) {
			//$i = 0;
			//foreach($countyMatches as $countyMatch) echo "\ncountyMatches[".$i++."] = ".$countyMatch."\n";
			$firstPart = "";
			$secondPart = "";
			$thirdPart = "";
			$lastPart = "";
			$state = "";
			$punctuation = "";
			$county = "";
			$country = "";
			if(count($countyMatches) > 14) {
				$firstPart = $countyMatches[14];
				$secondPart = "";
				$lastPart = $countyMatches[15];
			} else if(count($countyMatches) > 12) {
				$firstPart = $countyMatches[12];
				$secondPart = "";
				$lastPart = $countyMatches[13];
			} else if(count($countyMatches) > 10) {
				$firstPart = $countyMatches[10];
				$secondPart = "";
				$lastPart = $countyMatches[11];
			} else if(count($countyMatches) > 7) {
				$firstPart = $countyMatches[7];
				$secondPart = $countyMatches[8];
				$lastPart = $countyMatches[9];
			} else if(count($countyMatches) > 4) {
				$firstPart = $countyMatches[4];
				$secondPart = $countyMatches[5];
				$lastPart = $countyMatches[6];
			} else if(count($countyMatches) > 0) {
				$firstPart = $countyMatches[1];
				$secondPart = $countyMatches[2];
				$lastPart = $countyMatches[3];
			}//echo "\nline 1238, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlastPart: ".$lastPart."\n";
			$cs = $this->processCountyMatches($firstPart, $secondPart, $lastPart);
			if($cs != null) {
				$county = $cs['county'];
				if(array_key_exists('stateProvince', $cs)) $state = $cs['stateProvince'];
				if(array_key_exists('country', $cs)) $country = $cs['country'];
				$pos = strripos($firstPart, $county);
				if($pos !== FALSE) $firstPart = substr($firstPart, 0, $pos);
				else {
					$pos = stripos($firstPart, str_replace("Saint", "St.", $county));
					if($pos !== FALSE) $firstPart = substr($firstPart, 0, $pos);
				}
			}
			if(strlen($county) == 0) {
				if(strlen($state_province) > 0) {
					$counties = $this->getCounties($state_province);
					if(count($counties) > 0) {
						foreach($counties as $countie) {
							if(strlen($secondPart) > 0) {
								$county = $this->findTokenAtEnd($secondPart, $countie);
								if(strlen($county) > 0) break;
							} else if(strlen($firstPart) > 0) {
								$county = $this->findTokenAtEnd($firstPart, $countie);
								if(strlen($county) > 0) break;
							}
						}
					}
				}
			}
			if(strlen($county) > 0) {
				$pos = strrpos($county, ",");
				if($pos !== FALSE) {
					$temp = trim(substr($county, $pos+1));
					if(strlen($temp) > 0) $county = $temp;
				}
				$pos = strpos($county, ":");
				if($pos !== FALSE) $county = trim(substr($county, $pos+1));
				$pos = strpos($county, ".");
				if($pos !== FALSE) {
					$temp = trim(substr($county, 0, $pos));
					if(strlen($temp) > 3) {
						$county = trim(substr($county, $pos+1));
						$secondPart .= " ".$temp;
					}
				}
				if(strlen($state) > 0) {
					$tState = substr($state, 0, strlen($state)-1);
					if(strpos($tState, ".") !== FALSE) {
						$state = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5', '2', '0'),
									array('l', 'l', 'l', 'S', 'Z', 'O'),
									trim
									(
										$this->terminateField
										(
											$state,
											"/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|[ (]\\d{2,}|\\d{2,}\\s|[lI!|]ocality|[lI!|]ocation|".
											"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|s[:.])|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|NEW \\w{4} BOTAN.+)/i"
										),
										" \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"
									)
								)
							)
						);
					} else {
						$state = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5', '2', '0'),
									array('l', 'l', 'l', 'S', 'Z', 'O'),
									trim
									(
										$this->terminateField
										(
											$state,
											"/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|[ (]\\d{2,}|\\d{2,}\\s|[lI!|]ocality|[lI!|]ocation|".
											"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|s[:.])|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|NEW \\w{4} BOTAN.+)/i"
										),
										" \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"
									)
								)
							)
						);
					}
				}
				$county = ucwords
				(
					strtolower
					(
						str_replace
						(
							array('1', '!', '|', '5', '2', '0'),
							array('l', 'l', 'l', 'S', 'Z', 'O'),
							trim($county)
						)
					)
				);
			}
			//echo "\nline 1344, leaving findCounty, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\ncounty: ".$county."\nstate: ".$state."\nlastPart: ".$lastPart."\n";
			return array($firstPart, $secondPart, $county, $country, $lastPart, $state);
		}
		return null;
	}

	private function getAssociatedTaxa($str) {
		$atPat = "/(?:(?:Assoc(?:[,.]|[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|with|spp[,.]?)[:;]?)|containing|".
			"(?:along|together)\\swith(?:,?\\s?e[.\\s]?g\\.,)?)\\s(?:include\\s)?(.*)/is";
		if(preg_match($atPat, $str, $matches)) {
			$taxa = trim($matches[1]);
			//$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))?|Apr(?:\\.|(?:il))?|May|Jun[\\.|e]?|Jul[\\.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
			$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
			$endPatStr = "/(.*?)(?:(?:\\d{1,3}(?:\\.\\d{1,7})?\\s?°)|Alt.|Elev|\\son\\s|Date|[;:]|(?:(?:\\d{1,2}\\s)?(?:".$possibleMonths.")))/is";
			if(preg_match($endPatStr, $taxa, $tMatches)) $taxa = trim($tMatches[1]);
			return str_replace(array("\r\n","\n", "- "), array(" ", " ", ""), $taxa);
		}
		return "";
	}

	private function getTaxonOfHeaderInfo($str) {
		$statePatStr = "/((?s).*)?((?:(?:[EP][L1!|]ANT[5S])|(?:ASC[O0D]MYC[EC]T[EC]S[5S])|(?:FL[O0D]R\\w)|(?:F(?:U|(?:LI))N[EGC][Il!1.,])|".
			"(?:.{1,2}[I|!l][ECU](?:H|(?:I-?I)|TI)EN[5S])|(?:CRYPT[O0DQU]GAM[5S])|(?:BRY[O0DQU]PHYT[EC][5S]))".
			"(?:\\r\\n|\\n|\\r|\\s)?[DOU0][REFPNKI1][RFPN]?)(.*)(?:\\r\\n|\\n|\\r)((?s).*)/i";
		if(preg_match($statePatStr, $str, $matches)) {
			if(preg_match($statePatStr, $matches[1], $matches2)) {
				return array($matches2[1], $matches2[3], $matches2[4].$matches[2].$matches[3].$matches[4]);
			}
			else return array($matches[1], $matches[3], $matches[4]);
		}
		return null;
	}

	private function processTaxonOfHeaderInfo($matches) {
		$result = array();
		$country = "";
		$location = "";
		$state_province = "";
		$county = "";
		$scientificName = "";
		$substrate = "";
		$recordNumber = "";
		$habitat = "";
		$associatedTaxa = "";
		$taxonRank = "";
		$infraspecificEpithet = "";
		$verbatimAttributes = "";
		$states = array();
		$startOfFile = trim($matches[0]);
		$endOfLine = trim($matches[1]);
		$endOfFile = trim($matches[2]);
		$sLen = strlen($endOfLine);
		//echo "\nline 1417, startOfFile: ".$startOfFile."\n\tendOfLine: ".$endOfLine."\n\tendOfFile: ".$endOfFile."\n";
		//if there is nothing after the "Taxons of", get it from the beginning of the next line
		if($sLen == 0) {
			$eolPos = strpos($endOfFile, "\n");
			if($eolPos !== FALSE) {
				$firstLine = trim(substr($endOfFile, 0, $eolPos));
				$endOfFile = trim(substr($endOfFile, $eolPos+1));
				$commaPos = strpos($firstLine, ",");
				if($commaPos !== FALSE) {
					$firstPart = trim(substr($firstLine, 0, $commaPos));
					$lastPart = trim(substr($firstLine, $commaPos+1));
					$spacePos = strpos($lastPart, " ");
					if($spacePos !== FALSE) {
						$endOfLine = $firstPart.", ".substr($lastPart, 0, $spacePos);
						$endOfFile = substr($lastPart, $spacePos+1)."\n".$endOfFile;
					} else $endOfLine = $firstLine;
				} else {
					$spacePos = strpos($firstLine, " ");
					if($spacePos !== FALSE) {
						$endOfLine = trim(substr($firstLine, 0, $spacePos));
						$endOfFile = substr($firstLine, $spacePos+1)."\n".$endOfFile;
					} else $endOfLine = $firstLine;
				}
			}
		//if there is an isolated character at the end of the line, remove it
		} else if($sLen > 2 && substr($endOfLine, $sLen-2, 1) == " ") $endOfLine = trim(substr($endOfLine, 0, $sLen-1));
		//echo "\nline 1443, startOfFile: ".$startOfFile."\n\tendOfLine: ".$endOfLine."\n\tendOfFile: ".$endOfFile."\n";
		$commaPos = strpos($endOfLine, ',');
		$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		if($commaPos !== FALSE) {
			$firstPart = trim(substr($endOfLine, 0, $commaPos));
			$secondPart = trim(substr($endOfLine, $commaPos+1));
			//echo "\nline 1450, firstPart: ".$firstPart."\n\tsecondPart: ".$secondPart."\n";
			$sp = $this->getStateOrProvince($firstPart);
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
				$pos = stripos($firstPart, $state_province);
				if($pos !== FALSE) {
					if($pos == 0) $firstPart = substr($firstPart, strlen($state_province));
					else $firstPart = substr($firstPart, 0, $pos);
				}
				$countyMatches = $this->findCounty($firstPart, $state_province);
				if($countyMatches != null) {
					$county = trim($countyMatches[2]);
				} else {
					$countyMatches = $this->findCounty($secondPart, $state_province);
					if($countyMatches != null) $county = trim($countyMatches[2]);
				}
			} else {
				$country = $this->getCountry(trim($secondPart));
				if(strlen($country) > 0) {
					$states = $this->getStatesFromCountry($country);
					foreach($states as $state) {
						if(strcasecmp($firstPart, $state) == 0) $state_province = $state;
					}
				} else {
					$sp = $this->getStateOrProvince($secondPart);
					if(count($sp) > 0) {
						$state_province = $sp[0];
						if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))\\b/i", $firstPart, $cMatches)) {
							$county = trim($cMatches[1]);
						}
						$country = $sp[1];
					} else {
						$sp = $this->getStateOrProvince($firstPart);
						if(count($sp) > 0) {
							$state_province = $sp[0];
							if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))\\b/i", $secondPart, $cMatches)) {
								$county = trim($cMatches[1]);
							}
							$country = $sp[1];
						} else $country = $this->getCountry(trim($firstPart));
					}
				}
			}
		} else {
			$ps = $this->getStateOrProvince(trim($endOfLine));
			if(count($ps) > 0) {
				$state_province = $ps[0];
				$country = $ps[1];
				if(strcasecmp($state_province, $endOfLine) != 0) {
					if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))/i", $endOfLine, $cMatches)) {
						$countyMatches = $this->findCounty($endOfLine, $state_province);
						if($countyMatches != null) {
							if(strlen($state_province) == 0) $state_province = trim($countyMatches[5]);
							if(strlen($location) == 0) $location = trim($countyMatches[0]);
							$county = trim($countyMatches[2]);
						}
					}
				}
			} else {
				$country = $this->getCountry($endOfLine);
				if(strlen($country) == 0) {
					if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))/i", $endOfLine)) {
						$countyMatches = $this->findCounty($endOfLine, $state_province);
						if($countyMatches != null) {
							if(strlen($state_province) == 0) $state_province = trim($countyMatches[5]);
							if(strlen($location) == 0) $location = trim($countyMatches[0]);
							$county = trim($countyMatches[2]);
							$country = trim($countyMatches[3]);
						}
					}
					else {
						$ps = $this->getStateOrProvince(trim($endOfLine));
						if(count($ps) > 0) {
							$state_province = $ps[0];
							$country = $ps[1];
						}
					}
				} else if($country != $endOfLine) {
					if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))/i", $endOfLine, $cMatches)) {
						$countyMatches = $this->findCounty($endOfLine, $state_province);
						if($countyMatches != null) {
							if(strlen($state_province) == 0) $state_province = trim($countyMatches[5]);
							if(strlen($location) == 0) $location = trim($countyMatches[0]);
							$county = trim($countyMatches[2]);
						}
					}
					$states = $this->getStatesFromCountry($country);
				} else $states = $this->getStatesFromCountry($country);
			}
		}
		$eolPos = strpos($endOfFile, "\n");
		$temp = "";//first line of endOfFile
		$rest = "";//rest of endOfFile
		$foundSciName = false;
		if($eolPos !== FALSE) {
			$temp = trim(substr($endOfFile, 0, $eolPos));
			$rest = trim(substr($endOfFile, $eolPos+1));
		} else $temp = $endOfFile;
		if(strlen($rest) > 0) {
			if(preg_match("/\\s(?:&|var\\.?|s(?:ub)?sp\\.|f\\.)$/i", $temp)) {//combine the first line and the next line
				$eolPos = strpos($rest, "\n");
				if($eolPos !== FALSE) {
					$temp .= " ".trim(substr($rest, 0, $eolPos));
					$rest = trim(substr($rest, $eolPos+1));
				} else {
					$temp .= " ".$rest;
					$rest = "";
				}
			}
		}
		if(strlen($rest) > 0) {//echo "\nline 1557, temp: ".$temp.", rest: ".$rest."\n";
			$eolPos = strpos($rest, "\n");
			if($eolPos !== FALSE) {
				$nl = trim(substr($rest, 0, $eolPos));//get the next line
				if(preg_match("/^(var\\.?|s(?:ub)?sp\\.|f\\.)\\s(.*)/i", $nl, $tMats)) {
					$rest = trim(substr($rest, $eolPos+1));
					$tR = $tMats[1];
					$t = trim($tMats[2]);
					$sPos = strpos($t, ";");
					if($sPos === FALSE) $sPos = strpos($t, " ");
					if($sPos !== FALSE) {
						$temp .= " ".$tR." ".trim(substr($t, 0, $sPos));
						$rest = trim(substr($t, $sPos+1))." ".$rest;
					} else $temp .= " ".$nl;
					$eolPos = strpos($rest, "\n");
					if($eolPos !== FALSE) $nl = trim(substr($rest, 0, $eolPos));
					else $nl = $rest;
				}
				if(preg_match("/^(?:\+|on)\\s/i", $nl)) {
					$temp .= " ".$nl;
					$rest = trim(substr($rest, $eolPos+1));
				}
			} else {
				if(preg_match("/^(?:var\\.?|s(?:ub)?sp\\.|f\\.)\\s/i", $rest)) {
					$sPos = strpos($rest, ";");
					if($sPos === FALSE) $sPos = strpos($rest, " ");
					if($sPos !== FALSE) {
						$temp .= " ".trim(substr($rest, 0, $sPos));
						$rest = trim(substr($rest, $sPos+1));
					} else {
						$temp .= " ".$rest;
						$rest = "";
					}
				}
				if(preg_match("/^(?:\+|on)\\s/i", $rest)) {
					$temp .= " ".$rest;
					$rest = "";
				}
			}
		}
		if
		(!is_numeric($temp) &&
			!preg_match("/\\b(?:lichens?|loc(?:\\.|ality|ation)|loc(?:\\.|ality|ation)|Herbarium|Univers|".
				"County|Par[il!|]sh|B[o0]r[o0]ugh|Park|Island|Quadrangle|Dist(?:\\.|rict)|U\\.?S\\.?A\\.?|NEW Y\\wRK|".
				"B[O0]TAN[I1!l|]CAL|=)\\b/i", $temp)
		) {//echo "\nline 1608, temp: ".$temp."\n";
			$psn = $this->processSciName($temp);
			if($psn != null) {
				if(array_key_exists ('scientificName', $psn)) {
					$scientificName = $psn['scientificName'];
					$foundSciName = true;
				}
				if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
				if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
				if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
				if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
				if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
				if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
			}
		}
		if(!$foundSciName && strlen($rest) > 0) {
			$eolPos = strpos($rest, "\n");
			if($eolPos !== FALSE) {
				$temp = trim(substr($rest, 0, $eolPos));
				$rest = trim(substr($rest, $eolPos+1));
			} else $temp = $rest;
			if(strlen($rest) > 0) {
				if(preg_match("/\\s(?:&|var\\.?|s(?:ub)?sp\\.|f\\.)$/i", $temp)) {
					$eolPos = strpos($rest, "\n");
					if($eolPos !== FALSE) {
						$temp .= " ".trim(substr($rest, 0, $eolPos));
						$rest = trim(substr($rest, $eolPos+1));
					} else {
						$temp .= " ".$rest;
						$rest = "";
					}
				}
			}
			if(strlen($rest) > 0) {
				$eolPos = strpos($rest, "\n");
				if($eolPos !== FALSE) {
					$nl = trim(substr($rest, 0, $eolPos));
					if(preg_match("/^(var\\.?|s(?:ub)?sp\\.|f\\.)\\s(.*)/i", $nl, $tMats)) {
						$rest = trim(substr($rest, $eolPos+1));
						$tR = $tMats[1];
						$t = trim($tMats[2]);
						$sPos = strpos($t, ";");
						if($sPos === FALSE) $sPos = strpos($t, " ");
						if($sPos !== FALSE) {
							$temp .= " ".$tR." ".trim(substr($t, 0, $sPos));
							$rest = trim(substr($t, $sPos+1))." ".$rest;
						} else $temp .= " ".$nl;
						$eolPos = strpos($rest, "\n");
						if($eolPos !== FALSE) $nl = trim(substr($rest, 0, $eolPos));
						else $nl = $rest;
					}
					if(preg_match("/^(?:\+|on)\\s/i", $nl)) {
						$temp .= " ".$nl;
						$rest = trim(substr($rest, $eolPos+1));
					}
				} else {
					if(preg_match("/^(?:var\\.?|s(?:ub)?sp\\.|f\\.)\\s/i", $rest)) {
						$sPos = strpos($rest, ";");
						if($sPos === FALSE) $sPos = strpos($rest, " ");
						if($sPos !== FALSE) {
							$temp .= " ".trim(substr($rest, 0, $sPos));
							$rest = trim(substr($rest, $sPos+1));
						} else {
							$temp .= " ".$rest;
							$rest = "";
						}
					}
					if(preg_match("/^(?:\+|on)\\s/i", $rest)) {
						$temp .= " ".$rest;
						$rest = "";
					}
				}
			}
			if
			(!is_numeric($temp) &&
				!preg_match("/\\b(?:lichens?|loc(?:\\.|ality|ation)|loc(?:\\.|ality|ation)|Herbarium|Univers|".
				"County|Par[il!|]sh|B[o0]r[o0]ugh|Park|Island|Quadrangle|Dist(?:\\.|rict)|U\\.?S\\.?A\\.?|NEW Y\\wRK|".
				"B[O0]TAN[I1!l|]CAL|=)\\b/i", $temp)
			) {
				$psn = $this->processSciName($temp);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
						$foundSciName = true;
					}
					if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
					if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
				}
			}
		}//echo "\nline 2201, endOfFile: ".$endOfFile."\n\tcounty: ".$county."\n";
		if(strlen($county) == 0) {
			$countyMatches = $this->findCounty($endOfFile, $state_province);
			if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 1708, countyMatches[".$i++."] = ".$countyMatche."\n";
				if(strlen($state_province) == 0) $state_province = trim($countyMatches[5]);
				$firstPart = trim($countyMatches[0]);
				$secondPart = trim($countyMatches[1]);
				$county = trim($countyMatches[2]);
				if(strlen($country) == 0) $country = trim($countyMatches[3]);
				if(strlen($county) > 0) {//echo "\nline 2211, location: ".$location."\n\tcounty: ".$county."\nfirstPart: ".$firstPart."\nsecondPart: ".$secondPart."\n";
					$location = ltrim($countyMatches[4], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
					$pos = stripos($location, $scientificName);
					if($pos !== FALSE && $pos < 6) $location = substr($location, $pos+strlen($scientificName));
					if(strlen($county) > 0) {
						$county = ucwords
						(
							strtolower
							(
								str_replace(array('1', '!', '|', '5', '2', '0'), array('l', 'l', 'l', 'S', 'Z', 'O'), trim($county))
							)
						);
					}
					$onPos = stripos($secondPart, "on ");
					if($onPos !== FALSE) {
						if($onPos == 0) {
							$substrate = trim($secondPart);
						} else if(strlen($scientificName) == 0) {
							$psn = $this->processSciName($secondPart);
							if($psn != null) {
								if(array_key_exists ('scientificName', $psn)) {
									$scientificName = $psn['scientificName'];
									$foundSciName = true;
								}
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
								if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
							}
						}
					} else if(strlen($scientificName) == 0) {
						$psn = $this->processSciName($secondPart);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								$foundSciName = true;
							}
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
							if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
						}
					}
					if(strlen($scientificName) == 0) {
						$psn = $this->processSciName($firstPart);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								$foundSciName = true;
							}
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
							if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
						}
					}
					if(strlen($location) > 0) {
						if(strlen($scientificName) == 0) {
							$lWords = explode("\n", $location);
							if(count($lWords) > 1) {
								$lWords0 = trim($lWords[0]);
								$psn = $this->processSciName($lWords0);
								if($psn != null) {
									if(array_key_exists ('scientificName', $psn)) {
										$scientificName = $psn['scientificName'];
										$foundSciName = true;
									}
									if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
									if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
									if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
									if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
									if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
									if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
									$location = trim(substr($location, strpos($location, $lWords0)+strlen($lWords0)));
								}
							}
						}
						$pos = strpos($location, "\n\n");
						if($pos !== FALSE) {
							$temp = trim(substr($location, 0, $pos));
							$pos2 = strpos($temp, "\n");
							if($pos2 === FALSE) {
								$sp = $this->getStateOrProvince($temp);
								if(count($sp) > 0) {
									if(strlen($state_province) == 0) $state_province = $sp[0];
									if(strlen($country) == 0) $country = $sp[1];
									$location = trim(substr($location, $pos));
								} else if(strlen($scientificName) == 0) {
									$psn = $this->processSciName($temp);
									if($psn != null) {
										if(array_key_exists ('scientificName', $psn)) {
											$scientificName = $psn['scientificName'];
											$foundSciName = true;
										}
										if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
										if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
										if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
										if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
										if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
										if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
										$location = trim(substr($location, strpos($location, $temp)+strlen($temp)));
									}
								}
							}
						}
					}
				}
				if(strlen($country) > 0 && strlen($state_province) == 0) {
					if(preg_match("/\\bU\\.?S\\.?A\\.?\\b/", $country) || preg_match("/United States/i", $country)) {
						$ps = $this->getStateFromCounty($county, null, "United States");
						if(count($ps) > 0) $state_province = $ps[0];
					} else {
						$ps = $this->getStateFromCounty($county, null, $country);
						if(count($ps) > 0) $state_province = $ps[0];
					}
					if(strlen($state_province) == 0) {
						$thirdPart = ltrim($countyMatches[4], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
						if(strlen($thirdPart) > 0) {
							$states = $this->getStatesFromCountry($country);
							foreach($states as $s) if(strcasecmp($s, $thirdPart) == 0) $state_province = $s;
							if(strlen($state_province) == 0) $location = $thirdPart." ".$location;
						}
					}
				}
			} else if(count($states) > 0 && strlen($state_province) == 0) {
				foreach($states as $state) {
					$countyPatStr = "/(.*)".$state."[:;,.)\\n\\r]\\s?(.*)/is";
					if(preg_match($countyPatStr, $endOfFile, $countyMatches)) {
						$state_province = $state;
						$firstPart = trim($countyMatches[1]);
						$location = trim($countyMatches[2]);
						$countyPos = stripos($location, " County");
						if($countyPos !== FALSE && strlen($county) == 0) {
							$county = substr($location, 0, $countyPos);
							$location = substr($location, $countyPos+6);
						}
					}
				}
			}
		}
		if(strlen($substrate) == 0) {
			$lines = explode("\n", $endOfFile);
			$lookingForLocation = false;
			foreach($lines as $line) {
				if(preg_match("/^(on\\s.*)/i", $line, $mats)) {
					$sub = trim($mats[1]);
					$inappropriateWords = array(" road", " north", " south", " east", " west", " highway", " hiway", " area", " state",
						" valley", " slope", " route", " Rd.", " Co.", " County");
					foreach($inappropriateWords as $inappropriateWord) {
						if(stripos($sub, $inappropriateWord) !== FALSE) {
							if(strlen($location) == 0) {
								$location = $sub;
								$lookingForLocation = true;
							}
							$sub = "";
							break;
						}
					}
					if(strlen($sub) > 0) {
						if(preg_match("/(.*)\\b(?:UNIVERS|COLL(?:\\.|ect)|HERBAR|DET(?:\\.|ermin)|Identif|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden|Date|".$possibleMonths.")/i", $sub, $mats)) {
							$sub = trim($mats[1]);
						}
						$dotPos = strpos($sub, ".");
						if($dotPos !== FALSE) {
							if(strlen($habitat) == 0) $habitat = trim(substr($sub, $dotPos+1));
							$substrate = trim(substr($sub, 0, $dotPos));
						} else $substrate = $sub;
						continue;
					}
				}
				if($lookingForLocation) {
					if(preg_match("/(.*)\\b(?:UNIVERS|COLL(?:\\.|ect)|HERBAR|DET(?:\\.|ermin)|Identif|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden|Date|".$possibleMonths.")/i", $line, $mats2)) {
						$temp = trim($mats2[1]);
						if(strlen($temp) > 6) {
							if($lookingForLocation && stripos($location, $temp) === FALSE) $location .= " ".$temp;
						}
					} else if($lookingForLocation && stripos($location, $line) === FALSE) $location .= " ".$line;
				}
			}
		}//echo "\nline 1895, location: ".$location."\nscientificName: ".$scientificName."\n";
		if(strlen($location) == 0) $location = trim($endOfFile);
		$locationPatStr = "/(.*)\\b(?:L|(?:|_))[o0]c(?:a[1!l]ity|ati[o0]n|\\.)?[:;,)]?\\s(.*)(?:(?:\\r\\n|\\n\\r|\\n)(.*))?/i";
		if(preg_match($locationPatStr, $location, $locationMatches)) {
			$location = trim($locationMatches[2]);
			if(count($locationMatches) == 4) $location .= " ".trim($locationMatches[3]);
		} else if(preg_match($locationPatStr, $endOfFile, $locationMatches)) {
			$location = trim($locationMatches[2]);
			if(count($locationMatches) == 4) $location .= " ".trim($locationMatches[3]);
		}
		if(strlen($state_province) > 0 && strlen($country) == 0) {
			$sp = $this->getStateOrProvince($state_province);
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
		}
		$result['country'] = $country;
		$result['locality'] = $location;
		$result['stateProvince'] = $state_province;
		$result['verbatimAttributes'] = $verbatimAttributes;
		$result['associatedTaxa'] = $associatedTaxa;
		$result['infraspecificEpithet'] = $infraspecificEpithet;
		$result['taxonRank'] = $taxonRank;
		$result['county'] = $county;
		$result['scientificName'] = $scientificName;
		$result['recordNumber'] = $recordNumber;
		$result['states'] = $states;
		$result['substrate'] = $substrate;
		return $result;
	}

	private function getScientificName($s) {
		$sciNamePatStr = "/Scientific Name[:;,]?\\b(.*)/i";
		if(preg_match($sciNamePatStr, $s, $sciNameMatches)) return trim($sciNameMatches[1]);
		return null;
	}

	private function getMunicipality($s) {
		$townPatStr = "/\\b(?:T[o0]wn|C[lI!|1]ty|V[lI!|1]{3}age) [o0][fr][;:]?\\s([!|0152\\w]+\\s?(?:[0152\\w]+)?)[:;,.]/is";
		if(preg_match($townPatStr, $s, $townMatches)) {
			return str_replace(array("0", "1", "!", "|", "5", "2"), array("O", "l", "l", "l", "S", "Z"), trim($townMatches[1]));
		}
	}

	private function getLocality($s) {
		//echo "\nInput to getLocality: ".$s."\n";
		$locationPatStr = "/\\b(?:L|(?:\|\_))?[o0]c(?:a[1!lI]{2}ty|ati[o0]n|\\.)?[:;,)]?\\s(.*)(?:(?:\\n|\\n\\r)(.*))?/i";
		if(preg_match($locationPatStr, $s, $locationMatches)) {
			$location = trim($locationMatches[1]);
			if(count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		} else {
			$locationPatStr = "/\\bC[o0][il1!|]{2}[ec]{2}t[il1!|][o0]n\\sS[il1!|]te[:;,.]?\\s(.+)/i";
			if(preg_match($locationPatStr, $s, $locationMatches)) {
				$location = trim($locationMatches[1]);
				if(count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
				return $location;
			} else {
				$locationPatStr = "/\\b((?:\\w+\\s)*S[il1!|]te[:;,.]?\\s.+)(?:(?:\\n|\\r\\n)((?s).+))?/i";
				if(preg_match($locationPatStr, $s, $locationMatches)) {
					$location = trim($locationMatches[1]);
					if(count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
					return $location;
				}
			}
		}
	}

	private function isFarlowLabel($s) {//only interested in finding the Farlow labels that are labeled as such at the top
		$farlowPat = "/.*\\bFarlow Herbarium\\b.{0,12}\\bHarvard\\b.*(?:\\n|\\n\\r).*(?:\\n|\\n\\r).*/i";
		if(preg_match($farlowPat, $s)) return true;
		else return false;
	}

	private function isKienerMemorialLabel($s) {//only interested in finding the Farlow labels that are labeled as such at the top
		$kienerPat = "/.*\\sK[1!lI]ENER\\sMEMOR[1!lI]A(?:L|[|!][-_]).*/i";
		if(preg_match($kienerPat, $s)) return true;
		else return false;
	}

	private function doFarlowLabel($s) {//Farlow labels have the sciName after the institution identifiers followed by substrate, if given
		//followed by the location, if given followed by the collector
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$state_province = '';
		$recordedBy = '';
		$country = '';
		$location = '';
		$substrate = '';
		$scientificName = '';
		$identified_by = '';
		$date_identified = array();
		$event_date = array();
		$s = substr($s, stripos($s, "Farlow Herbarium")+17);
		$eolPos = strpos($s, "\n");
		$s = substr($s, $eolPos+1);//go to next line
		$lines = explode("\n", $s);
		$foundSciName = false;
		foreach($lines as $line) {
			if(!(stripos($line, "Herbarium") !== FALSE || stripos($line, "Lichens") !== FALSE ||
				stripos($line, "New York") !== FALSE || stripos($line, "Botanical") !== FALSE ||
				stripos($line, "Garden") !== FALSE || stripos($line, "dupl.") !== FALSE) && strlen($line) > 6)
			{
				$scientificName = $line;
				$s = trim(substr($s, strpos($s, $scientificName)+strlen($scientificName)+1));
				$t = substr($scientificName, 0, 1);
				while(is_numeric($t)) {
					$scientificName = ltrim(substr($scientificName, 1), " .,:;");
					$t = substr($scientificName, 0, 1);
				}
				$foundSciName = true;
				break;
			}
		}
		if($foundSciName) {
			$lines = explode("\n", $s);
			$foundDeterminer = false;
			foreach($lines as $line) {
				$line = trim($line);
				$onPos = stripos($line, "On ");
				if($onPos !== FALSE && $onPos == 0) {
					$substrate = $line;
					$commaPos = stripos($substrate, ",");
					if($commaPos !== FALSE) {
						$location = trim(substr($substrate, $commaPos+1));
						$substrate = trim(substr($substrate, 0, $commaPos));
					}
				}
				else {
					$colPos = stripos($line, "Coll. ");
					if($colPos !== FALSE) {
						$detPos = stripos($line, " Det. ");
						if($detPos !== FALSE) {
							$identified_by = substr($line, $detPos+6);
							$recordedBy = $identified_by;
							$s = substr($s, strpos($s, $identified_by)+strlen($identified_by)+1);
							$foundDeterminer = true;
							break;
						} else {
							$recordedBy = substr($line, $colPos+6);
							$s = substr($s, strpos($s, $recordedBy)+strlen($recordedBy)+1);
						}
					} else {
						$legPos = stripos($line, "Leg. ");
						if($legPos !== FALSE) {
							$recordedBy = substr($line, $legPos+5);
							$s = substr($s, strpos($s, $recordedBy)+strlen($recordedBy)+1);
						}
						else {
							$detPos = stripos($line, "Det. ");
							if($detPos !== FALSE) {
								$identified_by = substr($line, $detPos+5);
								$s = substr($s, strpos($s, $identified_by)+strlen($identified_by)+1);
								$foundDeterminer = true;
								break;
							} else if(strlen($line) > 0 && strcmp(substr($line, 0, 1), "=") != 0 && strcasecmp(substr($line, 0, 5), "Syn. ") != 0) {
								if(strlen($location) == 0) $location = $line;
								else if(strlen($location) > 0 && stripos($location, $line) == FALSE) {
									$location .= " ".$line;
								}
							}
						}
					}
				}
			}
			$lPat = "/((?s).*)\\b(?:[il1!|]eg|".$possibleMonths."|\\d+)/i";
				if(strlen($location) > 0) {
				while(preg_match($lPat, $location, $ms)) $location = trim($ms[1], " .");
				if($this->isUSState($location)) {
					$state_province = $location;
					$country = "United States";
					$location = "";
				}
				if(strlen($location) > 0) {
					$words = array_reverse(explode(" ", $location));
					$nextWord = "";
					$index = 0;
					foreach($words as $word) {
						if($index == 0) $nextWord = $word;
						else if($index == 1) {
							$temp = $word." ".$nextWord;
							if($this->isUSState($temp)) {
								$state_province = $temp;
								$country = "United States";
								break;
							} else if($this->isUSState($nextWord)) {
								$state_province = $nextWord;
								$country = "United States";
								break;
							}
						} else break;
						$index++;
					}
				}
			}
			if($foundDeterminer) {//look for revised determination
				$detPos = stripos($s, "Det. ");
				if($detPos !== FALSE) {//echo "\nline 2166, found a redeterminer in ".$s."\n";
					$firstPart = trim(substr($s, 0, $detPos));
					$identified_by = trim(substr($s, $detPos+5));
					$eolPos = strpos($identified_by, "\n");
					if($eolPos !== FALSE) $identified_by = trim(substr($identified_by, 0, $eolPos));
					$linesAbove = array_reverse(explode("\n", $firstPart));
					foreach($linesAbove as $lineAbove) {
						if(strlen($lineAbove) > 0) {
							$scientificName = trim($lineAbove);
							break;
						}
					}
				}
			}
		}
		$cPat = "/((?s).*)\\b(".$possibleMonths.")?[ ,]?\\s?(\\d{4})/i";
		if(strlen($identified_by) > 0) {
			if(preg_match($cPat, $identified_by, $ms)) {
				$identified_by = trim($ms[1]);
				$date_identified['month'] = trim($ms[2]);
				$date_identified['year'] = trim($ms[3]);
			}
		}
		//$cPat = "/((?s).*)\\b((?:".$possibleMonths.")?[ ,]\\s?\\d{4})/i";
		if(strlen($recordedBy) > 0) {
			if(preg_match($cPat, $recordedBy, $ms)) {
				$recordedBy = trim($ms[1]);
				$event_date['month'] = trim($ms[2]);
				$event_date['year'] = trim($ms[3]);
			}
			if(strlen($recordedBy) > 0) {
				$words = array_reverse(explode(" ", $recordedBy));
				$nextWord = "";
				$index = 0;
				foreach($words as $word) {
					if($index == 0) $nextWord = $word;
					else if($index == 1) {
						$temp = $word." ".$nextWord;
						if($this->isUSState($temp)) {
							$state_province = $temp;
							$country = "United States";
							$recordedBy = substr($recordedBy, 0, strpos($recordedBy, $state_province));
							break;
						} else if($this->isUSState($nextWord)) {
							$state_province = $nextWord;
							$country = "United States";
							$recordedBy = substr($recordedBy, 0, strpos($recordedBy, $state_province));
							break;
						}
					} else break;
					$index++;
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName(trim($scientificName, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => $country,
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'eventDate' => $event_date,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	private function isNewStyleAKFWSLabel($s) {
		$akfwsPat = "/.*\\bA\\w{3,6}\\sHE[Rs]\\s?B[\w!|\s]{4,10}\\s[\"\'*]{1,3}\\sFL[O0]\\w{1,2}A\\s[O0]\\w\\sA[1Il!|]A[S5]KA(.+)/is";
		if(preg_match($akfwsPat, $s)) return true;
		else {
			$akfwsPat = "/.*\\bA\\wF\\w[S5]\\s[HB]E[RS]B[\w!|\s]{4,10}\\s[\"\'*]{1,3}\\sFL[O0]\\w{1,2}A\\s[O0]\\w\\sA[1Il!|]A[S5]KA.+/is";
			if(preg_match($akfwsPat, $s)) return true;
			else {
				$akfwsPat = "/[QO0G]uad\\.?\\s(?:M|H|IX|fl|Il)ap/i";
				if(preg_match($akfwsPat, $s)) return true;
				else return false;
			}
		}
	}

	private function isUSFishAndWildlifeServiceLabel($s) {
		$akfwsPat = "/.*\\bU\\.S[.#]\\sFish\\s(?:and|&)\\sWildlife\\sService(.+)/is";
		if(preg_match($akfwsPat, $s)) return true;
		else return false;
	}

	private function isMerrilLichenesExsiccatiLabel($s) {
		$merrillPat = "/.*M[CE]rr[1Il!|]{2,3}.*/is";
		if(preg_match($merrillPat, $s)) return true;
		else {
			$merrillPat = "/.*R[0o]ckp[0o]rt,\\sMain[CE].*/is";
			if(preg_match($merrillPat, $s)) return true;
			else {
				$merrillPat = "/.*Pr[CE]par[CE]d\\sby\\sG.*/is";
				if(preg_match($merrillPat, $s)) return true;
				else return false;
			}
		}
	}

	private function isWeberLichenesExsiccatiLabel($s) {
		$weberPat = "/.*[OD][1Il!|][-s5°][a-zA-Z10!|\'\"\/ -]{12,42}ado\\s[MW]us[CE]u[mn].*/is";
		if(preg_match($weberPat, $s)) return true;
		else {
			$weberPat = "/.*[OD[1Il!|].{2,3}tributed\\sby\\sthe\\sUniversity\\sof\\sColorado\\s[MW]us[CE]u[mn].*/is";
			if(preg_match($weberPat, $s)) return true;
			else {
				$weberPat = "/.*Colorado\\sMuseum,\\sB.*/is";
				if(preg_match($weberPat, $s)) return true;
				else return false;
			}
		}
	}

	private function isASULichenesExsiccatiLabel($s) {
		$asuPat = "/.*[OD][1Il!|][-s5][a-zA-Z10!|\'\"\/ -]{6,42}ona\\s[S5]t.t[CE]\\sUn.vers[1Il!|]t.*/is";
		if(preg_match($asuPat, $s)) return true;
		else {
			$asuPat = "/.*A\\.?S\\.?U\\.?\\s[a-zA-Z10!|.]{2,4}\\s[0-9lO]{2,3}.*/is";
			if(preg_match($asuPat, $s)) return true;
			else return false;
		}
	}

	private function isHasseLichenesExsiccatiLabel($s) {
		$hassePat = "/.*Hasse\\srelicti.*/is";
		if(preg_match($hassePat, $s)) return true;
		else {
			$hassePat = "/.*H.{1,2}\\sE.{1,2}\\sHasse\\b.*/is";
			if(preg_match($hassePat, $s)) return true;
			else return false;
		}
	}

	private function isAKFWSLabel($s) {//there are 2 kinds of AKFWS labels: the older ones with AKFWS herbarium above the Flora of Alaska
		//and the newer ones with AKFWS HERBARIUM ** FLORA of ALASKA on the same line
		//this function is called on lables that already match isFloraOfAlaska
		$akfwsPat = "/[AL]\\w{3,6}\\sHE[RSEK]\\s?B[A-Za-z0-9!| ]{4,10}.+/is";
		if(preg_match($akfwsPat, $s)) return true;
		else {
			$akfwsPat = "/[AL]\\wF\\w[S5]\\s[HBS]E[RSEK]B[A-Za-z0-9!| ]{4,10}.+/is";
			if(preg_match($akfwsPat, $s)) return true;
			else return false;
		}
	}

	private function isAlcanExpeditionLabel($s) {
		$akfwsPat = "/.*AL[CE]AN [CE]XP[CE]D[1Il!|]T[1Il!|][O0Q]N.+/is";
		if(preg_match($akfwsPat, $s)) return true;
		else return false;
	}

	private function isPlantsOfWyomingLabel($s) {
		$pat = "/.*P[1Il!|]ant[s5]\\s?[O0Q]f\\s?Wy[O0Q]m[1Il!|]ng.*/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isLichenesGroenlandiciLabel($s) {
		$pat = "/.*[1Il!|][CE]H[CE]N[BES5]{2}\\s?[CG]R[O0QD][CE]NLAND[1Il!|]C[1Il!|]\\s?[CE]XS[1Il!|][CE]{2}AT[1Il!|].+/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isBorealiAmericaniLabel($s) {
		$baPat = "/.*[BES5][ ._#-]{1,2}B[O0Q]r[CE]a[1Il!|]{2}[ ._#-]{1,2}Am[CE].[1Il!|][CE]an[1Il!|]?.+/is";
		if(preg_match($baPat, $s)) return true;
		else {
			$baPat = "/.*[1Il!|][CE]H[CE]N[BES5]{2}[ ._#-]{1,2}B[O0Q].{2}a[1Il!|]{2}[ ._#-]{1,2}Am[CE]r[1Il!|][CE]an[1Il!|]?.+/is";
			if(preg_match($baPat, $s)) return true;
			else {
				$baPat = "/.*[1Il!|]{2}[CE]H[CE]N[BES5]{2}[ ._#-]{1,2}B[O0Q]rea[1Il!|].?[1Il!|][ ._#-]{1,2}Am[CE].[1Il!|][CE].?an.?[1Il!|]?.+/is";
				if(preg_match($baPat, $s)) return true;
			}
			return false;
		}
	}

	private function isFloraOfAlaskaLabel($s) {//there are 2 kinds of FloraOfAlaska labels: The ones with AKFWS herbarium
		//and those with a Lat/Long label
		$alaPat = "/.*FL[O0]\\wA[.,]?\\s[O0Q]\\w\\sA[1Il!|]A[S5]KA.*/is";
		if(preg_match($alaPat, $s)) return true;
		else return false;
	}

	public function isLichenesExsiccatiLabel($s) {
		$exsiccatiPat = "/.*(?:L[1Il!|]|IZ|U)(?:[CE]H|QI)[CE]N[CE]S\\s[CE]XS[1Il!|][CE]{2}AT[1Il!|].*/is";
		if(preg_match($exsiccatiPat, $s)) return true;
		else return false;
	}

	private function isLichenesArticiLabel($s) {
		$articiPat = "/.*L[1Il!|][CE]H[CE]N[CE]S\\sARCT[1Il!|][CE][1Il!|].*/is";
		if(preg_match($articiPat, $s)) return true;
		else return false;
	}

	private function isHattoriTennesseeLabel($s) {
		$hattoriPat = "/.*HATT[O0Q]R[1Il!|]-T[CE]NN[CE]SS[CE]{2} [CE][O0Q]{2}PERAT[1Il!|]V[CE].*/is";
		if(preg_match($hattoriPat, $s, $mat)) return true;
		else return false;
	}

	private function doAlcanExpeditionLabel($s) {//new style labels have the AKFWS Herbarium followed by ** Flora of Alaska
		//They also have separate sections on the label for latitude and longtude
		//echo "\nDid AlcanExpeditionLabel\n";
		//$akfwsPat = "/.*ALCAN EXPEDITION(.+)/is";
		//if(preg_match($akfwsPat, $s, $ms)) $s = trim($ms[1]);
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$state_province = '';
		$recordedBy = '';
		$country = '';
		$substrate = '';
		$scientificName = '';
		//$eolPos = trim(strpos($s, "\n"));
		//$s = substr($s, $eolPos+1);//go to next line
		$location = '';
		$habitat = '';
		$elevation = '';
		//$elevationArray = $this->getElevation($s);
		//if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$elevPatStr = "/E[li1|][ce]v(?:ati[o0][nr]|\\.|\\*)?\\s((?:ab[o0]ut\\s|to\\s|ca\\.?\\s)?".
			"[OQSZl|I!&\d]{0,2},?[OQSZl|I!&\d]{2,3}(?:\\s?-\\s?[OQSZl|I!&\d]{0,2},?[OQSZl|I!&\d]{2,3})?\\s{1,2}".
			"(?:(?:ft|tt|fc)\\.?+)?)/is";
		if(preg_match($elevPatStr, $s, $mat)) $elevation = $mat[1];
		//echo "\nline 2347, Elevation: ".$elevation."\n";
		$foundState = false;
		$finishedLocation = false;
		$lookingForHabitat = false;
		$lookingForSciName = false;
		$foundSciName = false;
		$lines = explode("\n", $s);
		foreach($lines as $line) {
			$line = trim($line);
			if(!$lookingForSciName) {
				$nsfPos = stripos($line, "N.S.F. Fund");
				if($nsfPos !== FALSE) {
					$lookingForSciName = true;
					continue;
				}
			} else if(!$foundSciName) {
				if(!$this->isMostlyGarbage($line, 0.54)) {
					$line = str_replace(array("*", "'"), "", $line);
					$spacePos = strpos($line, " ");
					if($spacePos !== FALSE) {
						$temp = trim(substr($line, 0, $spacePos));
						$rest = trim(substr($line, $spacePos+1));
						$spacePos = strpos($rest, " ");
						if($spacePos !== FALSE) {
							$rest = trim(substr($rest, 0, $spacePos));
							if($this->isPossibleSciName($temp." ".$rest)) $scientificName = $temp." ".$rest;
							else if($this->isPossibleSciName($temp)) $scientificName = $temp;
						} else if($this->isPossibleSciName($temp." ".$rest)) $scientificName = $temp." ".$rest;
						else if($this->isPossibleSciName($temp)) $scientificName = $temp;
					}
					else if($this->isPossibleSciName($line)) $scientificName = $line;
				}
				$foundSciName = true;
			}
			if(!$foundState) {
				$colonPos = strpos($line, ":");
				if($colonPos !== FALSE) {
					$potentialState = trim(substr($line, 0, $colonPos));
					$sp = $this->getStateOrProvince(trim($potentialState));
					if(count($sp) > 0) {
						$state_province = $sp[0];
						$country = $sp[1];
						$location = trim(substr($line, $colonPos+1), " \t\n\r\0\x0B:;!\"\'\\~@#$%^&*_-");
						$patStr = "/(.*)Lat\\./i";
						if(preg_match($patStr, $location, $mat)) {
							$location = $mat[1];
							$finishedLocation = true;
						}
						$foundState = true;
						continue;
					}
				} else {
					$alaskaPat = "/^ALASKA\\.?+(.*)/i";
					if(preg_match($alaskaPat, $line, $mat)) {
						$state_province = "ALASKA";
						$country = "USA";
						$location = trim($mat[1], " \t\n\r\0\x0B:;!\"\'\\~@#$%^&*_-");
						$patStr = "/(.*)Lat\\./i";
						if(preg_match($patStr, $location, $mat)) {
							$location = $mat[1];
							$finishedLocation = true;
						}
						$foundState = true;
						continue;
					}
				}
			}
			if($foundState && !$finishedLocation && strlen($line) > 3) {
				$patStr = "/(.*)Lat\\./i";
				if(preg_match($patStr, $line, $mat)) {
					$location .= $mat[1];
					$finishedLocation = true;
				} else {
					$patStr = "/(.*)Long\\./i";
					if(preg_match($patStr, $line, $mat)) {
						$location .= $mat[1];
						$finishedLocation = true;
					} else {
						$patStr = "/(.*)Elev\\.?\\s/i";
						if(preg_match($patStr, $line, $mat)) {
							$location .= $mat[1];
							$finishedLocation = true;
						} else if(!$this->isMostlyGarbage($line, 0.54)) {
							if(stripos($line, "ALCAN E") === FALSE && stripos($line, "UNIVERSITY") === FALSE && stripos($line, "N.S.F") === FALSE)
								$location .= " ".trim($line, " \t\n\r\0\x0B:;!\"\'\\~@#$%^&*_-");
							//else $finishedLocation = true;
						}
					}
				}
			}
			if(strlen($elevation) > 0) {
				$elevPos = strpos($line, " ft.");
				if($elevPos !== FALSE) {
					if($foundState) {
						$temp = trim(substr(trim($line), $elevPos+4), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
						if(strlen($temp) > 2 && !$this->isMostlyGarbage($temp, 0.54)) $habitat = $temp;
						$lookingForHabitat = true;
						continue;
					}
				} else {
					$elevPos = strpos($line, $elevation);
					if($elevPos !== FALSE) {
						$temp = trim(substr(trim($line), $elevPos+strlen($elevation)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
						if(strlen($temp) > 0 && !$this->isMostlyGarbage($temp, 0.54)) $habitat = $temp;
						$lookingForHabitat = true;
						continue;
					}
				}
			}
			if($lookingForHabitat) {
				$patStr = "/(.+)(?:".$possibleMonths.")/i";
				if(preg_match($patStr, $line, $mat)) {
					$t = trim($mat[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					if(!$this->isMostlyGarbage($t, 0.54)) $habitat .= $t;
					break;
				} else if(!$this->isMostlyGarbage($line, 0.54)) $habitat .= trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			}
		}
		$patStr = "/(.*)(?:".$possibleMonths.")/i";
		while(preg_match($patStr, $habitat, $mat)) $habitat = $mat[1];
		$colPos = stripos($habitat, "John");
		if($colPos !== FALSE) $habitat = substr($habitat, 0, $colPos);
		else {
			$colPos = stripos($habitat, "Thoms");
			if($colPos !== FALSE) {
				$colPos2 = stripos($habitat, "W. Thoms");
				if($colPos2 !== FALSE) $habitat = substr($habitat, 0, $colPos2);
				else {
					$colPos2 = stripos($habitat, "W, Thoms");
					if($colPos2 !== FALSE) $habitat = substr($habitat, 0, $colPos2);
					else $habitat = substr($habitat, 0, $colPos);
				}
			} else {
				$colPos = stripos($habitat, "Teuvo");
				if($colPos !== FALSE) $habitat = substr($habitat, 0, $colPos);
			}
		}
		$latPos = stripos($habitat, "Lat.");
		if($latPos !== FALSE) $habitat = substr($habitat, 0, $latPos);
		if(strlen($elevation) > 0) {
			$elevation = str_ireplace(array("fc", "tt"), array("ft"), trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
			$ftPos = strpos($elevation, "ft");
			if($ftPos === FALSE) $elevation .= " ft.";
		}
		return array
		(

			'scientificName' => $scientificName,
			'stateProvince' => $state_province,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	private function doLichenesArticiLabel($s) {//new style labels have the AKFWS Herbarium followed by ** Flora of Alaska
		//They also have separate sections on the label for latitude and longtude
		//echo "\nDid LichenesArticiLabel\n";
		$articiPat = "/.*L[1Il!|][CE]H[CE]N[CE]S\\sARCT[1Il!|][CE][1Il!|](.+)/is";
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		if(preg_match($articiPat, $s, $ms)) $s = trim($ms[1]);
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = '';
		$country = "";
		$substrate = '';
		$habitat = '';
		$scientificName = '';
		$recordedBy = "";
		$verbatimAttributes = '';
		$location = "";
		$pos = stripos($s, "LICHENS FROM NORTHERN ALASKA");
		if($pos !== FALSE) {
			$location = "NORTHERN ALASKA";
			$country = "USA";
			$state_province = "ALASKA";
			$s = trim(substr($s, $pos+28));
		} else {
			$pos = stripos($s, "NORTHWEST TERRITORIES");
			if($pos !== FALSE) {
				$location = "NORTHWEST TERRITORIES";
				$country = "Canada";
				$state_province = "NORTHWEST TERRITORIES";
				$s = trim(substr($s, $pos+21));
			} else {
				$pos = stripos($s, "CANADIAN ARCTIC ARCHIPELAGO");
				if($pos !== FALSE) {
					$location = "CANADIAN ARCTIC ARCHIPELAGO CORNWALLIS ISLAND";
					$country = "Canada";
					$state_province = "Nunavut";
					$s = trim(substr($s, $pos+45));
				}
			}
		}
		$possibleNumbers = "[OQSZl|I!0-9]";
		$patStr = "/^".$possibleNumbers."{1,4}[.,*#]\\s(\\w.*)/is";
		$lines = explode("\n", $s);
		$foundSciName = false;
		$foundLocation = false;
		foreach($lines as $line) {
			$line = trim($line, " \t\n\r\0\x0B:;!()\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if(!$this->isMostlyGarbage($line, 0.60)) {
					if(preg_match($patStr, $line, $mat)) {
						$scientificName = trim($mat[1]);
						$foundSciName = true;
						$attPos = stripos($scientificName, " (contains ");
						if($attPos !== FALSE) {
							$verbatimAttributes = trim(substr($scientificName, $attPos+1), " \t\n\r\0\x0B,:;!()\"\'\\~@#$%^&*_-");
							$scientificName = trim(substr($scientificName, 0, $attPos));
						}
						$onPos = stripos($scientificName, " on ");
						if($onPos !== FALSE) {
							$substrate = trim(substr($scientificName, $onPos), " \t\n\r\0\x0B,:;!()\"\'\\~@#$%^&*_-");
							$scientificName = trim(substr($scientificName, 0, $onPos));
						}
					} else if(strlen($line) > 3) {
						$uniPos = stripos($line, "THE UNIVERSITY");
						if($uniPos === FALSE || $uniPos < 0) $location .= " ".trim($line);
						$location = trim($location);
					}
				}
			} else {
				$attPos = stripos($line, "(contains ");
				if($attPos !== FALSE) $verbatimAttributes = trim(substr($line, $attPos+1), " \t\n\r\0\x0B,:;!()\"\'\\~@#$%^&*_-");
				else {
					$detPos = stripos($line, "Det. ");
					if($detPos !== FALSE) $identifiedBy = trim(substr($line, $detPos+5), " \t\n\r\0\x0B,:;!()\"\'\\~@#$%^&*_-");
					else {
						if(!$foundLocation) {//haven't terminated location because of a lat/long or a date
							if(preg_match("/^((?:on|cn)\\s\\w.*+)/i", $line, $mats)) {//look for a habitat
								$habitat = trim($mats[1]);
								$atPos = strpos($habitat, " at ");
								if($atPos !== FALSE) {
									//terminate the habitat at the first occurrence of the word "at" and put the rest in the location
									$line .= " ".trim(substr($habitat, $atPos+1));
									$habitat = trim(substr($habitat, 0, $atPos));
								} else {//terminate the habitat at the first period and put the rest in the location
									$dotPos = strpos($habitat, ".");
									if($dotPos !== FALSE) {
										$line .= " ".trim(substr($habitat, $dotPos+1));
										$habitat = trim(substr($habitat, 0, $dotPos));
									}
								}
								$habitat = str_ireplace("cn ", "on ", $habitat);
							}
							if(preg_match("/(.*?)(?:".$possibleMonths.")/i", $line, $mats)) {
								//if the line contains a date put the part before the date in the location and quit looking for location
								$line = $mats[1];
								$foundLocation = true;
							}
							if(preg_match("/(.*?)".$possibleNumbers."{1,3}+\\s?°/i", $line)) {
								$pat = "/(.*?)".$possibleNumbers."{1,3}+\\s?°/i";
								while(preg_match($pat, $line, $mats)) $line = $mats[1];
								$foundLocation = true;
							}
							if(!$this->isMostlyGarbage($line, 0.60) && strlen($line) > 3) {
								$uniPos = stripos($line, "THE UNIVERSITY ");
								if($uniPos === FALSE || $uniPos < 0) $location .= " ".trim($line);
							}
						} else {
							$collPos = stripos($line, "Coll.");
							if($collPos !== FALSE) $recordedBy = trim(substr($line, $collPos+5), " \t\n\r\0\x0B,:;!()\"\'\\~@#$%^&*_-");
						}
					}
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'stateProvince' => $state_province,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			)
		);
	}

	public function processSciName($name) {//echo "\nInput to processSciName: ".$name."\n";
		if($name) {
			$name = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $name));
			$recordNumber = "";
			if(preg_match("/^No[.,*#]\\s?([UIO!SQl0-9]{1,6}[a-z]?)\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^([UIO!SQl0-9]{1,6}[a-z]?)[.,*#]\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^([0-9]{2,6}[A-Za-z]?)[.,*#]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/[0-9]{1,3}[A-Za-z]?[.,*#]\\s(.*)/", $name, $mats)) {
				$name = trim($mats[1]);
			}//echo "\nline 3081, name: ".$name.", recordNumber: ".$recordNumber."\n";
			if($this->isPossibleSciName($name)) return array('scientificName' => $name, 'recordNumber' => $recordNumber);
			else {//echo "\nline 2616, name: ".$name.", recordNumber: ".$recordNumber."\n";
				$results = array();
				$foundSciName = false;
				if(stripos($name, " strain") !== FALSE) {
					$words = array_reverse(explode(" ", $name));
					$wordcount = count($words);
					if($wordcount > 3) {
						if(strcasecmp(trim($words[0]), "strain") == 0) {
							$potentialSciName = trim($words[$wordcount-1]." ".$words[$wordcount-2]);
							if($this->isPossibleSciName($potentialSciName)) {
								$foundSciName = true;
								$results['scientificName'] = $potentialSciName;
								$results['recordNumber'] = $recordNumber;
								$results['verbatimAttributes'] = trim(substr($name, strpos($name, $potentialSciName)+strlen($potentialSciName)));
							} else if($wordcount < 5) $results['verbatimAttributes'] = $name;
						}
					}
				}
				if(!$foundSciName) {
					$potentialSciName = "";
					if(preg_match("/(.*)\\s(var[.,*#»]?|ssp[.,*#»]?|subsp[.,*#»]?)\\s(.*)/i", $name, $mats)) {
						$potentialSciName = $mats[1];
					} else if(preg_match("/(.*)\\s(v[.,*#»]|f[.,*#»])\\s(.*)/", $name, $mats)) {
						$potentialSciName = $mats[1];
					}
					if($this->isPossibleSciName($potentialSciName)) {
						$foundSciName = true;
						$results['scientificName'] = $potentialSciName;
						$results['recordNumber'] = $recordNumber;
						$temp = trim($mats[3]);
						if(preg_match("/(.*?)((?:\\s(?:Found|Common|Loose))?\\son\\s.*)/i", $temp, $mats2)) {
							$substrate = trim($mats2[2]);
							if(!preg_match("/\\b(?:HWY|Highway|road)\\b/i", $substrate)) $results['substrate'] = $substrate;
							$temp = trim($mats2[1]);
							$spacePos = strpos($temp, " ");
							if($spacePos !== FALSE) $temp = strtolower(substr(trim($temp), 0, $spacePos))." ".trim(substr($temp, $spacePos+1));
						} else {
							$spacePos = strpos($temp, " ");
							if($spacePos !== FALSE) $temp = strtolower(substr($temp, 0, $spacePos));
						}
						if(strlen($temp) > 3) {
							$results['taxonRank'] = str_replace(array("»", "*", "#", ",", "v.", "ssp"), array(".", ".", ".", ".", "var.", "subsp"), strtolower($mats[2]));
							$results['infraspecificEpithet'] = $temp;
						}
					}
				}
				if(!$foundSciName && preg_match("/(.*)(?:\+|(?:\\salong|\\stogether|\\sfound|\\sassociated)?\\swith\\s)(.+)/i", $name, $mats)) {
					$potentialSciName = trim($mats[1]);
					if($this->isPossibleSciName($potentialSciName)) {
						$foundSciName = true;
						$results['scientificName'] = $potentialSciName;
						$possibleATs = explode(",", trim($mats[2]));
						$associatedTaxa = "";
						$index = 0;
						foreach($possibleATs as $possibleAT) {
							$possibleAT = trim($possibleAT);
							if($this->isPossibleSciName($possibleAT)) {
								if($index++ == 0) $associatedTaxa = $possibleAT;
								else $associatedTaxa .= ", ".$possibleAT;
							} else if(preg_match("/(.*)\\s(on\\s.*)/i", $possibleAT, $mats2)) {
								$potentialAssTaxa = $mats2[1];
								if($this->isPossibleSciName($potentialAssTaxa)) {
									if($index++ == 0) $associatedTaxa = $potentialAssTaxa;
									else $associatedTaxa .= ", ".$potentialAssTaxa;
									$substrate = $mats2[2];
									if(!preg_match("/\\b(?:HWY|Highway|road)\\b/i", $substrate)) $results['substrate'] = $substrate;
								}
							}
						}
						if(strlen($associatedTaxa) > 0) $results['associatedTaxa'] = $associatedTaxa;
						$results['recordNumber'] = $recordNumber;
					}
				}
				if(!$foundSciName && preg_match("/(.{4,})((?:\\s(?:F[o0]und|C[o0]mm[o0]n|L[o0]{2}se|[o0]ccasi[o0]na[lI1!|]))?\\son\\s.*)/i", $name, $mats)) {
					$potentialSciName = $mats[1];
					if($this->isPossibleSciName($potentialSciName)) {
						$foundSciName = true;
						$results['scientificName'] = $potentialSciName;
						$results['recordNumber'] = $recordNumber;
						$substrate = trim($mats[2]);
						if(!preg_match("/\\b(?:HWY|Highway|road)\\b/i", $substrate)) $results['substrate'] = $substrate;
					}
				}
				if(!$foundSciName) {
					$name = str_ireplace(array("0", "1", "!", "|", "5", "2"), array("O", "l", "l", "l", "S", "Z"), $name);
					//echo "\nline 2692, name: ".$name."\n";
					if(preg_match("/^([A-Za-z.]{4,}\\s[A-Za-z.]{3,}).*/i", $name, $mats)) {
						$potentialSciName =$mats[1];
						if($this->isPossibleSciName($potentialSciName)) {
							$results['scientificName'] = $potentialSciName;
							$results['recordNumber'] = $recordNumber;
						} else {
							$potentialSciName = str_replace("q", "g", $potentialSciName);
							if($this->isPossibleSciName($potentialSciName)) {
								$results['scientificName'] = $potentialSciName;
								$results['recordNumber'] = $recordNumber;
							} else {
								$potentialSciName = str_replace(".", "", $potentialSciName);
								if($this->isPossibleSciName($potentialSciName)) {
									$results['scientificName'] = $potentialSciName;
									$results['recordNumber'] = $recordNumber;
								} else {
									$potentialSciName = str_replace("G", "C", $potentialSciName);
									if($this->isPossibleSciName($potentialSciName)) {
										$results['scientificName'] = $potentialSciName;
										$results['recordNumber'] = $recordNumber;
									}
								}
							}
						}
					}
				}
				return $results;
			}
		}
	}

	private function doHattoriTennesseeLabel($s) {
		//echo "\nDid HattoriTennesseeLabel\n";
		$pattern =
			array
			(
				"/,,/i",
				"/\\.\\./i",
				"/Col.{1,2}\\b/i",
				"/a IT. S. 'A./",
				"/8g TOUTED\\sSTATES,\\s/i",
				"/TE7TE3SX2/",
				"/Okaloose Co./i",
				"/-T.{6,9}?SSEE/i",
				"/C°ll\\./",
				"/Co\\wl\*/",
				"/COU\\./",
				"/[fl1]{5,}/",
				"/1.2X1\\sCO\\s/",
				"/I'-lEXICO/",
				"/M.{1,3}XICO/",
				"/Â£aÂ£OCOo/",
				"/t-iETvICn/",
				"/Â»/i",
				"/\\sra[co]ist,?\\s/i",
				"/\(Ac.{1,5}\)/i",
				"/(?:If|M)[ei]KICO/i",
				"/TE.{3,5}?[S35]SEE/i",
				"/TE.{2,4}?ES[SG]EE/i",
				"/TE.{2,4}?ESS[E2]E/i",
				"/U[.>]?\\s[S35]\\.?\\s(?:A\\.?|A\\w)\\s/",
				"/(?:it|[IT][IJ]|tf|TT|F)[.>]\\s[S35]\\.\\sA\\./i",
				"/(?:it|[IT][IJ]|tf|TT|F)[.>]\\s[S35]\\.\\sA\\s?/i",
				"/TENN-L-[A-Za-z0-9!?|]*/i",
				"/AL.{1,3}[S35]KA/i",
				"/Col\\.1/i",
				"/¢Coll\\./",
				"/Snyth Co\\./i"
			);
		$replacement =
			array
			(
				",",
				".",
				"Coll.",
				"USA",
				"USA, ",
				"Tennessee",
				"Okaloosa Co.",
				"-Tennessee",
				"Coll. ",
				"Coll.",
				"Coll.",
				"",
				"Mexico",
				"Mexico",
				"Mexico",
				"Mexico",
				"Mexico",
				"",
				" moist ",
				"(Ach.)",
				"Mexico",
				"Tennessee",
				"Tennessee",
				"Tennessee",
				"USA ",
				"USA.",
				"USA ",
				"",
				"Alaska",
				"Coll.",
				"Coll.",
				"Smyth Co."
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$badWordsPat =
		"/".
			"Un[l1I]v[EC]r[S5][l1I]ty|".
			"Hatt[0o]r[l1I]|".
			"B[0o]tan[l1I]cal|".
			"Lab[0o]rat[0o]ry|".
			"[EC][0o][l1I]{1,2}[EC]CTI[0o]N[S5]".
		"/i";
		$recordNumber = '';
		$possibleNumbers = "[OQSZl|I!0-9]";
		$hattoriPat = "/(.*)HATT[OQ0]R[I1l|!]-TENNE[S5][S5]EE\\sC[OQ0]{1,3}PERAT[I1l|!]VE(?:\\r\\n|\\r|\\n|\\s).{0,2}RY\\s?[OQ0][OQ0G ]E[OQ0G]GR\\s?APH\\s?[Il1|!]C\\sC[OQ0][Il1|!]{1,2}ECT[Il1|!][OQ0]N[S5][,.*]\\s1964-.{1,2}(.*)/is";
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		if(preg_match($hattoriPat, $s, $ms)) {
			$s = trim($ms[2]);
			$temp = trim($ms[1]);
			$ls = explode("\n", $temp);
			foreach($ls as $l) {
				$l = trim(preg_replace("/^(".$possibleNumbers."{1,3}+)\\s(".$possibleNumbers."{1,4}+)/", "$1$2", $l, 1));
				if(preg_match("/^(".$possibleNumbers."{1,6}\\w?)\\.?$/", $l, $rs)) {
					$recordNumber = $this->replaceMistakenNumbers($rs[1]);
					break;
				}
			}
		} else {
			$ls = explode("\n", $s);
			foreach($ls as $l) {
				$l = trim(preg_replace("/^(".$possibleNumbers."{1,3}+)\\s(".$possibleNumbers."{1,4}+)/", "$1$2", $l, 1));
				if(preg_match("/^(".$possibleNumbers."{1,6}\\w?)\\.?$/", $l, $rs)) {
					$recordNumber = $this->replaceMistakenNumbers($rs[1]);
					break;
				}
			}
		}
		//echo "\recordNumber: ".$recordNumber."\n";
		$hattoriPat = "/^.?The University of Tennessee,? and The Hattori Botanical Laboratory(.*)/is";
		if(preg_match($hattoriPat, $s, $ms)) {
			$temp = trim($ms[1]);
			if(strlen($temp) > 3) $s = $temp;
		}
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = '';
		$country = "";
		$county = "";
		$substrate = '';
		$habitat = '';
		$taxonRank = '';
		$infraspecificEpithet = '';
		$scientificName = '';
		$recordedBy = "";
		$verbatimAttributes = '';
		$associatedTaxa = '';
		$location = "";
		if(preg_match("/(.*)(?:C[0o][l1I|!]{2}[^EC]{1,2}+|Leg.)\\s?(.*)/is", $s, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 2844, mats[".$i++."] = ".$mat."\n";
			$s = trim($mats[1]);
			$rest = trim($mats[2]);
			$pos = strrpos($rest, "\n");
			if($pos !== FALSE) $rest = trim(substr($rest, $pos+1));
			$psn = $this->processSciName($rest);
			if($psn != null) {
				if(array_key_exists ('scientificName', $psn)) {
					$scientificName = $psn['scientificName'];
					$foundSciName = true;
				}
				if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
				if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
				if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
				if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
				if(array_key_exists ('recordNumber', $psn)) {
					$trn = $psn['recordNumber'];
					if(strlen($trn) > 0) $recordNumber = $trn;
				}
				if(array_key_exists ('substrate', $psn)) {
					$substrate = $psn['substrate'];
					if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
				}
			}
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) {
			$temp = $elevationArray[1];
			if(strlen($temp) > 0) {
				$elevation = $temp;
				$s = trim($elevationArray[0])." ".trim($elevationArray[2]);
			}
		}
		$foundSciName = false;
		$foundState = false;
		$lines = array_reverse(explode("\n", $s));
		$lineBefore = "";
		$lineCount = count($lines);
		$badHabitatWordsPat =
		"/".
			"Un[l1I]v[EC]r[S5][l1I]ty|".
			"Hatt[0o]r[l1I]|".
			"B[0o]tan[l1I]cal|".
			"U\\.?,?\\s?S\\.?,?\\s?A\\.?,?\\s?|".
			"United\\sStates\\s|".
			"Lab[0o]rat[0o]ry|".
			"[EC][0o][l1I]{1,2}[EC]CTI[0o]N[S5]".
		"/i";
		//echo "\nRedeemed Again:\n".$s."\n";
		if(preg_match("/(.*)(?:M|I1|IX|K|'i)E[XK]IC[0O].?[,.]?\\s([a-zA-Z015\"\']+)[.,:*#\"\'¢-]{0,2}+\\s(.*+)/is", $s, $mexMats)) {
			//$i=0;
			//foreach($mexMats as $mexMat) echo "mexMats[".$i++."]: ".$mexMat."\n";
			$temp = trim($mexMats[1], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			$temp2 = trim($mexMats[2], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			$temp3 = trim($mexMats[3], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			if(preg_match("/(.*)(?:M|I1|IX|K|'i)E[XK]IC[0o].?[,.]?\\s(\\w+)[.,:*#\"\'¢-]{0,2}+\\s(.*)/is", $temp, $mexMats2)) {
				$temp = trim($mexMats2[1], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
				$temp2 = trim($mexMats2[2], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
				$temp3 = trim($mexMats2[3], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-")." ".
				trim($mexMats[2], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-")." ".
				trim($mexMats[3], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			}
			if(strlen($temp2) < 3) {
				$spacePos = strpos($temp3, " ");
				if($spacePos !== FALSE) {
					$temp2 = trim(substr($temp3, 0, $spacePos), " \t\n\r\0\x0B:;!\"\'\\~@#$%^&*_-");
					$temp3 = trim(substr($temp3, $spacePos+1), " \t\n\r\0\x0B:;!\"\'\\~@#$%^&*_-");
				}
			}
			$state_province = $temp2;
			$location = $temp3;
			$country = "Mexico";
			if(strlen($temp) > 0) {
				$pos = strrpos($temp, "\n");
				if($pos !== FALSE) {
					$temp2 = trim(substr($temp, $pos+1));
					if(strlen($temp2) > 3 && !$this->isMostlyGarbage($temp2, 0.60) && !preg_match($badHabitatWordsPat, $temp2)) $habitat = $temp2;
					else {
						$temp2 = trim(substr($temp, 0, $pos));
						if(strlen($temp2) > 3 && !$this->isMostlyGarbage($temp2, 0.60) && !preg_match($badHabitatWordsPat, $temp2)) $habitat = $temp2;
					}
				} else if(!$this->isMostlyGarbage($temp, 0.60) && !preg_match($badHabitatWordsPat, $temp)) $habitat = $temp;
			}
			$foundState = true;
		} else if(preg_match("/.*(PH[Il1]L[Il1]PP[Il1]NES|[Il1]ND[Il1]A|JAPAN|TA[Il1]WAN)[.,:*i]?\\s(.*)/is", $s, $countryMats)) {
			$country = trim($countryMats[1], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			$location = trim($countryMats[2], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
			else if(preg_match("/(.*)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
			if(preg_match("/(.*)\\s[GC]o\\..*/", $location, $m)) {
				$temp = trim($m[1]);
				$spacePos = strrpos($temp, " ");
				if($spacePos !== FALSE) {
					$location = trim(substr($temp, 0, $spacePos));
					$county = trim(substr($temp, $spacePos+1));
				}
			}
			$foundState = true;
		} else if(preg_match("/(.*)JA[MF]A[Il1]CA[.,:*]\\s(.+)Parish(.*)/is", $s, $countryMats)) {
			$temp = trim($countryMats[1], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			$pos = strrpos($temp, "\n");
			if($pos !== FALSE) {
				$temp = trim(substr($temp, $pos), " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
				if($this->isPossibleSciName($temp)) $scientificName = $temp;
			}
			$county = trim($countryMats[2], " \t\n\r\0\x0B:;!\"\'\\~@©#$%^&*_-");
			$country = "Jamaica";
			$aArray = $this->getStateFromCounty($county, null, $country);
			if($aArray != null) $state_province = $aArray[0];
			$location = trim($countryMats[3], " \t\n\r\0\x0B:;,.!\"\'\\~@©#$%^&*_-");
			if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
			else if(preg_match("/(.*)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
			$foundState = true;
		} else if(preg_match("/(.*)\\sCo.?\\.?,?(?:\\s|\\r\\n|\\n|\\r)(.*)/is", $s, $ts)) {
			$t = $ts[1];
			$temp = trim($ts[2], " \t\n\r\0\x0B:;!\"\'\\~@#$%^&*_-");
			if(strlen($temp) > 3) {
				$location = ltrim($temp, " ,.");
				if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
				else if(preg_match("/(.*)(?:".$possibleMonths.")\\w?\\b/is", $location, $m)) $location = trim($m[1]);
			}//echo "\nline 3173, location: ".$location."\n";
			$spacePos = strrpos($t, " ");
			if($spacePos !== FALSE) {
				$token = trim(substr($t, $spacePos+1));
				$t = substr($t, 0, $spacePos);
				//echo "\nline 3178, t: ".$t.", token: ".$token."\n";
				$countyArray = $this->getCounty($token);
				if($countyArray != null) {
					if(count($countyArray) == 1) {
						$countyArray = $countyArray[0];
						$county = $countyArray['county'];
						$state_province = $countyArray['stateProvince'];
						$statePos = stripos($t, $state_province);
						if($statePos !== FALSE) {
							$rest = substr($t, $statePos+strlen($state_province));
							$t = substr($t, 0, $statePos);
							$statePos = stripos($rest, $state_province);
							if($statePos !== FALSE) {
								$t .= " ".trim(substr($rest, 0, $statePos));
								if(strlen($location) < 3) $location = substr($rest, $statePos+strlen($state_province));
							} else if(strlen($location) < 3) $location = trim($rest, " \t\n\r\0\x0B:;,!\"\'\\~@#$%^&*_-");
							$colonPos = strpos($t, ":");
							if($colonPos !== FALSE) {
								$linePos = strpos($t, "\n");
								if($linePos !== FALSE) $t = trim(substr($t, 0, $linePos));
							}
						}
						$country = $countyArray['country'];
						$foundState = true;
					} else {
						$countyArray = $this->processCArray($countyArray, $s);
						if(count($countyArray) == 1) {
							$countyArray = $countyArray[0];
							$county = $countyArray['county'];
							$state_province = $countyArray['stateProvince'];
							$statePos = stripos($t, $state_province);
							if($statePos !== FALSE) {
								$rest = substr($t, $statePos+strlen($state_province));
								$t = trim(substr($t, 0, $statePos));
								$statePos = stripos($rest, $state_province);
								if($statePos !== FALSE) {
									$t .= " ".trim(substr($rest, 0, $statePos));
									if(strlen($location) < 3) $location = substr($rest, $statePos+strlen($state_province));
								} else if(strlen($location) < 3) {
									$rest= trim($rest, " \t\n\r\0\x0B:;,!\"\'\\~@#$%^&*_-");
									if
									(
										strlen($rest) > 3 &&
										!$this->isMostlyGarbage($rest, 0.60) &&
										!preg_match($badWordsPat, $rest) &&
										!is_numeric($rest)
									) $location = $rest;
								}
								if(strpos($t, ":") !== FALSE || strpos($t, "USA") !== FALSE) {
									$linePos = strpos($t, "\n");
									if($linePos !== FALSE) $t = trim(substr($t, 0, $linePos));
									else $t = "";
								}
							}
							$country = $countyArray['country'];
							$foundState = true;
						}//echo "\nline 3016, count(countyArray): ".count($countyArray)."\n";
					}
					$pos = strrpos($t, "\n");
					$potentialHabitat = "";
					//echo "\nline 3265, t: ".$t."\n";
					if($pos !== FALSE) $potentialHabitat = ltrim(rtrim(substr($t, $pos), " \t\n\r\0\x0B:;,!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-");
					else $potentialHabitat = ltrim(rtrim($t, " \t\n\r\0\x0B:;,!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-");
					if(strlen($potentialHabitat) > 3 && !$this->isMostlyGarbage($potentialHabitat, 0.60) && !preg_match($badHabitatWordsPat, $potentialHabitat)) {
						$psn = $this->processSciName($potentialHabitat);
						if($psn != null) {//echo "\nline 3270, potentialHabitat: ".$potentialHabitat."\n";
							if(array_key_exists ('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								$foundSciName = true;
							}
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('recordNumber', $psn)) {
								$trn = $psn['recordNumber'];
								if(strlen($trn) > 0) $recordNumber = $trn;
							}
							if(array_key_exists ('substrate', $psn)) {
								$substrate = $psn['substrate'];
								if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
							}
						}
					} else if($pos !== FALSE) {
						$potentialHabitat = ltrim(rtrim(substr($t, 0, $pos), " \t\n\r\0\x0B:;,!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-");
						//echo "\nline 3290, potentialHabitat: ".$potentialHabitat."\n";
						if(!preg_match($badHabitatWordsPat, $potentialHabitat)) {
							$psn = $this->processSciName($potentialHabitat);
							if($psn != null) {//echo "\nline 3244, potentialHabitat: ".$potentialHabitat."\n";
								if(array_key_exists ('scientificName', $psn)) {
									$scientificName = $psn['scientificName'];
									$foundSciName = true;
								}
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('recordNumber', $psn)) {
									$trn = $psn['recordNumber'];
									if(strlen($trn) > 0) $recordNumber = $trn;
								}
								if(array_key_exists ('substrate', $psn)) {
									$substrate = $psn['substrate'];
									if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
								}
							}
						}
					}//echo "\nline 3312, potentialHabitat: ".$potentialHabitat."\n";
					if(!$foundSciName && !$this->isMostlyGarbage($potentialHabitat, 0.60) && !preg_match($badHabitatWordsPat, $potentialHabitat)) $habitat = $potentialHabitat;
					//echo "\nline 3314, habitat: ".$habitat."\n";
				} else {//can't find county but may be able to get the rest
					$linePos = strrpos($t, "\n");
					if($linePos !== FALSE) {
						$previousLine = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", substr($t, 0, $linePos)));
						$thisLine = trim(substr($t, $linePos));
						$spacePos = strrpos($thisLine, " ");
						if($spacePos !== FALSE) {
							$possibleState = trim(substr($thisLine, $spacePos), " \t\n\r\0\x0B:,.;!\"\'\\~@#$%^&*_-");
							$sp = $this->getStateOrProvince($possibleState);
							if($sp != null && count($sp) > 0) {
								$state_province = $sp[0];
								$country = $sp[1];
								$countryPos = stripos($thisLine, $country);
								if($countryPos !== FALSE) $thisLine = trim(substr($thisLine, 0, $countryPos));
								$foundState = true;
							}
						}
						if(strlen($thisLine) > 3 && !preg_match($badHabitatWordsPat, $thisLine) && !$this->isMostlyGarbage($thisLine, 0.60)) $habitat = $thisLine;
						$onPos = stripos($previousLine, " on ");
						if($onPos !== FALSE && !preg_match("/\\b(?:HWY|Highway|road)\\b/i", $previousLine)) {
							$substrate = trim(substr($previousLine, $onPos));
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
							$previousLine = trim(substr($previousLine, 0, $onPos));
						}
						$previousLine = ltrim(rtrim($previousLine, " \t\n\r\0\x0B:;,!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-");
						if(!preg_match($badHabitatWordsPat, $previousLine)) {
							$psn = $this->processSciName($previousLine);
							if($psn != null) {
								if(array_key_exists('scientificName', $psn)) {
									$scientificName = $psn['scientificName'];
									$foundSciName = true;
								}
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('recordNumber', $psn)) {
									$trn = $psn['recordNumber'];
									if(strlen($trn) > 0) $recordNumber = $trn;
								}
								if(array_key_exists ('substrate', $psn)) {
									$substrate = $psn['substrate'];
									if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
								}
							} else $habitat = $previousLine;
						}
					} else if(!preg_match($badHabitatWordsPat, $t)) {
						$psn = $this->processSciName($t);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								$foundSciName = true;
							}
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('recordNumber', $psn)) {
								$trn = $psn['recordNumber'];
								if(strlen($trn) > 0) $recordNumber = $trn;
							}
							if(array_key_exists ('substrate', $psn)) {
								$substrate = $psn['substrate'];
								if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
							}
						} else if(!$this->isMostlyGarbage($t, 0.60)) $habitat = $t;
					}
				}
			}
		} else {
			$i=0;
			while(strlen($lineBefore) == 0 && $i < $lineCount) $lineBefore = trim(str_replace(",,", ",", $lines[$i++]), " 0\t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$pos = strrpos($lineBefore, " ");
			if($pos !== FALSE) {
				$t = trim(substr($lineBefore, $pos+1), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
				$sp = $this->getStateOrProvince($t);
				if($sp != null && count($sp) > 0) {
					$state_province = $sp[0];
					$country = $sp[1];
					$foundState = true;
					$statePos = stripos($t, $state_province);
					if($statePos !== FALSE) {
						$location = substr($t, $statePos + strlen($state_province));
						if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
						else if(preg_match("/(.*)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
						if($this->isMostlyGarbage($location, 0.60) || is_numeric($location) || preg_match($badWordsPat, $location)) $location = "";
						$temp = trim(substr($t, 0, $statePos), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
						if(strlen($temp) > 3 && !preg_match($badHabitatWordsPat, $temp) && !$this->isMostlyGarbage($temp, 0.60)) $habitat = $temp;
					}
					$countryPos = stripos($t, $country);
					if($countryPos !== FALSE) {
						$temp = trim(substr($t, 0, $countryPos), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
						if(strlen($temp) > 3 && !preg_match($badHabitatWordsPat, $temp) && !$this->isMostlyGarbage($temp, 0.60)) $habitat = $temp;
					}
					//echo "\nline 3411, t = ".$t.", lineBefore = ".$lineBefore.", habitat = ".$habitat.", location = ".$location."\n";
				} else {//echo "\nline 3362, t = ".$t.", lineBefore = ".$lineBefore.", habitat = ".$habitat.", location = ".$location."\n";
					$pos = strrpos($lineBefore, ",");
					if($pos !== FALSE) {
						$t = trim(substr($lineBefore, $pos+1), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
						$sp = $this->getStateOrProvince($t);
						if($sp != null && count($sp) > 0) {
							$state_province = $sp[0];
							$country = $sp[1];
							$foundState = true;
							$statePos = stripos($t, $state_province);
							if($statePos !== FALSE) {
								$location = substr($t, $statePos + strlen($state_province));
								if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
								else if(preg_match("/(.*)(?:".$possibleMonths.")\\b/is", $location, $m)) $location = trim($m[1]);
								if($this->isMostlyGarbage($location, 0.60) || is_numeric($location) || preg_match($badWordsPat, $location)) $location = "";
								$temp = trim(substr($t, 0, $statePos), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
								if(strlen($temp) > 3 && !preg_match($badHabitatWordsPat, $temp) && !$this->isMostlyGarbage($temp, 0.60)) $habitat = $temp;
								//echo "\nline 3376, t = ".$t.", lineBefore = ".$lineBefore.", habitat = ".$habitat.", location = ".$location."\n";
							}
							$countryPos = stripos($t, $country);
							if($countryPos !== FALSE) {
								$temp = trim(substr($t, 0, $countryPos), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
								if(strlen($temp) > 3 && !preg_match($badHabitatWordsPat, $temp) && !$this->isMostlyGarbage($temp, 0.60)) $habitat = $temp;
							}
						}
					}
				}
				if(strlen($lineBefore) > 3 && strlen($location) < 3) {
					if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)?+(?:".$possibleMonths.")\\b/i", $lineBefore, $m)) $location = trim($m[1]);
					else $location = $lineBefore;
				} else if(strlen($lineBefore) > 3) {//echo "\nline 3039, lineBefore: ".$lineBefore.", location: ".$location."\n";
					if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)?+(?:".$possibleMonths.")\\b/i", $lineBefore, $m)) $location .= " ".trim($m[1]);
					else $location = $lineBefore." ".$location;
				}//echo "\nline 3445, t = ".$t.", lineBefore = ".$lineBefore.", habitat = ".$habitat.", location = ".$location."\n";
				if(strlen($location) > 3) {
					if($i < $lineCount) $lineBefore = trim($lines[$i++], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					$tennPos = stripos($lineBefore, "tenn-");
					if($tennPos !== FALSE) $lineBefore = trim(substr($lineBefore, 0, $tennPos));
					if(!preg_match($badHabitatWordsPat, $lineBefore)) {
						if(!$foundSciName) {
							$psn = $this->processSciName($lineBefore);
							if($psn != null) {
								if(array_key_exists ('scientificName', $psn)) {
									$scientificName = $psn['scientificName'];
									$foundSciName = true;
								}
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('recordNumber', $psn)) {
									$trn = $psn['recordNumber'];
									if(strlen($trn) > 0) $recordNumber = $trn;
								}
								if(array_key_exists ('substrate', $psn)) {
									$substrate = $psn['substrate'];
									if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
								}
							} else if(strlen($habitat) < 3 && strlen($lineBefore) > 3 && !$this->isMostlyGarbage($lineBefore, 0.60)) {//echo "\nline 3417, t = ".$t.", lineBefore = ".$lineBefore.", habitat = ".$habitat.", location = ".$location."\n";
								if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)?+(?:".$possibleMonths.")\\b/i", $lineBefore, $m)) $habitat = trim($m[1]);
								else $habitat = $lineBefore;
							}
						} else if(strlen($habitat) < 3 && strlen($lineBefore) > 3 && !$this->isMostlyGarbage($lineBefore, 0.60)) {
							if(preg_match("/(.*)(?:".$possibleNumbers."{1,2}+\\s)?+(?:".$possibleMonths.")\\b/i", $lineBefore, $m)) $habitat = trim($m[1]);
							else $habitat = $lineBefore;
						}
					}
				}
			}
			if($pos === FALSE && !$foundState) {
				$sp = $this->getStateOrProvince($lineBefore);
				if($sp != null && count($sp) > 0) {
					$state_province = $sp[0];
					$country = $sp[1];
					$lineBefore = "";
					$foundState = true;
					if($i < $lineCount) {
						$lineBefore = trim($lines[$i++], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
						if(!preg_match($badWordsPat, $lineBefore)) {
							if(!$foundSciName) {
								$psn = $this->processSciName($lineBefore);
								if($psn != null) {
									if(array_key_exists ('scientificName', $psn)) {
										$scientificName = $psn['scientificName'];
										$foundSciName = true;
									}
									if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
									if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
									if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
									if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
									if(array_key_exists ('recordNumber', $psn)) {
										$trn = $psn['recordNumber'];
										if(strlen($trn) > 0) $recordNumber = $trn;
									}
									if(array_key_exists ('substrate', $psn)) {
										$substrate = $psn['substrate'];
										if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
									}
								} else if(strlen($location) < 3 && strlen($lineBefore) > 3 && !$this->isMostlyGarbage($lineBefore, 0.60)) $location = $lineBefore;
							} else if(strlen($location) < 3 && strlen($lineBefore) > 3 && !$this->isMostlyGarbage($lineBefore, 0.60)) $location = $lineBefore;
						}
					}
					if($i < $lineCount) {
						$lineBefore = trim($lines[$i++], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
						if(!preg_match($badHabitatWordsPat, $lineBefore)) {
							if(!$foundSciName) {
								$psn = $this->processSciName($lineBefore);
								if($psn != null) {
									if(array_key_exists ('scientificName', $psn)) {
										$scientificName = $psn['scientificName'];
										$foundSciName = true;
									}
									if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
									if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
									if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
									if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
									if(array_key_exists ('recordNumber', $psn)) {
										$trn = $psn['recordNumber'];
										if(strlen($trn) > 0) $recordNumber = $trn;
									}
									if(array_key_exists ('substrate', $psn)) {
										$substrate = $psn['substrate'];
										if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
									}
								} else if(strlen($habitat) < 3 && strlen($lineBefore) > 3 && !$this->isMostlyGarbage($lineBefore, 0.60)) $habitat = $lineBefore;//"From line 3010: ".$lineBefore;//
							} else if(strlen($habitat) < 3 && strlen($lineBefore) > 3 && !$this->isMostlyGarbage($lineBefore, 0.60)) $habitat = $lineBefore;//"From line 3010: ".$lineBefore;//
						}
					}
				}
			}
			if($i < $lineCount) {
				$lineBefore = trim($lines[$i++], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				$tennPos = stripos($lineBefore, "tenn-");
				if($tennPos !== FALSE) $lineBefore = trim(substr($lineBefore, 0, $tennPos));
				if(strlen($lineBefore) > 3 && !$this->isMostlyGarbage($lineBefore, 0.60) && !preg_match($badWordsPat, $lineBefore)) {
					if(!$foundSciName) {
						$psn = $this->processSciName($lineBefore);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								$foundSciName = true;
							}
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('recordNumber', $psn)) {
								$trn = $psn['recordNumber'];
								if(strlen($trn) > 0) $recordNumber = $trn;
							}
							if(array_key_exists ('substrate', $psn)) {
								$substrate = $psn['substrate'];
								if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
							}
						} else if(strlen($location) < 6) $location = $lineBefore;
						else if(strlen($habitat) < 6) $habitat = $lineBefore;
					} else if(strlen($location) < 6) $location = $lineBefore;
					else if(strlen($habitat) < 6) $habitat = $lineBefore;
				}
				if((strlen($location) < 6 || strlen($habitat) < 6) && $i < $lineCount) {
					$lineBefore = trim($lines[$i++], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					if(strlen($lineBefore) > 3 && !preg_match($badWordsPat, $lineBefore)) {
						if(!$foundSciName) {
							$psn = $this->processSciName($lineBefore);
							if($psn != null) {
								if(array_key_exists ('scientificName', $psn)) {
									$scientificName = $psn['scientificName'];
									$foundSciName = true;
								}
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('recordNumber', $psn)) {
									$trn = $psn['recordNumber'];
									if(strlen($trn) > 0) $recordNumber = $trn;
								}
								if(array_key_exists ('substrate', $psn)) {
									$substrate = $psn['substrate'];
									if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
								}
							} else if(strlen($location) < 6) $location = $lineBefore;//"From line 3010: ".$lineBefore;//
							else if(strlen($habitat) < 6) $habitat = $lineBefore;//"From line 3010: ".$lineBefore;
						} else if(strlen($location) < 6) $location = $lineBefore;//"From line 3010: ".$lineBefore;//
						else if(strlen($habitat) < 6) $habitat = $lineBefore;//"From line 3010: ".$lineBefore;//
					}
				}
			}
		}
		if(count($lines) > 1) {
			$lineBefore = trim(str_replace(",,", ",", $lines[1]), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$tennPos = stripos($lineBefore, "tenn-");
			if($tennPos !== FALSE) $lineBefore = trim(substr($lineBefore, 0, $tennPos));
			if(!$foundState) {
				$commaPos = strrpos($lineBefore, ",");
				if($commaPos !== FALSE) {
					$t = trim(substr($lineBefore, $commaPos+1), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
					if(preg_match("/(.*)\\sCo\\.?,?\\b(.*)/is", $t, $ts)) {
						$t = $ts[1];
						$temp = trim($ts[2], " \t\n\r\0\x0B:;!\"\'\\~@#$%^&*_-");
						if(strlen($temp) > 3 && !preg_match($badWordsPat, $temp)) $location = ltrim($temp, " ,.");
					}
					$spacePos = strrpos($t, " ");
					if($spacePos !== FALSE) {
						$tokens = array_reverse(explode(" ", $t));
						$token = trim($tokens[0]);
						$token2 = "";
						if(count($tokens) > 1) $token2 = trim($tokens[1]);
						if(strlen($token2) > 0) {
							$countyArray = $this->getCounty($token." ".$token2);
							if($countyArray != null || count($countyArray) == 0) $countyArray = $this->getCounty($token);
						} else $countyArray = $this->getCounty($token);
						if(count($countyArray) == 1) {
							$countyArray = $countyArray[0];
							$county = $countyArray['county'];
							$state_province = $countyArray['stateProvince'];
							$country = $countyArray['country'];
							$foundState = true;
						} else {
							$countyArray = $this->processCArray($countyArray, $s);
							if(count($countyArray) == 1) {
								$countyArray = $countyArray[0];
								$county = $countyArray['county'];
								$state_province = $countyArray['stateProvince'];
								$country = $countyArray['country'];
								$foundState = true;
							}
						}
					}
					if(!$foundState) {
						if(preg_match("/(.*)\\sCo\\.?\\b/is", $t, $ts)) $t = $ts[1];
						$sp = $this->getStateOrProvince($t);
						if($sp != null && count($sp) > 0) {
							$state_province = $sp[0];
							$country = $sp[1];
							$foundState = true;
						} else if(strlen($lineBefore) > 3) {
							if(preg_match("/(.*)(?:U\\.?[S5]\\.?A\\.?|Un[il1!|]ted [S5]tate[S5]\\.?\\s)(.*)/i", $lineBefore, $matches)) {
								$country = "USA";
								$lineBefore = rtrim(ltrim($matches[2], " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-"));
								$dotPos = strpos($lineBefore, ".");
								if($dotPos !== FALSE) {
									$tState = substr($lineBefore, 0, $dotPos);
									if($this->isUSState($tState)) {
										$state_province = $tState;
										$lineBefore = trim(substr($lineBefore, $dotPos+1));
										$foundState = true;
									}
								}
								$pos = stripos($location, $lineBefore);
								if($pos === FALSE) {
									$pos = stripos($lineBefore, $location);
									if($pos === FALSE) $location = $lineBefore." ".$location;
									else $location = $lineBefore;
								}
							}
						}
						if(preg_match("/(.*)(?:U\\.?[S5]\\.?A\\.?|Un[il1!|]ted [S5]tate[S5]\\.?\\s)(.*)/i", $location, $matches)) {
							$location = rtrim(ltrim($matches[2], " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-"));
						}
						if(preg_match("/(.*)(?:".$state_province.")(.*)/i", $location, $matches)) {
							if(strlen($matches[2]) > strlen($matches[1])) $location = rtrim(ltrim($matches[2], " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-"));
							else $location = rtrim(ltrim($matches[1], " \t\n\r\0\x0B:;,.!\"\'\\~@#$%^&*_-"));
						}
					}
				} else if(strlen($lineBefore) > 3) {
					if(!preg_match($badWordsPat, $lineBefore)) {
						$psn = $this->processSciName($lineBefore);
						if($psn != null) {
							if(!$foundSciName) {
								if(array_key_exists ('scientificName', $psn)) {
									$scientificName = $psn['scientificName'];
									$foundSciName = true;
								}
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('substrate', $psn)) {
									$substrate = $psn['substrate'];
									if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
								}
							}
						} else if(stripos($location, $lineBefore) === FALSE) {
							if(stripos($lineBefore, $location) !== FALSE) $location = $lineBefore;
							else $location = $lineBefore." ".$location;
						}
					}
				}
			}
		}//echo "\nline 3637, lineBefore: ".$lineBefore.", location: ".$location.", habitat: ".$habitat."\n";
		if(!$foundSciName) {
			$lines = array_reverse(explode("\n", $s));
			foreach($lines as $line) {
				if(!$this->isMostlyGarbage($line, 0.60) && !preg_match($badWordsPat, $line)) {
					$line = trim($line);
					$tennPos = stripos($line, "tenn-");
					if($tennPos !== FALSE) $line = trim(substr($line, 0, $tennPos));
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) {
							$scientificName = $psn['scientificName'];
							$foundSciName = true;
						}
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('recordNumber', $psn)) {
							$trn = $psn['recordNumber'];
							if(strlen($trn) > 0) $recordNumber = $trn;
						}
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
						if($foundSciName) break;
					} else if(preg_match("/^(\\d{3,5}\\w?)\\.?(?:.*)/", $line, $m) && strlen($recordNumber) == 0) {
						$temp = trim($m[1]);
						if(!preg_match("/^19(?:.)/", $temp)) $recordNumber = trim($temp);
					}
				}
			}
		}
		$pos = stripos($location, "strain");
		if($pos !== FALSE) {
			$verbatimAttributes = substr($location, 0, $pos+6);
			$location = trim(substr($location, $pos+7));
		}
		$pos = stripos($location, $habitat);
		if($pos !== FALSE && $pos == 0) $location = trim(substr($location, strlen($habitat)));
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => $recordNumber,
			'stateProvince' => $state_province,
			'county' => $county,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			)
		);
	}

	private function formatSciName($scientificName) {
		if(strlen($scientificName) > 0) {
			$scientificName = trim($scientificName, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			$spacePos = strpos($scientificName, " ");
			if($spacePos !== FALSE) {
				$firstWord = ucfirst(strtolower(str_ireplace(array("!", "1", "|", "0", "."), array("l", "l", "l", "o", ""), trim(substr($scientificName, 0, $spacePos)))));
				$secondWord = trim(substr($scientificName, $spacePos));
				$spacePos = strpos($secondWord, " ");
				if($spacePos !== FALSE) {
					$rest = trim(substr($secondWord, $spacePos));
					$secondWord = trim(substr($secondWord, 0, $spacePos));
					return $firstWord." ".strtolower($secondWord)." ".$rest;
				} else {
					return $firstWord." ".strtolower($secondWord);
				}
			} else return ucfirst(strtolower(str_ireplace(array("!", "1", "|", "0", "."), array("l", "l", "l", "o", ""), $scientificName)));
		}
	}

	private function doNewStyleAKFWSLabel($s) {//new style labels have the AKFWS Herbarium followed by ** Flora of Alaska
		//They also have separate sections on the label for latitude and longtude
		//echo "\nDid NewStyleAKFWSLabel\n";
		$akfwsPat = "/.*\\bA\\w{3,6}\\sHE[Rs]\\s?B[\w!|\s]{4,10}\\s[\"\'*]{1,3}\\sFL[O0]\\w{1,2}A\\s[O0]\\w\\sA[1Il!|]A[S5]KA(.+)/is";
		if(preg_match($akfwsPat, $s, $ms)) $s = trim($ms[1]);
		else {
			$akfwsPat = "/.*\\bA\\wF\\w[S5]\\s[HB]E[RS]B[\w!|\s]{4,10}\\s[\"\'*]{1,3}\\sFL[O0]\\w{1,2}A\\s[O0]\\w\\sA[1Il!|]A[S5]KA(.+)/is";
			if(preg_match($akfwsPat, $s, $ms)) $s = trim($ms[1]);
		}
		$state_province = "Alaska";
		$identifiedBy = '';
		$dateIdentified = '';
		$country = "USA";
		$substrate = '';
		$scientificName = '';
		$location = trim($this->getLocality($s));
		$patStr = "/(.*)(?:L|(?:I?\\.))at[li1!|]tude/i";
		if(preg_match($patStr, $location, $mat)) $location = $mat[1];
		$habitat = '';
		$habitatArray = $this->getHabitat($s);
		if($habitatArray != null && count($habitatArray) > 0) {
			$habitat = $habitatArray[1]." ".$habitatArray[2];
			$patStr = "/(.*)[QO0]ua[ad]\\.?\\s[MH]ap/is";
			if(preg_match($patStr, $habitat, $mat)) $habitat = $mat[1];
			$patStr = "/^([0GQO]n\\s.+)/i";
			if(preg_match($patStr, $habitat, $mat)) $substrate = $this->terminateSubstrate($mat[1]);
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		//Occasionally the habitats and locations are confused in the output so check
		$possibleNumbers = "[OQSZl|I!0-9]";
		if(strlen($location) == 0) {
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°.*\\d[\"\']\\s?[EW](.*)/is";
			if(preg_match($patStr, $habitat, $mat)) {
				$location = $mat[1];
				$habitat = $mat[2];
			}
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°/is";
			while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
			$patStr = "/\\bE[l1!I][ec]vat[l1!I][o0]n:?\\s".$elevation."(.*)/is";
			if(preg_match($patStr, $habitat, $mat)) $habitat = trim($mat[1]);
		}
		$patStr = "/(.*)\\bE[l1!I][ec]vat[l1!I][o0]n:?\\s".$elevation."/is";
		if(preg_match($patStr, $habitat, $mat)) $habitat = trim($mat[1]);
		$patStr = "/(.*)(?:L|(?:\|\_))at[li1!|]tude/i";
		if(preg_match($patStr, $location, $mat)) $location = $mat[1];
		$georeferenceRemarks = '';
		if(preg_match("/[QO0]ua[ad]\\.?\\s[MH]ap(.+)/i", $s, $mat)) {
			$georeferenceRemarks = $mat[1];
			if(strpos($georeferenceRemarks, "(") !== FALSE) $georeferenceRemarks = substr($georeferenceRemarks, 0, strpos($georeferenceRemarks, "("));
			else if(strpos($georeferenceRemarks, ",") !== FALSE) $georeferenceRemarks = substr($georeferenceRemarks, 0, strpos($georeferenceRemarks, ","));
			else if(preg_match("/(.*)C[o0][li1!|]{1,2}\\.?\\sDate/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
		}
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null && count($identifier) > 0) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}
		$foundSciName = false;
		$lines = explode("\n", $s);
		foreach($lines as $line) {
			$patStr = "/(?:L[o0]cat[li1!|][o0]n|Lat[li1!|]tude|[MH]ab[li1!|]tat|".
				"[QO0]uad\\.?\\s[MH]ap|C[o0][li1!|]{1,2}[ec]{2}t[o0]r|".
				"D[ec]t\\.?|E[l1!I][ec]vat[l1!I][o0]n)\\s(.*)/i";
			if(strlen($line) > 1 && !preg_match($patStr, $line)) {
				if(!$this->isMostlyGarbage($line, 0.54)) {
					$line = str_replace(array("*", "'"), "", $line);
					$spacePos = strpos($line, " ");
					if($spacePos !== FALSE) {
						$temp = trim(substr($line, 0, $spacePos));
						$rest = trim(substr($line, $spacePos+1));
						$spacePos = strpos($rest, " ");
						if($spacePos !== FALSE) {
							$rest = trim(substr($rest, 0, $spacePos));
							if($this->isPossibleSciName($temp." ".$rest)) {
								$scientificName = $temp." ".$rest;
								break;
							}
							else if($this->isPossibleSciName($temp)) {
								$scientificName = $temp;
								break;
							}
						} else if($this->isPossibleSciName($temp." ".$rest)) {
							$scientificName = $temp." ".$rest;
							break;
						}
						else if($this->isPossibleSciName($temp)) {
							$scientificName = $temp;
							break;
						}
					}
					else if($this->isPossibleSciName($line)) {
						$scientificName = $line;
						break;
					}
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'georeferenceRemarks' => str_replace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($georeferenceRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);
	}

	private function doOldStyleAKFWSLabel($s) {//old style labels have the AKFWS Herbarium followed by Flora of Alaska (no **) on the same or next line
		//echo "\nDid OldStyleAKFWSLabel\n";
		//They have latitude and longtude in the location section
		$akfwsPat = "/.*\\bA\\w{3,6}\\sHE[Rs]\\s?B[\w!|\s]{4,10}.*FL[O0]\\w{1,2}A\\s[O0]\\w\\sA[1Il!|]A[S5]KA(.+)/is";
		if(preg_match($akfwsPat, $s, $ms)) $s = trim($ms[1]);
		else {
			$akfwsPat = "/.*\\bA\\wF\\w[S5]\\s[HB]E[RS]B[\w!|\s]{4,10}.*FL[O0]\\w{1,2}A\\s[O0]\\w\\sA[1Il!|]A[S5]KA(.+)/is";
			if(preg_match($akfwsPat, $s, $ms)) $s = trim($ms[1]);
		}
		$state_province = "Alaska";
		$identifiedBy = '';
		$dateIdentified = '';
		$country = "USA";
		$substrate = '';
		$location = trim($this->getLocality($s));
		$patStr = "/(.*)\\b[HM]ab[li1!|]tat/is";
		if(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°/is";
		while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$habitat = '';
		$habitatArray = $this->getHabitat($s);
		if($habitatArray != null && count($habitatArray) > 0) {
			$habitat = $habitatArray[1]." ".$habitatArray[2];
			$patStr = "/(.*)(?:[EC][li1!|][ec](?:va|c)t[li1!|]on|C[o0][li1!|]{2}(?:[ec]{2}t[o0]r)?):?\\s[!li|0-9]/is";
			if(preg_match($patStr, $habitat, $mat))  $habitat = $mat[1];
		}
		//Occasionally the habitats and locations are confused in the output so check
		$possibleNumbers = "[OQSZl|I!0-9]";
		if(strlen($location) == 0) {//echo "\nline 2617\n";
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°.*\\d[\"\']\\s?[EW](.*)/is";
			if(preg_match($patStr, $habitat, $mat)) {
				$location = $mat[1];
				$habitat = $mat[2];
			}
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°/is";
			while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
			$patStr = "/\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?[NS]\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?".
				"(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})\\b(.+)/is";
			//$patStr = "/\\bT\\s?(?:".$possibleNumbers."{1,3})\\s?[NS]\\.?,?(?:\\s|\\n|\\r\\n)R\\s?".
			//	"(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})(.*)/is";
			if(preg_match($patStr, $habitat, $mat)) $habitat = trim($mat[1]);
		} else {
			$patStr = "/(.*)\\bT\\s?(?:".$possibleNumbers."{1,3})\\s?[NS]\\.?,?(?:\\s|\\n|\\r\\n)R\\s?".
				"(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})/is";
			if(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		}
		$patStr = "/^([0GQO]n\\s.+)/i";
		if(preg_match($patStr, $habitat, $mat)) $substrate = $this->terminateSubstrate($mat[1]);
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$georeferenceRemarks = '';
		if(preg_match("/(?:M|H|IX|fl|Il)ap\/[QO0G]ua[ad]\\.?(.+)/i", $s, $mat)) {
			$georeferenceRemarks = "Quad Map: ".$mat[1];
			if(strpos($georeferenceRemarks, "(") !== FALSE) $georeferenceRemarks = substr($georeferenceRemarks, 0, strpos($georeferenceRemarks, "("));
			else if(strpos($georeferenceRemarks, ",") !== FALSE) $georeferenceRemarks = substr($georeferenceRemarks, 0, strpos($georeferenceRemarks, ","));
			else {
				if(preg_match("/(.*)[0OGD]uad/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
				else if(preg_match("/(.*)[0OD]at[ec]/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
			}
		}
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null && count($identifier) > 0) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}
		return array
		(
			'stateProvince' => $state_province,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'georeferenceRemarks' => str_replace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($georeferenceRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);
	}

	private function terminateSubstrate($sub) {
		$inPos = stripos($sub, " in ");
		if($inPos !== FALSE) return substr($sub, 0, $inPos);
		else {
			$inPos = stripos($sub, " - ");
			if($inPos !== FALSE) return substr($sub, 0, $inPos);
			else {
				$inPos = stripos($sub, " at ");
				if($inPos !== FALSE) return substr($sub, 0, $inPos);
				else {
					$inPos = stripos($sub, " by ");
					if($inPos !== FALSE) return substr($sub, 0, $inPos);
					else {
						$inPos = stripos($sub, " on ");
						if($inPos !== FALSE) return substr($sub, 0, $inPos);
						else {
							$inPos = stripos($sub, " over ");
							if($inPos !== FALSE) return substr($sub, 0, $inPos);
							else {
								$temp = trim(substr($sub, 3));
								$spacePos = strpos($temp, " ");
								if($spacePos !== FALSE) return "on ".trim(substr($temp, 0, $spacePos));
								else return "on ".$temp;
							}
						}
					}
				}
			}
		}
		return $sub;
	}

	private function doUSFishAndWildLifeServiceLabel($s) {//these labels have Flora of Alaska at the top and U.S. Fish and Wildlife Service
		//towards the bottom (no AKFWS). They have Location:, Lat/Long:, Elevation:, Site: and Coll:, Det: labels
		//echo "\nDid USFishAndWildLifeServiceLabel\n";
		//echo "\nline 2721, s: ".$s."\n\n";
		$akfwsPat = "/FL[O0][RB]A\\s[O0]\\w\\sA[1Il!|]A[S5]KA.*(?:\\n|\\r\\n)((?s).*)/i";
		if(preg_match($akfwsPat, $s, $mat)) $s = trim($mat[1]);
		//echo "\nline 2724, s: ".$s."\n\n";
		$state_province = "Alaska";
		$identifiedBy = '';
		$dateIdentified = '';
		$country = "USA";
		$substrate = '';
		$location = trim($this->getLocality($s));
		$patStr = "/(.*)\\bLat\/Long/is";
		if(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°/is";
		while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$habitat = '';
		$habitatArray = $this->getHabitat($s);
		if($habitatArray != null && count($habitatArray) > 0) {
			$habitat = $habitatArray[1]." ".$habitatArray[2];
			$patStr = "/(.*)C[0o][li!1|]{2}:?/is";
			if(preg_match($patStr, $habitat, $mat))  $habitat = $mat[1];
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$patStr = "/(.*)\\bS[il1!|]t[ec]:?/is";
		if(preg_match($patStr, $elevation, $mat)) $elevation = trim($mat[1]);
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null && count($identifier) > 0) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}

		return array
		(
			'stateProvince' => $state_province,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);
	}

	private function isLendemerLichenHerbariumLabel($s) {
		$lendemerPat = "/.*L[1Il!|]ch[CE]n H[CE]rbar[1Il!|]um [O0Q]f Jam[CE]s [CE]\\.? L[CE]nd[CE]m[CE]r.*/is";
		if(preg_match($lendemerPat, $s)) return true;
		else return false;
	}

	private function isLichensOfLabel($s) {
		$lichensOfPat = "/.*(?:Macro)?(?:U|L[1Il!|]|[1Il!|])ch[CE]ns\\s(?:[O0Q]F|FR[O0Q]M)\\b.*/is";
		if(preg_match($lichensOfPat, $s)) return true;
		else if(preg_match("/.*hens\\s(?:[O0Q]F|FR[O0Q]M)\\b.*/is", $s)) return true;
		else return false;
	}

	private function isLichensOfYellowStoneNPLabel($s) {
		$montanaPat = "/.*(?:U|L[1Il!|]|[1Il!|])ch[CE]ns\\s[O0Q]F\\sYell[O0Q]wstone\\sNat[1Il!|][O0Q]na[1Il!|]\\sPark.*/is";
		if(preg_match($montanaPat, $s)) return true;
		else {
			$lichensOfPat = "/.*hens\\s[O0Q]F\\sYell[O0Q]wstone\\sNat[1Il!|][O0Q]na[1Il!|]\\sPark.*/is";
			if(preg_match($lichensOfPat, $s)) return true;
			else return false;
		}
	}

	private function isLichensOfMontanaLabel($s) {
		$montanaPat = "/.*(?:U|L[1Il!|]|[1Il!|])ch[CE]ns\\s[O0Q]F\\sM[O0Q]ntana.*/is";
		if(preg_match($montanaPat, $s)) return true;
		else {
			$lichensOfPat = "/.*hens\\s[O0Q]F\\sM[O0Q]ntana.*/is";
			if(preg_match($lichensOfPat, $s)) return true;
			else return false;
		}
	}

	private function isLichenFloraOfAlaskaLabel($s) {//there are 2 kinds of FloraOfAlaska labels: The ones with AKFWS herbarium
		//and those with a Lat/Long label
		$akfwsPat = "/.*L[1Il!|][CE]H[CE]N\\s?FL[O0Q]\\wA[.,]?\\s[O0Q]\\w\\sA[1Il!|]A[S5]KA.*/is";
		if(preg_match($akfwsPat, $s)) return true;
		else return false;
	}

	private function isMycologicalCollectionsLabel($s) {
		$mycPat = "/.*MYC[O0Q]LOG[1Il!|]CAL\\s?C[O0Q][1Il!|]{2}ECT[1Il!|][O0Q]NS(.*)/is";
		if(preg_match($mycPat, $s, $matches)) {
			$t = trim($matches[1]);
			//echo "\nline 3914, t:\n".$t."\n";
			if(strcasecmp(substr($t, 0, 3), "OF ") != 0) return true;
		}
		return false;
	}

	private function doMassMycologicalCollectionsLabel($s) {
		$otherCatalogNumbers = '';
		$recordNumber = '';
		if(preg_match("/^.{0,3}A[CG]\\s(.{3,7})\n/is", $s, $mats)) $otherCatalogNumbers = "Accession Number: ".$this->replaceMistakenNumbers(trim($mats[1]));
		if(preg_match("/^.{0,3}N[O0Q][,.]\\s(.{2,4})\n/is", $s, $mats)) $recordNumber = $this->replaceMistakenNumbers(trim($mats[1]));
		$mycPat = "/MYC[O0Q]LOG[1Il!|]CAL\\s?C[O0Q][1Il!|]{2}ECT[1Il!|][O0Q]NS(.*)/is";
		if(preg_match($mycPat, $s, $matches)) $s = trim($matches[1]);
		$pattern =
			array
			(
				"/\\b.{1,2}eptogium\\b/i",
				"/squamo\\s?s[1Il!|]\\s?[s35]{2}[1Il!|]ma/i",
				"/IIo. ^16\\s/",
				"/No. Ajö\\s/",
				"/Po5^7\\s/",
				"/\\bPar.{1,2}elia\\s/i",
				"/\\b.axat[1Il!|]{2,3}s\\b/i",
				"/\\bcristate[1Il!|]{2}.?\\s/i",
				"/\\b[GC].?raph[1Il!|][s5]\\s/i",
				"/Hu\\s?t\\s?c\\s?h\\s?in\\s?s\\s?o\\s?n/i",
				"/\\s(C[O0Q][1Il!|]{2}[:;,.*#]\\s?.*)/i",
				"/.[1Il!|]adon[1Il!|]a/i",
				"/\\b[PF]hy.{2}[1Il!|]a\\b/i",
				"/\\s(N[O0Qo][:;,.#*]\\s.+)/",
				"/\\s([DB]e[tc]\\.[:;]\\s.+)/",
				"/Donated to NY in .*/i",
				"/[1Il!|]{4}N[O0Q][1Il!|]S\\sNATURA[1Il!|]\\sH[1Il!|][S5]T[O0Q]RY\\s[S5]URVEY\\s\([1Il!|]{3}[S5].{0,3}$/im",
				"/New\\s?[YV][O0Q]rk\\s?B[O0Q]tan[ij]cal\\s?Garden/i"
			);
		$replacement =
			array
			(
				"Leptogium",
				"squamosissima",
				"No. 516 ",
				"No. 438 ",
				"No. 567 ",
				"Parmelia ",
				"saxatilis",
				"cristatella ",
				"Graphis ",
				"Hutchinson",
				"\n\${1}",
				"Cladonia",
				"Physcia",
				"\n\${1}",
				"\n\${1}",
				"",
				"",
				""
			);
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 3976, s:\n".$s."\n";
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = '';
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null && count($identifier) > 0) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}
		$county = "";
		$country = "";
		$substrate = '';
		$scientificName = '';
		$associatedTaxa = '';
		$location = "";
		$habitat = '';
		$elevation = '';
		$countyMatches = $this->findCounty($s, "");
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 4214, countyMatches[".$i++."] = ".$countyMatche."\n";
			$county = trim($countyMatches[2]);
			$country = trim($countyMatches[3]);
			$sp = $this->getStateOrProvince(trim($countyMatches[5]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			} else {
				$sp = $this->getStateOrProvince(trim($countyMatches[4]));
				if(count($sp) > 0) {
					$state_province = $sp[0];
					$country = $sp[1];
				}
			}
		}
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$recordedBy = '';
		$recordedById = '';
		$lines = explode("\n", $s);
		$foundSciName = false;
		foreach($lines as $line) {//echo "\nline 4018, line: ".$line."\n";
			$line = trim($line ," ,;:?^");
			if(!$foundSciName) {
				if(preg_match("/^.{0,4}[;:,.*]?\\s([1-9](?:[0-9]){0,3})\\s(.*)/m", $line, $mats)) {
					if(strlen($recordNumber) == 0) $temp =trim($mats[2]);
					$recordNumber = trim($mats[1]);
					if(strlen($temp) > 12) {
						$psn = $this->processSciName($temp);
						if($psn != null) {
							if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
							if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
							if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
							$foundSciName = true;
							$pos = stripos($temp, $scientificName);
							if($pos !== FALSE) $temp = substr($temp, 0, $pos);
							if(strlen($temp) > 3) $line = $temp;
							else continue;
						}
					}
				} else {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
						if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
						$foundSciName = true;
						$pos = stripos($line, $scientificName);
						if($pos !== FALSE) $line = substr($line, 0, $pos);
						if(strlen($line) <= 3) continue;
					}
				}
			}
			if(preg_match("/[CO][O0Q][1Il!|]{2}[,.;:#*]\\s{1,2}(&\\sex\\.?\\sherb\\.?|&\\s(?:d|cl)et[,.]?(?:\\sby\\b)?)?(.*)/i", $line, $mats)) {
				$temp = "";
				$midpart = "";
				if(count($mats) == 3) {
					$midpart = trim($mats[1]);
					$temp = trim($mats[2]);
				} else if(count($mats) == 2) $temp = trim($mats[1]);
				if(strlen($temp) > 6) {
					$recordedBy = $temp;
					$terms = array_reverse(explode(" ", $recordedBy));
					if(count($terms) > 2 || substr_count($recordedBy, ".") > 1) {
						$lastTerm = $terms[0];
						if(strlen($lastTerm) == 1 && count($terms) > 3) $lastTerm = $terms[1];
						if($this->containsNumber($lastTerm)) {
							$recordNumber = $lastTerm;
							$recordedBy = trim(substr($recordedBy, 0, stripos($recordedBy, $lastTerm)), " #*");
						} else if(strlen($lastTerm) > 2 && strcasecmp(substr($lastTerm, 0, 3), "s.n") == 0) {
							$recordNumber = "";
							$recordedBy = trim(substr($recordedBy, 0, stripos($recordedBy, $lastTerm)));
						}
					}
					if(strcmp($recordedBy, "Wesley Gillis Hutchinson") == 0) {
						$recordedBy = "William Hutchinson";
						$recordedById = "9959";
					} else if(preg_match("/(.*)\\sDet.?\\b/i", $recordedBy, $mats)) $recordedBy = trim($mats[1]);
					if(stripos($midpart, "Det") !== FALSE) $identifiedBy = $recordedBy;
				}
				continue;
			}
			if(preg_match("/^on\\s.+/i", $line)) {
				$substrate = $line;
				continue;
			} else if(preg_match("/^at\\s.+/i", $line)) $location = $line;
		}
		if(strlen($recordedBy) == 0) {
			$collectorInfo = $this->getCollector($s);
			if($collectorInfo != null) {
				if(array_key_exists('collectorName', $collectorInfo)) {
					$recordedBy = $collectorInfo['collectorName'];
					if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
					if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
					if(array_key_exists('identifiedBy', $collectorInfo) && strlen($identifiedBy) == 0) $identifiedBy = $collectorInfo['identifiedBy'];
					if(array_key_exists('otherCatalogNumbers', $collectorInfo) && strlen($otherCatalogNumbers) == 0) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				}
			}
		}
		if(strlen($substrate) > 0) {
			if(preg_match("/(.+)\\s((?:near|along|at|behind)\\s.{6,})/", $substrate, $mats)) {
				$substrate = trim($mats[1]);
				$location = trim($mats[2]);
			}
			if(preg_match("/(.+?)\\s(in\\s.{3,}+)/", $substrate, $mats)) {
				$temp = trim($mats[2]);
				if(!is_numeric($temp)) {
					$substrate = trim($mats[1]);
					$habitat = $temp;
				}
			}
		}
		if(strcmp($recordedBy, "Wesley Gillis Hutchinson") == 0) {
			$recordedBy = "William Hutchinson";
			$recordedById = "9959";
		} else if(preg_match("/^Det[,.;:]?$/i", trim($recordedBy))) $recordedBy = "";
		if(preg_match("/(.*)[#*].*/", $identifiedBy, $mats)) $identifiedBy = trim($mats[1]);

		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'infraspecificEpithet' => $infraspecificEpithet,
			'taxonRank' => $taxonRank,
			'verbatimAttributes' => $verbatimAttributes,
			'otherCatalogNumbers' => $otherCatalogNumbers,
			'associatedTaxa' => $associatedTaxa,
			'recordNumber' => $recordNumber,
			'recordedBy' => $recordedBy,
			'recordedById' => $recordedById,
			'county' => $county,
			'stateProvince' => $state_province,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);

	}

	public function doMycologicalCollectionsLabel($s) {
		$mycPat = "/(.*)MYC[O0Q]LOG[1Il!|]CAL\\s?C[O0Q][1Il!|]{2}ECT[1Il!|][O0Q]NS(.*)/is";
		if(preg_match($mycPat, $s, $matches)) {
			$t = trim($matches[1]);
			if(preg_match("/UN[1Il!|]VER[S5][1Il!|]TY\\s?[O0Q]F\\s?MA[S5]{2}ACHU[S5]ETT[S5]/i", $t)) return $this->doMassMycologicalCollectionsLabel($s);
			$s = trim($matches[2]);
		}//echo "\nDoing MycologicalCollectionsLabel\n";
		$pattern =
			array
			(
				"/\\s([DB]e[tc]\\.[:;]\\s.+)/",
				"/\\s((?:L|\|_|\|-)[O0Q]cati[O0Q]n[:;]\\s.*)/i",
				"/-((?:L|\|_|\|-)[O0Q]cati[O0Q]n[:;]\\s.*)/i",
				"/\\s(C[O0Q][1Il!|]{2}[ec]{2}t[1Il!|][O0Q]n\\sS[1Il!|]te[:;]\\s?.*)/i",
				"/\\s(Date[:;]\\s.*)/i",
				"/^Bet\\.[:;]\\s/m",
				"/Tucker.?man/i",
				"/,U\\.?\\s?S\\.?\\s?A\\.?/i",
				"/Lethariacolumbiana[^ ]/",
				"/Lethariacolumbiana/",
				"/Heterodermias.uamu[1Il!|]osa.Dettel[,.]\)/",
				"/Cladoniafurcata\\s/i",
				"/Cladoniafurcata\(/i",
				"/FloridaDade\\s/i",
				"/[,.]\\sCana.{1,3}/i",
				"/[,.]\\sCinad.{1,2}/i",
				"/[1Il!|]{4}N[O0Q][1Il!|]S\\sNATURA[1Il!|]\\sH[1Il!|][S5]T[O0Q]RY\\s[S5]URVEY\\s\([1Il!|]{3}[S5].{0,3}$/im"
			);
		$replacement =
			array
			(
				"\n\${1}",
				"\n\${1}",
				"-\n\${1}",
				"\n\${1}",
				"\n\${1}",
				"Det.: ",
				"Tuckerman",
				", U.S.A.",
				"Letharia columbiana ",
				"Letharia columbiana",
				"Heterodermia squamulosa (Degel.)",
				"Cladonia furcata ",
				"Cladonia furcata (",
				"Florida, Miami Dade ",
				", Canada",
				", Canada",
				""
			);
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 4079, s:\n".$s."\n";
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = '';
		$county = "";
		$country = "";
		$substrate = '';
		$scientificName = '';
		$associatedTaxa = '';
		$location = "";
		$habitat = '';
		$elevation = '';
		$otherCatalogNumbers = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$recordedBy = '';
		$recordedById = '';
		$recordNumber = '';
		$lines = explode("\n", $s);
		$foundSciName = false;
		$needMoreLocation = true;
		foreach($lines as $line) {//echo "\nline 4107, line: ".$line."\n";
			$line = trim($line ," ;:?^");
			if(!$foundSciName) {
				if(preg_match("/.?UNGUS[;:,.]\\s?+(.*)/i", $line, $mats)) {
					$temp =trim($mats[1]);
					if(strlen($temp) > 12) {
						$scientificName = $temp;
						$foundSciName = true;
						continue;
					}
				} else {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
						if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
						$foundSciName = true;
						continue;
					}
				}
			}
			if(preg_match("/^New\\sY[O0Q]rk\\sB[O0Q]tanical\\sGarden/i", $line)){
				if($needMoreLocation && strlen($location) > 0) $needMoreLocation = false;
				continue;
			} else if(preg_match("/(.+)New\\sY[O0Q]rk\\sB[O0Q]tanical\\sGarden$/i", $line, $mats)) $line = trim($mats[1]);
			//echo "\nline 4136, line: ".$line."\n";
			if(preg_match("/^De[a-zA-Z][,.][;:]\\s?+(.+)/i", $line, $mats)) {
				$temp = trim($mats[1]);
				if(strlen($temp) > 6) {
					$identifiedBy = $temp;
					continue;
				}
			} else if(preg_match("/^D[a-zA-Z]t[,.][;:]\\s?+(.+)/i", $line, $mats)) {
				$temp = trim($mats[1]);
				if(strlen($temp) > 6) {
					$identifiedBy = $temp;
					continue;
				}
			}
			if(preg_match("/H[O0Q]s[tl1]\\s?[O0Q]r\\s?[s5]ubstrate[;:!]\\s?(.*)/i", $line, $mats)) {
				$temp = trim($mats[1]);
				if(strlen($temp) > 6) {
					$substrate = trim($temp, " ,;:");
					$attWords = array("cortex", "medulla", "K+", "K-", "P+", "P-", "Podetia", "apothecia", "Thallus");
					$withPos = stripos($substrate, " with ");
					if($withPos !== FALSE) {
						$temp = trim(substr($substrate, $withPos+6), " .,;:");
						if(strlen($temp) > 6) {
							$dotPos = strpos($temp, ".");
							$potSciName = "";
							if($dotPos !== FALSE) {
								$temp2 = trim(substr($temp, 0, $dotPos));
								if(strlen($temp2) > 3) {
									$potSciName = $temp2;
									$temp = trim(substr($temp, $dotPos+1));
								} else $potSciName = $temp;
							} else $potSciName = $temp;
							$psn = $this->processSciName($potSciName);
							if($psn != null) {
								if(array_key_exists ('scientificName', $psn) && strlen($associatedTaxa) == 0) $associatedTaxa = $psn['scientificName'];
								$substrate = substr($substrate, 0, $withPos);
								if(strlen($temp) > 3) {
									foreach($attWords as $attWord) {
										if(stripos($temp, $attWord) !== FALSE) $verbatimAttributes = $temp;
									}
								}
							}
						}
					}
					if(strlen($verbatimAttributes) == 0) {
						$pos = strrpos($substrate, ".");
						if($pos !== FALSE) {
							$temp1 = trim(substr($substrate, 0, $pos));
							$temp2 = trim(substr($substrate, $pos+1));
							if(strlen($temp1) > 3) {
								foreach($attWords as $attWord) {
									if(stripos($temp2, $attWord) !== FALSE) {
										$verbatimAttributes = $temp2;
										$substrate = $temp1;
										break;
									}
								}
							}
						}
					}
				}
				continue;
			}
			if(preg_match("/C[O0Q][1Il!|]{2}ect[1Il!|][O0Q]n\\sS[1Il!|]te[;:]\\s?(.*)/i", $line, $mats)) {
				$temp = trim($mats[1]);
				if(strlen($temp) > 6) {
					$location = $temp;
					continue;
				}
			}
			if(preg_match("/(?:L|\|_|\|_)[O0Q]cati[O0Q]n[;:]\\s?(.*)/i", $line, $mats)) {
				$line = trim($mats[1]);
				$countyMatches = $this->findCounty($line, "");
				if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 4214, countyMatches[".$i++."] = ".$countyMatche."\n";
					$county = trim($countyMatches[2]);
					$country = trim($countyMatches[3]);
					$sp = $this->getStateOrProvince(trim($countyMatches[5]));
					if(count($sp) > 0) {
						$state_province = $sp[0];
						$country = $sp[1];
					} else {
						$sp = $this->getStateOrProvince(trim($countyMatches[4]));
						if(count($sp) > 0) {
							$state_province = $sp[0];
							$country = $sp[1];
						}
					}
				} else {
					$sp = $this->getStateOrProvince(trim($line));
					if(count($sp) > 0) {
						$state_province = $sp[0];
						$country = $sp[1];
					} else {
						$terms = array_reverse(explode(",", $line));
						if(count($terms) > 1) {
							$sp = $this->getStateOrProvince(trim($terms[0]));
							if(count($sp) > 0) {
								$state_province = $sp[0];
								$country = $sp[1];
							} else {
								$term1 = trim($terms[1]);
								$dotPos = strrpos($term1, ".");
								if($dotPos !== FALSE) {
									$term1 = trim(substr($term1, $dotPos));
									$commaPos = strrpos($term1, ",");
									if($dotPos !== FALSE) $term1 = trim(substr($term1, $commaPos));
								}
								$sp = $this->getStateOrProvince($term1);
								if(count($sp) > 0) {
									$state_province = $sp[0];
									$country = $sp[1];
								}
							}
						}
					}
				}
				if($needMoreLocation && strlen($location) > 0) $needMoreLocation = false;
				continue;
			}
			if(preg_match("/C[O0Q][1Il!|]{2}ect[O0Q]r[;:]\\s(.*)/i", $line, $mats)) {
				$temp = trim($mats[1]);
				if(strlen($temp) > 6) {
					$recordedBy = $temp;
					$terms = array_reverse(explode(" ", $recordedBy));
					if(count($terms) > 2) {
						$lastTerm = $terms[0];
						if(strlen($lastTerm) == 1 && count($terms) > 3) $lastTerm = $terms[1];
						if($this->containsNumber($lastTerm)) {
							$recordNumber = $lastTerm;
							$recordedBy = trim(substr($recordedBy, 0, stripos($recordedBy, $lastTerm)));
						} else if(strlen($lastTerm) > 2 && strcasecmp(substr($lastTerm, 0, 3), "s.n") == 0) {
							$recordNumber = "";
							$recordedBy = trim(substr($recordedBy, 0, stripos($recordedBy, $lastTerm)));
						}
					}
					if(strcmp($recordedBy, "Wesley Gillis Hutchinson") == 0) {
						$recordedBy = "William Hutchinson";
						$recordedById = "9959";
					}
				}
				if($needMoreLocation && strlen($location) > 0) $needMoreLocation = false;
				continue;
			}
			if(preg_match("/^A[ce]{3}[s5]{2}[1Il!|][O0Q]n\\sNu.?mber[;:]\\s(.+)/i", $line, $mats)) {
				$temp = trim($mats[1], " .,;:_");
				if(is_numeric($temp)) $otherCatalogNumbers = "Accession Number: ".$temp;
				if($needMoreLocation && strlen($location) > 0) $needMoreLocation = false;
				continue;
			} else if(preg_match("/(.+)\\sA[ce]{3}[s5]{2}[1Il!|][O0Q]n\\sNu.?mber[;:]\\s(.+)/i", $line, $mats)) {
				$temp = trim($mats[2], " .,;:_");
				if(is_numeric($temp)) $otherCatalogNumbers = "Accession Number: ".$temp;
				if($needMoreLocation && strlen($location) > 0) $needMoreLocation = false;
				$line = trim($mats[1]);
			}
			if($needMoreLocation && strlen($location) > 0) {
				$location .= " ".$line;
				$needMoreLocation = false;
				continue;
			}
		}
		if(strlen($substrate) > 0 && preg_match("/(.+?)\\s(in\\s.{3,}+)/i", $substrate, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 4419, mats[".$i++."] = ".$mat."\n";
			$temp = trim($mats[2]);
			if(!is_numeric($temp)) {
				$substrate = trim($mats[1]);
				$habitat = $temp;
				if(preg_match("/(.+)[,.](.+)/", $habitat, $mats2)) {
					$temp = trim($mats2[1]);
					$temp2 = trim($mats2[2]);
					if(preg_match("/(.*(?:cortex|medulla|Podetia|apothecia|thallus|[KPC][+-]).*)/i", $temp2)) {
						$verbatimAttributes = $temp2;
						$habitat = $temp;
						if(preg_match("/(.+)([,.])(.+)/", $verbatimAttributes, $mats3)) {
							$temp = $mats3[1];
							$temp2 = $mats3[3];
							if(!preg_match("/(.+(?:cortex|medulla|Podetia|apothecia|thallus|[KPC][+-]).*)/i", $temp)) {
								$habitat .= $mats3[2].$temp;
							}
						}
						if(preg_match("/(.+)([,.])(.+)/", $habitat, $mats3)) {
							$temp = $mats3[1];
							$temp2 = $mats3[3];
							if(preg_match("/(.+(?:cortex|medulla|Podetia|apothecia|thallus|[KPC][+-]).*)/i", $temp2)) {
								$verbatimAttributes = $temp2.$mats3[2].$mats3[2];
							}
						}
					}
				}
			}
		}

		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'infraspecificEpithet' => $infraspecificEpithet,
			'taxonRank' => $taxonRank,
			'verbatimAttributes' => $verbatimAttributes,
			'otherCatalogNumbers' => $otherCatalogNumbers,
			'associatedTaxa' => $associatedTaxa,
			'recordNumber' => $recordNumber,
			'recordedBy' => $recordedBy,
			'recordedById' => $recordedById,
			'county' => $county,
			'stateProvince' => $state_province,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);
	}

	private function doLendemerLichenHerbariumLabel($s) {
		//echo "\nDid USFishAndWildLifeServiceLabel\n";
		$lendemerPat = "/(.*)L[1Il!|]ch[CE]n H[CE]rbar[1Il!|]um [O0Q]f Jam[CE]s [CE]\\.? L[CE]nd[CE]m[CE]r.*?(?:\\r\\n|\\n\\r|\\n)(.*)/is";
		$firstPart = "";
		if(preg_match($lendemerPat, $s, $mat)) {
			$firstPart = trim($mat[1]);
			$s = trim($mat[2]);
		}
		$s = preg_replace("/C[O0]\\wN\\wY/s", "COUNTY", $s);
		//echo "\nline 3954, s: ".$s."\n\n";
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = '';
		$county = "";
		$country = "";
		$substrate = '';
		$scientificName = '';
		$associatedTaxa = '';
		$location = "";
		$habitat = '';
		$elevation = '';
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$recordNumber = '';
		$lineIndex = 0;
		$lines = explode("\n", $s);
		$foundSciName = false;
		foreach($lines as $line) {
			if($lineIndex < 3) {
				if(!preg_match("/.*(?:D.{1,2}P[1Il!|]{1,2}[CE]AT[CE]|HB\\.?\\s[1Il!|][CE]ND[CE]M[CE]R).*/i", $line)) {
					//echo "\nline 3965, Maybe Sci Name: ".$line."\n";
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
						$s = trim(substr($s, stripos($s, $line) + strlen($line)));
						break;
					}
				} else $s = trim(substr($s, stripos($s, $line) + strlen($line)));
			} else {
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
					if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
					if(array_key_exists ('substrate', $psn)) {
						$substrate = $psn['substrate'];
						if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
					}
					break;
				}
			}
			$lineIndex++;
		}
		$countyMatches = $this->findCounty($s, "");
		if($countyMatches != null) {
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			$location = ltrim($countyMatches[4], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$county = trim($countyMatches[2]);
			$country = trim($countyMatches[3]);
			$sp = $this->getStateOrProvince(trim($countyMatches[5]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
			//echo "\nline 4024, location: ".$location."\n";
			if(strlen($location) > 0) {
				if(preg_match("/(.*)C[o0][li!|]{1,2}[ec]{1,2}t[li!|][o0]n\\sdata[,.:;]?\\s(.*)/is", $location, $locMats)) {//echo "\nline 4026, It's a match\n";
					$location = trim($locMats[1]);
				}
				//echo "\nline 4029, location: ".$location."\n";
				if(preg_match("/(.*?)Ass[o0]c[,.]?\\sspp[,.]?(.*)/is", $location, $locMats2)) {
					$location = trim($locMats2[1]);
					if(strlen($associatedTaxa) == 0) $associatedTaxa = trim($locMats2[2]);
					$thallusPos = strpos($associatedTaxa, "Thallus");
					if($thallusPos !== FALSE) {
						if(strlen($verbatimAttributes) == 0) {
							$verbatimAttributes = ltrim(trim(substr($associatedTaxa, $thallusPos)), " :.-");
							$associatedTaxa = ltrim(trim(substr($associatedTaxa, 0, $thallusPos)), " :.-");
						}
					}
				}
				if(preg_match("/(.*?)-\\s?UTM.*?\\s?-\\s?(.*)/is", $location, $locMats2)) {//echo "\nline 4034, It's a match\n";
					//$i=0;
					//foreach($locMats2 as $locMat) echo "\nlocMats2[".$i++."] = ".$locMat."\n";
					$location = trim($locMats2[1]);
					$habitat = ltrim(trim($locMats2[2]), " :.-");
				}
				if(preg_match("/(.*?)\\bLat.*?[EWV]\\s?-(.*)/is", $habitat, $locMats2)) {//echo "\nline 4047, It's a match\n";
					$habitat = ltrim(trim($locMats2[2]), " :.-");
					//$i=0;
					//foreach($locMats2 as $locMat) echo "\nlocMats2[".$i++."] = ".$locMat."\n";
				} else if(preg_match("/(.*?)\\bLat.*?[EW]\\s?-?(.*)/is", $habitat, $locMats2)) {//echo "\nline 4051, It's a match\n";
					$habitat = ltrim(trim($locMats2[2]), " :.-");
					//$i=0;
					//foreach($locMats2 as $locMat) echo "\nlocMats2[".$i++."] = ".$locMat."\n";
				} else if(preg_match("/(.*?)\\bLat.*?[EWV]\\s?-(.*)/is", $location, $locMats2)) {//echo "\nline 4056, It's a match\n";
					$location = trim($locMats2[1]);
					$habitat = ltrim(trim($locMats2[2]), " :.-");
					//$i=0;
					//foreach($locMats2 as $locMat) echo "\nlocMats2[".$i++."] = ".$locMat."\n";
				} else if(preg_match("/(.*?)\\bLat.*?[EW]\\s?-?(.*)/is", $location, $locMats2)) {//echo "\nline 4061, It's a match\n";
					$location = trim($locMats2[1]);
					$habitat = ltrim(trim($locMats2[2]), " :.-");
					//$i=0;
					//foreach($locMats2 as $locMat) echo "\nlocMats2[".$i++."] = ".$locMat."\n";
				}
				$elevArr = $this->getElevation($location);
				$temp = $elevArr[0];
				if(strlen($temp) > 0) {
					$elevation = $elevArr[1];
					$ePos = stripos($location, $elevation);
					if($ePos !== FALSE && strlen($habitat) == 0) $habitat = trim(substr($location, $ePos+strlen($elevation)));
					$location = $temp;
				}
				if(preg_match("/(.*?) - (On\\s.*)/is", $habitat, $locMats2)) {//echo "\nline 4050, It's a match\n";
					//$i=0;
					//foreach($locMats2 as $locMat) echo "\nlocMats2[".$i++."] = ".$locMat."\n";
					$habitat = trim($locMats2[1]);
					$substrate = trim($locMats2[2]);
				}
				$onPos = stripos($location, "on ");
				if($onPos !== FALSE && $onPos == 0) {
					$commaPos = strpos($location, ",");
					if($commaPos !== FALSE) {
						$substrate = substr($location, 0, $commaPos);
						$location = trim(substr($location, $commaPos+1));
					}
				}
			}
			//echo "\nline 4074, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
			if(strlen($county) > 0 && (strlen($state_province) == 0 || strlen($country) == 0)) {
				$polInfo = $this->getPolticalInfoFromCounty($county);
				if($polInfo != null ) {
					$county = ucwords
					(
						strtolower
						(
							str_replace
							(
							array('1', '!', '|', '5'. '0'),
								array('l', 'l', 'l', 'S', 'O'),
								trim($polInfo['county'])
							)
						)
					);
					if(array_key_exists('state', $polInfo)) $state_province = $polInfo['state'];
					if(array_key_exists('country', $polInfo)) $country = $polInfo['country'];
				}
			}
		}
		$patStr = "/(.*)\\bLat\/Long/is";
		if(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°/is";
		while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$patStr = "/(.*)\\bS[il1!|]t[ec]:?/is";
		if(preg_match($patStr, $elevation, $mat)) $elevation = trim($mat[1]);
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null && count($identifier) > 0) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}

		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'infraspecificEpithet' => $infraspecificEpithet,
			'taxonRank' => $taxonRank,
			'verbatimAttributes' => $verbatimAttributes,
			'associatedTaxa' => $associatedTaxa,
			'recordNumber' => $recordNumber,
			'county' => $county,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);
	}

	private function doAKFWSLabel($s) {
		if($this->isNewStyleAKFWSLabel($s)) return $this->doNewStyleAKFWSLabel($s);
		else return $this->doOldStyleAKFWSLabel($s);
	}

	private function doLichenFloraOfAlaskaLabel($s) {
		//echo "\nDid LichenFloraOfAlaskaLabel\n";
		$pattern =
			array
			(
				"/\\s(Det\\.\\s.*)/",
				"/^Bet\\.\\s/m"
			);
		$replacement =
			array
			(
				"\n\${1}",
				"Det. "
			);
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$state_province = '';
		$recordedBy = '';
		$recordNumber = '';
		$recordedById = '';
		$otherCatalogNumbers = '';
		$country = '';
		$location = '';
		$substrate = '';
		$identifiedBy = '';
		$dateIdentified = array();
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}
		$event_date = array();
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
				if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
				if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				if(strcasecmp($recordedBy, "Aaron Guy Johnson") == 0) {
					$recordedBy = "Anita Johnson";
					$recordedById = "";
				}
			}
		}
		$lfaPat = "/.*L[1Il!|][CE]H[CE]N\\sFL[O0]\\wA[,.]?\\s[O0]\\w\\sA[1Il!|]A[S5]KA[,.]?+(.+)/is";
		if(preg_match($lfaPat, $s, $ms)) $s = trim($ms[1]);
		$state_province = "Alaska";
		$country = "USA";
		$substrate = '';
		$scientificName = '';
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$associatedTaxa = '';
		$location = "";
		$patStr = "/(.*)(?:L|(?:|_))at\/(?:L|(?:|_))[o0]ng/i";
		if(preg_match($patStr, $location, $mat)) $location = $mat[1];
		$habitat = '';
		$habitatArray = $this->getHabitat($s);
		if($habitatArray != null && count($habitatArray) > 0) {
			$habitat = $habitatArray[1]." ".$habitatArray[2];
				$patStr = "/(.*)[EC][li1!|][ec]vat[li1!|]on/i";
			if(preg_match($patStr, $habitat, $mat)) $habitat = $mat[1];
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$patStr = "/[QO0]uad\\.?\\s[MH]ap(\\.)/i";
		$municipality = '';
		if(preg_match($patStr, $s, $mat)) {
			$municipality = $mat[1];
			if(strpos($municipality, "(") !== FALSE) $municipality = substr($municipality, 0, strpos($municipality, "("));
			else if(strpos($municipality, ",") !== FALSE) $municipality = substr($municipality, 0, strpos($municipality, ","));
			else {
				if(preg_match("/(.*)[0OD]uad/i", $municipality, $mat)) $municipality = $mat[1];
				else if(preg_match("/(.*)[0OD]at[ec]/i", $municipality, $mat)) $municipality = $mat[1];
			}
		}
		$lines = explode("\n", $s);
		$foundSciName = false;
		foreach($lines as $line) {
			$line = trim($line);
			if(strlen($line) > 1) {
				$patStr = "/(?:[HM]ab[li1!|]tat|[MH]ap\/[QO0]uad\\.?|C[o0][li1!|]{1,2}[ec]{2}t[o0]r|".
					"D[ec]t\\.?)\\s(.*)/i";
				if(!preg_match($patStr, $line, $mat)) {
					if(!$foundSciName && !preg_match($patStr, $line, $mat)) {
						$psn = $this->processSciName($line);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
							if(array_key_exists ('substrate', $psn)) {
								$substrate = $psn['substrate'];
								if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
							}
							$foundSciName = true;
							continue;
						}
					}
					$location = $this->getLocality($line);
					if(strlen($location) == 0 && preg_match("/\\b(?:[A-Za-z]+)(?:\\s[A-Za-z])?[:;.,].+/", $line)) {
						$location = $line;
						break;
					}
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'country' => $country,
			'recordedBy' => $recordedBy,
			'recordNumber' => $recordNumber,
			'recordedById' => $recordedById,
			'infraspecificEpithet' => $infraspecificEpithet,
			'taxonRank' => $taxonRank,
			'verbatimAttributes' => $verbatimAttributes,
			'associatedTaxa' => $associatedTaxa,
			'otherCatalogNumbers' => $otherCatalogNumbers,
			'identifiedBy' => $identifiedBy,
			'dateIdentified' => $dateIdentified,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'municipality' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($municipality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	private function doFloraOfAlaskaLabel($s) {//FloraOfAlaskaLabels that arent AKFWS have Lat/Long sections
		//echo "\nDid FloraOfAlaskaLabel\n";
		if($this->isAKFWSLabel($s)) return $this->doAKFWSLabel($s);
		else if($this->isUSFishAndWildlifeServiceLabel($s)) return $this->doUSFishAndWildlifeServiceLabel($s);
		else if($this->isLichenFloraOfAlaskaLabel($s)) return $this->doLichenFloraOfAlaskaLabel($s);
		else {
		$pattern =
			array
			(
				"/\\s(Det\\.\\s.*)/",
				"/\\sby\\s\?\n/"
			);
		$replacement =
			array
			(
				"\n\${1}",
				" by "
			);
			$s = trim(preg_replace($pattern, $replacement, $s, -1));
			$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
			$state_province = '';
			$recordedBy = '';
			$recordNumber = '';
			$recordedById = '';
			$otherCatalogNumbers = '';
			$country = '';
			$infraspecificEpithet = '';
			$taxonRank = '';
			$verbatimAttributes = '';
			$associatedTaxa = '';
			$location = '';
			$substrate = '';
			$identifiedBy = '';
			$date_identified = array();
			$event_date = array();
			$collectorInfo = $this->getCollector($s);
			if($collectorInfo != null) {
				if(array_key_exists('collectorName', $collectorInfo)) {
					$recordedBy = $collectorInfo['collectorName'];
					if(array_key_exists('collectorNum', $collectorInfo)) {
						$recordNumber = $collectorInfo['collectorNum'];
						if(stripos($s, "Deposited at NY in ".$recordNumber) !== FALSE) $recordNumber = "";
					}
					if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
					if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
					if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				}
			}
			$akfwsPat = "/.*FL[O0]\\wA[,.]?\\s[O0]\\w\\sA[1Il!|]A[S5]KA[,.]?+(.+)/is";
			if(preg_match($akfwsPat, $s, $ms)) $s = trim($ms[1]);
			$state_province = "Alaska";
			$country = "USA";
			$scientificName = '';
			//$eolPos = trim(strpos($s, "\n"));
			//$s = substr($s, $eolPos+1);//go to next line
			$location = $this->getLocality($s);
			$patStr = "/(.*)(?:L|(?:|_))at\/(?:L|(?:|_))[o0]ng/i";
			if(preg_match($patStr, $location, $mat)) $location = $mat[1];
			$habitat = '';
			$habitatArray = $this->getHabitat($s);
			if($habitatArray != null && count($habitatArray) > 0) {
				$habitat = $habitatArray[1]." ".$habitatArray[2];
				$patStr = "/(.*)[EC][li1!|][ec]vat[li1!|]on/i";
				if(preg_match($patStr, $habitat, $mat)) $habitat = $mat[1];
			}
			$elevation = '';
			$elevationArray = $this->getElevation($s);
			if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
			$patStr = "/[QO0]uad\\.?\\s[MH]ap(\\.)/i";
			$municipality = '';
			if(preg_match($patStr, $s, $mat)) {
				$municipality = $mat[1];
				if(strpos($municipality, "(") !== FALSE) $municipality = substr($municipality, 0, strpos($municipality, "("));
				else if(strpos($municipality, ",") !== FALSE) $municipality = substr($municipality, 0, strpos($municipality, ","));
				else {
					if(preg_match("/(.*)[0OD]uad/i", $municipality, $mat)) $municipality = $mat[1];
					else if(preg_match("/(.*)[0OD]at[ec]/i", $municipality, $mat)) $municipality = $mat[1];
				}
			}

			$lines = explode("\n", $s);
			foreach($lines as $line) {
				$patStr = "/(?:(?:L|(?:|_))[o0]cat[li1!|][o0]n|[HM]ab[li1!|]tat|[MH]ap\/[QO0]uad\\.?|C[o0][li1!|]{1,2}[ec]{2}t[o0]r|".
					"D[ec]t\\.?)\\s(.*)/i";
				if(strlen($line) > 1 && !preg_match($patStr, $line, $mat)) {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
						break;
					}
				}
			}
			return array
			(
				'scientificName' => $this->formatSciName($scientificName),
				'stateProvince' => $state_province,
				'country' => $country,
				'recordedBy' => $recordedBy,
				'associatedTaxa' => $associatedTaxa,
				'verbatimAttributes' => $verbatimAttributes,
				'taxonRank' => $taxonRank,
				'associatedTaxa' => $associatedTaxa,
				'recordNumber' => $recordNumber,
				'recordedById' => $recordedById,
				'otherCatalogNumbers' => $otherCatalogNumbers,
				'identifiedBy' => $identifiedBy,
				'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'municipality' => str_ireplace
				(
					array("!", "1", "|", "0"),
					array("l", "l", "l", "o"),
					trim($municipality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
				),
				'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			);
		}
	}

	private function doWeberLichenesExsiccatiLabel($s) {
		//echo "\nDoing WeberLichenesExsiccatiLabel\n";
		$pattern =
			array
			(
				"/,,/i",
				"/\\.\\./i",
				"/C_O_UNTY/i",
				"/30 M@Y I950/i",
				"/4,500 it alf\\./i",
				"/ELDORADO COUNTY:/i",
				"/u\\. s\\. A\\. COLORADO\\. Hoffat County:/i",
				"/alt\\.; with /i",
				"/doug\|asii, 1\\.200 'H'\\./i",
				"/k 510\\./i",
				"/AUSTRALIA. A\\. C\\. T\\./i",
				"/CQCH[l1|I!]SE/i",
				"/AFUZONA/i",
				"/Augus'r/i",
				"/Pr\\.\\self\\./",
				"/\|2,000\"\"\|2,2OO/",
				"/(No.)\\n(\\d)/",
				"/N°-\\s(\\d)/"
			);
		$replacement =
			array
			(
				",",
				".",
				"County",
				"30 May 1950",
				"4,500 ft alt.",
				"EL DORADO COUNTY:",
				"U. S. A. COLORADO. Moffat County:",
				"alt.; associated with ",
				"douglasii, 1,200 ft.",
				"No.",
				"AUSTRALIA. AUSTRALIAN CAPITAL TERRITORY.",
				"Cochise",
				"Arizona",
				"August",
				"ft. alt.",
				"12,000 - 12,200",
				"\${1} \${2}",
				"No. \${1}"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 4731, s: ".$s."\n\n";
		$exsnumber = "";
		$scientificName = "";
		$location = "";
		$substrate = "";
		$municipality = "";
		$state_province = "";
		$elevation = "";
		$recordedBy = "";
		$verbatimEventDate = "";
		$county = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$country = "";
		$habitat = "";
		$foundSciName = false;
		$date_identified = array();
		$identified_by = '';
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Au[gq](?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]c[tf](?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$possibleNumbers = "[OQSZl|I!0-9]";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			$location = $temp = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($countyMatches[4], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			$county = trim($countyMatches[2]);
			$country = trim($countyMatches[3]);
			$sp = $this->getStateOrProvince(trim($countyMatches[5]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
			if(preg_match("/(.*)(?:Univers)/i", $location, $mats)) $location = trim($mats[1]);
			//echo "\nline 4765, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
			if(strlen($county) > 0 && (strlen($state_province) == 0 || strlen($country) == 0)) {
				$polInfo = $this->getPolticalInfoFromCounty($county);
				if($polInfo != null ) {
					$county = ucwords
					(
						strtolower
						(
							str_replace
							(
							array('1', '!', '|', '5'. '0'),
								array('l', 'l', 'l', 'S', 'O'),
								trim($polInfo['county'])
							)
						)
					);
					if(array_key_exists('state', $polInfo)) $state_province = $polInfo['state'];
					if(array_key_exists('country', $polInfo)) $country = $polInfo['country'];
				}
			}
		}
		$lines = explode("\n", $s);
		$numLines = count($lines);
		foreach($lines as $line) {
			if(preg_match("/.*L[1Il!|][CE]H[CE]N[CE]S\\s[CE]XS[1Il!|][CE]{2}AT[1Il!|].*/i", $line)) continue;
			if(preg_match("/D[1Il!|]str[1Il!|]but[CE]d\\sby\\sth[CE]\\sUn[1Il!|]v[CE]rs[1Il!|]ty\\s[0O]f\\sC[0O].{2,4}ad[0O]\\sMus[CE]um,?\\sB[0O]uld[CE]r(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(stripos($line, " distribut") !== FALSE ||
				stripos($line, " publish") !== FALSE ||
				stripos($line, " Univers") !== FALSE ||
				stripos($line, " Museum ") !== FALSE) continue;
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if(preg_match("/^.{0,4}?[Nn][0Oo][.,]?\\s([][OQSZlU|I!0-9&]{1,3})[.,_]?+\\s(.*)/", $line, $mats) && strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
					$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
					$temp = trim($mats[2]);
					if(strlen($temp) > 3) $scientificName = $temp;
					$foundSciName = true;
					//$break;
				}
			} else {
				if(strlen($state_province) == 0 && strlen($county) == 0) {
					$dotPos = strpos($line, ".");
					$colonPos = strpos($line, ":");
					if($dotPos !== FALSE) {
						if($colonPos !== FALSE && $colonPos < $dotPos) $dotPos = $colonPos;
					} else $dotPos = $colonPos;
					if($dotPos !== FALSE) {
						$potentialCountry = trim(substr($line, 0, $dotPos));
						if(strlen($potentialCountry) > 3 && $this->isCountryInDatabase($potentialCountry)) {
							$country = $potentialCountry;
							$rest = ltrim(rtrim(substr($line, $dotPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							if(strlen($rest) > 0) {
								$dotPos = strpos($rest, ".");
								$colonPos = strpos($rest, ":");
								if($dotPos !== FALSE) {
									if($colonPos !== FALSE && $colonPos < $dotPos) $dotPos = $colonPos;
								} else $dotPos = $colonPos;
								if($dotPos !== FALSE) {
									$state_province = trim(substr($rest, 0, $dotPos));
									if(strlen($state_province) < 3) $state_province = "";
									if(strlen($state_province) > 0) $location = preg_replace(
										array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
										array("-", " ", " "),
										trim(substr($s, strpos($s, $state_province))));
								}
								break;
							}
						}
					}
				}
			}
		}
		if(strlen($location) > 0) {//echo "\nline 4837, location: ".$location."\n";
			$elevArr = $this->getElevation($location);
			$temp = '';
			if($elevArr != null) {
				$elevation = $elevArr[1];
				if(strlen($elevation) > 0) {
					$location = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("-", " ", " "),
						trim($elevArr[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					$temp = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("-", " ", " "),
						ltrim(rtrim($elevArr[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					if(preg_match("/alt[,.];?\\s(.*)/", $temp, $mats)) $temp = trim($mats[1]);
					$termLocPat = "/(.*)(?:a[s5]{2}[o0][ce][1Il!|]a[tf][ce]d\\sw[1Il!|][t+]h)\\s(.*)/is";
					if(preg_match($termLocPat, $temp, $lMats)) {
						$temp = trim($lMats[1]);
						$associatedTaxa = trim($lMats[2]);
						$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
						if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[1]);
						$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°|".
							"T\\s?".$possibleNumbers."{1,3}\\s?[NS]\\sR\\s?".$possibleNumbers."{1,3}[EW]\\sS".$possibleNumbers."{1,3}+)\\s(.*)/s";
						if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[2]);
						if(strlen($associatedTaxa) < 6) $associatedTaxa = "";
					}
				}
			}
		} else {
			if(strlen($scientificName) > 0) {
				$s = trim(substr($s, stripos($s, $scientificName)+strlen($scientificName)));
				$elevArr = $this->getElevation($s);
				if($elevArr != null) {
					$elevation = $elevArr[1];
					if(strlen($elevation) > 0) {
						$location = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("-", " ", " "),
							trim($elevArr[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
						$temp = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("-", " ", " "),
							ltrim(rtrim($elevArr[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
						if(preg_match("/alt[,.];?\\s(.*)/", $temp, $mats)) $temp = trim($mats[1]);
						$termLocPat = "/(.*)(?:a[s5]{2}[o0][ce][1Il!|]a[tf][ce]d\\sw[1Il!|][t+]h)\\s(.*)/is";
						if(preg_match($termLocPat, $temp, $lMats)) {
							$temp = trim($lMats[1]);
							$associatedTaxa = trim($lMats[2]);
							$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
							if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[1]);
							$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°|".
								"T\\s?".$possibleNumbers."{1,3}\\s?[NS]\\sR\\s?".$possibleNumbers."{1,3}[EW]\\sS".$possibleNumbers."{1,3}+)\\s(.*)/s";
							if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[2]);
							if(strlen($associatedTaxa) < 6) $associatedTaxa = "";
						}
					}
				}
			} else {
				$elevArr = $this->getElevation($s);
				if($elevArr != null) {
					$elevation = $elevArr[1];
					if(strlen($elevation) > 0) {
						$temp = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("-", " ", " "),
							ltrim(rtrim($elevArr[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
						if(preg_match("/alt[,.];?\\s(.*)/", $temp, $mats)) $temp = trim($mats[1]);
						$termLocPat = "/(.*)(?:a[s5]{2}[o0][ce][1Il!|]a[tf][ce]d\\sw[1Il!|][t+]h)\\s(.*)/is";
						if(preg_match($termLocPat, $temp, $lMats)) {
							$temp = trim($lMats[1]);
							$associatedTaxa = trim($lMats[2]);
							$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
							if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[1]);
							$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°|".
								"T\\s?".$possibleNumbers."{1,3}\\s?[NS]\\sR\\s?".$possibleNumbers."{1,3}[EW]\\sS".$possibleNumbers."{1,3}+)\\s(.*)/s";
							if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[2]);
							if(strlen($associatedTaxa) < 6) $associatedTaxa = "";
						}
					}
				}
			}
		}
		if(strlen($location) > 0) {
			$semiPos = strpos($location, ";");
			if($semiPos !== FALSE) {
				$temp3 = preg_replace(array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"), array("-", " ", " "), trim(substr($location, $semiPos+1)));
				$temp2 = $temp3;
				if(strcasecmp(substr($temp3, 0, 3), "on ") == 0) {
					$inappropriateWords = array("road", "north", "south", "highway", "hiway", "area", "state",
						"route", "park", "Rd.", "Co.", "County", "mile");
					foreach($inappropriateWords as $inappropriateWord) {
						if(stripos($temp3, $inappropriateWord) !== FALSE) $temp3 = "";
					}
					if(strlen($temp3) > 0) {
						$substrate = $temp3;
						$pos = strpos($substrate, ".");
						if($pos !== FALSE) $substrate = trim(substr($substrate, 0, $pos));
					}
				} else $habitat = $temp3;
				$location = trim(substr($location, 0, $semiPos));
			}
			if(strlen($habitat) == 0) {
				if(strlen($temp) > 6 && strlen($substrate) == 0) {
					if(strcasecmp(substr($temp, 0, 3), "on ") == 0) {
						$inappropriateWords = array("road", "north", "south", "highway", "hiway", "area",
							"state", "route", "park", "Rd.", "Co.", "County", "mile");
						foreach($inappropriateWords as $inappropriateWord) {
							if(stripos($temp, $inappropriateWord) !== FALSE) $temp = "";
						}
						if(strlen($temp) > 0) $substrate = $temp;
					} else $habitat = $temp;
				}
			} else if(strlen($temp) > 6) {
				$verbatimAttributes = $temp;
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
				if(preg_match($termLocPat, $verbatimAttributes, $lMats)) $verbatimAttributes = trim($lMats[1]);
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°|".
					"T\\s?".$possibleNumbers."{1,3}\\s?[NS]\\sR\\s?".$possibleNumbers."{1,3}[EW]\\sS".$possibleNumbers."{1,3}+)\\s(.*)/s";
				if(preg_match($termLocPat, $verbatimAttributes, $lMats)) $verbatimAttributes = trim($lMats[2]);
				if(strlen($verbatimAttributes) < 6) $verbatimAttributes = "";
			}
			if(strlen($habitat) > 0) {//echo "\nline 4602, habitat: ".$habitat."\n";
				$pos = strpos($habitat, "; on ");
				if($pos === FALSE || $pos < 6) $pos = strpos($habitat, ", on ");
				if($pos !== FALSE) {
					$temp = trim(substr($habitat, $pos+2));
					$inappropriateWords = array("road", "north", "south", "highway", "hiway", "area", "state",
						"route", "park", "Rd.", "Co.", "County", "mile");
					foreach($inappropriateWords as $inappropriateWord) {
						if(stripos($temp, $inappropriateWord) !== FALSE) $temp = "";
					}
					if(strlen($temp) > 0) {
						$substrate = $temp;
						$habitat = trim(substr($habitat, 0, $pos));
					}
				}
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
				if(preg_match($termLocPat, $habitat, $lMats)) $habitat = trim($lMats[1]);
				$termLocPat = "/(.*?)(?:\\d{1,3}+(?:\\.\\d{1,6})\\s?°|".
					"T\\.?\\s?".$possibleNumbers."{1,3}\\s?[NS],?\\sR\\.?\\s?".$possibleNumbers."{1,3}\\s?[EW],?\\sS(?:ec)?\\.?\\s?".$possibleNumbers."{1,3})\\b(.*)/s";
				if(preg_match($termLocPat, $habitat, $lMats)) $habitat = trim($lMats[1]);
				if(strlen($habitat) < 6) $habitat = "";
			}
			if(strlen($location) > 0) {
				if(strcasecmp(substr($location, 0, 3), "on ") == 0 && strlen($substrate) == 0) {
					$pos = strpos($location, ", ");
					if($pos !== FALSE) {
						$temp = trim(substr($location, 0, $pos));
						$inappropriateWords = array("road", "north", "south", "highway", "hiway", "area", "state",
							"route", "park", "Rd.", "Co.", "County", "mile");
						foreach($inappropriateWords as $inappropriateWord) {
							if(stripos($temp, $inappropriateWord) !== FALSE) $temp = "";
						}
						if(strlen($temp) > 0) {
							$substrate = $temp;
							$location = trim(substr($location, $pos+2));
						}
					}
				}
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
				if(preg_match($termLocPat, $location, $lMats)) $location = trim($lMats[1]);
				$termLocPat = "/(.*)(?:a[s5]{2}[o0][ce][1Il!|]a[tf][ce]d\\sw[1Il!|][t+]h)\\s(.*)/is";
				if(preg_match($termLocPat, $location, $lMats)) {
					$location = trim($lMats[1]);
					$associatedTaxa = trim($lMats[2]);
				}
			}
			if(strlen($habitat) > 0) {
				$termLocPat = "/(.*)(?:a[s5]{2}[o0][ce][1Il!|]a[tf][ce]d\\sw[1Il!|][t+]h)\\s(.*)/is";
				if(preg_match($termLocPat, $habitat, $lMats)) {
					$habitat = trim($lMats[1]);
					$associatedTaxa = trim($lMats[2]);
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'municipality' => ucfirst(trim($municipality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "91",
			'exsnumber' => $exsnumber
		);
	}

	private function doMerrilLichenesExsiccatiLabel($str) {
		//echo "\nDoing MerrilLichenesExsiccatiLabel\n";
		$pattern =
			array
			(
				"/,,/i",
				"/\\.\\./i",
				"/M1\inÂ¢-/",
				"/Hawa[1Il!|]{2}an [1Il!|]s[1Il!|]ands/i",
				"/Was-hington\\./i",
				"/Rockport\\.?\\sMaine/i",
				"/Rockland\\.?\\sMaine/i"
			);
		$replacement =
			array
			(
				",",
				".",
				"Maine.",
				"Hawaii",
				"Washington.",
				"Rockport, Maine",
				"Rockland, Maine"
			);

		$s = trim(preg_replace($pattern, $replacement, $str, -1));
		$exsnumber = "";
		$scientificName = "";
		$substrate = "";
		$municipality = "";
		$state_province = "";
		$recordedBy = "";
		$verbatimEventDate = "";
		$county = "";
		$country = "";
		$foundSciName = false;
		$date_identified = array();
		$identified_by = '';
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$lines = explode("\n", $s);
		foreach($lines as $line) {
			if(preg_match("/.*L[1Il!|][CE]H[CE]N[CE]S\\s[CE]XS[1Il!|][CE]{2}AT[1Il!|].*/i", $line)) continue;
			if(preg_match("/.*[CG]. K. M[CE]RR[1Il!|]{2.4}.*/i", $line)) continue;
			if(preg_match("/.*3[O0Q]9 Br[O0]adwa[pqgy].*/i", $line)) continue;
			if(stripos($line, " publish") !== FALSE ||
				stripos($line, "prepare") !== FALSE ||
				stripos($line, " distribut") !== FALSE ||
				stripos($line, " DUKE U") !== FALSE ||
				stripos($line, " Univers") !== FALSE ||
				stripos($line, " erbarium") !== FALSE ||
				stripos($line, " series ") !== FALSE) continue;
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if(preg_match("/^.{0,3}?([][OQSZlU|I!0-9&]{1,3})[.,_]?+\\s(.*)/", $line, $mats) && strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
					if(!preg_match("/.{0,3}?Co[1Il!|]{2}\\.?\\s/i", $line) && !preg_match("/.{0,3}?C?oll\\.?\\s/i", $line)) {
						$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
						$scientificName = trim($mats[2]);
						if(preg_match("/(.+)\\s(on\\s.+)/i", $scientificName, $mats2)) {
							$substrate = trim($mats2[2]);
							$scientificName = trim($mats2[1]);
						}
						$foundSciName = true;
						continue;
					}
				}
			}
			$onPos = stripos($line, "on ");
			if($onPos !== FALSE && $onPos < 3) $substrate = $line;
			else {
				$commaPos = strpos($line, ",");
				if($commaPos !== FALSE && !$this->isMostlyGarbage($line, 0.60)) {
					$potentialCityOrCounty = trim(substr($line, 0, $commaPos));
					$rest = trim(substr($line, $commaPos+1));
					$dotPos = strpos($rest, ".");
					$potentialState = trim(substr($rest, 0, $dotPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					$rest = trim(substr($rest, $dotPos+1));
					$cArray = $this->getCounty($potentialCityOrCounty);
					if($cArray != null) {
						$size = count($cArray);
						if($size == 1) {
							$cArra = $cArray[0];
							if(array_key_exists ('stateProvince', $cArra)) {
								$t = $cArra['stateProvince'];
								if(strcasecmp($t, $potentialState) == 0) {
									$state_province = $t;
									if(array_key_exists ('county', $cArra)) $county = $cArra['county'];
									if(array_key_exists ('country', $cArra)) $country = $cArra['country'];
								}
							}
						} else if($size > 1) {
							foreach($cArray as $cArra) {
								if(array_key_exists ('stateProvince', $cArra)) {
									$t = $cArra['stateProvince'];
									if(strcasecmp($t, $potentialState) == 0) {
										$state_province = $t;
										if(array_key_exists ('county', $cArra)) $county = $cArra['county'];
										if(array_key_exists ('country', $cArra)) $country = $cArra['country'];
										break;
									}
								}
							}
						}
					}
					if(strlen($county) == 0) {
						$municipality = $potentialCityOrCounty;
						$stateAndCountry = $this->getStateOrProvince($potentialState);
						if($stateAndCountry != null) {
							$state_province = $stateAndCountry[0];
							$country = $stateAndCountry[1];
						}
					}
					if(preg_match("/(.*)\\b(".$possibleMonths.")(.*)/i", $rest, $ms)) {
						$recordedBy = trim($ms[1]);
						$verbatimEventDate = ucfirst(trim($ms[2]))." ".$this->replaceMistakenNumbers(trim($ms[3]));
					} else if(preg_match("/(.*)\\b([][l1|I!]9[OQSZlU|I!0-9&]{2})[.,]?+/i", $rest, $ms)) {
						$recordedBy = trim($ms[1]);
						$verbatimEventDate = $this->replaceMistakenNumbers(trim($ms[2]));
					}
				}
			}
			if($foundSciName && strlen($state_province) > 0) break;
		}
		$result = array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'municipality' => ucfirst(trim($municipality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "89",
			'exsnumber' => $exsnumber
		);
		if($this->isKienerMemorialLabel($str)) return $this->combineArrays($result, $this->doKienerMemorialLabel($str));
		return $result;
	}

	private function combineArrays($array1, $array2) {//combines 2 arrays.  Unlike the PHP array_merge function, if the second array has a value it overwrites
		if($array1 && $array2) {
			$result = array();
			foreach($array2 as $k2 => $v2) {
				if(is_array($v2)) {
					if(count($v2) > 0) $result[$k2] = $v2;
				} else if(strlen($v2) > 0) $result[$k2] = $v2;
			}
			foreach($array1 as $k1 => $v1) if(!array_key_exists($k1, $result)) $result[$k1] = $v1;
			return $result;
		} else if($array1) return $array1;
		else if($array2) return $array2;
		else return array();
	}

	private function doASULichenesExsiccatiLabel($s) {
		//echo "\nDoing ASULichenesExsiccatiLabel\n";
		$pattern =
			array
			(
				"/,,/i",
				"/\\.\\./i",
				"/COCHISEZ/i",
				"/TETONI/i",
				"/MEIXICX\)/i",
				"/ESTAIDDEBAJRQLIHDRNIASLIR/i",
				"/ASU lb./i",
				"/Nash 326,110/"
			);
		$replacement =
			array
			(
				",",
				".",
				"COCHISE",
				"Teton",
				"Mexico",
				"Baja California Sur",
				"ASU No.",
				"Nash #26,110"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 4508, s: ".$s."\n\n";
		$exsnumber = "";
		$scientificName = "";
		$location = "";
		$substrate = "";
		$state_province = "";
		$elevation = "";
		$recordedBy = "";
		$verbatimEventDate = "";
		$county = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$country = "";
		$habitat = "";
		$taxonRank = "";
		$infraspecificEpithet = "";
		$date_identified = array();
		$identified_by = '';
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Au[gq](?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]c[tf](?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$possibleNumbers = "[OQSZl|I!0-9]";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			$location = $temp = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($countyMatches[4], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			$county = trim($countyMatches[2]);
			$country = trim($countyMatches[3]);
			$sp = $this->getStateOrProvince(trim($countyMatches[5]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
			//echo "\nline 4532, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
			if(strlen($county) > 0 && (strlen($state_province) == 0 || strlen($country) == 0)) {
				$polInfo = $this->getPolticalInfoFromCounty($county);
				if($polInfo != null ) {
					$county = ucwords
					(
						strtolower
						(
							str_replace
							(
							array('1', '!', '|', '5'. '0'),
								array('l', 'l', 'l', 'S', 'O'),
								trim($polInfo['county'])
							)
						)
					);
					if(array_key_exists('state', $polInfo)) $state_province = $polInfo['state'];
					if(array_key_exists('country', $polInfo)) $country = $polInfo['country'];
				}
			}
		}
		$lines = explode("\n", $s);
		$numLines = count($lines);
		$foundSciName = false;
		foreach($lines as $line) {
			if(preg_match("/.*(?:L[1Il!|]|IZ)(?:[CE]H|QI)[CE]N[CE]S\\s[CE]XS[1Il!|][CE]{2}AT[1Il!|]\\sA\\.?S\\.?U\\.?\\sNo\\.\\s?(".$possibleNumbers."{1,3})/i", $line, $numMats)) {
				$exsnumber = $this->replaceMistakenNumbers(trim($numMats[1]));
				continue;
			}

			if(stripos($line, " distribut") !== FALSE ||
				stripos($line, " publish") !== FALSE ||
				stripos($line, " Univers") !== FALSE) continue;
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if
				(
					preg_match("/^.??([][OQSZlU|I!0-9&]{1,3})[.,_]?+\\s(.*)/", $line, $mats) &&
					strlen($line) > 6 &&
					!$this->isMostlyGarbage($line, 0.60) &&
					!preg_match("/^C[0o][1Il!|]{2}.*/", $line)
				) {
					if(strlen($exsnumber) == 0) $exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
					$scientificName = trim($mats[2]);
					$foundSciName = true;
				} else {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) {
							$scientificName = $psn['scientificName'];
							$foundSciName = true;
						}
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) {
							$trn = $psn['recordNumber'];
							if(strlen($trn) > 0) $recordNumber = $trn;
						}
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
					}
				}
			}
			if(strlen($state_province) == 0 && strlen($county) == 0) {
				if(preg_match("/.{0,2}U\\.?\\s?[S5]\\.?\\s?A[,.]?(.*)/i", $line, $cMats)) {
					$country = "U.S.A.";
					$rest = trim($cMats[1]);
					$dotPos = strpos($rest, ".");
					$commaPos = strpos($rest, ",");
					if($dotPos !== FALSE) {
						if($commaPos !== FALSE && $commaPos < $dotPos) $dotPos = $commaPos;
					} else $dotPos = $commaPos;
					if($dotPos !== FALSE && $dotPos > 0) {
						$state_province = trim(substr($rest, 0, $dotPos));
						$potentialCounty = trim(substr($rest, $dotPos+1));
						$cs = $this->getCounty($potentialCounty);
						if($cs != null) $county = $potentialCounty;
						if(strlen($county) > 0) $location = ltrim(rtrim(substr($s, strpos($s, $county)+strlen($county)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
						else $location = ltrim(rtrim(substr($s, strpos($s, $state_province)+strlen($state_province)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					} else $location = ltrim(rtrim(substr($s, strpos($s, $rest)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
				} else {
					$dotPos = strpos($line, ".");
					$commaPos = strpos($line, ",");
					if($dotPos !== FALSE) {
						if($commaPos !== FALSE && $commaPos < $dotPos) $dotPos = $commaPos;
					} else $dotPos = $commaPos;
					if($dotPos !== FALSE) {
						$potentialCountry = trim(substr($line, 0, $dotPos));
						if(strlen($potentialCountry) > 3 && $this->isCountryInDatabase($potentialCountry)) {
							$country = $potentialCountry;
							$rest = ltrim(rtrim(substr($line, $dotPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							$dotPos = strpos($rest, ".");
							$colonPos = strpos($rest, ":");
							if($dotPos !== FALSE) {
								if($colonPos !== FALSE && $colonPos < $dotPos) $dotPos = $colonPos;
							} else $dotPos = $colonPos;
							if($dotPos !== FALSE) {
								$state_province = trim(substr($rest, 0, $dotPos));
								if(strlen($state_province) < 3) $state_province = "";
								if(strlen($state_province) > 0) $location = preg_replace(
									array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
									array("-", " ", " "),
									trim(substr($s, strpos($s, $state_province))));
							}
							break;
						}
					}
				}
			}
		}
		if(strlen($location) > 0) {//echo "\nline 4991, location: ".$location."\n";
			$elevArr = $this->getElevation($location);
			$temp = '';
			if($elevArr != null) {
				$elevation = $elevArr[1];
				if(strlen($elevation) > 0) {
					$location = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("-", " ", " "),
						trim($elevArr[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				}
			}
			if(strlen($location) > 0) {
				if(preg_match("/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°)(.*)/", $location, $lMats)) {
					$location = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					$habitat = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				} else {
					$pat = "/(.*?)(?:".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3}\\s?'[NS],\\s?".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3})\\s?'[EW](.*)/";
					if(preg_match($pat, $location, $lMats)) {
						$location = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
						$habitat = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					}
				}
				$lPat = "/.*(?:".$possibleNumbers."{1,3}+\\s?°\\s?".$possibleNumbers."{1,3}+\\s?'\\s?(?:".$possibleNumbers."{1,3}+\\s?\")?\\s?[EW])(.*)/";
				if(preg_match($lPat, $habitat, $lMats)) {
					$habitat = trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				} else {
					$pat = "/.*?(?:".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3}\\s?'[NS],\\s?".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3})\\s?'[EW](.*)/";
					if(preg_match($pat, $habitat, $lMats)) {
						$habitat = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					}
				}
			}
			if(strlen($habitat) > 0) {
				if(preg_match("/(.*)C[o0][1Il!|]{2}/i", $habitat, $mats)) $habitat = trim($mats[1]);
				if(strlen($habitat) > 0) {
					$onPos = stripos($habitat, "on ");
					if($onPos !== FALSE && $onPos == 0) {
						$commaPos = strpos($habitat, ",");
						if($commaPos !== FALSE) $substrate = trim(substr($habitat, 0, $commaPos));
						else {
							$dotPos = strpos($habitat, ".");
							if($dotPos !== FALSE) $substrate = trim(substr($habitat, 0, $dotPos));
						}
					} else {
						$onPos = stripos($habitat, ". on ");
						if($onPos !== FALSE) {
							$commaPos = strpos($habitat, ",");
							if($commaPos !== FALSE) $substrate = ltrim(rtrim(substr($habitat, 0, $commaPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							else {
								$dotPos = strpos($habitat, ".");
								if($dotPos !== FALSE) $substrate = ltrim(rtrim(substr($habitat, 0, $dotPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							}
						}
					}
					$habitat = trim($habitat, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					if(preg_match("/\(([A-Za-z ]*)\)/", $habitat, $mats)) {
						$verbatimAttributes = trim($mats[1]);
						$habitat = "";
					}
				}
			}
			if(strlen($location) > 0) {
				if(preg_match("/(.*)C[o0][1Il!|]{2}/i", $location, $mats)) $location = trim($mats[1]);
			}
		}//echo "\nline 4616, location: ".$location."\n";
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "93",
			'exsnumber' => $exsnumber
		);
	}

	private function doHasseLichenesExsiccatiLabel($s) {
		//echo "\nDoing HasseLichenesExsiccatiLabel\n";
		$pattern =
			array
			(
				"/,,/i",
				"/\\.\\./i"
			);
		$replacement =
			array
			(
				",",
				"."
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 4508, s: ".$s."\n\n";
		$exsnumber = "";
		$scientificName = "";
		$location = "";
		$substrate = "";
		$state_province = "";
		$elevation = "";
		$recordedBy = "";
		$verbatimEventDate = "";
		$county = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$country = "";
		$habitat = "";
		$taxonRank = "";
		$infraspecificEpithet = "";
		$date_identified = array();
		$identified_by = '';
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Au[gq](?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]c[tf](?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$possibleNumbers = "[OQSZl|I!0-9]";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			$location = $temp = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($countyMatches[4], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			$county = trim($countyMatches[2]);
			$country = trim($countyMatches[3]);
			$sp = $this->getStateOrProvince(trim($countyMatches[5]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
			//echo "\nline 4532, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
			if(strlen($county) > 0 && (strlen($state_province) == 0 || strlen($country) == 0)) {
				$polInfo = $this->getPolticalInfoFromCounty($county);
				if($polInfo != null ) {
					$county = ucwords
					(
						strtolower
						(
							str_replace
							(
							array('1', '!', '|', '5'. '0'),
								array('l', 'l', 'l', 'S', 'O'),
								trim($polInfo['county'])
							)
						)
					);
					if(array_key_exists('state', $polInfo)) $state_province = $polInfo['state'];
					if(array_key_exists('country', $polInfo)) $country = $polInfo['country'];
				}
			}
		}
		$lines = explode("\n", $s);
		$foundSciName = false;
		$numLines = count($lines);
		foreach($lines as $line) {
			if(stripos($line, " distribut") !== FALSE ||
				stripos($line, " publish") !== FALSE ||
				stripos($line, " Univers") !== FALSE) continue;
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if
				(
					preg_match("/^.??([][OQSZlU|I!0-9&]{1,3})[.,_]?+\\s(.*)/", $line, $mats) &&
					strlen($line) > 6 &&
					!$this->isMostlyGarbage($line, 0.60) &&
					!preg_match("/^C[0o][1Il!|]{2}.*/", $line)
				) {
					$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
					$scientificName = trim($mats[2]);
					$foundSciName = true;
					continue;
				} else {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) {
							$scientificName = $psn['scientificName'];
							$foundSciName = true;
						}
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) {
							$trn = $psn['recordNumber'];
							if(strlen($trn) > 0) $recordNumber = $trn;
						}
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
						continue;
					}
				}
			}
			if(strlen($state_province) == 0 && strlen($county) == 0) {
				if(preg_match("/.{0,2}U\\.?\\s?[S5]\\.?\\s?A[,.]?(.*)/i", $line, $cMats)) {
					$country = "U.S.A.";
					$rest = trim($cMats[1]);
					if(strlen($rest) > 0) {
						$dotPos = strpos($rest, ".");
						$commaPos = strpos($rest, ",");
						if($dotPos !== FALSE) {
							if($commaPos !== FALSE && $commaPos < $dotPos) $dotPos = $commaPos;
						} else $dotPos = $commaPos;
						if($dotPos !== FALSE && $dotPos > 0) {
							$state_province = trim(substr($rest, 0, $dotPos));
							$potentialCounty = trim(substr($rest, $dotPos+1));
							$cs = $this->getCounty($potentialCounty);
							if($cs != null) $county = $potentialCounty;
							if(strlen($county) > 0) $location = ltrim(rtrim(substr($s, strpos($s, $county)+strlen($county)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							else $location = ltrim(rtrim(substr($s, strpos($s, $state_province)+strlen($state_province)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
						} else $location = ltrim(rtrim(substr($s, strpos($s, $rest)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					}
				} else {
					$dotPos = strpos($line, ".");
					$commaPos = strpos($line, ",");
					if($dotPos !== FALSE) {
						if($commaPos !== FALSE && $commaPos < $dotPos) $dotPos = $commaPos;
					} else $dotPos = $commaPos;
					if($dotPos !== FALSE) {
						$potentialCountry = trim(substr($line, 0, $dotPos));
						if(strlen($potentialCountry) > 3 && $this->isCountryInDatabase($potentialCountry)) {
							$country = $potentialCountry;
							$rest = ltrim(rtrim(substr($line, $dotPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							if(strlen($rest) > 0) {
								$dotPos = strpos($rest, ".");
								$colonPos = strpos($rest, ":");
								if($dotPos !== FALSE) {
									if($colonPos !== FALSE && $colonPos < $dotPos) $dotPos = $colonPos;
								} else $dotPos = $colonPos;
								if($dotPos !== FALSE) {
									$state_province = trim(substr($rest, 0, $dotPos));
									if(strlen($state_province) < 3) $state_province = "";
									if(strlen($state_province) > 0) $location = preg_replace(
										array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
										array("-", " ", " "),
										trim(substr($s, strpos($s, $state_province))));
								}
								break;
							}
						}
					}
				}
			}
		}
		if(strlen($location) > 0) {//echo "\nline 4991, location: ".$location."\n";
			$elevArr = $this->getElevation($location);
			$temp = '';
			if($elevArr != null) {
				$elevation = $elevArr[1];
				if(strlen($elevation) > 0) {
					$location = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("-", " ", " "),
						trim($elevArr[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				}
			}
			if(strlen($location) > 0) {
				if(preg_match("/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°)(.*)/", $location, $lMats)) {
					$location = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					$habitat = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				} else {
					$pat = "/(.*?)(?:".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3}\\s?'[NS],\\s?".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3})\\s?'[EW](.*)/";
					if(preg_match($pat, $location, $lMats)) {
						$location = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
						$habitat = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					}
				}
				$lPat = "/.*(?:".$possibleNumbers."{1,3}+\\s?°\\s?".$possibleNumbers."{1,3}+\\s?'\\s?(?:".$possibleNumbers."{1,3}+\\s?\")?\\s?[EW])(.*)/";
				if(preg_match($lPat, $habitat, $lMats)) {
					$habitat = trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				} else {
					$pat = "/.*?(?:".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3}\\s?'[NS],\\s?".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3})\\s?'[EW](.*)/";
					if(preg_match($pat, $habitat, $lMats)) {
						$habitat = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					}
				}
			}
			if(strlen($habitat) > 0) {
				if(preg_match("/(.*)C[o0][1Il!|]{2}/i", $habitat, $mats)) $habitat = trim($mats[1]);
				if(strlen($habitat) > 0) {
					$onPos = stripos($habitat, "on ");
					if($onPos !== FALSE && $onPos == 0) {
						$commaPos = strpos($habitat, ",");
						if($commaPos !== FALSE) $substrate = trim(substr($habitat, 0, $commaPos));
						else {
							$dotPos = strpos($habitat, ".");
							if($dotPos !== FALSE) $substrate = trim(substr($habitat, 0, $dotPos));
						}
					} else {
						$onPos = stripos($habitat, ". on ");
						if($onPos !== FALSE) {
							$commaPos = strpos($habitat, ",");
							if($commaPos !== FALSE) $substrate = ltrim(rtrim(substr($habitat, 0, $commaPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							else {
								$dotPos = strpos($habitat, ".");
								if($dotPos !== FALSE) $substrate = ltrim(rtrim(substr($habitat, 0, $dotPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							}
						}
					}
					$habitat = trim($habitat, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					if(preg_match("/\(([A-Za-z ]*)\)/", $habitat, $mats)) {
						$verbatimAttributes = trim($mats[1]);
						$habitat = "";
					}
				}
			}
			if(strlen($location) > 0) {
				if(preg_match("/(.*)C[o0][1Il!|]{2}/i", $location, $mats)) $location = trim($mats[1]);
			}
		}//echo "\nline 4616, location: ".$location."\n";
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "92",
			'exsnumber' => $exsnumber
		);
	}

	private function doLichenesExsiccatiLabel($s) {
		//echo "\nDid LichenesExsiccatiLabel\n";
		if($this->isWeberLichenesExsiccatiLabel($s)) return $this->doWeberLichenesExsiccatiLabel($s);
		else if($this->isMerrilLichenesExsiccatiLabel($s)) return $this->doMerrilLichenesExsiccatiLabel($s);
		else if($this->isASULichenesExsiccatiLabel($s)) return $this->doASULichenesExsiccatiLabel($s);
		else if($this->isHasseLichenesExsiccatiLabel($s)) return $this->doHasseLichenesExsiccatiLabel($s);
		else {
			$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
			$state_province = '';
			$recordedBy = '';
			$country = '';
			$location = '';
			$substrate = '';
			$date_identified = array();
			$event_date = array();
			$akfwsPat = "/.*FL[O0]\\wA\\s[O0]\\w\\sA[1Il!|]A[S5]KA(.+)/is";
			if(preg_match($akfwsPat, $s, $ms)) $s = trim($ms[1]);
			$state_province = "Alaska";
			$country = "USA";
			$substrate = '';
			$scientificName = '';
			$eolPos = trim(strpos($s, "\n"));
			$s = substr($s, $eolPos+1);//go to next line
			$location = $this->getLocality($s);
			$patStr = "/(.*)(?:L|(?:|_))at\/(?:L|(?:|_))[o0]ng/i";
			if(preg_match($patStr, $location, $mat)) $location = $mat[1];
			$habitat = '';
			$habitatArray = $this->getHabitat($s);
			if($habitatArray != null && count($habitatArray) > 0) {
				$habitat = $habitatArray[1]." ".$habitatArray[2];
				$patStr = "/(.*)[EC][li1!|][ec]vat[li1!|]on/i";
				if(preg_match($patStr, $habitat, $mat)) $habitat = $mat[1];
			}
			$elevation = '';
			$elevationArray = $this->getElevation($s);
			if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
			$patStr = "/[QO0]uad\\.?\\s[MH]ap(\\.)/i";
			$municipality = '';
			if(preg_match($patStr, $s, $mat)) {
				$municipality = $mat[1];
				if(strpos($municipality, "(") !== FALSE) $municipality = substr($municipality, 0, strpos($municipality, "("));
				else if(strpos($municipality, ",") !== FALSE) $municipality = substr($municipality, 0, strpos($municipality, ","));
				else {
					if(preg_match("/(.*)[0OD]uad/i", $municipality, $mat)) $municipality = $mat[1];
					else if(preg_match("/(.*)[0OD]at[ec]/i", $municipality, $mat)) $municipality = $mat[1];
				}
			}

			$lines = explode("\n", $s);
			foreach($lines as $line) {
				$patStr = "/(?:(?:L|(?:|_))[o0]cat[li1!|][o0]n|[HM]ab[li1!|]tat|[MH]ap\/[QO0]uad\\.?|C[o0][li1!|]{1,2}[ec]{2}t[o0]r|".
					"D[ec]t\\.?)\\s(.*)/i";
				if(strlen($line) > 1 && !preg_match($patStr, $line, $mat)) {
					$possibleScientificName = $line;
					$foundSciName = true;
					$spacePos = strpos($scientificName, " ");
					if($spacePos !== FALSE) {
						$names = explode(" ", $possibleScientificName);
						foreach($names as $name) if(!$this->isMostlyGarbage($name, 0.33)) $scientificName .= $name;
					} else if(!$this->isMostlyGarbage($possibleScientificName, 0.33)) $scientificName = $possibleScientificName;
					$scientificName = trim($scientificName, " \t\n\r\0\x0B,:;\"\'\\~@#$%^&*_-");
					if(strlen($scientificName) > 0) break;
				}
			}
			return array
			(
				'scientificName' => $this->formatSciName($scientificName),
				'stateProvince' => $state_province,
				'country' => $country,
				'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'municipality' => str_ireplace
				(
					array("!", "1", "|", "0"),
					array("l", "l", "l", "o"),
					trim($municipality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
				),
				'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			);
		}
	}

	private function doLichensOfFloridaLabel($s) {
		//echo "\nDoing LichensOfFloridaLabel\n";
		$pattern =
			array
			(
				"/,,/i",
				"/\\.\\./i",
				"/BiÂ£iÂ£OSÂ£ora/"
			);
		$replacement =
			array
			(
				",",
				".",
				"Bactrospora"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 4508, s: ".$s."\n\n";
		$scientificName = "";
		$location = "";
		$substrate = "";
		$state_province = "";
		$elevation = "";
		$recordedBy = "";
		$recordNumber = "";
		$verbatimEventDate = "";
		$county = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$country = "";
		$habitat = "";
		$taxonRank = "";
		$infraspecificEpithet = "";
		$date_identified = array();
		$identifiedBy = '';
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Au[gq](?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]c[tf](?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identifiedBy = $identifier[0];
			$date_identified = $identifier[1];
		}
		$possibleNumbers = "[OQSZl|I!0-9]";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			$location = $temp = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($countyMatches[4], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			$county = trim($countyMatches[2]);
			//echo "\nline 5722, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
		}
		$lines = explode("\n", $s);
		$foundSciName = false;
		$numLines = count($lines);
		$index = 0;
		while($index < $numLines) {
			$line = trim($lines[$index++]);
			if(stripos($line, " New York") !== FALSE ||
				stripos($line, " Herbariu") !== FALSE ||
				stripos($line, " Botanical") !== FALSE ||
				stripos($line, " Garden") !== FALSE ||
				stripos($line, "Lichens of Florida") !== FALSE) continue;
			if(preg_match("/^[Cc][0o][1Il!|]{2}(?:\\.\\s|ect).*/", $line)) continue;
			if(preg_match("/^[LIl][FPf][. -]([OQSZl|I!0-9.]{1,3}+)[ -](.*)/", $line, $lMats)) {
				$recordNumber = "LF-".$this->replaceMistakenNumbers(str_replace(".", "", trim($lMats[1])));
				if(count($lMats) > 2) $line = trim($lMats[2]);
				else $line = "";
				if($foundSciName) break;
			}
			if(strlen($line) > 6 && !$foundSciName && !$this->isMostlyGarbage($line, 0.60)) {
				if(preg_match("/(.*)\\b(?:C[o0][1!|lI]{2}(?:\\.|[ec]\{2}t)|Det(?:\\.|ermine)).*/i", $line, $mats)) {
					if(count($mats) > 1) $line = $mats[1];
					else continue;
					if($this->isMostlyGarbage($line, 0.60)) continue;
				}
				if(preg_match("/\\s(?:&|var\\.?|s(?:ub)?sp\\.|f\\.)$/i", $line) && $index < $numLines) {
					$l = trim($lines[$index++]);
					$line .= " ".$l;
				}
				if($index < $numLines) {
					$l = trim($lines[$index++]);
					if(preg_match("/^(var\\.?|s(?:ub)?sp\\.|f\\.)\\s(.*)/i", $l, $tMats)) $line .= " ".$l;
					else $index--;
					if($index < $numLines) {
						$l = trim($lines[$index++]);
						if(preg_match("/^(?:\+|on)\\s/i", $l)) $line .= " ".$l;
						else $index--;
					}
				}
				$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
						$foundSciName = true;
					}
					if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists ('recordNumber', $psn)) {
						$trn = $psn['recordNumber'];
						if(strlen($trn) > 0) $recordNumber = $trn;
					}
					if(array_key_exists ('substrate', $psn)) {
						$substrate = $psn['substrate'];
						if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
					}
					if($foundSciName && strlen($recordNumber) > 0) break;
				}
			}
		}
		if(strlen($location) > 0) {
			$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°|".
				"T\\.?\\s?".$possibleNumbers."{1,3}\\s?[NS]\\.?,?\\sR\\.?\\s?".$possibleNumbers."{1,3}[EW]\\.?,?\\s(?i:S(?:[ec]{2}(?:\\.|t(?:\\.|ion))))\\.?".$possibleNumbers."{1,3}+);?\\s?(.*)/s";
			if(preg_match($termLocPat, $location, $lMats)) {
				$location = preg_replace(
					array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
					array("", " ", " "),
					trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				$habitat = preg_replace(
					array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
					array("", " ", " "),
					trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
			} else {
				$pat = "/(.*?)(?:".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3}\\s?'[NS],\\s?".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3})\\s?'[EW](.*)/";
				if(preg_match($pat, $location, $lMats)) {
					$location = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					$habitat = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				}
			}
			if(strlen($location) > 0) {//echo "\nline 5743, location: ".$location."\n";
				$sCPos = strpos($location, ";");
				if($sCPos !== FALSE) {
					$habitat = trim(substr($location, $sCPos+1));
					$location = trim(substr($location, 0, $sCPos));
				}
				if(preg_match("/(.*?)".$possibleNumbers."{1,2}+[- ]?(?:".$possibleMonths.")[- ]?".$possibleNumbers."{4}.*/i", $location, $mMats)) {
					$location = trim($mMats[1]);
				}
				if(preg_match("/(.*?)C[o0][[|!1l]{2}(?:ect|\\.).*/i", $location, $mMats)) $location = trim($mMats[1]);
			}

		}
		if(strlen($habitat) > 0) {
			$lPat = "/.*(?:".$possibleNumbers."{1,3}+\\s?°\\s?".$possibleNumbers."{1,3}+\\s?'\\s?(?:".$possibleNumbers."{1,3}+\\s?\")?\\s?[EW])(.*)/";
			$termLocPat = "/.*?(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?°\\s?".$possibleNumbers."{1,3}+\\s?'\\s?(?:".$possibleNumbers."{1,3}+\\s?\")?\\s?[EW]|".
				"T\\.?\\s?".$possibleNumbers."{1,3}\\s?[NS]\\.?,?\\sR\\.?\\s?".$possibleNumbers."{1,3}[EW]\\.?,?\\s(?:S|sec(?:tion)?)\\.?\\s?".$possibleNumbers."{1,3}+);?\\s?(.*)/s";
			if(preg_match($termLocPat, $habitat, $lMats)) {
				$habitat = trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			} else {
				$pat = "/.*?(?:".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3}\\s?'[NS],\\s?".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3})\\s?'[EW](.*)/";
				if(preg_match($pat, $habitat, $lMats)) {
					$habitat = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				}
			}
			if(strlen($habitat) > 0) {
				if(preg_match("/(.*?)".$possibleNumbers."{1,2}+[- ]?(?:".$possibleMonths.")[- ]?".$possibleNumbers."{4}.*/", $habitat, $mMats)) {
					$habitat = trim($mMats[1]);
				}
				if(strlen($substrate) == 0) {
					$onPos = stripos($habitat, "on ");
					if($onPos !== FALSE && $onPos == 0) {
						$commaPos = strpos($habitat, ",");
						if($commaPos !== FALSE) $substrate = trim(substr($habitat, 0, $commaPos));
						else {
							$dotPos = strpos($habitat, ".");
							if($dotPos !== FALSE) $substrate = trim(substr($habitat, 0, $dotPos));
						}
					} else {
						$onPos = stripos($habitat, ". on ");
						if($onPos !== FALSE) {
							$commaPos = strpos($habitat, ",");
							if($commaPos !== FALSE) $substrate = ltrim(rtrim(substr($habitat, 0, $commaPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							else {
								$dotPos = strpos($habitat, ".");
							if($dotPos !== FALSE) $substrate = ltrim(rtrim(substr($habitat, 0, $dotPos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							}
						}
					}
				}
				$hPat = "/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|[lI!|]ocality|[lI!|]ocation|".
					"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|l?s[:.]?)|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|".
					"[lI!|]at[li!|]tude|(?:THE )?NEW\\s\\w{4}\\sBOTAN.+|(?:(?:Assoc(?:[,.]|".
					"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|spp[,.]?|with)[:;]?)|(?:along|together)\\swith))/i";

				if($this->isCompleteGarbage($habitat)) $habitat = "";
				else {
					$pat = "/(.*?)\\b(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
						$possibleNumbers."|3[1Iil!|OQ0\]])[ -](?:".$possibleMonths.")\\b.*/i";
					if(preg_match($pat, $habitat, $mats)) {
						$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					} else if(preg_match("/((?s).*?)(?:".$possibleMonths.")\\s(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
						$possibleNumbers."|3[1Iil!|OQ0\]])[,.]?\\s(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}/i", $habitat, $mats)) {
						$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					} else if(preg_match("/((?s).*?)(?:".$possibleMonths.")(?:[,.]\\s|[ -])(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}/i", $habitat, $mats)) {
						$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					}
					if(preg_match("/((?s).*?)(?:Sec?(:\\.|tion)|(?:C[OQ0][1Iil!|]{1,2}(?:\\.|ection|ected))|".
						"(?:Latitude[ ,:;])|(?:N\wW YORK)|(?:B[O0]TAN[1!|I]CAL))/i", $habitat, $mats)) $habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					$trsPatStr = "/(?(?=\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)".
						"R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".
						$possibleNumbers."{1,3})\\b)".
					//if the condition is true then the form is TRS
						"\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".
						$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})\\b(.+)|".
					//else the form is STR
						"\\b[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3}),?(?:\\s|\\n|\\r\\n)T(?:\\.|wnshp.?|ownship)?\\s?(?:".
						$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?\\b(.+))/is";
					if(preg_match($trsPatStr, $habitat, $trsMatches)) $habitat = trim($trsMatches[1]);
					$pat2 = "/\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
						"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
						"(?:".$possibleNumbers."{1,3}\\s?\"?)?)?\\s?[EW](.*)/";
					if(preg_match($pat2, $habitat, $hMats2)) $habitat = trim($hMats2[1]);
					if(preg_match($hPat, $habitat, $hMats2)) $habitat = trim($hMats2[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					if(is_numeric($habitat)) $habitat = "";
				}
				$habitat = trim($habitat, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => "Florida",
			'country' => "USA",
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-")
		);
	}

	private function doLichensOfWesternNorthAmericaLabel($s) {
		//echo "\nDoing LichensOfWesternNothAmericaLabel\n";
		$pattern =
			array
			(
				"/40\* 13' 45\" North Latitude, 105' 21' West,/",
				"/41 North Latitude,/",
				"/Shushan fsl-5023\)/"
			);
		$replacement =
			array
			(
				"40° 13' 45\" North Latitude, 105° 21' West",
				"41°North Latitude,",
				"Shushan (sl-5023)"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$exsnumber = "";
		$scientificName = "";
		$substrate = "";
		$habitat = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		$associatedTaxa = "";
		$recordNumber = "";
		$identifiedBy = "";
		$municipality = "";
		$state_province = "";
		$recordedBy = "";
		$recordedById = "";
		$otherCatalogNumbers = "";
		$verbatimEventDate = "";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				if(stripos($recordedBy, " Thomson") !== FALSE) $recordedBy = "";
				else if(stripos($recordedBy, " Anderson") !== FALSE && stripos(substr($recordedBy, 0, strrpos($recordedBy, " ")), "R") !== FALSE) {
					$recordedBy = "Roger A. Anderson";
					if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
					if(array_key_exists('collectorID', $collectorInfo)) "181";
					if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
					if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				} else {
					if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
					if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
					if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
					if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				}
			}
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$county = "";
		$country = "";
		$location = "";
		$date_identified = array();
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		if(strlen($identifiedBy) == 0) {
			$identifier = $this->getIdentifier($s, $possibleMonths);
			if($identifier != null) {
				$identifiedBy = $identifier[0];
				$date_identified = $identifier[1];
			}
		}
		$possibleNumbers = "[OQSZl|I!0-9]";
		$firstPart = "";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {
			$state_province = trim($countyMatches[5]);
			$country = trim($countyMatches[3]);
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			$location = ltrim(rtrim($countyMatches[4], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$county = trim($countyMatches[2]);
			//echo "\nline 5610, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
		}
		if(strlen($firstPart) > 0) $s = $firstPart;
		$lines = explode("\n", $s);
		foreach($lines as $line) {
			if(preg_match("/.*[1Il!|][CE]H[CE]N[S5]\\s[O0Q]f\\sWestern\\sN[O0Q]rth\\sAm[CE]r[1Il!|]ca.*/i", $line)) continue;
			if(preg_match("/.*Anders[O0Q]n and Shushan.*/i", $line)) continue;
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/.*N[o0Q]\\.?\\s([SZl|I!1-9]".$possibleNumbers."{0,2}+)[.,]?(.*)/i", $line, $mats)) {
				$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
				if(strcmp($exsnumber, $recordNumber) == 0) $recordNumber = "";
				$lookingForSciName = true;
				$temp = trim($mats[2]);
				if(strlen($temp) > 6 && strpos($temp, " ") !== FALSE) {
					$psn = $this->processSciName($temp);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) {
							$scientificName = $psn['scientificName'];
						}
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn) && strlen($recordNumber) == 0) {
							$trn = $psn['recordNumber'];
							if(strlen($trn) > 0) $recordNumber = $trn;
						}
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
						break;
					}
				}
			}
			if(strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
					}
					if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists ('recordNumber', $psn) && strlen($recordNumber) == 0) {
						$trn = $psn['recordNumber'];
						if(strlen($trn) > 0) $recordNumber = $trn;
					}
					if(array_key_exists ('substrate', $psn)) {
						$substrate = $psn['substrate'];
						if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
					}
					break;
				}
			}
		}
		if(strlen($location) > 0) {//echo "\nline 5648, location: ".$location."\n";
			$lines = explode("\n", $location);
			foreach($lines as $line) {
				if(strlen($line) > 6 &&
					!$this->isMostlyGarbage($line, 0.60) &&
					strlen($recordNumber) == 0) {
					$mPat = "/^".$possibleNumbers."{1,2}+[- ]?(?:".$possibleMonths.")[- ]?".$possibleNumbers."{4}(.*)/i";
					if(preg_match($mPat, $line, $matches3)) $line = trim($matches3[1]);
					if(strlen($recordedBy) > 0) {
						$pos = strripos($recordedBy, " ");
						if($pos !== FALSE) {
							$lName = trim(substr($recordedBy, $pos+1));
							if(preg_match("/.*".$lName."-(".$possibleNumbers."{1,6}+[A-Fa-f]?)/", $line, $mats)) $recordNumber = $this->replaceMistakenNumbers($mats[1]);
						}
					} else {
						if(preg_match("/.*BRY(?:[a-z]-|\\s)".$possibleNumbers."{1,6}+[A-Fa-f]?(.*)/", $line, $mats)) {
							$line = trim($mats[1]);
							if(strlen($line) > 6 && preg_match("/^([A-Za-z. ]{6,18})[- ](".$possibleNumbers."{1,6}+[A-Fa-f]?)/", $line, $mats2)) {
								$recordedBy = trim($mats2[1]);
								$recordNumber = $this->replaceMistakenNumbers($mats2[2]);
							}
						} else {
							if(preg_match("/^([A-Z][A-Za-z .]{1,18})[- ](".$possibleNumbers."{1,6}+[A-Fa-f]?)/", $line, $mats)) {
								$recordedBy = trim($mats[1]);
								$recordNumber = $this->replaceMistakenNumbers($mats[2]);
							}
						}
					}
				}
			}
			$location = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			if(preg_match("/(.*?)\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?°(.*)/is", $location, $matches)) {
				$location = trim($matches[1]);
				$rest = trim($matches[2]);
				if(preg_match("/(.*?)\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?°(.*)/is", $location, $matches)) {
					$location = trim($matches[1]);
					$rest = trim($matches[2])." ".$rest;
				}
				if(preg_match("/.*?\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?°?\\s?(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,3}+)?'\\s?(?:".$possibleNumbers."{1,3}\"?\\s?)?)?W(?:\\.|est)?\\sLong(?:\\.|itude)?(.*+)/is", $rest, $matches2)) {
					$habitat = trim(ltrim($matches2[1], " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"));
				} else if(preg_match("/.*?\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?°?\\s?(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,3}+)?'\\s?(?:".$possibleNumbers."{1,3}\"?\\s?)?)?W(?:\\.|est)?\\sLong(?:\\.|itude)?(.*+)/is", $location, $matches2)) {
					$habitat = trim(ltrim($matches2[1], " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"));
				}
				if(strlen($habitat) > 0 && strlen($elevation) > 0 && preg_match("/(.*)\\b".$elevation."(.*)/i", $habitat, $matches3)) {
					$temp = trim($matches3[1]);
					$temp2 = trim($matches3[2]);
					if(strlen($temp) > 6) $habitat = $temp;
					else if(strlen($temp2) > 6) {
						$habitat = $temp2;
						if(preg_match("/alt\\.?+\\s(.*)/", $habitat, $mats)) $habitat = trim($mats[1]);
					}
				}
			} else if(strlen($elevation) > 0) {
				if(preg_match("/(.*)\\b".$elevation."(.*)/i", $location, $matches3)) $location = trim($matches3[1]);
			}
			if(strlen($habitat) > 0 && preg_match("/(.*?)".$possibleNumbers."{1,2}+[- ]?(?:".$possibleMonths.")[- ]?".$possibleNumbers."{4}.*/i", $habitat, $matches3)) $habitat = trim($matches3[1]);
		}
		if(stripos($recordedBy, "Shushan") !== FALSE) {
			if(preg_match("/\(s[I1!|l]-(".$possibleNumbers."{2,5}+[A-Fa-f]?)\)/i", $recordNumber, $mats)) $recordNumber = "sI-".$this->replaceMistakenNumbers($mats[1]);
			else if(preg_match("/s[I1!|l]-(".$possibleNumbers."{2,5}+[A-Fa-f]?)/i", $recordNumber, $mats)) $recordNumber = "sI-".$this->replaceMistakenNumbers($mats[1]);
		} else {
			if(strcmp($recordedBy, "SDL") == 0) {
				$recordedBy = "Steven D. Leavitt";
				$recordedById = "9957";
			}
		}
		if(strlen($habitat) > 0 && strlen($substrate) == 0) {
			if(preg_match("/^(on\\s.*?)\\s(?:and\\s)?+((?:over|along|at)\\s.*)/i", $habitat, $mats)) {
				$substrate = trim($mats[1]);
				$habitat = trim($mats[2]);
			} else if(preg_match("/^(on\\s.*)/i", $habitat, $mats)) {
				$substrate = trim($mats[1]);
				$habitat = "";
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'municipality' => ucfirst(trim($municipality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-\(\)"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedById' => $recordedById,
			'otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimCoordinates' => $verbatimCoordinates,
			'ometid' => "242",
			'exsnumber' => $exsnumber
		);
	}

	private function isLichensOfWesternNorthAmericaLabel($s) {
		$pat = "/.*[1Il!|][CE]H[CE]N[S5]\\s[O0Q]f\\sWestern\\sN[O0Q]rth\\sAm[CE]r[1Il!|]ca.*/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isLewisAndClarkCavernsLabel($s) {
		$pat = "/L[EF]W\\s?[1Il!|]S\\s?A.{2,7}\\s?CLARK\\s?C\\s?AV\\s?ERNS\\s?STATE\\sPARK(.*)/s";
		if(preg_match($pat, $s)) return true;
		return false;
	}

	private function isLichensOfFloridaLabel($s) {
		$lfPat = "/.*(?:L[1Il!|][CE]H[CE]N|Cr[ypqg]{2}t[O0Q][ypqg]am)[S5]\\s[O0Q]F\\sFL[O0Q]R[1Il!|]DA?+.*/is";
		if(preg_match($lfPat, $s)) return true;
		else return false;
	}

	private function isPlantsOfFloridaLabel($s) {
		$lfPat = "/.*P[1Il!|]ANT[S5]\\s[O0Q]F\\sF[1Il!|][O0Q].[1Il!|]DA?+.*/is";
		if(preg_match($lfPat, $s)) return true;
		else return false;
	}

	private function isHerbariumOfForestServiceLabel($s) {
		$lfPat = "/.*HERBAR[1Il!|]UM\\s?[O0Q]F\\s?THE\\s?F[O0Q]REST\\s?SERV[1Il!|]CE.*/is";
		if(preg_match($lfPat, $s)) return true;
		else return false;
	}

	private function isHerbariumOfMontanaStateUniversityLabel($s) {
		$lfPat = "/.*M\\sOF\\sM[O0Q]NTANA\\s?STATE.*/is";
		if(preg_match($lfPat, $s)) return true;
		else {
			$lfPat = "/.*OF\\sM[O0Q]NTANA\\s?STATE\\s?UN[1Il!|]V.*/is";
			if(preg_match($lfPat, $s)) return true;
			else return false;
		}
	}

	private function isMontanaStateUniversityHerbariumLabel($s) {
		$lfPat = "/.*M[O0Q]NTANA\\s?STATE\\s?UN[1Il!|]VERSITY\\s?HERBAR[1Il!|]U.*/is";
		if(preg_match($lfPat, $s)) return true;
		else return false;
	}

	private function isLichensOfGeorgiaLabel($s) {
		$lfPat = "/.*L[1Il!|][CE]H[CE]N[S5]\\s[O0Q]F\\s[CG]E[O0Q]RG[1Il!|]A.*/is";
		if(preg_match($lfPat, $s)) return true;
		else return false;
	}

	private function isLichensAndMossesOfYellowstoneLabel($s) {
		$yPat = "/.*L[1Il!|][CE]H[CE]N[S5]\\sAND\\sM[O0Q]sses.*(?:[O0Q]F)?.*YE[1Il!|]{2}[O0Q]WST[O0Q]NE.*/is";
		if(preg_match($yPat, $s)) return true;
		else return false;
	}

	private function doMTLichensOfLabel($s) {
		//echo "\nDoing LichensOfLabel\n";
		$pattern =
			array
			(
				"/caesiocmerea/",
				"/Amandinea \(Bue[1Il!|\/]{3}a\)\\s/i",
				"/Matanuska-[S5]us[1Il!|]tna?/i",
				"/\\b.{1,2}mb(?:[1Il!|]{3}|iU)car[1Il!|].\\s/i",
				"/\\sXanthoria\\s/i",
				"/\\b.snea\\s.{1,3}ir[tl]a\\b/i",
				"/\\sUtter\\s/",
				"/Pe.tigera necker./i",
				"/\\.1versman/",
				"/<=>haron Eversman/i",
				"/Colli 3, Iversman/",
				"/Colxj o. Â¿tversman/"
			);
		$replacement =
			array
			(
				"caesiocinerea",
				"Amandinea ",
				"Matanuska Susitna",
				"\Umbilicaria ",
				"\nXanthoria ",
				"Usnea hirta",
				" litter ",
				"Peltigera neckeri",
				"Eversman",
				"Sharon Eversman",
				"Coll: S. Eversman",
				"Coll: S. Eversman"
			);
		$recordedBy = '';
		$recordNumber = '';
		$recordedById = '';
		$otherCatalogNumbers = '';
		$identifiedBy = '';
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				$pos = strrpos($recordedBy, " ");
				if($pos !== FALSE) {
					$temp = trim(substr($recordedBy, $pos+1));
					$l = strlen($temp);
					if($l > 2 && $l < 5 && strcasecmp(substr($temp, 0, 3), "s.n") == 0) $recordedBy = trim(substr($recordedBy, 0, $pos));
				}
			}
			if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
			if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
			if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
			if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
		}
		$county = "";
		$country = "";
		$scientificName = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$substrate = "";
		$location = "";
		$habitat = "";
		$state_province = "";
		$firstPart = "";
		$lastPart = "";
		$elevation = "";
		$date_identified = array();
		$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		if(strlen($identifiedBy) == 0) {
			$identifier = $this->getIdentifier($s, $possibleMonths);
			if($identifier != null) {
				$identifiedBy = $identifier[0];
				$date_identified = $identifier[1];
			}
		}
		if(strcasecmp($identifiedBy, "se") == 0) $identifiedBy = "Sharon Eversman";
		if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sM[O0Q]ntana(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Montana";
			$country = "U.S.A.";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sYe[1Il!|]{2}[O0Q]wstone\\sNat[1Il!|][O0Q]na[1Il!|]\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
		} else if(preg_match("/[ce]hens\\s[O0Q]F\\sYell[O0Q]wstone\\sNat[1Il!|][O0Q]na[1Il!|]\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
		} else if(preg_match("/L[EF]W\\s?[1Il!|]S\\s?A.{2,7}\\s?CLARK\\s?C\\s?AV\\s?ERNS\\s?STATE\\sPARK(.*)/s", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Montana";
			$county = "Jefferson";
			$country = "U.S.A.";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sHEADWATERS\\s?STATE\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Montana";
			$county = "Gallatin";
			$country = "U.S.A.";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\s[CG]rand\\s?Tet[O0Q]n\\s?Nati[O0Q]na[1Il!|]\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Wyoming";
			$county = "Teton";
			$country = "U.S.A.";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sWASH[1Il!|]NGTON\\sSTATE(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Washington";
			$country = "U.S.A.";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sNEW\\sMEX[1Il!|]CO(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "New Mexico";
			$country = "U.S.A.";
		} else if(preg_match("/.*[ce]h[ce]ns\\s[O0Q]F\\sCH[1Il!|]LKAT\\s?PASS(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			if(preg_match("/.*Br[1Il!|]t[1Il!|]sh\\sColumb[1Il!|]a/is", $s) || preg_match("/.*Canada/is", $s)) {
				$state_province = "British Columbia";
				$country = "Canada";
			} else {
				$state_province = "Alaska";
				$country = "U.S.A.";
				$county = "Haines";
			}
		} else if(preg_match("/Macro(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\s?Denal[1Il!|]\\s?State\\s?Park(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
		} else if(preg_match("/(?:Macro)?(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s(?:[O0Q]F|FR[O0Q]M)\\s[A-Za-z]{3,}\\b(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
		}
		//echo "\nline 6769, s: ".$s."\n";
		$workingSection = $s;
		$countyMatches = $this->findCounty($s, $state_province);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
			if(strlen($county) == 0) $county = trim($countyMatches[2]);
			if(strlen($state_province) == 0) $state_province = trim($countyMatches[5]);
			if(strlen($country) == 0) $country = trim($countyMatches[3]);
			$firstPart = trim($countyMatches[0]);
			$lastPart = trim($countyMatches[4]);
			if($this->countPotentialLocalityWords($lastPart) > $this->countPotentialLocalityWords($firstPart)) $workingSection = $lastPart;
			else $workingSection = $firstPart;
		}
		$lookForAssciatedTaxa = false;
		$foundSciName = false;
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 6784, line: ".$line."\n";
			if($foundSciName && strlen($line) > 6) {
				if(!$lookForAssciatedTaxa && preg_match("/^with[:;,]?\\s(.+)/i", $line, $mats)) {
					$line = trim($mats[1]);
					$lookForAssciatedTaxa = true;
				}
				if($lookForAssciatedTaxa) {
					$names = explode(",", $line);
					foreach($names as $name) {//echo "\nline 6792, name: ".$name."\n";
						$psn = $this->processSciName($name);
						if($psn != null) {
							if(array_key_exists('scientificName', $psn)) {
								$temp = $psn['scientificName'];
								if(strlen($associatedTaxa) == 0) $associatedTaxa = $temp;//trim($line);//
								else if(stripos($associatedTaxa, $temp) === FALSE) $associatedTaxa = trim($associatedTaxa, " ;:,").", ".$temp;//trim($line);//
								$pos = stripos($workingSection, $temp);
								if($pos !== FALSE) $workingSection = trim(substr($workingSection, $pos+strlen($temp)));
							}
							if(array_key_exists('substrate', $psn)) {
								$temp = $psn['substrate'];
								if(strlen($substrate) == 0) $substrate = $temp;
								else $substrate = trim($substrate, " ;:,").", ".$temp;
								if(preg_match("/(.*)".str_replace("/", "\/", $substrate)."/i", $associatedTaxa, $mats)) $associatedTaxa = trim($mats[1]);
							}
							//findCounty removes double quotes and changes "&" to "and" so put them back so they match
							if(strcasecmp($s, $workingSection) != 0) $line = trim(str_replace(array("\"", " & "), array("", " and "), $line));
							$pos = stripos($workingSection, $associatedTaxa);
							if($pos !== FALSE) $workingSection = trim(substr($workingSection, $pos+strlen($associatedTaxa)));
							else if(strlen($substrate) > 0) {
								if(strcasecmp($s, $workingSection) != 0) $substrate = trim(str_replace(array("\"", " & "), array("", " and "), $substrate));
								$pos = stripos($workingSection, $substrate);
								if($pos !== FALSE) $workingSection = trim(substr($workingSection, $pos+strlen($substrate)));
								else if(strlen($associatedTaxa) > 0) {
									if(strcasecmp($s, $associatedTaxa) != 0) $associatedTaxa = trim(str_replace(array("\"", " & "), array("", " and "), $associatedTaxa));
									$pos = stripos($workingSection, $associatedTaxa);
									if($pos !== FALSE) $workingSection = trim(substr($workingSection, $pos+strlen($associatedTaxa)));
								}
							}
						}
					}
				}
				break;
			} else {
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
					if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					//echo "\nline 6834, associatedTaxa: ".$associatedTaxa."\n";
					if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
					if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
					//echo "\nline 6837, substrate: ".$substrate."\n";
					//findCounty removes double quotes and changes & to and
					if(strcasecmp($s, $workingSection) != 0) $line = trim(str_replace(array("\"", " & "), array("", " and "), $line));
					$pos = stripos($workingSection, $line);
					if($pos !== FALSE) $workingSection = trim(substr($workingSection, $pos+strlen($line)));
					//$s = str_ireplace($line, "", $s);
					if(preg_match("/(.*)\\sw.?[il1|]th:?/i", $line, $mats)) {
						$scientificName = trim($mats[1]);
						$lookForAssciatedTaxa = true;
					}
					$foundSciName = true;
				}
			}
		}
		if(strlen($workingSection) == 0) {
			if(preg_match("/.+\\sCO(?:\\.|UNTY)[;:.,]?+(.+)/i", $s, $mats)) $workingSection = trim($mats[1]);
		}
		if(strlen($workingSection) > 0) {//echo "\nline 6857, workingSection: ".$workingSection."\n";
			$elevArr = $this->getElevation($workingSection);
			$temp = $elevArr[0];
			$temp2 = $elevArr[2];
			if(strlen($temp) > 0 && !preg_match("/^(?:above|be(?:low|yond|neath)|along|under)/i", $temp2)) $elevation = $elevArr[1];
			else {
				$elevArr = $this->getElevation($s);
				$temp = $elevArr[0];
				$temp2 = $elevArr[2];
				if(strlen($temp) > 0) {
					if(!preg_match("/^(?:above|be(?:low|yond|neath)|along|under)/i", $temp2)) $elevation = $elevArr[1];
					else {
						$elevArr = $this->getElevation($temp2);
						$temp = $elevArr[0];
						$temp2 = $elevArr[2];
						if(strlen($temp) > 0 && !preg_match("/^(?:above|be(?:low|yond|neath)|along|under)/i", $temp2)) $elevation = $elevArr[1];
					}
				}
			}
			if(strlen($substrate) == 0) {//echo "\nline 6850, workingSection: ".$workingSection."\n";
				if(preg_match("/(.+?).?\\s{1,2}(Located\\s.+)/is", $workingSection, $mats)) {
					$habitat = trim($mats[1]);
					$location = trim($mats[2]);
					if(preg_match("/^(On .+)(?:\\n\\r|\\r\\n|\\n|\\r)(.*)/is", $habitat, $mats)) {
						$substrate = trim($mats[1]);
						$habitat = trim($mats[2]);
					} else if(preg_match("/\\b(On\\s.+\\scommon)\\b[;:,.]?(.++)/is", $habitat, $mats)) {
						$substrate = trim($mats[1]);
						$habitat = trim($mats[2]);
					}
				} else if(preg_match("/\\b(On\\s.+?),?\\s(in\\s.+?),\\s(.+)/is", $workingSection, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6878, mats[".$i++."] = ".$mat."\n";
					$substrate = $mats[1];
					$habitat = trim($mats[2]);
					$location = trim($mats[3]);
				} else if(preg_match("/^(On .+)(?:\\n\\r|\\r\\n|\\n|\\r)(.*)(?:\\n\\r|\\r\\n|\\n|\\r)(.*)/i", $workingSection, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6882, mats[".$i++."] = ".$mat."\n";
					$substrate = $mats[1];
					$temp = trim($mats[2]);
					$temp2 = trim($mats[3]);
					$count1 = $this->countPotentialHabitatWords($temp);
					$count2 = $this->countPotentialHabitatWords($temp2);
					if($count2 > $count1) {
						$habitat = $temp2;
						if($this->countPotentialLocalityWords($temp)) $location = $temp;
					} else {
						if($count1 > 0) $habitat = $temp;
						if($this->countPotentialLocalityWords($temp2) > $count2) $location = $temp2;
						else if($count2 > 0) $habitat .= " ".$temp2;
					}

				} else if(preg_match("/^(On .+)(?:\\n\\r|\\r\\n|\\n|\\r)(.*)/i", $workingSection, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6885, mats[".$i++."] = ".$mat."\n";
					$substrate = $mats[1];
					$temp = trim($mats[2]);
					if($this->countPotentialHabitatWords($temp) > $this->countPotentialLocalityWords($temp)) $habitat = $temp;
					else $location = $temp;
				} else $location = $workingSection;
			} else if(preg_match("/(.+?).?\\s{1,2}(Located\\s.+)/is", $workingSection, $mats)) {
				$habitat = trim($mats[1]);
				$location = trim($mats[2]);
			} else if(preg_match("/^(On\\s.+?),?\\s(in\\s.+?)/i", $workingSection, $mats)) {
				$habitat = $mats[1];
				$location = trim($mats[2]);
			} else if(preg_match("/^(On .+)(?:\\n\\r|\\r\\n|\\n|\\r)(.*(?:\\n\\r|\\r\\n|\\n|\\r).*)/i", $workingSection, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6895, mats[".$i++."] = ".$mat."\n";
				$habitat = $mats[1];
				$location = trim($mats[2]);
			} else if(preg_match("/^(On .+)(?:\\n\\r|\\r\\n|\\n|\\r)(.*)/i", $workingSection, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6898, mats[".$i++."] = ".$mat."\n";
				$habitat = $mats[1];
				$location = trim($mats[2]);
			} else $location = $workingSection;
		}
		$substrate = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $substrate);
		$habitat = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat);
		$location = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location);
		if(strlen($elevation) > 1) {
			if(preg_match("/(.*?)(?:elev(?:\\.|ation)?[:;]?)?\\s?".$elevation."[,.]?\\s(On\\s.{3,})\\s(in\\s.+)/i", $location, $mats)) {
				$location = trim($mats[1]);
				if(strlen($substrate) == 0) {
					$substrate = trim($mats[2]);
					$habitat = trim($mats[3]);
				} else $habitat = trim($mats[2])." ".trim($mats[3]);
			} else if(preg_match("/(.*?)(?:elev(?:\\.|ation)?[:;]?)?\\s?".$elevation."[,.]?\\s(On\\s.*)/i", $location, $mats)) {
				$location = trim($mats[1]);
				if(strlen($substrate) == 0) $substrate = trim($mats[2]);
				else $habitat = trim($mats[2]);
			} else if(preg_match("/(.*?)(?:elev(?:\\.|ation)?[:;]?)?\\s?".$elevation."(.*)/i", $location, $mats)) {
				$mats1 = trim($mats[1]);
				$mats2 = trim($mats[2]);
				if($this->countPotentialLocalityWords($mats2) > $this->countPotentialLocalityWords($mats1)) {
					$location = $mats2;
					if($this->countPotentialHabitatWords($mats1) > 0) {
						if(strlen($substrate) == 0 && strcasecmp(substr($mats1, 0, 3), "on ") == 0) $substrate = $mats1;
						else $habitat .= " ".$mats1;
					}
				} else {
					$location = $mats1;
					if($this->countPotentialHabitatWords($mats2) > 0) {
						if(strlen($substrate) == 0 && strcasecmp(substr($mats2, 0, 3), "on ") == 0) $substrate = $mats2;
						else $habitat .= " ".$mats2;
					}
				}
			}
		}
		if(strlen($recordedBy) > 1) {
			if(preg_match(
				"/(.*?)\\s?".str_replace(array("[", "]", "(", ")", "?", "/"), array("\[", "\]", "\(", "\)", "\?", "\/"), $recordedBy)."(.*)/i",
				$location,
				$mats)
				) {
				$mats1 = trim($mats[1]);
				$mats2 = trim($mats[2]);
				if($this->countPotentialLocalityWords($mats2) > $this->countPotentialLocalityWords($mats1)) $location = $mats2;
				else $location = $mats1;
			} else if(preg_match(
				"/(.*?)\\s?".str_replace(array("[", "]", "(", ")", "?", "/"), array("\[", "\]", "\(", "\)", "\?", "\/"), $recordedBy)."(.*)/i",
				$habitat,
				$mats)
				) {
				$mats1 = trim($mats[1]);
				$mats2 = trim($mats[2]);
				if($this->countPotentialLocalityWords($mats2) > $this->countPotentialLocalityWords($mats1)) $habitat = $mats2;
				else $habitat = $mats1;
			}
		}
		if(strlen($recordNumber) > 1 &&
			preg_match(
				"/(.*?)\\s?".str_replace(array("[", "]", "(", ")", "?", "/"), array("\[", "\]", "\(", "\)", "\?", "\/"), $recordNumber)."(.*)/i",
				$location,
				$mats)
			) {
			$mats1 = trim($mats[1]);
			$mats2 = trim($mats[2]);
			if($this->countPotentialLocalityWords($mats2) > $this->countPotentialLocalityWords($mats1)) $location = $mats2;
			else $location = $mats1;
		}
		if(strlen($substrate) > 6) {
			$pos = strpos($substrate, "; ");
			if($pos !== FALSE) {
				$firstPart = trim(substr($substrate, 0, $pos));
				$secondPart = trim(substr($substrate, $pos));
				if(strlen($habitat) == 0) {
					if(strlen($location) == 0 && $this->countPotentialLocalityWords($secondPart) > $this->countPotentialHabitatWords($secondPart)) {
						$substrate = $firstPart;
						$location = $secondPart;
					} else if($this->countPotentialHabitatWords($secondPart) > 0) {
						$substrate = $firstPart;
						$habitat = $secondPart;
					}
				} else if(strlen($location) == 0) {
					if($this->countPotentialLocalityWords($secondPart) > 0) {
						$substrate = $firstPart;
						$location = $secondPart;
					}
				}
			}
		}
		if(strlen($habitat) > 6 && strlen($location) == 0) {
			$pos = strpos($habitat, "; ");
			if($pos !== FALSE) {
				$firstPart = trim(substr($habitat, 0, $pos));
				$secondPart = trim(substr($habitat, $pos+1));
				if($this->countPotentialLocalityWords($secondPart) > $this->countPotentialLocalityWords($firstPart)) {
					$habitat = $firstPart;
					$location = $secondPart;
				}
			}
			if(strlen($location) == 0) {
				$pos = strpos($habitat, ". ");
				if($pos !== FALSE) {
					$firstPart = trim(substr($habitat, 0, $pos));
					$secondPart = trim(substr($habitat, $pos+1));
					if(strlen($firstPart) > 6 && strlen($secondPart) > 6 &&
						$this->countPotentialLocalityWords($secondPart) > $this->countPotentialLocalityWords($firstPart) &&
						$this->countPotentialHabitatWords($firstPart) > $this->countPotentialHabitatWords($secondPart)
					) {
						$habitat = $firstPart;
						$location = $secondPart;
					}
				}
			}
		}
		if(strlen($location) > 6 && strlen($habitat) == 0) {
			$pos = strpos($location, "; ");
			if($pos !== FALSE) {
				$firstPart = trim(substr($location, 0, $pos));
				$secondPart = trim(substr($location, $pos+1));
				if($this->countPotentialLocalityWords($secondPart) > $this->countPotentialLocalityWords($firstPart) &&
					$this->countPotentialHabitatWords($firstPart) > 0) {
					if(strlen($substrate) == 0 && strcasecmp(substr($firstPart, 0, 3), "on ") == 0) $substrate = $firstPart;
					else $habitat = $firstPart;
					$location = $secondPart;
				} else if($this->countPotentialHabitatWords($secondPart) > $this->countPotentialHabitatWords($firstPart) &&
					$this->countPotentialLocalityWords($firstPart) > 0) {
					if(strlen($substrate) == 0 && strcasecmp(substr($secondPart, 0, 3), "on ") == 0) $substrate = $secondPart;
					else $habitat = $secondPart;
					$location = $firstPart;
				}
			}
			if(strlen($habitat) == 0) {
				$pos = strpos($location, ". ");
				if($pos !== FALSE) {
					$firstPart = trim(substr($location, 0, $pos));
					$secondPart = trim(substr($location, $pos+1));
					if(strlen($firstPart) > 6 && strlen($secondPart) > 6) {
						if($this->countPotentialLocalityWords($secondPart) > $this->countPotentialLocalityWords($firstPart) &&
							$this->countPotentialHabitatWords($firstPart) > $this->countPotentialHabitatWords($secondPart)
						) {
							if(strlen($substrate) == 0 && strcasecmp(substr($firstPart, 0, 3), "on ") == 0) $substrate = $firstPart;
							else $habitat = $firstPart;
							$location = $secondPart;
						} else if($this->countPotentialLocalityWords($firstPart) > $this->countPotentialLocalityWords($secondPart) &&
							$this->countPotentialHabitatWords($secondPart) > $this->countPotentialHabitatWords($firstPart)
						) {
							if(strlen($substrate) == 0 && strcasecmp(substr($secondPart, 0, 3), "on ") == 0) $substrate = $secondPart;
							else $habitat = $secondPart;
							$location = $firstPart;
						}
					}
				}
			}
			if(strlen($location) > 6) {
				if(preg_match("/^(?:along\\s)?with\\s([a-zA-Z]{2,}\\s[a-zA-Z]{2,}\\.?),\\s?(([a-zA-Z]{2,}\\s[a-zA-Z]{2,}\\.?\\s)(.*))/i", $location, $mats)) {
					$psn = $this->processSciName($mats[1]);
					if($psn != null) {
						if(array_key_exists('scientificName', $psn)) {
							$temp = $psn['scientificName'];
							if(strlen($associatedTaxa) == 0) $associatedTaxa = $this->formatSciName($temp);
							else $associatedTaxa .= ", ".$this->formatSciName($temp);
							$location = trim($mats[3]);
							$psn = $this->processSciName($location);
							if($psn != null) {
								if(array_key_exists('scientificName', $psn)) {
									$temp = $psn['scientificName'];
									if(strlen($associatedTaxa) == 0) $associatedTaxa = $this->formatSciName($temp);
									else if(stripos($associatedTaxa, $temp) === FALSE) $associatedTaxa .= ", ".$this->formatSciName($temp);
									$location = trim(substr($location, stripos($location, $temp)+strlen($temp)));
									if(array_key_exists('substrate', $psn) && strlen($substrate) == 0) {
										$substrate = $psn['substrate'];
										$location = trim(substr($location, stripos($location, $substrate)+strlen($substrate)));
									}
								}
							} else {
								$psn = $this->processSciName($mats[3]);
								if($psn != null) {
									if(array_key_exists('scientificName', $psn)) {
										$temp = $psn['scientificName'];
										if(strlen($associatedTaxa) == 0) $associatedTaxa = $this->formatSciName($temp);
										else if(stripos($associatedTaxa, $temp) === FALSE) $associatedTaxa .= ", ".$this->formatSciName($temp);
										$location = trim($mats[4]);
									}
								}
							}
						}
					}
				} else if(preg_match("/^(?:along\\s)?with\\s(([a-zA-Z]{2,}\\s[a-zA-Z]{2,}\\.?)\\s?(.*))/i", $location, $mats)) {
					$psn = $this->processSciName($mats[1]);
					if($psn != null) {
						if(array_key_exists('scientificName', $psn)) {
							$temp = $psn['scientificName'];
							if(strlen($associatedTaxa) == 0) $associatedTaxa = $this->formatSciName($temp);
							else if(stripos($associatedTaxa, $temp) === FALSE) $associatedTaxa .= ", ".$this->formatSciName($temp);
							$location = trim(substr($location, stripos($location, $temp)+strlen($temp)));
							if(array_key_exists('substrate', $psn) && strlen($substrate) == 0) {
								$substrate = $psn['substrate'];
								$location = trim(substr($location, stripos($location, $substrate)+strlen($substrate)));
							}
						}
					} else {
						$psn = $this->processSciName($mats[2]);
						if($psn != null) {
							if(array_key_exists('scientificName', $psn)) {
								$temp = $psn['scientificName'];
								if(strlen($associatedTaxa) == 0) $associatedTaxa = $this->formatSciName($temp);
								else if(stripos($associatedTaxa, $temp) !== FALSE) $associatedTaxa .= ", ".$this->formatSciName($temp);
								$location = trim($mats[3]);
							}
						}
					}
				}
			}
		}
		if(strlen($substrate) > 6) {
			if(preg_match("/(.{3,})\\s((?:along|between|above|within|near)\\s.{3,})/i", $substrate, $mats)) {
				$substrate = trim($mats[1]);
				$temp = trim($mats[2]);
				$pos = strrpos($substrate, ",");
				if($pos !== FALSE) {
					$temp2 = trim(substr($substrate, $pos+1));
					if(strpos($temp2, " ") === FALSE) {
						$substrate = substr($substrate, 0, $pos);
						$temp = $temp2." ".$temp;
					}
				}
				$habitat = trim($temp." ".$habitat);
			}
		}
		if(strlen($habitat) > 12) {
			if(preg_match("/(.+)\\sC[o0][l1I|!]{1,2}\\.\\s/i", $habitat, $mats)) $habitat = trim($mats[1]);
			if(strlen($elevation) > 3 && preg_match("/(.*?)(?:elev(?:\\.|ation)?[:;]?)?\\s?".str_replace(array("[", "]", "(", ")", "?", "/"), array("\[", "\]", "\(", "\)", "\?", "\/"), $elevation)."[,.]?\\s/i", $habitat, $mats)) $habitat = trim($mats[1]);
			if(preg_match("/(.*)\\sElevat[il!1|][o0]n[,.;:]?\\b/", $habitat, $mats)) $habitat = trim($mats[1]);
			if(preg_match("/(.*)\\sLat[il!1|]tude[,.;:]?\\b/", $habitat, $mats)) $habitat = trim($mats[1]);
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'country' => trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedById' => trim($recordedById, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-")
		);
	}

	private function countPotentialLocalityWords($pLoc) {
		$lWords = array(" road", " Highway", " hway", " area", " path ", " forest", " route ", " range", "city ",
			" trail", " mountain", " wilderness", " canyon", " state ", " loop", " mount ", " pass", " drive", " picnic ",
			" slope", " Locat", " mile", " km ", " mi.", " km.", " national ", " park", " island", " camp", "falls", " county ",
			" district", " junction", " service ", " station", "town", "coast", "shore", " peninsula", " entrance");
		$result = 0;
		foreach($lWords as $lWord) if(stripos($pLoc, $lWord) !== FALSE) $result++;
		if(preg_match("/.*\\s(?:N(?:[EW]|orth)?|S(?:[EW]|outh)?|E(?:ast)?|W(?:est)?)\\s[o0QD]f\\s.+/i", $pLoc)) $result++;
		return $result/(count(explode(" ", $pLoc))*count($lWords));
	}

	private function countPotentialHabitatWords($pHab) {
		$hWords = array(" near ", " area", " above ", " around ", " path ", "field", " rock", " quercus", "wood", "bottom", "abundant ", " aspen",
			"grass", " meadow", " forest", " mountain", " canyon", " ground", " mixed ", "salix", " acer ", " alder ", " tundra ", " abies",
			" slope", " outcrop", " boulder", " Granit", " limestone", " sandstone", " sandy ", " creek", " tree", "pine ", " soil", " bark",
			" open", " deciduous ", "exposed ", " shaded ", " aspect", "facing ", " pinus ", "habitat", "degrees", " coniferous ", "spruce",
			"substrate", "thuja", " box elder", " dry ", " damp ", " moist ", " wet ", " fir ", " fir,", " fir/", " basalt", " juniper", " plant",
			" moss", " crust", "sagebrush");
		$result = 0;
		foreach($hWords as $hWord) if(stripos($pHab, $hWord) !== FALSE) $result++;
		return $result/(count(explode(" ", $pHab))*count($hWords));
	}

	private function doBorealiAmericaniLabel($s) {//echo "\nDoing BorealiAmericaniLabel\n";
		$pattern =
			array
			(
				"/\\bCo11' °\/ara E' Cummings\\./",
				"/\\b\(.?o[lI1!|]{2}[._*-]\\s/",
				"/\\bCo[lI1!|]{2}[._*-]\\.?\\s/",
				"/^Co\\s[lI1!|]{2}\\.\\s/",
				"/\/73\\s\(_6\$y\\s/",
				"/42\\s1\)\\..\\s/",
				"/loiva\\./",
				"/Co\\.,\\sCa.\\.,/i",
				"/The\\ss\*\\sA\\.\\sW[li!1|]{4}ams/",
				"/ab[o0]ut([1-9])/i",
				"/\\s={0,2}\\s?N[.,*]?\\s?A[.,*]?\\s?L/"
			);
		$replacement =
			array
			(
				"\nColl. Clara E. Cummings",
				"\nColl. ",
				"\nColl. ",
				"Coll. ",
				"67. ",
				"42 b. ",
				"Iowa",
				"County., California,",
				"Thomas A. Williams",
				"about \${1}",
				"\n=N.A.L"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 7235, s:\n".$s."\n";
		$exsnumber = "";
		$taxonRank = "";
		$infraspecificEpithet = "";
		$scientificName = "";
		$substrate = "";
		$state_province = "";
		$otherCatalogNumbers = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$recordedBy = "";
		$verbatimEventDate = "";
		$county = "";
		$country = "";
		$countyMatches = $this->findCounty($s, $state_province);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
			$county = trim($countyMatches[2]);
			$state_province = trim($countyMatches[5]);
			$country = trim($countyMatches[3]);
		}
		$foundSciName = false;
		$date_identified = array();
		$identified_by = '';
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				$pos = strrpos($recordedBy, " ");
				if($pos !== FALSE) {
					$temp = trim(substr($recordedBy, $pos+1));
					$l = strlen($temp);
					if($l > 2 && $l < 5 && strcasecmp(substr($temp, 0, 3), "s.n") == 0) $recordedBy = trim(substr($recordedBy, 0, $pos));
				}
			}
		}//echo "\nline 6721, recordedBy: ".$recordedBy.", recordNumber: ".$recordNumber."\n";
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 6112, line: ".$line."\n";
			if(preg_match("/.*[BS][ ._#-]{1,2}B[o0]rea[1Il!|]{2}[ ._#-]{1,2}Amer[1Il!|]can[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			if(preg_match("/.*[1Il!|]CHEN[BES5]{2}[ ._#-]{1,2}B[O0Q].{2}a[1Il!|]{2}[ ._#-]{1,2}Amer[1Il!|]can[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			if(preg_match("/.*[1Il!|]{3,5}ams\\sand\\sA\\.\\s[HB]\\.\\sSeym[o0].*/i", $line)) continue;
			if(preg_match("/.*(?:\\spublish|Second|edition|prepare|\\sdistribut|\\sUnivers|\\sDUKE\\sU|erbarium|Coll[,.]\\s|\\sDecades).*/i", $line)) continue;
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if(strcmp(substr($line, 0, 1), "=") == 0) $line = trim(substr($line, 1));
				if(strlen($line) > 6 &&
					!$this->isMostlyGarbage($line, 0.60) &&
					preg_match("/^[^a-zA-Z0-9]{0,3}?([SZlU|I!1-9&][.,_]|[\]\[OQSZlU|I!0-9&]{2,3}\\s?[a-fA-F]?)[.,_]?\\s(.*)/", $line, $mats)) {
					if(!preg_match("/.{0,3}?Co[1Il!|]{2}.?\\.?\\s/i", $line) && !preg_match("/.{0,3}?C?oll\\.?\\s/i", $line)) {
						$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
						$mats2 = trim(str_replace(",", "", $mats[2]));
						$psn = $this->processSciName($mats2);
						if($psn != null) {
							if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
							if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							//if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
							if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
						} else if(stripos($mats2, $recordedBy) === FALSE) {
							$scientificName = trim($mats[2]);
							if(preg_match("/(.+)\\s(var\\.?|ssp\\.|f\\.|subsp\\.?)\\s(.+)/i", $scientificName, $mats2)) {
								$taxonRank = trim($mats2[2]);
								$infraspecificEpithet = trim($mats2[3]);
								$scientificName = trim($mats2[1]);
								if(preg_match("/(.+)\\s(on\\s.+)/i", $infraspecificEpithet, $mats3)) {
									$substrate = trim($mats3[2]);
									$infraspecificEpithet = trim($mats3[1]);
								}
							} else if(preg_match("/(.+)\\s(on\\s.+)/i", $scientificName, $mats2)) {
								$substrate = trim($mats2[2]);
								$scientificName = trim($mats2[1]);
							}
						}
						$foundSciName = true;
						continue;
					}
				}
			}
			$onPos = stripos($line, "on ");
			if($onPos !== FALSE && $onPos < 2) $substrate = $line;
			else {
				if(strlen($county) == 0) {
					$commaPos = strpos($line, ",");
					if($commaPos !== FALSE && !$this->isMostlyGarbage($line, 0.60)) {
						$potentialCityOrCounty = trim(substr($line, 0, $commaPos));
						$rest = trim(substr($line, $commaPos+1));
						$pos = strpos($rest, ",");
						$potentialState = "";
						if($pos !== FALSE) {
							$potentialState = trim(substr($rest, 0, $pos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
							$rest = trim(substr($rest, $pos+1));
						} else {
							$pos = strpos($rest, ".");
							if($pos !== FALSE) {
								$potentialState = trim(substr($rest, 0, $pos), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
								$rest = trim(substr($rest, $pos+1));
							}
						}
						$cArray = $this->getCounty($potentialCityOrCounty);
						if($cArray != null) {
							$size = count($cArray);
							if($size == 1) {
								$cArra = $cArray[0];
								if(array_key_exists ('stateProvince', $cArra)) {
									$t = $cArra['stateProvince'];
									if(strcasecmp($t, $potentialState) == 0) {
										$state_province = $t;
										if(array_key_exists ('county', $cArra)) $county = $cArra['county'];
										if(array_key_exists ('country', $cArra)) $country = $cArra['country'];
									}
								}
							} else if($size > 1) {
								foreach($cArray as $cArra) {
									if(array_key_exists ('stateProvince', $cArra)) {
										$t = $cArra['stateProvince'];
										if(strcasecmp($t, $potentialState) == 0) {
											$state_province = $t;
											if(array_key_exists ('county', $cArra)) $county = $cArra['county'];
											if(array_key_exists ('country', $cArra)) $country = $cArra['country'];
											break;
										}
									}
								}
							}
						}
						if(strlen($county) == 0 && strlen($potentialState) > 1) {
							$stateAndCountry = $this->getStateOrProvince($potentialState);
							if($stateAndCountry != null) {
								$state_province = $stateAndCountry[0];
								$country = $stateAndCountry[1];
							}
						}
					}
				}
				if(strlen($line) > 6 && strcmp(substr($line, 0, 5), "var. ") == 0) {
					$taxonRank = "var.";
					$infraspecificEpithet = trim(substr($line, 5));
					$onPos = stripos($infraspecificEpithet, " on ");
					if($onPos !== FALSE) {
						if(strlen($substrate) == 0) $substrate = trim(substr($infraspecificEpithet, $onPos));
						$infraspecificEpithet = trim(substr($infraspecificEpithet, 0, $onPos));
					}
				}
			}
			if(preg_match("/=?\\s?N[,.]?\\s?A[,.]?\\s?[a-zA-Z]{1,2}\\.?\\s?([OQSZlU|I!0-9&]{1,3}[a-fA-F]?)[,.]?/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 7275, mats[".$i++."] = ".$mat."\n";
				$temp = $this->replaceMistakenNumbers(trim($mats[1]));
				if(is_numeric($temp)) {
					if($temp > 0) $otherCatalogNumbers = "N. A. L. ".$temp;
				} else $otherCatalogNumbers = "N. A. L. ".$temp;
			} else if(preg_match("/=?\\s?[^E]{1,4}[,.]?\\s?A[,.]?\\sL\\.\\s([OQSZlU|I!0-9&]{1,3}[a-fA-F]?)[,.]?/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 7280, mats[".$i++."] = ".$mat."\n";
				$temp = $this->replaceMistakenNumbers(trim($mats[1]));
				if(is_numeric($temp)) {
					if($temp > 0) $otherCatalogNumbers = "N. A. L. ".$temp;
				} else $otherCatalogNumbers = "N. A. L. ".$temp;
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'otherCatalogNumbers' => ucfirst(trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => $taxonRank,
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "88",
			'exsnumber' => $exsnumber
		);
	}

	private function convertSlashedDates($string) {//echo "\nInput to convertSlashedDates: ".$string."\n";
		$tokens = explode("/", $string);
		$c = count($tokens);
		if($c > 0) {
			$year = $tokens[$c-1];
			if(strlen($year) == 2) $year = "19".$year;
			if($c == 3) return $year."-".str_pad($tokens[0], 2, "0", STR_PAD_LEFT)."-".str_pad($tokens[1], 2, "0", STR_PAD_LEFT);
			if($c == 2) return $year."-".str_pad($tokens[0], 2, "0", STR_PAD_LEFT);
			return $year;
		}
		return "";
	}

	private function doLichensAndMossesOfYellowstoneLabel($s) {
		//echo "\nDoing LichensAndMossesOfYellowstoneLabel\n";
		$pattern =
			array
			(
				"/bnaron tversman/i",
				"/\"¢/i"
			);
		$replacement =
			array
			(
				"Sharon Eversman",
				"&"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$recordNumber = '';
		$recordedBy = '';
		$recordedById = '';
		$otherCatalogNumbers = '';
		$identifiedBy = '';
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = $collectorInfo['collectorName'];
			if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
			if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
			if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
			if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
		}//echo "\nline 5925, collectorName: ".$collectorName.", collectorNum: ".$collectorNum."\n";
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = '';
		$scientificName = '';
		$country = "USA";
		$substrate = '';
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimEventDate = '';
		$verbatimAttributes = '';
		$associatedTaxa = '';
		$location = "";
		$habitat = '';
		$yPat = "/.*YE[1Il!|]{2}[O0Q]WST[O0Q]NE\\sNational\\s?Park(.*)/is";
		if(preg_match($yPat, $s, $mat)) $s = trim($mat[1]);
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null && count($identifier) > 0) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}
		$foundSciName = false;
		$lookingForLocation = false;
		$lines = explode("\n", $s);
		foreach($lines as $line) {
			if(!$foundSciName) {
				$line = trim($line);
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
					if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
					if(array_key_exists('substrate', $psn)) {
						$substrate = $psn['substrate'];
						if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
					}
					$foundSciName = true;
					continue;
				}

			}
			if(preg_match("/^(?:L[O0Q]cati[O0Q]n|Hab[1Il!|]tat)$/i", $line)) continue;
			if(preg_match("/\\sL[O0Q]cati[O0Q]n[:;]?(.{6,})/i", $line, $mats)) {
				$location = trim($mats[1]);
				continue;
			} else if(preg_match("/^L[O0Q]cati[O0Q]n[:;]?(.{6,})/i", $line, $mats)) {
				$location = trim($mats[1]);
				continue;
			}
			if(preg_match("/\\sHab[1Il!|]tat[:;]?(.{6,})/i", $line, $mats)) {
				$location = trim($mats[1]);
				continue;
			} else if(preg_match("/^Hab[1Il!|]tat[:;]?(.{6,})/i", $line, $mats)) {
				$habitat = trim($mats[1]);
				continue;
			}
			if(strlen($substrate) == 0) {
				if(preg_match("/^(?:(?:Found|Common|Loose)\\s)?on\\s.{3,}/i", $line)) {
					$substrate = $line;
					continue;
				}
			}
			if(strlen($location) == 0) {
				$location = $line;
				continue;
			} else {
				$lWordPat = "/\\b(?:HWY|highway|road|park|trail|entrance|national|river|island|forest|wilderness|mountains?|miles?|outlook|km|slopes?)\\b/i";
				if(preg_match($lWordPat, $line)) {
					$location = $line;
					continue;
				}
			}
			if(strlen($habitat) == 0) {
				$hWordPat = "/\\b(?:rocks?|logs?|trees?|bark|earth|soil|granit(?:e|ic)|limestone|exposed|wood(?:land)?|forest|cultivated?|outcrops?|sandy?|bank|alpine)\\b/i";
				if(preg_match($hWordPat, $line)) {
					$habitat = $line;
					continue;
				}
			}
			if(preg_match("/^(?:No.+:\\s)?([A-Z]{2,3}\\d{2,6})/", $line, $mats) && strlen($recordNumber) == 0) $recordNumber = trim($mats[1]);
			$dPat = "/.*((?:[0OQ]?+[!lI2ZS1-9]|[1!Il][OQ!lI2Z12])\/(?:[0OQ]?+[!lI2ZS1-9]|[1!Il2Z][OQ!lI2ZS0-9]|3[0OQ!|Il1])\/(?:[0OQ][!lI2ZS1-9]|[OQ!lI2ZS0-9]{2}+))/";
			if(preg_match($dPat, $line, $mats)) $verbatimEventDate = $this->convertSlashedDates($this->replaceMistakenNumbers($mats[1]));
		}//echo "\nline 6014, collectorName: ".$collectorName.", collectorNum: ".$collectorNum."\n";
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'infraspecificEpithet' => $infraspecificEpithet,
			'taxonRank' => $taxonRank,
			'verbatimAttributes' => $verbatimAttributes,
			'verbatimEventDate' => $verbatimEventDate,
			'associatedTaxa' => $associatedTaxa,
			'recordNumber' => $recordNumber,
			'recordedBy' => $recordedBy,
			'recordedById' => $recordedById,
			'$otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);
	}

	private function doMontanaStateUniversityHerbariumLabel($s) {
		//echo "\nDoing MontanaStateUniversityHerbariumLabe\n";
		$pattern =
		array
		(
			"/L\/PPONICA/i",
			"/Evers.nan/i",
			"/Tversman/i",
			"/Eversm.{1,2}/i",
			"/s\^-aron Eversman/",
			"/Sharon\\.Eversman/"
		);
		$replacement =
		array
		(
			"lapponica",
			"Eversman",
			"Eversman",
			"Eversman",
			"Sharon Eversman",
			"Sharon Eversman"
		);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\ns:\n".$s."\n";
		$recordNumber = '';
		$recordedBy = '';
		$recordedById = '';
		$otherCatalogNumbers = '';
		$identifiedBy = '';
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = $collectorInfo['collectorName'];
			if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
			if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
			if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
			if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
		}//echo "\nline 6079, s:\n".$s."\nrecordedBy:\n".$recordedBy."\nrecordNumber:\n".$recordNumber."\nidentifiedBy:\n".$identifiedBy."\n";
		$lfPat = "/.*M[O0Q]NTANA\\s?STATE\\s?UN[1Il!|]VERSITY\\s?HERBAR[1Il!|]UM?+(.*)/is";
		if(preg_match($lfPat, $s, $mats)) $s = trim($mats[1]);
		$state_province = "";
		$dateIdentified = '';
		$scientificName = '';
		$country = "USA";
		$county = "";
		$substrate = '';
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$associatedTaxa = '';
		$location = "";
		$habitat = '';
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null && count($identifier) > 0) {
			$identifiedBy = $identifier[0];
			$dateIdentified = $identifier[1];
		}
		if(strcasecmp($identifiedBy, "se") == 0) $identifiedBy = "Sharon Eversman";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
			$county = trim($countyMatches[2]);
			$s = trim($countyMatches[0]);
			$state_province = trim($countyMatches[5]);
		}
		$lookingForHabitat = true;
		$foundSciName = false;
		$lines = explode("\n", $s);
		$lineIndex = 0;
		foreach($lines as $line) {//echo "\nline 6596, line: ".$line."\n";
			$line = trim($line);
			if(strlen($line) < 6 || $this->isMostlyGarbage($line, 0.50)) {
				$lineIndex++;
				continue;
			}
			if(!$foundSciName) {
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
					if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
					if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
					if(stripos($line, $scientificName) !== FALSE) $line = trim(substr($line, stripos($line, $scientificName)+strlen($scientificName)));
					if(stripos($line, $infraspecificEpithet) !== FALSE) $line = trim(substr($line, stripos($line, $infraspecificEpithet)+strlen($infraspecificEpithet)));
					if(stripos($line, $associatedTaxa) !== FALSE) $line = trim(substr($line, stripos($line, $associatedTaxa)+strlen($associatedTaxa)));
					if(stripos($line, $substrate) !== FALSE) $line = trim(substr($line, stripos($line, $substrate)+strlen($substrate)));
					$foundSciName = true;
				}
			}
			if($lineIndex++ > 0 && strlen($line) > 6) {
				if(strlen($substrate) == 0) {
					if(preg_match("/^(?:(?:Found|Common|Loose)\\s)?on\\s.{3,}/i", $line)) {
						$substrate = $line;
						continue;
					}
				}
				if($lookingForHabitat) {
					$hWordPat = "/\\b(?:rocks?|logs?|trees?|bark|earth|soil|granit(?:e|ic)|limestone|exposed|wood(?:land)?|forest|cultivated?|outcrops?|sandy?|bank|alpine)\\b/i";
					if(preg_match($hWordPat, $line)) {
						$habitat = $line;
						$lookingForHabitat = false;
						continue;
					}
				}
				if(strlen($location) == 0) $location = $line;
				else {
					$lWordPat = "/\\b(?:HWY|highway|road|park|trail|entrance|national|river|island|forest|wilderness|mountains?|miles?|outlook|km)\\b/i";
					if(preg_match($lWordPat, $line)) {
						$location = $line;
						break;
					}
				}
			}
		}//echo "\nline 6160, s:\n".$s."\nrecordedBy:\n".$recordedBy."\nrecordNumber:\n".$recordNumber."\nidentifiedBy:\n".$identifiedBy."\n";
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'infraspecificEpithet' => $infraspecificEpithet,
			'taxonRank' => $taxonRank,
			'verbatimAttributes' => $verbatimAttributes,
			'associatedTaxa' => $associatedTaxa,
			'recordedBy' => $recordedBy,
			'recordedById' => $recordedById,
			'recordNumber' => $recordNumber,
			'otherCatalogNumbers' => $otherCatalogNumbers,
			'country' => $country,
			'county' => $county,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => str_ireplace
			(
				array("!", "1", "|", "0"),
				array("l", "l", "l", "o"),
				trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
			),
			'dateIdentified' => $dateIdentified
		);
	}

	private function doHerbariumOfMontanaStateUniversityLabel($s) {
		$pattern =
			array
			(
				"/^Col[!Il1|].\\s/i",
				"/Col[!Il1|].{1,2}\\s([A-Z])/",
				"/Eve.sma./",
				"/Eve.{2}man/",
				"/So\\sEversman/i",
				"/Cal.oplaca/",
				"/Ga[!Il1|]{2}a.in/"
			);
		$replacement =
			array
			(
				"Coll: ",
				"Coll: \${1}",
				"Eversman",
				"Eversman",
				"S. Eversman",
				"Caloplaca",
				"Gallatin"

			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$scientificName = "";
		$substrate = "";
		$habitat = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		if(preg_match("/.*M\\sOF\\sM[O0Q]NTANA\\s?STATE(.*)/is", $s, $mats)) $s = trim($mats[1]);
		if(preg_match("/.*B[O0Q].?[SZ]EMAN.?(?:,\\sM[O0Q]NTANA)?+(.*)/is", $s, $mats)) $s = trim($mats[1]);
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		$associatedTaxa = "";
		$recordNumber = "";
		$identifiedBy = "";
		$state_province = "";
		$recordedBy = "";
		$recordedById = "";
		$otherCatalogNumbers = "";
		$verbatimEventDate = "";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
				if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
				if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			}
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$county = "";
		$country = "";
		$location = "";
		$date_identified = array();
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		if(strlen($identifiedBy) == 0) {
			$identifier = $this->getIdentifier($s, $possibleMonths);
			if($identifier != null) {
				$identifiedBy = $identifier[0];
				$date_identified = $identifier[1];
			}
		}
		if(strcasecmp($identifiedBy, "se") == 0) $identifiedBy = "Sharon Eversman";
		$possibleNumbers = "[OQSZl|I!0-9]";
		$firstPart = "";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 6542, countyMatches[".$i++."] = ".$countyMatche."\n";
			$state_province = trim($countyMatches[5]);
			$country = trim($countyMatches[3]);
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			//$location = ltrim(rtrim($countyMatches[4], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$county = trim($countyMatches[2]);
			//echo "\nline 6561, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
		}
		$foundSciName = false;
		if(strlen($firstPart) > 0) $s = $firstPart;
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 6566, line: ".$line."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/(.*)N[o0Q]\\.?\\s((?:[A-Z]\\d-)?[SZl|I!1-9]".$possibleNumbers."{0,2}+)[.,]?+(.*)/i", $line, $mats)) {
				$recordNumber = $this->replaceMistakenNumbers(trim($mats[2]));
				$pos = stripos($s, $line);
				if($pos !== FALSE) $s = trim(substr($s, $pos+strlen($line)));
				$line = trim(trim($mats[1])." ".trim($mats[3]));
			}
			if(!$foundSciName && strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
					if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists ('recordNumber', $psn) && strlen($recordNumber) == 0) {
						$trn = $psn['recordNumber'];
						if(strlen($trn) > 0) $recordNumber = $trn;
					}
					if(array_key_exists ('substrate', $psn)) {
						$substrate = $psn['substrate'];
						if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
					}
					$sciPos = stripos($s, $line);
					if($sciPos !== FALSE) $s = substr($s, $sciPos+strlen($line));
					$foundSciName = true;
				}
			} else if(strlen($habitat) == 0 &&
				strlen($line) > 6 &&
				!$this->isMostlyGarbage($line, 0.60) &&
				!preg_match("/.?N[o0Q]\\.?\\s/", $line) &&
				!preg_match("/C[o0Q][!Il1|]{2}:\\s/", $line)) $habitat = $line;
		}$location = $s;
		if(strlen($location) > 0) {//echo "\nline 6598, location: ".$location."\n";
			$location = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			if(strlen($habitat) > 0) {
				$pos = stripos($location, $habitat);
				if($pos !== FALSE && $pos < 9) $location = trim(substr($location, $pos+strlen($habitat)));
			}
			if(strlen($elevation) > 0) {
				if(preg_match("/(.*)\\b".$elevation."(.*)/i", $location, $matches3)) $location = trim($matches3[1]);
			}
			if(preg_match("/(.*?)".$possibleNumbers."{1,2}+[- ]?(?:".$possibleMonths.")[- ]?".$possibleNumbers."{4}.*/i", $location, $matches3)) $location = trim($matches3[1]);
			if(preg_match("/(.*?)C[o0Q][!Il1|]{2}:\\s.*/i", $location, $matches3)) $location = trim($matches3[1]);
		}
		if(strlen($location) > 0 && strlen($substrate) == 0) {
			if(preg_match("/^(on\\s.*?)\\s(?:and\\s)?+((?:over|along|at)\\s.*)/i", $location, $mats)) {
				$substrate = trim($mats[1]);
				$location = trim($mats[2]);
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-\(\)"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedById' => $recordedById,
			'otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimCoordinates' => $verbatimCoordinates
		);
	}

	private function doKienerMemorialLabel($s) {
		$pattern =
			array
			(
				"/R.malina/i",
				"/Pertus.{1,2}ria/i",
				"/Wa[1!lI]ter\\sK[1!lI]ener/i"
			);
		$replacement =
			array
			(
				"Ramalina",
				"Pertusaria",
				"Walter Kiener"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$scientificName = "";
		$substrate = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		if(preg_match("/.*\\sK[1!lI]ENER\\sMEMOR[1!lI]A(?:L|[|!][-_])(.*)/is", $s, $mats)) $s = trim($mats[1]);
		if(preg_match("/.*CHEN\\sCO[1!lI]{2}ECT[1!lI]ON(.*)/is", $s, $mats)) $s = trim($mats[1]);
		if(preg_match("/(.*)NEB([^ ]{3,9})\\b(.*)/is", $s, $mats)) {
			$mats2 = $mats[2];
			if($this->containsNumber($mats2)) $s = trim($mats[1])." ".trim($mats[3]);
		}
		$habitat = '';
		$habitatArray = $this->getHabitat($s);
		if($habitatArray != null && count($habitatArray) > 0) {
			$habitat = $habitatArray[1]." ".$habitatArray[2];
			$patStr = "/(.*)[1!lI]oca[1!lI]{2}t/i";
			if(preg_match($patStr, $habitat, $mat)) $habitat = $mat[1];
		}
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		$associatedTaxa = "";
		$recordNumber = "";
		$identifiedBy = "";
		$state_province = "";
		$recordedBy = "";
		$recordedById = "";
		$otherCatalogNumbers = "";
		$verbatimEventDate = "";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
				if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
				if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			}
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$county = "";
		$country = "";
		$location = $this->getLocality($s);
		$date_identified = array();
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		if(strlen($identifiedBy) == 0) {
			$identifier = $this->getIdentifier($s, $possibleMonths);
			if($identifier != null) {
				$identifiedBy = $identifier[0];
				$date_identified = $identifier[1];
			}
		}
		$possibleNumbers = "[OQSZl|I!0-9]";
		$firstPart = "";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 6542, countyMatches[".$i++."] = ".$countyMatche."\n";
			$state_province = trim($countyMatches[5]);
			$country = trim($countyMatches[3]);
			$firstPart = trim($countyMatches[0]);
			$secondPart = trim($countyMatches[1]);
			//$location = ltrim(rtrim($countyMatches[4], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$county = trim($countyMatches[2]);
			//echo "\nline 6738, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
		}
		$foundSciName = false;
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 6743, line: ".$line."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/(.{0,3})Un[l1|I!]vers[l1|I!]ty\\sof\\sNebraska/i", $line, $mats)) continue;
			if(preg_match("/(.{0,3})N[o0Q]\\.?\\s([SZl|I!1-9]".$possibleNumbers."{0,3}+)[.,]?+\\sPrepared\\s/i", $line, $mats)) {
				$recordNumber = $this->replaceMistakenNumbers(trim($mats[2]));
				continue;
			}
			if(preg_match("/(?:(?:(?:[1!lI]{2}|U)CHEN\\s)?FLORA|(?:[1!lI]{2}|U)CHENS)\\sOF\\s(.*)/i", $line, $mats)) {
				$mats1 = trim($mats[1]);
				$sp = $this->getStateOrProvince($mats1);
				if(count($sp) > 0) {
					$state_province = $sp[0];
					$country = $sp[1];
					continue;
				}
			} else if(preg_match("/^(?:[1!lI]{2}|U)CHEN[S5]?$/i", $line, $mats)) continue;
			if(!$foundSciName && strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
					if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists ('recordNumber', $psn) && strlen($recordNumber) == 0) {
						$trn = $psn['recordNumber'];
						if(strlen($trn) > 0) $recordNumber = $trn;
					}
					if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
					$sciPos = stripos($s, $line);
					if($sciPos !== FALSE) $s = substr($s, $sciPos+strlen($line));
					$foundSciName = true;
				} else {
					$pos = strpos($line, " ");
					if($pos !== FALSE) {
						$temp = substr($line, 0, $pos);
						if(strlen($temp) > 6 && !$this->isMostlyGarbage($temp, 0.60)) {
							$psn = $this->processSciName($temp);
							if($psn != null) {
								if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('recordNumber', $psn) && strlen($recordNumber) == 0) {
									$trn = $psn['recordNumber'];
									if(strlen($trn) > 0) $recordNumber = $trn;
								}
								if(array_key_exists ('substrate', $psn))  $substrate = $psn['substrate'];
								$sciPos = stripos($s, $line);
								if($sciPos !== FALSE) $s = substr($s, $sciPos+strlen($line));
								$foundSciName = true;
							}
						}
					}
				}
			}
		}
		if(preg_match("/(?:[A-Za-z]+\\s)?by\\s(.*)/i", $recordedBy, $mats)) $recordedBy = trim($mats[1]);
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-\(\)"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedById' => $recordedById,
			'otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimCoordinates' => $verbatimCoordinates
		);
	}

	private function doLichenesGroenlandiciLabel($s) {
		$pattern =
			array
			(
				"/1453.\\s/",
				"/\\sHab\\.\\s/",
				"/,\\se\\.g.{1,4}[>\/]/",
				"/\\sliehens\\s/",
				"/\\sliehen\\s/"
			);
		$replacement =
			array
			(
				"453. ",
				"\nHab. ",
				", e.g.,",
				" lichens ",
				" lichen "
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 7697, s:\n".$s."\n";
		if(preg_match( "/.*[1Il!|][CE]H[CE]N[BES5]{2}\\s?[CG]R[O0QD][CE]NLAND[1Il!|]C[1Il!|]\\s?[CE]XS[1Il!|][CE]{2}AT[1Il!|](.+)/is", $s, $mats)) $s = trim($mats[1]);
		if(preg_match( "/.*Mus[ce]um\\s?B[O0Q]tan[1Il!|][ce]um\\s?Haun[1Il!|][ce]ns[ce]?(.+)/is", $s, $mats)) $s = trim($mats[1]);
		$exsnumber = "";
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$taxonRank = "";
		$infraspecificEpithet = "";
		$scientificName = "";
		$substrate = "";
		$recordedBy = "";
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		$verbatimEventDate = "";
		$foundSciName = false;
		$lookingForLocation = false;
		$associatedTaxa = "";
		$date_identified = array();
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$location = "";
		$habitat = "";
		$identified_by = '';
		$recordNumber = "";
		$recordedBy = "";
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 6112, line: ".$line."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if(strlen($line) > 6 &&
					!$this->isMostlyGarbage($line, 0.60) &&
					preg_match("/^[^1]{0,3}?([SZlU|I!1-9&]|[\]\[OQSZlU|I!0-9&]{2,4}[a-fA-F]?)[.,_]?+\\s(.*)/", $line, $mats)) {
					$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
					$psn = $this->processSciName(trim($mats[2]));
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
						if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
						$foundSciName = true;
						continue;
					}
				}
			}
			if($foundSciName) {
				if(preg_match("/^.ab.?\\s(.+)/i", $line, $mats)) {
					$habitat = trim($mats[1]);
					if(preg_match("/^(on\\s.+)\\stogether\\swith(?:\\s?,\\se\\.?g\\.?,?)?\\s(.+)/i", $habitat, $mats2)) {
						$substrate = trim($mats2[1]);
						if(strlen($associatedTaxa) == 0) $associatedTaxa = trim($mats2[2]);
						else {
							$temp = trim($mats2[2]);
							if(!strcasecmp($associatedTaxa, $temp) != 0) {
								$associatedTaxa .= " ".$temp;
								$associatedTaxa = trim($associatedTaxa);
							}
						}
					} else if(preg_match("/(.*)\\stogether\\swith(?:\\s?,\\se\\.?g\\.?,?)?\\s(.+)/i", $habitat, $mats2)) {
						if(strlen($associatedTaxa) == 0) $associatedTaxa = trim($mats2[2]);
						else {
							$temp = trim($mats2[2]);
							if(!strcasecmp($associatedTaxa, $temp) != 0) {
								$associatedTaxa .= " ".$temp;
								$associatedTaxa = trim($associatedTaxa);
							}
						}
					}
					continue;
				} else if(preg_match("/Hab.\\s(.+)/i", $line, $mats)) {
					$habitat = trim($mats[1]);
					if(preg_match("/^(on\\s.+)\\stogether\\swith(?:\\s?,\\se\\.?g\\.?,?)?\\s(.+)/i", $habitat, $mats2)) {
						$substrate = trim($mats2[1]);
						if(strlen($associatedTaxa) == 0) $associatedTaxa = trim($mats2[2]);
						else {
							$temp = trim($mats2[2]);
							if(!strcasecmp($associatedTaxa, $temp) != 0) {
								$associatedTaxa .= " ".$temp;
								$associatedTaxa = trim($associatedTaxa);
							}
						}
					} else if(preg_match("/(.*)\\stogether\\swith(?:\\s?,\\se\\.?g\\.?,?)?\\s(.+)/i", $habitat, $mats2)) {
						if(strlen($associatedTaxa) == 0) $associatedTaxa = trim($mats2[2]);
						else {
							$temp = trim($mats2[2]);
							if(!strcasecmp($associatedTaxa, $temp) != 0) {
								$associatedTaxa .= " ".$temp;
								$associatedTaxa = trim($associatedTaxa);
							}
						}
					}
					continue;
				} else if(preg_match("/.*Leg\\.?\\s?&\\s?det\\.?:?(.*)/", $line, $mats)) {
					$recordedBy = trim($mats[1]);
					$identified_by = $recordedBy;
					break;
				} else if(stripos($line, "Greenland") !== FALSE) {
					$location = $line;
					if(preg_match("/(.*)\([S5-8][OQSZlU|I!0-9&]°.*/", $location, $mats)) $location = trim($mats[1]);
					if(strlen($elevation) > 0 && preg_match("/(.*)".$elevation.".*/", $location, $mats)) $location = trim($mats[1]);
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'country' => "Greenland",
			'substrate' => $substrate,
			'habitat' => $habitat,
			'locality' => $location,
			'taxonRank' => $taxonRank,
			'verbatimCoordinates' => $verbatimCoordinates,
			'verbatimAttributes' => $verbatimAttributes,
			'verbatimEventDate' => $verbatimEventDate,
			'verbatimElevation' => $elevation,
			'dateIdentified' => $date_identified,
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => $taxonRank,
			'associatedTaxa' => $associatedTaxa,
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "279",
			'exsnumber' => $exsnumber
		);
	}

	public function doGenericLabel($str) {
		$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$possibleNumbers = "[OQSZl|I!0-9]";
		$state_province = '';
		$possible_collector_num = '';
		$scientificName = $this->getScientificName($str);
		//if($scientificName != null) echo "\nScientific Name: ".$scientificName."\n";
		$associated_taxa = $this->getAssociatedTaxa($str);
		$county = '';
		$recordedBy = '';
		$recordNumber = '';
		$taxonRank = '';
		$infraspecificEpithet = '';
		$country = '';
		$verbatimAttributes = '';
		$georeferenceRemarks = '';
		$habitat = '';
		$location = "";
		$temp = $this->getLocality($str);
		if(strlen($temp) > 0 && !$this->isMostlyGarbage($temp, 0.48)) $location = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $temp);
		$habitatArray = $this->getHabitat($str);
		$temp = $habitatArray[1];
		if(strlen($temp) > 0 && !$this->isMostlyGarbage($temp, 0.48)) $habitat = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $temp);
		$substrate = $this->getSubstrate($str);
		$elevation = '';
		$event_date = array();
		$date_identified = array();
		$identified_by = '';
		$identifier = $this->getIdentifier($str, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$matches = $this->getTaxonOfHeaderInfo($str);
		$states = array();
		if($matches != null) {
			$info = $this->processTaxonOfHeaderInfo($matches);
			if(array_key_exists('locality', $info)) $location = $info['locality'];
			if(array_key_exists('county', $info)) $county = $info['county'];
			if(array_key_exists('country', $info)) $country = $info['country'];
			if(array_key_exists('stateProvince', $info)) $state_province = $info['stateProvince'];
			if(array_key_exists('substrate', $info) && strlen($substrate) == 0) $substrate = $info['substrate'];
			if(array_key_exists('scientificName', $info) && strlen($scientificName) == 0) $scientificName = $info['scientificName'];
			if(array_key_exists('states', $info)) $states = $info['states'];
			if(array_key_exists('verbatimAttributes', $info)) $verbatimAttributes = $info['verbatimAttributes'];
			if(array_key_exists('recordNumber', $info)) $recordNumber = $info['recordNumber'];
			if(array_key_exists('taxonRank', $info)) $taxonRank = $info['taxonRank'];
			if(array_key_exists('infraspecificEpithet', $info)) $infraspecificEpithet = $info['infraspecificEpithet'];
			if(array_key_exists('associatedTaxa', $info)) {
				if(strlen($associated_taxa) == 0) $associated_taxa = $info['associatedTaxa'];
				else $associated_taxa .= ", ".$info['associatedTaxa'];
			}
		} else {
			$foundSciName = false;
			$countyMatches = $this->findCounty($str);
			if($countyMatches != null) {
				$firstPart = trim($countyMatches[0]);
				$secondPart = trim($countyMatches[1]);
				if(strlen($location) < 3) $location = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", ltrim($countyMatches[4], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
				$county = trim($countyMatches[2]);
				$country = trim($countyMatches[3]);
				$m5 = trim($countyMatches[5]);
				//echo "\nline 6978, firstPart: ".$firstPart."\nm5: ".$m5."\nlocation: ".$location."\n";
				$sp = $this->getStateOrProvince($m5);
				if(count($sp) > 0) {
					$state_province = $sp[0];
					$country = $sp[1];
					$m5Pos = stripos($location, $m5);
					if($m5Pos !== FALSE && $m5Pos == 0) $location = trim(substr($location, strlen($m5)));
				}
				if(strlen($county) > 0 && (strlen($state_province) == 0 || strlen($country) == 0)) {
					$polInfo = $this->getPolticalInfoFromCounty($county);
					if($polInfo != null ) {
					$county = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5'. '0'),
									array('l', 'l', 'l', 'S', 'O'),
									trim($polInfo['county'])
								)
							)
						);
						if(array_key_exists('state', $polInfo)) $state_province = $polInfo['state'];
						if(array_key_exists('country', $polInfo)) $country = $polInfo['country'];
					}
				}
				$lines = explode("\n", $secondPart);
				foreach($lines as $line) {//echo "\nline 6047, line: ".$line."\n";
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
						$foundSciName = true;
						break;
					}
				}
				if(!$foundSciName) {
					$lines = array_reverse(explode("\n", $firstPart));
					foreach($lines as $line) {//echo "\nline 6066, line: ".$line."\n";
						$psn = $this->processSciName($line);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
							if(array_key_exists ('substrate', $psn)) {
								$substrate = $psn['substrate'];
								if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
							}
							$foundSciName = true;
							break;
						}
					}
				}
				if(strlen($location) > 0) {//echo "\nline 6092, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
					$elevArr = $this->getElevation($location);
					$temp = $elevArr[0];
					if(strlen($temp) > 0) {
						$elevation = $elevArr[1];
						$ePos = stripos($location, $elevation);
						if($ePos !== FALSE) $habitat = substr($location, $ePos+strlen($elevation));
						$location = $temp;
					} else {
						$termLocPat = "/(.*?)\\b(?:(?:(?:".$possibleMonths.")[ -](?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
							$possibleNumbers."|3[1Iil!|OQ0\]]))|(?:(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
							$possibleNumbers."|3[1Iil!|OQ0\]])[ -](?:".$possibleMonths."))|\\d{1,3}(?:\\.\\d{1,6})?\\s?°|".
							"(?:T(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?[NS]\\sR(?:\\.|ange)?\\s?".
							$possibleNumbers."{1,3}\\s[EW](?:sec(?:\\.|tion)\\s?".$possibleNumbers."{1,3}))|(?:sec(?:\\.|tion)\\s".
							$possibleNumbers."{1,3})\\s(?:T(?:\\.|wnshp.?|ownship)?\\s?(?:".
							$possibleNumbers."{1,3})\\s?[NS]\\sR(?:\\.|ange)?\\s?".$possibleNumbers."{1,3}\\s[EW]))\\b(.*)/is";
						if(preg_match($termLocPat, $location, $lMats)) {//$i=0;foreach($lMats as $lMat) echo "\nlMats[".$i++."] = ".$lMat."\n";
							$location = trim($lMats[1]);
							$temp = trim($lMats[2]);
							if(strlen($habitat) == 0 && !$this->isMostlyGarbage($temp, 0.50)) {
								//$habitat = $temp;
								if(preg_match("/(?:[1!|I][89]|[2Z][0O])".$possibleNumbers."{2}(.*)/i", $temp, $mats)) $temp = trim($mats[1]);
								if(preg_match("/(.*)C[o0][lI!|](?:\\.|:|[lI!|]s?[:.]?|[lI!|]ectors?|lected)/i", $temp, $mats)) $temp = trim($mats[1]);
								$pat2 = "/\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
									"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
									"(?:".$possibleNumbers."{1,3}\\s?\"?)?)?\\s?[EW](.*)/";
								if(preg_match($pat2, $temp, $hMats2)) $temp = trim($hMats2[1]);
								if(strlen($temp) > 3 && !$this->isMostlyGarbage($temp, 0.50)) $habitat = $temp;
							}
						}
					}
					if(!$foundSciName) {
						$lines = explode("\n", $location);
						foreach($lines as $line) {//echo "\nline 6117, line: ".$line."\n";
							$psn = $this->processSciName($line);
							if($psn != null) {
								if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
								if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
								if(array_key_exists('substrate', $psn)) {
									$substrate = $psn['substrate'];
									if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
								}
								$foundSciName = true;
								$pos = stripos($location, $line);
								if($pos !== FALSE && $pos == 0) $location = trim(substr($location, strlen($line)));
								break;
							}
						}
					}
				}
			}//echo "\nline 6146, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\n";
			$lines = explode("\n", $str);
			$lookingForHabitat = false;
			$lookingForLocation = false;
			foreach($lines as $line) {//echo "\nline 6142, line: ".$line."\n";
				if(!$foundSciName) {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
						if(array_key_exists('substrate', $psn)) {
							$substrate = $psn['substrate'];
							if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						}
						$foundSciName = true;
						if($lookingForLocation) break;
						else continue;
					}
				}
				if(strlen($recordNumber) == 0) {
					$noPos = stripos($line, "No. ");
					if($noPos !== FALSE && $noPos == 0) {
						$temp = trim(substr($line, $noPos+4));
						$spacePos = strpos($temp, " ");
						if($spacePos !== FALSE) {
							$temp = trim(substr($temp, 0, $spacePos));
							if($this->containsNumber($temp)) $recordNumber = $temp;
						} else if($this->containsNumber($temp)) $recordNumber = $temp;
					}
				}
				if(strlen($habitat) == 0) {
					$onPos = stripos($line, "on ");
					if($onPos !== FALSE && $onPos == 0) {
						$temp = $line;
						$inappropriateWords = array("road", "north", "south", "east", "west", "highway", "hiway", "area", "state",
							"valley", "slope", "route", "Rd.", "Co.", "County");
						foreach($inappropriateWords as $inappropriateWord) {
							if(stripos($temp, $inappropriateWord) !== FALSE) {
								if(strlen($location) == 0) {
									$location = $line;
									$lookingForLocation = true;
								}
								$temp = "";
								break;
							}
						}
						if(strlen($temp) > 0) {
							if(preg_match("/(.*)\\b(?:UNIVERS|COLL(?:\\.|ect)|HERBAR|DET(?:\\.|ermin)|Identif|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden|Date|".$possibleMonths.")/i", $temp, $mats)) {
								$temp = trim($mats[1]);
							}
							if(strlen($habitat) > 5) {
								$habitat = $temp;
								continue;
							}
						}
					}
				}
				if($lookingForLocation) {
					if(preg_match("/(.*)\\b(?:UNIVERS|COLL(?:\\.|ect)|HERBAR|DET(?:\\.|ermin)|Identif|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden|Date|".$possibleMonths.")/i", $line, $mats)) {
					$temp = trim($mats[1]);
						if(strlen($temp) > 6) {
							if($lookingForLocation && stripos($location, $temp) === FALSE) $location .= " ".$temp;
						}
							} else if($lookingForLocation && stripos($location, $line) === FALSE) $location .= " ".$line;
							if($foundSciName) break;
						}
					}
				}//echo "\nline 6215, location: ".$location."\nscientificName: ".$scientificName."\n";
				if(strlen($scientificName) == 0) {
					$lines = explode("\n", $location);
					foreach($lines as $line) {//echo "\nline 6086, line: ".$line."\n";
						$psn = $this->processSciName($line);
						if($psn != null) {
							if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
							if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
							if(array_key_exists('substrate', $psn)) {
						$substrate = $psn['substrate'];
						if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
							}
							$pos = stripos($location, $line);
							if($pos !== FALSE && $pos == 0) $location = trim(substr($location, strlen($line)));
							break;
						}
					}
				}//echo "\nline 6195, location: ".$location."\nscientificName: ".$scientificName."\n";
				$location = str_replace
				(
					array("\r\n", "\n", "\r"),
					array(" ", " ", " "),
					trim($location)
				);
				$locationPatStr = "/(.*?)(\\stype\\s)?\\b(?:L|(?:|_))[o0]ca(?:[1!l]ity|ti[o0]n|\\.)?[:;,)]?\\s(.+)/is";
				if(preg_match($locationPatStr, $location, $locationMatches)) {
					if(count($locationMatches) == 4 && strlen(trim($locationMatches[2])) == 0) $location = trim($locationMatches[3]);
				} else if(preg_match($locationPatStr, $str, $locationMatches)) {
					if(count($locationMatches) == 4 && strlen(trim($locationMatches[2])) == 0) $location = trim($locationMatches[3]);
				}
				$lPat = "/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|Date|".
					"C[o0][lI!|](?:\\.|:|[lI!|]s?[:.]?|[lI!|]ectors?|lected|[lI!|]?s[:.])|".
					"(?:H[o0]st\\s)?+Det(?:\\.|ermined by)|(?:THE )?NEW \\w{4} B[OD]TAN.+|(?:(?:Assoc(?:[,.]|".
					"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|spp[,.]?|with)[:;]?)|(?:along|together)\\swith)|(?:Substrat(?:um|e))|".
					"(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0\]][ -])(?:".
					$possibleMonths.")\\b|\\d{1,3}(?:\\.\\d{1,6})?\\s?°)/i";

				$hPat = "/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|[lI!|]ocality|[lI!|]ocation|".
					"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|l?s[:.]?)|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|".
					"[lI!|]at[li!|]tude|(?:THE )?NEW\\s\\w{4}\\sBOTAN.+|(?:(?:Assoc(?:[,.]|".
					"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|spp[,.]?|with)[:;]?)|(?:along|together)\\swith))/i";
				if(strlen($location) > 0) {//echo "\nline 7164, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
					$habitatArr = $this->getHabitat($location);
					$temp = $habitatArr[0];
					if(strlen($temp) > 0) {
						$location = trim($temp);
						if(strlen($habitat) == 0) $habitat = trim($habitatArr[1]);
						$nextLine = trim($habitatArr[2]);
						$elevArr = $this->getElevation($nextLine);
						$temp = $elevArr[0];
						if(strlen($temp) > 0) {
							//$habitat = $temp;
							$elevation = $elevArr[1];
						} else {
							$elevArr = $this->getElevation($location);
							$temp = $elevArr[0];
							if(strlen($temp) > 0) {
						$location = $temp;
						$elevation = $elevArr[1];
							}
						}
						$pat = "/(.*)\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
							"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
							"(?:".$possibleNumbers."{1,3}\\s?\")?)?\\s?[NS],?\\s{1,2}(?:Long(?:\\.|itude)?[:;]?\\s)?".
							$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
							"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
							"(?:".$possibleNumbers."{1,3}\\s?\")?)?\\s?[EW](.*)/";
						if(preg_match($pat, $location, $hMats)) {
							$location = trim($hMats[1]);
							if(strlen($habitat) == 0) $habitat = trim($hMats[2]);
							else $habitat .= " ".trim($hMats[2]);
						} else {//there may be an incomplete lat/long which has a good beginning and end
							$pat = "/(.*?)\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°(.*+)/";
							if(preg_match($pat, $location, $hMats)) {
						$location = trim(ltrim($hMats[1], "."));
						$temp = trim($hMats[2]);
						$pat2 = "/\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
							"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
							"(?:".$possibleNumbers."{1,3}\\s?\")?)?\\s?[EW](.*)/";
						if(preg_match($pat2, $temp, $hMats2)) {
							if(strlen($habitat) == 0) $habitat = trim($hMats2[1]);
							else $habitat .= " ".trim($hMats2[1]);
						}
					}
				}

				if($this->isCompleteGarbage($location)) $location = "";
				else $location = $this->terminateField($location, $lPat);
				if($this->isCompleteGarbage($habitat)) $habitat = "";
				else $habitat = $this->terminateField($habitat." ".$nextLine, $hPat);
			} else {
				$elevArr = $this->getElevation($location);
				$temp = $elevArr[0];
					if(strlen($temp) > 0) {
					$location = str_replace
					(
						array("\r\n", "\n", "\r"),
						array(" ", " ", " "),
						trim($temp)
					);
					$elevation = $elevArr[1];
					$temp = $elevArr[2];
					if(strlen($temp) > 0) {
						$habitat = str_replace
						(
							array("\r\n", "\n", "\r"),
							array(" ", " ", " "),
							trim($temp)
						);
					}
				}
				$pat = "/(.*)\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
					"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
					"(?:".$possibleNumbers."{1,3}\\s?)?)?\\s?[NS],?\\s{1,2}(?:Long(?:\\.|itude)?[:;]?\\s)?".
					$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
					"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
					"(?:".$possibleNumbers."{1,3}\\s?)?)?\\s?[EW](.*)/";
				if(preg_match($pat, $location, $hMats)) {//$i=0;foreach($hMats as $hMat) echo "\nline 6224, hMats[".$i++."] = ".$hMat."\n";
					$location = trim($hMats[1]);
					if(strlen($habitat) == 0) $habitat = trim($hMats[2]);
					else $habitat .= " ".trim($hMats[2]);
				} else {//there may be an incomplete lat/long which has a good beginning and end
					$pat = "/(.*)\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°(.*+)/";
					if(preg_match($pat, $location, $hMats)) {
						$location = trim(ltrim($hMats[1], "."));
						$temp = trim($hMats[2]);
						$pat2 = "/\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
							"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
							"(?:".$possibleNumbers."{1,3}\\s?\")?)?\\s?[EW](.*)/";
						if(preg_match($pat2, $temp, $hMats2)) {
							if(strlen($habitat) == 0) $habitat = trim($hMats2[1]);
							else $habitat .= " ".trim($hMats2[1]);
						} else $habitat .= " ".trim($temp);
					}
				}//echo "\nline 6270, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
				if($this->isCompleteGarbage($location)) $location = "";
				else $location = $this->terminateField($location, $lPat);
				if($this->isCompleteGarbage($habitat)) $habitat = "";
				else {
					$pat = "/(.*?)\\b(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
					$possibleNumbers."|3[1Iil!|OQ0\]])[ -](?:".$possibleMonths.")\\b.*/i";
						if(preg_match($pat, $habitat, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6277, mats[".$i++."] = ".$mat."\n";
						$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					}
					if(preg_match("/((?s).*?)(?:".$possibleMonths.")\\s(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".
						$possibleNumbers."|3[1Iil!|OQ0\]])[,.]?\\s(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}/i", $habitat, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6280, mats[".$i++."] = ".$mat."\n";
						$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					}
					if(preg_match("/((?s).*?)(?:".$possibleMonths.")(?:[,.]\\s|[ -])(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}/i", $habitat, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 6282, mats[".$i++."] = ".$mat."\n";
						$habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					}
					if(preg_match("/((?s).*?)(?:Sec?(:\\.|tion)|(?:C[OQ0][1Iil!|]{1,2}(?:\\.|ection|ected))|".
						"(?:Latitude[ ,:;])|(?:N\wW YORK)|(?:B[O0]TAN[1!|I]CAL))/i", $habitat, $mats)) $habitat = trim(ltrim($mats[1], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					$trsPatStr = "/(?(?=\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)".
						"R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".
						$possibleNumbers."{1,3})\\b)".
					//if the condition is true then the form is TRS
						"\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".
						$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})\\b(.+)|".
					//else the form is STR
						"\\b[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3}),?(?:\\s|\\n|\\r\\n)T(?:\\.|wnshp.?|ownship)?\\s?(?:".
						$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?\\b(.+))/is";
					if(preg_match($trsPatStr, $habitat, $trsMatches)) $habitat = trim($trsMatches[1]);
					$pat2 = "/\\b".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?°\\s?".
						"(?:".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,6})?\\s?'\\s?".
						"(?:".$possibleNumbers."{1,3}\\s?\"?)?)?\\s?[EW](.*)/";
					if(preg_match($pat2, $habitat, $hMats2)) $habitat = trim($hMats2[1]);
					if(preg_match($hPat, $habitat, $hMats2)) $habitat = trim($hMats2[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					if(is_numeric($habitat)) $habitat = "";
				}
			}
		}//echo "\nline 6305, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
		if(strlen($elevation) == 0) {
			$elevArr = $this->getElevation($str);
			$temp = $elevArr[1];
			if(strlen($temp) > 0) $elevation = $temp;
			//echo "\nline 1718, Elevation: ".$elevation."\n";
		}
		if(preg_match("/\\bQu[ao][ad]\\.?(?:\\sM[ao]p)?[.;:]?\\s(.*)/i", $str, $quadMatches)) {
			$georeferenceRemarks = "Quad. Map: ".trim($quadMatches[1]);
			$mLength = strlen($georeferenceRemarks);
			if($mLength > 12) {
				$commaPos = strpos($georeferenceRemarks, ",");
				if($commaPos !== FALSE && $commaPos<$mLength-1) {
					$rest = trim(substr($georeferenceRemarks, $commaPos+1));
					$rLength = strlen($rest);
					$georeferenceRemarks = trim(substr($georeferenceRemarks, 0, $commaPos));
					$eMuns = explode(" ", $georeferenceRemarks);
					$temp = "";
					foreach($eMuns as $eMun) {
						$eMun = trim($eMun);
						if($this->isText($eMun) && strcasecmp($eMun, "elev.") != 0 && strcasecmp($eMun, "Coll.") != 0) $temp .= " ".$eMun;
						else break;
					}
					if(strlen($temp) > 0) {
						$rest = substr($georeferenceRemarks, strpos($georeferenceRemarks, $temp));
						$georeferenceRemarks =
							str_replace
							(
								array("0", "1", "!", "|", "5", "2"),
								array("O", "l", "l", "l", "S", "Z"),
								trim($temp)
							);
						$datePos = stripos($rest, " date ");
						if($datePos !== FALSE && $datePos < $rLength-6) {
							$pDate = trim(substr($rest, $datePos+6));
							$rest = trim(substr($rest, 0, $datePos));
							$collPat = "/(.*)Co0[l1]{1,2}/i";
							$c = preg_match($collPat, $rest, $colMatches);
							if($c) $rest = trim($colMatches[1]);
							$datePatternStr = "/(?:(?(?=\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ]?(?:\\d{4})))".
								"\\b(\\d{1,2})[-\\s]?((?i)".$possibleMonths.")[- ]?(\\d{4})|".
								"\\b((?i)".$possibleMonths.")\\b,?\\s?(\\d{4}))/s";
							if(preg_match($datePatternStr, $pDate, $dateMatches)) {
								$day = $dateMatches[1];
								if(strlen($day) > 0) {
									$event_date['day'] = $day;
									$event_date['month'] = $dateMatches[2];
									$event_date['year'] = $dateMatches[3];
								} else {
									$event_date['month'] = $dateMatches[4];
									$event_date['year'] = $dateMatches[5];
								}
							}
						}
						if(strlen($state_province) == 0) {
							$sp = $this->getStateOrProvince($rest);
							if(count($sp) > 0) {
								$state_province = $sp[0];
								$country = $sp[1];
							} else if(strlen($country) > 0) {
								$states = $this->getStatesFromCountry($country);
								foreach($states as $state) if(strcasecmp($state, $rest) == 0) $state_province = $rest;
							}
						}
						$paren1Pos = strpos($georeferenceRemarks, "(");
						if($paren1Pos !== FALSE) {
							$rest = trim(substr($georeferenceRemarks, $paren1Pos+1));
							$paren2Pos = strpos($rest, ")");
							if($paren2Pos !== FALSE) {
								$georeferenceRemarks =
									str_replace
									(
										array("0", "1", "!", "|", "5", "2"),
										array("O", "l", "l", "l", "S", "Z"),
										trim(substr($georeferenceRemarks, 0, $paren1Pos))
									);
							}
						}
					} else $georeferenceRemarks = "";
				} else {
					$paren1Pos = strpos($georeferenceRemarks, "(");
					if($paren1Pos !== FALSE) {
						$rest = trim(substr($georeferenceRemarks, $paren1Pos+1));
						$paren2Pos = strpos($rest, ")");
						if($paren2Pos !== FALSE) {
							$rest = trim(substr($georeferenceRemarks, $paren2Pos+1));
							$rLength = strlen($rest);
							$georeferenceRemarks =
								str_replace
								(
									array("0", "1", "!", "|", "5", "2"),
									array("O", "l", "l", "l", "S", "Z"),
									trim(substr($georeferenceRemarks, 0, $paren1Pos))
								);
							$datePos = stripos($rest, " date ");
							if($datePos !== FALSE && $datePos < $rLength-6) {
								$pDate = trim(substr($rest, $datePos+6));
								$rest = trim(substr($rest, 0, $datePos));
								$collPat = "/(.*)Co0[l1]{1,2}/i";
								if(preg_match($collPat, $rest, $colMatches)) $rest = trim($colMatches[1]);
								$datePatternStr = "/(?:(?(?=\\b(?:\\d{1,2})[-\\s]?(?:(?i)".$possibleMonths.")\\b[- ]?(?:\\d{4})))".
									"\\b(\\d{1,2})[- ]?((?i)".$possibleMonths.")\\b[- ]?(\\d{4})|".
									"\\b((?i)".$possibleMonths.")\\b,?\\s?(\\d{4}))/s";
								if(preg_match($datePatternStr, $pDate, $dateMatches)) {
									$day = $dateMatches[1];
									if(strlen($day) > 0) {
										$event_date['day'] = $day;
										$event_date['month'] = $dateMatches[2];
										$event_date['year'] = $dateMatches[3];
									} else {
										$event_date['month'] = $dateMatches[5];
										$event_date['year'] = $dateMatches[6];
									}
								}
							}
							if(strlen($state_province) == 0) {
								$sp = $this->getStateOrProvince($rest);
								if(count($sp) > 0) {
									$state_province = $sp[0];
									$country = $sp[1];
								} else if(strlen($country) > 0) {
									$states = $this->getStatesFromCountry($country);
									foreach($states as $state) if(strcasecmp($state, $rest) == 0) $state_province = $rest;
								}
							}
						}
					} else {
						$eMuns = explode(" ", $georeferenceRemarks);
						$temp = "";
						foreach($eMuns as $eMun) {
							$eMun = trim($eMun);
							if($this->isText($eMun) && strcasecmp($eMun, "elev.") != 0 && strcasecmp($eMun, "Coll.") != 0)  $temp .= " ".$eMun;
							else break;
						}
						if(strlen($temp) > 0) {
							$georeferenceRemarks =
							str_replace
								(
									array("0", "1", "!", "|", "5", "2"),
									array("O", "l", "l", "l", "S", "Z"),
									trim($temp)
								);
						}
					}
				}
			}
		}//echo "\nline 6395, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
		$datePat = "/(?:(?(?=(?:.+)\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ](?:\\d{4})))".
			"(.+)\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ](?:\\d{4})|".
			"(?=(?:.+)\\b(?:(?i)".$possibleMonths.")\\s(?:\\d{1,2}),?\\s(?:\\d{4}))".
			"(.+)\\b(?:(?i)".$possibleMonths.")\\s(?:\\d{1,2}),?\\s(?:\\d{4})|".
			"(.+)\\b(?:(?i)".$possibleMonths."),?\\s(?:\\d{4}))/s";
		if(preg_match($datePat, $location, $dateMatches)) {
			$countMatches = count($dateMatches);
			$location = trim($dateMatches[1]);
			if(strlen($location) == 0 && $countMatches > 2) {
				$location = trim($dateMatches[2]);
				if(strlen($location) == 0 && $countMatches > 3) $location = trim($dateMatches[3]);
			}
		}//echo "\nline 6408, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
		$locPatStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°\\s?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)?\\s?\'".
			"\\s?(?:\\d{1,3}(?:\\.\\d{1,3})?)?)?\\s?[NS]\\b,?(?:\\s|\\n|\\r\\n)(?:Long(?:\\.|(?:itude)?:?)?\\s?)?".
			"(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?°".
			"\\s?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)?\\s?\'\\s?(?:\\d{1,3}(?:\\.\\d{1,3})?)?)?\\s?[EW]\\b(?:[:;,.]?)(.*)/is";

		if(preg_match($locPatStr, $location, $locMatches)) {
			$location = trim($locMatches[1]);
			if(strlen($habitat) == 0) $habitat = $this->terminateField(trim($locMatches[2]), $hPat);
			//echo "\nline 1904, Locality: ".$location.", Habitat: ".$habitat.", Habitat2: ".$this->terminateField(trim($locMatches[2]), $hPat)."\n";
		} else {
			$possibleNumbers = "[OQSZl|I!\d]";
			$trsPatStr = "/(?(?=(?:.+)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)".
				"R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".
				$possibleNumbers."{1,3})\\b)".
			//if the condition is true then the form is TRS
				"(.+)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".
				$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})\\b(.+)|".
			//else the form is STR
				"(.+)\\b[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3}),?(?:\\s|\\n|\\r\\n)T(?:\\.|wnshp.?|ownship)?\\s?(?:".
				$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?\\b(.+))/is";
			if(preg_match($trsPatStr, $location, $locMatches)) {
				$location = trim($locMatches[1]);
				if(strlen($location) > 0) {
					if(strlen($habitat) == 0) $habitat = $this->terminateField(trim(ltrim($locMatches[2], ".")), $hPat);
				} else {
					$location = trim($locMatches[3]);
					if(strlen($habitat) == 0) $habitat = $this->terminateField(trim(ltrim($locMatches[4], ".")), $hPat);
				}
			} else {
				$utmPatStr = "/(.*)\\b(?:UTM:?(?:\\s|\\n|\\r\\n)(?:Zone\\s)?(?:".$possibleNumbers."{1,2})(?:\\w?(?:\\s|\\n|\\r\\n))(?:".
					$possibleNumbers."{1,8}E?(?:\\s|\\n|\\r\\n)".$possibleNumbers."{1,8}N?))\\b(.*)/is";
				if(preg_match($utmPatStr, $location, $locMatches)) {
					$location = trim($locMatches[1]);
					if(strlen($habitat) == 0) $habitat = $this->terminateField(trim(ltrim($locMatches[2], ".")), $hPat);
				}
			}
		}
		if(strlen($scientificName) > 0 && strlen($location) > 0) {
			$pos = strpos($location, $scientificName);
			if($pos !== FALSE && $pos == 0) $location = trim(substr($location, strlen($scientificName)));
		}
		if(strlen($substrate) > 0) {
			if(strlen($location) > 0) {
				$pos = strpos($location, $substrate);
				if($pos !== FALSE) {
					if(strcasecmp($location, $substrate) == 0) {
						$dotPos = strpos($substrate, ", ");
						if($dotPos !== FALSE) {
							$location = trim(substr($substrate, $dotPos+2));
							$substrate = trim(substr($substrate, 0, $dotPos+1));
						} else {
							$dotPos = strpos($substrate, ". ");
							if($dotPos !== FALSE) {
								$location = trim(substr($substrate, $dotPos+2));
								$substrate = trim(substr($substrate, 0, $dotPos+1));
							}
						}
					} else if($pos == 0) $location = trim(substr($location, strlen($substrate)));
					else {
						$temp = "On ".$substrate;
						$pos = stripos($location, $temp);
						if($pos !== FALSE && $pos == 0) $location = trim(substr($location, strlen($temp)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					}
				}
			} else {
				$dotPos = strpos($substrate, ". ");
				if($dotPos !== FALSE) {
					$location = trim(substr($substrate, $dotPos+2));
					$substrate = trim(substr($substrate, 0, $dotPos+1));
				}
			}
		} else {//strlen(substrate) == 0
			$sPos = strpos($location, ". On ");
			$inappropriateWords = array("road", "highway", "hiway", "state", "route", "park", "Rd.", "Co.", "County", "miles");
			if($sPos !== FALSE) {
				$sub = trim(substr($location, $sPos+2));
				foreach($inappropriateWords as $inappropriateWord) {
					if(stripos($sub, $inappropriateWord) !== FALSE) {
						$sub = "";
						break;
					}
				}
				if(strlen($sub) > 0) {
					$location = trim(substr($location, 0, $sPos));
					$dotPos = strpos($sub, ".");
					if($dotPos !== FALSE) {
						if(strlen($habitat) == 0) $habitat = trim(substr($sub, $dotPos+1));
						$substrate = trim(substr($sub, 0, $dotPos));
					} else $substrate = $sub;
				}
			} else {//echo "\nline 7530, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
				$sPos = strpos($location, "On ");
				if($sPos !== FALSE && $sPos == 0) {
					$sub = $location;
					//$pos = stripos($sub, ", in ");
					$inappropriateWords = array("road", "north", "south", "east", "west", "highway", "hiway", "area", "state",
					"valley", "slope", "route", "park", "Rd.", "Co.", "County", "miles");
					if(preg_match("/(.*),\\s(in|along|within|at\\s.*)/i", $sub, $mats)) {
						$temp = trim($mats[2]);
						$sub = trim($mats[1]);
						foreach($inappropriateWords as $inappropriateWord) {
							if(stripos($sub, $inappropriateWord) !== FALSE) {
								$sub = "";
								break;
							}
						}
						if(strlen($sub) > 0) {
							$substrate = $sub;
							$commaPos = strpos($temp, ", ");
							if($commaPos !== FALSE) {
								$temp2 = trim(substr($temp, $commaPos+2));
								$temp = trim(substr($temp, 0, $commaPos));
								foreach($inappropriateWords as $inappropriateWord) {
									if(stripos($temp2, $inappropriateWord) !== FALSE) {
										$location = $temp2;
										$habitat = $temp;
										break;
									}
								}
							} else {
								foreach($inappropriateWords as $inappropriateWord) {
									if(stripos($temp, $inappropriateWord) !== FALSE) {
										$temp = "";
										break;
									}
								}
								if(strlen($temp) > 0) $habitat = $temp;
							}
						}
					} else {
						foreach($inappropriateWords as $inappropriateWord) {
							if(stripos($sub, $inappropriateWord) !== FALSE) {
								$sub = "";
								break;
							}
						}
						if(strlen($sub) > 0 && !$this->containsNumber($sub)) {
							$substrate = $sub;
							$location = "";
						}
					}
				}
			}
		}//echo "\nline 7583, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\n";
		if((strlen($state_province) == 0 || strlen($country) == 0 ) && strlen($county) > 0) {
			$ps = $this->getStateFromCounty($county, $states);
			if(count($ps) > 0) {
				if(strlen($state_province) == 0) $state_province = $ps[0];
				if(strlen($country) == 0) $country = $ps[1];
			}
		}
		$sPat = "/((?s).*)\\b(?:\\son\\s|s\\.\\s[il1!|]at\\.|[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|[ (]\\d{2,}|\\d{2,}\\s|[lI!|]ocality|[lI!|]ocation|".
			"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|l?s[:.])|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|".
			"D[lI!|]v[lI!|]s[lI!|][o0]n|(?:U\\.?\\s?S\\.?\\s)?+D[ec]partm[ec]nt|(?:THE )?NEW \\w{4} BOTAN.+|Lichens?|Univers|State|".
			$state_province."|".$county.")/i";
		if(strlen($location) > 0 && strlen($scientificName) > 0) {
			$pos = stripos($location, $scientificName);
			if($pos !== FALSE && $pos == 0) $location = substr($location, strlen($scientificName));
		}
		$subPat = "/((?s).*)\\b(?:[il1!|]at(?:[il1!|]tude|\\.)?|quad|[ec][lI!|][ec]v|Date|Col(?:\\.|".
			":|ls?[:.]?|lectors?|lected|l?s[:.])|(?:H[o0]st\\s)?+Det(?:\\.|ermined by)|(?:THE )?NEW \\w{4} B[OD]TAN.+|(?:(?:Assoc(?:\\.|".
			"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|with)[:;]?)|(?:along|together)\\swith)|State|".$county."|".$state_province.")/i";
		if(strlen($substrate) > 0) $substrate = $this->terminateField(trim($substrate), $subPat);
		if(strlen($county) > 0 && strlen($state_province) > 0 && strcasecmp($county, $state_province) != 0) {
			$pos = stripos($county, $state_province);
			if($pos !== FALSE) $county = trim(substr($county, $pos+strlen($state_province)), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		}
		if(preg_match("/(.*)[0OD]at[ec]/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
		if(strlen($scientificName) == 0) {
			$lines = explode("\n", $str);
			foreach($lines as $line) {
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
					if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
					if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
					if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
					//$location = trim(substr($location, strpos($location, $lWords0)+strlen($lWords0)));
					if(array_key_exists ('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
						break;
					}
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'georeferenceRemarks' => trim($georeferenceRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associated_taxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'eventDate' => $event_date,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	private function getPoliticalConfigInfo($str) {
		if($str) {
			$pat = "/(.*)HERBAR[1Il!|]UM\\s?[O0Q]F\\s?THE UN[1Il!|]VER[S5][1Il!|]TY\\s?[O0Q]F\\s?M[1Il!|]CH[1Il!|]GAN?+(.*)/is";
			if(preg_match($pat, $str, $matches)) {
				$str = trim(trim($matches[1])."\n".trim($matches[2]));
				$pat = "/(.*)Un[1Il!|]v[ec]r[S5][1Il!|]ty\\s?[O0Q]f\\s?M[1Il!|][ec]h[1Il!|]gan\\s?Fungu[S5]\\s?C[O0Q][1Il!|]{2}[ec]ct[1Il!|][O0Q]n?+(.*)/is";
				if(preg_match($pat, $str, $matches2)) $str = trim(trim($matches2[1])."\n".trim($matches2[2]));
			}
			if($this->isFarlowLabel($str)) return $this->doFarlowLabel($str);
			else if($this->isFloraOfAlaskaLabel($str)) return $this->doFloraOfAlaskaLabel($str);
			else if($this->isAlcanExpeditionLabel($str)) return $this->doAlcanExpeditionLabel($str);
			else if($this->isLichenesArticiLabel($str)) return $this->doLichenesArticiLabel($str);
			else if($this->isHattoriTennesseeLabel($str)) return $this->doHattoriTennesseeLabel($str);
			else if($this->isLendemerLichenHerbariumLabel($str)) return $this->doLendemerLichenHerbariumLabel($str);
			else if($this->isLichensOfFloridaLabel($str)) return $this->doLichensOfFloridaLabel($str);
			else if($this->isHerbariumOfMontanaStateUniversityLabel($str)) return $this->doHerbariumOfMontanaStateUniversityLabel($str);
			else if($this->isMontanaStateUniversityHerbariumLabel($str)) return $this->doMontanaStateUniversityHerbariumLabel($str);
			else if($this->isBorealiAmericaniLabel($str)) return $this->doBorealiAmericaniLabel($str);
			else if($this->isLichenesExsiccatiLabel($str)) return $this->doLichenesExsiccatiLabel($str);
			else if($this->isLichensAndMossesOfYellowstoneLabel($str)) return $this->doLichensAndMossesOfYellowstoneLabel($str);
			else if($this->isLichensOfWesternNorthAmericaLabel($str)) return $this->doLichensOfWesternNorthAmericaLabel($str);
			else if($this->isKienerMemorialLabel($str)) return $this->doKienerMemorialLabel($str);
			else if($this->isMycologicalCollectionsLabel($str)) return $this->doMycologicalCollectionsLabel($str);
			else if($this->isLichenesGroenlandiciLabel($str)) return $this->doLichenesGroenlandiciLabel($str);
			else if($this->isLewisAndClarkCavernsLabel($str)) return $this->doMTLichensOfLabel($str);
			else if($this->collId == 42 && $this->isLichensOfLabel($str)) return $this->doMTLichensOfLabel($str);
			else if($this->collId == 42 && $this->isHerbariumOfForestServiceLabel($str)) return array();
			else return $this->doGenericLabel($str);
		}
		return array();
	}

	private function getIdentifier($str, $possibleMonths) {
		$detPatStr = "/\\b(?:(?:D[ec][trf](?:[.:;]|[ec]rmin[ec](?:d b[vy]|r[sz]?)))|(?:[Il!|]d[ec]nt[Il!|]f[Il!|][ec]d b[vy]))[;:]?\\s?(.+)(?:(?:\\n|\\r\\n)((?s).+))?/i";
		if(preg_match($detPatStr, $str, $detMatches)) {
			//$i=0;
			//foreach($detMatches as $detMatch) echo "\ndetMatches[".$i++."] = ".$detMatch."\n";
			$identified_by = trim($detMatches[1], " .\t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			$date_identified = array();
			if(strlen($identified_by) > 0) {
				$datePatternStr = "/(?:(?(?=(?:.*)\\b(?:\\d{1,2})[- ]?+(?:(?i)".$possibleMonths.")[- ]?(?:(?:1[89]|20)\\d{2})))".

					"(.*)\\b(\\d{1,2})[- ]?+((?i)".$possibleMonths.")[- ]?((?:1[89]|20)\\d{2})|".

					"(?:(?(?=(?:.*)\\b(?:(?i)".$possibleMonths.")[,-]?\\s(?:\\d{1,2}),?\\s(?:(?:1[89]|20)\\d{2})))".

					"(.*)\\b((?i)".$possibleMonths.")\\s(\\d{1,2})[,-]?\\s((?:1[89]|20)\\d{2})|".

					"(?:(?(?=(?:.*)\\b(?:(?i)".$possibleMonths.")[,-]?\\s?(?:(?:1[89]|20)\\d{2})))".

					"(.*)\\b((?i)".$possibleMonths.")[,-]?\\s?((?:1[89]|20)\\d{2})|".

					"(.*)\\b((?:1[89]|20)\\d{2})\\b)))/s";

				if(preg_match($datePatternStr, $identified_by, $dateMatches)) {
					//$i=0;
					//foreach($dateMatches as $dateMatch) echo "\ndateMatches[".$i++."] = ".$dateMatch."\n";
					$day = $dateMatches[2];
					if(strlen($day) > 0) {
						$identified_by = $dateMatches[1];
						$date_identified['day'] = $day;
						$date_identified['month'] = $dateMatches[3];
						$date_identified['year'] = $dateMatches[4];
					} else {
						$month = $dateMatches[6];
						if(strlen($month) > 0) {
							$identified_by = $dateMatches[5];
							$date_identified['month'] = $month;
							$date_identified['day'] = $dateMatches[7];
							$date_identified['year'] = $dateMatches[8];
						} else {
							$month = $dateMatches[10];
							if(strlen($month) > 0) {
								$identified_by = $dateMatches[9];
								$date_identified['month'] = $month;
								$date_identified['year'] = $dateMatches[11];
							} else {
								$identified_by = $dateMatches[12];
								$date_identified['year'] = $dateMatches[13];
							}
						}
					}
				} else if(count($detMatches) > 2) {
					$nextLine = trim($detMatches[2]);
					if(preg_match($datePatternStr, $nextLine, $dateMatches)) {
						$day = $dateMatches[2];
						if(strlen($day) > 0) {
							//$identified_by .= $dateMatches[1];
							$date_identified['day'] = $day;
							$date_identified['month'] = $dateMatches[3];
							$date_identified['year'] = $dateMatches[4];
						} else {
							$month = $dateMatches[6];
							if(strlen($month) > 0) {
								//$identified_by .= $dateMatches[5];
								$date_identified['month'] = $month;
								$date_identified['day'] = $dateMatches[7];
								$date_identified['year'] = $dateMatches[8];
							} else {
								$month = $dateMatches[10];
								if(strlen($month) > 0) {
									//$identified_by .=$dateMatches[9];
									$date_identified['month'] = $month;
									$date_identified['year'] = $dateMatches[11];
								} else {
									//$identified_by .= $dateMatches[12];
									$date_identified['year'] = $dateMatches[13];
								}
							}
						}
					}
				}
				$ibLength = strlen($identified_by);
				if($ibLength > 1) {
					if(preg_match("/(.*)\\bDate\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
					if(preg_match("/(.*)\\bAlt\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
					$identified_by = trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				}
				if(!$this->isMostlyGarbage2($identified_by, 0.50)) return array($identified_by, $date_identified);
				else return array("", $date_identified);
			} else if(count($detMatches) > 2) return $this->getIdentifier(trim($detMatches[2]), $possibleMonths);
		}
		return null;
	}

	private function terminateField($f, $regExp) {
		if(preg_match($regExp, $f, $ms)) {
			$temp = str_replace
			(
				array("\r\n", "\n", "\r"),
				array(" ", " ", " "),
				$ms[1]
			);
		}
		else {
			$temp = str_replace
			(
				array("\r\n", "\n", "\r"),
				array(" ", " ", " "),
				$f
			);
		}
		if($this->isMostlyGarbage2($temp, 0.50)) return "";
		else return $temp;
	}

	private function getHabitat($string) {//echo "\nInput to getHabitat: ".$string."\n\n";
		if(strlen($string) > 0) {
			$habitatPatStr = "/((?s).+)\\b(?:Micro)?Hab(?:[il1!|]tat)?[:;,.]?\\s(.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)(?:\\r\\n|\\n\\r|\\n|\\r)/i";
			if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nfirst Match\n";
				$firstPart = trim($habitatMatches[1]);
				$habitat = trim($habitatMatches[2]);
				$nextLine = trim($habitatMatches[3]);
				//echo "\nline 7600, firstPart: ".$firstPart.", habitat: ".$habitat.", nextLine: ".$nextLine."\n\n";
				return array($firstPart, $habitat, $nextLine);
			} else {
				$habitatPatStr = "/((?s).+)\\b(?:Micro)?Hab(?:[il1!|]tat)?[:;,.]?\\s(.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)/i";
				if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nsecond Match\n";
					$firstPart = trim($habitatMatches[1]);
					$habitat = trim($habitatMatches[2]);
					$nextLine = trim($habitatMatches[3]);
					return array($firstPart, $habitat, $nextLine);
				} else {
					$habitatPatStr = "/((?s).+)\\b(?:Micro)?Hab(?:[il1!|]tat)?[:;,.]?\\s(.+)/i";
					if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nthird Match\n";
						$firstPart = trim($habitatMatches[1]);
						$habitat = trim($habitatMatches[2]);
						return array($firstPart, $habitat, "");
					} else {
						$habitatPatStr = "/((?s).+)(?:\\n|\\r\\n)S[il1!|]te[:;,.]?\\s(.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)(?:\\r\\n|\\n\\r|\\n|\\r)/i";
						if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nfourth Match\n";
							$firstPart = trim($habitatMatches[1]);
							$habitat = trim($habitatMatches[2]);
							$nextLine = trim($habitatMatches[3]);
							return array($firstPart, $habitat, $nextLine);
						}
					}
				}
			}
		}
		return array("", "", "");
	}

	private function getSubstrate($string) {
		if(strlen($string) > 0) {
			$sub = "";
			$subPatStr = "/Substrat(?:e|um)[:;,.]?(.+)(?:\\s|\\n|\\r\\n)/i";
			if(preg_match($subPatStr, $string, $subMatches)) $sub = trim($subMatches[1]);
			/*else {
				$subPatStr = "/\\bOn\\s((?:[(A-Za-z),;:.&\-']+?\\s)+)/i";
				if(preg_match($subPatStr, $string, $subMatches)) $sub = trim($subMatches[1]);
			}
			if(strlen($sub) > 0) {
				$pos = stripos($sub, " in ");
				if($pos > 0) $sub = trim(substr($sub, 0, $pos));
				else {
					$pos = stripos($sub, " along ");
					if($pos > 0) $sub = trim(substr($sub, 0, $pos));
					else {
						$pos = stripos($sub, " above ");
						if($pos > 0) $sub = trim(substr($sub, 0, $pos));
						else {
							$pos = stripos($sub, " below ");
							if($pos > 0) $sub = trim(substr($sub, 0, $pos));
							else {
								$pos = stripos($sub, " elev");
								if($pos > 0) $sub = trim(substr($sub, 0, $pos));
								else {
									$pos = stripos($sub, " near");
									if($pos > 0) $sub = trim(substr($sub, 0, $pos));
								}
							}
						}
					}
				}
			}
			$inappropriateWords = array("road", "north", "south", "east", "west", "highway", "hiway", "area", "state",
				"valley", "slope", "route", "Rd.", "Co.", "County");
			foreach($inappropriateWords as $inappropriateWord) if(stripos($sub, $inappropriateWord) !== FALSE) $sub = "";*/
			$sub = trim($sub, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
			if(strlen($sub) > 2 && !$this->isMostlyGarbage($sub, 0.48)) return $sub;
		}
		return "";
	}

	private function getElevation($string) {
		if(strlen($string) > 0) {//echo "\nInput to getElevation: ".$string."\n";
			$possibleNumbers = "[OQSZl|I!&0-9^]";
			$elevPatStr = "/(.*?)\\b((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[SZl|I!&1-9])(?:\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[SZl|I!&1-9]))?)\\s(m\\.?s\\.?m[,.;:]{0,2})\\s(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nelevMatches0[".$i++."]: ".$elevMatche."\n";
				$elevation = str_ireplace
					(
						array("^", "O", "Q", "l", "|", "I", "!", "S", "Z", "&"),
						array("5", "0", "0", "1", "1", "1", "1", "5", "2", "6"),
						trim($elevMatches[2])
					)." ".strtolower(trim($elevMatches[3]));
				$firstPart = trim($elevMatches[1]);
				$nextPart = trim($elevMatches[4]);
				return array($firstPart, $elevation, $nextPart);
			}
			$elevPatStr = "/(?:(?(?=(?:.*?)(?:(?:E[li1|][ce]v(?:ati[o0][nr]|\\.)?|Alt(?:\\.|itude)?)[:;,]?)\\s".
				"(?:(?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])".
				"(?:\\s?(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?".
				"\\s{0,2}(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet))[,;]?(?:(?:\\s|\\n|\\r\\n)?(?:.*+))))".

				"(.*?)(?:(?:E[li1|][ce]v(?:ati[o0][nr]|\\.?)?|Alt(?:\\.|itude)?)[:;,]?)\\s((?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])".
				"(?:\\s?(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}".
				"(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet))\\b[,;]?(?:(?:\\s|\\n|\\r\\n)?(.*+))|".

				"(.*?)(?:(?:E[li1|][ce]v(?:ati[o0][nr]|\\.?)?|Alt(?:\\.|itude)?)[:;,]?)\\s((?:ab[o0]ut\\s|ab[o0]ve\\s|ca(?:\\.?\\s|\\,\\s?)|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]])".
				"(?:\\s?(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}".
				"(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet))\\b[,;]?(.*+))/is";

			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nelevMatches1[".$i++."] = ".$elevMatch."\n";
				if(count($elevMatches) == 4) {
					$elevation = trim($elevMatches[2]);
					if(strcasecmp($elevation, "ISM") == 0 || stripos($elevation, "ssii") !== FALSE ||
						stripos($elevation, "ls") !== FALSE) {
						$elevation = "";
						$firstPart = "";
						$nextPart = "";
					} else {
						$firstPart = trim($elevMatches[1]);
						$nextPart = $elevMatches[3];
					}
				} else {
					$elevation = trim($elevMatches[5]);
					if(strcasecmp($elevation, "ISM") == 0 || stripos($elevation, "ssii") !== FALSE ||
						stripos($elevation, "ls") !== FALSE) {
						$elevation = "";
						$firstPart = "";
						$nextPart = "";
					} else {
						$firstPart = trim($elevMatches[4]);
						$nextPart = trim($elevMatches[6]);
					}
				}
				$elevation = str_ireplace
				(
					array("^", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "ab0ut", "ab0ve", "ab0w", "abow", "meter5"),
					array("5", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "about", "above", "above", "above", "meters"),
					$elevation
				);
				return array($firstPart, $elevation, $nextPart);
			}
			$elevPatStr = "/(?:(?(?=(?:.*?)(?:(?:E[li1|][ce]v(?:ati[o0][nr]|\\.)?|Alt(?:\\.|itude)?)[:;,]?)\\s".
				"(?:(?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])".
				"(?:\\s?(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?".
				"\\s{0,2}(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet))\\b[,;]?(?:(?:\\s|\\n|\\r\\n)?(?:.*+))))".

				"(.*?)(?:(?:E[li1|][ce]v(?:ati[o0][nr]|\\.?)?|Alt(?:\\.|itude)?)[:;,]?)\\s((?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])".
				"(?:\\s?(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}(?:ft\\.?|tt\\.?|m\\.?|rn\\.?|Feet))\\b[,;]?(?:(?:\\s|\\n|\\r\\n)?(.*+))|".

				"(.*?)(?:(?:E[li1|][ce]v(?:ati[o0][nr]|\\.?)?|Alt(?:\\.|itude)?)[:;,]?)\\s((?:ab[o0]ut\\s|ab[o0]ve\\s|ca(?:\\.?\\s|\\,\\s?)|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])".
				"(?:\\s?(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet))\\b[,;]?(.*+))/is";

			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nelevMatches2[".$i++."] = ".$elevMatch."\n";
				if(count($elevMatches) == 4) {
					$elevation = trim($elevMatches[2]);
					if(strcasecmp($elevation, "ISM") == 0 || stripos($elevation, "ssii") !== FALSE ||
						stripos($elevation, "ls") !== FALSE) {
						$elevation = "";
						$firstPart = "";
						$nextPart = "";
					} else {
						$firstPart = trim($elevMatches[1]);
						$nextPart = $elevMatches[3];
					}
				} else {
					$elevation = trim($elevMatches[5]);
					if(strcasecmp($elevation, "ISM") == 0 || stripos($elevation, "ssii") !== FALSE ||
						stripos($elevation, "ls") !== FALSE) {
						$elevation = "";
						$firstPart = "";
						$nextPart = "";
					} else {
						$firstPart = trim($elevMatches[4]);
						$nextPart = trim($elevMatches[6]);
					}
				}
				$elevation = str_ireplace
				(
					array("^", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "ab0ut", "ab0ve", "ab0w", "abow", "meter5"),
					array("5", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "about", "above", "above", "above", "meters"),
					$elevation
				);
				return array($firstPart, $elevation, $nextPart);
			}

			$elevPatStr = "/(?:(?(?=(?:.*?[^°])\\s(?:(?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])(?:\\.[0-9])?".
				"(?:\\s?(?:ft\\.?|tt\\.?|m\\.?|rn\\.?|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet))\\b[,;]?(?:(?:\\s|\\n|\\r\\n)?(?:.*+))))".

				"(.*?[^°])\\s((?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])(?:\\.[0-9])?".
				"(?:\\s?(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet))\\b[,;]?".
				"(?:(?:\\s|\\n|\\r\\n)?(.*+))|".

				"(.*?[^°])\\s((?:ab[o0]ut\\s|ab[o0]ve\\s|ca(?:\\.?\\s|\\,\\s?)|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])(?:\\.[0-9])?".
				"(?:\\s?(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}(?:ft\\.?|tt\\.?|(?:m|rn)(?:\\.|eters?)?|Feet))\\b[,;]?(.*+))/is";

			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nelevMatches3[".$i++."] = ".$elevMatch."\n";
				if(count($elevMatches) == 4) {
					$elevation = trim($elevMatches[2]);
					if(strcasecmp($elevation, "ISM") == 0 || stripos($elevation, "ssii") !== FALSE ||
						stripos($elevation, "ls") !== FALSE) {
						$elevation = "";
						$firstPart = "";
						$nextPart = "";
					} else {
						$firstPart = trim($elevMatches[1]);
						$nextPart = $elevMatches[3];
					}
				} else {
					$elevation = trim($elevMatches[5]);
					if(strcasecmp($elevation, "ISM") == 0 || stripos($elevation, "ssii") !== FALSE ||
						stripos($elevation, "ls") !== FALSE) {
						$elevation = "";
						$firstPart = "";
						$nextPart = "";
					} else {
						$firstPart = trim($elevMatches[4]);
						$nextPart = trim($elevMatches[6]);
					}
				}
				$elevation = str_ireplace
				(
					array("^", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "ab0ut", "ab0ve", "ab0w", "abow", "meter5"),
					array("5", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "about", "above", "above", "above", "meters"),
					$elevation
				);
				return array($firstPart, $elevation, $nextPart);
				$possibleNumbers = "[OQZl|I!&0-9^]";
				$elevPatStr = "/(.*?)(?:(?:Alt\\.?(\\s\(m\\s?\\.?\))?)[:;,]?)\\s(".
					"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
					"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9])".
					"(?:\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
					"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?".
					"\\s{0,2})\\b[,;]?(?:\\s|\\n|\\r\\n)(.*)/is";
				if(preg_match($elevPatStr, $string, $elevMatches)) {
					$elevation = trim($elevMatches[3]);
					if(strcasecmp($elevation, "ISM") == 0 || stripos($elevation, "ssii") !== FALSE) {
						$elevation = "";
						$firstPart = "";
						$nextPart = "";
					} else {
						$firstPart = trim($elevMatches[1]);
						//$unit = trim($elevMatches[2]);
						$elevation = str_replace
						(
							array("^", "O", "Q", "l", "|", "I", "!", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "ab0ut"),
							array("5", "0", "0", "1", "1", "1", "1", "2", "ft", "m", " ", " ", " ", "6", "about"),
							$elevation
						).
						" ".
						str_replace
						(
							array("(", ")"),
							array("", ""),
							trim($elevMatches[2])
						);
						$nextLine = trim($elevMatches[4]);
						$pos = strpos($nextLine, "Notes");
						if($pos !== FALSE) $nextLine = substr($nextLine, 0, $pos);
						return array($firstPart, $elevation, $nextLine);
					}
				}
			}
		}
		return array("", "", "");
	}

	private function max_date($dateArr) {
		$resultDate = array();
		$resultIndex = 0;
		foreach($dateArr as $date) {
			if(array_key_exists('year', $resultDate)) {
				$maxYear = $resultDate['year'];
				$y = $date['year'];
				if($y > $maxYear) $resultDate = $date;
				else if($y == $maxYear) {
					if(array_key_exists('month', $date)) {
						if(array_key_exists('month', $resultDate)) {
							$month = $resultDate['month'];
							if(is_numeric($month)) $maxMonth = $month;
							else $maxMonth = $this->getNumericMonth($month);
							$m = $date['month'];
							if(is_numeric($m)) $nm = $m;
							else $nm = $this->getNumericMonth($m);
							if($nm > $maxMonth) $resultDate = $date;
							else if($nm == $maxMonth) {
								if(array_key_exists('day', $date)) {
									if(array_key_exists('day', $resultDate)) {
										$maxDay = $resultDate['day'];
										$d = $date['day'];
										if($d > $maxDay) $resultDate = $date;
									} else $resultDate = $date;
								}
							}
						} else $resultDate = $date;
					}
				}
			} else $resultDate = $date;
		}
		return $resultDate;
	}

	private function containsNumber($s) {
		return preg_match("/\\d+/", $s);
	}

	private function isText($s) {
		$splitChars = str_split($s);
		foreach($splitChars as $splitChar) {
			$ord = ord($splitChar);
			if(($ord < 65 && $ord != 46) || ($ord > 90 && $ord < 97) || $ord > 122) return FALSE;
		}
		return TRUE;
	}

	private function min_date($dateArr) {
		$resultDate = array();
		$resultIndex = 0;
		foreach($dateArr as $date) {
			if(array_key_exists('year', $resultDate)) {
				$maxYear = $resultDate['year'];
				$y = $date['year'];
				if($y < $maxYear) $resultDate = $date;
				else if($y == $maxYear) {
					if(array_key_exists('month', $resultDate)) {
						if(array_key_exists('month', $date)) {
							$month = $resultDate['month'];
							if(is_numeric($month)) $maxMonth = $month;
							else $maxMonth = $this->getNumericMonth($month);
							$m = $date['month'];
							if(is_numeric($m)) $nm = $m;
							else $nm = $this->getNumericMonth($m);
							if($nm < $maxMonth) $resultDate = $date;
							else if($nm == $maxMonth) {
								if(array_key_exists('day', $resultDate)) {
									if(array_key_exists('day', $date)) {
										$maxDay = $resultDate['day'];
										$d = $date['day'];
										if($d < $maxDay) $resultDate = $date;
									} else $resultDate = $date;
								}
							}
						} else $resultDate = $date;
					}
				}
			} else $resultDate = $date;
		}
		return $resultDate;
	}

	private function getNumericMonth($m) {//echo "\nInput to getNumericMonth: ".$m."\n";
		$index = 1;
		$monthArray = array
		(
			"/Jan(?:\\.|(?:uary))?/i",
			"/Feb(?:\\.|(?:ruary))?/i",
			"/Mar(?:\\.|(?:ch))?/i",
			"/Apr(?:\\.|(?:il))?/i",
			"/May/i",
			"/Jun[.e]?/i",
			"/Jul[.y]?/i",
			"/Aug(?:\\.|(?:ust))?/i",
			"/Sep(?:\\.|(?:t\\.?)|(?:tember))?/i",
			"/Oct(?:\\.|(?:ober))?/i",
			"/Nov(?:\\.|(?:ember))?/i",
			"/Dec(?:\\.|(?:ember))?/i"
		);
		foreach ($monthArray as $monthPat) {
			$mat = preg_match($monthPat, $m);
			if($mat !== FALSE && $mat != FALSE) return $index;
			$index++;
		}
		return 0;
	}

	private function extractCollectorInfo($str, $cStr, $lineAfter=null) {
		//echo "\nExtracting: ".$cStr."\n";
		$detPat = "/\\b(?:Herbar|Cummings\\sHerb|Univ|Botanical\\sGarden)/i";
		if(preg_match($detPat, $cStr)) return null;
		$detPat = "/(.*)\\bdet(?:\\.?|ermined by)?/i";
		if(preg_match($detPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
		if(!preg_match("/^Herb.*/i", $cStr)) {//echo "\nSecond Pattern Not Matched: ".$cStr."\n";
			$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:[tf]emb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
			$mPat = "/^".$possibleMonths."\\s(?:[o0Q]?[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])".
				",?\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})(.*)/i";
			if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
			else {
				$mPat = "/(.+?)".$possibleMonths."\\s(?:[o0Q]?+[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])".
					",?\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})$/i";
				if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
				else {
					$mPat = "/(.+?)(?:[o0Q]?+[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?".$possibleMonths.
						"[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})$/i";
					if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
					else {
						$mPat = "/^(?:[o0Q]?[O0!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?".$possibleMonths.
							"[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})(.+)/i";
						if(preg_match($mPat, $cStr, $dMatches)) $cStr = trim($dMatches[1]);
						else {
							$mPat = "/^(.+?)(?:[o0Q]?+[!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?".$possibleMonths.
								"[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2})\\s(.+)/i";
							if(preg_match($mPat, $cStr, $dMatches)) {
								$cStr = trim($dMatches[1]);
								$temp = trim($dMatches[2]);
								if($this->containsNumber($temp)) $cStr .= " ".$temp;
							}
						}
					}
				}
			}
			$collWords = array_reverse(explode(" ", $cStr));
			$cfds = array();
			foreach($collWords as $collWord) {//echo "\nline 8237, collWord: ".$collWord."\n";
				$collWord = trim($collWord, "-,.");
				if(strlen($collWord) > 2 && !$this->containsNumber($collWord)) {
					$cfds = $this->getCollectorFromDatabase($collWord, $cStr);
					if($cfds) {
						$familyName = $cfds['familyName'];
						$collectorName = $cfds['collectorName'];
						$collectorNum = trim(substr($cStr, strpos($cStr, $familyName)+strlen($familyName)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^*_-");
						if(preg_match("/(.*)(?:No.?|#)(.*)/i", $collectorNum, $matches)) {
							$collectorNum = trim($matches[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^*_-");
							$firstPart = trim($matches[1]);
							$andPos = strpos($firstPart, "&");
							if($andPos !== FALSE && $andPos == 0) $collectorName .= " ".$firstPart;
						} else if(strpos($collectorNum, "&") !== FALSE || strpos($collectorNum, " and ") !== FALSE) {
							//echo "\nColector Number: ".$collectorNum."\n";
							$collectorName .= " ".trim($collectorNum);
							$collectorNum = "";
						}
						if(preg_match("/(.*)\\bSpecimen:\\s(.*)/i", $collectorNum, $cMats)) {
							$temp = trim($cMats[2]);
							if($this->containsNumber($temp)) $collectorNum = $temp;
						}
						$collectorNum = $this->terminateCollectorNum($collectorNum);
						if(strlen($collectorNum) == 0) $collectorNum = $this->extractCollectorNum($str);
						if(preg_match("/^(?:&|and\\s)(.*)/i", $collectorNum, $mats)) {
							$temp = trim($mats[1]);
							$tempWords = array_reverse(explode(" ", $temp));
							if(count($tempWords) > 1) {
								$tempWord = trim($tempWords[0]);
								$l = strlen($tempWord);
								if($l > 0 && (is_numeric($tempWord) || is_numeric(trim(substr($tempWord, 0, $l-1))))) {
									$collectorNum = $tempWord;
									$collectorName .= " and ".trim(substr($temp, 0, strpos($temp, $tempWord)));
								} else {
									$collectorName .= " and ".trim($temp);
									$collectorNum = "";
								}
							} else {
								$collectorName .= " ".trim($temp);
								$collectorNum = "";
							}
						} else {
							if(preg_match("/(.*)\\b(?:&|and\\s)(.*)/i", $collectorNum, $mats)) {
								$temp = trim($mats[1]);
								$l = strlen($temp);
								if($l > 1 && (is_numeric($temp) || is_numeric(trim(substr($temp, 0, $l-1))))) {
									$collectorNum = $temp;
									$collectorName .= " ".trim($mats[2]);
									$collectorName = trim($collectorName);
								} else {
									$temp = trim($mats[2]);
									$tempWords = array_reverse(explode(" ", $temp));
									if(count($tempWords) > 1) {
										$tempWord = trim($tempWords[0]);
										$l = strlen($tempWord);
										if($l > 0 && (is_numeric($tempWord) || is_numeric(trim(substr($tempWord, 0, $l-1))))) {
											$collectorNum = $tempWord;
											$collectorName .= " and ".trim(substr($temp, 0, strpos($temp, $tempWord)));
										} else {
											$collectorName .= " ".trim($temp);
											$collectorNum = "";
										}
									} else {
										$collectorName .= " ".trim($temp);
										$collectorNum = "";
									}
								}
							}
						}
						if($this->containsNumber($collectorNum)) {
							$spacePos = strpos($collectorNum, " ");
							if($spacePos !== FALSE) {
								$temp2 = substr($collectorNum, 0, $spacePos);
								$temp3 = trim(substr($collectorNum, $spacePos+1));
								$spacePos = strpos($temp3, " ");
								if($spacePos !== FALSE) $temp3 = trim(substr($temp3, $spacePos+1));
								if($this->containsNumber($temp2)) {
									if(is_numeric($temp3) || strlen($temp3) < 3 || preg_match("/^\(.{0,9}\)/", $temp3)) $collectorNum = $temp2." ".$temp3;
									else $collectorNum = $temp2;
								} else if(strlen($temp2) < 3) {
									if($this->containsNumber($temp3)) $collectorNum = $temp2." ".$temp3;
								}
							}
							if(strlen($collectorNum) > 2) {
								if(strcmp(substr($collectorNum, 0, 1), "(") == 0) {
									if(strcmp(substr(strrev($collectorNum), 0, 1), "(") == 0) $collectorNum = trim($collectorNum, " ()");
								}
							}
							if(preg_match("/\\b(?:[1!l|I][89][OQ1!Il|ZS&0-9]{2}|[2Z][O0]{2}[OQ1!Il|ZS&0-9])/", $collectorNum)) $collectorNum = "";
						} else $collectorNum = "";
						$cfds['collectorName'] = $collectorName;
						$cfds['collectorNum'] = $collectorNum;
						//echo "\nline 7665, collectorName: ".$collectorName.", collectorNum: ".$collectorNum."\n";
						return $cfds;
					}
				}
			}
		}
		return null;
	}

	private function extractCollectorNum($s) {
		$s = trim($s);
		$sStrs = explode("\n", $s);
		$count = count($sStrs);
		if($count > 0) {
			$possibleNumbers = "[OQSZl|I!&0-9]";
			foreach($sStrs as $sStr) {
				$sStr = trim($sStr);
				if(preg_match("/(?:(?:N(?:um(?:ber)?|o)\\.)|#)\\s?(".$possibleNumbers."{1,6}\\w?+)$/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/N(?:um(?:ber)?|o)\\s(".$possibleNumbers."{1,6}\\w?+)$/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^(?:(?:N(?:um(?:ber)?|o)\\.)|#)\\s?(".$possibleNumbers."{1,6}\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^N(?:um(?:ber)?|o)\\s(".$possibleNumbers."{1,6}\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/\\sSpecimen:\\s(.*)/i", $sStr, $cMats)) {
					$temp = trim($cMats[1]);
					if($this->containsNumber($temp)) return $temp;
				}
				if(preg_match("/^Specimen:\\s(.*)/i", $sStr, $cMats)) {
					$temp = trim($cMats[1]);
					if($this->containsNumber($temp)) return $temp;
				}
			}
			$lastLine = trim($sStrs[$count-1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/^[A-Za-z]{0,3}\\d{2,10}\\w?$/", $lastLine)) return $lastLine;
			else {
				$spacePos = strrpos($lastLine, " ");
				if($spacePos !== FALSE) {
					$lastWord = trim(substr($lastLine, $spacePos+1));
					if(preg_match("/^[A-Za-z]{0,3}\\d{2,10}\\w?$/", $lastWord)) return $lastWord;
				}
			}
			$firstLine = trim($sStrs[0], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/^[A-Za-z]{0,3}\\d{2,10}\\w?$/", $firstLine)) return $firstLine;
		}
		return "";
	}

	private function terminateCollectorNum($col) {//echo "\nInput to terminateCollectorNum: ".$col."\n";
		if(!$this->containsNumber($col)) return "";
		$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
		$datPat = "/(?:(?(?=(?:.*?)\\b(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."))".

			"(.*?)\\b(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."|".

			"(?:(?(?=(?:.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}))".

			"(.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}|".

			"(.*)\\b".$possibleMonths."[.,]?\\b))/i";

		if(preg_match($datPat, $col, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nTdateMatches[".$i++."] = ".$dateMatche."\n";
			$mNum = count($dateMatches);
			if($mNum > 3) $col = trim($dateMatches[3]);
			else if($mNum > 2) $col = trim($dateMatches[2]);
			else $col = trim($dateMatches[1]);
		}
		if(preg_match("/(.*)Det(?:\\.|#|ermine)/i", $col, $dMats)) $col = trim($dMats[1]);
		if(preg_match("/(.*)Elev(?:\\.|ation)/i", $col, $dMats)) $col = trim($dMats[1]);
		//echo "\nline 8332, col: ".$col."\n";
		//$pos = stripos($col, "(");
		//if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Alt.");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Date");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " NEW YORK BOTAN");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " QUAD");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Map/Quad");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		if($this->containsNumber($col)) {
			$spacePos = strrpos($col, " ");
			if($spacePos !== FALSE) {
				$firstPart = trim(substr($col, 0, $spacePos));
				$lastPart = trim(substr($col, $spacePos+1));
				if(is_numeric($lastPart)) {
					$spacePos = strrpos($firstPart, " ");
					if($spacePos !== FALSE) {
						$endOfFirstPart = trim(substr($firstPart, $spacePos+1));
						if(strlen($endOfFirstPart) <= 3 && !is_numeric($endOfFirstPart)) return $endOfFirstPart." ".$lastPart;
						else return $lastPart;
					} else return $col;
				} else if($this->containsNumber($lastPart)) return $lastPart;
				return $col;
			} else return $col;
		}
		return "";
	}

	private function terminateCollector($col) {//echo "\nInput to terminateCollector: ".$col."\n";
		//if(!$this->containsNumber($col)) return "";
		$possibleMonths = "(?:Jan(?:\\.|(?:uar\\w{1,2}))?|Feb(?:\\.|(?:ruar\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)";
		$datPat = "/(?:(?(?=(?:.*?)(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."))".

			"(.*?)(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1])\\s?".
			"(?:-(?:[OQ0]?+[!IlZS&|1-9]|[!IlZ|12][O0Q!IlZS&|0-9]|3[O0Q!Il1]))?\\s?[.-]?\\s?".$possibleMonths."|".

			"(?:(?(?=(?:.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}))".

			"(.*)\\s".$possibleMonths."\\s\\d{1,2}[.,]?\\s\\d{4}|".

			"(.*)\\b".$possibleMonths."[.,]?\\b))/i";

		if(preg_match($datPat, $col, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nTdateMatches[".$i++."] = ".$dateMatche."\n";
			$mNum = count($dateMatches);
			if($mNum > 3) $col = trim($dateMatches[3]);
			else if($mNum > 2) $col = trim($dateMatches[2]);
			else $col = trim($dateMatches[1]);
		}
		if(preg_match("/(.*)Det(?:\\.|#|ermine)/i", $col, $dMats)) $col = trim($dMats[1]);
		if(preg_match("/(.*)Elev(?:\\.|ation)/i", $col, $dMats)) $col = trim($dMats[1]);
		//echo "\nline 6833, col: ".$col."\n";
		//$pos = stripos($col, "(");
		//if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Alt.");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Date");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " NEW YORK BOTAN");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " QUAD");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		$pos = stripos($col, " Map/Quad");
		if($pos !== FALSE && $pos > 3) $col = trim(substr($col, 0, $pos));
		//if($this->containsNumber($col)) return $col;
		return $col;
	}

//this function returns the collector name if it is preceded by a collector label.
//If not found tries to find a collector name from the database in a likely place on many labels
	private function getCollector($str) {//echo "\nInput to getCollector: ".$str."\n";
		if($str) {
			$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
			$collector = "";
			$nextLine = "";
			$identifiedBy = "";
			$otherCatalogNumbers = "";
			$collectorNum = "";
			$isIdentifier = false;
			$collPatternStr = "/\\bC[o0D][l1|!i]{2}(?:\\.|[ec]{2}t[ec]d)?\\s(?:and|&)\\s(?:det(?:\\.|ermined)?|[l1|!i]dent[l1|!i]f[l1|!i]ed)".
				"(?:\\s\\w[yxv])?+[:,;.]?\\s(?!Date)(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
				$isIdentifier = true;
			} else {
				$collPatternStr = "/\\bC[o0D][l1|!i]{1,2}(?:[ec]{2}t[ec]d\\s(?:(?:and|&)\\sprepared\\s)?".
					"\\w[yxv]|[.os]|[ec]{2}t[o0D]rs?|[ec]{2}ti[o0D]n data|\\s&\\s[NW][o0D])?[:,;.]?".
					"\\s(?!Date)(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
				if(preg_match($collPatternStr, $str, $collMatches)) {
					$collector = trim($collMatches[1]);
					if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
				} else {
					$collPatternStr = "/C[o0D][l1|!i]{2}(?:\\.|[ec]{2}t[ec]d)?\\s(and\\sprep(?:ared|[,.])?\\s)?b[yxv][:,;.*]?\\s(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
					if(preg_match($collPatternStr, $str, $collMatches)) {
						$collector = trim($collMatches[1]);
						if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
					} else {
						$collPatternStr = "/\\b(?:C[o0D](?:[l1|!i]{1,2}|U)(?:[ec]{2}t[o0]rs?)?|leg(?:it)?)[:,;.]{1,2}\\s(.+)(?:(?:\\r\\n|\\n|\\r)(.+))?/i";
						if(preg_match($collPatternStr, $str, $collMatches)) {
							$collector = trim($collMatches[1]);
							if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
						} else {
							$collPatternStr = "/(?:\\r\\n|\\n|\\r)(.+),\\s?Co[l1!I]{2}(?:\\.\*|ector)?\\b(?:(?:\\r\\n|\\n|\\r|\\s)(.*))?/i";
							if(preg_match($collPatternStr, $str, $collMatches)) {
								$collector = $collMatches[1];
								if(strpos($collector, " ") !==  FALSE) {
									$words = array_reverse(explode(" ", $collector));
									$index = 0;
									foreach($words as $word) {
										if($index++ == 0) $collector = $word;
										else {
											if($index < 4) {
												$dotPos = strpos($word, ".");
												if($dotPos !== FALSE) {
													if(strlen($word) == 2) $collector = $word." ".$collector;
													else break;
												} else $collector = $word." ".$collector;
											} else break;
										}
									}
								}
								if(count($collMatches) > 2) {
									$nextLine = $collMatches[2];
									if($this->containsNumber($nextLine) && !preg_match("/.*(?:".$possibleMonths.").*/i", $nextLine)) {
										$collectorNum = $nextLine;
									}
								}
								return array
								(
									'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
									'collectorNum' => $collectorNum
								);
							} else {
								$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)by (.+)(?:\\r\\n|\\n|\\r)((?s).*)/i";
								if(preg_match($collPatternStr, $str, $collMatches)) {
									$temp = trim($collMatches[1]);
									$spacePos = strrpos(" ", $temp);
									if($spacePos !== FALSE) $endWord = trim(substr($temp, $spacePos+1));
									else $endWord = $temp;
									if(preg_match("/C[o0D][l1|!i]{2}[ec]{2}t[ec]d/i", $endWord)) {
										$collector = trim($collMatches[2]);
										$nextLine = trim($collMatches[3]);
									} else {
										$cI = $this->getCollector($collMatches[3]);
										if($cI != null && count($cI) > 0) return $cI;
										else {
											$cI = $this->getCollector($temp);
											if($cI != null && count($cI) > 0) return $cI;
										}
									}
								}
							}
						}
					}
				}
			}
			if(strlen($collector) > 1) {
				$collector = trim($collector);
				if(strlen($collector) <= 3 && strcasecmp(substr($collector, 0, 2), "No") == 0) $collector = "";
				$collector = trim(preg_replace("/\\s{2,}/m", " ", $collector));
				if(preg_match("/(.*)Acc(?:[,.]|ession)\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $collector, $cMats)) {//$i=0;foreach($cMats as $cMat) echo "\n6916, cMats[".$i++."] = ".$cMat."\n";
					$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@$%^&*_-");
					$otherCatalogNumbers = trim($cMats[2]);
				} else if(strlen($nextLine) > 1) {
					if(preg_match("/(.*)Acc(?:[,.]|ession)?\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $nextLine, $cMats)) {//$i=0;foreach($cMats as $cMat) echo "\n6916, cMats[".$i++."] = ".$cMat.".\n";
						$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$otherCatalogNumbers = $this->terminateCollectorNum(trim($cMats[2]));
						$nextLine = "";
					}
				}
				if(preg_match("/(.*?)\(No\\.?\\s?([0-9OQ!|lI]+[abc]?)\)/i", $collector, $cMats)) {
					return array
					(
						'collectorName' => trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
						'collectorNum' => $this->replaceMistakenNumbers(trim($cMats[2]))
					);
				}
				if(preg_match("/(.*)\\bSpecimen:\\s(.*)/i", $collector, $cMats)) {
					$temp = trim($cMats[2]);
					if($this->containsNumber($temp)) {
						$collector  = trim($cMats[1]);
						$collectorNum = $temp;
					}
				}
				if(preg_match("/(.*?)\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $collector, $cMats)) {
					$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
					$temp = trim($cMats[2]);
					if($this->containsNumber($temp)) {
						$temp =  $this->terminateCollectorNum($temp);
						$spacePos = strpos($temp, " ");
						if($spacePos !== FALSE) {
							$temp2 = substr($temp, 0, $spacePos);
							$temp3 = trim(substr($temp, $spacePos+1));
							$spacePos = strpos($temp3, " ");
							if($spacePos !== FALSE) $temp3 = trim(substr($temp3, $spacePos+1));
							if($this->containsNumber($temp2)) {
								if(is_numeric($temp3) || strlen($temp3) < 3) $collectorNum = $temp2." ".$temp3;
								else $collectorNum = $temp2;
							} else if(strlen($temp2) < 3) {
								if($this->containsNumber($temp3)) $collectorNum = $temp2." ".$temp3;
							}
						} else $collectorNum = $temp;
					}
				} else if(strlen($nextLine) > 0) {
					if(preg_match("/(.*?)\\b(?:[N][o0Q][.o]|#)(.*)/i", $nextLine, $cMats)) {
						$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$temp = trim($cMats[2]);
						if($this->containsNumber($temp)) $collectorNum = $this->terminateCollectorNum($temp);
					}
				}
				if(preg_match("/(.*)\\bDet[.:;,](.*)/i", $collector, $mats)) {
					$temp = trim($mats[2]);
					$mPat = "/^.+?(?:(?:[o0Q]?+[!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?(?:".$possibleMonths.
						")[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2}))\\s(.+)/i";
					if(preg_match($mPat, $temp, $dMatches)) {
						if(count($dMatches) > 1) {
							$temp = trim($dMatches[1]);
							if(strlen($temp) > 1 && $this->containsNumber($temp)) $collectorNum = $temp;
						}
					}
					$collector = trim($mats[1]);
				} else if(preg_match("/(.*)\\bDet[.:;,]/i", $collectorNum, $mats)) {
					$temp = trim($mats[1]);
					if($this->containsNumber($temp)) $collectorNum = $temp;
					else $collectorNum = "";
				}
				$dateIndex = stripos($collector, " Date ");
				if($dateIndex > 0) $collector = substr($collector, 0, $dateIndex);
				if(strlen($collector) > 0) $collector = $this->terminateCollector($collector);
				if(strlen($collectorNum) > 0) {
					$temp = $this->terminateCollectorNum($collectorNum);
				}
				if(strlen($collector) > 0 && strlen($collectorNum) == 0) {
					$spacePos = strrpos($collector, " ");
					if($spacePos !== FALSE) {//echo "\nline 6961, spacePos !== FALSE\n";
						$potCollector = trim(substr($collector, 0, $spacePos));
						$potCollNum = trim(substr($collector, $spacePos));
						while($spacePos !== FALSE && !$this->containsNumber($potCollNum)) {
							$potCollNum = trim(substr($potCollector, $spacePos));
							$potCollector = trim(substr($potCollector, 0, $spacePos));
							$spacePos = strrpos($potCollector, " ");
						}
						if(strlen($potCollector) > 3 && $this->containsNumber($potCollNum)) {
							$collector = $potCollector;
							$collectorNum = $potCollNum;
						}
					}
				}
				$collectorNum = trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
				if(strlen($collectorNum) == 0) $collectorNum = $this->extractCollectorNum($str);
				$collector = str_replace(chr(13), " ", $collector);
				if(strlen($collectorNum) > 0) {
					$andPos = strpos($collectorNum, " & ");
					if($andPos !== FALSE) {
						$collector .= " ".trim(substr($collectorNum, $andPos));
						$collectorNum = trim(substr($collectorNum, 0, $andPos));
					} else {
						$andPos = strpos($collectorNum, " and ");
						if($andPos !== FALSE) {
							$collector .= " ".trim(substr($collectorNum, $andPos));
							$collectorNum = trim(substr($collectorNum, 0, $andPos));
						}
					}
					if(preg_match("/(.*)Elev(?:\\.|ation)?/i", $collectorNum, $mats)) $collectorNum = trim($mats[1]);
					else if(preg_match("/(.+)\\s\\d{1,5}\\s?(?:m|rn|ft)\\.?/i", $collectorNum, $mats)) $collectorNum = trim($mats[1]);
					if(!$this->containsNumber($collectorNum)) $collectorNum = "";
				}
				$namePat = "/([A-Z]\\.?\\s?[A-Z]\\.?\\s[a-zA-Z]{2,}\\s|[A-Z][a-zA-Z]+[A-Z](?:\\.|[a-zA-Z]{2,})?\\s[A-Z][a-zA-Z]{2,}).*/";
				if(preg_match($namePat, $collector, $nMats)) $collector = trim($nMats[1]);
				if($isIdentifier) $identifiedBy = $collector;
				if(preg_match("/(?:[A-Za-z]+\\s)?by\\s(.*)/i", $collector, $mats)) {
					$collector = trim($mats[1]);
					if(preg_match("/(.+)\\s[A-Za-z ,.]+\\sby\\s.*/i", $collector, $mats2)) {
						if(count($mats2) > 1) $collector = trim($mats2[1]);
					}
				}
				//echo "\nline 9790, collector: ".$collector.", collectorNum: ".$collectorNum."\n";
				if(strlen($collector) > 0) {
					if(strlen($collectorNum) > 2) {
						if(strcmp(substr($collectorNum, 0, 1), "(") == 0) {
							if(strcmp(substr(strrev($collectorNum), 0, 1), "(") == 0) $collectorNum = trim($collectorNum, " ()");
						}
					}
					return array
					(
						'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
						'collectorNum' => $collectorNum,
						'otherCatalogNumbers' => $otherCatalogNumbers,
						'identifiedBy' => $identifiedBy
					);
				}
			}
			//I have observed that on many labels -- especially those from NYBG -- have a line that says
			//something like "Collected on the 4th Crum Bryophyte Workshop"
			//when this are there the collector is often a line or two above it
			$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)Co[l|1!I]{2}ected (?:during|on) the (?:.*)/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n6984, collMatches[".$i++."] = ".$collMatche.".\n";
				$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
				if($result != null) return $result;
			}
			$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)$/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n6995, collMatches[".$i++."] = ".$collMatche.".\n";
				$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
				if($result != null) return $result;
			}
			$collPatternStr = "/(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r)(.*)(?:\\n|\\n\\r)NEW \\w{4} BOTAN.+/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n6973, collMatches[".$i++."] = ".$collMatche.".\n";
				$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
				if($result != null) return $result;
				$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
				if($result != null) return $result;
			}
		}
		return array();
	}

//gets dates in the form DayMonthYear.  If not found it will get dates like Month, Year
	private function getDMYDates($str, $possibleMonths) {
		$results = array();
		if($str) {
			$datePatternStr = "/(?:(?(?=(?:.*)\\b(?:\\d{1,2})[-\\s]?(?:(?i)".$possibleMonths.")[-\\s]?(?:\\d{4})))".

				"(.*)\\b(\\d{1,2})[-\\s]?((?i)".$possibleMonths.")[-\\s]?(\\d{4})|".

				"(.*)\\b((?i)".$possibleMonths."),?\\s?(\\d{4}))/s";

			if(preg_match($datePatternStr, $str, $dateMatches)) {
				$day = $dateMatches[2];
				if(strlen($day) > 0) {
					$results[] = array("day" => str_pad($day, 2, "0", STR_PAD_LEFT), "month" => str_pad($this->getNumericMonth(str_replace(".", "", $dateMatches[3])), 2, "0", STR_PAD_LEFT), "year" => $dateMatches[4]);
					//$results[] = array("day" => $day, "month" => str_replace(".", "", $dateMatches[3]), "year" => $dateMatches[4]);
					$results = array_merge_recursive($results, $this->getDMYDates($dateMatches[1], $possibleMonths));
				} else {
					$results[] = array("month" => str_pad($this->getNumericMonth(str_replace(".", "", $dateMatches[6])), 2, "0", STR_PAD_LEFT), "year" => $dateMatches[7]);
					//$results[] = array("month" => str_replace(".", "", $dateMatches[6]), "year" => $dateMatches[7]);
					$results = array_merge_recursive($results, $this->getDMYDates($dateMatches[5], $possibleMonths));
				}

			}
		}
		return $results;
	}

	private function getMDYDates($str, $possibleMonths) {
		$results = array();
		if($str) {
			$datePatternStr = "/(.*)\\b((?i)".$possibleMonths.")\\s?(\\d{1,2}),?\\s(\\d{4})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {
				array_push($results, array("day" => str_pad($dateMatches[3], 2, "0", STR_PAD_LEFT), "month" => str_pad($this->getNumericMonth(str_replace(".", "", $dateMatches[2])), 2, "0", STR_PAD_LEFT), "year" => $dateMatches[4]));
				//array_push($results, array("day" => $dateMatches[3], "month" => str_replace(".", "", $dateMatches[2]), "year" => $dateMatches[4]));
				$results = array_merge_recursive($results, $this->getMDYDates($dateMatches[1], $possibleMonths));
			}
		}
		return $results;
	}

	private function getNumericDates($str) {
		$results = array();
		$letterMonths = array
		(
			1=>'January',
			2=>'February',
			3=>'March',
			4=>'April',
			5=>'May',
			6=>'June',
			7=>'July',
			8=>'August',
			9=>'September',
			10=>'October',
			11=>'November',
			12=>'December'
		);
		if($str) {
			$possibleNumbers = "[OQSZl|I!&0-9]";
			$datePatternStr = "/(?(?=(?:.*)\\b(?:(?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\s?[-\/]\\s?(?:[OQ0]?".
				$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[-\/]\\s?(?:[OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]]))".

				"(.*)\\b((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\s?[-\/]\\s?([OQ0]?".
				$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[-\/]\\s?([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]])|".

				"(.*)\\b([OQ0]?".$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[-\/]\\s?([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]])\\s?[-\/]\\s?((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}))/s";

			if(preg_match($datePatternStr, $str, $dateMatches)) {
				$year = $this->replaceMistakenNumbers($dateMatches[2]);
				if($year != null && strlen($year) > 1) {
					$month = $this->removeLeadingZeros($this->replaceMistakenNumbers($dateMatches[3]));
					if(is_numeric($month) && $month > 0 && $month <= 12) {
						array_push(
							$results,
							array
							(
								"day" => str_pad($this->replaceMistakenNumbers($dateMatches[4]), 2, "0", STR_PAD_LEFT),
								"month" => $month,
								//"month" => $letterMonths[$month],
								"year" => $year
							)
						);
					}
					$results = array_merge_recursive($results, $this->getNumericDates($dateMatches[1]));
				} else {
					$year = $this->replaceMistakenNumbers($dateMatches[8]);
					$month = $this->replaceMistakenNumbers($dateMatches[6]);
					if(is_numeric($month) && $month > 0 && $month <= 12) {
						array_push(
							$results,
							array
							(
								"day" => str_pad($this->replaceMistakenNumbers($dateMatches[7]), 2, "0", STR_PAD_LEFT),
								"month" => str_pad($month, 2, "0", STR_PAD_LEFT),
								"year" => $year
							)
						);
					}
					$results = array_merge_recursive($results, $this->getNumericDates($dateMatches[5]));
				}
			}
		}
		return $results;
	}

	private function getTRSCoordinates($str) {
		$results = array();
		if($str) {
			$possibleNumbers = "[OQSZl|I!&\\d]";
			$trsPatStr = "/(?(?=(?:.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)".
				"R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".
				$possibleNumbers."{1,3})\\b)".
//if the condition is true then the form is TRS
				"(.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(".$possibleNumbers."{1,3})\\s?([NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(".
				$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(".$possibleNumbers."{1,3})\\b|".
//else the form is STR
				"(.*)\\b[S5](?:\\.|ect?\\.?|ection)?\\s?(".$possibleNumbers."{1,3}),?(?:\\s|\\n|\\r\\n)T(?:\\.|wnshp.?|ownship)?\\s?(".
				$possibleNumbers."{1,3})\\s?([NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(".$possibleNumbers."{1,3}\\s?[EW])\\.?\\b)/is";

			if(preg_match($trsPatStr, $str, $trsMatches)) {
				$township = trim($trsMatches[2]);
				if($township != null && strlen($township) > 0) {
					$trs = "TRS: T.".$this->replaceMistakenNumbers($township).$trsMatches[3]."., R.".
						$this->replaceMistakenNumbers(trim($trsMatches[4]))."., Sec. ".$this->replaceMistakenNumbers(trim($trsMatches[5]));
					array_push($results, $trs);
					$results = array_merge_recursive($results, $this->getTRSCoordinates($trsMatches[1]));
				} else {
					$township = trim($trsMatches[8]);
					$trs = "TRS: T.".$this->replaceMistakenNumbers($township).$trsMatches[9]."., R.".
						$this->replaceMistakenNumbers(trim($trsMatches[10]))."., Sec. ".$this->replaceMistakenNumbers(trim($trsMatches[7]));
					array_push($results, $trs);
					$results = array_merge_recursive($results, $this->getTRSCoordinates($trsMatches[6]));
				}
			}
		}
		return $results;
	}

	private function getUTMCoordinates($str) {
		$results = array();
		if($str) {
			$possibleNumbers = "[OQSZl|I!&\\d]";
			$utmPatStr = "/(.*)\\b(UTM:?(?:\\s|\\n|\\r\\n)(?:Zone\\s)?(".$possibleNumbers."{1,2})(\\w?(?:\\s|\\n|\\r\\n))(".
				$possibleNumbers."{1,8}E?(?:\\s|\\n|\\r\\n)".$possibleNumbers."{1,8}N?))\\b/is";
			if(preg_match($utmPatStr, $str, $utmMatches)) {
				$utm = "UTM: ".$this->replaceMistakenNumbers(trim($utmMatches[3])).$utmMatches[4].$this->replaceMistakenNumbers(trim($utmMatches[5]));
				array_push($results, $utm);
				$results = array_merge_recursive($results, $this->getUTMCoordinates($utmMatches[1]));
			}
		}
		return $results;
	}

	private function getLatLongs($str) {//echo "\nInput to getLatLongs:\n".$str."\n";
		$results = array();
		if($str) {
			$latLongPatStr = "/(.*?)\\b((?:\\d{1,3}+(?:\\.\\d{1,7})?)\\s?°\\s?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)?\\s?\'".
				"(?:\\s|\\n|\\r\\n)?(?:\\d{1,3}(?:\\.\\d{1,3})?\\s?\")?)?+\\s??(?:N(?:orth)?|S(?:outh)?))\\b[,.]?(?:\\s|\\n|\\r\\n)?".
				"(?:L(?:ong|at)(?:\\.|itude)?[:,]?(?:\\s|\\n|\\r\\n)?)?((?:\\d{1,3}+(?:\\.\\d{1,7})?)\\s?°".
				"(?:\\s|\\n|\\r\\n)?(?:(?:\\d{1,3}(?:\\.\\d{1,3})?)?\\s?\'(?:\\s|\\n|\\r\\n)?(?:\\d{1,3}(?:\\.\\d{1,3})?\\s?\")?)?+".
				"\\s?(?:E(?:ast)?|W(?:est)?))\\b/is";
			if(preg_match($latLongPatStr, $str, $latLongMatches)) {//$i=0;foreach($latLongMatches as $latLongMatch) echo "\nlatLongMatches[".$i++."] = ".$latLongMatch."\n";
				$latitude = $this->replaceMistakenNumbers(trim($latLongMatches[2]));
				$longitude = $this->replaceMistakenNumbers(trim($latLongMatches[3]));
				array_push($results, array("latitude" => $latitude, "longitude" => $longitude));
				$results = array_merge_recursive($results, $this->getLatLongs($latLongMatches[1]));
			}
		}
		return $results;
	}

	private function fixLatLongs($str) {
		if($str) {
			$possibleNumbers = "[OQSZl|I!&0-9]";
			//first find patterns with degree signs and minutes signs and seconds signs and no non-integer values
			$latLongPatStr = "/(?:(?(?=(?s:.*)\\b(?:".$possibleNumbers."{1,3}+)\\s?[°*?](?:\\s|\\n|\\r\\n)?".
				"(?:".$possibleNumbers."{1,3})\\s?['*?](?:\\s|\\n|\\r\\n)?".
				"(?:(?:".$possibleNumbers."{1,3})\\s?\")?\\s?(?:N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)?(?:L(?:ong|at)(?:[\\._]|(?:itude))?)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n)".
				"(?:".$possibleNumbers."{1,3})\\s?[°*?](?:\\s|\\n|\\r\\n)?".
				"(?:".$possibleNumbers."{1,3})\\s?['*?](?:\\s|\\n|\\r\\n)?".
				"(?:(?:".$possibleNumbers."{1,3})\\s?\")?\\s?(?:E(?:ast)?|(?:W|VV)(?:est)?)\\b(?s:.*)))".

				"((?s).*)\\b(".$possibleNumbers."{1,3}+)\\s?[°*?](?:\\s|\\n|\\r\\n)?".
				"(?:(".$possibleNumbers."{1,3})\\s?['*?](?:\\s|\\n|\\r\\n)?".
				"(".$possibleNumbers."{1,3})\\s?\")?\\s?(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)?(?:L(?:ong|at)(?:[\\._]|(?:itude))?)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n)".
				"(".$possibleNumbers."{1,3})\\s?[°*?](?:\\s|\\n|\\r\\n)?".
				"(".$possibleNumbers."{1,3})\\s?['*?](?:\\s|\\n|\\r\\n)?".
				"(?:(".$possibleNumbers."{1,3})\\s?\")?\\s?(E(?:ast)?|(?:W|VV)(?:est)?)\\b((?s).*)|".

			//if not found find patterns with no degree signs or minutes signs or seconds signs and no non-integer values
				"(?:(?(?=(?s:.*)\\b(?:".$possibleNumbers."{1,3}+)\\s(?:\\s|\\n|\\r\\n)?".
				"(?:(?:".$possibleNumbers."{1,3})\\s(?:\\s|\\n|\\r\\n)?".
				"(?:(?:".$possibleNumbers."{1,3})))\\s?(?:N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)?(?:L(?:ong|at)(?:[._]|(?:itude))?):?,?\\s{0,2}(?:\\s|\\n|\\r\\n)".
				"(?:".$possibleNumbers."{1,3})\\s(?:\\s|\\n|\\r\\n)?".
				"(?:(?:".$possibleNumbers."{1,3})\\s(?:\\s|\\n|\\r\\n)?".
				"(?:".$possibleNumbers."{1,3}))\\s?(?:E(?:ast)?|(?:W|VV)(?:est)?)\\b(?s:.*)))".

				"((?s).*)\\b(".$possibleNumbers."{1,3}+)\\s(?:\\s|\\n|\\r\\n)?".
				"(?:(".$possibleNumbers."{1,3})\\s(?:\\s|\\n|\\r\\n)?".
				"(".$possibleNumbers."{1,3}))\\s?(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)?(?:L(?:ong|at)(?:[._]|(?:itude))?):?,?\\s{0,2}(?:\\s|\\n|\\r\\n)".
				"(".$possibleNumbers."{1,3})\\s(?:\\s|\\n|\\r\\n)?".
				"(?:(".$possibleNumbers."{1,3})\\s(?:\\s|\\n|\\r\\n)?".
				"(".$possibleNumbers."{1,3}))\\s?(E(?:ast)?|(?:W|VV)(?:est)?)\\b((?s).*)|".

			//if not found look for patterns with possible single quotes as sign for seconds with possible space only in degrees
				"(?:(?(?=(?s:.*)\\b(?:(?:".$possibleNumbers."{1,3}+(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|\*|\?|\\s|(?:deg(?:[\\._]|rees)?))".
				"\\s?'?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\?|\*(?:min(?:[\\._]|utes)?)))".
				"\\s?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[\\._]|onds)?)))?\\s?".
				"(?:N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n))?".
				"(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|\?|\*|\\s|(?:deg(?:[\\._]|rees)?))".
				"\\s?'?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\?|\*|(?:min(?:[\\._]|utes)?)))".
				"\\s?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[\\._]|onds)?)))?".
				"\\s?(?:E(?:ast)?|(?:W|VV)(?:est)?))\\b(?:(?s).*)))".

				"((?s).*)\\b(?:(".$possibleNumbers."{1,3}+(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|\?|\*|\\s|(?:deg(?:[\\._]|rees)?))".
				"\\s?'?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|(?:min(?:[\\._]|utes)?)))".
				"\\s?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[\\._]|onds)?)))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n))?".
				"(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|\*|\?|\\s|(?:deg(?:[._]|rees)?))".
				"\\s?'?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|(?:min(?:[._]|utes)?)))".
				"\\s?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[._]|onds)?)))?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?))\\b((?s).*)|".

			//if not found look for patterns with possible double quotes or spaces as sign for degrees or minutes
				"((?s).*)\\b(?:(".$possibleNumbers."{1,3}+(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:\"|°|\*|\?|\\s|(?:deg(?:[._]|rees)?))".
				"\\s?'?(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|\\s|(?:min(?:[._]|utes)?))".
				"\\s?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[._]|onds)?)))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n))?".
				"(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:\"|°|\?|\*|\\s|(?:deg(?:[._]|rees)?))".
				"\\s?'?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\?|\*|\\s|(?:min(?:[._]|utes)?)))".
				"\\s?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[._]|onds)?)))?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?))\\b((?s).*))))/i";

			if(preg_match($latLongPatStr, $str, $latLongMatches)) {
				//$i=0;
				//foreach($latLongMatches as $latLongMatch) echo "\nlatLongMatches[".$i++."] = ".$latLongMatch."\n";
				$latitude = trim($latLongMatches[2]);
				if($latitude != null && strlen($latitude) > 0) {
					$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."°";
					$next = trim($latLongMatches[3]);
					if($next != null && strlen($next) > 0) {
						$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[4]);
						if($next != null && strlen($next) > 0) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$latitude .= strtoupper(substr(trim($latLongMatches[5]), 0, 1));
					$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[6])))."°";
					$next = trim($latLongMatches[7]);
					if($next != null && strlen($next) > 0) {
						$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
						$next = trim($latLongMatches[8]);
						if($next != null && strlen($next) > 0) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
					}
					$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[9]), 0, 1)));
					//echo "\nstrlen(latitude) > 0\n\n";
					return $this->fixLatLongs($latLongMatches[1]).$latitude.", ".$longitude.$latLongMatches[10];
				} else {
					$latitude = trim($latLongMatches[12]);
					if($latitude != null && strlen($latitude) > 0) {
						$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."°";
						$next = trim($latLongMatches[13]);
						if($next != null && strlen($next) > 0) {
							$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
							$next = trim($latLongMatches[14]);
							if($next != null && strlen($next) > 0) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
						}
						$latitude .= strtoupper(substr(trim($latLongMatches[15]), 0, 1));
						$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[16])))."°";
						$next = trim($latLongMatches[17]);
						if($next != null && strlen($next) > 0) {
							$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
							$next = trim($latLongMatches[18]);
							if($next != null && strlen($next) > 0) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
						}
						$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[19]), 0, 1)));
						//echo "\nstrlen(latitude) > 0\n\n";
						return $this->fixLatLongs($latLongMatches[11]).$latitude.", ".$longitude.$latLongMatches[20];
					} else {
						$latitude = trim($latLongMatches[22]);
						if($latitude != null && strlen($latitude) > 0) {
							$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."°";
							$next = trim($latLongMatches[23]);
							if($next != null && strlen($next) > 0) {
								$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
								$next = trim($latLongMatches[24]);
								if($next != null && strlen($next) > 0) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
							}
							$latitude .= strtoupper(substr(trim($latLongMatches[25]), 0, 1));
							$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[26])))."°";
							$next = trim($latLongMatches[27]);
							if($next != null && strlen($next) > 0) {
								$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
								$next = trim($latLongMatches[28]);
								if($next != null && strlen($next) > 0) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
							}
							$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[29]), 0, 1)));
							return $this->fixLatLongs($latLongMatches[21]).$latitude.", ".$longitude.$latLongMatches[30];
						} else {
							$latitude = trim($latLongMatches[32]);
							if($latitude != null && strlen($latitude) > 0) {
								$latitude = str_replace("_", ".", $this->replaceMistakenNumbers($latitude))."°";
								$next = trim($latLongMatches[33]);
								if($next != null && strlen($next) > 0) {
									$latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
									$next = trim($latLongMatches[34]);
									if($next != null && strlen($next) > 0) $latitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
								}
								$latitude .= strtoupper(substr(trim($latLongMatches[35]), 0, 1));
								$longitude = str_replace("_", ".", $this->replaceMistakenNumbers(trim($latLongMatches[36])))."°";
								$next = trim($latLongMatches[37]);
								if($next != null && strlen($next) > 0) {
									$longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."'";
									$next = trim($latLongMatches[38]);
									if($next != null && strlen($next) > 0) $longitude .= str_replace("_", ".", $this->replaceMistakenNumbers($next))."\"";
								}
								$longitude .= str_replace("VV", "W", strtoupper(substr(trim($latLongMatches[39]), 0, 1)));
								return $this->fixLatLongs($latLongMatches[31]).$latitude.", ".$longitude.$latLongMatches[40];
							}
						}
					}
				}
			}
		}
		return $str;
	}

	private function fixString($str) {
		if($str) {
			//$str = str_replace("/^?/", "", $str);
			$catNo = $this->catalogNumber;
			$needles = array("â€¢", "Ã“", "Â»", "Ã¢", "Ã¨", "Ã¬", "Ã¹", "ÃŸ", "Ã ", "Ã¶", "Ãº", "Ã¡", "Ã¤", "Ã¼", "Ã³", "Ã­", "(FÂ£e)", "(F6e)", "/\\_", "/\\", "/'\\_", "/'\\", "/°\\", "AÂ£", " ", " V/", "Â¥", "Miill.", "&gt;", "&lt;", "—", "ï»¿", "&amp;", "&apos;", "&quot;", "\/V", " VV_", " VV.", "\/\/_", "\/\/", "\X/", "\\'X/", chr(157), chr(226).chr(128).chr(156), "Ã©", "/\ch.", "/\.", "/-\\", "X/", "\X/", "\Y/", "`\â€˜i/", chr(96), chr(145), chr(146), "â€˜", "’" , chr(226).chr(128).chr(152), chr(226).chr(128).chr(153), chr(226).chr(128), "“", "”", "”", chr(147), chr(148), chr(152), "Â°", "º", chr(239));
			$replacements = array(".", "O", ".", "a", "e", "i", "u", "B", "a", "o", "u", "a", "a", "ü", "o", "i", "(Fée)", "(Fée)", "A.", "A", "A.", "A", "A", "AK", " ", " W ", "W", "Müll.", ">", "<", "-", "", "&", "'", "\"", "W", " W.", " W.", "W.", "W", "W", "W", "", "\"", "é", "Ach.", "A.", "A","W","W", "W", "W", "'", "'", "'", "'", "'", "'", "'", "\"", "\"", "\"", "\"", "\"", "\"", "\"", "°", "°", "°");
			$pat = "/\\A[^\w(]+(.*)/s";
			if(preg_match($pat, $str, $patMatches)) $str = trim($patMatches[1]);
			$str = str_replace($needles, $replacements, $str);
			if($catNo) {
				$str = str_replace("0".$catNo, "", $str);
				$str = str_replace("O".$catNo, "", $str);
				$str = str_replace($catNo, "", $str);
				$firstChar = substr($catNo, 0, 1);
				if(!is_numeric($firstChar)) {
					while(strlen($catNo) > 1 && !is_numeric($firstChar)) {
						$catNo = substr($catNo, 1);
						$firstChar = substr($catNo, 0, 1);
					}
					$str = str_replace("0".$catNo, "", $str);
					$str = str_replace("O".$catNo, "", $str);
					$str = str_replace($catNo, "", $str);
				}
			}
			$str = preg_replace
			(
				array("/Rama.{1,2}ina /i", "/([A-Za-z]{4,14}+)'(of)\\s(\\w+)/i", "/\\s\)M[u][1!|l|]{2}\\.?\\s?Arg[.,]?/i", "/\(Ach\\.?\)\\sM[\\w01]{2,4}\\.?\\sArg\\.?/i", "/M[Uui][A-Za-z][l1!|I]\\.?\\sArg\\.?/", "/M[Uui][il1!| ]{2,5}\\.?\\sArg\\.?/", "/M[ul10|!\']{3,5}\\.?\\sArg\\.?/", "/U-[S5]-A.{0,2}/", "/\\.{3,}/", "/Lichens of(\\w+)/i", "/\\bQu\\wrcus\\b/i", "/\\bVVYOMING\\b/i", "/\) Â.{0,2}ch\\./", "/\(Â.{0,2}ch\\.\)/"),
				array("Ramalina ", "\${1} \${2} \${3}", ") Müll. Arg.", "(Ach.) Müll. Arg.", "Müll. Arg.", "Müll. Arg.", "Müll. Arg.", "USA", " ", "Lichens of \${1}", "Quercus", "Wyoming", ")Ach.", "(Ach.)"), $str, -1, $count
			);
			//$str = str_replace("Miill.", "Müll.", $str);
			//if($count > 0) echo "Replaced It\n\n";//remove barcodes (strings of ~s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long), one-character lines, and latitudes and longitudes with double quotes instead of degree signs
			$false_num_class = "[OSZl|I!\d]";//the regex class that represents numbers and characters that numbers are commonly replaced with
			$pattern =
				array(
				"/^LEWIS\\sAND\\sCLARK\\s?CAVERNS\\s?STATE\\sPARK/m",
				"/[\|!Iil\"'1U~()@\[\]{}H\/\\\]{6,}/", //strings of ~s, 's, "s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long (from barcodes)
				"/^.{1,2}$/m", //one-character lines (Tesseract must generate a 2-char end of line)
				"/(lat|long)(\.|,|:|.:|itude)(:|,)?\s?(".$false_num_class."{1,3}(\.".$false_num_class."{1,7})?)\"/i" //the beginning of lat-long repair
				);
			$replacement = array("LICHENS OF LEWIS AND CLARK CAVERNS STATE PARK", "", "", "\${1}\${2}\$3 \${4}".chr(176));
			$str = preg_replace($pattern, $replacement, $str, -1);
			$str = str_replace("Â°", chr(176), $str);
			$str = $this->replaceMissingDegreeSignsInLatLongs($str);
			$str = $this->fixLatLongs($str);
			$str = $this->fixDates($str);
			$sArray = explode("\n", $str);
			$sResult = "";
			foreach($sArray as $s) $sResult .= trim($s, " \0\x0B!\\~@$%^?*_")."\n";
			$sResult = preg_replace("/[\n\r]{2,}/", "\n", $sResult);
			return $sResult;
		}
		return $str;
	}

	private function fixGapsInMDYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$badDatePatternStr = "/(.*)\\b((?i)".$possibleMonths.")\\s([0123Ili!|oOzZ]\\s".$possibleNumbers."?),?\\s?([12Iil!|zZ]\\s?"
				."[7890Oo]\\s?".$possibleNumbers."\\s?".$possibleNumbers."\\b)(.*)/s";
			$l = preg_match($badDatePatternStr, $str, $locMatches);
			if($l) {
				return $this->fixGapsInMDYDates($locMatches[1], $possibleNumbers, $possibleMonths).$locMatches[2]." ".
					str_replace(' ', '', $locMatches[3]).", ".str_replace(' ', '', $locMatches[4]).$locMatches[5];
			}
		}
		return $str;
	}

//this function does not fix gaps in years when the days have only a single digit
	private function fixGapsInDMYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$result = "";
			$badDatePatternStr = "/(.*)\\b([0123Iil!|oOQzZ]\\s?".$possibleNumbers.")([-\\s])?((?i)".$possibleMonths.")([-\\s]?)(".
				"[12Iil!|zZ]\\s?[7890OQo]\\s?".$possibleNumbers."\\s?".$possibleNumbers."\\b)(.*)/s";
			if(preg_match($badDatePatternStr, $str, $locMatches)) {
				return $this->fixGapsInDMYDates($locMatches[1], $possibleNumbers, $possibleMonths).str_replace(' ', '', $locMatches[2]).
					$locMatches[3].$locMatches[4].$locMatches[5].str_replace(' ', '', $locMatches[6]).$locMatches[7];
			}
		}
		return $str;
	}

	private function replaceMistakenCharactersInMDYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$badDatePatternStr = "/(.*)\\b((?i)".$possibleMonths.")\\s([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0])[,.]?\\s((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})\\b(.*)/s";
			if(preg_match($badDatePatternStr, $str, $dateMatches)) {
				return $this->replaceMistakenCharactersInMDYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					ucfirst($this->replaceMistakenLetters($dateMatches[2]))." ".$this->replaceMistakenNumbers($dateMatches[3]).", ".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
		}
		return $str;
	}

//if it finds a date in the form DayMonthYear it fixes.
	private function replaceMistakenCharactersInDMYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$datePatternStr = "/(.*)\\b([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".
				$possibleNumbers."|3[1Iil!|OQ0\]])[- ]?((?i)".$possibleMonths.")[- ]?((?:[1Iil!|][789]|[zZ2][OQ0])".
				$possibleNumbers."{2}\\b)(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {
				return $this->replaceMistakenCharactersInDMYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					$this->replaceMistakenNumbers($dateMatches[2])." ".
					ucfirst($this->replaceMistakenLetters($dateMatches[3]))." ".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
		}
		return $str;
	}

//if it finds a date in the form MonthYear it fixes.
	private function replaceMistakenCharactersInMYDates($str, $possibleNumbers, $possibleMonths) {
		if($str) {
			$datePatternStr = "/(.*)\\b((?i)".$possibleMonths."),?\\s?((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2}\\b)(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {
				return $this->replaceMistakenCharactersInMYDates($dateMatches[1], $possibleNumbers, $possibleMonths).
					ucfirst($this->replaceMistakenLetters($dateMatches[2]))." ".
					$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
			}
		}
		return $str;
	}

	private function convertRomanNumeralNumsToMonths($str) {
		if($str) {
			return str_replace
			(
				array("IV", "VIII", "VII", "III", "XII", "II", "IX", "VI", "V", "XI", "I", "X"),
				array("April", "August", "July", "March", "December", "February", "September", "June", "May", "November", "January", "October"),
				$str
			);
		}
		return $str;
	}

//if it finds a numeric date it converts it to a normal month in the format DD Month YYYY.
	private function convertNumericDates($str) {
		if($str) {
			$letterMonths = array
			(
				1=>'January',
				2=>'February',
				3=>'March',
				4=>'April',
				5=>'May',
				6=>'June',
				7=>'July',
				8=>'August',
				9=>'September',
				10=>'October',
				11=>'November',
				12=>'December'
			);
			$possibleNumbers = "[0-9&OQIil!|ozZsS]";
			$datePatternStr = "/(.*?)\\b([OQ0]?+".$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[.-]\\s?".
				"(?:(?:([OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0\]])\\s?[.-])\\s?)".
				"((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\ndateMatches[".$i++."] = ".$dateMatche."\n";
				$mIndex = $this->removeLeadingZeros($this->replaceMistakenNumbers($dateMatches[2]));
				if($mIndex > 0 && $mIndex <= 12) {
					return $this->convertNumericDates($dateMatches[1]).
						$this->replaceMistakenNumbers($this->replaceMistakenNumbers($dateMatches[3]))." ".
						$letterMonths[$mIndex]." ".
						$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
				} else return $str;
			}
			$datePatternStr = "/(.*?)\\b(?:(?:([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0\]])\\s?[.-])\\s?)".
				"([OQ0]?".$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[.-]\\s?((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {
				$mIndex = $this->removeLeadingZeros($this->replaceMistakenNumbers($dateMatches[3]));
				if($mIndex > 0 && $mIndex <= 12) {
					return $this->convertNumericDates($dateMatches[1]).
						$this->replaceMistakenNumbers($this->replaceMistakenNumbers($dateMatches[2]))." ".
						$letterMonths[$mIndex]." ".
						$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
				} else return $str;
			}
			$datePatternStr = "/(.*?)\\b([OQ0]?".$possibleNumbers."|[Iil!|1][12Iil!|zZ])\\s?[.-]\\s?((?:[1Iil!|][789]|[zZ2][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {
				$firstPart = $dateMatches[1];
				if(preg_match("/\\d{1,2}:$/", trim($firstPart))) return $str;
				$mIndex = $this->removeLeadingZeros($this->replaceMistakenNumbers($dateMatches[2]));
				if($mIndex > 0 && $mIndex <= 12) {
					return $this->convertNumericDates($firstPart).
						$letterMonths[$mIndex]." ".
						$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
				} else return $str;
			}
		}
		return $str;
	}

//if it finds a numeric date with a Roman Numeral month it converts it to a normal month in the format DD-Month-YYYY.
	private function convertRomanNumeralDates($str) {
		if($str) {
			$possibleNumbers = "[0-9&OQIil!|ozZsS]";
			$datePatternStr = "/(.*?)\\b(?:([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0])\\.\\s?)".
				"([1lI ]{1,4}+|[1lI]\\s?[VX]|V\\s?[1lI]{0,3}+|X\\s?[1lI]{0,2}+)[.-]\\s?((?:[1Iil!|][789]|[zZ12][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 8031, dateMatches2[".$i++."] = ".$dateMatche."\n";
				return trim($this->convertRomanNumeralDates($dateMatches[1]))." ".
					$this->replaceMistakenNumbers(trim($dateMatches[2]))."-".
					$this->convertRomanNumeralNumsToMonths(str_replace(array("l", "1", " "), array("I", "I", ""), $dateMatches[3]))."-".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
			$datePatternStr = "/(.*?)\\b([1lI ]{1,4}+|[1lI]\\s?[VX]|V\\s?[1lI]{0,3}+|X\\s?[1lI]{0,2}+)[.-]\\s?((?:[1Iil!|][789]|[zZ12][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 8038, dateMatches[".$i++."] = ".$dateMatche."\n";
				$firstPart = trim($dateMatches[1]);
				if(preg_match("/\\d{1,2}:$/", trim($firstPart))) return $str;
				$revFirstPart = strrev($firstPart);
				if(strcmp(substr($revFirstPart, 0, 2), "/\\") == 0) {
					$firstPart = trim(substr($firstPart, 0, strlen($firstPart)-2));
					$pat2 = "/(.*?)\\b(?:([OQ0]?".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0])\\.\\s?)$/s";
					if(preg_match($pat2, $firstPart, $dateMatches2)) {
						$firstPart = trim($dateMatches2[1]);
						return trim($this->convertRomanNumeralDates($firstPart))." ".
							$this->replaceMistakenNumbers(trim($dateMatches2[2]))."-".
							$this->convertRomanNumeralNumsToMonths(str_replace(array("l", "1", " "), array("I", "I", ""), "V".$dateMatches[2]))."-".
							$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
					}
				}
				return trim($this->convertRomanNumeralDates($firstPart))." ".
					$this->convertRomanNumeralNumsToMonths(str_replace(array("l", "1", " "), array("I", "I", ""), $dateMatches[2]))."-".
					$this->replaceMistakenNumbers($dateMatches[3]).$dateMatches[4];
			}
		}
		return $str;
	}

	private function fixDates($str) {
		if($str) {
			$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))?|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
			$possibleNumbers = "[0-9&OQIil!|ozZsS]";
			$str = preg_replace_callback(
				'/(]an(?:\\.|(?:uary))?|]un[\\.|e]?|]u[l1|I!][\\.y]?\\s[0123Il!|oOQzZ]\\s?'.
				$possibleNumbers.',?\\s[12Il!|zZ]\\s?[7890OQo]'.$possibleNumbers.'\\s?'.$possibleNumbers.')\\b/',
				create_function('$matches','return str_replace("]", "J", $matches[1]);'),
				$str
			);
			$result = $this->fixGapsInDMYDates($str, $possibleNumbers, $possibleMonths);
			$result = $this->fixGapsInMDYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->replaceMistakenCharactersInDMYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->replaceMistakenCharactersInMYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->replaceMistakenCharactersInMDYDates($result, $possibleNumbers, $possibleMonths);
			$result = $this->convertRomanNumeralDates($result);
			$result = $this->convertNumericDates($result);
			return $result;
		}
		return $str;
	}

//fix latLongs in the form "34Ol7'N, ll7O56'W".  It won't necessarily fix latLongs containing Os misplaced with 0s.  But will fix others
	private function replaceMissingDegreeSignsInLatLongs($str) {
		if($str) {
			$badLatLongPatStr = "/(.*)\\b((?(?=°)(?:[SZl|IO!\\d]{2,3})|(?:[SZl|I!\\d]{2,3})))([0OQ°])((?(?<=°)(?:[SZl|IO!\\d]{2,3})|(?:[SZl|I!\\d]{2,3}))\\s?'\\s?)".
				"([OQSZl|I!\\d]{1,3}\\s?\")?\\s?([NS])'?(\\.?\\s?Lat(?:\\.|[i1!|]tude)?)?:?,?(?:\\s|\\n|\\r\\n)".
				"((?(?=°)(?:[SZl|IO!\\d]{2,3})|(?:[SZl|I!\\d]{2,3})))([0OQ°])((?(?<=°)(?:[SZl|IO!\\d]{2,3})|(?:[SZl|I!\\d]{2,3}))\\s?'\\s?)\\s?([OQSZl|I!\\d]{1,3}".
				"\\s?[\"\'])?\\s?((?:E|W|VV))\\b(.*)/is";
			if(preg_match($badLatLongPatStr, $str, $latLongMatchesBad)) {//$i=0;foreach($latLongMatchesBad as $latLongMatchesBa) echo "\nlatLongMatchesBad[".$i++."] = ".$latLongMatchesBa."\n";
				$str = $this->replaceMissingDegreeSignsInLatLongs($latLongMatchesBad[1]).
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[2]).
					str_ireplace(array("O", "Q", "0"), "°", $latLongMatchesBad[3]).
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[4]).
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "'"), array("5", "2", "1", "1", "1", "1", "\""), $latLongMatchesBad[5]).
					$latLongMatchesBad[6].str_ireplace(array("|", "!", "'"), array("i", "i", "\""), $latLongMatchesBad[7]).", ".
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[8]).
					str_ireplace(array("O", "Q", "0"), "°",$latLongMatchesBad[9]).
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "O"), array("5", "2", "1", "1", "1", "1", "0"), $latLongMatchesBad[10]).
					str_ireplace(array("S", "Z", "l", "|", "I", "!", "'"), array("5", "2", "1", "1", "1", "1", "\""), $latLongMatchesBad[11]).
					str_ireplace("VV", "W", $latLongMatchesBad[12]).$latLongMatchesBad[13];
			}
		}
		return $str;
	}

	private function getCountry($c) {
		if($c) {
			if($this->isCountry($c))  return $c;
			else {
				$c2 = str_replace("Q", "O", $c);
				if($this->isCountry($c2)) return $c2;
				else {
					$c3 = str_replace("U", "O", $c);
					if($this->isCountry($c3)) return $c3;
					else  {
						$c4 = str_replace("l", "I", $c);
						if($this->isCountry($c4)) return $c4;
					}
				}
			}
		}
		return '';
	}

	private function isCountry($country) {
		if($country != null) {
			$countryPatStr = '/(Canada|CA\\.?|Can\\.?|Un[il!|1]ted States(?: [o0]f America)?|U\\.?S\\.?(?:A\\.?)?|Mexic[o0]|MX\\.?|Mex\\.?|".
				"S[o0]UTH AFR[il!|1]CA|S[o0]\\.? AFR[il!|1]CA|C[o0]sta\\sR[il!|1]ca|N[o0]rway|[S5]w[ec]d[ec]n|F[il!|1]n[il!|1]and|Braz[il!|1]{2}|Japan)/i';
			//$countryPatStr = '/\\b(Canada|CA\.?|Can\.?|United States(?: of America)?|U\.?S\.?(?:A\.?)?|Mexico|MX\.?|Mex\.?|S[o0](?:\.[o0]UTH)? AFRICA)\\b/i';
			$m = preg_match($countryPatStr, $country, $matches);
			//if($m) echo "matches[1] ".$matches[1]."\n";
			return ($m && $country == $matches[1]);
		}
		return false;
	}

	private function getStateOrProvince($d) {//echo "\nInput to getStateOrProvince: ".$d."\n";
		if($d != null) {
			$d = trim($d, " ,:;");
			$pos = stripos($d, " State");
			if($pos !== FALSE) $d = trim(substr($d, 0, $pos));
			$countries = array
			(
				'Mexico'=>array
				(
					'Aguascalientes'=>'/^(?:Aguascal[il!|1][ec]nt[ec]s|AG\\.?)$/i',
					'Baja California'=>'/^(?:Baja\\sCal[il!|1]forn[il!|1]a)$/i',
					'Baja California Sur'=>'/^(?:Baja Cal[il!|1]forn[il!|1]a Sur|B\\.?[S5]\\.?)$/i',
					'Campeche'=>'/^(?:Camp[ec]{2}h[ec]|CM\\.?)$/i',
					'Chiapas'=>'/^(?:Ch[il!|1]apas|CS\\.?)$/i',
					'Chihuahua'=>'/^(?:Ch[il!|1]huahua|CH\\.?)$/i',
					'Coahuila'=>'/^(?:Coahu[il!|1]{2}a)$/i',
					'Colima'=>'/^(?:Co[il!|1]{2}ma|CL\\.?)$/i',
					'Durango'=>'/^(?:Durang[o0uq]|DG\\.?)$/i',
					'Guanajuato'=>'/^(?:Guanajuat[o0uq]|GT\\.?)$/i',
					'Guerrero'=>'/^(?:Gu[ec]rr[ec]r[o0uq]|DG\\.?)$/i',
					'Hidalgo'=>'/^(?:H[il!|1]dalg[o0uq]|HG\\.?)$/i',
					'Jalisco'=>'/^(?:Ja[il!|1]{2}sc[o0uq]|JA\\.?)$/i',
					'México'=>'/^(?:Méxic[o0uq])$/i',
					'Michoacán'=>'/^(?:M[il!|1]ch[o0uq]acán|M[il!|1]ch[o0uq]acan)$/i',
					'Morelos'=>'/^(?:Morelo[o0uq]s)$/i',
					'Nayarit'=>'/^(?:Nayar[il!|1]t|NA\\.?)$/i',
					'Nuevo León'=>'/^(?:Nu[ec]v[o0uq]\\sL[ec]ón|Nu[ec]v[o0uq] L[ec][o0uq]n|N\\.?L\\.?)$/i',
					'Oaxaca'=>'/^(?:[o0uq]axaca|[o0uq]A\\.?)$/i',
					'Puebla'=>'/^(?:Pu[ec]bla|PB\\.?)$/i',
					'Querétaro'=>'/^(?:Qu[ec]rétar[o0uq]|Qu[ec]r[ec]tar[o0uq])$/i',
					'Quintana Roo'=>'/^(?:Qu[il!|1]ntana\\sR[o0uq]{2}|QR\\.?)$/i',
					'San Luis Potosí'=>'/^(?:San\\sLu[il!|1]s\\sPotosí|San Lu[il!|1]s Potos[il!|1]|S\\.?L\\.?)$/i',
					'Sinaloa'=>'/^(?:S[il!|1]nal[o0uq]a|SI\\.?)$/i',
					'Tabasco'=>'/^(?:Tabas[ec][o0uq]|TB\\.?)$/i',
					'Tamaulipas'=>'/^(?:Tamaul[il!|1]pa[s5]|TM\\.?)$/i',
					'Tlaxcala'=>'/^(?:T[il!|1]ax[ec]ala|TL\\.?)$/i',
					'Veracruz'=>'/^(?:V[ec]ra[ec]ruz|VE\\.?)$/i',
					'Yucatán'=>'/^(?:Yu[ec]atán|Yu[ec]atan|YU\\.?)$/i',
					'Zacatecas'=>'/^(?:Za[ec]at[ec]{2}as|ZA\\.?)$/i'
				),
				'USA'=>array
				(
					'Alabama'=>'/^(?:A[il!|1]abama|A[il!|1]\\.?|A[il!|1]a\\.?)$/i',
					'Alaska'=>'/^(?:A[il!|1]aska|AK\\.?)$/i',
					'Arkansas'=>'/^(?:Arkan[s5]a[s5]|AR\\.?|Ark\\.?)$/i',
					'California'=>'/^(?:Ca[il!|1]{2}f[o0uq]rn[il!|1]a|CA\\.|Cal[il!|1]f\\.?)$/i',
					'Colorado'=>'/^(?:C[o0uq][il!|1][o0uq]rad[o0uq]|C[o0uq]\\.|C[o0uq][il!|1][o0uq]\\.?)$/i',
					'Connecticut'=>'/^(?:C[o0uq]nn[ec]{2}t[il!|1]cut|CT\\.?|C[o0uq]nn\\.?)$/i',
					'Delaware'=>'/^(?:D[ec]lawar[ec]|DE\\.?|Del\\.?)$/i',
					'DC'=>'/^(?:D[il!|1][s5]tr[il!|1]ct [o0uq]f C[o0uq][il!|1]umb[il!|1]a|D\\.?C\\.?)$/i',
					'Florida'=>'/^(?:F[il!|1][o0uq]r[il!|1]da|F[il!|1]\\.?|F[il!|1]a\\.?)$/i',
					'Georgia'=>'/^(?:G[ec][o0uq]rg[il!|1]a|GA\\.?)$/i',
					'Hawaii'=>'/^(?:Hawa[il!|1]{2}|H[il!|1]\\.?)$/i',
					'Idaho'=>'/^(?:[il!|1]dah[o0uq]|[il!|1]D\\.?)$/i',
					'Illinois'=>'/^(?:[il!|1]{4}n[o0uq][il!|1]s|[il!|1]{3}?\\.)$/i',
					'Indiana'=>'/^(?:[il!|1]nd[il!|1]ana|[il!|1]N\\.|[il!|1]nd\\.?)$/i',
					'Iowa'=>'/^(?:[il!|1][o0uq]wa|IA\\.)$/i',
					'Kansas'=>'/^(?:Kansas|KS\\.?|Kans\\.?)$/i',
					'Kentucky'=>'/^(?:K[ec]ntucky|KY\\.?)$/i',
					'Louisiana'=>'/^(?:[il!|1][o0uq]u[il!|1]s[il!|1]ana|LA\\.?)$/i',
					'Maine'=>'/^(?:Ma[il!|1]ne|ME\\.?)$/i',
					'Maryland'=>'/^(?:Mary[il!|1]and|MD\\.?)$/i',
					'Massachusetts'=>'/^(?:Ma[s5]{2}achu[s5][ec]tt[s5]|MA\\.?|Ma[s5]{2}\\.?)$/i',
					'Michigan'=>'/^(?:M[il!|1][ec]h[il!|1]gan|M[il!|1]\\.?|M[il!|1][ec]h\\.?)$/i',
					'Minnesota'=>'/^(?:M[il!|1]nn[ec]s[o0uq]ta|MN\\.?|M[il!|1]nn\\.?)$/i',
					'Mississippi'=>'/^(?:M[il!|1][s5]{2}[il!|1][s5]{2}[il!|1]pp[il!|1]|M[s5]\\.?|M[il!|1][s5]{2}\\.?)$/i',
					'Missouri'=>'/^(?:M[il!|1][s5]{2}[o0uq]ur[il!|1]|M[o0uq]\\.?)$/i',
					'Montana'=>'/^(?:M[o0uq]ntana|MT\\.?|M[o0uq]nt\\.?)$/i',
					'Nebraska'=>'/^(?:N[ec]braska|N[ec]\\.?|N[ec]br\\.?)$/i',
					'Nevada'=>'/^(?:N[ec]vada|NV\\.?|N[ec]v\\.?)$/i',
					'New Hampshire'=>'/^(?:N[ec]w\\sHampsh[il!|1]r[ec]|N\\.?H\\.?)$/i',
					'New Jersey'=>'/^(?:N[ec]w\\sJ[ec]rs[ec]y|N\\.?J\\.?)$/i',
					'New Mexico'=>'/^(?:N[ec]w\\sM[ec]x[il!|1][ec][o0uq]|NM\\.?|N\\.? M[ec]x\\.?)$/i',
					'New York'=>'/^(?:N[ec]w\\sY[o0uq]rk|N\\.?Y\\.?)$/i',
					'North Carolina'=>'/^(?:N[o0uq]rth\\sCar[o0uq][il!|1]{2}na|N\\.?C\\.?)$/i',
					'North Dakota'=>'/^(?:N[o0uq]rth\\sDak[o0uq]ta|N\\.?D\\.?|N\\.? Dak\\.?)$/i',
					'Ohio'=>'/^(?:[o0uq]h[il!|1][o0uq]|[o0uq]H\\.?)$/i',
					'Oklahoma'=>'/^(?:[o0uq]k[il!|1]ah[o0uq]ma|[o0uq]K\\.|[o0uq]k[il!|1]a\\.?)$/i',
					'Oregon'=>'/^(?:[o0uq]r[ec]g[o0uq]n|[o0uq]R\\.|[o0uq]r[ec]g\\.?)$/i',
					'Pennsylvania'=>'/^(?:P[ec]nnsy[il!|1]van[il!|1]a|PA\\.?)$/i',
					'Rhode Island'=>'/^(?:Rh[o0uq]de\\s[il!|1]s[il!|1]and|R\\.?I\\.?)$/i',
					'South Carolina'=>'/^(?:S[o0uq]uth\\sCar[o0uq][il!|1]{2}na|S\\.?C\\.?)$/i',
					'South Dakota'=>'/^(?:S[o0uq]uth\\sDak[o0uq]ta|S\\.?D\\.?|S\\.?\\sDak\\.?)$/i',
					'Tennessee'=>'/^(?:T[ec]nn[ec]ss[ec]{2}|TN\\.?|T[ec]nn\\.?)$/i',
					'Texas'=>'/^(?:T[ec]xas|TX\\.?|T[ec]x\\.?)$/i',
					'Utah'=>'/^(?:Utah|UT\\.?)$/i',
					'Vermont'=>'/^(?:V[ec]rm[o0uq]nt|VT\\.?)$/i',
					'Virginia'=>'/^(?:V[il!|1]rg[il!|1]n[il!|1]a|VA\\.?)$/i',
					'Washington'=>'/^(?:Wash[il!|1]ngt[o0uq]n|WA\\.?|Wash\\.?)$/i',
					'West Virginia'=>'/^(?:West V[il!|1]rg[il!|1]n[il!|1]a|W\\.? Va?\\.?)$/i',
					'Wisconsin'=>'/^(?:W[il!|1]s[ec][o0uq]ns[il!|1]n|W[il!|1]s?\\.?)$/i',
					'Wyoming'=>'/^(?:W[gy][o0uq]m[il!|1]n[gy]|W[gy][o0uq]?\\.?)$/i'
				),
				'Canada'=>array
				(
					'Ontario'=>'/^(?:[o0uq]ntari[o0uq]|[o0uq]nt\\.?|[o0uq]N\\.)$/i',
					'Quebec'=>'/^(?:[o0uq]u[ec]b[ec]{2}|[o0uq][ec]\\.?)$/i',
					'Nova Scotia'=>'/^(?:N[o0uq]va\\s[S5]c[o0uq]t[il!|1]a|N\\.?[S5]\\.?)$/i',
					'New Brunswick'=>'/^(?:N[ec]w\\sBrunsw[il!|1][ec]k|N\\.?B\\.?)$/i',
					'Manitoba'=>'/^(?:Man[il!|1]t[o0uq]ba|MB\\.?)$/i',
					'British Columbia'=>'/^(?:Br[il!|1]t[il!|1][S5]h\\sC[o0uq]lumb[il!|1]a|B\\.?C\\.?)$/i',
					'Prince Edward Island'=>'/^(?:Pr[il!|1]n[ec]{2}\\s[ec]dward\\s[il!|1]sland|P\\.?E\\.?I\\.?|P\\.?E\\.?)$/i',
					'Saskatchewan'=>'/^(?:[S5]a[S5]kat[ec]h[ec]wan|[S5]a[S5]k\\.?|[S5]K\\.?)$/i',
					'Alberta'=>'/^(?:A[il!|1]b[ec]rta|A[il!|1]b\\.?|AB\\.?)$/i',
					'Northwest Territories'=>'/^(?:N[o0uq]rthw[ec]st T[ec]rr[il!|1]t[o0uq]r[il!|1]es|N\\.?W\\.?T\\.?)$/i',
					'Yukon'=>'/^(?:Yuk[o0uq]n|YT\\.?)$/i',
					'Nunavut'=>'/^(?:Nunavut|NU\\.?)$/i',
					'Newfoundland and Labrador'=>'/^(?:N[ec]wf[o0uq]undland and Labrad[o0uq]r|N[ec]wf[o0uq]undland|Labrad[o0uq]r|N\\.?L\\.?)$/i'
				)
			);

			foreach($countries as $country=>$divisions) {
				foreach($divisions as $name=>$pat) if(preg_match($pat, $d)) return array($name, $country);
			}
			$tDivisions = explode(" ", $d);
			$prevDivision = "";
			$beforePrevDivision = "";
			$i=0;
			foreach($tDivisions as $tDivision) {
				$tDivision = trim($tDivision, " ;:,");
				if(strlen($tDivision) > 3) $tDivision = trim($tDivision, ".");
				foreach($countries as $country=>$divisions) {
					foreach($divisions as $name=>$pat) {
						if(strlen($prevDivision) > 0 && $i < 3) {
							$t2Division = $prevDivision." ".$tDivision;
							if(strlen($beforePrevDivision) > 0) {
								$t3Division = $beforePrevDivision." ".$t2Division;
								//echo "\nt3Division: ".$t3Division."\nPattern: ".$pat."\n";
								if(preg_match($pat, $t3Division)) return array($name, $country);
							}
							//echo "\nt2Division: ".$t2Division."\nPattern: ".$pat."\n";
							if(preg_match($pat, $t2Division)) return array($name, $country);
						}
						//echo "\ntDivision: ".$tDivision."\nPattern: ".$pat."\n";
						if(preg_match($pat, $tDivision)) return array($name, $country);
					}
				}
				$i++;
				$beforePrevDivision = $prevDivision;
				$prevDivision = $tDivision;
			}
		}
		return array();
	}

	private function isUSState($state) {
		if($state != null) {//echo "\nInput State: ".$state."\n";
			$state = trim($state, " ,");
			$statePatStr = '/\\b(A[il!|1]abama|A[il!|1]\\.?|A[il!|1]a\\.?|'.
			'A[il!|1]aska|AK\\.?|'.
			'Ar[il!|1]z[o0uq]na|AZ\\.?|Ar[il!|1]z\\.?|'.
			'Arkansas|AR\\.?|Ark\\.?|'.
			'Ca[il!|1]{2}f[o0uq]rn[il!|1]a|CA\\.?|Cal[il!|1]f\\.?|'.
			'C[o0uq][il!|1][o0uq]rad[o0uq]|C[o0uq]\\.?|C[o0uq][il!|1][o0uq]\\.?|'.
			'C[o0uq]nnect[il!|1]cut|CT\\.?|C[o0uq]nn\\.?|'.
			'Delaware|DE\\.?|Del\\.?|'.
			'D[il!|1]str[il!|1]ct [o0uq]f C[o0uq]lumbia|D\\.?C\\.?|'.
			'F[il!|1][o0uq]r[il!|1]da|F[il!|1]\\.?|F[il!|1]a\\.?|'.
			'Ge[o0uq]rg[il!|1]a|GA\\.?|'.
			'Hawa[il!|1]{2}|H[il!|1]\\.?|'.
			'[il!|1]dah[o0uq]|[il!|1]D\\.?|'.
			'[il!|1]{4}n[o0uq][il!|1]s|[il!|1]{3}?\\.?|'.
			'[il!|1]nd[il!|1]ana|[il!|1]N\\.?|[il!|1]nd\\.?|'.
			'[il!|1][o0uq]wa|[il!|1]A\\.?|'.
			'Kansas|KS\\.?|Kans\\.?|'.
			'Kentucky|KY\\.?|'.
			'[il!|1][o0uq]u[il!|1]s[il!|1]ana|[il!|1]A\\.?|'.
			'Ma[il!|1]ne|ME\\.?|'.
			'Mary[il!|1]and|MD\\.?|'.
			'Massachusetts|MA\\.?|Mass\\.?|'.
			'M[il!|1]ch[il!|1]gan|M[il!|1]\\.?|M[il!|1]ch\\.?|'.
			'M[il!|1]nnes[o0uq]ta|MN\\.?|M[il!|1]nn\\.?|'.
			'M[il!|1]ss[il!|1]ss[il!|1]pp[il!|1]|MS\\.?|M[il!|1]ss\\.?|'.
			'M[il!|1]ss[o0uq]ur[il!|1]|M[o0uq]\\.?|'.
			'M[o0uq]ntana|MT\\.?|M[o0uq]nt\\.?|'.
			'Nebraska|NE\\.?|Nebr\\.?|'.
			'Nevada|NV\\.?|Nev\\.?|'.
			'New Hampsh[il!|1]re|N\\.?H\\.?|'.
			'New Jersey|N\\.?J\\.?|'.
			'New Mex[il!|1]c[o0uq]|NM\\.?|N\\.? Mex\\.?|'.
			'New Y[o0uq]rk|N\\.?Y\\.?|'.
			'N[o0uq]rth Car[o0uq][il!|1]{2}na|N\\.?C\\.?|'.
			'N[o0uq]rth Dak[o0uq]ta|N\\.?D\\.?|N\\.? Dak\\.?|'.
			'[o0uq]h[il!|1][o0uq]|[o0uq]H\\.?|'.
			'[o0uq]klah[o0uq]ma|[o0uq]K\\.?|[o0uq]kla\\.?|'.
			'[o0uq]reg[o0uq]n|[o0uq]R\\.?|[o0uq]reg\\.?|'.
			'Pennsylvan[il!|1]a|PA\\.?|'.
			'Rh[o0uq]de [il!|1]s[il!|1]and|R\\.?I\\.?|'.
			'S[o0uq]uth Car[o0uq]l[il!|1]na|S\\.?C\\.?|'.
			'S[o0uq]uth Dak[o0uq]ta|S\\.?D\\.?|S\\.? Dak\\.?|'.
			'Tennessee|TN\\.?|Tenn\\.?|'.
			'Texas|TX\\.?|Tex\\.?|'.
			'Utah|UT\\.?|'.
			'Verm[o0uq]nt|VT\\.?|'.
			'V[il!|1]rg[il!|1]n[il!|1]a|VA\\.?|'.
			'Wash[il!|1]ngt[o0uq]n|WA\\.?|Wash\\.?|'.
			'West V[il!|1]rg[il!|1]n[il!|1]a|W\\.? Va?\\.?|'.
			'W[il!|1]sc[o0uq]ns[il!|1]n|W[il!|1]s?\\.?|'.
			'Wy[o0uq]ming|Wy[o0uq]?\\.?'.
			')\\b/i';
			$m = preg_match($statePatStr, $state, $matches);
			if($m) {
				//echo "matches[1] ".$matches[1]."\n";
				$thisState = $matches[1];
				if($state == $thisState) return true;
				/*else {
					if(strpos($state, " ") > 1) {
						$tStates = explode(" ", $state);
						foreach ($tStates as $tState) if($tState == $thisState) return true;
					}
				}*/
			}
			return ($m && ($state == $matches[1] || $state == $matches[1]."."));
		}
		return false;
	}

	private function replaceMistakenLetters($word) {
		$retStr = str_replace(array("1", "|", "!", "0", "5", "]"), array("l", "l", "l", "O", "S", "J"), $word);
		$patStr = "/(Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Sep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|Oct(?:\\.|(?:ob\\w{1,2}))?|Nov(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)/";
		if(preg_match($patStr, $retStr, $matches)) {
			$match = $matches[1];
			if(substr_compare($match, "Jan", 0, 3, TRUE) == 0) return "January";
			else if(substr_compare($match, "Feb", 0, 3, TRUE) == 0) return "February";
			else if(substr_compare($match, "Sep", 0, 3, TRUE) == 0) return "September";
			else if(substr_compare($match, "Oct", 0, 3, TRUE) == 0) return "October";
			else if(substr_compare($match, "Nov", 0, 3, TRUE) == 0) return "November";
			else if(substr_compare($match, "Dec", 0, 3, TRUE) == 0) return "December";
		}
		return $retStr;
	}

	private function replaceMistakenNumbers($num) {
		return str_replace(array("l", "I", "|", "!", "i", "O", "Q", "o", "s", "S", "Z", "z", "&", "U", "[", "]"), array("1", "1", "1", "1", "1", "0", "0", "0", "5", "5", "2", "2", "6", "4", "1", "1"), $num);
	}

	private function getDates($str, $possibleMonths) {
		if($str) {
			return array_merge($this->getDMYDates($str, $possibleMonths), $this->getMDYDates($str, $possibleMonths), $this->getNumericDates($str));
		}
		return array();
	}

	private function getVerbatimCoordinates($str) {
		if($str) {
			$result = "";
			$latLongs = $this->getLatLongs($str);
			$index = 0;
			if(count($latLongs) > 0) {
				foreach($latLongs as $latLong) {
					if($index++ == 0) $result .= $latLong["latitude"].", ".$latLong["longitude"];
					else $result .= "; ".$latLong["latitude"].", ".$latLong["longitude"];
				}
			}
			$utms = $this->getUTMCoordinates($str);
			if(count($utms) > 0) {
				foreach($utms as $utm) {
					if($index++ == 0) $result .= $utm;
					else $result .= "; ".$utm;
				}
			}
			$trss = $this->getTRSCoordinates($str);
			if(count($trss) > 0) {
				foreach($trss as $trs) {
					if($index++ == 0) $result .= $trs;
					else $result .= "; ".$trs;
				}
			}
			return $result;
		}
		return "";
	}

	private function removeLeadingZeros($str)
	{
		while(strlen($str) > 0 && substr($str, 0, 1) == "0") $str = substr($str, 1);
		return $str;
	}
}
?>
