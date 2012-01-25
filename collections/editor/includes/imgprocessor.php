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
				<img id="activeimage" src="<?php echo (substr($imgArr[0],0,4)=='http'?'':$imgUrlPrefix).$imgArr[0]; ?>" style="width:400px;height:400px;" />
			</div>
			<div style="width:100%;">
				<input type="button" name="ocrsubmit" value="OCR Image" onclick="ocrImage()" />
				<?php 
				if(count($imgArr)>1){
					?>
					<script type="text/javascript"> 
						var activeImageArr = new Array("<?php echo implode('","',$imgArr); ?>");
						var activeImageIndex = 0; 
						var imagePrefix = "<?php echo $imgUrlPrefix; ?>";
						function nextLabelProcessingImage(){
							activeImageIndex++;
							if(activeImageIndex >= activeImageArr.length){
								activeImageIndex = 0;
							}
							var activeImageSrc = activeImageArr[activeImageIndex];
							if(activeImageSrc.substring(0,4)!="http") activeImageSrc = imagePrefix + activeImageSrc;
							document.getElementById("activeimage").src = activeImageSrc;
							document.getElementById("imageindex").innerHTML = activeImageIndex + 1;
						}
					</script>
					<?php 
				}
				?>
				<span style="float:right;margin-right:20px;font-weight:bold;">
					Image <span id="imageindex">1</span> of  
					<?php 
					echo count($imgArr);  
					if(count($imgArr) > 1) echo '<a href="#" onclick="nextLabelProcessingImage(); return false;">=&gt;&gt;</a>';
					?>
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
					?>
					<div id="txtfrag<?php echo $fragCnt; ?>" style="<?php echo ($fragCnt?'display:none':''); ?>">
						<textarea id="txtfrag" name="rawtext-<?php echo $prlid; ?>" style="width:400px;height:325px;">
							<?php echo $rawStr; ?>
						</textarea>
					</div>
					<?php 
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
						<a href="#" onclick="nextRawText();return false;">=&gt;&gt;</a>
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
