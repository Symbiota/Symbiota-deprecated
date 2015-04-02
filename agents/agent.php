<?php

include_once('../config/symbini.php');
include_once($serverRoot.'/classes/AgentManager.php');
include_once($serverRoot.'/classes/RdfUtility.php');
include_once($serverRoot.'/classes/UuidFactory.php');

// Find out what media types the client would like, in order.
$accept = RdfUtility::parseHTTPAcceptHeader($_SERVER['HTTP_ACCEPT']);
$force = array_key_exists("force",$_REQUEST)?$_REQUEST["force"]:"";
$agentid = preg_replace('[^0-9]','',array_key_exists("agentid",$_REQUEST)?$_REQUEST["agentid"]:"");
$uuid = array_key_exists("uuid",$_REQUEST)?$_REQUEST["uuid"]:"";
$findobjects = preg_replace('[^0-9]','',array_key_exists("findobjects",$_REQUEST)?$_REQUEST["findobjects"]:"");

$agent = new Agent();
$agentview = new AgentView();
if (strlen($agentid) > 0 ) { 
  $agent->load($agentid);
  $agentview->setModel($agent);
} elseif (strlen($uuid)>0) { 
  if (UuidFactory::is_valid($uuid)) { 
     $agent->loadByGUID($uuid);
     $agentview->setModel($agent);
  } 
}

$done = FALSE;
if ($force=='turtle') { 
       deliverTurtle(); 
       $done = TRUE;
}
if ($force=='rdfxml') { 
       deliverRdfXml(); 
       $done = TRUE;
}
reset($accept);
while (!$done && list($key, $mediarange) = each($accept)) {
    if ($mediarange=='text/turtle') {
       deliverTurtle(); 
       $done = TRUE;
    } 
    if ($mediarange=='application/rdf+xml') {
       deliverRdfXml(); 
       $done = TRUE;
    } 
}
if (!$done) { 
  Header("Content-Type: text/html; charset=".$charset);
  $spDisplay = " Agent: ". $agent->getMinimalName();
  pageheader($agent);
  $am = new AgentManager();
  if ($am->isAgentEditor()) { 
     echo "<div id='commandDiv'><span class='link' id='editLink'>Edit</span>&nbsp;<span class='link' id='viewLink'>View</span>&nbsp;<span class='link' id='createLink'>New</span></div>";
     echo "
     <script type='text/javascript'>
        $('#editLink').click(function () {
            $.ajax({
               type: 'GET',
               url: '$clientRoot/agents/rpc/handler.php',
               data: 'mode=edit&table=Agent&agentid=".$agent->getagentid()."',
               dataType : 'html',
               success: function(data){
                  $('#agentDetailDiv".$agent->getagentid()."').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false; 
        });
        $('#viewLink').click(function () {
            $.ajax({
               type: 'GET',
               url: '$clientRoot/agents/rpc/handler.php',
               data: 'mode=show&table=Agent&agentid=".$agent->getagentid()."',
               dataType : 'html',
               success: function(data){
                  $('#agentDetailDiv".$agent->getagentid()."').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false; 
        });
        $('#createLink').click(function () {
            $.ajax({
               type: 'GET',
               url: '$clientRoot/agents/rpc/handler.php',
               data: 'mode=create&table=Agent',
               dataType : 'html',
               success: function(data){
                  $('#agentDetailDiv".$agent->getagentid()."').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false; 
        });
     </script>
     ";
  } 
  echo "<div id='agentDetailDiv".$agent->getagentid()."'>";
  echo $agentview->getDetailsView();
  echo "</div>";
  if ($findobjects==1) { 
    echo $am->getPrettyListOfCollectionObjectsForCollector($agent->getagentid());
  }
  footer();
}

/**
 * Return the requested agent as RDF in a turtle serialization.
 */
function deliverTurtle() {
   global $agent, $agentview, $charset;
   Header("Content-Type: text/turtle; charset=".$charset);
   echo $agentview->getAsTurtle();
}

function deliverRdfXml() { 
   global $agent, $agentview, $charset;
   Header("Content-Type: application/rdf+xml; charset=".$charset);
   echo $agentview->getAsRdfXml();
}

function pageheader($agent) { 
   global $serverRoot, $defaultTitle, $spDisplay, $clientRoot, $agent_indexCrumbs, $charset;
echo '<!DOCTYPE HTML>
<html>
<head>
	<title>'.$defaultTitle.' - '.$spDisplay. '</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<meta name="keywords" content='. $spDisplay .' />
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui_accordian.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">';
    // include_once($serverRoot.'/config/googleanalytics.php'); 
echo '</script>
	<script type="text/javascript">
		var currentLevel = ' . ($descrDisplayLevel?$descrDisplayLevel:"1"). ';
		var levelArr = new Array('. ($descr?"'".implode("','",array_keys($descr))."'":"") . ');
	</script>
</head>
<body>';
   $displayLeftMenu = FALSE;
   include($serverRoot.'/header.php');
   if(!isset($agent_indexCrumbs)){
      $agent_indexCrumbs = array();
      array_push($agent_indexCrumbs,"<a href='$clientRoot/index.php'>Home</a>");
      array_push($agent_indexCrumbs,"<a href='$clientRoot/agents/index.php'>Agents</a>");
   }
   if (isset($agent)) { 
      $name = $agent->getMinimalName();
      $queryname = $agent->getMinimalName(false);
   } 
   if (strlen($name)>0) {
      array_push($agent_indexCrumbs,"<a href='$clientRoot/agents/index.php?name=$queryname'>Search</a>");
      array_push($agent_indexCrumbs,$name);
   } 
   echo "<div class='navpath'>";
   $last = array_pop($agent_indexCrumbs);
   echo implode($agent_indexCrumbs, " &gt;&gt;");
   echo " &gt;&gt;<strong>$last</strong>";
   array_push($agent_indexCrumbs,$last);
   echo "</div>";

}
  


function footer() { 
   global $serverRoot,$clientRoot;
  include($serverRoot.'/footer.php');
  echo "</body>\n</html>";
}
?>