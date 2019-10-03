<?php

require_once("../def/require_php.php");
require_once("../class/pedido.class.php");
require_once("../class/itpedido.class.php");
require_once("../class/tributacaoproduto.class.php");
require_once("../class/itemcalculo.class.php");
require_once("../class/cliente.class.php");
require_once("../class/cidade.class.php");
require_once("../class/paramnotafiscal.class.php");
require_once("../class/condpagto.class.php");
require_once("../class/produto.class.php");
require_once("../class/produtoean.class.php");
require_once("../class/condpagto.class.php");
require_once("../class/departamento.class.php");
require_once("../class/grupoprod.class.php");
require_once("../class/subgrupo.class.php");
require_once("../class/classfiscal.class.php");
require_once("../class/piscofins.class.php");
require_once("../class/embalagem.class.php");
require_once("../class/ncm.class.php");
require_once("../class/ncmestado.class.php");
require_once("../class/estado.class.php");
require_once("../class/piscofins.class.php");
require_once("../class/lancamento.class.php");


set_time_limit(0);

class ImportarPedidoSisautom{
	const OPERACAO = "VD";
	const TIPOPARCEIRO = "C";
	const TIPOPRECO = "V";
	const TABELAPRECO = "1";
	const UFESTABELEC = "SP";
	const ISENTO = "ISENTO";
	const ALIQPIS = 1.65;
	const ALIQCOFINS = 7.60;

	private $connection;
	private $icms_interestadual = array("N" => array("N" => 12.00, "S" => 12.00), "S" => array("N" => 7.00, "S" => 12.00));

	function __construct(Connection $con){
		$this->connection = $con;
	}

	public function importarpedido($numero_pedido){
		$local_pedido = "../sisautom";
		$arr_arquivo = scandir($local_pedido);
		$arquivo = "PA{$numero_pedido}.TXT";

		if(file_exists($local_pedido.DIRECTORY_SEPARATOR.$arquivo)){
			$destacar_frete = FALSE;
			$total_frete = 0;
			$qtde_itens = 0;
			$arr_classfiscalp = array();
			$this->connection->start_transaction();
			$gravar_pedido = FALSE;
			$arquivo_pedido = file($local_pedido.DIRECTORY_SEPARATOR.$arquivo);
			foreach($arquivo_pedido as $index => $linha_arquivo){
				$arr_linha = explode(";", $linha_arquivo);
				$tipo_registro = $arr_linha[0];
				switch($tipo_registro){
					case "01":	//cadastro do cliente
						$tipocadastro = $arr_linha[2];
						$cpfcnpj = $arr_linha[7];
						$rgie = trim($arr_linha[8]);
						$ufparceiro = $arr_linha[15];
						if(strlen($rgie)){
							$rgie = formatarIE($ufparceiro, $rgie);
							if($rgie === FALSE){
								$_SESSION["ERROR"] = "Inscrição estadual do cliente é inválida.";
								return FALSE;
							}
						}
						if(strtoupper($rgie) == $this::ISENTO){
							$contribuinte = "N";
						}else{
							$contribuinte = "S";
						}
						if($tipocadastro == "J"){
							$cpfcnpj = preg_replace("#([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})#","\\1.\\2.\\3/\\4-\\5",$cpfcnpj);
						}else{
							$cpfcnpj = preg_replace("#([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{2})#","\\1.\\2.\\3-\\4",$cpfcnpj);
						}
						$cliente = new Cliente();
						$cliente->setconnection($this->connection);
						$cliente->settppessoa($tipocadastro);
						$cliente->setcpfcnpj($cpfcnpj);

						$arr_cliente = object_array($cliente);
						$cliente_exist = count($arr_cliente) > 0;
						if(!$cliente_exist){
							$codparceiro = $arr_linha[1];
							$cliente = new Cliente();
							$cliente->setcodcliente($codparceiro);
							$cliente->setenviaemailmkt("N");
							$cliente->setorgaopublico("N");
							$cliente->setcodstatus("1");
							if($tipocadastro == "J"){
								if($arr_linha[23] == "S"){
									$cliente->setcontribuinteicms("N");
								}else{
									$cliente->setcontribuinteicms("S");
								}
							}else{
								$cliente->setcontribuinteicms("N");
							}
						}else{
							$cliente = array_shift($arr_cliente);
						}
						$cliente->settppessoa($tipocadastro);
						$cliente->setdtinclusao(convert_date($arr_linha[3],"d/m/y", "Y-m-d"));
						$cliente->setdatalog($arr_linha[4]);
						$cliente->setrazaosocial($arr_linha[5]);
						if(strlen(trim($arr_linha[6])) == 0){
							$cliente->setnome($arr_linha[5]);
						}else{
							$cliente->setnome($arr_linha[6]);
						}
						$cliente->setcpfcnpj($cpfcnpj);
						$cliente->setrgie($rgie);
						$cliente->setcontribuinteicms($contribuinte);

						//Endereço residencial
						$cidade = new Cidade();
						$cidade->setconnection($this->connection);
						$cidade->setcodoficial($arr_linha[14]);
						$arr_cidade = object_array($cidade);
						$codcidade = $arr_cidade[0]->getcodcidade();

						$ender = $arr_linha[9];
						/*
						$posnumero = strpos($ender, ",");
						if($posnumero > 0){
							$ender = substr($ender, 0, $posnumero);
						}

						$posnumero = strpos($ender, ";");
						if($posnumero > 0){
							$ender = substr($ender, 0, $posnumero);
						}
						*/
						$cliente->setenderres($ender);
						$cliente->setnumerores($arr_linha[10]);
						$cliente->setbairrores($arr_linha[12]);
						$cliente->setcomplementores($arr_linha[11]);
						$cliente->setcodcidaderes($codcidade);
						$cliente->setufres($arr_linha[15]);
						$cliente->setcepres($arr_linha[16]);
						$cliente->setfoneres("({$arr_linha[17]}){$arr_linha[18]}");

						//endereço faturamento
						$cliente->setenderfat($arr_linha[9]);
						$cliente->setcomplementofat("");
						$cliente->setnumeroent($arr_linha[10]);
						$cliente->setcomplementofat($arr_linha[11]);
						$cliente->setbairrofat($arr_linha[12]);
						$cliente->setcodcidadefat($codcidade);
						$cliente->setuffat($arr_linha[15]);
						$cliente->setcepfat($arr_linha[16]);
						$cliente->setfonefat("({$arr_linha[17]}){$arr_linha[18]}");

						//endereço entrega
						$cliente->setenderent($arr_linha[9]);
						$cliente->setcomplementoent("");
						$cliente->setnumeroent($arr_linha[10]);
						$cliente->setcomplementoent($arr_linha[11]);
						$cliente->setbairroent($arr_linha[12]);
						$cliente->setcodcidadeent($codcidade);
						$cliente->setufent($arr_linha[15]);
						$cliente->setcepent($arr_linha[16]);
						$cliente->setfoneent("({$arr_linha[17]}){$arr_linha[18]}");

						$cliente->setcontato($arr_linha[20]);
						$cliente->setemail($arr_linha[21]);
						$cliente->setemailnfe($arr_linha[22]);

						if(!$cliente->save()){
							$this->connection->rollback();
							return FALSE;
						}

						$operacao_interestadual = $arr_linha[15] != "SP";
						break;
					case "02":	//cadastro do pedido
						$codestabelec = $arr_linha[8];
						$parametrosnf = new ParamNotaFiscal($codestabelec, $this::OPERACAO);
						$codigostatus = $arr_linha[11];
						$pedidocliente = $arr_linha[12];
						$numerotid = $arr_linha[13];
						if(!in_array($codigostatus, array(10,13))){
							$_SESSION["ERROR"] = "Pedido não liberado para faturamento";
							return FALSE;
						}
						$totalpedido = (float)$arr_linha[7];
						$pedido = new Pedido();
						$numpedido = $arr_linha[1];
						$pedido->setconnection($this->connection);
						$pedido->setcodestabelec($codestabelec);
						$pedido->setoperacao($this::OPERACAO);
						$pedido->setnumpedido($numpedido);
						$arr_pedido = object_array($pedido);
						$pedido_exist = count($arr_pedido) > 0;
						$gerarfinanceiro = TRUE;
						if($pedido_exist){
							$pedido = array_shift($arr_pedido);
							if(in_array($pedido->getstatus(),array("P","L"))){
								$lancamento = new Lancamento();
								$lancamento->setconnection($this->connection);
								$lancamento->settipoparceiro($this::TIPOPARCEIRO);
								$lancamento->setcodparceiro($cliente->getcodcliente());
								$lancamento->setcodestabelec($codestabelec);
								$lancamento->setnumpedido($arr_linha[1]);
								$arr_lancamento = object_array($lancamento);
								$lancamento = array_shift($arr_lancamento);

								$gerarfinanceiro = isset($lancamento) && $lancamento->getprevreal() == "P";
								$lancamento = NULL;
								$pedido->delete();
								$pedido = new Pedido();
								$numpedido = $arr_linha[1];
								$pedido->setconnection($this->connection);
								$pedido->setcodestabelec($codestabelec);
								$pedido->setoperacao($this::OPERACAO);
								$pedido->setnumpedido($numpedido);
							}else{
								if($pedido->getstatus() == "C"){
									$statuspedido = "Cancelado";
								}else if($pedido->getstatus() == "L"){
									$statuspedido = "em Analise";
								}else if($pedido->getstatus() == "A"){
									$statuspedido = "Atendido";
								}
								$_SESSION["ERROR"] = "Pedido ja Importado e {$statuspedido}";
								return FALSE;
							}
						}
						switch($tipocadastro){
							case "J":
								if($operacao_interestadual){
									if($cliente->getcontribuinteicms() == "S"){
										if(!$gerarfinanceiro){
											if($codestabelec == "1"){
												$pedido->setnatoperacao("6.10299");
											}else{
												$pedido->setnatoperacao("6.10298");
											}
										}else{
											$pedido->setnatoperacao($parametrosnf->getnatoperacaopjex());
										}
									}else{
										if(!$gerarfinanceiro){
											if($codestabelec == "1"){
												$pedido->setnatoperacao("6.10899");
											}else{
												$pedido->setnatoperacao("6.10898");
											}
										}else{
											$pedido->setnatoperacao($parametrosnf->getnatoperacaopfex());
										}
									}
								}else{
									if(!$gerarfinanceiro){
										if($codestabelec == "1"){
											$pedido->setnatoperacao("5.10299");
										}else{
											$pedido->setnatoperacao("5.10297");
										}
									}else{
										$pedido->setnatoperacao($parametrosnf->getnatoperacaopjin());
									}
								}
								break;
							case "F":
								if($operacao_interestadual){
									if(!$gerarfinanceiro){
										if($codestabelec == "1"){
											$pedido->setnatoperacao("6.10899");
										}else{
											$pedido->setnatoperacao("6.10898");
										}
									}else{
										$pedido->setnatoperacao($parametrosnf->getnatoperacaopfex());
									}
								}else{
									if(!$gerarfinanceiro){
										if($codestabelec == "1"){
											$pedido->setnatoperacao("5.10299");
										}else{
											$pedido->setnatoperacao("5.10298");
										}
									}else{
										$pedido->setnatoperacao($parametrosnf->getnatoperacaopfin());
									}
								}
								break;
						}

						$natoperacao = objectbytable("natoperacao", $pedido->getnatoperacao(), $this->connection);
						$pedido->setnumerotid($numerotid);
						$pedido->setcodtabela($this::TABELAPRECO);
						$pedido->settipoparceiro($this::TIPOPARCEIRO);
						$pedido->setcodparceiro($cliente->getcodcliente());
						//$pedido->setdtemissao(convert_date($arr_linha[2], "d/m/y", "d-m-Y"));
						$pedido->setdtemissao(date("Y-m-d"));
						$pedido->setdtentrega(date("Y-m-d"));
						$pedido->setcodespecie($arr_linha[5]);
						if($codigostatus == "13"){
							$pedido->setstatus("L");
						}

						$arr_parcelas = explode("/", $arr_linha[6]);
						$arr_parcelas = array_merge(array_filter($arr_parcelas));
						$fcondpag = implode("/", $arr_parcelas);

						$condpagto = new CondPagto();
						$condpagto->setconnection($this->connection);
						$condpagto->setdescricao($fcondpag);
						$arr_condpagto = object_array($condpagto);

						if(count($arr_condpagto) > 0){
							$condpagto = $arr_condpagto[0];
						}else{
							$perc_parcela = 100 / count($arr_parcelas);
							$condpagto->settipo("DD");
							$condpagto->setdescricao($fcondpag);
							foreach($arr_parcelas as $idx => $parcela){
								call_user_func(array($condpagto, "setdia".($idx + 1)), $parcela);
								call_user_func(array($condpagto, "setpercdia".($idx + 1)), $perc_parcela);
							}
							if(!$condpagto->save()){
								$this->connection->rollback();
								gravar_erro($local_pedido.DIRECTORY_SEPARATOR.$arquivo, $arquivo_pedido);
								die();
							}
						}
						$destacar_frete = $arr_linha[10] == "S";
						$total_frete = value_numeric($arr_linha[9]);
						$pedido->setcodcondpagto($condpagto->getcodcondpagto());
						$pedido->setmodfrete("0");
						$pedido->flag_itpedido(TRUE);
						$gravar_pedido = TRUE;
						break;
					case "03":	//cadastro da secão (deparatamento)
						$departamento = new Departamento();
						$departamento->setconnection($this->connection);
						$departamento->setnome($arr_linha[1]);
						$arr_departamento = object_array($departamento);
						$dep_exist = count($arr_departamento) > 0;
						if(!$dep_exist){
							$departamento->setcoddepto(NULL);
							$departamento->setnome($arr_linha[1]);
							if(!$departamento->save()){
								$this->connection->rollback();
								return FALSE;
							}
						}else{
							$departamento = $arr_departamento[0];
						}
						break;
					case "04":	//cadastro da categoria (grupo e subgrupo)
						$grupoprod = new GrupoProd();
						$grupoprod->setconnection($this->connection);
						$grupoprod->setcoddepto($departamento->getcoddepto());
						$grupoprod->setdescricao($arr_linha[2]);
						$arr_grupoprod = object_array($grupoprod);
						$dep_exist = count($arr_grupoprod) > 0;
						if(!$dep_exist){
							$grupoprod->setcoddepto($departamento->getcoddepto());
							$grupoprod->setdescricao($arr_linha[2]);
							if(!$grupoprod->save()){
								$this->connection->rollback();
								return FALSE;
							}
							$subgrupo = new SubGrupo();
							$subgrupo->setconnection($this->connection);
							$subgrupo->setcoddepto($departamento->getcoddepto());
							$subgrupo->setcodgrupo($grupoprod->getcodgrupo());
							$subgrupo->setdescricao($arr_linha[2]);
							if(!$subgrupo->save()){
								$this->connection->rollback();
								return FALSE;
							}
						}else{
							$grupoprod = $arr_grupoprod[0];

							$subgrupo = new SubGrupo();
							$subgrupo->setconnection($this->connection);
							$subgrupo->setcoddepto($departamento->getcoddepto());
							$subgrupo->setcodgrupo($grupoprod->getcodgrupo());
							$subgrupo->setdescricao($grupoprod->getdescricao());
							$arr_subgrupo = object_array($subgrupo);
							if(count($arr_subgrupo) > 0){
								$subgrupo = $arr_subgrupo[0];
							}else{
								$subgrupo = new SubGrupo();
								$subgrupo->setconnection($this->connection);
								$subgrupo->setcoddepto($departamento->getcoddepto());
								$subgrupo->setcodgrupo($grupoprod->getcodgrupo());
								$subgrupo->setdescricao($arr_linha[2]);
								if(!$subgrupo->save()){
									$this->connection->rollback();
									return FALSE;
								}
							}
						}
						break;
					case "05":	//cadastro do produto
						$codproduto = (int)$arr_linha[1];
						$codncm = $arr_linha[4];
						$tipotributacao = $arr_linha[5];
						$aliquotaicms = $arr_linha[6];
						$reducaoicms = $arr_linha[7];
						$aliquotaiva = $arr_linha[8];
						$tipopiscof = $arr_linha[15];
						$ean = $arr_linha[16];
						$produto = new Produto();
						$produto->setconnection($this->connection);
						$produto->setcodproduto($codproduto);
						//$produto->setidncm($value);
						$arr_produto = object_array($produto);
						$produto_exist = count($arr_produto) > 0;
						$atualizar_inf_fiscais = TRUE;
						if($produto_exist){
							$produto = $arr_produto[0];
							$atualizar_inf_fiscais = FALSE;
						}else{
							$produto->setdescricao(utf8_encode($arr_linha[2]));
							$produto->setdescricaofiscal(utf8_encode($arr_linha[2]));
							$produto->setcoddepto($departamento->getcoddepto());
							$produto->setcodgrupo($grupoprod->getcodgrupo());
							$produto->setcodsubgrupo($subgrupo->getcodsubgrupo());
						}

						$piscofe = new PisCofins();
						$piscofs = new PisCofins();
						$piscofe->setconnection($this->connection);
						$piscofe->settipo($tipopiscof);
						$piscofe->setcodcst(($tipopiscof == "T" ? "50" : "71"));
						$piscofe->setaliqcofins(($tipopiscof == "T" ? $this::ALIQCOFINS : 0.00));
						$piscofe->setaliqpis(($tipopiscof == "T" ? $this::ALIQPIS : 0.00));
						$arr_piscofe = object_array($piscofe);
						if(count($arr_piscofe) > 0){
							$piscofe = $arr_piscofe[0];
						}else{
							$piscofe->setcodccs("01");
							$piscofe->setdescricao("PIS(".$this::ALIQPIS.")% COFINS(".$this::ALIQCOFINS.")%");
							if(!$piscofe->save()){
								$this->connection->rollback();
								return FALSE;
							}
						}

						$piscofs->setconnection($this->connection);
						$piscofs->settipo($tipopiscof);
						$piscofs->setcodcst(($tipopiscof == "T" ? "01" : "07"));
						$piscofs->setaliqcofins(($tipopiscof == "T" ? $this::ALIQCOFINS : 0.00));
						$piscofs->setaliqpis(($tipopiscof == "T" ? $this::ALIQPIS : 0.00));
						$arr_piscofs = object_array($piscofs);
						if(count($arr_piscofs) > 0){
							$piscofs = $arr_piscofs[0];
						}else{
							$piscofs->setcodccs("01");
							$piscofs->setdescricao("PIS(".$this::ALIQPIS.")% COFINS(".$this::ALIQCOFINS.")%");
							if(!$piscofs->save()){
								$this->connection->rollback();
								return FALSE;
							}
						}

						$classfiscalnfs = new ClassFiscal();
						$classfiscalnfs->setconnection($this->connection);
						$classfiscalnfs->settptribicms($tipotributacao);
						$classfiscalnfs->setaliqicms($aliquotaicms);
						$classfiscalnfs->setaliqredicms($reducaoicms);

						$cst = $this->getcsticms($classfiscalnfs, $arr_linha[13]);

						$arr_classfiscal = object_array($classfiscalnfs);
						$classfiscal_exist = count($arr_classfiscal) > 0;

						if($classfiscal_exist){
							$classfiscalnfs = $arr_classfiscal[0];
						}else{
							$classfiscalnfs->setcodcf(NULL);
							$classfiscalnfs->setforcarcst("N");
							$classfiscalnfs->setdescricao("{$arr_linha[5]} {$cst} {$aliquotaicms}% {$reducaoicms}");
							if(!$classfiscalnfs->save()){
								$this->connection->rollback();
								return FALSE;
							}
						}

						$arr_classfiscalp[$codproduto] = $classfiscalnfs;

						$ncm = new Ncm();
						$ncm->setconnection($this->connection);
						$ncm->setcodigoncm($codncm);
						$arr_ncm = object_array($ncm);
						$ncm_exist = count($arr_ncm) > 0;
						if(!$ncm_exist){
							$ncm->setcodcfnfe($classfiscalnfs->getcodcf());
							$ncm->setcodcfnfs($classfiscalnfs->getcodcf());
							$ncm->setcodcfpdv($classfiscalnfs->getcodcf());
							$ncm->setcodigoncm($codncm);
							$ncm->setdescricao($codncm);
							$ncm->setcodipi("1");
							$ncm->setcodpiscofinsent($piscofe->getcodpiscofins()); //6
							$ncm->setcodpiscofinssai($piscofs->getcodpiscofins());	//4
							if(!$ncm->save()){
								$this->connection->rollback();
								return FALSE;
							}
						}else{
							$ncm = $arr_ncm[0];
						}

						if($atualizar_inf_fiscais){
							$produto->setatualizancm("N");
							$produto->setcodcfnfe($classfiscalnfs->getcodcf());
							$produto->setcodcfnfs($classfiscalnfs->getcodcf());
							$produto->setcodcfpdv($classfiscalnfs->getcodcf());
							$produto->setidncm($ncm->getidncm());
							$produto->setaliqiva($aliquotaiva);
							$produto->setcodipi("1");
							$produto->setcodpiscofinsent($piscofe->getcodpiscofins()); //6
							$produto->setcodpiscofinssai($piscofs->getcodpiscofins()); //4
							$produto->setpesobruto($linha_arquivo[14]);
							$produto->setpesoliq($linha_arquivo[14]);
							$produto->setcodembalcpa($arr_linha[11]);
							$produto->setcodembalvda($arr_linha[12]);
						}
						if(!$produto->save()){
							$this->connection->rollback();
							return FALSE;
						}

						if(strlen(trim($ean)) > 0){
							$produtoean = new ProdutoEan();
							$produtoean->setconnection($this->connection);
							$produtoean->setcodean($ean);
							$arr_produtoean = object_array($produtoean);
							if(count($arr_produtoean) > 0){
								$produtoean = $arr_produtoean[0];
								if($produtoean->getcodproduto() != $produto->getcodproduto()){
									$_SESSION["ERROR"] = "O código de barras {$ean} informado no produto {$produto->getcodproduto()}, ja esta cadastrado para o produto {$produtoean->getcodproduto()}";
									$this->connection->rollback();
									return FALSE;
								}
							}

							$produtoean->setcodproduto($produto->getcodproduto());
							if(!$produtoean->save()){
								$this->connection->rollback();
								return FALSE;
							}
						}

						$arr_produtoc[$codproduto] = $produto;
						break;
					case "06":	//itens do pedido
						$codproduto = $arr_linha[2];
						$operacaonota = objectbytable("operacaonota", $this::OPERACAO, $this->connection);

						$tributacaoproduto = new TributacaoProduto($this->connection);
						$tributacaoproduto->setcodestabelec($codestabelec);
						$tributacaoproduto->setoperacaonota($operacaonota);
						$tributacaoproduto->setnatoperacao($pedido->getnatoperacao());
						$tributacaoproduto->settipopreco($this::TIPOPRECO);
						$tributacaoproduto->setparceiro($cliente);
						$tributacaoproduto->setproduto($produto);
						$tributacaoproduto->buscar_dados();

						$piscofins = new PisCofins();
						$piscofins->setconnection($this->connection);
						$piscofins->setcodpiscofins($produto->getcodpiscofinssai());
						$arr_piscofins = object_array($piscofins);
						if(count($arr_piscofins) > 0){
							$piscofins = $arr_piscofins[0];
						}

						$embalagem = new Embalagem();
						$embalagem->setconnection($this->connection);
						$embalagem->setcodembal($produto->getcodembalvda());
						$arr_embalagem = object_array($embalagem);
						if(count($arr_embalagem) > 0){
							$embalagem = $arr_embalagem[0];
						}

						$precounitario = $arr_linha[5];
						$venda_inter = $cliente->getufres() != $this::UFESTABELEC;


						if($venda_inter && $cliente->getcontribuinteicms() == "S" && $tributacaoproduto->gettptribicms() == "F"){
							$uf_cliente = new Estado();
							$uf_cliente->setconnection($this->connection);
							$uf_cliente->setuf($cliente->getufres());
							$arr_uf_cliente = object_array($uf_cliente);
							$uf_cliente = $arr_uf_cliente[0];

							$ncm_estado = new NcmEstado();
							$idncm = $ncm->getidncm();
							$cliente_uf = $cliente->getufres();
							$ncm_estado->setconnection($this->connection);
							$ncm_estado->setidncm($idncm);
							$ncm_estado->setuf($cliente_uf);
							$arr_ncm_estado = object_array($ncm_estado);
							$ncm_estado = $arr_ncm_estado[0];
							$calculardifal = $ncm_estado->getcalculardifal();
							$ufcliente_convernio = $uf_cliente->getconvenioicms();
							$ncmestado_convenio = $ncm_estado->getconvenioicms();
							if($ncm_estado->getcalculardifal() == "S" && ($ncm_estado->getconvenioicms() == "S" || $uf_cliente->getconvenioicms() == "S")){
								if($ncm_estado->getaliqinterna() > 0){
									$aliq_interna = $ncm_estado->getaliqinterna();
								}else{
									$aliq_interna =  $uf_cliente->getaliqicms();
								}
								$origem = substr($classfiscalnfs->getcodcst(), 0, 1);
								if(!in_array($origem, array("1","2","6","7"))){
									$aliq_inter = $this->icms_interestadual["S"][$uf_cliente->getregiao()];
								}else{
									$aliq_inter = 4.00;
								}
								$difal_icms = $aliq_interna - $aliq_inter;
								if($difal_icms > 0 && $codestabelec == 1){
									$precounitario = $precounitario / (1 + $difal_icms / 100);
								}
							}
						}else{
							if($operacaonota->getoperacao() == "VD" && $natoperacao->getcalcdifaloperinter() == "S" && $cliente->getcontribuinteicms() == "N" && $venda_inter && $tributacaoproduto->getaliqicms() > 0){
								$aliqfcpufdest = $tributacaoproduto->getaliqfcpufdest(TRUE);
								$aliqicmsufdest = $tributacaoproduto->getaliqicmsufdest(TRUE);
								$ano = date("Y");
								switch ($ano){
									case "2016": $aliqicminterpart = 40; break;
									case "2017": $aliqicminterpart = 60; break;
									case "2018": $aliqicminterpart = 80; break;
									default: $aliqicminterpart = 100; break;
								}
							}
						}



						$itpedido = new ItPedido();

						$itpedido->setconnection($this->connection);
						$itpedido->setcodestabelec($codestabelec);
						$itpedido->setnumpedido($numpedido);
						$itpedido->setcodproduto($codproduto);

						$arr_itpedido = object_array($itpedido);
						$itpedido_exist = count($arr_itpedido) > 0;

						if($itpedido_exist){
							$itpedido = $arr_itpedido[0];
						}
						$itpedido->setseqitem($arr_linha[1]);
						$itpedido->setcodestabelec($codestabelec);
						$itpedido->setpedcliente($pedidocliente);
						$itpedido->setnumpedido($numpedido);
						$itpedido->setcodproduto($codproduto);
						$itpedido->setquantidade($arr_linha[4]);
						$itpedido->setpreco($precounitario);
						$itpedido->setvaldescto($tributacaoproduto->getvaldescto());
						$itpedido->setpercdescto($tributacaoproduto->getpercdescto());
						$itpedido->setvalacresc($tributacaoproduto->getvalacresc());
						$itpedido->setpercacresc($tributacaoproduto->getpercacresc());
						$itpedido->setvalfrete($tributacaoproduto->getvalfrete());
						$itpedido->setpercfrete($tributacaoproduto->getpercfrete());
						$itpedido->setdtentrega($pedido->getdtentrega());
						$itpedido->settipoipi($tributacaoproduto->gettipoipi());
						$itpedido->setvalipi($tributacaoproduto->getvalipi());
						$itpedido->setpercipi($tributacaoproduto->getpercipi());
						$itpedido->settptribicms($tributacaoproduto->gettptribicms());
						$itpedido->setredicms($tributacaoproduto->getredicms());
						$itpedido->setaliqicms($tributacaoproduto->getaliqicms());
						$itpedido->setaliqiva(0);
						$itpedido->setvalorpauta($tributacaoproduto->getvalorpauta());
						$itpedido->setnatoperacao($tributacaoproduto->getnatoperacao()->getnatoperacao());
						$itpedido->setaliqicmsinter($tributacaoproduto->getaliqicmsinter());
						$itpedido->setaliqfcpufdest($tributacaoproduto->getaliqfcpufdest());
						$itpedido->setaliqicmsufdest($tributacaoproduto->getaliqicmsufdest());
						$itpedido->setaliqicminterpart($aliqicminterpart);
						$itpedido->setaliqpis($piscofins->getaliqpis());
						$itpedido->setaliqcofins($piscofins->getaliqcofins());
						$itpedido->setqtdeunidade("1");
						$itpedido->setcodunidade($embalagem->getcodunidade());

						if(!$destacar_frete){
							$itemcalculo = new ItemCalculo($con);
							$itemcalculo->setcodestabelec($codestabelec);
							$itemcalculo->setoperacaonota($operacaonota);
							$itemcalculo->setnatoperacao($pedido->getnatoperacao());
							$itemcalculo->setparceiro($cliente);
							$itemcalculo->setitem($itpedido, $produto);
							$itemcalculo->setclassfiscalnfe($classfiscalnfs);
							$itemcalculo->setclassfiscalnfs($classfiscalnfs);
							$itemcalculo->calcular();
						}

						$qtde_itens += $itpedido->getquantidade();

						$pedido->itpedido[] = $itpedido;
						break;
				}
			}
			$valor_frete = 0;
			if($gravar_pedido){
				if($destacar_frete){	//fazer o rateio do frete nos itens
					$valor_frete = round($total_frete / $qtde_itens, 4);
					foreach($pedido->itpedido as $idx => $itpedido){
						$itpedido->setvalfrete($valor_frete);
						$itpedido->setpreco($itpedido->getpreco() - $valor_frete);

						$itemcalculo = new ItemCalculo($con);
						$itemcalculo->setcodestabelec($codestabelec);
						$itemcalculo->setoperacaonota($operacaonota);
						$itemcalculo->setnatoperacao($pedido->getnatoperacao());
						$itemcalculo->setparceiro($cliente);
						$itemcalculo->setitem($itpedido, $arr_produtoc[$itpedido->getcodproduto()]);
						$itemcalculo->setclassfiscalnfe($arr_classfiscalp[$itpedido->getcodproduto()]);
						$itemcalculo->setclassfiscalnfs($arr_classfiscalp[$itpedido->getcodproduto()]);
						$itemcalculo->calcular();
						$tfrete += $itpedido->gettotalfrete();
						$pedido->itpedido[$idx] = $itpedido;
					}
					$difFrete = round($tfrete,2) - $total_frete ;
					if($difFrete != 0){
						$itpedido = $pedido->itpedido[$idx];
						$itpedido->setvalfrete($itpedido->getvalfrete() - $difFrete);
						$itpedido->setpreco($itpedido->getpreco() + $difFrete);

						$itemcalculo = new ItemCalculo($con);
						$itemcalculo->setcodestabelec($codestabelec);
						$itemcalculo->setoperacaonota($operacaonota);
						$itemcalculo->setnatoperacao($pedido->getnatoperacao());
						$itemcalculo->setparceiro($cliente);
						$itemcalculo->setitem($itpedido, $arr_produtoc[$itpedido->getcodproduto()]);
						$itemcalculo->setclassfiscalnfe($arr_classfiscalp[$itpedido->getcodproduto()]);
						$itemcalculo->setclassfiscalnfs($arr_classfiscalp[$itpedido->getcodproduto()]);
						$itemcalculo->calcular();
					}
				}
				$pedido->calcular_totais();
				$difPedido = round($totalpedido - $pedido->gettotalliquido(),2);
				if($difPedido < -0.99 || $difPedido > 0.99){
					$_SESSION["ERROR"]  = "Divergência de Valores<br>";
					$_SESSION["ERROR"] .= "Total Pedido Sisautom: ".number_format($totalpedido, 2, ",",".")."<br>";
					$_SESSION["ERROR"] .= "Total Pedido WebSac: ".number_format($pedido->gettotalliquido(), 2, ",",".");
					$this->connection->rollback();
					return FALSE;
				}
				if(!$pedido->save()){
					$this->connection->rollback();
					return FALSE;
				}
			}
			if(file_exists($local_pedido.DIRECTORY_SEPARATOR.$arquivo)){
				unlink($local_pedido.DIRECTORY_SEPARATOR.$arquivo);
			}
			$this->connection->commit();
			return TRUE;
		}
		return TRUE;
	}

	private function getaliqicminterpart($dtemissao){
		$ano = convert_date($dtemissao, "d/m/y", "Y");
		switch ($ano){
			case "2016": $aliqicminterpart = 40; break;
			case "2017": $aliqicminterpart = 60; break;
			case "2018": $aliqicminterpart = 80; break;
			default: $aliqicminterpart = 100; break;
		}
		return $aliqicminterpart;
	}

	private function getcsticms(ClassFiscal &$classfiscal, $importado){
		$orig = ($importado == "S" ? "2" : "0");
		switch($classfiscal->gettptribicms()){
			case "T":
				$classfiscal->setcodcst($orig."00");
				$classfiscal->setcsosn($orig."101");
				break;
			case "N":
				$classfiscal->setcodcst($orig."41");
				$classfiscal->setcsosn($orig."400");
				break;
			case "I":
				$classfiscal->setcodcst($orig."40");
				$classfiscal->setcsosn($orig."300");
				break;
			case "F":
				if($classfiscal->getaliqicms() > 0){
					$classfiscal->setcodcst($orig."10");
					$classfiscal->setcsosn($orig."201");
				}else{
					$classfiscal->setcodcst($orig."60");
					$classfiscal->setcsosn($orig."201");
				}
				break;
			case "R":
				$classfiscal->setcodcst($orig."20");
				$classfiscal->setcsosn($orig."101");
				break;
			default :
				$classfiscal->setcodcst($orig."40");
				$classfiscal->setcsosn($orig."400");
				$classfiscal->settptribicms("I");
				$classfiscal->setaliqicms(0);
				$classfiscal->setaliqredicms(0);
		}
		return $classfiscal->getcodcst();
	}
}
