<?php

require_once("websac/require_file.php");
require_file("def/function.php");

class ITWorks{

	private $con;
	private $soap;
	private $soap_count;
	private $centralizador;
	private $caixaentrada;
	private $codigoacesso;
	private $emailnotificacao;
	private $uf;
	private $regimetributario;
	private $emitente;
	private $enviar_lote;
	private $arr_classfiscal;
	private $arr_embalagem;
	private $arr_ncm;
	private $arr_unidade;
	private $arr_consulta_ok;
	private $arr_incluir_ok;
	private $log_filename = "../temp/itworks.log";

	function __construct(Connection $con, $new_log = TRUE){
		$this->con = $con;

		$this->soap_reconnect();

		$ini = parse_ini_string(param("INTEGRACAO", "ITWORKS", $this->con));
		$this->centralizador = $ini["Centralizador"];
		$this->caixaentrada = $ini["CaixaEntrada"];
		$this->codigoacesso = $ini["CodigoAcesso"];
		$this->emailnotificacao = $ini["EmailNotificacao"];

		$this->limpar_consulta();

		$this->emitente = objectbytable("emitente", 1, $this->con);

		// Verifica se deve enviar os produtos em lote
		$res = $this->con->query("SELECT COUNT(codproduto) FROM produto WHERE dtsaneamentoins IS NOT NULL AND sanear = 'S'");
		$this->enviar_lote = ((int) $res->fetchColumn() === 0);

		if($new_log){
			$this->log_clear();
		}
	}

	private function classfiscal($codcf){
		$classfiscal = $this->arr_classfiscal[$codcf];
		if(!is_object($classfiscal)){
			$classfiscal = objectbytable("classfiscal", $codcf, $this->con);
			$this->arr_classfiscal[$codcf] = $classfiscal;
		}
		return $classfiscal;
	}

	private function consultar_envio($param, $produto = NULL, $inicio = NULL, $limite = NULL){
		// Deixa todos os parametros como tipo String
		foreach($param as $i => $val){
			$param[$i] = (string) $val;
		}

		$this->log_write("Executando consulta de produto\r\n".json_encode($param));

		// Executa a consulta do produto
		for($i = 0; $i < 20; $i++){
			try{
				$result = $this->soap->__soapCall("ConsultarProduto", array($param));
				$this->soap_increment();
				break;
			}catch(Exception $exc){
				$this->log_write("Erro na consulta de produto\r\n{$exc->getMessage()}");
				$_SESSION["ERROR"] = $exc->getMessage();
				sleep(500);
				$this->soap_reconnect();
			}
		}

		// Verifica se retornou alguma resposta
		if($result === false || $result === null){
			return false;
		}

		$this->log_write("Resposta da consulta de produto\r\n".var_export($result, true));

		// Verifica se a consulta retornou com erro
		if(!isset($result->ConsultarProdutoResult)){
			$erro = (string) $result->MensagemErro;
			if(strlen($erro) > 0){
				$_SESSION["ERROR"] = str_replace("|", "<br>", $erro);
				return FALSE;
			}
		}else{
			// Verifica se o erro retornado eh por produto nao encontrado
			$erro = strtolower((string) $result->MensagemErro);
			if(strlen($erro) > 0){
				// Faz umas verificacoes com a string de erro
				if(strpos($erro, "plu") !== FALSE && strpos($erro, "localizado") !== FALSE){
					// Produto nao localizado (deve ignorar, pois pode ser que o produto ainda nao foi saneado)
					return TRUE;
				}else{
					$_SESSION["ERROR"] = str_replace("|", "<br>", $erro);
					return FALSE;
				}
			}elseif($result->ConsultarProdutoResult === FALSE){ // Nao consultou com sucesso
				// Deve ignorar, pois pode ser que o produto ainda nao foi saneado
				return TRUE;
			}else{ // Consulta retornada com sucesso
				if(strlen($result->RespostaDadosDoProdutoFormatoXML) > 0){
					$this->consultar_resposta($result->RespostaDadosDoProdutoFormatoXML);
				}elseif(strlen($result->RespostaListaProdutosAlteradosFormatoXML) > 0){
					// Faz os tratamentos com o retorno do produto
					$qtdepaginas = (int) $result->QtdeDePaginas;
					//$pagina = (int) $result->NroPagina;
					$pagina = (int) $param["NroPagina"];
					if($pagina > 1){
						return (string) $result->RespostaListaProdutosAlteradosFormatoXML;
					}else{
						$arr_xml_resposta = array();
						$arr_xml_resposta[] = (string) $result->RespostaListaProdutosAlteradosFormatoXML;
						$pagina++;
						while($pagina <= $qtdepaginas){
							$param["NroPagina"] = $pagina++;
							$result = $this->consultar_envio($param);
							if($result === FALSE){
								return FALSE;
							}else{
								$arr_xml_resposta[] = $result;
							}
						}
						$arr_codproduto = array();
						foreach($arr_xml_resposta as $xml){
							$arr = xml2array($xml);
							if($arr !== FALSE && is_array($arr["PLU"])){
								foreach($arr["PLU"] as $produto){
									$arr_codproduto[] = $produto["Produto"];
								}
							}
						}
						if(!is_null($inicio)){
							if($inicio > 0){
								$inicio--;
							}
							$arr_codproduto = array_slice($arr_codproduto, $inicio);
						}
						if(!is_null($limite)){
							$arr_codproduto = array_slice($arr_codproduto, 0, $limite);
						}
						$i = 1;
						$n = count($arr_codproduto);
						foreach($arr_codproduto as $codproduto){
							setprogress(($i / $n * 100), "Consultando produtos: {$i} de {$n}");
							$i++;
							if($this->consultar_produto($codproduto) === FALSE){
								return FALSE;
							}
						}
						return TRUE;
					}
				}else{
					return TRUE;
				}
			}
		}
	}

	function consultar_periodo($dtinicial, $inicio = NULL, $limite = NULL){
		// Converte para o formata Y-m-d
		$dtinicial = value_date($dtinicial);

		// Valida a data inicial
		if(strlen($dtinicial) == 0){
			$_SESSION["ERROR"] = "Informe um valor v&aacute;lido para a data inicial.";
			return FALSE;
		}

		// Preenche os dados do parametro
		$param = array(
			"Centralizador" => $this->centralizador,
			"CaixaEntrada" => $this->caixaentrada,
			"CodigoAcesso" => $this->codigoacesso,
			"CodigoProduto" => NULL,
			"DataAlteradosApartir" => $dtinicial,
			"NroPagina" => 1,
			"QtdeDePaginas" => NULL,
			"MensagemErro" => NULL,
			"RespostaDadosDoProdutoFormatoXML" => NULL,
			"RespostaListaProdutosAlteradosFormatoXML" => NULL
		);

		return $this->consultar_envio($param, NULL, $inicio, $limite);
	}

	function consultar_produto($produto){
		// Verifica se foi enviado o objeto ou o apenas o codigo
		if(!is_object($produto)){
			$codproduto = $produto;
			if(strlen($codproduto) > 6){
				$produtoean = objectbytable("produtoean", $codproduto, $this->con);
				$produto = objectbytable("produto", $produtoean->getcodproduto(), $this->con);
			}else{
				$produto = objectbytable("produto", $codproduto, $this->con);
			}
		}else{
			$codproduto = $produto->getcodproduto();
		}

		// Verifica se o produto existe
		if(!$produto->exists()){
			return TRUE;
			//$_SESSION["ERROR"] = "Produto informado n&atilde;o p&ocirc;de ser encontrado.<br>Código do produto: ".$codproduto;
			//return FALSE;
		}

		// Verifica se foi informado o estado e o regime tributario
		if(strlen($this->uf) == 0){
			$_SESSION["ERROR"] = "Informe o estado desejado para consultar os dados do produto.";
			return FALSE;
		}
		if(strlen($this->regimetributario) == 0){
			$_SESSION["ERROR"] = "Informe o regime tribut&aacute;rio desejado para consultar os dados do produto.";
			return FALSE;
		}

		// Preenche os outros dados do parametro
		$param = array(
			"Centralizador" => $this->centralizador,
			"CaixaEntrada" => $this->caixaentrada,
			"CodigoAcesso" => $this->codigoacesso,
			"CodigoProduto" => $produto->getcodproduto(),
			"DataAlteradosApartir" => NULL,
			"NroPagina" => 1,
			"QtdeDePaginas" => NULL,
			"MensagemErro" => NULL,
			"RespostaDadosDoProdutoFormatoXML" => NULL,
			"RespostaListaProdutosAlteradosFormatoXML" => NULL
		);

		// Executa o metodo de consulta
		return $this->consultar_envio($param, $produto);
	}

	// O parametro $xml_resposta corresponte ao objeto RespostaDadosDoProdutoFormatoXML da consulta do IT Works
	private function consultar_resposta($arr_xml_resposta){
		if(!is_array($arr_xml_resposta)){
			$arr_xml_resposta = array((string) $arr_xml_resposta);
		}

		foreach($arr_xml_resposta as $xml_resposta){
			if($this->validar_xml_resposta($xml_resposta)){
				$retproduto = xml2array($xml_resposta);
			}else{
				/*
				  $_SESSION["ERROR"] = "XML de resposta &eacute; inv&aacute;lido.";
				  var_dump($xml_resposta);
				  return FALSE;
				 */
				return TRUE;
			}

			if($retproduto === FALSE){
				continue;
			}

			if(isset($retproduto["Produto"])){
				$retproduto = $retproduto["Produto"];
			}

			$codproduto = $retproduto["DadosBasicos"]["Produto"];

			$this->arr_consulta_ok[$codproduto]["codproduto"] = $codproduto;
			$this->arr_consulta_ok[$codproduto]["descricaofiscal"] = $retproduto["DadosBasicos"]["Descricao"];

			// Captura o NCM
			$codigoncm = $retproduto["DadosBasicos"]["Capitulo"].$retproduto["DadosBasicos"]["Item"];
			$codigoncm = trim(str_replace(".", "", $codigoncm));
			if(strlen($codigoncm) > 0){
				$codigoncm = substr($codigoncm, 0, 4).".".substr($codigoncm, 4, 2).".".substr($codigoncm, 6, 2);
				$this->arr_consulta_ok[$codproduto]["ncm"]["codigoncm"] = $codigoncm;
				$this->arr_consulta_ok[$codproduto]["ncm"]["descricao"] = $retproduto["DadosBasicos"]["DescricaoNCM"];
			}

			// Captura os impostos federais (PIS/Cofins e IPI)
			if(isset($retproduto["ProdutosFederal"]["Vigencia"])){
				$arr_vigencia = $retproduto["ProdutosFederal"]["Vigencia"];
				if(isset($arr_vigencia["@attributes"])){
					$arr_vigencia = array($arr_vigencia);
				}
			}else{
				$arr_vigencia = array();
			}
			foreach($arr_vigencia as $vigencia){
				$inicio = substr($vigencia["VigenciaInicio"], 0, 10);
				$final = substr($vigencia["VigenciaFinal"], 0, 10);
				if(compare_date(date("Y-m-d"), $inicio, "Y-m-d", ">=") && compare_date(date("Y-m-d"), $final, "Y-m-d", "<=")){
					// PIS/Cofins entrada
					if(is_string($vigencia["CSTPis"]) && strlen($vigencia["CSTPis"]) > 0){
						$this->arr_consulta_ok[$codproduto]["piscofinsent"]["codcst"] = $vigencia["CSTPis"];
						$this->arr_consulta_ok[$codproduto]["piscofinsent"]["codccs"] = "01";
						switch($this->regimetributario){
							case "2":
								$this->arr_consulta_ok[$codproduto]["piscofinsent"]["aliqpis"] = 0;
								$this->arr_consulta_ok[$codproduto]["piscofinsent"]["aliqcofins"] = 0;
								break;
							case "3":
								$this->arr_consulta_ok[$codproduto]["piscofinsent"]["aliqpis"] = value_numeric($vigencia["LRealPISAliq"]);
								$this->arr_consulta_ok[$codproduto]["piscofinsent"]["aliqcofins"] = value_numeric($vigencia["LRealCOFINSAliq"]);
								break;
						}
					}

					// PIS/Cofins saida
					if(is_string($vigencia["CSTPisSaida"]) && strlen($vigencia["CSTPisSaida"]) > 0){
						$this->arr_consulta_ok[$codproduto]["piscofinssai"]["codcst"] = $vigencia["CSTPisSaida"];
						$this->arr_consulta_ok[$codproduto]["piscofinssai"]["codccs"] = "01";
						switch($this->regimetributario){
							case "2":
								$this->arr_consulta_ok[$codproduto]["piscofinssai"]["aliqpis"] = value_numeric($vigencia["LPresPISAliq"]);
								$this->arr_consulta_ok[$codproduto]["piscofinssai"]["aliqcofins"] = value_numeric($vigencia["LPresCOFINSAliq"]);
								break;
							case "3":
								$this->arr_consulta_ok[$codproduto]["piscofinssai"]["aliqpis"] = value_numeric($vigencia["LRealPISAliqSaida"]);
								$this->arr_consulta_ok[$codproduto]["piscofinssai"]["aliqcofins"] = value_numeric($vigencia["LRealCOFINSAliqSaida"]);
								break;
						}
					}

					// Natureza da receita
					$natreceita = (string) $vigencia["NatRecPIS"];
					if(substr(strtolower($natreceita), 0, 3) === "arr"){
						$natreceita = null;
					}
					$this->arr_consulta_ok[$codproduto]["natreceita"] = $natreceita;

					// IPI
					/* $this->arr_consulta_ok[$codproduto]["ipi"]["codcstsai"] = $vigencia["CSTIpi"];
					  if(value_numeric($vigencia["ValorIPIPauta"]) > 0){
					  $this->arr_consulta_ok[$codproduto]["ipi"]["tptribipi"] = "F";
					  $this->arr_consulta_ok[$codproduto]["ipi"]["aliqipi"] = value_numeric($vigencia["ValorIPIPauta"]);
					  }else{
					  $this->arr_consulta_ok[$codproduto]["ipi"]["tptribipi"] = "P";
					  $this->arr_consulta_ok[$codproduto]["ipi"]["aliqipi"] = value_numeric($vigencia["IPI"]);
					  } */
					break;
				}
			}

			// Captura os impostos estaduais (ICMS)
			$arr_uf = $retproduto["ProdutosSefaz"]["UF"];
			if(isset($arr_uf["@attributes"])){
				$arr_uf = array($arr_uf);
			}
			foreach($arr_uf as $uf){
				if($uf["@attributes"]["Sigla"] == $this->uf){
					$arr_vigencia = $uf["Vigencia"];
					if(isset($arr_vigencia["@attributes"])){
						$arr_vigencia = array($arr_vigencia);
					}
					foreach($arr_vigencia as $vigencia){
						$inicio = substr($vigencia["VigenciaInicio"], 0, 10);
						$final = substr($vigencia["VigenciaFinal"], 0, 10);
						if(compare_date(date("Y-m-d"), $inicio, "Y-m-d", ">=") && compare_date(date("Y-m-d"), $final, "Y-m-d", "<=")){
							// Origem
							$origem = (string) $retproduto["OrigemMercadoria"];
							if(strlen($origem) === 0){
								$origem = "0";
							}

							// Entrada de nota fiscal
							if(is_string($vigencia["CSTInd"]) && strlen($vigencia["CSTInd"]) > 0){
								$this->arr_consulta_ok[$codproduto]["classfiscalnfe"]["codcst"] = $origem.$vigencia["CSTInd"];
								$this->arr_consulta_ok[$codproduto]["classfiscalnfe"]["aliqicms"] = value_numeric($vigencia["ICMSAliqFinalOpeInt"]);
								$this->arr_consulta_ok[$codproduto]["classfiscalnfe"]["aliqredicms"] = value_numeric($vigencia["ICMSReducOpeInt"] ? $vigencia["ICMSReducOpeInt"] : 0);
								$this->arr_consulta_ok[$codproduto]["classfiscalnfe"]["aliqiva"] = value_numeric($vigencia["IVAOpeInt"]);
								$this->arr_consulta_ok[$codproduto]["classfiscalnfe"]["valorpauta"] = 0; //value_numeric($vigencia["ICMSValorPautaOpeInt"]);
							}

							// Saida de nota fiscal
							$this->arr_consulta_ok[$codproduto]["classfiscalnfs"]["codcst"] = $origem.$vigencia["SaidaCST"];
							$this->arr_consulta_ok[$codproduto]["classfiscalnfs"]["aliqicms"] = value_numeric($vigencia["SaidaICMSAliq"]);
							$this->arr_consulta_ok[$codproduto]["classfiscalnfs"]["aliqredicms"] = value_numeric($vigencia["SaidaICMSAliqReducao"] ? $vigencia["SaidaICMSAliqReducao"] : 0);
							$this->arr_consulta_ok[$codproduto]["classfiscalnfs"]["aliqiva"] = 0;
							$this->arr_consulta_ok[$codproduto]["classfiscalnfs"]["valorpauta"] = 0;

							// Saida no frente de caixa
							$saidacstecf = $vigencia["SaidaCSTECF"];
							if($saidacstecf === "20"){
								$saidacstecf = "00";
							}
							$this->arr_consulta_ok[$codproduto]["classfiscalpdv"]["codcst"] = $origem.$saidacstecf;
							$this->arr_consulta_ok[$codproduto]["classfiscalpdv"]["aliqicms"] = value_numeric($vigencia["SaidaICMSAliqECF"]);
							$this->arr_consulta_ok[$codproduto]["classfiscalpdv"]["aliqredicms"] = 0;
							$this->arr_consulta_ok[$codproduto]["classfiscalpdv"]["aliqiva"] = 0;
							$this->arr_consulta_ok[$codproduto]["classfiscalpdv"]["valorpauta"] = 0;

							// CEST
							$this->arr_consulta_ok[$codproduto]["cest"] = $vigencia["CEST"];
						}
					}
					break;
				}
			}
		}

		return TRUE;
	}

	private function descricao($descricao){
		$descricao = removespecial($descricao);
		$descricao = preg_replace("/[^a-z0-9]/i", " ", $descricao);
		$descricao = str_replace("  ", " ", $descricao);
		$descricao = trim($descricao);
		return $descricao;
	}

	private function embalagem($codembal){
		$embalagem = $this->arr_embalagem[$codembal];
		if(!is_object($embalagem)){
			$embalagem = objectbytable("embalagem", $codembal, $this->con);
			$this->arr_embalagem[$codembal] = $embalagem;
		}
		return $embalagem;
	}

	function getlistaconsulta(){
		return $this->arr_consulta_ok;
	}

	function getlistaincluir(){
		return $this->arr_incluir_ok;
	}

	private function log_write($text){
		if(!is_string($text)){
			$text = json_encode($text);
		}
		$text = date("d/m/Y H:i:s")."\r\n".$text."\r\n\r\n";

		$file = fopen($this->log_filename, "a+");
		fwrite($file, $text);
		fclose($file);
	}

	function limpar_consulta(){
		$this->arr_consulta_ok = array();
		$this->arr_incluir_ok = array();
	}

	private function log_clear(){
		$file = fopen($this->log_filename, "w+");
		fclose($file);
	}

	function incluir($arr_produto = NULL){
		if(!is_null($arr_produto)){
			// Valida a lista de produtos enviado
			if(!is_array($arr_produto)){
				$_SESSION["ERROR"] = "Lista de produtos enviada para saneamento &eacute; inv&aacute;lida.";
				return FALSE;
			}
			foreach($arr_produto as $produto){
				if(get_class($produto) !== "Produto"){
					$_SESSION["ERROR"] = "Existem objetos que n&atilde;o s&atilde;o produtos na lista.";
					return FALSE;
				}
			}
		}else{
			// Se nao foi informado os produtos, carrega todos nao incluidos ainda
			$arr_codproduto = array();
			$res = $this->con->query("SELECT codproduto FROM produto WHERE dtsaneamentoins IS NULL ORDER BY codproduto");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$arr_codproduto[] = $row["codproduto"];
			}
			$arr_produto = object_array_key(objectbytable("produto", NULL, $this->con), $arr_codproduto);
		}

		// Gera um numero de lote
		$numerolote = time();

		// Verifica se deve enviar lote
		if($this->enviar_lote){

			// Divide todos os produtos em grupos de 200
			$arr_produto_lote = array_chunk($arr_produto, 200);

			// Abre/inicia o lote
			$param = array(
				"Centralizador" => $this->centralizador,
				"CaixaEntrada" => $this->caixaentrada,
				"CodigoAcesso" => $this->codigoacesso,
				"SeuNumeroLote" => $numerolote,
				"Operacao" => "A",
				"QtdeProdutosNestaChamada" => 0,
				"DadosBasicosLoteProdutoFormatoXML" => "",
				"ListaEmailsNotificacao" => $this->emailnotificacao,
				"ColecaoAlertas" => "",
				"MensagemErro" => ""
			);
			$this->log_write("IncluirProdutoLote (abertura)\r\n".json_encode($param));
			$result = $this->soap->__soapCall("IncluirProdutoLote", array($param));
			$this->soap_increment();
			$mensagemerro = (string) $result->MensagemErro;
			if(strlen($mensagemerro) > 0){
				$_SESSION["ERROR"] = str_replace("|", "<br>", $mensagemerro);
				return FALSE;
			}

			// Faz a inclusao dos produtos
			foreach($arr_produto_lote as $i => $arr_produto2){
				setprogress((($i + 1) / count($arr_produto_lote) * 100), "Enviando lote de produtos: ".($i + 1)." de ".count($arr_produto_lote));

				$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
				$xml .= "<InclusaoProdutoLote>";
				$j = 0;
				foreach($arr_produto2 as $produto){
					$j++;
					$xml .= $this->xml_produto($produto, FALSE, $j);
				}
				$xml .= "</InclusaoProdutoLote>";

				$param = array(
					"Centralizador" => $this->centralizador,
					"CaixaEntrada" => $this->caixaentrada,
					"CodigoAcesso" => $this->codigoacesso,
					"SeuNumeroLote" => $numerolote,
					"Operacao" => "I",
					"QtdeProdutosNestaChamada" => count($arr_produto2),
					"DadosBasicosProdutoFormatoXML" => $xml,
					"ListaEmailsNotificacao" => $this->emailnotificacao,
					"ColecaoAlertas" => "",
					"MensagemErro" => ""
				);
				$this->log_write("IncluirProdutoLote (inclusao)\r\n".json_encode($param));
				$result = $this->soap->__soapCall("IncluirProdutoLote", array($param));
				$this->soap_increment();
				$mensagemerro = (string) $result->MensagemErro;
				if(strlen($mensagemerro) > 0){
					$_SESSION["ERROR"] = str_replace("|", "<br>", $mensagemerro);
					return FALSE;
				}
			}

			// Encerra o lote
			$param = array(
				"Centralizador" => $this->centralizador,
				"CaixaEntrada" => $this->caixaentrada,
				"CodigoAcesso" => $this->codigoacesso,
				"SeuNumeroLote" => $numerolote,
				"Operacao" => "E",
				"QtdeProdutosNestaChamada" => 0,
				"DadosBasicosLoteProdutoFormatoXML" => "",
				"ListaEmailsNotificacao" => $this->emailnotificacao,
				"ColecaoAlertas" => "",
				"MensagemErro" => ""
			);
			$this->log_write("IncluirProdutoLote (encerramento)\r\n".json_encode($param));
			$result = $this->soap->__soapCall("IncluirProdutoLote", array($param));
			$this->soap_increment();
			$mensagemerro = (string) $result->MensagemErro;
			if(strlen($mensagemerro) > 0){
				$_SESSION["ERROR"] = str_replace("|", "<br>", $mensagemerro);
				return FALSE;
			}

			// Armazena a data de inclusao nos produtos
			$i = 1;
			$n = count($arr_produto);
			foreach($arr_produto as $produto){
				setprogress(($i / $n * 100), "Atualizando data de inclusao dos produtos: ".$i." de ".$n);
				$produto->setdtsaneamentoins(date("Y-m-d"));
				$produto->save();
				$i++;
			}
		}else{
			// Faz a inclusao dos produtos
			$i = 1;
			foreach($arr_produto as $produto){
				setprogress(($i / count($arr_produto) * 100), "Enviando produtos: {$i} de ".count($arr_produto));
				$i++;

				$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
				$xml .= "<InclusaoProduto>";
				$xml .= $this->xml_produto($produto, FALSE);
				$xml .= "</InclusaoProduto>";

				$param = array(
					"Centralizador" => $this->centralizador,
					"CaixaEntrada" => $this->caixaentrada,
					"CodigoAcesso" => $this->codigoacesso,
					"SeuNumeroLote" => "",
					"Operacao" => "I",
					"QtdeProdutosNestaChamada" => "1",
					"DadosBasicosProdutoFormatoXML" => $xml,
					"ListaEmailsNotificacao" => $this->emailnotificacao,
					"ColecaoAlertas" => "",
					"MensagemErro" => ""
				);

				$this->log_write("IncluirProduto\r\n".json_encode($param));
				$result = $this->soap->__soapCall("IncluirProduto", array($param));
				$this->soap_increment();
				$mensagemerro = (string) $result->MensagemErro;
				$mensagemerro = trim(utf8_encode($mensagemerro));
				if(strlen($mensagemerro) > 0){
					// Verifica se eh EAN cadastrado em outro produto
					// Se for, reenvia o produto acusado de possuir o EAN
					if(strpos($mensagemerro, "cadastrado como um PLU ou existe relacionado") !== false){
						$codproduto_aux = explode(":", $mensagemerro);
						$codproduto_aux = explode("-", $codproduto_aux[1]);
						$codproduto_aux = trim($codproduto_aux[0]);
						$produto_aux = objectbytable("produto", $codproduto_aux, $this->con);
						if(!$this->incluir(array($produto_aux))){
							return FALSE;
						}else{
							$this->incluir(array($produto));
							continue;
						}
					}else{
						$_SESSION["ERROR"] = str_replace("|", "<br>", $mensagemerro);
						return FALSE;
					}
				}

				$produto->setdtsaneamentoins(date("Y-m-d"));
				if(!$produto->save()){
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	private function ncm($idncm){
		if(strlen($idncm) > 0){
			$ncm = $this->arr_ncm[$idncm];
			if(!is_object($ncm)){
				$ncm = objectbytable("ncm", $idncm, $this->con);
				$this->arr_ncm[$idncm] = $ncm;
			}
		}else{
			$ncm = objectbytable("ncm", NULL, $this->con);
		}
		return $ncm;
	}

	function setregimetributario($regimetributario){
		$this->regimetributario = $regimetributario;
	}

	function setuf($uf){
		$this->uf = $uf;
	}

	private function soap_increment(){
		$this->soap_count++;
		if($this->soap_count >= 10){
		//	$this->soap_reconnect();
		}
	}

	private function soap_reconnect(){
		unset($this->soap);
		for($i = 0; $i < 100; $i++){
			try{
				$this->log_write("Reconectando com o servidor IT-Works.");
				$this->soap = new SoapClient("http://app1.rfdmonitor.com.br/servicos/ws/RFDProdutoWS.asmx?wsdl", array(
					"encoding" => "ISO-8859-1",
					"exceptions" => true,
					"soap_version" => SOAP_1_2
				));
				break;
			}catch(Exception $exc){
				$this->log_write("Erro ao reconectar com o servidor IT-Works:\r\n".$exc->getMessage());
				usleep(500);
			}
		}
		$this->soap_count = 0;
	}

	private function unidade($codunidade){
		$unidade = $this->arr_unidade[$codunidade];
		if(!is_object($unidade)){
			$unidade = objectbytable("unidade", $codunidade, $this->con);
			$this->arr_unidade[$codunidade] = $embalagem;
		}
		return $unidade;
	}

	private function validar_xml_resposta($xml_resposta){
		$xml_resposta = strtolower((string) $xml_resposta);
		return (strpos($xml_resposta, "vigencia") !== FALSE);
	}

	private function xml_produto(Produto $produto, $completo = TRUE, $numitem = NULL){
		$embalagemcpa = $this->embalagem($produto->getcodembalcpa());
		$embalagemvda = $this->embalagem($produto->getcodembalvda());
		$unidadecpa = $this->unidade($embalagemcpa->getcodunidade());
		$unidadevda = $this->unidade($embalagemvda->getcodunidade());

		$classfiscal = $this->classfiscal($produto->getcodcfnfe());

		$produtoean = objectbytable("produtoean", NULL, $this->con);
		$produtoean->setcodproduto($produto->getcodproduto());
		$arr_produtoean = object_array($produtoean);
		if(count($arr_produtoean) > 0){
			$produtoean = array_shift($arr_produtoean);
			$codean = $produtoean->getcodean();
			switch(strlen($codean)){
				case 11:
					$codean = str_pad($codean, 12, "0", STR_PAD_LEFT);
					break;
			}
			if(plutoean13($produto->getcodproduto()) == $codean || !valid_ean($codean)){
				$codean = NULL;
			}
		}else{
			$codean = NULL;
		}

		$arr = array(
			"cnpjcpfMandatorio" => removeformat($this->emitente->getcpfcnpj()),
			"Produto" => $produto->getcodproduto(),
			"CB" => $codean,
			"Apelido" => substr($this->descricao($produto->getdescricao()), 0, 30),
			"Descricao" => substr($this->descricao($produto->getdescricaofiscal()), 0, 29),
			"DescricaoFiscal" => substr($this->descricao($produto->getdescricao()), 0, 29),
			"UnidadeEstoque" => $unidadecpa->getsigla(),
			"UnidadeComercial" => $unidadevda->getsigla(),
			"FatorConvComercial" => ($embalagemvda->getquantidade() / $embalagemcpa->getquantidade()),
			"TipoItem" => "00",
			"ContaContabil" => NULL,
			"ContaContabilEntradas" => NULL,
			"IndicadorPropriedade" => "0",
			"ProdutoRegistroSped" => $produto->getcodproduto()
		);
		if($completo){
			$arr = array_merge($arr, array(
				"GeneroItem" => NULL,
				"CodigoServico" => NULL,
				"EX_IPI" => NULL,
				"Capitulo" => NULL,
				"Item" => NULL,
				"CnpjCpfPropriedade" => NULL,
				"ProdutoReferencialSPED" => NULL,
				"flagCombustivel" => "N",
				"FormulacaoDireta" => "N",
				"Desativado" => $produto->getforalinha(),
				"TAGProdutoPautado" => NULL,
				"OrigemMercadoria" => substr($classfiscal->getcodcst(), 0, 1),
				"cProdANP" => NULL
			));
		}

		$xml = "";
		foreach($arr as $name => $value){
			$xml .= "<{$name}>{$value}</{$name}>";
		}

		$xml = "<DadosBasicos".(!is_null($numitem) ? " nItem=\"{$i}\"" : "").">{$xml}</DadosBasicos>";

		return $xml;
	}

}