<?php

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class CupomFiscalEletronico{

	private $con;
	private $pdvconfig;
	private $arr_pdvvenda;
	private $arr_pdvfinalizador;
	private $arr_filename; // Armazena o nome dos arquivos da ultima importacao
	private $arr_ecf; // Armazena todos os ECFs cadastrados

	function __construct(){
		$this->limpar_dados();
		$this->carregar_ecf();
	}

	// Carrega o cadastro de ECF (que na verdade eh SAT)
	private function carregar_ecf(){
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setstatus("A");
		$ecf->setequipamentofiscal("SAT");
		$this->arr_ecf = object_array($ecf);
	}

	// Captura os dados de ICMS da estrutura XML
	private function capturar_icms(&$tptribicms, &$aliqicms, SimpleXMLElement $icms){
		if(strlen($icms->CSOSN) > 0){
			// Aliquota de ICMS
			$aliqicms = (float) (strlen($icms->pICMS) > 0 ? $icms->pICMS : (strlen($icms->pICMSST) > 0 ? $icms->pICMSST : 0));

			// Tipo de tributacao do ICMS do item
			switch($icms->CSOSN){
				case "101": // Tributada pelo Simples Nacional com permissao de credito
					$tptribicms = "T";
					break;
				case "102": // Tributada pelo Simples Nacional sem permissao de credito
					$tptribicms = "N";
					break;
				case "103":
					$tptribicms = "N";
					break;
				case "201": // Tributada pelo Simples Nacional com permissao de credito e com cobranca do ICMS por substituicao tributario
					$tptribicms = "F";
					break;
				case "202": // Tributada pelo Simples Nacional sem permissao de credito e com cobranca do ICMS por substituicao tributario
					$tptribicms = "F";
					break;
				case "203":
					$tptribicms = "F";
					break;
				case "300":
					$tptribicms = "N";
					break;
				case "400": // Nao tributada pelo Simples Nacional
					$tptribicms = "N";
					break;
				case "500": // ICMS cobrado anteriormente por substituicao tributario (substituido) ou por antecipacao
					$tptribicms = "F";
					break;
				case "900":
					$tptribicms = "N";
					break;
			}
		}else{
			// Aliquota de ICMS
			$aliqicms = (float) (strlen($icms->pICMS) > 0 ? $icms->pICMS : 0);

			// Tipo de tributacao do ICMS do item
			switch($icms->CST){
				case "00": // Tributada integralmente
					$tptribicms = "T";
					break;
				case "10": // Tributada e com cobranca do ICMS por substituicao tributaria
					$tptribicms = "F";
					break;
				case "20": // Com reducao de base de calculo
					$tptribicms = "R";
					break;
				case "30": // Isenta ou nao tributada e com cobraca do ICMS por substituicao tributaria
					$tptribicms = "I";
					break;
				case "40": // Isenta
					$tptribicms = "I";
					break;
				case "41": // Nao tributada
					$tptribicms = "I";
					break;
				case "50": // Suspensao
					$tptribicms = "N";
					break;
				case "51": // Diferimento (A exigencia do preenchimento das informacoes do ICMS diferido fica a criterio de cada UF)
					$tptribicms = "N";
					break;
				case "60": // ICMS cobrado anteriormente por substituicao
					$tptribicms = "F";
					break;
				case "70": // Com reducao de base de calculo e cobranca do ICMS por substituicao tributaria
					$tptribicms = "F";
					break;
				case "90": // Outros
					$tptribicms = "N";
					break;
			}
		}
	}

	function getpdvfinalizador(){
		return $this->arr_pdvfinalizador;
	}

	function getpdvvenda(){
		return $this->arr_pdvvenda;
	}

	// Limpa as variavies que armazena as vendas
	function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
		$this->arr_arquivo = array();
	}

	// Processa um unico arquivo XML de CF-e, alimentando as variaveis $arr_pdvvenda e $arr_pdvfinalizador
	function processar_arquivo($filename){
		// Carrega o arquivo XML do CF-e
		$xml = simplexml_load_file($filename);

		// Verifica se o XML foi carregado corretamente
		if($xml === FALSE){
			$_SESSION["ERROR"] = "O arquivo <b>{$filename}</b> não é um arquivo XML válido.";
			return FALSE;
		}

		// Localiza a tag infCFe
		$infCFe = $xml->infCFe;

		// Carrega os dados do cupom
		$chavecfe = substr($infCFe["Id"], -44);
		$seqecf = (string) $infCFe->ide->nCFe;
		$cupom = $seqecf;
		$caixa = (int) $infCFe->ide->numeroCaixa;
		$numfabricacao = (string) $infCFe->ide->nserieSAT;
		$data = (string) $infCFe->ide->dEmi;
		$hora = (string) $infCFe->ide->hEmi;
		$cpfcnpj = (string) $infCFe->dest->CPF;

		// Formata data e hora
		$data = substr($data, 0, 4)."-".substr($data, 4, 2)."-".substr($data, 6, 2);
		$hora = substr($hora, 0, 2).":".substr($hora, 2, 2).":".substr($hora, 4, 2);

		// Localiza a ECF (no caso o SAT) a partir do numero de serie
		$found = FALSE;
		foreach($this->arr_ecf as $ecf){
			if($ecf->getnumfabricacao() === $numfabricacao){
				$found = TRUE;
				break;
			}
		}
		if(!$found){
			$_SESSION["ERROR"] = "Equipamento fiscal de série <b>{$numfabricacao}</b> não foi encontrado no cadastro.";
			return FALSE;
		}

		// Captura os dados do ECF
		$codecf = $ecf->getcodecf();
		$numeroecf = $ecf->getnumeroecf();

		// Cria o cupom
		$pdvvenda = new PdvVenda();
		$pdvvenda->setstatus("A");
		$pdvvenda->setcaixa($caixa);
		$pdvvenda->setchavecfe($chavecfe);
		$pdvvenda->setcodecf($codecf);
		$pdvvenda->setnumeroecf($numeroecf);
		$pdvvenda->setseqecf($seqecf);
		$pdvvenda->setcupom($cupom);
		$pdvvenda->setdata($data);
		$pdvvenda->sethora($hora);
		$pdvvenda->setcpfcnpj($cpfcnpj);

		// Percorre todos os itens do XML
		foreach($infCFe->det as $det){

			// Captura os dados de cada item
			$sequencial = $det["nItem"];
			$codproduto = (string) $det->prod->cProd;
			$quantidade = (float) $det->prod->qCom;
			$preco = (float) $det->prod->vUnCom;
			$desconto = (float) $det->prod->vDesc;
			$total = (float) $det->prod->vItem;

			// Captura as informacoes de ICMS
			$icms = reset($det->imposto->ICMS);
			$this->capturar_icms($tptribicms, $aliqicms, $icms);

			// Cria o item
			$pdvitem = new PdvItem();
			$pdvitem->setstatus("A");
			$pdvitem->setsequencial($sequencial);
			$pdvitem->setcodproduto($codproduto);
			$pdvitem->setquantidade($quantidade);
			$pdvitem->setpreco($preco);
			$pdvitem->setdesconto($desconto);
			$pdvitem->settotal($total);
			$pdvitem->settptribicms($tptribicms);
			$pdvitem->setaliqicms($aliqicms);

			// Inclui o item na venda
			$pdvvenda->pdvitem[] = $pdvitem;
		}

		// Le as finalizadoras de venda
		foreach($infCFe->pgto->MP as $mp){
			$pdvfinalizador = new PdvFinalizador();
			$pdvfinalizador->setdata($pdvvenda->getdata());
			$pdvfinalizador->setcaixa($pdvvenda->getcaixa());
			$pdvfinalizador->setcupom($pdvvenda->getcupom());
			$pdvfinalizador->setcodcliente($pdvvenda->getcodcliente());
			$pdvfinalizador->setcpfcliente($pdvvenda->getcpfcnpj());
			$pdvfinalizador->setcodfinaliz((string) $mp->cMP);
			$pdvfinalizador->setvalortotal((float) $mp->vMP);

			// Armazena a finalizadora
			$this->arr_pdvfinalizador[] = $pdvfinalizador;
		}

		// Armazena a venda
		$this->arr_pdvvenda[] = $pdvvenda;

		// Armazena o nome do arquivo lido
		$this->arr_filename[] = $filename;

		// Retorna TRUE para identificar que foi processado com sucesso
		return TRUE;
	}

	// Processa um diretorio procurando por XMLs e rodando o metodo processar_arquivo() em cada arquivo
	function processar_diretorio($dirname){
		// Verifica se o diretorio existe
		if(!is_dir($dirname)){
			$_SESSION["ERROR"] = "O diretório <b>{$dirname}</b> não pôde ser encontrado.";
			return FALSE;
		}

		// Le todos os arquivos do diretorio
		$arr_filename = scandir($dirname);

		// Percorre os arquivos encontrados
		foreach($arr_filename as $filename){
			// Verifica se eh um arquivo XML
			if(strtolower(substr($filename, -4)) !== ".xml"){
				continue;
			}
			// Processa o arquivo XML
			if(!$this->processar_arquivo("{$dirname}/{$filename}")){
				return FALSE;
			}
		}

		// Retorna TRUE para identificar que foi processado com sucesso
		return TRUE;
	}

	// Passa o pdvconfig para a classe
	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
	}

}