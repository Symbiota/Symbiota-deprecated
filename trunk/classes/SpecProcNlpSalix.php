<?php
include_once($serverRoot.'/classes/SalixUtilities.php');
include_once($serverRoot.'/classes/OccurrenceUtilities.php');

/* This class extends the SpecProcNlp class and thus inherits all public and protected variables and functions 
 * The general idea is to that the SpecProcNlp class will cover all shared Symbiota functions (e.g. batch processor, resolve state, resolve collector, test output, etc)
 */ 
/* The general approach of the algorithm is as follows:
	The label is pre-formatted and separated into lines, broken both at existing newlines and also at some semi-colons and periods.
	Some separated lines are re-joined if the previous one ends in words such as by, at, on, etc.
	Each field is processed one at a time, starting with Latitude/Longitude.  Order matters.
	For each field (in general): 
		Lines are evaluated, ranked and sorted for probability of containing that field.
		Relevant content is focused on in the most probable line(s) (using various techniques), extracted and added to the Results array
		Content that was extracted is generally deleted from the line so that other fields won't mistakenly grab it.
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
	private $PregMonths ="(\b(jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec|january|february|march|april|june|july|august|september|october|november|december|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|deciembre|janvier|febrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre)\b\.?)";
	private $LabelLines=array();
	private $PregStart = array();
			//An array of regular expressions indicating the start words for many fields.
	private $Family = "";
			//Made global as a tentative family for other algorithms to consider
	private $LichenFamilies =  array();
			//A list of all lichen families.  Mainly useful during debug, but helps to identify which specimens may have substrates.
	private $FamilySyn = array();
			//List of family synonyms to help interpret old labels
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
		
		
		//Set the keys for a few arrays to the names of the return fields
		$this->Results = array_fill_keys($this->ResultKeys,'');
		$this->Assigned = array_fill_keys($this->ResultKeys,-1);
		$this->PregStart = array_fill_keys($this->ResultKeys,'');
		$this->GetLichenNames(); //Get list of Lichen families to help find substrates
		$this->GetFamilySyn();
		//****************************************************************
		// Do some preformatting on the input label text
		//****************************************************************



		if(strpos($this->Label,"°") > 0)
			{//The degree symbol is the main character above 122 that must be preserved.
			$this->Label = str_replace("°","17*17",$this->Label);
			}
		
		//Convert everything to English letters.  This may need to be modified for some users...
	    $this->Label = preg_replace("/[áàâãªä]/u","a",$this->Label);
		$this->Label = preg_replace("/[ÁÀÂÃÄ]/u","A",$this->Label);
		$this->Label = preg_replace("/[ÍÌÎÏ¡]/u","I",$this->Label);
		$this->Label = preg_replace("/[íìîï]/u","i",$this->Label);
		$this->Label = preg_replace("/[éèêë]/u","e",$this->Label);
		$this->Label = preg_replace("/[ÉÈÊË]/u","E",$this->Label);
		$this->Label = preg_replace("/[ôóòôõºö]/u","o",$this->Label);
		$this->Label = preg_replace("/[ÓÒÔÕÖ]/u","O",$this->Label);
		$this->Label = preg_replace("/[úùûüú]/u","u",$this->Label);
		$this->Label = preg_replace("/[ÚÙÛÜ]/u","U",$this->Label);
		//$this->Label = preg_replace("/[ÚÙÛÜ]/u","U",$this->Label);
		$this->Label = preg_replace("/[’‘‹›‚]/u","'",$this->Label);
		$this->Label = preg_replace("/[“”„]/u",'"',$this->Label);
		$this->Label = preg_replace("/[«»]/u",'-',$this->Label);
		$this->Label = str_replace("–","-",$this->Label);
		$this->Label = str_replace(" "," ",$this->Label);
		$this->Label = str_replace("ç","c",$this->Label);
		$this->Label = str_replace("Ç","C",$this->Label);
		$this->Label = str_replace("ñ","n",$this->Label);
		$this->Label = str_replace("Ñ","N",$this->Label);
		$this->Label = str_replace("¿","-",$this->Label);
		$this->Label = str_replace("£","L",$this->Label);
		
		for($i=0;$i<strlen($this->Label)-4;$i++)
			{
			if(ord($this->Label[$i]) >= 226)
				$this->Label = substr($this->Label,0,$i)."-".substr($this->Label,$i+3);// was +5
			else if(ord($this->Label[$i]) >= 224)
				$this->Label = substr($this->Label,0,$i)."-".substr($this->Label,$i+4);
			else if(ord($this->Label[$i]) >= 192)
				$this->Label = substr($this->Label,0,$i)."-".substr($this->Label,$i+3);
			}	

		setlocale(LC_ALL,"en_US");
		if(mb_detect_encoding($this->Label,'UTF-8') == "UTF-8")
			{
			$this->Label = iconv("UTF-8","ISO-8859-1//TRANSLIT",$this->Label);
			$this->Label = str_replace("í","i",$this->Label);
			}
		$this->Label = str_replace("17*17","°",$this->Label);
		
		if(strlen($this->Label) < 10)
			return;
		
		//A few replacements to format the label making it easier to parse
		
		//Eliminate the "dupl" in determiner line
		$this->Label = preg_replace("(det(.)? dupl(.)?)i","Det.",$this->Label);
		//A fairly rare instance where both collector and determiner are same person on the same line. Split into two lines.
		$this->Label = preg_replace("([\n\r]{1,2}((leg|coll|col).? (&|and) (det).?)(.+)[\n\r]{1,2})i","\n$2. $5\n$4.$5",$this->Label);
		//echo $this->Label."<br>";
		//Make sure space between NSEW cardinal directions and following digits, for Lat/Long 
		$this->Label = preg_replace("(([NESW]\.?)(\d))","$1 $2",$this->Label);
		$this->Label = preg_replace("((\d)([NESW]))","$1 $2",$this->Label);
		//Make sure space between altitude and indicator (ft or m)
		$this->Label = preg_replace("((\d)((m|ft)(\s|.)))","$1 $2",$this->Label);
		//Make sure space between period and capital letter, such as an initial followed by a name.
		$this->Label = preg_replace("((\.)([A-Z]))","$1 $2",$this->Label);

		//Sometimes the double quote in Lat/Long gets OCRd as a single.  Look for likely instances and correct.
		$this->Label = preg_replace("(([0-9]{1,2}\'\s?[0-9]{1,2})(\')(\s?[NSEW]))","$1\"$3",$this->Label);
		
		//Sometimes the minute quote mark gets OCRd as a 1 (one) or l (el).  Look for and correct.
		$this->Label = preg_replace("(\b([0-9]{1,2})1\s?([0-9]{1,2}\")(\s?[NSEW]\b))","$1'$2$3",$this->Label);
		
		//Sometimes year gets separated to next line from day/month.
		$Preg = "(([0-9]{1,2}\s+\b{$this->PregMonths})\.[\s]+(\b[0-9]{4,4}\b))i";
		$this->Label = preg_replace($Preg,"$1, $4",$this->Label);

		//Connect some lines when joined by a connecting preposition or hyphen
		$this->Label = preg_replace("(\b(of|to|by|at|in|over|under|on|from)\s?[\r\n]{1,2})i","$1 ",$this->Label);//Prepositions at end of line
		$this->Label = preg_replace("([\r\n]{1,2}\b(of|to|by|at|in|over|under|on|from)\b)","$1",$this->Label);//Prepositions at start of line
		$this->Label = preg_replace("(\b([A-Z]?[a-z]{2,10})- ?[\n\r]{1,2}([a-z]{2,10}))","$1$2",$this->Label);//Hyphen at end of line

		
		
		
		
		//Remove double spaces and tabs
		$this->Label = str_replace("\t"," ",$this->Label);
		while(strstr($this->Label,"  ") !== false)
			$this->Label = str_replace("  "," ",$this->Label);
		
		//Remove (?) <>.  Perhaps should leave in...?  Would a collector ever write e.g. "Found > 500 m. elevation"? "Collected < 100 meters from stream"?
		$this->Label = str_replace("<","",$this->Label);
		$this->Label = str_replace(">","",$this->Label);
		$this->Label = str_replace("^"," ",$this->Label);
		$this->Label = str_replace("’","'",$this->Label);
		$this->Label = str_replace("°;","°",$this->Label);

		//$match = array();
		$FromTo = array("l"=>"1","O"=>"0");
		foreach($FromTo as $From=>$To)
			{//Catch cases where zero or one mis-OCR'd as oh or el.
			$this->Label = preg_replace("(([-0-9/.])".$From."([0-9/.]))",'${1}'.$To.'${2}',$this->Label);
			}
		$this->Label = str_replace("(M0)","(MO)",$this->Label);
		//Separate at semicolons
		$this->Label = str_replace(";","\r\n",$this->Label);
		
		//Separate lines at a few start words
		foreach(array("Collected by:","Coll. Date","Collector:","Date:","Det.","Altitude:","Altitude about","Determined by","Determiner") as $SplitPoint)
			{
			$this->Label = str_ireplace("$SplitPoint","\r\n$SplitPoint",$this->Label);
			}
		
		//A series of rare but easy to correct OCR errors.
		$this->Label = str_ireplace("Jon!","Joni",$this->Label);

		
			
		//Remove empty lines
		$this->Label = str_replace("\r\n\r\n","\r\n",$this->Label);
		$this->Label = str_replace("/","\r\n",$this->Label);
		
		$this->LabelLines = preg_split("[(?<=[a-z]{4,4})\.|(?<=[a-z]);|\n]",$this->Label,-1,PREG_SPLIT_DELIM_CAPTURE);
		//Split lines at semicolons and periods.  But not at periods unless the preceding is
		//at least 4 lower case letters.  This preserves abbreviations.  
		
		$L = 0;
		while($L < count($this->LabelLines))
			{
			$this->LabelLines[$L] = trim($this->LabelLines[$L]," \t");
			if(strlen($this->LabelLines[$L]) <3) //Remove lines less than 3 characters long
				unset($this->LabelLines[$L]);
			$L++;
			}
		$this->LabelLines = array_values($this->LabelLines); //renumber the array
		
		//Cases where the institute (capitalized) gets included in another line.  Separate them.
		$L=0;
		while($L < count($this->LabelLines))
			{
			if(preg_match("(\b(INSTITUTE|INSTITUTO|MUNICIPAL|HERBARIUM|UNIVERSITY|BOTANICAL|GARDEN|RESERVA|NATIONAL|MUSEUM|MUSEO)\b)",$this->LabelLines[$L],$match))
				{
				$Found = preg_match("(([^A-Z]{2,20})\s([^a-z0-9]+)\Z)",$this->LabelLines[$L],$match);
				if($Found > 0)
					{
					//$this->printr($match,"Using Preg");
					if(strlen($match[2]) > 20)
						{
						$this->LabelLines[$L] = str_replace($match[2],"",$this->LabelLines[$L]);
						array_splice($this->LabelLines,$L+1,0,$match[2]);
						}
					}
				}
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
			$this->LabelArray[] = $WordsArray; 
			}

		$this->InitStartWords();//Initialize the array of start words, which are hard coded in the function.
								//Also creates an array $LineStart where the element for each line indicates if it has a start word.

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
		//$this->Results['rawnotes'] = "SALIX Version 0.6";
		//$this->printr($this->Assigned,"Assigned");
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
			$query = "SELECT sciname FrOM taxa WHERE UnitName1 LIKE '$First'";
			//echo "Query = $query<br>";
			$result = $this->conn->query($query);
			if($result->num_rows >0)  //Found matches to Genus.
				{
				$Score= $this->LevenshteinCheck("$First $Second",$result,$CloseSci);
				//echo "Check sci: $First<br>";
				}
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
			//echo "Rank {$RankArray[$L]}, {$this->LabelLines[$L]}<br>";
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
			if($SciScore == 0)
				if(preg_match("([A-Z][a-z]{3,20})",$WordsArray[0][$w][0]))
					{
					$query = "SELECT sciname FrOM taxa WHERE UnitName1 LIKE '{$WordsArray[0][$w][0]}' LIMIT 1";
					//echo $query."<br>";
					$result = $this->conn->query($query);
					if($result->num_rows == 1)
						$SciScore += 1;
					}
			if($SciScore == 0)
				if(preg_match("([a-z]{3,20})",$WordsArray[0][$w][0]))
					{
					$query = "SELECT sciname FrOM taxa WHERE UnitName2 LIKE '{$WordsArray[0][$w][0]}' LIMIT 1";
					$result = $this->conn->query($query);
					if($result->num_rows == 1)
						$SciScore += 1;
					}
			if($SciScore == 0)
				if(preg_match("(\b(ssp|var|forma)\b)",$Phrase) > 0)
					$SciScore += 1;
			if($SciScore == 0)
				if(preg_match("((and|&) [A-Z][a-z]{3,20})",$Phrase) > 0)
					$SciScore += 1;
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
		
		$RankArray = array();
		$match = array();
		for($L=0;$L<count($this->LabelLines);$L++)
			{//Score the line
			//echo "{$this->LabelLines[$L]}<br>";
			$Rank = 0;
			//if(count($this->LabelArray[$L]) < 2)
			//	$Rank = -10; //Not likely if the line is only one word long.
			if($this->Assigned['sciname'] == $L)
				$Rank -=100;
			if($this->LineStart[$L] == "associatedTaxa" && !$this->Assigned['associatedCollectors'] != $L)
				{
				$Rank = 100;
				}
			if($this->Assigned['sciname'] == $L || $this->Assigned['associatedCollectors'] == $L)
				$Rank-=100; //This line is either the scientific name line, or associated collectors line.  Can't be associated species line.
			if($this->LineStart[$L] == 'substrate')
				$Rank -= 100;
			if($this->LineStart[$L] == 'habitat')
				{
				$Rank -= 100;
				//echo "Assigning -100 to {$this->LabelLines[$L]}<br>";
				}
			$CommaCount = substr_count($this->LabelLines[$L], ","); //Commas indicate a list, perhaps of associated species.
			if($CommaCount > 2)
				$CommaCount = 2;
			$Rank += $CommaCount;
			if($this->Assigned['associatedCollectors'] != "" && $this->Assigned['associatedCollectors'] != $L)
				{//As long as associated collectors is clearly not on this line, then the word "associated" means this is probably the line.
				if(strpos($this->LabelLines[$L],"associated") !== false)
					$Rank += 20;
				}
			$Found = preg_match("([w|W]ith ([A-Z][a-z]{4,20}) ([a-z]{3,20}))",$this->LabelLines[$L],$match); 
			if($Found === 1 && $this->Assigned['associatedCollectors'] != $L)	
				{
				$Rank+= 20;
				if($this->ScoreSciName($match[1],"",true))
					$Rank += 90;
				$Found=0;
				}
			else
				{
				$Found = preg_match("([w|W]ith ([A-Z][a-z]{4,20}))",$this->LabelLines[$L],$match); //With followed by a potential sciname is a strong indicator
				if($Found === 1 && $this->Assigned['associatedCollectors'] != $L)	
					{//Found the word "with", and it isn't for associated collectors.
					$Rank+= 10;
					if($this->ScoreSciName($match[1],"",true))
						$Rank += 90;
					}
				}
			$Rank += (preg_match_all("(([A-Z][a-z]{3,20}) ([a-z]{4,20}))",$this->LabelLines[$L],$match))/2; //Add a point for each potential sciname
			$Rank += preg_match_all("(\b(by|ssp)\b)",$this->LabelLines[$L]); //Add a point for each ssp
			if($Rank < 10 && count($match[0]) > 0)
				{
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
			$Rank += preg_match_all("([a-z], )",$this->LabelLines[$L]); //Add for S. mirabilis... format 
			if(preg_match("(\A([A-Z])\. [a-z]{3,20}\Z)",$this->LabelLines[$L],$match) > 0)
				{
				//$this->printr($match,"This match");
				$Rank+=5;
				if($L > 0 && $RankArray[$L-1] > 10 && $match[1] == $this->LabelLines[$L-1][0])
					$Rank += 5;
				}
			//echo "$L $Rank, {$this->LabelLines[$L]}<br>";
			//echo "Rank 14 = $Rank<br>";
			if($Rank >= 6)
				$RankArray[$L] = $Rank;
			}
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

		if($MaxLine < 1)
			return $RankArray;
		//Work up from this line, including lines as long as their rank is high enough, if it's not clearly the first line.
		$Start = $MaxLine;
		$End = $MaxLine;
		if($Max < 99)
			{//Only if it is clearly not the first line.
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
		//echo "Start  = $Start: {$this->LabelLines[$Start]}, End = $End<br>";
		//If the list contains several lines, make sure there isn't a mis-OCR causing false end to the list
		$TestEnd = $End+2;
		//while($TestEnd < count($this->LabelLines)-2 && $RankArray[$TestEnd] >= 10)
		while(isset($RankArray[$TestEnd]) && $RankArray[$TestEnd] > 5)
			{
			$End = $TestEnd;
			$TestEnd ++;
			}
		//echo "Then Start  = $Start, End = $End<br>";
		
		//$this->printr($this->Assigned,"Assigned");
		//Now $Start indicates the first line that has AT, and $End indicates the last.
		if($RankArray[$Start] >= 100)
			{
			foreach($RankArray as $Key=>$Value)
				if($Key < $Start)
					unset($RankArray[$Key]);
			}
		else
			{
			for($A = $Start - 1;$A >= 0;$A--)
				{
				if(preg_match("(\b[a-z]{3,20}\b)",$this->LabelLines[$A] == 0) && preg_match("([A-Z]\. [a-z]{3,20})") == 0)
					unset($RankArray[$A]);
				if($A < $this->Assigned['sciname'])
					unset($RankArray[$A]);
				
				}
			}
		for($A = $End + 1;$A < count($this->LabelLines);$A++)
			unset($RankArray[$A]);
		//$this->printr($RankArray,"Reduced RA");
		/*
		for($L=$Start+1;$L<count($this->LabelLines);$L++)
			{
			//echo "Check {$this->LabelLines[$L]}<br>";
			if(preg_match("(\A([A-Z][a-z]{3,20})( [a-z]{3,20})?\Z)",$this->LabelLines[$L],$match))
				$RankArray[$L] = 10;
			else
				break;
			}
		*/
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
		if(preg_match("(N \d{1,2} \d{1,2} \d{3,3}, E \d{1,2} \d{1,2} \d{3,3})",$this->LabelLines[$L]))
			{
			if($this->GetUTM($L,true))
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
	private function GetUTM($L,$Baker = false)	
		{
		$match=array();
		$TempString = $this->LabelLines[$L];
		if($Baker)
			{
			$Preg = "(N (\d{1,2}) (\d{1,2}) (\d{3,3}), E (\d{1,2}) (\d{1,2}) (\d{3,3}))";
			$Found = preg_match($Preg,$this->LabelLines[$L],$match);
			$Verb = $match[0];
			$UTM = "12 ".$match[4].$match[5].$match[6]." ".$match[1].$match[2].$match[3];
			}
		else
			{
			$Found = preg_match("((utm|UTM[ :-]*)([0-9]{1,2}\s*[A-Z]\s+[0-9]{6,8}\s*[EN]\s+[0-9]{6,8}\s*[EN]))",$TempString,$match);
			if(!$Found)
				return false;
			$TempString = $match[2];
			$Verb = $match[0];
			$UTM = preg_replace("(([0-9]{1,2})\s*([A-Z]\s+[0-9]{6,8})\s*([EN]\s+[0-9]{6,8})\s*([EN]))","$1$2$3$4$5",$TempString);
			}
		$OU = new OccurrenceUtilities;
		$LL = $OU->parseVerbatimCoordinates($UTM,"UTM");
		if(count($LL) > 0)
			{
			$this->AddToResults('verbatimCoordinates',$Verb,$L);
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
		//echo "$OneLine<br>";
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
		$PregWords1 = "([\d,]{2,5})";
		$PregWords2 = "(\b(meters|m.|m|feet|ft.|ft)\b)";
		$PregWords3 = "(\b(elevation|elev|altitude|alt)\b)";
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
			$Found = preg_match_all($PregWords1, $this->LabelLines[$L],$match);
			if($Found > 0)
				$RankArray[$L] += 5;
			$Found = 10*preg_match_all($PregWords2, $this->LabelLines[$L],$match);
			$Found = 20*preg_match_all($PregWords2, $this->LabelLines[$L],$match);
			$RankArray[$L] += $Found;
			if(preg_match("([0-9,]{2,6})",$this->LabelLines[$L]) != 1)
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
			//echo "Testing $L, $Value: {$this->LabelLines[$L]}<br>";
			if($Value < 5)
				break;
			$TempString = $this->LabelLines[$L];
			//$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*[about ]*([0-9,-]+)([\s]*)(m(eter)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
			$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*[about ]*([0-9, -]+)\s?\d?([\s]*)(m(eter)?s?\b|f(ee)?t?\b|\'))i",$TempString,$match);
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
			$this->LabelLines[$L] = $this->RemoveStartWordsFromString($this->LabelLines[$L],'minimumElevationInMeters');
			//echo "To this: {$this->LabelLines[$L]}<br>";
			}
		$Preg = "(\d+[ to-]+$TempString)";
		$Found = preg_match($Preg, $this->Label,$match);
		if($Found > 0)
			{
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
			if($Alt >= 0 && $Alt < 5000)
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
		//$this->PrintRank($RankArray,"$Field");
		reset($RankArray);
		//echo "Current = ".current($RankArray)."<br>";
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
			//echo "Check ($Field): $L: {$this->LabelLines[$L]}<br>";
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
			//echo "Here 1<br>";
			if($Field == 'recordedBy' && $this->Assigned['recordedBy'] >= 0 && $L > $this->Assigned['recordedBy']+1)
				break;//There shouldn't be any more associated collectors more than one line after main collector
			//echo "Here 2<br>";
			}
		return;
		}

	private function GetNamesFromLine($L,$Field)
		{
		//echo "Testing $Field: {$this->LabelLines[$L]}<br>";
		//$BadWords = "(\b(copyright|herbarium|garden|vascular|specimen|database|institute|instituto|plant|county|pacific|trail|museum|mount|range|image|island[s]?)\b)i";
		//if(preg_match($BadWords,$this->LabelLines[$L]) > 0)
		//	return false;
		$this->RemoveStartWords($L,'recordedBy');
		$match=array();
		
		$Preg = "(\b([DM]r\.?\s)([A-Z][\. ]*\s[A-Z][\. ]*)\s(and|&)\s(Mrs\.?\s)([A-Z][\. ]*\s[A-Z][\. ]*)\s([A-Z][a-z]{3,20}))";
		$Found = preg_match($Preg, $this->LabelLines[$L],$match);
		if($Found > 0)
			{
			//Convert Mr. A. B. and Mrs. C. D. Smith to A. B. Smith, C. D. Smith.
			$this->LabelLines[$L] = str_replace($match[0],$match[2]." ".$match[6].", ".$match[5]." ".$match[6],$this->LabelLines[$L]);
			}
		$Found = preg_match("((\bcollected.+\bby\b)\s+([A-Za-z .]{2,25}))i",$this->LabelLines[$L],$match);
		if($Found >0) //like:  Collected and prepared by John P. Smith
			{
			$Name = mb_convert_case($match[2], MB_CASE_TITLE);
			$this->AddToResults($Field,$Name,$L);
			$this->LabelLines[$L]= str_replace($match[1],"",$this->LabelLines[$L]);
			return true;
			}
		$Preg = "(\b([A-Z][.])\s(\b[A-Z][a-z]{2,20}\b)\s(\b[A-Z][a-z]{2,20}\b)\s(No|[0-9]{2,6}))";
		//(Initial) (middle name) (last name) followed by No or number
		$Found = preg_match_all($Preg, $this->LabelLines[$L],$match);
		if($Found > 0) 
			{
			$match[0][0] = $match[1][0]." ".$match[2][0]." ".$match[3][0];
			if(preg_match("(\b(january|february|september|october|november|december)\b)i",$match[0][0]) > 0)
				$Found = 0;
			}
		if($Found == 0)
			{
			$Preg = "(\b(([A-Z][a-z]{2,20} )|([A-Z][\.]? ))([A-Z][\.] )?([A-Z][a-z]{2,20}\b))";
				//(Initial or first name), (optional middle initial), (last name).
			$Found = preg_match_all($Preg, $this->LabelLines[$L],$match);
			//$this->printr($match,"Name Match");
			}
		if($Found > 0)
			{
			for($N=0;$N<$Found;$N++)
				{
				$Name = $match[0][$N];
				//echo "Before CRB: Name = $Name<br>";
				if($this->ConfirmRecordedBy($Name) < 0)
					continue;
				//echo "Adding here<br>";
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
				if($Field == 'identifiedBy')
					return true;
				}
			if($Field == "recordedBy" && ($L < count($this->LabelLines)-1 && ($this->LineStart[$L+1] == "" || $this->LineStart[$L+1] == "associatedCollectors")))
				{
				//echo "Checking next line<br>";
				return $this->GetNamesFromLine(++$L,$Field);//Recursively calls this routine to add possible associated collectors.
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
	private function ConfirmRecordedBy(&$Name)
		{//Check for the name in the omoccurrences table.  Lower score if it looks like a university, herbarium, county, etc.
		$match=array();
		$Score = 0;
		$PregNotNames = "(\b(arizona|municip|herbarium|agua|province|university|mun|county|botanical|garden|reserva|conserva|comunidad|pacific|date|north|south|canal|mountain|national|image|island[s]?)\b)i";  //Known to be confused with names
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
			//echo "Name $Name: $Score<br>";
			if($result->num_rows > 0)
				{
				//echo "Adding 10<br>";
				$Record = $result->fetch_assoc();
				$Name = $Record['recordedBy'];
				return 10;
				}
			//echo "Returning $Score from Confirm<br>";
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
			$PregNotNames = "(\b(municip|trail|peak|mountain|herbarium|agua|province|university|mun|county|botanical|garden|pacific|island[s]?)\b)i";  //Known to be confused with names
			$RankArray[$L] -= 5*(preg_match_all($PregNotNames,$this->LabelLines[$L],$match));
			
			if(preg_match("(By[:\s]{0,2}(\b[A-Z][a-z]{3,20}\b)\s+(\b[A-Z][a-z]{3,20}\b))", $this->LabelLines[$L]))
				{
				$RankArray[$L] += 5;
				//echo "Found By<br>";
				}
			if(count($this->LabelArray[$L]) < 2)
				{
				$RankArray[$L]-=100;
				continue;
				}
			if($this->LineStart[$L] == $Field)
				{
				//echo "Found Start word in {$this->LabelLines[$L]}<br>";
				$RankArray[$L] += 1000;
				}
			if($this->StatScore[$L]['Score'] > 100)
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
						//echo "$L Decrease 1<br>";
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
			if($Field == 'recordedBy' && $this->LineStart[$L] == 'recordNumber')
				{
				//echo "Recordnumber<br>";
				$RankArray[$L] += 10; //Decrease if start word for another field
				}
			if($this->SingleWordStats($this->LabelLines[$L],'locality') > 100)
				{
				$RankArray[$L] -= 10;
				}
			if($Field == 'associatedCollectors' && $this->Assigned['recordedBy'] > $L)
				$RankArray[$L] -= 110; //Associated Collectors should always follow main collector
			
			if($Field == 'identifiedBy' && $this->Assigned['recordedBy'] == $L)
				$RankArray[$L] -= 10; //Determiner rarely on same line as collector (?)

			if($this->Assigned['infraspecificEpithet'] == $L)
				{
				$RankArray[$L] -= 100; //Must be author instead of collector
				}
			$Found = preg_match("(\b(([A-Z][a-z]{2,20} )|([A-Z][\.] ))([A-Z][\.] )?([A-Z][a-z]{2,20}\b))",$this->LabelLines[$L]);
			if($Found === 1) //Looks like a name
				{
				$RankArray[$L] += 10;
				//echo "{$RankArray[$L]}: Looks like name:  {$this->LabelLines[$L]}<br>";
				}
			//else
			{
				if($Field == "recordedBy" && preg_match("((\bcollected).+(\bby\b)\s+([A-Za-z .]{2,25}))i",$this->LabelLines[$L]) > 0)
					$RankArray[$L] += 10;
				else if($Field == "recordedBy" && preg_match("((\bcollected).+\b(by|in|at)\b)i",$this->LabelLines[$L]) > 0)
					{
					$RankArray[$L] -= 10;
					//echo "$L Reducing for collected in<br>";
					}
				//else
				//	$RankArray[$L] -= 10;
			}
			$RankArray[$L] += 3*preg_match("( [0-9]{3,10} ?)",$this->LabelLines[$L]); //Add a little for a number -- could be date or collection number.
			$RankArray[$L] += 10*preg_match("(collected)i",$this->LabelLines[$L]); //The word "collected" without "by" isn't sure, but it helps
			$RankArray[$L] -= 3*preg_match("((\bft\b)|(\bm\b))",$this->LabelLines[$L]); //Subtract if looks like altitude
			$RankArray[$L] -= (3*preg_match_all("([0-9]\.[0-9])",$this->LabelLines[$L],$match)); //Decimal not likely to be in collection number or date
			$RankArray[$L] -= 2*preg_match_all("(\(|\)|�|\")",$this->LabelLines[$L],$match); //Parenthesis on the line probably mean author, not collector.  Degree looks like lat/long.  Quote looks like lat/long
			$RankArray[$L] += 5*preg_match($this->PregMonths."i",$this->LabelLines[$L]); //Add a little for a month -- could be collection date
			if($L < count($this->LabelLines)-1)
				$RankArray[$L] += 2*preg_match($this->PregMonths."i",$this->LabelLines[$L+1]); //Add a little for a month -- next line could be collection date
			if($L >0)
				$RankArray[$L] += 2*preg_match($this->PregMonths."i",$this->LabelLines[$L-1]); //Add a little for a month -- previous line could be collection date
			if($Field != 'identifiedBy' && strpos($this->LabelLines[$L], "&") >0)
				{
				$RankArray[$L] += 4; // Could be associated collectors on the same line
				if(preg_match("([a-z]{2,20}\s&\s[A-Z][.a-z])",$this->LabelLines[$L]) > 0)
					$RankArray[$L] += 5; //Capital after ampersand even better.
				}
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
			//echo "$Field, $L: {$this->LabelLines[$L]}<br>";
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
		$BadFields = array('minimumElevationInMeters','family','sciname');
		$RankArray = array();
		$RankArray = array_fill(0,count($this->LabelLines),0);
		if($this->Assigned[$EventField] != '-1')
			{
			$L = $this->Assigned[$EventField];
			$RankArray[$L] = 10; //Most likely on the same line as the event
			while(++$L < count($this->LabelArray)) //Diminishing likelihood on subsequent lines
				$RankArray[$L] = 10 - 3*($L-$this->Assigned[$EventField]);
			$L = $this->Assigned[$EventField];
			while(--$L >= 0) //Less likelihood on previous lines
				$RankArray[$L] = 8 - 2*($this->Assigned[$EventField]-$L);
			}
		for($L=0;$L<count($this->LabelArray);$L++)
			{//Various characteristics increase or decrease probability
			$TempString = $this->LabelLines[$L];
			foreach($BadFields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					//echo "Badfield $F in {$this->LabelLines[$L]}<br>";
					$RankArray[$L] -= 100;
					}
				}
			if(preg_match("(\bdate\b)i",$TempString) > 0)	
				{
				$RankArray[$L] += 10;	
				}
			if(preg_match("(\b\d+\b)",$TempString) !== 1)
				{// Must be a number on the date line. (Unless roman numeral.)
				$Preg = "(([0-9]+[ ./-]{1,2})([IVX]+)([ ./-]{1,2}[0-9]+))i";
				$Found = preg_match($Preg, $TempString,$match);
				if($Found > 0)
					$RankArray += 10; //Probable roman numeral
				else
					{
					$RankArray[$L] -= 100;
					continue;
					}
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

			}
		asort($RankArray);
		return (array_reverse($RankArray, true));
		}

	//**********************************************
	private function DateFromOneLine($Field, $L,$Partial=false)
		{//Assumes standard format, d/m/y or m,d,y
		$RomanMonths = array("I"=>"Jan","II"=>"Feb","III"=>"Mar","IV"=>"Apr","V"=>"May","VI"=>"Jun","VII"=>"Jul","VIII"=>"Aug","IX"=>"Sep","X"=>"Oct","XI"=>"Nov","XII"=>"Dec");
		$match=array();
		$RealVDate = "";
		$VDate = "";
		$TempString = ($this->LabelLines[$L]);
		//echo "Getting date from {$this->LabelLines[$L]}<br>";
		if($Partial)
			$Preg = "(\b{$this->PregMonths}\s*(\b[0-9]{1,4}\b))i";
		else
			$Preg = "((\b[0-9]{1,4}\b)\s*{$this->PregMonths}[.,]*\s*(\b[0-9]{1,4}\b))i";
		$Found = preg_match($Preg,$TempString,$match);
		if($Found !== 1)
			{//Format April 21, 1929
			$Preg = "({$this->PregMonths}[.\s]*(\b[0-9]{1,4}\b)[.,]*\s*(\b[0-9]{1,4}\b)\s*)i";
			$Found = preg_match($Preg,$TempString,$match);
			if($Found)
				{
				$VDate = $match[2]." ".$match[3].", ".$match[4];
				$RealVDate = $match[0];
				}
			}
		if($Found !== 1)
			{//Format April, 1929
			$Preg = "({$this->PregMonths}[.,]?\s*(\b[0-9]{1,4}\b)\s*)i";
			$Found = preg_match($Preg,$TempString,$match);
			}
		//if($Found > 0)
				//echo "Found<br>";
		if($Found !== 0)
			{
			if($VDate == "")
				{
				$VDate = $match[0];
				$RealVDate = $VDate;
				}
			}
		else 
			{
			$Preg = "(([0-9]+[ ./-]{1,2})([IVX]+)([ ./-]{1,2}[0-9]+))i";
			$Found = preg_match($Preg, $TempString,$match);
			if($Found >0)
				{
				$Month = $RomanMonths[strtoupper($match[2])];
				$VDate = trim($match[1]," .-")."-".$Month."-".trim($match[3]," .-");
				$RealVDate = $match[0];
				}
			}
		if($Found == 0)
			{
			$Preg = "(\b(19[0-9]{2,2}|20[01][0-9])\b)";
			$Found = preg_match($Preg, $TempString,$match);
			if($Found !== 0)
				{
				$VDate = $match[0];
				$RealVDate = $VDate;
				}
			
			}
		if($Found!== 0)
			{
			$FrenchMonths = array("janvier","febrier","mars","avril","mai","juin","juillet","aout","septembre","octobre","novembre","decembre");
			$VDate = str_replace($FrenchMonths,$RomanMonths,$VDate);//Converts french dates to English (Using the same Roman Numeral conversion array)
			$OU = new OccurrenceUtilities;
			$FormattedDate = $OU->formatDate($VDate);
			if($FormattedDate != "")
				{
				$this->AddToResults($Field, $FormattedDate,$L);
				if($RealVDate != "")
					$VDate = $RealVDate;
				$this->LabelLines[$L] = str_replace($VDate,"",$this->LabelLines[$L]);
				if($Field == "eventDate")
					$this->AddToResults("verbatimEventDate",$VDate,$L);
				$this->RemoveStartWords($L,$Field);
				return true;
				}
			}
		return;
		}

//************************************************************************************************************************************
//***************** RecordNumber Functions *******************************************************************************************
//************************************************************************************************************************************
	
	
//******************************************************************
	private function GetRecordNumber($L = -1)
		{
		$match=array();
		//Check for start word
		for($L1=0;$L1<count($this->LabelLines);$L1++)
			{
			//echo "Checking $L1: {$this->LabelLines[$L1]}<br>";
			if($this->LineStart[$L1] =='recordNumber' || $this->CheckStartWords($L1, 'recordNumber'))
				{
				$this->RemoveStartWords($L1,'recordNumber');
				$L = $L1;
				//echo "L=$L<br>";
				break;
				}
			}
		//Assume on the same line as recordedBy.  If no recordBy, then return empty.
		if($this->Assigned['recordedBy'] == "")
			return; //No collector, can't have collection number
		if($L==-1)
			$L = $this->Assigned["recordedBy"];//Find the collectors line
			
		if($L < 0)
			return;
		//Date will have already been removed from the line, so any number remaining is likely the collection number.
		$WordArray = preg_split("( )",$this->LabelLines[$L],-1,PREG_SPLIT_DELIM_CAPTURE);
		for($W=0;$W<count($WordArray);$W++)
			{//Check words one at a time for a match to typical collection number format.
			$Word= trim($WordArray[$W]);
			
			$PM=preg_match('(([#0-9]+-?)([0-9a-zA-Z]*)(-[0-9]*)?)',$Word,$match);
			//$this->printr($match,"RecNum");
			if($Word != "" && !ctype_alpha($Word) && $PM!==0)
				{//Consists of digits, optional letters, and an optional hyphen.
				$Match = $match[0];
				//echo "Match = $Match<br>";
				if(preg_match("(($Match)°)",$this->LabelLines[$L]))
					continue;
				if(strpos($Word,"#") !== false && preg_match("([0-9])",$Word) === 0)
					{
					if($W < count($WordArray)-1 && preg_match("([0-9])", $WordArray[$W+1]) === 1)
						{
						$Match .= " ".$WordArray[$W+1];
						}
					}
				$this->AddToResults('recordNumber', $Match,$L);
				$this->LabelLines[$L] = str_replace($Match,"",$this->LabelLines[$L]);
				return;
				}
			}
		if($L == $this->Assigned['recordedBy'])
			$this->GetRecordNumber($L+1);
			
		}

//******************************************************************
//******************* Country/State Functions ***************************
//******************************************************************


	function GetCountryState()
		{
		if($this->Results['decimalLatitude'] != "" && $this->Results['decimalLongitude'] != "")
			{
			$Lat = $this->Results['decimalLatitude'];
			$Long = $this->Results['decimalLongitude'];
			}
		else
			{
			$Lat = 0;
			$Long = 0;
			}
			
		$Label = str_replace("-"," ",$this->Label);
		$Found = preg_match("(([A-Za-z]{2,20}ACEAE)\s+(of)\s+(\b\S+[\b-])\s+(\b\S+\b)?)i",$Label,$match);
		if($Found !== 1)
			$Found = preg_match("((plants|flora|lichens|algae|fungi|cryptogams)\s(of|de|du)\s+(\b\S+\b)(\s?\b\S+\b)?)i",$Label,$match);
		if($Found)
			{// Found "Plants of...".  Look for state or country
			//$this->printr($match,"Algae of");
			$Name1 = trim($match[3]," .,;:");
			$Name2 = "";
			if(count($match) > 4)
				$Name2 = $Name1." ".trim($match[4]," .,;:");
			$this->PlantsOf($Name2);
			if($this->Results['country'] == "")
				$this->PlantsOf($Name1);
			if($this->Results['country'] == "" && count($match) > 4)
				$this->PlantsOf(trim($match[4]));
			}
		if($this->Results['country'] != "") //Found it.
			return;

		//"Plants of" didn't work.  Look for "xxx State".
		$match = $this->LabeledRegion("stateProvince");
		if($match != false)
			{ //Found "xxx province or state"
			$State = $match[1]." ".$match[2];
			//echo "State might be $State<br>";
			if(!$this->PlantsOf($State))
				$this->PlantsOf(trim($match[2]));
			}
		if($this->Results['country'] != "") //Found it.
			return;
		//Look for "xxx County".
		$match = $this->LabeledRegion("county");
		if($match != false)
			{ //Found "xxx County"
			$County = $match[1]." ".$match[2];
			//echo "County might be $County<br>";
			if(!$this->ScanForState($County))
				$this->ScanForState(trim($match[2]));
			}
		$this->SeekState(223,-1);
		if($Lat != 0 || $Long != 0)
			{
			//echo "Here 2<br>";
			if($this->GetFromLatLong($Lat, $Long))
				return;
			}
		
		}

	//*****************************************************************************
	private function LabeledRegion($Type)
		{ //Looks for a labeled region, such as County or province on the label
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|vascular|university|specimen|botanical\b))i";
		$PrePreg = "((\b[A-Z][a-z]{1,20}\b)?[\s]*(\b[A-Z][a-z]{1,20}\b)[\s]*";
		switch($Type)
			{
			case "stateProvince":
				//$Preg = "((\b[A-Z][a-z]{1,20}\b)?[\s]*(\b[A-Z][a-z]{1,20}\b)[\s]*(\b(prov|province|state|estado)\b))i";
				$Preg = "(\bprov|province|state|estado\b)";
				break;
			case "county":
				//$Preg = "((\b[A-Z][a-z]{1,20}\b)?[\s]*(\b[A-Z][a-z]{1,20}\b)[\s]*(\b(County|Co)\b))i";
				$Preg = "(\b(County|Co)\b)i";
				break;
			default:
				return false;
			}
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			if(preg_match($BadWords,$this->LabelLines[$L]))
				continue;
			//echo "Testing {$this->LabelLines[$L]}<br>";
			$Found = preg_match($Preg."i",$this->LabelLines[$L],$match);
			
			if($Found)
				{
				$FullPreg = $PrePreg."(".$Preg."))i" ;
				$Found = preg_match($PrePreg."(".$Preg."))i",$this->LabelLines[$L],$match);
				if($Found == 0)
					{
					$FullPreg = "(".$Preg.$PrePreg."))i";
					//echo "Full = $FullPreg<br>";
					$Found = preg_match($FullPreg,$this->LabelLines[$L],$match);
					}
				if($Found)
					{
					$match['Line'] = $L;
					//$this->printr($match,"Match");
					return $match;
					}
				}
			}
		return false;
		}

	//*****************************************************************************
	function PlantsOf($Name)
		{//Find country/state from "Plants of" statement.
		$query = "SELECT * FROM lkupcountry where countryName LIKE '$Name'";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			$Country = $result->fetch_assoc();
			$this->AddToResults('country',$Country['countryName'],-1);
			$this->SeekState($Country['countryId'],-1);
			return true;
			}
		else
			{
			//echo "Here for $Name<br>";
			$match = $this->LabeledRegion('county');
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
						return true;
						}
					}
				}
			//Didn't find a county.  Just look for the state
			$query = "SELECT s.stateName, s.stateId, cr.countryName, cr.countryId FROM lkupstateprovince s inner join lkupcountry cr on cr.countryId = s.countryId WHERE s.stateName LIKE '$Name'";
			//echo "$query<br>";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				{
				while($OneState = $result->fetch_assoc())
					{
					//$this->printr($OneState,"OneState");
					if($this->Results['stateProvince'] == "")
						{
						if($this->CheckCoordinates($OneState['stateName']))
							{
							//$this->printr($OneState,"OneState");
							//if($this->Results['stateProvince'] != "")
								{
								//echo "Checking county<br>";
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
											return true;
											}
										
										}
									}
								}
							}
						}
					if($this->Results['county'] != "")
						return true;
					}
				}
			}
		}
	
	private function CheckCoordinates($State)
		{
		if($this->Results['decimalLatitude'] != "" && $this->Results['decimalLongitude'] != "")
			{
			$Lat = $this->Results['decimalLatitude'];
			$Long = $this->Results['decimalLongitude'];
			$query =  "SELECT decimalLatitude, decimalLongitude, stateProvince, country, county from omoccurrences where stateProvince LIKE '$State' AND decimalLongitude IS NOT NULL LIMIT 5";
			$result = $this->conn->query($query);
			if($result->num_rows == 0)
					return false;
			while($One = $result->fetch_array())
				{
				if(abs($Lat - $One['decimalLatitude']) < 2 && abs($Long - $One['decimalLongitude'] < 2))
					{
					$this->AddToResults('country',$One['country'],-1);
					$this->AddToResults('stateProvince',$One['stateProvince'],-1);
					return true;
					}
					
				}
			}
	
		return false;	
		}
	
	
		
	//*****************************************************************************
	function ScanForState($County)
		{ //Given a county, come up with a list of possible states, then scan for them.
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
		
		
	//*****************************************************************************
	function ScanForCounty($OneState)
		{//Given a state, find a county
		$BadCounties = array("island","park"); //These are much more likely to be false positives.
		$query = "SELECT cy.countyName from lkupcounty cy INNER JOIN lkupstateprovince s on s.stateId=cy.stateId WHERE cy.stateId = ".$OneState['stateId'];
		//echo $query."<br>";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			while($OneCounty = $result->fetch_assoc())
				{
				//echo "Checking {$OneCounty['countyName']}<br>";
				$CountyName = $OneCounty['countyName'];
				if(preg_match("(\b$CountyName\b)",$this->Results['recordedBy']) > 0) 
						continue;  //Rare case of collector name matching a county
				$Preg = "((\b$CountyName\b)(\scounty)?)i";
				if(preg_match($Preg,$this->Label,$match))
					{
					if(array_search(strtolower($match[1]),$BadCounties)!== false && !isset($match[2]))
						continue;
					else
						return $CountyName;
					}
				if(preg_match("(Saint [A-Z][a-z]{2,20})",$CountyName))
					{
					$Preg = str_replace("Saint ","St. ",$Preg);
					if(preg_match($Preg,$this->Label,$match))
						return $CountyName;
					}
				}
		}

	//*****************************************************************************
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
		
	private function GetFromLatLong($Lat,$Long)
		{//Slow with unindexed lat long in the table.  Left as a last resort.
		$Box = .5;
		$query = "SELECT country,stateProvince FROM omoccurrences where decimalLatitude between ".($Lat-$Box)." AND ".($Lat+$Box)." AND decimalLongitude between ".($Long-$Box)." AND ".($Long+$Box)." AND country IS NOT NULL LIMIT 5";
		//echo "$query<br>";
		$result = $this->conn->query($query);
		while($Loc = $result->fetch_assoc())
			{
			$Country = $Loc['country'];
			$State = $Loc['stateProvince'];
			for($L=0;$L < count($this->LabelLines);$L++)
				{
				if(preg_match("(\b($State)\b)i",$this->LabelLines[$L]))
					{
					if($this->Results['country'] == "" || $this->Results['country'] != $Country)
						$this->AddToResults('country',$Country,$L);
					$this->AddToResults('stateProvince',$State,$L);
					$this->LabelLines[$L] = preg_replace("(\b($State)\b)i","",$this->LabelLines[$L]);
					return true;
					}
				else if($this->Results['country'] == "" && preg_match("(\b($Country)\b)i",$this->LabelLines[$L]))
					{
					$this->AddToResults('country',$Country,$L);
					}
				}
			}
		}
		
	//*****************************************************************************
	private function SeekState($CountryId,$m)
		{
		//Given the country (or if none assume USA), scan the whole label for a contained state.
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|vascular|university|specimen|botanical\b))i";
		$query = "SELECT stateName,stateId from lkupstateprovince where countryId like '$CountryId'";
		//echo $query."<br>";
		$StateArray = array();
		$StateResult = $this->conn->query($query);
		$Num = $StateResult->num_rows;
		if($Num == 0)
			return false;
		$RankArray = $this->RankCountryLines();
		foreach ($RankArray as $L=>$Score)
			{
			$StateResult->data_seek(0);
			if(preg_match($BadWords, $this->LabelLines[$L])>0)
				continue;
			while($OneState = $StateResult->fetch_assoc())
				{
				if(preg_match("(\b{$OneState['stateName']}\b)i",$this->LabelLines[$L]) > 0)
					{
					$StateArray[] = $OneState['stateName'];
					$StateId[] = $OneState['stateId'];
					}
				}
			if(count($StateArray) > 0)
				{
				$Lengths = array_map('strlen',$StateArray);
				$MaxLength = max($Lengths);
				$index = array_search($MaxLength,$Lengths);
				//$this->printr($StateId,"StateId");
				$this->AddToResults('stateProvince',$StateArray[$index],$L);
				$this->LabelLines[$L]= str_ireplace($StateArray[$index],"",$this->LabelLines[$L]);
				$County = $this->ScanForCounty(array ('stateId' => $StateId[$index]));
				if($County != "")
					{
					$this->AddToResults('county',$County,-1);
					}
				if($this->Results['country'] == "")
					{
					$query = "SELECT countryName from lkupcountry where countryId like $CountryId LIMIT 1";
					$CountryResult = $this->conn->query($query);
					$Country = $CountryResult->fetch_assoc();
					$this->AddToResults('country',$Country['countryName'],-1);
					}
				return true;
				}
			}
		return false;
		}
	
	//*****************************************************************************
	private function RankCountryLines()
		{
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|vascular|university|specimen|botanical\b))i";
		$BadFields = array("recordedBy","family","identifiedBy","associatedCollectors","sciname","infraspecificEpithet");
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$RankArray[$L] = 0;
			$Score = 0;
			if(preg_match($BadWords,$this->LabelLines[$L]) > 0)
				$Score -= 10;
			foreach($BadFields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					$Score -= 10;
					break;
					}
				if($this->Assigned[$F] == $L)
					{
					$Score -= 10;
					break;
					}
				}
			if(strlen($this->LabelLines[$L]) < 5)
				$Score -= (10 - strlen($this->LabelLines[$L]));

			if($this->LineStart[$L] =='locality')
				$RankArray[$L] += 5;
			if($this->Assigned['locality'] == $L)
				$RankArray[$L] += 5;
			if($this->Assigned['identifiedBy'] == $L)
				$RankArray[$L] -= 5;
			if(count($this->LabelArray[$L])<2)
				$RankArray[$L] -= 5;
			$RankArray[$L] += preg_match_all("(\b[A-Z][a-z]{3,20}\b)",$this->LabelLines[$L]);
			}
		asort($RankArray);
		$RankArray = array_reverse($RankArray, true);
		return $RankArray;
		}
		
		
//*********************************************************************************************************************

//************************************************************************************************************************************
//******************* Word Stat functions ********************************************************************************************
//************************************************************************************************************************************
	

	//******************************************************************
	private function GetWordStatFields()
		{
		$OneLine = array();
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|university|plants of|aceae of|flora de|univ|et al)\b)i";
		$Max = array();
		//$this->printr($this->LineStart,"Line Start");
		//$this->printr($this->LabelLines,"Lines");
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			if($this->SpecialWordStatCases($L))
				continue;
			$Skip=false;
			//If contains start word, then just assign the whole line
			foreach($Fields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					//echo "Starts with $F, {$this->LabelLines[$L]}<br>";
					$this->RemoveStartWords($L,$F);
					$this->AddToResults($F,$this->LabelLines[$L],$L);
					$Skip = true;
					break;
					}
				}
			foreach(array("recordedBy","family","identifiedBy","sciname","infraspecificEpithet") as $F)
				{//Don't bother scoring if this line has start words or has already been assigned.
				if($this->LineStart[$L] ==$F)
					{
					//echo "2 Starts with $F, {$this->LabelLines[$L]}<br>";
					$Skip=true;
					break;
					}
				}
			if($this->Assigned['ignore'] == $L)
				{
				$Skip = true;
				//echo "Skipping for ignore<br>";
				}
			if(preg_match("([a-z])",$this->LabelLines[$L]) === 0)
				{//Usually not all upper case
				//echo "Skipping for upper case<br>";
				$Skip=true;
				}
			if($this->StatScore[$L]['Score'] < 20)
				{
				$Skip = true;  //Already measured this line and it falls short.
				//echo "Skipping {$this->LabelLines[$L]} for score = ".$this->StatScore[$L]['Score']."<br>";
				}
			if(preg_match($BadWords,$this->LabelLines[$L]) > 0)
				$Skip=true;
			//if($this->PlantsOfLine == $L)
			//	$Skip=true;
			if($Skip)
				{
				//echo "Skipping {$this->LabelLines[$L]}<br>";
				continue;
				}
			$this->SplitWordStatLine($L);
			continue;
			$this->ScoreOneLine($L, $Field, $Score);// Field and Score are called by reference.
			//echo "Final Score = $Score<br>";
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

	private function SpecialWordStatCases($L)
		{
		$PregArray[] = array('Preg'=>"(\A[.0-9-]+ m\.?( tall)?[\r\n])",'Field'=>'verbatimAttributes');
		foreach($PregArray as $P)
			{
			if(preg_match($P['Preg'],$this->LabelLines[$L])>0)
				{
				//echo "Found {$P['Field']} in {$this->LabelLines[$L]}<br>";
				$this->AddToResults($P['Field'],$this->LabelLines[$L],$L);
				return true;
				}
			}
		if(($this->Assigned['stateProvince'] == $L) && strlen($this->LabelLines[$L]) < 20)
				{
				//echo "Found {$P['Field']} in {$this->LabelLines[$L]}<br>";
				$this->AddToResults('locality',$this->LabelLines[$L],$L);
				return true;
				}
		
		}
	
	
	
	//************************************************************************************************	
	private function SplitWordStatLine($L)
		{
		//echo "Testing $L: {$this->LabelLines[$L]}<br>";
		
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		//$this->printr($Fields,"Fields");
		$ScoreArray = array();
		$TempString = $this->LabelLines[$L];
		$TempString = str_replace("'","`",$TempString);
		$Found = preg_match_all("(\b[A-Za-z`]{1,20}\b)",$TempString,$WordsArray, PREG_OFFSET_CAPTURE);//Break the string up into words
		//$this->printr($WordsArray,"WA");
		$FieldScoreArray = array();
		$StatSums = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);
		for($w=0;$w<count($WordsArray[0])-1;$w++)
			{
			$Loc = $WordsArray[0][$w][1];
			$Word1 = $WordsArray[0][$w][0];
			$Word2 = $WordsArray[0][$w+1][0];
			$ScoreArray = $this->ScoreTwoWords($Word1,$Word2);//Get word stats score for these two words
			if(max($ScoreArray) == 0)
				{//Could be a locality
				if($w>0 && $this->MaxKey($FieldScoreArray[$w-1]) == 'locality' && preg_match("([A-Z][a-z]{2,20})", $Word1) > 0)
					$ScoreArray['locality'] = 100;
				}
			$FieldScoreArray[$w] = $ScoreArray;
			foreach($Fields as $F)
				{
				$StatSums[$F] += $ScoreArray[$F];
				}
			//$this->printr($StatSums,"StatSums");
			}
		//Get the value for the single last word
		$w = count($WordsArray[0])-1;
		//echo "Final word: {$WordsArray[0][$w][0]}<br>";
		$ScoreArray = $this->ScoreTwoWords($WordsArray[0][$w][0],"");
		//$this->printr($ScoreArray,"SA");
		foreach($Fields as $F)
			{
			$FieldScoreArray[$w][$F] = $ScoreArray[$F];
			$StatSums[$F] += $ScoreArray[$F];
			}
		
		if(count($FieldScoreArray) == 0)
				return;
		//Check for first/last words same field and larger than any other field for the rest of the words.
		$Size = count($WordsArray[0])-2;
		if($Size > 0)
			{
			$Field1 = $this->MaxKey($FieldScoreArray[0]);
			$Field2 = $this->MaxKey($FieldScoreArray[$Size]);
			if(max($FieldScoreArray[0]) != 0 && $Field1 == $Field2)
				{//First and last the same field
				//echo "First and last ($Size) both $Field1<br>";
				$this->AddToResults($Field1,$this->LabelLines[$L],$L);	
				return;
				}
			}
		//$this->printr($FieldScoreArray,"1-FSA");
		
		//Smooth the array, removing isolated high and low points
		foreach($Fields as $F)
			{
			for($w=1;$w < count($FieldScoreArray)-1;$w++)
				{
					//echo "w=$w, F=$F<br>";
				if($FieldScoreArray[$w][$F] < $FieldScoreArray[$w-1][$F] && $FieldScoreArray[$w][$F] < $FieldScoreArray[$w+1][$F])
					$FieldScoreArray[$w][$F] = ($FieldScoreArray[$w][$F]+$FieldScoreArray[$w-1][$F]+$FieldScoreArray[$w+1][$F])/3;
				if($FieldScoreArray[$w][$F] > $FieldScoreArray[$w-1][$F] && $FieldScoreArray[$w][$F] > $FieldScoreArray[$w+1][$F])
					$FieldScoreArray[$w][$F] = ($FieldScoreArray[$w][$F]+$FieldScoreArray[$w-1][$F]+$FieldScoreArray[$w+1][$F])/3;
				}
			}
		//$this->printr($FieldScoreArray,"2-FSA");
		if(count($WordsArray[0]) > 0)
			$Max = max($StatSums)/(count($WordsArray[0]));
		else
			{
			return;
			}
		//echo "$Max, {$this->LabelLines[$L]}<br>";
		if($Max < 20)
			return;
			
		//Now try to split into Fields.
		$ResultFields = array();
		$ChangeFields = array();
		$Prep = array("in","on","of","by","inside","along","ca","de","en");
		//echo "{$this->LabelLines[$L]}<br>";
		//$this->printr($FieldScoreArray,"FSA");
		
		//Find any zero value words and set to the following word...?
		do
			{
			$Flag = false;
			$w=0;
			while($w < count($FieldScoreArray)-1)
				{
				if(max($FieldScoreArray[$w]) == 0)
					if(max($FieldScoreArray[$w+1]) > 0)
						{
						//echo "Setting $w to ".($w+1)."<br>";
						$FieldScoreArray[$w] = $FieldScoreArray[$w+1];
						$Flag = true;
						}
				$w++;
				}
			}while($Flag);
		for($w=0;$w<count($FieldScoreArray);$w++)
			{
			$Word1 = $WordsArray[0][$w][0];
			$Max = max($FieldScoreArray[$w]);
			//if($Max > 0)
				{
				$Field = array_search($Max,$FieldScoreArray[$w]);
				$ResultFields[$w] = $Field;
				//echo "Max $Max in $Field<br>";
				}
			//else
				//echo "Max = 0<br>";
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
		//echo "{$this->LabelLines[$L]}<br>";	
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
							if($FieldScoreArray[$ChangeFields[1]][$ResultFields[1]] > ($FieldScoreArray[$ChangeFields[1]-1][$ResultFields[0]])/2)
								{
								//echo "Adjust 1<br>";
								$Adjust = array('RemovePoint' => 1, 'NewField'=> $ResultFields[1],'FieldStart' =>0,'FieldEnd'=>0,'Flag'=>true);
								}
							}
						}
					else if($ChangeFields[2] - $ChangeFields[1] > 3)
						{
						$Adjust = array('RemovePoint' => 1, 'NewField'=> $ResultFields[1],'FieldStart' =>0,'FieldEnd'=>0,'Flag'=>true);
						//echo "Adjust 2<br>";
						}
					}
				
				if(!$Adjust['Flag'])
					{
					$LastChange = count($ChangeFields)-1;
					$LastWord = count($WordsArray[0])-1;
					if($LastWord - $ChangeFields[$LastChange] < 4 && $FieldScoreArray[$ChangeFields[$LastChange]][$ResultFields[$ChangeFields[$LastChange]]] < 500)
							{
							$Adjust = array('RemovePoint' => $LastChange, 'NewField'=> $ResultFields[$ChangeFields[$LastChange-1]],'FieldStart' =>$ChangeFields[$LastChange],'FieldEnd'=>$LastWord,'Flag'=>true);
							}
					}

				if(!$Adjust['Flag'])
					for($c=0;$c<count($ChangeFields)-1;$c++)
						{
						$w1 = $ChangeFields[$c];
						$w2 = $ChangeFields[$c+1];
						if($w2 - $w1 < 4)
							{
							//echo "Found $w1, $w2 {$WordsArray[0][$w1][0]} in:  {$this->LabelLines[$L]}<br>";
							if($c>0 && $ResultFields[$ChangeFields[$c-1]] == $ResultFields[$w2])
								{//A few on one field in the middle of another field.  Use the surrounding field.
								//echo "Adjust 4<br>";
								$Adjust = array('RemovePoint' => $c, 'RemovePoint2'=>$c+1, 'NewField'=> $ResultFields[$w2],'FieldStart' =>$w1,'FieldEnd'=>$w2,'Flag'=>true);
								}
							else
								{ //Not a simple change/change back.  Find which field it shoud belong too.
								//echo "---{$WordsArray[0][$w1][0]} is {$ResultFields[$w1]}, {$WordsArray[0][$w2][0]} is {$ResultFields[$w2]}<br>";
								if($w2 < count($FieldScoreArray) && $FieldScoreArray[$w2][$ResultFields[$w1]] > 50)
									{//Reasonable score on previous value.  Set back to that
									//echo "Adjust 6<br>";
									if($w2 < count($ResultFields)-1 && $ResultFields[$w2+1] != $ResultFields[$w1])
										{
										$ChangeFields[]= $w2+1;
										asort($ChangeFields);
										}
									//echo "Adjust 5<br>";
										
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
					//echo "{$this->LabelLines[$L]}<br>";
					//$this->printr($ResultFields,"Before");
					for($w=$Adjust['FieldStart'];$w<=$Adjust['FieldEnd'];$w++)
						$ResultFields[$w] = $Adjust['NewField'];
					unset($ChangeFields[$Adjust['RemovePoint']]);
					if(isset($Adjust['RemovePoint2']))
						unset($ChangeFields[$Adjust['RemovePoint2']]);
					$ChangeFields = array_values($ChangeFields);
					//$this->printr($ResultFields,"After");
					}
				}
			}while($Adjust['Flag']);
				
			
		if(count($ChangeFields) == 1)
			{
			//Whole line is a single field
			$Field = $ResultFields[0];
			$this->AddToResults($Field,trim($this->LabelLines[$L],": ,;"),$L);
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
				$TempString = trim(substr($this->LabelLines[$L],$p1,$p2 - $p1),": ,;");
				$this->AddToResults($Field,$TempString,$L);
				}
			if($p2 < strlen($this->LabelLines[$L]))
				{
				$Field = $ResultFields[$w2];
				$TempString = trim(substr($this->LabelLines[$L],$p2),": ,;");
				$this->AddToResults($Field,$TempString,$L);
				}
			
			
			}
		/*
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
		*/
		return;
		}
	
	//************************************************************************************************	
	private function ScoreTwoWords($Word1,$Word2)
		{
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$ScoreArray = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);
		
		if(preg_match("([nsewNSEW] of)",$Word1." ".$Word2) > 0)
			{
			$ScoreArray['locality'] = 200;
			return $ScoreArray;
			}

		
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
					{
					$Factor = ($Values['totalcount'])/10;
					$Factor = ($Factor*$Factor)/100;
					}
				foreach($Fields as $F)
					{
					$OneScore = $Factor * $Values[$F.'Freq'];
					//echo "Adding $OneScore to $F for $Word1<br>";
					if($OneScore < 1)
						$OneScore = 0;
					if($OneScore > $MaxScore1[$F])
						$MaxScore1[$F] = $OneScore;
					}
				}
			}
		if($Word2 != "")
			{//Look for two-word combinations.  Score with more weight than single words -- 5X as I write this comment.
			$query = "SELECT * from salixwordstats where firstword like '$Word1' AND secondword LIKE '$Word2' LIMIT 3";
			//echo "$query<br>";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				{
				while($Values = $result->fetch_assoc())
					{
					$Factor = 1;
					if($Values['totalcount'] < 10) //Reduce impact if only seen few times
						{
						$Factor = ($Values['totalcount']);
						$Factor = ($Factor*$Factor)/100;
						}
					foreach($Fields as $F)
						{
						$OneScore = 10*$Factor * $Values[$F.'Freq'];
						if($OneScore < 1)
							$OneScore = 0;
						//echo "Adding $OneScore to $F for $Word1, $Word2<br>";
						if($OneScore > $MaxScore2[$F])
							$MaxScore2[$F] = $OneScore;
						}
					}
				}
			}
		foreach($Fields as $F)
			$ScoreArray[$F] = $MaxScore2[$F]+$MaxScore1[$F];//+$MaxScore1A[$F];
		//$this->printr($ScoreArray,"Raw $Word1 $Word2");
		//if("$Word1 $Word2" == mb_convert_case("$Word1 $Word2",MB_CASE_TITLE))
			{
			//$ScoreArray['locality'] += 25; //Title case indicates proper noun, probably location name
			}
		//$this->printr($ScoreArray,"ScoreArray");
		return $ScoreArray;
		}
		
		
	//************************************************************************************************	
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
		
	//************************************************************************************************	
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
			//echo "$query<br>";
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
				//echo "$query<br>";
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
		if(preg_match("(\b[nsewNSEW] of [A-Z][a-z]{2,20})",$TempString) > 0)
			$ScoreArray['locality'] += 100;
		//$this->printr($ScoreArray,"ScoreArray");
		asort($ScoreArray);
		end($ScoreArray); //Select the last (highest) element in the scores array
		$Field = key($ScoreArray); //Maximum field
		$Score = floor($ScoreArray[$Field]/count($WordsArray));
		//echo "Score = $Score<br>";
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
		$String = trim($String," :;,");
		if(array_search($Field, array("country","stateProvince","county","minimimumElevationInMeters")) !== false)
			{ //Make sure not to add multiple results to these fields.  Assume the later addition is more likely to be correct, so replace.
			$this->Results[$Field] = $String;
			return;
			}
		if(array_search($Field, array("associatedCollectors","identifiedBy","associatedTaxa")) !== false && $this->Results[$Field] != "")
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
		$this->PregStart['recordedBy'] = "(^(coll(.|ected)? by|collectors|collector|coll|col|leg|by)\b)i";
		$this->PregStart['eventDate'] = "(^(EventDate|Date)\b)i";
		$this->PregStart['recordNumber'] = "(^(number|no)\b)i";
		$this->PregStart['identifiedBy'] = "(^(det(.|:|ermined)? by|determined|det(.)? dupl|det|identified by|identified)\b)i";
		$this->PregStart['associatedCollectors'] = "(^(with|and|&)\b)i";
		$this->PregStart['habitat'] = "(^(habitat|site)\b)i";
		$this->PregStart['locality'] = "(^(locality|location|loc|collected off|collected near)\b)i";
		$this->PregStart['substrate'] = "(^(substrate)\b)i";
		$this->PregStart['country'] = "(^(country)\b)i";
		$this->PregStart['stateProvince'] = "(^(state|province)\b)i";
		$this->PregStart['county'] = "(^(county|parish)\b)i";
		$this->PregStart['minimumElevationInMeters'] = "(^(elevation|elev|altitude|alt)\b)i";
		$this->PregStart['associatedTaxa'] = "(^(growing with|associated taxa|associated with|assoc(.|iated)? plants|assoc(.|iated)? spp|assoc(.|iated)? species|associated|other spp)\b)i";
		$this->PregStart['infraspecificEpithet'] = "(^(ssp|variety|subsp)\b)i";
		$this->PregStart['occurrenceRemarks'] = "(^(notes)\b)i";
		//$this->PregStart['verbatimAttributes'] = "(^(habit)\b)i";
		$this->PregStart['verbatimCoordinates'] = "(^(utm|Latitude|Longitude|Lat|Long)\b)i";
		$this->PregStart['ignore'] = "(^(synonym)\b)i";
		$this->PregStart['substrate'] = "(^(growing on|on)\b)i";
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$this->LineStart[$L] = "";
			foreach($this->PregStart as $Field=>$Val)
				{
				//echo "Checking $Field: {$this->LabelLines[$L]}<br>";
				if($this->CheckStartWords($L,$Field))
					{
					//echo "Assign $Field to {$this->LabelLines[$L]}<br>";
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
		//if(substr_count ($this->LabelLines[$L]," ") < 1)
		//	return false;
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
			$Preg = "(\A{$match[0]}\b)";
			$TempString = preg_replace($Preg," ",$TempString);
			//$TempString = str_ireplace($match[0],"",$TempString);
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
		{//Debug only.  Enhances the print_r routine by adding an optional note before and a newline after.
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

	private function PrintRank($RankArray, $Name = "")
		{//Debug only.  Print RankArray as a table showing line text
		echo "<br>";
		if($Name != "")
			echo "$Name<br>";
		echo "<table border=1><tr><td>Line</td><td>Score</td><td>Text</td></tr>";
		//for($L=0;$L<count($this->LabelLines);$L++)
		foreach($RankArray as $L=>$Value)
			echo "<tr><td>$L:</td><td>{$RankArray[$L]}</td><td>{$this->LabelLines[$L]}</td></tr>";
		echo"</table>";
		}
	
	
	private function MaxKey($A)
		{
		$MaxV = -1000;
		$Field = "";
		foreach($A as $Key=>$Value)
			{
			if($Value > $MaxV)
				{
				$MaxV = $Value;
				$Field = $Key;
				}
			}
		return $Field;
		}
}



?>