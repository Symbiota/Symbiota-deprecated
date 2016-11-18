<?php
require_once __DIR__ . '../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;

$app = new Silex\Application();


$app->GET('/agent', function(Application $app, Request $request) {
            $uuid = $request->get('uuid');    
            
            return new Response('How about implementing agentGet as a GET method ?');
            });


$app->GET('/agents', function(Application $app, Request $request) {
            $familyname = $request->get('familyname');    $firstname = $request->get('firstname');    $middlename = $request->get('middlename');    $namestring = $request->get('namestring');    $yearofbirth = $request->get('yearofbirth');    $yearofdeath = $request->get('yearofdeath');    $variantname = $request->get('variantname');    
            
            return new Response('How about implementing agentsGet as a GET method ?');
            });


$app->run();
