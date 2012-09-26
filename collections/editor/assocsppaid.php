<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
 
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Associated Species Entry Aid</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">

		$(document).ready(function() {
			$("#taxonname").autocomplete({ source: "rpc/getassocspp.php" },
			{ minLength: 6, autoFocus: true, delay: 200 });

			$("#taxonname").focus();
		});

		function addName(){
		    var nameElem = document.getElementById("taxonname");
		    if(nameElem.value){
		    	var asStr = opener.document.fullform.associatedtaxa.value;
		    	if(asStr) asStr = asStr + ", ";  
		    	opener.document.fullform.associatedtaxa.value = asStr + nameElem.value;
		    	nameElem.value = "";
		    	nameElem.focus();
		    }
	    }

	</script>
</head>

<body>
	<!-- This is inner text! -->
	<div id="innertext">
		<fieldset style="width:450px;">
			<legend><b>Associated Species Entry Aid</b></legend>
			<div style="">
				Taxon: 
				<input id="taxonname" type="text" style="width:350px;" /><br/>
				<input id="transbutton" type="button" value="Add Name" onclick="addName();" />
			</div>
		</fieldset>
	</div>
</body>
</html> 

