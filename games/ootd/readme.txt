Copy code below into the site's home page (index.php) or any other page of interest.
Modify variable values to target taxa from ta a specific checklist.
The target checklist can be a state or regional list, 
or even a private list that created specifically for feature.


<?php
//Enter checklist id (clid) of the checklist you wish to use 
$ootdGameChecklist = 1; 
//Change to modify title
$ootdGameTitle = "Organism of the Day "; 
//Replace "plant" with the type of organism, eg: plant, animal, insect, fungi, etc.
$ootdGameType = "plant"; 

<div style="float:right;margin-right:10px;width:290px;text-align:center;">
	<div style="font-size:130%;font-weight:bold;">
		<?php echo $ootdGameTitle; ?>
	</div>
	<a href="<?php echo $serverRoot; ?>/games/ootd/index.php">
		<img src="<?php echo $serverRoot; ?>/temp/ootd/plant300_1.jpg" style="width:250px;border:0px;" />
	</a><br/>
	<b>What is this <?php echo $ootdGameType; ?>?</b><br/>
	<a href="<?php echo $serverRoot; ?>/games/ootd/index.php">
		Click here to test your knowledge
	</a>
</div>
?>