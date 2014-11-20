<?php
include_once($serverRoot.'/classes/SalixUtilities.php');
include_once($serverRoot.'/classes/OccurrenceUtilities.php');

/* This class extends the SpecProcNlp class and thus inherits all public and protected variables and functions 
 * The general idea is to that the SpecProcNlp class will cover all shared Symbiota functions (e.g. batch processor, resolve state, resolve collector, test output, etc)
 */ 
/* The general approach of the algorithm is as follows:
	The label is pre-formatted and separated into lines, broken both at existing newlines and also at some semi-colons and periods.
	Each field is processed one at a time, starting with Latitude/Longitude.  Order matters.
	For each field (in general): 
		Lines are evaluated, ranked and sorted for probability of containing that field.
		Relevant content is focused on in the most probable line(s) (using various techniques), extracted and added to the Results array
		Content that was extracted is deleted from the line so that other fields won't mistakenly grab it.
	
*/
	

class SpecProcNlpSalix
	{
	private $conn;
	private $wordFreqArr = array();
	private $ResultKeys = array("catalogNumber","otherCatalogNumbers","family","scientificName","sciname","genus","specificEpithet","infraspecificEpithet","taxonRank","scientificNameAuthorship","recordedBy","recordNumber","associatedCollectors","eventDate","year","month","day","verbatimEventDate","identifiedBy","dateIdentified","habitat","","substrate","fieldNotes","occurrenceRemarks","associatedTaxa","verbatimAttributes","country","stateProvince","county","municipality","locality","decimalLatitude","decimalLongitude","verbatimCoordinates","minimumElevationInMeters","maximumElevationInMeters","verbatimElevation","recordEnteredBy","dateEntered","ignore");
			//A list of all the potential return fields
	private $Results = array();
			//The return array
	private $Assigned = array();
			//Indicates to what line a field has been assigned.
	private $PregMonths ="(\b(jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec|january|february|march|april|june|july|august|september|october|november|december|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|deciembre)\b\.?)";
	private $LabelLines=array();
	private $PregStart = array();
			//An array of regular expressions indicating the start words for many fields.
	private $Family = "";
	private $LichenFamilies =  array();
	private $StatScore = array();
			//The wordstats score of each line
	private $LineStart = array();
			//One element for each line indicating if it starts with a start word.

	
	function __construct() 
		{
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		set_time_limit(7200);
		}

	function __destruct()
		{
		if(!($this->conn === false)) $this->conn->close();
		}
	
	//Parsing functions
	public function parse($Label) 
		{
		if(strlen($Label) < 20)
			return $this->Results;
		$dwcArr = array();
		//Add the SALIX parsing code here and/or in following functions of a private scope
		if(mb_detect_encoding($Label,'UTF-8,ISO-8859-1') == "UTF-8")
			{
			$Label = utf8_decode($Label);
			//$ocrStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$ocrStr);
			}
		//Set the keys for a few arrays to the names of the return fields
		$this->Results = array_fill_keys($this->ResultKeys,'');
		$this->Assigned = array_fill_keys($this->ResultKeys,-1);
		$this->PregStart = array_fill_keys($this->ResultKeys,'');
		$this->GetLichenNames(); //Get list of Lichen families to help find substrates
		
		//****************************************************************
		// Do some preformatting on the input label text
		//****************************************************************
		
		//A few replacements to format the label making it easier to parse
		
		//Make sure space between NSEW cardinal directions and following digits, for Lat/Long 
		$Label = preg_replace("(([NESW]\.?)(\d))","$1 $2",$Label);
		//Make sure space between altitude and indicator (ft or m)
		$Label = preg_replace("((\d)((m|ft)(\s|.)))","$1 $2",$Label);
		//Make sure space between period and capital letter, such as an initial followed by a name.
		$Label = preg_replace("((\.)([A-Z]))","$1 $2",$Label);

		//Remove double spaces and tabs
		$Label = str_replace("  "," ",$Label);
		$Label = str_replace("\t"," ",$Label);
		//Remove (?) <>.  Perhaps should leave in...?  Would a collector ever write e.g. "Found > 500 m. elevation"? "Collected < 100 meters from stream"?
		$Label = str_replace("<","",$Label);
		$Label = str_replace(">","",$Label);
		$Label = str_replace("^"," ",$Label);

		//Separate at semicolons
		$Label = str_replace(";","\r\n",$Label);
		
		//Separate lines at a few start words
		foreach(array("County:","Collected by:","Collector:","Date:","Det.","Altitude:","Altitude about") as $SplitPoint)
			{
			$Label = str_ireplace("$SplitPoint","\r\n$SplitPoint",$Label);
			}

		//Remove empty lines
		$Label = str_replace("\r\n\r\n","\r\n",$Label);
		
		$this->LabelLines = preg_split("[(?<=[a-z]{4,4})\.|(?<=[a-z]);|\n]",$Label,-1,PREG_SPLIT_DELIM_CAPTURE);
		//regex expression to split lines at semicolons and periods.  But not at periods unless the preceding is
		//at least 4 lower case letters.  This preserves abbreviations.  
		$L = 0;
		while($L < count($this->LabelLines))
			{
			$this->LabelLines[$L] = trim($this->LabelLines[$L]);
			if(strlen($this->LabelLines[$L]) <3) //Remove lines less than 3 characters long
				unset($this->LabelLines[$L]);
			$L++;
			}
		$this->LabelLines = array_values($this->LabelLines); //renumber the array

			
		//Break each line up into an array of words resulting in a two-dimensional array -- $this->LabelArray
		//This may be phased out as regular expressions take over most of the work.  Let's hope!
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$OneLine = $this->LabelLines[$L];
			$Words = str_replace('\'','',$OneLine);
			$Words = str_replace('-',' ',$Words);
			$WordsArray = str_word_count($Words,1,"0123456789-&."); // Break sentence into words
			$this->LabelArray[] = $WordsArray; 
			}

		$this->InitStartWords();//Initialize the array of start words, which are hard coded in the function.
								//Also creates an array $LineStart where the element for each line indicating if it has a start word.

		//Save preliminary wordstat measurement of each line.  Avoids some repeated calls later.
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$OneStat = array();
			$this->ScoreOneLine($L, $Field, $Score);
			$OneStat['Field'] = $Field;
			$OneStat['Score'] = $Score;
			$this->StatScore[$L] = $OneStat;
			}
		
		//$this->printr($this->StatScore,"Stats");
		//*************************************************************
		//Here's where the individual fields get called and hopefully filled
		
		//Order is important.  
		//First, easy-to-find fields are done first, and then removed so as to not confuse other fields.
		//Example:  LatLong is easy to find because of degree symbols, sequences of numbers, etc.  And by finding LatLong first, we can then remove it from locality.
		
		//Second, knowing one field can help find another related field.
		//Example:  If we find recordedBy, then we know eventDate and recordNumber are probably very near, likely on the same line, so we can use that to rank the lines.
		//Example:  If we find LatLong, then we know Locality should be near, probably on the same line.

		$this->GetLatLong();
		$this->GetScientificName();
		$this->GetName('recordedBy');
		$this->GetName('identifiedBy');
		$this->GetEventDate("recordedBy","eventDate");
		$this->GetEventDate("identifiedBy", "dateIdentified");
		$this->GetRecordNumber();
		$this->GetElevation();
		$this->GetAssociatedTaxa();
		$this->GetCountryState();
		$this->GetWordStatFields();
		//Clean up the Results array and remove empty fields
		foreach($this->Results as $Key=>$Val)
			{
			if($Val == "")
				unset($this->Results[$Key]);
			else
				$this->Results[$Key] = trim($Val);
			}
		return $this->Results;
		
		
	}

//************************************************************************************************************************************
//***************** Scientific Name Functions ****************************************************************************************
//************************************************************************************************************************************

	//**********************************************
	private function GetScientificName()
		{
		$this->FindFamilyName();//Look for family name first.  Can help locate sciname, and help validate sciname.
		$match=array();
		$ScoreArray = array();
		$MaxLine = 0;
		$Max = -100;
		for($L=0;$L<count($this->LabelLines);$L++)
			{//Check each line for possible scientific name.  Score the return value by a few parameters.
			$Score=0;
			//echo "Checking {$this->LabelLines[$L]}<br>";
			$Found = preg_match("((\A[A-Z][a-z]{3,20}) ([a-z]{4,20}\b))", $this->LabelLines[$L],$match);
			if($Found === 1)//Looks like a sciname and is at the beginning of the line.
				{
				$Score += 2 + $this->ScoreSciName($match[1],$match[2]);//Increase score based on how close it is to a valid scientific name
				}
			else
				{
				$Found = preg_match("((\b[A-Z][a-z]{3,20}) ([a-z]{4,20}\b))", $this->LabelLines[$L],$match);
				if($Found ===1) //Looks like scientific name, though not near the beginning of the line
					$Score = $this->ScoreSciName($match[1], $match[2]);
				else
					{
					$Found = preg_match("((\A[A-Z]{3,20}) ([A-Z]{4,20}\b))", $this->LabelLines[$L],$match); //Might be all capitals
					if($Found ===1) //Looks like scientific name, though not near the beginning of the line
						$Score = $this->ScoreSciName($match[1], $match[2]);
					}
				}
			if(preg_match("(\([A-Z][a-z\.])",$this->LabelLines[$L]) === 1)
				$Score += 5; //Could be the author
			if($Found === 0)
				{ //Finally check for a Genus
				$Found = preg_match("((\b[A-Z][a-z]{3,20}))", $this->LabelLines[$L],$match);
				if($Found ===1)
					$Score = $this->ScoreSciName($match[1]," ");
				}
			$PosDeduction = 0;
			if($Found ===1)
				{
				if($this->Family != "")
					{
					$PosDeduction = abs($L-$this->Assigned['family']); //Reduce the score if far from the family name
					}
				else
					$PosDeduction = $L-4;//Or reduce the score if far from line 3, which is about where the Family is usually listed.
				if($PosDeduction > 3)
					$PosDeduction = 3;
				
				$Score -= $PosDeduction;
				$ScoreArray[$match[0]] = $Score;
				}
			//Check for county, state our country
			if(count($match) > 1)
				{
				 
				$query = "SELECT * FROM `lkupstateprovince` WHERE `stateName` LIKE '{$match[1]}' LIMIT 1";
				//echo "query = $query<br>";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					$ScoreArray[$match[0]] =  0;
				//echo "Found ".$result->num_rows." for state<br>";
				$query = "SELECT CountryName from lkupcountry where CountryName LIKE '{$match[1]}' LIMIT 1";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					$ScoreArray[$match[0]] =  0;
				
				}
			if($Score > $Max)	
				{
				$Max = $Score;
				$MaxLine = $L;
				}
			}
		foreach($ScoreArray as $Key=>$Score)
			$ScoreArray[$Key] += floor($Score/10); 

		asort($ScoreArray);//Sort the array, and then...
		end($ScoreArray); //Select the last (highest) element in the scores array
		$SciName = trim(key($ScoreArray)); 
			
		if(count($ScoreArray) == 0 || max($ScoreArray) <7 || (10-$ScoreArray[$SciName])/(strlen($SciName)) > .2)
			{ //Didn't find it this way.  Try brute force, checking each two-word combination
			for($L=0;$L<count($this->LabelLines);$L++)
				{
				for($W=0;$W<count($this->LabelArray[$L])-1;$W++)
					{
					$Word1=mb_convert_case($this->LabelArray[$L][$W],MB_CASE_TITLE);
					$Word2=mb_convert_case($this->LabelArray[$L][$W+1],MB_CASE_LOWER);
					if($this->ScoreSciName($Word1,$Word2) >=10)
						{
						$this->FillInSciName($Word1." ".$Word2,$MaxLine);
						return;
						}
					}
				}
			return;
			}
		$this->FillInSciName($SciName,$MaxLine);//Need to check family to make sure it matches.
		}

	//**********************************************
	private function FindFamilyName()
		{//First look for "....aceae of" something
		$PotentialFamily = "";
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$Found = preg_match("((\b\w{2,20}(aceae))\s(of)\s(.*))i",$this->LabelLines[$L],$match);//Replace above with simpler case insensitive version
			if($Found === 1)
				{
				//$this->printr($match,"ACEAE match:");
				$query = "SELECT family from omoccurrences WHERE family LIKE '{$match[1]}' LIMIT 1";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					{
					$this->Family = mb_convert_case($match[1],MB_CASE_TITLE);
					$this->AddToResults('family',$this->Family,$L);
					return;
					}
				}
			else if($PotentialFamily == "")
				{
				//$Found = preg_match("((\b[A-Za-z]{2,20}(aceae|ACEAE))\b)",$this->LabelLines[$L],$match);
				$Found = preg_match("((\b\w{2,20}(aceae))\b)i",$this->LabelLines[$L],$match);//Replace above with case insensitive version
				if($Found === 1)
					{ //If not found as "...aceae of...", then use the first "...acea..." found.
					//$this->printr($match,"ACEAE match:");
					$query = "SELECT family from taxstatus WHERE family LIKE '{$match[1]}' LIMIT 1";
					$result = $this->conn->query($query);
					if($result->num_rows > 0)
						{
						$PotentialFamily = mb_convert_case($match[1],MB_CASE_TITLE);
						}
					}
				}
			}
			//If something better was found, we won't get to this point.  But if we do, then use the most likely alternative
			if($PotentialFamily != "")
				{
				$this->Family = $PotentialFamily;
				$this->AddToResults('family',$PotentialFamily,$L);
				}
		}
		
		
	//**********************************************
	private function CheckOneSciName($query, $L)
		{//Check the database.  If sciname is found and is in the family (if known), add to database.
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			$OneLine = $result->fetch_assoc();
			if($this->Family == "" || strtolower($OneLine['family']) == strtolower($this->Family))
				{//Either matches the family, or family not known.
				$this->AddToResults('family', $OneLine['family'],-1);
				$this->FillInSciName($OneLine['sciname'],$L);
				return true;
				}
			}
		return false;
		}

	//**********************************************
	private function ScoreSciName($First, $Second, $IgnoreFamily=false)
		{ //Find the closest match in the taxa table, and return a score.  Higher is better.
		//First is Genus, Second is specific epithet.
		//Start by checking the full name (Genus species).  Slowly reduce the number of pieces to check, accepting the closest match.
		$Score=0;
		$CloseSci="";
		$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$First $Second' LIMIT 1";
		$result = $this->conn->query($query);
		if($result->num_rows == 1)
			{//Found perfect match
			$CloseSci = "$First $Second";
			$Score= 10;
			}
		if($Score < 10)
			{//Make the last half of specific epithet wild.
			$Piece2 = substr($Second, 0, floor(strlen($Second)/2));
			$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$First $Piece2%'";
			$result = $this->conn->query($query);
			if($result->num_rows >0) //Found some matches to Genus spec...
				$Score= $this->LevenshteinCheck("$First $Second",$result,$CloseSci);
			}
		if($Score < 10)
			{//Make the whole specific epithet wild
			$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$First%'";
			//echo "Query = $query<br>";
			$result = $this->conn->query($query);
			if($result->num_rows >0)  //Found matches to Genus.
				$Score= $this->LevenshteinCheck("$First $Second",$result,$CloseSci);
			}
		/*if($Score < 10) //Propose removing.  Finds too many false matches.
			{//Just look for the first half of the Genus
			$Piece1 = substr($First,0,floor(strlen($First)/2));
			$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$Piece1%'";
			$result = $this->conn->query($query);
			if($result->num_rows >0) //Found matches to Gen...
				$Score = $this->LevenshteinCheck("$First $Second",$result,$CloseSci);
			}
		*/
		if(!$IgnoreFamily && $this->Family != "")
			{//If we are already sure about the family, then check if the selected sciname is actually in that family.
			$query = "SELECT ts.family, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname LIKE '$CloseSci' AND ts.family like '{$this->Family}' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows == 0)
				{
				//Deduct 20 for not in known family";
				return -20;
				}
			}
		return $Score;
		}

	//**********************************************
	private function LevenshteinCheck($SciName, $result, &$CloseSci)
		{//Use levenshtein comparison to find closest match in the set of scinames and return the score
		// Return the closest scientific name too (called by address as CloseSci)
		//This is not used to correct OCR errors, but rather to improve detection of the scientific name which may contain errors
		$Best=100;
		$Len = strlen($CloseSci);
		while($OneSci = $result->fetch_assoc())
			{
			$Dist = levenshtein(trim($OneSci['sciname']),trim($SciName));
			if($Dist < $Best)
				{
				//echo "$SciName matched to {$OneSci['sciname']} scores $Dist<br>";
				$Best = $Dist;
				$CloseSci = $OneSci['sciname'];
				}
			}
		if($Best > 1 && $Len < 6)
			$Best += (8-$Len);
		return(10-$Best);//A perfect match will return a score of 10 
		}
		
		
	//**********************************************
	private function FillInSciName($SciName,$L)
		{//Add scientific name to the Results array if it is in the right family
		 //If family not known, then determine family.
		 //Then look for infra-specific epithet and author
		
		$NameArray = array();
		$NameArray = explode(" ",$SciName);
		$Genus = $NameArray[0];
		$Author="";
		if(count($NameArray) > 1)
			{
			$Species = $NameArray[1];
			$SciName = $Genus." ".$Species;
			}
		else
			{
			$Species = "";
			$SciName = $Genus;
			}
		
		$this->AddToResults('sciname',$SciName,$L);
		if($this->Family == "")
			{ //Family was not already known, so find it from the taxstatus table
			$query = "SELECT ts.family FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname LIKE '$SciName' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows == 1)
				{
				$OneLine = $result->fetch_assoc();
				$this->Family = $OneLine['family'];
				$this->AddToResults('family',$this->Family, -1);
				}
			}
		else if($this->Results['family'] == "")
			$this->AddToResults('family',$this->Family, -1);

		
		if(!$this->GetInfraSpecificEpithet($SciName,$Species,$L))
			{
			$query = "Select Author from taxa where SciName LIKE '$SciName' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				{
				$OneReturn = $result->fetch_assoc();
				$Author = $OneReturn['Author'];
				$this->AddToResults('scientificNameAuthorship',$Author,$L);
				$this->LabelLines[$L] = "";
				}
			}
		}	
		
	//**********************************************
	private function GetInfraspecificEpithet($SciName, $Species, $L)
		{
		$TaxonPreg = "((\b(ssp|var|varietas|forma|for)\.?)\s([a-z]{3,20})(\s[A-Za-z\(\)&\. ]*)?)";
		$match = array();
		$Line = $this->Assigned['sciname'];
		$Found = preg_match($TaxonPreg,$this->LabelLines[$L],$match);
		if($Found !== 1 && $L < count($this->LabelLines)-1) //Infraspecific might be on the next line
			$Found = preg_match($TaxonPreg,$this->LabelLines[++$L],$match);
		if($Found === 1)
			{
			$Infra = $match[3];
			$Rank = $match[1];
			$this->AddToResults('taxonRank',$Rank,$L);
			$this->AddToResults('infraspecificEpithet',$Infra,$L);
			$this->AddToResults('scientificName',trim($SciName." ".$Rank." ".$Infra),$L);
			if(count($match) > 4)
				$this->AddToResults('scientificNameAuthorship',$match[4],$L);
			return true;
			}
		return false;
		}


//************************************************************************************************************************************
//********************* Associated Taxa **********************************************************************************************
//************************************************************************************************************************************
		
		
	//**********************************************
	private function GetAssociatedTaxa()
		{
		$RankArray = $this->RankAssociatedTaxa();
		$match=array();
		foreach($RankArray as $L=>$Value)
			{
			if($Value < 2)
				return; //All potential lines tested.  Give up.
			$Preg = "(\b(WITH|With|with|w/|W/)[:-]*\s([A-Z][a-z]{3,20}\b).*)";//Must be case sensitive test
			$Found = preg_match($Preg,$this->LabelLines[$L],$match);
			if($Found ===1)
				{//Apparent sciname following the word "With"
				$this->RemoveStartWords($L,'associatedTaxa');
				$TempString = $match[0];
				$TempString = str_replace($match[1],"",$match[0]);
				}
			else if($this->LineStart[$L] == 'associatedTaxa')
				{//Line starts with an associatedTaxa start word (e.g. "with")
				$this->RemoveStartWords($L,'associatedTaxa');
				$TempString = $this->LabelLines[$L];
				$Found=true;
				}
			else 
				{//Still the most likely line, but none of the above worked.  Just look for a series of scinames on the same line.
				$Score = 0;
				$Found = preg_match_all("(([A-Z][a-z]{3,20}) ([a-z]{4,20}))",$this->LabelLines[$L], $match);//Capitalized word followed by lower case word
				if($Found > 0)
					{//Found will indicate the number of (apparent) sciname matches found
					//echo "Found $Found scinames<br>";
					$Limit = min($Found,4);
					for($S=0;$S<$Limit;$S++)
						{//Add up the scores for each entry in the line to see if they probably are scientific names.
						$OneScore = $this->ScoreSciName($match[1][$S],$match[2][$S],true);
						$Score += $OneScore;
						}
					$Score /= $Limit; //Average score for each entry on the line
					//echo "Score = $Score<br>";
					}
				if($Score < 7)
					$Found = false;
				else
					{
					$TempString = $this->LabelLines[$L];
					if($L > 0 && stripos($this->LabelLines[$L-1],"with") > 0)
						{
						preg_match("((\b(WITH|With|with)[:-]*)(.{5,}))",$this->LabelLines[$L-1],$match);
						if(count($match) > 0)
							$TempString = $match[3].", ".$TempString;
						}
					}
				}
			if($Found)
				{//Found the start of the associated species list.  
				$StatWord = $this->SplitString($TempString, $Field1, $Score1);
				if($StatWord > 0)
					{//Ass Taxa seems to end in the middle of the line, followed by something else.  Split apart.
					$StatString = substr($TempString,$StatWord); //Indicates the point to split apart.
					if(preg_match("(\A[A-Z]\. )",$StatString) == 0)
						{//If false, then don't split.  This is probably just an abreviated Genus -- 'O.' .  Leave it as it is.  Otherwise...
						$this->ScoreString($StatString, $Field, $Score);
						$this->AddToResults($Field,$StatString,$L); //Add string to the right wordstats field, and remove from the line.
						$this->LabelLines[$L] = str_replace($StatString,"",$this->LabelLines[$L]);
						$TempString = substr($TempString,0,$StatWord-1); //Strip off the non-associated taxa stuff
						}
					}
				$this->AddToResults("associatedTaxa", $TempString,$L);
				$this->RemoveStartWords($L,'associatedTaxa');
				$this->LabelLines[$L] = str_replace($TempString,"",$this->LabelLines[$L]);
				$this->LabelLines[$L] = str_ireplace("with","",$this->LabelLines[$L]);
				
				//Now check subsequent lines for more associated taxa
				while(++$L < count($this->LabelLines)-1)
					{
					$Found = preg_match("(\A([A-Z][a-z]{3,20}) ([a-z]{4,20})(,|\Z))",$this->LabelLines[$L],$match); //Line starting with "Genus species"
					if($Found !==1)
						$Found = preg_match("(\A([A-Z][a-z]{3,20})(\Z|,))",$this->LabelLines[$L],$match); //Line with just "Genus" on the line.  May need to tighten this up by checking subsequent lines or the rest of this line 
					if($Found !==1)
						$Found = preg_match("(\A([A-Z]\.\s[a-z]{3,20})(\Z|,))",$this->LabelLines[$L],$match); //Line starting with "Genus" on the line, but then more stuff.  May need to tighten this up
					if($Found ===1)
						{
						if(strpos($match[1], ". ") == 1 && $this->Results['associatedTaxa'] != "")
							{//Abbreviated Genus.  See if the previous genus starts with the same letter.  If so, expand this one.
							$LastArray = preg_split("([;,])",$this->Results['associatedTaxa']);
							$Last = trim(array_pop($LastArray));
							if(substr($Last,0,1) == substr($match[1],0,1))
								{
								$TempArray = explode(" ",$Last);
								$Genus = $TempArray[0];
								$match[2] = substr($match[1],3);
								$match[1] = $Genus;//str_replace(substr($match[1],0,2),$Genus, $match[1]);
								$this->LabelLines[$L] = str_replace($match[0],$Genus." ".$match[2].",", $this->LabelLines[$L]);
								}
							}
						//Next make sure this line scores low for WordStats
						$StatScore = $this->StatScore[$L]['Score'];
						//$this->ScoreOneLine($L,$Field,$StatScore);
						$SciScore=$this->ScoreSciName($match[1],$match[2],true);
						if($SciScore == 0)
							return; //Definitely not a sciname
						if($SciScore < 9 && $StatScore/$SciScore > 10)
							return; //probably not sciname, and likely a wordstats line.
						$this->AddToResults('associatedTaxa',$this->LabelLines[$L],$L);
						$this->RemoveStartWords($L,'associatedTaxa');

						$this->LabelLines[$L] = "";
						}
					}
				return;
				}
			}
		return;
		}

	private function SplitString($TempString, &$Field, &$Score)
		{ //Find where an Associated Species series ends and something else begins.  Only works for wordstats fields.
		$Found = preg_match_all("(\b[A-Za-z]{1,20}\b)",$TempString,$WordsArray, PREG_OFFSET_CAPTURE);//Break the string up into words
		for($w=0;$w<count($WordsArray[0])-1;$w++)
			{
			$Phrase = $WordsArray[0][$w][0]." ".$WordsArray[0][$w+1][0]; //Two word phrase
			$Score = $this->SingleWordStats($Phrase);//Get word stats score for these two words
			
			$SciScore = 0;
			$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$Phrase' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows == 1)
				$SciScore += 10;
			
			
			//$SciScore = $this->ScoreSciName($WordsArray[0][$w][0], $WordsArray[0][$w+1][0], true);//Is it a scientific name?
			if($SciScore > 8)//It's a sciname
				$FScore = 0;
			else if($SciScore <= 0)
				$FScore = $Score;//It's not a sciname.
			else
				$FScore = floor($Score/$SciScore);
			if($FScore > 60)
				{
				return $WordsArray[0][$w][1];//Return the offset where the line should be split
				}
				
			}
		return "0";
		}
	//**********************************************
	private function RankAssociatedTaxa()
		{//Each line is scored for probability of containing associated taxa
		//The array of lines is sorted by score and then reversed so the line of maximum probability is first, etc.
		//The routine then starts with the most probable line, continuing until the field is found, or score is below a minimum.
		$RankArray = array_fill(0,count($this->LabelLines),0);
		for($L=0;$L<count($this->LabelLines);$L++)
			{//Score the line
			if(count($this->LabelArray[$L]) < 2)
				$RankArray[$L] = -10; //Not likely if the line is only one word long.
			if($this->Assigned['sciname'] == $L)
				$RankArray[$L] -=100;
			if($this->LineStart[$L] == "associatedTaxa" && !$this->Assigned['associatedCollectors'] != $L)
				{
				//echo "Found start word in line $L<br>";
				$RankArray[$L] = 100;
				}
			if($this->Assigned['sciname'] == $L || $this->Assigned['associatedCollectors'] == $L)
				$RankArray[$L] -=100; //This line is either the scientific name line, or associated collectors line.  Can't be associated species line.
			$RankArray[$L] += substr_count($this->LabelLines[$L], ","); //Commas indicate a list, perhaps of associated species.
			if($this->Assigned['associatedCollectors'] != "" && $this->Assigned['associatedCollectors'] != $L)
				{//As long as associated collectors is clearly not on this line, then the word "associated" means this is probably the line.
				if(strpos($this->LabelLines[$L],"associated") !== false)
					$RankArray[$L] += 20;
				}
			$Found = preg_match("([w|W]ith [A-Z][a-z]{4,20})",$this->LabelLines[$L]); //With followed by a potential sciname is a strong indicator
			if($Found === 1 && $this->Assigned['associatedCollectors'] != $L)	
				{//Found the word "with", and it isn't for associated collectors.
				$RankArray[$L]+= 15;
				}
			$RankArray[$L] += preg_match_all("(([A-Z][a-z]{3,20}) ([a-z]{4,20}))",$this->LabelLines[$L],$match); //Add a point for each potential sciname
			}
		asort($RankArray);//Sort the array of scores...
		$RankArray = array_reverse($RankArray, true);//Then reverse order to bring most probable to the first position
		return $RankArray;
		}	

		
		
		
//************************************************************************************************************************************
//******************* Lat/Long Functions *********************************************************************************************
//************************************************************************************************************************************
	
	//******************************************************************
	private function GetLatLong()
		{
		//Determine which line is most likely to contain Lat/Long by counting probable elements
		$RankArray = array();
		$Start = 0;$End = 0;
		$match=array();
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$RankArray[$L] = 0;
			if(preg_match_all("(\b[NSEW]\b)",$this->LabelLines[$L],$match) == 2)
				$RankArray[$L] = 5;
			$RankArray[$L] += preg_match_all("([0-9]{2,3}+[°\*\"\' ])",$this->LabelLines[$L],$match);
			if(strpos($this->LabelLines[$L],"°") > 0)
				$RankArray[$L] += 5; //A degree symbol pretty much clinches it for Lat/Long, though they often get mis-OCR'd
			}
		asort($RankArray);
		//$this->printr($RankArray,"LL Rank");
		end($RankArray); //Select the last (highest) element in the scores array
		if(current($RankArray) > 1)
			$L=key($RankArray);
		else
			return;
		//echo "{$RankArray[$L]}<br>";
		if(strpos($this->LabelLines[$L],"°") > 0 && $RankArray[$L] < 10)//Found degree symbol, but rank is a little low.  Maybe it's split over two lines
			{
			if($L > 0 && strpos($this->LabelLines[$L-1],"°") > 0)
				$OneLine = $this->LabelLines[$L-1]." ".$this->LabelLines[$L];
			else if($L < count($this->LabelLines)-1 && strpos($this->LabelLines[$L+1],"°") > 0)
				$OneLine = $this->LabelLines[$L]." ".$this->LabelLines[$L+1];
			else
				$OneLine = $this->LabelLines[$L];
			}
		else
			$OneLine = $this->LabelLines[$L];
			
		//Put together the modules to build a Lat/Long in several formats
		$Preg = array();
		$Preg['dir'] = "[NSEW][\., ]*";
		$Preg['deg'] = "[0-9\.]+[°\*] ?";
		$Preg['min'] = "[0-9\.]+[\'’`, ]*";
		$Preg['sec'] = "(?:[0-9\.]+\")*";
		
		if($this->PregLatLong($Preg['dir'].$Preg['deg'].$Preg['min'].$Preg['sec'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['dir'].$Preg['deg'].$Preg['min'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['deg'].$Preg['min'].$Preg['sec'].$Preg['dir'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['deg'].$Preg['min'].$Preg['dir'],$L,$OneLine))
			return;
		}

	//**********************************************
	private function PregLatLong($Preg,$L,$OneLine="")
		{
		//Use the regular expression passed as "$Preg" on $OneLine.  If $OneLine is empty, use LabelLine[$L].
		if($OneLine == "")
			$OneLine = $this->LabelLines[$L];
		$match=array();
		$Found = preg_match_all("((".$Preg."))", $OneLine,$match);
		if($Found > 1)
			{
			$OU = new OccurrenceUtilities;
			$LL = $OU->parseVerbatimCoordinates($match[0][0]." ".$match[0][1]);
			if(count($LL) > 0)
				{
				$this->AddToResults('verbatimCoordinates',$match[0][0]." ".$match[0][1],$L);
				$this->AddToResults('decimalLatitude',$LL['lat'],$L);
				$this->AddToResults('decimalLongitude',$LL['lng'],$L);
				for($Line=$L-1;$Line < $L+2;$Line++)
					{
					if($L < 0)
						continue;
					if($L >= count($this->LabelLines))
						continue;
					$this->LabelLines[$Line] = str_replace($match[0][0],"",$this->LabelLines[$Line]);
					$this->LabelLines[$Line] = str_replace($match[0][1],"",$this->LabelLines[$Line]);
					}
				return true;
				}
			}
		return false;
		}
		
		

//************************************************************************************************************************************
//******************* Elevation Functions ********************************************************************************************
//************************************************************************************************************************************
		
	//******************************************************************
	private function GetElevation()
		{//Determine which lines are most probable to have an Elevation.  
		//NOTE:  Still doesn't capture maximum elevation.
		
		$PregWords = "(\b(elevation|elev|m.|m|ft.|ft|feet|meters|altitude|alt|ca)\b|[\d,]{3,5})";
		$RankArray = array();
		$match=array();
		$Start = 0;$End=0;
		for($L=0;$L<count($this->LabelArray);$L++)
			{
			$RankArray[$L] = 0;
			if($this->LineStart[$L] == 'minimumElevationInMeters')
				$RankArray[$L]+=100;

			//Adjust for some possible confusing lines
			if($this->LineStart[$L] == 'recordedBy')
				$RankArray[$L]-=100;//elevation won't be here
			if($this->LineStart[$L] == 'locality')
				$RankArray[$L]+=10;//Often in this line
			if($this->LineStart[$L] == 'habitat')
				$RankArray[$L]+=5;//Sometimes in this line

			//Look for the PregWords and adjust rank.
			$Found = 5*preg_match_all($PregWords, $this->LabelLines[$L],$match);
			$RankArray[$L] += $Found;
			if(preg_match("([0-9,]{3,6})",$this->LabelLines[$L]) != 1)
				$RankArray[$L] -= 100;  //There must be an appropriate numeric entry somewhere for elevation
			}
		//Sort the lines in rank order
		asort($RankArray);
		$RankArray = array_reverse($RankArray, true);
		//$this->printr($RankArray,"Elevs");
		//Now have an array of probable lines sorted most probable first.
		//ScoreArray will be filled with possible elevations and a score for each.
		$ScoreArray = array();
		foreach($RankArray as $L=>$Value)
			{//Iterate through the lines from most to least likely, cutting off if value gets below a threshold.
			if($Value < 5)
				break;
			$TempString = $this->LabelLines[$L];
			//echo "$TempString<br>";
			//$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*([0-9,]+)([\s]*)(f(ee)?t\b|m(eter)?s?\b|\'))",$TempString,$match);
			$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*[about ]*([0-9,-]+)([\s]*)(f(ee)?t\b|m(eter)?s?\b|\'))",$TempString,$match);
			if($Found === 1) //Embedded in a line with number following key word, like "Found at elevation 2342 feet"
				{
				//$this->printr($match,"Elev match 1");
				$ScoreArray[$match[0]] = $Value+10;
				if(max($ScoreArray) == $Value+10)
					$FoundElev = $match[2];
				}
			if($Found !== 1)
				{
				$Found = preg_match("((ca\.?\s)?([0-9,]+)([ ]*)(f(ee)?t\b|m(eter)?s?\b)[\S]*[\s]*([a|A]lt|[e|E]lev)[ation]?[\S]*)",$TempString,$match);
				if($Found === 1)  //Same as above but with the key word Elevation/Altitude following the number
					{
				//$this->printr($match,"Elev match 2");
					$ScoreArray[$match[0]] = $Value+10;
				if(max($ScoreArray) == $Value+10)
					$FoundElev = $match[1];
					}
				}
			if($Found !== 1)
				{ 
				$Found = preg_match("(\A(ca\.?\s)?([0-9,]+)([ ]*)(f(ee)?t\b|m(eter)?s?\b|\'))",$TempString,$match);
				if($Found === 1) //Found at beginning of the line, but without "Elevation" or "Altitude" indicator.  Just feet and meters.
					{
				//$this->printr($match,"Elev match 3");
					$Score =$Value+2;
					if($match[1] > 1000 && $match[1] < 12000)
						$Score+= 2;//Increase score if altitude in reasonable range
					$ScoreArray[$match[0]] = $Score;
				if(max($ScoreArray) == $Score)
					$FoundElev = $match[1];
					}
				}
			if($Found !== 1)
				{//Least certain, just a number followed by feet or meters in middle of a line.  Could be height, distance, etc.
				$Found = preg_match("((ca\.?\s)?\b([0-9,]+)(\s?)(f(ee)?t\b|m(eter)?s?\b|\'))",$TempString,$match);
				if($Found === 1)
					{
					$Score =$Value;
					if($match[1] > 1000 && $match[1] < 12000)
						$Score+= 2;//Increase score if altitude in reasonable range
					$ScoreArray[$match[0]] = $Score;
				if(max($ScoreArray) == $Score)
					$FoundElev = $match[1];
					}
				}
			}

		//All potential altitudes are found and in $ScoreArray.  Now sort by most likely and accept the top one.

		if(count($ScoreArray) == 0)
			return;
		asort($ScoreArray);
		end($ScoreArray); //Select the last (highest) element in the scores array
		$TempString = key($ScoreArray);//Should be the best line.
		$L = array_search($TempString,$this->LabelLines);
		$this->AddToResults('verbatimElevation', $TempString,$L);
		$Found = false;
		for($L=0;$L<count($this->LabelLines) && !$Found;$L++)
			if(strpos($this->LabelLines[$L],$TempString) !== false)
				{
				$this->LabelLines[$L] = str_replace($TempString,"",$this->LabelLines[$L]);
				$Found=true;
				}
		//Remove comma from thousands place for simpler calculations
		$TempString = preg_replace("(([0-9]),([0-9]))","\\1\\2", $TempString); 
		$TempString = trim(str_ireplace(array("altitude","elevation","about","ca"),"",$TempString));
		//echo "Tempstring = $TempString<br>";
		$OU = new OccurrenceUtilities();
		$El = $OU->parseVerbatimElevation($TempString);
		$Found=false;
		//$this->printr($El,"El");
		if(isset($El['minelev']))
			{
			//$this->printr($El,"El Return");
			$Alt = $El['minelev'];
			if($Alt > 10 && $Alt < 5000)
				{
				$this->AddToResults('minimumElevationInMeters',$Alt,$L);
				$Found=true;
				if(isset($El['maxelev']))
					$this->AddToResults('maximumElevationInMeters',$El['maxelev'],$L);
				}
			}
		if($Found)
			return;
			
		}

		
//************************************************************************************************************************************
//*********** Name Finding Routines **************************************************************************************************
//************************************************************************************************************************************

	private function GetName($Field)
		{//Ranks lines for probability, then looks for name pattern
		global $Label;
		if($this->Results[$Field] != "")
			return;
		$RankArray = $this->RankLinesForNames($Field);
		//$this->printr($RankArray,"Name Rank");
		if(current($RankArray) < 2 && $Field == "recordedBy")
			{
			$match=array();
			$Preg = "((Collector|Collected by:?)\s\b((([A-Z][a-z]{2,20} )|([A-Z][\.] ))([A-Z][\.] )?([A-Z][a-z]{2,20})\b))";
					//(Initial or first name), (optional middle initial), (last name).
			for($L=0;$L < count($this->LabelLines);$L++)
				{
				$Found = preg_match_all($Preg, $this->LabelLines[$L],$match);
				//$this->printr($match,"MidNameMatch");
				if($Found > 0)
					{
					$this->AddToResults($Field,$match[2][0],$L);
					$this->LabelLines[$L] = str_replace($match[0][0],"",$this->LabelLines[$L]);
					return;
					}
				}
			}
		
		
		foreach($RankArray as $L=>$Value)
			{
			if($Field=='identifiedBy' && $Value < 90)
				return; //Currently can't find identifiedBy unless there is a start word.  Need to find alternative clues.
			if($Value < 2)
				return; //Since array is sorted, no need to look at the rest
			$FieldLine = $this->Assigned[$Field];	
			if($FieldLine > 0)
				{
				if($FieldLine > $L || $L - $FieldLine > 2)
					continue;
				}
			if($this->GetNamesFromLine($L,$Field))
				break;
			if($Field == 'recordedBy' && $L > $this->Assigned['recordedBy']+1)
				break;//There shouldn't be any more associated collectors more than one line after main collector
			}
		return;
		}

	private function GetNamesFromLine($L,$Field)
		{
		//echo "Testing $Field: {$this->LabelLines[$L]}<br>";
		$match=array();
		$Preg = "(\b(([A-Z][a-z]{2,20} )|([A-Z][\.] ))([A-Z][\.] )?([A-Z][a-z]{2,20}\b))";
				//(Initial or first name), (optional middle initial), (last name).
		$Found = preg_match_all($Preg, $this->LabelLines[$L],$match);
		if($Found > 0)
			{
			for($N=0;$N<$Found;$N++)
				{
				$Name = $match[0][$N];
				//echo "Name = $Name<br>";
				if($this->ConfirmRecordedBy($Name) < 0)
					continue;
				$this->AddToResults($Field,$match[0][$N],$L);
				$this->RemoveStartWords($L,$Field);
				$this->LabelLines[$L] = str_ireplace("with","",$this->LabelLines[$L]);
				$this->LabelLines[$L] = trim(str_replace($match[0][$N],"",$this->LabelLines[$L])," ,");
				//echo "L=$L<br>";
				if($L < count($this->LabelLines)-1 && preg_match("(\A[A-Z][a-z]{2,15}\Z)",$this->LabelLines[$L]) && preg_match("(\A[A-Z][a-z]{2,15}\Z)",$this->LabelLines[$L+1]))
					{//Catch the case where the given name on this line, surname is on the next line.  Fairly rare, but happens.
					$Name = $this->LabelLines[$L]." ".$this->LabelLines[$L+1];
					if($this->ConfirmRecordedBy($Name) >=0)
						{
						$this->AddToResults($Field,$Name,$L);
						$this->LabelLines[$L]="";
						$this->LabelLines[$L+1]="";
						}
					}
				}
			if($Field == "recordedBy" && $L < count($this->LabelLines)-1 && ($this->LineStart[$L+1] == "" || $this->LineStart[$L+1] == "associatedCollectors"))
				{
				$this->GetNamesFromLine(++$L,$Field);//Recursively calls this routine to add possible associated collectors.
				}
			return true;
			}
		if($this->LineStart[$L]== $Field)
			{
			$this->RemoveStartWords($L,$Field);
			$Found = preg_match("(\A[A-Za-z\. ]{4,20}\b)",$this->LabelLines[$L],$match);
			if($Found > 0)
				{
				$Name = mb_convert_case($match[0],MB_CASE_TITLE);
				$this->AddToResults($Field,$Name,$L);
				$this->LabelLines[$L] = str_ireplace($Name,"",$this->LabelLines[$L]);
				
				}
			
			}
		return false;
		}
		
		
	//**********************************************
	private function ConfirmRecordedBy($Name)
		{//Check for the name in the omoccurrences table.  Lower score if it looks like a university, herbarium, county, etc.
		$match=array();
		$Score = 0;
		$PregNotNames = "(\b(municip|herbarium|agua|province|university|mun|county|botanical|garden)\b)i";  //Known to be confused with names
		$Score -= 5*(preg_match_all($PregNotNames,$Name,$match));
		$query = "SELECT recordedBy FROM omoccurrences where recordedBy LIKE '$Name' LIMIT 1";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			return 10;
		else
			return $Score;
		}
		
		
		
	//******************************************************************
	private function RankLinesForNames($Field)
		{//Determine which lines are most probable to have a name.  Also check for start words.
		 //Return array of names sorted in order of probability of having the $Field
		$ConflictFields = array('sciname','country','county','identifiedBy','associatedCollectors','recordedBy',"locality","family");
		$RankArray = array();
		$match=array();
		for($L=0;$L<count($this->LabelArray);$L++)
			{
			$RankArray[$L] = 0;
			if(count($this->LabelArray[$L]) < 2)
				{
				$RankArray[$L]-=100;
				continue;
				}
			if($this->LineStart[$L] == $Field)
				$RankArray[$L] += 1000;
			if($this->StatScore[$L]['Score'] > 50)
				{
				//echo "$L: StatScore = {$this->StatScore[$L]['Score']}, {$this->StatScore[$L]['Field']}, {$this->LabelLines[$L]}<br>";
				if($this->StatScore[$L]['Field'] != "locality")
					$RankArray[$L] -= $this->StatScore[$L]['Score'];
				
				}
			foreach($ConflictFields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					//echo "SW for $F in {$this->LabelLines[$L]}<br>";
					if($F == $Field)
						{
						$RankArray[$L] += 100; //Increase if start word for this field
						continue; //Don't need to go on
						}
					if($F == 'sciname' || $F == 'country' || $F == 'county' || $F == 'family')
						{
						$RankArray[$L] -= 10; //Decrease if start word for another field
						}
					if($Field != 'identifiedBy' && $F == 'identifiedBy')
						$RankArray[$L] -= 100; //Decrease if start word for another field
					if($Field == 'recordedBy' && $F == 'associatedCollectors')
						{
						$RankArray[$L] -= 5; //Decrease, but not too much the associateds can be found later.
						if($L > 1 && $RankArray[$L-1] > 0)
							$RankArray[$L-1] += 5; //Decrease, but not too much the associateds can be found later.
						}
					if($Field == 'identifiedBy' && $F != 'identifiedBy')
						$RankArray[$L] -= 100; //Decrease if start word for another field
					}
				}
			if($this->SingleWordStats($this->LabelLines[$L],'locality') > 100)
				$RankArray[$L] -= 10;
			if($Field == 'associatedCollectors' && $this->Assigned['recordedBy'] > $L)
				$RankArray[$L] -= 110; //Associated Collectors should always follow main collector
			
			if($Field == 'identifiedBy' && $this->Assigned['recordedBy'] == $L)
				$RankArray[$L] -= 100; //Determiner never on same line as collector (?)

			if($this->Assigned['infraspecificEpithet'] == $L)
				{
				$RankArray[$L] -= 100; //Must be author instead of collector
				}
			
			$Found = preg_match("(\b(([A-Z][a-z]{2,20} )|([A-Z][\.] ))([A-Z][\.] )?([A-Z][a-z]{2,20}\b))",$this->LabelLines[$L]);
			if($Found === 1) //Looks like a name
				$RankArray[$L] += 10;
			else
				$RankArray[$L] -= 10;
			$RankArray[$L] += 3*preg_match("( [0-9]{3,10} ?)",$this->LabelLines[$L]); //Add a little for a number -- could be date or collection number.
			$RankArray[$L] -= 3*preg_match("((\bft\b)|(\bm\b))",$this->LabelLines[$L]); //Subtract if looks like altitude
			$RankArray[$L] -= (3*preg_match_all("([0-9]\.[0-9])",$this->LabelLines[$L],$match)); //Decimal not likely to be in collection number or date
			$RankArray[$L] -= 2*preg_match_all("(\(|\)|°|\")",$this->LabelLines[$L],$match); //Parenthesis on the line probably mean author, not collector.  Degree looks like lat/long.  Quote looks like lat/long
			$RankArray[$L] += 4*preg_match($this->PregMonths."i",$this->LabelLines[$L]); //Add a little for a month -- could be collection date
			if($L < count($this->LabelLines)-1)
				$RankArray[$L] += 2*preg_match($this->PregMonths."i",$this->LabelLines[$L+1]); //Add a little for a month -- next line could be collection date
			if($L >0)
				$RankArray[$L] += 2*preg_match($this->PregMonths."i",$this->LabelLines[$L-1]); //Add a little for a month -- previous line could be collection date
			if($Field != 'identifiedBy' && strpos($this->LabelLines[$L], "&") >0)
				$RankArray[$L]+=3; // Could be associated collectors on the same line
			if($Field == 'recordedBy' && strpos($this->LabelLines[$L]," s. n."))
				$RankArray[$L]+=10; 
			}
		//Sort the lines in rank order
		
		
		asort($RankArray);
		return (array_reverse($RankArray, true));
		}

		
	
//**********************************************************************************************************************************
//*********** Event Date Routines **************************************************************************************************
//**********************************************************************************************************************************
	
	//******************************************************************
	private function GetEventDate($EventField, $Field)
		{
		//Used by both eventDate and identifiedDate, determined by $Field
		$m=0;$Year=0;$Day=0;$Date="";
		$FoundLine=0;
		$ReturnDate = array('Year'=>0,'Month'=>$m,'Day'=>0);
		$RankArray = $this->RankForDate($EventField, $Field);
		//$this->printr($RankArray,"Date Rank");
		foreach($RankArray as $L => $Value)
			{
			if($Value < 0)
				break;
			if($this->DateFromOneLine($Field,$L))
				return;
			}
		//Didn't find in standard format.  Try "July 1992"
		foreach($RankArray as $L => $Value)
			{
			if($Value < 0)
				break;
				
			if($this->DateFromOneLine($Field,$L,true))
				return;
			}
		
		return;
		}

	//**********************************************
	private function RankForDate($EventField, $Field)
		{ //Rank lines for $EventField date
		$RankArray = array();
		$RankArray = array_fill(0,count($this->LabelLines),0);
		if($this->Assigned[$EventField] != '-1')
			{
			$L = $this->Assigned[$EventField];
			$RankArray[$L] = 10; //Most likely on the same line as the event
			while(++$L < count($this->LabelArray)) //Diminishing likelihood on subsequent lines
				$RankArray[$L] = 10 - ($L-$this->Assigned[$EventField]);
			$L = $this->Assigned[$EventField];
			while(--$L >= 0) //Less likelihood on previous lines
				$RankArray[$L] = 8 - ($this->Assigned[$EventField]-$L);
			}

		for($L=0;$L<count($this->LabelArray);$L++)
			{//Various characteristics increase or decrease probability
			$TempString = $this->LabelLines[$L];
			if(preg_match("(\b\d+\b)",$TempString) !== 1)
				{// Must be a number on the date line. (Unless roman numeral.  Later...)
				$RankArray[$L] = -100;
				continue;
				}
			if($Field != "eventDate" && $this->Assigned['eventDate'] == $L)
				{//Not likely to find identifiedDate on the recordedby line
				$RankArray[$L] = -100;
				continue;
				}
			if($Field != "dateIdentified" && $this->Assigned['identifiedBy'] == $L)
				{//Not likely to find recordedBy date on identifiedBy line
				$RankArray[$L] = -100;
				continue;
				}
			if(preg_match("(\b(1[8-9]\d{2,2})|(20[01]\d{1,1})\b)",$TempString,$match)===1)
				{
				$Year = $match[0];
				$RankArray[$L]+=3;//Could be year between 1800 and 2019
				if($Year > 1950)
					$RankArray[$L] += 1;  //Last 60 some years, more likely
				if(Date("Y") - $Year < 3)
					$RankArray[$L] += 3;  //Last couple of years, a likely date
				}
			if(preg_match("((\b1[0|1|2]\b)|(\b[1-9]\b))",$TempString,$match)===1)
				$RankArray[$L] += 1; //Could be month.  Not worth much.

			if(preg_match("((\b[1|2|3]?[0-9]\b))",$TempString,$match)===1)
				if($match[0] < 32)
					$RankArray[$L] += 2; //Could be day of the month.

			$RankArray[$L] += 2*substr_count($this->LabelLines[$L],"/");//A slash is often found in a date.

			$Numeric=false;
			$Roman = false;
			if(strpos($this->LabelLines[$L],"X") || strpos($this->LabelLines[$L],"I"))
				$Roman = true; //Not doing anything with this yet.

			$TempString = strtolower($this->LabelLines[$L]);
			if(preg_match($this->PregMonths,$TempString) == 1)
				$RankArray[$L] += 10; //Month string appears in the line.
			if(preg_match("(\d{1,4})",$TempString) != 1)
					$RankArray[$L] -= 100;  //There must be an appropriate numeric entry somewhere for elevation
			//if(!$Numeric && $Roman)
			//	$RankArray[$L] -= 5; // Might be expressed in Roman numerals.  Have not implemented yet.
			}
		asort($RankArray);
		return (array_reverse($RankArray, true));
		}

	//**********************************************
	private function DateFromOneLine($Field, $L,$Partial=false)
		{//Assumes standard format, d/m/y or m,d,y
		$match=array();
		$RealVDate = "";
		$TempString = ($this->LabelLines[$L]);
		if($Partial)
			$Preg = "(\b{$this->PregMonths}\s*(\b[0-9]{1,4}\b))i";
		else
			$Preg = "((\b[0-9]{1,4}\b)\s*{$this->PregMonths}\s*(\b[0-9]{1,4}\b))i";
		$Found = preg_match($Preg,$TempString,$match);
		if($Found !== 1)
			{//Format April 21, 1929
			$Preg = "({$this->PregMonths}\s*(\b[0-9]{1,4}\b),?\s*(\b[0-9]{1,4}\b)\s*)i";
			$Found = preg_match($Preg,$TempString,$match);
			//echo "$Preg<br>";
			}
		if($Found !== 1)
			{
			$Preg = "(([0-9]+[/-])([IVX]+)([/-][0-9]+))";
			$Found = preg_match($Preg, $TempString,$match);
			
			if($Found >0)
				{
				$RomanMonths = array("I"=>"Jan","II"=>"Feb","III"=>"Mar","IV"=>"Apr","V"=>"May","VI"=>"Jun","VII"=>"Jul","VIII"=>"Aug","IX"=>"Sep","X"=>"Oct","XI"=>"Nov","XII"=>"Dec");
				$Month = $RomanMonths[$match[2]];
				$TempString = str_replace($match[2],$Month,$TempString);
				//$this->printr($match,"Roman Match");
				$RealVDate = $match[0];
				$match[0] = str_replace($match[2],$Month,$match[0]);
				}
			}
		if($Found)
			{
			$OU = new OccurrenceUtilities;
			$VDate = $match[0];
			$this->AddToResults($Field, $OU->formatDate($VDate),$L);
			if($RealVDate != "")
				$VDate = $RealVDate;
			$this->LabelLines[$L] = str_replace($VDate,"",$this->LabelLines[$L]);
			if($Field == "eventDate")
				$this->AddToResults("verbatimEventDate",$VDate,$L);
			$this->RemoveStartWords($L,$Field);
			return true;
			}
		return;
		}

//************************************************************************************************************************************
//***************** RecordNumber Functions *******************************************************************************************
//************************************************************************************************************************************
	
	
//******************************************************************
	private function GetRecordNumber()
		{
		$match=array();
		//Assume on the same line as recordedBy.  If no recordBy, then return empty.
		if($this->Assigned['recordedBy'] == "")
			return; //No collector, can't have collection number
		$L = $this->Assigned["recordedBy"];//Find the collectors line
		//But check for start word as a back up
		for($L1=0;$L1<count($this->LabelLines);$L1++)
			{
			if($this->LineStart[$L1] =='recordNumber')
				{
				$this->RemoveStartWords($L1,'recordNumber');
				$L = $L1;
				break;
				}
			}
		if($L < 0)
			return;
		//Date will have already been removed from the line, so any number remaining is likely the collection number.
		$WordArray = preg_split("( )",$this->LabelLines[$L],-1,PREG_SPLIT_DELIM_CAPTURE);
		for($W=0;$W<count($WordArray);$W++)
			{//Check words one at a time for a match to typical collection number format.
			$Word= trim($WordArray[$W]);
			
			$PM=preg_match('(([#0-9]+-?)([a-zA-Z-]*))',$Word,$match);
			if($Word != "" && !ctype_alpha($Word) && $PM!==0)
				{//Consists of digits, optional letters, and an optional hyphen.
				$Match = $match[0];
				if(strpos($Word,"#") !== false && preg_match("([0-9])",$Word) === 0)
					{
					if($W < count($WordArray)-1 && preg_match("([0-9])", $WordArray[$W+1]) === 1)
						{
						$Match .= " ".$WordArray[$W+1];
						}
					}
				$this->AddToResults('recordNumber', $Match,$L);
				return;
				}
			}
			
		}

//******************************************************************
//******************* Country/State Functions ***************************
//******************************************************************


	//**********************************************
	function GetCountryState()
		{
		global $Label;
		//Assumes that the Country and state are already in the lkup... tables
		$RankArray = array();
		$match=array();
		$PlantsOf = "";
		$PlantsOfLine = -1;
		$CountyName = "";
		$CountryArray = array();
		
		//Go through and rank the lines, looking for "Plants Of" and "County" at the same time.
		for($L=0;$L<count($this->LabelLines);$L++)
			{ //If certain words appear, then much more likely state is there.  
			$RankArray[$L] = 10-$L;
			$Found = preg_match("(([A-Za-z]{2,20}ACEAE)\s+(of)\s+(.*))i",$this->LabelLines[$L],$match);
			if($Found !== 1)
				$Found = preg_match("((plants|flora|lichens)(\sof\s)(.*))i",$this->LabelLines[$L],$match);

			if($Found ===1)
				{
				$RankArray[$L] += 5;
				$PlantsOf = trim($match[3]," .,;:");  //Grab the location name.  Could be state or country.
				$PlantsOfLine = $L;
				}
			$Preg = "(\b([a-z]{2,20})\s+((\bcounty\b)|(\bco\b)))i";
			$Found = preg_match($Preg,$this->LabelLines[$L],$match);
			if($Found === 0)
				{
				
				$Preg = "((\bcounty\b):*\s+\b([a-z]{2,20}))i";
				$Found = preg_match($Preg,$this->LabelLines[$L],$match);
				}
			
			if($Found === 1)
				{//Found the word "County" on the label.  Might be enough to determine state and country.
				//echo "Found word County<br>";
				$query = "SELECT c.stateId,c.countyName,s.statename,s.countryId FROM lkupcounty c INNER JOIN lkupstateprovince s where c.stateId=s.stateId AND countyName LIKE '{$match[1]}'";
				$result = $this->conn->query($query);
				if($result->num_rows == 0)
					{//Single word not a county name.  Look for two word county
					$Preg = "(\b([a-z]{2,20}\s+\b[a-z]{2,20})\s+((\bcounty\b)|(\bco\b)))i";
					$Found = preg_match($Preg,$this->LabelLines[$L],$match);
					if($Found)
						{
						$query = "SELECT c.stateId,c.countyName,s.statename,s.countryId FROM lkupcounty c INNER JOIN lkupstateprovince s where c.stateId=s.stateId AND countyName LIKE '{$match[1]}'";
						$result = $this->conn->query($query);
						}
					if($Found===0)
						{
						$Preg = "((\bcounty\b):*\s+\b([a-z]{2,20}\s+\b[a-z]{2,20}))i";
						$Found = preg_match($Preg,$this->LabelLines[$L],$match);
						if($Found > 0)
							{
							$query = "SELECT c.stateId,c.countyName,s.statename,s.countryId FROM lkupcounty c INNER JOIN lkupstateprovince s where c.stateId=s.stateId AND countyName LIKE '{$match[2]}'";
							$result = $this->conn->query($query);
							}
						}
					}
				if($result->num_rows > 0)
					{ 
					$OneLine=$result->fetch_assoc();
					//$this->printr($OneLine,"OneLine");
					$this->AddToResults('county',$OneLine['countyName'],$L); //First add the county, and remove the text from the line.
					$CountyName = $OneLine['countyName'];
					$this->LabelLines[$L] = str_replace($match[0],"",$this->LabelLines[$L]);
					$this->LabelLines[$L] = trim($this->LabelLines[$L]," :.\t,;-");
					if($result->num_rows == 1)
						{
						$this->AddToResults('stateProvince',$OneLine['statename'],$PlantsOfLine);
						$query = "SELECT countryname from lkupcountry where countryId = {$OneLine['countryId']} LIMIT 1";
						$CountryResult = $this->conn->query($query);
						$TempArray = $CountryResult->fetch_assoc();
						$this->AddToResults('country',$TempArray['countryname'],-1);
						return;
						}
					//Not sure if state and/or country on the same line.  Don't adjust RankArray.
					}
				}
			$Found = preg_match("((herbarium|university|garden|botanical)(\sof\s)(.*))i",$this->LabelLines[$L],$match);
			if($Found ===1)
				$RankArray[$L] -= 10;//Looks like institution name;

			if(stripos($this->LabelLines[$L],"country")!== false)
				$RankArray[$L] += 10;
			if($this->LineStart[$L] =='locality')
				$RankArray[$L] += 5;
			if($this->Assigned['locality'] == $L)
				$RankArray[$L] += 5;
			if($this->Assigned['identifiedBy'] == $L)
				$RankArray[$L] -= 5;
			if(count($this->LabelArray[$L])<2)
				$RankArray[$L] -= 5;
			}

		if($PlantsOf != "" || $CountyName != "")
			{//This is a pretty good giveaway.  State or country should follow this.
			//Try state first
			if($CountyName != "")
				{
				$query = "SELECT s.stateId,s.statename from lkupstateprovince s INNER JOIN lkupcounty c WHERE s.stateId=c.stateId AND c.countyname LIKE '$CountyName'";
				
				if($PlantsOf != "")
					$query .= " AND s.statename LIKE '$PlantsOf'";
				}
			else
				$query = "SELECT s.stateId,s.statename from lkupstateprovince s  WHERE stateName LIKE '$PlantsOf'";
			
			$StateResult = $this->conn->query($query);
			if(!$StateResult)
				return;
			if($StateResult->num_rows == 0 && $PlantsOf != "")
				{ //Perhaps the first word after "Plants of" is the state name.
				$SingleWordState = explode(" ",$PlantsOf);
				$query = str_replace($PlantsOf, $SingleWordState[0], $query);
				$StateResult = $this->conn->query($query);
				}
				
			if($StateResult->num_rows > 0)
				{
				if($this->GetStateFromList($StateResult, $PlantsOfLine))
					return;
				
				}
			
			}
			// End of routine that uses "Plant Of" and/or county.  Either doesn't have "Plant Of", or County, or using them failed.
			
		return;
		
		//Neither Plants of nor county found.  Begin a general search.
		//Reverse sort the Rank array to bring the most likely lines to the beginning.
		asort($RankArray);
		$RankArray = array_reverse($RankArray, true);

		
		foreach($RankArray as $L=>$Value)
			{//First look for the country or state at the beginning of the lines, a common place to find it
			if($Value < 1 || count($this->LabelArray[$L]) < 2)
				continue;
			if($this->CheckOneCountry('country',$L,$this->LabelArray[$L][0],$this->LabelArray[$L][1]))
				{//If found country, look next for a member state.
				$this->GetStateProvince($Results['country'],$L);
				return; //Found country and maybe state, so return.
				}
			if($this->CheckOneCountry('stateProvince',$L,$this->LabelArray[$L][0],$this->LabelArray[$L][1]))
				{
				return;
				}
			}
		foreach($RankArray as $L=>$Value)
			{//Look for country deeper into lines.  Slower, so we checked the first word first above.
			if($Value < 1)
				continue;
			for($W=0;$W<count($this->LabelArray[$L])-1;$W++)
				{
				if($this->CheckOneCountry('country',$L,$this->LabelArray[$L][$W],$this->LabelArray[$L][$W+1]))
					{
					$this->GetStateProvince($this->Results['country'],$L);
					return;
					}
				}
			for($W=0;$W<count($this->LabelArray[$L])-1;$W++)
				{
				if($this->CheckOneCountry('stateProvince',$L,$this->LabelArray[$L][$W],$this->LabelArray[$L][$W+1]))
					{
					$this->GetStateProvince($this->Results['country'],$L);
					return;
					}
				}
			}
		}

		
	private function GetStateFromList($StateResult, $L)
		{
		//$StateResult is a mysql result from a query.  Has one or more potential states
		//echo "Getting state from list<br>";
		global $Label;
		$OneState = $StateResult->fetch_assoc();
		//$this->printr($OneState,"OneState");
		$this->AddToResults('stateProvince', $OneState['statename'],$L);
		$query = "SELECT c.countryname FROM lkupcountry c INNER JOIN lkupstateprovince s where s.countryId=c.countryId AND s.stateId={$OneState['stateId']}";
		$CountryResult = $this->conn->query($query);
		$OneCountry = $CountryResult->fetch_assoc();
		$CountryArray[] = $OneCountry['countryname'];
		if($StateResult->num_rows == 1)
			{ //Only one result.  Accept it.
			$this->AddToResults('stateProvince',$OneState['statename'],$L);
			$this->AddToResults('country', $OneCountry['countryname'],-1);
			return true;
			}
		else 
			{//More than one result.  See if the Country name is on the label.  
			while($OneState = $StateResult->fetch_assoc())
				{
				$query = "SELECT c.countryname FROM lkupcountry c INNER JOIN lkupstateprovince s where s.countryId=c.countryId AND s.stateId={$OneState['stateId']}";
				$CountryResult = $this->conn->query($query);
				$TempArray = $CountryResult->fetch_assoc();
				$CountryArray[] = $TempArray['countryname'];
				}
			//$this->printr($CountryArray,"State Array");
			foreach($CountryArray as $Country)
				{//Look for each country name on the label.
				if(preg_match("(\b$Country\b)i", $Label))
					{
					$this->AddToResults('country',$Country, -1);
					return true;
					}
				}
			//Country name not on label.  Default to USA if this state has the name of one of the 50
			if(count(preg_grep("(United States|USA)", $CountryArray)) > 0)
				{
				$this->AddToResults("country","United States",-1);
				//echo "Default to USA<br>";
				return true;
				}
			}
		}

		
	//**********************************************
	private function CheckOneCountry($Field,$L,$Word1,$Word2="")
		{//Can check for either state or country
		$Word1=trim($Word1,":.,;");
		if(!ctype_alpha($Word1))
			return false;
		$Num=0;
		//If we already have a country and are looking for the state/province:
		if($Field == 'stateProvince' && $this->Results['country'] != "")
			$queryEnd = " AND country LIKE '{$this->Results['country']}' LIMIT 5";
		else
			$queryEnd = " LIMIT 5";
			
		if($Word2 != "" && ctype_alpha($Word2))
			{//Look for two word countries/states
			$query = "Select country,stateProvince from omoccurrences where $Field LIKE '$Word1 $Word2' $queryEnd";
			//echo $query."<br>";
			$result = $this->conn->query($query);
			$Num = $result->num_rows;
			}
		if($Num <5)//If valid country, there should be more than 5 hits in the whole database...
			{//Look for one-word countries/states
			$query = "Select country,stateProvince from omoccurrences where $Field LIKE '$Word1' $queryEnd";
			$result = $this->conn->query($query);
			$Num = $result->num_rows;
			}
		if($Num > 4)
			{//Require a minimum number of hits to ensure it's not a database error (it happens!)
			 // Maybe 9 is more than necessary.  5?  3? (in the interest of speed)
			$OneReturn = $result->fetch_assoc();
			$this->AddToResults('country', $OneReturn['country'],$L);
			if($Field=='stateProvince')
				$this->AddToResults('stateProvince', $OneReturn['stateProvince'],$L);
			return true;
			}
		
		}
	

	//**********************************************
	private function County($Country,$State)
		{
		global $Label;
		$match=array();
		$Found = preg_match("((\b[A-Z][a-z]{1,20}\b)?[\s]*(\b[A-Z][a-z]{1,20}\b)[\s]*(\b(County|Co)\b))",$Label,$match);
		if($Found ===1)
			{
			$County = trim($match[1]." ".$match[2]);
			$query = "SELECT county FROM omoccurrences WHERE country LIKE '$Country' AND stateProvince LIKE '$State' and county LIKE '$County'";
			$result = $this->conn->query($query);
			if($result->num_rows == 0)
				{
				$County = trim($match[2]);
				$query = "SELECT county FROM omoccurrences WHERE country LIKE '$Country' AND stateProvince LIKE '$State' and county LIKE '$County'";
				$result = $this->conn->query($query);
				}
			if($result->num_rows > 0)
				{
				$M = trim($match[0]);
				$this->AddToResults('county',$County,0);
				$Preg = "($M)";
				$LArray = preg_grep($Preg,$this->LabelLines);
				if(count($LArray) > 0)
					{
					$L = key($LArray);
					$this->LabelLines[$L] = trim(str_replace($M,"",$this->LabelLines[$L])," \t:;,.");
					}
				return;
				}
			}
		//Didn't find it from the word "County".  Search the full label for a county that is in the given country/state.
		$query = "SELECT stateid FROM lkupstateprovince WHERE stateName LIKE '$State'";
		$Stateresult = $this->conn->query($query);
		if($Stateresult->num_rows > 0)
			{
			$CountyArray = array();
			while($OneLine = $Stateresult->fetch_assoc())
				{
				$query = "SELECT countyname FROM lkupcounty WHERE stateid LIKE '{$OneLine['stateid']}'";
				$Countyresult = $this->conn->query($query);
				if($Countyresult->num_rows > 1)
					{
					while($Cty = $Countyresult->fetch_assoc())
						{
						if(stripos($Label,$Cty['countyname'])!==false)
							{
							$CountyArray[] = $Cty['countyname'];
							if(strpos($Cty['countyname']," ") !== false)
								break;
							}
						}
					}
				}
			}
		if(count($CountyArray) > 0)
			{//Sort results so the longest is first. 
			
			$lengths = array_map('strlen', $CountyArray);
			array_multisort($lengths,SORT_DESC,$CountyArray);
			$CountyArray = array_values($CountyArray);
			$this->AddToResults('county',$CountyArray[0],0);
			}
		}
	
	//******************************************************************
	function GetStateProvince($Country, $FLine)
		{//Called from GetCountryState. $FLine indicates line where country was found. 
		//global $LabelArray,$Results,$LabelLines;
		$Synonyms = array("Brasil"=>"Brazil","U.S.A."=>"USA","United States"=>"USA");

		//Rank lines by probability.
		$RankArray = array();
		$RankArray = array_fill(0, count($this->LabelArray),0);
		$RankArray[$FLine] = 5; //Assume state will be near country.
		if($FLine < count($this->LabelArray)-2)
			$RankArray[$FLine+1] = 4;
		if($FLine < count($this->LabelArray)-3)
			$RankArray[$FLine+2] = 3;
		if($FLine > 0)
			$RankArray[$FLine-1] = 2;
		for($L=0;$L<count($this->LabelLines);$L++)
			{ //If certain words appear, then much more likely state is there.
			if(strpos(strtolower($this->LabelLines[$L]),"state")!== false)
				{
				$RankArray[$L] = 10;
				if(strpos(strtolower($this->LabelLines[$L]),"state of")!== false)
					$RankArray[$L] += 20;
				if(strpos(strtolower($this->LabelLines[$L]),"state university")!== false)
					$RankArray[$L] = 0; //Probably just indicates which university has the specimen, not where it was found
				}
			if(strpos(strtolower($this->LabelLines[$L]),"province")!== false)
				$RankArray[$L] = 10;
			}
		asort($RankArray);
		$RankArray = array_reverse($RankArray, true);

		foreach($RankArray as $L=>$Value)
			{
			if($RankArray[$L] <=0)
				continue;
			for($W=0;$W<count($this->LabelArray[$L]);$W++)
				{
				$Word = $this->LabelArray[$L][$W];
				if(strlen($Word) < 3)
					continue;
				if(strtolower($Word) === strtolower($Country))
					continue;
				$Num=0;
				if($W<count($this->LabelArray[$L])-1)
					{
					$Word2 = $this->LabelArray[$L][$W+1];
					if(strlen($Word) < 3 && strlen($Word2) < 3)
						continue;
					if(!ctype_alpha($Word) || !ctype_alpha($Word2))
						continue;
					$query = "Select country,stateProvince from omoccurrences where country LIKE '$Country' AND stateProvince LIKE '$Word $Word2' LIMIT 10";
					$result = $this->conn->query($query);
					$Num = $result->num_rows;
					if($Num < 10 && isset($Synonyms[$Country]))
						{
						$query = "Select country,stateProvince from omoccurrences where country LIKE '{$Synonyms[$Country]}' AND stateProvince LIKE '$Word $Word2' LIMIT 10";
						$result = $this->conn->query($query);
						$Num = $result->num_rows;
						}
					}
				if($Num <10)
					{
					if(strlen($Word) < 3)
						continue;
					if(!ctype_alpha($Word))
						continue;
					$query = "sElect country,stateProvince from omoccurrences where country LIKE '$Country' AND stateProvince LIKE '$Word' LIMIT 10";
					$result = $this->conn->query($query);
					$Num = $result->num_rows;
					if($Num <10 && isset($Synonyms[$Country]))
						{
						$query = "sElect country,stateProvince from omoccurrences where country LIKE '{$Synonyms[$Country]}' AND stateProvince LIKE '$Word' LIMIT 10";
						$result = $this->conn->query($query);
						$Num = $result->num_rows;
						}
					}
				if($Num > 9)
					{
					$OneReturn = $result->fetch_assoc();
					$this->AddToResults('stateProvince', $OneReturn['stateProvince'],$L);
					return;
					}
				}
			}
		}


//************************************************************************************************************************************
//******************* Word Stat functions ********************************************************************************************
//************************************************************************************************************************************
	

	//******************************************************************
	private function GetWordStatFields()
		{
		$OneLine = array();
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$Max = array();
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$Skip=false;
			foreach(array("recordedBy","family","identifiedBy","associatedCollectors","sciname","infraspecificEpithet") as $F)
				{//Don't bother scoring if this line has start words or has already been assigned.
				if($this->LineStart[$L] ==$F)
					$Skip=true;
				if($this->Assigned[$F] == $L)
					$Skip=true;;
				}
			if(preg_match("([a-z])",$this->LabelLines[$L]) === 0)
				{//Usually not all upper case
				$Skip=true;
				}
			if($this->StatScore[$L]['Score'] < 50)
				$Skip = true;  //Already measured this line and it falls short.
			if($Skip)
				{
				continue;
				}
			$this->ScoreOneLine($L, $Field, $Score);// Field and Score are called by reference.
			if($Score > 50)
				{//Score is high enough (though limit is empirical).  Add line to highest scoring field.
				//NOTE:  May want to look into breaking a line in the middle if it changes fields partway through.
				//Example:  Small herb with yellow flowers growing beside the road.  (verbAttr followed by habitat or locality)
				//However, this is very hard to do reliably.
				$this->RemoveStartWords($L,$Field);
				if($this->Results[$Field] != "")
					$this->Results[$Field] .= ", ".trim($this->LabelLines[$L]); //Append
				else
					$this->Results[$Field] = trim($this->LabelLines[$L]);
				}
			}
		return;			
		}

	private function ScoreOneLine($L, &$Field, &$Score)
		{ //Score a line for wordstats
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$StartField = "";
		foreach($Fields as $F)
			{
			if($this->LineStart[$L] == $F)
				{
				$Field = $F;
				$Score = 1000;
				return;
				}
			}
		$this->ScoreString($this->LabelLines[$L],$Field, $Score);
		return;
		}
		
	private function ScoreString($TempString,&$Field, &$Score)
		{//Called by ScoreOneLine and also by associated species routine
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$BadWords = "(\b(copyright|herbarium|garden|database)\b)i";
		$ScoreArray  = array_fill_keys($Fields,0);
		if(preg_match($BadWords,$TempString) > 0)
			return;
		$match=array();
		$Found = preg_match_all("(\b\w{2,20}\b)",$TempString,$match);
		if($Found == 0)
			return;
		else
			$WordsArray = $match[0];
		for($W=0;$W<count($WordsArray);$W++)
			{//Look for each word, and each two word combination in the salix word stats table
			//First look for single word
			$query = "Select * from salixwordstats where firstword like '{$WordsArray[$W]}' AND secondword IS NULL LIMIT 3";
			$result = $this->conn->query($query);
			$num1 = $result->num_rows;
			if($num1 > 0)
				{
				while($Values = $result->fetch_assoc())
					{
					$Factor = 1;
					if($Values['totalcount'] < 10) //Reduce impact if only seen few times
						$Factor = ($Values['totalcount'])/10;
					foreach($Fields as $F)
						$ScoreArray[$F] += $Factor * $Values[$F.'Freq'];
					}
				}
			if(strtolower($WordsArray[$W]) == "on")
				{
				if(array_search($this->Family,$this->LichenFamilies)!==false)
					{
					$ScoreArray['substrate'] += 1000;
					}
				}
			if($W < count($WordsArray)-1)
				{//Look for two-word combinations.  Score with more weight than single words -- 5X as I write this comment.
				$query = "SELECT * from salixwordstats where firstword like '{$WordsArray[$W]}' AND secondword LIKE '{$WordsArray[$W+1]}' LIMIT 3";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					{
					while($Values = $result->fetch_assoc())
						{
						$Factor = 1;
						if($Values['totalcount'] < 10) //Reduce impact if only seen few times
							$Factor = ($Values['totalcount'])/10;
						foreach($Fields as $F)
							$ScoreArray[$F] += $Factor*5*$Values[$F.'Freq'];
							}
					}
				}
			}
		asort($ScoreArray);
		end($ScoreArray); //Select the last (highest) element in the scores array
		$Field = key($ScoreArray); //Maximum field
		$Score = floor($ScoreArray[$Field]/count($WordsArray));
		}

		
	//**********************************************
	private function SingleWordStats($Words, $Field="All")
		{//Used mainly for non-word stats fields to adjust their probability.
		//e.g. if I think it might be a collector's name but the line scores high for locality, then probably a place name instead
		
		$Preg = "(\b[A-Za-z]{2,20}\b)";
		$match = array();
		$Found = preg_match_all($Preg,$Words,$match);
		if($Found === 0)
			return 0;
		$Word1 = $match[0][0];
		$Score=0;
		
		if(count($match[0]) > 1)
			{
			$Word2 = $match[0][1];
			$query = "SELECT * from salixwordstats where firstword like '$Word1' AND secondword LIKE '$Word2' LIMIT 3";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				$Score += 5*$this->ScoreWordStatResult($result,$Field);
			}
		$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL LIMIT 3";
		$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL LIMIT 3";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			$Score += $this->ScoreWordStatResult($result,$Field);
		return $Score;
		}
	
	private function ScoreWordStatResult($result,$Field)
		{//Called from SingleWordStats above
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate"); 
		$Score = 0;
		while($Values = $result->fetch_assoc())
			{
			$Factor = .1;
			if($Values['totalcount'] < 10) //Reduce impact if only in database a few times
				$Factor = ($Values['totalcount'])/100;
			if($Field=="All")
				foreach($Fields as $F)
					$Score += $Factor * $Values[$F.'Freq'];
			else	
					$Score += $Factor * $Values[$Field.'Freq'];
			}
		return $Score;
		}
	
		
//************************************************************************************************************************************
//******************* Misc Functions *************************************************************************************************
//************************************************************************************************************************************
		
	//******************************************************************
	private function AddToResults($Field, $String, $L)
		{ //Set $Field results to $String.  Mark Line $L as used for this field
		if($Field == "recordedBy" && $this->Results['recordedBy'] != "")
			{//Separate associated collectors from recordedBy
			$this->AddToResults('associatedCollectors',$String,$L);  //Recursive call
			return;
			}
		if($Field == "associatedCollectors")
			{
			if($this->Assigned['sciname'] == $L || $this->Assigned['taxonRank'] == $L)
				return;//Probably picking up the author instead of an associated collector
			else if($this->Assigned['recordedBy'] > $L)
				return;//Associated collectors should always be after the main collector
			}
		if($L>=0)
			$this->Assigned[$Field] = $L;
		$String = trim($String);
		if(strpos("xassociatedCollectors,identifiedBy,associatedTaxa",$Field) > 0 && $this->Results[$Field] != "")
			$this->Results[$Field] .= "; ".$String;  //Append
		else
			$this->Results[$Field] = $String;
		}
		
		
	//**********************************************
	private function InitStartWords()
		{//Fill the StartWords array
		$this->PregStart['family'] = "(^(family)\b)i";
		$this->PregStart['recordedBy'] = "(^(collected by|collectors|collector|collected|coll|col|leg|by)\b)i";
		$this->PregStart['eventDate'] = "(^(EventDate|Date)\b)i";
		$this->PregStart['recordNumber'] = "(^(number|coll)\b)i";
		$this->PregStart['identifiedBy'] = "(^(determined by|determined|det|identified by|identified)\b)i";
		$this->PregStart['associatedCollectors'] = "(^(with|and|&)\b)i";
		$this->PregStart['habitat'] = "(^(habitat)\b)i";
		$this->PregStart['locality'] = "(^(locality|location|loc)\b)i";
		$this->PregStart['substrate'] = "(^(substrate)\b)i";
		$this->PregStart['country'] = "(^(country)\b)i";
		$this->PregStart['stateProvince'] = "(^(state|province)\b)i";
		$this->PregStart['county'] = "(^(county|parish)\b)i";
		$this->PregStart['minimumElevationInMeters'] = "(^(elevation|elev|altitude|alt)\b)i";
		$this->PregStart['associatedTaxa'] = "(^(assoc|growing with|associated taxa|associated with|associated plants|associated spp|associated species|associated|other spp)\b)i";
		$this->PregStart['infraspecificEpithet'] = "(^(ssp|variety|subsp)\b)i";
		$this->PregStart['occurrenceRemarks'] = "(^(notes)\b)i";
		$this->PregStart['ignore'] = "(^(synonym)\b)i";
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$this->LineStart[$L] = "";
			foreach($this->PregStart as $Field=>$Val)
				{
				if($this->CheckStartWords($L,$Field))
					{
					$this->LineStart[$L] = $Field;
					break;
					}
				}
			}
		//$this->printr($this->LineStart,"Line Start");
		}

		
	//**********************************************
	private function CheckStartWords($L, $Field)
		{//Returns true if Line $L starts with any start word from $Field
		if($this->PregStart[$Field] == "" )
			return false;
		if($L >= count($this->LabelArray))
			return false;
		if(count($this->LabelArray[$L]) < 2)
			return false;
		$Found = preg_match($this->PregStart[$Field], $this->LabelLines[$L]);
		if($Found === 1)
			{
			return true;
			}
		else
			return false;
		}

	private function RemoveStartWords($L, $Field)
		{//Clean up a field by removing any start words.  Example:
		//"Collector Les Landrum" would have "Collector" removed from the line
		$match=array();
		if($this->PregStart[$Field] == "" )
			return false;
		if($L >= count($this->LabelArray))
			return false;
		if(count($this->LabelArray[$L]) < 2)
			return false;
		$Found = preg_match($this->PregStart[$Field], $this->LabelLines[$L],$match);
		if($Found === 1)
			{
			$this->LabelLines[$L] = str_ireplace($match[0],"",$this->LabelLines[$L]);
			$this->LabelLines[$L] = trim($this->LabelLines[$L],": ,;-.\t");
			}
		}
		
		
	//**********************************************
	private function MakePregWords($Ain, $Start=false)
		{//Convert an array into a regular expression all separated by | (or).
		//If Start is set, then require the words be at the start of the line.
		//Initially used to easily convert some of my C++ arrays into regular expressions.
		//Kept because it simplifies creating a long regular expression with many or statements
		$Preg="";
		if($Start)
			$S = "\b|^";
		else
			$S = "\b|\b";
		$Preg = "(".substr($S,3).implode($S,$Ain)."\b)";;
		return $Preg;
		}

		
	//**********************************************
	private function getRawOcr($prlid){
		$retStr = '';
		if(is_numeric($prlid)){
			//Get raw OCR string
			$sql = 'SELECT rawstr '.
				'FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retStr = $r->rawstr;
			}
			$rs->free();
		}
		return $retStr;
	}

	private function GetLichenNames()
		{
		$this->LichenFamilies = array("Acarosporaceae","Adelococcaceae","Agyriaceae","Anamylopsoraceae","Anziaceae","Arctomiaceae","Arthoniaceae","Arthopyreniaceae","Arthrorhaphidaceae","Aspidotheliaceae","Atheliaceae","Baeomycetaceae","Biatorellaceae","Bionectriaceae","Brigantiaeaceae","Caliciaceae","Candelariaceae","Capnodiaceae","Catillariaceae","Cetradoniaceae","Chionosphaeraceae","Chrysothricaceae","Cladoniaceae","Clavulinaceae","Coccocarpiaceae","Coccotremataceae","Coenogoniaceae","Collemataceae","Coniocybaceae","Coniophoraceae","Crocyniaceae","Dacampiaceae","Dactylosporaceae","Didymosphaeriaceae","Epigloeaceae","Fuscideaceae","Gloeoheppiaceae","Gomphillaceae","Graphidaceae","Gyalectaceae","Gypsoplacaceae","Haematommataceae","Helotiaceae","Heppiaceae","Herpotrichiellaceae","Hyaloscyphaceae","Hygrophoraceae","Hymeneliaceae","Hyponectriaceae","Icmadophilaceae","Lahmiaceae","Lecanoraceae","Lecideaceae","Lepidostromataceae","Letrouitiaceae","Lichenotheliaceae","Lichinaceae","Lobariaceae","Loxosporaceae","Mastodiaceae","Megalariaceae","Megalosporaceae","Megasporaceae","Melanommataceae","Melaspileaceae","Microascaceae","Microcaliciaceae","Microthyriaceae","Monoblastiaceae","Mycoblastaceae","Mycocaliciaceae","Mycosphaerellaceae","Mytilinidiaceae","Myxotrichaceae","Naetrocymbaceae","Nectriaceae","Nephromataceae","Niessliaceae","Nitschkiaceae","Obryzaceae","Odontotremataceae","Ophioparmaceae","Pannariaceae","Parmeliaceae","Parmulariaceae","Peltigeraceae","Peltulaceae","Pertusariaceae","Phlyctidaceae","Phyllachoraceae","Physciaceae","Pilocarpaceae","Placynthiaceae","Platygloeaceae","Pleomassariaceae","Pleosporaceae","Porinaceae","Porpidiaceae","Protothelenellaceae","Pseudoperisporiaceae","Psoraceae","Pucciniaceae","Pyrenotrichaceae","Pyrenulaceae","Ramalinaceae","Requienellaceae","Rhizocarpaceae","Roccellaceae","Schaereriaceae","Solorinellaceae","Sphaerophoraceae","Sphinctrinaceae","Stereocaulaceae","Stictidaceae","Strigulaceae","Syzygosporaceae","Teloschistaceae","Thelenellaceae","Thelocarpaceae","Thelotremataceae","Tremellaceae","Tricholomataceae","Trypetheliaceae","Umbilicariaceae","Verrucariaceae","Vezdaeaceae","Xanthopyreniaceae");
		}
	
	
	//**********************************************
	private function getWordFreq(){
		$sql = 'SELECT firstword, secondword, locality, localityfreq, habitat, habitatFreq, substrate, substrateFreq, '.
			'verbatimAttributes, verbatimAttributesFreq, occurrenceRemarks, occurrenceRemarksFreq, totalcount, datelastmodified '.
			'FROM salixwordstats '.
			'WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			//Not sure if this is the best way organize the word stats, but it's an idea
			$this->wordFreqArr[$r->firstword][$r->secondword]['loc'] = $r->localityfreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['hab'] = $r->habitatFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['sub'] = $r->substrateFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['att'] = $r->verbatimAttributesFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['rem'] = $r->occurrenceRemarksFreq;
		}
		$rs->free();
	}
	
	//**********************************************
	private function rebuildWordStats(){
		$salixHandler = new SalixUtilities();
		$salixHandler->setVerbose(0);
		$salixHandler->buildWordStats($this->collId, 1);
	}

	//**********************************************
	private function printr($A, $Note="omit")
	{//Enhances the print_r routine by adding an optional note before and a newline after.
	if(!is_array($A))
		echo "Not array";
	else
		{
		if($Note != "omit")
			echo $Note.": ";
		print_r($A);
		}
	echo "<br>";
	}


}
?>