<?php
require_once("websac/require_file.php");
require_file("class/interfacebancaria.class.php");
/*---------------------------------------------------------------------------------------------------------
Autor...: Jesus
Objetivo: Classe responsavel por gerar o arquivo de remessa e processar o arquivo de retorno do banco santander
---------------------------------------------------------------------------------------------------------*/
class Santander extends InterfaceBancaria{
	protected $con;
	protected $estabelecimento;
	protected $banco;
	private $linhas;
	protected $arr_lancamento;
	protected $ocorrencia;
	protected $remessa_anterior;
	private $arquivo_retorno_banco;
	protected $arr_banco; // Array com os bancos dos lancamentos
	private $arr_parceiro; // Array com os tipo de parceiros dos lancamentos, de acordo com a view v_parceiro

	function __construct($con){
		$this->con = $con;
		$this->linhas = array();
		$this->arr_lancamento = array();
		$this->setocorrencia("N");
	}

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
	private function forma_lancamento($lancamento){
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
	}

	function setarquivo_retorno_banco($arquivo_retorno_banco){
		$this->arquivo_retorno_banco = $arquivo_retorno_banco;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	function setbanco($banco){
		$this->banco = $banco;
	}

	function addlancamento($lancamento){
		$this->arr_lancamento[] = $lancamento;
	}

	function setremessa_anterior(){
		$this->remessa_anterior = $this->remessa_gerada_anteriormente();
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor...: Jesus
	Objetivo: Processar o arquivo de retorno do banco
	---------------------------------------------------------------------------------------------------------*/
	function processar_retorno($arquivo_retorno){
		$arquivo_retorno = fopen($arquivo_retorno,"r");
		$linhas = array();
		while(!feof($arquivo_retorno)){
			$linhas[] = fgets($arquivo_retorno,4096);
		}
		fclose($arquivo_retorno);

		$linha_header = array_shift($linhas);
		array_pop($linhas);
		//verifica se a primeira linha do arquivo e de um arquivo de retorno e se foi gerado pelo banco bradesco
		$retorno = strtoupper(substr($linha_header,2,7));
		$banco = substr($linha_header,76,3);
		$data_pagamento = substr($linha_header,94,6);
		if(trim($retorno) != "RETORNO" || trim($banco) != "237"){
			$_SESSION["ERROR"] = "O arquivo ".$this->arquivo_retorno_banco." n&atilde;o &eacute; um arquivo de retorno v&aacute;lido para o banco bradesco.";
			return FALSE;
		}

		//verifica se codigo da empresa do arquivo de retorno confere
		if(substr($linha_header,26,20) != $this->formatainteiro($this->banco->getcodigoempresa(),20)){
			$_SESSION["ERROR"] = "O c&oacute;digo da empresa do arquivo retorno selecionado &eacute; diferente do c&oacute; da empresa do banco selecionado para processo de retorno.";
			return FALSE;
		}

		if(sizeof($linhas) > 0){
			$this->con->start_transaction();
			foreach($linhas as $i => $linha){
				if(substr($linha,0,1) == "1"){
					$codlancto = trim(ltrim(substr($linha,37,25),"0"));
					$ocorrencia = substr($linha,108,2);
					$valor_titulo = substr($linha,152,13) / 100;

					$valor_pago = substr($linha,253,13) / 100;
					$desconto = substr($linha,240,13) / 100;
					$valorjuros = substr($linha,266,13) / 100;
					if($data_pagamento == NULL){
						$data_pagamento = substr($linha,295,6);
					}
					$motivo = $this->getmotivo($ocorrencia,substr($linha,318,10));
					$lancamento = objectbytable("lancamento",$codlancto,$this->con);
					if($lancamento->exists()){
						if($ocorrencia == "06" || $ocorrencia == "15" || $ocorrencia == "17"){
							$lancamento->setcodocorrencia($ocorrencia);
							$lancamento->setmotivoocorrencia($motivo);
							$lancamento->setstatus("L");
							$lancamento->setvalorpago($valor_pago);
							$lancamento->setcodespecieliq($lancamento->getcodespecie());
							$lancamento->setcodbanco($lancamento->getcodbanco());
							$lancamento->setdocliquidacao($lancamento->getnumnotafis());
							$lancamento->setvalorjuros($valorjuros);
							$lancamento->setdtliquid(convert_date($data_pagamento,"dmy","d/m/Y"));
						}else{
							$lancamento->setcodocorrencia($ocorrencia);
							$lancamento->setmotivoocorrencia($motivo);
						}
						if(!$lancamento->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}elseif(substr($linha,0,1) == "0"){
					//pegar a data de pagamento a data de gravacao do arquivo
					$data_pagamento = substr($linha,94,6);

				}
			}
			$this->con->commit();
			return TRUE;
		}
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor...: Jesus
	Objetivo:Gerar o arquivo de remessa
	---------------------------------------------------------------------------------------------------------*/
	function gerar_remessa(){
		if(!is_dir($this->estabelecimento->getdirremessabanco())){
			$_SESSION["ERROR"] = "Diret&oacute;rio de remessa n&atilde;o configurado no estabelecimento.<br><a onclick=\"openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}
		$this->setremessa_anterior();
		$this->con->start_transaction();
		if($this->banco->getdtultremessa(TRUE) == date("d/m/Y")){
			$this->banco->setnrultremessa($this->banco->getnrultremessa()+1);
		}else{
			$this->banco->setnrultremessa(1);
			$this->banco->setdtultremessa(date("d/m/Y"));
		}
		//criar na tabela de controle de processos o registro do processo executado
		if(!$this->remessa_anterior && $this->ocorrencia == "01"){
			$processo_gerado = objectbytable("controleprocfinan",NULL,$this->con);
			$processo_gerado->settipoprocesso("GR");  	//GR-geracao remessa
			$processo_gerado->setstatus("N");			//N-normal
			if(!$processo_gerado->save()){
				$this->con->rollback();
				return FALSE;
			}
		}elseif($this->ocorrencia == "02"){
			$processogr = $this->arr_lancamento[0]->getprocessogr();
			$lancamento = objectbytable("lancamento",NULL,$this->con);
			$lancamento->setprocessogr($processogr);
			$search = $lancamento->searchbyobject();
			var_dump($search);
			if($search === FALSE){
				$search = array();
			}
			elseif(!is_array($search)){
				$search = array($search);
			}
			if(sizeof($this->arr_lancamento) == sizeof($search)){
				$processo_gerado = objectbytable("controleprocfinan",$processogr,$this->con);
				$processo_gerado->setstatus("C");			//C-cancelado
				if(!$processo_gerado->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}

		if(!$this->linha_header()){
			$this->banco->rollback();
			return FALSE;
		}
		$item = 2;
		foreach($this->arr_lancamento as $lancamento){
			$this->linha_detalhe($lancamento, $item++,(is_object($processo_gerado) ? $processo_gerado->getcodcontrprocfinan() : NULL));
		}

		$this->linha_trailler($item);
		if(!$this->banco->save()){
			$this->con->rollback();
			return FALSE;
		}
		$arquivo_remessa = fopen($this->estabelecimento->getdirremessabanco()."CS".date("dm").$this->banco->getnrultremessa().".rem","w+");
		fwrite($arquivo_remessa,implode("\r\n",$this->linhas)."\r\n");
		fclose($arquivo_remessa);
		//$this->con->rollback();
		$this->con->commit();
		return TRUE;
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor...: Jesus
	Objetivo: Gerar a linha Header do arquivo de remessa
	---------------------------------------------------------------------------------------------------------*/
	function linha_header(){
		if((!$this->remessa_anterior) || ($this->remessa_anterior && $this->ocorrencia == "N")){
			$this->banco->setseqremessa($this->banco->getseqremessa()+1);
			if(!$this->banco->save()){
				return FALSE;
			}
		}
		$linha =  "0";       														//Identificacao do registro
		$linha .= "1";																//Idetificacoa do arquivo de remessa
		$linha .= "REMESSA";														//Lieral "REMESSA"
		$linha .= "01";																//Codigo do servico
		$linha .= $this->formatatexto("COBRANCA",15);								//Literal servico
		$linha .= $this->formatainteiro($this->banco->getagencia(),4);				//Codigo da agencia
		$linha .= $this->formatainteiro($this->banco->getdigitoagencia(), 1);		//digito agencia
		$linha .= $this->formatatexto($this->banco->getcodigoempresa(),7);			//Conta movimento do cendente
		$linha .= $this->formatatexto($this->banco->getconta(),8);					//Conta cobranca do cedente
		$linha .= $this->formatatexto($this->estabelecimento->getrazaosocial(),30); //Nome do cedente
		$linha .= "033";															//Numero do banco santander na camara de compensacao
		$linha .= $this->formatatexto("SANTANDER",15);								//Nome do banco
		$linha .= date("dmy");														//Data de gravacao do arquivo
		$linha .= str_repeat("0",16);                  								//Zeros
		$linha .= str_repeat(" ",275);                  							//Espacos em branco
		$linha .= str_repeat(" ",3);                  								//Numero da versao (opcional)
		$linha .= "000001";															//Sequencial do registro
		$this->linhas[] = $linha;
		return TRUE;
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor...: Jesus
	Objetivo: Gerar a linha Detalhe do arquivo de remessa
	---------------------------------------------------------------------------------------------------------*/
	function linha_detalhe($lancamento,$index,$nrprocesso,$tipo = 1){
		if($lancamento->gettipoparceiro() == "F"){
			$parceiro = objectbytable("fornecedor",$lancamento->getcodparceiro(),$this->con);
			$cidade = objectbytable("cidade",$parceiro->getcodcidade());
		}else{
			$parceiro = objectbytable("cliente",$lancamento->getcodparceiro(),$this->con);
			$cidade = objectbytable("cidade",$parceiro->getcodcidadefat());
		}
		$cliente = "";
		if($tipo == 1){
			$linha = "1";		  														//Identificao do registro
			$linha .= "02";																//Tipo inscricao do cedente 1-CPF 2-CGC
			$linha .= $this->formatainteiro(removeformat($this->estabelecimento->getcpfcnpj()),14); //Numero da inscricao do sacado
			$linha .= $this->formatainteiro($this->banco->getagencia(),4);				//Codigo da agencia
			$linha .= $this->formatainteiro($this->banco->getcodigoempresa(),8); 		//Conta movimento do cendente
			$linha .= $this->formatainteiro($this->banco->getconta(),8); 		//Conta cobranca do cedente
			$linha .= $this->formatatexto($lancamento->getcodlancto(),25);				//Numero de controle do cedente (participante)
			$nosso_numero = "00000000";
			$dac_nosso_numero = "0";
			if($this->banco->getbancoemiteboleto() == "N"){
				if(strlen($lancamento->getnossonumero()) == 0){
					$this->banco->setnossonumero($this->banco->getnossonumero()+1);
					$nosso_numero = trim($this->banco->getnossonumero());
					//$nosso_numero = $this->formatainteiro($nosso_numero,8);
					$nosso_numero = $this->formatainteiro($nosso_numero,7);
					$dac_nosso_numero = modulo_11BL($nosso_numero);
					//$dac_nosso_numero = dac_nosso_numero($this->banco->getcodoficial(),$nosso_numero,$this->banco->getcarteira());	//Digito verificado nosso numero
				}else{
					//$nosso_numero = $this->formatainteiro($lancamento->getnossonumero(),8);
					$nosso_numero = $this->formatainteiro($lancamento->getnossonumero(),7);
					//$dac_nosso_numero = "";
					$dac_nosso_numero = modulo_11BL($nosso_numero);
				}
			}
			//$nosso_numero = substr($nosso_numero,-8);
			$nosso_numero = substr($nosso_numero,-7);
			$linha .= $nosso_numero;
			$linha .= $dac_nosso_numero;
			$linha .= str_repeat("0",6);												//Data do segundo desconto
			$linha .= " ";																//Branco
			if($this->banco->getvalormoradiaria() > 0){
				$linha .= "4";															//Informacao de multa
			}else{
				$linha .= "0";															//Informacao de multa
			}
			$linha .= ($this->banco->getpercmulta() > 0 ? $this->formatadecimal($this->banco->getpercmulta(),4,2) : "0000");		//Percentual de multa
			$linha .= "00";																//Unidade de valor da moeda corrente = 00
			$linha .= str_repeat(" ",13);												//Valor do titulo em outra moeda
			$linha .= str_repeat(" ",4);												//Brancos
			$linha .= str_repeat("0",6);												//Data para cobranc da multa, Zeros sera cobrada a partir do vencimento
			//$linha .= substr($this->banco->getcarteira(), 0, 1);						//Codigo da carteira 2-Eletronica com registro 3-Caucionada eletronica 5-Rapida com registro (bloquet emitido pelo cliente
			$linha .= $this->banco->getcodcarteira();									//Codigo da carteira 2-Eletronica com registro 3-Caucionada eletronica 5-Rapida com registro (bloquet emitido pelo cliente
			switch($this->ocorrencia){
				case "C":
					$ocorrencia = "02";
					break;
				default:
					$ocorrencia = "01";
					break;
			}
			$linha .= $ocorrencia;											//Codigo da ocorrrencia
			$linha .= $this->formatainteiro($lancamento->getnumnotafis(),7)."/".$this->formatainteiro($lancamento->getparcela(),2);			//Numero do documento
			$linha .= convert_date($lancamento->getdtvencto(TRUE),"d/m/Y","dmy");		//Data de vencimento
			$linha .= $this->formatadecimal($lancamento->getvalorliquido(),13,2);		//Valor do titulo
			$linha .= "353";															//Numero do banco cobrador
			if($this->banco->getcarteira() == "5"){
				$linha .= $this->formatainteiro($this->banco->getagencia(),5);			//Codigo da agencia	cobradora (somente quando a carteira for igual a 5)
			}else{
				$linha .= "00000";														//Codigo agencia cobradora
			}
			$linha .= "01"; 															//Especie do titulo (01-duplicata)"
			$linha .= "N";																//Identificacao do tipo de aceite (sempre N)
			$linha .= convert_date($lancamento->getdtemissao(TRUE),"d/m/Y","dmy");		//Data de emissao do titulo
			$linha .= str_pad($this->banco->getcodpinstrucao(),"0", 2, STR_PAD_LEFT);	//Primeira instrucao
			$linha .= str_pad($this->banco->getcodsinstrucao(),"0", 2, STR_PAD_LEFT);	//Segunda instrucao
			/*
			if($this->banco->getdiasprotesto() > 0){
				$linha .= "06";															//Primeira instrucao
				$linha .= "00";															//Segunda instrucao
			}else{
				$linha .= "00";															//Primeira instrucao
				$linha .= "00";															//Segunda instrucao
			}
			 *
			 */
			$linha .= $this->formatadecimal(($lancamento->getvalorliquido * $this->banco->getvalormoradiaria()) / 30,13,2);	//Valor de juro de mora diaria
			$linha .= "000000";															//Data limite para concesao de desconto
			$linha .= "0000000000000";													//Valor do desconto
			$linha .= "0000000000000";													//Valor od IOF
			$linha .= "0000000000000";													//Valor do abatimento a ser concedido ou cancelado
			$linha .= ($parceiro->gettppessoa() == "F" ? "01" : "02");					//Tipo de inscricao do sacado
			$linha .= $this->formatainteiro(removeformat($parceiro->getcpfcnpj()),"14"); //Numero da inscricao do sacado
			$linha .= $this->formatatexto($parceiro->getrazaosocial(),40);				//Nome do sacado
			$linha .= $this->formatatexto(($lancamento->gettipoparceiro() == "F" ? $parceiro->getendereco() : $parceiro->getenderfat()),40);				//Endereco do sacado
			$linha .= $this->formatatexto(($lancamento->gettipoparceiro() == "F" ? $parceiro->getbairro() : $parceiro->getbairrofat()),12);					//Endereco do sacado
			$cep = removeformat(($lancamento->gettipoparceiro() == "F" ? $parceiro->getcep() : $parceiro->getcepfat()));

			$linha .= $this->formatainteiro(substr($cep,0,5),5);						//Cep do sacado
			$linha .= $this->formatainteiro(substr($cep,5,3),3);					    //Sufixo do cep
			$linha .= $this->formatatexto($cidade->getnome(),15);						//Municipio do sacado
			$linha .= ($lancamento->gettipoparceiro() == "F" ? $parceiro->getuf() : $parceiro->getuffat());												//UF do sacado
			$linha .= $this->formatatexto("",30);										//Sacador/Avalista 2ª mensagem
			$linha .= str_repeat(" ",1);												//Brancos
			$linha .= str_repeat("I",1);												//Identificador do Complemento (nota 2)
			$linha .= "63";																//Complemento (nota 2)
			$linha .= str_repeat(" ",6);												//Brancos
			if($this->banco->getdiasprotesto() > 0){
				$linha .= $this->formatainteiro($this->banco->getdiasprotesto(),2);		//Numero de dia para protesto
			}else{
				$linha .= "00";															//Numero de dia para protesto
			}
			$linha .= str_repeat(" ",1);												//Brancos
			$linha .= $this->formatainteiro($index+1,6);									//Sequencial do registro
		}elseif($tipo == 2){
			//Nao sera implementado neste momento
		}elseif($tipo == 3){
			//Nao sera implementado neste momento
		}elseif($tipo == 7){
			//Nao sera implementado neste momento
		}
		if($this->ocorrencia == "N" && !$this->remessa_anterior){
			$lancamento->setseqremessa($this->banco->getseqremessa());
			//$lancamento->setnossonumero($nosso_numero.$dac_nosso_numero);
			$lancamento->setnossonumero($nosso_numero);
			$lancamento->setprocessogr($nrprocesso);
			$lancamento->setcodocorrencia("00");
			$lancamento->setmotivoocorrencia("Aguardando Retorno");
			$lancamento->setprocessogr($this->controleprocfinan->getcodcontrprocfinan());
		}elseif($this->ocorrencia == "C" || $this->ocorrencia == "G"){
			$lancamento->setseqremessa(NULL);
			$lancamento->setnossonumero(NULL);
			$lancamento->setprocessogr(NULL);
			$lancamento->setcodocorrencia("01");
			$lancamento->setocorrencia("Cancelada remessa");
			$lancamento->setmotivoocorrencia("Cancelada a Geracao de Remessa");
			$lancamento->setdtremessa(NULL);
		}

		if(!$lancamento->save()){
			$this->con->rollback();
			return FALSE;
		}
		$this->linhas[] = $linha;
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor...: Jesus
	Objetivo: Gerar a linha Trailler do arquivo de remessa
	---------------------------------------------------------------------------------------------------------*/
	function linha_trailler($total_registros, $valor_total_titulos){
		$linha = "9";																	//Identificacao do registro
		$linha .= $this->formatainteiro($total_registros,6);    						//Numero de documentos no arquivo
//		$linha .= $this->formatadecimal($valor_total_titulos,13,2);						//Valor total dos titulos
		$linha .= $this->formatadecimal($valor_total_titulos,13,2);						//Valor total dos titulos
		$linha .= str_repeat("0",374);													//Zeros
		$linha .= $this->formatainteiro($total_registros+2,6);    						//Sequencial do registro
		$this->linhas[] = $linha;
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor...: Jesus
	Objetivo: Verificar se os lancamentos da geracao atual pertence a uma geracao anterior
	*/
	/*
	function remessa_gerada_anteriormente(){
		$found = FALSE;
		foreach($this->arr_lancamento as $lancamento){
			if(strlen($lancamento->getseqremessa()) > 0){
				$found = TRUE;
				break;
			}
		}
		return $found;
	}
	*/
	/*---------------------------------------------------------------------------------------------------------
	Autor: Jesus
	Objetivo.....: formatar retornar um valor monetario sem ponto e virgulo conforme tamanho e decimais passados
				 :	como parametro
	Parametros...:
		$valor...:  valor monetario a ser formatado
		$tamanho.: tamanho total dos dados apos formatacao feita
		$decimais: numero de casas decimais a ser considerada
	retorno......: o valor formatado
	---------------------------------------------------------------------------------------------------------*/
	function formatadecimal($valor,$tamanho,$decimais){
		return $this->formatainteiro(number_format($valor,$decimais,"",""),$tamanho);
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor.......: Jesus
	Objetivo....: formatar retornar um numero inteiro com zeros a esquerda
	Parametros..:
		$numero.: o valor inteiro a ser formatado
		$tamanho: tamnaho total dos dados apos a formatcao
	Retorno: numero interiro formatado
	---------------------------------------------------------------------------------------------------------*/
	function formatainteiro($numero,$tamanho){
		return str_pad($numero,$tamanho,"0",STR_PAD_LEFT);
	}

	/*---------------------------------------------------------------------------------------------------------
	Autor: Jesus
	Objetivo........: Formatar e retornar um texto todo em caixa alta conforme parametros passsado
	Parametros......:
		$texto......: o texto a ser formatado
		$tamanho....: tamanho do texto a ser retornado apos a formatacao
		$posicao....: posicao inicial de onde no texto passado ($texto) deve ser consirado
		$alinhamento: indica se o texto formata de ve ser alinhado a esquerda(E) ou a direita(D)
	Retorno.........: texto formato conforme tamanho e alinhamento passados nos parametros
	---------------------------------------------------------------------------------------------------------*/
	function formatatexto($texto, $tamanho, $posicao = 0,$alinhamento = "E"){
		if($alinhamento == "D"){
			return strtoupper(str_pad(substr($texto,$posicao,$tamanho),$tamanho," ",STR_PAD_LEFT));
		}else{
			return strtoupper(str_pad(substr($texto,$posicao,$tamanho),$tamanho," ",STR_PAD_RIGHT));
		}
	}

	/*-------------------------------------------------------------------------------------------------------
	Autor: Jesus
	Objetivo...........: Retornar os motivos conforme codigo de ocorrencia encontradas no arquivo de retorno
	Parametros.........:
		$ocorrencia....: codigo da ocorrencia principal encontrada no titulo
		$codigo_motivo.: lista de codigos de subocorrencias encontrada no titulo (pode conter ate 5 codigos
							diferentes para uma mesma ocorrencia principal
	--------------------------------------------------------------------------------------------------------*/
	function getmotivo($ocorrencia, $codigo_motivo){
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
							$motivo .= "Ocorrência Aceita"; break;
						case "01": $motivo .= "Código do Banco Inválido"; break;
						case "04": $motivo .= "Código do Movimento não Permitido para a Carteira"; break;
						case "15": $motivo .= "Caracteristicas de Cobrança Incompatíveis"; break;
						case "17": $motivo .= "Data e Vencimento Anterior a Data de Emissão"; break;
						case "21": $motivo .= "Espécie de Título Inválido"; break;
						case "24": $motivo .= "Data de Emissão Inválida"; break;
						case "27": $motivo .= "Valor/Taxa de Juros Mora Diária Inválida"; break;
						case "38": $motivo .= "Prazo para Protesto Inválido"; break;
						case "45": $motivo .= "Nome do Sacado Inválido"; break;
						case "46": $motivo .= "Tipo/Numero de Inscrição do Sacado Invalido"; break;
						case "47": $motivo .= "Endereço do Sacado Inválido"; break;
						case "48": $motivo .= "Cep Inválido"; break;
						case "50": $motivo .= "Cep Referente a Banco Correspondente"; break;
						case "53": $motivo .= "Numero de Inscrição do Sacador/Avalista Inválidos"; break;
						case "54": $motivo .= "Sacador/Avalista não Informado"; break;
						case "67": $motivo .= "Debito Automático Agendado"; break;
						case "68": $motivo .= "Debito não Agendado - Erros nos Dados da Remessa"; break;
						case "69": $motivo .= "Debito não Agendado - Sacado não Consta Cadastro de Autorizante"; break;
						case "70": $motivo .= "Debito não Agendado - Cedente não Autorizado pelo Sacado"; break;
						case "71": $motivo .= "Debito não Agendado - Cedente não Participa de Modalidade D&eacute;bito Automático"; break;
						case "72": $motivo .= "Debito não Agendado - C&oacute; de Moeda Diferente de R$"; break;
						case "73": $motivo .= "Debito não Agendado - Data de Vencimento Inválida/Vencida"; break;
						case "75": $motivo .= "Debito não Agendado - Tipo de Numero de Inscrição do Sacado Debitado Inválido"; break;
						case "76": $motivo .= "Sacado Eletrônico DDA"; break;
						case "86": $motivo .= "Seu Numero de Documento Inválido"; break;
						case "89": $motivo .= "Email do Sacado não Enviado - Título com Débito Automático"; break;
						case "90": $motivo .= "Email do Sacado não Enviado - Título de Cobranca sem Regsitro"; break;
					}
				}
				break;
			case "03":
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr($codigo_motivo,$i,2);
					switch($subocorrencia){
						case "02": $motivo .= "Código do Registro Detalhe Inválido"; break;
						case "03": $motivo .= "Código da Ocorrência Inválida"; break;
						case "04": $motivo .= "Código da Ocorrência não Permitida para a Carteira"; break;
						case "05": $motivo .= "Código de Ocorrência não Numerico"; break;
						case "07": $motivo .= "Agencia/Conta/Digito - Inválido"; break;
						case "08": $motivo .= "Nosso Numero Inválido"; break;
						case "09": $motivo .= "Nosso Numero Duplicado"; break;
						case "10": $motivo .= "Carteira Inválida"; break;
						case "13": $motivo .= "Identificação da Emissão do Bloqueto Inválida"; break;
						case "16": $motivo .= "Data de Vencimento Inválida"; break;
						case "18": $motivo .= "Vencimento Fora do Prazo"; break;
						case "20": $motivo .= "Valor do Título Inválido"; break;
						case "21": $motivo .= "Esp&eacute;cie do T&iacute;tulo Inválido"; break;
						case "22": $motivo .= "Espécie não Permitida para a Carteira"; break;
						case "24": $motivo .= "Data de Emiss&atilde;o Inv&Aacute;lida"; break;
						case "28": $motivo .= "Código de Desconto Inválido"; break;
						case "38": $motivo .= "Prazo para Desconto Inválido"; break;
						case "44": $motivo .= "Agencia do Cedente não Prevista"; break;
						case "45": $motivo .= "Nome do Sacado não Informado"; break;
						case "46": $motivo .= "Tipo/Numero da Inscrição Inválidos"; break;
						case "47": $motivo .= "Endereço do Sacado Não Informado"; break;
						case "48": $motivo .= "Cep Inválido"; break;
						case "50": $motivo .= "Cep Irregular - Banco Correspondente"; break;
						case "63": $motivo .= "Entrada Para Título ja Cadastrado"; break;
						case "65": $motivo .= "Limite Excedido"; break;
						case "66": $motivo .= "Numero Autorização Inexistente"; break;
						case "68": $motivo .= "Débito não Agendado - Erros nos Dados de Remessa"; break;
						case "69": $motivo .= "Débito não Agendado - Sacado não Consta no Cadastro Autorizante"; break;
						case "70": $motivo .= "Débito não Agendado - Cedente não Autorizado pelo Sacado"; break;
						case "71": $motivo .= "Débito não Agendado - Cedente não Participa do Débito Automático"; break;
						case "72": $motivo .= "Débito não Agendado - Código de Moeda Diferente de R$"; break;
						case "73": $motivo .= "Débito não Agendado - Data de Vencimento Inválida"; break;
						case "74": $motivo .= "Débito não Agendado - Conforme Seu Pedido Título não Registrado"; break;
						case "75": $motivo .= "Débito não Agendado - Tipo de N&uacute;mero de Inscrição do Debitado Inválido"; break;
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
							$motivo .= "Título Pago com Dinheiro"; break;
						case "01":
						case "15": $motivo .= "Título Pago com Cheque"; break;
						case "04": $motivo .= "Rateio não Efetuado "; break;
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
							$motivo = "Baixado Conforme Instruções da Agência"; break;
						case "14": $motivo = "Título Protestado"; break;
						case "15": $motivo = "Título Excluído"; break;
						case "16": $motivo = "Título Baixado Pelo Banco por Decurso Prazo"; break;
						case "17": $motivo = "Título Baixado Transferido Carteira"; break;
						case "20": $motivo = "Título Baixado e Transferido Para Desconto"; break;
					}
				}
				break;
			case "27":
				for($i = 0;$i < strlen($codigo_motivo);$i += 2){
					$subocorrencia = substr(i,2);
					switch($subocorrencia){
						case "04": $motivo = "Código de Ocorrência não Permitido para a Carteira"; break;
						case "07": $motivo = "Agência/Conta/Dígito Inválidos"; break;
						case "08": $motivo = "Nosso Numero Inválido"; break;
						case "10": $motivo = "Carteira Inválida"; break;
						case "15": $motivo = "Carteira/Agência/Conta/Nosso Numero Inválidos"; break;
						case "40": $motivo = "Título com Ordem de Protesto Emitido"; break;
						case "42": $motivo = "Código para Baixa/Devolução Via Telebradesco Inválido"; break;
						case "60": $motivo = "Movimento Para Título não Cadastrado"; break;
						case "77": $motivo = "Transferência Para Desconto não Permitido para a Carteira"; break;
						case "85": $motivo = "Título com Pagamento Vinculado"; break;
					}
				}
				break;
		}
		return $motivo;
	}

	function gerar_remessa_recebimento(){
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

		// Incrementa o numero da remessa no banco
		if(!$this->remessa_anterior){
			$this->banco->setseqremessa($this->banco->getseqremessa() + 1);
			if(!$this->banco->save()){
				//$this->con->rollback();
				return FALSE;
			}
		}

		$this->linhas = array();

		$this->linha_header();

		$valor_total_titulos = 0;
		foreach($this->arr_lancamento as $i => $lancamento){
			$this->linha_detalhe($lancamento,($i+1),1,1);
			$valor_total_titulos += $lancamento->getvalorparcela();

		}

		$this->linha_trailler(count($this->arr_lancamento), $valor_total_titulos);

		$codprocfinan= 1;
		$paramarqremessa = array("numremessa" => $codprocfinan, "banco" => $this->banco->getcodoficial(), "nomebanco" => str_replace(" ", "", $this->banco->getnome()));

		parent::relatorio_remessa($this->arr_remessa, $paramarqremessa);

		echo write_file($this->estabelecimento->getdirremessabanco()."CB".date("dm").$this->banco->getnrultremessa().".rem", $this->linhas, true, "w+", true);

		return TRUE;
	}

	// Retorna o tipo de servico para geracao da remessa
	protected function tipo_servico($lancamento){
		if(in_array($lancamento->getcodtipoaceite(), array(1, 2, 3))){
			return "20";
		}else{
			return "22";
		}
	}
}