<?php
echo json_encode(array('catalognumber' => 'ASU012345','decimalLatitude' => '32.123456','decimalLongitude' => '-112.123456','coordinateuncertaintyinmeters' => '1234'));

?>
<form action="occurrencewriter.php" method="get" target="_blank">
    
	<b>Key:</b> <input type="text" name="key" value="" style="width:300px" /><br/>

	<b>occid:</b> <input type="text" name="occid" value="" style="width:300px" /><br/>
	<b>recordID:</b> <input type="text" name="recordid" value="" /><br/>
	<b>dwcObj:</b> <input type="text" name="dwcobj" value="" style="width:1000px;" /><br/>
	<b>source:</b> <input type="text" name="source" value="" /><br/>
	<b>editor:</b> <input type="text" name="editor" value="" /><br/>
	<b>timestamp:</b> <input type="text" name="timestamp" value="" /><br/>

	<input type="submit" value="Submit Data" />
</form>
