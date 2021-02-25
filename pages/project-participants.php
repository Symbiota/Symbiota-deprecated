<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );

function obfuscate($email) {
    //build the mailto link
    $unencrypted_link = '<a href="mailto:'.$email.'">'.$email.'</a>';
    $noscript_link = "email";
    //put them together and encrypt
    return '<script type="text/javascript">Rot13.write(\''.str_rot13($unencrypted_link).'\');</script><noscript>'.$noscript_link . '</noscript>';
}
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Partners</title>
    <meta charset="UTF-8">
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet"/>
    <link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet"/>
    <meta name='keywords' content=''/>
    <script type="text/javascript">
		<?php include_once( $serverRoot . '/config/googleanalytics.php' ); ?>
    </script>
    <script>
        Rot13 = {
            map: null,

            convert: function(a) {
                Rot13.init();

                var s = "";
                for (i=0; i < a.length; i++) {
                    var b = a.charAt(i);
                    s += ((b>='A' && b<='Z') || (b>='a' && b<='z') ? Rot13.map[b] : b);
                }
                return s;
            },

            init: function() {
                if (Rot13.map != null)
                    return;

                var map = new Array();
                var s   = "abcdefghijklmnopqrstuvwxyz";

                for (i=0; i<s.length; i++)
                    map[s.charAt(i)] = s.charAt((i+13)%26);
                for (i=0; i<s.length; i++)
                    map[s.charAt(i).toUpperCase()] = s.charAt((i+13)%26).toUpperCase();

                Rot13.map = map;
            },

            write: function(a) {
                document.write(Rot13.convert(a));
            }
        }
    </script>
</head>
<body>
<?php
include( $serverRoot . "/header.php" );
?>

<div class="info-page">
    <section id="titlebackground" class="title-leaf">
        <div class="inner-content">
            <h1>Partners</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content">
        <!-- place static page content here. -->
            <h2>Staff</h2>
							<p>Linda Hardison, Director<br /><?php echo obfuscate("hardisol@science.oregonstate.edu") ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
							<p>Tanya Harvey, <em>Flora of Oregon</em> graphic designer<br /><?php echo obfuscate("tanya@westerncascades.com") ?></p>							
							<p>Thea Jaster, Database manager, botanist<br /><?php echo obfuscate("jastert@science.oregonstate.edu") ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-2445</p>
							<p>Stephen Meyers, Taxonomic Director<br /><?php echo obfuscate("meyersst@science.oregonstate.edu") ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
							<p>Katie Mitchell, Database manager, botanist<br /><?php echo obfuscate("mitchelk@science.oregonstate.edu") ?></p>
							<p>John Myers, <em>Flora of Oregon</em> principal illustrator<br /><?php echo obfuscate(" myersj8@oregonstate.edu") ?></p>
							
							<br>
            <h2>Affiliates</h2>
							<p>Dennis Albert</p>
							<p>Jason Bradford</p>
							<p>Gerald Carr</p>
							<p>Kenton Chambers</p>
							<p>Brant Cothern</p>
							<p>Christian Feuillet</p>
							<p>Richard Halse</p>
							<p>George Kral</p>
							<br>
            <h2>Students (2021)</h2>
							<p>Mya Falk</p>
							<p>Amadeo Ramos</p>
							<p>Jessica Tuson</p>
							<br>
            <h2>Advisory Council</h2>
							<p>Lynda Boyer</p>
							<p>Jason Bradford</p>
							<p>Daniel Luoma</p>
							<p>Will McClatchey</p>
							<p>Joan Seevers</p>
							<p>Robert Soreng</p>
							<br>
            <h2>Project Partners</h2>
							<p>The <a href="http://npsoregon.org" target="_blank">Native Plant Society of Oregon (NPSO)</a> has been a sponsor of OregonFlora since the project&rsquo;s inception in 1994. The Society and its chapters provide financial support and promote the exchange of plant observation data and photographs.</p>
							<p>Our close ties with the <a href="https://bpp.oregonstate.edu/herbarium" target"_blank">OSU Herbarium</a> are mutually beneficial &mdash; the Herbarium excels as a dynamic resource with exceptional depth in its Oregon collections, and OregonFlora enhances their value with careful taxonomic analysis and digital presentation within the context of the OregonFlora tools.</p>
							<p><a href="https://oregonmetro.gov/garden" target="_blank">Metro</a> and its partners in the Northwest Adult Conservation Educators (NW-ACE) working group have led the development and support of the Grow Natives section of this website. These groups conceived and developed the foundation of this tool and have assisted in its launching.</p>
							<p>Primary members/contributors of NW-ACE are: <a href="https://backyardhabitats.org/" target="_blank">Backyard Habitat Certification Program</a>, City of Gresham, City of Portland, <a href="https://www.cleanwaterservices.org/" target="_blank">Clean Water Services</a>, <a href="https://emswcd.org/in-your-yard/" target="_blank">East Multnomah Soil & Water Conservation District</a>, <a href="https://tualatinswcd.org" target="_blank">Tualatin Soil & Water Conservation District</a>, and <a href="https://wmswcd.org"target="_blank">West Multnomah Soil & Water Conservation District</a>.</p>
							<p>This website uses the Symbiota platform with customized modules. Our website is hosted at the College of Science Information Network (COSINe) at Oregon State University.</p>
							<p>We collaborate and share information with numerous groups: academic institutions, federal agencies (Oregon/Washington Bureau of Land Management, US Forest Service), state organizations (Oregon Biodiversity Information Center), Metro, Native Plant Society of Oregon, and individuals.&nbsp;</p>
							<br>
						<h2>Key Supporters</h2>
							<p>John and Betty Soreng Environmental Fund of the Oregon Community Foundation</p>
							<p>Oregon/Washington Bureau of Land Management</p>
							<p>Native Plant Society of Oregon</p>
							<p>Metro (Portland Oregon)</p>
							<p>Barbara J. Mendius Administrative Trust</p>
							<p>USDA&mdash;Natural Resources Conservation Service (Oregon)</p>
							<p>Department of Botany &amp; Plant Pathology, Oregon State University</p>`
        </div> <!-- .inner-content -->
    </section>
</div> <!-- .info-page -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>