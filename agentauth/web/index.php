<?php
require_once __DIR__ . '../vendor/autoload.php';

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/AgentManager.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

$app = new Silex\Application();


$app->GET('/agent', function(Application $app, Request $request) {
    $uuid = $request->get('uuid');

    $agent = null;
    if (strlen($uuid)>0) {
        if (UuidFactory::isValid($uuid)) {
            $agent = new Agent();
            $agent->loadByGUID($uuid);
        }
    }

    $result = array('id'=>$agent->getagentid(), 'type'=>$agent->gettype(), 'prefix'=>$agent->getprefix(),
        'firstname'=>$agent->getfirstname(), 'middlename'=>$agent->getmiddlename(),
        'familyname'=>$agent->getfamilyname(), 'namestring'=>$agent->getnamestring(),
        'yearofbirth'=>$agent->getyearofbirth(), 'yearofdeath'=>$agent->getyearofdeath());

    return new Response(json_encode($result));
});


$app->GET('/agents', function(Application $app, Request $request) {
    $variantname = $request->get('variantname');

    $am = new AgentManager();

    $result = null;
    if (!empty($variantname)) {
        $result = $am->agentNameSearch($variantname);
    } else {
        $query = (!empty($familyname) ? "familyname=" . $request->get('familyname') . " " : "") .
            (!empty($firstname) ? "firstname=" . $request->get('firstname') . " " : "") .
            (!empty($middlename) ? "middlename=" . $request->get('middlename') . " " : "") .
            (!empty($namestring) ? "namestring=" . $request->get('namestring') . " " : "") .
            (!empty($yearofbirth) ? "yearofbirth=" . $request->get('yearofbirth') . " " : "") .
            (!empty($yearofdeath) ? "yearofdeath=" . $request->get('yearofdeath') . " " : "");

        $result = $am->agentNameSearch($query);
    }

    return new Response(json_encode($result));
});


$app->run();
