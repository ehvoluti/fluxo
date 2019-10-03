<?php

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");
require_once("../class/interligapdv.class.php");

class Saurus{

	private $con;
	private $pdvconfig;
	private $arr_pdvfinalizador;
	private $arr_maparesumo; // Array de objetos da tabela mapa resumo
	private $arr_arquivos_lidos; // Array com os arquivos lidos
	private $estabelecimento;
	private $dtmovto;

	function __construct(Connection $con){
		$this->con = $con;
		$this->arr_ecf = array();
		$this->arr_maparesumo = array();
		$this->arr_arquivos_lidos = array();
	}

	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
	}

	public function consultar_convenio($string_xml, $codestabelec){
		return "0";
	}

	public function importvendas(){
		$query = "SELECT arqxml, codestabelec, referencia, caixa ";
		$query .= "FROM saurusvenda ";
		$query .= "WHERE codestabelec = {$this->estabelecimento->getcodestabelec()}  ";
		$query .= "AND dtmovto = '{$this->dtmovto}' ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll();

		foreach($arr as $row){
			if(!$this->processar_venda($row["arqxml"], $row["codestabelec"], $row["referencia"])){
				return false;
			}
			if(!$this->mov_status($row["arqxml"], $row["codestabelec"], $row["caixa"])){
				return false;
			}
		}

		return true;
	}

	public function processar_venda($string_xml, $codestabelec, $referencia){
		$this->estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);

		$xml = simplexml_load_string('<?xml version="1.0" encoding="ISO-8859-1"?>'.$string_xml);

		// Verifica se o XML foi carregado corretamente
		if($xml === FALSE){
			$_SESSION["ERROR"] = "O arquivo <b>{$filename}</b> não é um arquivo XML válido.";
			return FALSE;
		}

				// Verifica se existe itens no cupom
		$infNFe = $xml->infNFe;
		$bool_cfe = false;
		if(!isset($infNFe->det)){
			$infNFe = $xml->CFe->infCFe;
			if(!isset($infNFe->det)){
				return TRUE;
			}
			$bool_cfe = true;
		}

		if($bool_cfe){
			$cupom = (string) $infNFe->ide->cNF;
			$seqecf = (string) $infNFe->ide->cNF;
			$caixa = (int) $infNFe->ide->numeroCaixa;
			$data = (string)$infNFe->ide->dEmi;
			$data = substr($data,0,4)."-".substr($data,4,2)."-".substr($data,6,2);
			$hora = substr((string)$infNFe->ide->hEmi,0,2).":00:00";
			$tpMov = 70;
		}else{
			$cupom = (string) $infNFe->ide->nNF;
			$seqecf = (string) $infNFe->ide->nNF;
			$caixa = (int) $infNFe->ide["numCaixa"];
			$operador = (int) $infNFe->ide["idOperador"];
			$tpMov = (int) $infNFe->ide["tpMov"];
			$datahora = explode("T", $infNFe->ide->dhEmi);
			$data = (string) $datahora[0];
			$hora = (string) substr($datahora[1], 0, 10);
			
			$validar_cpfcnpj = true;
			if(isset($infNFe->dest->CPF)){				
				$cpfcnpj = (string) $infNFe->dest->CPF;				
			}elseif(isset($infNFe->dest->CNPJ)){
				$cpfcnpj = (string) $infNFe->dest->CNPJ;				
			}elseif(isset($infNFe->dest->idEstrangeiro)){
				$cpfcnpj = (string) $infNFe->dest->idEstrangeiro;
				$validar_cpfcnpj = false;
			}else{
				$cpfcnpj = "";
			}
		}

		// Carrega os dados do cupom
		if($tpMov == "70"){
			$chavecfe = (string) $infNFe->attributes()['Id'];
			$chavecfe = substr($chavecfe, 3);
			$numfabricacao = substr($chavecfe, 22, 9);
		}else{
			$chavecfe = "";
			$numfabricacao = "";
			$seqecf = "";
		}

		// Cria o cupom
		$pdvvenda = new PdvVenda();
		$pdvvenda->setstatus("A");
		$pdvvenda->setcaixa($caixa);
		$pdvvenda->setchavecfe($chavecfe);
		$pdvvenda->setnumfabricacao($numfabricacao);
		$pdvvenda->setseqecf($seqecf);
		$pdvvenda->setcupom($cupom);
		$pdvvenda->setdata($data);
		$pdvvenda->sethora($hora);
		$pdvvenda->setcpfcnpj($cpfcnpj, $validar_cpfcnpj);
		$pdvvenda->setoperador($operador);
		if($operador > 100000){
			$pdvvenda->setoperador($operador - 100000);
		}
		$pdvvenda->setreferencia($referencia);

		// Percorre todos os itens do XML
		foreach($infNFe->det as $det){

			// Captura os dados de cada item
			$sequencial = (int) $det["nItem"];
			$codproduto = (string) $det["idProduto"];
			$codfunc = (string) $det["idVendedor"];
			$status = (string) $det["indStatus"] == 1 ? "C" : "A";
			$quantidade = (float) $det->prod->qCom;
			$preco = (float) $det->prod->vUnCom;
			$desconto = (float) $det->prod->vDesc;
			$acrescimo = (float) $det->prod->vOutro;
			$total = round(($quantidade * $preco) - $desconto + $acrescimo,2);

			// Captura as informacoes de ICMS
			$icms = $det->imposto->ICMS;
			$this->capturar_icms($tptribicms, $aliqicms, $icms);

			// Cria o item
			$pdvitem = new PdvItem();
			$pdvitem->setstatus($status);
			$pdvitem->setsequencial($sequencial);
			$pdvitem->setcodproduto($codproduto);
			$pdvitem->setquantidade($quantidade);
			$pdvitem->setpreco($preco);
			$pdvitem->setdesconto($desconto);
			$pdvitem->setacrescimo($acrescimo);
			$pdvitem->settotal($total);
			$pdvitem->settptribicms($tptribicms);
			$pdvitem->setaliqicms($aliqicms);

			// Inclui o item na venda
			$pdvvenda->pdvitem[] = $pdvitem;
		}

		$pdvvenda->setcodfunc($codfunc);
		if($codfunc > 100000){
			$pdvvenda->setcodfunc($codfunc- 100000);
		}

		// Le as finalizadoras de venda
		$semfinalizadora = true;
		foreach($infNFe->pag->detPag as $detPag){
			$semfinalizadora = false;
			$pdvfinalizador = new PdvFinalizador();
			$pdvfinalizador->setdata($pdvvenda->getdata());
			$pdvfinalizador->setcaixa($pdvvenda->getcaixa());
			$pdvfinalizador->setcupom($pdvvenda->getcupom());
			$pdvfinalizador->setcodcliente($pdvvenda->getcodcliente());
			$pdvfinalizador->setcpfcliente($pdvvenda->getcpfcnpj());
			$idPag = (string) $detPag->attributes()['idPag'];
			if(strlen($idPag) <= 0){
				unset($pdvvenda);
				return true;
			}
			$idPag = str_pad($idPag, 2, "0", STR_PAD_LEFT);
			$pdvfinalizador->setcodfinaliz($idPag);
			$pdvfinalizador->setvalortotal((float) $detPag->vPag);

			// Armazena a finalizadora
			$this->arr_pdvfinalizador[] = $pdvfinalizador;
		}
		if($semfinalizadora){
			return true;
		}

		// Grava a venda
		$interligapdv = new InterligaPdv($this->con);
		$interligapdv->setcodestabelec($codestabelec);
		$interligapdv->setpdvfinalizador($this->arr_pdvfinalizador);
		$interligapdv->setpdvvenda(array($pdvvenda));

		// Retorna TRUE para identificar que foi processado com sucesso
		return $interligapdv->gravarvendas();
	}

	public function mov_status($string_xml, $codestabelec, $xIdCaixa){
		$string_xml = str_replace(array("\r","\n","'"),"",$string_xml);
		$xml = simplexml_load_string('<?xml version="1.0" encoding="ISO-8859-1"?>'.$string_xml);

		// Verifica se o XML foi carregado corretamente
		if($xml === FALSE){
			$_SESSION["ERROR"] = "O arquivo <b>{$filename}</b> não é um arquivo XML válido.";
			return FALSE;
		}

		foreach($xml->tbMovStatus as $tbMovStatus){
			$referencia = strtolower((string) $tbMovStatus->mov_idMov);
			$mov_tpStatus = (string) $tbMovStatus->mov_tpStatus;

			if(in_array($mov_tpStatus, array("4012", "7040", "7041", "8041"))){
				$res = $this->con->query("SELECT count(arqxml) FROM saurusvenda WHERE  arqxml = '$string_xml'");
				$contSaurusVenda = $res->fetchColumn();

				if($contSaurusVenda == 0){
					$this->con->exec("INSERT INTO saurusvenda(codestabelec,arqxml,referencia,caixa) VALUES ($codestabelec,'$string_xml','$referencia',$xIdCaixa)");
				}

				$cupom = objectbytable("cupom", null, $this->con);
				$cupom->setcodestabelec($codestabelec);
				$cupom->setreferencia($referencia);
				$cupom->searchbyobject();
				if($cupom->getstatus() != "A"){
					return true;
				}
				$cupom->setstatus("C");

				if(!$cupom->save()){
					return false;
				}
			}

			if(in_array($mov_tpStatus, array("7032"))){
				$xArqXml = $tbMovStatus->mov_arqXml;
				$xArqXml = $xArqXml->asXML();

				$res = $this->con->query("SELECT count(arqxml) FROM saurusvenda WHERE  arqxml = '$xArqXml'");
				$contSaurusVenda = $res->fetchColumn();

				if($contSaurusVenda == 0){
					$this->con->exec("INSERT INTO saurusvenda(codestabelec,arqxml,referencia,caixa) VALUES ($codestabelec,'$xArqXml','$referencia',$xIdCaixa)");
				}
			}
		}
		return true;
	}

	public function precopdv(){
		$query  = "SELECT produtoestab.codproduto ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produto.codproduto=produtoestab.codproduto) ";
		$query .= "WHERE produtoestab.disponivel = 'S' ";
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		if(param("ESTOQUE", "ENVIAFORALINHAPDV", $this->con) == "N"){
			$query .= "	AND produto.foralinha = 'N' ";
		}

		$res = $this->con->query($query);

		$arr_codproduto = $res->fetchAll(PDO::FETCH_COLUMN);

		if(sizeof($arr_codproduto) > 0){
			$str_codproduto = implode(",", $arr_codproduto);

			$query = "UPDATE produtoestab SET horalog = current_time, datalog = current_date WHERE codproduto in ($str_codproduto)";
			$this->con->exec($query);

			$this->pdvconfig->atualizar_precopdv($arr_codproduto);
		}


		return true;
	}

	private function verificar_ecf($numfabricacao, $modelo, $caixa, $equipamentofiscal){
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setnumfabricacao($numfabricacao);
		$arr_ecf = object_array($ecf);
		if(count($arr_ecf) > 0){
			$ecf = array_shift($arr_ecf);
		}else{
			$ecf = objectbytable("ecf", NULL, $this->con);
			$ecf->setcodestabelec($this->estabelecimento->getcodestabelec());
			$ecf->setnumfabricacao($numfabricacao);
			$ecf->setmodelo($modelo);
			$ecf->setcaixa($caixa);
			$ecf->setequipamentofiscal($equipamentofiscal);
			if(!$ecf->save()){
				return FALSE;
			}
		}
		return $ecf;
	}

	// Captura os dados de ICMS da estrutura XML
	private function capturar_icms(&$tptribicms, &$aliqicms, SimpleXMLElement $icms){
		if(strlen($icms->CSOSN->ICMSSN500->CSOSN) > 0){
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

	public function erro($texto){
		$file = fopen("../proc/error.log", "a+");
		fwrite($file, date("d/m/Y H:i:s")." - \r\n".$texto."\r\n\r\n");
		fclose($file);
		return true;
	}

	public function setdtmovto($dtmovto){
		$this->dtmovto = $dtmovto;
	}

	public function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

}
