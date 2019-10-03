<?php

require_once("../def/function.php");

class InterligaBalanca{

	private $con; // Conexao com o banco de dados
	private $balanca; // Objeto da balanca que sera gerado os arquivos
	private $codestabelec; // Codigo do estabelecimento para gerar os arquivos
	private $path; // Diretorio onde a classe vai trabalhar
	private $tipopreco; // Tipo de preco (A = atacado; V = Varejo)
	private $tiposetor; // Tipo de setor (0 = Geral; 1 = Departamento; 2 = Grupo)
	private $tipodescricao; // Tipo de descricao do produto (C = Completa; R = Reduzida)
	private $coddepto; // Departamento

	function __construct(){
		$this->con = NULL;
		$this->settiposetor(0);
		$this->settipodescricao("R");
	}

	private function connect(){
		if($this->con == NULL){
			$this->con = new Connection();
		}
	}

	private function file_create($filename, $arr_line, $return = false){
		$arr_line[] = "";
		$filename = $this->path.$filename;
		if($return){
			return array(
				"filename" => $filename,
				"content" => implode("\r\n", $arr_line)
			);
		}else{
			if(param("SISTEMA", "TIPOSERVIDOR", $this->con) == "0"){
				$file = fopen($filename, "w+");
				fwrite($file, implode("\r\n", $arr_line));
				fclose($file);
			}else{
				echo write_file($filename, $arr_line);
			}
			@chmod($filename, 0777);
		}
	}

	function exportproduto($return = false){
		$arr_file = array();
		switch($this->balanca->getcodbalanca()){
			case "1": // Toledo
				$arr_file = array_merge($arr_file, $this->toledo_exportproduto($return));
				$arr_file = array_merge($arr_file, $this->toledo_exportnutricional($return));
				$arr_file = array_merge($arr_file, $this->toledo_exportreceita($return));
				break;
			case "2": // Filizola
				$arr_file = array_merge($arr_file, $this->filizola_exportproduto($return));
				$arr_file = array_merge($arr_file, $this->filizola_exportnutricional($return));
				$arr_file = array_merge($arr_file, $this->filizola_exportreceita($return));
                                if($this->balanca->getfornec() == "S"){
                                    $arr_file = array_merge($arr_file, $this->filizola_exportfornecedor($return));
				}
                                if($this->balanca->getfigura() == "S"){
                                    $arr_file = array_merge($arr_file, $this->filizola_exportfiguras($return));
				}
				break;
		}
		return $arr_file;
	}

	private function filizola_exportnutricional($return = false){
		setprogress(0, "Buscando informacoes nutricionais");
		$this->connect();
		$linhas = array();
		$query = "SELECT ".$this->query_codproduto().", nutricional.* ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN nutricional ON (produto.codnutricional = nutricional.codnutricional) ";
		$query .= "ORDER BY produto.codproduto ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$progress_total = sizeof($arr);
		foreach($arr as $i => $row){
			/*
			  ATENCAO:
			  Os campos de porcentagem sao calculados automaticamente pelo Smart (programa da gerenciador das balancas)
			 */
			setprogress((($i + 1) / $progress_total * 100), "Exportanto informacoes nutricionais: ".($i + 1)." de ".$progress_total);
			if($this->balanca->getpluean() == "4"){
				$row["codproduto"] = substr($row["codproduto"], 0, -1);
			}
			$linha = str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
			$linha .= str_pad(substr(removespecial($row["descricaoporcao"]), 0, 35), 35, " ", STR_PAD_RIGHT); // Descricao da porcao
			$linha .= str_pad(ceil($row["qtdecal"]), 5, "0", STR_PAD_LEFT); // Valor energetico do produto
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem do valor energetico
			$linha .= str_pad(number_format($row["qtdecarbo"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de carboidratos
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem de carboidratos
			$linha .= str_pad(number_format($row["qtdeprot"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de proteinas
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem de proteinas
			$linha .= str_pad(number_format($row["qtdegord"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de gorduras totais
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem de gorduras totais
			$linha .= str_pad(number_format($row["qtdegordsat"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de gorduras saturadas
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem de gorduras saturadas
			$linha .= str_pad(number_format($row["qtdegordtrans"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de gorduras trans
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem de gorduras trans
			$linha .= str_pad(number_format($row["qtdefibra"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de fibras
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem de fibras
			$linha .= str_pad("", 5, "*", STR_PAD_LEFT); // Quantidade de calcio
			$linha .= str_pad("", 4, "*", STR_PAD_LEFT); // Porcentagem de calcio
			$linha .= str_pad("", 5, "*", STR_PAD_LEFT); // Quantidade de ferro
			$linha .= str_pad("", 4, "*", STR_PAD_LEFT); // Porcentagem de ferro
			$linha .= str_pad(number_format($row["qtdesodio"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de sodio
			$linha .= str_pad("", 4, "0", STR_PAD_LEFT); // Porcentagem de sodio
			$linhas[] = $linha;
		}
		return array($this->file_create("NUTRI.txt", $linhas, $return));
	}

	private function filizola_exportproduto($return = false){
		setprogress(0, "Buscando produtos");
		$this->connect();
		$linhas_produto = array(); // Linhas do arquivo de produto
		$linhas_setor = array(); // Linhas do arquivo de setores
		$where = array();
		// Procura os produtos da loja

		if(strlen($this->coddepto) > 0)
			$where[] = "produto.coddepto = ".$this->coddepto;
		if(param("ESTOQUE", "ENVIAFORALINHAPDV", $this->con) === "N"){
			$where[] = " produto.foralinha = 'N' ";
		}
		$where[] = "produtoestab.codestabelec = ".$this->codestabelec." ";
		$where[] = "produtoestab.disponivel = 'S' ";
		$where[] = "produto.pesado = 'S' ";

		$query = "SELECT ".$this->query_codproduto().", produto.descricao, produto.descricaofiscal, ".sql_tipopreco($this->tipopreco).", produto.pesounid, ";
		$query .= "	(CASE WHEN produtoestab.diasvalidade > 0 THEN produtoestab.diasvalidade ELSE produto.diasvalidade END) AS diasvalidade, ";
		$query .= "	departamento.nome AS departamento, grupoprod.descricao AS grupoprod, produto.teclarapida, produto.codreceita ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "INNER JOIN grupoprod ON (produto.codgrupo = grupoprod.codgrupo) ";
		$query .= "INNER JOIN departamento ON (produto.coddepto = departamento.coddepto) ";
		$query .= "WHERE ".implode(" AND ", $where);

		$query .= " ORDER BY produtoestab.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		if(is_array($arr)){
			$progress_total = sizeof($arr);
			// Percorre os produtos
			foreach($arr as $i => $row){
				if($row["preco"] <= 0){
					continue;
				}
				if($this->balanca->getpluean() == "4"){
					$row["codproduto"] = substr($row["codproduto"], 0, -1);
				}

				setprogress((($i + 1) / $progress_total * 100), "Exportanto produtos: ".($i + 1)." de ".$progress_total);
				$linha_produto = str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
				if(strlen($row["codreceita"]) == 0){
					$linha_produto .= str_pad(strtoupper($row["pesounid"]), 1, " ", STR_PAD_LEFT); // Se e vendido em unidade ou por peso
				}else{
					$linha_produto .= str_pad(strtolower($row["pesounid"]), 1, " ", STR_PAD_LEFT); // Se e vendido em unidade ou por peso
				}
				$linha_produto .= str_pad(strtoupper(substr(removespecial($this->tipodescricao == "C" ? $row["descricaofiscal"] : $row["descricao"]), 0, 22)), 22, " ", STR_PAD_RIGHT); // Descricao do produto (rezumida)
				$linha_produto .= str_pad(($row["preco"] * 100), 7, "0", STR_PAD_LEFT); // Preco
				$validade = (strlen($row["validade"]) > 0 && $row["validade"] > 0) ? $row["validade"] : $row["diasvalidade"];
				$linha_produto .= str_pad($validade, 3, "0", STR_PAD_LEFT); // Dias de validade
				//$linha_produto .= str_pad($row["diasvalidade"],3,"0",STR_PAD_LEFT); // Dias de validade
				$linhas_produto[] = $linha_produto;

				switch($this->tiposetor){
					case "0": $setor = "GERAL";
						break;
					case "1": $setor = $row["departamento"];
						break;
					case "2": $setor = $row["grupoprod"];
						break;
				}

				$linha_setor = str_pad(strtoupper(substr($setor, 0, 12)), 12, " ", STR_PAD_RIGHT); // Desccricao do setor
				$linha_setor .= str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
				$linha_setor .= str_pad("", 4, "0", STR_PAD_LEFT); // Espaco reservado
				$linha_setor .= str_pad(strtoupper(substr($row["teclarapida"], 0, 3)), 3, "0", STR_PAD_RIGHT); // Tecla rapida
				$linhas_setor[] = $linha_setor;
			}
		}
		return array(
			$this->file_create("CADTXT.txt", $linhas_produto, $return),
			$this->file_create("SETORTXT.txt", $linhas_setor, $return)
		);
	}

	private function filizola_exportreceita($return = false){
		setprogress(0, "Buscando receitas");
		$this->connect();
		$linhas = array();
		$query = "SELECT ".$this->query_codproduto().", receita.* ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN receita ON (produto.codreceita = receita.codreceita) ";
		$query .= "ORDER BY produto.codproduto ";
		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$progress_total = sizeof($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $progress_total * 100), "Exportanto receitas: ".($i + 1)." de ".$progress_total);

			if($this->balanca->getpluean() == "4"){
				$row["codproduto"] = substr($row["codproduto"], 0, -1);
			}

			$linha = str_repeat(" ", 12); // Espaco reservado
			$linha .= str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
			$linha .= str_pad($row["codreceita"], 6, "0", STR_PAD_LEFT); // Codigo da receita
			$linha .= $row["descricao"]."\r\n"; // Descricao da receita
			if(strlen($row["componentes"]) > 0){
				$linha .= "Ingredientes: ".str_replace("\n", "\r\n", $row["componentes"])."\r\n";
			}
			if(strlen($row["modopreparo"]) > 0){
				$linha .= "Modo de Preparo: ".str_replace("\n", "\r\n", $row["modopreparo"])."\r\n";
			}
			$linha .= "@";
			$linhas[] = $linha;
		}
		return array($this->file_create("RECEITA.txt", $linhas, $return));
	}

	private function filizola_exportfornecedor($return = false){
		setprogress(0, "Buscando Fornecedores");
		$this->connect();
		$linhas = array();
		$query = "SELECT ".$this->query_codproduto().", fornecedor.codfornec, fornecedor.nome, fornecedor.cpfcnpj ";
		$query .= "FROM produto ";
		$query .= "INNER JOIN fornecedor ON (fornecedor.codfornec = (select notafiscal.codparceiro from itnotafiscal INNER JOIN notafiscal on (itnotafiscal.idnotafiscal = notafiscal.idnotafiscal) WHERE itnotafiscal.codproduto = produto.codproduto AND notafiscal.operacao = 'CP' ORDER BY notafiscal.dtentrega DESC limit 1)) ";
		$query .= "WHERE produto.pesado = 'S' ";
		$query .= "ORDER BY produto.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$progress_total = sizeof($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $progress_total * 100), "Exportanto fornecedores: ".($i + 1)." de ".$progress_total);

			if($this->balanca->getpluean() == "4"){
				$row["codproduto"] = substr($row["codproduto"], 0, -1);
			}

			$linha = str_repeat(" ", 12); // Espaco reservado
			$linha .= str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
			$linha .= str_pad($row["codfornec"], 6, "0", STR_PAD_LEFT); // Codigo do fornecedor
			$linha .= "{$row["nome"]} - {$row["cpfcnpj"]} "."@"; // Descricao
			$linhas[] = $linha;
		}
		return array($this->file_create("RECFOR.txt", $linhas, $return));
	}

	private function filizola_exportfiguras($return = false){
		setprogress(0, "Buscando Fornecedores");
		$this->connect();
		$linhas = array();
		$query = "SELECT ".$this->query_codproduto().", produto.descricaofiscal ";
		$query .= "FROM produto ";
		$query .= "WHERE produto.pesado = 'S' ";
		$query .= "ORDER BY produto.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$progress_total = sizeof($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $progress_total * 100), "Exportanto fornecedores: ".($i + 1)." de ".$progress_total);

			if($this->balanca->getpluean() == "4"){
				$row["codproduto"] = substr($row["codproduto"], 0, -1);
			}

			$linha = str_repeat(" ", 12); // Espaco reservado
			$linha .= str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
			$linha .= str_pad(substr($row["descricaofiscal"],0,12), 12, "0", STR_PAD_LEFT); // Figura associada
			$linha .= "@"; // finalizador
			$linhas[] = $linha;
		}
		return array($this->file_create("EXPORTFIGURAS.txt", $linhas, $return));
	}

	private function toledo_exportnutricional($return = false){
		setprogress(0, "Buscando informacoes nutricionais");
		$this->connect();
		$linhas = array();
		$res = $this->con->query("SELECT nutricional.* FROM nutricional");
		$arr = $res->fetchAll(2);
		$progress_total = sizeof($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $progress_total * 100), "Exportanto informacoes nutricionais: ".($i + 1)." de ".$progress_total);
			$linha = "N"; // Indicador de nova informacao nutricional (sempre 'N')
			$linha .= str_pad($row["codnutricional"], 6, "0", STR_PAD_LEFT); // Codigo da informacao nutricional
			$linha .= str_pad("0", 1, " ", STR_PAD_RIGHT); // Reservado
			$linha .= str_pad(ceil($row["qtdeporcao"]), 3, "0", STR_PAD_LEFT); // Quantidade da porcao
			$linha .= str_pad($row["unidporcao"], 1, "0", STR_PAD_LEFT); // Tipo da porcao
			$linha .= str_pad($row["intmedcas"], 2, "0", STR_PAD_LEFT); // Parte inteira da medida caseira
			$linha .= str_pad($row["decmedcas"], 1, "0", STR_PAD_LEFT); // Parte decimal da medida caseira
			$linha .= str_pad($row["medcaseira"], 2, "0", STR_PAD_LEFT); // Medida caseira utilizada
			$linha .= str_pad(ceil($row["qtdecal"]), 4, "0", STR_PAD_LEFT); // Valor energetico
			$linha .= str_pad(number_format($row["qtdecarbo"], 1, "", ""), 4, "0", STR_PAD_LEFT); // Quantidade de carboidratos
			$linha .= str_pad(number_format($row["qtdeprot"], 1, "", ""), 3, "0", STR_PAD_LEFT); // Quantidade de proteinas
			$linha .= str_pad(number_format($row["qtdegord"], 1, "", ""), 3, "0", STR_PAD_LEFT); // Quantidade de gorduras totais
			$linha .= str_pad(number_format($row["qtdegordsat"], 1, "", ""), 3, "0", STR_PAD_LEFT); // Quantidade de gorduras saturadas
			$linha .= str_pad(number_format($row["qtdegordtrans"], 1, "", ""), 3, "0", STR_PAD_LEFT); // Quantidade de gorduras trans
			$linha .= str_pad(number_format($row["qtdefibra"], 1, "", ""), 3, "0", STR_PAD_LEFT); // Quantidade de fibras
			$linha .= str_pad(number_format($row["qtdesodio"], 1, "", ""), 5, "0", STR_PAD_LEFT); // Quantidade de sodio
			$linhas[] = $linha;
		}
		return array($this->file_create("INFNUTRI.txt", $linhas, $return));
	}

	private function toledo_exportproduto($return = false){
		setprogress(0, "Buscando produtos");
		$this->connect();
		$linhas_produto = array(); // linhas do arquivo de produto
		$where = array();

		// Procura os produtos da loja
		if(strlen($this->coddepto) > 0){
			$where[] = " produto.coddepto = ".$this->coddepto;
		}
		if(param("ESTOQUE", "ENVIAFORALINHAPDV", $this->con) === "N"){
			$where[] = "produto.foralinha = 'N'";
		}
		$where[] = "produtoestab.disponivel = 'S'";
		$where[] = "produto.pesado = 'S'";
		$where[] = "produtoestab.codestabelec = ".$this->codestabelec;

		$query = "SELECT ".$this->query_codproduto().", produto.descricao, produto.descricaofiscal, ".sql_tipopreco($this->tipopreco).", ";
		$query .= "	produto.pesounid, (CASE WHEN produtoestab.diasvalidade > 0 THEN produtoestab.diasvalidade ELSE produto.diasvalidade END) AS diasvalidade, produto.codnutricional, ";
		$query .= "	produto.codreceita, produto.coddepto, produto.codgrupo, produto.teclarapida ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "WHERE ".implode(" AND ", $where);

		$query .= " ORDER BY produtoestab.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		$progress_total = sizeof($arr);
		// Percorre os produtos
		foreach($arr as $i => $row){
			setprogress(($i / $progress_total * 100), "Exportanto produtos: ".($i + 1)." de ".$progress_total);
			$linha_p = ""; // Linha do arquivo de produto
			switch($this->tiposetor){
				case "0":
					$setor = "64";
					break;
				case "1":
					$setor = $row["coddepto"];
					break;
				case "2":
					$setor = $row["codgrupo"];
					break;
			}

			$descricao = removespecial($this->tipodescricao == "C" ? $row["descricaofiscal"] : $row["descricao"]);

			if(true){
				$linha_p .= str_pad(substr($setor, 0, 2), 2, "0", STR_PAD_LEFT); // Codigo do setor
				$linha_p .= str_pad(($row["pesounid"] == "U" ? "1" : "0"), 1, " ", STR_PAD_LEFT); // Tipo de produto (0 = venda por peso; 1 = venda por unidade; 2 = EAN 13)
				$linha_p .= str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
				$linha_p .= str_pad($row["preco"] * 100, 6, "0", STR_PAD_LEFT); // Preco
				$validade = (strlen($row["validade"]) > 0 && $row["validade"] > 0) ? $row["validade"] : $row["diasvalidade"];
				$linha_p .= str_pad($validade, 3, "0", STR_PAD_LEFT); // Dias de validade
				//$linha_p .= str_pad($row["diasvalidade"],3,"0",STR_PAD_LEFT); // Dias de validade
				$linha_p .= str_pad(strtoupper(substr($descricao, 0, 25)), 25, " ", STR_PAD_RIGHT); // Descricao parte 1
				$linha_p .= str_pad(strtoupper(substr($descricao, 25, 25)), 25, " ", STR_PAD_RIGHT); // Descricao parte 2
				$linha_p .= str_pad($row["codreceita"], 6, "0", STR_PAD_LEFT); // Informacoes extras (codigo da receita)
				$linha_p .= str_pad("000", 3, "0", STR_PAD_LEFT); // Codigo da Imagem
				$linha_p .= str_pad($row["codnutricional"], 4, "0", STR_PAD_LEFT); // Codigo nutricional
				$linha_p .= str_pad("1", 1, " ", STR_PAD_LEFT); // Imprime data de validade
				$linha_p .= str_pad("1", 1, " ", STR_PAD_LEFT); // Imprime data de embalagem
				$linha_p .= str_pad("0000", 4, " ", STR_PAD_LEFT); // Codigo do fornecedor
				$linha_p .= str_pad("000000000000", 12, " ", STR_PAD_LEFT); // Lote
				$linha_p .= str_pad("0", 11, "0", STR_PAD_LEFT); // Codigo especial
				$linha_p .= str_pad("0", 1, " ", STR_PAD_LEFT); // Versao do preco / indicador de uso
				$linha_p .= str_pad("0", 1, " ", STR_PAD_LEFT); // [Reservado]
			}else{
				$linha_p .= str_pad(substr($setor, 0, 2), 2, "0", STR_PAD_LEFT); // Codigo do setor
				$linha_p .= str_pad(($row["pesounid"] == "U" ? "1" : "0"), 1, " ", STR_PAD_LEFT); // Tipo de produto (0 = venda por peso; 1 = venda por unidade; 2 = EAN 13)
				$linha_p .= str_pad($row["codproduto"], 6, "0", STR_PAD_LEFT); // Codigo do produto
				$linha_p .= str_pad($row["preco"] * 100, 6, "0", STR_PAD_LEFT); // Preco
				$validade = (strlen($row["validade"]) > 0 && $row["validade"] > 0) ? $row["validade"] : $row["diasvalidade"];
				$linha_p .= str_pad($validade, 3, "0", STR_PAD_LEFT); // Dias de validade

				$linha_p .= str_pad(strtoupper(substr($descricao, 0, 25)), 25, " ", STR_PAD_RIGHT); // Descricao parte 1
				$linha_p .= str_pad(strtoupper(substr($descricao, 25, 25)), 25, " ", STR_PAD_RIGHT); // Descricao parte 2

				$linha_p .= str_pad($row["codreceita"], 6, "0", STR_PAD_LEFT); // Informacoes extras (codigo da receita)
				$linha_p .= str_pad("000", 4, "0", STR_PAD_LEFT); // Codigo da Imagem
				$linha_p .= str_pad($row["codnutricional"], 6, "0", STR_PAD_LEFT); // Codigo nutricional

				$linha_p .= str_pad("1", 1, " ", STR_PAD_LEFT); // Imprime data de validade
				$linha_p .= str_pad("1", 1, " ", STR_PAD_LEFT); // Imprime data de embalagem
				$linha_p .= str_pad("0000", 4, " ", STR_PAD_LEFT); // Codigo do fornecedor
				$linha_p .= str_pad("000000000000", 12, " ", STR_PAD_LEFT); // Lote
				$linha_p .= str_pad("0", 11, "0", STR_PAD_LEFT); // Codigo especial
				$linha_p .= str_pad("0", 1, " ", STR_PAD_LEFT); // Versao do preco / indicador de uso
				$linha_p .= str_pad("0", 4, " ", STR_PAD_LEFT); // Codigo do som
				$linha_p .= str_pad($row["tara"], 4, " ", STR_PAD_LEFT); // Codigo da tara
				$linha_p .= str_pad("0", 4, " ", STR_PAD_LEFT); // Codigo do fracianador
				$linha_p .= str_pad("0", 8, " ", STR_PAD_LEFT); // Campo extra 1 e 2
				$linha_p .= str_pad("0", 4, " ", STR_PAD_LEFT); // Codigo da conservacao
				$linha_p .= str_pad("0", 12, " ", STR_PAD_LEFT); // EAN 12
			}
			$linhas_produto[] = $linha_p;
		}
		// Cria arquivo de produtos
		return array($this->file_create("ITENSMGV.txt", $linhas_produto, $return));
	}

	function toledo_exportreceita($return = false){
		setprogress(0, "Buscando informacoes nutricionais");
		$this->connect();
		$arr_linha = array();
		$res = $this->con->query("SELECT * FROM receita");
		$arr = $res->fetchAll(2);
		$progress_total = sizeof($arr);
		foreach($arr as $i => $row){
			setprogress((($i + 1) / $progress_total * 100), "Exportanto informacoes nutricionais: ".($i + 1)." de ".$progress_total);
			$linha = str_pad($row["codreceita"], 6, "0", STR_PAD_LEFT);
			$linha .= str_pad(substr($row["descricao"], 0, 100), 100, " ", STR_PAD_RIGHT);
			$componentes = str_replace("\n", " ", $row["componentes"]);
			$modopreparo = str_replace("\n", " ", (strlen($row["modopreparo"]) > 0 ? "Modo de Preparo: " : "").$row["modopreparo"]);
			for($j = 0; $j < 15; $j++){
				$componentes_p = substr($componentes, (56 * $j), 56);
				$modopreparo_p = substr($modopreparo, (56 * $j), 56);
				if(strlen($componentes_p) > 0){
					$linha .= str_pad($componentes_p, 56, " ", STR_PAD_RIGHT);
				}elseif(strlen($modopreparo_p) > 0){
					$linha .= str_pad($modopreparo_p, 56, " ", STR_PAD_RIGHT);
				}else{
					$linha .= str_repeat(" ", 56);
				}
			}
			$arr_linha[] = $linha;
		}
		// Cria arquivo de receitas
		return array($this->file_create("TXINFO.txt", $arr_linha, $return));
	}

	function query_codproduto(){
		if($this->balanca->getpluean() == "P"){
			return "produto.codproduto";
		}else{
			return "(SELECT CAST(codean AS bigint) FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) AS codproduto";
		}
	}

	function setcodestabelec($value){
		$this->connect();
		$this->codestabelec = $value;
		$estabelecimento = objectbytable("estabelecimento", $this->codestabelec, $this->con);
		$this->path = $estabelecimento->getpathedi();
	}

	function setconnection($connection){
		$this->con = $connection;
	}

	function setbalanca($value){
		if(is_object($value)){
			$this->balanca = $value;
		}else{
			$this->balanca = objectbytable("balanca", $value, $this->con);
		}
		return TRUE;
	}

	function settipodescricao($tipodescricao){
		if($tipodescricao != "C"){
			$tipodescricao = "R";
		}
		$this->tipodescricao = $tipodescricao;
	}

	function settipopreco($tipopreco){
		if($tipopreco != "A"){
			$tipopreco = "V";
		}
		$this->tipopreco = $tipopreco;
	}

	function settiposetor($tiposetor){
		$this->tiposetor = $tiposetor;
	}

	function setcoddepto($coddepto){
		$this->coddepto = $coddepto;
	}

}