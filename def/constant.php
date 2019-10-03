<?php

// Icms interestadual - Exemplo: IcmsInterestadual["N"]["S"] (Primeiro nivel = origem, segundo nivel = destino)
$IcmsInterestadual = array("N" => array("N" => 12.00, "S" => 12.00), "S" => array("N" => 7.00, "S" => 12.00));

// Tabelas no banco de dados - Exemplo: TabelasDB["paramestoque"] = "Parametros de Estoque"
$TabelasDB = array(
	"catlancto" => "Categoria de Lan&ccedil;amentos",
	"classfiscal" => "Classifica&ccedil;&atilde;o Fiscal",
	"cupom" => "Cupom",
	"departamento" => "Departamento",
	"estabelecimento" => "Estabelecimento",
	"fornecedor" => "Fornecedor",
	"grupoprod" => "Grupo de Produtos",
	"movimento" => "Movimenta&ccedil;&otilde;es de Produtos",
	"notafiscal" => "Nota Fiscal de Entrada",
	"paramestoque" => "Parametriza&ccedil;&otilde;o de Estoque",
	"produto" => "Produto",
	"produtoestab" => "Estoque de Produtos",
	"subcatlancto" => "SubCategoria de Lan&ccedil;amentos",
	"subgrupo" => "SubGrupo de Produtos"
);
