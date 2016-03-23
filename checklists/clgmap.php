<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');
header("Content-Type: text/html; charset=".$charset);

$projValue = $_REQUEST['proj'];
if(!$projValue && isset($defaultProjId)) $projValue = $defaultProjId;
$clType = array_key_exists('cltype',$_REQUEST)?$_REQUEST['cltype']:'research';
$target = array_key_exists('target',$_REQUEST)?$_REQUEST['target']:'checklists';

$mapperObj = new ChecklistMapper($projValue);

?>
<html>
	<head>
		<title><?php echo $defaultTitle?> - Species Checklists</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
		<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?sensor=false">
		</script>
		<script type="text/javascript">
		    var map;
		    var points = new Array();
		    var infoWins = new Array();
		  	
		    function initialize(){
		    	var dmLatLng = new google.maps.LatLng(41.0, -95.0);
		    	var dmOptions = {
					zoom: 3,
					center: dmLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				};

				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
                <?php $mapperObj->echoChecklistPoints($clType); ?>
                resizeMap();
	        }

			function resizeMap() {
				var minLng = 180;       //Pixels
				var minLat = 180;
				var maxLng = -180;
				var maxLat = -180;
				var averLat = 0;
				var averLng = 0;
				var panBounds;
	            
				var neBounds;
				var swBounds;
				var optimalBounds;
				var zoomLevel = 3;
	
				// Find the max/min points
				for( var i = 0; i < points.length; i++ ) {
					var p = points[i];
					if ( p.lat() < minLat ) minLat = p.lat();
					if ( p.lat() > maxLat ) maxLat = p.lat();
					if ( p.lng() < minLng ) minLng = p.lng();
					if ( p.lng() > maxLng ) maxLng = p.lng();
				}
				var swLatLng = new google.maps.LatLng(minLat, minLng);
				var neLatLng = new google.maps.LatLng(maxLat, maxLng);
				var llBounds = new google.maps.LatLngBounds(swLatLng, neLatLng);
				map.fitBounds(llBounds);
	    	}

	    	function closeAllInfoWins(){
				for( var w = 0; w < infoWins.length; w++ ) {
					var win = infoWins[w];
					win.close();
				}
	    	}
		</script>
	    <style>
			html, body, #map_canvas {
				width: 100%;
				height: 100%;
				margin: 0;
				padding: 0;
			}
		</style>
	</head>
	<body style="background-color:#ffffff;" onload="initialize()">
    	<div id="map_canvas"></div>
	</body>
</html>

<?php 

class ChecklistMapper{
    
    private $projName;
    private $pid;
    private $conn; 

    function __construct($projValue) {
        $this->conn = MySQLiConnectionFactory::getCon("readonly");
        $sql = "SELECT p.pid, p.projname FROM fmprojects p ";
        if(is_numeric($projValue)){
			$sql .= " WHERE p.pid = ".$this->conn->real_escape_string($projValue);
        }
        else{
            $sql .= " WHERE p.projname = '".$projValue."'";
        }
        $result = $this->conn->query($sql);
        if($row = $result->fetch_object()){
            $this->pid = $row->pid;
            $this->projName = $row->projname;
        }
    }

     public function __destruct(){
         if(!($this->conn === false)) $this->conn->close();
     }

    public function getProjName(){
        return $this->projName;
    }

    public function getPid(){
        return $this->pid;
    }

    public function echoChecklistPoints($type,$target="checklists"){
        if($type == "research"){
        	$this->echoResearchPoints($target);
        }
        elseif($type == "survey"){
        	$this->echoSurveyPoints();
        }
    }

    private function echoResearchPoints($target){
    	$sql = "SELECT c.clid, c.name, c.longcentroid, c.latcentroid ".
            "FROM (fmchecklists c INNER JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid) ". 
            "INNER JOIN fmprojects p ON cpl.pid = p.pid ".
            "WHERE c.access = 'public' AND c.LongCentroid IS NOT NULL AND p.pid = ".$this->conn->real_escape_string($this->pid);
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $idStr = $row->clid;
            $nameStr = $row->name;
            if(strpos($nameStr,'"') !== false) $nameStr = str_replace('"',"'",$nameStr);
			echo "var point".$idStr." = new google.maps.LatLng(".$row->latcentroid.", ".$row->longcentroid.");\n";
			echo "points.push( point".$idStr." );\n";
			echo 'var marker'.$idStr.' = new google.maps.Marker({ position: point'.$idStr.', map: map, title: "'.$nameStr.'" });'."\n";
			//Single click event
			echo 'var infoWin'.$idStr.' = new google.maps.InfoWindow({ content: "<div style=\'width:300px;\'><b>'.$nameStr.'</b><br/>Double Click to open</div>" });'."\n";
			echo "infoWins.push( infoWin".$idStr." );\n";
			echo "google.maps.event.addListener(marker".$idStr.", 'click', function(){ closeAllInfoWins(); infoWin".$idStr.".open(map,marker".$idStr."); });\n";
			//Double click event
			if($target == 'keys'){
				echo "var lStr".$idStr." = '../keys.php?cl=".$idStr."&proj=".$this->getPid()."';\n";
			}
			else{
				echo "var lStr".$idStr." = 'checklist.php?cl=".$idStr."&proj=".$this->getPid()."';\n";
			}
			echo "google.maps.event.addListener(marker".$idStr.", 'dblclick', function(){ closeAllInfoWins(); marker".$idStr.".setAnimation(google.maps.Animation.BOUNCE); window.location.href = lStr".$idStr."; });\n";
        }
        $result->close();
    }

    private function echoSurveyPoints(){
        $sql = "SELECT s.surveyid, s.projectname, s.longcentroid, s.latcentroid ".
            "FROM omsurveys s INNER JOIN omsurveyprojlink spl ON s.surveyid = spl.surveyid ". 
        	"WHERE s.longcentroid IS NOT NULL AND spl.pid = ".$this->conn->real_escape_string($this->pid);
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $idStr = $row->surveyid;
            $nameStr = $row->projectname;
			echo "var point".$idStr." = new google.maps.LatLng(".$row->latcentroid.", ".$row->longcentroid.");\n";
			echo "points.push( point".$idStr." );\n";
			echo "var marker".$idStr." = new google.maps.Marker({ position: point".$idStr.", map: map, title: '".$nameStr."' });\n";
			//Single click event
			echo "var infoWin".$idStr." = new google.maps.InfoWindow({ content: '<div style=\"width:300px;\"><b>".$nameStr."</b><br/>Double Click to open</div>' });\n";
			echo "infoWins.push( infoWin".$idStr." );\n";
			echo "google.maps.event.addListener(marker".$idStr.", 'click', function(){ closeAllInfoWins(); infoWin".$idStr.".open(map,marker".$idStr."); });\n";
			//Double click event
			echo "var lStr".$idStr." = 'survey.php?surveyid=".$idStr."&proj=".$this->getPid()."';\n";
			echo "google.maps.event.addListener(marker".$idStr.", 'dblclick', function(){ closeAllInfoWins(); marker".$idStr.".setAnimation(google.maps.Animation.BOUNCE); window.location.href = lStr".$idStr."; });\n";
		}
		$result->close();
	}
}
?>