<?php

    namespace App\Models;

    use MF\Model\Model;
    
    class Microserviço extends Model {
    

        //Retornar o valor de um atributo
        public function __get($atributo){
            return $this->$atributo;
        }

        //Adicionar o valor a um atributo
        public function __set($atributo, $valor){
            return $this->$atributo = $valor;
        }

        public function getUser(){
            $query = "SELECT id_usuario FROM tb_usuario WHERE email = :email";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':email', $this->__get('email'));
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        public function autenticarUsuario(){

            $query = "SELECT id_usuario, nome, email
                      FROM tb_usuario
                      WHERE email = :email
                      AND senha = :senha
                      AND ativo = true";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':email', $this->__get('email'));
            $stmt->bindValue(':senha', $this->__get('senha'));
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        public function getDadosUsuarioPorId(){
            $query = "SELECT nome, email, telefone, data_nascimento,
                            cep, rua, numero, complemento, bairro, cidade, estado,
                            github,
                            modalidades_trabalho, cargos_interesse, niveis_interesse,
                            beneficios_interesse, faixa_salarial, cursos, cargos_experiencia, skills, descricoes_atividades, github, tempos_experiencia, niveis_proficiencia
                      FROM tb_usuario
                      WHERE id_usuario = :id_usuario
                      AND ativo = true";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id_usuario', $this->__get('id_usuario'));
            $stmt->execute();

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Converter arrays PostgreSQL para PHP
            if ($result) {
                $result['modalidades_trabalho'] = $this->postgreSQLToArray($result['modalidades_trabalho']);
                $result['cargos_interesse'] = $this->postgreSQLToArray($result['cargos_interesse']);
                $result['niveis_interesse'] = $this->postgreSQLToArray($result['niveis_interesse']);
                $result['beneficios_interesse'] = $this->postgreSQLToArray($result['beneficios_interesse']);
                
                $result['cargos_experiencia'] = $this->postgreSQLToArray($result['cargos_experiencia']);
                $result['skills'] = $this->postgreSQLToArray($result['skills']);
                $result['tempos_experiencia'] = $this->postgreSQLToArray($result['tempos_experiencia']);
                $result['descricoes_atividades'] = $this->postgreSQLToArray($result['descricoes_atividades']);

            
            }

            return $result;
        }

        public function getDadosVagas(){
            $query = "SELECT * FROM tb_vagas where plataforma not in ('Indeed') ORDER BY data_publicacao DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();

            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result;
        }


        public function atualizarUsuario(){
            // Converter arrays PHP para formato PostgreSQL
            $modalidades_trabalho_pg = $this->arrayToPostgreSQL($this->__get('modalidades_trabalho'));
            $cargos_interesse_pg = $this->arrayToPostgreSQL($this->__get('cargos_interesse'));
            $niveis_interesse_pg = $this->arrayToPostgreSQL($this->__get('niveis_interesse'));
            $beneficios_interesse_pg = $this->arrayToPostgreSQL($this->__get('beneficios_interesse'), true);

            // Construir query base
            $query = "UPDATE tb_usuario SET
                        nome = :nome,
                        telefone = :telefone,
                        data_nascimento = :data_nascimento,
                        cep = :cep,
                        rua = :rua,
                        numero = :numero,
                        complemento = :complemento,
                        bairro = :bairro,
                        cidade = :cidade,
                        estado = :estado,
                        github = :github,
                        modalidades_trabalho = :modalidades_trabalho,
                        cargos_interesse = :cargos_interesse,
                        niveis_interesse = :niveis_interesse,
                        beneficios_interesse = :beneficios_interesse,
                        faixa_salarial = :faixa_salarial,
                        data_atualizacao = CURRENT_TIMESTAMP";

            // Adicionar senha se fornecida
            if ($this->__get('nova_senha')) {
                $query .= ", senha = :senha";
            }

            $query .= " WHERE id_usuario = :id_usuario";

            $stmt = $this->db->prepare($query);

            // Bind de valores
            $stmt->bindValue(':nome', $this->__get('nome'));
            $stmt->bindValue(':telefone', $this->__get('telefone'));
            $stmt->bindValue(':data_nascimento', $this->__get('data_nascimento'));
            $stmt->bindValue(':cep', $this->__get('cep'));
            $stmt->bindValue(':rua', $this->__get('rua'));
            $stmt->bindValue(':numero', $this->__get('numero'));
            $stmt->bindValue(':complemento', $this->__get('complemento'));
            $stmt->bindValue(':bairro', $this->__get('bairro'));
            $stmt->bindValue(':cidade', $this->__get('cidade'));
            $stmt->bindValue(':estado', $this->__get('estado'));
            $stmt->bindValue(':github', $this->__get('github'));
            $stmt->bindValue(':modalidades_trabalho', $modalidades_trabalho_pg);
            $stmt->bindValue(':cargos_interesse', $cargos_interesse_pg);
            $stmt->bindValue(':niveis_interesse', $niveis_interesse_pg);
            $stmt->bindValue(':beneficios_interesse', $beneficios_interesse_pg);
            $stmt->bindValue(':faixa_salarial', $this->__get('faixa_salarial'));
            $stmt->bindValue(':id_usuario', $this->__get('id_usuario'));

            if ($this->__get('nova_senha')) {
                $stmt->bindValue(':senha', md5($this->__get('nova_senha')));
            }

            return $stmt->execute();
        }

        // Método para converter array PostgreSQL para PHP
        private function postgreSQLToArray($pgArray) {
            if (!$pgArray || $pgArray === '{}') {
                return [];
            }

            // Remove as chaves {}
            $pgArray = trim($pgArray, '{}');

            // Separa os elementos
            $elements = str_getcsv($pgArray);

            // Remove aspas de cada elemento
            return array_map(function($item) {
                return trim($item, '"');
            }, $elements);
        }

        public function cadastrarUsuario(){
            // Converter arrays PHP para formato PostgreSQL
            $areas_interesse_pg = $this->arrayToPostgreSQL($this->__get('areas_interesse'));
            $modalidades_trabalho_pg = $this->arrayToPostgreSQL($this->__get('modalidades_trabalho'));
            $cargos_interesse_pg = $this->arrayToPostgreSQL($this->__get('cargos_interesse'));
            $niveis_interesse_pg = $this->arrayToPostgreSQL($this->__get('niveis_interesse'));

            // Arrays opcionais
            $instituicoes_pg = $this->arrayToPostgreSQL($this->__get('instituicoes'), true);
            $cursos_pg = $this->arrayToPostgreSQL($this->__get('cursos'), true);
            $datas_inicio_formacao_pg = $this->arrayToPostgreSQL($this->__get('datas_inicio_formacao'), true);
            $datas_conclusao_formacao_pg = $this->arrayToPostgreSQL($this->__get('datas_conclusao_formacao'), true);
            $cursando_pg = $this->booleanArrayToPostgreSQL($this->__get('cursando'), true);

            $empresas_pg = $this->arrayToPostgreSQL($this->__get('empresas'), true);
            $cargos_experiencia_pg = $this->arrayToPostgreSQL($this->__get('cargos_experiencia'), true);
            $datas_inicio_experiencia_pg = $this->arrayToPostgreSQL($this->__get('datas_inicio_experiencia'), true);
            $datas_termino_experiencia_pg = $this->arrayToPostgreSQL($this->__get('datas_termino_experiencia'), true);
            $trabalhando_atualmente_pg = $this->booleanArrayToPostgreSQL($this->__get('trabalhando_atualmente'), true);
            $descricoes_atividades_pg = $this->arrayToPostgreSQL($this->__get('descricoes_atividades'), true);

            $skills_pg = $this->arrayToPostgreSQL($this->__get('skills'), true);
            $tempos_experiencia_pg = $this->arrayToPostgreSQL($this->__get('tempos_experiencia'), true);
            $niveis_proficiencia_pg = $this->arrayToPostgreSQL($this->__get('niveis_proficiencia'), true);

            $beneficios_interesse_pg = $this->arrayToPostgreSQL($this->__get('beneficios_interesse'), true);

            // Criptografar senha com MD5
            $senha_md5 = md5($this->__get('senha'));

            $query = "INSERT INTO tb_usuario (
                nome, email, telefone, data_nascimento,
                cep, rua, numero, complemento, bairro, cidade, estado,
                areas_interesse,
                github,
                instituicoes, cursos, datas_inicio_formacao, datas_conclusao_formacao, cursando,
                empresas, cargos_experiencia, datas_inicio_experiencia, datas_termino_experiencia,
                trabalhando_atualmente, descricoes_atividades,
                skills, tempos_experiencia, niveis_proficiencia,
                modalidades_trabalho, cargos_interesse, niveis_interesse, beneficios_interesse,
                faixa_salarial, senha
            ) VALUES (
                :nome, :email, :telefone, :data_nascimento,
                :cep, :rua, :numero, :complemento, :bairro, :cidade, :estado,
                :areas_interesse,
                :github,
                :instituicoes, :cursos, :datas_inicio_formacao, :datas_conclusao_formacao, :cursando,
                :empresas, :cargos_experiencia, :datas_inicio_experiencia, :datas_termino_experiencia,
                :trabalhando_atualmente, :descricoes_atividades,
                :skills, :tempos_experiencia, :niveis_proficiencia,
                :modalidades_trabalho, :cargos_interesse, :niveis_interesse, :beneficios_interesse,
                :faixa_salarial, :senha
            ) RETURNING id_usuario";

            $stmt = $this->db->prepare($query);

            // Bind de valores
            $stmt->bindValue(':nome', $this->__get('nome'));
            $stmt->bindValue(':email', $this->__get('email'));
            $stmt->bindValue(':telefone', $this->__get('telefone'));
            $stmt->bindValue(':data_nascimento', $this->__get('data_nascimento'));
            $stmt->bindValue(':cep', $this->__get('cep'));
            $stmt->bindValue(':rua', $this->__get('rua'));
            $stmt->bindValue(':numero', $this->__get('numero'));
            $stmt->bindValue(':complemento', $this->__get('complemento'));
            $stmt->bindValue(':bairro', $this->__get('bairro'));
            $stmt->bindValue(':cidade', $this->__get('cidade'));
            $stmt->bindValue(':estado', $this->__get('estado'));
            $stmt->bindValue(':areas_interesse', $areas_interesse_pg);
            $stmt->bindValue(':github', $this->__get('github'));
            $stmt->bindValue(':instituicoes', $instituicoes_pg);
            $stmt->bindValue(':cursos', $cursos_pg);
            $stmt->bindValue(':datas_inicio_formacao', $datas_inicio_formacao_pg);
            $stmt->bindValue(':datas_conclusao_formacao', $datas_conclusao_formacao_pg);
            $stmt->bindValue(':cursando', $cursando_pg);
            $stmt->bindValue(':empresas', $empresas_pg);
            $stmt->bindValue(':cargos_experiencia', $cargos_experiencia_pg);
            $stmt->bindValue(':datas_inicio_experiencia', $datas_inicio_experiencia_pg);
            $stmt->bindValue(':datas_termino_experiencia', $datas_termino_experiencia_pg);
            $stmt->bindValue(':trabalhando_atualmente', $trabalhando_atualmente_pg);
            $stmt->bindValue(':descricoes_atividades', $descricoes_atividades_pg);
            $stmt->bindValue(':skills', $skills_pg);
            $stmt->bindValue(':tempos_experiencia', $tempos_experiencia_pg);
            $stmt->bindValue(':niveis_proficiencia', $niveis_proficiencia_pg);
            $stmt->bindValue(':modalidades_trabalho', $modalidades_trabalho_pg);
            $stmt->bindValue(':cargos_interesse', $cargos_interesse_pg);
            $stmt->bindValue(':niveis_interesse', $niveis_interesse_pg);
            $stmt->bindValue(':beneficios_interesse', $beneficios_interesse_pg);
            $stmt->bindValue(':faixa_salarial', $this->__get('faixa_salarial'));
            $stmt->bindValue(':senha', $senha_md5);

            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        // Método auxiliar para converter array PHP para formato PostgreSQL
        private function arrayToPostgreSQL($array, $optional = false) {
            if ($optional && (empty($array) || !is_array($array))) {
                return null;
            }

            if (!is_array($array)) {
                return null;
            }

            $escaped = array_map(function($v) {
                return '"' . str_replace('"', '\"', $v) . '"';
            }, $array);

            return '{' . implode(',', $escaped) . '}';
        }

        // Método auxiliar para converter array de booleanos para formato PostgreSQL
        private function booleanArrayToPostgreSQL($array, $optional = false) {
            if ($optional && (empty($array) || !is_array($array))) {
                return null;
            }

            if (!is_array($array)) {
                return null;
            }

            $escaped = array_map(function($v) {
                return $v ? 'true' : 'false';
            }, $array);

            return '{' . implode(',', $escaped) . '}';
        }



}


?>