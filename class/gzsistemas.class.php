<?php

require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class GzSistemas{

	private $con;
	private $pdvconfig;
	private $pdvvenda;
	private $pdvfinalizador;
	private $pdvrecebepdv;

	function __construct(){
		$this->limpar_dados();
	}

	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
	}

	function exportar_cliente($return = FALSE){
		setprogress(0, "Buscando clientes", TRUE);
		$estabelecimento = $this->pdvconfig->getestabelecimento();

		$arr_linha_cliente = array();
		$arr_linha_convenio = array();
		$arr_linha_conveniado = array();
		$query = "SELECT cliente.codcliente, cliente.nome, cliente.razaosocial, cliente.enderfat, cliente.bairrofat, cidade.nome AS cidade, cidade.uf, ";
		$query .= "	cliente.cepfat, cliente.fonefat, cliente.cpfcnpj, to_char(cliente.dtnascto,'dd/mm/yy') AS dtnascto, statuscliente.bloqueado, ";
		$query .= "	clicartaoprop.valutilizado, cliente.rgie, cliente.convenio, cliente.codempresa, cliente.nummatricula, ";
		$query .= "	cliente.limite1, cliente.debito1, cliente.limite2, cliente.debito2, ";
		$query .= "	to_char((SELECT MAX(dtmovto) FROM cupom WHERE codcliente = cliente.codcliente),'dd/mm/yy')  AS dtultvenda, ";
		$query .= "	statuscliente.infostatus ";
		$query .= "FROM cliente ";
		$query .= "INNER JOIN cidade ON (cliente.codcidadefat = cidade.codcidade) ";
		$query .= "INNER JOIN statuscliente ON (cliente.codstatus = statuscliente.codstatus) ";
		$query .= "LEFT JOIN clicartaoprop ON (cliente.codcliente = clicartaoprop.codcliente) ";
		if(param("CADASTRO", "MIXCLIENTE", $this->con) == "S"){
			$query .= "INNER JOIN clienteestab ON (cliente.codcliente = clienteestab.codcliente) ";
			$query .= "WHERE clienteestab.codestabelec = ".$estabelecimento->getcodestabelec();
		}

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		// Percorre todos os clientes
		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando clientes: ".($i + 1)." de ".sizeof($arr));
			// Verifica o status do cliente
			unset($status);
			if($row["limite1"] > 0){
				if($row["debito1"] >= $row["limite1"]){
					$status = "N";
				}
			}
			if($row["limite2"] > 0){
				if($row["debito2"] >= $row["limite2"]){
					$status = "N";
				}
			}
			if(!isset($status)){
				$status = ($row["bloqueado"] == "S" ? "B" : "L");
			}
			// Verifica se nao e um cliente convenio para o arquivo principal de clientes
			if($row["convenio"] == "N"){
				$linha_cliente = ""; // Linha do arquivo de cliente
				$linha_cliente .= str_pad($row["codcliente"], 16, " ", STR_PAD_LEFT); // Codigo interno do cliente
				$linha_cliente .= str_pad(removespecial(utf8_encode($row["nome"])), 50, " ", STR_PAD_RIGHT); // Nome completo
				$linha_cliente .= str_pad(removespecial(utf8_encode($row["enderfat"])), 60, " ", STR_PAD_RIGHT); // Endereco
				$linha_cliente .= str_pad(removespecial(utf8_encode($row["bairrofat"])), 30, " ", STR_PAD_RIGHT); // Bairro
				$linha_cliente .= str_pad(removespecial($row["cidade"]), 30, " ", STR_PAD_RIGHT); // Cidade
				$linha_cliente .= str_pad($row["uf"], 2, " ", STR_PAD_RIGHT); // Estado (UF)
				$linha_cliente .= str_pad(substr(removeformat($row["cepfat"]), 0, 8), 8, " ", STR_PAD_RIGHT); // CEP
				$linha_cliente .= str_pad(substr($row["fonefat"], 0, 14), 15, " ", STR_PAD_RIGHT); // Telefone
				$linha_cliente .= str_pad($row["cpfcnpj"], 19, " ", STR_PAD_RIGHT); // CPF/CNPJ
				$linha_cliente .= str_pad($row["dtnascto"], 8, " ", STR_PAD_RIGHT); // Data de nascimento
				$linha_cliente .= str_pad($row["dtultvenda"], 8, " ", STR_PAD_RIGHT); // Data da ultima compra do cliente
				$linha_cliente .= str_pad($status, 1, " ", STR_PAD_RIGHT); // Situacao (L (liberado)/B (bloquiado)/N (negativo))
				$linha_cliente .= str_pad(number_format($row["limite1"], 2, "", ""), 12, "0", STR_PAD_LEFT); // Limite total de credito para compra convenio (fiado)
				$linha_cliente .= str_pad(number_format($row["debito1"], 2, "", ""), 12, "0", STR_PAD_LEFT); // Valor total de compras em aberto no convenio (fiado)
				$linha_cliente .= str_pad("", 4, " ", STR_PAD_LEFT); // Percentual de desconto sobre o cupom
				$linha_cliente .= str_pad("S", 1, " ", STR_PAD_RIGHT); // Desconto sobre produto em promocao
				$linha_cliente .= str_pad("S", 1, " ", STR_PAD_RIGHT); // Desconto geral
				$linha_cliente .= str_pad("S", 1, " ", STR_PAD_RIGHT); // Permite compra no convenio (fiado)
				$linha_cliente .= str_pad("", 80, " ", STR_PAD_RIGHT); // Mensagem (Aparece no pdv para o cliente)
				$linha_cliente .= str_pad(number_format($row["limite2"], 2, "", ""), 12, "0", STR_PAD_LEFT); // Limite total de credito para compra com cheque (valor)
				$linha_cliente .= str_pad("", 6, "0", STR_PAD_LEFT); // Limite total de credito para compra com cheque (quantidade)
				$linha_cliente .= str_pad(number_format($row["debito2"], 2, "", ""), 12, "0", STR_PAD_LEFT); // Valor total das comprar em aberto com cheque (valor)
				$linha_cliente .= str_pad("", 6, "0", STR_PAD_LEFT); // Valor total das comprar em aberto com cheque (quantidade)
				$linha_cliente .= str_pad("", 80, " ", STR_PAD_RIGHT); // Cartao/codigo de barras
				$linha_cliente .= str_pad("", 40, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
				$linha_cliente .= str_pad($row["rgie"], 25, " ", STR_PAD_RIGHT); // RG/IE
				$linha_cliente .= str_pad("", 9, " ", STR_PAD_LEFT); // Pontucao acumulada (fidelidade)
				$linha_cliente .= str_pad("", 2, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
				$linha_cliente .= str_pad("", 10, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
				$linha_cliente .= str_pad("", 12, " ", STR_PAD_LEFT); // Valor da ultimia compra
				$linha_cliente .= str_pad("", 12, " ", STR_PAD_LEFT); // Saldo para recebimento da conta
				$linha_cliente .= str_pad("", 6, " ", STR_PAD_LEFT); // Quantidade de documentos em aberto
				$linha_cliente .= str_pad("", 90, " ", STR_PAD_LEFT); // Finalizadores de venda bloquiado
				$linha_cliente .= str_pad("P", 1, " ", STR_PAD_RIGHT); // Tabela de preco a utilizar (P (padrao)/A (atacado)/E (especial))
				$linha_cliente .= str_pad((strlen($row["infostatus"]) > 0 ? $row["infostatus"] : "00"), 2, " ", STR_PAD_LEFT); // Nivel de bloqueio
				$linha_cliente .= str_pad("", 15, " ", STR_PAD_RIGHT); // Nome fantasia
				$linha_cliente .= str_pad("", 20, " ", STR_PAD_RIGHT); // Senha do cliente
				$linha_cliente .= str_pad("", 2, " ", STR_PAD_LEFT); // Dias para vencimento do convenio
				$linha_cliente .= str_pad("", 3, " ", STR_PAD_LEFT); // Dias para calculo do vencimento do convenio
				$linha_cliente .= str_pad("DD", 2, " ", STR_PAD_RIGHT); // Condicao para calculo do vencimento (DD (dias direto da data)/DS (Dias fora a semana)/DQ (dias fora a quinzena)/DM (dias fora o mes))
				$arr_linha_cliente[] = $linha_cliente;

				// Verifica se é um cliente conveniado
				if($row["codempresa"] > 0){
					$linha_conveniado = ""; // linha do cliente covenio
					$linha_conveniado .= str_pad($row["codempresa"], 3, " ", STR_PAD_LEFT); // Codigo do convenio
					$linha_conveniado .= str_pad($row["codcliente"], 6, " ", STR_PAD_LEFT); // Codigo interno do cliente
					$linha_conveniado .= str_pad(substr($row["nummatricula"], 0, 15), 15, " ", STR_PAD_RIGHT); // Numero matricula
					$linha_conveniado .= str_pad(removespecial($row["nome"]), 50, " ", STR_PAD_RIGHT); // Nome Completo
					$linha_conveniado .= str_pad("", 50, " ", STR_PAD_RIGHT); // 1ª pessoa altorizada a comprar
					$linha_conveniado .= str_pad("", 50, " ", STR_PAD_RIGHT); // 2ª pessoa altorizada a comprar
					$linha_conveniado .= str_pad("", 50, " ", STR_PAD_RIGHT); // 3ª pessoa altorizada a comprar
					$linha_conveniado .= str_pad(removespecial($row["enderfat"]), 60, " ", STR_PAD_RIGHT); // Endereco
					$linha_conveniado .= str_pad(removespecial($row["bairrofat"]), 30, " ", STR_PAD_RIGHT); // Bairro
					$linha_conveniado .= str_pad(removespecial($row["cidade"]), 30, " ", STR_PAD_RIGHT); // Cidade
					$linha_conveniado .= str_pad($row["uf"], 2, " ", STR_PAD_RIGHT); // Estado (UF)
					$linha_conveniado .= str_pad(removeformat($row["cepfat"]), 8, " ", STR_PAD_RIGHT); // CEP
					$linha_conveniado .= str_pad($row["dtnascto"], 8, " ", STR_PAD_RIGHT); // Data de nascimento
					$linha_conveniado .= str_pad($status, 1, " ", STR_PAD_RIGHT); // Situacao (L (liberado)/B (bloquiado)/N (negativo))
					$linha_conveniado .= str_pad(number_format($row["limite1"], 2, "", ""), 12, "0", STR_PAD_LEFT); // Valor do limite de compras no convenio (fiado)
					$linha_conveniado .= str_pad(number_format($row["debito1"], 2, "", ""), 12, "0", STR_PAD_LEFT); // Valor total das compras em aberto
					$linha_conveniado .= str_pad("", 4, " ", STR_PAD_LEFT); // Percentual de desconto sobre o cupom
					$linha_conveniado .= str_pad("S", 1, " ", STR_PAD_RIGHT); // Desconto sobre produto em promocao
					$linha_conveniado .= str_pad("S", 1, " ", STR_PAD_RIGHT); // Desconto sobre todos os produtos
					$linha_conveniado .= str_pad($row["nummatricula"], 80, " ", STR_PAD_RIGHT); // Codigo de barras do cartao
					$linha_conveniado .= str_pad("", 40, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
					$linha_conveniado .= str_pad("", 9, " ", STR_PAD_LEFT); // Pontuacao acumulada (fidelidade)
					$linha_conveniado .= str_pad("", 10, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
					$linha_conveniado .= str_pad($row["cpfcnpj"], 19, " ", STR_PAD_RIGHT); // CPF/CNPJ
					$linha_conveniado .= str_pad($row["rgie"], 25, " ", STR_PAD_RIGHT); // RG/IE
					$linha_conveniado .= str_pad(substr($row["fonefat"], 0, 15), 15, " ", STR_PAD_RIGHT); // Telefone
					$linha_conveniado .= str_pad("", 2, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
					$linha_conveniado .= str_pad($row["dtultvenda"], 8, " ", STR_PAD_RIGHT); // Data da ultima compra do cliente
					$linha_conveniado .= str_pad(number_format(0, 2, "", ""), 12, "0", STR_PAD_LEFT); // Valor da ultima compra do cliente
					$linha_conveniado .= str_pad("", 90, " ", STR_PAD_LEFT); // Finalizadores de venda bloquiado
					$linha_conveniado .= str_pad("", 80, " ", STR_PAD_RIGHT); // Mensagem (Aparece no pdv para o cliente)
					$linha_conveniado .= str_pad("P", 1, " ", STR_PAD_RIGHT); // Tabela de preco a utilizar (P (padrao)/A (atacado)/E (especial))
					$linha_conveniado .= str_pad((strlen($row["infostatus"]) > 0 ? $row["infostatus"] : "00"), 2, " ", STR_PAD_LEFT); // Nivel de bloqueio
					$linha_conveniado .= str_pad("", 1, " ", STR_PAD_RIGHT); // Tipo de bloqueio de produto (G (grupo)/D (departamento)/M (marca))
					$linha_conveniado .= str_pad("", 15, " ", STR_PAD_RIGHT); // Chapa
					$linha_conveniado .= str_pad("", 20, " ", STR_PAD_LEFT); // Senha do cliente
					$arr_linha_conveniado[] = $linha_conveniado;
				}

				// Verifica se é um cliente convenio (empresa)
			}elseif($row["convenio"] == "S"){
				$linha_convenio = ""; // linha do cliente covenio
				$linha_convenio .= str_pad($row["codcliente"], 3, " ", STR_PAD_LEFT); // Codigo interno do cliente
				$linha_convenio .= str_pad(removespecial($row["razaosocial"]), 50, " ", STR_PAD_RIGHT); // Nome completo
				$linha_convenio .= str_pad(substr(removespecial($row["nome"]), 0, 15), 15, " ", STR_PAD_RIGHT); // Nome fantasia
				$linha_convenio .= str_pad(removespecial($row["enderfat"]), 60, " ", STR_PAD_RIGHT); // Endereco
				$linha_convenio .= str_pad(removespecial($row["bairrofat"]), 30, " ", STR_PAD_RIGHT); // Bairro
				$linha_convenio .= str_pad(removespecial($row["cidade"]), 30, " ", STR_PAD_RIGHT); // Cidade
				$linha_convenio .= str_pad($row["uf"], 2, " ", STR_PAD_RIGHT); // Estado (UF)
				$linha_convenio .= str_pad(removeformat($row["cepfat"]), 8, " ", STR_PAD_RIGHT); // CEP
				$linha_convenio .= str_pad($status, 1, " ", STR_PAD_RIGHT); // Situacao (L (liberado)/B (bloquiado)/N (negativo))
				$linha_convenio .= str_pad("", 4, " ", STR_PAD_LEFT); // Percentual de desconto sobre o cupom
				$linha_convenio .= str_pad("S", 1, " ", STR_PAD_RIGHT); // Desconto sobre produto em promocao
				$linha_convenio .= str_pad($row["cpfcnpj"], 19, " ", STR_PAD_RIGHT); // CPF/CNPJ
				$linha_convenio .= str_pad($row["rgie"], 25, " ", STR_PAD_RIGHT); // RG/IE
				$linha_convenio .= str_pad("", 2, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
				$linha_convenio .= str_pad(substr($row["fonefat"], 0, 15), 15, " ", STR_PAD_RIGHT); // Telefone
				$linha_convenio .= str_pad("P", 1, " ", STR_PAD_RIGHT); // Tabela de preco a utilizar (P (padrao)/A (atacado)/E (especial))
				$linha_convenio .= str_pad("00", 2, " ", STR_PAD_LEFT); // Nivel de bloqueio
				$linha_convenio .= str_pad("", 1, " ", STR_PAD_RIGHT); // Tipo de bloqueio de produto (G (grupo)/D (departamento)/M (marca))
				$linha_convenio .= str_pad("", 240, " ", STR_PAD_RIGHT); // Dados do bloqueio
				$linha_convenio .= str_pad("", 20, " ", STR_PAD_LEFT); // Produto alternativo
				$arr_linha_convenio[] = $linha_convenio;
			}
		}

		if($return){
			return array(
				$this->pdvconfig->file_create("CLIENTES.TXT", $arr_linha_cliente, "w+", TRUE),
				$this->pdvconfig->file_create("EMPRESA.TXT", $arr_linha_convenio, "w+", TRUE),
				$this->pdvconfig->file_create("CLIENTEC.TXT", $arr_linha_conveniado, "w+", TRUE)
			);
		}else{
			// Cria arquivo de clientes
			$this->pdvconfig->file_create("CLIENTES.TXT", $arr_linha_cliente);
			// Cria arquivo de clientes convenio
			$this->pdvconfig->file_create("EMPRESA.TXT", $arr_linha_convenio);
			// Cria arquivo de clientes conveniado
			$this->pdvconfig->file_create("CLIENTEC.TXT", $arr_linha_conveniado);
		}
	}

	function exportar_produto($return = FALSE){
		$ini_parametro = parse_ini_string($this->pdvconfig->getfrentecaixa()->getparametro());

		$arr_linha_produto = array(); // Linhas do arquivo de produto
		$arr_linha_produtoean = array(); // Linhas do arquivo de eans
		$arr_linha_vasilhame = array(); // Linhas do arquivo de vasilhame
		$arr_linha_composicao = array(); // Linhas do arquivo de composicao
		// Procura os vasilhames da loja
		setprogress(0, "Buscando vasilhames", TRUE);
		$arr_vasilhame = array(); // Vetor que contem os vasilhames que estao no produto (o indice e o codigo do produto e o valor e numero dio vasilhame no arquivo)
		$res = $this->con->query("SELECT produto.codproduto, ".$this->pdvconfig->sql_descricao().", ".$this->pdvconfig->sql_tipopreco()." FROM produtoestab INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) WHERE codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." AND vasilhame = 'S' ORDER BY produto.datainclusao , produto.codproduto ");
		$arr = $res->fetchAll(2);
		foreach($arr as $row){
			$arr_vasilhame[$row["codproduto"]] = sizeof($arr_vasilhame) + 1;
			$linha = ""; // Linha do arquivo de vasilhame
			$linha .= str_pad($arr_vasilhame[$row["codproduto"]], 4, "0", STR_PAD_LEFT); // Codigo sequencial do vasilhame
			$linha .= str_pad(substr($row["descricaofiscal"], 0, 20), 20, " ", STR_PAD_RIGHT); // Descricao
			$linha .= str_pad(number_format($row["preco"], 2, "", ""), 8, "0", STR_PAD_LEFT); // Preco
			$linha .= str_pad($row["codproduto"], 20, " ", STR_PAD_LEFT); // Codigo interno do produto
			$arr_linha_vasilhame[] = $linha;
		}

		// Procura as composicoes (explode na venda com explosao automatica)
		setprogress(0, "Buscando composicoes", TRUE);
		$arr_codproduto_composicao = array();
		$query = "SELECT composicao_a.codproduto AS codprodutopai, itcomposicao_a.codproduto AS codprodutofilho, itcomposicao_a.quantidade, ".$this->pdvconfig->sql_descricao().", ";
		$query .= "	produtoestab_b.precoatc AS precoatc_pn, produtoestab_b.precovrj AS precovrj_pn, produtoestab_b.precoatcof AS precoatcof_pn, produtoestab_b.precovrjof AS precovrjof_pn, ";
		$query .= "	produtoestab_a.precoatc AS precoatc_fa, produtoestab_a.precovrj AS precovrj_fa, produtoestab_a.precoatcof AS precoatcof_fa, produtoestab_a.precovrjof AS precovrjof_fa, ";
		$query .= "	(SELECT SUM(precoatc * quantidade) FROM itcomposicao AS itcomposicao_c INNER JOIN produtoestab AS produtoestab_c ON (itcomposicao_c.codproduto = produtoestab_c.codproduto) WHERE itcomposicao_c.codcomposicao = composicao_a.codcomposicao AND produtoestab_c.codestabelec = produtoestab_a.codestabelec) AS precoatc_pa, ";
		$query .= "	(SELECT SUM(precovrj * quantidade) FROM itcomposicao AS itcomposicao_c INNER JOIN produtoestab AS produtoestab_c ON (itcomposicao_c.codproduto = produtoestab_c.codproduto) WHERE itcomposicao_c.codcomposicao = composicao_a.codcomposicao AND produtoestab_c.codestabelec = produtoestab_a.codestabelec) AS precovrj_pa, ";
		$query .= "	(SELECT SUM(precoatcof * quantidade) FROM itcomposicao AS itcomposicao_c INNER JOIN produtoestab AS produtoestab_c ON (itcomposicao_c.codproduto = produtoestab_c.codproduto) WHERE itcomposicao_c.codcomposicao = composicao_a.codcomposicao AND produtoestab_c.codestabelec = produtoestab_a.codestabelec) AS precoatcof_pa, ";
		$query .= "	(SELECT SUM(precovrjof * quantidade) FROM itcomposicao AS itcomposicao_c INNER JOIN produtoestab AS produtoestab_c ON (itcomposicao_c.codproduto = produtoestab_c.codproduto) WHERE itcomposicao_c.codcomposicao = composicao_a.codcomposicao AND produtoestab_c.codestabelec = produtoestab_a.codestabelec) AS precovrjof_pa ";
		$query .= "FROM itcomposicao AS itcomposicao_a ";
		$query .= "INNER JOIN composicao AS composicao_a ON (itcomposicao_a.codcomposicao = composicao_a.codcomposicao) ";
		$query .= "INNER JOIN produtoestab AS produtoestab_a ON (itcomposicao_a.codproduto = produtoestab_a.codproduto) ";
		$query .= "INNER JOIN produtoestab AS produtoestab_b ON (composicao_a.codproduto = produtoestab_b.codproduto AND produtoestab_a.codestabelec = produtoestab_b.codestabelec) ";
		$query .= "INNER JOIN produto ON (itcomposicao_a.codproduto = produto.codproduto) ";
		$query .= "WHERE composicao_a.tipo = 'V' ";
		$query .= "	AND produto.gerapdv = 'S' ";
		$query .= "	AND composicao_a.explosaoauto = 'S' ";
		$query .= "	AND produtoestab_a.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND composicao_a.codproduto IN (SELECT codproduto FROM produto WHERE datalog >='".date("Y/m/d")."') ";
		}
		$query .= "ORDER BY composicao_a.codproduto, itcomposicao_a.quantidade DESC ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$codprodutopai = "";
		foreach($arr as $i => $row){
			$arr_codproduto_composicao[] = $row["codprodutopai"];
			if($row["precoatc_pa"] == 0){ $row["precoatc_pa"] = 1; }
			if($row["precovrj_pa"] == 0){ $row["precovrj_pa"] = 1; }
			if($row["precoatcof_pa"] == 0){ $row["precoatcof_pa"] = 1; }
			if($row["precovrjof_pa"] == 0){ $row["precovrjof_pa"] = 1; }
			$row["precoatc_fn"] = ($row["precoatc_pn"] * $row["precoatc_fa"]) / $row["precoatc_pa"];
			$row["precovrj_fn"] = ($row["precovrj_pn"] * $row["precovrj_fa"]) / $row["precovrj_pa"];
			$row["precoatcof_fn"] = ($row["precoatcof_pn"] * $row["precoatcof_fa"]) / $row["precoatcof_pa"];
			$row["precovrjof_fn"] = ($row["precovrjof_pn"] * $row["precovrjof_fa"]) / $row["precovrjof_pa"];
			if($row["codprodutopai"] == $codprodutopai){
				$row["precovrj_fn"] = round($row["precovrj_fn"], 2);
				$total_precovrj += ($row["precovrj_fn"] * $row["quantidade"]);
				if($total_precovrj > $row["precovrj_pn"]){
					$row["precovrj_fn"] -= (($total_precovrj - $row["precovrj_pn"]) / $row["quantidade"]);
				}elseif($arr[$i + 1]["codprodutopai"] != $row["codprodutopai"] && $total_precovrj < $row["precovrj_pn"]){
					$row["precovrj_fn"] = $row["precovrj_fn"] + (($row["precovrj_pn"] - $total_precovrj) / $row["quantidade"]);
				}else{
					$row["precovrj_fn"] = round($row["precovrj_fn"], 2);
				}
			}else{
				$codprodutopai = $row["codprodutopai"];
				$row["precovrj_fn"] = round($row["precovrj_fn"], 2);
				$total_precovrj = $row["precovrj_fn"] * $row["quantidade"];
			}
			$linha = str_pad($row["codprodutopai"], 20, "0", STR_PAD_LEFT); // Codigo do produto pai
			$linha .= str_pad($row["codprodutofilho"], 20, "0", STR_PAD_LEFT); // Codigo do produto filho
			$linha .= str_pad(number_format($row["quantidade"], 3, "", ""), 7, "0", STR_PAD_LEFT); // Quantidade
			$linha .= str_pad(number_format(($this->tipopreco == "A" ? $row["precoatc_fn"] : $row["precovrj_fn"]), 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco de venda
			$linha .= str_pad(substr($row["descricao"], 0, 24), 24, " ", STR_PAD_RIGHT); // Descricao resumida do produto
			$linha .= str_pad(number_format(($this->tipopreco == "A" ? $row["precoatcof_fn"] : $row["precovrjof_fn"]), 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco de venda promocional
			$linha .= str_pad(number_format(0, 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco de venda atacado
			$linha .= str_pad(number_format(0, 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco de venda especial
			$linha .= str_pad($this->pdvconfig->getestabelecimento()->getcodestabelec(), 3, "0", STR_PAD_LEFT); // Estabelecimento
			$linha .= str_pad(" ", 5, " ", STR_PAD_LEFT); // Bancos
			$arr_linha_composicao[] = $linha;
		}
		$arr_codproduto_composicao = array_unique($arr_codproduto_composicao);

		// Busca as informacoes de tributacoes
		setprogress(0, "Buscando tributacoes", TRUE);
		$res = $this->con->query("SELECT * FROM icmspdv WHERE codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec());
		$arr_icmspdv = $res->fetchAll(2);

		// Procura os produtos da loja
		setprogress(0, "Buscando produtos", TRUE);
		$query = "SELECT produtoestab.qtdatacado, produto.codproduto, ".$this->pdvconfig->sql_descricao().", produto.pesado, produto.coddepto, ";
		$query .= " produtoestab.codestabelec, ";
		$query .= "	produtoestab.precoatc, produtoestab.precovrj, produtoestab.precoatcof, produtoestab.precovrjof, ";
		$query .= "	produtoestab.sldatual, unidade.sigla, produto.precovariavel, embalagem.quantidade, classfiscal.codcst, ";
		$query .= "	produto.foralinha, produto.codvasilhame, classfiscal.tptribicms, classfiscal.aliqicms, classfiscal.aliqredicms, ";
		$query .= " COALESCE(produto.aliqmedia,ncm.aliqmedia) AS aliqmedia, ";
		$query .= "	COALESCE(ibptestabelec.aliqnacionalfederal, produto.aliqmedia, ncm.aliqmedia) AS aliqmedianacional, ";
		$query .= "	COALESCE(ibptestabelec.aliqestadual, produto.aliqmedia, ncm.aliqmedia) AS aliqmediaestadual, ";
		$query .= " ncm.codigoncm, (CASE WHEN produto.cest IS NOT NULL THEN produto.cest ELSE cest.cest END) AS cest, ";
		$query .= " piscofins.aliqpis, piscofins.aliqcofins, piscofins.codcst AS cstpiscofins, ";
		$query .= " produto.codsimilar, simprod.descricao as descricaosimilar,  ";
		$query .= " (select localizacao from produtolocalizacao WHERE codproduto = produto.codproduto LIMIT 1) AS microterminal, ";
		if($this->pdvconfig->getfrentecaixa()->getbalancaean() == "S"){
			$query .= "	(SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) AS codean ";
		}else{
			$query .= "	(CASE WHEN produto.pesado = 'S' THEN '' ELSE (SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) END) AS codean ";
		}
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "LEFT JOIN simprod ON (produto.codsimilar = simprod.codsimilar)";
		$query .= "INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "INNER JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
		$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= "LEFT JOIN cest ON (ncm.idcest = cest.idcest) ";
		$query .= "LEFT JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "INNER JOIN estabelecimento ON (produtoestab.codestabelec = estabelecimento.codestabelec) ";
		$query .= "LEFT JOIN estadotributo ON (estabelecimento.uf = estadotributo.uf AND estabelecimento.regimetributario = estadotributo.regimetributario AND produto.codproduto = estadotributo.codproduto) ";
		$query .= "LEFT JOIN ibptestabelec ON (replace(ncm.codigoncm,'.','') = replace(ibptestabelec.codigoncm,'.','') AND produtoestab.codestabelec = ibptestabelec.codestabelec) ";
		$query .= "INNER JOIN classfiscal ON (COALESCE(estadotributo.codcfpdv,produto.codcfpdv) = classfiscal.codcf) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produtoestab.disponivel = 'S' AND produto.gerapdv = 'S' ";
		if(param("ESTOQUE", "CARGAITEMCOMESTOQ", $this->con) == "S"){
			$query .= " AND produtoestab.sldatual > 0 ";
		}
		$query .= "	AND ".sql_tipopreco($this->pdvconfig->gettipopreco(), TRUE, NULL)." > 0 ";
		if(param("ESTOQUE", "ENVIAFORALINHAPDV", $this->con) == "N"){
			$query .= "	AND produto.foralinha = 'N' ";
		}
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND ".$this->pdvconfig->produto_parcial_query();
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		$arr_codproduto = array();

		// Percorre os produtos
		foreach($arr as $i => $row){
			setprogress(($i + 1) / sizeof($arr) * 100, "Exportando produtos: ".($i + 1)." de ".sizeof($arr));

			$arr_codproduto[] = $row["codproduto"];

			// Arquivo de codigos de barra
			if($row["pesado"] == "N" || ($row["pesado"] == "S" && $this->pdvconfig->getfrentecaixa()->getbalancaean() == "S")){
				$res = $this->con->query("SELECT * FROM produtoean WHERE codproduto = ".$row["codproduto"]." AND codean <> '".$row["codean"]."'");
				$arr_produtoean = $res->fetchAll(2);
				foreach($arr_produtoean as $row_produtoean){
					$linha = ""; // Linha do arquivo de ean
					$linha .= str_pad($row_produtoean["codproduto"], 20, " ", STR_PAD_LEFT); // Codigo interno do produto
					$linha .= str_pad(substr($row_produtoean["codean"], 0, 20), 20, " ", STR_PAD_LEFT); // Ean do produto
					$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Observacao
					$linha .= str_pad(number_format($row_produtoean["quantidade"], 3, "", ""), 9, "0", STR_PAD_LEFT); // Quantidade do produto pelo ean
					$linha .= str_pad("", 9, " ", STR_PAD_LEFT); // Preco (em branco para utilizar do cadastro)
					$arr_linha_produtoean[] = $linha;
				}
			}

			// Acha a tributacao certa do produto
			$_icmspdv = "N";
			foreach($arr_icmspdv as $icmspdv){
				if($row["tptribicms"] == $icmspdv["tipoicms"] && $icmspdv["aliqicms"] == $row["aliqicms"] && $icmspdv["redicms"] == $row["aliqredicms"]){
					$_icmspdv = "S";
					break;
				}
			}
			// Verifica a tributacao no cadastro de PDV
			if($_icmspdv == "N"){
				echo messagebox("error", "", "N&atilde;o encontrado informa&ccedil;&otilde;es tributarias para o PDV do produto <b>".$row["codproduto"]."</b>: \n\n <b>Tipo de Tributa&ccedil;&atilde;o</b> = ".$row["tptribicms"]."\n<b>Aliquota </b> = ".$row["aliqicms"]."\n <b>Aliquota de Redu&ccedil;&atilde;o</b> = ".$row["aliqredicms"]."\n\n <a onclick=\"openProgram('InfTribPDV')\">Clique aqui</a> para abrir o cadastro de tributa&ccedil;&atilde;o do PDV.");
				die();
			}

			$linha = ""; // Linha do arquivo de produto
			$linha .= str_pad($row["codproduto"], 20, " ", STR_PAD_LEFT); // Codigo interno
			$linha .= str_pad((strlen($row["codean"]) > 0 ? $row["codean"] : $row["codproduto"]), 20, " ", STR_PAD_LEFT); // Ean pricipal
			$linha .= str_pad(strtoupper(substr(removespecial(utf8_decode($row["descricaofiscal"])), 0, 40)), 40, " ", STR_PAD_RIGHT); // Descricao completa
			$linha .= str_pad(strtoupper(substr(removespecial(utf8_decode($row["descricao"])), 0, 24)), 24, " ", STR_PAD_RIGHT); // Descricao resumida
			$linha .= str_pad((in_array($row["codproduto"], $arr_codproduto_composicao) ? "S" : "N"), 1, " ", STR_PAD_RIGHT); // Produto composto
			$linha .= str_pad(($row["pesado"] == "N" ? "UN" : substr($row["sigla"], 0, 2)), 2, " ", STR_PAD_RIGHT); // Unidade (sempre deve ser UN quando o produto nao for de balanca)
			$linha .= str_pad("", 4, " ", STR_PAD_LEFT); // Armacao/localizacao
			$linha .= str_pad(substr($row["coddepto"], 0, 2), 2, "0", STR_PAD_LEFT); // Codigo do departamento
			$linha .= str_pad(number_format(($this->pdvconfig->gettipopreco() == "A" ? $row["precoatc"] : $row["precovrj"]), 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco normal
			$linha .= str_pad(number_format(($this->pdvconfig->gettipopreco() == "A" ? $row["precoatcof"] : $row["precovrjof"]), 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco promocional
			$linha .= str_pad(number_format($row["sldatual"], 3, "", ""), 12, "0", STR_PAD_LEFT); // Saldo no estoque
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Desconto padrao (C (cliente convenio)/L (cliente loja)/A (ambos)/N (nenhum))
			$linha .= str_pad($row["pesado"], 1, " ", STR_PAD_RIGHT); // Produto de balanca
			$linha .= str_pad($row["precovariavel"], 1, " ", STR_PAD_RIGHT); // Preco variavel
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Bloqueia multiplicador
			$linha .= str_pad("", 6, " ", STR_PAD_RIGHT); // Promocao Leve X e Pague Y (3 caracteres pro Leve e 3 para o Pague)
			$linha .= str_pad($icmspdv["infpdv"], 2, "0", STR_PAD_LEFT); // Codigo da tributacao
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
			$linha .= str_pad(number_format($row["quantidade"], 3, "", ""), 7, "0", STR_PAD_LEFT); // Quantidade por embalagem
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Vende somente embalagem fechada
			$linha .= str_pad("", 4, " ", STR_PAD_LEFT); // Desconto na embalagem fechada (percentual)
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Pede descricao complementar
			$linha .= str_pad("", 80, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
			$linha .= str_pad(number_format($row["precoatc"], 3, "", ""), 9, "0", STR_PAD_LEFT); // Preco atacado
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Bloqueia venda fracionada
			$linha .= str_pad("", 40, " ", STR_PAD_RIGHT); // Referencia
			$linha .= str_pad($row["codcst"], 3, " ", STR_PAD_RIGHT); // Situacao tributaria
			$linha .= "A"; //str_pad(($row["foralinha"] == "S" ? "I" : "A"),1," ",STR_PAD_RIGHT); // Estado do produto (A (ativo)/I (inativo))
			$linha .= str_pad($arr_vasilhame[$row["codvasilhame"]], 4, "0", STR_PAD_LEFT); // Codigo do vasilhame
			$linha .= str_pad("", 81, " ", STR_PAD_RIGHT); // Espaco em branco (reservado)
			$linha .= str_pad("", 4, " ", STR_PAD_LEFT); // Desconto maximo permitido (percentual)
			$linha .= str_pad("", 7, " ", STR_PAD_LEFT); // Quantidade por embalagem em atacado
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Venda de embalagem fechada em atacado
			$linha .= str_pad("", 4, " ", STR_PAD_LEFT); // Desconto em atacado (percentual)
			$linha .= str_pad(removeformat($row["codigoncm"]), 10, " ", STR_PAD_RIGHT); // Classificacao fiscal
			$linha .= str_pad("", 12, " ", STR_PAD_LEFT); // Quantidade pendente
			$linha .= str_pad("", 4, " ", STR_PAD_RIGHT); // Validade da quantidade pendente
			$linha .= str_pad("", 9, " ", STR_PAD_LEFT); // Preco de venda especial
			$linha .= str_pad("", 7, " ", STR_PAD_LEFT); // Quantidade por embalagem em especial
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Venda de embalagem fechada em especial
			$linha .= str_pad("", 4, " ", STR_PAD_LEFT); // Desconto em especial (percentual)
			$linha .= str_pad(number_format($row["qtdatacado"], 3, "", ""), 12, "0", STR_PAD_LEFT); // Quantidade minima para preco atacado
			$linha .= str_pad("", 12, " ", STR_PAD_LEFT); // Quantidade minima para preco especial
			$linha .= str_pad("", 6, " ", STR_PAD_LEFT); // Codigo do grupo
			$linha .= str_pad("", 6, " ", STR_PAD_LEFT); // Codigo do departamento
			$linha .= str_pad("", 6, " ", STR_PAD_LEFT); // Codigo da marca
			$linha .= str_pad("", 8, " ", STR_PAD_LEFT); // Pontos clube fidelidade
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Base calculo clube fidelidade
			$linha .= str_pad("", 20, " ", STR_PAD_LEFT); // Codigo do produto associado
			$linha .= str_pad("", 3, " ", STR_PAD_LEFT); // Grupo de finalizadores
			$linha .= str_pad("", 9, " ", STR_PAD_LEFT); // Desconto finalizadores especificos
			$linha .= str_pad("", 90, " ", STR_PAD_RIGHT); // Finalizadores para desconto
			$linha .= str_pad(substr($row["microterminal"], 0, 2), 2, " ", STR_PAD_LEFT); // 1º micro-terminal de impressao
			$linha .= str_pad("", 6, " ", STR_PAD_LEFT); // Quantidade maxima de item por compra
			$linha .= str_pad("", 1, " ", STR_PAD_RIGHT); // Cupom vinculado (1 (cupom para retirada) / 2 (cupom de entrega))
			$linha .= str_pad("N", 1, " ", STR_PAD_RIGHT); // Bloquear venda quando nao houver estoque
			$linha .= str_pad("0", 1, " ", STR_PAD_RIGHT); // Tipo do produto (0 (produto) / 1 (servico))
			$linha .= str_pad("", 2, " ", STR_PAD_LEFT); // 2º micro-terminal de impressao
			$linha .= str_pad("", 2, " ", STR_PAD_LEFT); // 3º micro-terminal de impressao
			$linha .= str_pad("", 2, " ", STR_PAD_LEFT); // 4º micro-terminal de impressao
			$linha .= str_pad("", 2, " ", STR_PAD_LEFT); // 5º micro-terminal de impressao
			$linha .= "N"; // Solita senha para venda
			$linha .= $this->valor_numerico(0, 0, 3); // Grupo de balcao
			$linha .= "T"; // Proprio ou terceiros
			$linha .= "T"; // Arredondamento ou truncamento
			$linha .= $this->valor_numerico($row["precovrj"], 3, 9); // Preco maximo de venda ao consumidor
			$linha .= "000"; // Tipo do produto
			$linha .= $this->valor_numerico($row["aliqmedia"], 2, 4); // Carga tributaria

			if($this->pdvconfig->getfrentecaixa()->getversao() == "arius2"){
				$linha .= str_pad(" ", 4, " ", STR_PAD_LEFT); // CSOSN
				$linha .= "N"; // Entregavel
				$linha .= str_pad(removespecial($row["aliqmediaestadual"]), 4, " ", STR_PAD_LEFT); // Carga Tributária Estadual
				$linha .= str_pad("", 10, " ", STR_PAD_LEFT); // Chave Tabela IBPT
				$linha .= str_pad("", 100, " ", STR_PAD_LEFT); // Reservado - Espaço em Branco
				$linha .= str_pad($row["cstpiscofins"], 2, " ", STR_PAD_LEFT); // CST Pis
				$linha .= str_pad($row["cstpiscofins"], 2, " ", STR_PAD_LEFT); // CST Cofins
				$linha .= $this->valor_numerico($row["aliqpis"], 2, 5); // Percentual Pis
				$linha .= $this->valor_numerico($row["aliqcofins"], 2, 5); // Percentual Cofins
				$linha .= str_pad(removeformat($row["cest"]), 7, " ", STR_PAD_LEFT); // Codigo CEST
			}
			$arr_linha_produto[] = $linha;
		}

		$return_files = array();

		// Oferta para PDV Arius
		if(strlen($this->pdvconfig->getfrentecaixa()->getversao()) > 0){
			$arr_linha_grupooferta = array();
			$arr_linha_oferta = array();
			foreach($arr as $i => $row){
				if($row["qtdatacado"] <= 0 || $row["precoatc"] <= 0 && $row["sldatual"] > 0){
					continue;
				}

				$codgrupo = strlen($row["codsimilar"]) > 0 ? ($row["codsimilar"] + 1000000) : $row["codproduto"];
				$descricaopromocao = strlen($row["codsimilar"]) > 0 ? $row["descricaosimilar"] : $row["descricaofiscal"];

				$linha  = $this->texto($row["codestabelec"], 3, "0","E"); // Loja
				$linha .= $this->texto($codgrupo, 7, "0", "E"); // Código Grupo
				$linha .= str_pad(substr($descricaopromocao,0,30), 30, " ", STR_PAD_LEFT); // Descrição Grupo
				$linha .= $this->texto($row["codproduto"], 14, "0", "E"); // Código Interno
				$arr_linha_grupooferta[] = $linha;

				$linha  = $this->texto($row["codestabelec"], 3, "0", "E"); // Loja
				$linha .= str_pad(substr($descricaopromocao,0,30), 30, " ", STR_PAD_LEFT); // Descrição Promoção
				$linha .= "06"; // Tipo de Promoção
				$linha .= $this->texto(	$codgrupo,7, "0","E"); // Código Grupo Gatilho
				$linha .= $this->texto((integer)$row["qtdatacado"], 6, "0", "E"); // Quantidade Gatilho
				$linha .= $this->texto($codgrupo,7,"0","E"); // Código Grupo Desconto
				$linha .= $this->texto("0", 11, "0", "E"); // Quantidade Grupo Desconto
				$linha .= $this->texto("0", 9, "0", "E"); // % de Desconto
				$linha .= $this->texto(number_format($row["precoatc"],2,"",""), 9, "0", "E"); // Valor Final Unitário
				$linha .= str_pad(date("Y-m-d"), 10, " ", STR_PAD_LEFT); // Data Inicio (aaaa-mm-dd)
				$linha .= str_pad(date("Y-m-d"), 10, " ", STR_PAD_LEFT); // Data Fim (aaaa-mm-dd)
				$linha .= $this->texto("030000", 6, " ", STR_PAD_RIGHT); // Qtd Max Faixa gatilho
				//$linha .= str_pad(substr($row["codproduto"],0,9), 9, " ", STR_PAD_LEFT); // Código da Promoção
				$arr_linha_oferta[] = $linha;
			}
			if($return){
				$return_files[] = $this->pdvconfig->file_create("PACK_GRUPO.TXT", $arr_linha_grupooferta, "w+", TRUE);
				$return_files[] = $this->pdvconfig->file_create("PACK_PROMOCOES.TXT", $arr_linha_oferta, "w+", TRUE);
			}else{
				$this->pdvconfig->file_create("PACK_GRUPO.TXT", $arr_linha_grupooferta);
				$this->pdvconfig->file_create("PACK_PROMOCOES.TXT", $arr_linha_oferta);
			}
		}

		$this->pdvconfig->atualizar_precopdv($arr_codproduto);


		$arr_flexconxcent = null;

		if($ini_parametro["flexconcent"] == "S"){
			// Cria arquivo carga geral/parcial em branco segundo layout página 53
			if($this->pdvconfig->produto_parcial()){
				$filename_carga = "CARGA.PARCIAL";
			}else{
				$filename_carga = "CARGA.GERAL";
			}
			if($return){
				$arr_flexconxcent =  $this->pdvconfig->file_create($filename_carga, array(), "w+", TRUE);
			}else{
				$this->pdvconfig->file_create($filename_carga, array());
			}
		}

		if($return){
			$return_files[] = $this->pdvconfig->file_create("ESTOQUE.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_produto, "w+", TRUE);
			$return_files[] = $this->pdvconfig->file_create("BARRAREL.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_produtoean, "w+", TRUE);
			$return_files[] = $this->pdvconfig->file_create("VASILHAM.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_vasilhame, "w+", TRUE);
			$return_files[] = $this->pdvconfig->file_create("FORMULA.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_composicao, "w+", TRUE);
			$return_files[] = $arr_flexconxcent;
			return $return_files;
		}else{
			// Cria arquivo de produtos
			$this->pdvconfig->file_create("ESTOQUE.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_produto);
			// Cria arquivo de eans
			$this->pdvconfig->file_create("BARRAREL.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_produtoean);
			// Cria arquivo de vasilhames
			$this->pdvconfig->file_create("VASILHAM.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_vasilhame);
			// Cria arquivo de composicoes
			$this->pdvconfig->file_create("FORMULA.".($this->pdvconfig->produto_parcial() ? "ITE" : "TXT"), $arr_linha_composicao);
		}
	}

	function exportar_vendedor($return = FALSE){
		setprogress(0, "Buscando vendedores", TRUE);
		$arr_linha = array();
		$query = "SELECT * FROM funcionario ORDER BY codfunc";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		if(is_array($arr)){
			// Percorre todos os funcionario
			foreach($arr as $i => $row){
				setprogress(($i + 1) / sizeof($arr) * 100, "Exportando vendedores: ".($i + 1)." de ".sizeof($arr));
				$linha = ""; // Linha do arquivo de funcionario
				$linha .= str_pad($row["codfunc"], 4, " ", STR_PAD_LEFT); // Codigo interno do funcionario
				$linha .= str_pad(substr($row["nome"], 0, 15), 15, " ", STR_PAD_RIGHT); // Nome fantasia/Apelido
				$linha .= str_pad(substr($row["nome"], 0, 50), 50, " ", STR_PAD_RIGHT); // Nome completo
				$linha .= str_pad(number_format($row["comissao"], 2, "", ""), 5, " ", STR_PAD_LEFT); // Comissao padrao
				$linha .= str_pad("", 5, " ", STR_PAD_LEFT); // 1º percentual de comissao sobre a venda
				$linha .= str_pad("", 5, " ", STR_PAD_LEFT); // 2º percentual de comissao sobre a venda
				$linha .= str_pad("", 5, " ", STR_PAD_LEFT); // 3º percentual de comissao sobre a venda
				$linha .= str_pad("", 5, " ", STR_PAD_LEFT); // 4º percentual de comissao sobre a venda
				$linha .= str_pad("", 5, " ", STR_PAD_LEFT); // Percentual de desconto maximo sobre o produto
				$arr_linha[] = $linha;
			}
		}
		if($return){
			return array($this->pdvconfig->file_create("VENDEDOR.TXT", $arr_linha, "w+", TRUE));
		}else{
			$this->pdvconfig->file_create("VENDEDOR.TXT", $arr_linha);
		}
	}

	function arquivo_venda($file_name){
		$this->limpar_dados();
		$linhas = array();
		$file_name = file_exists_ci($file_name);

		if($file_name){
			$file = fopen($file_name, "r");
			while(!feof($file)){
				$linhas[] = fgets($file, 4096);
			}
			fclose($file);
		}

		$arr_recebimento = array();

		foreach($linhas as $i => $linha){
			setprogress($i / sizeof($linhas) * 100, "Lendo arquivo de vendas: ".($i + 1)." de ".sizeof($linhas));
			if(strlen(trim($linha)) == 0){
				continue;
			}
			$cupom = substr($linha, 0, 6); // Numero cupom
			$numpedido = substr($linha, 6, 8); // Numero do pedido
			$tipodocto = substr($linha, 14, 1); // Tipo de documento (T (cupom fiscal)/F (pedido))
			$registro = substr($linha, 15, 2); // Tipo de registro
			$codproduto = trim(substr($linha, 17, 20)); // Codigo do produto
			$tipoproduto = substr($linha, 37, 1); // Tipo do produto (F (formula, e os produtos da formula viram em seguida com o valor C, o resto vira em branco))
			$quantidade = substr($linha, 38, 8) / 1000; // Quantidade (*1000)
			$valorunit = substr($linha, 46, 12) / 1000; // Valor unitario (*1000)
			$valortotal = substr($linha, 58, 12) / 100; // Valor total (*100)
			$codvendedor = substr($linha, 70, 4); // Codigo do vendedor
			$codempresa = substr($linha, 74, 3); // Codigo da empresa (cliente) convenio
			$codcliente = substr($linha, 77, 16); // Codigo do cliente
			$tipocliente = substr($linha, 93, 1); // Tipo de cliente (L (loja)/C (convenio))
			$codestabelec = substr($linha, 94, 3); // Codigo do estabelecimento
			$guiche = substr($linha, 97, 3); // Numero do guiche
			$operador = substr($linha, 100, 4); // Numero do operador
			$data = substr($linha, 104, 8); // Data da venda
			$hora = substr($linha, 112, 5); // Hora da venda
			$codfinaliz = substr($linha, 117, 3); // Codigo do finalizador de venda
			$cpfcnpj = substr($linha, 120, 19); // CPF/CNPJ (cheque)
			$banco = substr($linha, 139, 3); // Banco (cheque)
			$agencia = substr($linha, 142, 7); // Agencia (cheque)
			$antcontacorrente = substr($linha, 149, 9); // Antigo conta corrente (cheque)
			$numcheque = substr($linha, 158, 6); // Numero do cheque (cheque)
			$datavencto = substr($linha, 164, 8); // Data de vencimento (cheque)
			$codtitular = substr($linha, 172, 8); // Codigo do titular (cartao RC)
			$sequencia = substr($linha, 182, 1); // Sequencia (cartao RC)
			$via = substr($linha, 183, 1); // Via (cartao RC)
			$numboleta = substr($linha, 184, 8); // Numero da boleta (cartao RC)
			$codcondpagto = substr($linha, 192, 4); // Codigo da condicao de pagamento (cartao RC)
			$condpagto = substr($linha, 196, 10); // Condicao de pagamento (nº parcelas TEF/senha/cond pagto/ticket vasilhame/ticket troca)
			$telefone = substr($linha, 206, 10); // Telefone (cheque)
			$atcontcorrent = substr($linha, 216, 10); // Atual conta conrrente
			$cmc7documento = substr($linha, 226, 70); // CMC-7/Numero do documento
			$rededesttransacao = substr($linha, 296, 4); // Rede destino da transacao (TEF)
			$tipotransacao = substr($linha, 300, 1); // Tipo da transacao (TEF)
			$numcartao = substr($linha, 301, 40); // Numero do cartao (credito rotativo)
			$tipodoctopagtoconta = substr($linha, 341, 3); // Tipo de documento pagamento conta
			$statustransacao = substr($linha, 344, 1); // Status da transacao (TEF) (N (nao efetivada))
			$valortroco = substr($linha, 345, 12) / 100; // Valor do troco (*100)
			$tabprecoutil = substr($linha, 357, 1); // Tabela de preco utilizada (A (atacado)/E (especial)/P (padrao)/R (promocao))
			$pontos = substr($linha, 358, 8) / 10000; // Pontos concedidos no item (*10000)
			$tributacao = substr($linha, 366, 6); // Tributacao
			$doctodig1 = substr($linha, 372, 20); // 1º documento digitado
			$contordemoper = substr($linha, 392, 6); // Contador de order de operacao (COO)
			$numordemitcupom = substr($linha, 398, 6); // Numerio de order de item no cupom
			$doctodig2 = substr($linha, 404, 20); // 2º documento digitado
			$doctodig3 = substr($linha, 424, 20); // 3º documento digitado
			$doctodig4 = substr($linha, 444, 20); // 4º documento digitado
			$numecf = substr($linha, 464, 3); // Numero da ECF
			$supervidor = substr($linha, 467, 4); // Codigo do supervisor
			$dtmovto = substr($linha, 471, 8); // Data do movimento
			$codbarras = substr($linha, 479, 20); // Codigo de barras digitado/lido
			$desconto = substr($linha, 499, 12) / 1000; // Desconto no item (o total ja vem com o desconto abatido) (*1000)
			$abatimento = substr($linha, 511, 12) / 1000; // Abatimento (cupom) (*1000)
			$acrescimo = substr($linha, 523, 12) / 1000; // Acrescimo (cupom) (*1000)
			$tipoparcelamento = substr($linha, 535, 2); // Tipo do parcelemento (TEF)
			$codtabprecoutil = substr($linha, 538, 6); // Codigo da tabela de preco utilizada
			$bincartao = substr($linha, 543, 6); // Bin do cartao de credito/debito (credito rotativo)
			$numparcelas = substr($linha, 549, 2); // Numero de parcelas
			$indregcanc = substr($linha, 551, 1); // Indicacao de registro cancelado
			$codfinalizadorant = substr($linha, 552, 3); // Codigo do finalizador de venda anterior
			$chavecfe = substr($linha, 702, 44); // Chave do CF-e

			$cpfcnpj = trim($cpfcnpj);

			// Verifica se o PLU que puxou e valido
/*			$codproduto = trim($codproduto);
			$codbarras = trim($codbarras);
*/
			// Formata as data de YYYYMMDD para YYYY-MM-DD
			$data = substr($data, 0, 4)."-".substr($data, 4, 2)."-".substr($data, 6, 2);
			$dtmovto = substr($dtmovto, 0, 4)."-".substr($dtmovto, 4, 2)."-".substr($dtmovto, 6, 2);
			$datavencto = substr($datavencto, 0, 4)."-".substr($datavencto, 4, 2)."-".substr($datavencto, 6, 2);

			// Captura tipo e aliquota de ICMS
			$tptribicms = substr($tributacao, 0, 1);
			$aliqicms = trim(substr($tributacao, 1));

			// Verifica a chave CF-e
			$chavecfe = trim($chavecfe);
			if(strlen($chavecfe) > 0 && $chavecfe > 0){
				$contordemoper = substr($chavecfe, 31, 6);
			}

			// Tratamento para balanca
			$aux_codproduto = ltrim($codproduto,"0");
			if(substr($aux_codproduto,0,1) == "2" && strlen($aux_codproduto) == 13){
				$codproduto = substr($aux_codproduto,0,5);
			}

			// Venda de item
			if($registro == "01"){
				if($tipoproduto == "F"){
					continue;
				}
				if((int)$codproduto == 0){
					$i++;
					echo messagebox("error", "", "Não foi possivel fazer a leitura do arquivo, na linha {$i} foi encontrado um produto de codigo 0.");
					die;
				}

				$item = new PdvItem();
				$item->setstatus("A");
				$item->setcodproduto($codproduto);
				$item->setquantidade($quantidade);
				$item->setpreco($valorunit);
				//if($tabprecoutil == "P"){
				$item->setdesconto($abatimento);
				//}
				$item->setacrescimo($acrescimo);
				$item->settotal($valortotal - $abatimento + $acrescimo);
				$item->settptribicms($tptribicms);
				$item->setaliqicms($aliqicms);

				$found = FALSE;
				foreach(array_reverse($this->pdvvenda) as $pdvvenda){
					if($pdvvenda->getdata() == $dtmovto && $pdvvenda->getcupom() == $cupom && $pdvvenda->getcaixa() == $guiche){
						$pdvvenda->pdvitem[] = $item;
						$found = TRUE;
						break;
					}
				}
				if(!$found){
					$pdvvenda = new PdvVenda;
					$pdvvenda->setcupom($cupom);
					$pdvvenda->setcaixa($guiche);
					$pdvvenda->setnumeroecf($numecf);
					$pdvvenda->setdata($dtmovto);
					$pdvvenda->sethora($hora);
					$pdvvenda->setcodcliente($codcliente);
					$pdvvenda->setcodfunc($codvendedor);
					$pdvvenda->setcpfcnpj($cpfcnpj);
					$pdvvenda->setseqecf($contordemoper);
					$pdvvenda->setchavecfe($chavecfe);
					$pdvvenda->setoperador($operador);
					$pdvvenda->setarquivo($file_name);
					$pdvvenda->pdvitem[] = $item;
					$this->pdvvenda[] = $pdvvenda;
				}
				// Abatimento (desconto) sobre o total do cupom
			}elseif($registro == "02"){

				// Finalizador de venda
			}elseif($registro == "03"){
				$finalizador = new PdvFinalizador();
				$finalizador->setstatus($indregcanc == "T" ? "C" : "A");
				$finalizador->setcupom($cupom);
				$finalizador->setcaixa($guiche);
				$finalizador->setcodcliente($codcliente);
				$finalizador->setcpfcliente($cpfcnpj);
				$finalizador->setdata($dtmovto);
				$finalizador->sethora($hora);
				$finalizador->setcodfinaliz($codfinaliz);
				$finalizador->setvalortotal($valortotal - $valortroco);
				$finalizador->setdatavencto($datavencto);
				$finalizador->setnumagenciacheq($agencia);
				$finalizador->setcodbanco($banco);
				$finalizador->setnumcheque($numcheque);
				$finalizador->setcodfunc($codvendedor);
				$finalizador->setbin($bincartao);
				$finalizador->settipotransacao($tipotransacao);
				$finalizador->setrecebepdv(in_array($guiche."|".$cupom."|".$dtmovto, $arr_recebimento));

				$this->pdvfinalizador[] = $finalizador;


				foreach(array_reverse($this->pdvvenda) as $pdvvenda){
					if($pdvvenda->getdata() == $dtmovto && $pdvvenda->getcaixa() == $guiche && $pdvvenda->getcupom() == $cupom){
						$pdvvenda->setstatus("A");
						if(strlen($cpfcnpj) > 0){
							$pdvvenda->setcpfcnpj($cpfcnpj);
						}
						break;
					}
				}

				// Cancelamento de cupom
			}elseif($registro == "04"){
				$found = FALSE;
				foreach(array_reverse($this->pdvvenda) as $pdvvenda){
					if($pdvvenda->getdata() == $dtmovto && $pdvvenda->getcupom() == $cupom && $pdvvenda->getcaixa() == $guiche){
						$pdvvenda->setstatus("C");
						$found = TRUE;
						break;
					}
				}
				if($found){
					foreach($this->pdvfinalizador as $j => $pdvfinalizador){
						if($pdvfinalizador->getdata() == $dtmovto && $pdvfinalizador->getcupom() == $cupom && $pdvfinalizador->getcaixa() == $guiche){
							unset($this->pdvfinalizador[$j]);
						}
					}
				}else{
					$pdvvenda = new PdvVenda;
					$pdvvenda->setstatus("C");
					$pdvvenda->setcupom($cupom);
					$pdvvenda->setcaixa($guiche);
					$pdvvenda->setdata($dtmovto);
					$pdvvenda->setnumeroecf($numecf);
					$pdvvenda->setchavecfe($chavecfe);
					$pdvvenda->setarquivo($file_name);
					//	$pdvvenda->setseqecf($contordemoper);
					$this->pdvvenda[] = $pdvvenda;
				}

				foreach(array_reverse($this->pdvfinalizador) as $pdvfinalizador){
					if($pdvfinalizador->getdata() == $dtmovto && $pdvfinalizador->getcupom() == $cupom && $pdvfinalizador->getcaixa() == $guiche){
						$pdvfinalizador->setstatus("C");
						break;
					}
				}

				// Acrescimo sobre total do cupom
			}elseif($registro == "05"){

				// Contra-vale emitido
			}elseif($registro == "06"){
				//$pdvfinalizador = end($this->pdvfinalizador);
				//$pdvfinalizador->setvalortotal($pdvfinalizador->getvalortotal() - $valortotal);

				// Cupom vasilhame
			}elseif($registro == "07"){

				// Troca
			}elseif($registro == "08"){

				// Correcao de finalizador
			}elseif($registro == "09"){

				// Cancelamento de item
			}elseif($registro == "10"){
				$f = FALSE;
				foreach(array_reverse($this->pdvvenda) as $i => $pdvvenda){
					if($pdvvenda->getdata() == $dtmovto && $pdvvenda->getcupom() == $cupom && $pdvvenda->getcaixa() == $guiche){
						$f = TRUE;
						break;
					}
				}
				if(!$f){
					$pdvvenda = new PdvVenda;
					$pdvvenda->setcupom($cupom);
					$pdvvenda->setcaixa($guiche);
					$pdvvenda->setnumeroecf($numecf);
					$pdvvenda->setdata($dtmovto);
					$pdvvenda->sethora($hora);
					$pdvvenda->setcodcliente($codcliente);
					$pdvvenda->setcodfunc($codvendedor);
					$pdvvenda->setcpfcnpj($cpfcnpj);
					$pdvvenda->setseqecf($contordemoper);
					$pdvvenda->setchavecfe($chavecfe);
					$pdvvenda->setstatus("C");
					$pdvvenda->setarquivo($file_name);
					$this->pdvvenda[] = $pdvvenda;
				}
				$pdvitem = new PdvItem;
				$pdvitem->setcodproduto($codproduto);
				$pdvitem->setstatus("C");
				$pdvitem->setaliqicms($aliqicms);
				$pdvitem->settptribicms($tptribicms);
				$pdvitem->setquantidade($quantidade);
				$pdvitem->setpreco($valorunit);
				$pdvitem->setdesconto($abatimento);
				$pdvitem->setacrescimo($acrescimo);
				$pdvitem->settotal($valortotal - $abatimento + $acrescimo);
				$pdvitem->settptribicms($tptribicms);
				$pdvitem->setaliqicms($aliqicms);
				$pdvvenda->pdvitem[] = $pdvitem;

				// Cancelamento de venda
			}elseif($registro == "11"){

				// Troco de finalizador
			}elseif($registro == "12"){

				// Recebimentos
			}elseif($registro == "90"){
				$recebepdv = objectbytable("recebepdv", null, $this->con);
				$recebepdv->setcodestabelec($codestabelec);
				$recebepdv->setcupom($cupom);
				$recebepdv->setdtmovto($dtmovto);
				$recebepdv->settotalliquido($valortotal);
				$recebepdv->setcodfinaliz($codfinaliz);
				$recebepdv->setcaixa($guiche);
				$recebepdv->settiporecebimento("C");
				$recebepdv->settipoparceiro("C");
				$recebepdv->setcodparceiro($codcliente);

				$chave = $guiche."|".$cupom."|".$dtmovto;

				$arr_recebimento[] = $chave;
				$this->pdvrecebepdv[$chave] = $recebepdv;
			}
		}
	}

	function getpdvvenda(){
		return $this->pdvvenda;
	}

	function getpdvfinalizador(){
		return $this->pdvfinalizador;
	}

	function getpdvrecebepdv(){
		return $this->pdvrecebepdv;
	}

	private function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
	}

	private function valor_numerico($numero, $decimais, $tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero, $decimais, "", "");
		$numero = substr($numero, 0, $tamanho);
		$numero = str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
		return $numero;
	}

	private function texto($value, $tamanho, $complemento = " ", $esqdir = "D"){
		$value = substr($value, 0, $tamanho);
		$value = str_pad($value, $tamanho, $complemento, $esqdir == "E" ? STR_PAD_LEFT : STR_PAD_RIGHT);
		return $value;
	}

}
