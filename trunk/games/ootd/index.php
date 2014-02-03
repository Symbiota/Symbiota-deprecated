<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/GamesManager.php');
header("Content-Type: text/html; charset=".$charset);

$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:0;
$submitAction = array_key_exists("submitaction",$_POST)?$_POST["submitaction"]:'';

$gameManager = new GamesManager();
$clArr = $gameManager->getChecklistArr($pid);

$gameInfo = $gameManager->setOOTD($ootdGameChecklist);
$imageArr = $gameInfo['images'];

if($submitAction){
	$scinameAnswerArr = explode(' ',trim($_POST['sciname_answer']));
	$genusAnswer = strtolower($scinameAnswerArr[0]);
}

 ?>

<html>
<head>
	<title><?php echo (isset($ootdGameTitle)?$ootdGameTitle:'Organism of the Day'); ?></title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../../js/symb/games.ootd.js"></script>
	
	<script type="text/javascript">
		function toggleById(target){
			var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else {
				obj.style.display="none";
			}
		}

		//<!-------------cycles the images-->
		var ImgNum = 0;
		var ImgLength = NewImg.length - 1;
		var delay = 3000; // Time delay between Slides in milliseconds 
		var lock = false;
		var run;

		var ImgNum = 0;
			
		function chgImg(direction){
			var NewImg = <?php echo json_encode($imageArr); ?>;
			var ImgLength = NewImg.length - 1;
			if (document.images) {
				ImgNum = ImgNum + direction;
				if (ImgNum > ImgLength) { ImgNum = 0; }
				if (ImgNum < 0) { ImgNum = ImgLength; }
				document.getElementById('slideshow').src = NewImg[ImgNum];
			}
		}
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($indexMenu)?$indexMenu:"true");
	include($serverRoot."/header.php");
	?> 
	<!-- This is inner text! -->
	<div  id="innertext" style="">
		<!-- This is inner text! -->
		<h1><?php echo (isset($ootdGameTitle)?$ootdGameTitle:'Organism of the Day'); ?></h1>
		<div style="margin:2px;">
			<div style="font:bold 16px Arial,Helvetica,sans-serif;float:left;" >
				<?php echo date('l, F jS, Y'); ?>
			</div>
			<div onclick="toggleById('researchlistpopup');" title="How to Play?" style="display:table-cell;vertical-align:middle;cursor:pointer;float:left;height:26px;margin-left:10px;z-index:5;">
				<img src="../../images/games/ootd/qmark.jpg" style="height:20px;"/>
			</div>
			<div id="researchlistpopup" class="genericpopup" style="display:none;position:relative;top:30px;left:193px;" >
				<img src="../../images/games/ootd/uptriangle.png" style="position:absolute;top:-12px;left:30px;z-index:5;" />
				<div style="position:relative;top:-15px;text-align:left;clear:both;margin-bottom:-15px;" >Look at the picture, and see if you can figure out what the plant is. If you get completely stumped, you can 
					click the "I give up" button. A new plant is updated daily, so make sure you check back every day to test your knowledge!
				</div>
			</div>
		</div>
		<?php
		if(!$submitAction){
			?>
			<div id="">
				<div style="z-index:1;" >
					<!--Plant of the Day body here-->
					<title><?php echo (isset($ootdGameTitle)?$ootdGameTitle:'Organism of the Day'); ?></title>
					<br /><br />
					<div class = "dailypicture" align = "center">
						<div>
							<div height = "350" style="vertical-align:middle"">
								<a href="javascript:chgImg(1)"><img src="../../temp/ootd/plant300_1.jpg" name="slideshow" id="slideshow" style="width:500px;" ></a><br />
							</div><br />
							<a href="javascript:chgImg(-1)">Previous</a> &nbsp;|&nbsp;
							<a href="javascript:chgImg(1)">Next</a>
						</div>
					</div>
					<div style="margin-left:auto;margin-right:auto;font-size:18px;text-align:center;margin-top:20px;margin-bottom:20px;" ><b>Name that <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?>!</b></div>
					<div>
						<form name="answers" id="answers" method="post" action="index.php" class="asholder">
							<div style="width:500px;margin-left:auto;margin-right:auto;" >
								<div style="float:left;" >
									<div style="float:left;" >
										<b>Family:</b> <input type="text" id="family_answer" name = "family_answer" style="width:200px;color:#888;font-weight:bold;" value = "Family" onfocus="if(this.value=='Family') {this.value='', this.style.color='black', this.style.fontWeight='normal'}" onblur="if(this.value=='') {this.value='Family', this.style.color='#888', this.style.fontWeight='bold'}" />
									</div>
									<div style="margin-top:20px;float:left;clear:left;" >
										<b>Scientific name:</b> <input type="text" id="sciname_answer" style="width:200px;color:#888;font-weight:bold;" name = "sciname_answer" value = "Genus species" onfocus="if(this.value=='Genus species') {this.value='', this.style.color='black', this.style.fontWeight='normal'}" onblur="if(this.value=='') {this.value='Genus species', this.style.color='#888', this.style.fontWeight='bold'}" />
									</div>
								</div>
								<div style="float:right;" >
									<div style="float:right;" >
										<input name="submitaction" type="submit" value="Submit" style="height:7em; width:10em;"/>
									</div>
									<div style="margin-top:20px;float:right;clear:right;" >
										<button name="submitaction" type="submit" value="giveup" style="height:2em; width:8em;" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >I give up!</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<?php
		}
		elseif((strtolower($_POST['family_answer']) == strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) == strtolower($gameInfo['sciname']))){
			?>
			<div id="correct" style="">
				<div style="width:700px;margin-top:80px;margin-left:auto;margin-right:auto;clear:both;text-align:center;display:table;">
					<div style="display:table-row;" >
						<div style="width:160px;float:left;display:table-cell;" >
							<img src = "../../images/games/ootd/balloons-150.jpg">
						</div>
						<div style="width:350px;font-size:25px;float:left;margin-top:50px;display:table-cell;" >
							<b>Congratulations! That is<br />correct!</b>
						</div>
						<div style="width:160px;float:right;display:table-cell;" >
							<img src = "../../images/games/ootd/balloons-150.jpg">
						</div>
					</div>
				</div>
				<div style="width:670px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
					<div style="font-size:18px;" ><b><?php echo $gameInfo['family']; ?></b><br />
						<i><?php echo $gameInfo['sciname']; ?></i>
					</div>
					<div style="margin-top:30px;font-size:16px;" >
						<a href = "#" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here to learn more about this plant-</a>
					</div>
				</div>
			</div>
			<?php
		}
		
		elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) != strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer != strtolower($gameInfo['genus']))){
			?>
			<div id="incorrect_both" class="middlecenter">
				<!-- This is inner text! -->
				<div style="width:670px;margin-top:80px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
					<div style="font-size:25px;" >
						<b>Sorry, that is not correct</b>
					</div>
					<div style="margin-top:25px;font-size:18px;" >
						<b>Hint:</b> The family is <u>not</u> 
						<?php echo $_POST['family_answer']; ?>.
					</div>
					<div style="margin-top:40px;font-size:16px;" >
						<a href = ".">Click Here to try again!</a>
						<br /><br />
						OR
						<br /><br />
						<a href = "index.php?submitaction=giveup" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the plant was-</a>
					</div>
				</div>
			</div>
			<?php
		}
		
		elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) == strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer != strtolower($gameInfo['genus']))){
			?>
			<div id="incorrect_sciname" class="middlecenter">
				<!-- This is inner text! -->
				<div style="width:670px;margin-top:80px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
					<div style="font-size:25px;" >
						<b>Sorry, that is not correct</b>
					</div>
					<div style="margin-top:25px;font-size:18px;" >
						On the bright side, <b>you did get the family right</b>; it's 
						<?php echo $gameInfo['family']; ?>.
					</div>
					<div style="margin-top:40px;font-size:16px;" >
						<a href = ".">Click Here to try again!</a>
						<br /><br />
						OR
						<br /><br />
						<a href = "index.php?submitaction=giveup" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the plant was-</a>
					</div>
				</div>
			</div>
			<?php
		}
		
		elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) != strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) == strtolower($gameInfo['sciname']))){
			?>
			<div id="incorrect_sciname" class="middlecenter">
				<!-- This is inner text! -->
				<div style="width:670px;margin-top:80px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
					<div style="font-size:25px;" >
						<b>Sorry, that is not correct</b>
					</div>
					<div style="margin-top:25px;font-size:18px;" >
						<b>You did get the scientific name right</b>; it's 
						<?php echo $gameInfo['sciname']; ?>, but the family is not <?php echo $_POST['family_answer']; ?>.
					</div>
					<div style="margin-top:40px;font-size:16px;" >
						<a href = ".">Click Here to try again!</a>
						<br /><br />
						OR
						<br /><br />
						<a href = "index.php?submitaction=giveup" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the plant was-</a>
					</div>
				</div>
			</div>
			<?php
		}
		
		elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) != strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer == strtolower($gameInfo['genus']))){
			?>
			<div id="incorrect_sciname" class="middlecenter">
				<!-- This is inner text! -->
				<div style="width:670px;margin-top:80px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
					<div style="font-size:25px;" >
						<b>Sorry, that is not correct</b>
					</div>
					<div style="margin-top:25px;font-size:18px;" >
						On the bright side, <b>you did get the genus right</b>; it's 
						<?php echo $gameInfo['genus']; ?>.
					</div>
					<div style="margin-top:40px;font-size:16px;" >
						<a href = ".">Click Here to try again!</a>
						<br /><br />
						OR
						<br /><br />
						<a href = "index.php?submitaction=giveup" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the plant was-</a>
					</div>
				</div>
			</div>
			<?php
		}
		
		elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) == strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer == strtolower($gameInfo['genus']))){
			?>
			<div id="incorrect_sciname" class="middlecenter">
				<!-- This is inner text! -->
				<div style="width:670px;margin-top:80px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
					<div style="font-size:25px;" >
						<b>Sorry, that is not correct</b>
					</div>
					<div style="margin-top:25px;font-size:18px;" >
						On the bright side, <b>you did get the family and genus right</b>; The family 
						is <?php echo $gameInfo['family']; ?>, and the genus is <?php echo $gameInfo['genus']; ?>.
					</div>
					<div style="margin-top:40px;font-size:16px;" >
						<a href = ".">Click Here to try again!</a>
						<br /><br />
						OR
						<br /><br />
						<a href = "index.php?submitaction=giveup" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the plant was-</a>
					</div>
				</div>
			</div>
			<?php
		}
		
		elseif($submitAction == 'giveup'){
			?>
			<div id="giveup" class="middlecenter">
				<!-- This is inner text! -->
				<div style="width:670px;margin-top:80px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
					<div style="font-size:25px;" >
						<b>Too bad!</b>
					</div>
					<div style="margin-top:25px;font-size:18px;" >
						It was <br /><br />
						<b><?php echo $gameInfo['family']; ?></b><br />
						<i><?php echo $gameInfo['sciname']; ?></i>
					</div>
					<div style="margin-top:40px;font-size:16px;" >
						<a href = "#" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here to learn more about this plant-</a>
						<br /><br />
						Thank you for playing!
						<br /><br />
						Check back tomorrow for a new plant!
					</div>
				</div>
			</div>
			<?php
		}
		?>
	</div>

	<?php
	include($serverRoot."/footer.php");
	?> 
</body>
</html>
