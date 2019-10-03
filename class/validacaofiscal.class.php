<?php

require_once("../lib/sqlformatter-1.0.0/lib/SqlFormatter.php");

class ValidacaoFiscal{

	private $con;

	private $codestabelec;
	private $dtfinal;
	private $dtinicial;

	function __construct(Connection $con){
		$this->con = $con;
	}

	function setcodestabelec($codestabelec){
		$this->codestabelec = $codestabelec;
	}

	function setdtfinal($dtfinal){
		$this->dtfinal = value_date($dtfinal);
	}

	function setdtinicial($dtinicial){
		$this->dtinicial = value_date($dtinicial);
	}

	function validar_itcupom_maparesumoimposto(){
		$query  = "SELECT dtmovto, caixa, numeroecf, tptribicms, aliqicms, ";
		$query .= "    SUM(totalmaparesumoimposto) AS totalmaparesumoimposto, SUM(totalitcupom) AS totalitcupom, ";
		$query .= "    (SUM(totalmaparesumoimposto) - SUM(totalitcupom)) AS diferenca ";
		$query .= "FROM ( ";
		$query .= "    SELECT maparesumo.dtmovto, maparesumo.caixa, maparesumo.numeroecf, ";
		$query .= "        maparesumoimposto.tptribicms, maparesumoimposto.aliqicms, ";
		$query .= "        maparesumoimposto.totalliquido AS totalmaparesumoimposto, ";
		$query .= "        0 AS totalitcupom ";
		$query .= "    FROM maparesumoimposto ";
		$query .= "    INNER JOIN maparesumo USING (codmaparesumo) ";
		$query .= "    WHERE maparesumo.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "        AND maparesumo.dtmovto BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    UNION ALL ";
		$query .= "    SELECT cupom.dtmovto, cupom.caixa, cupom.numeroecf, ";
		$query .= "        itcupom.tptribicms, itcupom.aliqicms, ";
		$query .= "        0 AS totalmaparesumoimposto, ";
		$query .= "        itcupom.valortotal AS totalitcupom ";
		$query .= "    FROM itcupom ";
		$query .= "    INNER JOIN cupom USING (idcupom) ";
		$query .= "    WHERE cupom.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "        AND cupom.dtmovto BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "        AND cupom.status = 'A' ";
		$query .= "        AND itcupom.status = 'A' ";
		$query .= "        AND itcupom.composicao != 'F' ";
		$query .= "        AND cupom.chavecfe IS NULL ";
		$query .= ") AS temporario ";
		$query .= "GROUP BY dtmovto, caixa, numeroecf, tptribicms, aliqicms ";
		$query .= "HAVING (SUM(ROUND(totalmaparesumoimposto, 2)) - SUM(ROUND(totalitcupom, 2))) != 0 ";
		$query .= "ORDER BY dtmovto, caixa, numeroecf, tptribicms, aliqicms ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);
		//echo $query;

		return array(
			"codigo" => "201",
			"texto" => "Diferença nas tributações entre os cupons e mapas resumo",
			"registros" => $arr
		);
	}

	function validar_itcupom_piscofins_cadastroisento_cupomtributado(){
		$query  = "SELECT cupom.idcupom, cupom.dtmovto, cupom.caixa, cupom.cupom, itcupom.codproduto, ";
		$query .= "    piscofins.codcst, itcupom.totalbasepis, itcupom.totalbasecofins ";
		$query .= "FROM itcupom ";
		$query .= "INNER JOIN cupom USING (idcupom) ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "WHERE cupom.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND cupom.dtmovto BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND (piscofins.codcst IN ('01') OR piscofins.tipo = 'T') ";
		$query .= "    AND (itcupom.totalbasepis = 0 OR itcupom.totalbasecofins = 0) ";
		$query .= "ORDER BY cupom.idcupom, itcupom.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "202",
			"texto" => "Itens de cupom que são tributados de PIS/Cofins no cadastro porém estão isentos no cupom",
			"registros" => $arr
		);
	}

	function validar_itcupom_piscofins_cadastrotributado_cupomisento(){
		$query  = "SELECT cupom.idcupom, cupom.dtmovto, cupom.caixa, cupom.cupom, itcupom.codproduto, ";
		$query .= "    piscofins.codcst, itcupom.totalbasepis, itcupom.totalbasecofins ";
		$query .= "FROM itcupom ";
		$query .= "INNER JOIN cupom USING (idcupom) ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "WHERE cupom.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND cupom.dtmovto BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND piscofins.codcst NOT IN ('01') ";
		$query .= "    AND (itcupom.totalbasepis > 0 OR itcupom.totalbasecofins > 0) ";
		$query .= "ORDER BY cupom.idcupom, itcupom.codproduto ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "203",
			"texto" => "Itens de cupom que são isentos de PIS/Cofins no cadastro porém estão tributados no cupom",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_bonificado_natoperacaonao_notafiscalsim(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.natoperacao ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND SUBSTR(itnotafiscal.natoperacao, 3, 1) != '9' ";
		$query .= "    AND itnotafiscal.bonificado = 'S' ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "101",
			"texto" => "Itens de nota fiscal que são bonificados porém não estão com CFOP de bonificação",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_bonificado_natoperacaosim_notafiscalnao(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.natoperacao ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND SUBSTR(itnotafiscal.natoperacao, 3, 1) = '9' ";
		$query .= "    AND SUBSTR(itnotafiscal.natoperacao, 1, 1) < '4' ";
		$query .= "    AND itnotafiscal.bonificado = 'N' ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "102",
			"texto" => "Itens de nota fiscal que não são bonificados porém estão com CFOP de bonificação",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_bonificado_piscofins(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.totalpis, ";
		$query .= "    itnotafiscal.totalcofins ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN natoperacao ON (notafiscal.natoperacao = natoperacao.natoperacao) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND itnotafiscal.bonificado = 'S' ";
		$query .= "    AND natoperacao.geraspedpiscofins = 'S' ";
		$query .= "    AND (itnotafiscal.totalpis > 0 OR itnotafiscal.totalcofins > 0) ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "103",
			"texto" => "Itens de nota fiscal que são bonificados porém foram tributados de PIS/Cofins",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_piscofins_entrada_cadastroisento_notafiscaltributado(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.totalbasepis, ";
		$query .= "    itnotafiscal.totalbasecofins, piscofins.codcst ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN natoperacao ON (notafiscal.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinsent = piscofins.codpiscofins) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND operacaonota.tipo = 'E' ";
		$query .= "    AND piscofins.codcst NOT IN ('50') ";
		$query .= "    AND (itnotafiscal.totalbasepis > 0 OR itnotafiscal.totalbasecofins > 0) ";
		$query .= "    AND natoperacao.geraspedpiscofins = 'S' ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "104",
			"texto" => "Itens de nota fiscal de entrada isentos de PIS/Cofins no cadastro porém está tributado na nota fiscal",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_piscofins_entrada_cadastrotributado_notafiscalisento(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.totalbasepis, ";
		$query .= "    itnotafiscal.totalbasecofins, piscofins.codcst ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinsent = piscofins.codpiscofins) ";
		$query .= "INNER JOIN natoperacao ON (itnotafiscal.natoperacao = natoperacao.natoperacao) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND operacaonota.tipo = 'E' ";
		$query .= "    AND (piscofins.codcst IN ('50') OR piscofins.tipo = 'T') ";
		$query .= "    AND (itnotafiscal.totalbasepis = 0 OR itnotafiscal.totalbasecofins = 0) ";
		$query .= "    AND SUBSTR(itnotafiscal.natoperacao, 3, 1) != '9' ";
		$query .= "    AND SUBSTR(natoperacao.natoperacao,1,5) NOT IN ('1.556','1.407') ";
		$query .= "    AND natoperacao.geraspedpiscofins = 'S' ";
		$query .= "    AND operacaonota.operacao != 'TE' ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "105",
			"texto" => "Itens de nota fiscal de entrada tributados de PIS/Cofins no cadastro porém está isento na nota fiscal",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_piscofins_saida_cadastroisento_notafiscaltributado(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.totalbasepis, ";
		$query .= "    itnotafiscal.totalbasecofins, piscofins.codcst ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN natoperacao ON (notafiscal.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND operacaonota.tipo = 'S' ";
		$query .= "    AND piscofins.codcst NOT IN ('01') ";
		$query .= "    AND (itnotafiscal.totalbasepis > 0 OR itnotafiscal.totalbasecofins > 0) ";
		$query .= "    AND natoperacao.geraspedpiscofins = 'S' ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "106",
			"texto" => "Itens de nota fiscal de saída isentos de PIS/Cofins no cadastro porém está tributado na nota fiscal",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_piscofins_saida_cadastrotributado_notafiscalisento(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.totalbasepis, ";
		$query .= "    itnotafiscal.totalbasecofins, piscofins.codcst ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN natoperacao ON (notafiscal.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "INNER JOIN piscofins ON (produto.codpiscofinssai = piscofins.codpiscofins) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND operacaonota.tipo = 'S' ";
		$query .= "    AND (piscofins.codcst IN ('01') OR piscofins.tipo = 'T') ";
		$query .= "    AND (itnotafiscal.totalbasepis = 0 OR itnotafiscal.totalbasecofins = 0) ";
		$query .= "    AND natoperacao.geraspedpiscofins = 'S' ";
		$query .= "    AND operacaonota.operacao != 'TE' AND substr(notafiscal.natoperacao,1,5) != '5.929' ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "107",
			"texto" => "Itens de nota fiscal de saída tributados de PIS/Cofins no cadastro porém está isento na nota fiscal",
			"registros" => $arr
		);
	}

	function validar_itnotafiscal_piscofins_entrada_notafiscaltributado_natoperacaosemcredito(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, itnotafiscal.seqitem, itnotafiscal.codproduto, itnotafiscal.totalbasepis, ";
		$query .= "    itnotafiscal.totalbasecofins, itnotafiscal.natoperacao ";
		$query .= "FROM itnotafiscal ";
		$query .= "INNER JOIN notafiscal USING (idnotafiscal) ";
		$query .= "INNER JOIN natoperacao ON (notafiscal.natoperacao = natoperacao.natoperacao) ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN produto USING (codproduto) ";
		$query .= "WHERE notafiscal.codestabelec = {$this->codestabelec} ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND operacaonota.tipo = 'E' ";
		$query .= "    AND (itnotafiscal.totalbasepis > 0 OR itnotafiscal.totalbasecofins > 0) ";
		$query .= "    AND SUBSTR(itnotafiscal.natoperacao, 1, 5) = '1.556' ";
		$query .= "    AND natoperacao.geraspedpiscofins = 'S' ";
		$query .= "ORDER BY notafiscal.idnotafiscal, itnotafiscal.seqitem ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "108",
			"texto" => "Itens de nota fiscal de entrada tributados de PIS/Cofins, porém com natureza de operação que não permite crédito",
			"registros" => $arr
		);
	}

	function validar_maparesumo_informacoesbasicas(){
		$query  = "SELECT maparesumo.codmaparesumo, maparesumo.dtmovto, maparesumo.caixa, ";
		$query .= "    maparesumo.numeroreducoes, maparesumo.operacaofim, maparesumo.reiniciofim ";
		$query .= "FROM maparesumo ";
		$query .= "WHERE maparesumo.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND maparesumo.dtmovto BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND (numeroreducoes IS NULL OR numeroreducoes = 0 OR operacaofim IS NULL OR operacaofim = 0 OR reiniciofim IS NULL OR reiniciofim = 0) ";
		$query .= "ORDER BY maparesumo.dtmovto, maparesumo.caixa ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "301",
			"texto" => "Mapas resumo com falta de informações básicas",
			"registros" => $arr
		);
	}

	function validar_notafiscal_chavenfe(){
		$query  = "SELECT notafiscal.idnotafiscal, operacaonota.descricao AS operacaonota, notafiscal.numnotafis, ";
		$query .= "    notafiscal.serie, notafiscal.dtemissao, notafiscal.dtentrega, parceiro.nome AS parceiro, ";
		$query .= "    notafiscal.chavenfe ";
		$query .= "FROM notafiscal ";
		$query .= "INNER JOIN operacaonota ON (notafiscal.operacao = operacaonota.operacao) ";
		$query .= "INNER JOIN v_parceiro AS parceiro ON (notafiscal.tipoparceiro = parceiro.tipoparceiro AND notafiscal.codparceiro = parceiro.codparceiro) ";
		$query .= "WHERE notafiscal.codestabelec IN (SELECT codestabelec FROM estabelecimento WHERE codestabelecfiscal = {$this->codestabelec}) ";
		$query .= "    AND notafiscal.dtentrega BETWEEN '{$this->dtinicial}' AND '{$this->dtfinal}' ";
		$query .= "    AND chavenfe IS NOT NULL ";
		$query .= "    AND chavenfe != chavenfe(notafiscal.idnotafiscal) ";
		$query .= "ORDER BY notafiscal.operacao, notafiscal.dtemissao, notafiscal.numnotafis ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "250",
			"texto" => "Notas fiscais com a chave de nota fiscal eletrônica inválidas",
			"registros" => $arr
		);
	}

	function validar_unidade_cadastro(){
		$query  = "SELECT unidade.codunidade, unidade.descricao, unidade.sigla ";
		$query .= "FROM unidade ";
		$query .= "WHERE unidade.descricao = unidade.sigla ";
		$query .= "ORDER BY codunidade ";

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		return array(
			"codigo" => "001",
			"texto" => "Unidades com descrição igual a sigla",
			"registros" => $arr
		);
	}

}