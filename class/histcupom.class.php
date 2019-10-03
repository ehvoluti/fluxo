<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class HistCupom extends Cadastro{
	private $parampontovenda;
	private $orcamento;
	private $pedido;
	private $pontovenda;

	function __construct($codcupom = NULL){
		parent::__construct();
		$this->table = "histcupom";
		$this->primarykey = array("codcupom");
		$this->setcodcupom($codcupom);
		if(!is_null($this->getcodcupom())){
			$this->searchbyobject();
		}
	}

    function setparampontovenda($parampontovenda){
        $this->parampontovenda = $parampontovenda;
	}

	function setpontovenda($pontovenda){
		$this->pontovenda = $pontovenda;
	}

	function setorcamento($orcamento){
		$this->orcamento = $orcamento;
	}

	function setpontovena($pontovenda){
		$this->pontovena = $pontovena;
	}

	function setpedido($pedido){
		$this->pedido = $pedido;
	}

	function histcupom(){
        $arr_operacao = array("VD" => "VENDA", "DC" => "DEVOLUCAO");
        $condpagto = NULL;
        $formapagto = NULL;
		$arr_lancamento = array();
		if(is_object($this->orcamento)){
			$codfunc = $this->orcamento->getcodfunc();
			$natoperacao = $this->orcamento->getnatoperacao();
			$codparceiro = $this->orcamento->getcodcliente();
			$codestabelec = $this->orcamento->getcodestabelec();
			$codorcamento = $this->orcamento->getcodorcamento();
            $observacao = $this->orcamento->getobservacao();
            $numpedido = $codorcamento;
            $operacao = "ORCAMENTO:";
            $this->parampontovenda = objectbytable("parampontovenda", $codestabelec, $this->con);

            if($this->parampontovenda->getcondpagto() == "S"){
                $condpagto = objectbytable("condpagto", $this->orcamento->getcodcondpagto(), $this->con);
            }

            if($this->parampontovenda->getformapagto() == "S"){
                $formapagto = objectbytable("especie", $this->orcamento->getcodespecie(), $this->con);
            }

            $textovias = $this->parampontovenda->gettextoviasorc();
			$itorcamento = objectbytable("itorcamento", null, $this->con);
			$itorcamento->setcodorcamento($codorcamento);
			$arr_itorcamento = object_array($itorcamento);

			$itens = array();
			foreach($arr_itorcamento as $itorcamento){
				$produto = objectbytable("produto", $itorcamento->getcodproduto(), $this->con);

				$itens[]= array(
					"codproduto" => $itorcamento->getcodproduto(),
					"totalitem" => $itorcamento->gettotalliquido(),
					"quantidade" => $itorcamento->getquantidade(),
					"preco" => $itorcamento->getpreco(),
					"descricao" => $produto->getdescricao(),
					"entregaretira" => ""
				);
			}

		}elseif(is_object($this->pedido)){
			$codfunc = $this->pedido->getcodfunc();
			$natoperacao = $this->pedido->getnatoperacao();
			$codparceiro = $this->pedido->getcodparceiro();
			$codestabelec = $this->pedido->getcodestabelec();
			$numpedido = $this->pedido->getnumpedido();
            $observacao = $this->pedido->getobservacao();
            $operacao = "PEDIDO - {$arr_operacao[$this->pedido->getoperacao()]}:";

            $this->parampontovenda = objectbytable("parampontovenda", $codestabelec, $this->con);

            if($this->parampontovenda->getcondpagto() == "S"){
                $condpagto = objectbytable("condpagto", $this->pedido->getcodcondpagto(), $this->con);
            }

            if($this->parampontovenda->getformapagto() == "S"){
                $formapagto = objectbytable("especie", $this->pedido->getcodespecie(), $this->con);
            }
            $textovias = $this->parampontovenda->gettextovias();
			$itpedido = objectbytable("itpedido", null, $this->con);
			$itpedido->setnumpedido($numpedido);
			$itpedido->setcodestabelec($codestabelec);
			$itpedido->setorder("entregaretira DESC", "seqitem");
			$arr_itpedido = object_array($itpedido);

			$itens = array();
			foreach($arr_itpedido AS $itpedido){
				$produto = objectbytable("produto", $itpedido->getcodproduto(), $this->con);

				$itens[]= array(
					"codproduto" => $itpedido->getcodproduto(),
					"totalitem" => $itpedido->gettotalliquido(),
					"quantidade" => $itpedido->getquantidade(),
					"preco" => $itpedido->getpreco(),
					"descricao" => $produto->getdescricao(),
					"entregaretira" => $itpedido->getentregaretira()
				);
			}

			$lancamento = objectbytable("lancamento", NULL, $this->con);
			$lancamento->setcodestabelec($this->pedido->getcodestabelec());
			$lancamento->setnumpedido($this->pedido->getnumpedido());
			$lancamento->setorder("parcela");
			$arr_lancamento = object_array($lancamento);

			// Carrega as formas de pagamento
			$arr_codespecie = array();
			foreach($arr_lancamento as $lancamento){
				$arr_codespecie[] = $lancamento->getcodespecie();
			}
			$arr_especie = object_array_key(objectbytable("especie", NULL, $this->con), $arr_codespecie);

		}elseif(is_object($this->pontovenda)){
			$codfunc = $this->pontovenda->getcodfunc();
			$natoperacao = $this->pontovenda->getnatoperacao();
			$codparceiro = $this->pontovenda->getcliente();
			$codestabelec = $this->pontovenda->getcodestabelec();
            $observacao = $this->pontovenda->getobservacao();
			$operacao = "PONTO VENDA:";
            $textovias = $this->parampontovenda->gettextovias();
			$itpontovenda = objectbytable("itpontovenda", null, $this->con);
			$itpontovenda->setlogin($this->pontovenda->getlogin());

			$arr_itpontovenda = object_array($itpontovenda);

			$itens = array();
			foreach($arr_itpontovenda AS $itpontovenda){
				$itens[]= array(
					"codproduto" => $itpontovenda->getcodproduto(),
					"totalitem" => $itpontovenda->gettotalitem(),
					"quantidade" => $itpontovenda->getquantidade(),
					"preco" => $itpontovenda->getpreco(),
					"descricao" => $itpontovenda->getdescricao(),
					"entregaretira" => ""
				);
			}
            $this->parampontovenda = objectbytable("parampontovenda", $codestabelec, $this->con);
		}

		$funcionario = objectbytable("funcionario", $codfunc, $this->con);
		$natoperacao = objectbytable("natoperacao", $natoperacao, $this->con);
		$cliente = objectbytable("cliente", $codparceiro, $this->con);
		$estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);

		$tiporecibo = $this->parampontovenda->gettiporecibo();
		if($tiporecibo == 1){
			$aux_espaco = 4;
        }else if($tiporecibo == 2){
            $aux_espaco = 8;
		}else{
			$aux_espaco = 0;
		}
		$arr_linha = array();
		$arr_linha[] = str_repeat("-", 39 + $aux_espaco);
		$arr_linha[] = str_pad($this->parampontovenda->gettituloimpresso(), 39 + $aux_espaco, " ", STR_PAD_BOTH);
		$arr_linha[] = str_repeat("-", 39 + $aux_espaco);
		$arr_linha[] = "";
		if($this->parampontovenda->getimpestabelecimento() == "S"){
			$arr_linha[] = substr($estabelecimento->getrazaosocial(), 0, 39 + $aux_espaco);
		}
		$arr_linha[] = date("d/m/Y").str_repeat(" ", 21 + $aux_espaco).date("H:i:s");
		$arr_linha[] = "";
		if($this->parampontovenda->getimprimedocumento() == "S"){
			$arr_linha[] = $identificacao_cupom;
		}

		//$arr_linha[] = "VENDEDOR: ".str_pad($funcionario->getcodfunc(), 6, "0", STR_PAD_LEFT)." ".substr($funcionario->getnome(), 0, 23);
		$arr_linha[] = "VENDEDOR: ".str_pad($funcionario->getcodfunc(), 6, "0", STR_PAD_LEFT)." ".substr($funcionario->getnome(), 0, 23 + $aux_espaco);
		if($this->parampontovenda->getimpusuariologado() == "S"){
			$arr_linha[] = "USUARIO LOG: ".$_SESSION["WUser"];
		}
		if(strlen($numpedido) > 0){
			//$arr_linha[] = "PEDIDO: ".str_pad($numpedido, 6, "0", STR_PAD_LEFT);
			$arr_linha[] = $operacao.str_pad($numpedido, 6, "0", STR_PAD_LEFT);
		}
		if($natoperacao->getgerafiscal() == "N" || $this->parampontovenda->getimpnomecliente() == "S"){
			//$arr_linha[] = "CLIENTE: ".str_pad($cliente->getcodcliente(), 6, "0", STR_PAD_LEFT)." ".substr($cliente->getnome(), 0, 23);
			$arr_linha[] = "CLIENTE: ".str_pad($cliente->getcodcliente(), 6, "0", STR_PAD_LEFT)." ".substr($cliente->getnome(), 0, 23 + $aux_espaco);
		}

		if($this->parampontovenda->getimpcpfcnpj() == "S"){
			$arr_linha[] = "CPF/CNPJ: ".$cliente->getcpfcnpj();
		}

		if($this->parampontovenda->getimptelefonecli() == "S"){
			$arr_linha[] = "TELEFONE CLI: ".$cliente->getfoneres();
		}

		if($this->parampontovenda->getimpendereco() == "S"){
			$cidade = objectbytable("cidade", $cliente->getcodcidadeent(), $this->con);
			$endereco = "ENDERECO: ".$cliente->getenderent().", ".$cliente->getnumeroent()." - ".$cliente->getbairroent()." - ".$cidade->getnome()." - ".$cidade->getuf().", ".$cliente->getcomplementoent();
			//$arr_endereco = str_split(utf8_encode($endereco), 39);
			$arr_endereco = str_split(utf8_encode($endereco), 39 + $aux_espaco);
			$arr_linha = array_merge($arr_linha, $arr_endereco);
		}

        if(isset($condpagto)){
            $arr_linha[] = substr("COND. PAGTO: {$condpagto->getdescricao()}", 0, 39 + $aux_espaco);
        }

        if(isset($formapagto)){
            $arr_linha[] = substr("FORMA PAGTO: {$formapagto->getdescricao()}", 0, 39 + $aux_espaco);
        }

		$arr_linha[] = "";
		$arr_linha[] = str_repeat(" ", 17)."ITENS";
		$totalvenda = 0;
		$totaldesconto = 0;

		$param_ordemitem = $this->parampontovenda->getordemitem() == "S";

		$totalbruto = 0;
		$print_secao_retira = FALSE;
		$print_secao_entrega = FALSE;
		foreach($itens as $i => $item){
			if($param_ordemitem){
				$contador = ($i + 1).". ";
				$contador = str_pad($contador, 4,"0",STR_PAD_LEFT);
			}

			$totalitem = $item["totalitem"];

			$itdescto = ($item["quantidade"] * $item["preco"]) - $totalitem;
			$totaldesconto += $itdescto;
			$totalvenda += $item["totalitem"];
			$totalbruto += $item["quantidade"] * $item["preco"];

			$totalitem = number_format($totalitem, 2, ",", ".");

			if($this->parampontovenda->getdadosfornec() == "S"){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($item["codproduto"]);
				$arr_prodfornec = object_array($prodfornec);
				if(sizeof($arr_prodfornec) > 0){
					$prodfornec = reset($arr_prodfornec);
					$reffornec = $prodfornec->getreffornec();
					$fornecedor = objectbytable("fornecedor",$prodfornec->getcodfornec(), $this->con);
					$fornecedor_nome = $fornecedor->getnome();
				}else{
					$reffornec = " ";
					$fornecedor_nome = " ";
				}
			}

			if($item["entregaretira"] == "R" && !$print_secao_retira){
				$arr_linha[] = "RETIRA";
				$arr_linha[] = str_repeat("-", 39 + $aux_espaco);
				$print_secao_retira = TRUE;
			}

			if($item["entregaretira"] == "E" && !$print_secao_entrega){
				$arr_linha[] = "ENTREGA";
				$arr_linha[] = str_repeat("-", 39 + $aux_espaco);
				$print_secao_entrega = TRUE;
			}

			if($tiporecibo == 1){
				$linha = $contador.str_pad(str_pad($item["codproduto"], 6, "0", STR_PAD_LEFT)." ".substr(removespecial($item["descricao"]), 0, 10), 18, " ");
				$linha .= str_repeat(" ", 20 - (strlen($linha) + strlen($totalitem)));
				$linha .= str_pad(number_format($item["preco"], 2, ",", "."), 6, " ", STR_PAD_LEFT)." ";
				$linha .= str_pad(number_format($item["quantidade"], 2, ",", "."), 6, " ", STR_PAD_LEFT);
				$linha .= " ".str_pad(number_format($item["totalitem"], 2, ",", "."), 7, " ", STR_PAD_LEFT);
				$arr_linha[] = $linha;
			}else{
				//$arr_linha[] = substr(($contador.str_pad($item["codproduto"], 6, "0", STR_PAD_LEFT)." ".removespecial($item["descricao"])), 0, 39);
				$arr_linha[] = substr(($contador.str_pad($item["codproduto"], 6, "0", STR_PAD_LEFT)." ".removespecial($item["descricao"])), 0, 39 + $aux_espaco);
				$linha = number_format($item["preco"], 2, ",", ".")." x ".number_format($item["quantidade"], 4, ",", ".");
				$linha .= " - ".number_format($itdescto, 2, ",", ".")." ";
				//$linha .= str_repeat(" ", 39 - (strlen($linha) + strlen($totalitem))).$totalitem;
				$linha .= str_repeat(" ", (39 + $aux_espaco) - (strlen($linha) + strlen($totalitem))).$totalitem;
				$arr_linha[] = $linha;
				if($this->parampontovenda->getdadosfornec() == "S"){
					$arr_linha[] = "Fornecedor: ".$fornecedor_nome;
					$arr_linha[] = "Referencia: ".$reffornec;
					$arr_linha[] = " ";
				}

			}
		}

		$subtotalvenda = $totalvenda + $totaldesconto;
		$arr_linha[] = str_repeat("-", 39 + $aux_espaco);
		if($totaldesconto > 0){
			$subtotalvenda = number_format($subtotalvenda, 2, ",", ".");
			$totaldesconto = number_format($totaldesconto, 2, ",", ".");
			$totalbruto = number_format($totalbruto, 2, ",", ".");
			$arr_linha[] = "SUBTOTAL".str_repeat(" ", 31 + $aux_espaco + - strlen($subtotalvenda)).$subtotalvenda;
			if(sizeof($arr_descto) > 2){
				foreach($arr_descto AS $descto){
					if($descto > 0){
						$arr_linha[] = "DESCONTO".str_repeat(" ", 30 + $aux_espaco - strlen($descto)).$descto."%";
					}
				}
			}else{
				$desc_perc = round(((value_numeric($totaldesconto) / value_numeric($subtotalvenda)) * 100),2);
				$desc_perc = number_format($desc_perc,2,",","");
				$texto_desc = "($desc_perc%) $totaldesconto";
				$arr_linha[] = "DESCONTO".str_repeat(" ", 31 + $aux_espaco - strlen($texto_desc)).$texto_desc;
			}
		}
		$totalvenda = number_format($totalvenda, 2, ",", ".");
		$arr_linha[] = "TOTAL".str_repeat(" ", 34 + $aux_espaco - strlen($totalvenda)).$totalvenda;
		if($dinheiro > 0){
			$arr_linha[] = "DINHEIRO".str_repeat(" ", 31 + $aux_espaco - strlen($dinheiro)).$dinheiro;
			$troco = value_numeric($dinheiro) - value_numeric($totalvenda);
			$troco = number_format($troco, 2, ",", "");
			$arr_linha[] = "TROCO".str_repeat(" ", 34 + $aux_espaco - strlen($troco)).$troco;
		}

		if($this->parampontovenda->getparcelafin() == "S" && count($arr_lancamento) > 0){
			$arr_linha[] = "";
			$arr_linha[] = "FINANCEIRO";
			$arr_linha[] = str_repeat("-", 39 + $aux_espaco);
			foreach($arr_lancamento as $lancamento){
				$especie = $arr_especie[$lancamento->getcodespecie()];
				$arr_linha[] = str_pad($lancamento->getparcela(),2, "0", STR_PAD_LEFT)." ".str_pad($lancamento->getdtvencto(TRUE),10, " ", STR_PAD_BOTH).str_pad(number_format($lancamento->getvalorparcela(), 2, ",", "."),23 + $aux_espaco, " ", STR_PAD_LEFT)." ".$especie->getespecie();
			}
		}

        if($this->parampontovenda->getobservacaopedorc() == "S"){
            for($i = 0; $i < 4; $i++){
                $arr_linha[] = " ";
            }
            $arr_linha[] = "Observacao";
            $arr_linha[] = str_repeat("-", 39 + $aux_espaco);
            $observacao = wordwrap($observacao,39 + $aux_espaco,"<br>");
            $arr__observacao = explode("<br>",$observacao);
            for($j = 0; $j < count($arr__observacao); $j++){
                $obs = $arr__observacao[$j];
                $arr_linha[] = $obs;
            }
        }

		for($i = 0; $i < $this->parampontovenda->getsaltofinal(); $i++){
			$arr_linha[] = " ";
		}
		$arr_linha[] = str_repeat("-\r\n");

		$texto_completo = implode("\r\n", $arr_linha)."\r\n";

		if(is_numeric($textovias)){
			//for($i = 0; $i < $this->parampontovenda->gettextovias(); $i++){
			for($i = 0; $i < $textovias; $i++){
				$texto_completo .= $texto_completo.escpos_guilhotina();
			}
		}else{
			//$arr_textovias = explode(";",$this->parampontovenda->gettextovias());
			$arr_textovias = explode(";",$textovias);
			$aux_texto_completo = $texto_completo;
			$texto_completo = "";
			foreach($arr_textovias AS $i => $textovias){
				$textovias = str_pad(trim($textovias), 40, " ", STR_PAD_BOTH)."\r\n";
				$texto_completo .= $textovias.$aux_texto_completo.escpos_guilhotina();
			}
		}

		//$histcupom = objectbytable("histcupom", NULL, $this->con);
		$this->setcodestabelec($codestabelec);
		$this->setcodvendedor($codfunc);
		$this->setcodcliente($codparceiro);
		$this->setcupom($texto_completo."\r\n");
		$this->setlogin($_SESSION["WUser"]);
		$this->setimpresso("S");
		if(!$this->save()){
			return false;
		}
		return true;

	}

	function imprimir(){
		// Verifica se o parametro ja foi informado
		if(!is_object($this->parampontovenda)){
			$this->parampontovenda = objectbytable("parampontovenda",$this->getcodestabelec(),$this->con);
		}

		// Verifica se deve imprimir cupom nao fiscal
		if(strlen($this->parampontovenda->getlocalimpressora()) > 0){
			echo write_file($this->parampontovenda->getlocalimpressora()."cupom.txt",$this->getcupom(),$this->parampontovenda->gettiposervidor() != "N");
			// Impressao pelo WebSac Desktop
			$this->setdtmovto(date("Y-m-d"));
			$this->sethrmovto(date("H:i:s"));
			$this->setimpresso("N");
			return $this->save();
		}else{
			$_SESSION["ERROR"] = "Local da impressora não foi definido.";
			return false;
		}
	}

	function getcodcupom(){
		return $this->fields["codcupom"];
	}

	function getcodestabelec(){
		return $this->fields["codestabelec"];
	}

	function getcodvendedor(){
		return $this->fields["codvendedor"];
	}

	function getcodcliente(){
		return $this->fields["codcliente"];
	}

	function getdtmovto($format = FALSE){
		return ($format ? convert_date($this->fields["dtmovto"],"Y-m-d","d/m/Y") : $this->fields["dtmovto"]);
	}

	function gethrmovto(){
		return substr($this->fields["hrmovto"],0,8);
	}

	function getcupom(){
		return $this->fields["cupom"];
	}

	function getimpresso(){
		return $this->fields["impresso"];
	}

	function getlogin(){
		return $this->fields["login"];
	}

    function setcodcupom($value){
		$this->fields["codcupom"] = value_numeric($value);
	}

	function setcodestabelec($value){
		$this->fields["codestabelec"] = value_numeric($value);
	}

	function setcodvendedor($value){
		$this->fields["codvendedor"] = value_numeric($value);
	}

	function setcodcliente($value){
		$this->fields["codcliente"] = value_numeric($value);
	}

	function setdtmovto($value){
		$this->fields["dtmovto"] = value_date($value);
	}

	function sethrmovto($value){
		$this->fields["hrmovto"] = value_time($value);
	}

	function setcupom($value){
		$this->fields["cupom"] = value_string($value);
	}

	function setimpresso($value){
		$this->fields["impresso"] = value_string($value);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value, 20);
	}
 }
