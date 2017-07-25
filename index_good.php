<?php
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
<head>
	<meta http-equiv="X-Frame-Options" content="deny">
	<title><?php echo $DEFAULT_TITLE; ?> Home</title>
	<link href="css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/header.php');
	?> 
	<!-- This is inner text! -->
	<div  id="innertext">
		<h1></h1>

		<div id="quicksearchdiv">
			<div style="float:left;">
				<?php
				//---------------------------QUICK SEARCH SETTINGS---------------------------------------
				//Title text that will appear. 
				$searchText = 'Search Taxon'; 
		
				//Text that will appear on search button. 
				$buttonText = 'Search';
		
				//---------------------------DO NOT CHANGE BELOW HERE-----------------------------
				include_once($SERVER_ROOT.'/classes/PluginsManager.php');
				$pluginManager = new PluginsManager();
				$quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText);
				echo $quicksearch;
				?>
			</div>
		</div>
		<div style="padding: 0px 10px;">
                        <div style="float:right">
                                <?php
                                //---------------------------GAME SETTINGS---------------------------------------
                                //If more than one game will be active, assign unique numerical ids for each game.
                                //If only one game will be active, leave set to 1.
                                $oodID = 1;
                                //Enter checklist id (clid) of the checklist you wish to use, if you would like to use more than one checklist,
                                //separate their ids with a comma ex. "1,2,3,4"
                                $ootdGameChecklist = "2";

                                //Change to modify title
                                $ootdGameTitle = "Reptile of the Day ";

                                //Replace "plant" with the type of organism, eg: plant, animal, insect, fungi, etc.
                                //This setting will appear in "Name that ______"
                                $ootdGameType = "reptile";
                                //---------------------------DO NOT CHANGE BELOW HERE-----------------------------

                                include_once($serverRoot.'/classes/GamesManager.php');
                                $gameManager = new GamesManager();
                                $gameInfo = $gameManager->setOOTD($oodID,$ootdGameChecklist);
                                ?>
                                <div style="float:right;margin-right:10px;width:350px;text-align:center;">
                                        <div style="font-size:130%;font-weight:bold;">
                                                <?php echo $ootdGameTitle; ?>
                                        </div>
                                        <a href="<?php echo $clientRoot; ?>/games/ootd/index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
                                        <img src="<?php echo $clientRoot; ?>/temp/ootd/<?php echo $oodID; ?>_organism300_1.jpg" style="width:340px;border:0px;" />
                                        </a><br/>
                                        <b>What is this <?php echo $ootdGameType; ?>?</b><br/>
                                        <a href="<?php echo $clientRoot; ?>/games/ootd/index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
                                                Click here to test your knowledge
                                        </a>

                                </div>
                        </div>
                        <div style="float:right">
                                <?php
                                $oodID = 2;
                                //Enter checklist id (clid) of the checklist you wish to use, if you would like to use more than one checklist,
                                //separate their ids with a comma ex. "1,2,3,4"
                                $ootdGameChecklist = "3";
                                
                                //Change to modify title
                                $ootdGameTitle = "Mammal of the Day ";
                                
                                //Replace "plant" with the type of organism, eg: plant, animal, insect, fungi, etc.
                                //This setting will appear in "Name that ______"
                                $ootdGameType = "mammal";
                                //---------------------------DO NOT CHANGE BELOW HERE-----------------------------
                                
                                include_once($serverRoot.'/classes/GamesManager.php');
                                $gameManager = new GamesManager();
                                //                                $gameInfo = $gameManager->setOOTD($oodID,$ootdGameChecklist);
                                ?>
                                                                <div style="float:right;margin-right:10px;width:350px;text-align:center;">
                                                                        <div style="font-size:130%;font-weight:bold;">
                                                                                <?php echo $ootdGameTitle; ?>
                                                                        </div>
                                                                        <a href="<?php echo $clientRoot; ?>/games/ootd/index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
                                                                        <img src="<?php echo $clientRoot; ?>/temp/ootd/<?php echo $oodID; ?>_organism300_1.jpg" style="width:340px;border:0px;" />
                                                                        </a><br/>
                                                                        <b>What is this <?php echo $ootdGameType; ?>?</b><br/>
                                                                        <a href="<?php echo $clientRoot; ?>/games/ootd/index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
                                                                                Click here to test your knowledge
                                                                        </a>
                                
                                                                </div>
                                                        </div>
                                
                                                                
 			Description and introduction of project
		</div>
	</div>

	<?php
	include($SERVER_ROOT.'/footer.php');
	?> 
</body>
</html>