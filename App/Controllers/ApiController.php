<?php 

namespace App\Controllers;

use App\Models\Protocolos;
use MF\Controller\Action;
use MF\Model\Container;
use MF\Model\Containeres;


class ApiController extends Action {

    public function Authenticate() {
        header('WWW-Authenticate: Basic realm="System Auth"');
        header('HTTP/1.0 401 Unauthorized');
        echo "You must enter a valid login ID and password to access this resource\n";
        exit;
    }

    public function Valid() {
        session_start();
        if($_SESSION['login'] = "1H=VN/E|AO:A#^M@LTE}"){
            return true;
        }

        header('Content-Type: application/json');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Methods: GET');

        $valid_passwords = array ("controle" => md5("1H=VN/E|AO:A#^M@LTE}"));
        $valid_users = array_keys($valid_passwords);

        $user = $_SERVER['PHP_AUTH_USER'];
        $pass = $_SERVER['PHP_AUTH_PW'];


        if (!isset($_SERVER['PHP_AUTH_USER'])){
            $this->Authenticate();

        } 
        
        else {
            $validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);
        }

        if (!$validated) {
            header('WWW-Authenticate: Basic realm="System Auth"');
            header('HTTP/1.0 401 Unauthorized');
            die ("Not authorized");
          } else {
            return true;
        }
    }

    public function matching(){
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        session_start();

        try {
            $Microserviço = Container::getModel('Microserviço');

            // Buscar dados do usuário e vagas
            $Microserviço->__set('id_usuario', $_SESSION['id_usuario']);
            $usuario = $Microserviço->getDadosUsuarioPorId();
            $vagas = $Microserviço->getDadosVagas();

            if (!$usuario) {
                throw new \Exception('Usuário não encontrado');
            }

            // Coeficientes da regressão linear múltipla (trabalho acadêmico)
            $BETAS = [2.847, 0.394, 0.267, 0.183, 0.089, 0.061];

            // Função helper para parsear arrays PostgreSQL ou PHP
            $parseArray = function($val) {
                // Se já é um array PHP, retorna direto
                if (is_array($val)) {
                    return array_filter($val);
                }

                // Se é string vazia ou nula
                if (empty($val) || $val === '{}') return [];

                // Se é string JSON do PostgreSQL
                if (is_string($val)) {
                    preg_match_all('/"([^"]+)"|([^,{}]+)/', trim($val, '{}'), $m);
                    return array_filter(array_map('trim', array_merge($m[1], $m[2])));
                }

                return [];
            };

            // Parse dados do usuário
            $uSkills = array_map('strtolower', $parseArray($usuario['skills'] ?? []));
            $uProf = $parseArray($usuario['niveis_proficiencia'] ?? []);
            $uTempos = $parseArray($usuario['tempos_experiencia'] ?? []);
            $uModalidades = array_map('strtolower', $parseArray($usuario['modalidades_trabalho'] ?? []));
            $uNiveis = array_map('strtolower', $parseArray($usuario['niveis_interesse'] ?? []));
            $uBeneficios = array_map('strtolower', $parseArray($usuario['beneficios_interesse'] ?? []));
            $uCargos = array_map('strtolower', $parseArray($usuario['cargos_interesse'] ?? []));
            $uEstado = strtoupper($usuario['estado'] ?? '');

            // Calcular anos de experiência do usuário
            $tempoMap = [
                'menos_1_ano' => 0.5,
                '1_2_anos' => 1.5,
                '2_3_anos' => 2.5,
                '3_5_anos' => 4,
                '5_mais_anos' => 6
            ];
            $uAnos = array_sum(array_map(function($t) use ($tempoMap) {
                return $tempoMap[$t] ?? 1;
            }, $uTempos));

            // Mapeamentos
            $profScore = ['basico' => 0.5, 'intermediario' => 0.75, 'avancado' => 1.0];
            $senioridadeAnos = [
                'estagiário' => 0, 'estagiario' => 0,
                'júnior' => 1, 'junior' => 1,
                'pleno' => 3,
                'sênior' => 5, 'senior' => 5,
                'especialista' => 7,
                'líder' => 6, 'lider' => 6,
                'gerente' => 8
            ];

            $matches = [];
            $linkedinCount = 0; // Contador para limitar LinkedIn a 10 vagas

            // Calcular match para cada vaga
            foreach ($vagas as $vaga) {
                $vSkills = array_map('strtolower', $parseArray($vaga['skills_requeridas'] ?? '{}'));
                $vModalidade = strtolower($vaga['modelo_trabalho'] ?? '');
                $vNivel = strtolower($vaga['nivel_senioridade'] ?? '');
                $vBeneficios = array_map('strtolower', $parseArray($vaga['beneficios'] ?? '{}'));
                $vEstado = strtoupper($vaga['estado'] ?? '');
                $vAnos = $vaga['experiencia_anos'] ?? ($senioridadeAnos[$vNivel] ?? 2);
                $vTitulo = strtolower($vaga['titulo'] ?? '');

                // ===== X1: Skill Match Score (0-100) =====
                $x1 = 50;
                if (!empty($vSkills)) {
                    $skillScore = 0;
                    $matchedSkills = [];
                    $missingSkills = [];

                    foreach ($vSkills as $vs) {
                        $found = false;
                        foreach ($uSkills as $i => $us) {
                            // Verificar match exato ou parcial
                            if ($us === $vs || strpos($us, $vs) !== false || strpos($vs, $us) !== false) {
                                $peso = $profScore[$uProf[$i] ?? 'intermediario'] ?? 0.75;
                                $skillScore += $peso * 100;
                                $matchedSkills[] = $vs;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $missingSkills[] = $vs;
                        }
                    }
                    $x1 = $skillScore / count($vSkills);
                }

                // ===== X2: Experience Score (0-100) =====
                $x2 = 100;
                if ($vAnos > 0) {
                    $ratio = $uAnos / $vAnos;
                    if ($ratio >= 1.0) {
                        // Experiência suficiente - penalizar levemente se muito acima
                        $x2 = 100 - max(0, ($ratio - 1.5) * 10);
                    } else {
                        // Experiência insuficiente - score proporcional
                        $x2 = $ratio * 100;
                    }
                }

                // ===== X3: Preference Alignment (0-100) =====
                $x3 = 0;

                // Modalidade de trabalho (30%)
                if (empty($vModalidade)) {
                    $x3 += 15;
                } elseif (!empty($uModalidades)) {
                    $modalidadeMatch = false;
                    foreach ($uModalidades as $uMod) {
                        if (strpos($vModalidade, $uMod) !== false || strpos($uMod, $vModalidade) !== false) {
                            $modalidadeMatch = true;
                            break;
                        }
                        // Remoto aceita híbrido
                        if ($uMod === 'remoto' && strpos($vModalidade, 'híbrido') !== false) {
                            $modalidadeMatch = true;
                            break;
                        }
                    }
                    $x3 += $modalidadeMatch ? 30 : 0;
                } else {
                    $x3 += 15;
                }

                // Localização/Estado (25%)
                if (empty($vEstado) || strpos($vModalidade, 'remoto') !== false) {
                    $x3 += 25;
                } elseif ($uEstado === $vEstado) {
                    $x3 += 25;
                } elseif (!empty($uEstado)) {
                    $x3 += 10;
                } else {
                    $x3 += 12.5;
                }

                // Benefícios (25%)
                if (!empty($uBeneficios) && !empty($vBeneficios)) {
                    $benMatch = 0;
                    foreach ($uBeneficios as $ub) {
                        foreach ($vBeneficios as $vb) {
                            $ubNorm = str_replace('_', ' ', $ub);
                            if (strpos($vb, $ubNorm) !== false || strpos($vb, $ub) !== false) {
                                $benMatch++;
                                break;
                            }
                        }
                    }
                    $x3 += ($benMatch / count($uBeneficios)) * 25;
                } else {
                    $x3 += 12.5;
                }

                // Nível de senioridade (20%)
                if (empty($vNivel)) {
                    $x3 += 10;
                } elseif (!empty($uNiveis)) {
                    $nivelMatch = false;
                    foreach ($uNiveis as $uNiv) {
                        if (strpos($vNivel, $uNiv) !== false || strpos($uNiv, $vNivel) !== false) {
                            $nivelMatch = true;
                            break;
                        }
                    }
                    $x3 += $nivelMatch ? 20 : 0;
                } else {
                    $x3 += 10;
                }

                // ===== X4: Interest Fit Score (0-100) =====
                $x4 = 50;
                if (!empty($uCargos)) {
                    $cargoMatches = 0;
                    foreach ($uCargos as $c) {
                        $cargoNorm = str_replace('_', ' ', $c);
                        if (strpos($vTitulo, $cargoNorm) !== false) {
                            $cargoMatches++;
                        }
                    }
                    $x4 = $cargoMatches > 0 ? min(100, ($cargoMatches / count($uCargos)) * 100 + 50) : 30;
                }

                // ===== X5: Hire Similarity Score (0-100) =====
                // Baseado na completude do perfil
                $campos = [
                    $usuario['skills'] ?? [],
                    $usuario['tempos_experiencia'] ?? [],
                    $usuario['cursos'] ?? '',
                    $usuario['github'] ?? '',
                    $usuario['descricoes_atividades'] ?? [],
                    $usuario['cargos_experiencia'] ?? [],
                    $usuario['modalidades_trabalho'] ?? [],
                    $usuario['cargos_interesse'] ?? [],
                    $usuario['niveis_interesse'] ?? [],
                    $usuario['beneficios_interesse'] ?? [],
                    $usuario['telefone'] ?? ''
                ];

                $camposPreenchidos = 0;
                foreach ($campos as $c) {
                    if (is_array($c)) {
                        // Se é array, verifica se tem elementos
                        if (!empty($c)) $camposPreenchidos++;
                    } else {
                        // Se é string, verifica se não está vazio e não é '{}'
                        $c = trim($c);
                        if (!empty($c) && $c !== '{}') $camposPreenchidos++;
                    }
                }

                $x5 = 50 + ($camposPreenchidos / count($campos)) * 50;

                // ===== MATCH FINAL =====
                $matchFinal = $BETAS[0]
                    + $BETAS[1] * $x1
                    + $BETAS[2] * $x2
                    + $BETAS[3] * $x3
                    + $BETAS[4] * $x4
                    + $BETAS[5] * $x5;

                // Normalizar para 0-100
                $matchFinal = max(0, min(100, $matchFinal));

                // DEBUG: Log de TODAS as vagas
                $plat = $vaga['plataforma'] ?? 'Desconhecida';
                if (!isset($debugStats[$plat])) {
                    $debugStats[$plat] = ['total' => 0, 'acima_48' => 0, 'scores' => []];
                }
                $debugStats[$plat]['total']++;
                $debugStats[$plat]['scores'][] = $matchFinal;
                if ($matchFinal >= 48) {
                    $debugStats[$plat]['acima_48']++;
                }

                // Filtrar apenas matches >= 48% (Moderado para cima)
                if ($matchFinal >= 48) {
                    // Limitar LinkedIn a 10 vagas
                    if ($plat === 'LinkedIn') {
                        if ($linkedinCount >= 10) {
                            continue; // Pula esta vaga do LinkedIn
                        }
                        $linkedinCount++;
                    }

                    // Determinar categoria
                    if ($matchFinal < 69) {
                        $cat = ['C3', 'Moderado', '#ffc107'];
                    } elseif ($matchFinal < 85) {
                        $cat = ['C4', 'Alto', '#28a745'];
                    } else {
                        $cat = ['C5', 'Excelente', '#20c997'];
                    }

                    $matches[] = [
                        'id_vaga' => $vaga['id_vaga'],
                        'titulo' => $vaga['titulo'],
                        'empresa' => $vaga['empresa'],
                        'cidade' => $vaga['cidade'],
                        'estado' => $vaga['estado'],
                        'modelo_trabalho' => $vaga['modelo_trabalho'],
                        'nivel_senioridade' => $vaga['nivel_senioridade'],
                        'url_original' => $vaga['url_original'],
                        'salario' => $vaga['salario'] ?? null,
                        'plataforma' => $plat, // Adicionar plataforma para usar no frontend
                        'match_final' => round($matchFinal, 2),
                        'categoria' => [
                            'codigo' => $cat[0],
                            'nome' => $cat[1],
                            'cor' => $cat[2]
                        ],
                        'metricas' => [
                            'skill_match_score' => round($x1, 2),
                            'relevant_experience_score' => round($x2, 2),
                            'preference_alignment_index' => round($x3, 2),
                            'interest_fit_score' => round($x4, 2),
                            'hire_similarity_score' => round($x5, 2)
                        ],
                        'skills_match' => [
                            'matched' => $matchedSkills ?? [],
                            'missing' => $missingSkills ?? []
                        ]
                    ];
                }
            }

            // Ordenar por match_final decrescente
            usort($matches, function($a, $b) {
                return $b['match_final'] <=> $a['match_final'];
            });

            // Processar estatísticas de debug
            $statsProcessadas = [];
            foreach ($debugStats as $plat => $data) {
                $statsProcessadas[$plat] = [
                    'total_vagas' => $data['total'],
                    'vagas_acima_48' => $data['acima_48'],
                    'percentual_aprovadas' => round(($data['acima_48'] / $data['total']) * 100, 1),
                    'score_medio' => round(array_sum($data['scores']) / count($data['scores']), 1),
                    'score_min' => round(min($data['scores']), 1),
                    'score_max' => round(max($data['scores']), 1)
                ];
            }

            echo json_encode([
                'success' => true,
                'total' => count($matches),
                'vagas' => $matches,
                'debug_stats' => $statsProcessadas  // TEMPORÁRIO PARA DEBUG
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function autenticar() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');
        
        try {
            // Verificar se é POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método não permitido');
            }

            // Validação básica
            if (!isset($_POST['email']) || !isset($_POST['senha'])) {
                throw new \Exception('E-mail e senha são obrigatórios');
            }

            // Instanciar modelo
            $Microserviço = Container::getModel('Microserviço');

            // Criptografar senha
            $senha_md5 = md5($_POST['senha']);

            // Setar credenciais
            $Microserviço->__set('email', $_POST['email']);
            $Microserviço->__set('senha', $senha_md5);

            // Autenticar usuário
            $usuario = $Microserviço->autenticarUsuario();

            if ($usuario) {
                // Iniciar sessão
                session_start();
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['autenticado'] = true;

                echo json_encode([
                    'success' => true,
                    'message' => 'Login realizado com sucesso!',
                    'usuario' => [
                        'id' => $usuario['id_usuario'],
                        'nome' => $usuario['nome'],
                        'email' => $usuario['email']
                    ]
                ]);
            } else {
                throw new \Exception('Email ou senha incorretos');
            }

        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getDadosUsuario() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');

        try {
            // Verificar se usuário está autenticado
            session_start();
            if (!isset($_SESSION['id_usuario']) || !$_SESSION['autenticado']) {
                throw new \Exception('Usuário não autenticado');
            }

            // Instanciar modelo
            $Microserviço = Container::getModel('Microserviço');
            $Microserviço->__set('id_usuario', $_SESSION['id_usuario']);

            // Buscar dados do usuário
            $usuario = $Microserviço->getDadosUsuarioPorId();

            if ($usuario) {
                echo json_encode([
                    'success' => true,
                    'usuario' => $usuario
                ]);
            } else {
                throw new \Exception('Usuário não encontrado');
            }

        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function atualizarUsuario() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        try {
            // Verificar se usuário está autenticado
            session_start();
            if (!isset($_SESSION['id_usuario']) || !$_SESSION['autenticado']) {
                throw new \Exception('Usuário não autenticado');
            }

            // Verificar se é POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método não permitido');
            }

            // Validação básica
            if (!isset($_POST['nome']) || !isset($_POST['telefone'])) {
                throw new \Exception('Campos obrigatórios não preenchidos');
            }

            // Instanciar modelo
            $Microserviço = Container::getModel('Microserviço');

            // Setar ID do usuário da sessão
            $Microserviço->__set('id_usuario', $_SESSION['id_usuario']);

            // Setar dados do formulário
            $Microserviço->__set('nome', $_POST['nome']);
            $Microserviço->__set('telefone', $_POST['telefone']);
            $Microserviço->__set('data_nascimento', $_POST['data_nascimento'] ?? null);
            $Microserviço->__set('cep', $_POST['cep'] ?? null);
            $Microserviço->__set('rua', $_POST['rua'] ?? null);
            $Microserviço->__set('numero', $_POST['numero'] ?? null);
            $Microserviço->__set('complemento', $_POST['complemento'] ?? null);
            $Microserviço->__set('bairro', $_POST['bairro'] ?? null);
            $Microserviço->__set('cidade', $_POST['cidade'] ?? null);
            $Microserviço->__set('estado', $_POST['estado'] ?? null);
            $Microserviço->__set('github', $_POST['github'] ?? null);
            $Microserviço->__set('modalidades_trabalho', $_POST['modalidade_trabalho'] ?? []);
            $Microserviço->__set('cargos_interesse', $_POST['cargos_interesse'] ?? []);
            $Microserviço->__set('niveis_interesse', $_POST['nivel_interesse'] ?? []);
            $Microserviço->__set('beneficios_interesse', $_POST['beneficios_interesse'] ?? []);
            $Microserviço->__set('faixa_salarial', $_POST['faixa_salarial'] ?? null);

            // Verificar se quer alterar senha
            if (!empty($_POST['nova_senha'])) {
                $Microserviço->__set('nova_senha', $_POST['nova_senha']);
            }

            // Executar atualização
            $result = $Microserviço->atualizarUsuario();

            if ($result) {
                // Atualizar nome na sessão se foi alterado
                $_SESSION['nome'] = $_POST['nome'];

                echo json_encode([
                    'success' => true,
                    'message' => 'Dados atualizados com sucesso!'
                ]);
            } else {
                throw new \Exception('Erro ao atualizar dados no banco');
            }

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function cadastrarUsuario() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        try {
            // Verificar se é POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método não permitido');
            }

            // Validação básica
            if (!isset($_POST['nome']) || !isset($_POST['email']) || !isset($_POST['telefone']) || !isset($_POST['senha'])) {
                throw new \Exception('Campos obrigatórios não preenchidos');
            }

            // Validar email
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Email inválido');
            }

            // Instanciar modelo
            $Microserviço = Container::getModel('Microserviço');

            // Verificar se email já existe
            $Microserviço->__set('email', $_POST['email']);
            $validationEmail = $Microserviço->getUser();

            if ($validationEmail) {
                throw new \Exception('Este email já está cadastrado');
            }

            // Setar todos os dados no modelo
            $Microserviço->__set('nome', $_POST['nome']);
            $Microserviço->__set('email', $_POST['email']);
            $Microserviço->__set('telefone', $_POST['telefone']);
            $Microserviço->__set('data_nascimento', $_POST['data_nascimento'] ?? null);
            $Microserviço->__set('cep', $_POST['cep'] ?? null);
            $Microserviço->__set('rua', $_POST['rua'] ?? null);
            $Microserviço->__set('numero', $_POST['numero'] ?? null);
            $Microserviço->__set('complemento', $_POST['complemento'] ?? null);
            $Microserviço->__set('bairro', $_POST['bairro'] ?? null);
            $Microserviço->__set('cidade', $_POST['cidade'] ?? null);
            $Microserviço->__set('estado', $_POST['estado'] ?? null);
            $Microserviço->__set('areas_interesse', $_POST['area_interesse'] ?? []);
            $Microserviço->__set('github', $_POST['github'] ?? null);
            $Microserviço->__set('instituicoes', $_POST['instituicao'] ?? []);
            $Microserviço->__set('cursos', $_POST['curso'] ?? []);
            $Microserviço->__set('datas_inicio_formacao', $_POST['data_inicio'] ?? []);
            $Microserviço->__set('datas_conclusao_formacao', $_POST['data_conclusao'] ?? []);
            $Microserviço->__set('cursando', $_POST['cursando'] ?? []);
            $Microserviço->__set('empresas', $_POST['empresa'] ?? []);
            $Microserviço->__set('cargos_experiencia', $_POST['cargo'] ?? []);
            $Microserviço->__set('datas_inicio_experiencia', $_POST['exp_data_inicio'] ?? []);
            $Microserviço->__set('datas_termino_experiencia', $_POST['exp_data_termino'] ?? []);
            $Microserviço->__set('trabalhando_atualmente', $_POST['trabalhando'] ?? []);
            $Microserviço->__set('descricoes_atividades', $_POST['descricao_atividades'] ?? []);
            $Microserviço->__set('skills', $_POST['skill'] ?? []);
            $Microserviço->__set('tempos_experiencia', $_POST['tempo_experiencia'] ?? []);
            $Microserviço->__set('niveis_proficiencia', $_POST['nivel'] ?? []);
            $Microserviço->__set('modalidades_trabalho', $_POST['modalidade_trabalho'] ?? []);
            $Microserviço->__set('cargos_interesse', $_POST['cargos_interesse'] ?? []);
            $Microserviço->__set('niveis_interesse', $_POST['nivel_interesse'] ?? []);
            $Microserviço->__set('beneficios_interesse', $_POST['beneficios_interesse'] ?? []);
            $Microserviço->__set('faixa_salarial', $_POST['faixa_salarial'] ?? null);
            $Microserviço->__set('senha', $_POST['senha']);

            // Executar cadastro
            $result = $Microserviço->cadastrarUsuario();

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuário cadastrado com sucesso!',
                    'id_usuario' => $result['id_usuario']
                ]);
            } else {
                throw new \Exception('Erro ao inserir dados no banco');
            }

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


}
  



?>