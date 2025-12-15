<?php

namespace App;

class Connection {

	public static function getDbs() {

		$host = '172.16.1.230';
		$dbname = 'Cobranca';
		$username = 'julio.cesar';
		$password = '161220';

		$dsn = "pgsql:host=$host;port=5437;dbname=$dbname;user=$username;password=$password";

		try {

			$con = new \PDO($dsn);

			return $con;

		} catch (\PDOException $e) {
			//.. tratar de alguma forma ..//
		}
	}
}

?>