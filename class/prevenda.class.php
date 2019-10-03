<?php

class PreVenda{

	private $con;
	private $orcamento;
	private $arr_itorcamento;
	private $estabelecimento;
	private $cliente;
	private $cidade_cliente;
	private $codprevenda;
	private $numpedido;

	function __construct($con){
		$this->con = $con;
	}

	function setorcamento($orcamento){
		$this->orcamento = $orcamento;
		$this->estabelecimento = objectbytable("estabelecimento", $orcamento->getcodestabelec(), $this->con);
		$this->cliente = objectbytable("cliente", $this->orcamento->getcodcliente(), $this->con);
		$this->cidade_cliente = objectbytable("cidade", $this->cliente->getcodcidaderes(), $this->con);
		$itorcamento = objectbytable("itorcamento", NULL, $this->con);
		$itorcamento->setcodorcamento($this->orcamento->getcodorcamento());
		$this->arr_itorcamento = object_array($itorcamento);

		$res = $this->con->query("SELECT numpedido FROM pedido WHERE codorcamento = ".$orcamento->getcodorcamento());
		$this->codprevenda = $res->fetchColumn();
	//	if(strlen($this->codprevenda) == 0){
			$this->codprevenda = $orcamento->getcodorcamento();
	//	}
	}

	function setnumpedido($numpedido){
		$this->numpedido($numpedido);
	}

	function gerar_arquivo(){
		if(!is_object($this->orcamento)){
			$_SESSION["ERROR"] = "Informe um or&ccedil;amento v&aacute;lido para a gera&ccedil;&atilde;o de arquivo da pr&eacute;-venda.";
			return FALSE;
		}
		switch($this->estabelecimento->getcodfrentecaixa()){
			case 1: // GZ Sistemas
				return $this->gerar_arquivo_gzsistemas();
			case 2: // Coral
				return $this->gerar_arquivo_coral();
			case 4: // SysPDV
				return $this->gerar_arquivo_syspdv();
			case 11: // saurus
				return true;
			default: // Nao gera pre-venda para o PDV
				$_SESSION["ERROR"] = "N&atilde;o h&aacute; suporte de pr&eacute;-venda para o frente de caixa informado no estabelecimento.";
				return false;
		}
	}

	private function gerar_arquivo_gzsistemas(){
		$arr_linha = array();

		// Busca a finalizadora
		$finalizadora = objectbytable("finalizadora", NULL, $this->con);
		$finalizadora->setcodestabelec($this->orcamento->getcodestabelec());
		$finalizadora->setcodespecie($this->orcamento->getcodespecie());
		if(param("PONTOVENDA", "VERIFICAFINALIZ") == "S"){
			$finalizadora->setcodcondpagto($this->orcamento->getcodcondpagto());
		}
		$arr_finalizadora = object_array($finalizadora);
		if(sizeof($arr_finalizadora) == 0){
			$_SESSION["ERROR"] = "Finalizadora n&atilde;o encontrada para a forma e condi&ccedil;&atilde;o de pagamento.<br><a onclick=\"openProgram('CadFinalizadora')\">Clique aqui</a> para abrir o cadastro de finalizadoras.";
			return FALSE;
		}
		$finalizadora = $arr_finalizadora[0];

		// Cria as linhas para o arquivo
		$seqitem = 1;
		foreach($this->arr_itorcamento as $itorcamento){
			$linha = str_pad($this->orcamento->getcodorcamento(), 8, "0", STR_PAD_LEFT); // Numero da pre-venda
			$linha .= str_pad($seqitem++, 3, "0", STR_PAD_LEFT); // Sequencial do item
			$linha .= str_pad($itorcamento->getcodproduto(), 20, "0", STR_PAD_LEFT); // Codigo do produto
			$linha .= str_pad(number_format($itorcamento->getquantidade() * $itorcamento->getqtdeunidade(), 3, "", ""), 8, "0", STR_PAD_LEFT); // Quantidade
			$linha .= str_pad(number_format($itorcamento->getpreco(), 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco unitario
			$linha .= str_pad($this->orcamento->getcodfunc(), 4, "0", STR_PAD_LEFT); // Codigo do vendedor
			$linha .= str_pad("", 3, "0", STR_PAD_LEFT); // Codigo da empresa (quando convenio)
			$linha .= str_pad($this->orcamento->getcodcliente(), 16, "0", STR_PAD_LEFT); // Codigo do cliente
			$linha .= str_pad($finalizadora->getcodfinaliz(), 3, "0", STR_PAD_LEFT); // Codigo da modalidade de pagamento
			$arr_linha[] = $linha;
		}

		// Desconto/abatimento
		if($this->orcamento->gettotaldesconto() > 0){
			$linha = str_pad($this->orcamento->getcodorcamento(), 8, "0", STR_PAD_LEFT); // Numero da pre-venda
			$linha .= str_pad($seqitem++, 3, "0", STR_PAD_LEFT); // Sequencial do item
			$linha .= str_pad("ABATIMENTO", 20, " ", STR_PAD_RIGHT); // Codigo do produto
			$linha .= str_pad(1, 8, "0", STR_PAD_LEFT); // Quantidade
			$linha .= str_pad(number_format($this->orcamento->gettotaldesconto(), 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco unitario
			$linha .= str_pad($this->orcamento->getcodfunc(), 4, "0", STR_PAD_LEFT); // Codigo do vendedor
			$linha .= str_pad("", 3, "0", STR_PAD_LEFT); // Codigo da empresa (quando convenio)
			$linha .= str_pad($this->orcamento->getcodcliente(), 16, "0", STR_PAD_LEFT); // Codigo do cliente
			$linha .= str_pad($finalizadora->getcodfinaliz(), 3, "0", STR_PAD_LEFT); // Codigo da modalidade de pagamento
			$arr_linha[] = $linha;
		}

		// Cria arquivo
		if(!$this->criar_arquivo($this->orcamento->getcodorcamento().".txt", $arr_linha)){
			return FALSE;
		}

		return TRUE;
	}

	private function gerar_arquivo_coral(){
		$arr_linha = array();

		// Cria as linhas para o arquivo
		foreach($this->arr_itorcamento as $itorcamento){
			$query = "SELECT codean FROM produtoean WHERE codproduto = ".$itorcamento->getcodproduto()." LIMIT 1 ";
			echo $query;
			$res = $this->con->query($query);
			$codean = $res->fetchColumn();

			$linha = str_pad($codean, 13, "0", STR_PAD_LEFT); // Codigo do ean
			$linha .= str_pad(number_format($itorcamento->getquantidade() * $itorcamento->getqtdeunidade(), 3, ",", ""), 10, "0", STR_PAD_LEFT); // Quantidade
			$arr_linha[] = $linha;
		}

		// Cria arquivo
		if(!$this->criar_arquivo("CR".str_pad($this->orcamento->getcodorcamento(), 6, "0", STR_PAD_LEFT).".txt", $arr_linha)){
			return FALSE;
		}

		return TRUE;
	}

	private function gerar_arquivo_syspdv(){
		$cidade = objectbytable("cidade", $this->cliente->getcodcidaderes(), $this->con);

		$aux_totalliquido = 0;
		/*foreach($this->arr_itorcamento as $itorcamento){
			$aux_totalliquido += ($itorcamento->getpreco() * $itorcamento->getquantidade()) - $itorcamento->gettotaldesconto();
		}*/
		$this->orcamento->settotalliquido($aux_totalliquido);

		$arr_linha_rpx = array();


		// Itens da pre-venda
		foreach($this->arr_itorcamento as $itorcamento){
			$produto = objectbytable("produto", $itorcamento->getcodproduto(), $this->con);
			$ncm = objectbytable("ncm", $produto->getidncm(), $this->con);
			$classfiscal = objectbytable("classfiscal", $produto->getcodcfpdv(), $this->con);
			$icmspdv = objectbytable("icmspdv", array($this->estabelecimento->getcodestabelec(), $classfiscal->gettptribicms(), $classfiscal->getaliqicms(), $classfiscal->getaliqredicms()), $this->con);
			$numeroserie = objectbytable("numeroserie", NULL, $this->con);
			$numeroserie->setcodorcamento($this->orcamento->getcodorcamento());
			$numeroserie->setcodproduto($itorcamento->getcodproduto());
			$arr_numeroserie = object_array($numeroserie);

			if(sizeof($arr_numeroserie) > 0){
				foreach($arr_numeroserie as $numeroserie){
					$linha = "02"; // Tipo de registro (texto fixo contendo "02")
					$linha .= str_pad($this->codprevenda, 9, "0", STR_PAD_LEFT); // Numero da pre-venda
					$linha .= str_pad($itorcamento->getcodproduto(), 14, "0", STR_PAD_LEFT); // Codigo do produto
					$linha .= str_pad(substr($produto->getdescricao(), 0, 45), 45, " ", STR_PAD_RIGHT); // Descricao completa do produto
					$linha .= str_pad(substr($produto->getdescricao(), 0, 20), 20, " ", STR_PAD_RIGHT); // Descricao reduzida do produto
					$linha .= str_pad(number_format(1, 3, ".", ""), 15, "0", STR_PAD_LEFT); // Quantidade
					$linha .= str_pad(number_format($itorcamento->getpreco(), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Preco unitario
					$linha .= str_pad(number_format($itorcamento->gettotaldesconto(), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Total dos descontos
					$linha .= str_pad($icmspdv->getinfpdv(), 3, " ", STR_PAD_RIGHT); // Tributacao
					$linha .= str_pad("NS: ".$numeroserie->getnumeroserie(), 70, " ", STR_PAD_RIGHT); // Complemento da descricao do produto
					$linha .= str_repeat(" ", 255); // Observacao
					$linha .= "S"; // Alterar produto no cadastro
					$arr_linha_rpx[] = $linha;
				}
			}else{
				//$aux_totalliquido += round($itorcamento->gettotalliquido() / $itorcamento->getquantidade() ,2) * $itorcamento->getquantidade();
				$aux_totalliquido += round($itorcamento->gettotalliquido() / $itorcamento->getquantidade() ,3) * $itorcamento->getquantidade();

				$linha = "02"; // Tipo de registro (texto fixo contendo "02")
				$linha .= str_pad($this->codprevenda, 9, "0", STR_PAD_LEFT); // Numero da pre-venda
				$linha .= str_pad($itorcamento->getcodproduto(), 14, "0", STR_PAD_LEFT); // Codigo do produto
				$linha .= str_pad(substr($produto->getdescricaofiscal(), 0, 45), 45, " ", STR_PAD_RIGHT); // Descricao completa do produto
				$linha .= str_pad(substr($produto->getdescricao(), 0, 20), 20, " ", STR_PAD_RIGHT); // Descricao reduzida do produto
				$linha .= str_pad(number_format($itorcamento->getquantidade() * $itorcamento->getqtdeunidade(), 3, ".", ""), 15, "0", STR_PAD_LEFT); // Quantidade
				//$linha .= str_pad(number_format(round($itorcamento->gettotalliquido() / $itorcamento->getquantidade() / $itorcamento->getqtdeunidade(), 2), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Preco unitario
				$linha .= str_pad(number_format(round($itorcamento->gettotalliquido() / $itorcamento->getquantidade() / $itorcamento->getqtdeunidade(), 3), 3, ".", ""), 15, "0", STR_PAD_LEFT); // Preco unitario
				$linha .= str_pad(number_format(0, 2, ".", ""), 15, "0", STR_PAD_LEFT); // Total dos descontos
				$linha .= str_pad($icmspdv->getinfpdv(), 3, " ", STR_PAD_RIGHT); // Tributacao
				$linha .= str_repeat(" ", 70); // Complemento da descricao do produto
				$totaldesconto = number_format($itorcamento->gettotaldesconto(),2,",","");
				$codproduto = $itorcamento->getcodproduto();
				$linha .= str_pad("Produto: {$codproduto}, Desconto: {$totaldesconto}  ", 255, " ",STR_PAD_RIGHT); // Observacao
				$linha .= "N"; // Alterar produto no cadastro
				$linha .= str_pad($this->codprevenda, 10, "0", STR_PAD_LEFT); // Numero da pre-venda
				$linha .= str_repeat(" ", 20); // Codigo auxiliar
				$linha .= str_repeat(" ", 3); // Codigo auxiliar
				$linha .= str_pad(str_replace(".", "", $ncm->getcodigoncm()), 8, "0", STR_PAD_LEFT); // Numero da pre-venda
				$linha .= str_repeat(" ", 27)."0"; // Tabela A

				$arr_linha_rpx[] = $linha;
			}
		}
		// Cabecalho da pre-venda
		$linha = "01"; // Tipo de registro (texto fixo contente "01")
		$linha .= str_pad($this->codprevenda, 9, "0", STR_PAD_LEFT); // Numero da pre-venda
		$linha .= str_pad($this->orcamento->getcodcliente(), 15, "0", STR_PAD_LEFT); // Codigo do cliente
		$linha .= str_pad(str_replace("/", "", $this->orcamento->getdtemissao(TRUE)), 8, "0", STR_PAD_LEFT); // Data de emissao
		$linha .= str_pad(substr(str_replace(":", "", $this->orcamento->gethremissao(TRUE)), 0, 4), 4, "0", STR_PAD_LEFT); // Hora de emissao
		$linha .= str_pad($this->orcamento->getcodfunc(), 4, "0", STR_PAD_LEFT); // Codigo do funcionario
		$linha .= str_pad(number_format(round($aux_totalliquido,2), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor da pre-venda
		$linha .= str_repeat(" ", 60); // Observacao
		$linha .= str_pad(number_format($this->orcamento->gettotaldesconto(), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor dos descontos
		$linha .= str_pad(substr($this->cliente->getnome(), 0, 40), 40, " ", STR_PAD_RIGHT); // Nome do cliente
		$linha .= str_pad(substr($this->cliente->getenderres(), 0, 45), 45, " ", STR_PAD_RIGHT); // Endereco do cliente
		$linha .= str_pad(substr($this->cliente->getbairrores(), 0, 15), 15, " ", STR_PAD_RIGHT); // Bairro do cliente
		$linha .= str_pad(substr($this->cidade_cliente->getnome(), 0, 20), 20, " ", STR_PAD_RIGHT); // Cidade do cliente
		$linha .= str_pad(substr($this->cidade_cliente->getuf(), 0, 2), 2, " ", STR_PAD_RIGHT); // Estado do cliente
		$linha .= str_pad(substr($this->cliente->getnumerores(), 0, 6), 6, " ", STR_PAD_RIGHT); // Numero do endereco do cliente
		$linha .= str_pad(substr($this->cliente->getcomplementores(), 0, 15), 15, " ", STR_PAD_RIGHT); // Complemento do endereco do cliente

		$linha .= str_pad("", 6, " ", STR_PAD_RIGHT); // Códido do funcionário
		$linha .= str_pad("", 10, " ", STR_PAD_RIGHT); // Número da prevenda/pedido
		$linha .= str_pad("", 15, " ", STR_PAD_RIGHT); // Nome do vendedor
		$linha .= str_pad("", 12, " ", STR_PAD_RIGHT); // Telefone
		$linha .= str_pad($this->cliente->gettppessoa(), 1, " ", STR_PAD_RIGHT); // Tipo de Pessoa
		$linha .= str_pad("", 20, " ", STR_PAD_LEFT); // Inscrição Estadual
		$linha .= str_pad("01058", 5, " ", STR_PAD_RIGHT); // Código do Pais
		$linha .= str_pad($cidade->getcodoficial(), 7, "0", STR_PAD_LEFT); // Código do IBGE
		$linha .= str_pad("", 8, " ", STR_PAD_RIGHT); // CEP

		$linha .= str_pad(removeformat($this->cliente->getcpfcnpj()), 14, " ", STR_PAD_LEFT); // CPF/CNPJ

		array_unshift($arr_linha_rpx, $linha);

		// Finalizadora
		$finalizadora = objectbytable("finalizadora", NULL, $this->con);
		$finalizadora->setcodestabelec($this->orcamento->getcodestabelec());
		$finalizadora->setcodespecie($this->orcamento->getcodespecie());
		if(param("PONTOVENDA", "VERIFICAFINALIZ") == "S"){
			$finalizadora->setcodcondpagto($this->orcamento->getcodcondpagto());
		}

		$arr_finalizadora = object_array($finalizadora);
		if(sizeof($arr_finalizadora) == 0){
			$_SESSION["ERROR"] = "Finalizadora n&atilde;o encontrada para a forma e condi&ccedil;&atilde;o de pagamento.<br><a onclick=\"openProgram('CadFinalizadora')\">Clique aqui</a> para abrir o cadastro de finalizadoras.";
			return FALSE;
		}
		$finalizadora = $arr_finalizadora[0];
		$condpagto = objectbytable("condpagto", $this->orcamento->getcodcondpagto(), $this->con);
		$n_parcelas = 0;
		if($condpagto->getpercdia1() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia2() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia3() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia4() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia5() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia6() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia7() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia8() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia9() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia10() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia11() > 0){
			$n_parcelas++;
		}
		if($condpagto->getpercdia12() > 0){
			$n_parcelas++;
		}
		$linha = "03"; // Tipo de registro (texto fixo contendo "03")
		$linha .= str_pad(substr($finalizadora->getcodfinaliz(), 0, 3), 3, "0", STR_PAD_LEFT); // Codigo da finalizadora
		$linha .= str_pad($this->codprevenda, 9, "0", STR_PAD_LEFT); // Numero da pre-venda
		$linha .= str_pad(number_format(0, 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor do troco
		$linha .= str_pad(number_format(round($aux_totalliquido,2), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor da finalizacao
		$linha .= "01"; // Codigo do plano de pagamento
		$linha .= str_pad(number_format(round($aux_totalliquido,2), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor da entrada
		$linha .= str_pad(number_format(round($aux_totalliquido,2), 2, ".", ""), 15, "0", STR_PAD_LEFT); // Valor da principal
		$linha .= str_pad(substr($finalizadora->getcodfinaliz(), 0, 3), 3, "0", STR_PAD_LEFT); // Codigo da finalizadora em que foi dado a entrada
		$linha .= str_pad($n_parcelas, 3, "0", STR_PAD_LEFT); // Numero parcelas finalizadora
		$arr_linha_rpx[] = $linha;

		// Cria arquivo de venda
		var_dump($arr_linha_rpx);
		if(!$this->criar_arquivo("RPX".str_pad($this->codprevenda, 7, "0", STR_PAD_LEFT).".ECF", $arr_linha_rpx)){
			return false;
		}

		// Cria arquivo de finalizadora
		/* 		if(!$this->criar_arquivo("PRX".str_pad($this->orcamento->getcodorcamento(),7,"0",STR_PAD_LEFT).".ECF",$arr_linha_prx)){
		  return FALSE;
		  }
		 */
		return true;
	}

	private function criar_arquivo($nome_arquivo, $arr_linha){
		$arr_linha[] = "";
		$dir = $this->estabelecimento->getdirpdvexp();

		if(strlen($dir) == 0){
			$_SESSION["ERROR"] = "Diret&oacute;rio de integr&ccedil;&atilde;o com o frente de caixa n&atilde;o foi informado.<br><a onclick=\"openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro do estabelecimento.";
			return false;
		}

		$nome_arquivo = $dir."/".$nome_arquivo;

		write_file($nome_arquivo, $arr_linha, param("SISTEMA", "TIPOSERVIDOR", $this->con) == 0);

		$parampontovenda = objectbytable("parampontovenda", $this->estabelecimento->getcodestabelec(), $this->con);
		if($parampontovenda->gettiposervidor() === "N"){
			$controleprevenda = objectbytable("controleprevenda", null, $this->con);
			$controleprevenda->setcodestabelec($this->estabelecimento->getcodestabelec());
			$controleprevenda->setnomearquivo($nome_arquivo);
			$controleprevenda->setconteudo(implode("\r\n", $arr_linha));
			if(!$controleprevenda->save()){
				return false;
			}
		}

		return true;
	}

}
