<?php
include_once('../../../config/symbini.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
header("Content-Type: text/html; charset=".$charset);

$statusStr = 0;
$url = 'https://www.museum.tulane.edu/coge/symbiota/';
$fh = fsockopen($url);
//$fh = fopen($url,'r');
$content = trim(fread($fh,8192));
echo $content;
if(substr($content,0,1) == '{') $statusStr = 1;
fclose($fh);

echo $statusStr;
?>