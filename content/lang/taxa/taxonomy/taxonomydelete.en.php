<?php 
/*
------------------
Language: English (Ingles)
------------------
*/

include_once('sharedterms.es.php');

$LANG['PAR3'] = 'Taxon record first needs to be evaluated before it can be deleted from the system. 
		The evaluation ensures that the deletion of this record will not interfer with 
		data integrity. ';

$LANG['APPROVED'] = 'Approved';
$LANG['NO_CHILD'] = 'no children taxa are linked to this taxon';
$LANG['CHILDREN'] = 'Children Taxa';
$LANG['WARNING'] = 'Warning: children taxa exist for this taxon. They must be remapped before this taxon can be removed';
$LANG['SYNONYM_LINK'] = 'Synonym Links';
$LANG['WRRN_SYN'] = 'Warning: synonym links exist for this taxon. They must be remapped before this taxon can be removed';
$LANG['NO_SYNO'] = 'no synonyms are linked to this taxon';
$LANG['IMAGES'] = 'Images';
$LANG['WARNING_1'] = 'Warning';
$LANG['IMAG_LINKED'] = 'images linked to this taxon';
$LANG['NO_IMG_LINKED'] = 'no images linked to this taxon';
$LANG['VERNACULARS'] = 'Vernaculars';
$LANG['WAR_LINKED'] = 'Warning, linked vernacular names:';
$LANG['NO_VERNACULAR'] = 'no vernacular names linked to this taxon';
$LANG['TEXT_DESCRIP'] = 'Text Descriptions';
$LANG['WAR_LINKED_TXT'] = 'Warning, linked text descriptions exist:';
$LANG['NO_TEXT'] = 'no text descriptions linked to this taxon';
$LANG['OCRR_REC'] = 'Occurrence records';
$LANG['WAR_LINK_OCURR'] = 'Warning, linked occurrence records exist:';
$LANG['OCURR_RECORD'] = 'occurrence records linked to this taxon';
$LANG['WAR_DETER'] = 'Warning, linked determination records exist:';
$LANG['NO_OCURRENCE'] = 'no occurrence determinations linked to this taxon';
$LANG['CHECKLIST'] = 'Checklists';
$LANG['WAR_LINK_CHECK'] = 'Warning, linked checklists exist:';
$LANG['NO_CHECKLIST'] = 'no checklists linked to this taxon';
$LANG['MORPHOLOGICAL'] = 'Morphological Characters (Key):';
$LANG['LINKED'] = 'linked morphological characters';
$LANG['NO_MORPHO'] = 'no morphological characters linked to this taxon';
$LANG['LINK_RESOURCES'] = 'Linked Resources:';
$LANG['WAR_LINKED_RES'] = 'Warning: linked resources exists';
$LANG['NO_RESOURCE'] = 'no resources linked to this taxon';
$LANG['REMAP'] = 'Remap Resources to Another Taxon';
$LANG['TARG_TAX'] = 'Target taxon:';
$LANG['DEL_TAX'] = 'Delete Taxon and Existing Resources';
$LANG['ARE_YOU'] = 'Are you sure you want to delete this taxon? Action can not be undone!';
$LANG['TAX_CANNOT'] = 'Taxon cannot be deleted until all children, synonyms, images, and text descriptions are removed or remapped to another taxon.';
$LANG['WAR_VERNACULARS'] = 'Warning: Vernaculars will be deleted with taxon';
$LANG['WAR_MORPHOLOGICAL'] = 'Warning: Morphological Key Characters will be deleted with taxon';
$LANG['WAR_LINKS_DEL'] = 'Warning: Links to checklists will be deleted with taxon';
$LANG['WAR_LINKED_BE_DEL'] = 'Warning: Linked Resources will be deleted with taxon';
$LANG['ERROR'] = 'ERROR: Remapping taxon not found in thesaurus. Is the name spelled correctly?';
$LANG[''] = '';
$LANG[''] = '';

?>