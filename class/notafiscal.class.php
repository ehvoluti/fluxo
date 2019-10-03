<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");
require_file("class/itemcalculo.class.php");

class NotaFiscal extends Cadastro{

	public $itnotafiscal = array();
	public $notafiscalreferenciada = array();
	public $flag_itnotafiscal = FALSE;
	private $itemcalculo;
	private $tributacaoproduto;

	function __construct($idnotafiscal = NULL){
		parent::__construct();
		$this->newrelation("notafiscal", "codestabelec", "estabelecimento", "codestabelec");
		$this->newrelation("notafiscal", array("codparceiro", "tipoparceiro"), "cliente", array("codcliente", "'C'"));
		$this->newrelation("notafiscal", array("codparceiro", "tipoparceiro"), "fornecedor", array("codfornec", "'F'"));
		$this->newrelation("notafiscal", array("codparceiro", "tipoparceiro"), "estabelecimento", array("codestabelec", "'E'"), "LEFT", array(), "estabelecimento2");
		$this->table = "notafiscal";
		$this->primarykey = array("idnotafiscal");
		$this->setidnotafiscal($idnotafiscal);
		if($this->getidnotafiscal() != NULL){
			$this->searchbyobject();
		}
	}

	function calcular_chavenfe($cNFAuxiliar = NULL){
		$arr_ini = array(1,2,3,4,5,6);
		if($this->getgerafiscal() == "S"){
			$operacaonota = objectbytable("operacaonota", $this->getoperacao(), $this->con);
			if($operacaonota->gettipo() == "E" && !in_array($operacaonota->getoperacao(), array("DC", "PR", "IM","EC","EF"))){
				switch($operacaonota->getparceiro()){
					case "C":
					case "E":
						$emitente = objectbytable("estabelecimento", $this->getcodparceiro(), $this->con);
						break;
					case "F":
						$emitente = objectbytable("fornecedor", $this->getcodparceiro(), $this->con);
						break;
				}
			}else{
				$emitente = objectbytable("estabelecimento", $this->getcodestabelec(), $this->con);
			}

			$estado = objectbytable("estado", $emitente->getuf(), $this->con);

			$chavenfe = str_pad(substr($estado->getcodoficial(), -2), 2, "0", STR_PAD_LEFT); // Codigo oficial do estado do emitente
			$chavenfe .= convert_date($this->getdtemissao(), "Y-m-d", "ym"); // Ano e mes da data de emissao (formato: AAMM)
			$chavenfe .= str_pad(substr(removeformat($emitente->getcpfcnpj()), 0, 14), 14, "0", STR_PAD_LEFT); // CNPJ do emitente
			$chavenfe .= "55"; // Modelo da nota fiscal
			$chavenfe .= str_pad(substr($this->getserie(), 0, 3), 3, "0", STR_PAD_LEFT); // Serie da nota fiscal
			$chavenfe .= str_pad($this->getnumnotafis(), 9, "0", STR_PAD_LEFT); // Numero da nota fiscal
			$chavenfe .= $this->gettipoemissao(); // Forma de emissao da nota fiscal
			//$chavenfe .= str_pad($cNFAuxiliar, 8, "0", STR_PAD_LEFT); // Codigo numerico (enviar numero da nota fiscal)
			//$chavenfe .= str_pad($this->getnumnotafis(), 8, "0", STR_PAD_LEFT); // Codigo numerico (enviar numero da nota fiscal)
			if(strlen(trim($this->getnumerodocumento())) == 0){
				$cNFAuxiliar = md5($this->getnumnotafis());
				$cNFAuxiliar = sprintf("%.0f", hexdec($cNFAuxiliar));
				$posIni = $arr_ini[$this->weekOfMonth($this->getdtemissao()) - 1];
				$cNFAuxiliar = str_pad(substr($cNFAuxiliar, $posIni * 5, 8) , 8, "0", STR_PAD_LEFT);
			}else{
				$cNFAuxiliar = $this->getnumerodocumento();
			}
			$chavenfe .= $cNFAuxiliar;
			$chavenfe_rev = strrev($chavenfe);
			$ponderacao = 0;
			$peso = 2;
			for($i = 0; $i < strlen($chavenfe_rev); $i++){
				$ponderacao += substr($chavenfe_rev, $i, 1) * $peso;
				if(++$peso > 9){
					$peso = 2;
				}
			}
			$digito = 11 - ($ponderacao % 11);
			if($digito > 9){
				$digito = 0;
			}
			$chavenfe .= $digito;
		}else{
			$chavenfe = NULL;
		}

		$this->setchavenfe($chavenfe);
		$this->setnumerodocumento($cNFAuxiliar);
		return $chavenfe;
	}

	function calcular_totais(){
		if(in_array($this->getoperacao(), array("AE", "AS"))){
			return true;
		}

		if(!is_array($this->itnotafiscal) || sizeof($this->itnotafiscal) == 0){
			$itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
			$itnotafiscal->setidnotafiscal($this->getidnotafiscal());
			$this->itnotafiscal = object_array($itnotafiscal);
		}

		$operacaonota = objectbytable("operacaonota", $this->getoperacao(), $this->con);

		$arr_campo = array(
			"totaldesconto", "totalfrete", "totalipi", "totalbaseicms", "totalicms", "totalbaseicmssubst", "totalicmssubst",
			"totalbruto", "totalliquido", "numeroitens", "totalarecolher", "totalbasepis", "totalbasecofins", "totalpis",
			"totalcofins", "totalacrescimo", "totalbonificado", "totalbaseii", "totalii", "totaliof", "totalseguro",
			"totaldespaduaneira", "totalsiscomex", "totalcustotab", "dtdigitacao", "totalvalorafrmm", "totalicmsufdest",
			"totalfcpufdest", "totalicmsufremet", "valortotalinss", "valortotalir", "valortotalcsll", "totaldesoneracao",
			"totalbcfcpufdest",	"totalbcfcpufdestst", "totalfcpufdestst", "basecalcufdest", "basecalculofcpst", "valorfcpst"
		);
		foreach($arr_campo as $campo){
			$$campo = 0;
		}

		// Carrega os produtos
		$arr_codproduto = array();
		foreach($this->itnotafiscal as $itnotafiscal){
			$arr_codproduto[] = $itnotafiscal->getcodproduto();
			$arr_iditnotafiscal[$itnotafiscal->getiditnotafiscal()] = $itnotafiscal;
		}
		$arr_produto = object_array_key(objectbytable("produto", NULL, $this->con), $arr_codproduto);

		foreach($this->itnotafiscal as $itnotafiscal){
			$produto = $arr_produto[$itnotafiscal->getcodproduto()];
			if($itnotafiscal->getcomposicao() != "F"){
				$numeroitens++;
			}

			if($itnotafiscal->getcomposicao() == "P"){
				$composicao = objectbytable("composicao",null, $this->con);
				$composicao->setcodproduto($itnotafiscal->getcodproduto());
				$composicao->searchbyobject();
				if(($operacaonota->gettipo() == "S" && $operacaonota->getoperacao() != "DF") && $composicao->getexplosaoauto() == "S"){
					continue;
				}
			}elseif($itnotafiscal->getcomposicao() == "F"){
				$pai_itnotafiscal = $arr_iditnotafiscal[$itnotafiscal->getiditnotafiscalpai()];

				$composicao = objectbytable("composicao",null, $this->con);
				$composicao->setcodproduto($pai_itnotafiscal->getcodproduto());
				$composicao->searchbyobject();

				if($composicao->getexplosaoauto() == "N" && $operacaonota->gettipo() == "S"){
					continue;
				}

				if(($operacaonota->gettipo() == "E" || $operacaonota->getoperacao() == "DF" && $composicao->getexplosaoauto() == "N")){
					continue;
				}
			}

			$totaldesconto += $itnotafiscal->gettotaldesconto();
			$totalacrescimo += $itnotafiscal->gettotalacrescimo();
			$totalseguro += $itnotafiscal->gettotalseguro();
			$totalfrete += $itnotafiscal->gettotalfrete();
			$totalipi += $itnotafiscal->gettotalipi();
			$totalbruto += $itnotafiscal->gettotalbruto();
			$totalarecolher += $itnotafiscal->gettotalarecolher();
			$totalbaseicms += $itnotafiscal->gettotalbaseicms();
			$totalicms += $itnotafiscal->gettotalicms();
			$totalbasepis += $itnotafiscal->gettotalbasepis();
			$totalbasecofins += $itnotafiscal->gettotalbasecofins();
			$totalpis += $itnotafiscal->gettotalpis();
			$totalcofins += $itnotafiscal->gettotalcofins();
			$totalbaseicmssubst += $itnotafiscal->gettotalbaseicmssubst();
			$totalicmssubst += $itnotafiscal->gettotalicmssubst();
			$totalbaseii += $itnotafiscal->gettotalbaseii();
			$totalii += $itnotafiscal->gettotalii();
			$totaliof += $itnotafiscal->getvaliof();
			$totalsiscomex += $itnotafiscal->getvalsiscomex();
			$totalvalorafrmm += $itnotafiscal->getvalorafrmm();
			$totaldespaduaneira += $itnotafiscal->getdespaduaneira();
			$totalicmsufdest += $itnotafiscal->getvaloricmsufdest();
			$basecalcufdest += $itnotafiscal->getbasecalcufdest();
			$totalfcpufdest += $itnotafiscal->getvalorfcpufdest();
			$totalbcfcpufdest += $itnotafiscal->getvalorbcfcpufdest();
			$totalicmsufremet += $itnotafiscal->getvaloricmsufremet();
			$valortotalinss += $itnotafiscal->getvalorinss();
			$valortotalir += $itnotafiscal->getvalorir();
			$valortotalcsll += $itnotafiscal->getvalorcsll();
			$totaldesoneracao += $itnotafiscal->getvalordesoneracao();
			$basecalculofcpst += $itnotafiscal->getbasecalculofcpst();
			$valorfcpst += $itnotafiscal->getvalorfcpst();
			if($itnotafiscal->getbonificado() == "S"){
				$totalbonificado += $itnotafiscal->gettotalliquido() - $itnotafiscal->gettotalipi();
			}
			$totalliquido += $itnotafiscal->gettotalliquido();

			$dtdigitacao = date("d/m/Y");
		}

		foreach($arr_campo as $campo){
			if(method_exists($this, "set{$campo}")){
				call_user_func(array($this, "set{$campo}"), $$campo);
			}
		}
	}

	private function explodir_composicao($itnotafiscal, $itnotafiscalpai = null){
		// Verifica se o pai foi preenchido, e caso nao tenha, preenche com o proprio item
		if(is_null($itnotafiscalpai)){
			$itnotafiscalpai = $itnotafiscal;
		}

		// Define o que cada tipo de operacao vai utilizar como de tipo de composicao
		switch($this->getoperacao()){
			case "CP": $tipos = ["A", "C"]; break;
			case "DC": $tipos = ["A", "V"]; break;
			case "DF": $tipos = ["A", "C"]; break;
			case "PR": $tipos = ["A", "C"]; break;
			case "TE": $tipos = ["A", "C", "T"]; break;
			case "TS": $tipos = ["A", "V"]; break;
			case "VD": $tipos = ["A", "V"]; break;
			default:
				if($itnotafiscal->getcodproduto() !== $itnotafiscalpai->getcodproduto()){
					$itnotafiscalpai->arr_itnotafiscal[] = $itnotafiscal;
				}
				return true;
		}

		// Prepara os tipos selecionados para ser utilizado dentro da Query
		foreach($tipos as $i => $tipo){
			$tipos[$i] = "'{$tipo}'";
		}
		$tipos = implode(", ", $tipos);

		// Procura por composicoes do produto com o tipo especificado
		$res = $this->con->query("SELECT * FROM composicao WHERE codproduto = {$itnotafiscal->getcodproduto()} AND tipo IN ({$tipos})");
		$arr = $res->fetchAll();
		if(count($arr) > 0){
			$composicao = array_shift($arr);

			// Informa que o produto eh pai de composicao
			if(strlen($itnotafiscal->getcomposicao()) === 0){
				$itnotafiscal->setcomposicao("P");
			}

			// Carrega os filhos da composicao
			$itcomposicao = objectbytable("itcomposicao", null, $this->con);
			$itcomposicao->setcodcomposicao($composicao["codcomposicao"]);
			$arr_itcomposicao = object_array($itcomposicao);

			// Insere os itens da composicao recalculados para a nota fiscal
			$this->explodir_composicao_itcomposicao($itnotafiscal, $itnotafiscalpai, $composicao, $arr_itcomposicao);
		}else{
			// Se o produto nao eh pai de nenhuma composicao, ele entra como produto normal na nota fiscal
			if(strlen($itnotafiscal->getcomposicao()) === 0){
				$itnotafiscal->setcomposicao("N");
			}
			if($itnotafiscal->getcodproduto() !== $itnotafiscalpai->getcodproduto()){
				$itnotafiscalpai->arr_itnotafiscal[] = $itnotafiscal;
			}
		}
	}

	private function explodir_composicao_itcomposicao($itnotafiscal, $itnotafiscalpai, $composicao, $arr_itcomposicao){
		// Verifica se existe pelo menos um filho na composicao
		if(count($arr_itcomposicao) === 0){
			return true;
		}

		// Calcula os fatores a serem aplicados nos filhos
		$fator_ipi = $itnotafiscal->getvalipi() / ($itnotafiscal->getpreco());
		$fator_descto = $itnotafiscal->getvaldescto() / ($itnotafiscal->getpreco());
		$fator_acresc = $itnotafiscal->getvalacresc() / ($itnotafiscal->getpreco());
		$fator_frete = $itnotafiscal->getvalfrete() / ($itnotafiscal->getpreco());
		$fator_seguro = $itnotafiscal->getvalseguro() / ($itnotafiscal->getpreco());
		$fator_iof = $itnotafiscal->getvaliof() / ($itnotafiscal->getpreco());
		$fator_baseufdest = $itnotafiscal->getbasecalcufdest() / ($itnotafiscal->getpreco());
		$fator_valorfcpufdest = $itnotafiscal->getvalorfcpufdest() / ($itnotafiscal->getpreco());
		$fator_valoricmsufremet = $itnotafiscal->getvaloricmsufremet() / ($itnotafiscal->getpreco());
		$fator_despaduaneira = $itnotafiscal->getdespaduaneira() / ($itnotafiscal->getpreco() * $itnotafiscal->getquantidade());
		$fator_desctodi = $itnotafiscal->getvaldesctodi() / ($itnotafiscal->getpreco() * $itnotafiscal->getquantidade());
		$fator_siscomex = $itnotafiscal->getvalsiscomex() / ($itnotafiscal->getpreco() * $itnotafiscal->getquantidade());
		$fator_valorafrmm = $itnotafiscal->getvalorafrmm() / ($itnotafiscal->getpreco() * $itnotafiscal->getquantidade());

		if($this->getoperacao() == "VD"){
			// Soma o total de preco dos filhos
			$precovrj_filhos = 0;
			foreach($arr_itcomposicao as $itcomposicao){
				$produtoestab = objectbytable("produtoestab", array($itnotafiscal->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con);
				$precovrj_filhos += $produtoestab->getprecovrj() * $itcomposicao->getquantidade();
			}
			// Verifica se os filhos nao estao com os precos todos zerados
			// E calcula o fator para aplica em cada filho
			if($precovrj_filhos == 0){
				$precovrj_fator = 1 / count($arr_itcomposicao);
			}else{
				$precovrj_fator = $itnotafiscal->getpreco() / $precovrj_filhos; // * $itcomposicao->getquantidade();
			}
		}

		foreach($arr_itcomposicao as $itcomposicao){
			$produtoestab = objectbytable("produtoestab", array($itnotafiscal->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con);
			$itnotafiscal_f = objectbytable("itnotafiscal", NULL, $this->con);
			$itnotafiscal_f->setcodproduto($itcomposicao->getcodproduto());
			$itnotafiscal_f->setidnotafiscal($itnotafiscal->getidnotafiscal());
			$itnotafiscal_f->setcodestabelec($itnotafiscal->getcodestabelec());
			$itnotafiscal_f->setdtentrega($itnotafiscal->getdtentrega());
			$itnotafiscal_f->setoperacao($itnotafiscal->getoperacao());
			$itnotafiscal_f->setnumpedido($itnotafiscal->getnumpedido());
			$itnotafiscal_f->setquantidade($itnotafiscal->getquantidade() * $itcomposicao->getquantidade());
			$itnotafiscal_f->setqtdeunidade($itnotafiscal->getqtdeunidade());
			$itnotafiscal_f->setbonificado($itnotafiscal->getbonificado());
			$itnotafiscal_f->setnatoperacao($itnotafiscal->getnatoperacao());

			// Verifica o tipo de operacao da nota
			switch($this->getoperacao()){
				case "CP": // Compra
				case "TS": // Transferencia (saida)
				case "TE": // Transferencia (entrada)
					// Verifica o tipo de atualizacao de custo na composicao
					switch($composicao["tipocusto"]){
						case "D": // Dividido automatico
							$totalparticipacao = 0;
							foreach($arr_itcomposicao as $_itcomposicao){
								$totalparticipacao += $_itcomposicao->getquantidade();
							}
							$itnotafiscal_f->setpreco($itnotafiscal->getpreco() / $totalparticipacao);
							$itnotafiscal_f->setprecopolitica($itnotafiscal->getpreco() / $totalparticipacao);
							break;
						case "N": // Nao atualizar
							$itnotafiscal_f->setpreco($produtoestab->getcustorep());
							$itnotafiscal_f->setprecopolitica($produtoestab->getcustorep());
							break;
						case "P": // Por participacao
							$preco_f = ($itnotafiscal->getpreco() / $itcomposicao->getquantidade()) * $itcomposicao->getpartcusto();
							if(sizeof($arr_itcomposicao) == 1){
								$itnotafiscal_f->setpreco($preco_f);
								$itnotafiscal_f->setprecopolitica($preco_f);
							}else{
								$itnotafiscal_f->setpreco($itnotafiscal->getpreco() * $itcomposicao->getpartcusto());
								$itnotafiscal_f->setprecopolitica($itnotafiscal->getpreco() * $itcomposicao->getpartcusto());
							}
							break;
						case "S": // Custo completo do pai
							$itnotafiscal_f->setpreco($itnotafiscal->getpreco());
							$itnotafiscal_f->setprecopolitica($itnotafiscal->getprecopolitica());
							break;
					}
					break;
				case "VD": // Venda
					if($precovrj_filhos > 0){
						$produtoestab = objectbytable("produtoestab", array($itnotafiscal->getcodestabelec(), $itcomposicao->getcodproduto()), $this->con);
						$preco_f = $produtoestab->getprecovrj() * $precovrj_fator;
					}else{
						$preco_f = $itnotafiscalpai->getpreco() * $precovrj_fator;
					}
					$itnotafiscal_f->setpreco($preco_f);
					$itnotafiscal_f->setprecopolitica($preco_f);
					break;
				default: // Outras operacoes
					$preco_f = $itnotafiscal->getpreco() / $itcomposicao->getquantidade();
					$itnotafiscal_f->setpreco($preco_f / $itcomposicao->getquantidade());
					$itnotafiscal_f->setprecopolitica($preco_f / $itcomposicao->getquantidade());
					break;
			}
			if(!isset($preco_f)){
				$preco_f = $itnotafiscal_f->getpreco();
			}

			$itnotafiscal_f->settipoipi($itnotafiscal->gettipoipi());
			$itnotafiscal_f->setvalipi($preco_f * $fator_ipi);
			$itnotafiscal_f->setpercipi($itnotafiscal->getpercipi());
			$itnotafiscal_f->setvaldescto($preco_f * $fator_descto);
			$itnotafiscal_f->setpercdescto($itnotafiscal->getpercdescto());
			$itnotafiscal_f->setvalacresc($preco_f * $fator_acresc);
			$itnotafiscal_f->setpercacresc($itnotafiscal->getpercacresc());
			$itnotafiscal_f->setvalfrete($preco_f * $fator_frete);
			$itnotafiscal_f->setpercfrete($itnotafiscal->getpercfrete());
			$itnotafiscal_f->setvalseguro($preco_f * $fator_seguro);
			$itnotafiscal_f->setpercseguro($itnotafiscal->getpercseguro());
			$itnotafiscal_f->setcodunidade($itcomposicao->getcodunidade());
			$itnotafiscal_f->setnatoperacao($itnotafiscal->getnatoperacao());
			$itnotafiscal_f->setcomposicao("F");
			$itnotafiscal_f->setiditnotafiscal($itnotafiscal->getiditnotafiscal());
			$itnotafiscal_f->setvaliof($preco_f * $fator_iof);
			$itnotafiscal_f->setdespaduaneira($preco_f * $fator_despaduaneira);
			$itnotafiscal_f->setvaldesctodi($preco_f * $fator_desctodi);
			$itnotafiscal_f->setvalsiscomex($preco_f * $fator_siscomex);
			$itnotafiscal_f->setvalorafrmm($preco_f * $fator_valorafrmm);
			$itnotafiscal_f->setbasecalcufdest($preco_f * $fator_baseufdest);
			$itnotafiscal_f->setaliqfcpufdest($itnotafiscal->getaliqfcpufdest());
			$itnotafiscal_f->setvalorfcpufdest($preco_f * $fator_valorfcpufdest);

			$itnotafiscal_f->setvalorfcpst($itnotafiscal->getvalorfcpst());
			$itnotafiscal_f->setbasecalculofcpst($itnotafiscal->getbasecalculofcpst());
			$itnotafiscal_f->setpercfcpst($itnotafiscal->getpercfcpst());

			$itnotafiscal_f->setaliqicmsufdest($itnotafiscal->getaliqicmsufdest());
			$itnotafiscal_f->setvaloricmsufdest($preco_f * $fator_valoricmsufremet);
			$itnotafiscal_f->setaliqicmsinter($itnotafiscal->getaliqicmsinter());
			$itnotafiscal_f->setvaloricmsufremet($preco_f * $fator_baseufdest);
			$itnotafiscal_f->setaliqicminterpart($itnotafiscal->getaliqicminterpart());

			$natoperacao = objectbytable("natoperacao", $itnotafiscal_f->getnatoperacao(), $this->con);

			$produto = objectbytable("produto", $itcomposicao->getcodproduto(), $this->con);
			if($composicao["tipocusto"] != "P"){
				if(strlen($natoperacao->getcodcf()) > 0){
					$classfiscal = objectbytable("classfiscal", $natoperacao->getcodcf(), $this->con);
				}else{
					$classfiscal = objectbytable("classfiscal", ($this->getoperacao() == "CP" ? $produto->getcodcfnfe() : $produto->getcodcfnfs()), $this->con);
				}
				$itnotafiscal_f->settptribicms($classfiscal->gettptribicms());
				$itnotafiscal_f->setaliqicms($classfiscal->getaliqicms());
				$itnotafiscal_f->setredicms($classfiscal->getaliqredicms());
				$itnotafiscal_f->setvalorpauta($classfiscal->getvalorpauta());
				$itnotafiscal_f->setaliqiva($classfiscal->getaliqiva());
				$itnotafiscal_f->setaliqii($classfiscal->getaliqii());
			}else{
				$itnotafiscal_f->settptribicms($itnotafiscal->gettptribicms());
				$itnotafiscal_f->setaliqicms($itnotafiscal->getaliqicms());
				$itnotafiscal_f->setredicms($itnotafiscal->getredicms());
				$itnotafiscal_f->setvalorpauta(($itnotafiscal->getvalorpauta() / $itcomposicao->getquantidade()) * $itcomposicao->getpartcusto());
				$itnotafiscal_f->setaliqiva($itnotafiscal->getaliqiva());
				$itnotafiscal_f->setaliqii($itnotafiscal->getaliqii());
			}

			if($itnotafiscal->gettptribicms() == "F" || $composicao["explosaoauto"] == "N"){
				$itnotafiscal_f->settptribicms($itnotafiscal->gettptribicms());
				$itnotafiscal_f->setaliqicms($itnotafiscal->getaliqicms());
				$itnotafiscal_f->setredicms($itnotafiscal->getredicms());
				$itnotafiscal_f->setvalorpauta($itnotafiscal->getvalorpauta() / $itcomposicao->getquantidade());
				$itnotafiscal_f->setaliqiva($itnotafiscal->getaliqiva());
				$itnotafiscal_f->setaliqii($itnotafiscal->getaliqii());
			}

			// Verifica se eh para recalcular a tributacao
			if(substr($this->getnatoperacao(),0,1) == "6" && $this->getoperacao() == "VD"){
				$this->tributacaoproduto->setproduto($produto);
				$this->tributacaoproduto->buscar_dados();

				$itnotafiscal_f->settptribicms($this->tributacaoproduto->gettptribicms());
				$itnotafiscal_f->setaliqicms($this->tributacaoproduto->getaliqicms());
				$itnotafiscal_f->setvalorpauta($this->tributacaoproduto->getvalorpauta() / $itcomposicao->getquantidade());
				$itnotafiscal_f->setaliqiva($this->tributacaoproduto->getaliqiva());
			}
			$this->itemcalculo->setnatoperacao($natoperacao);
			$this->itemcalculo->setclassfiscalnfe(NULL);
			$this->itemcalculo->setclassfiscalnfs(NULL);
			$this->itemcalculo->setitem($itnotafiscal_f);
			$this->itemcalculo->calcular();

			if(count($arr_itcomposicao) === 1 && $composicao["tipocusto"] === "D" && in_array($this->getoperacao(),array("CP","PR"))){
				$arr_total = array("totalbruto", "totalbaseicms", "totalicms", "totalbaseicmssubst", "totalicmssubst", "totalliquido", "basefcpufdestst", "valorfcpst");
				foreach($arr_total as $total){
					call_user_func(array($itnotafiscal_f, "set{$total}"), call_user_func(array($itnotafiscal, "get{$total}")));
				}
			}

			$this->totalbaseicms += $itnotafiscal_f->gettotalbaseicms();
			$this->totalbaseicmssubst += $itnotafiscal_f->gettotalbaseicmssubst();

			$this->explodir_composicao($itnotafiscal_f, $itnotafiscalpai);
		}
	}

	function flag_itnotafiscal($value){
		if(is_bool($value)){
			$this->flag_itnotafiscal = $value;
		}
	}

	function delete(){
		$this->connect();

		$param_notafiscal_ativatransenauto = param("NOTAFISCAL", "ATIVATRANSENTAUTO");

		// Verifica se e uma tranferencia e se pode apagar mesmo
		if($this->getoperacao() == "TS" && strlen($this->getidnotafiscal()) > 0){
			$pedido = objectbytable("pedido", NULL, $this->con);
			$pedido->setidnotafiscalpai($this->getidnotafiscal());
			$pedido->searchbyobject();
			if($pedido->exists()){
				$notafiscal = objectbytable("notafiscal", NULL, $this->con);
				$notafiscal->setcodestabelec($pedido->getcodestabelec());
				$notafiscal->setnumpedido($pedido->getnumpedido());
				$notafiscal->setoperacao("TE");
				$arr_notafiscal = object_array($notafiscal);
				$notafiscal = reset($arr_notafiscal);
				if(sizeof($arr_notafiscal) > 0 && $param_notafiscal_ativatransenauto == "N" && $pedido->getstatus() == "A"){
					$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel exclu&iacute;r uma nota fiscal de transfer&ecirc;ncia de sa&iacute;da sem antes exclu&iacute;r a transfer&ecirc;ncia de entrada.<br><a onclick=\"$.messageBox('close'); openProgram('CadNotaFiscalTE','idnotafiscal=".$notafiscal->getidnotafiscal()."')\">Clique aqui</a> para abrir a nota fiscal de entrada.";
					return FALSE;
				}else{
					if($param_notafiscal_ativatransenauto == "S"){
						$notafiscal->delete();
						//$pedido->delete();
					}
				}
			}
		}
		$this->con->start_transaction();
		// Apaga os itens antes do pedido
		/* if($this->flag_itnotafiscal){
		  $itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
		  $itnotafiscal->setidnotafiscal($this->getidnotafiscal());
		  $this->itnotafiscal = object_array($itnotafiscal);
		  foreach($this->itnotafiscal as $itnotafiscal){
		  if(!$itnotafiscal->delete()){
		  $this->con->rollback();
		  return FALSE;
		  }
		  }
		  } */

		//limpa o id da nota fiscal emitida na tabela de nota fiscal referenciada
		//ficando apenas a referencia para o pedido
		if(is_array($this->notafiscalreferenciada)){
			foreach($this->notafiscalreferenciada as $notafiscalref){
				$notafiscalref->setidnotafiscal(null);
				if(!$notafiscalref->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}

		if(parent::delete()){
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function save($object = null){
		$this->connect();

		// Verifica se a data de entrega e maior que a data de emissao
		if(compare_date($this->getdtemissao(), $this->getdtentrega(), "Y-m-d", ">")){
			$_SESSION["ERROR"] = "Data de emiss&atilde;o n&atilde;o pode ser maior que a data de entrega.";
			return FALSE;
		}

		// Verifica se os parametros da nota fiscal estao informados
		$estabelecimentofiscal = objectbytable("estabelecimento", $this->getcodestabelec(), $this->con);

		$paramnotafiscal = objectbytable("paramnotafiscal", array($estabelecimentofiscal->getcodestabelecfiscal(), $this->getoperacao()), $this->con, true);

		if(!$paramnotafiscal->exists()){
			$_SESSION["ERROR"] = "Par&acirc;metros da nota fiscal n&atilde;o foram informados.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal')\">Clique aqui</a> para abrir as parametriza&ccedil;&otilde;es de notas fiscais.";
			return FALSE;
		}

		// Verifica se o tipo de documento para as movimentacoes de estoque foi preenchida nos parametros de nota fiscal
		if(strlen($paramnotafiscal->getcodtpdocto()) == 0){
			$_SESSION["ERROR"] = "Tipo de documento para movimento de estoque n&atilde;o foi informado nos par&acirc;metros da nota fiscal.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal','codestabelec=".$this->getcodestabelec()."&operacao=".$this->getoperacao()."')\">Clique aqui</a> para abrir as parametriza&ccedil;&otilde;es de notas fiscais.";
			return FALSE;
		}

		// Verifica parametros de comissao
		$paramcomissao = objectbytable("paramcomissao", $this->getcodestabelec(), $this->con);
		if(!$paramcomissao->exists()){
			$_SESSION["ERROR"] = "Par&acirc;metros de comiss&atilde;o ainda n&atilde;o foram informados para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamComissao','codestabelec=".$this->getcodestabelec()."')\">Clique aqui</a> para abrir os par&acirc;metros de comiss&atilde;o.";
			return FALSE;
		}

		// Verifica se os parametros fiscais foram informados
		$paramfiscal = objectbytable("paramfiscal", $estabelecimentofiscal->getcodestabelecfiscal(), $this->con);
		if(!$paramfiscal->exists()){
			$_SESSION["ERROR"] = "Par&acirc;metros fiscais n&atilde;o informados.<br><a onclick=\"openProgram('CadParamFiscal','codestabelec=".$this->getcodestabelec()."')\">Clique aqui</a> para abrir o par&acirc;metros fiscais.";
			return FALSE;
		}

		$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);

		// Se for uma tranferencia (saida), verifica se a natureza de operacao contra-partida esta informada
		if($this->getoperacao() == "TS"){
			if(strlen($natoperacao->getnatoperacaocp()) == 0){
				$_SESSION["ERROR"] = "Natureza de opera&ccedil;&atilde;o contra-partida n&atilde;o foi informada.<br><a onclick=\"openProgram('NatOper','natoperacao=".$this->getnatoperacao()."')\">Clique aqui</a> para abrir o cadastro da natureza de opera&ccedil;&atilde;o.";
				return FALSE;
			}
		}

		// Verifica a categoria de lancamento na natureza de operacao
		if(strlen($natoperacao->getcodcatlancto()) == 0){
			$_SESSION["ERROR"] = "Categoria de lan&ccedil;amento n&atilde;o foi informado na natureza de opera&ccedil;&atilde;o.<br><a onclick=\"openProgram('NatOper','natoperacao=".$this->getnatoperacao()."')\">Clique aqui</a> para abrir o cadastro da natureza de opera&ccedil;&atilde;o.";
			return FALSE;
		}

		// Verifica a sub-categoria de lancamento na natureza de operacao
		if(strlen($natoperacao->getcodsubcatlancto()) == 0){
			$_SESSION["ERROR"] = "Subcategoria de lan&ccedil;amento n&atilde;o foi informado na natureza de opera&ccedil;&atilde;o.<br><a onclick=\"openProgram('NatOper','natoperacao=".$this->getnatoperacao()."')\">Clique aqui</a> para abrir o cadastro da natureza de opera&ccedil;&atilde;o.";
			return FALSE;
		}

		// Busca os campos necessarios da natureza de operacao para preencher os campos da nota
		$colunas_geracao = array("geraestoque", "gerafinanceiro", "geraliquidado", "gerafiscal", "geraicms", "geraipi", "gerapiscofins");
		foreach($colunas_geracao as $coluna){
			if(strlen(call_user_func(array($this, "get".$coluna))) == 0){
				call_user_func(array($this, "set".$coluna), call_user_func(array($natoperacao, "get".$coluna)));
			}
		}

		// Verifica se a chave nfe eh valida
		if(in_array($this->getoperacao(), array("CP"))){
			if(strlen(trim(($this->getchavenfe()))) > 0 && !valid_chave_nfe($this->getchavenfe())){
				$_SESSION["ERROR"] = "Chave NFE {$this->getchavenfe()} incorreta favor colocar uma chave valida.";
				return FALSE;
			}
		}

		$this->con->start_transaction();

//		if($this->getoperacao() == "TS" && strlen($this->getidnotafiscal()) > 0 && $this->getstatus() == "C"){
//			$param_notafiscal_ativatransenauto = param("NOTAFISCAL", "ATIVATRANSENTAUTO", $this->con);
//			$pedido = objectbytable("pedido", NULL, $this->con);
//			$pedido->setidnotafiscalpai($this->getidnotafiscal());
//			$pedido->searchbyobject();
//			if($pedido->exists()){
//				$notafiscal = objectbytable("notafiscal", NULL, $this->con);
//				$notafiscal->setcodestabelec($pedido->getcodestabelec());
//				$notafiscal->setnumpedido($pedido->getnumpedido());
//				$notafiscal->setoperacao("TE");
//				$arr_notafiscal = object_array($notafiscal);
//				$notafiscal = reset($arr_notafiscal);
//				if(sizeof($arr_notafiscal) > 0 && $param_notafiscal_ativatransenauto == "N" && $pedido->getstatus() == "A"){
//					$_SESSION["ERROR"] = "N&atilde;o &eacute; poss&iacute;vel exclu&iacute;r uma nota fiscal de transfer&ecirc;ncia de sa&iacute;da sem antes exclu&iacute;r a transfer&ecirc;ncia de entrada.<br><a onclick=\"$.messageBox('close'); openProgram('CadNotaFiscalTE','idnotafiscal=".$notafiscal->getidnotafiscal()."')\">Clique aqui</a> para abrir a nota fiscal de entrada.";
//					return FALSE;
//				}else{
//					if($param_notafiscal_ativatransenauto == "S"){
//						$notafiscal->setstatus("C");
//						$notafiscal->save();
//					}
//				}
//			}
//		}
		// Verifica se os itens e a nota tem o mesmo primeiro digito do CFOP
		if($this->flag_itnotafiscal){
			$itens_cfop = array();
			foreach($this->itnotafiscal as $itnotafiscal){
				$primdigito = substr($itnotafiscal->getnatoperacao(), 0, 1);
				$itens_cfop[] = $primdigito;
			}

			$itens_cfop[] = substr($this->getnatoperacao(), 0, 1);
			$qtdecfop = array_unique($itens_cfop);
			if(count($qtdecfop) > 1){
				$_SESSION["ERROR"] = "Natureza de opera&ccedil;&atilde;o dos itensn&atilde; confere com a natureza de opera&ccedil;&atilde;o da nota fiscal.";
				return FALSE;
			}
		}
		// Verifica se deve atualizar as tributacoes dos produtos
		if($this->flag_itnotafiscal && $this->getoperacao() == "CP" && false){
			$fornecedor = objectbytable("fornecedor", $this->getcodparceiro(), $this->con);
			if($fornecedor->getatualizatributacao() == "S"){
				// Percore os itens da nota e atualiza tributacoes dos produtos
				foreach($this->itnotafiscal as $itnotafiscal){
					$estabelecimento = objectbytable("estabelecimento", $itnotafiscal->getcodestabelec(), $this->con);
					$estadotributo = objectbytable("estadotributo", array($estabelecimento->getuf(), $estabelecimento->getregimetributario(), $itnotafiscal->getcodproduto()), $this->con);
					$produto = objectbytable("produto", $itnotafiscal->getcodproduto(), $this->con);
					if($estadotributo->getcodcfnfe() != NULL){ // Verifica se o produto esta sendo tratado na tabela estadotributo
						$classfiscal = objectbytable("classfiscal", $estadotributo->getcodcfnfe(), $this->con);
					}else{ // Caso a tabela estadotributo nao esteja usada usa a tabela produto
						$classfiscal = objectbytable("classfiscal", $produto->getcodcfnfe(), $this->con);
					}
					if($itnotafiscal->gettptribicms() != $classfiscal->gettptribicms() || $itnotafiscal->getaliqicms() != $classfiscal->getaliqicms() || $itnotafiscal->getredicms() != $classfiscal->getaliqredicms()){ // Verifica se a classificacao fiscal atual e correta senao pesquisa  por outra
						$classfiscal_p = objectbytable("classfiscal", NULL, $this->con);
						$classfiscal_p->settptribicms($itnotafiscal->gettptribicms());
						$classfiscal_p->setaliqicms($itnotafiscal->getaliqicms());
						$classfiscal_p->setaliqredicms($itnotafiscal->getredicms());
						$classfiscal_p->setcodcst($itnotafiscal->getcsticms());
						$arr_classfiscal = object_array($classfiscal_p);

						// Se a pesquisa retornar algo ele altera o codigo de classificacao fiscal do item
						if(sizeof($arr_classfiscal) > 0){
							if($estadotributo->getcodcfnfe() != NULL){
								foreach($arr_classfiscal as $codclassfiscal){
									$estadotributo->setcodcfnfe($codclassfiscal->getcodcf()); // Atualiza o codigo de classificacao fiscal em estadotributo
								}
								$estadotributo->save();
							}else{
								if($produto->getatualizancm == "S"){ // Caso o produto esteja puxando a tributacao do NCM essa verificacao retira a ligacao
									$produto->setatualizancm("N");
								}
								foreach($arr_classfiscal as $codclassfiscal){
									$produto->setcodcfnfe($codclassfiscal->getcodcf()); // Atualiza o codigo de classificacao fiscal em produto
								}
								$produto->setaliqiva($itnotafiscal->getaliqiva()); // Atualiza a aliquota de IVA no produto
								if(!$produto->save()){
									$this->con->rollback();
									return FALSE;
								}
							}
						}else{ // Caso a pesquisa nao retorne nada e criado uma nova classificacao fiscal
							$classfiscal_n = objectbytable("classfiscal", NULL, $this->con);
							$classfiscal_n->settptribicms($itnotafiscal->gettptribicms());
							$classfiscal_n->setaliqicms($itnotafiscal->getaliqicms());
							$classfiscal_n->setaliqredicms($itnotafiscal->getredicms());
							$produto->setaliqiva($itnotafiscal->getaliqiva());
							$classfiscal_n->setvalorpauta($itnotafiscal->getvalorpauta());
							$classfiscal_n->setcodcst($classfiscal->getcodcst());
							if(!$classfiscal_n->save()){
								$this->con->rollback();
								return FALSE;
							}
							if($estadotributo->getcodcfnfe() != NULL){
								$estadotributo->setcodcfnfe($classfiscal_n->getcodcf()); // Atualiza o codigo de classificacao fiscal em estadotributo
								if(!$estadotributo->save()){
									$this->con->rollback();
									return FALSE;
								}
							}else{
								if($produto->getatualizancm == "S"){ // Caso o produto esteja puxando a tributacao do NCM essa verificacao retira a ligacao
									$produto->setatualizancm("N");
								}
								$produto->setcodcfnfe($classfiscal_n->getcodcf()); // Atualiza o codigo de classificacao fiscal em produto
								if(!$produto->save()){
									$this->con->rollback();
									return FALSE;
								}
							}
						}
					}
				}
			}
		}

		// Indentifica se e emissao propria ou nao
		if(in_array($this->getoperacao(), array("TE", "CP"))){
			$this->setemissaopropria("N");
		}elseif(strlen($this->getemissaopropria()) == 0){
			if((strlen($this->getidnotafiscal()) == 0 && strlen($this->getnumnotafis()) > 0) && !in_array($this->getoperacao(), array("NC"))){
				$this->setemissaopropria("N");
			}else{
				$this->setemissaopropria("S");
			}
		}

		if(($this->getemissaopropria() == "N" && !in_array($this->getoperacao(), array("TE"))) || ($this->getemissaopropria() == "S" && in_array($this->getoperacao(), array("NC")))){
			$this->setstatus("A");
		}

		// Verifica se precisa buscar o proximo numero e a serie dos parametros fiscais
		if(!in_array($this->getoperacao(), array("CP", "SE"))){
			if(strlen($this->getnumnotafis()) == 0){
				// Verifica se CFOP esta para gerar fiscal, se sim pega o sequencial da nf fiscal caso contrario pega o sequencial nao fiscal
				if($natoperacao->getgerafiscal() == "S"){
					if(!in_array($this->getoperacao(), array("SS", "SE"))){
						$this->setnumnotafis($paramfiscal->getnumnotafis());
						$paramfiscal->setnumnotafis($paramfiscal->getnumnotafis() + 1);
					}else{
						$this->setnumnotafis($paramfiscal->getnumnotafiss());
						$paramfiscal->setnumnotafiss($paramfiscal->getnumnotafiss() + 1);
					}
				}else{
					$this->setnumnotafis($paramfiscal->getnumnotanfis());
					$this->setserie($paramfiscal->getserienfis());
					$paramfiscal->setnumnotanfis($paramfiscal->getnumnotanfis() + 1);
				}
				if(!$paramfiscal->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
			if(strlen($this->getserie()) == 0){
				if(!in_array($this->getoperacao(), array("SS", "SE"))){
					$this->setserie($paramfiscal->getserie());
				}else{
					$this->setserie($paramfiscal->getseriefiss());
				}
			}
		}

		// Verifica se deve preencher a data de rastreamento
		if(strlen($this->getidnotafiscal()) > 0){
			$notafiscal_old = objectbytable("notafiscal", $this->getidnotafiscal(), $this->con, true);
			if(strlen($notafiscal_old->getcodrastreamento()) == 0 && strlen($this->getcodrastreamento()) > 0){
				$this->setdtrastreamento(date("d/m/Y"));
			}
		}elseif(strlen($this->getcodrastreamento()) > 0){
			$this->setdtrastreamento(date("d/m/Y"));
		}

		$existe = $this->exists();

		$estabelecimento = objectbytable("estabelecimento", $this->getcodestabelec(), $this->con);
		if(in_array($this->getoperacao(), array("SS", "SE"))){
			if($this->getoperacao() == "SE" || ($this->getoperacao() == "SS" && strlen(trim($estabelecimento->getusuarionfse())) == 0)){
				$this->sethandlenfse("000000");
				$this->setlotenfse("000000");
				$this->setcodigostatus("100");
				$this->setstatus("A");
				$this->setxmotivo("NFS-e Autorizada com sucesso");
			}
		}

		if($this->gethrdigitacao() == null){
			$this->sethrdigitacao(date("H:i:s"));
		}
		// Grava a nota fiscal
		if(!parent::save($notafiscal_old)){
			$this->con->rollback();
			return FALSE;
		}

		// Carrega algumas tabelas relacionadas
		//$estabelecimento = objectbytable("estabelecimento", $this->getcodestabelec(), $this->con);
		$operacaonota = objectbytable("operacaonota", $this->getoperacao(), $this->con);

		// Carrega o parceiro da nota fiscal
		switch($operacaonota->getparceiro()){
			case "C":
				$parceiro = objectbytable("cliente", $this->getcodparceiro(), $this->con);
				$parceiro_uf = $parceiro->getufres();
				break;
			case "E":
				$parceiro = objectbytable("estabelecimento", $this->getcodparceiro(), $this->con);
				$parceiro_uf = $parceiro->getuf();
				break;
			case "F":
				$parceiro = objectbytable("fornecedor", $this->getcodparceiro(), $this->con);
				$parceiro_uf = $parceiro->getuf();
				break;
		}

		// Se for uma compra com distribuicao com transferencia, cria as transferencias de saida
		if($this->getoperacao() == "CP" && param("NOTAFISCAL", "HABCOMPRADISTRIB", $this->con) == "2"){
			if($this->flag_itnotafiscal){
				$arr_transferencia = array();
				foreach($this->itnotafiscal as $itnotafiscal){
					$pedidodistrib = objectbytable("pedidodistrib", NULL, $this->con);
					$pedidodistrib->setiditpedidocp($itnotafiscal->getiditpedido());
					$arr_pedidodistrib = object_array($pedidodistrib);
					foreach($arr_pedidodistrib as $pedidodistrib){
						if($pedidodistrib->getcodestabelec() == $this->getcodestabelec()){
							continue;
						}
						$arr_transferencia[$pedidodistrib->getcodestabelec()][] = array(
							"codproduto" => $itnotafiscal->getcodproduto(),
							"quantidade" => $pedidodistrib->getquantidade(),
							"qtdeunidade" => $itnotafiscal->getqtdeunidade(),
							"codunidade" => $itnotafiscal->getcodunidade(),
							"preco" => ($itnotafiscal->gettotalliquido() / $itnotafiscal->getquantidade()),
							"pedidodistrib" => $pedidodistrib,
							"itpedido" => NULL
						);
					}
				}
				foreach($arr_transferencia as $codestabelec => $transferencia){
					$operacaonota_distrib = objectbytable("operacaonota", "TS", $this->con);
					$paramnotafiscal_distrib = objectbytable("paramnotafiscal", array($estabelecimentofiscal->getcodestabelec(), "TS"), $this->con);
					$estabelecimento_distrib = objectbytable("estabelecimento", $codestabelec, $this->con);
					if($estabelecimento->getuf() == $estabelecimento_distrib->getuf()){
						$natoperacao_distrib = objectbytable("natoperacao", $paramnotafiscal_distrib->getnatoperacaopjin(), $this->con);
					}else{
						$natoperacao_distrib = objectbytable("natoperacao", $paramnotafiscal_distrib->getnatoperacaopjex(), $this->con);
					}

					if(strlen($paramnotafiscal_distrib->getnatoperacaopjin()) === 0){
						$_SESSION["ERROR"] = "Informe uma Natureza de Opera&ccedil;&atilde;o padr&atilde;o para pessoa jur&iacute;dica em opera&ccedil;&otilde;es internas para sa&iacute;da de transferencia.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal','codestabelec=".$this->getcodestabelec()."&operacao=TS')\">Clique aqui</a> para abrir os Par&acirc;metros de Nota Fiscal.";
						$this->con->rollback();
						return FALSE;
					}
					if(strlen($paramnotafiscal_distrib->getnatoperacaopjex()) === 0){
						$_SESSION["ERROR"] = "Informe uma Natureza de Opera&ccedil;&atilde;o padr&atilde;o para pessoa jur&iacute;dica em opera&ccedil;&otilde;es externas para sa&iacute;da de transferencia.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal','codestabelec=".$this->getcodestabelec()."&operacao=TS')\">Clique aqui</a> para abrir os Par&acirc;metros de Nota Fiscal.";
						$this->con->rollback();
						return FALSE;
					}
					if(strlen($paramnotafiscal_distrib->getcodcondpagtoauto()) === 0){
						$_SESSION["ERROR"] = "Informe uma Condi&ccedil;&atilde;o de Pagamento padr&atilde;o para sa&iacute;da de transferencia.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal','codestabelec=".$this->getcodestabelec()."&operacao=TS')\">Clique aqui</a> para abrir os Par&acirc;metros de Nota Fiscal.";
						$this->con->rollback();
						return FALSE;
					}
					if(strlen($paramnotafiscal_distrib->getcodespecieauto()) === 0){
						$_SESSION["ERROR"] = "Informe uma Forma de Pagamento padr&atilde;o para sa&iacute;da de transferencia.<br><a onclick=\"$.messageBox('close'); openProgram('CadParamNotaFiscal','codestabelec=".$this->getcodestabelec()."&operacao=TS')\">Clique aqui</a> para abrir os Par&acirc;metros de Nota Fiscal.";
						$this->con->rollback();
						return FALSE;
					}

					$pedido = objectbytable("pedido", NULL, $this->con);
					$pedido->setidnotafiscalpai($this->getidnotafiscal());
					$pedido->setcodestabelec($this->getcodestabelec());
					$pedido->setoperacao("TS");
					$pedido->setdtemissao($this->getdtemissao());
					$pedido->setdtentrega($this->getdtentrega());
					$pedido->settipoparceiro("E");
					$pedido->setcodparceiro($codestabelec);
					$pedido->setnatoperacao($natoperacao_distrib->getnatoperacao());
					$pedido->setstatus("P");
					$pedido->setcodcondpagto($paramnotafiscal_distrib->getcodcondpagtoauto());
					$pedido->setcodespecie($paramnotafiscal_distrib->getcodespecieauto());
					$pedido->setbonificacao("N");
					$pedido->flag_itpedido(TRUE);

					$tributacaoproduto = new TributacaoProduto($this->con);
					$tributacaoproduto->setestabelecimento($estabelecimento);
					$tributacaoproduto->setnatoperacao($natoperacao_distrib);
					$tributacaoproduto->setoperacaonota($operacaonota_distrib);
					$tributacaoproduto->setparceiro($estabelecimento_distrib);

					$itemcalculo = new ItemCalculo($this->con);
					$itemcalculo->setestabelecimento($estabelecimento);
					$itemcalculo->setoperacaonota($operacaonota_distrib);
					$itemcalculo->setparceiro($estabelecimento_distrib);

					$arr_refitpedido = array();

					foreach($transferencia as $i => $dados){
						$produto = objectbytable("produto", $dados["codproduto"], $this->con);
						$piscofins = objectbytable("piscofins", $produto->getcodpiscofinssai(), $this->con);
						$classfiscalnfe = objectbytable("classfiscal", $produto->getcodcfnfe(), $this->con);
						$classfiscalnfs = objectbytable("classfiscal", $produto->getcodcfnfs(), $this->con);

						$tributacaoproduto->setproduto($produto);
						$tributacaoproduto->buscar_dados();

						$natoperacao_it = $tributacaoproduto->getnatoperacao();
						$classfiscal = $tributacaoproduto->getclassfiscal();
						$tptribicms = $tributacaoproduto->gettptribicms();
						$aliqicms = $tributacaoproduto->getaliqicms();
						$redicms = $tributacaoproduto->getredicms();
						$aliqiva = $tributacaoproduto->getaliqiva();
						$valorpauta = $tributacaoproduto->getvalorpauta();
						$tipoipi = $tributacaoproduto->gettipoipi();
						$valipi = $tributacaoproduto->getvalipi();
						$percipi = $tributacaoproduto->getpercipi();

						$itpedido = objectbytable("itpedido", NULL, $this->con);
						$itpedido->setseqitem($i + 1);
						$itpedido->setcodestabelec($codestabelec);
						$itpedido->setnatoperacao($natoperacao_it->getnatoperacao());
						$itpedido->setcodproduto($produto->getcodproduto());
						$itpedido->setcodunidade($dados["codunidade"]);
						$itpedido->setqtdeunidade($dados["qtdeunidade"]);
						$itpedido->setquantidade($dados["quantidade"]);
						$itpedido->setpreco($dados["preco"]);
						$itpedido->settipoipi($tipoipi);
						$itpedido->setvalipi($valipi);
						$itpedido->setpercipi($percipi);
						$itpedido->settptribicms($tptribicms);
						$itpedido->setredicms($redicms);
						$itpedido->setaliqicms($aliqicms);
						$itpedido->setaliqiva($aliqiva);
						$itpedido->setvalorpauta($valorpauta);
						$itpedido->setaliqpis($piscofins->getaliqpis());
						$itpedido->setaliqcofins($piscofins->getaliqcofins());
						$itpedido->setpercdescto(0);
						$itpedido->setbonificado("N");
						$itpedido->setdtentrega($this->getdtentrega());

						$itemcalculo->setitem($itpedido);
						$itemcalculo->setclassfiscalnfe($classfiscalnfe);
						$itemcalculo->setclassfiscalnfs($classfiscalnfs);
						$itemcalculo->setnatoperacao($natoperacao_it);
						$itemcalculo->calcular();

						$transferencia[$i]["itpedido"] = $itpedido;

						$pedido->itpedido[] = $itpedido;
					}
					$pedido->calcular_totais();

					if(!$pedido->save()){
						$this->con->rollback();
						return FALSE;
					}

					foreach($transferencia as $dados){
						$pedidodistrib = $dados["pedidodistrib"];
						$pedidodistrib->setiditpedidots($dados["itpedido"]->getiditpedido());
						if(!$pedidodistrib->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
			}
		}

		// Se for uma tranferencia (saida), cria o pedido de tranferencia (entrada)
		if($this->getoperacao() == "TS"){
			// Verifica se esta trabalhando com os itens, senao ele vai zerar o pedido
			if($this->flag_itnotafiscal){
				if($this->getstatus() == "C"){
					$pedido = objectbytable("pedido", NULL, $this->con);
					$pedido->setidnotafiscalpai($this->getidnotafiscal());
					$pedido->searchbyobject();
					$pedido->setstatus("C");
					if($pedido->exists()){
						if(!$pedido->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}else{
					// Carrega a natureza de operacao da item de nota fiscal (TS)
					$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
					if(strlen($natoperacao->getnatoperacaocp()) == 0){
						$_SESSION["ERROR"] = "Natureza contra-partida para a natureza de opera&ccedil;&atilde;o <b>".$natoperacao->getnatoperacao()."</b> n&atilde;o foi informada.<br><a onclick=\"openProgram('NatOper','natoperacao=".$natoperacao->getnatoperacao()."')\">Clique aqui</a> para abrir o cadastro de natureza de opera&ccedil;&atilde;o.";
						return FALSE;
					}

					$pedido = objectbytable("pedido", NULL, $this->con);
					$pedido->setidnotafiscalpai($this->getidnotafiscal());
					$pedido->searchbyobject();
					if($pedido->exists()){
						if(!$pedido->delete()){
							$this->con->rollback();
							return FALSE;
						}
					}
					$arr_campo = array("codcondpagto", "dtentrega", "codtransp", "bonificacao", "codespecie", "totaldesconto", "totalacrescimo", "totalfrete", "totalipi", "totalbaseicms", "totalicms", "totalbaseicmssubst", "totalicmssubst", "totalbruto", "totalliquido", "numeroitens", "totalarecolher", "totalbonificado","totalpis","totalcofins");
					$pedido = objectbytable("pedido", NULL, $this->con);
					$pedido->setidnotafiscalpai($this->getidnotafiscal());
					$pedido->setcodestabelec($this->getcodparceiro());
					$pedido->setoperacao("TE");
					$pedido->setdtemissao($this->getdtemissao());
					$pedido->setdtentrega($this->getdtentrega());
					$pedido->settipoparceiro("E");
					$pedido->setemissaopropria("N");
					$pedido->setcodparceiro($this->getcodestabelec());
					$pedido->setnatoperacao($natoperacao->getnatoperacaocp());
					$pedido->setstatus("P");
					$pedido->setidnotafiscalref($this->getidnotafiscalref());
					foreach($arr_campo as $campo){
						call_user_func(array($pedido, "set".$campo), call_user_func(array($this, "get".$campo)));
					}

					if(!$pedido->save()){
						$this->con->rollback();
						return FALSE;
					}
					$tmp_pedido = clone $pedido;
				}
			}
		}

		// Verifica se esta trabalhando com os itens
		if($this->flag_itnotafiscal && $this->getstatus() != "C"){

			// Cria o objeto item calculo para calcular os totais do itens filhos de composicao
			$this->itemcalculo = new ItemCalculo($this->con);
			$this->itemcalculo->setestabelecimento($estabelecimento);
			$this->itemcalculo->setmodfrete($this->getmodfrete());
			$this->itemcalculo->setnatoperacao($natoperacao);
			$this->itemcalculo->setoperacaonota($operacaonota);
			$this->itemcalculo->setparceiro($parceiro);

			$this->tributacaoproduto = new TributacaoProduto($this->con);
			$this->tributacaoproduto->setestabelecimento($estabelecimento);
			$this->tributacaoproduto->setnatoperacao($natoperacao);
			$this->tributacaoproduto->setoperacaonota($operacaonota);
			$this->tributacaoproduto->setparceiro($parceiro);

			// Apaga todos os itens para criar de novo
			$itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
			$itnotafiscal->setidnotafiscal($this->getidnotafiscal());
			$arr_itnotafiscal = object_array($itnotafiscal);
			foreach($arr_itnotafiscal as $itnotafiscal){
				if(!$itnotafiscal->delete()){
					$this->con->rollback();
					return FALSE;
				}
			}
			// Cria composicao dos itens
			$arr_itnotafiscal = $this->itnotafiscal;
			foreach($arr_itnotafiscal as $itnotafiscal){
				$it_natoperacao = objectbytable("natoperacao", $itnotafiscal->getnatoperacao(), $this->con);
				if($it_natoperacao->getexplodetransf() == "N" && in_array($itnotafiscal->getoperacao(), array("TS", "TE"))){
					continue;
				}
				$this->explodir_composicao($itnotafiscal);
			}

			// Faz o rateio dos itens para nao apresentar diferenca entre o produto pai e filhos da composicao
			if($this->getoperacao() == "VD"){
				foreach($this->itnotafiscal as $itnotafiscalpai){
					if(is_array($itnotafiscalpai->arr_itnotafiscal) && count($itnotafiscalpai->arr_itnotafiscal) > 0){
						$totalbruto = 0;
						foreach($itnotafiscalpai->arr_itnotafiscal as $itnotafiscalfilho){
							$totalbruto += $itnotafiscalfilho->gettotalbruto();
						}
						$diferenca = round(($itnotafiscalpai->gettotalbruto() - $totalbruto), 2);
						if(abs($diferenca) > 0){
							$itnotafiscalfilho->setpreco($itnotafiscalfilho->getpreco() + ($diferenca / $itnotafiscalfilho->getquantidade()));
							$itnotafiscalfilho->setprecopolitica($itnotafiscalfilho->getprecopolitica());
							$this->itemcalculo->setitem($itnotafiscalfilho);
							$this->itemcalculo->calcular();
						}
					}
				}
			}

			// Cria os itens
			foreach($this->itnotafiscal as $itnotafiscal){
				$itnotafiscal->setconnection($this->con);
				$itnotafiscal->setnotafiscal($this);
				$itnotafiscal->setoperacaonota($operacaonota);
				$itnotafiscal->setestabelecimento($estabelecimento);
				$itnotafiscal->setidnotafiscal($this->getidnotafiscal());
				$itnotafiscal->setcodestabelec($this->getcodestabelec());
				$itnotafiscal->setdtentrega($this->getdtentrega());
				$itnotafiscal->setoperacao($this->getoperacao());
				$itnotafiscal->setnumpedido($this->getnumpedido());
				if($this->getoperacao() == "TE" && param("NOTAFISCAL", "ATIVATRANSENTAUTO", $this->con) == "N"){
					$res = $this->con->query("SELECT codproduto FROM composicao WHERE codproduto = ".$itnotafiscal->getcodproduto()." ");
					$arr = $res->fetchAll();
					if(count($arr) > 0){
			//			Ja é feito esse tratamento proximo a linha 207
			//			Esta sendo comentado por causa da OS 4969
			//			$itnotafiscal->setcomposicao("P");
					}
				}
				// comentado pois não pegou a natureza do item do pedido
//				$itnotafiscal->setnatoperacao($this->getnatoperacao());
				if(!$itnotafiscal->save()){
					$this->con->rollback();
					return FALSE;
				}
			}

			// Limpa a lista de itens para fazer com que sejam carregados novamente quando for calcular os totais
			$this->itnotafiscal = array();

			// Recalcula o total da nota
			$this->calcular_totais();

			// Grava a nota fiscal
			if(!parent::save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		if(is_array($this->notafiscalreferenciada) && !in_array($this->getoperacao(), array("TE", "TS"))){
			foreach($this->notafiscalreferenciada as $notafiscalreferenciada){
				$notafiscalreferenciada->setidnotafiscal($this->getidnotafiscal());
				$notafiscalreferenciada->setcodestabelec($this->getcodestabelec());
				if(!$notafiscalreferenciada->save()){
					$this->con->rollback();
					return TRUE;
				}
			}
		}

		if($this->getoperacao() == "TE"){
			$pedido_te = objectbytable("pedido", array($this->getcodestabelec(), $this->getnumpedido()), $this->con);
			$notafiscal_ts = objectbytable("notafiscal", $pedido_te->getidnotafiscalpai(), $this->con);
			$this->setchavenfe($notafiscal_ts->getchavenfe());
			parent::save();
		}

		if($this->getoperacao() == "TS" && strlen($this->getchavenfe()) > 0){
			$pedido = objectbytable("pedido", NULL, $this->con);
			$pedido->setidnotafiscalpai($this->getidnotafiscal());
			$pedido->searchbyobject();

			if($pedido->exists()){
				$notafiscal_te = objectbytable("notafiscal", NULL, $this->con);
				$notafiscal_te->setnumpedido($pedido->getnumpedido());
				$notafiscal_te->setcodestabelec($pedido->getcodestabelec());
				$notafiscal_te->searchbyobject();
				if($notafiscal_te->exists()){
					$notafiscal_te->setchavenfe($this->getchavenfe());
					parent::save();
				}
			}
		}

		if($this->getoperacao() == "TS" && param("NOTAFISCAL", "ATIVATRANSENTAUTO", $this->con) == "S" && !$existe){
			$pedido = $tmp_pedido;
			$notafiscal = clone $this;
			$notafiscal->setoperacao("TE");
			$notafiscal->setidnotafiscal(null);
			$notafiscal->setcodestabelec($pedido->getcodestabelec());
			$notafiscal->settipoparceiro("E");
			$notafiscal->setcodparceiro($pedido->getcodparceiro());
			$notafiscal->setnumpedido($pedido->getnumpedido());
			$notafiscal->setnatoperacao($natoperacao->getnatoperacaocp());
			$notafiscal->setstatus("A");

			foreach($notafiscal->itnotafiscal as $itnotafiscal){
				$itnotafiscal->setiditnotafiscal(null);
				$itnotafiscal->setiditnotafiscalpai(null);
				$itnotafiscal->setidnotafiscal(null);
				$itnotafiscal->setcodmovimento(null);
				$itnotafiscal->setcodestabelec($notafiscal->getcodestabelec());
				$itnotafiscal->setnumpedido($notafiscal->getnumpedido());
				$itnotafiscal->setnatoperacao($notafiscal->getnatoperacao());
				$itnotafiscal->setoperacao("TE");

				$codestabelec = $pedido->getcodestabelec();
				$numpedido = $pedido->getnumpedido();

				$itpedido = objectbytable("itpedido", null, $this->con);
				$itpedido->setcodestabelec($codestabelec);
				$itpedido->setnumpedido($numpedido);

				$arr_itpedido = object_array($itpedido);

				foreach($arr_itpedido as $itpedido){
					if($itpedido->getseqitem() == $itnotafiscal->getseqitem()){
						$itnotafiscal->setiditpedido($itpedido->getiditpedido());
					}
				}
			}

			if(!$notafiscal->save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		if($this->getoperacao() == "TS" && strlen($this->getchavenfe()) > 0 && param("NOTAFISCAL", "ATIVATRANSENTAUTO", $this->con) == "S"){
			$pedido = objectbytable("pedido", NULL, $this->con);
			$pedido->setidnotafiscalpai($this->getidnotafiscal());
			$pedido->searchbyobject();
			if($pedido->exists()){
				$notafiscal = objectbytable("notafiscal", null, $this->con);
				$notafiscal->setnumpedido($pedido->getnumpedido());
				$notafiscal->setcodestabelec($pedido->getcodestabelec());
				$notafiscal->setoperacao("TE");
				$notafiscal->searchbyobject();
				if($notafiscal->exists()){
					$notafiscal->setchavenfe($this->getchavenfe());
					if(!$notafiscal->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
		}

		// Verifica se deve gerar uma Guia GNRE automaticamente
		if($this->gettotalgnre() > 0 && param("NOTAFISCAL", "GERARGNREAUTO", $this->con) == "S"){
			$guiagnre = objectbytable("guiagnre", $this->getidnotafiscal(), $this->con);
			if(!$guiagnre->exists()){
				$guiagnre->setidnotafiscal($this->getidnotafiscal());
				$guiagnre->setuf($parceiro_uf);
				$guiagnre->setvalorguia($this->gettotalgnre());
				$guiagnre->setdtvencto($this->getdtemissao());
				$guiagnre->setcodespecie($this->getcodespecie());
				if(!$guiagnre->save()){
					$this->con->rollback();
					return FALSE;
				}
			}
		}

		// Atualiza os custos nas composicoes
		if($this->flag_itnotafiscal && in_array($this->getoperacao(), array("CP"))){
			$arr_codcomposicao = array();
			foreach($this->itnotafiscal as $itnotafiscal){
				$itcomposicao = objectbytable("itcomposicao", NULL, $this->con);
				$itcomposicao->setcodproduto($itnotafiscal->getcodproduto());
				$arr_itcomposicao = object_array($itcomposicao);
				foreach($arr_itcomposicao as $itcomposicao){
					$arr_codcomposicao[] = $itcomposicao->getcodcomposicao();
				}
			}
//			Comentado na OS 4069
//			Não foi criado triger na produtoestab para não demorar outras rotinas
//			$arr_codcomposicao = array_unique($arr_codcomposicao);
//			$arr_composicao = object_array_key(objectbytable("composicao", NULL, $this->con), $arr_codcomposicao);
//			foreach($arr_composicao as $composicao){
//			// Antigamente atualizava todos os precos
//			//	if(!$composicao->atualizar_precos($this->getcodestabelec())){
//			if(!$composicao->atualizar_custofilho($this->getcodestabelec(), "S")){
//					$this->con->rollback();
//					return FALSE;
//				}
//			}
		}

		$natoperacao = objectbytable("natoperacao", $this->getnatoperacao(), $this->con);
		if($natoperacao->getcalcdifaloperinter() == "S" && in_array($this->getoperacao(), array("VD"))){

			$tabelapreco = objectbytable("tabelapreco", $this->getcodtabela(), $this->con);
			$tributacaoproduto = new TributacaoProduto($this->con);
			$tributacaoproduto->setestabelecimento($estabelecimento);
			$tributacaoproduto->setnatoperacao($natoperacao);
			$tributacaoproduto->setoperacaonota($operacaonota);
			$tributacaoproduto->setparceiro($parceiro);
			$tributacaoproduto->settabelapreco($tabelapreco);

			$itemcalculo = new ItemCalculo($this->con);
			$itemcalculo->setestabelecimento($estabelecimento);
			$itemcalculo->setoperacaonota($operacaonota);
			$itemcalculo->setparceiro($parceiro);

			foreach($this->itnotafiscal as $itnotafiscal){
				$natoperacao = objectbytable("natoperacao", $itnotafiscal->getnatoperacao(), $this->con);
				$itemcalculo->setnatoperacao($natoperacao);

				$itnotafiscal->setconnection($this->con);
				$itnotafiscal->setnotafiscal($this);

				if($natoperacao->getcalcdifaloperinter() == "S"){
					$produto = objectbytable("produto", $itnotafiscal->getcodproduto(), $this->con);
					$tributacaoproduto->setproduto($produto);
					$tributacaoproduto->buscar_dados();

					$aliqicmsinter = $tributacaoproduto->getaliqicmsinter(TRUE);
					if($operacaonota->getoperacao() == "VD" && $natoperacao->getcalcdifaloperinter() == "S" && $parceiro->getcontribuinteicms() == "N" && $tributacaoproduto->getvenda_interestadual() && $aliqicmsinter > 0){

						$aliqfcpufdest = $tributacaoproduto->getaliqfcpufdest(TRUE);
						$aliqicmsufdest = $tributacaoproduto->getaliqicmsufdest(TRUE);
						$ano = convert_date($this->getdtemissao(), "Y-m-d", "Y");
						switch($ano){
							case "2016": $aliqicminterpart = 40;
								break;
							case "2017": $aliqicminterpart = 60;
								break;
							case "2018": $aliqicminterpart = 80;
								break;
							default: $aliqicminterpart = 100;
								break;
						}
					}else{
						$aliqfcpufdest = 0;
						$aliqicmsufdest = 0;
						$aliqicmsinter = 0;
						$aliqicminterpart = 0;
					}
					$itnotafiscal->setaliqfcpufdest($aliqfcpufdest);
					$itnotafiscal->setaliqicmsufdest($aliqicmsufdest);
					$itnotafiscal->setaliqicmsinter($aliqicmsinter);
					$itnotafiscal->setaliqicminterpart($aliqicminterpart);

					$itemcalculo->setitem($itnotafiscal);
					$itemcalculo->calcular();

					if(!$itnotafiscal->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			// Recalcula difal
			$this->calcular_totais();

			if(!parent::save()){
				$this->con->rollback();
				return FALSE;
			}
		}

		$this->con->commit();
		return TRUE;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		// Itens da nota
		if($this->flag_itnotafiscal){
			$objectsession = new ObjectSession($this->con, "itnotafiscal", "notafiscal_itnotafiscal_".$this->getoperacao());
			$this->itnotafiscal = $objectsession->getobject();
			$objectsession = new ObjectSession($this->con, "notafiscalreferenciada", "notafiscal_notafiscalreferenciada_".$this->getoperacao());
			$this->notafiscalreferenciada = $objectsession->getobject();
		}
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && !is_array($return[0])){
			$this->itnotafiscal = array();
			// Verifica se vai trabalhar com os itens
			if($this->flag_itnotafiscal){
				$itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
				$itnotafiscal->setidnotafiscal($this->getidnotafiscal());
				$itnotafiscal->setorder("seqitem");
				$this->itnotafiscal = object_array($itnotafiscal);

				$this->notafiscalreferenciada = array();
				$notafiscalreferenciada = objectbytable("notafiscalreferenciada", NULL, $this->con);
				$notafiscalreferenciada->setidnotafiscal($this->getidnotafiscal());
				$this->notafiscalreferenciada = object_array($notafiscalreferenciada);
			}
		}
		return $return;
	}

	function weekOfMonth($date) {
		// estract date parts
		list($y, $m, $d) = explode('-', date('Y-m-d', strtotime($date)));

		// current week, min 1
		$w = 1;

		// for each day since the start of the month
		for ($i = 1; $i <= $d; ++$i) {
			// if that day was a sunday and is not the first day of month
			if ($i > 1 && date('w', strtotime("$y-$m-$i")) == 0) {
				// increment current week
				++$w;
			}
		}

		// now return
		return $w;
	}

	function getidnotafiscal(){
		return $this->fields["idnotafiscal"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getnumnotafis(){
		return $this->fields["numnotafis"];
	}

	function getserie(){
		return $this->fields["serie"];
	}

	function getoperacao(){
		return $this->fields["operacao"];
	}

	function getdtemissao($format = FALSE){
		return ($format ? convert_date($this->fields["dtemissao"], "Y-m-d", "d/m/Y") : $this->fields["dtemissao"]);
	}

	function getdtentrega($format = FALSE){
		return ($format ? convert_date($this->fields["dtentrega"], "Y-m-d", "d/m/Y") : $this->fields["dtentrega"]);
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getcodtransp(){
		return $this->fields["codtransp"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getcodcondpagto(){
		return $this->fields["codcondpagto"];
	}

	function getnatoperacao(){
		return $this->fields["natoperacao"];
	}

	function getvaloricms($format = FALSE){
		return ($format ? number_format($this->fields["valoricms"], 2, ",", "") : $this->fields["valoricms"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function getbonificacao(){
		return $this->fields["bonificacao"];
	}

	function getnumpedido(){
		return $this->fields["numpedido"];
	}

	function getcodespecie(){
		return $this->fields["codespecie"];
	}

	function getnumeroitens(){
		return $this->fields["numeroitens"];
	}

	function gettotaldesconto($format = FALSE){
		return ($format ? number_format($this->fields["totaldesconto"], 2, ",", "") : $this->fields["totaldesconto"]);
	}

	function gettotalacrescimo($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimo"], 2, ",", "") : $this->fields["totalacrescimo"]);
	}

	function gettotalfrete($format = FALSE){
		return ($format ? number_format($this->fields["totalfrete"], 2, ",", "") : $this->fields["totalfrete"]);
	}

	function gettotalipi($format = FALSE){
		return ($format ? number_format($this->fields["totalipi"], 2, ",", "") : $this->fields["totalipi"]);
	}

	function gettotalbaseicms($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicms"], 2, ",", "") : $this->fields["totalbaseicms"]);
	}

	function gettotalicms($format = FALSE){
		return ($format ? number_format($this->fields["totalicms"], 2, ",", "") : $this->fields["totalicms"]);
	}

	function gettotalbaseicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmssubst"], 2, ",", "") : $this->fields["totalbaseicmssubst"]);
	}

	function gettotalicmssubst($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubst"], 2, ",", "") : $this->fields["totalicmssubst"]);
	}

	function gettotalbruto($format = FALSE){
		return ($format ? number_format($this->fields["totalbruto"], 2, ",", "") : $this->fields["totalbruto"]);
	}

	function gettotalliquido($format = FALSE){
		return ($format ? number_format($this->fields["totalliquido"], 2, ",", "") : $this->fields["totalliquido"]);
	}

	function gettotalarecolher($format = FALSE){
		return ($format ? number_format($this->fields["totalarecolher"], 2, ",", "") : $this->fields["totalarecolher"]);
	}

	function gettotalbonificado($format = FALSE){
		return ($format ? number_format($this->fields["totalbonificado"], 2, ",", "") : $this->fields["totalbonificado"]);
	}

	function gettotaldescontoc($format = FALSE){
		return ($format ? number_format($this->fields["totaldescontoc"], 2, ",", "") : $this->fields["totaldescontoc"]);
	}

	function gettotalacrescimoc($format = FALSE){
		return ($format ? number_format($this->fields["totalacrescimoc"], 2, ",", "") : $this->fields["totalacrescimoc"]);
	}

	function gettotalfretec($format = FALSE){
		return ($format ? number_format($this->fields["totalfretec"], 2, ",", "") : $this->fields["totalfretec"]);
	}

	function gettotalipic($format = FALSE){
		return ($format ? number_format($this->fields["totalipic"], 2, ",", "") : $this->fields["totalipic"]);
	}

	function gettotalbaseicmsc($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmsc"], 2, ",", "") : $this->fields["totalbaseicmsc"]);
	}

	function gettotalicmsc($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsc"], 2, ",", "") : $this->fields["totalicmsc"]);
	}

	function gettotalbaseicmssubstc($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmssubstc"], 2, ",", "") : $this->fields["totalbaseicmssubstc"]);
	}

	function gettotalicmssubstc($format = FALSE){
		return ($format ? number_format($this->fields["totalicmssubstc"], 2, ",", "") : $this->fields["totalicmssubstc"]);
	}

	function gettotalbrutoc($format = FALSE){
		return ($format ? number_format($this->fields["totalbrutoc"], 2, ",", "") : $this->fields["totalbrutoc"]);
	}

	function gettotalliquidoc($format = FALSE){
		return ($format ? number_format($this->fields["totalliquidoc"], 2, ",", "") : $this->fields["totalliquidoc"]);
	}

	function gettotalarecolherc($format = FALSE){
		return ($format ? number_format($this->fields["totalarecolherc"], 2, ",", "") : $this->fields["totalarecolherc"]);
	}

	function gettotalbonificadoc($format = FALSE){
		return ($format ? number_format($this->fields["totalbonificadoc"], 2, ",", "") : $this->fields["totalbonificadoc"]);
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getchavenfe(){
		return $this->fields["chavenfe"];
	}

	function gettotalpis($format = FALSE){
		return ($format ? number_format($this->fields["totalpis"], 2, ",", "") : $this->fields["totalpis"]);
	}

	function gettotalcofins($format = FALSE){
		return ($format ? number_format($this->fields["totalcofins"], 2, ",", "") : $this->fields["totalcofins"]);
	}

	function getcupom(){
		return $this->fields["cupom"];
	}

	function getnumeroecf(){
		return $this->fields["numeroecf"];
	}

	function getobservacaofiscal(){
		return $this->fields["observacaofiscal"];
	}

	function gettotalquantidade($format = FALSE){
		return ($format ? number_format($this->fields["totalquantidade"], 4, ",", "") : $this->fields["totalquantidade"]);
	}

	function getespecie(){
		return $this->fields["especie"];
	}

	function getmarca(){
		return $this->fields["marca"];
	}

	function getnumeracao(){
		return $this->fields["numeracao"];
	}

	function getpesobruto($format = FALSE){
		return ($format ? number_format($this->fields["pesobruto"], 4, ",", "") : $this->fields["pesobruto"]);
	}

	function getpesoliquido($format = FALSE){
		return ($format ? number_format($this->fields["pesoliquido"], 4, ",", "") : $this->fields["pesoliquido"]);
	}

	function getmodfrete(){
		return $this->fields["modfrete"];
	}

	function gettranspplacavei(){
		return $this->fields["transpplacavei"];
	}

	function gettranspufvei(){
		return $this->fields["transpufvei"];
	}

	function gettransprntc(){
		return $this->fields["transprntc"];
	}

	function getufdesembaraco(){
		return $this->fields["ufdesembaraco"];
	}

	function getlocaldesembaraco(){
		return $this->fields["localdesembaraco"];
	}

	function getdtdesembaraco($format = FALSE){
		return ($format ? convert_date($this->fields["dtdesembaraco"], "Y-m-d", "d/m/Y") : $this->fields["dtdesembaraco"]);
	}

	function getnumerodi(){
		return $this->fields["numerodi"];
	}

	function getdtregistrodi($format = FALSE){
		return ($format ? convert_date($this->fields["dtregistrodi"], "Y-m-d", "d/m/Y") : $this->fields["dtregistrodi"]);
	}

	function gettotalbaseii($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseii"], 4, ",", "") : $this->fields["totalbaseii"]);
	}

	function gettotalii($format = FALSE){
		return ($format ? number_format($this->fields["totalii"], 4, ",", "") : $this->fields["totalii"]);
	}

	function gettotaliof($format = FALSE){
		return ($format ? number_format($this->fields["totaliof"], 4, ",", "") : $this->fields["totaliof"]);
	}

	function gettotalseguro($format = FALSE){
		return ($format ? number_format($this->fields["totalseguro"], 2, ",", "") : $this->fields["totalseguro"]);
	}

	function gettotalseguroc($format = FALSE){
		return ($format ? number_format($this->fields["totalseguroc"], 2, ",", "") : $this->fields["totalseguroc"]);
	}

	function gettotaldespaduaneira($format = FALSE){
		return ($format ? number_format($this->fields["totaldespaduaneira"], 4, ",", "") : $this->fields["totaldespaduaneira"]);
	}

	function gettotalsiscomex($format = FALSE){
		return ($format ? number_format($this->fields["totalsiscomex"], 4, ",", "") : $this->fields["totalsiscomex"]);
	}

	function getmodelodocfiscal(){
		return $this->fields["modelodocfiscal"];
	}

	function getchavenferef(){
		return $this->fields["chavenferef"];
	}

	function getfinalidade(){
		return $this->fields["finalidade"];
	}

	function gettipoemissao(){
		return $this->fields["tipoemissao"];
	}

	function gettotalbasepis($format = FALSE){
		return ($format ? number_format($this->fields["totalbasepis"], 2, ",", "") : $this->fields["totalbasepis"]);
	}

	function gettotalbasecofins($format = FALSE){
		return ($format ? number_format($this->fields["totalbasecofins"], 2, ",", "") : $this->fields["totalbasecofins"]);
	}

	function getgeraestoque(){
		return $this->fields["geraestoque"];
	}

	function getgerafinanceiro(){
		return $this->fields["gerafinanceiro"];
	}

	function getgeraliquidado(){
		return $this->fields["geraliquidado"];
	}

	function getgerafiscal(){
		return $this->fields["gerafiscal"];
	}

	function getgeraicms(){
		return $this->fields["geraicms"];
	}

	function getgeraipi(){
		return $this->fields["geraipi"];
	}

	function getgerapiscofins(){
		return $this->fields["gerapiscofins"];
	}

	function getgeracustomedio(){
		return $this->fields["geracustomedio"];
	}

	function getxmlnfe(){
		return $this->fields["xmlnfe"];
	}

	function getdtdigitacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtdigitacao"], "Y-m-d", "d/m/Y") : $this->fields["dtdigitacao"]);
	}

	function gethrdigitacao($format = FALSE){
		return ($format ? substr($this->fields["hrdigitacao"], 0, 8) : $this->fields["hrdigitacao"]);
	}

	function gettotalbaseisento($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseisento"], 2, ",", "") : $this->fields["totalbaseisento"]);
	}

	function getidnotafrete(){
		return $this->fields["idnotafrete"];
	}

	function gettotalnotafrete($format = FALSE){
		return ($format ? number_format($this->fields["totalnotafrete"], 2, ",", "") : $this->fields["totalnotafrete"]);
	}

	function getcodtabela(){
		return $this->fields["codtabela"];
	}

	function getprotocolo(){
		return $this->fields["protocolo"];
	}

	function getcodrepresentante(){
		return $this->fields["codrepresentante"];
	}

	function getcodpremio(){
		return $this->fields["codpremio"];
	}

	function getcodfornecref(){
		return $this->fields["codfornecref"];
	}

	function gettotalcustotab($format = FALSE){
		return ($format ? number_format($this->fields["totalcustotab"], 2, ",", "") : $this->fields["totalcustotab"]);
	}

	function getcodrastreamento(){
		return $this->fields["codrastreamento"];
	}

	function getdtrastreamento($format = FALSE){
		return ($format ? convert_date($this->fields["dtrastreamento"], "Y-m-d", "d/m/Y") : $this->fields["dtrastreamento"]);
	}

	function getfinancpercentual($format = FALSE){
		return ($format ? number_format($this->fields["financpercentual"], 2, ",", "") : $this->fields["financpercentual"]);
	}

	function gettotalgnre($format = FALSE){
		return ($format ? number_format($this->fields["totalgnre"], 2, ",", "") : $this->fields["totalgnre"]);
	}

	function getemissaopropria(){
		return $this->fields["emissaopropria"];
	}

	function getcodigostatus(){
		return $this->fields["codigostatus"];
	}

	function getxmotivo(){
		return $this->fields["xmotivo"];
	}

	function gettipoevento(){
		return $this->fields["tipoevento"];
	}

	function getcce(){
		return $this->fields["cce"];
	}

	function getprotocolonfe(){
		return $this->fields["protocolonfe"];
	}

	function getprotocolocce(){
		return $this->fields["protocolocce"];
	}

	function getsequenciaevento(){
		return $this->fields["sequenciaevento"];
	}

	function getdataautorizacao(){
		return $this->fields["dataautorizacao"];
	}

	function getdatacancelamento(){
		return $this->fields["datacancelamento"];
	}

	function getdatacce(){
		return $this->fields["datacce"];
	}

	function getstatuscontabil(){
		return $this->fields["statuscontabil"];
	}

	function getviatransporte(){
		return $this->fields["viatransporte"];
	}

	function gettipoimportacao(){
		return $this->fields["tipoimportacao"];
	}

	function getcnpjadquirente(){
		return $this->fields["cnpjadquirente"];
	}

	function getufterceiro(){
		return $this->fields["ufterceiro"];
	}

	function getprotocolocanc(){
		return $this->fields["protocolocanc"];
	}

	function getidnotafiscalref(){
		return $this->fields["idnotafiscalref"];
	}

	function gettotalvalorafrmm($format = FALSE){
		return ($format ? number_format($this->fields["totalvalorafrmm"], 4, ",", "") : $this->fields["totalvalorafrmm"]);
	}

	function getimpresso(){
		return $this->fields["impresso"];
	}

	function getsuperimpressao(){
		return $this->fields["impresso"];
	}

	function gettipoajuste(){
		return $this->fields["tipoajuste"];
	}

	function getindpres(){
		return $this->fields["indpres"];
	}

	function getrecibonfe(){
		return $this->fields["recibonfe"];
	}

	function getbasecalcufdest($format = FALSE){
		return ($format ? number_format($this->fields["basecalcufdest"], 2, ",", "") : $this->fields["basecalcufdest"]);
	}

	function gettotalicmsufdest($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsufdest"], 2, ",", "") : $this->fields["totalicmsufdest"]);
	}

	function gettotalbcfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["totalbcfcpufdest"], 2, ",", "") : $this->fields["totalbcfcpufdest"]);
	}

	function gettotalfcpufdest($format = FALSE){
		return ($format ? number_format($this->fields["totalfcpufdest"], 2, ",", "") : $this->fields["totalfcpufdest"]);
	}

	function gettotalbcfcpufdestst($format = FALSE){
		return ($format ? number_format($this->fields["totalbcfcpufdestst"], 2, ",", "") : $this->fields["totalbcfcpufdestst"]);
	}

	function gettotalfcpufdestst($format = FALSE){
		return ($format ? number_format($this->fields["totalfcpufdestst"], 2, ",", "") : $this->fields["totalfcpufdestst"]);
	}

	function gettotalicmsufremet($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsufremet"], 2, ",", "") : $this->fields["totalicmsufremet"]);
	}

	function getxmlevento($format = FALSE){
		return $this->fields["xmlevento"];
	}

	function gettotalbasestretido($format = FALSE){
		return ($format ? number_format($this->fields["totalbasestretido"], 4, ",", "") : $this->fields["totalbasestretido"]);
	}

	function gettotalvalorstretido($format = FALSE){
		return ($format ? number_format($this->fields["totalvalorstretido"], 4, ",", "") : $this->fields["totalvalorstretido"]);
	}

	function getcupomnotafiscal($format = FALSE){
		return $this->fields["cupomnotafiscal"];
	}

	function getnumnotafisfinal(){
		return $this->fields["numnotafisfinal"];
	}

	function getseriefinal(){
		return $this->fields["seriefinal"];
	}

	function gettiponotafiscal(){
		return $this->fields["tiponotafiscal"];
	}

	function getnumerorps(){
		return $this->fields["numerorps"];
	}

	function gethandlenfse(){
		return $this->fields["handlenfse"];
	}

	function getlotenfse(){
		return $this->fields["lotenfse"];
	}

	function getvalortotalinss($format = FALSE){
		return ($format ? number_format($this->fields["valortotalinss"], 2, ",", "") : $this->fields["valortotalinss"]);
	}

	function getvalortotalir($format = FALSE){
		return ($format ? number_format($this->fields["valortotalir"], 2, ",", "") : $this->fields["valortotalir"]);
	}

	function getvalortotalcsll($format = FALSE){
		return ($format ? number_format($this->fields["valortotalcsll"], 2, ",", "") : $this->fields["valortotalcsll"]);
	}

	function gettotaldesoneracao($format = FALSE){
		return ($format ? number_format($this->fields["totaldesoneracao"], 2, ",", "") : $this->fields["totaldesoneracao"]);
	}

	function getbasecalculofcpst($format = FALSE){
		return ($format ? number_format($this->fields["basecalculofcpst"], 2, ",", "") : $this->fields["basecalculofcpst"]);
	}

	function getvalorfcpst($format = FALSE){
		return ($format ? number_format($this->fields["valorfcpst"], 2, ",", "") : $this->fields["valorfcpst"]);
	}

	function getnumerodocumento(){
		return $this->fields["numerodocumento"];
	}

	function gettotalbaseicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalbaseicmsnaoaproveitavel"], 2, ",", "") : $this->fields["totalbaseicmsnaoaproveitavel"]);
	}

	function gettotalicmsnaoaproveitavel($format = FALSE){
		return ($format ? number_format($this->fields["totalicmsnaoaproveitavel"], 2, ",", "") : $this->fields["totalicmsnaoaproveitavel"]);
	}

	function getxmlprotocoloautodeneg($format = FALSE){
		return $this->fields["xmlprotocoloautodeneg"];
	}

	function getxmlprotocolocanc($format = FALSE){
		return $this->fields["xmlprotocolocanc"];
	}

	function getchavecfe(){
		return $this->fields["chavecfe"];
	}

	function setidnotafiscal($value){
		$this->fields["idnotafiscal"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setnumnotafis($value){
		$this->fields["numnotafis"] = value_numeric($value);
	}

	function setserie($value){
		$this->fields["serie"] = value_string($value, 3);
	}

	function setoperacao($value){
		$this->fields["operacao"] = value_string($value, 2);
	}

	function setdtemissao($value){
		$this->fields["dtemissao"] = value_date($value);
	}

	function setdtentrega($value){
		$this->fields["dtentrega"] = value_date($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setcodtransp($value){
		$this->fields["codtransp"] = value_numeric($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setcodcondpagto($value){
		$this->fields["codcondpagto"] = value_numeric($value);
	}

	function setnatoperacao($value){
		$this->fields["natoperacao"] = value_string($value, 9);
	}

	function setvaloricms($value){
		$this->fields["valoricms"] = value_numeric($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value, 5000);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

	function setbonificacao($value){
		$this->fields["bonificacao"] = value_string($value, 1);
	}

	function setnumpedido($value){
		$this->fields["numpedido"] = value_numeric($value);
	}

	function setcodespecie($value){
		$this->fields["codespecie"] = value_numeric($value);
	}

	function setnumeroitens($value){
		$this->fields["numeroitens"] = value_numeric($value);
	}

	function settotaldesconto($value){
		$this->fields["totaldesconto"] = value_numeric($value);
	}

	function settotalacrescimo($value){
		$this->fields["totalacrescimo"] = value_numeric($value);
	}

	function settotalfrete($value){
		$this->fields["totalfrete"] = value_numeric($value);
	}

	function settotalipi($value){
		$this->fields["totalipi"] = value_numeric($value);
	}

	function settotalbaseicms($value){
		$this->fields["totalbaseicms"] = value_numeric($value);
	}

	function settotalicms($value){
		$this->fields["totalicms"] = value_numeric($value);
	}

	function settotalbaseicmssubst($value){
		$this->fields["totalbaseicmssubst"] = value_numeric($value);
	}

	function settotalicmssubst($value){
		$this->fields["totalicmssubst"] = value_numeric($value);
	}

	function settotalbruto($value){
		$this->fields["totalbruto"] = value_numeric($value);
	}

	function settotalliquido($value){
		$this->fields["totalliquido"] = value_numeric($value);
	}

	function settotalarecolher($value){
		$this->fields["totalarecolher"] = value_numeric($value);
	}

	function settotalbonificado($value){
		$this->fields["totalbonificado"] = value_numeric($value);
	}

	function settotaldescontoc($value){
		$this->fields["totaldescontoc"] = value_numeric($value);
	}

	function settotalacrescimoc($value){
		$this->fields["totalacrescimoc"] = value_numeric($value);
	}

	function settotalfretec($value){
		$this->fields["totalfretec"] = value_numeric($value);
	}

	function settotalipic($value){
		$this->fields["totalipic"] = value_numeric($value);
	}

	function settotalbaseicmsc($value){
		$this->fields["totalbaseicmsc"] = value_numeric($value);
	}

	function settotalicmsc($value){
		$this->fields["totalicmsc"] = value_numeric($value);
	}

	function settotalbaseicmssubstc($value){
		$this->fields["totalbaseicmssubstc"] = value_numeric($value);
	}

	function settotalicmssubstc($value){
		$this->fields["totalicmssubstc"] = value_numeric($value);
	}

	function settotalbrutoc($value){
		$this->fields["totalbrutoc"] = value_numeric($value);
	}

	function settotalliquidoc($value){
		$this->fields["totalliquidoc"] = value_numeric($value);
	}

	function settotalarecolherc($value){
		$this->fields["totalarecolherc"] = value_numeric($value);
	}

	function settotalbonificadoc($value){
		$this->fields["totalbonificadoc"] = value_numeric($value);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value, 1);
	}

	function setchavenfe($value){
		$this->fields["chavenfe"] = value_string($value, 44);
	}

	function settotalpis($value){
		$this->fields["totalpis"] = value_numeric($value);
	}

	function settotalcofins($value){
		$this->fields["totalcofins"] = value_numeric($value);
	}

	function setcupom($value){
		$this->fields["cupom"] = value_numeric($value);
	}

	function setnumeroecf($value){
		$this->fields["numeroecf"] = value_numeric($value);
	}

	function setobservacaofiscal($value){
		$this->fields["observacaofiscal"] = value_string($value);
	}

	function settotalquantidade($value){
		$this->fields["totalquantidade"] = value_numeric($value);
	}

	function setespecie($value){
		$this->fields["especie"] = value_string($value, 60);
	}

	function setmarca($value){
		$this->fields["marca"] = value_string($value, 60);
	}

	function setnumeracao($value){
		$this->fields["numeracao"] = value_string($value, 60);
	}

	function setpesobruto($value){
		$this->fields["pesobruto"] = value_numeric($value);
	}

	function setpesoliquido($value){
		$this->fields["pesoliquido"] = value_numeric($value);
	}

	function setmodfrete($value){
		$this->fields["modfrete"] = value_string($value, 1);
	}

	function settranspplacavei($value){
		$this->fields["transpplacavei"] = value_string($value, 8);
	}

	function settranspufvei($value){
		$this->fields["transpufvei"] = value_string($value, 2);
	}

	function settransprntc($value){
		$this->fields["transprntc"] = value_string($value, 20);
	}

	function setufdesembaraco($value){
		$this->fields["ufdesembaraco"] = value_string($value, 2);
	}

	function setlocaldesembaraco($value){
		$this->fields["localdesembaraco"] = value_string($value, 60);
	}

	function setdtdesembaraco($value){
		$this->fields["dtdesembaraco"] = value_date($value);
	}

	function setnumerodi($value){
		//Jes Alterado o tipo do campo no banco de dados, o tipo inteiro nao suporta 10 digitos que é o tamanho da DI
		//$this->fields["numerodi"] = value_numeric($value);
		$this->fields["numerodi"] = value_string($value, 10);
	}

	function setdtregistrodi($value){
		$this->fields["dtregistrodi"] = value_date($value);
	}

	function settotalbaseii($value){
		$this->fields["totalbaseii"] = value_numeric($value);
	}

	function settotalii($value){
		$this->fields["totalii"] = value_numeric($value);
	}

	function settotaliof($value){
		$this->fields["totaliof"] = value_numeric($value);
	}

	function settotalseguro($value){
		$this->fields["totalseguro"] = value_numeric($value);
	}

	function settotalseguroc($value){
		$this->fields["totalseguroc"] = value_numeric($value);
	}

	function settotaldespaduaneira($value){
		$this->fields["totaldespaduaneira"] = value_numeric($value);
	}

	function settotalsiscomex($value){
		$this->fields["totalsiscomex"] = value_numeric($value);
	}

	function setmodelodocfiscal($value){
		$this->fields["modelodocfiscal"] = value_string($value, 2);
	}

	function setchavenferef($value){
		$this->fields["chavenferef"] = value_string($value, 44);
	}

	function setfinalidade($value){
		$this->fields["finalidade"] = value_string($value, 1);
	}

	function settipoemissao($value){
		$this->fields["tipoemissao"] = value_string($value, 1);
	}

	function settotalbasepis($value){
		$this->fields["totalbasepis"] = value_numeric($value);
	}

	function settotalbasecofins($value){
		$this->fields["totalbasecofins"] = value_numeric($value);
	}

	function setgeraestoque($value){
		$this->fields["geraestoque"] = value_string($value, 1);
	}

	function setgerafinanceiro($value){
		$this->fields["gerafinanceiro"] = value_string($value, 1);
	}

	function setgeraliquidado($value){
		$this->fields["geraliquidado"] = value_string($value, 1);
	}

	function setgerafiscal($value){
		$this->fields["gerafiscal"] = value_string($value, 1);
	}

	function setgeraicms($value){
		$this->fields["geraicms"] = value_string($value, 1);
	}

	function setgeraipi($value){
		$this->fields["geraipi"] = value_string($value, 1);
	}

	function setgerapiscofins($value){
		$this->fields["gerapiscofins"] = value_string($value, 1);
	}

	function setgeracustomedio($value){
		$this->fields["geracustomedio"] = value_string($value, 1);
	}

	function setxmlnfe($value){
		$this->fields["xmlnfe"] = value_string($value);
	}

	function setdtdigitacao($value){
		$this->fields["dtdigitacao"] = value_date($value);
	}

	function sethrdigitacao($value){
		$this->fields["hrdigitacao"] = value_time($value);
	}

	function settotalbaseisento($value){
		$this->fields["totalbaseisento"] = value_numeric($value);
	}

	function setidnotafrete($value){
		$this->fields["idnotafrete"] = value_numeric($value);
	}

	function settotalnotafrete($value){
		$this->fields["totalnotafrete"] = value_numeric($value);
	}

	function setcodtabela($value){
		$this->fields["codtabela"] = value_numeric($value);
	}

	function setprotocolo($value){
		$this->fields["protocolo"] = value_string($value, 40);
	}

	function setcodrepresentante($value){
		$this->fields["codrepresentante"] = value_numeric($value);
	}

	function setcodpremio($value){
		$this->fields["codpremio"] = value_numeric($value);
	}

	function setcodfornecref($value){
		$this->fields["codfornecref"] = value_numeric($value);
	}

	function settotalcustotab($value){
		$this->fields["totalcustotab"] = value_numeric($value);
	}

	function setcodrastreamento($value){
		$this->fields["codrastreamento"] = value_string($value, 25);
	}

	function setdtrastreamento($value){
		$this->fields["dtrastreamento"] = value_date($value);
	}

	function setfinancpercentual($value){
		$this->fields["financpercentual"] = value_numeric($value);
	}

	function settotalgnre($value){
		$this->fields["totalgnre"] = value_numeric($value);
	}

	function setemissaopropria($value){
		$this->fields["emissaopropria"] = value_string($value);
	}

	function setcodigostatus($value){
		$this->fields["codigostatus"] = value_numeric($value);
	}

	function setxmotivo($value){
		$this->fields["xmotivo"] = value_string($value);
	}

	function settipoevento($value){
		$this->fields["tipoevento"] = value_string($value);
	}

	function setcce($value){
		$this->fields["cce"] = value_string($value);
	}

	function setprotocolonfe($value){
		$this->fields["protocolonfe"] = value_string($value);
	}

	function setprotocolocce($value){
		$this->fields["protocolocce"] = value_string($value);
	}

	function setsequenciaevento($value){
		$this->fields["sequenciaevento"] = value_numeric($value);
	}

	function setdataautorizacao($value){
		$this->fields["dataautorizacao"] = value_date($value);
	}

	function setdatacancelamento($value){
		$this->fields["datacancelamento"] = value_date($value);
	}

	function setdatacce($value){
		$this->fields["datacce"] = value_date($value);
	}

	function setstatuscontabil($value){
		$this->fields["statuscontabil"] = value_string($value, 1);
	}

	function setviatransporte($value){
		$this->fields["viatransporte"] = value_string($value, 2);
	}

	function settipoimportacao($value){
		$this->fields["tipoimportacao"] = value_string($value, 1);
	}

	function setcnpjadquirente($value){
		$this->fields["cnpjadquirente"] = value_string($value, 18);
	}

	function setufterceiro($value){
		$this->fields["ufterceiro"] = value_string($value, 2);
	}

	function setprotocolocanc($value){
		$this->fields["protocolocanc"] = value_string($value);
	}

	function setidnotafiscalref($value){
		$this->fields["idnotafiscalref"] = value_numeric($value);
	}

	function settotalvalorafrmm($value){
		$this->fields["totalvalorafrmm"] = value_numeric($value);
	}

	function setimpresso($value){
		$this->fields["impresso"] = value_string($value, 1);
	}

	function setsuperimpressao($value){
		$this->fields["superimpressao"] = value_string($value, 20);
	}

	function settipoajuste($value){
		$this->fields["tipoajuste"] = value_numeric($value);
	}

	function setindpres($value){
		$this->fields["indpres"] = value_numeric($value);
	}

	function setrecibonfe($value){
		$this->fields["recibonfe"] = value_string($value, 15);
	}

	function settotalicmsufdest($value){
		$this->fields["totalicmsufdest"] = value_numeric($value);
	}

	function settotalfcpufdest($value){
		$this->fields["totalfcpufdest"] = value_numeric($value);
	}

	function settotalicmsufremet($value){
		$this->fields["totalicmsufremet"] = value_numeric($value);
	}

	function setbasecalcufdest($value){
		$this->fields["basecalcufdest"] = value_numeric($value);
	}

	function settotalbcfcpufdest($value){
		$this->fields["totalbcfcpufdest"] = value_numeric($value);
	}

	function settotalbcfcpufdestst($value){
		$this->fields["totalbcfcpufdestst"] = value_numeric($value);
	}

	function settotalfcpufdestst($value){
		$this->fields["totalfcpufdestst"] = value_numeric($value);
	}

	function setxmlevento($value){
		$this->fields["xmlevento"] = value_string($value);
	}

	function settotalbasestretido($value){
		$this->fields["totalbasestretido"] = value_numeric($value);
	}

	function settotalvalorstretido($value){
		$this->fields["totalvalorstretido"] = value_numeric($value);
	}

	function setcupomnotafiscal($value){
		$this->fields["cupomnotafiscal"] = value_string($value);
	}

	function setnumnotafisfinal($value){
		$this->fields["numnotafisfinal"] = value_numeric($value);
	}

	function setseriefinal($value){
		$this->fields["seriefinal"] = value_numeric($value);
	}

	function settiponotafiscal($value){
		$this->fields["tiponotafiscal"] = value_string($value, 1);
	}

	function setnumerorps($value){
		$this->fields["numerorps"] = value_string($value, 20);
	}

	function sethandlenfse($value){
		$this->fields["handlenfse"] = value_string($value, 20);
	}

	function setlotenfse($value){
		$this->fields["lotenfse"] = value_string($value, 15);
	}

	function setvalortotalinss($value){
		$this->fields["valortotalinss"] = value_numeric($value);
	}

	function setvalortotalir($value){
		$this->fields["valortotalir"] = value_numeric($value);
	}

	function setvalortotalcsll($value){
		$this->fields["valortotalcsll"] = value_numeric($value);
	}

	function settotaldesoneracao($value){
		$this->fields["totaldesoneracao"] = value_numeric($value);
	}

	function setbasecalculofcpst($value){
		$this->fields["basecalculofcpst"] = value_numeric($value);
	}

	function setvalorfcpst($value){
		$this->fields["valorfcpst"] = value_numeric($value);
	}

	function setnumerodocumento($value){
		$this->fields["numerodocumento"] = value_string($value, 8);
	}

	function settotalbaseicmsnaoaproveitavel($value){
		$this->fields["totalbaseicmsnaoaproveitavel"] = value_numeric($value);
	}

	function settotalicmsnaoaproveitavel($value){
		$this->fields["totalicmsnaoaproveitavel"] = value_numeric($value);
	}

	function setxmlprotocoloautodeneg($value){
		$this->fields["xmlprotocoloautodeneg"] = value_string($value);
	}

	function setxmlprotocolocanc($value){
		$this->fields["xmlprotocolocanc"] = value_string($value);
	}

	function setchavecfe($value){
		$this->fields["chavecfe"] = value_string($value, 44);
	}
}
