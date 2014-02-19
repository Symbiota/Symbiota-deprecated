<?php 

include_once($serverRoot.'/classes/ActionManager.php');

/**
 * Controler class for ActionRequests pertaining to omoccurrences.
 */
class OccurrenceActionManager extends ActionManager { 

   /**
    * @return the actionrequestid for the inserted row, or null if there was an error.
    */
   public function makeOccurrenceActionRequest($uid,$occid,$requesttype,$remarks) { 
      return $this->makeActionRequest($uid,$occid,'omoccurrences',$requesttype,$remarks);
   } 

 
   /**
    * Obtain an array of text strings summarizing action requests that apply to an occurrance id.
    *
    * @param occid the occurence id to check for action requests.
    * @return an array of text strings, one per action request.
    */   
   public function listOccurrenceActionRequests($occid) { 
      return $this->listActionRequests($occid,'omoccurrences');
   }

} 

?>