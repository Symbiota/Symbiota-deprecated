<?php
class MySQLiConnectionFactory {
    static $SERVERS = array(
      array(
            'type' => 'readonly',
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'database' => ''
        ),
        array(
            'type' => 'write',
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'database' => ''
        )
    );

    public static function getCon($type) {
        // Figure out which connections are open, automatically opening any connections
        // which are failed or not yet opened but can be (re)established.
        for ($i = 0, $n = count(MySQLiConnectionFactory::$SERVERS); $i < $n; $i++) {
            $server = MySQLiConnectionFactory::$SERVERS[$i];
            if($server['type'] == $type){
				$connection = new mysqli($server['host'], $server['username'], $server['password'], $server['database']);
                if(mysqli_connect_errno()){
					throw new Exception('Could not connect to any databases! Please try again later.');
                }
				if(isset($charset) && $charset == 'UTF-8') {
					if(!$connection->set_charset($server['charset'])){
						throw new Exception('Error loading character set utf8: '.$mysqli->error);
					}
				}    
                return $connection;
            }
        }
    }
}
	
?>