<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/itemcalculo.class.php");

class Pedido extends Cadastro{

	public $itpedido;
	public $notafiscalreferenciada;
	protected $flag_itpedido = FALSE; // Verifica se e para puxar, gravar e apagar os itens do pedido
	protected $flag_semxml = FALSE; // Não traz o xml para que não trave a tela de pedido
	protected $flag_faturamentominimo = TRUE; // Verifica o faturamento minimo

	function __construct($codestabelec = NULL, $numpedido = NULL){
		parent::__construct();
		$this->newrelation("pedido", "codestabelec", "estabelecimento", "codestabelec");
		$this->newrelation("pedido", "idnotafiscalpai", "notafiscal", "idnotafiscal");
		$this->newrelation("pedido", array("codparceiro", "tipoparceiro"), "cliente", array("codcliente", "'C'"));
		$this->newrelation("pedido", array("codparceiro", "tipoparceiro"), "fornecedor", array("codfornec", "'F'"));
		$this->newrelation("pedido", array("codparceiro", "tipoparceiro"), "estabelecimento", array("codestabelec", "'E'"), "LEFT", array(), "estabelecimento2");
		$this->table = "pedido";
		$this->primarykey = array("codestabelec", "numpedido");
		$this->setcodestabelec($codestabelec);
		$this->setnumpedido($numpedido);
		if($this->getcodestabelec() != NULL && $this->getnumpedido() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_itpedido($value){
		if(is_bool($value)){
			$this->flag_itpedido = $value;
		}
	}

	function flag_semxml($value){
		if(is_bool($value)){
			$this->flag_semxml = $value;
		}
	}

	function flag_faturamentominimo($value){
		if(is_bool($value)){
			$this->flag_faturamentominimo = $value;
		}
	}

	function calcular_totais($calcular_itens = FALSE){
		if(!is_array($this->itpedido) || sizeof($this->itpedido) == 0){
			if(strlen($this->getcodestabelec()) > 0 && strlen($this->getnumpedido()) > 0){
				$itpedido = objectbytable("itpedido", NULL, $this->con);
				$itpedido->setcodestabelec($this->getcodestabelec());
				$itpedido->setnumpedido($this->getnumpedido());
				$this->itpedido = object_array($itpedido);
			}else{
				$this->itpedido = array();
			}
		}
		/*
		$arr_campo = array(
			"totaldesconto", "totalfrete", "totalipi", "totalbaseicms", "totalicms", "totalbaseicmssubst", "totalicmssubst",
			"totalbruto", "totalliquido", "numeroitens", "totalarecolher", "totalquantidade", "pesobruto", "pesoliquido",
			"totalbasepis", "totalbasecofins", "totalpis", "totalcofins", "totalgnre", "totalicmsufdest", "totalfcpufdest",
			"totalicmsufremet", "valortotalinss", "valortotalir", "valortotalcsll", "totaldesoneracao", "totalbcfcpufdest",
			"totalbcfcpufdestst", "totalfcpufdestst", "basecalcufdest"
		);
		 *
		 */
		$arr_campo = array(
			"totaldesconto", "totalfrete", "totalipi", "totalbaseicms", "totalicms", "totalbaseicmssubst", "totalicmssubst",
			"totalbruto", "totalliquido", "numeroitens", "totalarecolher",
			"totalbasepis", "totalbasecofins", "totalpis", "totalcofins", "totalgnre", "totalicmsufdest", "totalfcpufdest",
			"totalicmsufremet", "valortotalinss", "valortotalir", "valortotalcsll", "totaldesoneracao", "basecalculofcpst",
			"valorfcpst", "totalbcfcpufdestst", "totalfcpufdestst"
		);
		foreach($arr_campo as $campo){
			$$campo = 0;
		}

		$arr_codproduto = array();
		foreach($this->itpedido as $itpedido){
			$arr_codproduto[] = $itpedido->getcodproduto();
		}
		$arr_produto = object_array_key(objectbytable("produto", NULL, $this->con), $arr_codproduto);

		if($calcular_itens){
			$itemcalculo = new ItemCalculo($this->con);
			$itemcalculo->setoperacaonota(objectbytable("operacaonota", $this->getoperacao(), $this->con));
			$itemcalculo->setestabelecimento(objectbytable("estabelecimento", $this->getcodestabelec(), $this->con));
			$itemcalculo->setparceiro(objectbytable(($this->gettipoparceiro() == "C" ? "cliente" : ($this->gettipoparceiro() == "E" ? "estabelecimento" : "fornecedor")), $this->getcodparceiro(), $this->con));
			$itemcalculo->setmodfrete($this->getmodfrete());
		}

		foreach($this->itpedido as $itpedido){

			if($calcular_itens){
				$itemcalculo->setclassfiscalnfe(NULL);
				$itemcalculo->setclassfiscalnfs(NULL);
				$itemcalculo->setitem($itpedido);
				$itemcalculo->setnatoperacao(objectbytable("natoperacao", $itpedido->getnatoperacao(), $this->con));
				$itemcalculo->calcular();
			}

			$produto = $arr_produto[$itpedido->getcodproduto()];
			$numeroitens++;
			$totaldesconto += $itpedido->gettotaldesconto();
			$totalfrete += $itpedido->gettotalfrete();
			$totalipi += $itpedido->gettotalipi();
			$totalbruto += $itpedido->gettotalbruto();
			$totalarecolher += $itpedido->gettotalarecolher();
			$totalbaseicms += $itpedido->gettotalbaseicms();
			$totalicms += $itpedido->gettotalicms();
			$totalbasepis += $itpedido->gettotalbasepis();
			$totalbasecofins += $itpedido->gettotalbasecofins();
			$totalpis += $itpedido->gettotalpis();
			$totalcofins += $itpedido->gettotalcofins();

			//$totalquantidade += $itpedido->getquantidade();
			//$pesobruto += $produto->getpesobruto() * $itpedido->getquantidade() * $itpedido->getqtdeunidade();
			//$pesoliquido += $produto->getpesoliq() * $itpedido->getquantidade() * $itpedido->getqtdeunidade();

			////Jesus - acho que não precisa do "if", caso não tenha calculado vai apresentar zero e se calculou tem que apresentar os toatis
			//mas preferi manter porque pode ter alguma situação que mesmo calculando não deve totalizar, somente caso tenha icms próprio
			//if($itpedido->gettptribicms() == "F" && $itpedido->getaliqicms() > 0){
			if($itpedido->gettptribicms() == "F" && ($itpedido->getaliqicms() > 0 || $itpedido->getaliqicmsdesoneracao() > 0)){
				$totalbaseicmssubst += $itpedido->gettotalbaseicmssubst();
				$totalicmssubst += $itpedido->gettotalicmssubst();
			}
			$totalliquido += $itpedido->gettotalliquido();
			$basecalcufdest += $itpedido->getbasecalcufdest();
			$totalicmsufdest += $itpedido->getvaloricmsufdest();
			$totalfcpufdest += $itpedido->getvalorfcpufdest();
			$totalbcfcpufdest += $itpedido->getvalorbcfcpufdest();
			$totalicmsufremet += $itpedido->getvaloricmsufremet();
			$valortotalinss += $itpedido->getvalorinss();
			$valortotalir += $itpedido->getvalorir();
			$valortotalcsll += $itpedido->getvalorcsll();
			$totaldesoneracao += $itpedido->getvalordesoneracao();
			$basecalculofcpst += $itpedido->getbasecalculofcpst();
			$valorfcpst += $itpedido->getvalorfcpst();
		}

		foreach($arr_campo as $campo){
			call_user_func(array($this, "set".$campo), $$campo);
		}
	}

	function delete(){
		$this->connect();
		// Verifica se existe entrada de nota do pedido
		$notafiscal = objectbytable("notafiscal", NULL, $this->con);
		$notafiscal->setcodestabelec($this->getcodestabelec());
		$notafiscal->setnumpedido($this->getnumpedido());
		$arr_notafiscal = object_array($notafiscal);
		if(sizeof($arr_notafiscal) > 0){
			$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel excluir o pedido, pois existem notas fiscais relacionadas com o mesmo.";
			return FALSE;
		}
		$this->con->start_transaction();
		// Apaga os itens antes do pedido
		if($this->flag_itpedido){
			foreach($this->itpedido as $itpedido){
				if(!$itpedido->delete()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}
		if(parent::delete()){
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function save($object = null){
		$this->connect();

		// Verifica se o pedido ja nao foi atendido
//		if($this->exists() && $this->getstatus() != "C"){
//			$notafiscal = objectbytable("notafiscal", NULL, $this->con);
//			$notafiscal->setcodestabelec($this->getcodestabelec());
//			$notafiscal->setnumpedido($this->getnumpedido());
//			$search = $notafiscal->searchbyobject();
//			if($search !== FALSE){
//				$idtable = notafiscal_idtable($notafiscal->getoperacao());
//				$_SESSION["ERROR"] = "Nota fiscal <a target=\"_blank\" onclick=\"openProgram('{$idtable}','idnotafiscal={$notafiscal->getidnotafiscal()}')\">{$notafiscal->getnumnotafis()}-{$notafiscal->getserie()}</a> gravada para esse pedido. N&atilde;o &eacute; poss&iacute;vel fazer altera&ccedil;&otilde;es.";
//				return FALSE;
//			}
//		}

		// Verifica se a natureza de operacao esta de acordo com o estado parceiro e o estado do estabelecimento
		$estabelecimento = objectbytable("estabelecimento", $this->getcodestabelec(), $this->con);
		switch($this->getoperacao()){
			case "PR":
				$fornecedor = objectbytable("fornecedor", $this->getcodparceiro(), $this->con);
				if($fornecedor->getuf() == $estabelecimento->getuf() && substr($this->getnatoperacao(), 0, 1) != "1"){
					$_SESSION["ERROR"] = "CFOP v&aacute;lido apenas para situa&ccedil;&otilde;es interestaduais.";
					return FALSE;
				}elseif($fornecedor->getuf() != $estabelecimento->getuf() && substr($this->getnatoperacao(), 0, 1) != "2"){
					$_SESSION["ERROR"] = "CFOP v&aacute;lido apenas para situa&ccedil;&otilde;es estaduais.";
					return FALSE;
				}
				break;
			case "CP":
				$fornecedor = objectbytable("fornecedor", $this->getcodparceiro(), $this->con);
				if($fornecedor->getuf() == $estabelecimento->getuf() && substr($this->getnatoperacao(), 0, 1) != "1"){
					$_SESSION["ERROR"] = "CFOP v&aacute;lido apenas para situa&ccedil;&otilde;es interestaduais.";
					return FALSE;
				}elseif($fornecedor->getuf() != $estabelecimento->getuf() && substr($this->getnatoperacao(), 0, 1) != "2"){
					$_SESSION["ERROR"] = "CFOP v&aacute;lido apenas para situa&ccedil;&otilde;es estaduais.";
					return FALSE;
				}
				break;
			case "VD":
				$cliente = objectbytable("cliente", $this->getcodparceiro(), $this->con);
				if($cliente->getufres() == $estabelecimento->getuf() && substr($this->getnatoperacao(), 0, 1) != "5"){
					$_SESSION["ERROR"] = "CFOP v&aacute;lido apenas para situa&ccedil;&otilde;es interestaduais.";
					return FALSE;
				}elseif($cliente->getufres() != $estabelecimento->getuf() && substr($this->getnatoperacao(), 0, 1) != "6"){
					if(substr($this->getnatoperacao(), 0, 1) != "7"){
						$_SESSION["ERROR"] = "CFOP v&aacute;lido apenas para situa&ccedil;&otilde;es estaduais.";
						//return FALSE;
					}
				}
				break;
		}

		// Verifica se nao e uma transferencia onde o estabelecimento origem e o mesmo que o destino
		if(in_array($this->getoperacao(), array("TE", "TS")) && $this->getcodestabelec() == $this->getcodparceiro()){
			$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel conclu&iacute;r tranfer&ecirc;ncia onde a origem e destino s&atilde;o iguais.<br>Informe um estabelecimento diferente para origem ou destino.";
			return FALSE;
		}

		// Verifica se e um pedido de compra e se atingiu o faturamento minimo do fornecedor
		if($this->getoperacao() == "CP" && $this->flag_faturamentominimo){
			$fornecedor = objectbytable("fornecedor", $this->getcodparceiro(), $this->con);
			$fornecestab = objectbytable("fornecestab", array($this->getcodestabelec(), $this->getcodparceiro()), $this->con);

			if(strlen($fornecestab->getfaturamentominimo()) > 0){
				$faturamentominimo = $fornecestab->getfaturamentominimo();
			}else{
				$faturamentominimo = $fornecedor->getfaturamentominimo();
			}

			if($this->gettotalliquido() < $faturamentominimo && $this->getstatus() != "A"){
				$_SESSION["ERROR"] = "Total do pedido n&atilde;o alcan&ccedil;ou o faturamento m&iacute;nimo exigido pelo fornecedor.";
				return FALSE;
			}
		}

		// Verifica se a categoria e subcategoria de lancamento foi preenchida na natureza de operacao
		$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
		if(strlen($natoperacao->getcodcatlancto()) == 0 || strlen($natoperacao->getcodsubcatlancto()) == 0){
			$_SESSION["ERROR"] = "Informe a categoria e subcategoria de lan&ccedil;amento para Natureza de Opera&ccedil;&atilde;o <b>{$this->getnatoperacao()}</b>.<br><a onclick=\"$.messageBox('close'); openProgram('NatOper','natoperacao=".$this->getnatoperacao()."')\">Clique aqui</a> para abrir o cadastro de Natureza de Operação.";
			return FALSE;
		}

		// Verifica se deve incluir uma premiacao na venda
		if(strlen($this->getnumpedido()) == 0 && $this->getoperacao() == "VD"){
			$arr_premio = object_array(objectbytable("premio", NULL, $this->con));
			if(sizeof($arr_premio) > 0){
				$notafiscal = objectbytable("notafiscal", NULL, $this->con);
				$notafiscal->setoperacao("VD");
				$notafiscal->setcodparceiro($this->getcodparceiro());
				$arr_notafiscal = object_array($notafiscal);
				$n = sizeof($arr_notafiscal) + 1;
				foreach($arr_premio as $premio){
					if($premio->getativo() == "S" && $premio->getqtdevenda() == $n){
						$this->setcodpremio($premio->getcodpremio());
						if(strlen($this->getobservacao()) > 0){
							$this->setobservacao($this->getobservacao()."\n\n");
						}
						$this->setobservacao($this->getobservacao()."INCLUIR PREMIO:\n".$premio->getdescricao());
						break;
					}
				}
			}
		}

		// Verifica se o pedido é bonificado
		if(is_array($this->itpedido) && count($this->itpedido)){
			$pedidobonificado = 0;
			$totalitens = count($this->itpedido);
			foreach($this->itpedido as $itens){
				if($itens->getbonificado() == "S"){
					$pedidobonificado++;
				}
			}
			if($totalitens == $pedidobonificado && $this->getbonificacao() == "N" && $totalitens > 0){
				$this->setbonificacao("S");
			}
		}

		// Inicia uma transacao
		$this->con->start_transaction();

		// Trata emissão própria
		if(strlen($this->getemissaopropria()) == 0){
			if(in_array($this->getoperacao(), array("DC", "VD", "TS", "PR", "EX", "IM", "DF", "PD", "NC"))){
				$this->setemissaopropria("S");
			}
		}

		// Grava o pedido
		if(!parent::save($object)){
			$this->con->rollback();
			return FALSE;
		}

		// Verifica se esta trabalhando com os itens
		if($this->flag_itpedido){
			// Verifica se a lista de itens nao esta vazia
			if(sizeof($this->itpedido) == 0){
				$_SESSION["ERROR"] = "Informe os itens do pedido.";
				$this->con->rollback();
				return FALSE;
			}

			// Verifica se os itens e o pedido tem o mesmo primeiro digito do CFOP
			$arr_natoperacao = array(substr($this->getnatoperacao(), 0, 1)); // Ja inclui a natureza principal do pedido
			foreach($this->itpedido as $itpedido){ // Percorre todos os itens
				$arr_natoperacao[] = substr($itpedido->getnatoperacao(), 0, 1); // Inclui a natureza dos itens
			}
			$arr_natoperacao = array_unique($arr_natoperacao);
			if(count(array_unique($arr_natoperacao)) > 1){ // Verifica se diversifica o primeiro digito das naturezas
				$_SESSION["ERROR"] = "Natureza de opera&ccedil;&atilde;o principal do pedido e dos itens não conferem.";
				$this->con->rollback();
				return FALSE;
			}

			// Busca os itens antigos e verifica se deve ser apagado
			$itpedido = objectbytable("itpedido", NULL, $this->con);
			$itpedido->setcodestabelec($this->getcodestabelec());
			$itpedido->setnumpedido($this->getnumpedido());
			$arr_itpedido = object_array($itpedido);
			foreach($arr_itpedido as $itpedido_db){
				$found = FALSE;
				foreach($this->itpedido as $itpedido_ob){
					if($itpedido_db->getiditpedido() === $itpedido_ob->getiditpedido()){
						$found = TRUE;
						break;
					}
				}
				if(!$found){
					if(!$itpedido_db->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}

			// Gravar os itens do pedido
			foreach($this->itpedido as $itpedido){
				$itpedido->setconnection($this->con);
				$quantidade = $itpedido->getquantidade();
				$preco = $itpedido->getpreco();
				if($quantidade <= 0 && !in_array($this->getoperacao(), array("AE", "AS"))){
					$_SESSION["ERROR"] = "Quantidade de Unidades do produto <b>".$itpedido->getcodproduto()."</b> deve ser maior que 0 (zero).";
					$this->con->rollback();
					return FALSE;
				}
				if(($preco <= 0 && !in_array($this->getoperacao(), array("AE", "AS", "CP"))) || ($this->getoperacao() == "CP" && $preco < 0)){
					$_SESSION["ERROR"] = "O preço do produto <b>".$itpedido->getcodproduto()."</b> deve ser maior que 0 (zero).";
					$this->con->rollback();
					return FALSE;
				}
				if(($preco <= 0 && !in_array($this->getoperacao(), array("AE", "AS", "CP"))) || ($this->getoperacao() == "CP" && $preco == 0)){
					$_SESSION["ERROR"] = "O preço do produto <b>".$itpedido->getcodproduto()."</b> deve ser maior que 0 (zero).";
				}
				$itpedido->setnumpedido($this->getnumpedido());
				$itpedido->setcodestabelec($this->getcodestabelec());
				if(strlen($itpedido->getdtentrega()) == 0){
					$itpedido->setdtentrega($this->getdtentrega());
				}
				if(!$itpedido->save()){
					$this->con->rollback();
					return FALSE;
				}
			}

			// Verifica se deve fazer a distribuicao de compra com transferencia
			if(is_array($_SESSION["pedido_itpedido_compradistrib"]) && $this->getoperacao() == "CP"){
				if(param("NOTAFISCAL", "HABCOMPRADISTRIB", $this->con) == "2"){
					foreach($_SESSION["pedido_itpedido_compradistrib"] as $seqitem => $arr_distrib){
						$achou = FALSE;
						foreach($this->itpedido as $itpedido){
							if($itpedido->getseqitem() == $seqitem){
								$achou = TRUE;
								break;
							}
						}
						if(!$achou){
							$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o item de sequencial {$seqitem} para distribui&ccedil;&atilde;o.";
							$this->con->rollback();
							return FALSE;
						}
						foreach($arr_distrib as $codestabelec => $quantidade){
							if($quantidade == 0){
								continue;
							}
							// Apaga a distribuicao antiga
							$pedidodistrib = objectbytable("pedidodistrib", NULL, $this->con);
							$pedidodistrib->setiditpedidocp($itpedido->getiditpedido());
							$pedidodistrib->setcodestabelec($codestabelec);
							$arr_pedidodistrib = object_array($pedidodistrib);
							foreach($arr_pedidodistrib as $pedidodistrib){
								if(!$pedidodistrib->delete()){
									$this->con->rollback();
									return FALSE;
								}
							}
							// Criar nova distribuicao
							$pedidodistrib = objectbytable("pedidodistrib", NULL, $this->con);
							$pedidodistrib->setiditpedidocp($itpedido->getiditpedido());
							$pedidodistrib->setcodestabelec($codestabelec);
							$pedidodistrib->setquantidade($quantidade);
							if(!$pedidodistrib->save()){
								$this->con->rollback();
								return FALSE;
							}
						}
					}
				}
			}
		}

		// Atualiza o vendedor do cliente
		if($this->getoperacao() == "VD" && param("NOTAFISCAL", "BLOQCLIENTEVENDEDOR", $this->con) == "S"){
			$cliente = objectbytable("cliente", $this->getcodparceiro(), $this->con);
			$cliente->setcodvendedor($this->getcodfunc());
			if(!$cliente->save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		// Verifica se gerou os lancamentos (bugs que ainda nao foi descoberto porque as vezes nao gera os lancamentos)
		if($this->getstatus() != "C" && $this->getbonificacao() != "S" && $this->gettotalliquido() > 0){
			$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
			$especie = objectbytable("especie", $this->getcodespecie(), $this->con);
			if($especie->getgerafinanceiro() == "S" && $natoperacao->getgerafinanceiro() == "S" && !in_array($this->getoperacao(), array("DC", "DF"))){
				$lancamento = objectbytable("lancamento", NULL, $this->con);
				$lancamento->setcodestabelec($this->getcodestabelec());
				$lancamento->setnumpedido($this->getnumpedido());
				$arr_lancamento = object_array($lancamento);
				if(count($arr_lancamento) == 0){
					$_SESSION["ERROR"] = "Houve uma falha ao gerar os lan&ccedil;amentos financeiros do pedido, tente gravar novamente.";
					$this->con->rollback();
					return FALSE;
				}
			}
		}

		// Grava as notas referenciadas
		if(!in_array($this->getoperacao(), array("TE", "TS"))){
			$notafiscalreferenciada = objectbytable("notafiscalreferenciada", NULL, $this->con);
			$notafiscalreferenciada->setnumpedido($this->getnumpedido());
			$notafiscalreferenciada->setcodestabelec($this->getcodestabelec());
			$arr_notafiscalreferenciada = object_array($notafiscalreferenciada);
			foreach($arr_notafiscalreferenciada as $notafiscalreferenciada_db){
				$found = FALSE;
				if(is_array($this->notafiscalreferenciada)){
					foreach($this->notafiscalreferenciada as $notafiscalreferenciada_ob){
						if($notafiscalreferenciada_db->getidnotafiscalreferenciada() === $notafiscalreferenciada_ob->getidnotafiscalreferenciada()){
							$found = TRUE;
							break;
						}
					}
				}
				if(!$found){
					if(!$notafiscalreferenciada_db->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}

			if(is_array($this->notafiscalreferenciada)){
				foreach($this->notafiscalreferenciada as $notafiscalreferenciada){
					$notafiscalreferenciada->setnumpedido($this->getnumpedido());
					$notafiscalreferenciada->setcodestabelec($this->getcodestabelec());
					if(!$notafiscalreferenciada->save()){
						$this->con->rollback();
						return TRUE;
					}
				}
			}
		}

		// Atualiza tudo no banco de dados
		$this->con->commit();
		return TRUE;
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return[0])){
			$this->itpedido = array();
			// Verifica se vai trabalhar com os itens
			if($this->flag_itpedido){
				$itpedido = objectbytable("itpedido", NULL, $this->con);
				$itpedido->setcodestabelec($this->getcodestabelec());
				$itpedido->setnumpedido($this->getnumpedido());
				$itpedido->setorder("seqitem");
				$this->itpedido = object_array($itpedido);

				$this->notafiscalreferenciada = array();
				$notafiscalreferenciada = objectbytable("notafiscalreferenciada", NULL, $this->con);
				$notafiscalreferenciada->setcodestabelec($this->getcodestabelec());
				$notafiscalreferenciada->setnumpedido($this->getnumpedido());
				$this->notafiscalreferenciada = object_array($notafiscalreferenciada);
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		// Itens do pedido
		if($this->flag_itpedido){
			$objectsession = new ObjectSession($this->con, "itpedido", "pedido_itpedido_".$this->getoperacao());
			$this->itpedido = $objectsession->getobject();
			$objectsession = new ObjectSession($this->con, "notafiscalreferenciada", "pedido_notafiscalreferenciada_".$this->getoperacao());
			$this->notafiscalreferenciada = $objectsession->getobject();
		}
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumpedido(){
		return $this->fields["numpedido"];
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getdtemissao($format = FALSE){
		if($format){
			return convert_date($this->fields["dtemissao"], "Y-m-d", "d/m/Y");
		}else{
			return $this->fields["dtemissao"];
		}
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getrefpedido(){
		return $this->fields["refpedido"];
	}

	function getnumcotacao(){
		return $this->fields["numcotacao"];
	}

	function getcodfornec(){
		return $this->fields["codfornec"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog(){
		return $this->fields["datalog"];
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getdtentrega($format = FALSE){
		if($format){
			return convert_date($this->fields["dtentrega"], "Y-m-d", "d/m/Y");
		}else{
			return $this->fields["dtentrega"];
		}
	}

	function getdtstatus(){
		return $this->fields["dtstatus"];
	}

	function getdtvalidade($format = FALSE){
		if($format){
			return convert_date($this->fields["dtvalidade"], "Y-m-d", "d/m/Y");
		}else{
			return $this->fields["dtvalidade"];
		}
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getcodtransp(){
		return $this->fields["codtransp"];
	}

	function getbonificacao(){
		return $this->fields["bonificacao"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function gettotaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["totaldesconto"], 2, ",", "") : $this->fields["totaldesconto"]);
	}

	function gettotalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimo"], 2, ",", "") : $this->fields["totalacrescimo"]);
	}

	function gettotalfrete($format = FALSE){
		return ($format ? number_format($this->fields["totalfrete"], 2, ",", "") : $this->fields["totalfrete"]);
	}

	function gettotalipi($format = FALSE){
		return ($format ? number_format($this->fields["totalipi"], 2, ",", "") : $this->fields["totalipi"]);
	}

	function gettotalbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicms"], 2, ",", "") : $this->fields["totalbaseicms"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"], 2, ",", "") : $this->fields["totalicms"]);
	}

	function gettotalbaseicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmssubst"], 2, ",", "") : $this->fields["totalbaseicmssubst"]);
	}

	function gettotalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubst"], 2, ",", "") : $this->fields["totalicmssubst"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"], 2, ",", "") : $this->fields["totalbruto"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"], 2, ",", "") : $this->fields["totalliquido"]);
	}

	function getnumeroitens(){
		return $this->fields["numeroitens"];
	}

	function gettotalbonificado($format = FALSE){
		return ($format ? number_format($this->fields["totalbonificado"], 2, ",", "") : $this->fields["totalbonificado"]);
	}

	function gettotalarecolher($format = FALSE){
		return ($format ? number_format($this->fields["totalarecolher"], 2, ",", "") : $this->fields["totalarecolher"]);
	}

	function getratdesconto(){
		return $this->fields["ratdesconto"];
	}

	function getratvaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["ratvaldesconto"], 2, ",", "") : $this->fields["ratvaldesconto"]);
	}

	function getratacrescimo(){
		return $this->fields["ratacrescimo"];
	}

	function getratvalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["ratvalacrescimo"], 2, ",", "") : $this->fields["ratvalacrescimo"]);
	}

	function getraticmssubst(){
		return $this->fields["raticmssubst"];
	}

	function getratvalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["ratvalicmssubst"], 2, ",", "") : $this->fields["ratvalicmssubst"]);
	}

	function gettotalliquidoatendido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquidoatendido"], 2, ",", "") : $this->fields["totalliquidoatendido"]);
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getarqxmlnfe(){
		return $this->fields["arqxmlnfe"];
	}

	function getrattipodesconto(){
		return $this->fields["rattipodesconto"];
	}

	function getrattipoacrescimo(){
		return $this->fields["rattipoacrescimo"];
	}

	function getidnotafiscalpai(){
		return $this->fields["idnotafiscalpai"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getcupom(){
		return $this->fields["cupom"];
	}

	function getnumeroecf(){
		return $this->fields["numeroecf"];
	}

	function getratfrete(){
		return $this->fields["ratfrete"];
	}

	function getrattipofrete(){
		return $this->fields["rattipofrete"];
	}

	function getratvalfrete($format = FALSE){
		return ($format ? number_format($this->fields["ratvalfrete"], 2, ",", "") : $this->fields["ratvalfrete"]);
	}

	function getobservacaofiscal(){
		return $this->fields["observacaofiscal"];
	}

	function gettotalquantidade($format = FALSE){
		return ($format ? number_format($this->fields["totalquantidade"], 4, ",", "") : $this->fields["totalquantidade"]);
	}

	function getespecie(){
		return $this->fields["especie"];
	}

	function getmarca(){
		return $this->fields["marca"];
	}

	function getnumeracao(){
		return $this->fields["numeracao"];
	}

	function getpesobruto($format = FALSE){
		return ($format ? number_format($this->fields["pesobruto"], 4, ",", "") : $this->fields["pesobruto"]);
	}

	function getpesoliquido($format = FALSE){
		return ($format ? number_format($this->fields["pesoliquido"], 4, ",", "") : $this->fields["pesoliquido"]);
	}

	function getmodfrete(){
		return $this->fields["modfrete"];
	}

	function gettranspplacavei(){
		return $this->fields["transpplacavei"];
	}

	function gettranspufvei(){
		return $this->fields["transpufvei"];
	}

	function gettransprntc(){
		return $this->fields["transprntc"];
	}

	function getufdesembaraco(){
		return $this->fields["ufdesembaraco"];
	}

	function getlocaldesembaraco(){
		return $this->fields["localdesembaraco"];
	}

	function getdtdesembaraco($format = FALSE){
		return ($format ? convert_date($this->fields["dtdesembaraco"], "Y-m-d", "d/m/Y") : $this->fields["dtdesembaraco"]);
	}

	function getnumerodi(){
		return $this->fields["numerodi"];
	}

	function getdtregistrodi($format = FALSE){
		return ($format ? convert_date($this->fields["dtregistrodi"], "Y-m-d", "d/m/Y") : $this->fields["dtregistrodi"]);
	}

	function gettotalbaseii($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseii"], 2, ",", "") : $this->fields["totalbaseii"]);
	}

	function gettotalii($format = FALSE){
		return ($format ? number_format($this->fields["totalii"], 2, ",", "") : $this->fields["totalii"]);
	}

	function gettotaliof($format = FALSE){
		return ($format ? number_format($this->fields["totaliof"], 2, ",", "") : $this->fields["totaliof"]);
	}

	function gettotalseguro($format = FALSE){
		return ($format ? number_format($this->fields["totalseguro"], 2, ",", "") : $this->fields["totalseguro"]);
	}

	function gettotaldespaduaneira($format = FALSE){
		return ($format ? number_format($this->fields["totaldespaduaneira"], 2, ",", "") : $this->fields["totaldespaduaneira"]);
	}

	function gettotalsiscomex($format = FALSE){
		return ($format ? number_format($this->fields["totalsiscomex"], 2, ",", "") : $this->fields["totalsiscomex"]);
	}

	function gettotalpis($format = FALSE){
		return ($format ? number_format($this->fields["totalpis"], 2, ",", "") : $this->fields["totalpis"]);
	}

	function gettotalcofins($format = FALSE){
		return ($format ? number_format($this->fields["totalcofins"], 2, ",", "") : $this->fields["totalcofins"]);
	}

	function getmodelodocfiscal(){
		return $this->fields["modelodocfiscal"];
	}

	function getidnotafiscalref(){
		return $this->fields["idnotafiscalref"];
	}

	function getfinalidade(){
		return $this->fields["finalidade"];
	}

	function gettipoemissao(){
		return $this->fields["tipoemissao"];
	}

	function gettotalbasepis($format = FALSE){
		return ($format ? number_format($this->fields["totalbasepis"], 2, ",", "") : $this->fields["totalbasepis"]);
	}

	function gettotalbasecofins($format = FALSE){
		return ($format ? number_format($this->fields["totalbasecofins"], 2, ",", "") : $this->fields["totalbasecofins"]);
	}

	function getxmlnfe(){
		if($this->flag_semxml){
			return "";
		}else{
			return $this->fields["xmlnfe"];
		}
	}

	function getdtdigitacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtdigitacao"], "Y-m-d", "d/m/Y") : $this->fields["dtdigitacao"]);
	}

	function getcodfornecref(){
		return $this->fields["codfornecref"];
	}

	function getcodcotacao(){
		return $this->fields["codcotacao"];
	}

	function getautomatico(){
		return $this->fields["automatico"];
	}

	function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function getcodrepresentante(){
		return $this->fields["codrepresentante"];
	}

	function getcodpremio(){
		return $this->fields["codpremio"];
	}

	function getorigemreg(){
		return $this->fields["origemreg"];
	}

	function getcodorcamento(){
		return $this->fields["codorcamento"];
	}

	function gettotalcustotab($format = FALSE){
		return ($format ? number_format($this->fields["totalcustotab"], 2, ",", "") : $this->fields["totalcustotab"]);
	}

	function getmotivonegado(){
		return $this->fields["motivonegado"];
	}

	function getnumdistribuicao(){
		return $this->fields["numdistribuicao"];
	}

	function gettotalgnre($format = FALSE){
		return ($format ? number_format($this->fields["totalgnre"], 2, ",", "") : $this->fields["totalgnre"]);
	}

	function getimpresso(){
		return $this->fields["impresso"];
	}

    function getreciboimpresso(){
        return $this->fields["reciboimpresso"];
    }

	function getsuperimpressao(){
		return $this->fields["impresso"];
	}

	function getchavenferef(){
		return $this->fields["chavenferef"];
	}

	function gettotalvalorafrmm($format = FALSE){
		return ($format ? number_format($this->fields["totalvalorafrmm"], 2, ",", "") : $this->fields["totalvalorafrmm"]);
	}

	function getemissaopropria(){
		return $this->fields["emissaopropria"];
	}

	function gettipoajuste(){
		return $this->fields["tipoajuste"];
	}

	function getindpres(){
		return $this->fields["indpres"];
	}

	function gettotalicmsufremet($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsufremet"], 2, ",", "") : $this->fields["totalicmsufremet"]);
	}

	function getbasecalcufdest($format = FALSE){
		return ($format ? number_format($this->fields["basecalcufdest"], 2, ",", "") : $this->fields["basecalcufdest"]);
	}

	function gettotalicmsufdest($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsufdest"], 2, ",", "") : $this->fields["totalicmsufdest"]);
	}

	function gettotalbcfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["totalbcfcpufdest"], 2, ",", "") : $this->fields["totalbcfcpufdest"]);
	}

	function gettotalfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["totalfcpufdest"], 2, ",", "") : $this->fields["totalfcpufdest"]);
	}

	function gettotalbcfcpufdestst($format = FALSE){
		return ($format ? number_format($this->fields["totalbcfcpufdestst"], 2, ",", "") : $this->fields["totalbcfcpufdestst"]);
	}

	function gettotalfcpufdestst($format = FALSE){
		return ($format ? number_format($this->fields["totalfcpufdestst"], 2, ",", "") : $this->fields["totalfcpufdestst"]);
	}

	function gettotalbasestretido($format = FALSE){
		return ($format ? number_format($this->fields["totalbasestretido"], 4, ",", "") : $this->fields["totalbasestretido"]);
	}

	function gettotalvalorstretido($format = FALSE){
		return ($format ? number_format($this->fields["totalvalorstretido"], 4, ",", "") : $this->fields["totalvalorstretido"]);
	}

	function getcupomnotafiscal(){
		return $this->fields["cupomnotafiscal"];
	}

	function getdivergenciavalidacaoxml(){
		return $this->fields["divergenciavalidacaoxml"];
	}

	function getdivergenciaromaneio(){
		return $this->fields["divergenciaromaneio"];
	}

	function gettipopedido(){
		return $this->fields["tipopedido"];
	}

	function getvalortotalinss($format = FALSE){
		return ($format ? number_format($this->fields["valortotalinss"], 2, ",", "") : $this->fields["valortotalinss"]);
	}

	function getvalortotalir($format = FALSE){
		return ($format ? number_format($this->fields["valortotalir"], 2, ",", "") : $this->fields["valortotalir"]);
	}

	function getvalortotalcsll($format = FALSE){
		return ($format ? number_format($this->fields["valortotalcsll"], 2, ",", "") : $this->fields["valortotalcsll"]);
	}

	function gettotaldesoneracao($format = FALSE){
		return ($format ? number_format($this->fields["totaldesoneracao"], 2, ",", "") : $this->fields["totaldesoneracao"]);
	}

	function getcodrastreio(){
		return $this->fields["codrastreio"];
	}

	function getbasecalculofcpst($format = FALSE){
		return ($format ? number_format($this->fields["basecalculofcpst"], 2, ",", "") : $this->fields["basecalculofcpst"]);
	}

	function getvalorfcpst($format = FALSE){
		return ($format ? number_format($this->fields["valorfcpst"], 2, ",", "") : $this->fields["valorfcpst"]);
	}

	function gettotalbaseicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmsnaoaproveitavel"], 2, ",", "") : $this->fields["totalbaseicmsnaoaproveitavel"]);
	}

	function gettotalicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsnaoaproveitavel"], 2, ",", "") : $this->fields["totalicmsnaoaproveitavel"]);
	}

	function getnumerotid(){
		return $this->fields["numerotid"];
	}

	function getchavecfe(){
		return $this->fields["chavecfe"];
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumpedido($value){
		$this->fields["numpedido"] = value_numeric($value);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value, 2);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setrefpedido($value){
		$this->fields["refpedido"] = value_string($value, 20);
	}

	function setnumcotacao($value){
		$this->fields["numcotacao"] = value_numeric($value);
	}

	function setcodfornec($value){
		$this->fields["codfornec"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 5000);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function setdtentrega($value){
		$this->fields["dtentrega"] = value_date($value);
	}

	function setdtstatus($value){
		$this->fields["dtstatus"] = value_date($value);
	}

	function setdtvalidade($value){
		$this->fields["dtvalidade"] = value_date($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_check($value, array("A", "P", "C", "L"));
	}

	function setcodtransp($value){
		$this->fields["codtransp"] = value_numeric($value);
	}

	function setbonificacao($value){
		$this->fields["bonificacao"] = value_check($value, array("S", "N"));
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function settotaldesconto($value){
		$this->fields["totaldesconto"] = value_numeric($value);
	}

	function settotalacrescimo($value){
		$this->fields["totalacrescimo"] = value_numeric($value);
	}

	function settotalfrete($value){
		$this->fields["totalfrete"] = value_numeric($value);
	}

	function settotalipi($value){
		$this->fields["totalipi"] = value_numeric($value);
	}

	function settotalbaseicms($value){
		$this->fields["totalbaseicms"] = value_numeric($value);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}

	function settotalbaseicmssubst($value){
		$this->fields["totalbaseicmssubst"] = value_numeric($value);
	}

	function settotalicmssubst($value){
		$this->fields["totalicmssubst"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function setnumeroitens($value){
		$this->fields["numeroitens"] = value_numeric($value);
	}

	function settotalbonificado($value){
		$this->fields["totalbonificado"] = value_numeric($value);
	}

	function settotalarecolher($value){
		$this->fields["totalarecolher"] = value_numeric($value);
	}

	function setratdesconto($value){
		$this->fields["ratdesconto"] = value_check($value, array("S", "N"));
	}

	function setratvaldesconto($value){
		$this->fields["ratvaldesconto"] = value_numeric($value);
	}

	function setratacrescimo($value){
		$this->fields["ratacrescimo"] = value_check($value, array("S", "N"));
	}

	function setratvalacrescimo($value){
		$this->fields["ratvalacrescimo"] = value_numeric($value);
	}

	function setraticmssubst($value){
		$this->fields["raticmssubst"] = value_check($value, array("S", "N"));
	}

	function setratvalicmssubst($value){
		$this->fields["ratvalicmssubst"] = value_numeric($value);
	}

	function settotalliquidoatendido($value){
		$this->fields["totalliquidoatendido"] = value_numeric($value);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value, 1);
	}

	function setarqxmlnfe($value){
		$this->fields["arqxmlnfe"] = value_string($value, 200);
	}

	function setrattipodesconto($value){
		$this->fields["rattipodesconto"] = value_check($value, array("V", "P"));
	}

	function setrattipoacrescimo($value){
		$this->fields["rattipoacrescimo"] = value_check($value, array("V", "P"));
	}

	function setidnotafiscalpai($value){
		$this->fields["idnotafiscalpai"] = value_numeric($value);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value, 1);
	}

	function setcupom($value){
		$this->fields["cupom"] = value_numeric($value);
	}

	function setnumeroecf($value){
		$this->fields["numeroecf"] = value_numeric($value);
	}

	function setratfrete($value){
		$this->fields["ratfrete"] = value_check($value, array("S", "N"));
	}

	function setratvalfrete($value){
		$this->fields["ratvalfrete"] = value_numeric($value);
	}

	function setrattipofrete($value){
		$this->fields["rattipofrete"] = value_check($value, array("V", "P"));
	}

	function setobservacaofiscal($value){
		$this->fields["observacaofiscal"] = value_string($value);
	}

	function settotalquantidade($value){
		$this->fields["totalquantidade"] = value_numeric($value);
	}

	function setespecie($value){
		$this->fields["especie"] = value_string($value, 60);
	}

	function setmarca($value){
		$this->fields["marca"] = value_string($value, 60);
	}

	function setnumeracao($value){
		$this->fields["numeracao"] = value_string($value, 60);
	}

	function setpesobruto($value){
		$this->fields["pesobruto"] = value_numeric($value);
	}

	function setpesoliquido($value){
		$this->fields["pesoliquido"] = value_numeric($value);
	}

	function setmodfrete($value){
		$this->fields["modfrete"] = value_string($value, 1);
	}

	function settranspplacavei($value){
		$this->fields["transpplacavei"] = value_string($value, 8);
	}

	function settranspufvei($value){
		$this->fields["transpufvei"] = value_string($value, 2);
	}

	function settransprntc($value){
		$this->fields["transprntc"] = value_string($value, 20);
	}

	function setufdesembaraco($value){
		$this->fields["ufdesembaraco"] = value_string($value, 2);
	}

	function setlocaldesembaraco($value){
		$this->fields["localdesembaraco"] = value_string($value, 60);
	}

	function setdtdesembaraco($value){
		$this->fields["dtdesembaraco"] = value_date($value);
	}

	function setnumerodi($value){
		//Jes Alterado o tipo do campo no banco de dados, o tipo inteiro nao suporta 10 digitos que é o tamanho da DI
		//$this->fields["numerodi"] = value_numeric($value);
		$this->fields["numerodi"] = value_string($value, 10);
	}

	function setdtregistrodi($value){
		$this->fields["dtregistrodi"] = value_date($value);
	}

	function settotalbaseii($value){
		$this->fields["totalbaseii"] = value_numeric($value);
	}

	function settotalii($value){
		$this->fields["totalii"] = value_numeric($value);
	}

	function settotaliof($value){
		$this->fields["totaliof"] = value_numeric($value);
	}

	function settotalseguro($value){
		$this->fields["totalseguro"] = value_numeric($value);
	}

	function settotaldespaduaneira($value){
		$this->fields["totaldespaduaneira"] = value_numeric($value);
	}

	function settotalsiscomex($value){
		$this->fields["totalsiscomex"] = value_numeric($value);
	}

	function settotalpis($value){
		$this->fields["totalpis"] = value_numeric($value);
	}

	function settotalcofins($value){
		$this->fields["totalcofins"] = value_numeric($value);
	}

	function setmodelodocfiscal($value){
		$this->fields["modelodocfiscal"] = value_string($value, 2);
	}

	function setidnotafiscalref($value){
		$this->fields["idnotafiscalref"] = value_string($value, 44);
	}

	function setfinalidade($value){
		$this->fields["finalidade"] = value_string($value, 1);
	}

	function settipoemissao($value){
		$this->fields["tipoemissao"] = value_string($value, 1);
	}

	function settotalbasepis($value){
		$this->fields["totalbasepis"] = value_numeric($value);
	}

	function settotalbasecofins($value){
		$this->fields["totalbasecofins"] = value_numeric($value);
	}

	function setxmlnfe($value){
		$this->fields["xmlnfe"] = value_string($value);
	}

	function setdtdigitacao($value){
		$this->fields["dtdigitacao"] = value_date($value);
	}

	function setcodfornecref($value){
		$this->fields["codfornecref"] = value_numeric($value);
	}

	function setcodcotacao($value){
		$this->fields["codcotacao"] = value_numeric($value);
	}

	function setautomatico($value){
		$this->fields["automatico"] = value_string($value, 1);
	}

	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}

	function setcodrepresentante($value){
		$this->fields["codrepresentante"] = value_numeric($value);
	}

	function setcodpremio($value){
		$this->fields["codpremio"] = value_numeric($value);
	}

	function setorigemreg($value){
		$this->fields["origemreg"] = value_string($value, 1);
	}

	function setcodorcamento($value){
		$this->fields["codorcamento"] = value_string($value);
	}

	function settotalcustotab($value){
		$this->fields["totalcustotab"] = value_numeric($value);
	}

	function setmotivonegado($value){
		$this->fields["motivonegado"] = value_string($value);
	}

	function setnumdistribuicao($value){
		$this->fields["numdistribuicao"] = value_numeric($value);
	}

	function settotalgnre($value){
		$this->fields["totalgnre"] = value_numeric($value);
	}

	function setimpresso($value){
		$this->fields["impresso"] = value_string($value, 1);
	}

    function setreciboimpresso($value){
        $this->fields["reciboimpresso"] = value_string($value, 1);
    }

	function setsuperimpressao($value){
		$this->fields["superimpressao"] = value_string($value, 20);
	}

	function settotalvalorafrmm($value){
		$this->fields["totalvalorafrmm"] = value_numeric($value);
	}

	function setchavenferef($value){
		$this->fields["chavenferef"] = value_string($value, 44);
	}

	function setemissaopropria($value){
		$this->fields["emissaopropria"] = value_string($value, 1);
	}

	function settipoajuste($value){
		$this->fields["tipoajuste"] = value_numeric($value);
	}

	function setindpres($value){
		$this->fields["indpres"] = value_numeric($value);
	}

	function settotalicmsufremet($value){
		$this->fields["totalicmsufremet"] = value_numeric($value);
	}

	function setbasecalcufdest($value){
		$this->fields["basecalcufdest"] = value_numeric($value);
	}

	function settotalicmsufdest($value){
		$this->fields["totalicmsufdest"] = value_numeric($value);
	}

	function settotalbcfcpufdest($value){
		$this->fields["totalbcfcpufdest"] = value_numeric($value);
	}

	function settotalfcpufdest($value){
		$this->fields["totalfcpufdest"] = value_numeric($value);
	}

	function settotalbcfcpufdestst($value){
		$this->fields["totalbcfcpufdestst"] = value_numeric($value);
	}

	function settotalfcpufdestst($value){
		$this->fields["totalfcpufdestst"] = value_numeric($value);
	}

	function settotalbasestretido($value){
		$this->fields["totalbasestretido"] = value_numeric($value);
	}

	function settotalvalorstretido($value){
		$this->fields["totalvalorstretido"] = value_numeric($value);
	}

	function setcupomnotafiscal($value){
		$this->fields["cupomnotafiscal"] = value_string($value);
	}

	function setdivergenciavalidacaoxml($value){
		$this->fields["divergenciavalidacaoxml"] = value_string($value, 1);
	}

	function setdivergenciaromaneio($value){
		$this->fields["divergenciaromaneio"] = value_string($value, 1);
	}

	function settipopedido($value){
		$this->fields["tipopedido"] = value_string($value, 1);
	}

	function setvalortotalinss($value){
		$this->fields["valortotalinss"] = value_numeric($value);
	}

	function setvalortotalir($value){
		$this->fields["valortotalir"] = value_numeric($value);
	}

	function setvalortotalcsll($value){
		$this->fields["valortotalcsll"] = value_numeric($value);
	}

	function settotaldesoneracao($value){
		$this->fields["totaldesoneracao"] = value_numeric($value);
	}

	function setcodrastreio($value){
		$this->fields["codrastreio"] = value_string($value,60);
	}

	function setbasecalculofcpst($value){
		$this->fields["basecalculofcpst"] = value_numeric($value);
	}

	function setvalorfcpst($value){
		$this->fields["valorfcpst"] = value_numeric($value);
	}

	function settotalbaseicmsnaoaproveitavel($value){
		$this->fields["totalbaseicmsnaoaproveitavel"] = value_numeric($value);
	}

	function settotalicmsnaoaproveitavel($value){
		$this->fields["totalicmsnaoaproveitavel"] = value_numeric($value);
	}

	function setnumerotid($value){
		$this->fields["numerotid"] = value_string($value, 20);
	}

	function setchavecfe($value){
		$this->fields["chavecfe"] = value_string($value, 44);
	}
}
