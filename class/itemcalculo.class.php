<?php

require_file("class/tributacaoproduto.class.php");

class ItemCalculo{

	private $con;
	private $item;
	private $classfiscalnfe;
	private $classfiscalnfs;
	private $estado_origem;
	private $estado_destino;
	private $estabelecimento;
	private $natoperacao;
	private $operacaonota;
	private $parceiro;
	private $notafiscal_pedidotrunca;
	private $modfrete;
	private $guiagnre;
	private $ncmestado;
	private $produto;
	private $descincondicional;
	private $arr_objeto = array(); // Array com objetos pre-carregados, para ficar mais rapido

	function __construct($con){
		$this->con = $con;
		$this->guiagnre = FALSE;
		$this->notafiscal_pedidotrunca = param("NOTAFISCAL", "PEDIDOTRUNCA", $this->con);
		$this->descincondicional = "";
	}

	private function buscar_estado(){
		if(!is_object($this->estabelecimento) || !is_object($this->operacaonota) || !is_object($this->parceiro)){
			return FALSE;
		}

		$estado_estabelecimento = $this->carregar_objeto("estado", $this->estabelecimento->getuf());
		switch($this->operacaonota->getparceiro()){
			case "C":
				$estado_parceiro = $this->carregar_objeto("estado", $this->parceiro->getufres());
				break;
			case "E":
				$estado_parceiro = $this->carregar_objeto("estado", $this->parceiro->getuf());
				break;
			case "F":
				$estado_parceiro = $this->carregar_objeto("estado", $this->parceiro->getuf());
				break;
		}

		if($this->operacaonota->gettipo() == "E"){
			$this->estado_origem = $estado_parceiro;
			$this->estado_destino = $estado_estabelecimento;
		}else{
			$this->estado_origem = $estado_estabelecimento;
			$this->estado_destino = $estado_parceiro;
		}
		if(isset($this->produto)){
			$this->ncmestado = $this->carregar_objeto("ncmestado", array($this->produto->getidncm(), $this->estado_destino->getuf()));
		}
	}

	function calcular(){
		if(!is_object($this->classfiscalnfe) || !is_object($this->classfiscalnfs)){
			$produto = $this->carregar_objeto("produto", $this->item->getcodproduto());
			$estadotributo = $this->carregar_objeto("estadotributo", array($this->estabelecimento->getuf(), $this->estabelecimento->getregimetributario(), $produto->getcodproduto()));
			$this->classfiscalnfe = $this->carregar_objeto("classfiscal", (strlen($estadotributo->getcodcfnfe()) > 0 ? $estadotributo->getcodcfnfe() : $produto->getcodcfnfe()));
			$this->classfiscalnfs = $this->carregar_objeto("classfiscal", (strlen($estadotributo->getcodcfnfs()) > 0 ? $estadotributo->getcodcfnfs() : $produto->getcodcfnfs()));
		}

		$this->item->settotalbruto($this->calcular_totalbruto());
		$this->item->settotaldesconto($this->calcular_totaldesconto());
		$this->item->settotalacrescimo($this->calcular_totalacrescimo());
		$this->item->settotalfrete($this->calcular_totalfrete());
		if($this->item->gettablename() != "itorcamento"){
			$this->item->settotalseguro($this->calcular_totalseguro());
			$this->item->settotalcif($this->calcular_totalcif());
			$this->item->settotalbaseii($this->calcular_totalbaseii());
			$this->item->settotalii($this->calcular_totalii());
			$this->item->settotalipi($this->calcular_totalipi());
			//calcular primeiro o icms proprio, para se for permitido abate-lo da base do pis/cofins
			if($this->operacaonota->getoperacao != "IM"){
				$this->item->settotalbaseicms($this->calcular_totalbaseicms());
				$this->item->settotalicms($this->calcular_totalicms());
			}
			$this->item->settotalbasepis($this->calcular_totalbasepis());
			$this->item->settotalbasecofins($this->calcular_totalbasecofins());
			$this->item->settotalpis($this->calcular_totalpis());
			$this->item->settotalcofins($this->calcular_totalcofins());
			if($this->operacaonota->getoperacao() == "IM"){
				$this->item->settotalbaseicms($this->calcular_totalbaseicms());
				$this->item->settotalicms($this->calcular_totalicms());
			}
			$this->item->setvalordesoneracao($this->calcular_valoricmsdesonerado());
			$this->item->settotalbaseicmssubst($this->calcular_totalbaseicmssubst());
			$this->item->settotalicmssubst($this->calcular_totalicmssubst());
			$this->item->settotalarecolher($this->calcular_totalicmssubst(TRUE));
			$this->item->setbasecalcufdest($this->calcular_basecalcufdest());
			$this->item->setvaloricmsufdest($this->calcular_valoricmsufdest());
			$this->item->setvaloricmsufremet($this->calcular_valoricmsufremet());
			$this->item->setvalorbcfcpufdest($this->calcular_basefcpufdest());
			$this->item->setvalorfcpufdest($this->calcular_valorfcpufdest());
			$this->item->setbasecalculofcpst($this->calcular_basecalculofcpst());
			$this->item->setvalorfcpst($this->calcular_valorfcpst());
			$this->item->setvalorinss($this->calcular_valorinss());
			$this->item->setvalorir($this->calcular_valorir());
			$this->item->setvalorcsll($this->calcular_valorcsll());
			if(!$this->guiagnre){ // Tratamento para nao entrar em looping
				if(method_exists($this->item, "setguiagnre")){
					$this->item->setguiagnre($this->calcular_totalgnre());
				}
			}
			if($this->item->gettotalicmssubst() == 0){
				$this->item->settotalbaseicmssubst(0);
			}
		}
		$this->item->settotalliquido($this->calcular_totalliquido());
	}

	private function calcular_totalbruto(){
		if($this->item->gettablename() == "itorcamento"){
			$totalbruto = $this->item->getquantidade() * $this->item->getpreco();
		}else{
			$totalbruto = $this->item->getquantidade() * $this->item->getprecopolitica();
		}
		return ($this->notafiscal_pedidotrunca == "T" ? trunc($totalbruto, 2) : round($totalbruto, 2));
	}

	private function calcular_totaldesconto(){
		/* OS 5241 */
		$totaldesconto = round($this->item->gettotalbruto() * $this->item->getpercdescto() / 100, 2) + round($this->item->getquantidade() * $this->item->getvaldescto(),2);
		return round($totaldesconto, 2);
	}

	private function calcular_totalacrescimo(){
		/* OS 5241 */
		$totalacrescimo = round(($this->item->gettotalbruto() - $this->item->gettotaldesconto()) * $this->item->getpercacresc() / 100, 2) + round($this->item->getquantidade() * $this->item->getvalacresc(),2);
		return round($totalacrescimo, 2);
	}

	private function calcular_totalfrete(){
		$totalfrete = ($this->item->gettotalbruto() - $this->item->gettotaldesconto() + $this->item->gettotalacrescimo()) * $this->item->getpercfrete() / 100 + $this->item->getquantidade() * $this->item->getvalfrete();
		return round($totalfrete, 2);
	}

	private function calcular_totalseguro(){
		$totalseguro = ($this->item->gettotalbruto() - $this->item->gettotaldesconto() + $this->item->gettotalacrescimo()) * $this->item->getpercseguro() / 100 + $this->item->getquantidade() * $this->item->getvalseguro();
		return round($totalseguro, 2);
	}

	private function calcular_totalcif(){
		if($this->modfrete == "1"){
			$totalbruto = ($this->item->gettotalbruto() - $this->item->gettotaldesconto() + $this->item->gettotalacrescimo() + $this->item->gettotalfrete() + $this->item->gettotalseguro());
		}else{
			$totalbruto = ($this->item->gettotalbruto() - $this->item->gettotaldesconto() + $this->item->gettotalacrescimo() + $this->item->gettotalseguro());
		}
		return round($totalbruto, 2);
	}

	private function calcular_totalbaseii(){
		if($this->item->getaliqii() > 0){
			$totalbaseii = $this->item->gettotalcif();
		}else{
			$totalbaseii = 0;
		}
		return round($totalbaseii, 2);
	}

	private function calcular_totalii(){
		$totalii = $this->item->gettotalbaseii() * $this->item->getaliqii() / 100;
		return round($totalii, 2);
	}

	private function calcular_totalipi(){
		if($this->item->getvalipi() > 0){
			$totalipi = $this->item->getquantidade() * $this->item->getvalipi();
		}else{
			if($this->operacaonota->getoperacao() == "IM"){
				$baseipi = $this->item->gettotalcif() + $this->item->gettotalii();
			}else{
				$baseipi = $this->item->gettotalbruto() - $this->item->gettotaldesconto() + $this->item->gettotalacrescimo();
			}
			$totalipi = $baseipi * $this->item->getpercipi() / 100;
		}
		return round($totalipi, 2);
	}

	private function calcular_totalbasepis(){
		if($this->natoperacao->getgerapiscofins() == "N" || $this->item->getaliqpis() == 0){
			$totalbasepis = 0;
		}else{
			if($this->operacaonota->getoperacao() == "IM"){
				$totalbasepis = $this->item->gettotalcif();
			}else{
				if($this->natoperacao->getdescicmspbasepiscofins() == "S"){
					//$totalbasepis = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", ""));
					//$totalbasepis = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()), 2, ".", ""));
					$totalbasepis = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()), 2, ".", ""));
					$totalbasepis -= $this->item->gettotalicms();
				}else{
					//$totalbasepis = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", ""));
					//$totalbasepis = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()), 2, ".", ""));
					$totalbasepis = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()), 2, ".", ""));
				}
			}
			if(in_array($this->operacaonota->getoperacao(), array("CP", "PR"))){
				if($this->natoperacao->getcalculaipipiscofins() == "S"){
					$totalbasepis += $this->item->gettotalipi();
				}
			}
			$totalbasepis = $totalbasepis * (1 - $this->item->getredpis() / 100);
		}
		return round($totalbasepis, 2);
	}

	private function calcular_totalbasecofins(){
		if($this->natoperacao->getgerapiscofins() == "N" || $this->item->getaliqcofins() == 0){
			$totalbasecofins = 0;
		}else{
			if($this->operacaonota->getoperacao() == "IM"){
				$totalbasecofins = $this->item->gettotalcif();
			}else{
				if($this->natoperacao->getdescicmspbasepiscofins() == "S"){
					//$totalbasecofins = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", ""));
					//$totalbasecofins = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()), 2, ".", ""));
					$totalbasecofins = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()), 2, ".", ""));
					$totalbasecofins -= $this->item->gettotalicms();
				}else{
					//$totalbasecofins = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", ""));
					//$totalbasecofins = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()), 2, ".", ""));
					$totalbasecofins = (number_format($this->item->gettotalbruto(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()), 2, ".", ""));
				}
			}
			if(in_array($this->operacaonota->getoperacao(), array("CP", "PR"))){
				if($this->natoperacao->getcalculaipipiscofins() == "S"){
					$totalbasecofins += $this->item->gettotalipi();
				}
			}
			$totalbasecofins = $totalbasecofins * (1 - $this->item->getredcofins() / 100);
		}
		return round($totalbasecofins, 2);
	}

	private function calcular_totalpis(){
		$totalpis = $this->item->gettotalbasepis() * $this->item->getaliqpis() / 100;
		return round($totalpis, 2);
	}

	private function calcular_totalcofins(){
		$totalcofins = $this->item->gettotalbasecofins() * $this->item->getaliqcofins() / 100;
		return round($totalcofins, 2);
	}

	private function calcular_totalbaseicms(){
		$icmsent = $this->item->getaliqicms();
		$icmssai = $this->getaliquotaicmssaida();
		if($this->natoperacao->getgeraicms() == "S" && $this->item->getaliqicms() > 0 && (in_array($this->item->gettptribicms(), array("T", "R")) || ($this->item->gettptribicms() == "F" && ( $this->item->getaliqiva() > 0 || $this->item->getvalorpauta() > 0 || ($this->estado_origem->getuf() != $this->estado_destino->getuf()) && $icmssai > $icmsent)))){
			if($this->operacaonota->getoperacao() == "IM"){
				$totalbaseicms = (($this->item->gettotalcif() + $this->item->gettotalii() + $this->item->gettotalipi() + $this->item->gettotalpis() + $this->item->gettotalcofins() + $this->item->getvalsiscomex() + $this->item->getvalorafrmm() + $this->item->getdespaduaneira()) / (1 - $this->item->getaliqicms() / 100)) * (1 - $this->item->getredicms() / 100);
			}else{
				if($this->natoperacao->getcalcfretebaseicms() == "S"){
					$frete = $this->item->gettotalfrete();
				}else{
					$frete = 0;
				}
				if($this->natoperacao->getcalcicmsvalorbruto() == "N"){
					$desconto = $this->item->gettotaldesconto();
				}else{
					$desconto = 0;
				}
				if($this->natoperacao->getcalcipibaseicms() == "S"){
					$totalipi = $this->item->gettotalipi();
				}else{
					$totalipi = 0;
				}
//				$totalbaseicms = ($this->item->gettotalbruto() + ($this->natoperacao->getcalcfretebaseicms() == "S" ? $this->item->gettotalfrete() : 0) - $this->item->gettotaldesconto() + $this->item->gettotalacrescimo()) * (1 - $this->item->getredicms() / 100);
				if($this->item->getvalorpauta() > 0 && in_array($this->item->gettptribicms(), array("R","T"))){
					//$totalbaseicms = (($this->item->getquantidade() * $this->item->getvalorpauta()) + $frete - $desconto + $this->item->gettotalacrescimo() + $totalipi) * (1 - $this->item->getredicms() / 100);
					//$totalbaseicms = (($this->item->getquantidade() * $this->item->getvalorpauta()) + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
					$totalbaseicms = (($this->item->getquantidade() * $this->item->getvalorpauta()) + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
				}else{
					//$totalbaseicms = ($this->item->gettotalbruto() + $frete - $desconto + $this->item->gettotalacrescimo() + $totalipi) * (1 - $this->item->getredicms() / 100);
					//$totalbaseicms = ($this->item->gettotalbruto() + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
					$totalbaseicms = ($this->item->gettotalbruto() + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ?  ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
				}
			}
		}else{
			$totalbaseicms = 0;
		}
		return round($totalbaseicms, 2);
	}

	private function calcular_totalicms(){
		$totalicms = $this->item->gettotalbaseicms() * $this->item->getaliqicms() / 100;
		return round($totalicms, 2);
	}

	private function calcular_totalbaseicmsdesonerado(){
		if($this->natoperacao->getcalcfretebaseicms() == "S"){
			$frete = $this->item->gettotalfrete();
		}else{
			$frete = 0;
		}
		if($this->natoperacao->getcalcicmsvalorbruto() == "N"){
			$desconto = $this->item->gettotaldesconto();
		}else{
			$desconto = 0;
		}
		if($this->natoperacao->getcalcipibaseicms() == "S"){
			$totalipi = $this->item->gettotalipi();
		}else{
			$totalipi = 0;
		}
		//$totalbaseicmsdesonerado = ($this->item->gettotalbruto() + $frete - $desconto + $this->item->gettotalacrescimo() + $totalipi) * (1 - $this->item->getredicms() / 100);
		//$totalbaseicmsdesonerado = ($this->item->gettotalbruto() + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
		//$totalbaseicmsdesonerado = ($this->item->gettotalbruto() + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
		$totalbaseicmsdesonerado = ($this->item->gettotalbruto() + $frete + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
		return round($totalbaseicmsdesonerado, 2);
	}

	private function calcular_valoricmsdesonerado(){
		$icmsdesonerado = $this->item->getaliqicmsdesoneracao();
		$baseicmsdesonerado = $this->calcular_totalbaseicmsdesonerado();
		$totalicmsdesoneracao = $this->calcular_totalbaseicmsdesonerado() * $this->item->getaliqicmsdesoneracao() / 100;
		return round($totalicmsdesoneracao, 2);
	}

	private function calcular_totalbaseicmssubst($arecolher = FALSE){
		if($this->item->gettptribicms() != "F" || ($this->item->getaliqicms() == 0 && $this->item->getaliqicmsdesoneracao() == 0)){
		//if($this->item->gettptribicms() != "F"){
			$totalbaseicmssubst = 0;
		}else{
			if(in_array(substr($this->natoperacao->getnatoperacao(),2,3), ["929"])){
				$totalbaseicmssubst = 0;
			}else{
				$redsaida = $this->item->getredicms();
				if((in_array($redsaida, array(10.49, 33.33, 33.333, 33.3333, 52)) || $this->natoperacao->getredbcsticmsprop() == "C") && !(floor($redsaida) == 33 && $this->natoperacao->getredbcsticmsprop() == "A")){
					$redsaida = 0;
				}
				if($this->item->getvalorpauta() > 0){
					$totalbaseicmssubst = $this->item->getvalorpauta() * $this->item->getquantidade();
				}else{
					if($arecolher){
						$iva = number_format(($this->operacaonota->gettipo() == "E" ? $this->classfiscalnfe->getaliqiva() : $this->classfiscalnfs->getaliqiva()), 2, ".", "");
					}else{
						$iva = number_format($this->item->getaliqiva(), 2, ".", "");
					}
					if($iva > 0 || $this->estado_destino->getuf() != $this->estado_origem->getuf()){
						if((floor($redsaida) == 33 || $this->natoperacao->getredbcsticmsprop() == "C") && $this->natoperacao->getredbcsticmsprop() != "A"){
							$redsaida = 0;
						}

						if($this->natoperacao->getcalculastvalorbruto() == "N"){
							$desconto = number_format($this->item->gettotaldesconto(), 2, ".", "");
						}else{
							$desconto = 0;
						}
						if($this->natoperacao->getcalcfretebaseicms() == "S"){
							$totalfrete = number_format($this->item->gettotalfrete(), 2, ".", "");
						}else{
							$totalfrete = 0;
						}

						if($this->natoperacao->getcalcipibasesticms() == "S"){
							//$iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "") - $desconto + number_format($this->item->gettotalacrescimo(), 2, ".", "") + $totalfrete + number_format($this->item->gettotalipi(), 2, ".", "");
							//$iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "") - $desconto + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()), 2, ".", "") + $totalfrete + number_format($this->item->gettotalipi(), 2, ".", "");
							$iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "") - $desconto + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()), 2, ".", "") + $totalfrete + number_format($this->item->gettotalipi(), 2, ".", "");
						}else{
							//$iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "") - $desconto + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()), 2, ".", "") + $totalfrete;
							$iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "") - $desconto + number_format(($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()), 2, ".", "") + $totalfrete;
							//$iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "") - $desconto + number_format($this->item->gettotalacrescimo(), 2, ".", "") + $totalfrete;
						}

						/* if($this->natoperacao->getcalculastvalorbruto() == "N"){
						  $iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "") - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) + ($this->natoperacao->getcalcipibaseicms() == "S" ? number_format($this->item->gettotalipi(), 2, ".", "") : 0);
						  }else{
						  $iva_aux = number_format($this->item->gettotalbruto(), 2, ".", "")  + number_format($this->item->gettotalacrescimo(), 2, ".", "") + ($this->natoperacao->getcalcfretebaseicms() == "S" ? number_format($this->item->gettotalfrete(), 2, ".", "") : 0) + ($this->natoperacao->getcalcipibaseicms() == "S" ? number_format($this->item->gettotalipi(), 2, ".", "") : 0);
						  } */
						//$iva_aux = number_format($iva_aux * (1 - $redsaida / 100), 2, ".", "");
						if(isset($this->ncmestado) && $this->ncmestado->getcalculoliqmediast() != "S"){
							$iva_aux = number_format($iva_aux * (1 - $redsaida / 100), 2, ".", "") - ($this->descincondicional == "I" ? number_format($this->item->getvalordesoneracao(), 2, ".", "") : 0);
							$iva_total = number_format($iva_aux * (1 + $iva / 100), 2, ".", "");
							$totalbaseicmssubst = $iva_total;
						}else{
							$iva_aux = number_format($iva_aux * (1 - $redsaida / 100), 2, ".", "") - ($this->descincondicional == "I" ? number_format($this->item->getvalordesoneracao(), 2, ".", "") : 0);
							$iva_total = number_format($iva_aux * ($iva / 100), 2, ".", "");
							$icms_destino = $this->getaliquotaicmssaida();
							$totalbaseicmssubst = ($iva_total + $this->item->gettotalicms()) / ($icms_destino / 100);
							$this->item->settotalicmssubst($iva_total);
						}
					}else{
						$totalbaseicmssubst = 0;
					}
				}
			}
		}
		return round($totalbaseicmssubst, 2);
	}

	private function calcular_totalicmssubst($arecolher = FALSE){
		if(isset($this->ncmestado) && $this->ncmestado->getcalculoliqmediast() == "S"){
			return $this->item->gettotalicmssubst();
		}
		if($this->item->gettptribicms() == "F"){
			$icmsent = $this->item->getaliqicms();
			$icmssai = $this->getaliquotaicmssaida();

			if($this->operacaonota->getoperacao() == "CP" && $icmssai == 0 && $icmsent > 0){
				$icmsent = 0;
			}

			$custo_operacao = $this->item->gettotalbaseicms();

			// Calculo do credito de icms
			$creditoicms = number_format($custo_operacao * $icmsent / 100, 2, ".", "");

			// Calculo do debito de icms
			$debitoicms = number_format($this->item->gettotalbaseicmssubst() * $icmssai / 100, 2, ".", "");

			$valordesoneracao = number_format($this->item->getvalordesoneracao(),2, ".", "");

			// Calculo do total do icms st
			$totalicmssubst = $debitoicms - $creditoicms - $valordesoneracao;

			// Verifica se o total a recolher nao ficou negativo
			if($arecolher && $totalicmssubst < 0){
				$totalicmssubst = 0;
			}
		}else{
			$totalicmssubst = 0;
		}
		return round($totalicmssubst, 2);
	}

	private function calcular_basecalcufdest(){
		$totalbasecalcufdest = 0;
		if($this->item->getaliqicmsufdest() > 0){
			if($this->item->gettotalbaseicms() > 0){
				$totalbasecalcufdest = $this->item->gettotalbaseicms();
			}else{
				//$totalbasecalcufdest = ($this->item->gettotalbruto() + $frete - $desconto + $this->item->gettotalacrescimo() + $totalipi) * (1 - $this->item->getredicms() / 100);
				//$totalbasecalcufdest = ($this->item->gettotalbruto() + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
				$totalbasecalcufdest = ($this->item->gettotalbruto() + $frete - $desconto + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? ($this->natoperacao->getcalcicmsvalorbruto() == "S" ? $this->item->gettotalacrescimo() : 0) : $this->item->gettotalacrescimo()) + $totalipi) * (1 - $this->item->getredicms() / 100);
			}
		};
		return round($totalbasecalcufdest, 2);
	}

	private function calcular_valoricmsufdest(){
		$baseufdest = $this->item->getbasecalcufdest();
		$aliqicmsufdest = $this->item->getaliqicmsufdest();
		$aliqinter = $this->item->getaliqicmsinter();
		$aliqicmspart = $this->item->getaliqicminterpart();
		$basepropria = $this->item->getbasecalcufdest() * $this->item->getaliqicmsufdest() / 100;
		$basedebito = $this->item->getbasecalcufdest() * $this->item->getaliqicmsinter() / 100;
		$icmsdifal = $basepropria - $basedebito;
		$valoricmsufdest = $icmsdifal * $this->item->getaliqicminterpart() / 100;
		return round($valoricmsufdest, 2);
	}

	private function calcular_valoricmsufremet(){
		$basepropria = $this->item->getbasecalcufdest() * $this->item->getaliqicmsufdest() / 100;
		$basedebito = $this->item->getbasecalcufdest() * $this->item->getaliqicmsinter() / 100;
		$icmsdifal = $basepropria - $basedebito;
		$valoricmsufdest = $icmsdifal * (1 - $this->item->getaliqicminterpart() / 100);
		return round($valoricmsufdest, 2);
	}

	private function calcular_basefcpufdest(){
		$basecalculofcp = 0;
		if($this->item->getaliqfcpufdest() > 0){
			$basecalculofcp = $this->item->gettotalbaseicms();
		}
		return $basecalculofcp;
	}

	private function calcular_valorfcpufdest(){
		//$valorfcpufdest = $this->item->getbasecalcufdest() * $this->item->getaliqfcpufdest() / 100;
		//$valorfcpufdest = $this->item->gettotalbaseicms() * $this->item->getaliqfcpufdest() / 100;
		$valorfcpufdest = $this->item->getvalorbcfcpufdest() * $this->item->getaliqfcpufdest() / 100;
		return round($valorfcpufdest, 2);
	}

	private function calcular_basecalculofcpst(){
		$basefcpufdestst = 0;
		if($this->item->getpercfcpst() > 0){
			$basefcpufdestst = $this->item->gettotalbaseicmssubst();
		}
		return $basefcpufdestst;
	}

	private function calcular_valorfcpst(){
		$valorfcpufdestst = 0;
		if($this->item->getpercfcpst() > 0){
			$valorfcpufdestst = $this->item->getbasecalculofcpst() * $this->item->getpercfcpst() / 100;
		}
		return round($valorfcpufdestst,2);
	}

	private function calcular_valorinss(){
		$totalbaseinss = (number_format($this->item->gettotalbruto(), 2, ".", "") - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", ""));
		$aliquotainss = $this->item->getaliquotainss();
		$valortotalinss = $totalbaseinss * $aliquotainss / 100;
		return round($valortotalinss, 2);
	}

	private function calcular_valorir(){
		$totalbaseir = (number_format($this->item->gettotalbruto(), 2, ".", "") - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", ""));
		$aliquotair = $this->item->getaliquotair();
		$valortotalir = $totalbaseir * $aliquotair / 100;
		return round($valortotalir, 2);
	}

	private function calcular_valorcsll(){
		$totalbasecsll = (number_format($this->item->gettotalbruto(), 2, ".", "") - number_format($this->item->gettotaldesconto(), 2, ".", "") + number_format($this->item->gettotalacrescimo(), 2, ".", ""));
		$aliquotacsll = $this->item->getaliquotacsll();
		$valortotalcsll = $totalbasecsll * $aliquotacsll / 100;
		return round($valortotalcsll, 2);
	}

	private function calcular_totalgnre(){
		// Verifica se deve calcular na operacao atual
		if(!in_array($this->operacaonota->getoperacao(), array("VD", "CP"))){
			$totalgnre = 0;

			// Verifica se o estado origem e destino foram preenchidos
		}elseif(!is_object($this->estado_origem) || !is_object($this->estado_destino)){
			$totalgnre = 0;

			// Verifica se o parceiro eh de outro estado e se o estado dele tem convenio de ICMS
		}elseif($this->estado_origem->getuf() == $this->estado_destino->getuf() || (($this->operacaonota->gettipo() == "E" && $this->estado_origem->getconvenioicms() == "S") || ($this->operacaonota->gettipo() == "S" && $this->estado_destino->getconvenioicms() == "S"))){
			$totalgnre = 0;

			// Verifica se o parceiro e pessoa juridica e contribuinte de ICMS
		}elseif($this->parceiro->gettppessoa() == "F" || ($this->parceiro->gettablename() == "cliente" && $this->parceiro->getcontribuinteicms() == "N")){
			$totalgnre = 0;

			// Verifica a tributacao do produto
		}elseif($this->parceiro->gettppessoa() == "F" && $this->item->gettptribicms() == "F" && !$this->parceiro->getmodosubsttrib() == "1"){
			$totalgnre = 0;
		}elseif($this->parceiro->gettppessoa() == "C" && $this->item->gettptribicms() == "F"){
			$totalgnre = 0;
		}else{

			// Carrega os objetos necessarios
			$produto = $this->carregar_objeto("produto", $this->item->getcodproduto());

			// Procura a tributacao do produto
			$tributacaoproduto = new TributacaoProduto($this->con);
			$tributacaoproduto->setguiagnre(TRUE);
			$tributacaoproduto->setoperacaonota($this->operacaonota);
			$tributacaoproduto->setestabelecimento($this->estabelecimento);
			$tributacaoproduto->setnatoperacao($this->natoperacao);
			$tributacaoproduto->setparceiro($this->parceiro);
			$tributacaoproduto->setproduto($produto);
			$tributacaoproduto->buscar_dados();

			// Cria um novo item e preenche os dados necessarios para calcular os total
			$item = clone $this->item;
			$item->setnatoperacao($tributacaoproduto->getnatoperacao());
			$item->settptribicms($tributacaoproduto->gettptribicms());
			$item->setaliqicms($tributacaoproduto->getaliqicms());
			$item->setaliqiva($tributacaoproduto->getaliqiva());
			$item->setvalorpauta($tributacaoproduto->getvalorpauta());
			$item->settipoipi($tributacaoproduto->gettipoipi());
			$item->setpercipi($tributacaoproduto->getpercipi());
			$item->setvalipi($tributacaoproduto->getvalipi());
			$item->setpercdescto($tributacaoproduto->getpercdescto());
			$item->setvaldescto($tributacaoproduto->getvaldescto());
			$item->setpercacresc($tributacaoproduto->getpercacresc());
			$item->setvalacresc($tributacaoproduto->getvalacresc());
			$item->setpercfrete($tributacaoproduto->getpercfrete());
			$item->setvalfrete($tributacaoproduto->getvalfrete());
			$item->setpercseguro($tributacaoproduto->getpercseguro());
			$item->setvalseguro($tributacaoproduto->getvalseguro());

			// Calcula os totais do item
			$itemcalculo = clone $this;
			$itemcalculo->setitem($item);
			$itemcalculo->setguiagnre(TRUE);
			$itemcalculo->calcular();

			// Retorna o valor do ICMS ST para a Guia GNRE
			$totalgnre = $item->gettotalicmssubst();
		}
		return round($totalgnre, 2);
	}

	public function calcular_totalliquido(){
		if($this->item->gettablename() == "itorcamento"){ // A tabela de orcamento nao calcula impostos
			$totalliquido = $this->item->gettotalbruto() - $this->item->gettotaldesconto() + $this->item->gettotalacrescimo() + $this->item->gettotalfrete();
			//$totalliquido = $this->item->gettotalbruto() - $this->item->gettotaldesconto() + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()) + $this->item->gettotalfrete();
		}else{
			if(is_object($this->natoperacao) && $this->natoperacao->gettotnfigualbcicms() == "S"){
				$totalliquido = $this->item->gettotalbaseicms();
			}else{
				// Calcula o total liquido (abaixo existe complementos no calculo)
				$totalliquido = $this->item->gettotalbruto() - $this->item->gettotaldesconto() - $this->item->getvalordesoneracao() + $this->item->gettotalacrescimo() + $this->item->gettotalseguro() + $this->item->gettotalipi() + $this->item->getvalsiscomex() + $this->item->getvalorafrmm() + $this->item->gettotalicmssubst() + $this->item->getvalorfcpst();
				//$totalliquido = $this->item->gettotalbruto() - $this->item->gettotaldesconto() - $this->item->getvalordesoneracao() + ($this->natoperacao->getacrescimocomooutrasdespesas() == "S" ? 0 : $this->item->gettotalacrescimo()) + $this->item->gettotalseguro() + $this->item->gettotalipi() + $this->item->getvalsiscomex() + $this->item->getvalorafrmm() + $this->item->gettotalicmssubst() + $this->item->getvalorfcpst();
				// Quando o frete for por conta do destinatario deve somar o valor do frete no valor liquido dos itens
				//if($this->modfrete == "1"){
				$totalliquido += $this->item->gettotalfrete();
				/*
				  if($this->estabelecimento->getregimetributario() == "1"){
				  $totalliquido += ($this->item->getvaloricmsufdest() + $this->item->getvalorfcpufdest());
				  }
				 *
				 */
				//}
				// Verifica se deve somar o ICMS no total da nota fiscal
				if($this->natoperacao->getsumicmstotalnf() == "S"){
					$totalliquido += $this->item->gettotalicms();
				}
				// Verifica se deve somar o II no total da nota fiscal
				if($this->natoperacao->getoperacao() == "IM" && $this->natoperacao->gettotnfigualbcicms() == "N" && $this->natoperacao->getsumicmstotalnf() == "N"){
					$totalliquido += $this->item->gettotalii();
				}
			}
		}
		return ($this->notafiscal_pedidotrunca == "T" ? trunc($totalliquido, 2) : round($totalliquido, 2));
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

	function getaliquotaicmssaida(){
		$icmssai = 0;
		//$ncmestado = $this->carregar_objeto("ncmestado", array($this->produto->getidncm(), $this->estado_destino->getuf()));

		$uforig = $this->estado_origem->getuf();
		$ufdest = $this->estado_destino->getuf();
		$tipo = $this->operacaonota->gettipo();
		$conv = $this->estado_origem->getconvenioicms();
		$gnre = $this->guiagnre;
		if(is_object($this->estado_origem) && is_object($this->estado_destino) && $this->estado_origem->getuf() != $this->estado_destino->getuf() && (($this->operacaonota->gettipo() == "E" && $this->estado_origem->getconvenioicms() == "S") || ($this->operacaonota->gettipo() == "S" && ($this->estado_destino->getconvenioicms() == "S" || $this->ncmestado->getconvenioicms() == "S")) || $this->guiagnre)){
//			Porque São Paulo ver com Hugo? OS 2929 if(in_array($this->operacaonota->getoperacao(), array("CP", "DF")) && $this->estado_destino->getuf() == "SP"){
			if(in_array($this->operacaonota->getoperacao(), array("CP")) && $this->estado_destino->getuf() == "SP"){
				$icmssai = $this->classfiscalnfe->getaliqicms();
			}elseif(in_array($this->operacaonota->getoperacao(), array("DC"))){
				$icmssai = $this->estado_origem->getaliqicms();
			}elseif(in_array($this->operacaonota->getoperacao(), array("DF"))){
				$icmssai = $this->classfiscalnfe->getaliqicms();
			}else{
				if(!$gnre){
					$icmssai = $this->ncmestado->getaliqinterna();
					if($icmssai == 0 || is_Null($icmssai)){
						$icmssai = $this->estado_destino->getaliqicms();
					}
				}else{
					$icmssai = $this->classfiscalnfe->getaliqicms();
					if($icmssai == 0 || is_Null($icmssai)){
						$icmssai = $this->estado_destino->getaliqicms();
					}
				}
				$icmssai += $this->ncmestado->getaliqfcp();
			}
		}else{
			$icmssai = ($this->classfiscalnfs->getaliqicms() == 0 ? $this->item->getaliqicms() : $this->classfiscalnfs->getaliqicms());
		}
		return $icmssai;
	}

	function getitem(){
		return $this->item;
	}

	function setclassfiscalnfe($classfiscal){
		$this->classfiscalnfe = $classfiscal;
	}

	function setclassfiscalnfs($classfiscal){
		$this->classfiscalnfs = $classfiscal;
	}

	function setcodestabelec($codestabelec){
		$estabelecimento = $this->carregar_objeto("estabelecimento", $codestabelec);
		$this->setestabelecimento($estabelecimento);
	}

	function setconnection(Connection $con){
		$this->con = $con;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = $estabelecimento;
		$this->buscar_estado();
	}

	function setguiagnre($guiagnre){
		if(is_bool($guiagnre)){
			$this->guiagnre = $guiagnre;
		}
	}

	function setitem(Cadastro $item, Produto $produto = null){
		$this->item = $item;
		if(is_null($produto)){
			$produto = $this->carregar_objeto("produto", $this->item->getcodproduto());
		}
		$this->produto = $produto;
		$this->classfiscalnfe = NULL;
		$this->classfiscalnfs = NULL;
		if(!isset($this->ncmestado)){
			$this->ncmestado = $this->carregar_objeto("ncmestado", array($this->produto->getidncm(), $this->estado_destino->getuf()));
		}
	}

	function setmodfrete($modfrete){
		$this->modfrete = $modfrete;
	}

	function setnatoperacao($natoperacao){
		if(is_string($natoperacao)){
			$natoperacao = $this->carregar_objeto("natoperacao", $natoperacao);
		}
		$this->natoperacao = $natoperacao;
	}

	function setoperacao($operacao){
		$operacaonota = $this->carregar_objeto("operacaonota", $operacao);
		$this->setoperacaonota($operacaonota);
	}

	function setoperacaonota($operacaonota){
		$this->operacaonota = $operacaonota;
		$this->buscar_estado();
	}

	function setparceiro($parceiro){
		$this->parceiro = $parceiro;
		$this->buscar_estado();
		if(is_object($this->operacaonota) && $this->operacaonota->getparceiro() == "F"){
			$this->descincondicional = $this->parceiro->gettipodescdesoneracao();
		}else{
			$this->descincondicional = "";
		}
	}

}
