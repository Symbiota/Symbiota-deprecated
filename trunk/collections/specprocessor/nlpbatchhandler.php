<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlp.php');
//Include additional parsing classes; example below
include_once($serverRoot.'/collections/spcprocessor/MyCustomParser.php');


$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

//Set up log file
$logFH = null;
if(($logFH = fopen($serverRoot.'/temp/logs/batchNlp_'.date('Ymd').'.log', 'a')) === FALSE){
	die('Failed to open log file!');
}
fwrite($logFH,"DateTime: ".date('Y-m-d h:i:s A')."\n\n");

$nlpHandler = new SpecProcNlp();
//If supplied, set collid; If null, OCR blocks will be returned for any collection
if($collId) $nlpHandler->setCollId($collId);
//Get OCR text blocks
$ocrArr = $nlpHandler->getOcrRawArr();

foreach($ocrArr as $occid => $prlArr){
	foreach($prlArr as $prlid => $rawArr){
		$rawStr = $rawArr['rawstr'];
		$source = $rawArr['source'];
		
		//Process string and load results into $dwcArr
		//Exceptions must be caught in try/catch blocks
		$dwcArr = array();
		try{
			/* Enter processing code below comment box...
			 * Output should be an array with Symbiota fild names a keys, for exmaple:
			 * $dwcArr['catalogNumber'] = 'DUKE-L-0123456';
			 * $dwcArr['recordedBy'] = 'T.H. Nash';
			 */

			
			
			

			//Below is an example of how to integrate ones php code, note that special classes need to be included as done above
			$myParse = new MyCustomParser();
			$dwcArr = $wcParse->parseOcr($rawStr,$source);
			
		}
		catch(Exception $e){
			fwrite($logFH,"ERROR: ".$e->getMessage()."\n");
		}
			

		//Add occid and prlid, which will be needed to upload data 
		$dwcArr['occid'] = $occid;
		$dwcArr['prlid'] = $prlid;
		
		//Load results
		//Exceptions must be caught in try/catch blocks
		try{
			$loadResult = $nlpHandler->loadParsedData($dwcArr);
			var_dump($loadResult);
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