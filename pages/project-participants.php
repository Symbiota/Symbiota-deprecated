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

<!-- if you need a full width colum, just put it outside of .inner-content -->
<!-- .inner-content makes a column max width 1100px, centered in the viewport -->
<div class="inner-content">
    <!-- place static page content here. -->
    <h2>Staff</h2>
    <p>Linda Hardison, Director</p>
    <p><?php echo obfuscate('hardisol@science.oregonstate.edu'); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
    <p>&nbsp;</p>
    <p>Thea Jaster, Database manager, botanist</p>
    <p><?php echo obfuscate('jastert@science.oregonstate.edu'); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-2445</p>
    <p>&nbsp;</p>
    <p>Stephen Meyers, Taxonomic Director</p>
    <p><?php echo obfuscate('meyersst@science.oregonstate.edu'); ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
    <p>&nbsp;</p>
    <p>Katie Mitchell, Database manager, botanist</p>
    <p><?php echo obfuscate('mitchelk@science.oregonstate.edu'); ?></p>
    <p>&nbsp;</p>
    <p>John Myers, illustrator</p>
    <p><?php echo obfuscate('myersj8@oregonstate.edu'); ?></p>
    <p>&nbsp;</p>
    <p>Tanya Harvey, <em>Flora of Oregon</em> Graphic Designer</p>
    <p><?php echo obfuscate('tanya@westerncascades.com'); ?></p>
    <p>&nbsp;</p>
    <h2>Advisory Council</h2>
    <p>Lynda Boyer</p>
    <p>Jason Bradford</p>
    <p>Daniel Luoma</p>
    <p>Will McClatchey</p>
    <p>Joan Seevers</p>
    <p>Robert Soreng</p>
    <p>&nbsp;</p>
    <h2>Project Associates</h2>
    <p>The <a target="_blank" href="http://npsoregon.org/">Native Plant Society of Oregon (NPSO)</a> has been a sponsor of OregonFlora since the project’s inception in 1994. The Society and its chapters provide financial support and promote the exchange of plant observation data and photographs.</p>
    <p>Our close ties with the <a target="_blank" href="http://oregonstate.edu/dept/botany/herbarium/">OSU Herbarium</a> are mutually beneficial--the Herbarium excels as a dynamic resource with exceptional depth in its Oregon collections, and the Flora Project enhances their value with careful taxonomic analysis.</p>
    <p>This website uses the <a target="_blank" href="http://symbiota.org/docs/">Symbiota</a> platform with customized modules. Our website is hosted at the <a target="_blank" href="http://my.science.oregonstate.edu/">College of Science Information Network</a> (COSINe) at Oregon State University.</p>
    <p>We collaborate and share information with numerous groups: academic institutions, federal agencies (Oregon/Washington Bureau of Land Management, US Forest Service), state organizations (Oregon Natural Heritage Information Center), Metro, Native Plant Society of Oregon, and individuals.</p>
    <p>&nbsp;</p>
    <h2>Key Supporters</h2>
    <p>John and Betty Soreng Environmental Fund of the Oregon Community Foundation</p>
    <p>Oregon/Washington Bureau of Land Management</p>
    <p>Native Plant Society of Oregon</p>
    <p>Metro (Portland Oregon)</p>
    <p>Department of Botany &amp; Plant Pathology, Oregon State University</p>
</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>