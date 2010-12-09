<?php 
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Image Usage policy</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
</head>
<body>

	<?php
	$displayLeftMenu = (isset($imagelib_indexMenu)?$imagelib_indexMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_indexCrumbs;
		echo " <b>Image Library</b>";
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
	   	<h1>Image Usage policy</h1>
	    <div style="margin:30px;">
	    	<p>Images within this website have been generously contributed by their owners to 
	    	promote education and research. These contributors retain the full copyright for 
	    	their images. Unless stated otherwise, images are made available under the “Fair Use” 
	    	provision of the U.S. Copyright Law (
	    	<a href="http://lcweb.loc.gov/copyright">http://www.copyright.gov/</a>). They may be used 
	    	only for personal or educational use and are NOT available for commercial use unless 
	    	permission is first obtained from the copyright holder. If any image is used in a 
	    	non-commercial publication, report, or as a web link, one must credit the photographer 
	    	as well as the name of the website hosting the image. If you have any doubt or 
	    	questions regarding the use of an image, contact the author or the site manager.</p>
	    </div>
		<h1>Notes on Specimen Images</h1> 
	    <div style="margin:30px;">
			Specimens are used for scientific research and because of skilled preparation and 
			careful use they may last for hundreds of years. Some collections have specimens 
			that were collected over 100 years ago that are no longer occur within the area. 
			By making these specimens available on the web as images, their availability and 
			value improves without an increase in inadvertent damage caused by use. Note that 
			if you are considering making specimens, remember collecting normally requires 
			permission of the landowner and, in the case of rare and endangered plants, 
			additional permits may be required. It is best to coordinate such efforts with a 
			local institution that manages a local collection.
		</div> 
	    <div style="margin:30px;">
	    	Return to <a href="index.php">Image Library</a>
	    </div>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>
