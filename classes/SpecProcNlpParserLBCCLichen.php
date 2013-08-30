<?php
class SpecProcNlpParserLBCCLichen extends SpecProcNlpParserLBCCCommon{

	private $lichenOnlyVariables;
	
	function __construct() {
		parent::__construct();
	}
	
	function __destruct(){
		parent::__destruct();
	}
	
	public function doKienerMemorialLabel($s) {
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
			'verbatimCoordinates' => $verbatimCoordinates
		);
	}
	
}

?>