Copy code below into the site's home page (index.php) or any other page of interest.
Modify variable values to target taxa from ta a specific checklist.
The target checklist can be a state or regional list, 
or even a private list that created specifically for feature.

<?php
//---------------------------GAME SETTINGS---------------------------------------
//If more than one game will be active, assign unique numerical ids for each game.
//If only one game will be active, leave set to 1. 
$oodID = 1; 

//Enter checklist id (clid) of the checklist you wish to use 
$ootdGameChecklist = 1;

//Change to modify title
$ootdGameTitle = "Organism of the Day "; 

//Replace "plant" with the type of organism, eg: plant, animal, insect, fungi, etc.
//This setting will appear in "Name that ______"
$ootdGameType = "plant"; 
//---------------------------DO NOT CHANGE BELOW HERE-----------------------------

include_once($serverRoot.'/classes/GamesManager.php');
$gameManager = new GamesManager();
$gameInfo = $gameManager->setOOTD($oodID,$ootdGameChecklist);
?>
<div style="float:right;margin-right:10px;width:290px;text-align:center;">
	<div style="font-size:130%;font-weight:bold;">
		<?php echo $ootdGameTitle; ?>
	</div>
	<a href="<?php echo $clientRoot; ?>/games/ootd/index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
		<img src="<?php echo $clientRoot; ?>/temp/ootd/<?php echo $oodID; ?>_organism300_1.jpg" style="width:250px;border:0px;" />
	</a><br/>
	<b>What is this <?php echo $ootdGameType; ?>?</b><br/>
	<a href="<?php echo $clientRoot; ?>/games/ootd/index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
		Click here to test your knowledge
	</a>
</div>
