<?php
/*

Author: James Mickley
This script resizes large images on the fly to become web or thumbnail images
By doing this, it can save some storage, between 300 KB - 2 MB depending on JPEG quality
Usage (GET url):
imgresize.php?image=OSU_JGM/OSC-V-297/OSC-V-297327_lg.jpg&width=1400&quality=95

*/

// Include configuration
include_once('../config/symbini.php');

// Construct full image path, using the root path in the server
// N.B.: This may not actually be the path defined by the image processor. 
// An alternative would be to search the images table for an image, and pull the lg image path
$imagePath = $IMAGE_ROOT_PATH.(substr($IMAGE_ROOT_PATH,-1) == '/'?'':'/').$_GET['image'];

// Check if the file to resize exists
if (!file_exists($imagePath)) { die('Large image file does not exist to resize.'); }

// Check if the width passed is a positive integer value, and use 1400 px width if not
$newWidth = (intval($_GET['width']) && $_GET['width'] > 0 ? intval($_GET['width']) : 1400);

// Check if the quality passed is an integer [1,100], and use 75 if not
$quality = ($_GET['quality'] == "tn" || (intval($_GET['quality']) && $_GET['quality'] >= 1 && $_GET['quality'] <= 100) 
    ? intval($_GET['quality']) : 75);

// Make the php page act as an image, setting it to return a JPEG
header('Content-type:image/jpg');

// Check whether to use Image Magick, and fall back to GD, if not
if($USE_IMAGE_MAGICK) {

    // Read in the image to an Imagic object
    $image = new Imagick($imagePath);

    // Get the width and height of the original image
    $sourceWidth = $image->getImageWidth();
    $sourceHeight = $image->getImageHeight();

    // Get the width to height ratio to keep this aspect ratio for the resized image 
    $ratio = $sourceWidth / $sourceHeight;

    // Check if the requested width is larger than the original
    if($newWidth >= $sourceWidth) {

        // Just use the original rather than upsampling
        echo $image->getImageBlob();

        // delete the image resource
        $image->destroy();
    } 
    else {

        // Use the requested width as-is and calculate a proportional height
        $newHeight = $newWidth / $ratio; 
    }

    // Resize the image, using Lagrange filter, and no sharpening/blurring
    // N.B. This is a slow filter, but high quality. 
    //   Other good options = CATROM & maybe TRIANGLE
    // See comments in https://www.php.net/manual/en/imagick.resizeimage.php for speeds
    $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);

    // Check if it's a thumbnail image
    if(quality == "tn") {

        // If thumbnail image, set quality to 75 and strip image metadata
        $image->setImageCompressionQuality(75);
        $image->stripImage();
    } 
    else {

        // Set quality 
        $image->setImageCompressionQuality($quality);
    }
    
    // Print out the image
    echo $image->getImageBlob();

    // delete the image resource
    $image->destroy(); 
}

// Use GD if it's installed
elseif(extension_loaded('gd') && function_exists('gd_info')) {

    // Read in the original image
    $original = imagecreatefromjpeg($imagePath);

    // Get the width and height of the original image
    $sourceWidth = imagesx($original);
    $sourceHeight = imagesy($original);

    // Get the width to height ratio to keep this aspect ratio for the resized image 
    $ratio = $sourceWidth / $sourceHeight;

    // Check if the requested width is larger than the original
    if($newWidth >= $sourceWidth) {

        // Just use the original rather than upsampling
        imagejpeg($original);

    } else {

        // Use the requested width as-is and calculate a proportional height
        $newHeight = $newWidth / $ratio; 
    }

    // Make a new blank image to hold the resized image
    $tmpImg = imagecreatetruecolor($newWidth, $newHeight);

    // Resize the image and copy to the new image
    imagecopyresized($tmpImg, $original, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

    // Return the image as a JPEG to the user, without saving
    imagejpeg($tmpImg, NULL, $quality);
}

// No image resize software installed
else{
    exit("ABORT: No appropriate image handler for image conversions (GD or Image Magick)");
}

?>
