<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLabelManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = $_REQUEST["collid"];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$labelManager = new SpecLabelManager();
$labelManager->setCollId($collId);

$isEditor = 0;
$occArr = array();
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
	if($isEditor){
		if($action){
			$occArr = $labelManager->getLabelArr();
		}
	}
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> Default Labels</title>
	    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	</head>
	<body>
		<div>
			<?php 
			if($occArr){
				foreach($occArr as $occId => $recArr){
					?>
					<div style="float:right;width:400px;">
						
					
					</div>
					<?php 
				}
			}
			
			?>
		</div>
	</body>
</html>