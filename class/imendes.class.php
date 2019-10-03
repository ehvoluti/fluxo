<?php

ini_set("default_socket_timeout", 60);

class IMendes{

	private $con;
	private $estabelecimento;
	private $soap;
	private $wsdl = "http://consultatributos.com.br:8888/wsGeral.asmx?wsdl";
	private $log_filename = "../temp/imendes.log";
	private $n_service_error = 0; // Contador de erros da conexao

	function __construct(Connection $con){
		$this->con = $con;
		$this->log_clear();
	}

	function consultar_codproduto($codproduto){
		$produto = objectbytable("produto", $codproduto, $this->con);
		return $this->consultar_produto($produto);
	}

	function consultar_codproduto_lote($arr_codproduto){
		$arr_produto = object_array_key(objectbytable("produto", null, $this->con), $arr_codproduto);
		return $this->consultar_produto_lote($arr_produto);
	}

	function consultar_pendentes(){
		if(!is_object($this->estabelecimento)){
			$_SESSION["ERROR"] = "Informe o estabelecimento para consulta.";
			return false;
		}

		// Monta o XML a ser enviado
		$xml_enviodados = new SimpleXMLElement("<EnvioDados/>");
		$xml_cabecalho = $xml_enviodados->addChild("Cabecalho");
		$xml_cabecalho->addChild("CNPJ", removeformat($this->estabelecimento->getcpfcnpj()));
		$xml_cabecalho->addChild("UF", $this->estabelecimento->getuf());
		$xml_cabecalho->addChild("CRT", ($this->estabelecimento->getregimetributario() == "1" ? "1" : "3"));
		$xml_cabecalho->addChild("tpConsulta", "2"); // 1 - Homologacao; 2 - Producao
		$xml_cabecalho->addChild("versao", "2.0");

		// Iclui um produto inexistente, apenas para conseguir consultar
		$xml_produto = $xml_enviodados->addChild("Produto");
		$xml_produto->addChild("ID", 1);
		$xml_produto->addChild("EAN", null);
		$xml_produto->addChild("codigoInterno", 0);
		$xml_produto->addChild("descricao", "Consulta");

		// Consulta o servico
		$result = $this->service("MetodoConsultaProdutoXML", array("xml" => $xml_enviodados->asXML()));

		// Trata o retorno
		if(!is_object($result)){
			$_SESSION["ERROR"] = "Houve uma falha ao conectar com o servidor IMendes.";
			return false;
		}
		if(strlen($result->MetodoConsultaProdutoXMLResult) === 0){
			$_SESSION["ERROR"] = "Resposta vazia vinda do servidor IMendes.";
			return false;
		}
		$result_xml = @simplexml_load_string($result->MetodoConsultaProdutoXMLResult);
		if($result_xml === false){
			$_SESSION["ERROR"] = (string) $result->MetodoConsultaProdutoXMLResult;
			return false;
		}
		$result = $result_xml;

		var_dump($result);

		// Retorna os produtos pendentes
		return array(
			"plu" => (int) $result->Cabecalho->produtosPendentes_Interno,
			"ean" => (int) $result->Cabecalho->produtosPendentes_EAN
		);
	}

	function consultar_produto(Produto $produto){
		$arr_retorno = $this->consultar_produto_lote(array($produto));
		if(is_array($arr_retorno)){
			return array_shift($arr_retorno);
		}else{
			return $arr_retorno;
		}
	}

	function consultar_produto_lote($arr_produto){
		if(!is_object($this->estabelecimento)){
			$_SESSION["ERROR"] = "Informe o estabelecimento para consulta.";
			return false;
		}

		// Monta o XML a ser enviado
		$xml_enviodados = new SimpleXMLElement("<EnvioDados/>");
		$xml_cabecalho = $xml_enviodados->addChild("Cabecalho");
		$xml_cabecalho->addChild("CNPJ", removeformat($this->estabelecimento->getcpfcnpj()));
		$xml_cabecalho->addChild("UF", $this->estabelecimento->getuf());
		$xml_cabecalho->addChild("CRT", ($this->estabelecimento->getregimetributario() == "1" ? "1" : "3"));
		$xml_cabecalho->addChild("tpConsulta", "2"); // 1 - Homologacao; 2 - Producao
		$xml_cabecalho->addChild("versao", "2.0");
		$i = 1;
		foreach($arr_produto as $produto){
			if(!$produto->exists()){
				$_SESSION["ERROR"] = "O produto informado não foi encontrado.";
				return false;
			}

			// Localiza o EAN do produto
			$codean = null;
			$produtoean = objectbytable("produtoean", null, $this->con);
			$produtoean->setcodproduto($produto->getcodproduto());
			$arr_produtoean = object_array($produtoean);
			foreach($arr_produtoean as $produtoean){
				if(strlen($produtoean->getcodean()) >= 7 && strlen($produtoean->getcodean()) <= 13 && $produtoean->getcodean() != plutoean13($produto->getcodproduto()) && valid_ean($produtoean->getcodean())){
					$codean = $produtoean->getcodean();
					break;
				}
			}

			$xml_produto = $xml_enviodados->addChild("Produto");
			$xml_produto->addChild("ID", $i++);
			$xml_produto->addChild("EAN", $codean);
			$xml_produto->addChild("codigoInterno", $produto->getcodproduto());
			$xml_produto->addChild("descricao", $this->descricao($produto->getdescricaofiscal()));
		}

		// Consulta o servico
		$result = $this->service("MetodoConsultaProdutoXML", array("xml" => $xml_enviodados->asXML()));

		if(!is_object($result)){
			$_SESSION["ERROR"] = "Houve uma falha ao conectar com o servidor IMendes.";
			return false;
		}

		if(strlen($result->MetodoConsultaProdutoXMLResult) === 0){
			$_SESSION["ERROR"] = "Resposta vazia vinda do servidor IMendes.";
			return false;
		}

		$result_xml = @simplexml_load_string($result->MetodoConsultaProdutoXMLResult);
		if($result_xml === false){
			$_SESSION["ERROR"] = (string) $result->MetodoConsultaProdutoXMLResult;
			return false;
		}
		$result = $result_xml;

		if(!isset($result->Produto)){
			$_SESSION["ERROR"] = "Produto ainda não disponível pra saneamento.";
			return false;
		}

		// Array com os produtos retornados
		$arr_retorno = array();

		// Percorre todos os produtos
		foreach($result->Produto as $result_produto){
			foreach($arr_produto as $produto){
				if((int) $produto->getcodproduto() === (int) $result_produto->codigoInterno){
					break;
				}
			}

			// Dados principais
			$natreceita = (string) $result_produto->naturezaReceitaIsentaPISCOFINS;
			$retorno = array(
				"codproduto" => $produto->getcodproduto(),
				"descricaofiscal" => $produto->getdescricaofiscal(),
				"natreceita" => $natreceita
			);

			// NCM
			$codigoncm = trim(str_replace(".", "", (string) $result_produto->NCM));
			if(strlen($codigoncm) > 0){
				$retorno["ncm"]["codigoncm"] = $codigoncm;
			}

			// PIS/Cofins
			$cstpisent = str_pad((string) $result_produto->CSTPISCOFINSEntrada, 2, "0", STR_PAD_LEFT);
			$cstpissai = str_pad((string) $result_produto->CSTPISCOFINSSaida, 2, "0", STR_PAD_LEFT);
			switch($this->estabelecimento->getregimetributario()){
				case "1": // Simples nacional
					if($cstpisent === "50"){
						$cstpisent = "98";
					}
					if($cstpissai === "01"){
						$cstpissai = "49";
					}
					break;
				case "2": // Lucro presumido
					/*
					if($cstpisent === "50"){
						$cstpisent = "70";
					}
					if($cstpissai === "01"){
						$cstpissai = "04";
					}
					*/
					break;
				case "3": // Lucro real
					/*
					  if($cstpisent === "50"){
					  $cstpisent = "70";
					  }
					 */
					break;
			}
			$retorno["piscofinsent"]["codcst"] = $cstpisent;
			$retorno["piscofinssai"]["codcst"] = $cstpissai;

			// ICMS
			$csticms = (string) $result_produto->CST;
			$csosn = (string) $result_produto->CSOSN;
			$tptribicms = (string) $result_produto->simbPDV;
			$aliqicms = (float) $result_produto->pICMS;
			$aliqicmspdv = (float) $result_produto->pICMSPDV;
			$aliqiva = (float) $result_produto->pMVAST;
			$aliqredicms = (float) $result_produto->pRedBCICMS;
			$valorpauta = 0; //(float) $result_produto->vPautaST;
			//if($csosn === "500"){
			//	$aliqicms = 0;
			//}

			if(strlen($csticms) === 0 && strlen($tptribicms) > 0){
				$csticms = csticms($tptribicms, $aliqicms, $aliqredicms, $aliqiva, $valorpauta);
			}

			if($csticms === "040" || (strlen($csticms) === 0 && strlen($natreceita) > 0)){
				$tptribicms = "I";
			}elseif($aliqiva > 0 || $valorpauta > 0 || in_array($csticms, array("010", "060", "070"))){
				$tptribicms = "F";
			}elseif($aliqredicms > 0){
				$tptribicms = "R";
			}elseif($aliqicms > 0){
				$tptribicms = "T";
			}else{
				$tptribicms = "N";
			}

			// Tratamento com o CST de ICMS
			if(strlen($csticms) === 0){
				$csticms = csticms($tptribicms, $aliqicms, $aliqredicms, $aliqiva, $valorpauta);
			}
			if($csticms === "060" && $aliqicms > 0){
				$csticms = "010";
			}
			if($csticms === "010" && $aliqicms > 0 && $aliqredicms > 0){
				$csticms = "070";
			}

			if($aliqicmspdv == 11){
				$retorno["classfiscalnfe"]["codcst"] = "000";
				$retorno["classfiscalnfe"]["csosn"] = $csosn;
				$retorno["classfiscalnfe"]["aliqicms"] = 7;
				$retorno["classfiscalnfe"]["tptribicms"] = "T";
				$retorno["classfiscalnfe"]["aliqredicms"] = 0;
				$retorno["classfiscalnfe"]["aliqiva"] = 0;
				$retorno["classfiscalnfe"]["valorpauta"] = 0;
			}else{
				$retorno["classfiscalnfe"]["codcst"] = $csticms;
				$retorno["classfiscalnfe"]["csosn"] = $csosn;
				$retorno["classfiscalnfe"]["aliqicms"] = $aliqicms;
				$retorno["classfiscalnfe"]["tptribicms"] = $tptribicms;
				$retorno["classfiscalnfe"]["aliqredicms"] = $aliqredicms;
				$retorno["classfiscalnfe"]["aliqiva"] = $aliqiva;
				$retorno["classfiscalnfe"]["valorpauta"] = $valorpauta;
			}
			$retorno["classfiscalnfs"] = $retorno["classfiscalnfe"];
			if(in_array($retorno["classfiscalnfs"]["codcst"], array("010", "070"))){
				$retorno["classfiscalnfs"]["codcst"] = "060";
			}
			if($retorno["classfiscalnfs"]["tptribicms"] === "F"){
				$retorno["classfiscalnfs"]["aliqicms"] = 0;
				$retorno["classfiscalnfs"]["aliqredicms"] = 0;
				$retorno["classfiscalnfs"]["aliqiva"] = 0;
				$retorno["classfiscalnfs"]["valorpauta"] = 0;
			}
			$retorno["classfiscalpdv"] = $retorno["classfiscalnfs"];
			$retorno["classfiscalpdv"]["aliqicms"] = $aliqicmspdv;
			$retorno["classfiscalpdv"]["aliqredicms"] = 0;
			if($retorno["classfiscalpdv"]["tptribicms"] === "R"){
				$retorno["classfiscalpdv"]["tptribicms"] = "T";
				$retorno["classfiscalpdv"]["codcst"] = "000";
			}

			// IPI
			$aliqipi = (float) $result_produto->pIPI;
			$cstipi = (string) $result_produto->CSTIPI;
			$retorno["ipi"]["codcstsai"] = $cstipi;
			$retorno["ipi"]["tptribipi"] = "P";
			$retorno["ipi"]["aliqipi"] = $aliqipi;

			// CEST
			$retorno["cest"] = (string) $result_produto->CEST;

			// Inclui o retorno na lista
			$arr_retorno[] = $retorno;
		}

		return $arr_retorno;
	}

	private function descricao($descricao){
		$validos = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", " ");
		$descricao_valida = "";
		for($i = 0; $i < strlen($descricao); $i++){
			$char = strtoupper(substr($descricao, $i, 1));
			if(in_array($char, $validos)){
				$descricao_valida .= $char;
			}
		}
		return $descricao_valida;
	}

	function log($text){
		$file = fopen($this->log_filename, "a+");
		fwrite($file, date("d/m/Y H:i:s")." {$text}\r\n");
		fclose($file);
	}

	function log_clear(){
		if(file_exists($this->log_filename)){
			unlink($this->log_filename);
		}
		$file = fopen($this->log_filename, "w+");
		fclose($file);
		chmod($this->log_filename, 0777);
	}

	private function service($name, $param){
		$this->log("Entrada: ".var_export($param, true));
		try{
			if(!is_object($this->soap) && !$this->soap_connect()){
				return false;
			}
			if(is_object($this->soap)){
				$result = $this->soap->__soapCall($name, array($param));
			}else{
				return false;
			}
		}catch(Exception $ex){
			$result = false;
			$error = $ex->getMessage();
			$this->n_service_error++;
		}
		$this->log("Saida: ".var_export($result, true));
		if($result === false){
			$this->log("Erro: {$error}");
		}
		if($this->n_service_error === 100){
			die("Erro de conexão IMendes: Limite máximo de erros de conexões foi excedido.");
		}
		return $result;
	}

	function setestabelecimento(Estabelecimento $estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	private function soap_connect(){
		for($i = 0; $i < 20; $i++){
			try{
				$this->soap = new SoapClient($this->wsdl, array(
					"cache_wsdl" => WSDL_CACHE_NONE,
					"connection_timeout" => 5,
					"exceptions" => true,
					"soap_version" => SOAP_1_2
				));
				if(is_object($this->soap)){
					return true;
				}
			}catch(Exception $ex){

			}
			sleep(1);
		}
		return false;
	}

}