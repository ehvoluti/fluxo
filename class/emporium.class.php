<?php
require_once("../class/pdvvenda.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");

class Emporium{

    private $con;
    private $pdvconfig;
    private $pdvvenda;
    private $pdvfinalizador;
    private $arqtrib;
    private $delimitador = "|";
    private $arr_registro = array();
    private $arr_produto = array();
    private $arr_cliente = array();
    private $arr_codproduto = array();
    private $arr_codean = array();

    function __construct(){
        $this->limpar_dados();
    }

    function limpar_dados(){
        $this->pdvvenda = array();
        $this->pdvfinalizador = array();
        $this->arr_arquivo = array();
    }

    function setpdvconfig($pdvconfig){
        $this->pdvconfig = $pdvconfig;
        $this->con = $this->pdvconfig->getconnection();
    }

    function exportar_produto(){
        $this->con = new Connection();
        setprogress(25, "Carregando Produtos", TRUE);

        $query = " SELECT DISTINCT produtoestab.qtdatacado, produto.codproduto, produto.pesado, produto.coddepto, ";
        $query .= " (SELECT quantidade FROM embalagem WHERE codembal = produto.codembalvda) AS quantidadeembal, ";
        $query .= " produto.descricao, produto.descricaofiscal, unidade.sigla, produto.coddepto, ";
        $query .= " (SELECT infpdv FROM icmspdv WHERE icmspdv.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." AND classfiscal.tptribicms = icmspdv.tipoicms AND classfiscal.aliqicms = icmspdv.aliqicms LIMIT 1) AS infpdv, ";
        $query .= " classfiscal.aliqredicms, classfiscal.aliqicms, classfiscal.tptribicms, ";
        $query .= "	produtoestab.precoatc, produtoestab.precovrj, produtoestab.precoatcof, produtoestab.precovrjof, ";
        $query .= "	produtoestab.sldatual, unidade.sigla, produto.precovariavel, embalagem.quantidade, classfiscal.codcst, ";
        $query .= "	produto.foralinha, produto.codvasilhame, classfiscal.tptribicms, classfiscal.aliqicms, classfiscal.aliqredicms, ";
        $query .= " produtoestab.precovrjof, ";
        $query .= " (SELECT cpfcnpj FROM fornecedor INNER JOIN prodfornec ON (produto.codproduto = prodfornec.codproduto) ";
        $query .= " WHERE prodfornec.codfornec = fornecedor.codfornec LIMIT 1) AS cpfcnpj, ";
        $query .= " produtoestab.codestabelec, produtoestab.margemvrj, produtoestab.dtultcpa, produtoestab.dtultvda, ";
        $query .= " produtoestab.custotab, produto.multiplicado, ";
        $query .= " COALESCE(produto.aliqmedia,ncm.aliqmedia) AS aliqmedia, ";
		$query .= "	COALESCE(ibptestabelec.aliqnacionalfederal, produto.aliqmedia, ncm.aliqmedia) AS aliqmedianacional, ";
		$query .= "	COALESCE(ibptestabelec.aliqestadual, produto.aliqmedia, ncm.aliqmedia) AS aliqmediaestadual, ";
        $query .= " ncm.codigoncm, ";
        if($this->pdvconfig->getfrentecaixa()->getbalancaean() == "S"){
            $query .= "	(SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) AS codean ";
        }else{
            $query .= "	(CASE WHEN produto.pesado = 'S' THEN '' ELSE (SELECT codean FROM produtoean WHERE codproduto = produto.codproduto LIMIT 1) END) AS codean ";
        }
        $query .= " FROM produtoestab ";
        $query .= " INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
        $query .= " INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
        $query .= " INNER JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
        $query .= "	INNER JOIN estabelecimento ON (produtoestab.codestabelec = estabelecimento.codestabelec) ";
        $query .= "	INNER JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
        $query .= " LEFT JOIN ncm ON (produto.idncm = ncm.idncm) ";
		$query .= " LEFT JOIN ibptestabelec ON (replace(ncm.codigoncm,'.','') = replace(ibptestabelec.codigoncm,'.','') AND produtoestab.codestabelec = ibptestabelec.codestabelec) ";
        $query .= " WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec();
        $query .= "	AND produtoestab.disponivel = 'S' ";
		$query .= "	AND produto.gerapdv = 'S' ";
        if(param("ESTOQUE", "CARGAITEMCOMESTOQ", $this->con) == "S"){
            $query .= " AND produtoestab.sldatual > 0 ";
        }
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
        $this->arr_produto = $res->fetchAll(2);
        unset($res);

        $this->gerar_registro10();
        if(param("SISTEMA", "TIPOSERVIDOR") == "L"){
            $this->pdvconfig->file_create("EMPORIUM.TXT", array_unique($this->arr_registro));
            unset($this->arr_registro);
        }

        setprogress(50, "Carregando Estoques", TRUE);
        //$this->gerar_registro11();
        $this->gerar_registro2();
        $this->gerar_registro12();
        setprogress(75, "Carregando EAN's", TRUE);
        $this->gerar_registro1();
        //$this->gerar_registro13();
        setprogress(90, "Carregando Composicoes", TRUE);
        $this->gerar_registro14();
        //$this->gerar_registro15();
        //$this->gerar_registro16();
        //$this->gerar_registro19();

		$this->pdvconfig->atualizar_precopdv($this->arr_codproduto);
        unset($this->arr_codproduto);

        $this->pdvconfig->file_create("EMPORIUM.TXT", array_unique($this->arr_registro));
		return true;
    }

    function exportar_cliente($return = FALSE){
        setprogress(0, "Buscando clientes", TRUE);
        $this->con = new Connection();
        $estabelecimento = $this->pdvconfig->getestabelecimento();

        $query = "SELECT DISTINCT cl.tppessoa, cl.codcliente, cl.razaosocial, cl.nome, cl.email, cl.foneres, cl.dtinclusao, cpfcnpj ";
        $query .= "FROM cliente cl ";
        if(param("CADASTRO", "MIXCLIENTE", $this->con) == "S"){
            $query .= "INNER JOIN clienteestab ON (cl.codcliente = clienteestab.codcliente) ";
            $query .= "WHERE clienteestab.codestabelec = ".$estabelecimento->getcodestabelec();
        }
        $query .= "ORDER BY cl.codcliente ";

        $res = $this->con->query($query);
        $this->arr_cliente = $res->fetchAll(2);
        unset($this->arr_registro);
        unset($res);

        foreach($this->arr_cliente as $cliente){
            $linha = "0".$this->delimitador; // 0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "20".$this->delimitador; // Indentificao fixa do registro
            $linha .= $this->valor_texto(($cliente["tppessoa"] == "F" ? "1" : "2"), 2).$this->delimitador; // Tipo do documento
            $linha .= $this->valor_texto($cliente["codcliente"], 20).$this->delimitador; // Codigo interno do cliente
            $linha .= $this->valor_texto(($cliente["tppessoa"] == "F" ? "1" : "2"), 3).$this->delimitador; // Tipo do cliente
            $linha .= $this->valor_texto($cliente["razaosocial"], 60).$this->delimitador; // Razao social
            $linha .= $this->valor_texto($cliente["nome"], 60).$this->delimitador; // Nome do cliente
            $linha .= $this->valor_texto($cliente["email"], 50).$this->delimitador; // Email
            $linha .= $this->valor_texto($cliente["foneres"], 15).$this->delimitador; // Telefone
            $linha .= " ".$this->delimitador; // Celular
            $linha .= $this->valor_texto(convert_date($cliente["dtinclusao"], "Y-m-d", "dmY"), 8).$this->delimitador; // Data de cadastramento
            $linha .= $this->valor_texto(str_replace(array(".", "-", "/"), "", str_pad($cliente["cpfcnpj"], 25)), 25).$this->delimitador; // Cpf/cnpj do cliente
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //
            $linha .= " ".$this->delimitador; //

            $this->arr_registro[] = $linha;
        }
        $this->cliente_gerar_registro22();
        $this->cliente_gerar_registro23();

		$this->arr_registro = array_unique($this->arr_registro);

		if($return){
			return array($this->pdvconfig->file_create("EMPORIUM_CLIENTES.TXT", $this->arr_registro, "w+", TRUE));
		}else{
			$this->pdvconfig->file_create("EMPORIUM_CLIENTES.TXT", $this->arr_registro, "w+", FALSE);
			unset($this->arr_registro);
		}
		return true;
    }

    function cliente_gerar_registro22(){
        $estabelecimento = $this->pdvconfig->getestabelecimento();
        $query = "SELECT DISTINCT cl.tppessoa, cl.codcliente, cl.razaosocial, cl.nome, cl.email, cl.foneres, ";
        $query .= "cl.dtinclusao, cpfcnpj, cl.limite1 AS limite, (cl.limite1 - cl.debito1) AS saldo, cl.codstatus, sc.infostatus ";
        $query .= "FROM cliente cl ";
        $query .= "INNER JOIN statuscliente sc ON (cl.codstatus = sc.codstatus)";
        if(param("CADASTRO", "MIXCLIENTE", $this->con) == "S"){
            $query .= "INNER JOIN clienteestab ON (cl.codcliente = clienteestab.codcliente) ";
            $query .= "WHERE clienteestab.codestabelec = ".$estabelecimento->getcodestabelec();
        }
        $query .= "ORDER BY cl.codcliente ";

        $res = $this->con->query($query);
        $this->arr_cliente = $res->fetchAll(2);
        unset($res);

        foreach($this->arr_cliente as $cliente){
            $linha = "0".$this->delimitador; // 0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "22".$this->delimitador; // Indentificao fixa do registro
            $linha .= $this->valor_texto(($cliente["tppessoa"] == "F" ? "1" : "2"), 2).$this->delimitador; // Tipo do documento
            $linha .= $this->valor_texto($cliente["codcliente"], 20).$this->delimitador; // Codigo interno do cliente
            $linha .= $this->valor_texto(($cliente["tppessoa"] == "F" ? "1" : "2"), 3).$this->delimitador; // Tipo do cliente
            $linha .= (str_replace(array(".", "/", "-"), "", $cliente["cpfcnpj"])).$this->delimitador; // Cpf/cnpj do cliente
            $linha .= $this->valor_texto($cliente["infostatus"], 1).$this->delimitador;
            $linha .= $this->valor_numerico(0, 2, 11).$this->delimitador; // Saldo do cliente
            $linha .= $this->valor_numerico(0, 2, 11).$this->delimitador; // Limite do cliente
            $linha .= " ".$this->delimitador; // Total de pontos acumulados
            $linha .= " ".$this->delimitador; // Senha para o sku
            $linha .= " ".$this->delimitador; // Senha para o sku criptografada

            $this->arr_registro[] = $linha;
        }
        return true;
    }

    function cliente_gerar_registro23(){
        $estabelecimento = $this->pdvconfig->getestabelecimento();

        $query = "SELECT cl.tppessoa, cl.codcliente, cl.razaosocial, cl.nome, cl.email, cl.foneres, ";
        $query .= "cl.dtinclusao, cpfcnpj, cl.limite1 AS limite, (cl.limite1 - cl.debito1) AS saldo, cl.codstatus, sc.infostatus ";
        $query .= "FROM cliente cl ";
        $query .= "INNER JOIN statuscliente sc ON (cl.codstatus = sc.codstatus)";
        if(param("CADASTRO", "MIXCLIENTE", $this->con) == "S"){
            $query .= "INNER JOIN clienteestab ON (cl.codcliente = clienteestab.codcliente) ";
            $query .= "WHERE clienteestab.codestabelec = ".$estabelecimento->getcodestabelec();
        }
        $query .= "ORDER BY cl.codcliente ";

        $res = $this->con->query($query);
        $this->arr_cliente = $res->fetchAll(2);
        unset($res);

        foreach($this->arr_cliente as $cliente){
            $linha = "0".$this->delimitador; // 0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "23".$this->delimitador; // Indentificao fixa do registro
            $linha .= $this->valor_texto(($cliente["tppessoa"] == "F" ? "1" : "2"), 2).$this->delimitador; // Tipo do documento
            $linha .= $this->valor_texto($cliente["codcliente"], 20).$this->delimitador; // Codigo interno do cliente
            $linha .= $this->valor_numerico($cliente["saldo"], 2, 11).$this->delimitador; // Saldo do cliente
            $linha .= $this->valor_numerico($cliente["limite"], 2, 11).$this->delimitador; // Limite do cliente
            $linha .= " ".$this->delimitador; // Total de pontos acumulados
            $linha .= ($cliente["infostatus"]).$this->delimitador; // Status
            $linha .= " ".$this->delimitador; // Comentarios
            $linha .= " ".$this->delimitador; // Mensagem
            $linha .= " ".$this->delimitador; // Flag para mensagem
            $linha .= " ".$this->delimitador; // Flag para desconto
            $linha .= " ".$this->delimitador; // Saldo de cheques emitidos
            $linha .= " ".$this->delimitador; // Limite de numeros de cheques
            $linha .= " ".$this->delimitador; // Data da ultima emissão de cheque
            $linha .= ($cliente["codstatus"] == 1 ? "0" : "1").$this->delimitador; // Bloqueio da emissao de notafiscal
            $linha .= " ".$this->delimitador; // Data do ultimo desconto
            $linha .= " ".$this->delimitador; // Saldo de identificacao efetuadas
            $linha .= " ".$this->delimitador; // Limite de numero de identificacao
            $linha .= " ".$this->delimitador; // Data de ultima identificacaoes

            $this->arr_registro[] = $linha;
        }
        return true;
    }

    function gerar_registro1(){
        $query = "SELECT DISTINCT produto.codproduto,  produtoean.codean ";
        $query .= "FROM produto ";
        $query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto)";
        $query .= "WHERE produto.codproduto IN (".implode(",", $this->arr_codproduto).")";
        if($this->pdvconfig->getfrentecaixa()->getbalancaean() == "N"){
            $query .= "	AND produto.pesado = 'N' ";
        }

        $res = $this->con->query($query);
        $this->arr_codean = $res->fetchAll(2);

        foreach($this->arr_codean as $codean){
            $linha = "0".$this->delimitador; // 0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "01".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($codean["codean"], 0, 14).$this->delimitador; // Codigo EAN do produto
            $linha .= $this->valor_numerico($codean["codproduto"], 0, 10).$this->delimitador; // Codigo PLU interno do produto

            $this->arr_registro[] = $linha;
        }
        unset($this->arr_codean);
    }

    function gerar_registro2(){
        $query = "SELECT DISTINCT produto.coddepto, departamento.nome ";
        $query .= "FROM produto ";
        $query .= "INNER JOIN departamento ON (produto.coddepto = departamento.coddepto) ";
        $query .= "WHERE produto.codproduto IN (".implode(",", $this->arr_codproduto).")";

        $res = $this->con->query($query);
        $this->arr_coddepto = $res->fetchAll(2);

        foreach($this->arr_coddepto as $coddepto){
            $linha = "0".$this->delimitador; // 0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "02".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $coddepto["coddepto"].$this->delimitador; // Identificacao do departamento
            $linha .= $coddepto["coddepto"].$this->delimitador; // Identificacao do departamento base
            $linha .= "0".$this->delimitador; // flag que premite o departamento possuir itens associados
            $linha .= $this->valor_texto($coddepto["nome"], 50); // Descricao do departamento

            $this->arr_registro[] = $linha;
        }
        unset($this->arr_coddepto);
    }

    function gerar_registro10(){
        foreach($this->arr_produto as $produto){
            // Verifica a tributacao no cadastro de PDV
            if(strlen($produto["infpdv"]) == 0){
                echo messagebox("error", "", "N&atilde;o encontrado informa&ccedil;&otilde;es tributarias para o PDV do produto <b>".$produto["codproduto"]."</b>: \n\n <b>Tipo de Tributa&ccedil;&atilde;o</b> = ".$produto["tptribicms"]."\n<b>Aliquota </b> = ".$produto["aliqicms"]."\n <b>Aliquota de Redu&ccedil;&atilde;o</b> = ".$produto["aliqredicms"]."\n\n <a onclick=\"openProgram('InfTribPDV')\">Clique aqui</a> para abrir o cadastro de tributa&ccedil;&atilde;o do PDV.");
                die();
            }

            $this->arr_codproduto[] = $produto["codproduto"];
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "10".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($produto["codproduto"], 0, 10).$this->delimitador; // Codigo interno que funciona com SKU principal
            $linha .= $this->valor_numerico(0, 0, 10).$this->delimitador; // base_plu_key Codigo interno do item base
            $linha .= $this->valor_numerico(0, 0, 10).$this->delimitador; // link_plu_key Codigo interno do produto associado
            $linha .= $this->valor_numerico(0, 0, 5).$this->delimitador; // Se estiver zerado sera mandado a PLU default
            $linha .= $this->valor_numerico(removeformat($produto["cpfcnpj"]), 0, 25).$this->delimitador; // CNPJ/CPF do fabricante
            $linha .= $this->valor_texto($produto["descricao"], 22).$this->delimitador; // Descricao do produto para o PDV
            $linha .= $this->valor_texto($produto["descricaofiscal"], 50).$this->delimitador; // Descricao tecnica do produto
            $linha .= $this->valor_texto($produto["descricaofiscal"], 50).$this->delimitador; // Descricao comercial do produto
            $linha .= $this->valor_texto(" ", 255).$this->delimitador; // Imagem do item
            $linha .= $this->valor_texto($produto["infpdv"], 4).$this->delimitador; // Chave para taxa
            $linha .= $this->valor_numerico(2, 0, 2).$this->delimitador; // Quantidade de decimais que aparecera na tela
            $linha .= $this->valor_texto($produto["sigla"], 4).$this->delimitador; // Tipo de unidade (KG, LT, UN)
            $linha .= $this->valor_numerico(2, 0, 2).$this->delimitador; // Quantidade de decimais calculos e precos no display
            $linha .= $this->valor_numerico(0, 0, 10).$this->delimitador; // Acumuladores
            $linha .= $produto["coddepto"].$this->delimitador; // Codigo do departamento
            $linha .= $this->valor_texto(" ", 80).$this->delimitador; // Display de mensagem apos a venda do produto
            $linha .= $this->valor_numerico(0, 0, 16).$this->delimitador; // Numero da sequencia de entrada
            $linha .= $this->valor_numerico(0, 0, 16).$this->delimitador; // Chave da tabela tara
            $linha .= "0".$this->delimitador; // (0 ou 1) Habilita venda de itens complementares
            $linha .= "0".$this->delimitador; // (0 ou 1) Tem ligacao com deposito
            $linha .= "0".$this->delimitador; // (0 ou 1) Tem ligacao com produto
            $linha .= ($produto["precovariavel"] == "S" ? "1" : "0").$this->delimitador; // (0 ou 1) Requer entrada de preco manual
            $linha .= ($produto["multiplicado"] == "S" ? "0" : "1").$this->delimitador; // (0 ou 1) Desabilita entrada de quantidade
            $linha .= "0".$this->delimitador; // (0 ou 1) Requer entrada de quantidade
            $linha .= "0".$this->delimitador; // (0 ou 1) Desabilita entrada de deciamais
            $linha .= "0".$this->delimitador; // (0 ou 1) Requer entrada de decimais
            $linha .= "0".$this->delimitador; // (0 ou 1) Requer identificao do cliente
            $linha .= "0".$this->delimitador; // (0 ou 1) Desabilita tecla de repeticao
            $linha .= "0".$this->delimitador; // (0 ou 1) Produto pesado no checkout
            $linha .= "0".$this->delimitador; // (0 ou 1) Controle de totalizacao especial
            $linha .= "0".$this->delimitador; // (0 ou 1) Codigo de PLU para deposito
            $linha .= "0".$this->delimitador; // (0 ou 1) Produto nao e mercadoria
            $linha .= "0".$this->delimitador; // (0 ou 1) Devolucao nao permitida do item
            $linha .= "0".$this->delimitador; // (0 ou 1) Devolucao nao permitida em numerico
            $linha .= "0".$this->delimitador; // (0 ou 1) Item nao permite desconto
            $linha .= "0".$this->delimitador; // (0 ou 1) Item tem desconto
            $linha .= "0".$this->delimitador; // (0 ou 1) Produto nao disponivel para venda
            $linha .= "0".$this->delimitador; // (0 ou 1) Preco negativo
            $linha .= "0".$this->delimitador; // (0 ou 1) Requer identificacao do vendedor
            $linha .= "0".$this->delimitador; // (0 ou 1) Produto e kit
            $linha .= "0".$this->delimitador; // (0 ou 1) Enviar para balanca
            $linha .= "0".$this->delimitador; // (0 ou 1) Entrega em domicilio
            $linha .= "0".$this->delimitador; // (0 ou 1) Requer autorizacao
            $linha .= "0".$this->delimitador; // (0 ou 1) Produto pesado com etiqueta
            $linha .= "0".$this->delimitador; // (0 ou 1) Imprimir etiqueta de gondola
            $linha .= "0".$this->delimitador; // (0 - nao, 1 - para adicao, 2 - para exclusao, 3 - Ambos) E produto complementa
            $linha .= "0".$this->delimitador; // (0 ou 1) E PLU Base
            $linha .= "1".$this->delimitador; // (0 ou 1) Preco administrado pela loja
            $linha .= "0".$this->delimitador; // (0 ou 1) Kit reverso
            $linha .= "0".$this->delimitador; // (0 ou 1) Checar quantidade em estoque
            $linha .= $this->valor_numerico(0, 0, 5).$this->delimitador; // Dias de validade
            $linha .= $this->valor_numerico(removeformat($produto["codigoncm"]), 0, 12).$this->delimitador; // NCM
            $linha .= $this->valor_numerico(0, 0, 2).$this->delimitador; // PIS/COFINS
            $linha .= $this->valor_numerico(0, 3, 9).$this->delimitador; // Quantidade
            $linha .= $this->valor_numerico(0, 3, 9).$this->delimitador; // Quantidade Minima
            $linha .= $this->valor_numerico(0, 3, 9).$this->delimitador; // Quantidade Maxima
            $linha .= $this->valor_texto(" ", 20).$this->delimitador; // Classificacao de Produtos
            $linha .= $this->valor_numerico(0, 0, 2).$this->delimitador; // Origem do produto
            $linha .= $this->valor_numerico(0, 0, 20).$this->delimitador; // Grupo de produtos
            $linha .= $this->valor_numerico($produto["quantidadeembal"], 0, 6).$this->delimitador; // Quantidade na embalagem
            $linha .= $this->valor_numerico(0, 3, 9).$this->delimitador; // Quantidade atacado
            $linha .= $this->valor_numerico(0, 0, 3).$this->delimitador; // Tipo de entrega
            $linha .= $this->valor_texto(" ", 255).$this->delimitador; // Mensagem referente ao produto
            $linha .= $this->valor_texto(" ", 4).$this->delimitador; // Tipo de unidade padrao
            $linha .= $this->valor_numerico($produto["quantidadeembal"], 3, 9).$this->delimitador; // Quantidade de unidade padrao
            $linha .= "0".$this->delimitador; // E receita
            $linha .= "0".$this->delimitador; // E insumo
            $linha .= $this->valor_numerico(0, 0, 2).$this->delimitador; // Vender escaneado
            $linha .= $this->valor_numerico(0, 3, 7).$this->delimitador; // Peso bruto do produto
            $linha .= $this->valor_numerico(0, 3, 7).$this->delimitador; // Peso liquido do produto
            $linha .= $this->valor_numerico($produto["aliqmedianacional"], 3, 7).$this->delimitador; // Percentual dos impostos no produto
			if(strlen($this->pdvconfig->getfrentecaixa()->getversao()) > 0){
				$linha .= $this->valor_texto(" ", 10).$this->delimitador; // Codigo sefaz para combustivel
				$linha .= $this->valor_texto(" ", 10).$this->delimitador; // Codigo anp para combustivel
				$linha .= $this->valor_texto(" ", 4).$this->delimitador; // Legenda do pis pis_pos_id
				$linha .= $this->valor_texto(" ", 4).$this->delimitador; // Legenda do cofins cofins_pos_id
				$linha .= $this->valor_numerico(0, 7, 3).$this->delimitador; // Peso do item para uso no Self Checkout
				$linha .= $this->valor_numerico(0, 2, 0).$this->delimitador; // Passagem do produto no Self Checkout
				$linha .= $this->valor_numerico($produto["aliqmediaestadual"], 3, 7).$this->delimitador; // Percentual total de impostos total_tax_01
				$linha .= $this->valor_numerico(0, 7, 3).$this->delimitador; // Percentual total de impostos total_tax_02
			}
            $this->arr_registro[] = $linha;
        }
    }

    function gerar_registro11(){
        foreach($this->arr_produto as $produto){
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "11".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($produto["codestabelec"], 0, 10).$this->delimitador; // Chave da loja
            $linha .= $this->valor_numerico($produto["codproduto"], 0, 10).$this->delimitador; // ID
            $linha .= $this->valor_numerico($produto["margemvrj"], 2, 4).$this->delimitador; // Margem de lucro
            $linha .= $this->valor_numerico($produto["sldatual"], 3, 15).$this->delimitador; // Quantidade em estoque
            $linha .= $this->valor_texto("", 8).$this->delimitador; // Data de termino do estoque
            $linha .= $this->valor_data($produto["dtultcpa"]).$this->delimitador; // Data da ultima entrada
            $linha .= $this->valor_data($produto["dtultvda"]).$this->delimitador; // Data da ultima saida
            $linha .= $this->valor_numerico($produto["custotab"], 3, 15).$this->delimitador; // Custo do produto
            $linha .= $this->valor_data("").$this->delimitador; // Data da validade do custo do produto
            $linha = str_replace("  ", " ", $linha);
            $this->arr_registro[] = $linha;
        }
    }

    function gerar_registro12(){
        foreach($this->arr_produto as $seq=> $produto){
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "12".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($produto["codestabelec"], 0, 10).$this->delimitador; // Chave da loja
            $linha .= $this->valor_numerico($produto["codproduto"], 0, 10).$this->delimitador; // ID
            $linha .= $this->valor_numerico($produto["precovrjof"] > 0 ? $produto["precovrjof"] : $produto["precovrj"], 3, 18).$this->delimitador; // Preco de venda
            $linha .= $this->valor_data("").$this->delimitador; // Data do inicio da validade do preco de venda
            $linha .= $this->valor_texto("", 6).$this->delimitador;
            $linha .= "1".$this->delimitador; // Tipo do preco
            $linha .= ($produto["precovrjof"] > 0 ? "0" : "1").$this->delimitador; // Determina se e uma oferta
            $linha .= $this->valor_numerico(0, 0, 5).$this->delimitador; // Codigo da promocao
            $linha .= $this->valor_numerico(0, 3, 15).$this->delimitador; // Pontos atribuidos na venda
            $linha .= $this->valor_numerico($produto["sldatual"], 3, 15).$this->delimitador; // Quantidade
            $linha .= $this->valor_numerico(0, 3, 9).$this->delimitador; // Juros
            $linha .= $this->valor_numerico($seq, 0, 3).$this->delimitador; // Sequencial de preco
            $this->arr_registro[] = $linha;
        }

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
        $query .= "	AND composicao_a.explosaoauto = 'S' ";
        $query .= "	AND produtoestab_a.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
        if($this->pdvconfig->produto_parcial()){
            $query .= "	AND composicao_a.codproduto IN (SELECT codproduto FROM produto WHERE datalog >='".date("Y/m/d")."') ";
        }
        $query .= "ORDER BY composicao_a.codproduto, itcomposicao_a.quantidade DESC ";

        $res = $this->con->query($query);
        $arr = $res->fetchAll(2);

        $codprodutopai = 0;
        $contador = 1;
        foreach($arr AS $i=> $row){
            $row["precoatc_fn"] = ($row["precoatc_pn"] * $row["precoatc_fa"]) / $row["precoatc_pa"];
            $row["precovrj_fn"] = ($row["precovrj_pn"] * $row["precovrj_fa"]) / $row["precovrj_pa"];
            $row["precoatcof_fn"] = ($row["precovrjof_pa"]+0) == 0 ? 0 :($row["precoatcof_pn"] * $row["precoatcof_fa"]) / $row["precoatcof_pa"];
            $row["precovrjof_fn"] = ($row["precovrjof_pa"]+0) == 0 ? 0 :($row["precovrjof_pn"] * $row["precovrjof_fa"]) / $row["precovrjof_pa"];
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
                $contador++;
            }
            $row["precovrj_fn"] = $row["precovrj_fn"];

            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "12".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($this->pdvconfig->getestabelecimento()->getcodestabelec(), 0, 10).$this->delimitador; // Chave da loja
            $linha .= $this->valor_numerico($row["codprodutofilho"], 0, 10).$this->delimitador; // ID
            $linha .= $this->valor_numerico($row["precovrj_fn"], 3, 18).$this->delimitador; // Preco de venda
            $linha .= $this->valor_data("").$this->delimitador; // Data do inicio da validade do preco de venda
            $linha .= $this->valor_texto("", 6).$this->delimitador;
//            $linha .= "1".$this->delimitador; // Tipo do preco
            $linha .= $contador.$this->delimitador; // Tipo do preco
            $linha .= "0".$this->delimitador; // Determina se e uma oferta
            $linha .= $this->valor_numerico(0, 0, 5).$this->delimitador; // Codigo da promocao
            $linha .= $this->valor_numerico(0, 3, 15).$this->delimitador; // Pontos atribuidos na venda
            $linha .= $this->valor_numerico($row["quantidade"], 3, 15).$this->delimitador; // Quantidade
            $linha .= $this->valor_numerico(0, 3, 9).$this->delimitador; // Juros
            $linha .= $this->valor_numerico($i, 0, 3).$this->delimitador; // Sequencial de preco
            $this->arr_registro[] = $linha;
        }
    }

    function gerar_registro13(){
        foreach($this->arr_produto as $produto){
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "13".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico(0, 0, 14).$this->delimitador; // codigo ean do produto
            $linha .= $this->valor_numerico($produto["codestabelec"], 0, 10).$this->delimitador; // Chave da loja
            $linha .= $this->valor_numerico($produto["codproduto"], 0, 10).$this->delimitador; // Codigo interno do produto PLU
            $this->arr_registro[] = $linha;
        }
    }

    function gerar_registro14(){
        /* $query  = "SELECT DISTINCT produtoestab.codproduto, composicao.codcomposicao, ";
          $query .= "itcomposicao.codproduto AS codprodutofilho, itcomposicao.quantidade ";
          $query .= "FROM produtoestab ";
          $query .= "INNER JOIN composicao ON (produtoestab.codproduto = composicao.codproduto AND composicao.tipo = 'V' AND composicao.explosaoauto = 'S' ) ";
          $query .= "INNER JOIN itcomposicao ON (composicao.codcomposicao = itcomposicao.codcomposicao) ";
          $query .= "WHERE produtoestab.codproduto IN (".implode(",",$this->arr_codproduto).") ";
          $query .= "AND composicao.explosaoauto = 'S' ";

          $res = $this->con->query($query);
          $arr_composicao = $res->fetchAll(2);
         */

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
        $query .= "	AND composicao_a.explosaoauto = 'S' ";
        $query .= "	AND produtoestab_a.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
        if($this->pdvconfig->produto_parcial()){
            $query .= "	AND composicao_a.codproduto IN (SELECT codproduto FROM produto WHERE datalog >='".date("Y/m/d")."') ";
        }
        $query .= "ORDER BY composicao_a.codproduto, itcomposicao_a.quantidade DESC ";

        $res = $this->con->query($query);
        $arr = $res->fetchAll(2);
        $codprodutopai = 0;
        $contador = 1;
        foreach($arr as $row){
            if($row["codprodutopai"] != $codprodutopai){
                $contador++;
                $codprodutopai = $row["codprodutopai"];
            }
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "14".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($row["codprodutopai"], 0, 14).$this->delimitador; // Codigo interno do produto kit
            $linha .= $this->valor_numerico($row["codprodutofilho"], 0, 14).$this->delimitador; // Codigo interno do componente do kit
            $linha .= $this->valor_numerico($row["quantidade"], 3, 9).$this->delimitador; // Quantidade do componente no kit
            $linha .= $contador.$this->delimitador; // Nivel de preco do componete no kit
            $this->arr_registro[] = $linha;
        }


        /* foreach($arr_composicao as $composicao){
          $linha  = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
          $linha .= "14".$this->delimitador; // Indetificacao do registro fixo
          $linha .= $this->valor_numerico($composicao["codproduto"],0,14).$this->delimitador; // Codigo interno do produto kit
          $linha .= $this->valor_numerico($composicao["codprodutofilho"],0,14).$this->delimitador; // Codigo interno do componente do kit
          $linha .= $this->valor_numerico($composicao["quantidade"],3,9).$this->delimitador; // Quantidade do componente no kit
          $linha .= $this->valor_numerico(0,0,3).$this->delimitador; // Nivel de preco do componete no kit
          $this->arr_registro[] = $linha;
          } */
    }

    function gerar_registro15(){
        foreach($this->arr_produto as $produto){
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "15".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($produto["codestabelec"], 0, 10).$this->delimitador; // Chave da loja
            $linha .= $this->valor_numerico($produto["codproduto"], 0, 10).$this->delimitador; // ID
            $linha .= $this->valor_numerico(1, 0, 4).$this->delimitador; // Tipo do preco
            $linha .= $this->valor_numerico(1, 0, 4).$this->delimitador; // Degrau do preco
            $linha .= $this->valor_numerico($produto["precovrj"], 0, 10).$this->delimitador; // Preco de venda
            $linha .= $this->valor_data("").$this->delimitador; // Data da validade do preco do produto
            $linha .= $this->valor_texto("", 6).$this->delimitador; // Hora de inicioda validade dos preco de vena
            $linha .= $this->valor_numerico(0, 0, 4).$this->delimitador; // Tipo do preco
            $linha .= $this->valor_numerico(0, 0, 4).$this->delimitador; // Degrau do preco
            $linha .= $this->valor_numerico(0, 0, 4).$this->delimitador; // Uso futuro
            $this->arr_registro[] = $linha;
        }
    }

    function gerar_registro16(){
        foreach($this->arr_produto as $produto){
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "16".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($produto["codestabelec"], 0, 10).$this->delimitador; // Chave da loja
            $linha .= $this->valor_numerico($produto["codproduto"], 0, 10).$this->delimitador; // ID
            $linha .= $this->valor_numerico(1, 0, 4).$this->delimitador; // Tipo de etiqueta diferente de 0
            $linha .= $this->valor_texto("", 30).$this->delimitador; // Determina o endereco
            $linha .= $this->valor_numerico(0, 0, 4).$this->delimitador; // Quantidade
            $this->arr_registro[] = $linha;
        }
    }

    function gerar_registro19(){
        foreach($this->arr_produto as $produto){
            $linha = "0".$this->delimitador; //0 Inclusao ou alteracao de registro, 1 Exclusao
            $linha .= "19".$this->delimitador; // Indetificacao do registro fixo
            $linha .= $this->valor_numerico($produto["codproduto"], 0, 10).$this->delimitador; // Codigo interno do produto
            $linha .= ""; //codigo interno do componente pack
            $linha .= $this->valor_numerico($produto["codestabelec"], 0, 10).$this->delimitador; // Chave da loja
            $this->arr_registro[] = $linha;
        }
    }

    function importar_venda($dir_name, $arquivar = TRUE){
        $this->con = new Connection();

        $this->limpar_dados();
        // Tenta abrir o diretorio dos arquivos
        if(!($dir = opendir($dir_name))){
            $_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>".$this->path;
            return FALSE;
        }

        // Acha o arquivo gerado pelo emporium
        $i = 1;
        while($file = readdir($dir)){
            if((strcasecmp(substr($file, 0, 6), "expdet") == 0) && (intval(substr($file, 17, 4)) == $this->pdvconfig->getestabelecimento()->getcodestabelec())){
                $file_name = $file;
                $dtmovto = substr($file, 7, 8);
                $_SESSION["dtmovto_emporium"] = $dtmovto;
                break;
            }
        }

        if(strlen($file) == 0){
            $_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar nenhum arquivo.<br>".$this->path;
            return FALSE;
        }

        // Percorre o arquivo
        setprogress(25, "Processando arquivo de venda");
        $linhas = array();
        $file = fopen($dir_name.$file_name, "r");
        while(!feof($file)){
            $linha = fgets($file, 4096);
            $linhas[] = $linha;
        }
        fclose($file);

        $this->arr_arquivo[] = $dir_name.$file_name;

        // Busca os itens cancelados
        $arr_cupomcancelado = array();

        foreach($linhas as $i=> $linha){
            $arr_leiturapdv = explode("|", $linha);
            if($arr_leiturapdv[1] == "01"){
                if($arr_leiturapdv[7] == 1){
                    $arr_cupomcancelado[] = trim($arr_leiturapdv[4]);
                    $arr_itcupomcancelado[] = trim($arr_leiturapdv[4])."|".$arr_leiturapdv[8]."|".$arr_leiturapdv[12];
                }
            }
        }

        // Percorre todas as linhas
        $_continue_00 = FALSE;
        foreach($linhas as $i=> $linha){
			$arr_leiturapdv = explode("|", $linha);
			if($arr_leiturapdv[1] == "00" && !in_array(trim($arr_leiturapdv[8]), array("0", "10", "59"))){
				$_continue_00 = TRUE;
				continue;
			}

            if(($_continue_00 && $arr_leiturapdv[1] != "00")){
                continue;
            }else{
                $_continue_00 = FALSE;
            }
            switch($arr_leiturapdv[1]){
                // Abertura de cupom
                case "00":
                    $cupom = $arr_leiturapdv[4];
                    $datahora = $arr_leiturapdv[5];
                    $tem_cupomcancelado = "N";
                    $caixa = $arr_leiturapdv[22];
                    $finalizado = FALSE;

                    if(count($arr_pdvfinalizador) > 0){
                        foreach($arr_pdvfinalizador as $pdvfinalizador){
                            $this->pdvfinalizador[] = $pdvfinalizador;
                        }
                    }
                    $arr_pdvfinalizador = array();


                    if($arr_leiturapdv[7] == "1"){
                        $pdvvenda = end($this->pdvvenda);
						$pdvvenda->setstatus("C");

						$pdvfinalizador = end($this->pdvfinalizador);
						$pdvfinalizador->setstatus("C");

                        $_continue_00 = TRUE;
                        break;
                    }

                    if($arr_leiturapdv[8] == " 10"){
                        $_continue_00 = TRUE;
                        break;
                    }

                    $pdvvenda = new PdvVenda;

                    if($arr_leiturapdv[6] == "1"){
                        $tem_cupomcancelado = "S";
                        $pdvvenda->setstatus("C");
                    }else{
                        $pdvvenda->setstatus("A");
                    }
					$chavecfe = trim($arr_leiturapdv[26]);
					if(strlen($chavecfe) > 0){
						$chavecfe = $chavecfe;
						$seqecf = substr($chavecfe,31,6);
					}else{
						$seqecf = $cupom;
					}

                    $pdvvenda->setcupom(trim($cupom));
                    $pdvvenda->setseqecf(trim($seqecf));
                    $pdvvenda->setcaixa($caixa);
                    $pdvvenda->setnumeroecf($caixa);
                    $pdvvenda->setdata(substr($arr_leiturapdv[5], 0, 4)."-".substr($arr_leiturapdv[5], 4, 2)."-".substr($arr_leiturapdv[5], 6, 2));
                    $pdvvenda->sethora(substr($arr_leiturapdv[5], 8, 2).":".substr($arr_leiturapdv[5], 10, 2).":".substr($arr_leiturapdv[5], 12, 2));
					//$pdvvenda->setcodfunc(trim($arr_leiturapdv[13]));
                    //$pdvvenda->setcodorcamento($arr_leiturapdv[23]);
					$pdvvenda->setchavecfe(trim($chavecfe));

                    $tem_itcupomcancelado = "N";

                    foreach($arr_cupomcancelado AS $cupomcancelado){
                        if($cupomcancelado == trim($cupom)){
                            $tem_itcupomcancelado = "S";
                            break;
                        }
                    }

                    $this->pdvvenda[] = $pdvvenda;
                    break;

                // Itens do cupom
                case "01":
                    $pdvitem = new PdvItem();

                    // Busca cupom com item cancelado
                    if($tem_itcupomcancelado == "S"){
                        foreach($arr_itcupomcancelado AS $i=> $_itcupomcancelado){
                            $itcupomcancelado = explode("|", $_itcupomcancelado);
                            if($itcupomcancelado["0"] == trim($arr_leiturapdv[4]) &&
                                    $itcupomcancelado["1"] == ($arr_leiturapdv[8]) &&
                                    $itcupomcancelado["2"] == ($arr_leiturapdv[12]) &&
                                    $arr_leiturapdv[7] == 0
                            ){
								unset($arr_itcupomcancelado[$i]);

                                break 2;
                            }
                        }
                    }

                    if($arr_leiturapdv[7] == 1 || $tem_cupomcancelado == "S"){
                        $pdvitem->setstatus("C");
                    }else{
                        $pdvitem->setstatus("A");
                    }

                    // Grava a venda do item
					if(strlen($arr_leiturapdv[8]) > 8){
						$produtoean = objectbytable("produtoean",$arr_leiturapdv[8],$this->con);
						if(strlen($produtoean->getcodproduto()) >0){
							$arr_leiturapdv[8] = $produtoean->getcodproduto();
						}
					}

                    $pdvitem->setcodproduto(trim($arr_leiturapdv[8]));
                    $pdvitem->setquantidade($arr_leiturapdv[10] / 1000);
                    $pdvitem->setpreco($arr_leiturapdv[11] / 1000);
                    $pdvitem->setdesconto($arr_leiturapdv[16] / 1000);
                    $pdvitem->setacrescimo($arr_leiturapdv[17] / 1000);
                    $pdvitem->settotal(($arr_leiturapdv[12]) / 1000);
                    $pdvitem->settptribicms(substr($arr_leiturapdv[21], 0, 1));
                    $pdvitem->setaliqicms(intval(substr($arr_leiturapdv[22], 1, 2)));

                    $pdvvenda = end($this->pdvvenda);
                    $pdvvenda->pdvitem[] = $pdvitem;
                    break;

                // Finalizadora de venda
                case "09":
                    if($arr_leiturapdv[10] == "+"){
                        $valortotal = 0;
                        $pdvfinalizador = new PdvFinalizador();
                        $datahora = $arr_leiturapdv[5];
                        $finalizado = TRUE;
                        $valortotal = ($valortotal + $arr_leiturapdv[8]);
                        $pdvfinalizador->setstatus("A");
                        $pdvfinalizador->setcupom($arr_leiturapdv[4]);
                        $pdvfinalizador->setcaixa($caixa);
                        $data = substr($arr_leiturapdv[5], 0, 4)."-".substr($arr_leiturapdv[5], 4, 2)."-".substr($arr_leiturapdv[5], 6, 2);
                        $pdvfinalizador->setdata($data);
                        $pdvfinalizador->sethora(substr($arr_leiturapdv[5], 8, 2).":".substr($arr_leiturapdv[5], 10, 2).":".substr($arr_leiturapdv[5], 12, 2));
                        $pdvfinalizador->setcodfinaliz(str_replace(" ", "0", $arr_leiturapdv[7]));
                        $pdvfinalizador->setvalortotal($valortotal / 1000);
                        $pdvfinalizador->setdatavencto($data);

                        $arr_pdvfinalizador[trim($arr_leiturapdv[6])] = $pdvfinalizador;
                    }if($arr_leiturapdv[10] == "-"){
                        $valortotal = ($valortotal - $arr_leiturapdv[8]);
                        $pdvfinalizador->setvalortotal($valortotal / 1000);
                        $arr_pdvfinalizador[count($arr_pdvfinalizador) - 1] = $pdvfinalizador;
                    }

//					$this->pdvfinalizador[] = $pdvfinalizador;
                    break;

                // CNPJ/CPF do cliente
                case "10":
                    if(!$finalizado){
                        continue;
                    }
                    $pdvfinalizador = $arr_pdvfinalizador[trim($arr_leiturapdv[6])];
                    //$pdvfinalizador = end($this->pdvfinalizador);
                    if((strpos($arr_leiturapdv[8], "Cliente") !== FALSE) || (strpos($arr_leiturapdv[8], "Documento") !== FALSE) || (strpos($arr_leiturapdv[8], "CNPJ/CPF") !== FALSE)){
                        $result = $this->con->query("SELECT codcliente,cpfcnpj FROM cliente WHERE replace(replace(replace(cpfcnpj,'-',''),'.',''),'/','') = '".trim($arr_leiturapdv[9])."' LIMIT 1 ");
                        $clientefinaliz = $result->fetch(PDO::FETCH_ASSOC);

                        $pdvvenda = end($this->pdvvenda);
                        $pdvvenda->setcpfcnpj($clientefinaliz["cpfcnpj"]);
                        if(strlen(trim($clientefinaliz["codcliente"])) > 0){ // Verifica se existe o cliente
                            $pdvfinalizador->setcodcliente($clientefinaliz["codcliente"]);
                        }
                    }

                    if((strpos($arr_leiturapdv[8], "CNPJ/CPF") !== FALSE)){
                        $result = $this->con->query("SELECT codcliente,cpfcnpj FROM cliente WHERE replace(replace(replace(cpfcnpj,'-',''),'.',''),'/','') = '".trim($arr_leiturapdv[9])."' LIMIT 1 ");
                        $clientefinaliz = $result->fetch(PDO::FETCH_ASSOC);

                        if(strlen($clientefinaliz["codcliente"]) > 0){
                            $pdvfinalizador->setcodcliente($clientefinaliz["codcliente"]);

                            $pdvvenda = end($this->pdvvenda);
                            $pdvvenda->setcpfcnpj($clientefinaliz["cpfcnpj"]);
                        }
                    }
                    if((strpos($arr_leiturapdv[8], "Cheque") !== FALSE)){
                        $pdvfinalizador->setnumcheque($arr_leiturapdv[9]);
                    }
                    if((strpos($arr_leiturapdv[8], "Agencia") !== FALSE)){
                        $pdvfinalizador->setnumagenciacheq($arr_leiturapdv[9]);
                    }
                    if((strpos($arr_leiturapdv[8], "Banco") !== FALSE)){
                        $pdvfinalizador->setcodbanco($arr_leiturapdv[9]);
                    }
                    if((strpos($arr_leiturapdv[8], "Conta") !== FALSE)){
                        $pdvfinalizador->setcontacheque($arr_leiturapdv[9]);
                    }
                    if((strpos($arr_leiturapdv[8], "Data") !== FALSE)){
                        // Antigo era padrão americano
                        $data = substr($arr_leiturapdv[9], 4, 4)."-".substr($arr_leiturapdv[9], 2, 2)."-".substr($arr_leiturapdv[9], 0, 2);
                        $pdvfinalizador->setdatavencto($data);
                    }
                    $arr_pdvfinalizador[trim($arr_leiturapdv[6])] = $pdvfinalizador;
                    break;
            }
        }
        if(count($arr_pdvfinalizador) > 0){
            foreach($arr_pdvfinalizador as $pdvfinalizador){
                $this->pdvfinalizador[] = $pdvfinalizador;
            }
        }
/*
		foreach($this->pdvvenda as $pdvvenda){
			$arr_column = array("status","cupom","caixa","numeroecf","data","hora","codcliente","codfunc","cpfcnpj","codorcamento","seqecf","chavecfe","codecf");
			foreach($arr_column as $column){
			echo "{$column}: ".call_user_func(array($pdvvenda, "get{$column}"))."<br>";
			}
			echo "<br><br>";
		}
		die();
*/
        // Separa os arquivos que serao importados
        $_SESSION["file_name_det"] = $file_name;

        return $this->importar_maparesumo($arquivar = TRUE);
    }

    function importar_maparesumo($arquivar = TRUE){
        $codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();

        $arr_ecf = array();
        $ecf = objectbytable("ecf", NULL, $this->con);
        $arr_ecf = object_array($ecf);

        // Tenta abrir o diretorio dos arquivos
        $dir_name = $this->pdvconfig->getestabelecimento()->getdirpdvimp();
        if(!($dir = opendir($dir_name))){
            $_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>".$dir_name;
            return FALSE;
        }

        // Acha todos os arquivos gerados pelo emporium
        $i = 1;
        $achou = false;
        while($file = readdir($dir)){
            if((strcasecmp(substr($file, 0, 8), "expgeral") == 0) && (intval(substr($file, 19, 4)) == $codestabelec) && ( (isset($_SESSION["dtmovto_emporium"]) && $_SESSION["dtmovto_emporium"] == substr($file, 9, 8) ) || !isset($_SESSION["dtmovto_emporium"]) )){
                $file_name = $file;
                $dtmovto = substr($file, 9, 8);
                $achou = true;
                break;
            }
        }

        if(!$achou){
			copy($dir_name.$_SESSION["file_name_det"], $dir_name."IMPORTADO/".strtoupper($_SESSION["file_name_det"]));
			unlink($dir_name.$_SESSION["file_name_det"]);
            unset($_SESSION["file_name_det"]);
            return true;
        }

        $paramfiscal = objectbytable("paramfiscal", $codestabelec, $this->con);
        $dtmovto = substr($dtmovto, 0, 4)."-".substr($dtmovto, 4, 2)."-".substr($dtmovto, 6, 2);

        $this->con->start_transaction();

        // Percorre o arquivo de venda
        setprogress(50, "Processando arquivo de venda");

        $this->arr_arquivo[] = $dir_name.$file_name;

        $file = fopen($dir_name.$file_name, "r");
        while(!feof($file)){
            $linha = fgets($file, 4096);
            $arr_linha[] = $linha;
        }
        fclose($file);

        foreach($arr_linha as $i=> $linha){
            if(strlen(trim($linha)) == 0){
                continue;
            }

            $arr_leiturapdv = explode("|", $linha);

            $query = "SELECT ecf.codecf, ecf.numfabricacao, ecf.numeroecf, ecf.caixa ";
            $query .= "FROM ecf ";
            $query .= "WHERE ecf.numeroecf = '".$arr_leiturapdv[0]."' AND ecf.codestabelec = ".$codestabelec." ";
            $query .= "ORDER BY  1 DESC ";
            $query .= "LIMIT 1 ";

            $res = $this->con->query($query);

            $_ecf = array_shift($res->fetchAll());

            $codecf = $_ecf["codecf"];
            $numfabricacao = $_ecf["numfabricacao"];
            $numeroecf = $_ecf["numeroecf"];
            $caixa = $_ecf["caixa"];

            $achou_ecf = FALSE;
            foreach($arr_ecf as $ecf){
                if($ecf->getcodecf() == $codecf){
                    $achou_ecf = TRUE;
                    break;
                }
            }
            if(!$achou_ecf){
                $this->con->rollback();
                die(messagebox("error", "", "Falta cadastro de ECF para o numeroecf ".$arr_leiturapdv[0]." no estabelecimento ".$codestabelec." "));
            }

			if($ecf->getequipamentofiscal() === "SAT"){
				continue;
			}

            $maparesumo = objectbytable("maparesumo", NULL, $this->con);
            $maparesumo->setcodestabelec($this->pdvconfig->getestabelecimento()->getcodestabelec());
            $maparesumo->setcaixa($caixa);
            $maparesumo->setnummaparesumo(($paramfiscal->getnummaparesumo() == 0 ? 1 : $paramfiscal->getnummaparesumo()));
            $maparesumo->setdtmovto($dtmovto);
            $maparesumo->setnumeroecf(strlen($numeroecf) > 0 ? $numeroecf : 0);
            $maparesumo->setnumeroreducoes($arr_leiturapdv[1]);
            $maparesumo->setoperacaoini($arr_leiturapdv[2]);
            $maparesumo->setoperacaofim($arr_leiturapdv[3]);
            $maparesumo->setoperacaonaofiscini(0);
            $maparesumo->setoperacaonaofiscfim(0);
            $reinicio = intval((strlen($arr_leiturapdv[14]) == 0 || $arr_leiturapdv[14] == 0) ? 1 : $arr_leiturapdv[14]);
            $maparesumo->setreinicioini($reinicio);
            $maparesumo->setreiniciofim($reinicio);
            $maparesumo->setcuponsnaofiscemit(0);
            $maparesumo->setcuponsfiscemit(0);
            $maparesumo->setitenscancelados(0);
            $maparesumo->setcuponscancelados(0);
            $maparesumo->setcuponsnfisccanc(0);
            $maparesumo->setnumerodescontos(0);
            $maparesumo->setnumeroacrescimos(0);
            $maparesumo->setgtinicial($arr_leiturapdv[5] / 100);
            $maparesumo->setgtfinal($arr_leiturapdv[6] / 100);
            $maparesumo->settotalbruto($arr_leiturapdv[7] / 100);
            $maparesumo->settotalcupomcancelado($arr_leiturapdv[8] / 100);
            $maparesumo->settotalitemcancelado(0);
            $maparesumo->settotaldescontocupom($arr_leiturapdv[10] / 100);
            $maparesumo->settotaldescontoitem(0);
            $maparesumo->settotalacrescimocupom($arr_leiturapdv[11] / 100);
            $maparesumo->settotalliquido($arr_leiturapdv[12] / 100);
            $maparesumo->setnumseriefabecf($numfabricacao);
            $maparesumo->setcodecf($codecf);

            if(!$maparesumo->save()){
                $this->con->rollback();
                return FALSE;
            }elseif(!$this->importar_maparesumoimposto($dir_name, $arr_leiturapdv[0], $dtmovto, $maparesumo->getcodmaparesumo())){
                $this->con->rollback();
                return FALSE;
            }
        }
        // Separa os arquivos que serao importados
        if($arquivar){
            if(!is_dir($dir_name."IMPORTADO")){
                mkdir($dir_name."IMPORTADO");
            }

            copy($dir_name.$file_name, $dir_name."IMPORTADO/".strtoupper($file_name));
            copy($dir_name.$this->arqtrib, $dir_name."IMPORTADO/".strtoupper($this->arqtrib));
            copy($dir_name.$_SESSION["file_name_det"], $dir_name."IMPORTADO/".strtoupper($_SESSION["file_name_det"]));
            unlink($dir_name.$file_name);
            unlink($dir_name.$this->arqtrib);
            unlink($dir_name.$_SESSION["file_name_det"]);

            unset($_SESSION["file_name_det"]);
        }
        $this->con->commit();
        return TRUE;
    }

    function importar_maparesumoimposto($dir_name, $caixa, $dtmovto, $codmaparesumo){
        // Tenta abrir o diretorio dos arquivos
        if(!($dir = opendir($dir_name))){
            $_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel encontrar o diret&oacute;rio:<br>".$this->path;
            return FALSE;
        }

        // Acha todos os arquivos gerados pelo emporium
        $i = 1;
        while($file = readdir($dir)){
            if((strcasecmp(substr($file, 0, 7), "exptrib") == 0) && (intval(substr($file, 18, 4)) == $this->pdvconfig->getestabelecimento()->getcodestabelec() && str_replace("-", "", $dtmovto) == substr($file, 8, 8))){
                $file_name = $file;
                break;
            }
        }

        // Percorre o arquivo
        setprogress(75, "Processando Impostos do Mapa Resumo");
        $linhas = array();
        $file = fopen($dir_name.$file_name, "r");
        while(!feof($file)){
            $linha = fgets($file, 4096);
            $linhas[] = $linha;
        }
        fclose($file);

        $this->arr_arquivo[] = $dir_name.$file_name;

        $arr_maparesumoimposto = Array();

        $this->con->start_transaction();
        foreach($linhas as $i=> $linha){
            $arr_leiturapdv = explode("|", $linha);

            if($caixa == $arr_leiturapdv[0]){
                $arr_maparesumoimposto[$arr_leiturapdv[1]]["codmaparesumo"] = ($codmaparesumo);
                $arr_maparesumoimposto[$arr_leiturapdv[1]]["tptribicms"] = (substr($arr_leiturapdv[1], 0, 1));
                $arr_maparesumoimposto[$arr_leiturapdv[1]]["aliqicms"] = (strlen($arr_leiturapdv[4]) == 0 ? 0 : $arr_leiturapdv[4]) / 100;
                $arr_maparesumoimposto[$arr_leiturapdv[1]]["totalliquido"] += ($arr_leiturapdv[2] / 100);
                $arr_maparesumoimposto[$arr_leiturapdv[1]]["totalicms"] += ($arr_leiturapdv[3] / 100);
            }
        }

        if(isset($arr_maparesumoimposto)){
            foreach($arr_maparesumoimposto as $mapa){
                $maparesumoimposto = objectbytable("maparesumoimposto", NULL, $this->con);
                $maparesumoimposto->setcodmaparesumo($mapa["codmaparesumo"]);
                $maparesumoimposto->settptribicms($mapa["tptribicms"]);
                $maparesumoimposto->setaliqicms($mapa["aliqicms"]);
                $maparesumoimposto->settotalliquido($mapa["totalliquido"]);
                $maparesumoimposto->settotalicms($mapa["totalicms"]);
                if(!$maparesumoimposto->save()){
                    $this->con->rollback();
                    return FALSE;
                }
            }
            unset($arr_maparesumoimposto);
        }

        $this->arqtrib = $file_name;
        $this->con->commit();
        unset($_SESSION["dtmovto_emporium"]);
        return TRUE;
    }

    function arquivo_venda($file_name){
        $this->limpar_dados();

        if(file_exists($file_name)){
            if(!($xml = simplexml_load_file($file_name))){
                die(messagebox("error", "", "N&atilde;o foi poss&iacute;vel carregar o arquivo:<br>".str_replace("/", "//", $file_name)));
            }
            $cupom = get_object_vars($xml);
            var_dump($cupom);
        }


        $pdvvenda = new PdvVenda;
        $pdvvenda->setcupom($cupom["TICKET"]);
        $pdvvenda->setcaixa($cupom);
        $pdvvenda->setnumeroecf($cupom);
        $pdvvenda->setdata($cupom);
        $pdvvenda->sethora($cupom);
        $pdvvenda->setcodcliente($cupom);
//		$pdvvenda->setcodfunc($cupom);
        $pdvvenda->setcpfcnpj($cupom);

        foreach($cupom["ITEM"] as $item){
            $pdvvenda->pdvitem[] = $item;
        }

        $this->pdvvenda[] = $pdvvenda;
    }

    function getpdvvenda(){
        return $this->pdvvenda;
    }

    function getpdvfinalizador(){
        return $this->pdvfinalizador;
    }

    private function valor_data($data){
        $data = value_date($data);
        $data = convert_date($data, "Y-m-d", "dmY");
        $data = str_pad($data, 8, " ", STR_PAD_RIGHT);
        $data = trim($data);
        $data = strlen($data) == 0 ? " " : $data;
        return $data;
    }

    private function valor_numerico($numero, $decimais, $tamanho){
        if($numero == 0){
            return "0";
        }else{
            $numero = value_numeric($numero);
            $numero = number_format($numero, $decimais, "", ".");
            $numero = str_replace(array("."), "", $numero);
            $numero = substr($numero, 0, $tamanho);
            //$numero = str_pad($numero,$tamanho,"0",STR_PAD_LEFT);
            return $numero;
        }
    }

    private function valor_texto($texto, $tamanho){
        $texto = substr(rtrim($texto), 0, $tamanho);
        $texto = strlen($texto) == 0 ? " " : $texto;
        return $texto;
    }

    function diretorio_venda($dir_name){
        $files = array();
        $dir = opendir($dir_name);
        while($file_name = readdir($dir)){
            $files[] = $file_name;
        }
        foreach($files as $file){
            if(!is_file($dir_name.utf8_decode($file)) && strrchr($file, '.') != ".xml" || (!substr($file,7,1) == "0")){
				if(is_file($dir_name.utf8_decode($file))){
					$dirimp = $dir_name."/INVALIDO/";
					if(!is_dir($dirimp)){
						mkdir($dirimp);
					}
					copy($dir_name.utf8_decode($file), $dirimp.basename($file));
					unlink($dir_name.utf8_decode($file));
				}
                continue;
            }
            $xml = simplexml_load_file($dir_name.utf8_decode($file));

            $data = substr($xml->FISCAL_DAY, 0, 4)."-".substr($xml->FISCAL_DAY, 4, 2)."-".substr($xml->FISCAL_DAY, 6, 2);
            $hora = substr($xml->FISCAL_TIME, 0, 2).":".substr($xml->FISCAL_TIME, 2, 2).":".substr($xml->FISCAL_TIME, 4, 2);

            $pdvvenda = new PdvVenda;
            $pdvvenda->setstatus("A");
            $pdvvenda->setcupom($xml->TICKET);
            $pdvvenda->setcaixa($xml->POS);
            $pdvvenda->setnumeroecf($xml->FISCAL_POS);
            $pdvvenda->setdata($data);
            $pdvvenda->sethora($hora);

            foreach($xml->ITEM AS $item){
                $pdvitem = new PdvItem();
				if($item->NEG == "1"){
					$seq = $item->SEQ - 1;
					unset($pdvvenda->pdvitem[$seq]);
					continue;
				}else{
					$pdvitem->setstatus("A");
				}

                $pdvitem->setcodproduto($item->ID);
                $pdvitem->setquantidade($item->QTY);
                $pdvitem->setpreco($item->UNIT_PRICE);
                $pdvitem->setdesconto($item->DISCOUNT);
                $pdvitem->settotal($item->AMOUNT);
                $pdvitem->settptribicms($item->TAX_ID);
                $pdvitem->setaliqicms($item->TAX_PERC);

                $pdvvenda->pdvitem[] = $pdvitem;
            }

            $pdvfinalizador = new PdvFinalizador();
            $pdvfinalizador->setstatus("A");
            $pdvfinalizador->setcupom($xml->TICKET);
            $pdvfinalizador->setcaixa($xml->POS);
            $pdvfinalizador->setdata($data);
            $pdvfinalizador->sethora($hora);
            $pdvfinalizador->setcodfinaliz((string)$xml->MEDIA_CHANGE->CODE);
            $pdvfinalizador->setvalortotal($xml->MEDIA_CHANGE->AMOUNT);
            $pdvfinalizador->setdatavencto($data);

            $this->pdvfinalizador[] = $pdvfinalizador;

            $this->pdvvenda[] = $pdvvenda;

			if(file_exists($dir_name.utf8_decode($file))){
                $dirimp = $dir_name."/IMPORTADO/";
                if(!is_dir($dirimp)){
                    mkdir($dirimp);
                }
                copy($dir_name.utf8_decode($file), $dirimp.basename($file));
                unlink($dir_name.utf8_decode($file));
            }
        }
    }

    function devolucao($dir_name){
        $files = array();
        $dir = opendir($dir_name);
        while($file_name = readdir($dir)){
            if(strlen($file_name) > 5){
                $files[] = $file_name;
            }
        }
        foreach($files as $file){
            if(!is_file($dir_name.utf8_decode($file))){
                continue;
            }
            $xml = simplexml_load_file($dir_name.utf8_decode($file));
            if(strcasecmp($xml->getName(), "RETURN") == 0){
                $query = "SELECT codcliente FROM cliente WHERE REPLACE(REPLACE(REPLACE(cpfcnpj,'-',''),'.',''),'/','') = '".$xml->ANSWER->DATA."' ";
                $res = $this->con->query($query);
                $codcliente = $res->fetchColumn();

                if(strlen($codcliente) > 0){
                    $this->con->start_transaction();
                    $cliente = objectbytable("cliente", $codcliente, $this->con);
                    $operacaonota = objectbytable("operacaonota", "DC", $this->con);
                    $tabelapreco = objectbytable("tabelapreco", 1, $this->con);
                    $paramnotafiscal = objectbytable("paramnotafiscal", array($this->pdvconfig->getestabelecimento()->getcodestabelec(), "DC"), $this->con);

                    $codestabelec = $this->pdvconfig->getestabelecimento()->getcodestabelec();

                    // Verifica se e venda interestadual
                    if($this->pdvconfig->getestabelecimento()->getuf() != $cliente->getufres()){
                        $natoperacao = objectbytable("natoperacao", $cliente->gettppessoa() == "F" ? $paramnotafiscal->getnatoperacaopfex() : $paramnotafiscal->getnatoperacaopjex(), $this->con);
                    }else{
                        $natoperacao = objectbytable("natoperacao", $cliente->gettppessoa() == "F" ? $paramnotafiscal->getnatoperacaopfin() : $paramnotafiscal->getnatoperacaopjin(), $this->con);
                    }

                    $itemcalculo = new ItemCalculo($this->con);
                    $itemcalculo->setestabelecimento($this->pdvconfig->getestabelecimento());
                    $itemcalculo->setoperacaonota($operacaonota);
                    $itemcalculo->setnatoperacao($natoperacao);
                    $itemcalculo->setparceiro($cliente);

                    foreach($xml->ITEM->ID AS $i=> $item){

                        $produto = objectbytable("produto", (string) $xml->ITEM->ID, $this->con);
                        $piscofins = objectbytable("piscofins", $produto->getcodpiscofinssai(), $this->con);
                        $embalagem = objectbytable("embalagem", $produto->getcodembalvda(), $con);
                        $classfiscalnfe = objectbytable("classfiscal", $produto->getcodcfnfe(), $this->con);
                        $classfiscalnfs = objectbytable("classfiscal", $produto->getcodcfnfs(), $this->con);

                        $tributacaoproduto = new TributacaoProduto($this->con);
                        $tributacaoproduto->setestabelecimento($this->pdvconfig->getestabelecimento());
                        $tributacaoproduto->setnatoperacao($natoperacao);
                        $tributacaoproduto->setoperacaonota($operacaonota);
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
                        $percdescto = $tributacaoproduto->getpercdescto(TRUE);

                        $itpedido = objectbytable("itpedido", NULL, $this->con);
                        $itpedido->setseqitem($i + 1);
                        $itpedido->setcodestabelec($codestabelec);
                        $itpedido->setnatoperacao($natoperacao_it->getnatoperacao());
                        $itpedido->setcodproduto((string) $xml->ITEM->ID);
                        $itpedido->setcodunidade($embalagem->getcodunidade());
                        $itpedido->setqtdeunidade(1);
                        $itpedido->setquantidade((string) $xml->ITEM->QTY);
                        $itpedido->setpreco((string) $xml->ITEM->UNIT_PRICE);
                        $itpedido->settipoipi($tipoipi);
                        $itpedido->setvalipi($valipi);
                        $itpedido->setpercipi($percipi);
                        $itpedido->setpercdescto((string) $xml->ITEM->DECS_PRICE);
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
                    $pedido = objectbytable("pedido", NULL, $con);
                    $pedido->setoperacao("DC");
                    $pedido->setcodestabelec($codestabelec);
//					$pedido->setcodfunc($codfunc);
                    $pedido->settipoparceiro("C");
                    $pedido->setcodparceiro($codcliente);
                    $pedido->setcodespecie($paramnotafiscal->getcodespecieauto());
                    $pedido->setcodcondpagto($paramnotafiscal->getcodcondpagtoauto());
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
                    $pedido->setcodtabela($tabelapreco->getcodtabela());
                    $pedido->setobservacao($observacao);

                    $pedido->flag_itpedido(TRUE);
                    $pedido->itpedido = $arr_itpedido;
                    $pedido->calcular_totais();
                    if(!$pedido->save()){
                        $this->con->rollback();
                        die;
                    }

                    // GERANDO NOTA FISCAL
                    $notafiscal = objectbytable("notafiscal", NULL, $con);
                    $itnotafiscal = objectbytable("itnotafiscal", NULL, $con);
                    $colunas_pedido = $pedido->getcolumnnames();
                    $colunas_itpedido = $itpedido->getcolumnnames();
                    $colunas_notafiscal = $notafiscal->getcolumnnames();
                    $colunas_itnotafiscal = $itnotafiscal->getcolumnnames();
                    foreach($colunas_pedido as $coluna_pedido){
                        if(in_array($coluna_pedido, $colunas_notafiscal)){
                            call_user_func(array($notafiscal, "set".$coluna_pedido), call_user_func(array($pedido, "get".$coluna_pedido)));
                        }
                    }
                    $arr_itnotafiscal = array();
                    foreach($arr_itpedido as $i=> $itpedido){
                        $itnotafiscal = objectbytable("itnotafiscal", NULL, $con);
                        $itnotafiscal->setidnotafiscal($notafiscal->getidnotafiscal());
                        foreach($colunas_itpedido as $coluna_itpedido){
                            if(in_array($coluna_itpedido, $colunas_itnotafiscal)){
                                call_user_func(array($itnotafiscal, "set".$coluna_itpedido), call_user_func(array($itpedido, "get".$coluna_itpedido)));
                            }
                        }
                        $arr_itnotafiscal[] = $itnotafiscal;
                    }

                    $notafiscal->flag_itnotafiscal(TRUE);
                    $notafiscal->itnotafiscal = $arr_itnotafiscal;
                    if(!$notafiscal->save()){
                        $con->rollback();
                        die;
                    }

                    $this->con->commit();
                }
            }
            if(file_exists($dir_name.utf8_decode($file))){
                $dirimp = $dir_name."/IMPORTADO/";
                if(!is_dir($dirimp)){
                    mkdir($dirimp);
                }
                copy($dir_name.utf8_decode($file), $dirimp.basename($file));
                unlink($dir_name.utf8_decode($file));
            }
        }
    }

}
