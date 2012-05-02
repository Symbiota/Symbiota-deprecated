<?php 
include_once('../../config/symbini.php');

$locality = $_REQUEST['locality'];
$country = array_key_exists('country',$_REQUEST)?$_REQUEST['country']:'';
$state = array_key_exists('state',$_REQUEST)?$_REQUEST['state']:'';
$county = array_key_exists('county',$_REQUEST)?$_REQUEST['county']:'';

if(!$country || !$state || !$county){
	$locArr = explode(";",$locality);
	$locality = array_pop($locArr);
	if(!$country && $locArr) $country = array_shift($locArr);
	if(!$state && $locArr) $state = array_shift($locArr);
	if(!$county && $locArr) $county = array_shift($locArr);
}
if(preg_match('/\d{1,2}[NS]{1}T\s\d{1,2}[EW]{1}R\s\d{1,2}S/',$locality)){
	$locality = preg_replace('/(\d{1,2}[NS]{1})T\s(\d{1,2}[EW]{1})R\s(\d{1,2})S/', 'T$1 R$2 Sec$3', $locality);
}
elseif(preg_match('/R\d{1,2}[EW]{1}\sS\d{1,2}/i',$locality)){
	$locality = preg_replace('/\sS(\d{1,2})/', ' Sec$1', $locality);
}

$urlVariables = 'country='.$country.'&state='.$state.'&county='.$county.'&locality='.$locality;

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>GEOLocate Tool</title>
	<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
	<style>
		iframe {
			width: 1020px;
			height:720px;
			margin: 0px;
			border: 1px solid #000;
		}
	</style>
	<script type="text/javascript">
	    function transferCoord(evt) {
	        if(evt.origin !== "http://www.museum.tulane.edu") {
				alert("iframe url does not have permision to interact with me");
	        }
	        else {
	            var breakdown = evt.data.split("|");
                if(breakdown.length == 4){
                    if(breakdown[0] == ""){
                    	alert("There are no data points to tranfer");
                    }
                    else{
	                	opener.geoLocateUpdateCoord(breakdown[0],breakdown[1],breakdown[2]);
	                    self.close();
                    }
                }
	        }
	    }
	    if(window.addEventListener) {
	        // For standards-compliant web browsers
	        window.addEventListener("message", transferCoord, false);
	    }
	    else {
	        window.attachEvent("onmessage", transferCoord);
	    }
	</script>
</head>

<body>
	<div id="container">
		<div >
			<!--<iframe id="da-iframe" src="http://www.museum.tulane.edu/nelson/gravier2.html"></iframe>-->
			<iframe id="Iframe1" src="http://www.museum.tulane.edu/geolocate/web/webgeoreflight.aspx?v=1&georef=run&tab=locality&<?php echo $urlVariables; ?>"></iframe>
		</div>
	</div>
</body>
</html>
