<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GamesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:0;
$submitAction = array_key_exists("submitaction",$_POST)?$_POST["submitaction"]:'';
$oodID = array_key_exists("oodid",$_REQUEST)?$_REQUEST["oodid"]:1;
$ootdGameChecklist = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0;
$ootdGameTitle = array_key_exists("title",$_REQUEST)?$_REQUEST["title"]:0;
$ootdGameType = array_key_exists("type",$_REQUEST)?$_REQUEST["type"]:0;

$gameManager = new GamesManager();
$clArr = $gameManager->getChecklistArr($pid);

$gameInfo = $gameManager->setOOTD($oodID,$ootdGameChecklist);
$imageArr = $gameInfo['images'];

if($submitAction){
	$scinameAnswerArr = explode(' ',trim($_POST['sciname_answer']));
	$genusAnswer = strtolower($scinameAnswerArr[0]);
}
?>
<html>
<head>
	<title><?php echo (isset($ootdGameTitle)?$ootdGameTitle:'Organism of the Day'); ?></title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../../js/symb/games.ootd.js"></script>
	
	<script type="text/javascript">
		$(function() {
			var dialogArr = new Array("game");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true,
					position: { my: "center top", at: "right bottom", of: "#"+dialogStr }
				});

				$( "#"+dialogStr ).click(function() {
					$( "#"+this.id+"dialog" ).dialog( "open" );
				});
			}

		});
		
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
	include($SERVER_ROOT."/header.php");
	?> 
	<!-- This is inner text! -->
	<div id="innertext" style="">
		<!-- This is inner text! -->
		<div style="width:80%;margin-left:auto;margin-right:auto;">	
			<div style="text-align:center;margin-bottom:20px;">
				<h1><?php echo (isset($ootdGameTitle)?$ootdGameTitle:'Organism of the Day'); ?></h1>
			</div>
			<?php
			if(!$submitAction){
				?>
				<div style="z-index:1;width:500px;margin-left:auto;margin-right:auto;" >
					<!--Organism of the Day body here-->
					<div class = "dailypicture" align = "center">
						<div>
							<div style="vertical-align:middle;">
								<a href="javascript:chgImg(1)"><img src="../../temp/ootd/<?php echo $oodID; ?>_organism300_1.jpg" name="slideshow" id="slideshow" style="width:500px;" ></a><br />
							</div><br />
							<a href="javascript:chgImg(-1)">Previous</a> &nbsp;|&nbsp;
							<a href="javascript:chgImg(1)">Next</a>
						</div>
					</div>
					<div style="margin-left:auto;margin-right:auto;font-size:18px;text-align:center;margin-top:20px;margin-bottom:20px;" >
						<b>Name that <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?>!</b>
						<a id="gameinfo" href="#" onclick="return false" title="How to Play?">
							<img src="../../images/games/ootd/qmark.png" style="height:20px;"/>
						</a>
						<div id="gameinfodialog" title="How to Play">
							Look at the picture, and see if you can figure out what the <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?> is. If you get completely stumped, you can 
							click the "I give up" button. A new <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?> is updated daily, so make sure you check back every day to test your knowledge!
						</div>
					</div>
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
								<div style="float:right;margin-bottom:15px;" >
									<div style="float:right;" >
										<input name="submitaction" type="submit" value="Submit" style="height:7em; width:10em;"/>
									</div>
									<div style="margin-top:20px;float:right;clear:right;" >
										<button name="submitaction" type="submit" value="giveup" style="height:2em; width:8em;" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >I give up!</button>
									</div>
								</div>
								<div>
									<input name="oodid" type="hidden" value="<?php echo $oodID; ?>" />
									<input name="cl" type="hidden" value="<?php echo $ootdGameChecklist; ?>" /> 
									<input name="title" type="hidden" value="<?php echo $ootdGameTitle; ?>" /> 
									<input name="type" type="hidden" value="<?php echo $ootdGameType; ?>" /> 
								</div>
							</div>
						</form>
					</div>
				</div>
				<?php
			}
			elseif((strtolower($_POST['family_answer']) == strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) == strtolower($gameInfo['sciname']))){
				?>
				<div id="correct" style="">
					<div style="width:700px;margin-top:20px;margin-left:auto;margin-right:auto;clear:both;text-align:center;display:table;">
						<div style="display:table-row;" >
							<div style="width:160px;float:left;display:table-cell;" >
								<img src = "../../images/games/ootd/balloons-150.png">
							</div>
							<div style="width:350px;font-size:25px;float:left;margin-top:50px;display:table-cell;" >
								<b>Congratulations! That is<br />correct!</b>
							</div>
							<div style="width:160px;float:right;display:table-cell;" >
								<img src = "../../images/games/ootd/balloons-150.png">
							</div>
						</div>
					</div>
					<div style="width:670px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
						<div style="font-size:18px;" ><b><?php echo $gameInfo['family']; ?></b><br />
							<i><?php echo $gameInfo['sciname']; ?></i>
						</div>
						<div style="margin-top:30px;font-size:16px;" >
							<a href = "#" onClick="window.open('../../taxa/index.php?taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here to learn more about this <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?>-</a>
						</div>
					</div>
				</div>
				<?php
			}
			
			elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) != strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer != strtolower($gameInfo['genus']))){
				?>
				<div id="incorrect_both" class="middlecenter">
					<!-- This is inner text! -->
					<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
						<div style="font-size:25px;" >
							<b>Sorry, that is not correct</b>
						</div>
						<div style="margin-top:25px;font-size:18px;" >
							<b>Hint:</b> The family is <u>not</u> 
							<?php echo $_POST['family_answer']; ?>.
						</div>
						<div style="margin-top:40px;font-size:16px;" >
							<a href = "index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>">Click Here to try again!</a>
							<br /><br />
							OR
							<br /><br />
							<a href = "index.php?submitaction=giveup?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?> was-</a>
						</div>
					</div>
				</div>
				<?php
			}
			
			elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) == strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer != strtolower($gameInfo['genus']))){
				?>
				<div id="incorrect_sciname" class="middlecenter">
					<!-- This is inner text! -->
					<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
						<div style="font-size:25px;" >
							<b>Sorry, that is not correct</b>
						</div>
						<div style="margin-top:25px;font-size:18px;" >
							On the bright side, <b>you did get the family right</b>; it's 
							<?php echo $gameInfo['family']; ?>.
						</div>
						<div style="margin-top:40px;font-size:16px;" >
							<a href = "index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">Click Here to try again!</a>
							<br /><br />
							OR
							<br /><br />
							<a href = "index.php?submitaction=giveup?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?> was-</a>
						</div>
					</div>
				</div>
				<?php
			}
			
			elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) != strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) == strtolower($gameInfo['sciname']))){
				?>
				<div id="incorrect_sciname" class="middlecenter">
					<!-- This is inner text! -->
					<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
						<div style="font-size:25px;" >
							<b>Sorry, that is not correct</b>
						</div>
						<div style="margin-top:25px;font-size:18px;" >
							<b>You did get the scientific name right</b>; it's 
							<?php echo $gameInfo['sciname']; ?>, but the family is not <?php echo $_POST['family_answer']; ?>.
						</div>
						<div style="margin-top:40px;font-size:16px;" >
							<a href = "index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">Click Here to try again!</a>
							<br /><br />
							OR
							<br /><br />
							<a href = "index.php?submitaction=giveup?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?> was-</a>
						</div>
					</div>
				</div>
				<?php
			}
			
			elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) != strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer == strtolower($gameInfo['genus']))){
				?>
				<div id="incorrect_sciname" class="middlecenter">
					<!-- This is inner text! -->
					<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
						<div style="font-size:25px;" >
							<b>Sorry, that is not correct</b>
						</div>
						<div style="margin-top:25px;font-size:18px;" >
							On the bright side, <b>you did get the genus right</b>; it's 
							<?php echo $gameInfo['genus']; ?>.
						</div>
						<div style="margin-top:40px;font-size:16px;" >
							<a href = "index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">Click Here to try again!</a>
							<br /><br />
							OR
							<br /><br />
							<a href = "index.php?submitaction=giveup?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?> was-</a>
						</div>
					</div>
				</div>
				<?php
			}
			
			elseif(($submitAction != 'giveup') && (strtolower($_POST['family_answer']) == strtolower($gameInfo['family'])) && (strtolower($_POST['sciname_answer']) != strtolower($gameInfo['sciname'])) && ($genusAnswer == strtolower($gameInfo['genus']))){
				?>
				<div id="incorrect_sciname" class="middlecenter">
					<!-- This is inner text! -->
					<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
						<div style="font-size:25px;" >
							<b>Sorry, that is not correct</b>
						</div>
						<div style="margin-top:25px;font-size:18px;" >
							On the bright side, <b>you did get the family and genus right</b>; The family 
							is <?php echo $gameInfo['family']; ?>, and the genus is <?php echo $gameInfo['genus']; ?>.
						</div>
						<div style="margin-top:40px;font-size:16px;" >
							<a href = "index.php?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>">Click Here to try again!</a>
							<br /><br />
							OR
							<br /><br />
							<a href = "index.php?submitaction=giveup?oodid=<?php echo $oodID.'&cl='.$ootdGameChecklist.'&title='.$ootdGameTitle.'&type='.$ootdGameType; ?>" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here reveal what the <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?> was-</a>
						</div>
					</div>
				</div>
				<?php
			}
			
			elseif($submitAction == 'giveup'){
				?>
				<div id="giveup" class="middlecenter">
					<!-- This is inner text! -->
					<div style="width:670px;margin-top:30px;margin-left:auto;margin-right:auto;clear:both;text-align:center;" >
						<div style="font-size:25px;" >
							<b>Too bad!</b>
						</div>
						<div style="margin-top:25px;font-size:18px;" >
							It was <br /><br />
							<b><?php echo $gameInfo['family']; ?></b><br />
							<i><?php echo $gameInfo['sciname']; ?></i>
						</div>
						<div style="margin-top:40px;font-size:16px;" >
							<a href = "#" onClick="window.open('../../taxa/index.php?taxauthid=1&taxon=<?php echo $gameInfo['tid']; ?>','plantwindow','width=900,height=650')" >-Click here to learn more about this <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?>-</a>
							<br /><br />
							Thank you for playing!
							<br /><br />
							Check back tomorrow for a new <?php echo (isset($ootdGameType)?$ootdGameType:'organism'); ?>!
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<?php
	include($SERVER_ROOT."/footer.php");
	?> 
</body>
</html>
