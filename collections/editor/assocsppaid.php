<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
 
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Associated Species Entry Aid</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">

		$(document).ready(function() {
			$("#taxonname").autocomplete({ source: "rpc/getassocspp.php" },
			{ minLength: 4, autoFocus: true, delay: 200 });

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

<body style="background-color:white">
	<!-- This is inner text! -->
	<div id="innertext" style="background-color:white;">
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

