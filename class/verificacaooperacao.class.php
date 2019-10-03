<?php

class VerificacaoOperacao{

	// Variaveis padroes
	private $con; // Conexao com o banco de dados
	//
	// Parametros do sistema
	private $param_estoque_estoquezero; // Parametro que define como devera ser feito o tratamento de estoque
	private $param_estoque_percvarcusto; // Parametro que define variacao maxima do custo
	private $param_notafiscal_bloqprecopolitica; // Parametro que define se deve bloquear preco abaixo do politica
	private $param_notafiscal_habtransfvenda; // Parametro que define de habilita transferencia na venda
	private $param_notafiscal_maximodesconto; // Parametro que define o desconto maximo
	//
	// Parametros de entrada (valores)
	private $codestabelec; // Codigo do estabelecimento que sera verificado o estoque
	private $codestabelectransf; // Codigo do estabelecimento origem de uma transferencia (se houver)
	private $codproduto; // Codigo do produto a ser verificado
	private $quantidade; // Quantidade a ser verificada se esta disponivel no estoque
	private $qtdeunidade; // Quantidade da unidade na embalagem
	private $preco; // Preco a ser verificado se permitido
	private $valdescto; // Valor de desconto a ser aplicado no item
	private $percdescto; // Percentual de desconto a ser aplicado no item
	//
	// Parametros de entrada (objetos)
	private $natoperacao; // Objeto NatOperacao referente a operacao atual
	private $operacaonota; // Objeto OperacaoNota referente a operacao atual
	//
	// Parametros de saida
	private $error; // Array que contem o codigo do erro e a mensagem (ex: array("0" => "E001", "1" => "Total liquido do item deve ser maior que zero"))

	// Metodo construtor

	function __construct(Connection $con){
		// Informa a conexao com o banco de dados
		$this->con = $con;

		// Carrega os parametros do sistema
		$this->param_estoque_estoquezero = (string) param("ESTOQUE", "ESTOQUEZERO", $this->con);
		$this->param_estoque_percvarcusto = (string) param("ESTOQUE", "PERCVARCUSTO", $this->con);
		$this->param_notafiscal_bloqprecopolitica = (string) param("NOTAFISCAL", "BLOQPRECOPOLITICA", $this->con);
		$this->param_notafiscal_habtransfvenda = (string) param("NOTAFISCAL", "HABTRANSFVENDA", $this->con);
		$this->param_notafiscal_maximodesconto = (string) param("NOTAFISCAL", "MAXIMODESCONTO", $this->con);

		// Limpa os dados para inicar uma nova verificacao
		$this->limpar_dados();
	}

	// Carrega uma lista com o produtos caso seja composicao, caso nao seja
	// composicao, a lista vai conter apenas o produto principal
	// A lista contem os campos: codproduto, quantidade
	private function buscar_composicao($codproduto, $quantidade){
		$arr_produtos = array();
		$res = $this->con->query("SELECT * FROM composicao WHERE codproduto = {$codproduto} AND tipo IN ('A', 'V')");
		$arr = $res->fetchAll();
		if(count($arr) > 0){
			$composicao = array_shift($arr);
			$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
			$itcomposicao->setcodcomposicao($composicao["codcomposicao"]);
			$arr_itcomposicao = object_array($itcomposicao);
			foreach($arr_itcomposicao as $itcomposicao){
				$arr_produtos = array_merge($arr_produtos, $this->buscar_composicao($itcomposicao->getcodproduto(), ($itcomposicao->getquantidade() * $quantidade)));
			}
		}else{
			$arr_produtos[] = array("codproduto" => $codproduto, "quantidade" => $quantidade);
		}
		return $arr_produtos;
	}

	private function error($code, $message){
		$this->error = array($code, $message);
		/*
		 * LISTA DE ERROS
		 * E000: Succeso, sem nenhum erro ou alerta
		 * E001: Total liquido do item deve ser maior que zero
		 * E002: O custo unitario informado para o produto ultrapassa o percentual maximo de variacao
		 * E003: O valor definido no preco do produto eh menor do que o minimo definido
		 * E004: O custo reposicao do produto se encontra zerado no estabelecimento
		 * E005: O custo reposicao do produto se encontra maior que o preco de venda informado
		 * E006: Saldo de estoque do produto eh insuficiente para a quantidade informada (alerta)
		 * E007: Saldo de estoque do produto eh insuficiente para a quantidade informada (bloqueio)
		 * E008: Previsao de estoque do produto eh insuficiente para a quantidade informada (alerta)
		 * E009: Previsao de estoque do produto eh insuficiente para a quantidade informada (bloqueio)
		 * E010: O preco informado para o produto esta abaixo do preço minimo determinado pelo fabricante do produto
		 */
	}

	function resultado(){
		return $this->error;
	}

	function limpar_dados(){
		$this->error("E000", NULL);
		$this->operacaonota = NULL;
		$this->natoperacao = NULL;
		$this->codestabelec = NULL;
		$this->codproduto = NULL;
		$this->quantidade = 1;
		$this->qtdeunidade = 1;
		$this->preco = 0;
		$this->valdescto = 0;
		$this->percdescto = 0;
	}

	function setcodestabelec($codestabelec, $codestabelectransf = NULL){
		$this->codestabelec = $codestabelec;
		$this->codestabelectransf = $codestabelectransf;
	}

	function setcodproduto($codproduto){
		$this->codproduto = $codproduto;
	}

	function setnatoperacao(NatOperacao $natoperacao){
		$this->natoperacao = $natoperacao;
	}

	function setoperacaonota(OperacaoNota $operacaonota){
		$this->operacaonota = $operacaonota;
	}

	function setpercdescto($percdescto){
		$this->percdescto = $percdescto;
	}

	function setpreco($preco){
		$this->preco = $preco;
	}

	function setqtdeunidade($qtdeunidade){
		$this->qtdeunidade = $qtdeunidade;
	}

	function setquantidade($quantidade){
		$this->quantidade = $quantidade;
	}

	function setvaldescto($valdescto){
		$this->valdescto = $valdescto;
	}

	function verificar(){
		// Verifica se eh uma entrada ou saida
		switch($this->operacaonota->gettipo()){
			// Operacao entrada
			case "E":
				$param_estoque_percvarcusto = explode("|", param("ESTOQUE", "PERCVARCUSTO", $this->con));

				if(in_array($this->operacaonota->getoperacao(), array("CP")) && $param_estoque_percvarcusto[1] == "S"){
					$custodig = $this->preco / $this->qtdeunidade; // Custo informado
					$produtoestab = objectbytable("produtoestab", array($this->codestabelec, $this->codproduto), $con);
					if($custodig > $produtoestab->getprecovrj()){
						$custo = number_format($custodig, 2, ",", "");
						$precovrj = number_format($produtoestab->getprecovrj(), 2, ",", "");

						$this->error("E001", "O custo do produto é maior que o valor de venda.<br>Custo: <b>$custo</b><br>Valor de venda: <b>$precovrj</b>");

						//echo messagebox("error", "", "O custo do produto é maior que o valor de venda.<br>Custo: <b>$custo</b><br>Valor de venda: <b>$precovrj</b>");
						return FALSE;
					}
				}

				if(!in_array($this->operacaonota->getoperacao(), array("AE"))){
					$totalliquido = $this->preco - $this->valdescto - ($this->preco * ($this->percdescto / 100));
					if($totalliquido <= 0){
						$this->error("E001", "Total liquido do item deve ser maior que zero.");
						return FALSE;
					}

					if(strlen($param_estoque_percvarcusto[0]) > 0){
						$produtoestab = objectbytable("produtoestab", array($this->codestabelec, $this->codproduto), $this->con);

						$custodig = $this->preco / $this->qtdeunidade; // Custo informado
						$custotab = $produtoestab->getcustotab(); // Custo tabela atual
						$difcusto = $custotab * $this->param_estoque_percvarcusto[0] / 100; // Diferenca que pode existir entre o custo atual e o custo digitado
						$customax = $custotab + $difcusto; // Custo maximo que o produto pode ter
						$customin = $custotab - $difcusto;  // Custo minimo que o produto pode ter
						if(($custodig > $customax || $custodig < $customin) && $custotab > 0 && !in_array($this->operacaonota->getoperacao(), array("DC"))){
							if($param_estoque_percvarcusto["1"] == "S"){
								$this->error("E001", "O custo unitário informado para o produto ultrapassa o percentual máximo de variação.<br><br>Custo anterior: ".number_format($custotab, 2, ",", ".")."<br>Custo informado: ".number_format($custodig, 2, ",", ".")."<br>Diferença: ".number_format(abs($custotab - $custodig), 2, ",", "."));
							}elseif($param_estoque_percvarcusto["1"] == "P"){
								$this->error("E011", "O custo unitário informado para o produto ultrapassa o percentual máximo de variação, se quiser continuar sera necessario a senha do supervisor.<br><br>Custo anterior: ".number_format($custotab, 2, ",", ".")."<br>Custo informado: ".number_format($custodig, 2, ",", ".")."<br>Diferença: ".number_format(abs($custotab - $custodig), 2, ",", "."));
							}
						}
					}
				}
				break;
			// Operacao saida
			case "S":
				// Verificar se a logica dessa bloco esta correta
				if($this->natoperacao->getgeraestoque() == "S"){
					if(strlen($this->natoperacao->getcodestabelecestoque()) > 0){
						$codestabelecestaoque = $this->natoperacao->getcodestabelecestoque();
					}else{
						$codestabelecestaoque = $this->codestabelec;
					}
					$param_estoque_estoquezero = $this->param_estoque_estoquezero;
					if(strpos($param_estoque_estoquezero, ";") !== FALSE){
						$arr_param_estoque_estoquezero = explode(";", $param_estoque_estoquezero);
						foreach($arr_param_estoque_estoquezero as $estoquezero_estabelec){
							if(strpos($estoquezero_estabelec, "-") !== FALSE){
								$estoquezero_estabelec_val = explode("-", $estoquezero_estabelec);
								if($estoquezero_estabelec_val[1] == $codestabelecestaoque){
									$param_estoque_estoquezero = $estoquezero_estabelec_val[0];
									break;
								}
							}else{
								$param_estoque_estoquezero = $estoquezero_estabelec;
								break;
							}
						}
					}
				}else{
					$param_estoque_estoquezero = "0";
				}

				if(strlen($param_estoque_estoquezero) > 1){
					$param_estoque_estoquezero = "0";
				}

				// Verifica em qual estabelecimento vai verificar o estoque
				$codestabelecestoque = $this->codestabelec;
				if(strlen($this->codestabelectransf) > 0){
					$codestabelecestoque = $this->codestabelectransf;
				}elseif(strlen($this->natoperacao->getcodestabelecestoque()) > 0){
					$codestabelecestoque = $this->natoperacao->getcodestabelecestoque();
				}

				// Carrega uma lista com o produtos caso seja composicao, caso nao seja
				// composicao, a lista vai conter apenas o produto principal
				// A lista contem os campos: codproduto, quantidade
				$arr_ezproduto = $this->buscar_composicao($this->codproduto, ($this->quantidade * $this->qtdeunidade));

				// Explode o parametro do desocnto maximo
				$arr_param_notafiscal_maximodesconto = explode("|", $this->param_notafiscal_maximodesconto);

				// Percorre todos os itens
				foreach($arr_ezproduto as $ezproduto){
					// Carrega o produto na loja
					$produtoestab = objectbytable("produtoestab", array($codestabelecestoque, $ezproduto["codproduto"]), $this->con);

					// Verifica se o preco informado eh superior ao minimo definido
					if($arr_param_notafiscal_maximodesconto[0] === "1" && $this->operacaonota->getoperacao() === "VD"){
						$maximodesconto = $produtoestab->getcustorep() * $arr_param_notafiscal_maximodesconto[1] / 100;
						$valorminimo = $produtoestab->getcustorep() + $maximodesconto;
						if($this->preco < $valorminimo){
							$this->error("E003", "O valor definido no preço do produto é menor do que o mínimo definido de R$ ".number_format($valorminimo, 2, ",", ""));
							return FALSE;
						}
					}

					// Calcula o estoque atual e a previsao atual
					$estoque_atual = $produtoestab->getsldatual();
					$estoque_previsao = $estoque_atual + $produtoestab->getpreventrada() - $produtoestab->getprevsaida();

					// Verifica se eh uma venda agenciada
					if($this->operacaonota->getoperacao() === "VD" && $this->natoperacao->getvendaagenciada() === "S"){
						if((float) $produtoestab->getcustorep() === 0){
							$this->error("E004", "O custo reposição do produto se encontra zerado no estabelecimento, informe o custo do produto para prosseguir com a venda.<br><br><a onclick=\"$.messageBox('close'); openProgram('AjusteProduto','codestabelec={$produtoestab->getcodestabelec()}&codproduto={$produtoestab->getcodproduto()}&filtrar=S')\">Clique aqui</a> para ajustar o custo do produto.");
							return FALSE;
						}elseif($produtoestab->getcustorep() > $this->preco){
							$this->error("E005", "O custo reposição do produto se encontra maior que o preço de venda informado.<br>O custo reposição atual do produto é de R$ ".number_format($produtoestab->getcustorep(), 2, ",", ".")."<br><br>Se o custo informado se encontra incorreto, <a onclick=\"$.messageBox('close'); openProgram('AjusteProduto','codestabelec={$produtoestab->getcodestabelec()}&codproduto={$produtoestab->getcodproduto()}&filtrar=S')\">clique aqui</a> para ajustar.");
							return FALSE;
						}
					}

					// Verifica se existe um previsao de entrada para o produto
					$query = "SELECT (itpedido.qtdeunidade * itpedido.quantidade) AS quantidade, itpedido.dtentrega FROM itpedido INNER JOIN produto ON (itpedido.codproduto = produto.codproduto) WHERE itpedido.operacao = 'CP' AND itpedido.dtentrega > CURRENT_DATE AND itpedido.codestabelec = {$produtoestab->getcodestabelec()} AND itpedido.codproduto = {$produtoestab->getcodproduto()} ORDER BY itpedido.dtentrega LIMIT 1";
					$res = $this->con->query($query);
					if($res->rowCount() == 1){
						$arr = $res->fetchAll(2);
						$row = $arr[0];
						$texto_previsaoentrada = "<br><br>Pr&oacute;xima entrada: ".number_format($row["quantidade"], 4, ",", ".")." ".($row["pesado"] == "S" ? "Kg" : "unidades")." em ".convert_date($row["dtentrega"], "Y-m-d", "d/m/Y");
					}else{
						$texto_previsaoentrada = "<br><br>Pr&oacute;xima entrada: Sem previs&atilde;o";
					}

					switch($param_estoque_estoquezero){
						case "0": // Nao verificar estoque
							break;
						case "1": // Alertar (estoque atual)
							if($estoque_atual - $ezproduto["quantidade"] < 0){
								$this->error("E006", "Saldo de estoque do produto {$produtoestab->getcodproduto()} é insuficiente para a quantidade informada.<br>Saldo atual (unidades): ".number_format($estoque_atual, 4, ",", ".").$texto_previsaoentrada);
							}
							break;
						case "2": // Bloquear (estoque atual)
							if($estoque_atual - $ezproduto["quantidade"] < 0){
								$this->error("E007", "Saldo de estoque do produto {$produtoestab->getcodproduto()} é insuficiente para a quantidade informada.<br>Saldo atual (unidades): ".number_format($estoque_atual, 4, ",", ".").$texto_previsaoentrada);
								return FALSE;
							}
							break;
						case "3": // Alertar (previsao de estoque)
							if($estoque_previsao - $ezproduto["quantidade"] < 0){
								$this->error("E008", "Previsão de estoque do produto é insuficiente para a quantidade informada.<br>Previsão de estoque (unidades): ".number_format($estoque_previsao, 4, ",", ".").$texto_previsaoentrada);
							}
							break;
						case "4": // Bloquear (previsao de estoque)
							if($estoque_previsao - $ezproduto["quantidade"] < 0){
								$this->error("E009", "Previsão de estoque do produto é insuficiente para a quantidade informada.<br>Previsão de estoque (unidades): ".number_format($estoque_previsao, 4, ",", ".").$texto_previsaoentrada);
								return FALSE;
							}
						case "5": // Bloquear (previsao de estoque)							
							$param_pedido_campoadicional = param("PEDIDO","CAMPOADICIONAL", $this->con);
							if(substr($param_pedido_campoadicional,1,1) == "X"){		
								$query = "SELECT SUM(sldatual + preventrada - prevsaida) FROM produtoestab WHERE codproduto = {$produtoestab->getcodproduto()}";
								$res = $this->con->query($query);
								$estoque_previsao = $res->fetchColumn(0);
							}	

							if($estoque_previsao - $ezproduto["quantidade"] < 0){
								$this->error("E010", "Saldo de estoque do produto {$produtoestab->getcodproduto()} é insuficiente para a quantidade informada.<br>Saldo atual (unidades): ".number_format($estoque_previsao, 4, ",", ".").$texto_previsaoentrada);
								return FALSE;
							}
							break;
					}
				}

				// Carrega o objeto do produto
				$produto = objectbytable("produto", $this->codproduto, $this->con);

				// Verifica o preco politica do fabricante
				if($this->operacaonota->getoperacao() === "VD" && $this->param_notafiscal_bloqprecopolitica === "S" && $produto->getprecopolitica() > 0 && $this->preco < $produto->getprecopolitica()){
					$this->error("E010", "O preço informado para o produto está abaixo do preço mínimo determinado pelo fabricante do produto.<br>Preço Política: R$ ".$produto->getprecopolitica(TRUE));
					return FALSE;
				}
				break;
		}

		return TRUE;
	}

}