<?php

if(!is_object($con)){
	$con = new Connection();
}

$param_cadastro_exibirprecoprod = param("CADASTRO", "EXIBIRPRECOPROD", $con);

$parametros = array(
	"administradora" => array(
		"ordem" => "nome",
		"chave" => array("codadminist"),
		"titulo" => array("C&oacute;digo", "Nome", "Telefone", "Estado", "Cidade"),
		"coluna" => array("codadminist", "nome", "fone", "estado", "cidade"),
		"largura" => array("10%", "35%", "15%", "15%", "25%"),
		"alinhamento" => array("right", "left", "left", "left", "left"),
		"colunaextra" => "estado.nome AS estado, cidade.nome AS cidade"
	),
	"atividade" => array(
		"ordem" => "descricao",
		"chave" => array("codatividade"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codatividade", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"banco" => array(
		"ordem" => "nome",
		"chave" => array("codbanco"),
		"titulo" => array("C&oacute;digo", "Nome", "C&oacute;digo Oficial","Agencia","Conta"),
		"coluna" => array("codbanco", "nome", "codoficial", "agencia" ,"conta"),
		"largura" => array("10%", "58%", "12%", "10%", "10%"),
		"alinhamento" => array("right", "left", "right", "right", "right")
	),
	"carimbo" => array(
		"ordem" => "descrreduzida",
		"chave" => array("codcarimbo"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codatividade", "descrreduzida"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"carrossel" => array(
		"ordem" => "codcarrossel",
		"chave" => array("codcarrossel"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codcarrossel", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"catlancto" => array(
		"ordem" => "descricao",
		"chave" => array("codcatlancto"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codcatlancto", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"centrocusto" => array(
		"ordem" => "nome",
		"chave" => array("codccusto"),
		"titulo" => array("C&oacute;digo", "Nome"),
		"coluna" => array("codccusto", "nome"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"cidade" => array(
		"ordem" => "nome",
		"chave" => array("codcidade"),
		"titulo" => array("C&oacute;digo", "Nome", "Estado"),
		"coluna" => array("codcidade", "nome", "estado"),
		"largura" => array("10%", "60%", "30%"),
		"alinhamento" => array("right", "left", "center"),
		"colunaextra" => "estado.nome AS estado"
	),

	"codigoservico" => array(
		"ordem" => "descricao",
		"chave" => array("idcodigoservico"),
		"titulo" => array("Código Serviço","Código Sub-Item","Descrição", "Aliquota ISS"),
		"coluna" => array("idcodigoservico","codigosubitem", "descricao","aliquotaiss"),
		"largura" => array("10%","15%","65%","10%"),
		"alinhamento" => array("left","left","left","right")
	),
	"classfiscal" => array(
		"ordem" => "descricao",
		"chave" => array("codcf"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Tipo", "Al&iacute;quota"),
		"coluna" => array("codcf", "descricao", "_tptribicms", "_aliqicms"),
		"largura" => array("10%", "60%", "20%", "10%"),
		"alinhamento" => array("right", "left", "left", "right"),
		"colunaextra" => "formatar(aliqicms,2) AS _aliqicms, (CASE WHEN tptribicms = 'I' THEN 'Isento' WHEN tptribicms = 'T' THEN 'Tributado' WHEN tptribicms = 'F' THEN 'Substitui&ccedil;&atilde;o' WHEN tptribicms = 'R' THEN 'Reduzido' WHEN tptribicms = 'N' THEN 'N&atilde;o Tributado' ELSE tptribicms END) AS _tptribicms"
	),
	"classificacao" => array(
		"ordem" => "descricao",
		"chave" => array("codclassif"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Direcionamento"),
		"coluna" => array("codclassif", "descricao", "_tipo"),
		"largura" => array("10%", "70%", "20%"),
		"alinhamento" => array("right", "left", "left"),
		"colunaextra" => "(CASE WHEN tipo = 'C' THEN 'Cliente' WHEN tipo = 'F' THEN 'Fornecedor' ELSE 'Transportadora' END) AS _tipo"
	),
	"clicartaoprop" => array(
		"ordem" => "descricao",
		"chave" => array("codcliente", "codcartao"),
		"titulo" => array("Cliente", "Cart&atilde;o", "Status"),
		"coluna" => array("cliente", "cartao", "_status"),
		"largura" => array("40%", "40%", "20%"),
		"alinhamento" => array("left", "left", "left"),
		"colunaextra" => "cliente.nome AS cliente, cartao.descricao AS cartao, (CASE WHEN clicartaoprop.status = 'A' THEN 'Ativo' WHEN clicartaoprop.status = 'B' THEN 'Bloqueio Automatico' WHEN clicartaoprop.status = 'M' THEN 'Bloqueio Manual' WHEN clicartaoprop.status = 'C' THEN 'Cancelado' ELSE 'Inativo' END) AS _status"
	),
	"cliente" => array(
		"ordem" => "nome",
		"chave" => array("codcliente"),
		"titulo" => array("C&oacute;digo", "Nome", "CPF/CNPJ", "Telefone", "Status"),
		"coluna" => array("codcliente", "nome", "cpfcnpj", "foneres", "statuscliente"),
		"largura" => array("10%", "45%", "15%", "15%", "15%"),
		"alinhamento" => array("right", "left", "left", "left", "left"),
		"colunaextra" => "(SELECT descricao FROM statuscliente WHERE codstatus = cliente.codstatus) AS statuscliente"
	),
	"complcadastro" => array(
		"ordem" => "_tabela, descricao",
		"chave" => array("codcomplcadastro"),
		"titulo" => array("C&oacute;digo", "Tabela", "Descri&ccedil;&atilde;o", "Tipo", "Obrigat&oacute;rio"),
		"coluna" => array("codcomplcadastro", "_tabela", "descricao", "_tipo", "_obrigatorio"),
		"largura" => array("10%", "20%", "40%", "15%", "10%"),
		"alinhamento" => array("right", "left", "left", "left", "center"),
		"colunaextra" => "(CASE WHEN tabela = 'cliente' THEN 'Cliente' WHEN tabela = 'fornecedor' THEN 'Fornecedor' WHEN tabela = 'produto' THEN 'Produto' ELSE tabela END) AS _tabela, (CASE WHEN tipo = 'B' THEN 'L&oacute;gico' WHEN tipo = 'D' THEN 'Data' WHEN tipo = 'F' THEN 'Decimal' WHEN tipo = 'I' THEN 'Inteiro' WHEN tipo = 'S' THEN 'Texto' WHEN tipo = 'T' THEN 'Hora' ELSE tipo END) AS _tipo, (CASE WHEN obrigatorio = 'S' THEN 'Sim' ELSE 'N&atilde;o' END) AS _obrigatorio "
	),
	"composicao" => array(
		"ordem" => "produto, tipo",
		"chave" => array("codcomposicao"),
		"titulo" => array("C&oacute;digo", "Cod Prod Pai","Produto Pai", "Tipo"),
		"coluna" => array("codcomposicao", "codproduto","produto", "_tipo"),
		"largura" => array("10%", "12%","50%", "28%"),
		"alinhamento" => array("right", "right", "left", "left"),
		"colunaextra" => "(CASE WHEN composicao.tipo = 'V' THEN 'Explode na Venda' WHEN composicao.tipo = 'C' THEN 'Explode na Compra' WHEN composicao.tipo = 'A' THEN 'Explode em Ambos' WHEN composicao.tipo = 'D' THEN 'Desmembramento' WHEN composicao.tipo = 'T' THEN 'Explode na Tranferencia' WHEN composicao.tipo = 'P' THEN 'Produ&ccedil;&atilde;o' ELSE composicao.tipo END) AS _tipo, produto.descricaofiscal AS produto"
	),
	"concorrente" => array(
		"ordem" => "nome",
		"chave" => array("codconcorrente"),
		"titulo" => array("C&oacute;digo", "Nome", "Site"),
		"coluna" => array("codconcorrente", "nome", "site"),
		"largura" => array("10%", "40%", "50%"),
		"alinhamento" => array("right", "left", "left")
	),
	"condpagto" => array(
		"ordem" => "descricao",
		"chave" => array("codcondpagto"),
		"titulo" => array("C&oacute;digo", "Tipo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codcondpagto", "_tipo", "descricao"),
		"largura" => array("10%", "30%", "60%"),
		"alinhamento" => array("right", "left", "left"),
		"colunaextra" => "(CASE WHEN tipo = 'DD' THEN 'Dias da data de emiss&atilde;o' WHEN tipo = 'DDL' THEN 'Dias da data de entrega' WHEN tipo = 'DFS' THEN 'Dias fora semana' WHEN tipo = 'DFQ' THEN 'Dias fora quinzena' WHEN tipo = 'DFM' THEN 'Dias fora m&ecirc;s' WHEN tipo = 'PV' THEN 'Parcelamento em Vezes' ELSE tipo END) AS _tipo"
	),
	"contabilidade" => array(
		"ordem" => "nome",
		"chave" => array("codcontabilidade"),
		"titulo" => array("C&oacute;digo", "Nome", "Estado", "Cidade", "Contador"),
		"coluna" => array("codcontabilidade", "nome", "_estado", "_cidade", "nomecontador"),
		"largura" => array("10%", "30%", "20%", "15%", "25%"),
		"alinhamento" => array("right", "left", "left", "left", "left"),
		"colunaextra" => "(SELECT nome FROM estado WHERE uf = contabilidade.uf) AS _estado, (SELECT nome FROM cidade WHERE codcidade = contabilidade.codcidade) AS _cidade"
	),
	"cotacao" => array(
		"ordem" => "datacriacao DESC, horacriacao DESC",
		"chave" => array("codcotacao"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Estabelecimento", "Cria&ccedil;&atilde;o", "Encerramento"),
		"coluna" => array("codcotacao", "descricao", "estabelecimento", "_datacriacao", "_dataencerramento"),
		"largura" => array("10%", "30%", "30%", "15%", "15%"),
		"alinhamento" => array("right", "left", "left", "center", "center"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = cotacao.codestabelec) AS estabelecimento, formatar(datacriacao) AS _datacriacao, formatar(dataencerramento) AS _dataencerramento"
	),
	"departamento" => array(
		"ordem" => "nome",
		"chave" => array("coddepto"),
		"titulo" => array("C&oacute;digo", "Nome", "Repons&aacute;vel", "Total de Produtos"),
		"coluna" => array("coddepto", "nome", "funcionario", "totalprodutos"),
		"largura" => array("10%", "40%", "35%", "15%"),
		"alinhamento" => array("right", "left", "left", "right"),
		"colunaextra" => "funcionario.nome AS funcionario, (SELECT COUNT(codproduto) FROM produto WHERE coddepto = departamento.coddepto) AS totalprodutos"
	),
	"ecf" => array(
		"ordem" => "codestabelec, caixa, status, numfabricacao",
		"chave" => array("codecf"),
		"titulo" => array("C&oacute;digo", "Estabelecimento", "N&uacute;mero de S&eacute;rie de Fabrica&ccedil;&atilde;o", "Modelo", "Caixa", "ECF", "Status"),
		"coluna" => array("codecf", "estabelecimento", "numfabricacao", "modelo", "caixa", "numeroecf", "_status"),
		"largura" => array("10%", "28%", "28%", "25%", "10%", "10%", "14%"),
		"alinhamento" => array("right", "left", "center", "left", "center", "center", "left"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = ecf.codestabelec) AS estabelecimento, (CASE status WHEN 'A' THEN 'Ativo' WHEN 'I' THEN 'Inativo' ELSE status END) AS _status"
	),
	"embalagem" => array(
		"ordem" => "unidade, quantidade",
		"chave" => array("codembal"),
		"titulo" => array("C&oacute;digo", "Quantidade", "Unidade", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codembal", "_quantidade", "unidade", "descricao"),
		"largura" => array("10%", "15%", "25%", "50%"),
		"alinhamento" => array("right", "right", "left", "left"),
		"colunaextra" => "formatar(quantidade,2) AS _quantidade, unidade.descricao AS unidade"
	),
	"emitente" => array(
		"ordem" => "nome",
		"chave" => array("codemitente"),
		"titulo" => array("C&oacute;digo", "Nome", "Telefone", "Estado", "Cidade"),
		"coluna" => array("codemitente", "nome", "fone1", "estado", "cidade"),
		"largura" => array("10%", "30%", "15%", "20%", "25%"),
		"alinhamento" => array("right", "left", "left", "left", "left"),
		"colunaextra" => "cidade.nome AS cidade, estado.nome AS estado"
	),
	"equivalente" => array(
		"ordem" => "descricao",
		"chave" => array("codequivalente"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codequivalente", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"especie" => array(
		"ordem" => "descricao",
		"chave" => array("codespecie"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codespecie", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"estabelecimento" => array(
		"ordem" => "nome",
		"chave" => array("codestabelec"),
		"titulo" => array("C&oacute;digo", "Nome", "Telefone", "Estado", "Cidade", "Emitente"),
		"coluna" => array("codestabelec", "nome", "fone1", "estado", "cidade", "emitente"),
		"largura" => array("10%", "25%", "15%", "15%", "20%", "15%"),
		"alinhamento" => array("right", "left", "left", "left", "left"),
		"colunaextra" => "emitente.nome AS emitente, cidade.nome AS cidade, estado.nome AS estado"
	),
	"etiqgondola" => array(
		"ordem" => "descricao",
		"chave" => array("codetiqgondola"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codetiqgondola", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"etiqcliente" => array(
		"ordem" => "codetiqcliente",
		"chave" => array("codetiqcliente"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codetiqcliente", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"etiqnotafiscal" => array(
		"ordem" => "codetiqnotafiscal",
		"chave" => array("codetiqnotafiscal"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codetiqnotafiscal", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"familia" => array(
		"ordem" => "descricao",
		"chave" => array("codfamilia"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Total de Produtos"),
		"coluna" => array("codfamilia", "descricao", "totalprodutos"),
		"largura" => array("10%", "70%", "20%"),
		"alinhamento" => array("right", "left", "right"),
		"colunaextra" => "(SELECT COUNT(codproduto) FROM produto WHERE codfamilia = familia.codfamilia) AS totalprodutos"
	),
	"feriado" => array(
		"ordem" => "mes, dia",
		"chave" => array("mes", "dia"),
		"titulo" => array("M&ecirc;s", "Dia", "Nome"),
		"coluna" => array("_mes", "dia", "nome"),
		"largura" => array("20%", "10%", "70%"),
		"alinhamento" => array("left", "center", "left"),
		"colunaextra" => "mes(mes) AS _mes"
	),
	"familiafornec" => array(
		"ordem" => "descricao",
		"chave" => array("codfamfornec"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codfamfornec", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"fidelidadepremio" => array(
		"ordem" => "descricao",
		"chave" => array("codfidelidadepremio"),
		"titulo" => array("C&oacute;digo", "Produto", "Descrficao", "Pontos Resgate"),
		"coluna" => array("codfidelidadepremio", "codproduto", "descricao", "pontosresgate"),
		"largura" => array("10%", "10%", "75%", "15%"),
		"alinhamento" => array("right", "left", "left", "left")
	),
	"fidelidaderesgate" => array(
		"ordem" => "dataresgate",
		"chave" => array("codfidelidaderesgate"),
		"titulo" => array("Estabelecimento", "Pontos Disponiveis", "Pontos Resgatados"),
		"coluna" => array("estabelecimento", "pontosdisponiveis", "pontosresgatados"),
		"largura" => array("70%", "15%", "15%"),
		"alinhamento" => array("left", "left", "left"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = fidelidaderesgate.codestabelec) AS estabelecimento"
	),
	"finalizadora" => array(
		"ordem" => "estabelecimento,codfinaliz",
		"chave" => array("codestabelec", "codfinaliz"),
		"titulo" => array("Estabelecimento", "C&oacute;d Finalizadora", "Descri&ccedil;&atilde;o"),
		"coluna" => array("estabelecimento", "codfinaliz", "descricao"),
		"largura" => array("35%", "20%", "45%"),
		"alinhamento" => array("left", "center", "left"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento"
	),
	"fornecedor" => array(
		"ordem" => "nome",
		"chave" => array("codfornec"),
		"titulo" => array("C&oacute;digo", "Nome", "Telefone", "Estado", "Cidade"),
		"coluna" => array("codfornec", "nome", "fone", "estado", "cidade"),
		"largura" => array("10%", "35%", "15%", "15%", "25%"),
		"alinhamento" => array("right", "left", "left", "left", "left"),
		"colunaextra" => "estado.nome AS estado, cidade.nome AS cidade"
	),
	"funcionario" => array(
		"ordem" => "nome",
		"chave" => array("codfunc"),
		"titulo" => array("C&oacute;digo", "Nome", "Matr&iacute;cula", "Telefone", "Situa&ccedil;&atilde;o"),
		"coluna" => array("codfunc", "nome", "nummatricula", "fone1", "situacao"),
		"largura" => array("10%", "40%", "15%", "15%", "20%"),
		"alinhamento" => array("right", "left", "left", "left", "left")
	),
	"grupo" => array(
		"ordem" => "nome",
		"chave" => array("codgrupo"),
		"titulo" => array("C&oacute;digo", "Nome", "Respons&aacute;vel"),
		"coluna" => array("codgrupo", "nome", "responsavel"),
		"largura" => array("10%", "50%", "40%"),
		"alinhamento" => array("right", "left", "left")
	),
	"grupocta" => array(
		"ordem" => "descricao",
		"chave" => array("codgrcta"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codgrcta", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"grupoocorrencia" => array(
		"ordem" => "descricao",
		"chave" => array("codgrupoocor"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codgrupoocor", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"grupoprod" => array(
		"ordem" => "descricao",
		"chave" => array("codgrupo"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Departamento"),
		"coluna" => array("codgrupo", "descricao", "departamento"),
		"largura" => array("10%", "50%", "40%"),
		"alinhamento" => array("right", "left", "left"),
		"colunaextra" => "departamento.nome AS departamento"
	),
	"historicopadrao" => array(
		"ordem" => "descricao",
		"chave" => array("codhistorico"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codhistorico", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"icmspdv" => array(
		"ordem" => "codestabelec,tipoicms,aliqicms,redicms",
		"chave" => array("codestabelec", "tipoicms", "aliqicms", "redicms"),
		"titulo" => array("Estabelecimento", "Tipo de ICMS", "Al&iacute;quota ICMS", "Redu&ccedil;&atilde;o ICMS", "Informa&ccedil;&atilde;o PDV"),
		"coluna" => array("estabelecimento", "_tipoicms", "_aliqicms", "_redicms", "infpdv"),
		"largura" => array("35%", "20%", "15%", "15%", "15%"),
		"alinhamento" => array("left", "left", "right", "right", "center"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = icmspdv.codestabelec) AS estabelecimento, formatar(aliqicms,2) AS _aliqicms, formatar(redicms,2) AS _redicms, (CASE WHEN tipoicms = 'T' THEN 'Tributado' WHEN tipoicms = 'F' THEN 'Substitui&ccedil;&atilde;o' WHEN tipoicms = 'R' THEN 'Reduzido' WHEN tipoicms = 'I' THEN 'Isento' WHEN tipoicms = 'N' THEN 'N&atilde;o Tributado' ELSE tipoicms END) AS _tipoicms"
	),
	"interesse" => array(
		"ordem" => "descricao",
		"chave" => array("codinteresse"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codinteresse", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"inventario" => array(
		"ordem" => "data, descricao",
		"chave" => array("codinventario"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Cadastramento", "Congelamento", "Atualiza&ccedil;&atilde;o"),
		"coluna" => array("codinventario", "descricao", "_data", "_datacong", "_dataatua"),
		"largura" => array("10%", "45%", "15%", "15%", "15%"),
		"alinhamento" => array("right", "left", "center", "center", "center"),
		"colunaextra" => "formatar(data) AS _data, formatar(datacong) AS _datacong, formatar(dataatua) AS _dataatua"
	),
	"ipi" => array(
		"ordem" => "descricao",
		"chave" => array("codipi"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Tipo Tributa&ccedil;&atilde;o", "Al&iacute;quota IPI"),
		"coluna" => array("codipi", "descricao", "_tptribipi", "_aliqipi"),
		"largura" => array("10%", "50%", "20%", "20%"),
		"alinhamento" => array("right", "left", "left", "right"),
		"colunaextra" => "(CASE WHEN tptribipi = 'F' THEN 'Fixo' WHEN tptribipi = 'P' THEN 'Percentual' ELSE tptribipi END) AS _tptribipi, formatar(aliqipi,2) AS _aliqipi"
	),
	"lancamento" => array(
		"ordem" => param("LANCAMENTO", "ORDEMPESQLANC", $con) == "0" ? "codlancto DESC" : "dtemissao DESC,numnotafis DESC,favorecido DESC, parcela ASC",
		"chave" => array("codlancto"),
		"titulo" => array("Estab", "Pag/Rec", "Prev/Real", "Favorecido", "Documento", "Emiss&atilde;o", "Vencimento", "Liquida&ccedil;&atilde;o", "Parc","Cond Pagto", "Valor","Status"),
		"coluna" => array("codestabelec", "_pagrec", "_prevreal", "favorecido", "numnotafis", "_dtemissao", "_dtvencto", "_dtliquid", "_parcela","_condpagto", "_valorliquido","status"),
		"largura" => array("5%", "9%", "8%", "16%", "10%", "9%", "9%", "9%", "5%","9%" ,"10%","6%"),
		"alinhamento" => array("right", "left", "left", "left", "center", "center", "center", "center", "center","left","right", "center"),
		"colunaextra" => "(SELECT descricao FROM condpagto WHERE codcondpagto = lancamento.codcondpagto) AS _condpagto,estabelecimento.nome AS estabelecimento, formatar(dtemissao) AS _dtemissao, formatar(dtvencto) AS _dtvencto, formatar(dtliquid) AS _dtliquid, formatar(valorliquido,2) AS _valorliquido, (parcela || '/' || totalparcelas) AS _parcela, (CASE WHEN pagrec = 'R' THEN 'Recebimento' ELSE 'Pagamento' END) AS _pagrec, (CASE WHEN prevreal = 'R' THEN 'Real' ELSE 'Previs&atilde;o' END) AS _prevreal"
	),
	"lancamentogru" => array(
		"ordem" => "dtlancto DESC, favorecido",
		"chave" => array("codlanctogru"),
		"titulo" => array("Estabelecimento", "Pag/Rec", "Favorecido", "Emiss&atilde;o", "Condi&ccedil;&atilde;o de Pagto", "Valor Total"),
		"coluna" => array("_estabelecimento", "_pagrec", "favorecido", "_dtemissao", "_condpagto", "_valorliquido"),
		"largura" => array("20%", "10%", "20%", "10%", "20%", "10%"),
		"alinhamento" => array("left", "left", "left", "center", "left", "right"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = lancamentogru.codestabelec) AS _estabelecimento, formatar(dtemissao) AS _dtemissao, formatar(valorliquido,2) AS _valorliquido, (SELECT descricao FROM condpagto WHERE codcondpagto = lancamentogru.codcondpagto) AS _condpagto, (CASE WHEN pagrec = 'R' THEN 'Recebimento' ELSE 'Pagamento' END) AS _pagrec "
	),
	"layout" => array(
		"ordem" => "descricao",
		"chave" => array("codlayout"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Tipo de Layout", "Nome do Arquivo"),
		"coluna" => array("codlayout", "descricao", "_tipolayout", "nomearquivo"),
		"largura" => array("10%", "30%", "20%", "40%"),
		"alinhamento" => array("right", "left", "left", "left"),
		"colunaextra" => "(CASE WHEN tipolayout = 'E' THEN 'Exporta��o' WHEN tipolayout = 'I' THEN 'Importa��o' ELSE tipolayout END) AS _tipolayout"
	),
	"limitecredito" => array(
		"ordem" => "cliente",
		"chave" => array("codcliente"),
		"titulo" => array("Cliente", "Data Validade", "Valor Pago"),
		"coluna" => array("cliente", "_dtvalidade", "_valorpago"),
		"largura" => array("50%", "25%", "25%"),
		"alinhamento" => array("left", "center", "right"),
		"colunaextra" => "cliente.nome AS cliente"
	),
	"maparesumo" => array(
		"ordem" => "codestabelec,dtmovto,caixa",
		"chave" => array("codmaparesumo"),
		"titulo" => array("Estabelecimeto", "GT Inicial", "GT Final", "Data", "Caixa", "Total Liquido"),
		"coluna" => array("estabelecimento", "_gtinicial", "_gtfinal", "_dtmovto", "caixa", "_totalliquido"),
		"largura" => array("25%", "20%", "20%", "10%", "6%", "15%"),
		"alinhamento" => array("left", "right", "right", "center", "center", "right"),
		"colunaextra" => "formatar(gtinicial,2) AS _gtinicial, formatar(gtfinal,2) AS _gtfinal, formatar(dtmovto) AS _dtmovto, formatar(totalliquido,2) AS _totalliquido, (SELECT NOME FROM estabelecimento WHERE codestabelec = maparesumo.codestabelec) AS estabelecimento"
	),
	"marca" => array(
		"ordem" => "descricao",
		"chave" => array("codmarca"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Total de Produtos"),
		"coluna" => array("codmarca", "descricao", "totalprodutos"),
		"largura" => array("10%", "70%", "20%"),
		"alinhamento" => array("right", "left", "right"),
		"colunaextra" => "(SELECT COUNT(codproduto) FROM produto WHERE codmarca = marca.codmarca) AS totalprodutos"
	),
	"modeloemail" => array(
		"ordem" => "descricao",
		"chave" => array("codmodeloemail"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codmodeloemail", "descricao"),
		"largura" => array("15%", "85%"),
		"alinhamento" => array("right", "left")
	),
	"modelosaneamento" => array(
		"ordem" => "descricao",
		"chave" => array("codmodelo"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Arquivo"),
		"coluna" => array("codmodelo", "descricao", "nomearquivo"),
		"largura" => array("15%", "50%", "35%"),
		"alinhamento" => array("right", "left", "left")
	),
	"moeda" => array(
		"ordem" => "descricao",
		"chave" => array("codmoeda"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "S&iacute;mbolo"),
		"coluna" => array("codmoeda", "descricao", "simbolo"),
		"largura" => array("10%", "70%", "20%"),
		"alinhamento" => array("right", "left", "center")
	),
	"movimento" => array(
		"ordem" => "dtmovto DESC, hora DESC",
		"chave" => array("codmovimento"),
		"titulo" => array("Data", "Estabelecimento", "Tipo Documento", "Produto", "Quantidade (Un)"),
		"coluna" => array("_dtmovto", "estabelecimento", "tipodocumento", "produto", "_quantidade"),
		"largura" => array("10%", "20%", "20%", "38%", "12%"),
		"alinhamento" => array("center", "left", "left", "left", "right"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento, tipodocumento.descricao AS tipodocumento, produto.descricaofiscal AS produto, formatar(dtmovto) AS _dtmovto, formatar(quantidade * qtdeunidade,4) AS _quantidade"
	),
	"natoperacao" => array(
		"ordem" => "natoperacao",
		"chave" => array("natoperacao"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("natoperacao", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"natreceita" => array(
		"ordem" => "natreceita",
		"chave" => array("tabela", "codigo", "natreceita"),
		"titulo" => array("Tabela", "Chave da Tabela", "Natureza da Receita"),
		"coluna" => array("_tabela", "codigo", "natreceita"),
		"largura" => array("50%", "25%", "25%"),
		"alinhamento" => array("left", "right", "right"),
		"colunaextra" => "(CASE WHEN natreceita.tabela = 'P' THEN 'Produto' WHEN natreceita.tabela = 'D' THEN 'Departamento' WHEN natreceita.tabela = 'G' THEN 'Grupo' WHEN natreceita.tabela = 'S' THEN 'Sub Grupo' ELSE natreceita.tabela END) AS _tabela"
	),
	"ncm" => array(
		"ordem" => "codigoncm",
		"chave" => array("idncm"),
		"titulo" => array("Chave", "C&oacute;digo NCM", "Descri&ccedil;&atilde;o"),
		"coluna" => array("idncm", "codigoncm", "descricao"),
		"largura" => array("10%", "20%", "70%"),
		"alinhamento" => array("right", "center", "left")
	),
	"negociacaopreco" => array(
		"ordem" => "codnegociacaopreco",
		"chave" => array("codnegociacaopreco"),
		"titulo" => array("C&oacute;digo", "Descricao", "Parceiro", "Data Vigor"),
		"coluna" => array("codnegociacaopreco", "descricao", "parceiro", "_dtvigor"),
		"largura" => array("13%", "42%", "42%", "13%"),
		"alinhamento" => array("right", "left", "left", "center"),
		"colunaextra" => "formatar(dtvigor) AS _dtvigor, (SELECT nome FROM v_parceiro WHERE tipoparceiro = negociacaopreco.tipoparceiro AND codparceiro = negociacaopreco.codparceiro) AS parceiro"
	),
	"notacomplemento" => array(
		"ordem" => "codestabelec, numnotafis, serie",
		"chave" => array("idnotacomplemento"),
		"titulo" => array("Estabelecimento", "Nota Complementar", "Nota Referente", "Emiss&atilde;o"),
		"coluna" => array("estabelecimento", "_notafiscalcompl", "_notafiscalref", "_dtemissao"),
		"largura" => array("40%", "20%", "20%", "20%"),
		"alinhamento" => array("left", "center", "center", "center"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = notacomplemento.codestabelec) AS estabelecimento, (numnotafis || '-' || serie) AS _notafiscalcompl, (SELECT numnotafis || '-' || serie FROM notafiscal WHERE idnotafiscal = notacomplemento.idnotafiscal) AS _notafiscalref, formatar(dtemissao) AS _dtemissao"
	),
	"notadiversa" => array(
		"ordem" => "codestabelec, numnotafis, serie",
		"chave" => array("idnotadiversa"),
		"titulo" => array("Estabelecimento", "Nota fiscal", "Serie", "Parceiro", "Data Emiss&atilde;o", "Data Venc.", "Total"),
		"coluna" => array("_estabelecimento", "numnotafis", "serie", "_nome", "_dtemissao", "_dtvencto", "_totalliquido"),
		"largura" => array("25%", "10%", "6%", "25%", "12%", "12%", "10%"),
		"alinhamento" => array("left", "right", "center", "left", "center", "center", "right"),
		"colunaextra" => "(SELECT estabelecimento.nome FROM estabelecimento WHERE estabelecimento.codestabelec = notadiversa.codestabelec) AS _estabelecimento, (SELECT v_parceiro.nome FROM v_parceiro WHERE v_parceiro.codparceiro = notadiversa.codparceiro AND v_parceiro.tipoparceiro = notadiversa.tipoparceiro) AS _nome, (numnotafis||'-'||serie) AS documento ,formatar(dtemissao) AS _dtemissao ,formatar(dtvencto) AS _dtvencto , formatar(notadiversa.totalliquido,2) AS _totalliquido"
	),
	"notafiscalservico" => array(
		"ordem" => "codestabelec, numnotafis, serie",
		"chave" => array("idnotafiscalservico"),
		"titulo" => array("Estabelecimento","Nota Fiscal", "Serie", "Parceiro", "Data Emiss&atilde;o", "Data Eexecu&ccedil&atilde;o", "Valor Documento"),
		"coluna" => array("codestabelec","numnotafis", "serie", "nome", "dtemissao", "dtentrega", "valorliquido"),
		"largura" => array("25%","10%","5%","25%","12%","12%","11%"),
		"alinhamento" => array("left","left","left","left","center","center","right"),
		"colunaextra" => array()
	),
	"notafiscal" => array(
		"ordem" => "estabelecimento, numpedido DESC",
		"chave" => array("idnotafiscal"),
		"titulo" => array("Estabelecimento", "Nota Fiscal", "S&eacute;rie", "Parceiro", "Data Emiss&atilde;o", "Data Entrega", "Total"),
		"coluna" => array("estabelecimento", "numnotafis", "serie", "parceiro", "_dtemissao", "_dtentrega", "_totalliquido"),
		"largura" => array("25%", "10%", "6%", "25%", "12%", "12%", "10%"),
		"alinhamento" => array("left", "right", "center", "left", "center", "center", "right"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento, (CASE WHEN notafiscal.tipoparceiro = 'C' THEN cliente.nome WHEN notafiscal.tipoparceiro = 'F' THEN fornecedor.nome ELSE estabelecimento2.nome END) AS parceiro, formatar(dtemissao) AS _dtemissao, formatar(dtentrega) AS _dtentrega, formatar(notafiscal.totalliquido,2) AS _totalliquido"
	),
	"notafrete" => array(
		"ordem" => "dtemissao DESC, transportadora",
		"chave" => array("idnotafrete"),
		"titulo" => array("Transportadora", "Nota Fiscal", "S&eacute;rie", "Emiss&atilde;o", "Entrega", "Total Nota"),
		"coluna" => array("transportadora", "numnotafis", "serie", "_dtemissao", "_dtentrega", "_totalliquido"),
		"largura" => array("30%", "10%", "5%", "15%", "15%", "15%"),
		"alinhamento" => array("left", "right", "center", "center", "center", "right"),
		"colunaextra" => "(SELECT nome FROM transportadora WHERE codtransp = notafrete.codtransp) AS transportadora, formatar(dtemissao) AS _dtemissao, formatar(dtentrega) AS _dtentrega, formatar(totalliquido,2) AS _totalliquido"
	),
	"nutricional" => array(
		"ordem" => "descricao",
		"chave" => array("codnutricional"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codnutricional", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"nutricional" => array(
		"ordem" => "descricao",
		"chave" => array("codnutricional"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codnutricional", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"ocorrencia" => array(
		"ordem" => "dtcriacao DESC, hrcriacao DESC",
		"chave" => array("codocorrencia"),
		"titulo" => array("C&oacute;digo", "T&iacute;tulo", "Parceiro", "Grupo", "Status", "Cria&ccedil;&atilde;o"),
		"coluna" => array("codocorrencia", "titulo", "_parceiro", "_grupoocorrencia", "_status", "_criacao"),
		"largura" => array("10%", "18%", "22%", "15%", "10%", "15%"),
		"alinhamento" => array("right", "left", "left", "left", "center"),
		"colunaextra" => "(SELECT codparceiro || ' - ' || nome FROM v_parceiro WHERE tipoparceiro = ocorrencia.tipoparceiro AND codparceiro = ocorrencia.codparceiro) AS _parceiro, (SELECT descricao FROM grupoocorrencia WHERE codgrupoocor = ocorrencia.codgrupoocor) AS _grupoocorrencia, (CASE WHEN status = 'P' THEN 'Pendente' WHEN status = 'C' THEN 'Conclu&iacute;do' ELSE status END) AS _status, (formatar(dtcriacao) || ' ' || formatar(hrcriacao)) AS _criacao"
	),
	"oferta" => array(
		"ordem" => "datainicio DESC, horainicio DESC",
		"chave" => array("codoferta"),
		"titulo" => array("Descri&ccedil;&atilde;o", "Inicio", "Encerramento", "Status"),
		"coluna" => array("descricao", "_datainicio", "_datafinal", "_status"),
		"largura" => array("55%", "15%", "15%", "15%"),
		"alinhamento" => array("left", "center", "center", "left"),
		"colunaextra" => "formatar(datainicio) AS _datainicio, formatar(datafinal) AS _datafinal, (CASE WHEN oferta.status = 'I' THEN 'Inativa' WHEN oferta.status = 'A' THEN 'Ativa'WHEN oferta.status = 'E' THEN 'Encerrada' END) AS _status"
	),
	"orcamento" => array(
		"ordem" => "codorcamento",
		"chave" => array("codorcamento"),
		"titulo" => array("N&uacute;mero", "Estabelecimento", "Cliente", "Data", "Status","Total"),
		"coluna" => array("codorcamento", "estabelecimento", "cliente", "_dtemissao", "_status","_totalliquido"),
		"largura" => array("10%", "25%", "35%", "15%", "15%","15%"),
		"alinhamento" => array("right", "left", "left", "center", "left","right"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento, cliente.nome AS cliente, formatar(dtemissao) AS _dtemissao, (CASE WHEN orcamento.status = 'P' THEN 'Pendente' WHEN orcamento.status = 'A' THEN 'Atendido' WHEN orcamento.status = 'L' THEN 'Em Analise' ELSE 'Cancelado' END) AS _status, formatar(orcamento.totalliquido,2) AS _totalliquido"
	),
	"ordemservico" => array(
		"ordem" => "datacadastro DESC, horacadastro DESC",
		"chave" => array("codos"),
		"titulo" => array("C&oacute;digo", "T&iacute;tulo", "Data", "Previs&atilde;o", "Status", "Prioridade"),
		"coluna" => array("codos", "titulo", "_datacadastro", "_dataprevisao", "_status", "_prioridade"),
		"largura" => array("8%", "40%", "10%", "10%", "18%", "14%"),
		"alinhamento" => array("right", "left", "center", "center", "left", "left"),
		"colunaextra" => "formatar(datacadastro) AS _datacadastro, formatar(dataprevisao) AS _dataprevisao, (CASE WHEN status = 'P' THEN 'Pendente para An&aacute;lise'  WHEN status = 'B' THEN 'Pendente para Publica&ccedil;&atilde;o' WHEN status = 'L' THEN 'Em An&aacute;lise' WHEN status = 'O' THEN 'Aguardando Aprova&ccedil;&atilde;o Or&ccedil;amento' WHEN status = 'D' THEN 'Em Desenvolvimento' WHEN status = 'T' THEN 'Em Testes' WHEN status = 'C' THEN 'Conclu&iacute;do' WHEN status = 'N' THEN 'Cancelado' ELSE status END) AS _status, (CASE WHEN prioridade = 'B' THEN 'Baixa' WHEN prioridade = 'M' THEN 'M&eacute;dia' WHEN prioridade = 'A' THEN 'Alta' WHEN prioridade = 'U' AND status NOT IN ('C','N') THEN '<b>Urgente</b>' WHEN prioridade = 'U' THEN 'Urgente' ELSE prioridade END) AS _prioridade"
	),
	"outroscreditodebito" => array(
		"ordem" => "codoutroscreditodebito DESC",
		"chave" => array("codoutroscreditodebito"),
		"titulo" => array("C&oacute;digo", "Estabelecimento", "Data", "Descricao"),
		"coluna" => array("codoutroscreditodebito", "estabelecimento", "_dtdocumento", "descricaoajuste"),
		"largura" => array("7%", "25%", "10%", "58%"),
		"alinhamento" => array("right", "left", "center", "left"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = outroscreditodebito.codestabelec) AS estabelecimento, formatar(dtdocumento) AS _dtdocumento"
	),
	"paramcomissao" => array(
		"ordem" => "estabelecimento",
		"chave" => array("codestabelec"),
		"titulo" => array("Estabelecimento"),
		"coluna" => array("estabelecimento"),
		"largura" => array("100%"),
		"alinhamento" => array("left"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento"
	),
	"paramcoletor" => array(
		"ordem" => "estabelecimento",
		"chave" => array("codestabelec"),
		"titulo" => array("C&oacute;digo", "Estabelecimento", "Arquivo Exporta&ccedil;&atilde;o", "Arquivo Importa&ccedil;&atilde;o"),
		"coluna" => array("codestabelec", "estabelecimento", "exp_nomearquivo", "imp_nomearquivo"),
		"largura" => array("10%", "26%", "32%", "32%"),
		"alinhamento" => array("right", "left", "left", "left"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = paramcoletor.codestabelec) AS estabelecimento"
	),
	"paramestoque" => array(
		"ordem" => "emitente",
		"chave" => array("codemitente"),
		"titulo" => array("C&ooacute;digo", "Emitente"),
		"coluna" => array("codemitente", "emitente"),
		"largura" => array("15%", "85%"),
		"alinhamento" => array("right", "left"),
		"colunaextra" => "(SELECT nome FROM emitente WHERE codemitente = paramestoque.codemitente) AS emitente"
	),
	"paramfiscal" => array(
		"ordem" => "estabelecimento",
		"chave" => array("codestabelec"),
		"titulo" => array("Estabelecimento"),
		"coluna" => array("estabelecimento"),
		"largura" => array("100%"),
		"alinhamento" => array("left"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento"
	),
	"parametro" => array(
		"ordem" => "idparam, codparam",
		"chave" => array("idparam", "codparam"),
		"titulo" => array("Identificador", "C&oacute;digo", "Valor", "Observa&ccedil;&atilde;o"),
		"coluna" => array("idparam", "codparam", "valor_", "observacao"),
		"largura" => array("15%", "15%", "20%", "50%"),
		"alinhamento" => array("left", "left", "left"),
		"colunaextra" => "(SELECT CASE WHEN p.codparam = 'PUBLICIDADE' THEN 'PUBLICIDADE' ELSE p.valor END FROM parametro AS p WHERE p.idparam = parametro.idparam AND p.codparam = parametro.codparam limit 1) AS valor_"
	),
	"paramfat" => array(
		"ordem" => "emitente",
		"chave" => array("codemitente"),
		"titulo" => array("Emitente"),
		"coluna" => array("emitente"),
		"largura" => array("100%"),
		"alinhamento" => array("left"),
		"colunaextra" => "emitente.nome AS emitente"
	),
	"paramnotafiscal" => array(
		"ordem" => "estabelecimento, operacaonota",
		"chave" => array("codestabelec", "operacao"),
		"titulo" => array("Estabelecimento", "Opera&ccedil;&atilde;o"),
		"coluna" => array("estabelecimento", "operacaonota"),
		"largura" => array("50%", "50%"),
		"alinhamento" => array("left", "left"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = paramnotafiscal.codestabelec) AS estabelecimento, (SELECT descricao FROM operacaonota WHERE operacao = paramnotafiscal.operacao) AS operacaonota"
	),
	"paramfidelizacao" => array(
		"ordem" => "estabelecimento",
		"chave" => array("codestabelec"),
		"titulo" => array("Estabelecimento"),
		"coluna" => array("estabelecimento"),
		"largura" => array("100%"),
		"alinhamento" => array("left"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento"
	),
	"parampedido" => array(
		"ordem" => "estabelecimento, operacaonota",
		"chave" => array("codestabelec", "operacao"),
		"titulo" => array("Estabelecimento", "Opera&ccedil;&atilde;o"),
		"coluna" => array("estabelecimento", "operacaonota"),
		"largura" => array("50%", "50%"),
		"alinhamento" => array("left", "left"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento, operacaonota.descricao AS operacaonota"
	),
	"paramplanodecontas" => array(
		"ordem" => "codestabelec",
		"chave" => array("codestabelec", "codconta"),
		"titulo" => array("Codigo","Estabelecimento"),
		"coluna" => array("codestabelec","estabelecimento",),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = paramplanodecontas.codestabelec) AS estabelecimento"
	),
	"parampontovenda" => array(
		"ordem" => "estabelecimento",
		"chave" => array("codestabelec"),
		"titulo" => array("C&oacute;digo", "Estabelecimento"),
		"coluna" => array("codestabelec", "estabelecimento"),
		"largura" => array("15%", "85%"),
		"alinhamento" => array("right", "left"),
		"colunaextra" => "(SELECT nome FROM estabelecimento WHERE codestabelec = parampontovenda.codestabelec) AS estabelecimento"
	),
	"parampontooperacao" => array(
		"ordem" => "descricao",
		"chave" => array("operacao"),
		"titulo" => array("Opera&ccedil;&atilde;o"),
		"coluna" => array("descricao"),
		"largura" => array("100%"),
		"alinhamento" => array("left"),		
		"colunaextra" => "(SELECT descricao FROM operacaonota WHERE operacaonota.operacao = parampontooperacao.operacao) AS descricao"
	),
	"paramrec" => array(
		"ordem" => "emitente",
		"chave" => array("codemitente"),
		"titulo" => array("Emitente"),
		"coluna" => array("emitente"),
		"largura" => array("100%"),
		"alinhamento" => array("left"),
		"colunaextra" => "emitente.nome AS emitente"
	),
	"pedido" => array(
		"ordem" => "dtemissao, estabelecimento, parceiro",
		"chave" => array("codestabelec", "numpedido"),
		"titulo" => array("Data", "Estabelecimento", "N&uacute;mero", "Parceiro", "Status", "Total"),
		"coluna" => array("_dtemissao", "estabelecimento", "numpedido", "parceiro", "_status", "_totalliquido"),
		"largura" => array("12%", "25%", "8%", "30%", "12%", "12%"),
		"alinhamento" => array("center", "left", "right", "left", "left", "right"),
		"colunaextra" => "estabelecimento.nome AS estabelecimento, (CASE WHEN pedido.tipoparceiro = 'C' THEN cliente.nome WHEN pedido.tipoparceiro = 'F' THEN fornecedor.nome ELSE estabelecimento2.nome END) AS parceiro, formatar(dtemissao) AS _dtemissao, (CASE WHEN pedido.status = 'P' THEN 'Pendente' WHEN pedido.status = 'A' THEN 'Atendido' WHEN pedido.status = 'L' THEN 'Em Analise' WHEN pedido.status = 'I' THEN 'Parc Atendido' WHEN pedido.status = 'C' THEN 'Cancelado' ELSE pedido.status END) AS _status, formatar(pedido.totalliquido,2) AS _totalliquido"
	),
	"pedido_TE" => array(
		"ordem" => "pedido.dtemissao, estabelecimento, parceiro",
		"chave" => array("codestabelec", "numpedido"),
		"titulo" => array("Data", "N&uacute;mero", "Origem", "Destino", "Status", "Nota Fiscal"),
		"coluna" => array("_dtemissao", "numpedido", "parceiro", "estabelecimento", "_status", "notafiscal"),
		"largura" => array("10%", "10%", "25%", "25%", "15%", "15%"),
		"alinhamento" => array("center", "right", "left", "left", "left", "center"),
		"colunaextra" => "(notafiscal.numnotafis || '-' || notafiscal.serie) AS notafiscal, estabelecimento.nome AS estabelecimento, (CASE WHEN pedido.tipoparceiro = 'C' THEN cliente.nome WHEN pedido.tipoparceiro = 'F' THEN fornecedor.nome ELSE estabelecimento2.nome END) AS parceiro, formatar(pedido.dtemissao) AS _dtemissao, (CASE WHEN pedido.status = 'P' THEN 'Pendente' WHEN pedido.status = 'A' THEN 'Atendido' WHEN pedido.status = 'L' THEN 'Em Analise' WHEN pedido.status = 'C' THEN 'Cancelado' ELSE pedido.status END) AS _status"
	),
	"piscofins" => array(
		"ordem" => "descricao",
		"chave" => array("codpiscofins"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Al&iacute;quota PIS", "Al&iacute;quota COFINS"),
		"coluna" => array("codpiscofins", "descricao", "_aliqpis", "_aliqcofins"),
		"largura" => array("10%", "50%", "20%", "20%"),
		"alinhamento" => array("right", "left", "right", "right"),
		"colunaextra" => "formatar(aliqpis,2) AS _aliqpis, formatar(aliqcofins,2) AS _aliqcofins"
	),
	"planocontas" => array(
		"ordem" => "planocontas.codconta",
		"chave" => array("codconta"),
		"titulo" => array("C&oacute;digo", "Nome", "Conta Contabil"),
		"coluna" => array("codconta", "nome", "contacontabil"),
		"largura" => array("10%", "60%", "30%"),
		"alinhamento" => array("right", "left", "left"),
	),
	"premio" => array(
		"ordem" => "codpremio",
		"chave" => array("codpremio"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Produto", "Qtde Venda", "Ativo"),
		"coluna" => array("codpremio", "descricao", "_produto", "qtdevenda", "_ativo"),
		"largura" => array("10%", "35%", "35%", "10%", "10%"),
		"alinhamento" => array("right", "left", "left", "right", "center"),
		"colunaextra" => "(SELECT descricaofiscal FROM produto WHERE codproduto = premio.codproduto) AS _produto, (CASE WHEN ativo = 'S' THEN 'Sim' ELSE 'N&atilde;o' END) AS _ativo"
	),
	"produto" => array(
		"ordem" => "descricaofiscal",
		"chave" => array("codproduto"),
		"titulo" => array_merge(array("C&oacute;digo", "Descri&ccedil;&atilde;o"), ($param_cadastro_exibirprecoprod == "S" ? array("Custo Rep", "Pre&ccedil;o Varejo", "Pre&ccedil;o Atacado") : array("Departamento")), array("Fora Linha"), array("Similar")),
		"coluna" => array_merge(array("codproduto", "descricaofiscal"), ($param_cadastro_exibirprecoprod == "S" ? array("_custorep", "_precovrj", "_precoatc") : array("_departamento")), array("foralinha"), array("codsimilar")),
		"largura" => array_merge(array("10%", "35%"), ($param_cadastro_exibirprecoprod == "S" ? array("10%", "10%", "10%") : array("30%")), array("10%"), array("8%")),
		"alinhamento" => array_merge(array("right", "left"), ($param_cadastro_exibirprecoprod == "S" ? array("right", "right", "right") : array("left")), array("center"), array("right")),
		"colunaextra" => "formatar(produto.custorep,2) AS _custorep, formatar(produto.precovrj,2) AS _precovrj, formatar(produto.precoatc,2) AS _precoatc, (CASE WHEN produto.foralinha = 'S' THEN 'S I M' WHEN produto.foralinha = 'N' THEN '' ELSE produto.foralinha END) AS _foralinha, (SELECT nome FROM departamento WHERE departamento.coddepto = produto.coddepto LIMIT 1) AS _departamento"
	),
	"produtolocalizacao" => array(
		"ordem" => "codproduto",
		"chave" => array("idprodutolocalizacao"),
		"titulo" => array("C&oacute;digo Prod", "Localiza&ccedil;&atilde;o"),
		"coluna" => array("codproduto", "localizacao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"profissao" => array(
		"ordem" => "nome",
		"chave" => array("codprofissao"),
		"titulo" => array("C&oacute;digo", "Nome"),
		"coluna" => array("codprofissao", "nome"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"receita" => array(
		"ordem" => "descricao",
		"chave" => array("codreceita"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codreceita", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"regiao" => array(
		"ordem" => "nome",
		"chave" => array("codregiao"),
		"titulo" => array("C&oacute;digo", "Nome", "CEP"),
		"coluna" => array("codregiao", "nome", "cep"),
		"largura" => array("10%", "70%", "20%"),
		"alinhamento" => array("right", "left", "left")
	),
	"relatorio" => array(
		"ordem" => "descricao",
		"chave" => array("codrelatorio"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Data de Cria&ccedil;&atilde;o"),
		"coluna" => array("codrelatorio", "descricao", "_datacriacao"),
		"largura" => array("15%", "65%", "20%"),
		"alinhamento" => array("right", "left", "center"),
		"colunaextra" => "formatar(datacriacao) AS _datacriacao"
	),
	"representante" => array(
		"ordem" => "nome",
		"chave" => array("codrepresentante"),
		"titulo" => array("C&oacute;digo", "Nome", "Estado", "Cidade"),
		"coluna" => array("codrepresentante", "nome", "uf", "_cidade"),
		"largura" => array("10%", "50%", "10%", "30%"),
		"alinhamento" => array("right", "left", "center", "left"),
		"colunaextra" => "(SELECT nome FROM cidade WHERE codcidade = representante.codcidade) AS _cidade"
	),
	"rma" => array(
		"ordem" => "dtcriacao, hrcriacao",
		"chave" => array("codrma"),
		"titulo" => array("C&oacute;digo", "Data", "Hora", "Tipo", "Status", "Cliente"),
		"coluna" => array("codrma", "_dtcriacao", "_hrcriacao", "_tipo", "_status", "_cliente"),
		"largura" => array("8%", "10%", "10%", "20%", "20%", "32%"),
		"alinhamento" => array("right", "center", "center", "left", "left", "left"),
		"colunaextra" => "formatar(dtcriacao) AS _dtcriacao, formatar(hrcriacao) AS _hrcriacao, (CASE status WHEN 'P' THEN 'Aguardando Aprova&ccedil;&atilde;o' WHEN 'A' THEN 'Aprovado' WHEN 'R' THEN 'Reprovado' WHEN 'L' THEN 'Em An&aacute;lise' WHEN 'F' THEN 'Finalizado' WHEN 'C' THEN 'Cancelado' ELSE status END) AS _status, (CASE tipo WHEN 'D' THEN 'Devolu&ccedil;&atilde;o' WHEN 'F' THEN 'Troca (Defeito)' WHEN 'E' THEN 'Troca (Produto Errado)' WHEN 'S' THEN 'Troca (Desist&ecirc;ncia)' ELSE tipo END) AS _tipo, (SELECT nome FROM cliente WHERE codcliente = rma.codcliente) AS _cliente"
	),
	"sazonal" => array(
		"ordem" => "descricao",
		"chave" => array("codsazonal"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codsazonal", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"setorocorrencia" => array(
		"ordem" => "descricao",
		"chave" => array("codsetor"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codsetor", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"simprod" => array(
		"ordem" => "descricao",
		"chave" => array("codsimilar"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codsimilar", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"situacaolancto" => array(
		"ordem" => "descricao",
		"chave" => array("codsituacao"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codsituacao", "descricao"),
		"largura" => array("10%", "90%"),
		"alinhamento" => array("right", "left")
	),
	"statuscliente" => array(
		"ordem" => "codstatus",
		"chave" => array("codstatus"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Bloqueado"),
		"coluna" => array("codstatus", "descricao", "_bloqueado"),
		"largura" => array("10%", "60%", "30%"),
		"alinhamento" => array("right", "left", "center"),
		"colunaextra" => "(CASE WHEN bloqueado = 'S' THEN 'Sim' WHEN bloqueado = 'N' THEN 'N&atilde;o' ELSE bloqueado END) AS _bloqueado"
	),
	"subcatlancto" => array(
		"ordem" => "descricao",
		"chave" => array("codsubcatlancto"),
		"titulo" => array("C&oacute;digo", "Categoria", "Descri&ccedil;&atilde;o"),
		"coluna" => array("codsubcatlancto", "catlancto", "descricao"),
		"largura" => array("10%", "40%", "50%"),
		"alinhamento" => array("right", "left", "left"),
		"colunaextra" => "catlancto.descricao AS catlancto"
	),
	"subgrupo" => array(
		"ordem" => "descricao",
		"chave" => array("codsubgrupo"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Grupo", "Departamento"),
		"coluna" => array("codsubgrupo", "descricao", "grupoprod", "departamento"),
		"largura" => array("10%", "40%", "30%", "20%"),
		"alinhamento" => array("right", "left", "left", "left"),
		"colunaextra" => "grupoprod.descricao AS grupoprod, departamento.nome AS departamento"
	),
	"tabelapreco" => array(
		"ordem" => "descricao",
		"chave" => array("codtabela"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Tipo de Pre&ccedil;o", "Percentual do Pre&ccedil;o"),
		"coluna" => array("codtabela", "descricao", "_tipopreco", "_percpreco"),
		"largura" => array("10%", "50%", "20%", "20%"),
		"alinhamento" => array("right", "left", "left", "right"),
		"colunaextra" => "(CASE WHEN tipopreco = 'A' THEN 'Atacado' WHEN tipopreco = 'V' THEN 'Varejo' ELSE tipopreco END) AS _tipopreco, formatar(percpreco,2) AS _percpreco"
	),
	"tesouraria" => array(
		"ordem" => "codestabelec, dtmovto, caixa, numfechamento",
		"chave" => array("codtesouraria"),
		"titulo" => array("Estabelecimento", "Data", "Caixa", "Operador", "Valor Total"),
		"coluna" => array("estabelecimento", "_dtmovto", "caixa", "funcionario", "_valortotal"),
		"largura" => array("25%", "15%", "10%", "35%", "15%"),
		"alinhamento" => array("left", "center", "center", "left", "right"),
		"colunaextra" => "formatar(dtmovto) AS _dtmovto, formatar(valortotal,2) AS _valortotal, (SELECT nome FROM estabelecimento WHERE codestabelec = tesouraria.codestabelec) AS estabelecimento, (SELECT nome FROM funcionario WHERE codfunc = tesouraria.codfunc) AS funcionario"
	),
	"tipodocumento" => array(
		"ordem" => "descricao",
		"chave" => array("codtpdocto"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Tipo"),
		"coluna" => array("codtpdocto", "descricao", "_tipo"),
		"largura" => array("10%", "70%", "20%"),
		"alinhamento" => array("right", "left", "left"),
		"colunaextra" => "(CASE WHEN tipo = 'E' THEN 'Entrada' WHEN tipo = 'S' THEN 'Sa&iacute;da' WHEN tipo = 'F' THEN 'Financeiro' ELSE tipo END) AS _tipo"
	),
	"transportadora" => array(
		"ordem" => "nome",
		"chave" => array("codtransp"),
		"titulo" => array("C&oacute;digo", "Nome", "Telefone", "Estado", "Cidade"),
		"coluna" => array("codtransp", "nome", "fone", "estado", "cidade"),
		"largura" => array("10%", "35%", "15%", "15%", "25%"),
		"alinhamento" => array("right", "left", "left", "left", "left"),
		"colunaextra" => "estado.nome AS estado, cidade.nome AS cidade"
	),
	"tv" => array(
		"ordem" => "descricao",
		"chave" => array("codtv"),
		"titulo" => array("C&oacute;digo","Descri&ccedil;&atilde;o","Grupo"),
		"coluna" => array("codtv","descricao","grupo"),
		"largura" => array("10%","50%","40%"),
		"alinhamento" => array("center")
	),
	"unidade" => array(
		"ordem" => "descricao",
		"chave" => array("codunidade"),
		"titulo" => array("C&oacute;digo", "Descri&ccedil;&atilde;o", "Sigla"),
		"coluna" => array("codunidade", "descricao", "sigla"),
		"largura" => array("10%", "70%", "20%"),
		"alinhamento" => array("right", "left", "left")
	),
	"usuario" => array(
		"ordem" => "nome",
		"chave" => array("login"),
		"titulo" => array("Login", "Nome", "E-mail","Bloqueado"),
		"coluna" => array("login", "nome", "email","bloqueado"),
		"largura" => array("15%", "55%", "30%","10%"),
		"alinhamento" => array("left", "left", "left","center")
	)
);