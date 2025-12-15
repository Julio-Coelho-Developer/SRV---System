<?php

namespace MF\Model;

use App\Connection;

class Containeres {

	public static function getModelo($model) {
		$class = "\\App\\Models\\".ucfirst($model);
		$con = Connection::getDbs();

		return new $class($con);
	}
}



?>