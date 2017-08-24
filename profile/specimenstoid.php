<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$userId = $_REQUEST['userid'];
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

//Sanitation
if(!is_numeric($userId)) $userId = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$profileHandler = new ProfileManager();
$profileHandler->setUid($SYMB_UID);

?>
<div style="margin:10px;">
	<?php 
	if($SYMB_UID){
		if($action == 'showmissingids'){
			$profileHandler->echoSpecimensLackingIdent();
		}
		else{
			$profileHandler->echoSpecimensPendingIdent();
		}
	}
	?>	
	<div style="margin:25px 15px;">
		<?php 
		if($userId){
			if($action == 'showmissingids'){
				echo '<a href="viewprofile.php?tabindex=2&userid='.$userId.'"><b>Display Specimens within your Taxonomic Scope</b></a>';
			}
			else{
				echo '<a href="viewprofile.php?action=showmissingids&tabindex=2&userid='.$userId.'"><b>Order Level or Above (open to all identification editors)</b></a>';
			}
		}
		?>
	</div>
</div>