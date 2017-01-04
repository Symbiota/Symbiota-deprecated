<?php
/*
 * * ****  Accepts  ********************************************
 *
 * POST requests
 *
 * ****  Input Variables  ********************************************
 *
 * un: Username for user.
 * pw: Password for user.
 *
 * * ****  Output  ********************************************
 *
 * JSON array of user and permission variables.
 *
 * uid: User ID for user.
 * firstname: First name of user.
 * lastname: Last name of user.
 * email: Email address of user.
 * token: Token for user to use in future API access to portal.
 * collections: Subarray of collections user has permissions for (only included if permissions exist).
 * checklists: Subarray of checklists user has permissions for (only included if permissions exist).
 * projects: Subarray of projects user has permissions for (only included if permissions exist).
 * portal: Subarray of portal-wide permissions user has (only included if permissions exist).
 *
 * Each collections subarray contains with collection ID as index:
 *  CollectionName: Name of collection.
 *  CollectionCode: Collection code for collection.
 *  InstitutionCode: Institution code for collection.
 * Each checklists subarray contains with checklist ID as index:
 *  ChecklistName: Name of checklist.
 * Each projects subarray contains with project ID as index:
 *  ProjectName: Name of project.
 * Each portal subarray contains permissions.
 *
 */

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');

$un = array_key_exists('un',$_POST)?$_POST['un']:'';
$pw = array_key_exists('pw',$_POST)?$_POST['pw']:'';

$pHandler = new ProfileManager();

$tokenPacket = Array();

if($un && $pw){
    $pHandler->setUserName($un);
    if($pHandler->authenticate($pw)){
        $tokenPacket = $pHandler->generateTokenPacket();
        echo json_encode($tokenPacket);
    }
    else{
        echo 'Incorrect username/password.';
    }
}
?>