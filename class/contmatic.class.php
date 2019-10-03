<?php

class Contmatic{

	private $con;
	private $ano;
	private $mes;
	private $apelidocontimatic;
	protected $prevreal;
	protected $status;
	private $estabelecimento; // Estabelecimento a ser gerado
	private $gerar_notafiscal; // Se deve ou nao gerar notas fiscais
	private $gerar_cupomfiscal; // Se deve ou nao gerar cupons fiscais
	private $gerar_planodecontas; // Se deve ou nao gerar plano de contas
	private $gerar_financeiro; // Se deve ou nao gerar financeiro xls
	private $arr_cidade; // Array com as cidades dos parceiros ("codcidade" como chave do array)
	private $arr_cliente; // Array com os clientes utilizados nas notas fiscais e cupons fiscais ("codcliente" como chave do array)
	private $arr_estabelecimento; // Array com os estabelecimentos utilizados nas notas fiscais (transferencia) ("codestabelec" como chave do array)
	private $arr_fornecedor; // Array com os fornecedores utilizados nas notas fiscais ("codfornec" como chave do array)
	private $arr_maparesumo; // Array com os mapas resumo ("codmaparesumo" como chave do array)
	private $arr_maparesumoimposto; // Array com os impostos dos mapas resumo ("codmaparesumo" como chave do array) (contem um outro array para cada valor do primeiro nivel, onde sao os registros da tabela)
	private $arr_notafiscal; // Array com as notas fiscais ("idnotafiscal" como chave do array)
	private $arr_notafiscalimposto; // Array com os impostos das notas fiscais ("idnotafiscal" como chave do array) (contem um outro array para cada valor do primeiro nivel, onde sao os registros da tabela)
	private $arr_operacaonota; // Array com as operacoes de nota fiscal ("operacao" como chave do array)

	function __construct($con){
		$this->con = $con;
		$this->gerar_cupomfiscal(FALSE);
		$this->gerar_notafiscal(FALSE);
	}

	function criar_arquivo($arr_linha, $tipo){
		$arr_linha[] = "";
		$arr_razaosocial = explode(" ", $this->estabelecimento->getrazaosocial());
		$nomearq = $this->estabelecimento->getdircontabil().strtoupper($arr_razaosocial[0]).".".$tipo.str_pad($this->mes, 2, "0", "STR_PAD_LEFT");
		
		write_file($nomearq, $arr_linha, true);
		download($nomearq);		
	}

	function gerar_cupomfiscal($bool){
		if(is_bool($bool)){
			$this->gerar_cupomfiscal = $bool;
		}
	}

	function gerar_notafiscal($bool){
		if(is_bool($bool)){
			$this->gerar_notafiscal = $bool;
		}
	}

	function gerar_planodecontas($bool){
		if(is_bool($bool)){
			$this->gerar_planodecontas = $bool;
		}
	}

	function gerar_financeiro($bool){
		if(is_bool($bool)){
			$this->gerar_financeiro = $bool;
		}
	}

	function setano($ano){
		$this->ano = $ano;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	function setmes($mes){
		$this->mes = $mes;
	}

	public function setprevreal($prevreal = "R"){
		$this->prevreal = $prevreal;
	}

	public function setstatus($status = "L"){
		$this->status = $status;
	}

	public function setapelidocontimatic($apelidocontimatic){
		$this->apelidocontimatic = $apelidocontimatic;
	}

	function gerar(){
		// Verifica se foi informado o diretorio de integracao contabil
		if(strlen($this->estabelecimento->getdircontabil()) == 0){
			$_SESSION["ERROR"] = "Informe o diret&oacute;rio de integra&ccedil;&atilde;o cont&aacute;bil para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			echo messagebox("error", "", $_SESSION["ERROR"]);
			return FALSE;
		}

		if($this->gerar_financeiro){
			$query = "SELECT codlancto, data, debito, credito, LPAD(valorliquido::text,17,'0') AS valorliquido, historicopadrao, complemento, ccdb, cccr, pagrec FROM ";

			$query .= "( ";
			$query .= "SELECT lancamento.codlancto, lpad(Extract('Day' From  lancamento.dtliquid::date)::text,2,'0')|| '/' || lpad(Extract('Month' From  lancamento.dtliquid::date)::TEXT, 2, '0') AS data, ";
			$query .= "contaparceiro.contacontabil as debito, contabanco.contacontabil AS credito, ";
			$query .= "lancamento.valorparcela AS valorliquido, historicopadrao.descricao AS historicopadrao, ";
			$query .= "lancamento.numnotafis || v_parceiro.nome AS complemento, '' as CCDB, ";
			$query .= "'' AS CCCR , lancamento.pagrec ";
			$query .= "FROM lancamento ";
			$query .= "INNER JOIN v_parceiro ON (lancamento.codparceiro = v_parceiro.codparceiro AND lancamento.tipoparceiro = v_parceiro.tipoparceiro) ";
			$query .= "INNER join banco on (lancamento.codbanco = banco.codbanco) ";
			$query .= "LEFT JOIN planocontas AS contaparceiro on (v_parceiro.codconta = contaparceiro.codconta) ";
			$query .= "LEFT JOIN planocontas AS contabanco on (banco.codconta = contabanco.codconta) ";
			$query .= "LEFT JOIN historicopadrao ON (contaparceiro.codhistorico = historicopadrao.codhistorico) ";
			$query .= "WHERE EXTRACT(YEAR FROM lancamento.dtliquid) = '{$this->ano}' AND lancamento.codestabelec = {$this->estabelecimento->getcodestabelec()}  ";
			$query .= " AND EXTRACT(MONTH FROM lancamento.dtliquid) = '{$this->mes}' AND (SUBSTR(lancamento.serie,1,3) != 'TRF' OR lancamento.serie is null)  ";

			$query .= "union all ";

			$query .= "SELECT codlancto AS codlancto, 	lpad(Extract('Day' From lancamento.dtliquid::date)::text,2,'0')|| '/' || lpad(Extract('Month' From lancamento.dtliquid::date)::TEXT, 2, '0') AS data, ";
			$query .= "(SELECT contacontabil FROM planocontas WHERE codconta=(SELECT codconta FROM banco WHERE codbanco=(SELECT codbanco FROM lancamento AS lanc_aux WHERE lanc_aux.dtliquid=lancamento.dtliquid AND serie='TRF' AND lanc_aux.horalog=lancamento.horalog AND pagrec='R' limit 1))) AS debito, ";
			$query .= "(CASE WHEN pagrec='P' THEN contabanco.contacontabil END) AS credito, ";
			$query .= "lancamento.valorparcela AS valorliquido, ";
			$query .= "historicopadrao.descricao AS historicopadrao, ";
			$query .= "lancamento.numnotafis || v_parceiro.nome AS complemento, ";
			$query .= "'' as CCDB, ";
			$query .= "'' AS CCCR, ";
			$query .= "'T' AS pagrec ";
			$query .= "FROM lancamento INNER JOIN v_parceiro ON (lancamento.codparceiro = v_parceiro.codparceiro AND lancamento.tipoparceiro = v_parceiro.tipoparceiro) ";
			$query .= "INNER join banco on (lancamento.codbanco = banco.codbanco) ";
			$query .= "LEFT JOIN planocontas AS contabanco on (banco.codconta = contabanco.codconta) ";
			$query .= "LEFT JOIN planocontas AS contaparceiro on (v_parceiro.codconta = contaparceiro.codconta) ";
			$query .= "LEFT JOIN historicopadrao ON (contaparceiro.codhistorico = historicopadrao.codhistorico) ";
			$query .= "WHERE SUBSTR(lancamento.serie,1,3)='TRF' AND lancamento.pagrec = 'P' ";
			$query .= " AND EXTRACT(YEAR FROM lancamento.dtliquid) = '{$this->ano}' AND lancamento.codestabelec = {$this->estabelecimento->getcodestabelec()}  ";
			$query .= " AND EXTRACT(MONTH FROM lancamento.dtliquid) = '{$this->mes}'  ";
			$query .= "GROUP BY 1,2,3,4,5,6,7,8 ";

			$query .= "union all ";

			$query .= "SELECT lancamento.codlancto, lpad(Extract('Day' From lancamento.dtliquid::date)::text,2,'0')|| '/' || lpad(Extract('Month' From  lancamento.dtliquid::date)::TEXT, 2, '0') AS data, ";
			$query .= "CASE WHEN lancamento.pagrec = 'P' THEN conta_p.contacontabil ELSE conta_r.contacontabil END as debito, contabanco.contacontabil AS credito, ";
			$query .= "lancamento.valorjuros AS valorliquido, CASE WHEN lancamento.pagrec = 'P' THEN h_p.descricao ELSE h_r.descricao END AS historicopadrao, ";
			$query .= "lancamento.numnotafis || v_parceiro.nome AS complemento, '' as CCDB, ";
			$query .= "'' AS CCCR , lancamento.pagrec ";
			$query .= "FROM lancamento ";
			$query .= "INNER JOIN v_parceiro ON (lancamento.codparceiro = v_parceiro.codparceiro AND lancamento.tipoparceiro = v_parceiro.tipoparceiro) ";
			$query .= "INNER join banco on (lancamento.codbanco = banco.codbanco) ";
			$query .= "LEFT JOIN paramplanodecontas ON (paramplanodecontas.codestabelec =  lancamento.codestabelec) ";
			$query .= "LEFT JOIN planocontas AS conta_p on (paramplanodecontas.codconta_valorjuros_pag = conta_p.codconta) ";
			$query .= "LEFT JOIN planocontas AS conta_r on (paramplanodecontas.codconta_valorjuros_rec = conta_r.codconta) ";
			$query .= "LEFT JOIN planocontas AS contabanco on (banco.codconta = contabanco.codconta) ";
			$query .= "LEFT JOIN historicopadrao h_p ON (conta_p.codhistorico = h_p.codhistorico) ";
			$query .= "LEFT JOIN historicopadrao h_r ON (conta_r.codhistorico = h_r.codhistorico) ";
			$query .= "WHERE EXTRACT(YEAR FROM lancamento.dtliquid) = '{$this->ano}' AND lancamento.codestabelec = {$this->estabelecimento->getcodestabelec()}  AND lancamento.valorjuros > 0 ";
			$query .= " AND EXTRACT(MONTH FROM lancamento.dtliquid) = '{$this->mes}'  ";

			$query .= "union all ";

			$query .= "SELECT lancamento.codlancto, lpad(Extract('Day' From lancamento.dtliquid::date)::text,2,'0')|| '/' || lpad(Extract('Month' From  lancamento.dtliquid::date)::TEXT, 2, '0') AS data, ";
			$query .= "CASE WHEN lancamento.pagrec = 'P' THEN conta_p.contacontabil ELSE conta_r.contacontabil END as debito, contabanco.contacontabil AS credito, ";
			$query .= "lancamento.valordescto AS valorliquido, CASE WHEN lancamento.pagrec = 'P' THEN h_p.descricao ELSE h_r.descricao END AS historicopadrao, ";
			$query .= "lancamento.numnotafis || v_parceiro.nome AS complemento, '' as CCDB, ";
			$query .= "'' AS CCCR , lancamento.pagrec ";
			$query .= "FROM lancamento ";
			$query .= "INNER JOIN v_parceiro ON (lancamento.codparceiro = v_parceiro.codparceiro AND lancamento.tipoparceiro = v_parceiro.tipoparceiro) ";
			$query .= "INNER join banco on (lancamento.codbanco = banco.codbanco) ";
			$query .= "LEFT JOIN paramplanodecontas ON (paramplanodecontas.codestabelec =  lancamento.codestabelec) ";
			$query .= "LEFT JOIN planocontas AS conta_p on (paramplanodecontas.codconta_valordescto_pag = conta_p.codconta) ";
			$query .= "LEFT JOIN planocontas AS conta_r on (paramplanodecontas.codconta_valordescto_rec = conta_r.codconta) ";
			$query .= "LEFT JOIN planocontas AS contabanco on (banco.codconta = contabanco.codconta) ";
			$query .= "LEFT JOIN historicopadrao h_p ON (conta_p.codhistorico = h_p.codhistorico) ";
			$query .= "LEFT JOIN historicopadrao h_r ON (conta_r.codhistorico = h_r.codhistorico) ";
			$query .= "WHERE EXTRACT(YEAR FROM lancamento.dtliquid) = '{$this->ano}' AND lancamento.codestabelec = {$this->estabelecimento->getcodestabelec()}  AND lancamento.valordescto > 0 ";
			$query .= " AND EXTRACT(MONTH FROM lancamento.dtliquid) = '{$this->mes}'  ";

			$query .= "union all ";

			$query .= "SELECT lancamento.codlancto, lpad(Extract('Day' From lancamento.dtliquid::date)::text,2,'0')|| '/' || lpad(Extract('Month' From  lancamento.dtliquid::date)::TEXT, 2, '0') AS data, ";
			$query .= "CASE WHEN lancamento.pagrec = 'P' THEN conta_p.contacontabil ELSE conta_r.contacontabil END as debito, contabanco.contacontabil AS credito, ";
			$query .= "lancamento.valoracresc AS valorliquido, CASE WHEN lancamento.pagrec = 'P' THEN h_p.descricao ELSE h_r.descricao END AS historicopadrao, ";
			$query .= "lancamento.numnotafis || v_parceiro.nome AS complemento, '' as CCDB, ";
			$query .= "'' AS CCCR , lancamento.pagrec ";
			$query .= "FROM lancamento ";
			$query .= "INNER JOIN v_parceiro ON (lancamento.codparceiro = v_parceiro.codparceiro AND lancamento.tipoparceiro = v_parceiro.tipoparceiro) ";
			$query .= "INNER join banco on (lancamento.codbanco = banco.codbanco) ";
			$query .= "LEFT JOIN paramplanodecontas ON (paramplanodecontas.codestabelec =  lancamento.codestabelec) ";
			$query .= "LEFT JOIN planocontas AS conta_p on (paramplanodecontas.codconta_valordescto_pag = conta_p.codconta) ";
			$query .= "LEFT JOIN planocontas AS conta_r on (paramplanodecontas.codconta_valordescto_rec = conta_r.codconta) ";
			$query .= "LEFT JOIN planocontas AS contabanco on (banco.codconta = contabanco.codconta) ";
			$query .= "LEFT JOIN historicopadrao h_p ON (conta_p.codhistorico = h_p.codhistorico) ";
			$query .= "LEFT JOIN historicopadrao h_r ON (conta_r.codhistorico = h_r.codhistorico) ";
			$query .= "WHERE EXTRACT(YEAR FROM lancamento.dtliquid) = '{$this->ano}' AND lancamento.codestabelec = {$this->estabelecimento->getcodestabelec()}  AND lancamento.valoracresc > 0 ";
			$query .= " AND EXTRACT(MONTH FROM lancamento.dtliquid) = '{$this->mes}'  ";
			$query .= ")AS tmp ORDER BY tmp.data, tmp.complemento, tmp.historicopadrao ASC ";

			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);

			$arr_linha_contimatic = array();
			$cont = 1;
			foreach($arr as $row){

				$linha  = $this->valor_numerico($cont, 0, 7); //
				$linha .= $this->valor_texto($row["data"], 5); //

				if($row["pagrec"] == "R"){
					$linha .= $this->valor_numerico($row["credito"], 0, 7);
					$linha .= $this->valor_numerico($row["debito"], 0, 7);
				}else{
					$linha .= $this->valor_numerico($row["debito"], 0, 7);
					$linha .= $this->valor_numerico($row["credito"], 0, 7);
				}

				$linha .= $row["valorliquido"]; //
				$linha .= $this->valor_numerico($row["historicopadrao"],0,5);
				$linha .= $this->valor_texto(removespecial($row["complemento"]." Chave:".$row["codlancto"]), 200); //
				if($row["pagrec"] == "R"){
					$linha .= $this->valor_texto($row["cccr"], 42);
					$linha .= $this->valor_texto($row["ccdb"], 42);
				}else{
					$linha .= $this->valor_texto($row["ccdb"], 42);
					$linha .= $this->valor_texto($row["cccr"], 42);
				}
				$cont++;
				$arr_linha_contimatic[] = $linha;
			}

			if(strlen($this->apelidocontimatic) > 0){
				$nomearq = $this->estabelecimento->getdircontabil().$this->apelidocontimatic."."."M".substr($this->ano,-2);
			}else{
				$nomearq = $this->estabelecimento->getdircontabil().$this->estabelecimento->getnome()."_financeiro_".$this->mes.".txt";
			}
			$arquivo = fopen($nomearq, "w");
			foreach($arr_linha_contimatic as $linha){
				fwrite($arquivo, $linha."\r\n");
			}
			fclose($arquivo);
			echo messagebox("success", "", "Arquivo gerado com sucesso!");
			echo download($nomearq);
		}

		// Gera o planod e contas
		if($this->gerar_planodecontas){
			//******************************************************************
			//						Registro Plano de Contas
			//******************************************************************
			$where = array();
			$this->setprevreal("R");
			$this->setstatus("L");

			$query = "SELECT lancamento.codlancto, lancamento.dtlancto, lancamento.valorliquido, ";
			$query .= "(SELECT numconta FROM planocontas WHERE lancamento.codcontadeb = codconta AND tpconta IN ('D','A')) AS numcontadeb, ";
			$query .= "(SELECT numconta FROM planocontas WHERE lancamento.codcontacred = codconta AND tpconta IN ('C','A')) AS numcontacred, ";
			$query .= "lancamento.numnotafis, lancamento.serie, v_parceiro.nome as parceiro ";
			$query .= "FROM lancamento ";
			$query .= "INNER JOIN v_parceiro ON (lancamento.codparceiro = v_parceiro.codparceiro AND lancamento.tipoparceiro = v_parceiro.tipoparceiro) ";
			if(strlen($this->ano) > 0){
				$where[] = "EXTRACT(YEAR FROM dtliquid) = '{$this->ano}'";
			}
			if(strlen($this->mes) > 0){
				$where[] = "EXTRACT(MONTH FROM dtliquid) ='".$this->mes."'";
			}
			if(strlen($this->codestabelec) > 0){
				$where[] = "codestabelec = ".$this->codestabelec;
			}
			if(strlen($this->prevreal) > 0){
				$where[] = "prevreal = '".$this->prevreal."'";
			}
			if(strlen($this->status) > 0){
				$where[] = "status = '".$this->status."'";
			}
			if(sizeof($where) > 0){
				$query .= "WHERE (lancamento.codcontacred > 0 OR lancamento.codcontadeb > 0) AND ".implode(" AND ", $where)." ";
			}
			echo $query;
			$arr_linha_contimatic = array();
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);

			if(count($arr) == 0){
				die(messagebox("error", "", "Nenhum lancamento encontrado."));
			}

			foreach($arr as $row){
				$linha = $this->valor_numerico($row["codlancto"], 0, 7); // Codigo do lancamento
				$linha .= $this->valor_data_planodecontas($row["dtlancto"]); // Data do lancamento
				$linha .= $this->valor_numerico($row["numcontadeb"], 0, 7); // Codigo reduzido da conta a ser debitada
				$linha .= $this->valor_numerico($row["numcontacred"], 0, 7); // Codigo reduzido da conta a ser creditada
				$linha .= $this->valor_numerico($row["valorliquido"], 2, 17); // Valor
				$linha .= $this->valor_numerico($row["codcontacred"], 0, 5); // Historico padrao
				$linha .= $this->valor_texto("{$row["numnotafis"]} {$row["serie"]} - {$row["parceiro"]}", 200); // Complemento
				$arr_linha_contimatic[] = $linha;
			}

			$nomearq = $this->estabelecimento->getdircontabil().$this->estabelecimento->getnome().$this->mes.".txt";
			$arquivo = fopen($nomearq, "w");
			foreach($arr_linha_contimatic as $linha){
				fwrite($arquivo, $linha."\r\n");
			}
			fclose($arquivo);
			echo download($nomearq);
			//******************************************************************
		}

		// Busca os mapas resumo
		$this->arr_maparesumo = array();
		if($this->gerar_cupomfiscal){
			setprogress(0, "Carregando mapas resumo", TRUE);
			$res = $this->con->query("SELECT codmaparesumo FROM maparesumo WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." AND EXTRACT(YEAR FROM dtmovto) = ".$this->ano." AND EXTRACT(MONTH FROM dtmovto) = ".$this->mes." ORDER BY dtmovto, caixa");
			$arr = $res->fetchAll(2);
			$arr_codmaparesumo = array();
			foreach($arr as $i => $row){
				$arr_codmaparesumo[] = $row["codmaparesumo"];
			}
			$this->arr_maparesumo = object_array_key(objectbytable("maparesumo", NULL, $this->con), $arr_codmaparesumo);
		}

		// Busca os impostos dos mapas resumo
		$this->arr_maparesumoimposto = array();
		$i = 1;
		$n = sizeof($this->arr_maparesumo);
		foreach($this->arr_maparesumo as $maparesumo){
			setprogress(($i / $n * 100), "Carregando impostos dos mapas resumo: ".$i." de ".$n);
			$query = "SELECT tptribicms, aliqicms, SUM(totalliquido) AS totalliquido, SUM(totalicms) AS totalicms ";
			$query .= "FROM maparesumoimposto ";
			$query .= "WHERE codmaparesumo = ".$maparesumo->getcodmaparesumo()." ";
			$query .= "	AND totalliquido > 0 ";
			$query .= "GROUP BY tptribicms, aliqicms ";
			$query .= "ORDER BY tptribicms, aliqicms ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
				$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
				$maparesumoimposto->settptribicms($row["tptribicms"]);
				$maparesumoimposto->setaliqicms($row["aliqicms"]);
				$maparesumoimposto->settotalliquido($row["totalliquido"]);
				$maparesumoimposto->settotalicms($row["totalicms"]);
				$this->arr_maparesumoimposto[$maparesumo->getcodmaparesumo()][] = $maparesumoimposto;
			}
			$i++;
		}


		// Busca operacoes de nota fiscal
		$this->arr_operacaonota = array();
		setprogress(0, "Buscando operacoes de nota fiscal", TRUE);
		$operacaonota = objectbytable("operacaonota", NULL, $this->con);
		$arr_operacaonota = object_array($operacaonota);
		foreach($arr_operacaonota as $operacaonota){
			$this->arr_operacaonota[$operacaonota->getoperacao()] = $operacaonota;
		}

		// Busca as notas fiscais
		$this->arr_notafiscal = array();
		if($this->gerar_notafiscal){
			setprogress(0, "Carregando notas fiscais", TRUE);
			$res = $this->con->query("SELECT idnotafiscal FROM notafiscal WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." AND EXTRACT(YEAR FROM dtentrega) = ".$this->ano." AND EXTRACT(MONTH FROM dtentrega) = ".$this->mes);
			$arr = $res->fetchAll(2);
			$arr_idnotafiscal = array();
			foreach($arr as $i => $row){
				$arr_idnotafiscal[] = $row["idnotafiscal"];
			}
			$this->arr_notafiscal = object_array_key(objectbytable("notafiscal", NULL, $this->con), $arr_idnotafiscal);
		}

		// Busca os impostos das notas fiscais
		$this->arr_notafiscalimposto = array();
		$i = 1;
		$n = sizeof($this->arr_notafiscal);
		foreach($this->arr_notafiscal as $notafiscal){
			setprogress(($i / $n * 100), "Carregando impostos das notas fiscais: ".$i." de ".$n);
			$this->arr_notafiscalimposto[$notafiscal->getidnotafiscal()] = array();
			$query = "SELECT idnotafiscal, aliquota, SUM(base) AS base, SUM(valorimposto) AS valorimposto, SUM(reducao) AS reducao, SUM(isento) AS isento ";
			$query .= "FROM notafiscalimposto ";
			$query .= "WHERE idnotafiscal = ".$notafiscal->getidnotafiscal()." ";
			$query .= "	AND tipoimposto LIKE 'ICMS%' ";
			$query .= "	AND tipoimposto != 'ICMS_F' ";
			$query .= "GROUP BY idnotafiscal, aliquota ";
			$query .= "ORDER BY aliquota ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$notafiscalimposto = objectbytable("notafiscalimposto", NULL, $this->con);
				$notafiscalimposto->setidnotafiscal($notafiscal->getidnotafiscal());
				$notafiscalimposto->settipoimposto("ICMS");
				$notafiscalimposto->setaliquota($row["imposto"]);
				$notafiscalimposto->setbase($row["base"]);
				$notafiscalimposto->setvalorimposto($row["valorimposto"]);
				$notafiscalimposto->setreducao($row["reducao"]);
				$notafiscalimposto->setisento($row["isento"]);
				$this->arr_notafiscalimposto[$notafiscal->getidnotafiscal()][] = $notafiscalimposto;
			}
			$i++;
		}

		// Busca os cliente
		$this->arr_cliente = array();
		setprogress(0, "Buscando clientes", TRUE);
		$arr_codcliente = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal->getoperacao()];
			if($operacaonota->getparceiro() == "C"){
				$arr_codcliente[] = $notafiscal->getcodparceiro();
			}
		}
		$arr_codcliente = array_merge(array_unique($arr_codcliente));
		foreach($arr_codcliente as $i => $codcliente){
			setprogress((($i + 1) / sizeof($arr_codcliente) * 100), "Carregando clientes: ".($i + 1)." de ".sizeof($arr_codcliente));
			$this->arr_cliente[$codcliente] = objectbytable("cliente", $codcliente, $this->con);
		}

		// Busca os estabelecimentos
		$this->arr_estabelecimento = array();
		setprogress(0, "Buscando estabelecimentos", TRUE);
		$arr_codestabelec = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal->getoperacao()];
			if($operacaonota->getparceiro() == "E"){
				$arr_codestabelec[] = $notafiscal->getcodparceiro();
			}
		}
		$arr_codestabelec = array_merge(array_unique($arr_codestabelec));
		foreach($arr_codestabelec as $i => $codestabelec){
			setprogress((($i + 1) / sizeof($arr_codestabelec) * 100), "Carregando estabelecimentos: ".($i + 1)." de ".sizeof($arr_codestabelec));
			$this->arr_estabelecimento[$codestabelec] = objectbytable("estabelecimento", $codestabelec, $this->con);
		}

		// Busca os fornecedores
		$this->arr_fornecedor = array();
		setprogress(0, "Buscando fornecedores", TRUE);
		$arr_codfornec = array();
		foreach($this->arr_notafiscal as $notafiscal){
			$operacaonota = $this->arr_operacaonota[$notafiscal->getoperacao()];
			if($operacaonota->getparceiro() == "F"){
				$arr_codfornec[] = $notafiscal->getcodparceiro();
			}
		}
		$arr_codfornec = array_merge(array_unique($arr_codfornec));
		foreach($arr_codfornec as $i => $codfornec){
			setprogress((($i + 1) / sizeof($arr_codfornec) * 100), "Carregando fornecedores: ".($i + 1)." de ".sizeof($arr_codfornec));
			$this->arr_fornecedor[$codfornec] = objectbytable("fornecedor", $codfornec, $this->con);
		}

		// Busca as cidades
		$this->arr_cidade = array();
		setprogress(0, "Buscando cidades", TRUE);
		$arr_codcidade = array();
		$arr_codcidade[] = $this->estabelecimento->getcodcidade();
		foreach($this->arr_cliente as $cliente){
			$arr_codcidade[] = $cliente->getcodcidaderes();
		}
		foreach($this->arr_estabelecimento as $estabelecimento){
			$arr_codcidade[] = $estabelecimento->getcodcidade();
		}
		foreach($this->arr_fornecedor as $fornecedor){
			$arr_codcidade[] = $fornecedor->getcodcidade();
		}
		$arr_codcidade = array_merge(array_unique($arr_codcidade));
		foreach($arr_codcidade as $i => $codcidade){
			setprogress((($i + 1) / sizeof($arr_codcidade) * 100), "Carregando cidades: ".($i + 1)." de ".sizeof($arr_codcidade));
			$this->arr_cidade[$codcidade] = objectbytable("cidade", $codcidade, $this->con);
		}

		setprogress(0, "Gerando arquivo", TRUE);
		$arr_registro = array();
		foreach($this->arr_maparesumo as $maparesumo){
			$arr_registro[] = $this->registro_r1($maparesumo, FALSE);
			$arr_registro[] = $this->registro_r1($maparesumo, TRUE);
		}
		foreach($this->arr_notafiscal as $notafiscal){
			$arr_registro[] = $this->registro_r1($notafiscal);
		}

		$arr_linha_e = array(); // Linhas para criar arquivo de entrada
		$arr_linha_s = array(); // Linhas para criar arquivo de saida
		foreach($arr_registro as $registro){
			if(!is_null($registro)){
				$linha = implode("|", $registro);
				switch($registro["02"]){
					case "E": $arr_linha_e[] = $linha;
						break;
					case "S": $arr_linha_s[] = $linha;
						break;
				}
			}
		}

		$this->criar_arquivo($arr_linha_e, "E");
		$this->criar_arquivo($arr_linha_s, "S");

		echo messagebox("success", "", "Arquivo gerado com sucesso!");
	}

	private function valor_data($valor){
		$arr = explode("-", value_date($valor));
		return $arr[2].$arr[1];
	}

	private function valor_numerico($numero, $decimais, $tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero, $decimais, ".", "");
		$numero = substr($numero, 0, $tamanho);
		$numero = str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
		return $numero;
	}

	private function valor_texto($texto, $tamanho){
		$texto = substr($texto, 0, $tamanho);
		$texto = str_pad($texto, $tamanho, " ", STR_PAD_RIGHT);
		return $texto;
	}

	private function valor_data_planodecontas($data){
		$data = value_date($data);
		$data = convert_date($data, "Y-m-d", "d/m");
		if(strlen($data) == 0){
			$data = "00/00";
		}
		return $data;
	}

	private function valor_decimal($valor, $decimais){
		if(is_null($valor)){
			return NULL;
		}else{
			return number_format(value_numeric($valor), $decimais, "", "");
		}
	}

	private function registro_r1($ob_tabela, $subst_trib = FALSE){
		$tabela = ($ob_tabela->gettablename() == "maparesumo" ? "M" : "N");
		$arr_tributacao = array();
		switch($tabela){
			case "M": // Cupom (mapa resumo)
				$maparesumo = $ob_tabela;
				$parceiro = $this->estabelecimento;
				$totalliquido = 0;
				foreach($this->arr_maparesumoimposto[$maparesumo->getcodmaparesumo()] as $maparesumoimposto){
					if(($subst_trib && $maparesumoimposto->gettptribicms() == "F") || (!$subst_trib && $maparesumoimposto->gettptribicms() != "F")){
						$totalliquido += $maparesumoimposto->gettotalliquido();
						$arr_tributacao[] = array(
							"base" => (in_array($maparesumoimposto->gettptribicms(), array("I", "F")) ? 0 : $maparesumoimposto->gettotalliquido()),
							"aliquota" => $maparesumoimposto->getaliqicms(),
							"valorimposto" => ($subst_trib ? 0 : $maparesumoimposto->gettotalicms()),
							"isento" => ($maparesumoimposto->gettptribicms() == "I" ? $maparesumoimposto->gettotalliquido() : 0),
							"outros" => ($subst_trib ? $maparesumoimposto->gettotalliquido() : 0)
						);
					}
				}
				if(sizeof($arr_tributacao) == 0){
					return NULL;
				}
				break;
			case "N": // Nota fiscal
				$notafiscal = $ob_tabela;
				$operacaonota = $this->arr_operacaonota[$notafiscal->getoperacao()];
				switch($operacaonota->getparceiro()){
					case "C": // Cliente
						$parceiro = $this->arr_cliente[$notafiscal->getcodparceiro()];
						break;
					case "E": // Estabelecimento
						$parceiro = $this->arr_estabelecimento[$notafiscal->getcodparceiro()];
						break;
					case "F": // Fornecedor
						$parceiro = $this->arr_fornecedor[$notafiscal->getcodparceiro()];
						break;
				}
				foreach($this->arr_notafiscalimposto[$notafiscal->getidnotafiscal()] as $notafiscalimposto){
					$arr_tributacao[] = array(
						"base" => $notafiscalimposto->getbase(),
						"aliquota" => $notafiscalimposto->getaliquota(),
						"valorimposto" => $notafiscalimposto->getvalorimposto(),
						"isento" => $notafiscalimposto->getisento(),
						"outros" => 0
					);
				}
				break;
		}

		$cidade = $this->arr_cidade[$parceiro->gettablename() == "cliente" ? $parceiro->getcodcidaderes() : $parceiro->getcodcidade()];

		return array(
			// Tipo de registro
			"01" => "R1",
			// Indicador do tipo de operacao (E = entrada; S = saida)
			"02" => ($tabela == "M" ? "S" : $operacaonota->gettipo()),
			// Data de emissao
			"03" => ($tabela == "M" ? $this->valor_data($maparesumo->getdtmovto()) : $this->valor_data($notafiscal->getdtentrega())),
			// Data de circulacao
			"04" => ($tabela == "M" ? $this->valor_data($maparesumo->getdtmovto()) : $this->valor_data($notafiscal->getdtentrega())),
			// Especie da nota fiscal, de acordo com o item
			"05" => ($tabela == "M" ? "ECF" : "NF"),
			// Serie da nota fiscal
			"06" => ($tabela == "M" ? str_pad(trim($maparesumo->getcaixa()), 3, "0", STR_PAD_LEFT) : str_pad(trim($notafiscal->getserie()), 3, "0", STR_PAD_LEFT)),
			// Numero da nota fiscal (primeiro numero se for em lote)
			"07" => ($tabela == "M" ? $maparesumo->getoperacaoini() : $notafiscal->getnumnotafis()),
			// Numero da nota fiscal (ultimo numero se for em lote)
			"08" => ($tabela == "M" ? $maparesumo->getoperacaofim() : $notafiscal->getnumnotafis()),
			// Estado (UF) do emitente/destinatario (estabelecimento)
			"09" => $this->estabelecimento->getuf(),
			// Codigo fiscal de operacao da nota fiscal
			"10" => ($tabela == "M" ? ($subst_trib ? "5405" : "5102") : substr(removeformat($notafiscal->getnatoperacao()), 0, 4)),
			// Codigo contabil utilizado para integracao contabil
			"11" => NULL,
			// Interestadual (Na saida informar "1" para notas fora do estado e nao contribuinte. Na entrada informar "2" para petroleo/energia)
			"12" => ($tabela == "M" ? NULL : ($operacaonota->gettipo() == "S" && $this->estabelecimento->getuf() == $cidade->getuf() ? NULL : "1")),
			// Codigo do municipio paulista, conforme publicado pelo estado de Sao Paulo
			"13" => NULL,
			// Frase para sair na observacao do livro (opcional)
			"14" => NULL,
			// Valor total da nota fiscal
			"15" => ($tabela == "M" ? $this->valor_decimal($totalliquido, 2) : $this->valor_decimal($notafiscal->gettotalliquido(), 2)),
			// Base de calculo de ICMS	[1]
			"16" => $this->valor_decimal($arr_tributacao[0]["base"], 2),
			// Aliquota de ICMS			[1]
			"17" => $this->valor_decimal($arr_tributacao[0]["aliquota"], 4),
			// Valor total do ICMS		[1]
			"18" => $this->valor_decimal($arr_tributacao[0]["valorimposto"], 2),
			// Valor isento de ICMS		[1]
			"19" => $this->valor_decimal($arr_tributacao[0]["isento"], 2),
			// Outros valores de ICMS	[1]
			"20" => $this->valor_decimal($arr_tributacao[0]["outros"], 2),
			// Tipo da nota fiscal		[1]
			"21" => NULL,
			// Base de calculo de ICMS	[2]
			"22" => $this->valor_decimal($arr_tributacao[1]["base"], 2),
			// Aliquota de ICMS			[2]
			"23" => $this->valor_decimal($arr_tributacao[1]["aliquota"], 4),
			// Valor total do ICMS		[2]
			"24" => $this->valor_decimal($arr_tributacao[1]["valorimposto"], 2),
			// Valor isento de ICMS		[2]
			"25" => $this->valor_decimal($arr_tributacao[1]["isento"], 2),
			// Outros valores de ICMS	[2]
			"26" => $this->valor_decimal($arr_tributacao[1]["outros"], 2),
			// Tipo da nota fiscal		[2]
			"27" => NULL,
			// Base de calculo de ICMS	[3]
			"28" => $this->valor_decimal($arr_tributacao[2]["base"], 2),
			// Aliquota de ICMS			[3]
			"29" => $this->valor_decimal($arr_tributacao[2]["aliquota"], 4),
			// Valor total do ICMS		[3]
			"30" => $this->valor_decimal($arr_tributacao[2]["valorimposto"], 2),
			// Valor isento de ICMS		[3]
			"31" => $this->valor_decimal($arr_tributacao[2]["isento"], 2),
			// Outros valores de ICMS	[3]
			"32" => $this->valor_decimal($arr_tributacao[2]["outros"], 2),
			// Tipo da nota fiscal		[3]
			"33" => NULL,
			// Base de calculo de ICMS	[4]
			"34" => $this->valor_decimal($arr_tributacao[3]["base"], 2),
			// Aliquota de ICMS			[4]
			"35" => $this->valor_decimal($arr_tributacao[3]["aliquota"], 4),
			// Valor total do ICMS		[4]
			"36" => $this->valor_decimal($arr_tributacao[3]["valorimposto"], 2),
			// Valor isento de ICMS		[4]
			"37" => $this->valor_decimal($arr_tributacao[3]["isento"], 2),
			// Outros valores de ICMS	[4]
			"38" => $this->valor_decimal($arr_tributacao[3]["outros"], 2),
			// Tipo da nota fiscal		[4]
			"39" => NULL,
			// Base de calculo de ICMS	[5]
			"40" => $this->valor_decimal($arr_tributacao[4]["base"], 2),
			// Aliquota de ICMS			[5]
			"41" => $this->valor_decimal($arr_tributacao[4]["aliquota"], 4),
			// Valor total do ICMS		[5]
			"42" => $this->valor_decimal($arr_tributacao[4]["valorimposto"], 2),
			// Valor isento de ICMS		[5]
			"43" => $this->valor_decimal($arr_tributacao[4]["isento"], 2),
			// Outros valores de ICMS	[5]
			"44" => $this->valor_decimal($arr_tributacao[4]["outros"], 2),
			// Tipo da nota fiscal		[5]
			"45" => NULL,
			// Valor da base de calculo de IPI
			"46" => ($tabela == "M" ? NULL : $this->valor_decimal($notafiscal->gettotalbruto() - $notafiscal->gettotaldesconto() + $notafiscal->gettotalacrescimo(), 2)),
			// Total de IPI
			"47" => ($tabela == "M" ? NULL : $this->valor_decimal($notafiscal->gettotalipi(), 2)),
			// Valor isento de IPI
			"48" => NULL,
			// Outros valores de IPI
			"49" => NULL,
			// Valor do IPI nao aproveitado (somente para industrias)
			"50" => NULL,
			// Valor da base do ICMS ST
			"51" => ($tabela == "M" ? NULL : $this->valor_decimal($notafiscal->gettotalbaseicmssubst(), 2)),
			// Valor do ICMS ST
			"52" => ($tabela == "M" ? NULL : $this->valor_decimal($notafiscal->gettotalicmssubst(), 2)),
			// Valor do PVV
			"53" => NULL,
			// Valor do desconto
			"54" => ($tabela == "M" ? $this->valor_decimal($maparesumo->gettotaldescontocupom() + $maparesumo->gettotaldescontoitem(), 2) : $this->valor_decimal($notafiscal->gettotaldesconto(), 2)),
			// Valor do abatimento
			"55" => NULL,
			// Valor a vista
			"56" => NULL,
			// Valor a prazo
			"57" => NULL,
			// Vencimento
			"58" => NULL,
			// Valor isento de PIS/Cofins
			"59" => NULL,
			// CPF/CNPJ do parceiro
			"60" => removeformat($parceiro->getcpfcnpj()),
			// Incricao estaducal do parceiro
			"61" => ($parceiro->gettppessoa() == "J" ? removeformat($parceiro->getrgie()) : NULL),
			// Incricao municipal do parceiro
			"62" => NULL,
			// Razao social do parceiro
			"63" => $parceiro->getrazaosocial(),
			// Endereco do parceiro
			"64" => ($parceiro->gettablename() == "cliente" ? $parceiro->getenderres() : $parceiro->getendereco()),
			// Numero do endereco do parceiro
			"65" => ($parceiro->gettablename() == "cliente" ? $parceiro->getnumerores() : $parceiro->getnumero()),
			// Complemento do endereco do parceiro
			"66" => ($parceiro->gettablename() == "cliente" ? $parceiro->getcomplementores() : $parceiro->getcomplemento()),
			// Bairro do parceiro
			"67" => ($parceiro->gettablename() == "cliente" ? $parceiro->getbairrores() : $parceiro->getbairro()),
			// CEP do parceiro
			"68" => removeformat($parceiro->gettablename() == "cliente" ? $parceiro->getcepres() : $parceiro->getcep()),
			// Codigo oficial da cidade do parceiro
			"69" => $cidade->getcodoficial(),
			// Nome da cidade do parceiro
			"70" => $cidade->getnome(),
			// UF do parceiro
			"71" => $cidade->getuf(),
			// Pais do parceiro
			"72" => removeformat($parceiro->gettablename() == "fornecedor" ? $parceiro->getcodpais() : "01058"),
			// Inscricao SUFRAMA do parceiro
			"73" => NULL,
			// Conta contabil do parceio
			"74" => NULL
		);
	}

}