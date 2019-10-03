<?php

require_once("../def/require_php.php");

class Dominio{

	protected $con;
	protected $estabelecimento;
	protected $datainicial;
	protected $datafinal;
	protected $codbanco;
	protected $data_lancamento;
	protected $identificador;
	protected $arr_arquivo = array();
	protected $arr_lancamento;
	protected $arr_fiscal;
	protected $arr_linha = array();
	protected $gera_favorecido;
	protected $tipogeracao;
				function __construct($con){
		$this->con = $con;
	}

	public function gerar(){
		if($this->tipogeracao == "F"){
			$this->gerar_financeiro();
		}elseif($this->tipogeracao == "N"){
			$this->gerar_fiscal();
		}else{
			echo messagebox("alert", "", "Escolha o tipo da geração");
			die;
		}

	}

	protected function gerar_financeiro(){
		unlink($this->estabelecimento->getdircontabil()."{$this->identificador}dominio.txt");

		$dtalancamento = $this->data_lancamento == "R" ? "dtreconc" : "dtliquid";

		$query = "SELECT lancamento.pagrec, (lancamento.valorliquido - lancamento.valorjuros - lancamento.valoracresc) AS valorliquido, contalancto.nome AS contalancto, contaparceiro.contacontabil AS contaparceiro, ";
		$query .= "contabanco.contacontabil AS contabanco, contalancto.codhistorico AS historicolancto, lancamento.{$dtalancamento} AS dtliquid, v_parceiro.razaosocial AS parceiro, ";
		$query .= "lancamento.numnotafis, lancamento.serie, lancamento.valordescto, lancamento.valorjuros, ";
		$query .= "lancamento.valorabatimento, lancamento.valoracresc, ";
		$query .= "(CASE WHEN especie.especie = 'CH' THEN lancamento.docliquidacao ELSE '' END) AS numcheque ";
		$query .= "FROM lancamento ";
		$query .= "INNER JOIN v_parceiro ON (lancamento.tipoparceiro = v_parceiro.tipoparceiro AND lancamento.codparceiro = v_parceiro.codparceiro) ";
		$query .= "INNER JOIN banco ON (lancamento.codbanco = banco.codbanco) ";
		$query .= "LEFT JOIN planocontas AS contalancto ON (lancamento.codconta = contalancto.codconta) ";
		$query .= "LEFT JOIN planocontas AS contaparceiro ON (v_parceiro.codconta = contaparceiro.codconta) ";
		$query .= "LEFT JOIN planocontas AS contabanco ON (banco.codconta = contabanco.codconta) ";
		$query .= "LEFT JOIN especie ON (lancamento.codespecieliq = especie.codespecie) ";
		$query .= "WHERE true ";
		$query .= "AND lancamento.codestabelec = {$this->estabelecimento->getcodestabelec()} ";
		if(strlen($this->codbanco) > 0){
			$query .= " AND lancamento.codbanco = {$this->codbanco} ";
		}
		if(strlen($this->data_lancamento) > 0){
			if($this->data_lancamento == "R"){
				$query .= " AND lancamento.dtreconc >= '{$this->datainicial}' AND lancamento.dtreconc <= '{$this->datafinal}'  ";
			}else{
				$query .= " AND lancamento.dtliquid >= '{$this->datainicial}' AND lancamento.dtliquid <= '{$this->datafinal}'  ";
			}
		}
		$query .= "ORDER BY lancamento.dtliquid ";

		echo $query;
		$res = $this->con->query($query);
		$this->arr_lancamento = $res->fetchAll();

		$this->registro_0000();
		$this->registro_6000();
		return true;
	}

	protected function registro_0000(){
		$registro = array(
			"0000", // Registro fixo 6000
//			removeformat($this->estabelecimento->getcpfcnpj()) // D=Um débito p/vários créditos; C=Um crédito p/ vários débitos; X=Um débito p/ um crédito; V=Vários débitos p/ vários créditos.
			"45602315000147"// D=Um débito p/vários créditos; C=Um crédito p/ vários débitos; X=Um débito p/ um crédito; V=Vários débitos p/ vários créditos.
		);
		$this->escrever_registro($registro);
	}

	protected function registro_6000(){

		if($this->gera_favorecido){
			$this->registro_6100("X");
		}else{
			$registro = array(
				"6000", // Registro fixo 6000
				"D" // D=Um débito p/vários créditos; C=Um crédito p/ vários débitos; X=Um débito p/ um crédito; V=Vários débitos p/ vários créditos.
			);
			$this->escrever_registro($registro);
			$this->registro_6100("R");

			$registro = array(
				"6000", // Registro fixo 6000
				"C" // D=Um débito p/vários créditos; C=Um crédito p/ vários débitos; X=Um débito p/ um crédito; V=Vários débitos p/ vários créditos.
			);
			$this->escrever_registro($registro);
			$this->registro_6100("P");
		}
	}

	protected function registro_6100($pagrec){
		$paramplanodecontas = objectbytable("paramplanodecontas", $this->estabelecimento->getcodestabelec(), $this->con);

		$conta_descto_p = objectbytable("planocontas", $paramplanodecontas->getcodconta_valordescto_pag(), $this->con);
		$conta_acrescimo_p = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoracresc_pag(), $this->con);
		$conta_abatimento_p = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorabatimento_pag(), $this->con);
		$conta_juros_p = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorjuros_pag(), $this->con);

		$conta_descto_r = objectbytable("planocontas", $paramplanodecontas->getcodconta_valordescto_rec(), $this->con);
		$conta_acrescimo_r = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoracresc_rec(), $this->con);
		$conta_abatimento_r = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorabatimento_rec(), $this->con);
		$conta_juros_r = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorjuros_rec(), $this->con);

		foreach($this->arr_lancamento as $lancamento){
			if($lancamento["pagrec"] != $pagrec && $pagrec != "X"){
				continue;
			}

			if(strlen($lancamento["numnotafis"]) > 0){
				if(strlen($lancamento["serie"]) > 0){
					$nf = "NF: {$lancamento["numnotafis"]}-{$lancamento["serie"]}";
				}else{
					$nf = "NF: {$lancamento["numnotafis"]}";
				}
			}

			if(strlen(trim($lancamento["numcheque"])) > 0){
				$numcheque = "CH: {$lancamento["numcheque"]}";
			}else{
				$numcheque = "";
			}

			if($this->gera_favorecido){
				$hist = "$nf{$lancamento["parceiro"]}{$lancamento["historicolancto"]} {$numcheque}";
			}else{
				$hist = "$nf{$lancamento["historicolancto"]} {$numcheque}";
			}

			$hist = trim($hist);

			$lancamento["conta1"] = $lancamento["contaparceiro"];
			$lancamento["conta2"] = $lancamento["contabanco"];

			if(strlen($this->identificador) > 0){
				$hist .= "||".trim($this->identificador);
				$lancamento["conta1"] = trim($this->identificador).ltrim($lancamento["conta1"], 0);
			}

			if($pagrec == "X"){
				$registro = array(
					"6000", // Registro fixo 6000
					"X" // D=Um débito p/vários créditos; C=Um crédito p/ vários débitos; X=Um débito p/ um crédito; V=Vários débitos p/ vários créditos.
				);
				$this->escrever_registro($registro);

				if($lancamento["pagrec"] == "R"){
					$aux = "";
					$aux = $lancamento["conta1"];
					$lancamento["conta1"] = $lancamento["conta2"];
					$lancamento["conta2"] = $aux;
				}

				$registro = array(
					"6100", // Registro fixo 6100
					convert_date($lancamento["dtliquid"], "Y-m-d", "d/m/Y"), // Data do Lancamento
					$lancamento["conta1"], // Informar o código reduzido da conta contábil.
					$lancamento["conta2"], // Informar o código reduzido da conta contábil.
					number_format($lancamento["valorliquido"], "2", ",", ""), // Valor do lançamento
					"", // Informar código conforme registro 0220.
					$hist // Informar a descrição do histórico. Se for informado o código do histórico, preencher apenas o complemento.
				);
				$this->escrever_registro($registro);

				if($lancamento["pagrec"] == "R"){
					if($lancamento["valordescto"] > 0){
						$lancamento["conta2"] = $conta_descto_r->getcontacontabil();
						$registro = $this->gerarconta($lancamento, "Desconto $hist", $lancamento["valordescto"]);
					}
					if($lancamento["valoracresc"] > 0){
						$lancamento["conta1"] = $lancamento["contabanco"];
						$lancamento["conta2"] = $conta_acrescimo_r->getcontacontabil();
						$registro = $this->gerarconta($lancamento, "Acrescimo $hist", $lancamento["valoracresc"]);
					}
					if($lancamento["valorabatimento"] > 0){
						$lancamento["conta2"] = $conta_abatimento_r->getcontacontabil();
						$registro = $this->gerarconta($lancamento, "Abatimento $hist", $lancamento["valorabatimento"]);
					}
					if($lancamento["valorjuros"] > 0){
						$lancamento["conta1"] = $lancamento["contabanco"];
						$lancamento["conta2"] = $conta_juros_r->getcontacontabil();
						$registro = $this->gerarconta($lancamento, "Juros $hist", $lancamento["valorjuros"]);
					}
				}else{
					if($lancamento["valordescto"] > 0){
						$lancamento["conta2"] = $conta_descto_p->getcontacontabil();
						$registro = $this->gerarconta($lancamento, "Desconto $hist", $lancamento["valordescto"]);
					}
					if($lancamento["valoracresc"] > 0){
						$lancamento["conta1"] = $conta_acrescimo_p->getcontacontabil();
						$lancamento["conta2"] = $lancamento["contabanco"];
						$registro = $this->gerarconta($lancamento, "Acrescimo $hist", $lancamento["valoracresc"]);
					}
					if($lancamento["valorabatimento"] > 0){
						$lancamento["conta2"] = $conta_abatimento_p->getcontacontabil();
						$registro = $this->gerarconta($lancamento, "Abatimento $hist", $lancamento["valorabatimento"]);
					}
					if($lancamento["valorjuros"] > 0){
						$lancamento["conta1"] = $conta_juros_p->getcontacontabil();
						$lancamento["conta2"] = $lancamento["contabanco"];
						$registro = $this->gerarconta($lancamento, "Juros $hist", $lancamento["valorjuros"]);
					}
				}
			}else{
				if($lancamento["pagrec"] != $pagrec){
					continue;
				}

				if($pagrec == "R"){
					$registro = array(
						"6100", // Registro fixo 6100
						convert_date($lancamento["dtliquid"], "Y-m-d", "d/m/Y"), // Data do Lancamento
						$lancamento["contabanco"], // Informar o código reduzido da conta contábil.
						$lancamento["contaparceiro"], // Informar o código reduzido da conta contábil.
						number_format($lancamento["valorliquido"], "2", ",", ""), // Valor do lançamento
						"", // Informar código conforme registro 0220.
						$hist // Informar a descrição do histórico. Se for informado o código do histórico, preencher apenas o complemento.
					);

					if($lancamento["valordescto"] > 0){
						$registro = $this->gerarconta($lancamento, "Debito $hist", $conta_descto_r, $lancamento["valordescto"]);
					}
					if($lancamento["valoracresc"] > 0){
						$registro = $this->gerarconta($lancamento, "Acrescimo $hist", $conta_acrescimo_r, $lancamento["valoracresc"]);
					}
					if($lancamento["valorabatimento"] > 0){
						$registro = $this->gerarconta($lancamento, "Abatimento $hist", $conta_abatimento_r, $lancamento["valorabatimento"]);
					}
					if($lancamento["valorjuros"] > 0){
						$registro = $this->gerarconta($lancamento, "Juros $hist", $conta_juros_r, $lancamento["valorjuros"]);
					}
				}else{
					$registro = array(
						"6100", // Registro fixo 6100
						convert_date($lancamento["dtliquid"], "Y-m-d", "d/m/Y"), // Data do Lancamento
						$lancamento["contaparceiro"], // Informar o código reduzido da conta contábil.
						$lancamento["contabanco"], // Informar o código reduzido da conta contábil.
						number_format($lancamento["valorliquido"], "2", ",", ""), // Valor do lançamento
						"", // Informar código conforme registro 0220.
						$hist // Informar a descrição do histórico. Se for informado o código do histórico, preencher apenas o complemento.
					);
					if($lancamento["valordescto"] > 0){
						$registro = $this->gerarconta($lancamento, "Debito $hist", $conta_descto_p, $lancamento["valordescto"]);
					}
					if($lancamento["valoracresc"] > 0){
						$registro = $this->gerarconta($lancamento, "Acrescimo $hist", $conta_acrescimo_p, $lancamento["valoracresc"]);
					}
					if($lancamento["valorabatimento"] > 0){
						$registro = $this->gerarconta($lancamento, "Abatimento $hist", $conta_abatimento_p, $lancamento["valorabatimento"]);
					}
					if($lancamento["valorjuros"] > 0){
						$registro = $this->gerarconta($lancamento, "Juros $hist", $conta_juros_p, $lancamento["valorjuros"]);
					}
				}
				$this->escrever_registro($registro);
			}
		}
		$fp = fopen($this->estabelecimento->getdircontabil()."{$this->identificador}dominio.txt", "w");
		fwrite($fp, implode("", $this->arr_linha));
		fclose($fp);
	}

	protected function gerar_fiscal(){
		unlink($this->estabelecimento->getdircontabil()."{$this->identificador}dominio.txt");

		$query  = "(SELECT operacaonota.tipo as entsai,  contanatprincipal.contacontabil AS contanatprincipal, contanat.contacontabil AS contanat, contaparceiro.contacontabil AS contaparceiro, ";
		$query .= "contaestabelec.contacontabil AS contaestabelec, contanat.codhistorico AS historicolancto, notafiscal.dtentrega, notafiscal.dtemissao, ";
		$query .= "v_parceiro.razaosocial AS parceiro, notafiscal.numnotafis, notafiscal.serie, notafiscal.operacao, ";
		$query .= "notafiscal.bonificacao, ";
		$query .= "SUM(itnotafiscal.totalacrescimo + itnotafiscal.totalfrete) AS valoracresc, SUM(itnotafiscal.totaldesconto) AS valordescto,  SUM(itnotafiscal.totalliquido) AS valorliquido ";
		$query .= "FROM notafiscal ";
		$query .= "INNER JOIN itnotafiscal ON (notafiscal.idnotafiscal = itnotafiscal.idnotafiscal) ";
		$query .= "INNER JOIN estabelecimento ON (notafiscal.codestabelec = estabelecimento.codestabelec) ";
		$query .= "INNER JOIN v_parceiro ON (notafiscal.tipoparceiro = v_parceiro.tipoparceiro AND notafiscal.codparceiro = v_parceiro.codparceiro) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN natoperacao ON (itnotafiscal.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN natoperacaoestab ON (natoperacao.natoperacao = natoperacaoestab.natoperacao AND natoperacaoestab.codestabelec = notafiscal.codestabelec) ";
		$query .= "LEFT JOIN planocontas AS contanatprincipal ON (natoperacao.codconta = contanatprincipal.codconta) ";
		$query .= "LEFT JOIN planocontas AS contanat ON (natoperacaoestab.codconta = contanat.codconta) ";
		$query .= "LEFT JOIN planocontas AS contaparceiro ON (v_parceiro.codconta = contaparceiro.codconta) ";
		$query .= "LEFT JOIN planocontas AS contaestabelec ON (estabelecimento.codconta = contaestabelec.codconta) ";
		$query .= " WHERE notafiscal.operacao != 'TS' ";
		$query .= " AND notafiscal.status = 'A' ";
		$query .= " AND notafiscal.codestabelec = {$this->estabelecimento->getcodestabelec()} ";
		$query .= "	AND ((notafiscal.operacao IN ('CP','DF','TE') AND notafiscal.dtentrega BETWEEN '".$this->datainicial."' AND '".$this->datafinal."') ";
		$query .= "	OR  (notafiscal.operacao NOT IN ('CP','DF','TE') AND notafiscal.dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."')) ";
		$query .= "	AND itnotafiscal.composicao IN ('N','P') ";
		$query .= "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13 ";
		$query .= "ORDER BY 1 ";

		$query .= ") UNION ALL (";

		$query .= "select (CASE WHEN substr(itnotadiversa.natoperacao,1,1) = '2' THEN 'E' ELSE 'S' END) AS entsai, '' AS contanatprincipal, contanat.contacontabil AS contanat, ";
		$query .= "contaparceiro.contacontabil AS contaparceiro, contaestabelec.contacontabil AS contaestabelec, ";
		$query .= "contanat.codhistorico AS historicolancto, notadiversa.dtemissao as dtentrega, notadiversa.dtemissao, ";
		$query .= "v_parceiro.razaosocial AS parceiro, notadiversa.numnotafis, notadiversa.serie, 'ND' AS operacao, ";
		$query .= "'N' AS bonificacao, ";
		$query .= "0 AS valoracresc, ";
		$query .= "0 AS valordescto, itnotadiversa.totalliquido AS valorliquido ";
		$query .= "FROM notadiversa ";
		$query .= "INNER JOIN itnotadiversa ON (notadiversa.idnotadiversa = itnotadiversa.idnotadiversa) ";
		$query .= "INNER JOIN estabelecimento ON (notadiversa.codestabelec = estabelecimento.codestabelec) ";
		$query .= "INNER JOIN v_parceiro ON (notadiversa.tipoparceiro = v_parceiro.tipoparceiro AND notadiversa.codparceiro = v_parceiro.codparceiro) ";
		$query .= "INNER JOIN natoperacao ON (itnotadiversa.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN natoperacaoestab ON (natoperacao.natoperacao = natoperacaoestab.natoperacao AND natoperacaoestab.codestabelec = notadiversa.codestabelec) ";
		$query .= "LEFT JOIN planocontas AS contanat ON (natoperacaoestab.codconta = contanat.codconta) ";
		$query .= "LEFT JOIN planocontas AS contaparceiro ON (v_parceiro.codconta = contaparceiro.codconta) ";
		$query .= "LEFT JOIN planocontas AS contaestabelec ON (estabelecimento.codconta = contaestabelec.codconta) ";
		$query .= "WHERE TRUE ";
		$query .= "AND notadiversa.codestabelec = {$this->estabelecimento->getcodestabelec()} ";
		$query .= "	AND (notadiversa.dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."') ";

		$query .= ") UNION ALL (";

		$query .= "select 'S' AS entsai, '' AS contanatprincipal, contanat.contacontabil AS contanat, ";
		$query .= "contaparceiro.contacontabil AS contaparceiro, contaestabelec.contacontabil AS contaestabelec, ";
		$query .= "contanat.codhistorico AS historicolancto, notafrete.dtemissao as dtentrega, notafrete.dtemissao, ";
		$query .= "v_parceiro.razaosocial AS parceiro, notafrete.numnotafis, notafrete.serie, 'TR' AS operacao, ";
		$query .= "'N' AS bonificacao, ";
		$query .= "0 AS valoracresc, ";
		$query .= "0 AS valordescto, notafrete.totalliquido AS valorliquido ";
		$query .= "FROM notafrete ";
		$query .= "INNER JOIN estabelecimento ON (notafrete.codestabelec = estabelecimento.codestabelec) ";
		$query .= "INNER JOIN v_parceiro ON ('T' = v_parceiro.tipoparceiro AND notafrete.codtransp = v_parceiro.codparceiro) ";
		$query .= "INNER JOIN natoperacao ON (notafrete.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN natoperacaoestab ON (natoperacao.natoperacao = natoperacaoestab.natoperacao AND natoperacaoestab.codestabelec = notafrete.codestabelec) ";
		$query .= "LEFT JOIN planocontas AS contanat ON (natoperacaoestab.codconta = contanat.codconta) ";
		$query .= "LEFT JOIN planocontas AS contaparceiro ON (v_parceiro.codconta = contaparceiro.codconta) ";
		$query .= "LEFT JOIN planocontas AS contaestabelec ON (estabelecimento.codconta = contaestabelec.codconta) ";
		$query .= "WHERE TRUE ";
		$query .= "AND notafrete.codestabelec = {$this->estabelecimento->getcodestabelec()} ";
		$query .= "	AND (notafrete.dtemissao BETWEEN '".$this->datainicial."' AND '".$this->datafinal."') ";
		$query .= ") ";

		echo $query;
		$res = $this->con->query($query);
		$this->arr_fiscal = $res->fetchAll();

		$this->registro_0000();
		foreach($this->arr_fiscal AS $fiscal){
			$this->registrofiscal_6000($fiscal);
		}

		$fp = fopen($this->estabelecimento->getdircontabil()."{$this->identificador}dominio.txt", "w");
		fwrite($fp, implode("", $this->arr_linha));
		fclose($fp);

		return true;
	}

	protected function registrofiscal_6000($fiscal){

		$registro = array(
			"6000", // Registro fixo 6000
			"X" // D=Um débito p/vários créditos; C=Um crédito p/ vários débitos; X=Um débito p/ um crédito; V=Vários débitos p/ vários créditos.
		);
		$this->escrever_registro($registro);
		$this->registrofiscal_6100($fiscal);
	}

	protected function registrofiscal_6100($fiscal){

		$hist = "NF: {$fiscal["numnotafis"]}-{$fiscal["serie"]} {$fiscal["parceiro"]}";

		if(in_array($fiscal["operacao"], array("CP","PR","ND","TR"))){
			$fiscal["dtfiscal"] = $fiscal["dtentrega"];
			$fiscal["conta1"] = $fiscal["contanat"];
			$fiscal["conta2"] = $fiscal["contaparceiro"];
			$fiscal["conta3"] = $fiscal["contaestabelec"];
			if(strlen($fiscal["conta3"]) > 0 && $fiscal["bonificacao"] == "S"){
				$fiscal["conta1"] = $fiscal["contanatprincipal"];
				$fiscal["conta2"] = $fiscal["contanat"];
			}
			if(strlen($this->identificador) > 0){
				$fiscal["conta2"] = trim($this->identificador).ltrim($fiscal["conta2"], 0);
			}
		}elseif(in_array($fiscal["operacao"], array("TS"))){
			$fiscal["dtfiscal"] = $fiscal["dtentrega"];
			$fiscal["conta1"] = $fiscal["contaestabelec"];
			$fiscal["conta2"] = $fiscal["contanat"];
		}elseif(in_array($fiscal["operacao"], array("TE"))){
			$fiscal["dtfiscal"] = $fiscal["dtentrega"];
			$fiscal["conta1"] = $fiscal["contaestabelec"];
			$fiscal["conta2"] = $fiscal["contanat"];
		}else{
			$fiscal["dtfiscal"] = $fiscal["dtemissao"];
			$fiscal["conta1"] = $fiscal["contaparceiro"];
			$fiscal["conta2"] = $fiscal["contanat"];
			if(strlen($this->identificador) > 0 && !in_array($fiscal["operacao"], array("DF"))){
				$fiscal["conta2"] = trim($this->identificador).ltrim($fiscal["conta2"], 0);
			}elseif(strlen($this->identificador) > 0 && in_array($fiscal["operacao"], array("DF"))){
				$fiscal["conta1"] = trim($this->identificador).ltrim($fiscal["conta1"], 0);
			}

		}

		$registro = array(
			"6100", // Registro fixo 6100
			convert_date($fiscal["dtfiscal"], "Y-m-d", "d/m/Y"), // Data do Lancamento
			$fiscal["conta1"], // Informar o código reduzido da conta contábil, debito.
			$fiscal["conta2"], // Informar o código reduzido da conta contábil, credito.
			number_format($fiscal["valorliquido"], "2", ",", ""), // Valor do lançamento
			"", // Informar código conforme registro 0220.
			$hist // Informar a descrição do histórico. Se for informado o código do histórico, preencher apenas o complemento.
		);

		$this->escrever_registro($registro);
	}

	private function escrever_registro($registro){
		if(strlen($this->identificador) > 0 && $registro[0] == "6100"){
//			sem pipe no final
			$this->arr_linha[] = "|".implode("|", array_map("trim", $registro))."\r\n";
		}else{
			$this->arr_linha[] = "|".implode("|", array_map("trim", $registro))."|\r\n";
		}
	}

	public function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	public function setdatainicial($data){
		$this->datainicial = $data;
	}

	public function setdatafinal($data){
		$this->datafinal = $data;
	}

	public function setcodbanco($data){
		$this->codbanco = $data;
	}

	public function setidentificador($data){
		$this->identificador = $data;
	}

	public function setdata_lancamento($data){
		$this->data_lancamento = $data;
	}

	public function setgera_favorecido($gera_favorecido){
		$this->gera_favorecido = $gera_favorecido;
	}

	public function settipogeracao($tipogeracao){
		$this->tipogeracao = $tipogeracao;
	}

	protected function gerarconta($lancamento, $hist, $valor){
		$registro = array(
			"6100", // Registro fixo 6100
			convert_date($lancamento["dtliquid"], "Y-m-d", "d/m/Y"), // Data do Lancamento
			$lancamento["conta1"], // Informar o código reduzido da conta contábil.
			$lancamento["conta2"], // Informar o código reduzido da conta contábil.
			number_format($valor, "2", ",", ""), // Valor do lançamento
			"", // Informar código conforme registro 0220.
			$hist // Informar a descrição do histórico. Se for informado o código do histórico, preencher apenas o complemento.
		);
		$this->escrever_registro($registro);
	}

}