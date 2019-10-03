<?php

set_time_limit(0);
ini_set("memory_limit", -1);
set_time_limit(60 * 60);

require_once("../class/sped.class.php");
require_once("../lib/sqlformatter-1.0.0/lib/SqlFormatter.php");

final class SpedPisCofins extends Sped{

	protected $gerar_tudozerado = FALSE; // Campo booleano que determina se deve gerar o arquivo todo zerado
	protected $gerar_blocomzerado = FALSE; // Campo booleano que determina se deve gerar o o bloco M zerado
	protected $gerar_notafiscal = FALSE; // Campo booleano que determina se deve gerar notas fiscais
	protected $gerar_cupom = FALSE; // Campo booleano que determina se deve gerar cupons
	protected $arr_filtro = array(); // Array com os filtro tecnicos que serao aplicados na busca da notas fiscais (nome da coluna como indice)
	protected $arr_cidade; // Array com as cidades do estabelecimento e parceiros (codcidade com indice do array)
	protected $arr_classfiscal; // Array com as classificacoes fiscais dos itens das notas (codcf como indice do array)
	protected $arr_cliente; // Array com os clientes das notas (codfornec com indice do array
	protected $arr_contabilidade; // Array com as contabilidades dos estabelecimentos (codcontabilidade como indice do array)
	protected $arr_ecf; // Array com os ECFs (codecf como indice do array)
	protected $arr_embalagem; // Array com as embalagens dos itens (codembal como indice do array)
	protected $arr_estabelecimento_parc; // Array com os estabelecimentos das notas (transferencia) (codestabelec como indice do array)
	protected $arr_fornecedor; // Array com os fornecedores das notas (codfornec como indice do array)
	protected $arr_ipi; // Array com os IPIs dos itens das notas (codipi como indice do array)
	protected $arr_itcupom; // Array com os itens de cupom (codmaparesumo com indice e um array com os itens como valor do array principal)
	protected $arr_lancamento; // Array com os lancamentos das notas (idnotafiscal como indice do array)
	protected $arr_maparesumo; // Array com os mapas resumo
	protected $arr_natreceita; // Array com as naturezas de receita
	protected $arr_ncm; // Array com os NCMs dos items (idncm como indice do array)
	protected $arr_notacomplemento; // Array contendo as nota de complemento
	protected $arr_notadiversa; // Array contendo as notas diversas (como nota de energia eletrica)
	protected $arr_notafiscal; // Array com todas as notas fiscais (idnotafiscal com indice do array)
	protected $arr_notafiscalservico; //Array com todas as notas fiscais de serviços lançadas no sistema
	protected $arr_notafiscalimposto; // Array com os impostos das notas fiscais (tabela notafiscalimposto) (idnotafiscal como indice do array)
	protected $arr_notafrete; // Array contendo as notas de frete
	protected $arr_operacaonota; // Array com os tipos de operacao (operacao como indice do array)
	protected $arr_produto; // Array com os produtos das notas (codproduto como indice do array)
	protected $arr_piscofins; // Array com os PIS/Cofins dos itens das notas (codpiscofins como indice do array)
	protected $arr_produtoean; // Array com os eans dos produtos (codproduto como indice do array)
	protected $arr_transportadora; // Array com as transportadoras das notas (codtransp como indice do array)
	protected $arr_unidade; // Array com as unidades dos itens (codunidade como indice do array)
	protected $arr_planocontas; // Array com os plano de contas
	protected $arr_natoperacao; // Array com os plano de contas
	protected $totalanteriorpis; // Total do credito de PIS que poderia ser utilizado no mes anterior
	protected $utilizadoanteriorpis; // Total do credito de PIS que foi utilizado no mes anterior
	protected $totalanteriorcofins; // Total do credito de Cofins que poderia ser utilizado no mes anterior
	protected $utilizadoanteriorcofins; // Total do credito de Cofins que foi utilizado no mes anterior
	protected $c010_ind_escri = "2"; // Indicador da apuracao das contribuicoes e creditos, na escrituracao das operacoes por NF-e e ECF
	protected $codunidade_servico; //conter o codigo o da unidade a ser usada nas NFs de serviços
	protected $m200_arr_itcupom; // Array com os itens de nota fiscal que serao usados no registro M200 e M600
	protected $m200_arr_itnotafiscal; // Array com os itens de nota fiscal que serao usados no registro M200 e M600
	protected $m200_arr_itnotafiscalservico; // Array com os itens de nota fiscal de servico que serao usados no registro M200 e M600
	protected $m200_vl_tot_cred_desc; // // Valor do credito descontado, apurado no proprio periodo da escrituracao
	protected $m200_vl_tot_cont_cum_per; // Valor total da contribuicao nao cumulativa do periodo
	protected $m200_vl_tot_cont_nc_per; // Valor total da contribuicao nao cumulativa do periodo
	protected $m200_vl_tot_cont_nc_dev; // Valor total da contribuicao nao cumulativa devida
	protected $m600_vl_tot_cred_desc; // // Valor do credito descontado, apurado no proprio periodo da escrituracao
	protected $m600_vl_tot_cont_cum_per; // Valor total da contribuicao nao cumulativa do periodo
	protected $m600_vl_tot_cont_nc_per; // Valor total da contribuicao nao cumulativa do periodo
	protected $m600_vl_tot_cont_nc_dev; // Valor total da contribuicao nao cumulativa devida
	protected $_1100_vl_cred_desc_efd; // Valor total do credito descontado neste periodo de escrituracao
	protected $_1500_vl_cred_desc_efd; // Valor total do credito descontado neste periodo de escrituracao
	protected $paramestoque; // Objeto referente aos parametros de estoque

	function settotalanteriorpis($totalanteriorpis){
		$this->totalanteriorpis = $totalanteriorpis;
	}

	function setutilizadoanteriorpis($utilizadoanteriorpis){
		$this->utilizadoanteriorpis = $utilizadoanteriorpis;
	}

	function settotalanteriorcofins($totalanteriorcofins){
		$this->totalanteriorcofins = $totalanteriorcofins;
	}

	function setutilizadoanteriorcofins($utilizadoanteriorcofins){
		$this->utilizadoanteriorcofins = $utilizadoanteriorcofins;
	}

	// Adiciona um filtro na busca de notas fiscais
	public function filtro($coluna, $valor){
		if(strlen(trim($valor)) > 0){
			$this->arr_filtro[$coluna] = $valor;
		}
	}

	// Gera o arquivo referente ao SPED-PIS/Cofins
	public function gerar(){
		parent::gerar();

		foreach($this->arr_filtro as $coluna => $valor){
			if($coluna == "dtentregaini"){
				$this->datainicial = $valor;
			}
			if($coluna == "dtentregafim"){
				$this->datafinal = $valor;
			}
		}

		$this->progresso_t = 27;

		// Limpa alguma variaveis
		$this->n_registro = array();

		$this->progresso("Validando dados enviados");

		// Verifica se foi informado o estabelecimento
		if(!is_array($this->arr_estabelecimento) || sizeof($this->arr_estabelecimento) == 0){
			$_SESSION["ERROR"] = "Informe o estabelecimento emitente das notas fiscais para o arquivo.";
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

		// Verifica se foi informado o diretorio de geracao fiscal
		if(strlen($this->matriz->getdirarqfiscal()) == 0){
			$_SESSION["ERROR"] = "Informe o diret&oacute;rio para gera&ccedil;&atilde;o de arquivos fiscais para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->matriz->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}

		// Cria um array com o codigo de todas as lojas
		$arr_codestabelec = array();
		foreach($this->arr_estabelecimento as $estabelecimento){
			$arr_codestabelec[] = $estabelecimento->getcodestabelec();
		}

		// Carrega o parametro de estoque
		$this->paramestoque = objectbytable("paramestoque", $this->matriz->getcodemitente(), $this->con);

		// Busca todas as operacoes de nota fiscal
		$this->progresso("Carregando tipos de operacoes de nota fiscais");
		$this->arr_operacaonota = array();
		$res = $this->con->query("SELECT * FROM operacaonota");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_operacaonota[$row["operacao"]] = $row;
		}

		// Busca todos os planos de contas
		$this->progresso("Carregando os planos de contas");
		$this->arr_planocontas = array();
		$res = $this->con->query("SELECT * FROM planocontas");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_planocontas[$row["codconta"]] = $row;
		}

		// Busca todas as naturezas de operacao
		$this->progresso("Carregando as natureza de operacao");
		$this->arr_natoperacao = array();
		$res = $this->con->query("SELECT * FROM natoperacao");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_natoperacao[$row["natoperacao"]] = $row;
		}

		// Busca as contabilidades dos estabelecimentos
		$this->progresso("Carregando contabilidades");
		$this->arr_contabilidade = array();
		$arr_codcontabilidade = array();
		foreach($this->arr_estabelecimento as $estabelecimento){
			if(strlen($estabelecimento->getcodcontabilidade()) == 0){
				$_SESSION["ERROR"] = "Informe a contabilidade para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
				return FALSE;
			}
			$arr_codcontabilidade[] = $estabelecimento->getcodcontabilidade();
		}
		if(sizeof($arr_codcontabilidade) > 0){
			$res = $this->con->query("SELECT * FROM contabilidade WHERE codcontabilidade IN (".implode(",", $arr_codcontabilidade).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_contabilidade[$row["codcontabilidade"]] = $row;
			}
		}

		// Busca as notas fiscais e seus itens
		$this->progresso("Carregando notas fiscais");
		$this->arr_notafiscal = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query = "SELECT estabelecimento.codestabelecfiscal AS codestabelec, notafiscal.numnotafis, notafiscal.serie, notafiscal.operacao, notafiscal.dtemissao, notafiscal.status, ";
			$query .= "notafiscal.codparceiro, notafiscal.codtransp, notafiscal.codfunc, notafiscal.codcondpagto, notafiscal.natoperacao, notafiscal.valoricms, notafiscal.observacao, ";
			$query .= "notafiscal.usuario, notafiscal.datalog, notafiscal.bonificacao, notafiscal.numpedido, notafiscal.codespecie, notafiscal.dtentrega, ";
			$query .= "notafiscal.numeroitens, notafiscal.totaldesconto, notafiscal.totalacrescimo, notafiscal.totalfrete, notafiscal.totalipi, ";
			$query .= "notafiscal.totalbaseicms, notafiscal.totalicms, notafiscal.totalbaseicmssubst, notafiscal.totalicmssubst, ";
			$query .= "(notafiscal.totalbruto + notafiscal.totalacrescimo + notafiscal.totalseguro) AS totalbruto, ";
			$query .= "notafiscal.totalliquido, notafiscal.totalarecolher, notafiscal.totalbonificado, notafiscal.totaldescontoc, notafiscal.totalacrescimoc, ";
			$query .= "notafiscal.totalfretec, notafiscal.totalipic, notafiscal.totalbaseicmsc, notafiscal.totalicmsc, notafiscal.totalbaseicmssubstc, ";
			$query .= "notafiscal.totalicmssubstc, notafiscal.totalbrutoc, notafiscal.totalliquidoc, notafiscal.totalarecolherc, ";
			$query .= "notafiscal.totalbonificadoc, notafiscal.tipoparceiro, notafiscal.idnotafiscal, notafiscal.chavenfe, ";
			$query .= "notafiscal.totalpis, notafiscal.totalcofins, notafiscal.cupom, notafiscal.numeroecf, notafiscal.observacaofiscal, notafiscal.totalquantidade, ";
			$query .= "notafiscal.especie, notafiscal.marca, notafiscal.numeracao, notafiscal.pesobruto, notafiscal.pesoliquido, notafiscal.modfrete, ";
			$query .= "notafiscal.transpplacavei, notafiscal.transpufvei, notafiscal.transprntc, notafiscal.ufdesembaraco, notafiscal.localdesembaraco, ";
			$query .= "notafiscal.dtdesembaraco, notafiscal.numerodi, notafiscal.dtregistrodi, notafiscal.totalbaseii, notafiscal.totalii, ";
			$query .= "notafiscal.totaliof, notafiscal.totalseguro, notafiscal.totaldespaduaneira, notafiscal.totalsiscomex, notafiscal.modelodocfiscal, ";
			$query .= "notafiscal.chavenferef, notafiscal.finalidade, notafiscal.tipoemissao, notafiscal.totalbasepis, notafiscal.totalbasecofins, ";
			$query .= "notafiscal.geraestoque, notafiscal.gerafinanceiro, notafiscal.geraliquidado, notafiscal.gerafiscal, ";
			$query .= "notafiscal.geraicms, notafiscal.geraipi, notafiscal.gerapiscofins, notafiscal.geracustomedio, notafiscal.xmlnfe, ";
			$query .= "notafiscal.dtdigitacao, notafiscal.totalseguroc, notafiscal.totalbaseisento, notafiscal.idnotafrete, notafiscal.totalnotafrete, ";
			$query .= "notafiscal.codtabela, notafiscal.protocolo, notafiscal.codrepresentante, notafiscal.codpremio, notafiscal.codfornecref, ";
			$query .= "notafiscal.totalcustotab, notafiscal.codrastreamento, notafiscal.dtrastreamento, notafiscal.financpercentual, ";
			$query .= "notafiscal.totalgnre, notafiscal.emissaopropria, notafiscal.codigostatus, notafiscal.xmotivo, notafiscal.tipoevento, ";
			$query .= "notafiscal.cce, notafiscal.protocolonfe, notafiscal.protocolocce, notafiscal.sequenciaevento, notafiscal.dataautorizacao, ";
			$query .= "notafiscal.datacancelamento, notafiscal.datacce, notafiscal.hrdigitacao, notafiscal.viatransporte, notafiscal.totalvalorafrmm, ";
			$query .= "notafiscal.tipoimportacao, notafiscal.cnpjadquirente, notafiscal.ufterceiro, notafiscal.protocolocanc, ";
			$query .= "notafiscal.statuscontabil, notafiscal.idnotafiscalref, notafiscal.impresso, notafiscal.superimpressao, notafiscal.tipoajuste, notafiscal.indpres ";
			$query .= "FROM notafiscal ";
			$query .= "INNER JOIN estabelecimento ON (estabelecimento.codestabelec = notafiscal.codestabelec) ";
			$query .= "INNER JOIN natoperacao ON (natoperacao.natoperacao = notafiscal.natoperacao) ";
			$query .= "INNER JOIN itnotafiscal ON (notafiscal.idnotafiscal = itnotafiscal.idnotafiscal) ";
			$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal IN (".implode(",", $arr_codestabelec)."))  ";
			$query .= "	AND ((notafiscal.operacao IN ('CP','DF','TE') AND notafiscal.dtentrega BETWEEN '".$this->datainicial."' AND '".$this->datafinal."') ";
			$query .= "	 OR  (notafiscal.operacao NOT IN ('CP','DF','TE','SS','SE') AND notafiscal.dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."')) ";
			$query .= "	AND notafiscal.status NOT IN ('C','I','P','D') ";
			$query .= " AND natoperacao.geraspedpiscofins = 'S' ";
			$query .= " AND (estabelecimento.regimetributario != '2' OR (estabelecimento.regimetributario = '2' AND notafiscal.operacao NOT IN ('CP','TE','TS'))) ";
			$query .= "	AND ((cupom IS NULL AND notafiscal.operacao='VD') OR notafiscal.operacao != 'VD') ";
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND notafiscal.dtentrega >= '$valor' ";
				}elseif($coluna == "dtentregafim"){
					$query .= "	AND notafiscal.dtentrega <= '$valor' ";
				}elseif($coluna == "natoperacao"){
					$query .= "	AND itnotafiscal.natoperacao = '$valor' ";
				}else{
					$query .= "	AND notafiscal.".$coluna." = '$valor' ";
				}
			}
			//echo $query;
			$res = $this->con->query($query);
			while($row = $res->fetch(2)){
				$this->arr_notafiscal[$row["idnotafiscal"]] = $row;
				$arr_idnotafiscal[] = $row["idnotafiscal"];
			}
			if(sizeof($arr_idnotafiscal) > 0){
				$query = "SELECT itnotafiscal.*, (itnotafiscal.totalbruto + itnotafiscal.totalacrescimo + itnotafiscal.totalseguro) AS totalbruto, ";
				$query .= "natoperacao.cstpiscofins ";
				$query .= "FROM itnotafiscal ";
				$query .= "INNER JOIN natoperacao ON (itnotafiscal.natoperacao = natoperacao.natoperacao) ";
				$query .= "WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscal).") AND composicao IN ('N','P') ";
				$query .= "ORDER BY idnotafiscal, seqitem ";

				$res = $this->con->query($query);
				$arr = $res->fetchAll(2);
				foreach($arr as $row){
					$row["preco"] = $row["precopolitica"];
					$row["totalbasecofins"] = round($row["totalbasecofins"],2);
					$this->arr_notafiscal[$row["idnotafiscal"]]["itnotafiscal"][] = $row;
				}
			}

			// Gera NFc-e
			$query = "SELECT cupom.* ";
			$query .= "FROM cupom ";
			$query .= "INNER JOIN ecf ON (cupom.codecf = ecf.codecf) ";
			$query .= "WHERE cupom.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal=".$estabelecimento->getcodestabelec().") ";
			$query .= "	AND cupom.dtmovto BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' AND substr(chavecfe,21,2) = '65'   ";
			$query .= " AND cupom.codestabelec = {$estabelecimento->getcodestabelec()}";
			$query .= "	AND cupom.status = 'A' ";

			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND cupom.dtmovto >= '".$valor."' ";
				}elseif($coluna == "dtentregafim"){
					$query .= "	AND cupom.dtmovto <= '".$valor."' ";
				}
			}

			$query .= "ORDER BY cupom.idcupom ";
			$res = $this->con->query($query);
			$arr_nfce = $res->fetchAll(2);

			foreach($arr_nfce aS $i => $nfce){
				$query = "SELECT (CASE WHEN ecf.equipamentofiscal = 'SAT' THEN 'SAT' ELSE COALESCE(maparesumo.codmaparesumo::TEXT, 'SAT') END) AS codmaparesumo, itcupom.idcupom, itcupom.codproduto, ";
				$query .= "	SUM(itcupom.quantidade) AS quantidade, itcupom.preco, itcupom.desconto, ";
				$query .= "	itcupom.acrescimo, SUM(itcupom.valortotal) AS valortotal, itcupom.aliqicms, ";
				$query .= "	itcupom.tptribicms, round(itcupom.totalpis, 2) AS totalpis, round(itcupom.totalcofins,2) AS totalcofins, round(itcupom.totalbasepis,2) AS totalbasepis, ";
				//$query .= "	itcupom.tptribicms, round(itcupom.totalpis, 2) AS totalpis, round((round(itcupom.totalbasecofins,2) * itcupom.aliqcofins / 100),2 ) AS totalcofins, round(itcupom.totalbasepis,2) AS totalbasepis, ";
				$query .= "	itcupom.aliqpis, itcupom.aliqcofins, round(itcupom.totalbasecofins,2) AS totalbasecofins, embalagem.codunidade, ";
				$query .= " produto.fabricacaopropria ";
				$query .= "FROM itcupom ";
				$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
				$query .= "INNER JOIN produto ON (itcupom.codproduto = produto.codproduto) ";
				$query .= "LEFT JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
				$query .= "LEFT JOIN maparesumo ON (cupom.codestabelec = maparesumo.codestabelec AND cupom.dtmovto = maparesumo.dtmovto AND cupom.caixa = maparesumo.caixa AND cupom.numeroecf = maparesumo.numeroecf) ";
				$query .= "INNER JOIN ecf ON (cupom.codecf = ecf.codecf) ";
				$query .= "WHERE cupom.idcupom = {$nfce["idcupom"]} ";
				$query .= "	AND itcupom.status = 'A' ";
				$query .= "	AND itcupom.composicao IN ('N','P') ";
				$query .= " AND itcupom.valortotal >= 0.01 ";
				$query .= "GROUP BY maparesumo.codmaparesumo, itcupom.idcupom, itcupom.codproduto, ecf.equipamentofiscal, ";
				$query .= "	itcupom.preco, itcupom.desconto, itcupom.acrescimo, itcupom.aliqicms, ";
				$query .= "	itcupom.tptribicms,itcupom.totalpis, itcupom.totalcofins,itcupom.totalcofins, itcupom.totalbasepis, ";
				$query .= "	itcupom.aliqpis, itcupom.aliqcofins, itcupom.totalbasecofins, embalagem.codunidade,produto.fabricacaopropria ";
				$query .= "ORDER BY 1, 2 ";

				$res = $this->con->query($query);
				while($row = $res->fetch(2)){
					$arr_nfce[$i]["itcupom"][] = $row;
				}
			}


			foreach($arr_nfce AS $nfce){
				$notafiscal = $this->cupom_para_notafiscal($nfce);
				$this->arr_notafiscal[$notafiscal["idnotafiscal"]] = $notafiscal;
			}
		}

		$_SESSION["SPED"]["arr_idnotafiscal"] = $arr_idnotafiscal;

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
		$this->progresso("Carregando impostos das nota fiscais");
		$this->arr_notafiscalimposto = array();
		if($this->gerar_notafiscal && sizeof($arr_idnotafiscal) > 0){
			$res = $this->con->query("SELECT * FROM notafiscalimposto WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscal).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_notafiscalimposto[$row["idnotafiscal"]][] = $row;
			}
		}

		// Busca as nota de complemento
		$this->progresso("Carregando notas de complemento");
		$this->arr_notacomplemento = array();
		if(false && !$this->gerar_tudozerado && $this->gerar_notafiscal){
			$arr_idnotafiscal = array();
			$query = "SELECT * FROM notacomplemento ";
			$query .= "WHERE codestabelec IN (".implode(",", $arr_codestabelec).") ";
			$query .= "	AND dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_notacomplemento[$row["idnotacomplemento"]] = $row;
				if(strlen(trim($row["idnotafiscal"])) > 0){
					$arr_idnotafiscal[] = $row["idnotafiscal"];
				}
			}

			// Busca as notas fiscais que estao sendo complementadas
			if(sizeof($arr_idnotafiscal) > 0){
				$res = $this->con->query("SELECT * FROM notafiscal WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscal).")");
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

		$this->progresso("Carregando notas de serviços");
		$this->arr_notafiscalservico = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query = "SELECT estabelecimento.codestabelecfiscal AS codestabelec, notafiscal.numnotafis, notafiscal.serie, notafiscal.operacao, notafiscal.dtemissao, notafiscal.status, ";
			$query .= "notafiscal.codparceiro, notafiscal.codtransp, notafiscal.codfunc, notafiscal.codcondpagto, notafiscal.natoperacao, notafiscal.valoricms, notafiscal.observacao, ";
			$query .= "notafiscal.usuario, notafiscal.datalog, notafiscal.bonificacao, notafiscal.numpedido, notafiscal.codespecie, notafiscal.dtentrega, ";
			$query .= "notafiscal.numeroitens, notafiscal.totaldesconto, notafiscal.totalacrescimo, notafiscal.totalfrete, notafiscal.totalipi, ";
			$query .= "notafiscal.totalbaseicms, notafiscal.totalicms, notafiscal.totalbaseicmssubst, notafiscal.totalicmssubst, ";
			$query .= "(notafiscal.totalbruto + notafiscal.totalacrescimo + notafiscal.totalseguro) AS totalbruto, ";
			$query .= "notafiscal.totalliquido, notafiscal.totalarecolher, notafiscal.totalbonificado, notafiscal.totaldescontoc, notafiscal.totalacrescimoc, ";
			$query .= "notafiscal.totalfretec, notafiscal.totalipic, notafiscal.totalbaseicmsc, notafiscal.totalicmsc, notafiscal.totalbaseicmssubstc, ";
			$query .= "notafiscal.totalicmssubstc, notafiscal.totalbrutoc, notafiscal.totalliquidoc, notafiscal.totalarecolherc, ";
			$query .= "notafiscal.totalbonificadoc, notafiscal.tipoparceiro, notafiscal.idnotafiscal, notafiscal.chavenfe, ";
			$query .= "notafiscal.totalpis, notafiscal.totalcofins, notafiscal.cupom, notafiscal.numeroecf, notafiscal.observacaofiscal, notafiscal.totalquantidade, ";
			$query .= "notafiscal.especie, notafiscal.marca, notafiscal.numeracao, notafiscal.pesobruto, notafiscal.pesoliquido, notafiscal.modfrete, ";
			$query .= "notafiscal.transpplacavei, notafiscal.transpufvei, notafiscal.transprntc, notafiscal.ufdesembaraco, notafiscal.localdesembaraco, ";
			$query .= "notafiscal.dtdesembaraco, notafiscal.numerodi, notafiscal.dtregistrodi, notafiscal.totalbaseii, notafiscal.totalii, ";
			$query .= "notafiscal.totaliof, notafiscal.totalseguro, notafiscal.totaldespaduaneira, notafiscal.totalsiscomex, notafiscal.modelodocfiscal, ";
			$query .= "notafiscal.chavenferef, notafiscal.finalidade, notafiscal.tipoemissao, notafiscal.totalbasepis, notafiscal.totalbasecofins, ";
			$query .= "notafiscal.geraestoque, notafiscal.gerafinanceiro, notafiscal.geraliquidado, notafiscal.gerafiscal, ";
			$query .= "notafiscal.geraicms, notafiscal.geraipi, notafiscal.gerapiscofins, notafiscal.geracustomedio, notafiscal.xmlnfe, ";
			$query .= "notafiscal.dtdigitacao, notafiscal.totalseguroc, notafiscal.totalbaseisento, notafiscal.idnotafrete, notafiscal.totalnotafrete, ";
			$query .= "notafiscal.codtabela, notafiscal.protocolo, notafiscal.codrepresentante, notafiscal.codpremio, notafiscal.codfornecref, ";
			$query .= "notafiscal.totalcustotab, notafiscal.codrastreamento, notafiscal.dtrastreamento, notafiscal.financpercentual, ";
			$query .= "notafiscal.totalgnre, notafiscal.emissaopropria, notafiscal.codigostatus, notafiscal.xmotivo, notafiscal.tipoevento, ";
			$query .= "notafiscal.cce, notafiscal.protocolonfe, notafiscal.protocolocce, notafiscal.sequenciaevento, notafiscal.dataautorizacao, ";
			$query .= "notafiscal.datacancelamento, notafiscal.datacce, notafiscal.hrdigitacao, notafiscal.viatransporte, notafiscal.totalvalorafrmm, ";
			$query .= "notafiscal.tipoimportacao, notafiscal.cnpjadquirente, notafiscal.ufterceiro, notafiscal.protocolocanc, ";
			$query .= "notafiscal.statuscontabil, notafiscal.idnotafiscalref, notafiscal.impresso, notafiscal.superimpressao, notafiscal.tipoajuste, notafiscal.indpres ";
			$query .= "FROM notafiscal ";
			$query .= "INNER JOIN estabelecimento ON (estabelecimento.codestabelec = notafiscal.codestabelec) ";
			$query .= "INNER JOIN natoperacao ON (natoperacao.natoperacao = notafiscal.natoperacao) ";
			$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal IN (".implode(",", $arr_codestabelec)."))  ";
			$query .= "	AND notafiscal.operacao IN ('SE','SS') AND notafiscal.dtentrega BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$query .= "	AND notafiscal.status NOT IN ('C','I','P','D') ";
			$query .= " AND notafiscal.gerafiscal = 'S' ";
			$query .= " AND natoperacao.geraspedpiscofins = 'S' ";
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND notafiscal.dtentrega >= '".$valor."' ";
				}
				if($coluna == "dtentregafim"){
					$query .= "	AND notafiscal.dtentrega <= '".$valor."' ";
				}
				if(!in_array($coluna, array("dtentregaini", "dtentregafim"))){
					$query .= "	AND notafiscal.".$coluna." = '".$valor."' ";
				}
			}
			$query .= "ORDER BY dtentrega ";

			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_notafiscalservico[$row["idnotafiscal"]] = $row;
				$arr_idnotafiscalservico[] = $row["idnotafiscal"];
			}

			if(sizeof($arr_idnotafiscalservico) > 0){
				$query = "SELECT itnotafiscal.*, (itnotafiscal.totalbruto + itnotafiscal.totalacrescimo + itnotafiscal.totalseguro) AS totalbruto, ";
				$query .= "natoperacao.cstpiscofins ";
				$query .= "FROM itnotafiscal ";
				$query .= "INNER JOIN natoperacao ON (itnotafiscal.natoperacao = natoperacao.natoperacao) ";
				$query .= "WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscalservico).") AND composicao IN ('N','P') ";
				$query .= "ORDER BY idnotafiscal, seqitem ";

				$res = $this->con->query($query);
				$arr = $res->fetchAll(2);
				foreach($arr as $row){
					$row["preco"] = $row["precopolitica"];
					$this->arr_notafiscalservico[$row["idnotafiscal"]]["itnotafiscal"][] = $row;
				}
			}

			foreach($this->arr_notafiscalservico as $i => $notafiscalservico){
				if(in_array($notafiscalservico["status"], array("C", "D", "I"))){
					foreach($notafiscalservico as $coluna => $valor){
						if(strpos($coluna, "total") !== FALSE){
							$this->arr_notafiscalservico[$i][$coluna] = NULL;
						}
					}
					$this->arr_notafiscalservico[$i]["itnotafiscal"] = array();
				}
			}
		}

		// Busca as notas de frete
		$this->progresso("Carregando notas de frete");
		$this->arr_notafrete = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query = "SELECT DISTINCT notafrete.*, ";
			$query .= "	notafrete.totalliquido AS totalbasepis, notafrete.totalliquido AS totalbasecofins, ";
			$query .= "	(CASE WHEN natoperacao.gerapiscofins = 'N' THEN 0 WHEN estabelecimento.regimetributario = '2' THEN 0.65 ELSE 1.65 END) AS aliqpis, ";
			$query .= "	(CASE WHEN natoperacao.gerapiscofins = 'N' THEN 0 WHEN estabelecimento.regimetributario = '2' THEN 3.00 ELSE 7.60 END) AS aliqcofins, ";
			$query .= "	natoperacao.codconta ";
			$query .= "FROM notafrete ";
			$query .= "INNER JOIN natoperacao ON (notafrete.natoperacao = natoperacao.natoperacao) ";
			$query .= "INNER JOIN estabelecimento ON (notafrete.codestabelec = estabelecimento.codestabelec) ";
			$query .= "WHERE notafrete.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal IN (".implode(",", $arr_codestabelec).")) ";
			$query .= "	AND notafrete.dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$query .= " AND natoperacao.gerafiscal = 'S' ";
			$query .= " AND natoperacao.geraspedpiscofins = 'S' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$row["totalpis"] = round($row["totalbasepis"] * ($row["aliqpis"] / 100), 2);
				$row["totalcofins"] = round($row["totalbasecofins"] * ($row["aliqcofins"] / 100), 2);
				$this->arr_notafrete[$row["idnotafrete"]] = $row;
			}
		}

		// Busca as notas diversas
		$this->progresso("Carregando notas diversas");
		$this->arr_notadiversa = array();
		if(!$this->gerar_tudozerado && $this->gerar_notafiscal){
			$query = "SELECT  notadiversa.*, itnotadiversa.*, natoperacao.codconta  FROM notadiversa ";
			$query .= "LEFT JOIN itnotadiversa ON (notadiversa.idnotadiversa = itnotadiversa.idnotadiversa) ";
			$query .= "LEFT JOIN natoperacao ON (notadiversa.natoperacao = natoperacao.natoperacao)";
			$query .= "WHERE codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal IN (".implode(",", $arr_codestabelec).")) ";
			$query .= "	AND dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentregaini"){
					$query .= "	AND notadiversa.dtemissao >= '".$valor."' ";
				}elseif($coluna == "dtentregafim"){
					$query .= "	AND notadiversa.dtemissao <= '".$valor."' ";
				}else{
					if($coluna == "operacao"){
						$query .= " AND false ";
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

		// Busca os lancamentos das notas fiscais
		$this->progresso("Carregando lancamentos financeiros");
		$this->arr_lancamento = array();
		if(sizeof($arr_idnotafiscal) > 0){
			$res = $this->con->query("SELECT * FROM lancamento WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscal).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_lancamento[$row["idnotafiscal"]][] = $row;
			}
		}

		if(sizeof($arr_idnotafiscalservico) > 0){
			$res = $this->con->query("SELECT * FROM lancamento WHERE idnotafiscal IN (".implode(",", $arr_idnotafiscalservico).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_lancamento[$row["idnotafiscal"]][] = $row;
			}
		}

		// Lsita com codigos de ECF
		$arr_codecf = array();

		// Busca os mapas resumo
		$this->progresso("Carregando mapa resumo");
		$this->arr_maparesumo = array();
		if(!$this->gerar_tudozerado && $this->gerar_cupom){
			$query = "SELECT * FROM maparesumo ";
			$query .= "INNER JOIN ecf ON (maparesumo.codecf = ecf.codecf) ";
			$query .= "WHERE maparesumo.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal IN (".implode(",", $arr_codestabelec).")) ";
			$query .= "	AND maparesumo.dtmovto BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$query .= " AND numeroreducoes <> 0 AND operacaofim <> 0 AND reiniciofim <> 0 ";
			$query .= "	AND ecf.equipamentofiscal = 'ECF' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			$arr_codmaparesumo = array();
			foreach($arr as $row){
				$this->arr_maparesumo[$row["codmaparesumo"]] = $row;
				$arr_codmaparesumo[] = $row["codmaparesumo"];
				$arr_codecf[] = $row["codecf"];
			}
		}

		// Busca as tributacoes por mapa resumo
		$this->progresso("Carregando tributacoes por mapa resumo");
		if(count($this->arr_maparesumo) > 0){
			$res = $this->con->query("SELECT * FROM maparesumoimposto WHERE codmaparesumo IN (".implode(",", $arr_codmaparesumo).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_maparesumo[$row["codmaparesumo"]]["maparesumoimposto"][] = $row;
			}
		}

		// Busca os itens de cupom
		$this->progresso("Carregando itens por mapa resumo");
		$this->arr_itcupom = array();
		if($this->gerar_cupom){
			$query = "SELECT itcupom.codproduto, SUM(itcupom.quantidade) AS quantidade, SUM(itcupom.desconto) AS desconto, SUM(itcupom.custorep) AS custorep, ";
			$query .= "	SUM(itcupom.valortotal) AS valortotal, SUM(itcupom.acrescimo) AS acrescimo, itcupom.aliqpis, itcupom.aliqcofins, ";
			$query .= "	SUM(round(itcupom.totalbasepis,2)) AS totalbasepis, SUM(round(itcupom.totalbasecofins,2)) AS totalbasecofins, SUM(itcupom.totalpis) AS totalpis, ";
			$query .= "	SUM(itcupom.totalcofins) AS totalcofins, cupom.dtmovto, cupom.codecf, ecf.equipamentofiscal, ";
			$query .= "	cupom.codestabelec, (CASE WHEN ecf.equipamentofiscal = 'SAT' THEN 'SAT' ELSE COALESCE(maparesumo.codmaparesumo::TEXT, 'SAT') END) AS codmaparesumo, ";
			$query .= " estabelecimento.natoperacaonfcupom, produto.fabricacaopropria, CASE WHEN classfiscal.tptribicms = 'F' THEN 'F' ELSE 'T' END AS tptribicms ";
			$query .= "FROM itcupom ";
			$query .= "INNER JOIN produto ON (itcupom.codproduto = produto.codproduto) ";
			$query .= "INNER JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
			$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
			$query .= "INNER JOIN estabelecimento ON (cupom.codestabelec = estabelecimento.codestabelec) ";
			$query .= "INNER JOIN ecf ON (cupom.codecf = ecf.codecf) ";
			$query .= "LEFT JOIN maparesumo ON (cupom.codestabelec = maparesumo.codestabelec AND cupom.dtmovto = maparesumo.dtmovto AND cupom.caixa = maparesumo.caixa AND cupom.numeroecf = maparesumo.numeroecf) ";
			$query .= "WHERE cupom.status = 'A' ";
			$query .= "	AND itcupom.status = 'A' ";
			$query .= " AND itcupom.composicao != 'F' ";
			$query .= " AND itcupom.valortotal >= 0.01 ";
			$query .= " AND (substr(cupom.chavecfe,21,2) != '65' OR chavecfe is null) ";
			$query .= "	AND cupom.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal IN (".implode(",", $arr_codestabelec).")) ";
			$query .= "	AND cupom.dtmovto BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$query .= "	AND (maparesumo.codmaparesumo IS NOT NULL OR (maparesumo.codmaparesumo IS NULL AND cupom.chavecfe IS NOT NULL)) ";
			$query .= "GROUP BY itcupom.codproduto, itcupom.aliqpis, itcupom.aliqcofins, cupom.dtmovto, codmaparesumo, cupom.codecf, ecf.equipamentofiscal, cupom.codestabelec,  ";
			$query .= "natoperacaonfcupom, produto.fabricacaopropria, 20 ";

			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$codmaparesumo = $row["codmaparesumo"];
				$equipamentofiscal = $row["equipamentofiscal"];
				if($equipamentofiscal == "SAT"){
					$codmaparesumo = "SAT";
				}elseif(strlen($codmaparesumo) === 0 && $equipamentofiscal === "ECF"){
					continue;
				}
				$this->arr_itcupom[$codmaparesumo][] = $row;
				$arr_codecf[] = $row["codecf"];
			}
		}

		// Verifica se criou um array dos itens para cada mapa resumo
		foreach($this->arr_maparesumo as $maparesumo){
			if(!is_array($this->arr_itcupom[$maparesumo["codmaparesumo"]])){
				$this->arr_itcupom[$maparesumo["codmaparesumo"]] = array();
			}
		}

		// Busca os ECFs
		$this->progresso("Carregando ECFs");
		$this->arr_ecf = array();
		foreach($this->arr_maparesumo as $maparesumo){
			if(strlen($maparesumo["codecf"]) == 0){
				$_SESSION["ERROR"] = "O mapa resumo de chave <b>".$maparesumo["codmaparesumo"]."</b> encontrase sem um ECF associado. Para processeguir com a gera&ccedil;&atilde;o do arquivo &eacute; necess&aacute;rio relacionar um ECF ao mapa resumo.<br><a onclick=\"$.messageBox('close');openProgram('MapaResumo','codmaparesumo=".$maparesumo["codmaparesumo"]."')\">Clique aqui</a> para abrir o mapa resumo.";
				return FALSE;
			}
			$arr_codecf[] = $maparesumo["codecf"];
		}
		$arr_codecf = array_unique($arr_codecf);
		if(sizeof($arr_codecf) > 0){
			$res = $this->con->query("SELECT * FROM ecf WHERE codecf IN (".implode(",", $arr_codecf).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_ecf[$row["numfabricacao"]] = $row;
			}
		}

		// Busca dados de diarios das ECFs
		if(count($arr_codecf) > 0){
			$query = "SELECT ecf.numfabricacao, cupom.dtmovto, MIN(cupom.seqecf) AS cupomini, MAX(cupom.seqecf) AS cupomfim ";
			$query .= "FROM cupom ";
			$query .= "INNER JOIN ecf USING (codecf) ";
			$query .= "WHERE dtmovto BETWEEN '".$this->datainicial."' AND '".$this->datafinal."' ";
			$query .= "  AND ((cupom.status = 'C' AND ecf.equipamentofiscal = 'ECF') OR cupom.status = 'A') ";
			$query .= " AND (substr(cupom.chavecfe,21,2) != '65' OR chavecfe is null) ";
			$query .= "GROUP BY 1, 2 ";
			$query .= "ORDER BY 1, 2 ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_ecf[$row["numfabricacao"]]["resumodiario"][$row["dtmovto"]] = $row;
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

		// Carrega produtos das notas fiscais de serviços
		foreach($this->arr_notafiscalservico as $notafiscalservico){ // Busca os produtos das notas fiscais
			foreach($notafiscalservico["itnotafiscal"] as $itnotafiscalservico){
				$arr_codproduto[] = $itnotafiscalservico["codproduto"];
			}
		}

		foreach($this->arr_itcupom as $arr_itcupom){ // Busca os produtos dos cupons fiscais
			foreach($arr_itcupom as $itcupom){
				$arr_codproduto[] = $itcupom["codproduto"];
			}
		}
		$arr_codproduto = array_unique($arr_codproduto);
		if(sizeof($arr_codproduto) > 0){
			$res = $this->con->query("SELECT * FROM produto WHERE codproduto IN (".implode(",", $arr_codproduto).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_produto[$row["codproduto"]] = $row;
			}
		}

		// Busca todos os participantes das notas fiscais
		$this->progresso("Carregando parceiros das notas fiscais");
		$this->arr_cliente = array();
		$this->arr_estabelecimento_parc = array();
		$this->arr_fornecedor = array();
		$this->arr_transportadora = array();
		$arr_codcliente = array();
		$arr_codestabelec_parc = array();
		$arr_codfornec = array();
		$arr_codtransp = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];

			if($notafiscal["nfce"] == "S"){
				$arr_codcliente[] = $notafiscal["codparceiro"];
				continue;
			}

			if(strlen($notafiscal["codparceiro"]) === 0){
				continue;
			}

			switch($operacaonota["parceiro"]){
				case "C": $arr_codcliente[] = $notafiscal["codparceiro"];
					break;
				case "E": $arr_codestabelec_parc[] = $notafiscal["codparceiro"];
					break;
				case "F": $arr_codfornec[] = $notafiscal["codparceiro"];
					break;
			}
			if(strlen($notafiscal["codtransp"]) > 0){
				$arr_codtransp[] = $notafiscal["codtransp"];
			}
		}
		foreach($this->arr_notacomplemento as $notacomplemento){
			if(strlen($notacomplemento["codparceiro"]) === 0){
				continue;
			}
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
		foreach($this->arr_notafrete as $notafrete){
			$arr_codtransp[] = $notafrete["codtransp"];
		}
		foreach($this->arr_notadiversa as $notadiversa){
			if(strlen($notadiversa["codparceiro"]) === 0){
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
		foreach($this->arr_notafiscalservico as $notafiscalservico){
			if(strlen($notafiscalservico["codparceiro"]) === 0){
				continue;
			}
			switch($notafiscalservico["tipoparceiro"]){
				case "C": $arr_codcliente[] = $notafiscalservico["codparceiro"];
					break;
				case "E": $arr_codestabelec[] = $notafiscalservico["codparceiro"];
					break;
				case "F": $arr_codfornec[] = $notafiscalservico["codparceiro"];
					break;
			}
		}

		$arr_codcliente = array_filter(array_unique($arr_codcliente));
		$arr_codestabelec = array_filter(array_unique($arr_codestabelec));
		$arr_codfornec = array_filter(array_unique($arr_codfornec));

		if(sizeof($arr_codcliente) > 0){
			$res = $this->con->query("SELECT * FROM cliente WHERE codcliente IN (".implode(",", $arr_codcliente).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_cliente[$row["codcliente"]] = $row;
			}
		}
		if(sizeof($arr_codestabelec_parc) > 0){
			$res = $this->con->query("SELECT * FROM estabelecimento WHERE codestabelec IN (".implode(",", $arr_codestabelec_parc).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_estabelecimento_parc[$row["codestabelec"]] = $row;
			}
		}
		if(sizeof($arr_codfornec) > 0){
			$res = $this->con->query("SELECT * FROM fornecedor WHERE codfornec IN (".implode(",", $arr_codfornec).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_fornecedor[$row["codfornec"]] = $row;
			}
		}
		if(sizeof($arr_codtransp) > 0){
			$res = $this->con->query("SELECT * FROM transportadora WHERE codtransp IN (".implode(",", $arr_codtransp).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_transportadora[$row["codtransp"]] = $row;
			}
		}

		// Verifica se os parceiros estao com CPF/CNPJ informados
		$this->progresso("Validado dados dos parceiros");
		foreach($this->arr_cliente as $cliente){
			if($cliente["codcliente"] == $this->paramestoque->getcodclientevendapdv()){
				continue;
			}
			if(strlen(trim($cliente["cpfcnpj"])) == 0 && $cliente["codpaisres"] == "01058"){
				$_SESSION["ERROR"] = "Informe o CPF/CNPJ para o cliente <b>".$cliente["codcliente"]."</b> (".$cliente["nome"].").<br><a onclick=\"$.messageBox('close'); openProgram('ClientePF','codclientec=".$cliente["codcliente"]."')\">Clique aqui</a> para abrir o cadastro de clientes.";
				return FALSE;
			}
		}
		foreach($this->arr_estabelecimento_parc as $estabelecimento){
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

		// Busca as cidades
		$this->progresso("Carregando cidades");
		$this->arr_cidade = array();
		$arr_codcidade = array();
		foreach($this->arr_estabelecimento as $estabelecimento){
			$arr_codcidade[] = $estabelecimento->getcodcidade();
		}
		foreach($this->arr_cliente as $cliente){
			$arr_codcidade[] = $cliente["codcidaderes"];
		}
		foreach($this->arr_contabilidade as $contabilidade){
			$arr_codcidade[] = $contabilidade["codcidade"];
		}
		foreach($this->arr_estabelecimento_parc as $estabelecimento){
			$arr_codcidade[] = $estabelecimento["codcidade"];
		}
		foreach($this->arr_fornecedor as $fornecedor){
			$arr_codcidade[] = $fornecedor["codcidade"];
		}
		foreach($this->arr_transportadora as $transportadora){
			$arr_codcidade[] = $transportadora["codcidade"];
		}
		if(sizeof($arr_codcidade) > 0){
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
		if(sizeof($arr_codcf) > 0){
			$res = $this->con->query("SELECT * FROM classfiscal WHERE codcf IN (".implode(",", array_unique($arr_codcf)).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_classfiscal[$row["codcf"]] = $row;
			}
		}
		if(sizeof($arr_codipi) > 0){
			$res = $this->con->query("SELECT * FROM ipi WHERE codipi IN (".implode(",", array_unique($arr_codipi)).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_ipi[$row["codipi"]] = $row;
			}
		}
		/*
		  foreach($this->arr_notafiscalservico as $notafiscalservico){
		  foreach($notafiscalservico["itnotafiscalservico"] as $itnotafiscalservico){
		  $arr_codpiscofins[] = $itnotafiscalservico["cstpiscofins"];
		  }
		  }
		 *
		 */
		if(sizeof($arr_codpiscofins) > 0){
			$res = $this->con->query("SELECT * FROM piscofins WHERE codpiscofins IN (".implode(",", array_unique($arr_codpiscofins)).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_piscofins[$row["codpiscofins"]] = $row;
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
		if(sizeof($arr_idncm) > 0){
			$res = $this->con->query("SELECT * FROM ncm WHERE idncm IN (".implode(",", array_unique($arr_idncm)).")");
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
		if(sizeof($arr_codproduto) > 0){
			$res = $this->con->query("SELECT * FROM produtoean WHERE codproduto IN (".implode(",", $arr_codproduto).")");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_produtoean[$row["codproduto"]][] = $row;
			}
		}

		// Busca as naturezas de receita
		$this->progresso("Carregando naturezas de receita");
		$this->arr_natreceita = array();
		$res = $this->con->query("SELECT codproduto, natreceita FROM produto");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_natreceita[$row["codproduto"]] = $row["natreceita"];
		}

		// Verifica se existe nota fiscal e/ou cupom fiscal no periodo informado
		if(count($this->arr_notafiscal) === 0 && count($this->arr_notadiversa) === 0 && count($this->arr_maparesumo) === 0 && count($this->arr_itcupom) === 0 && count($this->arr_notafiscalservico) === 0){
			$_SESSION["ERROR"] = "N&atilde;o foi encontrado movimento no per&iacute;odo informado.";
			return FALSE;
		}

		// Limpa as variaveis para liberar mais memoria
		unset($arr_codcf);
		unset($arr_codcidade);
		unset($arr_codcliente);
		unset($arr_codcontabilidade);
		unset($arr_codecf);
		unset($arr_codestabelec);
		unset($arr_codestabelec_parc);
		unset($arr_codfornec);
		unset($arr_codipi);
		unset($arr_codmaparesumo);
		unset($arr_codpiscofins);
		unset($arr_codproduto);
		unset($arr_codtransp);

		// Prepara alguns dados antes da geracao
		$this->progresso("Preparando dados para iniciar geracao");
		$this->m200_arr_itcupom = array();
		$this->m200_arr_itnotafiscal = array();
		$this->m200_vl_tot_cont_cum_per = 0;
		$this->m200_vl_tot_cont_nc_per = 0;
		$this->m600_vl_tot_cont_cum_per = 0;
		$this->m600_vl_tot_cont_nc_per = 0;
		$arr_codmaparesumo = array("SAT");
		foreach($this->arr_maparesumo as $maparesumo){
			$arr_codmaparesumo[] = $maparesumo["codmaparesumo"];
		}
		foreach($arr_codmaparesumo as $codmaparesumo){
			if(is_array($this->arr_itcupom[$codmaparesumo])){
				foreach($this->arr_itcupom[$codmaparesumo] as $itcupom){
					$estabelecimento = $this->arr_estabelecimento[$itcupom["codestabelec"]];
					$cstpiscofins = $this->cstpiscofins($itcupom);
					if($itcupom["aliqpis"] > 0 && in_array($cstpiscofins, array("01", "02", "03", "04", "05"))){
						$this->m200_arr_itcupom[] = $itcupom;
						if($estabelecimento->getregimetributario() == "2"){
							$this->m200_vl_tot_cont_cum_per += $itcupom["totalbasepis"] * $itcupom["aliqpis"] / 100;
							$this->m600_vl_tot_cont_cum_per += $itcupom["totalbasecofins"] * $itcupom["aliqcofins"] / 100;
						}else{
							$this->m200_vl_tot_cont_nc_per += $itcupom["totalbasepis"] * $itcupom["aliqpis"] / 100;
							$this->m600_vl_tot_cont_nc_per += $itcupom["totalbasecofins"] * $itcupom["aliqcofins"] / 100;
						}
					}
				}
			}
		}
		foreach($this->arr_notafiscal as $notafiscal){
			$estabelecimento = $this->arr_estabelecimento[$notafiscal["codestabelec"]];
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			if($operacaonota["tipo"] != "S" || $notafiscal["totalpis"] == 0){
				continue;
			}
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$cstpiscofins = $this->cstpiscofins($itnotafiscal);
				if($itnotafiscal["aliqpis"] > 0 && in_array($cstpiscofins, array("01", "02", "03", "04", "05"))){
					$this->m200_arr_itnotafiscal[] = $itnotafiscal;
					if($estabelecimento->getregimetributario() == "2"){
						$this->m200_vl_tot_cont_cum_per += $itnotafiscal["totalpis"];
						$this->m600_vl_tot_cont_cum_per += $itnotafiscal["totalcofins"];
					}else{
						$this->m200_vl_tot_cont_nc_per += $itnotafiscal["totalpis"];
						$this->m600_vl_tot_cont_nc_per += $itnotafiscal["totalcofins"];
					}
				}
			}
		}
		/*
		  foreach($this->arr_notafiscalservico as $notafiscalservico){
		  if($notafiscalservico["indicadoroperacao"] == "0"){
		  continue;
		  }
		  foreach($notafiscalservico["itnotafiscalservico"] as $itnotafiscalservico){
		  $this->m200_arr_itnotafiscalservico[] = $itnotafiscalservico;
		  if($estabelecimento->getregimetributario() == "2"){
		  $this->m200_vl_tot_cont_cum_per += $itnotafiscalservico["valorpis"];
		  $this->m600_vl_tot_cont_cum_per += $itnotafiscalservico["valorcofins"];
		  }else{
		  $this->m200_vl_tot_cont_nc_per += $itnotafiscalservico["valorpis"];
		  $this->m600_vl_tot_cont_nc_per += $itnotafiscalservico["valorcofins"];
		  }
		  }
		  }
		 */
		foreach($this->arr_notafiscalservico as $notafiscalservico){
			foreach($notafiscalservico["itnotafiscal"] as $itnotafiscalservico){
				$this->m200_arr_itnotafiscalservico[] = $itnotafiscalservico;
				if($estabelecimento->getregimetributario() == "2"){
					$this->m200_vl_tot_cont_cum_per += $itnotafiscalservico["totalpis"];
					$this->m600_vl_tot_cont_cum_per += $itnotafiscalservico["totalcofins"];
				}else{
					$this->m200_vl_tot_cont_nc_per += $itnotafiscalservico["totalpis"];
					$this->m600_vl_tot_cont_nc_per += $itnotafiscalservico["totalcofins"];
				}
			}
		}
		// Inicia a criacao de cada bloco
		$this->arquivo_nome = $this->matriz->getdirarqfiscal()."SPEDPISCofins_".str_pad($this->matriz->getcodestabelec(), 4, "0", STR_PAD_LEFT)."_".substr($this->datainicial, 0, 4)."_".substr($this->datainicial, 5, 2).".txt";
		$this->arquivo = fopen($this->arquivo_nome, "w+");
		$this->progresso("Gerando bloco 0");
		$this->bloco_0();
		$this->progresso("Gerando bloco A");
		$this->bloco_A();
		$this->progresso("Gerando bloco C");
		$this->bloco_C();
		$this->progresso("Gerando bloco D");
		$this->bloco_D();
		$this->progresso("Gerando bloco F");
		$this->bloco_F();
		$this->progresso("Gerando bloco M");
		$bloco_M = $this->bloco_M();
		$this->progresso("Gerando bloco 1");
		$bloco_1 = $this->bloco_1();

		$reduz_m990 = 0;

		foreach($bloco_M as $i => $registro){
			switch($registro["REG"]){
				case "M200":
					$bloco_M[$i]["VL_TOT_CRED_DESC_ANT"] = $this->valor_decimal($this->_1100_vl_cred_desc_efd, 2);
					$bloco_M[$i]["VL_TOT_CONT_NC_DEV"] = $this->valor_decimal((number_format(value_numeric($registro["VL_TOT_CONT_NC_PER"]), 2, ".", "") - number_format(value_numeric($registro["VL_TOT_CRED_DESC"]), 2, ".", "") - number_format(value_numeric($bloco_M[$i]["VL_TOT_CRED_DESC_ANT"]), 2, ".", "")), 2);
					$bloco_M[$i]["VL_CONT_NC_REC"] = $this->valor_decimal((number_format(value_numeric($bloco_M[$i]["VL_TOT_CONT_NC_DEV"]), 2, ".", "") - number_format(value_numeric($registro["VL_RET_NC"]), 2, ".", "") - number_format(value_numeric($registro["VL_OUT_DED_NC"]), 2, ".", "")), 2);
					$bloco_M[$i]["VL_TOT_CONT_REC"] = $this->valor_decimal((number_format(value_numeric($bloco_M[$i]["VL_TOT_CONT_NC_DEV"]), 2, ".", "") + number_format(value_numeric($registro["VL_CONT_CUM_REC"]), 2, ".", "")), 2);

					$vl_cont_nc_rec = $bloco_M[$i]["VL_CONT_NC_REC"];
					$vl_cont_cum_rec = $bloco_M[$i]["VL_CONT_CUM_REC"];
					break;
				case "M205":
					$vl_debito = ($bloco_M[$i]["NUM_CAMPO"] == "08" ? $vl_cont_nc_rec : $vl_cont_cum_rec);
					if(value_numeric($vl_debito) > 0){
						$bloco_M[$i]["VL_DEBITO"] = $vl_debito;
					}else{
						unset($bloco_M[$i]);
						$reduz_m990++;
					}
					break;
				case "M600":
					$bloco_M[$i]["VL_TOT_CRED_DESC_ANT"] = $this->valor_decimal($this->_1500_vl_cred_desc_efd, 2);
					$bloco_M[$i]["VL_TOT_CONT_NC_DEV"] = $this->valor_decimal((number_format(value_numeric($registro["VL_TOT_CONT_NC_PER"]), 2, ".", "") - number_format(value_numeric($registro["VL_TOT_CRED_DESC"]), 2, ".", "") - number_format(value_numeric($bloco_M[$i]["VL_TOT_CRED_DESC_ANT"]), 2, ".", "")), 2);
					$bloco_M[$i]["VL_CONT_NC_REC"] = $this->valor_decimal((number_format(value_numeric($bloco_M[$i]["VL_TOT_CONT_NC_DEV"]), 2, ".", "") - number_format(value_numeric($registro["VL_RET_NC"]), 2, ".", "") - number_format(value_numeric($registro["VL_OUT_DED_NC"]), 2, ".", "")), 2);
					$bloco_M[$i]["VL_TOT_CONT_REC"] = $this->valor_decimal((number_format(value_numeric($bloco_M[$i]["VL_TOT_CONT_NC_DEV"]), 2, ".", "") + number_format(value_numeric($registro["VL_CONT_CUM_REC"]), 2, ".", "")), 2);

					$vl_cont_nc_rec = $bloco_M[$i]["VL_CONT_NC_REC"];
					$vl_cont_cum_rec = $bloco_M[$i]["VL_CONT_CUM_REC"];
					break;
				case "M605":
					$vl_debito = ($bloco_M[$i]["NUM_CAMPO"] == "08" ? $vl_cont_nc_rec : $vl_cont_cum_rec);
					if(value_numeric($vl_debito) > 0){
						$bloco_M[$i]["VL_DEBITO"] = $vl_debito;
					}else{
						unset($bloco_M[$i]);
						$reduz_m990++;
					}
					break;
				case "M990":
					$bloco_M[$i]["QTD_LIN_M"] -= $reduz_m990;
					break;
			}
		}

		$this->escrever_bloco($bloco_M);
		$this->escrever_bloco($bloco_1);
		$this->progresso("Gerando bloco 9");
		$this->bloco_9();
		fclose($this->arquivo);

		if(param("SISTEMA", "TIPOSERVIDOR", $this->con) == "1"){
			$this->download();
		}

		return TRUE;
	}

	public function gerar_blocomzerado($b){
		if(is_bool($b)){
			$this->gerar_blocomzerado = $b;
		}
	}

	public function gerar_cupom($b){
		if(is_bool($b)){
			$this->gerar_cupom = $b;
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

	// Abertura, idetificacao e referencias
	protected function bloco_0(){
		$this->registro_0000();
		$this->registro_0990();
	}

	// Documentos fiscais - servicos (nao sujeitos ao ICMS)
	protected function bloco_A(){
		$this->registro_A001();
		$this->registro_A990();
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

	// Demais documentos e operacoes
	protected function bloco_F(){
		$this->registro_F001();
		$this->registro_F990();
	}

	// Apuracao da contribuicao e credito do PIS/PASEP e da COFINS
	protected function bloco_M(){
		$registros = array();
		if(!$this->gerar_blocomzerado){
			$registros = array_merge($registros, $this->registro_M001());
			$registros = array_merge($registros, $this->registro_M990($registros));
		}else{
			$registros = array(
				array("M001", "0"),
				array("M200", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00"),
				array("M600", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00", "0,00"),
				array("M990", "4")
			);
		}
		return $registros;
	}

	// Complemento da escrituracao - controle de saldos de creditos e de retencoes, operacoes extemporaneas e outras informacoes
	protected function bloco_1(){
		$registros = array();
		$registros = array_merge($registros, $this->registro_1001());
		$registros = array_merge($registros, $this->registro_1990($registros));
		return $registros;
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
		$cidade = $this->arr_cidade[$this->matriz->getcodcidade()];

		// Calcula a data incial do arquivo (deve ser sempre o primeiro dia do mes)
		$arr_datainicial = explode("-", $this->datainicial);
		$time_datainicial = mktime(0, 0, 0, $arr_datainicial[1], 1, $arr_datainicial[0]);
		$dt_ini = date("Y-m-d", $time_datainicial);

		// Calcula a data final do arquivo (deve ser sempre o ultimo dia do mes)
		$arr_datafinal = explode("-", $this->datafinal);
		$time_datafinal = mktime(0, 0, 0, $arr_datafinal[1] + 1, 1, $arr_datafinal[0]);
		$dt_fin = date("Y-m-d", strtotime("-1 day", $time_datafinal));

		if(compare_date($this->datainicial, "2012-07-01", "Y-m-d", "<")){
			$cod_ver = "002";
		}elseif(compare_date($this->datainicial, "2018-06-01", "Y-m-d", "<")){
			$cod_ver = "003";
		}elseif(compare_date($this->datainicial, "2019-01-01", "Y-m-d", "<")){
			$cod_ver = "004";
		}else{
			$cod_ver = "005";
		}

		$registro = array(
			// Texto fixo contento "0000"
			"REG" => "0000",
			// Codigo da versao do layout conforme a tabela 3.1.1
			"COD_VER" => $cod_ver,
			// Tipo de escrtituracao:
			// 0 - Original
			// 1 - Retificadora
			"TIPO_ESCRIT" => $this->tipoescrituracao,
			// Indicador de situacao especial:
			// 0 - Abertura
			// 1 - Cisao
			// 2 - Fusao
			// 3 - Incorporacao
			// 4 - Encerramento
			"IND_SIT_ESP" => "",
			// Numero do Recibo da Escrituracao anterior a ser retificada, utilizado quando TIPO_ESCRIT for igual a 1
			"NUM_REC_ANTERIOR" => $this->numerorecibo,
			// Data inicial das informacoes contidas no arquivo
			"DT_INI" => $this->valor_data($dt_ini),
			// Data final das informacoes contidas no arquivo
			"DT_FIN" => $this->valor_data($dt_fin),
			// Nome empresarial da pessoa juridica
			"NOME" => $this->matriz->getrazaosocial(),
			// Numero de inscricao do estabelecimento matriz da pessoa juridica no CNPJ
			"CNPJ" => removeformat($this->matriz->getcpfcnpj()),
			// Sigla da unidade da federacao da pessoa juridica
			"UF" => $cidade["uf"],
			// Codigo do municipio do domicilio fiscal da entidade, conforme a tabela do IBGE
			"COD_MUN" => $cidade["codoficial"],
			// Inscricao da entidade no SUFRAMA
			"SUFRAMA" => "",
			// Indicador da natureza da pessoa juridica
			// 00 - Sociedade empresaria em geral
			// 01 - Sociedade cooperativa
			// 02 - Entidade sujeita ao PIS/Pasep exclusivamente com base na Folha de Salario
			"IND_NAT_PJ" => "",
			// Indicado de tipo de atividade preponderante:
			// 0 - Industrial ou equiparado a industrial
			// 1 - Prestador de servicos
			// 2 - Atividade de comercio
			// 3 - Atividade financeira
			// 4 - Atividade imobiliaria
			// 9 - Outros
			"IND_ATIV" => "2"
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
		$this->registro_0100();
		$this->registro_0110();
		$this->registro_0140();
		$this->registro_0500();
		$this->registro_0600();
	}

	// Dados do contabilista [nivel 2]
	protected function registro_0100(){
		$contabilidade = reset($this->arr_contabilidade);
		$cidade = $this->arr_cidade[$contabilidade["codcidade"]];
		$registro = array(
			// Texto fixo contento "0100"
			"REG" => "0100",
			// Nome do contabilista
			"NOME" => $contabilidade["nomecontador"],
			// Numero de inscricao do contabilista no CPF
			"CPF" => removeformat($contabilidade["cpfcontador"]),
			// Numero de inscricao do contabilista no Conselho Reginal de Contabilidade
			"CRC" => str_pad(removeformat($contabilidade["crccontador"]), 15, "0", STR_PAD_LEFT),
			// Numero de inscricao do escritorio do contabilidade no CNPJ
			"CNPJ" => removeformat($contabilidade["cpfcnpj"]),
			// Codigo do endereco postal
			"CEP" => removeformat($contabilidade["cep"]),
			// Logradouro e endereco do imovel
			"END" => $contabilidade["endereco"],
			// Numero do imovel
			"NUM" => $contabilidade["numero"],
			// Dados complementares do endereco
			"COMPL" => $contabilidade["complemento"],
			// Bairro em que o imovel enta situado
			"BAIRRO" => $contabilidade["bairro"],
			// Numero do telefone
			"FONE" => $this->valor_telefone($contabilidade["telefone"]),
			// Numero do fax
			"FAX" => $this->valor_telefone($contabilidade["fax"]),
			// Endereco do correio eletronico
			"EMAIL" => $contabilidade["email"],
			// Codigo do municipio, conforme a tabela IBGE
			"COD_MUN" => $cidade["codoficial"]
		);
		$this->escrever_registro($registro);
	}

	// Regimes de apuracao da contribuicao social e de apropriacao de credito [nivel 2]
	protected function registro_0110(){
		$arr_regimetributario = array();
		foreach($this->arr_estabelecimento as $estabelecimento){
			$arr_regimetributario[] = $estabelecimento->getregimetributario();
		}
		$arr_regimetributario = array_unique($arr_regimetributario);
		if(sizeof($arr_regimetributario) == 1){
			$regimetributario = array_shift($arr_regimetributario);
			$cod_inc_trib = ($regimetributario == "2" ? "2" : "1");
		}else{
			$cod_inc_trib = "3";
		}

		$registro = array(
			// Texto fixo contento "0110"
			"REG" => "0110",
			// Codigo indicador da incidencia tributaria no periodo:
			// 1 - Escrituracao de operacoes com incidencia exclusivamente no regime nao-cumulativo
			// 2 - Escrituracao de operacoes com incidencia exclusivamente no regime cumulativo
			// 3 - Escrituracao de operacoes com incidencia nos regimes nao-cumulativos e cumulativo
			"COD_INC_TRIB" => $cod_inc_trib,
			// Codigo indicador de metodo de apropriacao de creditos comuns, no caso de incidencia no
			// regime nao-cumulativo (COD_INC_TRIB = 1 ou 3):
			// 1 - Metodo de apropriacao direta
			// 2 - Metodo de rateio proporcional (receita bruta)
			"IND_APRO_CRED" => (in_array($cod_inc_trib, array("1", "3")) ? "1" : ""),
			// Codigo indicador do tipo de contribuicao apurada no periodo:
			// 1 - Apuracao da contribuicao exclusivamente a aliquota basica
			// 2 - Apuracao da contribuicao a aliquotas especificas (diferenciadas e/ou por unidade de medida de produto)
			"COD_TIPO_CONT" => "1"
		);

		if(compare_date($this->datainicial, "2012-07-01", "Y-m-d", ">=")){
			// Codigo indicador do criterio de escrituracao e apuracao adotado, no caso de incidencia exclusivameente no
			// regime cumulativo (COD_INC_TRIB = 2), pela pessoa juridica submetida ao regime de tributacao com base no
			// lucro presumido:
			// 1 - Regime de caixa - escrituracao consolidada (registro F500)
			// 2 - Regime de competencia - escrituracao consolidada (registro F550)
			// 9 - Regime de competencia - escrituracao detalhada, com base nos registros dos blocos "A", "C", "D" e "F"
			$registro["IND_REG_CUM"] = ($cod_inc_trib == "2" ? "" : "");
		}

		$this->escrever_registro($registro);
		$this->registro_0111();
	}

	// Tabela de receita bruta mensal para fins de rateio de credito comum [nivel 3]
	protected function registro_0111(){
		return array();
	}

	// Tabela do cadastro de estabelecimento [nivel 2]
	protected function registro_0140(){
		foreach($this->arr_estabelecimento as $estabelecimento){
			$cidade = $this->arr_cidade[$estabelecimento->getcodcidade()];
			$registro = array(
				// Texto fixo contendo "0140"
				"REG" => "0140",
				// Codigo de identificacao do estabelecimento
				"COD_EST" => $estabelecimento->getcodestabelec(),
				// Nome empresarial do estabelecimento
				"NOME" => $estabelecimento->getrazaosocial(),
				// Numero de inscricao do estabelecimento no CNPJ
				"CNPJ" => removeformat($estabelecimento->getcpfcnpj()),
				// Sigla da unidade da federacao do estabelecimento
				"UF" => $estabelecimento->getuf(),
				// Inscricao Estadual do estabelecimento, se contribuinte de ICMS
				"IE" => removeformat($estabelecimento->getrgie()),
				// Codigo do municipio do domicilio fiscal do estabelecimento, conforme a tabela IBGE
				"COD_MUN" => $cidade["codoficial"],
				// Inscricao Municipal do estabelecimento, se contribuinte do ISS
				"IM" => "",
				// Incricao do estabelecimento no SUFRAMA
				"SUFRAMA" => ""
			);
			$this->escrever_registro($registro);
			$this->registro_0150($estabelecimento);
			$this->registro_0190($estabelecimento);
			$this->registro_0200($estabelecimento);
			$this->registro_0400($estabelecimento);
			$this->registro_0450($estabelecimento);
		}
	}

	// Tabela de cadastro do participante [nivel 3]
	protected function registro_0150($estabelecimento){
		$arr_codparceiro = array();
		foreach($this->arr_notafiscal as $notafiscal){
			if($notafiscal["codestabelec"] == $estabelecimento->getcodestabelec()){
				$operacao = $this->arr_operacaonota[$notafiscal["operacao"]];
				$arr_codparceiro[] = $operacao["parceiro"].$notafiscal["codparceiro"];
				/*
				  if(strlen($notafiscal["codtransp"]) > 0){
				  $arr_codparceiro[] = "T".$notafiscal["codtransp"];
				  }
				 */
			}
		}
		foreach($this->arr_notacomplemento as $notacomplemento){
			$notafiscal = $notacomplemento["notafiscal"];
			$operacao = $this->arr_operacaonota[$notafiscal["operacao"]];
			$arr_codparceiro[] = $operacao["parceiro"].$notafiscal["codparceiro"];
		}
		foreach($this->arr_notafrete as $notafrete){
			$arr_codparceiro[] = "T".$notafrete["codtransp"];
		}
		foreach($this->arr_notadiversa as $notadiversa){
			$arr_codparceiro[] = $notadiversa["tipoparceiro"].$notadiversa["codparceiro"];
		}

		foreach($this->arr_notafiscalservico as $notafiscalservico){
			//$arr_codparceiro[] = $notafiscalservico["tipoparceiro"].$notafiscalservico["codparceiro"];
			if($notafiscalservico["codestabelec"] == $estabelecimento->getcodestabelec()){
				$operacao = $this->arr_operacaonota[$notafiscalservico["operacao"]];
				$arr_codparceiro[] = $operacao["parceiro"].$notafiscalservico["codparceiro"];
			}
		}

		$arr_codparceiro = array_unique($arr_codparceiro);
		foreach($arr_codparceiro as $codparceiro){
			switch(substr($codparceiro, 0, 1)){ // Verifica qual o parceiro da nota
				case "C": // Busca dados do cliente
					$cliente = $this->arr_cliente[substr($codparceiro, 1)];
					$cidade = $this->arr_cidade[$cliente["codcidaderes"]];
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
					$estabelecimento_parc = $this->arr_estabelecimento_parc[substr($codparceiro, 1)];
					$cidade = $this->arr_cidade[$estabelecimento_parc["codcidade"]];
					$codpais = "1058"; // Brasil
					$nome = $estabelecimento_parc["nome"];
					$endereco = $estabelecimento_parc["endereco"];
					$numero = $estabelecimento_parc["numero"];
					$complemento = $estabelecimento_parc["complemento"];
					$bairro = $estabelecimento_parc["bairro"];
					$cpf = "";
					$cnpj = $estabelecimento_parc["cpfcnpj"];
					$ie = $estabelecimento_parc["rgie"];
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
					$cidade = $this->arr_cidade[$fornecedor["codcidade"]];
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

	// Identificacao das unidades de medida [nivel 3]
	protected function registro_0190($estabelecimento){
		// Identifica quais sao os produtos que estao relacionados ao estabelecimento
		$arr_codproduto = array();
		foreach($this->arr_itcupom as $arr_itcupom){
			foreach($arr_itcupom as $itcupom){
				if($itcupom["codestabelec"] == $estabelecimento->getcodestabelec()){
					$arr_codproduto[] = $itcupom["codproduto"];
				}
			}
		}
		foreach($this->arr_notafiscal as $notafiscal){
			if($notafiscal["codestabelec"] == $estabelecimento->getcodestabelec()){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$arr_codproduto[] = $itnotafiscal["codproduto"];
				}
			}
		}

		foreach($this->arr_notafiscalservico as $notafiscalservico){
			if($notafiscalservico["codestabelec"] == $estabelecimento->getcodestabelec()){
				foreach($notafiscalservico["itnotafiscal"] as $itnotafiscalservico){
					$arr_codproduto[] = $itnotafiscalservico["codproduto"];
				}
			}
		}

		$arr_codproduto = array_unique($arr_codproduto);

		// Busca as unidades usadas nas notas fiscais
		$arr_codunidade = array();
		foreach($this->arr_produto as $produto){
			if(in_array($produto["codproduto"], $arr_codproduto)){
				$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
				$arr_codunidade[] = $embalagem["codunidade"];
			}
		}
		foreach($this->arr_notafiscal as $notafiscal){
			if($notafiscal["codestabelec"] == $estabelecimento->getcodestabelec()){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$arr_codunidade[] = $itnotafiscal["codunidade"];
				}
			}
		}
		$arr_codunidade = array_unique($arr_codunidade);
		foreach($arr_codunidade as $codunidade){
			$unidade = $this->arr_unidade[$codunidade];
			if(is_null($this->codunidade_servico)){
				$this->codunidade_servico = $unidade["codunidade"];
			}
			$registro = array(
				// Texto fixo contento "0190"
				"REG" => "0190",
				// Codigo da unidade de medida
				"UNID" => $unidade["codunidade"],
				// Descricao da unidade de medida
				"DESCR" => $unidade["descricao"]
			);
			$this->escrever_registro($registro);
		}
	}

	// Tabela de identificacao do item (produto e servicos) [nivel 3]
	protected function registro_0200($estabelecimento){
		$registros = array();

		// Identifica quais sao os produtos que estao relacionados ao estabelecimento
		$arr_codproduto = array();
		foreach($this->arr_maparesumo as $maparesumo){
			if($maparesumo["codestabelec"] == $estabelecimento->getcodestabelec()){
				foreach($this->arr_itcupom[$maparesumo["codmaparesumo"]] as $itcupom){
					$arr_codproduto[] = $itcupom["codproduto"];
				}
			}
		}
		if(is_array($this->arr_itcupom["SAT"])){
			foreach($this->arr_itcupom["SAT"] as $itcupom){
				if($itcupom["codestabelec"] == $estabelecimento->getcodestabelec()){
					$arr_codproduto[] = $itcupom["codproduto"];
				}
			}
		}
		foreach($this->arr_notafiscal as $notafiscal){
			if($notafiscal["codestabelec"] == $estabelecimento->getcodestabelec()){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$arr_codproduto[] = $itnotafiscal["codproduto"];
				}
			}
		}

		$arr_codprodutoservico = array();
		foreach($this->arr_notafiscalservico as $notafiscalservico){
			if($notafiscalservico["codestabelec"] == $estabelecimento->getcodestabelec()){
				foreach($notafiscalservico["itnotafiscal"] as $itnotafiscalservico){
					$arr_codprodutoservico[] = $itnotafiscalservico["codproduto"];
				}
			}
		}

		$arr_codproduto = array_unique($arr_codproduto);
		$arr_produtoservico = array_unique_multi($arr_produtoservico);
		foreach($this->arr_produto as $produto){
			if(in_array($produto["codproduto"], $arr_codproduto) || in_array($produto["codproduto"], $arr_codprodutoservico)){
				$embalagem = $this->arr_embalagem[$produto["codembalvda"]];
				$classfiscal = $this->arr_classfiscal[$produto["codcfnfe"]];
				if(strlen($produto["idncm"]) > 0){
					$codigoncm = $this->arr_ncm[$produto["idncm"]]["codigoncm"];
				}else{
					$codigoncm = NULL;
				}
				$codean = NULL;
				$arr_produtoean = $this->arr_produtoean[$produto["codproduto"]];
				if(is_array($arr_produtoean)){
					foreach($arr_produtoean as $produtoean){
						if(ltrim($produtoean["codean"], "0") > 7){
							$codean = $produtoean["codean"];
							break;
						}
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
					"UNID_INV" => $embalagem["codunidade"],
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
				$this->escrever_registro($registro);
				$this->registro_0205();
				$this->registro_0206();
				$this->registro_0208();
			}
		}
		if(count($arr_produtoservico) > 0){
			foreach($arr_produtoservico as $produtoservico){
				$classfiscal = $this->arr_classfiscal[$produtoservico["codcfnfe"]];
				if(strlen($produto["idncm"]) > 0){
					$codigoncm = $this->arr_ncm[$produtoservico["idncm"]]["codigoncm"];
				}else{
					$codigoncm = NULL;
				}
				$codean = NULL;
				$arr_produtoean = $this->arr_produtoean[$produtoservico["codproduto"]];
				if(is_array($arr_produtoean)){
					foreach($arr_produtoean as $produtoean){
						if(ltrim($produtoean["codean"], "0") > 7){
							$codean = $produtoean["codean"];
							break;
						}
					}
				}

				$registro = array(
					// Texto fixo contendo "0200"
					"REG" => "0200",
					// Codigo do item
					"COD_ITEM" => "S".$produtoservico["codproduto"],
					// Descricao do item
					"DESCR_ITEM" => $produtoservico["descricao"],
					// Representacao alfanumerico do codigo de barra do produto, se houve
					"COD_BARRA" => $codean,
					//Codigo anterior do item com relacao a ultima informacao apresentada
					"COD_ANT_ITEM" => "",
					// Unidade de medida utilizada na quantificacao de estoques
					"UNID_INV" => $this->codunidade_servico,
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
				$this->escrever_registro($registro);
				$this->registro_0205();
				$this->registro_0206();
				$this->registro_0208();
			}
		}
	}

	// Alteracao do item [nivel 4] [NAO GERAR]
	protected function registro_0205(){
		return array();
	}

	// Codigo de produto conforme a tabela publicada pela ANP (combustivel) [nivel 4] [NAO GERAR]
	protected function registro_0206(){
		return array();
	}

	// Codigo de grupos por marca comercial - refri (bebidas frias) [nivel 4] [NAO GERAR]
	protected function registro_0208(){
		return array();
	}

	// Tabela de natureza de operacao/prestacao [nivel 3] [NAO GERAR]
	protected function registro_0400(){
		return array();
	}

	// Tabela de informacao complementar do documento fiscal [nivel 3] [NAO GERAR]
	protected function registro_0450(){
		return array();
	}

	// Plano de contas contabeis [nivel 2] [NAO GERAR]
	protected function registro_0500(){
		$arr_planocontas = array();
		foreach($this->arr_notafiscal AS $notafiscal){
			foreach($notafiscal["itnotafiscal"] AS $itnotafiscal){
				$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
				$arr_planocontas[$natoperacao["codconta"]] = $this->arr_planocontas[$natoperacao["codconta"]];
			}
		}
		foreach($this->arr_notafrete AS $notafiscal){
			$natoperacao = $this->arr_natoperacao[$notafiscal["natoperacao"]];
			$arr_planocontas[$natoperacao["codconta"]] = $this->arr_planocontas[$natoperacao["codconta"]];
		}
		foreach($this->arr_notadiversa AS $notafiscal){
			$natoperacao = $this->arr_natoperacao[$notafiscal["natoperacao"]];
			$arr_planocontas[$natoperacao["codconta"]] = $this->arr_planocontas[$natoperacao["codconta"]];
		}
		$natoperacao = $this->arr_natoperacao["5.102"];
		$arr_planocontas[$natoperacao["codconta"]] = $this->arr_planocontas[$natoperacao["codconta"]];
		$natoperacao = $this->arr_natoperacao["5.405"];
		$arr_planocontas[$natoperacao["codconta"]] = $this->arr_planocontas[$natoperacao["codconta"]];
		foreach($this->arr_estabelecimento AS $estabelecimento){
			$natoperacao = $this->arr_natoperacao[$estabelecimento->getnatoperacaonfcupom()];
			$arr_planocontas[$natoperacao["codconta"]] = $this->arr_planocontas[$natoperacao["codconta"]];
		}
		$this->arr_planocontas = $arr_planocontas;
//		var_dump($this->arr_planocontas);
		foreach($arr_planocontas AS $planocontas){
			if(strlen(trim($planocontas["contacontabil"])) == 0){
				continue;
			}
			$registro = array(
				// Texto fixo contento "0500"
				"REG" => "0500",
				// DT_ALT Data da inclusão/alteração
				"DT_ALT" => convert_date($planocontas["datainclusao"], "Y-m-d", "dmY"),
				// Código da natureza da conta/grupo de contas:
				//		01 - Contas de ativo
				//		02 - Contas de passivo;
				//		03 - Patrimônio líquido;
				//		04 - Contas de resultado;
				//		05 - Contas de compensação;
				//		09 - Outras
				"COD_NAT_CC" => "04",
				//			Indicador do tipo de conta:
				//					S - Sintética (grupo de contas);
				//					A - Analítica (conta).
				"IND_CTA" => "S",
				// Nível da conta analítica/grupo de contas.
				"NIVEL" => "1",
				// Código da conta analítica/grupo de contas.
				"COD_CTA" => $planocontas["contacontabil"],
				// Nome da conta analítica/grupo de contas.
				"NOME_CTA" => $planocontas["nome"],
				// Código da conta correlacionada no Plano de Contas Referenciado, publicado pela RFB.
				"COD_CTA_REF" => "",
				// CNPJ do estabelecimento, no caso da conta informada no campo COD_CTA ser específica de um estabelecimento.
				"CNPJ_EST" => ""
			);
			$this->escrever_registro($registro);
		}
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

	// Abertura do bloco A [nivel 1]
	protected function registro_A001(){
		$registro = array(
			// Texto fixo contendo "A001"
			"REG" => "A001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => (count($this->arr_notafiscalservico) > 0 ? "0" : "1")
		);
		$this->escrever_registro($registro);
		if(count($this->arr_notafiscalservico) > 0){
			$this->registro_A010();
		}
	}

	//Identificação do estabelecimento [nivel 2]
	protected function registro_A010(){
		foreach($this->arr_estabelecimento as $estabelecimento){
			$registro = array(
				"REG" => "A010",
				// Numero de inscricao do estabelecimento no CNPJ
				"CNPJ" => removeformat($estabelecimento->getcpfcnpj())
			);
			$this->escrever_registro($registro);
			$this->registro_A100($estabelecimento);
		}
	}

	//Documento - nota fiscal serviço [nivel 3]
	protected function registro_A100($estabelecimento){
		foreach($this->arr_notafiscalservico as $notafiscalservico){
			if($notafiscalservico["codestabelec"] != $estabelecimento->getcodestabelecfiscal()){
				continue;
			}

			$arr_lancamento = $this->arr_lancamento[$notafiscalservico["idnotafiscal"]];
			if(sizeof($arr_lancamento) > 0){
				if(sizeof($arr_lancamento) == 1 && $arr_lancamento[0]["dtemissao"] == $arr_lancamento[0]["dtvencto"]){
					$ind_pgto = "0";
				}else{
					$ind_pgto = "1";
				}
			}else{
				$ind_pgto = "9";
			}

			$registro = array(
				"REG" => "A100",
				//indicador do tipo de operação
				//0 - serviço contratado pelo stabelecimento
				//1 - serviço prestado pelo estabelecimento
				"IND_OPER" => ($notafiscalservico["operacao"] == "SS" ? "1" : "0"),
				//indicador do emitente do documento fiscal
				//0 - emissão própria
				//1 - emissão de terceiros
				"IND_EMIT" => ($notafiscalservico["operacao"] == "SS" ? "0" : "1"),
				//codigo do particpante (campo 02 do registro 150)
				//do emitente do documento, no caso de emissão de terceiros;
				//do adquirente, no caso de serviços prestados
				"COD_PART" => $notafiscalservico["tipoparceiro"].$notafiscalservico["codparceiro"],
				//cdigo da sistuação do ocumento fiscal
				//00 - documento regular
				//02 - doucmento cancelado
				"COD_SIT" => ($notafiscalservico["status"] == "A" ? "00" : "02"),
				//Serie do documento fiscal
				"SER" => $notafiscalservico["serie"],
				//subserie do documento fiscal
				"SUB" => "",
				//numero do documento fiscal ou documento internacional equivalente
				"NUM_DOC" => $notafiscalservico["numnotafis"],
				//chave/codigo de verificação da notafiscal eletronica de serviços
				"CHV_NFSE" => "",
				//data da emissaõ do documento fiscal
				"DT_DOC" => $this->valor_data($notafiscalservico["dtemissao"]),
				//data de excução/conclusão do serviço
				"DT_EXE_SERV" => $this->valor_data($notafiscalservico["dtentrega"]),
				//valor total do documento
				"VL_DOC" => $this->valor_decimal($notafiscalservico["totalliquido"], 2),
				//indicador do tipo de pahamento
				//0 - A vista
				//1 - A prazao
				//9 - Sem pagamento
				"IND_PGTO" => $ind_pgto,
				//valor total do desconto
				"VL_DESC" => $this->valor_decimal($notafiscalservico["totaldesconto"], 2),
				//base de calculo do PIS/PASEP
				"VL_BC_PIS" => $this->valor_decimal($notafiscalservico["totalbasepis"], 2),
				//valor do PIS/PASEP
				"VL_PIS" => $this->valor_decimal($notafiscalservico["totalpis"], 2),
				//base de calculo do COFINS
				"VL_BC_COFINS" => $this->valor_decimal($notafiscalservico["totalbasecofins"], 2),
				//valor do COFINS
				"VL_COFINS" => $this->valor_decimal($notafiscalservico["totalcofins"], 2),
				//valor do PIS retido na fonte
				"VL_PIS_RET" => $this->valor_decimal(0, 2),
				//valor do COFINS retido na fonte
				"VL_COFINS_RET" => $this->valor_decimal(0, 2),
				//valor do ISS
				"VL_ISS" => $this->valor_decimal($notafiscalservico["valoricms"], 2)
			);
			$this->escrever_registro($registro);
			$this->registro_A170($notafiscalservico);
		}
	}

	//Complemento do documento - itens do documento [nivel 4]
	protected function registro_A170($notafiscalservico){
		//foreach($notafiscalservico["itnotafiscalservico"] as $itnotafiscalservico){
		foreach($notafiscalservico["itnotafiscal"] as $itnotafiscalservico){
			$cstpiscofins = $this->cstpiscofins($itnotafiscalservico);
			$registro = array(
				"REG" => "A170",
				//numeroi sequencial do item no documento fiscal
				"NUM_ITEM" => $itnotafiscalservico["seqitem"],
				//codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $itnotafiscalservico["codproduto"],
				//descrição complementar do item conforme adotado no doucmento fiscal
				"DESCR_COMPL" => "",
				//valor total do item (mercadoria ou serviço)
				"VL_ITEM" => $this->valor_decimal(number_format($itnotafiscalservico["totalbruto"], 2, ".", ""), 2),
				//valor total do desconto / EXCLUSAO
				"VL_DESC" => $this->valor_decimal(number_format($itnotafiscalservico["valordesconto"], 2, ".", ""), 2),
				//codigo da base de calculo do credito, conforme a tabela indicada no item 4.3.7
				//caso seja informado codigo representativo de credito no campo 09 (CST PIS) ou no
				//campo 13 (CST COFINS)
				"NAT_BC_CRED" => $itnotafiscalservico["natbccredito"],
				//indicador da origem do credito
				//0 - Operacao no mercado interno
				//1 - Operação de importação
				"IND_ORIG_CRED" => "0",
				//codigo de situação tributaria referente ao PIS/PASEP conforme tabela 4.3.3
				"CST_PIS" => $cstpiscofins,
				//valor daq base de calculo PIS/PASEP
				"VL_BC_PIS" => $this->valor_decimal($itnotafiscalservico["totalliquido"], 2),
				//aliquota do PIS/PASEP
				"ALIQ_PIS" => $this->valor_decimal($itnotafiscalservico["aliqpis"], 2),
				//valor do PIS/PASEP
				"VL_PIS" => $this->valor_decimal($itnotafiscalservico["totalpis"], 2),
				//codigo de situação tributaria referente ao COFINS conforme tabela 4.3.3
				"CST_COFINS" => $cstpiscofins,
				//base de caclulo do COFINS
				"VL_BC_COFINS" => $this->valor_decimal($itnotafiscalservico["totalliquido"], 2),
				//aliquota do COFINS
				"ALIQ_COFINS" => $this->valor_decimal($itnotafiscalservico["aliqcofins"], 2),
				//valor do COFINS
				"VL_COFINS" => $this->valor_decimal($itnotafiscalservico["totalcofins"], 2),
				//codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => "",
				//codigo do centro de custos
				"COD_CCUS" => ""
			);
			$this->escrever_registro($registro);
			if($itnotafiscalservico["totalpis"] + $itnotafiscalservico["totalcofins"] == 0){
				$itnotafiscalservico["totalbasepis"] = 0;
				$itnotafiscalservico["totalbasecofins"] = 0;
			}else{
				$itnotafiscalservico["totalbasepis"] = $itnotafiscalservico["totalliquido"];
				$itnotafiscalservico["totalbasecofins"] = $itnotafiscalservico["totalliquido"];
			}
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalbasepis"] += $itnotafiscalservico["totalbasepis"];
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalpis"] += $itnotafiscalservico["totalpis"];
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalbasecofins"] += $itnotafiscalservico["totalbasecofins"];
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalcofins"] += $itnotafiscalservico["totalcofins"];
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalliquido"] += $itnotafiscalservico["totalliquido"];
		}
	}

	// Encerramento do bloco A [nivel 1]
	protected function registro_A990(){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "A"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "A990"
			"REG" => "A990",
			// Quantidade de linhas do bloco A
			"QTD_LIN_A" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco C [nivel 1]
	protected function registro_C001(){
		$registro = array(
			// Texto fixo contento "C001"
			"REG" => "C001",
			// Indicador de movimento:
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "0"
		);
		$this->escrever_registro($registro);
		$this->registro_C010();
	}

	// Identificacao do estabelecimento [nivel 2]
	protected function registro_C010(){
		foreach($this->arr_estabelecimento as $estabelecimento){
			$registro = array(
				// Texto fixo contendo "C010"
				"REG" => "C010",
				// Numero de inscricao do estabelecimento no CNPJ
				"CNPJ" => removeformat($estabelecimento->getcpfcnpj()),
				// Indicador da apuracao das contribuicoes e creditos, na escrituracao das operacoes por NF-e e ECF, no periodo:
				// 1 - Apuracao com base nos registros de consolidacao das operacoes por NF-e (C180 e C190) e por ECF (C490)
				// 2 - Apuracao com base no registro individualizado de NF-e (C100 e C170) e de ECF (C400)
				"IND_ESCRI" => $this->c010_ind_escri
			);
			$this->escrever_registro($registro);

			if($this->c010_ind_escri == "2"){
				$this->registro_C100($estabelecimento);
			}else{
				$this->registro_C180($estabelecimento);
				$this->registro_C190($estabelecimento);
			}
			$this->registro_C380($estabelecimento);
			$this->registro_C395($estabelecimento);
			if($this->c010_ind_escri == "2"){
				$this->registro_C400($estabelecimento);
			}else{
				$this->registro_C490($estabelecimento);
			}
			$this->registro_C500($estabelecimento);
			$this->registro_C600($estabelecimento);
			$this->registro_C860($estabelecimento);
		}
	}

	// Documento - nota fiscal (codigo 01), nota fiscal avulsa (codigo 1B), nota fiscal de produto (codigo 04) e NF-e (codigo 55) [nivel 3]
	protected function registro_C100($estabelecimento){
		// Gera as notas fiscais
		foreach($this->arr_notafiscal as $notafiscal){
			// Verifica se a nota fiscal pertence ao estabelecimento corrente
			if($notafiscal["codestabelec"] != $estabelecimento->getcodestabelecfiscal()){
				continue;
			}
			// Verifica se precisa gerar o registro
			$codmodelo = $this->modelo($notafiscal);
			if(!in_array($codmodelo, array("01", "1B", "04", "55","65"))){
				continue;
			}

			$ind_emit = ($notafiscal["emissaopropria"] == "N" ? "1" : "0");

			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];

			// Verifica qual o indicador de pagamento
			$arr_lancamento = $this->arr_lancamento[$notafiscal["idnotafiscal"]];
			if(sizeof($arr_lancamento) > 0){
				if(sizeof($arr_lancamento) == 1 && $arr_lancamento[0]["dtemissao"] == $arr_lancamento[0]["dtvencto"]){
					$ind_pgto = "0";
				}else{
					$ind_pgto = "1";
				}
			}else{
				// Futuramente deixar apenas a atribuicao do valor 2
				if(compare_date($notafiscal["dtentrega"], "2012-07-01", "Y-m-d", "<")){
					$ind_pgto = "9";
				}else{
					$ind_pgto = "2";
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

			// Verifica se os itens da nota devem acrescentar no valor do icms
			$vl_bc_icms = 0;
			$vl_icms = 0;
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
				$csticms = substr($itnotafiscal["csticms"], 1, 2);
				if(in_array($csticms, array("00", "20")) || $natoperacao["aprovicmsprop"] == "S"){
					$vl_bc_icms += $itnotafiscal["totalbaseicms"];
					$vl_icms = $vl_icms + $itnotafiscal["totalicms"];
				}
			}

			if(in_array($notafiscal["status"], array("C", "I", "D")) || $codmodelo == "65"){
				$cod_part = null;
			}else{
				$cod_part = $this->codparceiro($notafiscal);
			}

			if(in_array($notafiscal["status"], array("C", "I", "D")) || in_array($codmodelo, array("65"))){
				$vl_bc_icms_st = null;
				$vl_icms_st = null;
			}elseif(in_array($notafiscal["operacao"], array("CP", "DF"))){
				$vl_bc_icms_st = $this->valor_decimal(0, 2);
				$vl_icms_st = $this->valor_decimal(0, 2);
			}else{
				$vl_bc_icms_st = $this->valor_decimal($notafiscal["totalbaseicmssubst"], 2);
				$vl_icms_st = $this->valor_decimal($notafiscal["totalicmssubst"], 2);
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
				"COD_PART" => $cod_part,
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => $this->modelo($notafiscal),
				// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
				"COD_SIT" => $cod_sit,
				// Serie do documento fiscal
				"SER" => $notafiscal["serie"],
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
				// 2 - Sem pagamento
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
				"VL_OUT_DA" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : "0,00"),
				// Valor da base de calculo do ICMS
				"VL_BC_ICMS" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($vl_bc_icms, 2)),
				// Valor do ICMS
				"VL_ICMS" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($vl_icms, 2)),
				// Valor da base de calculo do ICMS substituicao tributaria
				"VL_BC_ICMS_ST" => $vl_bc_icms_st,
				// Valor do ICMS retido por substituicao tributaria
				"VL_ICMS_ST" => $vl_icms_st,
				// Valor total do IPI
				"VL_IPI" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totalipi"], 2)),
				// Valor total do PIS
				"VL_PIS" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totalpis"], 2)),
				// Valor total do COFINS
				"VL_COFINS" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : $this->valor_decimal($notafiscal["totalcofins"], 2)),
				// Valor total do PIS retido por substituicao tributaria
				"VL_PIS_ST" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : "0,00"),
				// Valor total do COFINS retido por substituicao tributaria
				"VL_COFINS_ST" => (in_array($notafiscal["status"], array("C", "I", "D")) ? NULL : "0,00")
			);
			$this->escrever_registro($registro);
			if($codmodelo == "65"){
				$this->registro_C175($notafiscal);
			}else{
				$this->registro_C110();
				$this->registro_C111();
				$this->registro_C120();
				$this->registro_C170($notafiscal);
			}
		}
/*
		// Gera as notas de complemento
		foreach($this->arr_notacomplemento as $notacomplemento){
			$notafiscal = $notacomplemento["notafiscal"];

			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			// Verifica qual o indicador de pagamento
			$arr_lancamento = $this->arr_lancamento[$notafiscal["idnotafiscal"]];
			if(sizeof($arr_lancamento) > 0){
				if(sizeof($arr_lancamento) == 1 && $arr_lancamento[0]["dtemissao"] == $arr_lancamento[0]["dtvencto"]){
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
				"IND_EMIT" => (in_array($operacaonota["operacao"], array("CP", "IM", "TE")) ? "1" : "0"),
				// Codigo do participante (campo 02 do registro 0150)
				// Do emitente do documento ou do remetente das mercadorias, no caso de entradas
				// Do adquirente, no caso de saidas
				"COD_PART" => $this->codparceiro($notafiscal),
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
				"COD_MOD" => "55",
				// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
				"COD_SIT" => "06",
				// Serie do documento fiscal
				"SER" => $notacomplemento["serie"],
				// Numero do documento fiscal
				"NUM_DOC" => $notacomplemento["numnotafis"],
				// Chave da nota fiscal eletronica
				"CHV_NFE" => $notacomplemento["chavenfe"],
				// Data da emissao do documento fiscal
				"DT_DOC" => $this->valor_data($notacomplemento["dtemissao"]),
				// Data da entrada ou saida
				"DT_E_S" => $this->valor_data($notacomplemento["dtemissao"]),
				// Valor total do documento fiscal
				"VL_DOC" => $this->valor_decimal($notacomplemento["totalliquido"], 2),
				// Indicador do tipo de pagamento:
				// 0 - A vista
				// 1 - A prazo
				// 2 - Sem pagamento
				"IND_PGTO" => $ind_pgto,
				// Valor total do desconto
				"VL_DESC" => $this->valor_decimal(0, 2),
				// Abatimento nao tributado e nao comercial
				"VL_ABAT_NT" => "0,00",
				// Valor total das mercadorias e servicos
				"VL_MERC" => $this->valor_decimal(0, 2),
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
				"VL_OUT_DA" => $this->valor_decimal(0, 2),
				// Valor da base de calculo do ICMS
				"VL_BC_ICMS" => $this->valor_decimal($notacomplemento["totalbaseicms"], 2),
				// Valor do ICMS
				"VL_ICMS" => $this->valor_decimal($notacomplemento["totalicms"], 2),
				// Valor da base de calculo do ICMS substituicao tributaria
				"VL_BC_ICMS_ST" => $this->valor_decimal($notacomplemento["totalbaseicmssubst"], 2),
				// Valor do ICMS retido por substituicao tributaria
				"VL_ICMS_ST" => $this->valor_decimal($notacomplemento["totalicmssubst"], 2),
				// Valor total do IPI
				"VL_IPI" => $this->valor_decimal($notacomplemento["totalipi"], 2),
				// Valor total do PIS
				"VL_PIS" => $this->valor_decimal(0, 2),
				// Valor total do COFINS
				"VL_COFINS" => $this->valor_decimal(0, 2),
				// Valor total do PIS retido por substituicao tributaria
				"VL_PIS_ST" => $this->valor_decimal(0, 2),
				// Valor total do COFINS retido por substituicao tributaria
				"VL_COFINS_ST" => $this->valor_decimal(0, 2)
			);
			$this->escrever_registro($registro);
		}
*/
	}

	// Informacao complementar da nota fiscal (codigo 01, 1B, 04, 55) [nivel 4] [NAO GERAR]
	protected function registro_C110(){

	}

	// Processo referenciado [nivel 4] [NAO GERAR]
	protected function registro_C111(){

	}

	// Operacoes de importacao (codigo 01) [nivel 4] [NAO GERAR]
	protected function registro_C120(){

	}

	// Itens do documento(codigo 01, 1B, 04 e 55) [nivel 4]
	protected function registro_C170($notafiscal){
		// Verifica se precisa gerar o registro
		if(in_array($this->modelo($notafiscal), array("01", "1B", "04", "55"))){
			$operacao = $this->arr_operacaonota[$notafiscal["operacao"]];
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$produto = $this->arr_produto[$itnotafiscal["codproduto"]];

				$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
				$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];

				if($operacao["tipo"] == "E"){
					$classfiscal = $this->arr_classfiscal[$produto["codcfnfe"]];
				}else{
					$classfiscal = $this->arr_classfiscal[$produto["codcfnfs"]];
				}
				$cstpiscofins = $this->cstpiscofins($itnotafiscal);
				$ipi = $this->arr_ipi[$produto["codipi"]];
				$baseipi = $itnotafiscal["totalbruto"] - $itnotafiscal["totaldesconto"] + $itnotafiscal["totalacrescimo"];
				if($itnotafiscal["aliqpis"]){
					//$basepiscofins = $itnotafiscal["totalbruto"] - $itnotafiscal["totaldesconto"] + $itnotafiscal["totalacrescimo"];
					$basepiscofins = $itnotafiscal["totalbasepis"];
				}else{
					$basepiscofins = 0;
				}
				if($notafiscal["emissaopropria"] == "N" && in_array($itnotafiscal["csticms"], array("010", "070"))){
					$csticms = "060";
				}else{
					$csticms = $itnotafiscal["csticms"];
				}
				if(in_array(substr($csticms, -2), array("00", "20")) || $natoperacao["aprovicmsprop"] == "S"){
					$vl_bc_icms = $this->valor_decimal($itnotafiscal["totalbaseicms"], 2);
					$vl_icms = $this->valor_decimal($itnotafiscal["totalicms"], 2);
					$aliq_icms = $this->valor_decimal($itnotafiscal["aliqicms"], 2);
				}else{
					$vl_bc_icms = 0;
					$vl_icms = 0;
					$aliq_icms = 0;
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
					"UNID" => $itnotafiscal["codunidade"],
					// Valor total dos item (mercadorias ou servicos)
					"VL_ITEM" => $this->valor_decimal(number_format($itnotafiscal["totalbruto"], 2, ".", ""), 2),
					// Valor do desconto comercial
					"VL_DESC" => $this->valor_decimal($itnotafiscal["totaldesconto"], 2),
					// Movimentacao fisica do item:
					// 0 - Sim
					// 1 - Nao
					"IND_MOV" => "0",
					// Codigo da situacao tributaria referente ao ICMS, conforme a tabela indicada no item 4.3.1
					"CST_ICMS" => $csticms,
					// Codigo fiscal de operacao e prestacao
					"CFOP" => $this->valor_natoperacao($itnotafiscal["natoperacao"]),
					// Codigo da natureza de operacao (campo 02 do registro 0400),
					"COD_NAT" => "",
					// Valor da base de calculo do ICMS
					"VL_BC_ICMS" => $this->valor_decimal($vl_bc_icms, 2),
					// Aliquota de ICMS
					"ALIQ_ICMS" => $this->valor_decimal($aliq_icms, 2),
					// Valor do ICMS creditado/debitado
					"VL_ICMS" => $this->valor_decimal($vl_icms, 2),
					// Valor da base de calculo referente a substituicao tributaria
					"VL_BC_ICMS_ST" => $this->valor_decimal((in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalbaseicmssubst"]), 2),
					// Aliquota do ICMS da substituicao tributaria na unidade da federacao de destino
					"ALIQ_ST" => $this->valor_decimal((!in_array($notafiscal["operacao"], array("CP", "DF")) && $itnotafiscal["tptribicms"] == "F" ? $itnotafiscal["aliqicms"] : 0), 2),
					// Valor do ICMS referente a substituicao tributaria
					"VL_ICMS_ST" => $this->valor_decimal((in_array($notafiscal["operacao"], array("CP", "DF")) ? 0 : $itnotafiscal["totalicmssubst"]), 2),
					// Indicador do periodo de apuracao do IPI
					// 0 - Mensal
					// 1 - Decendial
					"IND_APUR" => "0",
					// Codigo da situtacao tributaria referente ao IPI, conforme a tebela indicada no item 4.3.2
					"CST_IPI" => $ipi["codcst"],
					// Codigo de enquadramento legal do IPI, conforme a tabela indicada no item 4.5.3
					"COD_ENQ" => "",
					// Valor da base de calculo do IPI
					"VL_BC_IPI" => $this->valor_decimal($baseipi, 2),
					// Aliquota de IPI
					"ALIQ_IPI" => $this->valor_decimal($itnotafiscal["percipi"], 2),
					// Valor do IPI creditado/debitado
					"VL_IPI" => $this->valor_decimal($itnotafiscal["totalipi"], 2),
					// Codigo da situacao tributaria referente ao PIS
					"CST_PIS" => $cstpiscofins,
					// Valor da base de calculo do PIS
					"VL_BC_PIS" => $this->valor_decimal($basepiscofins, 2),
					// Aliquota do PIS (em percentual)
					"ALIQ_PIS_P" => $this->valor_decimal($itnotafiscal["aliqpis"], 4),
					// Quantidade - base de calculo PIS
					"QUANT_BC_PIS" => "",
					// Aliquota do PIS (em reais)
					"ALIQ_PIS_F" => "",
					// Valor do PIS
					"VL_PIS" => $this->valor_decimal($itnotafiscal["totalpis"], 2),
					// Codigo da situacao tributaria referente ao COFINS
					"CST_COFINS" => $cstpiscofins,
					// Valor da base de calculo do COFINS
					"VL_BC_COFINS" => $this->valor_decimal($basepiscofins, 2),
					// Aliquota do COFINS (em percentual)
					"ALIQ_COFINS_P" => $this->valor_decimal($itnotafiscal["aliqcofins"], 4),
					// Quantidade - base de calculo COFINS
					"QUANT_BC_COFINS" => "",
					// Aliquota do COFINS (em reais)
					"ALIQ_COFINS_F" => "",
					// Valor do COFINS
					"VL_COFINS" => $this->valor_decimal($itnotafiscal["totalcofins"], 2),
					// Codigo da conta conta analitica contabil debitada/creditada
					"COD_CTA" => $planocontas["contacontabil"]
				);
				$this->escrever_registro($registro);

				$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalbruto"] += $itnotafiscal["totalbruto"];
				$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalbasepis"] += $itnotafiscal["totalbasepis"];
				$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalpis"] += $itnotafiscal["totalpis"];
				$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
				$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalcofins"] += $itnotafiscal["totalcofins"];
				$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalliquido"] += $itnotafiscal["totalliquido"];
			}
		}
	}

	protected function registro_C175($notafiscal){
		$totalpiscofins = array();
		foreach($notafiscal["itnotafiscal"] AS $itnotafiscal){
			$cst = $this->cstpiscofins($itnotafiscal);
			$chave = $cst.$itnotafiscal["aliqpis"].$itnotafiscal["aliqcofins"].$itnotafiscal["natoperacao"];
			$totalpiscofins[$chave]["aliqpis"] = $itnotafiscal["aliqpis"];
			$totalpiscofins[$chave]["aliqcofins"] = $itnotafiscal["aliqcofins"];
			$totalpiscofins[$chave]["totalpis"] += $itnotafiscal["totalpis"];
			$totalpiscofins[$chave]["totalcofins"] += $itnotafiscal["totalcofins"];
			$totalpiscofins[$chave]["totalbasepis"] += $itnotafiscal["totalbasepis"];
			$totalpiscofins[$chave]["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
			$totalpiscofins[$chave]["totaldesconto"] += $itnotafiscal["totaldesconto"];
			$totalpiscofins[$chave]["totalbruto"] += $itnotafiscal["totalbruto"];
			$totalpiscofins[$chave]["cst"] = $cst;
			$totalpiscofins[$chave]["natoperacao"] = $itnotafiscal["natoperacao"];
		}

		foreach($totalpiscofins as $sinteticopiscofins){
			$natoperacao = $this->arr_natoperacao[$sinteticopiscofins["natoperacao"]];
			$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];

			$registro = array(
				"REG" => "C175",
				"CFOP" => $this->valor_natoperacao($sinteticopiscofins["natoperacao"]),
				"VL_OPR" => $this->valor_decimal($sinteticopiscofins["totalbruto"],2),
				"VL_DESC" => $this->valor_decimal($sinteticopiscofins["totaldesconto"],2),
				"CST_PIS" => $sinteticopiscofins["cst"],
				"VL_BC_PIS" => $this->valor_decimal($sinteticopiscofins["totalbasepis"],2),
				"ALIQ_PIS" => $this->valor_decimal($sinteticopiscofins["aliqpis"],2),
				"QUANT_BC_PIS" => "",
				"ALIQ_PIS_QUANT" => "",
				"VL_PIS" => $this->valor_decimal($sinteticopiscofins["totalpis"],2),
				"CST_COFINS" => $sinteticopiscofins["cst"],
				"VL_BC_COFINS" => $this->valor_decimal($sinteticopiscofins["totalbasecofins"],2),
				"ALIQ_COFINS" => $this->valor_decimal($sinteticopiscofins["aliqcofins"],2),
				"QUANT_BC_COFINS" => "",
				"ALIQ_COFINS_QUANT" => "",
				"VL_COFINS" => $this->valor_decimal($sinteticopiscofins["totalcofins"],2),
				"COD_CTA" => $planocontas["contacontabil"],
				"INFO_COMPL" => ""
			);
			$this->escrever_registro($registro);
		}
	}


	// Consolidacao de notas fiscais eletronicas emitidas pela pessoa juridica (codigo 55) - operacoes de venda [nivel 3]
	protected function registro_C180($estabelecimento){
		$arr_item = array();
		$arr_itnotafiscal = array();
		foreach($this->arr_notafiscal as $notafiscal){
			// Verifica se a nota fiscal pertence ao estabelecimento corrente
			if($notafiscal["codestabelec"] != $estabelecimento->getcodestabelec()){
				continue;
			}
			if(in_array($notafiscal["operacao"], array("VD")) && in_array($this->modelo($notafiscal), array("55"))){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$arr_itnotafiscal[] = $itnotafiscal;
					$arr_item[$itnotafiscal["codproduto"]] += $itnotafiscal["totalliquido"];
				}
			}
		}
		foreach($arr_item as $codproduto => $totalliquido){
			$registro = array(
				// Texto fixo contendo "C180"
				"REG" => "C180",
				// Texto fixo contendo "55" (codigo da nota fiscal eletronica, modela 55, conforme a tabela 4.1.1)
				"COD_MOD" => "55",
				// Data de emissao inicial dos documentos
				"DT_DOC_INI" => $this->valor_data($this->datainicial),
				// Data de emissao final dos documentos
				"DT_DOC_FIN" => $this->valor_data($this->datafinal),
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $codproduto,
				// Codigo da nomenclatura comum do Mercosul
				"COD_NCM" => "",
				// Codigo EX, conforme a TIPI
				"EX_IPI" => "",
				// Valor total do item
				"VL_TOT_ITEM" => $this->valor_decimal($totalliquido)
			);
			$this->escrever_registro($registro);
			$this->registro_C181($codproduto, $arr_itnotafiscal);
			$this->registro_C185($codproduto, $arr_itnotafiscal);
			$this->registro_C188();
		}
	}

	// Detalhamento da consolidacao - operacoes de vendas - PIS/PASEP [nivel 4]
	protected function registro_C181($codproduto, $arr_itnotafiscal){
		$arr_detalhe = array();
		$produto = $this->arr_produto[$codproduto];
		foreach($arr_itnotafiscal as $itnotafiscal){
			if($itnotafiscal["codproduto"] == $codproduto){
				$cts = $this->cstpiscofins($itnotafiscal);
				$cfop = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
				$aliq = $this->valor_decimal($itnotafiscal["aliqpis"], 4);
				$detalhe = $arr_detalhe[$cst][$cfop][$aliq];
				$detalhe["totalbruto"] += $itnotafiscal["totalbruto"];
				$detalhe["totaldesconto"] += $itnotafiscal["totaldesconto"];
				$detalhe["totalbasepis"] += $itnotafiscal["totalbasepis"];
				$detalhe["totalpis"] += $itnotafiscal["totalpis"];
				$arr_detalhe[$cst][$cfop][$aliq] = $detalhe;
			}
		}
		foreach($arr_detalhe as $cst => $detalhe_cst){
			foreach($detalhe_cst as $cfop => $detalhe_cfop){
				foreach($detalhe_cfop as $aliq => $detalhe){
					$registro = array(
						// Texto fixo contendo "C181"
						"REG" => "C181",
						// Codigo da situacao tributaria referente ao PIS/PASEP, conforme a tabela indicada no item 4.3.3
						"CST_PIS" => $cst,
						// Codigo fiscal de operacao e prestacao
						"CFOP" => $cfop,
						// Valor do item
						"VL_ITEM" => $this->valor_decimal($detalhe["totalbruto"], 2),
						// Valor do desconto comercial / exclusao
						"VL_DESC" => $this->valor_decimal($detalhe["totaldesconto"], 2),
						// Valor da base de calculo do PIS/PASEP
						"VL_BC_PIS" => $this->valor_decimal($detalhe["totalbasepis"], 2),
						// Aliquota do PIS/PASEP (em percentual)
						"ALIQ_PIS" => $this->valor_decimal($aliq, 4),
						// Quantidade - base de calculo PIS/PASEP
						"QUANT_BC_PIS" => "",
						// Aliquota do PIS/PASEP (em reais)
						"ALIQ_PIS_QUANT" => "",
						// Valor do PIS/PASEP
						"VL_PIS" => $this->valor_decimal($detalhe["totalpis"], 2),
						// Codigo da conta contabil debitada/creditada
						"COD_CTA" => ""
					);
					$this->escrever_registro($registro);
				}
			}
		}
	}

	// Detalhamento da consolidacao - operacoes de vendas - COFINS [nivel 4]
	protected function registro_C185($codproduto, $arr_itnotafiscal){
		$arr_detalhe = array();
		foreach($arr_itnotafiscal as $itnotafiscal){
			if($itnotafiscal["codproduto"] == $codproduto){
				$cst = $this->cstpiscofins($itnotafiscal);
				$cfop = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
				$aliq = $this->valor_decimal($itnotafiscal["aliqcofins"], 4);
				$detalhe = $arr_detalhe[$cst][$cfop][$aliq];
				$detalhe["totalbruto"] += $itnotafiscal["totalbruto"];
				$detalhe["totaldesconto"] += $itnotafiscal["totaldesconto"];
				$detalhe["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
				$detalhe["totalcofins"] += $itnotafiscal["totalcofins"];
				$arr_detalhe[$cst][$cfop][$aliq] = $detalhe;
			}
		}
		foreach($arr_detalhe as $cst => $detalhe_cst){
			foreach($detalhe_cst as $cfop => $detalhe_cfop){
				foreach($detalhe_cfop as $aliq => $detalhe){
					$registro = array(
						// Texto fixo contendo "C185"
						"REG" => "C185",
						// Codigo da situacao tributaria referente a COFINS, conforme a tabela indicada no item 4.3.4
						"CST_COFINS" => $cst,
						// Codigo fiscal de operacao e prestacao
						"CFOP" => $cfop,
						// Valor do item
						"VL_ITEM" => $this->valor_decimal($detalhe["totalbruto"], 2),
						// Valor do desconto comercial / exclusao
						"VL_DESC" => $this->valor_decimal($detalhe["totaldesconto"], 2),
						// Valor da base de calculo do COFINS
						"VL_BC_COFINS" => $this->valor_decimal($detalhe["totalbasecofins"], 2),
						// Aliquota do COFINS (em percentual)
						"ALIQ_COFINS" => $this->valor_decimal($aliq, 4),
						// Quantidade - base de calculo COFINS
						"QUANT_BC_COFINS" => "",
						// Aliquota do COFINS (em reais)
						"ALIQ_COFINS_QUANT" => "",
						// Valor do COFINS
						"VL_COFINS" => $this->valor_decimal($detalhe["totalcofins"], 2),
						// Codigo da conta contabil debitada/creditada
						"COD_CTA" => ""
					);
					$this->escrever_registro($registro);
				}
			}
		}
	}

	// Processo referenciado [nivel 4] [NAO GERAR]
	protected function registro_C188(){

	}

	// Consolidacao de notas fiscais eletronicas (codigo 55) - operacoes de aquisicao com direito a credito, e operacoes de devolucao de compras e vendas [nivel 3]
	protected function registro_C190($estabelecimento){
		$arr_item = array();
		$arr_itnotafiscal = array();
		foreach($this->arr_notafiscal as $notafiscal){
			// Verifica se a nota fiscal pertence ao estabelecimento corrente
			if($notafiscal["codestabelec"] != $estabelecimento->getcodestabelec()){
				continue;
			}
			if(in_array($notafiscal["operacao"], array("DC", "DF")) && in_array($this->modelo($notafiscal), array("55"))){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$arr_itnotafiscal[] = $itnotafiscal;
					$arr_item[$itnotafiscal["codproduto"]] += $itnotafiscal["totalliquido"];
				}
			}
		}
		foreach($arr_item as $codproduto => $totalliquido){
			$registro = array(
				// Texto fixo contendo "C180"
				"REG" => "C180",
				// Texto fixo contendo "55" (codigo da nota fiscal eletronica, modela 55, conforme a tabela 4.1.1)
				"COD_MOD" => "55",
				// Data de emissao inicial dos documentos
				"DT_DOC_INI" => $this->valor_data($this->datainicial),
				// Data de emissao final dos documentos
				"DT_DOC_FIN" => $this->valor_data($this->datafinal),
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $codproduto,
				// Codigo da nomenclatura comum do Mercosul
				"COD_NCM" => "",
				// Codigo EX, conforme a TIPI
				"EX_IPI" => "",
				// Valor total do item
				"VL_TOT_ITEM" => $this->valor_decimal($totalliquido)
			);
			$this->escrever_registro($registro);
			$this->registro_C191($codproduto, $arr_itnotafiscal);
			$this->registro_C195($codproduto, $arr_itnotafiscal);
			$this->registro_C198();
			$this->registro_C199();
		}
	}

	// Detalhamento da consolidacao - operacoes de aquisicao com direito a credito, e operacoes de devolucao de compras e vendas - PIS/PASEP [nivel 4]
	protected function registro_C191($codproduto, $arr_itnotafiscal){
		$arr_detalhe = array();
		foreach($arr_itnotafiscal as $itnotafiscal){
			if($itnotafiscal["codproduto"] == $codproduto){
				$notafiscal = $this->arr_notafiscal[$itnotafiscal["idnotafiscal"]];
				$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
				switch($operacaonota["parceiro"]){
					case "C":
						$cliente = $this->arr_cliente[$notafiscal["codparceiro"]];
						$cpfcnpj = $cliente["cpfcnpj"];
						break;
					case "E":
						$estabelecimento = $this->arr_estabelecimento_parc[$notafiscal["codparceiro"]];
						$cpfcnpj = $estabelecimento["cpfcnpj"];
						break;
					case "F":
						$fornecedor = $this->arr_fornecedor[$notafiscal["codparceiro"]];
						$cpfcnpj = $fornecedor["cpfcnpj"];
						break;
				}
				$cpfcnpj = removeformat($cpfcnpj);
				$cst = $this->cstpiscofins($itnotafiscal);
				$cfop = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
				$aliq = $this->valor_decimal($itnotafiscal["aliqpis"], 4);
				$detalhe = $arr_detalhe[$cpfcnpj][$cst][$cfop][$aliq];
				$detalhe["totalbruto"] += $itnotafiscal["totalbruto"];

				$detalhe["totaldesconto"] += $itnotafiscal["totaldesconto"];
				$detalhe["totalbasepis"] += $itnotafiscal["totalbasepis"];
				$detalhe["totalpis"] += $itnotafiscal["totalpis"];
				$arr_detalhe[$cpfcnpj][$cst][$cfop][$aliq] = $detalhe;
			}
		}
		foreach($arr_detalhe as $cpfcnpj => $detalhe_cpfcnpj){
			foreach($detalhe_cpfcnpj as $cst => $detalhe_cst){
				foreach($detalhe_cst as $cfop => $detalhe_cfop){
					foreach($detalhe_cfop as $aliq => $detalhe){
						$registro = array(
							// Texto fixo contendo "C195"
							"REG" => "C195",
							// CNPJ/CPF do participante a que se referem as operacoes consolidadas neste registro (pessoa juridica ou pessoa fisica vendedora/remetente)
							"CNPJ_CPF_PART" => $cpfcnpj,
							// Codigo da situacao tributaria referente a PIS/PASEP
							"CST_PIS" => $cst,
							// Codigo fiscal de operacao e prestacao
							"CFOP" => $cfop,
							// Valor do item
							"VL_ITEM" => $this->valor_decimal($detalhe["totalbruto"], 2),
							// Valor do desconto comercial / exclusao
							"VL_DESC" => $this->valor_decimal($detalhe["totaldesconto"], 2),
							// Valor da base de calculo do PIS/PASEP
							"VL_BC_PIS" => $this->valor_decimal($detalhe["totalbasepis"], 2),
							// Aliquota do PIS/PASEP (em percentual)
							"ALIQ_PIS" => $this->valor_decimal($aliq, 4),
							// Quantidade - base de calculo PIS/PASEP
							"QUANT_BC_PIS" => "",
							// Aliquota do PIS/PASEP (em reais)
							"ALIQ_PIS_QUANT" => "",
							// Valor do PIS/PASEP
							"VL_PIS" => $this->valor_decimal($detalhe["totalpis"], 2),
							// Codigo da conta contabil debitada/creditada
							"COD_CTA" => ""
						);
						$this->escrever_registro($registro);
					}
				}
			}
		}
	}

	// Detalhamento da consolidacao - operacoes de aquisicao com direito a credito, e operacoes de devolucao de compras e vendas - COFINS [nivel 4]
	protected function registro_C195($codproduto, $arr_itnotafiscal){
		$arr_detalhe = array();
		foreach($arr_itnotafiscal as $itnotafiscal){
			if($itnotafiscal["codproduto"] == $codproduto){
				$notafiscal = $this->arr_notafiscal[$itnotafiscal["idnotafiscal"]];
				$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
				switch($operacaonota["parceiro"]){
					case "C":
						$cliente = $this->arr_cliente[$notafiscal["codparceiro"]];
						$cpfcnpj = $cliente["cpfcnpj"];
						break;
					case "E":
						$estabelecimento = $this->arr_estabelecimento_parc[$notafiscal["codparceiro"]];
						$cpfcnpj = $estabelecimento["cpfcnpj"];
						break;
					case "F":
						$fornecedor = $this->arr_fornecedor[$notafiscal["codparceiro"]];
						$cpfcnpj = $fornecedor["cpfcnpj"];
						break;
				}
				$cpfcnpj = removeformat($cpfcnpj);
				$cst = $this->cstpiscofins($itnotafiscal);
				$cfop = $this->valor_natoperacao($itnotafiscal["natoperacao"]);
				$aliq = $this->valor_decimal($itnotafiscal["aliqcofins"], 4);
				$detalhe = $arr_detalhe[$cpfcnpj][$cst][$cfop][$aliq];
				$detalhe["totalbruto"] += $itnotafiscal["totalbruto"];
				$detalhe["totaldesconto"] += $itnotafiscal["totaldesconto"];
				$detalhe["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
				$detalhe["totalcofins"] += $itnotafiscal["totalcofins"];
				$arr_detalhe[$cpfcnpj][$cst][$cfop][$aliq] = $detalhe;
			}
		}
		foreach($arr_detalhe as $cpfcnpj => $detalhe_cpfcnpj){
			foreach($detalhe_cpfcnpj as $cst => $detalhe_cst){
				foreach($detalhe_cst as $cfop => $detalhe_cfop){
					foreach($detalhe_cfop as $aliq => $detalhe){
						$registro = array(
							// Texto fixo contendo "C195"
							"REG" => "C195",
							// CNPJ/CPF do participante a que se referem as operacoes consolidadas neste registro (pessoa juridica ou pessoa fisica vendedora/remetente)
							"CNPJ_CPF_PART" => $cpfcnpj,
							// Codigo da situacao tributaria referente a COFINS
							"CST_COFINS" => $cst,
							// Codigo fiscal de operacao e prestacao
							"CFOP" => $cfop,
							// Valor do item
							"VL_ITEM" => $this->valor_decimal($detalhe["totalbruto"], 2),
							// Valor do desconto comercial / exclusao
							"VL_DESC" => $this->valor_decimal($detalhe["totaldesconto"], 2),
							// Valor da base de calculo do COFINS
							"VL_BC_COFINS" => $this->valor_decimal($detalhe["totalbasecofins"], 2),
							// Aliquota do COFINS (em percentual)
							"ALIQ_COFINS" => $this->valor_decimal($aliq, 4),
							// Quantidade - base de calculo COFINS
							"QUANT_BC_COFINS" => "",
							// Aliquota do COFINS (em reais)
							"ALIQ_COFINS_QUANT" => "",
							// Valor do COFINS
							"VL_COFINS" => $this->valor_decimal($detalhe["totalcofins"], 2),
							// Codigo da conta contabil debitada/creditada
							"COD_CTA" => ""
						);
						$this->escrever_registro($registro);
					}
				}
			}
		}
	}

	// Processo referenciado [nivel 4] [NAO GERAR]
	protected function registro_C198(){

	}

	// Complemento do documento - operacoes de importacao (codigo 55) [nivel 4] [NAO GERAR]
	protected function registro_C199(){

	}

	// Nota fiscal de venda a consumidor (codigo 02) - consolidacao de documentos emitidos [nivel 3]
	protected function registro_C380($estabelecimento){
		$arr_notafiscal = array();
		$dataini = NULL;
		$datafin = NULL;
		$numnotaini = NULL;
		$numnotafin = NULL;
		$totalliquido = 0;
		foreach($this->arr_notafiscal as $notafiscal){
			// Verifica se a nota fiscal pertence ao estabelecimento corrente
			if($notafiscal["codestabelec"] != $estabelecimento->getcodestabelec()){
				continue;
			}
			if(in_array($this->modelo($notafiscal), array("02"))){
				$arr_notafiscal[] = $notafiscal;
				$dtemissao = $notafiscal["dtemissao"];
				$numnotafis = $notafiscal["numnotafis"];
				if(is_null($dataini) || compare_date($dtemissao, $dataini, "Y-m-d", "<")){
					$dataini = $dtemissao;
				}
				if(is_null($datafin) || compare_date($dtemissao, $datafin, "Y-m-d", ">")){
					$datafin = $dtemissao;
				}
				if(is_null($numnotaini) || $numnotafis < $numnotaini){
					$numnotaini = $numnotafis;
				}
				if(is_null($numnotafin) || $numnotafis > $numnotafin){
					$numnotafin = $numnotafis;
				}
				$totalliquido += $notafiscal["totalliquido"];
			}
		}
		if(sizeof($arr_notafiscal) > 0){
			$registro = array(
				// Texto fixo contendo "C380"
				"REG" => "C380",
				// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1 (codigo 02 - nota fiscal de venda a consumidor)
				"COD_MOD" => "02",
				// Data de emissao inicial dos documentos
				"DT_DOC_INI" => $this->valor_data($dataini),
				// Data de emissao final dos documentos
				"DT_DOC_FIN" => $this->valor_data($datafin),
				// Numero do documento fiscal incial
				"NUM_DOC_INI" => $numnotaini,
				// Numero do documento fiscal final
				"NUM_DOC_FIN" => $numnotafin,
				// Valor total dos documentos emitidos
				"VL_DOC" => $this->valor_decimal($totalliquido, 2),
				// Valor total dos documentos cancelados
				"VL_DOC_CANC" => $this->valor_decimal(0, 2)
			);
			$this->escrever_registro($registro);
			$this->registro_C381($arr_notafiscal);
			$this->registro_C385($arr_notafiscal);
		}
	}

	// Detalhamento da consolidacao - PIS/PASEP [nivel 4]
	protected function registro_C381($arr_notafiscal){
		$arr_detalhe = array();
		foreach($arr_notafiscal as $notafiscal){
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$codproduto = $itnotafiscal["codproduto"];
				$cst = $this->cstpiscofins($itnotafiscal);
				$aliq = $this->valor_decimal($itnotafiscal["aliqpis"], 4);
				$detalhe = $arr_detalhe[$codproduto][$cst][$aliq];
				$detalhe["totalbruto"] += $itnotafiscal["totalbruto"];
				$detalhe["totalbasepis"] += $itnotafiscal["totalbasepis"];
				$detalhe["totalpis"] += $itnotafiscal["totalpis"];
				$arr_detalhe[$codproduto][$cst][$aliq] = $detalhe;

				$_SESSION["SPED"]["cons_cst"][$cst]["totalbruto"] += $itnotafiscal["totalbruto"];
				$_SESSION["SPED"]["cons_cst"][$cst]["totalbasepis"] += $itnotafiscal["totalbasepis"];
				$_SESSION["SPED"]["cons_cst"][$cst]["totalpis"] += $itnotafiscal["totalpis"];
				$_SESSION["SPED"]["cons_cst"][$cst]["totalliquido"] += $itnotafiscal["totalliquido"];
			}
		}
		foreach($arr_detalhe as $codproduto => $detalhe_codproduto){
			foreach($detalhe_codproduto as $cst => $detalhe_cst){
				foreach($detalhe_cst as $aliq => $detalhe){
					$registro = array(
						// Texto fixo contendo "C381"
						"REG" => "C381",
						// Codigo da situacao tributaria referente a PIS/PASEP
						"CST_PIS" => $cst,
						// Codigo do item (campo 02 do registro 0200)
						"COD_ITEM" => $codproduto,
						// Valor do item
						"VL_ITEM" => $this->valor_decimal($detalhe["totalbruto"], 2),
						// Valor da base de calculo do PIS/PASEP
						"VL_BC_PIS" => $this->valor_decimal($detalhe["totalbasepis"], 2),
						// Aliquota do PIS/PASEP (em percentual)
						"ALIQ_PIS" => $this->valor_decimal($aliq, 4),
						// Quantidade - base de calculo PIS/PASEP
						"QUANT_BC_PIS" => "",
						// Aliquota do PIS/PASEP (em reais)
						"ALIQ_PIS_QUANT" => "",
						// Valor do PIS/PASEP
						"VL_PIS" => $this->valor_decimal($detalhe["totalpis"], 2),
						// Codigo da conta contabil debitada/creditada
						"COD_CTA" => ""
					);
					$this->escrever_registro($registro);
				}
			}
		}
	}

	// Detalhamento da consolidacao - COFINS [nivel 4]
	protected function registro_C385($arr_notafiscal){
		$arr_detalhe = array();
		foreach($arr_notafiscal as $notafiscal){
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$codproduto = $itnotafiscal["codproduto"];
				$cst = $this->cstpiscofins($itnotafiscal);
				$aliq = $this->valor_decimal($itnotafiscal["aliqcofins"], 4);
				$detalhe = $arr_detalhe[$codproduto][$cst][$aliq];
				$detalhe["totalbruto"] += $itnotafiscal["totalbruto"];
				$detalhe["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
				$detalhe["totalcofins"] += $itnotafiscal["totalcofins"];
				$arr_detalhe[$codproduto][$cst][$aliq] = $detalhe;

				$_SESSION["SPED"]["cons_cst"][$cst]["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
				$_SESSION["SPED"]["cons_cst"][$cst]["totalcofins"] += $itnotafiscal["totalcofins"];
			}
		}
		foreach($arr_detalhe as $codproduto => $detalhe_codproduto){
			foreach($detalhe_codproduto as $cst => $detalhe_cst){
				foreach($detalhe_cst as $aliq => $detalhe){
					$registro = array(
						// Texto fixo contendo "C385"
						"REG" => "C385",
						// Codigo da situacao tributaria referente a COFINS
						"CST_COFINS" => $cst,
						// Codigo do item (campo 02 do registro 0200)
						"COD_ITEM" => $codproduto,
						// Valor do item
						"VL_ITEM" => $this->valor_decimal($detalhe["totalbruto"], 2),
						// Valor da base de calculo do COFINS
						"VL_BC_COFINS" => $this->valor_decimal($detalhe["totalbasecofins"], 2),
						// Aliquota do COFINS (em percentual)
						"ALIQ_COFINS" => $this->valor_decimal($aliq, 4),
						// Quantidade - base de calculo COFINS
						"QUANT_BC_COFINS" => "",
						// Aliquota do COFINS (em reais)
						"ALIQ_COFINS_QUANT" => "",
						// Valor do COFINS
						"VL_COFINS" => $this->valor_decimal($detalhe["totalcofins"], 2),
						// Codigo da conta contabil debitada/creditada
						"COD_CTA" => ""
					);
					$this->escrever_registro($registro);
				}
			}
		}
	}

	// Notas fiscais de venda a consumidor (codigos 02, 2D, 2E e 59) - aquisicoes/entradas com credito [nivel 3]
	protected function registro_C395($estabelecimento){
		foreach($this->arr_notafiscal as $notafiscal){
			// Verifica se a nota fiscal pertence ao estabelecimento corrente
			if($notafiscal["codestabelec"] != $estabelecimento->getcodestabelec() || $notafiscal[operacao] == "NC"){
				continue;
			}
			if(in_array($this->modelo($notafiscal), array("02", "2D", "2E", "59"))){
				$registro = array(
					// Texto fixo contendo "C395"
					"REG" => "C395",
					// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => $this->modelo($notafiscal),
					// Codigo do participante emitente do documento (campo 02 do registro 0150)
					"COD_PART" => $this->codparceiro($notafiscal),
					// Serie do documento fiscal
					"SER" => $notafiscal["serie"],
					// Subserie do documento fiscal
					"SUB_SER" => "",
					// Numero do documento fiscal
					"NUM_DOC" => $notafiscal["numnotafis"],
					// Data de emissao do documento fiscal
					"DT_DOC" => $this->valor_data($notafiscal["dtemissao"]),
					// Valor total do documento fiscal
					"VL_DOC" => $this->valor_decimal($notafiscal["totalliquido"], 2)
				);
				$this->escrever_registro($registro);
				$this->registro_C396($notafiscal);
			}
		}
	}

	// Itens do documento (codigos 02, 2D, 2E, e 59) - aquisicoes/entradas com credito [nivel 4]
	protected function registro_C396($notafiscal){
		foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
			$registro = array(
				// Texto fixo contendo "C396"
				"REG" => "C396",
				// Codigo do item (campo 02 do regsitro 0200)
				"COD_ITEM" => $itnotafiscal["codproduto"],
				// Valor total do item (mercadorias ou servicos)
				"VL_ITEM" => $this->valor_decimal($itnotafiscal["totalliquido"], 2),
				// Valor do desconto comercial do item
				"VL_DESC" => $this->valor_decimal($itnotafiscal["totaldesconto"], 2),
				// Codigo da base de calculo do credito, conforme a tabela indicada no item 4.3.7
				"NAT_BC_CRED" => "13",
				// Codigo da situacao tributaria referente ao PIS/PASEP
				"CST_PIS" => $this->cstpiscofins($itnotafiscal),
				// Valor da base de calculo do credito de PIS/PASEP
				"VL_BC_PIS" => $this->valor_decimal($itnotafiscal["totalbasepis"], 2),
				// Aliquota do PIS/PASEP (em percentual)
				"ALIQ_PIS" => $this->valor_decimal($itnotafiscal["aliqpis"], 4),
				// Valor de credito de PIS/PASEP
				"VL_PIS" => $this->valor_decimal($itnotafiscal["totalpis"], 2),
				// Codigo da situacao tributaria referente ao COFINS
				"CST_COFINS" => $this->cstpiscofins($itnotafiscal),
				// Valor da base de calculo do credito de COFINS
				"VL_BC_COFINS" => $this->valor_decimal($itnotafiscal["totalbasecofins"], 2),
				// Aliquota do COFINS (em percentual)
				"ALIQ_COFINS" => $this->valor_decimal($itnotafiscal["aliqcofins"], 4),
				// Valor de credito de COFINS
				"VL_COFINS" => $this->valor_decimal($itnotafiscal["totalcofins"], 2),
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => ""
			);
			$this->escrever_registro($registro);
		}
	}

	// Equipamento ECF (codigos 02 e 2D) [nivel 3]
	protected function registro_C400($estabelecimento){
		foreach($this->arr_ecf as $ecf){
			if($ecf["equipamentofiscal"] != "ECF"){
				continue;
			}
			// Verifica se o ECF pertence ao estabelecimento corrente
			if($ecf["codestabelec"] == $estabelecimento->getcodestabelec()){
				$registro = array(
					// Texto fixo contendo "C400"
					"REG" => "C400",
					// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => "2D",
					// Modelo do equipamento
					"ECF_MOD" => $ecf["modelo"],
					// Numero de serie de fabricacao do ECF
					"ECF_FAB" => $ecf["numfabricacao"],
					// Numero do caixa atribuido ao ECF
					"ECF_CX" => $ecf["numeroecf"]
				);
				$this->escrever_registro($registro);
				$this->registro_C405($ecf);
				$this->registro_C489();
			}
		}
	}

	// Reducao Z (codigos 02 e 2D) [nivel 4]
	protected function registro_C405($ecf){
		foreach($this->arr_maparesumo as $maparesumo){
			if($maparesumo["codecf"] == $ecf["codecf"]){
				$this->t += $maparesumo["totalbruto"];
				$registro = array(
					// Texto fixo contendo "C405"
					"REG" => "C405",
					// Data do movimento a que se refere a Reducao Z
					"DT_DOC" => $this->valor_data($maparesumo["dtmovto"]),
					// Posicao do contador de reinicio de operacao
					"CRO" => $maparesumo["reiniciofim"],
					// Posicao do contador de Reducao Z
					"CRZ" => $maparesumo["numeroreducoes"],
					// Numero do contador de ordem de operacao do ultimo documento emitido no dia. (numero do COO na Reducao Z)
					"NUM_COO_FIN" => $maparesumo["operacaofim"],
					// Valor do grande total final
					"GT_FIN" => $this->valor_decimal($maparesumo["gtfinal"], 2),
					// Valor da venda bruta
					"VL_BRT" => $this->valor_decimal($maparesumo["totalbruto"], 2)
				);
				$this->escrever_registro($registro);
				$this->registro_C481($maparesumo);
				$this->registro_C485($maparesumo);
			}
		}
	}

	// Resumo diario de documentos emitidos por ECF - PIS/PASEP (codigos 02 e 2D) [nivel 5]
	protected function registro_C481($maparesumo){
		$arr_itcupom = array();
		foreach($this->arr_itcupom[$maparesumo["codmaparesumo"]] as $itcupom){
			$arr_aux = array();
			$cst = $this->cstpiscofins($itcupom);

			$_SESSION["SPED"]["cons_cst"][$cst]["totalbruto"] += $itcupom["valortotal"];
			$_SESSION["SPED"]["cons_cst"][$cst]["totalbasepis"] += $itcupom["totalbasepis"];
			$_SESSION["SPED"]["cons_cst"][$cst]["totalpis"] += $itcupom["totalpis"];
			$_SESSION["SPED"]["cons_cst"][$cst]["totalliquido"] += $itcupom["valortotal"];
//			Removeu por causa da Raromak
//			if($itcupom["tptribicms"] == "F"){
//				$natoperacao = $this->arr_natoperacao["5.405"];
//			}else{
			$natoperacao = $this->arr_natoperacao["5.102"];
//			}
//			$natoperacao = $this->arr_natoperacao[$itcupom["natoperacaonfcupom"]];
			$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];

			$arr_aux["valortotal"] += $itcupom["valortotal"];
			$arr_aux["totalbasepis"] += $itcupom["totalbasepis"];
			$arr_aux["totalpis"] += $itcupom["totalpis"];
			$arr_aux["aliqpis"] = $itcupom["aliqpis"];
			$arr_aux["codproduto"] = $itcupom["codproduto"];
			$arr_aux["contacontabil"] = $planocontas["contacontabil"];
			$arr_aux["cst"] = $cst;

			$arr_itcupom[$itcupom["codproduto"]] = $arr_aux;
		}

		foreach($arr_itcupom AS $codproduto => $itcupom){
			$registro = array(
				// Texto fixo contendo "C481"
				"REG" => "C481",
				// Codigo da situacao tributaria referente ao PIS/PASEP
				"CST_PIS" => $itcupom["cst"],
				// Valor total dos itens
				"VL_ITEM" => $this->valor_decimal($itcupom["valortotal"], 2),
				// Valor da base de calculo do PIS/PASEP
				"VL_BC_PIS" => $this->valor_decimal($itcupom["totalbasepis"], 2),
				// Aliquota do PIS/PASEP (em percentual)
				"ALIQ_PIS" => $this->valor_decimal($itcupom["aliqpis"], 2),
				// Quantidade - base de calculo PIS/PASEP
				"QUANT_BC_PIS" => "",
				// Aliquota do PIS/PASEP (em reais)
				"ALIQ_PIS_QUANT" => "",
				// Valor do PIS/PASEP
				"VL_PIS" => $this->valor_decimal($itcupom["totalpis"], 2),
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $codproduto,
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => $itcupom["contacontabil"]
			);
			$this->escrever_registro($registro);
		}
	}

	// Resumo diario de documentos emitidos por ECF - COFINS (codigos 02 e 2D) [nivel 5]
	protected function registro_C485($maparesumo){
		$arr_itcupom = array();
		foreach($this->arr_itcupom[$maparesumo["codmaparesumo"]] as $itcupom){
			$arr_aux = array();
			$cst = $this->cstpiscofins($itcupom);

			$_SESSION["SPED"]["cons_cst"][$cst]["totalbasecofins"] += $itcupom["totalbasecofins"];
			$_SESSION["SPED"]["cons_cst"][$cst]["totalcofins"] += $itcupom["totalcofins"];

//			if($itcupom["tptribicms"] == "F"){
//				$natoperacao = $this->arr_natoperacao["5.405"];
//			}else{
			$natoperacao = $this->arr_natoperacao["5.102"];
//			}
			$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];

			$arr_aux["valortotal"] += $itcupom["valortotal"];
			$arr_aux["totalbasecofins"] += $itcupom["totalbasecofins"];
			$arr_aux["totalcofins"] += $itcupom["totalcofins"];
			$arr_aux["aliqcofins"] = $itcupom["aliqcofins"];
			$arr_aux["codproduto"] = $itcupom["codproduto"];
			$arr_aux["contacontabil"] = $planocontas["contacontabil"];
			$arr_aux["cst"] = $cst;

			$arr_itcupom[$itcupom["codproduto"]] = $arr_aux;
		}

		foreach($arr_itcupom AS $codproduto => $itcupom){
			$registro = array(
				// Texto fixo contendo "C485"
				"REG" => "C485",
				// Codigo da situacao tributaria referente ao COFINS
				"CST_COFINS" => $itcupom["cst"],
				// Valor total dos itens
				"VL_ITEM" => $this->valor_decimal($itcupom["valortotal"], 2),
				// Valor da base de calculo do COFINS
				"VL_BC_COFINS" => $this->valor_decimal($itcupom["totalbasecofins"], 2),
				// Aliquota do PIS/PASEP (em percentual)
				"ALIQ_COFINS" => $this->valor_decimal($itcupom["aliqcofins"], 2),
				// Quantidade - base de calculo COFINS
				"QUANT_BC_COFINS" => "",
				// Aliquota do COFINS (em reais)
				"ALIQ_COFINS_QUANT" => "",
				// Valor do COFINS
				"VL_COFINS" => $this->valor_decimal($itcupom["totalcofins"], 2),
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $codproduto,
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => $itcupom["contacontabil"]
			);
			$this->escrever_registro($registro);
		}
	}

	// Precesso referenciado [nivel 4] [NAO GERAR]
	protected function registro_C489(){

	}

	// Registro analitico do movimento diario (codigo 02 e 2D) [nivel 3] [NAO GERAR]
	protected function registro_C490(){

	}

	// Nota fiscal/conta de energia eletrica (codigo 06), nota fiscal/conta de fornecimento de agua
	// canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28) [nivel 3]
	protected function registro_C500($estabelecimento){
		foreach($this->arr_notadiversa as $notadiversa){
			if(!in_array($this->valor_natoperacao($notadiversa["tipodocumentofiscal"]), array("06", "28", "29"))){
				continue;
			}
			if($notadiversa["codestabelec"] != $estabelecimento->getcodestabelec()){
				continue;
			}

			$registro = array(
				// Texto fixo contendo "C500"
				"REG" => "C500",
				// Codigo do participante do fornecedor
				"COD_PART" => $notadiversa["tipoparceiro"].$notadiversa["codparceiro"],
				// Codigo do modelo do documento fiscal
				"COD_MOD" => $notadiversa["tipodocumentofiscal"],
				// Codigo da situacao do modelo fiscal
				"COD_SIT" => "00",
				// Serie do documento fiscal
				"SER" => $notadiversa["serie"],
				// Sub serie do documento fiscal
				"SUB" => "",
				// Numero do documento fiscal
				"NUM_DOC" => $notadiversa["numnotafis"],
				// Data de emissao da nota
				"DT_DOC" => $this->valor_data($notadiversa["dtemissao"]),
				// Data da entrada da nota
				"DT_NET" => "",
				// Valor total do documento fiscal
				"VL_DOC" => $this->valor_decimal($notadiversa["totalbruto"], 2),
				// Valor acumulado do ICMS
				"VL_ICMS" => $this->valor_decimal($notadiversa["totalicms"], 2),
				// Codigo da informacao complementar do documento fiscal (campo 02 do Registro 0450)
				"COD_INF" => "",
				// Valor total do PIS/PASEP
				"VL_PIS" => $this->valor_decimal($notadiversa["totalpis"], 2),
				// Valor total do COFINS
				"VL_COFINS" => $this->valor_decimal($notadiversa["totalcofins"], 2)
			);
			$this->escrever_registro($registro);
			$this->registro_C501($notadiversa);
			$this->registro_C505($notadiversa);
		}
	}

	// Complemento da operacao (codigos 06, 28 e 29) – PIS/PASEP [nivel 4]
	protected function registro_C501($notadiversa){
		$planocontas = $this->arr_planocontas[$notadiversa["codconta"]];

		$registro = array(
			// Texto fixo contendo C501
			"REG" => "C501",
			// Codigo de situacao tributaria referente ao PIS/PASEP
			"CST_PIS" => $notadiversa["codcstpiscofins"],
			// Valor total dos itens
			"VL_ITEM" => $this->valor_decimal($notadiversa["totalbruto"], 2),
			// Codigo da Base de Calculo do Credito, conforme a Tabela indicada no item 4.3.7
			"NAT_BC_CRED" => "04",
			// Valor da base de calculo do PIS/PASEP
			"VL_BC_PIS" => $this->valor_decimal($notadiversa["basepis"], 2),
			// Aliquota do PIS/PASEP (em percentual)
			"ALIQ_PIS" => $this->valor_decimal($notadiversa["aliqpis"], 2),
			// Valor do PIS/PASEP
			"VL_PIS" => $this->valor_decimal($notadiversa["totalpis"], 2),
			// Codigo da conta analitica contabil debitada/creditada
			"COD_CTA" => $planocontas["contacontabil"]
		);
		$this->escrever_registro($registro);
	}

	// Complemento da operacao (codigos 06, 28 e 29) – COFINS [nivel 4]
	protected function registro_C505($notadiversa){
		$planocontas = $this->arr_planocontas[$notadiversa["codconta"]];

		$registro = array(
			// Texto fixo contendo C505
			"REG" => "C505",
			// Codigo de situacao tributaria referente ao COFINS
			"CST_COFINS" => $notadiversa["codcstpiscofins"],
			// Valor total dos itens
			"VL_ITEM" => $this->valor_decimal($notadiversa["totalbruto"], 2),
			// Codigo da Base de Calculo do Credito, conforme a Tabela indicada no item 4.3.7
			"NAT_BC_CRED" => "04",
			// Valor da base de calculo do COFINS
			"VL_BC_COFINS" => $this->valor_decimal($notadiversa["basecofins"], 2),
			// Aliquota do COFINS (em percentual)
			"ALIQ_COFINS" => $this->valor_decimal($notadiversa["aliqcofins"], 2),
			// Valor do COFINS
			"VL_COFINS" => $this->valor_decimal($notadiversa["totalcofins"], 2),
			// Codigo da conta analitica contabil debitada/creditada
			"COD_CTA" => $planocontas["contacontabil"]
		);
		$this->escrever_registro($registro);
	}

	// Consolidacao diaria de notas fiscais/contas de energia eletrica (codigo 06), nota fiscal/conta de
	// fornecimento de agua canalizada (codigo 29) e notafiscal de consumo fornecimento de gas (codigo 28)
	// (empresas nao obrigadas ao convenio ICMS 115/03) [nivel 3] [NAO GERAR]
	protected function registro_C600(){

	}

	// Identificacao do equipamento SAT-CF-E
	protected function registro_C860($estabelecimento){
		$arr_numfabricao = array();
		foreach($this->arr_ecf as $ecf){
			if($ecf["codestabelec"] != $estabelecimento->getcodestabelec()){
				continue;
			}
			if($ecf["equipamentofiscal"] != "SAT"){
				continue;
			}
			if(in_array($ecf["numfabricacao"], $arr_numfabricao)){
				continue;
			}
			$arr_numfabricao[] = $ecf["numfabricacao"];

			foreach($ecf["resumodiario"] as $dtmovto => $resumo){
				$registro = array(
					// Texto fixo contendo "C860"
					"REG" => "C860",
					// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => "59",
					// Numero de serie do equipamento SAT
					"NR_SAT" => $ecf["numfabricacao"],
					// Data de emissao dos documentos fiscais
					"DT_DOC" => $this->valor_data($dtmovto),
					// Numero do documento inicial
					"DOC_INI" => $resumo["cupomini"],
					// Numero do documento final
					"DOC_FIM" => $resumo["cupomfim"]
				);
				$this->escrever_registro($registro);
				$this->registro_C870($ecf, $dtmovto);
			}
		}
	}

	// Resumo diario de documentos emitidos por equipamento SAT-CF-E (codigo 59) - PIS/PASEP e Cofins
	private function registro_C870($ecf, $dtmovto){
		foreach($this->arr_itcupom["SAT"] as $itcupom){
			$found = false;
			foreach($this->arr_ecf AS $ecf2){
				if($itcupom["codecf"] == $ecf2["codecf"] && $ecf["numfabricacao"] == $ecf2["numfabricacao"]){
					$found = true;
					break;
				}
			}
			if(!$found){
				continue;
			}
			if($itcupom["codecf"] != $ecf["codecf"]){
				continue;
			}
			if($itcupom["dtmovto"] != $dtmovto){
				continue;
			}
			$cstpiscofins = $this->cstpiscofins($itcupom);

			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalbruto"] += $itcupom["valortotal"];
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalbasepis"] += $itcupom["totalbasepis"];
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalpis"] += $itcupom["totalpis"];
			$_SESSION["SPED"]["cons_cst"][$cstpiscofins]["totalliquido"] += $itcupom["valortotal"];

			$natoperacao = $this->arr_natoperacao[$itcupom["natoperacaonfcupom"]];
			$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];

			if($itcupom["fabricacaopropria"] == "S"){
				$natoperacao = $this->valor_natoperacao($itcupom["tptribicms"] === "F" ? "5.401" : "5.101");
			}else{
				$natoperacao = $this->valor_natoperacao($itcupom["tptribicms"] === "F" ? "5.405" : "5.102");
			}

			$registro = array(
				// Texto fixo contendo "C870"
				"REG" => "C870",
				// Codigo do item (campo 02 do registro 0200)
				"COD_ITEM" => $itcupom["codproduto"],
				// Codigo fiscal de operacao e prestacao
				"CFOP" => $natoperacao,
				// Valor total dos itens
				"VL_ITEM" => $this->valor_decimal($itcupom["valortotal"], 2),
				// Valor da exclusao/desconto comercial dos itens
				"VL_DESC" => $this->valor_decimal(0, 2),
				// Codigo da situacao tributaria referente ao PIS/PASEP
				"CST_PIS" => $cstpiscofins,
				// Valor da base de calculo do PIS/PASEP
				"VL_BC_PIS" => $this->valor_decimal($itcupom["totalbasepis"], 2),
				// Aliquota do PIS/PASEP (em percentual)
				"ALIQ_PIS" => $this->valor_decimal($itcupom["aliqpis"], 2),
				// Valor do PIS/PASEP
				"VL_PIS" => $this->valor_decimal($itcupom["totalpis"], 2),
				// Codigo da situacao tributaria referente ao COFINS
				"CST_COFINS" => $cstpiscofins,
				// Valor da base de calculo do COFINS
				"VL_BC_COFINS" => $this->valor_decimal($itcupom["totalbasecofins"], 2),
				// Aliquota do COFINS (em percentual)
				"ALIQ_COFINS" => $this->valor_decimal($itcupom["aliqcofins"], 2),
				// Valor do COFINS
				"VL_COFINS" => $this->valor_decimal($itcupom["totalcofins"], 2),
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => $planocontas["contacontabil"]
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
		foreach($this->arr_notadiversa as $notadiversa){
			if($this->valor_natoperacao($notadiversa["natoperacao"]) == "1303"){
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
		if($ind_mov == 0){
			$this->registro_D010();
		}
	}

	// Identificacao do estabelecimento [nivel 2]
	protected function registro_D010(){
		foreach($this->arr_estabelecimento as $estabelecimento){
			$registro = array(
				// Texto fixo contendo "D010"
				"REG" => "D010",
				// Numero de inscricao do estabelecimento no CNPJ
				"CNPJ" => removeformat($estabelecimento->getcpfcnpj())
			);
			$this->escrever_registro($registro);
			$this->registro_D100($estabelecimento);
			//
			$this->registro_D500($estabelecimento);
		}
	}

	// Aquisicao de servicos de transporte - nota fiscal de servico de transporte (codigo 07) e conhecimento de transporte
	// rodoviario de cargas (codigo 08), conhecimento de transporte de cargas avulso (codigo 8B), aquaviario de cargas
	// (codigo 09), aereo (codigo 10), ferroviario de cargas (codigo 11), multimodal de cargas (codigo 26), nota fiscal
	// de transporte ferroviario de carga (codigo 27) e conhecimento de transporte eletronico - CT-e (codigo 57)
	protected function registro_D100($estabelecimento){
		foreach($this->arr_notafrete as $notafrete){
			if($notafrete["codestabelec"] == $estabelecimento->getcodestabelec()){
				$planocontas = $this->arr_planocontas[$notafrete["codconta"]];

				$registro = array(
					// Texto fixo contendo "D100"
					"REG" => "D100",
					// Indicador do tipo de operacao:
					// 0 - Aquisicao
					"IND_OPER" => "0",
					// Indicador do emitente do documento fiscal:
					// 0 - Emissao propria
					// 1 - Emissao por terceiros
					"IND_EMIT" => "1",
					// Codigo do participante (campo 02 do registro 0150)
					"COD_PART" => "T".$notafrete["codtransp"],
					// Codigo do modelo do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => $notafrete["tipodocumentofiscal"],
					// Codigo da situacao do documento fiscal, conforme a tabela 4.1.2
					"COD_SIT" => "00",
					// Serie do documento fiscal
					"SER" => $notafrete["serie"],
					// Subserie do documento fiscal
					"SUB" => "",
					// Numero do documento fiscal
					"NUM_DOC" => $notafrete["numnotafis"],
					// Chave do conhecimento de transporte eletronico
					"CHV_CTE" => "",
					// Data de referencia/emissao dos documentos fiscais
					"DT_DOC" => $this->valor_data($notafrete["dtemissao"]),
					// Data de aquisicao ou prestacao do servico
					"DT_A_P" => $this->valor_data($notafrete["dtentrega"]),
					// Tipo de conhecimento de transporte eletronico conforme definido no manual de integracao do CT-e
					"TP_CT-e" => "",
					// Chave do CT-e de referencia cujos valores foram complementados (opcao "1" do campo anterior)
					// ou cujo debito foi anulado (opcao "2" do campo anterior)
					"CHV_CTE_REF" => "",
					// Valor total do documento fiscal
					"VL_DOC" => $this->valor_decimal($notafrete["totalliquido"], 2),
					// Valor total do desconto
					"VL_DESC" => $this->valor_decimal(0, 2),
					// Indicador do tipo de frete:
					// 0 - Por conta do emitente
					// 1 - Por conta do destinatario/remetente
					// 2 - Por conta de terceiros
					// 9 - Sem cobranca de frete
					"IND_FRT" => "1",
					// Valor total da prestacao de servicos
					"VL_SERV" => $this->valor_decimal($notafrete["totalliquido"], 2),
					// Valor total da base de calculo do ICMS
					"VL_BC_ICMS" => $this->valor_decimal($notafrete["totalbaseicms"], 2),
					// Valor do ICMS
					"VL_ICMS" => $this->valor_decimal($notafrete["totalicms"], 2),
					// Valor nao-tributado do ICMS
					"VL_NT" => $this->valor_decimal($notafrete["totalbaseisento"], 2),
					// Codigo da informacao complementar do documento fiscal (campo 02 do registro 0450)
					"COD_INF" => "",
					// Codigo da conta analitica contabil debitada/creditada
					"COD_CTA" => $planocontas["contacontabil"],
				);
				$this->escrever_registro($registro);
				$this->registro_D101($notafrete);
				$this->registro_D105($notafrete);
			}
		}
	}

	// Complemento de documento de transporte (codigos 07, 08 8B, 09, 10, 11, 26, 27 e 57) - PIS/PASEP
	protected function registro_D101($notafrete){
		$planocontas = $this->arr_planocontas[$notafrete["codconta"]];

		$registro = array(
			// Texto fixo contendo "D101"
			"REG" => "D101",
			// Indicador da natureza do frete contratado, referente a:
			// 0 - Operacoes de vendas, com onus suportado pelo estabelecimento vendedor
			// 1 - Operacoes de vendas, com onus suportado pelo adquirente
			// 2 - Operacoes de compra (bens para revenda, materias-prima e outros produtos, geradores de credito)
			// 3 - Operacoes de compra (bens para revenda, materias-prima e outros produtos, nao geradores de credito)
			// 4 - Transferencia de produtos acabados entre estabelecimentos da pessoa juridica
			// 5 - Transferencia de produtos em elaboracao entre estabelecimentos da pessoa juridica
			// 9 - Outras
			"IND_NAT_FRT" => "2",
			// Valor total dos itens
			"VL_ITEM" => $this->valor_decimal($notafrete["totalliquido"], 2),
			// Codigo da situacao tributaria referente ao PIS/PASEP
			"CST_PIS" => "50",
			// Codigo da base de calculo do credito, conforme a tabela indicada no item 4.3.7
			"NAT_BC_CRED" => "14",
			// Valor da base de calculo do PIS/PASEP
			"VL_BC_PIS" => $this->valor_decimal($notafrete["totalbasepis"], 2),
			// Aliquota do PIS/PASEP
			"ALIQ_PIS" => $this->valor_decimal($notafrete["aliqpis"], 4),
			// Valor do PIS/PASEP
			"VL_PIS" => $this->valor_decimal($notafrete["totalpis"], 2),
			// Codigo da conta analitica contabil debitada/creditada
			"COD_CTA" => $planocontas["contacontabil"]
		);
		$this->escrever_registro($registro);
	}

	// Complemento de documento de transporte (codigos 07, 08 8B, 09, 10, 11, 26, 27 e 57) - COFINS
	protected function registro_D105($notafrete){
		$planocontas = $this->arr_planocontas[$notafrete["codconta"]];

		$registro = array(
			// Texto fixo contendo "D105"
			"REG" => "D105",
			// Indicador da natureza do frete contratado, referente a:
			// 0 - Operacoes de vendas, com onus suportado pelo estabelecimento vendedor
			// 1 - Operacoes de vendas, com onus suportado pelo adquirente
			// 2 - Operacoes de compra (bens para revenda, materias-prima e outros produtos, geradores de credito)
			// 3 - Operacoes de compra (bens para revenda, materias-prima e outros produtos, nao geradores de credito)
			// 4 - Transferencia de produtos acabados entre estabelecimentos da pessoa juridica
			// 5 - Transferencia de produtos em elaboracao entre estabelecimentos da pessoa juridica
			// 9 - Outras
			"IND_NAT_FRT" => "2",
			// Valor total dos itens
			"VL_ITEM" => $this->valor_decimal($notafrete["totalliquido"], 2),
			// Codigo da situacao tributaria referente ao COFINS
			"CST_COFINS" => "50",
			// Codigo da base de calculo do credito, conforme a tabela indicada no item 4.3.7
			"NAT_BC_CRED" => "14",
			// Valor da base de calculo do COFINS
			"VL_BC_COFINS" => $this->valor_decimal($notafrete["totalbasecofins"], 2),
			// Aliquota do COFINS
			"ALIQ_COFINS" => $this->valor_decimal($notafrete["aliqcofins"], 4),
			// Valor do COFINS
			"VL_COFINS" => $this->valor_decimal($notafrete["totalcofins"], 2),
			// Codigo da conta analitica contabil debitada/creditada
			"COD_CTA" => $planocontas["contacontabil"]
		);
		$this->escrever_registro($registro);
	}

	// Nota fiscal de servico de comunicacao (codigo 21) e nota fiscal de servico de telecomunicacao (codigo 22) [nivel 3]
	protected function registro_D500($estabelecimento){
		foreach($this->arr_notadiversa as $notadiversa){
			if($notadiversa["codestabelec"] == $estabelecimento->getcodestabelec() && $this->valor_natoperacao($notadiversa["natoperacao"]) == "1303"){
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
					// Codigo do identificacao do documento fiscal, conforme a tabela 4.1.1
					"COD_MOD" => "22",
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
					"DT_A_P" => $this->valor_data($notadiversa["dtdigitacao"]),
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
				);
				$this->escrever_registro($registro);
			}
		}
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

	// Abertura do bloco F [nivel 1]
	protected function registro_F001(){
		$registro = array(
			// Texto fixo contendo "F001"
			"REG" => "F001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "1"
		);
		$this->escrever_registro($registro);
	}

	// Encerramento do bloco F [nivel 1]
	protected function registro_F990($t_registros = null){
		$t_quantidade = 0;
		foreach($this->n_registro as $registro => $quantidade){
			if(substr($registro, 0, 1) == "F"){
				$t_quantidade += $quantidade;
			}
		}
		$registro = array(
			// Texto fixo contento "F990"
			"REG" => "F990",
			// Quantidade de linhas do bloco F
			"QTD_LIN_F" => $t_quantidade + 1
		);
		$this->escrever_registro($registro);
	}

	// Abertura do bloco M [nivel 1]
	protected function registro_M001(){
		$registros = array();
		$registros[] = array(
			// Texto fixo contendo "M001"
			"REG" => "M001",
			// Indicador de movimento:
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => "0"
		);

		if($this->matriz->getregimetributario() == "3"){
			$registros = array_merge($registros, $this->registro_M100());
		}
		$registros = array_merge($registros, $this->registro_M200());
		$registros = array_merge($registros, $this->registro_M300());
		$registros = array_merge($registros, $this->registro_M350());
		$registros = array_merge($registros, $this->registro_M400());
		if($this->matriz->getregimetributario() == "3"){
			$registros = array_merge($registros, $this->registro_M500());
		}
		$registros = array_merge($registros, $this->registro_M600());
		$registros = array_merge($registros, $this->registro_M700());
		$registros = array_merge($registros, $this->registro_M750());
		$registros = array_merge($registros, $this->registro_M800());
		return $registros;
	}

	// Credito de PIS/PASEP relativo ao periodo [nivel 2]
	protected function registro_M100(){
		$registros = array();
		$arr_itnotafiscal = array();
		$arr_itnotafiscal_reduc = array();
		$arr_notafrete = array();
		$arr_notadiversa = array();
		$arr_total = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			if($operacaonota["tipo"] == "E" && $notafiscal["totalpis"] > 0){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$tipocred = $this->tipocredito($itnotafiscal);
					$aliqpis = $this->valor_decimal($itnotafiscal["aliqpis"], 4);
					$arr_itnotafiscal[$tipocred][$aliqpis][] = $itnotafiscal;
					$arr_total[$tipocred][$aliqpis]["totalbasepis"] += $itnotafiscal["totalbasepis"];
					$arr_total[$tipocred][$aliqpis]["totalpis"] += $itnotafiscal["totalpis"];
				}
			}elseif($operacaonota["operacao"] == "DF"){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$tipocred = $this->tipocredito($itnotafiscal);
					$aliqpis = $this->valor_decimal($itnotafiscal["aliqpis"], 4);
					$arr_itnotafiscal_reduc[$tipocred][$aliqpis][] = $itnotafiscal;
					$arr_total[$tipocred][$aliqpis]["totalpis_reduc"] += $itnotafiscal["totalpis"];
				}
			}
		}
		foreach($this->arr_notafrete as $notafrete){
			$tipocred = "101";
			$aliqpis = $this->valor_decimal($notafrete["aliqpis"], 4);
			$arr_notafrete[$tipocred][$aliqpis][] = $notafrete;
			$arr_total[$tipocred][$aliqpis]["totalbasepis"] += $notafrete["totalbasepis"];
			$arr_total[$tipocred][$aliqpis]["totalpis"] += $notafrete["totalpis"];
		}
		foreach($this->arr_notadiversa as $notadiversa){
			// Nota fiscal de energia eletrica
			if(in_array($notadiversa["tipodocumentofiscal"], array("06"))){
				$tipocred = "101";
				$aliqpis = $this->valor_decimal($notadiversa["aliqpis"], 4);
				$arr_notadiversa[$tipocred][$aliqpis][] = $notadiversa;
				$arr_total[$tipocred][$aliqpis]["totalbasepis"] += $notadiversa["basepis"];
				$arr_total[$tipocred][$aliqpis]["totalpis"] += $notadiversa["totalpis"];
			}
		}
		foreach($this->arr_notafiscalservico as $notafiscalservico){
			if($notafiscalservico["indicadoroperacao"] == "1"){
				continue;
			}
			foreach($notafiscalservico["itnotafiscalservico"] as $itnotafiscalservico){
				$tipocred = "101";
				$aliqpis = $this->valor_decimal($itnotafiscalservico["aliquotapis"], 4);
				$arr_itnotafiscalservico[$tipocred][$aliqpis][] = $itnotafiscalservico;
				$arr_total[$tipocred][$aliqpis]["totalbasepis"] += $itnotafiscalservico["basecalculopis"];
				$arr_total[$tipocred][$aliqpis]["totalpis"] += $itnotafiscalservico["valorpis"];
			}
		}
		$sum_vl_cred = 0;
		foreach($arr_total as $tipocred => $total_tipocred){
			foreach($total_tipocred as $aliqpis => $total){
				$sum_vl_cred += $total["totalpis"];
			}
		}

		foreach($arr_total as $tipocred => $total_tipocred){
			foreach($total_tipocred as $aliqpis => $total){
				$vl_cred = $total["totalpis"];
				$vl_ajus_acres = 0;
				$vl_ajus_reduc = $total["totalpis_reduc"];
				$vl_cred_dif = 0;
				$vl_cred_desc = ($this->m200_vl_tot_cont_nc_per <= $sum_vl_cred ? $this->m200_vl_tot_cont_nc_per : $vl_cred);
				$vl_cred_disp = $vl_cred + $vl_ajus_acres - $vl_ajus_reduc - $vl_cred_dif;
				$vl_cred_desc = $total["totalpis"] - $vl_ajus_reduc;
				$sld_cred = $vl_cred_disp - $vl_cred_desc;
				$ind_desc_cred = ($this->m200_vl_tot_cont_nc_per >= $this->m200_vl_tot_cred_desc ? "0" : "1");

				if($vl_cred === 0){
					continue;
				}

				$this->m200_vl_tot_cred_desc += $vl_cred_desc;

				$registros[] = array(
					// Texto fixo contendo "M100"
					"REG" => "M100",
					// Codigo de Tipo de Credito apurado no periodo, conforme a tabela 4.3.6
					"COD_CRED" => $tipocred,
					// Indicador de credito oriundo de:
					// 0 - Operacoes proprias
					// 1 - Evento de incorporacao, cisao ou fusao
					"IND_CRED_ORI" => "0",
					// Valor da base de calculo do credito
					"VL_BC_PIS" => $this->valor_decimal($total["totalbasepis"], 2),
					// Aliquota do PIS/PASEP (em percentual)
					"ALIQ_PIS" => $aliqpis,
					// Quantidade - base de calculo PIS
					"QUANT_BC_PIS" => $this->valor_decimal(0, 2),
					// Aliquota do PIS (em reais)
					"ALIQ_PIS_QUANT" => "",
					// Valor total de credito apurado no periodo
					"VL_CRED" => $this->valor_decimal($vl_cred, 2),
					// Valor total dos ajustes de acrescimo
					"VL_AJUS_ACRES" => $this->valor_decimal($vl_ajus_acres, 2),
					// Valor total dos ajustes de reducao
					"VL_AJUS_REDUC" => $this->valor_decimal($vl_ajus_reduc, 2),
					// Valor total do credito diferido no periodo
					"VL_CRED_DIF" => $this->valor_decimal($vl_cred_dif, 2),
					// Valor total do credito disponivel relativo ao periodo (VL_CRED + VL_AJUS_ACRES - VL_AJUS_REDUC - VL_CRED_DIF)
					"VL_CRED_DISP" => $this->valor_decimal($vl_cred_disp, 2),
					// Indicador de opcao de utilizacao do credito disponivel no periodo:
					// 0 - Utilizacao do valor total para desconto da contribuicao apurada no periodo, no registro M200
					// 1 - Utilizacao do valor parcial para desconto da contribuicao apurada no periodo, no registro M200
					"IND_DESC_CRED" => $ind_desc_cred,
					// Valor do credito disponivel, descontado da contribuicao apurada no proprio periodo
					// Se IND_DESC_CRED = 0, informar o valor total do campo VL_CRED_DISP
					// Se IND_DESC_CRED = 1, informar o valor parcial do campo VL_CRED_DISP
					"VL_CRED_DESC" => $this->valor_decimal($vl_cred_desc, 2),
					// Saldo de creditos a utilizar em periodos futuros (VL_CRED_DIPS - VL_CRED_DESC)
					"SLD_CRED" => $this->valor_decimal($sld_cred, 2)
				);
				$registros = array_merge($registros, $this->registro_M105($arr_itnotafiscal[$tipocred][$aliqpis], $arr_notafrete[$tipocred][$aliqpis], $arr_notadiversa[$tipocred][$aliqpis], $arr_itnotafiscalservico[$tipocred][$aliqpis]));
				$registros = array_merge($registros, $this->registro_M110($arr_itnotafiscal_reduc[$tipocred][$aliqpis]));
			}
		}
		return $registros;
	}

	// Detalhamento da base de calculo de credito apurado no periodo - PIS/PASEP [nivel 3]
	protected function registro_M105($arr_itnotafiscal, $arr_notafrete, $arr_notadiversa, $arr_itnotafiscalservico){
		$registros = array();
		$arr_total = array();
		if(is_array($arr_itnotafiscal)){
			foreach($arr_itnotafiscal as $itnotafiscal){
				$natcred = $this->natcredito($itnotafiscal);
				$codcst = $this->cstpiscofins($itnotafiscal);
				$arr_total[$natcred][$codcst]["totalbasepis"] += $itnotafiscal["totalbasepis"];
				$arr_total[$natcred][$codcst]["totalpis"] += $itnotafiscal["totalpis"];
			}
		}
		if(is_array($arr_notafrete)){
			foreach($arr_notafrete as $notafrete){
				$natcred = "14";
				$codcst = "50";
				$arr_total[$natcred][$codcst]["totalbasepis"] += $notafrete["totalbasepis"];
				$arr_total[$natcred][$codcst]["totalpis"] += $notafrete["totalpis"];
			}
		}
		if(is_array($arr_notadiversa)){
			foreach($arr_notadiversa as $notadiversa){
				$natcred = "04";
				$codcst = "50";
				$arr_total[$natcred][$codcst]["totalbasepis"] += $notadiversa["basepis"];
				$arr_total[$natcred][$codcst]["totalpis"] += $notadiversa["totalpis"];
			}
		}
		if(is_array($arr_itnotafiscalservico)){
			foreach($arr_itnotafiscalservico as $itnotafiscalservico){
				$natcred = $itnotafiscalservico["natbasecalculocredito"];
				$codcst = $itnotafiscalservico["cstpis"];
				$arr_total[$natcred][$codcst]["totalbasepis"] += $itnotafiscalservico["basecalculopis"];
				$arr_total[$natcred][$codcst]["totalpis"] += $notafrete["valorpis"];
			}
		}

		foreach($arr_total as $natcred => $arr_codcst){
			foreach($arr_codcst as $codcst => $total){
				$vl_bc_pis_tot = $total["totalbasepis"];
				$vl_bc_pis_cum = 0;
				$vl_bc_pis_nc = $vl_bc_pis_tot - $vl_bc_pis_cum;
				$vl_bc_pis = $vl_bc_pis_nc;

				if($vl_bc_pis_tot == 0){
					continue;
				}

				$registros[] = array(
					// Texto fixo contendo "M105"
					"REG" => "M105",
					// Codigo da base de calculo do credito apurado no periodo, conforme a tabela 4.3.7
					"NAT_BC_CRED" => $natcred,
					// Codigo da situacao tributaria referente ao credito de PIS/PASEP (tabela 4.3.3)
					// vindulado ao tipo de credito escriturado em M100
					"CST_PIS" => $codcst,
					// Valor total da base de calculo escriturada nos documentos e operacoes (blocos A, C, D e F),
					// referente ao CST_PIS informado no campo CST_PIS
					"VL_BC_PIS_TOT" => $this->valor_decimal($vl_bc_pis_tot, 2),
					// Parcela do valor total da base de calculo informada no campo VL_BC_PIS_TOT,
					// vinculada a receitas com incidencia cumulativa
					// Campo de preenchimento especifico para pessoa juridica sujeita ao regime cumulativo e
					// nao-cumulativo da contribuicao (COD_INC_TRIB = 3 do registro 0110)
					"VL_BC_PIS_CUM" => "",
					// Valor total da base de calculo do credito, vinculada a receitas com incidencia nao-cumulativa (VL_BC_PIS_TOT - VL_BC_PIS_CUM)
					"VL_BC_PIS_NC" => $this->valor_decimal($vl_bc_pis_nc, 2),
					// Valor da base de calculo do credito, vinculada ao tipo de credito escriturado em M100
					// Para os CST_PIS = 50, 51, 52, 60, 61 e 62: informar o valor do campo VL_BC_PIS_NC
					// Para os CST_PIS = 53, 54, 55, 56, 63, 64, 65 e 66 (credito sobre operacoes vinculadas a mais de um tipo de recieta): informar
					// a parcela do valor do campo VL_BC_PIS_NC vinculada especificamente ao tipo de credito escriturado em M100
					// -> O valor desse campo sera transportado para o campo VL_BC_PIS do registro M100
					"VL_BC_PIS" => $this->valor_decimal($vl_bc_pis, 2),
					// Quantidade total da base de calculo do credito apurado em unidade de medida de produto, escriturada nos ducumentos e operacoes
					// (bloco A, C, D e F), referente ao campo CST_PIS
					"QUANT_BC_PIS_TOT" => "",
					// Parcela da base de calculo do credito em quantidade (campo QUANT_BC_PIS_TOT) vinculada ao tipo de credito escriturado em M100
					// Para os CST_PIS = 50, 51 e 52: informar o valor do campo QUANT_BC_PIS
					// Para os CST_PIS = 53, 54, 55 e 56 (credito vinculado a mais de um tipo de receita): informar a parcela do campo QUANT_BC_PIS
					// vinculada ao tipo de credito escriturado em M100
					"QUANT_BC_PIS" => "",
					// Descricao do credito
					"DESC_CRED" => ""
				);
			}
		}
		return $registros;
	}

	// Ajustes do credito de PIS/PASEP apurado [nivel 3]
	protected function registro_M110($arr_itnotafiscal){
		$registros = array();
		if(is_array($arr_itnotafiscal)){
			$arr = array();
			foreach($arr_itnotafiscal as $itnotafiscal){
				$arr[$itnotafiscal["idnotafiscal"]] += $itnotafiscal["totalpis"];
			}
			foreach($arr as $idnotafiscal => $totalpis){
				$notafiscal = $this->arr_notafiscal[$idnotafiscal];
				$registros[] = array(
					// Texto fixo contendo "M110"
					"REG" => "M110",
					// Indicador do tipo de ajuste:
					// 0 - Ajuste de reducao
					// 1 - Ajuste de acrescimo
					"IND_AJ" => "0",
					// Valor do ajuste
					"VL_AJ" => $this->valor_decimal($totalpis, 2),
					// Codigo do ajuste, conforme a tabela indicada no item 4.3.8
					"COD_AJ" => "06",
					// Numero  processo, documento ou ato concessorio ao qual o ajuste esta vinculado, se houver
					"NUM_DOC" => $notafiscal["numnotafis"],
					// Descricao resumida do ajuste
					"DESCR_AJ" => "AJUSTE DE ESTORNO REFRENTE A DEVOLUÇÃO DE COMPRA",
					// Data de referencia do ajuste
					"DT_REF" => $this->valor_data($notafiscal["dtemissao"])
				);
			}
		}
		return $registros;
	}

	// Consolidacao da contribuicao para o PIS/PASEP do periodo [nivel 2]
	protected function registro_M200(){
		/*		 * **********************************************************************************************
		  ATENCAO: A maioria dos campos desse registro sao recalculados apos a geracao de todos os registros
		 * ********************************************************************************************** */
		$registros = array();

		$vl_tot_cred_desc = $this->m200_vl_tot_cred_desc;
		$vl_tot_cred_desc_ant = 0;
		$vl_tot_cont_nc_dev = $this->m200_vl_tot_cont_nc_per - $vl_tot_cred_desc - $vl_tot_cred_desc_ant;
		$vl_ret_nc = 0;
		$vl_out_ded_nc = 0;
		$vl_cont_nc_rec = $vl_tot_cont_nc_dev - $vl_ret_nc - $vl_out_ded_nc;
		$vl_tot_ret_cum = 0;
		$vl_out_ded_cum = 0;
		$vl_cont_cum_rec = $this->m200_vl_tot_cont_cum_per - $vl_ret_cum - $vl_out_ded_cum;
		$vl_tot_cont_rec = $vl_cont_nc_rec + $vl_cont_cum_rec;

		$this->m200_vl_tot_cont_nc_dev = $vl_tot_cont_nc_dev;

		$registros[] = array(
			// Texto fixo contendo "M200"
			"REG" => "M200",
			// Valor total da contribuicao nao cumulativa do periodo (recuperado do campo 13 do registro M210,
			// quando o campo COD_CONT = 01, 02, 03, 04, 32 e 71)
			"VL_TOT_CONT_NC_PER" => $this->valor_decimal($this->m200_vl_tot_cont_nc_per, 2),
			// Valor do credito descontado, apurado no proprio periodo da escrituracao (recuperado do campo 14 (VL_CRED_DESC) do registro M100)
			"VL_TOT_CRED_DESC" => $this->valor_decimal($vl_tot_cred_desc, 2),
			// Valor do credito descontado, apurado em periodo de apuracao anterior (recuperado do campo 13 do registro 1100)
			"VL_TOT_CRED_DESC_ANT" => $this->valor_decimal($vl_tot_cred_desc_ant, 2),
			// Valor total da contribuicao nao cumulativa devida (VL_TOT_CONT_NC_PER - VL_TOT_CRED_DESC - VL_TOT_CRED_DESC_ANT)
			"VL_TOT_CONT_NC_DEV" => $this->valor_decimal($vl_tot_cont_nc_dev, 2),
			// Valor retido na fonte deduzido no periodo
			"VL_RET_NC" => $this->valor_decimal($vl_ret_nc, 2),
			// Outras deducoes no periodo
			"VL_OUT_DED_NC" => $this->valor_decimal($vl_out_ded_nc, 2),
			// Valor da contribuicao nao cumulativa a recolher/pagar (VL_TOT_CONT_NC_DEV - VL_RET_NC - VL_OUT_DED_NC)
			"VL_CONT_NC_REC" => $this->valor_decimal($vl_cont_nc_rec, 2),
			// Valor total da contribuicao cumulativa do periodo (recuperado do campo 13 do registro M210, quando o campo COD_CONT = 31, 32, 51, 52, 53, 54 e 72)
			"VL_TOT_CONT_CUM_PER" => $this->valor_decimal($this->m200_vl_tot_cont_cum_per, 2),
			// Valor retido na fonte deduzido no periodo
			"VL_RET_CUM" => $this->valor_decimal($vl_ret_nc, 2),
			// Outras deducoes no periodo
			"VL_OUT_DED_CUM" => $this->valor_decimal($vl_out_ded_cum, 2),
			// Valor da contribuicao a recolher/pagar (VL_TOT_CONT_CUM_PER - VL_RET_CUM - VL_OUT_DED_CUM)
			"VL_CONT_CUM_REC" => $this->valor_decimal($vl_cont_cum_rec, 2),
			// Valor total da contribuicao a recoher/pagar no periodo (VL_CONT_NC_REC + VL_CONT_CUM_REC)
			"VL_TOT_CONT_REC" => $this->valor_decimal($vl_tot_cont_rec, 2)
		);
		if(compare_date(date("Y-m-d"), "2014-04-01", "Y-m-d", ">=")){
			$registros = array_merge($registros, $this->registro_M205());
		}
		$registros = array_merge($registros, $this->registro_M210($this->m200_arr_itcupom, $this->m200_arr_itnotafiscal, $this->m200_arr_itnotafiscalservico));
		return $registros;
	}

	// Contribuicao para o PIS/PASEP a recolher - detalhamento por codigo de receita [nivel 3]
	protected function registro_M205(){
		$registros = array();
		$registros[] = array(
			// Texto fixo contendo "M205"
			"REG" => "M205",
			// Informar o numero do campo do registro "M200" (campo 08 (contribuicao nao cumulativa) ou
			// campo 12 (contribuicao cumulativa)), objeto de detalhamento neste registro
			"NUM_CAMPO" => ($this->matriz->getregimetributario() == "3" ? "08" : "12"),
			// Informar o codigo da receita referente a contribuicao a recolher, detalhada neste registro
			"COD_REC" => ($this->matriz->getregimetributario() == "3" ? "691201" : "810902"),
			// Valor do debito correspondente ao codigo do campo 03, conforme informacao na DCTF
			"VL_DEBITO" => ""
		);
		return $registros;
	}

	// Detalhamento da contribuicao para o PIS/PASEP do periodo [nivel 3]
	protected function registro_M210($arr_itcupom, $arr_itnotafiscal, $arr_itnotafiscalservico){
		$registros = array();
		$arr_total = array();
		foreach($arr_itcupom as $itcupom){
			$codccs = $this->ccspiscofins($itcupom);
			$aliqpis = $this->valor_decimal($itcupom["aliqpis"], 4);
			$totalbruto = $itcupom["valortotal"];
			if(!in_array($codccs, array("01", "02", "31", "32", "51", "52"))){
				$totalbruto = 0;
			}
			$arr_total[$codccs][$aliqpis]["totalbruto"] += $totalbruto;
			$arr_total[$codccs][$aliqpis]["totalbasepis"] += $itcupom["totalbasepis"];
			//$arr_total[$codccs][$aliqpis]["totalpis"] += $itcupom["totalpis"];
			$arr_total[$codccs][$aliqpis]["totalpis"] += $itcupom["totalbasepis"] * $itcupom["aliqpis"] / 100;;
		}
		foreach($arr_itnotafiscal as $itnotafiscal){
			$notafiscal = $this->arr_notafiscal[$itnotafiscal["idnotafiscal"]];
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			if($operacaonota["tipo"] == "E"){
				continue;
			}
			$codccs = $this->ccspiscofins($itnotafiscal);
			$aliqpis = $this->valor_decimal($itnotafiscal["aliqpis"], 4);
			$totalbruto = $itnotafiscal["totalbruto"];
			if(!in_array($codccs, array("01", "02", "31", "32", "51", "52"))){
				$totalbruto = 0;
			}
			$arr_total[$codccs][$aliqpis]["totalbruto"] += $totalbruto;
			$arr_total[$codccs][$aliqpis]["totalbasepis"] += $itnotafiscal["totalbasepis"];
			$arr_total[$codccs][$aliqpis]["totalpis"] += $itnotafiscal["totalpis"];
		}
		foreach($arr_itnotafiscalservico as $itnotafiscalservico){
			$notafiscalservico = $this->arr_notafiscalservico[$itnotafiscalservico["idnotafiscalservico"]];
			if($notafiscalservico["indicadoroperacao"] == "0"){
				continue;
			}
			$codccs = $this->ccspiscofins($itnotafiscalservico);
			$aliqpis = $this->valor_decimal($itnotafiscalservico["aliquotapis"], 4);
			$totalbruto = $itnotafiscalservico["valortotal"];
			if(!in_array($codccs, array("01", "02", "31", "32", "51", "52"))){
				$totalbruto = 0;
			}
			$arr_total[$codccs][$aliqpis]["totalbruto"] += $totalbruto;
			$arr_total[$codccs][$aliqpis]["totalbasepis"] += $itnotafiscalservico["basecalculopis"];
			$arr_total[$codccs][$aliqpis]["totalpis"] += $itnotafiscalservico["valorpis"];
		}
		foreach($arr_total as $codccs => $total_codccs){
			foreach($total_codccs as $aliqpis => $total){
				$vl_rec_brt = $total["totalbruto"];
				$vl_bc_cont = $total["totalbasepis"];
				$vl_ajus_acres_bc_pis = 0;
				$vl_ajus_reduc_bc_pis = 0;
				$vl_bc_cont_ajus = $vl_bc_cont + $vl_ajus_acres_bc_pis - $vl_ajus_reduc_bc_pis;
				$vl_cont_apur = $total["totalpis"];
				$vl_ajus_acres = 0;
				$vl_ajus_reduc = 0;
				$vl_cont_difer = 0;
				$vl_cont_difer_ant = 0;
				$vl_cont_per = $vl_cont_apur + $vl_ajus_acres - $vl_ajus_reduc - $vl_cont_difer + $vl_cont_difer_ant;

				$registro = array(
					// Texto fixo contendo "M210"
					"REG" => "M210",
					// Codigo da contribuicao social apurada no periodo, conforme a tabela 4.3.5
					"COD_CONT" => $codccs,
					// Valor da receita bruto
					"VL_REC_BRT" => $this->valor_decimal($vl_rec_brt, 2),
					// Valor da base de calculo da contribuicao
					"VL_BC_CONT" => $this->valor_decimal($vl_bc_cont, 2)
				);

				if(compare_date($this->datainicial, "2019-01-01", "Y-m-d", ">=")){
					$registro = array_merge($registro, array(
						// Valor do total dos ajustes de acrescimo da base de calculo da contribuicao a que se refere o VL_BC_CONT
						"VL_AJUS_ACRES_BC_PIS" => $this->valor_decimal($vl_ajus_acres_bc_pis,2),
						// Valor do total dos ajustes de reducao da base de calculo da contribuicao a que se refere o VL_BC_CONT
						"VL_AJUS_REDUC_BC_PIS" => $this->valor_decimal($vl_ajus_reduc_bc_pis,2),
						// Valor da base de calculo da contribuição, apos os ajustes (VL_BC_CONT_AJUS = VL_BC_CONT + VL_AJUS_ACRES_BC_PIS - VL_AJUS_REDUC_BC_PIS)
						"VL_BC_CONT_AJUS" => $this->valor_decimal($vl_bc_cont_ajus, 2)
					));
				}

				$registro = array_merge($registro, array(
					// Aliquota do PIS/PASEP (em percentual)
					"ALIQ_PIS" => $aliqpis,
					// Quantidade - Base de calculo
					"QUANT_BC_PIS" => "",
					// Aliquota do PIS (em reais)
					"ALIQ_PIS_QUANT" => "",
					// Valor total da contribuicao social apurada
					"VL_CONT_APUR" => $this->valor_decimal($vl_cont_apur, 2),
					// Valor total dos ajustes de acrescimo
					"VL_AJUS_ACRES" => $this->valor_decimal($vl_ajus_acres, 2),
					// Valor total dos ajustes de reducao
					"VL_AJUS_REDUC" => $this->valor_decimal($vl_ajus_reduc, 2),
					// Valor da contribuicao a diferir no periodo
					"VL_CONT_DIFER" => $this->valor_decimal($vl_cont_difer, 2),
					// Valor da contribuicao diferida em periodos anteriores
					"VL_CONT_DIFER_ANT" => $this->valor_decimal($vl_cont_difer_ant, 2),
					// Valor total da contribuicao do periodo (VL_CONT_APUR + VL_AJUS_ACRES - VL_AJUS_REDUC - VL_CONT_DIFER + VL_CONT_DIFER_ANT)
					"VL_CONT_PER" => $this->valor_decimal($vl_cont_per, 2)
				));

				$registros[] = $registro;

				$registros = array_merge($registros, $this->registro_M211());
				$registros = array_merge($registros, $this->registro_M220());
				$registros = array_merge($registros, $this->registro_M230());
			}
		}
		return $registros;
	}

	// Sociedade cooperativas - composicao da base de calculo - PIS/PASEP [nivel 4] [NAO GERAR]
	protected function registro_M211(){
		return array();
	}

	// Ajustes da contribuicao para o PIS/PASEP apurada [nivel 4] [NAO GERAR]
	protected function registro_M220(){
		return array();
	}

	// Informacoes adicionais de diferimento [nivel 4] [NAO GERAR]
	protected function registro_M230(){
		return array();
	}

	// Contribuicao de PIS/PASEP diferida em periodos anteriores - valores a pagar no periodo [nivel 2] [NAO GERAR]
	protected function registro_M300(){
		return array();
	}

	// PIS/PASEP - folha de salario [nivel 2] [NAO GERAR]
	protected function registro_M350(){
		return array();
	}

	// Receitas isentas, nao alcancadas pela incidencia da contribuicao, sujeitas a aliquota zero ou de vendas com suspensao - PIS/PASEP [nivel 2]
	protected function registro_M400(){
		$registros = array();
		$arr_cst = array();
		$arr_codmaparesumo = array("SAT");
		foreach($this->arr_maparesumo as $maparesumo){
			$arr_codmaparesumo[] = $maparesumo["codmaparesumo"];
		}
		foreach($arr_codmaparesumo as $codmaparesumo){
			if(is_array($this->arr_itcupom[$codmaparesumo])){
				foreach($this->arr_itcupom[$codmaparesumo] as $itcupom){
					$codcst = $this->cstpiscofins($itcupom);
					if(in_array($codcst, array("04", "05", "06", "07", "08", "09"))){
						$natoperacao = $this->arr_natoperacao[$itcupom["natoperacaonfcupom"]];
						$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];
						$contacontabil = $planocontas["contacontabil"];

						$natreceita = $this->natreceita($itcupom);
						$totalbruto = number_format($itcupom["valortotal"], 2, ".", "");
						$arr_cst[$codcst][$contacontabil]["totalbruto"] += $totalbruto;
						$arr_cst[$codcst][$contacontabil]["natreceita"][$natreceita] += $totalbruto;						
					}
				}
			}
		}
		foreach($this->arr_notafiscal as $notafiscal){
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$codcst = $this->cstpiscofins($itnotafiscal);
				if(in_array($codcst, array("04", "05", "06", "07", "08", "09"))){
					$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
					$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];
					$contacontabil = $planocontas["contacontabil"];

					$natreceita = $this->natreceita($itnotafiscal);
					$totalbruto = number_format($itnotafiscal["totalbruto"], 2, ".", "");
					$arr_cst[$codcst][$contacontabil]["totalbruto"] += $totalbruto;
					$arr_cst[$codcst][$contacontabil]["natreceita"][$natreceita] += $totalbruto;					
				}
			}
		}
		foreach($arr_cst as $cst_i => $cst_v){
			foreach($cst_v as $contacontabil => $result){				
				$registros[] = array(
					// Texto fixo contendo "M400"
					"REG" => "M400",
					// Codigo de situacao tributaria - CST das demais receitas auferidas no periodo, sem incidencia da contribuicao,
					// ou sem contribuicao apurada a pagar, conforme a tabela 4.3.3
					"CST_PIS" => $cst_i,
					// Valor total da receita bruta no periodo
					"VL_TOT_REC" => $this->valor_decimal($result["totalbruto"], 2),
					// Codigo da conta analitica contabil debitada/creditada
					"COD_CTA" => $contacontabil,
					// Descricao complementar da natureza da receita
					"DESC_COMPL" => ""
				);
				$registros = array_merge($registros, $this->registro_M410($cst_v[$contacontabil]["natreceita"], $contacontabil));	
			}
		}
		return $registros;
	}

	// Detalhamento das receitas isentas, nao alcancadas pela incidencia da contribuicao, sujeitas a aliquota zero ou de vendas com suspensao - PIS/PASEP [nivel 3]
	protected function registro_M410($arr_natreceita, $contacontabil){
		$registros = array();
		foreach($arr_natreceita as $natreceita => $totalbruto){
			$registros[] = array(
				// Texto fixo contendo "M410"
				"REG" => "M410",
				// Natureza da receita, conforme relacao constante nas tabelas de detalhamento da natureza da receita por situacao tributaria abaixo:
				// Tabela 4.3.10: Produtos sujeitos a incidencia monofasica da contribuicao social - aliquotas diferenciadas (CST 04 - revenda)
				// Tabela 4.3.11: Produtos sujeitos a incidencia monofasica da contribuicao social - aliquotas por unidade de medida do produto (CST 04 - revenda)
				// Tabela 4.3.12: Produtos sujeitos a substituicao tributaria da contribuicao social (CST 05 - revenda)
				// Tabela 4.3.13: Produtos sujeitos a aliquota zero da contribuicao social (CST 06)
				// Tabela 4.3.14: Operacoes com isencao da contribuicao social (CST 07)
				// Tabela 4.3.15: Operacoes sim incidencia da contribuicao social (CST 08)
				// Tabela 4.3.16: Operacoes com suspencao da contribuicao social (CST 09)
				"NAT_REC" => $natreceita,
				// Valor da receita bruta no periodo, relativo a natureza da receita (NAT_REC)
				"VL_REC" => $this->valor_decimal($totalbruto, 2),
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => $contacontabil,
				// Descricao complementar da natureza da receita
				"DESC_COMPL" => ""
			);
		}
		return $registros;
	}

	// Credito de COFINS relativo ao periodo [nivel 2]
	protected function registro_M500(){
		$registros = array();
		$arr_itnotafiscal = array();
		$arr_itnotafiscal_reduc = array();
		$arr_notafrete = array();
		$arr_notadiversa = array();
		$arr_total = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			if($operacaonota["tipo"] == "E" && $notafiscal["totalcofins"] > 0){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$tipocred = $this->tipocredito($itnotafiscal);
					$aliqcofins = $this->valor_decimal($itnotafiscal["aliqcofins"], 4);
					$arr_itnotafiscal[$tipocred][$aliqcofins][] = $itnotafiscal;
					$arr_total[$tipocred][$aliqcofins]["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
					$arr_total[$tipocred][$aliqcofins]["totalcofins"] += $itnotafiscal["totalcofins"];
				}
			}elseif($operacaonota["operacao"] == "DF"){
				foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
					$tipocred = $this->tipocredito($itnotafiscal);
					$aliqcofins = $this->valor_decimal($itnotafiscal["aliqcofins"], 4);
					$arr_itnotafiscal_reduc[$tipocred][$aliqcofins][] = $itnotafiscal;
					$arr_total[$tipocred][$aliqcofins]["totalcofins_reduc"] += $itnotafiscal["totalcofins"];
				}
			}
		}
		foreach($this->arr_notafrete as $notafrete){
			$tipocred = "101";
			$aliqcofins = $this->valor_decimal($notafrete["aliqcofins"], 4);
			$arr_notafrete[$tipocred][$aliqcofins][] = $notafrete;
			$arr_total[$tipocred][$aliqcofins]["totalbasecofins"] += $notafrete["totalbasecofins"];
			$arr_total[$tipocred][$aliqcofins]["totalcofins"] += $notafrete["totalcofins"];
		}
		foreach($this->arr_notadiversa as $notadiversa){
			// Nota fiscal de energia eletrica
			if($this->valor_natoperacao($notadiversa["natoperacao"]) == "1253"){
				$tipocred = "101";
				$aliqcofins = $this->valor_decimal($notadiversa["aliqcofins"], 4);
				$arr_notadiversa[$tipocred][$aliqcofins][] = $notadiversa;
				$arr_total[$tipocred][$aliqcofins]["totalbasecofins"] += $notadiversa["basecofins"];
				$arr_total[$tipocred][$aliqcofins]["totalcofins"] += $notadiversa["totalcofins"];
			}
		}
		foreach($this->arr_notafiscalservico as $notafiscalservico){
			if($notafiscalservico["indicadoroperacao"] == "1"){
				continue;
			}
			foreach($notafiscalservico["itnotafiscalservico"] as $itnotafiscalservico){
				$tipocred = "101";
				$aliqcofins = $this->valor_decimal($itnotafiscalservico["aliquotacofins"], 4);
				$arr_itnotafiscalservico[$tipocred][$aliqcofins][] = $itnotafiscalservico;
				$arr_total[$tipocred][$aliqcofins]["totalbasecofins"] += $itnotafiscalservico["basecalculocofins"];
				$arr_total[$tipocred][$aliqcofins]["totalcofins"] += $itnotafiscalservico["valorcofins"];
			}
		}

		$sum_vl_cred = 0;
		foreach($arr_total as $tipocred => $total_tipocred){
			foreach($total_tipocred as $aliqcofins => $total){
				$sum_vl_cred += $total["totalcofins"];
			}
		}

		foreach($arr_total as $tipocred => $total_tipocred){
			foreach($total_tipocred as $aliqcofins => $total){

				$vl_cred = $total["totalcofins"];
				$vl_ajus_acres = 0;
				$vl_ajus_reduc = $total["totalcofins_reduc"];
				$vl_cred_dif = 0;
				$vl_cred_desc = ($this->m600_vl_tot_cont_nc_per <= $sum_vl_cred ? $this->m600_vl_tot_cont_nc_per : $vl_cred);
				$vl_cred_disp = $vl_cred + $vl_ajus_acres - $vl_ajus_reduc - $vl_cred_dif;
				$vl_cred_desc = $total["totalcofins"] - $vl_ajus_reduc;
				$sld_cred = $vl_cred_disp - $vl_cred_desc;
				$ind_desc_cred = ($this->m600_vl_tot_cont_nc_per >= $this->m600_vl_tot_cred_desc ? "0" : "1");

				if($vl_cred == 0){
					continue;
				}

				$this->m600_vl_tot_cred_desc += $vl_cred_desc;

				$registros[] = array(
					// Texto fixo contendo "M500"
					"REG" => "M500",
					// Codigo de Tipo de Credito apurado no periodo, conforme a tabela 4.3.6
					"COD_CRED" => $tipocred,
					// Indicador de credito oriundo de:
					// 0 - Operacoes proprias
					// 1 - Evento de incorporacao, cisao ou fusao
					"IND_CRED_ORI" => "0",
					// Valor da base de calculo do credito
					"VL_BC_COFINS" => $this->valor_decimal($total["totalbasecofins"], 2),
					// Aliquota do COFINS (em percentual)
					"ALIQ_COFINS" => $aliqcofins,
					// Quantidade - base de calculo COFINS
					"QUANT_BC_COFINS" => $this->valor_decimal(0, 2),
					// Aliquota do COFINS (em reais)
					"ALIQ_COFINS_QUANT" => "",
					// Valor total de credito apurado no periodo
					"VL_CRED" => $this->valor_decimal($vl_cred, 2),
					// Valor total dos ajustes de acrescimo
					"VL_AJUS_ACRES" => $this->valor_decimal($vl_ajus_acres, 2),
					// Valor total dos ajustes de reducao
					"VL_AJUS_REDUC" => $this->valor_decimal($vl_ajus_reduc, 2),
					// Valor total do credito diferido no periodo
					"VL_CRED_DIFER" => $this->valor_decimal($vl_cred_difer, 2),
					// Valor total do credito disponivel relativo ao periodo (VL_CRED + VL_AJUS_ACRES - VL_AJUS_REDUC - VL_CRED_DIFER)
					"VL_CRED_DISP" => $this->valor_decimal($vl_cred_disp, 2),
					// Indicador de opcao de utilizacao do credito disponivel no periodo:
					// 0 - Utilizacao do valor total para desconto da contribuicao apurada no periodo, no registro M200
					// 1 - Utilizacao do valor parcial para desconto da contribuicao apurada no periodo, no registro M200
					"IND_DESC_CRED" => $ind_desc_cred,
					// Valor do credito disponivel, descontado da contribuicao apurada no proprio periodo
					// Se IND_DESC_CRED = 0, informar o valor total do campo VL_CRED_DISP
					// Se IND_DESC_CRED = 1, informar o valor parcial do campo VL_CRED_DISP
					"VL_CRED_DESC" => $this->valor_decimal($vl_cred_desc, 2),
					// Saldo de creditos a utilizar em periodos futuros (VL_CRED_DISP - VL_CRED_DESC)
					"SLD_CRED" => $this->valor_decimal($sld_cred, 2)
				);
				$registros = array_merge($registros, $this->registro_M505($arr_itnotafiscal[$tipocred][$aliqcofins], $arr_notafrete[$tipocred][$aliqcofins], $arr_notadiversa[$tipocred][$aliqcofins], $arr_itnotafiscalservico[$tipocred][$aliqcofins]));
				$registros = array_merge($registros, $this->registro_M510($arr_itnotafiscal_reduc[$tipocred][$aliqcofins]));
			}
		}
		return $registros;
	}

	// Detalhamento da base de calculo de credito apurado no periodo - COFINS [nivel 3]
	protected function registro_M505($arr_itnotafiscal, $arr_notafrete, $arr_notadiversa, $arr_itnotafiscalservico){
		$registros = array();
		$arr_total = array();
		if(is_array($arr_itnotafiscal)){
			foreach($arr_itnotafiscal as $itnotafiscal){
				$natcred = $this->natcredito($itnotafiscal);
				$codcst = $this->cstpiscofins($itnotafiscal);
				$arr_total[$natcred][$codcst]["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
				$arr_total[$natcred][$codcst]["totalcofins"] += $itnotafiscal["totalcofins"];
			}
		}
		if(is_array($arr_notafrete)){
			foreach($arr_notafrete as $notafrete){
				$natcred = "14";
				$codcst = "50";
				$arr_total[$natcred][$codcst]["totalbasecofins"] += $notafrete["totalbasecofins"];
				$arr_total[$natcred][$codcst]["totalcofins"] += $notafrete["totalcofins"];
			}
		}
		if(is_array($arr_notadiversa)){
			foreach($arr_notadiversa as $notadiversa){
				$natcred = "04";
				$codcst = "50";
				$arr_total[$natcred][$codcst]["totalbasecofins"] += $notadiversa["basecofins"];
				$arr_total[$natcred][$codcst]["totalcofins"] += $notadiversa["totalcofins"];
			}
		}
		if(is_array($arr_itnotafiscalservico)){
			foreach($arr_itnotafiscalservico as $itnotafiscalservico){
				$natcred = $itnotafiscalservico["natbasecalculocredito"];
				$codcst = $itnotafiscalservico["cstcofins"];
				$arr_total[$natcred][$codcst]["totalbasecofins"] += $itnotafiscalservico["basecalculocofins"];
				$arr_total[$natcred][$codcst]["totalcofins"] += $itnotafiscalservico["valorcofins"];
			}
		}

		foreach($arr_total as $natcred => $arr_codcst){
			foreach($arr_codcst as $codcst => $total){
				$vl_bc_cofins_tot = $total["totalbasecofins"];
				$vl_bc_cofins_cum = 0;
				$vl_bc_cofins_nc = $vl_bc_cofins_tot - $vl_bc_cofins_cum;
				if(in_array($codcst, array("50", "51", "52", "60", "61", "62"))){
					$vl_bc_cofins = $vl_bc_cofins_nc;
				}elseif(in_array($codcst, array("53", "54", "55", "56", "63", "64"))){
					$vl_bc_cofins = $vl_bc_cofins_nc;
				}else{
					$vl_bc_cofins = 0;
				}

				if($vl_bc_cofins_tot == 0){
					continue;
				}

				$registros[] = array(
					// Texto fixo contendo "M505"
					"REG" => "M505",
					// Codigo da base de calculo do credito apurado no periodo, conforme a tabela 4.3.7
					"NAT_BC_CRED" => $natcred,
					// Codigo da situacao tributaria referente ao credito de PIS/PASEP (tabela 4.3.3)
					// vindulado ao tipo de credito escriturado em M500
					"CST_COFINS" => $codcst,
					// Valor total da base de calculo escriturada nos documentos e operacoes (blocos A, C, D e F),
					// referente ao CST_PIS informado no campo CST_PIS
					"VL_BC_COFINS_TOT" => $this->valor_decimal($vl_bc_cofins_tot, 2),
					// Parcela do valor total da base de calculo informada no campo VL_BC_COFINS_TOT,
					// vinculada a receitas com incidencia cumulativa
					// Campo de preenchimento especifico para pessoa juridica sujeita ao regime cumulativo e
					// nao-cumulativo da contribuicao (COD_INC_TRIB = 3 do registro 0110)
					"VL_BC_COFINS_CUM" => $this->valor_decimal($vl_bc_cofins_cum, 2),
					// Valor total da base de calculo do credito, vinculada a receitas com incidencia nao-cumulativa (VL_BC_COFINS_TOT - VL_BC_COFINS_CUM)
					"VL_BC_COFINS_NC" => $this->valor_decimal($vl_bc_cofins_nc, 2),
					// Valor da base de calculo do credito, vinculada ao tipo de credito escriturado em M100
					// Para os CST_COFINS = 50, 51, 52, 60, 61 e 62: informar o valor do campo VL_BC_COFINS_NC
					// Para os CST_COFINS = 53, 54, 55, 56, 63, 64, 65 e 66 (credito sobre operacoes vinculadas a mais de um tipo de recieta): informar
					// a parcela do valor do campo VL_BC_COFINS_NC vinculada especificamente ao tipo de credito escriturado em M500
					// -> O valor desse campo sera transportado para o campo VL_BC_COFINS do registro M500
					"VL_BC_COFINS" => $this->valor_decimal($vl_bc_cofins, 2),
					// Quantidade total da base de calculo do credito apurado em unidade de medida de produto, escriturada nos ducumentos e operacoes
					// (bloco A, C, D e F), referente ao campo CST_COFINS
					"QUANT_BC_COFINS_TOT" => "",
					// Parcela da base de calculo do credito em quantidade (campo QUANT_BC_COFINS_TOT) vinculada ao tipo de credito escriturado em M500
					// Para os CST_COFINS = 50, 51 e 52: informar o valor do campo QUANT_BC_COFINS
					// Para os CST_COFINS = 53, 54, 55 e 56 (credito vinculado a mais de um tipo de receita): informar a parcela do campo QUANT_BC_COFINS
					// vinculada ao tipo de credito escriturado em M500
					"QUANT_BC_COFINS" => $this->valor_decimal(0, 2),
					// Descricao do credito
					"DESC_CRED" => ""
				);
			}
		}
		return $registros;
	}

	// Ajustes do credito de COFINS apurado [nivel 3]
	protected function registro_M510($arr_itnotafiscal){
		$registros = array();
		if(is_array($arr_itnotafiscal)){
			$arr = array();
			foreach($arr_itnotafiscal as $itnotafiscal){
				$arr[$itnotafiscal["idnotafiscal"]] += $itnotafiscal["totalcofins"];
			}
			foreach($arr as $idnotafiscal => $totalcofins){
				$notafiscal = $this->arr_notafiscal[$idnotafiscal];
				$registros[] = array(
					// Texto fixo contendo "M510"
					"REG" => "M510",
					// Indicador do tipo de ajuste:
					// 0 - Ajuste de reducao
					// 1 - Ajuste de acrescimo
					"IND_AJ" => "0",
					// Valor do ajuste
					"VL_AJ" => $this->valor_decimal($totalcofins, 2),
					// Codigo do ajuste, conforme a tabela indicada no item 4.3.8
					"COD_AJ" => "06",
					// Numero  processo, documento ou ato concessorio ao qual o ajuste esta vinculado, se houver
					"NUM_DOC" => $notafiscal["numnotafis"],
					// Descricao resumida do ajuste
					"DESCR_AJ" => "AJUSTE DE ESTORNO REFRENTE A DEVOLUÇÃO DE COMPRA",
					// Data de referencia do ajuste
					"DT_REF" => $this->valor_data($notafiscal["dtemissao"])
				);
			}
		}
		return $registros;
	}

	// Consolidacao da contribuicao para o COFINS do periodo [nivel 2]
	protected function registro_M600(){
		/*		 * ***********************************************************************************************
		  ATENCAO: A maiorias dos campos desse registro sao recalculados apos a geracao de todos os registros
		 * *********************************************************************************************** */
		$registros = array();

		$vl_tot_cred_desc = $this->m600_vl_tot_cred_desc;
		$vl_tot_cred_desc_ant = 0;
		$vl_tot_cont_nc_dev = $this->m600_vl_tot_cont_nc_per - $vl_tot_cred_desc - $vl_tot_cred_desc_ant;
		if($vl_tot_cont_nc_dev < 0){
			$vl_tot_cont_nc_dev = 0;
		}
		$vl_ret_nc = 0;
		$vl_out_ded_nc = 0;
		$vl_cont_nc_rec = $vl_tot_cont_nc_dev - $vl_ret_nc - $vl_out_ded_nc;
		$vl_tot_ret_cum = 0;
		$vl_out_ded_cum = 0;
		$vl_cont_cum_rec = $this->m600_vl_tot_cont_cum_per - $vl_ret_cum - $vl_out_ded_cum;
		$vl_tot_cont_rec = $vl_cont_nc_rec + $vl_cont_cum_rec;

		$this->m600_vl_tot_cont_nc_dev = $vl_tot_cont_nc_dev;

		$registros[] = array(
			// Texto fixo contendo "M600"
			"REG" => "M600",
			// Valor total da contribuicao nao cumulativa do periodo (recuperado do campo 13 do registro M610,
			// quando o campo COD_CONT = 01, 02, 03, 04, 32 e 71)
			"VL_TOT_CONT_NC_PER" => $this->valor_decimal($this->m600_vl_tot_cont_nc_per, 2),
			// Valor do credito descontado, apurado no proprio periodo da escrituracao (recuperado do campo 14 (VL_CRED_DESC) do registro M500)
			"VL_TOT_CRED_DESC" => $this->valor_decimal($vl_tot_cred_desc, 2),
			// Valor do credito descontado, apurado em periodo de apuracao anterior (recuperado do campo 13 do registro 1500)
			"VL_TOT_CRED_DESC_ANT" => $this->valor_decimal($vl_tot_cred_desc_ant, 2),
			// Valor total da contribuicao nao cumulativa devida (VL_TOT_CONT_NC_PER - VL_TOT_CRED_DESC - VL_TOT_CRED_DESC_ANT)
			"VL_TOT_CONT_NC_DEV" => $this->valor_decimal($vl_tot_cont_nc_dev, 2),
			// Valor retido na fonte deduzido no periodo
			"VL_RET_NC" => $this->valor_decimal($vl_ret_nc, 2),
			// Outras deducoes no periodo
			"VL_OUT_DED_NC" => $this->valor_decimal($vl_out_ded_nc, 2),
			// Valor da contribuicao nao cumulativa a recolher/pagar (VL_TOT_CONT_NC_DEV - VL_RET_NC - VL_OUT_DED_NC)
			"VL_CONT_NC_REC" => $this->valor_decimal($vl_cont_nc_rec, 2),
			// Valor total da contribuicao cumulativa do periodo (recuperado do campo 13 do registro M610, quando o campo COD_CONT = 31, 32, 51, 52, 53, 54 e 72)
			"VL_TOT_CONT_CUM_PER" => $this->valor_decimal($this->m600_vl_tot_cont_cum_per, 2),
			// Valor retido na fonte deduzido no periodo
			"VL_RET_CUM" => $this->valor_decimal($vl_ret_nc, 2),
			// Outras deducoes no periodo
			"VL_OUT_DED_CUM" => $this->valor_decimal($vl_out_ded_cum, 2),
			// Valor da contribuicao a recolher/pagar (VL_TOT_CONT_CUM_PER - VL_RET_CUM - VL_OUT_DED_CUM)
			"VL_CONT_CUM_REC" => $this->valor_decimal($vl_cont_cum_rec, 2),
			// Valor total da contribuicao a recoher/pagar no periodo (VL_CONT_NC_REC + VL_CONT_CUM_REC)
			"VL_TOT_CONT_REC" => $this->valor_decimal($vl_tot_cont_rec, 2)
		);
		if(compare_date(date("Y-m-d"), "2014-04-01", "Y-m-d", ">=")){
			$registros = array_merge($registros, $this->registro_M605());
		}
		$registros = array_merge($registros, $this->registro_M610($this->m200_arr_itcupom, $this->m200_arr_itnotafiscal));
		return $registros;
	}

	// Contribuicao para o COFINS a recolher - detalhamento por codigo de receita
	protected function registro_M605(){
		$registros = array();
		$registros[] = array(
			// Texto fixo contendo "M605"
			"REG" => "M605",
			// Informar o numero do campo do registro "M600" (campo 08 (contribuicao nao cumulativa) ou
			// campo 12 (contribuicao cumulativa)), objeto de detalhamento neste registro
			"NUM_CAMPO" => ($this->matriz->getregimetributario() == "3" ? "08" : "12"),
			// Informar o codigo da receita referente a contribuicao a recolher, detalhada neste registro
			"COD_REC" => ($this->matriz->getregimetributario() == "3" ? "585601" : "217201"),
			// Valor do debito correspondente ao codigo do campo 03, conforme informacao na DCTF
			"VL_DEBITO" => ""
		);
		return $registros;
	}

	// Detalhamento da contribuicao para o COFINS do periodo [nivel 3]
	protected function registro_M610($arr_itcupom, $arr_itnotafiscal){
		$registros = array();
		$arr_total = array();
		foreach($arr_itcupom as $itcupom){
			$codccs = $this->ccspiscofins($itcupom);
			$aliqcofins = $this->valor_decimal($itcupom["aliqcofins"], 4);
			$arr_total[$codccs][$aliqcofins]["totalbruto"] += $itcupom["valortotal"];
			$arr_total[$codccs][$aliqcofins]["totalbasecofins"] += $itcupom["totalbasecofins"];
			//$arr_total[$codccs][$aliqcofins]["totalcofins"] += $itcupom["totalcofins"];
			$arr_total[$codccs][$aliqcofins]["totalcofins"] += $itcupom["totalbasecofins"] * $itcupom["aliqcofins"] / 100;
		}
		foreach($arr_itnotafiscal as $itnotafiscal){
			$notafiscal = $this->arr_notafiscal[$itnotafiscal["idnotafiscal"]];
			$operacaonota = $this->arr_operacaonota[$notafiscal["operacao"]];
			if($operacaonota["tipo"] == "E"){
				continue;
			}
			$codccs = $this->ccspiscofins($itnotafiscal);
			$aliqcofins = $this->valor_decimal($itnotafiscal["aliqcofins"], 4);
			$arr_total[$codccs][$aliqcofins]["totalbruto"] += $itnotafiscal["totalbruto"];
			$arr_total[$codccs][$aliqcofins]["totalbasecofins"] += $itnotafiscal["totalbasecofins"];
			$arr_total[$codccs][$aliqcofins]["totalcofins"] += $itnotafiscal["totalcofins"];
		}
		foreach($this->arr_notafiscalservico as $notafiscalservico){
			if($notafiscalservico["indicadoroperacao"] == "0"){
				continue;
			}
			foreach($notafiscalservico["itnotafiscalservico"] as $itnotafiscalservico){
				$codccs = $this->ccspiscofins($itnotafiscalservico);
				$aliqcofins = $this->valor_decimal($itnotafiscalservico["aliquotacofins"], 4);
				$totalbruto = $itnotafiscalservico["valortotal"];
				if(!in_array($codccs, array("01", "02", "31", "32", "51", "52"))){
					$totalbruto = 0;
				}
				$arr_total[$codccs][$aliqcofins]["totalbruto"] += $totalbruto;
				$arr_total[$codccs][$aliqcofins]["totalbasecofins"] += $itnotafiscalservico["basecalculocofins"];
				$arr_total[$codccs][$aliqcofins]["totalcofins"] += $itnotafiscalservico["valorcofins"];
			}
		}
		foreach($arr_total as $codccs => $total_codccs){
			foreach($total_codccs as $aliqcofins => $total){
				$vl_rec_brt = $total["totalbruto"];
				$vl_bc_cont = $total["totalbasecofins"];
				$vl_ajus_acres_bc_cofins = 0;
				$vl_ajus_reduc_bc_cofins = 0;
				$vl_bc_cont_ajus = $vl_bc_cont + $vl_ajus_acres_bc_cofins - $vl_ajus_reduc_bc_cofins;
				$vl_cont_apur = $total["totalcofins"];
				$vl_ajus_acres = 0;
				$vl_ajus_reduc = 0;
				$vl_cont_difer = 0;
				$vl_cont_difer_ant = 0;
				$vl_cont_per = $vl_cont_apur + $vl_ajus_acres - $vl_ajus_reduc - $vl_cont_difer + $vl_cont_difer_ant;

				$registro = array(
					// Texto fixo contendo "M610"
					"REG" => "M610",
					// Codigo da contribuicao social apurada no periodo, conforme a tabela 4.3.5
					"COD_CONT" => $codccs,
					// Valor da receita bruto
					"VL_REC_BRT" => $this->valor_decimal($vl_rec_brt, 2),
					// Valor da base de calculo da contribuicao
					"VL_BC_CONT" => $this->valor_decimal($vl_bc_cont, 2)
				);

				if(compare_date($this->datainicial, "2019-01-01", "Y-m-d", ">=")){
					$registro = array_merge($registro, array(
						// Valor do total dos ajustes de acrescimo da base de calculo da contribuicao a que se refere o VL_BC_CONT
						"VL_AJUS_ACRES_BC_COFINS" => $this->valor_decimal($vl_ajus_acres_bc_cofins, 2),
						// Valor do total dos ajustes de reducao da base de calculo da contribuicao a que se refere o VL_BC_CONT
						"VL_AJUS_REDUC_BC_COFINS" => $this->valor_decimal($vl_ajus_reduc_bc_cofins, 2),
						// Valor da base de calculo da contribuição, apos os ajustes (VL_BC_CONT_AJUS = VL_BC_CONT + VL_AJUS_ACRES_BC_PIS - VL_AJUS_REDUC_BC_PIS)
						"VL_BC_CONT_AJUS" => $this->valor_decimal($vl_bc_cont_ajus,2)
					));
				}

				$registro = array_merge($registro, array(
					// Aliquota do COFINS (em percentual)
					"ALIQ_COFINS" => $aliqcofins,
					// Quantidade - Base de calculo
					"QUANT_BC_COFINS" => "",
					// Aliquota do COFINS (em reais)
					"ALIQ_COFINS_QUANT" => "",
					// Valor total da contribuicao social apurada
					"VL_CONT_APUR" => $this->valor_decimal($vl_cont_apur, 2),
					// Valor total dos ajustes de acrescimo
					"VL_AJUS_ACRES" => $this->valor_decimal($vl_ajus_acres, 2),
					// Valor total dos ajustes de reducao
					"VL_AJUS_REDUC" => $this->valor_decimal($vl_ajus_reduc, 2),
					// Valor da contribuicao a diferir no periodo
					"VL_CONT_DIFER" => $this->valor_decimal($vl_cont_difer, 2),
					// Valor da contribuicao diferida em periodos anteriores
					"VL_CONT_DIFER_ANT" => $this->valor_decimal($vl_cont_difer_ant, 2),
					// Valor total da contribuicao do periodo (VL_CONT_APUR + VL_AJUS_ACRES - VL_AJUS_REDUC - VL_CONT_DIFER + VL_CONT_DIFER_ANT)
					"VL_CONT_PER" => $this->valor_decimal($vl_cont_per, 2)
				));

				$registros[] = $registro;

				$registros = array_merge($registros, $this->registro_M611());
				$registros = array_merge($registros, $this->registro_M620());
				$registros = array_merge($registros, $this->registro_M630());
			}
		}
		return $registros;
	}

	// Sociedade cooperativas - composicao da base de calculo - COFINS [nivel 4] [NAO GERAR]
	protected function registro_M611(){
		return array();
	}

	// Ajustes da contribuicao para o COFINS apurada [nivel 4] [NAO GERAR]
	protected function registro_M620(){
		return array();
	}

	// Informacoes adicionais de diferimento [nivel 4] [NAO GERAR]
	protected function registro_M630(){
		return array();
	}

	// Contribuicao de COFINS diferida em periodos anteriores - valores a pagar no periodo [nivel 2] [NAO GERAR]
	protected function registro_M700(){
		return array();
	}

	// COFINS - folha de salario [nivel 2] [NAO GERAR]
	protected function registro_M750(){
		return array();
	}

	// Receitas isentas, nao alcancadas pela incidencia da contribuicao, sujeitas a aliquota zero ou de vendas com suspensao - COFINS [nivel 2]
	protected function registro_M800(){
		$registros = array();
		$arr_cst = array();
		$arr_codmaparesumo = array("SAT");
		foreach($this->arr_maparesumo as $maparesumo){
			$arr_codmaparesumo[] = $maparesumo["codmaparesumo"];
		}
		foreach($arr_codmaparesumo as $codmaparesumo){
			if(is_array($this->arr_itcupom[$codmaparesumo])){
				foreach($this->arr_itcupom[$codmaparesumo] as $itcupom){
					$codcst = $this->cstpiscofins($itcupom);
					if(in_array($codcst, array("04", "05", "06", "07", "08", "09"))){
						$natoperacao = $this->arr_natoperacao[$itcupom["natoperacaonfcupom"]];
						$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];
						$contacontabil = $planocontas["contacontabil"];

						$natreceita = $this->natreceita($itcupom);
						$totalbruto = number_format($itcupom["valortotal"], 2, ".", "");						
						$arr_cst[$codcst][$contacontabil]["totalbruto"] += $totalbruto;
						$arr_cst[$codcst][$contacontabil]["natreceita"][$natreceita] += $totalbruto;
					}
				}
			}
		}
		foreach($this->arr_notafiscal as $notafiscal){
			foreach($notafiscal["itnotafiscal"] as $itnotafiscal){
				$codcst = $this->cstpiscofins($itnotafiscal);
				if(in_array($codcst, array("04", "05", "06", "07", "08", "09"))){
					$natoperacao = $this->arr_natoperacao[$itnotafiscal["natoperacao"]];
					$planocontas = $this->arr_planocontas[$natoperacao["codconta"]];
					$contacontabil = $planocontas["contacontabil"];

					$natreceita = $this->natreceita($itnotafiscal);
					$totalbruto = number_format($itnotafiscal["totalbruto"], 2, ".", "");					
					$arr_cst[$codcst][$contacontabil]["totalbruto"] += $totalbruto;
					$arr_cst[$codcst][$contacontabil]["natreceita"][$natreceita] += $totalbruto;
				}
			}
		}
		foreach($arr_cst as $cst_i => $cst_v){
			foreach($cst_v as $contacontabil => $result){				
				$registros[] = array(
					// Texto fixo contendo "M800"
					"REG" => "M800",
					// Codigo de situacao tributaria - CST das demais receitas auferidas no periodo, sem incidencia da contribuicao,
					// ou sem contribuicao apurada a pagar, conforme a tabela 4.3.3
					"CST_COFINS" => $cst_i,
					// Valor total da receita bruta no periodo
					"VL_TOT_REC" => $this->valor_decimal($result["totalbruto"], 2),
					// Codigo da conta analitica contabil debitada/creditada
					"COD_CTA" => $contacontabil,
					// Descricao complementar da natureza da receita
					"DESC_COMPL" => ""
				);
				$registros = array_merge($registros, $this->registro_M810($cst_v[$contacontabil]["natreceita"], $contacontabil));
			}
		}
		return $registros;
	}

	// Detalhamento das receitas isentas, nao alcancadas pela incidencia da contribuicao, sujeitas a aliquota zero ou de vendas com suspensao - COFINS [nivel 3]
	protected function registro_M810($arr_natreceita, $contacontabil){
		$registros = array();
		foreach($arr_natreceita as $natreceita => $totalbruto){
			$registros[] = array(
				// Texto fixo contendo "M810"
				"REG" => "M810",
				// Natureza da receita, conforme relacao constante nas tabelas de detalhamento da natureza da receita por situacao tributaria abaixo:
				// Tabela 4.3.10: Produtos sujeitos a incidencia monofasica da contribuicao social - aliquotas diferenciadas (CST 04 - revenda)
				// Tabela 4.3.11: Produtos sujeitos a incidencia monofasica da contribuicao social - aliquotas por unidade de medida do produto (CST 04 - revenda)
				// Tabela 4.3.12: Produtos sujeitos a substituicao tributaria da contribuicao social (CST 05 - revenda)
				// Tabela 4.3.13: Produtos sujeitos a aliquota zero da contribuicao social (CST 06)
				// Tabela 4.3.14: Operacoes com isencao da contribuicao social (CST 07)
				// Tabela 4.3.15: Operacoes sim incidencia da contribuicao social (CST 08)
				// Tabela 4.3.16: Operacoes com suspencao da contribuicao social (CST 09)
				"NAT_REC" => str_pad($natreceita, 3, "0", STR_PAD_LEFT),
				// Valor da receita bruta no periodo, relativo a natureza da receita (NAT_REC)
				"VL_REC" => $this->valor_decimal($totalbruto, 2),
				// Codigo da conta analitica contabil debitada/creditada
				"COD_CTA" => $contacontabil,
				// Descricao complementar da natureza da receita
				"DESC_COMPL" => ""
			);
		}
		return $registros;
	}

	// Encerramento do bloco M [nivel 1]
	protected function registro_M990($t_registros){
		$registros = array();
		$registros[] = array(
			// Texto fixo contento "M990"
			"REG" => "M990",
			// Quantidade de linhas do bloco F
			"QTD_LIN_M" => sizeof($t_registros) + 1
		);
		return $registros;
	}

	// Abertura do bloco 1 [nivel 1]
	protected function registro_1001(){
		$registros = array();
		$ind_mov = ($this->totalanteriorpis > 0 || $this->totalanteriorcofins > 0 ? "0" : "1");
		$registros[] = array(
			// Texto fixo contendo "1001"
			"REG" => "1001",
			// Indicador de movimento
			// 0 - Bloco com dados informados
			// 1 - Bloco sem dados informados
			"IND_MOV" => $ind_mov
		);
		if($ind_mov == "0"){
			$registros = array_merge($registros, $this->registro_1100());
			$registros = array_merge($registros, $this->registro_1500());
		}
		return $registros;
	}

	// Controle de creditos fiscais - PIS/PASEP
	protected function registro_1100(){
		$registros = array();

		$ano = date_year($this->datainicial);
		$mes = date_month($this->datainicial);
		$per_apu_cred = date("mY", mktime(0, 0, 0, ($mes - 1), 1, $ano));

		$vl_cred_apur = $this->totalanteriorpis;
		$vl_cred_ext_apu = 0;
		$vl_tot_cred_apu = $vl_cred_apur + $vl_cred_ext_apu;
		$vl_cred_desc_pa_ant = $this->utilizadoanteriorpis;
		$vl_cred_per_pa_ant = 0;
		$vl_cred_dcomp_pa_ant = 0;
		$vl_cred_disp_efd = $vl_tot_cred_apu - $vl_cred_desc_pa_ant - $vl_cred_per_pa_ant - $vl_cred_dcomp_pa_ant;
		$vl_cred_desc_efd = ($vl_cred_disp_efd - $this->m200_vl_tot_cont_nc_dev <= 0 ? $vl_cred_disp_efd : $this->m200_vl_tot_cont_nc_dev);
		$vl_cred_per_efd = 0;
		$vl_cred_dcomp_efd = 0;
		$vl_cred_trans = 0;
		$vl_cred_out = 0;
		$sld_cred_fim = $vl_cred_disp_efd - $vl_cred_desc_efd - $vl_cred_per_efd - $vl_cred_dcomp_efd - $vl_cred_trans - $vl_cred_out;

		$this->_1100_vl_cred_desc_efd = $vl_cred_desc_efd;

		$registros[] = array(
			// Texto fixo contendo "1100"
			"REG" => "1100",
			// Periodo de apuracao do credito (mm/aaaa)
			"PER_APU_CRED" => $per_apu_cred,
			// Indicador da origem do credito:
			// 01 - Credito decorrente de operacoes proprias
			// 02 - Credito transferido por pessoa juridica sucedida
			"ORIG_CRED" => "01",
			// CNPJ da pessoa juridica cedente do credito (se ORIG_CRED = 02)
			"CNPJ_SUC" => "",
			// Codigo do tipo de credito, conforme tabela 4.3.6
			"COD_CRED" => "101",
			// Valor do credito apurado na escrituracao fiscal digital ou em demonstrativo DACON de periodos anteriores
			"VL_CRED_APU" => $this->valor_decimal($vl_cred_apur, 2),
			// Valor de credito extemporaneo apurado (registro 1101), referente a periodo anterior, informado no campo 02 - PER_APU_CRED
			"VL_CRED_EXT_APU" => $this->valor_decimal($vl_cred_ext_apu, 2),
			// Valor total do credito apurado (VL_CRED_APU + VL_CRED_EXT_APU)
			"VL_TOT_CRED_APU" => $this->valor_decimal($vl_tot_cred_apu, 2),
			// Valor do credito utilizado mediante desconto, em periodo(s) anterior(es)
			"VL_CRED_DESC_PA_ANT" => $this->valor_decimal($vl_cred_desc_pa_ant, 2),
			// Valor de credito utilizado mediante pedido de ressarcimento, em periodo(s) anterior(es)
			"VL_CRED_PER_PA_ANT" => $this->valor_decimal($vl_cred_per_pa_ant, 2),
			// Valor de credito utilizado mediante declaracao de compensacao intermediaria (credito de exportacao), em periodo(s) anterior(es)
			"VL_CRED_DCOMP_PA_ANT" => $this->valor_decimal($vl_cred_dcomp_pa_ant, 2),
			// Saldo de credito disponivel para utilizacao neste periodo de escrituracao (VL_CRED_DESC_APU - VL_CRED_DESC_PA_ANT - VL_CRED_PER_PA_ANT - VL_CRED_DCOMP_PA_ANT)
			"VL_CRED_DISP_EFD" => $this->valor_decimal($vl_cred_disp_efd, 2),
			// Valor de credito descontado neste periodo de escrituracao
			"VL_CRED_DESC_EFD" => $this->valor_decimal($vl_cred_desc_efd, 2),
			// Valor do credito objeto de pedido de ressarcimento (PER) neste periodo de escrituracao
			"VL_CRED_PER_EFD" => $this->valor_decimal($vl_cred_per_efd, 2),
			// Valor do credito utilizado mediante declaracao de compensacao intermediaria neste periodo de escrituracao
			"VL_CRED_DCOMP_EFD" => $this->valor_decimal($vl_cred_dcomp_efd, 2),
			// Valor do credito transferido em evento de cisao, fusao ou incorporacao
			"VL_CRED_TRANS" => $this->valor_decimal($vl_cred_trans, 2),
			// Valor do credito utilizado por outras formas
			"VL_CRED_OUT" => $this->valor_decimal($vl_cred_out, 2),
			// Saldo de creditos a utilizar em periodo de apuracao futuro (VL_CRED_DISP_EFD - VL_CRED_DESC_EFD - VL_CRED_PER_EFD - VL_CRED_DCOMP_EFD - VL_CRED_TRANS - VL_CRED_OUT)
			"SLD_CRED_FIM" => $this->valor_decimal($sld_cred_fim, 2)
		);
		return $registros;
	}

	// Controle de creditos fiscais - Cofins
	protected function registro_1500(){
		$registros = array();

		$ano = date_year($this->datainicial);
		$mes = date_month($this->datainicial);
		$per_apu_cred = date("mY", mktime(0, 0, 0, ($mes - 1), 1, $ano));

		$vl_cred_apur = $this->totalanteriorcofins;
		$vl_cred_ext_apu = 0;
		$vl_tot_cred_apu = $vl_cred_apur + $vl_cred_ext_apu;
		$vl_cred_desc_pa_ant = $this->utilizadoanteriorcofins;
		$vl_cred_per_pa_ant = 0;
		$vl_cred_dcomp_pa_ant = 0;
		$vl_cred_disp_efd = $vl_tot_cred_apu - $vl_cred_desc_pa_ant - $vl_cred_per_pa_ant - $vl_cred_dcomp_pa_ant;
		$vl_cred_desc_efd = ($vl_cred_disp_efd - $this->m600_vl_tot_cont_nc_dev <= 0 ? $vl_cred_disp_efd : $this->m600_vl_tot_cont_nc_dev);
		$vl_cred_per_efd = 0;
		$vl_cred_dcomp_efd = 0;
		$vl_cred_trans = 0;
		$vl_cred_out = 0;
		$sld_cred_fim = $vl_cred_disp_efd - $vl_cred_desc_efd - $vl_cred_per_efd - $vl_cred_dcomp_efd - $vl_cred_trans - $vl_cred_out;

		$this->_1500_vl_cred_desc_efd = $vl_cred_desc_efd;

		$registros[] = array(
			// Texto fixo contendo "1500"
			"REG" => "1500",
			// Periodo de apuracao do credito (mm/aaaa)
			"PER_APU_CRED" => $per_apu_cred,
			// Indicador da origem do credito:
			// 01 - Credito decorrente de operacoes proprias
			// 02 - Credito transferido por pessoa juridica sucedida
			"ORIG_CRED" => "01",
			// CNPJ da pessoa juridica cedente do credito (se ORIG_CRED = 02)
			"CNPJ_SUC" => "",
			// Codigo do tipo de credito, conforme tabela 4.3.6
			"COD_CRED" => "101",
			// Valor do credito apurado na escrituracao fiscal digital ou em demonstrativo DACON de periodos anteriores
			"VL_CRED_APU" => $this->valor_decimal($vl_cred_apur, 2),
			// Valor de credito extemporaneo apurado (registro 1101), referente a periodo anterior, informado no campo 02 - PER_APU_CRED
			"VL_CRED_EXT_APU" => $this->valor_decimal($vl_cred_ext_apu, 2),
			// Valor total do credito apurado (VL_CRED_APU + VL_CRED_EXT_APU)
			"VL_TOT_CRED_APU" => $this->valor_decimal($vl_tot_cred_apu, 2),
			// Valor do credito utilizado mediante desconto, em periodo(s) anterior(es)
			"VL_CRED_DESC_PA_ANT" => $this->valor_decimal($vl_cred_desc_pa_ant, 2),
			// Valor de credito utilizado mediante pedido de ressarcimento, em periodo(s) anterior(es)
			"VL_CRED_PER_PA_ANT" => $this->valor_decimal($vl_cred_per_pa_ant, 2),
			// Valor de credito utilizado mediante declaracao de compensacao intermediaria (credito de exportacao), em periodo(s) anterior(es)
			"VL_CRED_DCOMP_PA_ANT" => $this->valor_decimal($vl_cred_dcomp_pa_ant, 2),
			// Saldo de credito disponivel para utilizacao neste periodo de escrituracao (VL_CRED_DESC_APU - VL_CRED_DESC_PA_ANT - VL_CRED_PER_PA_ANT - VL_CRED_DCOMP_PA_ANT)
			"VL_CRED_DISP_EFD" => $this->valor_decimal($vl_cred_disp_efd, 2),
			// Valor de credito descontado neste periodo de escrituracao
			"VL_CRED_DESC_EFD" => $this->valor_decimal($vl_cred_desc_efd, 2),
			// Valor do credito objeto de pedido de ressarcimento (PER) neste periodo de escrituracao
			"VL_CRED_PER_EFD" => $this->valor_decimal($vl_cred_per_efd, 2),
			// Valor do credito utilizado mediante declaracao de compensacao intermediaria neste periodo de escrituracao
			"VL_CRED_DCOMP_EFD" => $this->valor_decimal($vl_cred_dcomp_efd, 2),
			// Valor do credito transferido em evento de cisao, fusao ou incorporacao
			"VL_CRED_TRANS" => $this->valor_decimal($vl_cred_trans, 2),
			// Valor do credito utilizado por outras formas
			"VL_CRED_OUT" => $this->valor_decimal($vl_cred_out, 2),
			// Saldo de creditos a utilizar em periodo de apuracao futuro (VL_CRED_DISP_EFD - VL_CRED_DESC_EFD - VL_CRED_PER_EFD - VL_CRED_DCOMP_EFD - VL_CRED_TRANS - VL_CRED_OUT)
			"SLD_CRED_FIM" => $this->valor_decimal($sld_cred_fim, 2)
		);
		return $registros;
	}

	// Encerramento do bloco 1 [nivel 1]
	protected function registro_1990($t_registros){
		$registros = array();
		$registros[] = array(
			// Texto fixo contento "1990"
			"REG" => "1990",
			// Quantidade de linhas do bloco F
			"QTD_LIN_1" => sizeof($t_registros) + 1
		);
		return $registros;
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
		$this->escrever_registro(array("REG" => "9900", "REG_BLOC" => "9900", "QTD_REG_BLC" => (sizeof($this->n_registro) + 4)));
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
			"QTD_LIN_9" => $t_quantidade + 2
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
				"totalbruto" => ($itcupom["valortotal"] + $itcupom["desconto"] - $itcupom["acrescimo"]),
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
			"nfce" => "S",
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

}
