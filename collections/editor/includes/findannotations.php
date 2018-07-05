<script src="../../js/jquery.js?ver=131123" type="text/javascript"></script>
<script src="../../js/jquery-ui.js?ver=131123" type="text/javascript"></script>

<?php
/* Copyright � 2012 President and Fellows of Harvard College
 *
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of Version 2 of the GNU General Public License
* as published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* Author: David B. Lowery
*/

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

// Check for required query params
    if (array_key_exists('catalognumber', $_GET) &&
        (array_key_exists('collectioncode', $_GET) || (array_key_exists('institutioncode', $_GET)))
    ) {
        $endpoint = FPNetworkFactory::getSparqlEndpoint();

        // returns query result formatted as html
        $results = $endpoint->getAnnotations($_GET);
        echo $results;

    } else {
        throw new Exception("catalognumber and either collectioncode or institutioncode required for \"Annotations\" tab view.");
    }

}

?>
