<?php
include_once('config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/help.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

?>

<html>
<head>
	<title>Ayuda - <?php echo $DEFAULT_TITLE;?></title>
	<link href="css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<!--inicio favicon -->
	<link rel="shortcut icon" href="images/favicon.png" type="image/x-icon">
</head>

<body class="body_index">
	<!-- Llama al header -->
	<?php include($SERVER_ROOT.'/header.php'); ?>

	<!-- Contenido -->
		<div id="container" class="container search">
			<div id="site-map">
				<center><h3><?php echo $LANG['BNDB'];?></h3></center>
					<p><b><?php echo $LANG['W_BNDB'];?></b></p>
					<p align="justify"><?php echo $LANG['W_LEGEND'];?> </br>

					<?php echo $LANG['LEGEND_BNDB'];?></br>
					<?php echo $LANG['S_BNDB'];?></br>

					<?php echo $LANG['ULTI'];?> <a target="blanck" href="http://symbiota.org/docs/symbiota-introduction/symbiota-help-pages/">symbiota.</a>
					</br>
					</p>

					<p><u><?php echo $LANG['B_DETAILS'];?></u></p>

					<p align="justify"><b><?php echo $LANG['P_REG'];?></b>
					<?php echo $LANG['DATA_USER'];?></p>
<p><a href="https://youtu.be/DUXj4v7pYUw"><?php echo $LANG['P_REG'];?></a></p>
<center><iframe width="700" height="500" src="https://www.youtube.com/embed/DUXj4v7pYUw?rel=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></center>
<p></p>

<p align="justify"><b><?php echo $LANG['VISIT_USER'];?></b> <?php echo $LANG['VISIT_LEGEND'];?></p>

<p><a href="https://youtu.be/et4Gq6r45jc"><?php echo $LANG['VISIT_USER'];?></a></p>

<center><iframe width="700" height="500" src="https://www.youtube.com/embed/et4Gq6r45jc?rel=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></center>
<p></p>

<p align="justify"><b><?php echo $LANG['USER_SADMIN'];?></b> <?php echo $LANG['SADMIN_LEGEND'];?></p>

<p><a href="https://youtu.be/8R_UazpUWsg"><?php echo $LANG['USER_SADMIN'];?></a></p>

<center><iframe width="700" height="500" src="https://www.youtube.com/embed/8R_UazpUWsg?rel=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></center>
<p></p>

<p align="justify"><b><?php echo $LANG['USER_TAX'];?></b> <?php echo $LANG['USER_TAX_LEGEND'];?></p>

<p><a href="https://youtu.be/EQ0mm64qdiE"><?php echo $LANG['USER_TAX'];?></a></p>

<center><iframe width="700" height="500" src="https://www.youtube.com/embed/EQ0mm64qdiE?rel=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></center>

<p></p>
<p><b><?php echo $LANG['UP_COL'];?></b></p>
<center><iframe src="https://www.youtube.com/embed/CvauneXHLbs?rel=0" width="700" height="500" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></center>
<p></p>
<center><a href="download/referenciaFormatosPlantilla.xlsx" download="referenciaFormatosPlantilla.xlsx">Descargar Formato Darwing Core</a></center>





<p><b><?php echo $LANG['DEP_TOOLS'];?></b></p>
<center><iframe width="700" height="500" src="https://www.youtube.com/embed/6M5gzO1p_jA?rel=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></center>



</div>
</div>
</body>
</html>
