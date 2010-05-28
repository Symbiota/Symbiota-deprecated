<?php
/*
 * Created on Jun 11, 2006
 * By E.E. Gilbert
 */

//error_reporting(E_ALL);
header('Content-Type: text/html; charset=ISO-8859-1');
include_once("../util/dbconnection.php");
include_once("../util/symbini.php");

$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
if(!$projValue && isset($defaultProjId)) $projValue = $defaultProjId;
$clType = array_key_exists("cltype",$_REQUEST)?$_REQUEST["cltype"]:""; 

$mapperObj = new ChecklistMapper($projValue, $clType);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php echo $defaultTitle?> - Species Checklists</title>
    <meta name='keywords' content='<?php echo"species distribution,".$mapperObj->getProjName(); ?>' />
    <script src="http://maps.google.com/maps?file=api&v=2&key=<?php echo $googleMapKey; ?>" type="text/javascript"></script>
    <script type="text/javascript">
      //<![CDATA[

        function load() 
        {
            if (GBrowserIsCompatible()) 
            {
                var map = new GMap2(document.getElementById("map"));
                var points = new Array();
                map.addControl(new GLargeMapControl()); // pan, zoom
                map.addControl(new GMapTypeControl()); // map, satellite, hybrid
                map.addControl(new GOverviewMapControl()); // small overview in corner

                map.setCenter(new GLatLng( 41.0, -95.0 ), 3);
                <?php
                    $mapperObj->echoChecklistPoints();
                ?>
                resizeMap(map, points);
            }
        }

        function resizeMap( map, points ) {
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
            for ( var i = 0; i < points.length; i++ ) {
                var p = points[i];
                if ( p.lat() < minLat ) minLat = p.lat();
                if ( p.lat() > maxLat ) maxLat = p.lat();
                if ( p.lng() < minLng ) minLng = p.lng();
                if ( p.lng() > maxLng ) maxLng = p.lng();
              }

            averLat =  (minLat + maxLat) / 2;
            averLng = (minLng + maxLng) / 2;
            panBounds = new GLatLng(averLat,averLng);

              // Find the optimal Width Zoom
              swBounds = new GLatLng(minLat,minLng);
              neBounds = new GLatLng(maxLat,maxLng);
              optimalBounds = new GLatLngBounds(swBounds,neBounds);
            zoomLevel = map.getBoundsZoomLevel(optimalBounds);

              // Reposition
            window.setTimeout(function() {
                map.setCenter(panBounds, zoomLevel);
              }, 500);
                    
        }
      //]]>
    </script>
  </head>
  <body onload="load()" onunload="GUnload()">
    <div id="map" style="width: 800px; height: 600px"></div>
  </body>
</html>

<?php 

class ChecklistMapper{
    
    private $projName;
    private $clType;
    private $pid;
    private $conn; 

    function __construct($projValue,$type) {
    	$this->clType = $type;
        $this->conn = MySQLiConnectionFactory::getCon("readonly");
        $sql = "SELECT p.pid, p.projname FROM fmprojects p ";
        if(is_numeric($projValue)){
            $sql .= " WHERE p.pid = ".$projValue;
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

    public function echoChecklistPoints(){
        $clList = Array();
        $sql = "SELECT c.clid, c.Name, c.LongCentroid, c.LatCentroid ".
            "FROM (fmchecklists c INNER JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid) ". 
            "INNER JOIN fmprojects p ON cpl.pid = p.pid ".
            "WHERE c.LongCentroid IS NOT NULL AND p.pid = ".$this->pid;
        if($this->clType){
        	$sql .= " AND c.type = '".$this->clType."' ";
        }
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $idStr = $row->clid;
            $nameStr = $row->Name;
            echo "var point = new GLatLng(".$row->LatCentroid.", ".$row->LongCentroid.");\n";
              echo "points.push( point );\n";
              echo "var marker$idStr = new GMarker(point);\n";
            echo "GEvent.addListener(marker$idStr, 'dblclick', function() {window.location.href = 'checklist.php?cl=".$idStr."&proj=".$this->getPid()."';});\n";
            echo "GEvent.addListener(marker$idStr, 'click', function() {marker$idStr.openInfoWindowHtml(\"<b>".$nameStr."</b><br>Double Click to open checklist.\");});\n";
              echo "map.addOverlay(marker$idStr);\n";
        }
        $result->close();
    }
}

?>