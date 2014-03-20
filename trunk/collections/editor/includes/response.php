<?php
include_once('../../../config/symbini.php');
include_once('fp/FPNetworkFactory.php');
include_once('fp/common/AnnotationGenerator.php');

// check that the client helper has been installed
$file = 'fp/FPNetworkFactory.php';
$includePaths = explode(PATH_SEPARATOR, get_include_path());
$fileExists = false;

foreach ($includePaths as $p) {
    $fullname = $p . DIRECTORY_SEPARATOR . $file;
    if (is_file($fullname)) {
        $fileExists = true;
        break;
    }
}

if (!$fileExists) {
    echo "FilteredPush Support has been enabled in this Symbiota installation, but FilteredPush helper code is not installed.<BR>";
    echo "<strong>$file not found.</strong>";
} else {
    if (isset($_GET['uri'])) {
        ?>
        <form action="response.php" method="post">
            Annotator Name: <input type="text" size="20" name="annotator_name"/><br/>
            Annotator Email: <input type="text" size="20" name="annotator_email"/><br/>
            <input type="radio" name="polarity" value="positive"/> Agree <input type="radio" name="polarity"
                                                                                value="neutral"/> Neutral <input
                type="radio" name="polarity" value="negative"/> Disagree<br/>
            Opinion Text: <input type="text" size="40" name="opinionText"/><br/>
            Evidence: <br/>
            <textarea name="evidence" rows="10" cols="35"></textarea><br/>
            <input type="hidden" name="annotationURI" value="<? echo $_GET['uri'] ?>"/>
            <input type="submit" value="Respond"/>
        </form>
    <?
    } else if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $fp = FPNetworkFactory::getNetworkFacade();

        $annotation = array();
        $annotation['target'] = array("annotationUri" => $_POST['annotationURI']);
        $annotation['body'] = array("polarity" => array("name" => $_POST['polarity']),
            "describesObject" => array("annotationUri" => $_POST['annotationURI']),
            "opinionText" => $_POST['opinionText']);
        $annotation['annotator_name'] = $_POST['annotator_name'];
        $annotation['annotator_email'] = $_POST['annotator_email'];
        $annotation['evidence'] = array("chars" => $_POST['evidence']);

        echo $fp->respond(AnnotationGenerator::responseAnnotation($annotation));

        echo "<script type='text/javascript'>";
        echo "window.close();";
        echo "</script>";
    }
}

?>