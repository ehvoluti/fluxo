<?php

final class VTex{

	private $con;
	private $debug;
	private $erros;
	private $parametro;
	private $estabelecimento;
	private $emitente;
	private $natoperacao;
	private $operacaonota;
	private $tabelapreco;
	private $condpagto;
	private $especie;
	private $funcionario;
	private $dirfotoprod;
	private $empresa;
	private $usuario;
	private $senha;
	private $chave;
	private $token;
	private $urlfoto;
	private $url_rest;
	private $url_soap;
	private $codproduto; // Codigo do ultimo produto sincronizado
	private $dthrsinc; // Data e hora da ultima sincronizacao
	private $idestoque; // ID do estoque no V-Tex
	private $namespace_soapenv = "http://schemas.xmlsoap.org/soap/envelope/";
	private $namespace_tem = "http://tempuri.org/";
	private $namespace_vtex = "http://schemas.datacontract.org/2004/07/Vtex.Commerce.WebApps.AdminWcfService.Contracts";
	private $namespace_arr = "http://schemas.microsoft.com/2003/10/Serialization/Arrays";

	function __construct(Connection $con, $debug = FALSE){
		$this->con = $con;
		$this->debug = $debug;
		$this->erros = array();

		if($this->debug){
			$file = fopen("../temp/vtex.log", "w+");
			fclose($file);
		}

		$this->parametro = objectbytable("parametro", array("INTEGRACAO", "VTEX"), $this->con);
		$param = parse_ini_string($this->parametro->getvalor());

		$this->estabelecimento = objectbytable("estabelecimento", $param["CODESTABELEC"], $this->con);
		$this->operacaonota = objectbytable("operacaonota", "VD", $this->con);
		$this->natoperacao = objectbytable("natoperacao", $param["NATOPERACAO"], $this->con);
		$this->tabelapreco = objectbytable("tabelapreco", $param["CODTABELA"], $this->con);
		$this->condpagto = objectbytable("condpagto", $param["CODCONDPAGTO"], $this->con);
		$this->especie = objectbytable("especie", $param["CODESPECIE"], $this->con);
		$this->funcionario = objectbytable("funcionario", $param["CODFUNC"], $this->con);
		$this->emitente = objectbytable("emitente", $this->estabelecimento->getcodemitente(), $this->con);

		$this->dirfotoprod = param("CADASTRO", "DIRFOTOPROD", $this->con);

		$this->empresa = $param["EMPRESA"];
		$this->usuario = $param["USUARIO"];
		$this->senha = $param["SENHA"];
		$this->chave = $param["CHAVE"];
		$this->token = $param["TOKEN"];
		$this->urlfoto = $param["URLFOTO"];

		$this->codproduto = $param["CODPRODUTO"];
		$this->dthrsinc = $param["DTHRSINC"];

		//$this->url_rest = "http://bridge.vtexlab.com.br:80/Vtex.Bridge.Web_deploy";
		$this->url_rest = "http://webservice-{$this->empresa}.vtexcommercestable.com.br";
		//$this->url_soap = "http://webservice-{$this->empresa}.vtexcommerce.com.br/AdminWebService/Service.svc?singleWsdl";
		$this->url_soap = "http://webservice-{$this->empresa}.vtexcommerce.com.br/service.svc?wsdl";

		$this->consultar_idestoque();
	}

	private function consultar_idestoque(){
		$result = $this->service_rest("/api/logistics/pvt/configuration/warehouses", "GET");
		$this->idestoque = $result[0]["id"];
	}

	function consultar_produto($codproduto, $column = null){
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}'/>");
		$Envelope->addChild("Header", null, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", null, $this->namespace_soapenv);
		$ProductGet = $Body->addChild("ProductGet", null, $this->namespace_tem);
		$ProductGet->addChild("idProduct", $codproduto, $this->namespace_tem);

		$result = $this->service_soap("ProductGet", $Envelope->asXML());

		if($result === false){
			return false;
		}


		$result = $this->xml2array($result);
		$result = $result["s:Envelope"]["s:Body"]["ProductGetResponse"]["ProductGetResult"];

		$produto = array();
		foreach($result as $name => $value){
			$name = str_replace("a:", "", $name);
			$produto[$name] = $value;
		}

		if(is_null($column)){
			return $produto;
		}else{
			return $produto[$column];
		}
	}

	private function consultar_cidade_por_cep($cep){
		$cep = removeformat($cep);
		$result = json_decode(file_get_contents("http://viacep.com.br/ws/{$cep}/json/"), TRUE);
		$codoficial = $result["ibge"];
		if(strlen($codoficial) === 0){
			$this->erros[] = "CEP {$cep} n&ailde;o foi encontrado.";
			$_SESSION["ERROR"] = end($this->erros);
			return FALSE;
		}
		$cidade = new Cidade();
		$cidade->setcodoficial($codoficial);
		$arr_cidade = object_array($cidade);
		if(count($arr_cidade) === 0){
			$this->erros[] = "Cidade n&atilde;o encontrada para c&oacute;digo IBGE {$codoficial}.";
			$_SESSION["ERROR"] = end($this->erros);
			return FALSE;
		}
		$cidade = reset($arr_cidade);
		return $cidade;
	}

	private function converter_codproduto2vtex($codproduto){
		//return "99".str_pad($codproduto, 6, "0", STR_PAD_LEFT);
		return $codproduto;
	}

	private function converter_vtex2codproduto($codproduto){
		//return (int) substr($codproduto, 2);
		return $codproduto;
	}

	function debug($texto){
		if($this->debug){
			$file = fopen("../temp/vtex.log", "a+");
			fwrite($file, date("H:i:s")." {$texto}\r\n");
			fclose($file);
		}
	}

	function enviar_departamento(Departamento $departamento){
		$descricao = htmlspecialchars(ucwords(strtolower($departamento->getnome())));
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}' xmlns:vtex='{$this->namespace_vtex}'/>");
		$Envelope->addChild("Header", NULL, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", NULL, $this->namespace_soapenv);
		$CategoryInsertUpdate = $Body->addChild("CategoryInsertUpdate", NULL, $this->namespace_tem);
		$category = $CategoryInsertUpdate->addChild("category", NULL, $this->namespace_tem);
		$category->addChild("Description", $descricao, $this->namespace_vtex);
		$category->addChild("Id", "1".$departamento->getcoddepto(), $this->namespace_vtex);
		$category->addChild("IsActive", TRUE, $this->namespace_vtex);
		$category->addChild("Name", $descricao, $this->namespace_vtex);
		$category->addChild("Title", $descricao, $this->namespace_vtex);

		$result = $this->service_soap("CategoryInsertUpdate", $Envelope->asXML());
		if($result === FALSE){
			return FALSE;
		}

		return TRUE;
	}

	function enviar_grupoprod(GrupoProd $grupoprod){
		$descricao = htmlspecialchars(ucwords(strtolower($grupoprod->getdescricao())));
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}' xmlns:vtex='{$this->namespace_vtex}'/>");
		$Envelope->addChild("Header", NULL, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", NULL, $this->namespace_soapenv);
		$CategoryInsertUpdate = $Body->addChild("CategoryInsertUpdate", NULL, $this->namespace_tem);
		$category = $CategoryInsertUpdate->addChild("category", NULL, $this->namespace_tem);
		$category->addChild("Description", $descricao, $this->namespace_vtex);
		$category->addChild("FatherCategoryId", "1".$grupoprod->getcoddepto(), $this->namespace_vtex);
		$category->addChild("Id", "2".$grupoprod->getcodgrupo(), $this->namespace_vtex);
		$category->addChild("IsActive", TRUE, $this->namespace_vtex);
		$category->addChild("Name", $descricao, $this->namespace_vtex);
		$category->addChild("Title", $descricao, $this->namespace_vtex);

		$result = $this->service_soap("CategoryInsertUpdate", $Envelope->asXML());
		if($result === FALSE){
			return FALSE;
		}

		return TRUE;
	}

	function enviar_marca(Marca $marca){
		$descricao = htmlspecialchars(ucwords(strtolower($marca->getdescricao())));
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}' xmlns:vtex='{$this->namespace_vtex}'/>");
		$Envelope->addChild("Header", NULL, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", NULL, $this->namespace_soapenv);
		$BrandInsertUpdate = $Body->addChild("BrandInsertUpdate", NULL, $this->namespace_tem);
		$brand = $BrandInsertUpdate->addChild("brand", NULL, $this->namespace_tem);
		$brand->addChild("Description", $descricao, $this->namespace_vtex);
		$brand->addChild("Id", $marca->getcodmarca(), $this->namespace_vtex);
		$brand->addChild("IsActive", TRUE, $this->namespace_vtex);
		$brand->addChild("Name", $descricao, $this->namespace_vtex);
		$brand->addChild("Title", $descricao, $this->namespace_vtex);

		$result = $this->service_soap("BrandInsertUpdate", $Envelope->asXML());
		if($result === FALSE){
			return FALSE;
		}

		return TRUE;
	}

	function enviar_produto(Produto $produto, ProdutoEstab $produtoestab = NULL){
		if(!is_object($produtoestab)){
			$produtoestab = objectbytable("produtoestab", array($this->estabelecimento->getcodestabelec(), $produto->getcodproduto()), $this->con);
		}

		// Verifica se o preco esta zerado
		if(round($produtoestab->getprecovrj(), 2) === 0.00){
			return true;
		}

		// Verifica de tem peso e dimensoes
		if($produto->getpesoliq() === 0 || $produto->getaltura() === 0 || $produto->getlargura() === 0 || $produto->getcomprimento() === 0){
			return true;
		}

		if(!$this->enviar_produto_cadastro($produto)){
			return false;
		}

		if(!$this->enviar_produto_informacao($produto, $produtoestab)){
			return false;
		}

		if(!$this->enviar_produto_foto($produto)){
			return true;
		}

		if(!$this->enviar_produto_preco($produtoestab)){
			return false;
		}

		if(!$this->enviar_produto_estoque($produtoestab)){
			return false;
		}

		if($produto->getforalinha() === "N"){
			if(!$this->enviar_produto_ativar($produto)){
				$erro = end($this->erros);
				if(strpos($erro, "imagem associada") === false){
					return false;
				}
				if(!$this->enviar_produto_foto($produto, true)){
					return false;
				}
				if(!$this->enviar_produto_ativar($produto)){
					return false;
				}
			}
		}

		$this->parametro_atualizar("CODPRODUTO", $produto->getcodproduto());

		return true;
	}

	private function enviar_produto_ativar(Produto $produto){
		return true;

		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}'/>");
		$Envelope->addChild("Header", null, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", null, $this->namespace_soapenv);
		$StockKeepingUnitActive = $Body->addChild("StockKeepingUnitActive", NULL, $this->namespace_tem);
		$StockKeepingUnitActive->addChild("idStockKeepingUnit", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_tem);

		$result = $this->service_soap("StockKeepingUnitActive", $Envelope->asXML());
		if($result === false){
			return false;
		}

		return true;
	}

	private function enviar_produto_cadastro(Produto $produto){
		// Carrega a categoria atual do produto no V-Tex
		/*
		$idcategoria = $this->consultar_produto($produto->getcodproduto(), "CategoryId");
		if(strlen($idcategoria) === 0){
			$idcategoria = 8;
		}
		*/

		// Formata o link do produto
		$link = $this->produto_descricao($produto);
		$link = removeformat(removespecial($link));
		$link = str_replace(" ", "-", $link);
		$link = strtolower($link);

		// Monta XML do produto
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}' xmlns:vtex='{$this->namespace_vtex}' xmlns:arr='{$this->namespace_arr}'/>");
		$Envelope->addChild("Header", NULL, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", NULL, $this->namespace_soapenv);
		$ProductInsertUpdate = $Body->addChild("ProductInsertUpdate", NULL, $this->namespace_tem);
		$productVO = $ProductInsertUpdate->addChild("productVO", NULL, $this->namespace_tem);
		$productVO->addChild("BrandId", $produto->getcodmarca(), $this->namespace_vtex);
		$productVO->addChild("CategoryId", "2".$produto->getcodgrupo(), $this->namespace_vtex);
		//$productVO->addChild("DepartmentId", 1, $this->namespace_vtex);
		//$productVO->addChild("Description", htmlspecialchars($produto->getespecificacoes()), $this->namespace_vtex);
		//$productVO->addChild("DescriptionShort", htmlspecialchars($produto->getcomplemento()), $this->namespace_vtex);
		$productVO->addChild("Id", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_vtex);
		$productVO->addChild("IsActive", ($produto->getforalinha() === "N" && $produto->getenviarecommerce() === "S" ? "true" : "false"), $this->namespace_vtex);
		$productVO->addChild("IsVisible", "true", $this->namespace_vtex);
		$productVO->addChild("KeyWords", str_replace(" ", ", ", $this->produto_descricao($produto)), $this->namespace_vtex);
		$ListStoreId = $productVO->addChild("ListStoreId", NULL, $this->namespace_vtex);
		$ListStoreId->addChild("int", 1, $this->namespace_arr);
		$productVO->addChild("LinkId", $link, $this->namespace_vtex);
		//$productVO->addChild("MetaTagDescription", htmlspecialchars($produto->getespecificacoesreduz()), $this->namespace_vtex);
		if (strlen($this->dthrsinc) === 0) {
			$productVO->addChild("Name", $this->produto_descricao($produto), $this->namespace_vtex);
		}
		$productVO->addChild("RefId", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_vtex);
		//$productVO->addChild("Title", $this->produto_descricao($produto)." - ".ucwords(strtolower($this->emitente->getrazaosocial())), $this->namespace_vtex);

		$result = $this->service_soap("ProductInsertUpdate", $Envelope->asXML());
		if($result === FALSE){
			return FALSE;
		}

		return TRUE;
	}

	private function enviar_produto_estoque(ProdutoEstab $produtoestab){
		return $this->service_rest("/api/logistics/pvt/inventory/warehouseitems/setbalance", "POST", array(
			array(
				"wareHouseId" => $this->idestoque,
				"itemId" => $this->converter_codproduto2vtex($produtoestab->getcodproduto()),
				"quantity" => floor($produtoestab->getsldatual())
			)
		));
	}

	private function enviar_produto_foto(Produto $produto, $forcar = false){
		return true;

		if(strlen($this->dirfotoprod) > 0){
			$filename = $produto->getcodproduto()."/0.jpg";
			$filename_complete = $this->dirfotoprod.$filename;
		}

		if(strlen($this->urlfoto) === 0){
			return false;
		}
		if(strlen($filename_complete) === 0){
			return false;
		}
		if(!file_exists($filename_complete)){
			return false;
		}

		$size = getimagesize($filename_complete);
		if($size[0] > 3200 || $size[1] > 3200){
			return false;
		}

		if(!$forcar && strlen($this->dthrsinc) > 0){
			$arr_dtsinc = explode(" ", $this->dthrsinc);
			if(filectime($filename_complete) < strtotime(value_date($arr_dtsinc[0]))){
				return true;
			}
		}

		// Remove as fotos do produto
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}' xmlns:vtex='{$this->namespace_vtex}'/>");
		$Envelope->addChild("Header", NULL, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", NULL, $this->namespace_soapenv);
		$StockKeepingUnitImageRemove = $Body->addChild("StockKeepingUnitImageRemove", NULL, $this->namespace_tem);
		$StockKeepingUnitImageRemove->addChild("stockKeepingUnitId", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_tem);
		$result = $this->service_soap("StockKeepingUnitImageRemove", $Envelope->asXML());
		if($result === false){
			return false;
		}

		// Envia a nova foto do produto
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}' xmlns:vtex='{$this->namespace_vtex}'/>");
		$Envelope->addChild("Header", NULL, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", NULL, $this->namespace_soapenv);
		$ImageServiceInsertUpdate = $Body->addChild("ImageInsertUpdate", NULL, $this->namespace_tem);
		$image = $ImageServiceInsertUpdate->addChild("image", NULL, $this->namespace_tem);
		$image->addChild("Name", "", $this->namespace_vtex);
		$image->addChild("StockKeepingUnitId", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_vtex);
		$image->addChild("Url", $this->urlfoto.$filename, $this->namespace_vtex);
		$image->addChild("fileId", $produto->getcodproduto(), $this->namespace_vtex);
		$result = $this->service_soap("ImageInsertUpdate", $Envelope->asXML());
		if($result === false){
			return false;
		}

		return true;
	}

	private function enviar_produto_informacao(Produto $produto, ProdutoEstab $produtoestab){
		$Envelope = new SimpleXMLElement("<soapenv:Envelope xmlns:soapenv='{$this->namespace_soapenv}' xmlns:tem='{$this->namespace_tem}' xmlns:vtex='{$this->namespace_vtex}'/>");
		$Envelope->addChild("Header", NULL, $this->namespace_soapenv);
		$Body = $Envelope->addChild("Body", NULL, $this->namespace_soapenv);
		$StockKeepingUnitInsertUpdate = $Body->addChild("StockKeepingUnitInsertUpdate", NULL, $this->namespace_tem);
		$stockKeepingUnitVO = $StockKeepingUnitInsertUpdate->addChild("stockKeepingUnitVO", NULL, $this->namespace_tem);
		$stockKeepingUnitVO->addChild("CostPrice", number_format($produtoestab->getcustorep(), 2, ".", ""), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("CubicWeight", 100, $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("Height", (strlen($produto->getaltura()) > 0 ? $produto->getaltura() : 0), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("Id", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("IsActive", ($produto->getforalinha() == "N" && $produto->getenviarecommerce() == "S" ? "true" : "false"), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("isAvaiable", "true", $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("IsKit", "false", $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("Length", (strlen($produto->getcomprimento()) > 0 ? $produto->getcomprimento() : 0), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("ListPrice", $produtoestab->getprecovrj(), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("ModalId", 1, $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("Name", $this->produto_descricao($produto), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("Price", $produtoestab->getprecovrj(), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("ProductId", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("RefId", $this->converter_codproduto2vtex($produto->getcodproduto()), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("RewardValue", 0, $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("UnitMultiplier", 1, $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("WeightKg", ($produto->getpesoliq() * 1000), $this->namespace_vtex);
		$stockKeepingUnitVO->addChild("Width", (strlen($produto->getlargura()) > 0 ? $produto->getlargura() : 0), $this->namespace_vtex);

		$result = $this->service_soap("StockKeepingUnitInsertUpdate", $Envelope->asXML());
		if($result === FALSE){
			return FALSE;
		}

		return TRUE;
	}

	private function enviar_produto_preco(ProdutoEstab $produtoestab){
		return $this->service_rest("/api/pricing/pvt/price-sheet", "POST", array(
				array(
					"itemId" => $this->converter_codproduto2vtex($produtoestab->getcodproduto()),
					"salesChannel" => "1",
					"price" => ($produtoestab->getprecoatc() > 0 ? $produtoestab->getprecoatc() : $produtoestab->getprecovrj()),
					"listPrice" => $produtoestab->getprecovrj()
				)
		));
	}

	function erros(){
		return $this->erros;
	}

	private function limpar_string($texto){
		//$texto = str_replace("&", "E", $texto);

		return $texto;
	}

	private function parametro_atualizar($nome, $valor){
		$param = parse_ini_string($this->parametro->getvalor());
		$param[$nome] = $valor;
		$arr_ini = array();
		foreach($param as $name => $value){
			$arr_ini[] = $name." = ".$value;
		}
		$this->parametro->setvalor(implode("\n", $arr_ini));
		return $this->parametro->save();
	}

	private function produto_descricao(Produto $produto){
		$descricao = $produto->getdescricaofiscal2();
		if(strlen($descricao) === 0){
			$descricao = $produto->getdescricaofiscal();
		}
		$descricao = iconv("UTF-8", "UTF-8//IGNORE", $descricao);
		$descricao = htmlspecialchars($descricao);
		return $descricao;
	}

	function receber_pedidos(){
		$arr_status = array(
			"P" => "invoiced",
			"C" => "cancel"
		);

		$limite_por_pagina = 200;

		$this->con->start_transaction();

		foreach($arr_status as $status => $status_vtex){
			$service = "/api/oms/pvt/orders?f_status={$status_vtex}&per_page={$limite_por_pagina}";
			$result = $this->service_rest($service);
			if($result === FALSE){
				$this->con->rollback();
				return FALSE;
			}

			foreach($result["list"] as $i => $order){
				$refpedido = $order["orderId"];
				if(strlen($refpedido) === 0){
					continue;
				}
				$pedido = objectbytable("pedido", NULL, $this->con);
				$pedido->setrefpedido($refpedido);
				$arr_pedido = object_array($pedido);

				if(count($arr_pedido) === 0){
					// Carrega os detalhes do pedido
					$service = "/api/oms/pvt/orders/{$refpedido}";
					$result = $this->service_rest($service);
					if($result === FALSE){
						$this->con->rollback();
						return FALSE;
					}

					// Carrega/inclui o cliente
					$cliente = $this->verificar_cliente($result["clientProfileData"], $result["shippingData"]);
					if($cliente === FALSE){
						return FALSE;
					}

					// Verifica se venda interestadual
					$natoperacao = $this->natoperacao;
					if($this->estabelecimento->getuf() != $cliente->getufres()){
						if(strlen($natoperacao->getnatoperacaointer()) > 0){
							$natoperacao = objectbytable("natoperacao", $natoperacao->getnatoperacaointer(), $this->con);
						}
					}

					// Data de entrega
					$dtentrega = $result["shippingData"]["logisticsInfo"][0]["shippingEstimateDate"];
					$dtentrega = substr($dtentrega, 0, 10);

					// Instancia classe que calcula os totais dos produtos
					$itemcalculo = new ItemCalculo($this->con);
					$itemcalculo->setestabelecimento($this->estabelecimento);
					$itemcalculo->setoperacaonota($this->operacaonota);
					$itemcalculo->setnatoperacao($natoperacao);
					$itemcalculo->setparceiro($cliente);

					// Percorre todos os itens do pedido
					$arr_itpedido = array();
					foreach($result["items"] as $j => $item){
						// Pedido com item sem ser enviado pelo WebSac deve ser ignorado
						if(strlen($item["refId"]) === 0){
							continue 2;
						}

						$produto = objectbytable("produto", $item["refId"], $this->con);
						$piscofins = objectbytable("piscofins", $produto->getcodpiscofinssai(), $this->con);
						$embalagem = objectbytable("embalagem", $produto->getcodembalvda(), $this->con);
						$classfiscalnfe = objectbytable("classfiscal", $produto->getcodcfnfe(), $this->con);
						$classfiscalnfs = objectbytable("classfiscal", $produto->getcodcfnfs(), $this->con);

						$tributacaoproduto = new TributacaoProduto($this->con);
						$tributacaoproduto->setestabelecimento($this->estabelecimento);
						$tributacaoproduto->setnatoperacao($natoperacao);
						$tributacaoproduto->setoperacaonota($this->operacaonota);
						$tributacaoproduto->setparceiro($cliente);
						$tributacaoproduto->setproduto($produto);

						$tributacaoproduto->buscar_dados();

						$natoperacao_it = $tributacaoproduto->getnatoperacao();
						$tptribicms = $tributacaoproduto->gettptribicms();
						$aliqicms = $tributacaoproduto->getaliqicms(TRUE);
						$redicms = $tributacaoproduto->getredicms(TRUE);
						$aliqiva = $tributacaoproduto->getaliqiva(TRUE);
						$valorpauta = $tributacaoproduto->getvalorpauta(TRUE);
						$tipoipi = $tributacaoproduto->gettipoipi();
						$valipi = $tributacaoproduto->getvalipi(TRUE);
						$percipi = $tributacaoproduto->getpercipi(TRUE);

						$itpedido = objectbytable("itpedido", NULL, $this->con);
						$itpedido->setseqitem($j + 1);
						$itpedido->setcodestabelec($this->estabelecimento->getcodestabelec());
						$itpedido->setnatoperacao($natoperacao_it->getnatoperacao());
						$itpedido->setcodproduto($produto->getcodproduto());
						$itpedido->setcodunidade($embalagem->getcodunidade());
						$itpedido->setqtdeunidade($embalagem->getquantidade());
						$itpedido->setquantidade($item["quantity"]);
						$itpedido->setpreco($item["price"]);
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
						$itpedido->setbonificado("N");
						$itpedido->setdtentrega($dtentrega);

						$itemcalculo->setitem($itpedido);
						$itemcalculo->setclassfiscalnfe($classfiscalnfe);
						$itemcalculo->setclassfiscalnfs($classfiscalnfs);
						$itemcalculo->calcular();
						$arr_itpedido[] = $itpedido;
					}

					$pedido = objectbytable("pedido", NULL, $this->con);
					$pedido->setoperacao("VD");
					$pedido->setcodestabelec($this->estabelecimento->getcodestabelec());
					$pedido->setrefpedido($result["orderId"]);
					$pedido->setstatus($status);
					$pedido->settipoparceiro("C");
					$pedido->setcodparceiro($cliente->getcodcliente());
					$pedido->setcodespecie($this->especie->getcodespecie()); // VERIFICAR
					$pedido->setcodcondpagto($this->condpagto->getcodcondpagto()); // VERIFICAR
					$pedido->setnatoperacao($this->natoperacao->getnatoperacao());
					$pedido->settipoparceiro("C");
					$pedido->setcodparceiro($cliente->getcodcliente());
					$pedido->setnatoperacao($natoperacao->getnatoperacao());
					$pedido->setcodtransp($codtransp);
					$pedido->setdtemissao(date("d/m/Y"));
					$pedido->setdtentrega(date("d/m/Y"));
					$pedido->setstatus("P");
					$pedido->settipopreco("V");
					$pedido->setbonificacao("N");
					$pedido->setfinalidade("1");
					$pedido->settipoemissao("1");
					$pedido->setmodfrete("9");
					$pedido->setcodtabela($this->tabelapreco->getcodtabela());
					$pedido->setobservacao("PEDIDO VINDO DO E-COMMERCE");
					$pedido->setorigemreg("E");

					$pedido->flag_itpedido(TRUE);
					$pedido->itpedido = $arr_itpedido;

					$pedido->calcular_totais();
					var_dump("Pedido será salvo");
					if(!$pedido->save()){
						$this->con->rollback();
						return FALSE;
					}
				}elseif($status === "C"){
					$pedido = reset($arr_pedido);
					if($pedido->getstatus() !== $status){
						$pedido->setstatus($status);
						if(!$pedido->save()){
							$this->con->rollback();
							return FALSE;
						}
					}
				}
			}
		}
		$this->con->commit();
		return TRUE;
	}

	private function service_rest($service, $method = "GET", $json = NULL){

		$url = $this->url_rest.$service;

		if(is_array($json)){
			$json = json_encode($json);
		}

		$this->debug("Metodo: {$service}\r\nEntrada:\r\n{$json}\r\n");

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			"Content-type: application/json",
			"x-vtex-api-appKey: $this->chave",
			"x-vtex-api-appToken: $this->token"
		));
		//curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		//curl_setopt($curl, CURLOPT_USERPWD, $this->usuario.":".$this->senha);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
		switch($method){
			case "DELETE":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
				break;
			case "GET":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
				break;
			case "POST":
				curl_setopt($curl, CURLOPT_POST, TRUE);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
				break;
		}

		$result = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);

		if($info["http_code"] == 200){
			$this->debug("Saida: OK\r\n{$result}\r\n");
			return json_decode($result, TRUE);
		}else{
			$this->erros[] = "HTTP Code {$info["http_code"]}\r\n".var_export($info, TRUE);
			$this->debug("Saida:\r\n".end($this->erros)."\r\n");
			return FALSE;
		}
	}

	private function service_soap($name, $xml){
		$this->debug("Metodo: {$name}\r\nEntrada:\r\n{$xml}");

		for($i = 0; $i < 10; $i++){
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_URL, $this->url_soap);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_TIMEOUT, 20);
			curl_setopt($curl, CURLOPT_POST, TRUE);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				"Content-type: text/xml; charset=utf-8",
				"SOAPAction: http://tempuri.org/IService/{$name}",
				"Authorization: Basic ".base64_encode("{$this->usuario}:{$this->senha}"),
				"Content-length: ".strlen($xml),
				"x-vtex-api-appKey: $this->chave",
				"x-vtex-api-appToken: $this->token"
			));
			$result = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			if($info["http_code"] == "200"){
				$this->debug("Saida: OK\r\n{$result}\r\n");
				return $result;
			}else{
				usleep(500);
			}
		}

		$this->erros[] = "HTTP Code {$info["http_code"]}\r\n".$result;
		$this->debug("Saida:\r\n".end($this->erros)."\r\n");
		return FALSE;
	}

	function sincronizar(){
		// Carrega os produtos devem ser sincronizados
		$arr_query = array();
		$arr_codproduto = array();
		if(strlen($this->dthrsinc) > 0){
			$arr_dtsinc = explode(" ", $this->dthrsinc);
			$dtsinc = value_date($arr_dtsinc[0]);
			$arr_query[] = "SELECT DISTINCT chave AS codproduto FROM historico WHERE tabela = 'produto' AND operacao IN ('I', 'U') AND dtcriacao >= '{$dtsinc}' ORDER BY 1";
			$arr_query[] = "SELECT DISTINCT codproduto FROM logpreco WHERE tipo IN ('PV', 'PA') AND codestabelec = {$this->estabelecimento->getcodestabelec()} AND data >= '{$dtsinc}' ORDER BY 1";
			$arr_query[] = "SELECT DISTINCT codproduto FROM produtoestabsaldo WHERE codestabelec = {$this->estabelecimento->getcodestabelec()} AND data >= '{$dtsinc}' ORDER BY 1";
		}else{
			if(strlen($this->codproduto) > 0){
				$arr_query[] = "SELECT codproduto FROM produto WHERE foralinha = 'N' AND codproduto > {$this->codproduto} ORDER BY codproduto";
			}else{
				$arr_query[] = "SELECT codproduto FROM produto WHERE foralinha = 'N' ORDER BY codproduto";
			}
		}
		foreach($arr_query as $query){
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			foreach($arr as $row){

				// REMOVER
				if($row["codproduto"] == 52628){
					continue;
				}

				$arr_codproduto[] = $row["codproduto"];
			}
		}
		sort($arr_codproduto);

		// Filtra apenas os produtos que tem foto
		/*
		$arr_codproduto_aux = array();
		if(strlen($this->dirfotoprod) > 0){
			foreach($arr_codproduto as $codproduto){
				$filename = "{$this->dirfotoprod}/{$codproduto}/0.jpg";
				if(!file_exists($filename)){
					continue;
				}
				$size = getimagesize($filename);
				if($size[0] > 3200 || $size[1] > 3200){
					continue;
				}
				$arr_codproduto_aux[] = $codproduto;
			}
		}
		$arr_codproduto = $arr_codproduto_aux;
		unset($arr_codproduto_aux);
		*/

		$this->debug("Total de produtos a serem enviados: ".count($arr_codproduto));

		// Carrega os produtos a partir dos codigos
		$arr_produto = object_array_key(objectbytable("produto", NULL, $this->con), $arr_codproduto, "codproduto");

		// Aplica alguns filtros nos produtos
		$arr_produto_aux = array();
		foreach($arr_produto as $produto){
			if($produto->getenviarecommerce() === "N"){
//				continue;
			}
			if(strlen($produto->getcodmarca()) === 0){
				continue;
			}
			$arr_produto_aux[] = $produto;
		}
		$arr_produto = $arr_produto_aux;
		unset($arr_produto_aux);

		// Carrega os departamentos dos produtos
		$arr_coddepto = array();
		foreach($arr_produto as $produto){
			$arr_coddepto[] = $produto->getcoddepto();
		}
		$arr_departamento = object_array_key(objectbytable("departamento", NULL, $this->con), $arr_coddepto, "coddepto");

		// Carrega os grupos dos produtos
		$arr_codgrupo = array();
		foreach($arr_produto as $produto){
			$arr_codgrupo[] = $produto->getcodgrupo();
		}
		$arr_grupoprod = object_array_key(objectbytable("grupoprod", NULL, $this->con), $arr_codgrupo, "codgrupo");

		// Carrega as marcas dos produtos
		$arr_codmarca = array();
		foreach($arr_produto as $produto){
			$arr_codmarca[] = $produto->getcodmarca();
		}
		$arr_marca = object_array_key(objectbytable("marca", NULL, $this->con), $arr_codmarca, "codmarca");

		// Envia os departamentos para o V-Tex
		foreach($arr_departamento as $departamento){
			if(!$this->enviar_departamento($departamento)){
				return FALSE;
			}
		}

		// Envia os grupos para o V-Tex
		foreach($arr_grupoprod as $grupoprod){
			if(!$this->enviar_grupoprod($grupoprod)){
				return FALSE;
			}
		}

		// Envia as marcas para o V-Tex
		foreach($arr_marca as $marca){
			if(!$this->enviar_marca($marca)){
				return FALSE;
			}
		}

		// Envia os produtos para o V-Tex
		foreach($arr_produto as $produto){
			if(!$this->enviar_produto($produto)){
				return FALSE;
			}
		}

		// Recebe os pedidos do V-Tex
		if(!$this->receber_pedidos()){
			return FALSE;
		}

		// Atualiza a data e hora de sincronizacao no parametro
		$this->parametro_atualizar("DTHRSINC", date("Y-m-d H:i:s"));

		$this->debug("Sincronização finalizada com sucesso!");

		return TRUE;
	}

	private function verificar_cliente($clientProfileData, $shippingData){
		$cpfcnpj = trim(removeformat($clientProfileData["document"]));
		if(strlen($cpfcnpj) === 0){
			$this->erros[] = "JSON de cliente &eacute; inv&aacute;lido.";
			$_SESSION["ERROR"] = end($this->erros);
			return FALSE;
		}
		switch($clientProfileData["documentType"]){
			case "cpf":
				$tppessoa = "F";
				$cpfcnpj = substr($cpfcnpj, 0, 3).".".substr($cpfcnpj, 3, 3).".".substr($cpfcnpj, 6, 3)."-".substr($cpfcnpj, 9, 2);
				break;
			case "cnpj":
				$tppessoa = "J";
				$cpfcnpj = substr($cpfcnpj, 0, 2).".".substr($cpfcnpj, 2, 3).".".substr($cpfcnpj, 5, 3)."/".substr($cpfcnpj, 8, 4)."-".substr($cpfcnpj, 12, 2);
				break;
		}
		$cliente = objectbytable("cliente", NULL, $this->con);
		$cliente->setcpfcnpj($cpfcnpj);
		$arr_cliente = object_array($cliente);
		if(count($arr_cliente) === 0){
			$telefone = $clientProfileData["phone"];
			$telefone = "(".substr($telefone, 3, 2).") ".substr($telefone, 5, (strlen($telefone) === 14 ? 5 : 4))."-".substr($telefone, -4);

			$cep = $shippingData["postalCode"];
			$cep = removeformat($cep);
			$cep = substr($cep, 0, 5)."-".substr($cep, 5, 3);
			$cidade = $this->consultar_cidade_por_cep($cep);
			if($cidade === FALSE){
				return FALSE;
			}

			$cliente = objectbytable("cliente", NULL, $this->con);
			$cliente = new Cliente();
			$cliente->setcodstatus(1); // Normalmente o codigo 1 eh o ativo
			$cliente->setnome($clientProfileData["firstName"]." ".$clientProfileData["lastName"]);
			$cliente->settppessoa($tppessoa);
			$cliente->setcpfcnpj($cpfcnpj);
			$cliente->setemail($clientProfileData["email"]);
			$cliente->setenviaemailmkt("S");
			$cliente->setcontribuinteicms("N");
			$cliente->setorgaopublico("N");
			// Dados de residencia
			$cliente->setfoneres($telefone);
			$cliente->setcepres($cep);
			$cliente->setcodcidaderes($cidade->getcodcidade());
			$cliente->setufres($cidade->getuf());
			$cliente->setbairrores($shippingData["neighborhood"]);
			$cliente->setenderent($shippingData["street"]);
			$cliente->setnumerores($shippingData["number"]);
			$cliente->setcomplementores($shippingData["complement"]);
			// Dados de entrega
			$cliente->setfoneent($telefone);
			$cliente->setcepent($cep);
			$cliente->setcodcidadeent($cidade->getcodcidade());
			$cliente->setufent($cidade->getuf());
			$cliente->setbairroent($shippingData["neighborhood"]);
			$cliente->setenderent($shippingData["street"]);
			$cliente->setnumeroent($shippingData["number"]);
			$cliente->setcomplementoent($shippingData["complement"]);
			// Dados de faturamento
			$cliente->setfonefat($telefone);
			$cliente->setcepfat($cep);
			$cliente->setcodcidadefat($cidade->getcodcidade());
			$cliente->setuffat($cidade->getuf());
			$cliente->setbairrofat($shippingData["neighborhood"]);
			$cliente->setenderent($shippingData["street"]);
			$cliente->setnumerofat($shippingData["number"]);
			$cliente->setcomplementofat($shippingData["complement"]);

			if(!$cliente->save()){
				return FALSE;
			}
		}else{
			$cliente = reset($arr_cliente);
		}
		return $cliente;
	}

	private function xml2array($xml, $get_attributes = 1, $priority = 'tag'){
		$parser = xml_parser_create("");
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($xml), $xml_values);
		xml_parser_free($parser);
		if(!$xml_values)
			return; //Hmm...
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();
		$current = & $xml_array;
		$repeated_tag_index = array();
		foreach($xml_values as $data){
			unset($attributes, $value);
			extract($data);
			$result = array();
			$attributes_data = array();
			if(isset($value)){
				if($priority == 'tag')
					$result = $value;
				else
					$result['value'] = $value;
			}
			if(isset($attributes) and $get_attributes){
				foreach($attributes as $attr => $val){
					if($priority == 'tag')
						$attributes_data[$attr] = $val;
					else
						$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}
			if($type == "open"){
				$parent[$level - 1] = & $current;
				if(!is_array($current) or ( !in_array($tag, array_keys($current)))){
					$current[$tag] = $result;
					if($attributes_data)
						$current[$tag.'_attr'] = $attributes_data;
					$repeated_tag_index[$tag.'_'.$level] = 1;
					$current = & $current[$tag];
				}
				else{
					if(isset($current[$tag][0])){
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
						$repeated_tag_index[$tag.'_'.$level] ++;
					}else{
						$current[$tag] = array(
							$current[$tag],
							$result
						);
						$repeated_tag_index[$tag.'_'.$level] = 2;
						if(isset($current[$tag.'_attr'])){
							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}
					}
					$last_item_index = $repeated_tag_index[$tag.'_'.$level] - 1;
					$current = & $current[$tag][$last_item_index];
				}
			}elseif($type == "complete"){
				if(!isset($current[$tag])){
					$current[$tag] = $result;
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority == 'tag' and $attributes_data)
						$current[$tag.'_attr'] = $attributes_data;
				}
				else{
					if(isset($current[$tag][0]) and is_array($current[$tag])){
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
						if($priority == 'tag' and $get_attributes and $attributes_data){
							$current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag.'_'.$level] ++;
					}else{
						$current[$tag] = array(
							$current[$tag],
							$result
						);
						$repeated_tag_index[$tag.'_'.$level] = 1;
						if($priority == 'tag' and $get_attributes){
							if(isset($current[$tag.'_attr'])){
								$current[$tag]['0_attr'] = $current[$tag.'_attr'];
								unset($current[$tag.'_attr']);
							}
							if($attributes_data){
								$current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag.'_'.$level] ++; //0 and 1 index is already taken
					}
				}
			}elseif($type == 'close'){
				$current = & $parent[$level - 1];
			}
		}
		return ($xml_array);
	}

}
