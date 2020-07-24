<?php
include_once("../../config/symbini.php");

function cleanMSWord($str) {

	$search = [                 // www.fileformat.info/info/unicode/<NUM>/ <NUM> = 2018
							"\xC2\xAB",     // « (U+00AB) in UTF-8
							"\xC2\xBB",     // » (U+00BB) in UTF-8
							"\xE2\x80\x98", // ‘ (U+2018) in UTF-8
							"\xE2\x80\x99", // ’ (U+2019) in UTF-8
							"\xE2\x80\x9A", // ‚ (U+201A) in UTF-8
							"\xE2\x80\x9B", // ‛ (U+201B) in UTF-8
							"\xE2\x80\x9C", // “ (U+201C) in UTF-8
							"\xE2\x80\x9D", // ” (U+201D) in UTF-8
							"\xE2\x80\x9E", // „ (U+201E) in UTF-8
							"\xE2\x80\x9F", // ‟ (U+201F) in UTF-8
							"\xE2\x80\xB9", // ‹ (U+2039) in UTF-8
							"\xE2\x80\xBA", // › (U+203A) in UTF-8
							"\xE2\x80\x93", // – (U+2013) in UTF-8
							"\xE2\x80\x94", // — (U+2014) in UTF-8
							"\xE2\x80\xA6"  // … (U+2026) in UTF-8
	];

	$replacements = [
							"<<", 
							">>",
							"'",
							"'",
							"'",
							"'",
							'"',
							'"',
							'"',
							'"',
							"<",
							">",
							"-",
							"-",
							"..."
	];
	$str = str_replace($search,$replacements,$str);
	$str = preg_replace('/[^\x20-\x7E]*/','', $str);
	return $str;
}


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
				$tmp = array_map('cleanMSWord',$tmp);
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
				$tmp = array_map('cleanMSWord',$tmp);
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



