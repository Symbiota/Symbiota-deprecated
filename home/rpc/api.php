<?php
include_once("../../config/symbini.php");


function getNews() {
	global $SERVER_ROOT;
	$return = array();
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);
	$dir = $SERVER_ROOT . '/home/news/';
	if ($handle = opendir($dir)) {
		
    while (false !== ($entry = readdir($handle))) {
			if (stripos($entry,".") !== 0 ) {
				$tmp = array();
    		$html = file_get_contents($dir . $entry);  	
				$dom->loadHTML($html);
				$xpath = new DOMXPath($dom);	
    		
				foreach ($xpath->query ("//div[@class='news-item']") as $news) {
					// search for sub nodes inside each element
					foreach ($xpath->query (".//div[@class='title']", $news) as $h2) {
						$tmp['title'] = $h2->nodeValue;
					}
					foreach ($xpath->query ("//div[@class='byline']", $news) as $byline) {
						$tmp['byline'] = $byline->nodeValue;
					}
					foreach ($xpath->query ("//div[@class='content']", $news) as $content) {
						$tmp['content'] = $content->nodeValue;
					}

					foreach ($xpath->query ("//img", $news) as $image) {
						$tmp['image_src'] = $image->getAttribute( 'src' );
						$tmp['image_alt'] = $image->getAttribute( 'alt' );
					}
					foreach ($xpath->query ("//div[@class='caption']", $news) as $caption) {
						$tmp['caption'] = $caption->nodeValue;
					}
				}
				#$tmp['content'] = mb_convert_encoding($tmp['content'], "UTF-8", array("Windows-1252"));
				$tmp['excerpt'] = substr($tmp['content'], 0, strrpos(substr($tmp['content'], 0, 200), ' '));
				$return[] = $tmp;  		
    	}
    }
    closedir($handle);
	}
	libxml_clear_errors();
	return $return;
}

function getEvents() {
	global $SERVER_ROOT;
	$return = array();
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);
	$dir = $SERVER_ROOT . '/home/events/';
	if ($handle = opendir($dir)) {
		
    while (false !== ($entry = readdir($handle))) {
			if (stripos($entry,".") !== 0 ) {
				$tmp = array();
    		$html = file_get_contents($dir . $entry);  	
				$dom->loadHTML($html);
				$xpath = new DOMXPath($dom);	
    		
				foreach ($xpath->query ("//div[@class='event-item']") as $event) {
					// search for sub nodes inside each element
					foreach ($xpath->query (".//div[@class='title']", $event) as $h2) {
						$tmp['title'] = $h2->nodeValue;
					}
					foreach ($xpath->query ("//div[@class='date']", $event) as $date) {
						$tmp['date'] = $date->nodeValue;
					}
					foreach ($xpath->query ("//div[@class='time']", $event) as $time) {
						$tmp['time'] = $time->nodeValue;
					}
					foreach ($xpath->query ("//div[@class='content']", $event) as $content) {
						$tmp['content'] = $content->nodeValue;
					}
					foreach ($xpath->query ("//div[@class='location']", $event) as $location) {
						$tmp['location'] = $location->nodeValue;
					}
				
				}
				$return[] = $tmp;  		
    	}
    }
    closedir($handle);
	}
	libxml_clear_errors();
	$return = array_reverse($return);#soonest first?
	return $return;
}


$result = [];
$result['news'] = getNews();
$result['events'] = getEvents();



// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>



