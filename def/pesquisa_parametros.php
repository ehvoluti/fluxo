<?php

$parametros = array(
	"administradora" => array(
		"campo" => array("nome", "cnpj", "fone"),
		"chave" => array("codadmininst"),
		"descricao" => "Administradora {codadminist}: {nome}",
		"idtable" => "CadAdministradora",
		"ordem" => "nome",
		"titulo" => "Administradoras"
	),
	"banco" => array(
		"campo" => array("nome", "conta", "agencia"),
		"chave" => array("codbanco"),
		"descricao" => "Banco {codbanco}: {nome}",
		"idtable" => "CadBanco",
		"ordem" => "nome",
		"titulo" => "Bancos"
	),
	"catlancto" => array(
		"campo" => array("descricao"),
		"chave" => array("codcatlancto"),
		"descricao" => "Categoria de Lan&ccedil;amento {codcatlancto}: {descricao}",
		"idtable" => "CadCatLancto",
		"ordem" => "descricao",
		"titulo" => "Categorias de Lan&ccedil;amento"
	),
	"cidade" => array(
		"campo" => array("nome", "codoficial"),
		"chave" => array("codcidade"),
		"descricao" => "Cidade {codcidade}: {nome}",
		"idtable" => "Cidade",
		"ordem" => "nome",
		"titulo" => "Cidades"
	),
	"classfiscal" => array(
		"campo" => array("descricao"),
		"chave" => array("codcf"),
		"descricao" => "Classifica&ccedil;&atilde;o Fiscal {codcf}: {descricao}",
		"idtable" => "CFiscal",
		"ordem" => "descricao",
		"titulo" => "Classifica&ccedil;&otilde;es Fiscais"
	),
	"cliente" => array(
		"campo" => array("nome", "razaosocial", "rgie", "cpfcnpj", "foneres", "fonefat", "fonefat"),
		"chave" => array("codcliente"),
		"descricao" => "Cliente {codcliente}: {nome}",
		"idtable" => "ClientePF",
		"ordem" => "nome",
		"titulo" => "Clientes"
	),
	"condpagto" => array(
		"campo" => array("descricao"),
		"chave" => array("codcondpagto"),
		"descricao" => "Condi&ccedil;&atilde;o de Pagamento {codcondpagto}: {descricao}",
		"idtable" => "CondPagto",
		"ordem" => "descricao",
		"titulo" => "Condi&ccedil;&otilde;es de Pagamento"
	),
	"contabilidade" => array(
		"campo" => array("nome", "razaosocial", "rgie", "cpfcnpj", "telefone", "nomecontador", "cpfcontador", "crccontador"),
		"chave" => array("codcontabilidade"),
		"descricao" => "Contabilidade {codcontabilidade}: {nome}",
		"idtable" => "CadContabilidade",
		"ordem" => "nome",
		"titulo" => "Contabilidades"
	),
	"cotacao" => array(
		"campo" => array("descricao"),
		"chave" => array("codcotacao"),
		"descricao" => "Cota&ccedil;&atilde;o {codcotacao}: {descricao}",
		"idtable" => "Cotacao",
		"ordem" => "descricao",
		"titulo" => "Cota&ccedil;&otilde;es"
	),
	"departamento" => array(
		"campo" => array("nome"),
		"chave" => array("coddepto"),
		"descricao" => "Departamento {coddepto}: {nome}",
		"idtable" => "Depto",
		"ordem" => "nome",
		"titulo" => "Departamentos"
	),
	"ecf" => array(
		"campo" => array("numfabricacao", "modelo"),
		"chave" => array("codecf"),
		"descricao" => "ECF {codecf}: {numfabricacao} ({modelo})",
		"idtable" => "Ecf",
		"ordem" => "numfabricacao",
		"titulo" => "Equipamento Fiscal"
	),
	"embalagem" => array(
		"campo" => array("descricao"),
		"chave" => array("codembal"),
		"descricao" => "Embalagem {codembal}: {descricao}",
		"idtable" => "Embalagem",
		"ordem" => "descricao",
		"titulo" => "Embalagens"
	),
	"emitente" => array(
		"campo" => array("nome", "razaosocial", "rgie", "cpfcnpj", "fone1", "fone2", "fax", "site", "email"),
		"chave" => array("codemitente"),
		"descricao" => "Emitente {codemitente}: {nome}",
		"idtable" => "Emitente",
		"ordem" => "nome",
		"titulo" => "Emitentes"
	),
	"especie" => array(
		"campo" => array("descricao"),
		"chave" => array("codespecie"),
		"descricao" => "Esp&eacute;cie {codespecie}: {descricao}",
		"idtable" => "Especie",
		"ordem" => "descricao",
		"titulo" => "Esp&eacutecies"
	),
	"estabelecimento" => array(
		"campo" => array("nome", "razaosocial", "rgie", "cpfcnpj", "fone1", "fone2", "fax", "email"),
		"chave" => array("codestabelec"),
		"descricao" => "Estabelecimento {codestabelec}: {nome}",
		"idtable" => "Estabel",
		"ordem" => "nome",
		"titulo" => "Estabelecimentos"
	),
	"etiqgondola" => array(
		"campo" => array("descricao"),
		"chave" => array("codetiqgondola"),
		"descricao" => "Etiqueta de Gond&ocirc;la {codetiqgondola}: {descricao}",
		"idtable" => "CadEtiqGondola",
		"ordem" => "descricao",
		"titulo" => "Etiquetas de G&ocirc;ndola"
	),
	"familia" => array(
		"campo" => array("descricao"),
		"chave" => array("codfamilia"),
		"descricao" => "Familia {codfamilia}: {descricao}",
		"idtable" => "CadFamilia",
		"ordem" => "descricao",
		"titulo" => "Familias de Produto"
	),
	"finalizadora" => array(
		"campo" => array("descricao"),
		"chave" => array("codfinaliz"),
		"descricao" => "Finalizadora {codfinaliz}: {descricao}",
		"idtable" => "CadFinalizadora",
		"ordem" => "descricao",
		"titulo" => "Finalizadoras"
	),
	"fornecedor" => array(
		"campo" => array("nome", "razaosocial", "rgie", "cpfcnpj", "fone", "site", "email"),
		"chave" => array("codfornec"),
		"descricao" => "Fornecedor {codfornec}: {nome}",
		"idtable" => "Fornecedor",
		"ordem" => "nome",
		"titulo" => "Fornecedores"
	),
	"funcionario" => array(
		"campo" => array("nome", "rgie", "cpfcnpj", "fone1", "fone2", "email", "contato", "nummatricula"),
		"chave" => array("codfunc"),
		"descricao" => "Funcion&aacute;rio {codfunc}: {nome}",
		"idtable" => "CadFuncionario",
		"ordem" => "nome",
		"titulo" => "Funcion&aacute;rios"
	),
	"grupo" => array(
		"campo" => array("nome", "loginresp"),
		"chave" => array("codgrupo"),
		"descricao" => "Grupo {codgrupo}: {nome}",
		"idtable" => "GrupoUsuario",
		"ordem" => "nome",
		"titulo" => "Grupos de Usu&aacute;rios"
	),
	"grupoprod" => array(
		"campo" => array("descricao"),
		"chave" => array("codgrupo"),
		"descricao" => "Grupo {codgrupo}: {descricao}",
		"idtable" => "Grupo",
		"ordem" => "descricao",
		"titulo" => "Grupos de Produto"
	),
	"interesse" => array(
		"campo" => array("descricao"),
		"chave" => array("codinteresse"),
		"descricao" => "Interesse {codinteresse}: {descricao}",
		"idtable" => "CadInterrese",
		"ordem" => "descricao",
		"titulo" => "Interesses de Cliente"
	),
	"inventario" => array(
		"campo" => array("descricao"),
		"chave" => array("codinventario"),
		"descricao" => "Invent&aacute;rio {codinventario}: {descricao}",
		"idtable" => "Inventario",
		"ordem" => "descricao",
		"titulo" => "Invent&aacute;rios"
	),
	"ipi" => array(
		"campo" => array("descricao"),
		"chave" => array("codipi"),
		"descricao" => "IPI {codipi}: {descricao}",
		"idtable" => "CadIpi",
		"ordem" => "descricao",
		"titulo" => "IPI"
	),
	"lancamento" => array(
		"campo" => array("favorecido"),
		"chave" => array("codlancto"),
		"descricao" => "Lan&ccedil;amento {codlancto}: {favorecido}",
		"idtable" => "Lancto",
		"ordem" => "dtvencto DESC",
		"titulo" => "Fluxo de Caixa"
	),
	"lancamentogru" => array(
		"campo" => array("favorecido"),
		"chave" => array("codlanctogru"),
		"descricao" => "Lan&ccedil;amento {codlanctogru}: {favorecido}",
		"idtable" => "LanctoGru",
		"ordem" => "dtemissao DESC",
		"titulo" => "Lan&ccedil;amentos"
	),
	"layout" => array(
		"campo" => array("descricao", "nomearquivo"),
		"chave" => array("codlayout"),
		"descricao" => "Layout {codlayout}: {descricao}",
		"idtable" => "CadLayout",
		"ordem" => "descricao",
		"titulo" => "Layouts"
	),
	"marca" => array(
		"campo" => array("descricao"),
		"chave" => array("codmarca"),
		"descricao" => "Marca {codmarca}: {descricao}",
		"idtable" => "CadMarca",
		"ordem" => "descricao",
		"titulo" => "Marcas"
	),
	"natoperacao" => array(
		"campo" => array("natoperacao", "descricao"),
		"chave" => array("natoperacao"),
		"descricao" => "Natureza de Opera&ccedil;&atilde;o {natoperacao}: {descricao}",
		"idtable" => "NatOper",
		"ordem" => "natoperacao",
		"titulo" => "Naturezas de Opera&ccedil;&atilde;o"
	),
	"ncm" => array(
		"campo" => array("codigoncm", "descricao"),
		"chave" => array("idncm"),
		"descricao" => "NCM {codigoncm}: {descricao}",
		"idtable" => "CadNcm",
		"ordem" => "descricao",
		"titulo" => "NCM"
	),
	"nutricional" => array(
		"campo" => array("descricao", "descricaoporcao"),
		"chave" => array("codnutricional"),
		"descricao" => "Nutricional {codnutricional}: {descricao}",
		"idtable" => "Nutricional",
		"ordem" => "descricao",
		"titulo" => "Informa&ccedil;&otilde;es Nutricionais"
	),
	"ocorrencia" => array(
		"campo" => array("titulo", "observacao"),
		"chave" => array("codocorrencia"),
		"descricao" => "Ocorr&ecirc;ncia {codocorrencia}: {titulo}",
		"idtable" => "CadOcorrencia",
		"ordem" => "titulo",
		"titulo" => "Ocorr&ecirc;ncias"
	),
	"oferta" => array(
		"campo" => array("descricao"),
		"chave" => array("codoferta"),
		"descricao" => "Oferta {codoferta}: {descricao}",
		"idtable" => "ProgramacaoOferta",
		"ordem" => "descricao",
		"titulo" => "Programa&ccedil;&atilde;o de Ofertas"
	),
	"parametro" => array(
		"campo" => array("idparam", "codparam", "observacao", "valor"),
		"chave" => array("idparam", "codparam"),
		"descricao" => "Par&acirc;metro {idparam} {codparam}",
		"idtable" => "Parametro",
		"ordem" => "idparam, codparam",
		"titulo" => "Par&acirc;metros do Sistema"
	),
	"piscofins" => array(
		"campo" => array("descricao"),
		"chave" => array("codpiscofins"),
		"descricao" => "PIS/Cofins {codpiscofins}: {descricao}",
		"idtable" => "CadPisCofins",
		"ordem" => "descricao",
		"titulo" => "PIS/Cofins"
	),
	"produto" => array(
		"campo" => array("descricao", "descricaofiscal"),
		"chave" => array("codproduto"),
		"descricao" => "Produto {codproduto}: {descricaofiscal}",
		"idtable" => "Produto",
		"ordem" => "descricaofiscal",
		"titulo" => "Produtos"
	),
	"programa" => array(
		"campo" => array("nome", "titulo"),
		"chave" => array("idtable"),
		"descricao" => "<img src=\"{imagem}\" style=\"width:11px\"> {titulo}",
		"idtable" => "",
		"ordem" => "titulo",
		"titulo" => "Menu de Acesso"
	),
	"receita" => array(
		"campo" => array("descricao", "componentes", "modopreparo"),
		"chave" => array("codreceita"),
		"descricao" => "Receita {codreceita}: {descricao}",
		"idtable" => "Receita",
		"ordem" => "descricao",
		"titulo" => "Receitas"
	),
	"relatorio" => array(
		"campo" => array("descricao", "instrucao", "observacao"),
		"chave" => array("codrelatorio"),
		"descricao" => "Relat&oacute;rio {codrelatorio}: {descricao}",
		"idtable" => "GeradorRelatorio",
		"ordem" => "descricao",
		"titulo" => "Gerador de Relat&oacute;rios"
	),
	"sazonal" => array(
		"campo" => array("descricao", "mensagem"),
		"chave" => array("codsazonal"),
		"descricao" => "Sazonalidade {codsazonal}: {descricao}",
		"idtable" => "Sazonalidade",
		"ordem" => "descricao",
		"titulo" => "Sazonalidades"
	),
	"simprod" => array(
		"campo" => array("descricao"),
		"chave" => array("codsimilar"),
		"descricao" => "Similar {codsimilar}: {descricao}",
		"idtable" => "Similar",
		"ordem" => "descricao",
		"titulo" => "Similares"
	),
	"statuscliente" => array(
		"campo" => array("descricao"),
		"chave" => array("codstatus"),
		"descricao" => "Status {codstatus}: {descricao}",
		"idtable" => "CadStatusCliente",
		"ordem" => "descricao",
		"titulo" => "Status de Cliente"
	),
	"subcatlancto" => array(
		"campo" => array("descricao"),
		"chave" => array("codsubcatlancto"),
		"descricao" => "SubCategoria de Lan&ccedil;amento {codsubcatlancto}: {descricao}",
		"idtable" => "CadSubCatLancto",
		"ordem" => "descricao",
		"titulo" => "SubCategorias de Lan&ccedil;amento"
	),
	"subgrupo" => array(
		"campo" => array("descricao"),
		"chave" => array("codsubgrupo"),
		"descricao" => "SubGrupo {codsubgrupo}: {descricao}",
		"idtable" => "SubGrupo",
		"ordem" => "descricao",
		"titulo" => "SubGrupos de Produto"
	),
	"tipodocumento" => array(
		"campo" => array("descricao"),
		"chave" => array("codtpdocto"),
		"descricao" => "Tipo de Documento {codtpdocto}: {descricao}",
		"idtable" => "TpDocto",
		"ordem" => "descricao",
		"titulo" => "Tipos de Documento"
	),
	"transportadora" => array(
		"campo" => array("nome", "razaosocial", "cpfcnpj", "rgie", "site", "email", "fone", "fax", "contato1", "contato2", "contato3"),
		"chave" => array("codtransp"),
		"descricao" => "Transportadora {codtransp}: {nome}",
		"idtable" => "Transportadora",
		"ordem" => "nome",
		"titulo" => "Transportadoras"
	),
	"unidade" => array(
		"campo" => array("descricao"),
		"chave" => array("codunidade"),
		"descricao" => "Unidade {codunidade}: {descricao}",
		"idtable" => "Unidade",
		"ordem" => "descricao",
		"titulo" => "Unidades"
	),
	"usuario" => array(
		"campo" => array("login", "nome", "email"),
		"chave" => array("login"),
		"descricao" => "Usu&aacute;rio {login} ({nome})",
		"idtable" => "Usuario",
		"ordem" => "login",
		"titulo" => "Usu&aacute;rios"
	)
);