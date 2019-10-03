<?php

require_once("../def/require_php.php");

class Prosoft{

	protected $con;
	protected $estabelecimento;
	protected $dtmovto1;
	protected $dtmovto2;
	protected $linha_arquivo = array();
	protected $primeirodigito;
	protected $identifiestab;
	protected $codbanco;

	function __construct($con){
		$this->con = $con;
	}

	public function setdtmovto1($dtmovto1){
		$this->dtmovto1 = $dtmovto1;
	}

	public function setdtmovto2($dtmovto2){
		$this->dtmovto2 = $dtmovto2;
	}

	public function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	public function setprimeirodigito($primeirodigito){
		$this->primeirodigito = $primeirodigito;
	}

	public function setidentifiestab($identifiestab){
		$this->identifiestab = $identifiestab;
	}

	public function setcodbanco($codbanco){
		$this->codbanco = $codbanco;
	}

	public function setdata_lancamento($data_lancamento){
		$this->data_lancamento = $data_lancamento;
	}

	public function gerar_lancamento($forcar = false){
		$this->linha_arquivo = array();
		setprogress(0, "Gerando Lancamentos", TRUE);

		if(strlen($this->estabelecimento->getdircontabil()) == 0){
			echo messagebox("error", "Diretorio nao informado", "Diretorio nao informado para o estabelecimento ".$estabelecimento["nome"]);
			die();
		}

		$query = "SELECT ";
		$query .= "(lancamento.valorparcela - lancamento.valordescto) AS valorliquido, lancamento.numnotafis, lancamento.serie, lancamento.codestabelec, ";
		$query .= " ".($this->data_lancamento == "L" ? " CASE WHEN dtreconc IS NOT NULL THEN dtreconc ELSE dtliquid END " : "dtreconc")." as dtliquid, ";
		$query .= "conta_lancto.contacontabil as conta_parceiro, conta_banco.contacontabil as conta_banco,lancamento.tipoparceiro, lancamento.codparceiro, ";
		$query .= "lancamento.pagrec as operacao, lancamento.docliquidacao, ";
		$query .= "lancamento.valordescto, lancamento.valoracresc, lancamento.valorabatimento,  ";
		$query .= "lancamento.docliquidacao, lancamento.valorpago, lancamento.valorjuros, lancamento.valorparcela ";
		$query .= "FROM lancamento ";
		$query .= "LEFT JOIN banco ON lancamento.codbanco = banco.codbanco ";
		$query .= "INNER JOIN planocontas AS conta_banco ON conta_banco.codconta = banco.codconta ";
		$query .= "INNER JOIN planocontas AS conta_lancto ON conta_lancto.codconta = lancamento.codconta ";
		$query .= "LEFT JOIN notafiscal ON lancamento.idnotafiscal = notafiscal.idnotafiscal ";
		$query .= "WHERE lancamento.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfinan = ".$this->estabelecimento->getcodestabelec().")  AND lancamento.status IN ('L','R') AND lancamento.codconta IS NOT NULL ";
		if($this->data_lancamento == "R"){
			$query .= "AND lancamento.dtreconc >= '".$this->dtmovto1."' AND lancamento.dtreconc <= '".$this->dtmovto2."' ";
		}else{
			$query .= "AND lancamento.dtliquid >= '".$this->dtmovto1."' AND lancamento.dtliquid <= '".$this->dtmovto2."' ";
		}
		if(strlen($this->codbanco) > 0){
			$query .= " AND lancamento.codbanco = ".$this->codbanco." ";
		}
		echo "Lancamentos <br>".$query;
		$res = $this->con->query($query);
		$arr_lancamento = $res->fetchAll(2);
		$cnt = 0;
		$paramplanodecontas = objectbytable("paramplanodecontas", $this->estabelecimento->getcodestabelec(), $this->con);

		$count = count($arr_lancamento);
		foreach($arr_lancamento as $i => $r){
			setprogress(($i / $count * 100), "Lançamentos: ".$i." de ".$count);

			if($r["operacao"] == "P"){
				$conta_descto = objectbytable("planocontas", $paramplanodecontas->getcodconta_valordescto_pag(), $this->con);
				$conta_acrescimo = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoracresc_pag(), $this->con);
				$conta_abatimento = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorabatimento_pag(), $this->con);
				$conta_juros = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorjuros_pag(), $this->con);
				$conta_acresc = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoracresc_pag(), $this->con);
				$conta_icms = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoricms_pag(), $this->con);
			}else{
				$conta_descto = objectbytable("planocontas", $paramplanodecontas->getcodconta_valordescto_rec(), $this->con);
				$conta_acrescimo = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoracresc_rec(), $this->con);
				$conta_abatimento = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorabatimento_rec(), $this->con);
				$conta_juros = objectbytable("planocontas", $paramplanodecontas->getcodconta_valorjuros_rec(), $this->con);
				$conta_acresc = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoracresc_rec(), $this->con);
				$conta_icms = objectbytable("planocontas", $paramplanodecontas->getcodconta_valoricms_rec(), $this->con);
			}

			$sql = "";
			if($r["tipoparceiro"] == "A"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome, contacontabil ";
				$sql .= "FROM administradora as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codadminist = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "C"){
				$sql = "SELECT ";
				$sql .= "parceiro.razaosocial as nome, contacontabil ";
				$sql .= "FROM cliente as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codcliente = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "E"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome, contacontabil ";
				$sql .= "FROM estabelecimento as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codestabelec = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "F"){
				$sql = "SELECT ";
				$sql .= "parceiro.razaosocial as nome, contacontabil ";
				$sql .= "FROM fornecedor as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codfornec = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "U"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome, contacontabil ";
				$sql .= "FROM funcionario as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codfunc = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "T"){
				$sql = "SELECT ";
				$sql .= "parceiro.razaosocial as nome, contacontabil ";
				$sql .= "FROM transportadora as parceiro ";
				$sql .= "LEFT JOIN planocontas ON CAST(parceiro.codconta AS integer) = planocontas.codconta ";
				$sql .= "WHERE codtransp = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "R"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome ";
				$sql .= "FROM representante as parceiro ";
				$sql .= "WHERE codrepresentante = ".$r["codparceiro"];
			}
			$conta_credito = "";
			$conta_debito = "";
			$result_sql = $this->con->query($sql);
			$r_sql = $result_sql->fetch(2);
			$nome_parceiro = $r_sql["nome"];

			if($r["operacao"] == "R"){
				$conta_credito = $r["conta_parceiro"];
				$conta_debito = $r["conta_banco"];
			}else{
				$conta_credito = $r["conta_banco"];
				$conta_debito = $r["conta_parceiro"];
			}
			if((strlen($conta_debito) > 0 && strlen($conta_credito) > 0) || $forcar){
				$planocontas_credito = objectbytable("planocontas", null, $this->con);
				$planocontas_credito->setcontacontabil($conta_credito);
				$planocontas_credito = object_array($planocontas_credito);
				$planocontas_credito = $planocontas_credito[0];
				if(strlen($planocontas_credito->getcodhistorico()) > 0){
					$historicopadrao_credito = objectbytable("historicopadrao", $planocontas_credito->getcodhistorico(), $this->con);
					$historicopadrao_texto = $historicopadrao_credito->getdescricao();
				}

				$cnt++;
				$linha = "LC1"; // Tipo
				$linha .= str_pad($cnt, 5, "0", STR_PAD_LEFT); // Ordem
				$linha .= "   "; // Filler
				$linha .= "1"; // Modo do lançamento
				$linha .= str_pad(implode("", array_reverse(explode("-", $r["dtliquid"]))), 8, "0", STR_PAD_RIGHT); // Data
				$linha .= str_pad($r["numnotafis"], 10, "0", STR_PAD_LEFT); // Número do documento
				$linha .= "00000"; // Número do lote
				$linha .= str_pad(" ", 30, " ", STR_PAD_RIGHT); // Origem do Lançamento
				$linha .= "000"; // Quantidade de Contas
				//$linha .= str_pad(substr(str_replace(".","",$conta_debito),0,5),5,"0",STR_PAD_LEFT); // Conta Débito - Código Acesso
				if(strlen($this->identifiestab) > 0){
					$conta_debito = $this->identifiestab.substr($this->contacontabil($conta_debito), 1, 4);
				}
				$linha .= $this->contacontabil($conta_debito);
				$linha .= str_pad(" ", 14, " ", STR_PAD_RIGHT); // Conta Débito - Terceiro
				$linha .= "00000"; // Conta Crédito - C/Custo
				//$linha .= str_pad(substr(str_replace(".","",$conta_credito),0,5),5,"0",STR_PAD_LEFT); // Conta Crédito - Código Acesso
				$linha .= $this->contacontabil($conta_credito); // Conta Crédito - Código Acesso
				$linha .= str_pad(" ", 14, " ", STR_PAD_RIGHT); // Conta Crédito - Terceiro
				$linha .= "00000"; // Conta Crédito - C/Custo
				$linha .= str_pad(number_format($r["valorliquido"], 2, ".", ""), 16, " ", STR_PAD_LEFT); // Valor do lançamento
				if(strlen($historicopadrao_texto) == "0"){
					$historicopadrao_texto = "NF N.o";
				}
				$linha .= $historicopadrao_texto.substr(str_pad($r["numnotafis"], 8, " ", STR_PAD_LEFT), 0, 8); // Histórico
				$linha .= str_pad((strlen($r["docliquidacao"]) > 0 ? " ".$r["docliquidacao"] : " "), 15, " ", STR_PAD_RIGHT); // Histórico
				$linha .= strtoupper(str_pad($nome_parceiro, 210, " ", STR_PAD_RIGHT)); // Nome Parceiro
				$linha .= " "; // Indicador de Conciliação - Débito
				$linha .= " "; // Indicador de Conciliação - Crédito
				$linha .= str_pad(" ", 74, " ", STR_PAD_RIGHT);
				$this->linha_arquivo[] = substr($linha, 0, 449);

				if($r["valordescto"] > 0){
					$cnt++;
					$linha_aux = substr_replace($linha, str_pad($cnt, 5, "0", STR_PAD_LEFT), 3, 5);
					$linha_aux = substr_replace($linha_aux, str_pad($this->contacontabil($conta_debito), 5, "0", STR_PAD_LEFT), 68, 5);
					$linha_aux = substr_replace($linha_aux, str_pad($this->contacontabil($conta_descto->getcontacontabil()), 5, "0", STR_PAD_LEFT), 92, 5);
					$linha_aux = substr_replace($linha_aux, str_pad(number_format($r["valordescto"], 2, ".", ""), 16, " ", STR_PAD_LEFT), 116, 16);
					if(strlen($conta_descto->getcodhistorico()) > 0){
						$historicopadrao_credito = objectbytable("historicopadrao", $conta_descto->getcodhistorico(), $this->con);
						$linha_aux = str_replace($historicopadrao_texto, $historicopadrao_credito->getdescricao(), $linha_aux);
					}
					$this->linha_arquivo[] = substr($linha_aux, 0, 449);
				}
				if($r["totalicms"] > 0){
					$cnt++;
					$linha_aux = substr_replace($linha, str_pad($cnt, 5, "0", STR_PAD_LEFT), 3, 5);
					$linha_aux = substr_replace($linha_aux, str_pad($this->contacontabil($conta_icms->getcontacontabil()), 5, "0", STR_PAD_LEFT), 68, 5);
					$linha_aux = substr_replace($linha_aux, str_pad(number_format($r["totalicms"], 2, ".", ""), 16, " ", STR_PAD_LEFT), 116, 16);
					if(strlen($conta_icms->getcodhistorico()) > 0){
						$historicopadrao_icms = objectbytable("historicopadrao", $conta_icms->getcodhistorico(), $this->con);
						$linha_aux = str_replace($historicopadrao_texto, $historicopadrao_icms->getdescricao(), $linha_aux);
					}
					$this->linha_arquivo[] = substr($linha_aux, 0, 449);
				}
				if($r["valorabatimento"] > 0){
					$cnt++;
					$linha_aux = substr_replace($linha, str_pad($cnt, 5, "0", STR_PAD_LEFT), 3, 5);
					$linha_aux = substr_replace($linha_aux, str_pad($this->contacontabil($conta_abatimento->getcontacontabil()), 5, "0", STR_PAD_LEFT), 68, 5);
					$linha_aux = substr_replace($linha_aux, str_pad(number_format($r["valorabatimento"], 2, ".", ""), 16, " ", STR_PAD_LEFT), 116, 16);
					if(strlen($conta_abatimento->getcodhistorico()) > 0){
						$historicopadrao_abatimento = objectbytable("historicopadrao", $conta_abatimento->getcodhistorico(), $this->con);
						$linha_aux = str_replace($historicopadrao_texto, $historicopadrao_abatimento->getdescricao(), $linha_aux);
					}
					$this->linha_arquivo[] = substr($linha_aux, 0, 449);
				}
				if($r["valorjuros"] > 0){
					$cnt++;
					$linha_aux = substr_replace($linha, str_pad($cnt, 5, "0", STR_PAD_LEFT), 3, 5);
					$linha_aux = substr_replace($linha_aux, str_pad($this->contacontabil($conta_juros->getcontacontabil()), 5, "0", STR_PAD_LEFT), 68, 5);
					$linha_aux = substr_replace($linha_aux, str_pad(number_format($r["valorjuros"], 2, ".", ""), 16, " ", STR_PAD_LEFT), 116, 16);
					if(strlen($conta_juros->getcodhistorico()) > 0){
						$historicopadrao_abatimento = objectbytable("historicopadrao", $conta_juros->getcodhistorico(), $this->con);
						$linha_aux = str_replace($historicopadrao_texto, $historicopadrao_abatimento->getdescricao(), $linha_aux);
					}
					$this->linha_arquivo[] = substr($linha_aux, 0, 449);
				}
				if($r["valoracresc"] > 0){
					$cnt++;
					$linha_aux = substr_replace($linha, str_pad($cnt, 5, "0", STR_PAD_LEFT), 3, 5);
					$linha_aux = substr_replace($linha_aux, str_pad($this->contacontabil($conta_acresc->getcontacontabil()), 5, "0", STR_PAD_LEFT), 68, 5);
					$linha_aux = substr_replace($linha_aux, str_pad(number_format($r["valoracresc"], 2, ".", ""), 16, " ", STR_PAD_LEFT), 116, 16);
					if(strlen($conta_acresc->getcodhistorico()) > 0){
						$historicopadrao_abatimento = objectbytable("historicopadrao", $conta_acresc->getcodhistorico(), $this->con);
						$linha_aux = str_replace($historicopadrao_texto, $historicopadrao_abatimento->getdescricao(), $linha_aux);
					}
					$this->linha_arquivo[] = substr($linha_aux, 0, 449);
				}
			}
		}
		$fp = fopen($this->estabelecimento->getdircontabil()."/CTBLCTOS".str_pad($this->estabelecimento->getcodestabelec(), 4, "0", STR_PAD_LEFT).".txt", "w");
		fwrite($fp, implode("\r\n", $this->linha_arquivo));
		fclose($fp);
	}

	public function gerar_notafiscal($forcar = false){
		$this->linha_arquivo = array();
		setprogress(0, "Gerando Fiscal", TRUE);

		if(strlen($this->estabelecimento->getdircontabil()) == 0){
			echo messagebox("error", "Diretorio nao informado", "Diretorio nao informado para o estabelecimento ".$estabelecimento["nome"]);
			die();
		}

		$query = "(SELECT ";
		$query .= "SUM(itnotafiscal.totalliquido) as valorliquido, notafiscal.numnotafis, notafiscal.serie, notafiscal.dtentrega as dtliquid, "; //conta_imposto.contacontabil as conta_imposto, ";
		$query .= "conta_estoque_1.contacontabil as conta_estoque_1,conta_estoque_2.contacontabil as conta_estoque_2, notafiscal.tipoparceiro, notafiscal.codparceiro, operacaonota.tipo as operacao, SUM(itnotafiscal.totalicms) AS totalicms, ";
		$query .= "SUM(itnotafiscal.totalacrescimo) AS totalacrescimo, SUM(itnotafiscal.totaldesconto) AS totaldesconto, operacaonota.operacao AS op, ";
		$query .= "notafiscal.natoperacao ";
		$query .= "FROM notafiscal ";
		$query .= "INNER JOIN itnotafiscal ON (notafiscal.idnotafiscal = itnotafiscal.idnotafiscal)";
		$query .= "LEFT JOIN natoperacao ON itnotafiscal.natoperacao = natoperacao.natoperacao ";
		$query .= "LEFT JOIN operacaonota ON notafiscal.operacao = operacaonota.operacao ";
		$query .= "LEFT JOIN natoperacaoestab ON natoperacaoestab.natoperacao = itnotafiscal.natoperacao AND natoperacaoestab.codestabelec = notafiscal.codestabelec ";
		$query .= "LEFT JOIN planocontas AS conta_estoque_1 ON conta_estoque_1.codconta = ";
		$query .= "CASE WHEN operacaonota.operacao = 'TE' THEN (SELECT codconta FROM estabelecimento WHERE codestabelec = notafiscal.codparceiro LIMIT 1) ";
		$query .= "WHEN operacaonota.operacao = 'TS' THEN (SELECT codconta FROM natoperacaoestab WHERE codestabelec = notafiscal.codestabelec AND natoperacao = notafiscal.natoperacao LIMIT 1) ";
		$query .= "WHEN operacaonota.operacao = 'TS' THEN (SELECT codconta FROM natoperacaoestab WHERE codestabelec = notafiscal.codestabelec AND natoperacao = notafiscal.natoperacao LIMIT 1) ";
//		$query .= "WHEN notafiscal.bonificacao = 'S' THEN (SELECT codconta FROM natoperacao WHERE natoperacao = notafiscal.natoperacao LIMIT 1) ";
		$query .= "ELSE (CASE WHEN natoperacaoestab.codconta > 0 THEN natoperacaoestab.codconta ELSE natoperacao.codconta END) END ";
		$query .= "LEFT JOIN planocontas AS conta_estoque_2 ON conta_estoque_2.codconta = ";
		$query .= "CASE WHEN operacaonota.operacao = 'TE' THEN (SELECT codconta FROM natoperacaoestab WHERE codestabelec = notafiscal.codparceiro AND natoperacao = notafiscal.natoperacao LIMIT 1) ";
		$query .= "WHEN operacaonota.operacao = 'TS' OR notafiscal.bonificacao = 'S' THEN (SELECT codconta FROM natoperacaoestab WHERE codestabelec = notafiscal.codestabelec AND natoperacao = notafiscal.natoperacao LIMIT 1) ";
		//$query .= "WHEN THEN (SELECT codconta FROM natoperacao WHERE natoperacao = notafiscal.natoperacao LIMIT 1) ";
		$query .= "ELSE natoperacaoestab.codconta END ";
		$query .= "WHERE notafiscal.status = 'A' AND notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") AND ";
		$query .= "((notafiscal.dtentrega >= '".$this->dtmovto1."' AND notafiscal.dtentrega <= '".$this->dtmovto2."' AND notafiscal.operacao IN ('CP','DF','TE')) OR ";
		$query .= "(notafiscal.dtemissao >= '".$this->dtmovto1."' AND notafiscal.dtemissao <= '".$this->dtmovto2."' AND notafiscal.operacao NOT IN ('CP','DF','TE'))) ";
		$query .= " AND operacaonota.operacao != 'TS' AND itnotafiscal.composicao != 'F'  ";
		$query .= "GROUP BY  numnotafis, serie, dtliquid, conta_estoque_1, conta_estoque_2, tipoparceiro, codparceiro, operacaonota.tipo ,operacaonota.operacao, notafiscal.natoperacao ";
		$query .= "ORDER BY operacaonota.operacao, dtliquid, numnotafis) ";

		$query .= " UNION ALL ";

		$query .= "(SELECT notadiversa.totalliquido, notadiversa.numnotafis, notadiversa.serie, notadiversa.dtemissao as dtliquid, ";
		$query .= "conta_estoque_1.contacontabil as conta_estoque_1,conta_estoque_2.contacontabil as conta_estoque_2, notadiversa.tipoparceiro, ";
		$query .= "notadiversa.codparceiro, operacaonota.tipo as operacao, SUM(itnotadiversa.totalicms) AS totalicms, 0 AS totalacrescimo, 0 AS totaldesconto, 'ND' AS op, ";
		$query .= "notadiversa.natoperacao ";
		$query .= "FROM notadiversa ";
		$query .= "INNER JOIN itnotadiversa ON notadiversa.idnotadiversa = itnotadiversa.idnotadiversa ";
		$query .= "INNER JOIN v_parceiro ON (notadiversa.codparceiro = v_parceiro.codparceiro AND notadiversa.tipoparceiro = v_parceiro.tipoparceiro) ";
		$query .= "LEFT JOIN natoperacao ON notadiversa.natoperacao = natoperacao.natoperacao ";
		$query .= "LEFT JOIN operacaonota ON 'ND' = operacaonota.operacao ";
		$query .= "LEFT JOIN natoperacaoestab ON natoperacaoestab.natoperacao = notadiversa.natoperacao AND natoperacaoestab.codestabelec = notadiversa.codestabelec ";
		$query .= "LEFT JOIN planocontas AS conta_estoque_1 ON conta_estoque_1.codconta = (SELECT codconta FROM natoperacaoestab WHERE codestabelec = notadiversa.codestabelec AND natoperacao = notadiversa.natoperacao LIMIT 1) ";
		$query .= "LEFT JOIN planocontas AS conta_estoque_2 ON conta_estoque_2.codconta = v_parceiro.codconta ";
		$query .= "WHERE notadiversa.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") AND notadiversa.dtemissao >= '".$this->dtmovto1."' AND notadiversa.dtemissao <= '".$this->dtmovto2."' ";
		$query .= "GROUP BY  1,2,3,4,5,6,7,8,9,11,12,13,14) ";

		$query .= " UNION ALL";

		$query .= "(SELECT totalliquido as valorliquido, notafrete.numnotafis, notafrete.serie, notafrete.dtemissao as dtliquid, ";
		$query .= "conta_estoque_1.contacontabil as conta_estoque_1,conta_estoque_2.contacontabil as conta_estoque_2, 'T' AS tipoparceiro, ";
		$query .= "notafrete.codtransp AS codparceiro, 'E' as operacao, notafrete.totalicms, 0 AS totalacrescimo, 0 AS totaldesconto, '' AS op, ";
		$query .= "notafrete.natoperacao ";
		$query .= "FROM notafrete ";
		$query .= "LEFT JOIN transportadora ON notafrete.codtransp = transportadora.codtransp ";
		$query .= "LEFT JOIN natoperacao ON notafrete.natoperacao = natoperacao.natoperacao ";
		$query .= "LEFT JOIN operacaonota ON 'ND' = operacaonota.operacao ";
		$query .= "LEFT JOIN natoperacaoestab ON natoperacaoestab.natoperacao = notafrete.natoperacao AND natoperacaoestab.codestabelec = notafrete.codestabelec ";
		$query .= "LEFT JOIN planocontas AS conta_estoque_1 ON conta_estoque_1.codconta = natoperacao.codconta ";
		$query .= "LEFT JOIN planocontas AS conta_estoque_2 ON conta_estoque_2.codconta = natoperacaoestab.codconta ";
		$query .= "WHERE notafrete.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") AND notafrete.dtentrega >= '".$this->dtmovto1."' AND notafrete.dtentrega <= '".$this->dtmovto2."') ";
		echo $query;
		$res = $this->con->query($query);
		$results = $res->fetchAll(2);
		$cnt = 0;

		$count = count($results);
		foreach($results as $i => $r){
			setprogress(($i / $count * 100), "Notas: ".$i." de ".$count);
			$sql = "";
			if($r["tipoparceiro"] == "A"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome, contacontabil ";
				$sql .= "FROM administradora as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codadminist = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "C"){
				$sql = "SELECT ";
				$sql .= "parceiro.razaosocial as nome, contacontabil ";
				$sql .= "FROM cliente as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codcliente = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "E"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome, contacontabil ";
				$sql .= "FROM estabelecimento as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codestabelec = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "F"){
				$sql = "SELECT ";
				$sql .= "parceiro.razaosocial as nome, contacontabil ";
				$sql .= "FROM fornecedor as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codfornec = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "U"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome, contacontabil ";
				$sql .= "FROM funcionario as parceiro ";
				$sql .= "LEFT JOIN planocontas ON parceiro.codconta = planocontas.codconta ";
				$sql .= "WHERE codfunc = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "T"){
				$sql = "SELECT ";
				$sql .= "parceiro.razaosocial as nome, contacontabil ";
				$sql .= "FROM transportadora as parceiro ";
				$sql .= "LEFT JOIN planocontas ON CAST(parceiro.codconta AS integer) = planocontas.codconta ";
				$sql .= "WHERE codtransp = ".$r["codparceiro"];
			}elseif($r["tipoparceiro"] == "R"){
				$sql = "SELECT ";
				$sql .= "parceiro.nome ";
				$sql .= "FROM representante as parceiro ";
				$sql .= "WHERE codrepresentante = ".$r["codparceiro"];
			}
			$conta_credito = "";
			$conta_debito = "";
			$result_sql = $this->con->query($sql);
			$r_sql = $result_sql->fetch(2);
			$nome_parceiro = $r_sql["nome"];

			if(in_array($r["op"], array("TE", "TS", "ND")) || substr($r["natoperacao"], 0, 5) == "1.910"){
				$conta_debito = $r["conta_estoque_1"];
				$conta_credito = $r["conta_estoque_2"];
			}else{
				if($r["operacao"] == "S"){
					$conta_debito = $r_sql["contacontabil"];
					if(strlen($r["conta_estoque_2"]) > 0){
						$conta_credito = $r["conta_estoque_2"];
					}else{
						$conta_credito = $r["conta_estoque_1"];
					}
				}else{
					$conta_credito = $r_sql["contacontabil"];
					if(strlen($r["conta_estoque_2"]) > 0){
						$conta_debito = $r["conta_estoque_2"];
					}else{
						$conta_debito = $r["conta_estoque_1"];
					}
				}
			}

			if((strlen($conta_debito) > 0 && strlen($conta_credito) > 0) || $forcar){
				$planocontas_credito = objectbytable("planocontas", null, $this->con);
				$planocontas_credito->setcontacontabil($conta_credito);
				$planocontas_credito = object_array($planocontas_credito);
				$planocontas_credito = $planocontas_credito[0];
				if(strlen($planocontas_credito->getcodhistorico()) > 0){
					$historicopadrao_credito = objectbytable("historicopadrao", $planocontas_credito->getcodhistorico(), $this->con);
					$historicopadrao_texto = substr($historicopadrao_credito->getdescricao(), 0, 7);
				}

				$cnt++;
				$linha = "LC1"; // Tipo
				$linha .= str_pad($cnt, 5, "0", STR_PAD_LEFT); // Ordem
				$linha .= "   "; // Filler
				$linha .= "1"; // Modo do lançamento
				$linha .= str_pad(implode("", array_reverse(explode("-", $r["dtliquid"]))), 8, "0", STR_PAD_RIGHT); // Data
				$linha .= str_pad($r["numnotafis"], 10, "0", STR_PAD_LEFT); // Número do documento
				$linha .= "00000"; // Número do lote
				$linha .= str_pad(" ", 30, " ", STR_PAD_RIGHT); // Origem do Lançamento
				$linha .= "000"; // Quantidade de Contas
				//$linha .= str_pad(substr(str_replace(".","",$conta_debito),0,5),5,"0",STR_PAD_LEFT); // Conta Débito - Código Acesso
				if(strlen($this->identifiestab) > 0){
					$conta_credito = $this->identifiestab.substr($this->contacontabil($conta_credito), 1, 4);
				}
				$linha .= $this->contacontabil($conta_debito);
				$linha .= str_pad(" ", 14, " ", STR_PAD_RIGHT); // Conta Débito - Terceiro
				$linha .= "00000"; // Conta Crédito - C/Custo
				//$linha .= str_pad(substr(str_replace(".","",$conta_credito),0,5),5,"0",STR_PAD_LEFT); // Conta Crédito - Código Acesso
				$linha .= $this->contacontabil($conta_credito);
				$linha .= str_pad(" ", 14, " ", STR_PAD_RIGHT); // Conta Crédito - Terceiro
				$linha .= "00000"; // Conta Crédito - C/Custo
				$linha .= str_pad(number_format($r["valorliquido"], 2, ".", ""), 16, " ", STR_PAD_LEFT); // Valor do lançamento
				if(strlen($historicopadrao_texto) == "0"){
					$historicopadrao_texto = "NF N.o";
				}
				$linha .= $historicopadrao_texto." ".substr(str_pad($r["numnotafis"], 8, " ", STR_PAD_LEFT), 0, 8); // Histórico
				$linha .= str_pad(" ", 15, " ", STR_PAD_RIGHT); // Histórico
				$linha .= strtoupper(str_pad($nome_parceiro, 210, " ", STR_PAD_RIGHT)); // Nome Parceiro
				$linha .= " "; // Indicador de Conciliação - Débito
				$linha .= " "; // Indicador de Conciliação - Crédito
				$linha .= str_pad(" ", 74, " ", STR_PAD_RIGHT);
				$this->linha_arquivo[] = $linha;
			}
		}
		$fp = fopen($this->estabelecimento->getdircontabil()."/CTBLCTOS".str_pad($this->estabelecimento->getcodestabelec(), 4, "0", STR_PAD_LEFT)."1.txt", "w");
		fwrite($fp, implode("\r\n", $this->linha_arquivo));
		fclose($fp);
	}

	public function financeiro_analitico_sac(){
		setprogress(0, "Gerando Analitico", TRUE);

		$query = "SELECT banco.codoficial, lancamento.docliquidacao, planocontas.contacontabil, lancamento.pagrec, ";
		$query .= "lancamento.numnotafis, lancamento.codconta, lancamento.valorliquido, historicopadrao.descricao AS historico, ";
		$query .= "lancamento.dtliquid, planocontasparceiro.contacontabil AS contacontabilparceiro, historicopadraoparceiro.descricao AS historicoparceiro  ";
		$query .= "FROM lancamento ";
		$query .= "INNER JOIN v_parceiro ON (lancamento.tipoparceiro = v_parceiro.tipoparceiro AND lancamento.codparceiro = v_parceiro.codparceiro) ";
		$query .= "INNER JOIN banco ON (lancamento.codbanco = banco.codbanco) ";
		$query .= "INNER JOIN planocontas ON (banco.codconta = planocontas.codconta) ";
		$query .= "INNER JOIN planocontas AS planocontasparceiro ON (v_parceiro.codconta = planocontasparceiro.codconta) ";
		$query .= "LEFT JOIN historicopadrao ON ((CASE WHEN lancamento.pagrec = 'P' THEN planocontas.codhistorico ELSE planocontas.codhistoricodeb END) = historicopadrao.codhistorico) ";
		$query .= "LEFT JOIN historicopadrao as historicopadraoparceiro  ON ((CASE WHEN lancamento.pagrec = 'P' THEN planocontasparceiro.codhistorico ELSE planocontasparceiro.codhistoricodeb END) = historicopadraoparceiro.codhistorico) ";
		$query .= "WHERE lancamento.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfinan = ".$this->estabelecimento->getcodestabelec().")  AND lancamento.status IN ('L','R') ";
		if($this->data_lancamento == "R"){
			$query .= "AND lancamento.dtreconc >= '".$this->dtmovto1."' AND lancamento.dtreconc <= '".$this->dtmovto2."' ";
		}else{
			$query .= "AND lancamento.dtliquid >= '".$this->dtmovto1."' AND lancamento.dtliquid <= '".$this->dtmovto2."' ";
		}
		$query .= "ORDER BY lancamento.pagrec DESC,banco.codoficial, lancamento.docliquidacao, planocontas.contacontabil ";

		$res = $this->con->query($query);
		$arr_lancamento = $res->fetchAll();

		$arr_ctbimpor = array();
		$arr_ctblmult = array();
		$lancamento_total = array();
		$quebra = "";
		$seq = 0;

		setprogress(0, "Calculando Totais dos Lançamentos", TRUE);
		foreach($arr_lancamento AS $lancamento){
			if($quebra != $lancamento["codoficial"].$lancamento["docliquidacao"].$lancamento["contacontabil"].$lancamento["pagrec"]){
				$quebra = $lancamento["codoficial"].$lancamento["docliquidacao"].$lancamento["contacontabil"].$lancamento["pagrec"];
				$seq++;
				$lancamento_total[$seq] += $lancamento["valorliquido"];
			}else{
				$lancamento_total[$seq] += $lancamento["valorliquido"];
			}
		}

		$seq = 0;
		$count = count($arr_lancamento);

		foreach($arr_lancamento AS $i => $lancamento){
			setprogress(($i / $count * 100), "Lançamentos Analitico: ".$i." de ".$count);
			if($quebra != $lancamento["codoficial"].$lancamento["docliquidacao"].$lancamento["contacontabil"].$lancamento["pagrec"]){
				$quebra = $lancamento["codoficial"].$lancamento["docliquidacao"].$lancamento["contacontabil"].$lancamento["pagrec"];
				$seq++;

				if($lancamento["pagrec"] == "P"){
					$conta_debito = "?????";
					$conta_credito = $lancamento["contacontabil"];
				}else{
					$conta_debito = $lancamento["contacontabil"];
					$conta_credito = "?????";
				}

				$linha_ctbimpor = convert_date($lancamento["dtliquid"], "Y-m-d", "ymd"); //	  CFIELD("L_DATA"  ,"C", 6,0)
				$linha_ctbimpor .= $this->texto($lancamento["numnotafis"], 6, "0", "E"); //CFIELD("L_NF"    ,"C",If(Pers("CONFIANCA"),7,6),0)
				$linha_ctbimpor .= $this->texto($conta_debito, 5); //CFIELD("L_DEBI"  ,"C",Tm,0)
				$linha_ctbimpor .= $this->texto($conta_credito, 5); //CFIELD("L_CRED"  ,"C",Tm,0)
				if($lancamento["pagrec"] == "P"){
					$linha_ctbimpor .= $this->texto("N/CHQ No. ".$lancamento["docliquidacao"], 90); //CFIELD("L_HIST"  ,"C",If(Pers("CONFIANCA"),100,90),0)
				}else{
					$linha_ctbimpor .= $this->texto("Creditos de Cobranca. ".$lancamento["docliquidacao"], 90); //CFIELD("L_HIST"  ,"C",If(Pers("CONFIANCA"),100,90),0)
				}

				$linha_ctbimpor .= $this->texto(number_format($lancamento_total[$seq], 2, ".", ""), 14, "0", "E"); //CFIELD("L_VALOR" ,"N",Tm,2)
				$linha_ctbimpor .= $this->texto($seq, 6, "0", "E"); //CFIELD("L_VALORS","C",14,0)
				$arr_ctbimpor[] = $linha_ctbimpor;

				$linha_ctblmult = $this->texto($seq, 6, "0", "E"); //CFIELD("L_SEQ"   ,"C",6,0)
				$linha_ctblmult .= $this->texto($lancamento["contacontabilparceiro"], 5); //CFIELD("L_DEBI"  ,"C",If(Pers("CONFIANCA"),10,5),0)
				$linha_ctblmult .= $this->texto($lancamento["historicoparceiro"]." ".$lancamento["numnotafis"], 240); //CFIELD("L_HIST"  ,"C",240,0)
				$linha_ctblmult .= $this->texto(number_format($lancamento["valorliquido"], 2, ".", ""), 17, "0", "E"); //CFIELD("L_VALOR" ,"N",If(Pers("CONFIANCA"),15,14),2)
				$arr_ctblmult[] = $linha_ctblmult;
			}else{
				$linha_ctblmult = $this->texto($seq, 6, "0", "E"); //CFIELD("L_SEQ"   ,"C",6,0)
				$linha_ctblmult .= $this->texto($lancamento["contacontabilparceiro"], 5); //CFIELD("L_DEBI"  ,"C",If(Pers("CONFIANCA"),10,5),0)
				$linha_ctblmult .= $this->texto($lancamento["historicoparceiro"]." ".$lancamento["numnotafis"], 240); //CFIELD("L_HIST"  ,"C",240,0)
				$linha_ctblmult .= $this->texto(number_format($lancamento["valorliquido"], 2, ".", ""), 17, "0", "E"); //CFIELD("L_VALOR" ,"N",If(Pers("CONFIANCA"),15,14),2)
				$arr_ctblmult[] = $linha_ctblmult;
			}
		}

		$this->escreve_arquivo($this->estabelecimento->getdircontabil()."/CTBIMPOR.".str_pad($this->estabelecimento->getcodestabelec(), 3, "0", STR_PAD_LEFT), $arr_ctbimpor);
		$this->escreve_arquivo($this->estabelecimento->getdircontabil()."/CTBLMULT.".str_pad($this->estabelecimento->getcodestabelec(), 3, "0", STR_PAD_LEFT), $arr_ctblmult);

		return true;
	}

	public function financeiro_analitico(){
		$arr_linha = array();

		setprogress(0, "Gerando Analitico", TRUE);

		$query  = "SELECT banco.codoficial, lancamento.docliquidacao, planocontas.contacontabil, lancamento.pagrec, ";
		$query .= "CASE WHEN lancamento.numnotafis::text IS NULL THEN lancamento.referencia ELSE lancamento.numnotafis::text END AS numnotafis, ";
		$query .= "lancamento.codconta, lancamento.valorliquido, historicopadrao.descricao AS historico, ";
		$query .= "lancamento.dtliquid, planocontasparceiro.contacontabil AS contacontabilparceiro, historicopadraoparceiro.descricao AS historicoparceiro  ";

		$query .= "FROM lancamento ";
		$query .= "INNER JOIN v_parceiro ON (lancamento.tipoparceiro = v_parceiro.tipoparceiro AND lancamento.codparceiro = v_parceiro.codparceiro) ";
		$query .= "INNER JOIN banco ON (lancamento.codbanco = banco.codbanco) ";
		$query .= "INNER JOIN planocontas ON (banco.codconta = planocontas.codconta) ";
		$query .= "INNER JOIN planocontas AS planocontasparceiro ON (v_parceiro.codconta = planocontasparceiro.codconta) ";
		$query .= "LEFT JOIN historicopadrao ON ((CASE WHEN lancamento.pagrec = 'R' THEN planocontas.codhistorico ELSE planocontas.codhistoricodeb END) = historicopadrao.codhistorico) ";
		$query .= "LEFT JOIN historicopadrao as historicopadraoparceiro  ON ((CASE WHEN lancamento.pagrec = 'R' THEN planocontasparceiro.codhistorico ELSE planocontasparceiro.codhistoricodeb END) = historicopadraoparceiro.codhistorico) ";
		$query .= "WHERE lancamento.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfinan = ".$this->estabelecimento->getcodestabelec().")  AND lancamento.status IN ('L','R') ";
		if($this->data_lancamento == "R"){
			$query .= "AND lancamento.dtreconc >= '".$this->dtmovto1."' AND lancamento.dtreconc <= '".$this->dtmovto2."' ";
		}else{
			$query .= "AND lancamento.dtliquid >= '".$this->dtmovto1."' AND lancamento.dtliquid <= '".$this->dtmovto2."' ";
		}
		$query .= "ORDER BY lancamento.pagrec DESC,banco.codoficial, lancamento.docliquidacao, planocontas.contacontabil ";
		echo $query;
		$res = $this->con->query($query);
		$arr_lancamento = $res->fetchAll();

		$lancamento_total = array();
		$quebra = "";
		$seq = 0;

		setprogress(0, "Calculando Totais dos Lançamentos", TRUE);
		foreach($arr_lancamento AS $lancamento){
			if($quebra != $lancamento["codoficial"].$lancamento["docliquidacao"].$lancamento["contacontabil"].$lancamento["pagrec"].$lancamento["dtliquid"]){
				$sub = 0;
				$quebra = $lancamento["codoficial"].$lancamento["docliquidacao"].$lancamento["contacontabil"].$lancamento["pagrec"].$lancamento["dtliquid"];
				$seq++;
				$lancamento_total[$seq][$sub]["valorliquido"] = $lancamento["valorliquido"];
				$lancamento_total[$seq][$sub]["totalseq"] = 2;
			}else{
				if($lancamento_total[$seq][$sub]["totalseq"] == 200){
					$sub = 1;
					$seq++;
					$lancamento_total[$seq][$sub]["valorliquido"] = 0;
					$lancamento_total[$seq][$sub]["totalseq"] = 1;
				}
				$lancamento_total[$seq][$sub]["valorliquido"] += $lancamento["valorliquido"];
				$lancamento_total[$seq][$sub]["totalseq"]++;
			}
		}

		$quebra = "";
		$seq = 0;
		$sub = 0;
		foreach($arr_lancamento AS $i => $lancamento){
			setprogress(($i / $count * 100), "Lançamentos Analitico: ".$i." de ".$count);
			$aux_quebra = $lancamento["codoficial"].$lancamento["docliquidacao"].$lancamento["contacontabil"].$lancamento["pagrec"].$lancamento["dtliquid"];

			if($sub_seq == 199){
				$quebra="";
			}

			if($quebra != $aux_quebra){
				$quebra = $aux_quebra;

				if($seq != 0){
					if($pagrec == "P"){
						$pagrec = "R";
						$aux_lancamento  = array("valorliquido" => $lancamento_total[$seq][$sub]["valorliquido"],"historicoparceiro"=>"N/CHQ No.".$docliquidacao,"pagrec"=>$pagrec);
					}else{
						$pagrec = "P";
						$aux_lancamento  = array("valorliquido" => $lancamento_total[$seq][$sub]["valorliquido"],"historicoparceiro"=>"Creditos de Cobranca","pagrec"=>$pagrec);
					}
					$sub_seq++;
					if($sub_seq == 200){
						$sub = 1;
					}else{
						$sub = 0;
					}

					$arr_linha[] = $this->lc2($seq,$sub_seq,$aux_lancamento,$contacontabil);
				}

				$sub_seq = 1;
				$pagrec = $lancamento["pagrec"];
				$docliquidacao = $lancamento["docliquidacao"];

				$seq++;

				$contacontabil = $lancamento["contacontabil"];
				$contacontabilparceiro = $lancamento["contacontabilparceiro"];

				$linha = "LC1"; // 01 Tipo 1 3 X Constante "LC1"
				$linha .= $this->texto($seq, 5, "0", "E"); // 02 Ordem 4 5 N *
				$linha .= $this->texto(" ", 3); // 03 FILLER 9 3 X Brancos
				$linha .= "2"; // 04 Modo do Lançamento 12 1 XN "1" - Simples	// "2" - Detalhado
				$linha .= convert_date($lancamento["dtliquid"], "Y-m-d", "dmY"); // 05 Data da Escrituração 13 8 DATA DDMMAAAA
				$linha .= $this->texto($lancamento["numnotafis"], 10, "0", "E"); // 06 Número do Documento 21 10 X
				$linha .= $this->texto(0, 5, "0", "E"); // 07 Número do Lote 31 5 N
				$linha .= $this->texto("", 30); // 08 Origem do Lançamento 36 30 X
				$linha .= $this->texto($lancamento_total[$seq][$sub]["totalseq"], 3, "0", "E"); // 09 Quantidade de Contas 66 3 N Utilizar somente p/ modo 2
				$linha .= $this->texto("", 5); // 10 Conta Débito - Código Acesso 69 5 N
				$linha .= $this->texto("", 14); // 11 Conta Débito - Terceiro 74 14 X Código interno c/ 6 dígitos

				$arr_linha[] = $linha;

				$arr_linha[] = $this->lc2($seq,$sub_seq,$lancamento,$contacontabilparceiro);
			}else{
				$sub_seq++;
				$arr_linha[] = $this->lc2($seq,$sub_seq,$lancamento,$lancamento["contacontabilparceiro"]);
			}

			if(($i+1) == count($arr_lancamento)){
				if($pagrec == "P"){
					$pagrec = "R";
					$aux_lancamento  = array("valorliquido" => $lancamento_total[$seq][$sub]["valorliquido"],"historicoparceiro"=>"N/CHQ No.".$docliquidacao,"pagrec"=>$pagrec);
				}else{
					$pagrec = "P";
					$aux_lancamento  = array("valorliquido" => $lancamento_total[$seq][$sub]["valorliquido"],"historicoparceiro"=>"Creditos de Cobranca","pagrec"=>$pagrec);
				}
				$sub_seq++;
				$arr_linha[] = $this->lc2($seq,$sub_seq,$aux_lancamento,$contacontabil);
			}
		}
		$filename = $this->estabelecimento->getdircontabil()."/CTBLCTOS".str_pad($this->estabelecimento->getcodestabelec(), 4, "0", STR_PAD_LEFT).".txt";
		$this->escreve_arquivo($filename, $arr_linha);

		return true;
	}

	public function gerar_estoque(){
		$dtini = $this->dtmovto1;
		$dtfim = $this->dtmovto2;

		if(true){
			$query_custosemimp = "CAST(produto.custorep AS numeric(12,2)) ";
		}elseif(param("NOTAFISCAL","CUSTOSPED",$this->con) == 1){
			$query_custosemimp = "CAST(produtoestab.custosemimp AS numeric(12,2)) ";
		}else{
			$query_custosemimp  = " (SELECT custosemimp FROM produtoestabsaldo ps WHERE data BETWEEN '".$dtini."' AND '".$dtfim."'";
			$query_custosemimp .= " AND ps.codproduto = produtoestab.codproduto AND ps.codestabelec = produtoestab.codestabelec LIMIT 1)::numeric(12,2) ";
		}

		$query  = "SELECT produto.descricaofiscal, produtoestab.codproduto, saldo(produtoestab.codestabelec,produtoestab.codproduto,'".$dtfim."') AS saldo, ";
		$query .= $query_custosemimp." AS custo, ncm.codigoncm ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "INNER JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "WHERE codestabelec = {$this->estabelecimento->getcodestabelec()} ";

		$res = $this->con->query($query);
		$arr_estoque = $res->fetchAll(2);

		$arr_linha = array();
		foreach($arr_estoque AS $estoque){
			if($estoque["saldo"] <= 0){
				continue;
			}
			$linha  = convert_date($dtfim,"Y-m-d","dmY"); // 1-8 Data do inventario
			$linha .= convert_date($dtini,"Y-m-d","my"); // 9-4 Mes ano inicial referencia
			$linha .= convert_date($dtfim,"Y-m-d","my"); // 13-4 Mes ano final referencia
			$linha .= $this->valor_numerico($estoque["codproduto"],0,20); // 17-20 Codigo do produto da empresa
			$linha .= "1"; // 37-1 Situacao do produto
			$linha .= $this->valor_texto(" ",14); // 38-14 CPF/CNPJ de terceiro
			$linha .= $this->valor_texto(" ",20); // 52-20 Inscricao estadual de terceiro
			$linha .= $this->valor_texto(" ",2); // 72-2 UF do terceiro
			$linha .= $this->valor_texto(" ",5); // 74-5 Filler
			$linha .= $this->valor_numerico($estoque["saldo"],6,16); // 79-16 Quantidade
			$linha .= $this->valor_numerico($estoque["custo"],4,17); // 95-17 valor unitario
			$linha .= $this->valor_numerico($estoque["custo"]*$estoque["saldo"],2,17); // 112-17 Quantidade
			$linha .= $this->valor_numerico(0,2,17); // 129-17 ICMS a recuperar
			$linha .= $this->valor_texto(" ",60); // 146-60 Observacao
			$linha .= $this->valor_texto($estoque["descricaofiscal"],80); // 206-80 Descricao do produto
			$linha .= $this->valor_numerico(0,0,4); // 286-4 Grupo de produto
			$linha .= $this->valor_texto(removeformat($estoque["codigoncm"]),10); // 290-10 Classificacao fiscal ncm
			$linha .= $this->valor_texto(" ",30); // 300-30 Reservado
			$linha .= $this->valor_texto("UN",3); // 330-3 Unidade de medida
			$linha .= $this->valor_texto(" ",30); // 333-30 Descricao grupo produto
			$linha .= $this->valor_texto("UN",6); // 363-6 Unidade de medida
			$linha .= $this->valor_numerico($estoque["custorep"],2,17); // 369-17 Valor do item para imposto de renda
			$arr_linha[] = $linha;
		}
		$filename = "../temp/localfile/PROSOFT_ESTOQUE.txt";
		$texto =  implode("\\r\\n",$arr_linha);
		echo script("download('$texto',\"prosoft_estoque.txt\",\"text/plain\");");
		$this->escreve_arquivo($filename, $arr_linha);
	}


	function valor_numerico($numero,$decimais,$tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero,$decimais,".","");
		$numero = substr($numero,0,$tamanho);
		$numero = str_pad($numero,$tamanho,"0",STR_PAD_LEFT);
		return $numero;
	}

	function valor_texto($texto,$tamanho){
		$texto = str_replace(array("\r","\n","'","§","º")," ",$texto); //Campo observacao
		$texto = substr($texto,0,$tamanho);
		$texto = str_pad($texto,$tamanho," ",STR_PAD_RIGHT);
		return $texto;
	}

	function contacontabil($value){
		$value = str_replace("-", "", $value);
		$value = str_replace(".", "", $value);
		$value = substr($value, 0, 5);
		$value = str_pad($value, 5, "0", STR_PAD_LEFT);
		return $value;
	}

	function texto($value, $tamanho, $complemento = " ", $esqdir = "D"){
		$value = substr($value, 0, $tamanho);
		$value = str_pad($value, $tamanho, $complemento, $esqdir == "E" ? STR_PAD_LEFT : STR_PAD_RIGHT);
		return $value;
	}

	function escreve_arquivo($arquivo, $texto){
		$fp = fopen($arquivo, "w");
		fwrite($fp, implode("\r\n", $texto));
		fclose($fp);
	}

	function lc2($seq,$sub_seq,$lancamento,$contacontabil){
		$linha  = "LC2"; // 01 Tipo 1 3 X Constante "LC2"
		$linha .= $this->texto($seq, 5, "0", "E"); // 02 Ordem 4 5 N *
		$linha .= $this->texto($sub_seq, 3, "0", "E"); // 03 C/Partida Número 9 3 N Sequencial de 001 até 200
		$linha .= $lancamento["pagrec"] == "R" ? "C" : "D"; // 04 Débito ou Crédito 12 1 X "D" ou "C"
		$linha .= $this->texto($contacontabil, 5); // 05 Código de Acesso 13 5 N
		$linha .= $this->texto(" ", 14); // 06 Código do Terceiro 18 14 X Código interno c/ 6 dígitos // ou CNPJ/CPF
		$linha .= $this->texto(0, 5, "0", "E"); // 07 Código do C/Custo 32 5 N
		$linha .= $this->texto(number_format($lancamento["valorliquido"],2,".",""), 16, " ", "E"); // 08 Valor 37 16 R$
		$linha .= $this->texto($lancamento["historicoparceiro"]." ".$lancamento["numnotafis"], 240); // 09 Histórico 53 240 X
		$linha .= $this->texto(" ", 1); // 10 Indicador Conciliação 293 1 X " " - Não conciliado	// "C" - Conciliado	// "P" - Pendente
		$linha .= $this->texto(" ", 49); // 11 FILLER 294 49 X Brancos
		return $linha;
	}
}