<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlp.php');
include_once($serverRoot.'/collections/spcprocessor/MyCustomParser.php');

$nlpHandler = new SpecProcNlp();

//Set up log file
$logFH = null;
if(($logFH = fopen($serverRoot.'/temp/logs/batchNlp_'.date('Ymd').'.log', 'a')) === FALSE){
	die('Failed to open log file!');
}
fwrite($logFH,"DateTime: ".date('Y-m-d h:i:s A')."\n\n");


$ocrArr = $nlpHandler->getOcrRawArr();

foreach($ocrArr as $occid => $prlArr){
	foreach($prlArr as $prlid => $rawArr){
		$rawStr = $rawArr['rawstr'];
		$source = $rawArr['source'];
		
		//Process string and load results into $dwcArr
		//Exceptions must be caught in try/catch blocks
		try{
			$dwcArr = array();
		}
		catch(Exception $e){
			fwrite($logFH,"ERROR: ".$e->getMessage()."\n");
		}
			

		/* Below is an example of how to integrate ones php code
		 * Output should be an array with Symbiota fild names a keys, for exmaple:
		 * $dwcArr['catalogNumber'] = 'DUKE-L-0123456';
		 * $dwcArr['recordedBy'] = 'T.H. Nash';
		 */
		try{
			$myParse = new MyCustomParser();
			$dwcArr = $wcParse->parseOcr($rawStr,$source);
		}
		catch(Exception $e){
			fwrite($logFH,"ERROR: ".$e->getMessage()."\n");
		}
		
		
		//Add occid and prlid, which will be needed to upload data 
		$dwcArr['occid'] = $occid;
		$dwcArr['ocprlidcid'] = $prlid;
		
		//Load results
		//Exceptions must be caught in try/catch blocks
		try{
			$nlpHandler->loadParsedData($dwcArr);
		}
		catch(Exception $e){
			fwrite($logFH,"ERROR: ".$e->getMessage()."\n");
		}
	}

}

//Close log file
fwrite($logFH,"Done processing ".date('Y-m-d h:i:s A')."\n\n");
if($logFH) fclose($logFH);

?>