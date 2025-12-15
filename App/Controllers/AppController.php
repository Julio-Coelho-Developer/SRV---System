<?php 

namespace App\Controllers;

use App\Models\Protocolos;
use MF\Controller\Action;
use MF\Model\Container;
use MF\Model\Containeres;


class AppController extends Action {    

   
    /*Função que tem como objetivo avalisar se o o usuário está logado, usando o session_start() aqui
    me permite não declarar ele caso eu declare essa função no restante do escopo do código.*/
    public function validaAutenticacao(){
        session_start();
        if(!isset($_SESSION['login']) || ($_SESSION['login']) == ''){
            header('Location: /');
        }
    }

    /*Página inicial após logar, onde é possível criar,excluir e configurar todos os 
    fluxogramas do sistema, aqui o usuário pode criar o fluxograma que quiser e vai ter acesso
    nos gráficos ao macro do sistema.*/
    public function Listagem(){

        session_start();

        $this->render('listagem');
    }

    public function configuracao(){
        $this->render('configuracao');
    }

    //Pegar todos os Fluxogramas do sistema, serve como uma api, também. 
    public function getFlowChartAll(){
        $Fluxogramas = Containeres::getModelo('Fluxogramas');
        $Fluxograma = $Fluxogramas->getflow();
        echo json_encode($Fluxograma);
    }

    //Pegar todos os assuntos vinculados a um fluxograma. 
    public function getFlowSubject(){
        $Fluxograma = Containeres::getModelo('Fluxogramas');
        $Assunto = Container::getModel('Protocolos');

        $Fluxograma->__set('fluxograma_id', $_POST['fluxograma_id']);
        $subject_id = $Fluxograma->getFlowChartAll(); 

        $Subjects = $Assunto->getSubject(); 

            
        foreach($subject_id as $value){
            foreach($Subjects as $key => $values){
                if($Subjects[$key]['id'] == $value['assunto_id']){
                    $Subjects[$key]['selected'] = true;
                } 
            }
        }

        if(!empty($value['titulo'])){
            $Subjects['titulo'] = $value['titulo']; 
        }

        echo json_encode($Subjects);


    }

    
    /*Criar fluxograma.*/
    public function criarfluxograma(){
        $this->validaAutenticacao();

        $Fluxograma = Containeres::getModelo('Fluxogramas'); 

        /* Criar o fluxograma, como nome do título */
        $Fluxograma->__set('titulo', $_POST['titulo']);
        $fluxograma_id = $Fluxograma->criarfluxograma();

       
        $Fluxograma->__set('fluxograma_id', $fluxograma_id['id']);
        foreach($_POST['assunto'] as $assunto_id ){
            $Fluxograma->__set('assunto_id', $assunto_id);
            $Fluxograma->vincular_fluxograma();
        }

       
        header('Location: /fluxogramas');
    }

    /*Essa função me permite deletar um fluxograma, ela vai como efeito cascata deletar todos
    os fluxogramas restantes */ 
    public function deletarFluxograma(){
        $this->validaAutenticacao();

        $user = Containeres::getModelo('Fluxogramas'); 
        $user->__set('titulo', $_POST['titulo']);
        $validacao = $user->deletarFluxograma();

        if(empty($validacao['id'])){
            header('Location: /fluxogramas?Codigo=1');
        }else{
            header('Location: /fluxogramas');
        }
     
    }

    public function editarFluxograma(){
        $this->validaAutenticacao();
        $Fluxograma = Containeres::getModelo('Fluxogramas');

        $Fluxograma->__set('fluxograma_id', $_POST['fluxograma_id']);
        $Fluxograma->__set('titulo', $_POST['titulo']);
        $Fluxograma->updateFlowChartTitle(); //Nome alterado. 
        $Fluxograma->deleteFlowChartTitle(); //Nome alterado. 

        foreach($_POST['assunto'] as $assunto_id ){
            $Fluxograma->__set('assunto_id', $assunto_id);
            $Fluxograma->vincular_fluxograma();
        }

        header('Location: /fluxogramas');
    }

    public function getAssuntoVinculado(){
        $this->validaAutenticacao();

        $user = Containeres::getModelo('Fluxogramas'); 
        $user->__set('titulo', $_POST['titulo']);
        $validacao = $user->deletarFluxograma();

        if(empty($validacao['id'])){
            header('Location: /fluxogramas?Codigo=1');
        }else{
            header('Location: /fluxogramas');
        }
     
    }

    public function anotacoes(){
        $this->validaAutenticacao();

        $anotações = Containeres::getModelo('Fluxogramas');

        $anotações->__set('protocolo_id', $_POST['protocolo_id']);
        $anotações->__set('descricao', $_POST['anotacao']);
        $existe = $anotações->getAnotacao();

        if($existe){
            $anotações->editarAnotacao();
            echo json_encode("sucesso");
            exit();
        }else{
            $anotações->criarAnotacao();
            echo json_encode("sucesso");
            exit();
        }
    }

    /*Essa função chama a página principal do sistema, ela é o fluxograma criado pelo usuário
    e pode ser de vários tipos (Denuncia , Ética... Entre outros)*/
    public function Processos(){
        $this->validaAutenticacao();
        
        $protocolo = Container::getModel('Protocolos');
        $Fluxos = Containeres::getModelo('Fluxogramas');
    
       
        $Fluxos->__set('fluxograma_id', $_GET['fluxograma_id']);
        $protocoloAssunto = $Fluxos->getFlowChartAll(); 

        $Protocolos = [];
        $passos = [];


       

        foreach($protocoloAssunto as $chave => $valor){
            $protocolo->__set('assunto_id',$valor['assunto_id']);
            $anotacao = $protocolo->getProtocolos(); 
            
            foreach($anotacao as $key => $value){
                $Fluxos->__set('protocolo_id',$value['id']);
                $notas = $Fluxos->getAnotacao(); 
                if(isset($notas['descricao'])){
                    $anotacao[$key]['anotacao'] = $notas['descricao'];
                }
            }

            array_push($Protocolos, $anotacao);
        }

    

        $Alerta = $Fluxos->getPassos(); 
                
        foreach($Protocolos as $chave => $protocolo){
            foreach($protocolo as $chave2 => $valor){
                $ingles = array("year","years","mon ","mons","day","days");
                $portuguese =  array("ano","anos","mês ","meses","dia","dias");
                $Protocolos[$chave][$chave2]['dias'] = str_replace($ingles, $portuguese,$valor['dias']);


                if($Protocolos[$chave][$chave2]['tempo_prescricao'] < 0){
                    $Protocolos[$chave][$chave2]['tempo_prescricao'] = 'Venceu prazo de prescrição';
                }else{
                    
                    $Protocolos[$chave][$chave2]['tempo_prescricao'] = str_replace($ingles, $portuguese,$valor['tempo_prescricao']);
                }
              
            }
        }
        
        
        foreach($Protocolos as $chave_1 => $protocolo){
            foreach($protocolo as $chave_2 => $valor){
                foreach($Alerta as $chave_3 => $prazo){
                    if($Protocolos[$chave_1][$chave_2]['motivo_id'] == $prazo['numero'] && $Protocolos[$chave_1][$chave_2]['tempo'] > $prazo['alerta']){
                        $Protocolos[$chave_1][$chave_2]['tempo_parado'] = true;
                        break;
                    }
                    else{
                        $Protocolos[$chave_1][$chave_2]['tempo_parado'] = false;
                    }
                }
            }
        }

        $this->view->protocolos = $Protocolos; 
        $this->render('Processos');       
        
    }

    public function Filtro(){
        $this->validaAutenticacao();
        
        $protocolo = Container::getModel('Protocolos');
        $Fluxos = Containeres::getModelo('Fluxogramas');
    
       
        $Fluxos->__set('fluxograma_id', $_GET['fluxograma_id']);
        $protocoloAssunto = $Fluxos->getFlowChartAll(); 

        $Protocolos = [];
        $passos = [];


        foreach($protocoloAssunto as $chave => $valor){
            $protocolo->__set('assunto_id',$valor['assunto_id']);

            if(!empty($_GET['motivo_id'])){

                $protocolo->__set('motivo_id',$_GET['motivo_id']);
                $protocolo->__set('data_inicio',$_GET['data_inicio']);
                $protocolo->__set('data_fim',$_GET['data_fim']);

                //Historico
                $anotacao = $protocolo->getProtocoloFiltragem(); 


            }
            else if(!empty($_GET['js-tipo-filtro'])){
                $anotacao = $protocolo->getProtocolosFiltro(); 
            }else{
                $anotacao = $protocolo->getProtocolos(); 
            }

            
            foreach($anotacao as $key => $value){
                $Fluxos->__set('protocolo_id',$value['id']);
                $notas = $Fluxos->getAnotacao(); 
                if(isset($notas['descricao'])){
                    $anotacao[$key]['anotacao'] = $notas['descricao'];
                }
            }

            array_push($Protocolos, $anotacao);
        }

    

        $Alerta = $Fluxos->getPassos(); 
                
        foreach($Protocolos as $chave => $protocolo){
            foreach($protocolo as $chave2 => $valor){
                $ingles = array("year","years","mon ","mons","day","days");
                $portuguese =  array("ano","anos","mês ","meses","dia","dias");
                $Protocolos[$chave][$chave2]['dias'] = str_replace($ingles, $portuguese,$valor['dias']);


                if($Protocolos[$chave][$chave2]['tempo_prescricao'] < 0){
                    $Protocolos[$chave][$chave2]['tempo_prescricao'] = 'Venceu prazo de prescrição';
                }else{
                    
                    $Protocolos[$chave][$chave2]['tempo_prescricao'] = str_replace($ingles, $portuguese,$valor['tempo_prescricao']);
                }
              
            }
        }
        
        
        foreach($Protocolos as $chave_1 => $protocolo){
            foreach($protocolo as $chave_2 => $valor){
                foreach($Alerta as $chave_3 => $prazo){
                    if($Protocolos[$chave_1][$chave_2]['motivo_id'] == $prazo['numero'] && $Protocolos[$chave_1][$chave_2]['tempo'] > $prazo['alerta']){
                        $Protocolos[$chave_1][$chave_2]['tempo_parado'] = true;
                        break;
                    }
                    else{
                        $Protocolos[$chave_1][$chave_2]['tempo_parado'] = false;
                    }
                }
            }
        }

        
        $this->view->protocolos = $Protocolos; 

        $this->render('Filtros');       
    }

    
    public function PageKanban(){
        $this->validaAutenticacao();
        $this->render('kanban');       
    }



    /* Retornar todos os cards do fluxograma de ética*/
    public function getAllFlowSteps(){
        $this->validaAutenticacao(); //Pegar o usuario do session e autenticaçaõo é o user completo.
        $Fluxos = Containeres::getModelo('Fluxogramas');
        $protocolo = Container::getModel('Protocolos');


        $Fluxos->__set('fluxograma_id', 45);
        $Fluxograma = $Fluxos->getFluxos();

        $protocoloAssunto = $Fluxos->getFlowChartAll(); 
        
        $Protocolos = [];

        foreach($protocoloAssunto as $chave => $valor){
            $protocolo->__set('assunto_id',$valor['assunto_id']);
            array_push($Protocolos, $protocolo->getNumberProtocolSteps());
        }

      

        foreach($Fluxograma as $chave_1 => $Flow){
            foreach($Protocolos as $chave_2 => $Flow){
                foreach($Flow as $chave_3 => $Protocol){

                    if($Fluxograma[$chave_1]['chave_subfluxograma']){
                        $codigo = explode(",",$Fluxograma[$chave_1]['codigos']);
                        foreach($codigo as $valores){
                            if($valores == $Protocol['motivo_id']){
                                $Fluxograma[$chave_1]['quantidade'] =  $Fluxograma[$chave_1]['quantidade'] + $Protocol['count'];
                            }
                        }

                       
                    }else{
                        if($Fluxograma[$chave_1]['codigo'] == $Protocol['motivo_id']){
                            $Fluxograma[$chave_1]['quantidade'] = $Protocol['count'];
                        }

                    }
                 
                }  
            }
        }

        echo json_encode($Fluxograma);
        
    }

    public function getFlowSteps(){
        $this->validaAutenticacao(); 
        $Fluxos = Containeres::getModelo('Fluxogramas');
        $protocolo = Container::getModel('Protocolos');

        $Fluxos->__set('etapa_id', $_POST['etapa_id']);
        $Fluxos->__set('fluxograma_id', $_POST['fluxograma_id']);

        $Fluxograma = $Fluxos->getSubFlowStep();

        $protocoloAssunto = $Fluxos->getFlowChartAll(); 
        
        $Protocolos = [];

      
        
        foreach($protocoloAssunto as $chave => $valor){
            $protocolo->__set('assunto_id',$valor['assunto_id']);
            array_push($Protocolos, $protocolo->getNumberProtocolSteps());
        }

         
        foreach($Fluxograma as $chave => $Flow){
            foreach($Protocolos as $keys => $Protocols){
                foreach($Protocols as $key => $Resultado)
                if($Fluxograma[$chave]['codigo'] == $Protocolos[$keys][$key]['motivo_id']){
                    $Fluxograma[$chave]['quantidade'] = $Protocolos[$keys][$key]['count'];
                }
            }    
        }
        
        
        echo json_encode($Fluxograma);
    }

   

    public function Protocolo(){

        /* Isso aqui não está funcionando por N-motivos. */ 
        $this->validaAutenticacao();
        $protocolo = Container::getModel('Protocolos');
        $Fluxos = Containeres::getModelo('Fluxogramas');

        $protocolo->__set('protocolo_id', $_GET['id']);


        $Protocolo = $protocolo->getStepsMovement(); 
        $Cards = $protocolo->getCardsProtocolos(); 
        $MotivoMovimento = $protocolo->getMotivoMovimento();

        foreach($Protocolo as $key => $value){
            $ingles = array("year","years","mon ","mons","day","days");
            $portuguese =  array("ano","anos","mês ","meses","dia","dias");
            $Protocolo[$key]['tempo_prescricao'] = str_replace($ingles, $portuguese,$value['tempo_prescricao']);
        }

        $this->view->protocolo = $Protocolo; 
        $this->view->Cards = $Cards;        
        $this->view->motivoMovimento = $MotivoMovimento; 

        $this->render('Protocolo');   
    }

    public function getStep(){
        $this->validaAutenticacao();
        $protocol = Container::getModel('Protocolos');
        $Fluxos = Containeres::getModelo('Fluxogramas');

        $Protocol = $protocol->getStep();
        
        echo json_encode($Protocol);
    }



   
    public function Denuncia(){        
        $denuncia = Container::getModel('Protocolos');
        $getTableData = $denuncia->getTableData(); 
        
        foreach($getTableData as $chave => $protocolo){
            $ingles = array("year","years","mon ","mons","day","days");
            $portuguese =  array("ano","anos","mês ","meses","dia","dias");
            $getTableData[$chave]['dias'] = str_replace($ingles, $portuguese,$protocolo['dias']);
        }
        

        $this->view->denuncias = $getTableData; 
        $this->render('Denuncia');   
    }

    
    public function getProtocoloFiltro(){      

        $this->validaAutenticacao();
        
        $protocolo = Container::getModel('Protocolos');
        $Fluxos = Containeres::getModelo('Fluxogramas');
    
       
        $Fluxos->__set('fluxograma_id', $_POST['fluxograma_id']);
        $protocoloAssunto = $Fluxos->getFlowChartAll(); 

        $Protocolos = [];
        $passos = [];
       

        foreach($protocoloAssunto as $chave => $valor){
            $protocolo->__set('assunto_id',$valor['assunto_id']);

            if(!empty($_POST['motivo_id'])){

                $protocolo->__set('motivo_id',$_POST['motivo_id']);
                $protocolo->__set('data_inicio',$_POST['data_inicio']);
                $protocolo->__set('data_fim',$_POST['data_fim']);

                $anotacao = $protocolo->getProtocoloSetorFiltragem(); 

            }else if(!empty($_POST['filtro']) && $_POST['filtro'] == 'conselheiro'){
                $anotacao = $protocolo->getProtocoloConselheiro(); 
            }else{
                $anotacao = $protocolo->getProtocoloUsuarios(); 
            }

            array_push($Protocolos, $anotacao);
        }

        echo json_encode(array_values($Protocolos));
    }

    
    public function getProtocoloFiltroLocal(){      

        $this->validaAutenticacao();
        
        $protocolo = Container::getModel('Protocolos');
        $Fluxos = Containeres::getModelo('Fluxogramas');
    
       
        $Fluxos->__set('fluxograma_id', $_POST['fluxograma_id']);
        $protocoloAssunto = $Fluxos->getFlowChartAll(); 

        $Protocolos = [];
        $passos = [];


       

        foreach($protocoloAssunto as $chave => $valor){
            $protocolo->__set('assunto_id',$valor['assunto_id']);

            if(!empty($_POST['motivo_id'])){

                $protocolo->__set('motivo_id',$_POST['motivo_id']);
                $protocolo->__set('data_inicio',$_POST['data_inicio']);
                $protocolo->__set('data_fim',$_POST['data_fim']);
                //$protocolo->__set('usuario',$_GET['usuario']);

                $anotacao = $protocolo->getProtocoloSetorFiltragem(); 

            }
            else if(!empty($_POST['filtro']) && $_POST['filtro'] == 'conselheiro'){
                $anotacao = $protocolo->getProtocoloCamara(); 
            }else{
                $anotacao = $protocolo->getProtocoloSetor(); 
            }

            array_push($Protocolos, $anotacao);
        }

        echo json_encode(array_values($Protocolos));
    }

    public function getTypeComplaint(){
        $denuncia = Container::getModel('Protocolos');
      
        $getTypeComplaint = $denuncia->getTypeComplaint(); 
        echo json_encode($getTypeComplaint);
    }

    
    public function getTableData(){
        $denuncia = Container::getModel('Protocolos');
      
        $getTableData = $denuncia->getTableData(); 
        echo json_encode($getTableData);
    }

    public function getTempoPrescricao(){
        $prescricao = Container::getModel('Protocolos');

        $prescricao->__set('protocolo_id',$_POST['protocolo_id']);
        $prescricao->__set('motivo_id',342);

        $prescricaoTempo = $prescricao->getTempoPreescricao(); 
        
    
        if($prescricaoTempo){

            foreach($prescricaoTempo as $key => $value){
                $ingles = array("year","years","mon ","mons","day","days");
                $portuguese =  array("ano","anos","mês ","meses","dia","dias");
                $prescricaoTempo[$key] = str_replace($ingles, $portuguese,$value);
            }

            if($prescricaoTempo['tempo_prescricao'] < 0){
                $prescricaoTempo['tempo_prescricao'] = str_replace('-','',$prescricaoTempo['tempo_prescricao'] );                
                $mensagem = 'A prescrição do processo ocorreu a ' . $prescricaoTempo['tempo_prescricao'] . '.';
            }else{
                $mensagem = 'Faltam ' . $prescricaoTempo['tempo_prescricao'] . ' para a prescrição do processo.';
            }

            echo json_encode(array("erro" => false,"mensagem" => $mensagem, "codigo" => 4,"dados" => $prescricaoTempo));
            exit();
        }else{
            echo json_encode(array("erro" => true,"mensagem" => "Tempo de preescrição não descoberto, pois não existe o passo de conhecimento das partes cadastrado.", "codigo" => 3)); 
            exit();
        }

    }

    /* Querys referentes a tela de denuncia (Fim) */


    public function criarEtapa(){
        $this->validaAutenticacao();

        $etapa = Containeres::getModelo('Fluxogramas');
        
       
        if(empty($_POST['numero']) || ( empty($_POST['fluxograma_id']) && empty($_POST['subfluxograma']) )  ||
        empty($_POST['titulo'])){
            echo json_encode(array("erro" => true,"mensagem" => "Existe campo(s) nulo(s).", "codigo" => 3,"dados" => $_POST));
            exit(); 
        }

  
        $etapa->__set('numero', $_POST['numero']);
        $etapa->__set('codigo', $_POST['codigo']);
        $etapa->__set('alerta', $_POST['alerta']);
        $etapa->__set('prazo', $_POST['prazo']);
        $etapa->__set('titulo', $_POST['titulo']);
    
      
        if($_POST['documento'] == 'fluxograma'){
            $etapa->__set('fluxo', $_POST['fluxo']);
            $etapa->__set('fluxograma_id', $_POST['fluxograma_id']);
            $passo = $etapa->criarEtapa(); 

            if($_POST['fluxo']){
                $etapa->__set('etapa_id', $passo['id']);
                $sub_passo = $etapa->criarSubfluxograma(); 
            }

        }else if ($_POST['documento'] == 'sub-fluxograma'){
            $etapa->__set('etapa_id', $_POST['fluxo']);
            $sub_passo = $etapa->criarSubfluxograma(); 
        }

        echo json_encode(array("erro" => false,"mensagem" => "Etapa cadastrada com sucesso.", "codigo" => 1,"dados" => null));

    }

    public function getEtapa(){
        $this->validaAutenticacao();

        $fluxograma = Container::getModel('Fluxograma'); 
        $fluxograma->__set('etapa_id', $_POST['etapa_id']);
        $Etapas = $fluxograma->getEtapa();


        echo json_encode($Etapas);
    }

    public function getMotivo(){
        $this->validaAutenticacao();
        $Fluxos = Containeres::getModelo('Fluxogramas');
        $motivo = Container::getModel('Protocolos'); 
        $Motivos = $motivo->getStep();

        foreach($Motivos as $key => $value){
            $Fluxos->__set('etapa_id', $value['id']);
            $x = $Fluxos->getMotivoFluxograma();
            
            if($x){
                $Motivos[$key] =  array_merge($Motivos[$key], $x);
            }

        }

        echo json_encode($Motivos);
    }

    public function getUsers(){
        $this->validaAutenticacao();
        $usuario = Container::getModel('Protocolos'); 
        $dados = $usuario->getUsuarios();

        echo json_encode($dados);
    }


    

    public function getSubFlow(){
        $this->validaAutenticacao();

        $Fluxos = Containeres::getModelo('Fluxogramas');

        $Fluxos->__set('fluxograma_id', $_POST['fluxograma_id']);
        $etapaSubFluxograma = $Fluxos->getSubFlow();

        echo json_encode($etapaSubFluxograma);
    }

    public function getNumberProtocol(){
        $this->validaAutenticacao();
        $protocol = Container::getModel('Protocolos'); 

        $Protocol = explode(",", $_POST['dados']);
        $protocol->__set('numero', $Protocol[0]);
        $protocol->__set('ano', $Protocol[1]);
        $protocol->protocolo_id();


        echo json_encode($protocol->protocolo_id());

    }


    public function alterarEtapa(){
        $this->validaAutenticacao();

        $protocolo = Container::getModel('Protocolos');
    
            $protocolos = explode(",", $_POST['numeroProtocolo']);

            foreach($protocolos as $chave => $valor){

                $numero = explode("/", $protocolos[$chave]);
            
                if(empty($numero[0]) || empty($numero[1])){
                    echo json_encode(array("resultado" => "erro","mensagem" => "Protocolo não encontrado", "codigo" => 1, "dados" => null));
                    exit(); 
                }
                
                $protocolo->__set('numero', $numero[0]);
                $protocolo->__set('ano', $numero[1]);
    
    
                $getProtocolo = $protocolo->getProtocolo();
    
                if($getProtocolo == false){
                    echo json_encode(array("resultado" => "erro","mensagem" => "Protocolo não encontrado", "codigo" => 1,"dados" => null));
                    exit(); 
                }
    
                $protocolo->__set('protocolo_id', $getProtocolo['protocolo_id']);
                $protocolo->__set('passo', $getProtocolo['passo']);
                $protocolo->__set('motivo_id', $_POST['motivo_id']);
                
         
                $retorno = $protocolo->alterarEtapa();

            }
            
          
        echo json_encode(array("erro" => false,"mensagem" => "Etapa alterada com sucesso.", "codigo" => 1,"dados" => null));
    }

    

    public function alterarNumeroMotivo(){
        $this->validaAutenticacao();

        $protocolo = Container::getModel('Protocolos');
        $fluxograma = Containeres::getModelo('Fluxogramas'); 

    
        $protocolo->__set('numero-motivo', $_POST['numero-motivo']);
        $protocolo->__set('motivo_id', $_POST['motivo_id']);

        $protocolo->__set('numero', $_POST['numero-motivo']);
        $existe = $protocolo->getMotivoMovimentoUnico();


        if(!$existe){

            $dados = $protocolo->alterarNumeroMotivo();

            if(isset($dados)){
                $fluxograma->__set('codigo', $dados['id']);
                $fluxograma->__set('motivo_id', $_POST['motivo_id']);
    
                $fluxoDados = $fluxograma->alterarMotivoFluxograma();
                $fluxoSubDados = $fluxograma->alterarMotivoSubFluxograma();
              
                if($dados && ($fluxoDados || $fluxoSubDados)){
                    echo json_encode(array("erro" => false,"mensagem" => "Número alterado com sucesso.", "codigo" => 1,"dados" => null));
                }

            }
        }else{
            echo json_encode(array("resultado" => true,"mensagem" => "Já existe uma etapa com esse número. "));
            exit(); 
        }

       
    }

    public function getInfoEtapa(){
        $this->validaAutenticacao();
        $Fluxograma = Containeres::getModelo('Fluxogramas');

        //Encontrar uma forma de pegar os dados, e retornar o motivo correto, além de transformar o input em um chosen. 
        $Fluxograma->__set('etapa_id', $_POST['etapa_id']);
        $infoEtapa = $Fluxograma->getEtapa();
        echo json_encode($infoEtapa);

    }

    public function getInfoSubfluxograma(){
        $this->validaAutenticacao();
        $Fluxograma = Containeres::getModelo('Fluxogramas');

        //Encontrar uma forma de pegar os dados, e retornar o motivo correto, além de transformar o input em um chosen. 
        $Fluxograma->__set('etapa_id', $_POST['etapa_id']);
        $infoSubfluxograma = $Fluxograma->getSubFlowStep();
        echo json_encode($infoSubfluxograma);

    }

    public function getInfoSubfluxogramaEtapa(){
        $this->validaAutenticacao();
        $Fluxograma = Containeres::getModelo('Fluxogramas');

        //Encontrar uma forma de pegar os dados, e retornar o motivo correto, além de transformar o input em um chosen. 
        $Fluxograma->__set('etapa_id', $_POST['etapa_id']);
        $infoSubfluxograma = $Fluxograma->getSubFlowStepDate();
        echo json_encode($infoSubfluxograma);

    }




    public function editarEtapa(){
        $this->validaAutenticacao();

        $etapa = Containeres::getModelo('Fluxogramas'); 
        $sitac = Container::getModel('Protocolos');

        

        if(empty($_POST['documento']) || empty($_POST['titulo']) ||  empty($_POST['etapa'])){

            echo json_encode(array("erro" => true,"mensagem" => "Existe campo(s) nulo(s).", "codigo" => 3,"dados" => $_POST));
            exit(); 
            
        }

        //
        $etapa->__set('numero', $_POST['numero']);
        $etapa->__set('codigo', $_POST['codigo']);
        $etapa->__set('alerta', $_POST['alerta']);
        $etapa->__set('prazo', $_POST['prazo']);
        $etapa->__set('titulo', $_POST['titulo']);
        $etapa->__set('etapa', $_POST['etapa']);

        $sitac->__set('codigo', $_POST['codigo']);
        $sitac->__set('titulo', $_POST['titulo']);

        $etapa->__set('etapa_id', $_POST['etapa']);

        $etapa->__set('codigo', $_POST['codigo']);

        if($_POST['codigo']){
            $antigo = $etapa->getCodigo(); 
        }

        if($_POST['documento'] == 'fluxograma'){
            $etapa->__set('fluxo', $_POST['fluxo']);

            $etapa->__set('etapa_id', $_POST['etapa']);

            if($antigo){
                if($antigo['codigo'] !== $_POST['codigo']){
                    $sitac->__set('motivo_antigo',  $antigo['codigo']);
                    $sitac->__set('motivo', $_POST['codigo']);
                    $sitac->editarMotivoMovimentoAtualizarProtocolos();
                }
                
                if($antigo['titulo'] !== $_POST['titulo']){
                    $sitac->editarMotivoMovimento();
                }
            }

            $etapa->editarEtapa(); 

        }else if ($_POST['documento'] == 'sub-fluxograma'){

            $etapa->__set('etapa', $_POST['fluxo']);

            if($antigo){

            if($antigo['codigo'] !== $_POST['codigo']){
                $sitac->__set('motivo_antigo', $antigo['codigo']);
                $sitac->__set('motivo', $_POST['codigo']);
                $resultado = $sitac->editarMotivoMovimentoAtualizarProtocolos();
            }}

            if($antigo){
                if($antigo['titulo'] !== $_POST['titulo']){
                    $resultado = $sitac->editarMotivoMovimento();
                }
            }
         
            
            $etapa->editarEtapaSubfluxograma(); 
        }

        
        echo json_encode(array("erro" => false,"mensagem" => "Etapa alterada com sucesso.", "codigo" => 1,"dados" => null));
    }

    public function deletarEtapa(){
        $this->validaAutenticacao();

        $etapa = Containeres::getModelo('Fluxogramas'); 

        if(empty($_POST['etapa']) || empty($_POST['documento']) ){

            echo json_encode(array("erro" => true,"mensagem" => "Existe campo(s) nulo(s).", "codigo" => 3,"dados" => $_POST));
            exit(); 
            
        }

        $etapa->__set('etapa', $_POST['etapa']);

        if($_POST['documento'] == 'fluxograma'){
            $etapa->deletarEtapa(); 
        }else if ($_POST['documento'] == 'sub-fluxograma'){
            $sub_passo = $etapa->deletarEtapaSubFluxograma(); 
        }
        echo json_encode(array("erro" => false,"mensagem" => "Etapa alterada com sucesso.", "codigo" => 1,"dados" => null));

    }
}


?>