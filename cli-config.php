<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;
include_once("./config/SymbosuEntityManager.php");

$entityManager = SymbosuEntityManager::getEntityManager();
return ConsoleRunner::createHelperSet($entityManager);
