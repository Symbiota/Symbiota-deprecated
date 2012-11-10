<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyMaintenance.php');

$treeBuilder = new TaxonomyMaintenance();

//$treeBuilder->buildHierarchyEnumTree();
$treeBuilder->buildHierarchyNestedTree();

?>