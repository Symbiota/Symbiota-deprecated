<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );

function obfuscate($email) {
    //build the mailto link
    $unencrypted_link = '<a href="mailto:'.$email.'">'.$email.'</a>';
    //put them together and encrypt
    return '<script type="text/javascript">Rot13.write(\''.str_rot13($unencrypted_link).'\');</script><noscript>'.$noscript_link . '</noscript>';
}
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Project Participants</title>
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


<div id="info-page">
    <div id="titlebackground"></div>
    <!-- if you need a full width column, just put it outside of .inner-content -->
    <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
    <div class="inner-content">
    <!-- place static page content here. -->
        <h1>Project Participants</h1>
        <h2>Staff</h2>
        <p>Linda Hardison, Director<br />hardisol@science.oregonstate.edu&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
        <p>Stephen Meyers, Taxonomic Director<br />meyersst@science.oregonstate.edu&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
        <p>Thea Jaster, Database manager, botanist<br />jastert@science.oregonstate.edu&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-2445</p>
        <p>Katie Mitchell, Database manager, botanist<br />mitchelk@science.oregonstate.edu</p>
        <p>John Myers, Illustrator</p>
        <p>Tanya Harvey, <em>Flora of Oregon</em> graphic designer<br /><a href="mailto:tanya@westerncascades.com">tanya@westerncascades.com</a></p>
        <h2>Affiliates</h2>
        <p>Dennis Albert</p>
        <p>Jason Bradford</p>
        <p>Gerald Carr</p>
        <p>Kenton Chambers</p>
        <p>Brant Cothern</p>
        <p>Christian Feuillet</p>
        <p>Richard Halse</p>
        <p>George Kral</p>
        <h2>Students (2020)</h2>
        <p>Rosa Arellanes</p>
        <p>Mya Falk</p>
        <p>Amadeo Ramos</p>
        <p>Yan Yan</p>
        <h2>Advisory Council</h2>
        <p>Lynda Boyer</p>
        <p>Jason Bradford</p>
        <p>Daniel Luoma</p>
        <p>Will McClatchey</p>
        <p>Joan Seevers</p>
        <p>Robert Soreng</p>
        <h2>Project Partners</h2>
        <p>The Native Plant Society of Oregon (NPSO) has been a sponsor of OregonFlora since the project&rsquo;s inception in 1994. The Society and its chapters provide financial support and promote the exchange of plant observation data and photographs.</p>
        <p>Our close ties with the OSU Herbarium are mutually beneficial &mdash; the Herbarium excels as a dynamic resource with exceptional depth in its Oregon collections, and OregonFlora enhances their value with careful taxonomic analysis and digital presentation within the context of the OregonFlora tools.</p>
        <p>This website uses the Symbiota platform with customized modules. Our website is hosted at the College of Science Information Network (COSINe) at Oregon State University.</p>
        <p>We collaborate and share information with numerous groups: academic institutions, federal agencies (Oregon/Washington Bureau of Land Management, US Forest Service), state organizations (Oregon Biodiversity Information Center), Metro, Native Plant Society of Oregon, and individuals.&nbsp;</p>
        <h2>Key Supporters</h2>
        <p>John and Betty Soreng Environmental Fund of the Oregon Community Foundation</p>
        <p>Oregon/Washington Bureau of Land Management</p>
        <p>Native Plant Society of Oregon</p>
        <p>Metro (Portland Oregon)</p>
        <p>Barbara J. Mendius Administrative Trust</p>
        <p>USDA--Natural Resources Conservation Service (Oregon)</p>
        <p>Department of Botany &amp; Plant Pathology, Oregon State University</p>`
    </div> <!-- .inner-content -->
</div> <!-- #info-page -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>