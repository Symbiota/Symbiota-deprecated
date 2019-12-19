<?php

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

include_once(__DIR__ . "/symbini.php");
include_once("$SERVER_ROOT/config/dbconnection.php");
require_once("$SERVER_ROOT/vendor/autoload.php");

foreach (glob("$SERVER_ROOT/models/*.php") as $file) {
  include_once($file);
}


class SymbosuEntityManager {

  // Doctrine config
  private static $EntityManager = null;

  private static function getMetaConfig() {
    global $SERVER_ROOT;
    global $IS_DEV;

    $config = new Configuration();

    $config->setProxyDir("$SERVER_ROOT/temp/proxies");
    $config->setProxyNamespace("Symbosu\Proxies");

    if ($IS_DEV) {
      $cache = new ArrayCache();
      $config->setAutoGenerateProxyClasses(true);
    } else {
      $cache = new ApcuCache();
      $config->setAutoGenerateProxyClasses(false);
    }

    $config->setMetadataCacheImpl($cache);
    $config->setQueryCacheImpl($cache);

    $driverImpl = $config->newDefaultAnnotationDriver("$SERVER_ROOT/models", false);
    $config->setMetadataDriverImpl($driverImpl);

    $factory = new DefaultCacheFactory(new RegionsConfiguration(), $cache);
    $config->setSecondLevelCacheEnabled();
    $config->getSecondLevelCacheConfiguration()->setCacheFactory($factory);

    return $config;
  }

  private static function getDbConfig() {
    $dbParams = MySQLiConnectionFactory::getConParams("readonly");
    return array(
      "dbname" => $dbParams["database"],
      "user" => $dbParams["username"],
      "password" => $dbParams["password"],
      "host" => $dbParams["host"],
      "driver" => "pdo_mysql"
    );
  }

  /**
   * @return \Doctrine\ORM\EntityManager
   * @throws \Doctrine\ORM\ORMException
   */
  public static function getEntityManager() {
    if (SymbosuEntityManager::$EntityManager === null) {
      SymbosuEntityManager::$EntityManager = EntityManager::create(
        SymbosuEntityManager::getDbConfig(),
        SymbosuEntityManager::getMetaConfig()
      );
    }

    return SymbosuEntityManager::$EntityManager;
  }
}
?>
