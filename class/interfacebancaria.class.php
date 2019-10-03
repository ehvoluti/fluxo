<?php
require_once("../class/pdf.class.php");

abstract class InterfaceBancaria{
	const CNAB_240 = "240";
	const CNAB_400 = "400";
	const TIPO_PAGAMENTO = "P";
	const TIPO_RECEBIMENTO = "R";

	protected $con;						// conexão com o banco
	protected $banco;					// Objeto da tabela banco
	protected $estabelecimento;			// Array com os objetos da tabela estabelecimento
	protected $controleprocfinan;		// Objeto da tabela controleprocfinan (preenchido e usado no metodo gerar_remessa)
	protected $arr_lancamento;			// Array com objetos da tabela lancamento
	protected $ocorrencia;				// (C = Cancelamento de remessa; N = Normal; G = Cancelamento de geracao)
	protected $remessa_anterior;		// Se os lancamentos da geracao atual pertencem a uma geracao anterior
	protected $arr_banco;				// Array com os bancos dos lancamentos
	protected $arr_instrucaobancaria;	//array contendo as intrucoes para o boleto no arquivo de remessa (recebimento apenas)
	protected $arr_remessa = array();

	function __construct($con){
		$this->con = $con;
		$this->arr_lancamento = array();
		$this->setocorrencia("N");
	}

	protected function carregarauxiliares(){
		$arr_codbanco = array();
		foreach($this->arr_lancamento as $lancamento){
			if(strlen($lancamento->getcodbanco()) > 0){
				$arr_codbanco[] = $lancamento->getcodbanco();
			}
			$this->arr_remessa[] = array(
				"chave" => $lancamento->getcodlancto(),
				"numnotafis" => $lancamento->getnumnotafis(),
				"favorecido" => $lancamento->getfavorecido(),
				"dtemissao" => $lancamento->getdtemissao(),
				"dtvencto" => $lancamento->getdtvencto(),
				"valor" => $lancamento->getvalorparcela()
			);
			$arr_lancamento[$this->tiposervico($lancamento->getpagrec(), (int)$lancamento->getcodtipoaceite())][$this->formapagamento(substr($lancamento->getcodbarras(), 0, 3), $lancamento->getcodtipoaceite())][] = $lancamento;
		}

		$this->arr_banco = object_array_key(objectbytable("banco", NULL, $this->con), $arr_codbanco);
		$instrucaobancaria = objectbytable("instrucaobancaria", NULL, $this->con);
		$instrucaobancaria->setcodoficial($this->banco->getcodoficial());
		$obj_arr_instrucaobancaria = object_array($instrucaobancaria);
		$this->arr_instrucaobancaria = array();

		foreach($obj_arr_instrucaobancaria AS $instrucaobancaria){
			$this->arr_instrucaobancaria[$instrucaobancaria->getcodigoinstrucao()] = $instrucaobancaria;
		}
		return $arr_lancamento;
	}

	function addlancamento($lancamento){
		$this->arr_lancamento[] = $lancamento;
	}

	function formatar_data($valor, $tipo = 'R'){
		$tamanho = 8;
		if(strlen($valor) > 0){
			switch($this->banco->getcodoficial()){
				case "001":
					if($tipo == "R"){
						$formato = "dmy";
						$tamanho = 6;
					}else{
						$formato = "dmY";
						$tamanho = 8;
					}
					break;
				case "237":
					$formato = "Ymd";
					break;
				case "341":
					if($tipo == "R"){
						$formato = "dmy";
						$tamanho = 6;
					}else{
						$formato = "dmY";
						$tamanho = 8;
					};
					break;
				default: $formato = "dmY";
					break;
			}
			$valor = value_date($valor);
			$valor = convert_date($valor, "Y-m-d", $formato);
		}
		return $this->formatar_inteiro($valor, $tamanho);
	}

	function formatar_decimal($valor, $tamanho, $decimal = 0){
		return $this->formatar_inteiro(number_format($valor, $decimal, "", ""), $tamanho);
	}

	function formatar_hora($valor){
		$valor = value_time($valor);
		return implode("", explode(":", $valor));
	}

	function formatar_inteiro($valor, $tamanho){
		return str_pad($valor, $tamanho, "0", STR_PAD_LEFT);
	}

	function formatar_texto($texto, $tamanho, $posicao = 0, $alinhamento = "E"){
		if($alinhamento == "D"){
			return strtoupper(str_pad(substr(removespecial($texto), $posicao, $tamanho), $tamanho, " ", STR_PAD_LEFT));
		}else{
			return strtoupper(str_pad(substr(removespecial($texto), $posicao, $tamanho), $tamanho, " ", STR_PAD_RIGHT));
		}
	}

	protected function gerar_remessa(){
		$tiposervidor = param("SISTEMA", "TIPOSERVIDOR", $this->con);
		$codestabelec = $_REQUEST["codestabelec"];
		$estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);

		//$estabelecimento = reset($this->arr_estabelecimento);
		if(!is_dir($estabelecimento->getdirremessabanco()) && $tiposervidor == 0){
			$_SESSION["ERROR"] = "Diret&oacute;rio de remessa n&atilde;o configurado no estabelecimento.<br><a onclick=\"openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}

		// Inicia a transacao no banco de dados
		$this->con->start_transaction();

		// Verifica a ultima remessa no banco
		if($this->banco->getdtultremessa(TRUE) == date("d/m/Y")){
			$this->banco->setnrultremessa($this->banco->getnrultremessa() + 1);
		}else{
			$this->banco->setnrultremessa(1);
			$this->banco->setdtultremessa(date("d/m/Y"));
		}

		// Cria na tabela de controle de processos o registro do processo executado
		if(!$this->remessa_gerada_anteriormente() && $this->ocorrencia == "N"){
			$lancamento = reset($this->arr_lancamento);
			$controleprocfinan = objectbytable("controleprocfinan", NULL, $this->con);
			$controleprocfinan->settipoprocesso("GR"); // Geracao Remessa
			$controleprocfinan->setstatus("N"); // Normal
			$controleprocfinan->setpagrec($lancamento->getpagrec());
			if(!$controleprocfinan->save()){
				$this->con->rollback();
				return FALSE;
			}
		}elseif(in_array($this->ocorrencia, array("C","G","N"))){
			foreach($this->arr_lancamento as $lancamento){
				if(strlen($lancamento->getprocessogr()) > 0){
					$processogr = $lancamento->getprocessogr();
					break;
				}
			}

			$lancamento = objectbytable("lancamento", NULL, $this->con);
			$lancamento->setprocessogr($processogr);
			$arr_lancamento = object_array($lancamento);
			if(in_array($this->ocorrencia, array("C","G"))){
				if(sizeof($this->arr_lancamento) > 0  && sizeof($arr_lancamento) > 0){
					$controleprocfinan = objectbytable("controleprocfinan", $processogr, $this->con);
					$controleprocfinan->setstatus("C"); // Cancelamento de remessa
					if(!$controleprocfinan->save()){
						$this->con->rollback();
						return FALSE;
					}
				}else{
					$this->con->rollback();
					return FALSE;
				}
			}else{
				$controleprocfinan = objectbytable("controleprocfinan", $processogr, $this->con);
			}
		}
		$this->con->commit();
		$this->controleprocfinan = (is_object($controleprocfinan) ? $controleprocfinan : NULL);
		return TRUE;
	}

	// Cria um relatório dos pagamentos inclusos na remessa
	// Parametro $arr_remessa:
	//   Um array de array que contem:
	//     Chave                    [chave]
	//     Favorecido               [favorecido]
	//     Data de emissão          [dtemissao]
	//     Data de vencimento       [dtvencto]
	//     Valor                    [valor]
	protected function relatorio_remessa($arr_remessa, $paramarqremessa){
		// Cabeçalho PDF
		$param_financeiro_remessasemverif = param("FINANCEIRO", "REMESSASEMVERIF", $this->con);
		$pdf = new PDF($this->con, NULL, "L");
		$pdf->SetTitle("Remessa");
		$pdf->SetFillColor(220, 220, 220);
		$pdf->AddPage();
		$pdf->SetFont("Arial", "", 9);
		$pdf->Cell(100, 8, "Remessa: ".$paramarqremessa["numremessa"], 0, 1, "L");
		$pdf->Cell(100, 4, "Banco: ".$paramarqremessa["nomebanco"], 0, 1, "L");

		$remessa = reset($arr_remessa);
		if(strlen($remessa["numcheque"]) > 0){
			$pdf->Cell(20, 8, "Numero do cheque: ".$remessa["numcheque"], 0, 1, "L");
		}
		$pdf->SetFont("Arial", "", 7);
		$pdf->Cell(20, 4, "Chave", 1, 0, "L", 1);

		$pdf->Cell(40, 4, "Estabelecimento", 1, 0, "L", 1);

		$pdf->Cell(20, 4, "Nota Fiscal", 1, 0, "L", 1);
		$pdf->Cell(75, 4, "Favorecido", 1, 0, "L", 1);
		$pdf->Cell(25, 4, "Data de Emissao", 1, 0, "L", 1);
		$pdf->Cell(25, 4, "Vencimento", 1, 0, "L", 1);

		$pdf->Cell(20, 4, "Desconto", 1, 0, "L", 1);
		$pdf->Cell(20, 4, "Acrescimo", 1, 0, "L", 1);
		$pdf->Cell(20, 4, "Valor Bruto", 1, 0, "L", 1);
		$pdf->Cell(20, 4, "Valor Liquido", 1, 1, "L", 1);

		foreach($arr_remessa as $remessa){
			$pdf->Cell(20, 4, $remessa["chave"], 1, 0, "L"); // Chave
			$lancamento = objectbytable("lancamento", $remessa["chave"], $this->con);

			$estabelecimento = objectbytable("estabelecimento", $lancamento->getcodestabelec(), $this->con);
			$pdf->Cell(40, 4, $estabelecimento->getnome(), 1, 0, "L"); // Numero da nota fiscal

			$pdf->Cell(20, 4, $remessa["numnotafis"], 1, 0, "L"); // Numero da nota fiscal
			$pdf->Cell(75, 4, $lancamento->getcodparceiro()." - ".$remessa["favorecido"], 1, 0, "L"); // Favorecido
			$pdf->Cell(25, 4, convert_date($remessa["dtemissao"], "Y-m-d", "d/m/Y"), 1, 0, "C"); // Data de Emissão
			$pdf->Cell(25, 4, convert_date($remessa["dtvencto"], "Y-m-d", "d/m/Y"), 1, 0, "C"); // Vencimento

			$pdf->Cell(20, 4, number_format($lancamento->getvalordescto(), 2, ",", "."), 1, 0, "R"); // Valor Desconto
			$pdf->Cell(20, 4, number_format($lancamento->getvaloracresc(), 2, ",", "."), 1, 0, "R"); // Valor Acrescimo
			$pdf->Cell(20, 4, number_format($lancamento->getvalorparcela(), 2, ",", "."), 1, 0, "R"); // Valor bruto
			$pdf->Cell(20, 4, number_format($lancamento->getvalorliquido(), 2, ",", "."), 1, 1, "R"); // Valor liquido

			$totalliquido += $lancamento->getvalorliquido();
			$totalbruto += $lancamento->getvalorparcela();
		}

		$pdf->Cell(25, 8, "", 0, 1);
		$pdf->Cell(25, 4, "Total Bruto", 1, 0, "L", 1);
		$pdf->Cell(25, 4, "Total Liquido", 1, 0, "L", 1);
		$pdf->Cell(25, 4, "Boletos Gerados", 1, 1, "L", 1);
		$pdf->Cell(25, 4, number_format($totalbruto, 2, ",", "."), 1, 0, "L"); // Chave
		$pdf->Cell(25, 4, number_format($totalliquido, 2, ",", "."), 1, 0, "L"); // Chave
		$pdf->Cell(25, 4, sizeof($arr_remessa), 1, 1, "C"); // Chave
		// Gera o arquivo na pasta temp
		$nomearquivo = "../temp/remessabancaria_".$_SESSION["WUser"]."_".$paramarqremessa["banco"]."_".removeformat(trim($paramarqremessa["nomebanco"]))."_".$paramarqremessa["numremessa"].".pdf";

		$pdf->Output($nomearquivo, "F");
		$_SESSION["arquivoremessa"] = ($nomearquivo);
	}

	// Processa o retorno dos bancos
	// Parametro $arr_retorno:
	//   Um array de array que contem:
	//     Codigo do lacamento      [codlancto]
	//     Nosso numero             [nossonumero]
	//     Valor da parcela         [valorparcela]
	//     Valor de juros           [valorjuros]
	//     Valor pago               [valorpago]
	//     Liquidado (booleano)     [liquidado]
	//     Data de vencimento       [dtvencto] (formato: Y-m-d)
	//     Data do pagamento        [dtliquid] (formato: Y-m-d)
	//     Codigo da ocorrencia     [codocorrencia]
	//     Ocorrencia               [ocorrencia]
	//     Motivo da ocorrencia     [motivoocorrencia]
	//     Data da ocorrencia       [dtocorrencia]
	protected function processar_retorno($arr_retorno){
		if(count($arr_retorno) == 0){
			return FALSE;
		}
		$pdf = new PDF($this->con, NULL, "L");
		$pdf->SetTitle("Rentorno Bancário");
		$pdf->SetFillColor(220, 220, 220);
		$pdf->AddPage();
		$pdf->SetFont("Arial", "", 7);
		$pdf->Cell(15, 4, "Chave", 1, 0, "L", 1);
		$pdf->Cell(26, 4, "Nosso Numero", 1, 0, "L", 1);
		$pdf->Cell(65, 4, "Favorecido", 1, 0, "L", 1);
		$pdf->Cell(25, 4, "Vencimento", 1, 0, "L", 1);
		$pdf->Cell(25, 4, "Valor Parcela", 1, 0, "L", 1);
		$pdf->Cell(25, 4, "Valor Pago", 1, 0, "L", 1);
		$pdf->Cell(70, 4, "Ocorrencia", 1, 0, "L", 1);
		$pdf->Cell(0, 4, "Data Ocorrencia", 1, 1, "L", 1);

		$this->con->start_transaction();
		$refavisobancario = array_pop($arr_retorno);
		foreach($arr_retorno as $i => $retorno){
			setprogress((($i + 1) / sizeof($arr_retorno) * 100), "Processando retorno: ".($i + 1)." de ".sizeof($arr_retorno));
			// Verifica se a chave do lancamento veio preenchida
			if(strlen(trim($retorno["codlancto"])) > 0){
				// Verifica se o lancamento existe
				$lancamento = objectbytable("lancamento", $retorno["codlancto"], $this->con);
				//            if($lancamento->exists() && ($this->banco->getbancoemiteboleto() == "S" || $lancamento->getnossonumero() == $retorno["nossonumero"])){
				if($lancamento->exists() && ($this->banco->getbancoemiteboleto() == "S" || $lancamento->getnossonumero() == 0 || ($lancamento->getnossonumero() > 0 && $lancamento->getnossonumero() == $retorno["nossonumero"]))){
					/*
					  // Inclui as ocorrencias do lancamento no historico
					  if(is_array($retorno["ocorrencia"]) && sizeof($retorno["ocorrencia"]) > 0){
					  $lancamento->incluir_historico(implode("\n",$retorno["ocorrencia"]));
					  }
					 */
					$lancamento->setcodocorrencia($retorno["codocorrencia"]);
					$lancamento->setocorrencia(utf8_decode($retorno["ocorrencia"]));
					if(!is_array($retorno["motivoocorrencia"])){
						$lancamento->setmotivoocorrencia($retorno["motivoocorrencia"]);
					}else{
						$lancamento->setmotivoocorrencia(implode("\n", $retorno["motivoocorrencia"]));
					}

					if($this->banco->getbancoemiteboleto() == "S" &&
						(($this->banco->getcodoficial() == "237" && $retorno["codocorrencia"] == "02") ||   // Bradesco
                                                ($this->banco->getcodoficial() == "001" && $retorno["codocorrencia"] != "03"))      // Banco do Brasil
					){
						$lancamento->setnossonumero($retorno["nossonumero"]);
					}

					// Verifica se o pagamento foi efetuado
					if($retorno["liquidado"] === TRUE){
						$lancamento->setstatus("L");
						$lancamento->setvalorjuros(is_null($retorno["valorjuros"]) ? 0 : $retorno["valorjuros"]);
						if($retorno["valorpago"] > 0){
							$lancamento->setvalorpago($retorno["valorpago"]);
						}else{
							$lancamento->setvalorpago($lancamento->getvalorliquido());
						}

						if(isset($retorno["dtcreditocc"])){
							$lancamento->setdtcreditocc($retorno["dtcreditocc"]);
							$lancamento->setstatusliquidacao($retorno["statusliquidacao"]);
							$lancamento->setrefavisobancario($refavisobancario);
						}

						$lancamento->setdtliquid($retorno["dtliquid"]);
						$dtliquid = $retorno["dtliquid"];

						$lancamento->settipoliquidacao("B");
						// Tratamento que a Eliane pediu
						if(param("SISTEMA","CODIGOCW", $this->con) == "9940"){
							if(date('N', strtotime($dtliquid)) == 5){
								$dtliquid = date($dtliquid, strtotime("+3 days"));
								$lancamento->setdtreconc($dtliquid);
							}else{
								$dtliquid = date($dtliquid, strtotime("+1 days"));
								$lancamento->setdtreconc($dtliquid);
							}
						}

						$lancamento->setcodespecieliq($lancamento->getcodespecie());
						$lancamento->setdocliquidacao($lancamento->getnumnotafis());
						if($retorno["valorparcela"] > 0 && $retorno["valorparcela"] > $lancamento->getvalorparcela()){
							$valoracresc = $retorno["valorparcela"] - $lancamento->getvalorparcela();
							$lancamento->setvaloracresc($valoracresc);
						}
					}
					if(!$lancamento->save()){
						$this->con->rollback();
						return FALSE;
					}
				}else{
					unset($lancamento);
				}
			}else{
				unset($lancamento);
			}
			$pdf->Cell(15, 4, $retorno["codlancto"], 1, 0, "R");
			$pdf->Cell(26, 4, $retorno["nossonumero"], 1, 0, "C");
			$pdf->Cell(65, 4, (is_object($lancamento) ? $lancamento->getfavorecido() : "LANCAMENTO NAO ENCONTRADO"), 1);
			$pdf->Cell(25, 4, (is_object($lancamento) ? $lancamento->getdtvencto(TRUE) : ""), 1, 0, "C");
			$pdf->Cell(25, 4, number_format($retorno["valorparcela"], 2, ",", "."), 1, 0, "R");
			$pdf->Cell(25, 4, number_format($retorno["valorpago"], 2, ",", "."), 1, 0, "R");
			$pdf->Cell(70, 4, $retorno["codocorrencia"]." - ".utf8_decode($retorno["ocorrencia"]), 1, 0);
			$pdf->Cell(0, 4, convert_date($retorno["dtocorrencia"], "Y-m-d", "d/m/Y"), 1, 1, "C");
		}
		$this->con->commit();
		$pdf->Output("../temp/retornobancario_".$_SESSION["WUser"].".pdf", "F");
		return TRUE;
	}

	// Verificar se os lancamentos da geracao atual pertence a uma geracao anterior
	function remessa_gerada_anteriormente($nova_busca = FALSE){
		if($nova_busca || !is_bool($this->remessa_anterior)){
			$this->remessa_anterior = FALSE;
			foreach($this->arr_lancamento as $lancamento){
				if(strlen($lancamento->getseqremessa()) > 0){
					$this->remessa_anterior = TRUE;
				}
			}
		}else{
			$this->remessa_anterior = FALSE;
		}
		return $this->remessa_anterior;
	}

	function setbanco($banco){
		$this->banco = $banco;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	function setocorrencia($ocorrencia){
		$this->ocorrencia = $ocorrencia;
	}

	protected function formapagamento($bancoboleto, $tipoaceite){
		switch((int)$tipoaceite){
			case 1:
			case 2:
				if($bancoboleto == $this->banco->getcodoficial()){
					return "30";
				}else{
					return "31";
				}
				break;
			case 3:
				if(in_array($banco, array("748"))){
					return "11";
				}else{
					return "13";
				}
				break;
			case 4: return "16";
				break;
			case 5:
				if(in_array($banco, array("748"))){
					return "17";
				}else{
					return "18";
				}
				break;
			case 6: return "21";
				break;
			case 7: return "22";
				break;
			case 8:
				if(in_array($banco, array("748"))){
					return "18";
				}else{
					return "17";
				}
				break;
			case 9: return "25";
				break;
			case 10: return "19";
				break;
			case 11: return "19";
				break;
			case 12: return "27";
				break;
			case 13: return "35";
				break;
			case 14: return "91";
				break;
			case 98: return "91";
				break;
		}
	}

	protected function tiposervico($tipo_lancamento, $tipo_aceite){
		if($tipo_lancamento == self::TIPO_PAGAMENTO){
			if($this->banco->getcodoficial() == "748"){
				if(in_array($tipo_aceite, array(1, 2))){
					return "03";
				}else{
					return "22";
				}
			}else{
				if(in_array($tipo_aceite, array(1, 2, 3))){
					return "20";
				}else{
					return "22";
				}
			}
		}else{
			return "01";
		}
	}

	public function atualiza_lancamentos_gerados(){
		//percorrer os lancamentos gerados para gravar o sequencial da remessa ,
		//codigo,tipo,data do processo de geracao
		foreach($this->arr_lancamento as $lancamento){
			$procfina = $lancamento->getprocessogr();
			if($this->ocorrencia == "N" && !$this->remessa_anterior){
				$lancamento->setseqremessa($this->banco->getseqremessa());
				$lancamento->setprocessogr($this->controleprocfinan->getcodcontrprocfinan());
				$lancamento->setcodocorrencia("00");
				$lancamento->setocorrencia(utf8_encode($this->ocorrencia("00")));
				$lancamento->setmotivoocorrencia("Aguardando Retorno");
				$lancamento->setdtremessa(date("Y-m-d"));
				$lancamento->setcodbanco($this->banco->getcodbanco());
			}elseif($this->ocorrencia == "C" || $this->ocorrencia == "G"){
				$lancamento->setseqremessa(NULL);
				if($this->ocorrencia == "C"){
					$lancamento->setnossonumero(NULL);
				}
				$lancamento->setprocessogr(NULL);
				$lancamento->setcodocorrencia("01");
				$lancamento->setocorrencia(utf8_encode($this->ocorrencia("01")));
				$lancamento->setmotivoocorrencia("Cancelada a Geracao de Remessa");
				$lancamento->setdtremessa(NULL);
			}
			if(!$lancamento->save()){
				return FALSE;
			}
		}
		return $procfina;
	}

	private function ocorrencia(){
		switch($this->ocorrencia){
			case "C": return "02";
			case "N": return "01";
			case "G": return "01";
		}
	}

	public function gerar_header_arquivo_240($seqremessa, $versaolayout, $tipo_lancamento){
		/* *************************************************
		  R E G I S T R O   H E A D E R   D O   A R Q U I V O
		 * *********************************************** */
		$linha = self::formatar_inteiro($this->banco->getcodoficial(), 3);							//1 - 3 Codigo do banco na compensacao
		$linha .= self::formatar_inteiro("0", 4);													//4 - 7 Codigo do lote
		$linha .= self::formatar_inteiro("0", 1);													//8 - 8 Tipo do registro

		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto(NULL, 9);												//9 - 17 Complemento(brancos)
		}else{
			$linha .= self::formatar_texto(NULL, 6);												//9 - 14 Complemento(brancos)
			$linha .= self::formatar_inteiro($versaolayout, 3);										//15 - 17 Versao do layout do arquivo
		}

		$linha .= self::formatar_inteiro("2", 1);													//18 - 18 Tipo da inscricao da empresa
		$linha .= self::formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), "14");	//19 - 32 Numero da inscricao(CPF/CNPJ) da empresa

		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			if($this->banco->getcodoficial() == "001"){
				if($tipo_lancamento == self::TIPO_RECEBIMENTO){
					$convenio  = str_pad(trim($this->banco->getcodigoempresa()), 9, "0", STR_PAD_LEFT);
					$convenio .= str_pad(trim($this->banco->getcodigocedente()), 4, "0", STR_PAD_LEFT);
					$convenio .= str_pad(trim($this->banco->getcarteira()), 2, "0", STR_PAD_LEFT);
					$convenio .= str_pad(trim($this->banco->getvarcarteira()), 3, "0", STR_PAD_LEFT);
					$convenio  = str_pad($convenio, 20, " ", STR_PAD_RIGHT);
				}else{
					$convenio = self::formatar_texto("", 20);
				}
			}else{
				$convenio = self::formatar_texto($this->banco->getcodigoempresa, 20);		    	//33 - 52 codigo convenio
			}
			$linha .= $convenio;
		}else{
			$linha .= self::formatar_texto(NULL, 20);												// Bancos
		}

		$linha .= self::formatar_inteiro($this->banco->getagencia(), 5);							//53 - 57 Numero da agencia
		if(in_array($this->banco->getcodoficial(), array("748","001"))){
			$linha .= self::formatar_texto($this->banco->getdigitoagencia(), 1);					//58 - 58 Digigito verificador da agencia
		}else{
			$linha .= self::formatar_texto(NULL, 1);												//58 - 58 Branco
		}
		$linha .= self::formatar_inteiro($this->banco->getconta(), 12);								//59 - 70 Numero da conta
		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto($this->banco->getdigito(), 1);							//71 - 71 digito da conta corrente
		}else{
			$linha .= self::formatar_texto(NULL, 1);												// Branco
		}
		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto("", 1);													//72 - 72 branco
		}else{
			$linha .= self::formatar_inteiro(trim($this->banco->getdigito()), 1);					// Digito da conta corrente
		}
		$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30);//73 - 102 Nome da empresa
		$linha .= self::formatar_texto($this->banco->getnome(), 30);								//103 - 132 Nome do banco
		$linha .= self::formatar_texto(NULL, 10);													//133 - 142 Brancos
		$linha .= self::formatar_inteiro("1", 1);													//143 - 143 Codigo do arquivo (1-remessa 2-retorno)
		$linha .= self::formatar_data(date("Y-m-d"), "P");											//144 - 151 Data de geracao do arquivo
		$linha .= self::formatar_hora(date("H:i:s"));												//152 - 157 Hora da geracao do arquivo
		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_inteiro($seqremessa, 6);										//158 - 164 sequencial do arquivo de remessa
			$linha .= $versaolayout;																//165 - 167 versão do layout
			$linha .= self::formatar_inteiro(NULL, 5);												//167 - 171 Unidade de densidade de gravcao do arquivo
			$linha .= self::formatar_texto(NULL, 20);												//172 - 191 uso do banco
			$linha .= self::formatar_texto(NULL, 20);												//192 - 211 uso da empresa
			$linha .= self::formatar_texto(NULL, 29);												//212 - 240 brancos
		}else{
			$linha .= self::formatar_inteiro(NULL, 9);												//158 - 166 Zeros
			$linha .= self::formatar_inteiro(NULL, 5);												//167 - 171 Unidade de densidade de gravcao do arquivo
			$linha .= self::formatar_texto(NULL, 69);												//172 - 240 Complemento(brancos)

		}
		return $linha;
	}

	public function gerar_trailer_arquivo_240($codigolote, $totalregistros){
		/** *************************************************
		  R E G I S T R O   T R A I L E R   D O   A R Q U I V O
		 * ************************************************* */
		$linha = $this->banco->getcodoficial();																	//1 - 3 Codigo do banco na compensacao
		$linha .= "9999";																		//4 - 7 Codigo do lote
		$linha .= "9";																			//8 - 8 Tipo do registro
		$linha .= self::formatar_texto("", 9);													//9 - 17 Complemento de registro
		$linha .= self::formatar_inteiro($codigolote, 6);										//18 - 23 Quantidade de lotes do arquivo
		$linha .= self::formatar_inteiro(($totalregistros + 1), 6);								//24 - 29 Quantidade de registros do arquivo
		if(!in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto("", 211);											//36 - 240 Complemento de registro
		}else{
			$linha .= self::formatar_inteiro(0, 6);												//30 - 35 Quantidade de registros do arquivo
			$linha .= self::formatar_texto("", 205);											//36 - 240 Complemento de registro
		}
		return $linha."\r\n";
	}

	public function gerar_header_lote_240(Cidade $cidade, $codigolote, $tipo_pagamento, $forma_pagamento, $versaolayout, $tipo_lancamento){
		/** *****************************************
		  R E G I S T R O   H E A D E R   D E   L O T E
		 * ***************************************** */
		$linha = self::formatar_inteiro($this->banco->getcodoficial(), 3);							//1 - 3 Codigo do banco na compensacao
		$linha .= self::formatar_inteiro($codigolote, 4);											//4 - 7 Lote de identificacao de pagamentos
		$linha .= self::formatar_inteiro("1", 1);													//8 - 8 Tipo do registro header do lote
		//$linha .= self::formatar_texto("C", 1);													//9 - 9 Tipo da operacao (C = credito)
		$linha .= ($tipo_lancamento == self::TIPO_PAGAMENTO ? "C" : "R");						//9 - 9 Tipo da operacao 'C' = Lançamento a Crédito
		$linha .= self::formatar_inteiro($tipo_pagamento, 2);										//10 - 11 Tipo de pagamento
		//$linha .= self::formatar_inteiro($forma_pagamento, 2);									//12 - 13 Forma de pagamento
		$linha .= ($tipo_lancamento == self::TIPO_PAGAMENTO ? self::formatar_inteiro($forma_pagamento, 2) : "  ");//12 - 13 Forma de pagamento
		$linha .= self::formatar_texto($versaolayout, 3);											//14 - 16 Versao do layout do lote
		$linha .= self::formatar_texto(NULL, 1);													//17 - 17 Complemento de registro
		$linha .= self::formatar_inteiro("2", 1);													//18 - 18 Tipo da inscricao da empresa (1 = CPF; 2 = CNPJ)

		if($tipo_lancamento == self::TIPO_PAGAMENTO){
			$linha .= self::formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), 14);	//19 - 32 Numero da inscricao (CPF/CNPJ) da empresa
		}else{
			$linha .= self::formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), 15);	//19 - 34 Numero da inscricao (CPF/CNPJ) da empresa
		}

		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			if($this->banco->getcodoficial() == "001"){
				if($tipo_lancamento == self::TIPO_RECEBIMENTO){
					$convenio  = str_pad(trim($this->banco->getcodigoempresa()), 9, "0", STR_PAD_LEFT);
					$convenio .= str_pad(trim($this->banco->getcodigocedente()), 4, "0", STR_PAD_LEFT);
					$convenio .= str_pad(trim($this->banco->getcarteira()), 2, "0", STR_PAD_LEFT);
					$convenio .= str_pad(trim($this->banco->getvarcarteira()), 3, "0", STR_PAD_LEFT);
					$convenio  = str_pad($convenio, 20, " ", STR_PAD_RIGHT);
				}else{
					$convenio = self::formatar_texto("", 20);
				}
			}else{
				$convenio = self::formatar_texto($this->banco->getcodigoempresa, 20);
			}
			$linha .= self::formatar_texto($convenio, 20);						//33 - 52 Complemento de registro
		}else{
			$linha .= self::formatar_texto("", 20);														//33 - 52 Complemento de registro
		}

		$linha .= self::formatar_inteiro($this->banco->getagencia(), 5);								//53 - 57 Numero da agencia debitada

		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto($this->banco->getdigitoagencia(), 1);						//58 - 58 digigto verificador da agencia
		}else{
			$linha .= self::formatar_texto(NULL, 1);													//58 - 58 Complemento de registro
		}

		$linha .= self::formatar_inteiro($this->banco->getconta(), 12);									//59 - 70 Numero da conta debitada

		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto($this->banco->getdigito(), 1);								//71 - 71 digito verificador da conta
		}else{
			$linha .= self::formatar_texto(NULL, 1);													//71 - 71 Complemento de registro
		}

		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto(NULL, 1);													//72 - 72
		}else{
			$linha .= self::formatar_inteiro(trim($this->banco->getdigito()), 1);						//72 - 72 Digito da conta debitada
		}

		$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30);	//73 - 102 Nome da empresa debitada
		$linha .= self::formatar_texto(NULL, 30);														//103 - 132 Finalidade dos pagamentos do lote
		$linha .= self::formatar_texto(NULL, 10);														//133 - 142 Complementos historico C/C debitada
		if($tipo_lancamento == self::TIPO_RECEBIMENTO){
			$linha .= str_repeat(" ", 40);																// Mensagem
			$linha .= $this->formatar_inteiro($this->banco->getseqremessa(), "08");						//Número Remessa/Retorno
			$linha .= date("dmY");
			$linha .= str_repeat("0", 8);																//Data do Crédito
			$linha .= str_repeat(" ", 33);																//Uso Exclusivo FEBRABAN/CNAB
		}else{
			$linha .= self::formatar_texto($this->estabelecimento->getendereco(), 30);						//143 - 172 Endereco da empresa
			$linha .= self::formatar_inteiro($this->estabelecimento->getnumero(), 5);						//173 - 177 Numero do endereco da empresa
			$linha .= self::formatar_texto($this->estabelecimento->getcomplemento(), 15);					//178 - 192 Complemento do endereco da empresa
			$linha .= self::formatar_texto($cidade->getnome(), 20);											//193 - 212 Nome da cidade da empresa
			$linha .= self::formatar_inteiro(removeformat($this->estabelecimento->getcep()), 8);			//213 - 220 CEP da empresa
			$linha .= self::formatar_texto($cidade->getuf(), 2);											//221 - 222 UF da empresa
			$linha .= self::formatar_texto(NULL, 8);														//223 - 230 Complemento de registro
			$linha .= self::formatar_texto(NULL, 10);														//231 - 240Ocorrencias (apenas para arquivo de retorno)
		}
		return $linha;
	}

	public function gerar_trailer_lote_240($codigolote, $totalregistroslote, $totalvalorlote, $totaltitulos, $pagar_receber){
		$arr_tamanho_decimal = array("341" => 18, "748", 16);
		$linha = $this->banco->getcodoficial();																	//1 - 3 Codigo do banco na compensacao
		$linha .= self::formatar_inteiro($codigolote, 4);										//4 - 7 Codigo do lote
		$linha .= "5";																			//8 - 8 Tipo do registro trailer do lote
		$linha .= str_repeat(" ", 9);															//9 - 17 brancos
		$linha .= self::formatar_inteiro(($totalregistroslote + 2), 6);							//18 - 23 Total de registros no lote
		if($pagar_receber == self::TIPO_RECEBIMENTO){
			$linha .= self::formatar_inteiro(($totaltitulos ), 6);								//18 - 23 Total de registros no lote
			$linha .= self::formatar_decimal($totalvalorlote, 17, 2);							//24 - 41 Total do valor do lote
			$linha .= $this->formatar_inteiro(0, 6);											//Quantidade de Títulos em Cobrança a Cobrança
			$linha .= $this->formatar_decimal(0, 17, 2);										//Valor Total dosTítulos em Carteiras a Cobrança
			$linha .= $this->formatar_inteiro(0, 6);											//Quantidade de Títulos em Cobrança	Caucionada
			$linha .= $this->formatar_decimal(0, 17, 2);										//Valor Total dosTítulos em Carteiras Caucionada
			$linha .= $this->formatar_inteiro(0, 6);											//Quantidade de Títulos em Cobrança Descontada
			$linha .= $this->formatar_decimal(0, 17, 2);										//Valor Total dosTítulos em Carteiras Descontada
			$linha .= $this->formatar_texto("", 8);												// Complemento de registro
			$linha .= $this->formatar_texto("", 117);											// Codigos de ocorrencia (apenas para retorno)
		}else{
			$linha .= self::formatar_decimal($totalvalorlote, 18, 2);								//24 - 41 Total do valor do lote
			$linha .= self::formatar_inteiro(0, 18);												//42 - 59 Complemento de registro
			if(!in_array($this->banco->getcodoficial(), array("748", "001"))){
				$linha .= self::formatar_texto("", 171);											//66 - 230 Codigos de ocorrencia (apenas para retorno)
			}else{
				$linha .= self::formatar_inteiro(0, 6);												//60 - 65 Complemento de registro
				$linha .= self::formatar_texto("", 165);											//66 - 230 Codigos de ocorrencia (apenas para retorno)
			}
			$linha .= self::formatar_texto("", 10);													//231 - 240 Codigos de ocorrencia (apenas para retorno)
		}
		return $linha;
	}

	public function gerar_segmento_J_240(Lancamento $lancamento, $codigolote, $seq_lote){
		$arr_tamanho_decimal= array("341" => 15, "748" => 13);

		$codbarras = $lancamento->getcodbarras();
		$linha = $this->banco->getcodoficial();													//1 - 3 Codigo do banco na compensacao
		$linha .= self::formatar_inteiro($codigolote, 4);										//4 - 7 Codigo do lote
		$linha .= "3";																			//8 - 8 Tipo de registro
		$linha .= self::formatar_inteiro(($seq_lote + 1), 5);											//9 - 13 Sequencial do registro no lote
		$linha .= "J";																			//14 - 14 Codigo do segmento do registro detalhe
		$linha .= "000";																		//15 - 17 Tipo de movimento (000 = inclusao de pagamento)
		$linha .= self::formatar_inteiro(substr($codbarras, 0, 3), 3);							//18 - 20 Codigo de barras - banco favorecido
		$linha .= self::formatar_inteiro(substr($codbarras, 3, 1), 1);							//21 - 21 Codigo de barras - moeda
		$linha .= self::formatar_inteiro(substr($codbarras, 32, 1), 1);							//22 - 22 Codigo de barras - digito verificador
		$linha .= self::formatar_inteiro(substr($codbarras, 33, 4), 4);							//23 - 26 Codigo de barras - fator de vencimento
		$linha .= self::formatar_inteiro(substr($codbarras, 37, 10), 10);						//27 - 36 Codigo de barras - valor do boleto
		$linha .= self::formatar_inteiro(substr($codbarras, 4, 5).substr($codbarras, 10, 10).substr($codbarras, 21, 10), 25); //37 - 61 Codigo de barras - campo livre
		$linha .= self::formatar_texto($lancamento->getfavorecido(), 30);						//62 - 91 Nome do favorecido
		$linha .= self::formatar_data($lancamento->getdtvencto(), "P");							//92 - 99 Data do vencimento
		/*
		$linha .= self::formatar_decimal($lancamento->getvalorliquido(), $arr_tamanho_decimal[$this->banco->getcodoficial()], 2);					//100 - 114 Valor do titulo
		$linha .= self::formatar_decimal($lancamento->getvalordescto(), $arr_tamanho_decimal[$this->banco->getcodoficial()], 2);					//115 - 129 Valor do desconto/abatimento
		$linha .= self::formatar_decimal($lancamento->getvaloracresc(), $arr_tamanho_decimal[$this->banco->getcodoficial()], 2);					//130 - 144 Valor da mora/multa
		*/
		$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);					//100 - 114 Valor do titulo
		$linha .= self::formatar_decimal($lancamento->getvalordescto(), 15, 2);					//115 - 129 Valor do desconto/abatimento
		$linha .= self::formatar_decimal($lancamento->getvaloracresc(), 15, 2);					//130 - 144 Valor da mora/multa

		$linha .= self::formatar_data($lancamento->getdtvencto(), "P");																//145 - 152 Data do pagamento
		//$linha .= self::formatar_decimal($lancamento->getvalorliquido(), $arr_tamanho_decimal[$this->banco->getcodoficial()], 2);					//153 - 167 Valor do pagamento
		$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);					//153 - 167 Valor do pagamento
		$linha .= self::formatar_inteiro(0, 15);												//168 - 182 Complemento de registro
		$linha .= self::formatar_texto($lancamento->getcodlancto(), 20);						//183 - 202 Seu numero
		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= self::formatar_texto("", 20);												//203 - 222 Complemento de registro
			$linha .= "09";																		//223 - 224 Nosso numero (apenas para retorno)
			$linha .= self::formatar_texto("", 6);												//225 - 230 Ocorrencia (apenas para retorno)
			$linha .= self::formatar_texto("", 10);												//231 - 240 Ocorrencia (apenas para retorno)
		}else{
			$linha .= self::formatar_texto("", 13);												//203 - 222 Complemento de registro
			$linha .= self::formatar_texto("", 15);												//223 - 240 Nosso numero (apenas para retorno)
			$linha .= self::formatar_texto("", 10);												//231 - 240 Ocorrencia (apenas para retorno)
		}
		return $linha;
	}

	public function gerar_segmento_J52_240(VParceiro $parceiro, Lancamento $lancamento, $codigolote, $seq_lote){
		$cnpj_pagador = removeformat($this->estabelecimento->getcpfcnpj());
		$cpfcnpj_beneficiario = strlen($lancamento->getcnpjboleto()) > 0 ?  removeformat($lancamento->getcnpjboleto()) : removeformat($parceiro->getcpfcnpj());
		$linha =  $this->banco->getcodoficial();														//1 - 3 codigo do banco na compesação
		$linha .= self::formatar_inteiro($codigolote, 4);										//4 - 7 Codigo do lote
		$linha .= "3";																			//8 - 8 Tipo de registro
		$linha .= self::formatar_inteiro(($seq_lote + 1), 5);									//9 - 13 Sequencial do registro no lote
		$linha .= "J";																			//14 - 14 Codigo do segmento do registro detalhe
		if(in_array($this->banco->getcodoficial(), array("748"))){
			$linha .= " ";																		//15 - 15 uso exclusivo sincredi (branco)
			$linha .= "01";																		//16 - 17 codigo do movimento
		}else{
			$linha .= "000";																	//15 - 17 Tipo de movimento (000 = inclusao de pagamento)
		}
		$linha .= "52";																			//18 - 19 identificação do registro opcional
		$linha .= "2";																			//20 - 20 tipo de inscrição do pagador
		$linha .= self::formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), 15);//21 - 35 Numero da inscricao(CPF/CNPJ) da empresa
		$linha .= self::formatar_texto( $this->estabelecimento->getrazaosocial(), 40);			//36 - 75 Nome do pagador
		$linha .= (strlen($cpfcnpj_beneficiario) > 11 ? "2" : "1");								//76 - 76 tipo de inscrição do benificiario
		$linha .= self::formatar_inteiro($cpfcnpj_beneficiario, 15);							//77 - 91 Numero da inscricao (CPF/CNPJ) do beneficiario
		$linha .= self::formatar_texto(removespecial($lancamento->getfavorecido()), 40);		//92 - 131 Nome do beneficiario
		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$linha .= "0";																		//132 - 132 tipo de inscricção do sacador avalista
			$linha .= "000000000000000";														//133 - 147 numero da inscrição do sacador avalista
			$linha .= str_pad("", 93, " ", STR_PAD_LEFT);										//148 - 240 brancos
		}else{
			$linha .= str_pad("", 109, " ", STR_PAD_LEFT);
		}
		return $linha;
	}

	public function gerar_segmento_O_240(Lancamento $lancamento, $codigolote, $seq_lote){
		/* ****************************************************************************************************
			 R E G I S T R O   D E T A L H E   D E   L O T E   ( C O N C E S S I O N A R I A   E   T R I B U T O S )
		* *************************************************************************************************** */
		$codbarras = $lancamento->getcodbarras();
		$linha = $this->banco->getcodoficial();														//1 - 3 Codigo do banco na compensacao
		$linha .= self::formatar_inteiro($codigolote, 4);										//4 - 7 Codigo do lote
		$linha .= "3";																			//8 - 8 Tipo de registro
		$linha .= self::formatar_inteiro(($seq_lote + 1), 5);									//9 - 13 Sequencial do registro no lote
		$linha .= "O";																			//14 - 14 Codigo do segmento do registro detalhe
		$linha .= "000";																		//15 - 17 Tipo de movimento (000 = inclusao de pagamento)
		if(in_array($this->banco->getcodoficial(), array("748", "001"))){
			$codbarras_temp = $lancamento->getcodbarras();
			if(in_array($this->banco->getcodoficial(), array("001"))){
				$codbarras = substr($codbarras_temp, 0, 11); // Bloco 1
				$codbarras .= substr($codbarras_temp, 12, 11); // Bloco 2
				$codbarras .= substr($codbarras_temp, 24, 11); // Bloco 3
				$codbarras .= substr($codbarras_temp, 35, 11); // Bloco 4
			}
			$linha .= self::formatar_texto($codbarras, 44);										//18 -61 Codigo de barras
			$linha .= self::formatar_texto($lancamento->getfavorecido(), 30);					//62 - 91 Nome do favorecido
			$linha .= self::formatar_data($lancamento->getdtvencto(), "P");						//92 - 99 Data do vencimento
			$linha .= self::formatar_data($lancamento->getdtvencto(), "P");						//100 - 107 Data do pagamento
			$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);				//108 - 122 Valor previsto do pagamento
			$linha .= self::formatar_texto($lancamento->getcodlancto(), 20);					//123 - 142 Seu numero
			$linha .= self::formatar_texto("", 20);												//143 - 162 nosso numero
			$linha .= self::formatar_texto("", 68);												//163 - 230 Complemento de registro(branco)
			$linha .= self::formatar_texto("", 10);												//231 - 240 Complemento de registro
		}else{
			$linha .= self::formatar_texto($codbarras, 48);										//18 -61 Codigo de barras
			$linha .= self::formatar_texto($lancamento->getfavorecido(), 30);					// Nome do favorecido
			$linha .= self::formatar_data($lancamento->getdtvencto(), "P");						// Data do vencimento
			$linha .= "REA";																	// Tipo de moeda
			$linha .= self::formatar_decimal(0, 15, 8);											// Quantidade da moeda (informar apenas se a moeda for diferente de Real)
			$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);				// Valor previsto do pagamento
			$linha .= self::formatar_data($lancamento->getdtvencto(), "P");						// Data do pagamento
			$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);				// Valor de efetivacao do pagamento
			$linha .= self::formatar_texto("", 3);												// Complemento de registro
			$linha .= self::formatar_inteiro($lancamento->getnumnotafis(), 9);					// Numero da nota fiscal
			$linha .= self::formatar_texto("", 3);												// Complemento de registro
			$linha .= self::formatar_texto($lancamento->getcodlancto(), 20);					// Seu numero
			$linha .= self::formatar_texto("", 21);												// Complemento de registro
			$linha .= self::formatar_texto("", 15);												// Nosso numero (apenas para retorno)
			$linha .= self::formatar_texto("", 10);												// Ocorrencia (apenas para retorno)
		}
		return $linha;
	}

	public function gerar_segmento_P_240(Lancamento $lancamento, $seq_registro, $codigolote){
		/*****************************************************************************************************************
		  R E G I S T R O   D E T A L H E   D E   L O T E   ( C O B R A N Ç A  S I M P L E S - S E G U I M E N T O "P" )
		 * ************************************************************************************************************* */
		$linha = $this->banco->getcodoficial();																				// Codigo do banco na compensacao
		$linha .= $this->formatar_inteiro($codigolote, 4);											// Codigo do lote
		$linha .= "3";																				// Tipo de registro
		$linha .= $this->formatar_inteiro($seq_registro, 5);										// Sequencial do registro no lote
		$linha .= "P";																				// Codigo do segmento do registro detalhe
		$linha .= " ";																				// Uso Exclusivo FEBRABAN/CNAB
		$linha .= "01";																				// Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
		$linha .= self::formatar_inteiro($this->banco->getagencia(), 5);							// Numero da agencia debitada
		$linha .= self::formatar_texto($this->banco->getdigitoagencia(), 1);						// Digito da agencia debitada
		$linha .= self::formatar_inteiro($this->banco->getconta(), 12);								// Numero da conta debitada
		$linha .= self::formatar_texto(substr($this->banco->getdigito(), 0, 1), 1);					// Digito da conta debitada
		$linha .= self::formatar_texto(substr($this->banco->getdigito(), 1, 1), 1);					// Digito da agencia/conta debitada
		$convenio = $this->banco->getcodigoempresa();
		if($this->banco->getbancoemiteboleto() == "S"){
			$linha .= str_repeat(" ", 17)."   ";
		}else{
			if(strlen($lancamento->getnossonumero()) > 0){
				$nossonumero = $lancamento->getnossonumero();
			}else{
				$this->banco->setnossonumero($this->banco->getnossonumero() + 1);
				$nossonumero = $this->banco->getnossonumero();

				if($this->banco->getcarteira() == "21" || strlen($this->banco->getcodigoempresa()) == 7){
					$lancamento->setnossonumero(str_pad($nossonumero, 10, "0", STR_PAD_LEFT));
				}else{
					$lancamento->setnossonumero(str_pad($nossonumero, 8, "0", STR_PAD_LEFT));
				}
			}
			$nossonumero = str_pad($nossonumero, (17 - strlen($convenio)), "0", STR_PAD_LEFT);
			$linha .= self::formatar_texto($convenio.$nossonumero, 20);								// nosso numero
		}
		$linha .= self::formatar_texto($this->banco->getcodcarteira(),1);							//Código da Carteira ('1' = Cobrança Simples '2' = Cobrança Vinculada '3' = Cobrança Caucionada '4' = Cobrança Descontada case "5": "Cobrança Vendo)
		$linha .= self::formatar_texto($this->banco->gettipocadcobranca(), 1);						//Forma de Cadastr. do Título no Banco ('1' = Com Cadastramento (Cobrança Registrada) '2' = Sem Cadastramento (Cobrança sem Registro) Obs.: Destina-se somente para emissão de Boleto de Pagamento pelo banco '3' = Com Cadastramento)
		$linha .= "1";																				//Tipo de documento ('1' = Tradicional '2' = Escritural)
		if($this->banco->getbancoemiteboleto() == "S"){												//Identificação da Emissão do Boleto de Pagamento
			$linha .= "1";																			//'1' = Banco Emite
		}else{
			$linha .= "2";																			//'2' = Cliente Emite
		}
		$linha .= self::formatar_inteiro($this->banco->gettipodistribuicao(), 1);					//Identificação da Distribuição ('1' = Banco Distribui '2' = Cliente Distribui '3' Banco envia e-mail '4' Banco envia SMS)
		$linha .= self::formatar_texto($lancamento->getnumnotafis(), 15);							//Número do Documento de Cobrança (seu numero)
		$linha .= self::formatar_data($lancamento->getdtvencto(), "");								// Data do vencimento
		$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);						// Valor nominal do titulo
		$linha .= str_repeat("0", 5);																// Agência Encarregada da Cobrança
		$linha .= "0";		 // Digito da agencia debitada
		$linha .= "02";																				//Especie do titulo '02' = DM Duplicata Mercantil
		if($this->banco->getaceitetitulo() == "S"){													//Identific. de Título Aceito/Não Aceito
			$linha .= "A";
		}else{
			$linha .= "N";
		}
		$linha .= self::formatar_data($lancamento->getdtemissao(), "");								// Data de emissão do titulo
		if($this->banco->getvalormoradiaria() > 0){
			$linha .= "1";																			//Código do Juros de Mora (Valor Diario)
			$linha .= date("dmY", strtotime("+1 day", strtotime($lancamento->getdtvencto())));		// Data do Juros de Mora
		}else{
			$linha .= "3";																			//Código do Juros de Mora(Isento)
			$linha .= str_repeat("0", 8);															//Data do Juros de Mora
		}
		$linha .= self::formatar_decimal(($lancamento->getvalorliquido() * ($this->banco->getvalormoradiaria() / 100) / 30), 15, 2); // Valor de juro de mora diaria
		$linha .= "0";																				//Código do Desconto 1
		$linha .= str_repeat("0", 8);																//Data do Desconto 1
		$linha .= str_repeat("0", 15);																//Valor/Percentual a ser Concedido
		$linha .= str_repeat("0", 15);																//Valor do IOF a ser Recolhido
		$linha .= str_repeat("0", 15);																//Valor do Abatimento
		$linha .= self::formatar_texto($lancamento->getcodlancto(), 25);							//Identificação do Título na Empresa
		if($this->banco->getdiasprotesto() > 0){
			$linha .= "1";																			//Código para Protesto
			$linha .= self::formatar_inteiro($this->banco->getdiasprotesto(), 2);					//Número de Dias para Protesto
		}else{
			$linha .= "3";																			//Código para Protesto
			$linha .= "00";																			//Número de Dias para Protesto
		}
		$linha .= "0";																				//Código para Baixa/Devolução
		$linha .= str_repeat("0", 3);																//Número de Dias para Baixa/Devolução
		$linha .= "09";																				//Código da Moeda
		$linha .= str_repeat("0", 10);																//Nº do Contrato da Operação de Créd.
		$linha .= " ";																				//Uso livre banco/empresa ou autorização de pagamento parcial
		return $linha;
	}

	public function gerar_segmento_Q_240(Lancamento $lancamento, $seq_registro, $codigolote){
		/*****************************************************************************************************************
			R E G I S T R O   D E T A L H E   D E   L O T E   ( C O B R A N Ç A  S I M P L E S - S E G U I M E N T O "Q" )
		* ************************************************************************************************************* */
		$parceiro = $this->arr_parceiro[$lancamento->gettipoparceiro().";".$lancamento->getcodparceiro()];
		$linha = $this->banco->getcodoficial();														// Codigo do banco na compensacao
		$linha .= $this->formatar_inteiro($codigolote, 4);											// Codigo do lote
		$linha .= "3";																				// Tipo de registro
		$linha .= $this->formatar_inteiro($seq_registro, 5);										// Sequencial do registro no lote
		$linha .= "Q";																				// Codigo do segmento do registro detalhe
		$linha .= " ";																				// Uso Exclusivo FEBRABAN/CNAB
		$linha .= "01";																				// Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
		$cpfcnpj = removeformat($parceiro->getcpfcnpj());
		if(strlen($cpfcnpj) > 11){
		  $linha .= "2";																			//Tipo de Inscrição da Empresa
		}else{
		  $linha .= "1";																			//Tipo de Inscrição da Empresa
		}
		$numero_endereco = ", ".trim($parceiro->getnumero());
		$linha .= $this->formatar_inteiro(removeformat($parceiro->getcpfcnpj()), 15);				//Número de Inscrição
		$linha .= $this->formatar_texto($parceiro->getrazaosocial(), 40);							//Nome parceiro
		$linha .= $this->formatar_texto($parceiro->getendereco(), 40 - strlen($numero_endereco)).$numero_endereco; //Endereço
		$linha .= $this->formatar_texto($parceiro->getbairro(), 15);								//Bairro
		$linha .= $this->formatar_texto($parceiro->getcep(), 5);									//CEP
		$linha .= substr($parceiro->getcep(), 6, 3);												//sufixo do CEP
		$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
		$linha .= $this->formatar_texto($cidade_parceiro->getnome(), 15);							// cidade
		$linha .= $parceiro->getuf();																// Unidade federação
		$linha .= " ";																				//Tipo inscrição sacador avalista
		$linha .= str_repeat(" ", 15);																//numero inscrição sacador avalista
		$linha .= str_repeat(" ", 40);																//nome sacador avalista
		$linha .= "000";																			//Cód. Bco. Corresp. na Compensação
		$linha .= str_repeat(" ", 20);																//Nosso Nº no Banco Correspondente
		$linha .= str_repeat(" ", 8);																//Uso Exclusivo FEBRABAN/CNAB
		return $linha;
	}

	public function gerar_segmento_N_240(Cidade $cidade, Estabelecimento $estabelecimento, Lancamento $lancamento, $codigolote, $seq_lote){
	   /** *************************************************************************************************************
		R E G I S T R O   D E T A L H E   D E   L O T E   ( T R I B U T O S   S E M   C O D I G O S   D E   B A R R A S )
	   * ************************************************************************************************************* */
		$codbarras = $lancamento->getcodbarras();
		$linha = $this->banco->getcodoficial();																			//1 - 3 Codigo do banco na compensacao
		$linha .= self::formatar_inteiro($codigolote, 4);										//4 - 7 Codigo do lote
		$linha .= "3";																			//8 - 8 Tipo de registro
		$linha .= self::formatar_inteiro(($seq_lote + 1), 5);									//9 - 13 Sequencial do registro no lote
		$linha .= "N";																			//14 - 14 Codigo do segmento do registro detalhe
		$linha .= "000";																		//15 - 17 Tipo de movimento (000 = inclusao de pagamento)
		if(in_array($this->banco->getcodoficial(), array("748"))){
			$linha .= self::formatar_texto($lancamento->getcodlancto(), 20);					//18 - 37 Seu numero
			$linha .= self::formatar_texto("", 20);												//38 - 57 Nosso numero (apenas para retorno)
			$linha .= self::formatar_texto($lancamento->getfavorecido(), 30);					//58 - 87 Nome do contribuinte
			$linha .= self::formatar_data($lancamento->getdtvencto(), "P");						//88 - 95 Data do pagamento
			$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);				//95 - 110 Valor total do pagamento
		}
		switch($lancamento->getcodtipoaceite()){
			case 4: // Composicao dos dados para pagamento de DARF normal
				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("", 6);													//111 - 116	Codigo da receita de tributo
					$linha .= self::formatar_inteiro(1, 2);													//117 - 118 Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				}else{
					$linha .= "02";																			// Identificacao do tributo (02 = DARF)
					$linha .= self::formatar_inteiro(0, 4);													// Codigo da receita
					$linha .= "2";																			// Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				}

				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14);		//119 - 132 CPF ou CNPJ do contribuinte

				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("16", 2);												//133 - 134
				}

				$linha .= self::formatar_data($lancamento->getdtemissao(), "P");							//135 - 142 Periodo de apuracao
				$linha .= self::formatar_inteiro($lancamento->getreferencia(), 17);							//143 - 159 Numero de referencia
				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2);					//160 - 174 Valor principal
					$linha .= self::formatar_decimal($lancamento->getvaloracresc(), 15, 2);					//175 - 189 Valor da multa
					$linha .= self::formatar_decimal($lancamento->getvalorjuros(), 15, 2);					//190 - 204 Valor do juros/encargos
				}else{
					$linha .= self::formatar_decimal($lancamento->getvalorparcela(), 14, 2);					// Valor principal
					$linha .= self::formatar_decimal($lancamento->getvaloracresc(), 14, 2);					// Valor da multa
					$linha .= self::formatar_decimal($lancamento->getvalorjuros(), 14, 2);					// Valor do juros/encargos
					$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2);					// Valor total a ser pago
				}

				$linha .= self::formatar_data($lancamento->getdtvencto(), "P");								//205 - 212 Data do vencimento

				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("", 18);													//213 - 230 Complemento do registro
				}else{
					$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
					$linha .= self::formatar_texto("", 30); // Complemento do registro
					$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
				}
				break;
			case 5: // Composicao dos dados para pagamento de DARF simples
				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("", 6);													//111 - 116	Codigo da receita de tributo
					$linha .= self::formatar_inteiro(1, 2);													//117 - 118 Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				}else{
					$linha .= "03"; // Identificacao do tributo (03 = DARF simples)
					$linha .= self::formatar_inteiro(0, 4); // Codigo da receita
					$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				}

				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14);		//119 - 132 CPF ou CNPJ do contribuinte
				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("18", 2);												//133 - 134
				}
				$linha .= self::formatar_data($lancamento->getdtemissao(), "P");							//135 - 142 Periodo de apuracao
				$linha .= self::formatar_decimal(0, 9, 2);													//143 - 157 Valor da receita bruta acumulada
				$linha .= self::formatar_decimal(0, 4, 2);													//158 - 164 Percentual sobre a receita bruta acumulada
				if(!in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("", 4); // Complemento do registro
					$linha .= self::formatar_decimal($lancamento->getvalorparcela(), 14, 2);					// Valor principal
				}else{
					$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2);					//165 - 179 Valor total a ser pago
				}
				$linha .= self::formatar_decimal($lancamento->getvaloracresc(), 14, 2);						//180 - 194 Valor da multa
				$linha .= self::formatar_decimal($lancamento->getvalorjuros(), 14, 2);						//195 - 209 Valor do juros/encargos
				if(!in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2);					// Valor total a ser pago
					$linha .= self::formatar_data($lancamento->getdtvencto(), "P");							// Data do vencimento
					$linha .= self::formatar_data($lancamento->getdtvencto(), "P");							// Data do pagamento
					$linha .= self::formatar_texto("", 30); // Complemento do registro
					$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
				}else{
					$linha .= self::formatar_texto("", 21);													//210 - 230	Codigo da receita de tributo
				}
				break;
			case 6: // Composicao dos dados para pagamento de DARJ
				$linha .= "04"; // Identificacao do tributo (04 = DARJ)
				$linha .= self::formatar_inteiro(0, 4); // Codigo da receita
				$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
				$linha .= self::formatar_inteiro(0, 16); // Numero do documento origem
				$linha .= self::formatar_texto("", 1); // Complemento de registro
				$linha .= self::formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor principal
				$linha .= self::formatar_decimal(0, 14, 2); // Valor da atualizacao monetaria
				$linha .= self::formatar_decimal(0, 14, 2); // Valor da mora
				$linha .= self::formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
				$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor total a ser pago
				$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
				$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
				$linha .= self::formatar_inteiro($lancamento->getparcela(), 6); // Periodo de referencia ou numero de parcela
				$linha .= self::formatar_texto("", 10); // Complemento do registro
				$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
				break;
			case 7: // Composicao dos dados para pagamento de GARE - SP ICMS
				$linha .= "05"; // Identificacao do tributo (05 = ICMS)
				$linha .= self::formatar_inteiro(0, 4); // Codigo da receita
				$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
				$linha .= self::formatar_inteiro(0, 13); // Divida ativa / numero etiqueta
				$linha .= self::formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "mY"), 6); // Mes/ano de referencia
				$linha .= self::formatar_inteiro($lancamento->getparcela(), 13); // Numero de parcela/notificacao
				$linha .= self::formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor da receita
				$linha .= self::formatar_decimal($lancamento->getvalorjuros(), 14, 2); // Valor do juros
				$linha .= self::formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
				$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor do pagamento
				$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
				$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
				$linha .= self::formatar_texto("", 11); // Complemento do registro
				$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
				break;
			case 8: // Composicao dos dados para pagamento de GPS
				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("", 6);																		//111 - 116	Codigo da receita de tributo
					$linha .= self::formatar_inteiro(1, 2);																	//117 - 118 Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				}else{
					$linha .= "02";																								// Identificacao do tributo (02 = GPS)
					$linha .= "2100";																							// Codigo de pagamento (2100 = empresa em geral  - CNPJ)
					$linha .= self::formatar_inteiro($lancamento->getmescompetencia().$lancamento->getanocompetencia(), 6);	// Mes e ano da competencia
				}
				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14);						//119 - 132 Identificacao CNPJ do contribuinte
				if(in_array($this->banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_texto("17", 2);																	//133 - 134
					$linha .= date($lancamento->getdtemissao(), "mY");															//135 - 140 periodo de apuração
				}else{

				}
				$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2);										//141 - 155 Valor previsto do pagamento do INSS
				$linha .= self::formatar_decimal(0, 14, 2);																	//156 - 170 Valor de outras entidades
				$linha .= self::formatar_decimal(0, 14, 2);																	//171 - 185 Atualizacao monetaria
				if(!in_array($banco->getcodoficial(), array("748"))){
					$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2);									// Valor arrecadado
					$linha .= self::formatar_data(date("Y-m-d"), "P");															// Data da arrecadacao/efetivacao do pagamento
					$linha .= self::formatar_texto("", 8);																		// Complemento do registro
					$linha .= self::formatar_texto("", 50);																	// Informacoes complementares
					$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30);				// Nome do contribuinte
				}else{
					$linha .= self::formatar_texto("", 30);																	//186 - 230 Informacoes complementares
				}
				break;
			case 9: // Composicao dos dados para pagamento de IPVA
			case 12: // Composicao dos dados para pagamento de DPVAT
				$linha .= ($lancamento->getcodtipoaceite() == 9 ? "07" : "08"); // Identificacao do tributo (07 = IPVA; 08 = DPVAT)
				$linha .= self::formatar_texto("", 4); // Complemento de registro
				$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
				$linha .= self::formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "Y"), 4); // Ano base
				$linha .= self::formatar_inteiro(0, 9); // Codigo do RENAVAM
				$linha .= self::formatar_inteiro(0, 13); // Divida ativa / numero etiqueta
				$linha .= self::formatar_texto($cidade->getuf(), 2); // Unidade da federacao
				$linha .= self::formatar_inteiro($cidade->getcodoficial(), 5); // Codigo do municipio
				$linha .= self::formatar_texto("", 7); // Placa do veiculo
				$linha .= ($lancamento->getcodtipoaceite() == 9 ? "2" : "0"); // Opcao de pagamento
				$linha .= self::formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor do IPVA/DPVAT
				$linha .= self::formatar_decimal($lancamento->getvalordesconto(), 14, 2); // Valor do desconto
				$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor do pagamento
				$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
				$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
				$linha .= self::formatar_texto("", 41); // Complemento do registro
				$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
				break;
			case 13: // Composicao dos dados para pagamento de FGTS-GRF/GRRF/GRDE
				$linha .= "11"; // Identificacao do tributo (11 = FGTS)
				$linha .= self::formatar_inteiro(0, 4); // Codigo da receita
				$linha .= "1"; // Tipo de inscricao do contribuinte (1 = CNPJ; 2 = CEI)
				$linha .= self::formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
				$linha .= self::formatar_texto($lancamento->getcodbarras(), 48); // Codigo de barras
				$linha .= self::formatar_inteiro(0, 16); // Identificador do FGTS
				$linha .= self::formatar_inteiro(0, 9); // Lacre de conectividade social
				$linha .= self::formatar_inteiro(0, 2); // Digito do lacre de conectividade social
				$linha .= self::formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
				$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
				$linha .= self::formatar_texto("", 30); // Complemento do registro
				break;
		}
		if(!in_array($banco->getcodoficial(), array("748"))){
			$linha .= self::formatar_texto($lancamento->getfavorecido(), 30); // Nome do favorecido
			$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
			$linha .= "REA"; // Tipo de moeda
			$linha .= self::formatar_decimal(0, 15, 8); // Quantidade da moeda (informar apenas se a moeda for diferente de Real)
			$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor previsto do pagamento
			$linha .= self::formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
			$linha .= self::formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor de efetivacao do pagamento
			$linha .= self::formatar_texto("", 3); // Complemento de registro
			$linha .= self::formatar_inteiro($lancamento->getnumnotafis(), 9); // Numero da nota fiscal
			$linha .= self::formatar_texto("", 3); // Complemento de registro
			$linha .= self::formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
			$linha .= self::formatar_texto("", 21); // Complemento de registro
			$linha .= self::formatar_texto("", 15); // Nosso numero (apenas para retorno)
		}
		$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
		return $linha;
	}

	public function getdadosliquidacao($linha_detalhe){
		$dados_liquidacao = new stdClass();
		$dados_liquidacao->ocorrencias = substr($linha_detalhe, 230, 10);
		switch(substr($linha_detalhe, 13, 1)){
			case "J": // Liquidacao de titulos (boletos)
				$dados_liquidacao->codlancto = (int) substr($linha_detalhe, 182, 20);
				$dados_liquidacao->dtliquid = substr($linha_detalhe, 144, 8);
				$dados_liquidacao->valorparcela = substr($linha_detalhe, 157, 10) / 100;
				break;
			case "O": // Pagamento de contas de concessionaria e tributos com codigo de barras
				$dados_liquidacao->codlancto = (int) substr($linha_detalhe, 174, 20);
				$dados_liquidacao->dtliquid = substr($linha_detalhe, 136, 8);
				break;
			case "N": // Pagamento de tributos sem codigo de barras
				$dados_liquidacao->codlancto = (int) substr($linha_detalhe, 215, 20);
				switch(substr($linha_detalhe, 17, 2)){
					case "01":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 99, 8);
						break;
					case "02":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 127, 8);
						break;
					case "03":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 127, 8);
						break;
					case "04":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 141, 8);
						break;
					case "05":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 146, 8);
						break;
					case "07":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 116, 8);
						break;
					case "08":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 116, 8);
						break;
					case "11":
						$dados_liquidacao->dtliquid = substr($linha_detalhe, 143, 8);
						break;
				}
				break;
		}
		$dados_liquidacao->dtliquid = convert_date($dados_liquidacao->dtliquid, "dmY", "Y-m-d");
		return $dados_liquidacao;
	}

	public function ocorrenciapagamento($codigo){
		if($this->banco->getcodoficial() == "001"){ //banco do brasil
			switch($codigo){
				case "GN": return "Geração normal.";
					break;
				case "00": return "Pagamento efetuado.";
					break;
				case "AE": return "Data de pagamento alterada.";
					break;
				case "AG": return "Número do lote inválido.";
					break;
				case "AH": return "Número sequencial do registro no lote inválido.";
					break;
				case "AI": return "Produto demonstrativo de pagamento não contratado.";
					break;
				case "AJ": return "Tipo de movimento inválido.";
					break;
				case "AL": return "Código do banco favorecido inválido.";
					break;
				case "AM": return "Agência do favorecido inválida.";
					break;
				case "AN": return "Conta corrente do favorecido inválida.";
					break;
				case "AO": return "Nome do favorecido inválido.";
					break;
				case "AP": return "Data de pagamento, data de validade, hora de lançamento, arrecadação ou apuração inválida.";
					break;
				case "AQ": return "Quantidade de registros maior que 999999.";
					break;
				case "AR": return "Valor arrecadado ou lançamento inválido.";
					break;
				case "BC": return "Nosso número inválido.";
					break;
				case "BD": return "Pagamento agendado.";
					break;
				case "BE": return "Pagamento agendado com forma alterada para OP.";
					break;
				case "BI": return "CNPJ/CPF do favorecido no segmento B inválido.";
					break;
				case "BL": return "Valor da parcela inválido.";
					break;
				case "CD": return "CNPJ/CPF informado divergente do cadastrado.";
					break;
				case "CE": return "Pagamento cancelado.";
					break;
				case "CF": return "Valor do documento inválido.";
					break;
				case "CG": return "Valor do abatimento inválido.";
					break;
				case "CH": return "Valor do desconto inválido.";
					break;
				case "CI": return "CNPJ, CPF, identificador, inscrição estadual, inscrição no CAD ou ICMS inválido.";
					break;
				case "CJ": return "Valor da multa inválido.";
					break;
				case "CK": return "Tipo de inscrição inválida.";
					break;
				case "CL": return "Valor do INSS inválido.";
					break;
				case "CM": return "Valor do Cofins inválido.";
					break;
				case "CN": return "Conta não cadastrada.";
					break;
				case "CO": return "Valor de outras entidades inválido.";
					break;
				case "CP": return "Confirmação de OP cumprida.";
					break;
				case "CQ": return "Soma das faturas difere do pagamento.";
					break;
				case "CR": return "Valor do CSLL inválido.";
					break;
				case "CS": return "Data de vencimento da fatura inválida.";
					break;
				case "DA": return "Número de dependência do salário familia inválido.";
					break;
				case "DB": return "Número de horas semanais inválido.";
					break;
				case "DC": return "Salário de contribuição INSS inválido.";
					break;
				case "DD": return "Salário de contribuição FGTS inválido.";
					break;
				case "DE": return "Valor total dos proventos inválido.";
					break;
				case "DF": return "Valor total dos descontos inválido.";
					break;
				case "DG": return "Valor líquido não numérico.";
					break;
				case "DH": return "Valor liquido calculado difere do calculado.";
					break;
				case "DI": return "Valor do salário-base inválido.";
					break;
				case "DJ": return "Base de calculo IRRF inválida.";
					break;
				case "DK": return "Base de calculo FGTS inválida.";
					break;
				case "DL": return "Forma de pagamento incompatível com holerite.";
					break;
				case "DM": return "E-mail do favorecido inválido.";
					break;
				case "DV": return "DOC ou TED devolvido pelo banco favorecido.";
					break;
				case "D0": return "Finalidade de holerite inválida.";
					break;
				case "D1": return "Mês de competência do holerite inválida.";
					break;
				case "D2": return "Dia da competência do holerite inválida.";
					break;
				case "D3": return "Centro de custo inválido.";
					break;
				case "D4": return "Campo numérico da funcional inválido.";
					break;
				case "D5": return "Data de início de férias não numérica.";
					break;
				case "D6": return "Data de início de férias inconsistente.";
					break;
				case "D7": return "Data de fim de férias não numérico.";
					break;
				case "D8": return "Data de fim de férias inconsistente.";
					break;
				case "D9": return "Número de dependentes IR inválido.";
					break;
				case "EM": return "Confirmação de OP emitida.";
					break;
				case "EX": return "Devolução de OP não sacada pelo favorecido.";
					break;
				case "E0": return "Tipo de movimento holerite inválido.";
					break;
				case "E1": return "Valor 01 do holerite/informe inválido.";
					break;
				case "E2": return "Valor 02 do holerite/informe inválido.";
					break;
				case "E3": return "Valor 03 do holerite/informe inválido.";
					break;
				case "E4": return "Valor 04 do holerite/informe inválido.";
					break;
				case "FC": return "Pagamento efetuado através de financiamento compror.";
					break;
				case "FD": return "Pagamento efetuado através de financiamento descompror.";
					break;
				case "IB": return "Valor do documento inválido.";
					break;
				case "IC": return "Valor do abatimento inválido.";
					break;
				case "ID": return "Valor do desconto inválido.";
					break;
				case "IE": return "Valor da mora inválido.";
					break;
				case "IF": return "Valor da multa inválido.";
					break;
				case "IG": return "Valor da dedução inválido.";
					break;
				case "IH": return "Valor do acrescimo inválido.";
					break;
				case "II": return "Data de vencimento inválida.";
					break;
				case "IJ": return "Competência, período referência ou parcela inválida.";
					break;
				case "IK": return "Tributo não liquidável via SISPAG ou não conveniado com Itaú.";
					break;
				case "IL": return "Código de pagamento, empresa ou receita inválido.";
					break;
				case "IM": return "Tipo de pagamento não compatível com forma de pagamento.";
					break;
				case "IN": return "Banco ou agência não cadastrados.";
					break;
				case "IO": return "DAC, valor, competência ou identificador do lacre inválido.";
					break;
				case "IP": return "DAC do código de barras inválido.";
					break;
				case "IQ": return "Dívida ativa ou número de etiqueta inválido.";
					break;
				case "IR": return "Pagamento alterado.";
					break;
				case "IS": return "Concessionária não conveniada com Itaú.";
					break;
				case "IT": return "Valor do tributo inválido.";
					break;
				case "IU": return "Valor da receita bruta acumulada inválido.";
					break;
				case "IV": return "Número do documento origem ou referência inválido.";
					break;
				case "IX": return "Código do produto inválido.";
					break;
				case "LA": return "Data de pagamento de um lote alterada.";
					break;
				case "LC": return "Lote de pagamento cancelado.";
					break;
				case "NA": return "Pagamento cancelado por falta de autorização.";
					break;
				case "NB": return "Identificação do tributo inválida.";
					break;
				case "NC": return "Exercício (ano base) inválido.";
					break;
				case "ND": return "Código RENAVAM não encontrado ou inválido.";
					break;
				case "NE": return "UF inválida.";
					break;
				case "NF": return "Código do município inválido.";
					break;
				case "NG": return "Placa inválida.";
					break;
				case "NH": return "Opção ou parcela de pagamento inválida.";
					break;
				case "NI": return "Tributo já foi pago ou está vencido.";
					break;
				case "NR": return "Operação não realizada";
					break;
				case "PD": return "Aquisição confirmada. (equivale a ocorrência 02 no layout de risco sacado)";
					break;
				case "RJ": return "Registro rejeitado.";
					break;
				case "SS": return "Pagamento cancelado por insuficiência de saldo ou limite diário de pagamento.";
					break;
				case "TA": return "Lote não aceito - totais do lote com diferença.";
					break;
				case "TI": return "Titularidade inválida.";
					break;
				case "X1": return "Forma incompatível com layout 010.";
					break;
				case "X2": return "Número da nota fiscal inválido.";
					break;
				case "X3": return "Indentificador de NF/CNPJ inválido.";
					break;
				case "X4": return "Forma 32 inválida.";
					break;
				default : return "Ocorrência: ".$codigo;
					break;
			}
		}

		if($this->banco->getcodoficial() == "748"){	//banco sicredi
			switch($codigo){
				case "00": return "crédito ou débito efetivado  indica que o pagamento foi confirmado";
				case "01": return "insuficiência de fundos - débito não efetuado";
				case "02": return "crédito ou débito cancelado pelo pagador/credor";
				case "03": return "débito autorizado pela agência - efetuado";
				case "AA": return "controle inválido";
				case "AB": return "tipo de operação inválido";
				case "AC": return "tipo de serviço inválido";
				case "AD": return "forma de lançamento inválida";
				case "AE": return "tipo/número de inscrição inválido";
				case "AF": return "código de convênio inválido";
				case "AG": return "agência/conta corrente/DV inválido";
				case "AH": return "nº sequencial do registro no lote inválido";
				case "AI": return "código de segmento de detalhe inválido";
				case "AJ": return "tipo de movimento inválido";
				case "AK": return "código da câmara de compensação do banco favorecido/depositário inválido";
				case "AL": return "código do banco favorecido ou depositário inválido";
				case "AM": return "agência mantenedora da conta corrente do favorecido inválida";
				case "AN": return "conta corrente/DV do favorecido inválido";
				case "AO": return "nome do favorecido não informado";
				case "AP": return "data lançamento inválido";
				case "AQ": return "tipo/quantidade da moeda inválido";
				case "AR": return "valor do lançamento inválido";
				case "AS": return "aviso ao favorecido - identificação inválida";
				case "AT": return "tipo/número de inscrição do favorecido inválido";
				case "AU": return "logradouro do favorecido não informado";
				case "AV": return "nº do local do favorecido não informado";
				case "AW": return "cidade do favorecido não informada";
				case "AX": return "CEP/complemento do favorecido inválido";
				case "AY": return "sigla do estado do favorecido inválida";
				case "AZ": return "código/nome do banco depositário inválido";
				case "BA": return "código/nome da agência depositária não informado";
				case "BB": return "seu número inválido";
				case "BC": return "nosso número inválido";
				case "BD": return "inclusão efetuada com sucesso";
				case "BE": return "alteração efetuada com sucesso";
				case "BF": return "exclusão efetuada com sucesso";
				case "BG": return "agência/conta impedida legalmente";
				case "BH": return "empresa não pagou salário";
				case "BI": return "falecimento do mutuário";
				case "BJ": return "empresa não enviou remessa do mutuário";
				case "BK": return "empresa não enviou remessa no vencimento";
				case "BL": return "valor da parcela inválida";
				case "BM": return "identificação do contrato inválida";
				case "BN": return "operação de consignação incluída com sucesso";
				case "BO": return "operação de consignação alterada com sucesso";
				case "BP": return "operação de consignação excluída com sucesso";
				case "BQ": return "operação de consignação liquidada com sucesso";
				case "CA": return "código de barras - código do banco inválido";
				case "CB": return "código de barras - código da moeda inválido";
				case "CC": return "código de barras - dígito verificador geral inválido";
				case "CD": return "código de barras - valor do título inválido";
				case "CE": return "código de barras - campo livre inválido";
				case "CF": return "valor do documento inválido";
				case "CG": return "valor do abatimento inválido";
				case "CH": return "valor do desconto inválido";
				case "CI": return "valor de mora inválido";
				case "CJ": return "valor da multa inválido";
				case "CK": return "valor do IR inválido";
				case "CL": return "valor do ISS inválido";
				case "CM": return "valor do IOF inválido";
				case "CN": return "valor de outras deduções inválido";
				case "CO": return "valor de outros acréscimos inválido";
				case "CP": return "valor do INSS inválido";
				case "HA": return "lote não aceito";
				case "HB": return "inscrição da empresa inválida para o contrato";
				case "HC": return "convênio com a empresa inexistente/inválido para o contrato";
				case "HD": return "agência/conta corrente da empresa inexistente/inválido para o contrato";
				case "HE": return "tipo de serviço inválido para o contrato";
				case "HF": return "conta corrente da empresa com saldo insuficiente";
				case "HG": return "lote de serviço fora de sequência";
				case "HH": return "lote de serviço inválido";
				case "HI": return "arquivo não aceito";
				case "HJ": return "tipo de registro inválido";
				case "HK": return "código remessa / retorno inválido";
				case "HL": return "versão de leiaute inválida";
				case "HM": return "mutuário não identificado";
				case "HN": return "tipo do benefício não permite empréstimo";
				case "HO": return "benefício cessado/suspenso";
				case "HP": return "benefício possui representante legal";
				case "HQ": return "benefício é do tipo PA (pensão alimentícia)";
				case "HR": return "quantidade de contratos permitida excedida";
				case "HS": return "benefício não pertence ao banco informado";
				case "HT": return "início do desconto informado já ultrapassado";
				case "HU": return "número da parcela inválida";
				case "HV": return "quantidade de parcela inválida";
				case "HW": return "margem consignável excedida para o mutuário dentro do prazo do contrato";
				case "HX": return "empréstimo já cadastrado";
				case "HY": return "empréstimo inexistente";
				case "HZ": return "empréstimo já encerrado";
				case "H1": return "arquivo sem trailer";
				case "H2": return "mutuário sem crédito na competência";
				case "H3": return "não descontado – outros motivos";
				case "H4": return "retorno de crédito não pago";
				case "H5": return "cancelamento de empréstimo retroativo";
				case "H6": return "outros motivos de glosa";
				case "H7": return "margem consignável excedida para o mutuário acima do prazo do contrato";
				case "H8": return "mutuário desligado do empregador";
				case "H9": return "mutuário afastado por licença";
				case "TA": return "lote não aceito - totais do lote com diferença";
				case "YA": return "título não encontrado";
				case "YB": return "identificador registro opcional inválido";
				case "YC": return "código padrão inválido";
				case "YD": return "código de ocorrência inválido";
				case "YE": return "complemento de ocorrência inválido";
				case "YF": return "alegação já informada";
				case "ZA": return "Agência / Conta do Favorecido Substituída";
				case "ZB": return "Divergência entre o primeiro e último nome do beneficiário versus primeiro e último nome na Receita Federal";
				case "ZC": return "Confirmação de Antecipação de Valor";
				case "ZD": return "Antecipação parcial de valor";
			}
		}

		if($this->banco->getcodoficial() == "341"){	//banco itaú
			switch($codigo){
				case "00": return "Pagamento efetuado.";
				case "AE": return "Data de pagamento alterada.";
				case "AG": return "Numero do lote invalido.";
				case "AH": return "Numero sequencial do registro no lote invalido.";
				case "AI": return "Produto demonstrativo de pagamento nao contratado.";
				case "AJ": return "Tipo de movimento invalido.";
				case "AL": return "Codigo do banco favorecido invalido.";
				case "AM": return "Agencia do favorecido invalida.";
				case "AN": return "Conta corrente do favorecido invalida.";
				case "AO": return "Nome do favorecido invalido.";
				case "AP": return "Data de pagamento, data de validade, hora de lancamento, arrecadacao ou apuracao invalida.";
				case "AQ": return "Quantidade de registros maior que 999999.";
				case "AR": return "Valor arrecadado ou lancamento invalido.";
				case "BC": return "Nosso numero invalido.";
				case "BD": return "Pagamento agendado.";
				case "BE": return "Pagamento agendado com forma alterada para OP.";
				case "BI": return "CNPJ/CPF do favorecido no segmento B invalido.";
				case "BL": return "Valor da parcela invalido.";
				case "CD": return "CNPJ/CPF informado divergente do cadastrado.";
				case "CE": return "Pagamento cancelado.";
				case "CF": return "Valor do documento invalido.";
				case "CG": return "Valor do abatimento invalido.";
				case "CH": return "Valor do desconto invalido.";
				case "CI": return "CNPJ, CPF, identificador, inscricao estadual, inscricao no CAD ou ICMS invalido.";
				case "CJ": return "Valor da multa invalido.";
				case "CK": return "Tipo de inscricao invalida.";
				case "CL": return "Valor do INSS invalido.";
				case "CM": return "Valor do Cofins invalido.";
				case "CN": return "Conta nao cadastrada.";
				case "CO": return "Valor de outras entidades invalido.";
				case "CP": return "Confirmacao de OP cumprida.";
				case "CQ": return "Soma das faturas difere do pagamento.";
				case "CR": return "Valor do CSLL invalido.";
				case "CS": return "Data de vencimento da fatura invalida.";
				case "DA": return "Numero de dependencia do salario familia invalido.";
				case "DB": return "Numero de horas semanais invalido.";
				case "DC": return "Salario de contribuicao INSS invalido.";
				case "DD": return "Salario de contribuicao FGTS invalido.";
				case "DE": return "Valor total dos proventos invalido.";
				case "DF": return "Valor total dos descontos invalido.";
				case "DG": return "Valor liquido nao numerico.";
				case "DH": return "Valor liquido calculado difere do calculado.";
				case "DI": return "Valor do salario-base invalido.";
				case "DJ": return "Base de calculo IRRF invalida.";
				case "DK": return "Base de calculo FGTS invalida.";
				case "DL": return "Forma de pagamento incompativel com holerite.";
				case "DM": return "E-mail do favorecido invalido.";
				case "DV": return "DOC ou TED devolvido pelo banco favorecido.";
				case "D0": return "Finalidade de holerite invalida.";
				case "D1": return "Mes de competencia do holerite invalida.";
				case "D2": return "Dia da competencia do holerite invalida.";
				case "D3": return "Centro de custo invalido.";
				case "D4": return "Campo numerico da funcional invalido.";
				case "D5": return "Data de inicio de ferias nao numerica.";
				case "D6": return "Data de inicio de ferias inconsistente.";
				case "D7": return "Data de fim de ferias nao numerico.";
				case "D8": return "Data de fim de ferias inconsistente.";
				case "D9": return "Numero de dependentes IR invalido.";
				case "EM": return "Confirmacao de OP emitida.";
				case "EX": return "Devolucao de OP nao sacada pelo favorecido.";
				case "E0": return "Tipo de movimento holerite invalido.";
				case "E1": return "Valor 01 do holerite/informe invalido.";
				case "E2": return "Valor 02 do holerite/informe invalido.";
				case "E3": return "Valor 03 do holerite/informe invalido.";
				case "E4": return "Valor 04 do holerite/informe invalido.";
				case "FC": return "Pagamento efetuado atraves de financiamento compror.";
				case "FD": return "Pagamento efetuado atraves de financiamento descompror.";
				case "IB": return "Valor do documento invalido.";
				case "IC": return "Valor do abatimento invalido.";
				case "ID": return "Valor do desconto invalido.";
				case "IE": return "Valor da mora invalido.";
				case "IF": return "Valor da multa invalido.";
				case "IG": return "Valor da deducao invalido.";
				case "IH": return "Valor do acrescimo invalido.";
				case "II": return "Data de vencimento invalida.";
				case "IJ": return "Competencia, periodo referencia ou parcela invalida.";
				case "IK": return "Tributo nao liquidavel via SISPAG ou nao conveniado com Itau.";
				case "IL": return "Codigo de pagamento, empresa ou receita invalido.";
				case "IM": return "Tipo de pagamento nao compativel com forma de pagamento.";
				case "IN": return "Banco ou agencia nao cadastrados.";
				case "IO": return "DAC, valor, competencia ou identificador do lacre invalido.";
				case "IP": return "DAC do codigo de barras invalido.";
				case "IQ": return "Divida ativa ou numero de etiqueta invalido.";
				case "IR": return "Pagamento alterado.";
				case "IS": return "Concessionaria nao conveniada com Itau.";
				case "IT": return "Valor do tributo invalido.";
				case "IU": return "Valor da receita bruta acumulada invalido.";
				case "IV": return "Numero do documento origem ou referencia invalido.";
				case "IX": return "Codigo do produto invalido.";
				case "LA": return "Data de pagamento de um lote alterada.";
				case "LC": return "Lote de pagamento cancelado.";
				case "NA": return "Pagamento cancelado por falta de autorizacao.";
				case "NB": return "Identificacao do tributo invalida.";
				case "NC": return "Exercicio (ano base) invalido.";
				case "ND": return "Codigo RENAVAM nao encontrado ou invalido.";
				case "NE": return "UF invalida.";
				case "NF": return "Codigo do municipio invalido.";
				case "NG": return "Placa invalida.";
				case "NH": return "Opcao ou parcela de pagamento invalida.";
				case "NI": return "Tributo ja foi pago ou esta vencido.";
				case "NR": return "Operacao nao realizada";
				case "PD": return "Aquisicao confirmada. (equivale a ocorrencia 02 no layout de risco sacado)";
				case "RJ": return "Registro rejeitado.";
				case "SS": return "Pagamento cancelado por insuficiencia de saldo ou limite diario de pagamento.";
				case "TA": return "Lote nao aceito - totais do lote com diferenca.";
				case "TI": return "Titularidade invalida.";
				case "X1": return "Forma incompativel com layout 010.";
				case "X2": return "Numero da nota fiscal invalido.";
				case "X3": return "Indentificador de NF/CNPJ invalido.";
				case "X4": return "Forma 32 invalida.";
				default : return "Ocorrencia: ".$codigo;
			}
		}
	}
}
?>