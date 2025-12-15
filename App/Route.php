<?php
//Essas rotas são divididas em dois tipos de script, um que vai dar um render em uma página outra que vai pegar dados que são enviados via POST e trabalhar em cima dele com script
namespace App;

use MF\Init\Bootstrap;

class Route extends Bootstrap {

	protected function initRoutes() {
		//Página inicial que é chamada. seu render é chamado pelo index no indexController
		$routes['home'] = array(
			'route' => '/',
			'controller' => 'indexController',
			'action' => 'index'
		);

		$routes['cadastrar'] = array(
			'route' => '/cadastrar',
			'controller' => 'indexController',
			'action' => 'cadastrar'
		);

		$routes['configuracao'] = array(
			'route' => '/configuracao',
			'controller' => 'indexController',
			'action' => 'configuracao'
		);

		$routes['autenticar'] = array(
			'route' => '/autenticar', //Caminho da rota com a função
			'controller' => 'ApiController',
			'action' => 'autenticar'
		);

		$routes['getDadosUsuario'] = array(
			'route' => '/getDadosUsuario', //Caminho da rota com a função
			'controller' => 'ApiController',
			'action' => 'getDadosUsuario'
		);

		$routes['atualizarUsuario'] = array(
			'route' => '/atualizarUsuario', //Caminho da rota com a função
			'controller' => 'ApiController',
			'action' => 'atualizarUsuario'
		);

		$routes['matching'] = array(
			'route' => '/matching', //Caminho da rota com a função
			'controller' => 'ApiController',
			'action' => 'matching'
		);

		$routes['Listagem'] = array(
			'route' => '/listagem',
			'controller' => 'AppController',
			'action' => 'listagem'
		);

		$routes['configuracao'] = array(
			'route' => '/configuracao',
			'controller' => 'AppController',
			'action' => 'configuracao'
		);

		//Rota para retornar todos os assuntos de protocolo do sistema.
		$routes['cadastrarUsuario'] = array(
			'route' => '/cadastrarUsuario',
			'controller' => 'ApiController',
			'action' => 'cadastrarUsuario'
		);

		$routes['getSubFlow'] = array(
			'route' => '/getSubFlow',
			'controller' => 'AppController',
			'action' => 'getSubFlow'
		);

		$routes['getProtocoloFiltro'] = array(
			'route' => '/getProtocoloFiltro',
			'controller' => 'AppController',
			'action' => 'getProtocoloFiltro'
		);

		$routes['getProtocoloFiltroLocal'] = array(
			'route' => '/getProtocoloFiltroLocal',
			'controller' => 'AppController',
			'action' => 'getProtocoloFiltroLocal'
		);

		$routes['getInfoEtapa'] = array(
			'route' => '/getInfoEtapa',
			'controller' => 'AppController',
			'action' => 'getInfoEtapa'
		);

		$routes['getInfoSubfluxograma'] = array(
			'route' => '/getInfoSubfluxograma',
			'controller' => 'AppController',
			'action' => 'getInfoSubfluxograma'
		);

		$routes['getInfoSubfluxogramaEtapa'] = array(
			'route' => '/getInfoSubfluxogramaEtapa',
			'controller' => 'AppController',
			'action' => 'getInfoSubfluxogramaEtapa'
		);

		$routes['Processos'] = array(
			'route' => '/Processos',
			'controller' => 'AppController',
			'action' => 'Processos'
		);

		$routes['Filtro'] = array(
			'route' => '/filtro',
			'controller' => 'AppController',
			'action' => 'Filtro'
		);

		$routes['PageKanban'] = array(
			'route' => '/PageKanban',
			'controller' => 'AppController',
			'action' => 'PageKanban'
		);

		$routes['getFlowSubject'] = array(
			'route' => '/getFlowSubject',
			'controller' => 'AppController',
			'action' => 'getFlowSubject'
		);

		$routes['editarFluxograma'] = array(
			'route' => '/editarFluxograma',
			'controller' => 'AppController',
			'action' => 'editarFluxograma'
		);
		

		$routes['Denuncia'] = array(
			'route' => '/Denuncia',
			'controller' => 'AppController',
			'action' => 'Denuncia'
		);

		$routes['getFlowChartAll'] = array(
			'route' => '/getFlowChartAll',
			'controller' => 'AppController',
			'action' => 'getFlowChartAll'
		);

		$routes['getTableData'] = array(
			'route' => '/getTableData',
			'controller' => 'AppController',
			'action' => 'getTableData'
		);

		$routes['getDelayedProtocols'] = array(
			'route' => '/getDelayedProtocols',
			'controller' => 'AppController',
			'action' => 'getDelayedProtocols'
		);

		$routes['getTempoPrescricao'] = array(
			'route' => '/getTempoPrescricao',
			'controller' => 'AppController',
			'action' => 'getTempoPrescricao'
		);


		

		$routes['criarFluxograma'] = array(
			'route' => '/criarFluxograma',
			'controller' => 'AppController',
			'action' => 'criarFluxograma'
		);

		$routes['Protocolo'] = array(
			'route' => '/Protocolo',
			'controller' => 'AppController',
			'action' => 'Protocolo'
		);

		$routes['alterarEtapa'] = array(
			'route' => '/alterarEtapa',
			'controller' => 'AppController',
			'action' => 'alterarEtapa'
		);

		$routes['alterarNumeroMotivo'] = array(
			'route' => '/alterarNumeroMotivo',
			'controller' => 'AppController',
			'action' => 'alterarNumeroMotivo'
		);

		$routes['alterarTipoProtocolo'] = array(
			'route' => '/alterarTipoProtocolo',
			'controller' => 'AppController',
			'action' => 'alterarTipoProtocolo'
		);

		//Script de Logoff
		$routes['sair'] = array(
			'route' => '/sair', //Rotar a ser passada na página phtml
			'controller' => 'AuthController', //Onde está o código nos controladores
			'action' => 'sair' //Método | Função
		);

		$routes['getAllFlowSteps'] = array(
			'route' => '/getAllFlowSteps',
			'controller' => 'AppController',
			'action' => 'getAllFlowSteps'
		);

		$routes['getFlowSteps'] = array(
			'route' => '/getFlowSteps',
			'controller' => 'AppController',
			'action' => 'getFlowSteps'
		);

		/*Criar as etapas dos fluxos*/

		$routes['criarEtapa'] = array(
			'route' => '/criarEtapa',
			'controller' => 'AppController',
			'action' => 'criarEtapa'
		);

		$routes['anotacoes'] = array(
			'route' => '/anotacoes',
			'controller' => 'AppController',
			'action' => 'anotacoes'
		);

		$routes['getMotivo'] = array(
			'route' => '/getMotivo',
			'controller' => 'AppController',
			'action' => 'getMotivo'
		);

		$routes['criarMotivo'] = array(
			'route' => '/criarMotivo',
			'controller' => 'AppController',
			'action' => 'criarMotivo'
		);

		$routes['deletarEtapa'] = array(
			'route' => '/deletarEtapa',
			'controller' => 'AppController',
			'action' => 'deletarEtapa'
		);

		$routes['excluirMotivo'] = array(
			'route' => '/excluirMotivo',
			'controller' => 'AppController',
			'action' => 'excluirMotivo'
		);

	
		$routes['deletarFluxograma'] = array(
			'route' => '/deletarFluxograma',
			'controller' => 'AppController',
			'action' => 'deletarFluxograma'
		);

		$routes['getDelayedProtocols'] = array(
			'route' => '/getDelayedProtocols',
			'controller' => 'AppController',
			'action' => 'getDelayedProtocols'
		);

		
		$routes['editarEtapa'] = array(
			'route' => '/editarEtapa',
			'controller' => 'AppController',
			'action' => 'editarEtapa'
		);

		$routes['getTypeComplaint'] = array(
			'route' => '/getTypeComplaint',
			'controller' => 'AppController',
			'action' => 'getTypeComplaint'
		);

		$routes['getNumberProtocol'] = array(
			'route' => '/getNumberProtocol',
			'controller' => 'AppController',
			'action' => 'getNumberProtocol'
		);

		$routes['getStep'] = array(
			'route' => '/getStep',
			'controller' => 'AppController',
			'action' => 'getStep'
		);

		$routes['getUsers'] = array(
			'route' => '/getUsers',
			'controller' => 'AppController',
			'action' => 'getUsers'
		);


		$this->setRoutes($routes);
	}

}

?>