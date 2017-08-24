<?php
/*
 ******  Create a custom template below to add to your taxon profile pages  ********************************************
 *
 * DEFAULT TEMPLATE:
 *
 * if($taxonRank){
 *      if($taxonRank > 180){
 *          $topRowElements = Array($editButtonDiv,$scinameHeaderDiv,$ambiguousDiv,$webLinksDiv);
 *          $leftColumnElements = Array($familyDiv,$vernacularsDiv,$synonymsDiv,$centralImageDiv);
 *          $rightColumnElements = Array($descTabsDiv);
 *          $bottomRowElements = Array($mapThumbDiv,$imgDiv,$imgTabDiv);
 *          $footerRowElements = Array($footerLinksDiv);
 *      }
 *      elseif($taxonRank == 180){
 *          $topRowElements = Array();
 *          $leftColumnElements = Array($scinameHeaderDiv,$familyDiv,$projectDiv,$centralImageDiv);
 *          $rightColumnElements = Array($editButtonDiv,$descTabsDiv);
 *          $bottomRowElements = Array($imgBoxDiv);
 *          $footerRowElements = Array($footerLinksDiv);
 *      }
 *      else{
 *          $topRowElements = Array();
 *          $leftColumnElements = Array($scinameHeaderDiv,$familyDiv,$projectDiv,$centralImageDiv);
 *          $rightColumnElements = Array($editButtonDiv,$descTabsDiv);
 *          $bottomRowElements = Array($imgBoxDiv);
 *          $footerRowElements = Array($footerLinksDiv);
 *      }
 *  }
 *  elseif($taxonValue){
 *      $topRowElements = Array($notFoundDiv);
 *  }
 *  else{
 *      $topRowElements = Array('ERROR!');
 *  }
 *
 * ******  Add custom plugins defined in the taxaProfileElementsCustom file  ********************************************
 *
 * EXAMPLE:
 * $topRowElements = Array($pluginName);
 *
 ***********************************************************************************************************************
 *
 */

//Enter one to many custom cascading style sheet files 
//$CSSARR = array('example1.css','example2.css');

//Enter one to many custom javascript files
//$JSARR = array('example1.js','example2.js'); 

include('includes/config/taxaProfileElementsDefault.php');
if(file_exists('includes/config/taxaProfileElementsCustom.php')){
    include('includes/config/taxaProfileElementsCustom.php');
}

$topRowElements = Array(); //Top horizontal bar in taxon profile page
$leftColumnElements = Array(); //Left column below top horizontal bar in taxon profile page
$rightColumnElements = Array(); //Right column below top horizontal bar in taxon profile page
$bottomRowElements = Array(); //Horizontal bar below left and right columns in taxon profile page
$footerRowElements = Array(); //Bottom horizontal bar in taxon profile page

if($taxonRank){
    if($taxonRank > 180){ //Template for taxa pages below the genus rank.
        $topRowElements = Array();
        $leftColumnElements = Array();
        $rightColumnElements = Array();
        $bottomRowElements = Array();
        $footerRowElements = Array();
    }
    elseif($taxonRank == 180){ //Template for genera taxa pages.
        $topRowElements = Array();
        $leftColumnElements = Array();
        $rightColumnElements = Array();
        $bottomRowElements = Array();
        $footerRowElements = Array();
    }
    else{  //Template for taxa pages above the genus rank.
        $topRowElements = Array();
        $leftColumnElements = Array();
        $rightColumnElements = Array();
        $bottomRowElements = Array();
        $footerRowElements = Array();
    }
}
elseif($taxonValue){
    $topRowElements = Array();
}
else{
    $topRowElements = Array('ERROR!');
}
?>