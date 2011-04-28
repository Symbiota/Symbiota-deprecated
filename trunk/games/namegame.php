<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GamesManager.php');
header("Content-Type: text/html; charset=".$charset);

$clName = (array_key_exists('listname',$_REQUEST)?$_REQUEST['listname']:"");
$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:"";
$dynClid = array_key_exists('dynclid',$_REQUEST)?$_REQUEST['dynclid']:"";

if(!$clName){
	$gameManager = new GamesManager();
	if($clid){
		$gameManager->setChecklist($clid);
	}
	elseif($dynClid){
		$gameManager->setDynChecklist($dynClid);
	}
	$clName = $gameManager->getClName();
}

$imgloc = "../images/games/namegame/";

?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Name Game</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<link rel="stylesheet" href="../css/namegamestyle.css" type="text/css" />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<style type="text/css">
		#blanket {background-color:#111;opacity: 0.65;position:absolute;z-index: 9001; /*ooveeerrrr nine thoussaaaannnd*/top:0px;left:0px;width:100%;}
		#popUpDiv {position:absolute;top: 46px; right: 80px;background-color:#eeeeee;width:300px;height:90px;z-index: 9002; /*ooveeerrrr nine thoussaaaannnd*/}
		#listreload {position:absolute;top: 46px; right: 80px;background-color:#eeeeee;width:300px;height:90px;z-index: 9003; /*ooveeerrrr nine thoussaaaannnd*/}
		html {overflow-y: scroll;}
		table{}
		.lettertable{border:1px solid #000000;border-spacing:3px;}
		.tableplain{border:1px}
		.tableplain th{}
		.tableplain tr{}
		#charactertable td{margin-left: auto;margin-right: auto;vertical-align: middle;border:1px solid #000000;width:50px;cursor:hand;cursor:pointer;font-size:25;font-weight:bold;color:#000000}
		.buttonover{border:5px outset gray;cursor:hand;cursor:pointer;font-weight:normal;font-weight:bold}
		.buttonout{border:5px outset #CCCC99;font-weight:normal}
		.buttondown{border:5px inset gray;cursor:hand;cursor:pointer;font-weight:bold}
		.buttonup{border:5px outset #CCCC99;cursor:hand;cursor:pointer;font-weight:normal;font-weight:bold}
		#showhint{width:100px;float:left;margin-left:10px}
		#newgame{width:100px;float:right;margin-right:10px}
		.comma{font-size:20px}
		.dot{font-size:20px}
		.dash{font-size:20px}
		.question{font-size:30px}
		#rw{margin-left:auto;margin-right:auto}
	</style>
	<script type="text/javascript" src="../js/symb/namegamecsspopup.js" charset="utf-8"></script>
	<script language="javascript"> 
		//COLLAPSE MENU
		function toggle(divID, linkID) {
			var ele = document.getElementById(divID);
			var text = document.getElementById(linkID);
			if(ele.style.display == "block") {
		    		ele.style.display = "none";
				//text.innerHTML = "<b><font size = \"4\">+</font></b>";
		  	}
			else {
				ele.style.display = "block";
				//text.innerHTML = "<b><font size = \"4\">-</font></b>";
			}
		} 

		//CHANGE WORDLIST SCRIPT
		function getXMLHTTP() { //fuction to return the xml http object
			var xmlhttp=false;	
			try{
				xmlhttp=new XMLHttpRequest();
			}
			catch(e){		
				try{			
					xmlhttp= new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch(e){
					try{
						xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
					}
					catch(e1){
						xmlhttp=false;
					}
				}
			}
			return xmlhttp;
		}
			
			
			
		function getWordList(strURL, referer){ 
			var req = getXMLHTTP();
					/*if (referer == 'newlist')
						{
								popup('popUpDiv');
						}*/
			if(req){
				req.onreadystatechange = function(){
					if (req.readyState == 4){
						// only if "OK"
						if (req.status == 200){
							//document.getElementById('wordlistinsert').innerHTML=req.responseText;
							eval(req.responseText);
							generate();
							/*if (referer == 'newlist')
							{
								popup('popUpDiv')
							}
							else
							{
								popup('listreload')
							}*/
							//alert("Plant list has been changed");
						} 
						else{
							alert("There was a problem while using XMLHTTP:\n" + req.statusText);
						}
					}				
				}			
				req.open("GET", strURL, true);
				req.send(null);
			}
		}
		
		function getpic(strURL){
			var req = getXMLHTTP();
			if(req){
				req.onreadystatechange = function(){
					if (req.readyState == 4){
						// only if "OK"
						if (req.status == 200){						
							eval(req.responseText);
						}
						else{
							alert("There was a problem while using XMLHTTP:\n" + req.statusText);
						}
					}				
				}			
				req.open("GET", strURL, true);
				req.send(null);
			}
		}

		function confirmlistchange(a){
			var confirmchange= confirm("Are you sure you want to set this plant list?\n    Current progress will be lost.");
			if(confirmchange== true){
				//<!-----------------------------Do stuff here to change the word list----------------------------------->
				//popup('popUpDiv');
				getWordList('rpc/getwordlist.php?<?php echo $clid?'clid='.$clid:'dynclid='.$dynClid; ?>'+a, 'newlist');
				listnum = a;
				return false;
			}
			else{
				return false;
			}
		}
		
		function picpopup(){
			getpic('getpic.php?species='+wordanswer);
			//window.open(picurl,'','width=800,height=600,resizable=yes'); 
		}
	</script>
	<script language="javascript">
		mainList = [['','']];
		hangpics=[
			["<?php echo $imgloc; ?>man1_0.gif","<?php echo $imgloc; ?>man1_1.gif","<?php echo $imgloc; ?>man1_2.gif","<?php echo $imgloc; ?>man1_3.gif","<?php echo $imgloc; ?>man1_4.gif","<?php echo $imgloc; ?>man1_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>man1win.gif"],
			["<?php echo $imgloc; ?>woman1_0.gif","<?php echo $imgloc; ?>woman1_1.gif","<?php echo $imgloc; ?>woman1_2.gif","<?php echo $imgloc; ?>woman1_3.gif","<?php echo $imgloc; ?>woman1_4.gif","<?php echo $imgloc; ?>woman1_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>woman1win.gif"],
			["<?php echo $imgloc; ?>man2_0.gif","<?php echo $imgloc; ?>man2_1.gif","<?php echo $imgloc; ?>man2_2.gif","<?php echo $imgloc; ?>man2_3.gif","<?php echo $imgloc; ?>man2_4.gif","<?php echo $imgloc; ?>man2_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>man2win.gif"],
			["<?php echo $imgloc; ?>woman2_0.gif","<?php echo $imgloc; ?>woman2_1.gif","<?php echo $imgloc; ?>woman2_2.gif","<?php echo $imgloc; ?>woman2_3.gif","<?php echo $imgloc; ?>woman2_4.gif","<?php echo $imgloc; ?>woman2_5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>woman2win.gif"],
			["<?php echo $imgloc; ?>wwoman0.gif","<?php echo $imgloc; ?>wwoman1.gif","<?php echo $imgloc; ?>wwoman2.gif","<?php echo $imgloc; ?>wwoman3.gif","<?php echo $imgloc; ?>wwoman4.gif","<?php echo $imgloc; ?>wwoman5.gif","<?php echo $imgloc; ?>gallow.gif","<?php echo $imgloc; ?>gallow5.gif","<?php echo $imgloc; ?>gallow4.gif","<?php echo $imgloc; ?>gallow3.gif","<?php echo $imgloc; ?>gallow2.gif","<?php echo $imgloc; ?>gallow1.gif","<?php echo $imgloc; ?>spacer.gif","<?php echo $imgloc; ?>wwomanwin.gif"],
			["<?php echo $imgloc; ?>flower0.gif","<?php echo $imgloc; ?>flower1.gif","<?php echo $imgloc; ?>flower2.gif","<?php echo $imgloc; ?>flower3.gif","<?php echo $imgloc; ?>flower4.gif","<?php echo $imgloc; ?>flower5.gif","<?php echo $imgloc; ?>flower6.gif","<?php echo $imgloc; ?>flower7.gif","<?php echo $imgloc; ?>flower8.gif","<?php echo $imgloc; ?>flower9.gif","<?php echo $imgloc; ?>flower10.gif","<?php echo $imgloc; ?>flower11.gif","<?php echo $imgloc; ?>flower12.gif","<?php echo $imgloc; ?>flowerwin.gif"],
			["<?php echo $imgloc; ?>plant0.gif","<?php echo $imgloc; ?>plant1.gif","<?php echo $imgloc; ?>plant2.gif","<?php echo $imgloc; ?>plant3.gif","<?php echo $imgloc; ?>plant4.gif","<?php echo $imgloc; ?>plant5.gif","<?php echo $imgloc; ?>plant6.gif","<?php echo $imgloc; ?>plant7.gif","<?php echo $imgloc; ?>plant8.gif","<?php echo $imgloc; ?>plant9.gif","<?php echo $imgloc; ?>plant10.gif","<?php echo $imgloc; ?>plant11.gif","<?php echo $imgloc; ?>plant12.gif","<?php echo $imgloc; ?>plantwin.gif"],
			["<?php echo $imgloc; ?>tempcover0.jpg","<?php echo $imgloc; ?>tempcover1.jpg","<?php echo $imgloc; ?>tempcover2.jpg","<?php echo $imgloc; ?>tempcover3.jpg","<?php echo $imgloc; ?>tempcover4.jpg","<?php echo $imgloc; ?>tempcover5.jpg","<?php echo $imgloc; ?>tempcover6.jpg","<?php echo $imgloc; ?>plant7.gif","<?php echo $imgloc; ?>plant8.gif","<?php echo $imgloc; ?>plant9.gif","<?php echo $imgloc; ?>plant10.gif","<?php echo $imgloc; ?>plant11.gif","<?php echo $imgloc; ?>plant12.gif","<?php echo $imgloc; ?>tempcover0.jpg"],
			["<?php echo $imgloc; ?>apple_0.gif","<?php echo $imgloc; ?>apple_1.gif","<?php echo $imgloc; ?>apple_2.gif","<?php echo $imgloc; ?>apple_3.gif","<?php echo $imgloc; ?>apple_4.gif","<?php echo $imgloc; ?>apple_5.gif","<?php echo $imgloc; ?>apple_6.gif","<?php echo $imgloc; ?>apple_7.gif","<?php echo $imgloc; ?>apple_8.gif","<?php echo $imgloc; ?>apple_9.gif","<?php echo $imgloc; ?>apple_10.gif","<?php echo $imgloc; ?>apple_11.gif","<?php echo $imgloc; ?>apple_12.gif","<?php echo $imgloc; ?>apple_win.gif"]
		];

		PreImage0 = new Image();
		PreImage1 = new Image();
		PreImage2 = new Image();
		PreImage3 = new Image();
		PreImage4 = new Image();
		PreImage5 = new Image();
		PreImage6 = new Image();
	
		defaultImage="<?php echo $imgloc; ?>plant7.gif";
		maxWildCards=1;

		imgSetId="imageset"; //  default avatar
		lastImgId = "img7";
		imgSetVal="6";
		lastImg="";

		firstload="1";
		levelSet="levelset"; //  default level
		lastLevelId = "level2";
		levelSetVal="6";
		lastLevelImg="";
		won=0;
		gameover=0;
		played=0;
		running=0;
		lastChar="";
		hintShown=0;
		wordChosen="";
		ns=document.getElementById&&!document.all;

		step=5;
		repeat="";

		function initNameGame(){
			
			mClick(imgSetId,lastImgId,imgSetVal,'<?php echo $imgloc; ?>plant_on.gif','<?php echo $imgloc; ?>plant_off.gif');
			mClick(levelSet,lastLevelId,levelSetVal,'<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif');
			
			tds=document.getElementById("charactertable").getElementsByTagName("TD");
			
			for(var i=0 ; i<tds.length ; i++){
				tds[i].getElementsByTagName("SPAN")[0].onmouseover=function(){this.offsetParent.bgColor='#CCCC99';};
				tds[i].getElementsByTagName("SPAN")[0].onmousedown=function(){/*this.offsetParent.bgColor='#FFFFFF';*/this.offsetParent.style.color='#FFFFFF';};
				tds[i].getElementsByTagName("SPAN")[0].onmouseout=function(){this.offsetParent.bgColor='';this.offsetParent.style.color='#000000';};
			
				if(i<tds.length-1){
					tds[i].getElementsByTagName("SPAN")[0].onclick=function(){getKey(this.id);};
				}
			
				if(i==tds.length-1){
					tds[i].getElementsByTagName("SPAN")[0].onclick=function(){wildCard();};
				}
			}
			//alert("To begin playing, please choose a plant list from the left.");
			getWordList('rpc/getwordlist.php?<?php echo $clid?'clid='.$clid:'dynclid='.$dynClid; ?>', 'newlist');
			generate();
		}

		function mOver(setId,imgId,imgOn){
			if(setId==imgSetId&&running==0){
				(lastImgId != imgId?document.getElementById(imgId).src = imgOn:"");
			}
		}

		function mOut(setId,imgId,imgOff){
			if(setId==imgSetId&&running==0){
				(lastImgId != imgId?document.getElementById(imgId).src = imgOff:"");
			}
		}

		function mClick(setId,imgId,imgVal,imgOn,imgOff){
			if(running==1) return;
		
			if(setId==imgSetId){
				document.getElementById(imgId).src = imgOn;
				
				if (lastImgId != ""){
					(lastImgId != imgId?document.getElementById(lastImgId).src = lastImg:"");
				}

				lastImgId = imgId;
				lastImg=imgOff;
				avatar=imgVal;
			}
		
			if(setId==levelSet){
				document.getElementById(imgId).src = imgOn;

				if (lastLevelId != ""){
					(lastLevelId != imgId?document.getElementById(lastLevelId).src = lastLevelImg:"");
				}

				lastLevelId = imgId;
				lastLevelImg=imgOff;
				levelSetVal=imgVal;
			}
			level();
		}

		function generate(){
			numbersRange=mainList.length; //range
			firstRun=true;
			selectedNums=new Array(); 
			
			for(var i=0;i<numbersRange;i++){
				wordChosen=false; 
				rndnum=Math.floor(Math.random()*numbersRange);
				
				if(!firstRun){
					for(var j=0;j<selectedNums.length;j++){
						if(rndnum==selectedNums[j]){
							wordChosen=true; 
							i--;
						}
					} 
				} 
				
				if(!wordChosen){ 
					selectedNums[i]=rndnum; 
					firstRun=false;
				} 
			}
			wordCount=0;
			newWord();
		}

		function newWord(){
			if(wordCount==selectedNums.length){ 
				// generate random list 
				// Does this when you run out of words in current list
				//generate();
				//popup('listreload');
				getWordList('rpc/getwordlist.php?<?php echo $clid?'clid='.$clid:'dynclid='.$dynClid; ?>', 'reloadlist');
				return;
			}
			
			lastChar="";
			running=0;
			clearTimeout(repeat);  // splash
			done=0;
			temp="";
			wildCount=0;
			hintShown=0;
			document.getElementById("hintdisplay").innerHTML="";
			charDisplay=document.getElementById("charactertable").getElementsByTagName('SPAN');
			
			for(var k=0;k<charDisplay.length;k++){
				charDisplay[k].style.visibility="visible"; // show all hidden alphabet characters
			}
		
			//currentNum=Math.floor(Math.random()*mainList.length) // randomly chosen word
			
			currentNum=selectedNums[wordCount];
			chosenWord=mainList[currentNum][0].toLowerCase(); // chosen word
			RealName=chosenWord;
		
			/////////////////////////TAKE OUT THE VAR. or SSP. BECUASE IT'S TOO LONG///////////////////////////
			tempWord=chosenWord;
			varpos=tempWord.indexOf(" var.");
			ssppos=tempWord.indexOf(" ssp.");
			secondWord='';
			if (varpos != -1){
				subWord=tempWord.substring(0, varpos);
				secondWord=tempWord.substring(varpos);
				secondWord=secondWord.toUpperCase();
				//alert("Removing ' var.'. First part is '"+subWord+"' and second part is '"+secondWord+"'.");
				chosenWord=subWord;
			}
			else if (ssppos != -1){
				subWord=tempWord.substring(0, ssppos);
				secondWord=tempWord.substring(ssppos);
				secondWord=secondWord.toUpperCase();
				//alert("Removing ' ssp.'. First part is '"+subWord+"' and second part is '"+secondWord+"'.");
				chosenWord=subWord;
			}
			///////////////////////////////////////////////////////////////////////////////////////////////////////////
			lengthofword=chosenWord;
			wordanswer=chosenWord;
			////////////////////////////MAKES SPACES WIDER///////////////////////////////////////////////////////////////
			tempchosen = chosenWord;
			templength = 0;
			tempbuilder = "";
			wordLength = tempchosen.length;
			for(var m=0;m<wordLength;m++){
				if(tempchosen.charAt(m)!=" "){
					tempbuilder+=tempchosen.charAt(m);
				}
				else{
					tempbuilder+="\u00A0\u00A0\u00A0\u00A0";
					templength+=3;
				}
			}
			chosenWord = tempbuilder;
			
			////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			initWildCard(chosenWord);
			wordLength=lengthofword.length; // word length
			wordLength+=templength;
			temp="";
			addedlength = 0;
			
			for(var n=0;n<wordLength;n++){
				if((chosenWord.charAt(n)!=" ")&&(chosenWord.charAt(n)!="\u00A0")){
					if(chosenWord.charAt(n)==".")
						temp+="."; // displays the periods
					else
						temp+="_"; // replace the characters in chosen word with empty places
				}
				else{
					temp+="\u00A0";
					//temp+=" \u00A0\u00A0";
					//addedlength += 2;
				}
			}
			//wordLength = temp.length; 
			//wordLength+=addedlength;
			document.getElementById("attempt").innerHTML=temp; // display empty places
			if ( firstload == "1"){
				//popup('popUpDiv');
				firstload = "0";
			}
			//document.getElementById("newgame").innerHTML="New Game"; 
			last=temp; // remember last attempt
			wordChosen=1; // word has been selected
			wordCount++;
			document.getElementById("showhint").disabled=false;
			
			var now = new Date();
		
			PreImage0.src = 'tempcover0.jpg?' + now.getTime();
			PreImage1.src = 'tempcover1.jpg?' + now.getTime();
			PreImage2.src = 'tempcover2.jpg?' + now.getTime();
			PreImage3.src = 'tempcover3.jpg?' + now.getTime();
			PreImage4.src = 'tempcover4.jpg?' + now.getTime();
			PreImage5.src = 'tempcover5.jpg?' + now.getTime();
			PreImage6.src = 'tempcover6.jpg?' + now.getTime();
			level();
		 	//document.getElementById("hintdisplay").innerHTML=list+"<br><br>"
		}

		function newGame (){
			if (gameover == 0)
				played++;
			document.getElementById("plays").innerHTML=played;
			document.getElementById("rate").innerHTML=((won/played)*100).toFixed(2)+"%";
			gameover = 0;
			newWord();
		}

		function level(){
			if(running==1)return;
			guessCount=levelSetVal;
			
			if(avatar=="5"){
				if(guessCount=="12") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>flower12.gif";
				else if(guessCount=="6") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>flower6.gif";
				else if(guessCount=="3") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>flower3.gif";
			}
			else if(avatar=="6"){
				if(guessCount=="12") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant12.gif";
				else if(guessCount=="6") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant7.gif";
				else if(guessCount=="3") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant4.gif";
			}
			else if(avatar=="7"){				// IF IT'S THE REVEALING PLANT PICTURE
				getpic('getpic.php?species='+wordanswer);
				if(guessCount=="12") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>plant12.gif";
				else if(guessCount=="6") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>tempcover6.jpg";
				else if(guessCount=="3") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>tempcover3.jpg";
			}
			else if(avatar=="8"){				// IF IT'S THE REVEALING PLANT PICTURE
				if(guessCount=="12") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>apple_12.gif";
				else if(guessCount=="6") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>apple_6.gif";
				else if(guessCount=="3") 	
					document.getElementById("hpic").src="<?php echo $imgloc; ?>apple_6.gif";
			}
			else if(guessCount=="12"){
				document.getElementById("hpic").src="<?php echo $imgloc; ?>spacer.gif";
			}
			else if((avatar>="0")&&(avatar<="4")){
				document.getElementById("hpic").src="<?php echo $imgloc; ?>gallows.gif";
			}
			
			document.getElementById("counter").innerHTML="Chances left = "+guessCount; // visual counter
			document.getElementById("splash").style.display="none";  // splash
		}

		function getKey(e){
			running=1;
			keyCode=(!ns)?event.keyCode:e.which; // which key has been pressed
			chkChar=e;
			temp="";
			
			if(keyCode){
				if(keyCode>=65&&keyCode<=90){ // if caps on
					keyCode+=32;
				}
				currentChar=keyCode; // character chosen by key
			}
			else{
				currentChar=chkChar.charCodeAt(0) // character chosen by mouse
			}

			if(currentChar==13){ // press return key to choose a new word
				newWord();
				return;
			}

			if(wordChosen==0){return;} // word not selected

			for(var k=0;k<wildCardArr.length;k++){ // remove from wildCardArr
				if(wildCardArr[k]==String.fromCharCode(currentChar)){
					wildCardArr.splice(k, 1);
				}
			}

			if(lastChar==currentChar||currentChar<44||currentChar>46&&currentChar<97||currentChar>122){
				return; // if not lowercase alphabet or if current key is the same as the last key pressed
			}

			if(document.getElementById(String.fromCharCode(currentChar)).style.visibility=="hidden"){return;}

			correct=false
			document.getElementById(String.fromCharCode(currentChar)).style.visibility="hidden"; // hide chosen character

			for(var n=0;n<last.length;n++){ // run through chosen word characters
				if(String.fromCharCode(currentChar)==chosenWord.charAt(n)){ // if selected character is in the chosen word
					temp+=chosenWord.charAt(n); // replace empty place with character
					correct=true;
				}
				else{
					/*if (last.charAt(n) == '&'){
						temp+=''
						//n+=5
					}
					else if (last.charAt(n) == ' '){
						temp+=' '
						//n+=5
					}
					else*/
					temp+=last.charAt(n); // replace with empty place
				}
			}

			if(correct==false&&guessCount>0){ // if selected character not correct
				guessCount--; // deduct 1 from guesses left
				document.getElementById("hpic").src=hangpics[avatar][guessCount]; // show pic
			}
			
			document.getElementById("attempt").innerHTML=temp.toUpperCase(); // change correct character chosen to uppercase
			document.getElementById("counter").innerHTML="Chances left = "+guessCount; // visual counter
			
			last=temp; // remember last attempt
			lastChar=currentChar; // remember last character selected
			
			if(guessCount==0){ // if counter reaches zero
				wordChosen=0;
				gameover=1;
				document.getElementById("counter").innerHTML="<div id=\"rw\" style=\"width:190px\" onmouseover=\"this.className='buttonover'\" onmouseout=\"this.className='buttonout'\" onmousedown=\"this.className='buttondown'\" onmouseup=\"this.className='buttonup'\" class=\"buttonout\" onclick='showWord()'><b><center>Reveal the Plant</center></b></div>";
				played++;
				document.getElementById("plays").innerHTML=played;
				var myNewString = RealName.replace(/\u00A0\u00A0\u00A0\u00A0/g, "%20");
				document.getElementById("splash").innerHTML="<font color = \"red\">Too Bad</font><br><a href = \"#\" onClick=\"window.open('../taxa/index.php?taxon="+myNewString+"','mywindow','width=900,height=675')\"> <font size = \"4\" color = \"#0000FF\"><center><u><b><br>Click here for more about this plant</b></u></center></font></a><br>"; // splash
				document.getElementById("splash").style.display="";  // splash
				document.getElementById("rate").innerHTML=((won/played)*100).toFixed(2)+"%";
				gameEnd(); // splash
			}

			if(temp==chosenWord){ // if correct word found
				wordChosen=0;
				gameover=1;
				played++;
				document.getElementById("plays").innerHTML=played;
				won++;
				document.getElementById("wins").innerHTML=won;
				var myNewString = RealName.replace(/\u00A0\u00A0\u00A0\u00A0/g, "%20");
				if (secondWord!='')
					document.getElementById("attempt").innerHTML=chosenWord.toUpperCase()+"<br><span style=\"font-size:12px\">"+secondWord+"</span>"; // change chosen word to uppercase
				else
					document.getElementById("attempt").innerHTML=chosenWord.toUpperCase(); // change chosen word to uppercase
				//if (secondWord!='')
					//document.getElementById("splash").innerHTML=secondWord+"<br><font color = \"#336699\">You Win!</font><br><a href = \"#\" onClick=\"window.open('../taxa/index.php?taxon="+myNewString+"','mywindow','width=900,height=675')\"> <font size = \"4\" color = \"#0000FF\"><center><u><b><br>Click here for more about this plant</b></u></center></font></a><br>" // splash
				//else
				document.getElementById("splash").innerHTML="<font color = \"#336699\">You Win!</font><br><a href = \"#\" onClick=\"window.open('../taxa/index.php?taxon="+myNewString+"','mywindow','width=900,height=675')\"> <font size = \"4\" color = \"#0000FF\"><center><u><b><br>Click here for more about this plant</b></u></center></font></a><br>"; // splash
				document.getElementById("hintdisplay").innerHTML=/*list+"<br>"+*/mainList[currentNum][1]; //show the family
				document.getElementById("splash").style.display="";  // splash
				document.getElementById("rate").innerHTML=((won/played)*100).toFixed(2)+"%";
				document.getElementById("hpic").src=hangpics[avatar][13];
				gameEnd(); // splash
			}
			
			if(guessCount==1){
				document.getElementById("showhint").disabled=true;
				document.getElementById("?").style.visibility="hidden";
			}
		}

		function showHint(){
			running=1;
			if(guessCount<=1||done==1){return;}
			if(hintShown==0){
				guessCount--;
				hintShown=1;
				document.getElementById("hintdisplay").innerHTML=/*list+"<br>"+*/mainList[currentNum][1]; //show the family
				document.getElementById("counter").innerHTML="Chances left = "+guessCount; // visual counter
				document.getElementById("hpic").src=hangpics[avatar][guessCount];
			}
			document.getElementById("showhint").disabled=true;
		}

		function showWord(){ // reveals the chosen word if counter reaches zero
			if(wordChosen==0&&guessCount!=0)
				{return;}
			if (secondWord!='')
				document.getElementById("attempt").innerHTML=chosenWord.toUpperCase()+"<br><span style=\"font-size:12px\">"+secondWord+"</span>"; // change chosen word to uppercase
			else
				document.getElementById("attempt").innerHTML=chosenWord.toUpperCase(); // change chosen word to uppercase
			hintShown=1;
			document.getElementById("hintdisplay").innerHTML=/*list+"<br>"+*/mainList[currentNum][1];
			clearTimeout(repeat);  // splash
			document.getElementById("rw").style.display="none";
		}

		function gameEnd(){
			done=1;
			sFont=1; // Smallest font size
			lFont=50; // Largest font size
			goSplash();
		}

		function goSplash(){
			document.getElementById("splash").style.visibility="visible";
			document.getElementById("splash").style.fontSize=30;
			sFont+=step;
			repeat=setTimeout("goSplash()",10); // Speed
			if(sFont>lFont){
				clearTimeout(repeat);
			}
		}

		function initWildCard(str){
			wildCardArr=[];
			wildCardArr[0]=str.charAt(0);
			for(var i=0;i<str.length;i++){
				isIn=0;
				for(var j=0;j<wildCardArr.length;j++){
					if(str.charAt(i)==wildCardArr[j]){
						isIn=1;
					}
				}
				if(isIn==0&&str.charAt(i)!=" "){
					wildCardArr[wildCardArr.length]=str.charAt(i);
				}
			}
			wildWordLength=wildCardArr.length; // for checking how may wild cards used against length of word
		}

		function wildCard(){
			if(wildCardArr.length==0||guessCount<=1){return;}
			wildCount++;
			rdm=Math.floor(Math.random()*wildCardArr.length);
			wildCardChar=wildCardArr[rdm]; //.splice(rdm, 1).toString()
			//wildCardArr.pop()
			if(wildCount==maxWildCards){
				document.getElementById("?").style.visibility="hidden";
			}
			guessCount--;
			document.getElementById("hpic").src=hangpics[avatar][guessCount];
			getKey(wildCardChar);
		}

		//document.onkeypress = getKey;
	</script>
</head>

<body onload="initNameGame()">

	<?php
	$displayLeftMenu = (isset($games_namegameMenu)?$games_namegameMenu:"true");
	include('../header.php');
	if(isset($games_namegameCrumbs)){
		?>
		<div class="navpath">
			<a href="../index.php">Home</a> &gt; 
			<?php echo $games_namegameCrumbs;?>
			<b>Name Game</b> 
		</div>
		<?php 
	}
	?>
	
	<!-- This is inner text! -->
	<div id="innertext">
		<h1><?php echo $defaultTitle; ?> Name Game</h1>
		<div style="margin:10px;">
			I am thinking of a species found within the following checklist: <b><?php echo $clName;?></b><br/> 
			What am I thinking of? 
		</div>

		<center>
			<table class="tableplain" border="0">
				<tr><td valign="top">
					<table class="tableplain" border="0" cellpadding="5px">
						<tr><td colspan="2" align="center">
							<P>
							<div id="imageset" style="cursor:hand;cursor:pointer">
								<img onclick="mClick(this.parentNode.id,this.id,'6','<?php echo $imgloc; ?>plant_on.gif','<?php echo $imgloc; ?>plant_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>plant_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>plant_off.gif')" src="<?php echo $imgloc; ?>plant_off.gif" id="img7">
								<img onclick="mClick(this.parentNode.id,this.id,'5','<?php echo $imgloc; ?>flower_on.gif','<?php echo $imgloc; ?>flower_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>flower_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>flower_off.gif')" src="<?php echo $imgloc; ?>flower_off.gif" id="img6">
								<img onclick="mClick(this.parentNode.id,this.id,'8','<?php echo $imgloc; ?>apple_on.gif','<?php echo $imgloc; ?>apple_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>apple_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>apple_off.gif')" src="<?php echo $imgloc; ?>apple_off.gif" id="img8">
								<!--<img onclick="mClick(this.parentNode.id,this.id,'0','<?php echo $imgloc; ?>man1_head_on.gif','<?php echo $imgloc; ?>man1_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man1_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man1_head_off.gif')" src="<?php echo $imgloc; ?>man1_head_off.gif" id="img1">-->
								<!--<img onclick="mClick(this.parentNode.id,this.id,'1','<?php echo $imgloc; ?>woman1_head_on.gif','<?php echo $imgloc; ?>woman1_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman1_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman1_head_off.gif')" src="<?php echo $imgloc; ?>woman1_head_off.gif" id="img2">-->
								<!--<img onclick="mClick(this.parentNode.id,this.id,'2','<?php echo $imgloc; ?>man2_head_on.gif','<?php echo $imgloc; ?>man2_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man2_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>man2_head_off.gif')" src="<?php echo $imgloc; ?>man2_head_off.gif" id="img3">-->
								<!--<img onclick="mClick(this.parentNode.id,this.id,'3','<?php echo $imgloc; ?>woman2_head_on.gif','<?php echo $imgloc; ?>woman2_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman2_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>woman2_head_off.gif')" src="<?php echo $imgloc; ?>woman2_head_off.gif" id="img4">-->
								<!--<img onclick="mClick(this.parentNode.id,this.id,'4','<?php echo $imgloc; ?>wwoman_head_on.gif','<?php echo $imgloc; ?>wwoman_head_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>wwoman_head_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>wwoman_head_off.gif')" src="<?php echo $imgloc; ?>wwoman_head_off.gif" id="img5">-->
								<!--<img onclick="mClick(this.parentNode.id,this.id,'7','<?php echo $imgloc; ?>hidden_on.gif','<?php echo $imgloc; ?>hidden_off.gif')" onmouseover="mOver(this.parentNode.id,this.id,'<?php echo $imgloc; ?>hidden_on.gif')" onmouseout="mOut(this.parentNode.id,this.id,'<?php echo $imgloc; ?>hidden_off.gif')" src="<?php echo $imgloc; ?>hidden_off.gif" id="img8">-->
							</div>
						</td></tr>
						<tr align="center">
							<td width="300px">
								<img id="hpic"src="<?php echo $imgloc; ?>plant7.gif"  height="150"><!--width="75"-->
								<P>
								<div id="counter" style="text-align:center">Chances left = 6</div><br>
							</td>
							<td width="350px" valign="top" align="center">
								<br><br>Difficulty<br>
								<div id="levelset" style="cursor:hand;cursor:pointer">
									Hard <img onclick="mClick(this.parentNode.id,this.id,'3','<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif')" src="<?php echo $imgloc; ?>radio_off4.gif" id="level1">
									<img onclick="mClick(this.parentNode.id,this.id,'6','<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif')" src="<?php echo $imgloc; ?>radio_off4.gif" id="level2">
									<img onclick="mClick(this.parentNode.id,this.id,'12','<?php echo $imgloc; ?>radio_on4.gif','<?php echo $imgloc; ?>radio_off4.gif')" src="<?php echo $imgloc; ?>radio_off4.gif" id="level3"> Easy
								</div>
			
								<P>Games Played <span id="plays">0</span><br>
								Games Won <span id="wins">0</span><br>
								Success Rate <span id="rate">0</span><br><br>
								<table class = "tableplain">
									<tr>
										<td>
											<div id="showhint" onclick="showHint()" onmouseover="this.className='buttonover'" onmouseout="this.className='buttonout'" onmousedown="this.className='buttondown'" onmouseup="this.className='buttonup'" class="buttonout" align = "center">Show Family</div>
										</td>
										<td>
											&nbsp;&nbsp;
										</td>
										<td>
											<div id="newgame" onclick="newGame()" onmouseover="this.className='buttonover'" onmouseout="this.className='buttonout'" onmousedown="this.className='buttondown'" onmouseup="this.className='buttonup'" class="buttonout" align = "center">New Game</div>
										</td>
									</tr>
								</table>
								<div style="clear:both"></div><br>
							</td>
						</tr>
						<tr>
							<th colspan = "2">
								<Center>
									<div id="hintdisplay" style="text-align:center; font-size:20">&nbsp;</div>
								</center>
			
							</th>
						</tr>
						<tr>
							<td colspan="2" align="center">
								<div id="attempt" style="letter-spacing:5px;font-weight:bold;font-size:20px">&nbsp;</div><br>
								<div id="splash" style="position:static; left:100; top:-400; visibility:hidden; width:400px; color:#336699"></div><br>
								<table id="charactertable" class = "lettertable" border="0" width="450">
									<tr align="center" height = '40' valign = "middle">
										<td><span id="a" style="display:block">A</span></td>
										<td><span id="b" style="display:block">B</span></td>
										<td><span id="c" style="display:block">C</span></td>
										<td><span id="d" style="display:block">D</span></td>
										<td><span id="e" style="display:block">E</span></td>
										<td><span id="f" style="display:block">F</span></td>
										<td><span id="g" style="display:block">G</span></td>
										<td><span id="h" style="display:block">H</span></td>
										<td><span id="i" style="display:block">I</span></td>
									</tr>
									<tr align="center" height = '40'>
										<td><span id="j" style="display:block">J</span></td>
										<td><span id="k" style="display:block">K</span></td>
										<td><span id="l" style="display:block">L</span></td>
										<td><span id="m" style="display:block">M</span></td>
										<td><span id="n" style="display:block">N</span></td>
										<td><span id="o" style="display:block">O</span></td>
										<td><span id="p" style="display:block">P</span></td>
										<td><span id="q" style="display:block">Q</span></td>
										<td><span id="r" style="display:block">R</span></td>
									</tr>
									<tr align="center" height = '40'>
										<td><span id="s" style="display:block">S</span></td>
										<td><span id="t" style="display:block">T</span></td>
										<td><span id="u" style="display:block">U</span></td>
										<td><span id="v" style="display:block">V</span></td>
										<td><span id="w" style="display:block">W</span></td>
										<td><span id="x" style="display:block">X</span></td>
										<td><span id="y" style="display:block">Y</span></td>
										<td><span id="z" style="display:block">Z</span></td>
										<!--<td valign="top"><span id="," class="comma">,</span></td>
										<td valign="center"><span id="." class="dot">.</span></td>
										<td valign="center"><!--<span id="-" class="dash">-</span></td>-->
										<td valign="center">
											<span id="?" style="display:block" class="question"  title="Wild Card">?</span>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan = "2" class = "tableplain">
								<center>
									<!------------------------------GIVES INSTRUCTIONS--------------------------------------------------->
									<table class = "tableplain">
										<tr>
											<td>
												<br><P>
												<ul>
													<li>Type or click on a letter to guess
													<li>Difficulty level affects how many chances you get: 3, 6, or 12
													<br><br>Tips:
													<ul>
														<li>Showing the family uses one of your chances
														<li>Using the wild card [?] uses one of your chances
														<li>Spaces are already displayed for you
														<li>You cannot change settings settings while in the middle of a game
														<li>The "Show Family"/wild card cannot be used if you are down to your last guess
													</ul>
												</ul>
												<!--<center><a href = "/seinet/potd"><b><u>Click here for the Plant of the Day!</u></b></a></center>-->
												<!--
												<P>Info:<br>
												Showing the family uses one of your chances<br>
												Using the wild card (?) uses one of your chances<br>
												Spaces are already displayed for you.<br>
												You cannot change settings settings while in the middle of a game<br>
												The "Show Family"/wild card cannot be used if you are down to your last guess<br>
												<a href = "javascript:picpopup()">show picture</a><br>-->
											</td>
										</tr>
										<tr>
											<td height = "80" valign = "bottom">
												<center>
													<!--<a href = "/demo" class = "normlink"><font color = "#0000FF" size = 3><u><b>Click here for the plant of the day!</b></u></font></a>-->
												</center>
											</td>
										</tr>
									</table>
									<!--------------------------------------------------------------------------------------------------->
								</center>
							</td>
						</tr>
					</table>
				</td></tr>
			</table>
		</center>
		<center>
			<br>
			<!--<a href = "/demo" class = "normlink"><font color = "#0000FF" size = 3><u><b>Click here for the plant of the day!</b></u></font></a>-->
		</center>
		<div id="blanket" style="display:none;"></div>
		<div id="popUpDiv" style="display:none;">
			<table border = "0" class = "tableplain" cellspacing = "0" cellpadding = "0" width = "300">
				<tr>
					<td valign = "top" bgcolor = "#555555">
						<table border = "0" class = "tableplain"><tr><td width = "10"></td><td width = "280"><center>
						<font color = "#FFFFFF" size = 4>
						<u><b>Loading...</b></u></center></td><td width = "10">
						<div align = right>
							<font size = "5">
							<a href="#" onclick="popup('popUpDiv')">X</a>
							</font>
						</div>
						</td></tr></table>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<table border = "0" cellpadding = "8" cellspacing = "4" class = "tableplain">
								<tr>
									<td>
										<div align = "center">
											<font size = "4">Please wait while we assemble your plant list</font>
										</div>
									</td>
								</tr>
							</table>
						</center>
					</td>
				</tr>
			</table>
		</div>
		
		<div id="listreload" style="display:none;">
			<table border = "0" class = "tableplain" cellspacing = "0" cellpadding = "0" width = "300">
				<tr>
					<td valign = "top" bgcolor = "#555555">
						<table border = "0" class = "tableplain"><tr><td width = "10"></td><td width = "280"><center>
						<font color = "#FFFFFF" size = 4>
						<u><b>Loading...</b></u></center></td><td width = "10">
						<div align = right>
							<font size = "5">
							<a href="#" onclick="popup('popUpDiv')">X</a>
							</font>
						</div>
						</td></tr></table>
					</td>
				</tr>
				<tr>
					<td>
						<center>
							<table border = "0" cellpadding = "8" cellspacing = "4" class = "tableplain">
								<tr>
									<td>
										<div align = "center">
											<font size = "4">Just a moment while we get more plants from that list</font>
										</div>
									</td>
								</tr>
							</table>
						</center>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<!-- This ends inner text! -->
	<?php
	include('../footer.php');
	?>
<!--
<div id="blanket" style="display:none;"></div>
	<div id="popUpDiv" style="display:none;">

		<a href="#" onclick="popup('popUpDiv')" class = "plainlink">Click Me To Close</a>
	</div>	
  <h1><a href="#" onclick="popup('popUpDiv')" class = "plainlink">Click Here To Open The Pop Up</a></h1>
  -->
</body>
</html>
