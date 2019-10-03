<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/vendamedia.class.php");
require_file("class/margem.class.php");

class ItNotaFiscal extends Cadastro{

	public $arr_itnotafiscal = array(); // Itens filhos do item pai
	public $estabelecimento;
	public $notafiscal;
	public $operacaonota;
	public $parceiro;
	protected $flag_verestoque = TRUE;

	function __construct($iditnotafiscal = NULL){
		parent::__construct();
		$this->table = "itnotafiscal";
		$this->primarykey = array("iditnotafiscal");
		$this->setiditnotafiscal($iditnotafiscal);
		if(!is_null($this->getiditnotafiscal())){
			$this->searchbyobject();
		}
	}

	function flag_verestoque($value){
		if(is_bool($value)){
			$this->flag_verestoque = $value;
		}
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	function setnotafiscal($notafiscal){
		$this->notafiscal = $notafiscal;
	}

	function setoperacaonota($operacaonota){
		$this->operacaonota = $operacaonota;
	}

	function criar_transferencia($codestabelec_orig, $codestabelec_dest){
		$novo_pedido = true; // Variavel que define se deve criar um novo pedido de transferencia
		// Verifica se ja existe um pedido pendente
		$pedido = objectbytable("pedido", NULL, $this->con);
		$pedido->setoperacao("TS");
		$pedido->setcodestabelec($codestabelec_orig);
		$pedido->setcodparceiro($codestabelec_dest);
		$pedido->setdtentrega($this->getdtentrega());
		$pedido->setstatus("P");
		$pedido->setautomatico("S");
		$arr_pedido = object_array($pedido);
		foreach($arr_pedido as $pedido_tmp){
			$pedido_tmp->flag_itpedido(TRUE);
			$pedido_tmp->searchbyobject();
			if(count($pedido_tmp->itpedido) < 100){
				$novo_pedido = false;
				$pedido = $pedido_tmp;
				break;
			}
		}

		if($novo_pedido){
			// Carrega os parametos de nota fiscal (para transferencia)
			$paramnotafiscal = objectbytable("paramnotafiscal", array($codestabelec_orig, "TS"), $this->con);
			if(!$paramnotafiscal->exists()){
				$_SESSION["ERROR"] = "Par&acirc;metros de nota fiscal n&atilde;o informados para nota fiscal de transfer&ecirc;ncia (sa&iacute;da) para o estabelecimento <b>{$codestabelec_orig}</b>.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal')\">Clique aqui</a> para abrir os par&acirc;metros de nota fiscal.";
				return FALSE;
			}elseif(strlen($paramnotafiscal->getnatoperacaopjin()) == 0 || strlen($paramnotafiscal->getcodcondpagtoauto()) == 0 || strlen($paramnotafiscal->getcodespecieauto()) == 0){
				$_SESSION["ERROR"] = "Valores padr&otilde;es dos par&acirc;metros de nota fiscal n&atilde;o foram informados para nota fiscal de transfer&ecirc;ncia (sa&iacute;da) para o estabelecimento <b>{$codestabelec_orig}</b>.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal','codestabelec=".$this->getcodestabelectransf()."&operacao=TS')\">Clique aqui</a> para abrir os par&acirc;metros de nota fiscal.";
				return FALSE;
			}

			$pedido->setdtemissao(date("Y-m-d"));
			$pedido->setnatoperacao($paramnotafiscal->getnatoperacaopjin());
			$pedido->setcodcondpagto($paramnotafiscal->getcodcondpagtoauto());
			$pedido->setcodespecie($paramnotafiscal->getcodespecieauto());
			$pedido->flag_itpedido(TRUE);
		}

		// Cria o item na transferencia
		$itpedido = objectbytable("itpedido", NULL, $this->con);
		$arr_column_itpedido = $itpedido->getcolumnnames();
		$arr_column_itnotafiscal = $this->getcolumnnames();
		foreach($arr_column_itpedido as $column){
			if(in_array($column, $arr_column_itnotafiscal)){
				call_user_func(array($itpedido, "set".$column), call_user_func(array($this, "get".$column)));
			}
		}

		// Verifica se eh operacao interestadual
		if(substr($this->getnatoperacao(), 0, 1) === "6"){
			$tributacaoproduto = new TributacaoProduto($this->con);
			$tributacaoproduto->setcodestabelec($pedido->getcodestabelec());
			$tributacaoproduto->setnatoperacao($pedido->getnatoperacao());
			$tributacaoproduto->setoperacaonota(objectbytable("operacaonota", "TS", $this->con));
			$tributacaoproduto->setparceiro(objectbytable("estabelecimento", $codestabelec_dest, $this->con));
			$tributacaoproduto->setproduto(objectbytable("produto", $this->getcodproduto(), $this->con));
			$tributacaoproduto->buscar_dados();

			$itpedido->settptribicms($tributacaoproduto->gettptribicms());
			$itpedido->setaliqicms($tributacaoproduto->getaliqicms());
			$itpedido->setredicms($tributacaoproduto->getredicms());
			$itpedido->setaliqiva($tributacaoproduto->getaliqiva());
			$itpedido->setvalorpauta($tributacaoproduto->getvalorpauta());
			$itpedido->setnatoperacao($tributacaoproduto->getnatoperacao()->getnatoperacao());
		}

		// Carrega os dados do produto no estabelecimento
		$produtoestab = objectbytable("produtoestab", array($pedido->getcodestabelec(), $itpedido->getcodproduto()), $this->con);
		$custotab = $produtoestab->getcustotab();

		// Se o custo do produto no estabelecimento for zero, deve buscar no cadastro de produto
		if($custotab == 0){
			$produto = objectbytable("produto", $itpedido->getcodproduto(), $this->con);
			$custotab = $produto->getcustotab();
		}

		// Verifica se deve utilizar a natureza de operacao para ST
		if($itpedido->gettptribicms() === "F"){
			$natoperacao = objectbytable("natoperacao", $pedido->getnatoperacao(), $this->con);
			if(strlen($natoperacao->getnatoperacaosubst()) > 0){
				$natoperacao = $natoperacao->getnatoperacaosubst();
			}else{
				$natoperacao = $natoperacao->getnatoperacao();
			}
		}else{
			$natoperacao = $pedido->getnatoperacao();
		}

		$itpedido->setiditpedido(NULL);
		$itpedido->setcodestabelectransf(NULL);
		$itpedido->setiditnotafiscalvd($this->getiditnotafiscal());
		$itpedido->setcodestabelec($pedido->getcodestabelec());
		$itpedido->setnumpedido($pedido->getnumpedido());
		$itpedido->setnatoperacao($natoperacao);
		$itpedido->setqtdeatendida(0);
		$itpedido->setpreco($custotab);
		$itpedido->setpercdescto(0);
		$itpedido->setvaldescto(0);
		$itpedido->setpercacresc(0);
		$itpedido->setvalacresc(0);
		$itpedido->setpercfrete(0);
		$itpedido->setvalfrete(0);
		$itpedido->setpercseguro(0);
		$itpedido->setvalseguro(0);

		// Calcula os totais do item
		$itemcalculo = new ItemCalculo($this->con);
		$itemcalculo->setestabelecimento(objectbytable("estabelecimento", $itpedido->getcodestabelec(), $this->con));
		$itemcalculo->setnatoperacao(objectbytable("natoperacao", $itpedido->getnatoperacao(), $this->con));
		$itemcalculo->setoperacaonota(objectbytable("operacaonota", $pedido->getoperacao(), $this->con));
		$itemcalculo->setparceiro(objectbytable("estabelecimento", $pedido->getcodparceiro(), $this->con));
		$itemcalculo->setitem($itpedido);
		$itemcalculo->calcular();

		// Inclui o item no pedido de transferencia
		$pedido->itpedido[] = $itpedido;

		// Repreenche o sequencial do itens do pedido de transferencia
		$seqitem = 1;
		foreach($pedido->itpedido as $itpedido){
			$itpedido->setseqitem($seqitem++);
		}

		// Recalcula os totais do pedido de transferencia
		$pedido->calcular_totais();

		// Grava oo pedido de transferencia
		return $pedido->save();
	}

	function delete(){
		$this->connect();
		$this->con->start_transaction();
		// Verifica se existe uma transferencia automatica da venda
		if(in_array($this->getoperacao(), array("VD", "EX"))){
			$itpedido = objectbytable("itpedido", $this->getiditpedido(), $this->con);
			if(strlen($itpedido->getcodestabelectransf()) > 0 && $itpedido->getcodestabelectransf() != $itpedido->getcodestabelec()){
				$itpedido = objectbytable("itpedido", NULL, $this->con);
				$itpedido->setiditnotafiscalvd($this->getiditnotafiscal());
				$arr_itpedido = object_array($itpedido);
				foreach($arr_itpedido as $itpedido){
					if($itpedido->getstatus() == "P"){
						$pedido = objectbytable("pedido", NULL, $this->con);
						$pedido->flag_itpedido(TRUE);
						$pedido->setcodestabelec($itpedido->getcodestabelec());
						$pedido->setnumpedido($itpedido->getnumpedido());
						if(!$itpedido->delete()){
							$this->con->rollback();
							return FALSE;
						}
						$pedido->searchbyobject();
						if(sizeof($pedido->itpedido) == 0){
							if(!$pedido->delete()){
								$this->con->rollback();
								return FALSE;
							}
						}else{
							$pedido->calcular_totais();
							if(!$pedido->save()){
								$this->con->rollback();
								return FALSE;
							}
						}
					}
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
		if(!is_object($this->notafiscal)){
			$this->notafiscal = objectbytable("notafiscal", $this->getidnotafiscal(), $this->con);
		}
		if(!is_object($this->estabelecimento)){
			$this->estabelecimento = objectbytable("estabelecimento", $this->notafiscal->getcodestabelec(), $this->con);
		}
		if(!is_object($this->operacaonota)){
			$this->operacaonota = objectbytable("operacaonota", $this->notafiscal->getoperacao(), $this->con);
		}

		if(!is_object($this->parceiro)){
			switch($this->operacaonota->getparceiro()){
				case "C":
					$this->parceiro = objectbytable("cliente", $this->notafiscal->getcodparceiro(), $this->con);
					break;
				case "E":
					$this->parceiro = objectbytable("estabelecimento", $this->notafiscal->getcodparceiro(), $this->con);
					break;
				case "F":
					$this->parceiro = objectbytable("fornecedor", $this->notafiscal->getcodparceiro(), $this->con);
					break;
			}
		}

		// Verifica tipo de tributacao do produto
		if($this->gettptribicms() == "R" && $this->getredicms() == 0){
			$this->settptribicms("T");
		}
		if($this->getquantidade() == 0 && $this->getoperacao() != "AE"){
			//unset($this);
			return true;
		}

		//$this->calcular_totais();
		// Verifica o CST de ICMS do produto
		$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
		$produto = objectbytable("produto", $this->getcodproduto(), $this->con);
		if(in_array($this->notafiscal->getoperacao(), array("VD", "TS", "DC", "PR", "EX","RC"))){
			if($natoperacao->getusartributacaoncm() == "S"){
				$ncm = objectbytable("ncm", $produto->getidncm(), $this->con);
				$classfiscal = objectbytable("classfiscal", $ncm->getcodcfnfs(), $this->con);
			}else{
				$classfiscal = objectbytable("classfiscal", $produto->getcodcfnfs(), $this->con);
			}
		}else{
			$classfiscal = objectbytable("classfiscal", $produto->getcodcfnfe(), $this->con);
		}
		if(strlen($this->getcsticms()) != 3){
			$this->setcsticms(csticms($this->gettptribicms(), $this->getaliqicms(), $this->getredicms(), $this->getaliqiva(), $this->getvalorpauta(), $classfiscal, $natoperacao, $this->gettotalicmssubst()));
		}

		if(!in_array($this->gettptribicms(), array("I", "F")) || ($this->gettptribicms() === "I" && $natoperacao->getalteracfisento() === "S") || ($this->gettptribicms() === "F" && $natoperacao->getalteracficmssubst() === "S")){
			if($natoperacao->getgeracsticms090() === "S"){
				$this->setcsticms("090");
			}
			if(strlen($natoperacao->getcodcf()) > 0){
				$classfiscal = objectbytable("classfiscal", $natoperacao->getcodcf(), $this->con);
				$this->setcsticms($classfiscal->getcodcst());
			}
		}

		// CSOSN
		// 101 - Tributado pelo simples nacional com permissão de credito
		// 102 - Tributado pelo simples nacional sem permissão de credito
		// 103 - Insensao pelo simples nacional com faixa de receita bruta
		// 201 - Tributado pelo simples nacional com permissão de credito e com cobrança do icms por ST
		// 202 - Tributado pelo simples nacional sem permissão de credito e com cobrança por ST
		// 203 - Insensao pelo simples nacional com faixa de receita bruta e com cobrança por ST
		// 300 - Imune
		// 400 - Nao tributado pelo simples nacional
		// 500 - Icms cobrado anteriormente por substituicao tributaria (Substituido ou por antecipacao)
		// 900 - Devolucao fornecedor

		if($this->estabelecimento->getregimetributario() == "1" && $classfiscal->getforcarcst() == "S"){
			$this->setcsticms($classfiscal->getcsosn());
		}elseif((!in_array($this->notafiscal->getoperacao(), array("CP", "DF", "RF"))) && $this->estabelecimento->getregimetributario() == "1"){
			if($this->gettptribicms() != "F" && $this->gettptribicms() != "I"){
				$cosn = $classfiscal->getcsosn();
				$this->setcsticms($classfiscal->getcsosn());
				$cosn = $this->getcsticms();
				if(in_array($this->notafiscal->getoperacao(), array("VD", "DC", "RC","EC")) && in_array($this->getcsticms(), array("101", "201", "202")) && $this->parceiro->getcontribuinteicms() == "N"){
					$this->setcsticms("102");
				}
			}else{
				$this->setcsticms($classfiscal->getcsosn());
				if(in_array($this->notafiscal->getoperacao(), array("VD", "DC", "RC","EC")) && $this->getcsticms() == "201" && $this->parceiro->getcontribuinteicms() == "N"){
					$this->setcsticms("202");
				}
				if($this->gettotalicmssubst() > 0){
					$this->setcsticms($classfiscal->getcsosn());
				}else{
					if(($this->notafiscal->getoperacao() == "EX" || substr($this->notafiscal->getnatoperacao(), 0, 1) == "7") || $this->gettptribicms() == "I"){
						$this->setcsticms("300");
					}else{
						if($this->operacaonota->getparceiro() != "C" || $this->parceiro->getcontribuinteicms() == "N"){
							$this->setcsticms("102");
						}else{
							$this->setcsticms("500");
						}
					}
				}
				if(!in_array($this->notafiscal->getoperacao(), array("TS","TE","PD"))){
					if($this->parceiro->getcontribuinteicms() == "N"){
						$this->setcsticms("102");
					}
				}
			}
		}elseif(in_array($this->notafiscal->getoperacao(), array("DF", "RF")) && $this->estabelecimento->getregimetributario() == "1"){
			if($this->parceiro->getcontribuinteicms() == "N"){
				$this->setcsticms("102");
			}else{
				$this->setcsticms("900");
			}
		}

		// Verifica se pode sair produto com estoque zerado
		$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
		if($this->operacaonota->gettipo() == "S" && $natoperacao->getgeraestoque() == "S"){
			$param_estoquezero = param("ESTOQUE", "ESTOQUEZERO", $this->con);
			if(strstr($param_estoquezero, ";")){
				$achou = false;
				$valor_padrao = "";
				$arr_param_estoquezero = explode(";", $param_estoquezero);
				foreach($arr_param_estoquezero as $estoquezero_estabelec){
					if(strstr($estoquezero_estabelec, "-")){
						$estoque = explode("-", $estoquezero_estabelec);
						if($estoque[1] == $this->notafiscal->getcodestabelec()){
							$param_estoquezero = $estoque[0];
							$achou = true;
						}
					}else{
						$valor_padrao = $estoquezero_estabelec;
					}
				}
				if(!$achou){
					$param_estoquezero = $valor_padrao;
				}
			}

			if(in_array($param_estoquezero, array("2", "4")) && !$this->exists()){
				$quantidade_total = $this->getquantidade() * $this->getqtdeunidade();
				$codestabelecestoque = (strlen($natoperacao->getcodestabelecestoque()) > 0 ? $natoperacao->getcodestabelecestoque() : $this->notafiscal->getcodestabelec());

				$codcomposicao = "";
				$composicao = objectbytable("composicao", NULL, $this->con);
				$composicao->setcodproduto($this->getcodproduto());
				$composicao->searchbyobject();
				if($composicao->exists()){
					$codcomposicao = $composicao->getcodcomposicao();
				}

				if($composicao->gettipo() != "P" && strlen($codcomposicao) > 0){
					$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
					$itcomposicao->setcodcomposicao($composicao->getcodcomposicao());
					$arr_itcomposicao = object_array($itcomposicao);

					foreach($arr_itcomposicao as $itcomposicao){
						$produtoestab = objectbytable("produtoestab", array($codestabelecestoque, $itcomposicao->getcodproduto()), $this->con);
						$estoque_atual = $produtoestab->getsldatual();
						if($estoque_atual - $quantidade_total < 0 && $this->flag_verestoque){
							$produto = objectbytable("produto", $itcomposicao->getcodproduto(), $this->con);
							$_SESSION["ERROR"] = "Estoque atual indispon&iacute;vel para sa&iacute;da do produto:<br>".$produtoestab->getcodproduto()." (".$produto->getdescricaofiscal().").<br><br>Saldo do estoque atual (unidade): ".number_format($estoque_atual, 4, ",", ".")."<br>Quantidade na nota fiscal (unidade): ".number_format($quantidade_total, 4, ",", ".");
							return FALSE;
						}
					}
				}else{
					$produtoestab = objectbytable("produtoestab", array($codestabelecestoque, $this->getcodproduto()), $this->con);
					$estoque_atual = $produtoestab->getsldatual();
					if($estoque_atual - $quantidade_total < 0 && $this->flag_verestoque){
						$produto = objectbytable("produto", $this->getcodproduto(), $this->con);
						$_SESSION["ERROR"] = "Estoque atual indispon&iacute;vel para sa&iacute;da do produto:<br>".$this->getcodproduto()." (".$produto->getdescricaofiscal().").<br><br>Saldo do estoque atual (unidade): ".number_format($estoque_atual, 4, ",", ".")."<br>Quantidade na nota fiscal (unidade): ".number_format($quantidade_total, 4, ",", ".");
						return FALSE;
					}
				}

			}
		}

		// Se o custo do produto no estabelecimento for zero, deve buscar no cadastro de produto
		$produtoestab = objectbytable("produtoestab", array($this->getcodestabelec(), $this->getcodproduto()), $this->con);
		$custotab = $produtoestab->getcustotab();
		if($custotab == 0){
			$produto = objectbytable("produto", $this->getcodproduto(), $this->con);
			$custotab = $produto->getcustotab();
		}
		$custorep = $produtoestab->getcustorep();
		if($custorep == 0){
			$produto = objectbytable("produto", $this->getcodproduto(), $this->con);
			$custorep = $produto->getcustorep();
		}
		$this->setcustotab($custotab);
		$this->setcustorep($custorep);
		$this->setcustosemimp($produtoestab->getcustosemimp());

		// Verifica se e pai de composicao
		if(sizeof($this->arr_itnotafiscal) > 0){
			$this->setcomposicao("P");
		}

		// Verifica se e uma atualizacao
		$update = $this->exists();

		// Inicia gravacao do item
		$this->con->start_transaction();
		if(!parent::save($object)){
			$this->con->rollback();
			return FALSE;
		}

		$seqitem = $this->getseqitem() + 1;
		// Grava os itens filhos da composicao
		foreach($this->arr_itnotafiscal as $itnotafiscal){
			$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);

			$itnotafiscal->setnotafiscal($this->notafiscal);
			$itnotafiscal->setoperacaonota($this->operacaonota);
			$itnotafiscal->setestabelecimento($this->estabelecimento);
			$itnotafiscal->setiditnotafiscalpai($this->getiditnotafiscal());
			$itnotafiscal->setcodestabelec($this->getcodestabelec());
			$itnotafiscal->setdtentrega($this->getdtentrega());
			$itnotafiscal->setoperacao($this->getoperacao());
			$itnotafiscal->setidnotafiscal($this->getidnotafiscal());
			$itnotafiscal->setnumpedido($this->getnumpedido());
			$itnotafiscal->setcomposicao("F");
			$itnotafiscal->setseqitem($seqitem++);

			if($itnotafiscal->gettptribicms() == "F" && substr($this->getnatoperacao(), 0, 5) != "5.929" && strlen($natoperacao->getnatoperacaosubst()) > 0){
				$itnotafiscal->setnatoperacao($natoperacao->getnatoperacaosubst());
			}

			if(!$itnotafiscal->save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		// Verifica o tipo de operacao
		switch($this->operacaonota->getoperacao()){
			// Tranferencia (saida)
			case "TS":
				// Nao precisa criar o item filho de composicao no pedido, pois na nota de entrada ja vai explodir a composicao
				if(!$update && $this->getcomposicao() != "F"){
					// Busca o pedido filho da nota fiscal
					$pedido = objectbytable("pedido", NULL, $this->con);
					$pedido->setidnotafiscalpai($this->getidnotafiscal());
					$pedido->searchbyobject();
					if(!$pedido->exists()){
						$this->con->rollback();
						$_SESSION["ERROR"] = "Pedido de tranfer&ecirc;ncia (entrada) n&atilde;o foi encontrado para a nota fiscal (".$notafiscal->getnumnotafis()."-".trim($notafiscal->getserie()).")";
						return FALSE;
					}

					// Apaga o item do pedido para criar de novo
					$criar_itpedido = TRUE;
					$itpedido = objectbytable("itpedido", NULL, $this->con);
					$itpedido->setcodestabelec($pedido->getcodestabelec());
					$itpedido->setnumpedido($pedido->getnumpedido());
					$itpedido->setcodproduto($this->getcodproduto());
					$itpedido->setseqitem($this->getseqitem());
					$arr_itpedido = object_array($itpedido);
					if(sizeof($arr_itpedido) > 0){
						$itpedido = array_shift($arr_itpedido);
						$itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
						$itnotafiscal->setiditpedido($itpedido->getiditpedido());
						$arr_itnotafiscal = object_array($itnotafiscal);
						if(sizeof($arr_itnotafiscal) == 0){
							if(!$itpedido->delete()){
								$this->con->rollback();
								return FALSE;
							}
						}else{
							$criar_itpedido = FALSE;
						}
					}
					// Cria o item do pedido de novo
					if($criar_itpedido){
						// Carrega o item do pedido da nota fiscal
						$itpedido = objectbytable("itpedido", $this->getiditpedido(), $this->con);

						// Carrega a natureza de operacao da item de nota fiscal (TS)
						$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
						if(strlen($natoperacao->getnatoperacaocp()) == 0){
							$_SESSION["ERROR"] = "Natureza contra-partida para a natureza de opera&ccedil;&atilde;o <b>".$natoperacao->getnatoperacao()."</b> n&atilde;o foi informada.<br><a onclick=\"openProgram('NatOper','natoperacao=".$natoperacao->getnatoperacao()."')\">Clique aqui</a> para abrir o cadastro de natureza de opera&ccedil;&atilde;o.";
							return FALSE;
						}
						$natoperacaocp = objectbytable("natoperacao", $natoperacao->getnatoperacaocp(), $this->con);
						if(strlen($natoperacaocp->getnatoperacaosubst()) == 0){
							$natoperacaocpsubst = $natoperacaocp;
						}else{
							$natoperacaocpsubst = objectbytable("natoperacao", $natoperacaocp->getnatoperacaosubst(), $this->con);
						}

						// Cria o item da tranferencia de entrada TE
						$campos = array("codproduto", "natoperacao", "quantidade", "preco", "percipi", "valipi", "aliqicms", "percdescto", "valdescto", "redicms", "codunidade", "qtdeunidade", "bonificado", "percacresc", "valacresc", "percfrete", "valfrete", "aliqiva", "tipoipi", "tptribicms", "valorpauta", "seqitem", "totaldesconto", "totalacrescimo", "totalfrete", "totalipi", "totalbaseicms", "totalicms", "totalbaseicmssubst", "totalicmssubst", "totalbruto", "totalliquido", "totalarecolher","aliqpis","aliqcofins","totalpis","totalcofins");
						$itpedido_te = objectbytable("itpedido", NULL, $this->con);
						$itpedido_te->setcodestabelec($pedido->getcodestabelec());
						$itpedido_te->setnumpedido($pedido->getnumpedido());
						$itpedido_te->setdtentrega(date("d/m/Y"));
						$itpedido_te->setiditnotafiscalvd($itpedido->getiditnotafiscalvd());
						foreach($campos as $campo){
							call_user_func(array($itpedido_te, "set".$campo), call_user_func(array($this, "get".$campo)));
						}
						$itpedido_te->setnatoperacao($itpedido_te->gettptribicms() == "F" ? $natoperacaocpsubst->getnatoperacao() : $natoperacaocp->getnatoperacao());
						$itpedido_te->setiditpedido(NULL);
						if(!$itpedido_te->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
				break;
			// Venda
			case "EX":
			case "VD":
				// Recalcula a venda media dos produtos
				$vendamedia = new VendaMedia($this->con);
				$vendamedia->setcodestabelec($this->estabelecimento->getcodestabelec());
				$vendamedia->setcodproduto($this->getcodproduto());
				if(!$vendamedia->atualizar()){
					$this->con->rollback();
					return FALSE;
				}

				// Verifica se deve criar uma transferencia automatica
				$itpedido_orig = objectbytable("itpedido", $this->getiditpedido(), $this->con);
				if(strlen($itpedido_orig->getcodestabelectransf()) > 0 && $itpedido_orig->getcodestabelectransf() != $itpedido_orig->getcodestabelec()){
					// Verifica se ja nao foi feito a transferecia anteriormente (evita a duplicacao de transferencia)
					$itpedido = objectbytable("itpedido", NULL, $this->con);
					$itpedido->setiditnotafiscalvd($this->getiditnotafiscal());
					$arr_itpedido = object_array($itpedido);
					if(sizeof($arr_itpedido) == 0){
						// Verifica se a mercadoria deve fazer um caminho especifico
						$param_notafiscal_caminhotransfauto = param("NOTAFISCAL", "CAMINHOTRANSFAUTO", $this->con);
						$arr_codestabelec_param = explode(",", $param_notafiscal_caminhotransfauto);
						if(strlen($arr_codestabelec_param[0]) > 0 && $arr_codestabelec_param[0] == $itpedido_orig->getcodestabelectransf() && $arr_codestabelec_param[1] != $itpedido_orig->getcodestabelec()){
							$arr_codestabelec_caminho = array(array($itpedido_orig->getcodestabelectransf(), $arr_codestabelec_param[1]), array($arr_codestabelec_param[1], $itpedido_orig->getcodestabelec()));
						}else{
							$arr_codestabelec_caminho = array(array($itpedido_orig->getcodestabelectransf(), $itpedido_orig->getcodestabelec()));
						}
						// Cria as transferencias
						foreach($arr_codestabelec_caminho as $arr_codestabelec){
							if(!$this->criar_transferencia($arr_codestabelec[0], $arr_codestabelec[1])){
								$this->con->rollback();
								return FALSE;
							}
						}
					}
				}
				break;
		}

		$param_notafiscal_atuestabprecomargem = param("NOTAFISCAL", "ATUESTABPRECOMARGEM", $this->con);
		$arr_atuestabprecomargem = explode(";", $param_notafiscal_atuestabprecomargem);
		if(in_array($this->getcodestabelec(), $arr_atuestabprecomargem) || $param_notafiscal_atuestabprecomargem == $this->getcodestabelec()){
			$produto = objectbytable("produto", $this->getcodproduto(), $this->con);
			$margem = new Margem($this->con);
			$margem->setestabelecimento($this->estabelecimento);
			$margem->setproduto($produto);

			$produtoestab = objectbytable("produtoestab", array($this->getcodestabelec(), $this->getcodproduto()), $this->con);
			$produtoestab->setprecovrj($margem->getprecosugestaovrj());
			$produtoestab->setorigempreco("Nota fiscal {$this->notafiscal->getnumnotafis()}-{$this->notafiscal->getserie()} ({$this->notafiscal->getoperacao()})");
			if(!$produtoestab->save()){
				$_SESSION["ERROR"] = "Erro ao gravar o pre&ccedil;o sugest&atilde;o no produto.";
				return false;
			}
		}

		$this->con->commit();
		return TRUE;
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getquantidade($format = FALSE){
		return ($format ? number_format($this->fields["quantidade"], 4, ",", "") : $this->fields["quantidade"]);
	}

	function getvalipi($format = FALSE){
		return ($format ? number_format($this->fields["valipi"], 4, ",", "") : $this->fields["valipi"]);
	}

	function getpercipi($format = FALSE){
		return ($format ? number_format($this->fields["percipi"], 4, ",", "") : $this->fields["percipi"]);
	}

	function getvaldescto($format = FALSE){
		return ($format ? number_format($this->fields["valdescto"], 6, ",", "") : $this->fields["valdescto"]);
	}

	function getpercdescto($format = FALSE){
		return ($format ? number_format($this->fields["percdescto"], 4, ",", "") : $this->fields["percdescto"]);
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->fields["aliqicms"], 4, ",", "") : $this->fields["aliqicms"]);
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->fields["preco"], 4, ",", "") : $this->fields["preco"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getreffornec(){
		return $this->fields["reffornec"];
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getredicms($format = FALSE){
		return ($format ? number_format($this->fields["redicms"], 4, ",", "") : $this->fields["redicms"]);
	}

	function getseqitem(){
		return $this->fields["seqitem"];
	}

	function getnumpedido(){
		return $this->fields["numpedido"];
	}

	function getcodunidade(){
		return $this->fields["codunidade"];
	}

	function getqtdeunidade($format = FALSE){
		return ($format ? number_format($this->fields["qtdeunidade"], 4, ",", "") : $this->fields["qtdeunidade"]);
	}

	function getbonificado(){
		return $this->fields["bonificado"];
	}

	function getpercacresc($format = FALSE){
		return ($format ? number_format($this->fields["percacresc"], 4, ",", "") : $this->fields["percacresc"]);
	}

	function getvalacresc($format = FALSE){
		return ($format ? number_format($this->fields["valacresc"], 6, ",", "") : $this->fields["valacresc"]);
	}

	function getpercfrete($format = FALSE){
		return ($format ? number_format($this->fields["percfrete"], 4, ",", "") : $this->fields["percfrete"]);
	}

	function getvalfrete($format = FALSE){
		return ($format ? number_format($this->fields["valfrete"], 6, ",", "") : $this->fields["valfrete"]);
	}

	function getaliqiva($format = FALSE){
		return ($format ? number_format($this->fields["aliqiva"], 4, ",", "") : $this->fields["aliqiva"]);
	}

	function gettipoipi(){
		return $this->fields["tipoipi"];
	}

	function gettptribicms(){
		return $this->fields["tptribicms"];
	}

	function getvalorpauta($format = FALSE){
		return ($format ? number_format($this->fields["valorpauta"], 4, ",", "") : $this->fields["valorpauta"]);
	}

	function gettotaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["totaldesconto"], 4, ",", "") : $this->fields["totaldesconto"]);
	}

	function gettotalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimo"], 4, ",", "") : $this->fields["totalacrescimo"]);
	}

	function gettotalfrete($format = FALSE){
		return ($format ? number_format($this->fields["totalfrete"], 4, ",", "") : $this->fields["totalfrete"]);
	}

	function gettotalipi($format = FALSE){
		return ($format ? number_format($this->fields["totalipi"], 4, ",", "") : $this->fields["totalipi"]);
	}

	function gettotalbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicms"], 4, ",", "") : $this->fields["totalbaseicms"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"], 4, ",", "") : $this->fields["totalicms"]);
	}

	function gettotalbaseicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmssubst"], 4, ",", "") : $this->fields["totalbaseicmssubst"]);
	}

	function gettotalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubst"], 4, ",", "") : $this->fields["totalicmssubst"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"], 4, ",", "") : $this->fields["totalbruto"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"], 4, ",", "") : $this->fields["totalliquido"]);
	}

	function gettotalarecolher($format = FALSE){
		return ($format ? number_format($this->fields["totalarecolher"], 4, ",", "") : $this->fields["totalarecolher"]);
	}

	function getcustodev($format = FALSE){
		return ($format ? number_format($this->fields["custodev"], 4, ",", "") : $this->fields["custodev"]);
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getcodmovimento(){
		return $this->fields["codmovimento"];
	}

	function getaliqpis($format = FALSE){
		return ($format ? number_format($this->fields["aliqpis"], 4, ",", "") : $this->fields["aliqpis"]);
	}

	function getaliqcofins($format = FALSE){
		return ($format ? number_format($this->fields["aliqcofins"], 4, ",", "") : $this->fields["aliqcofins"]);
	}

	function gettotalpis($format = FALSE){
		return ($format ? number_format($this->fields["totalpis"], 2, ",", "") : $this->fields["totalpis"]);
	}

	function gettotalcofins($format = FALSE){
		return ($format ? number_format($this->fields["totalcofins"], 2, ",", "") : $this->fields["totalcofins"]);
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdtentrega($format = FALSE){
		return ($format ? convert_date($this->fields["dtentrega"], "Y-m-d", "d/m/Y") : $this->fields["dtentrega"]);
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getcomposicao(){
		return $this->fields["composicao"];
	}

	function getcsticms(){
		return $this->fields["csticms"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function gettotalbaseii($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseii"], 4, ",", "") : $this->fields["totalbaseii"]);
	}

	function gettotalii($format = FALSE){
		return ($format ? number_format($this->fields["totalii"], 4, ",", "") : $this->fields["totalii"]);
	}

	function getvaliof($format = FALSE){
		return ($format ? number_format($this->fields["valiof"], 4, ",", "") : $this->fields["valiof"]);
	}

	function getdespaduaneira($format = FALSE){
		return ($format ? number_format($this->fields["despaduaneira"], 4, ",", "") : $this->fields["despaduaneira"]);
	}

	function getvalseguro($format = FALSE){
		return ($format ? number_format($this->fields["valseguro"], 6, ",", "") : $this->fields["valseguro"]);
	}

	function getnumadicao(){
		return $this->fields["numadicao"];
	}

	function getseqadicao(){
		return $this->fields["seqadicao"];
	}

	function getvaldesctodi($format = FALSE){
		return ($format ? number_format($this->fields["valdesctodi"], 4, ",", "") : $this->fields["valdesctodi"]);
	}

	function getpercseguro($format = FALSE){
		return ($format ? number_format($this->fields["percseguro"], 4, ",", "") : $this->fields["percseguro"]);
	}

	function gettotalseguro($format = FALSE){
		return ($format ? number_format($this->fields["totalseguro"], 4, ",", "") : $this->fields["totalseguro"]);
	}

	function getaliqii($format = FALSE){
		return ($format ? number_format($this->fields["aliqii"], 4, ",", "") : $this->fields["aliqii"]);
	}

	function getvalsiscomex($format = FALSE){
		return ($format ? number_format($this->fields["valsiscomex"], 4, ",", "") : $this->fields["valsiscomex"]);
	}

	function gettotalcif($format = FALSE){
		return ($format ? number_format($this->fields["totalcif"], 4, ",", "") : $this->fields["totalcif"]);
	}

	function gettotalbasepis($format = FALSE){
		return ($format ? number_format($this->fields["totalbasepis"], 4, ",", "") : $this->fields["totalbasepis"]);
	}

	function gettotalbasecofins($format = FALSE){
		return ($format ? number_format($this->fields["totalbasecofins"], 4, ",", "") : $this->fields["totalbasecofins"]);
	}

	function getredpis($format = FALSE){
		return ($format ? number_format($this->fields["redpis"], 4, ",", "") : $this->fields["redpis"]);
	}

	function getredcofins($format = FALSE){
		return ($format ? number_format($this->fields["redcofins"], 4, ",", "") : $this->fields["redcofins"]);
	}

	function gettotalbaseisento($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseisento"], 2, ",", "") : $this->fields["totalbaseisento"]);
	}

	function getiditnotafiscal(){
		return $this->fields["iditnotafiscal"];
	}

	function getiditpedido(){
		return $this->fields["iditpedido"];
	}

	function getiditnotafiscalpai(){
		return $this->fields["iditnotafiscalpai"];
	}

	function getnumerolote(){
		return $this->fields["numerolote"];
	}

	function getdtvalidade($format = FALSE){
		return ($format ? convert_date($this->fields["dtvalidade"], "Y-m-d", "d/m/Y") : $this->fields["dtvalidade"]);
	}

	function getprecopolitica($format = FALSE){
		return ($format ? number_format($this->fields["precopolitica"], 4, ",", "") : $this->fields["precopolitica"]);
	}

	function getcustorep($format = FALSE){
		return ($format ? number_format($this->fields["custorep"], 4, ",", "") : $this->fields["custorep"]);
	}

	function getcustosemimp($format = FALSE){
		return ($format ? number_format($this->fields["custosemimp"], 4, ",", "") : $this->fields["custosemimp"]);
	}

	function getcustotab($format = FALSE){
		return ($format ? number_format($this->fields["custotab"], 4, ",", "") : $this->fields["custotab"]);
	}

	function gettotalgnre($format = FALSE){
		return ($format ? number_format($this->fields["totalgnre"], 2, ",", "") : $this->fields["totalgnre"]);
	}

	function getpedcliente(){
		return $this->fields["pedcliente"];
	}

	function getseqitemcliente(){
		return $this->fields["seqitemcliente"];
	}

	function getguiagnre($format = FALSE){
		return ($format ? number_format($this->fields["guiagnre"], 2, ",", "") : $this->fields["guiagnre"]);
	}

	function gettotalnotafrete($format = FALSE){
		return ($format ? number_format($this->fields["totalnotafrete"], 2, ",", "") : $this->fields["totalnotafrete"]);
	}

	function getvalorafrmm($format = FALSE){
		return ($format ? number_format($this->fields["valorafrmm"], 4, ",", "") : $this->fields["valorafrmm"]);
	}

	function getbasecalcufdest($format = FALSE){
		return ($format ? number_format($this->fields["basecalcufdest"], 2, ",", "") : $this->fields["basecalcufdest"]);
	}

	function getvalorbcfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["valorbcfcpufdest"], 2, ",", "") : $this->fields["valorbcfcpufdest"]);
	}

	function getaliqfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["aliqfcpufdest"], 4, ",", "") : $this->fields["aliqfcpufdest"]);
	}

	function getvalorfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["valorfcpufdest"], 2, ",", "") : $this->fields["valorfcpufdest"]);
	}

	function getaliqicmsufdest($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsufdest"], 4, ",", "") : $this->fields["aliqicmsufdest"]);
	}

	function getvaloricmsufdest($format = FALSE){
		return ($format ? number_format($this->fields["valoricmsufdest"], 2, ",", "") : $this->fields["valoricmsufdest"]);
	}

	function getaliqicmsinter($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsinter"], 4, ",", "") : $this->fields["aliqicmsinter"]);
	}

	function getvaloricmsufremet($format = FALSE){
		return ($format ? number_format($this->fields["valoricmsufremet"], 2, ",", "") : $this->fields["valoricmsufremet"]);
	}

	function getaliqicminterpart($format = FALSE){
		return ($format ? number_format($this->fields["aliqicminterpart"], 4, ",", "") : $this->fields["aliqicminterpart"]);
	}

	function getbasestretido($format = FALSE){
		return ($format ? number_format($this->fields["basestretido"], 4, ",", "") : $this->fields["basestretido"]);
	}

	function getvalorstretido($format = FALSE){
		return ($format ? number_format($this->fields["valorstretido"], 4, ",", "") : $this->fields["valorstretido"]);
	}

	function getidcodigoservico(){
		return $this->fields["idcodigoservico"];
	}

	function getnattributacao(){
		return $this->fields["nattributacao"];
	}

	function getissretido(){
		return $this->fields["issretido"];
	}

	function getnatbccredito(){
		return $this->fields["natbccredito"];
	}

	function getaliquotainss(){
		return ($format ? number_format($this->fields["aliquotainss"], 2, ",", "") : $this->fields["aliquotainss"]);
	}

	function getvalorinss($format = FALSE){
		return ($format ? number_format($this->fields["valorinss"], 2, ",", "") : $this->fields["valorinss"]);
	}

	function getaliquotair($format = FALSE){
		return ($format ? number_format($this->fields["aliquotair"], 2, ",", "") : $this->fields["aliquotair"]);
	}

	function getvalorir($format = FALSE){
		return ($format ? number_format($this->fields["valorir"], 2, ",", "") : $this->fields["valorir"]);
	}

	function getaliquotacsll($format = FALSE){
		return ($format ? number_format($this->fields["aliquotacsll"], 2, ",", "") : $this->fields["aliquotacsll"]);
	}

	function getvalorcsll($format = FALSE){
		return ($format ? number_format($this->fields["valorcsll"], 2, ",", "") : $this->fields["valorcsll"]);
	}

	function getvalordesoneracao($format = FALSE){
		return ($format ? number_format($this->fields["valordesoneracao"], 4,",","") : $this->fields["valordesoneracao"]);
	}

	function getaliqicmsdesoneracao($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsdesoneracao"], 4,",","") : $this->fields["aliqicmsdesoneracao"]);
	}

	function getmotivodesoneracao(){
		return $this->fields["motivodesoneracao"];
	}

	function getbasecalculofcpst($format = FALSE){
		return ($format ? number_format($this->fields["basecalculofcpst"], 4,",","") : $this->fields["basecalculofcpst"]);
	}

	function getpercfcpst($format = FALSE){
		return ($format ? number_format($this->fields["percfcpst"], 4,",","") : $this->fields["percfcpst"]);
	}

	function getvalorfcpst($format = FALSE){
		return ($format ? number_format($this->fields["valorfcpst"], 4,",","") : $this->fields["valorfcpst"]);
	}

	function gettotalbaseicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmsnaoaproveitavel"], 2,",","") : $this->fields["totalbaseicmsnaoaproveitavel"]);
	}

	function gettotalicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsnaoaproveitavel"], 2,",","") : $this->fields["totalicmsnaoaproveitavel"]);
	}

	function getaliqicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["aliqicmsnaoaproveitavel"], 4,",","") : $this->fields["aliqicmsnaoaproveitavel"]);
	}

	function getredicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["redicmsnaoaproveitavel"], 4,",","") : $this->fields["redicmsnaoaproveitavel"]);
	}

	function getcsticmsnaoaproveitavel(){
		return $this->fields["csticmsnaoaproveitavel"];
	}

	/*OS 5240 -  campo que vai conter o desconto padrão da forma de pagamento*/
	function getprecovrj($format = FALSE){
		return ($format ? number_format($this->fields["precovrj"], 2, ",", "") : $this->fields["precovrj"]);
	}

	function getprecovrjof($format = FALSE){
		return ($format ? number_format($this->fields["precovrjof"], 2, ",", "") : $this->fields["precovrjof"]);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setquantidade($value){
		$this->fields["quantidade"] = value_numeric($value);
	}

	function setvalipi($value){
		$this->fields["valipi"] = value_numeric($value);
	}

	function setpercipi($value){
		$this->fields["percipi"] = value_numeric($value);
	}

	function setvaldescto($value){
		$this->fields["valdescto"] = value_numeric($value);
	}

	function setpercdescto($value){
		$this->fields["percdescto"] = value_numeric($value);
	}

	function setaliqicms($value){
		$this->fields["aliqicms"] = value_numeric($value);
	}

	function setpreco($value){
		$this->fields["preco"] = value_numeric($value);
		$this->setprecopolitica($this->getpreco());
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 80);
	}

	function setreffornec($value){
		$this->fields["reffornec"] = value_string($value, 20);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function setredicms($value){
		$this->fields["redicms"] = value_numeric($value);
	}

	function setseqitem($value){
		$this->fields["seqitem"] = value_numeric($value);
	}

	function setnumpedido($value){
		$this->fields["numpedido"] = value_numeric($value);
	}

	function setcodunidade($value){
		$this->fields["codunidade"] = value_numeric($value);
	}

	function setqtdeunidade($value){
		$this->fields["qtdeunidade"] = value_numeric($value);
	}

	function setbonificado($value){
		$this->fields["bonificado"] = value_string($value, 1);
	}

	function setpercacresc($value){
		$this->fields["percacresc"] = value_numeric($value);
	}

	function setvalacresc($value){
		$this->fields["valacresc"] = value_numeric($value);
	}

	function setpercfrete($value){
		$this->fields["percfrete"] = value_numeric($value);
	}

	function setvalfrete($value){
		$this->fields["valfrete"] = value_numeric($value);
	}

	function setaliqiva($value){
		$this->fields["aliqiva"] = value_numeric($value);
	}

	function settipoipi($value){
		$this->fields["tipoipi"] = value_string($value, 1);
	}

	function settptribicms($value){
		$this->fields["tptribicms"] = value_string($value, 1);
	}

	function setvalorpauta($value){
		$this->fields["valorpauta"] = value_numeric($value);
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

	function settotalarecolher($value){
		$this->fields["totalarecolher"] = value_numeric($value);
	}

	function setcustodev($value){
		$this->fields["custodev"] = value_numeric($value);
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setcodmovimento($value){
		$this->fields["codmovimento"] = value_numeric($value);
	}

	function setaliqpis($value){
		$this->fields["aliqpis"] = value_numeric($value);
	}

	function setaliqcofins($value){
		$this->fields["aliqcofins"] = value_numeric($value);
	}

	function settotalpis($value){
		$this->fields["totalpis"] = value_numeric($value);
	}

	function settotalcofins($value){
		$this->fields["totalcofins"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdtentrega($value){
		$this->fields["dtentrega"] = value_date($value);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value, 2);
	}

	function setcomposicao($value){
		$this->fields["composicao"] = value_string($value, 1);
	}

	function setcsticms($value){
		$this->fields["csticms"] = value_string($value, 3);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value, 500);
	}

	function settotalbaseii($value){
		$this->fields["totalbaseii"] = value_numeric($value);
	}

	function settotalii($value){
		$this->fields["totalii"] = value_numeric($value);
	}

	function setvaliof($value){
		$this->fields["valiof"] = value_numeric($value);
	}

	function setdespaduaneira($value){
		$this->fields["despaduaneira"] = value_numeric($value);
	}

	function setvalseguro($value){
		$this->fields["valseguro"] = value_numeric($value);
	}

	function setnumadicao($value){
		$this->fields["numadicao"] = value_numeric($value);
	}

	function setseqadicao($value){
		$this->fields["seqadicao"] = value_numeric($value);
	}

	function setvaldesctodi($value){
		$this->fields["valdesctodi"] = value_numeric($value);
	}

	function setpercseguro($value){
		$this->fields["percseguro"] = value_numeric($value);
	}

	function settotalseguro($value){
		$this->fields["totalseguro"] = value_numeric($value);
	}

	function setaliqii($value){
		$this->fields["aliqii"] = value_numeric($value);
	}

	function setvalsiscomex($value){
		$this->fields["valsiscomex"] = value_numeric($value);
	}

	function settotalcif($value){
		$this->fields["totalcif"] = value_numeric($value);
	}

	function settotalbasepis($value){
		$this->fields["totalbasepis"] = value_numeric($value);
	}

	function settotalbasecofins($value){
		$this->fields["totalbasecofins"] = value_numeric($value);
	}

	function setredpis($value){
		$this->fields["redpis"] = value_numeric($value);
	}

	function setredcofins($value){
		$this->fields["redcofins"] = value_numeric($value);
	}

	function settotalbaseisento($value){
		$this->fields["totalbaseisento"] = value_numeric($value);
	}

	function setiditnotafiscal($value){
		$this->fields["iditnotafiscal"] = value_numeric($value);
	}

	function setiditpedido($value){
		$this->fields["iditpedido"] = value_numeric($value);
	}

	function setiditnotafiscalpai($value){
		$this->fields["iditnotafiscalpai"] = value_numeric($value);
	}

	function setnumerolote($value){
		$this->fields["numerolote"] = value_string($value, 40);
	}

	function setdtvalidade($value){
		$this->fields["dtvalidade"] = value_date($value);
	}

	function setprecopolitica($value){
		$this->fields["precopolitica"] = value_numeric($value);
	}

	function setcustorep($value){
		$this->fields["custorep"] = value_numeric($value);
	}

	function setcustosemimp($value){
		$this->fields["custosemimp"] = value_numeric($value);
	}

	function setcustotab($value){
		$this->fields["custotab"] = value_numeric($value);
	}

	function settotalgnre($value){
		$this->fields["totalgnre"] = value_numeric($value);
	}

	function setpedcliente($value){
		$this->fields["pedcliente"] = value_string($value, 10);
	}

	function setseqitemcliente($value){
		$this->fields["seqitemcliente"] = value_numeric($value);
	}

	function setguiagnre($value){
		$this->fields["guiagnre"] = value_numeric($value);
	}

	function settotalnotafrete($value){
		$this->fields["totalnotafrete"] = value_numeric($value);
	}

	function setvalorafrmm($value){
		$this->fields["valorafrmm"] = value_numeric($value);
	}

	function setbasecalcufdest($value){
		$this->fields["basecalcufdest"] = value_numeric($value);
	}

	function setvalorbcfcpufdest($value){
		$this->fields["valorbcfcpufdest"] = value_numeric($value);
	}

	function setaliqfcpufdest($value){
		$this->fields["aliqfcpufdest"] = value_numeric($value);
	}

	function setvalorfcpufdest($value){
		$this->fields["valorfcpufdest"] = value_numeric($value);
	}

	function setaliqicmsufdest($value){
		$this->fields["aliqicmsufdest"] = value_numeric($value);
	}

	function setvaloricmsufdest($value){
		$this->fields["valoricmsufdest"] = value_numeric($value);
	}

	function setaliqicmsinter($value){
		$this->fields["aliqicmsinter"] = value_numeric($value);
	}

	function setvaloricmsufremet($value){
		$this->fields["valoricmsufremet"] = value_numeric($value);
	}

	function setaliqicminterpart($value){
		$this->fields["aliqicminterpart"] = value_numeric($value);
	}

	function setbasestretido($value){
		$this->fields["basestretido"] = value_numeric($value);
	}

	function setvalorstretido($value){
		$this->fields["valorstretido"] = value_numeric($value);
	}

	function setidcodigoservico($value){
		$this->fields["idcodigoservico"] = value_string($value, 20);
	}

	function setnattributacao($value){
		$this->fields["nattributacao"] = value_string($value, 1);
	}

	function setissretido($value){
		$this->fields["issretido"] = value_string($value, 1);
	}

	function setnatbccredito($value){
		$this->fields["natbccredito"] = value_string($value, 2);
	}

	function setaliquotainss($value){
		$this->fields["aliquotainss"] = value_numeric($value);
	}

	function setvalorinss($value){
		$this->fields["valorinss"] = value_numeric($value);
	}

	function setaliquotair($value){
		$this->fields["aliquotair"] = value_numeric($value);
	}

	function setvalorir($value){
		$this->fields["valorir"] = value_numeric($value);
	}

	function setaliquotacsll($value){
		$this->fields["aliquotacsll"] = value_numeric($value);
	}

	function setvalorcsll($value){
		$this->fields["valorcsll"] = value_numeric($value);
	}

	function setvalordesoneracao($value){
		$this->fields["valordesoneracao"] = value_numeric($value);
	}

	function setaliqicmsdesoneracao($value){
		$this->fields["aliqicmsdesoneracao"] = value_numeric($value);
	}

	function setmotivodesoneracao($value){
		$this->fields["motivodesoneracao"] = value_string($value, 1);
	}

	function setbasecalculofcpst($value){
		$this->fields["basecalculofcpst"] = value_numeric($value);
	}

	function setpercfcpst($value){
		$this->fields["percfcpst"] = value_numeric($value);
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

	function setaliqicmsnaoaproveitavel($value){
		$this->fields["aliqicmsnaoaproveitavel"] = value_numeric($value);
	}

	function setredicmsnaoaproveitavel($value){
		$this->fields["redicmsnaoaproveitavel"] = value_numeric($value);
	}

	function setcsticmsnaoaproveitavel($value){
		$this->fields["csticmsnaoaproveitavel"] = value_string($value, 3);
	}

	/*OS 5240 -  campo que vai conter o desconto padrão da forma de pagamento*/
	function setprecovrj($value){
		$this->fields["precovrj"] = value_numeric($value);
	}

	function setprecovrjof($value){
		$this->fields["precovrjof"] = value_numeric($value);
	}
}