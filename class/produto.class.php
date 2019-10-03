<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/temporary.class.php");
//require_file("class/encoding.class.php");

class Produto extends Cadastro{

	public $arr_produtocompl;
	public $arr_produtoean;
	public $arr_prodfornec;
	public $arr_produtoestab;
	protected $flag_produtocompl = FALSE;
	protected $flag_produtoean = FALSE;
	protected $flag_produtoestab = FALSE;
	protected $flag_prodfornec = FALSE;
	protected $alterar_similar = FALSE;
	protected $alterar_composicao = FALSE;

	function __construct($codproduto = NULL){
		parent::__construct();
		$this->table = "produto";
		$this->primarykey = array("codproduto");
		$this->newrelation("produto", "codproduto", "produtoean", "codproduto");
		$this->newrelation("produto", "codproduto", "prodfornec", "codproduto");
		$this->newrelation("produto", "codproduto", "produtoestab", "codproduto");
		$this->setcodproduto($codproduto);
		if($this->getcodproduto() != NULL){
			$this->searchbyobject();
		}
	}

	function flag_produtocompl($value){
		if(is_bool($value)){
			$this->flag_produtocompl = $value;
		}
	}

	function flag_prodfornec($value){
		if(is_bool($value)){
			$this->flag_prodfornec = $value;
		}
	}

	function flag_produtoean($value){
		if(is_bool($value)){
			$this->flag_produtoean = $value;
		}
	}

	function flag_produtoestab($value){
		if(is_bool($value)){
			$this->flag_produtoestab = $value;
		}
	}

	function alterar_composicao($value){
		if(is_bool($value)){
			$this->alterar_composicao = $value;
		}
	}

	function alterar_similar($value){
		if(is_bool($value)){
			$this->alterar_similar = $value;
		}
	}

	function formvariables(){
		$str = parent::formvariables().",";
		$str .= "codean:$(\"#codean\").val(),";
		$str .= "codfornec:$(\"#codfornec\").val(),";
		$str .= "reffornec:$(\"#reffornec\").val()";
		return $str;
	}

	function newprimarykey($min = NULL, $max = NULL, $key = NULL){
		$this->connect();

		$maxplubalanca = param("CADASTRO", "MAXPLUBALANCA", $this->con);
		if(strlen($maxplubalanca) === 0){
			$maxplubalanca = 5000;
		}

		if($this->getpesado() != "S"){
			$min = $maxplubalanca + 1;
			$max = 0;
		}else{
			$min = 0;
			$max = $maxplubalanca;
		}
		$query = "SELECT new_primarykey('produto','codproduto',".$min.",".$max.")";
		if($res = $this->con->query($query)){
			$primarykey = $res->fetchColumn();
			if($this->getpesado() == "S" && $primarykey == ($maxplubalanca + 1)){
				return NULL;
			}else{
				return $primarykey;
			}
		}else{
			return NULL;
		}
	}

	function delete(){
		$this->connect();
		$this->con->start_transaction();
		if(strlen($this->getcodproduto()) > 0){
			$produtoestab = objectbytable("produtoestab", NULL, $this->con);
			$produtoestab->setcodproduto($this->getcodproduto());
			$search = $produtoestab->searchbyobject();
			if($search !== FALSE){
				if(!is_array($search[0])){
					$search = array($search);
				}
				foreach($search as $key){
					$produtoestab = objectbytable("produtoestab", $key, $this->con);
					if($produtoestab->getsldatual() > 0){
						$estabelecimento = objectbytable("estabelecimento", $produtoestab->getcodestabelec(), $this->con);
						$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel exclu&iacute;r o produto, pois encontra-se com estoque positivo no estabelecimento ".$produtoestab->getcodestabelec()." (".$estabelecimento->getnome().").";
						$this->con->rollback();
						return FALSE;
					}elseif(!$produtoestab->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
		}
		if(parent::delete()){
			$this->con->commit();
			// Procura a foto do produto e apaga
			$foto = param("CADASTRO", "DIRFOTOPROD", $this->con).$this->getcodproduto().".jpg";
			if(file_exists($foto)){
				@unlink($foto);
			}
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function save($object = null){
		$this->connect();
		$this->con->start_transaction();

		// Verifica se a data de inclusao esta preenchida
		if(strlen($this->getdatainclusao()) == 0){
			$this->setdatainclusao(date("d/m/Y"));
		}

		// Carrega os produtos nos estabelecimentos para fazer a comparacao na hora de atualizar o mix
		if($this->flag_produtoestab && strlen($this->getcodproduto()) > 0){
			$produtoestab = objectbytable("produtoestab", NULL, $this->con);
			$produtoestab->setcodproduto($this->getcodproduto());
			$arr_produtoestab_old = object_array($produtoestab);
		}

		$object = objectbytable("produto", $this->getcodproduto(), $this->con, true);
		if(parent::save($object)){
			// Complemento de cadastro
			if($this->flag_produtocompl){
				$produtocompl = objectbytable("produtocompl", NULL, $this->con);
				$produtocompl->setcodproduto($this->getcodproduto());
				$arr_produtocompl = object_array($produtocompl);
				foreach($arr_produtocompl as $produtocompl){
					if(!$produtocompl->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->arr_produtocompl as $produtocompl){
					$produtocompl->setcodproduto($this->getcodproduto());
					if(!$produtocompl->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}

			// Ligacao do produto com EAN
			if($this->flag_produtoean){
				// Coloca o codigo de produto em todos os EANs
				foreach($this->arr_produtoean as $produtoean){
					$produtoean->setcodproduto($this->getcodproduto());
				}

				// Carrega os EANs atuais no banco de dados
				$produtoean = objectbytable("produtoean", NULL, $this->con);
				$produtoean->setcodproduto($this->getcodproduto());
				$arr_produtoean = object_array($produtoean);

				// Remove os EANs antigos
				foreach($arr_produtoean as $produtoean1){
					$achou = FALSE;
					foreach($this->arr_produtoean as $produtoean2){
						if(strcmp($produtoean1->getcodean(), $produtoean2->getcodean()) == 0){
							$achou = TRUE;
							break;
						}
					}
					if(!$achou){
						if(!$produtoean1->delete()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}

				// Grava os EANs novos
				foreach($this->arr_produtoean as $produtoean1){
					$achou = FALSE;
					foreach($arr_produtoean as $produtoean2){
						if(strcmp($produtoean1->getcodean(), $produtoean2->getcodean()) == 0){
							$achou = TRUE;
							break;
						}
					}
					if(!$achou){
						if(!$produtoean1->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}

				// Coloca/retira PLU no EAN do produto
				if(sizeof($this->arr_produtoean) > 1){
					$param_cadastro_manterplu = param("CADASTRO", "MANTEREANPLU", $this->con);
					foreach($this->arr_produtoean as $produtoean){
						if($produtoean->getcodean() == plutoean13($this->getcodproduto()) && $param_cadastro_manterplu != "S"){
							if(!$produtoean->delete()){
								$this->con->rollback();
								return FALSE;
							}
						}
					}
				}elseif(sizeof($this->arr_produtoean) == 0){
					if(param("CADASTRO", "CADPRODUTOEANAUTO", $this->con) == "S"){
						$produtoean = objectbytable("produtoean", NULL, $this->con);
						$produtoean->setcodproduto($this->getcodproduto());
						$produtoean->setcodean(plutoean13($this->getcodproduto()));
						if(!$produtoean->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
			}

			// Ligacao do produto com fornecedor
			if($this->flag_prodfornec){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($this->getcodproduto());
				$search = $prodfornec->searchbyobject();
				if($search !== FALSE){
					if(!is_array($search)){
						$search = array($search);
					}
					foreach($search as $keys){
						$prodfornec = objectbytable("prodfornec", $keys, $this->con);
						if(!$prodfornec->delete()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
				foreach($this->arr_prodfornec as $prodfornec){
					$prodfornec->setcodproduto($this->getcodproduto());
					if(!$prodfornec->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}

			// Mix por estabelecimento
			if($this->flag_produtoestab){

				$arr_coluna = array("custotab", "custorep", "precoatc", "precovrj", "precoatcof", "precovrjof", "diasvalidade");

				$produtoestab = objectbytable("produtoestab", NULL, $this->con);
				$produtoestab->setcodproduto($this->getcodproduto());
				$this->arr_produtoestab = object_array($produtoestab);
				foreach($this->arr_produtoestab as $produtoestab){
					$produtoestab->setdisponivel("N");
				}
				$temporary = new Temporary("produto_produtoestab", FALSE);
				for($i = 0; $i < $temporary->length(); $i++){
					if(strlen($temporary->getvalue($i, "disponivel")) > 0){
						foreach($this->arr_produtoestab as $produtoestab){
							if($produtoestab->getcodestabelec() == $temporary->getvalue($i, "codestabelec")){
								$produtoestab->setdisponivel($temporary->getvalue($i, "disponivel"));
								foreach($arr_produtoestab_old as $produtoestab_old){
									if($produtoestab->getcodestabelec() == $produtoestab_old->getcodestabelec()){
										break;
									}
								}
								foreach($arr_coluna as $coluna){
									if(call_user_func(array($produtoestab_old, "get".$coluna)) != $temporary->getvalue($i, $coluna)){
										call_user_func(array($produtoestab, "set".$coluna), $temporary->getvalue($i, $coluna));
										if($this->alterar_similar && strlen($this->getcodsimilar()) > 0){
											$produto = objectbytable("produto", NULL, $this->con);
											$produto->setcodsimilar($this->getcodsimilar());
											$arr_produto = object_array($produto);
											foreach($arr_produto as $produto){
												$produtoestab_sim = objectbytable("produtoestab", array($produtoestab->getcodestabelec(), $produto->getcodproduto()), $this->con);
												call_user_func(array($produtoestab_sim, "set".$coluna), $temporary->getvalue($i, $coluna));
												if(!$produtoestab_sim->save()){
													$this->con->rollback();
													return FALSE;
												}
											}
										}
									}
								}
								break;
							}
						}
					}
				}
				foreach($this->arr_produtoestab as $produtoestab){
					if(!$produtoestab->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}

			// Altera o preco dos produtos similares
			if($this->alterar_similar && strlen($this->getcodsimilar()) > 0){
				$produto = objectbytable("produto", NULL, $this->con);
				$produto->setcodsimilar($this->getcodsimilar());
				$arr_produto = object_array($produto);
				foreach($arr_produto as $produto){
					if($produto->getcodproduto() != $this->getcodproduto()){
						$produto->alterar_composicao($this->alterar_composicao);
						$produto->setprecoatc($this->getprecoatc());
						$produto->setprecovrj($this->getprecovrj());
						$produto->setprecoatcof($this->getprecoatcof());
						$produto->setprecovrjof($this->getprecovrjof());
						if(!$produto->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
			}
			// Altera o preco do produto pai da composicao
			if($this->alterar_composicao){
				$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
				$itcomposicao->setcodproduto($this->getcodproduto());
				$arr_itcomposicao = object_array($itcomposicao);
				foreach($arr_itcomposicao as $itcomposicao){
					$composicao = objectbytable("composicao", $itcomposicao->getcodcomposicao(), $this->con);
					if(!$composicao->atualizar_precos()){
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
			// Complemento de cadastro
			if($this->flag_produtocompl){
				$produtocompl = objectbytable("produtocompl", NULL, $this->con);
				$produtocompl->setcodproduto($this->getcodproduto());
				$this->arr_produtocompl = object_array($produtocompl);
			}
			// Lista de EANs
			if($this->flag_produtoean){
				$produtoean = objectbytable("produtoean", NULL, $this->con);
				$produtoean->setcodproduto($this->getcodproduto());
				$this->arr_produtoean = object_array($produtoean);
			}
			// Lista de fornecedores
			if($this->flag_prodfornec){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setorder("principal DESC, codfornec");
				$prodfornec->setcodproduto($this->getcodproduto());
				$this->arr_prodfornec = object_array($prodfornec);
			}
			// Mix por estabelecimento
			if($this->flag_produtoestab){
				$produtoestab = objectbytable("produtoestab", NULL, $this->con);
				$produtoestab->setcodproduto($this->getcodproduto());
				$this->arr_produtoestab = object_array($produtoestab);

				$res = $this->con->query("SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."' ");
				$arr_codestabelec = $res->fetchAll(PDO::FETCH_COLUMN, 0);
				$_verproduto = FALSE;

				foreach($this->arr_produtoestab AS $produtoestab){
					if(in_array($produtoestab->getcodestabelec(), $arr_codestabelec) && $produtoestab->getdisponivel() == "S"){
						$_verproduto = TRUE;
					}
				}

				if(!$_verproduto){
					die(messagebox("error", "", "Nenhum registro p&ocirc;de ser encontrado."));
				}
			}
		}
		return $return;
	}

	function searchbyobject($limit = NULL, $offset = NULL, $fetchAll = FALSE, $parcialString = FALSE){
		// Tratamento para carregar apenas os produtos disponiveis para o estabelecimento
		if($this->flag_produtoestab){
			$arr_codestabelec = array();
			$usuaestabel = objectbytable("usuaestabel", NULL, $this->con);
			$usuaestabel->setlogin($_SESSION["WUser"]);
			$arr_usuaestabel = object_array($usuaestabel);
			foreach($arr_usuaestabel as $usuaestabel){
				$arr_codestabelec[] = $usuaestabel->getcodestabelec();
			}
			$this->setcodestabelec($arr_codestabelec);
		}
		return parent::searchbyobject($limit, $offset, $fetchAll, $parcialString);
	}

	function varheader(){
		$str = parent::varheader()." + ";
		$str .= "'&codean=' + $('#codean').val() + ";
		$str .= "'&codfornec=' + $('#codfornec').val() + ";
		$str .= "'&reffornec=' + $('#reffornec').val() + ";
		$str .= "'&alterar_similar=' + $('#alterar_similar').val() + ";
		$str .= "'&alterar_composicao=' + $('#alterar_composicao').val() ";
		return $str;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		$this->setcodean($_REQUEST["p_codean"]);
		$this->setcodfornec($_REQUEST["p_codfornec"]);
		$this->setreffornec($_REQUEST["p_reffornec"]);
		$this->alterar_composicao($_REQUEST["alterar_composicao"] == "S" ? TRUE : FALSE);
		$this->alterar_similar($_REQUEST["alterar_similar"] == "S" ? TRUE : FALSE);

		// Complemento de cadastro
		$this->arr_produtocompl = array();
		$temporary = new Temporary("produto_produtocompl", FALSE);
		for($i = 0; $i < $temporary->length(); $i++){
			$produtocompl = objectbytable("produtocompl", NULL, $this->con);
			$produtocompl->setcodproduto($this->getcodproduto());
			$produtocompl->setcodcomplcadastro($temporary->getvalue($i, "codcomplcadastro"));
			$produtocompl->setvalor($temporary->getvalue($i, "valor"));
			$this->arr_produtocompl[] = $produtocompl;
		}

		// Eans do produto
		$temporary = new Temporary("produto_produtoean", FALSE);
		$this->arr_produtoean = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$codean = $temporary->getvalue($i, "codean");
			$quantidade = $temporary->getvalue($i, "quantidade");
			$produtoean = objectbytable("produtoean", NULL, $this->con);
			$produtoean->setcodproduto($this->getcodproduto());
			$produtoean->setcodean($codean);
			$produtoean->setquantidade($quantidade);
			$this->arr_produtoean[] = $produtoean; // Objeto com todos os eans do produto
		}

		// Fornecedores do produto
		$temporary = new Temporary("produto_prodfornec", FALSE);
		$this->arr_prodfornec = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$codfornec = $temporary->getvalue($i, "codfornec");
			$reffornec = $temporary->getvalue($i, "reffornec");
			$principal = $temporary->getvalue($i, "principal");
			$prodfornec = objectbytable("prodfornec", NULL, $this->con);
			$prodfornec->setcodproduto($this->getcodproduto());
			$prodfornec->setcodfornec($codfornec);
			$prodfornec->setreffornec($reffornec);
			$prodfornec->setprincipal($principal);
			$this->arr_prodfornec[] = $prodfornec; // Objeto com todos os fornecedores do produto
		}

		// Marca que as alteracoes de preco tiveram origens manuais
		if(basename($_SERVER["PHP_SELF"], ".php") === "cadastro_action"){
			$this->setorigempreco("Ajuste manual");
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		// Departamento, grupo e subgrupo
		$subgrupo = objectbytable("subgrupo", $this->getcodsubgrupo(), $this->con);
		$html .= "values = new Array('".$subgrupo->getcodgrupo()."','".$this->getcodsubgrupo()."'); "; // Monta array com valor do grupo e subgrupo
		$html .= "filterchild('coddepto',values); "; // Filtra o grupo e subgrupo

		// Mercadologico
		if(strlen($this->getidmercadologico()) > 0){
			$values = array();
			$mercadologico = objectbytable("mercadologico", $this->getidmercadologico(), $this->con);
			while($mercadologico->exists()){
				$values[] = $mercadologico->getidmercadologico();
				$mercadologico = objectbytable("mercadologico", $mercadologico->getidmercadologicopai(), $this->con);
			}
			$values = array_reverse($values);
			$html .= setvalue("idmercadologico_1", array_shift($values), false);
			$html .= "filterchild('idmercadologico_1', [".implode(", ", $values)."]); ";
		}

		// Habilita/desabilita o campo vasilhame
		$html .= "$('#codvasilhame').attr('disabled',($('#vasilhame').val() != 'N')); ";

		// Complemento de cadastro
		$temporary = new Temporary("produto_produtocompl", TRUE);
		$temporary->setcolumns(array("codcomplcadastro", "valor"));
		foreach($this->arr_produtocompl as $produtocompl){
			$temporary->append();
			$temporary->setvalue("last", "codcomplcadastro", $produtocompl->getcodcomplcadastro());
			$temporary->setvalue("last", "valor", utf8_encode($produtocompl->getvalor()));
		}
		$temporary->save();
		$html .= "produtocompl_carregar(); ";

		// Lista EAN
		$temporary = new Temporary("produto_produtoean", TRUE);
		$temporary->setcolumns(array("codean", "quantidade"));
		foreach($this->arr_produtoean as $produtoean){
			$temporary->append();
			$temporary->setvalue("last", "codean", $produtoean->getcodean());
			$temporary->setvalue("last", "quantidade", $produtoean->getquantidade());
		}
		$temporary->save();

		// Lista fornecedores
		$temporary = new Temporary("produto_prodfornec", TRUE);
		$temporary->setcolumns(array("codfornec", "reffornec", "principal"));
		foreach($this->arr_prodfornec as $prodfornec){
			$temporary->append();
			$temporary->setvalue("last", "codfornec", $prodfornec->getcodfornec());
			$temporary->setvalue("last", "reffornec", $prodfornec->getreffornec());
			$temporary->setvalue("last", "principal", $prodfornec->getprincipal());
		}
		$temporary->save();

		// Mix por estabelecimento
		$temporary = new Temporary("produto_produtoestab", TRUE);
		$temporary->setcolumns(array("codestabelec", "disponivel", "custotab", "custorep", "precoatc", "precovrj", "precoatcof", "precovrjof", "diasvalidade"));
		foreach($this->arr_produtoestab as $produtoestab){
			$temporary->append();
			$temporary->setvalue("last", "codestabelec", $produtoestab->getcodestabelec());
			$temporary->setvalue("last", "disponivel", $produtoestab->getdisponivel());
			$temporary->setvalue("last", "custotab", $produtoestab->getcustotab());
			$temporary->setvalue("last", "custorep", $produtoestab->getcustorep());
			$temporary->setvalue("last", "precoatc", $produtoestab->getprecoatc());
			$temporary->setvalue("last", "precovrj", $produtoestab->getprecovrj());
			$temporary->setvalue("last", "precoatcof", $produtoestab->getprecoatcof());
			$temporary->setvalue("last", "precovrjof", $produtoestab->getprecovrjof());
			$temporary->setvalue("last", "diasvalidade", $produtoestab->getdiasvalidade());
		}
		$temporary->save();

		return $html;
	}

	function getquery($limit = NULL, $offset = NULL, $addFields = NULL, $order = NULL, $parcialString = FALSE){
		$condition = array();
		$primarykey = $this->getprimarykey();

		// Verifica se a chave primaria foi informada dentro de um array
		if(!is_array($primarykey)){
			$primarykey = array($primarykey);
		}
		// Verifica se todas as chaves primarias foram preenchidas
		$foundNullPk = FALSE;
		foreach($primarykey as $key){
			if(strlen($this->fields[$key]) == 0){
				$foundNullPk = TRUE;
				break;
			}
		}
		// Percorre todos os campos da tabela para criar a condicao da instrucao SQL
		foreach($this->fields as $field => $value){
			if(strlen($value) > 0 && ($foundNullPk || in_array($field, $primarykey))){
				$condition[] = $this->condition($this->table, $field, $value, $parcialString);
			}
		}
		if(count($this->arraysearch) > 0){
			foreach($this->arraysearch as $i => $v){
				$condition[] = $this->condition($this->table, $i, $v, $parcialString);
			}
		}
		$q_from = "FROM ".$this->table." ";
		$q_join = "";		
		foreach($this->relation as $relation){
			
			$found = FALSE;
			foreach($relation["values"] as $column => $value){	
						
				if($value != NULL){
					if($column == "codfornec"){
						$condition[] = "(produto.codproduto in (SELECT codproduto FROM prodfornec as pf_aux WHERE pf_aux.codfornec IN (SELECT f_aux.codfornec FROM fornecedor AS f_aux where f_aux.codfornec = $value OR f_aux.codfornec_vinculado = $value)))";
						$found = TRUE;
					}else{						
						$condition[] = $this->condition($relation["table2"], $column, $value, $parcialString);
						$found = TRUE;
					}
				}
			}
			// Verifica se existe a chamada de alguma tabela secundaria nos campos adicionais
			if(!$found && strpos($addFields, $relation["table2_alias"]) !== FALSE){
				$found = TRUE;
			}
			if($found || $this->forcerelation){
				$join = array();
				for($i = 0; $i < sizeof($relation["column1"]); $i++){
					$join1 = (strpos($relation["column1"][$i], "'") === FALSE ? $relation["table1"].".".$relation["column1"][$i] : $relation["column1"][$i]);
					$join2 = (strpos($relation["column2"][$i], "'") === FALSE ? $relation["table2_alias"].".".$relation["column2"][$i] : $relation["column2"][$i]);
					$join[] = $join1." = ".$join2;
				}
				$q_join .= $relation["join"]." JOIN ".$relation["table2"]." AS ".$relation["table2_alias"]." ON (".implode(" AND ", $join).") ";
			}
		}
		$q_select = "SELECT ".(strlen($q_join) > 0 && strlen($order) == 0 ? "DISTINCT " : " ").$this->table.".*".($addFields != NULL ? ", ".$addFields : "")." ";		
		
		if(sizeof($condition) > 0){			
			$q_where = "WHERE ".implode(" AND ", $condition)." ";
		}else{
			$q_where = "";
		}
		if($order != NULL){
			$q_order = "ORDER BY ".$order." ";
		}elseif($order != NULL){
			$q_order = "ORDER BY ".$order." ";
		}else{
			$q_order = "";
		}
		$limit = ($limit == NULL ? $this->limit : $limit);
		$query = $q_select.$q_from.$q_join.$q_where.$q_order.($limit != NULL ? "LIMIT ".$limit : "").($offset != NULL ? "OFFSET ".$offset : "");
				
		return utf8_encode($query);
	}

	function getcomporcamento(){
		return $this->fields["comporcamento"];
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getdescricaofiscal(){
		return $this->fields["descricaofiscal"];
	}

	function getcoddepto(){
		return $this->fields["coddepto"];
	}

	function getcodgrupo(){
		return $this->fields["codgrupo"];
	}

	function getcodsubgrupo(){
		return $this->fields["codsubgrupo"];
	}

	function getcodsimilar(){
		return $this->fields["codsimilar"];
	}

	function getestminimo($format = FALSE){
		return ($format ? number_format($this->fields["estminimo"], 2, ",", "") : $this->fields["estminimo"]);
	}

	function getestmaximo($format = FALSE){
		return ($format ? number_format($this->fields["estmaximo"], 2, ",", "") : $this->fields["estmaximo"]);
	}

	function getpesoliq($format = FALSE){
		return ($format ? number_format($this->fields["pesoliq"], 3, ",", "") : $this->fields["pesoliq"]);
	}

	function getpesobruto($format = FALSE){
		return ($format ? number_format($this->fields["pesobruto"], 3, ",", "") : $this->fields["pesobruto"]);
	}

	function getcodsazonal(){
		return $this->fields["codsazonal"];
	}

	function getcodreceita(){
		return $this->fields["codreceita"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getcodnutricional(){
		return $this->fields["codnutricional"];
	}

	function getpesado(){
		return $this->fields["pesado"];
	}

	function getlimite(){
		return $this->fields["limite"];
	}

	function getforalinha(){
		return $this->fields["foralinha"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getcodmarca(){
		return $this->fields["codmarca"];
	}

	function getqtdeetiq(){
		return $this->fields["qtdeetiq"];
	}

	function getdiasvalidade($format = FALSE){
		return ($format ? number_format($this->fields["diasvalidade"], 2, ",", "") : $this->fields["diasvalidade"]);
	}

	function getpesounid(){
		return $this->fields["pesounid"];
	}

	function getdtforalinha($format = FALSE){
		return ($format ? convert_date($this->fields["dtforalinha"], "Y-m-d", "d/m/Y") : $this->fields["dtforalinha"]);
	}

	function getvasilhame(){
		return $this->fields["vasilhame"];
	}

	function getcodvasilhame(){
		return $this->fields["codvasilhame"];
	}

	function getflutuante(){
		return $this->fields["flutuante"];
	}

	function getcodembalcpa(){
		return $this->fields["codembalcpa"];
	}

	function getcodembalvda(){
		return $this->fields["codembalvda"];
	}

	function getcodfamilia(){
		return $this->fields["codfamilia"];
	}

	function getprecovariavel(){
		return $this->fields["precovariavel"];
	}

	function getcustotab($format = FALSE){
		return ($format ? number_format($this->fields["custotab"], 2, ",", "") : $this->fields["custotab"]);
	}

	function getcustorep($format = FALSE){
		return ($format ? number_format($this->fields["custorep"], 2, ",", "") : $this->fields["custorep"]);
	}

	function getcodcfnfe(){
		return $this->fields["codcfnfe"];
	}

	function getcodcfnfs(){
		return $this->fields["codcfnfs"];
	}

	function getcodcfpdv(){
		return $this->fields["codcfpdv"];
	}

	function getcodpiscofinsent(){
		return $this->fields["codpiscofinsent"];
	}

	function getcodpiscofinssai(){
		return $this->fields["codpiscofinssai"];
	}

	function getcodipi(){
		return $this->fields["codipi"];
	}

	function getidncm(){
		return $this->fields["idncm"];
	}

	function getprecoatc($format = FALSE){
		return ($format ? number_format($this->fields["precoatc"], 2, ",", "") : $this->fields["precoatc"]);
	}

	function getprecovrj($format = FALSE){
		return ($format ? number_format($this->fields["precovrj"], 2, ",", "") : $this->fields["precovrj"]);
	}

	function getprecoatcof($format = FALSE){
		return ($format ? number_format($this->fields["precoatcof"], 2, ",", "") : $this->fields["precoatcof"]);
	}

	function getprecovrjof($format = FALSE){
		return ($format ? number_format($this->fields["precovrjof"], 2, ",", "") : $this->fields["precovrjof"]);
	}

	function getqtdatacado($format = FALSE){
		return ($format ? number_format($this->fields["qtdatacado"], 2, ",", "") : $this->fields["qtdatacado"]);
	}

	function getmargematc($format = FALSE){
		return ($format ? number_format($this->fields["margematc"], 2, ",", "") : $this->fields["margematc"]);
	}

	function getmargemvrj($format = FALSE){
		return ($format ? number_format($this->fields["margemvrj"], 2, ",", "") : $this->fields["margemvrj"]);
	}

	function getdatainclusao($format = FALSE){
		return ($format ? convert_date($this->fields["datainclusao"], "Y-m-d", "d/m/Y") : $this->fields["datainclusao"]);
	}

	function getatualizancm(){
		return $this->fields["atualizancm"];
	}

	function getteclarapida(){
		return $this->fields["teclarapida"];
	}

	function gethoralog(){
		return $this->fields["horalog"];
	}

	function getcontrnumeroserie(){
		return $this->fields["contrnumeroserie"];
	}

	function gettipocompra(){
		return $this->fields["tipocompra"];
	}

	function getcontrnumerolote(){
		return $this->fields["contrnumerolote"];
	}

	function getaliqiva($format = FALSE){
		return ($format ? number_format($this->fields["aliqiva"], 4, ",", "") : $this->fields["aliqiva"]);
	}

	function getaliqmedia($format = FALSE){
		return ($format ? number_format($this->fields["aliqmedia"], 4, ",", "") : $this->fields["aliqmedia"]);
	}

	function getmesesgarantia(){
		return $this->fields["mesesgarantia"];
	}

	function getespecificacoes(){
		return $this->fields["especificacoes"];
	}

	function getalcoolico(){
		return $this->fields["alcoolico"];
	}

	function getpremiovenda($format = FALSE){
		return ($format ? number_format($this->fields["premiovenda"], 2, ",", "") : $this->fields["premiovenda"]);
	}

	function getprecopolitica($format = FALSE){
		return ($format ? number_format($this->fields["precopolitica"], 2, ",", "") : $this->fields["precopolitica"]);
	}

	function getcodequivalente(){
		return $this->fields["codequivalente"];
	}

	function gettextovenda(){
		return $this->fields["textovenda"];
	}

	function getmultiplicado(){
		return $this->fields["multiplicado"];
	}

	function getcodetiqgondola(){
		return $this->fields["codetiqgondola"];
	}

	function getdescricaofiscal2(){
		return $this->fields["descricaofiscal2"];
	}

	function getnatreceita(){
		return $this->fields["natreceita"];
	}

	function getgerapdv(){
		return $this->fields["gerapdv"];
	}

	function getcodprodutoant(){
		return $this->fields["codprodutoant"];
	}

	function getdtsaneamento($format = FALSE){
		return ($format ? convert_date($this->fields["dtsaneamento"], "Y-m-d", "d/m/Y") : $this->fields["dtsaneamento"]);
	}

	function getdtsaneamentoins($format = FALSE){
		return ($format ? convert_date($this->fields["dtsaneamentoins"], "Y-m-d", "d/m/Y") : $this->fields["dtsaneamentoins"]);
	}

	function getcodprodutosped(){
		return $this->fields["codprodutosped"];
	}

	function getaltura($format = FALSE){
		return ($format ? number_format($this->fields["altura"], 3, ",", "") : $this->fields["altura"]);
	}

	function getlargura($format = FALSE){
		return ($format ? number_format($this->fields["largura"], 3, ",", "") : $this->fields["largura"]);
	}

	function getcomprimento($format = FALSE){
		return ($format ? number_format($this->fields["comprimento"], 3, ",", "") : $this->fields["comprimento"]);
	}

	function getespecificacoesreduz(){
		return $this->fields["especificacoesreduz"];
	}

	function getcomplemento(){
		return $this->fields["complemento"];
	}

	function getenviarecommerce(){
		return $this->fields["enviarecommerce"];
	}

	function getcest(){
		return $this->fields["cest"];
	}

	function getorigempreco(){
		return $this->fields["origempreco"];
	}

	function getprodcotacao(){
		return $this->fields["prodcotacao"];
	}

	function getpesqconcorrente(){
		return $this->fields["pesqconcorrente"];
	}

	function gettipo(){
		return $this->fields["tipo"];
	}

	function getcarrossel(){
		return $this->fields["carrossel"];
	}

	function getfabricacaopropria(){
		return $this->fields["fabricacaopropria"];
	}

	function getsanear(){
		return $this->fields["sanear"];
	}

	function getidmercadologico(){
		return $this->fields["idmercadologico"];
	}

	function setcomporcamento($value){
		$this->fields["comporcamento"] = value_string($value, 200);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 100);
	}

	function setdescricaofiscal($value){
		$this->fields["descricaofiscal"] = value_string($value, 200);
	}

	function setcoddepto($value){
		$this->fields["coddepto"] = value_numeric($value);
	}

	function setcodgrupo($value){
		$this->fields["codgrupo"] = value_numeric($value);
	}

	function setcodsubgrupo($value){
		$this->fields["codsubgrupo"] = value_numeric($value);
	}

	function setcodsimilar($value){
		$this->fields["codsimilar"] = value_numeric($value);
	}

	function setestminimo($value){
		$this->fields["estminimo"] = value_numeric($value);
	}

	function setestmaximo($value){
		$this->fields["estmaximo"] = value_numeric($value);
	}

	function setpesoliq($value){
		$this->fields["pesoliq"] = value_numeric($value);
	}

	function setpesobruto($value){
		$this->fields["pesobruto"] = value_numeric($value);
	}

	function setcodsazonal($value){
		$this->fields["codsazonal"] = value_numeric($value);
	}

	function setcodreceita($value){
		$this->fields["codreceita"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 500);
	}

	function setcodnutricional($value){
		$this->fields["codnutricional"] = value_numeric($value);
	}

	function setpesado($value){
		$this->fields["pesado"] = value_string($value, 1);
	}

	function setlimite($value){
		$this->fields["limite"] = value_string($value, 1);
	}

	function setforalinha($value){
		$this->fields["foralinha"] = value_string($value, 1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setcodmarca($value){
		$this->fields["codmarca"] = value_numeric($value);
	}

	function setqtdeetiq($value){
		$this->fields["qtdeetiq"] = value_numeric($value);
	}

	function setdiasvalidade($value){
		$this->fields["diasvalidade"] = value_numeric($value);
	}

	function setpesounid($value){
		$this->fields["pesounid"] = value_string($value, 1);
	}

	function setdtforalinha($value){
		$this->fields["dtforalinha"] = value_date($value);
	}

	function setvasilhame($value){
		$this->fields["vasilhame"] = value_string($value, 1);
	}

	function setcodvasilhame($value){
		$this->fields["codvasilhame"] = value_numeric($value);
	}

	function setflutuante($value){
		$this->fields["flutuante"] = value_string($value, 1);
	}

	function setcodembalcpa($value){
		$this->fields["codembalcpa"] = value_numeric($value);
	}

	function setcodembalvda($value){
		$this->fields["codembalvda"] = value_numeric($value);
	}

	function setcodfamilia($value){
		$this->fields["codfamilia"] = value_numeric($value);
	}

	function setprecovariavel($value){
		$this->fields["precovariavel"] = value_string($value, 1);
	}

	function setcustotab($value){
		$this->fields["custotab"] = value_numeric($value);
	}

	function setcustorep($value){
		$this->fields["custorep"] = value_numeric($value);
	}

	function setcodcfnfe($value){
		$this->fields["codcfnfe"] = value_numeric($value);
	}

	function setcodcfnfs($value){
		$this->fields["codcfnfs"] = value_numeric($value);
	}

	function setcodcfpdv($value){
		$this->fields["codcfpdv"] = value_numeric($value);
	}

	function setcodpiscofinsent($value){
		$this->fields["codpiscofinsent"] = value_numeric($value);
	}

	function setcodpiscofinssai($value){
		$this->fields["codpiscofinssai"] = value_numeric($value);
	}

	function setcodipi($value){
		$this->fields["codipi"] = value_numeric($value);
	}

	function setidncm($value){
		$this->fields["idncm"] = value_numeric($value);
	}

	function setprecoatc($value){
		$this->fields["precoatc"] = value_numeric($value);
	}

	function setprecovrj($value){
		$this->fields["precovrj"] = value_numeric($value);
	}

	function setprecoatcof($value){
		$this->fields["precoatcof"] = value_numeric($value);
	}

	function setprecovrjof($value){
		$this->fields["precovrjof"] = value_numeric($value);
	}

	function setqtdatacado($value){
		$this->fields["qtdatacado"] = value_numeric($value);
	}

	function setmargematc($value){
		$this->fields["margematc"] = value_numeric($value);
	}

	function setmargemvrj($value){
		$this->fields["margemvrj"] = value_numeric($value);
	}

	function setatualizancm($value){
		$this->fields["atualizancm"] = value_string($value, 2);
	}

	function setteclarapida($value){
		$this->fields["teclarapida"] = value_string($value, 20);
	}

	function setdatainclusao($value){
		$this->fields["datainclusao"] = value_date($value);
	}

	function sethoralog($value){
		$this->fields["horalog"] = value_time($value);
	}

	function setcontrnumeroserie($value){
		$this->fields["contrnumeroserie"] = value_string($value, 1);
	}

	function settipocompra($value){
		$this->fields["tipocompra"] = value_string($value, 1);
	}

	function setcontrnumerolote($value){
		$this->fields["contrnumerolote"] = value_string($value, 1);
	}

	function setaliqiva($value){
		$this->fields["aliqiva"] = value_numeric($value);
	}

	function setaliqmedia($value){
		$this->fields["aliqmedia"] = value_numeric($value);
	}

	function setmesesgarantia($value){
		$this->fields["mesesgarantia"] = value_numeric($value);
	}

	function setespecificacoes($value){
		$this->fields["especificacoes"] = value_string($value);
	}

	function setalcoolico($value){
		$this->fields["alcoolico"] = value_string($value, 1);
	}

	function setpremiovenda($value){
		$this->fields["premiovenda"] = value_numeric($value);
	}

	function setprecopolitica($value){
		$this->fields["precopolitica"] = value_numeric($value);
	}

	function setcodequivalente($value){
		$this->fields["codequivalente"] = value_numeric($value);
	}

	function settextovenda($value){
		$this->fields["textovenda"] = value_string($value);
	}

	function setcodean($value){
		$this->setrelationvalue("produtoean", "codean", value_string($value, 20));
	}

	function setcodfornec($value){
		$this->setrelationvalue("prodfornec", "codfornec", value_numeric($value));
	}

	function setreffornec($value){
		$this->setrelationvalue("prodfornec", "reffornec", value_string($value, 20));
	}

	function setcodestabelec($value){
		$this->setrelationvalue("produtoestab", "codestabelec", $value);
		$usuario = objectbytable("usuario", $_SESSION["WUser"], $con);
		if(!$usuario->getcodgrupo() == 1){
			$this->setrelationvalue("produtoestab", "disponivel", "S");
		}
	}

	function setmultiplicado($value){
		$this->fields["multiplicado"] = value_string($value, 1);
	}

	function setcodetiqgondola($value){
		$this->fields["codetiqgondola"] = value_string($value, 2);
	}

	function setdescricaofiscal2($value){
		$this->fields["descricaofiscal2"] = value_string($value, 100);
	}

	function setnatreceita($value){
		$this->fields["natreceita"] = value_string($value, 3);
	}

	function setgerapdv($value){
		$this->fields["gerapdv"] = value_string($value, 1);
	}

	function setcodprodutoant($value){
		$this->fields["codprodutoant"] = value_string($value, 20);
	}

	function setdtsaneamento($value){
		$this->fields["dtsaneamento"] = value_date($value);
	}

	function setdtsaneamentoins($value){
		$this->fields["dtsaneamentoins"] = value_date($value);
	}

	function setcodprodutosped($value){
		$this->fields["codprodutosped"] = value_string($value, 40);
	}

	function setaltura($value){
		$this->fields["altura"] = value_numeric($value);
	}

	function setlargura($value){
		$this->fields["largura"] = value_numeric($value);
	}

	function setcomprimento($value){
		$this->fields["comprimento"] = value_numeric($value);
	}

	function setespecificacoesreduz($value){
		$this->fields["especificacoesreduz"] = value_string($value, 200);
	}

	function setcomplemento($value){
		$this->fields["complemento"] = value_string($value, 200);
	}

	function setenviarecommerce($value){
		$this->fields["enviarecommerce"] = value_string($value, 1);
	}

	function setcest($value){
		$this->fields["cest"] = value_string($value, 10);
	}

	function setorigempreco($value){
		$this->fields["origempreco"] = value_string($value, 40);
	}

	function setprodcotacao($value){
		$this->fields["prodcotacao"] = value_string($value, 1);
	}

	function setpesqconcorrente($value){
		$this->fields["pesqconcorrente"] = value_string($value, 1);
	}

	function settipo($value){
		$this->fields["tipo"] = value_string($value, 2);
	}

	function setcarrossel($value){
		$this->fields["carrossel"] = value_string($value, 1);
	}

	function setfabricacaopropria($value){
		$this->fields["fabricacaopropria"] = value_string($value, 1);
	}

	function setsanear($value){
		$this->fields["sanear"] = value_string($value, 1);
	}

	function setidmercadologico($value){
		$this->fields["idmercadologico"] = value_numeric($value);
	}

}
