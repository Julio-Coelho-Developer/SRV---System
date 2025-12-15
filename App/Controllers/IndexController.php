<?php

namespace App\Controllers;
/*Recursos do mini-framework*/
use MF\Controller\Action;

class IndexController extends Action {

	public function index() {
		$this->render('index');
	}

	
    public function cadastrar(){    
        $this->render('cadastrar');       
    }

}


?>