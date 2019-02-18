<?php
include_once($SERVER_ROOT.'/content/lang/collections/misc/collprofiles.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');

$statDisplay = array_key_exists('stat',$_REQUEST)?$_REQUEST['stat']:'';

if($statDisplay == 'geography'){
	$countryDist = array_key_exists('country',$_REQUEST)?htmlspecialchars($_REQUEST['country']):'';
	$stateDist = array_key_exists('state',$_REQUEST)?htmlspecialchars($_REQUEST['state']):'';
	$distArr = $collManager->getGeographyStats($countryDist,$stateDist);
	if($distArr){
		?>
		<fieldset id="geographystats" style="margin:20px;width:90%;">
			<legend>
				<b>
					<?php
					echo ($LANG['GEO_DIST']?$LANG['GEO_DIST']:'Geographic Distribution');
					if($stateDist){
						echo ' - '.$stateDist;
					}
					elseif($countryDist){
						echo ' - '.$countryDist;
					}
					?>
				</b>
			</legend>
			<div style="margin:15px;"><?php echo $LANG['CLICK_ON_SPEC_REC'];?></div>
			<ul>
				<?php
				foreach($distArr as $term => $cnt){
					$countryTerm = ($countryDist?$countryDist:$term);
					$stateTerm = ($countryDist?($stateDist?$stateDist:$term):'');
					$countyTerm = ($countryDist && $stateDist?$term:'');
					echo '<li>';
					if(!$stateDist) echo '<a href="collprofiles.php?collid='.$collid.'&stat=geography&country='.$countryTerm.'&state='.$stateTerm.'#geographystats">';
					echo $term;
					if(!$stateDist) echo '</a>';
					echo ' (<a href="../list.php?db='.$collid.'&reset=1&country='.$countryTerm.'&state='.$stateTerm.'&county='.$countyTerm.'" target="_blank">'.$cnt.'</a>)';
					echo '</li>';
				}
				?>
			</ul>
		</fieldset>
		<?php
	}
}
elseif($statDisplay == 'taxonomy'){
	$famArr = $collManager->getTaxonomyStats();
	?>
	<fieldset id="taxonomystats" style="margin:20px;width:90%;">
		<legend><b><?php echo $LANG['FAMILY_DIST']; ?></b></legend>
		<div style="margin:15px;float:left;">
			<?php echo $LANG['CLICK_ON_SPEC_FAM']; ?>
		</div>
		<div style="clear:both;">
			<ul>
				<?php
				foreach($famArr as $name => $cnt){
					echo '<li>';
					echo $name;
					echo ' (<a href="../list.php?db='.$collid.'&type=1&reset=1&taxa='.$name.'" target="_blank">'.$cnt.'</a>)';
					echo '</li>';
				}
				?>
			</ul>
		</div>
	</fieldset>
	<?php
}