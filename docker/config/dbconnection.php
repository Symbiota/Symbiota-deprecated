<?php

class MySQLiConnectionFactory {
    static $CONFIG_FILE_DIR = "/usr/local/etc/symbiota";
    static $CONFIG_FILE_DB = "database.yml";

    public static function getCon($type) {

        $db_config = yaml_parse_file(
            MySQLiConnectionFactory::$CONFIG_FILE_DIR .
            "/" .
            MySQLiConnectionFactory::$CONFIG_FILE_DB
        )["database"];

        $servers = array(
          array(
                'type' => 'readonly',
                'host' => $db_config["host"],
                'username' => $db_config["users"]["readonly"]["username"],
                'password' => $db_config["users"]["readonly"]["password"],
                'database' => $db_config["name"],
                'port' => $db_config["port"],
                'charset' => 'utf8'		//utf8, latin1, latin2, etc
            ),
            array(
                'type' => 'write',
                'host' => $db_config["host"],
                'username' => $db_config["users"]["readwrite"]["username"],
                'password' => $db_config["users"]["readwrite"]["password"],
                'database' => $db_config["name"],
                'port' => $db_config["port"],
                'charset' => 'utf8'
            )
        );

        // Figure out which connections are open, automatically opening any connections
        // which are failed or not yet opened but can be (re)established.
        for ($i = 0, $n = count($servers); $i < $n; $i++) {
            $server = $servers[$i];
            if($server['type'] == $type){
				$connection = new mysqli($server['host'], $server['username'], $server['password'], $server['database'], $server['port']);
                if(mysqli_connect_errno()){
					throw new Exception('Could not connect to any databases! Please try again later.');
                }
				if(isset($server['charset']) && $server['charset']) {
					if(!$connection->set_charset($server['charset'])){
						throw new Exception('Error loading character set '.$server['charset'].': '.$mysqli->error);
					}
				}
                return $connection;
            }
        }
    }
}
?>