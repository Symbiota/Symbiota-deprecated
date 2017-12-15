<?php
class OccurrenceSearchSupport {

	private $conn;
	private $collidStr = '';
	private $collArrIndex = 0;

	public function __construct($conn){
		$this->conn = $conn;
 	}

	public function __destruct(){
	}

	public function getFullCollectionList($catId = ''){
		if($catId && !is_numeric($catId)) $catId = '';
		//Set collection array
		$collIdArr = array();
		$catIdArr = array();
		if($this->collidStr){
			$cArr = explode(';',$this->collidStr);
			$collIdArr = explode(',',$cArr[0]);
			if(isset($cArr[1])) $catIdStr = $cArr[1];
		}
		//Set collections
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, c.colltype, ccl.ccpk, '.
			'cat.category, cat.icon AS caticon, cat.acronym '.
			'FROM omcollections c INNER JOIN omcollectionstats s ON c.collid = s.collid '.
			'LEFT JOIN omcollcatlink ccl ON c.collid = ccl.collid '.
			'LEFT JOIN omcollcategories cat ON ccl.ccpk = cat.ccpk '.
			'WHERE s.recordcnt > 0 AND (cat.inclusive IS NULL OR cat.inclusive = 1 OR cat.ccpk = 1) '.
			'ORDER BY ccl.sortsequence, cat.category, c.sortseq, c.CollectionName ';
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$collArr = array();
		while($r = $result->fetch_object()){
			$collType = (stripos($r->colltype, "observation") !== false?'obs':'spec');
			if($r->ccpk){
				if(!isset($collArr[$collType]['cat'][$r->ccpk]['name'])){
					$collArr[$collType]['cat'][$r->ccpk]['name'] = $r->category;
					$collArr[$collType]['cat'][$r->ccpk]['icon'] = $r->caticon;
					$collArr[$collType]['cat'][$r->ccpk]['acronym'] = $r->acronym;
					//if(in_array($r->ccpk,$catIdArr)) $retArr[$collType]['cat'][$catId]['isselected'] = 1;
				}
				$collArr[$collType]['cat'][$r->ccpk][$r->collid]["instcode"] = $r->institutioncode;
				$collArr[$collType]['cat'][$r->ccpk][$r->collid]["collcode"] = $r->collectioncode;
				$collArr[$collType]['cat'][$r->ccpk][$r->collid]["collname"] = $r->collectionname;
				$collArr[$collType]['cat'][$r->ccpk][$r->collid]["icon"] = $r->icon;
			}
			else{
				$collArr[$collType]['coll'][$r->collid]["instcode"] = $r->institutioncode;
				$collArr[$collType]['coll'][$r->collid]["collcode"] = $r->collectioncode;
				$collArr[$collType]['coll'][$r->collid]["collname"] = $r->collectionname;
				$collArr[$collType]['coll'][$r->collid]["icon"] = $r->icon;
			}
		}
		$result->free();

		$retArr = array();
		//Modify sort so that default catid is first
		if(isset($collArr['spec']['cat'][$catId])){
			$retArr['spec']['cat'][$catId] = $collArr['spec']['cat'][$catId];
			unset($collArr['spec']['cat'][$catId]);
		}
		elseif(isset($collArr['obs']['cat'][$catId])){
			$retArr['obs']['cat'][$catId] = $collArr['obs']['cat'][$catId];
			unset($collArr['obs']['cat'][$catId]);
		}
		foreach($collArr as $t => $tArr){
			foreach($tArr as $g => $gArr){
				foreach($gArr as $id => $idArr){
					$retArr[$t][$g][$id] = $idArr;
				}
			}
		}
		return $retArr;
	}

	public function outputFullCollArr($occArr, $targetCatID = 0){
		global $DEFAULTCATID, $LANG;
		if(!$targetCatID && $DEFAULTCATID) $targetCatID = $DEFAULTCATID;
		$buttonStr = '<button type="submit" class="ui-button ui-widget ui-corner-all">'.(isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next &gt;').'</button>';
		//$buttonStr = '<input type="submit" class="nextbtn searchcollnextbtn" value="'.(isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >').'" />';
		$collCnt = 0;
		echo '<div style="position:relative">';
		if(isset($occArr['cat'])){
			$categoryArr = $occArr['cat'];
			?>
			<div style="float:right;margin-top:20px;">
				<?php echo $buttonStr; ?>
			</div>
			<table style="float:left;width:80%;">
				<?php
				$cnt = 0;
				foreach($categoryArr as $catid => $catArr){
					$name = $catArr['name'];
					if($catArr['acronym']) $name .= ' ('.$catArr['acronym'].')';
					$catIcon = $catArr['icon'];
					unset($catArr['name']);
					unset($catArr['acronym']);
					unset($catArr['icon']);
					$idStr = $this->collArrIndex.'-'.$catid;
					?>
					<tr>
						<td style="<?php echo ($catIcon?'width:40px':''); ?>">
							<?php
							if($catIcon){
								$catIcon = (substr($catIcon,0,6)=='images'?'../':'').$catIcon;
								echo '<img src="'.$catIcon.'" style="border:0px;width:30px;height:30px;" />';
							}
							?>
						</td>
						<td style="padding:6px;width:25px;">
							<input id="cat-<?php echo $idStr; ?>-Input" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" checked />
						</td>
						<td style="padding:9px 5px;width:10px;">
							<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
								<img id="plus-<?php echo $idStr; ?>" src="../images/plus_sm.png" style="<?php echo ($targetCatID != $catid?'':'display:none;') ?>" /><img id="minus-<?php echo $idStr; ?>" src="../images/minus_sm.png" style="<?php echo ($targetCatID != $catid?'display:none;':'') ?>" />
							</a>
						</td>
						<td style="padding-top:8px;">
							<div class="categorytitle">
								<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
									<?php echo $name; ?>
								</a>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="4">
							<div id="cat-<?php echo $idStr; ?>" style="<?php echo ($targetCatID && $targetCatID != $catid?'display:none;':'') ?>margin:10px;padding:10px 20px;border:inset">
								<table>
									<?php
									foreach($catArr as $collid => $collName2){
										?>
										<tr>
											<td style="width:40px;">
												<?php
												if($collName2["icon"]){
													$cIcon = (substr($collName2["icon"],0,6)=='images'?'../':'').$collName2["icon"];
													?>
													<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'><img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" /></a>
													<?php
												}
												?>
											</td>
											<td style="padding:6px;width:25px;">
												<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" class="cat-<?php echo $idStr; ?>" onclick="unselectCat('cat-<?php echo $idStr; ?>-Input')" checked />
											</td>
											<td style="padding:6px">
												<div class="collectiontitle">
													<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'>
														<?php
														$codeStr = ' ('.$collName2['instcode'];
														if($collName2['collcode']) $codeStr .= '-'.$collName2['collcode'];
														$codeStr .= ')';
														echo $collName2["collname"].$codeStr;
														?>
													</a>
													<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
														more info
													</a>
												</div>
											</td>
										</tr>
										<?php
										$collCnt++;
									}
									?>
								</table>
							</div>
						</td>
					</tr>
					<?php
					$cnt++;
				}
				?>
			</table>
			<?php
		}
		if(isset($occArr['coll'])){
			$collArr = $occArr['coll'];
			?>
			<table style="float:left;width:80%;">
				<?php
				foreach($collArr as $collid => $cArr){
					?>
					<tr>
						<td style="<?php ($cArr["icon"]?'width:35px':''); ?>">
							<?php
							if($cArr["icon"]){
								$cIcon = (substr($cArr["icon"],0,6)=='images'?'../':'').$cArr["icon"];
								?>
								<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'><img src="<?php echo $cIcon; ?>" style="border:0px;width:30px;height:30px;" /></a>
								<?php
							}
							?>
							&nbsp;
						</td>
						<td style="padding:6px;width:25px;">
							<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="uncheckAll()" checked />
						</td>
						<td style="padding:6px">
							<div class="collectiontitle">
								<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>'>
									<?php
									$codeStr = ' ('.$cArr['instcode'];
									if($cArr['collcode']) $codeStr .= '-'.$cArr['collcode'];
									$codeStr .= ')';
									echo $cArr["collname"].$codeStr;
									?>
								</a>
								<a href = 'misc/collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
									more info
								</a>
							</div>
						</td>
					</tr>
					<?php
					$collCnt++;
				}
				?>
			</table>
			<?php
			if(!isset($occArr['cat'])){
				?>
				<div style="float:right;position:absolute;top:<?php echo count($collArr)*5; ?>px;right:0px;">
					<?php echo $buttonStr; ?>
				</div>
				<?php
			}
			if(count($collArr) > 40){
				?>
				<div style="float:right;position:absolute;top:<?php echo count($collArr)*15; ?>px;right:0px;">
					<?php echo $buttonStr; ?>
				</div>
				<?php
			}
		}
		echo '</div>';
		$this->collArrIndex++;
	}

	public function setCollidStr($str){
		$this->collidStr = $str;
	}
}
?>