<?php

set_time_limit(0);
ini_set("memory_limit",-1);

require_once("../class/sped.class.php");
require_once("../lib/sqlformatter-1.0.0/lib/SqlFormatter.php");

final class SpedFiscal extends Sped{

	protected $estabelecimento;
	protected $planocontas; // Plano de conta do estabelecimento
	protected $gerar_tudozerado = FALSE; // Campo booleano que determina se deve gerar o arquivo todo zerado
	protected $gerar_notafiscal = FALSE; // Campo booleano que determina se deve gerar notas fiscais
	protected $gerar_cupom = FALSE; // Campo booleano que determina se deve gerar cupons
	protected $gerar_inventario = FALSE; // Campo booleano que determina se deve gerar registro de inventario
	protected $gerar_alteracaotributaria = FALSE; // Campo booleano que determina se deve gerar registro de alteracao tributaria
	protected $gerar_contmatic = FALSE; // Campo booleano que determina se deve gerar os campos relacionados a PIS/Cofins de acordo com o layout Contmatic
	protected $gerar_c140c141 = FALSE; // Gerar no contmatic C140 C141
	protected $arr_filtro = array(); // Array com os filtro tecnicos que serao aplicados na busca da notas fiscais (nome da coluna como indice)
	protected $arr_c195 = array(); // Array com os codigos de observacao do registro 0460
	protected $arr_cidade; // Array com as cidades do estabelecimento e parceiros (codcidade com indice do array)
	protected $arr_classfiscal; // Array com as classificacoes fiscais dos itens das notas (codcf como indice do array)
	protected $arr_cliente; // Array com os clientes das notas (codfornec com indice do array
	protected $arr_cupom; // Array com os de cupons (codmaparesumo com indice e um array com os cupons como valor do array principal)
	protected $arr_ecf; // Array com os ECFs (codecf como indice do array)
	protected $arr_embalagem; // Array com as embalagens dos itens (codembal como indice do array)
	protected $arr_estabelecimento; // Array com os estabelecimentos das notas (transferencia) (codestabelec como indice do array)
	protected $arr_fornecedor; // Array com os fornecedores das notas (codfornec como indice do array)
	protected $arr_ipi; // Array com os IPIs dos itens das notas (codipi como indice do array)
	protected $arr_lancamento; // Array com os lancamentos das notas (idnotafiscal como indice do array)
	protected $arr_maparesumo; // Array com os mapas resumo
	protected $arr_ncm; // Array com os NCMs dos items (idncm como indice do array)
	protected $arr_notacomplemento; // Array contendo as nota de complemento
	protected $arr_notadiversa; // Array contendo as notas diversas (como nota de energia eletrica)
	protected $arr_notafiscal; // Array com todas as notas fiscais
	protected $arr_notafiscalimposto; // Array com os impostos das notas fiscais (tabela notafiscalimposto) (idnotafiscal como indice do array)
	protected $arr_notafrete; // Array com todas as notas de frete
	protected $arr_operacaonota; // Array com os tipos de operacao (operacao como indice do array)
	protected $arr_piscofins; // Array com os PIS/COFINS dos itens das notas (codpiscofins como indice do array)
	protected $arr_produto; // Array com os produtos das notas (codproduto como indice do array)
	protected $arr_produtoean; // Array com os eans dos produtos (codproduto como indice do array)
	protected $arr_transportadora; // Array com as transportadoras das notas (codtransp como indice do array)
	protected $arr_unidade; // Array com as unidades dos itens (codunidade como indice do array)
	protected $arr_outroscreditodebito; // Array outros creditos outros debitos
	protected $creditoicms; // Valor do credito de ICMS relativo ao periodo anterior
	protected $debitoicms; // Valor do debito de ICMS relativo ao periodo atual (o sistema que calcula)
	protected $dataalteracaotributariaini; // Data inicial da alteracao tributaria
	protected $dataalteracaotributariafin; // Data final da alteracao tributaria
	protected $arr_natoperacao;
	protected $gerar_0205;
	protected $gerar_0400;
	protected $r0460_cod_obs = 1; // Codigo da observacao do registro 0460

	function gerar_contmatic($bool){
		if(is_bool($bool)){
			$this->gerar_contmatic = $bool;
		}
	}

	function gerar_c140c141($bool){
		if(is_bool($bool)){
			$this->gerar_c140c141 = $bool;
		}
	}

	function gerar_0205($bool){
		if(is_bool($bool)){
			$this->gerar_0205 = $bool;
		}
	}

	function gerar_0400($bool){
		if(is_bool($bool)){
			$this->gerar_0400 = $bool;
		}
	}

	function setcreditoicms($creditoicms){
		$this->creditoicms = value_numeric($creditoicms);
	}

	// Adiciona um filtro na busca de notas fiscais
	public function filtro($coluna, $valor){
		if(strlen(trim($valor)) > 0){
			$this->arr_filtro[$coluna] = $valor;
		}
	}

	// Transforma um cupom em notafiscal
	private function cupom_para_notafiscal($cupom){

		$idnotafiscal = "C".$cupom["idcupom"];

		$arr_itnotafiscal = array();
		foreach($cupom["itcupom"] as $i => $itcupom){
			$totalbaseicms = (in_array($itcupom["tptribicms"], array("T", "R")) ? $itcupom["valortotal"] : 0);
			$totalicms = ($itcupom["aliqicms"] > 0 ? $totalbaseicms * $itcupom["aliqicms"] / 100 : 0);

			$itnotafiscal = array(
				"idnotafiscal" => $idnotafiscal,
				"codestabelec" => $cupom["codestabelec"],
				"operacao" => "VD",
				"dtentrega" => $cupom["dtmovto"],
				"seqitem" => ($i + 1),
				"codproduto" => $itcupom["codproduto"],
				"preco" => $itcupom["preco"],
				"precopolitica" => $itcupom["preco"],
				"custorep" => $itcupom["custorep"],
				"quantidade" => $itcupom["quantidade"],
				"qtdeunidade" => 1,
				"codunidade" => $itcupom["codunidade"],
				"natoperacao" => ($itcupom["tptribicms"] === "F" ? "5.405" : "5.102"),
				"totalbruto" => ($itcupom["quantidade"] * $itcupom["preco"]),
				"valdescto" => ($itcupom["desconto"] / $itcupom["quantidade"]),
				"percdescto" => 0,
				"totaldesconto" => $itcupom["desconto"],
				"valacresc" => ($itcupom["acrescimo"] / $itcupom["quantidade"]),
				"percacresc" => 0,
				"totalacrescimo" => $itcupom["acrescimo"],
				"tipoipi" => "F",
				"valipi" => 0,
				"percipi" => 0,
				"totalipi" => 0,
				"valfrete" => 0,
				"percfrete" => 0,
				"totalfrete" => 0,
				"tptribicms" => $itcupom["tptribicms"],
				"aliqicms" => $itcupom["aliqicms"],
				"redicms" => 0,
				"totalbaseicms" => $totalbaseicms,
				"totalicms" => $totalicms,
				"csticms" => $this->csticms($itcupom),
				"aliqiva" => 0,
				"valorpauta" => 0,
				"totalbaseicmssubst" => 0,
				"totalicmssubst" => 0,
				"aliqpis" => $itcupom["aliqpis"],
				"redpis" => 0,
				"totalbasepis" => $itcupom["totalbasepis"],
				"totalpis" => $itcupom["totalpis"],
				"aliqcofins" => $itcupom["aliqcofins"],
				"redcofins" => 0,
				"totalbasecofins" => $itcupom["totalbasecofins"],
				"totalcofins" => $itcupom["totalcofins"],
				"guiagnre" => 0,
				"totalgnre" => 0,
				"totalliquido" => $itcupom["valortotal"],
				"bonificado" => "N",
				"composicao" => $itcupom["composicao"]
			);
			$arr_itnotafiscal[] = $itnotafiscal;
		}

		$totalquantidade = 0;
		$totalbruto = 0;
		$totalbaseicms = 0;
		$totalicms = 0;
		$totalliquido = 0;
		foreach($arr_itnotafiscal as $itnotafiscal){
			$totalquantidade += $itnotafiscal["quantidade"];
			$totalbruto += $itnotafiscal["totalbruto"];
			$totalbaseicms += $itnotafiscal["totalbaseicms"];
			$totalicms += $itnotafiscal["totalicms"];
			$totalliquido += $itnotafiscal["totalliquido"];
		}

		$chavecfe = $cupom["chavecfe"];
		$serie = (int) substr($chavecfe, 22, 3);
		$numnotafis = (int) substr($chavecfe, 25, 9);

		$notafiscal = array(
			"idnotafiscal" => $idnotafiscal,
			"codestabelec" => $cupom["codestabelec"],
			"operacao" => "VD",
			"status" => $cupom["status"],
			"emissaopropria" => "S",
			"finalidade" => "1",
			"tipoemissao" => "1",
			"numnotafis" => $numnotafis,
			"serie" => $serie,
			"chavenfe" => $cupom["chavecfe"],
			"natoperacao" => "5.102",
			"dtemissao" => $cupom["dtmovto"],
			"dtentrega" => $cupom["dtmovto"],
			"tipoparceiro" => "C",
			"codparceiro" => $cupom["codcliente"],
			"totalquantidade" => $totalquantidade,
			"totalbruto" => $totalbruto,
			"totaldesconto" => $cupom["totaldesconto"],
			"totalacrescimo" => $cupom["totalacrescimo"],
			"modfrete" => "9",
			"totalfrete" => 0,
			"geraicms" => "S",
			"totalbaseicms" => $totalbaseicms,
			"totalicms" => $totalicms,
			"totalbaseisento" => ($totalliquido - $totalbaseicms),
			"totalicmsbasesubst" => 0,
			"totalicmssubst" => 0,
			"geraipi" => "N",
			"totalipi" => 0,
			"gerapiscofins" => "S",
			"totalpis" => $cupom["totalpis"],
			"totalbasepis" => $cupom["totalbasepis"],
			"totalcofins" => $cupom["totalcofins"],
			"totalbasecofins" => $cupom["totalbasecofins"],
			"totalgnre" => 0,
			"totalliquido" => $totalliquido,
			"numeroitens" => count($cupom["itcupom"]),
			"bonificacao" => "N"
		);

		$notafiscal["itnotafiscal"] = $arr_itnotafiscal;

		return $notafiscal;
	}

	public function gerar(){
		parent::gerar();
		$this->progresso_t = 28;

		// Limpa alguma variaveis
		$this->n_registro = array();

		$this->progresso("Validando dados enviados");

		// Verifica se foi informado o estabelecimento
		$this->estabelecimento = reset($this->arr_estabelecimento);
		if(!is_object($this->estabelecimento)){
			$_SESSION["ERROR"] = "Informe o estabelecimento emitente para o arquivo do SPED Fiscal.";
			return FALSE;
		}

		// Verifica se foi informado a data inicial
		if(strlen($this->datainicial) == 0){
			$_SESSION["ERROR"] = "Informe a data inicial da gera&ccedil;&atilde;o do arquivo do SPED Fiscal.";
			return FALSE;
		}

		// Verifica se foi informado a data final
		if(strlen($this->datafinal) == 0){
			$_SESSION["ERROR"] = "Informe a data final da gera&ccedil;&atilde;o do arquivo do SPED Fiscal.";
			return FALSE;
		}

		// Verifica se o perfil foi informado no estabelecimento
		if(strlen($this->estabelecimento->getperfil()) == 0){
			$_SESSION["ERROR"] = "Informe o perfil fiscal (A, B ou C) para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}

		// Verifica se o perfil foi informado no estabelecimento
		if(strlen($this->estabelecimento->getendereco()) == 0 || strlen($this->estabelecimento->getnumero()) == 0){
			$_SESSION["ERROR"] = "Informe o endereco com rua e numero para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}

		// Verifica se existe plano de conta ligado ao estabelecimento
		if(strlen($this->estabelecimento->getcodconta()) == 0){
			$_SESSION["ERROR"] = "Informe um plano de contas para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}else{
			$this->planocontas = objectbytable("planocontas", $this->estabelecimento->getcodconta(), $this->con);
		}

		// Verifica se o diretorio para geracao do arquivo foi informado
		if(strlen($this->estabelecimento->getdirarqfiscal()) == 0){
			$_SESSION["ERROR"] = "Informe o local de gera&ccedil;&atilde;o dos arquivos fiscais para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}

		// Busca a contabilidade do estabelecimento
		if(strlen($this->estabelecimento->getcodcontabilidade()) == 0){
			$_SESSION["ERROR"] = "Informe a contabilidade para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}
		$this->contabilidade = objectbytable("contabilidade", $this->estabelecimento->getcodcontabilidade(), $this->con);

		// Busca todas as operacoes de nota fiscal
		$this->progresso("Carregando tipos de operacoes de notas fiscais");
		$this->arr_operacaonota = array();
		$res = $this->con->query("SELECT * FROM operacaonota");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_operacaonota[$row["operacao"]] = $row;
		}

		// Operacoes que nao devem tratar composicao
		$arr_operacao_sem_composicao = array("CP", "DC", "PR", "TE");

		// Busca as notas fiscais e seus itens
		$this->progresso("Carregando notas fiscais");
		$this->arr_notafiscal = array();
		$arr_idnotafiscal = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query  = "SELECT notafiscal.* FROM notafiscal ";
			$query .= "INNER JOIN itnotafiscal ON (notafiscal.idnotafiscal = itnotafiscal.idnotafiscal) ";
			$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") AND notafiscal.status != 'P' ";
			$query .= "	AND ((notafiscal.operacao IN ('CP','DF','TE') AND notafiscal.dtentrega BETWEEN '".$this->datainicial."' AND '".$this->datafinal."') ";
			$query .= "	 OR  (notafiscal.operacao NOT IN ('CP','DF','TE') AND notafiscal.dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."')) ";
			if(!$this->gerar_contmatic){
//				$query .= "	AND notafiscal.status NOT IN ('C','I') ";
			}
			$query .= " AND notafiscal.gerafiscal = 'S' ";
//			$query .= " AND notafiscal.gerapiscofins = 'S' ";
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND notafiscal.dtentrega >= '$valor' ";
				}elseif($coluna == "dtentregafim"){
					$query .= "	AND notafiscal.dtentrega <= '$valor' ";
				}elseif($coluna == "natoperacao"){
					$query .= " AND itnotafiscal.natoperacao = '$valor' ";
				}else{
					$query .= "	AND notafiscal.".$coluna." = '$valor' ";
				}
			}
			$query .= "ORDER BY idnotafiscal ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_notafiscal[$row["idnotafiscal"]] = $row;
				$arr_idnotafiscal[] = $row["idnotafiscal"];
			}
			if(count($arr_idnotafiscal) > 0){
				$arr_operacao_sem_composicao_aspas = array();
				foreach($arr_operacao_sem_composicao as $operacao){
					$arr_operacao_sem_composicao_aspas[] = "'{$operacao}'";
				}
				$query = "SELECT * ";
				$query .= "FROM itnotafiscal ";
				$query .= "WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscal).") ";
				$query .= "	AND (operacao NOT IN (".implode(",", $arr_operacao_sem_composicao_aspas).") OR composicao IN ('N','P')) ";

				$query .= "ORDER BY idnotafiscal, seqitem ";
				$res = $this->con->query($query);
				$arr = $res->fetchAll(2);
				foreach($arr as $row){
					$row["preco"] = $row["precopolitica"];
					$this->arr_notafiscal[$row["idnotafiscal"]]["itnotafiscal"][] = $row;
				}
			}
		}
		$_SESSION["SPED"]["arr_idnotafiscal"] = $arr_idnotafiscal;

		// Verifica quais itens de composicao devem ser gerados
		$arr_composicao = object_array(objectbytable("composicao", NULL, $this->con));
		foreach($this->arr_notafiscal as $i => $notafiscal){
			if(in_array($notafiscal["operacao"], $arr_operacao_sem_composicao)){
				continue;
			}
			foreach($notafiscal["itnotafiscal"] as $j => $itnotafiscal){
				if($itnotafiscal["composicao"] === "P"){
					foreach($arr_composicao as $composicao){
						if($composicao->getcodproduto() === $itnotafiscal["codproduto"]){
							if($composicao->getexplosaoauto() === "S"){
								unset($this->arr_notafiscal[$i]["itnotafiscal"][$j]);
							}else{
								foreach($notafiscal["itnotafiscal"] as $k => $itnotafiscal2){
									if($itnotafiscal2["composicao"] === "F" && $itnotafiscal2["iditnotafiscalpai"] === $itnotafiscal["iditnotafiscal"]){
										unset($this->arr_notafiscal[$i]["itnotafiscal"][$k]);
									}
								}
							}
							break;
						}
					}
				}
			}
		}

		// Remove os totais e os itens das notas canceladas, inutilizadas e denegadas
		foreach($this->arr_notafiscal as $i => $notafiscal){
			if(in_array($notafiscal["status"], array("C", "D", "I"))){
				foreach($notafiscal as $coluna => $valor){
					if(strpos($coluna, "total") !== FALSE){
						$this->arr_notafiscal[$i][$coluna] = NULL;
					}
				}
				$this->arr_notafiscal[$i]["itnotafiscal"] = array();
			}
		}

		// Busca os impostos das notas fiscais
		$this->progresso("Carregando impostos das notas fiscais");
		$this->arr_notafiscalimposto = array();
		if($this->gerar_notafiscal && count($arr_idnotafiscal) > 0){
			$res = $this->con->query("SELECT * FROM notafiscalimposto WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscal).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_notafiscalimposto[$row["idnotafiscal"]][] = $row;
			}
		}

		// Busca as nota de complemento
		$this->progresso("Carregando notas de complemento");
		$this->arr_notacomplemento = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
//			$arr_idnotafiscal = array();
			$query = "SELECT * FROM notacomplemento ";
			$query .= "WHERE codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal=".$this->estabelecimento->getcodestabelec().") ";
			$query .= "	AND dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				if(strlen(trim($row["idnotafiscal"])) > 0){
					$this->arr_notacomplemento[$row["idnotacomplemento"]] = $row;
					$arr_idnotafiscal[] = $row["idnotafiscal"];
				}
			}

			// Busca as notas fiscais que estao sendo complementadas
			if(count($arr_idnotafiscal) > 0){
				$res = $this->con->query("SELECT * FROM notafiscal WHERE notafiscal.status != 'P' AND idnotafiscal IN (".implode(",", $arr_idnotafiscal).")");
				$arr = $res->fetchAll(2);
				foreach($this->arr_notacomplemento as $i => $notacomplemento){
					foreach($arr as $j => $row){
						if($row["idnotafiscal"] == $notacomplemento["idnotafiscal"]){
							$this->arr_notacomplemento[$i]["notafiscal"] = $row;
							break;
						}
					}
				}
			}
		}

		// Busca notas diversas
		$this->progresso("Carregando notas diversas");
		$this->arr_notadiversa = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query = "SELECT * ";
			$query .= "FROM notadiversa ";
			$query .= "LEFT JOIN itnotadiversa ON (notadiversa.idnotadiversa = itnotadiversa.idnotadiversa)";
			$query .= "WHERE codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal=".$this->estabelecimento->getcodestabelec().")";
			$query .= "	AND dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND notadiversa.dtemissao >= '".$valor."' ";
				}elseif($coluna == "dtentregafim"){
					$query .= "	AND notadiversa.dtemissao <= '".$valor."' ";
				}else{
					if($coluna == "operacao"){
						continue;
					}else{
						$query .= "	AND notadiversa.".$coluna." = '".$valor."' ";
					}
				}
			}
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_notadiversa[$row["idnotadiversa"]] = $row;
			}
		}

		// Busca as notas de frete
		$this->progresso("Carregando notas de frete");
		$this->arr_notafrete = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query = "SELECT * FROM notafrete ";
			$query .= "WHERE codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal=".$this->estabelecimento->getcodestabelec().") ";
			$query .= "	AND dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_notafrete[$row["idnotafrete"]] = $row;
			}
		}

		// Busca as outros creditos outros debitos
		$this->progresso("Carregando outros creditos outros debitos");
		$this->arr_outroscreditodebito = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query = "SELECT * FROM outroscreditodebito ";
			$query .= "WHERE codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal=".$this->estabelecimento->getcodestabelec().") ";
			$query .= "	AND dtdocumento BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_outroscreditodebito[$row["codoutroscreditodebito"]] = $row;
			}
		}

		// Busca os lancamentos das notas fiscais
		$this->progresso("Carregando lancamentos financeiros");
		$this->arr_lancamento = array();
		if(count($arr_idnotafiscal) > 0){
			$res = $this->con->query("SELECT * FROM lancamento WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscal).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_lancamento[$row["idnotafiscal"]][] = $row;
			}
		}

		// Lista com codigos de ECF
		$arr_codecf = array();

		// Busca os mapas resumo
		$this->progresso("Carregando mapa resumo");
		$this->arr_maparesumo = array();
		if(!$this->gerar_tudozerado && $this->gerar_cupom){
			$query = "SELECT * FROM maparesumo ";
			$query .= "INNER JOIN ecf ON (maparesumo.codecf = ecf.codecf) ";
			$query .= "WHERE maparesumo.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal=".$this->estabelecimento->getcodestabelec().") ";
			$query .= "	AND maparesumo.dtmovto BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$query .= " AND maparesumo.numeroreducoes > 0 ";
			$query .= "	AND maparesumo.operacaofim > 0 ";
			$query .= " AND maparesumo.reiniciofim > 0 ";
			$query .= "	AND maparesumo.totalliquido > 0 ";
			$query .= "	AND ecf.equipamentofiscal = 'ECF' ";
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND maparesumo.dtmovto >= '".$valor."' ";
				}elseif($coluna == "dtentregafim"){
					$query .= "	AND maparesumo.dtmovto <= '".$valor."' ";
				}
			}
			$query .= " ORDER BY maparesumo.dtmovto, maparesumo.codestabelec, maparesumo.caixa ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			$arr_codmaparesumo = array();
			foreach($arr as $row){
				if(strlen($row["codecf"]) == 0){
					$_SESSION["ERROR"] = "O mapa resumo de chave <b>".$maparesumo["codmaparesumo"]."</b> se encontra sem um ECF associado. Para processeguir com a gera&ccedil;&atilde;o do arquivo &eacute; necess&aacute;rio relacionar um ECF ao mapa resumo.<br><a onclick=\"$.messageBox('close');openProgram('MapaResumo','codmaparesumo=".$maparesumo["codmaparesumo"]."')\">Clique aqui</a> para abrir o mapa resumo.";
					return FALSE;
				}else{
					$arr_codecf[] = $row["codecf"];
				}
				$this->arr_maparesumo[$row["codmaparesumo"]] = $row;
				$arr_codmaparesumo[] = $row["codmaparesumo"];
			}
			if(count($arr_codmaparesumo) > 0){
				$res = $this->con->query("SELECT * FROM maparesumoimposto WHERE codmaparesumo IN (".implode(",", $arr_codmaparesumo).") AND totalliquido > 0");
				$arr = $res->fetchAll(2);
				foreach($arr as $row){
					$this->arr_maparesumo[$row["codmaparesumo"]]["maparesumoimposto"][] = $row;
				}
			}
		}

		// Buscar o cupons SAT sitetico por dia por equipamento fiscal
		if(!$this->gerar_tudozerado && $this->gerar_cupom){
			$query .= "SELECT ";

		}


		// Busca os cupons fiscais
		$this->progresso("Carregando cupons fiscais");
		$this->arr_cupom = array();
		$arr_idcupom = array();
		if($this->gerar_cupom){
			$query  = "SELECT cupom.*, (CASE WHEN ecf.equipamentofiscal = 'SAT' THEN '' ELSE maparesumo.codmaparesumo::TEXT END), ecf.equipamentofiscal ";
			$query .= "FROM cupom ";
			$query .= "INNER JOIN ecf ON (cupom.codecf = ecf.codecf) ";
			$query .= "LEFT JOIN maparesumo ON (cupom.codestabelec = maparesumo.codestabelec AND cupom.dtmovto = maparesumo.dtmovto AND cupom.caixa = maparesumo.caixa AND cupom.numeroecf = maparesumo.numeroecf) ";
			$query .= "WHERE cupom.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal=".$this->estabelecimento->getcodestabelec().") ";
			$query .= "	AND cupom.dtmovto BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";

			if(!$this->gerar_contmatic){
				$query .= "	AND cupom.status = 'A' ";
			}
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND cupom.dtmovto >= '".$valor."' ";
				}elseif($coluna == "dtentregafim"){
					$query .= "	AND cupom.dtmovto <= '".$valor."' ";
				}
			}
			$query .= "ORDER BY cupom.idcupom ";

			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$codmaparesumo = $row["codmaparesumo"];
				$equipamentofiscal = $row["equipamentofiscal"];
				if(strlen($codmaparesumo) === 0 && $equipamentofiscal === "SAT"){
					if(strlen($row["chavecfe"]) > 0){
						$codmaparesumo = "SAT";
					}else{
						continue;
					}
				}elseif(strlen($codmaparesumo) === 0 && $equipamentofiscal === "ECF"){
					continue;
				}
				$arr_codecf[] = $row["codecf"];
				$arr_idcupom[] = $row["idcupom"];
				$this->arr_cupom[$codmaparesumo][$row["idcupom"]] = $row;
				$this->arr_cupom[$codmaparesumo][$row["idcupom"]]["itcupom"] = array();
			}
		}

		// Busca os itens de cupom
		$this->progresso("Carregando itens de cupom fiscal");
		if(count($arr_idcupom) > 0){
			$query = "SELECT (CASE WHEN ecf.equipamentofiscal = 'SAT' THEN 'SAT' ELSE COALESCE(maparesumo.codmaparesumo::TEXT, 'SAT') END) AS codmaparesumo, itcupom.idcupom, itcupom.codproduto, ";
			$query .= "	SUM(itcupom.quantidade) AS quantidade, itcupom.preco, itcupom.desconto, ";
			$query .= "	itcupom.acrescimo, SUM(itcupom.valortotal) AS valortotal, itcupom.aliqicms, ";
			$query .= "	itcupom.tptribicms, itcupom.totalpis, itcupom.totalcofins, itcupom.totalbasepis,  ";
			$query .= "	itcupom.aliqpis, itcupom.aliqcofins, itcupom.totalbasecofins, embalagem.codunidade ";
			$query .= "FROM itcupom ";
			$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
			$query .= "INNER JOIN produto ON (itcupom.codproduto = produto.codproduto) ";
			$query .= "LEFT JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
			$query .= "LEFT JOIN maparesumo ON (cupom.codestabelec = maparesumo.codestabelec AND cupom.dtmovto = maparesumo.dtmovto AND cupom.caixa = maparesumo.caixa AND cupom.numeroecf = maparesumo.numeroecf) ";
			$query .= "INNER JOIN ecf ON (cupom.codecf = ecf.codecf) ";
			$query .= "WHERE cupom.idcupom IN (".implode(", ", $arr_idcupom).") ";
			$query .= "	AND (itcupom.status = 'A' ";
			if($this->gerar_contmatic){
				$query .= " OR cupom.status = 'C' ";
			}
			$query .= ")	AND itcupom.composicao IN ('N','P') ";
			$query .= " AND itcupom.valortotal >= 0.01 ";
			$query .= "GROUP BY maparesumo.codmaparesumo, itcupom.idcupom, itcupom.codproduto, ecf.equipamentofiscal, ";
			$query .= "	itcupom.preco, itcupom.desconto, itcupom.acrescimo, itcupom.aliqicms, ";
			$query .= "	itcupom.tptribicms,itcupom.totalpis, itcupom.totalcofins,itcupom.totalcofins, itcupom.totalbasepis, ";
			$query .= "	itcupom.aliqpis, itcupom.aliqcofins, itcupom.totalbasecofins, embalagem.codunidade ";
			$query .= "ORDER BY 1, 2 ";

			$res = $this->con->query($query);
			while($row = $res->fetch(2)){
				$this->arr_cupom[$row["codmaparesumo"]][$row["idcupom"]]["itcupom"][] = $row;
			}
		}

		// Remove cupons que ficaram sem itens
		foreach($this->arr_cupom as $codmaparesumo => $arr_cupom){
			foreach($arr_cupom as $idcupom => $cupom){
				if(!is_array($cupom["itcupom"]) || count($cupom["itcupom"]) == 0){
					unset($this->arr_cupom[$codmaparesumo][$idcupom]);
				}
			}
		}

		// Verifica se criou um array dos cupons para cada mapa resumo
		foreach($this->arr_maparesumo as $maparesumo){
			if(!is_array($this->arr_cupom[$maparesumo["codmaparesumo"]])){
				$this->arr_cupom[$maparesumo["codmaparesumo"]] = array();
			}
		}

		// Busca os ECFs
		$this->arr_ecf = array();
		$arr_codecf = array_unique($arr_codecf);
		if(count($arr_codecf) > 0){
			$this->progresso("Carregando ECFs");
			$arr_error_ecf = array();
			$res = $this->con->query("SELECT * FROM ecf WHERE codecf IN (".implode(", ", $arr_codecf).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_ecf[$row["codecf"]] = $row;
				if(strlen($row["modelo"]) == 0){
					$arr_error_ecf[] = $row["numfabricacao"];
				}
			}
			if(count($arr_error_ecf) > 0){
				echo messagebox("error", "", "Modelo ECF n&atilde;o informado para o numero de fabricacao <br><b>".implode("<br>", $arr_error_ecf)."</b><br> cadastre um modelo de ECF para continuar.");
				die();
			}
		}

		// Verifica se existem cupons que devem ser tratados como notafiscal
		if(is_array($this->arr_cupom["SAT"])){
			foreach($this->arr_cupom["SAT"] as $i => $cupom){
				$modelo = substr($cupom["chavecfe"], 20, 2);
				switch($modelo){
					case "65": // Nota fiscal consumidor eletronico (NFC-e)
						$notafiscal = $this->cupom_para_notafiscal($cupom);
						unset($this->arr_cupom["SAT"][$i]);
						$this->arr_notafiscal[$notafiscal["idnotafiscal"]] = $notafiscal;
						break;
				}
			}
		}

		// Lista de produtos
		$this->progresso("Carregando produtos");
		$this->arr_produto = array();
		$arr_codproduto = array();
		foreach($this->arr_notafiscal as $notafiscal){ // Busca os produtos das notas fiscais
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$arr_codproduto[] = $itnotafiscal["codproduto"];
			}
		}
		foreach($this->arr_cupom as $codmaparesumo => $arr_cupom){
			foreach($arr_cupom as $idcupom => $cupom){
				foreach($cupom["itcupom"] as $itcupom){
					$arr_codproduto[] = $itcupom["codproduto"];
				}
			}
		}
		if(strlen($this->datainventario) > 0){
			$datainventario = $this->datainventario;
		}else{
			$datainventario = $this->datafinal;
		}
		if(param("NOTAFISCAL", "CUSTOSPED", $this->con) == 1){
			$query_custosemimp = "CAST(produtoestab.custosemimp AS numeric(12,2)) ";
		}else{
			$query_custosemimp = " (SELECT custosemimp FROM produtoestabsaldo ps WHERE data <= '".$datainventario."' ";
			$query_custosemimp .= " AND ps.codproduto = produtoestab.codproduto AND ps.codestabelec = produtoestab.codestabelec ORDER BY data DESC  LIMIT 1)::numeric(12,2) ";
		}
		$arr_produto_inventario = array();
		if($this->gerar_inventario){
			$query = "SELECT codproduto, saldo(codestabelec,codproduto,'".$datainventario."') AS saldo, ".$query_custosemimp." AS custosemimp ";
			$query .= "FROM produtoestab ";
			$query .= "WHERE codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") ";
			$query .= "	AND produtoestab.disponivel = 'S' ";

			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				if($row["saldo"] > 0 && $row["custosemimp"] > 0){
					$arr_codproduto[] = $row["codproduto"];
					$arr_produto_inventario[$row["codproduto"]] = $row;
				}
			}
		}
		$arr_produto_alteracaotributaria = array();
		if($this->gerar_alteracaotributaria){
			$query = "SELECT produto.codproduto, saldo(produtoestab.codestabelec, produtoestab.codproduto, '".$datainventario."') AS saldo, ".$query_custosemimp." AS custosemimp, ";
			$query .= "	classfiscalold.codcst AS codcstold, classfiscalold.tptribicms AS tptribicmsold, classfiscalold.aliqicms AS aliqicmsold, classfiscalold.aliqredicms AS redicmsold, ";
			$query .= "	classfiscalnew.codcst AS codcstnew, classfiscalnew.tptribicms AS tptribicmsnew, classfiscalnew.aliqicms AS aliqicmsnew, classfiscalnew.aliqredicms AS redicmsnew ";
			$query .= "FROM produto ";
			$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
			$query .= "INNER JOIN ( ";
			$query .= "	SELECT DISTINCT ON (chave) chave::INT AS codproduto, dtcriacao AS dtalteracao, (registroold::JSON->>'codcfpdv')::INT AS codcfpdv ";
			$query .= "	FROM historico ";
			$query .= "	WHERE operacao = 'U' ";
			$query .= "		AND tabela = 'produto' ";
			$query .= "		AND dtcriacao BETWEEN '{$this->dataalteracaotributariaini}' AND '{$this->dataalteracaotributariafin}' ";
			$query .= "	ORDER BY chave, dtcriacao, hrcriacao ";
			$query .= ") AS historicoold ON (produto.codproduto = historicoold.codproduto) ";
			$query .= "INNER JOIN ( ";
			$query .= "	SELECT DISTINCT ON (chave) chave::INT AS codproduto, dtcriacao AS dtalteracao, (registronew::JSON->>'codcfpdv')::INT AS codcfpdv ";
			$query .= "	FROM historico ";
			$query .= "	WHERE operacao = 'U' ";
			$query .= "		AND tabela = 'produto' ";
			$query .= "		AND dtcriacao BETWEEN '{$this->dataalteracaotributariaini}' AND '{$this->dataalteracaotributariafin}' ";
			$query .= "	ORDER BY chave, dtalteracao DESC, hrcriacao DESC ";
			$query .= ") AS historiconew ON (produto.codproduto = historiconew.codproduto) ";
			$query .= "INNER JOIN classfiscal AS classfiscalold ON (historicoold.codcfpdv = classfiscalold.codcf) ";
			$query .= "INNER JOIN classfiscal AS classfiscalnew ON (historiconew.codcfpdv = classfiscalnew.codcf) ";
			$query .= "WHERE produtoestab.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->estabelecimento->getcodestabelec()}) ";
			$query .= "	AND ((classfiscalold.tptribicms IN ('T', 'R') AND classfiscalnew.tptribicms IN ('F')) ";
			$query .= "		OR (classfiscalold.tptribicms IN ('F') AND classfiscalnew.tptribicms IN ('T', 'R'))) ";

			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				if($row["saldo"] > 0){
					$arr_codproduto[] = $row["codproduto"];
					$arr_produto_alteracaotributaria[$row["codproduto"]] = $row;
				}
			}
		}
		$arr_codproduto = array_unique($arr_codproduto);
		if(count($arr_codproduto) > 0){
			$res = $this->con->query("SELECT * FROM produto WHERE codproduto IN (".implode(",", $arr_codproduto).") ORDER BY descricaofiscal");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_produto[$row["codproduto"]] = $row;
			}
		}
		foreach($arr_produto_inventario as $produto_inventario){
			$this->arr_produto[$produto_inventario["codproduto"]]["saldo"] = $produto_inventario["saldo"];
			$this->arr_produto[$produto_inventario["codproduto"]]["custosemimp"] = $produto_inventario["custosemimp"];
		}
		foreach($arr_produto_alteracaotributaria as $produto_alteracaotributaria){
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["alteracaotributaria"] = "S";
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["saldo"] = $produto_alteracaotributaria["saldo"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["custosemimp"] = $produto_alteracaotributaria["custosemimp"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["codcstold"] = $produto_alteracaotributaria["codcstold"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["tptribicmsold"] = $produto_alteracaotributaria["tptribicmsold"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["aliqicmsold"] = $produto_alteracaotributaria["aliqicmsold"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["redicmsold"] = $produto_alteracaotributaria["redicmsold"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["codcstnew"] = $produto_alteracaotributaria["codcstnew"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["tptribicmsnew"] = $produto_alteracaotributaria["tptribicmsnew"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["aliqicmsnew"] = $produto_alteracaotributaria["aliqicmsnew"];
			$this->arr_produto[$produto_alteracaotributaria["codproduto"]]["redicmsnew"] = $produto_alteracaotributaria["redicmsnew"];
		}

		// Busca todos os participantes das notas fiscais
		$this->progresso("Carregando parceiros das notas fiscais");
		$this->arr_cliente = array();
		$this->arr_estabelecimento = array();
		$this->arr_fornecedor = array();
		$this->arr_transportadora = array();
		$arr_codcliente = array();
		$arr_codestabelec = array();
		$arr_codfornec = array();
		$arr_codtransp = array();
		foreach($this->arr_notafiscal as $notafiscal){
			if(strlen($notafiscal["codparceiro"]) === 0){
				continue;
			}
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			switch($operacaonota["parceiro"]){
				case "C": $arr_codcliente[] = $notafiscal["codparceiro"];
					break;
				case "E": $arr_codestabelec[] = $notafiscal["codparceiro"];
					break;
				case "F": $arr_codfornec[] = $notafiscal["codparceiro"];
					break;
			}
			if(strlen($notafiscal["codtransp"]) > 0){
				$arr_codtransp[] = $notafiscal["codtransp"];
			}
		}
		foreach($this->arr_notacomplemento as $notacomplemento){
			$notafiscal = $notacomplemento["notafiscal"];
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			switch($operacaonota["parceiro"]){
				case "C": $arr_codcliente[] = $notafiscal["codparceiro"];
					break;
				case "E": $arr_codestabelec[] = $notafiscal["codparceiro"];
					break;
				case "F": $arr_codfornec[] = $notafiscal["codparceiro"];
					break;
			}
		}
		foreach($this->arr_notadiversa as $notadiversa){
			if(!in_array($notadiversa["tipodocumentofiscal"], array("06", "29", "28", "22", "21"))){
				continue;
			}
			switch($notadiversa["tipoparceiro"]){
				case "C": $arr_codcliente[] = $notadiversa["codparceiro"];
					break;
				case "E": $arr_codestabelec[] = $notadiversa["codparceiro"];
					break;
				case "F": $arr_codfornec[] = $notadiversa["codparceiro"];
					break;
			}
		}
		foreach($this->arr_notafrete as $notafrete){
			$arr_codtransp[] = $notafrete["codtransp"];
		}
		if(count($arr_codcliente) > 0){
			$res = $this->con->query("SELECT * FROM cliente WHERE codcliente IN (".implode(",", $arr_codcliente).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_cliente[$row["codcliente"]] = $row;
			}
		}
		if(count($arr_codestabelec) > 0){
			$res = $this->con->query("SELECT * FROM estabelecimento WHERE codestabelec IN (".implode(",", $arr_codestabelec).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_estabelecimento[$row["codestabelec"]] = $row;
			}
		}
		if(count($arr_codfornec) > 0){
			$res = $this->con->query("SELECT * FROM fornecedor WHERE codfornec IN (".implode(",", $arr_codfornec).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_fornecedor[$row["codfornec"]] = $row;
			}
		}
		if(count($arr_codtransp) > 0){
			$res = $this->con->query("SELECT * FROM transportadora WHERE codtransp IN (".implode(",", $arr_codtransp).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_transportadora[$row["codtransp"]] = $row;
			}
		}

		// Verifica se os parceiros estao com CPF/CNPJ informados
		$this->progresso("Validando dados dos parceiros");
		foreach($this->arr_cliente as $cliente){
			if(strlen(trim($cliente["cpfcnpj"])) == 0 && $cliente["codpaisres"] == "01058"){
				$_SESSION["ERROR"] = "Informe o CPF/CNPJ para o cliente <b>".$cliente["codcliente"]."</b> (".$cliente["nome"].").<br><a onclick=\"$.messageBox('close'); openProgram('ClientePF','codclientec=".$cliente["codcliente"]."')\">Clique aqui</a> para abrir o cadastro de clientes.";
				return FALSE;
			}
		}
		foreach($this->arr_estabelecimento as $estabelecimento){
			if(strlen(trim($estabelecimento["cpfcnpj"])) == 0){
				$_SESSION["ERROR"] = "Informe o CPF/CNPJ para o estabelecimento <b>".$estabelecimento["codestabelec"]."</b> (".$estabelecimento["nome"].").<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$estabelecimento["codestabelec"]."')\">Clique aqui</a> para abrir o cadastro de estabelecimentos.";
				return FALSE;
			}
		}
		foreach($this->arr_fornecedor as $fornecedor){
			if(strlen(trim($fornecedor["cpfcnpj"])) == 0){
				$_SESSION["ERROR"] = "Informe o CPF/CNPJ para o fornecedor <b>".$fornecedor["codfornec"]."</b> (".$fornecedor["nome"].").<br><a onclick=\"$.messageBox('close'); openProgram('Fornecedor','codfornec=".$fornecedor["codfornec"]."')\">Clique aqui</a> para abrir o cadastro de fornecedores.";
				return FALSE;
			}
		}
		$texto_fornec = "";
		foreach($this->arr_fornecedor as $fornecedor){
			if(strlen($fornecedor["endereco"]) == 0 || strlen($fornecedor["numero"]) == 0){
				$texto_fornec .= "<br><b>".$fornecedor["codfornec"]." - ".$fornecedor["nome"]." </b>";
			}
		}
		if(strlen($texto_fornec) > 0){
			$_SESSION["ERROR"] = "Os seguintes fornecedores est&atilde;o com o endere&ccedil;o ou n&uacute;mero incompleto <br>".$texto_fornec;
			return FALSE;
		}

		// Busca as cidades
		$this->progresso("Carregando cidades");
		$this->arr_cidade = array();
		$arr_codcidade = array();
		$arr_codcidade[] = $this->estabelecimento->getcodcidade();
		$arr_codcidade[] = $this->contabilidade->getcodcidade();
		foreach($this->arr_cliente as $cliente){
			$arr_codcidade[] = $cliente["codcidaderes"];
		}
		foreach($this->arr_estabelecimento as $estabelecimento){
			$arr_codcidade[] = $estabelecimento["codcidade"];
		}
		foreach($this->arr_fornecedor as $fornecedor){
			$arr_codcidade[] = $fornecedor["codcidade"];
		}
		foreach($this->arr_transportadora as $transportadora){
			$arr_codcidade[] = $transportadora["codcidade"];
		}
		if(count($arr_codcidade) > 0){
			$res = $this->con->query("SELECT * FROM cidade WHERE codcidade IN (".implode(",", array_unique($arr_codcidade)).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_cidade[$row["codcidade"]] = $row;
			}
		}

		// Busca os dados referentes aos itens das notas fiscais
		$this->progresso("Carregando dados tributarios");
		$this->arr_classfiscal = array();
		$this->arr_ipi = array();
		$this->arr_piscofins = array();
		$arr_codcf = array();
		$arr_codipi = array();
		$arr_codpiscofins = array();
		foreach($this->arr_produto as $produto){
			$arr_codcf[] = $produto["codcfnfe"];
			$arr_codcf[] = $produto["codcfnfs"];
			$arr_codcf[] = $produto["codcfpdv"];
			$arr_codipi[] = $produto["codipi"];
			$arr_codpiscofins[] = $produto["codpiscofinsent"];
			$arr_codpiscofins[] = $produto["codpiscofinssai"];
		}
		if(count($arr_codcf) > 0){
			$res = $this->con->query("SELECT * FROM classfiscal WHERE codcf IN (".implode(",", array_unique($arr_codcf)).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_classfiscal[$row["codcf"]] = $row;
			}
		}
		if(count($arr_codipi) > 0){
			$res = $this->con->query("SELECT * FROM ipi WHERE codipi IN (".implode(",", array_unique($arr_codipi)).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_ipi[$row["codipi"]] = $row;
			}
		}
		if(count($arr_codpiscofins) > 0){
			$res = $this->con->query("SELECT * FROM piscofins WHERE codpiscofins IN (".implode(",", array_unique($arr_codpiscofins)).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_piscofins[$row["codpiscofins"]] = $row;
			}
		}

		// Carregando narurezas de operacao
		if(count($this->arr_natoperacao) == 0){
			$res = $this->con->query("SELECT * FROM natoperacao");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_natoperacao[$row["natoperacao"]] = $row;
			}
		}

		// Busca os NCMs
		$this->progresso("Carregando NCMs");
		$this->arr_ncm = array();
		$arr_idncm = array();
		foreach($this->arr_produto as $produto){
			if(strlen($produto["idncm"]) > 0){
				$arr_idncm[] = $produto["idncm"];
			}
		}
		if(count($arr_idncm) > 0){
			$query = "SELECT ncm.*, cest.cest ";
			$query .= "FROM ncm ";
			$query .= "LEFT JOIN cest ON (ncm.idcest = cest.idcest) ";
			$query .= "WHERE idncm IN (".implode(",", array_unique($arr_idncm)).") ";
			$res = $this->con->query($query);

			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_ncm[$row["idncm"]] = $row;
			}
		}

		// Busca as embalagens
		$this->progresso("Carregando embalagens");
		$this->arr_embalagem = array();
		$res = $this->con->query("SELECT * FROM embalagem");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_embalagem[$row["codembal"]] = $row;
		}

		// Busca as unidades
		$this->progresso("Carregando unidades");
		$this->arr_unidade = array();
		$res = $this->con->query("SELECT * FROM unidade");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_unidade[$row["codunidade"]] = $row;
		}

		// Busca os codigos de barra
		$this->progresso("Carregando codigos de barra");
		$this->arr_produtoean = array();
		if(count($arr_codproduto) > 0){
			$res = $this->con->query("SELECT * FROM produtoean WHERE codproduto IN (".implode(",", $arr_codproduto).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_produtoean[$row["codproduto"]][] = $row;
			}
		}

		// Busca as naturezas de receita
		$this->progresso("Carregando naturezas de receita");
		$this->arr_natreceita = array();
		$res = $this->con->query("SELECT * FROM natreceita");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_natreceita[$row["tabela"].$row["codigo"]] = $row["natreceita"];
		}

		// Verifica se deve zerar o IPI
		foreach($this->arr_notafiscal as $i => $notafiscal){
			$totalipi = 0;
			foreach($notafiscal["itnotafiscal"] as $j => $itnotafiscal){
				$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
				if($natoperacao["geraspedipi"] === "N"){
					$itnotafiscal["valipi"] = 0;
					$itnotafiscal["percipi"] = 0;
					$itnotafiscal["totalipi"] = 0;
					$notafiscal["itnotafiscal"][$j] = $itnotafiscal;
				}
				$totalipi += $itnotafiscal["totalipi"];
			}
			$notafiscal["totalipi"] = $totalipi;
			$this->arr_notafiscal[$i] = $notafiscal;
		}

		// Verifica as notas que vieram a partir de cupom
		$this->progresso("Verificando notas a partir de cupom");
		foreach($this->arr_notafiscal as $i => $notafiscal){
			if(strlen($notafiscal["cupom"]) > 0){
				foreach($notafiscal["itnotafiscal"] as $j => $itnotafiscal){
					if(in_array($itnotafiscal["tptribicms"], array("T", "R"))){
						$itnotafiscal["tptribicms"] = "N";
						$itnotafiscal["aliqicms"] = 0;
						$itnotafiscal["redicms"] = 0;
						$itnotafiscal["totalbaseicms"] = 0;
						$itnotafiscal["totalicms"] = 0;
						$this->arr_notafiscal[$i]["itnotafiscal"][$j] = $itnotafiscal;
					}
				}
			}
		}

		// Recalcula bases de ICMS
		$this->progresso("Finalizando calculos para geracao");
		foreach($this->arr_notafiscal as $notafiscal){
			$totalbaseicms = 0;
			$totalicms = 0;
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				if($itnotafiscal["tptribicms"] == "F"){
					$itnotafiscal["totalbaseicms"] = 0;
					$itnotafiscal["totalicms"] = 0;
				}
				$totalbaseicms = $itnotafiscal["totalbaseicms"];
				$totalicms = $itnotafiscal["totalicms"];
			}
			$notafiscal["totalbaseicms"] = $totalbaseicms;
			$notafiscal["totalicms"] = $totalicms;
		}

		// Verifica se existe nota fiscal e/ou cupom fiscal no periodo informado
		if($this->gerar_tudozerado == FALSE && $this->gerar_inventario == FALSE && count($this->arr_notafiscal) == 0 && count($this->arr_maparesumo) == 0 && count($this->arr_cupom) == 0 && count($this->arr_notadiversa) == 0){
			$_SESSION["ERROR"] = "N&atilde;o foi encontrado movimento no per&iacute;odo informado.";
			return FALSE;
		}

		// Limpa as variaveis para liberar mais memoria
		unset($arr_codcf);
		unset($arr_codcidade);
		unset($arr_codcliente);
		unset($arr_codecf);
		unset($arr_codestabelec);
		unset($arr_codfornec);
		unset($arr_codipi);
		unset($arr_codmaparesumo);
		unset($arr_codpiscofins);
		unset($arr_codproduto);
		unset($arr_codtransp);

		// Inicia a criacao de cada bloco
		$this->arquivo_nome = $this->estabelecimento->getdirarqfiscal()."SPEDFiscal_".str_pad($this->estabelecimento->getcodestabelec(), 4, "0", STR_PAD_LEFT)."_".substr($this->datainicial, 0, 4)."_".substr($this->datainicial, 5, 2).".txt";
		$this->arquivo = fopen($this->arquivo_nome, "w+");
		$this->progresso("Gerando bloco 0");
		$this->bloco_0();
		$this->progresso("Gerando bloco C");
		$this->bloco_C();
		$this->progresso("Gerando bloco D");
		$this->bloco_D();
		$this->progresso("Gerando bloco E");
		$this->bloco_E();
		$this->progresso("Gerando bloco G");
		$this->bloco_G();
		$this->progresso("Gerando bloco H");
		$this->bloco_H();
		if(compare_date($this->datainicial, "2016-01-01", "Y-m-d", ">=")){
			$this->progresso("Gerando bloco K");
			$this->bloco_K();
		}
		$this->progresso("Gerando bloco 1");
		$this->bloco_1();
		$this->progresso("Gerando bloco 9");
		$this->bloco_9();
		fclose($this->arquivo);

		if(param("SISTEMA", "TIPOSERVIDOR", $this->con) == "1"){
			$this->download();
		}

		return TRUE;
	}

	public function gerar_alteracaotributaria($b, $dataalteracaotributariaini = NULL, $dataalteracaotributariafin = NULL){
		if(is_bool($b)){
			$this->gerar_alteracaotributaria = $b;
			$this->dataalteracaotributariaini = (!is_null($dataalteracaotributariaini) ? $dataalteracaotributariaini : value_date($this->datainicial));
			$this->dataalteracaotributariafin = (!is_null($dataalteracaotributariafin) ? $dataalteracaotributariafin : value_date($this->datafinal));
		}
	}

	public function gerar_cupom($b){
		if(is_bool($b)){
			$this->gerar_cupom = $b;
		}
	}

	public function gerar_inventario($b){
		if(is_bool($b)){
			$this->gerar_inventario = $b;
		}
	}

	public function gerar_notafiscal($b){
		if(is_bool($b)){
			$this->gerar_notafiscal = $b;
		}
	}

	public function gerar_tudozerado($b){
		if(is_bool($b)){
			$this->gerar_tudozerado = $b;
		}
	}

	protected function preparar_C190($notafiscal){
		$arr = array();

		// Verifica se e uma nota fiscal de complemento
		if(strlen($notafiscal["idnotacomplemento"]) > 0){
			$notacomplemento = $notafiscal;

			$codcst = "090";
			$natoperacao = $this->valor_natoperacao($notacomplemento["natoperacao"]);
			$aliqicms = 0;

			$arr[$codcst][$natoperacao][$aliqicms] = array(
				"totalliquido" => $notacomplemento["totalliquido"],
				"totalbaseicms" => $notacomplemento["totalbaseicms"],
				"totalicms" => $notacomplemento["totalicms"],
				"totalbaseicmssubst" => $notacomplemento["totalbaseicmssubst"],
				"totalicmssubst" => $notacomplemento["totalicmssubst"]
			);

			// Se for uma nota fiscal normal
		}else{
			if(in_array($this->modelo($notafiscal), array("01", "1B", "04", "55", "65"))){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$codcst = $this->csticms($itnotafiscal);
					$orig = substr($codcst, 0, 1);

					$base_icms = 0;
					$valor_icms = 0;
					$aliq_icms = 0;
					$red_icms = 0;

					$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
					if(in_array(substr($codcst, 1, 2), array("00", "20")) || $natoperacao["aprovicmsprop"] == "S"){
						$base_icms = $itnotafiscal["totalbaseicms"];
						$valor_icms = $itnotafiscal["totalicms"];
						$aliq_icms = $itnotafiscal["aliqicms"];
						$red_icms = $itnotafiscal["totalbaseisento"];
					}elseif(in_array(substr($codcst, 1, 2), array("70"))){
						$red_icms = $itnotafiscal["totalbruto"];
					}

					$natoperacao = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
					$aliqicms = $this->valor_decimal($aliq_icms, 2);
					if(!in_array(substr($codcst, 1, 2), array("70", "60", "10"))){
						$arr[$codcst][$natoperacao][$aliqicms]["totalliquido"] += $itnotafiscal["totalliquido"];
						$arr[$codcst][$natoperacao][$aliqicms]["totalbaseicms"] += $base_icms;
						$arr[$codcst][$natoperacao][$aliqicms]["totalicms"] += $valor_icms;
						$arr[$codcst][$natoperacao][$aliqicms]["totalbaseicmssubst"] += (in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalbaseicmssubst"]);
						$arr[$codcst][$natoperacao][$aliqicms]["totalicmssubst"] += (in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalicmssubst"]);
						$arr[$codcst][$natoperacao][$aliqicms]["totalredicms"] += $red_icms;
						$arr[$codcst][$natoperacao][$aliqicms]["totalipi"] += (in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalipi"]);
					}else{
						$arr[$orig."60"][$natoperacao][$aliqicms]["totalliquido"] += $itnotafiscal["totalliquido"];
						$arr[$orig."60"][$natoperacao][$aliqicms]["totalbaseicms"] += $base_icms;
						$arr[$orig."60"][$natoperacao][$aliqicms]["totalicms"] += $valor_icms;
						$arr[$orig."60"][$natoperacao][$aliqicms]["totalbaseicmssubst"] += (in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalbaseicmssubst"]);
						$arr[$orig."60"][$natoperacao][$aliqicms]["totalicmssubst"] += (in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalicmssubst"]);
						$arr[$orig."60"][$natoperacao][$aliqicms]["totalredicms"] += $red_icms;
						$arr[$orig."60"][$natoperacao][$aliqicms]["totalipi"] += (in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalipi"]);
					}

					$arr_txt = array();
					if(in_array($notafiscal["operacao"], array("CP"))){

						if($notafiscal["totalbaseicmssubst"] > 0){
							$arr_txt[] = "Total de base de calculo de ICMS ST R$ ".number_format($notafiscal["totalbaseicmssubst"], 2, ",", ".").".";
						}
						if($notafiscal["totalicmssubst"] > 0){
							$arr_txt[] = "Total de ICMS ST R$ ".number_format($notafiscal["totalicmssubst"], 2, ",", ".").".";
						}
						if($notafiscal["totalipi"] > 0){
							$arr_txt[] = "Total de IPI R$ ".number_format($notafiscal["totalipi"], 2, ",", ".").".";
						}
					}
					if(in_array($notafiscal["operacao"], array("VD")) && in_array($notafiscal["natoperacao"], array("5.929", "6.929"))){
						if(strlen($notafiscal["observacaofiscal"]) > 0){
							$arr_txt[] = str_replace(array("\r", "\n"), "", $notafiscal["observacaofiscal"]);
						}
					}
				}
				if(sizeof($arr_txt) > 0){
					array_merge(array_unique($arr_txt));
					if(!in_array(substr($codcst, 1, 2), array("70", "60", "10"))){
						$arr[$codcst][$natoperacao][$aliqicms]["cod_obs"] = (count($arr_txt) > 0 ? $this->r0460_cod_obs++ : "");
						$arr[$codcst][$natoperacao][$aliqicms]["txt"] = implode(" ", $arr_txt);
					}else{
						$arr[$orig."60"][$natoperacao][$aliqicms]["cod_obs"] = (count($arr_txt) > 0 ? $this->r0460_cod_obs++ : "");
						$arr[$orig."60"][$natoperacao][$aliqicms]["txt"] = implode(" ", $arr_txt);
					}
				}
			}
		}
		return $arr;
	}

	// Abertura, idetificacao e referencias
	protected function bloco_0(){
		$this->registro_0000();
		$this->registro_0990();
	}

	// Documentos fiscais I - Mercadorias (ICMS/IPI)
	protected function bloco_C(){
		$this->registro_C001();
		$this->registro_C990();
	}

	// Documentos fiscais II - Servicos (ICMS)
	protected function bloco_D(){
		$this->registro_D001();
		$this->registro_D990();
	}

	// Apuracao do ICMS e do IPI
	protected function bloco_E(){
		$this->registro_E001();
		$this->registro_E990();
	}

	// Controle do credito de ICMS de ativo permanente - CIAP
	protected function bloco_G(){
		$this->registro_G001();
		$this->registro_G990();
	}

	// Inventario fisico
	protected function bloco_H(){
		$this->registro_H001();
		$this->registro_H990();
	}

	// Movimentacao de estoque
	protected function bloco_K(){
		$this->registro_K001();
		$this->registro_K990();
	}

	// Outras informacoes
	protected function bloco_1(){
		$this->registro_1001();
		$this->registro_1990();
	}

	// Controle de encerramento do arquivo digital
	protected function bloco_9(){
		$this->registro_9001();
		$this->registro_9900();
		$this->registro_9990();
		$this->registro_9999();
	}

	// Abertura do arquivo digital e identificacao da entidade [nivel 0]
	protected function registro_0000(){
		$cidade = $this->arr_cidade[$this->estabelecimento->getcodcidade()];

		// Calcula o codigo da versao
		$anoinicial = substr($this->datainicial, 0, 4);
		$cod_ver = str_pad(($anoinicial - 2006), 3, "0", STR_PAD_LEFT);

		$registro = array(
			// Texto fixo contento "0000"
			"REG" => "0000",
			// Codigo da versao do layout conforme a tabela indicada no Ato COTEPE
			"COD_VER" => $cod_ver,
			// Codigo da finalidade do arquivo
			// 0 - Remessa do arquivo original
			// 1 - Remessa do arquivo substituto
			"COD_FIN" => $this->tipoescrituracao,
			// Data inicial das informacoes contidas no arquivo
			"DT_INI" => $this->valor_data($this->datainicial),
			// Data final das informacoes contidas no arquivo
			"DT_FIN" => $this->valor_data($this->datafinal),
			// Nome empresarial da entidade
			"NOME" => $this->estabelecimento->getrazaosocial(),
			// Numero de inscricao da entidade no CNPJ
			"CNPJ" => removeformat($this->estabelecimento->getcpfcnpj()),
			// Numero de inscricao da entidade no CPF
			"CPF" => "",
			// Sigla da unidade da federacao da entidade
			"UF" => $cidade["uf"],
			// Inscricao estadual da entidade
			"IE" => removeformat($this->estabelecimento->getrgie()),
			// Codigo do municipio do domicilio fiscal da entidade, conforme a tabela do IBGE
			"COD_MUN" => $cidade["codoficial"],
			// Inscricao municipal da entidade
			"IM" => "",
			// Inscricao da entidade no SUFRAMA
			"SUFRAMA" => "",
			// Perfil de apresentacao fiscal
			// A - Perfil A
			// B - Perfil B
			// C - Perfil C
			"IND_PERFIL" => $this->estabelecimento->getperfil(),
			// Indicado de tipo de atividade
			// 0 - Industrial ou equiparado a industrial
			// 1 - Outros
			"IND_ATIV" => "1"
		);
		$this->escrever_registro($registro);
		$this->registro_0001();
	}

	// Abertura do bloco 0 [nivel 1]
	protected function registro_0001(){
		$registro = array(
			// Texto fico contento "0001"
			"REG" => "0001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "0"
		);
		$this->escrever_registro($registro);
		$this->registro_0005();
		$this->registro_0015();
		$this->registro_0100();
		$this->registro_0150();
		$this->registro_0190();
		$this->registro_0200();
		$this->registro_0300();
		if($this->gerar_0400){
			$this->registro_0400();
		}
		$this->registro_0450();
		$this->registro_0460();
		$this->registro_0500();
		$this->registro_0600();
	}

	// Dados complementares da entidade [nivel 2]
	protected function registro_0005(){
		$registro = array(
			// Texto fixo contendo "0005"
			"REG" => "0005",
			// Nome fantasia associado ao nome empresarial
			"FANTASIA" => $this->estabelecimento->getnome(),
			// Codigo de endereco postal
			"CEP" => removeformat($this->estabelecimento->getcep()),
			// Logradouro e endereco do imovel
			"END" => $this->estabelecimento->getendereco(),
			// Numero do imovel
			"NUM" => $this->estabelecimento->getnumero(),
			// Dados complementares do endereco
			"COMPL" => $this->estabelecimento->getcomplemento(),
			// Bairro em que o imovel esta situado
			"BAIRRO" => $this->estabelecimento->getbairro(),
			// Numero do telefone
			"FONE" => $this->valor_telefone($this->estabelecimento->getfone1()),
			// Numero do fax
			"FAX" => $this->valor_telefone($this->estabelecimento->getfax()),
			// Endereco do correio eletronico
			"EMAIL" => $this->estabelecimento->getemail()
		);
		$this->escrever_registro($registro);
	}

	// Dados do contribuinte substituto [nivel 2] [NAO GERAR]
	protected function registro_0015(){
		return array();
	}

	// Dados do contabilista [nivel 2]
	protected function registro_0100(){
		$cidade = $this->arr_cidade[$this->contabilidade->getcodcidade()];
		$registro = array(
			// Texto fixo contento "0100"
			"REG" => "0100",
			// Nome do contabilista
			"NOME" => $this->contabilidade->getnomecontador(),
			// Numero de inscricao do contabilista no CPF
			"CPF" => removeformat($this->contabilidade->getcpfcontador()),
			// Numero de inscricao do contabilista no Conselho Reginal de Contabilidade
			"CRC" => removeformat($this->contabilidade->getcrccontador()),
			// Numero de inscricao do escritorio do contabilidade no CNPJ
			"CNPJ" => removeformat($this->contabilidade->getcpfcnpj()),
			// Codigo do endereco postal
			"CEP" => removeformat($this->contabilidade->getcep()),
			// Logradouro e endereco do imovel
			"END" => $this->contabilidade->getendereco(),
			// Numero do imovel
			"NUM" => $this->contabilidade->getnumero(),
			// Dados complementares do endereco
			"COMPL" => $this->contabilidade->getcomplemento(),
			// Bairro em que o imovel enta situado
			"BAIRRO" => $this->contabilidade->getbairro(),
			// Numero do telefone
			"FONE" => $this->valor_telefone($this->contabilidade->gettelefone()),
			// Numero do fax
			"FAX" => $this->valor_telefone($this->contabilidade->getfax()),
			// Endereco do correio eletronico
			"EMAIL" => $this->contabilidade->getemail(),
			// Codigo do municipio, conforme a tabela IBGE
			"COD_MUN" => $cidade["codoficial"]
		);
		$this->escrever_registro($registro);
	}

	// Tabela de cadastro do participante [nivel 2]
	protected function registro_0150(){
		$arr_codparceiro = array();
		foreach($this->arr_notafiscal as $notafiscal){
			if($notafiscal["operacao"] == "NC"){
				continue;
			}

			$modelo = $this->modelo($notafiscal);
			if(!in_array($notafiscal["status"], array("C", "I", "D")) && !in_array($modelo, array("65"))){
				$operacao = $this->arr_operacaonota[$notafiscal["operacao"]];
				$arr_codparceiro[] = $operacao["parceiro"].$notafiscal["codparceiro"];
				if(strlen($notafiscal["codtransp"]) > 0){
//					$arr_codparceiro[] = "T".$notafiscal["codtransp"];
				}
			}
		}
		foreach($this->arr_notacomplemento as $notacomplemento){
			$notafiscal = $notacomplemento["notafiscal"];
			$operacao = $this->arr_operacaonota[$notafiscal["operacao"]];
			$arr_codparceiro[] = $operacao["parceiro"].$notafiscal["codparceiro"];
		}
		foreach($this->arr_notadiversa as $notadiversa){
			if(in_array($notadiversa["tipodocumentofiscal"], array("22", "06", "07", "21", "28", "29"))){
				$arr_codparceiro[] = $notadiversa["tipoparceiro"].$notadiversa["codparceiro"];
			}
		}
		foreach($this->arr_notafrete as $notafrete){
			$arr_codparceiro[] = "T".$notafrete["codtransp"];
		}
		$arr_codparceiro = array_unique($arr_codparceiro);
		foreach($arr_codparceiro as $codparceiro){
			switch(substr($codparceiro, 0, 1)){ // Verifica qual o parceiro da nota
				case "C": // Busca dados do cliente
					$cliente = $this->arr_cliente[substr($codparceiro, 1)];
					$cidade = $cliente["ufres"] == "EX" ? "" : $this->arr_cidade[$cliente["codcidaderes"]];
					$codpais = substr($cliente["codpaisres"], -4);
					$nome = $cliente["nome"];
					$endereco = $cliente["enderres"];
					$numero = $cliente["numerores"];
					$complemento = $cliente["complementores"];
					$bairro = $cliente["bairrores"];
					if($cliente["tppessoa"] == "F"){
						$cpf = $cliente["cpfcnpj"];
						$cnpj = "";
						$ie = "";
					}else{
						$cpf = "";
						$cnpj = $cliente["cpfcnpj"];
						$ie = $cliente["rgie"];
					}
					break;
				case "E":
					$estabelecimento = $this->arr_estabelecimento[substr($codparceiro, 1)];
					$cidade = $this->arr_cidade[$estabelecimento["codcidade"]];
					$codpais = "1058"; // Brasil
					$nome = $estabelecimento["nome"];
					$endereco = $estabelecimento["endereco"];
					$numero = $estabelecimento["numero"];
					$complemento = $estabelecimento["complemento"];
					$bairro = $estabelecimento["bairro"];
					$cpf = "";
					$cnpj = $estabelecimento["cpfcnpj"];
					$ie = $estabelecimento["rgie"];
					break;
				case "F": // Busca dados do fornecedor
					$fornecedor = $this->arr_fornecedor[substr($codparceiro, 1)];
					$cidade = $this->arr_cidade[$fornecedor["codcidade"]];
					$codpais = substr($fornecedor["codpais"], -4);
					$nome = $fornecedor["razaosocial"];
					$endereco = $fornecedor["endereco"];
					$numero = $fornecedor["numero"];
					$complemento = $fornecedor["complemento"];
					$bairro = $fornecedor["bairro"];
					if($fornecedor["tppessoa"] == "F"){
						$rg = $fornecedor["rgie"];
						$cpf = $fornecedor["cpfcnpj"];
						$ie = "";
						$cnpj = "";
					}else{
						$rg = "";
						$cpf = "";
						$ie = $fornecedor["rgie"];
						$cnpj = $fornecedor["cpfcnpj"];
					}
					break;
				case "T": // Busca transportadora
					$transportadora = $this->arr_transportadora[substr($codparceiro, 1)];
					$cidade = $this->arr_cidade[$transportadora["codcidade"]];
					$codpais = "1058"; // Brasil
					$nome = $transportadora["nome"];
					$endereco = $transportadora["endereco"];
					$numero = $transportadora["numero"];
					$complemento = $transportadora["complemento"];
					$bairro = $transportadora["bairro"];
					$cpf = "";
					$cnpj = $transportadora["cpfcnpj"];
					$ie = $transportadora["rgie"];
					break;
			}
			if(strlen($codparceiro) > 1){
				if(strtolower($ie) === "isento"){
					$ie = "";
				}
				$registro = array(
					// Texto fixo contento "0150"
					"REG" => "0150",
					// Codigo de identificacao do participante no arquivo
					"COD_PART" => $codparceiro,
					// Nome pessoal ou empresarial do participante
					"NOME" => $nome,
					// Codigo do pais do participante, conforme a tabela 3.2.1
					"COD_PAIS" => $codpais,
					// CNPJ do participante
					"CNPJ" => substr(removeformat($cnpj), -14),
					// CPF do participante
					"CPF" => removeformat($cpf),
					// Inscricao estadual do participante
					"IE" => removeformat($ie),
					// Codigo do municipio, conforme a tabela IBGE
					"COD_MUN" => $cidade["codoficial"],
					// Numero de inscricao do participante na SUFRAMA
					"SUFRAMA" => "",
					// Logradouro e endereco do imovel
					"END" => $endereco,
					// Numero do imovel
					"NUM" => $numero,
					// Dados complementares do endereco
					"COMPL" => $complemento,
					// Bairro em que o imovel esta situado
					"BAIRO" => $bairro
				);
				$this->escrever_registro($registro);
			}
		}
	}

	// Alteracao da tabela de cadastro de participante [nivel 3] [NAO GERAR]
	protected function registro_0175(){
		return array();
	}

	// Identificacao das unidades de medida [nivel 2]
	protected function registro_0190(){
		// Busca as unidades usadas nas notas fiscais
		$arr_codunidade = array();

		foreach($this->arr_produto as $produto){
			$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
			$arr_codunidade[] = $embalagem["codunidade"];
		}
		foreach($this->arr_notafiscal as $notafiscal){
			if(in_array($notafiscal["operacao"], array("CP", "TE", "PR", "NC"))){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$arr_codunidade[] = $itnotafiscal["codunidade"];
				}
			}
		}
		$arr_codunidade = array_unique($arr_codunidade);
		foreach($arr_codunidade as $codunidade){
			$unidade = $this->arr_unidade[$codunidade];
			$registro = array(
				// Texto fixo contento "0190"
				"REG" => "0190",
				// Codigo da unidade de medida
				"UNID" => $unidade["sigla"],
				// Descricao da unidade de medida
				"DESCR" => $unidade["descricao"]
			);
			$this->escrever_registro($registro);
		}
	}

	// Tabela de identificacao do item (produto e servicos) [nivel 2]
	protected function registro_0200(){
		foreach($this->arr_produto as $produto){
			$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
			$unidade = $this->arr_unidade[$embalagem["codunidade"]];
			$classfiscal = $this->arr_classfiscal[$produto["codcfnfe"]];
			$piscofinsent = $this->arr_piscofins[$produto["codpiscofinsent"]];
			$piscofinssai = $this->arr_piscofins[$produto["codpiscofinssai"]];
			$ipi = $this->arr_ipi[$produto["codipi"]];
			if(strlen($produto["idncm"]) > 0){
				$codigoncm = $this->arr_ncm[$produto["idncm"]]["codigoncm"];
				$cest = removeformat($this->arr_ncm[$produto["idncm"]]["cest"]);
			}else{
				$codigoncm = NULL;
				$cest = NULL;
			}
			$codean = NULL;
			$arr_produtoean = $this->arr_produtoean[$produto["codproduto"]];
			foreach($arr_produtoean as $produtoean){
				if(ltrim($produtoean["codean"], "0") > 7){
					$codean = $produtoean["codean"];
					break;
				}
			}
			$registro = array(
				// Texto fixo contendo "0200"
				"REG" => "0200",
				// Codigo do item
				"COD_ITEM" => $produto["codproduto"],
				// Descricao do item
				"DESCR_ITEM" => $produto["descricaofiscal"],
				// Representacao alfanumerico do codigo de barra do produto, se houve
				"COD_BARRA" => $codean,
				//Codigo anterior do item com relacao a ultima informacao apresentada
				"COD_ANT_ITEM" => "",
				// Unidade de medida utilizada na quantificacao de estoques
				"UNID_INV" => $unidade["sigla"],
				// Tipo do item - Atividades industriais, comerciais e servicos:
				// 00 - Mercadoria para revenda
				// 01 - Materia-prima
				// 02 - Embalagem
				// 03 - Produto em processo
				// 04 - Produto acabado
				// 05 - Subproduto
				// 06 - Produto intermediario
				// 07 - Material de uso e consumo
				// 08 - Ativo imobilizado
				// 09 - Servicos
				// 10 - Outros insumos
				// 99 - Outras
				"TIPO_ITEM" => "00",
				// Codigo da nomeclatura comum do Mercosul
				"COD_NCM" => ($this->param_fiscal_spedenviarncm == "S" ? removeformat($codigoncm) : ""),
				// Codigo EX, conforme a TIPI
				"EX_IPI" => "",
				// Codigo do genero do item, conforme a tabela 4.2.1
				"COD_GEN" => "",
				// Codigo do servico conforme lista do anexo I da lei complementar federal n 116/03
				"COD_LST" => "",
				// Aliquota de ICMS aplicavel ao item nas operacoes internas
				"ALIQ_ICMS" => $this->valor_decimal($classfiscal["aliqicms"], 2)
			);
//			if(compare_date($this->datainicial, "2017-01-01", "Y-m-d", ">=") && !$this->gerar_contmatic){
//				$registro = array_merge($registro, array(
//					// Codigo especificador da substituicao tributaria
//					"CEST" => $cest
//				));
//			}
			if($this->gerar_contmatic){
				$registro = array_merge($registro, array(
					// Codigo do grupo da mercadoria para o inventario
					"COD_GRUPO" => "",
					// Descricao do grupo da mercadoria para o inventario
					"DESC_GRUPO" => "",
					// Codigo da mercadoria para a SEFAZ de Sao Paulo para geracao do arquivo GRF-CBT
					"COD_SEFAZ" => "",
					// Codigo da situacao da operacao no simples nacional
					"CSOSN" => "",
					// Codigo da situacao tributaria do ICMS
					"CST_ICMS" => $classfiscal["codcst"],
					// Percentual de reducao de base de calculo para ICMS
					"PER_RED_BC_ICMS" => $this->valor_decimal($classfiscal["aliqredicms"], 2),
					// Valor unitario de base de calculo do ICMS ST (valor fixado pela SEFAZ)
					"BC_ICMS_ST" => $this->valor_decimal($classfiscal["valorpauta"], 2),
					// Codigo da situacao tributaria do IPI nas entradas
					"CST_IPI_ENTRADA" => $ipi["codcst"],
					// Codigo da situacao tributaria do IPI nas saidas
					"CST_IPI_SAIDA" => "",
					// Aliquota do IPI para o item
					"ALIQ_IPI" => $this->valor_decimal($ipi["aliqipi"], 2),
					// Codigo da situacao tributaria do PIS nas entradas
					"CST_PIS_ENTRADA" => $piscofinsent["codcst"],
					// Codigo da situacao tributaria do PIS nas saidas
					"CST_PIS_SAIDA" => $piscofinssai["codcst"],
					// Codigo da natureza das receitas isentas, sem incidencia, com suspensao ou tributadas a aliquota zero para o PIS (para atender ao EFD PIS/Cofins)
					"NAT_REC_PIS" => $this->natreceita($produto),
					// Aliquota do PIS para o item
					"ALIQ_PIS" => $this->valor_decimal($piscofinsent["aliqpis"], 2),
					// Codigo da situacao tributaria do COFINS nas entradas
					"CST_COFINS_ENTRADA" => $piscofinsent["codcst"],
					// Codigo da situacao tributaria do COFINS nas saidas
					"CST_COFINS_SAIDA" => $piscofinssai["codcst"],
					// Codigo da natureza das receitas isentas, sem incidencia, com suspensao ou tributadas a aliquota zero para o PIS (para atender ao EFD PIS/Cofins)
					"NAT_REC_COFINS" => $this->natreceita($produto),
					// Aliquota do COFINS para o item
					"ALIQ_COFINS" => $this->valor_decimal($piscofinsent["aliqcofins"], 2),
					// Aliquota do ISS para o servico
					"ALIQ_ISS" => "",
					// Conta contabil do item
					"CC" => "",
					// Observacao referente ao item
					"OBSERVACAO" => ""
					// CEST
//					"CEST" => $cest
				));
			}
			$this->escrever_registro($registro);
			$this->registro_0205($produto);
			$this->registro_0206();
			$this->registro_0220($embalagem);
		}
	}

	// Alteracao do item [nivel 3]
	protected function registro_0205($produto){
		if(strlen($produto["codprodutoant"]) > 0 && $this->gerar_0205){
			$dataini = convert_date($this->datainicial, "Y-m-d", "dmY");
			$datafim = date('dmY', strtotime("-1 days", strtotime($dataini)));

			$registro = array(
				//	Texto fixo contendo "0205"
				"REG" => "0205",
				// Descrição anterior do item
				"DESCR_ANT_ITEM" => "",
				// Data inicial de utilização da descrição do item
				"DT_INI" => $dataini,
				// Data final de utilização da descrição do item
				"DT_FIM" => $dataini, //31 março
				// Código anterior do item com relação à última informação apresentada
				"COD_ANT_ITEM" => $produto["codprodutoant"]
			);
			$this->escrever_registro($registro);
		}
	}

	// Codigo de produto conforme a tabela publicada pela ANP (combustivel) [nivel 3] [NAO GERAR]
	protected function registro_0206(){
		return array();
	}

	// Fatores de conversao de unidades [nivel 3]
	protected function registro_0220($embalagem){
		$unidade = $this->arr_unidade[$embalagem["codunidade"]];
		$registro = array(
			//	Texto fixo contendo "0220"
			"REG" => "0220",
			// Unidade comercial a ser convertida na unidade de estoque referida no registro 0200
			"UNID_CONV" => $unidade["sigla"],
			// Fator de conversao: fator utilizado para converter (multiplicar) a unidade a ser convertida na unidade adotada no inventario
			"FAT_CONV" => $this->valor_decimal($embalagem["quantidade"], 6)
		);
		$this->escrever_registro($registro);
	}

	// cadastro de bens ou componentes do ativo imobilizado [nivel 2] [NAO GERAR]
	protected function registro_0300(){
		$this->registro_0305();
	}

	// Informacao sobre a utilizacao do bem [nivel 3] [NAO GERAR]
	protected function registro_0305(){
		return array();
	}

	// Tabela de natureza de operacao/prestacao [nivel 2] [NAO GERAR]
	protected function registro_0400(){
		$arr_natoperacao = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$arr_natoperacao[] = substr($notafiscal["natoperacao"],0,5);
		}
		$arr_natoperacao = array_unique($arr_natoperacao);

		$_arr_natoperacao = array();
		foreach($arr_natoperacao as $natoperacao){
			$_arr_natoperacao[] = $this->arr_natoperacao[$natoperacao];
		}

		foreach($_arr_natoperacao as $natoperacao){
			$registro = array(
				//	Texto fixo contendo "0400"
				"REG" => "0400",
				// Código da natureza da operação/prestação
				"COD_NAT" => removeformat($natoperacao["natoperacao"]),
				// Descrição da natureza da operação/prestação
				"DESCR_NAT" => $natoperacao["descricao"]
			);
			$this->escrever_registro($registro);
		}
	}

	// Tabela de informacao complementar do documento fiscal [nivel 2] [NAO GERAR]
	protected function registro_0450(){
		return array();
	}

	// Tabela de observacoes de lancamento fiscal [nivel 2]
	protected function registro_0460(){
		$this->r0460_cod_obs = 1;
		foreach($this->arr_notafiscal as $i => $notafiscal){
			if(in_array($notafiscal["operacao"], array("CP")) || ($notafiscal["operacao"] == "VD" && in_array($notafiscal["natoperacao"], array("5.929", "6.929")))){
				if(strlen($notafiscal["observacaofiscal"]) == 0 && ($notafiscal["operacao"] == "VD" && in_array($notafiscal["natoperacao"], array("5.929", "6.929")))){
					continue;
				}
				$arr = $arr = $this->preparar_C190($notafiscal);
				foreach($arr as $arr2){
					foreach($arr2 as $arr3){
						foreach($arr3 as $arr4){
							if(strlen($arr4["txt"]) > 0){
								$registro = array(
									// Texto fixo contendo "0460"
									"REG" => "0460",
									// Codigo da observacao do lancamento fiscal
									"COD_OBS" => $arr4["cod_obs"],
									// Descricao da observacao vinculada ao lancamento fiscal
									"TXT" => trim(substr($arr4["txt"], 0, 200))
								);
								$this->arr_c195[] = $arr4["cod_obs"];
								$this->escrever_registro($registro);
							}
						}
					}
				}
			}
		}
	}

	// Plano de contas contabeis [nivel 2] [NAO GERAR]
	protected function registro_0500(){
		/* 		$registro = array(
		  // Texto fixo contendo "0500"
		  "REG" => "0500",
		  // Data da inclusao/alteracao
		  "DT_ALT" => $this->valor_data($this->datainicial),
		  // Codigo da natureza da conta/grupo de contas:
		  // 01 - Contas de ativo
		  // 02 - Contas de passivo
		  // 03 - Patrimonio liquido
		  // 04 - Contas de resultado
		  // 05 - Contas de compensacao
		  // 09 - Outras
		  "COD_NAT_CC" => "01",
		  // Indicador do tipo de conta:
		  // S - Sintetica (grupo de contas)
		  // A - Analitica (conta)
		  "IND_CTA" => "A",
		  // Nivel da conta analitica/grupo de contas
		  "NIVEL" => "1",
		  // Codigo da conta analitica/grupo de contas
		  "COD_CTA" => $this->planocontas->getnumconta(),
		  // Nome da conta analitica/grupo de contas
		  "NOME_CTA" => $this->planocontas->getnome()
		  );
		  $this->escrever_registro($registros);
		 */
	}

	// Centro de custos [nivel 2] [NAO GERAR]
	protected function registro_0600(){
		return array();
	}

	// Encerramento do bloco 0 [nivel 1]
	protected function registro_0990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "0"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "0990"
			"REG" => "0990",
			// Quantidade de linhas do bloco 0
			"QTD_LIN_0" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco C [nivel 1]
	protected function registro_C001(){
		if(count($this->arr_cupom) > 0 || count($this->arr_notafiscal) > 0 || count($this->arr_notadiversa) > 0){
			$ind_mov = "0";
		}else{
			$ind_mov = "1";
		}

		$registro = array(
			// Texto fixo contento "C001"
			"REG" => "C001",
			// Indicador de movimento:
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => $ind_mov
		);
		$this->escrever_registro($registro);
		$this->registro_C100();
		$this->registro_C300();
		$this->registro_C350();
		$this->registro_C400();
		$this->registro_C495();
		$this->registro_C500();
		$this->registro_C600();
		$this->registro_C700();
		$this->registro_C800();
		$this->registro_C860();
	}

	// Nota fiscal (codigo 01), nota fiscal avulsa (codigo 1B), nota fiscal de produto (codigo 04) e NF-e (codigo 55) [nivel 2]
	protected function registro_C100(){
		$this->r0460_cod_obs = 1;
		$param_notafiscal_gerarlanctonfe = param("NOTAFISCAL", "GERARLANCTONFE", $this->con);

		// Gera as notas fiscais
		foreach($this->arr_notafiscal as $notafiscal){
			$modelo = $this->modelo($notafiscal);

			// Verifica se precisa gerar o registro
			if(!in_array($modelo, array("01", "1B", "04", "55", "65"))){
				continue;
			}

			$ind_emit = ($notafiscal["emissaopropria"] == "N" ? "1" : "0");

			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			// Verifica qual o indicador de pagamento
			$arr_lancamento = $this->arr_lancamento[$notafiscal["idnotafiscal"]];

			if($param_notafiscal_gerarlanctonfe == "2" && !in_array($notafiscal["operacao"], array("CP", "DF"))){
				$ind_pgto = "0";
			}else{
				if(count($arr_lancamento) > 0){
					if(count($arr_lancamento) == 1 && $arr_lancamento[0]["dtemissao"] == $arr_lancamento[0]["dtvencto"]){
						$ind_pgto = "0";
					}else{
						$ind_pgto = "1";
					}
				}else{
					$ind_pgto = "2";
				}
			}

			// Verifica se os itens da nota devem acrescentar no valor do icms
			$base_icms = 0;
			$valor_icms = 0;
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
				if(in_array(substr($itnotafiscal["csticms"], 1, 2), array("00", "20")) || $natoperacao["aprovicmsprop"] == "S"){
					$base_icms = $base_icms + $itnotafiscal["totalbaseicms"];
					$valor_icms = $valor_icms + $itnotafiscal["totalicms"];
				}
			}

			switch($notafiscal["status"]){
				case "C": $cod_sit = "02";
					break;
				case "D": $cod_sit = "04";
					break;
				case "I": $cod_sit = "05";
					break;
				default : $cod_sit = "00";
					break;
			}

			$registro = array(
				// Texto fixo contendo "C100"
				"REG" => "C100",
				// Indicador do tipo de operacao:
				// 0 - Entrada
				// 1 - Saida
				"IND_OPER" => ($operacaonota["tipo"] == "E" ? "0" : "1"),
				// Indicador do emitente do documento fiscal:
				// 0 - Emissao propria
				// 1 - Terceiros
				"IND_EMIT" => $ind_emit,
				// Codigo do participante (campo 02 do registro 0150)
				// Do emitente do documento ou do remetente das mercadorias, no caso de entradas
				// Do adquirente, no caso de saidas
				"COD_PART" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : $this->codparceiro($notafiscal)),
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => $modelo,
				// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
				"COD_SIT" => $cod_sit,
				// Serie do documento fiscal
				"SERIE" => $notafiscal["serie"],
				// Numero do documento fiscal
				"NUM_DOC" => $notafiscal["numnotafis"],
				// Chave da nota fiscal eletronica
				"CHV_NFE" => (in_array($notafiscal["status"], array("I")) ? NULL : $notafiscal["chavenfe"]),
				// Data da emissao do documento fiscal
				"DT_DOC" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_data($notafiscal["dtemissao"])),
				// Data da entrada ou saida
				"DT_E_S" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_data($notafiscal["dtentrega"])),
				// Valor total do documento fiscal
				"VL_DOC" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totalliquido"], 2)),
				// Indicador do tipo de pagamento:
				// 0 - A vista
				// 1 - A prazo
				// 9 - Sem pagamento
				"IND_PGTO" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $ind_pgto),
				// Valor total do desconto
				"VL_DESC" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totaldesconto"], 2)),
				// Abatimento nao tributado e nao comercial
				"VL_ABAT_NT" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : "0,00"),
				// Valor total das mercadorias e servicos
				"VL_MERC" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totalbruto"], 2)),
				// Indicador do tipo de frete:
				// 0 - Por conta de terceiros
				// 1 - Por conta do emitente
				// 2 - Por conta do destinatario
				// 9 - Sem combranca de frete
				"IND_FRT" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : ($notafiscal["totalfrete"] > 0 ? "2" : "9")),
				// Valor do frete indicado no documento fiscal
				"VL_FRT" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totalfrete"], 2)),
				// Valor do seguro indicado no documento fiscal
				"VL_SEG" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : "0,00"),
				// Valor de outras despesas acessorias
				"VL_OUT_DA" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totalacrescimo"], 2)),
				// Valor da base de calculo do ICMS
				"VL_BC_ICMS" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($base_icms, 2)),
				// Valor do ICMS
				"VL_ICMS" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($valor_icms, 2)),
				// Valor da base de calculo do ICMS substituicao tributaria
				"VL_BC_ICMS_ST" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : $this->valor_decimal((in_array($notafiscal["operacao"], array("CP", "DF")) && !$this->gerar_contmatic ? 0 : $notafiscal["totalbaseicmssubst"]), 2)),
				// Valor do ICMS retido por substituicao tributaria
				"VL_ICMS_ST" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : $this->valor_decimal((in_array($notafiscal["operacao"], array("CP", "DF")) && !$this->gerar_contmatic ? 0 : $notafiscal["totalicmssubst"]), 2)),
				// Valor total do IPI
				"VL_IPI" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : $this->valor_decimal((in_array($notafiscal["operacao"], array("CP", "DF")) && !$this->gerar_contmatic ? 0 : $notafiscal["totalipi"]), 2)),
				// Valor total do PIS
				"VL_PIS" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : $this->valor_decimal($notafiscal["totalpis"], 2)),
				// Valor total do COFINS
				"VL_COFINS" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : $this->valor_decimal($notafiscal["totalcofins"], 2)),
				// Valor total do PIS retido por substituicao tributaria
				"VL_PIS_ST" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : "0,00"),
				// Valor total do COFINS retido por substituicao tributaria
				"VL_COFINS_ST" => (in_array($notafiscal["status"], array("C", "I", "D")) || in_array($modelo, array("65")) ? NULL : "0,00")
			);
			$this->escrever_registro($registro);

			// Se for emissao propria, deve gerar apenas o registro C190
			if($ind_emit == "0" && !$this->gerar_contmatic){
				$this->registro_C190($notafiscal);
				if(in_array($notafiscal["natoperacao"], array("5.929", "6.929")) && strlen($notafiscal["observacaofiscal"]) > 0){
					$this->registro_C195();
				}
			}else{
				$this->registro_C105();
				$this->registro_C110();
				$this->registro_C111();
				$this->registro_C112();
				$this->registro_C113();
				$this->registro_C114();
				$this->registro_C115();
				$this->registro_C120();
				$this->registro_C130();
				$this->registro_C140($notafiscal);
				$this->registro_C160($notafiscal);
				$this->registro_C165();
				$this->registro_C170($notafiscal);
				$this->registro_C190($notafiscal);
				$this->registro_C195();
				$this->registro_C197();
			}
		}

		// Gera as notas de complemento
		foreach($this->arr_notacomplemento as $notacomplemento){
			$notafiscal = $notacomplemento["notafiscal"];

			switch($notacomplemento["status"]){
				case "C": $cod_sit = "02";
					break;
				case "D": $cod_sit = "04";
					break;
				case "I": $cod_sit = "05";
					break;
				default : $cod_sit = "06";
					break;
			}

			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			// Verifica qual o indicador de pagamento
			$arr_lancamento = $this->arr_lancamento[$notafiscal["idnotafiscal"]];
			if(count($arr_lancamento) > 0){
				if(count($arr_lancamento) == 1 && $arr_lancamento[0]["dtemissao"] == $arr_lancamento[0]["dtvencto"]){
					$ind_pgto = "0";
				}else{
					$ind_pgto = "1";
				}
			}else{
				$ind_pgto = "2";
			}

			$registro = array(
				// Texto fixo contendo "C100"
				"REG" => "C100",
				// Indicador do tipo de operacao:
				// 0 - Entrada
				// 1 - Saida
				"IND_OPER" => ($operacaonota["tipo"] == "E" ? "0" : "1"),
				// Indicador do emitente do documento fiscal:
				// 0 - Emissao propria
				// 1 - Terceiros
				"IND_EMIT" => ($operacaonota["tipo"] == "E" ? "1" : "0"),
				// Codigo do participante (campo 02 do registro 0150)
				// Do emitente do documento ou do remetente das mercadorias, no caso de entradas
				// Do adquirente, no caso de saidas
				"COD_PART" => ($cod_sit == "06" ? $this->codparceiro($notafiscal) : null),
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => "55",
				// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
				"COD_SIT" => $cod_sit,
				// Serie do documento fiscal
				"SERIE" => $notacomplemento["serie"],
				// Numero do documento fiscal
				"NUM_DOC" => $notacomplemento["numnotafis"],
				// Chave da nota fiscal eletronica
				"CHV_NFE" => $notacomplemento["chavenfe"],
				// Data da emissao do documento fiscal
				"DT_DOC" => ($cod_sit == "06" ? $this->valor_data($notacomplemento["dtemissao"]) : null),
				// Data da entrada ou saida
				"DT_E_S" => ($cod_sit == "06" ? $this->valor_data($notacomplemento["dtentrega"]) : null),
				// Valor total do documento fiscal
				"VL_DOC" => $cod_sit == "06" ? $this->valor_decimal($notacomplemento["totalliquido"], 2): null,
				// Indicador do tipo de pagamento:
				// 0 - A vista
				// 1 - A prazo
				// 9 - Sem pagamento
				"IND_PGTO" => ($cod_sit == "06" ? $ind_pgto : null),
				// Valor total do desconto
				"VL_DESC" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Abatimento nao tributado e nao comercial
				"VL_ABAT_NT" => ($cod_sit == "06" ? "0,00" : null),
				// Valor total das mercadorias e servicos
				"VL_MERC" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Indicador do tipo de frete:
				// 0 - Por conta de terceiros
				// 1 - Por conta do emitente
				// 2 - Por conta do destinatario
				// 9 - Sem combranca de frete
				"IND_FRT" => ($cod_sit == "06" ? "9" : null),
				// Valor do frete indicado no documento fiscal
				"VL_FRT" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Valor do seguro indicado no documento fiscal
				"VL_SEG" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Valor de outras despesas acessorias
				"VL_OUT_DA" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Valor da base de calculo do ICMS
				"VL_BC_ICMS" => ($cod_sit == "06" ? $this->valor_decimal($notacomplemento["totalbaseicms"],2) : null),
				// Valor do ICMS
				"VL_ICMS" => $cod_sit == "06" ? $this->valor_decimal($notacomplemento["totalicms"] , 2) : null,
				// Valor da base de calculo do ICMS substituicao tributaria
				"VL_BC_ICMS_ST" => $cod_sit == "06" ? $this->valor_decimal($notacomplemento["totalbaseicmssubst"], 2) : null,
				// Valor do ICMS retido por substituicao tributaria
				"VL_ICMS_ST" => $cod_sit == "06" ? $this->valor_decimal($notacomplemento["totalicmssubst"], 2) : null,
				// Valor total do IPI
				"VL_IPI" => $cod_sit == "06" ? $this->valor_decimal($notacomplemento["totalipi"], 2) : null,
				// Valor total do PIS
				"VL_PIS" =>($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Valor total do COFINS
				"VL_COFINS" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Valor total do PIS retido por substituicao tributaria
				"VL_PIS_ST" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
				// Valor total do COFINS retido por substituicao tributaria
				"VL_COFINS_ST" => ($cod_sit == "06" ? $this->valor_decimal(0, 2) : null),
			);
			$this->escrever_registro($registro);

			if($cod_sit == "06"){
				$this->registro_C190($notacomplemento);
			}
		}

		foreach($this->arr_notadiversa AS $notadiversa){
			if(in_array($this->valor_natoperacao($notadiversa["tipodocumentofiscal"]), array("55"))){
				$registro = array(
					// Texto fixo contendo "C100"
					"REG" => "C100",
					// Indicador do tipo de operacao:
					// 0 - Entrada
					// 1 - Saida
					"IND_OPER" => "1",
					// Indicador do emitente do documento fiscal:
					// 0 - Emissao propria
					// 1 - Terceiros
					"IND_EMIT" => "1",
					// Codigo do participante (campo 02 do registro 0150)
					// Do emitente do documento ou do remetente das mercadorias, no caso de entradas
					// Do adquirente, no caso de saidas
					"COD_PART" => $this->codparceiro($notadiversa),
					// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => $notadiversa["tipodocumentofiscal"],
					// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
					"COD_SIT" => $cod_sit,
					// Serie do documento fiscal
					"SERIE" => $notadiversa["serie"],
					// Numero do documento fiscal
					"NUM_DOC" => $notadiversa["numnotafis"],
					// Chave da nota fiscal eletronica
					"CHV_NFE" => "",
					// Data da emissao do documento fiscal
					"DT_DOC" => $this->valor_data($notadiversa["dtemissao"]),
					// Data da entrada ou saida
					"DT_E_S" => $this->valor_data($notadiversa["dtemissao"]),
					// Valor total do documento fiscal
					"VL_DOC" => $this->valor_decimal($notadiversa["totalliquido"], 2),
					// Indicador do tipo de pagamento:
					// 0 - A vista
					// 1 - A prazo
					// 9 - Sem pagamento
					"IND_PGTO" => "",
					// Valor total do desconto
					"VL_DESC" => "",
					// Abatimento nao tributado e nao comercial
					"VL_ABAT_NT" => "",
					// Valor total das mercadorias e servicos
					"VL_MERC" => $this->valor_decimal($notadiversa["totalbruto"], 2),
					// Indicador do tipo de frete:
					// 0 - Por conta de terceiros
					// 1 - Por conta do emitente
					// 2 - Por conta do destinatario
					// 9 - Sem combranca de frete
					"IND_FRT" => "9",
					// Valor do frete indicado no documento fiscal
					"VL_FRT" => $this->valor_decimal(0, 2),
					// Valor do seguro indicado no documento fiscal
					"VL_SEG" => $this->valor_decimal(0, 2),
					// Valor de outras despesas acessorias
					"VL_OUT_DA" => $this->valor_decimal($notadiversa["totalacrescimo"], 2),
					// Valor da base de calculo do ICMS
					"VL_BC_ICMS" => $this->valor_decimal($notadiversa["baseicms"], 2),
					// Valor do ICMS
					"VL_ICMS" => $this->valor_decimal($notadiversa["totalicms"], 2),
					// Valor da base de calculo do ICMS substituicao tributaria
					"VL_BC_ICMS_ST" => $this->valor_decimal($notadiversa["baseicmssubst"], 2),
					// Valor do ICMS retido por substituicao tributaria
					"VL_ICMS_ST" => $this->valor_decimal($notadiversa["totalicmssubst"], 2),
					// Valor total do IPI
					"VL_IPI" => $this->valor_decimal($notadiversa["totalipi"], 2),
					// Valor total do PIS
					"VL_PIS" => $this->valor_decimal($notadiversa["totalpis"], 2),
					// Valor total do COFINS
					"VL_COFINS" => $this->valor_decimal($notadiversa["totalcofins"], 2),
					// Valor total do PIS retido por substituicao tributaria
					"VL_PIS_ST" => "0,00",
					// Valor total do COFINS retido por substituicao tributaria
					"VL_COFINS_ST" => "0,00"
				);
				$this->escrever_registro($registro);
			}
		}
	}

	// Operacao com ICMS ST recolhido para UF diversa do destinatario do documento fiscal (codigo 55) [nivel 3] [NAO GERAR]
	protected function registro_C105(){
		return array();
	}

	// Informacao complementar da nota fiscal (codigo 01, 1B, 04, 55) [nivel 3] [NAO GERAR]
	protected function registro_C110(){
		return array();
	}

	// Processo referenciado [nivel 3] [NAO GERAR]
	protected function registro_C111(){
		return array();
	}

	// Documento de arrecadacao referenciado [nivel 3] [NAO GERAR]
	protected function registro_C112(){
		return array();
	}

	// Documento fiscal referenciado [nivel 3] [NAO GERAR]
	protected function registro_C113(){
		return array();
	}

	// Cupom fiscal referenciado [nivel 3] [NAO GERAR]
	protected function registro_C114(){
		return array();
	}

	// Local da coleta e/ou entrega (codigo 01, 1B e 04) [nivel 3] [NAO GERAR]
	protected function registro_C115(){
		return array();
	}

	// Operacoes de importacao (codigo 01) [nivel 3] [NAO GERAR]
	protected function registro_C120(){
		return array();
	}

	// ISSQN, IRRF e previdencia soocial [nivel 3] [NAO GERAR]
	protected function registro_C130(){
		return array();
	}

	// Fatura (codigo 01) [nivel 3]
	protected function registro_C140($notafiscal){
		// Verifica se precisa gerar o registro
		if(in_array($this->modelo($notafiscal), array("01")) || $this->gerar_c140c141){
			$arr_lancamento = $this->arr_lancamento[$notafiscal["idnotafiscal"]];
			// Gerar apenas quando for pagamento a prazo
			if(count($arr_lancamento) > 1 || (count($arr_lancamento) == 1 && $arr_lancamento[0]["dtemissao"] != $arr_lancamento[0]["dtvencto"])){
				$valor_total = 0;
				foreach($arr_lancamento as $lancamento){
					$valor_total += $lancamento["valorparcela"];
				}
				$registro = array(
					// Texto fixo contento "C140",
					"REG" => "C140",
					// Indicador do emitente do titulo:
					// 0 - Emissao propria
					// 1- Terceiros
					"IND_EMIT" => "0",
					// Indicador do tipo de titulo de credito
					// 00 - Duplicata
					// 01 - Cheque
					// 02 - Promissoria
					// 03 - Recibo
					// 99 - Outros (descrever)
					"IND_TIT" => "00",
					// Descricao complementar do titulo de credito
					"DESC_TIT" => "",
					// Numero ou codigo identificador do titulo do credito
					"NUM_TIT" => $notafiscal["idnotafiscal"],
					// Quantidade de parcelas
					"QTD_PARC" => count($arr_lancamento),
					// Valor total dos titulos de credito
					"VL_TIT" => $this->valor_decimal($valor_total, 2)
				);
				$this->escrever_registro($registro);
				$this->registro_C141($arr_lancamento);
			}
		}
	}

	// Vencimento da fatura (codigo 01) [nivel 4]
	protected function registro_C141($arr_lancamento){
		foreach($arr_lancamento as $lancamento){
			$registro = array(
				// Texto fixo contento "C141"
				"REG" => "C141",
				// Numero da parcela a pagar/receber
				"NUM_PARC" => $lancamento["parcela"],
				// Data de vencimento da parcela
				"DT_VCTO" => $this->valor_data($lancamento["dtvencto"]),
				// Valor da parcela a receber/pagar
				"VL_PARC" => $this->valor_decimal($lancamento["valorparcela"], 2)
			);
			$this->escrever_registro($registro);
		}
	}

	// Volumes transportados (codigo 01 e 04)- exceto combustiveis [nivel 3]
	protected function registro_C160($notafiscal){
		$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
		// Verifica se precisa gerar o registro
		if($operacaonota["tipo"] == "S" && in_array($this->modelo($notafiscal), array("01", "04"))){
			$pesobruto = 0;
			$pesoliq = 0;
			$quantidade = 0;
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$produto = $this->arr_produto[$itnotafiscal["codproduto"]];
				$quantidade += $itnotafiscal["quantidade"];
				$pesobruto += $produto["pesobruto"] * $itnotafiscal["quantidade"] * $itnotafiscal["qtdeunidade"];
				$pesoliq += $produto["pesoliq"] * $itnotafiscal["quantidade"] * $itnotafiscal["qtdeunidade"];
			}
			if(strlen($notafiscal["codtransp"]) > 0){
				$registro = array(
					// Texto fixo contento "C160"
					"REG" => "C160",
					// Codigo do participante (campo 02 do registro 0150)
					"COD_PART" => "T".$notafiscal["codtransp"],
					// Placa de identificacao do veiculo automotor
					"VEIC_ID" => "",
					// Quantidade do volumes transportados
					"QTD_VOL" => $this->valor_decimal($quantidade),
					// Peso bruto dos volumes transportados (em Kg)
					"PESO_BRT" => $this->valor_decimal($pesobruto, 2),
					// Peso liquido dos volumes transportados (em Kg)
					"PESO_LIQ" => $this->valor_decimal($pesoliq, 2),
					// Sigla da UF da placa do veiculo
					"UF_ID" => ""
				);
				$this->escrever_registro($registro);
			}
		}
	}

	// Operacoes com combustiveis (codigo 01) [nivel 3] [NAO GERAR]
	protected function registro_C165(){
		return array();
	}

	// Itens do documento(codigo 01, 1B, 04 e 55) [nivel 3]
	protected function registro_C170($notafiscal){
		// Verifica se precisa gerar o registro
		if(in_array($this->modelo($notafiscal), array("01", "1B", "04", "55")) || $this->gerar_contmatic){
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$produto = $this->arr_produto[$itnotafiscal["codproduto"]];
				$piscofins = $this->arr_piscofins[$produto["codpiscofins".($operacaonota["tipo"] == "E" ? "ent" : "sai")]];
				$unidade = $this->arr_unidade[$itnotafiscal["codunidade"]];
				$cst = $this->csticms($itnotafiscal);
				if(in_array(substr($cst, 1, 2), array("00", "20"))){
					$base_icms = $this->valor_decimal($itnotafiscal["totalbaseicms"], 2);
					$valor_icms = $this->valor_decimal($itnotafiscal["totalicms"], 2);
					$aliq_icms = $this->valor_decimal($itnotafiscal["aliqicms"], 2);
				}else{
					$base_icms = "0";
					$valor_icms = "0";
					$aliq_icms = "0";
				}
				$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];

				if($this->gerar_contmatic && $natoperacao["geraspedipi"] == "N"){
					$cstipi = "";
				}else{
					$cstipi = $this->cstipi($itnotafiscal);
				}

				$registro = array(
					// Texto fixo contento "C170"
					"REG" => "C170",
					// Numero sequencial do item no documento fiscal
					"NUM_ITEM" => $itnotafiscal["seqitem"],
					// Codigo do item (campo 02 do registro 0200)
					"COD_ITEM" => $itnotafiscal["codproduto"],
					// Descricao complementar do item como adotado no documento fiscal
					"DESCR_COMPL" => "",
					// Quantidade do item
					"QTD" => $this->valor_decimal($itnotafiscal["quantidade"], 5),
					// Unidade do item (campo 02 do registro 0190)
					"UNID" => $unidade["sigla"],
					// Valor total dos item (mercadorias ou servicos)
					"VL_ITEM" => $this->valor_decimal($itnotafiscal["totalbruto"], 2),
					// Valor do desconto comercial
					"VL_DESC" => $this->valor_decimal($itnotafiscal["totaldesconto"], 2),
					// Movimentacao fisica do item:
					// 0 - Sim
					// 1 - Nao
					"IND_MOV" => "0",
					// Codigo da situacao tributaria referente ao ICMS, conforme a tabela indicada no item 4.3.1
					"CST_ICMS" => (in_array(substr($this->csticms($itnotafiscal), 1, 2), array("10", "70", "60"))) ? substr($this->csticms($itnotafiscal), 0, 1)."60" : $this->csticms($itnotafiscal),
					// Codigo fiscal de operacao e prestacao
					"CFOP" => $this->valor_natoperacao($itnotafiscal["natoperacao"]),
					// Codigo da natureza de operacao (campo 02 do registro 0400),
					"COD_NAT" => $this->gerar_contmatic ? $this->valor_natoperacao($itnotafiscal["natoperacao"]) : "",
					// Valor da base de calculo do ICMS
					"VL_BC_ICMS" => $base_icms,
					// Aliquota de ICMS
					"ALIQ_ICMS" => $aliq_icms,
					// Valor do ICMS creditado/debitado
					"VL_ICMS" => $valor_icms,
					// Valor da base de calculo referente a substituicao tributaria
					"VL_BC_ICMS_ST" => $this->valor_decimal((in_array($notafiscal["operacao"], array("CP", "DF")) && !$this->gerar_contmatic ? 0 : $itnotafiscal["totalbaseicmssubst"]), 2),
					// Aliquota do ICMS da substituicao tributaria na unidade da federacao de destino
					"ALIQ_ST" => $this->valor_decimal(((!in_array($notafiscal["operacao"], array("CP", "DF")) && $itnotafiscal["tptribicms"] == "F") || $this->gerar_contmatic ? $itnotafiscal["aliqicms"] : 0), 2),
					// Valor do ICMS referente a substituicao tributaria
					"VL_ICMS_ST" => $this->valor_decimal((in_array($notafiscal["operacao"], array("CP", "DF")) && !$this->gerar_contmatic ? 0 : $itnotafiscal["totalicmssubst"]), 2),
					// Indicador do periodo de apuracao do IPI
					// 0 - Mensal
					// 1 - Decendial
					"IND_APUR" => "0",
					// Codigo da situtacao tributaria referente ao IPI, conforme a tebela indicada no item 4.3.2
					"CST_IPI" => $cstipi,
					// Codigo de enquadramento legal do IPI, conforme a tabela indicada no item 4.5.3
					"COD_ENQ" => "",
					// Valor da base de calculo do IPI
					"VL_BC_IPI" => $this->valor_decimal(($itnotafiscal["totalipi"] > 0 ? ($itnotafiscal["totalbruto"] - $itnotafiscal["totaldesconto"] + $itnotafiscal["totalacrescimo"]) : 0), 2),
					// Aliquota de IPI
					"ALIQ_IPI" => $this->valor_decimal($itnotafiscal["percipi"], 2),
					// Valor do IPI creditado/debitado
					"VL_IPI" => $this->valor_decimal($itnotafiscal["totalipi"], 2),
					// Codigo da situacao tributaria referente ao PIS
					"CST_PIS" => $this->cstpiscofins($itnotafiscal),
					// Valor da base de calculo do PIS
					"VL_BC_PIS" => $this->valor_decimal($itnotafiscal["totalbasepis"], 2),
					// Aliquota do PIS (em percentual)
					"ALIQ_PIS_P" => $this->valor_decimal($piscofins["aliqpis"], 2),
					// Quantidade - base de calculo PIS
					"QUANT_BC_PIS" => "",
					// Aliquota do PIS (em reais)
					"ALIQ_PIS_F" => "",
					// Valor do PIS
					"VL_PIS" => $this->valor_decimal($itnotafiscal["totalpis"], 2),
					// Codigo da situacao tributaria referente ao COFINS
					"CST_COFINS" => $this->cstpiscofins($itnotafiscal),
					// Valor da base de calculo do COFINS
					"VL_BC_COFINS" => $this->valor_decimal($itnotafiscal["totalbasecofins"], 2),
					// Aliquota do COFINS (em percentual)
					"ALIQ_COFINS_P" => $this->valor_decimal($piscofins["aliqcofins"], 2),
					// Quantidade - base de calculo COFINS
					"QUANT_BC_COFINS" => "",
					// Aliquota do COFINS (em reais)
					"ALIQ_COFINS_F" => "",
					// Valor do COFINS
					"VL_COFINS" => $this->valor_decimal($itnotafiscal["totalcofins"], 2),
					// Codigo da conta conta analitica contabil debitada/creditada
					"COD_CTA" => ""
				);
				if($this->gerar_contmatic){
					if(strlen($produto["idncm"]) > 0){
						$cest = removeformat($this->arr_ncm[$produto["idncm"]]["cest"]);
					}else{
						$cest = NULL;
					}


					$registro = array_merge($registro, array(
						// Natureza da receita isenta, nao alcancada pela incidencia da contribuicao, sujeita a aliquota zero ou venda com suspensao de PIS
						"NAT_REC_PIS" => $this->natreceita($itnotafiscal),
						// Natureza da receita isenta, nao alcancada pela incidencia da contribuicao, sujeita a aliquota zero ou venda com suspensao de Cofins
						"NAT_REC_COFINS" => $this->natreceita($itnotafiscal)
					));
				}
				$this->escrever_registro($registro);
				$this->registro_C171();
				$this->registro_C172();
				$this->registro_C173();
				$this->registro_C174();
				$this->registro_C175();
				$this->registro_C176();
				$this->registro_C177();
				$this->registro_C178();
				$this->registro_C179();
			}
			$_SESSION["SPED"]["cons_cst"]["totalbruto"] += $itnotafiscal["totalbruto"];
		}
	}

	// Armazenamento de combustivel (codigo 01 e 55) [nivel 4] [NAO GERAR]
	protected function registro_C171(){
		return array();
	}

	// Operacoes com ISSQN (codigo 01) [nivel 4] [NAO GERAR]
	protected function registro_C172(){
		return array();
	}

	// Operacoes com medicamentos (codigo 01 e 55) [nivel 4] [NAO GERAR]
	protected function registro_C173(){
		return array();
	}

	// Operacoes com armas de fogo (codigo 01) [nivel 4] [NAO GERAR]
	protected function registro_C174(){
		return array();
	}

	// Operacoes com veiculos novos (codigo 01 e 55) [nivel 4] [NAO GERAR]
	protected function registro_C175(){
		return array();
	}

	// Ressarcimento de ICMS em operacoes com substituicao tributaria (codigo 01 e 55) [nivel 4] [NAO GERAR]
	protected function registro_C176(){
		return array();
	}

	// Operacoes com produtos sujeitos a selo de controle IPI [nivel 4] [NAO GERAR]
	protected function registro_C177(){
		return array();
	}

	// Operacoes com produtos sujeitos a tributacao de IPI por unidade ou quantidade de produto [nivel 4] [NAO GERAR]
	protected function registro_C178(){
		return array();
	}

	// Informacoes complementares ST (codigo 01) [nivel 4] [NAO GERAR]
	protected function registro_C179(){
		return array();
	}

	// Registro analitico do documento (codigo 01, 1B, 04 e 55) [nivel 3]
	protected function registro_C190($notafiscal){
		$arr = $this->preparar_C190($notafiscal);
		unset($this->arr_c195);
		$this->arr_c195 = array();

		foreach($arr as $codcst => $arr2){
			foreach($arr2 as $natoperacao => $arr3){
				foreach($arr3 as $aliqicms => $arr4){
					$registro = array(
						// Texto fixo contendo "C190"
						"REG" => "C190",
						// Codigo da situacao tributaria, conforma a tabela indicada no item 4.3.1
						"CST_ICMS" => $codcst,
						// Codigo fiscal de operacao e prestacao do agrupamento de itens
						"CFOP" => $natoperacao,
						// Aliquota de ICMS
						"ALIQ_ICMS" => $aliqicms,
						// Valor da operacao na combinacao de CST_ICMS, CFOP e aliquota de ICMS, correspondente ao somatorio do valor
						// das mercadorias, despesas acessorias (frete, seguros e outras despesas acessorias), ICMS_ST e IPI
						"VL_OPR" => $this->valor_decimal($arr4["totalliquido"], 2),
						// Parcela correspondente ao "valor da base de calculo do ICMS" referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
						"VL_BC_ICMS" => $this->valor_decimal($arr4["totalbaseicms"], 2),
						// Parcela referente ao "valor do ICMS" referente a combinacao de CST_ICMS, CFOP e aliquota do ICMS
						"VL_ICMS" => $this->valor_decimal($arr4["totalicms"], 2),
						// Parcela correspondente ao "valor da base de calculo de ICMS" da substituicao tributaria referente
						// a combinacao de CST_ICMS, CFOP e aliquota de ICMS
						"VL_BC_ICMS_ST" => $this->valor_decimal($arr4["totalbaseicmssubst"], 2),
						// Parcela correspondente ao valor creditado/debitado do ICMS da substituicao tributaria referente
						// a combinacao de CST_ICMS, CFOP e aliquota de ICMS
						"VL_ICMS_ST" => $this->valor_decimal($arr4["totalicmssubst"], 2),
						// Valor nao tributado em funcao da reducao da base de calculo do ICMS, referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
						"VL_RED_BC" => $this->valor_decimal($arr4["totalredicms"], 2),
						// Parcela referente ao "valor do IPI" referente a combinacao de CST_ICMS, CFOP e aliquota do ICMS
						"VL_IPI" => $this->valor_decimal($arr4["totalipi"], 2),
						// Codigo da observacao fiscal (campo 02 do registro 0460)
						"COD_OBS" => ""
					);
					$this->arr_c195[] = $arr4["cod_obs"];
					$this->escrever_registro($registro);
				}
			}
		}
	}

	// Observacoes do lancamento fiscal (codigo 01, 1B, e 55) [nivel 3]
	protected function registro_C195(){
		foreach($this->arr_c195 AS $c195){
			if(strlen($c195) == 0){
				continue;
			}
			$registro = array(
				// Texto fixo contento "C195"
				"REG" => "C195",
				// Codigo da obsercao do lancamento fiscal
				"COD_OBS" => $c195,
				// Descricao complementar do codigo de observacao
				"TXT_COMPL" => ""
			);
			$this->escrever_registro($registro);
		}
	}

	// Outras obrigacoes tributarias, ajustes e informacoes de valores provenientes de documento fiscal [nivel 3] [NAO GERAR]
	protected function registro_C197(){
		return array();
	}

	// Resumo diario das notas fiscais de venda a consumidor (codigo 02) [nivel 2]
	protected function registro_C300(){
		if($this->estabelecimento->getperfil() == "B"){
			$arr_resumo = array();
			foreach($this->arr_notafiscal as $notafiscal){
				// Verifica o modelo do documento
				if(strlen($notafiscal["numnotafisfinal"]) > 0){
					$data = $notafiscal["dtemissao"];
					$serie = $notafiscal["serie"];
					if(!isset($arr_resumo[$data])){
						$arr_resumo[$data] = array();
					}
					if(!isset($arr_resumo[$data][$serie])){
						$arr_resumo[$data][$serie] = array(
							"numnotafisini" => $notafiscal["numnotafis"],
							"numnotafisfim" => $notafiscal["numnotafisfinal"],
							"totalliquido" => $notafiscal["totalliquido"],
							"totalpis" => $notafiscal["totalpis"],
							"totalcofins" => $notafiscal["totalcofins"]
						);
					}else{
						if($notafiscal["numnotafis"] < $arr_resumo[$data][$serie]["numnotafisini"]){
							$arr_resumo[$data][$serie]["numnotafisini"] = $notafiscal["numnotafis"];
						}
						if($notafiscal["numnotafis"] > $arr_resumo[$data][$serie]["numnotafisfim"]){
							$arr_resumo[$data][$serie]["numnotafisfim"] = $notafiscal["numnotafis"];
						}
						$arr_resumo[$data][$serie]["totalliquido"] += $notafiscal["totalliquido"];
						$arr_resumo[$data][$serie]["totalpis"] += $notafiscal["totalpis"];
						$arr_resumo[$data][$serie]["totalcofins"] += $notafiscal["totalcofins"];
					}
					$arr_resumo[$data][$serie]["notafiscal"][] = $notafiscal;
				}
				foreach($arr_resumo as $data => $resumo){
					foreach($resumo as $serie => $valor){
						$registro = array(
							// Texto fixo contendo "C300"
							"REG" => "C300",
							// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
							"COD_MOD" => "02",
							// Serie do documento fiscal
							"SER" => $serie,
							// Subserie do documento fiscal
							"SUB" => "",
							// Numero do documento fiscal inicial
							"NUM_DOC_INI" => $valor["numnotafisini"],
							// Numero do documento fiscal final
							"NUM_DOC_FIN" => $valor["numnotafisfim"],
							// Data da emissao dos documentos fiscais
							"DT_DOC" => $this->valor_data($data),
							// Valor total dos documentos
							"VL_DOC" => $this->valor_decimal($valor["totalliquido"], 2),
							// Valor total do PIS
							"VL_PIS" => $this->valor_decimal($valor["totalpis"], 2),
							// Valor total do COFINS
							"VL_COFINS" => $this->valor_decimal($valor["totalcofins"], 2),
							// Codigo da conta analitica contabil debitada/creditada
							"COD_CTA" => ""
						);
						$this->escrever_registro($registro);
						$this->registro_C310();
						$this->registro_C320($arr_resumo[$data][$serie]["notafiscal"]);
					}
				}
			}

			foreach($this->arr_notadiversa AS $notadiversa){
				if(in_array($this->valor_natoperacao($notadiversa["tipodocumentofiscal"]), array("02"))){

					$data = $notadiversa["dtemissao"];
					$serie = $notadiversa["serie"];
					if(!isset($arr_resumo[$data])){
						$arr_resumo[$data] = array();
					}
					if(!isset($arr_resumo[$data][$serie])){
						$arr_resumo[$data][$serie] = array(
							"numnotafisini" => $notadiversa["numnotafis"],
							"numnotafisfim" => $notadiversa["numnotafis"],
							"totalliquido" => $notadiversa["totalliquido"],
							"totalpis" => $notadiversa["totalpis"],
							"totalcofins" => $notadiversa["totalcofins"]
						);
					}else{
						if($notadiversa["numnotafis"] < $arr_resumo[$data][$serie]["numnotafisini"]){
							$arr_resumo[$data][$serie]["numnotafisini"] = $notadiversa["numnotafis"];
						}
						if($notadiversa["numnotafis"] > $arr_resumo[$data][$serie]["numnotafisfim"]){
							$arr_resumo[$data][$serie]["numnotafisfim"] = $notadiversa["numnotafis"];
						}
						$arr_resumo[$data][$serie]["totalliquido"] += $notadiversa["totalliquido"];
						$arr_resumo[$data][$serie]["totalpis"] += $notadiversa["totalpis"];
						$arr_resumo[$data][$serie]["totalcofins"] += $notadiversa["totalcofins"];
					}

					$registro = array(
						// Texto fixo contendo "C300"
						"REG" => "C300",
						// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
						"COD_MOD" => $notadiversa["tipodocumentofiscal"],
						// Serie do documento fiscal
						"SER" => $notadiversa["serie"],
						// Subserie do documento fiscal
						"SUB" => "",
						// Numero do documento fiscal inicial
						"NUM_DOC_INI" => $notadiversa["numnotafis"],
						// Numero do documento fiscal final
						"NUM_DOC_FIN" => $notadiversa["numnotafis"],
						// Data da emissao dos documentos fiscais
						"DT_DOC" => $this->valor_data($notadiversa["dtemissao"]),
						// Valor total dos documentos
						"VL_DOC" => $this->valor_decimal($notadiversa["totalliquido"], 2),
						// Valor total do PIS
						"VL_PIS" => $this->valor_decimal($notadiversa["totalpis"], 2),
						// Valor total do COFINS
						"VL_COFINS" => $this->valor_decimal($notadiversa["totalcofins"], 2),
						// Codigo da conta analitica contabil debitada/creditada
						"COD_CTA" => ""
					);
					$notadiversa["codcst"] = $notadiversa["codcsticms"];
					$notadiversa["itnotafiscal"] = array($notadiversa);
					$arr_resumo[$data][$serie]["notadiversa"][] = $notadiversa;

					$this->escrever_registro($registro);
					$this->registro_C310();
					$this->registro_C320($arr_resumo[$data][$serie]["notadiversa"]);
				}
			}
		}
	}

	// Documentos cancelados de notas fiscais de venda a consumidor (codigo 02) [nivel 3] [NAO GERAR]
	protected function registro_C310(){
		return array();
	}

	// Registro analitico do resumo diario das notas fiscais de venda a consumidor (codigo 02) [nivel 3]
	protected function registro_C320($arr_notafiscal){
		$arr = array();
		foreach($arr_notafiscal as $notafiscal){
			if(!strlen($notafiscal["numnotafisfinal"]) > 0){
				continue;
			}
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$csticms = substr($itnotafiscal["csticms"], 0, 3);
				$natoperacao = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
				$aliqicms = $this->valor_decimal($itnotafiscal["aliqicms"], 2);
				$arr[$codcst][$natoperacao][$aliqicms]["totalliquido"] += $itnotafiscal["totalliquido"];
				$arr[$codcst][$natoperacao][$aliqicms]["totalbaseicms"] += $itnotafiscal["totalbaseicms"];
				$arr[$codcst][$natoperacao][$aliqicms]["totalicms"] += $itnotafiscal["totalicms"];
				$arr[$codcst][$natoperacao][$aliqicms]["totalredicms"] += $itnotafiscal["totalbaseicms"] * $itnotafiscal["redicms"] / 100;
				$arr[$codcst][$natoperacao][$aliqicms]["itnotafiscal"][] = $itnotafiscal;
			}
		}
		foreach($arr as $codcst => $arr2){
			foreach($arr2 as $natoperacao => $arr3){
				foreach($arr3 as $aliqicms => $arr4){
					$registro = array(
						// Texto fixo contendo "C320"
						"REG" => "C320",
						// Codigo da situacao tributaria, conforma a tabela indicada no item 4.3.1
						"CST_ICMS" => $csticms,
						// Codigo fiscal de operacao e prestacao do agrupamento de itens
						"CFOP" => $natoperacao,
						// Aliquota de ICMS
						"ALIQ_ICMS" => $aliqicms,
						// Valor da operacao na combinacao de CST_ICMS, CFOP e aliquota de ICMS, correspondente ao somatorio do valor
						// das mercadorias, despesas acessorias (frete, seguros e outras despesas acessorias), ICMS_ST e IPI
						"VL_OPR" => $this->valor_decimal($arr4["totalliquido"], 2),
						// Parcela correspondente ao "valor da base de calculo do ICMS" referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
						"VL_BC_ICMS" => $this->valor_decimal($arr4["totalbaseicms"], 2),
						// Parcela referente ao "valor do ICMS" referente a combinacao de CST_ICMS, CFOP e aliquota do ICMS
						"VL_ICMS" => $this->valor_decimal($arr4["totalicms"], 2),
						// Valor nao tributado em funcao da reducao da base de calculo do ICMS, referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
						"VL_RED_ICMS" => $this->valor_decimal($arr4["totalredicms"], 2),
						// Codigo da observacao fiscal (campo 02 do registro 0460)
						"COD_OBS" => ""
					);
					$this->escrever_registro($registro);
					$this->registro_C321($arr4["itnotafiscal"]);
				}
			}
		}
	}

	// Itens do resumo diario dos documentos (codigo 2) [nivel 4]
	protected function registro_C321($arr_itnotafiscal){
		$arr = array();
		foreach($arr_itnotafiscal as $itnotafiscal){
			$codproduto = $itnotafiscal["codproduto"];
			$arr[$codproduto]["quantidade"] += $itnotafiscal["quantidade"];
			$arr[$codproduto]["totalliquido"] += $itnotafiscal["totalliquido"];
			$arr[$codproduto]["totaldesconto"] += $itnotafiscal["totaldesconto"];
			$arr[$codproduto]["totalbaseicms"] += $itnotafiscal["totalbaseicms"];
			$arr[$codproduto]["totalicms"] += $itnotafiscal["totalicms"];
			$arr[$codproduto]["totalpis"] += $itnotafiscal["totalpis"];
			$arr[$codproduto]["totalcofins"] += $itnotafiscal["totalcofins"];
			$arr[$codproduto]["codunidade"] = $itnotafiscal["codunidade"];
		}
		$produto = $this->arr_produto[$codproduto];
		$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
		$unidade = $this->arr_unidade[$embalagem["codunidade"]];

		foreach($arr as $codproduto => $row){
			$registro = array(
				// Texto fixo contendo "C321"
				"REG" => "C321",
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $codproduto,
				// Quantidade acumulada do item
				"QTD" => $this->valor_decimal($row["quantidade"], 3),
				// Unidade do item (campo 02 do registro 0190)
				"UNID" => $unidade["sigla"],
				// Valor acumulado do item
				"VL_ITEM" => $this->valor_decimal($row["totalliquido"], 2),
				// Valor do desconto acumulado
				"VL_DESC" => $this->valor_decimal($row["totaldesconto"], 2),
				// Valor acumulado da base de calculo de ICMS
				"VL_BC_ICMS" => $this->valor_decimal($row["totalbaseicms"], 2),
				// Valor acumulado do ICMS debitado
				"VL_ICMS" => $this->valor_decimal($row["totalicms"], 2),
				// Valor acumulado do PIS
				"VL_PIS" => $this->valor_decimal($row["totalpis"], 2),
				// Valor acumulado do COFINS
				"VL_COFINS" => $this->valor_decimal($row["totalcofins"], 2)
			);
			$this->escrever_registro($registro);
		}
	}

	//Nota fiscal de venda a consumidor (codigo 2) [nivel 2]
	protected function registro_C350(){
		if($this->estabelecimento->getperfil() == "A"){
			foreach($this->arr_notafiscal as $notafiscal){
				if(in_array($this->modelo($notafiscal), array("02"))){
					$registro = array(
						// Texto fixo contendo "C350"
						"REG" => "C350",
						// Serie do documento fiscal
						"SER" => $notafiscal["serie"],
						// Subserie do documento fiscal
						"SUB_SER" => "",
						// Numero do documento fiscal
						"NUM_DOC" => $notafiscal["numnotafis"],
						// Data da emissao do documento
						"DT_DOC" => $this->valor_data($notafiscal["dtemissao"]),
						// CFP ou CNPJ do destinatario
						"CNPJ_CPF" => "",
						// Valor das mercadorias constantes no documento fiscal
						"VL_MERC" => $this->valor_decimal($notafiscal["totalbruto"], 2),
						// Valor total do documento fiscal
						"VL_DOC" => $this->valor_decimal($notafiscal["totalliquido"], 2),
						// Valor total do desconto
						"VL_DESC" => $this->valor_decimal($notafiscal["totaldesconto"], 2),
						// Valor total do PIS
						"VL_PIS" => $this->valor_decimal($notafiscal["totalpis"], 2),
						// Valor total do COFINS
						"VL_COFINS" => $this->valor_decimal($notafiscal["totalcofins"], 2),
						// Codigo da conta analitica contabil debitada/creditada
						"COD_CTA" => ""
					);
					$this->escrever_registro($registro);
					$this->registro_C370($notafiscal);
					$this->registro_C390($notafiscal);
				}
			}
		}
	}

	// Itens do documento (codigo 2) [nivel 3]
	protected function registro_C370($notafiscal){
		foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
			$produto = $this->arr_produto[$itnotafiscal["codproduto"]];
			$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
			$unidade = $this->arr_unidade[$embalagem["codunidade"]];

			$registro = array(
				// Texto fixo contendo "C370"
				"REG" => "C370",
				// Numero sequencial do item no documento fiscal
				"NUM_ITEM" => $itnotafiscal["seqitem"],
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $itnotafiscal["codproduto"],
				// Quantidade do item
				"QTD" => $this->valor_decimal($itnotafiscal["quantidade"], 3),
				// Unidade do item (campo 02 do registro 0190)
				"UNID" => $unidade["sigla"],
				// Valor total do item
				"VL_ITEM" => $this->valor_decimal($itnotafiscal["totalliquido"], 2),
				// Valor total do desconto no item
				"VL_DESC" => $this->valor_decimal($itnotafiscal["totaldesconto"], 2)
			);
			$this->escrever_registro($registro);
		}
	}

	// Registro analitico das notas fiscais de venda a consimidor (codigo 02) [nivel 3]
	protected function registro_C390($notafiscal){
		if(in_array($this->modelo($notafiscal), array("02"))){
			$arr_sum = array();
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$csticms = $itnotafiscal["csticms"];
				$natoperacao = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
				$aliqicms = $this->valor_decimal($itnotafiscal["aliqicms"], 2);
				$index = $csticms."|".$natoperacao."|".$aliqicms;
				$arr_sum[$index]["VL_OP"] += $itnotafiscal["totalbruto"] + $itnotafiscal["totalacrescimo"] - $itnotafiscal["totaldesconto"];
				$arr_sum[$index]["VL_BC_ICMS"] += $itnotafiscal["totalbaseicms"];
				$arr_sum[$index]["VL_ICMS"] += $itnotafiscal["totalicms"];
				$arr_sum[$index]["VL_RED_BC"] += $itnotafiscal["totalbaseicms"] * $itnotafiscal["redicms"] / 100;
			}
			foreach($arr_sum as $index => $sum){
				$arr_index = explode("|", $index);
				$csticms = $arr_index[0];
				$natoperacao = $arr_index[1];
				$aliqicms = $arr_index[2];
				$registro = array(
					// Texto fixo contendo "C390"
					"REG" => "C390",
					// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
					"CST_ICMS" => $csticms,
					// Codigo fiscal de operacao e prestacao
					"CFOP" => $natoperacao,
					// Aliquota de ICMS
					"ALIQ_ICMS" => $aliqicms,
					// Valor total acumulado das operacoes correspondentes a combinacao de CST_ICMS, CFOP, e aliquota do ICMS, incluidas as despesas acessorias e acrescimos
					"VL_OPR" => $this->valor_decimal($sum["VL_OP"], 2),
					// Valor acumulado da base de calculo do ICMS, referente a combinacao de CST_ICMS, CFOP, e aliquota do ICMS
					"VL_BC_ICMS" => $this->valor_decimal($sum["VL_BC_ICMS"], 2),
					// Valor acumulado do ICMS, referente a combinacao de CST_ICMS, CFOP, e aliquota do ICMS
					"VL_ICMS" => $this->valor_decimal($sum["VL_ICMS"], 2),
					// Valor nao tributado em funcao da reducao da base de calculo do ICMS, referente a combinacao de CST_ICMS, CFOP, e aliquota do ICMS
					"VL_RED_BC" => $this->valor_decimal($sum["VL_RED_BC"], 2),
					// Codigo da observacao do lancamento fiscal (campo 2 do registro 0460)
					"COD_OBS" => ""
				);
				$this->escrever_registro($registro);
			}
		}
	}

	// Equipamento ECF (codigo 02 e 2D) [nivel 2]
	protected function registro_C400(){
		foreach($this->arr_ecf as $ecf){
			if($ecf["equipamentofiscal"] !== "ECF"){
				continue;
			}else{
				$registro = array(
					// Texto fixo contento "C400"
					"REG" => "C400",
					// Código do modelo do documento fiscal, confrome a tabela 4.1.1
					"COD_MOD" => $ecf["equipamentofiscal"] == "ECF" ? "2D" : "59",
					// Modelo do equipamento
					"ECF_MOD" => $ecf["modelo"],
					// Numero de serie de fabricacao do ECF
					"ECF_FAB" => $ecf["numfabricacao"],
					// Numero do caixa atribuido ao ECF
					"ECF_CX" => $ecf["numeroecf"]
				);
			}
			$this->escrever_registro($registro);
			if($ecf["equipamentofiscal"] == "ECF"){
				$this->registro_C405($ecf);
			}else{
				//$this->registro_C460($maparesumo);
			}
		}
	}

	// Reducao Z (codigo 02 e 2D) [nivel 3]
	protected function registro_C405($ecf){
		foreach($this->arr_maparesumo as $maparesumo){
			if($maparesumo["codecf"] !== $ecf["codecf"]){
				continue;
			}
			if($ecf["equipamentofiscal"] !== "ECF"){
				continue;
			}

			$registro = array(
				// Texto fixo contendo "C405"
				"REG" => "C405",
				// Data do movimento a que se refere a Reducao Z
				"DT_DOC" => $this->valor_data($maparesumo["dtmovto"]),
				// Posicao do contador de reinicio de operacao
				"CRO" => $maparesumo["reiniciofim"],
				// Posicao do contador de Reduzao Z
				"CRZ" => $maparesumo["numeroreducoes"],
				// Numero do contador de ordem de operacao do ultimo documento emitido no dia (numero do COO na Reducao Z)
				"NUM_COO_FIN" => $maparesumo["operacaofim"],
				// Valor do Grande Total final
				"GT_FIN" => $this->valor_decimal($maparesumo["gtfinal"], 2),
				// Valor da venda bruta
				"VL_BRT" => $this->valor_decimal($maparesumo["totalbruto"], 2)
			);
			$this->escrever_registro($registro);

			$this->registro_C410($maparesumo);
			$this->registro_C420($maparesumo);
			$this->registro_C460($maparesumo);
			$this->registro_C490($maparesumo);
		}

		if($this->gerar_contmatic && $ecf["equipamentofiscal"] != "ECF"){
			foreach($this->arr_cupom["SAT"] AS $cupom){
//				if($cupom["numeroecf"] != $ecf["numeroecf"]){
				if($cupom["codecf"] != $ecf["codecf"]){
					continue;
				}

				$registro = array(
					// Texto fixo contendo "C405"
					"REG" => "C405",
					// Data do movimento a que se refere a Reducao Z
					"DT_DOC" => $this->valor_data($cupom["dtmovto"]),
					// Posicao do contador de reinicio de operacao
					"CRO" => "",
					// Posicao do contador de Reduzao Z
					"CRZ" => "",
					// Numero do contador de ordem de operacao do ultimo documento emitido no dia (numero do COO na Reducao Z)
					"NUM_COO_FIN" => $cupom["seqecf"],
					// Valor do Grande Total final
					"GT_FIN" => "",
					// Valor da venda bruta
					"VL_BRT" => ""
				);
				$this->escrever_registro($registro);

				$this->registro_C460(null, $cupom);
			}
		}
	}

	// PIS e COFINS totalizados no dia (codigo 02 e 2D) [nivel 4]
	protected function registro_C410($maparesumo){
		$vl_pis = 0;
		$vl_cofins = 0;

		foreach($this->arr_cupom[$maparesumo["codmaparesumo"]] as $cupom){
			foreach($cupom["itcupom"] as $itcupom){
				$vl_pis += $itcupom["totalpis"];
				$vl_cofins += $itcupom["totalcofins"];
			}
		}
		if($vl_pis > 0 && $vl_cofins > 0){
			$registro = array(
				// Texto fixo contendo "C410"
				"REG" => "C410",
				// Valor total do PIS
				"VL_PIS" => $this->valor_decimal($vl_pis, 2),
				// Valor total da Cofins
				"VL_COFINS" => $this->valor_decimal($vl_cofins, 2)
			);
			$this->escrever_registro($registro);
		}
	}

	// Registro dos totalizadores parciais da reducao Z (codigo 02 e 2D) [nivel 4]
	protected function registro_C420($maparesumo){
		foreach($maparesumo["maparesumoimposto"] as $maparesumoimposto){
			switch($maparesumoimposto["tptribicms"]){
				case "F":
					$cod_tot_par = "F0";
					$nr_tot = "";
					break;
				case "I":
					$cod_tot_par = "I0";
					$nr_tot = "";
					break;
				case "N":
					$cod_tot_par = "N0";
					$nr_tot = "";
					break;
				case "T":
					$cod_tot_par = "T".str_pad(number_format($maparesumoimposto["aliqicms"], 2, "", ""), 4, "0", STR_PAD_LEFT);
					$nr_tot = "1";
					break;
			}
			$registro = array(
				// Texto fixo contendo "C420"
				"REG" => "C420",
				// Codigo do totalizador, conforme tabela 4.4.6
				"COD_TOT_PAR" => $cod_tot_par,
				// Valor acumulado no totalizador, relativo a respectiva Reducao Z
				"VLR_ACUM_TOT" => $this->valor_decimal($maparesumoimposto["totalliquido"], 2),
				// Numero do totalizador quando ocorrer mais de uma situacao com a mesma carga tributaria efetiva
				"NR_TOT" => $nr_tot,
				// Descricao da situacao tributaria relativa ao totalizador parcial, quando houver mais de um com a mesma carga tributaria efetiva
				"DESCR_NR_TOT" => ""
			);
			$this->escrever_registro($registro);
			$this->registro_C425($maparesumo, $maparesumoimposto);
		}
		$registro = array(
			// Texto fixo contendo "C420"
			"REG" => "C420",
			// Codigo do totalizador, conforme tabela 4.4.6
			"COD_TOT_PAR" => "Can-t",
			// Valor acumulado no totalizador, relativo a respectiva Reducao Z
			"VLR_ACUM_TOT" => $this->valor_decimal($maparesumo["totalcupomcancelado"], 2),
			// Numero do totalizador quando ocorrer mais de uma situacao com a mesma carga tributaria efetiva
			"NR_TOT" => "",
			// Descricao da situacao tributaria relativa ao totalizador parcial, quando houver mais de um com a mesma carga tributaria efetiva
			"DESCR_NR_TOT" => ""
		);
		$this->escrever_registro($registro);

		$registro = array(
			// Texto fixo contendo "C420"
			"REG" => "C420",
			// Codigo do totalizador, conforme tabela 4.4.6
			"COD_TOT_PAR" => "DT",
			// Valor acumulado no totalizador, relativo a respectiva Reducao Z
			"VLR_ACUM_TOT" => $this->valor_decimal($maparesumo["totaldescontocupom"], 2),
			// Numero do totalizador quando ocorrer mais de uma situacao com a mesma carga tributaria efetiva
			"NR_TOT" => "",
			// Descricao da situacao tributaria relativa ao totalizador parcial, quando houver mais de um com a mesma carga tributaria efetiva
			"DESCR_NR_TOT" => ""
		);
		$this->escrever_registro($registro);
	}

	// Resumo dos itens do movimento diario (codigo 02 e 2D) [nivel 5]
	protected function registro_C425($maparesumo, $maparesumoimposto){
		/*
		  $arr = array();
		  foreach($this->arr_cupom[$maparesumo["codmaparesumo"]] as $cupom){
		  foreach($cupom["itcupom"] as $itcupom){
		  $produto = $this->arr_produto[$itcupom["codproduto"]];
		  $embalagem = $this->arr_embalagem[$produto["codembalvda"]];
		  $arr[$itcupom["codproduto"]]["quantidade"] += $itcupom["quantidade"];
		  $arr[$itcupom["codproduto"]]["valortotal"] += $itcupom["valortotal"];
		  $arr[$itcupom["codproduto"]]["totalpis"] += $itcupom["totalpis"];
		  $arr[$itcupom["codproduto"]]["totalcofins"] += $itcupom["totalcofins"];
		  $arr[$itcupom["codproduto"]]["codunidade"] = $embalagem["codunidade"];
		  }
		  }

		  foreach($arr as $codproduto => $row){
		  $registro = array(
		  // Texto fixo contendo "C425"
		  "REG" => "C425",
		  // Codigo do item (campo 02 do registro 0200)
		  "COD_ITEM" => $codproduto,
		  // Quantidade acumulada do item
		  "QTD" => $this->valor_decimal($row["quantidade"],2),
		  // Unidade do item (campo 02 do registro 0190)
		  "UNID" => $row["codunidade"],
		  // Valor acumulado do item
		  "VL_ITEM" => $this->valor_decimal($row["valortotal"],2),
		  // Valor do PIS
		  "VL_PIS" => $this->valor_decimal($row["totalpis"],2),
		  // Valor da Cofins
		  "VL_COFINS" => $this->valor_decimal($row["totalcofins"],2)
		  );
		  $this->escrever_registro($registro);
		  }
		 */
	}

	// Documento fiscal emitido por ECF (codigo 02 e 2D) [nivel 4]
	protected function registro_C460($maparesumo = null, $cupom = null){
		if(!is_null($maparesumo)){
			foreach($this->arr_cupom[$maparesumo["codmaparesumo"]] as $cupom){
				if($cupom["status"] != "A"){
					continue;
				}

				$registro = array(
					// Texto fixo contendo "C460"
					"REG" => "C460",
					// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => "2D",
					// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
					"COD_SIT" => "00",
					// Numero do documento fiscal (COO)
					"NUM_DOC" => $cupom["seqecf"],
					// Data de emissao do documento fiscal
					"DT_DOC" => $this->valor_data($cupom["dtmovto"]),
					// Valor do documento fiscal
					"VL_DOC" => $this->valor_decimal($cupom["totalliquido"], 2),
					// Valor do PIS
					"VL_PIS" => $this->valor_decimal($cupom["totalpis"], 2),
					// Valor da COFINS
					"VL_COFINS" => $this->valor_decimal($cupom["totalcofins"], 2),
					// CPF ou CNPJ do adquirente
					"CPF_CNPJ" => removeformat($cupom["cpfcnpj"]),
					// Nome do adquirente
					"NOM_ADQ" => ""
				);

				$this->escrever_registro($registro);
				$this->registro_C470($cupom);
			}
		}else{
			$cod_sit = $cupom["status"] == "C" ? "02" : "00";

			$registro = array(
				// Texto fixo contendo "C460"
				"REG" => "C460",
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => "59",
				// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
				"COD_SIT" => $cod_sit,
				// Numero do documento fiscal (COO)
				"NUM_DOC" => $cupom["seqecf"],
				// Data de emissao do documento fiscal
				"DT_DOC" => $this->valor_data($cupom["dtmovto"]),
				// Valor do documento fiscal
				"VL_DOC" => $this->valor_decimal($cupom["totalliquido"], 2),
				// Valor do PIS
				"VL_PIS" => $this->valor_decimal($cupom["totalpis"], 2),
				// Valor da COFINS
				"VL_COFINS" => $this->valor_decimal($cupom["totalcofins"], 2),
				// CPF ou CNPJ do adquirente
				"CPF_CNPJ" => removeformat($cupom["cpfcnpj"]),
				// Nome do adquirente
				"NOM_ADQ" => ""
			);



			$registro = array_merge($registro,$registro_contimatic);

			$this->escrever_registro($registro);
			$this->registro_C470($cupom);
		}
	}

	// Itens do documento fiscal emitido por ECF (codigo 02 e 2D) [nivel 5]
	protected function registro_C470($cupom){
		foreach($cupom["itcupom"] as $itcupom){
			$produto = $this->arr_produto[$itcupom["codproduto"]];
			$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
			$unidade = $this->arr_unidade[$embalagem["codunidade"]];
			$piscofins = $this->arr_piscofins[$produto["codpiscofinssai"]];
			$registro = array(
				// Texto fixo contendo "C470"
				"REG" => "C470",
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $itcupom["codproduto"],
				// Quantidade do item
				"QTD" => $this->valor_decimal($itcupom["quantidade"], 3),
				// Quantidade cancelada, no caso de cancelamento parcial do item
				"QTD_CANC" => $this->valor_decimal(0, 3),
				// Unidade do item (campo 02 do registro 0190)
				"UNID" => $unidade["sigla"],
				// Valor total do item
				"VL_ITEM" => $this->valor_decimal($itcupom["valortotal"], 2),
				// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
				"CST_ICMS" => $this->csticms($itcupom),
				// Codigo fiscal de operacao e prestacao
				"CFOP" => ($this->csticms($itcupom) == "060" ? "5405" : "5102"),
				// Aliquota da ICMS - carga tributaria efetiva em percentual
				"ALIQ_ICMS" => $this->valor_decimal($itcupom["aliqicms"], 2),
				// Valor do PIS
				"VL_PIS" => $this->valor_decimal($itcupom["totalpis"], 2),
				// Valor da COFINS
				"VL_COFINS" => $this->valor_decimal($itcupom["totalcofins"], 2)
			);
			if($this->gerar_contmatic){
				$codcst = $this->csticms($itcupom);

				$TOT_ECF = "";
				if(in_array($codcst, array("060", "040"))){
					$TOT_ECF = "";
				}elseif($itcupom["aliqicms"] == 25){
					$TOT_ECF = "04";
				}elseif($itcupom["aliqicms"] == 18){
					$TOT_ECF = "01";
				}elseif($itcupom["aliqicms"] == 12){
					$TOT_ECF = "03";
				}elseif($itcupom["aliqicms"] == 07){
					$TOT_ECF = "02";
				}

				$registro = array_merge($registro, array(
					// Codigo da situacao tributaria referente ao PIS/PASEP
					"CST_PIS" => $piscofins["codcst"],
					// Natureza da receita isenta, não alcancada pela incidencia da contribuicao, sujeita a aliquota zero ou venda com suspensao de PIS
					"NAT_REC_PIS" => $this->natreceita($itcupom),
					// Valor da base de calculo do PIS/PASEP
					"VL_BC_PIS" => $this->valor_decimal($itcupom["totalbasepis"], 2),
					// Aliquota de PIS/PASEP (em percentual)
					"ALIQ_PIS" => $this->valor_decimal($itcupom["aliqpis"], 4),
					// Qunatidade - base de calculo PIS/PASEP
					"QUANT_BC_PIS" => "",
					// Aliquota de PIS/PASEP (em reais)
					"ALIQ_PIS_QUANT" => "",
					// Codigo da situacao tributaria referente ao Cofins
					"CST_COFINS" => $piscofins["codcst"],
					// Natureza da receita isenta, não alcancada pela incidencia da contribuicao, sujeita a aliquota zero ou venda com suspensao de Cofins
					"NAT_REC_COFINS" => $this->natreceita($itcupom),
					// Valor da base de calculo do Cofins
					"VL_BC_COFINS" => $this->valor_decimal($itcupom["totalbasecofins"], 2),
					// Aliquota de Cofins (em percentual)
					"ALIQ_COFINS" => $this->valor_decimal($itcupom["aliqcofins"], 4),
					// Qunatidade - base de calculo Cofins
					"QUANT_BC_COFINS" => "",
					// Aliquota de Cofins (em reais)
					"ALIQ_COFINS_QUANT" => "",
					// Codigo da conta analitica contabil debitada/creditada
					"COD_CTA" => "",
					// Movimentacao fisicao do item/produto:
					// 1 - Sim
					// 2 - Nao
					"IND_MOV" => "1",
					// Codigo do totalizador ECF
					"TOT_ECF" => $TOT_ECF,
					// Valor total dos descontos
					"VL_DESC" => $this->valor_decimal($itcupom["desconto"],2),
					// Valor total de outras despesas acessórias e acréscimos
					"VL_OUT_DA" => $this->valor_decimal($itcupom["acrescimo"],2)
				));
			}
			$this->escrever_registro($registro);
		}
	}

	// Registro analitico do movimento diario (codigo 02 e 2D) [nivel 4]
	protected function registro_C490($maparesumo){
		$arr_codcst = array();

		foreach($this->arr_cupom[$maparesumo["codmaparesumo"]] as $cupom){
			foreach($cupom["itcupom"] as $itcupom){
				$codcst = $this->csticms($itcupom);
				$aliqicms = number_format($itcupom["aliqicms"], 2, ".", "");
				$totalbaseicms = (in_array($itcupom["tptribicms"], array("T", "R")) ? $itcupom["valortotal"] : 0);
				$arr_codcst[$codcst][$aliqicms]["valortotal"] += $itcupom["valortotal"];
				$arr_codcst[$codcst][$aliqicms]["totalbaseicms"] += $totalbaseicms;
//				$arr_codcst[$codcst][$aliqicms]["totalicms"] += floor(($totalbaseicms * ($itcupom["aliqicms"] / 100)) * 100) / 100;
			}
		}

		foreach($arr_codcst as $codcst => $arr_aliqicms){
			foreach($arr_aliqicms as $aliqicms => $row){
				$registro = array(
					// Texto fixo contendo "C490"
					"REG" => "C490",
					// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
					"CST_ICMS" => $codcst,
					// Codigo fiscal de operacao e prestacao
					"CFOP" => ($codcst == "060" ? "5405" : "5102"),
					// Aliquota de ICMS
					"ALIQ_ICMS" => $this->valor_decimal($aliqicms, 2),
					// Valor da operacao correspondente a combinacao de CST_ICMS, CFOP e ALIQ_ICMS, incluidas as despesas acessorias e acrescimos
					"VL_OPR" => $this->valor_decimal($row["valortotal"], 2),
					// Valor acumulado da base de calculo do ICMS, referente a combinacao de CST_ICMS, CFOP e ALIQ_ICMS
					"VL_BC_ICMS" => $this->valor_decimal($row["totalbaseicms"], 2),
					// Valor acumulado do ICMS, referente a combinacao de CST_ICMS, CFOP e ALIQ_ICMS
//					"VL_ICMS" => $this->valor_decimal($row["totalicms"],2),
					"VL_ICMS" => $this->valor_decimal(($row["totalbaseicms"] * ($aliqicms / 100)), 2),
					// Codigo da observacao do lancamento fiscal (campo 02 do registro 0460)
					"COD_OBS" => ""
				);
				$this->escrever_registro($registro);
			}
		}
	}

	// [ G E R A R   E S S E   R E G I S T R O   A P E N A S   S E   F O R   D A   B A H I A ]
	// Resumo mensal de itens d ECF por estabelecimento (codigo 02 e 2D) [nivel 2] [NAO GERAR]
	protected function registro_C495(){

	}

	// Nota fiscal/conta de energia eletrica (codigo 06), nota fiscal/conta de fornecimento de agua
	// canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28) [nivel 2]
	protected function registro_C500(){
		foreach($this->arr_notadiversa as $notadiversa){
			if(!in_array($this->valor_natoperacao($notadiversa["tipodocumentofiscal"]), array("06", "29", "28"))){
				continue;
			}
			$registro = array(
				// Texto fixo contendo "C500"
				"REG" => "C500",
				// Indicador do tipo de operacao:
				// 0 - Entrada
				// 1 - Saida
				"IND_OPER" => "0",
				// Indicador de emitente do documento fiscal:
				// 0 - Emissao propria
				// 1 - Emissao de terceiros
				"IND_EMIT" => "1",
				// Codigo do participante (campo 02 do registro 0150)
				// Do adquirente no caso de saida
				// Do fornecedor no caso de entrada
				"COD_PART" => $notadiversa["tipoparceiro"].$notadiversa["codparceiro"],
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => $notadiversa["tipodocumentofiscal"],
				// Codigo da situacao do modelo fiscal, conforme a tabela 4.1.2
				"COD_SIT" => "00",
				// Serie do documento fiscal
				"SER" => $notadiversa["serie"],
				// Sub serie do documento fiscal
				"SUB" => "",
				// Codigo de classe de consumo de energia eletrica ou gas
				// 01 - Comercial
				// 02 - Consumo proprio
				// 03 - Iluminacao publica
				// 04 - Industrial
				// 05 - Poder publico
				// 06 - Residencial
				// 07 - Rural
				// 08 - Servico publico
				"COD_CONS" => "01",
				// Numero do documento fiscal
				"NUM_DOC" => $notadiversa["numnotafis"],
				// Data de emissao da nota
				"DT_DOC" => $this->valor_data($notadiversa["dtemissao"]),
				// Data da entrada ou saida da nota
				"DT_E_S" => $this->valor_data($notadiversa["dtemissao"]),
				// Valor total do documento fiscal
				"VL_DOC" => $this->valor_decimal($notadiversa["totalbruto"], 2),
				// Valor total do desconto
				"VL_DESC" => $this->valor_decimal(0, 2),
				// Valor total fornececido/consumido
				"VL_FORN" => $this->valor_decimal($notadiversa["totalbruto"], 2),
				// Valor total dos servicos nao-tributados pelo ICMS
				"VL_SERV_NT" => $this->valor_decimal(($notadiversa["totalbruto"] - $notadiversa["totalbaseicms"]), 2),
				// Valor total cobrado em nome de terceiros
				"VL_TERC" => $this->valor_decimal(0, 2),
				// Valor total de despesas acessorias indicadas no documento fiscal
				"VL_DA" => $this->valor_decimal(0, 2),
				// Valor acumuldado da base de calculo do ICMS
				"VL_BC_ICMS" => $this->valor_decimal($notadiversa["baseicms"], 2),
				// Valor acumulado do ICMS
				"VL_ICMS" => $this->valor_decimal($notadiversa["totalicms"], 2),
				// Valor acumuldado da base de calculo do ICMS substituicao tributaria
				"VL_BC_ICMS_ST" => $this->valor_decimal(0, 2),
				// Valor acumulado do ICMS substituicao tributaria
				"VL_ICMS_ST" => $this->valor_decimal(0, 2),
				// Codigo da informacao complementar do documento fiscal (campo 02 do Registro 0450)
				"COD_INF" => "",
				// Valor total do PIS/PASEP
				"VL_PIS" => $this->valor_decimal($notadiversa["totalpis"], 2),
				// Valor total do COFINS
				"VL_COFINS" => $this->valor_decimal($notadiversa["totalcofins"], 2),
				// Codigo do tipo de ligacao:
				// 1 - Monofasico
				// 2 - Bifasico
				// 3 - Trifasico
				"TP_LIGACAO" => "",
				// Codigo do grupo de tensao:
				// 01 - A1  - Alta tensao (230kV ou mais)
				// 02 - A2  - Alta tensao (88kV a 138kV)
				// 03 - A3  - Alta tensao (69kV)
				// 04 - A3a - Alta tensao (30kV a 44kV)
				// 05 - A4  - Alta tensao (2,3kV a 25kV)
				// 06 - AS  - Alta tensao subterraneo 06
				// 07 - B1  - Residencial
				// 08 - B1  - Residencial baixa renda
				// 09 - B2  - Rural
				// 10 - B2  - Cooperativa de eletrificacao rural
				// 11 - B2  - Servico publico de irrigacao
				// 12 - B3  - Demais classes
				// 13 - B4a - Iluminacao publica (rede de distribuicao)
				// 14 - B4b - Iluminacao publica (bulbo de lampada)
				"COD_GRUPO_TENSAO" => ""
			);
			$this->escrever_registro($registro);
			$this->registro_C510();
			$this->registro_C590($notadiversa);
		}
	}

	// Itens do documento nota fiscal/conta de energia eletrica (codigo 06), nota fiscal/conta de fornecimento
	// de agua canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28) [nivel 3] [NAO GERAR]
	protected function registro_C510(){
		return array();
	}

	// Registro analitico do documento - nota fiscal/conta de energia eletrica (codigo 06), nota fiscal/conta de
	// fornecimento de agua canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28) [nivel 3]
	protected function registro_C590($notadiversa){
		$natoperacao = $this->arr_natoperacao[$notadiversa["natoperacao"]];
		if($natoperacao["geracsticms090"] == "S"){
			$cst_icms = "090";
		}else{
			$cst_icms = $notadiversa["aliqicms"] > 0 ? "000" : "040";
		}
		$registro = array(
			// Texto fixo contendo "C590"
			"REG" => "C590",
			// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
			"CST_ICMS" => $cst_icms,
			// Codigo fiscal de operacao e prestacao do agrupamento de itens
			"CFOP" => $this->valor_natoperacao($notadiversa["natoperacao"]),
			// Aliquota de ICMS
			"ALIQ_ICMS" => $this->valor_decimal($notadiversa["aliqicms"], 2),
			// Valor da operacao correspondente a combinacao de CST_ICMS, CFOP e aliquota do ICMS
			"VL_OPR" => $this->valor_decimal($notadiversa["totalbruto"], 2),
			// Parcela correspondente ao "valor da base de calculo de ICMS" referente a combinacao de CST_ICMS, CFOP e aliquota	do ICMS
			"VL_BC_ICMS" => $this->valor_decimal($notadiversa["baseicms"], 2),
			// Parcela correspondente ao "valor do ICMS" referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
			"VL_ICMS" => $this->valor_decimal($notadiversa["totalicms"], 2),
			// Parcela correspondente ao "valor da base de calculo ICMS da substituicao tributaria" referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
			"VL_BC_ICMS_ST" => $this->valor_decimal(0, 2),
			// Parcela correspondente ao "valor creditado/debitado do ICMS da substituicao tributaria" referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
			"VL_ICMS_ST" => $this->valor_decimal(0, 2),
			// Valor nao tributado em funcao da reducao de base de calculo do ICMS, referente a combinacao de CST_ICMS, CFOP e aliquota de ICMS
			"VL_RED_BC" => $this->valor_decimal(0, 2),
			// Codigo da observacao do lancamento fiscal (campo 02 do regiistro 0460)
			"COD_OBS" => ""
		);
		$this->escrever_registro($registro);
	}

	// Consolidacao diaria de notas fiscais/contas de energia eletrica (codigo 06), nota fiscal/conta de
	// fornecimento de agua canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28)
	// (empresas nao obrigadas ao convenio ICMS 115/03) [nivel 2] [NAO GERAR]
	protected function registro_C600(){
		$this->registro_C601();
		$this->registro_C610();
		$this->registro_C690();
	}

	// Documentos cancelados - consolidacao diaria de notas fiscais/contas de energia eletrica (codigo 06), nota fiscal/conta de
	// fornecimento de agua canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28) [nivel 2] [NAO GERAR]
	protected function registro_C601(){

	}

	// Itens do documento consolidado (codigo 06), nota fiscal/conta de fornecimento de agua canalizada (codigo 29) e
	// notafiscal de consumo fornecimento de gas (codigo 28) (empresas nao obrigadas ao convenio ICMS 115/03) [nivel 2] [NAO GERAR]
	protected function registro_C610(){

	}

	// Registro analitico dos documentos de notas fiscais/contas de energia eletrica (codigo 06), nota fiscal/conta de
	// fornecimento de agua canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28)
	// (empresas nao obrigadas ao convenio ICMS 115/03) [nivel 2] [NAO GERAR]
	protected function registro_C690(){

	}

	// Consolidacao dos documentos nota fiscal/conta de energia eletrica (codigo 06), emitidas em via unica (empresas
	// obrigadas a entrega do arquivo previsto no convenio ICMS 115/03) e nota fiscal/conta de fornecimento de
	// gas canalizado [nivel 2] [NAO GERAR]
	protected function registro_C700(){
		$this->registro_C790();
	}

	// Registro analitico dos documentos (codigo 06) [nivel 3] [NAO GERAR]
	protected function registro_C790(){
		$this->registro_C791();
	}

	// Registro de informacoes de ST po UF (codigo 06) [nivel 4] [NAO GERAR]
	protected function registro_C791(){

	}

	// Cupom fiscal eletronico - SAT (CF-E-SAT) (codigo 59) [nivel 2]
	protected function registro_C800(){
		foreach($this->arr_cupom["SAT"] as $cupom){
			$ecf = $this->arr_ecf[$cupom["codecf"]];
			if($ecf["equipamentofiscal"] !== "SAT"){
				continue;
			}

			$totalicms = 0;
			foreach($cupom["itcupom"] as $itcupom){
				if($itcupom["tptribicms"] === "T"){
					$totalicms += round(($itcupom["valortotal"] * $itcupom["aliqicms"] / 100), 2);
				}
			}

			if($cupom["status"] === "C"){
				$cod_sit = "02";
			}else{
				$cod_sit = "00";
			}
			$registro = array(
				// Texto fixo contendo "C800"
				"REG" => "C800",
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => "59",
				// Codigo da situacao do documento fiscal, conforme a tabela 4.2.1
				"COD_SIT" => $cod_sit,
				// Numero do cupom fiscal eletronico
				"NUM_CFE" => $cupom["seqecf"],
				// Data da emissao do cupom fiscal eletronico
				"DT_DOC" => $this->valor_data($cupom["dtmovto"]),
				// Valor total do cupom fiscal eletronico
				"VL_CFE" => $this->valor_decimal($cupom["totalliquido"], 2),
				// Valor total do PIS
				"VL_PIS" => $this->valor_decimal($cupom["totalpis"], 2),
				// Valor total do Cofins
				"VL_COFINS" => $this->valor_decimal($cupom["totalcofins"], 2),
				// CNPJ ou CPF do destinatario
				"CPNJ_CPF" => removeformat($cupom["cpfcnpj"]),
				// Numero de serie do equipamento SAT
				"NR_SAT" => substr($cupom["chavecfe"], 22, 9), //$ecf["numfabricacao"],
				// Chave do cupom fiscal eletronico
				"CHV_CFE" => $cupom["chavecfe"],
				// Valor total de descontos
				"VL_DESC" => $this->valor_decimal($cupom["totaldesconto"], 2),
				// Valor total das mercadorias e servicos
				"VL_MERC" => $this->valor_decimal($cupom["totalbruto"]),
				// Valor total de outras despesas acessorias e acrescimos
				"VL_OUT_DA" => $this->valor_decimal($cupom["totalacrescimo"], 2),
				// Valor do ICMS
				"VL_ICMS" => $this->valor_decimal($totalicms, 2),
				// Valor total do PIS retido por substituicao tributaria
				"VL_PIS_ST" => $this->valor_decimal(0, 2),
				// Valor total do Cofins retido por substituicao tributaria
				"VL_COFINS_ST" => $this->valor_decimal(0, 2)
			);
			$this->escrever_registro($registro);
			$this->registro_C850($cupom["itcupom"]);
		}
	}

	// Registro analitico do CF-E-SAT (codigo 59) [nivel 3]
	protected function registro_C850($arr_itcupom){
		$arr_analitico = array();
		foreach($arr_itcupom as $itcupom){
			$csticms = $this->csticms($itcupom);
			$natoperacao = $this->valor_natoperacao($itcupom["tptribicms"] === "F" ? "5.405" : "5.102");
			$aliqicms = $this->valor_decimal($itcupom["aliqicms"], 2);
			$index = "{$csticms};{$natoperacao};{$aliqicms}";
			$arr_analitico[$index]["valortotal"] += $itcupom["valortotal"];
			if($itcupom["aliqicms"] > 0){
				$arr_analitico[$index]["totalbaseicms"] += $itcupom["valortotal"];
				$arr_analitico[$index]["totalicms"] += round(($itcupom["valortotal"] * $itcupom["aliqicms"] / 100), 2);
			}
		}
		foreach($arr_analitico as $index => $analitico){
			$arr_index = explode(";", $index);
			$csticms = $arr_index[0];
			$natoperacao = $arr_index[1];
			$aliqicms = $arr_index[2];

			$registro = array(
				// Texto fico contendo "C850"
				"REG" => "C850",
				// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
				"CST_ICMS" => $csticms,
				// Codigo fiscal de operacao e prestacao do agrupamento de itens
				"CFOP" => $natoperacao,
				// Aliquota do ICMS
				"ALIQ_ICMS" => $aliqicms,
				// Valor total do CF-e na combinacao de CST_ICMS, CFOP e aliquota do ICMS,
				// correspondente ao somatorio do valor liquido dos itens
				"VL_OPR" => $this->valor_decimal($analitico["valortotal"], 2),
				// Valor acumulado da base de calculo do ICMS, referente a combinacao de
				// CST_ICMS, CFOP e aliquota do ICMS
				"VL_BC_ICMS" => $this->valor_decimal($analitico["totalbaseicms"], 2),
				// Parcela correspondente ao valor do ICMS, referente a combinacao de
				// CST_ICMS, CFOP e aliquota do ICMS
				"VL_ICMS" => $this->valor_decimal($analitico["totalicms"], 2),
				// Codigo da observacao do lancamento fiscal (campo 02 do registro 0460)
				"COD_OBS" => NULL
			);
			$this->escrever_registro($registro);
		}
	}


	// Identificacao do equipamento SAT-CF-E [nivel 2]
	protected function registro_C860(){
		$query  = "select ecf.numfabricacao, cupom.dtmovto, cupom.caixa, ";
		$query .= "min(cupom.seqecf) AS operacaoini, max(cupom.seqecf) AS operacaofim ";
		$query .= "from cupom ";
		$query .= "LEFT JOIN ecf on (ecf.caixa = cupom.caixa) ";
		$query .= "where length(cupom.chavecfe) > 0 AND cupom.dtmovto >= '$this->datainicial' AND cupom.dtmovto <= '$this->datafinal' ";
		$query .= "AND cupom.codestabelec = {$this->estabelecimento->getcodestabelec()} ";
		foreach($this->arr_filtro as $coluna => $valor){
			if($coluna == "dtentregaini"){
				$query .= "	AND cupom.dtmovto >= '".$valor."' ";
			}elseif($coluna == "dtentregafim"){
				$query .= "	AND cupom.dtmovto <= '".$valor."' ";
			}
		}

		$query .= "GROUP BY ecf.numfabricacao, cupom.dtmovto, cupom.caixa ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach($arr as $row){
			$registro = array(
				// Texto fixo contendo "C860"
				"REG" => "C860",
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => "59",
				// Numero de serie do equipamento SAT
				"NR_SAT" => $row["numfabricacao"],
				// Data de emissao dos documentos fiscais
				"DT_DOC" => $this->valor_data($row["dtmovto"]),
				// Numero do documento inicial
				"DOC_INI" => $row["operacaoini"],
				// Numero do documento final
				"DOC_FIM" => $row["operacaofim"]
			);
			$this->escrever_registro($registro);
			$this->registro_C890($row);
		}
	}

	// Resumo diario do CF-E-SAT (codigo 59) por equipamento SAT-CF-E
	protected function registro_C890($cupom){
		$arr_analitico = array();
		$query  = "SELECT itcupom.* ";
		$query .= "FROM itcupom ";
		$query .= "INNER JOIN cupom on (cupom.idcupom = itcupom.idcupom) ";
		$query .= "WHERE cupom.dtmovto = '{$cupom["dtmovto"]}' AND cupom.codestabelec = {$this->estabelecimento->getcodestabelec()} ";
		$query .= " AND cupom.caixa = '{$cupom["caixa"]}' ";
		//echo $query;
		$res = $this->con->query($query);
		$arr_itcupom = $res->fetchAll();

		foreach($arr_itcupom as $itcupom){
			$csticms = $this->csticms($itcupom);
			$natoperacao = $this->valor_natoperacao($itcupom["tptribicms"] === "F" ? "5.405" : "5.102");
			$aliqicms = $this->valor_decimal($itcupom["aliqicms"], 2);
			$index = "{$csticms};{$natoperacao};{$aliqicms}";
			$arr_analitico[$index]["valortotal"] += $itcupom["valortotal"];
			$arr_analitico[$index]["totalbaseicms"] += $itcupom["valortotal"];
			$arr_analitico[$index]["totalicms"] += $itcupom["valortotal"] * $itcupom["aliqicms"] / 100;
		}

		foreach($arr_analitico as $index => $analitico){
			$arr_index = explode(";", $index);
			$csticms = $arr_index[0];
			$natoperacao = $arr_index[1];
			$aliqicms = $arr_index[2];

			$registro = array(
				// Texto fico contendo "C890"
				"REG" => "C890",
				// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
				"CST_ICMS" => $csticms,
				// Codigo fiscal de operacao e prestacao do agrupamento de itens
				"CFOP" => $natoperacao,
				// Aliquota do ICMS
				"ALIQ_ICMS" => $aliqicms,
				// Valor total do CF-e na combinacao de CST_ICMS, CFOP e aliquota do ICMS,
				// correspondente ao somatorio do valor liquido dos itens
				"VL_OPR" => $this->valor_decimal($analitico["valortotal"], 2),
				// Valor acumulado da base de calculo do ICMS, referente a combinacao de
				// CST_ICMS, CFOP e aliquota do ICMS
				"VL_BC_ICMS" => $this->valor_decimal($analitico["totalbaseicms"], 2),
				// Parcela correspondente ao valor do ICMS, referente a combinacao de
				// CST_ICMS, CFOP e aliquota do ICMS
				"VL_ICMS" => $this->valor_decimal($analitico["totalicms"], 2),
				// Codigo da observacao do lancamento fiscal (campo 02 do registro 0460)
				"COD_OBS" => NULL
			);
			$this->escrever_registro($registro);
		}
	}

	// Encerramento do bloco C [nivel 1]
	protected function registro_C990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "C"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "C990"
			"REG" => "C990",
			// Quantidade de linhas do bloco C
			"QTD_LIN_C" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco D [nivel 1]
	protected function registro_D001(){
		$ind_mov = "1";
		if(count($this->arr_notafrete) > 0){
			$ind_mov = "0";
		}

		foreach($this->arr_notadiversa as $notadiversa){
			if(in_array($notadiversa["tipodocumentofiscal"], array("22", "21"))){
				$ind_mov = "0";
				break;
			}
		}

		$registro = array(
			// Texto fixo contendo "D001"
			"REG" => "D001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => $ind_mov
		);
		$this->escrever_registro($registro);
		$this->registro_D100();
		$this->registro_D500();
	}

	// Nota fiscal de servico de transporte (codigo 07) e conhecimento de transporte rodoviario de
	// cargas (codigo 08), conhecimentos de transporte de cargas avulso (codigo 8B), aquaviario de
	// (codigo 09), aereo (codigo 10), ferroviario de cargas (codigo 11) e multimodal de cargas
	// (codigo 26), nota fiscal de transporte ferroviario de cargas (codigo 27) e conhecimento de
	// de transporte eletronico - CT-e (codigo 57) [nivel 2]
	protected function registro_D100(){
		foreach($this->arr_notafrete as $notafrete){
			if(strlen($notafrete["chavecte"]) > 0){
				$cod_mod = "57";
			}else{
				$cod_mod = "08";
			}

			$registro = array(
				// Texto fixo contendo "D100"
				"REG" => "D100",
				// Indicador do tipo de operacao:
				// 0 - Aquisicao
				// 1 - Prestacao
				"IND_OPER" => "0",
				// Indicador do emitente do documento fiscal:
				// 0 - Emissao propria
				// 1 - Terceiros
				"IND_EMIT" => "1",
				// Codigo do participante (campo 02 do registro 0150):
				// - do prestados de servico, no caso de aquisicao de servico
				// - do tomador de servico, no caso de prestacao de servico
				"COD_PART" => "T".$notafrete["codtransp"],
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => $cod_mod,
				// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
				"COD_SIT" => "00",
				// Serie do documento fiscal
				"SER" => $notafrete["serie"],
				// Subserie do documento fiscal
				"SUB" => "",
				// Numero do documento fiscal
				"NUM_DOC" => $notafrete["numnotafis"],
				// Chave do conhecimento de transporte eletronico
				"CHV_CTE" => $notafrete["chavecte"],
				// Data da emissao do documento fiscal
				"DT_DOC" => $this->valor_data($notafrete["dtemissao"]),
				// Data da aquisicao ou da prestacao do servico
				"DT_A_P" => $this->valor_data($notafrete["dtentrega"]),
				// Tipo de conhecimento de transporte eletronico conforme definido no manual
				// de integração do CT-e
				"TP_CT-e" => "",
				// Chave do CT-e de referencia cujos valores foram complementados (opcao "1"
				// do campo anterior) ou cujo debito foi anulado (opcao "2" do campo anterior)
				"CHV_CTE_REF" => "",
				// Valor total do documento fiscal
				"VL_DOC" => $this->valor_decimal($notafrete["totalliquido"], 2),
				// Valor total do desconto
				"VL_DESC" => "",
				// Indicador do tipo de frete:
				// 0 - Por conta do emitente
				// 1 - Por conta do destinatario/remetente
				// 2 - Por conta de terceiros
				// 9 - Sem cobranca de frete
				"IND_FRT" => "1",
				// Valor total da prestacao de servico
				"VL_SERV" => $this->valor_decimal($notafrete["outrasdespesas"], 2),
				// Valor da base de calculo de ICMS
				"VL_BC_ICMS" => $this->valor_decimal($notafrete["totalbaseicms"], 2),
				// Valor do ICMS
				"VL_ICMS" => $this->valor_decimal($notafrete["totalicms"], 2),
				// Valor nao-tributado
				"VL_NT" => $this->valor_decimal($notafrete["totalbaseisento"], 2),
				// Codigo da informacao complementar do documento fiscal (campo 02 do registro 0450)
				"COD_INF" => "",
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => ""
			);
			$this->escrever_registro($registro);
			$this->registro_D190($notafrete);
		}
	}

	// Registro analitico dos documentos (codigo 07, 08, 8B, 09, 10, 11, 26, 27 e 57) [nivel 3]
	protected function registro_D190($notafrete){
		$natoperacao = $this->arr_natoperacao[$notafrete["natoperacao"]];
		if($natoperacao["geracsticms090"] == "S"){
			$cst_icms = "090";
		}else{
			if($notafrete["aliqicms"] == 0){
				$cst_icms = "040";
			}elseif($notafrete["totalbaseisento"] > 0 || $notafrete["totalbaseoutras"] > 0){
				$cst_icms = "020";
			}else{
				$cst_icms = "000";
			}
		}
		$registro = array(
			// Texto fixo contendo "D190"
			"REG" => "D190",
			// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
			"CST_ICMS" => $cst_icms,
			// Codigo fiscal de operacao e prestacao, conforme a tabela indicada no item 4.2.2
			"CFOP" => $this->valor_natoperacao($notafrete["natoperacao"]),
			// Aliquta de ICMS
			"ALIQ_ICMS" => $this->valor_decimal($notafrete["aliqicms"], 2),
			// Valor da operacao correspondente a combinacao CST_ICMS, CFOP e aliquota de ICMS
			"VL_OPR" => $this->valor_decimal($notafrete["totalliquido"], 2),
			// Parcela correspondente ao "valor da base de calculo do ICMS" referente
			// a combinacao CST_ICMS, CFOP e aliquota de ICMS
			"VL_BC_ICMS" => $this->valor_decimal($notafrete["totalbaseicms"], 2),
			// Valor da operacao correspondente ao "valor do ICMS" referente
			// a combinacao CST_ICMS, CFOP e aliquota de ICMS
			"VL_ICMS" => $this->valor_decimal($notafrete["totalicms"], 2),
			// Valor não tributado em funcao da reducao da base de calculo do ICMS, referente
			// a combinacao CST_ICMS, CFOP e aliquota de ICMS
			"VL_RED_ICMS" => $this->valor_decimal($notafrete["totalbaseisento"], 2),
			// Codigo da observacao do lancamento fiscal (campo 02 do registro 0460)
			"COD_OBS" => ""
		);
		$this->escrever_registro($registro);
	}

	// Nota fiscal de servico de comunicacao (codigo 21) e nota fiscal de servico de telecomunicacao (codigo 22) [nivel 2]
	protected function registro_D500(){
		foreach($this->arr_notadiversa as $notadiversa){
			if(in_array($notadiversa["tipodocumentofiscal"], array("22", "21"))){

				$registro = array(
					// Texto fixo contendo "D500
					"REG" => "D500",
					// Indicador do tipo de operacao:
					// 0 - Aquisicao
					// 1 - Prestacao
					"IND_OPER" => "0",
					// Indicador do emitente do documento fiscal:
					// 0 - Emissao propria
					// 1 - Terceiros
					"IND_EMIT" => "1",
					// Codigo do participante (campo 02 do registro 0150):
					// - do prestador do servico, no caso de aquisicao
					// - do tomador do servico, no caso de prestacao
					"COD_PART" => $notadiversa["tipoparceiro"].$notadiversa["codparceiro"],
					// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => $notadiversa["tipodocumentofiscal"],
					// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
					"COD_SIT" => "00",
					// Serie do documento fiscal
					"SER" => $notadiversa["serie"],
					// Subserie do documento fiscal
					"SUB" => "",
					// Numero do documento fiscal
					"NUM_DOC" => $notadiversa["numnotafis"],
					// Data de emissao do documento fiscal
					"DT_DOC" => $this->valor_data($notadiversa["dtemissao"]),
					// Data da entrada (aquisicao) ou saida (prestacao do servico)
					"DT_A_P" => $this->valor_data($notadiversa["dtemissao"]),
					// Valor total do documento fiscal
					"VL_DOC" => $this->valor_decimal($notadiversa["totalliquido"], 2),
					// Valor total do desconto
					"VL_DESC" => $this->valor_decimal(0, 2),
					// Valor da prestacao de servico
					"VL_SERV" => $this->valor_decimal(0, 0),
					// Valor total dos servicos nao-tributados pelo ICMS
					"VL_SERV_NT" => $this->valor_decimal(0, 0),
					// Valorer cobrados em nome de terceiros
					"VL_TERC" => $this->valor_decimal(0, 0),
					// Valor das outras despesas indicadas no documento fiscal
					"VL_DA" => $this->valor_decimal($notadiversa["totalbaseoutras"], 2),
					// Valor da base de calculo do ICMS
					"VL_BC_ICMS" => $this->valor_decimal($notadiversa["baseicms"], 2),
					// Valor do ICMS
					"VL_ICMS" => $this->valor_decimal($notadiversa["totalicms"], 2),
					// Codigo da informacao complementar (campo 02 do registro 0450)
					"COD_INF" => "",
					// Valor do PIS
					"VL_PIS" => $this->valor_decimal($notadiversa["totalpis"], 2),
					// Valor da COFINS
					"VL_COFINS" => $this->valor_decimal($notadiversa["totalcofins"], 2),
					// Codigo da conta analitica contabil debitada/creditada
					"COD_CTA" => "",
					// Codigo do tipo de assinante:
					// 1 - Comercial/industrial
					// 2 - Poder publico
					// 3 - Residencial/pessoa fisica
					// 4 - Publico
					// 5 - Semi-publico
					// 6 - Outros
					"TP_ASSINANTE" => "1"
				);
				$this->escrever_registro($registro);
				$this->registro_D590($notadiversa);
			}
		}
	}

	// Registro analitico do documento (codigo 21 e 22)
	protected function registro_D590($notadiversa){
		$natoperacao = $this->arr_natoperacao[$notadiversa["natoperacao"]];
		if($natoperacao["geracsticms090"] == "S"){
			$cst_icms = "090";
		}else{
			$cst_icms = "000";
		}

		$registro = array(
			// Texto fixo contendo "D590"
			"REG" => "D590",
			// Codigo da situacao tributaria, conforme a tabela indicada no item 4.3.1
			"CST_ICMS" => $cst_icms,
			// Codigo fiscal de operacao de prestacao, conforme a tabela indicada no item 4.2.2
			"CFOP" => $this->valor_natoperacao($notadiversa["natoperacao"]),
			// Aliquota de ICMS
			"ALIQ_ICMS" => $this->valor_decimal($notadiversa["aliqicms"], 2),
			// Valor da operacao correspondente a combinacao de CST_ICMS, CFOP, e aliquota de ICMS, incluidas as despesas acessorias e acrescimos
			"VL_OPR" => $this->valor_decimal($notadiversa["totalliquido"], 2),
			// Parcela correspondente ao "valor da base de calculo de ICMS" referente a combinacao CST_ICMS, CFOP, e aliquota do ICMS
			"VL_BC_ICMS" => $this->valor_decimal($notadiversa["baseicms"], 2),
			// Parcela correspondente ao "valor do ICMS" referente a combinacao CST_ICMS, CFOP, e aliquota do ICMS
			"VL_ICMS" => $this->valor_decimal($notadiversa["totalicms"], 2),
			// Parcela correspondente ao "valor da base de calculo de ICMS de outras UF" referente a combinacao CST_ICMS, CFOP, e aliquota do ICMS
			"VL_BC_ICMS_UF" => $this->valor_decimal(0, 2),
			// Parcela correspondente ao "valor do ICMS de outras UF" referente a combinacao CST_ICMS, CFOP, e aliquota do ICMS
			"VL_ICMS_UF" => $this->valor_decimal(0, 2),
			// Valor nao tributado em funcao da reducao da base de calculo de ICMS, referente a combinacao CST_ICMS, CFOP, e aliquota do ICMS
			"VL_RED_BC" => $this->valor_decimal(0, 2),
			// Codigo da observacao (campo 02 do registro 0460)
			"COD_OBS" => ""
		);
		$this->escrever_registro($registro);
	}

	// Encerramento do bloco D [nivel 1]
	protected function registro_D990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "D"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "D990"
			"REG" => "D990",
			// Quantidade de linhas do bloco D
			"QTD_LIN_D" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco E [nivel 1]
	protected function registro_E001(){
		$registro = array(
			// Texto fixo contendo "E001"
			"REG" => "E001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "0"
		);
		$this->escrever_registro($registro);
		$this->registro_E100();
		$this->registro_E200();
		//$this->registro_E500();
	}

	// Periodo da apuracao do ICMS [nivel 2]
	protected function registro_E100(){
		$registro = array(
			// Texto fixo contendo "E100"
			"REG" => "E100",
			// Data incial a que a apuracao se refere
			"DT_INI" => $this->valor_data($this->datainicial),
			// Data final em que a apuracao se refere
			"DT_FIN" => $this->valor_data($this->datafinal)
		);
		$this->escrever_registro($registro);
		$this->registro_E110();
	}

	// Apuracao do ICMS - Operacoes proprias [nivel 3]
	protected function registro_E110(){
		$credito = 0; // Total de credito
		$debito = 0; // Total de debito
		$est_credito = 0; // Total de estorno de credito
		$est_debito = 0; // Total de estorno de debito
		$vl_aj_creditos = 0;
		$vl_tot_aj_debitos = 0;
		foreach($this->arr_notafiscal as $notafiscal){
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$cst = $this->csticms($itnotafiscal);
				$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
				if(in_array(substr($cst, 1, 2), array("00", "20")) || $natoperacao["aprovicmsprop"] == "S"){
					$valor_icms = $itnotafiscal["totalicms"];
				}else{
					$valor_icms = 0;
				}
				$natoperacao = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
				if((in_array(substr($natoperacao, 0, 1), array("1", "2", "3")) || $natoperacao == "5605") && $natoperacao != "1605"){
					$credito += $valor_icms;
				}elseif(in_array(substr($natoperacao, 0, 1), array("5", "6", "7")) || $natoperacao == "1605"){
					$debito += $valor_icms;
				}
			}
		}

		// Notas diversas
		foreach($this->arr_notadiversa as $notadiversa){
			$credito += $notadiversa["totalicms"];
		}

		// Notas de frete
		foreach($this->arr_notafrete as $notafrete){
			$credito += $notafrete["totalicms"];
		}

		// Notas de complemento
		foreach($this->arr_notacomplemento as $notacomplemento){
			if($notacomplemento["status"] == "C"){
				continue;
			}
			$credito += $notacomplemento["totalicms"];
		}

		// Calcula o debito da mesma maneira que e calculado VL_ICMS no registro C490
		$arr = array();
		foreach($this->arr_cupom as $codmaparesumo => $arr_cupom){
			foreach($arr_cupom as $cupom){
				foreach($cupom["itcupom"] as $itcupom){
					$codcst = $this->csticms($itcupom);
					$aliqicms = number_format($itcupom["aliqicms"], 2, ".", "");
					$arr[$codmaparesumo][$codcst][$aliqicms] += (in_array($itcupom["tptribicms"], array("T", "R")) ? $itcupom["valortotal"] : 0);
				}
			}
		}
		foreach($arr as $arr2){
			foreach($arr2 as $arr3){
				foreach($arr3 as $aliqicms => $totalbaseicms){
					$debito += round($totalbaseicms * ($aliqicms / 100), 2);
				}
			}
		}

		// Notas de outros credito e outros debitos
		$alteracaotributaria_credito = 0;
		$alteracaotributaria_debito = 0;
		foreach($this->arr_outroscreditodebito AS $outroscreditodebito){
			if($outroscreditodebito["tipo"] == "OC"){
				$vl_tot_aj_creditos += $outroscreditodebito["valor"];
			}else{
				$vl_tot_aj_debitos += $outroscreditodebito["valor"];
			}
		}

		// Calcula o debito e credito sobre as alteracoes tributarias
		if($this->gerar_alteracaotributaria){
			foreach($this->arr_produto as $produto){
				if($produto["alteracaotributaria"] !== "S"){
					continue;
				}
				if($produto["tptribicmsnew"] === "F"){
					$aliqicms = $produto["aliqicmsold"] * (1 - $produto["redicmsold"] / 100);
				}else{
					$aliqicms = $produto["aliqicmsnew"] * (1 - $produto["redicmsnew"] / 100);
				}

				$credito_ou_debito = $produto["saldo"] * $produto["custosemimp"] * $aliqicms / 100;
				$credito_ou_debito = round($credito_ou_debito, 2);

				if($produto["tptribicmsnew"] === "F"){
					$alteracaotributaria_debito += $credito_ou_debito;
				}else{
					$alteracaotributaria_credito += $credito_ou_debito;
				}
			}
		}

		$this->debitoicms = $debito;

		$vl_tot_debitos = $debito;
		$vl_aj_debitos = $alteracaotributaria_debito;
		$vl_estornos_cred = $est_credito;
		$vl_tot_creditos = $credito;
		$vl_tot_aj_creditos += $alteracaotributaria_credito;
		$vl_tot_aj_debitos += $alteracaotributaria_debito;
		$vl_estornos_deb = $est_debito;
		$vl_sld_credor_ant = $this->creditoicms;
		$vl_sld_apurado = ($vl_tot_debitos + $vl_aj_debitos + $vl_tot_aj_debitos + $vl_estornos_cred) - ($vl_tot_creditos + $vl_aj_creditos + $vl_tot_aj_creditos + $vl_estornos_deb + $this->creditoicms);
		$vl_tot_ded = 0;
		if($vl_sld_apurado >= 0){
			$vl_sld_credor_transportar = 0;
		}else{
			$vl_sld_credor_transportar = abs($vl_sld_apurado);
			$vl_sld_apurado = 0;
		}
		$vl_icms_recolher = $vl_sld_apurado - $vl_tot_ded;
		if($vl_icms_recolher < 0){
			$vl_sld_credor_transportar = abs($vl_icms_recolher);
			$vl_icms_recolher = 0;
		}
		$deb_esp = 0;

		$registro = array(
			// Texto fixo contendo "E110"
			"REG" => "E110",
			// Valor total dos debitos por saidas e prestacoes com debito do imposto
			"VL_TOT_DEBITOS" => $this->valor_decimal($vl_tot_debitos, 2),
			// Valor total dos ajustes a debito decorrentes do documento fiscal
			"VL_AJ_DEBITOS" => $this->valor_decimal($vl_aj_debitos, 2),
			// Valor total de ajustes e debitos
			"VL_TOT_AJ_DEBITOS" => $this->valor_decimal($vl_tot_aj_debitos, 2),
			// Valor total de ajustes "estornos de creditos"
			"VL_ESTORNOS_CRED" => $this->valor_decimal($vl_estornos_cred, 2),
			// Valor total dos creditos por entradas e aquisicoes com credito do imposto
			"VL_TOT_CREDITOS" => $this->valor_decimal($vl_tot_creditos, 2),
			// Valor total dos ajustes de credito decorrentes do documento fiscal
			"VL_AJ_CREDITO" => $this->valor_decimal($vl_aj_creditos, 2),
			// Valor total de ajuste a credito
			"VL_TOT_AJ_CREDITOS" => $this->valor_decimal($vl_tot_aj_creditos, 2),
			// Valor total de ajustes "estornos de debitos"
			"VL_ESTORNOS_DEB" => $this->valor_decimal($vl_estornos_deb, 2),
			// Valor total de saldo credor do periodo anterior
			"VL_SLD_CREDOR_ANT" => $this->valor_decimal($vl_sld_credor_ant, 2),
			// Valor do saldo devedor apurado
			"VL_SLD_APURADO" => $this->valor_decimal($vl_sld_apurado, 2),
			// Valor total de deducoes
			"VL_TOT_DED" => $this->valor_decimal($vl_tot_ded, 2),
			// Valor total de ICMS a recolher
			"VL_ICMS_RECOLHER" => $this->valor_decimal($vl_icms_recolher, 2),
			// Valor total de saldo credor a transportar para o periodo seguinte
			"VL_SLD_CREDOR_TRANSPORTAR" => $this->valor_decimal($vl_sld_credor_transportar, 2),
			// Valores recolhidos ou a recolher, extra-apuracao
			"DEB_ESP" => $this->valor_decimal($deb_esp, 2)
		);
		$this->escrever_registro($registro);
		if($alteracaotributaria_debito > 0){
			$this->registro_E111($alteracaotributaria_debito, NULL);
		}

		if($alteracaotributaria_credito > 0){
			$this->registro_E111(NULL, $alteracaotributaria_credito);
		}

		$this->registro_E111(null, null);

		$this->registro_E115();
		$this->registro_E116($vl_icms_recolher);
	}

	// Ajuste/beneficio/incentivo da apuracao do ICMS [nivel 4]
	protected function registro_E111($alteracaotributaria_debito, $alteracaotributaria_credito){
		if($alteracaotributaria_debito > 0){
			$cod_aj_apur = "SP000299";
			$descr_compl_aj = "Debito do Imposto - Outros Debitos";
			$vl_aj_apur = $alteracaotributaria_debito;
		}elseif($alteracaotributaria_credito > 0){
			$cod_aj_apur = "SP020719";
			$descr_compl_aj = "Credito do Imposto  - Outros Creditos";
			$vl_aj_apur = $alteracaotributaria_credito;
		}

		if($alteracaotributaria_debito > 0 || $alteracaotributaria_credito > 0){
			$registro = array(
				// Texto fixo contendo "E111"
				"REG" => "E111",
				// Codigo do ajuste da apuracao e deducao, conforme a tabela indicada no item 5.1.1
				"COD_AJ_APUR" => $cod_aj_apur,
				// Descricao complementar do ajuste da apuracao
				"DESCR_COMPL_AJ" => $descr_compl_aj,
				// Valor do ajuste da apauracao
				"VL_AJ_APUR" => $this->valor_decimal($vl_aj_apur, 2)
			);
			$this->escrever_registro($registro);
			$this->registro_E112();
			$this->registro_E113();
		}else{
			foreach($this->arr_outroscreditodebito AS $outroscreditodebito){
				$registro = array(
					// Texto fixo contendo "E111"
					"REG" => "E111",
					// Codigo do ajuste da apuracao e deducao, conforme a tabela indicada no item 5.1.1
					"COD_AJ_APUR" => $outroscreditodebito["codajuste"],
					// Descricao complementar do ajuste da apuracao
					"DESCR_COMPL_AJ" => $outroscreditodebito["descricaoajuste"]." ".$outroscreditodebito["fundamentolegal"],
					// Valor do ajuste da apauracao
					"VL_AJ_APUR" => number_format($outroscreditodebito["valor"], 2, ",", "")
				);
				$this->escrever_registro($registro);
			}
		}
	}

	// Informacoes adicionais dos ajustes da apuracao do ICMS [nivel 5] [NAO GERAR]
	protected function registro_E112(){

	}

	// Informacoes adicionais dos ajustes da apuracao do ICMS - Identificacao dos documentos fiscais [nivel 5] [NAO GERAR]
	protected function registro_E113(){

	}

	// Informacoes adicionais da apuracao - Valores declaratorios [nivel 4] [NAO GERAR]
	protected function registro_E115(){

	}

	// Obrigacoes do ICMS a recolher - Operacao proprias [nivel 4]
	protected function registro_E116($vl_or){
		switch($this->estabelecimento->getuf()){
			case "RS":
				$cod_rec = "022-1";
				break;
			case "SP":
				$cod_rec = "046-2";
				break;
			default:
				$cod_rec = "000-0";
				break;
		}
		$registro = array(
			// Texto fixo contendo "E116"
			"REG" => "E116",
			// Codigo da obrigacao a recolher, conforme a tabela 5.4
			"COD_OR" => "000",
			// Valor da obrigacao a recolher
			"VL_OR" => $this->valor_decimal($vl_or, 2),
			// Data de vencimento da obrigacao
			"DT_VCTO" => $this->valor_data($this->datafinal),
			// Codigo de receita referente a obrigacao, proprio da unidade da federacao, conforme a legislacao estadual
			"COD_REC" => $cod_rec,
			// Numero do processo ou auto de infracao ao qual a obrigacao esta vinculada, se houver
			"NUM_PROC" => "",
			// Indicador da origem do processo
			// 0 - SEFAZ
			// 1 - Justica Federal
			// 2 - Justica Estadual
			// 9 - Outros
			"IND_PROC" => "",
			// Descricao resumida do processo que embassou o lancamento
			"PROC" => "",
			// Descricao complementar das obrigacoes a recolher
			"TXT_COMPL" => "",
			// Informe o mes de referencia no formato "mmaaaa"
			"MES_REF" => substr($this->valor_data($this->datafinal), 2)
		);
		$this->escrever_registro($registro);
	}

	// Periodo da apuracao do ICMS - Substituicao tributaria [nivel 2]
	protected function registro_E200(){
		$arr_uf = array();
		foreach($this->arr_cliente as $cliente){
			$arr_uf[] = $cliente["ufres"];
		}
		foreach($this->arr_fornecedor as $fornecedor){
			$arr_uf[] = $fornecedor["uf"];
		}
		foreach($this->arr_estabelecimento as $estabelecimento){
			$arr_uf[] = $estabelecimento["uf"];
		}
		$arr_uf = array_unique($arr_uf);
		sort($arr_uf);

		foreach($arr_uf as $uf){
			$registro = array(
				// Texto fixo contendo "E200"
				"REG" => "E200",
				// Sigla da unidade de federacao a que se refere a apuracao do ICMS ST
				"UF" => $uf,
				// Data incial a que a apuracao se refere
				"DT_INI" => $this->valor_data($this->datainicial),
				// Data final em que a apuracao se refere
				"DT_FIN" => $this->valor_data($this->datafinal)
			);
			$this->escrever_registro($registro);
			$this->registro_E210($uf);
		}
	}

	// Apuracao do ICMS - Substituicao tributaria [nivel 3]
	protected function registro_E210($uf){
		$credito = 0; // Total de creidto de ICMS ST
		$devolucao = 0; // Total de ICMS ST de devolucao
		$ressarcimento = 0; // Total de ressarcimento de ICMS ST
		$retencao = 0; // Total de retencao de ICMS ST
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];

			if(!in_array($operacaonota["operacao"], array("DC", "PR", "RC", "VD", "TS", "NC","EC"))){
				continue;
			}

			switch($operacaonota["parceiro"]){
				case "C": $uf_parceiro = $this->arr_cliente[$notafiscal["codparceiro"]]["ufres"];
					break;
				case "E": $uf_parceiro = $this->arr_estabelecimento[$notafiscal["codparceiro"]]["uf"];
					break;
				case "F": $uf_parceiro = $this->arr_fornecedor[$notafiscal["codparceiro"]]["uf"];
					break;
			}

			if($uf_parceiro != $uf || !in_array($this->modelo($notafiscal), array("01", "04", "06", "1B", "28", "29", "55"))){
				continue;
			}

			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$cfop = substr(removeformat($itnotafiscal["natoperacao"]), 0, 4);
				if(in_array(substr($cfop, 0, 1), array("1", "2"))){
					$credito += $itnotafiscal["totalicmssubst"];
				}
				if(in_array($cfop, array("1410", "1411", "1414", "1415", "1660", "1661", "1662", "2410", "2411", "2414", "2415", "2660", "2661", "2662"))){
					$devolucao += $itnotafiscal["totalicmssubst"];
				}
				if(in_array($cfop, array("1603", "2603"))){
					$ressarcimento += $itnotafiscal["totalicmssubst"];
				}
				if(in_array(substr($cfop, 0, 1), array("5", "6"))){
					$retencao += $itnotafiscal["totalicmssubst"];
				}
			}
		}

		if($devolucao > 0 || $ressarcimento > 0 || $retencao > 0){
			$ind_movimento = 1;
		}else{
			$ind_movimento = 0;
		}

		$vl_sld_cred_ant_st = 0;
		$vl_devol_st = $devolucao;
		$vl_ressarc_st = $ressarcimento;
		$vl_out_cred_st = $credito;
		$vl_aj_creditos_st = 0;
		$vl_retencao_st = $retencao;
		$vl_out_deb_st = 0;
		$vl_aj_debitos_st = 0;
		$vl_sld_dev_ant_st = ($vl_retencao_st + $vl_out_deb_st + $vl_aj_debitos_st) - ($vl_sld_cred_ant_st + $vl_devol_st + $vl_ressarc_st + $vl_out_cred_st + $vl_aj_creditos_st);
		$vl_deducoes_st = 0;
		if($vl_sld_dev_ant_st >= 0){
			$vl_sld_cred_st_transportar = 0;
		}else{
			$vl_sld_cred_st_transportar = abs($vl_sld_dev_ant_st);
			$vl_sld_dev_ant_st = 0;
		}
		$vl_icms_recol_st = $vl_sld_dev_ant_st - $vl_deducoes_st;
		$deb_esp_st = 0;

		$registro = array(
			// Texto fixo contendo "E210"
			"REG" => "E210",
			// Indicador de movimento:
			// 0 - Sem operacoes com ST
			// 1 - Com operacoes com ST
			"IND_MOV_ST" => $ind_movimento,
			// Valor do saldo credor de periodo anterior - substituicao tributaria
			"VL_SLD_CRED_ANT_ST" => $this->valor_decimal($vl_sld_cred_ant_st, 2),
			// Valor total do ICMS ST de devolucao de mercadorias
			"VL_DEVOL_ST" => $this->valor_decimal($vl_devol_st, 2),
			// Valor total do ICMS ST de ressarcimento
			"VL_RESSARC_ST" => $this->valor_decimal($vl_ressarc_st, 2),
			// Valor total de ajustes "outros creditos ST" e "estorno de debito ST"
			"VL_OUT_CRED_ST" => $this->valor_decimal($vl_out_cred_st, 2),
			// Valor total de ajustes a credito de ICMS ST, provenientes de ajustes do documento fiscal
			"VL_AJ_CREDITOS_ST" => $this->valor_decimal($vl_aj_creditos_st, 2),
			// Valor total do ICMS retido por substituicao tributaria
			"VL_RETENCAO_ST" => $this->valor_decimal($vl_retencao_st, 2),
			// Valor total dos ajustes "outros debitos ST" e "estrono de credito ST"
			"VL_OUT_DEB_ST" => $this->valor_decimal($vl_out_deb_st, 2),
			// Valor total de ajustes a debito de ICMS ST, provenientes de ajustes do documento fiscal
			"VL_AJ_DEBITOS_ST" => $this->valor_decimal($vl_aj_debitos_st, 2),
			// Valor total de saldo devedor antes das deducoes
			"VL_SLD_DEV_ANT_ST" => $this->valor_decimal($vl_sld_dev_ant_st, 2),
			// Valor total dos ajustes "deducoes ST"
			"VL_DEDUCOES_ST" => $this->valor_decimal($vl_deducoes_st, 2),
			// Imposto a recolher ST
			"VL_ICMS_RECOL_ST" => $this->valor_decimal($vl_icms_recol_st, 2),
			// Saldo credor de ST a transportar para o periodo seguinte
			"VL_SLD_CRED_ST_TRANSPORTAR" => $this->valor_decimal($vl_sld_cred_st_transportar, 2),
			// Valores recolhidos ou a recolher, extra-apuracao
			"DEB_ESP_ST" => $this->valor_decimal($deb_esp_st, 2)
		);
		$this->escrever_registro($registro);
		$this->registro_E220();
		if($ind_movimento == "1"){
			$this->registro_E250($vl_icms_recol_st);
		}
	}

	// Ajuste/beneficio/incentivo da apuracao do ICMS substituicao tributaria [nivel 4] [NAO GERAR]
	protected function registro_E220(){
		$this->registro_E230();
		$this->registro_E240();
	}

	// Informacoes adicionais dos ajustes da apuracao do ICMS substituicao tributaria [nivel 5] [NAO GERAR]
	protected function registro_E230(){

	}

	// Informacoes adicionais dos ajustes da apuracao do ICMS substituicao tributaria - Identificacao dos documentos fiscais [nivel 5] [NAO GERAR]
	protected function registro_E240(){

	}

	// Obrigacoes do ICMS a recolher - Substituicao tributaria [nivel 4]
	protected function registro_E250($vl_or){
		$registro = array(
			// Texto fixo contendo "E250"
			"REG" => "E250",
			// Codigo da obrigacao a recolher, conforme a tabela 5.4
			"COD_OR" => "999",
			// Valor da obrigacao ICMS ST a recolher
			"VL_OR" => $this->valor_decimal($vl_or, 2),
			// Data de vencimento da obrigacao
			"DT_VCTO" => $this->valor_data($this->datafinal),
			// Codigo da receita referente a obrigacao, proprio da unidade da federacao do contribuinte substituido
			"COD_REC" => "100048",
			// Numero do processo ou auto de infracao ao qual a obrigacao esta vinculada, se houver
			"NUM_PROC" => NULL,
			// Indicador da origem do processo:
			// 0 - Sefaz
			// 1 - Justica federal
			// 2 - Justica estadual
			// 9 - Outros
			"IND_PROC" => NULL,
			// Descricao resumida do processo que embasou o lancamento
			"PROC" => NULL,
			// Descricao complementar dar obrigacoes a recolher
			"TXT_COMPL" => NULL,
			// Mes de referencia no formato "MMAAAA"
			"MES_REF" => substr($this->valor_data($this->datainicial), 2)
		);
		$this->escrever_registro($registro);
	}

	// Periodo de apuracao do IPI [nivel 2]
	protected function registro_E500(){
		$registro = array(
			// Texto fixo contendo "E500"
			"REG" => "E500",
			// Indicador de periodo de apuracao do IPI:
			// 0 - Mensal
			// 1 - Decendial
			"IND_APUR" => "0",
			// Data incial a que a apuracao se refere
			"DT_INI" => $this->valor_data($this->datainicial),
			// Data final em que a apuracao se refere
			"DT_FIN" => $this->valor_data($this->datafinal)
		);
		$this->escrever_registro($registro);
		$this->registro_E510();
		$this->registro_E520();
	}

	// Consolidacao dos valores do IPI [nivel 3]
	protected function registro_E510(){
		$arr_ipi = array();
		foreach($this->arr_notafiscal as $notafiscal){
			if(in_array($this->modelo($notafiscal), array("01", "1B", "04", "55"))){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$produto = $this->arr_produto[$itnotafiscal["codproduto"]];
					$ipi = $this->arr_ipi[$produto["codipi"]];
					$natoperacao = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
					$codcst = $ipi["codcst"];
					$arr_ipi[$natoperacao][$codcst]["totalbaseipi"] += 0; //$itnotafiscal["totalbruto"] - $itnotafiscal["totaldesconto"] + $itnotafiscal["totalacrescimo"];
					$arr_ipi[$natoperacao][$codcst]["totalipi"] += 0; //$itnotafiscal["totalipi"];
				}
			}
		}
		foreach($arr_ipi as $natoperacao => $arr_ipi_2){
			foreach($arr_ipi_2 as $codcst => $valor){
				if($valor["totalipi"] == 0){
					continue;
				}
				$registro = array(
					// Texto fixo contendo "E510"
					"REG" => "E510",
					// Codigo de operacao fiscal e prestacao do agrupamento de itens
					"CFOP" => $natoperacao,
					// Codigo da situacao tributaria referente ao IPI, conforme a tabela indicada no item 4.3.2
					"CST_IPI" => $codcst,
					// Parcela correspondente ao valor contabil referente ao CFOP e ao codigo de tributacao do IPI
					"VL_CONT_IPI" => $this->valor_decimal(0, 2),
					// Parcela correspondente ao valor da base de calculo do IPI referente ao CFOP e ao codifo de
					// tributacao do IPI, para operacoes tributarias
					"VL_BC_IPI" => $this->valor_decimal($valor["totalbaseipi"], 2),
					// Parcela correspondente ao valor do ipi referente ao CFOP e ao codigo de tributacao do IPI,
					// para operacoes tributadas
					"VL_IPI" => $this->valor_decimal($valor["totalipi"], 2)
				);
				$this->escrever_registro($registro);
			}
		}
	}

	// Apuracao do IPI [nivel 3]
	protected function registro_E520(){
		$debito = 0; // Total de debito
		$credito = 0; // Total de credito
		foreach($this->arr_notafiscal as $notafiscal){
			if(in_array($this->modelo($notafiscal), array("01", "1B", "04", "55"))){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$cfop = substr(removeformat($itnotafiscal["natoperacao"]), 0, 4);
					if(in_array(substr($cfop, 0, 1), array("5", "6"))){
						$debito += $itnotafiscal["totalipi"];
					}
					if(in_array(substr($cfop, 0, 1), array("1", "2", "3"))){
						$credito += 0; //$itnotafiscal["totalipi"];
					}
				}
			}
		}

		$vl_sd_ant_ipi = 0;
		$vl_deb_ipi = $debito;
		$vl_cred_ipi = $credito;
		$vl_od_ipi = 0;
		$vl_oc_ipi = 0;
		$vl_sc_ipi = ($vl_deb_ipi + $vl_od_ipi) - ($vl_sd_ant_ipi + $vl_cred_ipi + $vl_oc_ipi);
		if($vl_sc_ipi < 0){
			$vl_sc_ipi = abs($vl_sc_ipi);
			$vl_sd_ipi = 0;
		}else{
			$vl_sd_ipi = $vl_sc_ipi;
			$vl_sc_ipi = 0;
		}

		$registro = array(
			// Texto fixo contendo "E520"
			"REG" => "E520",
			// Saldo credor do IPI transferido do periodo anterior
			"VL_SD_ANT_IPI" => $this->valor_decimal($vl_sd_ant_ipi, 2),
			// Valor total dos debitos por saidas com debito do imposto
			"VL_DEB_IPI" => $this->valor_decimal($vl_deb_ipi, 2),
			// Valor total dos creditos por entradas com credito do imposto
			"VL_CRED_IPI" => $this->valor_decimal($vl_cred_ipi, 2),
			// Valor de outros debitos do IPI (inclusive estornos de credito)
			"VL_OD_IPI" => $this->valor_decimal($vl_od_ipi, 2),
			// Valor de outros creditos do IPI (inclusive estornos de debitos)
			"VL_OC_IPI" => $this->valor_decimal($vl_oc_ipi, 2),
			// Valor do saldo credor do IPI a transportar para o periodo seguinte
			"VL_SC_IPI" => $this->valor_decimal($vl_sc_ipi, 2),
			// Valor do saldo devedor do IPI a recolher
			"VL_SD_IPI" => $this->valor_decimal($vl_sd_ipi, 2)
		);
		$this->escrever_registro($registro);
		$this->registro_E530();
	}

	// Ajustes da apuracao do IPI [nivel 4] [NAO GERAR]
	protected function registro_E530(){
		return array();
	}

	// Encerramento do bloco E [nivel 1]
	protected function registro_E990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "E"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "E990"
			"REG" => "E990",
			// Quantidade de linhas do bloco E
			"QTD_LIN_E" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco G [nivel 1]
	protected function registro_G001(){
		$registro = array(
			// Texto fixo contendo "G001"
			"REG" => "G001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "1"
		);
		$this->escrever_registro($registro);
	}

	// Encerramento do bloco G [nivel 1]
	protected function registro_G990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "G"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "G990"
			"REG" => "G990",
			// Quantidade de linhas do bloco G
			"QTD_LIN_G" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco H [nivel 1]
	protected function registro_H001(){
		$registro = array(
			// Texto fixo contendo "H001"
			"REG" => "H001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "0"
		);
		$this->escrever_registro($registro);
		$this->registro_H005();
	}

	// Totais do inventario [nivel 2]
	protected function registro_H005(){

		$arr_mot_inv = array("01", "02");

		foreach($arr_mot_inv as $mot_inv){
			if($mot_inv === "02" && !$this->gerar_alteracaotributaria){
				continue;
			}
			$vl_inv = 0;
			foreach($this->arr_produto as $produto){
				if($produto["saldo"] == 0){
					continue;
				}
				if($mot_inv === "01" && !$this->gerar_inventario){
					continue;
				}
				if($mot_inv === "02" && (!$this->gerar_alteracaotributaria || $produto["alteracaotributaria"] != "S")){
					continue;
				}
				$vl_inv += round(($produto["saldo"] * $produto["custosemimp"]), 2);
			}

			if(strlen($this->datainventario) > 0){
				$datainventario = $this->datainventario;
			}else{
				$datainventario = $this->datafinal;
			}

			$registro = array(
				// Texto fixo contendo "H005"
				"REG" => "H005",
				// Data do inventario
				"DT_INV" => $this->valor_data($datainventario),
				// Valor total do estoque
				"VL_INV" => $this->valor_decimal($vl_inv, 2),
				// Motivo do inventario:
				// 01 – No final no periodo
				// 02 – Na mudanca de forma de tributacao da mercadoria (ICMS)
				// 03 – Na solicitacao da baixa cadastral, paralisacao temporaria e outras situacoes
				// 04 – Na alteracao de regime de pagamento – condicao do contribuinte
				// 05 – Por determinacao dos fiscos
				"MOT_INV" => $mot_inv
			);
			$this->escrever_registro($registro);

			if($vl_inv > 0){
				$this->registro_H010($mot_inv);
			}
		}
	}

	// Inventario [nivel 3]
	protected function registro_H010($mot_inv){
		foreach($this->arr_produto as $produto){
			if($produto["saldo"] == 0){
				continue;
			}
			if($mot_inv === "02" && $produto["alteracaotributaria"] !== "S"){
				continue;
			}
			$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
			$unidade = $this->arr_unidade[$embalagem["codunidade"]];
			$registro = array(
				// Texto fixo contendo "H010"
				"REG" => "H010",
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $produto["codproduto"],
				// Unidade do item
				"UNID" => $unidade["sigla"],
				// Quantidade do item
				"QTD" => $this->valor_decimal($produto["saldo"], 3),
				// Valor unitario do item
				"VL_UNIT" => $this->valor_decimal($produto["custosemimp"], 2),
				// Valor do item
				"VL_ITEM" => $this->valor_decimal($produto["saldo"] * $produto["custosemimp"], 2),
				// Indicador de propriedade/posse do item:
				// 0 - Item de propriedade do informante e em seu poder
				// 1 - Item de propriedade do informante e em posse de terceiros
				// 2 - Item de propriedade de terceiros e em posse do informante
				"IND_PROP" => "0",
				// Codigo do participante (campo 02 do registro 0150) (proprietario/possuidor que nao seja o informante do arquivo)
				"COD_PART" => "",
				// Descricao complementar
				"TXT_COMPL" => "",
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => $this->planocontas->getcontacontabil()
			);
			if(compare_date($this->datainicial, "2015-01-01", "Y-m-d", ">=")){
				// VL_ITEM_IR H010
				// Ainda não consegui achar o manual do SPED atualizado para verificar qual a forma mais correta de gerar este campo.
				$registro["VL_ITEM_IR"] = "";
			}
			$this->escrever_registro($registro);

			if($mot_inv == "02"){
				$this->registro_H020($produto);
			}
		}
	}

	// Informação complementar do inventário [nivel 4]
	protected function registro_H020($produto){
		if($produto["tptribicmsnew"] === "F"){
			$aliqicms = $produto["aliqicmsold"] * (1 - $produto["redicmsold"] / 100);
		}else{
			$aliqicms = $produto["aliqicmsnew"] * (1 - $produto["redicmsnew"] / 100);
		}
		$vl_icms = $produto["saldo"] * $produto["custosemimp"] * $aliqicms / 100;

		$registro = array(
			// Texto fixo contendo "H020"
			"REG" => "H020",
			// Codigo da Situacao Tributaria referente ao ICMS, conforme a tabela indicada no item 4.3.1
			"CST_ICMS" => $produto["codcstnew"],
			// Informe a base de calculo do ICMS
			"BC_ICMS" => $this->valor_decimal($produto["custosemimp"], 2),
			// Informe o valor do ICMS a ser debitado ou creditado
			"VL_ICMS" => $this->valor_decimal($vl_icms, 2)
		);
		$this->escrever_registro($registro);
	}

	// Encerramento do bloco H [nivel 1]
	protected function registro_H990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "H"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "H990"
			"REG" => "H990",
			// Quantidade de linhas do bloco H
			"QTD_LIN_H" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco K [nivel 1]
	protected function registro_K001(){
		$registro = array(
			// Texto fixo contendo "K001"
			"REG" => "K001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "1"
		);
		$this->escrever_registro($registro);
	}

	// Encerramento do bloco K [nivel 1]
	protected function registro_K990(){
		$registro = array(
			// Texto fixo contento "K990"
			"REG" => "K990",
			// Quantidade de linhas do bloco K
			"QTD_LIN_H" => 2
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco 1 [nivel 1]
	protected function registro_1001(){
		$registro = array(
			// Texto fixo contendo "1001"
			"REG" => "1001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "0"
		);
		$this->escrever_registro($registro);
		$this->registro_1010();
	}

	// Obrigatoriedade de registro do bloco 1 [nivel 2]
	protected function registro_1010(){
		$registro = array(
			// Texto fixo contendo "1010"
			"REG" => "1010",
			// Reg 1100 - ocorreu averbacao (conclusao) de exportacao no periodo (S/N)
			"IND_EXP" => "N",
			// Reg 1200 - existem informacoes acerca de creditos de ICMS a serem controlados, definidos pela Sefaz (S/N)
			"IND_CCRF" => (($this->creditoicms > 0 && $this->estabelecimento->getuf() != "SP") ? "S" : "N"),
			// Reg 1300 - e comercio varejista de combustivel (S/N)
			"IND_COMB" => "N",
			// Reg 1390 - usinas de acucar/alcool - o estabelecimento e produtos de acucar/alcool carburante (S/N)
			"IND_USINA" => "N",
			// Reg 1400 - existem informacoes a serem prestadas neste registro e o registro e obrigatorio em sua unidade de federacao (S/N)
			"IND_VA" => "N",
			// Reg 1500 - a empresa e distribuidora de energia e ocorreu fornecimento de energia eletrica para consumidores de outra UF (S/N)
			"IND_EE" => "N",
			// Reg 1600 - realizou vendas com cartao de credito ou de debito (S/N)
			"IND_CART" => "N",
			// Reg 1700 - e obrigatorio em sua unidade de federacao o controle de utilizacao de documentos fiscais em papel (S/N)
			"IND_FORM" => "N",
			// Reg 1800 - a empresa prestou servicos de transporte aereo de cargas e de passageiros
			"IND_AER" => "N"
		);
		$this->escrever_registro($registro);

		if($this->creditoicms > 0 && $this->estabelecimento->getuf() != "SP"){
			$this->registro_1200();
		}
	}

	// Controle de creditos fiscais - ICMS [nivel 2]
	protected function registro_1200(){
		$cred_util = ($this->creditoicms > $this->debitoicms ? $this->debitoicms : $this->creditoicms);

		$registro = array(
			// Texto fixo contendo "1200"
			"REG" => "1200",
			// Codigo de ajuste, conforme informado na tabela indicada no item 5.1.1
			"COD_AJ_APUR" => "SP099999",
			// Saldo de creditos fiscais de periodos anteriores
			"SLD_CRED" => $this->valor_decimal($this->creditoicms, 2),
			// Total de credito apropriado no mes
			"CRED_APR" => $this->valor_decimal(0, 2),
			// Total de creditos recebidos por transferencia
			"CRED_RECEB" => $this->valor_decimal(0, 2),
			// Total de creditos utilizados no periodo
			"CRED_UTIL" => $this->valor_decimal($cred_util, 2),
			// Saldo de credito fiscal acumulado a transportar para o periodo seguinte
			"SLD_CRED_FIM" => $this->valor_decimal(($this->creditoicms - $cred_util), 2)
		);
		$this->escrever_registro($registro);
		if($this->creditoicms > 0 && $this->estabelecimento->getuf() != "SP"){
			$this->registro_1210($cred_util);
		}
	}

	// Utilizacao de credito - ICMS [nivel 3]
	protected function registro_1210($vl_cred_util){
		$registro = array(
			// Texto fixo contendo "1210
			"REG" => "1210",
			// Tipo de utilizacao de credito, conforme a tabela indicada no item 5.5
			"TIPO_UTIL" => "SP01",
			// Numero de documento utilizado na baixa de creditos
			"NR_DOC" => "",
			// Total de credito utilizado
			"VL_CRED_UTIL" => $this->valor_decimal($vl_cred_util, 2)
		);
		$this->escrever_registro($registro);
	}

	// Encerramento do bloco 1 [nivel 1]
	protected function registro_1990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "1"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "1990"
			"REG" => "1990",
			// Quantidade de linhas do bloco 1
			"QTD_LIN_1" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco 9 [nivel 1]
	protected function registro_9001(){
		$registro = array(
			// Texto fixo contendo "9001"
			"REG" => "9001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "0"
		);
		$this->escrever_registro($registro);
	}

	// Registros do arquivo [nivel 2]
	protected function registro_9900(){
		foreach($this->n_registro as $registro => $quantidade){
			$registro = array(
				// Texto fixo contendo "9900"
				"REG" => "9900",
				// Registro que sera totalizado no proximo campo
				"REG_BLC" => $registro,
				// Total de registros do tipo informado no campo anterior
				"QTD_REG_BLC" => $this->valor_decimal($quantidade, 0)
			);
			$this->escrever_registro($registro);
		}
		// Inclui os registros "9900", "9990" e "9999"
		$this->escrever_registro(array("REG" => "9900", "REG_BLOC" => "9900", "QTD_REG_BLC" => (count($this->n_registro) + 2)));
		$this->escrever_registro(array("REG" => "9900", "REG_BLOC" => "9990", "QTD_REG_BLC" => "1"));
		$this->escrever_registro(array("REG" => "9900", "REG_BLOC" => "9999", "QTD_REG_BLC" => "1"));
	}

	// Encerramento do bloco 9 [nivel 1]
	protected function registro_9990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "9"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "9990"
			"REG" => "9990",
			// Quantidade de linhas do bloco 9
			"QTD_LIN_9" => ($t_quantidade + 2)
		);
		$this->escrever_registro($registro);
	}

	// Encerramento do arquivo digital [nivel 0]
	protected function registro_9999(){
		$registro = array(
			// Texto fixo contendo "9999"
			"REG" => "9999",
			// Quantidade total de linhas do arquivo digital
			"QTD_LIN" => (array_sum($this->n_registro) + 1)
		);
		$this->escrever_registro($registro);
	}

	public function getarquivo_nome(){
		return $this->arquivo_nome;
	}

}