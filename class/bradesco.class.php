<?php
require_once("websac/require_file.php");
require_file("class/interfacebancaria.class.php");

class Bradesco extends InterfaceBancaria{
	private $arr_linha;
	protected $arr_remessa = array();
	private $numcheque;

    function gerar_remessa_pagamento($layoutArquivo){
        if(!parent::gerar_remessa()){
            return FALSE;
        }

        $this->banco->setseqremessa($this->banco->getseqremessa() + 1);
        if(!$this->banco->save()){
            return FALSE;
        }

		// Array que contem todas a linha do arquivo 
		$arr_linha = array();

		if($layoutArquivo == "240"){

			$arr_lancamento = $this->carregarauxiliares();

			$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);

			$arr_linha[] = $this->gerar_header_arquivo_240($this->banco->getseqremessa(), $versaolayout, $tipo_lancamento);

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
		}else{

			$seqregistro = 1; // Numero sequencial de registro (incrementa a cada registro)

			/* ************************************************
			R E G I S T R O   H E A D E R   D O   A R Q U I V O
			************************************************ */
			$linha  = "0"; // Identificacao do registro
			$linha .= $this->formatar_inteiro($this->banco->getcodigoempresa(),8); // Codigo de comunicacao (identificacao da empresa no banco)
			$linha .= "2"; // Tipo de inscricao da empresa pagadora (1 = CPF; 2 = CNPJ; 3 = outros)
			$linha .= $this->formatar_inteiro(removeformat($this->estabelecimento->getcpfcnpj()),15); // Numero da inscricao (CNPJ)
			$linha .= $this->formatar_texto(removespecial($this->estabelecimento->getrazaosocial()),40); // Nome da empresa pagadora (razao social)
			$linha .= "20"; // Tipo de servico (20 = pagamento de fornecedores)
			$linha .= "1"; // Codigo de origem do arquivo (1 = origem no cliente; 2 = origem no banco)
			$linha .= $this->formatar_inteiro($this->banco->getseqremessa(),5); // Numero da remessa (sequencial crescente)
			$linha .= $this->formatar_inteiro(0,5); // Numero do retorno
			$linha .= $this->formatar_data(date("Y-m-d")); // Data da gravacao do arquivo
			$linha .= $this->formatar_hora(date("H:i:s")); // Hora da gravacao do arquivo
			$linha .= $this->formatar_texto("",5); // Densidade de gravacao do arquivo/fita
			$linha .= $this->formatar_texto("",3); // Unidade densidade de gravacao do arquivo/fita
			$linha .= $this->formatar_texto("",5); // Identificacao modulo micro
			$linha .= "0"; // Tipo de processamento (apenas para retorno)
			$linha .= $this->formatar_texto("",74); // Para uso da empresa
			$linha .= $this->formatar_texto("",80); // Reservado
			$linha .= $this->formatar_texto("",217); // Reservado
			$linha .= $this->formatar_inteiro("",9); // Numero da lista de debito
			$linha .= $this->formatar_texto("",8); // Reservado
			$linha .= $this->formatar_inteiro($seqregistro++,6); // Numero sequencial do registro
			$arr_linha[] = $linha;

			$total_valorliquido = 0;

			foreach($this->arr_lancamento as $lancamento){
				if($lancamento->gettipoparceiro() != "F"){
					continue;
				}
				$fornecedor = objectbytable("fornecedor",$lancamento->getcodparceiro(),$this->con);
				$banco = objectbytable("banco",$lancamento->getcodbanco(),$this->con);
				/* ********************************
				R E G I S T R O   T R A N S A C A O
				******************************** */
				$linha  = "1"; // Identificacao do registro
				$linha .= ($fornecedor->gettppessoa() == "F" ? "1" : "2"); // Tipo de inscricao (1 = CPF; 2 = CNPJ; 3 = outros)
				$linha .= $this->formatar_inteiro(removeformat($fornecedor->getcpfcnpj()),15); // Numero de inscricao do fornecedor
				$linha .= $this->formatar_texto($fornecedor->getrazaosocial(),30); // Razao social do fornecedor
				$linha .= $this->formatar_texto($fornecedor->getendereco().", ".$fornecedor->getnumero(),40); // Endereco do fornecedor
				$linha .= $this->formatar_inteiro(removeformat($fornecedor->getcep()),8); // CEP do fornecedor
				$linha .= $this->formatar_inteiro($banco->getcodoficial(),3); // Codigo do banco do fornecedor
				$linha .= $this->formatar_inteiro($lancamento->getagenciacedente(),5); // Codigo da agencia do fornecedor
				$linha .= $this->formatar_inteiro(NULL,1); // Digito da agencia do fornecedor
				$linha .= $this->formatar_inteiro(substr($lancamento->getcontacedente(),0,strlen($lancamento->getcontacedente()) - 1),13); // Conta corrente do fornecedor
				$linha .= $this->formatar_inteiro(substr($lancamento->getcontacedente(),-1),2); // Digito da conta corrente do fornecedor
				$linha .= $this->formatar_texto(NULL,16); // Numero do pagamento
				$linha .= $this->formatar_inteiro(($banco->getcodoficial() == "237" ? substr(removeformat($lancamento->getcodbarras()),8,1).substr(removeformat($lancamento->getcodbarras()),10) : NULL),3); // Carteira
				$linha .= $this->formatar_inteiro($lancamento->getnossonumero(),12); // Nosso numero
				$linha .= $this->formatar_inteiro($lancamento->getcodlancto(),15); // Seu numero
				$linha .= $this->formatar_data($lancamento->getdtvencto()); // Data de vencimento
				$linha .= $this->formatar_data($lancamento->getdtemissao()); // Data de emissao
				$linha .= $this->formatar_data(NULL); // Data limite para desconto
				$linha .= $this->formatar_inteiro("0",1); // Zero
				$linha .= $this->formatar_decimal(0,4,2); // Fator de vencimento
				$linha .= $this->formatar_decimal($lancamento->getvalorparcela(),10,2); // Valor do documento
				$linha .= $this->formatar_decimal($lancamento->getvalorliquido(),15,2); // Valor do pagamento
				$linha .= $this->formatar_decimal($lancamento->getvalordescto(),15,2); // Valor do desconto
				$linha .= $this->formatar_decimal($lancamento->getvaloracresc(),15,2); // Valor do acrescimo
				$linha .= "01"; // Tipo de documento (01 = nota fiscal/fatura; 02 = fatura; 03 = nota fiscal; 04 = duplicata; 05 = outros)
				$linha .= "0"; // Obrigatório – fixo “zero” (0)
				$linha .= (strlen($lancamento->getnumnotafis()) > 0) ? $this->formatar_decimal($lancamento->getnumnotafis(),9) : 0; // Numero da nota fiscal / fatura duplicata
				$linha .= $this->formatar_texto(trim($lancamento->getserie()),2); // Serie do documento
				$linha .= "31"; // Modalidade do pagamento (31 = titulos terceiros)
				$linha .= $this->formatar_data(date("Y-m-d")); // Data para efetivacao do pagamento (quando em branco, o sistema assume a data de vencimento para pagamento)
				$linha .= $this->formatar_inteiro(9,3); // Codigo da moeda
				$linha .= "01"; // Situacao do agendamento
				$linha .= $this->formatar_texto(NULL,10); // Informacao de retorno
				$linha .= "0"; // Tipo de movimento (0 = inclusao; 5 = alteracao; 9 = exclusao)
				$linha .= "00"; // Codigo do movimento (00 = autoriza agendamento; 25 = desautoriza agendamento; 50 = efetuar alegacao)
				$linha .= $this->formatar_texto(NULL,4); // Horario para colsulta de saldo para as modalidades real time
				$linha .= $this->formatar_texto(NULL,15); // Saldo disponivel no momento da consulta (somente para arquivo retorno)
				$linha .= $this->formatar_texto(NULL,15); // Valor da taxa pre funding (somente para arquivo retorno)
				$linha .= $this->formatar_texto(NULL,6); // Reservado
				$linha .= $this->formatar_texto(NULL,40); // Sacador/avalista (somente para titulos em cobranca)
				$linha .= $this->formatar_texto(NULL,1); // Reservado
				$linha .= $this->formatar_texto(NULL,1); // Nivel da informacao de retorno
				$linha .= $this->formatar_texto(NULL,40); // Informacoes complementares
				$linha .= $this->formatar_inteiro(NULL,2); // Codigo de area da empresa (uso da empresa)
				$linha .= $this->formatar_texto(NULL,35); // Campo para uso da empresa
				$linha .= $this->formatar_texto(NULL,22); // Reservado
				$linha .= $this->formatar_inteiro(NULL,5); // Codigo de lancamento no extrato de conta corrente
				$linha .= $this->formatar_texto(NULL,1); // Reservado
				$linha .= "1"; // Tipo de conta do fornecedor (1 = credito em conta corrente; 2 = credito em conta poupanca)
				$linha .= $this->formatar_inteiro(NULL,7); // Conta complementar
				$linha .= $this->formatar_texto(NULL,8); // Reservado
				$linha .= $this->formatar_inteiro($seqregistro++,6); // Numero sequencial do registro
				$arr_linha[] = $linha;

				$total_valorliquido += $lancamento->getvalorliquido();

				$this->arr_remessa[] = array(
					"chave"      => $lancamento->getcodlancto(),
					"favorecido" => $lancamento->getfavorecido(),
					"dtemissao"  => $lancamento->getdtemissao(),
					"dtvencto"   => $lancamento->getdtvencto(),
					"valor"      => $lancamento->getvalorparcela(),
					"numnotafis" => $lancamento->getnumnotafis(),
					"numcheque"  => $this->numcheque
				);
				$procfina = $lancamento->getprocessogr();
				if($this->ocorrencia == "N" && !$this->remessa_anterior){
					$lancamento->setseqremessa($this->banco->getseqremessa());
					$lancamento->setprocessogr($this->controleprocfinan->getcodcontrprocfinan());
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
					$lancamento->setcodocorrencia("01");
					$lancamento->setocorrencia(utf8_encode($this->ocorrencia_recebimento("01")));
					$lancamento->setmotivoocorrencia("Cancelada a Geracao de Remessa");
					$lancamento->setdtremessa(NULL);
				}
				if(!$lancamento->save()){
					return FALSE;
				}

			}

			/* ****************************************************
			R E G I S T R O   T R A I L L E R   D O   A R Q U I V O
			**************************************************** */
			$linha  = "9"; // Identificacao do registro
			$linha .= $this->formatar_inteiro(($seqregistro),6); // Quantidade de registros
			$linha .= $this->formatar_decimal($total_valorliquido,17,2); // Total dos valores de pagamento
			$linha .= $this->formatar_texto("",470); // Reservado
			$linha .= $this->formatar_inteiro($seqregistro,6)."\r\n"; // Numero sequencial do registro
			$arr_linha[] = $linha;

			// Gera relatorio de remessa na pasta temp
			$controleprocfinan = is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : "cancelado_$procfina";
			$paramarqremessa = array("numremessa" => $controleprocfinan,"banco" => $this->banco->getcodoficial(),"nomebanco" => $this->banco->getnome());

			parent::relatorio_remessa($this->arr_remessa,$paramarqremessa);
			echo write_file($this->estabelecimento->getdirremessabanco().$this->formatar_inteiro($controleprocfinan,6).".".$this->banco->getcodoficial(),$arr_linha,(param("SISTEMA","TIPOSERVIDOR",$this->con) == "0"), "w+", true);
		}
		return TRUE;
    }

	function gerar_remessa_recebimento($layoutArquivo){
        if(parent::gerar_remessa()){
            $this->con->start_transaction();
            if(!$this->linha_header()){
                $this->con->rollback();
                return FALSE;
            }
            $item = 2;
            foreach($this->arr_lancamento as $lancamento){
                if(!$this->linha_detalhe($lancamento,$item++,(is_object($this->controleprocfinan) ? $this->controleprocfinan->getcodcontrprocfinan() : NULL))){
                    $this->con->rollback();
                    return FALSE;
                }
            }

            $this->linha_trailler($item);
            if(!$this->banco->save()){
                $this->con->rollback();
                return FALSE;
            }
			$paramarqremessa = array("numremessa" => $this->controleprocfinan->getcodcontrprocfinan(),"banco" => $this->banco->getcodoficial(),"nomebanco" => str_replace(" ","",$this->banco->getnome()));
			parent::relatorio_remessa($this->arr_remessa,$paramarqremessa);

            echo write_file($this->estabelecimento->getdirremessabanco()."CB".date("dm").$this->banco->getnrultremessa().".rem",$this->arr_linha,(param("SISTEMA","TIPOSERVIDOR",$this->con) == "0"), "w+", true);

			$this->con->commit();
            return TRUE;
        }else{
            return FALSE;
        }
	}

	private function linha_header(){
		if((!$this->remessa_anterior) || ($this->remessa_anterior && $this->ocorrencia == "02")){
			$this->banco->setseqremessa($this->banco->getseqremessa()+1);
			if(!$this->banco->save()){
				return FALSE;
			}
		}
		$linha =  "0"; // Identificacao do registro
		$linha .= "1"; // Idetificacoa do arquivo de remessa
		$linha .= "REMESSA"; // Lieral "REMESSA"
		$linha .= "01"; // Codigo do servico
		$linha .= $this->formatar_texto("COBRANCA",15); // Literal servico
		$linha .= $this->formatar_inteiro($this->banco->getcodigoempresa(),20); // Codigo da empresa
		$linha .= $this->formatar_texto((strlen($this->banco->getnomecedente()) > 0 ? $this->banco->getnomecedente() : removespecial($this->estabelecimento->getrazaosocial())),30); // Nome da empresa
		$linha .= "237"; // Numero do bradesco na camara de compensacao
		$linha .= $this->formatar_texto("BRADESCO",15); // Nome do banco
		$linha .= date("dmy"); // Data de gravacao do arquivo
		$linha .= str_repeat(" ",8); // Espacos em branco
		$linha .= "MX"; // Identificacao do sistema
		$linha .= $this->formatar_inteiro($this->banco->getseqremessa(),7); // Sequencial da remessa
		$linha .= str_repeat(" ",277); // Espacos em branco
		$linha .= "000001"; // Sequencial do registro
		$this->arr_linha[] = $linha;
		return TRUE;
	}

	private function linha_detalhe($lancamento,$index,$nrprocesso,$tipo = 1){
		switch($lancamento->gettipoparceiro()){
			case "C": // Cliente
				$parceiro = objectbytable("cliente", $lancamento->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidaderes(), $this->con);
				$pais_parceiro = objectbytable("pais", $parceiro->getcodpaisres(), $this->con);
				break;
			case "E": // Estabelecimento
				$parceiro = objectbytable("estabelecimento", $lancamento->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
				$pais_parceiro = objectbytable("pais", "01058", $this->con);
				break;
			case "F": // Fornecedor
				$parceiro = objectbytable("fornecedor", $lancamento->getcodparceiro(), $this->con);
				$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
				$pais_parceiro = objectbytable("pais", $parceiro->getcodpais(), $this->con);
				break;
			default:
				$_SESSION["ERROR"] = "Tipo de parceiro (".$lancamento->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o de nota fiscal eletr&ocirc;nica.";
				return FALSE;
		}
		//$cliente = objectbytable("cliente",$lancamento->getcodparceiro(),$this->con);
		$cliente = $parceiro;
        switch($tipo){
            case 1:
                $linha = "1"; // Identificao do registro
                if($lancamento->gettipoparceiro() == "C" && $this->banco->getboletodebauto() == "S" && $cliente->getpermitedebauto() == "S"){ // Se o emitente tiver convenio para debito automatico na conta e cliente permite o debito em conta
                    $linha .= $this->formatar_inteiro($cliente->getagencia1(),5); // Agencia de debito (opcional)
                    $linha .= $cliente->getddigitoagencia(); // Digito de controle da agencia
                    $linha .= $this->formatar_decimal($cliente->getrazaoconta(),5); // Razao da conta
                    $linha .= $this->formatar_inteiro($cliente->getcontacor1(),7); // Conta corrente
                    $linha .= $this->formatar_inteiro($cliente->getdigitoconta(),1); // Digito de controle da conta
                }else{
                    $linha .= $this->formatar_texto(" ",5); // Agencia de debito (opcional)
                    $linha .= " "; // Digito de controle da agencia
                    $linha .= $this->formatar_texto(" ",5); // Razao da conta
                    $linha .= $this->formatar_texto(" ",7); // Conta corrente
                    $linha .= " "; // Digito de controle da conta
                }
                $linha .= "0"; // Identificacao da empresa cendente no banco - "0"
                $linha .= $this->formatar_inteiro($this->banco->getcarteira(),3); // Identificacao da empresa cendente no banco - codigo da carteira
                $linha .= $this->formatar_inteiro($this->banco->getagencia(),5); // Identificacao da empresa cendente no banco - codigo da agencia sem digito
                $linha .= $this->formatar_inteiro($this->banco->getconta(),7); // Identificacao da empresa cendente no banco - numero da conta corrente sem digito
                $linha .= $this->formatar_inteiro($this->banco->getdigito(),1); // Identificacao da empresa cendente no banco - digito da conta corrente
                $linha .= $this->formatar_texto($lancamento->getcodlancto(),25); // Numero de controle do cedente (participante)
                $linha .= ($this->banco->getboletodebauto() == "S" ? "237" :"000"); // Codigo do banco para debito automatico - "237" e se nao for debito automatico "000"
                $linha .= ($this->banco->getpercmulta() > 0 ? "2" : "0"); // Considera multa por atraso
                $linha .= ($this->banco->getpercmulta() > 0 ? $this->formatar_decimal($this->banco->getpercmulta(),4,2) : "0000"); // Percentual de multa
                $nosso_numero = "00000000000";
                $dac_nosso_numero = "0";
                if($this->banco->getbancoemiteboleto() == "N"){
                    if(strlen($lancamento->getnossonumero()) == 0){
                        $this->banco->setnossonumero($this->banco->getnossonumero() + 1);
                        $nosso_numero = trim($this->banco->getnossonumero());
                        $nosso_numero = $this->formatar_inteiro($nosso_numero,11);
                        $dac_nosso_numero = dac_nosso_numero($this->banco->getcodoficial(),$nosso_numero,$this->banco->getcarteira()); // Digito verificado nosso numero
                    }else{
                        $nosso_numero = $this->formatar_inteiro($lancamento->getnossonumero(),12);
                        $dac_nosso_numero = "";
                    }
                }
                $linha .= $nosso_numero;
                $linha .= $dac_nosso_numero;
                $linha .= str_repeat("0",10); // Desconto bonificado por dia
                $linha .= ($this->banco->getbancoemiteboleto() == "S" ? "1" : "2"); // Condicao para geracao da papeleta de cobranca
                $linha .= ($lancamento->gettipoparceiro() == "C" && $this->banco->getboletodebauto() == "S" && $cliente->getpermitedebauto() == "S" ? "S" : "N"); // Identificacao de emissao do boleto com debito automatico
                $linha .= str_repeat(" ",10); // Identificacao da operacao do banco (informar branco)
                $linha .= " "; // Identificacao de rateio de credito
                $linha .= "2"; // Indicador de geracao de aviso de debito automatico
                $linha .= "  "; // Branco
                $linha .= $this->ocorrencia(); // Codigo da ocorrrencia
                if($lancamento->gettotalparcelas() == 1){
					$linha .= $this->formatar_inteiro($lancamento->getnumnotafis(),10);  // Numero do documento
				}else{
					$linha .= $this->formatar_inteiro($lancamento->getnumnotafis(),7)."/".$this->formatar_inteiro($lancamento->getparcela(),2); // Numero do documento
				}
                $linha .= convert_date($lancamento->getdtvencto(TRUE),"d/m/Y","dmy"); // Data
                $linha .= $this->formatar_decimal($lancamento->getvalorliquido(),13,2); // Valor do titulo
                $linha .= "000"; // Banco encarregado da cobranca (preencher com zeros)
                $linha .= "00000"; // Agencia depositaria (preencher com zeros)
                $linha .= "01"; // Especie do titulo (01-duplicata"
                $linha .= "N"; // Identificacao (sempre N)
                $linha .= convert_date($lancamento->getdtemissao(TRUE),"d/m/Y","dmy"); // Data de emissao do titulo
                if($this->banco->getdiasprotesto() > 0){
                    $linha .= "06";	// Primeira instrucao
                    $linha .= $this->formatar_inteiro($this->banco->getdiasprotesto(),2); // Segunda instrucao
                }else{
                    $linha .= "00"; // Primeira instrucao
                    $linha .= "00"; // Segunda instrucao
                }
                $linha .= $this->formatar_decimal(($lancamento->getvalorliquido() * $this->banco->getvalormoradiaria()) / 30,13,0);	// Valor de juro de mora diaria
                $linha .= str_repeat(" ",6);	// Data limite para concesao de desconto
                $linha .= "0000000000000";		// Valor do desconto
                $linha .= "0000000000000";		// Valor od IOF
                $linha .= "0000000000000";		// Valor do abatimento a ser concedido ou cancelado
                $linha .= ($lancamento->gettipoparceiro() != "L" && $cliente->gettppessoa() == "F" ? "01" : "02"); // Tipo de inscricao do sacado
                $linha .= $this->formatar_inteiro(removeformat($cliente->getcpfcnpj()),"14"); // Numero da inscricao do sacado
                $linha .= $this->formatar_texto($cliente->getrazaosocial(),40); // Nome do sacado
				if($lancamento->gettipoparceiro() == "C"){
					$linha .= $this->formatar_texto(trim(substr($cliente->getenderres(),0,30).", ".$this->formatar_texto(trim($cliente->getnumerores()),8)),40); // Endereco do sacado
				}else{
					$linha .= $this->formatar_texto(trim(substr($cliente->getendereco(),0,30).", ".$this->formatar_texto(trim($cliente->getnumero()),8)),40); // Endereco do sacado
				}
                $linha .= $this->formatar_texto(" ",12); // 1ª mensagem
				if($lancamento->gettipoparceiro() == "C"){
					$linha .= $this->formatar_texto($cliente->getcepfat(),5); // Cep do sacado
					$linha .= $this->formatar_texto($cliente->getcepfat(),3,6); // Sufixo do cep
				}else{
					$linha .= $this->formatar_texto($cliente->getcep(),5); // Cep do sacado
					$linha .= $this->formatar_texto($cliente->getcep(),3,6); // Sufixo do cep
				}
                $linha .= $this->formatar_texto($this->banco->getsacadoravalista(),60); // Sacador/Avalista 2ª mensagem
                $linha .= $this->formatar_inteiro($index,6); // Sequencial do registro
                break;
            case 2:
                // Nao sera implementado neste momento
                break;
            case 3:
                // Nao sera implementado neste momento
                break;
            case 4:
                // Nao sera implementado neste momento
                break;
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
		$this->arr_remessa[] = array(
			"chave"      => $lancamento->getcodlancto(),
			"numnotafis" => $lancamento->getnumnotafis(),
			"favorecido" => $lancamento->getfavorecido(),
			"dtemissao"  => $lancamento->getdtemissao(),
			"dtvencto"   => $lancamento->getdtvencto(),
			"valor"      => $lancamento->getvalorparcela(),
			"numcheque"  => $this->numcheque
		);
		$this->arr_linha[] = $linha;
        return TRUE;
	}

	function linha_trailler($total_registros){
		$linha  = "9"; // Identificacao do registro
		$linha .= $this->formatar_texto("",393); // Espacos em branco
		$linha .= $this->formatar_inteiro($total_registros,6); // Sequencial do registro
		$this->arr_linha[] = $linha;
	}

    function ocorrencia(){
        switch($this->ocorrencia){
            case "C": return "02"; break;
            case "N": return "01"; break;
            case "G": return "01"; break;
        }
    }

    function processar_retorno_recebimento($arr_arquivo){
        if(!is_array($arr_arquivo)){
            $arr_arquivo = array($arr_arquivo);
        }

        $arr_retorno = array();

        foreach($arr_arquivo as $i => $arquivo){
            setprogress((($i + 1) / sizeof($arr_arquivo) * 100),"Carregando arquivos: ".($i + 1)." de ".sizeof($arr_arquivo));
            $arr_linha = read_file($arquivo);
            $linha_header = array_shift($arr_linha);
            array_pop($arr_linha);

            // Verifica se a primeira linha do arquivo e de um arquivo de retorno e se foi gerado pelo banco bradesco
            $retorno = strtoupper(substr($linha_header,2,7));
            $banco = substr($linha_header,76,3);
            $dtliquid = convert_date(substr($linha_header,94,6),"dmy","Y-m-d");
            if($retorno != "RETORNO" || $banco != "237"){
                $_SESSION["ERROR"] = "O arquivo ".$arquivo." n&atilde;o &eacute; um arquivo de retorno v&aacute;lido para o banco bradesco.";
                return FALSE;
            }

            foreach($arr_linha as $linha){
                $registro = substr($linha,0,1);
                switch($registro){
                    case "1":
                        $codlancto = trim(ltrim(substr($linha,37,25),"0"));
                        $nossonumero = substr($linha,70,12);
                        $codocorrencia = substr($linha,108,2);
                        $dtocorrencia = convert_date(substr($linha,110,6),"dmy","Y-m-d");
                        $dtvencto = convert_date(substr($linha,146,6),"dmy","Y-m-d");
                        $valorparcela = substr($linha,152,13) / 100;
                        $valorpago = substr($linha,253,13) / 100;
                        $valorjuros = substr($linha,266,13) / 100;

                        $ocorrencia = $this->ocorrencia_recebimento($codocorrencia);
                        $motivoocorrencia = $this->getmotivo($codocorrencia,substr($linha,318,10));

                        $arr_retorno[] = array(
                            "codlancto" => $codlancto,
                            "nossonumero" => $nossonumero,
                            "liquidado" => in_array($codocorrencia,array("06","15","16","17")),
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
        return parent::processar_retorno($arr_retorno);
	}

    function processar_retorno_pagamento($arr_arquivo){
        if(!is_array($arr_arquivo)){
            $arr_arquivo = array($arr_arquivo);
        }

        $arr_retorno = array();

        $this->con->start_transaction();

        foreach($arr_arquivo as $arquivo){
            $arr_linha = read_file($arquivo);

			// Verifica se a primeira linha do arquivo e de um arquivo de retorno e se foi gerado pelo banco bradesco
			$linha_header = array_shift($arr_linha);
            array_pop($arr_linha);

			$cobranca = strtoupper(substr($linha_header,11,8));
			$banco = substr($linha_header,76,3);
			$dtliquid = convert_date(substr($linha_header,94,6),"dmy","Y-m-d");

			if($cobranca == "COBRANCA"){
				return TRUE;
			}

			if(substr($linha_header, 65, 2) != "20"){
				$_SESSION["ERROR"] = "O arquivo ".$arquivo." n&atilde;o &eacute; um arquivo de retorno v&aacute;lido do banco bradesco para o layout de 500 bytes.";
				return FALSE;
			}

            foreach($arr_linha as $linha){
                if(substr($linha,0,1) != "1"){
                    continue;
                }

                $codlancto = substr($linha,150,15);
				$dtliquid = convert_date(substr($linha,266,8),"Ymd","dmY");
				$codocorrencia = substr($linha,276,6);
				$ocorrencias = substr($linha,278,10);

                $arr_ocorrencia = array();
				$liquidado = FALSE;
				$motivoocorrencia = "";
				for($j = 0; $j < 10; ($j = $j + 2)){
					$ocorrencia = trim(substr($ocorrencias,$j,2));
					if(strlen($ocorrencia) > 0){
						if(!$liquidado){
							$liquidado = $ocorrencia == "BW";
						}
						$ocorrencia = $this->ocorrencia_pagamento($ocorrencia);
						if($liquidado){
							$motivoocorrencia = $ocorrencia;
						}
						$arr_ocorrencia[] = $ocorrencia;
					}
				}
                $arr_retorno[] = array(
                    "codlancto" => $codlancto,
                    "liquidado" => $liquidado,
                    "dtliquid"  => $dtliquid,
                    "ocorrencia" => $arr_ocorrencia,
                    "motivo" => NULL
                );
            }
        }
        return parent::processar_retorno($arr_retorno);
    }

    function ocorrencia_pagamento($codocorrencia){
        switch($codocorrencia){
            case "AA": return "Arquivo duplicado."; break;
            case "AB": return "Data limite para desconto, sem valor correspondente."; break;
            case "AC": return "Tipo de servico invalido."; break;
            case "AD": return "Modalidade de pagamento invalida."; break;
            case "AE": return "Tipo de inscricao e identificacao do cliente pagador incompativeis."; break;
            case "AF": return "Valores nao numericos ou zerados."; break;
            case "AG": return "Tipo de inscricao e identificacao do favorecido incompativel."; break;
            case "AJ": return "Tipo de movimento invalido."; break;
            case "AL": return "Banco, agencia ou conta invalido."; break;
            case "AM": return "Agencia do favorecido invalida."; break;
            case "AN": return "Conta corrente do favorecido invalida."; break;
            case "AO": return "Nome do favorecido nao informado."; break;
            case "AQ": return "Tipo de moeda invalido."; break;
            case "AT": return "CGC/CPF do favorecido invalido."; break;
            case "AU": return "Endereco do favorecido nao informado."; break;
            case "AX": return "CEP do favorecido invalido."; break;
            case "AY": return "Alteracao invalida; Banco anterior Bradesco."; break;
            case "AZ": return "Codigo de Banco do favorecido invalido."; break;
            case "BD": return "Pagamento agendado."; break;
            case "BE": return "Hora de gravacao invalida."; break;
            case "BF": return "Identificacao da empresa no Banco, invalida."; break;
            case "BG": return "CGC/CPF do pagador invalido."; break;
            case "BH": return "Tipo de inscricao do cliente favorecido invalido."; break;
            case "BI": return "Data de vencimento invalida ou nao preenchida."; break;
            case "BJ": return "Data de emissao do documento invalida."; break;
            case "BK": return "Tipo de inscricao do cliente favorecido nao permitido."; break;
            case "BL": return "Data limite para desconto invalida."; break;
            case "BM": return "Data para efetivacao do pagamento invalida."; break;
            case "BN": return "Data para efetivacao anterior a do processamento."; break;
            case "BO": return "Cliente nao cadastrado."; break;
            case "BP": return "Identificacao de Titulo Bradesco divergente da original."; break;
            case "BQ": return "Data do documento posterior ao vencimento."; break;
            case "BT": return "Desautorizacao efetuada."; break;
            case "BU": return "Alteracao efetuada."; break;
            case "BV": return "Exclusao efetuada."; break;
            case "BW": return "Pagamento efetuado."; break;
            case "FA": return "Codigo de origem invalido."; break;
            case "FB": return "Data de gravacao do arquivo invalida."; break;
            case "FC": return "Tipo de documento invalido."; break;
            case "FE": return "Numero de pagamento invalido."; break;
            case "FF": return "Valor do desconto sem data limite."; break;
            case "FG": return "Data limite para desconto posterior ao vencimento."; break;
            case "FH": return "Falta numero e/ou serie do documento."; break;
            case "FI": return "Exclusao de agendamento nao disponivel."; break;
            case "FJ": return "Soma dos valores nao confere."; break;
            case "FK": return "Falta valor de pagamento."; break;
            case "FL": return "Modalidade de pagamento invalida para o contrato."; break;
            case "FM": return "Codigo de movimento invalido."; break;
            case "FN": return "Tentativa de inclusao de registro existente."; break;
            case "FO": return "Tentativa de alteracao para registro inexistente."; break;
            case "FP": return "Tentativa de efetivacao de agendamento nao disponivel."; break;
            case "FQ": return "Tentativa de desautorizacao de agendamento nao disponivel."; break;
            case "FR": return "Autorizacao de agendamento sem data de efetivacao e sem data de vencimento."; break;
            case "FT": return "Tipo de inscricao do cliente pagador invalido."; break;
            case "FU": return "Contrato inexistente ou inativo."; break;
            case "FV": return "Cliente com convenio cancelado."; break;
            case "FW": return "Valor autorizado inferior ao original."; break;
            case "FX": return "Esta faltando registro header."; break;
            case "FZ": return "Valor autorizado nao confere para pagamento em atraso."; break;
            case "F0": return "Agendamento em atraso; nao permitido pelo convenio."; break;
            case "F1": return "Tentativa de Agendamento com Desc. Fora do Prazo."; break;
            case "F3": return "Tentativa de alteracao invalida; confirmacao de debito ja efetuada."; break;
            case "F4": return "Falta registro trailler."; break;
            case "F5": return "Valor do trailler nao confere."; break;
            case "F6": return "Quantidade de registros do trailler nao confere."; break;
            case "F7": return "Tentativa de alteracao invalida; pagamento ja enviado ao Bradesco Instantâneo."; break;
            case "F8": return "Pagamento enviado apos o horario estipulado."; break;
            case "F9": return "Tentativa de inclusao de registro existente em historico."; break;
            case "GA": return "Tipo de DOC/TED invalido."; break;
            case "GB": return "Numero do DOC/TED invalido."; break;
            case "GC": return "Finalidade do DOC/TED invalida ou inexistente."; break;
            case "GD": return "Conta corrente do favorecido encerrada / bloqueada."; break;
            case "GE": return "Conta corrente do favorecido nao recadastrada."; break;
            case "GF": return "Inclusao de pagamento via modalidade 30 nao permitida."; break;
            case "GG": return "Campo livre do codigo de barras (linha digitavel) invalido."; break;
            case "GH": return "Digito verificador do codigo de barras invalido."; break;
            case "GI": return "Codigo da moeda da linha digitavel invalido."; break;
            case "GJ": return "Conta poupanca do favorecido invalida."; break;
            case "GK": return "Conta poupanca do favorecido nao recadastrada."; break;
            case "GL": return "Conta poupanca do favorecido nao encontrada."; break;
            case "GM": return "Pagamento 3 (tres) dias apos o vencimento."; break;
            case "GN": return "Conta complementar invalida."; break;
            case "GO": return "Inclusao de DOC/TED para Banco 237 nao permitido."; break;
            case "GP": return "CGC/CPF do favorecido divergente do cadastro do Banco."; break;
            case "GQ": return "Tipo de DOC/TED nao permitido via sistema eletrônico."; break;
            case "GR": return "Alteracao invalida; pagamento ja enviado a agencia pagadora."; break;
            case "GS": return "Limite de pagamento excedido. Fale com o Gerente da sua agencia."; break;
            case "GT": return "Limite vencido/vencer em 30 dias."; break;
            case "GU": return "Pagamento agendado por aumento de limite ou reducao no total autorizado."; break;
            case "GV": return "Cheque OP estornado conforme seu pedido."; break;
            case "GW": return "Conta corrente ou conta poupanca com razao nao permitido para efetivacao de credito."; break;
            case "GX": return "Cheque OP com data limite vencida."; break;
            case "GY": return "Conta poupanca do favorecido encerrada / bloqueada."; break;
            case "GZ": return "Conta corrente encerrada / bloqueada."; break;
            case "HA": return "Agendado, debito sob consulta de saldo."; break;
            case "HB": return "Pagamento nao efetuado, saldo insuficiente."; break;
            case "HC": return "Pagamento nao efetuado, alem de saldo insuficiente, conta com cadastro no DVL."; break;
            case "HD": return "Pagamento nao efetuado, alem de saldo insuficiente, conta bloqueada."; break;
            case "HE": return "Data de Vencto/Pagto fora do prazo de operacao do banco."; break;
            case "HF": return "Processado e debitado."; break;
            case "HG": return "Processado e nao debitado por saldo insuficiente."; break;
            case "HI": return "Cheque OP Emitido nesta data."; break;
            case "JA": return "Codigo de lancamento invalido."; break;
            case "JB": return "DOC/TED/Titulos devolvidos e estornados."; break;
            case "JC": return "Modalidade alterada de 07/CIP, para 08/STR."; break;
            case "JD": return "Modalidade alterada de 07/CIP, para 03/DOC COMPE."; break;
            case "JE": return "Modalidade alterada de 08/STR para 07/CIP."; break;
            case "JF": return "Modalidade alterada de 08/STR para 03/COMPE."; break;
            case "JG": return "Alteracao de Modalidade Via Arquivo nao Permitido."; break;
            case "JH": return "Horario de Consulta de Saldo apos Encerramento Rotina."; break;
            case "JI": return "Modalidade alterada de 01/Credito em conta para 05/Credito em conta real time."; break;
            case "JJ": return "Horario de agendamento Invalido."; break;
            case "JK": return "Tipo de conta – modalidade DOC/TED - invalido."; break;
            case "JL": return "Titulo Agendado/Descontado."; break;
            case "JM": return "Alteracao nao Permitida, Titulo Antecipado/Descontado."; break;
            case "JN": return "Modalidade Alter. de 05/Credito em Conta Real Time Para 01/Credito em Conta."; break;
            case "JO": return "Exclusao nao Permitida Titulo Antecipado/Descontado."; break;
            case "JP": return "Pagamento com Limite TED Excedido. Fale com o Gerente da sua agencia para Autorizacao."; break;
            case "KO": return "Autorizacao para debito em conta."; break;
            case "KP": return "Cliente pagador nao cadastrado do PAGFOR."; break;
            case "KQ": return "Modalidade invalida para pagador em teste."; break;
            case "KR": return "Banco destinatario nao operante nesta data."; break;
            case "KS": return "Modalidade alterada de DOC. Para TED."; break;
            case "KT": return "Dt. Efetivacao alterada p/ proximo MOVTO. ** TRAG."; break;
            case "KV": return "CPF/CNPJ do investidor invalido ou inexistente."; break;
            case "KW": return "Tipo Inscricao Investidor Invalido ou inexistente."; break;
            case "KX": return "Nome do Investidor Inexistente."; break;
            case "KZ": return "Codigo do Investidor Inexistente."; break;
            case "LA": return "Agendado. Sob Lista de Debito."; break;
            case "LB": return "Pagamento nao autorizado sob Lista de Debito."; break;
            case "LC": return "Lista com mais de uma modalidade."; break;
            case "LD": return "Lista com mais de uma data de Pagamento."; break;
            case "LE": return "Numero de Lista Duplicado."; break;
            case "LF": return "Lista de Debito vencida e nao autorizada."; break;
            case "LG": return "Conta Salario nao permitida para este convenio."; break;
            case "LH": return "Codigo de Lancamento invalido para Conta Salario."; break;
            case "LI": return "Finalidade de DOC / TED invalido para Salario."; break;
            case "LJ": return "Conta Salario obrigatoria para este Codigo de Lancamento."; break;
            case "LK": return "Tipo de Conta do Favorecido Invalida."; break;
            case "LL": return "Nome do Favorecido Inconsistente."; break;
            case "LM": return "Numero de Lista de Debito Invalido."; break;
            case "MA": return "Tipo conta Invalida para finalidade."; break;
            case "MB": return "Conta Credito Investimento invalida/inexistente."; break;
            case "MC": return "Conta Debito Investimento Invalida/inexistente."; break;
            case "MD": return "Titularidade diferente para tipo de conta."; break;
            case "ME": return "Data de Pagamento Alterada devido a Feriado Local."; break;
            case "MF": return "Alegacao Efetuada."; break;
            case "MG": return "Alegacao Nao Efetuada. Motivo da Alegacao/Reconhecimento da Divida Inconsistente."; break;
            case "MH": return "Autorizacao Nao Efetuada. Codigo de Reconhecimento da divida nao permitido."; break;
            case "TR": return "Ag/ Conta do favorecido alterado por Transferencia de agencia."; break;
        }
    }

    function ocorrencia_recebimento($codocorrencia){
        switch($codocorrencia){
            case "00": return "Geracao Normal"; break;
            case "01": return "Geracao de Cancelamento"; break;
            case "02": return "Entrada Confirmada"; break;
            case "03": return "Entrada Rejeitada"; break;
            case "06": return "Liquidacao Normal"; break;
            case "09": return "Baixado Automaticamento Via Arquivo"; break;
            case "10": return "Baixado Conforme Instrucoes da Agencia"; break;
            case "11": return "Em Ser - Arquivo de Titulos Pendente"; break;
            case "12": return "Abatimento Concedido"; break;
            case "13": return "Abatimento Cancelado"; break;
            case "14": return "Vencimento Alterado"; break;
            case "15": return "Liquidacao em Cartorio"; break;
            case "16": return "Titulo Pago em Cheque - Vinculado"; break;
            case "17": return "Liquidacao Apos Baixa ou Titulo Nao Registrado"; break;
            case "18": return "Acerto de Agencia Depositaria"; break;
            case "19": return "Confirmacao de Receb. de Instrucoes de Protesto"; break;
            case "20": return "Confirmacao de Receb. de Instrucoes de Sustacao de Protesto"; break;
            case "21": return "Acerto do Numero de Controle do Participante"; break;
            case "22": return "Titulo com Pagamento Cancelado"; break;
            case "23": return "Entrada de Titulo em Cartorio"; break;
            case "24": return "Entrada Rejeitada por Cep Irregular"; break;
            case "25": return "Confirmacao Receb. Instrucao de Protesto Falimentar"; break;
            case "27": return "Baixa Rejeitada"; break;
            case "28": return "Debito de Tarifas/Custas"; break;
            case "29": return "Ocorrencia do Sacado"; break;
            case "30": return "Alteracao de Outros Dados Rejeitados"; break;
            case "32": return "Instrucao Rejeitada"; break;
            case "33": return "Confirmacao de Pedido de Alteracao de Outros Dados"; break;
            case "34": return "Retirada em Cartorio e Manutencao em Carteira"; break;
            case "35": return "Desagendamento do Debito Automatico"; break;
            case "40": return "Estorno de Pagamento"; break;
            case "55": return "Sustado Judicial"; break;
            case "68": return "Acerto dos Dados de Rateio de Credito"; break;
            case "69": return "Cancelamento dos Dados de Rateio de Credito"; break;
        }
    }

	/*-------------------------------------------------------------------------------------------------------
	Autor: Jesus
	Objetivo...........: Retornar os motivos conforme codigo de ocorrencia encontradas no arquivo de retorno
	Parametros.........:
		$ocorrencia....: codigo da ocorrencia principal encontrada no titulo
		$codigo_motivo.: lista de codigos de subocorrencias encontrada no titulo (pode conter ate 5 codigos
                         diferentes para uma mesma ocorrencia principal)
	--------------------------------------------------------------------------------------------------------*/
	function getmotivo($ocorrencia,$codigo_motivo){
		$motivo = "";
		switch($ocorrencia){
			case "02":
				$arr_ocorr02 = array();
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr($codigo_motivo,$i,2);
					if(in_array($subocorrencia,$arr_ocorr02)){
						continue;
					}
					switch($subocorrencia){
						case "00":
							$arr_ocorr02[$subocorrencia] = $subocorrencia;
							$motivo .= "Ocorrencia Aceita"; break;
						case "01": $motivo .= "Codigo do Banco Invalido"; break;
						case "04": $motivo .= "Codigo do Movimento nao Permitido para a Carteira"; break;
						case "15": $motivo .= "Caracteristicas de Cobranca Incompativeis"; break;
						case "17": $motivo .= "Data e Vencimento Anterior a Data de Emissao"; break;
						case "21": $motivo .= "Especie de Titulo Invalido"; break;
						case "24": $motivo .= "Data de Emissao Invalida"; break;
						case "27": $motivo .= "Valor/Taxa de Juros Mora Diaria Invalida"; break;
						case "38": $motivo .= "Prazo para Protesto Invalido"; break;
						case "45": $motivo .= "Nome do Sacado Invalido"; break;
						case "46": $motivo .= "Tipo/Numero de Inscricao do Sacado Invalido"; break;
						case "47": $motivo .= "Endereco do Sacado Invalido"; break;
						case "48": $motivo .= "Cep Invalido"; break;
						case "50": $motivo .= "Cep Referente a Banco Correspondente"; break;
						case "53": $motivo .= "Numero de Inscricao do Sacador/Avalista Invalidos"; break;
						case "54": $motivo .= "Sacador/Avalista nao Informado"; break;
						case "67": $motivo .= "Debito Automatico Agendado"; break;
						case "68": $motivo .= "Debito nao Agendado - Erros nos Dados da Remessa"; break;
						case "69": $motivo .= "Debito nao Agendado - Sacado nao Consta Cadastro de Autorizante"; break;
						case "70": $motivo .= "Debito nao Agendado - Cedente nao Autorizado pelo Sacado"; break;
						case "71": $motivo .= "Debito nao Agendado - Cedente nao Participa de Modalidade D&eacute;bito Automatico"; break;
						case "72": $motivo .= "Debito nao Agendado - C&oacute; de Moeda Diferente de R$"; break;
						case "73": $motivo .= "Debito nao Agendado - Data de Vencimento Invalida/Vencida"; break;
						case "75": $motivo .= "Debito nao Agendado - Tipo de Numero de Inscricao do Sacado Debitado Invalido"; break;
						case "76": $motivo .= "Sacado Eletrônico DDA"; break;
						case "86": $motivo .= "Seu Numero de Documento Invalido"; break;
						case "89": $motivo .= "Email do Sacado nao Enviado - Titulo com Debito Automatico"; break;
						case "90": $motivo .= "Email do Sacado nao Enviado - Titulo de Cobranca sem Regsitro"; break;
					}
				}
				break;
			case "03":
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr($codigo_motivo,$i,2);
					switch($subocorrencia){
						case "02": $motivo .= "Codigo do Registro Detalhe Invalido"; break;
						case "03": $motivo .= "Codigo da Ocorrencia Invalida"; break;
						case "04": $motivo .= "Codigo da Ocorrencia nao Permitida para a Carteira"; break;
						case "05": $motivo .= "Codigo de Ocorrencia nao Numerico"; break;
						case "07": $motivo .= "Agencia/Conta/Digito - Invalido"; break;
						case "08": $motivo .= "Nosso Numero Invalido"; break;
						case "09": $motivo .= "Nosso Numero Duplicado"; break;
						case "10": $motivo .= "Carteira Invalida"; break;
						case "13": $motivo .= "Identificacao da Emissao do Bloqueto Invalida"; break;
						case "16": $motivo .= "Data de Vencimento Invalida"; break;
						case "18": $motivo .= "Vencimento Fora do Prazo"; break;
						case "20": $motivo .= "Valor do Titulo Invalido"; break;
						case "21": $motivo .= "Esp&eacute;cie do T&iacute;tulo Invalido"; break;
						case "22": $motivo .= "Especie nao Permitida para a Carteira"; break;
						case "24": $motivo .= "Data de Emiss&atilde;o Inv&Aacute;lida"; break;
						case "28": $motivo .= "Codigo de Desconto Invalido"; break;
						case "38": $motivo .= "Prazo para Desconto Invalido"; break;
						case "44": $motivo .= "Agencia do Cedente nao Prevista"; break;
						case "45": $motivo .= "Nome do Sacado nao Informado"; break;
						case "46": $motivo .= "Tipo/Numero da Inscricao Invalidos"; break;
						case "47": $motivo .= "Endereco do Sacado Nao Informado"; break;
						case "48": $motivo .= "Cep Invalido"; break;
						case "50": $motivo .= "Cep Irregular - Banco Correspondente"; break;
						case "63": $motivo .= "Entrada Para Titulo ja Cadastrado"; break;
						case "65": $motivo .= "Limite Excedido"; break;
						case "66": $motivo .= "Numero Autorizacao Inexistente"; break;
						case "68": $motivo .= "Debito nao Agendado - Erros nos Dados de Remessa"; break;
						case "69": $motivo .= "Debito nao Agendado - Sacado nao Consta no Cadastro Autorizante"; break;
						case "70": $motivo .= "Debito nao Agendado - Cedente nao Autorizado pelo Sacado"; break;
						case "71": $motivo .= "Debito nao Agendado - Cedente nao Participa do Debito Automatico"; break;
						case "72": $motivo .= "Debito nao Agendado - Codigo de Moeda Diferente de R$"; break;
						case "73": $motivo .= "Debito nao Agendado - Data de Vencimento Invalida"; break;
						case "74": $motivo .= "Debito nao Agendado - Conforme Seu Pedido Titulo nao Registrado"; break;
						case "75": $motivo .= "Debito nao Agendado - Tipo de N&uacute;mero de Inscricao do Debitado Invalido"; break;
					}
				}
				break;
			case "06":
			case "15":
			case "17":
				$arr_ocorrliq = array();
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr($codigo_motivo,$i,2);
					if(in_array($subocorrencia,$arr_ocorrliq)){
						continue;
					}
					switch($subocorrencia){
						case "00":
							$arr_ocorrliq[$subocorrencia] = $subocorrencia;
							$motivo .= "Titulo Pago com Dinheiro"; break;
						case "01":
						case "15": $motivo .= "Titulo Pago com Cheque"; break;
						case "04": $motivo .= "Rateio nao Efetuado "; break;
					}
				}
				break;
			case "09":
				$arr_ocorr09 = array();
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr(i,2);
					if(in_array($subocorrencia,$arr_ocorr09)){
						continue;
					}
					switch($subocorrencia){
						case "00":
							$arr_ocorr09[$subocorrencia] = $subocorrencia;
							$motivo = "Baixa Aceita"; break;
						case "10": $motivo = "Baixa Comandada Pelo Cliente"; break;
					}
				}
				break;
			case "10":
				$arr_ocorr10 = array();
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr(i,2);
					if(in_array($subocorrencia,$arr_ocorr10)){
						continue;
					}
					switch($subocorrencia){
						case "00":
							$arr_ocorr10[$subocorrencia] = $subocorrencia;
							$motivo = "Baixado Conforme Instrucoes da Agencia"; break;
						case "14": $motivo = "Titulo Protestado"; break;
						case "15": $motivo = "Titulo Excluido"; break;
						case "16": $motivo = "Titulo Baixado Pelo Banco por Decurso Prazo"; break;
						case "17": $motivo = "Titulo Baixado Transferido Carteira"; break;
						case "20": $motivo = "Titulo Baixado e Transferido Para Desconto"; break;
					}
				}
				break;
			case "27":
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr(i,2);
					switch($subocorrencia){
						case "04": $motivo = "Codigo de Ocorrencia nao Permitido para a Carteira"; break;
						case "07": $motivo = "Agencia/Conta/Digito Invalidos"; break;
						case "08": $motivo = "Nosso Numero Invalido"; break;
						case "10": $motivo = "Carteira Invalida"; break;
						case "15": $motivo = "Carteira/Agencia/Conta/Nosso Numero Invalidos"; break;
						case "40": $motivo = "Titulo com Ordem de Protesto Emitido"; break;
						case "42": $motivo = "Codigo para Baixa/Devolucao Via Telebradesco Invalido"; break;
						case "60": $motivo = "Movimento Para Titulo nao Cadastrado"; break;
						case "77": $motivo = "Transferencia Para Desconto nao Permitido para a Carteira"; break;
						case "85": $motivo = "Titulo com Pagamento Vinculado"; break;
					}
				}
				break;
		}
		return $motivo;
	}

	public function setnumcheque($numcheque){
		$this->numcheque = $numcheque;
	}
}
?>