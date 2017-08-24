Copy code below into the site's home page (index.php) or any other page of interest.
Modify variable values to customize slideshow to your preferences.
 
<?php
//---------------------------SLIDESHOW SETTINGS---------------------------------------
//If more than one slideshow will be active, assign unique numerical ids for each slideshow.
//If only one slideshow will be active, leave set to 1. 
$ssId = 1; 

//Enter number of images to be included in slideshow (minimum 5, maximum 10) 
$numSlides = 10;

//Enter width of slideshow window (in pixels, minimum 275, maximum 800)
$width = 300;

//Enter amount of days between image refreshes of images
$dayInterval = 7;

//Enter amount of time (in milliseconds) between rotation of images
$interval = 7000;

//Enter checklist id, if you wish for images to be pulled from a checklist,
//leave as 0 if you do not wish for images to come from a checklist
//if you would like to use more than one checklist, separate their ids with a comma ex. "1,2,3,4"
$clId = "0";

//Enter field, specimen, or both to specify whether to use only field or specimen images, or both
$imageType = "field";

//Enter number of days of most recent images that should be included 
$numDays = 30;

//---------------------------DO NOT CHANGE BELOW HERE-----------------------------

ini_set('max_execution_time', 120);
include_once($serverRoot.'/classes/PluginsManager.php');
$pluginManager = new PluginsManager();
$slideshow = $pluginManager->createSlidewhow($ssId,$numSlides,$width,$numDays,$imageType,$clId,$dayInterval,$interval);
echo $slideshow;
?>