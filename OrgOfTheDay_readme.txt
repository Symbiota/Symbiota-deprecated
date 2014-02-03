To add a window link to the Organism of the Day game showing the first picture, add the code below to the desired page:

<div style="float:right;margin-right:10px;width:290px;text-align:center;">
	<div style="font-size:130%;font-weight:bold;">
		<?php echo (isset($ootdGameTitle)?$ootdGameTitle:'Organism of the Day'); ?>
	</div>
	<a href="<?php echo $serverRoot; ?>/games/ootd/index.php">
		<img src="<?php echo $serverRoot; ?>/temp/ootd/plant300_1.jpg" style="width:250px;border:0px;" />
	</a><br/>
	<b>What is this <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?>?</b><br/>
	<a href="<?php echo $serverRoot; ?>/games/ootd/index.php">
		Click here to test your knowledge
	</a>
</div>

To set the research checklist from which the organism of the day will select taxa, the title which will display over the game, 
and what type of organism the game is covering entering the following lines of code to your symbini.php file:

$ootdGameChecklist = 2; //Replace 2 with the checklist id you would like to use.
$ootdGameTitle = "Organism of the Day Title"; //Replace "Organism of the Day Title" with the title you would like to use.
$ootdGameType = "plant"; //Replace "plant" with the type of organism, eg: plant, animal, insect, fungi, etc.