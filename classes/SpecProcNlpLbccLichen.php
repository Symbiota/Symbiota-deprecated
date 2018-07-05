<?php
include_once($SERVER_ROOT.'/classes/SpecProcNlpLbcc.php');

class SpecProcNlpLbccLichen extends SpecProcNlpLbcc {

	function __construct() {
		parent::__construct();
	}

	protected function getLabelInfo($str) {//return array('associatedCollectors' => $str);
		if($str) {
			$pat = "/(.*)HERBAR[1Il!|]UM\\s?[O0Q]F\\s?THE UN[1Il!|]VER[S5][1Il!|]TY\\s?[O0Q]F\\s?M[1Il!|]CH[1Il!|]GAN?+(.*)/is";
			if(preg_match($pat, $str, $matches)) {
				$str = trim(trim($matches[1])."\n".trim($matches[2]));
				$pat = "/(.*)Un[1Il!|]v[ec]r[S5][1Il!|]ty\\s?[O0Q]f\\s?M[1Il!|][ec]h[1Il!|]gan\\s?Fungu[S5]\\s?C[O0Q][1Il!|]{2}[ec]ct[1Il!|][O0Q]n?+(.*)/is";
				if(preg_match($pat, $str, $matches2)) $str = trim(trim($matches2[1])."\n".trim($matches2[2]));
			}
			if($this->isReliquiaeTuckermanianaeLabel($str)) return $this->doReliquiaeTuckermanianaeLabel($str);
			else if($this->isReliquiaeFarlowianaeLabel($str)) return $this->doReliquiaeFarlowianaeLabel($str);
			else if($this->isFarlowLabel($str)) return $this->doFarlowLabel($str);
			else if($this->isFloraOfAlaskaLabel($str)) return $this->doFloraOfAlaskaLabel($str);
			else if($this->isAlcanExpeditionLabel($str)) return $this->doAlcanExpeditionLabel($str);
			else if($this->isLichenesArticiLabel($str)) return $this->doLichenesArticiLabel($str);
			else if($this->isHattoriTennesseeLabel($str)) return $this->doHattoriTennesseeLabel($str);
			else if($this->isLichensOfFloridaLabel($str)) return $this->doLichensOfFloridaLabel($str);
			else if($this->isLichensOfEasternNorthAmericaLabel($str)) return $this->doLichensOfEasternNorthAmericaLabel($str);
			else if($this->isLendemerLichenHerbariumLabel($str)) return $this->doLendemerLichenHerbariumLabel($str);
			else if($this->isExHerbariumOfTheLabel($str)) return $this->doLendemerLichenHerbariumLabel($str);
			else if($this->isHerbariumOfMontanaStateUniversityLabel($str)) return $this->doHerbariumOfMontanaStateUniversityLabel($str);
			else if($this->isMontanaStateUniversityHerbariumLabel($str)) return $this->doMontanaStateUniversityHerbariumLabel($str);
			else if($this->isBorealiAmericaniLabel($str)) return $this->doBorealiAmericaniLabel($str);
			else if($this->isLichenesExsiccatiLabel($str)) return $this->doLichenesExsiccatiLabel($str);
			else if($this->isLichensAndMossesOfYellowstoneLabel($str)) return $this->doLichensAndMossesOfYellowstoneLabel($str);
			else if($this->isLichensOfWesternNorthAmericaLabel($str)) return $this->doLichensOfWesternNorthAmericaLabel($str);
			else if($this->isDecadesOfNorthAmericanLichensLabel($str)) return $this->doDecadesOfNorthAmericanLichensLabel($str);
			else if($this->isMycologicalCollectionsLabel($str)) return $this->doMycologicalCollectionsLabel($str);
			else if($this->isLichenesGroenlandiciLabel($str)) return $this->doLichenesGroenlandiciLabel($str);
			else if($this->isLewisAndClarkCavernsLabel($str)) return $this->doMTLichensOfLabel($str);
			else if($this->isPlantsOfWisconsinLabel($str)) return $this->doPlantsOfWisconsinLabel($str);
			else if($this->isLichenesCanadensesLabel($str)) return $this->doLichenesCanadensesLabel($str);
			else if($this->isVezdaLichenesSelectiExsiccatiLabel($str)) return $this->doVezdaLichenesSelectiExsiccatiLabel($str);
			else if($this->isLichenesWisconsinensesExsiccatiLabel($str)) return $this->doLichenesWisconsinensesExsiccatiLabel($str);
			else if($this->isLichenesRarioresEtCriticiExsiccatiLabel($str)) return $this->doLichenesRarioresEtCriticiExsiccatiLabel($str);
			else if($this->isKryptogamaeExsiccatiVindobonensiLabel($str)) return $this->doKryptogamaeExsiccatiVindobonensiLabel($str);
			else if($this->isLichenesIsidiosiEtSorediosiExsiccatiLabel($str)) return $this->doLichenesIsidiosiEtSorediosiExsiccatiLabel($str);
			else if($this->isCladoniaExsiccataeSandstedeLabel($str)) return $this->doCladoniaExsiccataeSandstedeLabel($str);
			else if($this->isCalicialesExsiccataeLabel($str)) return $this->doCalicialesExsiccataeLabel($str);
			else if($this->isLichenesAmericaniExsiccatiLabel($str)) return $this->doLichenesAmericaniExsiccatiLabel($str);
			else if($this->isCanadianLichensLabel($str)) return $this->doCanadianLichensLabel($str);
			else if($this->isLichenesRarioresExsiccatiLabel($str)) return $this->doLichenesRarioresExsiccatiLabel($str);
			else if($this->isKienerMemorialLabel($str)) return $this->doKienerMemorialLabel($str);
			else if($this->isMultipleChoiceLabel($str)) return array();
			else if($this->collId == 42 && $this->isLichensOfLabel($str)) return $this->doMTLichensOfLabel($str);
			else if($this->collId == 42 && $this->isHerbariumOfForestServiceLabel($str)) return array();
			else return $this->doGenericLabel($str);
		}
		return array();
	}

	private function isReliquiaeTuckermanianaeLabel($s) {
		$pat = "/.*\\bRE[il1!|]{2}[DOQ]U[il1!|]AE TUCK.?ERMAN[il1!|]ANA.?/i";
		if(preg_match($pat, $s)) return true;
		else if(preg_match("/.*\\bRe[il1!|]{2}qu[il1!|]ae.?[TF]U[CG]KERM ?AN[il1!|] ?ANA.?/i", $s)) return true;
		else return false;
	}

	private function isReliquiaeFarlowianaeLabel($s) {//only interested in finding the Farlow labels that are labeled as such at the top
		$pat = "/.*\\bRe[il1!|]{2}qu[il1!|]ae.?Far[il1!|]ow[il1!|]ana.?/i";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isFarlowLabel($s) {//only interested in finding the Farlow labels that are labeled as such at the top
		$farlowPat = "/.*\\bFarlow Herbarium\\b.{0,12}\\bHarvard\\b.*(?:\\n|\\n\\r).*(?:\\n|\\n\\r).*/i";
		if(preg_match($farlowPat, $s)) return true;
		else return false;
	}

	private function isCanadianLichensLabel($s) {//only interested in finding the Farlow labels that are labeled as such at the top
		if(preg_match("/.*\\b[CG]anadian L[1Il!|][ce]h[ce]n.*Ma[ce][0o]un\\b.*/i", $s)) return true;
		else return false;
	}

	private function doCanadianLichensLabel($s) {//only interested in finding the Farlow labels that are labeled as such at the top
		$s = preg_replace(
			array(
				"/WALTER\\sK[1Il!|]ENER\\sMEM[0OQ]R[1Il!|]AL\\sL[1Il!|]CHEN\\s[CG][0OQ]LLE[CG]T[1Il!|][0OQ]N/is",
				"/ Co[1Il!|]{2}(?:[,.]|ected) by /i",
				"/.\\b[CG]anad[1Il!|]an L[1Il!|][ce]h[ce]n.*/i"
			),
			array(
				"",
				"\nColl. by ",
				""
			),
			$s);
		return $this->doGenericLabel($s, "297", array('country' => "Canada"));
	}

	private function isFloraOfAlaskaLabel($s) {//there are 2 kinds of FloraOfAlaska labels: The ones with AKFWS herbarium
		//and those with a Lat/Long label
		$alaPat = "/.*FL[O0]\\wA[.,]?\\s[O0Q]\\w\\sA[1Il!|]A[S5]KA.*/is";
		if(preg_match($alaPat, $s)) return true;
		else return false;
	}

	private function isAlcanExpeditionLabel($s) {
		$akfwsPat = "/.*AL[CE]AN [CE]XP[CE]D[1Il!|]T[1Il!|][O0Q]N.+/is";
		if(preg_match($akfwsPat, $s)) return true;
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

	private function isLichensOfFloridaLabel($s) {
		$lfPat = "/.*(?:L[1Il!|][CE]H[CE]N|Cr[ypqg]{2}t[O0Q][ypqg]am|P[1Il!|]ant)'?[S5]\\s[O0Q]F\\sFL[O0Q]R[1Il!|]DA?+.*/is";
		if(preg_match($lfPat, $s)) return true;
		else return false;
	}

	private function isLendemerLichenHerbariumLabel($s) {
		$lendemerPat = "/.*L[1Il!|]ch[ce]n.?H[ce]rbar[1Il!|]um.?[O0Q]f.?Jam[ce]s.?[CG]\\.?.?L[ce]nd[ce]m[ce]r.*/is";
		if(preg_match($lendemerPat, $s)) return true;
		else if(preg_match("/.*Herbarium\\s[O0]f\\sthe\\sAcademy\\s[O0]f\\sNatura[1Il!|]\\sSciences [O0]f\\sPh[1Il!|]{2}ade[1Il!|]ph[1Il!|]a\\s(?:\(PH\))?.*/is", $s)) return true;
		else return false;
	}

	private function isExHerbariumOfTheLabel($s) {
		$pat = "/.*Ex\\sH[CE]rbar[1Il!|]um.?[O0Q]f.?th[ec].*/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isHerbariumOfMontanaStateUniversityLabel($s) {
		if(preg_match("/.*HERBAR[I1!|]UM [O0Q]F .[O0Q]NTANA [S5]TATE UN[I1!|]VER[S5][I1!|]TY(.*)/is", $s)) return true;
		if(preg_match("/.*ERBAR[1Il!|]UM\\s[O0Q].\\sM[O0Q]NTANA\\s[S5]TATE\\sUN[1Il!|]VERS[1Il!|]T.*/is", $s)) return true;
		if(preg_match("/.*ERBAR[1Il!|]UM\\s[O0QC]F\\sM[O0Q]NTANA\\s[S5]TATE\\sUN[1Il!|]VERS[1Il!|]T.*/is", $s)) return true;
		if(preg_match("/.*OF\\sM[O0Q]NTANA\\s?STATE\\s?UN[1Il!|]V.*/is", $s)) return true;
		if(preg_match("/.*M\\sOF\\sM[O0Q]NTANA\\s?STATE.*/is", $s)) return true;
		return false;
	}

	private function isMontanaStateUniversityHerbariumLabel($s) {
		if(preg_match("/.*M[O0Q]NTANA\\s?STATE\\s?UN[1Il!|]VERSITY\\s?HERBAR[1Il!|]U.*/is", $s)) return true;
		else return false;
	}

	private function isVezdaLichenesSelectiExsiccatiLabel($s) {
		if(preg_match("/.*(?:[1Il!|]{2}|U)chene[S5] [S5]e[1Il!|]ect[1Il!|]\\sExs[1Il!|]ccat.*/is", $s) && preg_match("/.*\\bV.{1,2}zda\\b.*/i", $s)) return true;
		else return false;
	}

	private function isLichenesWisconsinensesExsiccatiLabel($s) {
		if(preg_match("/.*(?:[1Il!|]{2}|U)[CE]H[CE]N[CE][S5]\\sW[1Il!|][S5]CON[S58][1Il!|]N[CE]N[S5][CE][S5]\\sExs[1Il!|]ccat.*/is", $s)) return true;
		else return false;
	}

	private function isLichenesRarioresEtCriticiExsiccatiLabel($s) {
		if(preg_match("/.*(?:[1Il!|]{2}|U)CHENE[S5] RAR[1Il!|][O0Q]RE[S5] ET CR[1Il!|]T[1Il!|]C[1Il!|] Exs[1Il!|]ccat.*/is", $s)) return true;
		else return false;
	}

	private function isLichenesRarioresExsiccatiLabel($s) {
		if(preg_match("/.*(?:[1Il!|]{2}|U)[CG]HENE[S5] RAR[1Il!|][O0QC]RE[S5]\\sExs[1Il!|][ceg]{2}at.*/is", $s)) return true;
		else return false;
	}

	private function doLichenesRarioresExsiccatiLabel($s) {
		if(preg_match("/\\bV.{0,2}zda\\b[.,;:]/i", $s)) return $this->doVezdaLichenesRarioresExsiccatiLabel($s);
		else return $this->doZahlbrucknerLichenesRarioresExsiccatiLabel($s);
	}

	private function doVezdaLichenesRarioresExsiccatiLabel($s) {
		$s = preg_replace(
			array(
				"/(?:A. ?V.{0,2}ZDA[.,;:] ?)?(?:[1Il!|]{2}|U)[CG]HENE[S5] RAR[1Il!|][O0QC]RE[S5]\\sExs[1Il!|][ceg]{2}at[1Il!|]?[E .]{0,2}+/is"
			),
			array(
				""
			),
			$s);
		return $this->doGenericLabel(str_replace("\n\n", "\n", $s), "340");
	}

	private function doZahlbrucknerLichenesRarioresExsiccatiLabel($s) {//only interested in finding the Farlow labels that are labeled as such at the top
		$s = preg_replace(
			array(
				"/(?:A. ?ZAHLBRUC ?KNER(?:-K. RED[1Il!|]NGER)?[.,;:] ?)?(?:[1Il!|]{2}|U)[CG]HENE[S5] RAR[1Il!|][O0QC]RE[S5]\\sExs[1Il!|][ceg]{2}at[1Il!|]?[E .]{0,2}+/is",
				"/Nr?\\.? (\\d{1,3})\\.\\n/",
				"/\\n[A-Z][^1-9]{0,3}(\\d{1,3})\\.?\\n/",
			),
			array(
				"",
				"No. \${1}. ",
				"\nNo. \${1}. "
			),
			$s);
		$s = str_replace("\n\n", "\n", $s);
		//echo "\nline 6355, s:\n".$s."\n";
		$exsNumber = "";
		$scientificName = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$taxonRemarks = "";
		$substrate = "";
		$lines = explode("\n", $s);
		foreach($lines as $line) {
			$line = trim($line);
			$psn = $this->processSciName($line);
			if($psn != null) {//foreach($psn as $k => $v) echo "\nline 14403, ".$k.": ".$v."\n";
				if(array_key_exists('scientificName', $psn)) $scientificName = $psn['scientificName'];
				if(array_key_exists('infraspecificEpithet', $psn)) {
					$infraspecificEpithet = $psn['infraspecificEpithet'];
					if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
				}
				if(array_key_exists('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
				if(array_key_exists('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
				if(array_key_exists('recordNumber', $psn)) $exsNumber = $psn['recordNumber'];
				if(array_key_exists('taxonRemarks', $psn)) $taxonRemarks = $psn['taxonRemarks'];
				if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
			}
		}
		$ometid = "100";
		if($exsNumber > 288) $ometid = "352";
		$fields = array
			(
				'scientificName' => $scientificName,
				'infraspecificEpithet' => $infraspecificEpithet,
				'taxonRank' => $taxonRank,
				'verbatimAttributes' => $verbatimAttributes,
				'associatedTaxa' => $associatedTaxa,
				'taxonRemarks' => $taxonRemarks,
				'substrate' => $substrate,
				'exsNumber' => $exsNumber
			);
		return $this->doGenericLabel($s, $ometid, $fields);
	}

	private function isBorealiAmericaniLabel($s) {
		//$s = preg_quote($s, "/");
		if(preg_match("/LICHENES BO.?REA.{1,3}LI-AMERICANI(.*)/is", $s)) return true;
		else if(preg_match("/.*LICHENES BO.EA[1Il!|]{2}-AME.[1Il!|]CA.+/is", $s)) return true;
		else {
			$baPat = "/.*[BES5][ ._#-]{1,2}B[O0Q]R[CE]A[1Il!|]{2}[ ._#-]{1,2}AM[CE].[1Il!|][CE]an[1Il!|]?.+/is";
			if(preg_match($baPat, $s)) return true;
			else {
				$baPat = "/.*[1Il!|][CE]H[CE]N[BES5]{2}[ ._#-]{1,2}B[O0Q].{2}a[1Il!|]{2}[ ._#-]{1,2}Am[CE]r[1Il!|][CE]an[1Il!|]?.+/is";
				if(preg_match($baPat, $s)) return true;
				else {
					$baPat = "/.*[1Il!|]{2}[CE]H[CE]N[BES5]{2}[ ._#-]{1,2}B[O0Q]rea[1Il!|].?[1Il!|][ ._#-]{1,2}Am[CE].[1Il!|][CE].?an.?[1Il!|]?.+/is";
					if(preg_match($baPat, $s)) return true;
					else if(preg_match("/L[1Il!|]CHENES\\sBO.EA[1Il!|]{2}.{2,4}AM.{1,7}[1Il!|]/i", $s)) return true;
					else if(preg_match("/L[1Il!|]CHENES\\sBORE ?A[1Il!|].{2,4}AM.{1,7}[1Il!|]/i", $s)) return true;
					else if(preg_match("/L[1Il!|]CHENES\\sB ?ORE ?A[1Il!|].{2,4}AM.{1,7}[1Il!|]/i", $s)) return true;
					else if(preg_match("/L[1Il!|]CHENES\\sB ?ORE ?A.{1,2}[1Il!|].{1,3}AM.{1,7}[1Il!|]/i", $s)) return true;
					else if(preg_match("/L[1Il!|]CHEN ES\\sBO ?RE ?A.{1,2}[1Il!|].{1,3}AM.{1,7}[1Il!|]/i", $s)) return true;
					else if(preg_match("/.*LICHENES BO.{1,3}E.?A[1Il!|].{1,3}-AM.{2,4}CAN.+/is", $s)) return true;
					else if(preg_match("/.*L[1Il!|].{2,4}NES B[O0Q].{2,3}A[1Il!|]{2,3}-AM.{2,4}CAN.+/is", $s)) return true;
					else if(preg_match("/.*L[1Il!|]C[1Il!|].{2,4}N{2,3} B[O0Q][RB]E.{1,2}A[1Il!|]{2,3}-AM.{2,4}CAN.+/is", $s)) return true;
					else if(preg_match("/.*L[1Il!|][CE]H[CE]N.?[BES5]{2} B.?[O0Q].{2,3}A[1Il!|]{2,3}-AM.{2,4}CAN.+/is", $s)) return true;
					else if(preg_match("/.*[1Il!|][CE]H[CE]N.?[BES5]{2} B.?[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA[1Il!|].[1Il!|]+/is", $s)) return true;
					else if(preg_match("/.*L[1Il!|].{2,7} B.?[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA[1Il!|]N[1Il!|].*/is", $s)) return true;
					else if(preg_match("/.*L[1Il!|][CE]H[CE].?N[BES5]{2} B[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA.N.{1,2}.*/is", $s)) return true;
					else if(preg_match("/.*L[1Il!|][CE]H[CE].{2,3}[BES5]{2} B[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA.?N.{1,2}.*/is", $s)) return true;
					else if(preg_match("/.*[BES5]{2} B[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CAN.{1,2}.*/is", $s)) return true;
					else if(preg_match("/.*N[BES5]{2} B[O0Q]REA[1Il!|] [1Il!|]-AMER[1Il!|]C.AN.{1,2}(.*)/is", $s)) return true;
				}
			}
		}
		return false;
	}

	private function isLichenesExsiccatiLabel($s) {
		$exsiccatiPat = "/.*(?:L[1Il!|]|IZ|U|X)(?:[CE]H|QI)[CE][NH][CE][S5]\\s[CE]X[S5][1Il!|][CE]{2}AT[1Il!|X].*/is";
		if(preg_match($exsiccatiPat, $s)) return true;
		else if(preg_match("/.*(?:L[1Il!|]|U)[CE]H[CE]N[CE][S5]\\s[CE]X[S5][1Il!|][CE]{1,2}.?A.*/is", $s)) return true;
		else if(preg_match("/L[1Il!|][CG]H ?EN ?E[S5] EXS[1Il!|][CG]{2}AT[1Il!|].*/is", $s)) return true;
		else if(preg_match("/L[1Il!|][CG](?:H|II)ENE[S5] EX[S5][1Il!|][CG]{2}AT[1Il!|].*/is", $s)) return true;
		else if(preg_match("/.*CHENE[S5] EXSI[CG]{2}AT[1Il!|].*/is", $s)) return true;
		else return false;
	}

	private function isLichensAndMossesOfYellowstoneLabel($s) {
		$yPat = "/.*(?:L[1Il!|]|U)[CE]H[CE]N[S5]\\sAND\\sM[O0Q]sses.*(?:[O0Q]F)?.*YE[1Il!|]{2}[O0Q]WST[O0Q]NE.*/is";
		if(preg_match($yPat, $s)) return true;
		else return false;
	}

	private function isLichensOfWesternNorthAmericaLabel($s) {
		$pat = "/.*[1Il!|][CE]H[CE]N[S5]\\s[O0Q]f\\sWestern\\sN[O0Q]rth\\sAm[CE]r[1Il!|]ca.*/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isDecadesOfNorthAmericanLichensLabel($s) {
		$pat = "/.*[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichen.*/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isKienerMemorialLabel($s) {
		$kienerPat = "/.*\\sK[1!lI]ENER\\sMEMOR[1!lI]A(?:L|[|!][-_]).*/i";
		if(preg_match($kienerPat, $s)) return true;
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

	private function isLichenesGroenlandiciLabel($s) {
		$pat = "/.*[1Il!|][CE]H[CE]N[BES5]{2}\\s?[CG]R[O0QD][CE]NLAND[1Il!|]C[1Il!|]\\s?[CE]XS[1Il!|][CE]{2}AT[1Il!|].+/is";
		if(preg_match($pat, $s)) return true;
		else return false;
	}

	private function isLewisAndClarkCavernsLabel($s) {
		$pat = "/L[EF]W\\s?[1Il!|]S\\s?A.{2,7}\\s?CLARK\\s?C\\s?AV\\s?ERNS\\s?STATE\\sPARK(.*)/s";
		if(preg_match($pat, $s)) return true;
		return false;
	}

	private function isPlantsOfWisconsinLabel($s) {
		$wisPat = "/.*[PF][1Il!|]ant[s5].[O0Q]F.W[1Il!|][s5]con[s5][1Il!|]n.*/is";
		if(preg_match($wisPat, $s)) return true;
		else return false;
	}

	private function isLichenesCanadensesLabel($s) {
		$exsiccatiPat = "/.*(?:L[1Il!|]|IZ|U)(?:[CG]H|QI)[FE][NH][FE]?[S5]\\s[CG]ANAD[FE]N[S5][FE][S5].*/is";
		if(preg_match($exsiccatiPat, $s)) return true;
		else return false;
	}

	private function isLichensOfLabel($s) {
		$lichensOfPat = "/.*(?:Macro)?(?:U|L[1Il!|]|[1Il!|])ch[CE]ns\\s(?:[O0Q]F|FR[O0Q]M)\\b.*/is";
		if(preg_match($lichensOfPat, $s)) return true;
		else if(preg_match("/.*hens\\s(?:[O0Q]F|FR[O0Q]M)\\b.*/is", $s)) return true;
		else return false;
	}

	private function isHerbariumOfForestServiceLabel($s) {
		$lfPat = "/.*HERBAR[1Il!|]UM\\s?[O0Q]F\\s?THE\\s?F[O0Q]REST\\s?SERV[1Il!|]CE.*/is";
		if(preg_match($lfPat, $s)) return true;
		else if(preg_match("/.*HERBAR[1Il!|]UM\\s?[O0Q]F\\s?THE\\s?F[O0Q]REST.{2,5}V[1Il!|]CE.{6,30}DEPARTMENT.?[O0Q]F.?A[GC]RI[GC]ULTURE/is", $s)) return true;
		else return false;
	}

	private function isLichenFloraOfAlaskaLabel($s) {
		$akfwsPat = "/.*(?:L[1Il!|][CE]H[CE]N|[GC]RYPT[O0Q][GC]AM[1Il!|][GC])\\s?FL[O0Q]\\wA[.,]?\\s[O0Q]\\w\\sA[1Il!|]A[S5]KA.*/is";
		if(preg_match($akfwsPat, $s)) return true;
		else return false;
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
		if(preg_match("/.*K\\. M[a-z]rr[1Il!|]{2,3}.*/is", $s)) return true;
		else if(preg_match("/.*\\bM[ec]rr[1Il!|]{2,3}.*/is", $s)) return true;
		else if(preg_match("/.*R[0o]ckp[0o]rt,\\sMain[CE].*/is", $s)) return true;
		else if(preg_match("/.*Pr[CE]par[CE]d\\sby\\sG.*/is", $s)) return true;
		else return false;
	}

	private function isWeberLichenesExsiccatiLabel($s) {
		$weberPat = "/.*[OD][1Il!|][-s5�][a-zA-Z10!|\'\"\/ -]{12,42}ado\\s[MW]us[CE]u[mn].*/is";
		if(preg_match($weberPat, $s)) return true;
		else {
			$weberPat = "/.*[OD[1Il!|].{2,3}tributed\\sby\\sthe\\sUniversity\\sof\\sColorado\\s.{1,2}us[CE]u[mn].*/is";
			if(preg_match($weberPat, $s)) return true;
			else if(preg_match("/.*Colorado\\sM[ui]seum,\\sB.*/is", $s)) return true;
			else if(preg_match("/.*Colorado\\sM[ui]seum,.*/is", $s)) return true;
			else return false;
		}
	}

	private function isASULichenesExsiccatiLabel($s) {
		$asuPat = "/.*[OD][1Il!|][-s5][a-zA-Z10!|\'\"\/ -]{6,42}ona\\s[S5]t.t[CE]\\sUn.vers[1Il!|]t.*/is";
		if(preg_match($asuPat, $s)) return true;
		else {
			$asuPat = "/.*A\\.?S\\.?U\\.?\\s[a-zA-Z10!|.]{2,4}\\s[0-9I!|lO]{2,3}.*/is";
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
		//this function is called on labels that already match isFloraOfAlaska
		if(preg_match("/A.{1,3}[S53]\\s[HBS]E[RSEK]B.+/is", $s)) return true;
		else if(preg_match("/A.F.[S53]\\s[HBS]E[RSEK]B.+/is", $s)) return true;
		else if(preg_match("/A.F.[S53]\\sHE[RSEK].+/is", $s)) return true;
		else if(preg_match("/A.F.[S53]\\sH.RBAR.+/is", $s)) return true;
		else if(preg_match("/AK.{1,2}[S53]\\sHE[RSEK]BA.+/is", $s)) return true;
		else if(preg_match("/A.{4,5} HE[RSEK]BAR[1Il!|]U.+/is", $s)) return true;
		else if(preg_match("/.{1,3}F[UVW][S53] [HBS]E[RSEK]BAR[1Il!|]U.+/is", $s)) return true;
		else if(preg_match("/A.{2,4}[S53]\\sHE[RSEK]BAR[1Il!|].+/is", $s)) return true;
		else if(preg_match("/A.{1,2}[VUW][S53]\\sHE[RSEK]BA.+/is", $s)) return true;
		else if(preg_match("/A.{1,2}FW[S53]\\sHE[RSEK]BA.+/is", $s)) return true;
		else if(preg_match("/AKF.{1,2}[S53]\\sHE[RSEK]BA.+/is", $s)) return true;
		else if(preg_match("/[AL][A-Za-z]F[A-Za-z][S53]\\s[HBS]E[RSEK]B[A-Za-z0-9!| ]{4,10}.+/is", $s)) return true;
		else if(preg_match("/A[A-Za-z]{1,2}[VUW][A-Za-z]{1,2} HE[RSEK] ?B[A-Za-z0-9!| ]{4,10}.+/is", $s)) return true;
		else if(preg_match("/A[A-Za-z]{1,2}[VUW][A-Za-z]{1,2} HE[RSEK] ?B[A-Za-z0-9!| ]{4,10}.+/is", $s)) return true;
		else return false;
	}

	private function isMultipleChoiceLabel($s) {//these labels contain choices for the fields that have to be underlined or circled and can't be parsed
		if(preg_match("/\\nLIGHT: Sunny/is", $s)) return true;
		if(preg_match("/\\nWATER: Dry/is", $s)) return true;
		if(preg_match("/\\nTOPOG: Ridgetop/is", $s)) return true;
		if(preg_match("/\\nHABIT: Forest/is", $s)) return true;
		if(preg_match("/\\nNAME OF ROCKS OR TREES: /", $s))  return true;
		return false;
	}

	private function isLichenesIsidiosiEtSorediosiExsiccatiLabel($s) {
		if(preg_match("/(?:[1Il!|]{2}|U)chenes ?[1!lI]s[1!lI]d[1!lI][0o]s[1!lI] ?Et ?S[0o]red[1!lI][0o]s[1!lI] ?Crustacei ?Exs[1!lI]ccat./is", $s)) return true;
		else return false;
	}

	private function isLichenesAmericaniExsiccatiLabel($s) {
		if(preg_match("/(?:[1Il!|]{2}|U)chene[s5] ?Amer[1Il!|]can[1Il!|] ?Exs[1!lI][ce]{2}at./is", $s)) return true;
		else return false;
	}

	private function isCladoniaExsiccataeSandstedeLabel($s) {
		if(preg_match("/C[1Il!|]adonia[ce] ?Exs[1!lI][ce]{2}at.+[S5]andst[ceo]d[ce]/is", $s)) return true;
		else if(preg_match("/[S5]andsted.+C[1Il!|]adonia[ce] ?Exsi[ce]{2}at.+/is", $s)) return true;
		else if(preg_match("/[S5]ands.?t[ceo]d[ce].+[CG][1Il!|]adonia[ce] ?Exs[1!lI][ce]{2}at.+/is", $s)) return true;
		else if(preg_match("/.{0,3}dst[ceo]d[ce].+[CG][1Il!|]adonia[ce] ?Exs[1!lI][ce]{2}at.+/is", $s)) return true;
		else if(preg_match("/[S5].{2,3}dst[ceo]d[ce].+[CG][1Il!|]adonia[ce] ?Exs[1!lI][ce]{2}at.+/is", $s)) return true;
		else return false;
	}

	private function isCalicialesExsiccataeLabel($s) {
		if(preg_match("/Ca[1Il!|]{2}cia[1Il!|]es Exsi[ce]{2}at./is", $s)) return true;
		else if(preg_match("/Tibell.*Ca[1Il!|]{2}cia[1Il!|]es /is", $s)) return true;
		else return false;
	}

	private function isLichensOfEasternNorthAmericaLabel($s) {
		if(preg_match("/(?:[1Il!|]{2}|U)[CG]HEN[S5] [0O]F EA[S5]TERN N[0O]RTH AMER[1Il!|]CA /is", $s)) return true;
		else if(preg_match("/[S5] [0O]F EA[S5]TERN N[0O]RTH AMER[1Il!|]CA EX[S5][1Il!|][CG]{2}AT./is", $s)) return true;
		else return false;
	}

	private function doLichensOfEasternNorthAmericaLabel($s) {
		$s = trim(preg_replace
		(
			array(
				"/\\s?(?:[1Il!|]{2}|U)[CG]HEN[S5] [0O]F EA[S5]TERN N[0O]RTH AMER[1Il!|]CA /i",
				"/.?[1Il!|][S5]TR[1Il!|]BUTED From THE A[CG]ADEMY OF NATURAL [S5][CG][1Il!|]EN[CG]E[S5] [0O]F PH[1Il!|]LADELPH[1Il!|]A (?:\(PH\))?/i"
			),
			array(
				"",
				""
			),
			$s
		));
		return $this->doLendemerLichenHerbariumLabel($s, "106");
	}

	private function doCladoniaExsiccataeSandstedeLabel($s) {
		$s = trim(preg_replace
		(
			array(
				"/[S5]ands.?t[ce]d[ce][.,] [CG][1Il!|]adonia[ce] exsiccata[ce]./i",
				"/\\nC[1Il!|]\\. ([A-Za-z])/i",
				"/\\n(\\d{3,4}) C[1Il!|][.,_]? ([A-Za-z])/i"
			),
			array(
				"",
				"\nCladonia \${1}",
				"\n\${1} Cladonia \${2}"
			),
			$s
		));
		return $this->doGenericLabel($s, "322");
	}

	private function doCalicialesExsiccataeLabel($s) {
		$s = trim(preg_replace
		(
			array(
				"/(?:(?:L. )?T[1Il!|]BE[1Il!|]{2}:? )?[CG]AL[1Il!|]C[1Il!|]ALE[S5] EX[S5][1Il!|][CG]{2}ATAE ?/i"
			),
			array(
				""
			),
			$s
		));//echo "\nline 4941, s:\n".$s."\n";
		return $this->doGenericLabel($s, "336");
	}

	private function doReliquiaeTuckermanianaeLabel($s) {
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$state_province = "";
		$recordedBy = '';
		$country = '';
		$county = '';
		$location = '';
		$exsNum = '';
		$substrate = '';
		$scientificName = '';
		$identified_by = '';
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$associatedTaxa = '';
		$associatedCollectors = '';
		$taxonRemarks = '';
		$otherCatalogNumbers = '';
		$date_identified = array();
		$event_date = array();
		$s = trim(preg_replace
		(
			array(
				"/(?:D[1!lI]str[1!lI]buted.by.the)?.?Far[1!lI][0o](?:w|vv) Herbar[1!lI]um.[0o]f.Harvard.Un[1!lI]vers[1!lI]ty/i",
				"/Re[1!lI]{2}qu[1!lI]ae.?[TF]U[CG]KERM ?AN[il1!|] ?ANA.?/i",
				"/Un[1!lI]vers[1!lI]ty.{2,4}M[1!lI]ch[1!lI]gan.?Fungus.?C[0o][1!lI]{2}ect[1!lI][0o]n/i",
				"/HERBAR[1!lI]UM.?[O0]F.?THE.?UN[1!lI]VERS[1!lI]TY.?[O0]F.?M[1!lI]CH[1!lI]GAN/i",
				"/NEW.?Y[O0]RK.?B[O0]TAN[1!lI][CG]AL.?[CG]ARDEN/i",
				"/\\nvar\\. /i",
				"/\\bSy.{1,2}\\. ?of /i",
				"/\\n.{0,2}Syn\\. of /i",
				"/\\n(\\d{1,3}) ([abc])\\. /i",
				"/\[OTA HI /",
				"/Un[1!lI]vers[1!lI]ty [O0]f Tennessee(?: \(TENN\))?/i",
				"/\\bEndoc.rpon /i",
				"/New. Hampshire\\b/i"
			),
			array("", "", "", "", "", " var. ", "Syn. of ", " Syn. of ", "\n\${1}\${2}. ", "", "", "Endocarpon ", "New Hampshire"),
			$s
		));
		//echo "\nline 2041, s: ".$s."\n";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
				if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
			}
		}
		$lines = explode("\n", $s);
		$foundSciName = false;
		foreach($lines as $line) {
			$line_copy = trim(str_replace(",", "", $line));
			$psn = $this->processSciName($line_copy);
			if($psn != null) {
				if(array_key_exists ('scientificName', $psn)) {
					$scientificName = $psn['scientificName'];
					$foundSciName = true;
				}
				if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
				if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
				if(array_key_exists ('taxonRemarks', $psn)) $taxonRemarks = $psn['taxonRemarks'];
				if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
				if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
				if(array_key_exists ('recordNumber', $psn)) {
					$trn = $psn['recordNumber'];
					if(strlen($trn) > 0) $exsNum = $trn;
				}
				if(array_key_exists ('substrate', $psn)) {
					$substrate = $psn['substrate'];
				}
				if($foundSciName) {
					//if it has matched the first scientific name (preceded by a exs number) then remove it and everything before it
					//otherwise just remove this line
					if(preg_match("/^\\d{1,3}.+/", $line)) $s = trim(substr($s, stripos($s, $line)+strlen($line)));
					else $s = trim(str_ireplace($line, "", $s));
					break;
				}
			}
		}
		if($foundSciName) {
			$lines = explode("\n", $s);
			$foundDeterminer = false;
			$lineBefore = "";
			foreach($lines as $line) {//echo "\nline 2091, line: ".$line."\n";
				$line = trim($line);
				$onPos = stripos($line, "On ");
				if($onPos !== FALSE && $onPos == 0) {
					$substrate = $line;
					$commaPos = stripos($substrate, ",");
					if($commaPos !== FALSE) {
						$location = trim(substr($substrate, $commaPos+1));
						$substrate = trim(substr($substrate, 0, $commaPos));
					}
				} else if(strlen($state_province) == 0) {
					$pos = stripos($line, "Coll. ");
					if($pos !== FALSE) $line = trim(substr($line, 0, $pos));
					if(preg_match("/(.+)(?:".$possibleMonths.")/", $line, $mats)) $line = trim($mats[1]);
					$pos = stripos($line, ",");
					if($pos !== FALSE) {
						$firstPart = trim(substr($line, 0, $pos));
						$lastPart = trim(substr($line, $pos+1));
						$pos2 = stripos($lastPart, ",");
						if($pos2 !== FALSE) {
							$firstPart .= ", ".trim(substr($lastPart, 0, $pos2));
							$lastPart = trim(substr($lastPart, $pos2+1));//echo "\nline 2112, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\n";
						}
						$sp = $this->getStateOrProvince($lastPart);
						if(count($sp) > 0) {
							$state_province = $sp[0];
							$country = $sp[1];
							if(strlen($location) == 0) $location = $firstPart;
							else if(stripos($location, $firstPart) === FALSE) $location .= ", ".$firstPart;
						} else {
							$sp = $this->getStateOrProvince($firstPart);
							if(count($sp) > 0) {
								$state_province = $sp[0];
								$country = $sp[1];
								if(strlen($location) == 0) $location = $lastPart;
								else if(stripos($location, $lastPart) === FALSE) $location .= ", ".$lastPart;
							}
						}
					} else if(strcasecmp(substr($line, 0, 4), "see ") != 0) {//to prevent a misreading of "Mo." in a literature reference as Missouri
						$sp = $this->getStateOrProvince($line);
						if(count($sp) > 0) {
							$state_province = $sp[0];
							$country = $sp[1];
							if($this->countPotentialLocalityWords($lineBefore) > 0) {
								if(strlen($location) == 0) $location = $lineBefore;
								else if(stripos($location, $lineBefore) === FALSE) $location .= ", ".$lineBefore;
							}
						}
					}
				}
				$lineBefore = $line;
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName(trim($scientificName, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => $country,
			'county' => $county,
			'exsNum' => $exsNum,
			'ometid' => "209",
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'eventDate' => $event_date,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'otherCatalogNumbers' => trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRemarks' => trim($taxonRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	private function doReliquiaeFarlowianaeLabel($s) {//Farlow labels have the sciName after the institution identifiers followed by substrate, if given
		//followed by the location, if given followed by the collector
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$state_province = '';
		$recordedBy = '';
		$country = '';
		$county = '';
		$location = '';
		$exsNum = '';
		$substrate = '';
		$scientificName = '';
		$identified_by = '';
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$associatedTaxa = '';
		$date_identified = array();
		$event_date = array();
		$s = trim(preg_replace
		(
			array(
				"/(?:D[1!lI]str[1!lI]buted.by.the)?.?Fa[rknm][1!lI][0o](?:w|vv) Herbar[1!lI]um.[0o].{2}Harvard.Un[1!lI]vers[1!lI]ty/i",
				"/Re[1!lI]{2}qu[1!lI]ae.?Far[1!lI][0o]w[1!lI]ana.*(?:Lichenes)?/i",
				"/t551. /",
				"/Un[1!lI]vers[1!lI]ty.{2,4}M[1!lI]ch[1!lI]gan.?Fungus.?C[0o][1!lI]{2}ect[1!lI][0o]n/i",
				"/HERBAR[1!lI]UM.?[O0]F.?THE.?UN[1!lI]VERS[1!lI]TY.?[O0]F.?M[1!lI]CH[1!lI]GAN/i",
				"/NEW.?Y[O0]RK.?B[O0]TAN[1!lI][CG].{3,6}[CG]ARDEN/is",
				"/\\nvar\\. /i",
				"/(\\d{3})[.,-_]\\n/",
				"/HERBAR[1!lI]UM[,.]?.?UN[1!lI]VERS[1!lI]TY.?[0o]..?[1!lI]{4}N[0o][1!lI][S5].?DEPARTMENT.?[0o]F.?B[0o]TANY/i",
				"/Un[1!lI]vers[1!lI]ty.?[0o]f.?[1!lI]{4}n[0o][1!lI]s/i"
			),
			array("", "", "651. ", "", "", "", " var. ", "\${1}. ", "", ""),
			$s
		));
		//echo "\nline 2028, s: ".$s."\n";
		$lines = explode("\n", $s);
		$foundSciName = false;
		foreach($lines as $line) {
			$line_copy = trim(str_replace(",", "", $line));
			$psn = $this->processSciName($line_copy);
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
					if(strlen($trn) > 0) $exsNum = $trn;
				}
				if(array_key_exists ('substrate', $psn)) {
					$substrate = $psn['substrate'];
				}
				if($foundSciName) {
					//if it has matched the first scientific name (preceded by a exs number) then remove it and everything before it
					//otherwise just remove this line
					if(preg_match("/^\\d{1,3}.+/", $line)) $s = trim(substr($s, stripos($s, $line)+strlen($line)));
					else $s = trim(str_ireplace($line, "", $s));
					break;
				}
			}
		}
		if($foundSciName) {
			$lines = explode("\n", $s);
			$foundDeterminer = false;
			foreach($lines as $line) {//echo "\nline 2287, line: ".$line."\n";
				$line = trim($line);
				while(preg_match("/(.*)(?:".$possibleMonths.")/", $line, $mats)) $line = trim($mats[1]);
				if(strlen($line) > 0) {
					$onPos = stripos($line, "On ");
					if($onPos !== FALSE && $onPos == 0) {
						$substrate = $line;
						$commaPos = stripos($substrate, ",");
						if($commaPos !== FALSE) {
							$temp = trim(substr($substrate, $commaPos+1));
							if($this->countPotentialLocalityWords($temp) > 0) {
								$location = $temp;
								$substrate = trim(substr($substrate, 0, $commaPos));
							}
						}
					} else if(strlen($state_province) == 0) {
						$words = explode(" ", $line);
						$wCount = count($words);
						if($wCount > 1) {
							$thisWord = trim($words[0], " :;,.");
							$sp = $this->getStateOrProvince($thisWord);
							if(count($sp) > 0) {
								$state_province = $sp[0];
								$country = $sp[1];
								if(stripos($line, $thisWord) !== FALSE) $line = trim(substr($line, stripos($line, $thisWord)+strlen($thisWord)), " :;,.");
							} else {
								$thisWord = trim($words[0]." ".$words[1], " :;,.");
								$sp = $this->getStateOrProvince($thisWord);
								if(count($sp) > 0) {
									$state_province = $sp[0];
									$country = $sp[1];
									if(stripos($line, $thisWord) !== FALSE) $line = trim(substr($line, stripos($line, $thisWord)+strlen($thisWord)), " :;,.");
								}
							}
						} else {
							$thisWord = trim($words[0]);
							$sp = $this->getStateOrProvince($thisWord);
							if(count($sp) > 0) {
								$state_province = $sp[0];
								$country = $sp[1];
								if(stripos($line, $thisWord) !== FALSE) $line = trim(substr($line, stripos($line, $thisWord)+strlen($thisWord)), " :;,.");
							}
						}
					}
					if(strlen($line) > 0) {//echo "\nline 2287, countPotentialLocalityWords(".$line."): ".$this->countPotentialLocalityWords($line)."\n";
						if($this->countPotentialLocalityWords($line) > 0) {
							if(strlen($location) == 0) $location = $line;
							else if(stripos($location, $line) === FALSE) $location .= " ".$line;
						}
					}
				}
			}
		}
		$ometid = "208";
		if($exsNum > 600) $ometid = "238";
		return array
		(
			'scientificName' => $this->formatSciName(trim($scientificName, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => $country,
			'county' => $county,
			'exsNum' => $exsNum,
			'ometid' => $ometid,
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'eventDate' => $event_date,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	private function doFarlowLabel($s) {//Farlow labels have the sciName after the institution identifiers followed by substrate, if given
		//followed by the location, if given followed by the collector
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$state_province = '';
		$recordedBy = '';
		$country = '';
		$county = '';
		$location = '';
		$substrate = '';
		$scientificName = '';
		$identified_by = '';
		$infraspecificEpithet = '';
		$taxonRank = '';
		$verbatimAttributes = '';
		$associatedTaxa = '';
		$recordNumber = '';
		$date_identified = array();
		$event_date = array();
		$s = substr($s, stripos($s, "Farlow Herbarium")+17);
		$s = preg_replace(array("/Schohar. /i", "/var\\.([a-z]{3,})/"), array("Schoharie ", "var. \${1}"), $s);
		$eolPos = strpos($s, "\n");
		$s = substr($s, $eolPos+1);//go to next line
		$lines = explode("\n", $s);
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
			$county = trim($countyMatches[1]);
			$country = trim($countyMatches[2]);
			$m4 = trim($countyMatches[4]);
			$sp = $this->getStateOrProvince($m4);
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
		}
		$foundSciName = false;
		foreach($lines as $line) {
			if(!(stripos($line, "Herbarium") !== FALSE || stripos($line, "Lichens") !== FALSE ||
				stripos($line, "New York") !== FALSE || stripos($line, "Botanical") !== FALSE ||
				stripos($line, "Garden") !== FALSE || stripos($line, "dupl.") !== FALSE) && strlen($line) > 6)
			{
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
						$foundSciName = true;
						$s = trim(str_ireplace($scientificName, "", $s));
					}
					if(array_key_exists ('infraspecificEpithet', $psn)) {
						$infraspecificEpithet = $psn['infraspecificEpithet'];
						$s = trim(str_ireplace($infraspecificEpithet, "", $s));
					}
					if(array_key_exists ('taxonRank', $psn)) {
						$taxonRank = $psn['taxonRank'];
						$s = trim(str_ireplace($taxonRank, "", $s));
					}
					if(array_key_exists ('verbatimAttributes', $psn)) {
						$verbatimAttributes = $psn['verbatimAttributes'];
						$s = trim(str_ireplace($verbatimAttributes, "", $s));
					}
					if(array_key_exists ('associatedTaxa', $psn)) {
						$associatedTaxa = $psn['associatedTaxa'];
						$s = trim(str_ireplace($associatedTaxa, "", $s));
					}
					if(array_key_exists ('recordNumber', $psn)) {
						$trn = $psn['recordNumber'];
						if(strlen($trn) > 0) $recordNumber = $trn;
						$s = trim(str_ireplace($recordNumber, "", $s));
					}
					if(array_key_exists ('substrate', $psn)) {
						$substrate = $psn['substrate'];
						$s = trim(str_ireplace($substrate, "", $s));
					}
					if($foundSciName) break;
				}
			}
		}
		if($foundSciName) {
			$lines = explode("\n", $s);
			$foundDeterminer = false;
			foreach($lines as $line) {//echo "\nline 5124, line: ".$line."\n";
				$line = trim($line);
				$onPos = stripos($line, "On ");
				if($onPos !== FALSE && $onPos == 0) {
					$substrate = $line;
					$commaPos = stripos($substrate, ",");
					if($commaPos !== FALSE) {
						$location = trim(substr($substrate, $commaPos+1));
						$substrate = trim(substr($substrate, 0, $commaPos));
					}
				} else if($this->countPotentialLocalityWords($line) > 0 && !$this->isMostlyGarbage($line, 0.51)) $location = trim($line);
				else if ($this->containsVerbatimAttribute($line) && !$this->isMostlyGarbage($line, 0.51)) {
					if(strlen($verbatimAttributes) == 0) $verbatimAttributes = trim($line);
					else if(stripos($line, $verbatimAttributes) === FALSE) $verbatimAttributes .= ", ".trim($line);
				}
				if(strlen($location) > 0 && strlen($substrate) > 0) break;
			}
			$lPat = "/((?s).*)\\b(?:[il1!|]eg|".$possibleMonths.")\\b/i";
			if(strlen($location) > 0) {
				if(strlen($county) > 0) if(preg_match("/(.*)".preg_quote($county, "/")."/i", $location, $mats)) $location = trim($mats[1]);
				while(preg_match($lPat, $location, $ms)) $location = trim($ms[1], " .");
				if(strlen($location) > 0) {
					while(preg_match("/(.*)\\b(?:Co[il1!|]{2}|Det)[,.;:]/i", $location, $mats)) $location = trim($mats[1]);
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
			'county' => $county,
			'recordNumber' => $recordNumber,
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'eventDate' => $event_date,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
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
				"/\\sby\\s\?\n/",
				"/S(?:[,.]|tephen)? ?(?:S\\.? ?)& S(?:[,.]|andra)? ?(?:L[,.]? ?)?Talbot/i"
			);
			$replacement =
			array
			(
				"\n\${1}",
				" by ",
				"Stephen Talbot & Sandra Talbot"
			);
			$s = trim(preg_replace($pattern, $replacement, $s, -1));
			//echo "\nline 5571, s:\n".$s."\n";
			return $this->doGenericLabel($s, "", array('country' => "U.S.A.", 'stateProvince' => "Alaska"));
		}
	}

	private function doAlcanExpeditionLabel($s) {
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
							if(preg_match("/(.*?)".$possibleNumbers."{1,3}+\\s?�/i", $line)) {
								$pat = "/(.*?)".$possibleNumbers."{1,3}+\\s?�/i";
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
				"/C�ll\\./",
				"/Co\\wl\*/",
				"/COU\\./",
				"/[fl1]{5,}/",
				"/1.2X1\\sCO\\s/",
				"/I'-lEXICO/",
				"/M.{1,3}XICO/",
				"/£a£OCOo/",
				"/t-iETvICn/",
				"/»/i",
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
				"/�Coll\\./",
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
		if(preg_match("/(.*)(?:M|I1|IX|K|'i)E[XK]IC[0O].?[,.]?\\s([a-zA-Z015\"\']+)[.,:*#\"\'�-]{0,2}+\\s(.*+)/is", $s, $mexMats)) {
			//$i=0;
			//foreach($mexMats as $mexMat) echo "mexMats[".$i++."]: ".$mexMat."\n";
			$temp = trim($mexMats[1], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
			$temp2 = trim($mexMats[2], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
			$temp3 = trim($mexMats[3], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
			if(preg_match("/(.*)(?:M|I1|IX|K|'i)E[XK]IC[0o].?[,.]?\\s(\\w+)[.,:*#\"\'�-]{0,2}+\\s(.*)/is", $temp, $mexMats2)) {
				$temp = trim($mexMats2[1], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
				$temp2 = trim($mexMats2[2], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
				$temp3 = trim($mexMats2[3], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-")." ".
				trim($mexMats[2], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-")." ".
				trim($mexMats[3], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
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
			$country = trim($countryMats[1], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
			$location = trim($countryMats[2], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
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
			$temp = trim($countryMats[1], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
			$pos = strrpos($temp, "\n");
			if($pos !== FALSE) {
				$temp = trim(substr($temp, $pos), " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
				if($this->isPossibleSciName($temp)) $scientificName = $temp;
			}
			$county = trim($countryMats[2], " \t\n\r\0\x0B:;!\"\'\\~@�#$%^&*_-");
			$country = "Jamaica";
			$aArray = $this->getStateFromCounty($county, null, $country);
			if($aArray != null) $state_province = $aArray[0];
			$location = trim($countryMats[3], " \t\n\r\0\x0B:;,.!\"\'\\~@�#$%^&*_-");
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

	private function doLichensOfFloridaLabel($s) {
		//echo "\nDoing LichensOfFloridaLabel\n";
		$pattern =
			array
			(
				"/Coun[1!|lI]y/i",
				"/Fiarris/",
				"/�H'W/",
				"/number 3\^x31/",
				"/NUMBER\\s(\\d)[ ,.](\\d{3})\\s/i",
				"/CO.{2}TY: /i",
				"/(?:Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?)\\s\\d{2}[,.]\\s\\d{4}/i",
				"/(?:(?:Herbar[1!|lI]um\\s[o0]f\\s)?The\\s)?+New\\sYork\\sBotan[1!|lI]cal\\sGarden(?:\\s\(NY\))?+/i",
				"/DUKE\\sUN[1!|lI]VERS[1!|lI]TY\\sHERBAR[1!|lI]UM/i",
				"/Bry[o0]phyt[ec]\\sand\\sL[1!|lI][ec]h[ec]n\\sH[ec]rbar[1!|lI]um[.,]?+\\s?+/i",
				"/Un[1!|lI]vers[1!|lI]ty\\s[o0]f.?F[1!|lI][o0]r[1!|lI]da[.,]\\sGa[1!|lI]nesv[1!|lI]{3}e[.,]\\sF[1!|lI][o0]r[1!|lI]da[.,]\\sU[.,]?S[.,]?A[.,]?\\s326[1!|lI]{2}/is",
				"/F[1!|lI][o0]r[1!|lI]da\\sMuseum\\s[o0]f\\sNatural\\sH[1!|lI]st[o0]ry/i",
				"/Un[1!|lI]vers[1!|lI]ty\\s[o0]f.?Fl[o0]r[1!|lI]da(?:\\sHerbarium)?+/i",
				//"/L[1!|lI][ce]h[ce]ns\\s[o0]f\\sFl[o0]r[1!|lI]da(?:[.,]\\sU[.,]?S[.,]?A[.,]?)?/i",
				//"/.*(?:L[1Il!|][CE]H[CE]N|Cr[ypqg]{2}t[O0Q][ypqg]am|P[1Il!|]ant)'?[S5]\\s[O0Q]F\\sFL[O0Q]R[1Il!|]DA?(?:[.,]\\sU[.,]?S[.,]?A[.,]?)?/is",
				"/,,/i",
				"/\\.\\./i",
				"/Bi£i£OS£ora/"
			);
		$replacement =
			array
			(
				"County",
				"Harris",
				"�11'W",
				"number 3131",
				"Number \${1}\${2} ",
				"County: ",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
			//	"",
			//	"",
				",",
				".",
				"Bactrospora"
			);
		$fields = array();
		$fields['country'] = "U.S.A.";
		$fields['stateProvince'] = "Florida";
		return $this->doGenericLabel(trim(preg_replace($pattern, $replacement, $s, -1)), null, $fields);
	}

	private function doLendemerLichenHerbariumLabel($s, $ometid="") {
		//echo "\nDid LendemerLichenHerbariumLabel\n";
		$lendemerPat = "/.*L[1Il!|]ch[ce]n.?H[ce]rbar[1Il!|]um.?[O0Q]f.?Jam[ce]s.?[CG]\\.?.?L[ce]nd[ce]m[ce]r.*?(?:\\r\\n|\\n\\r|\\n)(.*)/is";
		if(preg_match($lendemerPat, $s, $mat)) $s = trim($mat[1]);
		$pattern =
		array
		(
			"/C[O0]\\wN\\wY/s",
			"/DUPL[1Il!|][CG]ATE/",
			"/(?:Ex\\s)?H[ce]rbar[1Il!|]um.?[O0Q]f.?th[ec].?A[ce]ad[ce]my.?of.?Natura[1Il!|].?Sci[ce]nc[ce]s.?[o0]f.?Phi[1Il!|]ad[ce][1Il!|]ph[1Il!|]a.?\(PH\)?\\n/i",
			"/THE.?NEW.?Y[O0Q]RK.?B[O0Q]TAN[1Il!|][CG]AL.?[CG]ARDEN/is",
			"/\\setcalong\\b/",
			"/\\n1\\s35\\sft\\./",
			"/-\\sLTM\\s(\\d{2})/",
			"/\\sLai\\.\\s(\\d{2})c (\\d{2})�/",
			"/Fr.?ax.?mus\\s/i",
			"/\\selev\\.\\s(\\d{1,2})\\s(\\d{1,2})\\sft\\./i",
			"/\/¡moc\\.\\sspp/"
		);
		$replacements =
		array
		(
			"COUNTY",
			"",
			"",
			"",
			" etc., along",
			"135 ft.",
			"- UTM \${1}",
			" Lat. \${1}�\${2}'",
			"Fraxinus ",
			" elev. \${1}\${2} ft.",
			"Assoc. spp"
		);
		$s = preg_replace($pattern, $replacements, $s);
		//echo "\nline 4497, s: ".$s."\n\n";
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = array();
		$county = "";
		$country = "";
		$substrate = '';
		$exsNumber = "";
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
		foreach($lines as $line) {//echo "\nline 4479, line: ".$line."\n";
			$line = trim($line);
			if(preg_match("/LICHENS\\sOF\\s.+/i", $line)) continue;
			if($lineIndex < 3 && strlen($line) > 3) {
				if(!preg_match("/.*(?:D.{1,2}P[1Il!|]{1,2}[CE]AT[CE]|HB\\.?\\s[1Il!|][CE]ND[CE]M[CE]R).*/i", $line)) {
					//echo "\nline 4482, Maybe Sci Name: ".$line."\n";
					$psn = $this->processSciName($line);
					if($psn != null) {//foreach($psn as $k => $v) echo "\nline 6945, ".$k.": ".$v."\n";
						if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('recordNumber', $psn)) {
							if(strlen($ometid) > 0) $exsNumber = $psn['recordNumber'];
							else $recordNumber = $psn['recordNumber'];
						}
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							//if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
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
					if(array_key_exists ('recordNumber', $psn)) {
						if(strlen($ometid) > 0) $exsNumber = $psn['recordNumber'];
						else $recordNumber = $psn['recordNumber'];
					}
					if(array_key_exists ('substrate', $psn)) {
						$substrate = $psn['substrate'];
						//if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
					}
					break;
				}
			}
			$lineIndex++;
		}
		if($ometid && strlen($exsNumber) == 0 && preg_match("/Fascicle (?:.+): Number (\\d{1,3})/is", $s, $mats)) $exsNumber = trim($mats[1]);
		$countyMatches = $this->findCounty($s, "");
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 6986, countyMatches[".$i++."] = ".$countyMatche."\n";
			$firstPart = trim($countyMatches[0]);
			$location = ltrim($countyMatches[3], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			if($this->countPotentialLocalityWords($location) == 0 && $this->countPotentialLocalityWords($firstPart) > 0) $location = $firstPart;
			$county = trim($countyMatches[1]);
			$country = trim($countyMatches[2]);
			$sp = $this->getStateOrProvince(trim($countyMatches[4]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
		} else {
			if(preg_match("/.+\\bCANADA.\\s(.+)\\.\\s?:\\s(.+)/is", $s, $pMatches)) {
				$country = "Canada";
				$state_province = trim($pMatches[1]);
				$location = trim($pMatches[2]);
			}
		}
		if(strlen($location) > 0) {
			if(preg_match("/(.*)C[o0][li!|]{1,2}[ec]{1,2}t[li!|][o0]n\\sdata[,.:;]?\\s(.*)/is", $location, $locMats)) {
				$location = trim($locMats[1]);
			}
			//echo "\nline 4533, location: ".$location."\n";
			if(preg_match("/(.*?)A.?ss[o0]c[,.]?\\s?\*?s[ps]p[,.]?(.*)/is", $location, $locMats2)) {//$i=0;foreach($locMats2 as $locMat) echo "\nline 4534, locMats2[".$i++."] = ".$locMat."\n";
				$location = trim($locMats2[1], " :.-");
				if(strlen($associatedTaxa) == 0) $associatedTaxa = trim($locMats2[2]);
			} else if(preg_match("/(.*?)'\\d{1,3}W\\s-\\s(\\w{3,}\\sspp[,.]?.*)/is", $location, $locMats2)) {
				$location = trim($locMats2[1], " :.-");
				if(strlen($associatedTaxa) == 0) $associatedTaxa = trim($locMats2[2]);
			}
			if(strlen($associatedTaxa) > 0) {
				$thallusPos = strpos($associatedTaxa, "Thallus");
				if($thallusPos !== FALSE) {
					if(strlen($verbatimAttributes) == 0) {
						$verbatimAttributes = ltrim(trim(substr($associatedTaxa, $thallusPos)), " :.-");
						$associatedTaxa = ltrim(trim(substr($associatedTaxa, 0, $thallusPos)), " :.-");
					}
				}
			} else {
				$thallusPos = strpos($location, "Thallus");
				if($thallusPos !== FALSE) {
					if(strlen($verbatimAttributes) == 0) {
						$verbatimAttributes = ltrim(trim(substr($location, $thallusPos)), " :.-");
						$location = ltrim(trim(substr($location, 0, $thallusPos)), " :.-");
					}
				}
			}
			$elevArr = $this->getElevation($location);
			$temp = $elevArr[0];
			if(strlen($temp) > 0) {
				$elevation = $elevArr[1];
				$ePos = stripos($location, $elevation);
				if($ePos !== FALSE && strlen($habitat) == 0) $habitat = trim(substr($location, $ePos+strlen($elevation)), " :.-");
				$location = $temp;
				if(preg_match("/(.*)-?\\s?[ec][li!|][ec]v\\.?$/i", $location, $mats)) $location = trim($mats[1]);
			}
			if(preg_match("/(.*?)-\\s?UTM.*?\\s?-\\s?(.*)/is", $location, $locMats2)) {
				$location = trim($locMats2[1]);
				$habitat = ltrim(trim($locMats2[2]), " :.-");
			} else if(preg_match("/(.*?)-\\s?UTM\\b/is", $location, $locMats2)) $location = trim($locMats2[1]);
			else if(preg_match("/(.*?)\\sUTM\\b/is", $location, $locMats2)) $location = trim($locMats2[1]);
			if(preg_match("/(.*?)\\bLat.*?[EWV]\\s?-(.*)/is", $habitat, $locMats2)) $habitat = ltrim(trim($locMats2[2]), " :.-");
			else if(preg_match("/(.*?)\\bLat.*?[EW]\\s?-?(.*)/is", $habitat, $locMats2)) $habitat = ltrim(trim($locMats2[2]), " :.-");
			else if(preg_match("/(.*?)\\bLat.*?[EWV]\\s?-(.*)/is", $location, $locMats2)) {
				$location = trim($locMats2[1]);
				$habitat = ltrim(trim($locMats2[2]), " :.-");
			} else if(preg_match("/(.*?)\\bLat.*?[EW]\\s?-?(.*)/is", $location, $locMats2)) {
				$location = trim($locMats2[1]);
				$habitat = ltrim(trim($locMats2[2]), " :.-");
			}
			if(preg_match("/(.*?) - (On\\s.*)/is", $habitat, $locMats2)) {
				//$i=0;
				//foreach($locMats2 as $locMat) echo "\nlocMats2[".$i++."] = ".$locMat."\n";
				$habitat = trim($locMats2[1]);
				$substrate = trim($locMats2[2]);
			}
			$onPos = stripos($location, "on ");
			if($onPos !== FALSE && $onPos == 0) {
				$commaPos = stripos($location, ", in ");
				if($commaPos === FALSE) $commaPos = strpos($location, ",");
				if($commaPos !== FALSE) {
					$substrate = substr($location, 0, $commaPos);
					$location = trim(substr($location, $commaPos+1));
				}
			}
		}
		//echo "\nline 7071, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
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
		if(strlen($location) > 0) {
			if(preg_match("/(.*)\\bLat\/Long/is", $location, $mat)) $location = trim($mat[1]);
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?�/is";
			while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
			if(preg_match("/(.*)-?\\s?Long\\.?$/i", $location, $mat)) $location = trim($mat[1]);
			$firstPart = "";
			$secondPart = "";
			if(preg_match("/(.{3,})[.,]\\s(property\\sof\\s.+)/i", $location, $mats)) {
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
			} else if(preg_match("/(.{3,})[.,]\\s(along\\s.+)/i", $location, $mats)) {
				$firstPart = trim($mats[1]);
				$secondPart = trim($mats[2]);
			}
			if(strlen($firstPart) > 0) {
				if(preg_match("/(.{3,})[.,]\\s(along\\s.+)/i", $firstPart, $mats2)) {
					$firstPart = trim($mats2[1]);
					$secondPart = trim($mats2[2]).", ".$secondPart;
				}
				if(1.2*$this->countPotentialHabitatWords($firstPart) > 0 && $this->countPotentialLocalityWords($secondPart) > 0) {
					if(strlen($habitat) > 0) $habitat = $firstPart.", ".$habitat;
					else $habitat = $firstPart;
					$location = $secondPart;
				}
			}
			$pos = strpos($location, ",");
			if($pos !== FALSE) {
				while($pos !== FALSE) {
					$temp = trim(substr($location, 0, $pos));
					//echo "\nline 4700, location: ".$location."\nhabitat: ".$habitat."\ntemp: ".$temp."\nthis->countPotentialHabitatWords(temp): ".$this->countPotentialHabitatWords($temp)."\nthis->countPotentialLocalityWords(temp): ".$this->countPotentialLocalityWords($temp)."\n";
					if(1.2*$this->countPotentialHabitatWords($temp) > $this->countPotentialLocalityWords($temp)) {
						if(strlen($habitat) > 0) $habitat .= ", ".trim($temp);
						else $habitat = trim($temp);
						$location = substr($location, $pos+1);
						$pos = strpos($location, ",");
					} else break;
				}
			}
		}
		if(strlen($substrate) > 0) {
			if(preg_match("/(.{3,})\\s((?:along|on)\\s.+)/i", $substrate, $mats)) {
				$temp = trim($mats[2]);
				if(1.2*$this->countPotentialLocalityWords($temp) > $this->countPotentialHabitatWords($temp)) $location = $temp.", ".$location;
				else {
					if(strlen($habitat) > 0) $habitat = $temp.", ".$habitat;
					else $habitat = $temp;
				}
				$substrate = trim($mats[1]);
			}
		}
		if(strlen($elevation) == 0) {
			$elevationArray = $this->getElevation($s);
			if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		}
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
			'verbatimAttributes' => trim(ltrim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $verbatimAttributes), "-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_"),
			'associatedTaxa' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $associatedTaxa), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"),
			'recordNumber' => $recordNumber,
			'ometid' => $ometid,
			'exsNumber' => $exsNumber,
			'county' => $county,
			'country' => $country,
			'locality' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $location), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"),
			'substrate' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $substrate), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim(preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", $habitat), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"),
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
				"/Col[!Il1|].{1,2}\\s([A-Z])/i",
				"/Eve.sma./",
				"/Eve.{2}man/",
				"/So\\sEversman/i",
				"/Cal.oplaca/",
				"/Ga[!Il1|]{2}a.in/",
				"/\\sTha[!Il1|]{2}us\\s/",
				"/Monaana/",
				"/No\\.\\./",
				"/Co\"�,\\s/",
				"/ora calc.?area/i",
				"/Ever\\ss.{2,3}an/",
				"/Alectoria\\s(Bryoria\\sfuscescens)/",
				"/> ontana/",
				"/.?[!Il1|]ectoria\\sglabra/i",
				"/([A-Za-z]{3,}+)0\\s/",
				"/mosses-/i",
				"/«1/",
				"/.{2,4}RMELIA WYOMINGICA\\s/i",
				"/\\sParmelia\\s/i",
				"/Xanthor[!Il1|]a\\s/i",
				"/Mectoria\) Br/",
				"/Xanth[o0]ria.{1,2}e[!Il1|]egans/i",
				"/Xanth[o0]ria.{1,2}p[o0]lycarpa/i",
				"/S<£ of/",
				"/([A-Za-z]) Co , /",
				"/I&2E, Sec/"
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
				"Gallatin",
				"\nThallus ",
				"Montana",
				"No. ",
				"Co. ",
				"ora calcarea",
				"Eversman",
				"Bryoria fuscescens",
				"Montana",
				"Alectoria glabra",
				"\${1}. ",
				"mosses. ",
				"",
				"Parmelia wyomingica ",
				"\nParmelia ",
				"Xanthoria ",
				"Br",
				"Xanthoria elegans",
				"Xanthoria polycarpa",
				"SE of",
				"\${1} Co., ",
				"R42E, Sec"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));//return $this->doGenericLabel($s);
		$scientificName = "";
		$substrate = "";
		$habitat = "";
		$infraspecificEpithet = "";
		$taxonRank = "";
		$verbatimAttributes = "";
		if(preg_match("/.*B[O0Q].?[SZ]EMAN.?(?:,\\sM[O0Q]NTANA)(.*)/is", $s, $mats)) $s = trim($mats[1]);
		if(preg_match("/.*UN[I1!|]VER[S5][I1!|]TY.{1,9}B[O0Q].?[SZ]EMAN.?(?:,\\sM[O0Q]NTANA)?(.*)/is", $s, $mats)) $s = trim($mats[1]);
		if(preg_match("/.*HE[RH]BAR[I1!|]U. [O0Q]F .[O0Q]NTANA [S5]TATE UN[I1!|]VER[S5][I1!|]TY(.*)/is", $s, $mats)) $s = trim($mats[1]);
		if(preg_match("/.*M\\s[O0QC]F\\sM[O0Q]NTANA\\s?STATE(.*)/is", $s, $mats)) $s = trim($mats[1]);//echo "\nline 8276, s:\n".$s."\n";

		//return $this->doGenericLabel($s);
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		$associatedTaxa = "";
		$associatedCollectors = "";
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
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
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
			$state_province = trim($countyMatches[4]);
			$country = trim($countyMatches[2]);
			$firstPart = trim($countyMatches[0]);
			//$location = ltrim(rtrim($countyMatches[3], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$county = trim($countyMatches[1]);
			//echo "\nline 6561, firstPart: ".$firstPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
		}
		$foundSciName = false;
		$lookingForAssociatedTaxa = false;
		if(strlen($firstPart) > 0) $s = $firstPart;
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 7940, line: ".$line."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(preg_match("/(.*)[NH][o0Q]\\.?\\s((?:[A-Z]\\d-)?[SZl|I!1-9]".$possibleNumbers."{0,2}+)[.,]?+(.*)/i", $line, $mats)) {
				$recordNumber = $this->replaceMistakenNumbers(trim($mats[2]));
				$pos = stripos($s, $line);
				if($pos !== FALSE) $s = trim(substr($s, $pos+strlen($line)));
				$line = trim(trim($mats[1])." ".trim($mats[3]));
			}
			if(!$this->isMostlyGarbage($line, 0.60) && strlen($line) > 1) {
				if(!$foundSciName && strlen($line) > 6) {
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
						$lookingForAssociatedTaxa = true;
						continue;
					}
				} else if(!$lookingForAssociatedTaxa && strlen($line) > 6) {
					if(preg_match("/^with\\s(.+)\\.\\s(.{3,}+)/i", $line, $mats)) {
						$temp = trim($mats[1]);
						$temp2 = trim($mats[2]);
						$psn = $this->processSciName($temp);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) {
								$pAssociatedTaxa = $psn['scientificName'];
								if(strlen($associatedTaxa) == 0) $associatedTaxa = $pAssociatedTaxa;
								else if(stripos($associatedTaxa, $pAssociatedTaxa) === FALSE) $associatedTaxa .= " ".$pAssociatedTaxa;
							}
							if(array_key_exists ('substrate', $psn)) {
								$pSubstrate = $psn['substrate'];
								if(strlen($substrate) == 0) $substrate = $pSubstrate;
								else if(stripos($substrate, $pSubstrate) === FALSE) $substrate .= " ".$pSubstrate;
							}
							$s = preg_replace("/".preg_quote($temp, '/')."/", "", $s);
							if($this->countPotentialHabitatWords($temp2) > 0) {
								$habitat = $temp2;
								$s = preg_replace("/".preg_quote($temp2, '/')."/", "", $s);
								continue;
							} else $line = $temp2;
						}
					} else if(preg_match("/^w[ij]th\\s(.+)\\s(in\\s.+)/i", $line, $mats)) {
						$temp = trim($mats[1]);
						$temp2 = trim($mats[2]);
						$psn = $this->processSciName($temp);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) {
								$pAssociatedTaxa = $psn['scientificName'];
								if(strlen($associatedTaxa) == 0) $associatedTaxa = $pAssociatedTaxa;
								else if(stripos($associatedTaxa, $pAssociatedTaxa) === FALSE) $associatedTaxa .= " ".$pAssociatedTaxa;
							}
							if(array_key_exists ('substrate', $psn)) {
								$pSubstrate = $psn['substrate'];
								if(strlen($substrate) == 0) $substrate = $pSubstrate;
								else if(stripos($substrate, $pSubstrate) === FALSE) $substrate .= " ".$pSubstrate;
							}
							$s = preg_replace("/".preg_quote($temp, '/')."/", "", $s);
							if($this->countPotentialHabitatWords($temp2) > 0) {
								$habitat = $temp2;
								$s = preg_replace("/".preg_quote($temp2, '/')."/", "", $s);
								continue;
							} else $line = $temp2;
						}
					} else if(preg_match("/^w[ij]th\\s(.+)/i", $line, $mats)) {
						$temp = trim($mats[1]);
						$psn = $this->processSciName($temp);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) {
								$pAssociatedTaxa = $psn['scientificName'];
								if(strlen($associatedTaxa) == 0) $associatedTaxa = $pAssociatedTaxa;
								else if(stripos($associatedTaxa, $pAssociatedTaxa) === FALSE) $associatedTaxa .= " ".$pAssociatedTaxa;
							}
							if(array_key_exists ('substrate', $psn)) {
								$pSubstrate = $psn['substrate'];
								if(strlen($substrate) == 0) $substrate = $pSubstrate;
								else if(stripos($substrate, $pSubstrate) === FALSE) $substrate .= " ".$pSubstrate;
							}
							$s = preg_replace("/".preg_quote($temp, '/')."/", "", $s);
							continue;
						}
					}
					$lookingForAssociatedTaxa = false;
				}
				if(strlen($line) > 3) {
					if(preg_match("/.*(Thallus\\s.{3,}+)/i", $line, $mats)) {
						$temp = trim($mats[1]);
						$temp2 = "";
						if(preg_match("/(.+[a-zA-Z]{2,})\\.\\s(.+)/", $temp, $mats2)) {
							$temp2 = trim($mats2[1]);
							$temp3 = trim($mats2[2]);
							if($this->countPotentialHabitatWords($temp3) > 0) {
								$habitat = $temp3;
								$s = preg_replace("/".preg_quote($temp3, '/')."/", "", $s);
							}
						} else $temp2 = $line;
						if(strlen($verbatimAttributes) == 0) $verbatimAttributes = $temp2;
						else if(stripos($verbatimAttributes, $temp2) === FALSE) $verbatimAttributes .= " ".$temp2;
						$s = preg_replace("/".preg_quote($temp2, '/')."/", "", $s);
					} else if($this->containsVerbatimAttribute($line)) {//echo "\nline 7043, line: ".$line."\n";
						$temp = "";
						if(preg_match("/(.*[a-zA-Z]{2,})\\.\\s(.+)/", $line, $mats)) {
							$temp = trim($mats[1]);
							$temp2 = trim($mats[2]);
							if($this->containsVerbatimAttribute($temp)) {
								if(!$this->containsVerbatimAttribute($temp2)) {
									if($this->countPotentialHabitatWords($temp2) > 0) {
										if(strlen($habitat) == 0) $habitat = $temp2;
										else if(stripos($habitat, $temp2) === FALSE) $habitat .= " ".$temp2;
										$s = preg_replace("/".preg_quote($temp2, '/')."/", "", $s);
									}
								} else $temp = $line;
							} else if($this->countPotentialHabitatWords($temp) > 0) {
								if(strlen($habitat) == 0) $habitat = $temp;
								else if(stripos($habitat, $temp) === FALSE) $habitat .= " ".$temp;
								$s = preg_replace("/".preg_quote($temp, '/')."/", "", $s);
								$temp = $temp2;
							} else $temp = $temp2;
						} else $temp = $line;
						if(strlen($verbatimAttributes) == 0) $verbatimAttributes = $temp;
						else if(stripos($verbatimAttributes, $temp) === FALSE) $verbatimAttributes .= " ".$temp;
						$s = preg_replace("/".preg_quote($temp, '/')."/", "", $s);
					} else if(/*strlen($habitat) == 0 && */strlen($line) > 6 &&
						$this->countPotentialHabitatWords($line) > 0 &&
						!preg_match("/.?[NH][o0Q]\\.?\\s/", $line) &&
						!preg_match("/C[o0Q][!Il1|]{2}:\\s\\d/", $line)) {
						if($this->countPotentialLocalityWords($line) > 0) {
							$firstPart = "";
							$lastPart = "";
							$line = trim($line, ".");
							if(preg_match("/(.+ [A-Za-z]{4,}); (.+)/", $line, $mats)) {
								$firstPart = trim($mats[1]);
								$lastPart = trim($mats[2]);
							} else if(preg_match("/(.+ [A-Za-z]{5,})\\. (.+)/", $line, $mats)) {
								$firstPart = trim($mats[1]);
								$lastPart = trim($mats[2]);
							} else if(preg_match("/(.+ [A-Za-z]{4,}), (.+)/", $line, $mats)) {
								$firstPart = trim($mats[1]);
								$lastPart = trim($mats[2]);
							} else continue;
							if(strlen($firstPart) > 0 && strlen($lastPart) > 0) {
								if($this->countPotentialLocalityWords($firstPart) == 0) $line = $firstPart;
								else if($this->countPotentialLocalityWords($lastPart) == 0) $line = $lastPart;
								else if(preg_match("/(.+ [A-Za-z]{5,})\\. .+/", $firstPart, $mats2)) {
									$startOfFirstPart = trim($mats2[1]);
									if($this->countPotentialLocalityWords($startOfFirstPart) == 0) $line = $startOfFirstPart;
									else if(preg_match("/.+ [A-Za-z]{5,}\\. (.+)/", $lastPart, $mats2)) {
										$endOfLastPart = trim($mats2[1]);
										if($this->countPotentialLocalityWords($endOfLastPart) == 0) $line = $endOfLastPart;
									}
								}
							} else continue;
						}
						$habitat = $this->mergeFields($habitat, $line);
						//$s = preg_replace("/".preg_quote($line, '/')."/", "", $s);
						$s = trim(str_replace($line, "", $s));
					}
				}
			}
		}
		$location = $s;
		if(strlen($location) > 0) {//echo "\nline 7076, location: ".$location."\n";
			$location = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			if(strlen($habitat) > 0) {
				$pos = stripos($location, $habitat);
				if($pos !== FALSE && $pos < 9) $location = trim(substr($location, $pos+strlen($habitat)));
			}
			if(strlen($elevation) > 0) {
				if(preg_match("/(.*)\\b".$elevation.".*/i", $location, $matches3)) $location = trim($matches3[1]);
			}
			if(preg_match("/(.*?)".$possibleNumbers."{1,2}+[- ]?(?:".$possibleMonths.")[- ]?".$possibleNumbers."{4}.*/i", $location, $matches3)) $location = trim($matches3[1]);
			if(preg_match("/(.*?)C[o0Q][!Il1|]{2}:\\s.*/i", $location, $matches3)) $location = trim($matches3[1]);
		}
		if(strlen($location) > 0 && strlen($substrate) == 0) {
			if(preg_match("/^((?:(?:Quite )?Common\\s|Found\\s)?on\\s.*?)\\s(?:and\\s)?+((?:over|along|at)\\s.*)/i", $location, $mats)) {
				$substrate = trim($mats[1]);
				$location = trim($mats[2]);
			}
		}
		if(strlen($habitat) > 0 && strlen($substrate) == 0) {
			if(preg_match("/^((?:(?:Quite )?Common\\s|Found\\s)?on\\s.*?)\\s(?:and\\s)?+(in\\s.*)/i", $habitat, $mats)) {
				$substrate = trim($mats[1]);
				$habitat = trim($mats[2]);
			}
		}
		if(strlen($habitat) > 0) {//echo "\nline 7108, habitat: ".$habitat."\n";
			$pos = strpos($habitat, " along ");
			if($pos !== FALSE) {
				$firstPart = trim(substr($habitat, 0, $pos));
				$lastPart = trim(substr($habitat, $pos+1));
				if(1.2*$this->countPotentialHabitatWords($firstPart) > $this->countPotentialLocalityWords($firstPart) &&
					$this->countPotentialLocalityWords($lastPart) > 1.2*$this->countPotentialHabitatWords($lastPart)) {
					$habitat = $firstPart;
					if(strlen($location) == 0) $location = $lastPart;
					else $location = $lastPart." ".$location;
				}
			}
			$pos = strpos($habitat, ".");
			if($pos !== FALSE) {
				$temp2 = $habitat;
				$index = 0;
				$totPos = 0;
				while($pos !== FALSE) {
					//$totPos += $pos+1;
					$temp = substr($temp2, 0, $pos);
					//echo "\nline 8437, temp: ".$temp."\ntemp2: ".$temp2."\nhabitat: ".trim(substr($habitat, 0, $totPos))."\nlocation: ".trim(substr($habitat, $totPos+1))."\n";
					if(1.2*$this->countPotentialHabitatWords($temp) > $this->countPotentialLocalityWords($temp)) {
						$temp2 = substr($temp2, $pos+1);
						$totPos += $pos+$index++;
						$pos = strpos($temp2, ".");
					} else if($this->countPotentialLocalityWords($temp) > 0) {
						if(strlen($location) == 0) $location = trim(ltrim(substr($habitat, $totPos+$index), " ,."));
						else $location = trim(ltrim(substr($habitat, $totPos+$index), " ,.")).". ".ltrim($location, " ,.");
						$habitat = trim(substr($habitat, 0, $totPos));
						break;
					} else break;
				}
			}
		}
		if(strlen($habitat) > 0) {//echo "\nline 8464, habitat: ".$habitat."\n";
			$pos = strpos($habitat, ",");
			if($pos !== FALSE) {
				$temp2 = $habitat;
				$index = 0;
				$totPos = 0;
				while($pos !== FALSE) {
					//$totPos += $pos+1;
					$temp = substr($temp2, 0, $pos);
					//echo "\nline 8456, temp: ".$temp."\ntemp2: ".$temp2."\nhabitat: ".trim(substr($habitat, 0, $totPos))."\nlocation: ".trim(substr($habitat, $totPos+1))."\ncountPotentialHabitatWords(temp): ".$this->countPotentialHabitatWords($temp)."\ncountPotentialLocalityWords(temp): ".$this->countPotentialLocalityWords($temp)."\n";
					if(1.2*$this->countPotentialHabitatWords($temp) > $this->countPotentialLocalityWords($temp)) {
						$temp2 = substr($temp2, $pos+1);
						$totPos += $pos+$index++;
						$pos = strpos($temp2, ",");
					} else if($this->countPotentialLocalityWords($temp) > 0) {
						if(strlen($location) == 0) $location = trim(ltrim(substr($habitat, $totPos+$index), " ,."));
						else $location = trim(ltrim(substr($habitat, $totPos+$index), " ,.")).". ".ltrim($location, " ,.");
						$habitat = trim(substr($habitat, 0, $totPos));
						break;
					} else break;
				}
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
			'verbatimCoordinates' => $verbatimCoordinates,
			'associatedCollectors' => $associatedCollectors
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
			"/Sharon\\.Eversman/",
			"/ACA.{1,2}OSPORA /i",
			"/subp\\. /i"
		);
		$replacement =
		array
		(
			"lapponica",
			"Eversman",
			"Eversman",
			"Eversman",
			"Sharon Eversman",
			"Sharon Eversman",
			"Acarospora ",
			"subsp. "
		);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\ns:\n".$s."\n";
		$recordNumber = '';
		$recordedBy = '';
		$recordedById = '';
		$otherCatalogNumbers = '';
		$associatedCollectors = '';
		$identifiedBy = '';
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = $collectorInfo['collectorName'];
			if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
			if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
			if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
			if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
		}//echo "\nline 6079, s:\n".$s."\nrecordedBy:\n".$recordedBy."\nrecordNumber:\n".$recordNumber."\nidentifiedBy:\n".$identifiedBy."\n";
		$lfPat = "/.*M[O0Q]NTANA\\s?STATE\\s?UN[1Il!|]VERSITY\\s?HERBAR[1Il!|]UM?+(.*)/is";
		if(preg_match($lfPat, $s, $mats)) $s = trim($mats[1]);
		$state_province = "";
		$dateIdentified = array();
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
			$county = trim($countyMatches[1]);
			$s = trim($countyMatches[0]);
			$state_province = trim($countyMatches[4]);
		}
		$lookingForHabitat = true;
		$foundSciName = false;
		$lines = explode("\n", $s);
		$lineIndex = 0;
		foreach($lines as $line) {//echo "\nline 9183, line: ".$line."\n";
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
					if(preg_match("/^(?:(?:Found|Common|Loose|Sparse)\\s)?on\\s.{3,}/i", $line)) {
						$substrate = $line;
						continue;
					}
				}
				if($lookingForHabitat) {
					$hCount = $this->countPotentialHabitatWords($line);
					$lCount = $this->countPotentialLocalityWords($line);
					if($hCount > 0 && $lCount > 0) {
						$firstPart = "";
						$lastPart = "";
						if(preg_match("/(.+ [A-Za-z]{4,})\\. (.+)/", trim($line, "."), $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
							if(preg_match("/(.+ [A-Za-z]{4,})\\. (.{3,})/", trim($firstPart, "."), $mats2)) {
								$firstPart = trim($mats2[1]);
								$lastPart = trim($mats2[2]).". ".$lastPart;
							}
						} else if(preg_match("/(.+ [A-Za-z]{3,}), (.{3,})/", trim($line, ","), $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
							if(preg_match("/(.+ [A-Za-z]{3,}), (.{3,})/", trim($firstPart, ","), $mats2)) {
								$firstPart = trim($mats2[1]);
								$lastPart = trim($mats2[2]).", ".$lastPart;
							}
						}
						if(strlen($firstPart) > 0 && strlen($lastPart) > 0) {
							$hCount1 = $this->countPotentialHabitatWords($firstPart);
							$lCount1 = $this->countPotentialLocalityWords($firstPart);
							$hCount2 = $this->countPotentialHabitatWords($lastPart);
							$lCount2 = $this->countPotentialLocalityWords($lastPart);
							//echo "\nline 9231, firstPart: ".$firstPart."\nlastPart: ".$lastPart."\nhCount1: ".$hCount1."\nlCount1: ".$lCount1."\nhCount2: ".$hCount2."\nlCount2: ".$lCount2."\n";
							if($lCount2 > $lCount1 && $hCount1 > 0) {
								if(strlen($location) == 0) $location = $lastPart;
								else if(stripos($location, $lastPart) === FALSE) $location = trim($location, ",").", ".$lastPart;
								$habitat = $firstPart;
								$lookingForHabitat = false;
								continue;
							} else if($lCount2 > 0) {
								$location = $line;
								continue;
							} else {
								$habitat = $line;
								$lookingForHabitat = false;
								continue;
							}
						}
					} else if($hCount > 0) {
						$habitat = $line;
						$lookingForHabitat = false;
						continue;
					} else if($lCount > 0) {
						if(strlen($location) == 0) $location = $line;
						else if(stripos($location, $line) === FALSE) $location = trim($location, ",").", ".$line;
						continue;
					}
				}
				if($this->countPotentialLocalityWords($line) > 0) {
					if(strlen($location) == 0) $location = $line;
					else {
						if(stripos($location, $line) === FALSE) $location = trim($location, ",").", ".$line;
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
			'dateIdentified' => $dateIdentified,
			'associatedCollectors' => $associatedCollectors
		);
	}

	private function doBorealiAmericaniLabel($s) {//echo "\nDoing BorealiAmericaniLabel\n";
		$pattern =
			array
			(
				"/\\bCo11' �\/ara E' Cummings\\./",
				"/\\b\(.?o[lI1!|]{2}[._*-]\\s/",
				"/\\bCo[lI1!|]{2}[._*-]\\.?\\s/",
				"/^Co\\s[lI1!|]{2}\\.\\s/",
				"/\/73\\s\(_6\$y\\s/",
				"/42\\s1\)\\..\\s/",
				"/loiva\\./",
				"/Co\\.,\\sCa.\\.,/i",
				"/The\\ss\*\\sA\\.\\sW[li!1|]{4}ams/",
				"/ab[o0]ut([1-9])/i",
				"/\\s={0,2}\\s?N[.,*]?\\s?A[.,*]?\\s?L/",
				"/\\nColt\\.\\s/",
				"/\\n(\\d{2,3}\\.) ?\" /",
				"/ (?<! (?:p\\.|\\d,) )(\\d{2,3}\\. [A-Za-z])/",
				"/Biatora mi.{2,3}iaria, \(Fr.\) Tuck./",
				"/(\\d)\\.\" ([A-Za-z])/",
				"/\\bBæomyces /i",
				"/\\bLeeanora /i"
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
				"\n=N.A.L",
				"\nColl. ",
				"\n\${1} ",
				"\n\${1}",
				"Biatora milliaria (Fr.) Tuck.",
				"\${1}. \${2}",
				"Baeomyces ",
				"Lecanora "
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 7556, s:\n".$s."\n";
		$exsnumber = "";
		$taxonRank = "";
		$infraspecificEpithet = "";
		$scientificName = "";
		$substrate = "";
		$habitat = "";
		$state_province = "";
		$otherCatalogNumbers = "";
		$associatedCollectors = "";
		$verbatimAttributes = "";
		$verbatimElevation = "";
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $verbatimElevation = $elevationArray[1];
		$s = trim(preg_replace("/".preg_quote($verbatimElevation, '/')."/i", "", $s));
		$associatedTaxa = "";
		$recordedBy = "";
		$verbatimEventDate = "";
		$county = "";
		$country = "";
		$location = "";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
			$county = trim($countyMatches[1]);
			$state_province = trim($countyMatches[4]);
			$country = trim($countyMatches[2]);
			$firstPart = trim($countyMatches[0]);
			if(preg_match("/(.+),$/", $firstPart, $mats)) $location = $mats[1];
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
			if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
		}//echo "\nline 7590, location: ".$location.", scientificName: ".$scientificName."\n";
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 6112, line: ".$line."\n";
			if(preg_match("/LICHENES BO.?REA.{1,3}LI-AMERICANI(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*LICHENES BO.EA[1Il!|]{2}-AME.[1Il!|]CA.{1,3}(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/L[1Il!|]CHENES\\sBORE ?A[1Il!|].{2,4}AM.{1,7}[1Il!|]/i", $line)) $line = "";
			else if(preg_match("/L[1Il!|]CHENES\\sB ?ORE ?A[1Il!|].{2,4}AM.{1,7}[1Il!|]/i", $line)) $line = "";
			else if(preg_match("/L[1Il!|]CHENES\\sB ?ORE ?A.{1,2}[1Il!|].{1,3}AM.{1,7}[1Il!|]/i", $line)) $line = "";
			else if(preg_match("/L[1Il!|]CHEN ES\\sBO ?RE ?A.{1,2}[1Il!|].{1,3}AM.{1,7}[1Il!|]/i", $line)) $line = "";
			else if(preg_match("/.*L[1Il!|]C[1Il!|].{2,4}N{2,3} B[O0Q][RB]E.{1,2}A[1Il!|]{2,3}-AM.{2,4}CAN.+/is", $line)) $line = "";
			else if(preg_match("/.*[BES5][ ._#-]{1,2}B[O0Q]r[CE]a[1Il!|]{2}[ ._#-]{1,2}Am[CE].[1Il!|][CE]an[1Il!|]?(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*L[1Il!|]CHENES\\sSBO.EA[1Il!|]{2}.{2,4}AM.{1,7}[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*[BS][ ._#-]{1,2}B[o0]rea[1Il!|]{2}[ ._#-]{1,2}Amer[1Il!|]can[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*[1Il!|]CHEN[BES5]{2}[ ._#-]{1,2}B[O0Q].{2}a[1Il!|]{2}[ ._#-]{1,2}Amer[1Il!|]can[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*[1Il!|]{3,5}ams\\sand\\sA\\.\\s[HB]\\.\\sSeym[o0].*/i", $line)) continue;
			else if(preg_match("/.*LICHENES BO.{1,3}E.?A[1Il!|].{1,3}-AM.{2,4}CAN..{1,3}(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*L[1Il!|].{2,4}NES B[O0Q].{2,3}A[1Il!|]{2,3}-AM.{2,4}CAN[1Il!|](.+)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*L[1Il!|][CE]H[CE]N.?[BES5]{2} B.?[O0Q].{2,3}A[1Il!|]{2,3}-AM.{2,4}CAN.{2}(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*[1Il!|][CE]H[CE]N.?[BES5]{2} B.?[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA[1Il!|].[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*L[1Il!|].{2,7} B.?[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA[1Il!|]N[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*L[1Il!|][CE]H[CE].?N[BES5]{2}  B[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA.N.{1,2}(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*L[1Il!|][CE]H[CE].{2,3}[BES5]{2}  B[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CA.?N.{1,2}(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*[BES5]{2} B[O0Q].{2,3}A[1Il!|]{2,3}-AMER[1Il!|]CAN.{1,2}(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*N[BES5]{2} B[O0Q]REA[1Il!|] [1Il!|]-AMER[1Il!|]C.AN.{1,2}(.*)/i", $line, $mats)) $line = trim($mats[1]);
			if(preg_match("/.*(?:\\spublish|Second|edition|prepare|\\sdistribut|\\sUnivers|\\sDUKE\\sU|erbarium|Coll[,.]\\s|\\sDecades).*/i", $line)) continue;
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(!$foundSciName) {
				if(strcmp(substr($line, 0, 1), "=") == 0) $line = trim(substr($line, 1));
				if(strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60) && !preg_match("/.{0,3}?Co[1Il!|]{2}.?\\.?\\s/i", $line) && !preg_match("/.{0,3}?C?oll\\.?\\s/i", $line)) {
					$temp = "";
					$mats2_original = "";
					if(preg_match("/^([SZlU|I!1-9&]|[\]\[OQSZlU|I!0-9&]{2,3} ?[a-fA-F]?)[.,_*]? ([A-Za-z]{1,2}(?:[,.]|[A-Za-z]{3,}).*)/", $line, $mats)) {
						$temp = $this->replaceMistakenNumbers(trim($mats[1]));
						$mats2_original = trim($mats[2]);
					} else if(preg_match("/^[^a-zA-Z0-9]{0,3}?([SZlU|I!1-9&]|[\]\[OQSZlU|I!0-9&]{2,3} ?[a-fA-F]?)[.,_*]? ([A-Za-z]{1,2}(?:[,.]|[A-Za-z]{3,}) .*)/", $line, $mats)) {
						$temp = $this->replaceMistakenNumbers(trim($mats[1]));
						$mats2_original = trim($mats[2]);
					} else if(preg_match("/^([SZlU|I!1-9&]|[\]\[OQSZlU|I!0-9&]{2,3} ?[a-fA-F]?)[.,_*] (.+)/", $line, $mats)) {
						$temp = $this->replaceMistakenNumbers(trim($mats[1]));
						$mats2_original = trim($mats[2]);
					}
					if(strlen($temp) > 0) {
						$exsnumber = $temp;
						$mats2 = trim(str_replace(",", "", $mats2_original));
						$psn = $this->processSciName($mats2);
						if($psn != null) {
							$line = trim(ltrim(preg_replace("/".preg_quote($exsnumber, '/')."/i", "", $line), ".,"));
							if(array_key_exists('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								if(preg_match("/".preg_quote($scientificName, '/')."/i", $line)) $line = trim(preg_replace("/".preg_quote($scientificName, '/')."/i", "", $line));
								else $line = trim(preg_replace("/".preg_quote($mats2_original, '/')."/i", "", $line));
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
								$verbatimAttributes = $psn['verbatimAttributes'];
								$line = trim(preg_replace("/".preg_quote($verbatimAttributes, '/')."/i", "", $line));
							}
							if(array_key_exists('associatedTaxa', $psn)) {
								$associatedTaxa = $psn['associatedTaxa'];
								$line = trim(preg_replace("/".preg_quote($associatedTaxa, '/')."/i", "", $line));
							}
							//if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
							if(array_key_exists('substrate', $psn)) {
								$substrate = $psn['substrate'];
								$line = trim(preg_replace("/".preg_quote($substrate, '/')."/i", "", $line));
							}
							$foundSciName = true;
						}
					} else if(strcasecmp(substr($line, 0, 7), "Lichen ") != 0) {
						$line_copy = trim(str_replace(",", "", $line));
						$psn = $this->processSciName($line_copy);
						if($psn != null) {
							if(array_key_exists('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								if(preg_match("/".preg_quote($scientificName, '/')."/i", $line)) $line = trim(preg_replace("/".preg_quote($scientificName, '/')."/i", "", $line));
								else if(preg_match("/".preg_quote($scientificName, '/')."/i", $line_copy)) $line = trim(preg_replace("/".preg_quote($scientificName, '/')."/i", "", $line_copy));
								else $line = "";
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
								$verbatimAttributes = $psn['verbatimAttributes'];
								$line = trim(preg_replace("/".preg_quote($verbatimAttributes, '/')."/i", "", $line));
							}
							if(array_key_exists('associatedTaxa', $psn)) {
								$associatedTaxa = $psn['associatedTaxa'];
								$line = trim(preg_replace("/".preg_quote($associatedTaxa, '/')."/i", "", $line));
							}
							//if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
							if(array_key_exists('substrate', $psn)) {
								$substrate = $psn['substrate'];
								$line = trim(preg_replace("/".preg_quote($substrate, '/')."/i", "", $line));
							}
							$foundSciName = true;
						}
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
								$location = $potentialCityOrCounty;
							}
						}
					}
				}
				if(strlen($line) > 6) {
					if(strcasecmp(substr($line, 0, 5), "var. ") == 0) {
						$taxonRank = "var.";
						$infraspecificEpithet = trim(substr($line, 5));
						$onPos = stripos($infraspecificEpithet, " on ");
						if($onPos !== FALSE) {
							if(strlen($substrate) == 0) $substrate = trim(substr($infraspecificEpithet, $onPos));
							$infraspecificEpithet = trim(substr($infraspecificEpithet, 0, $onPos));
						}
					} else if(preg_match("/^.?(Tha[1Il!|]{1,2}us\\s.{3,}+)/i", $line, $mats)) $verbatimAttributes = trim($mats[1]);
					else {
						if(preg_match("/(.*)((?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant)|Found|Loose)? On .+)/i", $line, $mats)) {
							$firstPart = trim($mats[1]);
							$lastPart = trim($mats[2]);
							if($this->countPotentialLocalityWords($lastPart) == 0) $substrate = $this->mergeFields($substrate, $lastPart);
							else {
								if($this->countPotentialLocalityWords($firstPart) > 0) $location = $this->mergeFields($location, $line);
								else {
									$location = $this->mergeFields($location, $lastPart);
									if($this->countPotentialHabitatWords($firstPart) > 0) $habitat = $this->mergeFields($habitat, $firstPart);
								}
							}
						}
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
			} else if($this->countPotentialLocalityWords($line) > 0) $location = $this->mergeFields($location, $line);
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => ucfirst(trim($state_province, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'otherCatalogNumbers' => ucfirst(trim($otherCatalogNumbers, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'country' => ucfirst(trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'locality' => ucfirst(trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'habitat' => ucfirst(trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimElevation' => trim($verbatimElevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => $taxonRank,
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "88",
			'exsnumber' => $exsnumber
		);
	}

	private function doLichenesExsiccatiLabel($s) {
		//echo "\nDid LichenesExsiccatiLabel\n";
		if($this->isWeberLichenesExsiccatiLabel($s)) return $this->doWeberLichenesExsiccatiLabel($s);
		else if($this->isMerrilLichenesExsiccatiLabel($s)) return $this->doMerrilLichenesExsiccatiLabel($s);
		else if($this->isASULichenesExsiccatiLabel($s)) return $this->doASULichenesExsiccatiLabel($s);
		else if($this->isHasseLichenesExsiccatiLabel($s)) return $this->doHasseLichenesExsiccatiLabel($s);
		else return $this->doGenericLabel($s);
	}

	private function doLichensAndMossesOfYellowstoneLabel($s) {
		//echo "\nDoing LichensAndMossesOfYellowstoneLabel\n";
		$pattern =
			array
			(
				"/bnaron tversman/i",
				"/\"�/i"
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
		$associatedCollectors = '';
		$identifiedBy = '';
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = str_replace(" . ", ", ", $collectorInfo['collectorName']);
			if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
			if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
			if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
			if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
		}//echo "\nline 5925, collectorName: ".$collectorName.", collectorNum: ".$collectorNum."\n";
		$state_province = "";
		$identifiedBy = '';
		$dateIdentified = array();
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
			if(strcasecmp($identifiedBy, "se") == 0) $identifiedBy = "Sharon Eversman";
			$dateIdentified = $identifier[1];
		}
		$foundSciName = false;
		$foundLocation = false;
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 8486, line: ".$line."\n";
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
			if(preg_match("/(.*)\\sL[O0Q]cati[O0Q]n[:;]?(.{6,})/i", $line, $mats)) {
				$location = trim($mats[2]);
				$firstPart = trim($mats[1]);
				$lCount = $this->countPotentialLocalityWords($firstPart);
				$hCount = $this->countPotentialHabitatWords($firstPart);
				if($hCount > $lCount) $habitat = $firstPart;
				else if($lCount > 0) $location = $firstPart." ".$location;
				continue;
			} else if(preg_match("/^L[O0Q]cati[O0Q]n[:;]?(.{6,})/i", $line, $mats)) {
				$habitat = trim($mats[1]);
				continue;
			}
			if(preg_match("/\\sHab[1Il!|]tat[:;]?(.{6,})/i", $line, $mats)) {
				$habitat = trim($mats[1]);
				continue;
			} else if(preg_match("/^Hab[1Il!|]tat[:;]?(.{6,})/i", $line, $mats)) {
				if(preg_match("/^(?:(?:Found|Common|Loose)\\s)?on\\s.{3,}/i", $line)) $substrate = $line;
				else $habitat = trim($mats[1]);
				continue;
			}
			if(strlen($substrate) == 0) {
				if(preg_match("/^(?:(?:Found|Common|Loose)\\s)?on\\s.{3,}/i", $line)) {
					$substrate = $line;
					continue;
				}
			}
			if(preg_match("/^(?:No.+:\\s)?([A-Z]{2,3}\\d{2,6})/", $line, $mats) && strlen($recordNumber) == 0) {
				$recordNumber = trim($mats[1]);
				continue;
			}
			$dPat = "/(.*)((?:[0OQ]?+[!lI2ZS1-9]|[1!Il][OQ!lI2Z12])\/(?:[0OQ]?+[!lI2ZS1-9]|[1!Il2Z][OQ!lI2ZS0-9]|3[0OQ!|Il1])\/(?:[0OQ][!lI2ZS1-9]|[OQ!lI2ZS0-9]{2}+))/";
			if(preg_match($dPat, $line, $mats)) {
				$verbatimEventDate = $this->convertSlashedDates($this->replaceMistakenNumbers($mats[2]));
				$line = trim($mats[1]);
			}
			$lCount = $this->countPotentialLocalityWords($line);
			$hCount = $this->countPotentialHabitatWords($line);
			if($lCount > $hCount && $lCount > 0) {
				if(strlen($location) == 0) $location = $line;
				else if(!$foundLocation) {
					$location .= " ".$line;
					$foundLocation = true;
				}
			} else if(strlen($habitat) == 0 && $hCount > 0) $habitat = $line;
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
			'$associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
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

	private function doDecadesOfNorthAmericanLichensLabel($s) {
		$pattern =
			array
			(
				//"/.*[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichen.*/i",
				"/.*Prepared by Clara E. Cummings and A. B. [S5]eymour.*/i",
				"/.*Cla[br]a E. Cummings, T(?:hos)?\\. A W[1Il!|,]{3,5}ams and A B. [S5]eymour.*/i",
				"/.*WALTER K[1Il!|]ENER MEM[O0Q]R[1Il!|]AL(?:\\sL[1Il!|]CHEN C[O0Q]LLECT[1Il!|][O0Q]N)?.*/i",
				"/.*Un[1Il!|]vers[1Il!|]ty [O0Q]f Nebraska - L[1Il!|]n[ce][O0Q][1Il!|]n.*/i",
				"/.*Lichen Herbarium WA[1Il!|]TER K[1Il!|]ENER.*/i",
				"/Biatorahypno.hila/i"
			);
		$replacement =
			array
			(
				//"",
				"",
				"",
				"",
				"",
				"",
				"Biatora hypnophila"
			);
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 8964, s:\n".$s."\n";
		$ometid = "";
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
		$associatedCollectors = "";
		$workingSection = "";
		$verbatimEventDate = "";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
				if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
				if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
				if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
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
		//$possibleNumbers = "[0-9]";
		$firstPart = "";
		$countyMatches = $this->findCounty($s);
		if($countyMatches != null) {
			$state_province = trim($countyMatches[4]);
			$country = trim($countyMatches[2]);
			$firstPart = trim($countyMatches[0]);
			$lastPart = trim($countyMatches[3]);
			if(preg_match("/^".$state_province."(.*)/is", $lastPart, $mats)) $lastPart = trim(ltrim($mats[1], ".,:;"));
			if(strlen($firstPart) > 6) {
				$fLines = array_reverse(explode("\n", $firstPart));
				if(preg_match("/(.+,)$/", $fLines[0], $mats)) $location = trim($mats[1]);
			}
			if($this->countPotentialLocalityWords($lastPart) > 0 || $this->countPotentialHabitatWords($lastPart) > 0) $workingSection = ltrim(rtrim($lastPart, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$county = trim($countyMatches[1]);
			//echo "\nline 9021, firstPart: ".$firstPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
		}
		if(strlen($workingSection) == 0) $workingSection = $s;
		$foundSciName = false;
		if(strlen($firstPart) > 0) {
			if(preg_match("/^.*[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichens[.,]?(.*)/is", $firstPart, $mats)) {
				$lines = explode("\n", trim($mats[1]));
				$s = trim(preg_replace("/[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichens[.,]?/is", "", $s));
				$workingSection = trim(preg_replace("/[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichens[.,]?/is", "", $workingSection));
				foreach($lines as $line) {//echo "\nline 9032, line: ".$line."\n";
					$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					if(preg_match("/^\\W{0,2}?([SZl|I!1-9]".$possibleNumbers."{0,2}+ ?[A-Fa-f]?)[.,]? (.*)/i", $line, $mats)) {
						$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
						if(strcmp($exsnumber, $recordNumber) == 0) $recordNumber = "";
						$temp = trim($mats[2]);
						if(strlen($temp) > 6 && strpos($temp, " ") !== FALSE) {
							$psn = $this->processSciName($temp);
							if($psn != null) {
								if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
								$foundSciName = true;
								break;
							}
						}
					}
				}
			}
		}
		if(!$foundSciName) {
			if(preg_match("/.*[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichens[.,]?(.*)/is", $s, $mats)) {
				$lines = explode("\n", trim($mats[1]));
				$s = trim(preg_replace("/[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichens[.,]?/is", "", $s));
				$workingSection = trim(preg_replace("/[DO][ec]{2}ad[ec][S5]\\s[O0Q]f\\sN[O0Q]rth\\sAm[ec]r[1Il!|]can\\sLichens[.,]?/is", "", $workingSection));
				foreach($lines as $line) {//echo "\nline 9058, line: ".$line."\nexsnumber: ".$exsnumber."\n";
					$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
					if(preg_match("/^\\W{0,2}?([SZl|I!1-9]".$possibleNumbers."{0,2}+ ?[A-Fa-f]?)[.,]? (.*)/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 9060, mats[".$i++."] = ".$mat."\n";
						$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
						if(strcmp($exsnumber, $recordNumber) == 0) $recordNumber = "";
						$temp = trim($mats[2]);
						if(strlen($temp) > 6 && strpos($temp, " ") !== FALSE) {
							$psn = $this->processSciName($temp);
							if($psn != null) {
								if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
								if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
								if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
								if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
								if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
								if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
								$foundSciName = true;
								break;
							}
						}
					}
				}
			}
		}//echo "\nline 9089, scientificName: ".$scientificName."\nexsnumber: ".$exsnumber."\n";
		if(!$foundSciName) {
			$lines = explode("\n", $s);
			foreach($lines as $line) {//echo "\nline 9092, line: ".$line."\nexsnumber: ".$exsnumber."\n";
				$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				if(preg_match("/^\\W{0,2}?([SZl|I!1-9]".$possibleNumbers."{0,2}+ ?[A-Fa-f]?)[.,]? (.*)/i", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 9094, mats[".$i++."] = ".$mat."\n";
					$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
					if(strcmp($exsnumber, $recordNumber) == 0) $recordNumber = "";
					$temp = trim($mats[2]);
					if(strlen($temp) > 6 && strpos($temp, " ") !== FALSE) {
						$psn = $this->processSciName($temp);
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
							$foundSciName = true;
							break;
						}
					}
				}
			}
		}//echo "\nline 9122, scientificName: ".$scientificName."\nexsnumber: ".$exsnumber."\n";
		if(!$foundSciName) {
			$lines = explode("\n", $s);
			foreach($lines as $line) {//echo "\nline 9125, line: ".$line."\n";
				$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				if(strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
					$psn = $this->processSciName($line);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
						if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
						if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
						if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
						if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
						if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
						break;
					}
				}
			}
		}//echo "\nline 9149, scientificName: ".$scientificName."\nexsnumber: ".$exsnumber."\n";
		if(strlen($workingSection) > 0) {//echo "\nline 9203, workingSection: ".$workingSection."\nlocation: ".$location."\n";
			if(strlen($scientificName) > 0) $workingSection = trim(preg_replace("/".preg_quote($scientificName, '/')."/i", "", $workingSection));
			if(strlen($infraspecificEpithet) > 0) $workingSection = trim(preg_replace("/".preg_quote($infraspecificEpithet, '/')."/i", "", $workingSection));
			if(strlen($taxonRank) > 0) $workingSection = trim(preg_replace("/".preg_quote($taxonRank, '/')."/i", "", $workingSection));
			if(strlen($verbatimAttributes) > 0) $workingSection = trim(preg_replace("/".preg_quote($verbatimAttributes, '/')."/i", "", $workingSection));
			if(strlen($associatedTaxa) > 0) $workingSection = trim(preg_replace("/".preg_quote($associatedTaxa, '/')."/i", "", $workingSection));
			if(strlen($substrate) > 0) $workingSection = trim(preg_replace("/".preg_quote($substrate, '/')."/i", "", $workingSection));
			$lines = explode("\n", $workingSection);
			foreach($lines as $line) {//echo "\nline 9205, line: ".$line."\n";
				if(strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
					if(strlen($state_province) > 1) {
						if(preg_match("/(.+), ".$state_province."(.*)/i", $line, $mats)) {
							$location = $this->mergeFields($location, trim($mats[1]));
							$line = trim($mats[2]);
						}
					} else if(preg_match("/(.+), (.+[A-Za-z]{3,})[.,](.*)/i", $line, $mats)) {
						$temp = trim($mats[2]);
						if($this->isUSState($temp)) {
							$state_province = $temp;
							$location = $this->mergeFields($location, trim($mats[1]));
							$country = "U.S.A.";
							$line = trim($mats[3]);
						}
					} else if(preg_match("/(.+), (.+)$/i", $line, $mats)) {
						$temp = trim($mats[2]);
						if($this->isUSState($temp)) {
							$state_province = $temp;
							$location = $this->mergeFields($location, trim($mats[1]));
							$country = "U.S.A.";
							$line = "";
						}
					}
					if(preg_match("/^(On .+)$/i", $line, $mats)) {
						if($this->countPotentialLocalityWords($line) == 0) {
							$substrate = $this->mergeFields($substrate, $line);
							$line = "";
						} else if(preg_match("/^(On .+[A-Za-z]{3,})[;:.,](.*)/i", $line, $mats2)) {
							$temp = trim($mats2[1]);
							if($this->countPotentialLocalityWords($temp) == 0) {
								$substrate = $this->mergeFields($substrate, $temp);
								$line = trim($mats2[2]);
							}
						}
					}
					if($this->countPotentialLocalityWords($line) > 0) {
						$localityAnalysis = $this->analyzeLocalityLine($line);
						if(array_key_exists('location', $localityAnalysis)) $location = $this->mergeFields($location, $localityAnalysis['location']);
						if(array_key_exists('habitat', $localityAnalysis)) $habitat = $this->mergeFields($habitat, $localityAnalysis['habitat']);
						if(array_key_exists('substrate', $localityAnalysis)) $substrate = $this->mergeFields($substrate, $localityAnalysis['substrate']);
					} else if($this->countPotentialHabitatWords($line) > 0) $habitat = $this->mergeFields($habitat, $line);
					else if($this->containsVerbatimAttribute($line) > 0) $verbatimAttributes = $this->mergeFields($verbatimAttributes, $line);
				}
			}
			if(stripos($location, $scientificName) !== FALSE) $location = trim(str_ireplace($scientificName, "", $location));
			$location = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			if(preg_match("/(.*?)\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?�(.*)/is", $location, $matches)) {
				$location = trim($matches[1]);
				$rest = trim($matches[2]);
				if(preg_match("/(.*?)\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?�(.*)/is", $location, $matches)) {
					$location = trim($matches[1]);
					$rest = trim($matches[2])." ".$rest;
				}
				if(preg_match("/.*?\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?�?\\s?(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,3}+)?'\\s?(?:".$possibleNumbers."{1,3}\"?\\s?)?)?W(?:\\.|est)?\\sLong(?:\\.|itude)?(.*+)/is", $rest, $matches2)) {
					$habitat = trim(ltrim($matches2[1], " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"));
				} else if(preg_match("/.*?\\b".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{2,7}+)?\\s?�?\\s?(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,3}+)?'\\s?(?:".$possibleNumbers."{1,3}\"?\\s?)?)?W(?:\\.|est)?\\sLong(?:\\.|itude)?(.*+)/is", $location, $matches2)) {
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
		if(strlen($habitat) > 0 && strlen($substrate) == 0) {
			if(preg_match("/^(on\\s.*?)\\s(?:and\\s)?+((?:over|along|at)\\s.*)/i", $habitat, $mats)) {
				$substrate = trim($mats[1]);
				$habitat = trim($mats[2]);
			} else if(preg_match("/^(on\\s.*)/i", $habitat, $mats)) {
				$substrate = trim($mats[1]);
				$habitat = "";
			}
		}
		$iExsNumber = intval($exsnumber);
		if($iExsNumber > 150) $ometid = "38";
		else $ometid = "37";
		$exsnumber = str_replace(" ", "", $exsnumber);
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
			'verbatimCoordinates' => $verbatimCoordinates,
			'associatedCollectors' => $associatedCollectors,
			'ometid' => $ometid,
			'exsnumber' => $exsnumber
		);
	}

	private function doLichenesAmericaniExsiccatiLabel($s) {
		$pattern =
			array
			(
				"/(?:[1Il!|]{2}|U)chenes ?Amer[1Il!|]can[1Il!|] ?Exs[1!lI]ccat./is",
				"/Co[1Il!|]{2}ected ?and ?pub[1Il!|]{2}shed ?by ?M[.,] ?E[.,] ?Hale/i",
				"/ No[,.] (\\d)/",
				"/\\nvar\\. ([A-Za-z])/"
			);
		$replacement =
			array
			(
				"",
				"Collected by M. E. Hale",
				"\nNo. \${1}",
				" var. \${1}"
			);
		return $this->doGenericLabel(trim(preg_replace($pattern, $replacement, $s, -1)), "276");
	}

	private function doLichenesIsidiosiEtSorediosiExsiccatiLabel($s) {
		$pattern =
			array
			(
				"/(?:[1Il!|]{2}|U)chenes ?[1!lI]s[1!lI]d[1!lI][0o]s[1!lI] ?Et ?S[0o]red[1!lI][0o]s[1!lI] ?Crustacei ?Exs[1!lI]ccat./is",
				"/(\\d)TV/",
				"/ No[,.] (\\d)/"
			);
		$replacement =
			array
			(
				"",
				"\${1}'W",
				"\nNo. \${1}"
			);
		return $this->doGenericLabel(trim(preg_replace($pattern, $replacement, $s, -1)), "96");
	}

	private function doLichensOfWesternNorthAmericaLabel($s) {
		//echo "\nDoing LichensOfWesternNothAmericaLabel\n";
		$pattern =
			array
			(
				"/(?:ANDER[S5][0o]N AND [S5]HU[S5]HAN.{1,3})?(?:[1Il!|]{2}|U)[ce]h[ce]ns [0o]f W[ce]st[ce]rn N[0o]rth Ameri[ce]a/is",
				"/ No\\. (\\d+)\\n((?-i)[A-Z])/i",
				"/\\nNo\\. (\\d+\\.?)\\n((?-i)[A-Z])/i",
				"/40\* 13' 45\" North Latitude, 105' 21' West,/",
				"/41 North Latitude,/",
				"/Shushan fsl-5023\)/"
			);
		$replacement =
			array
			(
				"",
				"\nNo. \${1} \${2}",
				"\nNo. \${1} \${2}",
				"40� 13' 45\" North Latitude, 105� 21' West",
				"41�North Latitude,",
				"Shushan (sl-5023)"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$exsnumber = "";
		$associatedTaxa = "";
		$associatedCollectors = "";
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		$recordNumber = "";
		$identifiedBy = "";
		$recordedBy = "";
		$recordedById = "";
		$otherCatalogNumbers = "";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {//foreach($collectorInfo as $k => $v) echo "\n".$k.": ".$v."\n";
			if(array_key_exists('collectorName', $collectorInfo)) {
				$recordedBy = $collectorInfo['collectorName'];
				if(stripos($recordedBy, " Thomson") !== FALSE) $recordedBy = "";
				else if(stripos($recordedBy, " Anderson") !== FALSE && stripos(substr($recordedBy, 0, strrpos($recordedBy, " ")), "R") !== FALSE) {
					$recordedBy = "Roger A. Anderson";
					if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
					if(array_key_exists('collectorID', $collectorInfo)) $recordedById = "181";
					if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
					if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
					if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
				} else {
					if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
					if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
					if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
					if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
					if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
				}
			}
		}

		$fields = array();
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$possibleNumbers = "[OQSZl|I!0-9]";
		if($recordedBy) {
			if(stripos($recordedBy, "Shushan") !== FALSE) {
				if(preg_match("/\(s[I1!|l][-_](".$possibleNumbers."{1,5}+[A-Fa-f]?)\)/i", $recordNumber, $mats)) $recordNumber = "sI-".$this->replaceMistakenNumbers($mats[1]);
				else if(preg_match("/s[I1!|l][-_](".$possibleNumbers."{1,5}+[A-Fa-f]?)/i", $recordNumber, $mats)) $recordNumber = "sI-".$this->replaceMistakenNumbers($mats[1]);
				else {
					$exsnumber = $recordNumber;
					$recordNumber = "";
				}
			} else {
				if(strcmp($recordedBy, "SDL") == 0) {
					$recordedBy = "Steven D. Leavitt";
					$recordedById = "9957";
				}
				$exsnumber = $recordNumber;
				$recordNumber = "";
			}
			$fields['recordedBy'] = $recordedBy;
		}
		if(strlen($recordNumber) == 0) {
			$lines = array_reverse(explode("\n", $s));
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
							if(preg_match("/.*".$lName."[- ]\(?(".$possibleNumbers."{1,6}+[A-Fa-f]?)\)?/", $line, $mats)) $recordNumber = $this->replaceMistakenNumbers($mats[1]);
						}
					} else {
						if(preg_match("/.*BRY(?:[a-z]-|\\s)".$possibleNumbers."{1,6}+[A-Fa-f]?(.*)/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 9896, mats[".$i++."] = ".$mat."\n";
							$line = trim($mats[1]);
							if(strlen($line) > 6 && preg_match("/^([A-Za-z. ]{6,18})[- ]\(?(".$possibleNumbers."{1,6}+[A-Fa-f]?)\)?/", $line, $mats2)) {
								$recordedBy = trim($mats2[1]);
								$recordNumber = $this->replaceMistakenNumbers($mats2[2]);
							}
						} else {
							if(preg_match("/^([A-Z][A-Za-z .]{1,18})[- ]\(?(".$possibleNumbers."{1,6}+[A-Fa-f]?)\)?/", $line, $mats)) {
								$recordedBy = trim($mats[1]);
								$recordNumber = $this->replaceMistakenNumbers($mats[2]);
							}
						}
					}
				}
			}
		}
		if($recordedBy) $fields['recordedBy'] = $recordedBy;
		if($recordNumber) $fields['recordNumber'] = $recordNumber;
		if($recordedById) $fields['recordedById'] = $recordedById;
		if($identifiedBy) $fields['identifiedBy'] = $identifiedBy;
		if($otherCatalogNumbers) $fields['otherCatalogNumbers'] = $otherCatalogNumbers;
		if($associatedCollectors) $fields['associatedCollectors'] = $associatedCollectors;
		if($exsnumber) $fields['exsnumber'] = $exsnumber;
		if($verbatimCoordinates) $fields['verbatimCoordinates'] = $verbatimCoordinates;
		return $this->doGenericLabel($s, "242", $fields);
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
		$associatedCollectors = "";
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
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
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
			$state_province = trim($countyMatches[4]);
			$country = trim($countyMatches[2]);
			$firstPart = trim($countyMatches[0]);
			$county = trim($countyMatches[1]);
			//echo "\nline 6738, firstPart: ".$firstPart."location: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
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
			'verbatimCoordinates' => $verbatimCoordinates,
			'associatedCollectors' => $associatedCollectors
		);
	}

	private function doMycologicalCollectionsLabel($s) {
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
		$dateIdentified = array();
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
								if(strlen($temp) > 3 && $this->containsVerbatimAttribute($temp)) $verbatimAttributes = $temp;
							}
						}
					}
					if(strlen($verbatimAttributes) == 0) {
						$pos = strrpos($substrate, ".");
						if($pos !== FALSE) {
							$temp1 = trim(substr($substrate, 0, $pos));
							$temp2 = trim(substr($substrate, $pos+1));
							if(strlen($temp1) > 3 && $this->containsVerbatimAttribute($temp2)) {
								$verbatimAttributes = $temp2;
								$substrate = $temp1;
								break;
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
					$county = trim($countyMatches[1]);
					$country = trim($countyMatches[2]);
					$sp = $this->getStateOrProvince(trim($countyMatches[4]));
					if(count($sp) > 0) {
						$state_province = $sp[0];
						$country = $sp[1];
					} else {
						$sp = $this->getStateOrProvince(trim($countyMatches[3]));
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
					if($this->containsVerbatimAttribute($temp2)) {
						$verbatimAttributes = $temp2;
						$habitat = $temp;
						if(preg_match("/(.+)([,.])(.+)/", $verbatimAttributes, $mats3)) {
							$temp = $mats3[1];
							$temp2 = $mats3[3];
							if(!$this->containsVerbatimAttribute($temp)) {
								$habitat .= $mats3[2].$temp;
							}
						}
						if(preg_match("/(.+)([,.])(.+)/", $habitat, $mats3)) {
							$temp = $mats3[1];
							$temp2 = $mats3[3];
							if($this->containsVerbatimAttribute($temp2)) {
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
					if(preg_match("/(.*)\([S5-8][OQSZlU|I!0-9&]�.*/", $location, $mats)) $location = trim($mats[1]);
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

	private function doMTLichensOfLabel($s) {
		//echo "\nDoing LichensOfLabel\n";
		$pattern =
			array
			(
				"/fr.udans/i",
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
				"/Colxj o. ¿tversman/",
				"/Asp[1Il!|]c.?[1Il!|]{2,3}a/i",
				"/Asp.ci.{1,2}ia/i",
				"/\\sLecidella.?putavin./i",
				"/Vulpicida\\s\(Tuckermannopsis\)\\spinastri\\s\(Scop.\)\\n\(&3B&.\\sJ.-E.Mattsson\\s&\\sM.J.Lai\\s/i",
				"/A\\slector\\si[dao]\\s/",
				"/\\bsubal[A-Za-z]ine\\b/i",
				"/\\bBe.[1Il!|]emerea\\b/i",
				"/([A-Za-z]{3,}) Co , /",
				"/\\sASPICI.{1,2}IA\\b/i",
				"/\\sASPIC.{1,2}HA\\b/i",
				"/.{1,2}ra himalayana (Church. Bab.) Timdal i soil/",
				"/ ~\\s?(\\d+)/",
				"/ c[o0]rn.?us /i",
				"/site hen sis/i",
				"/ .?rowing with /i",
				"/ Forest Mixed /i"
			);
		$replacement =
			array
			(
				"fraudans",
				"caesiocinerea",
				"Amandinea ",
				"Matanuska Susitna",
				"\nUmbilicaria ",
				"\nXanthoria ",
				"Usnea hirta",
				" litter ",
				"Peltigera neckeri",
				"Eversman",
				"Sharon Eversman",
				"Coll: S. Eversman",
				"Coll: S. Eversman",
				"Aspicilia",
				"Aspicilia",
				"\nLecidella putavina",
				"Vulpicida pinastri (Scop.) J.-E.Mattsson & M.J.Lai (Tuckermannopsis)\n",
				"Alectoria ",
				"subalpine",
				"Bellemerea",
				"\${1} Co., ",
				"\nAspicilia",
				"\nAspicilia",
				"\nPsora himalayana (Church. Bab.) Timdal on soil",
				". ~\${1}",
				" Cornus ",
				"sitchensis",
				" growing with ",
				" Forest\nMixed "
			);
		$recordedBy = '';
		$recordNumber = '';
		$recordedById = '';
		$otherCatalogNumbers = '';
		$associatedCollectors = '';
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
			if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
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
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		if(strlen($verbatimCoordinates) > 0) $s = str_replace($verbatimCoordinates, "", $s);
		//echo "\nverbatimCoordinates: ".$verbatimCoordinates."\n";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			if(strlen($identifiedBy) == 0) $identifiedBy = $identifier[0];
			$date_identified = $identifier[1];
		}
		if(strcasecmp($identifiedBy, "se") == 0) $identifiedBy = "Sharon Eversman";
		if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sM[O0Q]ntana(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Montana";
			$country = "U.S.A.";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sYe[1Il!|]{2}[O0Q]wstone\\sNat[1Il!|][O0Q]na[1Il!|]\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$location = "Yellowstone National Park";
		} else if(preg_match("/[ce]hens\\s[O0Q]F\\sYell[O0Q]wstone\\sNat[1Il!|][O0Q]na[1Il!|]\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$location = "Yellowstone National Park";
		} else if(preg_match("/L[EF]W\\s?[1Il!|]S\\s?A.{2,7}\\s?CLARK\\s?C\\s?AV\\s?ERNS\\s?STATE\\sPARK(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Montana";
			$county = "Jefferson";
			$country = "U.S.A.";
			$location = "Lewis and Clark Caverns State Park";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\sHEADWATERS\\s?STATE\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Montana";
			$county = "Gallatin";
			$country = "U.S.A.";
			$location = "Headwaters State Park";
		} else if(preg_match("/(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s[O0Q]F\\s[CG]rand\\s?Tet[O0Q]n\\s?Nati[O0Q]na[1Il!|]\\sPark(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
			$state_province = "Wyoming";
			$county = "Teton";
			$country = "U.S.A.";
			$location = "Grand Teton National Park";
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
			$location = "Chilkat Pass";
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
			$location = "Denali State Park";
		} else if(preg_match("/(?:Macro)?(?:U|L[1Il!|]|[1Il!|])[ce]h[ce]ns\\s(?:[O0Q]F|FR[O0Q]M)\\s[A-Za-z]{3,}\\b(.*)/is", $s, $mats)) {
			$s = trim($mats[1]);
		}
		$workingSection = $s;
		$countyMatches = $this->findCounty($s, $state_province);
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
			if(strlen($county) == 0) $county = trim($countyMatches[1]);
			if(strlen($state_province) == 0) $state_province = trim($countyMatches[4]);
			if(strlen($country) == 0) $country = trim($countyMatches[2]);
			$firstPart = trim($countyMatches[0]);
			$lastPart = trim($countyMatches[3]);
			if(str_word_count($firstPart) > 3) {
				if(str_word_count($lastPart) > 3) {
					$countHab1 = $this->countPotentialHabitatWords($firstPart);
					$countHab2 = $this->countPotentialHabitatWords($lastPart);
					$countLoc1 = $this->countPotentialLocalityWords($firstPart);
					$countLoc2 = $this->countPotentialLocalityWords($lastPart);
					//echo "\nfirstPart: ".$firstPart."\nlastPart: ".$lastPart."\ncountHab1: ".$countHab1."\ncountHab2: ".$countHab2."\ncountLoc1: ".$countLoc1."\ncountLoc2: ".$countLoc2."\n";
					if($countLoc2 + $countHab2 > $countLoc1 + $countHab1) $workingSection = $lastPart;
					else if($countLoc1 + $countHab1 > 0) $workingSection = $firstPart;
				} else $workingSection = $firstPart;
			} else $workingSection = $lastPart;
		}
		//echo "\nline 10695, workingSection: ".$workingSection."\n";
		$lookForAssciatedTaxa = false;
		$foundSciName = false;
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 7394, line: ".$line."\n";
			$line = trim($line);
			if(strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
				if($foundSciName) {
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
								if(strlen($associatedTaxa) > 0) {
									//findCounty removes double quotes so put them back so they match
									if(strcasecmp($s, $workingSection) != 0) $associatedTaxa = trim(str_replace("\"", "", $associatedTaxa));
									$pos = stripos($workingSection, $associatedTaxa);
									if($pos !== FALSE) $workingSection = trim(substr($workingSection, $pos+strlen($associatedTaxa)));
								}
								if(strlen($substrate) > 0) {
									if(strcasecmp($s, $workingSection) != 0) $substrate = trim(str_replace(array("\"", " & "), array("", " and "), $substrate));
									$pos = stripos($workingSection, $substrate);
									if($pos !== FALSE) $workingSection = trim(substr($workingSection, $pos+strlen($substrate)));
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
						if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
						if(array_key_exists('substrate', $psn)) $substrate = $psn['substrate'];
						//findCounty removes double quotes so put them back so they match
						if(strcasecmp($s, $workingSection) != 0) $line = trim(str_replace("\"", "", $line));
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
		}
		if(strlen($workingSection) == 0) {
			if(preg_match("/.+\\sCO(?:\\.|UNTY)[;:.,]?+(.+)/i", $s, $mats)) $workingSection = trim($mats[1]);
		}
		if(strlen($workingSection) > 0) {
			$elevArr = $this->getElevation($workingSection);
			$temp = $elevArr[0];
			$temp1 = $elevArr[1];
			$numericPart = 0;
			if(preg_match("/[A-Za-z ]*(\\d+)[A-Za-z ]+/", $temp1, $mats)) $numericPart = $mats[1];
			$temp2 = $elevArr[2];
			if(strlen($temp) > 0) {
				if(preg_match("/^(?:above|be(?:low|yond|neath)|along|under)/i", $temp2)) {
					if($numericPart > 300) $elevation = $temp1;
				} else $elevation = $temp1;
			}
			if(strlen($elevation) == 0) {
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
		}
		if(strlen($workingSection) > 0) {
			$lines = explode("\n", $workingSection);
			foreach($lines as $line) {//echo "\nline 10798, line: ".$line."\n";
				$line = trim($line, " ,");
				if(preg_match("/(.+?).?\\s{1,2}(Located\\s.+)/is", $line, $mats)) {
					$temp = trim($mats[1]);
					if(preg_match("/^((?:(?:Fairly\\s|Un)?common\\s|abundant\\s|occasional )?On .+)/i", $temp) || preg_match("/\\b(On\\s.+\\s(?:un)?common)\\b[;:,.]?(.++)/i", $temp)) {
						$substrate = $this->mergeFields($substrate, $temp);
					} else if($this->countPotentialHabitatWords($temp) > 0) {
						$habitat = $this->mergeFields($habitat, $temp);
					}
					$location = $this->mergeFields($location, trim($mats[2]));
					continue;
				}
				if(preg_match("/^((?:(?:Fairly\\s)?common\\s|abundant\\s|occasional )?On .+)/i", $line)) {
					$lCount0 = $this->countPotentialLocalityWords($line);
					$hCount0 = $this->countPotentialHabitatWords($line);
					$first = "";
					$rest = "";
					if(preg_match("/^((?:(?:Fairly\\s|Un)?common |abundant |occasional )?On .+(?:occasional|(?:not |fairly |very )?+(?:(?:un)?common|rare)))\\b[ ;:.,]?(.*)/i", $line, $mats2)) {
						$first = trim($mats2[1], " ,;");
						$rest = trim($mats2[2], " ,;");
					} else if(preg_match("/^((?:(?:Fairly\\s|Un)?common |abundant |occasional )?On .{3,30}) ((?:near|along|among|within|between|above|below) .+)/i", $line, $mats2)) {
						$first = trim($mats2[1], " ,;");
						$rest = trim($mats2[2], " ,;");
					} else if(preg_match("/^((?:(?:Fairly\\s|Un)?common |abundant |occasional )?On .+?), (.+)/i", $line, $mats2)) {
						$first = trim($mats2[1], " ,;");
						$rest = trim($mats2[2], " ,;");
					} else if(preg_match("/^((?:(?:Fairly\\s|Un)?common |abundant |occasional )?On .+)[;:] (.+)/i", $line, $mats2)) {
						$first = trim($mats2[1], " ,;");
						$rest = trim($mats2[2], " ,;");
					} else if(preg_match("/^((?:(?:Fairly\\s|Un)?common |abundant |occasional )?On .+ [A-Za-z]{3,})\\. (.+)/i", $line, $mats2)) {
						$first = trim($mats2[1], " ,;");
						$rest = trim($mats2[2], " ,;");
					}
					if(strlen($rest) < 3) $rest = "";
					$lCount1 = $this->countPotentialLocalityWords($first);
					$hCount1 = $this->countPotentialHabitatWords($first);
					$lCount2 = $this->countPotentialLocalityWords($rest);
					$hCount2 = $this->countPotentialHabitatWords($rest);
					//echo "\nline 10835, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\nfirst: ".$first."\nrest: ".$rest."\nlCount1: ".$lCount1."\nhCount1: ".$hCount1."\nlCount2: ".$lCount2."\nhCount2: ".$hCount2."\n";
					if($hCount2 > $lCount2) {
						$habitat = $this->mergeFields($habitat, $rest);
						if(strlen($substrate) == 0 && $lCount1 == 0) $substrate = $first;
						else if($hCount1 > 0) $habitat = $first.", ".$habitat;
						else if($lCount1 > 0) $location = $this->mergeFields($location, $first);
					} else if($hCount1 > $lCount1) {
						if(strlen($substrate) == 0) {
							$substrate = $first;
							if($lCount2 > 0) $location = $this->mergeFields($location, $rest);
							else $habitat = $this->mergeFields($habitat, $rest);
						} else if($lCount2 > 0) {
							$habitat = $this->mergeFields($habitat, $first);
							$location = $this->mergeFields($location, $rest);
						} else $habitat = $this->mergeFields($habitat, $line);
					} else if($lCount2 > 0) {
						if($lCount1 > 0) $location = $this->mergeFields($location, $line);
						else if(strlen($substrate) == 0) {
							$substrate = $first;
							$location = $this->mergeFields($location, $rest);
						} else if($hCount1 > 0) {
							$habitat = $this->mergeFields($habitat, $first);
							$location = $this->mergeFields($location, $rest);
						} else $location = $this->mergeFields($location, $line);
					} else if(strlen($substrate) == 0) $substrate = $line;
					else if($hCount0 > 0) $habitat = $this->mergeFields($habitat, $line);
					else $location = $this->mergeFields($location, $line);
					continue;
				}
				$hCount = $this->countPotentialHabitatWords($line);
				$lCount = $this->countPotentialLocalityWords($line);
				if($hCount > 0 && $lCount > 0) {
					$firstPart = "";
					$lastPart = "";
					if(preg_match("/(.{6,} \\w{4,}\\.) (.{6,})/", $line, $mats)) {
						$firstPart = trim($mats[1]);
						$lastPart = trim($mats[2]);
					} else {
						$pos = strpos($line, ":");
						if($pos === FALSE) $pos = strpos($line, ";");
						if($pos === FALSE) $pos = strpos($line, ",");
						if($pos !== FALSE) {
							$firstPart = trim(substr($line, 0, $pos));
							$lastPart = trim(substr($line, $pos+1));
						}
					}
					if(strlen($firstPart) > 0) {
						$hCount21 = $this->countPotentialHabitatWords($firstPart);
						$lCount21 = $this->countPotentialLocalityWords($firstPart);
						$hCount22 = $this->countPotentialHabitatWords($lastPart);
						$lCount22 = $this->countPotentialLocalityWords($lastPart);
						//echo "\nline 9915, location: ".$location."\nhabitat: ".$habitat."\nfirstPart: ".$firstPart."\nlastPart: ".$lastPart."\nhCount21: ".$hCount21."\nlCount21: ".$lCount21."\nhCount22: ".$hCount22."\nlCount22: ".$lCount22."\n";
						if((($hCount21 > $lCount21) || ($hCount21 == $lCount21 && $lCount21 > 0)) && $lCount22 > $hCount22) {
							$habitat = $this->mergeFields($habitat, $firstPart);
							$location = $this->mergeFields($location, $lastPart);
						} else if((($hCount22 > $lCount22) || ($hCount22 == $lCount22 && $lCount22 > 0)) && $lCount21 > $hCount21) {
							$habitat = $this->mergeFields($habitat, $lastPart);
							$location = $this->mergeFields($location, $firstPart);
						} else if($hCount > $lCount) $habitat = $this->mergeFields($habitat, $line);
						else $location = $this->mergeFields($location, $line);
					} else if($hCount > $lCount) $habitat = $this->mergeFields($habitat, $line);
					else if($lCount > 0) $location = $this->mergeFields($location, $line);
				} else if($hCount > 0) $habitat = $this->mergeFields($habitat, $line);
				else if($lCount > 0) $location = $this->mergeFields($location, $line);
			}
		}
		if(strlen($elevation) > 1) {
			if(preg_match("/(.*?)(?:elev(?:\\.|ation)?[:;]?)?\\s?".$elevation."[,.]?\\s(On\\s.{3,})\\s(in\\s.+)/i", $location, $mats)) {
				$location = trim($mats[1]);
				if(strlen($substrate) == 0) {
					$substrate = trim($mats[2]);
					$habitat = trim($mats[3]);
				} else $habitat = trim($mats[2])." ".trim($mats[3]);
			} else if(preg_match("/(.*?)(?:elev(?:\\.|ation)?[:;]?)?\\s?".$elevation."[,.]?\\s((?:(?:Fairly\\s|Un)?common\\s)?On\\s.*)/i", $location, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 7098, mats[".$i++."] = ".$mat."\n";
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
						else if(stripos($habitat, $mats1) === FALSE) $habitat .= " ".$mats1;
					}
				} else {
					$location = $mats1;
					if($this->countPotentialHabitatWords($mats2) > 0) {
						if(strlen($substrate) == 0 && strcasecmp(substr($mats2, 0, 3), "on ") == 0) $substrate = $mats2;
						else if(stripos($habitat, $mats2) === FALSE) $habitat .= " ".$mats2;
					}
				}
			}
		}//echo "\nline 10927, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
		if(strlen($recordedBy) > 1) {
			if(preg_match("/^(.*?)\\s?".preg_quote($recordedBy, '/')."(.*)/i", $location, $mats)) {
				$mats1 = trim($mats[1]);
				$mats2 = trim($mats[2]);
				if($this->countPotentialLocalityWords($mats2) > $this->countPotentialLocalityWords($mats1)) $location = $mats2;
				else $location = $mats1;
			} else if(preg_match("/^(.*?)\\s?".preg_quote($recordedBy, '/')."(.*)/i", $habitat, $mats)) {
				$mats1 = trim($mats[1]);
				$mats2 = trim($mats[2]);
				if($this->countPotentialLocalityWords($mats2) > $this->countPotentialLocalityWords($mats1)) {
					$habitat = $this->mergeFields($habitat, $mats2, " ");
				} else $habitat = $this->mergeFields($habitat, $mats1, " ");
			}
		}//echo "\nline 10238, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
		if(strlen($recordNumber) > 1 && preg_match("/(.*?)\\s?".preg_quote($recordNumber, '/')."(.*)/i", $location, $mats)) {
			$mats1 = trim($mats[1]);
			$mats2 = trim($mats[2]);
			if($this->countPotentialLocalityWords($mats2) > $this->countPotentialLocalityWords($mats1)) $location = $mats2;
			else $location = $mats1;
		}
		if(strlen($substrate) > 6) {
			if(preg_match("/(.{3,}?)((?: along| .?rowing)? with(.*))/i", $substrate, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 10244, mats2[".$i++."] = ".$mat2."\n";
				$temp = trim($mats2[3]);
				while(preg_match("/^([A-Za-z0125!|]+(?: [A-Za-z0125!|]+)?)[,;](.+)/", $temp, $mats3)) {//$i=0;foreach($mats3 as $mat3) echo "\nline 10245, mats3[".$i++."] = ".$mat3."\n";
					$name = trim($mats3[1]);
					$temp = trim($mats3[2]);
					if($this->isPossibleSciName($name)) {
						$associatedTaxa = $this->mergeFields($associatedTaxa, $name);
						$substrate = trim($mats2[1]);
					} else {
						$name2 = trim($mats2[2]);
						if($this->countPotentialHabitatWords($name2) > 0) {//echo "\nline 7670, got a habitat word\n";
							$habitat = $this->mergeFields(trim($habitat, " ;:,"), $name2);
							$substrate = trim($mats2[1]);
							$temp = "";
							break;
						} else {
							$psn = $this->processSciName($name);
							if($psn != null) {
								if(array_key_exists('scientificName', $psn)) {
									$associatedTaxa = $this->mergeFields($associatedTaxa, $name);
									$habitat = $this->mergeFields(trim($habitat, " ;:,"), $name2);
									$substrate = trim($mats2[1]);
									$temp = "";
									break;
								}
							} else {
								$temp = $name2;
								break;
							}
						}
					}
				}
				if(strlen($temp) > 0) {//echo "\nline 10280, temp: ".$temp."\n";
					$sWords = explode(" ", $temp);
					if(count($sWords) == 1) {
						$sWord = trim($sWords[0], " ,;");
						if($this->isPossibleSciName($sWord)) {
							$associatedTaxa = $this->mergeFields($associatedTaxa, $sWord, " ");
							$substrate = trim($mats2[1]);
						} else if($this->countPotentialHabitatWords($temp) > 0) {//echo "\nline 7705, got a habitat word\n";
							$habitat = $this->mergeFields(trim($habitat, " ;:,"), $temp);
							$substrate = trim($mats2[1]);
						} else {
							$psn = $this->processSciName($sWord);
							if($psn != null) {
								$habitat = $this->mergeFields(trim($habitat, " ;:,"), $temp);
								$substrate = trim($mats2[1]);
							}
						}
					} else if(count($sWords) > 1) {
						$sWord = trim($sWords[0], " ,;")." ".trim($sWords[1], " ,;");
						if($this->isPossibleSciName($sWord)) {
							$associatedTaxa = $this->mergeFields($associatedTaxa, $sWord, " ");
							$substrate = trim($mats2[1]);
							if(count($sWords) > 2) {
								$rest = substr($temp, stripos($temp, $sWords[2]));
								$lCount = $this->countPotentialLocalityWords($rest);
								if($this->countPotentialHabitatWords($rest) > $lCount) $habitat = $this->mergeFields(trim($habitat, " ;:,"), $rest);
								else if($lCount > 0) $location = $this->mergeFields(trim($location, " ;:,"), $rest);
							}
						} else {
							$psn = $this->processSciName($sWord);
							if($psn != null) {
								$habitat = $this->mergeFields(trim($habitat, " ;:,"), $temp);
								$substrate = trim($mats2[1]);
							} else {
								$sWord = trim($sWords[0], " ,;");
								if($this->isPossibleSciName($sWord)) {
									$associatedTaxa = $this->mergeFields($associatedTaxa, $sWord, " ");
									$substrate = trim($mats2[1]);
									if(count($sWords) > 2) {
										$rest = substr($temp, stripos($temp, $sWords[2]));
										$lCount = $this->countPotentialLocalityWords($rest);
										if($this->countPotentialHabitatWords($rest) > $lCount) $habitat = $this->mergeFields($habitat, $rest);
										else if($lCount > 0) $location = $this->mergeFields(trim($location, " ;:,"), $rest);
									}
								} else {
									$psn = $this->processSciName($sWord);
									if($psn != null) {
										$associatedTaxa = $this->mergeFields($associatedTaxa, $sWord, " ");
										$substrate = trim($mats2[1]);
										if(count($sWords) > 2) {
											$rest = substr($temp, stripos($temp, $sWords[2]));
											$lCount = $this->countPotentialLocalityWords($rest);
											if($this->countPotentialHabitatWords($rest) > $lCount) $habitat = $this->mergeFields($habitat, $rest);
											else if($lCount > 0) $location = $this->mergeFields(trim($location, " ;:,"), $rest);
										}
									} else if($this->countPotentialHabitatWords($temp) > 0) {
										$habitat = $this->mergeFields($habitat, $temp);
										$substrate = trim($mats2[1]);
									}
								}
							}
						}
					}
				}
			}//echo "\nline 10356, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
			$pos = strpos($substrate, "; ");
			if($pos !== FALSE) {
				$firstPart = trim(substr($substrate, 0, $pos));
				$secondPart = trim(substr($substrate, $pos));
				$spacePos = strpos($secondPart, " ");
				if($spacePos !== FALSE) {
					if(strcasecmp(substr($secondPart, 0, $spacePos), "occasional") == 0) {
						$firstPart .= " occasional";
						$secondPart = trim(substr($secondPart, $spacePos));
					}
				}
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
		if(strlen($habitat) > 6) {
			$pos = strpos($habitat, "; ");
			if($pos !== FALSE) {
				$firstPart = trim(substr($habitat, 0, $pos));
				$secondPart = trim(substr($habitat, $pos+1));
				if($this->countPotentialLocalityWords($secondPart) > $this->countPotentialLocalityWords($firstPart)) {
					$habitat = $firstPart;
					$location = $this->mergeFields($location, $secondPart);
				}
			}
			if(preg_match("/^([A-Za-z]{3,}(?: [A-Za-z]{3,}|spp?\\.)?), (.+)/", $habitat)) {
				$temp = $habitat;
				$tHabitat = "";
				while(preg_match("/^([A-Za-z]{3,}(?: [A-Za-z]{3,}|spp?\\.)?), (.+)/", $temp, $mats)) {
					$firstPart = trim($mats[1]);
					$temp = trim($mats[2]);
					if($this->countPotentialLocalityWords($temp) > $this->countPotentialLocalityWords($firstPart) &&
						($this->countPotentialHabitatWords($firstPart) > $this->countPotentialHabitatWords($temp) ||
						($this->countPotentialHabitatWords($firstPart) == $this->countPotentialHabitatWords($temp) &&
						$this->countPotentialHabitatWords($firstPart) > 0))
					) $tHabitat = $this->mergeFields($tHabitat, $firstPart);
					else {
						$temp = $firstPart.", ".$temp;
						break;
					}
				}
				if(strlen($tHabitat) > 0) {
					if(preg_match("/^(and [A-Za-z]{3,}(?: [A-Za-z]{3,}|spp?\\.)?) (.*)/", $temp, $mats)) {
						$firstPart = trim($mats[1]);
						$temp = trim($mats[2]);
						if(stripos($tHabitat, $firstPart) === FALSE) $tHabitat .= " ".$firstPart;
					}
					$location = $this->mergeFields($location, $temp);
					$habitat = $tHabitat;
				}
			}
		}//echo "\nline 10404, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
		if(strlen($location) > 6) {
			$pos = strpos($location, "; ");
			if($pos !== FALSE) {
				$firstPart = trim(substr($location, 0, $pos));
				$secondPart = trim(substr($location, $pos+1));
				if(strlen($firstPart) > 6 && strlen($secondPart) > 6) {
					$countHab1 = $this->countPotentialHabitatWords($firstPart);
					$countHab2 = $this->countPotentialHabitatWords($secondPart);
					$countLoc1 = $this->countPotentialLocalityWords($firstPart);
					$countLoc2 = $this->countPotentialLocalityWords($secondPart);
					if($countLoc2 > $countLoc1 && $countHab1 > 0) {
						if(strlen($substrate) == 0 && strcasecmp(substr($firstPart, 0, 3), "on ") == 0) $substrate = $firstPart;
						else $habitat = $this->mergeFields($habitat, $firstPart, " ");
						$location = $secondPart;
					} else if($countHab2 > $countHab1 && $countLoc1 > 0) {
						if(strlen($substrate) == 0 && strcasecmp(substr($secondPart, 0, 3), "on ") == 0) $substrate = $secondPart;
						else $habitat = $this->mergeFields($habitat, $secondPart, " ");
						$location = $firstPart;
					}
				}
			}
			if(strlen($habitat) == 0) {
				$pos = strpos($location, ". ");
				if(preg_match("/(.+[^ .]{4,}\\.)\\s(.+)/", $location, $mats)) {
					$firstPart = trim($mats[1]);
					$secondPart = trim($mats[2]);
					if(strlen($firstPart) > 6 && strlen($secondPart) > 6) {
						$countHab1 = $this->countPotentialHabitatWords($firstPart);
						$countHab2 = $this->countPotentialHabitatWords($secondPart);
						$countLoc1 = $this->countPotentialLocalityWords($firstPart);
						$countLoc2 = $this->countPotentialLocalityWords($secondPart);
						if($countLoc2 > $countLoc1 && $countHab1 > $countHab2) {
							if(strlen($substrate) == 0 && strcasecmp(substr($firstPart, 0, 3), "on ") == 0) $substrate = $firstPart;
							else $habitat = $firstPart;
							$location = $secondPart;
						} else if($countLoc1 > $countLoc2 && $countHab2 > $countHab1) {
							if(strlen($substrate) == 0 && strcasecmp(substr($secondPart, 0, 3), "on ") == 0) $substrate = $secondPart;
							else $habitat = $secondPart;
							$location = $firstPart;
						}
					}
				}
			}//echo "\nline 10451, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
			if(strlen($location) > 6) {
				if(preg_match("/^.?((?:along|growing)? with(.*))/i", $location, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 10445, mats[".$i++."] = ".$mat."\n";
					$temp = trim($mats[2]);
					$temp2 = trim($mats[1]);
					while(preg_match("/^([A-Za-z0125!|]+(?: [A-Za-z0125!|]+)?)[,;](.+)/", $temp, $mats2)) {
						$name = trim($mats2[1]);
						$temp = trim($mats2[2]);
						if($this->isPossibleSciName($name)) {
							$associatedTaxa = $this->mergeFields($associatedTaxa, $name, " ");
							$temp2 = preg_replace(array("/(?:, ){2,}/m", "/\\s{2,}/m"), array(", ", " "), str_ireplace($name, "", $temp2));
						} else break;
					}
					if(preg_match("/^.?((?:along|growing)? with\\b(.*))/i", $temp2, $mats2)) {
						$temp = trim($mats2[2]);
						$sWords = explode(" ", $temp);
						if(count($sWords) == 1) {
							$sWord = trim($sWords[0], " ,;");
							if($this->isPossibleSciName($sWord)) {
								$associatedTaxa = $this->mergeFields($associatedTaxa, $sWord, " ");
								$temp2 = preg_replace(array("/(?:, ){2,}/m", "/\\s{2,}/m"), array(", ", " "), str_ireplace($name, "", $sWord));
							}
						} else if(count($sWords) > 1) {
							$sWord = trim($sWords[0], " ,;")." ".trim($sWords[1], " ,;");
							if($this->isPossibleSciName($sWord)) {
								$associatedTaxa = $this->mergeFields($associatedTaxa, $sWord, " ");
								$temp2 = preg_replace(array("/(?:, ){2,}/m", "/\\s{2,}/m"), array(", ", " "), str_ireplace($name, "", $sWord));
							} else {
								$sWord = trim($sWords[0], " ,;");
								if($this->isPossibleSciName($sWord)) {
									$associatedTaxa = $this->mergeFields($associatedTaxa, $sWord, " ");
									$temp2 = preg_replace(array("/(?:, ){2,}/m", "/\\s{2,}/m"), array(", ", " "), str_ireplace($name, "", $sWord));
								}
							}
						}
					}
					$temp2 = trim(preg_replace("/(?:along|growing)? with , /i", "", $temp2));
					if(preg_match("/^.?((?:along|growing)? with .*[A-Za-z]{3,}[.;,:]) (.+)/i", $temp2, $mats2)) {
						$habitat = trim($mats2[1]);
						$location = trim($mats2[2]);
					} else $location = trim($temp2);
				}
				$temp = $location;
				$temp2 = "";
				while(preg_match("/^(.+)[,;](.+)$/", $temp, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 10490, mats[".$i++."] = ".$mat."\n";
					$mats1 = trim($mats[1]);
					$mats2 = trim($mats[2]);
					if($this->countPotentialHabitatWords($mats2) > 0 && $this->countPotentialLocalityWords($mats2) == 0) {
						if(strlen($temp2) == 0) $temp2 = $mats2;
						else $temp2 = $mats2.", ".$temp2;
						$temp = $mats1;
					} else break;
				}
				$habitat = $this->mergeFields($habitat, $temp2);
				$location = $temp;
			}
		}//echo "\nline 10487, location: ".$location."\nhabitat: ".$habitat."\nsubstrate: ".$substrate."\n";
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
		} else if(preg_match("/\\n(on\\s.+)\\n/i", $s, $mats)) {
			$mats1 = trim($mats[1]);
			if($this->countPotentialLocalityWords($mats1) == 0) $substrate = $mats1;
		}
		if(strlen($habitat) > 12) {
			if(preg_match("/(.+)\\sC[o0][l1I|!]{1,2}\\.\\s/i", $habitat, $mats)) $habitat = trim($mats[1]);
			if(strlen($elevation) > 3 && preg_match("/(.*?)(?:elev(?:\\.|ation)?[:;]?)?\\s?".str_replace(array("[", "]", "(", ")", "?", "/"), array("\[", "\]", "\(", "\)", "\?", "\/"), $elevation)."[,.]?\\s/i", $habitat, $mats)) $habitat = trim($mats[1]);
			if(preg_match("/(.*)\\sElevat[il!1|][o0]n[,.;:]?\\b/", $habitat, $mats)) $habitat = trim($mats[1]);
			if(preg_match("/(.*)\\sLat[il!1|]tude[,.;:]?\\b/", $habitat, $mats)) $habitat = trim($mats[1]);
			if(strlen($habitat) > 12) {
				if(preg_match("/(.+ [A-Za-z]{2,} [A-Za-z]{5,}\\.) (.+)/", $habitat, $mats)) {
					$firstPart = trim($mats[1]);
					$lastPart = trim($mats[2]);
					$hCount1 = $this->countPotentialHabitatWords($firstPart);
					$hCount2 = $this->countPotentialHabitatWords($lastPart);
					$lCount1 = $this->countPotentialLocalityWords($firstPart);
					$lCount2 = $this->countPotentialLocalityWords($lastPart);
					if($lCount2 > $hCount2) {
						$location = $this->mergeFields($location, $lastPart, " ");
						$habitat = $firstPart;
					} else if($lCount1 > $hCount1) {
						$location = $this->mergeFields($location, $firstPart, " ");
						$habitat = $lastPart;
					}
				}
				$pos = strpos($habitat, ",");
				//if($pos === FALSE) $pos = strrpos($firstPart, ";");
				if($pos !== FALSE) {
					$firstPart = trim(substr($habitat, 0, $pos));
					$lastPart = trim(substr($habitat, $pos+1));
					$hCount1 = $this->countPotentialHabitatWords($firstPart);
					$hCount2 = $this->countPotentialHabitatWords($lastPart);
					$lCount1 = $this->countPotentialLocalityWords($firstPart);
					$lCount2 = $this->countPotentialLocalityWords($lastPart);
					if($lCount2 > $hCount2) {
						$location = $this->mergeFields($location, $lastPart, " ");
						$habitat = $firstPart;
					} else if($lCount1 > $hCount1) {
						$location = $this->mergeFields($location, $firstPart, " ");
						$habitat = $lastPart;
					}
				}
				if(preg_match("/(.+ [A-Za-z]{5,}\\.) (.+)/", $location, $mats)) {
					$firstPart = trim($mats[1]);
					$lastPart = trim($mats[2]);
					$hCount1 = $this->countPotentialHabitatWords($firstPart);
					$hCount2 = $this->countPotentialHabitatWords($lastPart);
					$lCount1 = $this->countPotentialLocalityWords($firstPart);
					$lCount2 = $this->countPotentialLocalityWords($lastPart);
					if($hCount2 > $lCount2) {
						$habitat = $this->mergeFields($habitat, $lastPart, " ");
						$location = $firstPart;
					} else if($hCount1 > $lCount1) {
						$habitat = $this->mergeFields($habitat, $firstPart, " ");
						$location = $lastPart;
					}
				}
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $state_province,
			'country' => trim($country, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'locality' => preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimCoordinates' => $verbatimCoordinates,
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'identifiedBy' => trim($identifiedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => preg_replace(array("/[\r\n]/m", "/\\s{2,}/m"), " ", trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_")),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedById' => trim($recordedById, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-"),
			'associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-")
		);
	}

	private function doPlantsOfWisconsinLabel($s) {
		$pattern =
			array
			(
				"/H.{3,6}[li!1|]um\\s[o0][fp]\\sW[li!1|]sc[o0]ns[li!1|]n\\s[S5]tate\\sU[nm][li!1|]ver.{1,2}[li!1|]t.\\s?.\\s?[S5][um]p.{1,2}r[li!1|][o0e]r/i",
				"/B[o0]b\\sJac[o0]bs[o0]n/i",
				"/HERBAR[li!1|]UM.?[O0Q][FP].?(?:THE.?)?U.?N[li!1|]VERS[li!1|]TY.?[O0Q][FP].?W[li!1|]SC[o0Q]N.?S[li!1|]N\\s?.\\s?[S5][um]p.{1,2}r[li!1|][o0e]r/i",
				"/HERBAR[li!1|]UM.?[O0Q][FP].?(?:THE.?)?U.?N[li!1|]VERS[li!1|]TY.?[O0Q][FP].?W[li!1|]SC[o0Q]NS[li!1|]N\\.?/i",
				"/HUDY\\sG\\.?/i",
				"/U[,.]?\\s?[S5][,.]?\\s?Nat[li!1|]ona[li!1|]\\s?Mus[ce]um/i"
			);
		$replacement =
			array
			(
				"",
				"Robert Jacobson",
				"",
				"",
				"Collector: Rudy G.",
				""
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 7444, s:\n".$s."\n";
		$taxonRank = "";
		$infraspecificEpithet = "";
		$scientificName = "";
		$substrate = "";
		$stateProvince = "";
		$otherCatalogNumbers = "";
		$associatedCollectors = "";
		$verbatimAttributes = "";
		$associatedTaxa = "";
		$recordedBy = "";
		$recordNumber = "";
		$location = "";
		$habitat = "";
		$verbatimEventDate = "";
		$county = "";
		if(preg_match("/.*[PF][1Il!|]ant[s5].[O0Q]F.W[1Il!|][s5]con[s5][1Il!|]n.OR.M[1Il!|]nn[ec][s5][O0Q]ta(.*)/is", $s, $mats)) $s = trim($mats[1]);
		else if(preg_match("/.*[PF][1Il!|]ant[s5].[O0Q]F.W[1Il!|][s5]con[s5][1Il!|]n(.*)/is", $s, $mats)) {
			$stateProvince = "Wisconsin";
			$s = trim($mats[1]);
		}
		if(preg_match("/(.*)FL[O0Q]RA.?[O0Q][FP].?AM.?N.?[1Il!|]C[O0Q]N.?FA[1Il!|]{2}S.?STATE.?[PF]ARK(.*)/is", $s, $mats)) {
			$stateProvince = "Wisconsin";
			$county = "Douglas";
			$s = trim($mats[1])." ".trim($mats[2]);
		} else if(preg_match("/(.*)FL[O0Q]RA.?[O0Q]F.?PATTISON.?STATE.?[PF]ARK(.*)/is", $s, $mats)) {
			$stateProvince = "Wisconsin";
			$county = "Douglas";
			$location = "Pattison State Park";
			$s = trim($mats[1])." ".trim($mats[2]);
		} else if(preg_match("/(.*)FL[O0Q]RA.?[O0Q]F.?BRU[1Il!|]E.?R[1Il!|]VER.?STATE.?F[O0Q]RES.?(.*)/is", $s, $mats)) {
			$stateProvince = "Wisconsin";
			$county = "Douglas";
			$location = "Brule River State Forest";
			$s = trim($mats[1])." ".trim($mats[2]);
		}
		//echo "\nline 7479, s:\n".$s."\n";
		$countyMatches = $this->findCounty($s, $stateProvince);
		if(strlen($county) == 0) {
			if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\ncountyMatches[".$i++."] = ".$countyMatche."\n";
				$county = trim($countyMatches[1]);
				if(strlen($stateProvince) == 0) $stateProvince = trim($countyMatches[4]);
			}
		}
		$foundSciName = false;
		$lookingForAttributes = false;
		$lookingForIDRemarks = false;
		$date_identified = array();
		$identified_by = '';
		$identificationRemarks = '';
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
				if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
			}
		}//echo "\nline 6721, recordedBy: ".$recordedBy.", recordNumber: ".$recordNumber."\n";
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 7506, line: ".$line.", lookingForIDRemarks: ".$lookingForIDRemarks."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(strlen($line) > 0 && !$this->isMostlyGarbage($line, 0.60)) {
				if($lookingForIDRemarks) {
					$lookingForIDRemarks = false;
					if(preg_match("/Ac[l1|I!]ds\\s?[l1|I!]d[ec]nt[l1|I!]f[l1|I!][ec]d\\s/i", $line)) {
						$identificationRemarks = $line;
						continue;
					}
				}
				if($lookingForAttributes) {
					$lookingForAttributes = false;
					if(preg_match("/Tha[1Il!|]{2}us\\s/i", $line)) {
						$verbatimAttributes .= ". ".$line;
						$lookingForIDRemarks = true;
						continue;
					}
				}
				if(preg_match("/(.*)\\bMat[,.]\\schim[,.;:]{0,2}(.*)/i", $line, $mats)) {
					if(strlen($verbatimAttributes) > 0 && stripos($verbatimAttributes, $line) === FALSE) $verbatimAttributes .= " ".trim($mats[2]);
					else $verbatimAttributes = trim($mats[2]);
					$line = trim($mats[1]);
					$lookingForAttributes = true;
				}
				if(!preg_match("/.{0,3}?Co[1Il!|]{2}.?\\.?\\s/i", $line) && !preg_match("/.{0,3}?C?oll\\.?\\s/i", $line) &&
					stripos($line, $recordedBy) === FALSE) {
					if(!$foundSciName && strlen($line) > 3) {
						$psn = $this->processSciName($line);
						if($psn != null) {
							if(array_key_exists('scientificName', $psn)) {
								$scientificName = $psn['scientificName'];
								$pos = stripos($line, $scientificName);
								if($pos !== FALSE) $line = substr($line, $pos+strlen($scientificName));
							}
							if(array_key_exists('infraspecificEpithet', $psn)) {
								$infraspecificEpithet = $psn['infraspecificEpithet'];
								$pos = stripos($line, $infraspecificEpithet);
								if($pos !== FALSE) $line = substr($line, $pos+strlen($infraspecificEpithet));
							}
							if(array_key_exists('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists('verbatimAttributes', $psn)) {
								$theseVerbatimAttributes = $psn['verbatimAttributes'];
								if(strlen($verbatimAttributes) == 0) $verbatimAttributes = $theseVerbatimAttributes;
								else if(stripos($verbatimAttributes, $theseVerbatimAttributes) === FALSE) $verbatimAttributes .= " ".$theseVerbatimAttributes;
								$pos = stripos($line, $theseVerbatimAttributes);
								if($pos !== FALSE) $line = substr($line, $pos+strlen($theseVerbatimAttributes));
							}
							if(array_key_exists('associatedTaxa', $psn)) {
								$associatedTaxa = $psn['associatedTaxa'];
								$pos = stripos($line, $associatedTaxa);
								if($pos !== FALSE) $line = substr($line, $pos+strlen($associatedTaxa));
							}
							//if(array_key_exists('recordNumber', $psn) && strlen($recordNumber) == 0) $recordNumber = $psn['recordNumber'];
							if(array_key_exists('substrate', $psn)) {
								$substrate = $psn['substrate'];
								$pos = stripos($line, $substrate);
								if($pos !== FALSE) $line = substr($line, $pos+strlen($substrate));
							}
							$foundSciName = true;
							//continue;
						}
					}
					if(preg_match("/^.{0,1}(?:Epiphytic\\s|Common\\s)?on\\s.+/i", $line)) {
						if(strlen($substrate) > 0 && stripos($substrate, $line) === FALSE && stripos($line, $substrate) === FALSE) $substrate .= " ".$line;
						else $substrate = $line;
					} else {
						if(!preg_match("/.{0,3}\\bdet\\b\\.?.*/i", $line) &&
							($this->countPotentialLocalityWords($line) > $this->countPotentialHabitatWords($line) ||
							preg_match("/Va[1Il!|]{2}ey\\s?of\\s?the.?\\s?W[1Il!|]scons[1Il!|]n\\s?R[1Il!|].?ver/i", $line))) {
							if(strlen($location) > 0 && stripos($location, $line) === FALSE && stripos($line, $location) === FALSE) $location .= " ".$line;
							else $location = $line;
						} else if($this->countPotentialHabitatWords($line) > 0) $habitat = $line;
					}
				}
			}
		}
		if(strlen($substrate) > 3) {
			$pos = strpos($substrate, ";");
			if($pos !== FALSE) {
				$lastPart = trim(substr($substrate, $pos+1));
				$cntLoc = $this->countPotentialLocalityWords($lastPart);
				$cntHab = $this->countPotentialHabitatWords($lastPart);
				if($cntLoc > 0) {
					$substrate = trim(substr($substrate, 0, $pos));
					if(strlen($location) > 3) $location = $lastPart." ".$location;
					else $location = $lastPart;
				} else if($this->countPotentialHabitatWords($lastPart) > 0) {
					$substrate = trim(substr($substrate, 0, $pos));
					if(strlen($habitat) > 3) $habitat = $lastPart." ".$habitat;
					else $habitat = $lastPart;
				}
			}
		}
		if(strlen($location) > 3 && strlen($county) > 0) {
			if(preg_match("/(.*)\\b".$county."\\b/i", $location, $mats)) $location = trim($mats[1]);
		}

		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'stateProvince' => $stateProvince,
			'country' => "U.S.A.",
			'county' => ucfirst(trim($county, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => $taxonRank,
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identificationRemarks' => trim($identificationRemarks, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedCollectors' => trim($associatedCollectors, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")
		);
	}

	private function doLichenesCanadensesLabel($s) {
		$pattern =
			array
			(
				"/[DO0Q][1Il!|]str[1Il!|]but[ec]d\\s?b[ygq]\\s?Th[ec]\\s?Nat[1Il!|][o0]na[1Il!|]\\s?H[ec]rbar[1Il!|]um\\s?[o0]f [CG]anada\\s?\([CG]ANL\)/is",
				"/L[DO0Q]U[1Il!|][S5][1Il!|]ANA.?[S5]TAT[EFP].?UN[1Il!|]V[EFP]R[S5][1Il!|]TY.?H[EFP]RBAR[1Il!|]UM/is",
				"/[CG]anad[1Il!|]an.?Mus[ec]um.?of.?Natur[ec].?([CG]ANL)/is",
				"/L[1Il!|][CG]H[EFP]N[EFP][S5].?[CG]ANAD[EFP]NS[EFP]S.?[EFP]XS[1Il!|][CG]{2}AT[1Il!|]/is"
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
		//echo "\nline 7697, s:\n".$s."\n";
		if(preg_match( "/.*(?:L[1Il!|]|IZ|U)(?:[CG]H|QI)[FE][NH][FE]?[S5]\\s[CG]ANAD[FE]N[S5][FE][S5]\\s(?:EXS[1Il!|][GC]{2}AT[1Il!|])?(.+)/is", $s, $mats)) $s = trim($mats[1]);
		$exsnumber = "";
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$taxonRank = "";
		$infraspecificEpithet = "";
		$scientificName = "";
		$substrate = "";
		$county = "";
		$verbatimCoordinates = $this->getVerbatimCoordinates($s);
		$verbatimEventDate = "";
		$foundSciName = false;
		$lookingForLocation = false;
		$associatedTaxa = "";
		$associatedCollectors = "";
		$stateProvince = "";
		$date_identified = array();
		$verbatimAttributes = "";
		$location = "";
		$habitat = "";
		$identified_by = '';
		$recordedBy = "";
		$recordNumber = "";
		$possibleMonths = "Jan(?:\\.|(?:ua\\w{1,2}))?|Feb(?:\\.|(?:rua\\w{1,2}))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:i[l1|I!]))?|May|Jun[.e]?|Ju[l1|I!][.y]?|Aug(?:\\.|(?:ust))?|[S5]ep(?:\\.|(?:t\\.?)|(?:temb\\w{1,2}))?|[O0]ct(?:\\.|(?:[O0]b\\w{1,2}))?|N[O0]v(?:\\.|(?:emb\\w{1,2}))?|Dec(?:\\.|(?:emb\\w{1,2}))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
			$identified_by = $identifier[0];
			$date_identified = $identifier[1];
		}
		$lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 8534, line: ".$line."\n";
			$line = trim($line, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
			if(strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
				if(!$foundSciName) {
					if(preg_match("/^[^No0-9]{0,3}?(?:N[0o][.,_*#-]\\s)?([SZlU|I!1-9&][\]\[OQSZlU|I!0-9&]{0,2})[.,_*#-]?+\\s(.*)/", $line, $mats)) {
						$exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
						$psn = $this->processSciName(trim($mats[2]));
						if($psn != null) {
							if(array_key_exists ('scientificName', $psn)) $scientificName = $psn['scientificName'];
							if(array_key_exists ('infraspecificEpithet', $psn)) $infraspecificEpithet = $psn['infraspecificEpithet'];
							if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
							if(array_key_exists ('verbatimAttributes', $psn)) $verbatimAttributes = $psn['verbatimAttributes'];
							if(array_key_exists ('associatedTaxa', $psn)) $associatedTaxa = $psn['associatedTaxa'];
							//if(array_key_exists ('recordNumber', $psn)) $recordNumber = $psn['recordNumber'];
							if(array_key_exists ('substrate', $psn)) $substrate = $psn['substrate'];
							$foundSciName = true;
							continue;
						}
					}
				}
				if(preg_match("/^.{0,3}\\b(contain(?:s|ing)\\s.+)/i", $line, $mats) || $this->containsVerbatimAttribute($line)) {
					if(stripos($line, $verbatimAttributes) === FALSE) {
						if(strlen($verbatimAttributes) > 0) $verbatimAttributes .= ", ".$line;
						else $verbatimAttributes = $line;
					}
					continue;
				}
				if(strlen($stateProvince) == 0 && preg_match("/((?:\\w+\\s){0,2}\\w+)\\.(.*)/i", $line, $mats)) {
					$sp = $this->getStateOrProvince(trim($mats[1]));
					if(count($sp) > 0) {
						$country = $sp[1];
						if(strcasecmp($country, "Canada") == 0) {
							$stateProvince = $sp[0];
							$location = trim($mats[2]);
							$pos = stripos($s, $line);
							if($pos !== FALSE) $s = trim(substr($s, $pos+strlen($line)));
							$lookingForLocation = true;
							continue;
						}
					}
				}
				if($lookingForLocation) {
					if($this->countPotentialHabitatWords($line) > 0 || $this->countPotentialLocalityWords($line) > 0) {
						$location .= ", ".$line;
						continue;
					} else $lookingForLocation = false;
				}
				if(preg_match("/(.+),\\s(?:N[0o][.,_*#-]\\s)?([\]\[OQSZlU|I!0-9&]+(?:-[\]\[OQSZlU|I!0-9&]+)?)\\b/", $line, $mats)) {
					$temp = trim($mats[2]);
					if($this->containsNumber($this->replaceMistakenNumbers($temp))) {
						$recordNumber = $temp;
						$recordedBy = trim($mats[1]);
					}
				}
			}
		}//echo "\nline 11215, recordNumber: ".$recordNumber."\n";
		if(strlen($s) > 0 && strlen($recordedBy) == 0) {
			$collectorInfo = $this->getCollector($s);
			if($collectorInfo != null) {
				if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = $collectorInfo['collectorName'];
				if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
				if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
			}
		}
		if(strlen($location) > 0) {
			if(preg_match("/(.+)\\s(?:County|District)\\s?[;:.,]\\s(.*)/is", $location, $mats)) {
				$county = trim($mats[1]);
				$location = trim($mats[2]);
			} else if(preg_match("/([^ ]+(?:\\s[^ ]+(?:\\s[^ ]+)?)?)\\s[;:]\\s(.*)/is", $location, $mats)) {
				$county = trim($mats[1]);
				$location = trim($mats[2]);
			}
			if(strlen($elevation) > 0) {
				if(preg_match("/(.*?)(?:elev(?:[,.]|ation))?+\\s".str_replace("/", "\/", $elevation)."(.*)/i", $location, $mats)) {
					$location = trim($mats[1]);
					$habitat = trim($mats[2], " .,:;");
					if(preg_match("/(.*?)\\d{1,3}+\\s?�(.+)/", $location, $mats2)) {
						$location = trim($mats2[1]);
						if(strlen($habitat) > 0) $habitat = trim($mats2[2])." ".$habitat;
						else $habitat = trim($mats2[2]);
					}
					if(preg_match("/\\d{1,3}\\s?'(?:\\d{1,3}\\s?\")?+\\s?W\\b[,.]?(.*)/", $habitat, $mats2)) $habitat = trim($mats2[1]);
					else if(preg_match("/\\d{1,3}\\s?�[0-9 '\"*]+[NW]\\b[,.]?(.*)/", $habitat, $mats2)) $habitat = trim($mats2[1]);
				}
			} else if(preg_match("/(.*?)\\d{1,3}+\\s?�(.*)/", $location, $mats)) {
				$location = trim($mats[1]);
				$habitat = trim($mats[2]);
				if(preg_match("/\\d{1,3}\\s?'(?:\\d{1,3}\\s?\")?+\\s?W\\b[,.]?(.*)/", $habitat, $mats2)) $habitat = trim($mats2[1]);
				else if(preg_match("/\\d{1,3}\\s?�[0-9 '\"*]+[NW]\\b[,.]?(.*)/", $habitat, $mats2)) $habitat = trim($mats2[1]);
			} else {
				$pos = strpos($location, ";");
				if($pos !== FALSE) {
					$firstPart = trim(substr($location, 0, $pos));
					$secondPart = trim(substr($location, $pos+1));
					if($this->countPotentialHabitatWords($secondPart) > $this->countPotentialHabitatWords($firstPart)) {
						$location = $firstPart;
						$habitat = $secondPart;
					}
				}
			}
		}
		if(strlen($habitat) > 0) {
			$temp = "";
			if(preg_match("/^(.{9,})[.,;]\\s(on\\s.+)$/is", $habitat, $mats)) {
				$temp = trim($mats[2], " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
				if(preg_match("/(.+?)(?:;|:|\(|\\sat\\s|\\sin\\s|\\snear\\s)/i", $temp, $mats2)) $temp = trim($mats2[1]);
				else if(preg_match("/(.+\\s\\w{3,})\\..+/i", $temp, $mats2)) $temp = trim($mats2[1]);
			} else if(preg_match("/^(.{9,})\\s(on\\s[^ ]+(?:\\s[^ ]+(?:\\s[^ ]+)?+)?+)$/is", $habitat, $mats)) {
				$temp = trim($mats[2]);
			}
			if(strlen($temp) > 0) {
				if(!$this->containsNumber($temp) && $this->countPotentialLocalityWords($temp) == 0 &&
					!preg_match("/(?:\\bnorth|\\bsouth|\\beast\\bwest|shore)\\b/i", $temp)) $substrate = $temp;
			}
		}
		return array
		(
			'scientificName' => $this->formatSciName($scientificName),
			'county' => $county,
			'country' => "Canada",
			'substrate' => $substrate,
			'habitat' => trim($habitat, " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"),
			'locality' => trim($location, " \t\n\r\0\x0B,:.;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => $taxonRank,
			'stateProvince' => $stateProvince,
			'verbatimCoordinates' => $verbatimCoordinates,
			'verbatimAttributes' => $verbatimAttributes,
			'verbatimEventDate' => $verbatimEventDate,
			'verbatimElevation' => $elevation,
			'dateIdentified' => $date_identified,
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => $taxonRank,
			'associatedTaxa' => $associatedTaxa,
			'associatedCollectors' => $associatedCollectors,
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'recordedBy' => trim($recordedBy, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "259",
			'exsnumber' => $exsnumber
		);
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
		$dateIdentified = array();
		$country = "USA";
		$substrate = '';
		$location = trim($this->getLocality($s));
		$patStr = "/(.*)\\bLat\/Long/is";
		if(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?�/is";
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
				"/No. Aj�\\s/",
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
		$dateIdentified = array();
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
		$associatedCollectors = '';
		$location = "";
		$habitat = '';
		$elevation = '';
		$countyMatches = $this->findCounty($s, "");
		if($countyMatches != null) {//$i=0;foreach($countyMatches as $countyMatche) echo "\nline 4214, countyMatches[".$i++."] = ".$countyMatche."\n";
			$county = trim($countyMatches[1]);
			$country = trim($countyMatches[2]);
			$sp = $this->getStateOrProvince(trim($countyMatches[4]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			} else {
				$sp = $this->getStateOrProvince(trim($countyMatches[3]));
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
					if(array_key_exists('associatedCollectors', $collectorInfo) && strlen($associatedCollectors) == 0) $associatedCollectors = $collectorInfo['associatedCollectors'];
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
			'dateIdentified' => $dateIdentified,
			'associatedCollectors' => $associatedCollectors
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
		$associatedCollectors = '';
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
				if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
				if(strcasecmp($recordedBy, "Aaron Guy Johnson") == 0) {
					$recordedBy = "Anita Johnson";
					$recordedById = "";
				}
			}
		}
		$fields = array
		(
			'stateProvince' => "Alaska",
			'country' => "USA",
			'identifiedBy' => $identifiedBy,
			'dateIdentified' => $dateIdentified,
			'recordedBy' => $recordedBy,
			'associatedCollectors' => $associatedCollectors
		);
		$lfaPat = "/.*(?:L[1Il!|][CE]H[CE]N|[GC]RYPT[O0Q][GC]AM[1Il!|][GC])\\s?FL[O0Q]\\wA[.,]?\\s[O0Q]\\w\\sA[1Il!|]A[S5]KA(.+)/is";
		if(preg_match($lfaPat, $s, $ms)) $s = trim($ms[1]);
		return $this->doGenericLabel($s, "", $fields);
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
				"/N�-\\s(\\d)/",
				"/\\bNo\\. (\\d{1,2}) (\\d{1,2}\\.?) ([A-Z])/",
				"/\\. PROV\\. /",
				"/(\\d� \\d{1,3})�N, (\\d)/",
				"/Distributed by the University of Colorado Museum(?:, Boulder)? ?/i",
				"/HERBAR[l1|I!]UM OF L[O0]U[l1|I!]S[l1|I!]ANA STATE UN[l1|I!]VERS[l1|I!]TY/i",
				"/\\\(\\d,\\d{3} ?- ?\\d,\\d{3} m\\.)/",
				"/(ca\\. \\d{1,2}) (\\d{1,2} m\\.)/"
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
				"No. \${1}",
				"No. \${1}\${2} \${3}",
				". ",
				"\${1}'N, \${2}",
				"",
				"",
				"\${1}",
				"\${1}\${2}"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 11297, s: ".$s."\n\n";
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
		$taxonRank = "";
		$infraspecificEpithet = "";
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
			$location = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($countyMatches[3], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			//$location = ltrim(rtrim($countyMatches[3], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
			$county = trim($countyMatches[1]);
			$country = trim($countyMatches[2]);
			$temp = trim($countyMatches[4]);
			$sp = $this->getStateOrProvince($temp);
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
			if(preg_match("/(.*)(?:Univers)/i", $location, $mats)) $location = trim($mats[1]);
			//echo "\nline 10696, firstPart: ".$firstPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
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
		foreach($lines as $line) {//echo "\nline 10945, line: ".$line."\n";
			if(preg_match("/.*L[1Il!|][CE]H[CE]N[CE]S\\s[CE]XS[1Il!|][CE]{2}AT[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*(?:L[1Il!|]|U)[CE]H[CE]N[CE][S5]\\s[CE]X[S5][1Il!|][CE]{1,2}.?A(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*CHENE[S5] EXSI[CG]{2}AT[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			if(preg_match("/D[1Il!|]str[1Il!|]but[CE]d\\sby\\sth[CE]\\sUn[1Il!|]v[CE]rs[1Il!|]ty\\s[0O]f\\sC[0O].{2,4}ad[0O]\\sMus[CE]um,?\\sB[0O]uld[CE]r(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*Colorado\\sM[ui]seum,\\sB(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*Colorado\\sM[ui]seum,(.*)/i", $line, $mats)) $line = trim($mats[1]);
			if(preg_match("/^(.{0,4}?(?:[NMn]|tl|H)[0Oo][.,]?\\s([][OQSZlU|I!0-9&]{1,3})[.,_]?+\\s)(.*)/", $line, $mats) && strlen($line) > 6 && !$this->isMostlyGarbage($line, 0.60)) {
				$mats1 = $mats[1];
				$mats2 = $mats[2];
				$temp = trim($mats[3]);
				if(strlen(trim($temp)) > 3) {
					$exsnumber = $this->replaceMistakenNumbers(trim($mats2));
					$s = str_replace($mats1, "", $s);
					$location = str_replace($mats1, "", $location);
					$psn = $this->processSciName($temp);
					if($psn != null) {
						if(array_key_exists ('scientificName', $psn)) {
							$scientificName = $psn['scientificName'];
							$s = str_replace($scientificName, "", $s);
							$location = str_replace($scientificName, "", $location);
							$s = $this->removeAuthority($s, $scientificName);
							$location = $this->removeAuthority(str_replace($scientificName, "", $location), $scientificName);;
						}
						if(array_key_exists ('infraspecificEpithet', $psn)) {
							$infraspecificEpithet = $psn['infraspecificEpithet'];
							$s = str_replace($infraspecificEpithet, "", $s);
							$location = str_replace($infraspecificEpithet, "", $location);
						}
						if(array_key_exists ('taxonRank', $psn)) {
							$taxonRank = $psn['taxonRank'];
							$s = str_replace($taxonRank, "", $s);
							$location = str_replace($taxonRank, "", $location);
						}
						if(array_key_exists ('verbatimAttributes', $psn)) {
							$verbatimAttributes = $psn['verbatimAttributes'];
							$s = str_replace($verbatimAttributes, "", $s);
							$location = str_replace($verbatimAttributes, "", $location);
						}
						if(array_key_exists ('associatedTaxa', $psn)) {
							$associatedTaxa = $psn['associatedTaxa'];
							$s = str_replace($associatedTaxa, "", $s);
							$location = str_replace($associatedTaxa, "", $location);
						}
						if(array_key_exists ('substrate', $psn)) {
							$substrate = $psn['substrate'];
							$s = str_replace($substrate, "", $s);
							$location = str_replace($substrate, "", $location);
						}
					} else $scientificName = trim($temp);
					$break;
				}
			}
		}//echo "\nline 10776, location: ".$location."\nstate_province: ".$state_province."\ncounty: ".$county."\ns: ".$s."\n";
		$lines = array();
		if(strlen($location) > 0) $lines = explode("\n", $location);
		else $lines = explode("\n", $s);
		foreach($lines as $line) {//echo "\nline 10780, line: ".$line."\n";
			if(preg_match("/.*L[1Il!|][CE]H[CE]N[CE]S\\s[CE]XS[1Il!|][CE]{2}AT[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*(?:L[1Il!|]|U)[CE]H[CE]N[CE][S5]\\s[CE]X[S5][1Il!|][CE]{1,2}.?A(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*CHENE[S5] EXSI[CG]{2}AT[1Il!|](.*)/i", $line, $mats)) $line = trim($mats[1]);
			if(preg_match("/D[1Il!|]str[1Il!|]but[CE]d\\sby\\sth[CE]\\sUn[1Il!|]v[CE]rs[1Il!|]ty\\s[0O]f\\sC[0O].{2,4}ad[0O]\\sMus[CE]um,?\\sB[0O]uld[CE]r(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*Colorado\\sM[ui]seum,\\sB(.*)/i", $line, $mats)) $line = trim($mats[1]);
			else if(preg_match("/.*Colorado\\sM[ui]seum,(.*)/i", $line, $mats)) $line = trim($mats[1]);
			//echo "\nline 11727, line: ".$line."\n";
			if(preg_match("/^U\\.? ?S\\.? ?A\\.? (.+)/", $line, $mats)) {
				$country = "United States";
				$temp = trim($mats[1]);
				if(preg_match("/^(.+?[A-Za-z]{4,}\\.)(.+)/", $temp, $mats2)) {
					$temp = trim($mats2[1]);
					if(preg_match("/(.+[;:])(.+)/", $temp, $mats3)) {
						$temp = trim($mats3[1]);
						$temp2 = trim($mats3[2])." ".trim($mats2[2]);
						if(strlen($temp) > 3 && str_word_count($temp) < 4) {
							$state_province = $temp;
							$location = $temp2;//$this->mergeFields($location, $temp2);
						} else $location = $temp2;//$this->mergeFields($location, trim($mats2[1]));
					} else if(strlen($temp) > 3 && str_word_count($temp) < 4) {
						$state_province = $temp;
						$location = trim($mats2[2]);//$this->mergeFields($location, trim($mats2[2]));
					} else $location = trim($mats2[2]);//$this->mergeFields($location, $temp);
				} else $location = $temp;//$this->mergeFields($location, $temp);
				continue;
			}
			if(strlen($state_province) == 0 && strlen($county) == 0) {
				if(preg_match("/(.+), U\\.?S\\.?A\\.?:? (.+)/", $line, $mats)) {
					$country = "United States";
					if(strlen($state_province) < 3) $state_province = trim($mats[1]);
					$location = $this->mergeFields($location, trim($mats[2]));
					continue;
				} else {
					$potentialCountry = "";
					$rest = "";
					$temp = "";
					if(preg_match("/(.+):(.*)/", $line, $mats)) {
						$temp = trim($mats[1]);
						if(preg_match("/(.+?[A-Za-z]{3,})\\.(.*)/", $temp, $mats2)) {
							$temp = trim($mats2[1]);
							$rest = trim($mats2[2]).": ".ltrim(rtrim($mats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
						} else $rest = ltrim(rtrim($mats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					}
					if(strlen($temp) > 3 && str_word_count($temp) < 5) $potentialCountry = $temp;
					else $rest = "";
					if(strlen($potentialCountry) == 0) {
						if(preg_match("/(.+?[A-Za-z]{3,})\\.(.*)/", $line, $mats)) {
							$temp = trim($mats[1]);
							if(strlen($temp) > 3 && str_word_count($temp) < 5) {
								$potentialCountry = $temp;
								$rest = ltrim(rtrim($mats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							}
						}
					}
					if(strlen($potentialCountry) == 0) {
						if(preg_match("/(.+?[A-Za-z]{3,}), (.*)/", $line, $mats)) {
							$temp = trim($mats[1]);
							if(strlen($temp) > 3 && str_word_count($temp) < 5) {
								$potentialCountry = $temp;
								$rest = ltrim(rtrim($mats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							}
						}
					}
					if(strlen($potentialCountry) > 0) {
						if(strcasecmp($potentialCountry, "USSR") == 0) $country = "Russia";
						else if($this->isCountryInDatabase($potentialCountry)) $country = $potentialCountry;
						else if(preg_match("/(?:(?:NORTH(?:EAST|WEST)?|SOUTH(?:EAST|WEST)?|EAST|WEST)(?:ERN)?) (.+)/i", $potentialCountry, $mats)) {
							$potentialCountry = trim($mats[1]);
							if($this->isCountryInDatabase($potentialCountry)) $country = $potentialCountry;
						}
						if(strlen($country) > 0) {
							$temp = "";
							$rest2 = "";
							if(strcasecmp(substr($rest, 0, 9), "STATE OF ") == 0) {
								$rest = trim(substr($rest, 9));
								$pos = strpos($rest, ".");
								if($pos !== FALSE) {
									$temp = trim(substr($rest, 0, $pos));
									$rest2 = trim(substr($rest, $pos+1));
									if(strlen($temp) > 3 && str_word_count($temp) < 4) {
										$state_province = $temp;
										$location = $this->mergeFields($location, str_replace($temp, "", $rest2));
										continue;
									}
								}
							}
							if(preg_match("/(.+[;:])(.*)/", $rest, $mats)) {
								$temp = trim($mats[1]);
								if(preg_match("/(.+?[A-Za-z]{3,})\\.(.*)/", $temp, $mats2)) {
									$temp = trim($mats2[1]);
									$rest2 = trim($mats2[2])." ".ltrim(rtrim($mats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
								} else $rest2 = ltrim(rtrim($mats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							} else if(preg_match("/(.+?[A-Za-z]{4,})\\.(.*)/", $rest, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 11084, mats[".$i++."] = ".$mat."\n";
								$temp = trim($mats[1]);
								$rest2 = ltrim(rtrim($mats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
							}
							if(strlen($temp) > 3 && str_word_count($temp) < 4) {
								if(strlen($state_province) == 0) {
									$sp = $this->getStateOrProvince($temp);
									if($sp) {
										$state_province = $sp[0];
										$country = $sp[1];
										$location = $this->mergeFields($location, str_replace($temp, "", $rest2));
										continue;
									} else if(preg_match("/(?:(?:NORTH(?:EAST|WEST)?|SOUTH(?:EAST|WEST)?|EAST|WEST)(?:ERN)?) (.+)/i", $temp, $mats)) {
										$temp2 = trim($mats[1]);
										$sp = $this->getStateOrProvince($temp2);
										if($sp) {
											$state_province = $sp[0];
											$country = $sp[1];
											$location = $this->mergeFields($location, str_replace($temp, "", $rest2));
											continue;
										} else {
											$state_province = $temp;
											$location = $this->mergeFields($location, $rest2);
											continue;
										}
									} else {
										$state_province = $temp;
										$location = $this->mergeFields($location, $rest2);
										continue;
									}
								}
							} else if($this->countPotentialLocalityWords($rest) > 0) {
								$location = $this->mergeFields($location, $rest);
								continue;
							}
						} else {
							$sp = $this->getStateOrProvince($potentialCountry);
							if($sp) {
								$state_province = $sp[0];
								$country = $sp[1];
								$location = $this->mergeFields($location, str_replace($potentialCountry, "", $rest));
								continue;
							} else if(preg_match("/(?:(?:NORTH(?:EAST|WEST)?|SOUTH(?:EAST|WEST)?|EAST|WEST)(?:ERN)?) (.+)/i", $potentialCountry, $mats)) {
								$potentialCountry = trim($mats[1]);
								$sp = $this->getStateOrProvince($potentialCountry);
								if($sp) {
									$state_province = $sp[0];
									$country = $sp[1];
									$location = $this->mergeFields($location, str_replace($potentialCountry, "", $rest));
									continue;
								}
							}
						}
					}
				}
			}
			if($this->countPotentialLocalityWords($line) > 0) {
				$location = $this->mergeFields($location, $line);
				break;
			}
		}
		$temp = "";
		if(strlen($location) > 0) {//echo "\nline 10893, location: ".$location."\nhabitat: ".$habitat."\ntemp: ".$temp."\n";
			$location = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
				array("-", " ", " "),
				ltrim(rtrim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			$elevArr = $this->getElevation($location);
			$temp = '';
			$elevation = $elevArr[1];
			if(strlen($elevation) > 0) {
				$location = trim($elevArr[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-");
				$temp = ltrim(rtrim($elevArr[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-");
				if(preg_match("/^alt[,.;]{1,2}\\s(.*)/", $temp, $mats)) $temp = trim($mats[1]);
				$termLocPat = "/(.*)(?:a[s5]{2}[o0][ce][1Il!|]a[tf][ce]d\\sw[1Il!|][t+]h)\\s(.*)/is";
				if(preg_match($termLocPat, $temp, $lMats)) {
					$temp = trim($lMats[1]);
					$associatedTaxa = trim($lMats[2]);
					$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
					if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[1]);
					$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?�|".
						"T\\s?".$possibleNumbers."{1,3}\\s?[NS]\\sR\\s?".$possibleNumbers."{1,3}[EW]\\sS".$possibleNumbers."{1,3}+)\\s(.*)/s";
					if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[2]);
					if(strlen($associatedTaxa) < 6) $associatedTaxa = "";
				} else if($this->containsVerbatimAttribute($temp)) {
					$verbatimAttributes = $this->mergeFields($verbatimAttributes, $temp);
					$temp = "";
				}
				if($this->countPotentialLocalityWords($temp) > 0) $location = $this->mergeFields($location, $temp);
				else if($this->countPotentialHabitatWords($temp) > 0) $habitat = $this->mergeFields($habitat, $temp);
			}
		} else {
			$pos = stripos($scientificName, $s);
			if(strlen($scientificName) > 0 && $pos !== FALSE) $s = trim(substr($s, stripos($s, $scientificName)+strlen($scientificName)));
			$elevArr = $this->getElevation($s);
			$elevation = $elevArr[1];
			if(strlen($elevation) > 0) {
				if($pos !== FALSE) $location = preg_replace(
					array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
					array("-", " ", " "),
					trim($elevArr[0], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
				$temp = preg_replace(
					array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
					array("-", " ", " "),
					ltrim(rtrim($elevArr[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
				//if(preg_match("/^alt[,.;]{1,2}\\s(.*)/", $temp, $mats)) $temp = trim($mats[1]);
				$termLocPat = "/(.*)(?:a[s5]{2}[o0][ce][1Il!|]a[tf][ce]d\\sw[1Il!|][t+]h)\\s(.*)/is";
				if(preg_match($termLocPat, $temp, $lMats)) {
					$temp = trim($lMats[1]);
					$associatedTaxa = trim($lMats[2]);
					$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
					if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[1]);
					$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?�|".
						"T\\s?".$possibleNumbers."{1,3}\\s?[NS]\\sR\\s?".$possibleNumbers."{1,3}[EW]\\sS".$possibleNumbers."{1,3}+)\\s(.*)/s";
					if(preg_match($termLocPat, $associatedTaxa, $lMats)) $associatedTaxa = trim($lMats[2]);
					if(strlen($associatedTaxa) < 6) $associatedTaxa = "";
				} else if($this->containsVerbatimAttribute($temp)) {
					$verbatimAttributes = $this->mergeFields($verbatimAttributes, $temp);
					$temp = "";
				}
				if($this->countPotentialLocalityWords($temp) > 0) $location = $this->mergeFields($location, $temp);
				else if($this->countPotentialHabitatWords($temp) > 0) $habitat = $this->mergeFields($habitat, $temp);
			}
		}
		if(strlen($location) > 0) {//echo "\nline 10969, location: ".$location."\nhabitat: ".$habitat."\nelevation: ".$elevation."\n";
			$semiPos = strpos($location, ";");
			if($semiPos !== FALSE) {
				$temp = trim(substr($location, $semiPos+1));
				$rest = trim(substr($location, 0, $semiPos));
				$tCountH = $this->countPotentialHabitatWords($temp);
				$tCountL = $this->countPotentialLocalityWords($temp);
				$rCountH = $this->countPotentialHabitatWords($rest);
				$rCountL = $this->countPotentialLocalityWords($rest);
				//echo "\nline 10979, temp: ".$temp."\nrest: ".$rest."\ntCountH: ".$tCountH."\ntCountL: ".$tCountL."\nrCountH: ".$rCountH."\nrCountL: ".$rCountL."\n";
				if($tCountL == 0) {
					if(strcasecmp(substr($temp, 0, 3), "on ") == 0) {
						$substrate = $temp;
						$temp = "";
						if(preg_match("/(.+[A-Za-z]{3,}[.;])(.*)/", $substrate, $mats)) {
							$substrate = trim($mats[1]);
							$temp = trim($mats[2]);
							$location = $rest;
						}
					} else if($tCountH > 0) {
						$habitat = $temp;
						$location = $rest;
						$temp = "";
					}

				} else if($tCountH > $tCountL) {
					$location = $rest;
					if(strcasecmp(substr($temp, 0, 3), "on ") == 0) {
						if(preg_match("/(.+[A-Za-z]{3,})[.;](.*)/", $temp, $mats)) {
							$temp2 = trim($mats[1]);
							$rest2 = trim($mats[2]);
							if($this->countPotentialLocalityWords($temp2) == 0) {
								$substrate = $temp2;
								if($this->countPotentialHabitatWords($rest2) > $this->countPotentialLocalityWords($rest2)) $habitat = $rest2;
								else $location = $this->mergeFields($location, $rest2);
								$temp = "";
							} else $location .= " ".$temp;
						} else $location .= " ".$temp;
					} else if(preg_match("/(.+[A-Za-z]{3,})[.;](.*)/", $temp, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 1107, mats[".$i++."] = ".$mat."\n";
						$temp2 = trim($mats[1]);
						$rest2 = trim($mats[2]);
						if(strcasecmp(substr($rest2, 0, 3), "on ") == 0) {
							if($this->countPotentialLocalityWords($rest2) == 0) {
								$substrate = $rest2;
								$location .= " ".$temp2;
							} else $location .= " ".$temp;
						} else if($this->countPotentialLocalityWords($temp2) > 0) {
							$location .= " ".$temp2;
							$habitat = $rest2;
						} else if($this->countPotentialLocalityWords($rest2) > 0) {
							$location = $this->mergeFields($location, $rest2);
							$habitat = $temp2;
						} else $habitat .= " ".$temp;
					} else $habitat .= " ".$temp;
					$temp = "";
				} else if($rCountL == 0 && $rCountH > 0) {
					if(strcasecmp(substr($rest, 0, 3), "on ") == 0) $substrate = $rest;
					else $habitat = $rest;
					$location = $temp;
					$temp = "";
				}
			}//echo "\nline 11029, location: ".$location."\nhabitat: ".$habitat."\ntemp: ".$temp."\n";
			if(strlen($temp) > 6 && $this->containsVerbatimAttribute($temp)) {
				$verbatimAttributes = $temp;
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
				if(preg_match($termLocPat, $verbatimAttributes, $lMats)) $verbatimAttributes = trim($lMats[1]);
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?�|".
					"T\\s?".$possibleNumbers."{1,3}\\s?[NS]\\sR\\s?".$possibleNumbers."{1,3}[EW]\\sS".$possibleNumbers."{1,3}+)\\s(.*)/s";
				if(preg_match($termLocPat, $verbatimAttributes, $lMats)) $verbatimAttributes = trim($lMats[2]);
				if(strlen($verbatimAttributes) < 6) $verbatimAttributes = "";
			}
			if(strlen($habitat) > 0) {//echo "\nline 4602, habitat: ".$habitat."\n";
				$pos = strpos($habitat, "; on ");
				if($pos === FALSE || $pos < 6) $pos = strpos($habitat, ", on ");
				if($pos !== FALSE) {
					$temp = trim(substr($habitat, $pos+2));
					if($this->countPotentialLocalityWords($temp) == 0) {
						$substrate = $temp;
						$habitat = trim(substr($habitat, 0, $pos));
					}
				}
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
				if(preg_match($termLocPat, $habitat, $lMats)) $habitat = trim($lMats[1]);
				$termLocPat = "/(.*?)(?:\\d{1,3}+(?:\\.\\d{1,6})\\s?�|".
					"T\\.?\\s?".$possibleNumbers."{1,3}\\s?[NS],?\\sR\\.?\\s?".$possibleNumbers."{1,3}\\s?[EW],?\\sS(?:ec)?\\.?\\s?".$possibleNumbers."{1,3})\\b(.*)/s";
				if(preg_match($termLocPat, $habitat, $lMats)) $habitat = trim($lMats[1]);
				if(strlen($habitat) < 6) $habitat = "";
			}//echo "\nline 11027, location: ".$location."\nhabitat: ".$habitat."\n";
			if(strlen($location) > 0) {
				if(strcasecmp(substr($location, 0, 3), "on ") == 0 && strlen($substrate) == 0) {
					$pos = strpos($location, ", ");
					if($pos !== FALSE) {
						$temp = trim(substr($location, 0, $pos));
						if($this->countPotentialLocalityWords($temp) == 0) {
							$substrate = $temp;
							$location = trim(substr($location, $pos+2));
						}
					}
				}
				$termLocPat = "/(.*?)(?:".$possibleNumbers."{1,2}+\\s(?:".$possibleMonths.")\\s".$possibleNumbers."{4})\\s(.*)/is";
				if(preg_match($termLocPat, $location, $lMats)) $location = trim($lMats[1]);
				if(preg_match($termLocPat, $substrate, $lMats)) $substrate = trim($lMats[1]);
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
			'locality' => trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimEventDate' => $verbatimEventDate,
			'dateIdentified' => $date_identified,
			'verbatimElevation' => trim($elevation, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'verbatimAttributes' => trim($verbatimAttributes, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'associatedTaxa' => trim($associatedTaxa, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'habitat' => trim($habitat, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'identifiedBy' => trim($identified_by, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'substrate' => trim($substrate, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'infraspecificEpithet' => trim($infraspecificEpithet, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'taxonRank' => trim($taxonRank, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
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
				"/M1\in¢-/",
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
		$location = "";
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
						$location = $potentialCityOrCounty;
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
			'locality' => ucfirst(trim($location, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-")),
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

	private function doASULichenesExsiccatiLabel($s) {
		//echo "\nDoing ASULichenesExsiccatiLabel\n";
		$pattern =
			array
			(
				"/L[DO0Q]U[1Il!|][S5][1Il!|]ANA.?[S5]TAT[EFP].?UN[1Il!|]V[EFP]R[S5][1Il!|]TY.?H[EFP]RBAR[1Il!|]UM/is",
				"/\\sdet\\.\\s/i",
				"/�\\s?(\\d{1.3})[,.]W[,.]?/",
				"/�\\s?(\\d{1.3})\\s?'\\s?(\\d{1.3})\\s?W[,.]?/",
				"/[DOQ0][l1|I!]str[l1|I!]but[ec]d.?b[ygq].?Ar[l1|I!]z[o0]na.?Stat[ec].?Un[l1|I!]v[ec]rs[l1|I!]t./i",
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
				"",
				"\nDet. ",
				"�\${1}'W,",
				"�\${1}'\${2}\"W,",
				"",
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
		//echo "\nline 5486, s: ".$s."\n\n";
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
		$recordedBy = "";
		$recordNumber = "";
		$associatedTaxa = "";
		$associatedCollectors = "";
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
			$location = $temp = preg_replace(
				array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m", "/�\\s?(\\d{1,3})\\s?'\\s?(\\d{1,3})\\s?W[,.]?/"),
				array("-", " ", " ", "�\${1}'\${2}\"W,"),
				ltrim(rtrim($countyMatches[3], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,.:;!\"\'\\~@#$%^&*_-"));
			$county = trim($countyMatches[1]);
			$country = trim($countyMatches[2]);
			$sp = $this->getStateOrProvince(trim($countyMatches[4]));
			if(count($sp) > 0) {
				$state_province = $sp[0];
				$country = $sp[1];
			}
			//echo "\nline 11203, firstPart: ".$firstPart."\nlocation: ".$location."\ncounty: ".$county."\nstate_province: ".$state_province."\n";
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
			if(preg_match("/.*(?:L[1Il!|]|IZ|U|X)(?:[CE]H|QI)[CE][NH][CE]S\\s[CE]XS[1Il!|][CE]{2}AT[1Il!|X]\\sA\\.?S\\.?U\\.?\\sNo\\.\\s?(".$possibleNumbers."{1,3})/i", $line, $numMats)) {
				$exsnumber = $this->replaceMistakenNumbers(trim($numMats[1]));
				continue;
			} else if(preg_match("/(?:L[1Il!|]|U)[CE]H[CE]N[CE][S5]\\s[CE]X[S5][1Il!|][CE]{1,2}.?A.{1,6}A\\.?S\\.?U\\.?\\sNo\\.\\s?(".$possibleNumbers."{1,3})/i", $line, $numMats)) {
				$exsnumber = $this->replaceMistakenNumbers(trim($numMats[1]));
				continue;
			} else if(preg_match("/.*CHENE[S5] EXSICCAT[1Il!|].{1,3}A\\.?S\\.?U\\.?\\sNo\\.\\s?(".$possibleNumbers."{1,3})/i", $line, $numMats)) {
				$exsnumber = $this->replaceMistakenNumbers(trim($numMats[1]));
				continue;
			}

			if(stripos($line, " distribut") !== FALSE ||
				stripos($line, " publish") !== FALSE ||
				stripos($line, " Univers") !== FALSE) continue;
			$line = trim($line);
			//echo "\nline 11280, line: ".$line."\n";
			if(!$foundSciName) {
				if
				(
					preg_match("/^.??([][OQSZlU|I!0-9&]{1,3})[.,_]?+\\s(.*)/", $line, $mats) &&
					strlen($line) > 6 &&
					!$this->isMostlyGarbage($line, 0.60) &&
					!preg_match("/^C[0o][1Il!|]{2}.*/", $line)
				) {
					if(strlen($exsnumber) == 0) $exsnumber = $this->replaceMistakenNumbers(trim($mats[1]));
					$line = trim($mats[2]);
				}
				$psn = $this->processSciName($line);
				if($psn != null) {
					if(array_key_exists ('scientificName', $psn)) {
						$scientificName = $psn['scientificName'];
						$pos = stripos($line, $scientificName);
						if($pos !== FALSE) $line = substr($line, $pos+strlen($scientificName));
						$foundSciName = true;
					}
					if(array_key_exists ('infraspecificEpithet', $psn)) {
						$infraspecificEpithet = $psn['infraspecificEpithet'];
						$pos = stripos($line, $infraspecificEpithet);
						if($pos !== FALSE) $line = substr($line, $pos+strlen($infraspecificEpithet));
					}
					if(array_key_exists ('taxonRank', $psn)) $taxonRank = $psn['taxonRank'];
					if(array_key_exists ('verbatimAttributes', $psn)) {
						$verbatimAttributes = $psn['verbatimAttributes'];
						$pos = stripos($line, $verbatimAttributes);
						if($pos !== FALSE) $line = substr($line, $pos+strlen($verbatimAttributes));
					}
					if(array_key_exists ('associatedTaxa', $psn)) {
						$associatedTaxa = $psn['associatedTaxa'];
						$pos = stripos($line, $associatedTaxa);
						if($pos !== FALSE) $line = substr($line, $pos+strlen($associatedTaxa));
					}
					if(array_key_exists ('substrate', $psn)) {
						$substrate = $psn['substrate'];
						//if(stripos($habitat, $substrate) === FALSE) $habitat = $substrate." ".$habitat;
						$pos = stripos($line, $substrate);
						if($pos !== FALSE) $line = substr($line, $pos+strlen($substrate));
					}
				}
			}
			if($this->countPotentialLocalityWords($line) == 0) {
				if(strcasecmp(substr($line, 0, 3), "On ") == 0) {
					$substrate = $this->mergeFields($substrate, $line);
					continue;
				} else if($this->countPotentialHabitatWords($line) > 0) {
					$habitat = $this->mergeFields($habitat, $line, " ");
					continue;
				}
			}
			if(strlen($state_province) == 0 && strlen($county) == 0) {
				if(preg_match("/.{0,2}\\bU\\.?\\s?[S5]\\.?\\s?A[,.]?(.*)/i", $line, $cMats)) {
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
				} else if(preg_match("/.{0,2}\\bMEX[I!1l|]C[Oo][:;,.]?(.*)/i", $line, $cMats)) {
					$country = "MEXICO";
					$rest = trim($cMats[1]);
					$pos = strpos($rest, ":");
					if($pos !== FALSE) {
						$state_province = trim(substr($rest, 0, $pos));
						if(strcasecmp(substr($state_province, 0, 9), "ESTADO DE") == 0) $state_province = trim(substr($state_province, 10));
						$location = $this->mergeFields($location, trim(substr($rest, $pos+1)));
					}
					//if($dotPos !== FALSE && $dotPos > 0) {
					//	$state_province = trim(substr($rest, 0, $dotPos));
					//	$potentialCounty = trim(substr($rest, $dotPos+1));
					//	$cs = $this->getCounty($potentialCounty);
					//	if($cs != null) $county = $potentialCounty;
					//	if(strlen($county) > 0) $location = ltrim(rtrim(substr($s, strpos($s, $county)+strlen($county)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					//	else $location = ltrim(rtrim(substr($s, strpos($s, $state_province)+strlen($state_province)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					//} else $location = ltrim(rtrim(substr($s, strpos($s, $rest)), " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"), " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
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
			} else if($this->countPotentialLocalityWords($line) > 0) {
				$location = $this->mergeFields($location, $line);
				break;
			}
		}
		if(strlen($location) > 0) {//echo "\nline 11400, location: ".$location."\nhabitat: ".$habitat."\n";
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
			if(strlen($location) > 0) {//echo "\nline 11412, location: ".$location."\nhabitat: ".$habitat."\n";
				$collectorInfo = $this->getCollector($location);
				if($collectorInfo != null) {
					if(array_key_exists('collectorName', $collectorInfo)) {
						$recordedBy = $collectorInfo['collectorName'];
						if(strlen($recordedBy) > 0 && preg_match("/(.*)".preg_quote($recordedBy, '/')."(.*)/is", $location, $mats)) $location = trim($mats[1])." ".trim($mats[2]);
						if(array_key_exists('collectorNum', $collectorInfo)) {
							$recordNumber = $collectorInfo['collectorNum'];
							if(strlen($recordNumber) > 0 && preg_match("/(.*)".preg_quote($recordNumber, '/')."(.*)/is", $location, $mats)) $location = trim($mats[1])." ".trim($mats[2]);
							if(stripos($s, "Deposited at NY in ".$recordNumber) !== FALSE) $recordNumber = "";
						}
						if(array_key_exists('identifiedBy', $collectorInfo) && strlen($identified_by) == 0) {
							$identified_by = $collectorInfo['identifiedBy'];
							if(strlen($identified_by) > 0 && preg_match("/(.*)".preg_quote($identified_by, '/')."(.*)/is", $location, $mats)) $location = trim($mats[1])." ".trim($mats[2]);
						}
						if(array_key_exists('associatedCollectors', $collectorInfo) && strlen($associatedCollectors) == 0) {
							$associatedCollectors = $collectorInfo['associatedCollectors'];
							if(strlen($associatedCollectors) > 0 && preg_match("/(.*)".preg_quote($associatedCollectors, '/')."(.*)/is", $location, $mats)) $location = trim($mats[1])." ".trim($mats[2]);
						}
					}
				}
				if(preg_match("/(.*?)(?:".$possibleNumbers."{1,3}+(?:\\.".$possibleNumbers."{1,6})?\\s?�)(.*)/", $location, $lMats)) {
					$location = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					$temp = preg_replace(
						array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
						array("", " ", " "),
						trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					if(preg_match("/".$possibleNumbers." ?�[EW], (.*)/", $temp, $mats)) $temp = trim($mats[1]);
					else if(preg_match("/".$possibleNumbers." ?� ?".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,3})?['Il] ?[EW], (.*)/", $temp, $mats)) $temp = trim($mats[1]);
					else if(preg_match("/".$possibleNumbers." ?� ?".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,3})?['Il] ?".$possibleNumbers."{1,3}[EW], (.*)/", $temp, $mats)) $temp = trim($mats[1]);
					if(strcasecmp($temp, "on") == 0 && strlen($habitat) > 0 && strlen($substrate) == 0 && strcasecmp(substr($habitat, 0, 3), "on ") != 0) {
						$substrate = "On ".$habitat;
						$habitat = "";
					} else $habitat = $this->mergeFields($habitat, $temp);
				} else {
					$pat = "/(.*?)(?:".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3}\\s?'[NS],\\s?".$possibleNumbers."{1,3}0".$possibleNumbers."{1,3})\\s?'[EW](.*)/";
					if(preg_match($pat, $location, $lMats)) {
						$location = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[1], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
						$temp = preg_replace(
							array("/-[\r\n]{1,2}/m", "/[\r\n]/m", "/\\s{2,}/m"),
							array("", " ", " "),
							trim($lMats[2], " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"));
					}
					if(preg_match("/".$possibleNumbers." ?�[EW], (.*)/", $temp, $mats)) $temp = trim($mats[1]);
					else if(preg_match("/".$possibleNumbers." ?� ?".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,3})?['Il] ?[EW], (.*)/", $temp, $mats)) $temp = trim($mats[1]);
					else if(preg_match("/".$possibleNumbers." ?� ?".$possibleNumbers."{1,3}(?:".$possibleNumbers."{1,3})?['Il] ?".$possibleNumbers."{1,3}[EW], (.*)/", $temp, $mats)) $temp = trim($mats[1]);
					if(strcasecmp($temp, "on") == 0 && strlen($habitat) > 0 && strlen($substrate) == 0 && strcasecmp(substr($habitat, 0, 3), "on ") != 0) {
						$substrate = "On ".$habitat;
						$habitat = "";
					} else $habitat = $this->mergeFields($habitat, $temp);
				}

				$lPat = "/.*(?:".$possibleNumbers."{1,3}+\\s?�\\s?".$possibleNumbers."{1,3}+\\s?'\\s?(?:".$possibleNumbers."{1,3}+\\s?\")?\\s?[EW])(.*)/";
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
				if(strlen($recordedBy) == 0) {
					$collectorInfo = $this->getCollector($habitat);
					if($collectorInfo != null) {//foreach($collectorInfo as $k => $v) echo "\n".$k.": ".$v."\n";
						if(array_key_exists('collectorName', $collectorInfo)) {
							$recordedBy = $collectorInfo['collectorName'];
							if(preg_match("/(.*)".str_replace("/", "\/", $recordedBy)."(.*)/i", $habitat, $mats)) $location = trim($mats[1])." ".trim($mats[2]);
							if(array_key_exists('collectorNum', $collectorInfo)) {
								$recordNumber = $collectorInfo['collectorNum'];
								if(preg_match("/(.*)".str_replace("/", "\/", $recordNumber)."(.*)/i", $habitat, $mats)) $habitat = trim($mats[1])." ".trim($mats[2]);
								if(stripos($s, "Deposited at NY in ".$recordNumber) !== FALSE) $recordNumber = "";
							}
							if(array_key_exists('identifiedBy', $collectorInfo) && strlen($identified_by) == 0) {
								$identified_by = $collectorInfo['identifiedBy'];
								if(preg_match("/(.*)".str_replace("/", "\/", $identified_by)."(.*)/i", $habitat, $mats)) $habitat = trim($mats[1])." ".trim($mats[2]);
							}
							if(array_key_exists('associatedCollectors', $collectorInfo) && strlen($associatedCollectors) == 0) {
								$associatedCollectors = $collectorInfo['associatedCollectors'];
								if(strlen($associatedCollectors) > 0 && preg_match("/(.*)".preg_quote($associatedCollectors, '/')."(.*)/is", $habitat, $mats)) $habitat = trim($mats[1])." ".trim($mats[2]);
							}
						}
					}
				}
				if(preg_match("/(.*)C[o0][1Il!|]{2}/i", $habitat, $mats)) $habitat = trim($mats[1]);
				if(strlen($habitat) > 0) {
					$onPos = stripos($habitat, "on ");
					if($onPos !== FALSE && $onPos == 0) {
						if(preg_match("/(.+)[A-Za-z]{3,}[,.] (.+)/", $habitat, $mats)) {
							$substrate = trim($mats[1]);
							$temp = trim($mats[2]);
							if(strlen($temp) > 0 && $this->countPotentialHabitatWords($temp) > 0) $habitat = $temp;
						} else {
							$substrate = $habitat;
							$habitat = "";
						}
					} else {
						$onPos = stripos($habitat, ". on ");
						if($onPos !== FALSE) {
							$substrate = trim(substr($habitat, $onPos+2));
							$temp = trim(substr($habitat, 0, $onPos)).".";
							if($this->countPotentialHabitatWords($temp) > 0) $habitat = $temp;
						}
					}
					$habitat = trim($habitat, " \t\n\r\0\x0B,:;.!\"\'\\~@#$%^&*_-");
					if(preg_match("/\(([A-Za-z ]*)\)/", $habitat, $mats)) {
						$temp = trim($mats[1]);
						if($this->containsVerbatimAttribute($temp)) {
							$verbatimAttributes = $temp;
							$habitat = "";
						}
					}
				}
			}
			if(strlen($location) > 0) {
				if(preg_match("/(.*)C[o0][1Il!|]{2}/i", $location, $mats)) $location = trim($mats[1]);
			}
		}//echo "\nline 11429, habitat: ".$habitat."\nrecordedBy: ".$recordedBy."\n";
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
			'recordNumber' => trim($recordNumber, " \t\n\r\0\x0B,:;!\"\'\\~@#$%^&*_-"),
			'ometid' => "93",
			'exsnumber' => $exsnumber,
			'associatedCollectors' => $associatedCollectors
		);
	}

	private function doHasseLichenesExsiccatiLabel($s) {
		//echo "\nDoing HasseLichenesExsiccatiLabel\n";
		$fields = array();

		if(preg_match("/Lichen F[1Il!|][0o]ra [0o]f Southern Ca[1Il!|]{2}fornia/i", $s)) {
			$fields['stateProvince'] = "California";
			$fields['country'] = "USA";
		}
		$pattern =
			array
			(
				"/H.{4,6}[1Il!|]UM\\s[0O]F\\s[CG][1Il!|]A[D0O][YV][S5] [PF]. ANDERS[0O]N/i",
				"/Lichen F[1Il!|][0o]ra [0o]f Southern Ca[1Il!|]{2}fornia/i",
				"/\\n.{0,3}ex herbari[0o] Dr. H. [EB]. HASSE, relicti/i",
				"/Distributed for the Sullivant M[0o][fs5]{2} S[0o][ce]iet.{1,2}/i",
				"/\\nUniversity [0o]f Michigan Fungus C[0o][1Il!|]{2}ecti[0o]n/i",
				"/,,/i",
				"/\\.\\./i"
			);
		$replacement =
			array
			(
				"",
				"",
				"",
				"",
				"",
				",",
				"."
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		return $this->doGenericLabel($s, "92", $fields);
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
		$s = trim(str_ireplace(array(" habitat ", "Stephen & Sandra Talbot"), array("\nhabitat ", "Stephen Talbot & Sandra Talbot"), $s));
		//echo "\nline 12668, s:\n".$s."\n";
		$state_province = "Alaska";
		$identifiedBy = '';
		$dateIdentified = array();
		$country = "USA";
		$substrate = '';
		$scientificName = '';
		$recordNumber = "";
		$recordedById = "";
		$associatedCollectors = "";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = str_replace(" . ", ", ", $collectorInfo['collectorName']);
			if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
			if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
			if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
			if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
		}
		$location = trim($this->getLocality($s));
		$patStr = "/(.*)(?:L|(?:I?\\.))at[li1!|]tude/i";
		if(preg_match($patStr, $location, $mat)) $location = $mat[1];
		$habitat = '';
		$habitatArray = $this->getHabitat($s);
		if($habitatArray != null && count($habitatArray) > 0) {
			$habitat = trim($habitatArray[1]." ".$habitatArray[2]);
			if(strlen($habitat) > 0) {
				$patStr = "/(.*)[QO0]ua[ad]\\.?\\s[MH]ap/is";
				if(preg_match($patStr, $habitat, $mat)) $habitat = $mat[1];
				if(preg_match("/^([0GQO]n\\s.+)/i", $habitat, $mat)) {
					$substrate = $this->terminateSubstrate($mat[1]);
					$habitat = trim(str_ireplace($substrate, "", $habitat));
				} else {
					$patStr = "/^((?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant) |Found |Loose |Epi(?:phyt|lith|xyl)ic |Xylicolous )?[0GQO]n\\s.+)/i";
					if(preg_match($patStr, $habitat, $mat)) {
						$substrate = $this->terminateSubstrate($mat[1]);
						$habitat = trim(str_ireplace($substrate, "", $habitat));
					}
				}
			}
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		//Occasionally the habitats and locations are confused in the output so check
		$possibleNumbers = "[OQSZl|I!0-9]";
		if(strlen($location) == 0 && strlen($habitat) > 0) {
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?�.*\\d[\"\']\\s?[EW](.*)/is";
			if(preg_match($patStr, $habitat, $mat)) {
				$location = $mat[1];
				$habitat = $mat[2];
			}
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?�/is";
			while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
			$patStr = "/\\bE[l1!I][ec]vat[l1!I][o0]n:?\\s".$elevation."(.*)/is";
			if(preg_match($patStr, $habitat, $mat)) $habitat = trim($mat[1]);
		}
		$patStr = "/(.*)\\bE[l1!I][ec]vat[l1!I][o0]n:?\\s".$elevation."/is";
		if(preg_match($patStr, $habitat, $mat)) $habitat = trim($mat[1]);
		$patStr = "/(.*)(?:L|(?:\|\_))at[li1!|]tude/i";
		if(preg_match($patStr, $location, $mat)) $location = $mat[1];
		$georeferenceRemarks = '';
		if(preg_match("/\\b[QO0]ua[ad]\\.?\\s(?:M|H|IX|[Jfli1!|]{2})ap(.+)/i", $s, $mat)) {
			$georeferenceRemarks = "Quad Map: ".trim($mat[1], " :;,");
			if(strpos($georeferenceRemarks, ",") !== FALSE) $georeferenceRemarks = substr($georeferenceRemarks, 0, strpos($georeferenceRemarks, ","));
			else if(preg_match("/(.*)C[o0][li1!|]{1,2}\\.?\\sDate/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = trim($mat[1]);
			else if(preg_match("/(.*)C[o0][li1!|]{1,2}[.:;,]/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = trim($mat[1]);
		} else if(preg_match("/\\b(?:M|H|IX|[Jfli1!|]{2})ap\/[QO0G]ua[ad][;:., ]{1,3}(.+)/i", $s, $mats)) $georeferenceRemarks = trim($mats[1]);
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
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
			'dateIdentified' => $dateIdentified,
			'recordNumber' => $recordNumber,
			'recordedById' => $recordedById,
			'associatedCollectors' => $associatedCollectors
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
		$pattern =
			array
			(
				"/ (Co[1Il!|]{2}ectors?[,.:]? .*)/i",
				"/ (Det\\. .*)/",
				"/\\sby\\s\?\n/",
				"/S(?:[,.]|tephen)? ?(?:S\\.? ?)?& S(?:[,.]|andra)? ?(?:L[,.]? ?)?Talbot/i"
			);
			$replacement =
			array
			(
				"\n\${1}",
				"\n\${1}",
				" by ",
				"Stephen Talbot & Sandra Talbot"
			);
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 12843, s:\n".$s."\n";
		$state_province = "Alaska";
		$identifiedBy = '';
		$dateIdentified = array();
		$country = "USA";
		$substrate = '';
		$recordNumber = "";
		$recordedBy = "";
		$recordedById = "";
		$associatedCollectors = "";
		$collectorInfo = $this->getCollector($s);
		if($collectorInfo != null) {
			if(array_key_exists('collectorName', $collectorInfo)) $recordedBy = str_replace(" . ", ", ", $collectorInfo['collectorName']);
			if(array_key_exists('collectorNum', $collectorInfo)) $recordNumber = $collectorInfo['collectorNum'];
			if(array_key_exists('collectorID', $collectorInfo)) $recordedById = $collectorInfo['collectorID'];
			if(array_key_exists('identifiedBy', $collectorInfo)) $identifiedBy = $collectorInfo['identifiedBy'];
			if(array_key_exists('otherCatalogNumbers', $collectorInfo)) $otherCatalogNumbers = $collectorInfo['otherCatalogNumbers'];
			if(array_key_exists('associatedCollectors', $collectorInfo)) $associatedCollectors = $collectorInfo['associatedCollectors'];
			if(strlen($recordedById) > 0) {
				if(preg_match("/ Talbot\\b/i", $s, $mats)) {
					$recordedById = "";
					$recordedBy = "Stephen Talbot";
				}
			}
		}
		$location = trim($this->getLocality($s));
		$patStr = "/(.*)\\b[HM]ab[li1!|]tat/is";
		if(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?�/is";
		while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		$habitat = '';
		$habitatArray = $this->getHabitat($s);
		if($habitatArray != null && count($habitatArray) > 0) {
			$habitat = $habitatArray[1];
			$habitatArray2 = $habitatArray[2];
			$lPos = strpos($habitatArray2, "\n");
			if($lPos !== FALSE) {
				$habitatArray2 = trim(substr($habitatArray2, 0, $lPos));
				$habitatArray22 = trim(substr($habitatArray2, $lPos+1));
				if(strlen($habitatArray2) > 2 && !preg_match("/^(?:E[li1!|]evation|Co[li1!|]{2}ectors?)[;:].+/", $habitatArray2)) $habitat .= " ".$habitatArray2;
				if($this->countPotentialHabitatWords($habitatArray22) > 0 && !preg_match("/^(?:E[li1!|]evation|Co[li1!|]{2}ectors?)[;:].+/", $habitatArray22)) $habitat .= " ".$habitatArray22;
			} else if(strlen($habitatArray2) > 2 && !preg_match("/^(?:E[li1!|]evation|Co[li1!|]{2}ectors?)[;:].+/", $habitatArray2)) $habitat .= " ".$habitatArray2;
			$patStr = "/(.*)(?:E[lI1!|][ec](?:va|c)t[li1!|]on[;:]|C[o0][lI1!|]{2}(?:[ec]{2}t[o0]r)?[;:]|[QO0]uad)/s";
			if(preg_match($patStr, $habitat, $mat))  $habitat = $mat[1];
		}
		//Occasionally the habitats and locations are confused in the output so check
		$possibleNumbers = "[OQSZl|I!0-9]";
		if(strlen($location) == 0) {//echo "\nline 2617\n";
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?�.*\\d[\"\']\\s?[EW](.*)/is";
			if(preg_match($patStr, $habitat, $mat)) {
				$location = $mat[1];
				$habitat = $mat[2];
			}
			$patStr = "/(.*)\\b(?:\\d{1,3}(?:\\.\\d{1,7})?)\\s?�/is";
			while(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
			$patStr = "/\\bT(?:\\.|wnshp.?|ownship)?\\s?(?:".$possibleNumbers."{1,3})\\s?[NS]\\.?,?(?:\\s|\\n|\\r\\n)R(?:\\.|ange)?\\s?".
				"(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})\\b(.+)/is";
			if(preg_match($patStr, $habitat, $mat)) $habitat = trim($mat[1]);
		} else {
			$patStr = "/(.*)\\bT\\s?(?:".$possibleNumbers."{1,3})\\s?[NS]\\.?,?(?:\\s|\\n|\\r\\n)R\\s?".
				"(?:".$possibleNumbers."{1,3}\\s?[EW])\\.?,?(?:\\s|\\n|\\r\\n)[S5](?:\\.|ect?\\.?|ection)?\\s?(?:".$possibleNumbers."{1,3})/is";
			if(preg_match($patStr, $location, $mat)) $location = trim($mat[1]);
		}
		if(preg_match("/^([0GQO]n\\s.+)/i", $habitat, $mat)) {
			$substrate = $this->terminateSubstrate($mat[1]);
			$habitat = trim(str_ireplace($substrate, "", $habitat));
		} else {
			$patStr = "/^((?:(?:Fairly |Quite |Very |Not )?(?:(?:Un)?Common|Abundant) |Found |Loose |Epi(?:phyt|lith|xyl)ic |Xylicolous )?[0GQO]n\\s.+)/i";
			if(preg_match($patStr, $habitat, $mat)) {
				$substrate = $this->terminateSubstrate($mat[1]);
				$habitat = trim(str_ireplace($substrate, "", $habitat));
			}
		}
		$elevation = '';
		$elevationArray = $this->getElevation($s);
		if($elevationArray != null && count($elevationArray) > 0) $elevation = $elevationArray[1];
		$georeferenceRemarks = '';
		if(preg_match("/\\b(?:M|H|IX|[Jfli1!|]{2})ap\/[QO0G]ua[ad][;:., ]{1,3}(.+)/i", $s, $mat)) {
			$georeferenceRemarks = "Quad Map: ".trim($mat[1], " :;.");
			if(strpos($georeferenceRemarks, ",") !== FALSE) $georeferenceRemarks = substr($georeferenceRemarks, 0, strpos($georeferenceRemarks, ","));
			else {
				if(preg_match("/(.*)[0OGD]uad/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
				else if(preg_match("/(.*)[0OD]at[ec]/i", $georeferenceRemarks, $mat)) $georeferenceRemarks = $mat[1];
			}
		} else if(preg_match("/(.{3,} Quad).+/", $s, $mats)) $georeferenceRemarks = trim($mats[1]);
		$possibleMonths = "Jan(?:\\.|(?:uary))?|Feb(?:\\.|(?:ruary))?|Mar(?:\\.|(?:ch))|Apr(?:\\.|(?:il))?|May|Jun[.e]?|Jul[.y]?|Aug(?:\\.|(?:ust))?|Sep(?:\\.|(?:t\\.?)|(?:tember))?|Oct(?:\\.|(?:ober))?|Nov(?:\\.|(?:ember))?|Dec(?:\\.|(?:ember))?";
		$identifier = $this->getIdentifier($s, $possibleMonths);
		if($identifier != null) {
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
			'dateIdentified' => $dateIdentified,
			'recordedBy' => $recordedBy,
			'recordNumber' => $recordNumber,
			'recordedById' => $recordedById,
			'associatedCollectors' => $associatedCollectors
		);
	}

	private function doVezdaLichenesSelectiExsiccatiLabel($s) {
		$pattern =
			array
			(
				"/IkLO\\. /",
				"/410\\.- /",
				"/A\\. VE ?ZD ?A[:;] (?:U|[1Il!|]{2})CHENES SELECT[1Il!|] EXS[1Il!|][CG]{2}AT[1Il!|] ?/i",
				"/\\bA.?n.?[nm].?o.?t\\.: \"?�?/i"
			);
		$replacement =
			array
			(
				"410. ",
				"410. ",
				"",
				""
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		return $this->doGenericLabel($s, "101");
	}

	private function doLichenesWisconsinensesExsiccatiLabel($s) {
		$pattern =
			array
			(
				"/(?:[1Il!|]{2}|U)[CE]H[CE]N[CE][S5]\\sW[1Il!|][S5][CE]ON[S58][1Il!|]N[CE]N[S5]E[S5]\\sExs[1Il!|]ccat[1Il!|]? ?/is",
				"/\\nNo\\. 81\|\\. /",
				"/sub.{1,2}ariosa/i"
			);
		$replacement =
			array
			(
				"",
				"\nNo. 84. ",
				"subcariosa"
			);
		$fields = array();
		$fields['country'] = "U.S.A.";
		$fields['stateProvince'] = "Wisconsin";
		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		//echo "\nline 13565, s:\n".$s."\n";
		return $this->doGenericLabel($s, "335", $fields);
	}

	private function doLichenesRarioresEtCriticiExsiccatiLabel($s) {
		$pattern =
			array
			(
				"/(?:Syo Kurokawa:|S\\. Kurokawa and H. Kashiwadani)\\s(?:[1Il!|]{2}|U)CHENE[S5] RAR[1Il!|][O0Q]RE[S5] ET CR[1Il!|]T[1Il!|]C[1Il!|] Exs[1Il!|]ccat[1Il!|]? ?/is",
				"/([^ ]\))- ?On /i",
				"/\\\/",
				"/(?<!JAPAN\\.|:) (on|among) (.+\\n)/i",
				"/U\\.S\\.A\\. /",
				"/URUGAY/i"
			);
		$replacement =
			array
			(
				"",
				"\${1}\nOn ",
				"",
				$this->countPotentialLocalityWords("\${2}") == 0? "\n\${1} \${2}": " \${1} \${2}",
				"USA. ",
				"URUGUAY"
			);

		$s = trim(preg_replace($pattern, $replacement, $s, -1));
		$lines = explode("\n", $s);
		$state_province = "";
		$country = "";
		$county = "";
		$location = "";
		$habitat = "";
		$substrate = "";
		$fields = array();
		$lookAtNextLine = false;
		foreach($lines as $line) {//echo "\nline 13293, line: ".$line."\nlookAtNextLine: ".$lookAtNextLine."\n";
			$line = trim($line);
			if(preg_match("/^([^ )]{1,5}\))(.*)$/", $line, $mats)) {
				$line = trim($mats[2]);
				$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats[1]), '/')."/", "", $s, -1)));
			}
			if(preg_match("/^(.*) (\([^ (]+)$/", $line, $mats)) {
				$line = trim($mats[1]);
				$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats[2]), '/')."/", "", $s, -1)));
			}//echo "\nline 13302, line: ".$line."\nlookAtNextLine: ".$lookAtNextLine."\n";
			if(preg_match("/^(?:[^A-Za-z ]{1,3}+ )?([A-Za-z]{3,}(?: [A-Za-z]{3,}(?: [A-Za-z]{3,})?)?)\\. (.+)/", $line, $mats)) {//$i=0;foreach($mats as $mat) echo "\nline 13303, mats[".$i++."] = ".$mat."\n";
				$temp = trim($mats[1]);
				$rest = trim($mats[2]);
				if($this->isCountryInDatabase($temp)) {
					$country = $temp;
					$s = trim(preg_replace("/".$country."\\. /", "", $s));//echo "\nline 13530, s: ".$s."\n";
					if(strcasecmp($country, "JAPAN") == 0) {
						if(preg_match("/([A-Za-z]+(?: [A-Za-z]{3,})?)\\. Prov\\. ([A-Za-z]{3,})\\b:? ?(.*)/", $rest, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13535, mats2[".$i++."] = ".$mat2."\n";
							$island = trim($mats2[1]);
							$state_province = trim($mats2[2]);
							$temp = trim($mats2[3]);
							$lookAtNextLine = true;
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
							if(preg_match("/^\(.{1,8}\):? ?(.+)$/", $temp, $mats3)) $temp = trim($mats3[1]);
							$location = trim($island." Island: ".$temp);
							if(preg_match("/^ ?:(.+)/", $rest, $mats3)) $rest = trim($mats3[1]);
							$location = trim(preg_replace("/:[^(]+\):/", ":", trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\):?/", "", $location, -1), " :")))));
						} else if(preg_match("/Prov\\. (\\w{3,})\\b ?(.*)/", $rest, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13561, mats2[".$i++."] = ".$mat2."\n";
							$state_province = trim($mats2[1]);
							$s = trim(preg_replace("/Prov\\. ".preg_quote($state_province, '/')."/", "", $s));
							$lookAtNextLine = true;
							$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", trim($mats2[2]), -1), " :")));
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats2[2]), '/')."/", "", $s, -1)));
						} else if(strcasecmp(substr($rest, 0, 3), "on ") !== 0 && ($this->countPotentialHabitatWords($rest) == 0 || $this->countPotentialLocalityWords($rest) > 0)) {
							$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $rest, -1), " :.")));
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
						}
					} else if(strcasecmp($country, "USA") == 0) {
						if(preg_match("/^([A-Za-z]{3,}(?: [A-Za-z]{3,})?): ?(.*)/", $rest, $mats2)) {
							$state_province = trim($mats2[1]);
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($state_province, '/')."/", "", $s, -1)));
							$rest = trim($mats2[2], " :");
							if(preg_match("/(.+) Co\\..*/", $rest, $mats3)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13535, mats2[".$i++."] = ".$mat2."\n";
								$firstPart = trim($mats3[1]);
								$pos = strrpos($firstPart, ",");
								if($pos !== FALSE) {
									$county = trim(substr($firstPart, $pos+1));
									$location = trim(substr($firstPart, 0, $pos));
									$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($firstPart, '/')."/", "", $s, -1)));
								}
							} else {
								$location = $rest;
								$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
							}
						} else if(preg_match("/Prov\\. (\\w{3,})\\b ?(.*)/", $rest, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13561, mats2[".$i++."] = ".$mat2."\n";
							$state_province = trim($mats2[1]);
							$s = trim(preg_replace("/Prov\\. ".preg_quote($state_province, '/')."/", "", $s));
							$lookAtNextLine = true;
							$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", trim($mats2[2]), -1), " :")));
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats2[2]), '/')."/", "", $s, -1)));
						} else if(strcasecmp(substr($rest, 0, 3), "on ") !== 0 && ($this->countPotentialHabitatWords($rest) == 0 || $this->countPotentialLocalityWords($rest) > 0)) {
							$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $rest, -1), " :.")));
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
						}
					} else if(preg_match("/Prov\\. (\\w{3,})\\b ?(.*)/", $rest, $mats2)) {
						$lookAtNextLine = true;
						$state_province = trim($mats2[1]);
						$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", trim($mats2[2]), -1), " :.")));
						$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
					} else if(preg_match("/Department of (\\w{3,})[;:.] ?(.*)/", $rest, $mats2)) {
						$lookAtNextLine = true;
						$state_province = trim($mats2[1]);
						$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", trim($mats2[2]), -1), " :.")));
						$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
					} else if(preg_match("/^([A-Za-z]{3,}(?: [A-Za-z]{3,})??(?: [A-Za-z]{3,})??)(?: District)?: ?(.*)/", $rest, $mats2)) {
						$lookAtNextLine = true;
						$state_province = trim($mats2[1]);
						$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", trim($mats2[2]), -1), " :.")));
						$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
					} else {
						$location = trim(preg_replace("/ ([.,;:])/", "\${1}", $rest, " :."));
						$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($rest, '/')."/", "", $s, -1)));
					}
				} else if(preg_match("/Prov\\. (\\w{3,})\\b ?(.*)/", $line, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13590, mats2[".$i++."] = ".$mat2."\n";
					$state_province = trim($mats2[1]);
					$location = trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", trim($mats2[2]), -1), " :")));
					$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($line, '/')."/", "", $s, -1)));
					break;
				} else if ($lookAtNextLine) {
					//$lookAtNextLine = false;
					if(preg_match("/^Co[l1I!|]{2}[;:,.]/", $line) || preg_match("/^Det[;:,.] /", $line) || preg_match("/Un[i1!l]vers[i1!l]t/i", $line)) break;
					else {
						if(preg_match("/^((Among .+ )(on .+?))[;,.]/i", $line, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13379, mats2[".$i++."] = ".$mat2."\n";
							$habitat = trim($mats2[2]);
							$substrate = trim($mats2[3]);
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats2[1]), '/')."/", "", $s, -1)));
						} else if(preg_match("/(.*) ((Among .+ )(on .+?))[;,.]/i", $line, $mats2)) {
							$temp = trim($mats2[1]);
							if($this->countPotentialHabitatWords($temp) == 0 || $this->countPotentialLocalityWords($temp) > 0) {
								if(preg_match("/-$/", $location)) $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))));
								else $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))), " ");
								$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($temp, '/')."/", "", $s, -1)));
							}
							$habitat = trim($mats2[3]);
							$substrate = trim($mats2[4]);
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats2[2]), '/')."/", "", $s, -1)));
						} else if(preg_match("/(.*) On /i", $line, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13399, mats2[".$i++."] = ".$mat2."\n";
							$temp = trim($mats2[1]);
							if($this->countPotentialHabitatWords($temp) == 0 || $this->countPotentialLocalityWords($temp) > 0) {
								if(preg_match("/-$/", $location)) $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))));
								else $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))), " ");
								$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($temp, '/')."/", "", $s, -1)));
							}
						} else if(!preg_match("/^On /i", $line)) {
							if($this->countPotentialHabitatWords($line) == 0 || $this->countPotentialLocalityWords($line) > 0) {
								if(preg_match("/-$/", $location)) $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $line)))));
								else $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $line)))), " ");
								$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($line, '/')."/", "", $s, -1)));
							}
						}
					}
				}
			} else if ($lookAtNextLine) {
				if(preg_match("/^Co[l1I!|]{2}[;:,.]/", $line) || preg_match("/^Det[;:,.] /", $line) || preg_match("/Un[i1!l]vers[i1!l]t/i", $line)) break;
				else {
					if(preg_match("/^((Among .+ )(on .+?))[;,.]/i", $line, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13379, mats2[".$i++."] = ".$mat2."\n";
						$habitat = trim($mats2[2]);
						$substrate = trim($mats2[3]);
						$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats2[1]), '/')."/", "", $s, -1)));
					} else if(preg_match("/(.*) ((Among .+ )(on .+?))[;,.]/i", $line, $mats2)) {
						$temp = trim($mats2[1]);
						if($this->countPotentialHabitatWords($temp) == 0 || $this->countPotentialLocalityWords($temp) > 0) {
							if(preg_match("/-$/", $location)) $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))));
							else $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))), " ");
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($temp, '/')."/", "", $s, -1)));
						}
						$habitat = trim($mats2[3]);
						$substrate = trim($mats2[4]);
						$s = str_replace("\\", "", trim(preg_replace("/".preg_quote(trim($mats2[2]), '/')."/", "", $s, -1)));
					} else if(preg_match("/(.+) On /i", $line, $mats2)) {//$i=0;foreach($mats2 as $mat2) echo "\nline 13432, mats2[".$i++."] = ".$mat2."\n";
						//$lookAtNextLine = false;
						$temp = trim($mats2[1]);
						if($this->countPotentialHabitatWords($temp) == 0 || $this->countPotentialLocalityWords($temp) > 0) {
							if(preg_match("/-$/", $location)) $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))));
							else $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $temp)))), " ");
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($temp, '/')."/", "", $s, -1)));
						}
					} else if(!preg_match("/^On /i", $line)) {//$lookAtNextLine = false;
						if($this->countPotentialHabitatWords($line) == 0 || $this->countPotentialLocalityWords($line) > 0) {
							if(preg_match("/-$/", $location)) $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $line)))));
							else $location = $this->mergeFields($location, trim(preg_replace("/ ([.,;:])/", "\${1}", trim(preg_replace("/\([^ ]+\)/", "", $line)))), " ");
							$s = str_replace("\\", "", trim(preg_replace("/".preg_quote($line, '/')."/", "", $s, -1)));
						}
					}
				}
			} else {
				$s =trim(str_replace($line, preg_replace("/ on /i", "\non ", $line), $s));
			}
		}//echo "\nline 13449, s: ".$s."\nlocation: ".$location."\nstate_province: ".$state_province."\ncounty: ".$county."\n";
		$fields['stateProvince'] = $state_province;
		$fields['country'] = $country;
		$fields['locality'] = $location;
		$fields['county'] = $county;
		$fields['habitat'] = $habitat;
		$fields['substrate'] = $substrate;
		return $this->doGenericLabel(str_replace("\n\n", "\n", $s), "293", $fields);
	}
}
?>