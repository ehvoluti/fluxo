<?php

class TributacaoProduto{

	private $con;
	private $classfiscal;
	private $emitente;
	private $estabelecimento;
	private $estadotributo;
	private $operacaonota;
	private $parceiro;
	private $produto;
	private $ncm;
	private $tabelapreco;
	private $idnotafiscalref; // Id da nota fiscal referencia, no caso de devolucoes
	private $natoperacao_in; // Natureza de operacao que o usuario informa (set)
	private $natoperacao_out; // Natureza de operacao que o usuario recebe (get)
	private $tptribicms;
	private $aliqicms;
	private $aliqicmsdesoneracao;
	private $redicms;
	private $aliqiva;
	private $valorpauta;
	private $tipoipi;
	private $valipi;
	private $percipi;
	private $preco; // O preco armazenado sera o preco UNITARIO do produto (para o pedido e nota fiscal, deve-se multiplicar pela quantidade a embalagem)
	private $percdescto;
	private $valdescto;
	private $percacresc;
	private $valacresc;
	private $percfrete;
	private $valfrete;
	private $percseguro;
	private $valseguro;
	private $guiagnre = FALSE;
	private $icms_interestadual = array("N" => array("N" => 12.00, "S" => 12.00), "S" => array("N" => 7.00, "S" => 12.00));
	private $aliqfcpst;			    //aliquota do fundo de combate a probreza substituição tributária
	private $aliqfcpufdest;			//aliquota do fundo de combate a probreza determinada para o produto no estado do destinatario
	private $aliqicmsufdest;		//aliquota de ICMS do destinatario
	private $aliqicmsinter;			//aliquota interestadual
	private $venda_interestadual;	//se venda interestadual setar como TRUE e se estadual setar como FALSE
	private $motivodesoneracao;
	private $calcularfcp = FALSE;
	private $arr_objeto = array(); // Array com objetos pre-carregados, para ficar mais rapido

	function __construct($con){
		$this->con = $con;
	}

	function buscar_dados(){
		// Carrega parametros
		$param_estoque_arredtabpreco = param("ESTOQUE", "ARREDTABPRECO", $this->con);

		// Carrega a classificacao fiscal
		$this->carregar_classfiscal();

		// Tratamento que deve ser removido em breve (apenas para nao ocorrer erro onde ainda nao tem tabela de preco)
		if(!is_object($this->tabelapreco)){
			$this->tabelapreco = $this->carregar_objeto("tabelapreco", 1);
		}

		// Verifica se deve usar a classificacao fiscal anexada a natureza de operacao informada
		//if($this->natoperacao_in->getcodcf() > 0 && $this->natoperacao_in->getcodcf() != $this->classfiscal->getcodcf() && $this->natoperacao_in->getalteracficmssubst() == "S"){
		if($this->natoperacao_in->getcodcf() > 0 && $this->natoperacao_in->getcodcf() != $this->classfiscal->getcodcf() && (in_array($this->classfiscal->gettptribicms(), array("T", "R")) ||
				($this->classfiscal->gettptribicms() == "F" && $this->natoperacao_in->getalteracficmssubst() == "S") ||
				($this->classfiscal->gettptribicms() == "I" && $this->natoperacao_in->getalteracfisento() == "S"))){
			$this->classfiscal = $this->carregar_objeto("classfiscal", $this->natoperacao_in->getcodcf());
		}

		$ufparceiro = ($this->operacaonota->getparceiro() == "C" ? $this->parceiro->getufres() : $this->parceiro->getuf());
		if(strlen($ufparceiro) === 0){
			$ufparceiro = $this->estabelecimento->getuf();
		}

		// Verifica se e uma devolucao (cliente/fornecedor) para buscar os dados do ultimo item
		if(in_array($this->operacaonota->getoperacao(), array("DC", "DF"))){
			switch($this->operacaonota->getparceiro()){
				case "C":
					$codparceiro = $this->parceiro->getcodcliente();
					break;
				case "F":
					$codparceiro = $this->parceiro->getcodfornec();
					break;
				case "E":
					$codparceiro = $this->parceiro->getcodestabelec();
					break;
			}

			$query = "SELECT itnotafiscal.iditnotafiscal ";
			$query .= "FROM itnotafiscal ";
			$query .= "INNER JOIN notafiscal ON (itnotafiscal.idnotafiscal = notafiscal.idnotafiscal) ";
			$query .= "WHERE notafiscal.operacao IN (".($this->operacaonota->getoperacao() == "DC" ? "'VD', 'RC'" : "'CP'").") ";
			$query .= "	AND notafiscal.codparceiro = $codparceiro ";
			$query .= "	AND itnotafiscal.codproduto = ".$this->produto->getcodproduto()." ";
			if(strlen($this->idnotafiscalref) > 0){
				$query .= "	AND notafiscal.idnotafiscal = ".$this->idnotafiscalref." ";
			}
			$query .= "ORDER BY notafiscal.dtentrega DESC ";
			$query .= "LIMIT 1 ";
			$res = $this->con->query($query);
			$iditnotafiscal = $res->fetchColumn();
			if(strlen($iditnotafiscal) > 0){
				$itnotafiscal = $this->carregar_objeto("itnotafiscal", $iditnotafiscal);
			}
		}

		// Verifica se ja existe um item de nota fiscal para copiar os valores
		if(is_object($itnotafiscal)){
			$this->tptribicms = $itnotafiscal->gettptribicms();
			$this->aliqicms = $itnotafiscal->getaliqicms();
			$this->redicms = $itnotafiscal->getredicms();
			$this->aliqiva = $itnotafiscal->getaliqiva();
			$this->aliqicmsdesoneracao = $itnotafiscal->getaliqicmsdesoneracao();
			$this->motivodesoneracao = $itnotafiscal->getmotivodesoneracao();
			$this->valorpauta = $itnotafiscal->getvalorpauta();
			$this->tipoipi = $itnotafiscal->gettipoipi();
			$this->valipi = $itnotafiscal->getvalipi();
			$this->percipi = $itnotafiscal->getpercipi();
			$this->preco = $itnotafiscal->getpreco() / $itnotafiscal->getqtdeunidade();
			$this->percdescto = $itnotafiscal->getpercdescto();
			$this->valdescto = $itnotafiscal->getvaldescto();
			$this->percacresc = $itnotafiscal->getpercacresc();
			$this->valacresc = $itnotafiscal->getvalacresc();
			$this->percfrete = $itnotafiscal->getpercfrete();
			$this->valfrete = $itnotafiscal->getvalfrete();
			$this->percseguro = $itnotafiscal->getpercseguro();
			$this->valseguro = $itnotafiscal->getvalseguro();
		}else{
			$this->aliqicmsdesoneracao = 0;
			$this->motivodesoneracao = "";
			// Carrega outros objetos necessarios
			$produtoestab = $this->carregar_objeto("produtoestab", array($this->estabelecimento->getcodestabelec(), $this->produto->getcodproduto()));

			// Busca o preco do produto
			if(($this->operacaonota->gettipo() == "E" || in_array($this->operacaonota->getoperacao(), array("TS", "DF", "RF"))) && !in_array($this->operacaonota->getoperacao(), array("DC"))){
				$this->preco = $produtoestab->getcustotab();
			}else{
				if($this->tabelapreco->gettipopreco() == "A"){
					$this->preco = ($produtoestab->getprecoatcof() > 0 ? $produtoestab->getprecoatcof() : $produtoestab->getprecoatc());
				}elseif($this->tabelapreco->gettipopreco() == "C"){
					$this->preco = $produtoestab->getcustorep();
				}else{
					$this->preco = ($produtoestab->getprecovrjof() > 0 ? $produtoestab->getprecovrjof() : $produtoestab->getprecovrj());
				}
				$ittabelapreco = $this->carregar_objeto("ittabelapreco", array($this->tabelapreco->getcodtabela(), $produtoestab->getcodproduto()));
				$percpreco = $ittabelapreco->getpercpreco();
				if(!($percpreco > 0)){
					$percpreco = $this->tabelapreco->getpercpreco();
				}
				$this->preco *= $percpreco / 100;

				if($param_estoque_arredtabpreco == 1){
					$this->preco = round($this->preco, 1);
				}elseif($this->operacaonota->getoperacao() === "VD"){
					$this->preco = round($this->preco, 2);
				}
			}
			$this->calcularfcp = TRUE;
			// Carrega o estado do estabelecimento e do parceiro
			$estado_parceiro = $this->carregar_objeto("estado", $ufparceiro);
			$estado_estabelecimento = $this->carregar_objeto("estado", $this->estabelecimento->getuf());
			$this->venda_interestadual = $estado_parceiro->getuf() != $estado_estabelecimento->getuf();
			// Verifica se estado tem convenio de ICMS (verifica o NCM)
			if(strlen($this->produto->getidncm()) > 0){
				$ncmestado = $this->carregar_objeto("ncmestado", array($this->produto->getidncm(), $estado_parceiro->getuf()));
				$convenioicms = $ncmestado->getconvenioicms();
			}else{
				$convenioicms = $estado_parceiro->getconvenioicms();
			}
			if($this->natoperacao_in->getcalcdifaloperinter() == "S" && ($this->operacaonota->getparceiro() !== "C" || $this->parceiro->getcontribuinteicms() === "N") && $this->venda_interestadual){
				if($ncmestado){
					$this->aliqfcpufdest = $ncmestado->getaliqfcp();
				}else{
					$this->aliqfcpufdest = 0;
				}
				if($this->estabelecimento->getregimetributario() == "1"){
					$this->aliqfcpufdest = 0;
				}

				if(is_object($ncmestado)){
					$this->aliqicmsufdest = $ncmestado->getaliqinterna();
				}else{
					$this->aliqicmsufdest = $estado_parceiro->getaliqicms();
				}
				if($this->aliqicmsufdest == 0 || is_null($this->aliqicmsufdest)){
					$this->aliqicmsufdest = $estado_parceiro->getaliqicms();
				}
				if($this->estabelecimento->getregimetributario() == "1" || (is_object($ncmestado) && $ncmestado->getcalculardifal() == "N")){
					$this->aliqicmsufdest = 0;
				}
				$this->aliqicmsinter = $this->icms_interestadual[$estado_estabelecimento->getregiao()][$estado_parceiro->getregiao()];
				if(in_array($this->operacaonota->getoperacao(), array("VD")) && in_array(substr($this->classfiscal->getcodcst(), 0, 1), array("1", "2","7"))){
					$this->aliqicmsinter = 4;
				}
			}else{
				$this->aliqfcpufdest = 0;
				$this->aliqicmsufdest = 0;
				$this->aliqicmsinter = 0;
				if($this->calcularfcp && !in_array($this->operacaonota->getoperacao(), array("TS","TE")) && $this->venda_interestadual && $this->parceiro->getcontribuinteicms() === "S"){
					if($ncmestado){
						$this->aliqfcpufdest = $ncmestado->getaliqfcp();
					}else{
						$this->aliqfcpufdest = 0;
					}
					if($this->estabelecimento->getregimetributario() == "1"){
						$this->aliqfcpufdest = 0;
					}
				}
			}

			// Se for orgao publico
			if($this->operacaonota->getparceiro() == "C" && (!in_array(substr($this->natoperacao_in->getnatoperacao(),0,5),array("5.929","6.929"))) && $this->parceiro->getorgaopublico() == "S" && in_array($this->classfiscal->gettptribicms(), array("T", "R"))){
				$this->percdescto = $this->classfiscal->getaliqicms() * (1 - $this->classfiscal->getaliqredicms() / 100);
				$this->tptribicms = "N";
				$this->aliqicms = 0;
				$this->redicms = 0;
				$this->aliqiva = 0;
				$this->valorpauta = 0;
				// Se for para dentro do estado, do regime simples ou excessao para o estado (substituicao tributaria)
				//}elseif($ufparceiro == $this->estabelecimento->getuf() || $this->estabelecimento->regime_simples() || $this->operacaonota->getoperacao() == "IM"){
			}elseif($ufparceiro == $this->estabelecimento->getuf() || $this->operacaonota->getoperacao() == "IM"){
				if($this->estabelecimento->regime_simples() && $ufparceiro != $this->estabelecimento->getuf() && $this->classfiscal->gettptribicms() == "F"){
					if(in_array($this->operacaonota->getoperacao(), array("VD")) && in_array(substr($this->classfiscal->getcodcst(), 0, 1), array("1", "2", "7"))){
						$aliqicms_inter = 4;
					}else{
						$aliqicms_inter = $this->icms_interestadual[$estado_estabelecimento->getregiao()][$estado_parceiro->getregiao()];
					}
					$this->aliqicms = $aliqicms_inter;
					$this->tptribicms = $this->classfiscal->gettptribicms();
					$this->redicms = $this->classfiscal->getaliqredicms();

					if($convenioicms == "S"){
						$estadotributo = $this->carregar_objeto("estadotributo", array($estado_parceiro->getuf(), $this->estabelecimento->getregimetributario(), $this->produto->getcodproduto()));
						$this->aliqiva = (strlen($this->estadotributo->getaliqiva()) > 0 ? $this->estadotributo->getaliqiva() : ($this->produto->getaliqiva() > 0 ? $this->produto->getaliqiva() : $this->classfiscal->getaliqiva()));
					}else{
						$this->aliqiva = ($this->produto->getaliqiva() > 0 ? $this->produto->getaliqiva() : $this->classfiscal->getaliqiva());
						$this->valorpauta = $this->classfiscal->getvalorpauta();
					}
					$this->valorpauta= (isset($ncmestado) && strlen($ncmestado->getvalorpauta()) > 0 ? $ncmestado->getvalorpauta() : $this->classfiscal->getvalorpauta());
					if($this->valorpauta > 0){
						$this->aliqiva = 0;
					}
					$this->valorpauta = $this->classfiscal->getvalorpauta();
					if($this->valorpauta > 0){
						$this->aliqiva = 0;
					}
				}else{
					if(in_array($this->classfiscal->gettptribicms(), array("T", "R", "F")) || $this->estabelecimento->gettipoatividade() == "I"){
						if(in_array(substr($this->classfiscal->getcodcst(),1,2), array("30", "40"))){
							$this->aliqicmsdesoneracao = $this->classfiscal->getaliqicms();
							$this->aliqicms = 0;
							$this->redicms = 0;
							$this->motivodesoneracao = $this->classfiscal->getmotivodesoneracao();
						}else{
							$this->aliqicms = $this->classfiscal->getaliqicms();
							$this->redicms = $this->classfiscal->getaliqredicms();
							$this->aliqicmsdesoneracao = 0;
							$this->motivodesoneracao = "";
						}
						$this->tptribicms = $this->classfiscal->gettptribicms();
						$this->aliqiva = (strlen($this->estadotributo->getaliqiva()) > 0 ? $this->estadotributo->getaliqiva() : ($this->produto->getaliqiva() > 0 ? $this->produto->getaliqiva() : $this->classfiscal->getaliqiva()));
						$this->valorpauta= (isset($ncmestado) && strlen($ncmestado->getvalorpauta()) > 0 ? $ncmestado->getvalorpauta() : $this->classfiscal->getvalorpauta());
						if($this->calcularfcp){
							$this->aliqfcpst = (isset($ncmestado) && strlen($ncmestado->getaliqfcp()) > 0 ? $ncmestado->getaliqfcp() : 0);
						}
						if($this->valorpauta > 0){
							$this->aliqiva = 0;
						}
					}else{
						if(in_array(substr($this->classfiscal->getcodcst(),1,2), array("40"))){
							$this->aliqicmsdesoneracao = $this->classfiscal->getaliqicms();
							$this->aliqicms = 0;
							$this->redicms = 0;
							$this->tptribicms = $this->classfiscal->gettptribicms();
							$this->motivodesoneracao = $this->classfiscal->getmotivodesoneracao();
						}else{
							$this->aliqicms = 0;
							$this->tptribicms = $this->classfiscal->gettptribicms();
							$this->redicms = 0;
							$this->aliqiva = 0;
						}
					}
				}
				// Se for para fora do estado
			}else{
				$recalcular = TRUE;
				if(in_array($this->operacaonota->getoperacao(), array("EX", "VD")) && substr($this->natoperacao_in->getnatoperacao(), 0, 1) == "7"){
					if($this->classfiscal->gettptribicms() == "T"){
						$this->tptribicms = "N";
					}else{
						$this->tptribicms = $this->classfiscal->gettptribicms();
					}
					$this->aliqicms = 0;
					$this->redicms = 0;
					$this->aliqiva = 0;
					$this->valorpauta = 0;
					$recalcular = FALSE;
				}elseif(in_array($this->operacaonota->getoperacao(), array("VD","CP")) && in_array(substr($this->classfiscal->getcodcst(), 0, 1), array("1", "2","7"))){
					$aliqicms_inter = 4;
				}elseif(($this->operacaonota->gettipo() == "E" && $this->operacaonota->getoperacao() != "DC") || $this->operacaonota->getoperacao() == "DF"){
					$aliqicms_inter = $this->icms_interestadual[$estado_parceiro->getregiao()][$estado_estabelecimento->getregiao()];
				}elseif(in_array($this->operacaonota->getoperacao(), array("VD")) && $this->parceiro->getcontribuinteicms() == "N" && !$this->guiagnre){
					/*
					if($this->classfiscal->gettptribicms() == "F"){
						$this->tptribicms = "F";
						$this->aliqicms = 0;
						$this->redicms = 0;
					}else*/
					//if($this->classfiscal->gettptribicms() != "I"){
						if($this->estabelecimento->getregimetributario() == "1"){
							if($this->natoperacao_in->getcodcf() > 0 && $this->natoperacao_in->getcodcf() != $this->classfiscal->getcodcf()){
								$this->classfiscal = $this->carregar_objeto("classfiscal", $this->natoperacao_in->getcodcf());
							}
							$this->tptribicms = $this->classfiscal->gettptribicms();
							$this->aliqicms = $this->classfiscal->getaliqicms();
							$this->redicms = 0;
						}elseif($this->classfiscal->gettptribicms() == "I"){
							// foi mechido para o cliente bahia na nota que tem que calcular difal OS 4222
							$this->tptribicms = "I";
							$this->aliqicms = 0;
							$this->redicms = 0;
						}else{
							$aliqicms_inter = $this->icms_interestadual[$estado_estabelecimento->getregiao()][$estado_parceiro->getregiao()];
							$this->tptribicms = "T";
							$this->aliqicms = $aliqicms_inter;
						}
						//$recalcular = TRUE;
					/*
					}elseif($this->classfiscal->gettptribicms() == "I"){
						$this->tptribicms = "I";
						$this->aliqicms = 0;
						$this->redicms = 0;
					}
					*
					*/
					$this->aliqiva = 0;
					$this->valorpauta = 0;
					$recalcular = FALSE;
				}else{
					$aliqicms_inter = $this->icms_interestadual[$estado_estabelecimento->getregiao()][$estado_parceiro->getregiao()];
				}
				if($recalcular){
					//if(in_array($this->classfiscal->gettptribicms(), array("T", "R")) || ($this->classfiscal->gettptribicms() == "F" && (($convenioicms == "N" || $this->parceiro->getcontribuinteicms() === "N" )&& !$this->guiagnre))){
					if(in_array($this->classfiscal->gettptribicms(), array("T", "R")) || ($this->classfiscal->gettptribicms() == "F" && (($convenioicms == "N" || ($this->operacaonota->getparceiro() == "C" && $this->parceiro->getcontribuinteicms() === "N") )&& !$this->guiagnre))){
						if(strlen($this->natoperacao_in->getcodcf()) == 0){
							$this->tptribicms = "T";
							$this->aliqicms = $aliqicms_inter;
							$this->aliqiva = 0;
						}else{
							$classfiscal = $this->carregar_objeto("classfiscal", $this->natoperacao_in->getcodcf());
							$this->tptribicms = $classfiscal->gettptribicms();
							$this->aliqicms = $classfiscal->getaliqicms();
							$this->aliqiva = $classfiscal->getaliqiva();
							$this->redicms = $classfiscal->getaliqredicms();
						}
					}elseif($this->classfiscal->gettptribicms() == "F" && ($convenioicms == "S" || $this->guiagnre)){
						$estadotributo = $this->carregar_objeto("estadotributo", array($estado_parceiro->getuf(), $this->estabelecimento->getregimetributario(), $this->produto->getcodproduto()));
						$this->tptribicms = "F";
						if(substr($this->classfiscal->getcodcst(),1,2) == "30"){
							$this->aliqicms = 0;
							$this->aliqicmsdesoneracao = $aliqicms_inter;
							$this->motivodesoneracao = $this->classfiscal->getmotivodesoneracao();
						}else{
							$this->aliqicms = $aliqicms_inter;
							$this->aliqicmsdesoneracao = 0;
							$this->motivodesoneracao = "";
						}
						if(strlen($estadotributo->getaliqiva()) > 0){
							$this->aliqiva = $estadotributo->getaliqiva();
						}else{
							if(is_object($ncmestado) && $ncmestado->getaliqiva() > 0){
								$this->aliqiva = $ncmestado->getaliqiva();
							}else{
								$this->aliqiva = (strlen($this->produto->getaliqiva()) > 0 ? $this->produto->getaliqiva() : $this->classfiscal->getaliqiva());
							}
//							if(!(is_object($ncmestado) && $ncmestado->getaliqiva() > 0) && $ncmestado->getajustariva() == "S"){
							if(is_object($ncmestado) && $ncmestado->getaliqiva() > 0 && $ncmestado->getajustariva() == "S"){
								if($this->operacaonota->gettipo() == "E"){
									$this->aliqiva = (1 + $this->aliqiva / 100) / (1 - $estado_estabelecimento->getaliqicms() / 100) * (1 - $aliqicms_inter / 100);
								}else{
									$this->aliqiva = (1 + $this->aliqiva / 100) / (1 - $estado_parceiro->getaliqicms() / 100) * (1 - $aliqicms_inter / 100);
								}
								$this->aliqiva = floor(($this->aliqiva - 1) * 10000) / 100;
							}
							$this->valorpauta= (isset($ncmestado) && strlen($ncmestado->getvalorpauta()) > 0 ? $ncmestado->getvalorpauta() : $this->classfiscal->getvalorpauta());
							if($this->valorpauta > 0){
								$this->aliqiva = 0;
							}
						}
					}else{
						$this->tptribicms = $this->classfiscal->gettptribicms();
						$this->aliqicms = 0;
						$this->aliqiva = 0;
					}
					$this->redicms = 0;
					if($this->classfiscal->gettptribicms() == "F"){
						if($this->operacaonota->getparceiro() == "F" && $this->parceiro->getmodosubsttrib() == "0"){
							$this->tptribicms = $this->classfiscal->gettptribicms();
							$this->aliqiva = number_format((strlen($this->estadotributo->getaliqiva()) > 0 ? $this->estadotributo->getaliqiva() : (strlen($this->produto->getaliqiva()) > 0 ? $this->produto->getaliqiva() : $this->classfiscal->getaliqiva())), 4, ",", "");
						}
					}
				}
			}

			$this->percdescto = ($this->percdescto > 0 ? $this->percdescto : 0);
			$this->valdescto = 0;
			$this->percacresc = 0;
			$this->valacresc = 0;
			$this->percfrete = 0;
			$this->valfrete = 0;
			$this->percseguro = 0;
			$this->valseguro = 0;

			// Verifica se deve zerar o IVA
			if(substr($this->classfiscal->getcodcst(), 0, 1) == "2"){ // CST que comeca com 2: ja foi pago os impostos da importacao
				$this->aliqiva = 0;
			}
		}

		// Natureza de operacao
		if($ufparceiro != $this->estabelecimento->getuf() && strlen($this->natoperacao_in->getnatoperacaointer()) > 0){
			$this->natoperacao_out = $this->carregar_objeto("natoperacao", $this->natoperacao_in->getnatoperacaointer());
			if($this->tptribicms == "F" && strlen($this->natoperacao_out->getnatoperacaosubst()) > 0){
				$this->natoperacao_out = $this->carregar_objeto("natoperacao", $this->natoperacao_out->getnatoperacaosubst());
			}
		}elseif($this->tptribicms == "F" && strlen($this->natoperacao_in->getnatoperacaosubst()) > 0){
			$this->natoperacao_out = $this->carregar_objeto("natoperacao", $this->natoperacao_in->getnatoperacaosubst());
		}else{
			$this->natoperacao_out = $this->natoperacao_in;
		}

		if(!is_object($itnotafiscal)){
			// Verifica se deve zerar a aliquota de ICMS
			if($this->natoperacao_out->getgeraicms() == "N" || ($this->tptribicms == "F" && $this->operacaonota->getparceiro() == "C" && ($this->parceiro->gettppessoa() == "F" || $this->parceiro->getcontribuinteicms() == "N"))){
				if(in_array($this->tptribicms, array("T", "R"))){
					$this->tptribicms = "N";
				}
				$this->aliqicms = 0;
				$this->redicms = 0;
				$this->aliqiva = 0;
				$this->valorpauta = 0;
			}
			//Jesus - coloquei isto aqui porque na NF de perda de estoque o sistema estava trazendo aliquota icms zero e o iva com valor

			if($this->tptribicms == "F" && $this->aliqicms == 0 && $this->aliqicmsdesoneracao == 0){
				$this->redicms = 0;
				$this->aliqiva = 0;
				$this->valorpauta = 0;
			}

			// IPI
			if($this->natoperacao_out->getgeraipi() == "S"){
				$ipi = $this->carregar_objeto("ipi", $this->produto->getcodipi());
				$this->tipoipi = $ipi->gettptribipi();
				if($ipi->gettptribipi() == "F"){
					$this->valipi = $ipi->getaliqipi();
					$this->percipi = 0;
				}else{
					$this->valipi = 0;
					$this->percipi = $ipi->getaliqipi();
				}
			}else{
				$this->tipoipi = "F";
				$this->valipi = 0;
				$this->percipi = 0;
			}
		}
	}

	function carregar_classfiscal(){
		$this->estadotributo = $this->carregar_objeto("estadotributo", array($this->estabelecimento->getuf(), $this->estabelecimento->getregimetributario(), $this->produto->getcodproduto()));

		// Classificacao fiscal de entrada
		if(strlen($this->estadotributo->getcodcfnfe()) > 0){
			$codcfnfe = $this->estadotributo->getcodcfnfe();
		}else{
			if($this->natoperacao_in->getusartributacaoncm() === "S"){
				$codcfnfe = $this->ncm->getcodcfnfe();
			}else{
				$codcfnfe = $this->produto->getcodcfnfe();
			}
		}

		// Classificacao fiscal de saida
		if(strlen($this->estadotributo->getcodcfnfs()) > 0){
			$codcfnfs = $this->estadotributo->getcodcfnfs();
		}else{
			if($this->natoperacao_in->getusartributacaoncm() === "S"){
				$codcfnfs = $this->ncm->getcodcfnfs();
			}else{
				$codcfnfs = $this->produto->getcodcfnfs();
			}
		}

		switch($this->operacaonota->getparceiro()){
			case "C":
				$this->classfiscal = $this->carregar_objeto("classfiscal", $codcfnfs);
				break;
			case "F":
				$this->classfiscal = $this->carregar_objeto("classfiscal", $codcfnfe);
				break;
			case "E":
				if($this->operacaonota->gettipo() == "E"){
					$this->classfiscal = $this->carregar_objeto("classfiscal", $codcfnfe);
				}else{
					$this->classfiscal = $this->carregar_objeto("classfiscal", $codcfnfs);
				}
				break;
		}
	}

	private function carregar_objeto($tabela, $chave){
		$chave_str = (is_array($chave) ? implode(";", $chave) : $chave);

		if(!isset($this->arr_objeto[$tabela])){
			$this->arr_objeto[$tabela] = array();
		}

		if(!isset($this->arr_objeto[$tabela][$chave_str])){
			$this->arr_objeto[$tabela][$chave_str] = objectbytable($tabela, $chave, $this->con);
		}

		return $this->arr_objeto[$tabela][$chave_str];
	}

	function gettptribicms(){
		return $this->tptribicms;
	}

	function getaliqicms($format = FALSE){
		return ($format ? number_format($this->aliqicms, 4, ",", "") : $this->aliqicms);
	}

	function getredicms($format = FALSE){
		return ($format ? number_format($this->redicms, 4, ",", "") : $this->redicms);
	}

	function getaliqiva($format = FALSE){
		return ($format ? number_format($this->aliqiva, 4, ",", "") : $this->aliqiva);
	}

	function getvalorpauta($format = FALSE){
		//return ($format ? number_format($this->classfiscal->getvalorpauta(), 4, ",", "") : $this->classfiscal->getvalorpauta());
		return ($format ? number_format($this->valorpauta, 4, ",", "") : $this->valorpauta);
	}

	function gettipoipi(){
		return $this->tipoipi;
	}

	function getvalipi($format = FALSE){
		return ($format ? number_format($this->valipi, 4, ",", "") : $this->valipi);
	}

	function getpercipi($format = FALSE){
		return ($format ? number_format($this->percipi, 4, ",", "") : $this->percipi);
	}

	function getnatoperacao(){
		return $this->natoperacao_out;
	}

	function getclassfiscal(){
		return $this->classfiscal;
	}

	function getpreco($format = FALSE){
		return ($format ? number_format($this->preco, 4, ",", "") : $this->preco);
	}

	function getpercdescto($format = FALSE){
		return ($format ? number_format($this->percdescto, 4, ",", "") : $this->percdescto);
	}

	function getvaldescto($format = FALSE){
		return ($format ? number_format($this->valdescto, 4, ",", "") : $this->valdescto);
	}

	function getpercacresc($format = FALSE){
		return ($format ? number_format($this->percacresc, 4, ",", "") : $this->percacresc);
	}

	function getvalacresc($format = FALSE){
		return ($format ? number_format($this->valacresc, 4, ",", "") : $this->valacresc);
	}

	function getpercfrete($format = FALSE){
		return ($format ? number_format($this->percfrete, 4, ",", "") : $this->percfrete);
	}

	function getvalfrete($format = FALSE){
		return ($format ? number_format($this->valfrete, 4, ",", "") : $this->valfrete);
	}

	function getpercseguro($format = FALSE){
		return ($format ? number_format($this->percseguro, 4, ",", "") : $this->percseguro);
	}

	function getvalseguro($format = FALSE){
		return ($format ? number_format($this->valseguro, 4, ",", "") : $this->valseguro);
	}

	function getaliqfcpst($format = FALSE){
		return ($format ? number_format($this->aliqfcpst, 4, ",", "") : $this->aliqfcpst);
	}

	function getaliqfcpufdest($format = FALSE){
		return ($format ? number_format($this->aliqfcpufdest, 4, ",", "") : $this->aliqfcpufdest);
	}

	function getaliqicmsufdest($format = FALSE){
		return ($format ? number_format($this->aliqicmsufdest, 4, ",", "") : $this->aliqicmsufdest);
	}

	function getaliqicmsinter($format = FALSE){
		return ($format ? number_format($this->aliqicmsinter, 4, ",", "") : $this->aliqicmsinter);
	}

	function getvenda_interestadual(){
		return $this->venda_interestadual;
	}

	function getcalcularcfp(){
		return $this->calcularfcp;
	}

	function getaliqicmsdesoneracao($format = FALSE){
		return ($format ? number_format($this->aliqicmsdesoneracao, 4, ",", "") : $this->aliqicmsdesoneracao);
	}

	function getredicmsdesoneracao($format = FALSE){
		return ($format ? number_format($this->redicmsdesoneracao, 4, ",", "") : $this->redicmsdesoneracao);
	}

	function getmotivodesoneracao(){
		return $this->motivodesoneracao;
	}

	function setcodestabelec($codestabelec){
		$estabelecimento = $this->carregar_objeto("estabelecimento", $codestabelec);
		$this->setestabelecimento($estabelecimento);
	}

	function setconnection(Connection $con){
		$this->con = $con;
	}

	function setestabelecimento(Estabelecimento $estabelecimento){
		$this->estabelecimento = $estabelecimento;
		$this->emitente = $this->carregar_objeto("emitente", $this->estabelecimento->getcodemitente());
		$this->ufestabelecimento = $this->carregar_objeto("estado", $this->estabelecimento->getuf());
	}

	function setguiagnre($guiagnre){
		if(is_bool($guiagnre)){
			$this->guiagnre = $guiagnre;
		}
	}

	function setidnotafiscalref($idnotafiscalref){
		$this->idnotafiscalref = $idnotafiscalref;
	}

	function setnatoperacao($natoperacao){
		if(is_string($natoperacao)){
			$natoperacao = $this->carregar_objeto("natoperacao", $natoperacao);
		}
		$this->natoperacao_in = $natoperacao;
	}

	function setoperacao($operacao){
		$operacaonota = $this->carregar_objeto("operacaonota", $operacao);
		$this->setoperacaonota($operacaonota);
	}

	function setoperacaonota(OperacaoNota $operacaonota){
		$this->operacaonota = $operacaonota;
	}


	function setparceiro(Cadastro $parceiro){
		$this->parceiro = $parceiro;
	}

	function setproduto(Produto $produto){
		$this->produto = $produto;
		$this->ncm = $this->carregar_objeto("ncm", $produto->getidncm());
	}

	function settabelapreco($tabelapreco){
		$this->tabelapreco = $tabelapreco;
	}

	function settipopreco($tipopreco){
		//$this->tipopreco = $tipopreco;
		switch($tipopreco){
			case "A": $this->settabelapreco($this->carregar_objeto("tabelapreco", 2)); break;
			case "V": $this->settabelapreco($this->carregar_objeto("tabelapreco", 1)); break;
		}
	}

}
