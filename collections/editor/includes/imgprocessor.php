<div style="width:100%;height:950px;">
	<fieldset style="height:95%">
		<legend><b>Label Processing</b></legend>
		<div id="labelprocessingdiv">
		<?php
		$imgCnt = 1;
		foreach($imgArr as $imgId => $iUrl){
			?>
			<div id="labeldiv-<?php echo $imgCnt; ?>" style="display:<?php echo ($imgCnt==1?'block':'none'); ?>;">
				<div>
					<img id="activeimg-<?php echo $imgCnt; ?>" src="<?php echo $iUrl; ?>" style="width:400px;height:400px;" />
				</div>
				<div style="width:100%;">
					<input type="button" value="OCR Image" onclick="ocrImage(this,<?php echo $imgCnt; ?>);" />
					<img id="workingcircle-<?php echo $imgCnt; ?>" src="../../images/workingcircle.gif" style="display:none;" />
					<span style="float:right;margin-right:20px;font-weight:bold;">
						Image <?php echo $imgCnt; ?> of 
						<?php 
						echo count($imgArr);
						if(count($imgArr)>1){
							echo '<a href="#" onclick="return nextLabelProcessingImage('.($imgCnt+1).');">=&gt;&gt;</a>';
						}
						?>
					</span>
				</div>
				<div style="width:100%">
					<div id="tfadddiv-<?php echo $imgCnt; ?>" style="display:none;">
						<form id="imgaddform-<?php echo $imgCnt; ?>" name="imgaddform-<?php echo $imgId; ?>" method="post" action="occurrenceeditor.php">
							<textarea name="rawtext" rows="20" cols="48" style="width:100%"></textarea>
							<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" /><br/>
							<!-- 
							<input name="formsubmit" type="submit" value="Save Text Fragment" />
							-->
						</form>
					</div>
					<div id="tfeditdiv-<?php echo $imgCnt; ?>">
						<?php
						if(array_key_exists($imgId,$fragArr)){ 
							$fArr = $fragArr[$imgId];
							$fragCnt = 1;
							foreach($fArr as $prlid => $rStr){
								?>
								<div id="tfdiv-<?php echo $imgCnt.'-'.$fragCnt; ?>" style="display:<?php echo ($fragCnt==1?'block':'none'); ?>">
									<form name="imgeditform-<?php echo $prlid; ?>" method="post" action="occurrenceeditor.php">
										<div>
											<textarea name="rawtext" rows="20" cols="48" style="width:100%"><?php echo $rStr; ?></textarea>
										</div>
										<div style="float:left;">
											<input type="hidden" name="prlid" value="<?php echo $prlid; ?>" />
											<!-- 
											<input name="formsubmit" type="submit" value="Save Text Fragment" />
											-->
										</div>
										<div style="float:right;font-weight:bold;margin-right:20px;">
											<?php 
											echo $fragCnt.' of '.count($fArr); 
											if(count($fArr) > 1){
												?>
												<a href="#" onclick="return nextRawText(<?php echo $imgCnt.','.($fragCnt+1); ?>)">=&gt;&gt;</a>
												<?php
											} 
											?>
										</div>
									</form>
								</div>
								<?php
								$fragCnt++;
							}
						}
						?>
					</div>
				</div>
			</div>
			<?php
			$imgCnt++;
		}
		?>
		</div>
	</fieldset>
</div>
