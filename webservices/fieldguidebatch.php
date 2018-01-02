<?php
/*
 * * ****  Accepts  ********************************************
 *
 * POST requests
 *
 * ****  Input Variables  ********************************************
 *
 * job_id: ID for batch processing job.
 * url: URL of results.
 *
 * * ****  Output  ********************************************
 *
 * ERROR or SUCCESS messages.
 *
 */

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAPIManager.php');

$jobId = array_key_exists('job_id',$_POST)?$_POST['job_id']:'';
$resultUrl = array_key_exists('url',$_POST)?$_POST['url']:'';

$rHandler = new OccurrenceAPIManager();
if($jobId && $resultUrl){
    if($rHandler->validateFGResults($jobId)){
        $rHandler->processFGResults($jobId,$resultUrl);
    }
}
?>