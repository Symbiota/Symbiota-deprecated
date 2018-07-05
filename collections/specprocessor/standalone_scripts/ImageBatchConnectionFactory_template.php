<?php
//If scripts should obtain a connection from the DBConnection class in the config folder, leave the variable empty in this file 
class ImageBatchConnectionFactory {
	static $SERVERS = array(
		array(
			'type' => 'write',
			'host' => 'localhost',
			'username' => '',
			'password' => '',
			'database' => '',
			'port' => '3306',
			'charset' => ''		//utf8, latin1, latin2, etc
		)
	);

	public static function getCon($type) {
		if(!ImageBatchConnectionFactory::$SERVERS[0]['database']) return null; 
		// Figure out which connections are open, automatically opening any connections
		// which are failed or not yet opened but can be (re)established.
		for ($i = 0, $n = count(ImageBatchConnectionFactory::$SERVERS); $i < $n; $i++) {
			$server = ImageBatchConnectionFactory::$SERVERS[$i];
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