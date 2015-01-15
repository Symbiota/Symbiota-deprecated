Copy code below into the site's home page (index.php) or any other page of interest.
Modify main.css file to customize quick search plugin to your preferences.
 
<?php
include_once($serverRoot.'/classes/PluginsManager.php');
$pluginManager = new PluginsManager();
$quicksearch = $pluginManager->createQuickSearch();
echo $quicksearch;
?>