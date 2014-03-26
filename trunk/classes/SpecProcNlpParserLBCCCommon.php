<?php

class SpecProcNlpParserLBCCCommon extends SpecProcNlp {

	protected $conn;

	function __construct($catalogNumber="") {
		$this->catalogNumber = $catalogNumber;
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	protected function parse($rawStr) {
		$rawStr = trim($this->fixString(str_replace("\t", " ", $rawStr)));
		//If OCR source is from tesseract (utf-8 is default), convert to a latin1 character set
		//if(mb_detect_encoding($rawStr,'UTF-8,ISO-8859-1') == "UTF-8"){
		//	$rawStr = utf8_decode($rawStr);
		//}
		if(strlen($rawStr) > 0 && !$this->isMostlyGarbage2($rawStr, 0.50)) {
			$results = array();
			$labelInfo = $this->getLabelInfo($rawStr, $this->collId);
			if($labelInfo) {
				$recordedBy = "";
				$recordedById = "";
				$recordNumber = "";
				$otherCatalogNumbers = '';
				$identifiedBy = '';
				$associatedCollectors = '';
				if(array_key_exists('recordedBy', $labelInfo)) $recordedBy = $labelInfo['recordedBy'];
				if(array_key_exists('recordedById', $labelInfo)) $recordedById = $labelInfo['recordedById'];
				if(array_key_exists('recordNumber', $labelInfo)) $recordNumber = $labelInfo['recordNumber'];
				if(array_key_exists('otherCatalogNumbers', $labelInfo)) $otherCatalogNumbers = $labelInfo['otherCatalogNumbers'];
				if(array_key_exists('associatedCollectors', $labelInfo)) $associatedCollectors = $labelInfo['associatedCollectors'];
				if(array_key_exists('identifiedBy', $labelInfo)) $identifiedBy = $labelInfo['identifiedBy'];
				//echo "\nline 36, recordedBy: ".$recordedBy."\nrecordNumber: ".$recordNumber."\nidentifiedBy: ".$identifiedBy."\notherCatalogNumbers: ".$otherCatalogNumbers."\n";
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
				$results['otherCatalogNumbers'] = $otherCatalogNumbers;
				$results['associatedCollectors'] = $associatedCollectors;
				$results['recordedBy'] = $recordedBy;
				$results['recordedById'] = $recordedById;
				$results['recordNumber'] = $recordNumber;
				$event_date = "";
				$det_date = "";
				if(array_key_exists('eventDate', $labelInfo)) {
					$t = $labelInfo['eventDate'];
					if(is_array($t)) if(count($t) > 0) $event_date = $this->formatDate($t);
					unset($labelInfo['eventDate']);
				}
				if(array_key_exists('dateIdentified', $labelInfo)) {
					$t = $labelInfo['dateIdentified'];
					if(is_array($t)) if(count($t) > 0) $det_date = $this->formatDate($t);
					unset($labelInfo['dateIdentified']);
				}
				$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
				if(strlen($identifiedBy) == 0) {
					$identifier = $this->getIdentifier($rawStr, $possibleMonths);
					if($identifier != null) {
						$identifiedBy = $identifier[0];
						if(strlen($det_date) == 0) $det_date = $this->formatDate($identifier[1]);
					}
				}
				if(strlen($event_date) == 0 || strlen($det_date) == 0) {
					$dates = $this->getDates($rawStr, $possibleMonths);
					if(count($dates) > 0) {
						if(strlen($event_date) == 0) $event_date = $this->formatDate($this->min_date($dates));
						if(strlen($det_date) == 0 && count($dates) > 1) $det_date = $this->formatDate($this->max_date($dates));
					}
				}
				$results['identifiedBy'] = $identifiedBy;
				$results['eventDate'] = $event_date;
				$results['dateIdentified'] = $det_date;
				$verbatimCoordinates = "";
				if(array_key_exists('verbatimCoordinates', $labelInfo)) $verbatimCoordinates = $labelInfo['verbatimCoordinates'];
				else $verbatimCoordinates = $this->getVerbatimCoordinates($rawStr);
				$results['verbatimCoordinates'] = $verbatimCoordinates;
				$latLongs = $this->getLatLongs($verbatimCoordinates);
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
				if(array_key_exists('ometid', $labelInfo)) {
					$exsTitleAndAbbr = $this->getExsiccatiTitleAndAbbreviation($labelInfo['ometid']);
					if($exsTitleAndAbbr) $results['exstitle'] = $exsTitleAndAbbr;
				}
			}
			//return $this->combineArrays($labelInfo, $results);
			return $this->combineArrays($results, $labelInfo);
		}
		return array();
	}

	private function getLabelInfo($str) {
		if($str) return $this->doGenericLabel($str);
		return array();
	}

	private function getExsiccatiTitleAndAbbreviation($ometid) {
		if($ometid) {
			$sql = "SELECT CONCAT(title, ' [', abbreviation, ']') titleAndAbbr from omexsiccatititles where ometid = ".$ometid;
			if($rs = $this->conn->query($sql)) {
				if($r = $rs->fetch_object()) return $r->titleAndAbbr;
			}
		}
	}

	protected function analyzeLocalityLine($line, $acceptableSmallWords="(?:road|pine|park|tree|fork|cove|camp|fire|bay|log|soil|sand|oaks?|base)") {//countPotentialLocalityWords > 0 for the line and it doesn't begin with Fairly common on, etc
		$firstPart = "";
		$lastPart = "";
		if(preg_match("/^(.+?);;; (.+)$/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 12166, mats[".$i++."] = ".$mat."\n";
			$firstPart = trim($mats[1]);
			$lastPart = trim($mats[2]);
		} else if(preg_match("/^(.*[A-Za-z]{4,}\)?); (.+)$/", $line, $mats)) {
			$firstPart = trim($mats[1]);
			$lastPart = trim($mats[2]);
		} else if(preg_match("/^(.+?); (.+)$/", $line, $mats)) {
			$firstPart = trim($mats[1]);
			$lastPart = trim($mats[2]);
		} else if(!preg_match("/^.* Sects\\. .+$/", $line) && preg_match("/^(.*[A-Za-z]{5,}\)?)\\. (.+)$/", $line, $mats)) {
			$firstPart = trim($mats[1]);
			$lastPart = trim($mats[2]);
		} else if(preg_match("/^(.* ".$acceptableSmallWords."\)?)\\. (.+)$/i", $line, $mats)) {
			$firstPart = trim($mats[1]);
			$lastPart = trim($mats[2]);
		} else if(preg_match("/^(.* [A-Za-z]{4,}+\)?), (.+?)$/", $line, $mats)) {
			$firstPart = trim($mats[1]);
			$lastPart = trim($mats[2]);
		} else if(preg_match("/^(.*[A-Za-z]{4,}\)?): (.+)$/", $line, $mats)) {
			$firstPart = trim($mats[1]);
			$lastPart = trim($mats[2]);
		}//echo "\nline 13061, line: ".$line."\nfirstPart: ".$firstPart."\nlastPart: ".$lastPart."\ncountPotentialLocalityWords(".$firstPart."): ".$this->countPotentialLocalityWords($firstPart)."\ncountPotentialHabitatWords(".$firstPart."): ".$this->countPotentialHabitatWords($firstPart)."\ncountPotentialLocalityWords(".$lastPart."): ".$this->countPotentialLocalityWords($lastPart)."\ncountPotentialHabitatWords(".$lastPart."): ".$this->countPotentialHabitatWords($lastPart)."\n";
		if(strlen($firstPart) > 0 && strlen($lastPart) > 0) {
			if(strlen($lastPart) < 4 && preg_match("/(.*[A-Za-z]{4,})[,;] (.+)/", $firstPart, $mats2)) {
				$firstPart = trim($mats2[1]);
				$lastPart = trim($mats2[2]);
			}
			$hCount1 = $this->countPotentialHabitatWords($firstPart);
			$lCount1 = $this->countPotentialLocalityWords($firstPart);
			$hCount2 = $this->countPotentialHabitatWords($lastPart);
			$lCount2 = $this->countPotentialLocalityWords($lastPart);
			if($lCount1 == 0) {
				if($hCount1 > 0) return array('locality' => $lastPart, 'habitat' => $firstPart);
			}
			if($lCount2 == 0) {
				if(preg_match("/^((?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant)|Found|Loose) On .+)/i", $lastPart)) return array('locality' => $firstPart, 'substrate' => $lastPart);
				if(preg_match("/^(.+)\\. {1,2}((?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant) |Found |Loose )?On .+)/i", $lastPart, $mats)) {
					$startOfLastPart = trim($mats[1]);
					if($this->countPotentialHabitatWords($startOfLastPart) > 0) return array('locality' => $firstPart, 'habitat' => $startOfLastPart, 'substrate' => trim($mats[2]));
					else return array('locality' => $firstPart.". ".$startOfLastPart, 'substrate' => trim($mats[2]));
				}
				if($hCount2 > 0) {
					if(strcasecmp(substr($lastPart, 0, 3), "On ") == 0) return array('locality' => $firstPart, 'substrate' => $lastPart);
					return array('locality' => $firstPart, 'habitat' => $lastPart);
				}
			}
			if($hCount1 == 0 && $hCount2 > $lCount1) {
				return array('locality' => $firstPart, 'habitat' => $lastPart);
			}
			if(preg_match("/([(A-Za-z ,-]+)\)?[,.]? ((?:along|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under) .+)/i", $firstPart, $mats2)) {//foreach($mats as $k=>$v) echo "\nline 14084, ".$k.": ".$v."\n";
				$startOfFirstPart = trim($mats2[1]);
				if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $this->countPotentialHabitatWords($startOfFirstPart) > 0) {
					return array('locality' => trim($mats2[2]).", ".$lastPart, 'habitat' => $startOfFirstPart);
				}
			}
			$endOfLastPart = "";
			$startOfLastPart = "";
			if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			} else if(preg_match("/(.+ ".$acceptableSmallWords."\)?)\\. (.+)/i", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			}
			if(strlen($startOfLastPart) > 0 && strlen($endOfLastPart) > 0) {
				if($this->countPotentialLocalityWords($endOfLastPart) == 0 && $this->countPotentialHabitatWords($endOfLastPart) > 0) {
					return array('locality' => $firstPart.". ".$startOfLastPart, 'habitat' => $endOfLastPart);
				}
				$startOfFirstPart = "";
				$endOfFirstPart = "";
				if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				} else if(preg_match("/(.+ ".$acceptableSmallWords."\)?)\\. (.+)/i", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				}
				if(strlen($startOfFirstPart) > 0 && strlen($endOfFirstPart) > 0) {
					if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $this->countPotentialHabitatWords($startOfFirstPart) > 0) {
						return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
					}
				}
			}
			$startOfFirstPart = "";
			$endOfFirstPart = "";
			if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			} else if(preg_match("/(.+ ".$acceptableSmallWords."\)?)\\. (.+)/i", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			}
			if(strlen($startOfFirstPart) > 0 && strlen($endOfFirstPart) > 0) {
				if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $this->countPotentialHabitatWords($startOfFirstPart) > 0) {
					return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
				}
			}
			$endOfLastPart = "";
			$startOfLastPart = "";
			if(preg_match("/(.*[A-Za-z]{3,}\)?), (.+)/", $lastPart, $mats2)) {
				$endOfLastPart = trim($mats2[2]);
				$startOfLastPart = trim($mats2[1]);
			}//echo "\nline 13091, startOfLastPart: ".$startOfLastPart."\nendOfLastPart: ".$endOfLastPart."\ncountPotentialLocalityWords(".$startOfLastPart."): ".$this->countPotentialLocalityWords($startOfLastPart)."\ncountPotentialHabitatWords(".$startOfLastPart."): ".$this->countPotentialHabitatWords($startOfLastPart)."\ncountPotentialLocalityWords(".$endOfLastPart."): ".$this->countPotentialLocalityWords($endOfLastPart)."\ncountPotentialHabitatWords(".$endOfLastPart."): ".$this->countPotentialHabitatWords($endOfLastPart)."\n";
			if(strlen($startOfLastPart) > 0 && strlen($endOfLastPart) > 0) {
				$hCount22 = $this->countPotentialHabitatWords($endOfLastPart);
				if($this->countPotentialLocalityWords($endOfLastPart) == 0 && $hCount22 > 0 && $hCount22 > $this->countPotentialHabitatWords($startOfLastPart)) {
					return array('locality' => $firstPart.". ".$startOfLastPart, 'habitat' => $endOfLastPart);
				}
				$startOfFirstPart = "";
				$endOfFirstPart = "";
				if(preg_match("/(.*[A-Za-z]{3,}\)?), (.+)/", $firstPart, $mats3)) {
					$startOfFirstPart = trim($mats3[1]);
					$endOfFirstPart = trim($mats3[2]);
				}
				if(strlen($startOfFirstPart) > 0 && strlen($endOfFirstPart) > 0) {
					$hCount11 = $this->countPotentialHabitatWords($startOfFirstPart);
					if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $hCount11 > 0 && $hCount11 > $this->countPotentialHabitatWords($endOfFirstPart)) {
						return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
					}
				}
			}
			$startOfFirstPart = "";
			$endOfFirstPart = "";
			if(preg_match("/(.*[A-Za-z]{3,}\)?), (.+)/", $firstPart, $mats2)) {
				$startOfFirstPart = trim($mats2[1]);
				$endOfFirstPart = trim($mats2[2]);
			}
			if(strlen($startOfFirstPart) > 0 && strlen($endOfFirstPart) > 0) {
				$hCount1 = $this->countPotentialHabitatWords($startOfFirstPart);
				if($this->countPotentialLocalityWords($startOfFirstPart) == 0 && $hCount1 > 0 && $hCount1 > 0 && $hCount1 > $this->countPotentialHabitatWords($endOfFirstPart)) {
					return array('locality' => $endOfFirstPart.". ".$lastPart, 'habitat' => $startOfFirstPart);
				}
			}
			if($hCount1 > $hCount2 && $hCount1 > $lCount1) return array('locality' => $lastPart, 'habitat' => $firstPart);
			else if($hCount2 > $hCount1 && $hCount2 > $lCount2) return array('locality' => $firstPart, 'habitat' => $lastPart);
			//echo "\nline 14131, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\nline: ".$line."\nthis->countPotentialHabitatWords(firstPart): ".$this->countPotentialHabitatWords($firstPart)."\nthis->countPotentialHabitatWords(lastPart): ".$this->countPotentialHabitatWords($lastPart)."\n";
		}
		return array('locality' => trim(str_replace(";;;", ";", $line)));
	}

	protected function mergeFields($first, $last, $joiner=null) {
		$first = trim($first);
		$last = trim($last);
		$l1 = strlen($first);
		if($l1 == 0) return $last;
		else if(stripos($first, $last) === FALSE && stripos($last, $first) === FALSE) {
			if($joiner == null) {
				if(preg_match("/-$/", $first)) {
					$pos = strrpos($first, " ");
					if($pos !== FALSE) {
						$lastPart = trim(substr($first, $pos));
						if(strlen($lastPart) > 2) $joiner = "";//don't want to treat the minus in K- as a hyphen
						else $joiner = " ";
					} else if(strlen($first) > 2) $joiner = "";
					else $joiner = " ";
				} else if(preg_match("/^[A-Z]/", $last)) $joiner = ". ";
				else $joiner = ", ";
			}
			return trim($first, " :;,.").$joiner.ltrim($last, " :;,.");
		} else if($l1 > strlen($last)) return $first;
		else return $last;
	}

	protected function doGenericLabel($str, $ometid="", $fields=null) {//echo "\nDoin' Generic\n";
		$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|ch\\b)|Apr(?:\\.|il\\b)|May\\b|Jun(?:\\.|e\\b)|Jul(?:\\.|y\\b)|Aug(?:\\.|(?:ust\\b))|Sep(?:\\.|(?:t\\b\\.?)|tember\\b)|Oct(?:\\.|ober\\b)|Nov(?:\\.|ember\\b)|Dec(?:\\.|ember\\b)";
		$possibleNumbers = "[OQSZl|I!0-9]";
		$str = preg_replace
		(
			array
			(
				"/,\\n([A-Za-z]{3,}(?: [A-Za-z]{3,})?[.,])\\n/s",
				"/ASPIC[I1!|].{1,3}[I1!|]A/i",
				"/Deerlodge Co\\./i",
				"/\\bC[0O].{2,4}TY: ([A-Z])/i",
				"/([A-Za-z]) Co\\. , /",
				"/([A-Za-z]) Coo, ([A-Z])/",
				"/\\b199 ([0-9]) /",
				"/DESOTO PARISH/i"
			) ,
			array
			(
				", \${1}\n",
				"Aspicilia",
				"Deer Lodge Co.",
				"County: \${1}",
				"\${1} Co., ",
				"\${1} Co., \${2}",
				"199\${1} ",
				"De Soto Parish"
			),
			$str
		);
		//echo "\nstr\n".$str."\n";
		$acceptableSmallWords = "(?:road|pine|park|tree|fork|cove|camp|fire|bay|log|soil|sand|oaks?|base)";
		$recordedBy = '';
		$recordNumber = '';
		$associatedCollectors = '';
		$exsNumber = '';
		$recordedById = '';
		$identifiedBy = '';
		$dateIdentified = array();
		$eventDate = array();
		$otherCatalogNumbers = '';
		$tempRecordNumber = "";
		$taxonRank = '';
		$taxonRemarks = '';
		$infraspecificEpithet = '';
		$verbatimAttributes = '';
		$georeferenceRemarks = '';
		$occurrenceRemarks = '';
		$habitat = '';
		$scientificName = "";
		$associatedTaxa = "";
		$stateProvince = "";
		$verbatimElevation = '';
		$country = "";
		$county = "";
		$locality = "";
		$substrate = "";
		if($fields != null) {//foreach($fields as $k => $v) echo "\nline 13236, ".$k.": ".$v."\n";
			if(array_key_exists('recordedBy', $fields)) $recordedBy = $fields['recordedBy'];
			if(array_key_exists('recordNumber', $fields)) $recordNumber = $fields['recordNumber'];
			if(array_key_exists('exsNumber', $fields)) $exsNumber = $fields['exsNumber'];
			if(array_key_exists('recordedById', $fields)) $recordedById = $fields['recordedById'];
			if(array_key_exists('dateIdentified', $fields)) $dateIdentified = $fields['dateIdentified'];
			if(array_key_exists('eventDate', $fields)) $eventDate = $fields['eventDate'];
			if(array_key_exists('otherCatalogNumbers', $fields)) $otherCatalogNumbers = $fields['otherCatalogNumbers'];
			if(array_key_exists('taxonRank', $fields)) $taxonRank = $fields['taxonRank'];
			if(array_key_exists('taxonRemarks', $fields)) $taxonRemarks = $fields['taxonRemarks'];
			if(array_key_exists('infraspecificEpithet', $fields)) $infraspecificEpithet = $fields['infraspecificEpithet'];
			if(array_key_exists('verbatimAttributes', $fields)) $verbatimAttributes = $fields['verbatimAttributes'];
			if(array_key_exists('georeferenceRemarks', $fields)) $georeferenceRemarks = $fields['georeferenceRemarks'];
			if(array_key_exists('occurrenceRemarks', $fields)) $occurrenceRemarks = $fields['occurrenceRemarks'];
			if(array_key_exists('habitat', $fields)) $habitat = $fields['habitat'];
			if(array_key_exists('scientificName', $fields)) $scientificName = $fields['scientificName'];
			if(array_key_exists('associatedTaxa', $fields)) $associatedTaxa = $fields['associatedTaxa'];
			if(array_key_exists('stateProvince', $fields)) $stateProvince = $fields['stateProvince'];
			if(array_key_exists('verbatimElevation', $fields)) $verbatimElevation = $fields['verbatimElevation'];
			if(array_key_exists('country', $fields)) $country = $fields['country'];
			if(array_key_exists('county', $fields)) $county = $fields['county'];
			if(array_key_exists('locality', $fields)) $locality = $fields['locality'];
			if(array_key_exists('substrate', $fields)) $substrate = $fields['substrate'];
			if(array_key_exists('associatedCollectors', $fields)) $associatedCollectors = $fields['associatedCollectors'];
		}
		$foundSciName = false;
		if(strlen($scientificName) <= 3) $scientificName = $this->getScientificName($str);
		if(strlen($scientificName) > 3) $foundSciName = true;
		if(strlen($associatedTaxa) <= 3) $associatedTaxa = $this->getAssociatedTaxa($str);
		if(strlen($recordedBy) == 0 || strlen($identifiedBy) == 0) {
			$collectorInfo = $this->getCollector($str);
			if($collectorInfo != null) {
				if(strlen($recordedBy) == 0 && array_key_exists('collectorName', $collectorInfo)) $recordedBy = str_replace(" . ", ", ", $collectorInfo['collectorName']);
				if(array_key_exists('collectorNum', $collectorInfo)) {
					if($ometid) {
						$tempRecordNumber = $this->replaceMistakenNumbers($collectorInfo['collectorNum']);
						if(strlen($tempRecordNumber) > 2) $str = preg_replace("/".preg_quote($tempRecordNumber, '/')."/i", "", $str);
					} else if(strlen($recordNumber) == 0) {
						$recordNumber = $collectorInfo['collectorNum'];
						if(strlen($recordNumber) > 2) $str = preg_replace("/".preg_quote($recordNumber, '/')."/i", "", $str);
					}
				}
				if(strlen($recordedById) == 0 && array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				if(strlen($identifiedBy) == 0 && array_key_exists('identifiedBy', $collectorInfo)) {
					$identifiedBy = $collectorInfo['identifiedBy'];
					if(strlen($identifiedBy) > 3) $str = preg_replace("/(?:(?:Det(?:[:;,.]|ermined by)|Identifie(?:r|ed by)) )?".preg_quote($identifiedBy, '/')."[.,;: ]{0,2}/i", "\n", $str);
				}
				if(strlen($associatedCollectors) == 0 && array_key_exists('associatedCollectors', $collectorInfo)) {
					$associatedCollectors = $collectorInfo['associatedCollectors'];
					if(strlen($associatedCollectors) > 3) $str = preg_replace("/".preg_quote($associatedCollectors, '/')."/i", "\n", $str);
				}
				if(strlen($otherCatalogNumbers) == 0 && array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			}
		}
		if(strlen($identifiedBy) == 0) {
			$identifier = $this->getIdentifier($str, $possibleMonths);
			if($identifier != null) {
				$identifiedBy = $identifier[0];
				if(strlen($identifiedBy) > 3) $str = preg_replace("/(?:(?:Det(?:[:;,.]|ermined by)|Identifie(?:r|ed by)) )?".preg_quote($identifiedBy, '/')."[.,;: ]{0,2}/i", "\n", $str);
				$dateIdentified = $identifier[1];
				$fDI = $this->formatDate($dateIdentified);
				if(strlen($fDI) > 3) $str = preg_replace("/".preg_quote($fDI, '/')."/i", "", $str);
			}
		}
		if(strlen($recordedBy) > 3) $str = preg_replace("/(?:(?:Coll(?:[:;,.]|ect(?:ed by|ors?))) |Leg[:;,.] )?".preg_quote($recordedBy, '/')."[.,;: ]{0,2}/i", "\n", $str);
		$str = trim(preg_replace("/\n{2,}/", "\n", $str));
		$lookingForRecordNumber = false;
		if(strlen($locality) == 0) {
			$temp = $this->getLocality($str);
			if(strlen($temp) > 0 && !$this->isMostlyGarbage($temp, 0.48)) $locality = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $temp);
		}
		if(strlen($habitat) == 0) {
			$habitatArray = $this->getHabitat($str);
			$temp = $habitatArray[1];
			if(strlen($temp) > 0 && !$this->isMostlyGarbage($temp, 0.48)) $habitat = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $temp);
		}
		if(strlen($substrate) == 0) {
			$subArray = $this->getSubstrate($str);
			if($subArray) {
				$temp = trim($subArray[2]);
				if(strlen($temp) > 0 && !$this->isMostlyGarbage($temp, 0.48)) {
					$substrate = $temp;
					$str = trim(str_replace(array($subArray[1], "\n\n"), "\n", $str));
				}
			}
		}
		if(strlen($verbatimElevation) == 0) {
			$elevArr = $this->getElevation($str);
			$temp = $elevArr[1];
			if(strlen($temp) > 0) {
				$verbatimElevation = $temp;
				$str = trim(trim($elevArr[0])."\n".trim($elevArr[2]));
				//echo "\nstr\n".$str."\n";
			}
		}
		$matches = $this->getTaxonOfHeaderInfo($str);
		$states = array();
		if($matches != null) {
			$info = $this->processTaxonOfHeaderInfo($matches);//foreach($info as $k => $v) echo "\nline 13713, ".$k.": ".$v."\n";
			//if(array_key_exists('locality', $info)) $locality = $info['locality'];
			if(array_key_exists('county', $info) && strlen($county) == 0) $county = $info['county'];
			if(array_key_exists('country', $info) && strlen($country) == 0) $country = $info['country'];
			if(array_key_exists('stateProvince', $info) && strlen($stateProvince) == 0) $stateProvince = $info['stateProvince'];
			if(array_key_exists('states', $info)) $states = $info['states'];
			if(array_key_exists('endOfFile', $info)) $str = $info['endOfFile'];
		} else {
			$countyMatches = $this->findCounty($str, $stateProvince);
			if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
				$firstPart = trim($countyMatches[0]);
				$lastPart = trim($countyMatches[3]);
				if(strlen($county) == 0) $county = trim($countyMatches[1]);
				if(strlen($country) == 0) $country = trim($countyMatches[2]);
				$m4 = trim($countyMatches[4]);
				if(strlen($stateProvince) == 0) {
					$sp = $this->getStateOrProvince($m4);
					if(count($sp) > 0) {
						$stateProvince = $sp[0];
						$country = $sp[1];
					}
				}
				if(strlen($county) > 0 && (strlen($stateProvince) == 0 || strlen($country) == 0)) {
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
						if(array_key_exists('state', $polInfo)) $stateProvince = $polInfo['state'];
						if(array_key_exists('country', $polInfo)) $country = $polInfo['country'];
					}
				}
			}
		}//echo "\nline 13770, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\ncounty: ".$county."\nstateProvince: ".$stateProvince."\n";
		$lines = explode("\n", $str);
		foreach($lines as $line) {//echo "\nline 13763, line: ".$line."\n";
			$line = trim($line, " ,");
			if(preg_match("/((?:[A-Za-z]{1,12}[,.]? )?[A-Za-z]{2,})[,.] (?:U[,.]?S[,.]?A[,.]?|United States)(.+)/i", $line, $mats)) {
				$match = trim($mats[1]);
				if($this->isUSState($match)) {
					$stateProvince = $match;
					$country = "United States";
					$line = trim($mats[2]);
				} else if(preg_match("/((?:[A-Za-z]{1,12}[,.]? )?[A-Za-z]{2,})[,.] (?:Canada)(.+)/i", $line, $mats)) {
					$match = trim($mats[1]);
					$sp = $this->getStateOrProvince($match);
					if($sp) {
						$pc = $sp[1];
						if(strcasecmp($pc, "Canada") == 0) {
							if(strlen($stateProvince) == 0) $stateProvince = $match;
							$country = $pc;
							$line = trim($mats[2]);
						}
					}
				}
			}//echo "\nline 13783, line: ".$line."\ncounty: ".$county."\nstateProvince: ".$stateProvince."\n";
			if(strlen($stateProvince) > 1) {
				$pat;
				if(strlen($county) > 1) {
					$temp_county = preg_quote($county, "/");
					if(stripos($temp_county, "Saint ") !== FALSE) $temp_county = str_ireplace("Saint ", "S(?:ain)?t.? ", $temp_county);
					if(stripos($temp_county, "Sainte ") !== FALSE) $temp_county = str_ireplace("Sainte ", "S(?:ain)?te?.? ", $temp_county);
					$pat = "/^.{0,6}".$temp_county." (?:C[O0]UNT[V?Yi]|PAR[I1!||]SH|B[O0]R[O0]U[GC]H|D[I1!||]STR[I1!||]CT|Co\\b\\.?)(?! (?:PARK|FOREST|P?RESERVE))[;:.,]?(?: ".preg_quote($stateProvince, "/").")?(.*?)$/i";
					if(preg_match($pat, $line, $mats)) {/*echo "\nline 13672, matched\n";*/$line = trim($mats[1], " ,;:");}
					else if(preg_match("/^(?:(?:STATE [O0]F |ESTADO (?:DE )?)?".preg_quote($stateProvince, "/")."[.,:;] )?".$temp_county." (?:COUNT[V?Yi]|PAR[I1!||]SH|B[O0]R[O0]U[GC]H|Co\\b\\.?)?[;:.,]?\\s(.+)/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					else if(preg_match("/^(?:U\\.?S\\.?A\\.?,? ".preg_quote($stateProvince, "/")."[.,:;] )?".$temp_county." (?:COUNT[V?Yi]|PAR[I1!||]SH|B[O0]R[O0]U[GC]H|Co\\b\\.?)?[;:.,]?\\s(.+)/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					else if(preg_match("/^(?:United States(?: [O0]. America)?, ".preg_quote($stateProvince, "/")."[.,:;] )?".$temp_county." (?:COUNT[V?Y]|PAR[I1!||]SH|B[O0]R[O0]U[GC]H|Co\\b\\.?)?[;:.,]?\\s(.+)/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					else if(preg_match("/^(?:C[O0]UNT[V?Yi]|PAR[I1!||]SH|B[O0]R[O0]U[GC]H|D[I1!||]STR[I1!||]CT)[;:.,]? ".$temp_county."[;:.,]?\\s?(.*)/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					else if(preg_match("/^.{0,6}".$temp_county."[;:.,]? (?!(?:River|Mountains?|Mts?\\.?|(?:[A-Za-z]+(?: [A-Za-z]+) )?(?:STATE|NATIONAL|NATL\\b\\.?|PROVINCIAL|COUNT[V?Yi]) (?:PARK|FOREST|P?RESERVE)))(.+)/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					else if(preg_match("/^.{0,6}".preg_quote($stateProvince, "/")."[;:.,]?[;:.,]? (?!(?:River|Mountains?|Mts?\\.?|[A-Za-z]+(?: [A-Za-z]+) (?:STATE|NATIONAL|NATL\\b\\.?|PROVINCIAL|COUNT[V?Yi]) (?:PARK|FOREST|P?RESERVE)))(.+)/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					$pat = "/^(.*?)(?: UNIVERSITY\\b|\\bC[O0]LL(?:\\.|ected by)|\\bHERBARIUM\\b|\\bDET(?:\\.|ermined)|Identified\\b|\\bNew\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden\\b|\\bDate\\b|\\b(?:[O|!lI0-9]{1,2}[- ])?(?:".$possibleMonths.")|[\n ]".$temp_county." (?:COUNT[V?Yi]|PAR[I1!||]SH|B[O0]R[O0]U[GC]H|Co\\b\\.?))(.*)$/i";
				} else {
					if(preg_match("/^.{0,6}(?: ".preg_quote($stateProvince, "/").")(.*?)$/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					else if(preg_match("/^(?:(?:STATE [O0]F |ESTAD[O0] (?:DE )?)".preg_quote($stateProvince, "/")."[.,:;])\\s(.+)/i", $line, $mats)) $line = trim($mats[1], " ,;:");
					$pat = "/^(.*?)\\b(?:UNIVERSITY\\b|C[O0]LL(?:\\.|ected by)|HERBARIUM\\b|DET(?:\\.|ermined)|Identified\\b|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden\\b|Date\\b|(?:[O|!lI0-9]{1,2}[- ])?(?:".$possibleMonths."))(.*)$/i";
				}
				if(strlen($pat) > 1 && preg_match($pat, $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13804, mats[".$i++."] = ".$mat."\n";
					$temp = trim($mats[1], " ,;:");
					if(strlen($temp) > 0) $line = $temp;
					else $line = trim($mats[2], " ,;:");
					if(preg_match($pat, $line, $mats)) {
						$temp = trim($mats[1], " ,;:");
						if(strlen($temp) > 0) $line = $temp;
						else $line = trim($mats[2], " ,;:");
					}
				}
			} else if(preg_match("/^(U\\.?[S5]\\.?[S5]\\.?R\\.?) (.*)$/", $line, $mats)) {
				$country = str_replace("5", "S", $mats[1]);
				$line = trim($mats[2]);
			} else if(preg_match("/^Mexico\\b[.,:;]? (.*)$/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13811, mats[".$i++."] = ".$mat."\n";
				$country = "Mexico";
				$line = trim($mats[1]);
				if(preg_match("/^(?:STATE [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $line, $mats2)) $line = trim($mats2[1]);
				if(strlen($line) > 3) {
					$sp = $this->getStateOrProvince($line);
					if(count($sp) > 0) {
						if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
						$line = ltrim(rtrim(substr($line, stripos($line, $stateProvince)+strlen($stateProvince))), " ;:.,");
					}
				}
			} else if(preg_match("/^([A-Za-z]{4,}(?: [A-Za-z]{4,}(?: [A-Za-z]{4,})?)?)\\. (([A-Za-z]{4,}(?: [A-Za-z]{3,}(?: [A-Za-z]{4,})?)?): ?(.*))$/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13842, mats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				if(preg_match("/^(?:STATE [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $temp, $mats2)) $temp = trim($mats2[1]);
				if(strlen($temp) > 3 && str_word_count($temp) < 4) {
					$sp = $this->getStateOrProvince($temp);
					if(count($sp) > 0) {
						if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
						if(strlen($country) == 0) $country = $sp[1];
						if(strlen($country) > 0) {
							$temp = trim($mats[3]);
							$cMatches = $this->getCounty($temp, $stateProvince);
							if($cMatches && array_key_exists('county', $cMatches)) {
								$county = $cMatches['county'];
								if(strlen(country) == 0 && array_key_exists('country', $cMatches)) $country = $cMatches['country'];
								$line = trim($mats[4]);
							} else $line = trim($mats[2]);
						} else $line = trim($mats[2]);
					} else if(strlen($country) > 0) {
						$states = $this->getStatesFromCountry($country);
						foreach($states as $state) {
							if(strcasecmp($state, $temp) == 0) {
								$stateProvince = $temp;
								$temp = trim($mats[3]);
								$cMatches = $this->getCounty($temp, $stateProvince);
								if($cMatches && array_key_exists('county', $cMatches)) {
									$county = $cMatches['county'];
									if(strlen(country) == 0 && array_key_exists('country', $cMatches)) $country = $cMatches['country'];
									$line = trim($mats[4]);
								} else $line = trim($mats[2]);
								break;
							}
						}
					} else {
						$country = $this->getCountry($temp);
						if(strlen($country) > 0) {
							$temp = trim($mats[3]);//echo "\nline 13866, temp: ".$temp."\n";
							$states = $this->getStatesFromCountry($country);
							foreach($states as $state) {
								if(strcasecmp($state, $temp) == 0) {
									$stateProvince = $temp;
									$line = trim($mats[4], " .;:");
									break;
								}
							}
							if(strlen($stateProvince) == 0) $line = trim($mats[2], " .;:");
						}
					}
				}
			} else if(preg_match("/^(.+?):(.*)$/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13890, mats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				if(preg_match("/^(?:STATE [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $temp, $mats2)) $temp = trim($mats2[1]);
				if(strlen($temp) > 3 && str_word_count($temp) < 4) {
					$sp = $this->getStateOrProvince($temp);
					if(count($sp) > 0) {
						if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
						if(strlen($country) == 0) $country = $sp[1];
						$line = trim($mats[2]);
					} else if(strlen($country) > 0) {
						$states = $this->getStatesFromCountry($country);
						foreach($states as $state) if(strcasecmp($state, $temp) == 0) {
							$stateProvince = $temp;
							$line = trim($mats[2]);
						}
					} else {
						$country = $this->getCountry($temp);
						if(strlen($country) > 0) {
							$line = trim($mats[2], " .;:");
							if(preg_match("/^(.+?[A-Za-z]{4,})[:;,.](.*+)$/", $line, $mats2)) {
								$temp = trim($mats2[1]);
								if(preg_match("/^(.+[A-Za-z]{4,})[:;,.](.*)/", $temp, $mats3)) {
									$temp = trim($mats3[1]);
									if(str_word_count($temp) < 4) {
										$sp = $this->getStateOrProvince($temp);
										if(count($sp) > 0) {
											if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
											$line = trim($mats3[2]);
										}
									}
								} else if(str_word_count($temp) < 4) {
									$sp = $this->getStateOrProvince($temp);
									if(count($sp) > 0) {
										if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
										$line = trim($mats2[2]);
									}
								}
							}
						}
					}
				}
			} else if(preg_match("/^([A-Za-z]{4,}(?: [A-Za-z]{4,}(?: [A-Za-z]{4,})?)?)\\. (.*)$/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13931, mats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				if(preg_match("/^(?:STATE [O0]F |ESTAD[O0] (?:DE )?)(.+)/i", $temp, $mats2)) $temp = trim($mats2[1]);
				if(strlen($temp) > 3 && str_word_count($temp) < 4) {
					$sp = $this->getStateOrProvince($temp);
					if(count($sp) > 0) {
						if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
						if(strlen($country) == 0) $country = $sp[1];
						$line = trim($mats[2]);
					} else if(strlen($country) > 0) {
						$states = $this->getStatesFromCountry($country);
						foreach($states as $state) if(strcasecmp($state, $temp) == 0) {
							$stateProvince = $temp;
							$line = trim($mats[2]);
						}
					} else {
						$country = $this->getCountry($temp);
						if(strlen($country) > 0) {
							$line = trim($mats[2], " .;:");
							if(preg_match("/^(.+?[A-Za-z]{4,})[,.] (.*+)$/", $line, $mats2)) {
								$temp = trim($mats2[1]);
								if(preg_match("/^(.+[A-Za-z]{4,})[:;](.*)/", $temp, $mats3)) {
									$temp = trim($mats3[1]);
									if(str_word_count($temp) < 4) {
										$sp = $this->getStateOrProvince($temp);
										if(count($sp) > 0) {
											if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
											$line = trim($mats3[2]);
										}
									}
								} else if(str_word_count($temp) < 4) {
									$sp = $this->getStateOrProvince($temp);
									if(count($sp) > 0) {
										if(strlen($stateProvince) == 0) $stateProvince = $sp[0];
										$line = trim($mats2[2]);
									}
								}
							}
						}
					}
				}
			} else {
				$pat = "/(.*)\\b(?:UNIVERSITY|COLL(?:\\.|ect(?:ed)?)|HERBARIUM|DET(?:\\.|ermined)|Identified|New\\s?Y[o0]rk\\s?B[o0]tan[1!il|]cal\\s?Garden|Date|".preg_quote($possibleMonths, "/").")\\b(.*)/i";
				if(preg_match($pat, $line, $mats)) {
					$temp = trim($mats[1], " ,;:");
					if(strlen($temp) > 2) $line = $temp;
					else $line = trim($mats[2], " ,;:");
					if(preg_match($pat, $line, $mats)) {
						$temp = trim($mats[1], " ,;:");
						if(strlen($temp) > 2) $line = $temp;
						else $line = trim($mats[2], " ,;:");
					}
				}
			}//echo "\nline 13970, line: ".$line."\n";
			$firstPart = "";
			$secondPart = "";
			//try to match the first part of a lat/long
			if(preg_match("/^([^°']*?)\\b(?:(?i:Near|ca\\.?(?-i)) )?(?i:Lat(?:\\.|itude)[:;, ]{0,2}(?-i))?(?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?[°\"'](?: ?[0-9!l|I]{1,3}+(?:[,.][0-9!l|I]{1,3})? ?['°]?(?: ?[0-9!l|I]{1,3}+ ?\")?)? ?[NSEW](.+)/", $line, $mats)) {
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
			} else if(preg_match("/^([^°']*?)\\b(?:(?i:Near|ca\\.?(?-i)) )?(?i:Lat(?:\\.|itude(?-i))[:;,]?)?(?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?) ?[°\"'] ?[0-9!l|I]{1,3} ?[°\"'] ?[NSEW](.+)/", $line, $mats)) {
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
			}
			if(strlen($secondPart) > 0) {//echo "\nline 13825, firstPart: ".$firstPart."\nsecondPart: ".$secondPart."\n";
				if(preg_match("/(?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?°(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?[' ]".
					"(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?\")?)?\\s?[NS5EW][,.]?(?:\\sL(?i)(?:at|ong)(?:\\.|itude)(?-i))?[;.:]?(.*?)$/", $secondPart, $mats2)) {
					$secondPart = trim($mats2[1]);
				}
				else if(preg_match("/(?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?°(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?[' ]".
					"(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?\")?)?\\s?[NS5EW][,.]?(?:\\sL(?i)ong(?:\\.|itude)(?-i))?[;.:]?(.*?)$/", $secondPart, $mats2)) {//$i=0;foreach($mats2 as $mat) echo "\nline 14207, mats2[".$i++."] = ".$mat."\n";
					$secondPart = trim($mats2[1]);
				}
				if(strlen($firstPart) > 0) $line = rtrim(trim($firstPart, " ,.;").";;; ".ltrim($secondPart, " ,.;"), " ;");
				else $line = trim($secondPart, " ,.;");
			} else if(preg_match("/([^°]*?)(?:(?:Near|ca\\.?) )?(?:Lat(?:\\.|itude)[:;,]?)?[NS](?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?°(.+)/i", $line, $mats)) {
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
				if(preg_match("/[NS](?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.] ?[0-9!l|I]{1,6})? ?°(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?'".
					"(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?\")?)?\\s?(?:\\sLong(?:\\.|itude))?[;.]?(.*?)$/i", $secondPart, $mats2)) $secondPart = trim($mats2[1]);
				else if(preg_match("/[NS](?:[1Il][0-9!l|IO]{1,2}|[1-9!l|I][0-9!l|IO]?)(?:[,.][0-9!l|I]{1,6})? ?°(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?'".
					"(?:\\s?[0-9!l|I]{1,3}(?:[,.][0-9!l|I]{1,6})? ?\")?)?\\s?(?:\\sLong(?:\\.|itude))?[;.]?(.*?)$/i", $secondPart, $mats2)) $secondPart = trim($mats2[1]);
				$line = trim(trim($firstPart, " ,.;").";;; ".ltrim($secondPart, " ,.;"), " ;");
			} else if(strlen($verbatimElevation) > 0) {
				if(preg_match("/(.+)\\b".preg_quote($verbatimElevation, "/")."(.+)/i", $line, $mats)) {
					$firstPart = trim($mats[1], " ,.;");
					$secondPart = ltrim($mats[2], " ,.;");
					if(preg_match("/(.+)\\b(?:elev(?:\\.|ation)?|alt(?:\\.|itude)?)/i", $firstPart, $mats2)) $firstPart = trim(rtrim($mats2[1], "("), " ,.;:");
					else if(preg_match("/(?:elev(?:\\.|ation)?|alt(?:\\.|itude)?)(.+)/i", $secondPart, $mats2)) $secondPart = trim(ltrim($mats2[1], ")"), " ,.;:");
					$line = trim($firstPart.";;; ".$secondPart, " ;");
				}
			}//echo "\nline 14020, line: ".$line."\n";
			$trsPatStr = "/(?(?=(?:.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)".
				"R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".
				$possibleNumbers."{1,3})\\b)".
			//if the condition is true then the form is TRS
				"(.*)\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".
				$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})\\b(.+)|".
			//else the form is STR
				"(.*)\\b[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3}),?(?:\\s|\\n|\\r\\n)T(?:\\.|wnshp.?|ownship)?\\s?(?:".
				$possibleNumbers."{1,3})\\s?(?:[NS])\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?\\b(.+))/is";
			if(preg_match($trsPatStr, $line, $trsMatches)) {//$i=0;foreach($trsMatches as $trsMatche) echo "\nline 12469, trsMatches[".$i++."] = ".$trsMatche."\n";
				if(count($trsMatches) > 4) $line = trim(trim($trsMatches[3], " ,.;").". ".ltrim($trsMatches[4], " ,.;"));
				else $line = trim(trim($trsMatches[1], " ,.;").". ".ltrim($trsMatches[2], " ,.;"));
			}
			$utmPatStr = "/(.*)\\b(?:UTM:?(?:\\s|\\n|\\r\\n)(?:Zone\\s)?(?:".$possibleNumbers."{1,2})(?:\\w?(?:\\s|\\n|\\r\\n))(?:".
				$possibleNumbers."{1,8}E?(?:\\s|\\n|\\r\\n)".$possibleNumbers."{1,8}N?))\\b(.*)/is";
			if(preg_match($utmPatStr, $line, $locMatches)) {
				$line = trim(trim($locMatches[1], " ,.;").". ".ltrim($locMatches[2], " ,.;"));
			}
			if(!$foundSciName) {
				$psn = $this->processSciName(trim(str_ireplace(array("Annotation ", "cf. "), "", $line), " \"\',"));
				if($psn != null) {//foreach($psn as $k => $v) echo "\nline 13514, ".$k.": ".$v."\n";
					if(array_key_exists('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
						$line2 = trim(preg_replace("/".preg_quote($scientificName, '/')."/i", "", $line));
						if(strcasecmp($line2, $line) == 0) {
							$words = array_reverse(explode(" ", $scientificName));
							if(count($words) > 1) {
								$word = $words[0];
								$wLength = strlen($word);
								if($wLength > 2) {
									$pos = stripos($line, $word);
									if($pos !== FALSE) $line = trim(substr($line, $pos+$wLength));
								}
							}
						} else $line = $line2;
					}
					if(array_key_exists('infraspecificEpithet', $psn)) {
						$infraspecificEpithet = $psn['infraspecificEpithet'];
						$line = trim(preg_replace("/".preg_quote($infraspecificEpithet, '/')."/i", "", $line));
					}
					if(array_key_exists('taxonRank', $psn)) {
						$taxonRank = $psn['taxonRank'];
						$line = trim(preg_replace("/".preg_quote($taxonRank, '/')."/i", "", $line));
					}
					if(array_key_exists('verbatimAttributes', $psn)) {
						$temp = $psn['verbatimAttributes'];
						$line = trim(preg_replace("/".preg_quote($temp, '/')."/i", "", $line));
						$verbatimAttributes = $this->mergeFields($verbatimAttributes, $temp);
					}
					if(array_key_exists('associatedTaxa', $psn)) {
						$temp = $psn['associatedTaxa'];
						$line = trim(preg_replace("/".preg_quote($temp, '/')."/i", "", $line));
						$associatedTaxa = $this->mergeFields($associatedTaxa, $temp);
					}
					if(array_key_exists('recordNumber', $psn)) {
						$temp = $psn['recordNumber'];
						if($ometid) {
							$exsNumber = $this->replaceMistakenNumbers($temp);
							$line = trim(ltrim(preg_replace("/(?:\\bNo\\.? ?)?".preg_quote($temp, '/')."/i", "", $line), " :;,."));
						} else if(strlen($recordNumber) == 0) {
							$recordNumber = $this->replaceMistakenNumbers($temp);
							$line = trim(ltrim(preg_replace("/(?:\\bNo\\.? ?)?".preg_quote($temp, '/')."/i", "", $line), " :;,."));
						}
					}
					if(array_key_exists('taxonRemarks', $psn)) {
						$temp = $psn['taxonRemarks'];
						$line = trim(preg_replace("/".preg_quote($temp, '/')."/i", "", $line));
						$taxonRemarks = $this->mergeFields($taxonRemarks, $temp);
					}
					if(array_key_exists('substrate', $psn)) {
						$temp = $psn['substrate'];
						$line = trim(preg_replace("/".preg_quote($temp, '/')."/i", "", $line));
						$substrate = $this->mergeFields($substrate, $temp);
					}
					$line = $this->removeAuthority($line, $scientificName);
					$foundSciName = true;
				}
			}//echo "\nline 14086, line: ".$line."\ncountPotentialLocalityWords(".$line."): ".$this->countPotentialLocalityWords($line)."\n";
			if($ometid) {
				if(strlen($exsNumber) == 0) {
					if(preg_match("/^No\\.:?(.*)/", $line, $mats)) {
						$temp = trim($mats[1]);
						if(strlen($temp) == 0) $lookingForRecordNumber = true;
						else {
							$spacePos = strpos($temp, " ");
							if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
							if($this->containsNumber($temp)) $exsNumber = $this->replaceMistakenNumbers($temp);
						}
					} else if($lookingForRecordNumber) {
						$lookingForRecordNumber = false;
						$spacePos = strpos($line, " ");
						if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
						else $temp = $line;
						if($this->containsNumber($temp)) {
							$exsNumber = $this->replaceMistakenNumbers($temp);
							$line = trim(substr($line, 0, strpos($line, $temp)+strlen($temp)));
						}
					}
				}
			} else if(strlen($recordNumber) == 0) {
				if(preg_match("/^No\\.:?(.*)/", $line, $mats)) {
					$temp = trim($mats[1]);
					if(strlen($temp) == 0) $lookingForRecordNumber = true;
					else {
						$spacePos = strpos($temp, " ");
						if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
						if($this->containsNumber($temp)) $recordNumber = $temp;
					}
				} else if($lookingForRecordNumber) {
					$lookingForRecordNumber = false;
					$spacePos = strpos($line, " ");
					if($spacePos !== FALSE) $temp = substr($temp, 0, $spacePos);
					else $temp = $line;
					if($this->containsNumber($temp)) {
						$recordNumber = $temp;
						$line = trim(substr($line, 0, strpos($line, $temp)+strlen($temp)));
					}
				}
			}//echo "\nline 14139, line: ".$line."\ncountPotentialLocalityWords(".$line."): ".$this->countPotentialLocalityWords($line)."\n";
			if($this->containsVerbatimAttribute($line)) {//echo "\nline 14140, containsVerbatimAttribute\n";
				$words = explode($line, " ");
				$skipToNext = false;
				if(count($words) > 2) {
					$first = $words[0];
					$rest = trim(substr($line, strpos($line, $first)+strlen($first)));
					if($this->containsVerbatimAttribute($first) && !$this->containsVerbatimAttribute($rest)) {
						$verbatimAttributes = $this->mergeFields($verbatimAttributes, $first);
						$line = $rest;
						$skipToNext = true;
					} else {
						$words = array_reverse($words);
						$last = $words[0];
						$rest = trim(substr($line, 0, strpos($line, $first)));
						if($this->containsVerbatimAttribute($last) && !$this->containsVerbatimAttribute($rest)) {
							$verbatimAttributes = $this->mergeFields($verbatimAttributes, $last);
							$line = $rest;
							$skipToNext = true;
						}
					}
				}
				if(!$skipToNext) {
					$pos = strpos($line, ";;;");
					$rest = "";
					if($pos !== FALSE) {
						$firstPart = trim(substr($line, 0, $pos));
						$lastPart = trim(substr($line, $pos+3));
						if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart)) {
							$rest = $firstPart;
							$line = $lastPart;
						} else if($this->containsVerbatimAttribute($lastPart) && !$this->containsVerbatimAttribute($firstPart)) {
							$rest = $lastPart;
							$line = $firstPart;
						} else {
							$rest = $line;
							$line = "";
						}
					} else {
						$pos = strpos($line, "; ");
						if($pos !== FALSE) {
							$firstPart = trim(substr($line, 0, $pos));
							$lastPart = trim(substr($line, $pos+2));
							if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart)) {
								$rest = $firstPart;
								$line = $lastPart;
							} else if($this->containsVerbatimAttribute($lastPart) && !$this->containsVerbatimAttribute($firstPart)) {
								$rest = $lastPart;
								$line = $firstPart;
							} else {
								$rest = $line;
								$line = "";
							}
						} else if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
							if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart)) {
								$rest = $firstPart;
								$line = $lastPart;
							} else if($this->containsVerbatimAttribute($lastPart) && !$this->containsVerbatimAttribute($firstPart)) {
								$rest = $lastPart;
								$line = $firstPart;
							} else {
								$rest = $line;
								$line = "";
							}
						} else {
							$rest = $line;
							$line = "";
						}
					}
					if(strlen($rest) > 0) {//echo "\nline 13682, rest: ".$rest."\n";
						$rest = trim(str_replace(";;;", ";", $rest));
						if(preg_match("/^((?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant) |Found |Loose |Growing )?On .+[A-Za-z]{4,})[,;.] (.++)$/i", $rest, $mats)) {
							$temp = trim($mats[1]);
							if($this->countPotentialLocalityWords($temp) == 0) {
								$substrate = $this->mergeFields($substrate, $temp);
								$rest = trim($mats[2], " ;:,.&");
							} else {
								$temp2 = "";
								$rest2 = "";
								if(preg_match("/(.+); (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								} else if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								} else if(preg_match("/(.+[A-Za-z]{3,}), (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								}
								if(strlen($temp2) > 0 && $this->countPotentialLocalityWords($temp2) == 0) {
									$substrate = $this->mergeFields($substrate, $temp2);
									$rest = $rest2.". ".$rest;
								}
							}
						} else if(preg_match("/^(Corticolous (on .+))/i", $rest, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13637, mats[".$i++."] = ".$mat."\n";
							$verbatimAttributes = "Corticolous";
							$temp = trim($mats[1]);
							if(preg_match("/^(Corticolous on [A-Za-z]+ )((?:along|on|at) .+)/i", $rest, $mats2)) {//$i=0;foreach($mats as $mat) echo "\nline 13641, mats[".$i++."] = ".$mat."\n";
								$temp = trim($mats2[2]);
								if($this->countPotentialLocalityWords($temp) > 0) {
									$substrate = $this->mergeFields($substrate, trim($mats2[1]));
									$locality = $this->mergeFields($locality, trim($mats2[2]));
								} else if($this->countPotentialHabitatWords($temp) > 0) {
									$substrate = $this->mergeFields($substrate, trim($mats2[1]));
									$habitat = $this->mergeFields($habitat, trim($mats2[2]));
								}
							} else $substrate = $this->mergeFields($substrate, $rest);
						} else if(preg_match("/(.+)[.,;] {1,2}((?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant) |Found |Loose |Growing )?On .+)/i", $rest, $mats)) {
							$temp = trim($mats[2]);
							if($this->countPotentialLocalityWords($temp) == 0) {
								$substrate = $this->mergeFields($substrate, $temp);
								$rest = trim($mats[1], " ;:,.&");
							} else {
								$temp2 = "";
								$rest2 = "";
								if(preg_match("/(.+); (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								} else if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								} else if(preg_match("/(.+[A-Za-z]{3,}), (.+)/i", $temp, $mats2)) {
									$temp2 = trim($mats2[1]);
									$rest2 = trim($mats2[2]);
								}
								if(strlen($temp2) > 0 && $this->countPotentialLocalityWords($temp2) == 0) {
									$substrate = $this->mergeFields($substrate, $temp2);
									$rest = trim($mats[1], " ;:,.&");
									$locality = $this->mergeFields($locality, $rest2);
								}
							}
						}
						if(preg_match("/(.+[A-Za-z]{3,})\\. (.+)/i", $rest, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
							if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart)) {
								if($this->countPotentialHabitatWords($lastPart) > 0) {
									$habitat = $this->mergeFields($habitat, $lastPart);
									$verbatimAttributes = $this->mergeFields($verbatimAttributes, $firstPart);
									$rest = "";
								} else if($this->countPotentialLocalityWords($lastPart) > 0) {
									$locality = $this->mergeFields($locality, $lastPart);
									$verbatimAttributes = $this->mergeFields($verbatimAttributes, $firstPart);
									$rest = "";
								}
							} else if($this->containsVerbatimAttribute($lastPart) && !$this->containsVerbatimAttribute($firstPart)) {
								if($this->countPotentialHabitatWords($firstPart) > 0) {
									$habitat = $this->mergeFields($habitat, $firstPart);
									$verbatimAttributes = $this->mergeFields($verbatimAttributes, $lastPart);
									$rest = "";
								} else if($this->countPotentialLocalityWords($firstPart) > 0) {
									$locality = $this->mergeFields($locality, $firstPart);
									$verbatimAttributes = $this->mergeFields($verbatimAttributes, $lastPart);
									$rest = "";
								}
							}
						}
						if(strlen($rest) > 0 && !$this->isMostlyGarbage2($rest, 0.48)) {
							if($this->containsVerbatimAttribute($rest)) {
								if(preg_match("/(.+) Substrate[;:]? (.*)/i", $rest, $mats)){
									$firstPart = trim($mats[1]);
									$lastPart = trim($mats[2]);
									if($this->containsVerbatimAttribute($firstPart) && !$this->containsVerbatimAttribute($lastPart)) {
										$verbatimAttributes = $this->mergeFields($verbatimAttributes, $firstPart);
										$substrate = $this->mergeFields($substrate, $lastPart);
									} else $verbatimAttributes = $this->mergeFields($verbatimAttributes, $rest);
								} else $verbatimAttributes = $this->mergeFields($verbatimAttributes, $rest);
							} else if($this->countPotentialHabitatWords($rest) > 0) $habitat = $this->mergeFields($habitat, $rest);
							else if($this->countPotentialLocalityWords($rest) > 0) $locality = $this->mergeFields($locality, $rest);
							else $occurrenceRemarks = $rest;
						}
					}
				}
			}//echo "\nline 14307, line: ".$line."\n";
			if(preg_match("/^((?:(?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant))|Found|Loose|Growing) On .+)/i", $line)) {
				$substrate = trim(str_replace(";;;", ";", $line));
				$firstPart = "";
				$lastPart = "";
				if(preg_match("/([A-Za-z ,-]+)[,.]? ((?:along|among|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under) .+)/i", $substrate, $mats)) {
					$firstPart = trim($mats[1]);
					$lastPart = trim($mats[2]);
					if(preg_match("/([A-Za-z ,-]+)[,.]? ((?:along|among|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under) .+)/i", $firstPart, $mats2)) {
						$firstPart = trim($mats2[1]);
						$lastPart = trim($mats2[2])." ".$lastPart;
					}
				} else if(preg_match("/(.+); (.+)/", $substrate, $mats2)) {
					$firstPart = trim($mats2[1]);
					$lastPart = trim($mats2[2]);
				} else if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $substrate, $mats2)) {
					$firstPart = trim($mats2[1]);
					$lastPart = trim($mats2[2]);
				} else if(preg_match("/(.* ".$acceptableSmallWords.")\\. (.+)/", $substrate, $mats2)) {
					$firstPart = trim($mats2[1]);
					$lastPart = trim($mats2[2]);
				} else if(preg_match("/(.*[A-Za-z]{4,}\)?), (.+)/", $substrate, $mats2)) {
					$firstPart = trim($mats2[1]);
					$lastPart = trim($mats2[2]);
				}
				if(strlen($firstPart) > 0 && strlen($lastPart) > 0) {//echo "\nline 13797, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\n";
					$startOfLastPart = "";
					$endOfLastPart = "";
					if($this->countPotentialLocalityWords($lastPart) == 0) {
						if($this->countPotentialHabitatWords($lastPart) > 0) {
							$substrate = $firstPart;
							$habitat = $this->mergeFields($habitat, $lastPart);
						}
					} else if(preg_match("/(.+); (.+)/", $lastPart, $mats2)) {
						$startOfLastPart = trim($mats2[1]);
						$endOfLastPart = trim($mats2[2]);
					} else if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $lastPart, $mats2)) {
						$startOfLastPart = trim($mats2[1]);
						$endOfLastPart = trim($mats2[2]);
					} else if(preg_match("/(.* ".$acceptableSmallWords.")\\. (.+)/", $lastPart, $mats2)) {
						$startOfLastPart = trim($mats2[1]);
						$endOfLastPart = trim($mats2[2]);
					} else if(preg_match("/(.*[A-Za-z]{4,}\)?), (.+)/", $lastPart, $mats2)) {
						$startOfLastPart = trim($mats2[1]);
						$endOfLastPart = trim($mats2[2]);
					} else {//the last part has locality words
						$substrate = $firstPart;
						$locality = $this->mergeFields($locality, $lastPart);
					}
					if(strlen($startOfLastPart) > 0 && strlen($endOfLastPart) > 0) {//the last part has locality words and 2 parts to analyze
						$substrate = $firstPart;
						if($this->countPotentialLocalityWords($startOfLastPart) == 0) {//all of the locality words are in endOfLastPart
							if($this->countPotentialHabitatWords($startOfLastPart) > 0) {
								$habitat = $this->mergeFields($habitat, $startOfLastPart);
								$locality = $this->mergeFields($locality, $endOfLastPart);
							} else $locality = $this->mergeFields($locality, $lastPart);
						} else if($this->countPotentialLocalityWords($endOfLastPart) == 0) {//all of the locality words are in startOfLastPart
							if($this->countPotentialHabitatWords($endOfLastPart) > 0) {
								$locality = $this->mergeFields($locality, $startOfLastPart);
								$habitat = $this->mergeFields($habitat, $endOfLastPart);
							} else $locality = $this->mergeFields($locality, $lastPart);
						} else $locality = $this->mergeFields($locality, $lastPart);
					}
				}
			} else if(strcasecmp(substr($line, 0, 3), "On ") == 0) {
				if(preg_match("/(.*)(?:".$possibleNumbers."{2,4}[ -])?(?:".$possibleMonths.")/i", $line, $mats)) {
					$line = trim($mats[1]);
					if(strcasecmp($line, "on") == 0) break;
				}
				if(strlen($line) > 0 && stripos($line, " Workshop") === FALSE && stripos($line, " foray") === FALSE && stripos($line, " Society") === FALSE) {
					$line = trim(str_replace(";;;", ";", $line));
					if($this->countPotentialLocalityWords($line) > 0) {//echo "\nline 13843, line: ".$line."\n";
						$firstPart = "";
						$lastPart = "";
						if(preg_match("/([A-Za-z ,-]+)[,.]? ((?:along|among|near|within|at|be(?:low|yond|neath|hind)|above|under) .+)/i", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.+); (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.* ".$acceptableSmallWords."\)?)\\. (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.*[A-Za-z]{4,}\)?), (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.+): (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						}
						if(strlen($firstPart) > 0 && strlen($lastPart) > 0) {//echo "\nline 14400, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\n";
							if(strlen($lastPart) < 4 && preg_match("/(.*[A-Za-z]{4,})[,;] (.+)/", $firstPart, $mats2)) {
								$firstPart = trim($mats2[1]);
								$lastPart = trim($mats2[2]);
							}
							if($this->countPotentialLocalityWords($firstPart) == 0) {//echo "\nline 13862, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\n";
								if(strlen($substrate) > 0 && $this->countPotentialHabitatWords($firstPart) > 0) $habitat = $this->mergeFields($habitat, $firstPart);
								else $substrate = $this->mergeFields($substrate, $firstPart);
								$locality = $this->mergeFields($locality, $lastPart);
							} else if($this->countPotentialLocalityWords($lastPart) == 0) {
								if($this->countPotentialHabitatWords($lastPart) > 0) {
									$locality = $this->mergeFields($locality, $firstPart);
									$habitat = $this->mergeFields($habitat, $lastPart);
								} else $locality = $this->mergeFields($locality, $line);
							} else $locality = $this->mergeFields($locality, $line);
						} else $locality = $this->mergeFields($locality, $line);
					} else if($this->countPotentialHabitatWords($line) > 0) {
						$firstPart = "";
						$lastPart = "";
						if(preg_match("/(.+); (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.* ".$acceptableSmallWords.")\\. (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						} else if(preg_match("/(.*[A-Za-z]{4,}\)?), (.+)/", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
						}
						if(strlen($firstPart) > 0 && strlen($lastPart) > 0) {
							if(strlen($lastPart) < 4 && preg_match("/(.*[A-Za-z]{4,})[,;] (.+)/", $firstPart, $mats2)) {
								$firstPart = trim($mats2[1]);
								$lastPart = trim($mats2[2]);
							}
							if($this->countPotentialHabitatWords($lastPart) > 0) {
								if(strlen($substrate) > 0) $habitat = $this->mergeFields($habitat, $line);
								else {
									$habitat = $this->mergeFields($habitat, $lastPart);
									$substrate = $this->mergeFields($substrate, $firstPart);
								}
							} else $substrate = $this->mergeFields($substrate, $line);
						} else $substrate = $this->mergeFields($substrate, $line);
					} else $substrate = $this->mergeFields($substrate, $line);
				} else break;
			} else if($this->countPotentialLocalityWords($line) > 0 && !preg_match("/^(?:L|(?:\|_))[o0]ca(?:[1!l]ity|ti[o0]n|\\.)[:;]?/i", $line)) {
				$localityAnalysis = $this->analyzeLocalityLine($line, $acceptableSmallWords);//foreach($localityAnalysis as $k => $v) echo "\nline 14589, ".$k.": ".$v."\n";
				if(array_key_exists('locality', $localityAnalysis)) $locality = $this->mergeFields($locality, $localityAnalysis['locality']);
				if(array_key_exists('habitat', $localityAnalysis)) $habitat = $this->mergeFields($habitat, $localityAnalysis['habitat']);
				if(array_key_exists('substrate', $localityAnalysis)) $substrate = $this->mergeFields($substrate, $localityAnalysis['substrate']);
			} else if($this->countPotentialHabitatWords($line) > 0 && strcasecmp($line, "Habitat") != 0) {//echo "\nline 14560, countPotentialHabitatWords: ".$line."\n";
				$firstPart = "";
				$lastPart = "";
				$pos = strpos($line, ";;;");
				if($pos !== FALSE) {
					$firstPart = trim(substr($line, 0, $pos));
					$lastPart = trim(substr($line, $pos+3));
				} else if(preg_match("/(.+); (.+)/", $line, $mats)) {
					$firstPart = trim($mats[1]);
					$lastPart = trim($mats[2]);
				} else if(preg_match("/(.*[A-Za-z]{5,}\)?)\\. (.+)/", $line, $mats)) {
					$firstPart = trim($mats[1]);
					$lastPart = trim($mats[2]);
				} else if(preg_match("/(.* ".$acceptableSmallWords.")\\. (.+)/", $line, $mats)) {
					$firstPart = trim($mats[1]);
					$lastPart = trim($mats[2]);
				} else if(preg_match("/(.*[A-Za-z]{4,}\)?), (.+)/", $line, $mats)) {
					$firstPart = trim($mats[1]);
					$lastPart = trim($mats[2]);
				}
				if(strlen($firstPart) > 0 && strlen($lastPart) > 0) {//echo "\nline 14501, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\n";
					if(preg_match("/^((?:(?:Fairly |Quite |Very |Not)?(?:(?:Un)?Common|Abundant) |Found |Loose |Growing )?On .+)/i", $lastPart)) {
						if(preg_match("/([A-Za-z ,-]+)[,.]? ((?:along|among|near|(?:with)?in|at|be(?:low|yond|neath|hind)|above|under) .+)/i", $lastPart, $mats)) {
							$substrate = $this->mergeFields($substrate, str_replace(";;;", ";", trim($mats[1])));
							$habitat = $this->mergeFields($habitat, str_replace(";;;", ";", trim($mats[2])));
						} else {
							$substrate = $this->mergeFields($substrate, str_replace(";;;", ";", $lastPart));
							$habitat = $this->mergeFields($habitat, str_replace(";;;", ";", $firstPart));
						}
					} else $habitat = $this->mergeFields($habitat, str_replace(";;;", ";", $line), " ");
				} else $habitat = $this->mergeFields($habitat, $line, " ");
			}
		}//echo "\nline 14484, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nhabitat: ".$habitat."\nverbatimAttributes: ".$verbatimAttributes."\n";
		$locality = str_replace
		(
			array("\r\n", "\n", "\r"),
			array(" ", " ", " "),
			trim($locality)
		);
		$localityPatStr = "/(.*?)(\\stype\\s)?\\b(?:L|(?:\|_))[o0]ca(?:[1!l]ity|ti[o0]n|\\.)[:;,)]?\\s(.+)/is";
		if(preg_match($localityPatStr, $locality, $locationMatches)) {
			if(count($locationMatches) == 4 && strlen(trim($locationMatches[2])) == 0) $locality = trim($locationMatches[1])." ".trim($locationMatches[3]);
		}// else if(preg_match($localityPatStr, $str, $locationMatches)) {
		//	if(count($locationMatches) == 4 && strlen(trim($locationMatches[2])) == 0) $locality = trim($locationMatches[3]);
		//}
		//echo "\nline 12700, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nhabitat: ".$habitat."\n";
		$lPat = "/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude)?\\b|quad\\b|[ec][lI!|][ec]v|Date|".
			"C[o0][lI!|](?:[lI!|]s?|[lI!|]ectors?|lected b[vyg]|[lI!|]?s)\\b|".
			"(?:H[o0]st\\s)?+Det(?:\\.|ermined by)|(?:THE )?NEW \\w{4} B[OD]TAN.+|(?:(?:Assoc(?:[,.]|".
			"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|spp[,.]?|with)[:;]?)|(?:along|together)\\swith)|(?:Substrat(?:um|e))|".
			"(?:[OQ0]?+".$possibleNumbers."|[Iil!|zZ12]".$possibleNumbers."|3[1Iil!|OQ0\]][ -])(?:".
			$possibleMonths.")\\b|\\d{1,3}(?:\\.\\d{1,6})?\\s?°)/i";
		$hPat = "/((?s).*?)\\b(?:[il1!|]at(?:[il1!|]tude)?\\b|quad|[ec][lI!|][ec]v|[lI!|]ocality|[lI!|]ocation|".
			"[lI!|]oc\\.|Date|Col(?:\\.|:|l[:.]|lectors?|lected|l?s[:.]?)|leg(?:it|\\.):?|Identif|Det(?:\\.|ermined by)|".
			"[lI!|]at[li!|]tude|(?:THE )?NEW\\s\\w{4}\\sBOTAN.+|(?:(?:Assoc(?:[,.]|".
			"[l!1|i]ated)\\s(?:Taxa|Spec[l!1|]es|P[l!1|]ants|spp[,.]?|with)[:;]?)|(?:along|together)\\swith))/i";
		if(strlen($locality) > 0 && strlen($verbatimElevation) == 0) {
			$elevArr = $this->getElevation($locality);
			$temp = $elevArr[0];
			if(strlen($temp) > 0) {
				$locality = trim($temp);
				$verbatimElevation = $elevArr[1];
				$temp = $elevArr[2];
				if(strlen($temp) > 3) {
					$temp = str_replace
					(
						array("\r\n", "\n", "\r"),
						array(" ", " ", " "),
						trim($temp)
					);
					if($this->countPotentialHabitatWords($temp) > 0) {
						if(strlen($habitat) < 3) $habitat = $temp;
						else if(stripos($habitat, $temp) === FALSE) $habitat = trim($habitat, " :;.,").", ".$temp;
					}
				}
			}//echo "\nline 12729, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\n";

			if($this->isCompleteGarbage($locality)) $locality = "";
			else $locality = $this->terminateField($locality, $lPat);
			//echo "\nline 12733, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nhabitat: ".$habitat."\n";
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
				if(is_numeric($habitat)) $habitat = "";
			}
		}//echo "\nline 12752, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\n";
		if(strlen($verbatimElevation) == 0) {
			$elevArr = $this->getElevation($str);
			$temp = $elevArr[1];
			if(strlen($temp) > 0) $verbatimElevation = $temp;
		}
		if(preg_match("/\\bQu[ao][ad]\\.?(?:\\sM[ao]p)?[.;:]?\\s(.*)/i", $str, $quadMatches)) {//$i=0;foreach($quadMatches as $quadMatche) echo "\nline 14019, quadMatches[".$i++."] = ".$quadMatche."\n";
			$georeferenceRemarks = "Quad. Map: ".trim($quadMatches[1]);
			$mLength = strlen($georeferenceRemarks);
			if($mLength > 12) {
				while(preg_match("/(.+) (?:C[0o][il|!1]{2}(?:[.,]|ect)|[ec][il|!1][ec]v(?:[.,]|at[il|!1][0o]n)|Date)/i", $georeferenceRemarks, $mats)) $georeferenceRemarks = trim($mats[1]);
				$datePatternStr = "/(?:(?(?=\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ]?(?:\\d{4})))".
					"\\b(\\d{1,2})[-\\s]?((?i)".$possibleMonths.")[- ]?(\\d{4})|".
					"\\b((?i)".$possibleMonths.")\\b,?\\s?(\\d{4}))/s";
				if(preg_match($datePatternStr, $georeferenceRemarks, $dateMatches)) $georeferenceRemarks = trim($dateMatches[1]);
				$commaPos = strpos($georeferenceRemarks, ",");
				if($commaPos !== FALSE && $commaPos<$mLength-1) {
					$rest = trim(substr($georeferenceRemarks, $commaPos+1));
					$georeferenceRemarks = trim(substr($georeferenceRemarks, 0, $commaPos));
					if(preg_match("/(.+)[;:] /", $rest, $mats2)) $rest = trim($mats2[1]);
					else {
						$pos = strpos($rest, " ");
						if($pos !== FALSE) $rest = trim(substr($rest, 0, $pos));
					}
					if(strlen($stateProvince) > 1) {
						if(strcasecmp($rest, $stateProvince) == 0) $georeferenceRemarks = $georeferenceRemarks.", ".$rest;
					} else {
						$sp = $this->getStateOrProvince($rest);
						if(count($sp) > 0) {
							$stateProvince = $sp[0];
							$country = $sp[1];
						} else if(strlen($country) > 0) {
							$states = $this->getStatesFromCountry($country);
							foreach($states as $state) if(strcasecmp($state, $rest) == 0) $stateProvince = $rest;
						}
					}
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
										$eventDate['day'] = $day;
										$eventDate['month'] = $dateMatches[2];
										$eventDate['year'] = $dateMatches[3];
									} else {
										$eventDate['month'] = $dateMatches[5];
										$eventDate['year'] = $dateMatches[6];
									}
								}
							}
							if(strlen($stateProvince) == 0) {
								$sp = $this->getStateOrProvince($rest);
								if(count($sp) > 0) {
									$stateProvince = $sp[0];
									$country = $sp[1];
								} else if(strlen($country) > 0) {
									$states = $this->getStatesFromCountry($country);
									foreach($states as $state) if(strcasecmp($state, $rest) == 0) $stateProvince = $rest;
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
		}//echo "\nline 14653, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
		$datePat = "/(?:(?(?=(?:.+)\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ](?:\\d{4})))".
			"(.+)\\b(?:\\d{1,2})[- ]?(?:(?i)".$possibleMonths.")[- ](?:\\d{4})|".
			"(?=(?:.+)\\b(?:(?i)".$possibleMonths.")\\s(?:\\d{1,2}),?\\s(?:\\d{4}))".
			"(.+)\\b(?:(?i)".$possibleMonths.")\\s(?:\\d{1,2}),?\\s(?:\\d{4})|".
			"(.+)\\b(?:(?i)".$possibleMonths."),?\\s(?:\\d{4}))/s";
		if(preg_match($datePat, $locality, $dateMatches)) {
			$countMatches = count($dateMatches);
			$locality = trim($dateMatches[1]);
			if(strlen($locality) == 0 && $countMatches > 2) {
				$locality = trim($dateMatches[2]);
				if(strlen($locality) == 0 && $countMatches > 3) $locality = trim($dateMatches[3]);
			}
		}//echo "\nline 12951, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
		if(strlen($scientificName) > 0 && strlen($locality) > 0) {
			$pos = strpos($locality, $scientificName);
			if($pos !== FALSE && $pos == 0) $locality = trim(substr($locality, strlen($scientificName)));
		}
		if(strlen($substrate) > 0) {
			if(strlen($locality) > 0) {
				$pos = strpos($locality, $substrate);
				if($pos !== FALSE) {
					if(strcasecmp($locality, $substrate) == 0) {
						$dotPos = strpos($substrate, ", ");
						if($dotPos !== FALSE) {
							$locality = trim(substr($substrate, $dotPos+2));
							$substrate = trim(substr($substrate, 0, $dotPos+1));
						} else {
							$dotPos = strpos($substrate, ". ");
							if($dotPos !== FALSE) {
								$locality = trim(substr($substrate, $dotPos+2));
								$substrate = trim(substr($substrate, 0, $dotPos+1));
							}
						}
					} else if($pos == 0) $locality = trim(substr($locality, strlen($substrate)));
					else {
						$temp = "On ".$substrate;
						$pos = stripos($locality, $temp);
						if($pos !== FALSE && $pos == 0) $locality = trim(substr($locality, strlen($temp)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					}
				}
			} else {
				$dotPos = strpos($substrate, ". ");
				if($dotPos !== FALSE) {
					$temp = trim(substr($substrate, $dotPos+2));
					if($this->countPotentialLocalityWords($temp) > 0) {
						$locality = $temp;
						$substrate = trim(substr($substrate, 0, $dotPos+1));
					}
				}
			}
		} else {//strlen(substrate) == 0
			$sPos = strpos($locality, ". On ");
			if($sPos !== FALSE) {
				$sub = trim(substr($locality, $sPos+2));
				if($this->countPotentialLocalityWords($sub) == 0) {
					$locality = trim(substr($locality, 0, $sPos));
					if(preg_match("/(.*[A-Za-z]{4,})\\. (.+)/", $sub, $mats)) {
						$temp = trim($mats[2]);
						if($this->countPotentialHabitatWords($temp) > 0) {
							if(strlen($habitat) == 0) $habitat = $temp;
							$substrate = trim($mats[1]);
						} else $substrate = $sub;
					} else $substrate = $sub;
				}
			} else if(strcasecmp(substr($locality, 0, 3), "On ") == 0) {
				$sub = $locality;
				if(preg_match("/(.+),\\s((?:in|along|within|at)\\s.*)/i", $sub, $mats)) {
					$temp = trim($mats[2]);
					$sub = trim($mats[1]);
					if($this->countPotentialLocalityWords($sub) == 0) {
						$substrate = $sub;
						if($this->countPotentialLocalityWords($temp) > 0) {
							$locality = $temp;
							$commaPos = strpos($locality, ", ");
							if($commaPos !== FALSE) {
								$temp2 = trim(substr($locality, $commaPos+2));
								$temp = trim(substr($locality, 0, $commaPos));
								if($this->countPotentialHabitatWords($temp) > 0 && $this->countPotentialLocalityWords($temp2) > 0) {
									$locality = $temp2;
									$habitat = $temp;
								}
							}
						} else if($this->countPotentialHabitatWords($temp) > 0) $habitat = $temp;
					}
				} else if(preg_match("/(.*[A-Za-z]{4,})\\. (.+)/", $sub, $mats)) {
					$temp = trim($mats[2]);
					$sub = trim($mats[1]);
					if($this->countPotentialLocalityWords($sub) == 0) {
						$substrate = $sub;
						$locality = $temp;
					} else if(preg_match("/(.*[A-Za-z]{4,})\\. (.+)/", $sub, $mats2)) {
						$temp = trim($mats2[2]);
						$sub = trim($mats2[1]);
						if($this->countPotentialLocalityWords($sub) == 0) {
							$substrate = $sub;
							$locality = $temp;
						}
					} else if(preg_match("/(.+), (.+)/", $sub, $mats2)) {
						$temp = trim($mats2[2]);
						$sub = trim($mats2[1]);
						if($this->countPotentialLocalityWords($sub) == 0) {
							$substrate = $sub;
							$locality = $temp;
						}
					}
				}
			} else if(preg_match("/(.*[A-Za-z]{4,}) (on .+) ((?:along|within) .+)/", $locality, $mats)) {//$i=0;foreach($mats as $mat) echo "\nmats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				$temp2 = trim($mats[2]);
				$temp3 = trim($mats[3]);
				$hCount1 = $this->countPotentialHabitatWords($temp);
				$hCount2 = $this->countPotentialHabitatWords($temp2);
				$hCount3 = $this->countPotentialHabitatWords($temp3);
				$lCount1 = $this->countPotentialLocalityWords($temp);
				$lCount2 = $this->countPotentialLocalityWords($temp2);
				$lCount3 = $this->countPotentialLocalityWords($temp3);
				//echo "\nhCount1: ".$hCount1."\nlCount1: ".$lCount1."\nhCount2: ".$hCount2."\nlCount2: ".$lCount2."\nhCount3: ".$hCount3."\nlCount3: ".$lCount3."\n";
				if($lCount2 == 0) {
					$substrate = $temp2;
					if($hCount1 > $lCount1) {
						if(strlen($habitat) == 0) $habitat = $temp;
						else if(stripos($habitat, $temp) === FALSE) $habitat = trim($habitat, " ;:,.").", ".$temp;
						$locality = "";
					} else if($lCount1 > 0) $locality = $temp;
					else $locality = "";
					if($hCount3 > $lCount3) {
						if(strlen($habitat) == 0) $habitat = trim($temp3, " ;:,.");
						else if(stripos($habitat, $temp3) === FALSE) $habitat = trim($habitat, " ;:,.").", ".$temp3;
					} else if($lCount3 > 0) {
						if(strlen($locality) == 0) $locality = $temp3;
						else if(stripos($locality, $temp3) === FALSE) $locality = trim($locality, " ;:,.").", ".$temp3;
					}
				}
			}
		}//echo "\nline 14298, habitat: ".$habitat."\nlocality: ".$locality."\nsubstrate: ".$substrate."\n";
		if((strlen($stateProvince) == 0 || strlen($country) == 0 ) && strlen($county) > 0) {
			$ps = $this->getStateFromCounty($county, $states);
			if(count($ps) > 0) {
				if(strlen($stateProvince) == 0) $stateProvince = $ps[0];
				if(strlen($country) == 0) $country = $ps[1];
			}
		}
		if(strlen($locality) > 0 && strlen($scientificName) > 0) {
			$pos = stripos($locality, $scientificName);
			if($pos !== FALSE && $pos == 0) $locality = substr($locality, strlen($scientificName));
		}

		if(strlen($substrate) > 0) {
			if(preg_match("/(.+?)[;,.]? ((?:Growing )?(?:along |together )?with[;,]?) (.+)/i", $substrate, $mats)) {
				$substrate = trim($mats[1]);
				$temp1 = trim($mats[3], " ,");
				$temp2 = $temp1;
				$isAssTaxaList = true;
				$pos = strpos($temp1, "&");
				$processed = false;
				if($pos !== FALSE) {
					$temp3 = trim(substr($temp1, 0, $pos));
					$temp4 = trim(substr($temp1, $pos+1));
					$processed = true;
					if(strlen($temp4) > 3 && $this->processSciName(trim($temp4, " \"\',")) == null) $isAssTaxaList = false;
					else $temp1 = $temp3;
				} else if(strpos($temp1, " and ") !== FALSE) {
					$processed = true;
					$temps = array_reverse(explode(" and ", $temp1));
					foreach($temps as $temp) {
						if(strlen($temp) > 3) {
							if($this->processSciName(trim($temp, " \"\',")) == null) {
								$isAssTaxaList = false;
								break;
							} else $temp1 = trim(substr($temp1, 0, " and ".stripos($temp1, $temp)));
						}
					}
				}
				if($isAssTaxaList && strpos($temp1, ", ") !== FALSE) {
					$processed = true;
					$temps = array_reverse(explode(", ", $temp1));
					foreach($temps as $temp) {
						if(strlen($temp) > 3) {
							if($this->processSciName(trim($temp, " \"\',")) == null) {
								$isAssTaxaList = false;
								break;
							}
						}
					}
				}
				if(!$processed) if($this->processSciName(trim($temp2, " \"\',")) == null) $isAssTaxaList = false;
				if($isAssTaxaList) $associatedTaxa = $this->mergeFields($associatedTaxa, $temp2, ", ");
				else $habitat = $this->mergeFields($habitat, trim($mats[2])." ".$temp2, ", ");
			}
			$subPat = "/((?s).*)\\b(?:[il1!|]at(?:[il1!|]tude|\\.|\\b)|quad|[ec][lI!|][ec]v|Date|Col(?:\\.|:|ls?[:.]|lectors?|lected|l?s[:.])|".
				"(?:H[o0]st\\s)?+Det(?:\\.|ermined by)|(?:THE )?NEW \\w{4} B[OD]TAN.+|State|(?:".$possibleNumbers."{2,4}[ -])?(?:".$possibleMonths.")";
			if(strlen($county) > 0) $subPat .= "|".preg_quote($county, '/')."\\b";
			if(strlen($stateProvince) > 0) $subPat .= "|".preg_quote($stateProvince, '/')."\\b";
			$subPat .= ")/i";
			$substrate = trim($this->terminateField($this->terminateField(trim($substrate), $subPat), $subPat));
			if(strcasecmp($substrate, "on") == 0) $substrate = "";
		}
		if(strlen($habitat) > 0) {
			if(preg_match("/^Habitat[;:.,]?(.*)/i", $habitat, $mats)) $habitat = trim($mats[1]);
		}
		if(strlen($county) > 0 && strlen($stateProvince) > 0 && strcasecmp($county, $stateProvince) != 0) {
			$pos = stripos($county, $stateProvince);
			if($pos !== FALSE) $county = trim(substr($county, $pos+strlen($stateProvince)), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		}
		if(preg_match("/(.*)[0OD]at[ec]/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
		$exsNumber = trim($exsNumber, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-");
		$recordNumber = trim($recordNumber, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-");
		//echo "\nline 14373, exsNumber: ".$exsNumber."\nrecordNumber: ".$recordNumber."\ntempRecordNumber: ".$tempRecordNumber."\n";
		if($ometid && strlen($recordNumber) == 0) {
			if(strcasecmp($exsNumber, $tempRecordNumber) != 0) $recordNumber = $tempRecordNumber;
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($stateProvince, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'georeferenceRemarks' => trim($georeferenceRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'occurrenceRemarks' => trim($occurrenceRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($locality, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRemarks' => trim($taxonRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_"),
			'recordNumber' => $recordNumber,
			'exsNumber' => $exsNumber,
			'ometid' => trim($ometid, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_"),
			'verbatimElevation' => trim($verbatimElevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'eventDate' => $eventDate,
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedById' => $recordedById,
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'dateIdentified' => $dateIdentified,
			'otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	protected function isKryptogamaeExsiccatiVindobonensiLabel($s) {
		if(preg_match("/.*[CGK]r[yv][ypg]to[ypg]ama[ce] [ce]xsi[ce]{2}ata[ce] [ce]d[1Il!|]ta[ce].*/is", $s)) return true;
		else if(preg_match("/Mus[ec][0o] H[1Il!|][s5]t[.,] Natur[.,] V[1Il!|]nd[0o]b[0o]n.+/is", $s)) return true;
		else return false;
	}

	protected function doKryptogamaeExsiccatiVindobonensiLabel($s) {//echo "\nDoin' doKryptogamaeExsiccatiVindobonensiLabel\n";
		$pattern =
			array
			(
				"/[CGK]r[yv][ypg]to[ypg]ama[ce] [ce]xsi[ce]{2}ata[ce] [ce]d[1Il!|]ta[ce] a Mus[ce][o0] Hist[.,] Natur[.,] Vind[o0]b[o0]n[ce]nsi/is",
				"/[CGK]r[yv][ypg]to[ypg]ama[ce] [ce]xsi[ce]{2}ata[ce] [ce]d[1Il!|]ta[ce] a Mus[ce][o0] Hist[.,] Natur[.,] Vind[o0]b[o0]n.{0,3}/is",
				"/.{0,3}Mus[ec][0o] H[1Il!|][s5]t[.,] Natur[.,] V[1Il!|]nd[0o]b[0o]n.{0,3}/is",
				"/ \(sect[.,] .{3,15}\)/i"
			);
		$replacement =
			array
			(
				"",
				"",
				"",
				""
			);
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 8296, s:\n".$s."\n";
		$ometid = "";
		$exsnumber = "";
		$scientificName = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$substrate = "";
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 9032, line: ".$line."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			$psn = $this->processSciName($line);
			if($psn != null) {
				if(array_key_exists ('scientificName', $psn)) {
					$scientificName = $psn['scientificName'];
					$s = trim(str_replace($scientificName, "", $s));
				}
				if(array_key_exists ('infraspecificEpithet', $psn)) {
					$infraspecificEpithet = $psn['infraspecificEpithet'];
					$s = trim(str_replace($infraspecificEpithet, "", $s));
				}
				if(array_key_exists ('taxonRank', $psn)) {
					$taxonRank = $psn['taxonRank'];
					$s = trim(str_replace($taxonRank, "", $s));
				}
				if(array_key_exists ('verbatimAttributes', $psn)) {
					$verbatimAttributes = $psn['verbatimAttributes'];
					$s = trim(str_replace($verbatimAttributes, "", $s));
				}
				if(array_key_exists ('associatedTaxa', $psn)) {
					$associatedTaxa = $psn['associatedTaxa'];
					$s = trim(str_replace($associatedTaxa, "", $s));
				}
				if(array_key_exists ('substrate', $psn)) {
					$substrate = $psn['substrate'];
					$s = trim(str_replace($substrate, "", $s));
				}
				if(array_key_exists ('recordNumber', $psn)) {
					$exsnumber = $psn['recordNumber'];
					$s = trim(str_replace($exsnumber, "", $s));
					//$s = trim(str_replace($line, "", $s));
					if(strlen($exsnumber) > 0) break;
				}
			}
		}
		$iExsNumber = 0;
		$exsnumber = str_replace(" ", "", $exsnumber);
		if(is_numeric($exsnumber)) $iExsNumber = intval($exsnumber);
		else if(strlen($exsnumber) > 1) {//remove the last character and see if the remainder is numeric
			$temp = trim(substr($exsnumber, 0, strlen($exsnumber)-1));
			if(is_numeric($temp)) $iExsNumber = intval($temp);
		}
		if($iExsNumber > 0) {
			if($iExsNumber > 3200) $ometid = "343";
			else if($iExsNumber > 2600) $ometid = "222";
			else if($iExsNumber > 400) $ometid = "221";
			else if($iExsNumber > 100) $ometid = "220";
			else $ometid = "78";
		} else $ometid = "78";
		$fields = array();
		$fields['scientificName'] = $this->formatSciName($scientificName);
		$fields['exsNumber'] = $exsnumber;
		$fields['infraspecificEpithet'] = $infraspecificEpithet;
		$fields['taxonRank'] = $taxonRank;
		$fields['verbatimAttributes'] = $verbatimAttributes;
		$fields['associatedTaxa'] = $associatedTaxa;
		$fields['substrate'] = $substrate;
		return $this->doGenericLabel($s, $ometid, $fields);
	}

	private function getTaxonOfHeaderInfo($str) {
		$statePatStr = "/((?s).*)?((?:[EP][L1!|]ANT[5S]|(?:ASC[O0D]MYC[EC]T[EC]S[5S])|(?:FL[O0D]RA)|(?:F(?:U|(?:LI))N[EGC][Il!1.,])|".
			"(?:.{1,2}[I|!l][ECU](?:H|(?:I-?I)|TI)EN'?[5S]|FL[O0D]RA)|(?:CRYPT[O0DQU]GAM[5S])|".
			"(?:BRY[O0DQU](?:(?:FL[O0D]RA)|(?:PHYT(?:A|[EC][5S]))))|(?:M[O0][5S]{2}E[5S])|".
			"(?:MU[5S][CG][l1|I!])|(?:HEPAT[L1!|]CA.)|(?:P[L1!|]ANT[5S])|(?:[5S]PHAGNA))".
			"(?:\\r\\n|\\n|\\r|\\s)?[DOU0][EFPKI1][RFPN]?)(.*)(?:\\r\\n|\\n|\\r)((?s).*)/i";
		if(preg_match($statePatStr, $str, $matches)) {
			if(preg_match($statePatStr, $matches[1], $matches2)) {
				return array($matches2[1], $matches2[3], $matches2[4].$matches[2].$matches[3].$matches[4]);
			} else return array($matches[1], $matches[3], $matches[4]);
		}
		return null;
	}

	private function processTaxonOfHeaderInfo($matches) {
		$country = "";
		$location = "";
		$state_province = "";
		$county = "";
		$states = array();
		$startOfFile = trim($matches[0]);
		$endOfLine = trim($matches[1]);
		$endOfFile = trim($matches[2]);
		$workingEndOfFile = $endOfFile;
		$sLen = strlen($endOfLine);
		//echo "\nline 339, startOfFile: ".$startOfFile."\n\tendOfLine: ".$endOfLine."\n\tendOfFile: ".$endOfFile."\n";
		//if there is nothing after the "Taxons of", get it from the beginning of the next line
		if($sLen == 0) {
			$eolPos = strpos($endOfFile, "\n");
			if($eolPos !== FALSE) {
				$firstLine = trim(substr($endOfFile, 0, $eolPos));
				$workingEndOfFile = trim(substr($endOfFile, $eolPos+1));
				$commaPos = strpos($firstLine, ",");
				if($commaPos !== FALSE) {
					$firstPart = trim(substr($firstLine, 0, $commaPos));
					$lastPart = trim(substr($firstLine, $commaPos+1));
					$spacePos = strpos($lastPart, " ");
					if($spacePos !== FALSE) {
						$endOfLine = $firstPart.", ".substr($lastPart, 0, $spacePos);
						$workingEndOfFile = substr($lastPart, $spacePos+1)."\n".$workingEndOfFile;
					} else $endOfLine = $firstLine;
				} else {
					$spacePos = strpos($firstLine, " ");
					if($spacePos !== FALSE) {
						$endOfLine = trim(substr($firstLine, 0, $spacePos));
						$workingEndOfFile = substr($firstLine, $spacePos+1)."\n".$workingEndOfFile;
					} else $endOfLine = $firstLine;
				}
			}
		//if there is an isolated character at the end of the line, remove it
		} else if($sLen > 2 && substr($endOfLine, $sLen-2, 1) == " ") $endOfLine = trim(substr($endOfLine, 0, $sLen-1));
		//echo "\nline 398, startOfFile: ".$startOfFile."\n\nendOfLine: ".$endOfLine."\n\nendOfFile: ".$endOfFile."\n\nworkingEndOfFile: ".$workingEndOfFile."\n\n";
		$commaPos = strpos($endOfLine, ',');
		$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		if($commaPos !== FALSE) {
			$firstPart = trim(substr($endOfLine, 0, $commaPos));
			$secondPart = trim(substr($endOfLine, $commaPos+1));
			$sp = $this->getStateOrProvince($firstPart);
			if(count($sp) > 0) {//$i=0;foreach($sp as $p) echo "line 406, sp[".$i++."] = ".$p."\n";
				$state_province = $sp[0];
				$country = $sp[1];
				$pos = stripos($firstPart, $state_province);
				if($pos !== FALSE) {
					if($pos == 0) $firstPart = substr($firstPart, strlen($state_province));
					else $firstPart = substr($firstPart, 0, $pos);
				}
				$temp = "";
				$pos = stripos($endOfLine, $state_province);
				if($pos !== FALSE) {
					$temp = trim(substr($endOfLine, $pos+strlen($state_province)));
					if(strlen($temp) <= 3) $temp = trim(substr($endOfLine, 0, $pos));
				}
				if(strlen($temp) > 3) $endOfFile = trim($temp." ".$endOfFile);
				$countyMatches = $this->findCounty($firstPart, $state_province);
				if($countyMatches != null) {
					$county = trim($countyMatches[1]);
				} else {
					$countyMatches = $this->findCounty($secondPart, $state_province);
					if($countyMatches != null) $county = trim($countyMatches[1]);
				}
			} else {
				$sp = $this->getStateOrProvince($secondPart);
				if(count($sp) > 0) {
					$state_province = $sp[0];
					$country = $sp[1];
					$countyMatches = $this->findCounty($firstPart, $state_province);
					if($countyMatches != null) {
						$state_province = trim($countyMatches[4]);
						$county = trim($countyMatches[1]);
						$country = trim($countyMatches[2]);
					}
				}
				if(strlen($county) == 0) {
					$countyMatches = $this->findCounty($firstPart);
					if($countyMatches != null) {
						$state_province = trim($countyMatches[4]);
						$county = trim($countyMatches[1]);
						$country = trim($countyMatches[2]);
					} else {
						$countyMatches = $this->findCounty($secondPart);
						if($countyMatches != null) {
							$state_province = trim($countyMatches[4]);
							$county = trim($countyMatches[1]);
							$country = trim($countyMatches[2]);
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
					}
				}
			}
		} else {
			$ps = $this->getStateOrProvince(trim($endOfLine));
			if(count($ps) > 0) {//$i=0;foreach($ps as $p) echo "line 483, ps[".$i++."] = ".$p."\n";
				$state_province = $ps[0];
				$l = strlen($state_province);
				if($l > 0) {
					$pos = stripos($endOfLine, $state_province);
					if($pos !== FALSE) {
						$rest = trim(substr($endOfLine, $pos+$l));
						if(strlen($rest) > 2) $endOfFile = $rest."\n".$workingEndOfFile;
					}
				}
				$country = $ps[1];
				if(strcasecmp($state_province, $endOfLine) != 0) {
					if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))/i", $endOfLine, $cMatches)) {
						$countyMatches = $this->findCounty($endOfLine, $state_province);
						if($countyMatches != null) {
							$state_province = trim($countyMatches[4]);
							$county = trim($countyMatches[1]);
							$country = trim($countyMatches[2]);
							$temp = trim($countyMatches[3]);
							if(strlen($temp) > 3) $endOfFile = trim($temp."\n".$endOfFile);
						}
					}
				}
				if(strlen($county) == 0) {
					$countyMatches = $this->findCounty($endOfFile, $state_province);
					if($countyMatches != null) {
						$state_province = trim($countyMatches[4]);
						$county = trim($countyMatches[1]);
						$country = trim($countyMatches[2]);
					}
				}
			} else {
				$country = $this->getCountry($endOfLine);
				if(strlen($country) == 0) {
					if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))/i", $endOfLine)) {
						$countyMatches = $this->findCounty($endOfLine, $state_province);
						if($countyMatches != null) {
							$state_province = trim($countyMatches[4]);
							$county = trim($countyMatches[1]);
							$country = trim($countyMatches[2]);
							if(strlen($state_province) == 0 && strlen($country) > 0) $states = $this->getStatesFromCountry($country);
							$temp = trim($countyMatches[3]);
							if(strlen($temp) > 3) $endOfFile = trim($temp."\n".$endOfFile);
						}
					}
					else {
						$ps = $this->getStateOrProvince(trim($endOfLine));
						if(count($ps) > 0) {
							$state_province = $ps[0];
							$country = $ps[1];
							$pos = stripos($endOfLine, $state_province);
							$temp = "";
							if($pos !== FALSE) {
								$temp = trim(substr($endOfLine, $pos+strlen($state_province)));
								if(strlen($temp) <= 3) $temp = trim(substr($endOfLine, 0, $pos));
							}
							if(strlen($temp) > 3) $endOfFile = trim($temp."\n".$endOfFile);
						}
					}
				} else if($country != $endOfLine) {
					if(preg_match("/(.*)\\s(?:Co(?:\\.|unty)|Par[il!|]sh|B[o0]r[o0]ugh|Dist(?:\\.|rict))/i", $endOfLine, $cMatches)) {
						$countyMatches = $this->findCounty($endOfLine, $state_province);
						if($countyMatches != null) {
							$state_province = trim($countyMatches[4]);
							$county = trim($countyMatches[1]);
						}
					} else {
						$l = strlen($country);
						if($l > 0) {
							$pos = stripos($endOfLine, $country);
							if($pos !== FALSE) {
								$rest = trim(substr($endOfLine, $pos+$l));
								if(strlen($rest) > 2) $endOfFile = $rest."\n".$workingEndOfFile;
							}
						}
					}
					if(strlen($state_province) == 0) $states = $this->getStatesFromCountry($country);
				} else if(strlen($state_province) == 0) $states = $this->getStatesFromCountry($country);
			}
		}
		if(strlen($county) == 0) {
			$countyMatches = $this->findCounty($endOfFile, $state_province);
			if($countyMatches != null) {
				$state_province = trim($countyMatches[4]);
				$county = trim($countyMatches[1]);
				$country = trim($countyMatches[2]);
			}
		}//echo "\nline 572, country: ".$country."\n\tstate_province: ".$state_province."\n\tcounty: ".$county."\n\tendOfFile: ".$endOfFile."\n";
		return array
		(
			'country' => $country,
			'stateProvince' => $state_province,
			'county' => $county,
			'states' => $states,
			'endOfFile' => $endOfFile
		);
	}

	protected function processSciName($name) {//echo "\nInput to processSciName: ".$name."\n";
		if($name) {
			$name = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $name));
			$recordNumber = "";
			if(preg_match("/^No[.,*#-]?\\s([UIO!SQil0-9]{1,6}[a-z]?)[.,*#]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^No[.,*#-]\\s?([UIO!SQil0-9]{1,6}[a-z]?)[.,*#]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^([0-9]{1,4}) ?([A-Za-z])[.,*#-]\\s(.*)/", $name, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 526, mats[".$i++."] = ".$mat."\n";
				$recordNumber = trim($mats[1]).trim($mats[2]);
				$name = trim($mats[3]);
			} else if(preg_match("/^([0-9]{1,4} ?)[.,*#-]\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/^([UIO!SQl0-9]{1,6}[a-z]?)[.,*#-]\\s(.*)/", $name, $mats)) {
				$recordNumber = trim(str_replace(array("l", "!", "I", "U", "O", "Q", "S"), array("1", "1", "1", "4", "0", "0", "5"), $mats[1]));
				$name = trim($mats[2]);
			} else if(preg_match("/^([0-9]{2,6}[A-Za-z]?)[.,*#-]?\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/^[^0-9]{0,6}+([0-9]{1,3}[A-Za-z]?)[.,*#-]\\s(.*)/", $name, $mats)) {
				$recordNumber = trim($mats[1]);
				$name = trim($mats[2]);
			} else if(preg_match("/^([A-Za-z]+)\\s\([A-Za-z]+\)\\s([A-Za-z]+.*)/", $name, $mats)) {
				$name = trim($mats[1])." ".trim($mats[2]);
			} else if(preg_match("/^[A-Za-z]+ \(([A-Za-z]+ [A-Za-z]+)\)$/", $name, $mats)) {
				$name = trim($mats[1]);
			}
			if($this->isPossibleSciName($name)) return array('scientificName' => $name, 'recordNumber' => $recordNumber);
			else {//echo "\nline 590, name: ".$name.", recordNumber: ".$recordNumber."\n";
				$results = array();
				$foundSciName = false;
				$words = array_reverse(explode(" ", $name));
				$wordcount = count($words);
				if($wordcount > 3) {
					if($this->containsVerbatimAttribute($name)) {
						$potentialVerbatimAtt = "";
						$potentialSubstrate = "";
						$potentialSciName = "";
						if(stripos($name, " strain") !== FALSE) {
							if(strcasecmp(trim($words[0]), "strain") == 0) {
								$potentialSciName = trim($words[$wordcount-1]." ".$words[$wordcount-2]);
								if($this->isPossibleSciName($potentialSciName)) {
									$foundSciName = true;
									$results['scientificName'] = $potentialSciName;
									$results['recordNumber'] = $recordNumber;
									$results['verbatimAttributes'] = trim(substr($name, strpos($name, $potentialSciName)+strlen($potentialSciName)));
								} else if($wordcount < 5) $results['verbatimAttributes'] = $name;
							} else if(preg_match("/(.+)\\s(contain(?:s|ing))\\s(.+)/i", $name, $mats)) {
								$potentialSciName = trim($mats[1]);
								if($this->isPossibleSciName($potentialSciName)) {
									$foundSciName = true;
									$results['scientificName'] = $potentialSciName;
									$results['recordNumber'] = $recordNumber;
									$results['verbatimAttributes'] = trim($mats[2]);
								} else $results['verbatimAttributes'] = trim($mats[2]);
							}
						} else if($wordcount > 5) {
							$firstPart = "";
							$endPart = "";
							if(preg_match("/^(.+)[;,] ?(on .+)$/i", $name, $mats)) {
								$firstPart = trim($mats[1]);
								$endPart = trim($mats[2]);
							} else if(preg_match("/^(.*[A-Za-z]{3,}) (on .+)$/i", $name, $mats)) {
								$firstPart = trim($mats[1]);
								$endPart = trim($mats[2]);
							}
							if(strlen($firstPart) > 0 && strlen($endPart) > 0) {
								if($this->countPotentialLocalityWords($endPart) == 0) {
									$potentialSubstrate = $endPart;
									if($this->containsVerbatimAttribute($endPart)) {
										if(preg_match("/(on .+) ((?:UV|[KPC])[+-].*)/", $endPart, $mats2)) {
											$potentialVerbatimAtt = trim($mats2[2]);
											$potentialSubstrate = trim($mats2[1]);
										} else if(preg_match("/(on .+); (.*)/", $endPart, $mats2)) {
											$temp = trim($mats2[2]);
											if($this->containsVerbatimAttribute($temp)) {
												$potentialSubstrate = trim($mats2[1]);
												$potentialVerbatimAtt = $temp;
											}
										}
									}
								}
								if($this->containsVerbatimAttribute($firstPart)) {
									if(preg_match("/^(.+) ((?:UV|[KPC])[+-].*)$/", $firstPart, $mats2)) {
										$potentialVerbatimAtt = trim($mats2[2]);
										$potentialSciName = trim($mats2[1]);
									}
								}
								if(strlen($potentialSciName) == 0) $potentialSciName = $firstPart;
							} else if(preg_match("/(.+) ((?:UV|[KPC])[+-].*)/", $name, $mats)) {
								$potentialVerbatimAtt = trim($mats[2]);
								$potentialSciName = trim($mats[1]);
							}
						} else if(preg_match("/(.+) ((?:UV|[KPC])[+-].*)/", $name, $mats)) {
							$potentialVerbatimAtt = trim($mats[2]);
							$potentialSciName = trim($mats[1]);
						}
						if(strlen($potentialSciName) == 0) $potentialSciName = trim($words[$wordcount-1]." ".$words[$wordcount-2]);
						if(!$foundSciName) {
							if($this->isPossibleSciName($potentialSciName)) {
								$foundSciName = true;
								$results['scientificName'] = $potentialSciName;
								$results['recordNumber'] = $recordNumber;
								if(strlen($potentialVerbatimAtt) > 0) $results['verbatimAttributes'] = trim($this->removeAuthority($potentialVerbatimAtt, $potentialSciName));
								if(strlen($potentialSubstrate) > 0) $results['substrate'] = $potentialSubstrate;
								if(strlen($potentialVerbatimAtt) == 0 || strlen($potentialVerbatimAtt) == 0) {
									$verbatimAttributes = trim(substr($name, strpos($name, $potentialSciName)+strlen($potentialSciName)));
									if(preg_match("/(.+), ?(on .+)/i", $verbatimAttributes, $mats)) {
										$temp = trim($mats[2]);
										if($this->countPotentialLocalityWords($temp) == 0) {
											if(strlen($potentialVerbatimAtt) == 0) $results['verbatimAttributes'] = trim($this->removeAuthority(trim($mats[1]), $potentialSciName));
											if(strlen($potentialSubstrate) == 0) $results['substrate'] = $temp;
										}
									}
								}
							}
						}
					}
				}//foreach($results as $k => $v)  echo "\nline 641, ".$k.": ".$v."\n";
				if(!$foundSciName) {
					$potentialSciName = "";
					$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))|May|Jun[.e]|Jul[.y]|Aug(?:\\.|(?:ust))|Sep(?:\\.|(?:t\\.?)|(?:tember))|Oct(?:\\.|(?:ober))|Nov(?:\\.|(?:ember))|Dec(?:\\.|(?:ember))";
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
						if(preg_match("/(.*?)((?:\\s(?:Found|(?:Fairly |Very )?Common|Loose|Abundant))?\\son\\s.*)/i", $temp, $mats2)) {
							$substrate = trim($mats2[2]);
							//if(!preg_match("/\\b(?:HWY|Highway|road)\\b/i", $substrate)) $results['substrate'] = $substrate;
							if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
							$temp = trim($mats2[1]);
							$spacePos = strpos($temp, " ");
							if($spacePos !== FALSE) $temp = strtolower(substr(trim($temp), 0, $spacePos))." ".trim(substr($temp, $spacePos+1));
						} else if(preg_match("/(.*)(\\sSyn(?:\\.|onym|type) ?of\\s.*)/i", $temp, $mats2)) {
							$results['taxonRemarks'] = trim($mats2[2]);
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
				if(!$foundSciName && preg_match("/(.*?)(\\sSyn(?:\\.|onym|type) ?of\\s.*)/i", $name, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 3101, mats[".$i++."] = ".$mat."\n";
					$potentialSciName = trim($mats[1]);
					if($this->isPossibleSciName($potentialSciName)) {
						$foundSciName = true;
						$results['scientificName'] = $potentialSciName;
						$results['recordNumber'] = $recordNumber;
					}
					$temp = trim($mats[2]);
					if(preg_match("/(.*)\\s(var[.,*#»]?|ssp[.,*#»]?|subsp[.,*#»]?) (.*)/i", $temp, $mats2)) {
						$results['taxonRemarks'] = $mats2[1];
						$temp = trim($mats2[3]);
					} else if(preg_match("/(.*)\\s(v[.,*#»]|f[.,*#»])\\s(.*)/", $temp, $mats2)) {
						$results['taxonRemarks'] = $mats2[1];
						$temp = trim($mats2[3]);
					} else {
						$results['taxonRemarks'] = $temp;
						$temp = "";
					}
					if(strlen($temp) > 3) {
						$results['taxonRank'] = str_replace(array("»", "*", "#", ",", "v.", "ssp"), array(".", ".", ".", ".", "var.", "subsp"), strtolower($mats2[2]));
						$results['infraspecificEpithet'] = $temp;
					}
				}
				if(!$foundSciName && preg_match("/(.*)(?:(?<! UV| [KPC])\+|(?:\\salong|\\stogether|\\sfound|\\sassociated)?\\swith\\s)(.+)/i", $name, $mats)) {
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
									//if(!preg_match("/\\b(?:HWY|Highway|road)\\b/i", $substrate)) $results['substrate'] = $substrate;
									if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
								}
							}
						}
						if(strlen($associatedTaxa) > 0) $results['associatedTaxa'] = $associatedTaxa;
						$results['recordNumber'] = $recordNumber;
					}
				}
				if(!$foundSciName && preg_match("/(.{4,}?)((?:\\s(?:F[o0]und|(?:Fairly\\s)?C[o0]mm[o0]n(?:\\sand\\sabundant)?|L[o0]{2}se|[o0]ccasi[o0]na[lI1!|]))?+\\son\\s.*)/i", $name, $mats)) {
					$potentialSciName = $mats[1];
					if($this->isPossibleSciName($potentialSciName)) {
						$foundSciName = true;
						$results['scientificName'] = $potentialSciName;
						$results['recordNumber'] = $recordNumber;
						$substrate = trim($mats[2]);
						if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
						else {
							$pos = strpos($substrate, ";");
							if($pos !== FALSE) {
								$substrate = trim(substr($substrate, 0, $pos));
								if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) $results['substrate'] = $substrate;
							}
							if(!array_key_exists('substrate', $results)) {
								while(preg_match("/(.+\\s[a-zA-Z]{3,})\\..+/", $substrate, $mats)) {
									$substrate = trim($mats[1]);
									if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) {
										$results['substrate'] = $substrate;
										break;
									}
								}
								if(!array_key_exists('substrate', $results)) {
									$pos = strrpos($substrate, ",");
									while($pos !== FALSE) {
										$substrate = trim(substr($substrate, 0, $pos));
										if($this->countPotentialLocalityWords($substrate) == 0 && !preg_match("/\\b(?:".$possibleMonths.")[ -,]/", $substrate)) {
											$results['substrate'] = $substrate;
											break;
										}
										$pos = strrpos($substrate, ",");
									}
								}
							}
						}
					}
				}
				if(!$foundSciName) {//echo "\nline 828, name: ".$name.", recordNumber: ".$recordNumber."\n";
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

	protected function removeAuthority($line, $sciname) {
		if($line) {
			if($sciname) {
				$sql = "SELECT author FROM taxa t WHERE t.sciName = '".$sciname."'";
				if($rs = $this->conn->query($sql)) {
					$author = "";
					if($r = $rs->fetch_object()) $author = $r->author;
					else {
						$sql = "SELECT author FROM taxa t WHERE t.sciName = '".$this->formatSciname($sciname)."'";
						if($rs = $this->conn->query($sql)) {
							if($r = $rs->fetch_object()) $author = $r->author;
						}
					}
					if(strlen($author) > 0) {
						//echo "\n".$author."\nline: ".$line."\n/\(?".$author." ?\)?(.*)/i\n";
						if(preg_match("/(.*)\(?".preg_quote($author, '/')." ?\)?(.*)/is", $line, $mats)) return trim(trim($mats[1])." ".trim($mats[2]));
						else {
							$author = preg_quote(str_replace(". ", ".", $author), '/');
							if(preg_match("/(.*)\(?".$author." ?\)?(.*)/is", $line, $mats)) return trim(trim($mats[1])." ".trim($mats[2]));
						}
					}
				}
			}
			return $line;
		} return "";
	}

	protected function isPossibleSciName($name) {//echo "\nInput to isPossibleSciName: ".$name."\n";
		$name = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $name), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
		$numPat = "/(.*)\\s\\w\\s(.*)/";
		if(preg_match($numPat, $name, $ns)) $name = trim($ns[1])." ".trim($ns[2]);
		$fPos = strpos($name, "¢");
		if($fPos !== FALSE && $fPos < 9) $name = trim(substr($name, $fPos+1));
		$name = str_ireplace(" cf. ", " ", $name);
		$name = str_ireplace(" cf ", " ", $name);
		//echo "\nInput to isPossibleSciName2: ".$name."\n";
		if(strlen($name) > 2 && !preg_match("/\\b(?:on|var\\.?|strain|contains|subsp\\.?|ssp\\.?|f\\.)\\b/i", $name)) {
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
						&& strcasecmp($word1, "clara") != 0 && strcasecmp($word1, "barbara") != 0
						&& strcasecmp($word1, "superior") != 0 && strcasecmp($word1, "minnesota") != 0
						 && strcasecmp($word1, "acre") != 0 && strcasecmp($word1, "sterile") != 0) {
						$sql = "SELECT * FROM taxa t WHERE t.unitName2 = '".$word1."'";
						if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
					}
				}
				if(count($words) > 3 && (strlen($word0) < 4 || strlen($word1) < 4 || strlen($words[2]) < 4)) {
					$name2 = str_replace(array("\"", "'"), "", $word0.trim($word1).trim($words[2]));
					//echo "\nline 760, sql: ".$sql."\n";
					$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
					if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
				}
				if(count($words) < 6) {
					$name2 = trim(str_replace(array("1", "!", "|", "5", "0", "\"", "'"), array("l", "l", "l", "S", "O", "", ""), $word0." ".trim($word1)));
					$sql = "SELECT * FROM taxa t WHERE t.sciName = '".$name2."'";
					if($r2s = $this->conn->query($sql)) if($r2s->num_rows > 0) return true;
				}
				if(preg_match("/^\\w{3,24}\\s\\w{3,24}\\s\([a-zA-Z01üé&. ]{1,14}\\.?\\s?\)\\s?(?:[A-Z]\\.\\s){0,2}[a-zA-Z01ü&. ]{2,19}\\.?$/", $name)) {
					//echo "\nline 1083, match\n";
					return true;
				}
				if(preg_match("/^[a-zA-Z01]{3,24}\\sspp?\\./", $name)) return true;
			}
		}
		return false;
	}

	protected function formatSciName($scientificName) {
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

	protected function formatDate($date) {
		if(array_key_exists('year', $date)) {
			$result = $date['year'];
			if(array_key_exists('month', $date)) {
				$result .= "-".$date['month'];
				if(array_key_exists('day', $date)) $result .= "-".$date['day'];
				else $result .= "-00";
			} else $result .= "-00-00";
			return $result;
		}
		return "";
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

	protected function containsNumber($s) {
		return preg_match("/\\d+/", $s);
	}

	protected function containsText($s) {
		return preg_match("/[A-Za-z]+/", $s);
	}

	protected function isText($s) {
		$splitChars = str_split($s);
		foreach($splitChars as $splitChar) {
			$ord = ord($splitChar);
			if(($ord < 65 && $ord != 46) || ($ord > 90 && $ord < 97) || $ord > 122) return FALSE;
		}
		return TRUE;
	}

	private function combineArrays($array1, $array2) {//combines 2 arrays.  Unlike the PHP array_merge function, if the second array has a value it overwrites
		if($array1 && $array2) {
			$result = array();
			foreach($array2 as $k2 => $v2) {
				if(!is_array($v2) && strlen($v2) > 0) $result[$k2] = $v2;
			}
			foreach($array1 as $k1 => $v1) if(!array_key_exists($k1, $result) && !is_array($v1) && strlen($v1) > 0) $result[$k1] = $v1;
			return $result;
		} else if($array1) return $array1;
		else if($array2) return $array2;
		else return array();
	}

	protected function isMostlyGarbage2($s, $cutoff) {
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

	protected function isMostlyGarbage($s, $cutoff) {
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

	protected function isCompleteGarbage($s) {
		if($s) {
			foreach (count_chars($s, 1) as $i => $val) {
				if(($i > 47 && $i < 58) || ($i > 64 && $i < 91) || ($i > 96 && $i < 123)) return false;
			}
		}
		return true;
	}

	protected function countPotentialLocalityWords($pLoc) {//echo "\ninput to countPotentialLocalityWords: ".$pLoc."\n";
		//$pLoc = preg_quote(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pLoc), '/');
		$pLoc = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pLoc));
		$lWords = array("/road(?:side|way)?\\b/i", "/\\bHighway\\b/i", "/\\bhwa?y\\b/i", "/ A(?i):rea\\b/", "/path\\b/i", "/\\br(?:ou)?te\\b/i",
			"/\\sCity\\b/i", "/\\bLoca(?:t(?:ed|ion)|lity)\\b/i", "/\\bmi(?:les?)?\\b/i", "/ L(?i)odge\\b/", "/ T(?i)rail\\b/", "/ A(?i)rboretum\\b/",
			"/\\bkm\\b/i", "/\\binternational\\b/i", "/ P(?i)arks?\\b/", "/ I(?i)sl(?:e|ands?)/", "/\\bcamp(?:grounds?)?\\b/i",
			"/ F(?i)alls/", "/\\bcounty\\b/i", "/\\sdistrict\\b/i", "/\\bjunction\\b/i", "/\\sC(?i)anyon\\b/", "/ C(?i)reek\\b/",
			"/ L(?i)oop\\b/", "/\\bservice\\b/", "/\\sstation\\b/", "/\\btown\\b/", "/\\bC(?i)oast\\b/", "/\\bS(?i)hore\\b/", "/\\bproperty\\b/i",
			"/\\bpeninsula\\b/i", "/\\bentrance\\b/i", "/\\bL(?i)akes?\\b/", "/\\sW(?i)ilderness\\b/", "/\\sR(?i)ange\\b/", "/\\bPass\\b/",
			"/\\b(?:N(?i)ationa[l1|I!]|(?-i)S(?i)t(?:\\.|ate)|(?-i)N(?i)at[l1|I!]?\\.)\\s(?-i)F(?i)orest\\b/", "/\\bvicinit(?:y|ate)\\b/i",
			"/\\sP(?i)eak\\b/", "/\\sS(?i)prings\\b/", "/\\bU\\. ?S\\. ?\\d{1,2}\\b/", "/\\bcamino\\b/", "/[A-Za-z]{3,} G(?i)ulch\\b/",
			"/\\b(?:N(?i)ationa[l1|I!]|(?-i)S(?i)t(?:\\.|ate)|(?-i)N(?i)at[l1|I!]?\\.|(?-i)P(?i)rov[l1|I!]nc[l1|I!]a[l1|I!])\\s(?-i)P(?i)ark\\b/i",
			"/\\bdrive\\b/i", "/[A-Za-z]{3,} R(?i)eserv(?:e|oir)\\b/", "/[A-Za-z]{3,} B(?i)utte\\b/", "/\\bReserve: /", "/\\bgravel\\b/i",
			"/ P(?i)reserve\\b/", "/\\bR(?i)d\\b/", "/\\bR(?i)egion(?:a[l1|I!])?\\b/", "/\\b[O0](?i)utlook\\b/", "/\\bintersection\\b/i",
			"/\\bWildlife Management\\b/", "/\\bQuad\\b/i", "/\\sR(?i)anch\\b/", "/\\sstreet\\b/i", "/ Ave\\b/i", "/\\sLane\\b/i",
			"/ Divide\\b/i", "/\\bM(?:t\\.?|(?i)ount) /", "/[A-Za-z] M(?:ts[,.]{0,2}|(?i)ountains?)\\b/", "/\\b(?:Conference|Visitors|Environmenta[l1|I!]) Center\\b/i", "/\\sR(?i)idge\\b/",
			"/\\b(?i:N(?:orth(?:east|west)?)?|S(?:outh(?:east|west)?)?|E(?:ast)?|W(?:est)?|[NE]?NE|[NW]?NW|[SE]?SE|[SW]?SW) (?:of|from) (?-i)[A-Z]/i",
			"/\\b[A-Za-z]{3,}v[l1|I!]{3}e\\b/i", "/\\b[A-Z][A-Za-z]{2,}t[o0]wn\\b/i", "/~ ?\\d/", "/ Gruppe\\b/", "/ A(?i)rea\\b/", "/ quarry/i",
			"/\\b[A-Z][A-Za-z]{2,} G(?i)u[l1|I!]ch\\b/", "/ T(?i)rail/", "/ B(?i)ay\\b/", "/[A-Za-z] A(?i)rboretum\\b/", "/ R(?i)iver\\b/",
			"/[A-Za-z]{3,} R(?i)ock\\b/", "/[A-Za-z]{3,} K(?i)eys?\\b/", "/[A-Za-z]{3,} S(?i)ound\\b/", "/\\bstate [1-9]/i", "/\\bprovince\\b/i");
		$result = 0;
		foreach($lWords as $lWord) if(preg_match($lWord, $pLoc)) {/*echo "\nlocality matched: ".$lWord."\n";*/$result++;}
		if(preg_match("/\\b(?:N(?:[EW]|orth(?:east|west)?)?|S(?:[EW]|outh(?:east|west)?)?|E(?:ast)?|W(?:est)?)\\s[o0QD]f\\s.+/i", $pLoc)) $result++;
		if($this->containsNumber($pLoc) && $result > 0 &&
			!preg_match("/\\b(?:Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)\\b/i", $pLoc) &&
			!preg_match("/\\d{1,2}\/\\d{1,2}\/(?:\\d{2}|\\d{4})/", $pLoc)) $result++;
		return $result/(count(explode(" ", $pLoc))*count($lWords));
		//return $result/count(explode(" ", $pLoc));
	}

	protected function countPotentialHabitatWords($pHab) {//echo "\ninput to countPotentialHabitatWords: ".$pHab."\n";
		//$pHab = preg_quote(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pHab), '/');
		$pHab = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pHab));
		$hWords = array("rocks?", "quercus", "(?:hard)?woods?", "abundant", "aspens?", "marsh", "juniper(?:us|s)?", "p[l1|I!]ants?", "humus",
			"understory", "grass(?:[l1|I!]and|es)?", "meadows?", "(?<!(?:National) )forest(?:ed)?", "ground", "mixed", "(?<!Jessie\\s)sa[l1|I!]ix", "acer ", "trunks?",
			"a[l1|I!]ders?", "tundra","abies", "calcareous", "outcrops?", "boulders?", "Granit(?:e|ic)", "limestone", "sandstone", "stand", "chert",
			"sand[ys]?", "cedars?", "trees?", "shrubs?", "(?:(?:sub)?al)?pine", "soi[l1|I!]s?", "(?:white)?bark", "open", "deciduous", "expos(?:ure|ed)",
			"aspect", "facing", "pinus", "habitat", "degrees?", "conifer(?:(?:ou)?s)?", "spruces?", "maples?", "substrate", "th[uv]ja", "shad(?:y|ed?)",
			"(?:[a-z]{2,})?berry", "box elders?", "dry", "damp", "moist", "wet", "firs?", "basalt(?:ic)?", "Liriodendron", "Juglans", "A[l1|I!]nus",
			"f[l1|I!][0o]{2}dp[l1|I!]ain", "gneiss", "moss(?:es|y)?", "crust", "(?:sage|brush|sagebrush)", "pocosin", "bog", "swamp", "branches",
			"Picea", "savanna", "Magno[l1|I!]ia", "Rhododendron", "[l1|I!]{2}ex", "Carpinus", "ta[l1|I!]us", "Nyssa", "bottom(?:[l1|I!]ands?)?",
			"w[l1|I!]{3}[0o]ws?", "riperian", "Fraxinus", "Betu[l1|I!]a", "Persea", "Carya", "ravine", "Aesculus", "cypress(?:es)?", "Empetrum",
			"Taxodium", "sparse(?:ly)?", "chaparra[l1|I!]", "temperate", "Sphagnum", "hemlocks?", "Myrica", "[l1|I!]odgepo[l1|I!]e", "Cornus",
			"myrt[l1|I!]es?", "Gordonia", "Liquidamber", "cottonwoods?", "pasture", "stump", "palmetto", "(?:mica)?schist(?:ose)?", "[l1|I!]itter",
			"scrub", "spp", "rotten", "logs?", "quartz(?:ite)?", "travertine", "grave[l1|I!](?:[l1|I!]y)?", "duff", "seepage", "submerged",
			"graminoids", "forbs", "mound", "ferns?", "mahogany", "cherry", "regenerating", "introduced", "(?:Pseudo)?tsuga", "timber(?:line)?",
			"flood ?plain", "terraces?", "thicket", "moraines?", "heath(?:er)?", "metamorphic", "vegetation", "quarry");
		$result = 0;
		foreach($hWords as $hWord) if(preg_match("/\\b".$hWord."\\b/i", $pHab)) {/*echo "\nhabitat matched: ".$hWord."\n";*/$result++;}
		return $result/(count(explode(" ", $pHab))*count($hWords));
		//return $result/count(explode(" ", $pHab));
	}

	protected function convertSlashedDates($string) {//echo "\nInput to convertSlashedDates: ".$string."\n";
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

	protected function isStateOrProvince($sp) {
		if($sp) {
			$sql = "SELECT * FROM lkupstateprovince lusp WHERE lusp.stateName = '".str_replace(array("\"", "'"), "", $sp)."'";
			if($r2s = $this->conn->query($sql)){
				if($r2 = $r2s->fetch_object()) return true;
			}
		}
		return false;
	}

	protected function isCountryInDatabase($c) {//echo "\nInput to isCountryInDatabase: ".$c."\n";
		if($c) {
			if(strcasecmp($c, "NEW GUINEA") == 0) return true;
			if(strcasecmp($c, "FORMOSA") == 0) return true;
			if(strcasecmp($c, "TAIWAN") == 0) return true;
			if(preg_match("/\\bU\\.?S\\.?S\\.?R\\b\\.?/", $c)) return true;
			$sql = "SELECT * FROM lkupcountry luc WHERE luc.countryName = '".str_replace(array("\"", "'"), "", $c)."'";
			if($r2s = $this->conn->query($sql)){
				if($r2 = $r2s->fetch_object()) return true;
			}
		}
		return false;
	}

	protected function getStatesFromCountry($c) {
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

	protected function getCounties($state) {
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

	protected function getStateFromCounty($c, $ss=null, $country="") {
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

	protected function getCountryFromState($s) {
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

	protected function getPolticalInfoFromCounty($c, $ss=null) {
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

	protected function getIdentifier($str, $possibleMonths) {//echo "\nInput to getIdentifier: ".$str."\n";
		$identified_by = "";
		$detPatStr = "/[^(]\\b(?:(?:D[ec][trf](?:[.:;,=1]|[ec]rmin[ec]d)|(?:[Il!|]d[ec]nt[Il!|]f[Il!|][ec]d))(?:\\sb[vy])?)[;:]?\\s?(.+)(?:(?:\\n|\\r\\n)((?s).+))?/i";
		if(preg_match($detPatStr, $str, $detMatches)) {//$i=0;foreach($detMatches as $detMatche) echo "\nline 1350, detMatches[".$i++."] = ".$detMatche."\n";
			$identified_by = trim($detMatches[1], " .\t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/^(.*)\\bCo[Il!1|]{2}[;:,.]{1,2} .*/i", $identified_by, $mats)) $identified_by = trim($mats[1], " .\t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		}
		else {
			$detPatStr = "/[^(]\\b(?:(?:D[ec][trf][ec]rmin[ec]r[sz]?)|(?:[Il!|]d[ec]nt[Il!|]f[Il!|][ec]r[sz]?))[;:]?\\s?(.+)(?:(?:\\n|\\r\\n)((?s).+))?/i";
			if(preg_match($detPatStr, $str, $detMatches)) {//$i=0;foreach($detMatches as $detMatche) echo "\nline 1356, detMatches[".$i++."] = ".$detMatche."\n";
				$identified_by = trim($detMatches[1], " .\t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			}
		}
		if(strlen($identified_by) > 0) {
			$date_identified = array();
			$datePatternStr = "/(?:(?(?=(?:.*)\\b(?:\\d{1,2})[- ]?+(?:(?i)".$possibleMonths.")[- ]?(?:(?:1[89]|20)\\d{2})))".

				"(.*)\\b(\\d{1,2})[- ]?+((?i)".$possibleMonths.")[- ]?((?:1[89]|20)\\d{2})|".

				"(?:(?(?=(?:.*)\\b(?:(?i)".$possibleMonths.")[,-]?\\s(?:\\d{1,2}),?\\s(?:(?:1[89]|20)\\d{2})))".

				"(.*)\\b((?i)".$possibleMonths.")\\s(\\d{1,2})[,-]?\\s((?:1[89]|20)\\d{2})|".

				"(?:(?(?=(?:.*)\\b(?:(?i)".$possibleMonths.")[,-]?\\s?(?:(?:1[89]|20)\\d{2})))".

				"(.*)\\b((?i)".$possibleMonths.")[,-]?\\s?((?:1[89]|20)\\d{2})|".

				"(.*)\\b((?:1[89]|20)\\d{2}(?! ?m))\\b)))/s";

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
				$pos = strpos($nextLine, "\n");
				if($pos !== FALSE) $nextLine = trim(substr($nextLine, 0, $pos));
				if(preg_match($datePatternStr, $nextLine, $dateMatches)) {
					$day = $dateMatches[2];
					if(strlen($day) > 0) {
						$date_identified['day'] = $day;
						$date_identified['month'] = $dateMatches[3];
						$date_identified['year'] = $dateMatches[4];
					} else {
						$month = $dateMatches[6];
						if(strlen($month) > 0) {
							$date_identified['month'] = $month;
							$date_identified['day'] = $dateMatches[7];
							$date_identified['year'] = $dateMatches[8];
						} else {
							$month = $dateMatches[10];
							if(strlen($month) > 0) {
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
				if(preg_match("/(.*)\\bNo\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/(.*)\\bAccession\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/(.*)\\bUniversity\\b/i", $identified_by, $ms)) $identified_by = $ms[1];
				if(preg_match("/.*\\bDet[;:. ]{1,2}(.+)/i", $identified_by, $ms)) $identified_by = $ms[1];
				$identified_by = trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			}
			if(!$this->isMostlyGarbage2($identified_by, 0.50) && $this->containsText($identified_by)) {
				if(str_word_count($identified_by) >= 6) {
					$pos = stripos($identified_by, " by ");
					if($pos !== FALSE) $identified_by = trim(substr($identified_by, $pos+3));
				}
				if(str_word_count($identified_by) >= 6) {
					$words = explode(" ", $identified_by);
					$index = 0;
					foreach($words as $word) {
						if(is_numeric($word)) {
							$identified_by = "";
							break;
						}
						if($index == 0) $identified_by = $word;
						else if($index == 1) {
							$identified_by .= " ".$word;
							if(strlen($word) > 2) break;
						} else {
							$identified_by .= " ".$word;
							break;
						}
						$index++;
					}
				}
				return array($identified_by, $date_identified);
			} else return array("", $date_identified);
		} else if(count($detMatches) > 2) return $this->getIdentifier(trim($detMatches[2]), $possibleMonths);
		return null;
	}

	protected function processCollectorName($lastName, $name) {
		$name = trim(preg_replace("/^Coll. ([A-Z])/i", "\${1}", $name));
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
			foreach($collWords as $collWord) {//echo "\nline 1387, collWord: ".$collWord."\n";
				$collWord = trim($collWord, "-,.");
				if(strlen($collWord) > 2 && !$this->containsNumber($collWord)) {
					$cfds = $this->getCollectorFromDatabase($collWord, $cStr);
					if($cfds) {
						$familyName = $cfds['familyName'];
						$collectorName = $cfds['collectorName'];
						$associatedCollectors = "";
						$collectorNum = trim(substr($cStr, strpos($cStr, $familyName)+strlen($familyName)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^*_-");
						if(preg_match("/(.*)(?:No\\b.?|#)(.*)/i", $collectorNum, $matches)) {
							$collectorNum = trim($matches[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^*_-");
							$firstPart = trim($matches[1]);
							$andPos = strpos($firstPart, "&");
							if($andPos !== FALSE && $andPos == 0) $associatedCollectors .= trim(substr($firstPart, 1));
						} else if(preg_match("/^(?:and |&)(.+)/i", $collectorNum, $mats)) {
							//echo "\nColector Number: ".$collectorNum."\n";
							$associatedCollectors = trim($mats[1]);
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
									$collectorName .= " and ".$temp;
									$collectorNum = "";
								}
							} else {
								$collectorName .= " ".$temp;
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
											$collectorName .= " ".$temp;
											$collectorNum = "";
										}
									} else {
										$collectorName .= " ".$temp;
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
							if(preg_match("/\\b(?:[1!l|I][89][OQ1!Il|ZS&0-9]{2}|[2Z][O0]{2}[OQ1!Il|ZS&0-9])/", $collectorNum) || str_word_count($collectorNum) > 2) $collectorNum = "";
						} else $collectorNum = "";
						$cfds['collectorName'] = $collectorName;
						$cfds['collectorNum'] = $collectorNum;
						$cfds['associatedCollectors'] = $associatedCollectors;
						//echo "\nline 7665, collectorName: ".$collectorName.", collectorNum: ".$collectorNum."\n";
						return $cfds;
					}
				}
			}
		}
		return null;
	}

	private function extractCollectorNum($s) {//echo "\ninput to extractCollectorNum, s: ".$s."\n";
		$s = trim($s);
		$sStrs = explode("\n", $s);
		$count = count($sStrs);
		if($count > 0) {
			$possibleNumbers = "[OQSZl|I!&0-9]";
			foreach($sStrs as $sStr) {
				$sStr = trim($sStr);
				if(preg_match("/(?:(?:N(?:um(?:ber)?|[o0])\\.)|#)\\s?(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)$/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/N(?:um(?:ber)?|[o0])\\s(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)$/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^(?:(?:N(?:um(?:ber)?|[o0])\\.)|#)\\s?(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^N(?:um(?:ber)?|[o0])\\s(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^(?:(?:N(?:um(?:ber)?|[o0])[.,;:*#])|#)\\s(".$possibleNumbers."{1,5}+\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
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
			if(preg_match($datPat, $col, $dateMatches2)) {
				$mNum = count($dateMatches2);
				if($mNum > 3) $col = trim($dateMatches2[3]);
				else if($mNum > 2) $col = trim($dateMatches2[2]);
				else $col = trim($dateMatches2[1]);
			}
			$pos = strpos($col, " ");
			if($pos !== FALSE) {
				$temp = trim(substr($col, 0, $pos));
				if($this->containsNumber($temp)) $col = $temp;
			}
		}
		if(preg_match("/(.*)Det(?:\\.|#|ermine)/i", $col, $dMats)) $col = trim($dMats[1]);
		if(preg_match("/(.*)Elev(?:\\.|ation)/i", $col, $dMats)) $col = trim($dMats[1]);
		//echo "\nline 11394, col: ".$col."\n";
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
				else {
					$spacePos = strpos($firstPart, " ");
					if($spacePos === FALSE) {
						if(strlen($lastPart) == 1) return $col;
						else return $firstPart;
					} else {
						$startOfFirstPart = trim(substr($firstPart, 0, $spacePos), " .");
						if(preg_match("/^(.+)[;:.,]$/", $startOfFirstPart, $mats)) {
							$startOfFirstPart = trim($mats[1]);
							if($this->containsNumber($startOfFirstPart)) return $startOfFirstPart;
						} else {
							$endOfFirstPart = trim(substr($firstPart, $spacePos+1));
							if(is_numeric($startOfFirstPart)) {
								if(strlen($endOfFirstPart) == 1) return $startOfFirstPart.$endOfFirstPart;
								else return $startOfFirstPart;
							} else if($this->containsNumber($startOfFirstPart)) return $startOfFirstPart;
						}
					}
				}
			} else return $col;
		}
		return "";
	}

	private function terminateCollector($col) {//echo "\nInput to terminateCollector: ".$col."\n";
		//if(!$this->containsNumber($col)) return "";
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

	protected function isNumericMDYDate($s) {
		if($s) {
			if(preg_match("/(?:[1I!|l][0O1I!|l2]|[0O]?[1-9I!|l])\/(?:[12][O0-9I!|l]|3[0O1I!|l]|[0O]?[1-9I!|l])\/(?:(?:[1I!|l][89])?[0-9OI!|l]{2}|(?:2[0O])?[0O][0-9OI!|l])/", $s)) return true;
		}
		return false;
	}

//this function returns the collector name if it is preceded by a collector label.
//If not found tries to find a collector name from the database in a likely place on many labels
	protected function getCollector($str) {//echo "\nInput to getCollector: ".$str."\n";
		if($str) {
			$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
			$collector = "";
			$nextLine = "";
			$identifiedBy = "";
			$otherCatalogNumbers = "";
			$collectorNum = "";
			$isIdentifier = false;
			$collPatternStr = "/\\bC[o0D](?:[l1|!i]{2}|U)[ec]{2}tion\\sdata[:,;. *]{1,3}(.*)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				if(count($collMatches) > 2) {
					if(strlen($collector) > 1) $nextLine = trim($collMatches[2]);
					else {
						$collector = trim($collMatches[2]);
						$nextLine = "";
					}
				}
			} else if(preg_match("/\\b(?:leg(?:[,.*#]|it))\\s?[:,;.]{0,2}\\s(.+)(?:\\n(.+))?/i", $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				$collPatternStr = "/\\b(?:leg(?:[,.*#]|it)) (?:et|and|&) ?(?:det(?:\\.|ermined)?|[l1|!i]dent[l1|!i]f[l1|!i]ed)(?:\\sb[vyg])?\\s?[:,;.]{0,2}\\s(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {
					$collector = trim($collMatches2[1]);
					$isIdentifier = true;
				}
				if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
			} else if(preg_match("/(?<!Date )\\bC[o0D](?:[l1|!i]{2}|U)[ec]{2}t[ec]d[:,;. *](?!\\s?(?:(?:on|during|at|in|near|from|along)\\s(?:the\\s)?)|with suppo)(.+)(?:\\n(.+))?/i", $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				$collPatternStr = "/\\bC[o0D](?:[l1|!I]{2}|U)[ec]{2}t[ec]d[:,;. *](?:\\s?(?:and|&)\\s(?:det(?:[;:,.=]|ermined)|[l1|!i]dent[l1|!i]f[l1|!i]ed)(?:\\sb[vyg])?)[:,;. *]{1,3}(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {
					$collector = trim($collMatches2[1]);
					if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					$isIdentifier = true;
				} else {
					$collPatternStr = "/C[o0D](?:[l1|!i]{2}|U)[ec]{2}t[ec]d[:,;. *](?:\\s?and\\sprep(?:ared|[,.])?\\sb[yxv])[:,;. *]{1,3}(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
					if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n11906, collMatches2[".$i++."] = ".$collMatche2."\n";
						$collector = trim($collMatches2[1]);
						if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					} else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}t[ec]d)\\s?b[vyg][:,;. *]{1,3}(.{3,}+)(?:\\n(.+))?/i", trim($collMatches[0]), $collMatches2)) {
						$collector = trim($collMatches2[1]);
						if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					} else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}t[ec]d)\\s?b[vyg]\\n(.+)(?:\\n(.+))?/i", $str, $collMatches2)) {
						$collector = trim($collMatches2[1]);
						if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					} else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}t[ec]d)\\sb[vyg](.*)/i", $str, $collMatches2)) {
						$collector = trim($collMatches2[1]);
					}
				}
				if(strlen($nextLine) == 0 && count($collMatches) > 2) $nextLine = trim($collMatches[2]);
			} else if(preg_match("/C[o0D](?:[l1|!i]{2}|U)[ec]{2}t[o0]r?s?[:,;. *=]{1,3}(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n11513, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[1]);
				if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
				$collPatternStr = "/(.*)\\b(?:(?:det(?:[;:,.=1]|ermined)|[l1|!i]dent[l1|!i]f[l1|!i]ed)(?:\\sb[vyg])?)[:,;. *]{1,3}(.+)/i";
				if(preg_match($collPatternStr, $collector, $collMatches2)) {
					$collector = trim($collMatches2[1]);
					$identifiedBy = trim($collMatches2[2]);
				}
			} else if(preg_match("/(.*)\\bC[o0D](?:[l1|!i]{2}|U)s?[:,;. *]{1,3}(?!\\s?Date)(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i", $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n1746, collMatches[".$i++."] = ".$collMatche."\n";
				$collector = trim($collMatches[2]);
				if(count($collMatches) > 3) $nextLine = trim($collMatches[3]);
				$collPatternStr = "/(?:(?:and|&.?)\\s?(?:det(?:[;:,.=1]|ermined)|[l1|!i]dent[l1|!i]f[l1|!i]ed)(?:\\sb[vyg])?)[:,;. *]{1,3}(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, $collector, $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n1634, collMatches2[".$i++."] = ".$collMatche2."\n";
					$collector = trim($collMatches2[1]);
					$isIdentifier = true;
					if(preg_match("/(.{6,})No. (.+)/i", $collector, $mats)) {
						$collector = trim($mats[1], " ,.");
						$temp = trim($mats[2]);
						if($this->containsNumber($temp)) {
							$collectorNum = $temp;
							if(preg_match("/(.*)(?:".$possibleMonths.")/", $collectorNum, $mats2)) $collectorNum = trim($mats2[1], " ,.");
							if(strlen($collector) > 0 && strlen($collectorNum) > 0 && str_word_count($collectorNum) < 3) {
								if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
									return array
									(
										'collectorName' => trim($mats[1]),
										'associatedCollectors' => trim($mats[2]),
										'collectorNum' => $collectorNum,
										'identifiedBy' => $collector
									);
								} else {
									return array
									(
										'collectorName' => $collector,
										'collectorNum' => $collectorNum,
										'identifiedBy' => $collector
									);
								}
							}
						}
					}
				} else if(preg_match("/(.+)[:,;. *]{1,3}Det(?:[:,;. *1]|ermined)(?:\\sb[vyg])?[:,;. *]?(.+)/i", $collector, $collMatches2)) {//$i=0;foreach($collMatches2 as $collMatche2) echo "\n1769, collMatches2[".$i++."] = ".$collMatche2."\n";
					$collector = trim($collMatches2[1]);
					$identifiedBy = trim($collMatches2[2]);
					$identifiedBy = trim(preg_replace("/(?:[!|I12Z]?[!|IZS0-9]|3[01!|I1]) (?:".$possibleMonths.") (?:[1!|!|I1]9[!|IZS0-9]{2}|[2Z]0[!|IZS0-9]{2})/", "", $identifiedBy));
					if(preg_match("/(.+) (?:N[0o]|#)[:,;. *]?(.+)/i", $identifiedBy, $mats3)) {
						$temp = trim($mats3[2]);
						if($this->containsNumber($temp) && str_word_count($temp) < 6) {
							if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
								return array
								(
									'collectorName' => trim($mats[1]),
									'associatedCollectors' => trim($mats[2]),
									'collectorNum' => $collectorNum,
									'identifiedBy' => trim($mats3[1])
								);
							} else {
								return array
								(
									'collectorName' => $collector,
									'collectorNum' => $temp,
									'identifiedBy' => trim($mats3[1])
								);
							}
						}
					} else {
						$pos = strrpos($identifiedBy, "  ");
						if($pos === FALSE) $pos = strrpos($identifiedBy, " ");
						if($pos !== FALSE) {
							$temp2 = trim(substr($identifiedBy, $pos+1));
							$temp = trim(substr($identifiedBy, 0, $pos));
							if($this->containsNumber($temp2) && strlen($temp) > 0 && str_word_count($temp2) < 3) {
								if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
									return array
									(
										'collectorName' => trim($mats[1]),
										'associatedCollectors' => trim($mats[2]),
										'collectorNum' => $temp2,
										'identifiedBy' => $temp
									);
								} else {
									return array
									(
										'collectorName' => $collector,
										'collectorNum' => $temp2,
										'identifiedBy' => $temp
									);
								}
							}
						}
					}
				} else if(preg_match("/(?<! Confirmed )\\bb[vyg][:,;. *]{1,3}(.+)/i", $collector, $collMatches2)) $collector = trim($collMatches2[1]);
				if(preg_match("/(.*)\\bDate\\b/i", $collector, $mats)) $collector = trim($mats[1]);
			} else {
				$collPatternStr = "/(?:\\r\\n|\\n|\\r)(.+),\\s?Co[l1!I]{2}(?:[,.*#]|ectors?)?\\b(?:(?:\\r\\n|\\n|\\r|\\s)(.*))?/i";
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
						if($this->containsNumber($nextLine) && !preg_match("/.*(?:".$possibleMonths.").*/i", $nextLine) && str_word_count($nextLine) < 3) {
							$collectorNum = $nextLine;
						}
					}
					if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
						return array
						(
							'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => $collectorNum
						);
					} else {
						return array
						(
							'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
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
			if(strlen($collector) > 1) {//echo "\nline 2024, collector = ".$collector."\ncollectorNum: ".$collectorNum."\nnextLine: ".$nextLine."\n";
				$collector = trim($collector);
				if(strlen($collector) <= 3 && strcasecmp(substr($collector, 0, 2), "No") == 0) $collector = "";
				$collector = trim(preg_replace("/\\s{2,}/m", " ", $collector));
				if(preg_match("/(.*)Acc(?:[,.]|ession)\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $collector, $cMats)) {
					$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@$%^&*_-");
					$otherCatalogNumbers = trim($cMats[2]);
				} else if(strlen($nextLine) > 1) {
					if(preg_match("/(.*)Acc(?:[,.]|ession)?\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $nextLine, $cMats)) {
						$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$otherCatalogNumbers = $this->terminateCollectorNum(trim($cMats[2]));
						$nextLine = "";
					}
				}
				if(preg_match("/(.*?)(?:No\\.? |# ?)([1-9!|lI][0-9OQ!|lI]?,[0-9OQ!|lI]{3}[abc]?)/i", $collector, $cMats)) {
					if($isIdentifier) $identifiedBy = $collector;
					if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
						return array
						(
							'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else {
						return array
						(
							'collectorName' => trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
				}
				if(preg_match("/(.*?)\((?:No\\.? |# ?)([0-9OQ!|lI]+[abc]?)\)/i", $collector, $cMats)) {
					if($isIdentifier) $identifiedBy = $collector;
					if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
						return array
						(
							'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else {
						return array
						(
							'collectorName' => trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
				}
				if(preg_match("/(.*?)(?:No\\.? |# ?)([0-9OQ!|lI]+[abc]?)/i", $collector, $cMats)) {
					$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
					if($isIdentifier) $identifiedBy = $collector;

					if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
						return array
						(
							'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else {
						return array
						(
							'collectorName' => $collector,
							'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
				}
				if(preg_match("/(.*)\\bSpecimen:\\s(.*)/i", $collector, $cMats)) {
					$temp = trim($cMats[2]);
					if($this->containsNumber($temp) && str_word_count($temp) < 3) {
						$collector  = trim($cMats[1]);
						$collectorNum = $temp;
					}
				}//echo "\n2103, collector = ".$collector."\ncollectorNum: ".$collectorNum."\nnextLine: ".$nextLine."\n";
				if(preg_match("/(.+) (?:elev(?:ation|[,.;:])|A[1!|Il]t[,.;:]?) [0-9OQ!|lI,]{1,5} ?(?:m(?:[,.;:]|eters)?|f(?:ee)?t[,.;:]?)/i", $collector, $mats)) $collector = trim($mats[1]);
				if(preg_match("/(.*?)\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $collector, $cMats)) {
					$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
					$temp = trim($cMats[2]);
					if($this->containsNumber($temp)) {
						$temp = $this->terminateCollectorNum($temp);
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
						if($isIdentifier) $identifiedBy = $collector;
						if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
							return array
							(
								'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						} else {
							return array
							(
								'collectorName' => $collector,
								'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						}
					}
				} else if(preg_match("/(.+)\\ss\\.n\\b/i", $collector, $mats)) {
					if($isIdentifier) $identifiedBy = $collector;
					if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats2)) {
						return array
						(
							'collectorName' => trim($mats2[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats2[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else {
						return array
						(
							'collectorName' => trim($mats[1]),
							'identifiedBy' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
				} else if($this->containsNumber($collector)) {
					$pos = strrpos($collector, " ");
					if($pos !== FALSE) {
						$firstPart = trim(substr($collector, 0, $pos));
						$lastPart = trim(substr($collector, $pos));
						if($this->containsNumber($lastPart) && !$this->containsNumber($firstPart) &&
							strpos($firstPart, " ") !== FALSE && !preg_match("/(?:".$possibleMonths.")/", $lastPart)) {
							if($isIdentifier) $identifiedBy = $collector;
							if(preg_match("/(.+)(?: and |&)(.+)/i", $firstPart, $mats)) {
								return array
								(
									'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
									'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
									'collectorNum' => trim($lastPart, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
									'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
								);
							} else {
								return array
								(
									'collectorName' => $firstPart,
									'collectorNum' => trim($lastPart, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
									'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
								);
							}
						}
					}
				}
				if(strlen($nextLine) > 0 && (strlen($collector) == 0 || strlen($collectorNum) == 0)) {
					if(preg_match("/(.*?)\\b(?:N[o0Q][.o]|#)(.*)/i", $nextLine, $cMats)) {//$i=0;foreach($cMats as $cMat) echo "\n11513, cMats[".$i++."] = ".$cMat."\n";
						$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$temp = trim($cMats[2]);
						if($this->containsNumber($temp)) $collectorNum = $this->terminateCollectorNum($temp);
					} else if(strpos($nextLine, " ") === FALSE && $this->containsNumber($nextLine)) $collectorNum = $nextLine;
				}
				if(preg_match("/(.*)\\bDet[.:;,](.*)/i", $collector, $mats)) {
					$temp = trim($mats[2]);
					$mPat = "/^.+?\\b(?:(?:[o0Q]?+[!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?(?:".$possibleMonths.
						")[ -]\\s?(?:[1!|Il][789][O!|lIZS0-9]{2}|[2Z][0OQ][O!|lIZS0-9]{2}))\\s(.+)/i";
					if(preg_match($mPat, $temp, $dMatches)) {
						if(count($dMatches) > 1) {
							$temp = trim($dMatches[1]);
							if(strlen($temp) > 1 && $this->containsNumber($temp) && str_word_count($temp) < 3) $collectorNum = $temp;
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
				if(strlen($collector) > 0 && strlen($collectorNum) == 0) {//echo "\n2212, collector = ".$collector."\nnextLine: ".$nextLine."\n";
					$spacePos = strrpos($collector, " ");
					if($spacePos !== FALSE) {//echo "\nline 6961, spacePos !== FALSE\n";
						$potCollector = trim(substr($collector, 0, $spacePos));
						$potCollNum = trim(substr($collector, $spacePos));
						while($spacePos !== FALSE && !$this->containsNumber($potCollNum)) {
							$potCollNum = trim(substr($potCollector, $spacePos));
							$potCollector = trim(substr($potCollector, 0, $spacePos));
							$spacePos = strrpos($potCollector, " ");
						}
						if(strlen($potCollector) > 3 && $this->containsNumber($potCollNum) && str_word_count($potCollNum) < 3) {
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
				if(preg_match("/(?:[A-Za-z]+\\s)?by\\s(.*)/i", $collector, $mats)) {
					$collector = trim($mats[1]);
					if(preg_match("/(.+)\\s[A-Za-z ,.]+\\sby\\s.*/i", $collector, $mats2)) {
						if(count($mats2) > 1) $collector = trim($mats2[1]);
					}
				}
				//echo "\nline 2255, collector: ".$collector.", collectorNum: ".$collectorNum."\n";
				if(strlen($collector) > 0) {
					if($isIdentifier) $identifiedBy = $collector;
					if(strlen($collectorNum) > 2) {
						if(strcmp(substr($collectorNum, 0, 1), "(") == 0) {
							if(strcmp(substr(strrev($collectorNum), 0, 1), "(") == 0) $collectorNum = trim($collectorNum, " ()");
						}
					} else {
						$lines = array_reverse(explode("\n", trim($str)));
						$count = count($lines);
						$index = 0;
						$lastLine = trim($lines[0]);
						while(strlen($lastLine) == 0 && $index < $count) {
							if(!$this->isMostlyGarbage($lastLine, 0.48)) $lastLine = trim($lines[$index++]);
							else $index++;
						}
						if(preg_match("/.?(?:#|N[o0][. :;])(.+)/", $lastLine, $mats)) {
							$temp = trim($mats[1]);
							if($this->containsNumber($temp) && !$this->isNumericMDYDate($temp)) $collectorNum = $temp;
							if(preg_match("/(.+)Det(?:[:,;. *]|ermined)/i", $collectorNum, $mats2)) $collectorNum = trim($mats2[1]);
						} else if(is_numeric($lastLine)) $collectorNum = $lastLine;
					}
					if(preg_match("/(.+)(?: and |&)(.+)/i", $collector, $mats)) {
						return array
						(
							'collectorName' => trim($mats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'associatedCollectors' => trim($mats[2], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'otherCatalogNumbers' => $otherCatalogNumbers,
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					} else {
						return array
						(
							'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'otherCatalogNumbers' => $otherCatalogNumbers,
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
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
			$collPatternStr = "/(?:(?:(.*)(?:\\r\\n|\\n|\\r)(.*))?(?:\\r\\n|\\n|\\r)(.*)(?:\\r\\n|\\n|\\r))?(.*)$/i";
			if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n2023, collMatches[".$i++."] = ".$collMatche."\n";
				$countMatches = count($collMatches);
				if($countMatches == 5) {
					$result = $this->extractCollectorInfo($str, trim($collMatches[4]));
					if($result != null) return $result;
					$result = $this->extractCollectorInfo($str, trim($collMatches[3]), trim($collMatches[4]));
					if($result != null) return $result;
					$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
					if($result != null) return $result;
					$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
					if($result != null) return $result;
				} else if($countMatches == 4) {
					$result = $this->extractCollectorInfo($str, trim($collMatches[3]));
					if($result != null) return $result;
					$result = $this->extractCollectorInfo($str, trim($collMatches[2]), trim($collMatches[3]));
					if($result != null) return $result;
					$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
					if($result != null) return $result;
				} else if($countMatches == 3) {
					$result = $this->extractCollectorInfo($str, trim($collMatches[2]));
					if($result != null) return $result;
					$result = $this->extractCollectorInfo($str, trim($collMatches[1]), trim($collMatches[2]));
					if($result != null) return $result;
				} else if($countMatches == 2) {
					$result = $this->extractCollectorInfo($str, trim($collMatches[1]));
					if($result != null) return $result;
				}
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
				if(strlen($firstName) > 1 && strcasecmp($name, "Park") != 0) {//need exact match for such a common string
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

	protected function getScientificName($s) {
		$sciNamePatStr = "/Scientific Name\\b[:;,]? (.*)/i";
		if(preg_match($sciNamePatStr, $s, $sciNameMatches)) return trim($sciNameMatches[1]);
		return "";
	}

	protected function getMunicipality($s) {
		$townPatStr = "/\\b(?:T[o0]wn|C[lI!|1]ty|V[lI!|1]{3}age) [o0][fr][;:]?\\s([!|0152\\w]+\\s?(?:[0152\\w]+)?)[:;,.]/is";
		if(preg_match($townPatStr, $s, $townMatches)) {
			return str_replace(array("0", "1", "!", "|", "5", "2"), array("O", "l", "l", "l", "S", "Z"), trim($townMatches[1]));
		}
	}

	protected function getAssociatedTaxa($str) {
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

	protected function getLocality($s) {
		//echo "\nInput to getLocality: ".$s."\n";
		$locationPatStr = "/\\n(?:L|(?:\|_))[o0]c(?:a[1!lI]{2}ty|ati[o0]n|\\.)[:;,)]? (?!Habitat)(.*)(?:(?:\\n|\\n\\r)(.*))?/i";
		if(preg_match($locationPatStr, $s, $locationMatches)) {//$i=0;foreach($locationMatches as $locationMatche) echo "\nline 2170, locationMatches[".$i++."] = ".$locationMatche."\n";
			$location = trim($locationMatches[1]);
			if(strlen($location) < 3 && count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		} if(preg_match("/\\bL[o0]c(?:a[1!lI]{2}ty|ati[o0]n|\\.)[:;,)] (?!Habitat)(.*)(?:(?:\\n|\\n\\r)(.*))?/i", $s, $locationMatches)) {//$i=0;foreach($locationMatches as $locationMatche) echo "\nline 2174, locationMatches[".$i++."] = ".$locationMatche."\n";
			$location = trim($locationMatches[1]);
			if(strlen($location) < 3 && count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		} else if(preg_match("/\\bC[o0][il1!|]{2}[ec]{2}t[il1!|][o0]n\\sS[il1!|]te[:;,.]\\s(.+)/i", $s, $locationMatches)) {//$i=0;foreach($locationMatches as $locationMatche) echo "\nline 2126, locationMatches[".$i++."] = ".$locationMatche."\n";
			$location = trim($locationMatches[1]);
			if(strlen($location) < 3 && count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		} else if(preg_match("/\\n(S(?i)[il1!|]te[:;.]\\s.+)(?:(?:\\n|\\r\\n)((?s).+))?/", $s, $locationMatches)) {//$i=0;foreach($locationMatches as $locationMatche) echo "\nline 2130, locationMatches[".$i++."] = ".$locationMatche."\n";
			$location = trim($locationMatches[1]);
			if(strlen($location) < 3 && count($locationMatches) == 3) $location .= " ".trim($locationMatches[2]);
			return $location;
		}
	}

	protected function terminateField($f, $regExp) {
		if(preg_match($regExp, preg_quote($f, '/'), $ms)) {
			$temp = str_replace
			(
				array("\r\n", "\n", "\r", "\\"),
				array(" ", " ", " ", ""),
				$ms[1]
			);
		} else {
			$temp = str_replace
			(
				array("\r\n", "\n", "\r"),
				array(" ", " ", ""),
				$f
			);
		}
		if($this->isMostlyGarbage2($temp, 0.50)) return "";
		else return $temp;
	}

	protected function getHabitat($string) {//echo "\nInput to getHabitat: ".$string."\n\n";
		if(strlen($string) > 0) {
			$string = preg_quote($string, "/");
			$habitatPatStr = "/((?s).+)\\n(?:Micro)?Hab[il1!|]tat[:;,.]? (.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)(?:\\r\\n|\\n\\r|\\n|\\r)/i";
			if(preg_match($habitatPatStr, $string, $habitatMatches)) {//$i=0;foreach($habitatMatches as $habitatMatche) echo "\nhabitatMatches[".$i++."] = ".$habitatMatche."\n";
				$firstPart = stripslashes(trim($habitatMatches[1]));
				$habitat = stripslashes(trim($habitatMatches[2]));
				$nextLine = stripslashes(trim($habitatMatches[3]));
				//echo "\nline 2148, firstPart: ".$firstPart.", habitat: ".$habitat.", nextLine: ".$nextLine."\n\n";
				if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), $nextLine);
			} else {
				$habitatPatStr = "/((?s).+)\\n(?:Micro)?Hab[il1!|]tat[:;,.]? (.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)/i";
				if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nsecond Match\n";
					$firstPart = stripslashes(trim($habitatMatches[1]));
					$habitat = stripslashes(trim($habitatMatches[2]));
					$nextLine = stripslashes(trim($habitatMatches[3]));
					if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), $nextLine);
				} else {
					$habitatPatStr = "/((?s).+)\\n(?:Micro)?Hab[il1!|]tat[:;,.]? (.+)/i";
					if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nthird Match\n";
						$firstPart = stripslashes(trim($habitatMatches[1]));
						$habitat = stripslashes(trim($habitatMatches[2]));
						if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), "");
					} else {
						$habitatPatStr = "/((?s).+) (?:Micro)?Hab[il1!|]tat[:;,.] (.+)/i";
						if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nthird Match\n";
							$firstPart = stripslashes(trim($habitatMatches[1]));
							$habitat = stripslashes(trim($habitatMatches[2]));
							if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), "");
						} else {
							$habitatPatStr = "/((?s).+)(?:\\n|\\r\\n)S[il1!|]te[:;,.]? (.+)(?:\\r\\n|\\n\\r|\\n|\\r)((?s).+)(?:\\r\\n|\\n\\r|\\n|\\r)/i";
							if(preg_match($habitatPatStr, $string, $habitatMatches)) {//echo "\nfourth Match\n";
								$firstPart = stripslashes(trim($habitatMatches[1]));
								$habitat = stripslashes(trim($habitatMatches[2]));
								$nextLine = stripslashes(trim($habitatMatches[3]));
								if(!$this->isMostlyGarbage($habitat, 0.51) && !preg_match("/^.{0,3}(?:Co[il1!|]{2}ect[^i]|Det(?:\\.|ermine))/i", $habitat)) return array($firstPart, str_replace(array("\r\n", "\n", "\r"), array(" ", " ", " "), $habitat), $nextLine);
							}
						}
					}
				}
			}
		}
		return array("", "", "");
	}

	protected function getSubstrate($string) {
		if(strlen($string) > 0) {
			$subPatStr = "/(Substrat(?:e|um)(?:[:;,.]| (?:i|wa)s | (?:appear|seem)(?:s|ed) to be )?(.{3,}?)[;:.\n] ?)/i";
			if(preg_match($subPatStr, $string, $subMatches)) return $subMatches;
		}
	}

	protected function terminateSubstrate($sub) {
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

	protected function getElevation($string) {
		if(strlen($string) > 0) {//echo "\nInput to getElevation: ".$string."\n";
			$possibleNumbers = "[OQSZl|I!&0-9^]";
			$approximateIndicators = "ab[o0]ut |ab[o0](?:ve|w) |ca\\.? |approx(?:\\.|imately)? |ar[o0]und |[-~]";
			//first, units: m.s.m. with a range of numbers preceded by label (elevation or altitude)
			$elevPatStr = "/(.*)\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?: ?- ?(?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9])))\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2}(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2297, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[2]))." m.s.m.", trim($elevMatches[3]));
			}
			//next units: m.s.m. with a range of numbers followed by label
			$elevPatStr = "/(.*)(?: |\\n)\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?: ?- ?(?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[I1-9])))\\s".
				"m\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2}".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[:;,]?\\s?(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2315, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[2]))." m.s.m.", trim($elevMatches[3]));
			}
			//next units: m.s.m. with a range of numbers
			$elevPatStr = "/(.*)(?: |\\n)\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?: ?- ?(?:[SZl|I!&1-9^],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9])))\\s".
				"m\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2}(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2332, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[2]))." m.s.m.", trim($elevMatches[3]));
			}
			//next units: m.s.m. preceded by approximate indicator and label
			$elevPatStr = "/(.*)\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"(?: |\\n)(".$approximateIndicators.")((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2}(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2349, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array
				(
					trim($elevMatches[1]),
					$this->replaceMistakenLetters(trim($elevMatches[2]))." ".$this->replaceMistakenNumbers(trim($elevMatches[3]))." m.s.m.",
					trim($elevMatches[4])
				);
			}
			//next units: m.s.m. preceded by approximate indicator, followed by label
			$elevPatStr = "/(.*)(?: |\\n)\(?(".$approximateIndicators.")([SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers.")\\sm\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2}".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)\)?[:;,]?\\s?(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2367, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array
				(
					trim($elevMatches[1]),
					$this->replaceMistakenLetters(trim($elevMatches[2]))." ".$this->replaceMistakenNumbers(trim($elevMatches[3]))." m.s.m.",
					trim($elevMatches[4])
				);
			}
			//next units: m.s.m. preceded by approximate indicator
			$elevPatStr = "/(.*)(?: |\\n)\(?(".$approximateIndicators.")([SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers.")\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2}(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2384, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array
				(
					trim($elevMatches[1]),
					$this->replaceMistakenLetters(trim($elevMatches[2]))." ".$this->replaceMistakenNumbers(trim($elevMatches[3]))." m.s.m.",
					trim($elevMatches[4])
				);
			}
			//next units: m.s.m. preceded by label
			$elevPatStr = "/(.*)\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))[:;,]?\\s?".
				"((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])\)?[,.;: ]{0,2}(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2401, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[2]))." m.s.m.", trim($elevMatches[3]));
			}
			//next units: m.s.m. followed by label
			$elevPatStr = "/(.*)(?: |\\n)\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2}".
				"\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[:;,]?\\s?(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2418, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[2]))." m.s.m.", trim($elevMatches[3]));
			}
			//next units: m.s.m.
			$elevPatStr = "/(.*)(?: |\\n)\(?((?:[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|".$possibleNumbers."))\\sm\\.?s\\.?(?:[mn]|r[na])[,.;: ]{0,2}(.*)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatche) echo "\nline 2434, elevMatches0[".$i++."]: ".$elevMatche."\n";
				return array(trim($elevMatches[1]), $this->replaceMistakenNumbers(trim($elevMatches[2]))." m.s.m.", trim($elevMatches[3]));
			}
			//feet or meters, range, preceded by label
			$elevPatStr = "/(.*)\(?\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,. \n]| [it]t[,. \n]|(?:m|rn)(?:[,. \n]|eter[5s]?)?|Feet)?".
				"\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9]))".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2}(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2314, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[3]));
			}
			//feet or meters, range followed by label
			$elevPatStr = "/(.*)(?: |\\n)\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,. \n]| [it]t[,. \n]|(?:m|rn)(?:[,. \n]|eter[5s]?)|Feet)?".
				"\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9])".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))),? ?".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[,.;: ]{0,2}(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2352, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[3]));
			}
			//feet or meters, approximate, preceded by label
			$elevPatStr = "/^(.*?)\(?+\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"(".$approximateIndicators.")((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet)))\)?[,.;: ]{0,2}(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2444, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = $this->replaceMistakenLetters($elevMatches[2]).
				str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet or meters, preceded by label
			$elevPatStr = "/(.*)(?: |\\n)\(?(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:\\.|itude)?)[:;,]?\\s?".
				"((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet)))\)?[,.;: ]{0,2}(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2386, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[3]));
			}
			//feet or meters, approximate, followed by label
			$elevPatStr = "/(.*)(?: |\\n)\(?(".$approximateIndicators.")".
				"\\b((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))),? ?".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[,.;: ]{0,2}(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2402, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = $this->replaceMistakenLetters($elevMatches[2]).
				str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//feet or meters, followed by label
			$elevPatStr = "/(.*)(?: |\\n)\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))),? ?".
				"(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|A[l|I!1]t(?:[,. ]|itude))\)?[,.;: ]{0,2}(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2417, elevMatches[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[3]));
			}
			//feet or meters, range, exact, no label
			$elevPatStr = "/(.*)(?: |\\n)\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[IO0-9])".
				"(?:\\s{0,2}(?:ft[,. \n]| [it]t[,. \n]|(?:m|rn)(?:[,. \n]|eter[5s]?)|Feet)?".
				"\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9]))".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2}".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side|yond))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2664, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[3]));
			}
			//feet or meters, approximate, no label
			$elevPatStr = "/(.*)(?: |\\n)\(?(".$approximateIndicators.")".
				"((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}|[I1-9])".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2}".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2470, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = $this->replaceMistakenLetters($elevMatches[2]).
				str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[3])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[4]));
			}
			//labels formated as XXXm/YYYft or XXXft/YYYm
			$elevPatStr = "/(.*)(?: |\\n|\\r)\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[1-9])".
				"\\s?(?:ft[,.; ]?|(?:m|rn)(?:[,.; \n]|eter[5s]?)?|Feet)\/(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}+|[1-9])".
				"\\s?(?:ft[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2485, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[3]));
			}
			//just numbers, feet or meters
			$elevPatStr = "/(.*)(?: |\\n|\\r)\(?((?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9^],".$possibleNumbers."{3}|".
				"[l|I!1]".$possibleNumbers."{4}|[SZl|I!&1-9^][OlI0-9]{1,3}+|[0-9])".
				"\\s{0,2}(?:ft[,.; \n]| [it]t[,.; \n]|(?:m|rn)(?:[,.; \n]|eter[5s]?)|Feet))\)?[,.;: ]{0,2}".
				"(?!(?:N(?:orth)? |S(?:outh)? |E(?:ast)? |W(?:est)? |[NE]?NE |[NW]?NW |[SE]?SE |[SW]?SW )?(?:of|from|above|be(?:low|neath|side))\\b)(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nline 2485, elevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("it", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "meter5"),
					array("ft", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[3]));
			}
		}
		return array("", "", "");
	}

	protected function findTokenAtEnd($string, $token) {//echo "\nInput to findTokenAtEnd, string: ".$string.", token: ".$token."\n";
		//if(strcasecmp($token, "Park") == 0) return '';
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

	protected function getCounty($c, $state_province="") {
		if($c) {
			if(strlen($c) > 2) {
				$result = array();
				$c = trim($c, " \t\n\r\0\x0B,.:;!()\"\'\\~@#$%^&*_-");
				$containsAmpersand = false;
				$mats1 = "";
				$mats2 = "";
				if(preg_match("/(.+)&(.+)/", $c, $mats)) {
					$mats1 = $mats[1];
					$mats2 = $mats[2];
					$c = trim($mats1)." and ".trim($mats2);
					$containsAmpersand = true;
				}
				$sql = "select lk1.countyName, lk2.stateName, lk3.countryName from lkupcounty lk1 INNER JOIN ".
					"(lkupstateprovince lk2 inner join lkupcountry lk3 on lk2.countryid = lk3.countryid) ".
					"on lk1.stateid = lk2.stateid ".
					"where lk1.countyName = '".str_replace(array("\"", "'"), "", $c)."'";
				if(strlen($state_province) > 0) $sql .= " AND lk2.stateName = '".$state_province."'";
				if($rs = $this->conn->query($sql)) {
					$num_rows = $rs->num_rows;
					if($num_rows > 0) {
						if($r = $rs->fetch_object()) {
							$c = $r->countyName;
							if($containsAmpersand) $c = str_ireplace(" and ", " & ", $c);
							array_push($result, array('county' => $c, 'stateProvince' => $r->stateName, 'country' => $r->countryName));
							while($r = $rs->fetch_object()) {
								array_push($result, array('county' => $c, 'stateProvince' => $r->stateName, 'country' => $r->countryName));
							}
							return $result;
						}
					} else {
						$c = ucwords
						(
							strtolower
							(
								str_replace
								(
									array('1', '!', '|', '5', '2', '0', 'ST.', 'St.', '[', ']', '(', ')', '"', '\'', '-'),
									array('l', 'l', 'l', 'S', 'Z', 'O', 'Saint', 'Saint', '', '', '', '', '', '', ' '),
									$c
								)
							)
						);
						$sql = "select lk1.countyName, lk2.stateName, lk3.countryName from lkupcounty lk1 INNER JOIN ".
							"(lkupstateprovince lk2 inner join lkupcountry lk3 on lk2.countryid = lk3.countryid) ".
							"on lk1.stateid = lk2.stateid ".
							"where lk1.countyName = '".$c."'";
						if(strlen($state_province) > 0) $sql .= " AND lk2.stateName = '".$state_province."'";
						if($r2s = $this->conn->query($sql)) {
							$num_rows = $r2s->num_rows;
							if($num_rows > 0) {
								if($r2 = $r2s->fetch_object()) {
									$c = $r2->countyName;
									if($containsAmpersand) $c = str_ireplace(" and ", " & ", $c);
									array_push($result, array('county' => $c, 'stateProvince' => $r2->stateName, 'country' => $r2->countryName));
									while($r2 = $r2s->fetch_object()) {
										array_push($result, array('county' => $c, 'stateProvince' => $r2->stateName, 'country' => $r2->countryName));
									}
									return $result;
								}
							} else if(stripos($c, "berg") !== FALSE) {
								$c = str_ireplace("berg", "burg", $c);
								$sql = "select lk1.countyName, lk2.stateName, lk3.countryName from lkupcounty lk1 INNER JOIN ".
									"(lkupstateprovince lk2 inner join lkupcountry lk3 on lk2.countryid = lk3.countryid) ".
									"on lk1.stateid = lk2.stateid ".
									"where lk1.countyName = '".$c."'";
								if(strlen($state_province) > 0) $sql .= " AND lk2.stateName = '".$state_province."'";
								if($r3s = $this->conn->query($sql)) {
									if($r3 = $r3s->fetch_object()) {
										$c = $r3->countyName;
										if($containsAmpersand) $c = str_ireplace(" and ", " & ", $c);
										while($r3 = $r3s->fetch_object()) {
											array_push($result, array('county' => $c, 'stateProvince' => $r3->stateName, 'country' => $r3->countryName));
										}
										return $result;
									}
								}
							} else if(stripos($c, "burg") !== FALSE) {
								$c = str_ireplace("burg", "berg", $c);
								$sql = "select lk1.countyName, lk2.stateName, lk3.countryName from lkupcounty lk1 INNER JOIN ".
									"(lkupstateprovince lk2 inner join lkupcountry lk3 on lk2.countryid = lk3.countryid) ".
									"on lk1.stateid = lk2.stateid ".
									"where lk1.countyName = '".$c."'";
								if(strlen($state_province) > 0) $sql .= " AND lk2.stateName = '".$state_province."'";
								if($r3s = $this->conn->query($sql)) {
									if($r3 = $r3s->fetch_object()) {
										$c = $r3->countyName;
										if($containsAmpersand) $c = str_ireplace(" and ", " & ", $c);
										array_push($result, array('county' => $c, 'stateProvince' => $r3->stateName, 'country' => $r3->countryName));
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
			}
		}
	}

	private function processCountyMatches($firstPart, $lastPart) {//echo "\nLine 2574, Input to processCountyMatches, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\n";
		$countyArray = null;
		$possibleState = "";
		$county = "";
		$possibleCountry = "";
		$nextWord = "";
		$firstPart = trim($firstPart, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		$lastPart = trim($lastPart, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		if(strpos($lastPart, "\n") !== FALSE) {
			$nextWords = explode("\n", $lastPart);
			$nextWord = trim($nextWords[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		} else $nextWord = trim($lastPart, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		if(strpos($firstPart, "\n") !== FALSE) {
			$prevLines = array_reverse(explode("\n", $firstPart));
			$num = 0;
			$i=0;
			foreach($prevLines as $prevLine) {
				if($i++ < 2) {
					if($countyArray == null || count($countyArray) == 0) {
						$countyArray = $this->processCountyString($prevLine, $nextWord);
						if($countyArray != null && count($countyArray) > 0) {
							if(count($countyArray) == 1)  return array_pop($countyArray);
							else {
								foreach($countyArray as $vs) {//foreach($vs as $k => $v) echo "\nline 795, ".$k.": ".$v."\n";
									if(array_key_exists('stateProvince', $vs)) {
										$possibleStateProvince = $vs['stateProvince'];
										if(preg_match("/\\b".preg_quote($possibleStateProvince, '/')."\\b/i", $prevLine)) return $vs;
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
					foreach($countyArray as $vs) {//foreach($vs as $k => $v) echo "\nline 788, ".$k.": ".$v."\n";
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
			if($countyArray != null && count($countyArray) > 0) {
				$county = $countyArray[0]['county'];
				if(strcasecmp($county, "Park") != 0) return array('county' => $county);//park is so common in the labels it should not be returned unless its state is found
			}
		} else {
			if(strlen($firstPart) > 0) {
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

	protected function processCArray($cArray, $s, $nextWord = null) {//echo "\nLine 1531, Input to processCArray: ".$s.", nextWord: ".$nextWord."\n";
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
		$cString = trim($cString, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		if(strlen($cString) > 0) {
			if($nextWord != null) {
				$nextWord = trim($nextWord, " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
				$pos = strrpos($cString, ",");
				if($pos !== FALSE) $temp = substr($cString, $pos);
				else {
					$pos = strrpos($cString, ";");
					if($pos !== FALSE) $temp = substr($cString, $pos);
					else $temp = $cString;
					$statePos = stripos($temp, "State");
					if($statePos !== FALSE) {
						$cArray = $this->getCounty($nextWord);
						if($cArray != null) {
							return $this->processCArray($cArray, $cString, $nextWord);
						}
					}
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

	private function getCountyMatches($c) {//echo "\nLine 3140, Input to getCountyMatches: ".$c."\n";
	//this function has the side-effect of removing double quotes
		if($c) {
			$tempC = str_replace("\"", "", $c);
			$countyPatStr = "/(.{3,})(?:C[o0q]un[tf][yv?i]|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;](.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.{3,})(?:C[o0q]un[tf][yv?i]|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[,.] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.{3,})\\b(?:C[o0][.,]?)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.*)\\n(?:C[o0][.,]?)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.{3,})(?:C[o0q]un[tf][yv?i]|Par[il!|]sh|B[o0]r[o0]ugh)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))\\b(.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.*)\\n(?:C[o0q]un[tf][yv?i]|Par[il!|]sh|B[o0]r[o0]ugh)[:;,.] (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.{3,})\\b(?:C[o0q][.,]|Go\\.)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,]? (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.{3,})\\b(?:C[o0q][.,])(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,]?(.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);

			$countyPatStr = "/(.*)\\n(?:C[o0q][.,]?)(?!(?:\\s(?:Road|Rd|Hiway|Hwy|Highway|line)))[:;,]? (.*)/is";
			if(preg_match($countyPatStr, $tempC, $countyMatches)) return array($countyMatches[1], $countyMatches[2]);
		}
	}


	protected function findCounty($c, $state_province="") {//echo "\nLine 3002, Input to findCounty: ".$c."\nstate_province: ".$state_province."\n";
		$firstPart = "";
		$secondPart = "";
		$lastPart = "";
		$state = "";
		$punctuation = "";
		$county = "";
		$country = "";
		$c = str_ireplace(" Dade Co", " Miami Dade Co", $c);
		$cm = $this->getCountyMatches($c);
		if($cm != null) {//$i=0;foreach($cm as $m) echo "\nLine 3187, cm[".$i++."] = ".$m."\n";
			$firstPart = trim($cm[0]);
			$lastPart = trim($cm[1]);
			if(strlen($state_province) > 0) {
				$counties = $this->getCounties($state_province);
				if(count($counties) > 0) {
					foreach($counties as $countie) {
						if(strcasecmp($countie, $firstPart) == 0) return array("", $countie, $this->getCountryFromState($state_province), $lastPart, $state_province);
						else if(strcasecmp($countie, $lastPart) == 0) return array($firstPart, $countie, $this->getCountryFromState($state_province), "", $state_province);
					}
				}

			}
			$cs = $this->processCountyMatches($firstPart, $lastPart);
			if($cs != null) {
				$county = $cs['county'];
				if(array_key_exists('stateProvince', $cs)) {
					$sp = $cs['stateProvince'];
					if(strlen($sp) > 0) $state = $sp;
				}
				if(array_key_exists('country', $cs)) {
					$t = $cs['country'];
					if(strlen($t) > 0) $country = $t;
				}
			}
		}
		if(strlen($county) == 0) {
			if(strlen($state_province) > 0) {
				$counties = $this->getCounties($state_province);
				if(count($counties) > 0) {
					foreach($counties as $countie) {
						if(strlen($firstPart) > 0) {
							$county = $this->findTokenAtEnd($firstPart, $countie);
							if(strlen($county) > 0) break;
						}
					}
				}
			}
		}
		if(strlen($county) == 0) {//the regular expression matches the last occurrence of the word "county" etc.
			//if can't resolve the county try looking for an earlier match
			$cm = $this->getCountyMatches($firstPart);
			if($cm != null) {
				$firstPart = trim($cm[0]);
				$lastPart = trim($cm[1])." ".$lastPart;
				$cs = $this->processCountyMatches($firstPart, $lastPart);
				if($cs != null) {
					$county = $cs['county'];
					if(array_key_exists('stateProvince', $cs)) $state = $cs['stateProvince'];
					if(array_key_exists('country', $cs)) $country = $cs['country'];
				}
			}
		}
		if(strlen($county) == 0) {
			if(strlen($state_province) > 0) {
				$counties = $this->getCounties($state_province);
				if(count($counties) > 0) {
					foreach($counties as $countie) {
						if(strlen($firstPart) > 0) {
							$county = $this->findTokenAtEnd($firstPart, $countie);
							if(strlen($county) > 0) break;
						}
					}
				}
			}
		}
		if(strlen($county) > 0) {
			$pos = strripos($firstPart, $county);
			if($pos !== FALSE) $firstPart = substr($firstPart, 0, $pos);
			else {
				$pos = stripos($firstPart, str_replace("Saint", "St.", $county));
				if($pos !== FALSE) $firstPart = substr($firstPart, 0, $pos);
			}
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
				if(strlen($temp) > 3) $county = trim(substr($county, $pos+1));
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
			if(strlen($state_province) > 0) {
				$state = $state_province;
				if($this->isUSState($state_province)) $country = "U.S.A.";
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
			return array($firstPart, $county, $country, $lastPart, $state);
		}
	}

	protected function getNumericMonth($m) {//echo "\nInput to getNumericMonth: ".$m."\n";
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

//gets dates in the form DayMonthYear.  If not found it will get dates like Month, Year
	protected function getDMYDates($str, $possibleMonths) {
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

	protected function getMDYDates($str, $possibleMonths) {
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

	protected function getNumericDates($str) {
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
					$month = ltrim($this->replaceMistakenNumbers($dateMatches[3]), "0");
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

	protected function getTRSCoordinates($str) {
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

	protected function getUTMCoordinates($str) {
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

	protected function getLatLongs($str) {//echo "\nInput to getLatLongs:\n".$str."\n";
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
			$latLongPatStr = "/(?:(?(?=(?s:.*)\\b(?:".$possibleNumbers."{1,3}+)\\s?[°*?c](?:\\s|\\n|\\r\\n)?".
				"(?:".$possibleNumbers."{1,3})\\s?['*?](?:\\s|\\n|\\r\\n)?".
				"(?:(?:".$possibleNumbers."{1,3})\\s?\")?\\s?(?:N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)?(?:L(?:ong|at)(?:[\\._]|(?:itude))?)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n)".
				"(?:".$possibleNumbers."{1,3})\\s?[°*?c](?:\\s|\\n|\\r\\n)?".
				"(?:".$possibleNumbers."{1,3})\\s?['*?](?:\\s|\\n|\\r\\n)?".
				"(?:(?:".$possibleNumbers."{1,3})\\s?\")?\\s?(?:E(?:ast)?|(?:W|VV)(?:est)?)\\b(?s:.*)))".

				"((?s).*)\\b(".$possibleNumbers."{1,3}+)\\s?[°*?c](?:\\s|\\n|\\r\\n)?".
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
				"(?:(?(?=(?s:.*)\\b(?:(?:".$possibleNumbers."{1,3}+(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|c|\*|\?|\\s|(?:deg(?:[\\._]|rees)?))".
				"\\s?'?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\?|\*(?:min(?:[\\._]|utes)?)))".
				"\\s?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[\\._]|onds)?)))?\\s?".
				"(?:N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n))?".
				"(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|c|\?|\*|\\s|(?:deg(?:[\\._]|rees)?))".
				"\\s?'?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\?|\*|(?:min(?:[\\._]|utes)?)))".
				"\\s?(?:(?:".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[\\._]|onds)?)))?".
				"\\s?(?:E(?:ast)?|(?:W|VV)(?:est)?))\\b(?:(?s).*)))".

				"((?s).*)\\b(?:(".$possibleNumbers."{1,3}+(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|c|\?|\*|\\s|(?:deg(?:[\\._]|rees)?))".
				"\\s?'?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|(?:min(?:[\\._]|utes)?)))".
				"\\s?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[\\._]|onds)?)))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n))?".
				"(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:°|c|\*|\?|\\s|(?:deg(?:[._]|rees)?))".
				"\\s?'?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|(?:min(?:[._]|utes)?)))".
				"\\s?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[._]|onds)?)))?".
				"\\s?(E(?:ast)?|(?:W|VV)(?:est)?))\\b((?s).*)|".

			//if not found look for patterns with possible double quotes or spaces as sign for degrees or minutes
				"((?s).*)\\b(?:(".$possibleNumbers."{1,3}+(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:\"|°|c|\*|\?|\\s|(?:deg(?:[._]|rees)?))".
				"\\s?'?(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:'|\*|\?|\\s|(?:min(?:[._]|utes)?))".
				"\\s?(?:(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,3})?)\\s?(?:\"|'|\?|(?:sec(?:[._]|onds)?)))?\\s?".
				"(N(?:orth)?|S(?:outh)?)\\b[,.]?\\s{0,2}".
				"(?:\\s|\\n|\\r\\n)(?:L(?:ong|at)(?:[._]|itude)?:?,?\\s{0,2}(?:\\s|\\n|\\r\\n))?".
				"(".$possibleNumbers."{1,3}(?:[._]".$possibleNumbers."{1,7})?)\\s?(?:\"|°|c|\?|\*|\\s|(?:deg(?:[._]|rees)?))".
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
			$catNo = $this->catalogNumber;
			$needles = array("â€”", "Â«", "Ã±", "â€¢", "Ã“", "Â»", "Ã¢", "Ã¨", "Ã¬", "Ã¹", "ÃŸ", "Ã ", "Ã¶", "Ãº", "Ã¡", "Ã¤", "Ã¼", "Ã³", "Ã­", "(FÂ£e)", "(F6e)", "/\\_", "/\\", "/'\\_", "/'\\", "/°\\", "AÂ£", " ", " V/", "Â¥", "Miill.", "&gt;", "&lt;", "—", "ï»¿", "&amp;", "&apos;", "&quot;", "\/V", " VV_", " VV.", "\/\/_", "\/\/", "\X/", "\\'X/", chr(157), chr(226).chr(128).chr(156), "Ã©", "/\ch.", "/\.", "/-\\", "X/", "\X/", "\Y/", "`\â€˜i/", chr(96), chr(145), chr(146), "â€˜", "’" , chr(226).chr(128).chr(152), chr(226).chr(128).chr(153), chr(226).chr(128), "“", "”", "”", chr(147), chr(148), chr(152), "Â°", "º", chr(239));
			$replacements = array("-", ".", "ñ", ".", "O", ".", "a", "e", "i", "u", "B", "a", "o", "u", "a", "a", "ü", "o", "i", "(Fée)", "(Fée)", "A.", "A", "A.", "A", "A", "AK", " ", " W ", "W", "Müll.", ">", "<", "-", "", "&", "'", "\"", "W", " W.", " W.", "W.", "W", "W", "W", "", "\"", "é", "Ach.", "A.", "A","W","W", "W", "W", "'", "'", "'", "'", "'", "'", "'", "\"", "\"", "\"", "\"", "\"", "\"", "\"", "°", "°", "°");
			$pat = "/\\A[^\w(]+(.*)/s";
			if(preg_match($pat, $str, $patMatches)) $str = trim($patMatches[1]);
			$str = str_replace($needles, $replacements, $str);
			if($catNo) {
				$str = str_replace("NY0".$catNo, "", $str);
				$str = str_replace("NYO".$catNo, "", $str);
				$str = str_replace("NY".$catNo, "", $str);
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
				"/ +([:;])([A-Za-z])/",
				"/ +([,.])([A-HJKLMNPRT-Ya-kmnp-y])/",
				"/ +([,:;.]\\W)/",
				"/[_-]{3,}/",
				"/([A-Za-z])Co\\., ([A-Z])/",
				"/^LEWIS\\sAND\\sCLARK\\s?CAVERNS\\s?STATE\\sPARK/m",
				"/(?:THE )?L[O0]U[|!1Il]S[|!1Il]ANA STATE UN[|!1Il]VERS[|!1Il]TY(?:\\r\\n| )(?:L[|!1Il][CG]HEN )?HERBAR[|!1Il]UM/is",
				"/(?:THE )?(?:MYC[O0]L[O0]G[|!1Il]CAL )?HERBAR[|!1Il]UM [ODQ0]F(?:\\r\\n| )L[O0]U[|!1Il]S[|!1Il]ANA STATE UN[|!1Il]VERS[|!1Il]TY/is",
				"/With the Cooperation of (?:Mr\\. )?Charles [DP]eering/is",
				"/(?:(?:HERBAR[|!1Il]UM [ODQ0]F\\s)?+The )?+NEW Y[O0]RK B[O0]TAN[|!1Il][CG]AL [CG]ARDEN/is",
				"/[|!1I ]{9,}/", //strings of ~s, 's, "s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long (from barcodes)
				"/[\|!Iil\"'1U~()@\[\]{}H\/\\\]{6,}/", //strings of ~s, 's, "s, @s, ls, Is, 1s, |s ,/s, \s, Us and Hs more than six characters long (from barcodes)
				"/^.{1,2}$/m", //one-character lines (Tesseract must generate a 2-char end of line)
				"/(lat|long)(\.|,|:|.:|itude)(:|,)?\s?(".$false_num_class."{1,3}(?:\.".$false_num_class."{1,7})?)\"/i" //the beginning of lat-long repair
				);
			$replacement = array("\${1} \${2}", "\${1} \${2}", "\${1}", " ", "\${1} Co., \${2}", "LICHENS OF LEWIS AND CLARK CAVERNS STATE PARK", "", "", "", "", "", "", "", "\${1}\${2}\$3 \${4}".chr(176));
			//$replacement = array(" ", "\${1} Co., \${2}", "LICHENS OF LEWIS AND CLARK CAVERNS STATE PARK", "", "", "", "", "", "", "", "\${1}\${2}\$3 \${4}".chr(176));
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
				$mIndex = ltrim($this->replaceMistakenNumbers($dateMatches[2]), "0");
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
				$mIndex = ltrim($this->replaceMistakenNumbers($dateMatches[3]), "0");
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
				$mIndex = ltrim($this->replaceMistakenNumbers($dateMatches[2]), "0");
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
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 4141, dateMatches2[".$i++."] = ".$dateMatche."\n";
				return $this->convertRomanNumeralDates($dateMatches[1]).
					$this->replaceMistakenNumbers(trim($dateMatches[2]))."-".
					$this->convertRomanNumeralNumsToMonths(str_replace(array("l", "1", " "), array("I", "I", ""), $dateMatches[3]))."-".
					$this->replaceMistakenNumbers($dateMatches[4]).$dateMatches[5];
			}
			$datePatternStr = "/(.*?)\\b([1lI ]{1,4}+|[1lI]\\s?[VX]|V\\s?[1lI]{0,3}+|X\\s?[1lI]{0,2}+)[.-]\\s?((?:[1Iil!|][789]|[zZ12][OQ0])".$possibleNumbers."{2})(.*)/s";
			if(preg_match($datePatternStr, $str, $dateMatches)) {//$i=0;foreach($dateMatches as $dateMatche) echo "\nline 4148, dateMatches[".$i++."] = ".$dateMatche."\n";
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

	protected function getCountry($c) {
		if($c) {
			if($this->isCountry($c)) {
				if(strpos($c, ".") !== FALSE) return $c;
				else return ucwords($c);
			} else {
				$c2 = str_replace("Q", "O", $c);
				if($this->isCountry($c2)) {
					if(strpos($c2, ".") !== FALSE) return $c2;
					else return ucwords($c2);
				} else {
					$c3 = str_replace("U", "O", $c);
					if($this->isCountry($c3)) {
						if(strpos($c3, ".") !== FALSE) return $c3;
						else return ucwords($c3);
					} else  {
						$c4 = str_replace("l", "I", $c);
						if($this->isCountry($c4)) {
							if(strpos($c4, ".") !== FALSE) return $c4;
							else return ucwords($c4);
						}
					}
				}
			}
		}
		return '';
	}

	protected function isCountry($country) {
		if($country != null) {
			$countryPatStr = '/(Canada|CA\\.?|Can\\.?|Un[il!|1]ted States(?: [o0]f America)?|U\\.?S\\.?(?:A\\.?)?|Mexic[o0]|MX\\.?|Mex\\.?|".
				"S[o0]UTH AFR[il!|1]CA|S[o0]\\.? AFR[il!|1]CA|C[o0]sta\\sR[il!|1]ca|N[o0]rway|[S5]w[ec]d[ec]n|F[il!|1]n[il!|1]and|Braz[il!|1]{2}|Japan)/i';
			//$countryPatStr = '/\\b(Canada|CA\.?|Can\.?|United States(?: of America)?|U\.?S\.?(?:A\.?)?|Mexico|MX\.?|Mex\.?|S[o0](?:\.[o0]UTH)? AFRICA)\\b/i';
			$m = preg_match($countryPatStr, $country, $matches);
			if($m) {
				if(strcasecmp($country, $matches[1]) == 0) return true;
			}
			return $this->isCountryInDatabase($country);
		}
		return false;
	}

	protected function getStateOrProvince($d) {//echo "\nInput to getStateOrProvince: ".$d."\n";
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
					'California'=>'/^(?:C(?i)a[il!|1]{2}f[o0uq]rn[il!|1]a|Cal[il!|1]f\\.?|(?-i)C(?i)A\\.)$/',
					'Colorado'=>'/^(?:C(?i)[o0uq][il!|1][o0uq]rad[o0uq]|C[o0uq][il!|1][o0]\\.?|(?-i)C(?i)[o0uq]\\.)$/',
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
					'Michigan'=>'/^(?:M(?i)[il!|1][ec]h[il!|1]gan|M[il!|1][ec]h\\.?|(?-i)M(?i)[il!|1]\\.?)$/',
					'Minnesota'=>'/^(?:M[il!|1]nn[ec]s[o0uq]ta|MN\\.?|M[il!|1]nn\\.?)$/i',
					'Mississippi'=>'/^(?:M[il!|1][s5]{2}[il!|1][s5]{2}[il!|1]pp[il!|1]|M[s5]\\.?|M[il!|1][s5]{2}\\.?)$/i',
					'Missouri'=>'/^(?:M[il!|1][s5]{2}[o0uq]ur[il!|1]|M[o0uq]\\.?)$/i',
					'Montana'=>'/^(?:M(?i)[o0uq]ntana|M[o0uq]nt\\.?|(?-i)M(?i)T\\.?)$/',
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
					'Puerto Rico'=>'/^(?:Puert[o0] R[il!|1]c[o0]|PR\\.?)$/i',
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

	protected function isUSState($state) {
		if($state != null) {//echo "\nInput State: ".$state."\n";
			$state = trim($state, " ,");
			$statePatStr = '(A[il!|1]abama|A[il!|1]\\.?|A[il!|1]a\\.?|'.
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
			'Puert[o0] R[il!|1]c[o0]|PR\\.?|'.
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
			')';
			$m = preg_match('/^'.$statePatStr.'$/i', $state, $matches);
			if($m) {
				$thisState = $matches[1];
				if($state == $thisState) return true;
			}
			$m = preg_match('/\\b'.$statePatStr.'[ ,.:;\n]{1,2}/i', $state, $matches);
			if($m) {
				$thisState = $matches[1];
				if($state == $thisState) return true;
			}
			return ($m && ($state == $matches[1] || $state == $matches[1]."."));
		}
		return false;
	}

	protected function replaceMistakenLetters($word) {
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

	protected function replaceMistakenNumbers($num) {
		return str_replace(array("l", "I", "|", "!", "i", "O", "Q", "o", "s", "S", "Z", "z", "&", "U", "[", "]"), array("1", "1", "1", "1", "1", "0", "0", "0", "5", "5", "2", "2", "6", "4", "1", "1"), $num);
	}

	private function getDates($str, $possibleMonths) {
		if($str) {
			return array_merge($this->getDMYDates($str, $possibleMonths), $this->getMDYDates($str, $possibleMonths), $this->getNumericDates($str));
		}
		return array();
	}

	protected function getVerbatimCoordinates($str) {
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

	private function containsVerbatimAttribute($pAtt) {
		$pAtt = trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pAtt));
		//if(strpos($pAtt, "|") !== FALSE || strpos($pAtt, "/") !== FALSE || strpos($pAtt, "\"") !== FALSE) $pAtt = preg_quote($pAtt, '/');
		$vaWords = array("atranorin", "fatty acids?", "cortex", "areolate", "medullae?", "podeti(?:a|um)(?! ?\\/)",
			"(?:(?:a|hy)po|epi)theci(?:a|um)(?! ?(?:\\/|color))",
			"thall(?:us|i)", "strain", "dis[ck]s?(?! (?:convex\\/|color))", "squamul(?:es?|ose)", "soredi(?:a(?:te)?|um)", "fruticose",
			"fruit(?:icose|s|ing)?", "crust(?:ose)?", "corticolous", "saxicolous", "terricolous", "chemotype", "terpenes?",
			"isidi(?:a(?:te)?|um)", "TLC", "parietin", "anthraquinones?", "pigment(?:s|ed)?", "soralia", "ostioles?", "spores",
			"cluster(?:s|ed)", "exciple", "paraphyses(?! ?branched\\/)", "foliose", "pruinose", "Chemica[l1|I!] contents", "ciliate",
			"sterile", "septate(?! ?\\/)", "(?:(?:nor)?stictic|usnic|sa[l1|I!]azinic|psoromic|ga[l1|I!]binic|[o0][l1|I!]ivetoric|evernic) acids?");
		//foreach($vaWords as $vaWord) if(stripos($word, $vaWord) !== FALSE) return true;
		foreach($vaWords as $vaWord) if(preg_match("/\\b".$vaWord."\\b/i", $pAtt)) return true;
		if(preg_match("/\\b[KPC][+-]\\B/", $pAtt)) return true;
		if(preg_match("/\\bUV[+-]\\B/", $pAtt)) return true;
		if(preg_match("/\\bPD[+-]\\B/", $pAtt)) return true;
		if(preg_match("/\\bHC[Il][+-]\\B/", $pAtt)) return true;
		return false;
	}
}

?>