<div style="width:100%;height:950px;">
	<fieldset style="height:95%">
		<legend><b>Label Processing</b></legend>
		<?php
		if($imgArr){
			?>
			<div id="labelimagediv">
				<img id="activeimage" src="<?php echo current($imgArr); ?>" style="width:400px;height:400px;" />
			</div>
			<div style="width:100%;">
				<input type="button" name="ocrsubmit" value="OCR Image" onclick="ocrImage()" />
				<span style="float:right;margin-right:20px;font-weight:bold;">
					Image <span id="imageindex">1</span> of  
					<?php 
					echo (count($imgArr));  
					if(count($imgArr)>1) echo '<a href="#" onclick="return nextLabelProcessingImage();">=&gt;&gt;</a>';
					?>
				</span>
			</div>
			<?php
		}
		?>
		<div id="rawtextdiv" style="width:400px;height:325px;">
			<div id="tfadddiv">
				<?php
				foreach($imgArr as $iId => $iArr ){ 
					?>
					<form id="imgaddform-<?php echo $iId; ?>" name="imgaddform-<?php echo $iId; ?>" method="post" action="occurrenceeditor.php" style="display:none;">
						<textarea name="rawtext" rows="20" cols="48"></textarea>
						<input type="hidden" name="imgid" value="<?php echo $iId; ?>" /><br/>
						<input name="formsubmit" type="submit" value="Save Text Fragment" />
					</form>
					<?php
				}
				?>
			</div>
			<div id="tfeditdiv">
				<?php 
				$imgCnt = 0;
				foreach($fragArr as $imgId => $labelArr){
					?>
					<div id="tfeditdiv-<?php echo $imgId; ?>" style="width:400px;height:325px;display:<?php echo ($imgCnt?'none':'block'); ?>">
						<?php
						$fragCnt = 0;
						$fragTotal = count($labelArr);
						$labelKeys = array_keys($labelArr);
						foreach($labelArr as $prlid => $rStr){ 
							?>
							<div id="tfdiv-<?php echo $prlid; ?>" style="display:<?php echo ($fragCnt?'none':'block'); ?>">
								<form name="imgeditform-<?php echo $prlid; ?>" method="post" action="occurrenceeditor.php">
									<div>
										<textarea name="rawtext" rows="20" cols="48"><?php echo $rStr; ?></textarea>
									</div>
									<div style="float:left;">
										<input type="hidden" name="prlid" value="<?php echo $prlid; ?>" />
										<input name="formsubmit" type="submit" value="Save Text Fragment" disabled="disabled" />
									</div>
									<div style="float:right;font-weight:bold;margin-right:20px;">
										<?php 
										echo ($fragCnt+1).' of '.$fragTotal; 
										if($fragTotal > 1){
											?>
											<a href="#" onclick="nextRawText(<?php echo $prlid.','.(($fragCnt+1)<$fragTotal?$labelKeys[$fragCnt+1]:$labelKeys[0]); ?>);return false;">=&gt;&gt;</a>
											<?php
										} 
										?>
									</div>
								</form>
							</div>
							<?php
							$fragCnt++;
						}
						?>
					</div>
					<?php 
					$imgCnt++;
				}
				?>
			</div>
		</div>
	</fieldset>
</div>
