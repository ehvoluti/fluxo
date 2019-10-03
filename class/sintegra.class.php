<?php

class Sintegra{

	private $con;
	private $estabelecimento;
	private $linhas;
	private $bloco10; // Blocos para escrever as linhas no arquivo do registro 10
	private $bloco11; // Blocos para escrever as linhas no arquivo do registro 11
	private $bloco50; // Blocos para escrever as linhas no arquivo do registro 50
	private $bloco53; // Blocos para escrever as linhas no arquivo do registro 53
	private $bloco54; // Blocos para escrever as linhas no arquivo do registro 54
	private $bloco60M; // Blocos para escrever as linhas no arquivo do registro 60M
	private $bloco60A; // Blocos para escrever as linhas no arquivo do registro 60A
	private $bloco60D; // Blocos para escrever as linhas no arquivo do registro 60D
	private $bloco60I; // Blocos para escrever as linhas no arquivo do registro 60I
	private $bloco60R; // Blocos para escrever as linhas no arquivo do registro 60R
	private $bloco74; // Blocos para escrever as linhas no arquivo do registro 74
	private $bloco75; // Blocos para escrever as linhas no arquivo do registro 75
	private $bloco90; // Blocos para escrever as linhas no arquivo do registro 90
	protected $n_registro; // Totalizador de linhas por registro (array[registro] => n_registros)
	protected $arquivo; // Arquivo aberto para criacao dos registros
	protected $arquivo_nome; // Nome do arquivo aberto para criacao dos registros
	private $finalidade;
	private $data_inicial;  //formato: Y-m-d
	private $data_final; //formato: Y-m-d
	private $dtinventario;
	private $gerar_notasfiscais; // Verifica se e para gerar os registros de nota fiscal
	private $gerar_notasfiscais_saida;
	private $gerar_notasfiscais_entrada;
	private $gerar_ecf; // Verifica se e para gerar os registros de ecf
	private $gerar_inventario; // Verifica se e para gerar os registros de inventario
	private $gerar_subtipo_60D; // Verifica se e para gerar o registro 60D
	private $gerar_subtipo_60I; // Verifica se e para gerar o registro 60I
	private $gerar_subtipo_60R; // Verifica se e para gerar o registro 60R
	private $filtro;  //contera o filtro das informações
	private $arr_notafiscal_60M; // Array para colocar os itens do registro 60M
	private $arr_notafiscal_60A; // Array para colocar os itens do registro 60A
	private $arr_notafiscal_60D; // Array para colocar os itens do registro 60D
	private $arr_notafiscal_60R; // Array para colocar os itens do registro 60D
	private $arr_notafiscal_60I; // Array para colocar os itens do registro 60I
	private $arr_notafiscal_61; // Array para colocar os itens do registro 61
	private $arr_notafiscal_61R; // Array para colocar os itens do registro 61R
	private $arr_notafiscal_70; // Array para colocar os itens do registro 70
	private $arr_notafiscal_75; // Array para colocar os itens do registro 75
	private $total_rg_10; // Numero total dos itens do registro 10
	private $total_rg_11; // Numero total dos itens do registro 11
	private $total_rg_50; // Numero total dos itens do registro 50
	private $total_rg_53; // Numero total dos itens do registro 53
	private $total_rg_54; // Numero total dos itens do registro 54
	private $total_rg_60; // Numero total dos itens do registro 60
	private $total_rg_70; // Numero total dos itens do registro 70
	private $total_rg_71; // Numero total dos itens do registro 70
	private $total_rg_74; // Numero total dos itens do registro 74
	private $total_rg_75; // Numero total dos itens do registro 75
	private $total_rg_90; // Numero total dos itens do registro 90
	private $arr_produto_75;
	protected $arr_filtro = array(); // Array com os filtro tecnicos que serao aplicados na busca da notas fiscais (nome da coluna como indice)

	function __construct($con){
		unset($_SESSION["sintegra"]);
		$this->con = $con;
		$this->estabelecimento = array();
		$this->data_inicial;
		$this->data_final;
		$this->linhas = array();
		$this->bloco10 = array();
		$this->bloco11 = array();
		$this->bloco50 = array();
		$this->bloco53 = array();
		$this->bloco54 = array();
		$this->bloco60M = array();
		$this->bloco60A = array();
		$this->bloco60D = array();
		$this->bloco60I = array();
		$this->bloco60R = array();
		$this->bloco74 = array();
		$this->bloco75 = array();
		$this->bloco90 = array();
		$this->total_rg_10 = 1;
		$this->total_rg_11 = 1;
		$this->total_rg_50 = 0;
		$this->total_rg_53 = 0;
		$this->total_rg_54 = 0;
		$this->total_rg_60 = 0;
		$this->total_rg_70 = 0;
		$this->total_rg_74 = 0;
		$this->total_rg_75 = 0;
		$this->total_rg_90 = 1;
		$this->gerar_subtipo_60D = FALSE;
		$this->gerar_subtipo_60I = FALSE;
		$this->gerar_subtipo_60R = FALSE;
		$this->gerar_inventario = FALSE;
		$this->opcao60D = FALSE;
		$this->opcao60I = FALSE;
		$this->opcao60R = FALSE;
		$this->gerar_ecf = FALSE;
		$this->gerar_notasfiscais = FALSE;
		$this->gerar_notasfiscais_saida = FALSE;
		$this->gerar_notasfiscais_entrada = FALSE;
		$this->finalidade = 1;
		$this->mesgeracao;
		$this->anogeracao;
		$this->dtinventario;
	}

	function setestabelecimento($estabelecimento){
		$this->estabelecimento = objectbytable("estabelecimento", $estabelecimento, $this->con);
	}

	function setmesgeracao($mesgeracao){
		if(strlen($mesgeracao) == 1){
			$this->mesgeracao = "0".$mesgeracao;
		}else{
			$this->mesgeracao = $mesgeracao;
		}
	}

	function setanogeracao($anogeracao){
		$this->anogeracao = $anogeracao;
	}

	function setdtinventario($dtinventario){
		$this->dtinventario = $dtinventario;
	}

	function setfinalidade($finalidade){
		$this->finalidade = $finalidade;
	}

	function setdatainicial($data_inicial){
		$this->data_inicial = value_date($data_inicial);
	}

	function setdatafinal($data_final){
		$this->data_final = value_date($data_final);
	}

	function setgerar_notasfiscais($gerar_notasfiscais){
		$this->gerar_notasfiscais = $gerar_notasfiscais;
	}

	function setgerar_notasfiscais_entrada($gerar_notasfiscais){
		$this->gerar_notasfiscais_entrada = $gerar_notasfiscais;
	}

	function setgerar_notasfiscais_saida($gerar_notasfiscais){
		$this->gerar_notasfiscais_saida = $gerar_notasfiscais;
	}

	function setregistro60d($registro60d){
		$this->opcao60D = $registro60d;
	}

	function setregistro60i($registro60i){
		$this->opcao60I = $registro60i;
	}

	function setregistro60r($registro60r){
		$this->opcao60R = $registro60r;
	}

	function setgerar_ecf($gerar_ecf){
		$this->gerar_ecf = $gerar_ecf;
	}

	function setgerar_inventario($gerar_inventario){
		$this->gerar_inventario = $gerar_inventario;
	}

	// Escreve o bloco no arquivo
	protected function escrever_bloco($bloco){
		if(is_array($bloco)){
			foreach($bloco as $registro){
				$this->escrever_registro($registro);
			}
		}else{
			$this->escrever_linha($bloco);
		}
	}

	// Escreve o registro no arquivo
	protected function escrever_registro($registro){
		if(sizeof($registro) > 0){
			fwrite($this->arquivo, $registro."\r\n");
		}
	}

	// Escreve apenas a linha enviada no arquivo
	protected function escrever_linha($linha){
		if(strlen($linha) > 0){
			fwrite($this->arquivo, $linha."\r\n");
		}
	}

	// Adiciona um filtro na busca de notas fiscais
	public function filtro($coluna, $valor){
		if(strlen(trim($valor)) > 0){
			$this->arr_filtro[$coluna] = $valor;
		}
	}

	function gerar_arquivo(){
		setprogress(0, "Validando dados enviados", TRUE);
		$this->arquivo_nome = $this->estabelecimento->getdirarqfiscal().'Sintegra '.$this->str_zero($this->estabelecimento->getcodestabelec(), 3).$this->mesgeracao.$this->anogeracao.".txt";
		$this->arquivo = fopen($this->arquivo_nome, "w+");
		// Verifica se foi informado o estabelecimento
		if(!is_object($this->estabelecimento)){
			$_SESSION["ERROR"] = "Informe o estabelecimento emitente para o arquivo do Sintegra.";
			return FALSE;
		}

		// Verifica se foi informado a data inicial
		if(strlen($this->data_inicial) == 0){
			$_SESSION["ERROR"] = "Informe a data inicial da gera&ccedil;&atilde;o do arquivo do Sintegra.";
			return FALSE;
		}

		// Verifica se foi informado a data final
		if(strlen($this->data_final) == 0){
			$_SESSION["ERROR"] = "Informe a data final da gera&ccedil;&atilde;o do arquivo do Sintegra.";
			return FALSE;
		}

		// Verifica se foi informado a finalidade da geracao do arquivo
		if(strlen($this->finalidade) == 0){
			$_SESSION["ERROR"] = "Informe a finalidade da gera&ccedil;&atilde;o do arquivo";
			return FALSE;
		}

		// Verifica se o diretorio para geracao do arquivo foi informado
		if(strlen($this->estabelecimento->getdirarqfiscal()) == 0){
			$_SESSION["ERROR"] = "Informe o local de gera&ccedil;&atilde;o dos arquivos fiscais para o estabelecimento.<br><a onclick=\"$.messageBox('close'); openProgram('Estabel','codestabelec=".$this->estabelecimento->getcodestabelec()."')\">Clique aqui</a> para abrir o cadastro de estabelecimento.";
			return FALSE;
		}
		$this->filtro = " (CASE WHEN op.tipo='E' THEN nf.dtentrega >= '".$this->data_inicial."' AND nf.dtentrega <= '".$this->data_final."' ELSE  nf.dtemissao >= '".$this->data_inicial."' AND nf.dtemissao <= '".$this->data_final."' END) ";
		//busca codigos dos parceiros

		if($this->gerar_notasfiscais_entrada || $this->gerar_notasfiscais_saida){
			//buscas operacoes nota fiscal
			setprogress(0, "Carregando operacoes notas fiscais", TRUE);
			$this->arr_operacaonota = array();
			$operacaonota = objectbytable("operacaonota", NULL, $this->con);
			$arr_operacaonota = object_array($operacaonota);
			foreach($arr_operacaonota as $operacaonota){
				$this->arr_operacaonota[$operacaonota->getoperacao()] = $operacaonota;
			}

			//busca notas fiscais
			setprogress(0, "Carregando notas fiscais", TRUE);
			$this->arr_notafiscal = array();
			$query = "SELECT ";
			$query .= "	nf.dtentrega as dtentrega, nf.codparceiro as parceiro, nf.tipoparceiro as tipoparceiro, nf.serie as serie, nf.idnotafiscal AS idnotafiscal, ";
			$query .= "	it.natoperacao as cfop, nf.numnotafis, it.aliqicms as icms, ";
			$query .= "	v_parceiro.cpfcnpj AS cnpj, v_parceiro.rgie AS ie, v_parceiro.uf,  ";
			$query .= "	CASE ";
			$query .= "		WHEN nf.status = 'A' THEN 'N' ";
			$query .= "		WHEN nf.status = 'C' THEN 'S' ";
			$query .= "		WHEN nf.status = 'D' THEN '2' ";
			$query .= "		WHEN nf.status = 'I' THEN '4' ";
			$query .= "	END as modelo,";
			$query .= "	nf.operacao,nf.chavenfe, op.tipo as tipooperacao,";
			$query .= "	SUM(it.totalliquido) as valortotal, sum(it.totalbaseicms) as baseicms,";
			$query .= "	SUM(it.totalicms) as valoricms, sum(it.totalbaseisento) as valorisento, sum(it.totalbaseicmssubst) as baseicmsst,";
			$query .= "	SUM(it.totalicmssubst) as valoricmsst, sum(it.totalipi) as ipi ";
			$query .= "FROM itnotafiscal it ";
			$query .= "INNER JOIN notafiscal nf USING(idnotafiscal) ";
			$query .= "INNER JOIN v_parceiro ON (nf.codparceiro = v_parceiro.codparceiro AND nf.tipoparceiro = v_parceiro.tipoparceiro)";
			$query .= "INNER JOIN operacaonota op ON (nf.operacao = op.operacao) ";
			$query .= "WHERE nf.gerafiscal = 'S' AND ".$this->filtro." AND nf.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") ";

			if(!($this->gerar_notasfiscais_entrada && $this->gerar_notasfiscais_saida)){
				if($this->gerar_notasfiscais_saida){
					$query .= " AND op.tipo = 'E' ";
				}
				if($this->gerar_notasfiscais_entrada){
					$query .= " AND op.tipo = 'S' ";
				}
			}

			foreach($this->arr_filtro as $coluna => $valor){
				$query .= "	AND nf.".$coluna." = '".$valor."' ";
			}
			$query .= "GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15 ";
			$query .= "ORDER BY nf.dtentrega ";

//			$query .= "SELECT notafrete.dtentrega, notafrete.codtransp AS codparceiro, 'T' AS tipoparceiro, notafrete.serie, notafrete.idnotafrete as idnotafiscal, ";
//			$query .= "notafrete.natoperacao AS cfop, notafrete.numnotafis, notafrete.aliqicms as icms, ";
//			$query .= "v_parceiro.cpfcnpj AS cnpj, v_parceiro.rgie AS ie, v_parceiro.uf, ";
//			$query .= "'N' AS modelo, ";
//			$query .= "operacaonota.operacao,notafrete.chavecte AS chavenfe, operacaonota.tipo as tipooperacao, ";
//			$query .= "notafrete.totalliquido as valortotal, notafrete.totalbaseicms as baseicms, ";
//			$query .= "notafrete.totalicms as valoricms, notafrete.totalbaseisento as valorisento, 0 as baseicmsst, ";
//			$query .= "0 as valoricmsst, 0 as ipi ";
//			$query .= "FROM notafrete ";
//			$query .= "INNER JOIN v_parceiro ON (notafrete.codtransp = v_parceiro.codparceiro AND 'T' = v_parceiro.tipoparceiro) ";
//			$query .= "INNER JOIN natoperacao ON (notafrete.natoperacao = natoperacao.natoperacao) ";
//			$query .= "INNER JOIN operacaonota ON (natoperacao.operacao = operacaonota.operacao) ";
//			$query .= "WHERE notafrete.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") ";
//			$query .= "AND notafrete.dtentrega >= '".$this->data_inicial."' AND notafrete.dtentrega <= '".$this->data_final."' ";
//			echo $query;
			$res = $this->con->query($query);
			$this->arr_notafiscal_50 = $res->fetchAll(2);
		}
		echo "Inicio geracao =>".date("H:i:s")."<br><br>";
		$this->gerar_registro10();
		$this->gerar_registro11();
		if($this->gerar_notasfiscais_entrada || $this->gerar_notasfiscais_saida){
			$this->gerar_registro50();
			$this->gerar_registro53();
			$this->gerar_registro54();
			$this->gerar_registro55();
		}

		if($this->gerar_ecf){
			setprogress(0, "Carregando dados dos caixas", TRUE);
			$this->gerar_registro60();
			$this->gerar_registro61();
			$this->gerar_registro61R();
		}

		if($this->gerar_notasfiscais_entrada || $this->gerar_notasfiscais_saida){
			$this->gerar_registro70();
		}
		if($this->gerar_inventario){
			$this->gerar_registro74();
		}
		$this->gerar_registro75();
		$this->gerar_registro90();

		if(param("SISTEMA", "TIPOSERVIDOR", $this->con) == "1"){
			$this->download();
		}
		echo "Final geracao =>".date("H:i:s")."<br>";
		return TRUE;
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes sobre o estabelcimento informante do arquivo
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro10(){
		$cidade = objectbytable("cidade", $this->estabelecimento->getcodcidade(), $this->con);
		$linha = "10"; //tipo do registro
		$linha .= $this->valor_texto(removeformat($this->estabelecimento->getcpfcnpj()), 14); //cnpj do contribuinte
		$linha .= $this->valor_texto(removeformat($this->estabelecimento->getrgie()), 14);  //inscricao estadual do contribuinte
		$linha .= $this->valor_texto($this->estabelecimento->getrazaosocial(), 35); //razao social do contribuinte
		$linha .= $this->valor_texto($cidade->getnome(), 30);   //nome da cidade do contribuinte
		$linha .= $this->estabelecimento->getuf();   //unidade da federacao do contribuinte
		$linha .= $this->valor_numerico(removeformat($this->estabelecimento->getfax()), 0, 10);   //numero do fax
		$linha .= $this->valor_data($this->data_inicial);  //data inicial do periodo
		$linha .= $this->valor_data($this->data_final);  //data fianl do periodo
		$linha .= "3"; //codigo de identificacao da estrutura do arquivo
		$linha .= "3"; //codigo de identificacao da natureza das operacoes
		$linha .= $this->finalidade;   //codigo da finalidade do arquivo
		$this->bloco10[] = $linha; //incluir a linha do registro 10 no arquivo
		$this->escrever_bloco($this->bloco10);
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes complemnetares sobre o estabelecimento informante do arquivo
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro11(){
		$linha = "11";   //tipo do registro
		$linha .= $this->valor_texto($this->estabelecimento->getendereco(), 34);   //endereco do contribuinte
		$linha .= $this->str_zero($this->estabelecimento->getnumero(), 5);  //numero referente ao endereco do contribuinte
		$linha .= $this->valor_texto($this->estabelecimento->getcomplemento(), 22);   //complemento do endereco do contribuinte
		$linha .= $this->valor_texto($this->estabelecimento->getbairro(), 15); //bairro do contribuinte
		$linha .= $this->str_zero(removeformat($this->estabelecimento->getcep()), 8);   //cep do contribuinte

		if($this->estabelecimento->getcontato() == ""){
			$linha .= $this->valor_texto("sem contato", 28); //nome do responsavel
		}else{
			$linha .= $this->valor_texto($this->estabelecimento->getcontato(), 28);   //nome do responsavel
		}
		$linha .= $this->str_zero(removeformat($this->estabelecimento->getfone1()), 12);  //numero do telefone do contribuinte
		$this->bloco11[] = $linha;   //inclusao do registro 11 no arquivo
		$this->escrever_bloco($this->bloco11);
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes das base de icms das notas fiscais do estabelecimento Entrada/Saida
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro50(){
		foreach($this->arr_notafiscal_50 as $notafiscal){
			$itnotafiscal = objectbytable("itnotafiscal", $notafiscal["idnotafiscal"],$this->con);
			$linha = "50";
			$linha .= $this->str_zero(substr(removeformat($notafiscal["cnpj"]), -14), 14); //cnpj do remetente/destinatario

			if(strlen(removeformat($notafiscal["ie"])) == 12 && removeformat($notafiscal["ie"]) != 0){
				$linha .= $this->valor_texto(removeformat($notafiscal["ie"]), 14);   //inscricao estadual do remetente/destinatario
			}else{
				$linha .= $this->valor_texto("ISENTO", 14);
			}
			$linha .= $this->valor_data($notafiscal["dtentrega"]);
			$linha .= $notafiscal["uf"]; //unidade da federacao
			$linha .= $this->modelo($notafiscal);  //modelo do documento
			$linha .= $this->valor_numerico(preg_replace("[a-zA-Z]", 9, ($notafiscal["serie"] == 000 ? 999 : $notafiscal["serie"])), 0, 3); //serie da nota fiscal
			$linha .= substr($this->str_zero(trim($notafiscal["numnotafis"]), 9), -6);  //numero da nota fiscal
			$linha .= $this->str_zero(substr(removeformat($notafiscal["cfop"]), 0, 4), 4);  //cfop da nota fiscal
			$linha .= (substr($notafiscal["cfop"], 0, 1) >= 5 || (substr($notafiscal["cfop"], 0, 1) <= 3 && (in_array($notafiscal["tipoparceiro"], array("C", "E")) || ($notafiscal["operacao"] == "IM" && $notafiscal["tipoparceiro"] == "F"))) ? "P" : "T");  //emitente da nota fiscal  P-proprio T-terceiros
			$linha .= $this->str_zero(number_format($notafiscal["valortotal"], 2, "", ""), 13);   //valor total da nota fiscal referente ao cfop/ e ou aliquota de icms
			if(in_array(substr($notafiscal["cfop"], 0, 5), array("5.929", "6.929"))){
				$linha .= $this->str_zero(0, 13); //quando estiver ST destacado na nota fiscal informar base icms zerada
				$linha .= $this->str_zero(0, 13); //quando estiver ST destacado na nota fiscal informar valor icms zerada
				$linha .= $this->str_zero(0, 13); //isento zerado
				$linha .= $this->str_zero(0, 13); //outras  zerado
				$linha .= $this->str_zero(number_format($notafiscal["icms"], 2, "", ""), 4);   //aliquota de icms
			}else{
				if(($notafiscal["baseicmsst"] > 0 && $notafiscal["tipooperacao"] == "E") || ($itnotafiscal->gettipoipi() == "F" && $notafiscal["tipooperacao"] == "E")){   //se for nf de entrada com ST destacada e for uma entrada
					$linha .= $this->str_zero(0, 13);   //quando estiver ST destacado na nota fiscal informar base icms zerada
					$linha .= $this->str_zero(0, 13);   //quando estiver ST destacado na nota fiscal informar valor icms zerada
					$linha .= $this->str_zero(0, 13);   //isento
					$linha .= $this->str_zero(number_format($notafiscal["valortotal"] + $notafiscal["ipi"], 2, "", ""), 13); //outras
				}elseif($notafiscal["baseicmsst"] > 0 && $notafiscal["tipooperacao"] == "S"){  //se for nf de saida com ST destacada e for uma entrada
					$linha .= $this->str_zero(number_format($notafiscal["baseicms"], 2, "", ""), 13); //quando estiver ST destacado na nota fiscal informar base icms proprio
					$linha .= $this->str_zero(number_format($notafiscal["valoricms"], 2, "", ""), 13); //quando estiver ST destacado na nota fiscal informar valor icms proprio
					$linha .= $this->str_zero(0, 13);   //isento
					$linha .= $this->valor_numerico($notafiscal["ipi"], 2, 13);   //outras
				}else{
					if($itnotafiscal->gettptribicms() == "F"){ //se for nf com ST substituido (CST 60)
						$linha .= $this->str_zero(0, 13);  //quando estiver CST 60 na nota fiscal informar base icms zerada
						$linha .= $this->str_zero(0, 13);  //quando estiver CST 60 na nota fiscal informar valor icms zerada
						$linha .= $this->str_zero(0, 13);  //isento
						$linha .= $this->str_zero(number_format($notafiscal["valortotal"] + $notafiscal["ipi"], 2, "", ""), 13);   //outras
					}else{
						$linha .= $this->str_zero(number_format($notafiscal["baseicms"], 2, "", ""), 13);   //base de icms
						$linha .= $this->str_zero(number_format($notafiscal["valoricms"], 2, "", ""), 13);   //valor de icms
						$linha .= $this->str_zero(number_format($notafiscal["baseisento"], 2, "", ""), 13);   //isento
						$linha .= $this->valor_numerico($notafiscal["ipi"], 2, 13);  //outras
					}
				}
				$linha .= $this->str_zero(number_format($notafiscal["icms"], 2, "", ""), 4);   //aliquota de icms
			}
			$linha .= ($notafiscal["status"] != "C" ? "N" : "S");   //situacao nota fiscal
			$this->escrever_linha($linha);
			$this->total_rg_50++;
			$this->arr_itnotafiscal_54[] = $notafiscal["idnotafiscal"];
		}
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes ipi dos produtos movimentados via notas fiscais
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro51(){

	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes de produtos via notas fiscais sujeitos a substiuicao tributaria Entrada/Saida
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro53(){
		foreach($this->arr_notafiscal_50 as $notafiscal){
			if(($notafiscal["baseicmsst"] > 0)){ //se for nf de entrada com ST destacada e for uma entrada
				$linha = "53";
				$linha .= $this->str_zero(substr(removeformat($notafiscal["cnpj"]), -14), 14);   //cnpj do remetente/destinatario
				$ie = trim(removeformat($notafiscal["ie"]));
				if(strlen($ie) > 0){
					$linha .= $this->valor_texto($ie, 14);   //inscricao estadual do remetente/destinatario
				}else{
					$linha .= $this->valor_texto("ISENTO", 14);
				}
				$linha .= convert_date($notafiscal["dtentrega"], "Y-m-d", "Ymd"); //data de emissao/entrada da nota fiscal
				$linha .= $notafiscal["uf"];   //unidade da federacao
				$linha .= $this->modelo($notafiscal); //modelo do documento
				$linha .= $this->valor_numerico(preg_replace("[a-zA-Z]", 9, ($notafiscal["serie"] == 000 ? 999 : $notafiscal["serie"])), 0, 3); //serie da nota fiscal
				$linha .= substr($this->str_zero(trim($notafiscal["numnotafis"]), 9), -6); //numero da nota fiscal
				$linha .= $this->str_zero(removeformat($notafiscal["cfop"]), 4); //cfop da nota fiscal
				$linha .= (substr($notafiscal["cfop"], 0, 1) >= 5 || (substr($notafiscal["cfop"], 0, 1) <= 3 && (in_array($notafiscal["tipoparceiro"], array("C", "E")) || ($notafiscal["operacao"] == "IM" && $notafiscal["tipoparceiro"] == "F"))) ? "P" : "T");  //emitente da nota fiscal  P-proprio T-terceiros
				$linha .= $this->str_zero(number_format($notafiscal["baseicmsst"], 2, "", ""), 13);   //quando estiver ST destacado na nota fiscal informar base icms ST
				$linha .= $this->str_zero(number_format($notafiscal["valoricmsst"], 2, "", ""), 13);  //quando estiver ST destacado na nota fiscal informar valor icms ST
				$linha .= $this->str_zero(number_format($notafiscal["valorfrete"] + $notafiscal["valorseseguro"], 2, "", ""), 13); //despesas acessorias
				$linha .= ($notafiscal["status"] != "C" ? "N" : "S");   //situacao nota fiscal
				$linha .= "4"; //codigo de antecipacao
				$linha .= str_repeat(" ", 29);   //espacos em branco
				$this->bloco53[] = $linha;
				$this->total_rg_53++;
			}
		}
		$this->escrever_bloco($this->bloco53);
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes dos itens movimentados via Notas fiscais pelo estabelecimento Entrada/Saida
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro54(){
		if(count($this->arr_itnotafiscal_54) == 0){
			die(messagebox("error", "", "Nenhuma nota encontrada!"));
		}
		$arr_idnotafiscal = array_unique($this->arr_itnotafiscal_54);

		$_SESSION["sintegra"]["arr_idnotafiscal"] = $arr_idnotafiscal;
		$query = "	SELECT ";
		$query .= "		CASE ";
		$query .= "			WHEN nf.tipoparceiro = 'F' THEN (select cpfcnpj from fornecedor where codfornec = nf.codparceiro) ";
		$query .= "			WHEN nf.tipoparceiro = 'C' THEN (select cpfcnpj from cliente where codcliente = nf.codparceiro) ";
		$query .= "			WHEN nf.tipoparceiro = 'E' THEN (select cpfcnpj from estabelecimento where codestabelec = nf.codparceiro) ";
		$query .= "		END as cnpj, ";
		$query .= "	CASE ";
		$query .= "		WHEN nf.tipoparceiro = 'F' THEN (SELECT rgie FROM fornecedor WHERE codfornec = nf.codparceiro)";
		$query .= "		WHEN nf.tipoparceiro = 'C' THEN (SELECT rgie FROM cliente WHERE codcliente = nf.codparceiro)";
		$query .= "		WHEN nf.tipoparceiro = 'E' THEN (SELECT rgie FROM estabelecimento WHERE codestabelec = nf.codparceiro)";
		$query .= "	END as ie,";
		$query .= "		it.csticms, nf.tipoparceiro, it.totalfrete, ";
		$query .= "		nf.serie as serie,nf.numnotafis as notafiscal, it.natoperacao as cfop, it.seqitem, ";
		$query .= "		it.codproduto, it.quantidade, (it.totalbruto + it.totalacrescimo) AS totalbruto, (it.valdescto * it.quantidade) as totaldesconto, it.totalbaseicms,it.totalbaseicmssubst, ";
		$query .= "		it.totalipi, it.aliqicms, nf.chavenfe, nf.operacao, it.tipoipi ";
		$query .= "	FROM itnotafiscal it ";
		$query .= "	INNER JOIN notafiscal nf using(idnotafiscal) ";
		$query .= "	INNER JOIN operacaonota op on (nf.operacao = op.operacao) ";
		$query .= "	WHERE nf.idnotafiscal IN (".implode(",", $arr_idnotafiscal).") AND nf.status != 'C' AND nf.gerafiscal = 'S' ";
		$query .= " AND nf.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") ";
		foreach($this->arr_filtro as $coluna => $valor){
			$query .= "	AND nf.".$coluna." = '".$valor."' ";
		}
		$query .= "	ORDER BY cnpj, nf.serie, nf.numnotafis, it.seqitem ";
		$this->arr_notafiscal_54 = array();
		$res = $this->con->query($query);
		$this->arr_notafiscal_54 = $res->fetchAll(2);
		$notafiscal = 0;
		$totalfrete = 0;
		foreach($this->arr_notafiscal_54 as $key => $itnotafiscal){
			////notas fiscais a partir de cupom fiscal, gerar somente o registro 50
			if($totalfrete > 0 && $itnotafiscal["notafiscal"] != $notafiscal){
				$linha = $cabecalho;
				$linha .= "991";
				$linha .= $this->valor_texto(" ", 14);
				$linha .=$this->valor_numerico(0, 0, 11);
				$linha .= $this->valor_numerico($totalfrete, 2, 12);
				$linha .= $this->valor_numerico(0, 0, 52);
				$this->bloco54[] = $linha;
				$this->total_rg_54++;
			}

			if($itnotafiscal["tipoparceiro"] == "T"){
				continue;
			}
			if(in_array(substr($itnotafiscal["cfop"], 0, 5), array("5.929", "6.929"))){
				continue;
			}

			//gerar registro 54 do produto
			$linha = "54";
			$linha .= $this->str_zero(substr(removeformat($itnotafiscal["cnpj"]), -14), 14);  //cnpj do remetente/destinatario
			$linha .= $this->modelo($itnotafiscal);   //modelo do documento
			$linha .= $this->valor_numerico(preg_replace("[a-zA-Z]", 9, ($itnotafiscal["serie"] == 000 ? 999 : $itnotafiscal["serie"])), 0, 3); //serie da nota fiscal
			$linha .= substr($this->str_zero(trim($itnotafiscal["notafiscal"]), 9), -6);   //numero da nota fiscal
			$linha .= $this->str_zero(substr(removeformat($itnotafiscal["cfop"]), 0, 4), 4);  //cfop da nota fiscal
			// Arruma o sequencial de item e gera o total frete no final de cada notafiscal no registro 54
			if($itnotafiscal["notafiscal"] != $notafiscal){
				$cabecalho = $linha."   ";
				$notafiscal = $itnotafiscal["notafiscal"];
				$seqitem = 1;
				$totalfrete = 0;
			}

			$linha .= $this->valor_texto($itnotafiscal["csticms"], 3); //CST do produto
			$linha .= $this->str_zero($seqitem, 3);   // Numero sequencial do item

			$seqitem++;

			$linha .= str_replace(".", "", $this->valor_numerico($itnotafiscal["codproduto"], 0, 14));   //codigo do produto
			$linha .= $this->str_zero(number_format($itnotafiscal["quantidade"], 2, "", ""), 11); //quantidade do produto
			$linha .= $this->str_zero(number_format($itnotafiscal["totalbruto"] + $itnotafiscal["totalipi"], 2, "", ""), 12); //total bruto
			$linha .= $this->str_zero(number_format($itnotafiscal["totaldesconto"], 2, "", ""), 12);   //total do desconto no item/despesa acessoria
			if(($itnotafiscal["totalbaseicmssubst"] > 0 && $itnotafiscal["tipooperacao"] == "S") || ($itnotafiscal["totalbaseicmssubst"] == 0) || ($itnotafiscal["tipoipi"] == "F" && $itnotafiscal["tipooperacao"] == "E")){   //se for uma saida com ST destacada, deve informar a base propria
				$linha .= $this->str_zero(number_format($itnotafiscal["totalbaseicms"], 2, "", ""), 12);  //base de calculo do icms
			}else{
				$linha .= $this->str_zero(0, 12);   //se for uma entrada com ST destacada, nao informar a base do icms proprio
			}
			if($itnotafiscal["tipooperacao"] == "E" || $itnotafiscal["tipoipi"] == "F"){
				$linha .= $this->str_zero(number_format($itnotafiscal["totalbruto"] + $itnotafiscal["totalipi"], 2, "", ""), 12);  //base de icms ST
			}else{
				$linha .= $this->str_zero(number_format($itnotafiscal["totalbaseicmssubst"], 2, "", ""), 12);  //base de icms ST
			}

			$linha .= $this->str_zero(number_format(0, 2, "", ""), 12);  //total do ipi
			$linha .= $this->str_zero(number_format($itnotafiscal["aliqicms"], 2, "", ""), 4);  //aliquota de icms
			$totalfrete += $itnotafiscal["totalfrete"];
			$this->bloco54[] = $linha;
			$this->total_rg_54++;
			$this->arr_produto_75[] = $itnotafiscal["codproduto"];  //codigo do produto gerado no registro 54
		}

		if($totalfrete > 0){
			$linha = $cabecalho;
			$linha .= "991";
			$linha .= $this->valor_texto(" ", 14);
			$linha .=$this->valor_numerico(0, 0, 11);
			$linha .= $this->valor_numerico($totalfrete, 2, 12);
			$linha .= $this->valor_numerico(0, 0, 52);
			$this->bloco54[] = $linha;
			$this->total_rg_54++;
		}


		//gerar registro 54 para frete na nf
		//gerar registro 54 para frete na nf
		if($notafiscal["gettotalseguro"] > 0){
			$linha = "54";
			$linha .= $this->str_zero(substr(removeformat($parceiro->getcpfcnpj()), -14), 14); //cnpj do remetente/destinatario
			$linha .= $this->modelo($notafiscal);   //modelo do documento
			$linha .= $this->valor_texto($notafiscal->getserie(), 3); //serie da nota fiscal
			$linha .= substr($this->str_zero(trim($notafiscal->getnumnotafis()), 9), -6);   //numero da nota fiscal
			$linha .= $this->str_zero(substr(removeformat($itnotafiscal->getnatoperacao()), 0, 4), 4);   //cfop da nota fiscal
			$linha .= "   ";  //CST do produto
			$linha .= "992";  //Numero sequencial do item
			$linha .= str_repeat(" ", 14);  //codigo do produto
			$linha .= $this->str_zero(100, 11); //quantidade do produto
			$linha .= $this->str_zero(number_format($notafiscal->gettotalfrete(), 2, "", ""), 12); //total bruto
			$linha .= $this->str_zero(0, 12); //total do desconto no item/despesa acessoria
			$linha .= $this->str_zero(0, 12); //base de calculo do icms
			$linha .= $this->str_zero(0, 12); //base de icms ST
			$linha .= $this->str_zero(0, 12); //total do ipi
			$linha .= $this->str_zero(0, 4);  //aliquota de icms
			$this->bloco54[] = $linha;
			$this->total_rg_54++;
		}

		//gerar registro 54 para acrescimos na nf
		if($notafiscal["gettotalacrescimo"] > 0){
			$linha = "54";
			$linha .= $this->str_zero(substr(removeformat($parceiro->getcpfcnpj()), -14), 14); //cnpj do remetente/destinatario
			$linha .= $this->modelo($notafiscal);   //modelo do documento
			$linha .= $this->valor_texto($notafiscal->getserie(), 3); //serie da nota fiscal
			$linha .= substr($this->str_zero(trim($notafiscal->getnumnotafis()), 9), -6);   //numero da nota fiscal
			$linha .= $this->str_zero(substr(removeformat($itnotafiscal->getnatoperacao()), 0, 4), 4);   //cfop da nota fiscal
			$linha .= "   ";  //CST do produto
			$linha .= "999";  //Numero sequencial do item
			$linha .= str_repeat(" ", 14);  //codigo do produto
			$linha .= $this->str_zero(100, 11); //quantidade do produto
			$linha .= $this->str_zero(number_format($notafiscal->gettotalacrescimo(), 2, "", ""), 12);   //total bruto
			$linha .= $this->str_zero(0, 12); //total do desconto no item/despesa acessoria
			$linha .= $this->str_zero(0, 12); //base de calculo do icms
			$linha .= $this->str_zero(0, 12); //base de icms ST
			$linha .= $this->str_zero(0, 12); //total do ipi
			$linha .= $this->str_zero(0, 4);  //aliquota de icms
			$this->bloco54[] = $linha;
			$this->total_rg_54++;
		}
		$this->escrever_bloco($this->bloco54);
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes de guias GNRE recebidas ou emitidas pelo estabelecimento
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro55(){

	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes de produtos comercialializado via ecf pelo estabelecimento
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro60(){
		$query = "SELECT DISTINCT maparesumo.dtmovto, maparesumo.totalcupomcancelado ,maparesumo.totaldescontocupom, maparesumo.codmaparesumo,ecf.numfabricacao, ecf.caixa AS caixa, maparesumo.operacaoini, maparesumo.operacaofim, ";
		$query .= "maparesumo.numeroreducoes, maparesumo.reiniciofim, maparesumo.totalbruto, maparesumo.gtfinal, maparesumo.numerodescontos,maparesumo.cuponscancelados ";
		$query .= "FROM maparesumo ";
		$query .= "INNER JOIN ecf ON (maparesumo.caixa = ecf.caixa AND maparesumo.codestabelec = ecf.codestabelec) ";
		$query .= "WHERE maparesumo.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") AND maparesumo.dtmovto BETWEEN '".$this->data_inicial."' AND '".$this->data_final."' ";
		foreach($this->arr_filtro as $coluna => $valor){
			if($coluna == "dtentrega"){
				$query .= "	AND maparesumo.".str_replace("dtentrega", "dtmovto", $coluna)." = '".$valor."' ";
			}
		}

		$res = $this->con->query($query);
		$this->arr_notafiscal_60M = $res->fetchAll(2);
		$aux = 0;
		foreach($this->arr_notafiscal_60M as $i => $arr_notafiscal_60M){
			setprogress($i / sizeof($this->arr_notafiscal_60M) * 100, "Carregando registro 60: ".($i + 1)." de ".sizeof($this->arr_notafiscal_60M));
			if($arr_notafiscal_60M["codmaparesumo"] != $aux){
				$linha = "60";   // Tipo
				$linha .= "M";   // Subtipo
				$linha .= $this->valor_data($arr_notafiscal_60M["dtmovto"]);   // Data de emissao
				$linha .= $this->valor_texto($arr_notafiscal_60M["numfabricacao"], 20); // Numero de fabricacao
				$linha .= $this->valor_numerico($arr_notafiscal_60M["caixa"], 0, 3);  // Numero do caixa
				$linha .= "2D";   // Modelo do documento fiscal
				$linha .= $this->valor_numerico($arr_notafiscal_60M["operacaoini"] == 0 ? 1 : $arr_notafiscal_60M["operacaoini"], 0, 6);   // Numero do contador da operacao inicial
				$linha .= $this->valor_numerico($arr_notafiscal_60M["operacaofim"], 0, 6);   // Numero do contador da operacao final
				$linha .= $this->valor_numerico($arr_notafiscal_60M["numeroreducoes"], 0, 6);   // Numero do contador da reducao Z
				$linha .= $this->valor_numerico($arr_notafiscal_60M["reiniciofim"], 0, 3);   // Valor acumulado do contador reinicio de operacao
				$linha .= $this->valor_numerico($arr_notafiscal_60M["totalbruto"], 2, 16);   // Valor da venda bruta
				$linha .= $this->valor_numerico($arr_notafiscal_60M["gtfinal"], 2, 16); // Valor totalizador do equipamento
				$linha .= str_repeat(" ", 37);
				$this->escrever_linha($linha);
				$linha = "";
			}

			$query = "SELECT DISTINCT maparesumoimposto.tptribicms, maparesumoimposto.aliqicms, maparesumoimposto.totalliquido ";
			$query .= "FROM maparesumoimposto ";
			$query .= "WHERE maparesumoimposto.codmaparesumo = ".$arr_notafiscal_60M["codmaparesumo"]."  AND maparesumoimposto.tptribicms <> ' ' ";
			$this->arr_notafiscal_60A = array();
			$res = $this->con->query($query);
			$this->arr_notafiscal_60A = $res->fetchAll(2);
			foreach($this->arr_notafiscal_60A as $arr_notafiscal_60A){
				$sittrib = $this->valor_aliquota($arr_notafiscal_60A["aliqicms"], $arr_notafiscal_60A["tptribicms"]);
				$linha = "60"; // Tipo
				$linha .= "A"; // Subtipo
				$linha .= $this->valor_data($arr_notafiscal_60M["dtmovto"]); // Data de emissao
				$linha .= $this->valor_texto($arr_notafiscal_60M["numfabricacao"], 20);  // Numero de fabricacao
				$linha .= $sittrib; // Situacao Tributaria
				$linha .= $this->valor_numerico($arr_notafiscal_60A["totalliquido"], 2, 12); // Total liquido
				$linha .= str_repeat(" ", 79);
				$this->escrever_linha($linha);
				$linha = "";
				$this->total_rg_60++;
			}

			if($arr_notafiscal_60M["totaldescontocupom"] != 0.00){
				$linha = "60"; // Tipo
				$linha .= "A"; // Subtipo
				$linha .= $this->valor_data($arr_notafiscal_60M["dtmovto"]); // Data de emissao
				$linha .= $this->valor_texto($arr_notafiscal_60M["numfabricacao"], 20); // Numero de fabricacao
				$linha .= "DESC";   // Situacao Tributaria
				$linha .= $this->valor_numerico($arr_notafiscal_60M["totaldescontocupom"], 2, 12);  // Total liquido
				$linha .= str_repeat(" ", 79);
				$this->escrever_linha($linha);
				$linha = "";
				$this->total_rg_60++;
			}

			if($arr_notafiscal_60M["totalcupomcancelado"] > 0){
				$linha = "60"; // Tipo
				$linha .= "A"; // Subtipo
				$linha .= $this->valor_data($arr_notafiscal_60M["dtmovto"]); // Data de emissao
				$linha .= $this->valor_texto($arr_notafiscal_60M["numfabricacao"], 20);  // Numero de fabricacao
				$linha .= "CANC";   // Situacao Tributaria
				$linha .= $this->valor_numerico($arr_notafiscal_60M["totalcupomcancelado"], 2, 12);  // Total liquido
				$linha .= str_repeat(" ", 79);
				$this->escrever_linha($linha);
				$linha = "";
				$this->total_rg_60++;
			}

			if($this->opcao60D){
				$query = "SELECT DISTINCT itcupom.codproduto, itcupom.tptribicms AS tribicms,itcupom.aliqicms, ";
				$query .= "CASE itcupom.tptribicms WHEN 'T' THEN SUM(itcupom.valortotal) WHEN 'R' THEN SUM(itcupom.valortotal) ELSE 0 END AS baseicms, ";
				$query .= "CASE itcupom.status  WHEN 'A' THEN itcupom.aliqicms WHEN 'C' THEN 123 END AS aliquota, ";
				$query .= "CASE itcupom.tptribicms WHEN 'T' THEN SUM(itcupom.valortotal * itcupom.aliqicms / 100) WHEN 'R' THEN SUM(itcupom.valortotal* itcupom.aliqicms / 100) ELSE 0 END AS icms, ";
				$query .= "SUM(itcupom.quantidade) AS quantidade, SUM(itcupom.valortotal) AS valortotal ";
				$query .= "FROM itcupom ";
				$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
				$query .= "WHERE cupom.caixa = ".$arr_notafiscal_60M["caixa"]." AND cupom.dtmovto = '".$arr_notafiscal_60M["dtmovto"]."' AND itcupom.tptribicms <> ' '  AND itcupom.composicao <> 'F' ";
				foreach($this->arr_filtro as $coluna => $valor){
					if($coluna == "dtentrega"){
						$query .= "	AND cupom.".str_replace("dtentrega", "dtmovto", $coluna)." = '".$valor."' ";
					}
				}
				$query .= "GROUP BY itcupom.codproduto, itcupom.tptribicms, itcupom.aliqicms, itcupom.status, itcupom.composicao ";
				$query .= "ORDER BY itcupom.codproduto ";

				$this->arr_notafiscal_60D = array();
				$res = $this->con->query($query);
				$this->arr_notafiscal_60D = $res->fetchAll(2);

				foreach($this->arr_notafiscal_60D as $arr_notafiscal_60D){

					$linha = "60";  // Tipo
					$linha .= "D";  // Subtipo
					$linha .= $this->valor_data($arr_notafiscal_60M["dtmovto"]);  // Data de emissao
					$linha .= $this->valor_texto($arr_notafiscal_60M["numfabricacao"], 20);   // Numero de fabricacao
					$linha .= $this->valor_numerico($arr_notafiscal_60D["codproduto"], 0, 14);  // Codigo do produto
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60D["quantidade"], 3, 13)); // Quantidade do produto
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60D["valortotal"], 2, 16)); // Valor total do produto
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60D["baseicms"], 2, 16));  // Valor de base do ICMS
					if($arr_notafiscal_60D["aliquota"] == 123){
						$linha .= $this->valor_texto("CANC", 4);  // Situacao tributaria
					}else{
						$linha .= $this->valor_aliquota($arr_notafiscal_60D["aliqicms"], $arr_notafiscal_60D["tribicms"]); // Situacao tributaria
					}
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60D["icms"], 2, 13));  // Valor  do ICMS
					$linha .= str_repeat(" ", 19);

					$this->escrever_linha($linha);
					$linha = "";
					$this->total_rg_60++;
					$this->arr_produto_75[] = $arr_notafiscal_60D["codproduto"];
				}
			}
			if($this->opcao60I){
				$query = "SELECT itcupom.codproduto, itcupom.preco, cupom.cupom, itcupom.tptribicms, itcupom.aliqicms, itcupom.idcupom as id_cupom, ";
				$query .= "CASE itcupom.tptribicms WHEN 'T' THEN itcupom.preco WHEN 'R' THEN itcupom.preco ELSE 0 END AS baseicms, ";
				$query .= "CASE itcupom.tptribicms WHEN 'T' THEN itcupom.preco * itcupom.aliqicms / 100 WHEN 'R' THEN itcupom.preco * itcupom.aliqicms / 100 ELSE 0 END AS icms, ";
				$query .= "CASE itcupom.status  WHEN 'A' THEN itcupom.aliqicms WHEN 'C' THEN 123 END AS aliquota, ";
				$query .= "itcupom.quantidade AS quantidade, itcupom.valortotal AS valortotal ";
				$query .= "FROM itcupom ";
				$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
				$query .= "WHERE cupom.caixa = ".$arr_notafiscal_60M["caixa"]." AND cupom.dtmovto = '".$arr_notafiscal_60M["dtmovto"]."' AND itcupom.tptribicms <> ' ' AND itcupom.composicao <> 'F' ";
				foreach($this->arr_filtro as $coluna => $valor){
					if($coluna == "dtentrega"){
						$query .= "	AND cupom.".str_replace("dtentrega", "dtmovto", $coluna)." = '".$valor."' ";
					}
				}
				$query .= "ORDER BY id_cupom,itcupom.codproduto,itcupom.composicao ";
				//echo "***".$query."***<br>";
				$res = $this->con->query($query);
				$this->arr_notafiscal_60I = $res->fetchAll(2);
				$cont = 1;
				$cupom_contador = 0;
				foreach($this->arr_notafiscal_60I as $arr_notafiscal_60I){
					if($cupom_contador == $arr_notafiscal_60I["id_cupom"]){
						$cont++;
					}else{
						$cont = 1;
					}
					$linha = "60";   // Tipo
					$linha .= "I";   // Subtipo
					$linha .= $this->valor_data($arr_notafiscal_60M["dtmovto"]);   // Data de emissao
					$linha .= $this->valor_texto($arr_notafiscal_60M["numfabricacao"], 20); // Numero de fabricacao
					$linha .= "2D";   // Modelo Fiscal
					$linha .= $this->valor_numerico($arr_notafiscal_60I["cupom"], 0, 6);  // Numero de ordem do documento fiscal
					$linha .= $this->valor_numerico($cont, 0, 3);  // Numero de ordem do item no documento fiscal
					$linha .= $this->valor_numerico($arr_notafiscal_60I["codproduto"], 0, 14);   // Codigo do produto
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60I["quantidade"], 3, 13)); // Quantidade do produto
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60I["preco"], 3, 13));   // Valor Unitario do produto
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60I["baseicms"], 2, 12));  // Valor de base do ICMS

					if($arr_notafiscal_60I["aliquota"] == 123){
						$linha .= $this->valor_texto("CANC", 4);   // Situacao tributaria
					}else{
						$linha .= $this->valor_aliquota($arr_notafiscal_60I["aliqicms"], $arr_notafiscal_60I["tptribicms"]); // Situacao tributaria
					}
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60I["icms"], 2, 12));   // Valor  do ICMS
					$linha .= str_repeat(" ", 16);
					$this->escrever_linha($linha);
					$linha = "";
					$this->total_rg_60++;

					$cupom_contador = $arr_notafiscal_60I["id_cupom"];
					$this->arr_produto_75[] = $arr_notafiscal_60I["codproduto"];
				}
			}
			$aux = $arr_notafiscal_60M["codmaparesumo"];
			$this->total_rg_60++;
		}

		if($this->opcao60R){
			setprogress(0, "Carregando registro 60R", TRUE);
			$query = "SELECT DISTINCT itcupom.codproduto, itcupom.tptribicms AS tribicms, itcupom.aliqicms, ";
			$query .= "(SELECT EXTRACT(MONTH FROM  cupom.dtmovto)) AS dtmovtomes, (SELECT EXTRACT(YEAR FROM  cupom.dtmovto)) AS dtmovtoano, ";
			$query .= "CASE itcupom.tptribicms WHEN 'T' THEN SUM(itcupom.valortotal) WHEN 'R' THEN SUM(itcupom.valortotal) ELSE 0 END AS baseicms, ";
			$query .= "CASE itcupom.status  WHEN 'A' THEN itcupom.aliqicms WHEN 'C' THEN 123 END AS aliquota, ";
			$query .= "SUM(itcupom.quantidade) AS quantidade, SUM(itcupom.valortotal) AS valortotal ";
			$query .= "FROM itcupom ";
			$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
			$query .= "WHERE cupom.dtmovto BETWEEN '".$this->data_inicial."' AND '".$this->data_final."' AND cupom.codestabelec = (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->estabelecimento->getcodestabelec()})  AND itcupom.tptribicms <> ' ' AND itcupom.composicao <> 'F' ";
			foreach($this->arr_filtro as $coluna => $valor){
				if($coluna == "dtentrega"){
					$query .= "	AND cupom.".str_replace("dtentrega", "dtmovto", $coluna)." = '".$valor."' ";
				}
			}
			$query .= "GROUP BY itcupom.codproduto, itcupom.tptribicms, itcupom.aliqicms, dtmovtomes, dtmovtoano, itcupom.status ";
			$query .= "ORDER BY  dtmovtomes,  dtmovtoano, itcupom.codproduto ";
			$this->arr_notafiscal_60R = array();
			$res = $this->con->query($query);
			$this->arr_notafiscal_60R = $res->fetchAll(2);

			foreach($this->arr_notafiscal_60R as $i => $arr_notafiscal_60R){
				setprogress($i / sizeof($this->arr_notafiscal_60R) * 100, "Carregando registro 60R: ".($i + 1)." de ".sizeof($this->arr_notafiscal_60R));
				if(strlen($arr_notafiscal_60R["dtmovtomes"]) == 1){
					$arr_notafiscal_60R["dtmovtomes"] = "0".$arr_notafiscal_60R["dtmovtomes"];
				}
				$linha = "60";  // Tipo
				$linha .= "R";  // Subtipo
				$linha .= ($arr_notafiscal_60R["dtmovtomes"].$arr_notafiscal_60R["dtmovtoano"]);   // Mes e ano de emissao
				$linha .= $this->valor_numerico($arr_notafiscal_60R["codproduto"], 0, 14);  // Codigo do produto
				$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60R["quantidade"], 3, 13));   // Quantidade do produto
				$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60R["valortotal"], 2, 16));   // Valor total do produto no periodo
				if($arr_notafiscal_60R["aliquota"] == "123"){
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60R["valortotal"], 2, 16));   // Valor de base do ICMS
					$linha .= $this->valor_texto("CANC", 4);  // Situacao tributaria
				}else{
					$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_60R["baseicms"], 2, 16)); // Valor de base do ICMS
					$linha .= $this->valor_aliquota($arr_notafiscal_60R["aliqicms"], $arr_notafiscal_60R["tribicms"]); // Situacao tributaria
				}
				$linha .= str_repeat(" ", 54);

				$this->bloco60R[] = $linha;
				$this->total_rg_60++;
				$this->arr_produto_75[] = $arr_notafiscal_60R["codproduto"];
			}
		}

		$this->escrever_bloco($this->bloco60R);
	}

	function gerar_registro61(){
		$query = "SELECT itcupom.codproduto, itcupom.preco, cupom.cupom, itcupom.tptribicms, itcupom.aliqicms, itcupom.idcupom as id_cupom, ";
		$query .= "CASE itcupom.tptribicms WHEN 'T' THEN itcupom.valortotal WHEN 'R' THEN itcupom.valortotal ELSE 0 END AS baseicms, ";
		$query .= "CASE itcupom.tptribicms WHEN 'T' THEN itcupom.preco * itcupom.aliqicms / 100 WHEN 'R' THEN itcupom.preco * itcupom.aliqicms / 100 ELSE 0 END AS icms, ";
		$query .= "CASE itcupom.status  WHEN 'A' THEN itcupom.aliqicms WHEN 'C' THEN 123 END AS aliquota, ";
		$query .= "itcupom.quantidade AS quantidade, itcupom.valortotal AS valortotal, cupom.dtmovto, cupom.caixa, ";
		$query .= "cupom.chavecfe ";
		$query .= "FROM itcupom ";
		$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
		$query .= "WHERE itcupom.tptribicms <> ' ' AND itcupom.composicao <> 'F'  AND cupom.chavecfe is not null ";
		$query .= "	AND cupom.dtmovto >='{$this->data_inicial}' AND cupom.dtmovto <='{$this->data_final}' ";
		$query .= " AND cupom.status = 'A' AND itcupom.status = 'A' ";
		foreach($this->arr_filtro as $coluna => $valor){
			if($coluna == "dtentrega"){
				$query .= "	AND cupom.".str_replace("dtentrega", "dtmovto", $coluna)." = '".$valor."' ";
			}
		}
		$query .= "GROUP BY 1, 2, 3, 4, 5, 6, itcupom.status, 10, 11, 12 , cupom.caixa, cupom.chavecfe ";
		$query .= "ORDER BY 1, 2, 3 ";
		
		$res = $this->con->query($query);
		$this->arr_notafiscal_61 = $res->fetchAll(2);

		$i = 1;
		$n = count($this->arr_notafiscal_61);

		foreach($this->arr_notafiscal_61 AS $notafiscal){
			setprogress(($i / $n * 100), "Carregando registro 61: {$i} de {$n}");
			$i++;

			$linha = "61";   // Tipo
			$linha .= str_repeat(" ", 14);   // brancos 14
			$linha .= str_repeat(" ", 14);   // brancos 14
			$linha .= $this->valor_data($notafiscal["dtmovto"]);   // Data de emissao
			$linha .= $this->valor_texto("65", 2); // Modelo do documento fiscal
			$linha .= $this->valor_texto("D", 3); // Serie do documento fiscal
			$linha .= $this->valor_texto("  ", 2); // Sub Serie do documento fiscal
			$linha .= $this->valor_numerico($notafiscal["cupom"], 0, 6); // Numero inicial da ordem
			$linha .= $this->valor_numerico($notafiscal["cupom"], 0, 6); // Numero final da ordem
			$linha .= str_replace(".", "", $this->valor_numerico($notafiscal["valortotal"], 2, 13)); // Valor Total
			$linha .= str_replace(".", "", $this->valor_numerico($notafiscal["baseicms"], 2, 13)); // Base de calculo
			$linha .= str_replace(".", "", $this->valor_numerico($notafiscal["icms"], 2, 12)); // Valor do icms
			$linha .= str_replace(".", "", $this->valor_numerico(0, 2, 13)); // Isenta ou nao tributado
			$linha .= str_replace(".", "", $this->valor_numerico(0, 2, 13)); // Outras
			$linha .= $this->valor_aliquota($notafiscal["aliqicms"], $notafiscal["tribicms"]); // Aliquota
			$linha .= " "; // brancos
			$this->total_rg_61++;
			$this->escrever_bloco($linha);
		}
	}

	function gerar_registro61R(){
		$query = "SELECT itcupom.codproduto, itcupom.tptribicms, itcupom.aliqicms, ";
		$query .= "itcupom.aliqicms AS aliquota, EXTRACT(MONTH FROM cupom.dtmovto) AS mes, EXTRACT(YEAR FROM cupom.dtmovto) AS ano, ";
		$query .= "SUM(CASE itcupom.tptribicms WHEN 'T' THEN itcupom.valortotal WHEN 'R' THEN itcupom.valortotal ELSE 0 END) AS baseicms, ";
		$query .= "SUM(CASE itcupom.tptribicms WHEN 'T' THEN itcupom.valortotal * itcupom.aliqicms / 100 WHEN 'R' THEN itcupom.valortotal * itcupom.aliqicms / 100 ELSE 0 END) AS icms, ";
		$query .= "SUM(itcupom.quantidade) AS quantidade, SUM(itcupom.valortotal) AS valortotal ";
		$query .= "FROM itcupom ";
		$query .= "INNER JOIN cupom ON (itcupom.idcupom = cupom.idcupom) ";
		$query .= "WHERE itcupom.tptribicms <> ' ' AND itcupom.composicao <> 'F' AND itcupom.status = 'A' AND cupom.chavecfe is not null ";
		$query .= " AND cupom.status = 'A' ";
		$query .= "	AND cupom.dtmovto >='{$this->data_inicial}' AND cupom.dtmovto <='{$this->data_final}' ";
		$query .= "GROUP BY 1,2,3,4,cupom.dtmovto ";
		$query .= "ORDER BY 1,2,3 ";

		$res = $this->con->query($query);
		$this->arr_notafiscal_61R = $res->fetchAll(2);

		$i = 1;
		$n = count($this->arr_notafiscal_61R);

		foreach($this->arr_notafiscal_61R AS $i => $notafiscal){
			setprogress(($i / $n * 100), "Carregando registro 61R: {$i} de {$n}");
			$i++;

			$linha = "61";   // Tipo
			$linha .= "R";   // Mestre
			$linha .= $this->valor_numerico($notafiscal["mes"], 0, 2); // Mes
			$linha .= $this->valor_numerico($notafiscal["ano"], 0, 4); // Ano
			$linha .= $this->valor_numerico($notafiscal["codproduto"], 0, 14);
			$linha .= $this->valor_numerico($notafiscal["quantidade"], 3, 13); // Quantidade
			$linha .= $this->valor_numerico($notafiscal["valortotal"], 2, 16); // Valor total
			$linha .= $this->valor_numerico($notafiscal["baseicms"], 2, 16); // Base de calculo do icms
			$linha .= $this->valor_aliquota($notafiscal["aliqicms"], $notafiscal["tribicms"]); // Aliquota
			$linha .= str_repeat(" ", 54);   // brancos 54

			$this->arr_produto_75[] = $notafiscal["codproduto"];
			$this->total_rg_61++;
			$this->escrever_bloco($linha);
		}
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes sobre notas de transporte emitente do documento
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro70(){
		$query .= "SELECT notafrete.dtentrega, notafrete.codtransp AS codparceiro, 'T' AS tipoparceiro, notafrete.serie, notafrete.idnotafrete, ";
		$query .= "notafrete.natoperacao, notafrete.numnotafis, notafrete.aliqicms as icms, ";
		$query .= "v_parceiro.cpfcnpj AS cnpj, v_parceiro.rgie AS ie, v_parceiro.uf, ";
		$query .= "'N' AS modelo, ";
		$query .= "operacaonota.operacao,notafrete.chavecte AS chavenfe, operacaonota.tipo as tipooperacao, ";
		$query .= "notafrete.totalliquido, notafrete.totalbaseicms, ";
		$query .= "notafrete.totalicms, notafrete.totalbaseisento, 0 as baseicmsst, ";
		$query .= "0 as valoricmsst, 0 as ipi ";
		$query .= "FROM notafrete ";
		$query .= "INNER JOIN v_parceiro ON (notafrete.codtransp = v_parceiro.codparceiro AND 'T' = v_parceiro.tipoparceiro) ";
		$query .= "INNER JOIN natoperacao ON (notafrete.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN operacaonota ON (natoperacao.operacao = operacaonota.operacao) ";
		$query .= "WHERE notafrete.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") ";
		$query .= "AND notafrete.dtentrega >= '".$this->data_inicial."' AND notafrete.dtentrega <= '".$this->data_final."' ";

		$res = $this->con->query($query);
		$this->arr_notafiscal_70 = $res->fetchAll(2);

		foreach($this->arr_notafiscal_70 AS $notafiscal_70){
			$linha = "70";   // Tipo
			$linha .= $this->valor_texto(removeformat($notafiscal_70["cnpj"]), 14);   // CNPJ
			$linha .= $this->valor_texto(removeformat($notafiscal_70["ie"]), 14);   // Incrição estadual
			$linha .= $this->valor_data($notafiscal_70["dtentrega"]);   // Data de emissao
			$linha .= $this->valor_texto($notafiscal_70["uf"], 2);
			$linha .= $this->valor_texto("08", 2);
			$linha .= $this->valor_texto(" ", 1);
			$linha .= $this->valor_texto(" ", 2);
			$linha .= $this->valor_numerico($notafiscal_70["numnotafis"], 0, 6);
			$linha .= $this->valor_numerico(removeformat($notafiscal_70["natoperacao"]), 0, 4);
			$linha .= $this->valor_numerico($notafiscal_70["totalliquido"], 2, 13);
			$linha .= $this->valor_numerico($notafiscal_70["totalbaseicms"], 2, 14);
			$linha .= $this->valor_numerico($notafiscal_70["totalicms"], 2, 14);
			$linha .= $this->valor_numerico($notafiscal_70["totalbaseisento"], 2, 14);
			$linha .= $this->valor_numerico(0, 2, 14);
			$linha .= $this->valor_texto("1", 1);
			$linha .= $this->valor_texto("N", 1);
			$this->total_rg_70++;
			$this->escrever_linha($linha);
			if(strlen($notafiscal_70["idnotafrete"]) > 0){
				$this->gerar_registro71($notafiscal_70);
			}
		}
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes sobre notas de transporte do tomador do servido de transporte
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro71($notafrete){
		$idnotafrete = $notafrete["idnotafrete"];
		$numnotafrete = $notafrete["numnotafis"];
		$arr_notafiscal = array();
		$notafiscal = objectbytable("notafiscal", NULL, $this->con);
		$notafiscal->setidnotafrete($idnotafrete);
		$arr_notafiscal = object_array($notafiscal);
		foreach($arr_notafiscal as $notafiscal){
			$query = "SELECT notafiscal.numnotafis, notafiscal.serie, v_parceiro.nome, ";
			$query .= "v_parceiro.rgie AS rgie_parceiro, v_parceiro.cpfcnpj as cpfcnpj_parceiro, ";
			$query .= "estabelecimento.rgie AS rgie_estabelec, estabelecimento.cpfcnpj AS cpfcnpj_estabelec, notafiscal.modfrete, ";
			$query .= "notafiscal.dtentrega, estabelecimento.uf AS uf_estab, v_parceiro.uf AS uf_parceiro, ";
			$query .= "notafiscal.numnotafis, notafiscal.dtemissao, notafiscal.totalliquido ";
			$query .= "FROM notafiscal ";
			$query .= "INNER JOIN v_parceiro ON (notafiscal.codparceiro = v_parceiro.codparceiro AND notafiscal.tipoparceiro = v_parceiro.tipoparceiro) ";
			$query .= "INNER JOIN estabelecimento ON (notafiscal.codestabelec = estabelecimento.codestabelec) ";
			$query .= "WHERE notafiscal.idnotafiscal = {$notafiscal->getidnotafiscal()} ";
			//modfrete 0-emit 1-dest
			echo $query;
			$res = $this->con->query($query);
			$notafiscal_71 = $res->fetch();


			$linha = "71";   // Tipo
			$linha .= $this->valor_texto(removeformat($notafrete["cnpj"]), 14);   // CNPJ
			$linha .= $this->valor_texto(removeformat($notafrete["ie"]), 14);   // Incrição estadual
			$linha .= $this->valor_data($notafrete["dtentrega"]);   // Data de emissao
			$linha .= $this->valor_texto($notafrete["uf"], 2);
//			if($notafiscal_71["modfrete"] == "0"){
//				$linha .= $this->valor_texto(removeformat($notafiscal_71["cpfcnpj_estabelec"]), 14);   // CNPJ
//				$linha .= $this->valor_texto(removeformat($notafiscal_71["rgie_estabelec"]), 14);   // Incrição estadual
//				$uf_tomador = $notafiscal_71["uf_estab"];
//			}else{
//				$linha .= $this->valor_texto(removeformat($notafiscal_71["cpfcnpj_parceiro"]), 14);   // CNPJ
//				$linha .= $this->valor_texto(removeformat($notafiscal_71["rgie_parceiro"]), 14);   // Incrição estadual
//				$uf_tomador = $notafiscal_71["uf_parceiro"];
//			}
//			$linha .= $this->valor_data($notafiscal_71["dtentrega"]);   // Data de emissao
//			$linha .= $this->valor_texto($uf_tomador, 2);
			$linha .= $this->valor_texto("08", 2);
			$linha .= $this->valor_texto(" ", 1);
			$linha .= $this->valor_texto(" ", 2);
			$linha .= $this->valor_numerico($numnotafrete, 0, 6);
			$linha .= $this->valor_texto($notafiscal_71["uf_parceiro"], 2);
			$linha .= $this->valor_texto(removeformat($notafiscal_71["cpfcnpj_parceiro"]), 14);   // CNPJ
			$linha .= $this->valor_texto(removeformat($notafiscal_71["rgie_parceiro"]), 14);   // Incrição estadual
			$linha .= $this->valor_data($notafiscal_71["dtemissao"]);   // Data de emissao
			$linha .= $this->valor_texto("55", 2);
			$linha .= $this->valor_texto(1, 1);
			$linha .= $this->valor_texto(" ", 2);
			$linha .= $this->valor_numerico($notafiscal_71["numnotafis"], 0, 6);
			$linha .= $this->valor_numerico($notafiscal_71["totalliquido"], 2, 14);
			$linha .= $this->valor_texto(" ", 12);
			$this->total_rg_71++;
			$this->escrever_linha($linha);
			$linha = "";
		}
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar o registro de inventario dos produtos inventariados no estabelecimento
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro74(){
		setprogress(0, "Carregando registro 74 Aguarde", TRUE);
		$query = "SELECT DISTINCT produtoestab.codproduto, produtoestab.codestabelec, produtoestab.custorep, estabelecimento.cpfcnpj AS cnpj, estabelecimento.rgie AS inscestadual, estabelecimento.uf, ";
		$query .= "COALESCE((SELECT saldo FROM produtoestabsaldo WHERE codproduto = produtoestab.codproduto AND codestabelec = produtoestab.codestabelec AND data <= '".$this->dtinventario."' AND produtoestabsaldo.saldo  > '0.0000' ORDER BY data DESC LIMIT 1),'0.0000') AS saldo ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN estabelecimento ON (estabelecimento.codestabelec = produtoestab.codestabelec) ";
		$query .= "WHERE produtoestab.disponivel = 'S' AND produtoestab.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = ".$this->estabelecimento->getcodestabelec().") ";

		$this->arr_itinventario_74 = array();
		$res = $this->con->query($query);
		$this->arr_itinventario_74 = $res->fetchAll(2);
		foreach($this->arr_itinventario_74 as $i => $itinventario){
			setprogress($i / sizeof($this->arr_itinventario_74) * 100, "Carregando registro 74: ".($i + 1)." de ".sizeof($this->arr_itinventario_74), TRUE);
			if($itinventario["saldo"] <= 0){
				continue;
			}
			if($itinventario["custorep"] == 0){
				continue;
			}
			$cnpj = str_replace(".", "", $itinventario["cnpj"]);
			$cnpj = str_replace("/", "", $cnpj);
			$cnpj = str_replace("-", "", $cnpj);
			$linha = "74";  // Tipo padrao 74
			$linha .= $this->valor_data($this->dtinventario);  // Data do inventario no formato AAAAMMDD
			$linha .= $this->valor_numerico($itinventario["codproduto"], 0, 14); // Codigo do produto do informante
			$linha .= str_replace(".", "", $this->valor_numerico($itinventario["saldo"], 3, 13));   // Quantidades do produto
			$linha .= str_replace(".", "", $this->valor_numerico($itinventario["custorep"], 2, 13));  // Valor do produto
			$linha .= "1";  // Codigo de posse das mercadorias
			$linha .= $this->valor_texto($cnpj, 14);  // CNPJ do proprietario
			$linha .= str_repeat(" ", 14); // Inscricao estadual
			$linha .= str_replace(".", "", $this->valor_texto($itinventario["uf"], 2));  // UF do proprietario
			$linha .= str_repeat(" ", 45);
			$this->escrever_linha($linha);
			$linha = "";
			$this->arr_produto_75[] = $itinventario["codproduto"];
			$this->total_rg_74++;
		}
	}

	/* ---------------------------------------------------------------------------------------------------------
	  Objetivo....: gerar informacoes do produtos movimentados no periodo informado para geracao do arquivo
	  --------------------------------------------------------------------------------------------------------- */

	function gerar_registro75(){
		setprogress(0, "Preparando produtos para o registro 75");

		$arr_codproduto = array_unique($this->arr_produto_75);
		if(count($arr_codproduto) === 0){
			return true;
		}
		$query = implode(" ", array(
			"SELECT produto.codproduto, COALESCE(ncm.codigoncm,'00000000') AS ncm, produto.descricao,",
			"  unidade.sigla,classfiscal.codcst,ipi.aliqipi,classfiscal.aliqicms,",
			"  classfiscal.aliqredicms, CASE tptribicms WHEN 'F' THEN produto.custorep ELSE 0 END AS base_cal",
			"FROM produto",
			"INNER JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf)",
			"LEFT JOIN ncm ON (produto.idncm = ncm.idncm)",
			"LEFT JOIN embalagem ON (produto.codembalcpa = embalagem.codembal)",
			"LEFT JOIN unidade   ON (embalagem.codunidade = unidade.codunidade)",
			"LEFT JOIN ipi ON (produto.codipi = ipi.codipi)",
			"WHERE produto.codproduto IN (".implode(" ,", $arr_codproduto).")"
		));
		$res = $this->con->query($query);
		$this->arr_notafiscal_75 = $res->fetchAll(2);

		$i = 1;
		$n = count($this->arr_notafiscal_75);
		foreach($this->arr_notafiscal_75 as $arr_notafiscal_75){
			setprogress(($i / $n * 100), "Escrevendo registro 75: {$i} de {$n}");
			$i++;

			$substituir = array("'");

			$linha = "75"; //tipo do registro
			$linha .= $this->anogeracao.$this->mesgeracao."01"; //data inicial do periodo
			$linha .= $this->anogeracao.$this->mesgeracao.month_last_day($this->anogeracao, $this->mesgeracao); //data fianl do periodo
			$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_75["codproduto"], 0, 14));   //codigo do produto
			$linha .= $this->valor_numerico(str_replace(".", "", $arr_notafiscal_75["ncm"]), 0, 8);  //ncm do produto
			$linha .= $this->valor_texto(str_replace($substituir, "", $arr_notafiscal_75["descricao"]), 53);  //descricao do produto
			$linha .= $this->valor_texto(str_replace(" ", "", $arr_notafiscal_75["sigla"]), 6);  //unidade comercializacao do produtos
			//$linha .= $arr_notafiscal_75["codcst"]; // Codigo CST
			$linha .= $this->valor_numerico($arr_notafiscal_75["aliqipi"], 2, 5);   //aliquota de ipi
			$linha .= $this->valor_numerico($arr_notafiscal_75["aliqicms"], 2, 4);  //aliquota de icms
			$linha .= $this->valor_numerico($arr_notafiscal_75["aliqredicms"], 2, 5);  //aliquota de reducao
			$linha .= $this->valor_numerico($arr_notafiscal_75["base_cal"], 2, 13);  // base de calculo do icms
			$this->escrever_linha($linha);

			$this->total_rg_75++;
		}
/*
		$idprodutos = array_unique($this->arr_produto_75);
		$i = 1;
		$n = count($idprodutos);
		foreach($idprodutos as $id){
			setprogress(($i / $n * 100), "Carregando registro 75: {$i} de {$n}");
			$i++;
			$query = "SELECT produto.codproduto, COALESCE(ncm.codigoncm,'00000000') AS ncm, produto.descricao, ";
			$query .= "unidade.sigla,classfiscal.codcst,ipi.aliqipi,classfiscal.aliqicms, ";
			$query .= "classfiscal.aliqredicms, CASE tptribicms WHEN 'F' THEN produto.custorep ELSE 0 END AS base_cal ";
			$query .= "FROM produto ";
			$query .= "INNER JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
			$query .= "LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
			$query .= "LEFT JOIN embalagem ON (produto.codembalcpa = embalagem.codembal) ";
			$query .= "LEFT JOIN unidade   ON (embalagem.codunidade = unidade.codunidade) ";
			$query .= "LEFT JOIN ipi ON (produto.codipi = ipi.codipi) ";
			$query .= "WHERE produto.codproduto =  ".$id." ";
			$this->arr_notafiscal_75 = array();
			$res = $this->con->query($query);
			$this->arr_notafiscal_75 = $res->fetchAll(2);
			$substituir = array("'");
			foreach($this->arr_notafiscal_75 as $arr_notafiscal_75){
				$linha = "75"; //tipo do registro
				$linha .= $this->anogeracao.$this->mesgeracao."01"; //data inicial do periodo
				$linha .= $this->anogeracao.$this->mesgeracao.month_last_day($this->anogeracao, $this->mesgeracao); //data fianl do periodo
				$linha .= str_replace(".", "", $this->valor_numerico($arr_notafiscal_75["codproduto"], 0, 14));   //codigo do produto
				$linha .= $this->valor_numerico(str_replace(".", "", $arr_notafiscal_75["ncm"]), 0, 8);  //ncm do produto
				$linha .= $this->valor_texto(str_replace($substituir, "", $arr_notafiscal_75["descricao"]), 53);  //descricao do produto
				$linha .= $this->valor_texto(str_replace(" ", "", $arr_notafiscal_75["sigla"]), 6);  //unidade comercializacao do produtos
				//$linha .= $arr_notafiscal_75["codcst"]; // Codigo CST
				$linha .= $this->valor_numerico($arr_notafiscal_75["aliqipi"], 2, 5);   //aliquota de ipi
				$linha .= $this->valor_numerico($arr_notafiscal_75["aliqicms"], 2, 4);  //aliquota de icms
				$linha .= $this->valor_numerico($arr_notafiscal_75["aliqredicms"], 2, 5);  //aliquota de reducao
				$linha .= $this->valor_numerico($arr_notafiscal_75["base_cal"], 2, 13);  // base de calculo do icms
				$this->escrever_linha($linha);
				$linha = "";
				$this->total_rg_75++;
			}
		}
*/
	}

	function gerar_registro90(){
		$linha = "90";
		$linha .= $this->valor_texto(removeformat($this->estabelecimento->getcpfcnpj()), 14); //cnpj do contribuinte
		$linha .= $this->valor_texto(removeformat($this->estabelecimento->getrgie()), 14);  //inscricao estadual do contribuinte
		if($this->total_rg_50 > 0){
			$linha .= "50".$this->str_zero($this->total_rg_50, 8);  //total d eregistros 50
		}
		if($this->total_rg_51 > 0){
			$linha .= "51".$this->str_zero($this->total_rg_51, 8);  //total d eregistros 51
		}
		if($this->total_rg_53 > 0){
			$linha .= "53".$this->str_zero($this->total_rg_53, 8);  //total d eregistros 53
		}
		if($this->total_rg_54 > 0){
			$linha .= "54".$this->str_zero($this->total_rg_54, 8);  //total d eregistros 54
		}
		if($this->total_rg_55 > 0){
			$linha .= "55".$this->str_zero($this->total_rg_55, 8);  //total d eregistros 55
		}
		if($this->total_rg_60 > 0){
			$linha .= "60".$this->str_zero($this->total_rg_60, 8);  //total d eregistros 60
		}
		if($this->total_rg_61 > 0){
			$linha .= "61".$this->str_zero($this->total_rg_61, 8);  //total d eregistros 61
		}
		if($this->total_rg_70 > 0){
			$linha .= "70".$this->str_zero($this->total_rg_70, 8);  //total de registros 70
		}
		if($this->total_rg_71 > 0){
			$linha .= "71".$this->str_zero($this->total_rg_71, 8);  //total de registros 70
		}
		if($this->total_rg_74 > 0){
			$linha .= "74".$this->str_zero($this->total_rg_74, 8);  //total d eregistros 74
		}
		if($this->total_rg_75 > 0){
			$linha .= "75".$this->str_zero($this->total_rg_75, 8);  //total d eregistros 75
		}
		$linha .= "99".$this->str_zero($this->total_rg_10 + $this->total_rg_11 + $this->total_rg_50 + $this->total_rg_51 + $this->total_rg_53 + $this->total_rg_54 + $this->total_rg_55 + $this->total_rg_60 + $this->total_rg_61 + $this->total_rg_70 + $this->total_rg_71 + $this->total_rg_74 + $this->total_rg_75 + $this->total_rg_90, 8);
		$linha .= str_repeat(" ", 125 - strlen($linha))."1";
		$this->bloco90[] = $linha;
		$this->escrever_bloco($this->bloco90);
	}

	// Faz download do arquivo
	public function download(){
		download($this->arquivo_nome);
	}

	function str_zero($numero, $tamanho){
		return str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
	}

	// Identifica o modelo do documento de acordo com a tabela 4.1.1
	private function modelo($notafiscal){
		$arr_operacao = array("VD", "TS", "DC", "DF");
		if(strlen($notafiscal["chavenfe"]) > 0 || in_array($notafiscal["operacao"], $arr_operacao)){ // Nota fiscal eletronica
			$modelo = "55";
		}else{ // Nota fiscal normal
			if(strlen(trim(removeformat($notafiscal["ie"]))) > 0){
				$modelo = "01";
			}else{
				$modelo = "55";
			}
		}
		return $modelo;
	}

	private function valor_data($data){
		$data = value_date($data);
		$data = convert_date($data, "Y-m-d", "Ymd");
		return $data;
	}

	private function valor_numerico($numero, $decimais, $tamanho){
		$numero = value_numeric($numero);
		$numero = number_format($numero, $decimais, "", "");
		$numero = substr($numero, 0, $tamanho);
		$numero = str_pad($numero, $tamanho, "0", STR_PAD_LEFT);
		return $numero;
	}

	private function valor_texto($texto, $tamanho){
		$texto = substr($texto, 0, $tamanho);
		$texto = str_pad($texto, $tamanho, " ", STR_PAD_RIGHT);
		return $texto;
	}

	private function valor_aliquota($valor, $trib){
		if($trib == "T" || $trib == "R"){

			$valores = explode(".", $valor);
			if(strlen($valores[1]) == 0){
				$posicao2 = "00";
			}else{
				$posicao2 = substr($valores[1], 0, 2);
			}

			if(strlen($valores[0]) == 1){
				$aliq = "0".$valores[0].$posicao2;
			}else{
				$aliq = $valores[0].$posicao2;
			}
		}elseif($trib == "F"){
			$aliq = "F   ";
		}elseif($trib == "I"){
			$aliq = "I   ";
		}elseif($trib == "N"){
			$aliq = "N   ";
		}else{
			$aliq = "0000";
		}
		return $aliq;
	}

	public function getarquivo_nome(){
		return $this->arquivo_nome;
	}

}