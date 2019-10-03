<?php

require_once("../class/pdvconfig.class.php");
require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class Scanntech{

	private $con; // Conexao com o banco de dados
	private $pdvconfig;
	private $pdvvenda;
	private $pdvfinalizador;
	private $server;
	private $user;
	private $password;
	private $idempresa;
	private $idlocal;
	private $arr_codproduto; // Controlar quais registro devem ser marcados como sincronizados no banco de dados

	function __construct(){
		unset($_SESSION["ERROR"]);
		$_SESSION["ERROR_PDV"] = array();
		$this->limpar_dados();
	}

	private function carregar_configuracao(){
		$estabelecimento = $this->pdvconfig->getestabelecimento();

		$this->server = $estabelecimento->getbeservidor();
		$this->user = $estabelecimento->getbeusuario();
		$this->password = $estabelecimento->getbesenha();
		$this->idempresa = $estabelecimento->getbeempresa();
		$this->idlocal = $estabelecimento->getbelocal();

		$link_estabelecimento = "<br><a onclick=\"openProgram('Estabel','codestabelec=".$estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro do estabelecimento.";
		if(strlen($this->server) == 0){
			$_SESSION["ERROR"] = "Por favor, informe o endere&ccedil;o do servidor do Backend Scanntech no estabelecimento.".$link_estabelecimento;
			return FALSE;
		}elseif(strlen($this->user) == 0){
			$_SESSION["ERROR"] = "Por favor, informe o usu&aacute;rio de acesso ao Backend Scanntech no estabelecimento.".$link_estabelecimento;
			return FALSE;
		}elseif(strlen($this->password) == 0){
			$_SESSION["ERROR"] = "Por favor, informe a senha de acesso ao Backend Scanntech no estabelecimento.".$link_estabelecimento;
			return FALSE;
		}elseif(strlen($this->idempresa) == 0){
			$_SESSION["ERROR"] = "Por favor, informe o c&oacute;digo da empresa relacionado ao Backend Scanntech no estabelecimento.".$link_estabelecimento;
			return FALSE;
		}elseif(strlen($this->idlocal) == 0){
			$_SESSION["ERROR"] = "Por favor, informe o c&oacute;digo do local relacionado ao Backend Scanntech no estabelecimento.".$link_estabelecimento;
			return FALSE;
		}

		if(substr($this->server, 0, 7) != "http://"){
			$this->server = "http://".$this->server;
		}
		if(substr($this->server, -1) != "/"){
			$this->server = $this->server."/";
		}

		return TRUE;
	}

	function getpdvvenda(){
		return $this->pdvvenda;
	}

	function getpdvconfig(){
		return $this->pdvconfig;
	}

	function getpdvfinalizador(){
		return $this->pdvfinalizador;
	}

	private function consultar_codean($codean){

		$service = "/minoristas/".$this->idempresa;
		$service .= "/articulos?codigoLocal=".$this->idlocal."&codigoBarra=".$codean;

		$result = $this->service($service, NULL, "GET");
		if($result === FALSE){
			return FALSE;
		}

		return $result["codigoArticulo"];
	}

	private function descricao($descricao){
		$descricao = removespecial($descricao);
		$descricao = preg_replace("/[^a-z0-9]/i", " ", $descricao);
		$descricao = str_replace("  ", " ", $descricao);
		$descricao = trim($descricao);
		return $descricao;
	}

	function exportar_cliente(){
		// Carrega as configuracoes
		setprogress(0, "Carregando configuracoes", TRUE);
		if(!$this->carregar_configuracao()){
			return FALSE;
		}

		$estabelecimento = $this->pdvconfig->getestabelecimento();

		// Carrega o cadastro de clientes
		setprogress(0, "Carregando cadastro de clientes", TRUE);

		$where = array();
		$where[] = "cliente.sincpdv IN (0,1)";
		if(param("CADASTRO", "MIXCLIENTE", $this->con) == "S"){
			$where[] = "clienteestab.codestabelec = ".$estabelecimento->getcodestabelec();
		}

		$query = "SELECT cliente.codcliente, cliente.razaosocial, cliente.tppessoa, cliente.cpfcnpj, cliente.enderres, cliente.numerores, ";
		$query .= "	cliente.bairrores, cliente.cepres, cliente.complementores, cidade.codoficial AS cidaderes, cliente.email, cliente.foneres, ";
		$query .= "	cliente.rgie, cliente.limite1, cliente.sincpdv, cliente.debito1 ";
		$query .= "FROM cliente	";
		$query .= "INNER JOIN cidade ON (cliente.codcidaderes = cidade.codcidade) ";
		if(param("CADASTRO", "MIXCLIENTE", $this->con) == "S"){
			$query .= "INNER JOIN clienteestab ON (cliente.codcliente = clienteestab.codcliente) ";
		}
		if(count($where) > 0){
			$query .= "WHERE ".implode(" AND ", $where)." ";
		}
//		$query .= "	AND cliente.codcliente = 100000 "; // REMOVER
		$query .= "ORDER BY cliente.codcliente ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$arr_codcliente = array();
		$n_total = count($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $n_total * 100), "Exportando clientes: ".($i + 1)." de ".$n_total);

			$arr_codcliente[] = $row["codcliente"];

			$documento = removeformat($row["cpfcnpj"]);

			$json = array(
				"codigoEmpresa" => $this->idempresa,
				"codigoCliente" => $row["codcliente"],
				"tipoCliente" => $row["tppessoa"],
				"numeroDocumento" => $documento,
				"nombre" => $row["razaosocial"],
				"calle" => $row["enderres"],
				"numeroPuerta" => (int) $row["numerores"],
				"barrio" => $row["bairrores"],
				"codigoPostal" => removeformat($row["cepres"]),
				"complemento" => $row["complementores"],
				"municipio" => $row["cidaderes"],
				"mail" => $row["email"],
				"telefono" => $row["foneres"],
				"fax" => NULL,
				"segundoNombre" => NULL,
				"apellido" => NULL,
				"segundoApellido" => NULL,
				"insEstadual" => ($row["tipopessoa"] == "J" ? removeformat($row["rgie"]) : NULL),
				"insMunicipal" => NULL,
				"regimenTributario" => "1", // ARRUMAR
				"cnae" => NULL
			);

			switch($row["sincpdv"]){
				case "0":
					$this->service("/minoristas/".$this->idempresa."/clientes/", $json, "POST", FALSE, FALSE);
					break;
				case "1":
					$this->service("/minoristas/".$this->idempresa."/clientes/".$row["codcliente"], $json, "PUT", FALSE, FALSE);
					break;
			}

			// Convenio
			if($row["limite1"] > 0){

				// Atualiza o limite
				$json = array(
					"tipoCliente" => $row["tppessoa"],
					"numeroDocumento" => $documento,
					"topeDiarioCheque" => 0,
					"topeCreditoCasa" => $row["limite1"],
					"ajusteCredito" => FALSE,
					"valorAjuste" => 0,
					"codigoMoneda" => "986"
				);
				$this->service("/minoristas/".$this->idempresa."/clientes/".$row["tppessoa"]."/".$documento."/chequecreditocasa/986", $json, "PUT", FALSE, FALSE);

				// Carrega o debito atual no backend
				$result = $this->service("/minoristas/".$this->idempresa."/clientes/".$row["tppessoa"]."/".$documento."/creditocasa/986", NULL, "GET");
				if($result !== FALSE){
					$diferenca = $row["debito1"] - $result["saldo"];
					if($diferenca !== 0){
						// Zera o debito atual do cliente no backend
						$json = array(
							"idEmpresa" => $this->idempresa,
							"tipoCliente" => $row["tppessoa"],
							"numeroDocumento" => $documento,
							"ajusteCredito" => ($diferenca > 0 ? 0 : 1),
							"monto" => abs($diferenca),
							"codigoMoneda" => "986"
						);
						$this->service("/minoristas/".$this->idempresa."/clientes/".$row["tppessoa"]."/".$documento."/movimientocreditocasa/986", $json, "POST");
					}
				}
			}
		}

		$this->log_error();

		// Atualiza campo que controla sincronizacao
		if(!$this->update_sincpdv("cliente", "codcliente", $arr_codcliente)){
			return FALSE;
		}

		// Exporta os lancamentos dos clientes (convenio)
		if(!$this->exportar_lancamento()){
			return FALSE;
		}

		return TRUE;
	}

	function exportar_lancamento(){
		return TRUE;

		// Limpa o erros
		unset($_SESSION["ERROR"]);

		// Carrega as configuracoes
		setprogress(0, "Carregando histórico financeiro", TRUE);
		if(!$this->carregar_configuracao()){
			return FALSE;
		}

		// Carrega o historico de lancamentos de convenios de clientes
		$query = "SELECT belancamento.*, cliente.tppessoa, cliente.cpfcnpj ";
		$query .= "FROM belancamento ";
		$query .= "INNER JOIN cliente ON (belancamento.codcliente = cliente.codcliente) ";
		$query .= "WHERE belancamento.sincpdv = 0 ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$n_total = count($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $n_total * 100), "Exportando lançamentos: ".($i + 1)." de ".$n_total);

//			$documento = str_pad(removeformat($row["cpfcnpj"]),14,"0",STR_PAD_LEFT);
			$documento = removeformat($row["cpfcnpj"]);

			if(strlen($documento) === 0){
				continue;
			}

			$json = array(
				"idEmpresa" => $this->idempresa,
				"tipoCliente" => $row["tppessoa"],
				"numeroDocumento" => $documento,
				"ajusteCredito" => ($row["tipo"] == "D" ? 0 : 1),
				"monto" => $row["valor"],
				"codigoMoneda" => "986"
			);

			$result = $this->service("/minoristas/".$this->idempresa."/clientes/".$row["tppessoa"]."/".$documento."/movimientocreditocasa/986", $json, "POST");
			//$result = $this->service("/minoristas/".$this->idempresa."/clientes/".$row["codcliente"]."/movimientocreditocasa",$json,"POST");

			$this->log_error();

			if($result === FALSE){
				return FALSE;
			}

			// Atualiza campo que controla sincronizacao
			if(!$this->update_sincpdv("belancamento", "codbelancamento", array($row["codbelancamento"]))){
				return FALSE;
			}
		}

		return TRUE;
	}

	function exportar_produto(){
		// Carrega as configuracoes
		setprogress(0, "Carregando configuracoes", TRUE);
		if(!$this->carregar_configuracao()){
			return FALSE;
		}

		$estabelecimento = $this->pdvconfig->getestabelecimento();

		// Remove os EANs
		setprogress(0, "Carregando EANs a serem removidos", TRUE);
		$this->log("Carregando EANs a serem removidos do banco de dados");
		$res = $this->con->query("SELECT * FROM bedelprodutoean WHERE sincpdv = 0 AND codestabelec = ".$estabelecimento->getcodestabelec());
		$arr = $res->fetchAll(2);

		$n_total = count($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $n_total * 100), "Removendo EANs: ".($i + 1)." de ".$n_total);

			$json = array("locales" => array(array("codigoLocal" => $this->idlocal)));
			$this->service("/minoristas/".$this->idempresa."/articulos/".$row["codproduto"]."/barras/".$row["codean"], $json, "DELETE");

			// Atualiza campo que controla sincronizacao
			if(!$this->update_sincpdv("bedelprodutoean", "codbedelprodutoean", array($row["codbedelprodutoean"]), $row["codestabelec"])){
				return FALSE;
			}
		}

		// Remove os produtos excluidos
		setprogress(0, "Carregando produtos a serem removidos", TRUE);
		$this->log("Carregando produtos a serem removidos do banco de dados");
		$res = $this->con->query("SELECT * FROM bedelproduto WHERE sincpdv = 0 AND codestabelec = ".$estabelecimento->getcodestabelec());
		$arr = $res->fetchAll(2);

		$n_total = count($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $n_total * 100), "Removendo produtos: ".($i + 1)." de ".$n_total);

			$json = array("locales" => array(array("codigoLocal" => $this->idlocal)));
			$this->service("/minoristas/".$this->idempresa."/articulos/".$row["codproduto"], $json, "DELETE");

			// Atualiza campo que controla sincronizacao
			if(!$this->update_sincpdv("bedelproduto", "codbedelproduto", array($row["codbedelproduto"]), $row["codestabelec"])){
				return FALSE;
			}
		}

		// Remove os produtos fora de linha
		setprogress(0, "Carregando produtos a fora de linha", TRUE);
		$this->log("Carregando produtos fora de linha");
		$res = $this->con->query("SELECT codproduto FROM produtoestab INNER JOIN produto USING (codproduto) WHERE produtoestab.sincpdv IN (0, 1) AND produto.foralinha = 'S' AND produto.gerapdv = 'S' AND produtoestab.codestabelec = {$estabelecimento->getcodestabelec()} ORDER BY 1");
		$arr = $res->fetchAll(2);

		$n_total = count($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $n_total * 100), "Removendo produtos: ".($i + 1)." de ".$n_total);

			$json = array("locales" => array(array("codigoLocal" => $this->idlocal)));
			$this->service("/minoristas/".$this->idempresa."/articulos/".$row["codproduto"], $json, "DELETE");

			// Atualiza campo que controla sincronizacao
			if(!$this->update_sincpdv("produtoestab", "codproduto", array($row["codproduto"]), $estabelecimento->getcodestabelec())){
				return FALSE;
			}
		}

		// Exporta o cadastro principal de produtos
		setprogress(0, "Carregando cadastro de produtos", TRUE);
		$this->log("Carregando produtos do banco de dados");

		$query = "SELECT produto.codproduto, produto.descricaofiscal, produto.descricao, produto.coddepto, produto.codgrupo, produto.codsubgrupo, ";
		$query .= "	unidade.sigla AS unidade, piscofins.aliqpis, piscofins.aliqcofins, produto.pesado, classfiscal.codcst AS csticms, ncm.codigoncm, ";
		$query .= "	produto.precovariavel, ".$this->pdvconfig->sql_tipopreco().", produtoestab.custorep, classfiscal.tptribicms, produto.diasvalidade, ";
		$query .= "	classfiscal.aliqicms, classfiscal.aliqredicms, embalagem.descricao AS embalagem, produto.pesounid, produtoean.codean, ";
		$query .= "	COALESCE((CASE WHEN produto.aliqmedia IS NOT NULL THEN produto.aliqmedia ELSE ncm.aliqmedia END),0) AS aliqmedia, produtoestab.sincpdv, ";
		$query .= "	piscofins.codcst AS cstpiscofins, produto.foralinha ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "LEFT JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "LEFT JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
		$query .= "LEFT JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
		$query .= "LEFT JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "LEFT JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produto.foralinha = 'N' ";
		$query .= "	AND produtoestab.disponivel = 'S' ";
		$query .= "	AND produtoestab.sincpdv IN (0, 1) ";
		$query .= "	AND produto.gerapdv = 'S' ";
		$query .= "	AND produto.codproduto NOT IN (SELECT codproduto FROM composicao WHERE tipo IN ('A','V') AND explosaoauto = 'S') ";
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		$query .= "ORDER BY produto.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$this->arr_codproduto = array();

		$arr_json = array();
		$n_total = count($arr);
		$this->log("Total de ".$n_total." produtos carregados para exportacao");
		foreach($arr as $i => $row){
			$this->arr_codproduto[] = $row["codproduto"];
			setprogress((($i + 1) / $n_total * 100), "Exportando produtos: ".($i + 1)." de ".$n_total);

			$codean = $row["codean"];
			if(plutoean13($row["codproduto"]) == $codean){
				$codean = NULL;
			}
			if($row["pesado"] == "S"){
				/*
				  $codean = str_pad($row["coddepto"], 2, "0", STR_PAD_LEFT); // Codigo da categoria
				  $codean .= "21"; // Identifica que e de balanca
				  $codean .= "0"; // Pesavel
				  $codean .= str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
				  $codean .= str_pad(number_format($row["preco"], 2, "", ""), 6, "0", STR_PAD_LEFT); // Preco do produto
				  $codean .= str_pad($row["diasvalidade"], 3, "0", STR_PAD_LEFT); // Dias de validade
				 */
				$codean = NULL;
			}
			if($row["tptribicms"] == "R"){
				$row["tptribicms"] = "T";
				$row["aliqicms"] = round($row["aliqicms"] * (1 - $row["aliqredicms"] / 100));
			}
			if($row["tptribicms"] == "T" && $row["aliqicms"] == 0){
				$row["tptribicms"] = "N";
			}

			if($row["foralinha"] === "S"){
				$arr_local = array();
			}else{
				$arr_local = array(
					array("codigoLocal" => $this->idlocal)
				);
			}

			$json = array(
				"codigoEmpresa" => $this->idempresa,
				"sku" => NULL,
				"codigoGTIN" => $codean,
				"codigoArticulo" => $row["codproduto"],
				"plu" => ($row["pesado"] == "S" ? $row["codproduto"] : NULL),
				"descipcion" => $this->descricao($row["descricaofiscal"]),
				"descipcionReducida" => $this->descricao($row["descricao"]),
				"codigoCategoria" => 240, // Valor padrao
				"codigoFamilia" => NULL,
				"codigoSubFamilia" => NULL,
				"codigoExTarifado" => NULL,
				"origenProducto" => (strlen($row["csticms"]) == 0 ? "0" : substr($row["csticms"], 0, 1)),
				"unidad" => $row["unidade"],
				"tipoProducto" => "1",
				"produccionPropia" => FALSE,
				"tipoPeso" => $row["pesounid"],
				"usaBalanza" => ($row["pesado"] == "S" ? TRUE : FALSE),
				"exportable" => ($row["pesado"] == "S" ? TRUE : FALSE),
				"ventaFraccionada" => ($row["pesounid"] == "P" ? TRUE : FALSE),
				"semejante" => NULL,
				"precioVenta" => $row["preco"],
				"truncamiento" => FALSE,
				"precioCosto" => NULL,
				"descuento" => TRUE,
				"descuentoMaximo" => $row["preco"],
				"locales" => $arr_local,
				"ncm" => removeformat($row["codigoncm"]),
				"aliquotaICMS" => ($row["tptribicms"] == "T" ? $row["aliqicms"] : 0),
				"tipoTributoSalida" => $row["tptribicms"],
				"aliquotaPIS" => $row["aliqpis"],
				"aliquotaCOFINS" => $row["aliqcofins"]
			);

			if($this->pdvconfig->getestabelecimento()->getequipamentofiscal() == "SAT"){
				if($this->pdvconfig->getestabelecimento()->getregimetributario() == "1"){
					$csosn = ($row["tptribicms"] == "F" ? "300" : "102");
				}else{
					$csosn = NULL;
				}

				$json = array_merge($json, array(
					"cfop" => ($row["tptribicms"] == "F" ? "5405" : "5102"),
					"csosn" => $csosn,
					"cstICMS" => $row["csticms"],
					"cstPIS" => $row["cstpiscofins"],
					"cstCOFINS" => $row["cstpiscofins"]
				));
			}

			$arr_json[] = $json;

			if(count($arr_json) >= 5 || (count($arr) == $i + 1)){
				$this->service("/minoristas/".$this->idempresa."/articulos/", $arr_json, "POST", TRUE, FALSE);
				$arr_json = array();
			}
		}

		$this->log_error();

		// Atualiza campo que controla sincronizacao
		if(!$this->update_sincpdv("produtoestab", "codproduto", $this->arr_codproduto, $this->pdvconfig->getestabelecimento()->getcodestabelec())){
			return FALSE;
		}

		// Atualiza o "precopdv" dos produtos
		$this->pdvconfig->atualizar_precopdv($this->arr_codproduto);

		// Exporta o cadastro composicoes
		setprogress(0, "Carregando cadastro de composicoes", TRUE);

		$query = "SELECT composicao.codcomposicao, produto.codproduto, produto.descricaofiscal, produto.descricao, ";
		$query .= "	produtoean.codean, produtoestab.sincpdv, ".$this->pdvconfig->sql_tipopreco()." ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "INNER JOIN composicao ON (composicao.codproduto = produto.codproduto) ";
		$query .= "LEFT JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produto.foralinha = 'N' ";
		$query .= "	AND produtoestab.disponivel = 'S' ";
		$query .= "	AND composicao.tipo IN ('A','V') ";
		$query .= "	AND explosaoauto = 'S' ";
		$query .= "ORDER BY produto.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$arr_codproduto = array();

		$n_total = count($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $n_total * 100), "Exportando composicoes: ".($i + 1)." de ".$n_total);

			$arr_codproduto[] = $row["codproduto"];

			$codean = $row["codean"];
			if(plutoean13($row["codproduto"]) == $codean){
				$codean = NULL;
			}

			$arr_componente = array();
			$res_c = $this->con->query("SELECT codproduto, quantidade FROM itcomposicao WHERE codcomposicao = ".$row["codcomposicao"]);
			$arr_c = $res_c->fetchAll(2);
			foreach($arr_c as $row_c){
				$componente = array("codigoArticulo" => $row_c["codproduto"]);
				if($row_c["quantidade"] != 1){
					$componente["cantidad"] = (int) $row_c["quantidade"];
				}
				$arr_componente[] = $componente;
			}

			$arr_local = array(
				array("codigoLocal" => $this->idlocal)
			);

			$json = array(
				"codigoEmpresa" => $this->idempresa,
				"codigo" => $row["codproduto"],
				"descripcion" => $this->descricao($row["descricaofiscal"]),
				"descripcionReducida" => $this->descricao($row["descricao"]),
				"codigoBarras" => $codean,
				"codigoLista" => NULL,
				"precioVenta" => $row["preco"],
				"descuento" => TRUE,
				"descuentoMaximo" => $row["preco"],
				"tipo" => "5", // Combo inverso
				"componentes" => $arr_componente,
				"locales" => $arr_local
			);

			$this->service("/minoristas/".$this->idempresa."/combos/", $json, "POST", FALSE, FALSE);
		}
		$this->pdvconfig->atualizar_precopdv($arr_codproduto);

		$this->log_error();

		// Exporta os precos por quantidade
		setprogress(0, "Carregando precos por quantidade", TRUE);

		$query = "SELECT produtoestab.codproduto, produtoestab.qtdatacado, produtoestab.precoatc, produtoestab.precovrj ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produtoestab.qtdatacado > 0 ";
		$query .= "ORDER BY produtoestab.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$n_total = count($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $n_total * 100), "Exportando precos por quantidade: ".($i + 1)." de ".$n_total);

			if($row["qtdatacado"] == 0 || $row["precoatc"] == 0){
				$this->service("/minoristas/{$this->idempresa}/{$this->idlocal}/articulos/{$row["codproduto"]}/precios/franjas", NULL, "DELETE", FALSE, FALSE);
			}else{
				$json = array(
					"franjasPrecio" => array(
						array(
							"cantidadDesde" => 1,
							"precioVenta" => $row["precovrj"]
						),
						array(
							"cantidadDesde" => round($row["qtdatacado"], 0),
							"precioVenta" => $row["precoatc"]
						)
					)
				);
				$this->service("/minoristas/{$this->idempresa}/{$this->idlocal}/articulos/{$row["codproduto"]}/precios/franjas", $json, "POST", FALSE, FALSE);
			}
		}

		return TRUE;
	}

	function importar_maparesumo(){
		// Carrega as configuracoes
		if(!$this->carregar_configuracao()){
			return FALSE;
		}

		$codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();
		$dtmovto = $this->pdvconfig->getdtmovto();

		if(strlen($dtmovto) == 0){
			$_SESSION["ERROR"] = "Por favor, informe a data desejada para importar as vendas.";
			return FALSE;
		}

		// Carrega todos os caixas do estabelecimento
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setcodestabelec($codestabelec);
		$ecf->setstatus("A");
		$ecf->setorder("caixa");
		$arr_ecf = object_array($ecf);

		// Inicia uma transacao
		$this->con->start_transaction();

		// Caixa ja lidos, para nao ler novamente
		$arr_caixa = array();

		$n_total = count($arr_ecf);
		foreach($arr_ecf as $i => $ecf){
			setprogress((($i + 1) / $n_total * 100), "Carregando mapa resumo dos caixas: ".($i + 1)." de ".$n_total);
			if(strlen($ecf->getcaixa()) > 0){
				if(in_array($ecf->getcaixa(), $arr_caixa)){
					continue;
				}else{
					$arr_caixa[] = $ecf->getcaixa();
				}

				$service = "/minoristas/".$this->idempresa;
				$service .= "/locales/".$this->idlocal;
				$service .= "/cajas/".$ecf->getcaixa();
				$service .= "/cierresDiarios?fechaConsulta=".$dtmovto;
				$result = $this->service($service, NULL, "GET");
				if($result === FALSE){
					return FALSE;
				}elseif(!is_array($result)){
					continue;
				}

				$cierrediario = $result;

				$maparesumo = objectbytable("maparesumo", NULL, $this->con);
				$maparesumo->setcodestabelec($codestabelec);
				$maparesumo->setcaixa($ecf->getcaixa());
				$maparesumo->setdtmovto($cierrediario["fechaVentas"]);
				$maparesumo->setoperacaoini($cierrediario["numeroCuponFiscalInicial"]);
				$maparesumo->setoperacaofim($cierrediario["numeroCuponFiscalFinal"]);
				$maparesumo->settotalbruto($cierrediario["ventaBrutaDiaria"]);
				$maparesumo->setgtfinal($cierrediario["gtFinal"]);
				$maparesumo->setgtinicial($maparesumo->getgtfinal() - $maparesumo->gettotalbruto());
				$maparesumo->settotalcupomcancelado($cierrediario["cancelaciones"]);
				$maparesumo->settotaldescontocupom($cierrediario["descuentos"]);
				$maparesumo->setnumeroreducoes($cierrediario["contadorReducciones"]);
				$maparesumo->setreiniciofim($cierrediario["contadorReinicioOperaciones"]);
				$maparesumo->setcodecf($ecf->getcodecf());
				$maparesumo->setnumeroecf($ecf->getnumeroecf());
				$maparesumo->settotalliquido($maparesumo->gettotalbruto() - $maparesumo->gettotaldescontocupom() - $maparesumo->gettotalcupomcancelado());

				if(!$maparesumo->save()){
					$this->con->rollback();
					return FALSE;
				}

				$arr_tributo = array(
					array("I", 0, (!is_null($cierrediario["totalExento"]) ? $cierrediario["totalExento"] : 0)),
					array("N", 0, (!is_null($cierrediario["totalNoTributado"]) ? $cierrediario["totalNoTributado"] : 0)),
					array("F", 0, (!is_null($cierrediario["totalSustitucion"]) ? $cierrediario["totalSustitucion"] : 0))
				);

				foreach($cierrediario["registrosAliquotaVenta"] as $aliquotavenda){
					$arr_tributo[] = array("T", $aliquotavenda["aliquota"], $aliquotavenda["baseCalculo"]);
				}

				foreach($arr_tributo as $tributo){
					$maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
					$maparesumoimposto->setcodmaparesumo($maparesumo->getcodmaparesumo());
					$maparesumoimposto->settptribicms($tributo[0]);
					$maparesumoimposto->setaliqicms($tributo[1]);
					$maparesumoimposto->settotalliquido($tributo[2]);
					$maparesumoimposto->settotalicms($tributo[2] * $tributo[1] / 100);

					if(!$maparesumoimposto->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
		}

		// Finaliza a transacao
		$this->con->commit();
		return TRUE;
	}

	function importar_venda($leituraonline = FALSE){
		// Carrega as configuracoes
		setprogress(0, "Carregando configuracoes", TRUE);
		if(!$this->carregar_configuracao()){
			return FALSE;
		}

		$codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();
		$dtmovto = $this->pdvconfig->getdtmovto();

		if(strlen($dtmovto) == 0){
			$_SESSION["ERROR"] = "Por favor, informe a data desejada para importar as vendas.";
			return FALSE;
		}

		// Carrega todos os ECFs por numero de serie
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setcodestabelec($codestabelec);
		$arr_ecf_numfabricacao_aux = object_array($ecf);
		$arr_ecf_numfabricacao = array();
		foreach($arr_ecf_numfabricacao_aux as $ecf_numfabricacao){
			if(strlen($ecf_numfabricacao->getnumfabricacao()) > 0){
				$arr_ecf_numfabricacao[$ecf_numfabricacao->getnumfabricacao()] = $ecf_numfabricacao;
			}
		}

		// Carrega todos os caixas do estabelecimento
		$ecf = objectbytable("ecf", NULL, $this->con);
		$ecf->setcodestabelec($codestabelec);
		$ecf->setorder("caixa");
		$ecf->setstatus("A");
//		$ecf->setcaixa(6); // REMOVER
		$arr_ecf = object_array($ecf);

		$arr_caixa = array();
		foreach($arr_ecf as $i => $ecf){
			if(strlen($ecf->getcaixa()) > 0){
				if(in_array($ecf->getcaixa(), $arr_caixa)){
					$_SESSION["ERROR"] = "Existem ECFs duplicados para o caixa de n&uacute;mero {$ecf->getcaixa()}.<br><a onclick=\"$.messageBox('close'); openProgram('Ecf')\">Clique aqui</a> para abrir o cadastro de ECFs.";
					return FALSE;
				}else{
					$arr_caixa[] = $ecf->getcaixa();
				}
			}
		}

		$n_total = count($arr_ecf);
		foreach($arr_ecf as $i => $ecf){
			setprogress((($i + 1) / $n_total * 100), "Carregando venda dos caixas: ".($i + 1)." de ".$n_total);
			if(strlen($ecf->getcaixa()) > 0){
				if($leituraonline){
					$query = "SELECT MAX(seqecf) FROM cupom WHERE codestabelec = {$this->pdvconfig->getestabelecimento()->getcodestabelec()} AND dtmovto = '{$dtmovto}' AND caixa = {$ecf->getcaixa()}";
					$res = $this->con->query($query);
					$seqecf_max = $res->fetchColumn();
				}

				$service = "/minoristas/".$this->idempresa;
				$service .= "/locales/".$this->idlocal;
				$service .= "/cajas/".$ecf->getcaixa();
				$service .= "/movimientos?fechaConsulta=".$dtmovto."T00:00:00.000-03:00";
				$result = $this->service($service, NULL, "GET");
				if($result === FALSE){
					return FALSE;
				}

				$arr_venda = $result;
				/*
				  // Reordena os cupons de acordo com o numero do movimento
				  $arr_venda_aux = array();
				  foreach($arr_venda as $venda){
				  $arr_venda_aux[$venda["numeroMov"]] = $venda;
				  }
				  ksort($arr_venda_aux);
				  $arr_venda = array_values($arr_venda_aux);
				 */
				// Reordena os cupons de acordo com o numero da operacao fiscal
				$numeroCuponFiscalZero = 1000000;
				$arr_venda_aux = array();
				foreach($arr_venda as $venda){
					if($venda["cuponAnulada"]){
						continue;
					}
					if(is_null(value_numeric($venda["numeroCuponFiscal"]))){
						continue;
					}
					if($venda["numeroCuponFiscal"] == 0){
						$venda["numeroCuponFiscal"] = $numeroCuponFiscalZero++;
					}
					$arr_venda_aux[$venda["numeroCuponFiscal"]] = $venda;
				}
				ksort($arr_venda_aux);
				$arr_venda = array_values($arr_venda_aux);

				$cont_cupom = 0;
				foreach($arr_venda as $j => $venda){
					/*
					if(strlen($venda["numeroSerieEcf"]) === 0){
						$venda["numeroSerieEcf"] = "000239333";
					}
					*/
					$ecf_numfabricacao = $arr_ecf_numfabricacao[$venda["numeroSerieEcf"]];
					if(!is_object($ecf_numfabricacao)){
						$_SESSION["ERROR"] = "ECF n&atilde;o encontrada para a s&eacute;rie {$venda["numeroSerieEcf"]}.<br><a onclick=\"$.messageBox('close'); openProgram('Ecf')\">Clique aqui</a> para abrir o cadastro de ECFs.";
						return FALSE;
					}

					$cpfcnpj = $venda["cnpjCpfCliente"];
					$cupom = $venda["numeroOperacionFiscal"];
					$seqecf = $venda["numeroCuponFiscal"];
					$data = substr($venda["fechaOperacion"], 0, 10);
					$hora = substr($venda["fechaOperacion"], 11, 8);
					$status = ($venda["cuponCancelado"] || ($venda["total"] < 0) ? "C" : "A");
					$numeroecf = $ecf_numfabricacao->getnumeroecf();

					$cupom = str_pad(trim($cupom), 6, "0", STR_PAD_LEFT);
					/*
					  $con = new Connection();
					  $con->exec("INSERT INTO temporario VALUES ('{$cupom}', '{$status}', {$venda["total"]})");
					 */

					if($leituraonline && strlen($seqecf_max) > 0 && (int) $seqecf_max >= (int) $seqecf){
						continue;
					}

					if($leituraonline && $cont_cupom >= 20){
						continue 2;
					}

					$cont_cupom++;

					if($status === "C"){
						$arr_pdvvenda = array_reverse($this->pdvvenda);
						foreach($arr_pdvvenda as $pdvvenda){
							if($pdvvenda->getdata() == $data && $pdvvenda->getcaixa() == $ecf->getcaixa() && $pdvvenda->getcupom() == $cupom){
								$pdvvenda->setstatus("C");

								foreach($this->pdvfinalizador as $pdvfinalizador){
									if($pdvfinalizador->getdata() == $data && $pdvfinalizador->getcaixa() == $ecf->getcaixa() && $pdvfinalizador->getcupom() == $cupom){
										$pdvfinalizador->setstatus("C");
									}
								}

								continue 2;
							}
						}
					}

					$pdvvenda = new PdvVenda();
					$pdvvenda->setcaixa($ecf->getcaixa());
					$pdvvenda->setcpfcnpj($cpfcnpj);
					$pdvvenda->setcupom($cupom);
					$pdvvenda->setdata($data);
					$pdvvenda->sethora($hora);
					$pdvvenda->setnumeroecf($numeroecf);
					$pdvvenda->setseqecf($seqecf);
					$pdvvenda->setstatus($status);
					$pdvvenda->setcodecf($ecf_numfabricacao->getcodecf());

					$k = 1;
					foreach($venda["detalles"] as $detalhe){
						// Verifica se nao eh item de promocao
						if(is_null(value_numeric($detalhe["codigoArticulo"]))){
							continue;
						}

						// Preco zerado, normalmente pai de composicao
						if(strlen($detalhe["importeUnitario"]) === 0){
							continue;
						}

						$codproduto = $detalhe["codigoBarras"];
						if(strlen(ltrim($codproduto, "0")) < 7 || strlen($codproduto) > 14 || substr($codproduto, 0, 1) == "2"){
							$codproduto = ltrim($detalhe["codigoArticulo"], "0");
							if(strlen($codproduto) === 0){
								$codproduto = "0";
							}
						}

						$preco = abs($detalhe["importeUnitario"]);
						$quantidade = abs($detalhe["cantidad"]);
						$total = abs($detalhe["importe"]);
						$desconto = $detalhe["descuento"];
						$acrescimo = 0;

						switch($detalhe["descuentos"][0]["tipoDescuento"]){
							case "DescXLineaCantidad":
								//$desconto = 0;
								break;
							case "BonifPorcentajeManual":
								$desconto = $total - ($preco * $quantidade);
								break;
							case "AjusteProrrateo":
								if($desconto < 0){
									$acrescimo = abs($desconto);
									$desconto = 0;
									$total += $acrescimo;
								}
								break;
							default:
								$desconto = abs($desconto);
								break;
						}

						$pdvitem = new PdvItem();
						$pdvitem->setsequencial($k++);
						$pdvitem->setcodproduto($codproduto, TRUE);
						$pdvitem->setquantidade($quantidade);
						$pdvitem->setpreco($preco);
						$pdvitem->setdesconto($desconto);
						$pdvitem->setacrescimo($acrescimo);
						$pdvitem->settotal($total);
						$pdvitem->settptribicms($detalhe["tipoTributoSalida"]);
						$pdvitem->setaliqicms($detalhe["tasaICMS"]);
						$pdvitem->setdescricao($detalhe["descripcionArticulo"]);

						switch($detalhe["codigoTipoDetalle"]){
							case "4": // Venda normal
								$pdvvenda->pdvitem[] = $pdvitem;
								break;
							case "5": // Cancelamento de item
								$arr_pdvitem = array_reverse($pdvvenda->pdvitem);
								foreach($arr_pdvitem as $pdvitem2){
									if($pdvitem->getcodproduto() == $pdvitem2->getcodproduto() && $pdvitem2->getstatus() == "A" && abs($pdvitem->gettotal() - $pdvitem2->gettotal()) < 0.05){
										$pdvitem2->setstatus("C");
										break;
									}
								}
								break;
						}
					}
					/*
					  $totalitens = 0;
					  foreach($pdvvenda->pdvitem as $pdvitem){
					  if($pdvitem->getstatus() === "C"){
					  $totalitens += $pdvitem->gettotal();
					  }
					  }
					  $diferenca = $totalitens - $venda["total"];
					  if($diferenca != 0 && abs($diferenca) < 0.1){
					  foreach($pdvvenda->pdvitem as $pdvitem){
					  if($pdvitem->getstatus() == "A" && $pdvitem->getquantidade() == 1){
					  $pdvitem->setpreco($pdvitem->getpreco() - $diferenca);
					  $pdvitem->settotal($pdvitem->getpreco() * $pdvitem->getquantidade() - $pdvitem->getdesconto());
					  break;
					  }
					  }
					  }
					 */
					foreach($venda["pagos"] as $k => $pagamento){

						if($pagamento["importe"] == 0){
							continue;
						}

						if($pagamento["codigoTipoPago"] == "10" && $pagamento["productoSITEF"] == "90"){
							$pagamento["codigoTipoPago"] = "20";
						}

						$pdvfinalizador = new PdvFinalizador();
						$pdvfinalizador->setcupom($pdvvenda->getcupom());
						$pdvfinalizador->setcaixa($pdvvenda->getcaixa());
						$pdvfinalizador->setcodfinaliz($pagamento["codigoTipoPago"]);
						$pdvfinalizador->setdata($pdvvenda->getdata());
						$pdvfinalizador->sethora($pdvvenda->gethora());
						//$pdvfinalizador->setdatavencto(substr($pagamento["fechaVencimiento"], 0, 10));
						$pdvfinalizador->setvalortotal(abs($pagamento["importe"]));
						//$pdvfinalizador->setcpfcliente($pagamento["documentoCliente"]);
						$pdvfinalizador->setcpfcliente($pdvvenda->getcpfcnpj());
						$pdvfinalizador->setbin($pagamento["bin"]);

						$this->pdvfinalizador[] = $pdvfinalizador;
					}

					$this->pdvvenda[] = $pdvvenda;
				}
			}
		}
		/*
		  $tc = 0;
		  $tl = 0;
		  foreach($this->pdvvenda as $pdvvenda){
		  if($pdvvenda->getstatus() == "A"){
		  foreach($pdvvenda->pdvitem as $pdvitem){
		  if($pdvitem->getstatus() == "A"){
		  $tc += $pdvitem->gettotal();
		  }
		  }
		  }
		  }
		  foreach($this->pdvfinalizador as $pdvfinalizador){
		  if($pdvfinalizador->getstatus() == "A"){
		  $tl += $pdvfinalizador->getvalortotal();
		  }
		  }
		  $_SESSION["ERROR"] = "Cupom: {$tc}<br>Lancamento: {$tl}";
		  return FALSE;
		 */
		return TRUE;
	}

	private function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
	}

	private function log($text, $time = NULL){
		$f = fopen("../temp/scanntech.log", "a+");
		fwrite($f, date("d/m/Y H:i:s")."\r\n");
		fwrite($f, $text."\r\n");
		if(!is_null($time)){
			fwrite($f, "Tempo: ".round((microtime(TRUE) - $time), 4)." seg\r\n");
		}
		fwrite($f, "\r\n");
		fclose($f);
		chmod("../temp/scanntech.log", 0777);
	}

	private function log_error(){
		if(count($_SESSION["ERROR_PDV"]) > 0){
			$f = fopen("../temp/scanntech.err", "a+");
			foreach($_SESSION["ERROR_PDV"] as $error){
				fwrite($f, $error["code"]."\r\n");
				fwrite($f, $error["description"]."\r\n");
				fwrite($f, $error["json"]."\r\n");
				fwrite($f, "Tempo: ".(microtime(TRUE) - $error["time"])." segundos\r\n");
				fwrite($f, "\r\n");

				$pdvlog = array();
				$arr = json_decode($error["json"], TRUE);
				if(isset($arr["codigoArticulo"])){
					$pdvlog["tabela"] = "Produto";
					$pdvlog["codigo"] = $arr["codigoArticulo"];
					$pdvlog["descricao"] = $arr["descipcion"];
				}elseif(isset($arr["codigoCliente"])){
					$pdvlog["tabela"] = "Cliente";
					$pdvlog["codigo"] = $arr["codigoCliente"];
					$pdvlog["descricao"] = $arr["nombre"];
				}elseif(isset($arr["codigo"])){
					$pdvlog["tabela"] = "Composicao";
					$pdvlog["codigo"] = $arr["codigo"];
					$pdvlog["descricao"] = $arr["descripcion"];
				}
				$pdvlog["erro"] = (strlen($error["code"]) > 0 ? $error["code"] : $error["description"]);
				switch($pdvlog["erro"]){
					case "CODIGO_BARRAS_ASOCIADO_A_OTRO_ARTICULO":
						$pdvlog["erro"] .= " (".$arr["codigoGTIN"].")";
						break;
				}
//				$pdvlog["erro"] = str_replace(array("<br>", "\r\n", "\n"), " ", $pdvlog["erro"]);
//				$pos = strpos($pdvlog["erro"], "[");
//				if($pos !== FALSE){
//					$pdvlog["erro"] = substr($pdvlog["erro"], 0, $pos);
//				}
//				$pdvlog["erro"] = trim($pdvlog["erro"]);
				$_SESSION["PDVLOG"][] = $pdvlog;
			}
			fclose($f);
			chmod("../temp/scanntech.err", 0777);
			$_SESSION["ERROR_PDV"] = array();
		}
	}

	private function service($service, $json, $method, $multi = FALSE, $stop_on_error = TRUE, $in_loop = FALSE){
		unset($_SESSION["ERROR"]);

		$url = $this->server."products.apibackend.rest.server/api".$service;

		$arr_json = ($multi ? $json : array($json));

		$arr_curlmap = array();
		$handle = curl_multi_init();

		foreach($arr_json as $json){
			if(is_array($json)){
				$json = json_encode($json);
			}

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json", "Accept: application/json"));
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $this->user.":".$this->password);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_TIMEOUT, 300);
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
			if(!$stop_on_error){
				curl_setopt($curl, CURLOPT_FAILONERROR, FALSE);
			}

			$arr_curlmap[] = array(
				"curl" => $curl,
				"json" => $json,
				"time" => microtime(TRUE)
			);

			curl_multi_add_handle($handle, $curl);
		}

		do{
			$mcurl = curl_multi_exec($handle, $active);
			if($state = curl_multi_info_read($handle)){
				$result = curl_multi_getcontent($state["handle"]);
				if(is_string($result)){
					$result = json_decode($result, TRUE);
				}
				$info = curl_getinfo($state["handle"]);
				curl_multi_remove_handle($handle, $state["handle"]);

				foreach($arr_curlmap as $i => $curlmap){
					if($curlmap["curl"] == $state["handle"]){
						unset($arr_curlmap[$i]);
						break;
					}
					unset($curlmap);
				}

				$this->log("HTTP Code ".$info["http_code"]."\r\n".$service."\r\n".$curlmap["json"], $curlmap["time"]);

				if($info["http_code"] == 200){
					$arr = json_decode($curlmap["json"], TRUE);
					if(isset($arr["codigoArticulo"])){
						$this->arr_codproduto[] = $arr["codigoArticulo"];
					}
					if($method == "GET" && !$multi){
						curl_multi_close($handle);
						return $result;
					}
				}else{
					if(is_array($result)){
						$row = json_decode($curlmap["json"], TRUE);
						switch($result["appErrorCode"]){
							case "ARTICULO_NO_ES_COMBO": // Composicao ja existe como produto
								if(!$in_loop){
									// Remove o produto do cadastro para poder cadastrar como composicao
									$this->service("/minoristas/".$this->idempresa."/articulos/".$row["codigo"], array("locales" => $row["locales"]), "DELETE");
									// Tenta incluir a composicao novamente
									$this->service($service, $curlmap["json"], $method, $multi, $stop_on_error, TRUE);
								}
								break;
							case "CODIGO_BARRAS_ASOCIADO_A_OTRO_ARTICULO": // Codigo de barras esta em outro produto
								if(!$in_loop){
									// Remove o codigo de barras do produto atual
									if($this->remover_codean($row["codigoGTIN"])){
										// Tenta incluir o produto novamente
										return $this->service($service, $curlmap["json"], $method, $multi, $stop_on_error, TRUE);
									}
								}
								break;
							case "ARTICULO_BORRADO": // Produto ja esta apagado
							case "ARTICULO_NO_ENCONTRADO": // Produto nao existe
							case "CAJA_NO_EXISTE": // Caixa nao existe
							case "NO_EXISTE_LOCAL": // Local nao existe
							case "YA_EXISTE_CLIENTE": // Cliente ja existe
							case "YA_EXISTE_CODIGO_BARRAS": // Codigo de barras ja existe
								continue 2;
								break;
						}
						$_SESSION["ERROR"] = $result["message"]." (".$result["appErrorCode"].")";
						$_SESSION["ERROR"] .= "<br><br>".$curlmap["json"];
					}
					if(strlen($_SESSION["ERROR"]) == 0){
						switch($info["http_code"]){
							case 400: $_SESSION["ERROR"] = "O servidor não entendeu a sintaxe da solicitação.";
								break;
							case 401: $_SESSION["ERROR"] = "Autenticação incorreta para a solicitação.";
								break;
							case 403: $_SESSION["ERROR"] = "O servidor está recusando a solicitação.";
								break;
							case 404: $_SESSION["ERROR"] = "O servidor não encontrou a página solicitada.";
								break;
							case 405: $_SESSION["ERROR"] = "O método especificado na solicitação não é permitido.";
								break;
							case 406: $_SESSION["ERROR"] = "A página solicitada não pode responder com as características de conteúdo solicitadas.";
								break;
							case 407: $_SESSION["ERROR"] = "A solicitação requer autenticação utilizando proxy.";
								break;
							case 408: $_SESSION["ERROR"] = "O servidor atingiu o tempo limite ao aguardar a solicitação.";
								break;
							case 409: $_SESSION["ERROR"] = "O servidor encontrou um conflito ao cumprir a solicitação.";
								break;
							case 410: $_SESSION["ERROR"] = "O servidor não encontrou a página solicitada.";
								break;
							case 411: $_SESSION["ERROR"] = "O servidor não aceitará a solicitação sem um campo de cabeçalho \"Comprimento-do-Conteúdo\" válido.";
								break;
							case 412: $_SESSION["ERROR"] = "O servidor não cumpre uma das pré-condições que o solicitante coloca na solicitação.";
								break;
							case 413: $_SESSION["ERROR"] = "O servidor não pode processar a solicitação porque ela é muito grande para a capacidade do servidor.";
								break;
							case 414: $_SESSION["ERROR"] = "O URI solicitado é muito longo para ser processado pelo servidor.";
								break;
							case 415: $_SESSION["ERROR"] = "A solicitação está em um formato não compatível com a página solicitada.";
								break;
							case 416: $_SESSION["ERROR"] = "Código de status da solicitação em uma faixa não disponível para a página.";
								break;
							case 417: $_SESSION["ERROR"] = "O servidor não pode cumprir os requisitos do campo \"Expectativa\" do cabeçalho da solicitação.";
								break;
							case 500: $_SESSION["ERROR"] = "Falha interna do servidor requisitado.";
								break;
							default : $_SESSION["ERROR"] = "";
								break;
						}
						$_SESSION["ERROR"] .= "<br>[erro ".$info["http_code"]."]";
						$_SESSION["ERROR"] .= "<br>Servi&ccedil;o: ".$url;
					}
					if($stop_on_error){
						curl_multi_close($handle);
						return FALSE;
					}else{
						$_SESSION["ERROR_PDV"][] = array(
							"code" => $result["appErrorCode"],
							"description" => $_SESSION["ERROR"],
							"json" => $curlmap["json"],
							"time" => $curlmap["time"]
						);
					}
				}
			}else{
				usleep(250);
			}
		}while(($mcurl == CURLM_CALL_MULTI_PERFORM || $active) && (!$stop_on_error || strlen($_SESSION["ERROR"]) == 0));

		curl_multi_close($handle);
		return TRUE;
	}

	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
	}

	private function remover_codean($codean){
		$codproduto = $this->consultar_codean($codean);
		if($codproduto === FALSE){
			return FALSE;
		}

		$service = "/minoristas/".$this->idempresa."/articulos/".$codproduto."/barras/".$codean;
		$result = $this->service($service, NULL, "DELETE");

		return ($result !== FALSE);
	}

	private function update_sincpdv($table, $column, $arr_values, $codestabelec = NULL){
		setprogress(0, "Atualizando tabela de controle");
		if(count($arr_values) === 0){
			return TRUE;
		}else{
			$where = array();
			$where[] = $column." IN (".implode(", ", $arr_values).")";
			if(!is_null($codestabelec)){
				$where[] = "codestabelec = ".$codestabelec;
			}
			$sql = "UPDATE ".$table." SET sincpdv = 2 WHERE ".implode(" AND ", $where);

			// Nesse caso, deve abrir uma nova conexao, pois nao e possivel executar um rollback no PDV
			$con = new Connection();
			$con->start_transaction();

			// Desabilita as triggers da tabela
			/* 			if(!$this->update_sincpdv_execsql($con,"ALTER TABLE ".$table." DISABLE TRIGGER USER")){
			  $con->rollback();
			  return FALSE;
			  }
			 */
			// Executa a atualizacao do campo
			if(!$this->update_sincpdv_execsql($con, $sql)){
				$con->rollback();
				return FALSE;
			}

			// Habilita as triggers da tabela
			/* 			if(!$this->update_sincpdv_execsql($con,"ALTER TABLE ".$table." ENABLE TRIGGER USER")){
			  $con->rollback();
			  return FALSE;
			  }
			 */
			$con->commit();
			return TRUE;
		}
	}

	private function update_sincpdv_execsql($con, $sql){
		if(@$con->exec($sql)){
			return TRUE;
		}else{
			$dberror = new DbError($con);
			$_SESSION["ERROR"] = $dberror->getmessage();
			return FALSE;
		}
	}

}