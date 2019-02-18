<?php
$mimeType = '';
$url = $_REQUEST['url'];
if($url){
	$typeCode = exif_imagetype($url);
	if($typeCode){
		if($typeCode == 1){
			$mimeType = 'image/gif';
		}
		elseif($typeCode == 2){
			$mimeType = 'image/jpeg';
		}
		elseif($typeCode == 3){
			$mimeType = 'image/png';
		}
	}
	else{
		$headers = @get_headers($url,1);
		if(isset($headers["Content-Type"])) $mimeType = $headers["Content-Type"];
	}
}

echo $mimeType;
?>