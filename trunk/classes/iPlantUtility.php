<?php
/**
* Utility functions for working with iPlant resources.
*/

/**
* Data structure to hold results of attempted query on bisque, including
* status and errors from request.
*/
class BisqueResult { 
   public $statusok;
   public $error;
   public $responsedocument;  
   // values from response
   public $created;
   public $name;
   public $owner;
   public $permission;
   public $resource_uniq;
   public $ts;
   public $uri;
   public $value;

/* Example response document 
<resource uri="http://bovary.iplantcollaborative.org/data_service/image?tag_query=filename:IMG_0046.dng"><image created="2013-10-16T05:45:14.361834" name="IMG_0046.dng" owner="http://bovary.iplantcollaborative.org/data_service/user/4892555" permission="published" resource_uniq="yKWNDWG6heD2QFWnHES4fF" ts="2013-10-16T05:45:14.361834" uri="http://bovary.iplantcollaborative.org/data_service/image/4892591" value="irods://data.iplantcollaborative.org/iplant/home/shared/NEVP/BRU/IMG_0046.dng"/></resource>

*/

}

/**
* Given a filename or irods:// URI of a resource that has been uploaded to iPlant 
* and an irods path in iPlant, obtain the iPlant resource_uniq identifier for the resource.
*
* @author Patrick Sweeney
* @author Paul J. Morris
* 
* @param inputFilename a full irods path to a file, or just a filename without the path.
*
* @param irodsPath  Fragment of an iRods path to filter on, broad filter is 
* "iplant\/home\/shared\/.*", project specfic filter is "iplant\/home\/shared\/NEVP.*"
* 
* @returns a BisqueResult object.
*/
function getiPlantID($inputFilename,$irodsPath){
    $result = new BisqueResult();
    $result->statusok = FALSE;
    // strip path, if any from filename
    $filename = preg_replace('/^.+[\\\\\\/]/', '', $inputFilename);
    // query bisque for a metadata document on the resource using the filename.
    $url = "http://bovary.iplantcollaborative.org/data_service/image?tag_query=filename:".$filename;
    $contents = @file_get_contents($url);
    if ($contents!==FALSE) { 
        // parse the returned document
        if ($contents!="") {
           $result->responsedocument = $contents; 
           $xml = new SimpleXMLElement($contents);
           foreach ($xml->image as $entry) {
               if (preg_match( '/^irods:\/\/data.iplantcollaborative.org\/'.$irodsPath, (string)$entry->attributes()->value) ){
                   $result->resource_uniq = (string)$entry->attributes()->resource_uniq;
                   $result->created = (string)$entry->attributes()->created;
                   $result->name = (string)$entry->attributes()->name;
                   $result->owner = (string)$entry->attributes()->owner;
                   $result->permission = (string)$entry->attributes()->permission;
                   $result->ts = (string)$entry->attributes()->ts;
                   $result->uri = (string)$entry->attributes()->uri;
                   $result->value = (string)$entry->attributes()->value;
                   $result->statusok = TRUE;
               }
           }
           if ($result->statusok===FALSE) { 
                $result->error = "No match found for $irodsPath in response from $url.";
           }
       } else { 
          $result->error = "Empty Response document from $url";
       }
    } else { 
       $result->error = "No response from $url";
    }
    return $result;  
}

/**
* NEVP specific lookup method, wraps getiPlantID()
* sets path filter to iplant\/home\/shared\/NEVP.* /
* 
* @param targetFilename, the filename, with or without path to query for.
* 
* @return a BisqueResult object.
* 
* Example Invocation: 
* <pre>

$result = getiPlantIDForNEVP("irods://data.iplantcollaborative.org/iplant/home/shared/NEVP/BRU/IMG_0046.dng");
if ($result->statusok===FALSE) { 
   echo "Error: " . $result->error . "\n";
} else { 
   echo $result->resource_uniq . "\n";
}

 </pre>
*
*/
function getiPlantIDForNEVP($targetFilename) { 
   return getiPlantID($targetFilename,"iplant\/home\/shared\/NEVP.*/");
}

?>
