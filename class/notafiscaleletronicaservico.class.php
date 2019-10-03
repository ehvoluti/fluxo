<?php

require_once("../def/require_php.php");
define("HOMOLOGACAO", "2");
define("PRODUCAO", "1");
define("AUTORIZADO", "RPS AUTORIZADA COM SUCESSO");
define("EXCEPTION","EXCEPTION");
define("WS_ROTA_PRODUCAO","https://managersaas.tecnospeed.com.br:8081/ManagerAPIWeb/nfse/");
define("WS_ROTA_HOMOLOGACAO","https://managersaashom.tecnospeed.com.br:7071/ManagerAPIWeb/nfse/");

class NotaFiscalEletronicaServico{
	private $notafiscalservico;
	private $estabelecimento;
	private $con;
	private $ambiente;
	private $usuario;
	private $senha;
	private $cidade_estabelecimento;
	private $parceiro;
	private $cidade_parceiro;
	private $paramfiscal;
	private $arr_servicos = array();
	private $nomecidade;
	private $nomegrupo;

	public function __construct($con, $ambiente){
		$this->con = $con;
		$this->ambiente = $ambiente;
	}

	public function setnotafiscalservico($value){
		$this->notafiscalservico = $value;
	}

	public function setestabelecimento($value){
		$this->estabelecimento = $value;
		$this->setnomegrupo($this->estabelecimento->getnomegrupo());
	}

	public function setcidade($value){
		$this->cidade_estabelecimento = $value;
		switch($this->cidade_estabelecimento->getcodoficial()){
			case "3550308": $this->setnomecidade("SaoPauloSP"); break;
		}
	}

	private function setcidade_parceiro($value){
		$this->cidade_parceiro = $value;
	}

	private function setparceiro($value){
		$this->parceiro = $value;
	}

	private function setarr_servicos($value){
		$this->arr_servicos = $value;
	}

	private function setparamfiscal($value){
		$this->paramfiscal = $value;
	}

	private function setnomecidade($value){
		$this->nomecidade = $value;
	}

	private function setnomegrupo($value){
		$this->nomegrupo = $value;
	}

	public function setambiente($value){
		$this->ambiente = $value;
	}

	public function setusuario($value){
		$this->usuario = $value;
	}

	public function setsenha($value){
		$this->senha = $value;
	}

	public function gerarTX2($opcao, $msg = NULL){
		unset($_SESSION["SUCCESS"]);
		unset($_SESSION["ERROR"]);
		try{
			$paramfiscal = objectbytable("paramfiscal", $this->estabelecimento->getcodestabelecfiscal(), $this->con);
			$this->setparamfiscal($paramfiscal);
			switch($opcao){
				case "Enviar":
					$tx2 = array();

					if($this->notafiscalservico->flag_itnotafiscal){
						$arr_itnotafiscal_aux = $this->notafiscalservico->itnotafiscal;
					}else{
						$itnotafiscal = objectbytable("itnotafiscal", NULL, $this->con);
						$itnotafiscal->setidnotafiscal($this->notafiscalservico->getidnotafiscal());
						$arr_itnotafiscal_aux = object_array($itnotafiscal);
					}

					$arr_servicos = array();
					foreach($arr_itnotafiscal_aux as $it_servico){
						$arr_servicos[$it_servico->getidnotafiscal()] = $it_servico;
					}

					$this->setarr_servicos($arr_servicos);

					switch($this->notafiscalservico->gettipoparceiro()){
						case "C": // Cliente
							$parceiro = objectbytable("cliente", $this->notafiscalservico->getcodparceiro(), $this->con);
							$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidaderes(), $this->con);
							$pais_parceiro = objectbytable("pais", $parceiro->getcodpaisres(), $this->con);
							break;
						case "E": // Estabelecimento
							$parceiro = objectbytable("estabelecimento", $this->notafiscalservico->getcodparceiro(), $this->con);
							$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
							$pais_parceiro = objectbytable("pais", "01058", $this->con);
							break;
						case "F": // Fornecedor
							$parceiro = objectbytable("fornecedor", $this->notafiscalservico->getcodparceiro(), $this->con);
							$cidade_parceiro = objectbytable("cidade", $parceiro->getcodcidade(), $this->con);
							$pais_parceiro = objectbytable("pais", $parceiro->getcodpais(), $this->con);
							break;
						default:
							$_SESSION["ERROR"] = "Tipo de parceiro (".$this->notafiscalservico->gettipoparceiro().") n&atilde;o encontrado para emiss&atilde;o da NFS-e.";
							return FALSE;
					}

					$this->setparceiro($parceiro);
					$this->setcidade_parceiro($cidade_parceiro);
					$codigo_ibge = $this->cidade_estabelecimento->getcodoficial();
					$nome_cidade = $this->cidade_estabelecimento->getnome();
					switch ($codigo_ibge){
						case "3550308":
							$tx2 = $this->layout_sao_paulo();
							break;
						default:
							$_SESSION["ERROR"] = "A cidade {$nome_cidade} do estabelecimento não possui integração para emissão NFS-e. Contate suporte técnico";
							return FALSE;

					}

					$cnpj = removeformat($this->estabelecimento->getcpfcnpj());

					$arquivo = implode("\n", $tx2);
					$postfields = array(
						"Grupo" => "{$this->nomegrupo}",
						"CNPJ" => "{$cnpj}",
						"Arquivo" => "{$arquivo}"
					);

					file_put_contents("../temp/nfse.tx2", $arquivo);

					if($this->notafiscalservico->getstatus() == "R"){
						if(!$this->gerarTX2("Descartar")){
							//return FALSE;
						}
					}
					//return TRUE;
					if($this->notafiscalservico->getstatus() == "P"){
						$consultar = TRUE;

						if(strlen(trim($this->usuario)) == 0){
							$result = "000000,000000,NFS-e Autorizada com sucesso";
							$consultar = FALSE;
						}else{
							$result = $this->ws("envia", $postfields);
						}

						$aresult = explode(",", $result);
						file_put_contents("../temp/retornonfse.txt", var_export($aresult, True));
						$msgretorno = str_replace(array("\r","\n","\""),"", utf8_encode($aresult[3]));
						if($aresult[0] == EXCEPTION){
							$this->notafiscalservico->setxmotivo(str_replace(array("\r","\n","\""),"", utf8_encode($aresult[2].$aresult[3].$aresult[4])));
							if(!$this->notafiscalservico->save()){

							}
							$_SESSION["ERROR"] = str_replace(array("\r","\n","\""),"",utf8_encode($aresult[2].$aresult[3].$aresult[4]));
							return FALSE;
						}else{
							$this->notafiscalservico->sethandlenfse($aresult[0]);
							$this->notafiscalservico->setlotenfse($aresult[1]);
							$this->notafiscalservico->setxmotivo($msgretorno);
							if(!$this->notafiscalservico->save()){

							}
							$_SESSION["SUCCESS"] = $msgretorno;
							if($consultar){
								$this->gerarTX2("Consultar", $msgretorno);
							}
							return TRUE;
						}
					}else{
						$_SESSION["ERROR"] = "NFS-e não esta com status correto para transmissão";
						return FALSE;
					}
					break;
				case "Consultar":
					if(strlen(trim($this->notafiscalservico->gethandlenfse())) == 0 || $this->notafiscalservico->gethandlenfse() == "000000"){
						if($this->notafiscalservico->gethandlenfse() == "000000"){
							$_SESSION["ERROR"] = "NFS-e emitida por outro sistema";
						}else{
							$_SESSION["ERROR"] = "NFS-e sem informações para consulta";
						}
						return FALSE;
					}
					$postfields = $this->consultar_nfse();

					$result = $this->ws("consulta", $postfields);
					$aresult = explode(",", $result);
					if($aresult[0] == EXCEPTION){
						$_SESSION["ERROR"] = str_replace(array("\r","\n","\""),"", $aresult[2]);
						return FALSE;
					}else{
						if(str_replace(array("\r","\n","\""),"", $aresult[1]) == "AUTORIZADA"){
							$this->notafiscalservico->setstatus("A");
							$this->notafiscalservico->setnumnotafis($aresult[0]);
							$this->notafiscalservico->setcodigostatus("100");
							$this->notafiscalservico->setxmotivo("NFS-e Autorizada com sucesso");
							$_SESSION["SUCCESS"] = "NFS-e Autorizada com sucesso";
						}elseif(str_replace(array("\r","\n","\""),"",$aresult[1]) == "CANCELADA"){
							$this->notafiscalservico->setstatus("C");
							$this->notafiscalservico->setcodigostatus("101");
							$this->notafiscalservico->setxmotivo("Cancelamento de NFS-e Homologado");
							$_SESSION["SUCCESS"] = "Cancelamento de NFS-e Homologado";
						}elseif(str_replace(array("\r","\n","\""),"",$aresult[1]) == "REJEITADA"){
							$this->notafiscalservico->setstatus("R");
							$this->notafiscalservico->setcodigostatus("110");
							if(!is_null($msg)){
								$this->notafiscalservico->setxmotivo($msg);
								$_SESSION["SUCCESS"] = $msg;
							}else{
								$this->notafiscalservico->setxmotivo("NFS-e rejeitada pelo servidor");
								$_SESSION["SUCCESS"] = "NFS-e rejeitada pelo servidor";
							}
						}else{
							$_SESSION["SUCCESS"] = str_replace(array("\r","\n","\""),"", $aresult[0]);
						}
						if(!$this->notafiscalservico->save()){
							return FALSE;
						}
						return TRUE;
					}
					break;
				case "Cancelar":
					if(strlen(trim($this->notafiscalservico->gethandlenfse())) == 0 || $this->notafiscalservico->gethandlenfse() == "000000"){
						if($this->notafiscalservico->gethandlenfse() == "000000"){
							$_SESSION["ERROR"] = "NFS-e emitida por outro sistema";
						}else{
							$_SESSION["ERROR"] = "NFS-e sem informações para cancelar";
						}
						return FALSE;
					}

					$postfields = $this->cancelar_nfse();

					$result = $this->ws("cancela", $postfields);
					$aresult = explode(",", $result);
					if($aresult[0] == EXCEPTION){
						$_SESSION["ERROR"] = str_replace(array("\r","\n","\""),"", $aresult[2]);
						return FALSE;
					}else{
						if(strtoupper(str_replace(array("\r","\n","\""),"", $aresult[3])) == "CANCELAMENTO DE NFS-E HOMOLOGADO"){
							$this->notafiscalservico->setstatus("C");
							$this->notafiscalservico->setcodigostatus("101");
							$this->notafiscalservico->setxmotivo(utf8_encode(str_replace(array("\r","\n","\""),"", $aresult[3])));
							$this->notafiscalservico->save();
							$_SESSION["SUCCESS"] = str_replace(array("\r","\n","\""),"", $aresult[3]);
							return TRUE;
						}else{
							$_SESSION["ERROR"] = "Não foi possivel identificar se a NFS-e foi cancelada com sucesso. Faça uma consulta para confirmar se a NFS-e foi cancelada com sucesso.";
							return FALSE;
						}
					}
					break;
				case "Imprimir":
					if(strlen(trim($this->notafiscalservico->gethandlenfse())) == 0 || $this->notafiscalservico->gethandlenfse() == "000000"){
						if($this->notafiscalservico->gethandlenfse() == "000000"){
							$_SESSION["ERROR"] = "NFS-e emitida por outro sistema";
						}else{
							$_SESSION["ERROR"] = "NFS-e sem informações para impressão";
						}
						return FALSE;
					}

					$postfields = $this->imprimir_nfse();

					$result = $this->ws("imprime", $postfields);
					$aresult = explode(",", $result);
					if($aresult[0] == EXCEPTION){
						$_SESSION["ERROR"] = $aresult[2];
						return FALSE;
					}else{
						$arquivopdf = file_get_contents($aresult[0]);
						$bytes = file_put_contents("../temp/nfse{$this->notafiscalservico->getnumnotafis()}.pdf", $arquivopdf);
						$arquivopdf = "window.open('../temp/nfse{$this->notafiscalservico->getnumnotafis()}.pdf');";
						echo script($arquivopdf);
						$_SESSION["SUCCESS"] = str_replace(array("\r","\n","\""),"", $aresult[1]);
						return TRUE;
					}
					break;
				case "Email":
					if(strlen(trim($this->notafiscalservico->gethandlenfse())) == 0 || $this->notafiscalservico->gethandlenfse() == "000000"){
						if($this->notafiscalservico->gethandlenfse() == "000000"){
							$_SESSION["ERROR"] = "NFS-e emitida por outro sistema";
						}else{
							$_SESSION["ERROR"] = "NFS-e sem informações para envio por email";
						}
						return FALSE;
					}

					$postfields = $this->email_nfse();

					$result = $this->ws("email", $postfields);
					$aresult = explode(",", $result);
					if($aresult[0] == EXCEPTION){
						$_SESSION["ERROR"] = $aresult[2];
						return FALSE;
					}else{
						$_SESSION["SUCCESS"] = str_replace(array("\r","\n","\""),"", $aresult[1]);
						return TRUE;
					}
					break;
				case "Descartar":
					if(strlen(trim($this->notafiscalservico->gethandlenfse())) == 0 || $this->notafiscalservico->gethandlenfse() == "000000"){
						if($this->notafiscalservico->gethandlenfse() == "000000"){
							$_SESSION["ERROR"] = "NFS-e emitida por outro sistema";
						}else{
							$_SESSION["ERROR"] = "NFS-e sem informações para descartar";
						}
						return FALSE;
					}

					$postfields = $this->descartar_nfse();

					$result = $this->ws("descarta", $postfields);
					$aresult = explode(",", $result);
					if($aresult[0] == EXCEPTION){
						$_SESSION["ERROR"] = $aresult[1];
						return FALSE;
					}elseif(trim($aresult[0]) == "OK"){
						$this->notafiscalservico->setstatus("P");
						$this->notafiscalservico->setcodigostatus("");
						$this->notafiscalservico->setxmotivo("");
						if(!$this->notafiscalservico->save()){

						}
						$_SESSION["SUCCESS"] = "NFS-e descartada com sucesso";
						return TRUE;
					}else{
						$_SESSION["ERROR"] = "Não foi possivel descartar a NFS-e";
					}
					break;
				case "Exportar":
					break;
			}
			return TRUE;
		}catch(Exception $ex){
			$_SESSION["ERROR"] = $ex->getMessage();
			throw new Exception("Houve um erro na geração dos dados da NFS-e");
			return FALSE;
		}

	}

	function ws($rota,$postfields){
		$userpassword = base64_encode("{$this->usuario}:{$this->senha}");
		$url = ($this->ambiente === HOMOLOGACAO ? WS_ROTA_HOMOLOGACAO : WS_ROTA_PRODUCAO);
		$url .= $rota;
		$curl = curl_init();
												// setar a url a ser executada
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);							// parametro padrão
		curl_setopt($curl, CURLOPT_ENCODING, "");									// parametro padrão
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);									// parametro padrão
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);									// parametro padrão
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);								// parametro padrão
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);			// parametro padrão
		if(in_array($rota, array("envia", "cancela", "email", "descarta"))){
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");						// tipo da ação a ser executada
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postfields));						// setar os parametros a ser passado na url
		}else{
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");						// tipo da ação a ser executada
			$postfields = $postfields;
			$url .= $postfields;
		}
		curl_setopt($curl, CURLOPT_URL,$url);
		curl_setopt($curl, CURLOPT_USERPWD, base64_encode("{$this->usuario}:{$this->senha}"));		// setar o usuário e senha de acesso ao serviço
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("application/text", "authorization: Basic {$userpassword}"));	// setar o tipo de autenticação

		$result = curl_exec($curl);		// executar a URL do serviço
		$erro = curl_error($curl);		// pegar possiveis erros encontrados
		return $result;
	}

	function consultar_nfse(){
		$cnpj = removeformat($this->estabelecimento->getcpfcnpj());
		$cidade = $this->nomecidade;
		$handlenfse = $this->notafiscalservico->gethandlenfse();
		$postfields  = "?Grupo={$this->nomegrupo}&CNPJ={$cnpj}";
		$postfields .= "&NomeCidade={$cidade}";
		$postfields .= "&filtro=handle={$handlenfse}";
		$postfields .= "&campos=nnfse,situacao";
		return $postfields;
	}

	function imprimir_nfse(){
		$cnpj = removeformat($this->estabelecimento->getcpfcnpj());
		$cidade = $this->nomecidade;

		$handlenfse = $this->notafiscalservico->gethandlenfse();
		$postfields  = "?Grupo={$this->nomegrupo}&CNPJ={$cnpj}";
		$postfields .= "&NomeCidade={$cidade}";
		$postfields .= "&handle={$handlenfse}";
		$postfields .= "&url=1";
		return $postfields;
	}

	function cancelar_nfse(){
		$cnpj = removeformat($this->estabelecimento->getcpfcnpj());
		$cidade = $this->nomecidade;
		$handlenfse = $this->notafiscalservico->gethandlenfse();
		$postfields = array(
			"Grupo" => "{$this->nomegrupo}",
			"CNPJ" => "{$cnpj}",
			"NomeCidade" => "{$cidade}",
			"handle" => "{$handlenfse}"
		);
		return $postfields;
	}

	function descartar_nfse(){
		$cnpj = removeformat($this->estabelecimento->getcpfcnpj());
		$cidade = $this->nomecidade;
		$handlenfse = $this->notafiscalservico->gethandlenfse();
		$postfields = array(
			"Grupo" => "{$this->nomegrupo}",
			"CNPJ" => "{$cnpj}",
			"NomeCidade" => "{$cidade}",
			"handle" => "{$handlenfse}"
		);
		return $postfields;
	}

	function email_nfse(){
		if($this->ambiente == HOMOLOGACAO){
			$cnpj = removeformat($this->estabelecimento->getcpfcnpj());
			$cidade = $this->nomecidade;
			$email = "jesus@controlware.com.br";
		}else {
			$cnpj =  $this->estabelecimento->getcpfcnpj();
			$cidade = $this->nomecidade;
			$email = $this->parceiro->getemail();
		}
		$handlenfse = $this->notafiscalservico->gethandlenfse();
		$postfields = array(
			"Grupo" => "{$this->nomegrupo}",
			"CNPJ" => "{$cnpj}",
			"NomeCidade" => "{$cidade}",
			"handle" => "{$handlenfse}",
			"EmailDestinatario" => $email,
			"AnexarPDF" => "1"
		);
		return $postfields;
	}

	function layout_maringa(){
		$tx2[] = "NomeCidade=Maringa";
		$tx2[] = "formato=tx2";
		$tx2[] = "padrao=tecnonfse";
		$tx2[] = "INCLUIR";
		$tx2[] = "IdLote=0"; //{$this->notafiscalservico->getidnotafiscal()}";
		$tx2[] = "NumeroLote=0"; //{$this->notafiscalservico->getidnotafiscal()}";
		$tx2[] = "CpfCnpjRemetente=08187168000160";
		$tx2[] = "InscricaoMunicipalRemetente=8214100028";
		$tx2[] = "RazaoSocialRemetente=Tecnospeed TI";
		$tx2[] = "QuantidadeRps=1";
		$tx2[] = "CodigoCidadeRemetente=41";
		$tx2[] = "Transacao=1";
		$tx2[] = "DataInicio=".date("Y-m-d");;
		$tx2[] = "DataFim=".date("Y-m-d");;
		$tx2[] = "Versao=3.10";
		$tx2[] = "ValorTotalServicos=".number_format($this->notafiscalservico->gettotalliquido(),2,".","");
		$tx2[] = "ValorTotalDeducoes=0";
		$tx2[] = "ValorTotalBaseCalculo=".number_format($this->notafiscalservico->gettotalliquido(),2,".","");
		$tx2[] = "SALVAR";

		foreach($this->arr_servicos as $it_servico){
			$tx2[] = "INCLUIRRPS";
			$tx2[] = "DataEmissao=".date("Y-m-d");
			$tx2[] = "IdRps=R1";
			$tx2[] = "NumeroRps=";
			$tx2[] = "SerieRps=".$this->notafiscalservico->getserie();
			$tx2[] = "TipoRps=1";
			$tx2[] = "OptanteSimplesNacional=2";
			$tx2[] = "IncentivadorCultural=1";
			$tx2[] = "ExigibilidadeISS=1";
			$tx2[] = "IncentivoFiscal=2";
			$tx2[] = "SituacaoNota=1";
			$tx2[] = "TipoTributacao=1";
			$tx2[] = "NaturezaTributacao=1";
			$tx2[] = "RegimeEspecialTributacao=0";
			$tx2[] = "ValorServicos=".number_format($it_servico->gettotalliquido(),2,".","");
			$tx2[] = "ValorDeducoes=0";
			$tx2[] = "ValorPis=". number_format($it_servico->gettotalpis(),2, ".", "");
			$tx2[] = "ValorCofins=".number_format($it_servico->gettotalcofins(),2, ".", "");
			$tx2[] = "ValorInss=0.00";
			$tx2[] = "ValorIr=0.00";
			$tx2[] = "ValorCsll=0.00";
			$tx2[] = "IssRetido=2";
			$tx2[] = "ValorIss=".number_format($it_servico->gettotalliquido() * ($it_servico->getaliqicms() / 100),2,".", "");
			$tx2[] = "ValorIssRetido=0.00";
			$tx2[] = "BaseCalculo=0";
			$tx2[] = "ValorLiquidoNfse=".number_format($it_servico->gettotalliquido(),2, ".", "");
			$tx2[] = "DescontoIncondicionado=0.00";
			$tx2[] = "DescontoCondicionado=0.00";
			$tx2[] = "AliquotaISS=";
			$tx2[] = "AliquotaPIS=1.65";
			$tx2[] = "AliquotaCOFINS=7.60";
			$tx2[] = "AliquotaINSS=0.00";
			$tx2[] = "AliquotaIR=0.00";
			$tx2[] = "AliquotaCSLL=0.00";
			$tx2[] = "CodigoItemListaServico=0107";
			$tx2[] = "CodigoCnae=6611801";
			$tx2[] = "CodigoTributacaoMunicipio=4115200";
			$tx2[] = "DiscriminacaoServico=Licenciamento de Software.";
			$tx2[] = "CodigoCidadePrestacao=4115200";
			$tx2[] = "DescricaoCidadePrestacao=MARINGA";
			$tx2[] = "CpfCnpjPrestador=08187168000160";
			$tx2[] = "InscricaoMunicipalPrestador=096650";
			$tx2[] = "RazaoSocialPrestador=Tecnospeed T.I.";
			$tx2[] = "DDDPrestador=44";
			$tx2[] = "TelefonePrestador=30284665";
			$tx2[] = "CpfCnpjTomador=08114280956";
			$tx2[] = "RazaoSocialTomador=Teste NFSe";
			$tx2[] = "InscricaoMunicipalTomador=";
			$tx2[] = "InscricaoEstadualTomador=";
			$tx2[] = "TipoLogradouroTomador=RUA";
			$tx2[] = "EnderecoTomador=JURANDA MARIGUTTI";
			$tx2[] = "NumeroTomador=2946";
			$tx2[] = "ComplementoTomador=EDF. VILLA MIX";
			$tx2[] = "TipoBairroTomador=JARDIM";
			$tx2[] = "BairroTomador=CENTRO";
			$tx2[] = "CodigoCidadeTomador=44";
			$tx2[] = "DescricaoCidadeTomador=MARINGA";
			$tx2[] = "UfTomador=PR";
			$tx2[] = "CepTomador=87015983";
			$tx2[] = "DDDTomador=044";
			$tx2[] = "TelefoneTomador=4430147841";
			$tx2[] = "EmailTomador=";
			$tx2[] = "PercentualDeduzir=0";
			$tx2[] = "QuantidadeServicos=1";
			$tx2[] = "ValorUnitarioServico=100.00";
			$tx2[] = "SALVARRPS";
		}
		return $tx2;
	}

	function layout_sao_paulo(){
		$operacaonota = objectbytable("operacaonota", $this->notafiscalservico->getoperacao(), $this->con);
		$tx2[] = "formato=tx2";
		$tx2[] = "NomeCidade={$this->nomecidade}";
		$tx2[] = "padrao=tecnonfse";

		$tx2[] = "INCLUIR";
		$tx2[] = "NumeroLote=0";
		$tx2[] = "QuantidadeRps=".count($this->arr_servicos); //?
		$tx2[] = "Transacao=true"; //?
		$tx2[] = "MetodoEnvio=WS";
		$tx2[] = "CpfCnpjRemetente=".removeformat($this->estabelecimento->getcpfcnpj());
		$tx2[] = "InscricaoMunicipalRemetente=".removeformat($this->estabelecimento->getinscmunicipal());
		$tx2[] = "RazaoSocialRemetente=".$this->estabelecimento->getrazaosocial();
		$tx2[] = "CodigoCidadeRemetente=".$this->cidade_estabelecimento->getcodoficial();
		$tx2[] = "DataInicio=".$this->notafiscalservico->getdtemissao();
		$tx2[] = "DataFim=".$this->notafiscalservico->getdtemissao();
		$tx2[] = "ValorTotalServicos=".number_format($this->notafiscalservico->gettotalliquido(),2,".","");
		$tx2[] = "ValorTotalDeducoes=".number_format($this->notafiscalservico->gettotaldesconto(),2,".","");
		$tx2[] = "ValorTotalBaseCalculo=".number_format($this->notafiscalservico->gettotalliquido(),2,".","");
		$tx2[] = "SALVAR";

		$tx2[] = "INCLUIRRPS";
		foreach($this->arr_servicos as $it_servico){
			$produto = objectbytable("produto", $it_servico->getcodproduto(), $this->con);
			$servico = objectbytable("codigoservico", $it_servico->getidcodigoservico(), $this->con);
			$piscofins = objectbytable("piscofins", ($operacaonota->gettipo() == "E" ? $produto->getcodpiscofinsent() : $produto->getcodpiscofinssai()), $this->con);

			$tx2[] = "IdRps=";
			$tx2[] = "SituacaoNota=1";
			$tx2[] = "TipoRps=1";
			$tx2[] = "NumeroRps="; //?
			$tx2[] = "SerieRps=".$this->notafiscalservico->getserie();  //?
			$tx2[] = "DataEmissao=".$this->notafiscalservico->getdtemissao()."T".date("h:i:s")."-02:00";
			$tx2[] = "Competencia=".$this->notafiscalservico->getdtemissao();
			$tx2[] = "CpfCnpjPrestador=".removeformat($this->estabelecimento->getcpfcnpj());
			$tx2[] = "InscricaoMunicipalPrestador=".removeformat($this->estabelecimento->getinscmunicipal());
			$tx2[] = "RazaoSocialPrestador=".$this->estabelecimento->getrazaosocial();
			$tx2[] = "CodigoCidadePrestacao=".$this->cidade_estabelecimento->getcodoficial();
			$tx2[] = "DescricaoCidadePrestacao=".$this->cidade_estabelecimento->getnome();
			if($this->estabelecimento->getregimetributario() == "1"){
				$tx2[] = "OptanteSimplesNacional=1";
			}else{
				$tx2[] = "OptanteSimplesNacional=2";
			}
			$tx2[] = "IncentivadorCultural=2";
			//$tx2[] = "RegimeEspecialTributacao=".$this->estabelecimento->getregimetspecialtributacao();
			$tx2[] = "NaturezaTributacao=".$it_servico->getnattributacao() ;
			$tx2[] = "IncentivoFiscal=2";
			$tx2[] = "InscricaoEstadualPrestador=".removeformat($this->estabelecimento->getrgie());

			$tx2[] = "TipoLogradouroPrestador=";
			$tx2[] = "EnderecoPrestador=".  $this->estabelecimento->getendereco();
			$tx2[] = "NumeroPrestador=".$this->estabelecimento->getnumero();
			$tx2[] = "ComplementoPrestador=".$this->estabelecimento->getcomplemento();
			$tx2[] = "TipoBairroPrestador=";
			$tx2[] = "BairroPrestador=".$this->estabelecimento->getbairro();
			$tx2[] = "CodigoCidadePrestador=".$this->cidade_estabelecimento->getcodoficial();
			$tx2[] = "DescricaoCidadePrestador=".$this->cidade_estabelecimento->getnome();
			$tx2[] = "DDDPrestador=";
			$tx2[] = "TelefonePrestador=".$this->estabelecimento->getfone1();
			$tx2[] = "EmailPrestador=";
			$tx2[] = "CepPrestador=".$this->estabelecimento->getcep();
			$tx2[] = "CpfCnpjTomador=".removeformat($this->parceiro->getcpfcnpj());
			$tx2[] = "RazaoSocialTomador=".$this->parceiro->getrazaosocial();
			$tx2[] = "InscricaoEstadualTomador=".removeformat($this->parceiro->getrgie());
			$tx2[] = "TipoLogradouroTomador=";
			if($this->notafiscalservico->gettipoparceiro() == "C"){
				$tx2[] = "EnderecoTomador=".$this->parceiro->getenderres();
				$tx2[] = "NumeroTomador=".$this->parceiro->getnumerores();
				$tx2[] = "ComplementoTomador=";
				$tx2[] = "TipoBairroTomador=";
				$tx2[] = "BairroTomador=".$this->parceiro->getbairrores();
				$tx2[] = "CodigoCidadeTomador=".$this->cidade_parceiro->getcodoficial();
				$tx2[] = "DescricaoCidadeTomador=".$this->cidade_parceiro->getnome();
				$tx2[] = "UfTomador=".$this->cidade_parceiro->getuf();
				$tx2[] = "CepTomador=".removeformat($this->parceiro->getcepres());
				$tx2[] = "DDDTomador=";
				$tx2[] = "TelefoneTomador=".removeformat($this->parceiro->getfoneres());
			}else{
				$tx2[] = "EnderecoTomador=".$this->parceiro->ende();
				$tx2[] = "NumeroTomador=".$this->parceiro->getnumero();
				$tx2[] = "ComplementoTomador=";
				$tx2[] = "TipoBairroTomador=";
				$tx2[] = "BairroTomador=".$this->parceiro->getbairro();
				$tx2[] = "CodigoCidadeTomador=".$this->cidade_parceiro->getcodoficial();
				$tx2[] = "DescricaoCidadeTomador=".$this->cidade_parceiro->getnome();
				$tx2[] = "UfTomador=".$this->cidade_parceiro->getuf();
				$tx2[] = "CepTomador=".removeformat($this->parceiro->getcep());
				$tx2[] = "DDDTomador=";
				$tx2[] = "TelefoneTomador=".removeformat($this->parceiro->getfone());
			}
			$tx2[] = "PaisTomador=1058";
			$tx2[] = "EmailTomador=".$this->parceiro->getemail();


			$tx2[] = "CodigoItemListaServico=".removeformat($servico->getcodigosubitem());
			$tx2[] = "CodigoTributacaoMunicipio=";
			$tx2[] = "CodigoCnae=";
			$tx2[] = "DiscriminacaoServico=".$produto->getdescricaofiscal().str_replace("\n","|",$this->notafiscalservico->getobservacaofiscal());
			$tx2[] = "TipoTributacao=".$it_servico->gettptribicms();
			$tx2[] = "ExigibilidadeISS=";
			$tx2[] = "Operacao=A";
			$tx2[] = "MunicipioIncidencia=".$this->cidade_parceiro->getcodoficial();
			$tx2[] = "ValorServicos=".number_format($it_servico->gettotalliquido(),2,".","");
			$tx2[] = "ValorDeducoes=".number_format($it_servico->gettotaldesconto(),2,".","");

			$tx2[] = "AliquotaPIS=".number_format($it_servico->getaliqpis(),2,".","");
			$tx2[] = "AliquotaCOFINS=".number_format($it_servico->getaliqcofins(),2,".","");
			$tx2[] = "AliquotaINSS=".number_format($it_servico->getaliquotainss(), 2, ".", "");
			$tx2[] = "AliquotaIR=".number_format($it_servico->getaliquotair(), 2, ".", "");
			$tx2[] = "AliquotaCSLL=".number_format($it_servico->getaliquotacsll(), 2, ".", "");
			$tx2[] = "ValorPIS=".number_format($it_servico->gettotalpis(),2,".","");
			$tx2[] = "ValorCOFINS=".number_format($it_servico->gettotalcofins(),2,".","");
			$tx2[] = "ValorINSS=".number_format($it_servico->getvalorinss(), 2, ".", "");
			$tx2[] = "ValorIR=".number_format($it_servico->getvalorir(), 2, ".", "");
			$tx2[] = "ValorCSLL=".number_format($it_servico->getvalorcsll(), 2, ".", "");
			$tx2[] = "OutrasRetencoes=0.00";
			$tx2[] = "DescontoIncondicionado=0.00";
			$tx2[] = "DescontoCondicionado=0.00";
			if($it_servico->getaliqicms() > 0){
				$tx2[] = "BaseCalculo=".number_format($it_servico->gettotalliquido(),2,".","");
			}else{
				$tx2[] = "BaseCalculo=".number_format(0,2,".","");
			}
			$tx2[] = "ValorIss=".number_format($it_servico->gettotalliquido() * ($it_servico->getaliqicms() / 100),2,".","");
			$tx2[] = "AliquotaISS=".number_format($it_servico->getaliqicms(),2,".","");
			$tx2[] = "IssRetido=".($it_servico->getissretido() == "S" ? "1" : "2");
			$tx2[] = "ValorISSRetido=0.00";
			$tx2[] = "ValorLiquidoNfse=".number_format($it_servico->gettotalliquido() - $it_servico->gettotalpis() - $it_servico->gettotalcofins() - $it_servico->getvalorinss() - $it_servico->getvalorir() - $it_servico->getvalorcsll(),2,".","");
			$tx2[] = "SALVARRPS";
		}
		return $tx2;
	}
}

