<div style="width:100%;height:825px;">
	<fieldset style="height:95%">
		<legend><b>Label Processing</b></legend>
		<?php
		if($specImgArr){
			$imgArr = array();
			foreach($specImgArr as $i2){
				$imgArr[] = ($i2['origurl']?$i2['origurl']:$i2['url']);
			}
			$imgUrlPrefix = (isset($imageDomain)?$imageDomain:'');
			?>
			<div id="labelimagediv">
				<img id="activeimage" src="<?php echo $imgUrlPrefix.$imgArr[0]; ?>" />
			</div>
			<div style="width:100%;">
			<!-- 	<input type="button" name="ocrsubmit" value="OCR Image" onclick="ocrImage()" />  -->
				<?php 
				if(count($imgArr)>1){
					?>
					<script type="text/javascript"> 
						var activeImageArr = new Array("<?php echo $imgUrlPrefix.implode('","'.$imgUrlPrefix,$imgArr); ?>");
						var activeImageIndex = 0; 
						function nextLabelProcessingImage(){
							activeImageIndex++;
							if(activeImageIndex >= activeImageArr.length){
								activeImageIndex = 0;
							}
							document.getElementById("activeimage").src = activeImageArr[activeImageIndex];
							document.getElementById("imageindex").innerHTML = activeImageIndex + 1;
						}
					</script>
					<?php 
				}
				?>
				<span style="margin-left:200px;font-weight:bold;">
					Image <span id="imageindex">1</span> 
					of <?php echo count($imgArr); ?> 
					<a href="#" onclick="nextLabelProcessingImage(); return false;">=&gt;&gt;</a>
				</span>
			</div>
			<?php
		}
		if($fragArr){
			?>
			<div id="rawtextdiv">
				<?php 
				$fragCnt = 0;
				foreach($fragArr as $prlid => $rawStr){
					echo '<div id="txtfrag'.$fragCnt.'" '.($fragCnt?'style="display:none"':'').'>'."\n";
					echo '<textarea name="rawtext-'.$prlid.'" style="width:400px;height:325px;">';
					echo $rawStr;
					echo '</textarea>'."\n";
					echo '</div>'."\n";
					$fragCnt++;
				}
				?>
				<div style="width:100%;text-align:right;font-weight:bold;">
					<span id="textfragindex">1</span> of <?php echo $fragCnt; ?>
					<?php 
					if($fragCnt > 1){
						?>
						<script type="text/javascript"> 
							var textFragIndex = 0;
							var totalFragCnt = <?php echo $fragCnt; ?>; 
							function nextRawText(){
								textFragIndex++;
								document.getElementById("txtfrag"+(textFragIndex-1)).style.display = "none";
								if(textFragIndex == totalFragCnt){
									textFragIndex = 0;
								}
								document.getElementById("txtfrag"+textFragIndex).style.display = "block";
								document.getElementById("textfragindex").innerHTML = textFragIndex + 1;
							}
						</script>
						<a href="#" onclick="nextRawText();return false;">=&gt;$gt;</a>
						<?php 
					}
					?>
				</div>
			</div>
			<?php
		}
		?>
	</fieldset>
</div>
