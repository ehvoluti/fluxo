<?php

require_once("../class/interfacebancaria.class.php");

class Itau extends InterfaceBancaria{
	private $refatorado = TRUE;
	protected $arr_banco; // Array com os bancos dos lancamentos
	protected $arr_instrucaobancaria; //array contendo as intrucoes para o boleto no arquivo de remessa (recebimento apenas)
	protected $arr_remessa = array();

	// Carrega os cadastros auxiliares para gerar os arquivos
	protected function carregar_auxiliares(){
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
		}
		$this->arr_banco = object_array_key(objectbytable("banco", NULL, $this->con), $arr_codbanco);
		$instrucaobancaria = objectbytable("instrucaobancaria", NULL, $this->con);
		$instrucaobancaria->setcodoficial($this->banco->getcodoficial());
		$obj_arr_instrucaobancaria = object_array($instrucaobancaria);
		$this->arr_instrucaobancaria = array();

		foreach($obj_arr_instrucaobancaria AS $instrucaobancaria){
			$this->arr_instrucaobancaria[$instrucaobancaria->getcodigoinstrucao()] = $instrucaobancaria;
		}
	}

	// Retorna a forma de pagamento para geracao de remessa
	protected function forma_pagamento($lancamento){
		switch($lancamento->getcodtipoaceite()){
			case 1:
			case 2:
				if(substr($lancamento->getcodbarras(), 0, 3) == "341"){
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
	}

	public function gerar_remessa_pagamento(){
		if(!parent::gerar_remessa()){
			return FALSE;
		}

		$this->banco->setseqremessa($this->banco->getseqremessa() + 1);
		if(!$this->banco->save()){
			return FALSE;
		}

		if($this->refatorado){
			$arr_lancamento = $this->carregarauxiliares();

			// Pega a cidade do estabelecimento
			$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);

			// Array que contem todas a linha do arquivo
			$arr_linha = array();

			//$arr_linha[] = $CNAB240->gerar_header_arquivo($this->banco, $this->estabelecimento, $seqremessa,"080");
			$arr_linha[] = $this->gerar_header_arquivo_240($seqremessa,"080", "P");

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
		}
		// Array que contem todas as informacoes para o relatorio
		$arr_remessa = array();

		// Carrega os cadastros auxiliares
		$this->carregar_auxiliares();

		// Separa os lancamentos para criacao dos lotes
		$arr_lancamento = array();
		foreach($this->arr_lancamento as $lancamento){
			// Montando arquivo de relatorio
			$arr_remessa[] = array(
				"chave" => $lancamento->getcodlancto(),
				"numnotafis" => $lancamento->getnumnotafis(),
				"favorecido" => $lancamento->getfavorecido(),
				"dtemissao" => $lancamento->getdtemissao(),
				"dtvencto" => $lancamento->getdtvencto(),
				"valor" => $lancamento->getvalorparcela()
			);
			$arr_lancamento[$this->tipo_pagamento($lancamento)][$this->forma_pagamento($lancamento)][] = $lancamento;
		}

		// Pega a cidade do estabelecimento
		$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);

		// Array que contem todas a linha do arquivo
		$arr_linha = array();

		/*		 * ***********************************************
		  R E G I S T R O   H E A D E R   D O   A R Q U I V O
		 * *********************************************** */
		$linha = $this->formatar_inteiro("341", 3); // Codigo do banco na compensacao
		$linha .= $this->formatar_inteiro("0", 4); // Codigo do lote
		$linha .= $this->formatar_inteiro("0", 1); // Tipo do registro
		$linha .= $this->formatar_texto(NULL, 6); // Complemento(brancos)
		$linha .= $this->formatar_inteiro("080", 3); // Versao do layout do arquivo
		$linha .= $this->formatar_inteiro("2", 1); // Tipo da inscricao da empresa
		$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), "14"); // Numero da inscricao(CPF/CNPJ) da empresa
		$linha .= $this->formatar_texto(NULL, 20); // Bancos
		$linha .= $this->formatar_inteiro($this->banco->getagencia(), 5); // Numero da agencia
		$linha .= $this->formatar_texto(NULL, 1); // Branco
		$linha .= $this->formatar_inteiro($this->banco->getconta(), 12); // Numero da conta
		$linha .= $this->formatar_texto(NULL, 1); // Branco
		$linha .= $this->formatar_inteiro(trim($this->banco->getdigito()), 1); // Digito da conta corrente
		$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome da empresa
		$linha .= $this->formatar_texto($this->banco->getnome(), 30); // Nome do banco
		$linha .= $this->formatar_texto(NULL, 10); // Brancos
		$linha .= $this->formatar_inteiro("1", 1); // Codigo do arquivo (1-remessa 2-retorno)
		$linha .= $this->formatar_data(date("Y-m-d"), "P"); // Data de geracao do arquivo
		$linha .= $this->formatar_hora(date("H:i:s")); // Hora da geracao do arquivo
		$linha .= $this->formatar_inteiro(NULL, 9); // Zeros
		$linha .= $this->formatar_inteiro(NULL, 5); // Unidade de densidade de gravcao do arquivo
		$linha .= $this->formatar_texto(NULL, 69); // Complemento(brancos)
		$arr_linha[] = $linha;

		$codigolote = 0; // Codigo do lote (inicia do zero, pois cada lote incrementa um antes da geracao)
		foreach($arr_lancamento as $tipo_pagamento => $arr_lancamento_2){
			foreach($arr_lancamento_2 as $forma_pagamento => $arr_lancamento_3){
				$codigolote++;
				$lancamento = $arr_lancamento_3[0]; // Pega o primeiro lancamento para decidir qual lote deve gerar

				/*				 * *****************************************
				  R E G I S T R O   H E A D E R   D E   L O T E
				 * ***************************************** */
				$linha = $this->formatar_inteiro("341", 3); // Codigo do banco na compensacao
				$linha .= $this->formatar_inteiro($codigolote, 4); // Lote de identificacao de pagamentos
				$linha .= $this->formatar_inteiro("1", 1); // Tipo do registro header do lote
				$linha .= $this->formatar_texto("C", 1); // Tipo da operacao (C = credito)
				$linha .= $this->formatar_inteiro($tipo_pagamento, 2); // Tipo de pagamento
				$linha .= $this->formatar_inteiro($forma_pagamento, 2); // Forma de pagamento
				$linha .= $this->formatar_inteiro("030", 3); // Versao do layout do lote
				$linha .= $this->formatar_texto(NULL, 1); // Complemento de registro
				$linha .= $this->formatar_inteiro("2", 1); // Tipo da inscricao da empresa (1 = CPF; 2 = CNPJ)
				$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), 14); // Numero da inscricao (CPF/CNPJ) da empresa
				$linha .= $this->formatar_texto("", 20); // Complemento de registro
				$linha .= $this->formatar_inteiro($this->banco->getagencia(), 5); // Numero da agencia debitada
				$linha .= $this->formatar_texto(NULL, 1); // Complemento de registro
				$linha .= $this->formatar_inteiro($this->banco->getconta(), 12); // Numero da conta debitada
				$linha .= $this->formatar_texto(NULL, 1); // Complemento de registro
				$linha .= $this->formatar_inteiro(trim($this->banco->getdigito()), 1); // Digito da conta debitada
				$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome da empresa debitada
				$linha .= $this->formatar_texto(NULL, 30); // Finalidade dos pagamentos do lote
				$linha .= $this->formatar_texto(NULL, 10); // Complementos historico C/C debitada
				$linha .= $this->formatar_texto($this->estabelecimento->getendereco(), 30); // Endereco da empresa
				$linha .= $this->formatar_inteiro($this->estabelecimento->getnumero(), 5); // Numero do endereco da empresa
				$linha .= $this->formatar_texto($this->estabelecimento->getcomplemento(), 15); // Complemento do endereco da empresa
				$linha .= $this->formatar_texto($cidade->getnome(), 20); // Nome da cidade da empresa
				$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcep()), 8); // CEP da empresa
				$linha .= $this->formatar_texto($cidade->getuf(), 2); // UF da empresa
				$linha .= $this->formatar_texto(NULL, 8); // Complemento de registro
				$linha .= $this->formatar_texto(NULL, 10); // Ocorrencias (apenas para arquivo de retorno)
				$arr_linha[] = $linha;

				$total_valorliquido = 0;

				if(in_array($lancamento->getcodtipoaceite(), array(1, 2))){

					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){
						/* * *************************************************************
						  R E G I S T R O   D E T A L H E   D E   L O T E   ( B O L E T O )
						 * ************************************************************* */
						//pegar o parceiro referente ao lancamento
						$parceiro = objectbytable("v_parceiro", array($lancamento->gettipoparceiro(), $lancamento->getcodparceiro()), $this->con);

						$codbarras = $lancamento->getcodbarras();
						$linha = "341"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
						$linha .= "J"; // Codigo do segmento do registro detalhe
						$linha .= "000"; // Tipo de movimento (000 = inclusao de pagamento)
						$linha .= $this->formatar_inteiro(substr($codbarras, 0, 3), 3); // Codigo de barras - banco favorecido
						$linha .= $this->formatar_inteiro(substr($codbarras, 3, 1), 1); // Codigo de barras - moeda
						$linha .= $this->formatar_inteiro(substr($codbarras, 32, 1), 1); // Codigo de barras - digito verificador
						$linha .= $this->formatar_inteiro(substr($codbarras, 33, 4), 4); // Codigo de barras - fator de vencimento
						$linha .= $this->formatar_inteiro(substr($codbarras, 37, 10), 10); // Codigo de barras - valor do boleto
						$linha .= $this->formatar_inteiro(substr($codbarras, 4, 5).substr($codbarras, 10, 10).substr($codbarras, 21, 10), 25); // Codigo de barras - campo livre
						$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome do favorecido
						$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do titulo
						$linha .= $this->formatar_decimal($lancamento->getvalordescto(), 15, 2); // Valor do desconto/abatimento
						$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 15, 2); // Valor da mora/multa
						$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor do pagamento
						$linha .= $this->formatar_inteiro(0, 15); // Complemento de registro
						$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
						$linha .= $this->formatar_texto("", 13); // Complemento de registro
						$linha .= $this->formatar_texto("", 15); // Nosso numero (apenas para retorno)
						$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
						$arr_linha[] = $linha;

						//Gerar o segmento J-52

						$cpfcnpj_beneficiario = removeformat($this->estabelecimento->getcpfcnpj());
						$cnpj_pagador = removeformat($parceiro->getcpfcnpj());
						$linha =  "341";	//codigo do banco na compesação
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
						$linha .= "J"; // Codigo do segmento do registro detalhe
						$linha .= "000"; // Tipo de movimento (000 = inclusao de pagamento)
						$linha .= "52";	//identificação do registro opcional
						$linha .= "2";	//tipo de inscrição do pagador
						$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), 15); // Numero da inscricao(CPF/CNPJ) da empresa
						$linha .= $this->formatar_texto( $this->estabelecimento->getrazaosocial(), 40); // Nome do pagador
						$linha .= (strlen($cpfcnpj_beneficiario) > 11 ? "2" : "1");	//tipo de inscrição do pagador
						$linha .= $this->formatar_inteiro($cnpj_pagador, 15); // Numero da inscricao (CPF/CNPJ) do beneficiario
						$linha .= $this->formatar_texto(removespecial($lancamento->getfavorecido()), 40); // Nome do beneficiario

						if(strlen($lancamento->getcnpjboleto()) > 0){
							$cpfcnpj_sacadoravalista = removeformat($lancamento->getcnpjboleto());
							$linha .= (strlen($cpfcnpj_sacadoravalista) > 11 ? "2" : "1");	//TIPO DE INSCRIÇÃO DO SACADOR AVALISTA
							$linha .= $this->formatar_inteiro($cpfcnpj_sacadoravalista, 15); // NUMERO DE INSCRIÇÃO DO SACADOR AVALISTA
							$linha .= $this->formatar_texto($cpfcnpj_sacadoravalista, 40); //NOME DO SACADOR AVALISTA
							$linha .= str_pad("", 53, " ", STR_PAD_LEFT);
						}else{
							$linha .= str_pad("", 109, " ", STR_PAD_LEFT);
						}

						$arr_linha[] = $linha;
						$total_valorliquido += $lancamento->getvalorliquido();
					}

					/*					 * **************************************************************
					  R E G I S T R O   T R A I L E R   D E   L O T E    ( B O L E T O )
					 * ************************************************************** */
					$linha = "341"; // Codigo do banco na compensacao
					$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
					$linha .= "5"; // Tipo do registro trailer do lote
					$linha .= str_repeat(" ", 9); // Complemento de registro
					//$linha .= $this->formatar_inteiro((sizeof($arr_lancamento_3) + 2), 6); // Total de registros no lote
					$linha .= $this->formatar_inteiro(((sizeof($arr_lancamento_3) * 2) + 2), 6); // Total de registros no lote
					$linha .= $this->formatar_decimal($total_valorliquido, 18, 2); // Total do valor do lote
					$linha .= $this->formatar_inteiro(0, 18); // Complemento de registro
					$linha .= $this->formatar_texto("", 171); // Complemento de registro
					$linha .= $this->formatar_texto("", 10); // Codigos de ocorrencia (apenas para retorno)
					$arr_linha[] = $linha;
				}elseif($lancamento->getcodtipoaceite() == 3 || strlen($lancamento->getcodbarras()) > 0){
					// Percorre os lancamentos para criar o detalhe do lote
					foreach($arr_lancamento_3 as $i => $lancamento){
						/*						 * ***************************************************************************************************
						  R E G I S T R O   D E T A L H E   D E   L O T E   ( C O N C E S S I O N A R I A   E   T R I B U T O S )
						 * *************************************************************************************************** */
						$codbarras = $lancamento->getcodbarras();
						$linha = "341"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
						$linha .= "O"; // Codigo do segmento do registro detalhe
						$linha .= "000"; // Tipo de movimento (000 = inclusao de pagamento)
						$linha .= $this->formatar_texto($codbarras, 48); // Codigo de barras
						$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome do favorecido
						$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
						$linha .= "REA"; // Tipo de moeda
						$linha .= $this->formatar_decimal(0, 15, 8); // Quantidade da moeda (informar apenas se a moeda for diferente de Real)
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor previsto do pagamento
						$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor de efetivacao do pagamento
						$linha .= $this->formatar_texto("", 3); // Complemento de registro
						$linha .= $this->formatar_inteiro($lancamento->getnumnotafis(), 9); // Numero da nota fiscal
						$linha .= $this->formatar_texto("", 3); // Complemento de registro
						$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
						$linha .= $this->formatar_texto("", 21); // Complemento de registro
						$linha .= $this->formatar_texto("", 15); // Nosso numero (apenas para retorno)
						$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
						$arr_linha[] = $linha;

						$total_valorliquido += $lancamento->getvalorliquido();
					}

					/*					 * ****************************************************************************************************
					  R E G I S T R O   T R A I L E R   D E   L O T E    ( C O N C E S S I O N A R I A   E   T R I B U T O S )
					 * **************************************************************************************************** */
					$linha = "341"; // Codigo do banco na compensacao
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
						$codbarras = $lancamento->getcodbarras();
						$linha = "341"; // Codigo do banco na compensacao
						$linha .= $this->formatar_inteiro($codigolote, 4); // Codigo do lote
						$linha .= "3"; // Tipo de registro
						$linha .= $this->formatar_inteiro(($i + 1), 5); // Sequencial do registro no lote
						$linha .= "N"; // Codigo do segmento do registro detalhe
						$linha .= "000"; // Tipo de movimento (000 = inclusao de pagamento)
						switch($lancamento->getcodtipoaceite()){
							case 4: // Composicao dos dados para pagamento de DARF normal
								$linha .= "02"; // Identificacao do tributo (02 = DARF)
								$linha .= $this->formatar_inteiro(0, 4); // Codigo da receita
								$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
								$linha .= $this->formatar_data($lancamento->getdtemissao(), "P"); // Periodo de apuracao
								$linha .= $this->formatar_inteiro($lancamento->getreferencia(), 17); // Numero de referencia
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor principal
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
								$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 14, 2); // Valor do juros/encargos
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor total a ser pago
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
								$linha .= $this->formatar_texto("", 30); // Complemento do registro
								$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
								break;
							case 5: // Composicao dos dados para pagamento de DARF simples
								$linha .= "03"; // Identificacao do tributo (03 = DARF simples)
								$linha .= $this->formatar_inteiro(0, 4); // Codigo da receita
								$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
								$linha .= $this->formatar_data($lancamento->getdtemissao(), "P"); // Periodo de apuracao
								$linha .= $this->formatar_decimal(0, 9, 2); // Valor da receita bruta acumulada
								$linha .= $this->formatar_decimal(0, 4, 2); // Percentual sobre a receita bruta acumulada
								$linha .= $this->formatar_texto("", 4); // Complemento do registro
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor principal
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
								$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 14, 2); // Valor do juros/encargos
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor total a ser pago
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
								$linha .= $this->formatar_texto("", 30); // Complemento do registro
								$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
								break;
							case 6: // Composicao dos dados para pagamento de DARJ
								$linha .= "04"; // Identificacao do tributo (04 = DARJ)
								$linha .= $this->formatar_inteiro(0, 4); // Codigo da receita
								$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
								$linha .= $this->formatar_inteiro(0, 16); // Numero do documento origem
								$linha .= $this->formatar_texto("", 1); // Complemento de registro
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor principal
								$linha .= $this->formatar_decimal(0, 14, 2); // Valor da atualizacao monetaria
								$linha .= $this->formatar_decimal(0, 14, 2); // Valor da mora
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor total a ser pago
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
								$linha .= $this->formatar_inteiro($lancamento->getparcela(), 6); // Periodo de referencia ou numero de parcela
								$linha .= $this->formatar_texto("", 10); // Complemento do registro
								$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
								break;
							case 7: // Composicao dos dados para pagamento de GARE - SP ICMS
								$linha .= "05"; // Identificacao do tributo (05 = ICMS)
								$linha .= $this->formatar_inteiro(0, 4); // Codigo da receita
								$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getrgie()), 8); // Inscricao estadual do contribuinte
								$linha .= $this->formatar_inteiro(0, 13); // Divida ativa / numero etiqueta
								$linha .= $this->formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "mY"), 6); // Mes/ano de referencia
								$linha .= $this->formatar_inteiro($lancamento->getparcela(), 13); // Numero de parcela/notificacao
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor da receita
								$linha .= $this->formatar_decimal($lancamento->getvalorjuros(), 14, 2); // Valor do juros
								$linha .= $this->formatar_decimal($lancamento->getvaloracresc(), 14, 2); // Valor da multa
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor do pagamento
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
								$linha .= $this->formatar_texto("", 11); // Complemento do registro
								$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
								break;
							case 8: // Composicao dos dados para pagamento de GPS
								$linha .= "02"; // Identificacao do tributo (02 = GPS)
								$linha .= "2100"; // Codigo de pagamento (2100 = empresa em geral  - CNPJ)
								$linha .= $this->formatar_inteiro($lancamento->getmescompetencia().$lancamento->getanocompetencia(), 6); // Mes e ano da competencia
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // Identificacao CNPJ do contribuinte
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor previsto do pagamento do INSS
								$linha .= $this->formatar_decimal(0, 14, 2); // Valor de outras entidades
								$linha .= $this->formatar_decimal(0, 14, 2); // Atualizacao monetaria
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor arrecadado
								$linha .= $this->formatar_data(date("Y-m-d"), "P"); // Data da arrecadacao/efetivacao do pagamento
								$linha .= $this->formatar_texto("", 8); // Complemento do registro
								$linha .= $this->formatar_texto("", 50); // Informacoes complementares
								$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
								break;
							case 9: // Composicao dos dados para pagamento de IPVA
							case 12: // Composicao dos dados para pagamento de DPVAT
								$linha .= ($lancamento->getcodtipoaceite() == 9 ? "07" : "08"); // Identificacao do tributo (07 = IPVA; 08 = DPVAT)
								$linha .= $this->formatar_texto("", 4); // Complemento de registro
								$linha .= "2"; // Tipo de inscricao do contribuinte (1 = CPF; 2 = CNPJ)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
								$linha .= $this->formatar_inteiro(convert_date($lancamento->getdtemissao(), "Y-m-d", "Y"), 4); // Ano base
								$linha .= $this->formatar_inteiro(0, 9); // Codigo do RENAVAM
								$linha .= $this->formatar_inteiro(0, 13); // Divida ativa / numero etiqueta
								$linha .= $this->formatar_texto($cidade->getuf(), 2); // Unidade da federacao
								$linha .= $this->formatar_inteiro($cidade->getcodoficial(), 5); // Codigo do municipio
								$linha .= $this->formatar_texto("", 7); // Placa do veiculo
								$linha .= ($lancamento->getcodtipoaceite() == 9 ? "2" : "0"); // Opcao de pagamento
								$linha .= $this->formatar_decimal($lancamento->getvalorparcela(), 14, 2); // Valor do IPVA/DPVAT
								$linha .= $this->formatar_decimal($lancamento->getvalordesconto(), 14, 2); // Valor do desconto
								$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 14, 2); // Valor do pagamento
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
								$linha .= $this->formatar_texto("", 41); // Complemento do registro
								$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
								break;
							case 13: // Composicao dos dados para pagamento de FGTS-GRF/GRRF/GRDE
								$linha .= "11"; // Identificacao do tributo (11 = FGTS)
								$linha .= $this->formatar_inteiro(0, 4); // Codigo da receita
								$linha .= "1"; // Tipo de inscricao do contribuinte (1 = CNPJ; 2 = CEI)
								$linha .= $this->formatar_inteiro(removespecial($this->estabelecimento->getcpfcnpj()), 14); // CPF ou CNPJ do contribuinte
								$linha .= $this->formatar_texto($lancamento->getcodbarras(), 48); // Codigo de barras
								$linha .= $this->formatar_inteiro(0, 16); // Identificador do FGTS
								$linha .= $this->formatar_inteiro(0, 9); // Lacre de conectividade social
								$linha .= $this->formatar_inteiro(0, 2); // Digito do lacre de conectividade social
								$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome do contribuinte
								$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
								$linha .= $this->formatar_texto("", 30); // Complemento do registro
								break;
						}
						$linha .= $this->formatar_texto($lancamento->getfavorecido(), 30); // Nome do favorecido
						$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do vencimento
						$linha .= "REA"; // Tipo de moeda
						$linha .= $this->formatar_decimal(0, 15, 8); // Quantidade da moeda (informar apenas se a moeda for diferente de Real)
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor previsto do pagamento
						$linha .= $this->formatar_data($lancamento->getdtvencto(), "P"); // Data do pagamento
						$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 15, 2); // Valor de efetivacao do pagamento
						$linha .= $this->formatar_texto("", 3); // Complemento de registro
						$linha .= $this->formatar_inteiro($lancamento->getnumnotafis(), 9); // Numero da nota fiscal
						$linha .= $this->formatar_texto("", 3); // Complemento de registro
						$linha .= $this->formatar_texto($lancamento->getcodlancto(), 20); // Seu numero
						$linha .= $this->formatar_texto("", 21); // Complemento de registro
						$linha .= $this->formatar_texto("", 15); // Nosso numero (apenas para retorno)
						$linha .= $this->formatar_texto("", 10); // Ocorrencia (apenas para retorno)
						$arr_linha[] = $linha;
					}
				}
			}
		}

		/*		 * *************************************************
		  R E G I S T R O   T R A I L E R   D O   A R Q U I V O
		 * ************************************************* */
		$linha = "341"; // Codigo do banco na compensacao
		$linha .= "9999"; // Codigo do lote
		$linha .= "9"; // Tipo do registro
		$linha .= $this->formatar_texto("", 9); // Complemento de registro
		$linha .= $this->formatar_inteiro($codigolote, 6); // Quantidade de lotes do arquivo
		$linha .= $this->formatar_inteiro((sizeof($arr_linha) + 1), 6); // Quantidade de registros do arquivo
		$linha .= $this->formatar_texto("", 211); // Complemento de registro
		$arr_linha[] = $linha."\r\n";

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

		// Gera relatorio de remessa na pasta temp
		$controleprocfinan = is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";
		$paramarqremessa = array("numremessa" => $controleprocfinan, "banco" => $this->banco->getcodoficial(), "nomebanco" => str_replace(" ", "", $this->banco->getnome()));

		parent::relatorio_remessa($this->arr_remessa, $paramarqremessa);
		echo write_file($this->estabelecimento->getdirremessabanco().$this->formatar_inteiro($controleprocfinan, 6).".".$this->banco->getcodoficial(), $arr_linha, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"), "w+", true);

		return TRUE;
	}

	function gerar_remessa_recebimento(){
		$this->carregar_auxiliares();
		if(parent::gerar_remessa()){
			$this->con->start_transaction();
			if(!$this->linha_header()){
				$this->con->rollback();
				return FALSE;
			}
			$item = 2;
			foreach($this->arr_lancamento as $lancamento){
				if(!$this->linha_detalhe($lancamento, $item++, (is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : NULL))){
					$this->con->rollback();
					return FALSE;
				}
			}

			$this->linha_trailler($item);
			if(!$this->banco->save()){
				$this->con->rollback();
				return FALSE;
			}
			$controleprocfinan = is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";
			//$paramarqremessa = array("numremessa" => $this->controleprocfinan->getcodcontrprocfinan(),"banco" => $this->banco->getcodoficial(),"nomebanco" => str_replace(" ","",$this->banco->getnome()));
			$paramarqremessa = array("numremessa" => $controleprocfinan, "banco" => $this->banco->getcodoficial(), "nomebanco" => str_replace(" ", "", $this->banco->getnome()));
			parent::relatorio_remessa($this->arr_remessa, $paramarqremessa);
			if($this->ocorrencia == "N" || $this->ocorrencia == "C"){
				//echo write_file($this->estabelecimento->getdirremessabanco()."CB".date("dm").$this->banco->getnrultremessa().".rem",$this->arr_linha);
				echo write_file($this->estabelecimento->getdirremessabanco().$this->banco->gettiporemessa().date("dm").$this->banco->getnrultremessa().".rem", $this->arr_linha, (param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"), "w+", true);
			}
			$this->con->commit();
			return TRUE;
		}else{
			return FALSE;
		}
	}

	function linha_header(){
		if((!$this->remessa_anterior) || ($this->remessa_anterior && $this->ocorrencia == "02")){
			$this->banco->setseqremessa($this->banco->getseqremessa() + 1);
			if(!$this->banco->save()){
				return FALSE;
			}
		}

		$linha = "0"; // Tipo do registro
		$linha .= "1"; // Tipo de operacao
		$linha .= "REMESSA"; // Lieral "REMESSA"
		if($this->banco->gettiporemessa() == "FC"){
			$linha .= "04"; // Codigo do servico
			$linha .= $this->formatar_texto("FINANCIAMENTO", 15); // Literal servico
		}else{
			$linha .= "01"; // Codigo do servico
			$linha .= $this->formatar_texto("COBRANCA", 15); // Literal servico
		}
		$linha .= $this->formatar_inteiro($this->banco->getagencia(), 4); // Codigo da Agencia
		$linha .= "00"; // Complemento de registros
		$linha .= $this->formatar_inteiro($this->banco->getconta(), 5); // Numero da conta corrente
		$linha .= $this->banco->getdigito(); // Digito verificador
		$linha .= $this->formatar_texto("", 8); // Complemento do Registro
		$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()), 30); // Nome da empresa mae
		$linha .= "341"; // numero do banco na camara de compensacao
		$linha .= $this->formatar_texto("BANCO ITAU SA", 15); // Nome do banco
		$linha .= date("dmy"); // Data da geracao do arquivo
		$linha .= $this->formatar_texto("", 294); // Complemento do Registro
		$linha .= "000001"; // Sequencial do registro
		$this->arr_linha[] = $linha;
		return TRUE;
	}

	function linha_detalhe($lancamento, $item, $nrprocesso){
		$parceiro = objectbytable("vparceiro", array($lancamento->gettipoparceiro(), $lancamento->getcodparceiro()), $this->con);
		$especie = objectbytable("especie", $lancamento->getcodespecie(), $this->con);
		if($this->banco->gettiporemessa() == "CB"){
			$linha = "1"; // Identificacao do registro de transacao
			$linha .= "02"; //Tipo de inscricao da empresa
			$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), 14); // Numero de inscricao da empresa
			$linha .= $this->formatar_inteiro($this->banco->getagencia(), 4); // Agencia mantedora da conta
			$linha .= "00"; // Complemento de registro
			$linha .= $this->formatar_inteiro($this->banco->getconta(), 5); // Numero da conta corrente
			$linha .= $this->banco->getdigito(); // Digito verificador
			$linha .= $this->formatar_texto("", 4); // Brancos
			$linha .= "0000"; // Alegacao a ser cancelada
			$linha .= $this->formatar_texto($lancamento->getcodlancto(), 25); // Identificacao do titulo na empresa

			$nosso_numero = "00000000";
			if($this->banco->getbancoemiteboleto() == "N"){
				if(strlen($lancamento->getnossonumero()) == 0){
					$this->banco->setnossonumero($this->banco->getnossonumero() + 1);
					$nosso_numero = trim($this->banco->getnossonumero());
					$nosso_numero = $this->formatar_inteiro($nosso_numero, 8);
				}else{
					$nosso_numero = $this->formatar_inteiro($lancamento->getnossonumero(), 8);
				}
			}
			$linha .= $nosso_numero; // Identificacao do titulo no banco
			$linha .= $this->formatar_inteiro(0, 13); //Quantidade de moeda variavel
			$linha .= $this->formatar_inteiro($this->banco->getcarteira(), 3); // Numero da carteira no banco
			$linha .= $this->formatar_texto("", 21); // Identificacao da operacao no banco
			$linha .= "I"; // Codigo da carteira
			$linha .= $this->ocorrencia(); // Codigo da ocorrencia
			if($lancamento->gettotalparcelas() == 1){
				$linha .= $this->formatar_texto(trim($lancamento->getnumnotafis()), 10);  // Numero do documento de cobranca
			}else{
				$linha .= $this->formatar_texto(trim($lancamento->getnumnotafis())."/".trim($lancamento->getparcela()), 10); // Numero do documento de cobranca
				//$linha .= $this->formatar_texto($lancamento->getcodlancto(), 10);
			}
			$linha .= $this->formatar_data($lancamento->getdtvencto(), "R"); // Data de vencimento do titulo
			$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 13, 2); // Valor do titulo
			$linha .= "341"; // Codigo do banco
			$linha .= "00000"; // Agencia cobradora
			$linha .= "01"; // Especie do titulo (01-DMI 08-DSI
			$linha .= "N"; // Identificao do titulo aceito ou nao
			$linha .= $this->formatar_data($lancamento->getdtemissao(), "R"); // Data de emissao do titulo
			$instrucao = "  ";
			if(strlen($this->banco->getcodpinstrucao()) > 0){
				if($especie->getnaoprotestar() == "S"){
					$instrucao = "10";
				}else{
					$instrucao = $this->arr_instrucaobancaria[$this->banco->getcodpinstrucao()]->getinstrucao();
				}
				$linha .= $instrucao;
			}else{
				if($especie->getnaoprotestar() == "S"){
					$linha .= "10";
				}else{
					$linha .= "90"; // Instrucao de cobranca 1
				}
			}
			if(strlen($this->banco->getcodsinstrucao()) > 0){
				if($especie->getnaoprotestar() == "S"){
					$instrucao = "02";
				}else{
					$instrucao = $this->arr_instrucaobancaria[$this->banco->getcodsinstrucao()]->getinstrucao();
				}
				$linha .= $instrucao;
			}else{
				if($especie->getnaoprotestar() == "S"){
					$linha .= "02";
				}else{
					$linha .= "00"; // Instrucao de cobranca 2
				}
			}
			$linha .= $this->formatar_decimal((($this->banco->getvalormoradiaria() * $lancamento->getvalorliquido()) / 30), 13, 0); // Valor de mora por dia de atraso
			$linha .= "000000"; // Data limite para consecao de descontos
			$linha .= $this->formatar_inteiro(0, 13); // Valor do desconto
			$linha .= $this->formatar_inteiro(0, 13); // Valor do IOF
			$linha .= $this->formatar_inteiro(0, 13); // Valor do abatimento a ser concedido ou cancelado
			$linha .= (strlen(removeformat($parceiro->getcpfcnpj())) < 14 ? "01" : "02"); // Tipo de inscricao do sacado
			$linha .= $this->formatar_inteiro(removeformat($parceiro->getcpfcnpj()), 14); // Numero da inscricao do sacado
			$linha .= $this->formatar_texto(substr($parceiro->getrazaosocial(),0,30), 30); // Nome do sacado(30)
			$linha .= $this->formatar_texto("", 10); // Brancos(10)
			$linha .= $this->formatar_texto(trim(substr($parceiro->getendereco(), 0, 30).", ".$this->formatar_texto(trim($parceiro->getnumero()), 8)), 40); // Endereco do sacado;
			$linha .= $this->formatar_texto($parceiro->getbairro(), 12); // Bairro do sacado
			$linha .= $this->formatar_inteiro(removeformat($parceiro->getcep()), 8); // Cep do sacado
			$cidade = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
			$linha .= $this->formatar_texto($cidade->getnome(), 15); // Cidade do sacado
			$linha .= $this->formatar_texto($parceiro->getuf(), 2); // UF do sacado
			$linha .= $this->formatar_texto($this->banco->getsacadoravalista(), 30); // Nome do sacado ou avalista
			$linha .= $this->formatar_texto("", 4); // Brancos
			$linha .= $this->formatar_texto("", 6); // Data de mora
			if(strlen($this->banco->getcodpinstrucao()) > 0){
				$linha .= $this->formatar_decimal($this->banco->getdiasprotesto(), 2, 0);
			}else{
				$linha .= "00"; // Quantidade de dias para protesto
			}
			$linha .= $this->formatar_texto("", 1); // Brancos complemento do registro
			$linha .= $this->formatar_inteiro($item, 6); // Sequencial do registro
		}else{
			$linha = "1"; // Identificacao do registro de transacao
			$linha .= "02"; //Tipo de inscricao da empresa
			$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()), 14); // Numero de inscricao da empresa
			$linha .= $this->formatar_inteiro($this->banco->getagencia(), 4); // Agencia mantedora da conta
			$linha .= "00"; // Complemento de registro
			$linha .= $this->formatar_inteiro($this->banco->getconta(), 5); // Numero da conta corrente
			$linha .= $this->banco->getdigito(); // Digito verificador
			$linha .= $this->formatar_texto("", 4); // Brancos
			$linha .= "0000"; // Alegacao a ser cancelada
			$linha .= $this->formatar_texto($lancamento->getcodlancto(), 22); // Identificacao do titulo na empresa
			$linha .= $this->formatar_inteiro($this->banco->getcarteira(), 3); // Numero da carteira no banco
			if($this->banco->getcarteira() == 344 || $this->banco->getcarteira() == 346){
				$nosso_numero = "        ";
			}elseif($this->banco->getcarteira() == 350){
				if(strlen($lancamento->getnossonumero()) == 0){
					$this->banco->setnossonumero($this->banco->getnossonumero() + 1);
					$nosso_numero = trim($this->banco->getnossonumero());
					$nosso_numero = $this->formatar_inteiro($nosso_numero, 8);
				}else{
					$nosso_numero = $this->formatar_inteiro($lancamento->getnossonumero(), 8);
				}
			}else{
				$nosso_numero = "        ";
			}
			$linha .= $nosso_numero; // Identificacao do titulo no banco
			$linha .= $this->formatar_texto("", 17); //complemento de registro
			if($this->banco->getcarteira() == 424 || $this->banco->getcarteira() == 426){
				$linha .= "000000000000";
			}else{
				$linha .= $this->formatar_texto("", 12);  //codigo do comprador
			}
			$linha .= $this->formatar_texto("", 6); // Complemento de registro
			$linha .= "01"; // Qunatidade de parcelas
			$linha .= "R"; // Codigo no sistema
			$linha .= $this->ocorrencia(); //Codigo da ocorrencia
			$linha .= $this->formatar_texto($lancamento->getcodlancto(), 10);  // Numero do documento de cobranca na empresa
			$linha .= $this->formatar_data($lancamento->getdtvencto(), "R"); // Data de vencimento do titulo
			$linha .= $this->formatar_decimal($lancamento->getvalorliquido(), 13, 2); // Valor do titulo
			$linha .= "341"; // Codigo do banco
			$linha .= "00000"; // Agencia cobradora
			$linha .= "06"; // Especie do titulo
			$linha .= " "; // Complemento de registro
			//$linha .= $this->formatar_data($lancamento->getdtemissao(), "R"); // Data de emissao do titulo
			$linha .= date("dmy"); // Data de vencimento do titulo
			$linha .= "10"; // Instrucao 1
			$linha .= $this->formatar_texto("", 60); // Complemento de registro
			$linha .= (strlen(removeformat($parceiro->getcpfcnpj())) < 14 ? "01" : "02"); //Tipo de inscricao do comprador
			$linha .= $this->formatar_inteiro(removeformat($parceiro->getcpfcnpj()), 14); // Numero da inscricao do comprador
			$linha .= $this->formatar_texto($parceiro->getrazaosocial(), 40); // Nome do comprador(30) + Brancos(10)
			$linha .= $this->formatar_texto(trim(substr($parceiro->getendereco(), 0, 30).", ".$this->formatar_texto(trim($parceiro->getnumero()), 8)), 40); // Endereco do sacado;
			$linha .= $this->formatar_texto($parceiro->getbairro(), 12); // Bairro do sacado
			$linha .= $this->formatar_inteiro(removeformat($parceiro->getcep()), 8); // Cep do sacado
			$cidade = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
			$linha .= $this->formatar_texto($cidade->getnome(), 15); // Cidade do sacado
			$linha .= $this->formatar_texto($parceiro->getuf(), 2); // UF do sacado
			$linha .= " "; // Indicador de vendor de cobranca
			$linha .= "0000000000000";//$this->formatar_decimal($lancamento->getvalorliquido(), 13, 2); // Valor do titulo no venbcimento (valor financiado + juros)
			$linha .= "0000000000000"; // Valor dos encargos
			$linha .= $this->formatar_texto("", 3); // Complemento de registro
			$linha .= "0000000"; // Taxa de juros negociada entre vendedor e comprador
			$linha .= $this->formatar_texto("", 3); // Complemento de registro
			$linha .= "00"; // Quantidade de dias para protesto
			$linha .= $this->formatar_texto("", 1); // Complemento de registro
			$linha .= $this->formatar_inteiro($item, 6); // Sequencial do registro
		}
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

		$this->arr_linha[] = $linha;
		return TRUE;
	}

	function linha_trailler($item){
		$linha = "9"; // Identificacao do registro
		$linha .= $this->formatar_texto("", 393); // Espacos em branco
		$linha .= $this->formatar_inteiro($item, 6); // Sequencial do registro
		$this->arr_linha[] = $linha."\r\n";
		return TRUE;
	}

	private function ocorrencia_pagamento($codigo){
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

	function ocorrencia_recebimento($codocorrencia){
		switch($codocorrencia){
			case "00": return "Geracao Normal";
			case "01": return "Geracao de Cancelamento";
			case "02": return "Entrada confirmada";
			case "03": return "Entrada rejeitada (nota 20 - tabela 1)";
			case "04": return "Alteracao de dados - nova entrada ou alteracao/exclusao de dados acatada";
			case "05": return "Alteracao de dados - baixa";
			case "06": return "Liquidacao normal";
			case "07": return "Liquidacao parcial - cobranca inteligente (B2B)";
			case "08": return "Liquidacao em cartorio";
			case "09": return "Baixa simples";
			case "10": return "Baixa por ter sido liquidado";
			case "11": return "Em ser (so no retorno mensal)";
			case "12": return "Abatimento concedido";
			case "13": return "Abatimento cancelado";
			case "14": return "Vencimento alterado";
			case "15": return "Baixas rejeitadas (nota 20 - tabela 4)";
			case "16": return "Instrucoes rejeitadas (nota 20 - tabela 3)";
			case "17": return "Alteracao/exclusao de dados rejeitados (nota 20 - tabela 2)";
			case "18": return "Cobranca contratual - instrucoes/alteracoes rejeitadas/pendentes (nota 20 - tabela 5)";
			case "19": return "Confirma recebimento de instrucao de protesto";
			case "20": return "Confirma recebimento de instrucao de sustacao de protesto /tarifa";
			case "21": return "Confirma recebimento de instrucao de nao protestar";
			case "22": return "Liquidacao de titulo baixado";
			case "23": return "Titulo enviado a cartorio/tarifa";
			case "24": return "Instrucao de protesto rejeitada / sustada / pendente (nota 20 - tabela 7)";
			case "25": return "Alegacoes do sacado (nota 20 - tabela 6)";
			case "26": return "Tarifa de aviso de cobranca";
			case "27": return "Tarifa de extrato posicao (b40x)";
			case "28": return "Tarifa de relacao das liquidacoes";
			case "29": return "Tarifa de manutencao de titulos vencidos";
			case "30": return "Debito mensal de tarifas (para entradas e baixas)";
			case "32": return "Baixa por ter sido protestado";
			case "33": return "Custas de protesto";
			case "34": return "Custas de sustacao";
			case "35": return "Custas de cartorio distribuidor";
			case "36": return "Custas de edital";
			case "37": return "Tarifa de emissao de boleto/tarifa de envio de duplicata";
			case "38": return "Tarifa de instrucao";
			case "39": return "Tarifa de ocorrencias";
			case "40": return "Tarifa mensal de emissao de boleto/tarifa mensal de envio de duplicata";
			case "41": return "Debito mensal de tarifas - extrato de posicao (b4ep/b4ox)";
			case "42": return "Debito mensal de tarifas - outras instrucoes";
			case "43": return "Debito mensal de tarifas - manutencao de titulos vencidos";
			case "44": return "Debito mensal de tarifas - outras ocorrencias";
			case "45": return "Debito mensal de tarifas - protesto";
			case "46": return "Debito mensal de tarifas - sustacao de protesto";
			case "47": return "Baixa com transferencia para desconto";
			case "48": return "Custas de sustacao judicialmarco 2012 cobranca mensagem cnab 400 21";
			case "51": return "Tarifa mensal ref a entradas bancos correspondentes na carteira";
			case "52": return "Tarifa mensal baixas na carteira";
			case "53": return "Tarifa mensal baixas em bancos correspondentes na carteira";
			case "54": return "Tarifa mensal de liquidacoes na carteira";
			case "55": return "Tarifa mensal de liquidacoes em bancos correspondentes na carteira";
			case "56": return "Custas de irregularidade";
			case "57": return "Instrucao cancelada (nota 20 - tabela 8)";
			case "59": return "Baixa por credito em c/c atraves do sispag";
			case "60": return "Entrada rejeitada carne (nota 20 - tabela 1)";
			case "61": return "Tarifa emissao aviso de movimentacao de titulos (2154)";
			case "62": return "Debito mensal de tarifa - aviso de movimentacao de titulos (2154)";
			case "63": return "Titulo sustado judicialmente";
			case "64": return "Entrada confirmada com rateio de credito";
			case "65": return "Equalizacao";
			case "69": return "Cheque devolvido (nota 20 - tabela 9)";
			case "71": return "Entrada registrada, aguardando avaliacao";
			case "72": return "Baixa por credito em c/c atraves do sispag sem titulo correspondente";
			case "73": return "Confirmacao de entrada na cobranca simples - entrada nao aceita na cobranca contratual";
			case "76": return "Cheque compensado";
		}
	}

	/* -------------------------------------------------------------------------------------------------------
	  Autor: Jesus
	  Objetivo...........: Retornar os motivos conforme codigo de ocorrencia encontradas no arquivo de retorno
	  Parametros.........:
	  $ocorrencia....: codigo da ocorrencia principal encontrada no titulo
	  $codigo_motivo.: lista de codigos de subocorrencias encontrada no titulo (pode conter ate 5 codigos
	  diferentes para uma mesma ocorrencia principal)
	  -------------------------------------------------------------------------------------------------------- */

	function getmotivo($ocorrencia, $codigo_motivo){
		$motivo = "";
		switch($ocorrencia){
			case "02": //entrada confirmada
				$motivo = "Entrada Confirmada";
				break;
			case "03": // entrada rejeitada
				for($i = 0; $i < strlen($codigo_motivo); $i += 2){
					$subocorrencia = substr($codigo_motivo, $i, 2);
					switch($subocorrencia){
						case "03": $motivo .= "Ag. cobradora nao foi possivel atribuir a agencia pelo cep ou cep sem atendimento de protesto no momento";
							break;
						case "04": $motivo .= "Estado sigla do estado invalida";
							break;
						case "05": $motivo .= "Data vencimento prazo da operacao menor que prazo minimo ou maior que o maximo";
							break;
						case "07": $motivo .= "Valor do titulo valor do titulo maior que 10.000.000,00";
							break;
						case "08": $motivo .= "Nome do sacado nao informado ou deslocado";
							break;
						case "09": $motivo .= "Agencia/conta agencia encerrada";
							break;
						case "10": $motivo .= "Logradouro nao informado ou deslocado";
							break;
						case "11": $motivo .= "Cep cep nao numerico ou cep invalido";
							break;
						case "12": $motivo .= "Sacador / avalista nome nao informado ou deslocado (bancos correspondentes)";
							break;
						case "13": $motivo .= "Estado/cep cep incompativel com a sigla do estado";
							break;
						case "14": $motivo .= "Nosso numero nosso numero ja registrado no cadastro do banco ou fora da faixa";
							break;
						case "15": $motivo .= "Nosso numero nosso numero em duplicidade no mesmo movimento";
							break;
						case "18": $motivo .= "Data de entrada data de entrada invalida para operar com esta carteira";
							break;
						case "19": $motivo .= "Ocorrencia ocorrencia invalida";
							break;
						case "21": $motivo .= "Ag. cobradora carteira nao aceita depositaria correspondente estado da agencia diferente do estado do sacado ag. cobradora nao consta no cadastro ou encerrando";
							break;
						case "22": $motivo .= "Carteira carteira nao permitida (necessario cadastrar faixa livre)";
							break;
						case "26": $motivo .= "Agencia/conta agencia/conta nao liberada para operar com cobranca";
							break;
						case "27": $motivo .= "Cnpj inapto cnpj do cedente inapto devolucao de titulo em garantia";
							break;
						case "29": $motivo .= "Codigo empresa categoria da conta invalida";
							break;
						case "30": $motivo .= "Entrada bloqueada entradas bloqueadas, conta suspensa em cobranca";
							break;
						case "31": $motivo .= "Agencia/conta conta nao tem permissao para protestar (contate seu gerente)";
							break;
						case "35": $motivo .= "Valor do iof iof maior que 5%";
							break;
						case "36": $motivo .= "Qtdade de moeda quantidade de moeda incompativel com valor do titulo";
							break;
						case "37": $motivo .= "Cnpj/cpf do sacado nao numerico ou igual a zeros";
							break;
						case "42": $motivo .= "Nosso numero nosso numero fora de faixa";
							break;
						case "52": $motivo .= "Ag. cobradora empresa nao aceita banco correspondente";
							break;
						case "53": $motivo .= "Ag. cobradora empresa nao aceita banco correspondente - cobranca mensagem";
							break;
						case "54": $motivo .= "Data de vencto banco correspondente - titulo com vencimento inferior a 15 dias";
							break;
						case "55": $motivo .= "Dep/bco corresp cep nao pertence à depositaria informada";
							break;
						case "56": $motivo .= "Dt vencto/bco corresp vencto superior a 180 dias da data de entrada";
							break;
						case "57": $motivo .= "Data de vencto cep so depositaria bco do brasil com vencto inferior a 8 dias";
							break;
						case "60": $motivo .= "Abatimento valor do abatimento invalido";
							break;
						case "61": $motivo .= "Juros de mora juros de mora maior que o permitido";
							break;
						case "63": $motivo .= "Desconto de antecipacao valor da importância por dia de desconto (idd) nao permitido";
							break;
						case "64": $motivo .= "Data de emissao data de emissao do titulo invalida";
							break;
						case "65": $motivo .= "Taxa financto taxa invalida (vendor)";
							break;
						case "66": $motivo .= "Data de vencto invalida/fora de prazo de operacao (minimo ou maximo)";
							break;
						case "67": $motivo .= "Valor/qtidade valor do titulo/quantidade de moeda invalido";
							break;
						case "68": $motivo .= "Carteira carteira invalida ou nao cadastrada no intercâmbio da cobranca";
							break;
						case "69": $motivo .= "Carteira carteira invalida para titulos com rateio de credito";
							break;
						case "70": $motivo .= "Agencia/conta cedente nao cadastrado para fazer rateio de credito";
							break;
						case "78": $motivo .= "Agencia/conta duplicidade de agencia/conta beneficiaria do rateio de credito";
							break;
						case "80": $motivo .= "Agencia/conta quantidade de contas beneficiarias do rateio maior do que o permitido (maximo de 30 contas por titulo)";
							break;
						case "81": $motivo .= "Agencia/conta conta para rateio de credito invalida / nao pertence ao itau";
							break;
						case "82": $motivo .= "Desconto/abati-mento desconto/abatimento nao permitido para titulos com rateio de credito";
							break;
						case "83": $motivo .= "Valor do titulo valor do titulo menor que a soma dos valores estipulados para rateio";
							break;
						case "84": $motivo .= "Agencia/conta agencia/conta beneficiaria do rateio e a centralizadora de credito do cedente";
							break;
						case "85": $motivo .= "Agencia/conta agencia/conta do cedente e contratual / rateio de credito nao permitido";
							break;
						case "86": $motivo .= "Tipo de valor codigo do tipo de valor invalido / nao previsto para titulos com rateio de credito";
							break;
						case "87": $motivo .= "Agencia/conta registro tipo 4 sem informacao de agencias/contas beneficiarias do rateio";
							break;
						case "90": $motivo .= "Nro da linha cobranca mensagem - numero da linha da mensagem invalido ou quantidade de linhas excedidas";
							break;
						case "97": $motivo .= "Sem mensagem cobranca mensagem sem mensagem (so de campos fixos), porem com registro do tipo 7 ou 8";
							break;
						case "98": $motivo .= "Flash invalido registro mensagem sem flash cadastrado ou flash informado diferente do cadastrado";
							break;
						case "99": $motivo .= "Flash invalido conta de cobranca com flash cadastrado e sem registro de mensagem correspondente codigos de erros para as subcarteiras 102, 103, 107, 172, 173, 195, 196";
							break;
						case "91": $motivo .= "Dac dac agencia / conta corrente invalido";
							break;
						case "92": $motivo .= "Dac dac agencia / conta corrente / carteira / nosso numero invalido";
							break;
						case "93": $motivo .= "Estado sigla estado invalida";
							break;
						case "94": $motivo .= "Estado sigla estado incompativel com o cep do sacado";
							break;
						case "95": $motivo .= "Cep cep do sacado nao numerico ou invalido";
							break;
						case "96": $motivo .= "Endereco endereco / nome / cidade sacado invalido";
							break;
					}
				}
			case "15":
				for($i = 0; $i < strlen($codigo_motivo); $i += 2){
					$subocorrencia = substr($codigo_motivo, $i, 2);
					switch($subocorrencia){
						case "01": $motivo .= "Carteira/nº numero nao numerico";
							break;
						case "04": $motivo .= "Nosso numero em duplicidade num mesmo movimento";
							break;
						case "05": $motivo .= "Solicitacao de baixa para titulo ja baixado ou liquidado";
							break;
						case "06": $motivo .= "Solicitacao de baixa para titulo nao registrado no sistema";
							break;
						case "07": $motivo .= "Cobranca prazo curto – solicitacao de baixa p/ titulo nao registrado no sistema";
							break;
						case "08": $motivo .= "Solicitacao de baixa para titulo em floating";
							break;
						case "10": $motivo .= "Valor do titulo faz parte de garantia de emprestimo";
							break;
						case "11": $motivo .= "Pago atraves do sispag por credito em c/c e nao baixado";
							break;
					}
				}
			case "16":
				for($i = 0; $i < strlen($codigo_motivo); $i += 2){
					$subocorrencia = substr($codigo_motivo, $i, 2);
					switch($subocorrencia){
						case "01": $motivo .= "Instrucao/ocorrencia nao existente";
							break;
						case "03": $motivo .= "Conta nao tem permissao para protestar (contate seu gerente)";
							break;
						case "06": $motivo .= "Nosso numero igual a zeros";
							break;
						case "09": $motivo .= "Cnpj/cpf do sacador/avalista invalido";
							break;
						case "10": $motivo .= "Valor do abatimento igual ou maior que o valor do titulo";
							break;
						case "11": $motivo .= "Segunda instrucao/ocorrencia nao existente";
							break;
						case "14": $motivo .= "Registro em duplicidade";
							break;
						case "15": $motivo .= "Cnpj/cpf informado sem nome do sacador/avalista";
							break;
						case "19": $motivo .= "Valor do abatimento maior que 90% do valor do titulo";
							break;
						case "20": $motivo .= "Existe sustacao de protesto pendente para o titulo";
							break;
						case "21": $motivo .= "Titulo nao registrado no sistema";
							break;
						case "22": $motivo .= "Titulo baixado ou liquidado";
							break;
						case "23": $motivo .= "Instrucao nao aceita por ter sido emitido ultimo aviso ao sacado";
							break;
						case "24": $motivo .= "Instrucao incompativel - existe instrucao de protesto para o titulo";
							break;
						case "25": $motivo .= "Instrucao incompativel – nao existe instrucao de protesto para o titulo";
							break;
						case "26": $motivo .= "Instrucao nao aceita por ja ter sido emitida a ordem de protesto ao cartorio";
							break;
						case "27": $motivo .= "Instrucao nao aceita por nao ter sido emitida a ordem de protesto ao cartorio";
							break;
						case "28": $motivo .= "Ja existe uma mesma instrucao cadastrada anteriormente para o titulo";
							break;
						case "29": $motivo .= "Valor liquido + valor do abatimento diferente do valor do titulo registrado";
							break;
						case "30": $motivo .= "Existe uma instrucao de nao protestar ativa para o titulo";
							break;
						case "31": $motivo .= "Existe uma ocorrencia do sacado que bloqueia a instrucao";
							break;
						case "32": $motivo .= "Depositaria do titulo = 9999 ou carteira nao aceita protesto";
							break;
						case "33": $motivo .= "Alteracao de vencimento igual à registrada no sistema ou que torna o titulo vencido";
							break;
						case "34": $motivo .= "Instrucao de emissao de aviso de cobranca para titulo vencido antes do vencimento";
							break;
						case "35": $motivo .= "Solicitacao de cancelamento de instrucao inexistente";
							break;
						case "36": $motivo .= "Titulo sofrendo alteracao de controle (agencia/conta/carteira/nosso numero)";
							break;
						case "37": $motivo .= "Instrucao nao permitida para a carteira";
							break;
						case "38": $motivo .= "Instrucao nao permitida para titulo com rateio de credito";
							break;
					}
				}
			case "17":
				for($i = 0; $i < strlen($codigo_motivo); $i += 2){
					$subocorrencia = substr($codigo_motivo, $i, 2);
					switch($subocorrencia){
						case "02": $motivo .= "Agencia cobradora invalida ou com o mesmo conteudo";
							break;
						case "04": $motivo .= "Sigla do estado invalida";
							break;
						case "05": $motivo .= "Data de vencimento invalida ou com o mesmo conteudo";
							break;
						case "06": $motivo .= "Valor do titulo com outra alteracao simultânea";
							break;
						case "08": $motivo .= "Nome do sacado com o mesmo conteudo";
							break;
						case "09": $motivo .= "Agencia/conta incorreta";
							break;
						case "11": $motivo .= "Cep invalido";
							break;
						case "12": $motivo .= "Numero inscricao invalido do sacador avalista";
							break;
						case "13": $motivo .= "Seu numero com o mesmo conteudo";
							break;
						case "16": $motivo .= "Abatimento/alteracao do valor do titulo ou solicitacao de baixa bloqueada";
							break;
						case "20": $motivo .= "Especie invalida";
							break;
						case "21": $motivo .= "Agencia cobradora nao consta no cadastro de depositaria ou em encerramento";
							break;
						case "23": $motivo .= "Data de emissao do titulo invalida ou com mesmo conteudo";
							break;
						case "41": $motivo .= "Campo aceite invalido ou com mesmo conteudo";
							break;
						case "42": $motivo .= "Alteracao invalida para titulo vencido";
							break;
						case "43": $motivo .= "Alteracao bloqueada – vencimento ja alterado";
							break;
						case "53": $motivo .= "Instrucao com o mesmo conteudo";
							break;
						case "54": $motivo .= "Data vencimento para bancos correspondentes inferior ao aceito pelo banco";
							break;
						case "55": $motivo .= "Alteracoes iguais para o mesmo controle (agencia/conta/carteira/nosso numero)";
							break;
						case "56": $motivo .= "Cnpj/cpf invalido nao numerico ou zerado";
							break;
						case "57": $motivo .= "Prazo de vencimento inferior a 15 dias";
							break;
						case "60": $motivo .= "Valor de iof – alteracao nao permitida para carteiras de n.s. – moeda variavel";
							break;
						case "61": $motivo .= "Titulo ja baixado ou liquidado ou nao existe titulo correspondente no sistema";
							break;
						case "66": $motivo .= "Alteracao nao permitida para carteiras de notas de seguros – moeda variavel";
							break;
						case "67": $motivo .= "Nome invalido do sacador avalista";
							break;
						case "72": $motivo .= "Endereco invalido – sacador avalista";
							break;
						case "73": $motivo .= "Bairro invalido – sacador avalista";
							break;
						case "74": $motivo .= "Cidade invalida – sacador avalista";
							break;
						case "75": $motivo .= "Sigla estado invalido – sacador avalista";
							break;
						case "76": $motivo .= "Cep invalido – sacador avalista";
							break;
						case "81": $motivo .= "Alteracao bloqueada – titulo com protesto";
							break;
						case "87": $motivo .= "Alteracao bloqueada – titulo com rateio de credito";
							break;
					}
				}
			case "18":
				for($i = 0; $i < strlen($codigo_motivo); $i += 2){
					$subocorrencia = substr($codigo_motivo, $i, 2);
					switch($subocorrencia){
						case "16":
							$motivo .= "Abatimento/alteracao do valor do titulo ou solicitacao de baixa bloqueados";
							break;
						case "40":
							$motivo .= "Nao aprovada devido ao impacto na elegibilidade de garantias";
							break;
						case "41":
							$motivo .= "Automaticamente rejeitada";
							break;
						case "42":
							$motivo .= "Confirma recebimento de instrucao - pendente de analise";
							break;
					}
				}
			case "24":
			case "25":
			case "57":
			case "69":
				$motivo = "Motivo sem definicao";
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

			// Verifica se e um arquivo valido
			$linha = $arr_linha[0];
			if(strlen($linha) != 240 || substr($linha, 0, 3) != "341"){
				continue;
			}

			foreach($arr_linha as $linha){
				// Verifica se e linha detalhe
				if(substr($linha, 7, 1) == "3"){

					$dados_retorno = new stdClass();
					$dados_retorno = $this->getdadosliquidacao($linha);

					if(isset($dados_retorno->codlancto)){
						// Captura as ocorrencias do lancamento
						$arr_ocorrencia = array();
						for($j = 0; $j < 10; ($j = $j + 2)){
							$ocorrencia = trim(substr($dados_retorno->ocorrencias, $j, 2));
							if(strlen($ocorrencia) > 0){
								if($this->refatorado){
									$arr_ocorrencia[] = array($ocorrencia, $this->ocorrenciapagamento($ocorrencia));
								}else{
									$arr_ocorrencia[] = array($ocorrencia, $this->ocorrencia_pagamento($ocorrencia));
								}
							}
						}
						$liquidado = (substr($dados_retorno->ocorrencias, 0, 2) == "00");
						$arr_retorno[] = array(
							"codlancto" => $dados_retorno->codlancto,
							"valorparcela" => $dados_retorno->valorparcela,
							"valorpago" => ($liquidado ? $dados_retorno->valorparcela : 0),
							"liquidado" => $liquidado,
							"dtliquid" => $dados_retorno->dtliquid,
							"codocorrencia" => $arr_ocorrencia[0][0],
							"ocorrencia" => $arr_ocorrencia[0][1]
						);
					}
				}
			}
		}
		if(sizeof($arr_retorno) > 0){
			$arr_retorno = aasort($arr_retorno, "codocorrencia");
			return parent::processar_retorno($arr_retorno);
		}else{
			return TRUE;
		}
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

			// Verifica se e um arquivo valido
			$linha = $arr_linha[0];
			if(strlen($linha) != 240 || substr($linha, 0, 3) != "341"){
				continue;
			}

			foreach($arr_linha as $linha){
				// Verifica se e linha detalhe
				if(substr($linha, 7, 1) == "3"){
					switch(substr($linha, 13, 1)){
						case "J": // Liquidacao de titulos (boletos)
							$codlancto = (int) substr($linha, 182, 20);
							$ocorrencias = substr($linha, 230, 10);
							$dtliquid = substr($linha, 144, 8);
							$valorparcela = substr($linha, 157, 10) / 100;
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
								case "01":
									$dtliquid = substr($linha, 99, 8);
									break;
								case "02":
									$dtliquid = substr($linha, 127, 8);
									break;
								case "03":
									$dtliquid = substr($linha, 127, 8);
									break;
								case "04":
									$dtliquid = substr($linha, 141, 8);
									break;
								case "05":
									$dtliquid = substr($linha, 146, 8);
									break;
								case "07":
									$dtliquid = substr($linha, 116, 8);
									break;
								case "08":
									$dtliquid = substr($linha, 116, 8);
									break;
								case "11":
									$dtliquid = substr($linha, 143, 8);
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
								$arr_ocorrencia[] = array($ocorrencia, $this->ocorrencia_pagamento($ocorrencia));
							}
						}
						$liquidado = (substr($ocorrencias, 0, 2) == "00");
						$arr_retorno[] = array(
							"codlancto" => $codlancto,
							"valorparcela" => $valorparcela,
							"valorpago" => ($liquidado ? $valorparcela : 0),
							"liquidado" => $liquidado,
							"dtliquid" => $dtliquid,
							"codocorrencia" => $arr_ocorrencia[0][0],
							"ocorrencia" => $arr_ocorrencia[0][1]
						);
					}
				}
			}
		}
		if(sizeof($arr_retorno) > 0){
			$arr_retorno = aasort($arr_retorno, "codocorrencia");
			return parent::processar_retorno($arr_retorno);
		}else{
			return TRUE;
		}
	}
	 *
	 */

	function processar_retorno_recebimento($arr_arquivo){
		if(!is_array($arr_arquivo)){
			$arr_arquivo = array($arr_arquivo);
		}

		$arr_retorno = array();

		foreach($arr_arquivo as $i => $arquivo){
			setprogress((($i + 1) / sizeof($arr_arquivo) * 100), "Carregando arquivos: ".($i + 1)." de ".sizeof($arr_arquivo));
			$arr_linha = read_file($arquivo);
			$linha_header = array_shift($arr_linha);
			array_pop($arr_linha);

			// Verifica se a primeira linha do arquivo e de um arquivo de retorno e se foi gerado pelo banco itau
			$retorno = strtoupper(substr($linha_header, 2, 7));
			$banco = substr($linha_header, 76, 3);
			$dtliquid = convert_date(substr($linha_header, 113, 6), "dmy", "Y-m-d");
			$tiposervico = substr($linha_header, 9, 2);
			if($retorno != "RETORNO" || $banco != "341"){
				continue;
				//$_SESSION["ERROR"] = "O arquivo ".$arquivo." n&atilde;o &eacute; um arquivo de retorno v&aacute;lido para o banco itau.";
				//return FALSE;
			}

			foreach($arr_linha as $linha){
				$registro = substr($linha, 0, 1);
				switch($registro){
					case "1":
						if($tiposervico == "01"){
							$codlancto = trim(ltrim(substr($linha, 37, 25), "0"));
						}else{
							$codlancto = trim(ltrim(substr($linha, 37, 22), "0"));
						}
						$nossonumero = substr($linha, 62, 8);
						$codocorrencia = substr($linha, 108, 2);
						$dtocorrencia = convert_date(substr($linha, 110, 6), "dmy", "Y-m-d");
						$dtvencto = convert_date(substr($linha, 146, 6), "dmy", "Y-m-d");
						$dtcreditocc = convert_date(substr($linha, 295, 6), "dmy", "Y-m-d");
						$valorparcela = substr($linha, 152, 13) / 100;
						$valorpago = substr($linha, 253, 13) / 100;
						$valorjuros = substr($linha, 266, 13) / 100;
						$codliquidacao = substr($linha, 392,2);
						$statusliquidacao = (in_array($codliquidacao, array("AA",
																			"AO",
																			"BC",
																			"BF",
																			"BL",
																			"CI",
																			"CK",
																			"CP",
																			"DG",
																			"EA",
																			"Q0",
																			"RA",
																			"ST")) ? "D" : "C");

						$ocorrencia = $this->ocorrencia_recebimento($codocorrencia);
						$motivoocorrencia = $this->getmotivo($codocorrencia, substr($linha, 377, 8));

						$arr_retorno[] = array(
							"codlancto" => $codlancto,
							"nossonumero" => $nossonumero,
							"liquidado" => in_array($codocorrencia, array("06", "08")),
							"valorparcela" => $valorparcela,
							"valorjuros" => $valorjuros,
							"valorpago" => ($valorpago > 0 ? ($valorparcela + $valorjuros) : 0), // Tratamento para ajustar um problema que acontecia quando o valor pago´eh menor que o valor da parcela (o valor vem menor porque desconta a taxa do banco)
							"dtvencto" => $dtvencto,
							"dtliquid" => $dtliquid,
							"ocorrencia" => $ocorrencia,
							"motivoocorrencia" => $motivoocorrencia,
							"dtocorrencia" => $dtocorrencia,
							"dtcreditocc" => $dtcreditocc,
							"statusliquidacao" => $statusliquidacao
						);
						break;
					case "9":
						$arr_retorno["refavisobancario"] = trim(substr($linha, 199, 8));
						break;
				}
			}
		}
		return parent::processar_retorno($arr_retorno);
	}

	private function tipo_pagamento($lancamento){
		if(in_array($lancamento->getcodtipoaceite(), array(1, 2, 3))){
			return "20";
		}else{
			return "22";
		}
	}

	function ocorrencia(){
		switch($this->ocorrencia){
			case "C": return "02";
			case "N": return "01";
			case "G": return "01";
		}
	}

}