<?php

    namespace App\Models;

    use MF\Model\Model;

    class usuario extends Model {
        
        private $login;
        private $senha;

        //Retornar o valor de um atributo
        public function __get($atributo){
            return $this->$atributo;
        }

        //Adicionar o valor a um atributo
        public function __set($atributo, $valor){
            return $this->$atributo = $valor;
        }

     
        public function autenticar(){
            

            $query = "select nome, usuario from tb_usuarios where usuario = :login and senha = :senha";
            $stmt = $this->db->prepare($query); 
            $stmt->bindValue(':login' , $this->__get('login'));
            $stmt->bindValue(':senha', $this->__get('senha')); 
            $stmt->execute();

            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

            if(!empty($usuario['id']) && !empty($usuario['nome'])){
                $this->__set('nome', $usuario['nome']);
                $this->__set('usuario', $usuario['usuario']);
            }
            return $usuario;
        }

        public function getUsers(){

            $query = "SELECT nome, usuario FROM tb_usuarios";
            $stmt = $this->db->prepare($query); 
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        }


    }

?>