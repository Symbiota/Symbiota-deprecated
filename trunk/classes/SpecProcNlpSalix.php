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
	private $FamilySyn = array();
	private $StatScore = array();
			//The wordstats score of each line
	private $LineStart = array();
			//One element for each line indicating if it starts with a start word.
	private $Label = "";
	
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
	public function parse($RawLabel) 
		{
		$this->Label = $RawLabel;
		if(strlen($this->Label) < 20)
			return $this->Results;
		$dwcArr = array();
		//Add the SALIX parsing code here and/or in following functions of a private scope
		//Set the keys for a few arrays to the names of the return fields
		$this->Results = array_fill_keys($this->ResultKeys,'');
		$this->Assigned = array_fill_keys($this->ResultKeys,-1);
		$this->PregStart = array_fill_keys($this->ResultKeys,'');
		$this->GetLichenNames(); //Get list of Lichen families to help find substrates
		$this->GetFamilySyn();
		//****************************************************************
		// Do some preformatting on the input label text
		//****************************************************************
		//for($i=0;$i<strlen($this->Label);$i++)
		//	if(ord($this->Label[$i]) >122)
		//		echo ord($this->Label[$i]).", ".substr($this->Label, $i-5,10)."<br>";;
		
		for($i=0;$i<strlen($this->Label)-1;$i++)
			if(ord($this->Label[$i]) == 195 && ord($this->Label[$i+1]) == 173)
				$this->Label = substr($this->Label,0,$i)."i".substr($this->Label,$i+2);
		setlocale(LC_ALL,"en_US");
		$this->Label = str_replace("í","i",$this->Label);
		$this->Label = str_replace("’","'",$this->Label);
		if(strpos($this->Label,"°") > 0)
			{
			$this->Label = str_replace("°","17*17",$this->Label);
			}
		if(mb_detect_encoding($this->Label,'UTF-8') == "UTF-8")
			{
			$this->Label = iconv("UTF-8","ISO-8859-1//TRANSLIT",$this->Label);
			$this->Label = str_replace("í","i",$this->Label);
			}
		$this->Label = str_replace("17*17","°",$this->Label);
		//echo $this->Label."<br>**_*_*_*_*_*_*_*_*_<br>";
		//A few replacements to format the label making it easier to parse
		
		//Make sure space between NSEW cardinal directions and following digits, for Lat/Long 
		$this->Label = preg_replace("(([NESW]\.?)(\d))","$1 $2",$this->Label);
		$this->Label = preg_replace("((\d)([NESW]))","$1 $2",$this->Label);
		//Make sure space between altitude and indicator (ft or m)
		$this->Label = preg_replace("((\d)((m|ft)(\s|.)))","$1 $2",$this->Label);
		//Make sure space between period and capital letter, such as an initial followed by a name.
		$this->Label = preg_replace("((\.)([A-Z]))","$1 $2",$this->Label);

		//Remove double spaces and tabs
		$this->Label = str_replace("  "," ",$this->Label);
		$this->Label = str_replace("\t"," ",$this->Label);
		//Remove (?) <>.  Perhaps should leave in...?  Would a collector ever write e.g. "Found > 500 m. elevation"? "Collected < 100 meters from stream"?
		$this->Label = str_replace("<","",$this->Label);
		$this->Label = str_replace(">","",$this->Label);
		$this->Label = str_replace("^"," ",$this->Label);
		$this->Label = str_replace("’","'",$this->Label);
		$this->Label = str_replace("°;","°",$this->Label);
		
		
		
		$match = array();
		$FromTo = array("l"=>"1","O"=>"0");
		foreach($FromTo as $From=>$To)
			{
			//$Preg = "lsk";
			$Preg = "([-0-9/.]".$From."[0-9/.])";
			do
				{
				$Found = preg_match($Preg,$this->Label,$match);
				if($Found)
					{
					$NewMatch = str_replace($From,$To,$match[0]);
					$this->Label = str_replace($match[0],$NewMatch,$this->Label);
					//$this->printr($match,"$From Digit Error");
					}
			
				}while($Found);
			}
		//Separate at semicolons
		$this->Label = str_replace(";","\r\n",$this->Label);
		
		//Separate lines at a few start words
		foreach(array("Collected by:","Collector:","Date:","Det.","Altitude:","Altitude about","Determined by","Determiner") as $SplitPoint)
			{
			$this->Label = str_ireplace("$SplitPoint","\r\n$SplitPoint",$this->Label);
			}

		//Remove empty lines
		$this->Label = str_replace("\r\n\r\n","\r\n",$this->Label);
		
		$this->LabelLines = preg_split("[(?<=[a-z]{3,3})\.|(?<=[a-z]);|\n]",$this->Label,-1,PREG_SPLIT_DELIM_CAPTURE);
		//regex expression to split lines at semicolons and periods.  But not at periods unless the preceding is
		//at least 3 lower case letters.  This preserves abbreviations.  
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
			$WordsArray = str_word_count($Words,1);
			//$WordsArray = str_word_count($Words,1,"0123456789-&."); // Break sentence into words
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
		//*************************************************************
		//Here's where the individual fields get called and hopefully filled
		//echo("Label = $this->Label");
		//echo "Count = ".count($this->LabelLines)."<br>";
		
		//Order is important.  
		//Easy-to-find fields are done first, and then removed so as to not confuse other fields.
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
		//echo $this->Label."<br>";
			
		return $this->Results;
		
		
	}

//************************************************************************************************************************************
//***************** Scientific Name Functions ****************************************************************************************
//************************************************************************************************************************************

	//**********************************************
	private function GetScientificName()
		{
		//Should not be after the associated Taxa.  Do a check afterwards.
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
				//echo "1) {$match[1]} {$match[2]}, Score = $Score<br>";
				$FoundAuthor = preg_match("((\A[A-Z][a-z]{3,20}) ([a-z]{4,20}\b)[\S\s]+(\b[A-Za-z]{1,20}\.))", $this->LabelLines[$L]);
				if($FoundAuthor)//Looks like an author at the end
					$Score += 5;
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
			//echo "First, $Score,  {$this->LabelLines[$L]}<br>";
						
			if(preg_match("(\([A-Z][a-z\.])",$this->LabelLines[$L]) === 1)
				{
				$Score += 5; //Could be the author
				}
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
					//echo "PosD = $PosDeduction for {$this->LabelLines[$L]}<br>";
					}
				else
					$PosDeduction = $L-4;//Or reduce the score if far from line 3, which is about where the Family is usually listed.
				if($PosDeduction > 3)
					$PosDeduction = 3;
				
				$Score -= $PosDeduction;
				$ScoreArray[$match[0]] = $Score;
				//echo "$Score,  {$match[0]}<br>";
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
		//$this->printr($ScoreArray,"SciName");	
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
			$Found = preg_match("((\b\w{2,20}(aceae))\s(of)\s(.*))i",$this->LabelLines[$L],$match);
			if($Found === 1)
				{
				//$this->printr($match,"ACEAE match:");
				$query = "SELECT family from omoccurrences WHERE family LIKE '{$match[1]}' LIMIT 1";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					{
					$this->Family = mb_convert_case($match[1],MB_CASE_TITLE);
					$this->AddToResults('family',$this->Family,$L);
					//echo "Family on line $L<br>";
					return;
					}
				}
			else if($PotentialFamily == "")
				{
				$Found = preg_match("((\b\w{2,20}(aceae))\b)i",$this->LabelLines[$L],$match);//Replace above with case insensitive version
				if($Found === 1)
					{ //If not found as "...aceae of...", then use the first "...acea..." found.
					//$this->printr($match,"ACEAE match:");
					$query = "SELECT family from taxstatus WHERE family LIKE '{$match[1]}' LIMIT 1";
					$result = $this->conn->query($query);
					if($result->num_rows > 0)
						{
						$PotentialFamily = mb_convert_case($match[1],MB_CASE_TITLE);
						$FamilyLine = $L;
						//echo "Found Family on line $L<br>";
					
						}
					}
				}
			}
			//If something better was found, we won't get to this point.  But if we do, then use the most likely alternative
			if($PotentialFamily != "")
				{
				$this->Family = $PotentialFamily;
				$this->AddToResults('family',$PotentialFamily,$FamilyLine);
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
		//echo "Query=$query<br>";
		$result = $this->conn->query($query);
		if(!is_object($result))
			return 0;
		if($result->num_rows == 1)
			{//Found perfect match
			//echo "Perfect match<br>";
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
		if($Score < 10 && strlen($Second) > 3)
			{//Look for the specific epithet only
			$query = "SELECT sciname from taxa WHERE UnitName2 LIKE '$Second' LIMIT 1";
			if(!$IgnoreFamily && $this->Family != "")
				$query = "SELECT ts.family, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.UnitName2 LIKE '$Second' AND ts.family like '{$this->Family}' LIMIT 1";
			//echo $query."<br>";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				return 5;
			}
		if(!$IgnoreFamily && $this->Family != "")
			{//If we are already sure about the family, then check if the selected sciname is actually in that family.
			$query = "SELECT ts.family, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname LIKE '$CloseSci' AND ts.family like '{$this->Family}' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows == 0)
				{
				if(isset($this->FamilySyn[$this->Family]))
					{
					$Fam = $this->FamilySyn[$this->Family];
					$query = "SELECT ts.family, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname LIKE '$CloseSci' AND ts.family like '$Fam' LIMIT 1";
					$result = $this->conn->query($query);
					if($result->num_rows != 0)	
						return $Score;
					}
				//Deduct 20 for not in known family";
				//echo "Not in family<br>";
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
				$this->LabelLines[$L] = str_ireplace($SciName,"",$this->LabelLines[$L]);
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
		
	private function GetAssociatedTaxa()
		{
		$SplitPoints = array();
		$RankArray = $this->RankAssociatedTaxa();
		if(count($RankArray) == 0)
			return;
		//$this->printr($RankArray,"AssTaxaRank");
		//All non-asociated taxa lines have been removed from RankArray
		reset($RankArray);
		$Start = key($RankArray);
		end($RankArray);
		$End = key($RankArray);
		while($End >= count($this->LabelLines))
			$End--;
		//foreach($RankArray as $L=>$Value)
		for($L=$Start;$L<=$End;$L++)
			{
			//echo "Rank $Value, {$this->LabelLines[$L]}<br>";
			$this->RemoveStartWords($L,'associatedTaxa');
			$TempString = $this->LabelLines[$L];
			$SciString = $TempString;
			if($L == $Start || $L == $End)
				{
				$SplitPoints = $this->SplitString($TempString, $Field, $Score1);
				if($SplitPoints[1] > 0)
					{
					//$this->printr($SplitPoints,"SplitPoints");
					if($End > $Start)
						{
						if($L==$Start)
							$SplitPoints[1] = strlen($TempString);
						else if($L == $End)
							$SplitPoints[0] == 0;
						}
					$SciString = substr($TempString,$SplitPoints[0],$SplitPoints[1]-$SplitPoints[0]); //Indicates the point to split apart.
					$this->LabelLines[$L]=str_replace($SciString,"",$this->LabelLines[$L]);
					//echo "Now: {$this->LabelLines[$L]}<br>";
					}
				}

			//$SciString = $this->RemoveStartWordsFromString($TempString,'associatedTaxa');
			//echo "Adding $SciString to AT<br>";
			$this->AddToResults("associatedTaxa", $SciString,$L);
			$this->LabelLines[$L] = str_replace($SciString,"",$this->LabelLines[$L]);
			$this->LabelLines[$L] = str_ireplace("with","",$this->LabelLines[$L]);
			//$this->LabelLines[$L] = "";
			}
		}
		

	private function SplitString($TempString, &$Field, &$Score)
		{ //Find where an Associated Species series ends and something else begins.  Only works for wordstats fields.
		
		$Found = preg_match_all("(([w|W]ith) ([A-Z][a-z]{4,20} ([a-z]{4,20})))",$TempString, $match, PREG_OFFSET_CAPTURE); 
		if($Found)
			return array($match[2][0][1],strlen($TempString));
		
		$ScoreArray = array();
		$Found = preg_match_all("(\b[A-Za-z]{1,20}\b)",$TempString,$WordsArray, PREG_OFFSET_CAPTURE);//Break the string up into words
		$FieldScoreArray = array();
		$SciScoreArray=array();
		
		for($w=0;$w<count($WordsArray[0])-1;$w++)
			{
			$Loc = $WordsArray[0][$w][1];
			if(trim($WordsArray[0][$w+1][0] == "sp"))
				$Phrase = $WordsArray[0][$w][0];
			else
				$Phrase = $WordsArray[0][$w][0]." ".$WordsArray[0][$w+1][0]; //Two word phrase
			$Score = $this->SingleWordStats($Phrase);//Get word stats score for these two words
			$Phrase = trim(str_replace(" sp.","",$Phrase));
			$Phrase = trim(str_replace(" sp ","",$Phrase));
			//echo "Phrase = $Phrase, Score=$Score, ";
			$FieldScoreArray[$w] = $Score;
			$SciScore = 0;
			if(preg_match("([A-Z][a-z]{3,20} [a-z]{3,20})",$Phrase))
				{
				$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$Phrase' LIMIT 1";
				//echo $query."<br>";
				$result = $this->conn->query($query);
				if($result->num_rows == 1)
						$SciScore += 1;
				}
			else if(preg_match("([A-Z][a-z]{3,20})",$WordsArray[0][$w][0]))
				{
				$query = "SELECT sciname FrOM taxa WHERE UnitName1 LIKE '{$WordsArray[0][$w][0]}' LIMIT 1";
				$result = $this->conn->query($query);
				if($result->num_rows == 1)
					$SciScore += 1;
				}
			else if(preg_match("([a-z]{3,20})",$WordsArray[0][$w][0]))
				{
				$query = "SELECT sciname FrOM taxa WHERE UnitName2 LIKE '{$WordsArray[0][$w][0]}' LIMIT 1";
				$result = $this->conn->query($query);
				if($result->num_rows == 1)
					$SciScore += 1;
				}
			$SciScoreArray[$w] = $SciScore;
			//echo ", SciScore = $SciScore<br>";
			if($SciScore > 8)//It's a sciname
				$FScore = 0;
			else if($SciScore <= 0)
				$FScore = $Score+5;//It's not a sciname.
			else
				$FScore = floor($Score/$SciScore);
			if($Loc > 1 && $Loc < strlen($TempString) - 5)
				{
				if(strpbrk(substr($TempString,$Loc-2,5),".;") !== false)
					$FScore += 10;
				}
			//echo "FScore=$FScore<br>";
			$ScoreArray[$w] = $FScore;
			}

		$AverageField = 0;
		for($w=0;$w<count($FieldScoreArray)-1;$w++)
			$AverageField += $FieldScoreArray[$w];
		if(count($FieldScoreArray) == 0)
			return array(0,0);
		//echo "<br><br>";
		for($w=0;$w<count($FieldScoreArray)-1;$w++)
			{
			$SciScoreArray[$w] = ($SciScoreArray[$w] + $SciScoreArray[$w+1])/2;
			$SciScoreArray[$w] *= $AverageField;
			//echo $FieldScoreArray[$w].", ".$SciScoreArray[$w]."<br>";
			}
		$w=count($FieldScoreArray)-1;
		$SciScoreArray[$w] *= $AverageField;
		//echo $FieldScoreArray[$w].", ".$SciScoreArray[$w]."<br>";
		$Limit = 2*$AverageField/5; //Arbitrarily cut off at 3/4
		
		//$this->printr($SciScoreArray,"SSA Before pruning");
		//Now find a cluster of high numbers, hopefully on beginning or end of the string.
		foreach($SciScoreArray as $Key=>$Sci)
			if($Sci < $Limit)
				unset($SciScoreArray[$Key]);
		//$this->printr($SciScoreArray,"SSA After pruning");
		if(count($SciScoreArray) > 1) // Greater than what?
			{
			reset($SciScoreArray);
			$Start = key($SciScoreArray);
			if($Start < 4)
				$Start = 0;
			if($Start > 0)
				{
				if(preg_match("([a-z]{3,20})",$WordsArray[0][$Start][0]) && preg_match("([A-Z][a-z]{3,20})",$WordsArray[0][$Start-1][0]))
					$Start--; //It's the specific epithet, but previous word is probably the genus.
				}
			end($SciScoreArray);
			$LastKey = key($SciScoreArray);
			//echo "LastKey = $LastKey.  {$WordsArray[0][$LastKey][0]}<br>";
			//echo "Count: ".count($WordsArray[0])."<br>";
			if(count($WordsArray[0]) - $LastKey < 4)
				$End = strlen($TempString);
			else if($LastKey < count($WordsArray[0]))
				$End = $WordsArray[0][$LastKey+1][1];
			else
				$End = strlen($TempString);
			return array($WordsArray[0][$Start][1], $End);
			}
		else
			return array(0,0);
		

		die("Shouldn't be here");
		//$this->printr($SciScoreArray,"SSA after filter");
		for($w=0;$w<count($ScoreArray)-1;$w++)
			{
			if($ScoreArray[$w] > 30 && $ScoreArray[$w+1] > 20)
				return $WordsArray[0][$w][1];//Return the offset where the line should be split
			}
		return "0";
		}
		
		
	//**********************************************
	private function RankAssociatedTaxa()
		{//Each line is scored for probability of containing associated taxa
		//The array of lines is sorted by score and then reversed so the line of maximum probability is first, etc.
		//The routine then starts with the most probable line, continuing until the field is found, or score is below a minimum.
		//$RankArray = array_fill(0,count($this->LabelLines),0);
		$RankArray = array();
		$match = array();
		for($L=0;$L<count($this->LabelLines);$L++)
			{//Score the line
			//echo "Line $L: {$this->LabelLines[$L]}<br>";
			$Rank = 0;
			if(count($this->LabelArray[$L]) < 2)
				$Rank = -10; //Not likely if the line is only one word long.
			if($this->Assigned['sciname'] == $L)
				$Rank -=100;
			if($this->LineStart[$L] == "associatedTaxa" && !$this->Assigned['associatedCollectors'] != $L)
				{
				//echo "Found start word in line $L<br>";
				$Rank = 100;
				}
			//echo "Rank 1=$Rank<br>";
			if($this->Assigned['sciname'] == $L || $this->Assigned['associatedCollectors'] == $L)
				$Rank-=100; //This line is either the scientific name line, or associated collectors line.  Can't be associated species line.
			$CommaCount = substr_count($this->LabelLines[$L], ","); //Commas indicate a list, perhaps of associated species.
			if($CommaCount > 3)
				$CommaCount = 3;
			$Rank += $CommaCount;
			if($this->Assigned['associatedCollectors'] != "" && $this->Assigned['associatedCollectors'] != $L)
				{//As long as associated collectors is clearly not on this line, then the word "associated" means this is probably the line.
				if(strpos($this->LabelLines[$L],"associated") !== false)
					$Rank += 20;
				}
			//echo "Rank 2=$Rank<br>";
			$Found = preg_match("([w|W]ith [A-Z][a-z]{4,20} [a-z]{3,20})",$this->LabelLines[$L]); 
			if($Found === 1 && $this->Assigned['associatedCollectors'] != $L)	
				{
				$Rank+= 20;
				$Found=0;
				}
			else 
				$Found = preg_match("([w|W]ith [A-Z][a-z]{4,20})",$this->LabelLines[$L]); //With followed by a potential sciname is a strong indicator
			if($Found === 1 && $this->Assigned['associatedCollectors'] != $L)	
				{//Found the word "with", and it isn't for associated collectors.
				$Rank+= 10;
				}
			//echo "Rank 3=$Rank<br>";
			$Rank += preg_match_all("(([A-Z][a-z]{3,20}) ([a-z]{4,20}))",$this->LabelLines[$L],$match); //Add a point for each potential sciname
			//$this->printr($match,"Match");
			if($Rank < 10 && count($match[0]) > 0)
				{
				//echo "1,0  2,0 = {$match[1][0]} {$match[2][0]}<br>";
				$Score = $this->ScoreSciName($match[1][0],$match[2][0],true);
				if($Score > 0)
					$Rank += $Score;
				if(count($match[0]) > 1)
					{
					$Score = $this->ScoreSciName($match[1][1],$match[2][1],true);
					if($Score > 0)
						$Rank += $Score;
					}
				}
			//echo "Rank 4=$Rank<br>";
		
				//$this->printr($match,"SciNames");
			if($Rank >= 6)
				$RankArray[$L] = $Rank;
			//echo "$Rank: {$this->LabelLines[$L]}<br>";
			}
		//$this->printr($RankArray,"Rank Array");	
		//Find maximum Rank
		$Max = 0;
		$MaxLine = -1;
		foreach($RankArray as $L=>$Val)
			{
			if($Val > $Max)
				{
				$Max = $Val;
				$MaxLine = $L;
				}
			}


			//Work up from this line, including lines as long as their rank is high enough.
		$Start = $MaxLine;
		$End = $MaxLine;
		if($Max < 99)
			{//Only if there is a not a start word.
			for($L = $MaxLine-1;$L > 0;$L--)
				{
				if(isset($RankArray[$L]) && $RankArray[$L]> 5)
					{
					//echo "Set Start to $L<br>";
					$Start = $L;
					}
				else
					break;
				}
			}
		//Work down from this line in the same way.
		for($L = $MaxLine+1; $L < count($this->LabelLines);$L++)
			{
			if(isset($RankArray[$L]) && $RankArray[$L]> 5)
				{
				//echo "Set End to $End<br>";
				$End = $L;
				}
			else
				break;
			}
		//echo "Start  = $Start, End = $End<br>";
		//If the list contains several lines, make sure there isn't a mis-OCR causing false end to the list
		$TestEnd = $End+2;
		//while($TestEnd < count($this->LabelLines)-2 && $RankArray[$TestEnd] >= 10)
		while(isset($RankArray[$TestEnd]) && $RankArray[$TestEnd] > 5)
			{
			$End = $TestEnd;
			$TestEnd ++;
			}
		//echo "Then Start  = $Start, End = $End<br>";
		
		
		//Now $Start indicates the first line that has AT, and $End indicates the last.
		for($A = $Start - 1;$A > 0;$A--)
			unset($RankArray[$A]);
		for($A = $End + 1;$A < count($this->LabelLines);$A++)
			unset($RankArray[$A]);
		//$this->printr($RankArray,"Reduced RA");
		for($L=$Start+1;$L<count($this->LabelLines);$L++)
			{
			//echo "Check {$this->LabelLines[$L]}<br>";
			if(preg_match("(\A([A-Z][a-z]{3,20})( [a-z]{3,20})?\Z)",$this->LabelLines[$L],$match))
				$RankArray[$L] = 10;
			else
				break;
			}
		//asort($RankArray);//Sort the array of scores...
		//$RankArray = array_reverse($RankArray, true);//Then reverse order to bring most probable to the first position
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
			//echo "$L, {$this->LabelLines[$L]}<br>";
			$RankArray[$L] = 0;
			if($this->CheckStartWords($L, 'verbatimCoordinates'))
				$RankArray[$L] = 100;
			if(preg_match_all("(\b[NSEW]\b)",$this->LabelLines[$L],$match) == 2)
				$RankArray[$L] += 5;
			$RankArray[$L] += preg_match_all("([0-9]{2,3}+[\?°\*\"\' ])",$this->LabelLines[$L],$match);
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
		//echo "{$this->LabelLines[$L]}<br>";
		if(stripos($this->LabelLines[$L],"utm") !== false)
			{
			if($this->GetUTM($L))
				return;
			}
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
		$Preg = "([0-9]{1,3}\.[0-9]{3,8}[,°:; ])";
		if($this->PregLatLong($Preg,$L,$OneLine))
			return;
		//Put together the modules to build a Lat/Long in several formats
		$Preg = array();
		$Preg['dir'] = "[ ]*[NSEW][\., ]*";
		$Preg['deg'] = "[0-9\.]+[°\* ]+";
		$Preg['min'] = "[0-9\.]+[\', ]*";//Used to be a comma next to the last space
		$Preg['sec'] = '[ ]*(?:[0-9\.]+")*';
		$Preg['decimal'] = '(([0-9\. ]+[°\* ]*[NSEW][\., ]*))';
		
		if($this->PregLatLong($Preg['dir'].$Preg['deg'].$Preg['min'].$Preg['sec'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['dir'].$Preg['deg'].$Preg['min'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['deg'].$Preg['min'].$Preg['sec'].$Preg['dir'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['deg'].$Preg['min'].$Preg['dir'],$L,$OneLine))
			return;
		$this->PregLatLong($Preg['decimal'],$L,$OneLine);
		}

	//**********************************************
	private function GetUTM($L)	
		{
		$match=array();
		$TempString = $this->LabelLines[$L];
		$Found = preg_match("((utm|UTM[ :-]*)([0-9]{1,2}\s*[A-Z]\s+[0-9]{6,8}\s*[EN]\s+[0-9]{6,8}\s*[EN]))",$TempString,$match);
		if(!$Found)
			return false;
		$TempString = $match[2];
		$UTM = preg_replace("(([0-9]{1,2})\s*([A-Z]\s+[0-9]{6,8})\s*([EN]\s+[0-9]{6,8})\s*([EN]))","$1$2$3$4$5",$TempString);
		$OU = new OccurrenceUtilities;
		$LL = $OU->parseVerbatimCoordinates($UTM,"UTM");
		if(count($LL) > 0)
			{
			$this->AddToResults('verbatimCoordinates',$UTM,$L);
			$this->AddToResults('decimalLatitude',$LL['lat'],$L);
			$this->AddToResults('decimalLongitude',$LL['lng'],$L);
			return true;
			}
		return false;
		
		}
		
		
	//**********************************************
	private function PregLatLong($Preg,$L,$OneLine="")
		{
		//Use the regular expression passed as "$Preg" on $OneLine.  If $OneLine is empty, use LabelLine[$L].
		if($OneLine == "")
			$OneLine = $this->LabelLines[$L];
		$match=array();
		$Preg = '(('.$Preg.'))';
		//echo "Preg=$Preg<br>";
		$Found = preg_match_all($Preg, $OneLine,$match);
		//$Found = 2;
		
		if($Found > 1)
			{
			//echo "Here<br>";
			$VerbLatLong = ($match[0][0]." ".$match[0][1]);
			//Correct where the decimal was rendered a space by OCR
			//Need to keep an eye on this
			$VerbLatLong = preg_replace("((\d{1,3}) (\d{3,6}) ([NESW]))","$1.$2 $3",$VerbLatLong);
			$OU = new OccurrenceUtilities;
			$LL = $OU->parseVerbatimCoordinates($VerbLatLong);
			if(count($LL) > 0)
				{
				$this->AddToResults('verbatimCoordinates',$VerbLatLong,$L);
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
		//global $this->Label;
		$PregWords = "(\b(elevation|elev|meters|m.|m|feet|ft.|ft|altitude|alt|ca)\b|[\d,]{3,5})";
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
		//Now have an array of probable lines sorted most probable first.
		//ScoreArray will be filled with possible elevations and a score for each.
		$ScoreArray = array();
		foreach($RankArray as $L=>$Value)
			{//Iterate through the lines from most to least likely, cutting off if value gets below a threshold.
			if($Value < 5)
				break;
			$TempString = $this->LabelLines[$L];
			//$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*[about ]*([0-9,-]+)([\s]*)(m(eter)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
			$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*[about ]*([0-9, -]+)\s?\d?([\s]*)(m(eter)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
			if($Found === 1) //Embedded in a line with number following key word, like "Found at elevation 2342 feet"
				{
				//$this->printr($match,"Elev match 1");
				$ScoreArray[$match[0]] = $Value+10;
				if(max($ScoreArray) == $Value+10)
					$FoundElev = $match[2];
				}
			if($Found !== 1)
				{
				$Found = preg_match("((ca\.?\s)?([0-9,]+)([ ]*)(m(eter)?s?\b|f(ee)?t?\b|\')[\S]*[\s]*([a|A]lt|[e|E]lev)[ation]?[\S]*)",$TempString,$match);
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
				$Found = preg_match("(\A(ca\.?\s)?([0-9,]+)([ ]*)(m(eter)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
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
				$Found = preg_match_all("((ca\.?\s)?\b([0-9,]+)(\s?)(m(eter)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
				if($Found >0)
					{
					//$this->printr($match,"Elev Match 4");
					$Score = $Value;
					$Elev = $match[0][0];
					if($Found > 1 && strpos($match[0][1],"m") > 1)
						$Elev = $match[0][1];
					if($Elev > 1000 && $Elev < 12000)
						$Score+= 2;//Increase score if altitude in reasonable range
					$ScoreArray[$Elev] = $Score;
				if(max($ScoreArray) == $Score)
					$FoundElev = $Elev;
					}
				}
			}

		//All potential altitudes are found and in $ScoreArray.  Now sort by most likely and accept the top one.

		if(count($ScoreArray) == 0)
			return;
		asort($ScoreArray);
		end($ScoreArray); //Select the last (highest) element in the scores array
		//$this->printr($ScoreArray,"ScoreArray");
		$TempString = key($ScoreArray);//Should be the best line.
		for($L=0;$L<count($this->LabelLines);$L++)
			if(strpos($this->LabelLines[$L],$TempString)!== false)
				{
				//$L = array_search($TempString, $this->LabelLines);
				$TempString = $this->RemoveStartWordsFromString($TempString,'minimumElevationInMeters');
				break;
				}
		if($L >= 0 && $L < count($this->LabelLines))
			{
			//echo "TempString=$TempString<br>";
			//echo "Changing this: {$this->LabelLines[$L]}...";
			$this->LabelLines[$L] = $this->RemoveStartWordsFromString($this->LabelLines[$L],'minimumElevationInMeters');
			//echo "To this: {$this->LabelLines[$L]}<br>";
			}
		$Preg = "(\d+[ to-]+$TempString)";
		$Found = preg_match($Preg, $this->Label,$match);
		//echo "Preg=$Preg, {$this->LabelLines[$L]}<br>";
		if($Found > 0)
			{
			//$this->printr($match,"MinMax");
			$TempString = $match[0];
			$TempString = str_replace("to","-",$TempString);
			}
		
		
		
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
		//$TempString = "1000 - 1200 m";
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
		//global $this->Label;
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
			//echo "Checking $L: {$this->LabelLines[$L]}<br>";
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
		$BadWords = "(\b(copyright|herbarium|garden|vascular|specimen|database|institute|instituto|plant)\b)i";
		if(preg_match($BadWords,$this->LabelLines[$L]) > 0)
			return false;

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
				$this->AddToResults($Field,$Name,$L);
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
   
	/**
     * Given a name, check to see if is the same as or similar to an existing 
     * agent of type individual.
     *
     * @param Name, reference to string to check against agent names, 
     *   may be altered to conform to a match.
     * @return score for match, 10 if exact match to name of only one agent.
     */
    private function ConfirmRecordedByAgents(&$Name) 
        { 
        $match = array();
        $Score = 0;
		$PregNotNames = "(\b(municip|herbarium|agua|province|university|mun|county|botanical|garden)\b)i";  //Known to be confused with names
		$Score -= 5*(preg_match_all($PregNotNames,$Name,$match));
        // Check for exact match against individual name
        $sql = "select distinct a.agentid from agentnames n left join agents a on n.agentid = a.agentid where a.type = 'Individual' and n.name = ? ";
        if ($stmt = $this->conn->prepare($sql)) 
           { 
           $stmt->bind_param('s',$Name);
           $stmt->execute();
           $stmt->bind_result($agentid);
           $stmt->store_result();
           if ($stmt->num_rows==1) 
              {
              $Score = 10;
              }
           else 
              {
              // Check for exact match against organization name.
              $sql = "select distinct a.agentid from agentnames n left join agents a on n.agentid = a.agentid where a.type = 'Organization' and n.name = ? ";
              if ($stmt = $this->conn->prepare($sql)) 
                  { 
                  $stmt->bind_param('s',$Name);
                  $stmt->execute();
                  $stmt->bind_result($agentid);
                  $stmt->store_result();
                  if ($stmt->num_rows>0) 
                      { 
                      $Score = -5;
                      }
                  }
              }
           } 
        else 
           { 
           throw new Exception("Error preparing query [$sql]. ". $this->conn->error);
           }
        return $Score;
        }

		
		
	//**********************************************
	private function ConfirmRecordedBy(&$Name)
		{//Check for the name in the omoccurrences table.  Lower score if it looks like a university, herbarium, county, etc.
		$match=array();
		$Score = 0;
		$PregNotNames = "(\b(municip|herbarium|agua|province|university|mun|county|botanical|garden)\b)i";  //Known to be confused with names
		$Score -= 5*(preg_match_all($PregNotNames,$Name,$match));
		$query = "SELECT recordedBy FROM omoccurrences where recordedBy LIKE '$Name' LIMIT 1";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			//echo "Num = ".$result->num_rows."<br>";
			return 10;
			}
		else
			{
			$query = "SELECT recordedBy FROM omoccurrences where recordedBy LIKE '$Name%' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				{
				$Record = $result->fetch_assoc();
				$Name = $Record['recordedBy'];
				return 10;
				}
			return $Score;
			}
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
			if($this->StatScore[$L]['Score'] > 100)
				{
				//echo "$L: StatScore = {$this->StatScore[$L]['Score']}, {$this->StatScore[$L]['Field']}, {$this->LabelLines[$L]}<br>";
				if($this->StatScore[$L]['Field'] != "locality")
					$RankArray[$L] -= $this->StatScore[$L]['Score'];
				
				}
			//echo "$L: {$RankArray[$L]}<br>";
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
			$RankArray[$L] -= 2*preg_match_all("(\(|\)|�|\")",$this->LabelLines[$L],$match); //Parenthesis on the line probably mean author, not collector.  Degree looks like lat/long.  Quote looks like lat/long
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
			
			$PM=preg_match('(([#0-9]+-?)([0-9a-zA-Z]*))',$Word,$match);
			//$this->printr($match,"RecNum");
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


	function GetCountryState()
		{
		//global $this->Label;
		
		//$Preg = "(\b([a-z]{2,20}\s+\b[a-z]{2,20})\s+((\bcounty\b)|(\bco\b)))i";
		//$Found = preg_match($Preg,$this->Label,$match);
		//if($Found)
		//	;
		$Found = preg_match("(([A-Za-z]{2,20}ACEAE)\s+(of)\s+(\b\S+\b)\s+(\b\S+\b)?)i",$this->Label,$match);
		if($Found !== 1)
			$Found = preg_match("((plants|flora|lichens)\s(of|de)\s+(\b\S+\b)(\s?\b\S+\b)?)i",$this->Label,$match);
		//$this->printr($match,"Plants Of");
		//$this->AddToResults('country',"$Found, Line = {$this->LabelLines[7]}",-1);
		if($Found)
			{// Found "Plants of...".  Look for state or country

			$Name1 = trim($match[3]," .,;:");
			$Name2 = "";
			if(count($match) > 4)
				$Name2 = $Name1." ".trim($match[4]," .,;:");
			$this->PlantsOf($Name1);
			if($this->Results['country'] == "")
				$this->PlantsOf($Name2);
			}
		if($this->Results['country'] != "") //Found it.
			return;

		//"Plants of" didn't work.  Look for "xxx County".
		$match = $this->CountyPreg();
		//$this->printr($match,"Match2");
		if($match != false)
			{ //Found "xxx County"
			$County = $match[1]." ".$match[2];
			//echo "County might be $County<br>";
			if(!$this->ScanForState($County))
				$this->ScanForState($match[2]);
			}
		}

		
		
	private function CountyPreg()
		{ //Looks for the word County on the label
		$Preg = "((\b[A-Z][a-z]{1,20}\b)?[\s]*(\b[A-Z][a-z]{1,20}\b)[\s]*(\b(County|Co)\b))i";
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			//echo "Testing {$this->LabelLines[$L]}<br>";
			$Found = preg_match($Preg,$this->LabelLines[$L],$match);
			
			if($Found)
				{
				//$this->printr($match,"County Match");
				$match['Line'] = $L;
				return $match;
				}
			}
		return false;
		}
							
		
		

	function PlantsOf($Name)
		{//Find country/state from "Plants of" statement.
		$query = "SELECT * FROM lkupcountry where countryName LIKE '$Name'";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{//This needs to be completed once I find an example
			//while($OneCountry = $result->fetch_assoc())
			//	echo $OneCountry['countryName']."<br>";
			}
		else
			{
			//echo "Here for $Name<br>";
			$match = $this->CountyPreg();
			if($match != false)
				{ //Found a county name on the label.  Check if $Name is a state that contains the county
				//echo "Here 1<br>";
				$County = $match[1]." ".$match[2];
				//$this->CheckCounty($County, $State
				$query = "SELECT s.stateName, s.stateId, cr.countryName, cr.countryId, cy.countyName FROM lkupstateprovince s inner join lkupcountry cr on cr.countryId = s.countryId INNER JOIN lkupcounty cy on cy.stateId = s.stateId WHERE s.stateName LIKE '$Name' AND cy.countyName like '$County'";
				$result = $this->conn->query($query);
				if($result->num_rows == 0)
					{
					$County = $match[2];
					$query = "SELECT s.stateName, s.stateId, cr.countryName, cr.countryId, cy.countyName FROM lkupstateprovince s inner join lkupcountry cr on cr.countryId = s.countryId INNER JOIN lkupcounty cy on cy.stateId = s.stateId WHERE s.stateName LIKE '$Name' AND cy.countyName like '$County'";
					//echo "$query<br>";
					$result = $this->conn->query($query);
					if($result->num_rows > 0)
						{
						$L = $match['Line'];
						$Result = $result->fetch_assoc();
						$this->AddToResults('county',$Result['countyName'],$L);
						$this->AddToResults('country',$Result['countryName'],$L);
						$this->AddToResults('stateProvince',$Result['stateName'],$L);
						$this->LabelLines[$L] = trim(str_replace($match[0],"",$this->LabelLines[$L])," ,;:");
						return;
						}
					}
				}
			else //Didn't find a county.  Just look for the state
				$query = "SELECT s.stateName, s.stateId, cr.countryName, cr.countryId FROM lkupstateprovince s inner join lkupcountry cr on cr.countryId = s.countryId WHERE s.stateName LIKE '$Name'";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				{
				while($OneState = $result->fetch_assoc())
					{
					//$this->printr($OneState,"OneState");
					if($this->Results['stateProvince'] == "")
						$this->AddToResults('stateProvince',$OneState['stateName'],-1);
					//$this->printr($OneState,"OneState");
					$County = $this->ScanForCounty($OneState);
					if($County != "")
						{
						$this->AddToResults('county',$County,-1);
						for($L=0;$L<count($this->LabelLines);$L++)
							{
							if(stripos($this->LabelLines[$L],$County)!== false)
								{
								$this->LabelLines[$L] = str_ireplace($County." county","",$this->LabelLines[$L]);
								$this->LabelLines[$L] = str_ireplace($County,"",$this->LabelLines[$L]);
								$this->LabelLines[$L] = trim($this->LabelLines[$L]," ,;:");
								break;
								}
							
							}
						$this->AddCountry($OneState['stateId']);
						}
					if($this->Results['county'] != "")
						break;
					}
				}
			}
		}

		
	function ScanForState($County)
		{
		//global $this->Label;
		$MaybeState = array();
		$query = "SELECT s.stateId, s.stateName, c.countyName from lkupstateProvince s inner join lkupcounty c on c.stateId=s.stateId where c.countyName LIKE '$County'";
		$result = $this->conn->query($query);
		if($result->num_rows == 0)
			{
			return false;
			}
		else 
			{
			while($OneCounty = $result->fetch_array())
				{
				$State = $OneCounty['stateName'];
				$Preg = "(\b$State\b)i";
				//echo "Preg = $Preg<br>";
				if(preg_match($Preg,$this->Label,$match))
					{
					$MaybeState[] = $OneCounty;
					}
				}
			if(count($MaybeState) == 1)
				{
				$this->AddToResults('county',$MaybeState[0]['countyName'],-1);
				$this->AddToResults('stateProvince',$MaybeState[0]['stateName'],-1);
				$this->AddCountry($MaybeState[0]['stateId']);
				return true;
				}
			
			}
		
		}
		
		
	function ScanForCounty($OneState)
		{
		//global $this->Label;
		$query = "SELECT cy.countyName from lkupcounty cy INNER JOIN lkupstateprovince s on s.stateId=cy.stateId WHERE cy.stateId = ".$OneState['stateId'];
		//echo "$query<br>";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			while($OneCounty = $result->fetch_assoc())
				{
				$CountyName = $OneCounty['countyName'];
				if(stripos($this->Label, $CountyName) !== false)
					{
					return $CountyName;
					}
				}
		}

	function AddCountry($StateId)
		{
		$query = "SELECT countryName FROM lkupcountry c INNER JOIN lkupstateprovince s on s.countryId= c.countryId where s.stateId=$StateId LIMIT 1";
		//echo "$query<br>";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			$Country = $result->fetch_assoc();
			$this->AddToResults('country',$Country['countryName'],-1);
			}
		return;
		}

//*********************************************************************************************************************
// Old get country stuff

	//**********************************************
	function GetCountryStateOld()
		{
		//global $this->Label;
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
			$Found = preg_match("(([A-Za-z]{2,20}ACEAE)\s+(of)\s+(\b\S+\b)\s+(\b\S+\b)?)i",$this->LabelLines[$L],$match);
			if($Found !== 1)
				$Found = preg_match("((plants|flora|lichens)\s(of|de)\s+(\b\S+\b)(\s?\b\S+\b)?)i",$this->LabelLines[$L],$match);

			if($Found ===1)
				{
				$this->Assigned['ignore'] = $L;
				$RankArray[$L] += 5;
				$PlantsOf = trim($match[3]," .,;:");  //Grab the location name.  Could be country, state or country.
				//echo "PlantsOf = $PlantsOf<br>";
				$Count = count($match);
				//echo "Count = $Count<br>";
				if(count($match) > 4)
					$PlantsOf2 = $PlantsOf." ".trim($match[4]," .,;:");
				else
					$PlantsOf2 = "";
				$PlantsOfLine = $L;
				if($PlantsOf2 != "") //Check first for two word country/state/county
					{
					//echo "PlantsOf2 = $PlantsOf2<br>";
					if($this->CheckCountry($L,$PlantsOf2))
						return;
					else if($this->CheckState($L, $PlantsOf2))
						{
						return;
						}
					else if($this->CheckCounty($L, $PlantsOf2))
						return;
					}
				if($this->CheckCountry($L, $PlantsOf2))
						{
						//Find State
						//echo "Returning<br>";
						return;
						}
				else if($this->CheckState($L, $PlantsOf))
					{
					//echo "Returning<br>";
					//Find county
					return;
					}
				else if($this->CheckCounty($L, $PlantsOf))
					return;
				}
			//Didn't find "Plants of" or "Flora de", etc.
			$Preg = "(\b([a-z]{2,20})\s+((\bcounty\b)|(\bco\b)))i";
			$Found = preg_match($Preg,$this->LabelLines[$L],$match);
			if($Found === 0)
				{
				$Preg = "((\bcounty\b):*\s+\b([a-z]{2,20}))i";
				$Found = preg_match($Preg,$this->LabelLines[$L],$match);
				}
			
			if($Found === 1)
				{//Found the word "County" on the label.  Might be enough to determine state and country.
				$query = "SELECT c.stateId,c.countyName,s.stateName,s.countryId FROM lkupcounty c INNER JOIN lkupstateprovince s where c.stateId=s.stateId AND countyName LIKE '{$match[1]}'";
				$result = $this->conn->query($query);
				if($result->num_rows == 0)
					{//Single word not a county name.  Look for two word county
					$Preg = "(\b([a-z]{2,20}\s+\b[a-z]{2,20})\s+((\bcounty\b)|(\bco\b)))i";
					$Found = preg_match($Preg,$this->LabelLines[$L],$match);
					if($Found)
						{
						$query = "SELECT c.stateId,c.countyName,s.stateName,s.countryId FROM lkupcounty c INNER JOIN lkupstateprovince s where c.stateId=s.stateId AND countyName LIKE '{$match[1]}'";
						$result = $this->conn->query($query);
						}
					if($Found===0)
						{
						$Preg = "((\bcounty\b):*\s+\b([a-z]{2,20}\s+\b[a-z]{2,20}))i";
						$Found = preg_match($Preg,$this->LabelLines[$L],$match);
						if($Found > 0)
							{
							$query = "SELECT c.stateId,c.countyName,s.stateName,s.countryId FROM lkupcounty c INNER JOIN lkupstateprovince s where c.stateId=s.stateId AND countyName LIKE '{$match[2]}'";
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
						$this->AddToResults('stateProvince',$OneLine['stateName'],$PlantsOfLine);
						$query = "SELECT countryname from lkupcountry where countryId = {$OneLine['countryId']} LIMIT 1";
						$CountryResult = $this->conn->query($query);
						$TempArray = $CountryResult->fetch_assoc();
						$this->AddToResults('country',$TempArray['countryname'],-1);
						if($PlantsOfLine >=0)
							$Assigned['country'] = $L;
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

		if($CountyName != "")
			{//This is a pretty good giveaway.  Country should follow this.
			//Try state first
			if($CountyName != "")
				{
				$query = "SELECT s.stateId,s.stateName from lkupstateprovince s INNER JOIN lkupcounty c WHERE s.stateId=c.stateId AND c.countyname LIKE '$CountyName'";
				
				if($PlantsOf != "")
					$query .= " AND s.stateName LIKE '$PlantsOf'";
				}
			else
				$query = "SELECT s.stateId,s.stateName from lkupstateprovince s  WHERE stateName LIKE '$PlantsOf'";
			
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
					{
					if($PlantsOfLine >=0)
						$Assigned['state'] = $L;
					return;
					}
				
				}
			
			}
			// End of routine that uses "Plant Of" and/or county.  Either doesn't have "Plant Of", or County, or using them failed.
			
		
		//Neither Plants of nor county found.  Begin a general search.
		//Reverse sort the Rank array to bring the most likely lines to the beginning.
		asort($RankArray);
		$RankArray = array_reverse($RankArray, true);
		//$this->printr($RankArray,"Country");
		
		foreach($RankArray as $L=>$Value)
			{//First look for the country or state at the beginning of the lines, a common place to find it
			if($Value < 1 || count($this->LabelArray[$L]) < 2)
				continue;
			if($this->CheckCountry($L,$this->LabelArray[$L][0].' '.$this->LabelArray[$L][1]))
				return; //Found country and maybe state, so return.
			if($this->CheckState($L,$this->LabelArray[$L][0].' '.$this->LabelArray[$L][1]))
				return;
			//echo "Checking {$this->LabelArray[$L][0]}<br>";
			if($this->CheckCountry($L,$this->LabelArray[$L][0]))
				{
				//echo "Found in {$this->LabelLines[$L]}<br>";
				return;
				}
			if($this->CheckState($L,$this->LabelArray[$L][0]))
				return;
			}
		foreach($RankArray as $L=>$Value)
			{//Look for country deeper into lines.  Slower, so we checked the first word first above.
			if($Value < 1)
				continue;
			for($W=0;$W<count($this->LabelArray[$L])-1;$W++)
				{
				if($this->CheckCountry($L,$this->LabelArray[$L][$W].' '.$this->LabelArray[$L][$W+1]))
					return;
				if($this->CheckState($L,$this->LabelArray[$L][$W].' '.$this->LabelArray[$L][$W+1]))
					return;
				if($this->CheckCountry($L,$this->LabelArray[$L][$W]))
					return;
				if($this->CheckState($L,$this->LabelArray[$L][$W]))
					return;
				}
			}
		}
/*
	private function CheckCountry($L, $Word)
		{
		$query = "SELECT countryId,countryName from lkupcountry where countryName like '$Word' LIMIT 1";
		//echo "$query<br>";
		$result = $this->conn->query($query);
		$Num = $result->num_rows;
		if($Num == 0)
			return false;
		else
			{
			$OneReturn = $result->fetch_assoc();
			$this->AddToResults('country', $OneReturn['countryName'],$L);
			$this->SeekState($OneReturn['countryId'],$L);
			return true;
			}
		}
	
	private function SeekState($CountryId,$L)
		{
		//Given the country, scan the whole label for a contained state.
		global $this->Label;
		//echo "Seek State<br>";
		$query = "SELECT stateName,stateId from lkupstateprovince where countryId like '$CountryId'";
		//echo $query."<br>";
		$StateArray = array();
		$StateResult = $this->conn->query($query);
		$Num = $StateResult->num_rows;
		if($Num > 0)
			{
			while($OneState = $StateResult->fetch_assoc())
				{
				if(stristr($this->Label,$OneState['stateName']))
					$StateArray[] = $OneState['stateName'];
				}
			if(count($StateArray) > 0)
				{
				$Lengths = array_map('strlen',$StateArray);
				$MaxLength = max($Lengths);
				$index = array_search($MaxLength,$Lengths);
				$this->AddToResults('stateProvince',$StateArray[$index],$L);
				//echo "Maximum length = {$StateArray[$index]}<br>";
				}
			
			return true;
			}
		else
			return false;
		
		}
	
	
	private function CheckState($L, $Word)
		{
		//USED echo "CheckState<br>";
		$query = "SELECT countryId,stateName,stateId from lkupstateprovince where stateName like '$Word'";
		//echo $query."<br>";
		$result = $this->conn->query($query);
		$Num = $result->num_rows;
		if($Num > 0)
			{
			$StateId = $this->GetStateFromList($result,$L);
			$StateName = $this->Results['stateProvince'];
			$CountryName = $this->Results['country'];
			if($StateName != "" && $CountryName != "")
				$this->County($CountryName, $StateName, $L);
			//$query = "SELECT countyName FROM lkupcounty WHERE stateId like '$StateId'";
			return true;
			}
		else
			return false;
		}
		
	private function GetCountryFromId($Id)
		{
		echo "GetCOuntryFromId<br>";
		$query = "SELECT countryName from lkupcountry where countryId like $Id LIMIT 1";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			$OneReturn = $result->fetch_assoc();
			return $OneReturn['countryName'];
			}
		return "";
		}

	private function CheckCounty($L, $Word)
		{
		//USED echo "CheckCountry<br>";
		$query = "SELECT stateId,countyName from lkupcounty where countyName like '$Word' LIMIT 1";
		//echo $query."<br>";
		$result = $this->conn->query($query);
		$Num = $result->num_rows;
		if($Num == 0)
			return false;
		else
			{
			$OneReturn = $result->fetch_assoc();
			$this->AddToResults('countyParish', $OneReturn['countyName'],$L);
			if($this->Results['stateProvince'] == "")
				{
				$Id = $OneReturn['stateId'];
				$StateName = $this->GetStateFromId($Id);
				$this->AddToResults('stateProvince',$StateName,$L);
				$this->AddToResults('country',$this->GetCountryFromId($Id),$L);
				}
			return true;
			}
		}

	private function GetStateFromId(&$StateId)
		{
		echo "GetStateFromId<br>";
		$query = "SELECT stateName,countryId from lkupstate where stateId like $StateId LIMIT 1";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			$OneReturn = $result->fetch_assoc();
			$Id = $OneReturn['countryId'];
			return $OneReturn['stateName'];
			}
		return "";
		}
		

		
	private function GetStateFromList($StateResult, $L)
		{
		//USED echo "GetStateFromList<br>";
		//$StateResult is a mysql result from a query.  Has one or more potential states
		global $this->Label;
		$OneState = $StateResult->fetch_assoc();
		//$this->printr($OneState,"OneState");
		$this->AddToResults('stateProvince', $OneState['stateName'],$L);
		$query = "SELECT c.countryname, s.stateId FROM lkupcountry c INNER JOIN lkupstateprovince s where s.countryId=c.countryId AND s.stateId={$OneState['stateId']}";
		$CountryResult = $this->conn->query($query);
		$OneCountry = $CountryResult->fetch_assoc();
		$CountryArray[] = $OneCountry['countryname'];
		if($StateResult->num_rows == 1)
			{ //Only one result.  Accept it.
			$this->AddToResults('stateProvince',$OneState['stateName'],$L);
			$this->AddToResults('country', $OneCountry['countryName'],-1);
			return $OneState('stateId');
			}
		else 
			{//More than one result.  See if the Country name is on the label.  
			while($OneState = $StateResult->fetch_assoc())
				{
				$query = "SELECT c.countryname FROM lkupcountry c INNER JOIN lkupstateprovince s where s.countryId=c.countryId AND s.stateId={$OneState['stateId']}";
				$CountryResult = $this->conn->query($query);
				$TempArray = $CountryResult->fetch_assoc();
				$CountryArray[] = $TempArray['countryname'];
				$StateId = $OneState['stateId'];
				}
			//$this->printr($CountryArray,"State Array");
			foreach($CountryArray as $Country)
				{//Look for each country name on the label.
				if(preg_match("(\b$Country\b)i", $this->Label))
					{
					$this->AddToResults('country',$Country, -1);
					return $StateId;
					}
				}
			//Country name not on label.  Default to USA if this state has the name of one of the 50
			if(count(preg_grep("(United States|USA)", $CountryArray)) > 0)
				{
				$this->AddToResults("country","United States",-1);
				//echo "Default to USA<br>";
				return $StateId;
				}
			}
		}

		
	//**********************************************
	private function CheckOneCountry($Field,$L,$Word1,$Word2="")
		{//Not used any more.  Keep for a while in case some routines might be useful.
		echo "CheckOneCountry<br>";
		//Can check for either state or country
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
	private function County($Country,$State, $StateLine)
		{
		global $this->Label;
		//USED echo "County<br>";
		$match=array();
		$Found = preg_match("((\b[A-Z][a-z]{1,20}\b)?[\s]*(\b[A-Z][a-z]{1,20}\b)[\s]*(\b(County|Co)\b))i",$this->Label,$match);
		if($Found ===1)
			{
			$County = trim($match[1]." ".$match[2]);
			$query = "SELECT * FROM lkupcounty c inner join lkupstateprovince s on c.stateId = s.stateId WHERE s.stateName LIKE '$State' and c.countyName LIKE '$County'";
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
				//echo "M=$M<br>";
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
		echo "Fell through in County<br>";
		//Didn't find it from the word "County".  Search the full label for a county that is in the given country/state.
		$query = "SELECT stateid FROM lkupstateprovince WHERE stateName LIKE '$State'";
		$Stateresult = $this->conn->query($query);
		$StartLine = $StateLine-1;
		if($StartLine < 0)
			$StartLine = 0;
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
						for($L=$StartLine;$L<count($this->LabelLines);$L++)
							
							if(stripos($this->LabelLines[$L],$Cty['countyname'])!==false)
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
		//global $this->LabelArray,$Results,$this->LabelLines;
		$Synonyms = array("Brasil"=>"Brazil","U.S.A."=>"USA","United States"=>"USA");
		echo "GetStateProvince<br>";
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
					$this->County($Country, $OneReturn['stateProvince'], $L);
					return;
					}
				}
			}
		}
*/

//************************************************************************************************************************************
//******************* Word Stat functions ********************************************************************************************
//************************************************************************************************************************************
	

	//******************************************************************
	private function GetWordStatFields()
		{
		$OneLine = array();
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|plants of|aceae of|flora de)\b)i";
		$Max = array();
		//$this->printr($this->LineStart,"Line Start");
		//$this->printr($this->LabelLines,"Lines");
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$Skip=false;
			//If contains start word, then just assign the whole line
			foreach($Fields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					$this->RemoveStartWords($L,$F);
					$this->AddToResults($F,$this->LabelLines[$L],$L);
					$Skip = true;
					break;
					}
				}
			foreach(array("recordedBy","family","identifiedBy","associatedCollectors","sciname","infraspecificEpithet") as $F)
				{//Don't bother scoring if this line has start words or has already been assigned.
				if($this->LineStart[$L] ==$F)
					$Skip=true;
				}
			if($this->Assigned['ignore'] == $L)
				$Skip = true;
			if(preg_match("([a-z])",$this->LabelLines[$L]) === 0)
				{//Usually not all upper case
				$Skip=true;
				}
			if($this->StatScore[$L]['Score'] < 50)
				$Skip = true;  //Already measured this line and it falls short.
			if(preg_match($BadWords,$this->LabelLines[$L]) > 0)
				$Skip=true;
			//if($this->PlantsOfLine == $L)
			//	$Skip=true;
			if($Skip)
				{
				continue;
				}
			$this->SplitWordStatLine($L);
			continue;
			$this->ScoreOneLine($L, $Field, $Score);// Field and Score are called by reference.
			if($Score > 50)
				{//Score is high enough (though limit is empirical).  Add line to highest scoring field.
				//NOTE:  May want to look into breaking a line in the middle if it changes fields partway through.
				//Example:  Small herb with yellow flowers growing beside the road.  (verbAttr followed by habitat or locality)
				//However, this is very hard to do reliably.
				$this->RemoveStartWords($L,$Field);
				//echo "Adding {$this->LabelLines[$L]} to $Field<br>";
				if($this->Results[$Field] != "")
					$this->Results[$Field] .= ", ".trim($this->LabelLines[$L]); //Append
				else
					$this->Results[$Field] = trim($this->LabelLines[$L]);
				}
			}
		return;			
		}


	private function SplitWordStatLine($L)
		{
		//echo "Testing {$this->LabelLines[$L]}<br>";
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		//$this->printr($Fields,"Fields");
		$ScoreArray = array();
		$TempString = $this->LabelLines[$L];
		$Found = preg_match_all("(\b[A-Za-z]{2,20}\b)",$TempString,$WordsArray, PREG_OFFSET_CAPTURE);//Break the string up into words
		$FieldScoreArray = array();
		$StatSums = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);
		for($w=0;$w<count($WordsArray[0])-1;$w++)
			{
			$Loc = $WordsArray[0][$w][1];
			$Word1 = $WordsArray[0][$w][0];
			$Word2 = $WordsArray[0][$w+1][0];
			$ScoreArray = $this->ScoreTwoWords($Word1,$Word2);//Get word stats score for these two words
			$FieldScoreArray[$w] = $ScoreArray;
			foreach($Fields as $F)
				{
				$StatSums[$F] += $ScoreArray[$F];
				}
			}
		//Smooth the array, removing isolated high and low points
		foreach($Fields as $F)
			{
			for($w=1;$w < count($FieldScoreArray)-1;$w++)
				{
				if($FieldScoreArray[$w][$F] < $FieldScoreArray[$w-1][$F] && $FieldScoreArray[$w][$F] < $FieldScoreArray[$w+1][$F])
					$FieldScoreArray[$w][$F] = ($FieldScoreArray[$w-1][$F]+$FieldScoreArray[$w+1][$F])/2;
				if($FieldScoreArray[$w][$F] > $FieldScoreArray[$w-1][$F] && $FieldScoreArray[$w][$F] > $FieldScoreArray[$w+1][$F])
					$FieldScoreArray[$w][$F] = ($FieldScoreArray[$w-1][$F]+$FieldScoreArray[$w+1][$F])/2;
				}
			}
		if(count($WordsArray[0]) > 0)
			$Max = max($StatSums)/(count($WordsArray[0]));
		else
			return;
		if($Max < 50)
			return;
			
		//Now try to split into Fields.
		$ResultFields = array();
		$ChangeFields = array();
		$Prep = array("in","on","of","by","inside","along","ca");
		for($w=0;$w<count($FieldScoreArray);$w++)
			{
			$Word1 = $WordsArray[0][$w][0];
			$Max = max($FieldScoreArray[$w]);
			$Field = array_search($Max,$FieldScoreArray[$w]);
			$ResultFields[$w] = $Field;
			//echo "Set $Word1 to $Field<br>";
			if($w==0)
				$ChangeFields[0] = 0;
			if($w > 0 && $ResultFields[$w-1] == 'locality' && $Word1 == mb_convert_case($Word1,MB_CASE_TITLE))
				{
				//echo "Setting {$WordsArray[0][$w][0]} to locality<br>";
				$Pos = $WordsArray[0][$w-1][1] + strlen($WordsArray[0][$w-1][0])+1;
				if(strpos(".,;",$TempString[$Pos]=== false)) //Unless previous word was followed by period, comma, semicolon...
					$ResultFields[$w] = 'locality'; //last field was locality, and this word is capitalized.  Probably part of previous.
				}
			if($w>0 && array_search($WordsArray[0][$w][0],$Prep))
				{
				//echo "Change $Word1 to {$ResultFields[$w-1]}<br>";
				$ResultFields[$w] = $ResultFields[$w-1];
				}
			if($w>0 && $ResultFields[$w] != $ResultFields[$w-1])
				{ //Add this as a point in the line where the field changes.
				//echo "Change at $w<br>";
				$ChangeFields[] = $w;
				}
			}
			
		//Now adjust fields
		//Look for and correct anomalies, such as single words in a field
		//Loop through the following until nothing changes, indicated by $Adjust['Flag']
		do
			{ //First check for widows or orphans
			$Adjust = array('Flag'=>false);
			if(count($ChangeFields) > 1) //Otherwise don't bother.
				{
				if($ChangeFields[1] < 3) //Changing on second or third word is suspicious
					{
					if(count($ChangeFields) == 2)
						{
						if(count($WordsArray[0] < 5) || count($WordsArray[0]) - $ChangeFields[1] > 3)
							{
							//echo "Adjust 1<br>";
							$Adjust = array('RemovePoint' => 1, 'NewField'=> $ResultFields[1],'FieldStart' =>0,'FieldEnd'=>0,'Flag'=>true);
							}
						}
					else if($ChangeFields[2] - $ChangeFields[1] > 3)
						{
						$Adjust = array('RemovePoint' => 1, 'NewField'=> $ResultFields[1],'FieldStart' =>0,'FieldEnd'=>0,'Flag'=>true);
						}
					}
				
				if(!$Adjust['Flag'])
					{
					$LastChange = count($ChangeFields)-1;
					$LastWord = count($WordsArray[0])-1;
					if($LastWord - $ChangeFields[$LastChange] < 4 && $FieldScoreArray[$ChangeFields[$LastChange]][$ResultFields[$ChangeFields[$LastChange]]] < 500)
							{
							//echo "LastChange = $LastChange<br>";
							//$this->printr($ChangeFields,"CF");
							//$this->printr($ResultFields,"RF");
							//$this->printr($FieldScoreArray[$ChangeFields[$LastChange]],"FS");
							//echo $FieldScoreArray[$ChangeFields[$LastChange]][$ResultFields[$ChangeFields[$LastChange]]]."<br>";
							//echo "Adjust 3<br>";
							$Adjust = array('RemovePoint' => $LastChange, 'NewField'=> $ResultFields[$ChangeFields[$LastChange-1]],'FieldStart' =>$ChangeFields[$LastChange],'FieldEnd'=>$LastWord,'Flag'=>true);
							}
					}

				if(!$Adjust['Flag'])
					for($c=0;$c<count($ChangeFields)-1;$c++)
						{
						$w1 = $ChangeFields[$c];
						$w2 = $ChangeFields[$c+1];
						//echo "c = $c, w2-w1 = ".($w2 - $w1)."in {$this->LabelLines[$L]}<br>";
						if($w2 - $w1 < 4)
							{
							//echo "Found $w1, $w2 {$WordsArray[0][$w1][0]} in {$this->LabelLines[$L]}<br>";
							if($c>0 && $ResultFields[$ChangeFields[$c-1]] == $ResultFields[$w2])
								{//A few on one field in the middle of another field.  Use the surrounding field.
								//echo "Adjust 4<br>";
								$Adjust = array('RemovePoint' => $c, 'RemovePoint2'=>$c+1, 'NewField'=> $ResultFields[$w2],'FieldStart' =>$w1,'FieldEnd'=>$w2,'Flag'=>true);
								}
							else
								{ //Not a simple change/change back.  Find which field it shoud belong too.
								//echo "---{$WordsArray[0][$w1][0]} is {$ResultFields[$w1]}, {$WordsArray[0][$w2][0]} is {$ResultFields[$w2]}<br>";
								if($FieldScoreArray[$w2][$ResultFields[$w1]] > 50)
									{//Reasonable score on previous value.  Set back to that
									//echo "Adjust 6<br>";
									if($ResultFields[$w2+1] != $ResultFields[$w1])
										{
										$ChangeFields[]= $w2+1;
										asort($ChangeFields);
										}
									$Adjust = array('RemovePoint' => $c+1, 'NewField'=> $ResultFields[$w1],'FieldStart' =>$w2,'FieldEnd'=>$w2,'Flag'=>true);
									break;
									}
								else if($w2 < count($WordsArray[0])-1)
									{
									;//$w3 = 
									
									}
								}
							}
						}
				if(!$Adjust['Flag'])
					{
					$LastChange = count($ChangeFields)-1;
					$LastWord = count($WordsArray[0])-2;
					if($ResultFields[0] == $ResultFields[$LastWord] && $LastChange > 0)
						{
						for($w=0;$w<$LastWord;$w++)
							{
							$ResultFields[$w] = $ResultFields[0];
							}
						$ChangeFields = array(0=>0);
						}
					}
				if($Adjust['Flag'])
					{
					//$this->printr($ResultFields,"Before");
					for($w=$Adjust['FieldStart'];$w<=$Adjust['FieldEnd'];$w++)
						$ResultFields[$w] = $Adjust['NewField'];
					unset($ChangeFields[$Adjust['RemovePoint']]);
					if(isset($Adjust['RemovePoint2']))
						unset($ChangeFields[$Adjust['RemovePoint2']]);
					$ChangeFields = array_values($ChangeFields);
					}
				}
			}while($Adjust['Flag']);
				
			
		if(count($ChangeFields) == 1)
			{
			//Whole line is a single field
			$Field = $ResultFields[0];
			$this->AddToResults($Field,$this->LabelLines[$L],$L);
			//echo "####### ".$ResultFields[0].":  {$this->LabelLines[$L]}<br>";
			}
		else
			{
			for($c=0;$c<count($ChangeFields)-1;$c++)
				{
				$w1 = $ChangeFields[$c];
				$w2 = $ChangeFields[$c+1];
				$p1 = $WordsArray[0][$w1][1];
				if($w2 < count($WordsArray[0])-1)
					$p2 = $WordsArray[0][$w2][1];
				else
					{
					$p2 = strlen($this->LabelLines[$L]);
					}
				$Field = $ResultFields[$ChangeFields[$c]];
				//echo "####### $p1,$p2: $Field:  ";
				$TempString = substr($this->LabelLines[$L],$p1,$p2 - $p1);
				//echo $TempString;
				//echo"<br>";
				$this->AddToResults($Field,$TempString,$L);
				}
			if($p2 < strlen($this->LabelLines[$L]))
				{
				$Field = $ResultFields[$w2];
				$TempString = substr($this->LabelLines[$L],$p2);
				$this->AddToResults($Field,$TempString,$L);
				}
			
			
			}
		return;
		
		$this->printr($Fields);
		echo "{$this->LabelLines[$L]}<br>";
		for($w=0;$w < count($FieldScoreArray);$w++)
			{
			echo $WordsArray[0][$w][0]." ".$WordsArray[0][$w+1][0];
			echo " {$ResultFields[$w]}, ";
			foreach($Fields as $F)
				echo ", ".floor($FieldScoreArray[$w][$F]);
			echo"<br>";
			
			}
		
		}
		
	private function ScoreTwoWords($Word1,$Word2)
		{
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$ScoreArray = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);
		$ExcludeWords = array('verbatimAttributes'=>array("herbarium","institute","university","botanical"),'habitat'=>array("herbarium","university"));
		$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL LIMIT 3";
		//echo "$query<br>";
		$result = $this->conn->query($query);
		$num1 = $result->num_rows;
		$MaxScore1 = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);
		$MaxScore2 = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);
		if($num1 > 0)
			{
			while($Values = $result->fetch_assoc())
				{
				$Factor = 1;
				if($Values['totalcount'] < 10) //Reduce impact if only seen few times
					$Factor = ($Values['totalcount'])/10;
				foreach($Fields as $F)
					{
					$OneScore = $Factor * $Values[$F.'Freq'];
					if($OneScore > $MaxScore1[$F])
						$MaxScore1[$F] = $OneScore;
					}
				}
			}
			if($Word2 != "")
				{//Look for two-word combinations.  Score with more weight than single words -- 5X as I write this comment.
				$query = "SELECT * from salixwordstats where firstword like '$Word1' AND secondword LIKE '$Word2' LIMIT 3";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					{
					while($Values = $result->fetch_assoc())
						{
						$Factor = 1;
						if($Values['totalcount'] < 10) //Reduce impact if only seen few times
							$Factor = ($Values['totalcount'])/10;
						foreach($Fields as $F)
							{
							$OneScore = 10*$Factor * $Values[$F.'Freq'];
							if($OneScore > $MaxScore2[$F])
								$MaxScore2[$F] = $OneScore;
							}
						}
					}
				foreach($Fields as $F)
					$ScoreArray[$F] = $MaxScore2[$F]+$MaxScore1[$F];
				//$this->printr($ScoreArray,"Raw $Word1 $Word2");
				if("$Word1 $Word2" == mb_convert_case("$Word1 $Word2",MB_CASE_TITLE))
					{
					$ScoreArray['locality'] += 100; //Title case indicates proper noun, probably location name
					}
				}
		return $ScoreArray;
		}
		
		
	private function ScoreOneLine($L, &$Field, &$Score)
		{ //Score a line for wordstats
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$ExcludeWords = array('verbatimAttributes'=>array("herbarium","institute","university","botanical"),'habitat'=>array("herbarium","university"));
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
		if(isset($ExcludeWords[$Field]))
			{
			foreach($ExcludeWords[$Field] as $Ex)
				{
				//echo "Checking for $Ex in {$this->LabelLines[$L]}<br>";
				if(stristr($this->LabelLines[$L],$Ex) !== false)
					{
					//echo "Found $Ex<br>";
					//echo "Score = $Score<br>";
					$Score -= 1000;
					}
				}
			}
		return;
		}
		
	private function ScoreString($TempString,&$Field, &$Score)
		{//Called by ScoreOneLine and also by associated species routine
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$BadWords = "(\b(copyright|herbarium|garden|database)\b)i";
		$ScoreArray  = array_fill_keys($Fields,0);
		//echo "Looking for badwords in $TempString<br>";
		if(preg_match($BadWords,$TempString) > 0)
			{
			//echo "Found<br>";
			
			return;
			}
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
			$query = "SELECT * from salixwordstats where firstword like '$Word1' AND secondword LIKE '$Word2' ORDER BY totalcount DESC LIMIT 3";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				$Score += 5*$this->ScoreWordStatResult($result,$Field);
			}
		//$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL LIMIT 3";
		$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL ORDER BY totalcount DESC LIMIT 3";
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
		if(strpos("associatedCollectors,identifiedBy,associatedTaxa",$Field) > 0 && $this->Results[$Field] != "")
			$this->Results[$Field] .= "; ".$String;  //Append
		else if($this->Results[$Field] != "")
			$this->Results[$Field] .= ", ".$String;
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
		//$this->PregStart['verbatimAttributes'] = "(^(habit)\b)i";
		$this->PregStart['verbatimCoordinates'] = "(^(utm|Latitude|Longitude|Lat|Long)\b)i";
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
		$this->LabelLines[$L] = $this->RemoveStartWordsFromString($this->LabelLines[$L], $Field);
		return;
		}

	private function RemoveStartWordsFromString($TempString,$Field)
		{
		//echo "In:  $TempString...<br>";
		$PF = $this->PregStart[$Field];
		if($Field == 'associatedTaxa')
			$PF = str_replace("associated with","associated with|with", $PF);
		if($PF == "")
			return $TempString;
		$match=array();
		if($this->PregStart[$Field] == "" )
			return $TempString;
		if($TempString == "")
			return $TempString;
		//echo "PF = $PF<br>";
		$Found = preg_match($PF, $TempString,$match);
		if($Found === 1)
			{
			$TempString = str_ireplace($match[0],"",$TempString);
			$TempString = trim($TempString,": ,;-.\t");
			}
		return $TempString;
		
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
	
	private function GetFamilySyn()
		{
		$this->FamilySyn = array("Agavaceae"=>"Asparagaceae");
		
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
		//$Out = str_replace("\n","<br>",$Out);
		//echo $Out;
		}
	echo "<br>";
	}


}
?>