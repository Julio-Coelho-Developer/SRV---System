<?php


namespace MF\Model;

abstract class Model {

	protected $db;

	public function __construct(\PDO $db) {
		$this->db = $db;
	}
}

abstract class Modelo {

	protected $db1;

	public function __construct(\PDO $db1) {
		$this->db1 = $db1;
	}
}


?>