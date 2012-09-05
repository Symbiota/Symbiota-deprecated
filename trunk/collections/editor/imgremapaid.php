<?php
 //error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);
 
$targetId = $_REQUEST["targetid"];
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0; 
$identifier = array_key_exists("identifier",$_REQUEST)?$_REQUEST["identifier"]:""; 
$collector = array_key_exists("collector",$_REQUEST)?$_REQUEST["collector"]:""; 
$collNumber = array_key_exists("collnum",$_REQUEST)?$_REQUEST["collnum"]:""; 
 
$occManager = new OccurrenceEditorImages();
 
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Search Page</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<script type="text/javascript">
	    function updateParentForm(occId) {
	        opener.document.getElementById("<?php echo $targetId;?>").value = occId;
	        self.close();
	        return false;
	    }

	</script>
</head>

<body>
	<!-- This is inner text! -->
	<div id="innertext">
		<form name="occform" action="imgremapaid.php" method="post" >
			<fieldset style="width:450px;">
				<legend><b>Voucher Search Pane</b></legend>
				<div style="clear:both;padding:2px;">
					<div style="float:left;width:130px;">Catalog #:</div>
					<div style="float:left;"><input name="identifier" type="text" /></div>
				</div>
				<div style="clear:both;padding:2px;">
					<div style="float:left;width:130px;">Collector Last Name:</div>
					<div style="float:left;"><input name="collector" type="text" /></div>
				</div>
				<div style="clear:both;padding:2px;">
					<div style="float:left;width:130px;">Collector Number:</div>
					<div style="float:left;"><input name="collnum" type="text" /></div>
				</div>
				<div style="clear:both;padding:2px;">
					<input name="action" type="submit" value="Search Occurrences" />
					<input type="hidden" name="targetid" value="<?php echo $targetId;?>" />
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
				</div>
			</fieldset>
		</form>
		<?php 
			if($identifier || $collector || $collNumber){
				$occArr = $occManager->getOccurrenceList($collId, $identifier, $collector, $collNumber);
				if($occArr){
					foreach($occArr as $occId => $vArr){
						?>
						<div style="margin:10px;">
							<?php 
							echo '<b>OccId <a href="../individual/index.php?occid='.$occId.'">'.$occId.'</a>:</b> <i>'.$vArr["sciname"].'</i>; ';
							echo $vArr['recordedby'].' ['.($vArr["recordnumber"]?$vArr["recordnumber"]:"s.n.")."]; ".$vArr["locality"];
							?>
							<div style="margin-left:10px;cursor:pointer;color:blue;" onclick="updateParentForm('<?php echo $occId;?>')">
								Select Occurrence Record
							</div>
						</div>
						<hr />
						<?php 
					}
				}
				else{
					?>
					<div style="margin:10px;">
						No records were returned. Please modify your search and try again. 
					</div>
					<?php 
				}
			}
			else{
				?>
				<div style="margin:10px;font-weight:bold;">
					Use fields above to query and select the occurrence record to which you wish to link the image. 
				</div>
				<?php 
			}
			?>
	</div>
</body>
</html> 

