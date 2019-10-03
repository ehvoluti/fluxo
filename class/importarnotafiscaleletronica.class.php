<?php

class ImportarNotaFiscalEletronica{

	private $con;
	private $xml;
	private $error;
	private $filename;
	private $tipoqtdenfe;
	private $retorno;
	private $estabelecimento;
	private $fornecedor;
	private $estado_estabelecimento;
	private $estado_fornecedor;
	private $arr_prodfornec;
	private $arr_produtoean;
	private $arr_embalagem;
	private $arr_natoperacao;
	private $arr_classfiscal;
	private $arr_piscofins;
	private $arr_piscofins_saida;
	private $arr_produto_encontrado;
	private $arr_produto_naoencontrado;
	private $param_notafiscal_atuareffornecxml;
	private $param_notafiscal_csticmsorig;
	private $param_notafiscal_autogravarprodxml;
	private $param_notafiscal_importxmlsemcnpj;
	private $param_notafiscal_ordemimportacaoxml;
	private $icms_interestadual = array("N" => array("N" => 12.00, "S" => 12.00), "S" => array("N" => 7.00, "S" => 12.00));

	function __construct(Connection $con){
		$this->con = $con;
		$this->retorno = array();

		// Carrega os parametros necessarios
		$this->param_notafiscal_atuareffornecxml = param("NOTAFISCAL", "ATUAREFFORNECXML", $this->con);
		$this->param_notafiscal_csticmsorig = param("NOTAFISCAL", "CSTICMSORIG", $this->con);
		$this->param_notafiscal_autogravarprodxml = param("NOTAFISCAL", "AUTOGRAVARPRODXML", $this->con);
		$this->param_notafiscal_importxmlsemcnpj = param("NOTAFISCAL", "IMPORTXMLSEMCNPJ", $this->con);
		$this->param_notafiscal_ordemimportacaoxml = param("NOTAFISCAL", "ORDEMIMPORTACAOXML", $this->con);
	}

	private function cadastrar_produto($infoproduto){
		// Verifica se o parametro que controla esse processo esta ativo
		$autogravarprodxml = explode("|", $this->param_notafiscal_autogravarprodxml);
		if($autogravarprodxml[0] === "S"){
			// Verifica se o parametro esta preenchido da forma correta
			if(count($autogravarprodxml) < 12){
				$this->error = "O parâmetro AUTOGRAVARPRODXML está incompleto, impossibilitando o cadastro de novos produtos de forma automática.<br><a onclick=\"openProgram('Parametro')\">Clique aqui</a> para abrir o parâmetro e configurar corretamente.";
				return false;
			}

			// Inicia a transacao no banco de dados
			$this->con->start_transaction();
			if(strlen($infoproduto["ncm"]) == 8){
				$ncm = objectbytable("ncm", NULL, $this->con);
				$codigoncm = substr($infoproduto["ncm"], 0,4).".".substr($infoproduto["ncm"], 4,2).".".substr($infoproduto["ncm"], 6,2);
				$ncm->setcodigoncm($codigoncm);
				$arr_ncm = object_array($ncm);
				$ncm = NULL;
				if(count($arr_ncm) > 0){
					$ncm = array_shift($arr_ncm);
				}
				if(is_null($ncm)){
					$this->con->start_transaction();
					$ncm = objectbytable("ncm", NULL, $this->con);
					$ncm->setcodcfnfe($autogravarprodxml[6]);
					$ncm->setcodcfnfs($autogravarprodxml[7]);
					$ncm->setcodcfpdv($autogravarprodxml[8]);
					$ncm->setcodigoncm($codigoncm);
					$ncm->setcodipi($autogravarprodxml[9]);
					$ncm->setcodpiscofinsent($autogravarprodxml[4]);
					$ncm->setcodpiscofinssai($autogravarprodxml[5]);
					$ncm->setdescricao($codigoncm);
					if(!$ncm->save()){
						$this->error = $_SESSION["ERROR"];
						return FALSE;
					}
					$this->con->commit();
				}
			}
			// Cria o cadastro principal do produto
			$produto = objectbytable("produto", NULL, $this->con);
			$produto->setdescricao($infoproduto["descricao"]);
			$produto->setdescricaofiscal($infoproduto["descricao"]);
			//$produto->setprecovrj($infoproduto["preco"]);
			//$produto->setprecoatc($infoproduto["preco"]);
			$produto->setcoddepto($autogravarprodxml[1]);
			$produto->setcodgrupo($autogravarprodxml[2]);
			$produto->setcodsubgrupo($autogravarprodxml[3]);
			$produto->setcodpiscofinsent($autogravarprodxml[4]);
			$produto->setcodpiscofinssai($autogravarprodxml[5]);
			$produto->setcodcfnfe($autogravarprodxml[6]);
			$produto->setcodcfnfs($autogravarprodxml[7]);
			$produto->setcodcfpdv($autogravarprodxml[8]);
			$produto->setcodipi($autogravarprodxml[9]);
			$produto->setcodembalcpa($autogravarprodxml[10]);
			$produto->setcodembalvda($autogravarprodxml[11]);
			if(!is_null($ncm)){
				$produto->setidncm($ncm->getidncm());
			}else{
				$produto->setidncm($autogravarprodxml[12]);
			}
			$produto->setforalinha("N");
			$produto->setprecovariavel("N");
			$produto->setpesado("N");
			$produto->setvasilhame("N");
			$produto->setflutuante("N");
			$produto->setatualizancm("N");
			$produto->setcontrnumeroserie("N");
			$produto->setcontrnumerolote("N");
			$produto->setalcoolico("N");
			if(!$produto->save()){
				$this->error = $_SESSION["ERROR"];
				$this->con->rollback();
				return false;
			}

			// Cria o EAN para o produto
			$produtoean = objectbytable("produtoean", NULL, $this->con);
			$produtoean->setcodproduto($produto->getcodproduto());
			if(strlen($infoproduto["codean"]) === 0){
				$infoproduto["codean"] = plutoean13($produto->getcodproduto());
			}
			$produtoean->setcodean($infoproduto["codean"]);
			if(!$produtoean->save()){
				$this->error = $_SESSION["ERROR"];
				$this->con->rollback();
				return false;
			}

			// Cria a relacao entre o produto e fornecedor
			if(strlen($infoproduto["reffornec"]) > 0){
				$prodfornec = objectbytable("prodfornec", NULL, $this->con);
				$prodfornec->setcodproduto($produto->getcodproduto());
				$prodfornec->setcodfornec($this->fornecedor->getcodfornec());
				$prodfornec->setreffornec($infoproduto["reffornec"]);
				if(!$prodfornec->save()){
					$this->error = $_SESSION["ERROR"];
					$this->con->rollback();
					return false;
				}
			}

			// Inclui o produto na lista de encontrados
			$this->arr_produto_encontrado[] = array(
				"codproduto" => $produto->getcodproduto(),
				"xml" => $infoproduto["xml"]
			);

			// Finaliza o processo com sucesso
			$this->con->commit();
			return true;
		}else{
			$this->error = "O parâmetro AUTOGRAVARPRODXML está desabilitado.";
			return false;
		}
	}

	private function capturar_natoperacao_contrapartida($produto, $tptribicms){
		// Verifica se o CFOP do XML esta cadastrado
		if(strlen($this->fornecedor->getnatoperacao()) > 0){
			$cfop = $this->fornecedor->getnatoperacao();
			$natoperacao = $this->arr_natoperacao[$cfop];

			if($tptribicms == "F"){
				$natoperacao = $this->arr_natoperacao[$natoperacao["natoperacaosubst"]];
			}
			return $natoperacao;
		}

		$cfop = removeformat(trim((string) $produto["xml"]->prod->CFOP));
		$cfop = substr($cfop, 0, 1).".".substr($cfop, 1);
		$found = false;

		foreach($this->arr_natoperacao as $i => $natoperacao){
			if($natoperacao["natoperacao"] == $cfop){
				$found = true;
				break;
			}
		}
		if(!$found){
			$this->error = "A natureza de operação <b>{$cfop}</b> não foi encontrada.<br><a onclick=\"$.messageBox('close'); openProgram('NatOper')\">Clique aqui</a> para abrir o cadastro de naturezas de operação.";
			return false;
		}

		// Verifica se existe a natureza contra-partida
		if(strlen($natoperacao["natoperacaocp"]) === 0){
			$this->error = "Natureza de operação contra-partida não informada para o CFOP <b>{$natoperacao["natoperacao"]}</b>.<br><a onclick=\"$.messageBox('close'); openProgram('NatOper','natoperacao={$natoperacao["natoperacao"]}'); $.messageBox('close')\">Clique aqui</a> para abrir o cadastro de naturezas de operação.";
			return false;
		}

		// Localiza a natureza de operacao contra-partida
		$natoperacao = $this->arr_natoperacao[$natoperacao["natoperacaocp"]];

		// Retorna a natureza de operacao contra partida
		return $natoperacao;
	}

	private function capturar_itnotafiscal($produto){
		// Quantidade do produto
		$quantidade = (float) $produto["xml"]->prod->qCom;

		// Joga o ICMS (objeto) do item em uma variavel ($icms)
		foreach($produto["xml"]->imposto->ICMS as $icms2){
			foreach($icms2 as $icms){
				break;
			}
		}

		// Joga o IPI (objeto) do item em uma variavel
		$ipi = $produto["xml"]->imposto->IPI->IPITrib;

		// Desconto
		if(strlen($produto["xml"]->prod->vDesc) > 0){
			$totaldescto = (float) $produto["xml"]->prod->vDesc;
			$valdescto = $totaldescto / $quantidade;
		}else{
			$totaldescto = 0;
			$valdescto = 0;
		}

		// Acrescimo
		if(strlen($produto["xml"]->prod->vOutro) > 0){
			$totalacresc = (float) $produto["xml"]->prod->vOutro;
			$valacresc = $totalacresc / $quantidade;
		}else{
			$totalacresc = 0;
			$valacresc = 0;
		}

		// Frete
		if(strlen($produto["xml"]->prod->vFrete) > 0){
			$totalfrete = (float) $produto["xml"]->prod->vFrete;
			$valfrete = $totalfrete / $quantidade;
		}else{
			$totalfrete = 0;
			$valfrete = 0;
		}

		// Seguro
		if(strlen($produto["xml"]->prod->vSeg) > 0){
			$totalseguro = (float) $produto["xml"]->prod->vSeg;
			$valseguro = $totalseguro / $quantidade;
		}else{
			$totalseguro = 0;
			$valseguro = 0;
		}

		// Verifica se o fornecedor esta enquadrado no simples nacional
		if(strlen($icms->CSOSN) > 0){
			$aliqicms = (float) (strlen($icms->pICMS) > 0 ? $icms->pICMS : (strlen($icms->pICMSST) > 0 ? $icms->pICMSST : 0));
			$totalbaseicms = ($aliqicms > 0 ? (float) $produto["xml"]->prod->vProd : 0);
			$totalicms = $totalbaseicms * ($aliqicms / 100);

			// Tipo de tributacao do ICMS do item
			switch($icms->CSOSN){
				// Tributada pelo Simples Nacional com permissao de credito
				case "101":
					$tptribicms = "T";
					break;
				// Tributada pelo Simples Nacional sem permissao de credito
				case "102":
					$tptribicms = "N";
					break;
				case "103":
					$tptribicms = "N";
					break;
				// Tributada pelo Simples Nacional com permissao de credito e com cobranca do ICMS por substituicao tributario
				case "201":
					$tptribicms = "F";
					break;
				// Tributada pelo Simples Nacional sem permissao de credito e com cobranca do ICMS por substituicao tributario
				case "202":
					$tptribicms = "F";
					break;
				case "203":
					$tptribicms = "F";
					break;
				case "300":
					$tptribicms = "N";
					break;
				// Nao tributada pelo Simples Nacional
				case "400":
					$tptribicms = "N";
					break;
				// ICMS cobrado anteriormente por substituicao tributario (substituido) ou por antecipacao
				case "500":
					$tptribicms = "F";
					break;
				case "900":
					$tptribicms = "N";
					break;
			}
		}else{
			$aliqicms = (float) (strlen($icms->pICMS) > 0 ? $icms->pICMS : 0);
			$totalbaseicms = (float) (strlen($icms->vBC) > 0 ? $icms->vBC : 0);
			$totalicms = (float) (strlen($icms->vICMS) > 0 ? $icms->vICMS : 0);
			/*if(is_null($this->getfornecedor()->gettipodescdesoneracao()) || strlen($this->getfornecedor()->gettipodescdesoneracao()) == 0){
				$valordesoneracao = 0;
			}else{*/
			$valordesoneracao = (float) (strlen($icms->vICMSDeson) > 0 ? $icms->vICMSDeson : 0);
			$motivodesoneracao = (string) (strlen($icms->motDesICMS) > 0 ? $icms->motDesICMS : 0);
			if($valordesoneracao > 0){
				$uf_emit = (string) $this->retorno["infNFe"]->emit->enderEmit->UF;
				$uf_dest = (string) $this->retorno["infNFe"]->dest->enderDest->UF;
				if( $uf_emit !=  $uf_dest){
					$aliqdesoneracao = $this->icms_interestadual[$this->estado_estabelecimento->getregiao()][$this->estado_fornecedor->getregiao()];
				}
			}
			//}
			// Tipo de tributacao do ICMS do item
			switch($icms->CST){
				// Tributada integralmente
				case "00": $tptribicms = "T";
					break;
				// Tributada e com cobranca do ICMS por substituicao tributaria
				case "10": $tptribicms = "F";
					break;
				// Com reducao de base de calculo
				case "20": $tptribicms = "R";
					break;
				// Isenta ou nao tributada e com cobraca do ICMS por substituicao tributaria
				case "30": $tptribicms = "F";
					break;
				// Isenta
				case "40": $tptribicms = "I";
					break;
				// Nao tributada
				case "41": $tptribicms = "I";
					break;
				// Suspensao
				case "50": $tptribicms = "N";
					break;
				// Diferimento (A exigencia do preenchimento das informacoes do ICMS diferido fica a criterio de cada UF)
				case "51": $tptribicms = "N";
					break;
				// ICMS cobrado anteriormente por substituicao
				case "60": $tptribicms = "F";
					break;
				// Com reducao de base de calculo e cobranca do ICMS por substituicao tributaria
				case "70": $tptribicms = "F";
					break;
				// Outros
				case "90": $tptribicms = "N";
					break;
			}
		}

		// ICMS ST
		$totalbaseicmssubst = (float) (strlen($icms->vBCST) > 0 ? $icms->vBCST : 0);
		$totalicmssubst = (float) (strlen($icms->vICMSST) > 0 ? $icms->vICMSST : 0);
		$basecalculofcpst =  (float) (strlen($icms->vBCFCPST) > 0 ? $icms->vBCFCPST : 0);
		$valorfcpst = (float) (strlen($icms->vFCPST) > 0 ? $icms->vFCPST : 0);
		$percfcpst = (float) (strlen($icms->pFCPST) > 0 ? $icms->pFCPST : 0);

		if($icms->CSOSN == 101){
			$aliqicms = (float) $icms->pCredSN;
			$totalicms = (float) $icms->vCredICMSSN;
			$totalbaseicms = (float) $produto["xml"]->prod->vProd;
		}

		$redicms = importarnotafiscal_xml_reducao($icms);

		// Captura a natureza de operacao correta do produto (contra-partida)
		$natoperacao = $this->capturar_natoperacao_contrapartida($produto, $tptribicms);
		if($natoperacao === false){
			return false;
		}

		if($natoperacao["piscofdesconto"] == "S"){
			$aliqpiscofins = (float)$this->arr_piscofins_saida[$produto["codproduto"]]["aliqpis"] + (float)$this->arr_piscofins_saida[$produto["codproduto"]]["aliqcofins"];
			$totaldescto += ((float) $produto["xml"]->prod->vProd - $totaldescto) * $aliqpiscofins / 100;
		}

		// Verifica se foi informado a reducao corretamente
		if($tptribicms == "R" && in_array((int) $redicms, array(0, 100))){
			$redicms = (1 - ($icms->vBC / $produto["xml"]->prod->vProd)) * 100;
			if($redicms === 100){
				$redicms = 0;
			}
		}

		// CST de ICMS
		if($this->param_notafiscal_csticmsorig === "S"){
			$csticms = (strlen($icms->orig) > 0 ? $icms->orig : "0").$icms->CST;
		}else{
			$csticms = "0".$icms->CST;
		}

		// IVA e valor de pauta
		if(in_array($icms->CST, array("10", "30", "70")) || in_array($icms->CSOSN, array("201", "202", "500"))){
			if(strlen($icms->pMVAST) > 0 && floatval($icms->pMVAST) > 0){
				$aliqiva = (float) $icms->pMVAST;
				$valorpauta = 0;
			}elseif(in_array($icms->modBCST, array("0", "1", "4"))){
				if($redicms === 100){
					die(messagebox("error", "", "O valor da redu&ccedil;&atilde;o n&atilde;o pode ser de 100%."));
				}elseif((float) $icms->pRedBC === 61.11){
					$aliqiva = number_format((($icms->vBCST / ($icms->vBC + $ipi->vIPI)) - 1) * 100, 4);
				}elseif(floatval($icms->vBC) > 0 && floatval($icms->vBC) < floatval($icms->vBCST)){
					if($redicms == 33.33){
						$aliqiva = number_format((($icms->vBCST / (($produto["xml"]->prod->vProd - $totaldescto + $totalacresc + $totalseguro) + $ipi->vIPI)) - 1) * 100, 4);
					}else{
						//$aliqiva = (($icms->vBCST / $icms->vBC) - 1) * 100;
						$aliqiva = (( (float)$icms->vBCST / (float)$icms->vBC) - 1) * 100;
					}
				}else{
					$aliqiva = 0;
				}

				$valorpauta = 0;
			}else{
				$aliqiva = 0;
				$valorpauta = 0;
			}
		}else{
			$aliqiva = 0;
			$valorpauta = 0;
		}

		// IPI
		$totalipi = (float) $ipi->vIPI;
		if(!($totalipi > 0)){ // Sem IPI
			$tipoipi = "F";
			$percipi = 0;
			$valipi = 0;
		}elseif(strlen($ipi->pIPI) > 0){ // Percentual
			$tipoipi = "P";
			$percipi = (float) $ipi->pIPI;
			$valipi = 0;
		}elseif(strlen($ipi->vUnid) > 0){ // Fixo
			$tipoipi = "F";
			$percipi = 0;
			$valipi = $totalipi / $quantidade;
		}

		$cfop = (string) $produto["xml"]->prod->CFOP;
		$bonificado = (substr($cfop, -3) === "910" ? "S" : "N");
		$totalbruto = (float) $produto["xml"]->prod->vProd;
		//$totalliquido = $totalbruto - $valordesoneracao - $totaldescto + $totalacresc + $totalseguro + $totalfrete + $totalipi + $totalicmssubst + $valorfcpst;

		$totalliquido = $totalbruto - (strlen($this->getfornecedor()->gettipodescdesoneracao()) > 0 ?  $valordesoneracao : 0) - $totaldescto + $totalacresc + $totalseguro + $totalfrete + $totalipi + $totalicmssubst + $valorfcpst;
		$totalbonificado = ($bonificado == "S" ? $totalliquido - $totalipi : 0);

		// PIS/Cofins
		$piscofins = $this->arr_piscofins[$produto["codproduto"]];
		if($bonificado == "N" && $piscofins["aliqpis"] > 0 && $natoperacao["gerapiscofins"] == "S"){
			$aliqpis = $piscofins["aliqpis"];
			$totalbasepis = $totalbruto + ($natoperacao["calcfretebaseicms"] == "S" ? $totalfrete : 0) + $totalacresc;
			if($natoperacao["calcicmsvalorbruto"] == "N"){
				$totalbasepis -= (strlen($produto["xml"]->prod->vDesc) > 0 ? (float)$produto["xml"]->prod->vDesc : 0);
			}
			if($natoperacao["considerardesonerbasesticmsp"] == "N"){
				$totalbasepis -= (strlen($icms->vICMSDeson) > 0 ? (float)$icms->vICMSDeson : 0);
			}
			$totalpis = $totalbasepis * ($aliqpis / 100);

			$aliqcofins = $piscofins["aliqcofins"];
			$totalbasecofins = $totalbruto + ($natoperacao["calcfretebaseicms"] == "S" ? $totalfrete : 0) + $totalacresc;
			if($natoperacao["calcicmsvalorbruto"] == "N"){
				$totalbasecofins -= (strlen($produto["xml"]->prod->vDesc) > 0 ? (float)$produto["xml"]->prod->vDesc : 0);
			}
			if($natoperacao["considerardesonerbasesticmsp"] == "N"){
				$totalbasecofins -= (strlen($icms->vICMSDeson) > 0 ? (float)$icms->vICMSDeson : 0);
			}
			$totalcofins = $totalbasecofins * ($aliqcofins / 100);
		}else{
			$aliqpis = 0;
			$totalbasepis = 0;
			$totalpis = 0;

			$aliqcofins = 0;
			$totalbasecofins = 0;
			$totalcofins = 0;
		}

		// Referencia do fornecedor
		if($this->param_notafiscal_atuareffornecxml === "S"){
			$reffornec = (string) $produto["xml"]->prod->cProd;
		}else{
			$reffornec = null;
		}

		// Quantidade unidade
		if($this->tipoqtdenfe === "E"){
			$qtdeunidade = $this->arr_embalagem[$produto["codproduto"]]["quantidade"];
		}else{
			$qtdeunidade = 1;
		}

		// Tratamento feito para compras fora do estado, onde o fornecedor envia uma tributacao
		// mas a loja precisa entrar com outra tributacao
		if($this->estado_fornecedor->getuf() !== $this->estado_estabelecimento->getuf() &&
				$this->estado_fornecedor->getconvenioicms() === "N" &&
				$tptribicms != //"F" &&
				$this->arr_classfiscal[$produto["codproduto"]]["tptribicms"] //=== "F"
		){
			//$totalbaseicms_revertido = $totalbaseicms;
			//$totalicms_revertido = $totalicms;
			$origem = (string)$icms->orig;
			if(in_array($this->arr_classfiscal[$produto["codproduto"]]["tptribicms"], array("F","I","N"))){
				$totalbaseicmsnaoaproveitavel = $totalbaseicms;
				$totalicmsnaoaproveitavel = $totalicms;
				$aliqicmsnaoaproveitavel = $aliqicms;
				$redicmsnaoaproveitavel = $redicms;
				$csticmsnaoaproveitavel = $csticms;

				$tptribicms = $this->arr_classfiscal[$produto["codproduto"]]["tptribicms"];
				$aliqicms = 0;
				$redicms = 0;
				$totalbaseicms = 0;
				$totalicms = 0;
				$aliqiva = 0;
				$valorpauta = 0;
				$totalbaseicmssubst = 0;
				$totalicmssubst = 0;
				$basecalculofcpst = 0;
				$valorfcpst = 0;
				$percfcpst = 0;
				switch($tptribicms){
					case "F": $csticms = "{$origem}60";	break;
					case "I": $csticms = "{$origem}40";	break;
					case "N": $csticms = "{$origem}41"; break;
				}
			}

			if(in_array($this->arr_classfiscal[$produto["codproduto"]]["tptribicms"], array("T","R"))){
				$csticms = "{$origem}00";
				$tptribicms = "T";
				$uf_emit = (string) $this->retorno["infNFe"]->emit->enderEmit->UF;
				$uf_dest = (string) $this->retorno["infNFe"]->dest->enderDest->UF;
				if( $uf_emit !=  $uf_dest){
					$aliqicms = $this->icms_interestadual[$this->estado_estabelecimento->getregiao()][$this->estado_fornecedor->getregiao()];
				}

				if(in_array($origem, array("1","2","6","7"))){
					$aliqicms = 4.00;
				}
				$totalbaseicms = (float) $produto["xml"]->prod->vProd;
				if($natoperacao["calcicmsvalorbruto"] == "N"){
					$totalbaseicms -= (strlen($produto["xml"]->prod->vDesc) > 0 ? (float)$produto["xml"]->prod->vDesc : 0);
				}
				if($natoperacao["considerardesonerbasesticmsp"] == "N"){
					$totalbaseicms -= (strlen($icms->vICMSDeson) > 0 ? (float)$icms->vICMSDeson : 0);
				}
				$totalicms = number_format($totalbaseicms * ($aliqicms / 100), 2);
			}

			if(strlen($natoperacao["natoperacaosubst"]) > 0){
				$natoperacao = $this->arr_natoperacao[$natoperacao["natoperacaosubst"]];
			}
		}else{
			$totalbaseicms_revertido = 0;
			$totalicms_revertido = 0;
		}

		// Retorna o item
		return array(
			"seqitem" => (int) $produto["xml"]["nItem"],
			"codproduto" => $produto["codproduto"],
			"bonificado" => $bonificado,
			"reffornec" => $reffornec,
			"quantidade" => $quantidade,
			"codunidade" => $this->arr_embalagem[$produto["codproduto"]]["codunidade"],
			"qtdeunidade" => $qtdeunidade,
			"preco" => (float) $produto["xml"]->prod->vUnCom,
			"totalbruto" => $totalbruto,
			"valdescto" => $valdescto,
			"totaldesconto" => $totaldescto,
			"valacresc" => $valacresc,
			"totalacrescimo" => $totalacresc,
			"valfrete" => $valfrete,
			"totalfrete" => $totalfrete,
			"valseguro" => $valseguro,
			"totalseguro" => $totalseguro,
			"csticms" => $csticms,
			"tptribicms" => $tptribicms,
			"aliqicms" => $aliqicms,
			"redicms" => $redicms,
			"totalbaseicms" => $totalbaseicms,
			"totalicms" => $totalicms,
			"valordesoneracao" => $valordesoneracao,
			"aliqicmsdesoneracao" => $aliqdesoneracao,
			"motivodesoneracao" => $motivodesoneracao,
			"aliqiva" => $aliqiva,
			"valorpauta" => $valorpauta,
			"totalbaseicmssubst" => $totalbaseicmssubst,
			"totalicmssubst" => $totalicmssubst,
			"tipoipi" => $tipoipi,
			"percipi" => $percipi,
			"valipi" => $valipi,
			"totalipi" => $totalipi,
			"aliqpis" => $aliqpis,
			"aliqcofins" => $aliqcofins,
			"totalbasepis" => $totalbasepis,
			"totalbasecofins" => $totalbasecofins,
			"totalpis" => $totalpis,
			"totalcofins" => $totalcofins,
			"totalliquido" => $totalliquido,
			"totalbonificado" => $totalbonificado,
			"natoperacao" => $natoperacao["natoperacao"],
			"totalbaseicms_revertido" => $totalbaseicms_revertido,
			"totalicms_revertido" => $totalicms_revertido,
			"basecalculofcpst" => $basecalculofcpst,
			"valorfcpst" => $valorfcpst,
			"percfcpst" => $percfcpst,
			"totalbaseicmsnaoaproveitavel" => $totalbaseicmsnaoaproveitavel,
			"totalicmsnaoaproveitavel" => $totalicmsnaoaproveitavel,
			"aliqicmsnaoaproveitavel" => $aliqicmsnaoaproveitavel,
			"redicmsnaoaproveitavel" => $redicmsnaoaproveitavel,
			"csticmsnaoaproveitavel" => $csticmsnaoaproveitavel,
			"det" => $produto["xml"]
		);
	}

	private function capturar_notafiscal($infNFe, $arr_itnotafiscal){
		// Bonificacao
		$bonificacao = "S";
		foreach($arr_itnotafiscal as $itnotafiscal){
			if($itnotafiscal["bonificado"] === "N"){
				$bonificacao = "N";
				break;
			}
		}

		// Data de emissao
		$dtemissao = convert_date($infNFe->ide->dEmi, "Y-m-d", "d/m/Y");
		if(strlen($dtemissao) === 0){
			$dtemissao = convert_date($infNFe->ide->dhEmi, "Y-m-d", "d/m/Y");
		}

		// Transporte
		$transportadora = objectbytable("transportadora", null, $this->con);
		$cnpj_transp = (string) $infNFe->transp->transporta->CNPJ;
		if(strlen($cnpj_transp) > 0){
			$cnpj_transp = substr($cnpj_transp, 0, 2).".".substr($cnpj_transp, 2, 3).".".substr($cnpj_transp, 5, 3)."/".substr($cnpj_transp, 8, 4)."-".substr($cnpj_transp, 12, 2);
			$transportadora->setcpfcnpj($cnpj_transp);
			$transportadora->searchbyobject();
		}
		$modfrete = (string) $infNFe->transp->modFrete;
		$transpplacavei = (string) $infNFe->transp->veicTransp->placa;
		if(strlen($transpplacavei) > 0){
			$transpplacavei = substr($transpplacavei, 0, 3)."-".substr($transpplacavei, 3, 4);
		}
		$transpufvei = (string) $infNFe->transp->veicTransp->UF;

		// Calcula os totais
		foreach($arr_itnotafiscal as $itnotafiscal){
			$totalbruto += $itnotafiscal["totalbruto"];
			$totaldesconto += $itnotafiscal["totaldesconto"];
			$totalacrescimo += $itnotafiscal["totalacrescimo"];
			$totalfrete += $itnotafiscal["totalfrete"];
			$totalseguro += $itnotafiscal["totalseguro"];
			$totalipi += $itnotafiscal["totalipi"];
			$totalbaseicms += $itnotafiscal["totalbaseicms"]; //(jesus: estava gerando base icms negativa )- $itnotafiscal["totalbaseicms_revertido"];
			$totalicms += $itnotafiscal["totalicms"]; //(Jesus: estava gerando valor icms negativo)- $itnotafiscal["totalicms_revertido"];
			$totaldesoneracao += $itnotafiscal["valordesoneracao"];
			$totalbaseicmssubst += $itnotafiscal["totalbaseicmssubst"] + $itnotafiscal["totalbaseicms_revertido"];
			$totalicmssubst += $itnotafiscal["totalicmssubst"];
			$totalbonificado += ($itnotafiscal["bonificado"] === "S" ? $itnotafiscal["totalliquido"] - $itnotafiscal["totalipi"] : 0);
			$totalliquido += $itnotafiscal["totalliquido"]; //($itnotafiscal["bonificado"] === "N" ? $itnotafiscal["totalliquido"] : 0);
			$totalbasepis += $itnotafiscal["totalbasepis"];
			$totalpis += $itnotafiscal["totalpis"];
			$totalbasecofins += $itnotafiscal["totalbasecofins"];
			$totalcofins += $itnotafiscal["totalcofins"];
			$basecalculofcpst += $itnotafiscal["basecalculofcpst"];
			$valorfcpst += $itnotafiscal["valorfcpst"];
			$totalbaseicmsnaoaproveitavel += $itnotafiscal["totalbaseicmsnaoaproveitavel"];
			$totalicmsnaoaproveitavel += $itnotafiscal["totalicmsnaoaproveitavel"];
		}

		// Monta o array com os dados da nota fiscal
		$notafiscal = array(
			"arqxmlnfe" => $this->filename,
			"chavenfe" => str_replace("NFe", "", (string) $infNFe["Id"]),
			"numnotafis" => (integer) $infNFe->ide->nNF,
			"serie" => (string) $infNFe->ide->serie,
			"bonificacao" => $bonificacao,
			"codestabelec" => $this->estabelecimento->getcodestabelec(),
			"codcondpagto" => $this->fornecedor->getcodcondpagto(),
			"codespecie" => $this->fornecedor->getcodespecie(),
			"operacao" => "CP",
			"tipoparceiro" => "F",
			"codparceiro" => $this->fornecedor->getcodfornec(),
			"codfunc" => $this->fornecedor->getcodcomprador(),
			"dtemissao" => $dtemissao,
			"dtentrega" => date("d/m/Y"),
			"codtransp" => $transportadora->getcodtransp(),
			"modfrete" => $modfrete,
			"transpplacavei" => $transpplacavei,
			"transpufvei" => $transpufvei,
			"totalbruto" => $totalbruto,
			"totaldesconto" => $totaldesconto,
			"totalacrescimo" => $totalacrescimo,
			"totalfrete" => $totalfrete,
			"totalseguro" => $totalseguro,
			"totalipi" => $totalipi,
			"totalbaseicms" => $totalbaseicms,
			"totalicms" => $totalicms,
			"totaldesoneracao" => $totaldesoneracao,
			"totalbaseicmssubst" => $totalbaseicmssubst,
			"totalicmssubst" => $totalicmssubst,
			"totalbonificado" => $totalbonificado,
			"totalliquido" => $totalliquido,
			"totalbasepis" => $totalbasepis,
			"totalpis" => $totalpis,
			"totalbasecofins" => $totalbasecofins,
			"totalcofins" => $totalcofins,
			"basecalculofcpst" => $basecalculofcpst,
			"valorfcpst" => $valorfcpst,
			"totalbaseicmsnaoaproveitavel" => $totalbaseicmsnaoaproveitavel,
			"totalicmsnaoaproveitavel" => $totalicmsnaoaproveitavel,
			"natoperacao" => $this->fornecedor->getnatoperacao()
		);

		return $notafiscal;
	}

	private function carregar_auxiliares(){
		// Acumula todos os codigos de produtos
		$arr_codproduto = array();
		foreach($this->arr_produto_encontrado as $produto){
			$arr_codproduto[] = $produto["codproduto"];
		}
		$arr_codproduto = array_unique($arr_codproduto);

		// Busca as embalagens dos produtos
		$this->arr_embalagem = array();
		$res = $this->con->query("SELECT produto.codproduto, embalagem.quantidade, embalagem.codunidade FROM produto INNER JOIN  embalagem ON (produto.codembalcpa = embalagem.codembal) WHERE codproduto IN (".implode(", ", $arr_codproduto).")");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_embalagem[$row["codproduto"]] = $row;
		}

		// Busca a lista de PIS/Cofins
		$this->arr_piscofins = array();
		$res = $this->con->query("SELECT produto.codproduto, piscofins.aliqpis, piscofins.aliqcofins FROM produto INNER JOIN piscofins ON (produto.codpiscofinsent = piscofins.codpiscofins) WHERE codproduto IN (".implode(", ", $arr_codproduto).")");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_piscofins[$row["codproduto"]] = $row;
		}

		$this->arr_piscofins_saida = array();
		$res = $this->con->query("SELECT produto.codproduto, piscofins.aliqpis, piscofins.aliqcofins FROM produto INNER JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) WHERE codproduto IN (".implode(", ", $arr_codproduto).")");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_piscofins_saida[$row["codproduto"]] = $row;
		}


		// Busca todas as naturezas de operacao
		$this->arr_natoperacao = array();
		$res = $this->con->query("SELECT * FROM natoperacao");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_natoperacao[$row["natoperacao"]] = $row;
		}

		// Busca todas as classificacoes fiscais
		$this->arr_classfiscal = array();
		$res = $this->con->query("SELECT produto.codproduto, classfiscal.* FROM produto INNER JOIN classfiscal ON (produto.codcfnfe = classfiscal.codcf) WHERE codproduto IN (".implode(", ", $arr_codproduto).")");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$this->arr_classfiscal[$row["codproduto"]] = $row;
		}
	}

	private function carregar_fornecedor($infNFe){
		$fornecedor = objectbytable("fornecedor", NULL, $this->con);
		$cpfcnpj = (string) $infNFe->emit->CNPJ;
		if(strlen($cpfcnpj) > 0){
			$cpfcnpj = substr($cpfcnpj, 0, 2).".".substr($cpfcnpj, 2, 3).".".substr($cpfcnpj, 5, 3)."/".substr($cpfcnpj, 8, 4)."-".substr($cpfcnpj, 12, 2);
		}else{
			$cpfcnpj = (string) $infNFe->emit->CPF;
			$cpfcnpj = substr($cpfcnpj, 0, 3).".".substr($cpfcnpj, 3, 3).".".substr($cpfcnpj, 6, 3)."-".substr($cpfcnpj, 9, 2);
		}
		$fornecedor->setcpfcnpj($cpfcnpj);
		$fornecedor->searchbyobject();
		if(!$fornecedor->exists()){
			//if(!$this->cadastrar_fornecedor($cpfcnpj, $infNFe)){
				$this->error = "O CNPJ/CPF do fornecedor informado na nota fiscal não consta no cadastro.<br>CNPJ/CPF informado na nota fiscal: {$cpfcnpj}";
				return FALSE;
			//}else{
			//	return TRUE;
			//}
		}
		$this->fornecedor = $fornecedor;
		unset($fornecedor);

		// Verifica o tipo de quantidade
		if(strlen($this->tipoqtdenfe) === 0){
			if(strlen($this->fornecedor->gettipoqtdenfe()) === 0){
				$this->error = "Informe o tipo de quantidade dos itens da nota fiscal eletrônica no cadastro do fornecedor.";
				return FALSE;
			}
			$this->tipoqtdenfe = $this->fornecedor->gettipoqtdenfe();
		}

		return true;
	}

	private function cadastrar_fornecedor($cnpj, $infNFe){
		$fornecedor = objectbytable("fornecedor", NULL, $this->con);
		$fornecedor->setstatus("A");
		$fornecedor->setnome((string)$infNFe->emit->xFant);
		$fornecedor->setrazaosocial((string)$infNFe->emit->xNome);
		$fornecedor->settppessoa((strlen($cnpj) == 14 ? "J" : "F"));
		$fornecedor->setcpfcnpj($cnpj);
		$fornecedor->setrgie((string)$infNFe->emit->IE);
		$fornecedor->setfone((string)$infNFe->emit->enderEmit->fone);
		$fornecedor->setendereco((string)$infNFe->emit->enderEmit->xLgr);
		$fornecedor->setcomplemento("");
		$fornecedor->setnumero((string)$infNFe->emit->enderEmit->nro);
		$fornecedor->setbairro((string)$infNFe->emit->enderEmit->xBairro);
		$cidade = objectbytable("cidade", NULL, $this->con);
		$cidade->setcodoficial((string)$infNFe->emit->enderEmit->cMun);
		$arr_cidade = object_array($cidade);
		$cidade = NULL;
		if(count($arr_cidade) > 0){
			$cidade = array_shift($arr_cidade);
		}
		$fornecedor->setcodcidade($cidade->getcodcidade());
		$fornecedor->setuf((string)$infNFe->emit->enderEmit->UF);
		$fornecedor->setcodpais($value);
		$fornecedor->setcep((string)$infNFe->emit->enderEmit->CEP);
		$fornecedor->setatualizatributacao("N");
		$fornecedor->setmodosubsttrib("0");
		$fornecedor->settipocompra("0");
		$this->con->start_transaction();
		if(!$fornecedor->save()){
			$this->error = $_SESSION["ERROR"];
			$this->con->rollback();
			return FALSE;
		}
		$this->fornecedor = $fornecedor;
		$this->con->commit();
		return TRUE;
	}

	private function carregar_prodfornec(){
		if(!is_object($this->fornecedor)){
			$this->error = "Informe o fornecedor para carregar a relação com os produtos.";
			return false;
		}
		$prodfornec = objectbytable("prodfornec", null, $this->con);
		$prodfornec->setcodfornec($this->fornecedor->getcodfornec());
		$arr_prodfornec = object_array($prodfornec);
		foreach($arr_prodfornec as $prodfornec){
			$this->arr_prodfornec[$prodfornec->getreffornec()] = $prodfornec;
		}
		return true;
	}

	private function carregar_produtoean(){
		if(!is_array($this->arr_produtoean) || count($this->arr_produtoean) === 0){
			$res = $this->con->query("SELECT codproduto, codean FROM produtoean");
			$arr = $res->fetchAll(2);
			foreach($arr as $row){
				$this->arr_produtoean[$row["codean"]] = $row["codproduto"];
			}
		}
		return true;
	}

	function error(){
		return $this->error;
	}

	function getfornecedor(){
		return $this->fornecedor;
	}

	// Importa o arquivo XML para dentro da classe
	private function importar_xml(){
		// Verifica se o estabelecimento foi informado
		if(!is_object($this->estabelecimento)){
			$this->error = "Informe o estabelecimento referente ao XML a ser importado.";
			return false;
		}

		// Verifica se foi informado o arquivo XML
		if(strlen($this->filename) === 0){
			$this->error = "Informe o nome do arquivo XML a ser importado.";
			return false;
		}

		// Verifica se o arquivo XML informado existe
		if(!file_exists($this->estabelecimento->getdirxmlnfe().$this->filename)){
			$this->error = "O arquivo <b>{$this->estabelecimento->getdirxmlnfe()}{$this->filename}</b> não pôde ser encontrado.";
			return false;
		}


		$xml_nome = $this->estabelecimento->getdirxmlnfe().$this->filename;
		// Carrega o XML
		if(!($xml = @simplexml_load_file($xml_nome))){
			$xmldoc = file_get_contents($xml_nome);
			$xmldnfe = new DomDocumentNFePHP($xmldoc);
			$procnfe = $xmldnfe->getElementsByTagName("procNFe")->item(0);
			if(isset($procnfe)){
				$xml = simplexml_import_dom($procnfe);
			}
		}
		// Se não carregar tenta pelo simple xml
		if(!$xml){
			$xml = simplexml_load_file($xml_nome);
		}

		// Verifica se o XML foi importado corretamente
		if(!is_object($xml)){
			$xmlnfe = file_get_contents($xml_nome);
			if(strpos($xmlnfe, "<?xml") === FALSE){
				$xmlnfetmp = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>".$xmlnfe;
				$xml = simplexml_load_string($xmlnfetmp);
			}
			if(!is_object($xml)){
				$xmlnfetmp = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>".$xmlnfe;
				$xml = simplexml_load_string($xmlnfetmp);

				if(!is_object($xml)){
					$this->error = "Não foi possível carregar o arquivo XML, verifique se é um arquivo válido.";
					return false;
				}
			}
		}

		$this->xml = $xml;

		return true;
	}

	private function localizar_produtos($infNFe){
		// Lista de produtos encontrados
		$this->arr_produto_encontrado = array();

		// Lista de produtos nao encontrados
		$this->arr_produto_naoencontrado = array();

		// Percorre todos os itens do XML
		foreach($infNFe->det as $item){

			// Limpa o codigo do produto
			$codproduto = null;

			// Carrega o EAN e a referencia do fornecedor
			$codean = (string) $item->prod->cEAN;
			$reffornec = (string) $item->prod->cProd;

			// Remove os zeros a esquerda do EAN
			$codean = ltrim($codean, "0");

			// Verifica a prioridade de busca do produto
			switch($this->param_notafiscal_ordemimportacaoxml){
				// Prioridade na pesquisa pelo EAN
				case "0":
					if(strlen(ltrim($codean)) >= 8 && array_key_exists($codean, $this->arr_produtoean)){ // Procura o produto pelo codigo de barras
						$codproduto = $this->arr_produtoean[$codean];
					}elseif(array_key_exists($reffornec, $this->arr_prodfornec)){ // Procura o produto pela referencia do fornecedor
						$codproduto = $this->arr_prodfornec[$reffornec]->getcodproduto();
					}elseif(array_key_exists(ltrim($reffornec, "0"), $this->arr_prodfornec)){ // Procura o produto pela referencia do fornecedor (sem zeros a esquerda)
						$codproduto = $this->arr_prodfornec[ltrim($reffornec, "0")]->getcodproduto();
					}elseif(strlen($codean) > 13 && array_key_exists(substr($codean, 1, 13), $this->arr_produtoean)){ // Procura o produto pelo codigo de barras (iultimos 13 caracteres)
						$codproduto = $this->arr_produtoean[substr($codean, 1, 13)];
					}
					break;
				// Prioridade na pesquisa pela referencia do fornecedor
				case "1":
					if(array_key_exists($reffornec, $this->arr_prodfornec)){ // Procura o produto pela referencia do fornecedor
						$codproduto = $this->arr_prodfornec[$reffornec]->getcodproduto();
					}elseif(array_key_exists(ltrim($reffornec, "0"), $this->arr_prodfornec)){ // Procura o produto pela referencia do fornecedor (sem zeros a esquerda)
						$codproduto = $this->arr_prodfornec[ltrim($reffornec, "0")]->getcodproduto();
					}elseif(strlen(ltrim($codean)) >= 8 && array_key_exists($codean, $this->arr_produtoean)){ // Procura o produto pelo codigo de barras
						$codproduto = $this->arr_produtoean[$codean];
					}elseif(strlen($codean) > 13 && array_key_exists(substr($codean, 1, 13), $this->arr_produtoean)){ // Procura o produto pelo codigo de barras (iultimos 13 caracteres)
						$codproduto = $this->arr_produtoean[substr($codean, 1, 13)];
					}
					break;
				// Busca somente pelo EAN
				case "2":
					if(strlen(ltrim($codean)) >= 8 && array_key_exists($codean, $this->arr_produtoean)){ // Procura o produto pelo codigo de barras
						$codproduto = $this->arr_produtoean[$codean];
					}elseif(strlen($codean) > 13 && array_key_exists(substr($codean, 1, 13), $this->arr_produtoean)){ // Procura o produto pelo codigo de barras (iultimos 13 caracteres)
						$codproduto = $this->arr_produtoean[substr($codean, 1, 13)];
					}
					break;
				// Busca somente pela Referencia
				case "3":
					if(array_key_exists($reffornec, $this->arr_prodfornec)){ // Procura o produto pela referencia do fornecedor
						$codproduto = $this->arr_prodfornec[$reffornec]->getcodproduto();
					}elseif(array_key_exists(ltrim($reffornec, "0"), $this->arr_prodfornec)){ // Procura o produto pela referencia do fornecedor (sem zeros a esquerda)
						$codproduto = $this->arr_prodfornec[ltrim($reffornec, "0")]->getcodproduto();
					}
					break;
			}

			// Verifica se conseguiu localizar o produto
			if(is_null($codproduto)){
				$this->arr_produto_naoencontrado[] = array(
					"codean" => $codean,
					"reffornec" => $reffornec,
					"descricao" => (string) $item->prod->xProd,
					"preco" => (string) $item->prod->vUnCom,
					"unidade" => (string) $item->prod->uCom,
					"ncm" => (string) $item->prod->NCM,
					"xml" => $item,
					"ean" => (string)$item->prod->cEAN
				);
				continue;
			}

			// Inclui o produto na lista de encontrados
			$this->arr_produto_encontrado[] = array(
				"codproduto" => $codproduto,
				"xml" => $item
			);
		}

		return true;
	}

	// Retorna os produtos nao encontrados no XML
	function produto_naoencontrado(){
		return $this->arr_produto_naoencontrado;
	}

	// Processa o arquivo XML informado e retorna um array com todos os valores
	function processar(){
		// Limpa a variavel de retorno
		$this->retorno = array(
			"notafiscal" => array(), // Dados referentes a nota fiscal em formato array
			"itnotafiscal" => array(), // Lista de itens da nota fiscal em formato array
			"xml" => null, // Conteudo do XML
			"infNFe" => null // Estrutura infNFe do XML
		);

		// Carrega o XML
		if(!$this->importar_xml()){
			return false;
		}

		// Captura todo o conteudo do arquivo
		$this->retorno["xml"] = file_get_contents($this->estabelecimento->getdirxmlnfe().$this->filename);

		// Localiza a tag infNFe no XML
		if(isset($this->xml->nfeProc)){
			$infNFe = $this->xml->nfeProc->NFe->infNFe;
		}elseif(isset($this->xml->NFe)){
			$infNFe = $this->xml->NFe->infNFe;
		}elseif(count($this->xml->enviNFe->NFe->infNFe) > 0){
			$infNFe = $this->xml->enviNFe->NFe->infNFe;
		}elseif(count($this->xml->nfeProc->NFe->infNFe) > 0){
			$infNFe = $this->xml->nfeProc->NFe->infNFe;
		}else{
			$infNFe = $this->xml->infNFe;
		}
		$this->retorno["infNFe"] = $infNFe;

		// Captura a chave NFe
		$this->retorno["chavenfe"] = str_replace("NFe", "", (string) $infNFe["Id"]);

		// Verifica o CNPJ do estabelecimento
		if(removeformat($this->estabelecimento->getcpfcnpj()) !== (string) $infNFe->dest->CNPJ && $this->param_notafiscal_importxmlsemcnpj === "N"){
			$this->error = "O CNPJ {$infNFe->dest->CNPJ} contido no XML não confere com o CNPJ {$this->estabelecimento->getcpfcnpj()} do estabelecimento selecionado.";
			return false;
		}

		// Verifica se encontrou o fornecedor no cadastro
		if(!$this->carregar_fornecedor($infNFe)){
			return false;
		}

		// Carrega a lista de EANs
		$this->carregar_produtoean();

		// Carrega a lista de referencia do fornecedor
		$this->carregar_prodfornec();

		// Localiza os produtos, alimentando as variaveis da classe: arr_produto_encontrado e arr_produto_naoencontrado
		$this->localizar_produtos($infNFe);

		// Verifica se existe produto nao encontrado
		if(count($this->arr_produto_naoencontrado) > 0){
			// Verifica se deve cadastrar os produtos nao encontrados
			if(substr($this->param_notafiscal_autogravarprodxml, 0, 1) === "S"){
				$this->con->start_transaction();
				foreach($this->arr_produto_naoencontrado as $produto_naoencontrado){
					if(!$this->cadastrar_produto($produto_naoencontrado)){
						$this->con->rollback();
						return false;
					}
				}
				$this->con->commit();
			}else{
				$this->error = "Existem produtos não cadastrados no XML.";
				return false;
			}
		}

		// Carrega os cadastro auxiliares (embalagem, natoperacao, classfiscal, piscofins)
		$this->carregar_auxiliares();

		// Carrega o estado do estabelecimento e do parceiro
		$this->estado_estabelecimento = objectbytable("estado", $this->estabelecimento->getuf(), $this->con);
		$this->estado_fornecedor = objectbytable("estado", $this->fornecedor->getuf(), $this->con);

		// Percorre todos produtos encontrados no XML
		foreach($this->arr_produto_encontrado as $produto){
			$itnotafiscal = $this->capturar_itnotafiscal($produto);
			if($itnotafiscal === false){
				return false;
			}
			$this->retorno["itnotafiscal"][] = $itnotafiscal;
		}

		// Captura/gera os dados referentes a nota fiscal
		$this->retorno["notafiscal"] = $this->capturar_notafiscal($infNFe, $this->retorno["itnotafiscal"]);

		// Retorno
		return $this->retorno;
	}

	function setcodestabelec($codestabelec){
		$estabelecimento = objectbytable("estabelecimento", $codestabelec, $this->con);
		$this->setestabelecimento($estabelecimento);
	}

	function setestabelecimento(Estabelecimento $estabelecimento){
		$this->estabelecimento = $estabelecimento;
	}

	function setfilename($filename){
		$this->filename = $filename;
	}

	function settipoqtdenfe($tipoqtdenfe){
		$this->tipoqtdenfe = $tipoqtdenfe;
	}

}
