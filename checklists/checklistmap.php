<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$clid = $_REQUEST['clid'];
$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0;
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:"";

$clManager = new ChecklistManager();
$clManager->setClid($clid);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);

$coordArr = $clManager->getVoucherCoordinates(0);
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> - Checklist Coordinate Map</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	<script type="text/javascript">
		var map;
		var puWin;

		function initialize(){
			var dmOptions = {
				zoom: 3,
				center: new google.maps.LatLng(41,-95),
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				scaleControl: true
			};

			var llBounds = new google.maps.LatLngBounds();
			<?php
			if($coordArr){
				?>
				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
				var vIcon = new google.maps.MarkerImage("../images/google/smpin_red.png");
				var pIcon = new google.maps.MarkerImage("../images/google/smpin_blue.png");
				<?php
				$mCnt = 0;
				foreach($coordArr as $tid => $cArr){
					foreach($cArr as $pArr){
						?>
						var pt = new google.maps.LatLng(<?php echo $pArr['ll']; ?>);
						llBounds.extend(pt);
						<?php
						if(array_key_exists('occid',$pArr)){
							?>
							var m<?php echo $mCnt; ?> = new google.maps.Marker({position: pt, map:map, title:"<?php echo $pArr['notes']; ?>", icon:vIcon});
							google.maps.event.addListener(m<?php echo $mCnt; ?>, "click", function(){ openIndPU(<?php echo $pArr['occid']; ?>); });
							<?php
						}
						else{
							?>
							var m<?php echo $mCnt; ?> = new google.maps.Marker({position: pt, map:map, title:"<?php echo $pArr['sciname']; ?>", icon:pIcon});
							<?php
						}
						$mCnt++;
					}
				}
			}
			//Check for and add checklist polygon
			$clMeta = $clManager->getClMetaData();
			if($clMeta['footprintwkt']){
				?>
				var polyPointArr = [];
				<?php
				$footPrintWkt = $clMeta['footprintwkt'];
				if(substr($footPrintWkt, 0, 7) == 'POLYGON'){
					$footPrintWkt = substr($footPrintWkt, 10, -2);
					$pointArr = explode(',', $footPrintWkt);
					foreach($pointArr as $pointStr){
						$llArr = explode(' ', trim($pointStr));
						if($llArr[0] > 90 || $llArr[0] < -90) break;
						?>
						var polyPt = new google.maps.LatLng(<?php echo $llArr[0].','.$llArr[1]; ?>);
						polyPointArr.push(polyPt);
						llBounds.extend(polyPt);
						<?php
					}
					?>
					var footPoly = new google.maps.Polygon({
						paths: polyPointArr,
						strokeWeight: 2,
						fillOpacity: 0.4,
						map: map
					});
					<?php
				}
			}
			?>
			map.fitBounds(llBounds);
			map.panToBounds(llBounds);
		}

		function openIndPU(occId){
			if(puWin != null) puWin.close();
			var puWin = window.open('../collections/individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=0,resizable=1,width=900,height=600,left=20,top=20');
			if(puWin.opener == null) puWin.opener = self;
			setTimeout(function () { puWin.focus(); }, 0.5);
			return false;
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
<body style="background-color:#ffffff;" onload="initialize();">
<?php
	if(!$coordArr){
		?>
		<div style='font-size:120%;font-weight:bold;'>
			Your query apparently does not contain any records with coordinates that can be mapped.
		</div>
		<div style="margin:15px;">
			It may be that the vouchers have rare/threatened status that require the locality coordinates be hidden.
		</div>
		<?php
	}
	?>
	<div id='map_canvas'></div>
</body>
</html>
