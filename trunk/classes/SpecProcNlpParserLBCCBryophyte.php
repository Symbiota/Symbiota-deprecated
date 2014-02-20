<?php
class SpecProcNlpParserLBCCBryophyte extends SpecProcNlpParserLBCCCommon{

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
		parent::__destruct();
	}

	protected function getLabelInfo($str, $collId=null) {
		if($str) {
			if($this->isReliquiaeFlowersianaeLabel($str)) return $this->doReliquiaeFlowersianaeLabel($str);
			else return $this->doGenericLabel($str);
		}
		return array();
	}

	private function isReliquiaeFlowersianaeLabel($s) {
		$pat = "/.*Re[1Il!|]{2}[qgO]u[1Il!|]a[ec]\\s?F[1Il!|][O0Q]wers[1Il!|]ana.*/is";
		//$pat = "/.*RELIQUIAE\\sFLOWERSIANA.*/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	protected function containsVerbatimAttribute($pAtt) {
		$vaWords = array("atranorin", "fatty acids?", "cortex", "areolate", "medullae?", "podetiae?", "apotheciae?", "thall(?:us|i)", "strain",
			"squamul(?:es?|ose)", "soredi(?:a(?:te)?|um)", "fruticose", "fruit(?:icose|s)?", "crust(?:ose)?", "corticolous", "saxicolous",
			"terricolous", "Synoicous", "chemotype", "terpene", "isidi(?:a(?:te)?|um)", "TLC", "monoicous", "dioicous", "sporangium",
			"parietin", "anthraquinone", "pigment(?:s|ed)?", "ostiole", "epiphyt(?:e|ic)", "soralia", "spor(?:ophyt)?es?",
			"Archegonia", "antheridia", "archegonia", "androecium", "gynoecium", "Autoicous", "Paroicous", "Heteroicous",
			"cladautoicous", "Gametangia", "paraphyses(?! ?branched\\/)", "pruinose");
		//foreach($vaWords as $vaWord) if(stripos($word, $vaWord) !== FALSE) return true;
		foreach($vaWords as $vaWord) if(preg_match("/".$vaWord."/i", $pAtt)) return true;
		return false;
	}

	private function doReliquiaeFlowersianaeLabel($s) {
		$pattern =
			array
			(
				"/Re[1Il!|]{2}[qgO]u[1Il!|]a[ec]\\s?F[1Il!|][O0Q]wers[1Il!|]anae(?:\\s?ex\\s?herb[.,](?:\\s?UT)?)?/i",
				"/University\\s?[O0]f\\s?C[O0]l[O0]rad[O0](?:\\s?\(C[O0]L[O0]\))?/i",
				"/University\\s?[O0]f\\s?T[ec]nn[ec][s5]{2}[ec]{2}(?:\\s?\(TENN\))?/i",
				"/C[O0]L[O0]-B-[^ ]{2,9}/i",
				"/TENN-B-[^ ]{2,10}/i",
				"/WTU-B-[^ ]{2,10}/i",
				"/U\\.\\s?Wash[1Il!|]ngt[O0]n\\s?Herbar[1Il!|]um(?:\\s?\(?WTU?\)?)?/i",
				"/U\\.\\s?S\\.\\s?A\\.\\s(\\d{2,})/",
				"/U\\.\\s?S\\.\\s?A\\.\\s([a-zA-Z]{6,})/",
				"/\\sBritish Columbia/i",
				"/S.LT LAKE C[O0]\\.:/i"
			);
		$replacement =
			array
			(
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"U. S. A.\n\${1}",
				"U. S. A.\n\${1}",
				"\nBritish Columbia",
				"Salt Lake CO.:"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$state_province = "";
		$location = "";
		$firstPart = "";
		$county = "";
		$habitat = "";
		$taxonRemarks = "";
		$countyMatches = $this->findCounty($s, "");
		if($countyMatches) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 7015, countyMatches[".$i++."] = ".$countyMatche."\n";
			$firstPart = $countyMatches[0];
			$state_province = trim($countyMatches[4]);
			$country = $countyMatches[2];
			$county = $countyMatches[1];
			$location = trim($countyMatches[3]);
			//sometimes the colon after "Co." is misinterpreted as "i"
			if(strcasecmp(substr($location, 0, 2), "i ") == 0) $location = trim(substr($location, 1));
		} else {
			if(preg_match("/^(?:.+\\n)?CANADA\\n(.+)/is", $s, $mats)) {
				$country = "Canada";
				$temp = trim($mats[1]);
				if(preg_match("/(.*)\\n([a-zA-Z]+(?:\\s[a-zA-Z]+(?:\\s[a-zA-Z]+)?)?):\\s(.+)/s", $temp, $mats2)) {
					$temp2 = trim($mats2[2]);
					if($this->isStateOrProvince($temp2)) {
						$state_province = $temp2;
						$firstPart = trim($mats2[1]);
						$location = trim($mats2[3]);
						if(preg_match("/^(on\\s[a-zA-Z ]{6,}),\\s/i", $location, $mats3)) {
							$location = trim($mats3[2]);
							$habitat = trim($mats3[1]);
						} else if(preg_match("/(.+);\\s(.+)/", $location, $mats3)) {
							$location = trim($mats3[1]);
							$habitat = trim($mats3[2]);
							if(preg_match("/(.+)\\s((?:on\\s).+)/", $habitat, $mats4)) {
								$location .= ", ".trim($mats4[1]);
								$habitat = trim($mats4[2]);
							}
						} else if(preg_match("/(.+),\\s(on\\s.+)/", $location, $mats3)) {
							$location = trim($mats3[1]);
							$habitat = trim($mats3[2]);
						}
					}
				}
			}
		}
		if(strlen($firstPart) > 0 || strlen($location) > 0) {//echo "\nline 7058, location: ".$location."\nhabitat: ".$habitat."\n";
			$scientificName = "";
			$infraspecificEpithet = "";
			$taxonRank = "";
			$verbatimAttributes = "";
			$associatedTaxa = "";
			$recordNumber = "";
			$associatedCollectors = "";
			$recordedBy = "";
			$recordedById = "";
			$substrate = "";
			$elevation = "";
			$elevationArray = $this->getElevation($location);
			if($elevationArray != null && count($elevationArray) > 0) {
				$elevation = $elevationArray[1];
				$location = trim(preg_replace("/".preg_quote($elevation, '/')."(?:\\.?[;,]?\\salt\\.?)?/", "", $location), " ,.;:");
			}
			if(strlen($elevation) == 0) {
				$elevationArray = $this->getElevation($habitat);
				if($elevationArray != null && count($elevationArray) > 0) {
					$elevation = $elevationArray[1];
					$habitat = trim(preg_replace("/".preg_quote($elevation, '/')."(?:\\.?[;,]?\\salt\\.?)?/", "", $habitat), " ,.;:");
				}
			}
			$identifiedBy = "";
			$otherCatalogNumbers = "";
			$dateIdentified = array();
			$identifiedBy = "";
			$collectorInfo = $this->getCollector($location);
			if($collectorInfo != null) {
				if(array_key_exists('collectorName', $collectorInfo)) {
					$recordedBy = str_replace(" . ", ", ", $collectorInfo['collectorName']);
					$location = preg_replace("/".preg_quote($recordedBy, '/')."/", "", $location);
				}
				if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
				if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
				if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
			} else {
				$collectorInfo = $this->getCollector($s);
				if($collectorInfo != null) {
					if(array_key_exists('collectorName', $collectorInfo)) {
						$recordedBy = str_replace(" . ", ", ", $collectorInfo['collectorName']);
						$location = preg_replace("/".preg_quote($recordedBy, '/')."/", "", $location);
						$habitat = preg_replace("/".preg_quote($recordedBy, '/')."/", "", $habitat);
					}
					if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
					if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
					if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
					if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
					if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
				}
			}
			$possibleMonths = "Jan(?:\\.|(?:uary))|Feb(?:\\.|(?:ruary))|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
			$identifier = $this->getIdentifier($s, $possibleMonths);
			if($identifier != null) {
				if(strlen($identifiedBy) == 0) $identifiedBy = $identifier[0];
				$dateIdentified = $identifier[1];
			}
			if(preg_match("/^(.+\\n.+)\\n.*/", $location, $mats)) $location = trim($mats[1], " ,.;:");
			$pos = strpos($location, "\n");
			if($pos !== FALSE) {
				$temp1 = trim(substr($location, 0, $pos));
				$temp2 = trim(substr($location, $pos+1));
				$pos = strpos($temp2, "\n");
				if($pos !== FALSE) $temp2 = trim(substr($temp2, 0, $pos));
				$hCount1 = $this->countPotentialHabitatWords($temp1);
				$hCount2 = $this->countPotentialHabitatWords($temp2);
				$lCount1 = $this->countPotentialLocalityWords($temp1);
				$lCount2 = $this->countPotentialLocalityWords($temp2);
				//echo "\nline 7102, temp1: ".$temp1."\ntemp2: ".$temp2."\nlCount2: ".$lCount2."\nhCount1: ".$hCount1."\nhCount2: ".$hCount2."\nlCount1: ".$lCount1."\n";
				if($hCount1 > $lCount1) {
					$habitat = $temp1;
					$location = "";
					if(preg_match("/(.+),\\s(on\\s.+)/i",$habitat , $mats)) {
						$temp1 = trim($mats[1]);
						$temp2 = trim($mats[2]);
						if($this->countPotentialHabitatWords($temp2) > 0) {
							$habitat = $temp2;
							$location = $temp1;
						}
					}
				} else $location = $temp1;
				if($lCount2 > 0) {
					if(strlen($location) == 0) $location = $temp2;
					else if(stripos($location, $temp2) === FALSE) $location .= " ".$temp2;
				} else if($hCount2 > 0) {
					if(strlen($habitat) == 0) $habitat = $temp2;
					else if(stripos($habitat, $temp2) === FALSE) $habitat .= " ".$temp2;
					if(preg_match("/(.+),\\son$/i", $location, $mats)) {
						$location = trim($mats[1]);
						$pos = strpos($habitat, ";");
						if($pos === FALSE) $pos = strpos($habitat, ",");
						if($pos !== FALSE) {
							$substrate = "on ".trim(substr($habitat, 0, $pos));
							$habitat = trim(substr($habitat, $pos+1));
						} else {
							$substrate = "on ".$habitat;
							$habitat = "";
						}
					} else if(preg_match("/(.+),\\s(on\\s[a-zA-Z]+(?:\\s[a-zA-Z]+)?(?:\\s[a-zA-Z]+)?)$/i", $location, $mats)) {
						$location = trim($mats[1]);
						$substrate = trim($mats[2]);
						if(preg_match("/^([a-zA-Z]+)\\s(in\\s.+)/i", $habitat, $mats2)) {
							$substrate .= " ".trim($mats2[1]);
							$habitat = trim($mats2[2]);
						} else if(preg_match("/^([a-zA-Z]+(?:\\s(?:(?:&|and)\\s)?[a-zA-Z]+)?),\\s(.+)/i", $habitat, $mats2)) {
							$substrate .= " ".trim($mats2[1]);
							$habitat = trim($mats2[2]);
						}
					}
				}
			}
			$pos = strrpos($location, ", ");
			if($pos === FALSE) $pos = strrpos($location, "; ");
			if($pos !== FALSE) {
				$temp1 = trim(substr($location, 0, $pos));
				$temp2 = trim(substr($location, $pos+1));
				$hCount1 = $this->countPotentialHabitatWords($temp1);
				$hCount2 = $this->countPotentialHabitatWords($temp2);
				$lCount1 = $this->countPotentialLocalityWords($temp1);
				$lCount2 = $this->countPotentialLocalityWords($temp2);
				//echo "\nline 7102, temp1: ".$temp1."\ntemp2: ".$temp2."\nlCount2: ".$lCount2."\nhCount1: ".$hCount1."\nhCount2: ".$hCount2."\nlCount1: ".$lCount1."\n";
				if($hCount1 > $lCount1) {
					if(strlen($habitat) == 0) $habitat = $temp1;
					else if(stripos($habitat, $temp1) === FALSE) $habitat .= " ".$temp1;
					if($lCount2 > 0) $location = $temp2;
				} else if($hCount2 > $lCount2) {
					if(strlen($habitat) == 0) $habitat = $temp2;
					else if(stripos($habitat, $temp2) === FALSE) $habitat .= " ".$temp2;
					if($lCount1 > 0) $location = $temp1;
				} else {
					$pos = strrpos($habitat, ", ");
					if($pos === FALSE) $pos = strrpos($habitat, "; ");
					if($pos !== FALSE) {
						$temp1 = trim(substr($habitat, 0, $pos));
						$temp2 = trim(substr($habitat, $pos+1));
						$hCount1 = $this->countPotentialHabitatWords($temp1);
						$hCount2 = $this->countPotentialHabitatWords($temp2);
						$lCount1 = $this->countPotentialLocalityWords($temp1);
						$lCount2 = $this->countPotentialLocalityWords($temp2);
						if($hCount1 > $lCount1) {
							if(stripos($habitat, $temp1) === FALSE) $habitat = $temp1." ".$habitat;
							if($lCount2 > 0 && stripos($location, $temp2) === FALSE) $location .= " ".$temp2;
						} else if($hCount2 > $lCount2) {
							if(stripos($habitat, $temp2) === FALSE) $habitat = $temp2." ".$habitat;
							if($lCount1 > 0 && stripos($location, $temp1) === FALSE) $location .= " ".$temp1;
						}
					}
				}
			}
			//echo "\nline 7111, location: ".$location."\nhabitat: ".$habitat."\n";
			$lines = explode("\n", $firstPart);
			foreach($lines as $line) {//echo "\nline 6784, line: ".$line."\n";
				$line = trim($line);
				if(strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
						if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
						if(array_key_exists('taxonRemarks', $psn)) $substrate = $psn['taxonRemarks'];
						break;
					}
				}
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
				'taxonRemarks' => trim($taxonRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'dateIdentified' => $dateIdentified,
				'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_"),
				'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'recordedById' => trim($recordedById, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
				'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"),
				'associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-")
			);
		} else return $this->doGenericLabel($s);
	}
}
?>