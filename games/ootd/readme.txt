Copy code below into the site's home page (index.php) or any other page of interest.
Modify variable values to target taxa from ta a specific checklist.
The target checklist can be a state or regional list, 
or even a private list that created specifically for feature.
The first time you add block to a page, click on "Click here to test your knowledge" 
link to activate game and establish images for the first time. 

<?php
//Enter checklist id (clid) of the checklist you wish to use 
$ootdGameChecklist = 1; 
//Change to modify title
$ootdGameTitle = "Organism of the Day "; 
//Replace "plant" with the type of organism, eg: plant, animal, insect, fungi, etc.
$ootdGameType = "plant"; 
?>
<div style="float:right;margin-right:10px;width:290px;text-align:center;">
	<div style="font-size:130%;font-weight:bold;">
		<?php echo $ootdGameTitle; ?>
	</div>
	<a href="<?php echo $clientRoot; ?>/games/ootd/index.php?cl=<?php echo $ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
		<img src="<?php echo $clientRoot; ?>/temp/ootd/organism300_1.jpg" style="width:250px;border:0px;" />
	</a><br/>
	<b>What is this <?php echo $ootdGameType; ?>?</b><br/>
	<a href="<?php echo $clientRoot; ?>/games/ootd/index.php?cl=<?php echo $ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">
		Click here to test your knowledge
	</a>
</div>
