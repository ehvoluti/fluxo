<?php

require_once("../class/connection.class.php");
require_once("../class/pdvconfig.class.php");
require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");
require_once("../class/pdvvasilhame.class.php");
require_once("../class/vendamedia.class.php");
require_once("../class/consultaproduto.class.php");
require_once("../def/function.php");

require_once("../class/coral.class.php");
require_once("../class/emporium.class.php");
require_once("../class/gdr.class.php");
require_once("../class/gzsistemas.class.php");
require_once("../class/notafiscalpaulista.class.php");
require_once("../class/scanntech.class.php");
require_once("../class/siac.class.php");
require_once("../class/syspdv.class.php");
require_once("../class/visualmix.class.php");
require_once("../class/zanthus.class.php");
require_once("../class/saurus.class.php");

class InterligaPdv{

	private $con; // Conexao com o banco de dados
	private $frentecaixa; // Objeto que contem as informacoes do frente de caixa
	private $pdv; // Nome do pdv que sera gerado os arquivos (gz,coral)
	private $tipopreco; // Tipo de preco (A = Atacado; V = Varejo)
	private $str_preco; // Armazena um texto para usar nos meios das instrucoes quando for exportar o preco dos produtos
	private $estabelecimento; // Objeto do estabelecimento
	private $urgente; // Se deve gerar apenas os produtos urgentes
	private $datalog; // Data de alteracao dos registros (exportacao de alterados)
	private $horalog; // Hora de alteracao dos registros (exportacao de alterados)
	private $datalogfim; // Data de alteracao dos registros (exportacao de alterados)
	private $horalogfim; // Hora de alteracao dos registros (exportacao de alterados)
	private $dtmovto; // Data do movimento exata (usado para fazer leitura de alguns PDVs via webservice)
	private $dtmovto_ini; // Data do movimento inicial (opcional)
	private $dtmovto_fim; // Data do movimento final (opcional)
	private $pdvconfig; // Objeto de configuracao do PDV
	private $coral; // Objeto do PDV Coral
	private $emporium; // Objeto do PDV Emporium
	private $gdr; // Objeto do PDV GDR
	private $gzsistemas; // Objeto do PDV Gz Sistemas
	private $notafiscalpaulista; // Objeto da Nota Fiscal Paulista
	private $scanntech; // Objecto do PDV Scanntech
	private $siac; // Objeto do PDV SIAC (Itautec)
	private $syspdv; // Objeto do PDV SysPDV
	private $visualmix; // Objeto do PDV Visual Mix
	private $zanthus; // Objeto do PDV Zanthus
	private $saurus; // Objeto do PDV Saurus
	private $vendas = array(); // Lista como todas as vendas feitas no PDV (classe PdvVenda)
	private $finalizadoras = array(); // Lista como todas as finalizadoras feitas no PDV (classe PdvFinalizador)
	private $vasilhames = array();
	private $arr_recebepdv = array();
	private $arr_bin = array(); // Array com os bins por tipo

	function __construct($con){
		$this->con = $con;
		$this->pdvconfig = new PdvConfig($this->con);

		$this->coral = new Coral();
		$this->emporium = new Emporium();
		$this->gdr = new Gdr();
		$this->gzsistemas = new GzSistemas();
		$this->notafiscalpaulista = new NotaFiscalPaulista($this->con);
		$this->scanntech = new Scanntech();
		$this->siac = new Siac();
		$this->syspdv = new SysPdv();
		$this->visualmix = new VisualMix();
		$this->zanthus = new Zanthus();
		$this->saurus = new Saurus($this->con);

		$this->seturgente(FALSE);
		$this->setdtmovto_ini(NULL);
		$this->setdtmovto_fim(NULL);
		$this->settipopreco("V");
		$this->vasilhames = array();
	}

	private function file_create($filename, $lines, $mode = "w+", $return = FALSE){
		return $this->pdvconfig->file_create($filename, $lines, $mode, $return);
	}

	function gravarvendas_(){
		// Verifica se existe dados para o processamento
		if(count($this->vendas) === 0 && count($this->arr_recebepdv) === 0 && count($this->vasilhames) === 0){
			$_SESSION["ERROR"] = "Nenhuma venda foi encontrada.";
			return false;
		}

		// Carrega os parametros de estoque
		setprogress(0, "Carregando os parametros necessarios.", true);
		$paramcomissao = objectbytable("paramcomissao", $this->estabelecimento->getcodestabelec(), $this->con);
		if(!$paramcomissao->exists()){
			$_SESSION["ERROR"] = "Par&acirc;metros de comiss&atilde;o ainda n&atilde;o foram informados para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamComissao','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir os par&acirc;metros de comiss&atilde;o.";
			return false;
		}
		$paramestoque = objectbytable("paramestoque", $this->estabelecimento->getcodemitente(), $this->con);
		if(strlen($paramestoque->getcodclientevendapdv()) === 0){
			$_SESSION["ERROR"] = "Cliente padr&atilde;o n&atilde;o foi informado na par&acirc;metriza&ccedil;&atilde;o de estoque.<br><a onclick=\"$.messageBox('close'); openProgram('ParamEst','codemitente=".$this->estabelecimento->getcodemitente()."')\">Clique aqui</a> para abrir os  par&acirc;metros de estoque.";
			return false;
		}
		$codtpdocto = $paramestoque->getcodtpdoctovendapdv();
		if(strlen($codtpdocto) === 0){
			$_SESSION["ERROR"] = "Tipo de documento para venda (PDV) n&atilde;o foi informado na par&acirc;metriza&ccedil;&atilde;o de estoque.<br><a onclick=\"$.messageBox('close'); openProgram('ParamEst','codemitente=".$this->estabelecimento->getcodemitente()."')\">Clique aqui</a> para abrir os  par&acirc;metros de estoque.";
			return false;
		}

		// Verifica se o cupom ativo soh tem itens cancelados
		setprogress(0, "Verificando cupons cancelados.", true);
		foreach($this->vendas as $pdvvenda){
			if($pdvvenda->getstatus() === "A"){
				$cancelado = true;
				foreach($pdvvenda->pdvitem as $pdvitem){
					if($pdvitem->getstatus() !== "C"){
						$cancelado = false;
						break;
					}
				}
				if($cancelado){
					$pdvvenda->setstatus("C");
				}
			}
		}

		// Remove cupons duplicados no mesmo lote
		setprogress(0, "Verificando cupons duplicados.", true);
		$arr_pdvvenda = array();
		foreach($this->vendas as $pdvvenda1){
			foreach($arr_pdvvenda as $pdvvenda2){
				if($pdvvenda1->getdata() === $pdvvenda2->getdata() && $pdvvenda1->getcaixa() === $pdvvenda2->getcaixa() && $pdvvenda1->getcupom() === $pdvvenda2->getcupom() && $pdvvenda1->getstatus() === $pdvvenda2->getstatus() && $pdvvenda1->getseqecf() === $pdvvenda2->getseqecf()){
					continue 2;
				}
			}
			$arr_pdvvenda[] = $pdvvenda1;
		}
		$this->vendas = $arr_pdvvenda;

		// Reordena as venda para ler os cancelamentos por ultimo
		setprogress(0, "Reordenando cupons.", true);
		$arr_venda_a = array();
		$arr_venda_c = array();
		$arr_venda_i = array();
		foreach($this->vendas as $i => $venda){
			setprogress(($i + 1) / count($this->vendas) * 100, "Reordenando cupons: ".($i + 1)." de ".count($this->vendas));
			switch($venda->getstatus()){
				case "A": $arr_venda_a[] = $venda; break;
				case "C": $arr_venda_c[] = $venda; break;
				case "I": $arr_venda_i[] = $venda; break;
			}
		}

		// Verifica se existe um cupom cancelado de um cupom ativo
		setprogress(0, "Verificando cupons cancelados.", true);
		foreach($arr_venda_c as $i => $venda_c){
			foreach($arr_venda_a as $j => $venda_a){
				if($venda_c->getdata() === $venda_a->getdata() && $venda_c->getcaixa() === $venda_a->getcaixa() && $venda_c->getcupom() === $venda_a->getcupom()){
					$venda_a->setstatus("C");
					$arr_venda_c[$i] = clone $venda_a;
					unset($arr_venda_a[$j]);
					break;
				}
			}
		}

		// Vincula as finalizadoras ao cupom, caso venha separado
		setprogress(0, "Vinculando finalizadoras aos cupons.", true);
		foreach($this->finalizadoras as $i => $pdvfinalizador){
			if($pdvfinalizador->getrecebepdv()){
				continue;
			}
			foreach($this->vendas as $pdvvenda){
				if($pdvfinalizador->getdata() == $pdvvenda->getdata()
					&& intval($pdvfinalizador->getcaixa()) == $pdvvenda->getcaixa()
					&& $pdvfinalizador->getcupom() == $pdvvenda->getcupom()
				){
					// Vincula a finalizadora e remove ela da lista
					$pdvvenda->pdvfinalizador[] = $pdvfinalizador;
					unset($this->finalizadoras[$i]);
					continue 2;
				}
			}
			// Se chegou nesse ponto eh porque nao achou a venda para vincular a finalizadora
			$_SESSION["ERROR"] = "Não foi possível encontrar o cupom da finalizadora do caixa {$pdvfinalizador->getcaixa()} e número {$pdvfinalizador->getcupom()}.";
			return false;
		}

		// Verifica o numero de fabricacao nas vendas
		$param_frentecaixa_validacnpjestableitura = param("FRENTECAIXA", "VALIDCNPJESTABSAT", $this->con);
		foreach($this->vendas as $pdvvenda){
			if(strlen($pdvvenda->getnumfabricacao()) === 0 && strlen($pdvvenda->getchavecfe()) > 0){
				$mod = substr($pdvvenda->getchavecfe(), 19,2);
				if($mod == "65"){
					$query = "SELECT numfabricacao FROM ecf WHERE status='A' AND codestabelec = {$this->estabelecimento->getcodestabelec()} AND caixa = '{$pdvvenda->getcaixa()}'";
					$res = $this->con->query($query);
					$numfabricacao = $res->fetchColumn();

					if(strlen($numfabricacao) <= 0){
						$_SESSION["ERROR"] = "Não foi possível encontrar o numero de fabricacao da <b>NFC-E</b> para o caixa {$pdvvenda->getcaixa()}.";
						return false;
					}

					$pdvvenda->setnumfabricacao($numfabricacao);
				}
			}

			if($param_frentecaixa_validacnpjestableitura == "S" && strlen($pdvvenda->getchavecfe()) > 0){
				$aux_cnpj = substr($pdvvenda->getchavecfe(),4,14);
				if($aux_cnpj != removeformat($this->estabelecimento->getcpfcnpj())){
					$_SESSION["ERROR"] = "CNPJ do SAT {$aux_cnpj} é diferente do estabelecimento {$this->estabelecimento->getcpfcnpj()}.";
					return false;
				}
			}
		}

		// Verifica os numeros de fabricacao para os equipamentos fiscais correspondentes
		setprogress(0, "Verificando equipamentos fiscais.", true);
		if(in_array($this->pdv, array("syspdv", "saurus", "zanthus"))){
			$arr_numfabricacao = [];
			foreach($this->vendas as $venda){
				if(strlen($venda->getnumfabricacao()) > 0){
					if(!in_array($venda->getnumfabricacao(), $arr_numfabricacao)){
						$numfabricacao = trim($venda->getnumfabricacao());
						$query = "SELECT codecf FROM ecf WHERE status='A' AND numfabricacao = '{$numfabricacao}'";
						$res = $this->con->query($query);
						$codecf = $res->fetchColumn();
						if(strlen($codecf) <= 0){
							$msg = "Numero de fabrica&ccedil;&atilde;o do equipamento fiscal n&atilde;o encontrado <b>{$venda->getnumfabricacao()}</b> para o caixa <b>{$venda->getcaixa()}</b> e cupom <b>{$venda->getcupom()}</b>";
							$_SESSION["ERROR"] = $msg;
							return false;
						}else{
							$arr_numfabricacao[$numfabricacao] = $codecf;
						}
					}
				}
			}
		}

		// Verifica os produtos cadastrados
		setprogress(0, "Verificando produtos cadastrados.", true);
		$arr_codproduto = array();
		foreach($this->vendas as $pdvvenda){
			foreach($pdvvenda->pdvitem as $pdvitem){
				$arr_codproduto[] = $pdvitem->getcodproduto();
			}
		}
		$arr_codproduto = array_unique($arr_codproduto);
		$priopesqproduto = $this->frentecaixa->gettipocodproduto() == "E" ? "1" : "0";
		$consultaproduto = new ConsultaProduto($this->con, $priopesqproduto);
		$consultaproduto->addcodproduto($arr_codproduto);
		$consultaproduto->consultar();
		$arr_codproduto_naoencontrado = $consultaproduto->getnaoencontrado();
		if(count($arr_codproduto_naoencontrado) > 0){
			$param_estoque_codprodnaocad = trim(param("ESTOQUE", "CODPRODNAOCAD", $this->con));
			if(strlen($param_estoque_codprodnaocad) > 0){
				foreach($this->vendas as $pdvvenda){
					foreach($pdvvenda->pdvitem as $pdvitem){
						if(in_array($pdvitem->getcodproduto(), $arr_codproduto_naoencontrado)){
							$pdvitem->setcodproduto($param_estoque_codprodnaocad);
						}
					}
				}
			}else{
				$_SESSION["ERROR"] = "Existem produtos não cadastrados no arquivo de vendas. Recadastre os produtos apagados para prosseguir com a leitura.<br><br>Lista de produtos não encontrados:<br>".implode(", ", $arr_codproduto_naoencontrado);
				return false;
			}
		}
		$arr_codproduto_encontrado = $consultaproduto->getencontrado();
		foreach($this->vendas as $pdvvenda){
			foreach($pdvvenda->pdvitem as $pdvitem){
				$codproduto = $arr_codproduto_encontrado[$pdvitem->getcodproduto()];
				if(strlen($codproduto) === 0){
					$codproduto = $param_estoque_codprodnaocad;
				}
				$pdvitem->setcodproduto($codproduto);
			}
		}

		// Carrega os produtos
		setprogress(0, "Carregando cadastro de produtos.", true);
		if(count($arr_codproduto_encontrado) > 0){
			setprogress(0, "Carregando cadastro de produtos.", true);
			$query = "SELECT produto.codproduto, produtoean.codean, produtoestab.custorep, produtoestab.custotab, produtoestab.custosemimp, classfiscal.tptribicms, classfiscal.aliqicms, ";
			$query .= "	classfiscal.aliqredicms, embalagem.codunidade, embalagem.quantidade AS qtdeunidade, piscofins.aliqpis, piscofins.aliqcofins ";
			$query .= "FROM produto ";
			$query .= "LEFT JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
			$query .= "LEFT JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
			$query .= "LEFT JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
			$query .= "LEFT JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
			$query .= "LEFT JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
			$query .= "WHERE produtoestab.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= "  AND produto.codproduto IN (".implode(",", $arr_codproduto_encontrado).") ";
			$query .= "ORDER BY produto.codproduto ";
			$res = $this->con->query($query);
			$arrProduto = $res->fetchAll(2);
		}else{
			$arrProduto = array();
		}

		// Carrega a lista de clientes cadastrados
		setprogress(0, "Carregando cadastro de clientes.", true);
		$res = $this->con->query("SELECT codcliente, cpfcnpj FROM cliente");
		$arr = $res->fetchAll(2);
		$arrCliente = array();
		foreach($arr as $row){
			$arrCliente[$row["codcliente"]] = $row["cpfcnpj"];
		}

		// Carrega a lista de todas as finalizadoras cadastradas
		setprogress(0, "Carregando cadastro de finalizadoras.", true);
		$res = $this->con->query("SELECT finalizadora.*, especie.geraliquidado FROM finalizadora INNER JOIN especie ON (finalizadora.codespecie = especie.codespecie) WHERE codestabelec = ".$this->estabelecimento->getcodestabelec());
		$arr = $res->fetchAll(2);
		$arr_finalizadora = array();
		foreach($arr as $row){
			$arr_finalizadora[$row["codfinaliz"]] = $row;
		}

		// Cria duas listas para localizar mais facil o produto
		$arrCodProduto = array();
		$arrCodEan = array();
		foreach($arrProduto as $i => $row){
			$arrCodProduto[$i] = $row["codproduto"];
			$arrCodEan[$i] = $row["codean"];
		}

		// Verifica se todas as finalizadoras existem no cadastro
		$arr_finalizadora_naoencontrado = array();
		foreach($this->finalizadoras as $finalizador){
			if(!array_key_exists($finalizador->getcodfinaliz(), $arr_finalizadora)){
				$arr_finalizadora_naoencontrado[] = $finalizador->getcodfinaliz();
			}
		}
		$arr_finalizadora_naoencontrado = array_unique($arr_finalizadora_naoencontrado);
		if(count($arr_finalizadora_naoencontrado) > 0){
			$_SESSION["ERROR"] = "Existem finalizadoras não cadastradas no arquivo de vendas. Cadastre as finalizadoras para prosseguir com a leitura.<br><br>Lista de finalizadoras não encontradas:<br>".implode(", ", $arr_finalizadora_naoencontrado);
			return false;
		}

		// Junta os mesmos produtos do cupom
		setprogress(0, "Reorganizando cupons.", true);
		foreach($this->vendas as $pdvvenda){
			$arr_pdvitem = array();
			foreach($pdvvenda->pdvitem as $pdvitem1){
				$found = false;
				foreach($arr_pdvitem as $pdvitem2){
					if($pdvitem1->getcodproduto() == $pdvitem2->getcodproduto() && $pdvitem1->getstatus() == $pdvitem2->getstatus()){
						$pdvitem2->setquantidade($pdvitem2->getquantidade() + $pdvitem1->getquantidade());
						$pdvitem2->setdesconto($pdvitem2->getdesconto() + $pdvitem1->getdesconto());
						$pdvitem2->setacrescimo($pdvitem2->getacrescimo() + $pdvitem1->getacrescimo());
						$pdvitem2->settotal($pdvitem2->gettotal() + $pdvitem1->gettotal());
						$pdvitem2->setpreco(($pdvitem2->gettotal() + $pdvitem2->getdesconto() - $pdvitem2->getacrescimo()) / $pdvitem2->getquantidade());
						$found = true;
						break;
					}
				}
				if(!$found){
					$arr_pdvitem[] = $pdvitem1;
				}
			}
			$pdvvenda->pdvitem = $arr_pdvitem;
		}

		// Busca as composicoes do tipo explode na venda
		setprogress(0, "Carregando cadastro de composicoes.", true);
		$arrComposicao = array();
		if(sizeof($arrCodProduto) > 0){
			$res = $this->con->query("SELECT * FROM composicao WHERE tipo IN ('A','V')");
			$arrComposicaoAux = $res->fetchAll(2);
			foreach($arrComposicaoAux as $rowComposicao){
				$res = $this->con->query("SELECT * FROM itcomposicao WHERE codcomposicao = ".$rowComposicao["codcomposicao"]);
				$arrComposicao[$rowComposicao["codproduto"]] = $rowComposicao;
				$arrComposicao[$rowComposicao["codproduto"]]["itcomposicao"] = $res->fetchAll(2);
			}
		}

		// Carrega a lista de codigo de vendedores cadastrados
		setprogress(0, "Carregando cadastro de funcionarios.", true);
		$res = $this->con->query("SELECT codfunc FROM funcionario");
		$arr = $res->fetchAll(2);
		$arr_codfunc = array();
		foreach($arr as $row){
			$arr_codfunc[] = $row["codfunc"];
		}

		// Carrega os equipamentos fiscais cadastrados
		setprogress(0, "Carregando equipamentos fiscais.", true);
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setcodestabelec($this->estabelecimento->getcodestabelec());
		$ecf->setstatus("A");
		$arr_ecf_aux = object_array($ecf);
		$arr_ecf = array();
		foreach($arr_ecf_aux as $ecf){
			$arr_ecf[$ecf->getcaixa()] = $ecf;
		}

		// Remove recebimento duplicado
		setprogress(0, "Removendo recebimentos duplicados.", true);
		if(is_array($this->arr_recebepdv)){
			foreach($this->arr_recebepdv as $i => $recebepdv){
				$query = implode(" ", array(
					"SELECT dtmovto, caixa, cupom, codestabelec",
					"FROM recebepdv",
					"WHERE codestabelec = ".$this->estabelecimento->getcodestabelec(),
					"  AND dtmovto = '{$recebepdv->getdtmovto()}' AND caixa = {$recebepdv->getcaixa()} AND cupom='{$recebepdv->getcupom()}'"
				));

				$res = $this->con->query($query);
				$arr = $res->fetchAll(2);
				foreach($arr as $i => $row){
					setprogress((($i + 1) / sizeof($arr) * 100), "Removendo recebimentos duplicados: ".($i + 1)." de ".sizeof($arr));
					foreach($this->finalizadoras as $j => $finalizador){
						if($finalizador->getdata() == $row["dtmovto"] && $finalizador->getcaixa() == $row["caixa"] && $finalizador->getcupom() == $row["cupom"]){
							unset($this->finalizadoras[$j]);
						}
					}
				}
			}
		}

		//  Verifica se o total dos itens bate com a as finalizadoras
		setprogress(0, "Verificando total das vendas.", true);
		foreach($this->vendas as $pdvvenda){

			// Verifica se eh um cupom ativo
			if($pdvvenda->getstatus() !== "A"){
				continue;
			}

			// Soma total dos itens
			$totalitens = 0;
			foreach($pdvvenda->pdvitem as $pdvitem){
				if($pdvitem->getstatus() === "A"){
					$totalitens += $pdvitem->gettotal();
				}elseif($pdvitem->getstatus() === "T"){
					continue 2;
				}
			}

			// Soma total das finalizadoras
			$totalfinalizadoras = 0;
			foreach($pdvvenda->pdvfinalizador as $pdvfinalizador){
				$totalfinalizadoras += $pdvfinalizador->getvalortotal();
			}

			// Verifica se o total dos itens bate com as finalizadoras		
			if(round($totalitens, 2) !== round($totalfinalizadoras, 2) && !in_array($this->pdv, array("nfpaulista", "scanntech"))){
				$data = convert_date($pdvvenda->getdata(), "Y-m-d", "d/m/Y");
				$totalitens = number_format($totalitens, 2, ",", ".");
				$totalfinalizadoras = number_format($totalfinalizadoras, 2, ",", ".");
				$_SESSION["ERROR"] = "Somatório dos itens do cupom {$pdvvenda->getcupom()} do caixa {$pdvvenda->getcaixa()} do dia {$data} não confere com o somatório das finalizadoras.<br>Arquivo: {$pdvvenda->getarquivo()}<br>Total dos itens: R$ {$totalitens}<br>Total das finalizadoras: R$ {$totalfinalizadoras}";
				return false;
			}
		}

		// Inicia a transacao no banco de dados
		$this->con->start_transaction();

		// Verifica e grava os vasilhames
		setprogress(0, "Gravando vasilhames.", true);
		if(!$this->gravarvendas_vasilhame()){
			$this->con->rollback();
			return false;
		}

		// Cadastra um novo cliente caso não encontrado o CPF ou CNPJ
		setprogress(0, "Verificando clientes das vendas.", true);
		if(param("CADASTRO", "CADAUTOCLIENTE", $this->con) === "S"){
			$arr_cpfcnpj = array();
			foreach($this->vendas as $venda){
				if($venda->getcpfcnpj() != NULL){
					$arr_cpfcnpj[] = $venda->getcpfcnpj();
				}
			}
			foreach($this->finalizadoras as $finalizadora){
				if($finalizadora->getcpfcliente() != NULL){
					$arr_cpfcnpj[] = $finalizadora->getcpfcliente();
				}
			}
			array_unique($arr_cpfcnpj);

			$res = $this->con->query("SELECT cpfcnpj FROM cliente");
			$arr = $res->fetchAll(2);

			foreach($arr_cpfcnpj as $cpfcnpj){
				if(valid_cpf($cpfcnpj) || valid_cnpj($cpfcnpj)){
					foreach($arr as $row){
						if($cpfcnpj === $row["cpfcnpj"]){
							continue 2;
						}
					}
					if(!$this->gravar_cliente($cpfcnpj)){
						$this->con->rollback();
						return false;
					}
				}
			}
		}

		// Remove cupons duplicados (ativos)
		setprogress(0, "Verificando cupons duplicados", TRUE);
		$arr_chave = array();
		foreach($arr_venda_a as $venda){
			$arr_chave[] = "('".$venda->getdata()."','".$venda->getcaixa()."','".$venda->getcupom()."','".$venda->getseqecf()."')";
		}
		$arr_chave = array_unique($arr_chave);
		if(count($arr_chave) > 0){
			$query = "SELECT idcupom, dtmovto, caixa, cupom, totalliquido ";
			$query .= "FROM cupom ";
			$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ((dtmovto,caixa,cupom,COALESCE(seqecf,'')) IN (".implode(",", $arr_chave).")) ";
			//$query .= " AND status = 'A' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $i => $row){
				setprogress((($i + 1) / count($arr) * 100), "Removendo cupons duplicados: ".($i + 1)." de ".count($arr));
				foreach($arr_venda_a as $j => $venda){
					if($venda->getdata() == $row["dtmovto"] && $venda->getcaixa() == $row["caixa"] && $venda->getcupom() == $row["cupom"]){
						unset($arr_venda_a[$j]);
					}
				}
			}
		}

		// Remove cupons duplicados (cancelados)
		setprogress(33, "Verificando cupons duplicados", TRUE);
		$arr_chave = array();
		foreach($arr_venda_c as $venda){
			$arr_chave[] = "('".$venda->getdata()."','".$venda->getcaixa()."','".$venda->getcupom()."')";
		}
		$arr_chave = array_unique($arr_chave);
		if(sizeof($arr_chave) > 0){
			$query = "SELECT dtmovto, caixa, cupom ";
			$query .= "FROM cupom ";
			$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ((dtmovto,caixa,cupom) IN (".implode(",", $arr_chave).")) ";
			$query .= " AND status = 'C' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $i => $row){
				setprogress((($i + 1) / sizeof($arr) * 100), "Removendo cupons duplicados: ".($i + 1)." de ".sizeof($arr));
				foreach($arr_venda_c as $j => $venda){
					if($venda->getdata() == $row["dtmovto"] && $venda->getcaixa() == $row["caixa"] && $venda->getcupom() == $row["cupom"]){
						unset($arr_venda_c[$j]);
					}
				}
				foreach($this->finalizadoras as $j => $finalizador){
					if($finalizador->getdata() == $row["dtmovto"] && $finalizador->getcaixa() == $row["caixa"] && $finalizador->getcupom() == $row["cupom"]){
						unset($this->finalizadoras[$j]);
					}
				}
			}
		}

		// Remove cupons duplicados (inutilizados)
		setprogress(66, "Verificando cupons duplicados", TRUE);
		$arr_chave = array();
		foreach($arr_venda_i as $venda){
			$arr_chave[] = "('".$venda->getdata()."','".$venda->getcaixa()."','".$venda->getcupom()."')";
		}
		$arr_chave = array_unique($arr_chave);
		if(sizeof($arr_chave) > 0){
			$query = "SELECT dtmovto, caixa, cupom ";
			$query .= "FROM cupom ";
			$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ((dtmovto,caixa,cupom) IN (".implode(",", $arr_chave).")) ";
			$query .= " AND status = 'I' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $i => $row){
				setprogress((($i + 1) / sizeof($arr) * 100), "Removendo cupons duplicados: ".($i + 1)." de ".sizeof($arr));
				foreach($arr_venda_i as $j => $venda){
					if($venda->getdata() == $row["dtmovto"] && $venda->getcaixa() == $row["caixa"] && $venda->getcupom() == $row["cupom"]){
						unset($arr_venda_i[$j]);
					}
				}
			}
		}

		// Junta as vendas normais com as canceladas
		$this->vendas = array_merge($arr_venda_a, $arr_venda_c, $arr_venda_i);

		// Percorre todas as vendas
		foreach($this->vendas as $i => $venda){
			setprogress(($i + 1) / count($this->vendas) * 100, "Gravando vendas: ".($i + 1)." de ".count($this->vendas));

			// Trata cupom cancelado
			if($venda->getstatus() === "C"){
				// Procura o cupom para atualizar seu status para cancelado
				$cupom = objectbytable("cupom", NULL, $this->con);
				if(strlen($venda->getchavecfe()) > 0  && $this->estabelecimento->getcodfrentecaixa() != 1){
					$cupom->setchavecfe($venda->getchavecfe());
				}else{
					$cupom->setcodestabelec($this->estabelecimento->getcodestabelec());
					$cupom->setcaixa($venda->getcaixa());
					$cupom->setdtmovto($venda->getdata());
					$cupom->setcupom($venda->getcupom());
				}
				$cupom->setstatus("A");
				$arr_cupom = object_array($cupom);
				if(count($arr_cupom) > 0){
					$cupom = array_shift($arr_cupom);
					$cupom->setstatus("C");
					if(!$cupom->save()){
						$this->con->rollback();
						return false;
					}
					continue;
				}
			}

			// Calcula os totais necessarios para gravar a venda
			$total_bruto = 0;
			$total_desconto = 0;
			$total_acrescimo = 0;
			foreach($venda->pdvitem as $pdvitem){
				if($pdvitem->getstatus() === "A" || $venda->getstatus() === "C"){
					$total_bruto += ($pdvitem->gettotal() + $pdvitem->getdesconto() - $pdvitem->getacrescimo());
					$total_desconto += $pdvitem->getdesconto();
					$total_acrescimo += $pdvitem->getacrescimo();
				}
			}

			if($venda->getstatus() === "I"){
				var_dump($venda);
			}

			// Cria cupom de venda
			$cupom = objectbytable("cupom", NULL, $this->con);
			$cupom->setstatus($venda->getstatus());
			$cupom->setcodestabelec($this->estabelecimento->getcodestabelec());
			$cupom->setcaixa($venda->getcaixa());
			$cupom->setdtmovto($venda->getdata());
			$cupom->sethrmovto($venda->gethora());
			$cupom->setcupom($venda->getcupom());
			$cupom->setchavecfe($venda->getchavecfe());
			$cupom->setoperador($venda->getoperador());
			if(strlen($venda->getreferencia()) > 0){
				$cupom->setreferencia($venda->getreferencia());
			}
			$cupom->setcpfcnpj($venda->getcpfcnpj());
			if($venda->getcodcliente() > 0 && array_key_exists($venda->getcodcliente(), $arrCliente)){
				$cupom->setcodcliente($venda->getcodcliente());
			}elseif(strlen($venda->getcpfcnpj()) > 0 && in_array($venda->getcpfcnpj(), $arrCliente)){
				$cupom->setcodcliente(array_search($venda->getcpfcnpj(), $arrCliente));
			}else{
				$cupom->setcodcliente($paramestoque->getcodclientevendapdv());
			}
			if($venda->getcodfunc() > 0 && in_array((string) $venda->getcodfunc(), $arr_codfunc)){
				$cupom->setcodfunc($venda->getcodfunc());
			}
			$cupom->settotalbruto($total_bruto);
			$cupom->settotaldesconto($total_desconto);
			$cupom->settotalacrescimo($total_acrescimo);
			$cupom->settotalliquido($total_bruto + $total_acrescimo - $total_desconto);
			$cupom->setnumeroecf($venda->getnumeroecf());
			$cupom->setseqecf($venda->getseqecf());
			$cupom->setcodsupervisor($venda->getcodsupervisor());
			$cupom->setnomecliente($venda->getnomecliente());

			if(!in_array($this->pdv, array("syspdv", "saurus", "zanthus"))){
				if(strlen($venda->getcodecf()) > 0){
					$cupom->setcodecf($venda->getcodecf());
				}else{
					// Carrega a ECF correta da venda
					$ecf = $arr_ecf[$venda->getcaixa()];
					if(!is_object($ecf)){
						$_SESSION["ERROR"] = "Equipamento fiscal no caixa {$venda->getcaixa()} n&atilde;o foi encontrado no cadastro para o cupom de número {$venda->getcupom()}.<br><a onclick=\"$.messageBox('close'); openProgram('Ecf')\">Clique aqui</a> para abrir o cadastro de Equipamentos Fiscais.";
						return false;
					}
					$cupom->setcodecf($ecf->getcodecf());
				}
			}else{			
				$ecf = $arr_ecf[$venda->getcaixa()];

				if(!is_object($ecf)){
					$_SESSION["ERROR"] = "Equipamento fiscal no caixa {$venda->getcaixa()} n&atilde;o foi encontrado no cadastro para o cupom de número {$venda->getcupom()}.<br><a onclick=\"$.messageBox('close'); openProgram('Ecf')\">Clique aqui</a> para abrir o cadastro de Equipamentos Fiscais.";
					return false;
				}
		
				if($ecf->getequipamentofiscal() == "NFC"){
					$cupom->setcodecf($ecf->getcodecf());						
				}else{
					if(strlen($venda->getnumfabricacao()) > 0){
						$cupom->setnumfabricacao($venda->getnumfabricacao());
						$cupom->setcodecf($arr_numfabricacao[$venda->getnumfabricacao()]);
					}else{
						$cupom->setcodecf(null);
					}
				}			
			}

			if(!$cupom->save()){
				$this->con->rollback();
				return false;
			}

			// Percorre todos os itens do cupom
			foreach($venda->pdvitem as $pdvitem){
				// Busca o indice que contem os dados do produto
				$idxProduto = array_search($pdvitem->getcodproduto(), $arrCodProduto);
				$produto = $arrProduto[$idxProduto];

				// Verifica se eh troca
				if($pdvitem->getstatus() === "T"){
					if(!$this->gravarvendas_trocaitem($venda, $pdvitem, $produto, $paramestoque, $arrComposicao)){
						$this->con->rollback();
						return false;
					}
				}else{
					// Cria leitura do item
					$itcupom = objectbytable("itcupom", null, $this->con);
					$itcupom->setidcupom($cupom->getidcupom());
					$itcupom->setcodproduto((integer) $pdvitem->getcodproduto());
					$itcupom->setcustorep($produto["custorep"]);
					$itcupom->setcustotab($produto["custotab"]);
					$itcupom->setcustosemimp($produto["custosemimp"]);
					$itcupom->setquantidade($pdvitem->getquantidade());
					$itcupom->setpreco($pdvitem->getpreco());
					$itcupom->setdesconto($pdvitem->getdesconto());
					$itcupom->setacrescimo($pdvitem->getacrescimo());
					$itcupom->setstatus($pdvitem->getstatus());
					$itcupom->setcodsupervisor($pdvitem->getcodsupervisor());
					if(strlen($pdvitem->gettptribicms()) === 0 || strlen($pdvitem->getaliqicms()) === 0){
						$itcupom->settptribicms($produto["tptribicms"] == "R" ? "T" : $produto["tptribicms"]);
						if(in_array($itcupom->gettptribicms(), array("T", "R"))){
							$itcupom->setaliqicms(number_format(($produto["aliqicms"] - ($produto["aliqicms"] * $produto["aliqredicms"] / 100)), 2, ".", ""));
						}else{
							$itcupom->setaliqicms(0);
						}
					}else{
						$itcupom->settptribicms($pdvitem->gettptribicms());
						$itcupom->setaliqicms($pdvitem->getaliqicms());
					}
					$itcupom->setaliqpis($arrProduto[$idxProduto]["aliqpis"]);
					$itcupom->setaliqcofins($arrProduto[$idxProduto]["aliqcofins"]);
					$itcupom->setvalortotal($pdvitem->gettotal());

					// Nao explode os itens de explosao automatica do SysPDV (porque ja vem explodido no arquivo)
					$gravar_pai_composicao = true;
					if($this->pdv == "syspdv"){
						foreach($arrComposicao as $composicao){
							if($composicao["explosaoauto"] == "S" && $composicao["codproduto"] == $itcupom->getcodproduto()){
								$gravar_pai_composicao = false;
								break;
							}
						}
					}

					// Trata composicao
					if($gravar_pai_composicao){
						$arr_itcupom_comp = $this->explodirproduto($itcupom, $arrComposicao);
					}else{
						$arr_itcupom_comp = array();
					}
					if(sizeof($arr_itcupom_comp) > 0 || !$gravar_pai_composicao){
						$itcupom->setcomposicao("P");
					}else{
						$itcupom->setcomposicao("N");
					}

					// Salva item principal
					if(!$itcupom->save()){
						$this->con->rollback();
						return false;
					}

					// Salva filhos da composicao
					foreach($arr_itcupom_comp as $itcupom_comp){
						if(!$itcupom_comp->save()){
							$this->con->rollback();
							return false;
						}
					}
				}
			}

			// Lancamentos financeiros
			if($cupom->getstatus() === "A"){
				foreach($venda->pdvfinalizador as $pdvfinalizador){
					$observacao = null;
					if(strlen($pdvfinalizador->getnumcheque()) > 0){
						$observacao = "Banco: ".$pdvfinalizador->getcodbanco()."\nAgencia: ".$pdvfinalizador->getnumagenciacheq()."\nConta: ".$pdvfinalizador->getcontacheque()."\nCheque: ".$pdvfinalizador->getnumcheque();
					}

					$cupomlancto = objectbytable("cupomlancto", null, $this->con);
					$cupomlancto->setidcupom($cupom->getidcupom());
					$cupomlancto->setcodfinaliz($pdvfinalizador->getcodfinaliz());
					$cupomlancto->settotalliquido($pdvfinalizador->getvalortotal());
					$cupomlancto->setdtvencto($pdvfinalizador->getdatavencto());
					$cupomlancto->setdocliquidacao($pdvfinalizador->getnumcheque());
					$cupomlancto->setcodcliente($pdvfinalizador->getcodcliente());
					$cupomlancto->setobservacao($observacao);
					if(!$cupomlancto->save()){
						$this->con->rollback();
						return false;
					}
				}
			}
		}

		// Recebimentos
		foreach($this->finalizadoras as $pdvfinalizador){
			if(!$this->gravarvendas_recebimento($pdvfinalizador, $paramestoque)){
				$this->con->rollback();
				return false;
			}
		}

		// Busca todos os produtos dos cupons para recalcular a venda media
		$arr_codproduto = array();
		foreach($this->vendas as $pdvvenda){
			foreach($venda->pdvitem as $pdvitem){
				$arr_codproduto[] = $pdvitem->getcodproduto();
			}
		}
		$arr_codproduto = array_merge(array_unique($arr_codproduto));

		// Recalcula venda media
		$vendamedia = new VendaMedia($this->con);
		$vendamedia->setcodestabelec($this->estabelecimento->getcodestabelec());
		foreach($arr_codproduto as $i => $codproduto){
			setprogress($i / count($arr_codproduto) * 100, "Atualizando venda media: ".($i + 1)." de ".count($arr_codproduto));
			$vendamedia->setcodproduto($codproduto);
			if(!$vendamedia->atualizar()){
				$this->con->rollback();
				return false;
			}
		}

		// Confirma transacao no banco
		$this->con->commit();

		// Retorna sucesso
		return true;
	}

	function gravarvendas_recebimento($pdvfinalizador, $paramestoque){
		if(!$pdvfinalizador->getrecebepdv()){
			return true;
		}

		if(strlen($pdvfinalizador->getcodfinaliz()) === 0){
			$_SESSION["ERROR"] = "Existe um recebimento sem finalizadora definida.";
			return false;
		}

		// Carrega a finalizadora
		$finalizadora = objectbytable("finalizadora", null, $this->con);
		$finalizadora->setcodestabelec($this->estabelecimento->getcodestabelec());
		$finalizadora->setcodfinaliz($pdvfinalizador->getcodfinaliz());
		$arr_finalizadora = object_array($finalizadora);
		if(count($arr_finalizadora) === 0){
			$_SESSION["ERROR"] = "Não foi encotrado a finalizadora '{$pdvfinalizador->getcodfinaliz()}' do estabelecimento {$this->estabelecimento->getcodestabelec()} para efetuar o recebimento.";
			return false;
		}
		$finalizadora = array_shift($arr_finalizadora);

		// Grava o recebimentos
		$codrecebepdv = null;
		foreach($this->arr_recebepdv as $recebepdv){
			if(intval($recebepdv->getcupom()) === intval($pdvfinalizador->getcupom())){
				$recebepdv->setconnection($this->con);
				if(!$recebepdv->save()){
					$_SESSION["ERROR"] = $_SESSION["ERROR"];
					return false;
				}
				$codrecebepdv = $recebepdv->getcodrecebepdv();
				break;
			}
		}

		// Verifica se deve gerar o financeiro
		if($finalizadora->getgerafinanceiro() === "N"){
			return true;
		}

		// Define o parceiro
		if(strlen($finalizadora->gettipoparceiro()) > 0 && strlen($finalizadora->getcodparceiro()) > 0){
			$tipoparceiro = $finalizadora->gettipoparceiro();
			$codparceiro = $finalizadora->getcodparceiro();
		}else{
			$tipoparceiro = "C";
			$codparceiro = $paramestoque->getcodclientevendapdv();
		}

		// Carrega tabelas auxiliares
		$especie = objectbytable("especie", $finalizadora->getcodespecie(), $this->con);
		$condpagto = objectbytable("condpagto", $finalizadora->getcodcondpagto(), $this->con);

		// Calcula data de vencimento
		if(strlen($pdvfinalizador->getdatavencto()) > 0){
			$dtvencto = $pdvfinalizador->getdatavencto();
		}else{
			$arr_data = explode("-", value_date($pdvfinalizador->getdata()));
			if($condpagto->gettipo() === "PV"){
				$dtinicial = mktime(0, 0, 0, $arr_data[1], ($arr_data[2] + $condpagto->getdiascarencia()), $arr_data[0]);
				if(strlen($condpagto->getdiavencimento()) > 0){
					if(date("d", $dtinicial) < $condpagto->getdiavencimento()){
						$dtvencto = date("Y-m-d", mktime(0, 0, 0, date("m", $dtinicial), $condpagto->getdiavencimento(), date("Y", $dtinicial)));
					}else{
						$dtvencto = date("Y-m-d", mktime(0, 0, 0, (date("m", $dtinicial) + 1), $condpagto->getdiavencimento(), date("Y", $dtinicial)));
					}
				}else{
					$dtvencto = date("Y-m-d", $dtinicial);
				}
			}else{
				$dtvencto = date("Y-m-d", mktime(0, 0, 0, $arr_data[1], ($arr_data[2] + $condpagto->getdia1()), $arr_data[0]));
			}
		}

		// Verifica se gera liquidado
		if($especie->getgeraliquidado() === "S"){
			$valorpago = $pdvfinalizador->getvalortotal();
			$dtliquid = $pdvfinalizador->getdata();
		}else{
			$valorpago = 0;
			$dtliquid = 0;
		}

		// Cria o lancamento
		$lancamento = objectbytable("lancamento", NULL, $this->con);
		$lancamento->setsincpdv("2");
		$lancamento->setcodestabelec($this->estabelecimento->getcodestabelec());
		$lancamento->setpagrec("R");
		$lancamento->setprevreal("R");
		$lancamento->setparcela(1);
		$lancamento->settotalparcelas(1);
		$lancamento->settipoparceiro($tipoparceiro);
		$lancamento->setcodparceiro($codparceiro);
		$lancamento->setnumnotafis($pdvfinalizador->getcupom());
		$lancamento->setserie("REC");
		$lancamento->setcodcondpagto($finalizadora->getcodcondpagto());
		$lancamento->setcodespecie($finalizadora->getcodespecie());
		$lancamento->setvalorparcela($pdvfinalizador->getvalortotal());
		$lancamento->setvalorpago($valorpago);
		$lancamento->setdtemissao($pdvfinalizador->getdata());
		$lancamento->setdtentrada($pdvfinalizador->getdata());
		$lancamento->setdtvencto($dtvencto);
		$lancamento->setdtliquid($dtliquid);

		$lancamento->setcodcatlancto($finalizadora->getcodcatlancto());
		$lancamento->setcodsubcatlancto($finalizadora->getcodsubcatlancto());
		if(strlen($pdvfinalizador->getnumcheque()) > 0){
			$observacao = "\nBanco: ".$pdvfinalizador->getcodbanco()."\nAgencia: ".$pdvfinalizador->getnumagenciacheq()."\nConta: ".$pdvfinalizador->getcontacheque()."\nCheque: ".$pdvfinalizador->getnumcheque();
			$lancamento->setobservacao($lancamento->getobservacao().$observacao);
		}
		$lancamento->setcodccusto($finalizadora->getcodccusto());
		$lancamento->setcodcontacred($this->estabelecimento->getcodconta());
		$lancamento->setcodfinaliz($finalizadora->getcodfinaliz());
		$lancamento->setcodmoeda($finalizadora->getcodmoeda());
		$lancamento->setcodbanco($finalizadora->getcodbanco());
		$lancamento->setdocliquidacao($pdvfinalizador->getnumcheque());
		$lancamento->setobservacao("Recebimento PDV cupom: {$pdvfinalizador->getcupom()}");
		if(!$lancamento->save()){
			return false;
		}

		// Vincula o recebimento com o pagamento
		$recebepdvlancto = objectbytable("recebepdvlancto", null, $this->con);
		$recebepdvlancto->setcodrecebepdv($codrecebepdv);
		$recebepdvlancto->setcodlancto($lancamento->getcodlancto());
		if(!$recebepdvlancto->save()){
			return false;
		}

		return true;
	}

	function gravarvendas_vasilhame(){
		// Grava os Vasilhames
		if(is_array($this->vasilhames)){
			foreach($this->vasilhames AS $arr_pdvvasilhame){
				foreach($arr_pdvvasilhame as $pdvvasilhame){
					if(substr($pdvvasilhame->getentsai(), 0, 1) == "D"){
						$entsai = substr($pdvvasilhame->getentsai(), 1, 1);
						$query = "DELETE ";
						$query .= "FROM vasilhame ";
						$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
						if(strlen($pdvvasilhame->getdtvenda()) > 0){
							$query .= " AND dtvenda = '{$pdvvasilhame->getdtvenda()}'";
						}else{
							$query .= " AND dtrecepcao = '{$pdvvasilhame->getdtrecepcao()}'";
						}
						$query .= " AND entsai = '{$entsai}' ";
						$query .= " AND codvasilhame = {$pdvvasilhame->getcodvasilhame()} ";
						$query .= " AND numrecepcao = {$pdvvasilhame->getnumrecepcao()} ";
						$res = $this->con->query($query);

						if(!$res->execute()){
							$_SESSION["ERROR"] = "Vasilhame com error.";
							return false;
						}
					}else{
						if(strlen($pdvvasilhame->getcodvasilhame()) == 0){
							continue;
						}
						$query = "SELECT codvasilhame ";
						$query .= "FROM vasilhame ";
						$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
						if(strlen($pdvvasilhame->getdtvenda()) > 0){
							$query .= " AND dtvenda = '{$pdvvasilhame->getdtvenda()}'";
						}else{
							$query .= " AND dtrecepcao = '{$pdvvasilhame->getdtrecepcao()}'";
						}
						$query .= " AND entsai = '{$pdvvasilhame->getentsai()}' ";
						$query .= " AND codvasilhame = {$pdvvasilhame->getcodvasilhame()} ";
						$query .= " AND numrecepcao = {$pdvvasilhame->getnumrecepcao()} ";

						$res = $this->con->query($query);
						$arr = $res->fetchAll(2);
						if(sizeof($arr) > 0){
							continue;
						}

						$vasilhame = objectbytable("vasilhame", null, $this->con);
						$vasilhame->setcodestabelec($this->estabelecimento->getcodestabelec());
						$vasilhame->setnumrecepcao($pdvvasilhame->getnumrecepcao());
						$vasilhame->setcodcliente($pdvvasilhame->getcodcliente());
						$vasilhame->setcaixa($pdvvasilhame->getcaixa());
						$vasilhame->setdtvenda($pdvvasilhame->getdtvenda());
						$vasilhame->setdtrecepcao($pdvvasilhame->getdtrecepcao());
						$vasilhame->setcodfunc($pdvvasilhame->getcodfunc());
						$vasilhame->setquantidade($pdvvasilhame->getquantidade());
						$vasilhame->setcodvasilhame($pdvvasilhame->getcodvasilhame());
						$vasilhame->setentsai($pdvvasilhame->getentsai());

						if(!$vasilhame->save()){
							$_SESSION["ERROR"] = "Vasilhame com error.";
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	function gravarvendas_trocaitem($pdvvenda, $pdvitem, $produto, $paramestoque, $arrComposicao){
		if(strlen($paramestoque->getcodtpdoctotrocapdv()) === 0){
			$_SESSION["ERROR"] = "Tipo de documento para troca no PDV n&atilde;o foi informado na par&acirc;metriza&ccedil;&atilde;o de estoque.<br><a onclick=\"$.messageBox('close'); openProgram('ParamEst','codemitente=".$this->estabelecimento->getcodemitente()."')\">Clique aqui</a> para abrir os  par&acirc;metros de estoque.";
			return false;
		}

		// Cria o ItCupom apenas para tratar a composicao
		$itcupom = objectbytable("itcupom", null, $this->con);
		$itcupom->setcodproduto((integer) $pdvitem->getcodproduto());
		$itcupom->setcustorep($produto["custo"]);
		$itcupom->setquantidade($pdvitem->getquantidade());
		$itcupom->setpreco($pdvitem->getpreco());

		// Trata composicao
		$arr_itcupom_comp = $this->explodirproduto($itcupom, $arrComposicao);
		if(count($arr_itcupom_comp) > 0){
			// Produto de composicao
			foreach($arr_itcupom_comp as $itcupom_comp){
				$movimento = objectbytable("movimento", NULL, $this->con);
				$movimento->setcodestabelec($this->estabelecimento->getcodestabelec());
				$movimento->setcodproduto($itcupom_comp->getcodproduto());
				$movimento->settipo("E");
				$movimento->setdtmovto($pdvvenda->getdata());
				$movimento->setquantidade($itcupom_comp->getquantidade());
				$movimento->setpreco($itcupom_comp->getpreco());
				$movimento->setcustorep($itcupom_comp->getcustorep());
				$movimento->setcupom($pdvvenda->getcupom());
				$movimento->setpdv($pdvvenda->getcaixa());
				$movimento->sethrmovto($pdvvenda->gethora());
				$movimento->setcodunidade($produto["codunidade"]);
				$movimento->setcodtpdocto($paramestoque->getcodtpdoctotrocapdv());
				if(!$movimento->save()){
					return false;
				}
			}
		}else{
			// Produto normal (sem composicao)
			$movimento = objectbytable("movimento", null, $this->con);
			$movimento->setcodestabelec($this->estabelecimento->getcodestabelec());
			$movimento->setcodproduto($pdvitem->getcodproduto());
			$movimento->settipo("E");
			$movimento->setdtmovto($pdvvenda->getdata());
			$movimento->setquantidade($pdvitem->getquantidade());
			$movimento->setpreco($pdvitem->getpreco());
			$movimento->setcustorep($produto["custorep"]);
			$movimento->setcupom($pdvvenda->getcupom());
			$movimento->setpdv($pdvvenda->getcaixa());
			$movimento->sethrmovto($pdvvenda->gethora());
			$movimento->setcodunidade($produto["codunidade"]);
			$movimento->setcodtpdocto($paramestoque->getcodtpdoctotrocapdv());
			if(!$movimento->save()){
				return false;
			}
		}

		return true;
	}

	function gravarvendas(){
		// Verifica se executa o metodo antigo ou novo
		if(param("FRENTECAIXA", "METODOGRAVARVENDAS", $this->con) === "1"){
			return $this->gravarvendas_();
		}

		// Grava os Vasilhames
		if(is_array($this->vasilhames)){
			foreach($this->vasilhames AS $arr_pdvvasilhame){
				foreach($arr_pdvvasilhame as $pdvvasilhame){
					if(substr($pdvvasilhame->getentsai(), 0, 1) == "D"){
						$entsai = substr($pdvvasilhame->getentsai(), 1, 1);
						$query = "DELETE ";
						$query .= "FROM vasilhame ";
						$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
						if(strlen($pdvvasilhame->getdtvenda()) > 0){
							$query .= " AND dtvenda = '{$pdvvasilhame->getdtvenda()}'";
						}else{
							$query .= " AND dtrecepcao = '{$pdvvasilhame->getdtrecepcao()}'";
						}
						$query .= " AND entsai = '{$entsai}' ";
						$query .= " AND codvasilhame = {$pdvvasilhame->getcodvasilhame()} ";
						$query .= " AND numrecepcao = {$pdvvasilhame->getnumrecepcao()} ";
						$res = $this->con->query($query);

						if(!$res->execute()){
							$_SESSION["ERROR"] = "Vasilhame com error.";
							return FALSE;
						}
					}else{
						if(strlen($pdvvasilhame->getcodvasilhame()) == 0){
							continue;
						}
						$query = "SELECT codvasilhame ";
						$query .= "FROM vasilhame ";
						$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
						if(strlen($pdvvasilhame->getdtvenda()) > 0){
							$query .= " AND dtvenda = '{$pdvvasilhame->getdtvenda()}'";
						}else{
							$query .= " AND dtrecepcao = '{$pdvvasilhame->getdtrecepcao()}'";
						}
						$query .= " AND entsai = '{$pdvvasilhame->getentsai()}' ";
						$query .= " AND codvasilhame = {$pdvvasilhame->getcodvasilhame()} ";
						$query .= " AND numrecepcao = {$pdvvasilhame->getnumrecepcao()} ";

						$res = $this->con->query($query);
						$arr = $res->fetchAll(2);
						if(sizeof($arr) > 0){
							continue;
						}

						$vasilhame = objectbytable("vasilhame", null, $this->con);
						$vasilhame->setcodestabelec($this->estabelecimento->getcodestabelec());
						$vasilhame->setnumrecepcao($pdvvasilhame->getnumrecepcao());
						$vasilhame->setcodcliente($pdvvasilhame->getcodcliente());
						$vasilhame->setcaixa($pdvvasilhame->getcaixa());
						$vasilhame->setdtvenda($pdvvasilhame->getdtvenda());
						$vasilhame->setdtrecepcao($pdvvasilhame->getdtrecepcao());
						$vasilhame->setcodfunc($pdvvasilhame->getcodfunc());
						$vasilhame->setquantidade($pdvvasilhame->getquantidade());
						$vasilhame->setcodvasilhame($pdvvasilhame->getcodvasilhame());
						$vasilhame->setentsai($pdvvasilhame->getentsai());

						if(!$vasilhame->save()){
							$_SESSION["ERROR"] = "Vasilhame com error.";
							return FALSE;
						}
					}
				}
			}
		}

		if(sizeof($this->vendas) == 0 && sizeof($this->arr_recebepdv) == 0 && sizeof($this->vasilhames) == 0){
			$_SESSION["ERROR"] = "Nenhuma venda foi encontrada.";
			return FALSE;
		}

		setprogress(10, "Carregando os parametros necessarios para importacao.", TRUE);
		// Carrega os parametros de estoque
		$paramcomissao = objectbytable("paramcomissao", $this->estabelecimento->getcodestabelec(), $this->con);
		if(!$paramcomissao->exists()){
			$_SESSION["ERROR"] = "Par&acirc;metros de comiss&atilde;o ainda n&atilde;o foram informados para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamComissao','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir os par&acirc;metros de comiss&atilde;o.";
			return FALSE;
		}
		setprogress(35, "Carregando os parametros necessarios para importacao.", TRUE);
		$paramestoque = objectbytable("paramestoque", $this->estabelecimento->getcodemitente(), $this->con);
		if(strlen($paramestoque->getcodclientevendapdv()) == 0){
			$_SESSION["ERROR"] = "Cliente padr&atilde;o n&atilde;o foi informado na par&acirc;metriza&ccedil;&atilde;o de estoque.<br><a onclick=\"$.messageBox('close'); openProgram('ParamEst','codemitente=".$this->estabelecimento->getcodemitente()."')\">Clique aqui</a> para abrir os  par&acirc;metros de estoque.";
			return FALSE;
		}
		setprogress(75, "Carregando os parametros necessarios para importacao.", TRUE);
		$codtpdocto = $paramestoque->getcodtpdoctovendapdv();
		if(strlen($codtpdocto) == 0){
			$_SESSION["ERROR"] = "Tipo de documento para venda (PDV) n&atilde;o foi informado na par&acirc;metriza&ccedil;&atilde;o de estoque.<br><a onclick=\"$.messageBox('close'); openProgram('ParamEst','codemitente=".$this->estabelecimento->getcodemitente()."')\">Clique aqui</a> para abrir os  par&acirc;metros de estoque.";
			return FALSE;
		}

		// Verifica se o cupom ativo soh tem itens cancelados
		foreach($this->vendas as $pdvvenda){
			if($pdvvenda->getstatus() === "A"){
				$cancelado = true;
				foreach($pdvvenda->pdvitem as $pdvitem){
					if($pdvitem->getstatus() !== "C"){
						$cancelado = false;
						break;
					}
				}
				if($cancelado){
					$pdvvenda->setstatus("C");
				}
			}
		}

		setprogress(90, "Verificando ECFs cadastradas", TRUE);
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setcodestabelec($this->estabelecimento->getcodestabelec());
		$ecf->setstatus("A");
		$arr_ecf_aux = object_array($ecf);
		$arr_ecf = array();
		foreach($arr_ecf_aux as $ecf){
			$arr_ecf[$ecf->getcaixa()] = $ecf;
		}
		foreach($this->vendas as $pdvvenda){
			if(!isset($arr_ecf[$pdvvenda->getcaixa()])){
				$_SESSION["ERROR"] = "Equipamento fiscal no caixa {$pdvvenda->getcaixa()} n&atilde;o foi encontrado no cadastro para o cupom de número {$pdvvenda->getcupom()}.<br><a onclick=\"$.messageBox('close'); openProgram('Ecf')\">Clique aqui</a> para abrir o cadastro de Equipamentos Fiscais.";
				return false;
			}
		}

		// Carrega as datas que ja foram feitas leitura na loja
		$arrDatas = array("old" => array(), "new" => array());
		$leituradata = objectbytable("leituradata", NULL, $this->con);
		$leituradata->setcodestabelec($this->estabelecimento->getcodestabelec());
		$arr_leituradata = object_array($leituradata);
		foreach($arr_leituradata as $leituradata){
			$arrDatas["old"][] = $leituradata->getdtmovto();
		}

		// Verifica os produtos cadastrados
		setprogress(0, "Verificando produtos cadastrados.", true);
		$arr_codproduto = array();
		foreach($this->vendas as $pdvvenda){
			foreach($pdvvenda->pdvitem as $pdvitem){
				$arr_codproduto[] = $pdvitem->getcodproduto();
			}
		}
		$arr_codproduto = array_unique($arr_codproduto);
		$priopesqproduto = $this->frentecaixa->gettipocodproduto() == "E" ? "1" : "0";
		$consultaproduto = new ConsultaProduto($this->con, $priopesqproduto);
		$consultaproduto->addcodproduto($arr_codproduto);
		$consultaproduto->consultar();
		$arr_codproduto_naoencontrado = $consultaproduto->getnaoencontrado();
		if(count($arr_codproduto_naoencontrado) > 0){
			$param_estoque_codprodnaocad = trim(param("ESTOQUE", "CODPRODNAOCAD", $this->con));
			if(strlen($param_estoque_codprodnaocad) > 0){
				foreach($this->vendas as $pdvvenda){
					foreach($pdvvenda->pdvitem as $pdvitem){
						if(in_array($pdvitem->getcodproduto(), $arr_codproduto_naoencontrado)){
							$pdvitem->setcodproduto($param_estoque_codprodnaocad);
						}
					}
				}
			}else{
				$_SESSION["ERROR"] = "Existem produtos não cadastrados no arquivo de vendas. Recadastre os produtos apagados para prosseguir com a leitura.<br><br>Lista de produtos não encontrados:<br>".implode(", ", $arr_codproduto_naoencontrado);
				return false;
			}
		}
		$arr_codproduto_encontrado = $consultaproduto->getencontrado();
		foreach($this->vendas as $pdvvenda){
			foreach($pdvvenda->pdvitem as $pdvitem){
				$codproduto = $arr_codproduto_encontrado[$pdvitem->getcodproduto()];
				if(strlen($codproduto) === 0){
					$codproduto = $param_estoque_codprodnaocad;
				}
				$pdvitem->setcodproduto($codproduto);
			}
		}

		// Carrega os produtos
		if(count($arr_codproduto_encontrado) > 0){
			setprogress(0, "Carregando cadastro de produtos.", true);
			$query = "SELECT produto.codproduto, produtoean.codean, produtoestab.custorep AS custo, produtoestab.custotab, produtoestab.custosemimp, classfiscal.tptribicms, classfiscal.aliqicms, ";
			$query .= "	classfiscal.aliqredicms, embalagem.codunidade, embalagem.quantidade AS qtdeunidade, piscofins.aliqpis, piscofins.aliqcofins ";
			$query .= "FROM produto ";
			$query .= "LEFT JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
			$query .= "LEFT JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
			$query .= "LEFT JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
			$query .= "LEFT JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
			$query .= "LEFT JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
			$query .= "WHERE produtoestab.codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= "  AND produto.codproduto IN (".implode(",", $arr_codproduto_encontrado).") ";
			$query .= "ORDER BY produto.codproduto ";
			$res = $this->con->query($query);
			$arrProduto = $res->fetchAll(2);
		}else{
			$arrProduto = array();
		}

		// Carrega a lista de clientes cadastrados
		setprogress(0, "Carregando cadastro de clientes.", TRUE);
		$res = $this->con->query("SELECT codcliente, cpfcnpj FROM cliente");
		$arr = $res->fetchAll(2);
		$arrCliente = array();
		foreach($arr as $row){
			$arrCliente[$row["codcliente"]] = $row["cpfcnpj"];
		}

		// Carrega a lista de todas as finalizadoras cadastradas
		setprogress(0, "Carregando cadastro de finalizadoras.", TRUE);
		$res = $this->con->query("SELECT finalizadora.*, especie.geraliquidado FROM finalizadora INNER JOIN especie ON (finalizadora.codespecie = especie.codespecie) WHERE codestabelec = ".$this->estabelecimento->getcodestabelec());
		$arr = $res->fetchAll(2);
		$arr_finalizadora = array();
		foreach($arr as $row){
			$arr_finalizadora[$row["codfinaliz"]] = $row;
		}

		$arrCodProduto = array();
		$arrCodEan = array();
		foreach($arrProduto as $i => $row){
			$arrCodProduto[$i] = $row["codproduto"];
			$arrCodEan[$i] = $row["codean"];
		}

		// Carrega lista de bancos cadastrados
		setprogress(0, "Carregando cadastro de bancos.", TRUE);
		$res = $this->con->query("SELECT * FROM banco");
		$arr = $res->fetchAll(2);
		$arr_banco = array();
		foreach($arr as $row){
			$arr_banco[$row["codoficial"]] = $row;
		}

		// Carrega as condicoes de pagamento
		setprogress(0, "Carregando condicoes de pagamento", TRUE);
		$arr_condpagto = array();
		$arr_condpagto_aux = object_array(objectbytable("condpagto", NULL, $this->con));
		foreach($arr_condpagto_aux as $condpagto){
			$arr_condpagto[$condpagto->getcodcondpagto()] = $condpagto;
		}

		// Verifica se todas as finalizadoras e BIN existem no cadastro
		if(TRUE){
			$query = "SELECT DISTINCT ad.tipotransacao AS tipotransacao, adbin.codadminist,adbin.bin ";
			$query .= "FROM administradorabin AS adbin ";
			$query .= "INNER JOIN administradora ad ON (adbin.codadminist = ad.codadminist) ";
			$query .= "WHERE ad.tipotransacao IS NOT NULL AND adbin.bin IS NOT NULL ";
			$query .= "ORDER BY 1, 2, 3 ";
		}

		$res = $this->con->query($query);
		$this->arr_bin = $res->fetchAll();

		$arr_finalizadora_naoencontrado = array();
		$arr_bin_naoencontrado = array();
		foreach($this->finalizadoras as $finalizador){
			if(!array_key_exists($finalizador->getcodfinaliz(), $arr_finalizadora)){
				$arr_finalizadora_naoencontrado[] = $finalizador->getcodfinaliz();
			}

			$ok = FALSE;
			if(strlen($finalizador->getbin()) > 0 && strlen($finalizador->gettipotransacao()) > 0 && sizeof($this->arr_bin) > 0){
				foreach($this->arr_bin AS $bin){
					if($finalizador->getbin() == $bin["bin"] && $finalizador->gettipotransacao() == $bin["tipotransacao"]){
						if(strlen($bin["codadminist"]) > 0){
							$ok = TRUE;
							break;
						}
					}
				}
				if(!$ok){
					$arr_bin_naoencontrado[] = $finalizador->getbin()." ".$finalizador->gettipotransacao();
				}
			}
		}
		$arr_finalizadora_naoencontrado = array_unique($arr_finalizadora_naoencontrado);
		if(sizeof($arr_finalizadora_naoencontrado) > 0){
			$_SESSION["ERROR"] = "Existem finalizadoras n&atilde;o cadastradas no arquivo de vendas. Cadastre as finalizadoras para prosseguir com a leitura.<br><br>Lista de finalizadoras n&atilde;o encontradas:<br>".implode(", ", $arr_finalizadora_naoencontrado);
			return FALSE;
		}
		if(sizeof($arr_bin_naoencontrado) > 0){
			$_SESSION["ERROR"] = "Existem BIN n&atilde;o cadastradas no arquivo de vendas. Cadastre os BIN para prosseguir com a leitura.<br><br>Lista de BIN n&atilde;o encontradas:<br>".implode(", ", array_unique($arr_bin_naoencontrado));
			return FALSE;
		}

		// Junta os mesmos produtos do cupom
		foreach($this->vendas as $pdvvenda){
			$arr_pdvitem = array();
			foreach($pdvvenda->pdvitem as $pdvitem1){
				$found = FALSE;
				foreach($arr_pdvitem as $pdvitem2){
					if($pdvitem1->getcodproduto() == $pdvitem2->getcodproduto() && $pdvitem1->getstatus() == $pdvitem2->getstatus()){
						$pdvitem2->setquantidade($pdvitem2->getquantidade() + $pdvitem1->getquantidade());
						$pdvitem2->setdesconto($pdvitem2->getdesconto() + $pdvitem1->getdesconto());
						$pdvitem2->setacrescimo($pdvitem2->getacrescimo() + $pdvitem1->getacrescimo());
						$pdvitem2->settotal($pdvitem2->gettotal() + $pdvitem1->gettotal());
						$pdvitem2->setpreco(($pdvitem2->gettotal() + $pdvitem2->getdesconto() - $pdvitem2->getacrescimo()) / $pdvitem2->getquantidade());
						$found = TRUE;
						break;
					}
				}
				if(!$found){
					$arr_pdvitem[] = $pdvitem1;
				}
			}
			$pdvvenda->pdvitem = $arr_pdvitem;
		}

		// Busca as composicoes do tipo explode na venda
		setprogress(0, "Carregando cadastro de composicoes", TRUE);
		$arrComposicao = array();
		if(sizeof($arrCodProduto) > 0){
			$res = $this->con->query("SELECT * FROM composicao WHERE tipo IN ('A','V')");
			$arrComposicaoAux = $res->fetchAll(2);
			foreach($arrComposicaoAux as $rowComposicao){
				$res = $this->con->query("SELECT * FROM itcomposicao WHERE codcomposicao = ".$rowComposicao["codcomposicao"]);
				$arrComposicao[$rowComposicao["codproduto"]] = $rowComposicao;
				$arrComposicao[$rowComposicao["codproduto"]]["itcomposicao"] = $res->fetchAll(2);
			}
		}

		// Inicia a transacao
		$this->con->start_transaction();

		// Cadastra um novo cliente caso não encontrado o cpf ou cnpj
		if(param("CADASTRO", "CADAUTOCLIENTE", $this->con) == "S"){
			setprogress($i / sizeof($this->vendas) * 100, "Verificando novos clientes: ".($i + 1)." de ".sizeof($this->vendas));
			$arr_cpfcnpj = array();

			foreach($this->vendas as $venda){
				if($venda->getcpfcnpj() != NULL){
					$arr_cpfcnpj[] = $venda->getcpfcnpj();
				}
			}

			foreach($this->finalizadoras as $finalizadora){
				if($finalizadora->getcpfcliente() != NULL){
					$arr_cpfcnpj[] = $finalizadora->getcpfcliente();
				}
			}

			array_unique($arr_cpfcnpj);
			$res = $this->con->query("SELECT cpfcnpj FROM cliente");
			$arr = $res->fetchAll(2);

			foreach($arr_cpfcnpj as $cpfcnpj){
				if(valid_cpf($cpfcnpj) || valid_cnpj($cpfcnpj)){
					$cpfcnpj_bool = TRUE;
					foreach($arr as $row){
						if($cpfcnpj == $row["cpfcnpj"]){
							$cpfcnpj_bool = FALSE;
						}
					}
					if($cpfcnpj_bool){
						if(!$this->gravar_cliente($cpfcnpj)){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
			}
		}

		// Busca os parceiros dos lancamentos
		$arr_codadminist = array();
		setprogress(0, "Carregando cadastro de parceiros", TRUE);
		$arr_codcliente = array();
		$arr_codestabelec = array();
		$arr_codfornec = array();
		$arr_codtransp = array();
		$arr_codfunc = array();
		foreach($this->finalizadoras as $i => $finalizador){
			$finalizadora = $arr_finalizadora[$finalizador->getcodfinaliz()];
			if($finalizadora["gerafinanceiro"] == "S"){
				$this->finalizadora_parceiro($tipoparceiro, $codparceiro, $arr_finalizadora, $arrCliente, $finalizador, $paramestoque);
				switch($tipoparceiro){
					case "A": $arr_codadminist[] = $codparceiro;
						break;
					case "C": $arr_codcliente[] = $codparceiro;
						break;
					case "E": $arr_codestabelec[] = $codparceiro;
						break;
					case "F": $arr_codfornec[] = $codparceiro;
						break;
					case "T": $arr_codtransp[] = $codparceiro;
						break;
					case "U": $arr_codfunc[] = $codparceiro;
						break;
				}
			}
		}
		// Carrega os codigos dos vendedores que estao nos cupons
		foreach($this->vendas as $pdvvenda){
			$arr_codfunc[] = $pdvvenda->getcodfunc();
		}

		// Verifica os numeros de fabricacao para os equipamentos fiscais correspondentes
		if(in_array($this->pdv, array("syspdv", "saurus", "zanthus"))){
			$arr_numfabricacao = [];
			foreach($this->vendas as $venda){
				if(strlen($venda->getnumfabricacao()) > 0){
					if(!in_array($venda->getnumfabricacao(), $arr_numfabricacao)){
						$numfabricacao = trim($venda->getnumfabricacao());
						$query = "SELECT codecf FROM ecf WHERE status='A' AND numfabricacao = '{$numfabricacao}'";
						$res = $this->con->query($query);
						$codecf = $res->fetchColumn();
						if(strlen($codecf) <= 0){
							$msg = "Numero de fabrica&ccedil;&atilde;o do equipamento fiscal n&atilde;o encontrado <b>{$venda->getnumfabricacao()}</b> para o caixa <b>{$venda->getcaixa()}</b> e cupom <b>{$venda->getcupom()}</b>";
							$_SESSION["ERROR"] = $msg;
							return false;
						}else{
							$arr_numfabricacao[$numfabricacao] = $codecf;
						}
					}
				}
			}
		}

		// Carrega um array de objetos para cada tipo de parceiro
		$arr_administradora = object_array_key(objectbytable("administradora", NULL, $this->con), $arr_codadminist);
		$arr_cliente = object_array_key(objectbytable("cliente", NULL, $this->con), $arr_codcliente);
		$arr_estabelecimento = object_array_key(objectbytable("estabelecimento", NULL, $this->con), $arr_codestabelec);
		$arr_fornecedor = object_array_key(objectbytable("fornecedor", NULL, $this->con), $arr_codfornec);
		$arr_transportadora = object_array_key(objectbytable("transportadora", NULL, $this->con), $arr_codtransp);
		$arr_funcionario = object_array_key(objectbytable("funcionario", NULL, $this->con), $arr_codfunc);

		$administestabelec = objectbytable("administestabelec", NULL, $this->con);
		$administestabelec->setcodestabelec($this->estabelecimento->getcodestabelec());
		$arr_administestabelec = object_array($administestabelec);

		// Carrega a lista de codigo de vendedores cadastrados
		$res = $this->con->query("SELECT codfunc FROM funcionario");
		$arr = $res->fetchAll(2);
		$arr_codfunc = array();
		foreach($arr as $row){
			$arr_codfunc[] = $row["codfunc"];
		}

		// Remove cupons duplicados no mesmo lote
		$arr_pdvvenda = array();
		foreach($this->vendas as $pdvvenda1){
			foreach($arr_pdvvenda as $pdvvenda2){
				if($pdvvenda1->getdata() === $pdvvenda2->getdata() && $pdvvenda1->getcaixa() === $pdvvenda2->getcaixa() && $pdvvenda1->getcupom() === $pdvvenda2->getcupom() && $pdvvenda1->getstatus() === $pdvvenda2->getstatus()  && $pdvvenda1->getseqecf() === $pdvvenda2->getseqecf()){
					continue 2;
				}
			}
			$arr_pdvvenda[] = $pdvvenda1;
		}
		$this->vendas = $arr_pdvvenda;

		// Reordena as venda para ler os cancelamentos por ultimo
		$arr_venda_a = array();
		$arr_venda_c = array();
		foreach($this->vendas as $i => $venda){
			setprogress(($i + 1) / sizeof($this->vendas) * 100, "Reordenando cupons: ".($i + 1)." de ".sizeof($this->vendas));
			switch($venda->getstatus()){
				case "A": $arr_venda_a[] = $venda;
					break;
				case "C": $arr_venda_c[] = $venda;
					break;
			}
		}

		// Remove cupons duplicados (nao cancelados)
		setprogress(0, "Verificando cupons duplicados", TRUE);
		$arr_chave = array();
		foreach($arr_venda_a as $venda){
			$arr_chave[] = "('".$venda->getdata()."','".$venda->getcaixa()."','".$venda->getcupom()."','".$venda->getseqecf()."')";
			//$arr_chave[] = "('".$venda->getdata()."','".$venda->getcaixa()."','".$venda->getcupom()."')";
		}
		$arr_chave = array_unique($arr_chave);
		if(sizeof($arr_chave) > 0){
			$query = "SELECT idcupom, dtmovto, caixa, cupom, totalliquido ";
			$query .= "FROM cupom ";
			$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ((dtmovto,caixa,cupom,COALESCE(seqecf,'')) IN (".implode(",", $arr_chave).")) ";
			//$query .= " AND status = 'A' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $i => $row){
				setprogress((($i + 1) / sizeof($arr) * 100), "Removendo cupons duplicados: ".($i + 1)." de ".sizeof($arr));
				foreach($arr_venda_a as $j => $venda){
					if($venda->getdata() == $row["dtmovto"] && $venda->getcaixa() == $row["caixa"] && $venda->getcupom() == $row["cupom"]){
						$totalitens = 0;
						foreach($venda->pdvitem as $pdvitem){
							if($pdvitem->getstatus() === "A"){
								$totalitens += $pdvitem->gettotal();
							}
						}
						unset($arr_venda_a[$j]);
						/*if(round($totalitens, 2) == round($row["totalliquido"], 2)){
							unset($arr_venda_a[$j]);
						}else{
							$cupom = objectbytable("cupom", $row["idcupom"], $this->con);
							if(!$cupom->delete()){
								$this->con->rollback();
								return false;
							}
						}*/
					}
				}
				foreach($this->finalizadoras as $j => $finalizador){
					if($finalizador->getdata() == $row["dtmovto"] && $finalizador->getcaixa() == $row["caixa"] && $finalizador->getcupom() == $row["cupom"]){
						if(round($totalitens, 2) == round($row["totalliquido"], 2)){
							unset($this->finalizadoras[$j]);
						}
					}
				}
			}
		}

		// Remove cupons duplicados (cancelados)
		setprogress(0, "Verificando cupons duplicados", TRUE);
		$arr_chave = array();
		foreach($arr_venda_c as $venda){
			$arr_chave[] = "('".$venda->getdata()."','".$venda->getcaixa()."','".$venda->getcupom()."')";
		}
		$arr_chave = array_unique($arr_chave);
		if(sizeof($arr_chave) > 0){
			$query = "SELECT dtmovto, caixa, cupom ";
			$query .= "FROM cupom ";
			$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
			$query .= " AND ((dtmovto,caixa,cupom) IN (".implode(",", $arr_chave).")) ";
			$query .= " AND status = 'C' ";
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $i => $row){
				setprogress((($i + 1) / sizeof($arr) * 100), "Removendo cupons duplicados: ".($i + 1)." de ".sizeof($arr));
				foreach($arr_venda_c as $j => $venda){
					if($venda->getdata() == $row["dtmovto"] && $venda->getcaixa() == $row["caixa"] && $venda->getcupom() == $row["cupom"]){
						unset($arr_venda_c[$j]);
					}
				}
				foreach($this->finalizadoras as $j => $finalizador){
					if($finalizador->getdata() == $row["dtmovto"] && $finalizador->getcaixa() == $row["caixa"] && $finalizador->getcupom() == $row["cupom"]){
						unset($this->finalizadoras[$j]);
					}
				}
			}
		}

		// Remove Recebimento Duplicado
		setprogress(0, "Verificando recebimentos duplicados", TRUE);
		if(is_array($this->arr_recebepdv)){
			foreach($this->arr_recebepdv AS $i => $recebepdv){
				$query = "SELECT dtmovto, caixa, cupom, codestabelec ";
				$query .= "FROM recebepdv ";
				$query .= "WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." ";
				$query .= " AND dtmovto = '{$recebepdv->getdtmovto()}' AND caixa = {$recebepdv->getcaixa()} AND cupom='{$recebepdv->getcupom()}' ";

				$res = $this->con->query($query);
				$arr = $res->fetchAll(2);
				foreach($arr as $i => $row){
					setprogress((($i + 1) / sizeof($arr) * 100), "Removendo recebimentos duplicados: ".($i + 1)." de ".sizeof($arr));
					foreach($this->finalizadoras as $j => $finalizador){
						if($finalizador->getdata() == $row["dtmovto"] && $finalizador->getcaixa() == $row["caixa"] && $finalizador->getcupom() == $row["cupom"]){
							unset($this->finalizadoras[$j]);
						}
					}
				}
			}
		}

		// Junta as vendas normais com as canceladas
		$this->vendas = array_merge($arr_venda_a, $arr_venda_c);

		// Percorre todas as finalizadoras
		$arr_lancamento = array(); // Grupo de lancamentos sinteticos
		$arr_finaliz_cupom = array(); // Vetor que relacina os cupons com as finalizadoras
		$arr_lancto_cupom = array(); // Vetor que relaciona os cupons com os lancamentos
		$query = "SELECT DISTINCT ad.codadminist, 'codadminist' AS cod ";
		$query .= "FROM administradora ad ";
		$query .= "INNER JOIN administradorabin adbin ON (ad.codadminist = adbin.codadminist) ";
		$query .= "WHERE adbin.bin IS NOT NULL AND ad.codadminist IS NOT NULL ";
		$query .= "ORDER BY 1 ";

		$res = $this->con->query($query);
		$_arr_codadminist = $res->fetchAll();

		$a_arr_codadminist = array();
		foreach($_arr_codadminist AS $codadminist){
			$a_arr_codadminist[] = $codadminist["codadminist"];
		}
		foreach($this->finalizadoras as $i => $finalizador){
			setprogress($i / sizeof($this->finalizadoras) * 100, "Gerando lancamentos financeiros: ".($i + 1)." de ".sizeof($this->finalizadoras));
			// Verifica se ja nao foi feita a leitura na data da venda
			// Trata as novas datas na hora que for ler as vendas
			if(($this->estabelecimento->getleituraonline() == "N" && $this->estabelecimento->getcodfrentecaixa() != "11") && (in_array($finalizador->getdata(), $arrDatas["old"]) || !$this->entre_dtmovto($finalizador->getdata()))){
				continue;
			}
			if($finalizador->getstatus() == "A"){
				$finalizadora = $arr_finalizadora[$finalizador->getcodfinaliz()];

				// Grava os recebimentoos
				$codrecebepdv = "";
				foreach($this->arr_recebepdv AS $i => $recebepdv){
					if(intval($recebepdv->getcupom()) == intval($finalizador->getcupom())){
						if(!$recebepdv->save()){
							$_SESSION["ERROR"] = "Recebimento com erro. ".$_SESSION["ERROR"];
							return FALSE;
						}
						$codrecebepdv = $recebepdv->getcodrecebepdv();

						break;
					}
				}

				if($finalizadora["gerafinanceiro"] == "S"){
					$condpagto = $arr_condpagto[$finalizadora["codcondpagto"]];
					$banco = $arr_banco[$finalizador->getcodbanco()];
					$this->finalizadora_parceiro($tipoparceiro, $codparceiro, $arr_finalizadora, $arrCliente, $finalizador, $paramestoque);
					switch($tipoparceiro){
						case "A": $parceiro = $arr_administradora[$codparceiro];
							break;
						case "C": $parceiro = $arr_cliente[$codparceiro];
							break;
						case "E": $parceiro = $arr_estabelecimento[$codparceiro];
							break;
						case "F": $parceiro = $arr_fornecedor[$codparceiro];
							break;
						case "T": $parceiro = $arr_transportadora[$codparceiro];
							break;
						case "U": $parceiro = $arr_funcionario[$codparceiro];
							break;
					}

					$valordesconto = 0;
					if($tipoparceiro == "A"){
						foreach($arr_administestabelec as $administestabelec){
							if($parceiro->getcodadminist() == $administestabelec->getcodadminist()){
								$valordesconto = number_format($finalizador->getvalortotal() * $administestabelec->getpercdescto() / 100 + $administestabelec->getvaldescto(), 2);
								break;
							}
						}
					}
					if($finalizadora["geraliquidado"] == "S"){
						$valorpago = $finalizador->getvalortotal() - $valordesconto;
						$dtliquid = $finalizador->getdata();
					}else{
						$valorpago = 0;
						$dtliquid = NULL;
					}
					// Calcula data de vencimento
					if(strlen($finalizador->getdatavencto()) > 0){
						$dtvencto = $finalizador->getdatavencto();
					}else{
						$arr_data = explode("-", value_date($finalizador->getdata()));
						if($condpagto->gettipo() == "PV"){
							$dtinicial = mktime(0, 0, 0, $arr_data[1], ($arr_data[2] + $condpagto->getdiascarencia()), $arr_data[0]);
							if(strlen($condpagto->getdiavencimento()) > 0){
								if(date("d", $dtinicial) < $condpagto->getdiavencimento()){
									$dtvencto = date("Y-m-d", mktime(0, 0, 0, date("m", $dtinicial), $condpagto->getdiavencimento(), date("Y", $dtinicial)));
								}else{
									$dtvencto = date("Y-m-d", mktime(0, 0, 0, (date("m", $dtinicial) + 1), $condpagto->getdiavencimento(), date("Y", $dtinicial)));
								}
							}else{
								$dtvencto = date("Y-m-d", $dtinicial);
							}
						}else{
							$dtvencto = date("Y-m-d", mktime(0, 0, 0, $arr_data[1], ($arr_data[2] + $condpagto->getdia1()), $arr_data[0]));
						}
					}
					$lancamento = objectbytable("lancamento", NULL, $this->con);
					$lancamento->setsincpdv("2");
					$lancamento->setcodestabelec($this->estabelecimento->getcodestabelec());
					$lancamento->setpagrec("R");
					$lancamento->setprevreal("R");
					$lancamento->setparcela(1);
					$lancamento->settotalparcelas(1);
					$lancamento->settipoparceiro($tipoparceiro);
					$lancamento->setcodparceiro($codparceiro);
					$lancamento->setnumnotafis($finalizador->getcupom());
					if($finalizador->getrecebepdv()){
						$lancamento->setserie("REC");
					}else{
						$lancamento->setserie("PDV");
					}
					$lancamento->setcodcondpagto($condpagto->getcodcondpagto());
					$lancamento->setcodespecie($finalizadora["codespecie"]);
					$lancamento->setvalorparcela($finalizador->getvalortotal());
					$lancamento->setvalordescto($valordesconto);
					$lancamento->setvaloracresc(0);
					$lancamento->setvalorpago($valorpago);
					$lancamento->setdtemissao($finalizador->getdata());
					$lancamento->setdtentrada($finalizador->getdata());
					$lancamento->setdtvencto($dtvencto);
					$lancamento->setdtliquid($dtliquid);
					$lancamento->setcodcatlancto($finalizadora["codcatlancto"]);
					$lancamento->setcodsubcatlancto($finalizadora["codsubcatlancto"]);
					$lancamento->setobservacao("LANCAMENTO AUTOMATICO");
					if(strlen($finalizador->getnumcheque()) > 0){
						$observacao = "\nBanco: ".$finalizador->getcodbanco()."\nAgencia: ".$finalizador->getnumagenciacheq()."\nConta: ".$finalizador->getcontacheque()."\nCheque: ".$finalizador->getnumcheque();
						$lancamento->setobservacao($lancamento->getobservacao().$observacao);
					}
					$lancamento->setcodccusto($finalizadora["codccusto"]);
					$lancamento->setcodcontacred($this->estabelecimento->getcodconta());
					$lancamento->setcodfinaliz($finalizadora["codfinaliz"]);
					if($parceiro == "F"){
						$lancamento->setcodcontadeb($parceiro->getcodconta());
					}
					$lancamento->setcodmoeda($finalizadora["codmoeda"]);
					$lancamento->setcodbancocheq($banco["codbanco"]);
					if(strlen($finalizadora["codbanco"]) > 0){
						$lancamento->setcodbanco($finalizadora["codbanco"]);
					}
					$lancamento->setdocliquidacao($finalizador->getnumcheque());
					if($finalizador->getcodfunc() > 0 && in_array((string) $finalizador->getcodfunc(), $arr_codfunc)){
						$lancamento->setcodfunc($finalizador->getcodfunc());
					}
					if($finalizadora["tipogeracao"] == "A" || $finalizador->getrecebepdv()){ // Analitico
						if(!$lancamento->save()){
							$this->con->rollback();
							return FALSE;
						}
						if(!$finalizador->getrecebepdv()){
							$arr_lancto_cupom[$lancamento->getcodlancto()] = array(array(
									"caixa" => $finalizador->getcaixa(),
									"cupom" => $finalizador->getcupom(),
									"totalliquido" => $finalizador->getvalortotal(),
									"codfinaliz" => $finalizador->getcodfinaliz()
							));
						}
						if($finalizador->getrecebepdv()){
							$lancamento->setobservacao("Recebimento PDV cupom: {$finalizador->getcupom()}");

							if(!$lancamento->save()){
								$this->con->rollback();
								return FALSE;
							}

							$recebepdvlancto = objectbytable("recebepdvlancto", null, $this->con);
							$recebepdvlancto->setcodrecebepdv($codrecebepdv);
							$recebepdvlancto->setcodlancto($lancamento->getcodlancto());
							if(!$recebepdvlancto->save()){
								$this->con->rollback();
								return FALSE;
							}
						}
					}elseif($finalizadora["tipogeracao"] == "S"){ // Sintetico
						if(sizeof($_arr_codadminist) > 0){
							if($tipoparceiro == "A" && in_array($codparceiro, $a_arr_codadminist)){
								$tipo_sintetico = "A";
							}else{
								$tipo_sintetico = "F";
							}
						}else{
							$tipo_sintetico = "F";
						}

						$idx_lancto = $lancamento->getdtemissao().";".($tipo_sintetico == "A" ? $codparceiro : $finalizadora["codparceiro"]).";".$tipo_sintetico;

						// Sintetico por finalizadora
						if(count($this->finalizadoras) > 1000){
							$s_arr_lancamento = array();
						}else{
							$s_lancamento = objectbytable("lancamento", NULL, $this->con);
							$s_lancamento->setdtemissao($lancamento->getdtemissao());
							$s_lancamento->setcodfinaliz($lancamento->getcodfinaliz());
							$s_lancamento->setcodparceiro($tipo_sintetico == "A" ? $codparceiro : $finalizadora["codparceiro"]);
							$s_lancamento->settipoparceiro($tipoparceiro);
							$s_lancamento->setcodestabelec($lancamento->getcodestabelec());
							$s_lancamento->setserie("PDV");
							$s_arr_lancamento = object_array($s_lancamento);
						}
						if(sizeof($s_arr_lancamento) > 0){
							$_lancamento = array_shift($s_arr_lancamento);
							$_lancamento->setvalorparcela($_lancamento->getvalorparcela() + $lancamento->getvalorparcela());
							$_lancamento->setvalordescto($_lancamento->getvalordescto() + $lancamento->getvalordescto());
							$_lancamento->setvalorpago($_lancamento->getvalorpago() + $lancamento->getvalorpago());
							if(!$_lancamento->save()){
								$this->con->rollback;
								return FALSE;
							}
							$arr_lancamento[$idx_lancto] = $_lancamento;
						}else{
							if(array_key_exists($idx_lancto, $arr_lancamento)){
								$arr_lancamento[$idx_lancto]->setnumnotafis(NULL);
								$arr_lancamento[$idx_lancto]->setvalorparcela($arr_lancamento[$idx_lancto]->getvalorparcela() + $lancamento->getvalorparcela());
								$arr_lancamento[$idx_lancto]->setvalordescto($arr_lancamento[$idx_lancto]->getvalordescto() + $lancamento->getvalordescto());
								$arr_lancamento[$idx_lancto]->setvalorpago($arr_lancamento[$idx_lancto]->getvalorpago() + $lancamento->getvalorpago());
							}else{
								$arr_lancamento[$idx_lancto] = $lancamento;
							}
						}
						$found = FALSE;
						if(is_array($arr_finaliz_cupom[$idx_lancto])){
							foreach($arr_finaliz_cupom[$idx_lancto] as $j => $finaliz_cupom){
								if($finaliz_cupom["caixa"] == $finalizador->getcaixa() && $finaliz_cupom["cupom"] == $finalizador->getcupom() && $finaliz_cupom["codfinaliz"] == $finalizador->getcodfinaliz()){
									$arr_finaliz_cupom[$idx_lancto][$j]["totalliquido"] += $finalizador->getvalortotal();
									$found = TRUE;
									break;
								}
							}
						}
						if(!$found && !$finalizador->getrecebepdv()){
							$arr_finaliz_cupom[$idx_lancto][] = array(
								"caixa" => $finalizador->getcaixa(),
								"cupom" => $finalizador->getcupom(),
								"totalliquido" => $finalizador->getvalortotal(),
								"codfinaliz" => $finalizador->getcodfinaliz()
							);
						}

						if($finalizador->getrecebepdv()){
							$lancamento->setobservacao($lancamento->getobservacao()." Recebimento PDV cupom: {$finalizador->getcupom()};");
							if(!$lancamento->save()){
								$this->con->rollback();
								return FALSE;
							}
							$codrecebepdv = "";
							// Grava os recebimentoos
							foreach($this->arr_recebepdv AS $i => $recebepdv){
								if(intval($recebepdv->getcupom()) == intval($finalizador->getcupom()) && $recebepdv->getcaixa() == $finalizador->getcaixa()){
									if(!$recebepdv->save()){
										$_SESSION["ERROR"] = "Recebimento com erro. ".$_SESSION["ERROR"];
										return FALSE;
									}
									$codrecebepdv = $recebepdv->getcodrecebepdv();
									break;
								}
							}

							$recebepdvlancto = objectbytable("recebepdvlancto", null, $this->con);
							$recebepdvlancto->setcodrecebepdv($codrecebepdv);
							$recebepdvlancto->setcodlancto($lancamento->getcodlancto());
							if(!$recebepdvlancto->save()){
								$this->con->rollback();
								return FALSE;
							}
						}
					}
				}
			}
		}

		// Gravar os lancamentos sinteticos
		$i = 1;
		foreach($arr_lancamento as $codfinaliz => $lancamento){
			setprogress($i / sizeof($arr_lancamento) * 100, "Gravando lancamentos financeiros: ".($i++)." de ".sizeof($arr_lancamento));
			if(!$lancamento->save()){
				$this->con->rollback();
				return FALSE;
			}
			$arr_lancto_cupom[$lancamento->getcodlancto()] = $arr_finaliz_cupom[$codfinaliz];
		}

		// Percorre todas as vendas
		foreach($this->vendas as $i => $venda){
			setprogress($i / sizeof($this->vendas) * 100, "Gravando vendas: ".($i + 1)." de ".sizeof($this->vendas));

			// Verifica se ja nao foi feita a leitura na data da venda
			if(($this->estabelecimento->getleituraonline() == "N" && $this->estabelecimento->getcodfrentecaixa() != "11") && (in_array($venda->getdata(), $arrDatas["old"]) || !$this->entre_dtmovto($venda->getdata()))){
				continue;
			}else{
				// Grava leitura de data
				if(!in_array($venda->getdata(), $arrDatas["new"])){
					$arrDatas["new"][] = $venda->getdata();
					$leituradata = objectbytable("leituradata", NULL, $this->con);
					$leituradata->setcodestabelec($this->estabelecimento->getcodestabelec());
					$leituradata->setdtmovto($venda->getdata());
					if(!$leituradata->save()){
						$this->con->rollback();
						return FALSE;
					}
				}

				// Verifica se esta com todos os itens cancelados
				if($venda->getstatus() == "A"){
					$itens_cancelados = TRUE;
					foreach($venda->pdvitem as $item){
						if($item->getstatus() != "C"){
							$itens_cancelados = FALSE;
							break;
						}
					}
					if($itens_cancelados){
						$venda->setstatus("C");
					}
				}

				// Carrega a ECF correta da venda
				$ecf = $arr_ecf[$venda->getcaixa()];

				// Trata cupom cancelado
				if($venda->getstatus() == "C"){
					// Procura o cupom para atualizar seu status para cancelado
					$cupom = objectbytable("cupom", NULL, $this->con);
					$cupom->setcodestabelec($this->estabelecimento->getcodestabelec());
					$cupom->setcaixa($venda->getcaixa());
					$cupom->setdtmovto($venda->getdata());
					//$cupom->sethrmovto($venda->gethora());
					$cupom->setcupom($venda->getcupom());
					$cupom->setnumeroecf($venda->getnumeroecf());
					$cupom->setseqecf($venda->getseqecf());
					if(!in_array($this->pdv,array("syspdv","saurus"))){
						if(strlen($venda->getcodecf()) > 0){
							$cupom->setcodecf($venda->getcodecf());
						}else{
							$cupom->setcodecf($ecf->getcodecf());
						}
					}else{
						if(strlen($venda->getnumfabricacao()) > 0){
							$numfabricacao = trim($venda->getnumfabricacao());
							$cupom->setnumfabricacao($numfabricacao);
							$cupom->setcodecf($arr_numfabricacao[$numfabricacao]);
						}else{
							$cupom->setcodecf(null);
						}
					}
					$arr_cupom = object_array($cupom);
					if(count($arr_cupom) > 0){
						$cupom = array_shift($arr_cupom);
						$cupom->setstatus("C");
						if($cupom->save()){
							continue;
						}else{
							$this->con->rollback();
							return FALSE;
						}
					}
				}

				// Calcula os totais necessarios para gravar a venda
				$total_bruto = 0;
				$total_desconto = 0;
				$total_acrescimo = 0;
				foreach($venda->pdvitem as $item){
					if($item->getstatus() == "A" || $venda->getstatus() == "C"){
						$total_bruto += ($item->gettotal() + $item->getdesconto() - $item->getacrescimo());
						$total_desconto += $item->getdesconto();
						$total_acrescimo += $item->getacrescimo();
					}
				}

				// Cria cupom de venda
				$cupom = objectbytable("cupom", NULL, $this->con);
				$cupom->setstatus($venda->getstatus());
				$cupom->setcodestabelec($this->estabelecimento->getcodestabelec());
				$cupom->setcaixa($venda->getcaixa());
				$cupom->setnumeroecf($venda->getnumeroecf());
				$cupom->setdtmovto($venda->getdata());
				$cupom->sethrmovto($venda->gethora());
				$cupom->setcupom($venda->getcupom());
				$cupom->setseqecf($venda->getseqecf());
				$cupom->setchavecfe($venda->getchavecfe());
				$cupom->setoperador($venda->getoperador());

				if(strlen($venda->getreferencia()) > 0){
					$cupom->setreferencia($venda->getreferencia());
				}

				$cupom->setnumeroecf($venda->getnumeroecf());
				$cupom->setseqecf($venda->getseqecf());

				if(!in_array($this->pdv,array("syspdv","saurus"))){
					if(strlen($venda->getcodecf()) > 0){
						$cupom->setcodecf($venda->getcodecf());
					}else{
						$cupom->setcodecf($ecf->getcodecf());
					}
				}else{
					$ecf = $arr_ecf[$venda->getcaixa()];

					if($ecf->getequipamentofiscal() == "NFC"){
						$cupom->setcodecf($ecf->getcodecf());						
					}else{
						if(strlen($venda->getnumfabricacao()) > 0){
							$numfabricacao = trim($venda->getnumfabricacao());
							$cupom->setnumfabricacao($numfabricacao);
							$cupom->setcodecf($arr_numfabricacao[$numfabricacao]);
						}else{
							$cupom->setcodecf(null);
						}
					}
				}

				$cupom->setcpfcnpj($venda->getcpfcnpj());
				if($venda->getcodcliente() > 0 && array_key_exists($venda->getcodcliente(), $arrCliente)){
					$cupom->setcodcliente($venda->getcodcliente());
				}elseif(strlen($venda->getcpfcnpj()) > 0 && in_array($venda->getcpfcnpj(), $arrCliente)){
					$cupom->setcodcliente(array_search($venda->getcpfcnpj(), $arrCliente));
				}elseif(count($arr_codcliente) > 0){
					$cupom->setcodcliente($paramestoque->getcodclientevendapdv());
				}
				if($venda->getcodfunc() > 0 && in_array((string) $venda->getcodfunc(), $arr_codfunc)){
					$cupom->setcodfunc($venda->getcodfunc());
				}

				$cupom->settotalbruto($total_bruto);
				$cupom->settotaldesconto($total_desconto);
				$cupom->settotalacrescimo($total_acrescimo);
				$cupom->settotalliquido($total_bruto + $total_acrescimo - $total_desconto);

				if(!$cupom->save()){
					$this->con->rollback();
					return FALSE;
				}

				//$this->con->exec("INSERT INTO temporario VALUES ('{$cupom->getcupom()}', '{$cupom->getstatus()}', {$cupom->gettotalliquido()})");
				// Verifica se existe um orcamento da venda e busca os numeros de serie
				if($venda->getcodorcamento() > 0){
					$numeroserie = objectbytable("numeroserie", NULL, $this->con);
					$numeroserie->setcodorcamento($venda->getcodorcamento());
					$arr_numeroserie = object_array($numeroserie);
					foreach($arr_numeroserie as $numeroserie){
						$numeroserie->setdata($cupom->getdtmovto(TRUE));
						$numeroserie->sethora($cupom->gethrmovto());
						$numeroserie->setcodorcamento(NULL);
						$numeroserie->setidcupom($cupom->getidcupom());
						if(!$numeroserie->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}

				// Lancamentos financeiros
				if($cupom->getstatus() == "A"){
					// Liga os cupons com os lancamentos antes de gravar
					foreach($arr_lancto_cupom as $codlancto => $chaves_cupons){
						foreach($chaves_cupons as $chave_cupom){
							if($chave_cupom["caixa"] == $cupom->getcaixa() && $chave_cupom["cupom"] == $cupom->getcupom()){
								$cupomlancto = objectbytable("cupomlancto", NULL, $this->con);
								$cupomlancto->setidcupom($cupom->getidcupom());
								$cupomlancto->setcodlancto($codlancto);
								$cupomlancto->settotalliquido($chave_cupom["totalliquido"]);
								$cupomlancto->setcodfinaliz($chave_cupom["codfinaliz"]);
								if(!$cupomlancto->save()){
									$this->con->rollback();
									return FALSE;
								}
							}
						}
					}
					// Liga as comissoes geradas com os lancamentos
					foreach($arr_lancto_cupom as $codlancto => $chaves_cupons){
						foreach($chaves_cupons as $chave_cupom){
							if($chave_cupom["caixa"] == $cupom->getcaixa() && $chave_cupom["cupom"] == $cupom->getcupom()){
								if(strlen($cupom->getcodfunc()) > 0){
									$comissao = objectbytable("comissao", NULL, $this->con);
									$comissao->setidcupom($cupom->getidcupom());
									$comissao->searchbyobject();
									if($comissao->exists()){
										$comissao->setcodlanctopai($codlancto);
										if(!$comissao->save()){
											$this->con->rollback();
											return FALSE;
										}
									}
								}
								break 2;
							}
						}
					}
				}

				// Percorre todos os itens do cupom
				foreach($venda->pdvitem as $item){
					// Busca o indice que contem os dados do produto
					$idxProduto = array_search($item->getcodproduto(), $arrCodProduto);
					// Se o status do item esta como Atendido
					if($item->getstatus() == "A" || $item->getstatus() == "C"){
						// Cria leitura do item
						$itcupom = objectbytable("itcupom", NULL, $this->con);
						$itcupom->setidcupom($cupom->getidcupom());
						$itcupom->setcodproduto((integer) $item->getcodproduto());
						$itcupom->setcustorep($arrProduto[$idxProduto]["custo"]);
						$itcupom->setcustotab($arrProduto[$idxProduto]["custotab"]);
						$itcupom->setcustosemimp($arrProduto[$idxProduto]["custosemimp"]);
						$itcupom->setquantidade($item->getquantidade());
						$itcupom->setpreco($item->getpreco());
						$itcupom->setdesconto($item->getdesconto());
						$itcupom->setacrescimo($item->getacrescimo());
						$itcupom->setstatus($item->getstatus());

						if(strlen($item->gettptribicms()) == 0 || strlen($item->getaliqicms()) == 0){
							$itcupom->settptribicms($arrProduto[$idxProduto]["tptribicms"] == "R" ? "T" : $arrProduto[$idxProduto]["tptribicms"]);
							if(in_array($itcupom->gettptribicms(), array("T", "R"))){
								$itcupom->setaliqicms(number_format(($arrProduto[$idxProduto]["aliqicms"] - ($arrProduto[$idxProduto]["aliqicms"] * $arrProduto[$idxProduto]["aliqredicms"] / 100)), 2, ".", ""));
							}else{
								$itcupom->setaliqicms(0);
							}
						}else{
							$itcupom->settptribicms($item->gettptribicms());
							$itcupom->setaliqicms($item->getaliqicms());
						}
						$itcupom->setaliqpis($arrProduto[$idxProduto]["aliqpis"]);
						$itcupom->setaliqcofins($arrProduto[$idxProduto]["aliqcofins"]);
						$itcupom->setvalortotal($item->gettotal());

						// Nao explode os itens de explosao automatica do SysPDV (porque ja vem explodido no arquivo)
						$gravar_pai_composicao = TRUE;
						if($this->pdv == "syspdv"){
							foreach($arrComposicao as $composicao){
								if($composicao["explosaoauto"] == "S" && $composicao["codproduto"] == $itcupom->getcodproduto()){
									$gravar_pai_composicao = FALSE;
									break;
								}
							}
						}

						// Trata composicao
						if($gravar_pai_composicao){
							$arr_itcupom_comp = $this->explodirproduto($itcupom, $arrComposicao);
						}else{
							$arr_itcupom_comp = array();
						}
						if(sizeof($arr_itcupom_comp) > 0 || !$gravar_pai_composicao){
							$itcupom->setcomposicao("P");
						}else{
							$itcupom->setcomposicao("N");
						}

						// Salva item principal
						if(!$itcupom->save()){
							var_dump("Ao salvar itcupom pai: ".$itcupom->getcodproduto()." Cupom: ".$venda->getcupom());
							$this->con->rollback();
							return FALSE;
						}
						// Salva filhos da composicao
						foreach($arr_itcupom_comp as $itcupom_comp){
							if(!$itcupom_comp->save()){
								var_dump("Ao salvar itcupom filho: ".$itcupom_comp->getcodproduto());
								//($itcupom_comp->fields);
								$this->con->rollback();
								return FALSE;
							}
						}
						/*
						  // Se o status do item for Cancelado
						  }elseif($item->getstatus() == "C"){
						  // Procura o item no cupom para atualizar seu status para cancelado
						  $cupom = objectbytable("cupom",NULL,$this->con);
						  $cupom->setcodestabelec($this->estabelecimento->getcodestabelec());
						  $cupom->setcaixa($venda->getcaixa());
						  $cupom->setdtmovto($venda->getdata());
						  $cupom->setcupom($venda->getcupom());
						  $arr_cupom = object_array($cupom);
						  foreach($arr_cupom as $cupom){
						  $itcupom = objectbytable("itcupom",NULL,$this->con);
						  $itcupom->setidcupom($cupom->getidcupom());
						  if(strlen($item->getsequencial()) > 0){
						  $itcupom->setseqitem((integer) $item->getsequencial());
						  }else{
						  $itcupom->setcodproduto((integer) $item->getcodproduto());
						  }
						  $arr_itcupom = object_array($itcupom);
						  foreach($arr_itcupom as $itcupom){
						  $itcupom->setstatus("C");
						  if(!$itcupom->save()){
						  $this->con->rollback();
						  return FALSE;
						  }
						  }
						  }
						 */
						// Se for item de Troca
					}elseif($item->getstatus() == "T"){
						if(strlen($paramestoque->getcodtpdoctotrocapdv()) == 0){
							$_SESSION["ERROR"] = "Tipo de documento para troca no PDV n&atilde;o foi informado na par&acirc;metriza&ccedil;&atilde;o de estoque.<br><a onclick=\"$.messageBox('close'); openProgram('ParamEst','codemitente=".$this->estabelecimento->getcodemitente()."')\">Clique aqui</a> para abrir os  par&acirc;metros de estoque.";
							$this->con->rollback();
							return FALSE;
						}

						// Cria o ItCupom apenas para tratar a composicao
						$itcupom = objectbytable("itcupom", NULL, $this->con);
						$itcupom->setcodproduto((integer) $item->getcodproduto());
						$itcupom->setcustorep($arrProduto[$idxProduto]["custo"]);
						$itcupom->setquantidade($item->getquantidade());
						$itcupom->setpreco($item->getpreco());

						// Trata composicao
						$arr_itcupom_comp = $this->explodirproduto($itcupom, $arrComposicao);
						if(count($arr_itcupom_comp) > 0){
							// Produto de composicao
							foreach($arr_itcupom_comp as $itcupom_comp){
								$movimento = objectbytable("movimento", NULL, $this->con);
								$movimento->setcodestabelec($this->estabelecimento->getcodestabelec());
								$movimento->setcodproduto($itcupom_comp->getcodproduto());
								$movimento->settipo("E");
								$movimento->setdtmovto($venda->getdata());
								$movimento->setquantidade($itcupom_comp->getquantidade());
								$movimento->setpreco($itcupom_comp->getpreco());
								$movimento->setcustorep($itcupom_comp->getcustorep());
								$movimento->setcupom($venda->getcupom());
								$movimento->setpdv($venda->getcaixa());
								$movimento->sethrmovto($venda->gethora());
								$movimento->setcodunidade($arrProduto[$idxProduto]["codunidade"]);
								$movimento->setcodtpdocto($paramestoque->getcodtpdoctotrocapdv());
								if(!$movimento->save()){
									$this->con->rollback();
									return FALSE;
								}
							}
						}else{
							// Produto normal (sem composicao)
							$movimento = objectbytable("movimento", NULL, $this->con);
							$movimento->setcodestabelec($this->estabelecimento->getcodestabelec());
							$movimento->setcodproduto($item->getcodproduto());
							$movimento->settipo("E");
							$movimento->setdtmovto($venda->getdata());
							$movimento->setquantidade($item->getquantidade());
							$movimento->setpreco($item->getpreco());
							$movimento->setcustorep($arrProduto[$idxProduto]["custorep"]);
							$movimento->setcupom($venda->getcupom());
							$movimento->setpdv($venda->getcaixa());
							$movimento->sethrmovto($venda->gethora());
							$movimento->setcodunidade($arrProduto[$idxProduto]["codunidade"]);
							$movimento->setcodtpdocto($paramestoque->getcodtpdoctotrocapdv());
							if(!$movimento->save()){
								$this->con->rollback();
								return FALSE;
							}
						}
					}
				}
			}
		}

		// Busca todos os produtos dos cupons para recalcular a venda media
		$arr_codproduto = array();
		foreach($this->vendas as $i => $venda){
			foreach($venda->pdvitem as $j => $item){
				$arr_codproduto[] = $item->getcodproduto();
			}
		}
		$arr_codproduto = array_merge(array_unique($arr_codproduto));

		// Recalcula venda media
		$vendamedia = new VendaMedia($this->con);
		$vendamedia->setcodestabelec($this->estabelecimento->getcodestabelec());
		foreach($arr_codproduto as $i => $codproduto){
			setprogress($i / sizeof($arr_codproduto) * 100, "Atualizando venda media: ".($i + 1)." de ".sizeof($arr_codproduto));
			$vendamedia->setcodproduto($codproduto);
			if(!$vendamedia->atualizar()){
				$this->con->rollback();
				return FALSE;
			}
		}
		$this->con->commit();
		return TRUE;
	}

	function getpdvconfig(){
		return $this->pdvconfig;
	}

	private function gravar_cliente($cpfcnpj){
		$this->con->start_transaction();
		$cliente = objectbytable("cliente", NULL, $this->con);
		$cliente->setnome($cpfcnpj);
		$cliente->setrazaosocial($cpfcnpj);
		$cliente->setcpfcnpj($cpfcnpj);
		$cliente->setufres($this->estabelecimento->getuf());
		$cliente->setcodcidaderes($this->estabelecimento->getcodcidade());
		$cliente->setuffat($this->estabelecimento->getuf());
		$cliente->setcodcidadefat($this->estabelecimento->getcodcidade());
		$cliente->setufent($this->estabelecimento->getuf());
		$cliente->setcodcidadeent($this->estabelecimento->getcodcidade());
		$cliente->setcodstatus("1");
		if($cliente->save()){
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	private function entre_dtmovto($data){
		if(strlen($this->dtmovto_ini) == 0 && strlen($this->dtmovto_fim) == 0){
			return TRUE;
		}elseif(strlen($this->dtmovto_ini) == 0 && strlen($this->dtmovto_fim) > 0){
			$this->dtmovto_ini = $this->dtmovto_fim;
		}elseif(strlen($this->dtmovto_ini) > 0 && strlen($this->dtmovto_fim) == 0){
			$this->dtmovto_fim = $this->dtmovto_ini;
		}
		$data = value_date($data);
		return (compare_date($data, $this->dtmovto_ini, "Y-m-d", ">=") && compare_date($data, $this->dtmovto_fim, "Y-m-d", "<="));
	}

	private function explodirproduto(ItCupom $itcupom, $arrComposicao, $codprodutopai = NULL){
		if(array_key_exists($itcupom->getcodproduto(), $arrComposicao)){
			if(is_null($codprodutopai)){
				$codprodutopai = $itcupom->getcodproduto();
			}
			$arr_itcupom_comp = array();
			$n = sizeof($arrComposicao[$itcupom->getcodproduto()]["itcomposicao"]);
			foreach($arrComposicao[$itcupom->getcodproduto()]["itcomposicao"] as $rowItComposicao){
				$itcupom_comp = objectbytable("itcupom", NULL, $this->con);
				$itcupom_comp->setidcupom($itcupom->getidcupom());
				$itcupom_comp->setcodproduto($rowItComposicao["codproduto"]);
				$itcupom_comp->setcodprodutopai($codprodutopai);
				$itcupom_comp->setquantidade($itcupom->getquantidade() * $rowItComposicao["quantidade"]);
				$itcupom_comp->setcustorep(($itcupom->getcustorep() / $itcupom_comp->getquantidade()) * $itcupom->getquantidade());
				$itcupom_comp->setpreco(($itcupom->getpreco() / $itcupom_comp->getquantidade()) * $itcupom->getquantidade());
				$itcupom_comp->setdesconto($itcupom->getdesconto() / $n);
				$itcupom_comp->setacrescimo($itcupom->getacrescimo() / $n);
				$itcupom_comp->settptribicms($itcupom->gettptribicms());
				$itcupom_comp->setaliqicms($itcupom->getaliqicms());
				$itcupom_comp->setaliqpis($itcupom->getaliqpis());
				$itcupom_comp->setaliqcofins($itcupom->getaliqcofins());
				$itcupom_comp->setcomposicao("F");
				$itcupom_comp->setstatus($itcupom->getstatus());
				$arr_itcupom_comp = array_merge($arr_itcupom_comp, $this->explodirproduto($itcupom_comp, $arrComposicao, $codprodutopai));
			}
			return $arr_itcupom_comp;
		}elseif(is_null($codprodutopai)){
			return array();
		}else{
			return array($itcupom);
		}
	}

	function exportcliente($return = FALSE){
		switch($this->pdv){
			case "emporium":
				return $this->emporium->exportar_cliente($return);
			case "gdr":
				$this->gdr->exportar_cliente();
				break;
			case "gz":
				return $this->gzsistemas->exportar_cliente($return);
			case "scanntech":
				return $this->scanntech->exportar_cliente();
			case "siac":
				$this->siac->exportar_cliente();
				break;
			case "syspdv":
				return $this->syspdv->exportar_cliente($return);
			case "zanthus":
				if($this->frentecaixa->getversao() == "3"){
					return $this->zanthus->cargaCliente();
				}else{
					return $this->zanthus_exportcliente();
				}
		}
		return TRUE;
	}

	function exportfinalizadora(){
		switch($this->pdv){
			case "syspdv": $this->syspdv->exportar_finalizadora();
				break;
		}
	}

	function exportproduto($return = FALSE){
		switch($this->pdv){
			case "saurus":
				return $this->saurus->precopdv();
			case "coral":
				return $this->coral->exportar_produto($return);
			case "emporium":
				$this->emporium->exportar_produto();
				break;
			case "gdr":
				$this->gdr->exportar_produto();
				break;
			case "gz":
				return $this->gzsistemas->exportar_produto($return);
			case "scanntech":
				return $this->scanntech->exportar_produto();
			case "siac":
				return $this->siac->exportar_produto();
			case "syspdv":
				$result = $this->syspdv->exportar_produto($return);
				if($result === false){
					$_SESSION["ERROR"] = $this->syspdv->error();
					return false;
				}else{
					return $result;
				}
			case "visualmix":
				$this->visualmix->exportar_produto();
				break;
			case "zanthus":
				if($this->frentecaixa->getversao() == "3"){
				    return $this->zanthus->cargaProduto();
				}else{
					return $this->zanthus->zanthusfile_exportproduto($return);
				}

		}
		return TRUE;
	}

	function exportvendedor($return = FALSE){
		switch($this->pdv){
			case "gz":
				return $this->gzsistemas->exportar_vendedor($return);
		}
	}

	function importmaparesumo($find_file = TRUE){
		$this->con->start_transaction();
		setprogress(0, "Lendo arquivo de mapa resumo", TRUE);
		switch($this->pdv){
			case "coral":
				if(!$this->coral_importmaparesumo()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "zanthus":
				$this->zanthus->codestabelec = $this->estabelecimento->getcodestabelec();
				$this->zanthus->dtmovto_ini = $this->dtmovto_ini;
				$this->zanthus->dtmovto_fim = $this->dtmovto_fim;

				if(!$this->zanthus->importar_maparesumo()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "emporium":
				if($find_file){
					if(!$this->emporium->importar_maparesumo($find_file)){
						$this->con->rollback();
						return FALSE;
					}
				}
				break;
			case "gz":
				if(!$this->gz_importmaparesumo($find_file)){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "nfpaulista":
				$this->notafiscalpaulista->importar_venda($this->pdvconfig->getestabelecimento());
				$arr_ecf = $this->notafiscalpaulista->getarr_ecf();
				$arr_maparesumo = $this->notafiscalpaulista->getarr_maparesumo();
				foreach($arr_ecf as $ecf){
					if(!$ecf->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($arr_maparesumo as $maparesumo){
					if(!$maparesumo->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
				break;
			case "scanntech":
				if(strlen($this->dtmovto) == 0 && strlen($this->dtmovto_ini) > 0 && strlen($this->dtmovto_fim) > 0){
					$dtinicial = strtotime($this->dtmovto_ini);
					$dtfinal = strtotime($this->dtmovto_fim);
					while($dtinicial <= $dtfinal){
						$this->setdtmovto(date("Y-m-d", $dtinicial));
						if(!$this->scanntech->importar_maparesumo()){
							$this->con->rollback();
							return FALSE;
						}
						$dtinicial = strtotime("+1 day", $dtinicial);
					}
				}else{
					if(!$this->scanntech->importar_maparesumo()){
						$this->con->rollback();
						return FALSE;
					}
				}
				break;
			case "siac":
				$arquivo = $this->estabelecimento->getdirpdvimp();
				if(is_dir($arquivo)){
					$arquivo .= "ECF.ASC";
				}
				if(strlen($this->dtmovto) == 0 && strlen($this->dtmovto_ini) > 0 && strlen($this->dtmovto_fim) > 0){
					$dtinicial = strtotime($this->dtmovto_ini);
					$dtfinal = strtotime($this->dtmovto_fim);
					while($dtinicial <= $dtfinal){
						$this->setdtmovto(date("Y-m-d", $dtinicial));
						if(!$this->siac->importar_maparesumo($arquivo, $find_file)){
							$this->con->rollback();
							return FALSE;
						}
						$dtinicial = strtotime("+1 day", $dtinicial);
					}
				}else{
					if(!$this->siac->importar_maparesumo($arquivo, $find_file)){
						$this->con->rollback();
						return FALSE;
					}
				}
				break;
			case "syspdv":
				if(!$this->syspdv->importar_maparesumo($this->estabelecimento->getdirpdvimp()."syspmov.txt", $find_file)){
					$this->con->rollback();
					return FALSE;
				}
				break;
		}
		$this->con->commit();
		return TRUE;
	}

	function importvendas(){
		setprogress(0, "Lendo arquivo de vendas", TRUE);
		$this->con->start_transaction();
		switch($this->pdv){
			case "coral":
				if(!$this->coral_importvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "emporium":
				if(!$this->emporium_importvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "gdr":
				if(!$this->gdr_importvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "gz":
				if(!$this->gz_importvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "nfpaulista":
				if(!$this->notafiscalpaulista->importar_venda($this->estabelecimento)){
					$this->con->rollback();
					return FALSE;
				}
				$this->vendas = $this->notafiscalpaulista->getarr_pdvvenda();
				$this->finalizadoras = $this->notafiscalpaulista->getarr_pdvfinalizador();
				if(!$this->gravarvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "scanntech":
				if(!$this->scanntech->importar_venda()){
					$this->con->rollback();
					return FALSE;
				}
				$this->vendas = $this->scanntech->getpdvvenda();
				$this->finalizadoras = $this->scanntech->getpdvfinalizador();
				if(!$this->gravarvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "siac":
				$arquivo = $this->estabelecimento->getdirpdvimp();
				if(is_dir($arquivo)){
					$arquivo .= "MOVIMENT.ASC";
				}
				if(!$this->siac->importar_venda($arquivo)){
					$this->con->rollback();
					return FALSE;
				}
				$this->vendas = $this->siac->getpdvvenda();
				$this->finalizadoras = $this->siac->getpdvfinalizador();
				if(!$this->gravarvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "syspdv":
				if(!$this->syspdv_importvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "zanthus":
				if(!$this->zanthus_importvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
			case "saurus":
				$this->saurus->setdtmovto($this->dtmovto);
				$this->saurus->setestabelecimento($this->estabelecimento);
				if(!$this->saurus->importvendas()){
					$this->con->rollback();
					return FALSE;
				}
				break;
		}

		// Importa o mapa resumo
		if($this->pdv != "zanthus"){
			if(!$this->importmaparesumo(FALSE)){
				$this->con->rollback();
				return FALSE;
			}
		}

		// Se for PDV Coral, move os arquivos para pasta importados
		if($this->pdv == "coral"){
			$this->coral->armazenar_arquivos();
		}
		$this->con->commit();
		return TRUE;
	}

	private function log($text){
		$file = fopen(__DIR__."/../temp/interligapdv.log", "a+");
		fwrite($file, date("Y-m-d H:i:s")." ".str_pad(microtime(true), 15, "0", STR_PAD_RIGHT)." {$text}\r\n");
		fclose($file);
	}

	private function refresh_pdvconfig(){
		$this->coral->setpdvconfig($this->pdvconfig);
		$this->gdr->setpdvconfig($this->pdvconfig);
		$this->gzsistemas->setpdvconfig($this->pdvconfig);
		$this->scanntech->setpdvconfig($this->pdvconfig);
		$this->siac->setpdvconfig($this->pdvconfig);
		$this->syspdv->setpdvconfig($this->pdvconfig);
		$this->visualmix->setpdvconfig($this->pdvconfig);
		$this->emporium->setpdvconfig($this->pdvconfig);
		$this->zanthus->setpdvconfig($this->pdvconfig);
		$this->saurus->setpdvconfig($this->pdvconfig);
	}

	private function removeformat($arr){
		foreach($arr as $i => $row){
			foreach($row as $j => $val){
				$arr[$i][$j] = removespecial(utf8_decode($val));
			}
		}
		return $arr;
	}

	function setcodestabelec($value){
		$this->estabelecimento = objectbytable("estabelecimento", $value, $this->con);
		if(!$this->estabelecimento->exists()){
			$_SESSION["ERROR"] = "Estabelecimento n&atilde;o encontrado.";
			return FALSE;
		}elseif(strlen($this->estabelecimento->getcodfrentecaixa()) == 0){
			$_SESSION["ERROR"] = "Informe o Frente de Caixa no cadastro do estabelecimento.";
			return FALSE;
		}else{
			$this->frentecaixa = objectbytable("frentecaixa", $this->estabelecimento->getcodfrentecaixa(), $this->con);
			switch($this->frentecaixa->getcodfrentecaixa()){
				case 1: $this->pdv = "gz";
					break;
				case 2: $this->pdv = "coral";
					break;
				case 3: $this->pdv = "zanthus";
					break;
				case 4: $this->pdv = "syspdv";
					break;
				case 5: $this->pdv = "gdr";
					break;
				case 6: $this->pdv = "siac";
					break;
				case 7: $this->pdv = "visualmix";
					break;
				case 8: $this->pdv = "nfpaulista";
					break;
				case 9: $this->pdv = "emporium";
					break;
				case 10: $this->pdv = "scanntech";
					break;
				case 11: $this->pdv = "saurus";
					break;
				default:
					$_SESSION["ERROR"] = "Frente de Caixa informado no estabelecimento n&atilde;o &eacute; v&aacute;lido.";
					return FALSE;
			}
			$this->pdvconfig->setestabelecimento($this->estabelecimento);
			$this->pdvconfig->setfrentecaixa($this->frentecaixa);
			$this->refresh_pdvconfig();

			return TRUE;
		}
	}

	function setdatalog($datalog){
		$this->datalog = value_date($datalog);

		$this->pdvconfig->setdatalog($datalog);
		$this->refresh_pdvconfig();
	}

	function setdatalogfim($datalogfim){
		$this->datalogfim = value_date($datalogfim);

		$this->pdvconfig->setdatalogfim($datalogfim);
		$this->refresh_pdvconfig();
	}

	function setdtmovto($dtmovto){
		$this->dtmovto = value_date($dtmovto);
		$this->pdvconfig->setdtmovto($this->dtmovto);
		return TRUE;
	}

	function setdtmovto_ini($dtmovto){
		$this->dtmovto_ini = value_date($dtmovto);
		return TRUE;
	}

	function setdtmovto_fim($dtmovto){
		$this->dtmovto_fim = value_date($dtmovto);
		return TRUE;
	}

	function sethoralog($horalog){
		$this->horalog = value_time($horalog);

		$this->pdvconfig->sethoralog($horalog);
		$this->refresh_pdvconfig();
	}

	function sethoralogfim($horalogfim){
		$this->horalogfim = value_time($horalogfim);

		$this->pdvconfig->sethoralogfim($horalogfim);
		$this->refresh_pdvconfig();
	}

	function setpdvfinalizador($arr_pdvfinalizador){
		$this->finalizadoras = $arr_pdvfinalizador;
	}

	function setpdvvenda($arr_pdvvenda){
		$this->vendas = $arr_pdvvenda;
	}

	function setpdvvasilhame($arr_pdvvasilhame){
		$this->vasilhames = $arr_pdvvasilhame;
	}

	function setarr_recebepdv($arr_recebepdv){
		$this->arr_recebepdv = $arr_recebepdv;
	}

	function settipopreco($tipopreco){
		if($tipopreco != "A"){
			$tipopreco = "V";
		}
		$this->tipopreco = $tipopreco;
		$this->str_preco = sql_tipopreco($this->tipopreco);

		$this->pdvconfig->settipopreco($tipopreco);
		$this->refresh_pdvconfig();
	}

	function seturgente($urgente){
		if(is_bool($urgente)){
			$this->urgente = $urgente;
		}

		$this->pdvconfig->seturgente($this->urgente);
		$this->refresh_pdvconfig();
	}

	private function coral_importvendas(){
		if(!$this->coral->diretorio_venda($this->estabelecimento->getdirpdvimp(), TRUE)){
			return FALSE;
		}
		$this->vendas = $this->coral->getpdvvenda();
		$this->finalizadoras = $this->coral->getpdvfinalizador();
		return $this->gravarvendas();
	}

	private function coral_importmaparesumo($find_file = TRUE){
		$paramfiscal = objectbytable("paramfiscal", $this->estabelecimento->getcodestabelec(), $this->con);
		// Busca as datas que nao deverao ser importadas
		$res = $this->con->query("SELECT DISTINCT dtmovto FROM maparesumo WHERE codestabelec = ".$this->estabelecimento->getcodestabelec());
		$arr = $res->fetchAll(2);
		$arr_dtmovto = array();
		foreach($arr as $row){
			$arr_dtmovto[] = convert_date($row["dtmovto"], "Y-m-d", "d/m/Y");
		}

		// Tenta abrir o diretorio dos arquivos
		if(!($dir = @opendir($this->estabelecimento->getdirpdvimp()))){
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>".$this->estabelecimento->getdirpdvimp();
			return FALSE;
		}

		// Acha todos os arquivos gerados pelo coral
		$files = array();
		while($file = readdir($dir)){
			if(strlen($file) == 12 && substr($file, 0, 2) == "LZ"){
				$files[] = $file;
			}
		}

		if(!$find_file && sizeof($files) == 0){
			return TRUE;
		}

		$this->con->start_transaction();

		// Percorre todos os arquivos
		foreach($files as $i => $file){
			$nome_arquivo = $file;
			setprogress(($i + 1), "Processando mapa resumo: ".($i + 1)." de ".sizeof($files));
			$dtmovto = substr($file, 2, 6);
			$dtmovto = date("d/m/Y", mktime(0, 0, 0, substr($dtmovto, 2, 2), substr($dtmovto, 0, 2), substr($dtmovto, 4, 2)));
			if(in_array($dtmovto, $arr_dtmovto) || !$this->entre_dtmovto($dtmovto)){
				$data = substr($dtmovto, 6, 4)." ".substr($dtmovto, 3, 2)." ".substr($dtmovto, 0, 2);
				fclose($file);
				if(!is_dir($this->estabelecimento->getdirpdvimp()."IMPORTADO")){
					mkdir($this->estabelecimento->getdirpdvimp()."IMPORTADO");
				}
				if(!is_dir($this->estabelecimento->getdirpdvimp()."IMPORTADO/".$data)){
					mkdir($this->estabelecimento->getdirpdvimp()."IMPORTADO/".$data);
				}

				copy($this->estabelecimento->getdirpdvimp().$nome_arquivo, $this->estabelecimento->getdirpdvimp()."IMPORTADO/".$data."/".basename($nome_arquivo));
				unlink($this->estabelecimento->getdirpdvimp().$nome_arquivo);
				closedir($dir);
				continue;
			}
			$caixa = substr($file, 9, 3);
			$linhas = array();
			$file = fopen($this->estabelecimento->getdirpdvimp().$file, "r");
			while(!feof($file)){
				$linhas[] = fgets($file, 1024);
			}

			$maparesumo = objectbytable("maparesumo", NULL, $this->con);
			$maparesumo->setcodestabelec($this->estabelecimento->getcodestabelec());
			$maparesumo->setcaixa($caixa);
			$maparesumo->setnumeroecf($caixa);
			$maparesumo->setnummaparesumo(($paramfiscal->getnummaparesumo() == 0 ? 1 : $paramfiscal->getnummaparesumo()));
			$maparesumo->setdtmovto($dtmovto);

			$arr_maparesumoimposto = array();
			foreach($linhas as $linha){
				$arr = explode("=", trim($linha));
				$campo = $arr[0];
				$valor = $arr[1];
				switch($campo){
					case "GTI": $maparesumo->setgtinicial($valor / 100);
						break;
					case "GTF": $maparesumo->setgtfinal($valor / 100);
						break;
					case "NCANCITEM": $maparesumo->setitenscancelados($valor);
						break;
					case "VCANCITEM": $maparesumo->settotalitemcancelado($valor / 100);
						break;
					case "NCANCCUPOM": $maparesumo->setcuponscancelados($valor);
						break;
					case "VCANCCUPOM": $maparesumo->settotalcupomcancelado($valor / 100);
						break;
					case "NDESCONTOS": $maparesumo->setnumerodescontos($valor);
						break;
					case "VDESCONTOS": $maparesumo->settotaldescontocupom($valor / 100);
						break;
					case "VBRUTA": $maparesumo->settotalbruto($valor / 100);
						break;
					case "VLIQUIDA": $maparesumo->settotalliquido($valor / 100);
						break;
					case "REDUCOES": $maparesumo->setnumeroreducoes($valor);
						break;
					case "REINICIOS":
						$maparesumo->setreinicioini($valor - 1);
						$maparesumo->setreiniciofim($valor);
						break;
					case "NSERIE":
						$valor = strtoupper($valor);
						$ecf = objectbytable("ecf", NULL, $this->con);
						$ecf->setnumfabricacao($valor);
						$ecf->setequipamentofiscal("ECF");
						$arr_ecf = object_array($ecf);
						if(sizeof($arr_ecf) > 0){
							$ecf = array_shift($arr_ecf);
						}else{
							$ecf->setcodestabelec($this->estabelecimento->getcodestabelec());
							$ecf->setcaixa($caixa);
							if(!$ecf->save()){
								$this->con->rollback();
								return FALSE;
							}
						}
						$maparesumo->setcodecf($ecf->getcodecf());
						$maparesumo->setnumseriefabecf($ecf->getnumfabricacao());
						break;
					case "COOI": $maparesumo->setoperacaoini($valor);
						break;
					case "COOF": $maparesumo->setoperacaofim($valor);
						break;
					case "I":
					case "N":
					case "F":
					case "T0700":
					case "T1800":
					case "T1200":
					case "T2500":
					case "T1100":
					case "T0400":
						$tptribicms = substr($campo, 0, 1);
						$aliqicms = substr($campo, 1) / 100;
						$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
						$maparesumoimposto->settptribicms($tptribicms);
						$maparesumoimposto->setaliqicms($aliqicms);
						$maparesumoimposto->settotalliquido($valor / 100);
						$maparesumoimposto->settotalicms(($valor / 100) * ($aliqicms / 100));
						$arr_maparesumoimposto[] = $maparesumoimposto;
						break;
				}
			}

			// Verifica se ja existe o mapa resumo
			$count_maparesumo = $this->con->query("SELECT * FROM maparesumo WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." AND caixa = ".$caixa." AND dtmovto = '".convert_date($dtmovto, "d/m/Y", "Y-m-d")."' AND codecf = ".$ecf->getcodecf());
			if($count_maparesumo->rowCount() > 0){
				continue;
			}

			if(!$maparesumo->save()){
				$this->con->rollback();
				return FALSE;
			}
			foreach($arr_maparesumoimposto as $maparesumoimposto){
				$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
				if(!$maparesumoimposto->save()){
					$this->con->rollback();
					return FALSE;
				}
			}

			$data = substr($dtmovto, 6, 4)." ".substr($dtmovto, 3, 2)." ".substr($dtmovto, 0, 2);
			fclose($file);
			if(!is_dir($this->estabelecimento->getdirpdvimp()."IMPORTADO")){
				mkdir($this->estabelecimento->getdirpdvimp()."IMPORTADO");
			}
			if(!is_dir($this->estabelecimento->getdirpdvimp()."IMPORTADO/".$data)){
				mkdir($this->estabelecimento->getdirpdvimp()."IMPORTADO/".$data);
			}

			copy($this->estabelecimento->getdirpdvimp().$nome_arquivo, $this->estabelecimento->getdirpdvimp()."IMPORTADO/".$data."/".basename($nome_arquivo));
			unlink($this->estabelecimento->getdirpdvimp().$nome_arquivo);
			closedir($dir);
		}

		$paramfiscal->setnummaparesumo($paramfiscal->getnummaparesumo() + 1);
		if(!$paramfiscal->save()){
			$this->con->rollback();
			return FALSE;
		}
		$this->con->commit();
		return TRUE;
	}

	private function finalizadora_parceiro(&$tipoparceiro, &$codparceiro, $arr_finalizadora, $arr_cliente, $finalizador, $paramestoque){
		$ok = FALSE;

		if(strlen($finalizador->getbin()) > 0 && strlen($finalizador->gettipotransacao()) > 0 && sizeof($this->arr_bin) > 0){
			foreach($this->arr_bin AS $bin){
				if($finalizador->getbin() == $bin["bin"] && $finalizador->gettipotransacao() == $bin["tipotransacao"]){
					if(strlen($bin["codadminist"]) > 0){
						$tipoparceiro = "A";
						$codparceiro = $bin["codadminist"];
						$ok = TRUE;
						break;
					}
				}
			}
			if(!$ok){
				echo messagebox("error", "", "BIN ".$finalizador->getbin()." tipo ".$finalizador->gettipotransacao()." n&atilde;o se encontra cadastrado.");
				die();
			}
		}else{
			$codparceiro = NULL;
			$finalizadora = $arr_finalizadora[$finalizador->getcodfinaliz()];
			if(strlen($finalizadora["tipoparceiro"]) > 0 && strlen($finalizadora["codparceiro"]) > 0){
				$tipoparceiro = $finalizadora["tipoparceiro"];
				$codparceiro = $finalizadora["codparceiro"];
			}elseif(array_key_exists($finalizador->getcodcliente(), $arr_cliente)){
				$tipoparceiro = "C";
				$codparceiro = $finalizador->getcodcliente();
			}elseif(strlen($finalizador->getcpfcliente()) > 0 && in_array($finalizador->getcpfcliente(), $arr_cliente)){
				$tipoparceiro = "C";
				$codparceiro = array_search($finalizador->getcpfcliente(), $arr_cliente);
			}
			if(is_null($codparceiro)){
				$tipoparceiro = "C";
				$codparceiro = $paramestoque->getcodclientevendapdv();
			}
		}
	}

	private function emporium_importvendas(){
		if(!$this->emporium->importar_venda($this->estabelecimento->getdirpdvimp(), TRUE)){
			return FALSE;
		}
		$this->vendas = $this->emporium->getpdvvenda();
		$this->finalizadoras = $this->emporium->getpdvfinalizador();
		return $this->gravarvendas();
	}

	private function gdr_importvendas(){
		if(!$this->gdr->diretorio_venda($this->estabelecimento->getdirpdvimp(), TRUE)){
			return FALSE;
		}
		$this->vendas = $this->gdr->getpdvvenda();
		$this->finalizadoras = $this->gdr->getpdvfinalizador();
		return $this->gravarvendas();
	}

	private function gz_importmaparesumo($find_file = TRUE){
		$linhas = array();
		$file_name = $this->estabelecimento->getdirpdvimp()."MAPAECF.TXT";
		if(!file_exists($file_name)){
			$file_name = $this->estabelecimento->getdirpdvimp()."mapaecf.txt";
			if(!file_exists($file_name)){
				if($find_file){
					$_SESSION["ERROR"] = "Arquivo do mapa resumo do ECF n&atilde;o foi encontrado.";
					return FALSE;
				}else{
					return TRUE;
				}
			}
		}

		$res = $this->con->query("SELECT DISTINCT dtmovto FROM maparesumo WHERE codestabelec = ".$this->estabelecimento->getcodestabelec());
		$arr = $res->fetchAll(2);
		$arr_dtmovto = array();
		foreach($arr as $row){
			$arr_dtmovto[] = $row["dtmovto"];
		}

		$paramfiscal = objectbytable("paramfiscal", $this->estabelecimento->getcodestabelec(), $this->con);

		$arr_ecf = array();
		$ecf = objectbytable("ecf", NULL, $this->con);
		$arr_ecf = object_array($ecf);

		$linhas = array();
		$file = fopen($file_name, "r");
		while(!feof($file)){
			$linhas[] = fgets($file, 1320);
		}
		fclose($file);

		$this->con->start_transaction();
		foreach($linhas as $i => $linha){
			setprogress(($i + 1) / sizeof($linhas) * 100, "Importando mapa resumo: ".($i + 1)." de ".sizeof($linhas));
			if(strlen(trim($linha)) == 0){
				continue;
			}
			$dtmovto = substr($linha, 0, 8);
			$codestabelec = substr($linha, 8, 3);
			$numeroecf = substr($linha, 11, 3);
			$numeroreducoes = substr($linha, 14, 6);
			$operacaoini = substr($linha, 20, 6);
			$operacaofim = substr($linha, 26, 6);
			$operacaonaofiscini = substr($linha, 32, 6);
			$operacaonaofiscfim = substr($linha, 38, 6);
			$reinicioini = substr($linha, 44, 6);
			$reiniciofim = substr($linha, 50, 6);
			$cuponsnaofiscemit = substr($linha, 56, 6);
			$cuponsfiscemit = substr($linha, 63, 6);
			$itenscancelados = substr($linha, 68, 6);
			$cuponscancelados = substr($linha, 74, 6);
			$cuponsnfisccanc = substr($linha, 80, 6);
			$numerodescontos = substr($linha, 86, 6);
			$numeroacrescimos = substr($linha, 92, 6);
			$gtinicial = substr($linha, 98, 18) / 100;
			$gtfinal = substr($linha, 116, 18) / 100;
			$totalbruto = substr($linha, 134, 18) / 100;
			$totalcupomcancelado = substr($linha, 152, 18) / 100;
			$totalitemcancelado = substr($linha, 170, 18) / 100;
			$totaldescontoitem = substr($linha, 188, 18) / 100;
			$totaldescontocupom = substr($linha, 206, 18) / 100;
			$totalacrescimocupom = substr($linha, 224, 18) / 100;
			$totalliquido = substr($linha, 242, 18) / 100;
			$caixa = substr($linha, 1260, 3);
			$numseriefabecf = substr($linha, 1263, 20);

			$dtmovto = substr($dtmovto, 6, 2)."/".substr($dtmovto, 4, 2)."/".substr($dtmovto, 0, 4);
			$dtmovto_comp = convert_date($dtmovto, "d/m/Y", "Y-m-d");

			$numseriefabecf = trim($numseriefabecf);

			$impostos = array();
			for($j = 0; $j < 20; $j++){
				$ini = 260 + 50 * $j;
				$impostos[] = array(
					"descricao" => substr($linha, $ini, 10),
					"aliqicms" => substr($linha, ($ini + 10), 4) / 100,
					"totalliquido" => substr($linha, ($ini + 14), 18) / 100,
					"totalicms" => substr($linha, ($ini + 32), 18) / 100
				);
			}
			foreach($impostos as $j => $imposto){
				if($imposto["totalliquido"] == 0){
					unset($impostos[$j]);
				}
			}

			if(in_array($dtmovto_comp, $arr_dtmovto) || $codestabelec != $this->estabelecimento->getcodestabelec() || !$this->entre_dtmovto($dtmovto_comp)){
				continue;
			}

			$found_ecf = FALSE;
			foreach($arr_ecf as $ecf){
				if($ecf->getnumfabricacao() == $numseriefabecf){
					$caixa = $ecf->getcaixa();
					$found_ecf = TRUE;
					break;
				}
			}
			if(!$found_ecf){
				$ecf = objectbytable("ecf", NULL, $this->con);
				$ecf->setcodestabelec($codestabelec);
				$ecf->setnumfabricacao($numseriefabecf);
				$ecf->setcaixa($caixa);
				if(!$ecf->save()){
					$this->con->rollback();
					return FALSE;
				}
				$arr_ecf[] = $ecf;
			}

			$maparesumo = objectbytable("maparesumo", NULL, $this->con);
			$maparesumo->setcodestabelec($this->estabelecimento->getcodestabelec());
			$maparesumo->setcaixa($caixa);
			$maparesumo->setnummaparesumo(($paramfiscal->getnummaparesumo() == 0 ? 1 : $paramfiscal->getnummaparesumo()));
			$maparesumo->setdtmovto($dtmovto);
			$maparesumo->setcodecf($ecf->getcodecf());
			$maparesumo->setnumeroecf($numeroecf);
			$maparesumo->setnumeroreducoes($numeroreducoes);
			$maparesumo->setoperacaoini($operacaoini);
			$maparesumo->setoperacaofim($operacaofim);
			$maparesumo->setoperacaonaofiscini($operacaonaofiscini);
			$maparesumo->setoperacaonaofiscfim($operacaonaofiscfim);
			$maparesumo->setreinicioini($reinicioini);
			$maparesumo->setreiniciofim($reiniciofim);
			$maparesumo->setcuponsnaofiscemit($cuponsnaofiscemit);
			$maparesumo->setcuponsfiscemit($cuponsfiscemit);
			$maparesumo->setitenscancelados($itenscancelados);
			$maparesumo->setcuponscancelados($cuponscancelados);
			$maparesumo->setcuponsnfisccanc($cuponsnfisccanc);
			$maparesumo->setnumerodescontos($numerodescontos);
			$maparesumo->setnumeroacrescimos($numeroacrescimos);
			$maparesumo->setgtinicial($gtinicial);
			$maparesumo->setgtfinal($gtfinal);
			$maparesumo->settotalbruto($totalbruto);
			$maparesumo->settotalcupomcancelado($totalcupomcancelado);
			$maparesumo->settotalitemcancelado($totalitemcancelado);
			$maparesumo->settotaldescontocupom($totaldescontocupom);
			$maparesumo->settotaldescontoitem($totaldescontoitem);
			$maparesumo->settotalacrescimocupom($totalacrescimocupom);
			$maparesumo->settotalliquido($totalliquido);
			$maparesumo->setnumseriefabecf($numseriefabecf);

			// Verifica se ja existe o mapa resumo
			$count_maparesumo = $this->con->query("SELECT * FROM maparesumo WHERE codestabelec = ".$this->estabelecimento->getcodestabelec()." AND caixa = ".$caixa." AND dtmovto = '".convert_date($dtmovto, "d/m/Y", "Y-m-d")."' AND codecf = ".$ecf->getcodecf());
			if($count_maparesumo->rowCount() > 0){
				continue;
			}

			if(!$maparesumo->save()){
				$this->con->rollback();
				return FALSE;
			}
			foreach($impostos as $imposto){
				$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
				$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
				$maparesumoimposto->settptribicms(substr($imposto["descricao"], 0, 1));
				$maparesumoimposto->setaliqicms($imposto["aliqicms"]);
				$maparesumoimposto->settotalliquido($imposto["totalliquido"]);
				$maparesumoimposto->settotalicms($imposto["totalicms"]);
				if(!$maparesumoimposto->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}
		$paramfiscal->setnummaparesumo($paramfiscal->getnummaparesumo() + 1);
		if(!$paramfiscal->save()){
			$this->con->rollback();
			return FALSE;
		}
		$this->con->commit();
		return TRUE;
	}

	private function gz_importvendas(){
		$file_name = $this->estabelecimento->getdirpdvimp()."MOVOUTRA.TXT";
		$this->gzsistemas->arquivo_venda($file_name);
		$this->vendas = $this->gzsistemas->getpdvvenda();
		$this->finalizadoras = $this->gzsistemas->getpdvfinalizador();
		$this->arr_recebepdv = $this->gzsistemas->getpdvrecebepdv();
		return $this->gravarvendas();
	}

	private function zanthus_importvendas(){
		if($this->frentecaixa->getversao() == "3"){
			$this->zanthus->leituraVendas($this->dtmovto,"00:00:00",true);
		}else{
			$this->zanthus->importvendas($this->estabelecimento->getdirpdvimp());
		}

		$this->vendas = $this->zanthus->getpdvvenda();
		$this->finalizadoras = $this->zanthus->getpdvfinalizador();
		return $this->gravarvendas();
	}

	private function syspdv_importvendas(){
		$file_name = $this->estabelecimento->getdirpdvimp()."syspmov.txt";
		$this->syspdv->arquivo_venda($file_name);
		$this->vendas = $this->syspdv->getpdvvenda();
		$this->finalizadoras = $this->syspdv->getpdvfinalizador();
		$this->vasilhames = $this->syspdv->getpdvvasilhame();
		$this->arr_recebepdv = $this->syspdv->getpdvrecebepdv();
		return $this->gravarvendas();
	}
}
