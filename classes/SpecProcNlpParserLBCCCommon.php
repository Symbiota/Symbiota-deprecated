<?php
include_once($serverRoot.'/classes/'SpecProcNlpParserLBCCBryophyte.php');
include_once($serverRoot.'/classes/'SpecProcNlpParserLBCCLichen.php');

class SpecProcNlpParserLBCCCommon extends SpecProcNlp {

	protected $conn;

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	protected function parse($rawStr) {
		$handler;
		$url = $this->url;
		if(stripos($url, "/bryophytes/") !== FALSE) $handler = new SpecProcNlpParserLBCCBryophyte();
		else if(stripos($url, "/lichens/") !== FALSE) $handler = new SpecProcNlpParserLBCCLichen();
		else return array();
		$results = array();
		$rawStr = trim($this->fixString(str_replace("\t", " ", $rawStr)));
		//If OCR source is from tesseract (utf-8 is default), convert to a latin1 character set
		//if(mb_detect_encoding($rawStr,'UTF-8,ISO-8859-1') == "UTF-8"){
		//	$rawStr = utf8_decode($rawStr);
		//}
		$labelInfo = array();
		if(strlen($rawStr) > 0 && !$this->isMostlyGarbage2($rawStr, 0.50)) {
			$labelInfo = $handler->getLabelInfo($rawStr);
			if($labelInfo) {
				$recordedBy = "";
				$recordedById = "";
				$recordNumber = "";
				$otherCatalogNumbers = '';
				$identifiedBy = '';
				if(array_key_exists('recordedBy', $labelInfo)) $recordedBy = $labelInfo['recordedBy'];
				if(array_key_exists('recordedById', $labelInfo)) $recordedById = $labelInfo['recordedById'];
				if(array_key_exists('recordNumber', $labelInfo)) $recordNumber = $labelInfo['recordNumber'];
				if(array_key_exists('otherCatalogNumbers', $labelInfo)) $otherCatalogNumbers = $labelInfo['otherCatalogNumbers'];
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
			}
		}
		return $this->combineArrays($labelInfo, $results);
	}

	protected function doGenericLabel($str) {//echo "\nDoin' GenericLabel\n";
		$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$possibleNumbers = "[OQSZl|I!0-9]";
		$state_province = '';
		$possible_collector_num = '';
		$scientificName = $this->getScientificName($str);
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
			if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
				$firstPart = trim($countyMatches[0]);
				$secondPart = trim($countyMatches[1]);
				$lastPart = trim($countyMatches[4]);
				if(strlen($location) < 3) {
					$hCount1 = $this->countPotentialHabitatWords($firstPart);
					$lCount1 = $this->countPotentialLocalityWords($firstPart);
					$hCount2 = $this->countPotentialHabitatWords($lastPart);
					$lCount2 = $this->countPotentialLocalityWords($lastPart);
					if($hCount1 + $lCount1 > $hCount2 + $lCount2) $location = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", ltrim($firstPart, " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					else $location = preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", ltrim($lastPart, " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
					//echo "\nlocation = ".$location."\n";
				}
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
				foreach($lines as $line) {//echo "\nline 9144, line: ".$line."\n";
					if(strlen($line) > 3) {
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
				if(!$foundSciName) {
					$lines = array_reverse(explode("\n", $firstPart));
					foreach($lines as $line) {//echo "\nline 9165, line: ".$line."\n";
						if(strlen($line) > 3) {
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
				}
				if(strlen($location) > 0) {//echo "\nline 9185, habitat: ".$habitat."\nlocation: ".$location."\nsubstrate: ".$substrate."\nscientificName: ".$scientificName."\n";
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
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	protected function processSciName($name) {//echo "\nInput to processSciName: ".$name."\n";
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
			} else if(preg_match("/^([A-Za-z]+)\\s\([A-Za-z]+\)\\s([A-Za-z]+.*)/", $name, $mats)) {
				$name = trim($mats[1])." ".trim($mats[2]);
			}
			if($this->isPossibleSciName($name)) return array('scientificName' => $name, 'recordNumber' => $recordNumber);
			else {//echo "\nline 2636, name: ".$name.", recordNumber: ".$recordNumber."\n";
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
						} else if(preg_match("/(.+)\\s(contain(?:s|ing))\\s(.+)/i", $name, $mats)) {
							$potentialSciName = trim($mats[1]);
							if($this->isPossibleSciName($potentialSciName)) {
								$foundSciName = true;
								$results['scientificName'] = $potentialSciName;
								$results['recordNumber'] = $recordNumber;
								$results['verbatimAttributes'] = trim($mats[2]);
							} else $results['verbatimAttributes'] = trim($mats[2]);
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
				if(!$foundSciName && preg_match("/(.{4,}?)((?:\\s(?:F[o0]und|(?:Fairly\\s)?C[o0]mm[o0]n(?:\\sand\\sabundant)?|L[o0]{2}se|[o0]ccasi[o0]na[lI1!|]))?+\\son\\s.*)/i", $name, $mats)) {
					$potentialSciName = $mats[1];
					if($this->isPossibleSciName($potentialSciName)) {
						$foundSciName = true;
						$results['scientificName'] = $potentialSciName;
						$results['recordNumber'] = $recordNumber;
						$substrate = trim($mats[2]);
						if($this->countPotentialLocalityWords($substrate) == 0) $results['substrate'] = $substrate;
						else {
							$pos = strpos($substrate, ";");
							if($pos !== FALSE) {
								$substrate = trim(substr($substrate, 0, $pos));
								if($this->countPotentialLocalityWords($substrate) == 0) $results['substrate'] = $substrate;
							}
							if(!array_key_exists('substrate', $results)) {
								while(preg_match("/(.+\\s[a-zA-Z]{3,})\\..+/", $substrate, $mats)) {
									$substrate = trim($mats[1]);
									if($this->countPotentialLocalityWords($substrate) == 0) {
										$results['substrate'] = $substrate;
										break;
									}
								}
								if(!array_key_exists('substrate', $results)) {
									$pos = strrpos($substrate, ",");
									while($pos !== FALSE) {
										$substrate = trim(substr($substrate, 0, $pos));
										if($this->countPotentialLocalityWords($substrate) == 0) {
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
				if(!$foundSciName) {//echo "\nline 2754, name: ".$name.", recordNumber: ".$recordNumber."\n";
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
			}
			return $result;
		}
		return "";
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

	protected function combineArrays($array1, $array2) {//combines 2 arrays.  Unlike the PHP array_merge function, if the second array has a value it overwrites
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

	private function countPotentialLocalityWords($pLoc) {//echo "\ninput to countPotentialLocalityWords: ".$pLoc."\n";
		$pLoc = preg_quote(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pLoc), '/');
		$lWords = array("road", "Highway", "hwa?y", "area", "path", "route", "range", "city",
			"trail", "wilderness", "state", "loop", "mount", "pass", "drive", "picnic",
			"Loca(?:t(?:ed|ion)|lity)", "miles?", "km", "mi", "national", "parks?", "islands?", "camp(?:grounds?)?", "falls", "county",
			"district", "junction", "service", "station", "town", "coast", "shore", "peninsula", "entrance",
			"(?:National|St(?:\\.|ate)|Natl?\\.)\\sForest", "(?:National|St(?:\\.|ate)|Natl?\\.)\\sPark", "preserve", "Rd", "path", "region",
			"intersection", "University", "Wildlife Management", "Quad", "ranch", "street", "Ave", "Lane",
			"(?:Conference|Visitors|Environmenta[l1|I!])\\scenter", "[A-Za-z]{3,}v[l1|I!]{3}e", "[A-Za-z]{3,}t[o0]wn");
		$result = 0;
		foreach($lWords as $lWord) if(preg_match("/\\b".$lWord."\\b/i", $pLoc)) {/*echo "\nmatched: ".$lWord."\n";*/$result++;}
		if(preg_match("/.*\\s(?:N(?:[EW]|orth(?:east|west)?)?|S(?:[EW]|outh(?:east|west)?)?|E(?:ast)?|W(?:est)?)\\s[o0QD]f\\s.+/i", $pLoc)) $result++;
		if($this->containsNumber($pLoc) && $result > 0 &&
			!preg_match("/(?:Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)/i", $pLoc) &&
			!preg_match("/\\d{1,2}\/\\d{1,2}\/(?:\\d{2}|\\d{4})/", $pLoc)) $result++;
		return $result/(count(explode(" ", $pLoc))*count($lWords));
		//return $result/count($lWords);
	}

	private function countPotentialHabitatWords($pHab) {//echo "\ninput to countPotentialHabitatWords: ".$pHab."\n";
		$pHab = preg_quote(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $pHab), '/');
		$hWords = array("field", "rocks?", "quercus", "woods?", "hardwoods?", "bottom", "abundant", "aspens?", "marsh", "juniper(?:us|s)?", "plants?",
			"understory", "grass", "meadow", "forest", "ground", "mixed", "(?<!Jessie\\s)salix", "acer ", "alder ", "tundra ", "abies", "calcareous",
			"slope", "outcrops?", "boulders?", "Granit(?:e|ic)", "limestone", "sandstone", "sandy", "cedars?", "trees?", "(?:(?:sub)?al)?pine", "soils?",
			"bark", "open", "deciduous", "expos(?:ure|ed)", "shad(?:y|ed?)", "aspect", "facing", "pinus", "habitat", "degrees?", "conifer(?:ou)?s",
			"spruce", "maple", "substrate", "thuja", "box elder", "dry", "damp", "moist", "wet", "fir", " basalt", "Liriodendron", "Juglans",
			"floodplain", "gneiss", "moss(?:es|y)?", "crust", "(?:sage)?brush", "pocosin", "bog", "swamp", "Picea", "savanna", "Magnolia",
			"Rhododendron", "Ilex", "Carpinus", "talus", "Nyssa", "bottomlands?", "willows?", "riperian", "Fraxinus", "Betula", "Persea", "Carya",
			"ravine", "Aesculus", "cypress", "Taxodium", "sparse", "chaparral", "temperate", "Sphagnum", "hemlock", "Myrica", "lodgepole",
			"myrtle", "Gordonia", "Liquidamber", "cottonwoods?", "pasture", "stump", "palmetto", "(?:mica)?schist", "scrub", "spp");
		$result = 0;
		foreach($hWords as $hWord) if(preg_match("/\\b".$hWord."\\b/i", $pHab)) {/*echo "\nmatched: ".$hWord."\n";*/$result++;}
		return $result/(count(explode(" ", $pHab))*count($hWords));
		//return $result/count($hWords);
	}

	private function containsVerbatimAttribute($word) {
		$vaWords = array("atranorin", "fatty acid", "cortex", "areolate", "medulla", "podetia", "apothecia", "thallus", "strain", "squamul",
			"soredia", "fruticose", "fruiticose", "crustose", "corticolous", "saxicolous", "terricolous", "evernic acid",
			"isidia", "TLC", "crystal", "stictic acid", "usnic acid", "salazinic acid", "parietin", "anthraquinone", "pigment");
		foreach($vaWords as $vaWord) if(stripos($word, $vaWord) !== FALSE) return true;
		if(preg_match("/\\b[KPC][+-]\\b/", $word)) return true;
		if(preg_match("/\\bUV[+-]\\b/", $word)) return true;
		return false;
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
			$sql = "SELECT * FROM lkupcountry luc WHERE luc.countryName = '".str_replace(array("\"", "'"), "", $c)."'";
			if($r2s = $this->conn->query($sql)){
				if($r2 = $r2s->fetch_object()) return true;
			}
		}
		return false;
	}

	protected function getIdentifier($str, $possibleMonths) {
		$detPatStr = "/\\b(?:(?:D[ec][trf](?:[.:;]|[ec]rmin[ec](?:d\\sb[vy]|r[sz]?)))|(?:[Il!|]d[ec]nt[Il!|]f[Il!|][ec]d\\sb[vy]))[;:]?\\s?(.+)(?:(?:\\n|\\r\\n)((?s).+))?/i";
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

	protected function getHabitat($string) {//echo "\nInput to getHabitat: ".$string."\n\n";
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

	protected function getSubstrate($string) {
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
			$elevPatStr = "/(.*)\\b(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|Alt(?:\\.|itude)?[:;,]?)?\\s?".
				"((?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|~)?(?:".$possibleNumbers."|[l|I!1]".
				$possibleNumbers.",?".$possibleNumbers."{3}|[SZl|I!&1-9^],?".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,2})".
				"(?:\\s{0,2}(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eter[5s]?)|Feet)?\\s?-\\s?(?:".$possibleNumbers."|[l|I!1]".$possibleNumbers.
				",?".$possibleNumbers."{3}|[SZl|I!&1-9^],?".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,3}))".
				"\\s{0,2}(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eter[5s]?)?|Feet))(.*+)/is";
			if(preg_match($elevPatStr, $string, $elevMatches)) {
				//$i=0;
				//foreach($elevMatches as $elevMatch) echo "\nelevMatches1[".$i++."] = ".$elevMatch."\n";
				$elevation = str_ireplace
				(
					array("^", "O", "Q", "l", "|", "I", "!", "S", "Z", "tt", "rn", "\r\n", "\n", "\r", "&", "ab0ut", "ab0ve", "ab0w", "abow", "meter5"),
					array("5", "0", "0", "1", "1", "1", "1", "5", "2", "ft", "m", " ", " ", " ", "6", "about", "above", "above", "above", "meters"),
					trim($elevMatches[2])
				);
				return array(trim($elevMatches[1]), $elevation, trim($elevMatches[1]));
			}
			$elevPatStr = "/(?:(?(?=(?:.*?)(?:(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.)?|Alt(?:\\.|itude)?)[:;,]?)\\s".
				"(?:(?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[0-9])".
				"(?:\\s?(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?".
				"\\s{0,2}(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet))[,;]?(?:(?:\\s|\\n|\\r\\n)?(?:.*+))))".

				"(.*?)(?:(?:[ce][li1|][ce]v(?:ati[o0][nr]|\\.?)?|Alt(?:\\.|itude)?)[:;,]?)\\s((?:ab[o0]ut\\s|ab[o0](?:ve|w)\\s|ca\\.?\\s|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[0-9])".
				"(?:\\s?(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}".
				"(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet))[,;]?(?:(?:\\s|\\n|\\r\\n)?(.*+))|".

				"(.*?)(?:(?:E[li1|][ce]v(?:ati[o0][nr]|\\.?)?|Alt(?:\\.|itude)?)[:;,]?)\\s((?:ab[o0]ut\\s|ab[o0]ve\\s|ca(?:\\.?\\s|\\,\\s?)|[-~])?".
				"(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|[SZl|I!&1-9],".$possibleNumbers."{3}|".
				"[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[0-9]])".
				"(?:\\s?(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet)?\\s?-\\s?(?:[l|I!1]".$possibleNumbers.",".$possibleNumbers."{3}|".
				"[SZl|I!&1-9],".$possibleNumbers."{3}|[SZl|I!&1-9^]".$possibleNumbers."{1,4}|[1-9]))?\\s{0,2}".
				"(?:ft\\.|tt\\.|(?:m|rn)(?:\\.|eters?)|Feet))[,;]?(.*+))/is";

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

	protected function containsNumber($s) {
		return preg_match("/\\d+/", $s);
	}

	protected function isText($s) {
		$splitChars = str_split($s);
		foreach($splitChars as $splitChar) {
			$ord = ord($splitChar);
			if(($ord < 65 && $ord != 46) || ($ord > 90 && $ord < 97) || $ord > 122) return FALSE;
		}
		return TRUE;
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

	private function extractCollectorNum($s) {//echo "\ninput to extractCollectorNum, s: ".$s."\n";
		$s = trim($s);
		$sStrs = explode("\n", $s);
		$count = count($sStrs);
		if($count > 0) {
			$possibleNumbers = "[OQSZl|I!&0-9]";
			foreach($sStrs as $sStr) {//echo "\nline 10661, sStr: ".$sStr."\n";
				$sStr = trim($sStr);
				if(preg_match("/(?:(?:N(?:um(?:ber)?|o)\\.)|#)\\s?(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)$/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/N(?:um(?:ber)?|o)\\s(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)$/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^(?:(?:N(?:um(?:ber)?|o)\\.)|#)\\s?(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^N(?:um(?:ber)?|o)\\s(".$possibleNumbers."{0,2}+,?".$possibleNumbers."{3}\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
				if(preg_match("/^N(?:um(?:ber)?|o)\\s(".$possibleNumbers."{1,5}+\\w?+)\\b/i", $sStr, $cMats)) return $this->replaceMistakenNumbers(trim($cMats[1]));
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
				$collPatternStr = "/\\b(?:leg(?:[,.*#]|it))\\s(?:et|and)\\s(?:det(?:\\.|ermined)?|[l1|!i]dent[l1|!i]f[l1|!i]ed)(?:\\sb[vyg])?\\s?[:,;.]{0,2}\\s(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {
					$collector = trim($collMatches2[1]);
					$isIdentifier = true;
				}
				if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
			} else if(preg_match("/\\b(?:C[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}ted)?)(?!\\s(?:on|during)\\sthe\\s)[:,;. *]{1,3}(.+)(?:\\n(.+))?/i", $str, $collMatches)) {
				$collector = trim($collMatches[1]);
				$collPatternStr = "/\\b(?:C[o0D](?:[l1|!I]{2}|U)(?:\\.|[ec]{2}ted)?)(?:\\sand\\s(?:det(?:\\.|ermined)|[l1|!i]dent[l1|!i]f[l1|!i]ed)(?:\\sb[vyg])?)[:,;. *]{1,3}(.+)(?:\\n(.+))?/i";
				if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) {
					$collector = trim($collMatches2[1]);
					$isIdentifier = true;
				} else {
					$collPatternStr = "/C[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}t[ec]d)?(?:\\sand\\sprep(?:ared|[,.])?\\sb[yxv])[:,;. *]{1,3}(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
					if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) $collector = trim($collMatches2[1]);
					else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}ted)\\s?b[vyg][:,;. *]{1,3}(.{3,}+)(?:\\n(.+))?/i", trim($collMatches[0]), $collMatches2)) $collector = trim($collMatches2[1]);
					else if(preg_match("/\\bC[o0D](?:[l1|!i]{2}|U)(?:\\.|[ec]{2}ted)\\s?b[vyg]\\n(.+)(?:\\n(.+))?/i", $str, $collMatches2)) {
						$collector = trim($collMatches2[1]);
						if(count($collMatches2) > 2) $nextLine = trim($collMatches2[2]);
					}
				}
				if(strlen($nextLine) == 0 && count($collMatches) > 2) $nextLine = trim($collMatches[2]);
			} else {
				$collPatternStr = "/C[o0D](?:[l1|!i]{2}|U)(?:[,.*#]|[ec]{2}t[o0]r?s?)?+[:,;. *]{1,3}(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
				if(preg_match($collPatternStr, $str, $collMatches)) {
					$collector = trim($collMatches[1]);
					//$collPatternStr = "/C[o0D](?:[l1|!i]{2}|U)(?:[,.*#]|[ec]{2}tors?)[:,;. *]{1,3}(.+)(?:(?:\\r\\n|\\n|\\r)(.*))?/i";
					//if(preg_match($collPatternStr, trim($collMatches[0]), $collMatches2)) $collector = trim($collMatches2[1]);
					if(count($collMatches) > 2) $nextLine = trim($collMatches[2]);
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
							if($this->containsNumber($nextLine) && !preg_match("/.*(?:".$possibleMonths.").*/i", $nextLine)) {
								$collectorNum = $nextLine;
							}
						}
						return array
						(
							'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
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
			if(strlen($collector) > 1) {//echo "\n10897, collector = ".$collector."\nnextLine: ".$nextLine."\n";
				$collector = trim($collector);
				if(strlen($collector) <= 3 && strcasecmp(substr($collector, 0, 2), "No") == 0) $collector = "";
				$collector = trim(preg_replace("/\\s{2,}/m", " ", $collector));
				if(preg_match("/(.*)Acc(?:[,.]|ession)\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $collector, $cMats)) {//$i=0;foreach($cMats as $cMat) echo "\n6916, cMats[".$i++."] = ".$cMat."\n";
					$collector = trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@$%^&*_-");
					$otherCatalogNumbers = trim($cMats[2]);
				} else if(strlen($nextLine) > 1) {
					if(preg_match("/(.*)Acc(?:[,.]|ession)?\\s(?:[NW][o0Q][.ou]|#)(.*)/i", $nextLine, $cMats)) {
						$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$otherCatalogNumbers = $this->terminateCollectorNum(trim($cMats[2]));
						$nextLine = "";
					}
				}
				if(preg_match("/(.*?)\(No\\.?\\s?([0-9OQ!|lI]+[abc]?)\)/i", $collector, $cMats)) {
					if($isIdentifier) $identifiedBy = $collector;
					return array
					(
						'collectorName' => trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
						'collectorNum' => trim($this->replaceMistakenNumbers(trim($cMats[2])), " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
						'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
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
						return array
						(
							'collectorName' => $collector,
							'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
							'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
						);
					}
				} else if(preg_match("/(.+)\\ss\\.n\\b/i", $collector, $mats)) {
					if($isIdentifier) $identifiedBy = $collector;
					return array
					(
						'collectorName' => trim($mats[1]),
						'collectorNum' => "",
						'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
					);
				} else if($this->containsNumber($collector)) {
					$pos = strrpos($collector, " ");
					if($pos !== FALSE) {
						$firstPart = trim(substr($collector, 0, $pos));
						$lastPart = trim(substr($collector, $pos));
						if($this->containsNumber($lastPart) && !$this->containsNumber($firstPart) &&
							strpos($firstPart, " ") !== FALSE && !preg_match("/(?:".$possibleMonths.")/", $lastPart)) {
							if($isIdentifier) $identifiedBy = $collector;
							return array
							(
								'collectorName' => $firstPart,
								'collectorNum' => trim($lastPart, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
								'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-")
							);
						}
					}
				}
				if(strlen($nextLine) > 0) {
					if(preg_match("/(.*?)\\b(?:[N][o0Q][.o]|#)(.*)/i", $nextLine, $cMats)) {
						$collector .= " ".trim($cMats[1], " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-");
						$temp = trim($cMats[2]);
						if($this->containsNumber($temp)) $collectorNum = $this->terminateCollectorNum($temp);
					} else if(strpos($nextLine, " ") === FALSE && $this->containsNumber($nextLine)) $collectorNum = $nextLine;
				}//echo "\n10897, collector = ".$collector."\nnextLine: ".$nextLine."\ncollectorNum: ".$collectorNum."\n";
				if(preg_match("/(.*)\\bDet[.:;,](.*)/i", $collector, $mats)) {
					$temp = trim($mats[2]);
					$mPat = "/^.+?\\b(?:(?:[o0Q]?+[!|lIZS1-9]|[!|lIZ12][O!|lIZS0-9]|3[O0!|l1I])[ -]\\s?(?:".$possibleMonths.
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
				if(preg_match("/(?:[A-Za-z]+\\s)?by\\s(.*)/i", $collector, $mats)) {
					$collector = trim($mats[1]);
					if(preg_match("/(.+)\\s[A-Za-z ,.]+\\sby\\s.*/i", $collector, $mats2)) {
						if(count($mats2) > 1) $collector = trim($mats2[1]);
					}
				}
				//echo "\nline 10204, collector: ".$collector.", collectorNum: ".$collectorNum."\n";
				if(strlen($collector) > 0) {
					if($isIdentifier) $identifiedBy = $collector;
					if(strlen($collectorNum) > 2) {
						if(strcmp(substr($collectorNum, 0, 1), "(") == 0) {
							if(strcmp(substr(strrev($collectorNum), 0, 1), "(") == 0) $collectorNum = trim($collectorNum, " ()");
						}
					}
					return array
					(
						'collectorName' => trim($collector, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
						'collectorNum' => trim($collectorNum, " \t\n\r\0\x0B.,:;!\"\'\\~@#$%^&*_-"),
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
			if(preg_match($collPatternStr, $str, $collMatches)) {//$i=0;foreach($collMatches as $collMatche) echo "\n6995, collMatches[".$i++."] = ".$collMatche."\n";
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
						&& strcasecmp($word1, "clara") != 0 && strcasecmp($word1, "barbara") != 0 && strcasecmp($word1, "superior") != 0) {
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
				if(preg_match("/^\\w{3,24}\\s\\w{3,24}\\s\([a-zA-Z01üé&. ]{1,14}\\.?\\s?\)\\s?(?:[A-Z]\\.\\s){0,2}[a-zA-Z01ü&. ]{2,19}\\.?$/", $name)) {
					//echo "\nline 1083, match\n";
					return true;
				}
				if(preg_match("/^[a-zA-Z01]{3,24}\\sspp?\\./", $name)) return true;
			}
		}
		return false;
	}

	protected function getCollectorFromDatabase($name, $string) {
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

	protected function findTokenAtEnd($string, $token) {//echo "\nInput to findTokenAtEnd, string: ".$string.", token: ".$token."\n";
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

	protected function getCounty($c) {
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
				$sql = "select lk2.stateName, lk3.countryName from lkupcounty lk1 INNER JOIN ".
					"(lkupstateprovince lk2 inner join lkupcountry lk3 on lk2.countryid = lk3.countryid) ".
					"on lk1.stateid = lk2.stateid ".
					"where lk1.countyName = '".str_replace(array("\"", "'"), "", $c)."'";
				if($r2s = $this->conn->query($sql)) {
					$num_rows = $r2s->num_rows;
					if($num_rows > 0) {
						if($containsAmpersand) $c = $mats1."&".$mats2;
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
							if($containsAmpersand) $c = $mats1."&".$mats2;
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
		$firstPart = trim($firstPart, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		$middlePart = trim($middlePart, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		$lastPart = trim($lastPart, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		if(strpos($lastPart, "\n") !== FALSE) {
			$nextWords = explode("\n", $lastPart);
			$nextWord = trim($nextWords[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
		} else $nextWord = trim($lastPart, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");

		if(strpos($firstPart, "\n") !== FALSE) {
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
		$cString = trim($cString, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
		//echo "\nLine 1010, Input to processCountyString: ".$cString.", nextWord: ".$nextWord."\n";
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

	private function getCountyMatches($c, $state_province="") {//echo "\nLine 1149, Input to getCountyMatches: ".$c."\n";
	//this function has the side-effect of removing double quotes
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

		$tempC = str_replace("\"", "", $c);
		if(preg_match($countyPatStr, $tempC, $countyMatches)) {
			//$i = 0;
			//foreach($countyMatches as $countyMatch) echo "\ncountyMatches[".$i++."] = ".$countyMatch."\n";
			if(count($countyMatches) > 14) return array($countyMatches[14], "", $countyMatches[15]);
			else if(count($countyMatches) > 12) return array($countyMatches[12], "", $countyMatches[13]);
			else if(count($countyMatches) > 10) return array($countyMatches[10], "", $countyMatches[11]);
			else if(count($countyMatches) > 7) return array($countyMatches[7], $countyMatches[8], $countyMatches[9]);
			else if(count($countyMatches) > 4) return array($countyMatches[4], $countyMatches[5], $countyMatches[6]);
			else if(count($countyMatches) > 0) return array($countyMatches[1], $countyMatches[2], $countyMatches[3]);
		}
	}

	protected function findCounty($c, $state_province="") {//echo "\nLine 1193, Input to findCounty: ".$c."\n";
		$firstPart = "";
		$secondPart = "";
		$lastPart = "";
		$state = "";
		$punctuation = "";
		$county = "";
		$country = "";
		$cm = $this->getCountyMatches($c);
		if($cm != null) {
			$firstPart = trim($cm[0]);
			$secondPart = trim($cm[1]);
			$lastPart = trim($cm[2]);
			$cs = $this->processCountyMatches($firstPart, $secondPart, $lastPart);
			if($cs != null) {
				$county = $cs['county'];
				if(array_key_exists('stateProvince', $cs)) $state = $cs['stateProvince'];
				if(array_key_exists('country', $cs)) $country = $cs['country'];
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
		if(strlen($county) == 0) {//the regular expression matches the last occurrence of the word "county" etc.
			//if can't resolve the county try looking for an earlier match
			$cm = $this->getCountyMatches($firstPart);
			if($cm != null) {
				$firstPart = trim($cm[0]);
				$secondPart = trim(trim($cm[1])." ".$secondPart);
				$lastPart = trim($cm[2])." ".$lastPart;
				$cs = $this->processCountyMatches($firstPart, $secondPart, $lastPart);
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
			return array($firstPart, $secondPart, $county, $country, $lastPart, $state);
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

	protected function getTaxonOfHeaderInfo($str) {
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

	protected function processTaxonOfHeaderInfo($matches) {
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
		//echo "\nline 1402, startOfFile: ".$startOfFile."\n\tendOfLine: ".$endOfLine."\n\tendOfFile: ".$endOfFile."\n";
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
			//echo "\nline 1434, firstPart: ".$firstPart."\n\tsecondPart: ".$secondPart."\n";
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
						if(strlen($temp) > 6 && stripos($location, $temp) === FALSE) $location .= " ".$temp;
					} else if(stripos($location, $line) === FALSE) $location .= " ".$line;
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
		return array
		(
			'country' => $country,
			'locality' => $location,
			'stateProvince' => $state_province,
			'verbatimAttributes' => $verbatimAttributes,
			'associatedTaxa' => $associatedTaxa,
			'infraspecificEpithet' => $infraspecificEpithet,
			'taxonRank' => $taxonRank,
			'county' => $county,
			'scientificName' => $scientificName,
			'recordNumber' => $recordNumber,
			'states' => $states,
			'substrate' => $substrate
		);
	}

	protected function getScientificName($s) {
		$sciNamePatStr = "/Scientific Name[:;,]?\\b(.*)/i";
		if(preg_match($sciNamePatStr, $s, $sciNameMatches)) return trim($sciNameMatches[1]);
		return null;
	}

	protected function getMunicipality($s) {
		$townPatStr = "/\\b(?:T[o0]wn|C[lI!|1]ty|V[lI!|1]{3}age) [o0][fr][;:]?\\s([!|0152\\w]+\\s?(?:[0152\\w]+)?)[:;,.]/is";
		if(preg_match($townPatStr, $s, $townMatches)) {
			return str_replace(array("0", "1", "!", "|", "5", "2"), array("O", "l", "l", "l", "S", "Z"), trim($townMatches[1]));
		}
	}

	protected function getLocality($s) {
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

	private function fixString($str) {
		if($str) {
			$catNo = $this->catalogNumber;
			$needles = array("Ã±", "â€¢", "Ã“", "Â»", "Ã¢", "Ã¨", "Ã¬", "Ã¹", "ÃŸ", "Ã ", "Ã¶", "Ãº", "Ã¡", "Ã¤", "Ã¼", "Ã³", "Ã­", "(FÂ£e)", "(F6e)", "/\\_", "/\\", "/'\\_", "/'\\", "/°\\", "AÂ£", " ", " V/", "Â¥", "Miill.", "&gt;", "&lt;", "—", "ï»¿", "&amp;", "&apos;", "&quot;", "\/V", " VV_", " VV.", "\/\/_", "\/\/", "\X/", "\\'X/", chr(157), chr(226).chr(128).chr(156), "Ã©", "/\ch.", "/\.", "/-\\", "X/", "\X/", "\Y/", "`\â€˜i/", chr(96), chr(145), chr(146), "â€˜", "’" , chr(226).chr(128).chr(152), chr(226).chr(128).chr(153), chr(226).chr(128), "“", "”", "”", chr(147), chr(148), chr(152), "Â°", "º", chr(239));
			$replacements = array("ñ", ".", "O", ".", "a", "e", "i", "u", "B", "a", "o", "u", "a", "a", "ü", "o", "i", "(Fée)", "(Fée)", "A.", "A", "A.", "A", "A", "AK", " ", " W ", "W", "Müll.", ">", "<", "-", "", "&", "'", "\"", "W", " W.", " W.", "W.", "W", "W", "W", "", "\"", "é", "Ach.", "A.", "A","W","W", "W", "W", "'", "'", "'", "'", "'", "'", "'", "\"", "\"", "\"", "\"", "\"", "\"", "\"", "°", "°", "°");
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

	protected function getCountry($c) {
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

	protected function isCountry($country) {
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

	protected function isUSState($state) {
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

	protected function removeLeadingZeros($str)
	{
		while(strlen($str) > 0 && substr($str, 0, 1) == "0") $str = substr($str, 1);
		return $str;
	}
}

?>