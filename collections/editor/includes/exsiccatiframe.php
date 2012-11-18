<div id="exsiccatidiv" style="clear:both;padding:10px;margin:5px;border:1px solid gray;display:none;">
	<span>
		Exsiccati Title:
		<input id="ffexstitle" name="exsiccatititle" type="text" tabindex="16" maxlength="255" style="width:400px;" value="<?php echo array_key_exists('exsiccatititle',$occArr)?$occArr['exsiccatititle']:''; ?>" onchange="fieldChanged('exsiccatititle')" />
	</span>
	<span style="margin-left:10px;">
		Number:
		<input name="exsiccatinumber" type="text" tabindex="17" style="width:45px;" value="<?php echo array_key_exists('exsiccatinumber',$occArr)?$occArr['exsiccatinumber']:''; ?>" onchange="fieldChanged('exsiccatinumber');" />
	</span>
	<span style="margin-left:5px;cursor:pointer;">
		<input type="button" value="Dupes?" tabindex="18" onclick="lookForExsDupes(this.form);" />
	</span>
</div>


