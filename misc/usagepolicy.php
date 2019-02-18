<?php
//error_reporting(E_ALL);
 include_once('../config/symbini.php');
 include_once($SERVER_ROOT.'/content/lang/misc/usagepolicy.'.$LANG_TAG.'.php');

 header("Content-Type: text/html; charset=".$charset);

?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> Data Usage Guidelines</title>
		<link href="../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
		<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />

    <!--inicio favicon -->
	<link rel="shortcut icon" href="../images/favicon.png" type="image/x-icon">

  </head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1><?php echo $LANG['GUIDELINES'];?></h1><br />

			<h2><?php echo $LANG['CIT_FORMATS'];?></h2>
			<div style="margin:10px">
				<?php echo $LANG['USE'];?> <?php echo $defaultTitle; ?> <?php echo $LANG['NET'];?>
				<div style="font-weight:bold;margin-top:10px;">
					<?php echo $LANG['CIT_GEN'];?>
				</div>
				<div style="margin:10px;">
					<?php
					echo $defaultTitle.'. '.date('Y').'. ';
					echo 'http//:'.$_SERVER['HTTP_HOST'].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'index.php. ';
					echo 'Accessed on '.date('F d').'. ';
					?>
				</div>

				<div style="font-weight:bold;margin-top:10px;">
					<?php echo $LANG['DATA_OCC'];?>
				</div>
				<div style="margin:10px;">
					<?php echo $LANG['BIO_OCC'];?> &lt;List of Collections&gt;
					(Accessed through <?php echo $defaultTitle; ?> Data Portal,
					<?php echo 'http//:'.$_SERVER['HTTP_HOST'].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'index.php'; ?>, YYYY-MM-DD)<br/><br/>
					<b><?php echo $LANG['EXAMPLE'];?></b><br/>
					<?php echo $LANG['BIO_OCC'];?>
					Field Museum of Natural History, Museum of Vertebrate Zoology, and New York Botanical Garden
					(Accessed through <?php echo $defaultTitle; ?> Data Portal,
					<?php echo 'http//:'.$_SERVER['HTTP_HOST'].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'index.php, '.date('Y-m-d').')'; ?>
				</div>
			</div>
			<div>
			</div>

			<a name="occurrences"></a>
			<h2><?php echo $LANG['OCC_POLY'];?></h2>
		    <div style="margin:10px;">
				<ul>
					<li>
						<?php echo $LANG['WHILE'];?> <?php echo $defaultTitle; ?> <?php echo $LANG['POSS_CONTROL'];?>
					</li>
					<li>
						<?php echo $defaultTitle; ?> <?php echo $LANG['RESP_DAMAGE'];?>
					</li>
					<li>
						<?php echo $LANG['MATTER'];?>
					</li>
					<li>
						<?php echo $defaultTitle; ?> <?php echo $LANG['SOL_USER'];?>
					</li>
				</ul>
		    </div>

			<a name="images"></a>
			<h2><?php echo $LANG['IMAGES'];?></h2>
		    <div style="margin:15px;">
		    	<?php echo $LANG['LEGGEND1'];?><a href="http://creativecommons.org/licenses/by-sa/3.0/">CC BY-SA</a><?php echo $LANG['LEGGEND2'];?>
		    </div>

			<h2><?php echo $LANG['NOT_REG'];?></h2>
		    <div style="margin:15px;">
				<?php echo $LANG['LEGGEND3'];?>
			</div>
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
