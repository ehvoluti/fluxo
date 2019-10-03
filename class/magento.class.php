<?php
require_once("websac/require_file.php");
require_file("def/require_php.php");
require_file("class/interligapdv.class.php");
require_file("class/leituraonline.class.php");
require_file("class/itemcalculo.class.php");
require_file("class/tributacaoproduto.class.php");

$con = new Connection();

$processo = objectbytable("processo","MAGENTO",$con);
$param_sistema_magento = parse_ini_string(param("SISTEMA","MAGENTO",$con));


if($processo->verificar_intervalo()){
	$proxy = new SoapClient($param_sistema_magento["wsdl"]);
	$sessionId = $proxy->login($param_sistema_magento["wsdl"],$param_sistema_magento["senha"]);

	// I N I   C A D A S T R O   D E   C L I E N T E S
	$lista_cliente = $proxy->call($sessionId,'customer.list',array(array("group_id" => "1")));

	$con->start_transaction();
	foreach($lista_cliente as $_cliente){
		$result = $con->query("SELECT COUNT(*) FROM cliente WHERE email = '".$_cliente["email"]."'");
		$exist_cliente = $result->fetchColumn();

		if($exist_cliente == "0"){

			echo "<br>".$_cliente["email"]." - insert websac cliente";


			$info_cliente = $proxy->call($sessionId,'customer.info',$_cliente["customer_id"]);
			$address_cliente = reset($proxy->call($sessionId,'customer_address.list',$_cliente["customer_id"]));

			if(strlen($address_cliente["postcode"]) == 0){
				continue;
			}

			$cliente = objectbytable("cliente",null,$con);

			$cliente->setnome($info_cliente["firstname"]." ".$info_cliente["lastname"]);
			$cliente->setrazaosocial($info_cliente["firstname"]." ".$info_cliente["lastname"]);
			$cliente->setcpfcnpj("");
			$cliente->setemail($info_cliente["email"]);
			$cliente->setcodstatus("1");
			$cliente->setenviaemailmkt("S");
			$cliente->settppessoa("F");

			$info = json_decode(file_get_contents("http://viacep.com.br/ws/".removespecial($address_cliente["postcode"])."/json/"));
			$cep = substr($address_cliente["postcode"],0,5)."-".substr($address_cliente["postcode"],5,3);

			$cliente->setcepres($cep);
			$cliente->setenderres(strtoupper($info[0]));
			$cliente->setcodcidaderes($info[4]);
			$cliente->setbairrores($info[1]);
			$cliente->setufres($info[3]);
			$cliente->setcodpaisres("01058");
			$cliente->setfoneres($address_cliente["telephone"]);

			$cliente->setcepfat($cep);
			$cliente->setenderfat(strtoupper($info[0]));
			$cliente->setcodcidadefat($info[4]);
			$cliente->setbairrofat($info[1]);
			$cliente->setuffat($info[3]);
			$cliente->setcodpaisfat("01058");
			$cliente->setfonefat($address_cliente["telephone"]);

			$cliente->setcepent($cep);
			$cliente->setenderent(strtoupper($info[0]));
			$cliente->setcodcidadeent($info[4]);
			$cliente->setbairroent($info[1]);
			$cliente->setufent($info[3]);
			$cliente->setcodpaisent("01058");
			$cliente->setfoneent($address_cliente["telephone"]);

			if(!$cliente->save()){
				$con->rollback();
				die("<br>Cliente ".$info_cliente["email"]." n&atilde;o foi salvo. ".$_SESSION["ERROR"]);
			}
			$proxy->call($sessionId,"customer.update",array('customerId' => $_cliente["customer_id"],'customerData' => array("group_id" => "3")));
		}
	}
	$con->commit();

	// F I M   C A D A S T R O   D E   C L I E N T E S
	// I N I   C A D A S T R O   D E   P R O D U T O
	$con->start_transaction();
	$query = "SELECT produto.codproduto, produto.descricao, produto.descricaofiscal, produtoestab.precovrj, ";
	$query .= "produtoestab.sldatual , produtoestab.disponivel, produto.datainclusao ";
	$query .= "FROM produto ";
	$query .= "INNER JOIN produtoestab ON (produto.codproduto = produtoestab.codproduto) ";
	$query .= "WHERE produtoestab.codestabelec = ".$param_sistema_magento["codestabelec"]." AND ";
	if($processo->getdataexecucao() == 0){
		$query .= "TRUE ";
	}else{
		$query .= "((produto.datalog > '".$processo->getdataexecucao()."' AND produto.horalog > '".$processo->gethoraexecucao()."') OR (produtoestab.datalog >= '".$processo->getdataexecucao()."' AND produtoestab.horalog > '".$processo->gethoraexecucao()."')) ";
	}

	$result = $con->query($query);
	$arr_produto = $result->fetchAll();


	foreach($arr_produto as $produto){
		echo "<br>".$produto["codproduto"]." - ";
		$mag_produto = ($proxy->call($sessionId,'catalog_product.list',array(array('sku' => $produto["codproduto"]))));

		if(strlen($mag_produto[0]["product_id"]) > 0){
			echo "update produto";
			$productId = $mag_produto[0]["product_id"];
			$stockItemData = array(
				"name" => $produto["descricao"],
				"description" => $produto["descricaofiscal"],
				"short_description" => $produto["descricao"],
				'price' => $produto["precovrj"]
			);

			$result = $proxy->call(
				$sessionId,'product.update',array(
				$productId,
				$stockItemData
				)
			);
		}else{
			echo "insert produto";
			$attributeSets = $proxy->call($sessionId,"product_attribute_set.list");
			$attributeSet = current($attributeSets);

			$proxy->call($sessionId,"catalog_product.create",array("simple",$attributeSet["set_id"],$produto["codproduto"],array(
					"categories" => array(2),
					"websites" => array(1),
					"name" => $produto["descricao"],
					"description" => $produto["descricaofiscal"],
					"short_description" => $produto["descricao"],
					"status" => "0",
					"visibility" => "4",
					"price" => $produto["precovrj"],
					"tax_class_id" => 2,
					"meta_title" => $produto["descricao"],
					"meta_keyword" => $produto["descricao"],
					"meta_description" => $produto["descricao"],
					"qty" => $produto["sldatual"]
				)));
		}
	}
	$con->commit();
	// F I M   C A D A S T R O   D E   P R O D U T O

	// I N I   C A D A S T R O   D E   P E D I D O
	$con->start_transaction();
	$lista_venda = $proxy->call($sessionId,'order.list',array(array('status' => 'pending')));
	ver($lista_venda);

	foreach($lista_venda as $venda){
		$result = $con->query("SELECT codcliente, cpfcnpj FROM cliente WHERE email = '".$venda["customer_email"]."' LIMIT 1");
		$cliente_ = $result->fetch(2);
		$cliente = objectbytable("cliente",$cliente_["codcliente"],$con);

		$operacaonota = objectbytable("operacaonota","VD",$con);
		$estabelecimento = objectbytable("estabelecimento",$param_sistema_magento["codestabelec"],$con);
		$natoperacao = objectbytable("natoperacao",$param_sistema_magento["cfop"],$con);
		$tabelapreco = objectbytable("tabelapreco",$param_sistema_magento["tabelapreco"],$con);
		$cliente = objectbytable("cliente",$codcliente,$con);

		$itemcalculo = new ItemCalculo($con);
		$itemcalculo->setestabelecimento($estabelecimento);
		$itemcalculo->setoperacaonota($operacaonota);
		$itemcalculo->setnatoperacao($natoperacao);
		$itemcalculo->setparceiro($cliente);

		$lista_itvenda = $proxy->call($sessionId,'sales_order.info',$venda["increment_id"]);

		// I N I   C A D A S T R O   D E   I T E N S   D O   P E D I D O
		$arr_itpedido = array();
		foreach($lista_itvenda["items"] as $itvenda){
			$produto = objectbytable("produto",$itvenda["sku"],$con);
			$piscofins = objectbytable("piscofins",$produto->getcodpiscofinssai(),$con);
			$embalagem = objectbytable("embalagem",$produto->getcodembalvda(),$con);
			$classfiscalnfe = objectbytable("classfiscal",$produto->getcodcfnfe(),$con);
			$classfiscalnfs = objectbytable("classfiscal",$produto->getcodcfnfs(),$con);

			$tributacaoproduto = new TributacaoProduto($con);
			$tributacaoproduto->setestabelecimento($estabelecimento);
			$tributacaoproduto->setnatoperacao($natoperacao);
			$tributacaoproduto->setoperacaonota($operacaonota);
			$tributacaoproduto->setparceiro($cliente);
			$tributacaoproduto->setproduto($produto);

			$tributacaoproduto->buscar_dados();

			$natoperacao_it = $tributacaoproduto->getnatoperacao();
			$classfiscal = $tributacaoproduto->getclassfiscal();

			$tptribicms = $tributacaoproduto->gettptribicms();
			$aliqicms = $tributacaoproduto->getaliqicms(TRUE);
			$redicms = $tributacaoproduto->getredicms(TRUE);
			$aliqiva = $tributacaoproduto->getaliqiva(TRUE);
			$valorpauta = $tributacaoproduto->getvalorpauta(TRUE);
			$tipoipi = $tributacaoproduto->gettipoipi();
			$valipi = $tributacaoproduto->getvalipi(TRUE);
			$percipi = $tributacaoproduto->getpercipi(TRUE);
			$percdescto = $tributacaoproduto->getpercdescto(TRUE);

			$itpedido = objectbytable("itpedido",NULL,$con);
			$itpedido->setseqitem($i + 1);
			$itpedido->setcodestabelec($param_sistema_magento["codestabelec"]);
			$itpedido->setnatoperacao($natoperacao_it->getnatoperacao());
			$itpedido->setcodproduto($itvenda["sku"]);
			$itpedido->setcodunidade($embalagem->getcodunidade());
			$itpedido->setqtdeunidade(1);
			$itpedido->setquantidade($itvenda["qty_ordered"]);
			$itpedido->setpreco($itvenda["price"]);
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
			$itpedido->setpercdescto($itpedido->getpercdescto() + $percdescto);
			$itpedido->setbonificado("N");
			$itpedido->setdtentrega(date("d/m/Y"));

			$itemcalculo->setitem($itpedido);
			$itemcalculo->setclassfiscalnfe($classfiscalnfe);
			$itemcalculo->setclassfiscalnfs($classfiscalnfs);
			$itemcalculo->calcular();
			$arr_itpedido[] = $itpedido;
		}
		$pedido = objectbytable("pedido",NULL,$con);
		$pedido->setoperacao("VD");
		$pedido->setcodestabelec($param_sistema_magento["codestabelec"]);
		$pedido->setcodfunc($codfunc);
		$pedido->settipoparceiro("C");
		$pedido->setcodparceiro($cliente_["codcliente"]);

		$pedido->setcodespecie(1);
		$pedido->setcodcondpagto(1);
		$pedido->setnatoperacao($param_sistema_magento["cfop"]);

		$pedido->setdtemissao(date("d/m/Y"));
		$pedido->setdtentrega(date("d/m/Y"));
		$pedido->setstatus("P");
		$pedido->settipopreco("V");
		$pedido->setbonificacao("N");
		$pedido->setfinalidade("1");
		$pedido->settipoemissao("1");
		$pedido->setmodfrete("9");
		$pedido->setcodtabela($tabelapreco->getcodtabela());

		$pedido->flag_itpedido(TRUE);
		$pedido->itpedido = $arr_itpedido;
		$pedido->calcular_totais();
		if(!$pedido->save()){
			$con->rollback();
			die("<br><br>Erro ao gravar pedido ".$_SESSION["ERROR"]);
		}
		// F I M   C A D A S T R O   D E   I T E N S   D O   P E D I D O
		$proxy->call($sessionId,'sales_order.hold',$venda["increment_id"]);
		echo "<br><br>".$pedido->getnumpedido()."<br><br>";
	}
	// F I M   C A D A S T R O   D E   P E D I D O


	if(!$processo->atualizar_intervalo()){
		$con->rollback();
		die();
	}

	$con->commit();
}
function ver($ver){
	echo "<pre>";
	var_dump($ver);
	echo "</pre>";
	die;
}

?>