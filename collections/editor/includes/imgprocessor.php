<div style="width:100%;height:950px;">
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
		?>
		<div id="rawtextdiv">
			<div id="tfdiv-add" class="tfdiv" style="width:400px;height:325px;display:none;">
				<?php
				foreach($specImgArr as $iId => $iArr ){ 
					?>
					<form id="imgaddform-<?php echo $iId; ?>" name="imgaddform-<?php echo $iId; ?>" method="post" action="occurrenceeditor.php">
						<textarea name="rawtext"></textarea>
						<input type="hidden" name="imgid" value="<?php echo $iId; ?>" /><br/>
						<input name="formsubmit" type="submit" value="Save Text Fragment" /> 
					</form>
					<?php
				} 
				?>
			</div>
			<?php 
			$imgCnt = 0;
			foreach($fragArr as $imgId => $labelArr){
				?>
				<div id="tfdiv-<?php echo $imgId; ?>" class="tfdiv" style="width:400px;height:325px;display:<?php echo ($imgCnt?'none':'block'); ?>">
					<?php
					$fragCnt = 0;
					foreach($labelArr as $prlid => $rStr){ 
						?>
						<div id="imgeditdiv-<?php echo $fragCnt; ?>" style="display:<?php echo ($fragCnt?'none':'block'); ?>">
							<form name="imgeditform-<?php echo $fragCnt; ?>" method="post" action="occurrenceeditor.php">
								<textarea name="rawtext" rows="20" cols="45" onchange="this.form.formsubmit.disabled = false;" ><?php echo $rStr; ?></textarea>
								<input type="hidden" name="prlid" value="<?php echo $prlid; ?>" /><br/>
								<input name="formsubmit" type="submit" value="Save Text Fragment" disabled="disabled" />
							</form>
						</div>
						<?php
						$fragCnt++;
					}
					?>
					<div style="width:100%;text-align:right;font-weight:bold;">
						<span id="tfindex-<?php echo $imgId; ?>">1</span> of <?php echo $fragCnt; ?>
						<?php 
						if($fragCnt > 1){
							?>
							<a href="#" onclick="nextRawText(<?php echo $imgId; ?>);return false;">=&gt;&gt;</a>
							<?php 
						}
						?>
					</div>
				</div>
				<?php 
				$imgCnt++;
			}
			?>
		</div>
	</fieldset>
</div>
