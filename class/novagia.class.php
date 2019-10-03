<?php

class NovaGia{

	private $con;
	private $arr_arquivo;
	private $arr_apurnatoperacao;
	private $arr_apurnatoperacaoestado;
	private $datainicial;
	private $datafinal;
	private $codestabelec;
	private $creditoicms;
	private $creditoicmsst;

	private $arquivo;

	function __construct($con){
		$this->con = $con;
	}

	function gerar(){
		$query = "SELECT substr(natoperacao,1,5) AS natoperacao, entsai, coalesce(uf,'SP') AS uf,contribuinteicms, SUM(totalliquido) AS totalliquido, SUM(totalbaseicms) aS totalbaseicms, ";
		$query .= "SUM(totalicms) AS totalicms, SUM(totalbaseisento) as totalbaseisento, SUM(totalbaseoutras) AS totalbaseoutras, ";
		$query .= "SUM(totalipi) AS totalipi, SUM(totalicmssubst) AS totalicmssubst ";
		$query .= "FROM( ";
		$query .= "SELECT natoperacao,uf,contribuinteicms, CASE WHEN (substr(natoperacao,1,1) = '2' AND contribuinteicms = 'N') THEN SUM(0) ELSE SUM(totalliquido) END AS totalliquido, ";
		$query .= "CASE WHEN (substr(natoperacao,1,1) = '2' AND contribuinteicms = 'N') THEN SUM(0) ELSE SUM(totalbaseicms) END AS totalbaseicms, ";
		$query .= "entsai, SUM(totalicms) AS totalicms, SUM(totalbaseisento) AS totalbaseisento, SUM(totalbaseoutras + totalicmssubst) AS totalbaseoutras, ";
		$query .= "SUM(totalipi) AS totalipi, SUM(totalicmssubst) AS totalicmssubst ";
		$query .= "FROM v_apuracao_natoperacao ";
		$query .= "LEFT JOIN v_parceiro ON (v_apuracao_natoperacao.tipoparceiro = v_parceiro.tipoparceiro AND v_apuracao_natoperacao.codparceiro = v_parceiro.codparceiro) ";
		$query .= "WHERE codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) AND ";
		$query .= "dtmovto >= '{$this->datainicial}' AND dtmovto <= '{$this->datafinal}' ";
		$query .= "GROUP BY 1,2,3, entsai,v_apuracao_natoperacao.totalbaseicms,v_apuracao_natoperacao.totalbaseisento ";
		$query .= "ORDER BY entsai,natoperacao ) AS tmp ";
		$query .= "GROUP by 2,1,3,4 ";
		$query .= "ORDER BY 1,3 ";

		echo $query;
		$res = $this->con->query($query);
		$arr_apurnatoperacao = $res->fetchAll();

		foreach($arr_apurnatoperacao AS $apurnatoperacao){
			$this->arr_apurnatoperacaoestado[$apurnatoperacao["natoperacao"]][$apurnatoperacao["uf"]][] = $apurnatoperacao;

			if(count($this->arr_apurnatoperacao[$apurnatoperacao["natoperacao"]]) == 0){
				$this->arr_apurnatoperacao[$apurnatoperacao["natoperacao"]] = $apurnatoperacao;
			}else{
				$apurnatoperacao_tot = $this->arr_apurnatoperacao[$apurnatoperacao["natoperacao"]];
				$apurnatoperacao_tot["totalliquido"] += $apurnatoperacao["totalliquido"];
				$apurnatoperacao_tot["totalbaseicms"] += $apurnatoperacao["totalbaseicms"];
				$apurnatoperacao_tot["totalicms"] += $apurnatoperacao["totalicms"];
				$apurnatoperacao_tot["totalbaseisento"] += $apurnatoperacao["totalbaseisento"];
				$apurnatoperacao_tot["totalbaseoutras"] += $apurnatoperacao["totalbaseoutras"];
				$apurnatoperacao_tot["totalipi"] += $apurnatoperacao["totalipi"];

				$this->arr_apurnatoperacao[$apurnatoperacao["natoperacao"]] = $apurnatoperacao_tot;
			}
		}

		$nome_arquivo = "novagia_{$this->codestabelec}_{$this->datainicial}.prf";

		$this->arquivo = fopen("../temp/upload/$nome_arquivo", "w+");

		$this->gerar_registroCR01(); // Registro Mestre
		$this->gerar_registroCR05(); // Cabeçalho do Documento Fiscal
		$this->gerar_registroCR10(); // Detalhes CFOPs
//		$this->gerar_registroCR14(); // Detalhes Interestaduais
//		$this->gerar_registroCR18(); // ZFM/ALC Operação interestadual com CFOP do grupo 6 isento
//		$this->gerar_registroCR20(); // ZFM/ALC Operação interestadual com CFOP do grupo 6 isento
//		$this->gerar_registroCR28(); // Credito Acumulado
		fclose($this->arquivo);
		download("../temp/upload/$nome_arquivo");
		return true;
	}

	private function gerar_registroCR01(){
		$quant_registro = 1;

		$registro = array(
			"CR" => "01", // Código de registro
			"TIPODOCTO" => "01", // Identifica tipo do documento
			"DATAGERACAO" => date("Ymd"), // Data da geração do arquivo Pré-formatado-NG
			"HORAGERACAO" => date("His"), // Hora da geração do arquivo Pré-formatado-NG
			"VERSAOFRONTEND" => "0000", // Versão do sistema GIA
			"VERSAOPREF" => "0210", // Versão do Layout do Pré-formatadoNG
			"Q05" => str_pad($quant_registro, 4, "0", STR_PAD_LEFT), // Quantidade de registro CR=05
			"SEPARADORES" => "\r\n" // Identifica o fim do registro
		);
		$this->escrever_registro($registro);
	}

	private function gerar_registroCR05(){
		$estabelecimento = objectbytable("estabelecimento", $this->codestabelec, $this->con);

		$quant_registro_07 = 0;
		$quant_registro_10 = count($this->arr_apurnatoperacao);
		$quant_registro_20 = 0;
		$quant_registro_30 = 0;
		$quant_registro_31 = 0;

		$anoRef = substr($this->datainicial,0,4);
		$mesRef = substr($this->datainicial,5,2);

		$registro = array(
			"CR" => "05", // Código de registro
			"IE" => removeformat($estabelecimento->getrgie()), // Inscrição Estadual
			"CNPJ" => removeformat($estabelecimento->getcpfcnpj()), // Cadastro Nacional de Pessoa Jurídica
			"CNAE" => str_repeat("0", 7), // Classificação Nacional de Atividade Econômica
			"REGTRIB" => "01", // Regime Tributário
			"REF" => $anoRef.$mesRef, // Referência (Ano e Mês da GIA)
			"REFINICIAL" => "000000", // Referência Inicial
			"TIPO" => "01", // Tipo da GIA
			"MOVIMENTO" => "1", // Indica se houve movimento
			"TRANSMITIDA" => "1", // Indica se o Documento Fiscal já foi transmitido
			"SALDOCREDPERIODOANT" => $this->decimal($this->creditoicms), // Saldo Credor do Período Anterior
			"SALDOCREDPERIODOANTST" => $this->decimal($this->creditoicmsst), // Saldo Credor do Período Anterior para Substituição Tributária
			"ORIGEMSOFTWARE" => "57959652000175", // Identificação do fabricante do sistema de informação contábil que gerou o arquivo Pré-formatado-NG
			"ORIGEMPREDIG" => "0", // Indica se o arquivo Pré-formatadoNG foi gerado por algum sistema de informação contábil
			"ICMSFIXPER" => str_repeat("0", 15), // ICMS Fixado para o período
			"CHAVEINTERNA" => str_repeat("0", 32), // Chave Interna
			"Q07" => str_pad($quant_registro_07, 4, "0", STR_PAD_LEFT), // Quantidade de registro CR=07
			"Q10" => str_pad($quant_registro_10, 4, "0", STR_PAD_LEFT), // Quantidade de registro CR=10
			"Q20" => str_pad($quant_registro_20, 4, "0", STR_PAD_LEFT), // Quantidade de registro CR=20
			"Q30" => str_pad($quant_registro_30, 4, "0", STR_PAD_LEFT), // Quantidade de registro CR=30
			"Q31" => str_pad($quant_registro_31, 4, "0", STR_PAD_LEFT), // Quantidade de registro CR=31
			"SEPARADORES" => "\r\n" // Identifica o fim do registro
		);
		$this->escrever_registro($registro);
	}

	private function gerar_registroCR10(){

		foreach($this->arr_apurnatoperacao AS $apurnatoperacao){

			$quant_registro = 0;
			if(in_array(substr($apurnatoperacao["natoperacao"], 0, 1), array("2", "6"))){
				$arr_natinterestadual = $this->arr_apurnatoperacaoestado[$apurnatoperacao["natoperacao"]];

				$quant_registro = count($arr_natinterestadual);
			}

			$registro = array(
				"CR" => "10", // Código de registro
				"CFOP" => str_pad(removeformat(substr($apurnatoperacao["natoperacao"],0,5)),6,"0",STR_PAD_RIGHT), // Código Fiscal de Operação e Prestação
				"VALORCONTABIL" => $this->decimal($apurnatoperacao["totalliquido"]), // Valor Contábil
				"BASECALCULO" => $this->decimal($apurnatoperacao["totalbaseicms"]), // Base de Cálculo
				"IMPOSTO" => $this->decimal($apurnatoperacao["totalicms"]), // Imposto Creditado ou Debitado
				"ISENTASNAOTRIB" => $this->decimal($apurnatoperacao["totalbaseisento"]), // Isentas e Não Tributadas
				"OUTRAS" => $this->decimal($apurnatoperacao["totalbaseoutras"]), // Outros valores
				"IMPOSTORETIDOST" => $this->decimal($apurnatoperacao["totalicmssubst"]), // Imposto Retido por Substituição Tributária
				"IMPOSTORETSUBSTITUTOST" => $this->decimal(), // Imposto lançado para contribuinte do tipo Substituto, responsável pelo recolhimento do imposto.
				"IMPRETSUBSTITUIDO" => $this->decimal(), // Imposto Retido por Substituição Tributária (Substituído)
				"OUTROSIMPOSTOS" => $this->decimal($apurnatoperacao["totalipi"]), // Outros Impostos
				"Q14" => str_pad($quant_registro, 4, "0", STR_PAD_LEFT), // Quantidade de registros CR=14
				"SEPARADORES" => "\r\n", // Identifica o fim do registro
			);
			$this->escrever_registro($registro);

			if(in_array(substr($apurnatoperacao[natoperacao], 0, 1), array("2", "6"))){
				$this->gerar_registroCR14($arr_natinterestadual);
			}
		}
	}

	private function gerar_registroCR14($arr_natinterestadual){

		foreach($this->unidade_federacao() AS $uf => $unidade_federacao){
			$contrib_totalliquido = 0;
			$contrib_totalbaseicms = 0;
			$naocontrib_totalliquido = 0;
			$naocontrib_totalbaseicms = 0;
			$valoroutras = 0;
			$valoricms = 0;
			$valoricmsst = 0;
			$valoricmsstoutros = 0;
			$natoperacao_int = substr(str_replace(".","",$natinterestadual["natoperacao"]),0,4);

			if(count($arr_natinterestadual[$uf]) > 0){
				$natinterestadual = $arr_natinterestadual[$uf];

				foreach($arr_natinterestadual[$uf] AS $natinterestadual){

					if($natinterestadual["contribuinteicms"] == "S"){
						$contrib_totalliquido += $natinterestadual["totalliquido"];
						$contrib_totalbaseicms += $natinterestadual["totalbaseicms"];
						$naocontrib_totalliquido += 0;
						$naocontrib_totalbaseicms += 0;

					}else{
						$contrib_totalliquido += 0;
						$contrib_totalbaseicms += 0;
						$naocontrib_totalliquido += $natinterestadual["totalliquido"];
						$naocontrib_totalbaseicms += $natinterestadual["totalbaseicms"];
					}
					$valoroutras += $natinterestadual["totalbaseoutras"];
					$valoricms += $natinterestadual["totalicms"];
					$valoricmsst += $natinterestadual["totalicmssubst"];

					if(substr($natinterestadual["natoperacao"],0,1) != 6){
						$naocontrib_totalliquido = 0;
						$naocontrib_totalbaseicms = 0;

					}

					if(
						($natoperacao_int <> 1360) ||
						($natoperacao_int >= 1401 && $natoperacao_int <= 1449) ||
						($natoperacao_int >= 1651 && $natoperacao_int <= 1699) ||
						($natoperacao_int >= 1900 && $natoperacao_int <= 1999) ||
						($natoperacao_int >= 2401 && $natoperacao_int <= 2449) ||
						($natoperacao_int >= 2651 && $natoperacao_int <= 2699) ||
						($natoperacao_int >= 2900 && $natoperacao_int <= 2999) ||
						($natoperacao_int == 5360) ||
						($natoperacao_int >= 5401 && $natoperacao_int <= 5449) ||
						($natoperacao_int >= 5651 && $natoperacao_int <= 5699) ||
						($natoperacao_int >= 5900 && $natoperacao_int <= 5999) ||
						($natoperacao_int == 6360) ||
						($natoperacao_int >= 6401 && $natoperacao_int <= 6449) ||
						($natoperacao_int >= 6651 && $natoperacao_int <= 6699) ||
						($natoperacao_int >= 6900 && $natoperacao_int <= 6999)
					){
						$valoricmsst = 0;
					}

				}

				$registro = array(
					"CR" => "14", // Código de registro
					"UF" => $unidade_federacao, // Unidade da Federação
					"VALORCONTABIL1" => $this->decimal($contrib_totalliquido), // Valor Contábil de Contribuinte
					"BASECALCULO1" => $this->decimal($contrib_totalbaseicms), // Base de Cálculo de Contribuinte
					"VALORCONTABIL2" => $this->decimal($naocontrib_totalliquido), // Valor Contábil de Não Contribuinte
					"BASECALCULO2" => $this->decimal($naocontrib_totalbaseicms), // Base de Cálculo de Não Contribuinte
					"IMPOSTO" => $this->decimal($valoricms), // Imposto Creditado ou Debitado
					"OUTRAS" => $this->decimal($valoroutras), // Outros valores
					"ICMSCOBRADOST" => $this->decimal($valoricmsst), // ICMS Cobrado por Substituição Tributária
					"PETROLEOENERGIA" => $this->decimal(0), // Petróleo e Energia quando ICMS cobrado por Substituição Tributária
					"OUTROSPRODUTOS" => $this->decimal($valoricmsstoutros), // Outros Produtos quando ICMS cobrado por Substituição Tributária
					"BENEF" => "0", // Indica se há alguma operação Beneficiada por isenção de ICMS (ZFM/ALC)
					"Q18" => "0000", // Quantidade de registros CR=18
					"SEPARADORES" => "\r\n" // Identifica o fim do registro
				);
				$this->escrever_registro($registro);
			}
		}
	}

	private function gerar_registroCR18(){
		$linha = "18"; // Código de registro
		$linha .= ""; //
		return $this->arr_arquivo;
	}

	private function gerar_registroCR20(){
		$linha = "20"; // Código de registro
		$linha .= ""; //
		return $this->arr_arquivo;
	}

	private function decimal($decimal){
		$decimal = number_format($decimal, 2, "", "");
		$decimal = abs($decimal);
		$decimal = str_pad($decimal, 15,"0", STR_PAD_LEFT);
		return $decimal;
	}

	private function escrever_registro($registro){
		if(is_array($registro) && sizeof($registro) > 0){
			fwrite($this->arquivo, implode("", $registro));
		}
	}

	private function valor_data($data){
		if(strpos($data, "/") !== FALSE){ // No formato 'dd/mm/aaaa'
			$arr = explode("/", $data);
			$time = mktime(0, 0, 0, $arr[1], $arr[0], $arr[2]);
		}elseif(strpos($data, "-") !== FALSE){ // No formato 'aaaa-mm-dd'
			$arr = explode("-", $data);
			$time = mktime(0, 0, 0, $arr[1], $arr[2], $arr[0]);
		}
		return date("dmY", $time);
	}

	public function setcodestabelec($codestabelec){
		$this->codestabelec = $codestabelec;
	}

	public function setdatainicial($data){
		if(strlen($data) == 10){
			$data = $this->valor_data($data);
			$data = substr($data, 4)."-".substr($data, 2, 2)."-".substr($data, 0, 2);
			$this->datainicial = $data;
		}
	}

	// Armazena a data final de geracao (armazenada no formato Y-m-d)
	public function setdatafinal($data){
		if(strlen($data) == 10){
			$data = $this->valor_data($data);
			$data = substr($data, 4)."-".substr($data, 2, 2)."-".substr($data, 0, 2);
			$this->datafinal = $data;
		}
	}

	public function setcreditoicms($creditoicms){
		$this->creditoicms = $creditoicms;
	}

	public function setcreditoicmsst($creditoicmsst){
		$this->creditoicmsst = $creditoicmsst;
	}

	public function filtro($coluna, $valor){
		if(strlen(trim($valor)) > 0){
			$this->arr_filtro[$coluna] = $valor;
		}
	}

	private function unidade_federacao(){
		$this->arr_unidade_federacao["AC"] = "01";
		$this->arr_unidade_federacao["AL"] = "02";
		$this->arr_unidade_federacao["AP"] = "03";
		$this->arr_unidade_federacao["AM"] = "04";
		$this->arr_unidade_federacao["BA"] = "05";
		$this->arr_unidade_federacao["CE"] = "06";
		$this->arr_unidade_federacao["DF"] = "07";
		$this->arr_unidade_federacao["ES"] = "08";
		$this->arr_unidade_federacao["GO"] = "10";
		$this->arr_unidade_federacao["MA"] = "12";
		$this->arr_unidade_federacao["MT"] = "13";
		$this->arr_unidade_federacao["MS"] = "28";
		$this->arr_unidade_federacao["MG"] = "14";
		$this->arr_unidade_federacao["PA"] = "15";
		$this->arr_unidade_federacao["PB"] = "16";
		$this->arr_unidade_federacao["PR"] = "17";
		$this->arr_unidade_federacao["PE"] = "18";
		$this->arr_unidade_federacao["PI"] = "19";
		$this->arr_unidade_federacao["RJ"] = "22";
		$this->arr_unidade_federacao["RN"] = "20";
		$this->arr_unidade_federacao["RS"] = "21";
		$this->arr_unidade_federacao["RO"] = "23";
		$this->arr_unidade_federacao["RR"] = "24";
		$this->arr_unidade_federacao["SC"] = "25";
		$this->arr_unidade_federacao["SP"] = "26";
		$this->arr_unidade_federacao["SE"] = "27";
		$this->arr_unidade_federacao["TO"] = "29";

		asort($this->arr_unidade_federacao);

		return $this->arr_unidade_federacao;

	}
}