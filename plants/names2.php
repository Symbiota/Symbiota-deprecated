<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>About Plants - Names - Scientific Names</title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = "true";
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->
		<!-- start of inner text and right side content -->
		<div  id="innervplantstext">
			<div id="bodywrap">
				<div id="wrapper1"><!-- for navigation and content -->

					<!-- PAGE CONTENT STARTS -->

					<div id="content1wrap"><!--  for content1 only -->

					<div id="content1"><!-- start of primary content --><a id="pagecontent" name="pagecontent"></a>
						<h1>Scientific Names</h1>

						<div style="margin:20px;">
							<p>Scientific names are the official names that follow published naming rules (codes of nomenclature, see <a href="names.php">Taxonomy</a>) that uniquely identify a specific organism or a group of related organisms, such as a genus or family (e.g. the genus <i>Ulmus</i> and the family Ulmaceae). The scientific names for genera, species, subspecies, varieties, and formas are always Latinized words, and thus always appear in italics (or underlined). Family names and other ranks above genus are not italicized but rather identified by their ending. Family names end with "-aceae".
							</p>

							<h2>The Binomial</h2>
							<p>
							Names for species are always a combination of two Latinized words, called a binomial (meaning two names).  The first part of a binomial is the genus name, and it is always capitalized.  The second part of the species binomial is called the specific epithet or species epithet, and it is never capitalized (e.g. the scientific name for the American elm is <i>Ulmus americana</i>). Epithets based on proper names were previously capitalized but that practice ended.  
							It is customary to shorten the genus name of the binomial to the first letter (still capitalized) followed by a period when the genus can be clearly presumed based on the appearance of the full genus name earlier in the sentence or paragraph. For example, <i>Ulmus americana</i>, <i>U. rubra</i>, and <i>U. thomasi</i> are all in the genus <i>Ulmus</i>. 
							</p>

							<h2>Subspecific epithets</h2>
							<p>
							Taxonomic levels (or ranks) below species are, in descending order, subspecies (subsp. or ssp.), variety (var.), and forma (f.). When used, these are always added after the species name, but with the level specified, for example, <i>Campanula aparinoides</i> ssp. <i>uliginosa</i>. 
							Note that subsp., var., and f. are not put in italics (or underlined).
							</p>

							<h2>Authors</h2>
							<p>
							In order to be completely clear about the scientific name of a species or lower level taxon, the author &#151; the name(s) or standard abbreviation(s) of the person(s) who first published that name combination &#151; should also be added after the end of the epithet, for example <i>Rhus copalina</i> L., or <i>R. copalina</i> var. <i>latifolia</i> Engl. 
							</p>

							<img src="<?php echo $clientRoot; ?>/images/vplants/feature/sciname.gif" width="400" height="97" alt="diagram of the parts of a scientific name.">

							<p>When a taxon name is moved to a different genus, the specific epithet is transferred to that new genus. The original taxon author(s) is placed in parentheses and the author(s) making the new combination is placed after that. For example, in 1849 Berkeley and Curtis described a small tan mushroom with a spongy stem base. The name history for this species is as follows. The first three names are synonyms of the fourth name <i>Gymnopus spongiosus</i>, which is the currently accepted name. 
							<ol>
							<li><span class="taxon">
							 <i class="genus">Marasmius</i>
							 <i class="epithet">spongiosus</i>
							 <span class="author">Berkeley &amp; Curtis</span>
								</span>
							 &#151; 1849, the species is first described and published.</li>


							<li><span class="taxon">
							 <i class="genus">Marasmius</i> 
							 <i class="epithet">semisquarrosus</i>
							 <span class="author">Berkeley &amp; Cooke</span>
								</span>
							 &#151; 1878, a new species is published, but later it is decided that this represents the same species as <i>M. spongiosus</i>. The name that was published first (in this case 1849 for <i>M. spongiosus</i>) takes precedence over any names described later.</li>

							<li><span class="taxon">
							 <i class="genus">Collybia</i> 
							 <i class="epithet">spongiosa</i>
							 <span class="author">(Berk. &amp; Curt.) Singer</span>
								</span>
							 &#151; 1949, Rolf Singer moves the species to the genus <i>Collybia</i>.</li>

							<li><span class="taxon">
							 <i class="genus">Gymnopus</i> 
							 <i class="epithet">spongiosus</i>
							 <span class="author">(Berk. &amp; Curt.) Halling</span>
								</span>
							 &#151; 1996, after the genus <i>Collybia</i> is split apart, Roy Halling transfers the mushroom again.</li>
							</ol>
							</p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
					
					<div id="content2">
					
						<div class="box">
							<h3>Names</h3>
							<ul>
								<li><a href="names.php">Names Main</a></li>
								<li><a href="names5.php">Taxonomy</a></li>
								<li><strong>Scientific names</strong></li>
								<li><a href="names3.php">Synonyms</a></li>
								<li><a href="names4.php">Common names</a></li>
							</ul>
						</div>

						<div class="box document">
						<h3>....</h3>
						<ul><li>
						<!-- put blurb here about Carolus Linnaeus, father of binomial nomenclature -->
						</li></ul>
						</div>

						<div class="box external">
						<h3>....</h3>
						<ul>
						<li>
						<!-- link to standard author abbreviations -->

						</li>
						</ul>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>