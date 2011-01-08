<?php

$clid=$_REQUEST['clid'];


	include_once('../../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	
	$con = MySQLiConnectionFactory::getCon("readonly");

	$linkquery="select * from fmchklsttaxalink WHERE CLID = '$clid' ORDER BY RAND() LIMIT 25";

	$linkresult=mysqli_query($con, $linkquery);
	$numlinks = mysqli_num_rows($linkresult);
	$kount = 1;
	
	echo "mainList=[\n";
	while ($linkarray = mysqli_fetch_array($linkresult, MYSQL_ASSOC))
	{
		$tempTID = $linkarray['TID'];
		
		//GET THE PLANT NAME
		$taxaquery="select * from taxa WHERE TID = '$tempTID' LIMIT 0, 1";
		//if ($taxaquery="select * from taxa WHERE TID = '$tempTID' AND CHAR_LENGTH(SciName) < 31 LIMIT 0, 1")
		//{
			$taxaresult=mysqli_query($con, $taxaquery);
			$taxaarray = mysqli_fetch_array($taxaresult, MYSQL_ASSOC);
			
			//GET THE PLANT'S FAMILY
			$familyquery="select * from taxstatus WHERE tid = '$tempTID' LIMIT 0, 1";
			
			$familyresult=mysqli_query($con, $familyquery);
			$familyarray = mysqli_fetch_array($familyresult, MYSQL_ASSOC);
			
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