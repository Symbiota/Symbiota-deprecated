<?
$clid=$_REQUEST['clid'];

	$dbhost = 'DBHOST';
	$dbuser = 'DBUSERNAME';
	$dbpass = 'DBPASS';
	$dbname = 'symbiotaseinet';
	$linked = mysql_connect($dbhost, $dbuser, $dbpass) or die ("Error connecting to database:".mysql_error());
	mysql_select_db($dbname) or die("Database $dbhost could not be accessed.<BR>Please advise the system administrators.");
	
	$countquery = "SELECT COUNT(*) FROM fmchklsttaxalink where CLID = '$clid'";
	$countresult = mysql_query($countquery) or die("Query failed : " . mysql_error());
	$countnumresults = mysql_result($countresult, 0, 0);
	
	if ($countnumresults < 25)
		$linkquery="select * from fmchklsttaxalink WHERE CLID = '$clid' ORDER BY TID";
	else
		$linkquery="select * from fmchklsttaxalink WHERE CLID = '$clid' ORDER BY RAND() LIMIT 25";

	$linkresult=mysql_query($linkquery);
	$numlinks = mysql_numrows($linkresult);
	$kount = 1;
	
	echo "mainList=[\n";
	while ($linkarray = mysql_fetch_array($linkresult, MYSQL_ASSOC))
	{
		$tempTID = $linkarray['TID'];
		
		//GET THE PLANT NAME
		$taxaquery="select * from taxa WHERE TID = '$tempTID' LIMIT 0, 1";
		//if ($taxaquery="select * from taxa WHERE TID = '$tempTID' AND CHAR_LENGTH(SciName) < 31 LIMIT 0, 1")
		//{
			$taxaresult=mysql_query($taxaquery);
			$taxaarray = mysql_fetch_array($taxaresult, MYSQL_ASSOC);
			
			//GET THE PLANT'S FAMILY
			$familyquery="select * from taxstatus WHERE tid = '$tempTID' LIMIT 0, 1";
			
			$familyresult=mysql_query($familyquery);
			$familyarray = mysql_fetch_array($familyresult, MYSQL_ASSOC);
			
			if ($kount < $numlinks)
			{
				$kount++;
				echo "[\"".$taxaarray['SciName']."\",\"".$familyarray['family']."\"],\n";
			}
			else 
				echo "[\"".$taxaarray['SciName']."\",\"".$familyarray['family']."\"]\n";
		//}
	}
	echo "]";

?>