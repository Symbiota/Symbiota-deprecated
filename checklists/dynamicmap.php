<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DynamicChecklistManager.php');
include_once($SERVER_ROOT.'/content/lang/checklists/dynamicmap.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset='.$CHARSET);
$tid = array_key_exists('tid',$_REQUEST)?$_REQUEST['tid']:0;
$taxa = array_key_exists('taxa',$_REQUEST)?$_REQUEST['taxa']:'';
$interface = array_key_exists('interface',$_REQUEST)&&$_REQUEST['interface']?htmlspecialchars($_REQUEST['interface']):'checklist';
$dynClManager = new DynamicChecklistManager();
$latCen = 41.0;
$longCen = -95.0;
$coorArr = explode(";",$MAPPING_BOUNDARIES);
if($coorArr && count($coorArr) == 4){
	$latCen = ($coorArr[0] + $coorArr[2])/2;
	$longCen = ($coorArr[1] + $coorArr[3])/2;
}
$coordRange = 50;
if($coorArr && count($coorArr) == 4) $coordRange = ($coorArr[0] - $coorArr[2]);
$zoomInt = 5;
if($coordRange < 20){
	$zoomInt = 7;
}
elseif($coordRange > 35 && $coordRange < 40){
	$zoomInt = 5;
}
elseif($coordRange > 40){
	$zoomInt = 4;
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - Dynamic Checklist Generator</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../css/bootstrap.min.css" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="stylesheet" />

	<!--inicio favicon -->
	<link rel="shortcut icon" href="../images/favicon.png" type="image/x-icon">

	<script src="../js/jquery.js" type="text/javascript"></script>
	<script src="../js/jquery-ui.js" type="text/javascript"></script>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>"></script>

	<script type="text/javascript">
	    var map;
	    var currentMarker;
	  	var zoomLevel = 5;
	  	var submitCoord = false;
        $(document).ready(function() {
        	$( "#taxa" ).autocomplete({
        		source: function( request, response ) {
        			$.getJSON( "rpc/speciessuggest.php", { term: request.term, level: 'high' }, response );
        		},
        		minLength: 2,
        		autoFocus: true,
        		select: function( event, ui ) {
        			if(ui.item){
        				$( "#tid" ).val(ui.item.id);
        			}
				}
        	});

        });
	    function initialize(){
	    	var dmLatLng = new google.maps.LatLng(<?php echo $latCen.",".$longCen; ?>);
	    	var dmOptions = {
				zoom: <?php echo $zoomInt; ?>,
				center: dmLatLng,
				mapTypeId: google.maps.MapTypeId.TERRAIN
			};
	    	map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);

			google.maps.event.addListener(map, 'click', function(event) {
	            mapZoom = map.getZoom();
	            startLocation = event.latLng;
	            setTimeout("placeMarker()", 500);
	        });
	    }

	    function placeMarker() {
			if(currentMarker) currentMarker.setMap();
	        if(mapZoom == map.getZoom()){
	            var marker = new google.maps.Marker({
	                position: startLocation,
	                map: map
	            });
				currentMarker = marker;
		        var latValue = startLocation.lat();
		        var lonValue = startLocation.lng();
		        latValue = latValue.toFixed(5);;
		        lonValue = lonValue.toFixed(5);
				document.getElementById("latbox").value = latValue;
                document.getElementById("lngbox").value = lonValue;
                document.getElementById("latlngspan").innerHTML = latValue + ", " + lonValue;
                document.mapForm.buildchecklistbutton.disabled = false;
                submitCoord = true;
			}
	    }
		function checkForm(){
			if(submitCoord) return true;
			alert("You must first click on map to capture coordinate points");
			return false;
		}
	</script>
</head>
<body style="background-color:#ffffff;" onload="initialize()">
	<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		if(isset($checklists_dynamicmapCrumbs)){
			if($checklists_dynamicmapCrumbs){
				echo "<div class='navpath'>";
				echo "<a href='../index.php'>Home</a> &gt; ";
				echo $checklists_dynamicmapCrumbs;
				echo "<b>Dynamic Map</b>";
				echo "</div>";
			}
		}
		else{
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt;
				<b>Dynamic Map</b>
			</div>
			<?php
		}
		?>
		<div id='innertext'>
			<div>
				<?php echo $LANG['LEGEND'];?>

				<span id="moredetails" style="cursor:pointer;color:blue;font-size:80%;" onclick="this.style.display='none';document.getElementById('moreinfo').style.display='inline';document.getElementById('lessdetails').style.display='inline';">
					<?php echo $LANG['MORE_DETAILS'];?>
				</span>
				<span id="moreinfo" style="display:none;">

					<?php echo $LANG['LEGEND2'];?>

				</span>
				<span id="lessdetails" style="cursor:pointer;color:blue;font-size:80%;display:none;" onclick="this.style.display='none';document.getElementById('moreinfo').style.display='none';document.getElementById('moredetails').style.display='inline';">
					<?php echo $LANG['LESS_DETAILS'];?>
				</span>
			</div>
			<div style="margin-top:5px;">
				<form name="mapForm" action="dynamicchecklist.php" method="post" onsubmit="return checkForm();">
					<div style="float:left;width:500px;">
						<div>
							<input type="submit" name="buildchecklistbutton" value="Construir lista de verificación" disabled />
							<input type="hidden" name="interface" value="<?php echo $interface; ?>" />
							<input type="hidden" id="latbox" name="lat" value="" />
							<input type="hidden" id="lngbox" name="lng" value="" />
						</div>
						<div>
							<b><?php echo $LANG['POINT'];?></b>
							<span id="latlngspan"> &lt; Click on map &gt; </span>
						</div>
					</div>
					<div style="float:left;">
						<div style="margin-right:35px;">
							<b><?php echo $LANG['TAX_FILTER'];?></b> <input id="taxa" name="taxa" type="text" value="<?php echo $taxa; ?>" />
							<input id="tid" name="tid" type="hidden" value="<?php echo $tid; ?>" />
						</div>
						<div>
							<b><?php echo $LANG['RADIUS'];?></b>
							<input name="radius" value="(opcional)" type="text" style="width:140px;" onfocus="this.value = ''" />
							<select name="radiusunits">
								<option value="km">Kilómetros</option>
								<option value="mi">Millas</option>
							</select>
						</div>
					</div>
				</form>
			</div>
			<div id='map_canvas' style='width:100%; height:650px; clear:both;'></div>
		</div>
	<?php
 	include_once($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>
