<?php

require_once("websac/require_file.php");
require_file("class/interfacebancaria.class.php");

class BancoBrasil extends InterfaceBancaria{
	const PAGAMENTO = "P";
	const RECEBIMENTO = "R";

	protected $arr_banco; // Array com os bancos dos lancamentos
	protected $arr_parceiro; // Array com os tipo de parceiros dos lancamentos, de acordo com a view v_parceiro
	protected $arr_remessa = array();
	private $numcheque;
	private $refatorado = TRUE;

	// Carrega os cadastros auxiliares para gerar os arquivos
	protected function carregar_auxiliares(){
		$arr_codbanco = array();
		$arr_codparceiro = array();
		foreach($this->arr_lancamento as $lancamento){
			if(strlen($lancamento->getcodbanco()) > 0){
				$arr_codbanco[] = $lancamento->getcodbanco();
			}
			$arr_codparceiro[] = array($lancamento->gettipoparceiro(), $lancamento->getcodparceiro());
		}
		$this->arr_banco = object_array_key(objectbytable("banco", NULL, $this->con), $arr_codbanco);
		$this->arr_parceiro = object_array_key(objectbytable("vparceiro", NULL, $this->con), $arr_codparceiro);
	}

	// Retorna a forma de lancamento para geracao de remessa
	private function forma_lancamento(Lancamento $lancamento){
		if($lancamento->getpagrec() == self::PAGAMENTO){
			switch($lancamento->getcodtipoaceite()){
				case 1:
				case 2:
					if(substr($lancamento->getcodbarras(), 0, 3) == "001"){
						return "30";
					}else{
						return "31";
					}
					break;
				case 3: return "13";
					break;
				case 4: return "16";
					break;
				case 5: return "18";
					break;
				case 6: return "21";
					break;
				case 7: return "22";
					break;
				case 8: return "17";
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
		}else{
			return "50";
		}
	}

	public function gerar_remessa_pagamento(){
		if(!parent::gerar_remessa()){
			return FALSE;
		}

		$this->banco->setseqremessa($this->banco->getseqremessa() + 1);
		if(!$this->banco->save()){
			return FALSE;
		}

		// Array que contem todas as linhas do arquivo
		$arr_linha = array();
		if($this->refatorado){

			// Array que contem todas as informações para o relatorio
			// $arr_remessa = array();
			// Carrega os cadastros auxiliares
			$arr_lancamento = $this->carregarauxiliares();

			$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);

			/*
			$qtde_lotes = 0;
			$arr_header[] = $this->gerar_header_arquivo(str_repeat(" ", 20), "084");
			$arr_detalhe = $this->gerar_detalhe_arquivo($arr_lancamento, str_repeat(" ", 20), "030", $qtde_lotes);
			$arr_trailer = $this->gerar_trailer_arquivo($qtde_lotes, sizeof($arr_detalhe) + 1);
			$arr_linha = array_merge($arr_header, $arr_detalhe, $arr_trailer);
			 *
			 */
			$arr_linha[] = $this->gerar_header_arquivo_240($seqremessa,"084", "P");

			$codigolote = 0; // Codigo do lote (inicia do zero, pois cada lote incrementa um antes da geracao)
			foreach($arr_lancamento as $tipo_pagamento => $arr_lancamento_2){
				foreach($arr_lancamento_2 as $forma_pagamento => $arr_lancamento_3){
					$codigolote++;
					$lancamento = $arr_lancamento_3[0]; // Pega o primeiro lancamento para decidir qual lote deve gerar
					$arr_linha[] = $this->gerar_header_lote_240($cidade, $codigolote, $tipo_pagamento, $forma_pagamento,"030", $lancamento->getpagrec());

					$total_valorliquido = 0;

					if(in_array($lancamento->getcodtipoaceite(), array(1, 2))){

						// Percorre os lancamentos para criar o detalhe do lote
						foreach($arr_lancamento_3 as $i => $lancamento){ //Boletos
							$parceiro = objectbytable("v_parceiro", array($lancamento->gettipoparceiro(), $lancamento->getcodparceiro()), $this->con);
							$arr_linha[] = $this->gerar_segmento_J_240($lancamento, $codigolote, $i);

							$arr_linha[] = $this->gerar_segmento_J52_240($parceiro, $lancamento, $codigolote, $i);
							$total_valorliquido += $lancamento->getvalorliquido();
						}
						$arr_linha[] = $this->gerar_trailer_lote_240($codigolote, (sizeof($arr_lancamento_3) * 2), $total_valorliquido);

					}elseif($lancamento->getcodtipoaceite() == 3 || strlen($lancamento->getcodbarras()) > 0){ //Concessionarias
						// Percorre os lancamentos para criar o detalhe do lote
						foreach($arr_lancamento_3 as $i => $lancamento){
							$arr_linha[] = $this->gerar_segmento_O_240($lancamento, $codigolote, $i);
							$total_valorliquido += $lancamento->getvalorliquido();
						}
						$arr_linha[] = $this->gerar_trailer_lote_240($codigolote, sizeof($arr_lancamento_3), $total_valorliquido);
					}else{

						// Percorre os lancamentos para criar o detalhe do lote
						foreach($arr_lancamento_3 as $i => $lancamento){ // Tributos sem codigos de barras
							$arr_linha[] = $this->gerar_segmento_N_240($cidade, $lancamento, $codigolote, $i);
							$total_valorliquido += $lancamento->getvalorliquido();
						}
						$arr_linha[] = $this->gerar_trailer_lote_240($this->banco->getcodoficial(), $codigolote, $totalregistroslote, $total_valorliquido);
					}
				}
			}
			$arr_linha[] = $this->gerar_trailer_arquivo_240($codigolote, sizeof($arr_linha));

			$procfina = $this->atualiza_lancamentos_gerados();

			// Gera relatorio de remessa na pasta temp
			$controleprocfinan = is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";
			$paramarqremessa = array("numremessa" => $controleprocfinan, "banco" => $this->banco->getcodoficial(), "nomebanco" => str_replace(" ", "", $this->banco->getnome()));

			parent::relatorio_remessa($this->arr_remessa, $paramarqremessa);
			echo write_file($this->estabelecimento->getdirremessabanco().$this->formatar_inteiro($controleprocfinan, 6).".".$this->banco->getcodoficial(), $arr_linha, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"), "w+", true);

			return TRUE;
		}else{
			// Separa os lancamentos para criacao dos lotes
			$arr_lancamento = array();
			foreach($this->arr_lancamento as $lancamento){
				// Montando arquivo de relatóorio
				$this->arr_remessa[] = array(
					"chave" => $lancamento->getcodlancto(),
					"favorecido" => $lancamento->getfavorecido(),
					"dtemissao" => $lancamento->getdtemissao(),
					"dtvencto" => $lancamento->getdtvencto(),
					"valor" => $lancamento->getvalorparcela(),
					"numnotafis" => $lancamento->getnumnotafis(),
					"numcheque" => $this->numcheque
				);
				$arr_lancamento[$this->tipo_servico($lancamento)][$this->forma_lancamento($lancamento)][] = $lancamento;
			}

			$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);

			/* * ***********************************************
			  R E G I S T R O   H E A D E R   D O   A R Q U I V O
			 * *********************************************** */

			$linha = "001"; // Codigo do banco na compensacao
			$linha .= "0000"; // Codigo do lote
			$linha .= "0"; // Tipo do registro
			$linha .= str_repeat(" ", 9); // Complemento de registro
			$linha .= "2"; // Tipo da inscricao da empresa
			$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), "14"); // Numero da inscricao(CPF/CNPJ) da empresa
			$linha .= str_repeat(" ", 20); // Complemento de registro
			$linha .= $this->formatar_inteiro($this->banco->getagencia(), 5); // Numero da agencia
			$linha .= $this->formatar_texto($this->banco->getdigitoagencia(), 1); // Digito verificador da agencia
			$linha .= $this->formatar_inteiro($this->banco->getconta(), 12); // Numero da conta
			$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 0, 1), 1); // Digito verificador da conta
			$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 1, 1), 1); // Digito verificador da agencia/conta
			$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome da empresa
			$linha .= $this->formatar_texto($this->banco->getnome(), 30); // Nome do banco
			$linha .= str_repeat(" ", 10); // Complemento de registro
			$linha .= "1"; // Codigo do arquivo (1-remessa 2-retorno)
			$linha .= date("dmY"); // Data de geracao do arquivo
			$linha .= date("His"); // Hora da geracao do arquivo
			$linha .= $this->formatar_inteiro($this->banco->getseqremessa(), 6); // Numero sequencial do arquivo
			$linha .= "084"; // Numero da versao do layout do arquivo
			$linha .= $this->formatar_inteiro(0, 5); // Densidade de gravcao do arquivo
			$linha .= str_repeat(" ", 20); // Para uso reservado do banco
			$linha .= str_repeat(" ", 20); // Para uso reservado da empresa
			$linha .= str_repeat(" ", 29); // Complemento de registro
			$arr_linha[] = $linha;


			$codigolote = 0; // Codigo do lote (inicia do zero, pois cada lote incrementa um antes da geracao)
			foreach($arr_lancamento as $tipo_servico => $arr_lancamento_2){
				foreach($arr_lancamento_2 as $forma_lancamento => $arr_lancamento_3){
					$codigolote++;
					$lancamento = $arr_lancamento_3[0]; // Pega o primeiro lancamento para decidir qual lote deve gerar

					/*				 * *****************************************
					  R E G I S T R O   H E A D E R   D E   L O T E
					 * ***************************************** */
					$linha = "001"; // Codigo do banco na compensacao
					$linha .= $this->formatar_inteiro($codigolote, 4); // Lote de identificacao de pagamentos
					$linha .= "1"; // Tipo do registro header do lote
					$linha .= "C"; // Tipo da operacao (C = credito)
					$linha .= $this->formatar_inteiro($tipo_servico, 2); // Tipo de servico
					$linha .= $this->formatar_inteiro($forma_lancamento, 2); // Forma de lancamento
					$linha .= "030"; // Versao do layout do lote
					$linha .= " "; // Complemento de registro
					$linha .= "2"; // Tipo da inscricao da empresa (1 = CPF; 2 = CNPJ)
					$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), "14"); // Numero da inscricao (CPF/CNPJ) da empresa
					$linha .= str_repeat(" ", 20); // Codigo do convenio no banco
					$linha .= $this->formatar_inteiro($this->banco->getagencia(), 5); // Numero da agencia debitada
					$linha .= $this->formatar_texto($this->banco->getdigitoagencia(), 1); // Digito da agencia debitada
					$linha .= $this->formatar_inteiro($this->banco->getconta(), 12); // Numero da conta debitada
					$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 0, 1), 1); // Digito da conta debitada
					$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 1, 1), 1); // Digito da agencia/conta debitada
					$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome da empresa debitada
					$linha .= str_repeat(" ", 40); // Mensagem
					$linha .= $this->formatar_texto($this->estabelecimento->getendereco(), 30); // Endereco da empresa
					$linha .= $this->formatar_inteiro($this->estabelecimento->getnumero(), 5); // Numero do endereco da empresa
					$linha .= $this->formatar_texto($this->estabelecimento->getcomplemento(), 15); // Complemento do endereco da empresa
					$linha .= $this->formatar_texto($cidade->getnome(), 20); // Nome da cidade da empresa
					$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcep()), 8); // CEP da empresa
					$linha .= $cidade->getuf(); // UF da empresa
					$linha .= str_repeat(" ", 8); // Complemento de registro
					$linha .= str_repeat(" ", 10); // Ocorrencias (apenas para arquivo de retorno)
					$arr_linha[] = $linha;

					$total_valorliquido = 0;

					if(in_array($lancamento->getcodtipoaceite(), array(1, 2))){

						// Percorre os lancamentos para criar o detalhe do lote
						foreach($arr_lancamento_3 as $i => $lancamento){
							/*						 * *************************************************************
							  R E G I S T R O   D E T A L H E   D E   L O T E   ( B O L E T O )
							 * ************************************************************* */
							$linha = "001"; // Codigo do banco na compensacao
							$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
							$linha .= "3"; // Tipo de registro
							$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
							$linha .= "J"; // Codigo do segmento do registro detalhe
							$linha .= "0"; // Tipo de movimento (0 = inclusao)
							$linha .= "00"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
							$codbarras_temp = $lancamento->getcodbarras();
							$codbarras = substr($codbarras_temp, 0, 4); // Codigo do banco + codigo moeda
							$codbarras .= substr($codbarras_temp, 32, 1); // Codigo do digito verificador do codigo de barras
							$codbarras .= substr($codbarras_temp, 33, 4); // Fator de vencimanto
							$codbarras .= substr($codbarras_temp, 37, 10); // Valor
							$codbarras .= substr($codbarras_temp, 4, 5); // Campo livre
							$codbarras .= substr($codbarras_temp, 10, 10); // Campo livre
							$codbarras .= substr($codbarras_temp, 21, 10); // Campo livre
							$linha .= $this->formatar_inteiro($codbarras, 44); // Codigo de barras
							$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome do cedente
							$linha .= convert_date($lancamento->getdtvencto(),"Y-m-d","dmY"); // Data do vencimento
							$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do titulo
							$linha .= $this->formatar_decimal($lancamento->getvalordescto(), 15, 2); // Valor do desconto/abatimento
							$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da mora/multa
							$linha .= convert_date($lancamento->getdtvencto(),"Y-m-d","dmY"); // Data do pagamento
							$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do pagamento
							$linha .= $this->formatar_inteiro(0, 15); // Quantidade da moeda
							$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Numero do documento atribuido pela empresa
							$linha .= $this->formatar_texto($lancamento->getnossonumero(), 20); // Numero do documento atribuido pelo banco
							$linha .= "09"; // Codigo da moeda
							$linha .= $this->formatar_texto("", 6); // Complemento de registro
							$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
							$arr_linha[] = $linha;

							$total_valorliquido += $lancamento->getvalorliquido();
						}

						/*					 * **************************************************************
						  R E G I S T R O   T R A I L E R   D E   L O T E    ( B O L E T O )
						 * ************************************************************** */
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "5"; // Tipo do registro trailer do lote
						$linha .= str_repeat(" ", 9); // Complemento de registro
						$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3) + 2), 6); // Total de registros no lote
						$linha .= $this->formatar_decimal($total_valorliquido, 18, 2); // Total do valor do lote
						$linha .= $this->formatar_inteiro(0, 18); // Somatoria de quantidade de moedas
						$linha .= $this->formatar_inteiro(0, 6); // Numero aviso debito
						$linha .= $this->formatar_texto("", 165); // Complemento de registro
						$linha .= $this->formatar_texto("", 10); // Codigos de ocorrencia (apenas para retorno)
						$arr_linha[] = $linha;
					}elseif($lancamento->getcodtipoaceite() == 3 || strlen($lancamento->getcodbarras()) > 0){
						// Percorre os lancamentos para criar o detalhe do lote
						foreach($arr_lancamento_3 as $i => $lancamento){
							/*						 * ***************************************************************************************************
							  R E G I S T R O   D E T A L H E   D E   L O T E   ( C O N C E S S I O N A R I A   E   T R I B U T O S )
							 * *************************************************************************************************** */
							$linha = "001"; // Codigo do banco na compensacao
							$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
							$linha .= "3"; // Tipo de registro
							$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
							$linha .= "O"; // Codigo do segmento do registro detalhe
							$linha .= "0"; // Tipo de movimento (0 = inclusao)
							$linha .= "00"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
							$codbarras_temp = $lancamento->getcodbarras();
							$codbarras = substr($codbarras_temp, 0, 11); // Bloco 1
							$codbarras .= substr($codbarras_temp, 12, 11); // Bloco 2
							$codbarras .= substr($codbarras_temp, 24, 11); // Bloco 3
							$codbarras .= substr($codbarras_temp, 35, 11); // Bloco 4
							$linha .= $this->formatar_inteiro($codbarras, 44); // Codigo de barras
							$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome da concessionaria ou orgao publico
							$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
							$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
							$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do pagamento
							$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
							$linha .= $this->formatar_texto("", 20); // Nosso numero (apenas para retorno)
							$linha .= $this->formatar_texto("", 68); // Complemento de registro
							$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)

							$arr_linha[] = $linha;

							$total_valorliquido += $lancamento->getvalorliquido();
						}

						/*					 * ****************************************************************************************************
						  R E G I S T R O   T R A I L E R   D E   L O T E    ( C O N C E S S I O N A R I A   E   T R I B U T O S )
						 * **************************************************************************************************** */
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "5"; // Tipo do registro trailer do lote
						$linha .= str_repeat(" ", 9); // Complemento de registro
						$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3) + 2), 6); // Total de registros no lote
						$linha .= $this->formatar_decimal($total_valorliquido, 18, 2); // Total do valor do lote
						$linha .= $this->formatar_inteiro(0, 15); // Total de quantidade de moeda
						$linha .= $this->formatar_texto("", 174); // Complemento de registro
						$linha .= $this->formatar_texto("", 10); // Codigos de ocorrencia (apenas para retorno)
						$arr_linha[] = $linha;
					}else{

						// Percorre os lancamentos para criar o detalhe do lote
						foreach($arr_lancamento_3 as $i => $lancamento){

							/*						 * *************************************************************************************************************
							  R E G I S T R O   D E T A L H E   D E   L O T E   ( T R I B U T O S   S E M   C O D I G O S   D E   B A R R A S )
							 * ************************************************************************************************************* */
							$linha = "001"; // Codigo do banco na compensacao
							$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
							$linha .= "3"; // Tipo de registro
							$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
							$linha .= "N"; // Codigo do segmento do registro detalhe
							$linha .= "0"; // Tipo de movimento (0 = inclusao)
							$linha .= "00"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
							$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
							$linha .= $this->formatar_texto("", 20); // Nosso numero (apenas para retorno)
							$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome do favorecido
							$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do pagamento
							$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do pagamento
							switch($lancamento->getcodtipoaceite()){
								case 8: // Composicao dos dados para pagamento de GPS
									$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
									$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
									$linha .= "17"; // Codigo de identificacao do tributo
									$linha .= $this->formatar_inteiro($lancamento->getmescompetencia().$lancamento->getanocompetencia(), 6); // Mes e ano da competencia
									$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor previsto do pagamento do INSS
									$linha .= $this->formatar_decimal(0, 15, 2); // Valor de outras entidades
									$linha .= $this->formatar_decimal(0, 15, 2); // Atualizacao monetaria
									$linha .= $this->formatar_texto("", 45); // Complemento do registro
									break;
								case 5: // Composicao dos dados para pagamento de DARF simples
									$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
									$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
									$linha .= "03"; // Codigo de identificacao do tributo
									$linha .= $this->formatar_data($lancamento->getdtemissao()); // Periodo de apuracao
									$linha .= $this->formatar_decimal(0, 15, 2); // Valor da receita bruta acumulada
									$linha .= $this->formatar_decimal(0, 5, 2); // Percentual sobre a receita bruta acumulada
									$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor principal
									$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da multa
									$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 15, 2); // Valor do juros/encargos
									$linha .= $this->formatar_texto("", 21); // Complemento do registro
									break;
								case 9: // Composicao dos dados para pagamento de IPVA
								case 12: // Composicao dos dados para pagamento de DPVAT
									$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
									$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
									$linha .= ($lancamento->getcodtipoaceite() == 9 ? "07" : "08"); // Identificacao do tributo (07 = IPVA; 08 = DPVAT)
									$linha .= $this->formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "Y"), 4); // Ano base
									$linha .= $this->formatar_inteiro(0, 9); // Codigo do RENAVAM
									$linha .= $this->formatar_texto($cidade->getuf(), 2); // Unidade da federacao
									$linha .= $this->formatar_inteiro($cidade->getcodoficial(), 5); // Codigo do municipio
									$linha .= $this->formatar_texto("", 7); // Placa do veiculo
									$linha .= ($lancamento->getcodtipoaceite() == 9 ? "2" : "0"); // Opcao de pagamento
									$linha .= $this->formatar_texto("", 68); // Complemento do registro
									break;
								case 4: // Composicao dos dados para pagamento de DARF normal
									$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
									$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
									$linha .= "02"; // Identificacao do tributo (02 = DARF)
									$linha .= $this->formatar_data($lancamento->getdtemissao()); // Periodo de apuracao
									$linha .= $this->formatar_inteiro($lancamento->getreferencia(), 17); // Numero de referencia
									$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor principal
									$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da multa
									$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 15, 2); // Valor do juros/encargos
									$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do vencimento
									$linha .= $this->formatar_texto("", 18); // Complemento do registro
									break;
								case 7: // Composicao dos dados para pagamento de GARE-SP (ICMS/DR/ITCMD)
									$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
									$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
									$linha .= "05"; // Identificacao do tributo (05 = ICMS)
									$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do vencimento
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
									$linha .= $this->formatar_inteiro(0, 13); // Divida ativa / numero etiqueta
									$linha .= $this->formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "mY"), 6); // Mes/ano de referencia
									$linha .= $this->formatar_inteiro($lancamento->getparcela(), 13); // Numero de parcela/notificacao
									$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor da receita
									$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 14, 2); // Valor do juros
									$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
									$linha .= $this->formatar_texto("", 1); // Complemento do registro
									break;
								case 6: // Composicao dos dados para pagamento de DARJ
									$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
									$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
									$linha .= $this->formatar_inteiro(0, 16); // Numero do documento origem
									$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor principal
									$linha .= $this->formatar_decimal(0, 15, 2); // Valor da atualizacao monetaria
									$linha .= $this->formatar_decimal(0, 15, 2); // Valor da mora
									$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da multa
									$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do vencimento
									$linha .= $this->formatar_inteiro($lancamento->getparcela(), 6); // Periodo de referencia ou numero de parcela
									break;
								case 13: // Composicao dos dados para pagamento de FGTS-GRF/GRRF/GRDE
									$linha .= "11"; // Identificacao do tributo (11 = FGTS)
									$linha .= $this->formatar_inteiro(0, 6); // Codigo da receita
									$linha .= "01"; // Tipo de inscricao do contribuinte (01 = CNPJ)
									$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
									$linha .= $this->formatar_inteiro(0, 16); // Identificador do FGTS
									$linha .= $this->formatar_inteiro(0, 9); // Lacre de conectividade social
									$linha .= $this->formatar_inteiro(0, 2); // Digito do lacre de conectividade social
									$linha .= $this->formatar_texto("", 1); // Complemento do registro
									break;
							}
							$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
							$arr_linha[] = $linha;
						}
						/*					 * **************************************************************************************************************
						  R E G I S T R O   T R A I L E R   D E   L O T E    ( T R I B U T O S   S E M   C O D I G O S   D E   B A R R A S )
						 * ************************************************************************************************************** */
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "5"; // Tipo do registro trailer do lote
						$linha .= str_repeat("", 9); // Complemento de registro
						$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3) + 2), 6); // Total de registros no lote
						$linha .= $this->formatar_decimal($total_valorliquido, 18, 2); // Total do valor do lote
						$linha .= $this->formatar_texto("", 189); // Complemento de registro
						$linha .= $this->formatar_texto("", 10); // Codigos de ocorrencia (apenas para retorno)
						$arr_linha[] = $linha;
					}
				}
			}

			/*		 * *************************************************
			  R E G I S T R O   T R A I L L E R   D O   A R Q U I V O
			 * ************************************************* */

			$linha = "001"; // Codigo do banco na compensacao
			$linha .= "9999"; // Codigo do lote
			$linha .= "9"; // Tipo do registro
			$linha .= $this->formatar_texto("", 9); // Complemento de registro
			$linha .= $this->formatar_inteiro($codigolote, 6); // Quantidade de lotes do arquivo
			$linha .= $this->formatar_inteiro((sizeof($arr_linha) + 1), 6); // Quantidade de registros do arquivo
			$linha .= $this->formatar_inteiro(0, 6); // Quantidade de contas para conciliacao *** V E R I F I C A R ***
			$linha .= $this->formatar_texto("", 205); // Complemento de registro
			$arr_linha[] = $linha."\r\n";
		}
		//percorrer os lancamentos gerados para gravar o sequencial da remessa ,
		//codigo,tipo,data do processo de geracao
		foreach($this->arr_lancamento as $lancamento){
			$procfina = $lancamento->getprocessogr();
			if($this->ocorrencia == "N" && !$this->remessa_anterior){
				$lancamento->setseqremessa($this->banco->getseqremessa());
				$lancamento->setprocessogr($this->controleprocfinan->getcodcontrprocfinan());
				$lancamento->setcodocorrencia("GN");
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
				$lancamento->setmotivoocorrencia("Cancelada a Geração de Remessa");
				$lancamento->setdtremessa(NULL);
			}
			if(!$lancamento->save()){
				return FALSE;
			}
		}
		// Gera relatório de remessa na pasta temp
		$controleprocfinan = is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";
		$paramarqremessa = array("numremessa" => $controleprocfinan, "banco" => $this->banco->getcodoficial(), "nomebanco" => $this->banco->getnome());

		parent::relatorio_remessa($this->arr_remessa, $paramarqremessa);
		echo write_file($this->estabelecimento->getdirremessabanco().$this->formatar_inteiro($controleprocfinan, 6).".".$this->banco->getcodoficial(), $arr_linha, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"), "w+", true);

		return TRUE;
	}

	function gerar_remessa_recebimento($modelo){
		if(!parent::gerar_remessa()){
			return FALSE;
		}

		$this->carregar_auxiliares();

		// Separa os lancamentos para criacao dos lotes
		$arr_lancamento = array();
		foreach($this->arr_lancamento as $lancamento){
			// Montando arquivo de relatorio
			$this->arr_remessa[] = array(
				"chave" => $lancamento->getcodlancto(),
				"favorecido" => $lancamento->getfavorecido(),
				"dtemissao" => $lancamento->getdtemissao(),
				"dtvencto" => $lancamento->getdtvencto(),
				"valor" => $lancamento->getvalorparcela(),
				"numnotafis" => $lancamento->getnumnotafis(),
				"numcheque" => $this->numcheque
			);
			$arr_lancamento[$this->tipo_servico($lancamento)][$this->forma_lancamento($lancamento)][] = $lancamento;
		}

		$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);

		$estabelecimento_cpfcnpj = removeformat($this->estabelecimento->getcpfcnpj());
		$estabelecimento_tppessoa = (strlen($estabelecimento_cpfcnpj) === 11 ? "F" : "J");

		$this->con->start_transaction();

		// Incrementa o numero da remessa no banco
		if(!$this->remessa_anterior){
			$this->banco->setseqremessa($this->banco->getseqremessa() + 1);
			if(!$this->banco->save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		$arr_linha = array();

		if($modelo == self::CNAB_240){
			// Numero do convenio
			if($this->refatorado){

				$arr_linha[] = $this->gerar_header_arquivo_240($this->banco->getseqremessa(),"101");

				foreach($arr_lancamento as $tipo_servico => $arr_lancamento_2){
					foreach($arr_lancamento_2 as $forma_lancamento => $arr_lancamento_3){
						$codigolote++;
						$lancamento = $arr_lancamento_3[0]; // Pega o primeiro lancamento para decidir qual lote deve gerar

						$arr_linha[] = $this->gerar_header_lote_240($cidade, $codigolote, $tipo_servico, $forma_lancamento, "060", $lancamento->getpagrec());

						$total_valorliquido = 0;

						$seq_registro = 0;
						foreach($arr_lancamento_3 as $i => $lancamento){
							$seq_registro++;
							$arr_linha[] = $this->gerar_segmento_P_240($lancamento, $seq_registro, $codigolote);
							$seq_registro++;
							$arr_linha[] = $this->gerar_segmento_Q_240($lancamento, $seq_registro, $codigolote);

							$total_valorliquido += $lancamento->getvalorliquido();

							$nrprocesso = (is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : NULL);
							if($this->ocorrencia == "N" && !$this->remessa_anterior){
								$lancamento->setseqremessa($this->banco->getseqremessa());
								$lancamento->setprocessogr($nrprocesso);
								$lancamento->setcodocorrencia("00");
								$lancamento->setocorrencia(utf8_encode($this->ocorrencia_recebimento("00")));
								$lancamento->setmotivoocorrencia("Aguardando Retorno");
								$lancamento->setdtremessa(date("Y-m-d"));
								$lancamento->setcodbanco($this->banco->getcodbanco());
							}elseif($this->ocorrencia == "C" || $this->ocorrencia == "G"){
								$lancamento->setseqremessa(NULL);
								if($this->ocorrencia == "C"){
									$lancamento->setnossonumero(NULL);
								}
								$lancamento->setprocessogr(NULL);
								if($this->ocorrencia == "C"){
									$lancamento->setcodocorrencia("01");
									$lancamento->setocorrencia(utf8_encode($this->ocorrencia_recebimento("01")));
									$lancamento->setmotivoocorrencia("Geracao de remessa para cancelamento no banco");
								}else{
									$lancamento->setcodocorrencia(NULL);
									$lancamento->setocorrencia(NULL);
									$lancamento->setmotivoocorrencia("Cancelada geração da remessa");
								}
								$lancamento->setdtremessa(NULL);
							}
							if(!$lancamento->save()){
								return FALSE;
							}
							if(!$this->banco->save()){
								return FALSE;
							}
						}
						$arr_linha[] = $this->gerar_trailer_lote_240($codigolote, (sizeof($arr_lancamento_3) * 2), $total_valorliquido, sizeof($arr_lancamento_3), "R");
					}
				}
				$arr_linha[] = $this->gerar_trailer_arquivo_240($codigolote, sizeof($arr_linha));
			}else{
				$convenio  = str_pad(trim($this->banco->getcodigoempresa()), 9, "0", STR_PAD_LEFT);
				$convenio .= str_pad(trim($this->banco->getcodigocedente()), 4, "0", STR_PAD_LEFT);
				$convenio .= str_pad(trim($this->banco->getcarteira()), 2, "0", STR_PAD_LEFT);
				$convenio .= str_pad(trim($this->banco->getvarcarteira()), 3, "0", STR_PAD_LEFT);
				$convenio  = str_pad($convenio, 20, " ", STR_PAD_RIGHT);
				$qtde_lotes = 0;
				$arr_header[] = $this->gerar_header_arquivo($convenio, "101");
				$arr_detelahe = $this->gerar_detalhe_arquivo($arr_lancamento, $convenio, "060", $qtde_lotes);
				$arr_trailer = $this->gerar_trailer_arquivo($qtde_lotes, sizeof($arr_detelahe) + 1);
				$arr_linha = array_merge($arr_header, $arr_detelahe, $arr_trailer);
			}
		}else{
			$convenio = $this->banco->getcodigoempresa();
			// Sequencial de registro
			$sequencial = 1;

			// Array contendo todas as linha do arquivo de remessa

			// Cria a linha HEADER
			$linha = $this->formatar_inteiro(0, 1); // Identificacao do registro header: "0"
			$linha .= $this->formatar_inteiro(1, 1); // Tipo de operacao: "1"
			$linha .= $this->formatar_texto("REMESSA", 7); // Identificacao por extenso do tipo de operacao: "TESTE" ou "REMESSA"
			$linha .= $this->formatar_inteiro(1, 2); // Identificacao do tipo de servico: "01"
			$linha .= $this->formatar_texto("COBRANCA", 8); // Identificacao por extenso do tipo de servico: "COBRANCA"
			$linha .= $this->formatar_texto("", 7); // Em branco
			$linha .= $this->formatar_inteiro($this->banco->getagencia(), 4); // Numero da agencia onde esta cadastrado o convenio lider do cedente
			$linha .= $this->formatar_texto($this->banco->getdigitoagencia(), 1); // Digito verificador da agencia
			$linha .= $this->formatar_inteiro($this->banco->getconta(), 8); // Numero da conta corrente onde esta cadastrado o convenio lider do cedente
			$linha .= $this->formatar_texto($this->banco->getdigito(), 1); // Digito verificador da conta
			$linha .= $this->formatar_inteiro(0, 6); // Em branco
			$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do cedente
			$linha .= $this->formatar_texto("001BANCODOBRASIL", 18); // Texto fixo: "001BANCODOBRASIL"
			$linha .= $this->formatar_data(date("Y-m-d"), "R"); // Data da gravacao no formato "DDMMAA"
			$linha .= $this->formatar_inteiro($this->banco->getseqremessa(), 7); // Sequencial da remessa
			$linha .= $this->formatar_texto("", 22); // Em branco
			$linha .= $this->formatar_inteiro($convenio, 7); // Numero do convenio lider
			$linha .= $this->formatar_texto("", 258); // Em branco
			$linha .= $this->formatar_inteiro($sequencial++, 6); // Sequencial do registro
			$arr_linha[] = $linha;

			// Cria as linhas DETALHES
			foreach($this->arr_lancamento as $lancamento){
				$parceiro = $this->arr_parceiro[$lancamento->gettipoparceiro().";".$lancamento->getcodparceiro()];
				$parceiro_cpfcnpj = removeformat($parceiro->getcpfcnpj());
				$parceiro_tppessoa = (strlen($parceiro_cpfcnpj) === 11 ? "F" : "J");

				$cidade = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);

				if($this->banco->getbancoemiteboleto() == "N"){
					if(strlen($lancamento->getnossonumero()) > 0){
						$nossonumero = $lancamento->getnossonumero();
					}else{
						$this->banco->setnossonumero($this->banco->getnossonumero() + 1);
						$nossonumero = $this->banco->getnossonumero();
					}
					$nossonumero = $convenio.str_pad($nossonumero, (17 - strlen($convenio)), "0", STR_PAD_LEFT);
				}else{
					$nossonumero = NULL;
				}

				$linha =  $this->formatar_inteiro(7, 1); // Indentificacao do registro detalhe: "7"
				$linha .= $this->formatar_inteiro(($estabelecimento_tppessoa === "F" ? "01" : "02"), 2); // Tipo de inscricao do cedente: "01" (CPF), "02" (CNPJ)
				$linha .= $this->formatar_inteiro($estabelecimento_cpfcnpj, 14); // Numero do CPF/CNPJ do cedente
				$linha .= $this->formatar_inteiro($this->banco->getagencia(), 4); // Numero da agencia do cedente
				$linha .= $this->formatar_texto($this->banco->getdigitoagencia(), 1); // Digito verificador da agencia
				$linha .= $this->formatar_inteiro($this->banco->getconta(), 8); // Numero da conta corrente do cedente
				$linha .= $this->formatar_texto($this->banco->getdigito(), 1); // Digito verificador da conta
				$linha .= $this->formatar_inteiro($convenio, 7); // Numero do convenio de cobranca do cedente
				$linha .= $this->formatar_texto($lancamento->getcodlancto(), 25); // Codigo de controle da empresa
				$linha .= $this->formatar_inteiro($nossonumero, 17); // Nosso numero
				$linha .= $this->formatar_inteiro(0, 2); // Numero da prestacao: "00"
				$linha .= $this->formatar_inteiro(0, 2); // Grupo de valor: "00"
				$linha .= $this->formatar_texto("", 3); // Em branco
				$linha .= $this->formatar_texto("", 1); // Indicativo de mensagem ou sacador/avalista
				$linha .= $this->formatar_texto("", 3); // Prefixo do titulo: "Brancos"
				$linha .= $this->formatar_inteiro($this->banco->getvarcarteira(), 3); // Variacao da carteira
				$linha .= $this->formatar_inteiro(0, 1); // Conta caucao: "0"
				$linha .= $this->formatar_inteiro(0, 6); // Numero de bordero: "000000"
				$linha .= $this->formatar_texto("", 5); // Tipo de cobranca
				$linha .= $this->formatar_inteiro($this->banco->getcarteira(), 2); // Carteira de cobranca
				$linha .= $this->formatar_inteiro(1, 2); // Comando
				$linha .= $this->formatar_texto($lancamento->getcodlancto(), 10); // Seu numero (atribuido pelo cedente)
				$linha .= $this->formatar_data($lancamento->getdtvencto(), "R"); // Data de vencimento
				$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 13, 2); // Valor do titulo
				$linha .= $this->formatar_inteiro(1, 3); // Numero do banco: "001"
				$linha .= $this->formatar_inteiro(0, 4); // Prefixo da agencia cobradora: "0000"
				$linha .= $this->formatar_texto("", 1); // Digito verificador do prefixo da agencia cobradora
				$linha .= $this->formatar_inteiro(1, 2); // Especie de titulo: "01" (Duplicata mercantil)
				$linha .= $this->formatar_texto((strlen($this->banco->getaceitetitulo()) > 0 ? $this->banco->getaceitetitulo() : "N"), 1); // Aceita de titulo ("N": sem aceite, "A": com aceite)
				$linha .= $this->formatar_data($lancamento->getdtemissao(), "R"); // Data de emissao
				if($this->banco->getdiasprotesto() > 0){
					$linha .= $this->formatar_inteiro($this->banco->getdiasprotesto(), 2); // Instrucao codificada
				}else{
					$linha .= $this->formatar_inteiro(0, 2); // Instrucao codificada
				}
				$linha .= $this->formatar_inteiro(0, 2); // Instrucao codificada
				$linha .= $this->formatar_decimal(($lancamento->getvalorliquido() * $this->banco->getvalormoradiaria() / 30), 13, 2); // Valor de juro de mora diaria
				$linha .= $this->formatar_inteiro(0, 6); // Data limite para concessao de desconto
				$linha .= $this->formatar_inteiro(0, 13); // Valor do desconto
				$linha .= $this->formatar_inteiro(0, 13); // Valor do IOF
				$linha .= $this->formatar_inteiro(0, 13); // Valor do abatimento
				$linha .= $this->formatar_inteiro(($parceiro_tppessoa === "F" ? "01" : "02"), 2); // Tipo de inscricao do sacado: "01" (CPF), "02" (CNPJ)
				$linha .= $this->formatar_inteiro($parceiro_cpfcnpj, 14); // Numero do CPF/CNPJ do sacado
				$linha .= $this->formatar_texto(removespecial($parceiro->getnome()), 37); // Nome do sacado
				$linha .= $this->formatar_texto("", 3); // Em branco
				$linha .= $this->formatar_texto($parceiro->getendereco(), 40); // Endereco do sacado
				$linha .= $this->formatar_texto($parceiro->getbairro(), 12); // Bairro do sacado
				$linha .= $this->formatar_inteiro(removeformat($parceiro->getcep()), 8); // CEP do sacado
				$linha .= $this->formatar_texto($cidade->getnome(), 15); // Cidade do sacado
				$linha .= $this->formatar_texto($cidade->getuf(), 2); // Estado do sacado
				$linha .= $this->formatar_texto("", 40);
				$linha .= $this->formatar_texto("", 2); // Dias para protesto
				$linha .= $this->formatar_texto("", 1); // Em branco
				$linha .= $this->formatar_inteiro($sequencial++, 6); // Sequencial do registro

				$arr_linha[] = $linha;

				$nrprocesso = (is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : NULL);
				if($this->ocorrencia == "N" && !$this->remessa_anterior){
					$lancamento->setseqremessa($this->banco->getseqremessa());
					$lancamento->setprocessogr($nrprocesso);
					$lancamento->setcodocorrencia("00");
					$lancamento->setocorrencia(utf8_encode($this->ocorrencia_recebimento("00")));
					$lancamento->setmotivoocorrencia("Aguardando Retorno");
					$lancamento->setdtremessa(date("Y-m-d"));
					$lancamento->setcodbanco($this->banco->getcodbanco());
					if($this->banco->getbancoemiteboleto() == "N"){
						$lancamento->setnossonumero($nosso_numero.$dac_nosso_numero);
					}
				}elseif($this->ocorrencia == "C" || $this->ocorrencia == "G"){
					$lancamento->setseqremessa(NULL);
					if($this->ocorrencia == "C"){
						$lancamento->setnossonumero(NULL);
					}
					$lancamento->setprocessogr(NULL);
					if($this->ocorrencia == "C"){
						$lancamento->setcodocorrencia("01");
						$lancamento->setocorrencia(utf8_encode($this->ocorrencia_recebimento("01")));
						$lancamento->setmotivoocorrencia("Geracao de remessa para cancelamento no banco");
					}else{
						$lancamento->setcodocorrencia(NULL);
						$lancamento->setocorrencia(NULL);
						$lancamento->setmotivoocorrencia("Cancelada geração da remessa");
					}
					$lancamento->setdtremessa(NULL);
				}
				if(!$lancamento->save()){
					return FALSE;
				}
			}

			// Cria a linha TRAILLER
			$linha = $this->formatar_inteiro(9, 1); // Identificacao do registro trailler: "9"
			$linha .= $this->formatar_texto("", 393); // Em branco
			$linha .= $this->formatar_inteiro($sequencial++, 6); // Sequencial do registro
			$arr_linha[] = $linha;

			// Grava as alteracoes no banco que foram feitas durante a geracao
			if(!$this->banco->save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		$controleprocfinan = is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";

		$paramarqremessa = array("numremessa" => $this->controleprocfinan->getcodcontrprocfinan(), "banco" => $this->banco->getcodoficial(), "nomebanco" => str_replace(" ", "", $this->banco->getnome()));
		parent::relatorio_remessa($this->arr_remessa, $paramarqremessa);

		echo write_file($this->estabelecimento->getdirremessabanco()."CB".date("dm").$this->banco->getnrultremessa().".REM", $arr_linha, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"), "w+", true);

		$this->con->commit();
		return TRUE;
	}

	function processar_retorno_recebimento($arr_arquivo, $modelo){
		if(!is_array($arr_arquivo)){
            $arr_arquivo = array($arr_arquivo);
        }

		$arr_retorno = array();

		foreach($arr_arquivo as $i => $arquivo){
            setprogress((($i + 1) / sizeof($arr_arquivo) * 100),"Carregando arquivos: ".($i + 1)." de ".sizeof($arr_arquivo));
			$arr_linha = file($arquivo);
			if($modelo == self::CNAB_240){

				foreach($arr_linha as $linha){
					$linha = trim($linha);

					// Verifica se e linha detalhe
					if(substr($linha, 7, 1) == "3"){
						$update = FALSE;
						switch(substr($linha, 13, 1)){
							case "T": // Liquidacao de titulos (boletos)
								//$codlancto = (int) substr($linha, 58, 15);
								$codlancto =  trim(substr($linha, 105, 25));
								$codocorrencia = substr($linha, 15, 2);
								$nossonumero = substr($linha,44, 10);
								$dtvencto = convert_date(substr($linha, 73, 8), "dmY","Y-m-d");
								$ocorrencias = substr($linha, 213, 10);
								$motivoocorrencia = substr($linha, 213, 10);
								$valortitulo = substr($linha, 81, 15);
								$valortitulo = $valortitulo / 100;
								break;
							case "U": // Pagamento de contas de concessionaria e tributos com codigo de barras
								$valorparcela = substr($linha, 92, 15) / 100;
								$dtliquid = substr($linha, 137, 8);
								if(strlen(trim($dtliquid)) > 0){
									$dtliquid = convert_date($dtliquid,"dmY","Y-m-d");
								}else{
									$dtliquid = NULL;
								}
								$dtocorrencia = substr($linha, 137, 8);
								if(strlen(trim($dtocorrencia)) > 0){
									$dtocorrencia = convert_date($dtocorrencia,"dmY","Y-m-d");
								}else{
									$dtocorrencia = NULL;
								}
								$valorjuros = substr($linha, 17, 15) / 100;
								$valorpago = substr($linha, 77, 15) / 100;
								$update = TRUE;
								break;

						}
						if($update){
							$liquidado = in_array($codocorrencia,array("06"));
							if($liquidado && $valorpago !== ((float)$valorjuros) + (float)$valortitulo){
								$valorjuros = ((float)$valorpago - (float)$valortitulo);
							}
							// Captura as ocorrencias do lancamento
							$arr_ocorrencia = array();
							for($j = 0; $j < 10; ($j = $j + 2)){
								$ocorrencia = trim(substr($ocorrencias, $j, 2));
								if(strlen($ocorrencia) > 0){
									$arr_ocorrencia[] = $ocorrencia.":".$this->ocorrencia_C47($ocorrencia);
								}
							}
							if(count($arr_ocorrencia) > 0){
								$motivoocorrencia = implode("\n", $arr_ocorrencia);
							}
							$arr_retorno[] = array(
								"codlancto" => $codlancto,
								"nossonumero" => $nossonumero,
								"liquidado" => $liquidado,
								"valorparcela" => $valorparcela,
								"valorjuros" => $valorjuros,
								"valorpago" => $valorpago,
								"dtvencto" => $dtvencto,
								"dtliquid" => $dtliquid,
								"codocorrencia" => $codocorrencia,
								"ocorrencia" => $codocorrencia.":".$this->ocorrencia_C44($codocorrencia),
								"motivoocorrencia" => $motivoocorrencia,
								"dtocorrencia" => $dtocorrencia
							);
							unset($codlancto);
						}
					}
				}
			}else{
				$linha_header = array_shift($arr_linha);
				array_pop($arr_linha);
				// Verifica se a primeira linha do arquivo e de um arquivo de retorno e se foi gerado pelo banco bradesco
				$retorno = strtoupper(substr($linha_header,2,7));
				$banco = substr($linha_header,76,3);
				$dtliquid = convert_date(substr($linha_header,94,6),"dmy","Y-m-d");
				if($retorno != "RETORNO" || $banco != "001"){
					$_SESSION["ERROR"] = "O arquivo ".$arquivo." n&atilde;o &eacute; um arquivo de retorno v&aacute;lido para o Banco do Brasil.";
					return FALSE;
				}

				foreach($arr_linha as $linha){
					$registro = substr($linha, 0, 1);
					switch ($registro){
						case "7":
							$codlancto = trim(ltrim(substr($linha,37,25),"0"));
							$nossonumero = substr($linha,69,11);
							$codocorrencia = substr($linha,108,2);
							$dtocorrencia = convert_date(substr($linha,110,6),"dmy","Y-m-d");
							$dtvencto = convert_date(substr($linha,146,6),"dmy","Y-m-d");
							$valorparcela = substr($linha,152,13) / 100;
							$valorpago = substr($linha,253,13) / 100;
							$valorjuros = substr($linha,266,13) / 100;
							$naturezarec = substr($linha, 86, 2);
							$ocorrencia = $this->ocorrencia_recebimento($codocorrencia);
							$motivoocorrencia = $this->tipocobranca($naturezarec, $codocorrencia);

							$arr_retorno[] = array(
								"codlancto" => $codlancto,
								"nossonumero" => $nossonumero,
								"liquidado" => in_array($codocorrencia,array("05","06","07","08","15")),
								"valorparcela" => $valorparcela,
								"valorjuros" => $valorjuros,
								"valorpago" => $valorpago,
								"dtvencto" => $dtvencto,
								"dtliquid" => $dtliquid,
								"codocorrencia" => $codocorrencia,
								"ocorrencia" => $ocorrencia,
								"motivoocorrencia" => $motivoocorrencia,
								"dtocorrencia" => $dtocorrencia
							);
							break;
					}
				}
			}
		}
		return parent::processar_retorno($arr_retorno);
	}

	private function ocorrencia_C44($codigo_movimento){
		switch($codigo_movimento){
			case "02": return "Entrada Confirmada"; break;
			case "03": return "Entrada Rejeitada"; break;
			case "04": return "Transferência de Carteira/Entrada"; break;
			case "05": return "Transferência de Carteira/Baixa"; break;
			case "06": return "Liquidação"; break;
			case "07": return "Confirmação do Recebimento da Instrução de Desconto"; break;
			case "08": return "Confirmação do Recebimento do Cancelamento do Desconto"; break;
			case "09": return "Baixa"; break;
			case "11": return "Títulos em Carteira (Em Ser)"; break;
			case "12": return "Confirmação Recebimento Instrução de Abatimento"; break;
			case "13": return "Confirmação Recebimento Instrução de Cancelamento Abatimento"; break;
			case "14": return "Confirmação Recebimento Instrução Alteração de Vencimento"; break;
			case "15": return "Franco de Pagamento"; break;
			case "17": return "Liquidação Após Baixa ou Liquidação Título Não Registrado"; break;
			case "19": return "Confirmação Recebimento Instrução de Protesto"; break;
			case "20": return "Confirmação Recebimento Instrução de Sustação/Cancelamento de Protesto"; break;
			case "23": return "Remessa a Cartório (Aponte em Cartório)"; break;
			case "24": return "Retirada de Cartório e Manutenção em Carteira"; break;
			case "25": return "Protestado e Baixado (Baixa por Ter Sido Protestado)"; break;
			case "26": return "Instrução Rejeitada"; break;
			case "27": return "Confirmação do Pedido de Alteração de Outros Dados"; break;
			case "28": return "Débito de Tarifas/Custas"; break;
			case "29": return "Ocorrências do Pagador"; break;
			case "30": return  "Alteração de Dados Rejeitada"; break;
			case "33": return "Confirmação da Alteração dos Dados do Rateio de Crédito"; break;
			case "34": return  "Confirmação do Cancelamento dos Dados do Rateio de Crédito"; break;
			case "35": return "Confirmação do Desagendamento do Débito Automático"; break;
			case "36": return "Confirmação de envio de e-mail/SMS"; break;
			case "37": return "Envio de e-mail/SMS rejeitado"; break;
			case "38": return "Confirmação de alteração do Prazo Limite de Recebimento (a data deve ser informada no campo 28.3.p)"; break;
			case "39": return "Confirmação de Dispensa de Prazo Limite de Recebimento"; break;
			case "40": return "Confirmação da alteração do número do título dado pelo Beneficiário"; break;
			case "41": return "Confirmação da alteração do número controle do Participante"; break;
			case "42": return "Confirmação da alteração dos dados do Pagador"; break;
			case "43": return "Confirmação da alteração dos dados do Sacador/Avalista"; break;
			case "44": return "Título pago com cheque devolvido"; break;
			case "45": return "Título pago com cheque compensado"; break;
			case "46": return "Instrução para cancelar protesto confirmada"; break;
			case "47": return "Instrução para protesto para fins falimentares confirmada"; break;
			case "48": return "Confirmação de instrução de transferência de carteira/modalidade de cobrança"; break;
			case "49": return "Alteração de contrato de cobrança"; break;
			case "50": return "Título pago com cheque pendente de liquidação"; break;
			case "51": return "Título DDA reconhecido pelo Pagador"; break;
			case "52": return "Título DDA não reconhecido pelo Pagador"; break;
			case "53": return "Título DDA recusado pela CIP"; break;
			case "54": return "Confirmação da Instrução de Baixa de Título Negativado sem Protesto"; break;
			case "55": return "Confirmação de Pedido de Dispensa de Multa"; break;
			case "56": return "Confirmação do Pedido de Cobrança de Multa"; break;
			case "57": return "Confirmação do Pedido de Alteração de Cobrança de Juros"; break;
			case "58": return "Confirmação do Pedido de Alteração do Valor/Data de Desconto"; break;
			case "59": return "Confirmação do Pedido de Alteração do Beneficiário do Título"; break;
			case "60": return "Confirmação do Pedido de Dispensa de Juros de Mora"; break;
			case "61": return "Confirmação de Alteração do Valor Nominal do Título"; break;
			case "63": return "Título Sustado Judicialmente"; break;
			default: return "Ocorrencia não definida";
		}
	}

	private function ocorrencia_C47($codigo_ocorrencia){
		switch($codigo_ocorrencia){
			case "01": return "Código do Banco Inválido"; break;
			case "02": return "Código do Registro Detalhe Inválido"; break;
			case "03": return "Código do Segmento Inválido"; break;
			case "04": return "Código de Movimento Não Permitido para Carteira"; break;
			case "05": return "Código de Movimento Inválido"; break;
			case "06": return "Tipo/Número de Inscrição do Beneficiário Inválidos"; break;
			case "07": return "Agência/Conta/DV Inválido"; break;
			case "08": return "Nosso Número Inválido"; break;
			case "09": return "Nosso Número Duplicado"; break;
			case "10": return "Carteira Inválida"; break;
			case "11": return "Forma de Cadastramento do Título Inválido"; break;
			case "12": return "Tipo de Documento Inválido"; break;
			case "13": return "Identificação da Emissão do Boleto de Pagamento Inválida"; break;
			case "14": return "Identificação da Distribuição do Boleto de Pagamento Inválida"; break;
			case "15": return "Características da Cobrança Incompatíveis"; break;
			case "16": return "Data de Vencimento Inválida"; break;
			case "17": return "Data de Vencimento Anterior a Data de Emissão"; break;
			case "18": return "Vencimento Fora do Prazo de Operação"; break;
			case "19": return "Título a Cargo de Bancos Correspondentes com Vencimento Inferior a XX Dias"; break;
			case "20": return "Valor do Título Inválido"; break;
			case "21": return "Espécie do Título Inválida"; break;
			case "22": return "Espécie do Título Não Permitida para a Carteira"; break;
			case "23": return "Aceite Inválido"; break;
			case "24": return "Data da Emissão Inválida"; break;
			case "25": return "Data da Emissão Posterior a Data de Entrada"; break;
			case "26": return "Código de Juros de Mora Inválido"; break;
			case "27": return "Valor/Taxa de Juros de Mora Inválido"; break;
			case "28": return "Código do Desconto Inválido"; break;
			case "29": return "Valor do Desconto Maior ou Igual ao Valor do Título"; break;
			case "30": return "Desconto a Conceder Não Confere"; break;
			case "31": return "Concessão de Desconto - Já Existe Desconto Anterior"; break;
			case "32": return "Valor do IOF Inválido"; break;
			case "33": return "Valor do Abatimento Inválido"; break;
			case "34": return "Valor do Abatimento Maior ou Igual ao Valor do Título"; break;
			case "35": return "Valor a Conceder Não Confere"; break;
			case "36": return "Concessão de Abatimento - Já Existe Abatimento Anterior"; break;
			case "37": return "Código para Protesto Inválido"; break;
			case "38": return "Prazo para Protesto Inválido"; break;"; break;"; break;
			case "39": return "Pedido de Protesto Não Permitido para o Título"; break;
			case "40": return "Título com Ordem de Protesto Emitida"; break;
			case "41": return "Pedido de Cancelamento/Sustação para Títulos sem Instrução de Protesto"; break;
			case "42": return "Código para Baixa/Devolução Inválido"; break;
			case "43": return "Prazo para Baixa/Devolução Inválido"; break;
			case "44": return "Código da Moeda Inválido"; break;
			case "45": return "Nome do Pagador Não Informado"; break;
			case "46": return "Tipo/Número de Inscrição do Pagador Inválidos"; break;
			case "47": return "Endereço do Pagador Não Informado"; break;
			case "48": return "CEP Inválido"; break;
			case "49": return "CEP Sem Praça de Cobrança (Não Localizado)"; break;
			case "50": return "CEP Referente a um Banco Correspondente"; break;
			case "51": return "CEP incompatível com a Unidade da Federação"; break;
			case "52": return "Unidade da Federação Inválida"; break;
			case "53": return "Tipo/Número de Inscrição do Sacador/Avalista Inválidos"; break;
			case "54": return "Sacador/Avalista Não Informado"; break;
			case "55": return "Nosso número no Banco Correspondente Não Informado"; break;
			case "56": return "Código do Banco Correspondente Não Informado"; break;
			case "57": return "Código da Multa Inválido"; break;
			case "58": return "Data da Multa Inválida"; break;
			case "59": return "Valor/Percentual da Multa Inválido"; break;
			case "60": return "Movimento para Título Não Cadastrado"; break;
			case "61": return "Alteração da Agência Cobradora/DV Inválida"; break;
			case "62": return "Tipo de Impressão Inválido"; break;
			case "63": return "Entrada para Título já Cadastrado"; break;
			case "64": return "Número da Linha Inválido"; break;
			case "65": return "Código do Banco para Débito Inválido"; break;
			case "66": return "Agência/Conta/DV para Débito Inválido"; break;
			case "67": return "Dados para Débito incompatível com a Identificação da Emissão do Boleto de Pagamento"; break;
			case "68": return "Débito Automático Agendado"; break;
			case "69": return "Débito Não Agendado - Erro nos Dados da Remessa"; break;
			case "70": return "Débito Não Agendado - Pagador Não Consta do Cadastro de Autorizante"; break;
			case "71": return "Débito Não Agendado - Beneficiário Não Autorizado pelo Pagador"; break;
			case "72": return "Débito Não Agendado - Beneficiário Não Participa da Modalidade Débito Automático"; break;
			case "73": return "Débito Não Agendado - Código de Moeda Diferente de Real (R$)"; break;
			case "74": return "Débito Não Agendado - Data Vencimento Inválida"; break;
			case "75": return "Débito Não Agendado, Conforme seu Pedido, Título Não Registrado"; break;
			case "76": return "Débito Não Agendado, Tipo/Num. Inscrição do Debitado, Inválido"; break;
			case "77": return "Transferência para Desconto Não Permitida para a Carteira do Título"; break;
			default: return "Ocorrencia não definida";
		}
	}

	private function ocorrencia_recebimento($codigo_ocorrencia){
		switch($codigo_ocorrencia){
			case "02" : return "Confirmação de Entrada de Título"; break;
			case "03": return "Comando recusado (Motivo indicado na posição 087/088)"; break;
			case "05": return "Liquidado sem registro (carteira 17-tipo4)"; break;
			case "06": return "Liquidação Normal"; break;
			case "07": return "Liquidação por Conta"; break;
			case "08": return "Liquidação por Saldo"; break;
			case "09": return "Baixa de Titulo"; break;
			case "10": return "Baixa Solicitada"; break;
			case "11": return "Títulos em Ser (constara somente do arquivo de existência de cobrança, fornecido mediante solicitação do cliente)"; break;
			case "12": return "Abatimento Concedido"; break;
			case "13": return "Abatimento Cancelado"; break;
			case "14": return "Alteração de Vencimento do título"; break;
			case "15": return "Liquidação em Cartório"; break;
			case "16": return "Confirmação de alteração de juros de mora"; break;
			case "19": return "Confirmação de recebimento de instruções para protesto"; break;
			case "20": return "Debito em Conta"; break;
			case "21": return "Alteração do Nome do Sacado"; break;
			case "22": return "Alteração do Endereço do Sacado"; break;
			case "23": return "Indicação de encaminhamento a cartório"; break;
			case "24": return "Sustar Protesto"; break;
			case "25": return "Dispensar Juros de mora"; break;
			case "26": return "Alteração do número do título dado pelo Cedente (Seu número) – 10 e 15 posições"; break;
			case "28": return "Manutenção de titulo vencido"; break;
			case "31": return "Conceder desconto"; break;
			case "32": return "Não conceder desconto"; break;
			case "33": return "Retificar desconto"; break;
			case "34": return "Alterar data para desconto"; break;
			case "35": return "Cobrar Multa"; break;
			case "36": return "Dispensar Multa"; break;
			case "37": return "Dispensar Indexador"; break;
			case "38": return "Dispensar prazo limite para recebimento"; break;
			case "39": return "Alterar prazo limite para recebimento"; break;
			case "41": return "Alteração do número do controle do participante (25 posições)"; break;
			case "42": return "Alteração do número do documento do sacado (CNPJ/CPF)"; break;
			case "44": return "Título pago com cheque devolvido"; break;
			case "46": return "Título pago com cheque, aguardando compensação"; break;
			case "72": return "lteração de tipo de cobrança (específico para títulos das carteiras 11 e 17)"; break;
			case "96": return "Despesas de Protesto"; break;
			case "97": return "Despesas de Sustação de Protesto"; break;
			case "98": return "Debito de Custas Antecipadas"; break;
			case "78": "Data Inferior ou Igual ao Vencimento para Débito Automático"; break;
			case "79": "Data Juros de Mora Inválido"; break;
			case "80": "Data do Desconto Inválida"; break;
			case "81": "Tentativas de Débito Esgotadas - Baixado"; break;
			case "82": "Tentativas de Débito Esgotadas - Pendente"; break;
			case "83": "Limite Excedido"; break;
			case "84": "Número Autorização Inexistente"; break;
			case "85": "Título com Pagamento Vinculado"; break;
			case "86": "Seu Número Inválido"; break;
			case "87": "e-mail/SMS enviado"; break;
			case "88": "e-mail Lido"; break;
			case "89": "e-mail/SMS devolvido - endereço de e-mail ou número do celular incorreto"; break;
			case "90": "e-mail devolvido - caixa postal cheia"; break;
			case "91": "e-mail/número do celular do Pagador não informado"; break;
			case "92": "Pagador optante por Boleto de Pagamento Eletrônico - e-mail não enviado"; break;
			case "93": "Código para emissão de Boleto de Pagamento não permite envio de e-mail"; break;
			case "94": "Código da Carteira inválido para envio e-mail."; break;
			case "95": "Contrato não permite o envio de e-mail"; break;
			case "96": "Número de contrato inválido"; break;
			case "97": "Rejeição da alteração do prazo limite de recebimento (a data deve ser informada	no campo 28.3.p)"; break;
			case "98": "Rejeição de dispensa de prazo limite de recebimento"; break;
			case "99": "Rejeição da alteração do número do título dado pelo Beneficiário"; break;
			case "A1": "Rejeição da alteração do número controle do participante"; break;
			case "A2": "Rejeição da alteração dos dados do Pagador"; break;
			case "A3": "Rejeição da alteração dos dados do Sacador/avalista"; break;
			case "A4": "Pagador DDA"; break;
			case "A5": "Registro Rejeitado – Título já Liquidado"; break;
			case "A6": "Código do Convenente Inválido ou Encerrado"; break;
			case "A7": "Título já se encontra na situação Pretendida"; break;
			case "A8": "Valor do Abatimento inválido para cancelamento"; break;
			case "A9": "Não autoriza pagamento parcial"; break;
			case "B1": "Autoriza recebimento parcial"; break;
			case "B2": "Valor Nominal do Título Conflitante"; break;
			case "B3": "Tipo de Pagamento Inválido"; break;
			case "B4": "Valor Máximo/Percentual Inválido"; break;
			case "B5": "Valor Mínimo/Percentual Inválido"; break;
		}
	}

	private function tipocobranca($tipocobranca, $codigoocorrencia){
		switch($codigoocorrencia){
			case "05":
			case "06":
			case "07":
			case "08":
			case "15":
			case "46":
				switch($tipocobranca){
					case "01": return "liquidação normal"; break;
					case "02": return "liquidação parcial"; break;
					case "03": return "liquidação por saldo"; break;
					case "04": return "liquidação com cheque a compensar"; break;
					case "05": return "liquidação de título sem registro (carteira 7 tipo 4)"; break;
					case "07": return "liquidação na apresentação"; break;
					case "09": return "liquidação em cartório"; break;
				}
				break;
			case "02":
				switch($tipocobranca){
					case "00": return "por meio magnético"; break;
					case "11": return "por via convencional"; break;
					case "16": return "por alteração do código do cedente"; break;
					case "17": return "por alteração da variação"; break;
					case "18": return "por alteração da carteira"; break;
				}
				break;
			case "09":
			case "10":
			case "20":
				switch($tipocobranca){
					case "00": return "solicitada pelo cliente"; break;
					case "15": return "protestado"; break;
					case "18": return "por alteração da carteira"; break;
					case "19": return "débito automático"; break;
					case "31": return "liquidado anteriormente" ;break;
					case "32": return "habilitado em processo"; break;
					case "33": return "incobrável por nosso intermédio"; break;
					case "34": return "transferido para créditos em liquidação"; break;
					case "46": return "por alteração da variação"; break;
					case "47": return "por alteração da variação"; break;
					case "51": return "acerto"; break;
					case "90": return "baixa automática"; break;
				}
				break;
			case "03";
				switch($tipocobranca){
					case "01": return "identificação inválida"; break;
					case "02": return "variação da carteira inválida"; break;
					case "03": return "valor dos juros por um dia inválido"; break;
					case "04": return "valor do desconto inválido"; break;
					case "05": return "espécie de título inválida para carteira/variação"; break;
					case "06": return "espécie de valor invariável inválido"; break;
					case "07": return "prefixo da agência usuária inválido"; break;
					case "08": return "valor do título/apólice inválido"; break;
					case "09": return "data de vencimento inválida"; break;
					case "10": return "fora do prazo/só admissível na carteira"; break;
					case "11": return "inexistência de margem para desconto"; break;
					case "12": return "o banco não tem agência na praça do sacado"; break;
					case "13": return "razões cadastrais"; break;
					case "14": return "sacado interligado com o sacador (só admissível em cobrança simples- cart. 11e 17)"; break;
					case "15": return "Titulo sacado contra órgão do Poder Público (só admissível na carteira 11 e sem ordem de protesto)"; break;
					case "16": return "Titulo preenchido de forma irregular"; break;
					case "17": return "Titulo rasurado"; break;
					case "18": return "Endereço do sacado não localizado ou incompleto"; break;
					case "19": return "Código do cedente inválido"; break;
					case "20": return "Nome/endereço do cliente não informado (ECT)"; break;
					case "21": return "Carteira inválida"; break;
					case "22": return "Quantidade de valor variável inválida"; break;
					case "23": return "Faixa nosso-numero excedida"; break;
					case "24": return "Valor do abatimento inválido"; break;
					case "25": return "Novo número do título dado pelo cedente inválido (Seu número)"; break;
					case "26": return "Valor do IOF de seguro inválido"; break;
					case "27": return "Nome do sacado/cedente inválido"; break;
					case "28": return "Data do novo vencimento inválida"; break;
					case "29": return "Endereço não informado"; break;
					case "30": return "Registro de título já liquidado (carteira 17-tipo 4)"; break;
					case "31": return "Numero do borderô inválido"; break;
					case "32": return "Nome da pessoa autorizada inválido"; break;
					case "33": return "Nosso número já existente"; break;
					case "34": return "Numero da prestação do contrato inválido"; break;
					case "35": return "percentual de desconto inválido"; break;
					case "36": return "Dias para fichamento de protesto inválido"; break;
					case "37": return "Data de emissão do título inválida"; break;
					case "38": return "Data do vencimento anterior à data da emissão do título"; break;
					case "39": return "Comando de alteração indevido para a carteira"; break;
					case "40": return "Tipo de moeda inválido"; break;
					case "41": return "Abatimento não permitido"; break;
					case "42": return "CEP/UF inválido/não compatíveis (ECT)"; break;
					case "43": return "Código de unidade variável incompatível com a data de emissão do título"; break;
					case "44": return "Dados para debito ao sacado inválidos"; break;
					case "45": return "Carteira/variação encerrada"; break;
					case "46": return "Convenio encerrado"; break;
					case "47": return "Titulo tem valor diverso do informado"; break;
					case "48": return "Motivo de baixa invalido para a carteira"; break;
					case "49": return "Abatimento a cancelar não consta do título"; break;
					case "50": return "Comando incompatível com a carteira"; break;
					case "51": return "Código do convenente invalido"; break;
					case "52": return "Abatimento igual ou maior que o valor do titulo"; break;
					case "53": return "Titulo já se encontra na situação pretendida"; break;
					case "54": return "Titulo fora do prazo admitido para a conta 1"; break;
					case "55": return "Novo vencimento fora dos limites da carteira"; break;
					case "56": return "Titulo não pertence ao convenente"; break;
					case "57": return "Variação incompatível com a carteira"; break;
					case "58": return "Impossível a variação única para a carteira indicada"; break;
					case "59": return "Titulo vencido em transferência para a carteira 51"; break;
					case "60": return "Titulo com prazo superior a 179 dias em variação única para carteira 51"; break;
					case "61": return "Titulo já foi fichado para protesto"; break;
					case "62": return "Alteração da situação de debito inválida para o código de responsabilidade"; break;
					case "63": return "DV do nosso número inválido"; break;
					case "64": return "Titulo não passível de débito/baixa – situação anormal"; break;
					case "65": return "Titulo com ordem de não protestar – não pode ser encaminhado a cartório"; break;
					case "66": return "Número do documento do sacado (CNPJ/CPF) inválido"; break;
					case "67": return "Titulo/carne rejeitado"; break;
					case "68": return " Código/Data/Percentual de multa inválido"; break;
					case "69": return "Valor/Percentual de Juros Inválido"; break;
					case "70": return "Título já se encontra isento de juros"; break;
					case "71": return "Código de Juros Inválido"; break;
					case "72": return "Prefixo da Ag. cobradora inválido"; break;
					case "73": return "Numero do controle do participante inválido"; break;
					case "74": return "Cliente não cadastrado no CIOPE (Desconto/Vendor)"; break;
					case "75": return "Qtde. de dias do prazo limite p/ recebimento de título vencido inválido"; break;
					case "76": return "Titulo excluído automaticamente por decurso de prazo CIOPE(Desconto/Vendor)"; break;
					case "77": return "Titulo vencido transferido para a conta 1 – Carteira vinculada"; break;
					case "84": return "Título não localizado na existência/Baixado por protesto"; break;
					case "80": return "Nosso numero inválido"; break;
					case "81": return "Data para concessão do desconto inválida. Gerada nos seguintes casos:11 - erro na data do desconto;12 - data do desconto anterior à data de emissão"; break;
					case "82": return "CEP do sacado inválido"; break;
					case "83": return "Carteira/variação não localizada no cedente"; break;
					case "84": return "Titulo não localizado na existência"; break;
					case "99": return "Outros motivos"; break;
				}
				break;
			case "72":
				switch($tipocobranca){
					case "00": return "transferência de título de cobrança simples para descontada ou vice-versa"; break;
					case "52": return "reembolso de título vendor ou descontado, quando ocorrerem reembolsos de títulos por falta de liquidação. Não há migração de carteira descontada para simples."; break;
				}
			default: return "";
				break;
		}
	}


	private function ocorrencia($codigo){
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

	function processar_retorno_pagamento($arr_arquivo){
		if(!is_array($arr_arquivo)){
			$arr_arquivo = array($arr_arquivo);
		}
		$arr_retorno = array();
		foreach($arr_arquivo as $i => $arquivo){
			setprogress((($i + 1) / sizeof($arr_arquivo) * 100), "Lendo arquivos: ".($i + 1)." de ".sizeof($arr_arquivo));
			$arr_linha = read_file($arquivo);
			foreach($arr_linha as $linha){
				// Verifica se e linha detalhe
				$dados_retorno = new stdClass();
				$dados_retorno = $this->getdadosliquidacao($linha);

				if(isset($dados_retorno->codlancto)){
					// Captura as ocorrencias do lancamento
					$arr_ocorrencia = array();
					for($j = 0; $j < 10; ($j = $j + 2)){
						$ocorrencia = trim(substr($dados_retorno->ocorrencias, $j, 2));
						if(strlen($ocorrencia) > 0){
							$arr_ocorrencia[] = $this->ocorrencia($ocorrencia);
						}
					}
					$arr_retorno[] = array(
						"codlancto" => $dados_retorno->codlancto,
						"liquidado" => (substr($dados_retorno->ocorrencias, 0, 2) == "00"),
						"dtliquid" => $dados_retorno->dtliquid,
						"ocorrencia" => $arr_ocorrencia
					);
				}
			}
		}
		return parent::processar_retorno($arr_retorno);
	}

	/*
	function processar_retorno_pagamento($arr_arquivo){
		if(!is_array($arr_arquivo)){
			$arr_arquivo = array($arr_arquivo);
		}
		$arr_retorno = array();
		foreach($arr_arquivo as $i => $arquivo){
			setprogress((($i + 1) / sizeof($arr_arquivo) * 100), "Lendo arquivos: ".($i + 1)." de ".sizeof($arr_arquivo));
			$arr_linha = read_file($arquivo);
			foreach($arr_linha as $linha){
				// Verifica se e linha detalhe
				if(substr($linha, 7, 1) == "3"){
					unset($codlancto);
					switch(substr($linha, 13, 1)){
						case "J": // Liquidacao de titulos (boletos)
							$codlancto = (int) substr($linha, 182, 20);
							$ocorrencias = substr($linha, 230, 10);
							$dtliquid = substr($linha, 144, 8);
							break;
						case "O": // Pagamento de contas de concessionaria e tributos com codigo de barras
							$codlancto = (int) substr($linha, 174, 20);
							$ocorrencias = substr($linha, 230, 10);
							$dtliquid = substr($linha, 136, 8);
							break;
						case "N": // Pagamento de tributos sem codigo de barras
							$codlancto = (int) substr($linha, 215, 20);
							$ocorrencias = substr($linha, 230, 10);
							switch(substr($linha, 17, 2)){
								case "01": $dtliquid = substr($linha, 99, 8);
									break;
								case "02": $dtliquid = substr($linha, 127, 8);
									break;
								case "03": $dtliquid = substr($linha, 127, 8);
									break;
								case "04": $dtliquid = substr($linha, 141, 8);
									break;
								case "05": $dtliquid = substr($linha, 146, 8);
									break;
								case "07": $dtliquid = substr($linha, 116, 8);
									break;
								case "08": $dtliquid = substr($linha, 116, 8);
									break;
								case "11": $dtliquid = substr($linha, 143, 8);
									break;
							}
							break;
					}
					$dtliquid = convert_date($dtliquid, "dmY", "Y-m-d");
					if(isset($codlancto)){
						// Captura as ocorrencias do lancamento
						$arr_ocorrencia = array();
						for($j = 0; $j < 10; ($j = $j + 2)){
							$ocorrencia = trim(substr($ocorrencias, $j, 2));
							if(strlen($ocorrencia) > 0){
								$arr_ocorrencia[] = $this->ocorrencia($ocorrencia);
							}
						}
						$arr_retorno[] = array(
							"codlancto" => $codlancto,
							"liquidado" => (substr($ocorrencias, 0, 2) == "00"),
							"dtliquid" => $dtliquid,
							"ocorrencia" => $arr_ocorrencia
						);
					}
				}
			}
		}
		return parent::processar_retorno($arr_retorno);
	}
	 *
	 */

	// Retorna o tipo de servico para geracao da remessa
	protected function tipo_servico(Lancamento $lancamento){
		if($lancamento->getpagrec() == self::PAGAMENTO){
			if(in_array($lancamento->getcodtipoaceite(), array(1, 2, 3))){
				return "20";
			}else{
				return "22";
			}
		}else{
			return "01";
		}
	}

	public function setnumcheque($numcheque){
		$this->numcheque = $numcheque;
	}

	function gerar_header_arquivo($codigo_convenio, $versao){
		/****************************************************
		  R E G I S T R O   H E A D E R   D O   A R Q U I V O
		****************************************************/
		$linha  = "001";				//Código do Banco na Compensação
		$linha .= "0000";				//Lote de Serviço
		$linha .= "0";					//Tipo de Registro
		$linha .= str_repeat(" ", 9);	//Uso Exclusivo FEBRABAN / CNAB
		$linha .= "2";					//Tipo de Inscrição da Empresa '0' = Isento / Não Informado '1' = CPF '2' = CGC / CNPJ '3' = PIS / PASEP '9' = Outros
		$linha .= removeformat($this->estabelecimento->getcpfcnpj());				//Número de Inscrição da Empresa
		$linha .= $codigo_convenio;													//Código do Convênio no Banco
		$linha .= $this->formatar_inteiro($this->banco->getagencia(), 5);			//Agência Mantenedora da Conta
		$linha .= $this->formatar_texto($this->banco->getdigitoagencia(), 1);		//Dígito Verificador da Agência
		$linha .= $this->formatar_inteiro($this->banco->getconta(), 12);			// Número da Conta Corrente
		$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 0, 1), 1);//Digito verificador da conta
		$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 1, 1), 1);// Digito verificador da agencia/conta
		$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome da empresa
		$linha .= $this->formatar_texto($this->banco->getnome(), 30);				// Nome do banco
		$linha .= str_repeat(" ", 10);												//Uso Exclusivo FEBRABAN / CNAB
		$linha .= "1";																//Código Remessa / Retorno: '1' = Remessa (Cliente  Banco) '2' = Retorno (Banco  Cliente)
		$linha .= date("dmY");														// Data de geracao do arquivo
		$linha .= date("His");														// Hora da geracao do arquivo
		$linha .= $this->formatar_inteiro($this->banco->getseqremessa(), 6);		// Numero sequencial do arquivo
		$linha .= "   "; //$versao;													// Numero da versao do layout do arquivo
		$linha .= $this->formatar_inteiro(0, 5);									// Densidade de gravcao do arquivo
		$linha .= str_repeat(" ", 20); // Para uso reservado do banco
		$linha .= str_repeat(" ", 20); // Para uso reservado da empresa
		$linha .= str_repeat(" ", 29); // Complemento de registro//Data de Geração do Arquivo
		return $linha;
	}

	function gerar_detalhe_arquivo($arr_lancamento, $codigo_convenio, $versao, &$codigolote){
		$arr_detalhe = array();
		$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);
		foreach($arr_lancamento as $tipo_servico => $arr_lancamento_2){
			foreach($arr_lancamento_2 as $forma_lancamento => $arr_lancamento_3){
				$codigolote++;
				$lancamento = $arr_lancamento_3[0]; // Pega o primeiro lancamento para decidir qual lote deve gerar

				/*				 * *****************************************
				  R E G I S T R O   H E A D E R   D E   L O T E
				 * ***************************************** */
				$linha = "001"; // Codigo do banco na compensacao
				$linha .= $this->formatar_inteiro($codigolote, 4); // Lote de identificacao de pagamentos
				$linha .= "1";	// Tipo do registro header do lote
				$linha .= ($lancamento->getpagrec() == self::PAGAMENTO ? "C" : "R");	// Tipo da operacao 'C' = Lançamento a Crédito
								//														'D' = Lançamento a Débito
								//														'E' = Extrato para Conciliação
								//														'G' = Extrato para Gestão de Caixa
								//														'I' = Informações de Títulos Capturados do Próprio Banco
								//														'R' = Arquivo Remessa
								//														'T' = Arquivo Retorno
				$linha .= $this->formatar_inteiro($tipo_servico, 2); // Tipo de servico
				if($lancamento->getpagrec() == self::PAGAMENTO){
					$linha .= $this->formatar_inteiro($forma_lancamento, 2); // Forma de lancament
				}else{
					$linha .= "  ";	//										Uso Exclusivo FEBRABAN/CNAB
				}
				$linha .= $versao; // Versao do layout do lote
				$linha .= " "; // Complemento de registro
				$linha .= "2"; // Tipo da inscricao da empresa (1 = CPF; 2 = CNPJ)
				if($lancamento->getpagrec() == self::PAGAMENTO){
					$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), "14"); // Numero da inscricao (CPF/CNPJ) da empresa
				}else{
					$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), "15"); // Numero da inscricao (CPF/CNPJ) da empresa
				}
				$linha .= $codigo_convenio; // Codigo do convenio no banco
				$linha .= $this->formatar_inteiro($this->banco->getagencia(), 5); // Numero da agencia debitada
				$linha .= $this->formatar_texto($this->banco->getdigitoagencia(), 1); // Digito da agencia debitada
				$linha .= $this->formatar_inteiro($this->banco->getconta(), 12); // Numero da conta debitada
				$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 0, 1), 1); // Digito da conta debitada
				$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 1, 1), 1); // Digito da agencia/conta debitada
				$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome da empresa debitada
				$linha .= str_repeat(" ", 40); // Mensagem
				if($lancamento->getpagrec() == self::RECEBIMENTO){
					$linha .= str_repeat(" ", 40); // Mensagem
					$linha .= $this->formatar_inteiro($this->banco->getseqremessa(), "08");	//Número Remessa/Retorno
					$linha .= date("dmY");
					$linha .= str_repeat("0", 8);											//Data do Crédito
					$linha .= str_repeat(" ", 33);											//Uso Exclusivo FEBRABAN/CNAB
				}else{
					$linha .= $this->formatar_texto($this->estabelecimento->getendereco(), 30); // Endereco da empresa
					$linha .= $this->formatar_inteiro($this->estabelecimento->getnumero(), 5); // Numero do endereco da empresa
					$linha .= $this->formatar_texto($this->estabelecimento->getcomplemento(), 15); // Complemento do endereco da empresa
					$linha .= $this->formatar_texto($cidade->getnome(), 20); // Nome da cidade da empresa
					$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcep()), 8); // CEP da empresa
					$linha .= $cidade->getuf(); // UF da empresa
					$linha .= str_repeat(" ", 8); // Complemento de registro
					$linha .= str_repeat(" ", 10); // Ocorrencias (apenas para arquivo de retorno)
				}
				$arr_detalhe[] = $linha;

				$total_valorliquido = 0;

				if($lancamento->getpagrec() == self::PAGAMENTO && in_array($lancamento->getcodtipoaceite(), array(1, 2))){

					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){
						/*						 * *************************************************************
						  R E G I S T R O   D E T A L H E   D E   L O T E   ( B O L E T O )
						 * ************************************************************* */
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
						$linha .= "J"; // Codigo do segmento do registro detalhe
						$linha .= "0"; // Tipo de movimento (0 = inclusao)
						$linha .= "00"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
						$codbarras_temp = $lancamento->getcodbarras();
						$codbarras = substr($codbarras_temp, 0, 4); // Codigo do banco + codigo moeda
						$codbarras .= substr($codbarras_temp, 32, 1); // Codigo do digito verificador do codigo de barras
						$codbarras .= substr($codbarras_temp, 33, 4); // Fator de vencimanto
						$codbarras .= substr($codbarras_temp, 37, 10); // Valor
						$codbarras .= substr($codbarras_temp, 4, 5); // Campo livre
						$codbarras .= substr($codbarras_temp, 10, 10); // Campo livre
						$codbarras .= substr($codbarras_temp, 21, 10); // Campo livre
						$linha .= $this->formatar_inteiro($codbarras, 44); // Codigo de barras
						$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome do cedente
						$linha .= convert_date($lancamento->getdtvencto(),"Y-m-d","dmY"); // Data do vencimento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do titulo
						$linha .= $this->formatar_decimal($lancamento->getvalordescto(), 15, 2); // Valor do desconto/abatimento
						$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da mora/multa
						$linha .= convert_date($lancamento->getdtvencto(),"Y-m-d","dmY"); // Data do pagamento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do pagamento
						$linha .= $this->formatar_inteiro(0, 15); // Quantidade da moeda
						$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Numero do documento atribuido pela empresa
						$linha .= $this->formatar_texto($lancamento->getnossonumero(), 20); // Numero do documento atribuido pelo banco
						$linha .= "09"; // Codigo da moeda
						$linha .= $this->formatar_texto("", 6); // Complemento de registro
						$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
						$arr_detalhe[] = $linha;

						$total_valorliquido += $lancamento->getvalorliquido();
					}

					/*					 * **************************************************************
					  R E G I S T R O   T R A I L E R   D E   L O T E    ( B O L E T O )
					 * ************************************************************** */
					$linha = "001"; // Codigo do banco na compensacao
					$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
					$linha .= "5"; // Tipo do registro trailer do lote
					$linha .= str_repeat(" ", 9); // Complemento de registro
					$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3) + 2), 6); // Total de registros no lote
					$linha .= $this->formatar_decimal($total_valorliquido, 18, 2); // Total do valor do lote
					$linha .= $this->formatar_inteiro(0, 18); // Somatoria de quantidade de moedas
					$linha .= $this->formatar_inteiro(0, 6); // Numero aviso debito
					$linha .= $this->formatar_texto("", 165); // Complemento de registro
					$linha .= $this->formatar_texto("", 10); // Codigos de ocorrencia (apenas para retorno)
					$arr_detalhe[] = $linha;
				}elseif($lancamento->getpagrec() == self::PAGAMENTO && ($lancamento->getcodtipoaceite() == 3 || strlen($lancamento->getcodbarras()) > 0)){
					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){
						/*						 * ***************************************************************************************************
						  R E G I S T R O   D E T A L H E   D E   L O T E   ( C O N C E S S I O N A R I A   E   T R I B U T O S )
						 * *************************************************************************************************** */
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
						$linha .= "O"; // Codigo do segmento do registro detalhe
						$linha .= "0"; // Tipo de movimento (0 = inclusao)
						$linha .= "00"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
						$codbarras_temp = $lancamento->getcodbarras();
						$codbarras = substr($codbarras_temp, 0, 11); // Bloco 1
						$codbarras .= substr($codbarras_temp, 12, 11); // Bloco 2
						$codbarras .= substr($codbarras_temp, 24, 11); // Bloco 3
						$codbarras .= substr($codbarras_temp, 35, 11); // Bloco 4
						$linha .= $this->formatar_inteiro($codbarras, 44); // Codigo de barras
						$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome da concessionaria ou orgao publico
						$linha .= $this->formatar_data($lancamento->getdtvencto(),"P"); // Data do vencimento
						$linha .= $this->formatar_data($lancamento->getdtvencto(),"P"); // Data do pagamento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do pagamento
						$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
						$linha .= $this->formatar_texto("", 20); // Nosso numero (apenas para retorno)
						$linha .= $this->formatar_texto("", 68); // Complemento de registro
						$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)

						$arr_detalhe[] = $linha;

						$total_valorliquido += $lancamento->getvalorliquido();
					}

					/*					 * ****************************************************************************************************
					  R E G I S T R O   T R A I L E R   D E   L O T E    ( C O N C E S S I O N A R I A   E   T R I B U T O S )
					 * **************************************************************************************************** */
					$linha = "001"; // Codigo do banco na compensacao
					$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
					$linha .= "5"; // Tipo do registro trailer do lote
					$linha .= str_repeat(" ", 9); // Complemento de registro
					$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3) + 2), 6); // Total de registros no lote
					$linha .= $this->formatar_decimal($total_valorliquido, 18, 2); // Total do valor do lote
					$linha .= $this->formatar_inteiro(0, 15); // Total de quantidade de moeda
					$linha .= $this->formatar_texto("", 174); // Complemento de registro
					$linha .= $this->formatar_texto("", 10); // Codigos de ocorrencia (apenas para retorno)
					$arr_linha[] = $linha;
				}elseif($lancamento->getpagrec() == self::PAGAMENTO){

					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){

						/*						 * *************************************************************************************************************
						  R E G I S T R O   D E T A L H E   D E   L O T E   ( T R I B U T O S   S E M   C O D I G O S   D E   B A R R A S )
						 * ************************************************************************************************************* */
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
						$linha .= "N"; // Codigo do segmento do registro detalhe
						$linha .= "0"; // Tipo de movimento (0 = inclusao)
						$linha .= "00"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
						$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
						$linha .= $this->formatar_texto("", 20); // Nosso numero (apenas para retorno)
						$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome do favorecido
						$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do pagamento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do pagamento
						switch($lancamento->getcodtipoaceite()){
							case 8: // Composicao dos dados para pagamento de GPS
								$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
								$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
								$linha .= "17"; // Codigo de identificacao do tributo
								$linha .= $this->formatar_inteiro($lancamento->getmescompetencia().$lancamento->getanocompetencia(), 6); // Mes e ano da competencia
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor previsto do pagamento do INSS
								$linha .= $this->formatar_decimal(0, 15, 2); // Valor de outras entidades
								$linha .= $this->formatar_decimal(0, 15, 2); // Atualizacao monetaria
								$linha .= $this->formatar_texto("", 45); // Complemento do registro
								break;
							case 5: // Composicao dos dados para pagamento de DARF simples
								$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
								$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
								$linha .= "03"; // Codigo de identificacao do tributo
								$linha .= $this->formatar_data($lancamento->getdtemissao()); // Periodo de apuracao
								$linha .= $this->formatar_decimal(0, 15, 2); // Valor da receita bruta acumulada
								$linha .= $this->formatar_decimal(0, 5, 2); // Percentual sobre a receita bruta acumulada
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor principal
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da multa
								$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 15, 2); // Valor do juros/encargos
								$linha .= $this->formatar_texto("", 21); // Complemento do registro
								break;
							case 9: // Composicao dos dados para pagamento de IPVA
							case 12: // Composicao dos dados para pagamento de DPVAT
								$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
								$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
								$linha .= ($lancamento->getcodtipoaceite() == 9 ? "07" : "08"); // Identificacao do tributo (07 = IPVA; 08 = DPVAT)
								$linha .= $this->formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "Y"), 4); // Ano base
								$linha .= $this->formatar_inteiro(0, 9); // Codigo do RENAVAM
								$linha .= $this->formatar_texto($cidade->getuf(), 2); // Unidade da federacao
								$linha .= $this->formatar_inteiro($cidade->getcodoficial(), 5); // Codigo do municipio
								$linha .= $this->formatar_texto("", 7); // Placa do veiculo
								$linha .= ($lancamento->getcodtipoaceite() == 9 ? "2" : "0"); // Opcao de pagamento
								$linha .= $this->formatar_texto("", 68); // Complemento do registro
								break;
							case 4: // Composicao dos dados para pagamento de DARF normal
								$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
								$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
								$linha .= "02"; // Identificacao do tributo (02 = DARF)
								$linha .= $this->formatar_data($lancamento->getdtemissao()); // Periodo de apuracao
								$linha .= $this->formatar_inteiro($lancamento->getreferencia(), 17); // Numero de referencia
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor principal
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da multa
								$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 15, 2); // Valor do juros/encargos
								$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do vencimento
								$linha .= $this->formatar_texto("", 18); // Complemento do registro
								break;
							case 7: // Composicao dos dados para pagamento de GARE-SP (ICMS/DR/ITCMD)
								$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
								$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
								$linha .= "05"; // Identificacao do tributo (05 = ICMS)
								$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do vencimento
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
								$linha .= $this->formatar_inteiro(0, 13); // Divida ativa / numero etiqueta
								$linha .= $this->formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "mY"), 6); // Mes/ano de referencia
								$linha .= $this->formatar_inteiro($lancamento->getparcela(), 13); // Numero de parcela/notificacao
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor da receita
								$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 14, 2); // Valor do juros
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
								$linha .= $this->formatar_texto("", 1); // Complemento do registro
								break;
							case 6: // Composicao dos dados para pagamento de DARJ
								$linha .= $this->formatar_texto("", 6); // Codigo da receita do tributo
								$linha .= "01"; // Tipo de identificacao do contribuinte (01 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
								$linha .= $this->formatar_inteiro(0, 16); // Numero do documento origem
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 15, 2); // Valor principal
								$linha .= $this->formatar_decimal(0, 15, 2); // Valor da atualizacao monetaria
								$linha .= $this->formatar_decimal(0, 15, 2); // Valor da mora
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da multa
								$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data do vencimento
								$linha .= $this->formatar_inteiro($lancamento->getparcela(), 6); // Periodo de referencia ou numero de parcela
								break;
							case 13: // Composicao dos dados para pagamento de FGTS-GRF/GRRF/GRDE
								$linha .= "11"; // Identificacao do tributo (11 = FGTS)
								$linha .= $this->formatar_inteiro(0, 6); // Codigo da receita
								$linha .= "01"; // Tipo de inscricao do contribuinte (01 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
								$linha .= $this->formatar_inteiro(0, 16); // Identificador do FGTS
								$linha .= $this->formatar_inteiro(0, 9); // Lacre de conectividade social
								$linha .= $this->formatar_inteiro(0, 2); // Digito do lacre de conectividade social
								$linha .= $this->formatar_texto("", 1); // Complemento do registro
								break;
						}
						$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
						$arr_detalhe[] = $linha;
					}
					/*					 * **************************************************************************************************************
					  R E G I S T R O   T R A I L E R   D E   L O T E    ( T R I B U T O S   S E M   C O D I G O S   D E   B A R R A S )
					 * ************************************************************************************************************** */
					$linha = "001"; // Codigo do banco na compensacao
					$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
					$linha .= "5"; // Tipo do registro trailer do lote
					$linha .= str_repeat("", 9); // Complemento de registro
					$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3) + 2), 6); // Total de registros no lote
					$linha .= $this->formatar_decimal($total_valorliquido, 18, 2); // Total do valor do lote
					$linha .= $this->formatar_texto("", 189); // Complemento de registro
					$linha .= $this->formatar_texto("", 10); // Codigos de ocorrencia (apenas para retorno)
					$arr_detalhe[] = $linha;

				}elseif($lancamento->getpagrec() == self::RECEBIMENTO){
					$seq_registro = 0;
					foreach($arr_lancamento_3 as $i => $lancamento){
						/*****************************************************************************************************************
						  R E G I S T R O   D E T A L H E   D E   L O T E   ( C O B R A N Ç A  S I M P L E S - S E G U I M E N T O "P" )
						 * ************************************************************************************************************* */
						$seq_registro++;
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro($seq_registro, 5); // Sequencial do registro no lote
						$linha .= "P"; // Codigo do segmento do registro detalhe
						$linha .= " "; // Uso Exclusivo FEBRABAN/CNAB
						$linha .= "01"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
						$linha .= $this->formatar_inteiro($this->banco->getagencia(), 5); // Numero da agencia debitada
						$linha .= $this->formatar_texto($this->banco->getdigitoagencia(), 1); // Digito da agencia debitada
						$linha .= $this->formatar_inteiro($this->banco->getconta(), 12); // Numero da conta debitada
						$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 0, 1), 1); // Digito da conta debitada
						$linha .= $this->formatar_texto(substr($this->banco->getdigito(), 1, 1), 1); // Digito da agencia/conta debitada
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
							$linha .= $this->formatar_texto($convenio.$nossonumero, 20);// nosso numero
						}
						$linha .= $this->formatar_texto($this->banco->getcodcarteira(),1);	//Código da Carteira
																							//'1' = Cobrança Simples
																							//'2' = Cobrança Vinculada
																							//'3' = Cobrança Caucionada
																							//'4' = Cobrança Descontada
																							//case "5": "Cobrança Vendo
						$linha .= $this->formatar_texto($this->banco->gettipocadcobranca(), 1);	//Forma de Cadastr. do Título no Banco
																								//'1' = Com Cadastramento (Cobrança Registrada)
																								//'2' = Sem Cadastramento (Cobrança sem Registro)
																								//Obs.: Destina-se somente para emissão de Boleto de Pagamento pelo banco
																								//'3' = Com Cadastramento
						$linha .= "1";														//Tipo de documento
																							//'1' = Tradicional
																							//'2' = Escritural
						if($this->banco->getbancoemiteboleto() == "S"){						//Identificação da Emissão do Boleto de Pagamento
							$linha .= "1";													//'1' = Banco Emite
						}else{
							$linha .= "2";													//'2' = Cliente Emite
						}
						$linha .= $this->formatar_inteiro($this->banco->gettipodistribuicao(), 1); //Identificação da Distribuição
																									//'1' = Banco Distribui
																									//'2' = Cliente Distribui
																									//case "3": "Banco envia e-mail
																									//case "4": "Banco envia SMS
						//$linha .= $this->formatar_texto($lancamento->getcodlancto(), 15);			 //Número do Documento de Cobrança (seu numero)
						$linha .= $this->formatar_texto($lancamento->getnumnotafis(), 15);			 //Número do Documento de Cobrança (seu numero)
						$linha .= $this->formatar_data($lancamento->getdtvencto(), "");					 // Data do vencimento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2);    // Valor nominal do titulo
						$linha .= str_repeat("0", 5);			 // Agência Encarregada da Cobrança
						$linha .= "0";		 // Digito da agencia debitada
						$linha .= "02";																 //Especie do titulo '02' = DM Duplicata Mercantil
						if($this->banco->getaceitetitulo() == "S"){									 //Identific. de Título Aceito/Não Aceito
							$linha .= "A";
						}else{
							$linha .= "N";
						}
						$linha .= $this->formatar_data($lancamento->getdtemissao(), "");				 // Data de emissão do titulo
						if($this->banco->getvalormoradiaria() > 0){
							$linha .= "1";																 //Código do Juros de Mora (Valor Diario)
							$linha .= date("dmY", strtotime("+1 day", strtotime($lancamento->getdtvencto()))); // Data do Juros de Mora
						}else{
							$linha .= "3";																 //Código do Juros de Mora(Isento)
							$linha .= str_repeat("0", 8);												 //Data do Juros de Mora
						}
						$linha .= $this->formatar_decimal(($lancamento->getvalorliquido() * ($this->banco->getvalormoradiaria() / 100) / 30), 15, 2); // Valor de juro de mora diaria
						$linha .= "0";																	//Código do Desconto 1
						$linha .= str_repeat("0", 8);													//Data do Desconto 1
						$linha .= str_repeat("0", 15);													//Valor/Percentual a ser Concedido
						$linha .= str_repeat("0", 15);													//Valor do IOF a ser Recolhido
						$linha .= str_repeat("0", 15);													//Valor do Abatimento
						$linha .= $this->formatar_texto($lancamento->getcodlancto(), 25);				//Identificação do Título na Empresa
						if($this->banco->getdiasprotesto() > 0){
							$linha .= "1";																//Código para Protesto
							$linha .= $this->formatar_inteiro($this->banco->getdiasprotesto(), 2);		//Número de Dias para Protesto
						}else{
							$linha .= "3";																//Código para Protesto
							$linha .= "00";																//Número de Dias para Protesto
						}
						$linha .= "0";																	//Código para Baixa/Devolução
						$linha .= str_repeat("0", 3);													//Número de Dias para Baixa/Devolução
						$linha .= "09";																	//Código da Moeda
						$linha .= str_repeat("0", 10);													//Nº do Contrato da Operação de Créd.
						$linha .= " ";																	//Uso livre banco/empresa ou autorização de pagamento parcial

						$arr_detalhe[] = $linha;

						$total_valorliquido += $lancamento->getvalorliquido();

						/*****************************************************************************************************************
						  R E G I S T R O   D E T A L H E   D E   L O T E   ( C O B R A N Ç A  S I M P L E S - S E G U I M E N T O "Q" )
						 * ************************************************************************************************************* */
						$seq_registro++;
						$parceiro = $this->arr_parceiro[$lancamento->gettipoparceiro().";".$lancamento->getcodparceiro()];
						$linha = "001"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro($seq_registro, 5); // Sequencial do registro no lote
						$linha .= "Q"; // Codigo do segmento do registro detalhe
						$linha .= " "; // Uso Exclusivo FEBRABAN/CNAB
						$linha .= "01"; // Codigo da instrucao para movimento (00 = inclusao de registro detalhe liberado)
						$cpfcnpj = removeformat($parceiro->getcpfcnpj());
						if(strlen($cpfcnpj) > 11){
							$linha .= "2";									//Tipo de Inscrição da Empresa
						}else{
							$linha .= "1";									//Tipo de Inscrição da Empresa
						}
						$numero_endereco = ", ".trim($parceiro->getnumero());
						$linha .= $this->formatar_inteiro(removeformat($parceiro->getcpfcnpj()), 15);	//Número de Inscrição
						$linha .= $this->formatar_texto($parceiro->getrazaosocial(), 40); //Nome parceiro
						$linha .= $this->formatar_texto($parceiro->getendereco(), 40 - strlen($numero_endereco)).$numero_endereco; //Endereço
						$linha .= $this->formatar_texto($parceiro->getbairro(), 15);	//Bairro
						$linha .= $this->formatar_texto($parceiro->getcep(), 5);		//CEP
						$linha .= substr($parceiro->getcep(), 6, 3);					//sufixo do CEP
						$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
						$linha .= $this->formatar_texto($cidade_parceiro->getnome(), 15);	// cidade
						$linha .= $parceiro->getuf();		// Unidade federação
						$linha .= " ";						//Tipo inscrição sacador avalista
						$linha .= str_repeat(" ", 15);		//numero inscrição sacador avalista
						$linha .= str_repeat(" ", 40);		//nome sacador avalista
						$linha .= "000";					//Cód. Bco. Corresp. na Compensação
						$linha .= str_repeat(" ", 20);		//Nosso Nº no Banco Correspondente
						$linha .= str_repeat(" ", 8);		//Uso Exclusivo FEBRABAN/CNAB

						$arr_detalhe[] = $linha;

						$nrprocesso = (is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : NULL);
						if($this->ocorrencia == "N" && !$this->remessa_anterior){
							$lancamento->setseqremessa($this->banco->getseqremessa());
							$lancamento->setprocessogr($nrprocesso);
							$lancamento->setcodocorrencia("00");
							$lancamento->setocorrencia(utf8_encode($this->ocorrencia_recebimento("00")));
							$lancamento->setmotivoocorrencia("Aguardando Retorno");
							$lancamento->setdtremessa(date("Y-m-d"));
							$lancamento->setcodbanco($this->banco->getcodbanco());
						}elseif($this->ocorrencia == "C" || $this->ocorrencia == "G"){
							$lancamento->setseqremessa(NULL);
							if($this->ocorrencia == "C"){
								$lancamento->setnossonumero(NULL);
							}
							$lancamento->setprocessogr(NULL);
							if($this->ocorrencia == "C"){
								$lancamento->setcodocorrencia("01");
								$lancamento->setocorrencia(utf8_encode($this->ocorrencia_recebimento("01")));
								$lancamento->setmotivoocorrencia("Geracao de remessa para cancelamento no banco");
							}else{
								$lancamento->setcodocorrencia(NULL);
								$lancamento->setocorrencia(NULL);
								$lancamento->setmotivoocorrencia("Cancelada geração da remessa");
							}
							$lancamento->setdtremessa(NULL);
						}
						if(!$lancamento->save()){
							return FALSE;
						}
						if(!$this->banco->save()){
							return FALSE;
						}
					}
					/******************************************************************************************************************
					  R E G I S T R O   T R A I L E R   D E   L O T E    ( T R I B U T O S   S E M   C O D I G O S   D E   B A R R A S )
					 **************************************************************************************************************** */
					$linha = "001"; // Codigo do banco na compensacao
					$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
					$linha .= "5"; // Tipo do registro trailer do lote
					$linha .= str_repeat(" ", 9); // Complemento de registro
					$linha .= $this->formatar_inteiro(((sizeof($arr_lancamento_3) * 2) + 2), 6); // Total de registros no lote
					$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3)), 6);     // Total de titulos em cobranca
					$linha .= $this->formatar_decimal($total_valorliquido, 17, 2); // Total do valor do lote
					$linha .= $this->formatar_inteiro(0, 6);		//Quantidade de Títulos em Cobrança a Cobrança
					$linha .= $this->formatar_decimal(0, 17, 2);	//Valor Total dosTítulos em Carteiras a Cobrança
					$linha .= $this->formatar_inteiro(0, 6);		//Quantidade de Títulos em Cobrança	Caucionada
					$linha .= $this->formatar_decimal(0, 17, 2);	//Valor Total dosTítulos em Carteiras Caucionada
					$linha .= $this->formatar_inteiro(0, 6);		//Quantidade de Títulos em Cobrança Descontada
					$linha .= $this->formatar_decimal(0, 17, 2);	//Valor Total dosTítulos em Carteiras Descontada
					$linha .= $this->formatar_texto("", 8); // Complemento de registro
					$linha .= $this->formatar_texto("", 117); // Codigos de ocorrencia (apenas para retorno)
					$arr_detalhe[] = $linha;
				}
			}
		}
		return $arr_detalhe;
	}

	function gerar_trailer_arquivo($qtde_lotes, $qtde_registros){
		/*******************************************************
		  R E G I S T R O   T R A I L L E R   D O   A R Q U I V O
		 *******************************************************/
		$arr_trailer = array();
		$linha = "001";							// Codigo do banco na compensacao
		$linha .= "9999";						// Codigo do lote
		$linha .= "9";							// Tipo do registro
		$linha .= $this->formatar_texto("", 9); // Complemento de registro
		$linha .= $this->formatar_inteiro($qtde_lotes, 6); // Quantidade de lotes do arquivo
		$linha .= $this->formatar_inteiro($qtde_registros + 1, 6); // Quantidade de registros do arquivo
		$linha .= $this->formatar_inteiro(0, 6); // Quantidade de contas para conciliacao *** V E R I F I C A R ***
		$linha .= $this->formatar_texto("", 205); // Complemento de registro
		$arr_trailer[] = $linha."\r\n";
		return $arr_trailer;
	}

}
