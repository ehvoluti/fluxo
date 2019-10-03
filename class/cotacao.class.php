<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/email.class.php");
require_file("class/itemcalculo.class.php");
require_file("class/tributacaoproduto.class.php");

class Cotacao extends Cadastro{

	private $cotacaofornec = array();
	private $itcotacao = array();
	private $itcotacaoestab = array();
	private $flag_cotacaofornec = FALSE;
	private $flag_itcotacao = FALSE;
	private $natoperacao_padrao = "1.102";

	function __construct($codcotacao = NULL){
		parent::__construct();
		$this->table = "cotacao";
		$this->primarykey = array("codcotacao");
		$this->setcodcotacao($codcotacao);
		if(!is_null($this->getcodcotacao())){
			$this->searchbyobject();
		}
	}

	function flag_cotacaofornec($b){
		if(is_bool($b)){
			$this->flag_cotacaofornec = $b;
		}
	}

	function flag_itcotacao($b){
		if(is_bool($b)){
			$this->flag_itcotacao = $b;
		}
	}

	function encerrar(){
		setprogress(0, "Carregando dados da cotacao", TRUE);

		// Carrega as ligacoes dos produtos com a cotacao
		$itcotacao = objectbytable("itcotacao", NULL, $this->con);
		$itcotacao->setcodcotacao($this->getcodcotacao());
		$arr_itcotacao = object_array($itcotacao);

		// Carrega as ligacoes dos produtos por fornecedor com a cotacao
		$itcotacaofornec = objectbytable("itcotacaofornec", NULL, $this->con);
		$itcotacaofornec->setcodcotacao($this->getcodcotacao());
		$arr_itcotacaofornec = object_array($itcotacaofornec);

		$this->con->start_transaction();

		foreach($arr_itcotacao as $i => $itcotacao){
			setprogress(($i / sizeof($arr_itcotacao) * 100), "Selecionando fornecedores: ".($i + 1)." de ".sizeof($arr_itcotacao));
			unset($codfornec);
			unset($min_precodeciao);
			foreach($arr_itcotacaofornec as $itcotacaofornec){
				if($itcotacao->getcodproduto() == $itcotacaofornec->getcodproduto()){
					if(!isset($codfornec)){
						$codfornec = $itcotacaofornec->getcodfornec();
						$min_precodecisao = $itcotacaofornec->getprecodecisao();
					}elseif($itcotacaofornec->getprecodecisao() < $min_precodecisao){
						$codfornec = $itcotacaofornec->getcodfornec();
						$min_precodecisao = $itcotacaofornec->getprecodecisao();
					}elseif($itcotacaofornec->getprecodecisao() == $min_precodecisao){
						$fornecedor1 = objectbytable("fornecedor", $codfornec, $this->con);
						$res = $this->con->query("SELECT condpagto_prazomedio({$fornecedor1->getcodcondpagto()})");
						$prazomedio1 = $res->fetchColumn();

						$fornecedor2 = objectbytable("fornecedor", $itcotacaofornec->getcodfornec(), $this->con);
						$res = $this->con->query("SELECT condpagto_prazomedio({$fornecedor2->getcodcondpagto()})");
						$prazomedio2 = $res->fetchColumn();

						if($prazomedio2 > $prazomedio1){
							$codfornec = $itcotacaofornec->getcodfornec();
							$min_precodecisao = $itcotacaofornec->getprecodecisao();
						}
					}
				}
			}
			if(isset($codfornec)){
				$itcotacao->setcodfornec($codfornec);
				if(!$itcotacao->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}

		$this->setstatus("E");
		setprogress(0, "Gravando alteracoes da cotacao");
		if(!$this->save()){
			$this->con->rollback();
			return FALSE;
		}
		$this->con->commit();
		return TRUE;
	}

	function enviar_emails($codmodeloemail){
		$modeloemail = objectbytable("modeloemail", $codmodeloemail, $this->con);

		$cotacaofornec = objectbytable("cotacaofornec", NULL, $this->con);
		$cotacaofornec->setcodcotacao($this->getcodcotacao());
		$arr_cotacaofornec = object_array($cotacaofornec);

		foreach($arr_cotacaofornec AS $cotacaofornec){
			$corpo = $modeloemail->getcorpo();
			$pos_corpo = strrpos($corpo, "</body>");
			$corpo_ini = substr($corpo, 0, $pos_corpo);
			$corpo_fim = "</body>".substr($corpo, $pos_corpo);

			$corpo = $corpo_ini."<br>Data de cria&ccedil;&atilde;o: ".$this->getdatacriacao(TRUE);
			$corpo .= "<br>Data encerramento: ".$this->getdataencerramento(TRUE)." &agrave;s ".$this->gethoraencerramento();
			$corpo .= "<br>Observa&ccedil;&atilde;o: ".$this->getobservacao();

			$fornecedor = objectbytable("fornecedor", $cotacaofornec->getcodfornec(), $this->con);
			$corpo .= "<br>Nome: ".$fornecedor->getnome()."<br>Senha: ".$fornecedor->getsenhacotacao().$corpo_fim;

			if(strlen($fornecedor->getemail()) > 0 && filter_var($fornecedor->getemail(), FILTER_VALIDATE_EMAIL)){
				$email = new Email($this->con);
				$email->setcorpo($corpo);
				$email->setdestinatario($fornecedor->getemail());
				$email->sethost($modeloemail->gethost());
				$email->setporta($modeloemail->getporta());
				$email->settipoautenticacao($modeloemail->gettipoautenticacao());
				$email->setsenha($modeloemail->getsenha());
				$email->settitulo($this->getdescricao());
				$email->setusuario($modeloemail->getusuario());
				$email->enviar();
				/*
				unset($_SESSION["ERROR"]);
				if(!$email->enviar()){
					return false;
				}
				*/
			}
		}
		return true;
	}

	function gerar_pedidos(){
		$this->con->start_transaction();

		// Verifica se a cotacao ja foi encerrada
		if($this->getstatus() == "A"){
			if(!$this->encerrar()){
				$this->con->rollback();
				return FALSE;
			}
		}

		// Verifica se tem distribuicao
		if($this->getdistribuicao() === "N"){
			if(!$this->gerar_pedidos_estabelecimento($this->getcodestabelec())){
				$this->con->rollback();
				return FALSE;
			}
		}else{
			$arr_codestabelec = array();
			$itcotacaoestab = objectbytable("itcotacaoestab", NULL, $this->con);
			$itcotacaoestab->setcodcotacao($this->getcodcotacao());
			$arr_itcotacaoestab = object_array($itcotacaoestab);
			foreach($arr_itcotacaoestab as $itcotacaoestab){
				$arr_codestabelec[] = $itcotacaoestab->getcodestabelec();
			}
			$arr_codestabelec = array_unique($arr_codestabelec);
			foreach($arr_codestabelec as $codestabelec){
				if(!$this->gerar_pedidos_estabelecimento($codestabelec)){
					$this->con->rollback();
					return FALSE;
				}
			}
		}

		$this->con->commit();
		return TRUE;
	}

	function gerar_pedidos_estabelecimento($codestabelec){
		$this->con->start_transaction();

		// Carrega a operacao de nota fiscal
		$operacaonota = objectbytable("operacaonota", "CP", $this->con);

		// Carrega o estabelecimento
		$estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);

		// Carrega as ligacoes dos fornecedores com a cotacao
		$cotacaofornec = objectbytable("cotacaofornec", NULL, $this->con);
		$cotacaofornec->setcodcotacao($this->getcodcotacao());
		$arr_cotacaofornec = object_array($cotacaofornec);

		// Carrega as ligacoes dos produtos com a cotacao
		$itcotacao = objectbytable("itcotacao", NULL, $this->con);
		$itcotacao->setcodcotacao($this->getcodcotacao());
		$arr_itcotacao = object_array($itcotacao);

		// Verifica se exite distribuicao
		if($this->getdistribuicao() === "S"){
			$itcotacaoestab = objectbytable("itcotacaoestab", NULL, $this->con);
			$itcotacaoestab->setcodcotacao($this->getcodcotacao());
			$itcotacaoestab->setcodestabelec($codestabelec);
			$arr_itcotacaoestab = object_array($itcotacaoestab);
			foreach($arr_itcotacao as $i => $itcotacao){
				$achou = FALSE;
				foreach($arr_itcotacaoestab as $itcotacaoestab){
					if($itcotacao->getcodproduto() === $itcotacaoestab->getcodproduto()){
						if($itcotacaoestab->getquantidade() > 0){
							$itcotacao->setquantidade($itcotacaoestab->getquantidade());
							$achou = TRUE;
						}
						break;
					}
				}
				if(!$achou){
					unset($arr_itcotacao[$i]);
				}
			}
		}

		// Carrega os produtos para poder organizar por descricao
		$arr_codproduto = array();
		foreach($arr_itcotacao as $itcotacao){
			$arr_codproduto[] = $itcotacao->getcodproduto();
		}
		$arr_produto = object_array_key(objectbytable("produto", NULL, $this->con), $arr_codproduto);
		$arr_itcotacao_aux = array();
		foreach($arr_itcotacao as $itcotacao){
			$produto = $arr_produto[$itcotacao->getcodproduto()];
			$arr_itcotacao_aux[$produto->getdescricaofiscal()." - ".$produto->getcodproduto()] = $itcotacao;
		}
		ksort($arr_itcotacao_aux);
		$arr_itcotacao = $arr_itcotacao_aux;

		// Carrega as ligacoes dos produtos por fornecedor com a cotacao
		$itcotacaofornec = objectbytable("itcotacaofornec", NULL, $this->con);
		$itcotacaofornec->setcodcotacao($this->getcodcotacao());
		$arr_itcotacaofornec = object_array($itcotacaofornec);

		// Carrega os fornecedores das cotacoes
		$arr_codfornec = array();
		$arr_idfornecestab = array();
		foreach($arr_cotacaofornec as $cotacaofornec){
			$arr_codfornec[] = $cotacaofornec->getcodfornec();
			$arr_idfornecestab[] = array($codestabelec, $cotacaofornec->getcodfornec());
		}
		$arr_fornecedor = object_array_key(objectbytable("fornecedor", NULL, $this->con), $arr_codfornec);
		$arr_fornecestab = object_array_key(objectbytable("fornecestab", NULL, $this->con), $arr_idfornecestab);

		// Carrega os produtos da cotacao
		$arr_codproduto = array();
		foreach($arr_itcotacao as $itcotacao){
			$arr_codproduto[] = $itcotacao->getcodproduto();
		}
		$arr_produto = object_array_key(objectbytable("produto", NULL, $this->con), $arr_codproduto);

		// Carrega as naturezas de operacao
		$arr_natoperacao_id = array($this->natoperacao_padrao);
		foreach($arr_fornecedor as $fornecedor){
			if(sizeof($fornecedor->getnatoperacao()) > 0){
				$arr_natoperacao_id[] = $fornecedor->getnatoperacao();
			}
		}
		$arr_natoperacao = object_array_key(objectbytable("natoperacao", NULL, $con), $arr_natoperacao_id);

		// Carrega as naturezas de operacao interestaduais
		$arr_natoperacao_id = array();
		foreach($arr_natoperacao as $natoperacao){
			if(strlen($natoperacao->getnatoperacaointer()) > 0){
				$arr_natoperacao_id[] = $natoperacao->getnatoperacaointer();
			}
		}
		$arr_natoperacao = array_merge($arr_natoperacao, object_array_key(objectbytable("natoperacao", NULL, $con), $arr_natoperacao_id));

		// Cria a classe que busca os dados tributarios dos produtos
		$tributacaoproduto = new TributacaoProduto($this->con);
		$tributacaoproduto->setestabelecimento($estabelecimento);
		$tributacaoproduto->setoperacaonota($operacaonota);

		// Cria os pedidos de compra
		foreach($arr_cotacaofornec as $cotacaofornec){
			$fornecedor = $arr_fornecedor[$cotacaofornec->getcodfornec()];
			$fornecestab = $arr_fornecestab[$codestabelec.";".$cotacaofornec->getcodfornec()];
			if(!is_object($fornecestab)){
				$_SESSION["ERROR"] = "Fornecedor <b>{$cotacaofornec->getcodfornec()}</b> n&atilde;o encontrado para o estabelecimento $codestabelec.";
				$this->con->rollback();
				return FALSE;
			}
			$natoperacao = (sizeof($fornecedor->getnatoperacao()) > 0 ? $arr_natoperacao[$fornecedor->getnatoperacao()] : $arr_natoperacao[$this->natoperacao_padrao]);
			if($estabelecimento->getuf() != $fornecedor->getuf() && strlen($natoperacao->getnatoperacaointer()) > 0){
				$natoperacao = $arr_natoperacao[$natoperacao->getnatoperacaointer()];
			}

			$tributacaoproduto->setparceiro($fornecedor);

			$arr_itcotacao_fornecedor = array(); // Array com o object itcotacao separado por fornecedor
			foreach($arr_itcotacao as $i => $itcotacao){
				if($itcotacao->getcodfornec() == $cotacaofornec->getcodfornec()){
					$arr_itcotacao_fornecedor[] = $itcotacao;
					unset($arr_itcotacao[$i]);
				}
			}
			// Verifica se existe pelo menos um produto para criar o pedido
			if(count($arr_itcotacao_fornecedor) > 0){
				$pedido = objectbytable("pedido", NULL, $this->con);
				$pedido->setcodcotacao($this->getcodcotacao());
				$pedido->setcodestabelec($codestabelec);
				$pedido->setoperacao("CP");
				$pedido->setstatus("P");
				$pedido->setcodparceiro($cotacaofornec->getcodfornec());
				$pedido->setnatoperacao($natoperacao->getnatoperacao());
				$pedido->setdtemissao(date("d/m/Y"));
				$pedido->setdtentrega(date("d/m/Y", mktime(0, 0, 0, date("m"), (date("d") + $fornecestab->getdiasentrega()), date("Y"))));
				$pedido->setcodcondpagto($fornecedor->getcodcondpagto());
				$pedido->setcodespecie($this->getcodespecie());
				$pedido->setbonificacao("N");

				$pedido->flag_itpedido(TRUE);

				$tributacaoproduto->setnatoperacao($natoperacao);

				$seqitem = 1;
				foreach($arr_itcotacao_fornecedor as $itcotacao){
					foreach($arr_itcotacaofornec as $i => $itcotacaofornec){
						if($itcotacaofornec->getcodfornec() == $cotacaofornec->getcodfornec() && $itcotacaofornec->getcodproduto() == $itcotacao->getcodproduto()){
							// Cria a classe que calcula os totais do item
							$itemcalculo = new ItemCalculo($this->con);
							$itemcalculo->setestabelecimento($estabelecimento);
							$itemcalculo->setoperacaonota($operacaonota);
							$itemcalculo->setparceiro($fornecedor);

							$produto = $arr_produto[$itcotacao->getcodproduto()];

							$tributacaoproduto->setproduto($produto);
							$tributacaoproduto->buscar_dados();

							$itpedido = objectbytable("itpedido", NULL, $this->con);
							$itpedido->setcodproduto($produto->getcodproduto());
							$itpedido->setcodunidade($itcotacao->getcodunidade());
							$itpedido->setqtdeunidade($itcotacao->getqtdeunidade());
							$itpedido->setquantidade($itcotacao->getquantidade());
							$itpedido->setpreco($itcotacaofornec->getpreco() * $itcotacao->getqtdeunidade());
							$itpedido->settptribicms($tributacaoproduto->gettptribicms());
							$itpedido->setaliqicms($tributacaoproduto->getaliqicms());
							$itpedido->setredicms($tributacaoproduto->getredicms());
							if($fornecedor->getfabricante() == "S"){
								$itpedido->setaliqiva($tributacaoproduto->getaliqiva());
								$itpedido->setvalorpauta($tributacaoproduto->getvalorpauta());
							}else{
								$itpedido->setaliqiva(0);
								$itpedido->setvalorpauta(0);
							}
							$itpedido->setvalipi($tributacaoproduto->getvalipi());
							$itpedido->setpercipi($tributacaoproduto->getpercipi());
							$itpedido->setnatoperacao($tributacaoproduto->getnatoperacao()->getnatoperacao());
							$itpedido->setbonificado("N");
							$itpedido->setseqitem($seqitem++);

							$itemcalculo->setitem($itpedido);
							$itemcalculo->setnatoperacao($tributacaoproduto->getnatoperacao());
							$itemcalculo->calcular();

							$pedido->itpedido[] = $itpedido;

							unset($arr_itcotacaofornec[$i]);
						}
					}
				}
				$pedido->calcular_totais();
				if(!$pedido->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}
		$this->setstatus("G");
		if(!$this->save()){
			$this->con->rollback();
			return FALSE;
		}
		$this->con->commit();
		return TRUE;
	}

	function save($object = null){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save($object)){
			if($this->flag_cotacaofornec){
				if(sizeof($this->cotacaofornec) == 0){
					$_SESSION["ERROR"] = "Informe os fornecedores para cota&ccedil;&atilde;o.";
					$this->con->rollback();
					return FALSE;
				}
				$cotacaofornec = objectbytable("cotacaofornec", NULL, $this->con);
				$cotacaofornec->setcodcotacao($this->getcodcotacao());
				$arr_cotacaofornec = object_array($cotacaofornec);
				foreach($arr_cotacaofornec as $cotacaofornec_db){
					$found = false;
					foreach($this->cotacaofornec as $cotacaofornec_ob){
						if($cotacaofornec_db->getcodfornec() == $cotacaofornec_ob->getcodfornec()){
							$found = true;
							break;
						}
					}
					if(!$found){
						if(!$cotacaofornec_db->delete()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}

				$cotacaofornec = objectbytable("cotacaofornec", NULL, $this->con);
				$cotacaofornec->setcodcotacao($this->getcodcotacao());
				$arr_cotacaofornec = object_array($cotacaofornec);
				foreach($this->cotacaofornec as $cotacaofornec_ob){
					$found = false;
					foreach($arr_cotacaofornec as $cotacaofornec_db){
						if($cotacaofornec_db->getcodfornec() == $cotacaofornec_ob->getcodfornec()){
							$found = true;
							break;
						}
					}
					if(!$found){
						$cotacaofornec_ob->setcodcotacao($this->getcodcotacao());
						if(!$cotacaofornec_ob->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
			}
			if($this->flag_itcotacao){
				if(sizeof($this->itcotacao) == 0){
					$_SESSION["ERROR"] = "Informe os produto para cota&ccedil;&atilde;o.";
					$this->con->rollback();
					return FALSE;
				}
				$itcotacao = objectbytable("itcotacao", NULL, $this->con);
				$itcotacao->setcodcotacao($this->getcodcotacao());
				$arr_itcotacao = object_array($itcotacao);
				foreach($arr_itcotacao as $itcotacao_db){
					foreach($this->itcotacao as $itcotacao_ob){
						if($itcotacao_db->getcodproduto() == $itcotacao_ob->getcodproduto()){
							continue 2;
						}
					}
					if(!$itcotacao_db->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->itcotacao as $itcotacao){
					$itcotacao->setcodcotacao($this->getcodcotacao());
					if(!$itcotacao->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->itcotacaoestab as $itcotacaoestab){
					$itcotacaoestab->setcodcotacao($this->getcodcotacao());
					if(!$itcotacaoestab->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && !$fetchAll){
			if($this->flag_cotacaofornec){
				$cotacaofornec = objectbytable("cotacaofornec", NULL, $this->con);
				$cotacaofornec->setcodcotacao($this->getcodcotacao());
				$this->cotacaofornec = object_array($cotacaofornec);
			}
			if($this->flag_itcotacao){
				$itcotacao = objectbytable("itcotacao", NULL, $this->con);
				$itcotacao->setcodcotacao($this->getcodcotacao());
				$this->itcotacao = object_array($itcotacao);

				$itcotacaoestab = objectbytable("itcotacaoestab", NULL, $this->con);
				$itcotacaoestab->setcodcotacao($this->getcodcotacao());
				$this->itcotacaoestab = object_array($itcotacaoestab);
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		$temporary = new Temporary("cotacao_cotacaofornec", FALSE);
		$this->cotacaofornec = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$cotacaofornec = objectbytable("cotacaofornec", NULL, $this->con);
			$cotacaofornec->setcodcotacao($this->getcodcotacao());
			$cotacaofornec->setcodfornec($temporary->getvalue($i, "codfornec"));
			$this->cotacaofornec[] = $cotacaofornec;
		}

		$temporary = new Temporary("cotacao_itcotacao", FALSE);
		$this->itcotacao = array();
		$this->itcotacaoestab = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$itcotacao = objectbytable("itcotacao", NULL, $this->con);
			$itcotacao->setcodcotacao($this->getcodcotacao());
			$itcotacao->setcodproduto($temporary->getvalue($i, "codproduto"));
			$itcotacao->setcodunidade($temporary->getvalue($i, "codunidade"));
			$itcotacao->setqtdeunidade($temporary->getvalue($i, "qtdeunidade"));
			$itcotacao->setquantidade($temporary->getvalue($i, "quantidade"));
			$this->itcotacao[] = $itcotacao;

			$distribuicao = json_decode($temporary->getvalue($i, "distribuicao"), TRUE);
			if(is_array($distribuicao)){
				foreach($distribuicao as $codestabelec => $quantidade){
					$itcotacaoestab = objectbytable("itcotacaoestab", NULL, $this->con);
					$itcotacaoestab->setcodcotacao($this->getcodcotacao());
					$itcotacaoestab->setcodproduto($itcotacao->getcodproduto());
					$itcotacaoestab->setcodestabelec($codestabelec);
					$itcotacaoestab->setquantidade($quantidade);
					$this->itcotacaoestab[] = $itcotacaoestab;
				}
			}
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		$temporary = new Temporary("cotacao_cotacaofornec", TRUE);
		$temporary->setcolumns(array("codfornec"));
		foreach($this->cotacaofornec as $cotacaofornec){
			$temporary->append();
			$temporary->setvalue("last", "codfornec", $cotacaofornec->getcodfornec());
		}
		$temporary->save();

		$temporary = new Temporary("cotacao_itcotacao", TRUE);
		$temporary->setcolumns(array("codproduto", "codunidade", "qtdeunidade", "quantidade", "distribuicao"));
		foreach($this->itcotacao as $itcotacao){
			$distribuicao = array();
			foreach($this->itcotacaoestab as $itcotacaoestab){
				if($itcotacaoestab->getcodproduto() == $itcotacao->getcodproduto()){
					$distribuicao[$itcotacaoestab->getcodestabelec()] = $itcotacaoestab->getquantidade();
				}
			}
			$temporary->append();
			$temporary->setvalue("last", "codproduto", $itcotacao->getcodproduto());
			$temporary->setvalue("last", "codunidade", $itcotacao->getcodunidade());
			$temporary->setvalue("last", "qtdeunidade", $itcotacao->getqtdeunidade());
			$temporary->setvalue("last", "quantidade", $itcotacao->getquantidade());
			$temporary->setvalue("last", "distribuicao", json_encode($distribuicao));
		}
		$temporary->save();

		return $html;
	}

	function getcodcotacao(){
		return $this->fields["codcotacao"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getdatacriacao($format = FALSE){
		return ($format ? convert_date($this->fields["datacriacao"], "Y-m-d", "d/m/Y") : $this->fields["datacriacao"]);
	}

	function gethoracriacao(){
		return $this->fields["horacriacao"];
	}

	function getdataencerramento($format = FALSE){
		return ($format ? convert_date($this->fields["dataencerramento"], "Y-m-d", "d/m/Y") : $this->fields["dataencerramento"]);
	}

	function gethoraencerramento(){
		return $this->fields["horaencerramento"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getenviarrelacionado(){
		return $this->fields["enviarrelacionado"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getdistribuicao(){
		return $this->fields["distribuicao"];
	}

	function setcodcotacao($value){
		$this->fields["codcotacao"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 60);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setdatacriacao($value){
		$this->fields["datacriacao"] = value_date($value);
	}

	function sethoracriacao($value){
		$this->fields["horacriacao"] = value_string($value);
	}

	function setdataencerramento($value){
		$this->fields["dataencerramento"] = value_date($value);
	}

	function sethoraencerramento($value){
		$this->fields["horaencerramento"] = value_string($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setenviarrelacionado($value){
		$this->fields["enviarrelacionado"] = value_string($value, 1);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setdistribuicao($value){
		$this->fields["distribuicao"] = value_string($value, 1);
	}

}